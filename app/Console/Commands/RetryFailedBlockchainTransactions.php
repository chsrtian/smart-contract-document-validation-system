<?php

namespace App\Console\Commands;

use App\Models\Scan;
use App\Jobs\AnchorToBlockchainJob;
use App\Services\AuditLogService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class RetryFailedBlockchainTransactions extends Command
{
    protected $signature = 'blockchain:retry-failed {--limit=10 : Maximum number of transactions to retry}';
    protected $description = 'Automatically retry failed blockchain transactions';

    public function handle()
    {
        $limit = $this->option('limit');
        
        $this->info("Searching for failed blockchain transactions...");

        // Get failed transactions older than 1 hour (avoid retrying too quickly)
        $failedDocuments = Scan::where('blockchain_status', 'failed')
            ->where('updated_at', '<', Carbon::now()->subHour())
            ->limit($limit)
            ->get();

        if ($failedDocuments->isEmpty()) {
            $this->info('No failed transactions to retry.');
            return 0;
        }

        $retryCount = 0;

        foreach ($failedDocuments as $document) {
            $document->update(['blockchain_status' => 'pending']);
            AnchorToBlockchainJob::dispatch($document);
            $retryCount++;
            
            $this->line("Retrying document ID: {$document->id}");
        }

        // Log automatic retry
        AuditLogService::log(
            'blockchain.auto_retry',
            null,
            null,
            ['count' => $retryCount],
            'info',
            "Automated retry initiated for {$retryCount} failed blockchain transaction(s)"
        );

        $this->info("Successfully queued {$retryCount} transaction(s) for retry.");
        
        return 0;
    }
}