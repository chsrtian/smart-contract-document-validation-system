<?php

namespace App\Console\Commands;

use App\Models\Scan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixDocumentStatuses extends Command
{
    protected $signature = 'fix:document-statuses {--scan-id= : Fix specific scan ID} {--force : Force blockchain dispatch}';
    protected $description = 'Fix document statuses and trigger blockchain anchoring';

    public function handle()
    {
        $this->info('🔧 Starting document status fixes...');
        
        if ($this->option('scan-id')) {
            // Fix specific document
            $documents = Scan::where('id', $this->option('scan-id'))->get();
        } else {
            // Fix documents with 100% manual completion
            $documents = Scan::where('manual_completion_score', '>=', 100)
                            ->get();
        }
        
        $this->info("📋 Found {$documents->count()} documents to fix");
        
        foreach ($documents as $doc) {
            $this->info("🔨 Processing document ID: {$doc->id}");
            $this->info("   Current status: {$doc->verification_status}");
            $this->info("   Manual score: {$doc->manual_completion_score}%");
            $this->info("   Validation score: {$doc->validation_score}%");
            $this->info("   Blockchain status: " . ($doc->blockchain_status ?? 'null'));
            
            // Force status update
            $doc->updateValidationStatus();
            $doc->refresh();
            
            $this->info("   ✅ New status: {$doc->verification_status}");
            $this->info("   ✅ Blockchain eligible: " . ($doc->blockchain_eligible ? 'Yes' : 'No'));
            
            // If ready for blockchain and not already confirmed
            if ($doc->isReadyForBlockchainAnchoring() && 
                $doc->blockchain_status !== 'confirmed') {
                
                $doc->blockchain_status = 'pending';
                $doc->blockchain_submitted_at = now();
                $doc->save();
                
                // Clear existing jobs for this document
                DB::table('jobs')->where('payload', 'like', '%"id":' . $doc->id . '%')->delete();
                
                // Dispatch new blockchain job
                \App\Jobs\AnchorToBlockchainJob::dispatch($doc);
                
                $this->info("   🚀 Blockchain job dispatched (using default queue)");
            } else {
                $this->info("   ⏸️  Not ready for blockchain or already confirmed");
            }
            
            $this->newLine();
        }
        
        $this->info('🎉 Document status fixes completed!');
        
        // Show queue status
        $queueCount = DB::table('jobs')->where('queue', 'default')->count();
        $this->info("📊 Current jobs in default queue: {$queueCount}");
        
        return 0;
    }
}