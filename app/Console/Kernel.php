<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
    Commands\FixDocumentStatuses::class,
    Commands\EscalatePendingCorrections::class,
    Commands\CreateScheduledBackup::class,
    Commands\CleanupOldBackups::class,
    Commands\RetryFailedBlockchainTransactions::class,
    Commands\GenerateMonthlyReport::class,
    Commands\CheckSystemHealth::class, 
];

protected function schedule(Schedule $schedule)
{
    // Escalate pending corrections hourly
    $schedule->command('corrections:escalate')->hourly();
    
    // Daily backup at 2:00 AM
    $schedule->command('backup:scheduled daily')
        ->dailyAt('02:00')
        ->appendOutputTo(storage_path('logs/backup-daily.log'));
    
    // Weekly backup every Sunday at 3:00 AM
    $schedule->command('backup:scheduled weekly')
        ->weeklyOn(0, '03:00')
        ->appendOutputTo(storage_path('logs/backup-weekly.log'));
    
    // Monthly backup on the 1st at 4:00 AM
    $schedule->command('backup:scheduled monthly')
        ->monthlyOn(1, '04:00')
        ->appendOutputTo(storage_path('logs/backup-monthly.log'));
    
    // Clean old backups daily
    $schedule->command('backup:cleanup')->daily();
    
    // Auto-retry failed blockchain transactions every 6 hours
    $schedule->command('blockchain:retry-failed --limit=20')
        ->everySixHours()
        ->appendOutputTo(storage_path('logs/blockchain-retry.log'));
    
    // Generate monthly report on the 1st at 6:00 AM
    $schedule->command('report:monthly --email')
        ->monthlyOn(1, '06:00')
        ->appendOutputTo(storage_path('logs/monthly-report.log'));
    
    // NEW: System health check every 15 minutes
    $schedule->command('system:health-check')
        ->everyFifteenMinutes()
        ->appendOutputTo(storage_path('logs/health-check.log'));
}

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}