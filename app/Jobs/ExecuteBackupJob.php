<?php

namespace App\Jobs;

use App\Models\Backup;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use ZipArchive;

class ExecuteBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;
    public $tries = 1;

    protected $backupId;
    protected $userId;
    protected string $description;

    public function __construct(?int $backupId = null, ?int $userId = null, string $description = 'Manual backup')
    {
        $this->backupId = $backupId;
        $this->userId = $userId;
        $this->description = $description;
    }

    public function handle()
    {
        $backupRecord = null;

        try {
            $this->normalizeLegacyPayload();
            $backupRecord = $this->resolveBackupRecord();

            $timestamp = now()->format('Y-m-d_His');
            $backupName = "backup_{$timestamp}";
            $tempDir = storage_path("app/temp_backup_{$timestamp}");

            // Create temp directory
            if (!File::exists($tempDir)) {
                File::makeDirectory($tempDir, 0755, true);
            }

            // 1. Export Database
            $this->exportDatabase($tempDir, $backupName);

            // 2. Copy Document Files
            $this->copyDocuments($tempDir);

            // 3. Create ZIP Archive
            $zipPath = $this->createZipArchive($tempDir, $backupName);

            // 4. Calculate Checksum
            $checksum = hash_file('sha256', $zipPath);

            // 5. Move to backups directory
            $finalPath = "backups/{$backupName}.zip";
            if (!Storage::move("temp/{$backupName}.zip", $finalPath)) {
                throw new \RuntimeException('Failed to move backup archive to final storage path.');
            }

            // 6. Clean up temp directory
            File::deleteDirectory($tempDir);

            // Update backup record
            $backupRecord->update([
                'status' => 'completed',
                'file_path' => $finalPath,
                'file_size' => Storage::size($finalPath),
                'checksum' => $checksum,
                'failure_reason' => null,
                'error_message' => null,
                'completed_at' => now(),
            ]);

            // Log success
            AuditLogService::log(
                'backup.completed',
                $backupRecord,
                null,
                ['file_path' => $finalPath, 'file_size' => Storage::size($finalPath)],
                'info',
                $this->userId ? "Backup completed successfully by user {$this->userId}" : 'Scheduled backup completed successfully'
            );

            NotificationService::notifyBackupSuccess($backupRecord);
        } catch (\Exception $e) {
            if ($backupRecord) {
                // Update backup record as failed
                $backupRecord->update([
                    'status' => 'failed',
                    'failure_reason' => $e->getMessage(),
                    'error_message' => $e->getMessage(),
                    'completed_at' => now(),
                ]);
            }

            // Log failure
            AuditLogService::log(
                'backup.failed',
                $backupRecord,
                null,
                ['error' => $e->getMessage()],
                'critical',
                "Backup failed: {$e->getMessage()}"
            );

            if ($backupRecord) {
                NotificationService::notifyBackupFailed($backupRecord, $e->getMessage());
            }

            throw $e;
        }
    }

    protected function resolveBackupRecord(): Backup
    {
        if ($this->backupId !== null) {
            $backupRecord = Backup::find($this->backupId);

            if (!$backupRecord) {
                throw new \RuntimeException("Backup record {$this->backupId} not found.");
            }

            $backupRecord->update([
                'status' => 'in_progress',
                'description' => $backupRecord->description ?? $this->description,
                'started_at' => $backupRecord->started_at ?? now(),
                'initiated_by' => $backupRecord->initiated_by ?? $this->userId,
            ]);

            return $backupRecord->fresh();
        }

        return Backup::create([
            'type' => $this->userId ? 'manual' : 'scheduled',
            'initiated_by' => $this->userId,
            'status' => 'in_progress',
            'description' => $this->description,
            'started_at' => now(),
            'includes_database' => true,
            'includes_documents' => true,
            'includes_audit_logs' => true,
        ]);
    }

    protected function normalizeLegacyPayload(): void
    {
        // Legacy payload compatibility: older queued jobs may serialize a Backup
        // model identifier into userId due constructor signature drift.
        if ($this->userId instanceof Backup && empty($this->backupId)) {
            $this->backupId = $this->userId->id;
            $this->userId = null;
        }

        if ($this->backupId instanceof Backup) {
            $this->backupId = $this->backupId->id;
        }

        $this->backupId = is_numeric($this->backupId) ? (int) $this->backupId : null;
        $this->userId = is_numeric($this->userId) ? (int) $this->userId : null;
    }


    protected function exportDatabase($tempDir, $backupName)
    {
        $connectionName = config('database.default', 'mysql');
        $connection = config("database.connections.{$connectionName}", []);
        $driver = (string) ($connection['driver'] ?? 'mysql');

        if ($driver === 'sqlite') {
            $databaseFile = (string) ($connection['database'] ?? '');
            if ($databaseFile === '') {
                throw new \RuntimeException('SQLite backup failed: database path is not configured.');
            }

            if (!preg_match('/^[A-Za-z]:[\\\\\/]|^\//', $databaseFile)) {
                $databaseFile = database_path($databaseFile);
            }

            if (!File::exists($databaseFile)) {
                throw new \RuntimeException("SQLite backup failed: database file not found at {$databaseFile}");
            }

            File::copy($databaseFile, "{$tempDir}/database.sqlite");
            return;
        }

        if ($driver !== 'mysql') {
            throw new \RuntimeException("Unsupported backup driver: {$driver}");
        }

        $dbName = (string) ($connection['database'] ?? '');
        $dbUser = (string) ($connection['username'] ?? '');
        $dbPassword = (string) ($connection['password'] ?? '');
        $dbHost = (string) ($connection['host'] ?? '127.0.0.1');
        $dbPort = (string) ($connection['port'] ?? '3306');

        if ($dbName === '' || $dbUser === '') {
            throw new \RuntimeException('Database export failed: missing database credentials.');
        }

        $sqlFile = "{$tempDir}/{$backupName}.sql";
        $mysqldumpBinary = $this->resolveMysqldumpBinary();

        $command = [
            $mysqldumpBinary,
            "--host={$dbHost}",
            "--port={$dbPort}",
            "--user={$dbUser}",
        ];

        if ($dbPassword !== '') {
            $command[] = "--password={$dbPassword}";
        }

        $command = array_merge($command, [
            '--single-transaction',
            '--skip-lock-tables',
            '--routines',
            '--events',
            '--triggers',
            $dbName,
        ]);

        $process = new Process($command);
        $process->setTimeout(1800);
        $process->run();

        if (!$process->isSuccessful()) {
            $errorOutput = trim($process->getErrorOutput() ?: $process->getOutput());
            throw new \RuntimeException('Database export failed: ' . ($errorOutput !== '' ? $errorOutput : 'Unknown mysqldump error.'));
        }

        File::put($sqlFile, $process->getOutput());

        if (!File::exists($sqlFile) || File::size($sqlFile) === 0) {
            throw new \RuntimeException('Database export failed: dump output is empty.');
        }
    }

    protected function copyDocuments($tempDir)
    {
        $documentsPath = storage_path('app/documents');
        $backupDocumentsPath = "{$tempDir}/documents";

        if (File::exists($documentsPath)) {
            File::copyDirectory($documentsPath, $backupDocumentsPath);
        }
    }

    protected function createZipArchive($tempDir, $backupName)
    {
        $zipPath = storage_path("app/temp/{$backupName}.zip");
        
        if (!File::exists(storage_path('app/temp'))) {
            File::makeDirectory(storage_path('app/temp'), 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception("Cannot create ZIP archive");
        }

        $files = File::allFiles($tempDir);
        foreach ($files as $file) {
            $relativePath = str_replace($tempDir . '/', '', $file->getPathname());
            $zip->addFile($file->getPathname(), $relativePath);
        }

        $zip->close();

        return $zipPath;
    }

    protected function resolveMysqldumpBinary(): string
    {
        $candidates = array_filter([
            env('MYSQLDUMP_PATH'),
            config('database.connections.mysql.mysqldump_path'),
            'C:/xampp/mysql/bin/mysqldump.exe',
            'C:/Program Files/MySQL/MySQL Server 8.0/bin/mysqldump.exe',
            'C:/Program Files/MySQL/MySQL Server 5.7/bin/mysqldump.exe',
            'mysqldump',
        ]);

        foreach ($candidates as $candidate) {
            $candidate = (string) $candidate;
            if ($candidate === '') {
                continue;
            }

            if (str_contains($candidate, '/') || str_contains($candidate, '\\\\')) {
                if (File::exists($candidate)) {
                    return $candidate;
                }
                continue;
            }

            $probeCommand = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'
                ? ['where', $candidate]
                : ['which', $candidate];

            $probe = new Process($probeCommand);
            $probe->run();
            if ($probe->isSuccessful()) {
                return $candidate;
            }
        }

        throw new \RuntimeException('mysqldump executable not found. Configure MYSQLDUMP_PATH or add mysqldump to system PATH.');
    }
}