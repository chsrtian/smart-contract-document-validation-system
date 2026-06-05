<?php

namespace App\Services;

use App\Models\Scan;
use App\Models\User;
use App\Models\CorrectionRequest;
use Illuminate\Support\Facades\Cache;

class AdminDashboardService
{
    /**
     * Get comprehensive overview statistics for admin dashboard
     * Results are cached for 10 minutes
     */
    public static function getOverviewStats(): array
    {
        return Cache::remember('admin_dashboard_stats', 600, function () {
            return [
                'users' => [
                    'total' => User::count(),
                    'active' => User::where('status', 'active')->count(),
                    'inactive' => User::where('status', 'inactive')->count(),
                    'by_role' => [
                        'staff' => User::role('staff')->count(),
                        'supervisor' => User::role('supervisor')->count(),
                        'admin' => User::role('admin')->count(),
                    ],
                ],
                'documents' => [
                    'total' => Scan::count(),
                    'today' => Scan::whereDate('created_at', today())->count(),
                    'locked_count' => Scan::where('locked', true)->count(),
                    'archived_count' => Scan::where('archived', true)->count(),
                    'flagged_count' => Scan::where('flagged', true)->count(),
                    'by_status' => [
                        'draft' => Scan::where('verification_status', 'draft')->count(),
                        'pending' => Scan::where('verification_status', 'pending')->count(),
                        'completed' => Scan::where('verification_status', 'completed')->count(),
                        'rejected' => Scan::where('verification_status', 'rejected')->count(),
                    ],
                    'by_type' => [
                        'birth_certificate' => Scan::where('document_type', 'birth_certificate')->count(),
                        'death_certificate' => Scan::where('document_type', 'death_certificate')->count(),
                        'marriage_certificate' => Scan::where('document_type', 'marriage_certificate')->count(),
                        'cenomar' => Scan::where('document_type', 'cenomar')->count(),
                    ],
                ],
                'corrections' => [
                    'total' => CorrectionRequest::count(),
                    'pending' => CorrectionRequest::where('status', CorrectionRequest::STATUS_PENDING)->count(),
                    'approved' => CorrectionRequest::where('status', CorrectionRequest::STATUS_APPROVED)->count(),
                    'rejected' => CorrectionRequest::where('status', CorrectionRequest::STATUS_REJECTED)->count(),
                    'escalated' => CorrectionRequest::whereNotNull('escalated_at')->count(),
                    'overridden' => CorrectionRequest::whereNotNull('override_by')->count(),
                ],
                'blockchain' => [
                    'pending' => Scan::where('blockchain_status', 'pending')->count(),
                    'confirmed' => Scan::where('blockchain_status', 'confirmed')->count(),
                    'failed' => Scan::where('blockchain_status', 'failed')->count(),
                ],
            ];
        });
    }

    /**
     * Clear cached dashboard statistics
     */
    public static function clearCache(): void
    {
        Cache::forget('admin_dashboard_stats');
    }
}