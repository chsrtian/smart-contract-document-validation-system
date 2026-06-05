<?php

namespace App\Console\Commands;

use App\Models\CorrectionRecord;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BackfillCorrectionRecordBlockNumbers extends Command
{
    protected $signature = 'corrections:backfill-block-numbers
                            {--dry-run : Preview changes without writing updates}
                            {--chunk=100 : Number of records to process per chunk}';

    protected $description = 'Backfill correction_records blockchain metadata block_number from Ganache receipts';

    public function handle(): int
    {
        $ganacheUrl = config('blockchain.ganache.url')
            ?? config('blockchain.network.provider')
            ?? env('GANACHE_URL', 'http://127.0.0.1:7545');

        $dryRun = (bool) $this->option('dry-run');
        $chunkSize = max((int) $this->option('chunk'), 1);

        $query = CorrectionRecord::query()
            ->whereNotNull('correction_tx_hash')
            ->orderBy('id');

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('No correction records with correction_tx_hash found.');
            return Command::SUCCESS;
        }

        $this->info('Starting correction block number backfill...');
        $this->line('Ganache URL: ' . $ganacheUrl);

        if ($dryRun) {
            $this->warn('Dry-run mode enabled. No updates will be written.');
        }

        $stats = [
            'processed' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'missing_receipt' => 0,
            'marked_unverifiable' => 0,
            'errors' => 0,
        ];

        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        $query->chunkById($chunkSize, function ($records) use (&$stats, $ganacheUrl, $dryRun, $progressBar) {
            foreach ($records as $record) {
                $stats['processed']++;

                try {
                    $txHash = trim((string) $record->correction_tx_hash);
                    if ($txHash === '') {
                        $stats['missing_receipt']++;
                        continue;
                    }

                    $metadata = is_array($record->blockchain_metadata)
                        ? $record->blockchain_metadata
                        : [];

                    $currentBlockNumber = isset($metadata['block_number']) && $metadata['block_number'] !== null
                        ? (int) $metadata['block_number']
                        : null;

                    $receipt = $this->fetchTransactionReceipt($ganacheUrl, $txHash);
                    if ($receipt === null || empty($receipt['blockNumber'])) {
                        $stats['missing_receipt']++;

                        $metadataNeedsUpdate = false;

                        if (($metadata['block_number_verified'] ?? null) !== false) {
                            $metadata['block_number_verified'] = false;
                            $metadataNeedsUpdate = true;
                        }

                        if (($metadata['block_number_source'] ?? null) !== 'receipt_unavailable_current_chain') {
                            $metadata['block_number_source'] = 'receipt_unavailable_current_chain';
                            $metadataNeedsUpdate = true;
                        }

                        if (($metadata['receipt_status'] ?? null) !== 'missing') {
                            $metadata['receipt_status'] = 'missing';
                            $metadataNeedsUpdate = true;
                        }

                        if (!array_key_exists('legacy_block_number', $metadata) && isset($metadata['block_number']) && $metadata['block_number'] !== null) {
                            $metadata['legacy_block_number'] = (int) $metadata['block_number'];
                            $metadataNeedsUpdate = true;
                        }

                        if (isset($metadata['block_number']) && $metadata['block_number'] !== null) {
                            $metadata['block_number'] = null;
                            $metadataNeedsUpdate = true;
                        }

                        $metadata['receipt_checked_at'] = now()->toISOString();

                        if ($metadataNeedsUpdate) {
                            if (!$dryRun) {
                                $record->update([
                                    'blockchain_metadata' => $metadata,
                                ]);
                            }

                            $stats['updated']++;
                            $stats['marked_unverifiable']++;
                            $this->newLine();
                            $this->line(sprintf(
                                '%s correction_record #%d (%s): marked block_number as unavailable on current chain (legacy value: %s)',
                                $dryRun ? '[DRY-RUN] Would update' : 'Updated',
                                $record->id,
                                $this->shortHash($txHash),
                                $currentBlockNumber === null ? 'null' : (string) $currentBlockNumber
                            ));
                        } else {
                            $stats['unchanged']++;
                        }

                        continue;
                    }

                    $resolvedBlockNumber = hexdec($receipt['blockNumber']);

                    if (
                        $currentBlockNumber === $resolvedBlockNumber
                        && ($metadata['block_number_verified'] ?? null) === true
                        && ($metadata['block_number_source'] ?? null) === 'eth_getTransactionReceipt'
                        && ($metadata['network'] ?? null) === 'ganache'
                    ) {
                        $stats['unchanged']++;
                        continue;
                    }

                    $metadata['block_number'] = $resolvedBlockNumber;
                    $metadata['network'] = 'ganache';
                    $metadata['anchored_via'] = 'GanacheBlockchainService';
                    $metadata['block_number_verified'] = true;
                    $metadata['block_number_source'] = 'eth_getTransactionReceipt';
                    $metadata['receipt_status'] = 'ok';
                    $metadata['receipt_checked_at'] = now()->toISOString();

                    if (!$dryRun) {
                        $record->update([
                            'blockchain_metadata' => $metadata,
                        ]);
                    }

                    $stats['updated']++;
                    $this->newLine();
                    $this->line(sprintf(
                        '%s correction_record #%d (%s): block_number %s -> %d',
                        $dryRun ? '[DRY-RUN] Would update' : 'Updated',
                        $record->id,
                        $this->shortHash($txHash),
                        $currentBlockNumber === null ? 'null' : (string) $currentBlockNumber,
                        $resolvedBlockNumber
                    ));
                } catch (\Throwable $e) {
                    $stats['errors']++;
                    Log::warning('corrections:backfill-block-numbers failed for record', [
                        'correction_record_id' => $record->id,
                        'transaction_hash' => $record->correction_tx_hash,
                        'error' => $e->getMessage(),
                    ]);

                    $this->newLine();
                    $this->error(sprintf(
                        'Failed correction_record #%d (%s): %s',
                        $record->id,
                        $this->shortHash($record->correction_tx_hash),
                        $e->getMessage()
                    ));
                } finally {
                    $progressBar->advance();
                }
            }
        });

        $progressBar->finish();
        $this->newLine(2);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Processed', $stats['processed']],
                ['Updated', $stats['updated']],
                ['Unchanged', $stats['unchanged']],
                ['Missing Receipt', $stats['missing_receipt']],
                ['Marked Unverifiable', $stats['marked_unverifiable']],
                ['Errors', $stats['errors']],
                ['Mode', $dryRun ? 'dry-run' : 'write'],
            ]
        );

        if ($stats['errors'] > 0) {
            $this->warn('Backfill completed with errors. Check logs for details.');
        } else {
            $this->info('Backfill completed successfully.');
        }

        return Command::SUCCESS;
    }

    private function fetchTransactionReceipt(string $ganacheUrl, string $transactionHash): ?array
    {
        $response = Http::timeout(10)->post($ganacheUrl, [
            'jsonrpc' => '2.0',
            'method' => 'eth_getTransactionReceipt',
            'params' => [$transactionHash],
            'id' => time(),
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('HTTP request failed with status ' . $response->status());
        }

        $payload = $response->json();

        if (isset($payload['error'])) {
            $errorMessage = $payload['error']['message'] ?? 'Unknown JSON-RPC error';
            throw new \RuntimeException($errorMessage);
        }

        $result = $payload['result'] ?? null;

        return is_array($result) ? $result : null;
    }

    private function shortHash(?string $hash): string
    {
        if (!$hash) {
            return 'n/a';
        }

        if (strlen($hash) <= 18) {
            return $hash;
        }

        return substr($hash, 0, 10) . '...' . substr($hash, -6);
    }
}
