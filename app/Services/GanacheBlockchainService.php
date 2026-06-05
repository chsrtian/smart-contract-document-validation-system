<?php


namespace App\Services;

use Web3\Web3;
use Web3\Providers\HttpProvider;
use Web3\RequestManagers\HttpRequestManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Scan;
use App\Models\CorrectionRecord;

class GanacheBlockchainService
{
    // ========================================
    // CLASS PROPERTIES (must be at the top)
    // ========================================
    protected $web3;
    protected $ganacheUrl;
    protected $networkId;
    protected $fromAddress;
    protected $gasLimit;
    protected $gasPrice;
    
    // ========================================
    // CONSTRUCTOR (unchanged - no modifications needed)
    // ========================================
    public function __construct()
    {
        try {
            $this->ganacheUrl = config('blockchain.ganache.url') ?? env('GANACHE_URL', 'http://127.0.0.1:7545');
            $this->networkId = config('blockchain.ganache.network_id') ?? env('GANACHE_NETWORK_ID', '5777');
            $this->fromAddress = config('blockchain.ganache.from_address') ?? env('GANACHE_FROM_ADDRESS');
            $this->gasLimit = (int)(config('blockchain.ganache.gas_limit') ?? env('GANACHE_GAS_LIMIT', 6721975));
            
            if (empty($this->fromAddress)) {
                throw new \Exception('GANACHE_FROM_ADDRESS not configured in .env file');
            }
            
            if (!preg_match('/^0x[a-fA-F0-9]{40}$/', $this->fromAddress)) {
                throw new \Exception("Invalid Ethereum address: {$this->fromAddress}");
            }
            
            // Test connection first
            if (!$this->testConnection()) {
                throw new \Exception('Cannot connect to Ganache at ' . $this->ganacheUrl);
            }
            
            // Reduced timeout to prevent hanging
            $timeout = 10;
            
            $this->web3 = new Web3(new HttpProvider(
                new HttpRequestManager($this->ganacheUrl, $timeout)
            ));
            
            Log::info('GanacheBlockchainService initialized successfully', [
                'ganache_url' => $this->ganacheUrl,
                'network_id' => $this->networkId,
                'from_address' => $this->fromAddress,
                'gas_limit' => $this->gasLimit,
                'timeout' => $timeout
            ]);
            
        } catch (\Exception $e) {
            Log::error('GanacheBlockchainService initialization failed', [
                'error' => $e->getMessage(),
                'ganache_url' => $this->ganacheUrl ?? 'not set',
                'from_address_env' => env('GANACHE_FROM_ADDRESS') ?? 'not set',
                'network_id' => $this->networkId ?? 'not set'
            ]);
            
            throw new \Exception('Failed to initialize Ganache connection: ' . $e->getMessage());
        }
    }

    // ========================================
    // CONNECTION TEST
    // ========================================
    private function testConnection(): bool
    {
        try {
            $response = Http::timeout(5)->post($this->ganacheUrl, [
                'jsonrpc' => '2.0',
                'method' => 'eth_blockNumber',
                'params' => [],
                'id' => 1
            ]);
            
            if ($response->successful() && $response->json('result')) {
                Log::info('Ganache connection test successful', [
                    'block_number' => $response->json('result')
                ]);
                return true;
            }
            
            Log::warning('Ganache connection test failed', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            return false;
            
        } catch (\Exception $e) {
            Log::error('Ganache connection test error', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    // ========================================
    // ANCHOR DOCUMENT (for original documents)
    // ========================================
    public function anchorDocument(Scan $scan): array
    {
        try {
            Log::info('Starting Ganache anchoring', [
                'scan_id' => $scan->id,
                'document_id' => $scan->document_id,
                'from_address' => $this->fromAddress
            ]);
            
            $documentHash = $this->generateDocumentHash($scan);
            $data = $this->encodeDocumentData($scan, $documentHash);
            
            $nonce = $this->getTransactionCountHttp($this->fromAddress);
            $gasPrice = $this->getGasPriceHttp();
            
            Log::info('Transaction params retrieved', [
                'nonce' => $nonce,
                'gas_price' => $gasPrice,
                'document_hash' => $documentHash
            ]);
            
            $transactionParams = [
                'from' => $this->fromAddress,
                'to' => $this->fromAddress,
                'value' => '0x0',
                'gas' => '0x' . dechex($this->gasLimit),
                'gasPrice' => '0x' . dechex($gasPrice),
                'nonce' => '0x' . dechex($nonce),
                'data' => '0x' . bin2hex($data)
            ];
            
            $txHash = $this->sendTransactionHttp($transactionParams);
            
            Log::info('Transaction sent successfully', [
                'tx_hash' => $txHash
            ]);
            
            $receipt = $this->waitForTransactionReceiptHttp($txHash, 30);
            
            if (!$receipt) {
                throw new \Exception('Transaction receipt not received');
            }
            
            Log::info('Transaction receipt received', [
                'tx_hash' => $txHash,
                'block_number' => $receipt['blockNumber'],
                'gas_used' => $receipt['gasUsed'],
                'status' => $receipt['status']
            ]);
            
            $this->updateScanWithTransactionDetails($scan, $txHash, (object)$receipt, $transactionParams);
            
            return [
                'success' => true,
                'message' => 'Document successfully anchored to Ganache blockchain',
                'transaction_hash' => $txHash,
                'block_number' => hexdec($receipt['blockNumber']),
                'gas_used' => hexdec($receipt['gasUsed']),
                'status' => $receipt['status'] === '0x1' ? 'success' : 'failed',
                'network_id' => $this->networkId,
                'ganache_url' => $this->ganacheUrl,
                'input_data' => $transactionParams['data']
            ];
            
        } catch (\Exception $e) {
            Log::error('Ganache anchoring failed', [
                'scan_id' => $scan->id,
                'error' => $e->getMessage()
            ]);
            
            $scan->update([
                'blockchain_status' => 'failed',
                'blockchain_metadata' => json_encode([
                    'error' => $e->getMessage(),
                    'failed_at' => now()->toIso8601String()
                ])
            ]);
            
            return [
                'success' => false,
                'message' => 'Blockchain anchoring failed: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    // ========================================
    // ANCHOR CORRECTION (for approved corrections)
    // ========================================
    public function anchorCorrection(CorrectionRecord $correction): array
    {
        try {
            Log::info('GanacheBlockchainService: Starting correction anchoring', [
                'correction_id' => $correction->id,
                'scan_id' => $correction->scan_id,
                'reference_tx_hash' => $correction->reference_tx_hash,
                'corrected_field' => $correction->corrected_field
            ]);

            // Build correction payload (includes reference to original)
            $correctionPayload = [
                'type' => 'document_correction',
                'original_tx_hash' => $correction->reference_tx_hash,
                'document_id' => $correction->scan->document_id ?? 'unknown',
                'corrected_field' => $correction->corrected_field,
                'previous_value_hash' => hash('sha256', $correction->previous_value ?? ''),
                'new_value_hash' => hash('sha256', $correction->new_value ?? ''),
                'correction_reason' => $correction->correction_reason,
                'corrected_by' => $correction->corrected_by_staff,
                'approved_by' => $correction->approved_by_supervisor,
                'timestamp' => now()->timestamp
            ];

            // Generate correction document hash
            $correctionHash = '0x' . hash('sha256', json_encode($correctionPayload));

            // Submit to Ganache - this creates a REAL transaction
            $result = $this->sendCorrectionTransaction($correctionHash, $correctionPayload);

            if ($result['success']) {
                Log::info('GanacheBlockchainService: Correction anchored successfully', [
                    'correction_id' => $correction->id,
                    'correction_tx_hash' => $result['transactionHash'],
                    'block_number' => $result['blockNumber'],
                    'reference_tx_hash' => $correction->reference_tx_hash
                ]);

                return [
                    'success' => true,
                    'correction_tx_hash' => $result['transactionHash'],
                    'correction_document_hash' => $correctionHash,
                    'reference_tx_hash' => $correction->reference_tx_hash,
                    'block_number' => $result['blockNumber'],
                    'gas_used' => $result['gasUsed'] ?? 21000,
                    'network' => 'ganache',
                    'timestamp' => now()->toISOString(),
                    'type' => 'document_correction'
                ];
            }

            throw new \Exception($result['error'] ?? 'Failed to send correction transaction');

        } catch (\Exception $e) {
            Log::error('GanacheBlockchainService: Correction anchoring failed', [
                'correction_id' => $correction->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ];
        }
    }

    // ========================================
    // SEND CORRECTION TRANSACTION
    // ========================================
    private function sendCorrectionTransaction(string $correctionHash, array $payload): array
    {
        try {
            // Encode the payload as hex data for the transaction
            $dataHex = '0x' . bin2hex(json_encode($payload));

            // Build the transaction
            $transaction = [
                'from' => $this->fromAddress,
                'to' => $this->fromAddress,
                'value' => '0x0',
                'gas' => '0x' . dechex($this->gasLimit),
                'data' => $dataHex
            ];

            $transactionHash = null;
            $error = null;

            // Send transaction via JSON-RPC
            $response = Http::timeout(30)->post($this->ganacheUrl, [
                'jsonrpc' => '2.0',
                'method' => 'eth_sendTransaction',
                'params' => [$transaction],
                'id' => time()
            ]);

            if ($response->successful()) {
                $result = $response->json();
                
                if (isset($result['result'])) {
                    $transactionHash = $result['result'];
                    $blockNumber = null;
                    $gasUsed = 21000;

                    // Wait for receipt so block number comes from the actual mined transaction.
                    $receipt = $this->waitForTransactionReceiptHttp($transactionHash, 10);
                    if ($receipt) {
                        $blockNumber = isset($receipt['blockNumber']) ? hexdec($receipt['blockNumber']) : null;
                        $gasUsed = isset($receipt['gasUsed']) ? hexdec($receipt['gasUsed']) : 21000;
                    } else {
                        Log::warning('GanacheBlockchainService: receipt not found yet for correction transaction', [
                            'transaction_hash' => $transactionHash,
                        ]);
                    }

                    return [
                        'success' => true,
                        'transactionHash' => $transactionHash,
                        'blockNumber' => $blockNumber,
                        'gasUsed' => $gasUsed
                    ];
                } elseif (isset($result['error'])) {
                    $error = $result['error']['message'] ?? 'Unknown RPC error';
                }
            } else {
                $error = 'HTTP request failed: ' . $response->status();
            }

            return [
                'success' => false,
                'error' => $error ?? 'Unknown error'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    // ========================================
    // HELPER METHODS
    // ========================================
    private function getTransactionCountHttp(string $address): int
    {
        $response = Http::timeout(10)->post($this->ganacheUrl, [
            'jsonrpc' => '2.0',
            'method' => 'eth_getTransactionCount',
            'params' => [$address, 'pending'],
            'id' => 1
        ]);
        
        if (!$response->successful()) {
            throw new \Exception('Failed to get transaction count: HTTP ' . $response->status());
        }
        
        $result = $response->json('result');
        if ($result === null) {
            throw new \Exception('Invalid response for transaction count');
        }
        
        return hexdec($result);
    }
    
    private function getGasPriceHttp(): int
    {
        $response = Http::timeout(10)->post($this->ganacheUrl, [
            'jsonrpc' => '2.0',
            'method' => 'eth_gasPrice',
            'params' => [],
            'id' => 1
        ]);
        
        if (!$response->successful()) {
            Log::warning('Failed to get gas price, using fallback');
            return 25000000000;
        }
        
        $result = $response->json('result');
        if ($result === null) {
            return 25000000000;
        }
        
        $gasPrice = hexdec($result);
        return (int)($gasPrice * 1.25);
    }
    
    private function sendTransactionHttp(array $params): string
    {
        $response = Http::timeout(30)->post($this->ganacheUrl, [
            'jsonrpc' => '2.0',
            'method' => 'eth_sendTransaction',
            'params' => [$params],
            'id' => 1
        ]);
        
        if (!$response->successful()) {
            throw new \Exception('Failed to send transaction: HTTP ' . $response->status());
        }
        
        $result = $response->json('result');
        if (!$result) {
            $error = $response->json('error.message') ?? 'Unknown error';
            throw new \Exception('Transaction failed: ' . $error);
        }
        
        return $result;
    }
    
    private function waitForTransactionReceiptHttp(string $txHash, int $maxAttempts = 30): ?array
    {
        $attempts = 0;
        
        while ($attempts < $maxAttempts) {
            $response = Http::timeout(10)->post($this->ganacheUrl, [
                'jsonrpc' => '2.0',
                'method' => 'eth_getTransactionReceipt',
                'params' => [$txHash],
                'id' => 1
            ]);
            
            if ($response->successful()) {
                $receipt = $response->json('result');
                if ($receipt !== null) {
                    return $receipt;
                }
            }
            
            $attempts++;
            sleep(1);
            
            Log::debug('Waiting for transaction receipt', [
                'tx_hash' => $txHash,
                'attempt' => $attempts,
                'max_attempts' => $maxAttempts
            ]);
        }
        
        return null;
    }
    
    protected function generateDocumentHash(Scan $scan): string
    {
        $dataToHash = json_encode([
            'document_id' => $scan->document_id,
            'document_type' => $scan->document_type,
            'file_hash' => $scan->file_hash,
            'extracted_fields' => $scan->extracted_fields,
            'created_at' => $scan->created_at->toIso8601String()
        ]);
        
        return hash('sha256', $dataToHash);
    }
    
    protected function encodeDocumentData(Scan $scan, string $documentHash): string
    {
        $metadata = [
            'doc_id' => $scan->document_id,
            'doc_type' => $scan->document_type,
            'hash' => $documentHash,
            'timestamp' => time()
        ];
        
        return json_encode($metadata);
    }
    
    protected function updateScanWithTransactionDetails(Scan $scan, string $txHash, object $receipt, array $transactionParams): void
    {
        $scan->update([
            'blockchain_tx_hash' => $txHash,
            'blockchain_status' => $receipt->status === '0x1' ? 'confirmed' : 'failed',
            'blockchain_confirmed_at' => now(),
            'blockchain_block_number' => hexdec($receipt->blockNumber),
            'blockchain_gas_used' => hexdec($receipt->gasUsed),
            'blockchain_gas_price' => hexdec($transactionParams['gasPrice']),
            'blockchain_gas_limit' => hexdec($transactionParams['gas']),
            'blockchain_network_id' => $this->networkId,
            'blockchain_confirmations' => 1,
            'blockchain_block_hash' => $receipt->blockHash,
            'blockchain_transaction_index' => hexdec($receipt->transactionIndex),
            'blockchain_value' => '0',
            'blockchain_from_address' => $transactionParams['from'],
            'blockchain_to_address' => $transactionParams['to'],
            'blockchain_nonce' => hexdec($transactionParams['nonce']),
            'blockchain_status_code' => $receipt->status === '0x1' ? 1 : 0,
            'blockchain_input_data' => $transactionParams['data'] ?? null,
            'blockchain_metadata' => json_encode([
                'ganache_url' => $this->ganacheUrl,
                'network_id' => $this->networkId,
                'anchored_at' => now()->toIso8601String(),
                'gas_cost_wei' => hexdec($receipt->gasUsed) * hexdec($transactionParams['gasPrice']),
                'document_hash' => $this->generateDocumentHash($scan),
                'transaction_params' => [
                    'from' => $transactionParams['from'],
                    'to' => $transactionParams['to'],
                    'nonce' => hexdec($transactionParams['nonce']),
                    'gas_limit' => hexdec($transactionParams['gas']),
                    'gas_price' => hexdec($transactionParams['gasPrice'])
                ]
            ])
        ]);
        
        Log::info('Scan updated with complete Ganache transaction details', [
            'scan_id' => $scan->id,
            'tx_hash' => $txHash,
            'from_address' => $transactionParams['from'],
            'to_address' => $transactionParams['to'],
            'nonce' => hexdec($transactionParams['nonce']),
            'block_number' => hexdec($receipt->blockNumber),
            'gas_used' => hexdec($receipt->gasUsed),
            'status_code' => $receipt->status === '0x1' ? 1 : 0,
            'input_data_length' => strlen($transactionParams['data'] ?? '')
        ]);
    }
}