<?php

namespace App\Console\Commands;

use App\Models\Scan;
use Illuminate\Console\Command;

class BackfillScanUserFields extends Command
{
    protected $signature = 'scans:backfill-user-fields';
    protected $description = 'Backfill null user tracking fields in scans table';

    public function handle()
    {
        $this->info('Starting backfill of null user fields...');
        
        // Find scans with null user fields
        $scans = Scan::whereNull('user_id')
            ->orWhereNull('created_by')
            ->orWhereNull('verified_by')
            ->orWhereNull('reviewed_by')
            ->get();
        
        $this->info("Found {$scans->count()} scans with null fields");
        
        $progressBar = $this->output->createProgressBar($scans->count());
        $progressBar->start();
        
        foreach ($scans as $scan) {
            $updates = [];
            
            // Use processed_by as fallback for all user fields
            $fallbackUserId = $scan->processed_by ?? 1; // System user ID
            
            if (!$scan->user_id) {
                $updates['user_id'] = $fallbackUserId;
            }
            
            if (!$scan->created_by) {
                $updates['created_by'] = $fallbackUserId;
            }
            
            // If verification is completed but verified_by is null
            if (in_array($scan->verification_status, ['completed', 'verified'])) {
                if (!$scan->verified_by) {
                    $updates['verified_by'] = $fallbackUserId;
                }
                if (!$scan->verified_at) {
                    $updates['verified_at'] = $scan->blockchain_confirmed_at ?? now();
                }
                if (!$scan->reviewed_by) {
                    $updates['reviewed_by'] = $fallbackUserId;
                }
                if (!$scan->reviewed_at) {
                    $updates['reviewed_at'] = $scan->blockchain_confirmed_at ?? now();
                }
            }
            
            if (!empty($updates)) {
                $scan->update($updates);
                $this->line("\n✅ Updated scan #{$scan->id}: " . json_encode($updates));
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine(2);
        $this->info('✅ Backfill completed successfully!');
        
        return Command::SUCCESS;
    }
}