<?php

namespace App\Services;

use App\Models\Scan;
use App\Models\User;
use App\Models\CorrectionRequest;
use App\Models\Backup;
use App\Models\AdminNotification;
use Illuminate\Support\Facades\DB;

class SystemHealthService
{
    /**
     * Get comprehensive system health metrics
     */
    public static function getHealthMetrics(): array
    {
        return [
            'overall_status' => self::getOverallStatus(),
            'database' => self::checkDatabase(),
            'storage' => self::checkStorage(),
            'queue' => self::checkQueue(),
            'blockchain' => self::checkBlockchain(),
            'backups' => self::checkBackups(),
            'performance' => self::getPerformanceMetrics(),
            'alerts' => self::getActiveAlerts(),
            'last_checked' => now()->toDateTimeString(),
        ];
    }

    /**
     * Get overall system status
     */
    protected static function getOverallStatus(): array
    {
        $checks = [
            self::checkDatabase(),
            self::checkStorage(),
            self::checkQueue(),
            self::checkBlockchain(),
            self::checkBackups(),
        ];

        $criticalIssues = collect($checks)->where('status', 'critical')->count();
        $warnings = collect($checks)->where('status', 'warning')->count();

        if ($criticalIssues > 0) {
            $status = 'critical';
            $message = "{$criticalIssues} critical issue(s) detected";
        } elseif ($warnings > 0) {
            $status = 'warning';
            $message = "{$warnings} warning(s) detected";
        } else {
            $status = 'healthy';
            $message = 'All systems operational';
        }

        return [
            'status' => $status,
            'message' => $message,
            'uptime' => self::getUptime(),
            'last_incident' => self::getLastIncident(),
        ];
    }

    /**
     * Check database health
     */
    protected static function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $responseTime = round((microtime(true) - $start) * 1000, 2);

            $status = 'healthy';
            $message = 'Database connection active';

            if ($responseTime > 1000) {
                $status = 'warning';
                $message = 'Slow database response';
            }

            return [
                'name' => 'Database',
                'status' => $status,
                'message' => $message,
                'metric_label' => 'Response Time',
                'metric_value' => $responseTime . ' ms',
                'response_time' => $responseTime . 'ms',
                'details' => [
                    'connection' => config('database.default'),
                    'total_tables' => self::getTableCount(),
                    'total_records' => [
                        'users' => User::count(),
                        'documents' => Scan::count(),
                        'corrections' => CorrectionRequest::count(),
                    ],
                ],
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'Database',
                'status' => 'critical',
                'message' => 'Database connection failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check storage health
     */
    protected static function checkStorage(): array
    {
        try {
            $totalSpace = disk_total_space(storage_path());
            $freeSpace = disk_free_space(storage_path());
            $usedSpace = $totalSpace - $freeSpace;
            $usedPercentage = round(($usedSpace / $totalSpace) * 100, 2);

            $status = 'healthy';
            $message = 'Storage healthy';

            if ($usedPercentage > 90) {
                $status = 'critical';
                $message = 'Storage critically low';
            } elseif ($usedPercentage > 75) {
                $status = 'warning';
                $message = 'Storage usage high';
            }

            return [
                'name' => 'Storage',
                'status' => $status,
                'message' => $message,
                'metric_label' => 'Used Capacity',
                'metric_value' => $usedPercentage . '%',
                'details' => [
                    'total' => self::formatBytes($totalSpace),
                    'used' => self::formatBytes($usedSpace),
                    'free' => self::formatBytes($freeSpace),
                    'used_percentage' => $usedPercentage,
                    'used_percentage_text' => $usedPercentage . '%',
                    'documents_storage' => self::getDocumentsStorageSize(),
                    'backups_storage' => self::getBackupsStorageSize(),
                ],
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'Storage',
                'status' => 'warning',
                'message' => 'Unable to check storage',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check queue health
     */
    protected static function checkQueue(): array
    {
        try {
            // Check failed jobs
            $failedJobs = DB::table('failed_jobs')->count();

            $status = 'healthy';
            $message = 'Queue system operational';

            if ($failedJobs > 50) {
                $status = 'critical';
                $message = 'High number of failed jobs';
            } elseif ($failedJobs > 10) {
                $status = 'warning';
                $message = 'Some jobs have failed';
            }

            return [
                'name' => 'Queue',
                'status' => $status,
                'message' => $message,
                'metric_label' => 'Failed Jobs',
                'metric_value' => number_format($failedJobs),
                'details' => [
                    'failed_jobs' => $failedJobs,
                    'jobs_today' => DB::table('jobs')->whereDate('created_at', today())->count(),
                ],
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'Queue',
                'status' => 'warning',
                'message' => 'Unable to check queue status',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check blockchain health
     */
    protected static function checkBlockchain(): array
    {
        $total = Scan::whereNotNull('blockchain_status')->count();
        $failed = Scan::where('blockchain_status', 'failed')->count();
        $pending = Scan::where('blockchain_status', 'pending')->count();
        $confirmed = Scan::where('blockchain_status', 'confirmed')->count();

        $failureRate = $total > 0 ? round(($failed / $total) * 100, 2) : 0;

        $status = 'healthy';
        $message = 'Blockchain operations stable';

        if ($total === 0) {
            $message = 'No blockchain activity yet';
        }

        // Never report healthy when failed transactions exist.
        if ($failed > 0) {
            $status = 'warning';
            $message = $failed === 1
                ? '1 failed blockchain transaction detected'
                : "{$failed} failed blockchain transactions detected";
        }

        if ($pending > 50 && $status !== 'critical') {
            $status = 'warning';
            $message = 'Blockchain pending queue is growing';
        }

        if ($failureRate > 10 || $failed >= 10) {
            $status = 'critical';
            $message = 'High blockchain failure rate';
        }

        return [
            'name' => 'Blockchain',
            'status' => $status,
            'message' => $message,
            'metric_label' => 'Failed Transactions',
            'metric_value' => number_format($failed),
            'details' => [
                'total_transactions' => $total,
                'confirmed' => $confirmed,
                'pending' => $pending,
                'failed' => $failed,
                'failure_rate' => $failureRate . '%',
            ],
        ];
    }

    /**
     * Check backups health
     */
    protected static function checkBackups(): array
    {
        $lastBackup = Backup::where('status', 'completed')
            ->latest('completed_at')
            ->first();

        $recentFailed = Backup::where('status', 'failed')
            ->where('created_at', '>', now()->subDays(7))
            ->count();

        $status = 'healthy';
        $message = 'Backup system operational';

        if (!$lastBackup || $lastBackup->completed_at < now()->subDays(2)) {
            $status = 'critical';
            $message = 'No recent successful backup';
        } elseif ($recentFailed > 3) {
            $status = 'warning';
            $message = 'Multiple backup failures detected';
        }

        return [
            'name' => 'Backups',
            'status' => $status,
            'message' => $message,
            'metric_label' => 'Last Successful Backup',
            'metric_value' => $lastBackup ? $lastBackup->completed_at->diffForHumans() : 'Never',
            'details' => [
                'last_successful' => $lastBackup ? $lastBackup->completed_at->diffForHumans() : 'Never',
                'total_backups' => Backup::where('status', 'completed')->count(),
                'failed_this_week' => $recentFailed,
                'total_backup_size' => self::getTotalBackupSize(),
            ],
        ];
    }

    /**
     * Get performance metrics
     */
    protected static function getPerformanceMetrics(): array
    {
        return [
            'documents_today' => Scan::whereDate('created_at', today())->count(),
            'corrections_pending' => CorrectionRequest::where('status', CorrectionRequest::STATUS_PENDING)->count(),
            'avg_document_processing_time' => self::getAvgProcessingTime(),
            'active_users_today' => self::getActiveUsersToday(),
            'system_load' => self::getSystemLoad(),
        ];
    }

    /**
     * Get active alerts
     */
    protected static function getActiveAlerts(): array
    {
        $criticalNotifications = self::deduplicateAlerts(AdminNotification::unread()
            ->severity(AdminNotification::SEVERITY_CRITICAL)
            ->latest()
            ->limit(25)
            ->get())
            ->take(5)
            ->values();

        $warningNotifications = self::deduplicateAlerts(AdminNotification::unread()
            ->severity(AdminNotification::SEVERITY_WARNING)
            ->latest()
            ->limit(25)
            ->get())
            ->take(5)
            ->values();

        return [
            'critical_count' => $criticalNotifications->count(),
            'warning_count' => $warningNotifications->count(),
            'critical_alerts' => $criticalNotifications->map(fn($n) => [
                'title' => $n->title,
                'message' => $n->message,
                'created_at' => $n->created_at->diffForHumans(),
            ])->values()->all(),
            'warning_alerts' => $warningNotifications->map(fn($n) => [
                'title' => $n->title,
                'message' => $n->message,
                'created_at' => $n->created_at->diffForHumans(),
            ])->values()->all(),
        ];
    }

    /**
     * Collapse duplicate alerts with matching title + message.
     */
    protected static function deduplicateAlerts($alerts)
    {
        return $alerts->unique(function ($alert) {
            $title = mb_strtolower(trim((string) $alert->title));
            $message = mb_strtolower(trim((string) $alert->message));
            return $title . '|' . $message;
        });
    }

    /**
     * Helper: Format bytes to human readable
     */
    protected static function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Get documents storage size
     */
    protected static function getDocumentsStorageSize(): string
    {
        $path = storage_path('app/documents');
        if (!file_exists($path)) {
            return '0 B';
        }
        $size = self::getDirectorySize($path);
        return self::formatBytes($size);
    }

    /**
     * Get backups storage size
     */
    protected static function getBackupsStorageSize(): string
    {
        $path = storage_path('app/backups');
        if (!file_exists($path)) {
            return '0 B';
        }
        $size = self::getDirectorySize($path);
        return self::formatBytes($size);
    }

    /**
     * Get directory size recursively
     */
    protected static function getDirectorySize($path): int
    {
        $size = 0;
        foreach (glob(rtrim($path, '/').'/*', GLOB_NOSORT) as $file) {
            $size += is_file($file) ? filesize($file) : self::getDirectorySize($file);
        }
        return $size;
    }

    /**
     * Get table count
     */
    protected static function getTableCount(): int
    {
        try {
            $tables = DB::select('SHOW TABLES');
            return count($tables);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get total backup size
     */
    protected static function getTotalBackupSize(): string
    {
        $totalSize = Backup::where('status', 'completed')
            ->sum('file_size');
        return self::formatBytes($totalSize);
    }

    /**
     * Get average processing time
     */
    protected static function getAvgProcessingTime(): string
    {
        $recentProcessed = Scan::whereNotNull('created_at')
            ->whereNotNull('processed_at')
            ->whereColumn('processed_at', '>=', 'created_at')
            ->latest('processed_at')
            ->limit(200)
            ->get(['created_at', 'processed_at']);

        if ($recentProcessed->isEmpty()) {
            return 'N/A';
        }

        $avgSeconds = (int) round($recentProcessed->avg(function ($scan) {
            return $scan->created_at->diffInSeconds($scan->processed_at);
        }));

        if ($avgSeconds < 60) {
            return $avgSeconds . ' sec';
        }

        $minutes = intdiv($avgSeconds, 60);
        $seconds = $avgSeconds % 60;

        if ($minutes >= 60) {
            $hours = intdiv($minutes, 60);
            $remainingMinutes = $minutes % 60;
            return $hours . 'h ' . $remainingMinutes . 'm';
        }

        if ($seconds === 0) {
            return $minutes . ' min';
        }

        return $minutes . 'm ' . $seconds . 's';
    }

    /**
     * Get active users today
     */
    protected static function getActiveUsersToday(): int
    {
        // Count users who uploaded documents today
        return Scan::whereDate('created_at', today())
            ->distinct('user_id')
            ->count('user_id');
    }

    /**
     * Get system uptime
     */
    protected static function getUptime(): string
    {
        // Placeholder - implement based on server start time
        return '99.9%';
    }

    /**
     * Get last incident
     */
    protected static function getLastIncident(): ?string
    {
        $lastCritical = AdminNotification::severity(AdminNotification::SEVERITY_CRITICAL)
            ->latest()
            ->first();

        return $lastCritical ? $lastCritical->created_at->diffForHumans() : 'None';
    }

    /**
     * Get system load
     */
    protected static function getSystemLoad(): array
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return [
                '1min' => round($load[0], 2),
                '5min' => round($load[1], 2),
                '15min' => round($load[2], 2),
            ];
        }
        return ['1min' => 0, '5min' => 0, '15min' => 0];
    }
}