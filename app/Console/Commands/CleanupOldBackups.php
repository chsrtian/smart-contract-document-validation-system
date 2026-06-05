<?php

namespace App\Console\Commands;

use App\Models\Backup;
use App\Services\AuditLogService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CleanupOldBackups extends Command
{
    protected $signature = 'backup:cleanup';
    protected $description = 'Clean up old backup files based on retention policy';

    public function handle()
    {
        $this->info('Starting backup cleanup...');

        // Retention policy:
        // - Daily backups: Keep for 30 days
        // - Weekly backups: Keep for 90 days
        // - Monthly backups: Keep for 1 year
        // - Manual backups: Keep for 90 days

        $deletedCount = 0;

        // Clean daily backups older than 30 days
        $dailyBackups = Backup::where('description', 'like', 'Daily%')
            ->where('created_at', '<', Carbon::now()->subDays(30))
            ->where('status', 'completed')
            ->get();

        foreach ($dailyBackups as $backup) {
            $this->deleteBackup($backup);
            $deletedCount++;
        }

        // Clean weekly backups older than 90 days
        $weeklyBackups = Backup::where('description', 'like', 'Weekly%')
            ->where('created_at', '<', Carbon::now()->subDays(90))
            ->where('status', 'completed')
            ->get();

        foreach ($weeklyBackups as $backup) {
            $this->deleteBackup($backup);
            $deletedCount++;
        }

        // Clean monthly backups older than 1 year
        $monthlyBackups = Backup::where('description', 'like', 'Monthly%')
            ->where('created_at', '<', Carbon::now()->subYear())
            ->where('status', 'completed')
            ->get();

        foreach ($monthlyBackups as $backup) {
            $this->deleteBackup($backup);
            $deletedCount++;
        }

        // Clean failed backups older than 7 days
        $failedBackups = Backup::where('status', 'failed')
            ->where('created_at', '<', Carbon::now()->subDays(7))
            ->get();

        foreach ($failedBackups as $backup) {
            $backup->delete();
            $deletedCount++;
        }

        $this->info("Cleaned up {$deletedCount} old backup(s).");

        // Log cleanup activity
        AuditLogService::log(
            'backup.cleanup',
            null,
            null,
            ['deleted_count' => $deletedCount],
            'info',
            "Backup cleanup completed: {$deletedCount} backup(s) removed"
        );

        return 0;
    }

    protected function deleteBackup(Backup $backup)
    {
        // Delete file from storage
        if ($backup->file_path && Storage::exists($backup->file_path)) {
            Storage::delete($backup->file_path);
        }

        // Delete database record
        $backup->delete();

        $this->line("Deleted backup: {$backup->description} (ID: {$backup->id})");
    }
}