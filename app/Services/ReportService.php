<?php

namespace App\Services;

use App\Models\Scan;
use App\Models\User;
use App\Models\CorrectionRequest;
use App\Models\AuditLog;
use App\Models\Backup;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Generate comprehensive monthly report data
     */
    public static function generateMonthlyReport($month = null, $year = null): array
    {
        $month = $month ?? now()->subMonth()->month;
        $year = $year ?? now()->subMonth()->year;
        
        $startDate = Carbon::create($year, $month, 1)->startOfDay();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

        return [
            'period' => [
                'month' => $startDate->format('F'),
                'year' => $year,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'documents' => self::getDocumentStats($startDate, $endDate),
            'corrections' => self::getCorrectionStats($startDate, $endDate),
            'users' => self::getUserStats($startDate, $endDate),
            'blockchain' => self::getBlockchainStats($startDate, $endDate),
            'system' => self::getSystemStats($startDate, $endDate),
            'performance' => self::getPerformanceMetrics($startDate, $endDate),
            'top_performers' => self::getTopPerformers($startDate, $endDate),
        ];
    }

    protected static function getDocumentStats($startDate, $endDate): array
    {
        $total = Scan::whereBetween('created_at', [$startDate, $endDate])->count();
        
        return [
            'total_uploaded' => $total,
            'by_type' => [
                'birth_certificate' => Scan::whereBetween('created_at', [$startDate, $endDate])
                    ->where('document_type', 'birth_certificate')->count(),
                'death_certificate' => Scan::whereBetween('created_at', [$startDate, $endDate])
                    ->where('document_type', 'death_certificate')->count(),
                'marriage_certificate' => Scan::whereBetween('created_at', [$startDate, $endDate])
                    ->where('document_type', 'marriage_certificate')->count(),
                'cenomar' => Scan::whereBetween('created_at', [$startDate, $endDate])
                    ->where('document_type', 'cenomar')->count(),
            ],
            'by_status' => [
                'draft' => Scan::whereBetween('created_at', [$startDate, $endDate])
                    ->where('verification_status', 'draft')->count(),
                'pending' => Scan::whereBetween('created_at', [$startDate, $endDate])
                    ->where('verification_status', 'pending')->count(),
                'completed' => Scan::whereBetween('created_at', [$startDate, $endDate])
                    ->where('verification_status', 'completed')->count(),
                'rejected' => Scan::whereBetween('created_at', [$startDate, $endDate])
                    ->where('verification_status', 'rejected')->count(),
            ],
            'interventions' => [
                'locked' => Scan::whereBetween('locked_at', [$startDate, $endDate])->count(),
                'archived' => Scan::whereBetween('archived_at', [$startDate, $endDate])->count(),
                'flagged' => Scan::whereBetween('flagged_at', [$startDate, $endDate])->count(),
            ],
            'daily_average' => round($total / $startDate->daysInMonth, 2),
        ];
    }

    protected static function getCorrectionStats($startDate, $endDate): array
    {
        return [
            'total_requests' => CorrectionRequest::whereBetween('requested_at', [$startDate, $endDate])->count(),
            'approved' => CorrectionRequest::whereBetween('requested_at', [$startDate, $endDate])
                ->where('status', CorrectionRequest::STATUS_APPROVED)->count(),
            'rejected' => CorrectionRequest::whereBetween('requested_at', [$startDate, $endDate])
                ->where('status', CorrectionRequest::STATUS_REJECTED)->count(),
            'pending' => CorrectionRequest::whereBetween('requested_at', [$startDate, $endDate])
                ->where('status', CorrectionRequest::STATUS_PENDING)->count(),
            'escalated' => CorrectionRequest::whereBetween('escalated_at', [$startDate, $endDate])->count(),
            'overridden' => CorrectionRequest::whereBetween('override_at', [$startDate, $endDate])->count(),
            'avg_resolution_time' => self::getAvgResolutionTime($startDate, $endDate),
        ];
    }

    protected static function getUserStats($startDate, $endDate): array
    {
        return [
            'new_users' => User::whereBetween('created_at', [$startDate, $endDate])->count(),
            'active_users' => User::where('status', 'active')->count(), // Total active users (not by period)
            'deactivated' => User::where('status', 'inactive')
                ->whereBetween('updated_at', [$startDate, $endDate])->count(),
            'by_role' => [
                'staff' => User::role('staff')->whereBetween('created_at', [$startDate, $endDate])->count(),
                'supervisor' => User::role('supervisor')->whereBetween('created_at', [$startDate, $endDate])->count(),
                'admin' => User::role('admin')->whereBetween('created_at', [$startDate, $endDate])->count(),
            ],
        ];
    }

    protected static function getBlockchainStats($startDate, $endDate): array
    {
        return [
            'total_transactions' => Scan::whereBetween('created_at', [$startDate, $endDate])
                ->whereNotNull('blockchain_status')->count(),
            'confirmed' => Scan::whereBetween('created_at', [$startDate, $endDate])
                ->where('blockchain_status', 'confirmed')->count(),
            'failed' => Scan::whereBetween('created_at', [$startDate, $endDate])
                ->where('blockchain_status', 'failed')->count(),
            'pending' => Scan::whereBetween('created_at', [$startDate, $endDate])
                ->where('blockchain_status', 'pending')->count(),
            'success_rate' => self::getBlockchainSuccessRate($startDate, $endDate),
        ];
    }

    protected static function getSystemStats($startDate, $endDate): array
    {
        return [
            'backups_created' => Backup::whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'completed')->count(),
            'backup_failures' => Backup::whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'failed')->count(),
            'audit_logs' => AuditLog::whereBetween('created_at', [$startDate, $endDate])->count(),
            'critical_events' => AuditLog::whereBetween('created_at', [$startDate, $endDate])
                ->where('severity', 'critical')->count(),
            'warnings' => AuditLog::whereBetween('created_at', [$startDate, $endDate])
                ->where('severity', 'warning')->count(),
        ];
    }

    protected static function getPerformanceMetrics($startDate, $endDate): array
    {
        $totalDocuments = Scan::whereBetween('created_at', [$startDate, $endDate])->count();
        $completedDocuments = Scan::whereBetween('created_at', [$startDate, $endDate])
            ->where('verification_status', 'completed')->count();

        return [
            'document_completion_rate' => $totalDocuments > 0 
                ? round(($completedDocuments / $totalDocuments) * 100, 2) 
                : 0,
            'avg_documents_per_day' => round($totalDocuments / $startDate->daysInMonth, 2),
            'peak_upload_day' => self::getPeakUploadDay($startDate, $endDate),
            'system_uptime' => '99.9%', // TODO: Implement actual uptime tracking
        ];
    }

    protected static function getTopPerformers($startDate, $endDate): array
    {
        $topUploaders = Scan::select('user_id', DB::raw('count(*) as upload_count'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderByDesc('upload_count')
            ->limit(5)
            ->with('user')
            ->get()
            ->map(function ($scan) {
                return [
                    'user' => $scan->user?->name ?? 'Unknown',
                    'uploads' => $scan->upload_count,
                ];
            });

        return [
            'top_uploaders' => $topUploaders->toArray(),
        ];
    }

    protected static function getAvgResolutionTime($startDate, $endDate): string
    {
        $corrections = CorrectionRequest::whereBetween('requested_at', [$startDate, $endDate])
            ->whereIn('status', [CorrectionRequest::STATUS_APPROVED, CorrectionRequest::STATUS_REJECTED])
            ->get();

        if ($corrections->isEmpty()) {
            return 'N/A';
        }

        $totalMinutes = 0;
        $count = 0;

        foreach ($corrections as $correction) {
            if ($correction->requested_at && $correction->reviewed_at) {
                $totalMinutes += $correction->requested_at->diffInMinutes($correction->reviewed_at);
                $count++;
            }
        }

        if ($count === 0) {
            return 'N/A';
        }

        $avgMinutes = $totalMinutes / $count;
        $hours = floor($avgMinutes / 60);
        $minutes = $avgMinutes % 60;

        return "{$hours}h {$minutes}m";
    }

    protected static function getBlockchainSuccessRate($startDate, $endDate): float
    {
        $total = Scan::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('blockchain_status')
            ->whereIn('blockchain_status', ['confirmed', 'failed'])
            ->count();

        if ($total === 0) {
            return 0;
        }

        $confirmed = Scan::whereBetween('created_at', [$startDate, $endDate])
            ->where('blockchain_status', 'confirmed')
            ->count();

        return round(($confirmed / $total) * 100, 2);
    }

    protected static function getPeakUploadDay($startDate, $endDate): string
    {
        $peakDay = Scan::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderByDesc('count')
            ->first();

        return $peakDay ? Carbon::parse($peakDay->date)->format('M d, Y') : 'N/A';
    }
}