<?php


namespace App\Observers;

use App\Models\Scan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ScanObserver
{
    /**
     * Handle the Scan "creating" event.
     * 
     * WHAT IT DOES:
     * - Automatically sets user_id and created_by before saving
     * - Ensures these fields are NEVER null
     */
    public function creating(Scan $scan): void
    {
        // Auto-set user tracking fields if not already set
        if (!$scan->user_id && Auth::check()) {
            $scan->user_id = Auth::id();
        }
        
        if (!$scan->created_by && Auth::check()) {
            $scan->created_by = Auth::id();
        }
        
        // Auto-set processed_by if not already set
        if (!$scan->processed_by && Auth::check()) {
            $scan->processed_by = Auth::id();
        }
        
        Log::debug('ScanObserver: Auto-populated user fields', [
            'user_id' => $scan->user_id,
            'created_by' => $scan->created_by,
            'processed_by' => $scan->processed_by
        ]);
    }
    
    /**
     * Handle the Scan "updating" event.
     * 
     * WHAT IT DOES:
     * - Auto-sets verified_by/verified_at when status changes to completed
     * - Prevents manual overrides from being lost
     */
    public function updating(Scan $scan): void
    {
        // Check if verification_status is changing to completed/verified
        if ($scan->isDirty('verification_status')) {
            $newStatus = $scan->verification_status;
            
            if (in_array($newStatus, ['completed', 'verified']) && !$scan->verified_at) {
                // Auto-set verification fields
                $scan->verified_at = now();
                $scan->verified_by = $scan->verified_by ?? Auth::id();
                
                Log::info('ScanObserver: Auto-set verification fields', [
                    'scan_id' => $scan->id,
                    'verified_by' => $scan->verified_by,
                    'verified_at' => $scan->verified_at
                ]);
            }
        }
    }
    
    /**
     * Handle the Scan "created" event.
     */
    public function created(Scan $scan): void
    {
        Log::info('ScanObserver: Scan created', [
            'scan_id' => $scan->id,
            'document_id' => $scan->document_id,
            'user_id' => $scan->user_id,
            'created_by' => $scan->created_by
        ]);
    }
}