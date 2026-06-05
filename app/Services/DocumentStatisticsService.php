<?php

namespace App\Services;

use App\Models\Scan;
use Illuminate\Support\Facades\Cache;

class DocumentStatisticsService
{
    /**
     * Get comprehensive document statistics
     * Results are cached for 10 minutes
     */
    public static function getStats(): array
    {
        return Cache::remember('document_statistics', 600, function () {
            return [
                'total' => Scan::count(),
                'today' => Scan::whereDate('created_at', today())->count(),
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
                    'other' => Scan::whereNotIn('document_type', [
                        'birth_certificate', 'death_certificate', 'marriage_certificate', 'cenomar'
                    ])->count(),
                ],
                'by_blockchain_status' => [
                    'pending' => Scan::where('blockchain_status', 'pending')->count(),
                    'confirmed' => Scan::where('blockchain_status', 'confirmed')->count(),
                    'failed' => Scan::where('blockchain_status', 'failed')->count(),
                    'not_submitted' => Scan::whereNull('blockchain_status')
                        ->orWhere('blockchain_status', '')->count(),
                ],
                'flagged_count' => Scan::where('flagged', true)->count(), 
            ];
        });
    }

    /**
     * Clear cached statistics
     */
    public static function clearCache(): void
    {
        Cache::forget('document_statistics');
    }
}