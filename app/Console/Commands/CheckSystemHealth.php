<?php

namespace App\Console\Commands;

use App\Services\SystemHealthService;
use App\Services\NotificationService;
use App\Models\AdminNotification;
use Illuminate\Console\Command;

class CheckSystemHealth extends Command
{
    protected $signature = 'system:health-check';
    protected $description = 'Perform system health check and notify on critical issues';

    public function handle()
    {
        $this->info('Performing system health check...');

        $health = SystemHealthService::getHealthMetrics();

        // Display overall status
        $this->newLine();
        $this->info("Overall Status: {$health['overall_status']['status']}");
        $this->line($health['overall_status']['message']);

        // Check each component
        $criticalIssues = [];
        $warnings = [];

        foreach (['database', 'storage', 'queue', 'blockchain', 'backups'] as $component) {
            if (isset($health[$component])) {
                $status = $health[$component]['status'];
                $name = $health[$component]['name'];
                $message = $health[$component]['message'];

                $icon = match($status) {
                    'healthy' => '✓',
                    'warning' => '⚠',
                    'critical' => '✗',
                    default => '?',
                };

                $this->line("{$icon} {$name}: {$message}");

                if ($status === 'critical') {
                    $criticalIssues[] = "{$name}: {$message}";
                } elseif ($status === 'warning') {
                    $warnings[] = "{$name}: {$message}";
                }
            }
        }

        // Send notifications for critical issues
        if (!empty($criticalIssues)) {
            $this->error("\nCritical Issues Detected:");
            foreach ($criticalIssues as $issue) {
                $this->error("  - {$issue}");
            }

            // Create notification
            NotificationService::create(
                AdminNotification::TYPE_SYSTEM_ERROR,
                AdminNotification::SEVERITY_CRITICAL,
                'System Health Check Failed',
                count($criticalIssues) . ' critical issue(s) detected: ' . implode(', ', $criticalIssues),
                null,
                ['issues' => $criticalIssues]
            );
        }

        // Display warnings
        if (!empty($warnings)) {
            $this->warn("\nWarnings:");
            foreach ($warnings as $warning) {
                $this->warn("  - {$warning}");
            }
        }

        if (empty($criticalIssues) && empty($warnings)) {
            $this->info("\n✓ All systems operational!");
        }

        return empty($criticalIssues) ? 0 : 1;
    }
}