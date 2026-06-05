<?php

namespace App\Jobs;

use App\Models\Scan;
use App\Services\GanacheBlockchainService; 
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class AnchorToBlockchainJob implements ShouldQueue
{
    use Queueable;

    public $scan;
    public $tries = 3; 
    public $timeout = 300;
    public $maxExceptions = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(Scan $scan)
    {
        $this->scan = $scan;
    }

    /**
     * Execute the job.
     * 
     * UPDATED: Use real Ganache blockchain instead of mock
     * 
     * WHAT IT DOES:
     * 1. Validate scan is ready for blockchain
     * 2. Send transaction to Ganache
     * 3. Wait for confirmation
     * 4. Update database with ALL Ganache transaction details
     */
    public function handle(): void
    {
        try {
            $startTime = microtime(true); // ADDED: Track execution time
            
            Log::info('AnchorToBlockchainJob started', [
                'scan_id' => $this->scan->id,
                'document_id' => $this->scan->document_id,
                'verification_status' => $this->scan->verification_status,
                'validation_score' => $this->scan->validation_score,
                'attempt' => $this->attempts(),
                'job_timeout' => $this->timeout,
                'max_tries' => $this->tries
            ]);
            
            // Validate eligibility
            if (!$this->scan->isReadyForBlockchainAnchoring()) {
                Log::warning('Scan not ready for blockchain anchoring', [
                    'scan_id' => $this->scan->id,
                    'validation_score' => $this->scan->validation_score,
                    'blockchain_eligible' => $this->scan->blockchain_eligible,
                    'verification_status' => $this->scan->verification_status,
                    'blockchain_status' => $this->scan->blockchain_status
                ]);
                
                return;
            }
            
            // Update status to pending
            $this->scan->update([
                'blockchain_status' => 'pending',
                'blockchain_submitted_at' => now()
            ]);
            
            // IMPROVED: Initialize service with detailed error context
            try {
                Log::info('Initializing Ganache service', [
                    'scan_id' => $this->scan->id,
                    'ganache_url' => env('GANACHE_URL'),
                    'from_address' => env('GANACHE_FROM_ADDRESS'),
                    'network_id' => env('GANACHE_NETWORK_ID')
                ]);
                
                $ganacheService = app(GanacheBlockchainService::class);
                
                $initTime = microtime(true) - $startTime;
                Log::info('Ganache service initialized', [
                    'scan_id' => $this->scan->id,
                    'init_time_ms' => round($initTime * 1000, 2)
                ]);
                
            } catch (\Exception $e) {
                Log::error('Failed to initialize Ganache service', [
                    'scan_id' => $this->scan->id,
                    'error' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                    'env_ganache_url' => env('GANACHE_URL'),
                    'env_from_address' => env('GANACHE_FROM_ADDRESS'),
                    'config_ganache_url' => config('blockchain.ganache.url'),
                    'config_account_address' => config('blockchain.account.address'),
                    'trace' => $e->getTraceAsString()
                ]);
                
                throw new \Exception('Ganache service initialization failed: ' . $e->getMessage());
            }
            
            // Anchor document
            $anchorStartTime = microtime(true);
            $result = $ganacheService->anchorDocument($this->scan);
            $anchorTime = microtime(true) - $anchorStartTime;
            
            if ($result['success']) {
                Log::info('Blockchain anchoring successful', [
                    'scan_id' => $this->scan->id,
                    'tx_hash' => $result['transaction_hash'],
                    'block_number' => $result['block_number'],
                    'gas_used' => $result['gas_used'],
                    'anchor_time_ms' => round($anchorTime * 1000, 2),
                    'total_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
                ]);
                
                // Auto-update verification status
                $this->scan->refresh();
                
                $updates = ['blockchain_eligible' => true];
        
                if ($this->scan->verification_status === 'pending' && $this->scan->validation_score >= 85) {
                    $updates['verification_status'] = 'completed';  
                    $updates['verified_at'] = now();
                    $updates['verified_by'] = $this->scan->processed_by ?? 1; 
                    $updates['reviewed_at'] = now();
                    $updates['reviewed_by'] = $this->scan->processed_by ?? 1;
                   
                    if (!$this->scan->user_id && $this->scan->processed_by) {
                        $updates['user_id'] = $this->scan->processed_by;
                    }
                    if (!$this->scan->created_by && $this->scan->processed_by) {
                        $updates['created_by'] = $this->scan->processed_by;
                    }
                            
                    Log::info('Auto-updating verification status to completed', [
                        'scan_id' => $this->scan->id,
                        'updates' => $updates
                    ]);
                }
                        
                $this->scan->update($updates);
                $this->scan->refresh();
                        
                Log::info('Document status updated after blockchain success', [
                    'scan_id' => $this->scan->id,
                    'verification_status' => $this->scan->verification_status,
                    'blockchain_status' => $this->scan->blockchain_status,
                    'blockchain_tx_hash' => $this->scan->blockchain_tx_hash
                ]);
                        
            } else {
                throw new \Exception($result['message'] ?? 'Blockchain anchoring failed');
            }
            
        } catch (\Exception $e) {
            Log::error('AnchorToBlockchainJob exception', [
                'scan_id' => $this->scan->id,
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'trace' => $e->getTraceAsString(),
                'attempt' => $this->attempts(),
                'execution_time_ms' => round((microtime(true) - ($startTime ?? microtime(true))) * 1000, 2)
            ]);
            
            $this->scan->update([
                'blockchain_status' => 'failed',
                'blockchain_metadata' => json_encode([
                    'error' => $e->getMessage(),
                    'error_class' => get_class($e),
                    'failed_at' => now()->toIso8601String(),
                    'attempt' => $this->attempts()
                ])
            ]);
            
            throw $e;
        }
    }


    /**
     * Handle job failure after all retries exhausted
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('AnchorToBlockchainJob failed permanently', [
            'scan_id' => $this->scan->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
        
        $this->scan->update([
            'blockchain_status' => 'failed',
            'blockchain_metadata' => json_encode([
                'error' => $exception->getMessage(),
                'failed_permanently_at' => now()->toIso8601String(),
                'total_attempts' => $this->attempts()
            ])
        ]);
    }
}