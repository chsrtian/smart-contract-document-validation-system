<?php

namespace App\Services;

use App\Jobs\ExecuteBackupJob;
use App\Models\Backup;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class BackupService
{
    /**
     * Create a manual backup.
     */
    public static function createManualBackup(User $initiator, array $timeframe): Backup
    {
        $timeframeLabel = (string) data_get($timeframe, 'label', 'Selected timeframe');
        $description = Str::limit('Manual backup - ' . $timeframeLabel, 255, '');

        $backup = Backup::create([
            'type' => 'manual',
            'description' => $description,
            'status' => 'pending',
            'initiated_by' => $initiator->id,
            'started_at' => now(),
            'includes_database' => true,
            'includes_documents' => true,
            'includes_audit_logs' => true,
        ]);

        // Dispatch job to execute backup using the existing backup record.
        ExecuteBackupJob::dispatch($backup->id, $initiator->id, $description);

        // Trigger queue processing for environments without a persistent worker.
        self::ensureBackupQueueProcessing();

        // Log audit
        AuditLogService::log(
            'backup.initiated',
            $backup,
            null,
            ['type' => 'manual', 'status' => 'pending', 'timeframe' => $timeframe],
            'info',
            'Manual backup initiated by admin for ' . $timeframeLabel
        );

        return $backup;
    }

    /**
     * Trigger backup queue processing for database queue connection.
     */
    public static function ensureBackupQueueProcessing(): void
    {
        if ((string) config('queue.default') !== 'database') {
            return;
        }

        $hasQueuedBackupJob = DB::table('jobs')
            ->where('payload', 'like', '%ExecuteBackupJob%')
            ->exists();

        if (!$hasQueuedBackupJob) {
            return;
        }

        $cooldownCacheKey = 'backup.queue.worker.last_kick_unix';
        $lastKickAt = (int) cache()->get($cooldownCacheKey, 0);
        if ($lastKickAt > 0 && (time() - $lastKickAt) < 20) {
            return;
        }

        cache()->put($cooldownCacheKey, time(), now()->addMinute());

        self::spawnDetachedQueueWorker();
    }

    /**
     * Reconcile stale pending backups based on queue / failed job evidence.
     *
     * This prevents backups from staying in pending forever when queue jobs
     * fail before handle() can update the backup record.
     */
    public static function reconcileBackupStatuses(): void
    {
        $pendingBackups = Backup::where('status', 'pending')->get()->keyBy('id');

        if ($pendingBackups->isEmpty()) {
            return;
        }

        $queuedBackupIds = [];
        DB::table('jobs')
            ->where('payload', 'like', '%ExecuteBackupJob%')
            ->pluck('payload')
            ->each(function ($payload) use (&$queuedBackupIds) {
                $backupId = self::extractBackupIdFromPayload((string) $payload);
                if ($backupId !== null) {
                    $queuedBackupIds[$backupId] = true;
                }
            });

        DB::table('failed_jobs')
            ->where('payload', 'like', '%ExecuteBackupJob%')
            ->orderByDesc('id')
            ->get(['payload', 'exception', 'failed_at'])
            ->each(function ($failedJob) use ($pendingBackups) {
                $backupId = self::extractBackupIdFromPayload((string) $failedJob->payload);
                if ($backupId === null || !$pendingBackups->has($backupId)) {
                    return;
                }

                /** @var Backup $backup */
                $backup = $pendingBackups->get($backupId);
                $summary = self::summarizeException((string) $failedJob->exception);

                $backup->update([
                    'status' => 'failed',
                    'failure_reason' => $summary,
                    'error_message' => (string) $failedJob->exception,
                    'completed_at' => $failedJob->failed_at ?? now(),
                ]);
            });

        // Mark very old orphaned pending backups as failed when no queue record exists.
        $orphanTimeoutMinutes = 30;
        Backup::where('status', 'pending')
            ->where('started_at', '<=', now()->subMinutes($orphanTimeoutMinutes))
            ->get()
            ->each(function (Backup $backup) use ($queuedBackupIds) {
                if (isset($queuedBackupIds[$backup->id])) {
                    return;
                }

                $backup->update([
                    'status' => 'failed',
                    'failure_reason' => 'Backup job was not found in queue after waiting for processing.',
                    'error_message' => 'No active queue job found for pending backup record. Queue worker may be offline or job payload may have failed before execution.',
                    'completed_at' => now(),
                ]);
            });
    }

    protected static function extractBackupIdFromPayload(string $payload): ?int
    {
        $decoded = json_decode($payload, true);
        if (!is_array($decoded)) {
            return null;
        }

        $command = (string) data_get($decoded, 'data.command', '');
        if ($command === '') {
            return null;
        }

        // Legacy payload format where Backup model was serialized into a property.
        if (preg_match('/App\\\\Models\\\\Backup";s:\\d+:"id";i:(\\d+)/', $command, $matches) === 1) {
            return (int) $matches[1];
        }

        // Current payload format with backupId scalar.
        if (preg_match('/backupId";i:(\\d+)/', $command, $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }

    protected static function summarizeException(string $exception): string
    {
        $firstLine = trim(Str::before($exception, "\n"));
        return Str::limit($firstLine !== '' ? $firstLine : 'Backup job failed before execution.', 500, '');
    }

    protected static function spawnDetachedQueueWorker(): void
    {
        $workerArgs = 'queue:work --stop-when-empty --queue=default --tries=1 --timeout=3600';
        $artisanPath = base_path('artisan');

        try {
            if (DIRECTORY_SEPARATOR === '\\') {
                $phpBinary = str_replace('"', '\\"', PHP_BINARY);
                $artisanBinary = str_replace('"', '\\"', $artisanPath);
                $command = 'start /B "" "' . $phpBinary . '" "' . $artisanBinary . '" ' . $workerArgs . ' >NUL 2>&1';

                Process::fromShellCommandline($command, base_path())->run();
                return;
            }

            $command = 'nohup ' . escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($artisanPath) . ' ' . $workerArgs . ' >/dev/null 2>&1 &';
            Process::fromShellCommandline($command, base_path())->run();
        } catch (\Throwable $exception) {
            Log::warning('Unable to auto-start backup queue worker.', [
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
