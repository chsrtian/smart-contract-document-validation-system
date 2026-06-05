<?php

namespace App\Console\Commands;

use App\Jobs\ExecuteBackupJob;
use App\Services\AuditLogService;
use Illuminate\Console\Command;

class CreateScheduledBackup extends Command
{
    protected $signature = 'backup:scheduled {type=daily : Backup type (daily, weekly, monthly)}';
    protected $description = 'Create a scheduled automated backup';

    public function handle()
    {
        $type = $this->argument('type');
        
        if (!in_array($type, ['daily', 'weekly', 'monthly'])) {
            $this->error('Invalid backup type. Use: daily, weekly, or monthly');
            return 1;
        }

        $this->info("Starting {$type} scheduled backup...");

        // Dispatch backup job
        ExecuteBackupJob::dispatch(
            null,
            null, // System-initiated (no user)
            ucfirst($type) . ' automated backup'
        );

        // Log audit entry (system-initiated)
        AuditLogService::log(
            'backup.scheduled',
            null,
            null,
            ['type' => $type, 'scheduled_at' => now()],
            'info',
            "Scheduled {$type} backup initiated by system cron"
        );

        $this->info("Backup job dispatched successfully.");
        
        return 0;
    }
}