<?php

namespace App\Http\Controllers;

use App\Models\Scan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Carbon\Carbon;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use App\Jobs\AnchorToBlockchainJob;
use Illuminate\Support\Facades\Schema;
use App\Services\OCRService;


class ScanController extends Controller
{
    /**
     * Display the scan interface
     */
    public function index()
    {
        return view('staff.scan');
    }

    private function getAllSupportedDocumentTypes(): array
    {
        return Scan::DOCUMENT_TYPES;
    }

    private function getOcrSupportedDocumentTypes(): array
    {
        return Scan::OCR_DOCUMENT_TYPES;
    }

    private function isOcrSupportedDocumentType(string $documentType): bool
    {
        return in_array($documentType, $this->getOcrSupportedDocumentTypes(), true);
    }

    private function resolveProcessingMode(string $documentType, ?string $processingMode = null): string
    {
        if (in_array($processingMode, ['ocr', 'manual'], true)) {
            return $processingMode;
        }

        return $this->isOcrSupportedDocumentType($documentType) ? 'ocr' : 'manual';
    }

    public function detectScanners(Request $request)
{
    try {
        // Get scanner bridge URL from environment
        $bridgeUrl = env('SCANNER_BRIDGE_URL', 'http://127.0.0.1:3000');
        $bridgeEnabled = env('SCANNER_BRIDGE_ENABLED', true);
        
        Log::info('Scanner detection request received', [
            'bridge_url' => $bridgeUrl,
            'bridge_enabled' => $bridgeEnabled,
            'user_id' => Auth::id()
        ]);
        
        // If bridge is enabled, return bridge connection info
        if ($bridgeEnabled) {
            // Optional: Ping bridge to check if it's running
            $bridgeStatus = $this->checkBridgeStatus($bridgeUrl);
            
            return response()->json([
                'success' => true,
                'bridge_url' => $bridgeUrl,
                'bridge_status' => $bridgeStatus,
                'message' => $bridgeStatus['online'] 
                    ? 'Scanner bridge is online. Use bridge for real-time detection.' 
                    : 'Scanner bridge offline. Using fallback scanners.',
                'fallback_scanners' => $this->getMockScanners(), // For offline mode
                'detection_method' => 'bridge'
            ]);
        }
        
        // Fallback: Traditional detection methods (if bridge disabled)
        $scanners = [];
        
        // Method 1: Brother MFC detection
        $brotherScanner = $this->detectBrotherMFC();
        if ($brotherScanner) {
            $scanners[] = $brotherScanner;
        }
        
        // Method 2: Generic scanner detection
        $genericScanners = $this->detectGenericScanners();
        $scanners = array_merge($scanners, $genericScanners);
        
        // Method 3: Network printer detection
        $networkPrinters = $this->detectNetworkPrintersWithScan();
        $scanners = array_merge($scanners, $networkPrinters);
        
        Log::info('Traditional scanner detection completed', [
            'found_scanners' => count($scanners),
            'scanners' => $scanners
        ]);
        
        return response()->json([
            'success' => true,
            'scanners' => $scanners,
            'message' => count($scanners) > 0 
                ? 'Scanners detected successfully' 
                : 'No scanners found',
            'detection_method' => 'traditional'
        ]);
        
    } catch (\Exception $e) {
        Log::error('Scanner detection failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        // Return mock scanners for development/fallback
        return response()->json([
            'success' => true,
            'scanners' => $this->getMockScanners(),
            'message' => 'Using mock scanners (fallback mode)',
            'detection_method' => 'mock',
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Check if scanner bridge is online and responding
 * 
 * @param string $bridgeUrl
 * @return array Status information
 */
private function checkBridgeStatus(string $bridgeUrl): array
{
    try {
        $healthUrl = rtrim($bridgeUrl, '/') . '/health';
        
        // Create a stream context with timeout
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 2, // 2 second timeout
                'ignore_errors' => true
            ]
        ]);
        
        $response = @file_get_contents($healthUrl, false, $context);
        
        if ($response === false) {
            return [
                'online' => false,
                'message' => 'Scanner bridge not responding',
                'checked_at' => now()->toISOString()
            ];
        }
        
        $data = json_decode($response, true);
        
        if (isset($data['status']) && $data['status'] === 'ok') {
            return [
                'online' => true,
                'message' => 'Scanner bridge is operational',
                'version' => $data['version'] ?? 'unknown',
                'platform' => $data['platform'] ?? 'unknown',
                'checked_at' => now()->toISOString()
            ];
        }
        
        return [
            'online' => false,
            'message' => 'Scanner bridge returned unexpected response',
            'checked_at' => now()->toISOString()
        ];
        
    } catch (\Exception $e) {
        Log::warning('Bridge status check failed', [
            'error' => $e->getMessage(),
            'bridge_url' => $bridgeUrl
        ]);
        
        return [
            'online' => false,
            'message' => 'Bridge status check failed: ' . $e->getMessage(),
            'checked_at' => now()->toISOString()
        ];
    }
}

    /**
     * Detect Brother MFC-T4500DW specifically
     */
    private function detectBrotherMFC()
    {
        // TODO: Implement Brother-specific detection
        // This would use Brother's SDK or network discovery
        
        try {
            // Check common Brother printer IPs
            $brotherIPs = ['192.168.1.100', '192.168.0.100', '10.0.0.100'];
            
            foreach ($brotherIPs as $ip) {
                if ($this->pingDevice($ip, 9100)) { // IPP port
                    return [
                        'id' => 'brother_mfc_t4500dw_' . str_replace('.', '_', $ip),
                        'name' => 'Brother MFC-T4500DW',
                        'status' => 'Ready',
                        'ip_address' => $ip,
                        'maxResolution' => '1200 DPI',
                        'scanSpeed' => '29 PPM',
                        'capabilities' => ['color', 'grayscale', 'bw', 'duplex', 'ocr', 'adf'],
                        'supportedFormats' => ['pdf', 'jpg', 'png', 'tiff'],
                        'isActualDevice' => true,
                        'manufacturer' => 'Brother',
                        'model' => 'MFC-T4500DW'
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('Brother scanner detection failed', ['error' => $e->getMessage()]);
        }
        
        return null;
    }

    /**
     * Detect generic scanners using system commands
     */
    private function detectGenericScanners()
    {
        $scanners = [];
        
        try {
            // Windows: Use WMI to detect imaging devices
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $output = [];
                exec('wmic path Win32_PnPEntity where "Name like \'%scan%\' or Name like \'%imaging%\'" get Name,DeviceID', $output);
                
                foreach ($output as $line) {
                    if (stripos($line, 'scan') !== false || stripos($line, 'imaging') !== false) {
                        $scanners[] = [
                            'id' => 'generic_' . md5($line),
                            'name' => trim($line),
                            'status' => 'Ready',
                            'maxResolution' => '600 DPI',
                            'capabilities' => ['color', 'grayscale', 'bw'],
                            'supportedFormats' => ['pdf', 'jpg', 'png'],
                            'isActualDevice' => true,
                            'type' => 'system_detected'
                        ];
                    }
                }
            }
            
            // Linux: Use SANE to detect scanners
            if (strtoupper(substr(PHP_OS, 0, 5)) === 'LINUX') {
                $output = [];
                exec('scanimage -L 2>/dev/null', $output);
                
                foreach ($output as $line) {
                    if (stripos($line, 'device') !== false) {
                        preg_match('/device `([^\']+)\' is a (.+)/', $line, $matches);
                        if (count($matches) >= 3) {
                            $scanners[] = [
                                'id' => 'sane_' . md5($matches[1]),
                                'name' => $matches[2],
                                'device_path' => $matches[1],
                                'status' => 'Ready',
                                'maxResolution' => '600 DPI',
                                'capabilities' => ['color', 'grayscale', 'bw'],
                                'supportedFormats' => ['pdf', 'jpg', 'png', 'tiff'],
                                'isActualDevice' => true,
                                'type' => 'sane_detected'
                            ];
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Generic scanner detection failed', ['error' => $e->getMessage()]);
        }
        
        return $scanners;
    }

    /**
     * Detect network printers with scan capability
     */
    private function detectNetworkPrintersWithScan()
    {
        $printers = [];
        
        try {
            // Check common printer network ranges
            $networkRanges = [
                '192.168.1.1-192.168.1.254',
                '192.168.0.1-192.168.0.254',
                '10.0.0.1-10.0.0.254'
            ];
            
            foreach ($networkRanges as $range) {
                $foundPrinters = $this->scanNetworkRange($range);
                $printers = array_merge($printers, $foundPrinters);
            }
        } catch (\Exception $e) {
            Log::warning('Network printer detection failed', ['error' => $e->getMessage()]);
        }
        
        return $printers;
    }

    /**
     * Scan network range for printers
     */
    private function scanNetworkRange($range)
    {
        $printers = [];
        // TODO: Implement actual network scanning
        // This is a simplified version for demonstration
        
        return $printers;
    }

    /**
     * Ping device to check availability
     */
    private function pingDevice($host, $port, $timeout = 1)
    {
        try {
            $connection = @fsockopen($host, $port, $errno, $errstr, $timeout);
            if ($connection) {
                fclose($connection);
                return true;
            }
        } catch (\Exception $e) {
            // Connection failed
        }
        return false;
    }

    /**
     * Get mock scanners for development
     */
    private function getMockScanners()
    {
        return [
            [
                'id' => 'brother_mfc_t4500dw',
                'name' => 'Brother MFC-T4500DW (Mock)',
                'status' => 'Ready',
                'maxResolution' => '1200 DPI',
                'scanSpeed' => '29 PPM',
                'capabilities' => ['color', 'grayscale', 'bw', 'duplex', 'ocr', 'adf'],
                'supportedFormats' => ['pdf', 'jpg', 'png', 'tiff'],
                'isActualDevice' => false,
                'manufacturer' => 'Brother',
                'model' => 'MFC-T4500DW'
            ],
            [
                'id' => 'virtual_scanner',
                'name' => 'Virtual Scanner (Development)',
                'status' => 'Ready',
                'maxResolution' => '600 DPI',
                'scanSpeed' => '15 PPM',
                'capabilities' => ['color', 'grayscale', 'bw'],
                'supportedFormats' => ['pdf', 'jpg', 'png'],
                'isActualDevice' => false,
                'type' => 'virtual'
            ]
        ];
    }

    /**
     * Generate scan preview
     */
    public function preview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'scanner_id' => 'required|string',
            'quality' => 'sometimes|integer|in:150,300,600,1200'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid parameters',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            Log::info('Preview scan initiated', [
                'scanner_id' => $request->scanner_id,
                'quality' => $request->quality ?? 300,
                'user_id' => Auth::id()
            ]);

            // TODO: Implement actual preview scanning
            // For now, return a mock preview
            
            $previewPath = $this->generateMockPreview($request->scanner_id);
            
            return response()->json([
                'success' => true,
                'preview_url' => $previewPath,
                'message' => 'Preview generated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Preview scan failed', [
                'error' => $e->getMessage(),
                'scanner_id' => $request->scanner_id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Preview failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start actual scanning process
     */
    public function scan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'scanner_id' => 'required|string',
            'quality' => 'required|in:150,300,600,1200',
            'color_mode' => 'required|in:color,grayscale,bw',
            'output_format' => 'required|in:pdf,jpg,png,tiff',
            'copies' => 'sometimes|integer|min:1|max:99',
            'ocr_enabled' => 'sometimes|boolean',
            'auto_crop' => 'sometimes|boolean',
            'brightness' => 'sometimes|integer|min:-50|max:50',
            'contrast' => 'sometimes|integer|min:-50|max:50',
            'compression' => 'sometimes|integer|min:1|max:100',
            'duplex_mode' => 'sometimes|in:simplex,duplex,auto',
            'scan_range' => 'sometimes|in:all,current,selection,custom',
            'custom_pages' => 'sometimes|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid scan parameters',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $scanSettings = $request->all();
            $scanId = 'scan_' . time() . '_' . Str::random(8);
            
            Log::info('Scan initiated', [
                'scan_id' => $scanId,
                'scanner_id' => $request->scanner_id,
                'settings' => $scanSettings,
                'user_id' => Auth::id()
            ]);

            // TODO: Implement actual scanning with hardware
            $scanResult = $this->performActualScan($scanSettings, $scanId);
            
            // For now, simulate scanning process
            if (!$scanResult) {
                $scanResult = $this->simulateScan($scanSettings, $scanId);
            }

            return response()->json([
                'success' => true,
                'message' => 'Scan completed successfully',
                'scan_id' => $scanId,
                'pages_scanned' => $scanSettings['copies'] ?? 1,
                'file_path' => $scanResult['file_path'],
                'file_size' => $scanResult['file_size'],
                'scan_time' => $scanResult['scan_time']
            ]);

        } catch (\Exception $e) {
            Log::error('Scan failed', [
                'error' => $e->getMessage(),
                'scanner_id' => $request->scanner_id,
                'user_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Scan failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Perform actual scanning with hardware
     */
    private function performActualScan($settings, $scanId)
    {
        // TODO: Implement actual hardware scanning
        // This would integrate with:
        // - Brother SDK for Brother MFC-T4500DW
        // - TWAIN interface for Windows
        // - SANE for Linux
        // - WIA for Windows Image Acquisition
        
        /*
        Example implementation structure:
        
        if ($settings['scanner_id'] === 'brother_mfc_t4500dw') {
            return $this->scanWithBrotherSDK($settings, $scanId);
        } elseif (strpos($settings['scanner_id'], 'sane_') === 0) {
            return $this->scanWithSANE($settings, $scanId);
        } elseif (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return $this->scanWithWIA($settings, $scanId);
        }
        */
        
        return null; // Return null to use simulation
    }

    /**
     * Simulate scanning process for development
     */
    private function simulateScan($settings, $scanId)
    {
        // Create scans directory if it doesn't exist
        $scanDir = 'scans/' . date('Y/m/d');
        Storage::disk('public')->makeDirectory($scanDir);
        
        // Generate filename
        $filename = $scanId . '.' . $settings['output_format'];
        $filePath = $scanDir . '/' . $filename;
        
        // Create a mock scan file (copy a sample file or generate content)
        $mockContent = $this->generateMockScanContent($settings);
        Storage::disk('public')->put($filePath, $mockContent);
        
        return [
            'file_path' => '/storage/' . $filePath,
            'file_size' => strlen($mockContent),
            'scan_time' => Carbon::now()->toISOString()
        ];
    }

    /**
     * Generate mock scan content
     */
    private function generateMockScanContent($settings)
    {
        // TODO: Create actual scan content based on format
        // For now, return basic content
        
        if ($settings['output_format'] === 'pdf') {
            // Create a simple PDF
            return "%PDF-1.4\n1 0 obj\n<<\n/Type /Catalog\n/Pages 2 0 R\n>>\nendobj\n\n2 0 obj\n<<\n/Type /Pages\n/Kids [3 0 R]\n/Count 1\n>>\nendobj\n\n3 0 obj\n<<\n/Type /Page\n/Parent 2 0 R\n/MediaBox [0 0 612 792]\n>>\nendobj\n\nxref\n0 4\n0000000000 65535 f \n0000000010 00000 n \n0000000079 00000 n \n0000000173 00000 n \ntrailer\n<<\n/Size 4\n/Root 1 0 R\n>>\nstartxref\n301\n%%EOF";
        } else {
            // For images, return base64 encoded minimal image data
            return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        }
    }

    /**
     * Generate mock preview
     */
    private function generateMockPreview($scannerId)
    {
        // Return a sample preview image URL
        // In production, this would generate an actual preview
        return '/images/sample-preview.png';
    }

    /**
     * Store scanned document to database
     */
public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'type' => 'required|string|in:contract,invoice,certificate,identification,other',
            'description' => 'nullable|string|max:1000',
            'blockchain_validation' => 'sometimes|boolean',
            'files.*' => 'sometimes|file|mimes:jpg,jpeg,png,pdf,tiff|max:10240', // 10MB max
            'scanned_files' => 'sometimes|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $scan = new Scan([
                'title' => $request->title,
                'type' => $request->type,
                'description' => $request->description,
                'user_id' => Auth::id(),
                'status' => 'pending',
                'scanned_at' => Carbon::now()
            ]);

            // Handle uploaded files
            $filePaths = [];
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $index => $file) {
                    $filePath = $this->storeUploadedFile($file);
                    $filePaths[] = $filePath;
                }
            }

            // Handle scanned files
            if ($request->has('scanned_files')) {
                foreach ($request->scanned_files as $scannedFile) {
                    $fileData = json_decode($scannedFile, true);
                    if ($fileData && isset($fileData['path'])) {
                        $filePaths[] = $fileData['path'];
                    }
                }
            }

            $scan->file_paths = json_encode($filePaths);
            
            // Calculate document hash if blockchain validation is enabled
            if ($request->blockchain_validation) {
                $documentHash = $this->calculateDocumentHash($filePaths);
                $scan->document_hash = $documentHash;
                $scan->blockchain_enabled = true;
            }

            $scan->save();

            Log::info('Document saved successfully', [
                'scan_id' => $scan->id,
                'title' => $scan->title,
                'user_id' => Auth::id(),
                'file_count' => count($filePaths),
                'blockchain_enabled' => $scan->blockchain_enabled
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document saved successfully',
                'document_id' => $scan->id,
                'document_hash' => $scan->document_hash,
                'file_count' => count($filePaths)
            ]);

        } catch (\Exception $e) {
            Log::error('Document save failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save document: ' . $e->getMessage()
            ], 500);
        }
}

    /**
     * Store uploaded file
     */
    private function storeUploadedFile($file)
    {
        $fileName = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
        $filePath = $file->storeAs('uploads/scans/' . date('Y/m/d'), $fileName, 'public');
        return '/storage/' . $filePath;
    }

    /**
     * Calculate document hash for blockchain validation
     */
    public function calculateHash(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'file_paths' => 'required|array',
                'file_paths.*' => 'string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid parameters'
                ], 400);
            }

            $hash = $this->calculateDocumentHash($request->file_paths);

            return response()->json([
                'success' => true,
                'hash' => $hash,
                'algorithm' => 'SHA-256'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Hash calculation failed'
            ], 500);
        }
    }

    /**
     * Calculate SHA-256 hash of document files
     */
    private function calculateDocumentHash(array $extractedFieldsOrFilePaths, array $filePaths = []): string
    {
        $extractedFields = [];

        // Backward-compatible call support:
        // - calculateDocumentHash($filePaths)
        // - calculateDocumentHash($extractedFields, $filePaths)
        if (empty($filePaths)) {
            $filePaths = $extractedFieldsOrFilePaths;
        } else {
            $extractedFields = $extractedFieldsOrFilePaths;
        }

        $normalize = function ($value) use (&$normalize) {
            if (!is_array($value)) {
                return $value;
            }

            $normalized = [];
            foreach ($value as $key => $item) {
                $normalized[$key] = $normalize($item);
            }

            if (array_is_list($normalized)) {
                usort($normalized, static function ($a, $b) {
                    $left = json_encode($a, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
                    $right = json_encode($b, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';

                    return strcmp($left, $right);
                });

                return $normalized;
            }

            ksort($normalized);

            return $normalized;
        };

        $normalizedFields = $normalize($extractedFields);
        $normalizedFilePaths = array_values(array_map('strval', $filePaths));
        sort($normalizedFilePaths);

        $dataToHash = json_encode([
            'extracted_fields' => $normalizedFields,
            'file_paths' => $normalizedFilePaths,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($dataToHash === false) {
            throw new \RuntimeException('Failed to encode hash payload.');
        }
        
        $hash = hash('sha256', $dataToHash);
        
        Log::info('Document hash calculated', [
            'hash' => $hash,
            'data_length' => strlen($dataToHash),
            'field_count' => count($normalizedFields),
            'file_count' => count($normalizedFilePaths)
        ]);
        
        return $hash;
    }

    /**
     * Submit document hash to blockchain
     */
    public function submitToBlockchain(Request $request)
    {
        // TODO: Implement blockchain integration
        // This would connect to Ethereum/Solana networks
        
        $validator = Validator::make($request->all(), [
            'document_id' => 'required|exists:scans,id',
            'hash' => 'required|string',
            'network' => 'sometimes|in:ethereum,solana,polygon'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid parameters'
            ], 400);
        }

        try {
            // TODO: Smart contract interaction
            /*
            Example structure:
            
            $network = $request->network ?? 'ethereum';
            $scan = Scan::findOrFail($request->document_id);
            
            if ($network === 'ethereum') {
                $txHash = $this->submitToEthereum($scan, $request->hash);
            } elseif ($network === 'solana') {
                $txHash = $this->submitToSolana($scan, $request->hash);
            }
            
            $scan->blockchain_tx_hash = $txHash;
            $scan->blockchain_network = $network;
            $scan->validated_at = Carbon::now();
            $scan->save();
            */

            // For now, simulate blockchain submission
            $scan = Scan::findOrFail($request->document_id);
            $requestedNetwork = $request->network ?? 'ethereum';

            $scan->blockchain_tx_hash = 'mock_tx_' . Str::random(64);

            // Prefer current Ganache-compatible fields.
            if (Schema::hasColumn('scans', 'blockchain_network_id')) {
                $scan->blockchain_network_id = (string) config('blockchain.ganache.network_id', '5777');
            }

            if (Schema::hasColumn('scans', 'blockchain_confirmed_at')) {
                $scan->blockchain_confirmed_at = Carbon::now();
            }

            // Deprecated legacy columns are still written only when present for backward compatibility.
            if (Schema::hasColumn('scans', 'blockchain_network')) {
                $scan->blockchain_network = $requestedNetwork;
            }

            if (Schema::hasColumn('scans', 'validated_at')) {
                $scan->validated_at = Carbon::now();
            }

            $scan->save();

            $networkValue = $scan->blockchain_network_id
                ?? ($scan->blockchain_network ?? $requestedNetwork);

            return response()->json([
                'success' => true,
                'message' => 'Document hash submitted to blockchain',
                'transaction_hash' => $scan->blockchain_tx_hash,
                'network' => $networkValue
            ]);

        } catch (\Exception $e) {
            Log::error('Blockchain submission failed', [
                'error' => $e->getMessage(),
                'document_id' => $request->document_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Blockchain submission failed'
            ], 500);
        }
    }


public function show(Scan $scan)

    {
        // Load relationships
        $scan->load(['processedBy', 'reviewedBy', 'createdBy']);
        
        // Decode JSON fields
        $extractedFields = is_array($scan->extracted_fields) 
            ? $scan->extracted_fields 
            : (json_decode($scan->extracted_fields, true) ?? []);
        
        $ocrData = is_array($scan->ocr_data) 
            ? $scan->ocr_data 
            : (json_decode($scan->ocr_data, true) ?? []);
        
        Log::info('Showing document detail view', [
            'scan_id' => $scan->id,
            'document_id' => $scan->document_id,
            'user_id' => Auth::id()
        ]);
        
        return view('staff.show', compact('scan', 'extractedFields', 'ocrData'));
    }

    
public function update(Request $request, Scan $scan)
    {
        // Ensure user can only update their own scans
        if ($scan->user_id !== Auth::id()) {
            abort(403, 'Unauthorized to update this document');
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|in:contract,invoice,certificate,identification,other',
            'description' => 'sometimes|nullable|string|max:1000',
            'status' => 'sometimes|in:pending,validated,rejected'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $scan->update($request->only(['title', 'type', 'description', 'status']));

            return response()->json([
                'success' => true,
                'message' => 'Document updated successfully',
                'scan' => $scan
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Update failed'
            ], 500);
        }
    }

    /**
     * Delete scan document
     */
    public function destroy(Scan $scan)
    {
        // Ensure user can only delete their own scans
        if ($scan->user_id !== Auth::id()) {
            abort(403, 'Unauthorized to delete this document');
        }

        try {
            // Delete associated files
            if ($scan->file_paths) {
                $filePaths = json_decode($scan->file_paths, true);
                foreach ($filePaths as $filePath) {
                    $storagePath = str_replace('/storage/', '', $filePath);
                    Storage::disk('public')->delete($storagePath);
                }
            }

            $scan->delete();

            return response()->json([
                'success' => true,
                'message' => 'Document deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Delete failed'
            ], 500);
        }
    }

    /**
     * Verify blockchain validation
     */
    public function verifyBlockchain(Scan $scan)
    {
        // TODO: Implement blockchain verification
        // This would query the smart contract to verify the hash
        
        try {
            if (!$scan->blockchain_tx_hash) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document not submitted to blockchain'
                ]);
            }

            // TODO: Query blockchain for verification
            /*
            $isValid = $this->queryBlockchainHash(
                $scan->blockchain_network_id ?? $scan->blockchain_network,
                $scan->blockchain_tx_hash,
                $scan->document_hash
            );
            */

            // For now, simulate verification
            $isValid = !empty($scan->blockchain_tx_hash);
            // Prefer modern fields and fall back to legacy columns when old schema is in use.
            $networkValue = $scan->blockchain_network_id ?? ($scan->blockchain_network ?? null);
            $validatedAtValue = $scan->blockchain_confirmed_at ?? ($scan->validated_at ?? null);

            return response()->json([
                'success' => true,
                'is_valid' => $isValid,
                'transaction_hash' => $scan->blockchain_tx_hash,
                'network' => $networkValue,
                'validated_at' => $validatedAtValue
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Verification failed'
            ], 500);
        }
    }

    /**
     * Legacy method for network printer detection
     */
    public function detectNetworkPrinters(Request $request)
    {
        return $this->detectNetworkPrintersWithScan();
    }

    public function processOCR(Request $request)
{
    // Override PHP max_execution_time for this long-running OCR request.
    // php.ini may say 120 but the built-in server caches the old value
    // until restarted.  This is the only reliable way.
    set_time_limit(300);

    $validator = Validator::make($request->all(), [
        'file' => 'required|file|mimes:jpg,jpeg,png|max:10240',
        'document_type' => ['required', 'string', Rule::in($this->getAllSupportedDocumentTypes())],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 422);
    }

    try {
        $file = $request->file('file');
        $documentType = $request->input('document_type');

        if (!$this->isOcrSupportedDocumentType($documentType)) {
            return response()->json([
                'success' => false,
                'ocr_not_required' => true,
                'processing_mode' => 'manual',
                'message' => 'OCR is only available for Birth, Death, and Marriage Certificates. Save this document using manual mode.',
            ], 422);
        }
        
        Log::info('Starting enhanced OCR processing', [
            'filename' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'type' => $documentType,
            'mime_type' => $file->getMimeType()
        ]);

        // Ensure temp/ocr directory exists
        $tempOcrDir = storage_path('app' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'ocr');
        if (!file_exists($tempOcrDir)) {
            mkdir($tempOcrDir, 0755, true);
        }

        // Save uploaded file with unique name
        $uniqueFileName = 'ocr_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $originalPath = $tempOcrDir . DIRECTORY_SEPARATOR . $uniqueFileName;
        
        if (!$file->move($tempOcrDir, $uniqueFileName)) {
            throw new \Exception('Failed to save uploaded file');
        }

        // ════════════════════════════════════════════════════════════════════
        // CRITICAL: Store image path for coordinate-based name extraction
        // ════════════════════════════════════════════════════════════════════
        session(['current_ocr_image_path' => $originalPath]);
        request()->merge(['_ocr_image_path' => $originalPath]);
        
        Log::info('File saved and path stored for coordinate extraction', [
            'path' => $originalPath,
            'session_stored' => true
        ]);

        // ════════════════════════════════════════════════════════════════════
        // ENGINE: PaddleOCR via local micro-service (replaces Tesseract)
        // The field extraction pipeline below is UNCHANGED.
        // ════════════════════════════════════════════════════════════════════
        $ocrService = app(OCRService::class);
        $paddleResult = $ocrService->extractText($originalPath, $documentType);

        $paddleRawText = (string) ($paddleResult['raw_text'] ?? '');
        $paddleWordCount = (int) ($paddleResult['word_count'] ?? 0);
        $paddleConfidence = (float) ($paddleResult['confidence'] ?? 0);

        $shouldUseTesseractFallback = !$paddleResult['success']
            || trim($paddleRawText) === ''
            || ($paddleWordCount === 0 && $paddleConfidence <= 0);

        if ($shouldUseTesseractFallback) {
            $fallbackTrigger = !$paddleResult['success']
                ? 'paddle_error'
                : 'paddle_empty_result';

            if ($ocrService->isFallbackDisabled()) {
                Log::warning('PADDLE_OCR_DISABLE_FALLBACK=true is set, but fallback is enforced for production reliability.', [
                    'document_type' => $documentType,
                ]);
            }

            Log::warning('Falling back to Tesseract OCR', [
                'trigger' => $fallbackTrigger,
                'paddle_error' => $paddleResult['error'] ?? null,
                'paddle_word_count' => $paddleWordCount,
                'paddle_confidence' => $paddleConfidence,
            ]);

            $processedPath = $this->enhancedImagePreprocessing($originalPath, $documentType);
            $ocrResult = $this->extractTextWithEnhancedTesseract($processedPath, $documentType);

            if (!empty($ocrResult['success'])) {
                $ocrResult['processing_method'] = 'tesseract_fallback';
                $ocrResult['fallback_trigger'] = $fallbackTrigger;
                $ocrResult['primary_engine'] = 'paddleocr';
                $ocrResult['fallback_engine'] = 'tesseract';
            }

            $this->cleanupTempFiles($originalPath, $processedPath);
        } else {
            // ── PaddleOCR succeeded — feed raw text into existing pipeline ──
            $rawText = $paddleRawText;
            $paddleConfidence = $paddleResult['confidence'] ?? 0;

            Log::info('PaddleOCR raw text received', [
                'text_length' => strlen($rawText),
                'paddle_confidence' => $paddleConfidence,
                'word_count' => $paddleResult['word_count'] ?? 0,
                'box_count' => $paddleResult['box_count'] ?? count($paddleResult['boxes'] ?? []),
                'engine_used' => $paddleResult['engine_used'] ?? 'baseline_detector',
                'fallback_used' => $paddleResult['fallback_used'] ?? false,
                'fallback_reasons' => $paddleResult['fallback_reasons'] ?? [],
                'processing_time_ms' => $paddleResult['processing_time_ms'] ?? null,
                'timings_ms' => $paddleResult['timings_ms'] ?? null,
                'pass2_used' => $paddleResult['pass2_used'] ?? false,
            ]);

            // Reuse existing text cleaning
            $cleanedText = $this->enhancedTextCleaning($rawText);

            // Pass bounding-box data for spatial extraction (marriage certs etc.)
            $boxes = $paddleResult['boxes'] ?? [];

            // Field extraction — spatial-aware when boxes available
            $extractedFields = $this->enhancedFieldExtraction($cleanedText, $documentType, $rawText, $boxes);

            // Reuse existing confidence scoring
            $confidence = $this->calculateConfidenceScore($cleanedText, $extractedFields, $documentType);

            // Coverage: how many expected fields were actually filled
            $coverageScore = $this->calculateCoverageScore($extractedFields, $documentType);

            // Critical field hit metrics for guarded OCR calibration logging.
            $importantFields = $this->getImportantFieldsForDocumentType($documentType, array_keys($extractedFields));
            $criticalFilled = 0;
            foreach ($importantFields as $key) {
                if (!empty($extractedFields[$key]) && strlen(trim((string) $extractedFields[$key])) >= 2) {
                    $criticalFilled++;
                }
            }
            $criticalTotal = max(count($importantFields), 1);

            // Blend: OCR quality (PaddleOCR confidence) + field-based scoring + coverage
            // Old formula over-weighted text quality — new formula gives coverage 30%
            $blendedConfidence = round(
                ($paddleConfidence * 0.30) +   // OCR engine confidence (text readability)
                ($confidence       * 0.30) +   // keyword+structure score
                ($coverageScore    * 0.40),     // actual field extraction success
                2
            );

            $ocrResult = [
                'success' => true,
                'raw_text' => $rawText,
                'cleaned_text' => $cleanedText,
                'extracted_fields' => $extractedFields,
                'confidence' => $blendedConfidence,
                'ocr_quality_score' => round($paddleConfidence, 2),
                'coverage_score' => round($coverageScore, 2),
                'word_count' => str_word_count($cleanedText),
                'document_type' => $documentType,
                'processing_method' => 'paddleocr',
            ];

            Log::info('OCR calibration run metrics', [
                'document_type' => $documentType,
                'engine_used' => $paddleResult['engine_used'] ?? 'baseline_detector',
                'fallback_used' => $paddleResult['fallback_used'] ?? false,
                'fallback_reasons' => $paddleResult['fallback_reasons'] ?? [],
                'processing_time_ms' => $paddleResult['processing_time_ms'] ?? null,
                'timings_ms' => $paddleResult['timings_ms'] ?? null,
                'confidence' => $paddleConfidence,
                'word_count' => $paddleResult['word_count'] ?? 0,
                'box_count' => $paddleResult['box_count'] ?? count($boxes),
                'critical_fields_filled' => $criticalFilled,
                'critical_fields_total' => $criticalTotal,
                'pass2_used' => $paddleResult['pass2_used'] ?? false,
            ]);

            // Clean up temp file (no preprocessed copy needed)
            $this->cleanupTempFiles($originalPath, $originalPath);
        }

        if ($ocrResult['success']) {
            Log::info('OCR processing completed successfully', [
                'confidence' => $ocrResult['confidence'],
                'fields_extracted' => count(array_filter($ocrResult['extracted_fields'] ?? [])),
                'word_count' => $ocrResult['word_count']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'OCR processing completed successfully',
                'ocr_results' => $ocrResult
            ]);
        } else {
            throw new \Exception($ocrResult['error'] ?? 'OCR processing failed');
        }

    } catch (\Exception $e) {
        Log::error('OCR processing failed', [
            'error' => $e->getMessage(),
            'file' => $request->file('file') ? $request->file('file')->getClientOriginalName() : 'unknown',
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'OCR processing failed: ' . $e->getMessage()
        ], 500);
    }
}

private function extractTextWithEnhancedTesseract($imagePath, $documentType)
{
    try {
        Log::info('Starting enhanced Tesseract OCR for death certificates', [
            'image_path' => $imagePath,
            'document_type' => $documentType
        ]);

        // Find Tesseract executable
        $tesseractPath = $this->findTesseractExecutable();
        if (!$tesseractPath) {
            return [
                'success' => false,
                'error' => 'Tesseract OCR executable not found. Please ensure Tesseract is installed.'
            ];
        }

        // Create OCR instance
        $ocr = new TesseractOCR($imagePath);
        
        if ($tesseractPath !== 'tesseract') {
            $ocr->executable($tesseractPath);
        }

        if ($documentType === 'birth_certificate') {
            // PSM 6: Assume a single uniform block of text (better for form fields)
            $ocr->psm(6);
            $ocr->oem(1); // LSTM neural nets engine
            
            // Enhanced language configuration - include Filipino
            try {
                $ocr->lang('eng+fil');
                Log::info('Using English + Filipino language models for birth certificate');
            } catch (\Exception $e) {
                $ocr->lang('eng');
                Log::info('Using English language model only for birth certificate');
            }

            // Birth certificate specific character allowlist
            $allowedChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'
                          . 'abcdefghijklmnopqrstuvwxyz'
                          . '0123456789'
                          . '.,;:!?()-_/\\@#$%^&*+= \n\r\t'
                          . 'ÑñÁáÉéÍíÓóÚúÜü'; // Filipino characters

            $ocr->allowlist($allowedChars);
            
            // Configuration for better form recognition
            $ocr->configVar('tessedit_pageseg_mode', '6');
            $ocr->configVar('preserve_interword_spaces', '1');
            $ocr->configVar('textord_tablefind_good_neighbours', '1');
            $ocr->configVar('classify_enable_learning', '0');
            $ocr->configVar('classify_enable_adaptive_matcher', '1');
            
            Log::info('Applied birth certificate specific OCR configuration');
            
        } elseif ($documentType === 'death_certificate') {
            // Use PSM 4 for single column of text (better for certificates)
            $ocr->psm(4);
            $ocr->oem(1); // LSTM neural nets engine
            
            // Enhanced language configuration
            try {
                $ocr->lang('eng+fil');
                Log::info('Using English + Filipino language models for death certificate');
            } catch (\Exception $e) {
                $ocr->lang('eng');
                Log::info('Using English language model only for death certificate');
            }

            // Death certificate specific character allowlist
            $allowedChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'
                          . 'abcdefghijklmnopqrstuvwxyz'
                          . '0123456789'
                          . '.,;:!?()-_/\\@#$%^&*+= \n\r\t'
                          . 'ÑñÁáÉéÍíÓóÚúÜü'; // Filipino characters

            $ocr->allowlist($allowedChars);
            
            // Enhanced configuration for death certificates
            $ocr->configVar('tessedit_pageseg_mode', '4');
            $ocr->configVar('preserve_interword_spaces', '1');
            $ocr->configVar('textord_tablefind_good_neighbours', '1');
            $ocr->configVar('textord_tabfind_find_tables', '1');
            $ocr->configVar('classify_enable_learning', '0');
            $ocr->configVar('classify_enable_adaptive_matcher', '1');
            
            Log::info('Applied death certificate specific OCR configuration');
            
        } elseif ($documentType === 'marriage_certificate') {
            // Marriage certificates: two-column layout needs PSM 4 (single column) for better results
            $ocr->psm(4);
            $ocr->oem(1);
            
            try {
                $ocr->lang('eng+fil');
                Log::info('Using English + Filipino language models for marriage certificate');
            } catch (\Exception $e) {
                $ocr->lang('eng');
                Log::info('Using English language model only for marriage certificate');
            }

            $allowedChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'
                          . 'abcdefghijklmnopqrstuvwxyz'
                          . '0123456789'
                          . '.,;:!?()-_/\\@#$%^&*+= \n\r\t'
                          . 'ÑñÁáÉéÍíÓóÚúÜü';

            $ocr->allowlist($allowedChars);
            
            $ocr->configVar('tessedit_pageseg_mode', '4');
            $ocr->configVar('preserve_interword_spaces', '1');
            $ocr->configVar('textord_tablefind_good_neighbours', '1');
            $ocr->configVar('classify_enable_learning', '0');
            $ocr->configVar('classify_enable_adaptive_matcher', '1');
            
            Log::info('Applied marriage certificate specific OCR configuration');
            
        } else {
            // Default configuration
            $ocr->psm(3);
            $ocr->oem(1);
            $ocr->lang('eng');
        }

        // Execute OCR with primary PSM mode
        $rawText = $ocr->run();

        // Multi-pass OCR: try a second pass with a different PSM if the first yields poor results
        $rawText = $this->multiPassOCR($rawText, $imagePath, $documentType, $tesseractPath);

        if (empty(trim($rawText))) {
            Log::warning('No text extracted after multi-pass, trying fallback methods');
            return $this->fallbackOCRProcessing($imagePath, $documentType);
        }

        Log::info('Enhanced OCR extraction successful', [
            'text_length' => strlen($rawText),
            'preview' => substr(str_replace(["\n", "\r"], ' ', $rawText), 0, 150) . '...'
        ]);

        // Enhanced text cleaning
        $cleanedText = $this->enhancedTextCleaning($rawText);
        
        // Extract structured fields with enhanced patterns
        $extractedFields = $this->enhancedFieldExtraction($cleanedText, $documentType, $rawText);
        
        // Calculate confidence score
        $confidence = $this->calculateConfidenceScore($cleanedText, $extractedFields, $documentType);
        $wordCount = str_word_count($cleanedText);

        if ($confidence <= 0 && $wordCount > 0) {
            $confidence = $this->estimateTesseractConfidence($wordCount, $extractedFields);
        }

        return [
            'success' => true,
            'raw_text' => $rawText,
            'cleaned_text' => $cleanedText,
            'extracted_fields' => $extractedFields,
            'confidence' => $confidence,
            'word_count' => $wordCount,
            'document_type' => $documentType,
            'processing_method' => 'tesseract_enhanced'
        ];

    } catch (\Exception $e) {
        Log::error('Enhanced Tesseract OCR failed', [
            'error' => $e->getMessage(),
            'image_path' => $imagePath
        ]);

        return [
            'success' => false,
            'error' => 'OCR processing failed: ' . $e->getMessage()
        ];
    }
}

private function enhancedImagePreprocessing($imagePath, $documentType)
{
    try {
        Log::info('Starting enhanced image preprocessing', [
            'input_path' => $imagePath,
            'document_type' => $documentType,
            'file_exists' => file_exists($imagePath),
            'file_size' => file_exists($imagePath) ? filesize($imagePath) : 0
        ]);

        // Generate processed file path
        $processedPath = str_replace('.', '_processed.', $imagePath);

        // Use ImageManager with proper driver
        $manager = new ImageManager(new Driver());
        $image = $manager->read($imagePath);
        
        // Get original dimensions
        $originalWidth = $image->width();
        $originalHeight = $image->height();
        
        Log::info('Original image dimensions', [
            'width' => $originalWidth,
            'height' => $originalHeight
        ]);

        // ENHANCED: Document-specific preprocessing
        if ($documentType === 'marriage_certificate') {
            // Step 1: Significantly increase resolution for better text recognition
            $scaleFactor = 2.5; // Increase size for marriage certificates
            $image->resize((int)($originalWidth * $scaleFactor), (int)($originalHeight * $scaleFactor));
            
            // Step 2: Convert to grayscale first
            $image->greyscale();
            
            // Step 3: Apply enhanced contrast for better text clarity
            $image->contrast(35); // Higher contrast for marriage certificates
            $image->brightness(15); // Higher brightness to make text clearer
            
            // Step 4: Apply sharpening for text clarity
            $image->sharpen(20); // More aggressive sharpening
            
            // Step 5: Apply additional contrast boost
            $image->contrast(45); // Final contrast boost for clean text
            
            Log::info('Applied marriage certificate specific preprocessing', [
                'scale_factor' => $scaleFactor,
                'contrast' => 35,
                'brightness' => 15,
                'sharpening' => 20
            ]);
            
        } elseif ($documentType === 'death_certificate') {
            // Step 1: Significantly increase resolution for better text recognition
            $scaleFactor = 3.0; // Triple the size for death certificates
            $image->resize((int)($originalWidth * $scaleFactor), (int)($originalHeight * $scaleFactor));
            
            // Step 2: Convert to grayscale first
            $image->greyscale();
            
            // Step 3: Apply aggressive contrast enhancement for legal documents
            $image->contrast(40); // Much higher contrast for death certificates
            $image->brightness(20); // Higher brightness to make text clearer
            
            // Step 4: Apply sharpening for text clarity
            $image->sharpen(25); // More aggressive sharpening
            
            // Step 5: Apply threshold effect to make text black and white
            $image->contrast(50); // Final contrast boost for clean text
            
            Log::info('Applied death certificate specific preprocessing', [
                'scale_factor' => $scaleFactor,
                'contrast' => 40,
                'brightness' => 20,
                'sharpening' => 25
            ]);
            
        } elseif ($documentType === 'birth_certificate') {
            // Enhanced preprocessing for birth certificates - aggressive settings for form fields
            $scaleFactor = 3.0; // Match death certificate scale for consistency
            $image->resize((int)($originalWidth * $scaleFactor), (int)($originalHeight * $scaleFactor));
            
            // Convert to grayscale
            $image->greyscale();
            
            // Aggressive enhancements to match death cert quality
            $image->contrast(35); // Increased from 20
            $image->brightness(15); // Increased from 8
            $image->sharpen(20); // Increased from 12
            
            // Second contrast pass to push text towards pure black
            $image->contrast(40); // Final contrast boost
            
            Log::info('Applied birth certificate specific preprocessing', [
                'scale_factor' => $scaleFactor,
                'contrast' => '35+40',
                'brightness' => 15,
                'sharpening' => 20
            ]);
            
        } else {
            // Standard preprocessing for other documents
            $scaleFactor = 1.5; // Moderate increase
            $image->resize((int)($originalWidth * $scaleFactor), (int)($originalHeight * $scaleFactor));
            
            $image->greyscale();
            $image->contrast(20);
            $image->brightness(10);
            $image->sharpen(15);
            
            Log::info('Applied standard preprocessing', [
                'scale_factor' => $scaleFactor,
                'document_type' => $documentType
            ]);
        }

        // Save with maximum quality
        $image->save($processedPath, quality: 100);

        Log::info('Enhanced preprocessing completed', [
            'processed_path' => $processedPath,
            'final_size' => filesize($processedPath),
            'final_dimensions' => [
                'width' => $image->width(),
                'height' => $image->height()
            ]
        ]);

        return $processedPath;

    } catch (\Exception $e) {
        Log::error('Enhanced image preprocessing failed', [
            'error' => $e->getMessage(),
            'input_path' => $imagePath,
            'document_type' => $documentType,
            'trace' => $e->getTraceAsString()
        ]);
        
        // Fallback to original image if preprocessing fails
        Log::warning('Using original image as fallback due to preprocessing failure');
        return $imagePath;
    }
}
 
private function cleanupTempFiles($originalPath, $processedPath)
{
    try {
        if (file_exists($originalPath)) {
            unlink($originalPath);
        }
        if ($processedPath !== $originalPath && file_exists($processedPath)) {
            unlink($processedPath);
        }
    } catch (\Exception $e) {
        Log::warning('Failed to clean up temp files', ['error' => $e->getMessage()]);
    }
}

/**
 * Multi-pass OCR: Run a second pass with an alternative PSM mode and merge/select better result.
 * This compensates for the fact that different PSM modes capture different parts of a certificate.
 */
private function multiPassOCR($primaryText, $imagePath, $documentType, $tesseractPath)
{
    // If primary text is already good (300+ chars with letters), skip second pass
    $letterCount = preg_match_all('/[A-Za-z]/', $primaryText);
    if ($letterCount >= 300) {
        Log::info('Primary OCR pass produced sufficient text, skipping second pass', [
            'letter_count' => $letterCount
        ]);
        return $primaryText;
    }

    // Determine alternate PSM mode for the second pass
    $alternatePSM = match ($documentType) {
        'birth_certificate' => 4,   // Try single column if uniform block (6) was poor
        'death_certificate' => 6,   // Try uniform block if single column (4) was poor
        'marriage_certificate' => 6, // Try uniform block if single column (4) was poor
        default => 6
    };

    try {
        Log::info('Starting multi-pass OCR second pass', [
            'primary_length' => strlen($primaryText),
            'alternate_psm' => $alternatePSM,
            'document_type' => $documentType
        ]);

        $ocr2 = new TesseractOCR($imagePath);
        if ($tesseractPath !== 'tesseract') {
            $ocr2->executable($tesseractPath);
        }
        $ocr2->psm($alternatePSM);
        $ocr2->oem(1);
        
        try {
            $ocr2->lang('eng+fil');
        } catch (\Exception $e) {
            $ocr2->lang('eng');
        }

        $allowedChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'
                      . 'abcdefghijklmnopqrstuvwxyz'
                      . '0123456789'
                      . '.,;:!?()-_/\\@#$%^&*+= \n\r\t'
                      . 'ÑñÁáÉéÍíÓóÚúÜü';
        $ocr2->allowlist($allowedChars);
        $ocr2->configVar('preserve_interword_spaces', '1');

        $secondText = $ocr2->run();
        $secondLetters = preg_match_all('/[A-Za-z]/', $secondText);

        Log::info('Multi-pass second pass completed', [
            'second_length' => strlen($secondText),
            'second_letters' => $secondLetters,
            'primary_letters' => $letterCount
        ]);

        // Choose the result with more alphabetical content (better OCR)
        if ($secondLetters > $letterCount * 1.2) {
            Log::info('Using second-pass OCR result (more text captured)');
            return $secondText;
        }

        // If both are similar, merge unique lines from the second pass into the primary
        if (!empty(trim($secondText)) && !empty(trim($primaryText))) {
            $mergedText = $this->mergeOCRResults($primaryText, $secondText);
            Log::info('Merged multi-pass OCR results', ['merged_length' => strlen($mergedText)]);
            return $mergedText;
        }

    } catch (\Exception $e) {
        Log::warning('Multi-pass OCR second pass failed, using primary result', [
            'error' => $e->getMessage()
        ]);
    }

    return $primaryText;
}

/**
 * Merge two OCR text results by combining unique lines.
 * Takes the primary as base, then appends any unique lines from the secondary.
 */
private function mergeOCRResults($primary, $secondary)
{
    $primaryLines = array_filter(array_map('trim', explode("\n", $primary)));
    $secondaryLines = array_filter(array_map('trim', explode("\n", $secondary)));
    
    // Use primary as base
    $merged = $primaryLines;
    $primaryLower = array_map('strtolower', $primaryLines);
    
    // Add unique lines from secondary that aren't already in primary
    foreach ($secondaryLines as $line) {
        if (strlen($line) < 3) continue;
        $lineLower = strtolower($line);
        
        // Check if this line (or something very similar) already exists
        $found = false;
        foreach ($primaryLower as $pLine) {
            if ($lineLower === $pLine || similar_text($lineLower, $pLine) > strlen($lineLower) * 0.8) {
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $merged[] = $line;
        }
    }
    
    return implode("\n", $merged);
}


/**
 * Basic image preprocessing fallback
 */
private function basicImagePreprocessing($imagePath, $processedPath)
{
    try {
        // Check if GD extension is loaded
        if (!extension_loaded('gd')) {
            Log::warning('GD extension not available, using original image');
            copy($imagePath, $processedPath);
            return $processedPath;
        }

        Log::info('Processing image with GD library');

        // Get image info
        $imageInfo = getimagesize($imagePath);
        if (!$imageInfo) {
            throw new \Exception('Invalid image file');
        }

        $imageType = $imageInfo[2];
        $originalWidth = $imageInfo[0];
        $originalHeight = $imageInfo[1];

        // Create image resource based on type
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($imagePath);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($imagePath);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($imagePath);
                break;
            default:
                throw new \Exception('Unsupported image type: ' . $imageType);
        }

        if (!$sourceImage) {
            throw new \Exception('Failed to create image resource');
        }

        // Calculate new dimensions (increase size for better OCR)
        $scaleFactor = 2.0; // Double the size
        $newWidth = (int)($originalWidth * $scaleFactor);
        $newHeight = (int)($originalHeight * $scaleFactor);

        // Ensure minimum dimensions for OCR
        if ($newHeight < 2000) {
            $scaleFactor = 2000 / $originalHeight;
            $newWidth = (int)($originalWidth * $scaleFactor);
            $newHeight = 2000;
        }

        // Create new image with white background
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        $white = imagecolorallocate($newImage, 255, 255, 255);
        imagefill($newImage, 0, 0, $white);

        // Resize image with high quality
        imagecopyresampled(
            $newImage, $sourceImage,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $originalWidth, $originalHeight
        );

        // Convert to grayscale for consistent OCR processing
        imagefilter($newImage, IMG_FILTER_GRAYSCALE);

        // Enhance contrast for better text recognition
        imagefilter($newImage, IMG_FILTER_CONTRAST, -40); // Higher contrast for certificates
        imagefilter($newImage, IMG_FILTER_BRIGHTNESS, 20); // Slight brightness increase

        // Apply sharpening for text clarity
        $sharpenMatrix = array(
            array(0, -1, 0),
            array(-1, 5, -1),
            array(0, -1, 0)
        );
        imageconvolution($newImage, $sharpenMatrix, 1, 0);

        // Save processed image
        $saved = imagejpeg($newImage, $processedPath, 95); // High quality JPEG

        // Clean up memory
        imagedestroy($sourceImage);
        imagedestroy($newImage);

        if (!$saved) {
            throw new \Exception('Failed to save processed image');
        }

        Log::info('GD image processing completed successfully', [
            'original_dimensions' => ['width' => $originalWidth, 'height' => $originalHeight],
            'new_dimensions' => ['width' => $newWidth, 'height' => $newHeight],
            'processed_path' => $processedPath,
            'file_size' => filesize($processedPath)
        ]);

        return $processedPath;

    } catch (\Exception $e) {
        Log::error('GD image processing failed', [
            'error' => $e->getMessage(),
            'input_path' => $imagePath
        ]);

        // Final fallback - just copy the original file
        if (copy($imagePath, $processedPath)) {
            Log::info('Using original image as fallback');
            return $processedPath;
        } else {
            throw new \Exception('Failed to process image and copy fallback failed');
        }
    }
}

/**
 * Extract text using Tesseract OCR
 */
private function extractTextWithTesseract($imagePath, $documentType)
{
    try {
        // Ensure proper path separators
        $imagePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $imagePath);
        
        Log::info('Starting enhanced Tesseract OCR for legal documents', [
            'image_path' => $imagePath,
            'file_exists' => file_exists($imagePath),
            'file_size' => file_exists($imagePath) ? filesize($imagePath) : 0,
            'document_type' => $documentType,
            'working_directory' => getcwd()
        ]);
        
        // Check if Tesseract OCR class exists
        if (!class_exists('thiagoalessio\TesseractOCR\TesseractOCR')) {
            return [
                'success' => false,
                'error' => 'Tesseract OCR library not found. Please install: composer require thiagoalessio/tesseract_ocr'
            ];
        }
        
        // Verify file exists
        if (!file_exists($imagePath)) {
            return [
                'success' => false,
                'error' => 'Image file not found: ' . $imagePath
            ];
        }

        // Find Tesseract executable
        $tesseractPath = $this->findTesseractExecutable();
        if (!$tesseractPath) {
            return [
                'success' => false,
                'error' => 'Tesseract OCR executable not found. Please ensure Tesseract is installed.'
            ];
        }

        // Store original working directory
        $originalCwd = getcwd();
        
        try {
            // Change to project root to ensure proper path resolution
            chdir(base_path());
            
            Log::info('Enhanced Tesseract OCR configuration', [
                'original_cwd' => $originalCwd,
                'new_cwd' => getcwd(),
                'tesseract_path' => $tesseractPath,
                'document_type' => $documentType
            ]);

            // Create OCR instance with absolute path
            $ocr = new TesseractOCR($imagePath);
            
            // Set executable path if not using PATH
            if ($tesseractPath !== 'tesseract') {
                $ocr->executable($tesseractPath);
            }
            
            // Enhanced OCR configuration for legal documents
            $ocr->lang('eng'); // Primary language: English
            
            // Try to add Filipino language support if available
            try {
                $ocr->lang('eng+fil'); // English + Filipino if available
                Log::info('Using English + Filipino language models');
            } catch (\Exception $e) {
                Log::info('Filipino language model not available, using English only');
            }
            
            // Enhanced OCR configuration for birth certificate structure
            if ($documentType === 'birth_certificate') {
                // Use PSM 6 for uniform block of text (best for forms)
                $ocr->psm(6);
                
                // Use best OCR Engine Mode for forms
                $ocr->oem(1); // Neural nets LSTM engine only (best accuracy)
                
                // Enhanced character allowlist for birth certificates with checkmarks
                $enhancedChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'
                              . '.,;:!?()-_/\\@#$%^&*+= \n\r'
                              . 'ÑñÁáÉéÍíÓóÚúÜü'  // Filipino characters
                              . 'xX√✓□■☐☑☒';      // Checkmarks and form elements
                
                $ocr->allowlist($enhancedChars);
                
                // Add custom configuration for birth certificates
                $ocr->configVar('tessedit_pageseg_mode', '6');
                $ocr->configVar('tessedit_char_whitelist', $enhancedChars);
                
                // Enhance detection of checkmarks and form elements
                $ocr->configVar('textord_tabfind_find_tables', '1');
                $ocr->configVar('textord_tablefind_good_neighbours', '1');
                $ocr->configVar('textord_heavy_nr', '1'); // Better table detection
                
                // Form-specific optimizations
                $ocr->configVar('classify_enable_learning', '0'); // Disable learning for consistency
                $ocr->configVar('classify_enable_adaptive_matcher', '1'); // Enable adaptive matching
                $ocr->configVar('textord_min_linesize', '1.5'); // Smaller minimum line size for forms
                $ocr->configVar('preserve_interword_spaces', '1'); // Preserve spaces
                $ocr->configVar('tessedit_create_boxfile', '0'); // Don't create box files
                
                // Additional form recognition settings
                $ocr->configVar('textord_tabfind_show_vlines', '0');
                $ocr->configVar('textord_use_cjk_fp_model', '0');
                $ocr->configVar('segment_penalty_dict_frequent_word', '1');
                $ocr->configVar('segment_penalty_dict_case_ok', '1');
                
                Log::info('Applied birth certificate specific OCR configuration', [
                    'psm_mode' => '6 (uniform block for forms)',
                    'oem_mode' => '1 (LSTM neural nets)',
                    'enhanced_chars_count' => strlen($enhancedChars),
                    'table_detection' => 'enabled',
                    'form_optimization' => 'enabled'
                ]);
                
            } elseif ($documentType === 'death_certificate' || $documentType === 'marriage_certificate') {
                // Enhanced form-based configuration for certificates
                $ocr->psm(6); // Uniform block of text - best for forms
                $ocr->oem(1); // Neural nets LSTM engine only (best accuracy)
                
                $allowedChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'
                            . '.,;:!?()-_/\\@#$%^&*+= \n\r'
                            . 'ÑñÁáÉéÍíÓóÚúÜü'; // Filipino characters
                
                $ocr->allowlist($allowedChars);
                $ocr->configVar('tessedit_char_whitelist', $allowedChars);
                
                // Enhanced configuration for marriage certificates
                if ($documentType === 'marriage_certificate') {
                    $ocr->configVar('textord_tabfind_find_tables', '1');
                    $ocr->configVar('textord_tablefind_good_neighbours', '1');
                    $ocr->configVar('preserve_interword_spaces', '1');
                    $ocr->configVar('classify_enable_adaptive_matcher', '1');
                    
                    Log::info('Applied marriage certificate specific OCR configuration');
                }
                
                Log::info('Applied certificate-specific OCR configuration', [
                    'document_type' => $documentType,
                    'psm_mode' => '6 (uniform block)'
                ]);
                
            } else {
                // Default configuration for other document types
                $ocr->psm(3); // Fully automatic page segmentation
                $ocr->oem(1); // Neural nets LSTM engine only (best accuracy)
                
                $allowedChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'
                              . '.,;:!?()-_/\\@#$%^&*+= \n\r'
                              . 'ÑñÁáÉéÍíÓóÚúÜü'; // Filipino characters
                
                $ocr->allowlist($allowedChars);
                
                Log::info('Applied default OCR configuration', [
                    'document_type' => $documentType,
                    'psm_mode' => '3 (auto segmentation)'
                ]);
            }
            
            // Additional Tesseract configurations for better accuracy (applied to all document types)
            $ocr->configFile('tesseract_legal'); // Custom config if available
            
            // Common settings for all documents
            $ocr->configVar('classify_enable_learning', '0'); // Disable learning for consistency
            $ocr->configVar('classify_enable_adaptive_matcher', '1'); // Enable adaptive matching
            $ocr->configVar('preserve_interword_spaces', '1'); // Preserve spaces

            Log::info('Enhanced Tesseract OCR configuration applied', [
                'executable' => $tesseractPath,
                'working_directory' => getcwd(),
                'image_path' => $imagePath,
                'document_type' => $documentType,
                'configuration_complete' => true
            ]);

            // Extract text with enhanced settings
            $rawText = $ocr->run();
            
        } finally {
            // Always restore original working directory
            chdir($originalCwd);
            Log::info('Working directory restored after OCR');
        }
        
        if (empty(trim($rawText))) {
            // Try fallback with different PSM settings
            Log::warning('No text extracted, trying fallback OCR settings');
            return $this->fallbackOCRProcessing($imagePath, $documentType);
        }

        Log::info('Enhanced Tesseract OCR completed successfully', [
            'text_length' => strlen($rawText),
            'line_count' => substr_count($rawText, "\n"),
            'preview' => substr(str_replace(["\n", "\r"], ' ', $rawText), 0, 150) . '...'
        ]);

        // Enhanced text cleaning and processing
        $cleanedText = $this->enhancedTextCleaning($rawText);
        
        // Extract specific fields with improved patterns
        $extractedFields = $this->enhancedFieldExtraction($cleanedText, $documentType);
        
        // Calculate enhanced confidence score
        $confidence = $this->enhancedConfidenceScore($cleanedText, $documentType, $extractedFields);
        
        return [
            'success' => true,
            'raw_text' => $rawText,
            'cleaned_text' => $cleanedText,
            'extracted_fields' => $extractedFields,
            'confidence' => $confidence,
            'word_count' => str_word_count($cleanedText),
            'line_count' => substr_count($cleanedText, "\n"),
            'document_type' => $documentType,
            'processing_time' => microtime(true),
            'quality_metrics' => [
                'text_length' => strlen($cleanedText),
                'field_extraction_success' => count(array_filter($extractedFields)) > 0,
                'contains_names' => $this->containsValidNames($cleanedText),
                'ocr_method' => 'enhanced_' . $documentType
            ]
        ];

    } catch (\Exception $e) {
        Log::error('Enhanced Tesseract OCR exception', [
            'error_message' => $e->getMessage(),
            'image_path' => $imagePath ?? 'unknown',
            'document_type' => $documentType,
            'stack_trace' => $e->getTraceAsString()
        ]);
        
        return [
            'success' => false,
            'error' => 'Enhanced OCR processing failed: ' . $e->getMessage()
        ];
    }
}

private function fallbackOCRProcessing($imagePath, $documentType)
{
    Log::info('Starting fallback OCR processing');

    try {
        $tesseractPath = $this->findTesseractExecutable();
        
        // Try different PSM modes
        $psmModes = [3, 8, 4, 6, 11, 12];
        
        foreach ($psmModes as $psm) {
            try {
                $ocr = new TesseractOCR($imagePath);
                
                if ($tesseractPath !== 'tesseract') {
                    $ocr->executable($tesseractPath);
                }
                
                $ocr->lang('eng')
                    ->psm($psm)
                    ->oem(1);
                
                $text = $ocr->run();
                
                if (!empty(trim($text))) {
                    Log::info('Fallback OCR succeeded', ['psm_mode' => $psm]);
                    
                    $cleanedText = $this->enhancedTextCleaning($text);
                    $extractedFields = $this->enhancedFieldExtraction($cleanedText, $documentType, $text);
                    $wordCount = str_word_count($cleanedText);

                    $confidence = $this->calculateConfidenceScore($cleanedText, $extractedFields, $documentType);
                    if ($confidence <= 0 && $wordCount > 0) {
                        $confidence = $this->estimateTesseractConfidence($wordCount, $extractedFields);
                    }
                    
                    return [
                        'success' => true,
                        'raw_text' => $text,
                        'cleaned_text' => $cleanedText,
                        'extracted_fields' => $extractedFields,
                        'confidence' => $confidence,
                        'word_count' => $wordCount,
                        'document_type' => $documentType,
                        'processing_method' => 'tesseract_fallback_psm',
                        'psm_used' => $psm
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('Fallback OCR attempt failed', ['psm' => $psm, 'error' => $e->getMessage()]);
                continue;
            }
        }
        
        return [
            'success' => false,
            'error' => 'All OCR processing attempts failed'
        ];
        
    } catch (\Exception $e) {
        return [
            'success' => false,
            'error' => 'Fallback OCR processing failed: ' . $e->getMessage()
        ];
    }
}

private function estimateTesseractConfidence(int $wordCount, array $extractedFields): int
{
    $filledFields = count(array_filter($extractedFields, function ($value) {
        if (is_array($value)) {
            return !empty($value);
        }

        return trim((string) $value) !== '';
    }));

    $wordScore = min(55, max(10, (int) round($wordCount * 1.2)));
    $fieldScore = min(35, $filledFields * 5);

    return max(1, min(95, $wordScore + $fieldScore));
}

private function enhancedConfidenceScore($text, $documentType, $extractedFields)
{
    $score = 0;
    $maxScore = 100;
    
    // Base score from text length and quality (30 points)
    $textLength = strlen($text);
    if ($textLength > 50) $score += 10;
    if ($textLength > 200) $score += 10;
    if ($textLength > 500) $score += 10;
    
    // Field extraction success (40 points)
    $filledFields = count(array_filter($extractedFields));
    $totalFields = count($extractedFields);
    
    if ($totalFields > 0) {
        $fieldScore = ($filledFields / $totalFields) * 40;
        $score += $fieldScore;
    }
    
    // Document type keywords (20 points)
    $keywords = [];
    switch ($documentType) {
        case 'birth_certificate':
            $keywords = ['birth', 'certificate', 'republic', 'philippines', 'civil', 'registry', 'child', 'mother', 'father'];
            break;
        case 'death_certificate':
            $keywords = ['death', 'certificate', 'deceased', 'cause'];
            break;
        case 'marriage_certificate':
            $keywords = ['marriage', 'certificate', 'bride', 'groom', 'wedding'];
            break;
        default:
            $keywords = ['certificate', 'document', 'official'];
    }
    
    $foundKeywords = 0;
    foreach ($keywords as $keyword) {
        if (stripos($text, $keyword) !== false) {
            $foundKeywords++;
        }
    }
    
    if (count($keywords) > 0) {
        $score += ($foundKeywords / count($keywords)) * 20;
    }
    
    // Text structure and quality (10 points)
    if (preg_match('/:\s*[A-Z]/', $text)) $score += 3; // Field labels with values
    if (preg_match('/\d{1,2}[\s\/-]\d{1,2}[\s\/-]\d{4}/', $text)) $score += 3; // Date format
    if ($this->containsValidNames($text)) $score += 4; // Contains valid names
    
    $finalScore = min(round($score), $maxScore);
    
    Log::info('Enhanced confidence score calculated', [
        'final_score' => $finalScore,
        'text_length_score' => min(30, ($textLength > 500 ? 30 : ($textLength > 200 ? 20 : ($textLength > 50 ? 10 : 0)))),
        'field_extraction_score' => $totalFields > 0 ? round(($filledFields / $totalFields) * 40) : 0,
        'keyword_score' => count($keywords) > 0 ? round(($foundKeywords / count($keywords)) * 20) : 0,
        'structure_score' => min(10, 3 + 3 + 4)
    ]);
    
    return $finalScore;
}



private function enhancedTextCleaning($text)
{
    Log::info('Starting enhanced text cleaning (line-preserving, digits-safe)');

    // 1) Preserve line structure; normalize line endings
    $text = str_replace(["\r\n", "\r"], "\n", $text);

    // 2) Collapse spaces/tabs per line but keep newlines
    $lines = explode("\n", $text);
    $lines = array_map(function ($l) {
        // Keep multiple spaces of 2+ so we can detect columns later
        $l = preg_replace('/[ \t]+/', ' ', $l); // single spaces within tokens
        return trim($l);
    }, $lines);
    // Remove empty trash lines only if there are long runs of empties
    $text = implode("\n", array_filter($lines, fn($l) => $l !== ''));

    // 3) Fix a few OCR artifacts WITHOUT touching digits
    $replacements = [
        '/\|/' => 'I',
        // keep exclamation mark as punctuation; do not convert '1' or '0'
    ];
    foreach ($replacements as $pattern => $replacement) {
        $text = preg_replace($pattern, $replacement, $text);
    }

    // 4) Normalize punctuation spacing
    $text = preg_replace('/\s*:\s*/', ': ', $text);
    $text = preg_replace('/\s*,\s*/', ', ', $text);
    $text = preg_replace('/\s*\.\s*/', '. ', $text);

    $text = preg_replace('/[^\PC\s]/u', '', $text);

    $text = $this->fixFilipinoNamePatterns($text);
    $text = $this->reconstructBrokenWords($text);

    Log::info('Text cleaning completed (lines preserved)', ['cleaned_length' => strlen($text)]);
    return $text;
}


private function fixFilipinoNamePatterns($text)
{
    $namePatterns = [
        // Common Filipino surnames
        '/\bDe\s+La\s+Cruz\b/i' => 'De La Cruz',
        '/\bDel\s+Rosario\b/i' => 'Del Rosario',
        '/\bSan\s+Jose\b/i' => 'San Jose',
        '/\bSanta\s+Maria\b/i' => 'Santa Maria',
        
        // Common first names
        '/\bMaria\s+([A-Z][a-z]+)\b/' => 'Maria $1',
        '/\bJose\s+([A-Z][a-z]+)\b/' => 'Jose $1',
        '/\bJuan\s+([A-Z][a-z]+)\b/' => 'Juan $1',
        
        // Fix common OCR errors in names
        '/\b([A-Z][a-z]+)0([A-Z][a-z]+)\b/' => '$1O$2', // 0 -> O
        '/\b([A-Z][a-z]+)1([A-Z][a-z]+)\b/' => '$1I$2', // 1 -> I
        '/\b([A-Z])3([A-Z])/' => '$1E$2', // 3 -> E
    ];

    foreach ($namePatterns as $pattern => $replacement) {
        $text = preg_replace($pattern, $replacement, $text);
    }

    return $text;
}

private function enhancedFieldExtraction($text, $documentType, $rawText = null, array $boxes = [])
{
    Log::info('Starting enhanced field extraction', [
        'document_type' => $documentType,
        'text_length' => strlen($text),
        'boxes_count' => count($boxes),
    ]);
    
    $fields = [];
    
    try {
        switch ($documentType) {
            case 'birth_certificate':
                $fields = $this->extractBirthCertificateFields($text, $rawText ?: $text);
                // Spatial post-processing: fix/fill fields using bounding-box data
                if (!empty($boxes)) {
                    $fields = $this->spatialEnhanceBirthFields($fields, $boxes);
                }
                break;
            case 'death_certificate':
                $fields = $this->extractDeathCertificateFields($text, $rawText ?: $text);
                // Spatial post-processing for death certificate fields
                if (!empty($boxes)) {
                    $fields = $this->spatialEnhanceDeathFields($fields, $boxes);
                }
                break;
            case 'marriage_certificate':
                // Use spatial extraction when bounding boxes are available
                if (!empty($boxes)) {
                    $fields = $this->extractMarriageCertificateFieldsSpatial($text, $boxes);
                } else {
                    $fields = $this->extractMarriageCertificateFields($text);
                }
                break;
            default:
                $fields = $this->extractGenericFields($text);
        }
        
        // Additional post-processing for all document types
        $fields = $this->postProcessExtractedFields($fields, $documentType);
        
        Log::info('Enhanced field extraction completed', [
            'document_type' => $documentType,
            'total_fields' => count($fields),
            'filled_fields' => count(array_filter($fields))
        ]);
        
    } catch (\Exception $e) {
        Log::error('Enhanced field extraction failed', [
            'error' => $e->getMessage(),
            'document_type' => $documentType
        ]);
    }
    
    return $fields;
}

/**
 * Post-process extracted fields for quality improvement
 */
private function postProcessExtractedFields($fields, $documentType)
{
    foreach ($fields as $key => $value) {
        if (is_string($value) && !empty($value)) {
            // Clean up field values
            $value = trim($value);
            
            // Apply name-specific cleaning for name fields
            if (strpos($key, 'name') !== false) {
                $value = $this->cleanNameField($value);
            }
            
            // Apply date-specific cleaning for date fields
            if (strpos($key, 'date') !== false || $key === 'birthdate') {
                $value = $this->cleanDateField($value);
            }
            
            // Apply place-specific cleaning for place fields
            if (strpos($key, 'place') !== false) {
                $value = $this->cleanPlaceField($value);
            }
            
            $fields[$key] = $value;
        }
    }
    
    return $fields;
}

/**
 * Clean name field values
 */
private function cleanNameField($name)
{
    if (!$name) return '';
    
    // Preserve initials: "A.", "C.", single letter => keep as-is with period
    $trimmed = trim($name);
    if (preg_match('/^[A-Za-z]{1,2}\.?$/', $trimmed)) {
        return strtoupper(rtrim($trimmed, '.')) . '.';
    }
    
    // Remove extra whitespace and clean up
    $cleaned = preg_replace('/\s+/', ' ', $trimmed);
    
    // Remove leading/trailing punctuation and numbers
    $cleaned = preg_replace('/^[^A-Za-z]+|[^A-Za-z]+$/', '', $cleaned);
    
    // Fix common OCR errors
    $ocrFixes = [
        '/^[0-9]/' => '', // Remove leading numbers
        '/[0-9]$/' => '', // Remove trailing numbers
        '/\b[A-Z]{1}\b/' => '', // Remove single letters
        '/\s+/' => ' ' // Normalize spaces
    ];
    
    foreach ($ocrFixes as $pattern => $replacement) {
        $cleaned = preg_replace($pattern, $replacement, $cleaned);
    }
    
    $cleaned = trim($cleaned);
    
    // Only capitalize if it looks like a valid name
    if (strlen($cleaned) > 1 && preg_match('/^[A-Za-z\s]+$/', $cleaned)) {
        $cleaned = ucwords(strtolower($cleaned));
    }
    
    return $cleaned;
}

/**
 * Clean date field values
 */
private function cleanDateField($date)
{
    // Remove extra spaces and normalize separators
    $date = preg_replace('/\s+/', ' ', $date);
    $date = preg_replace('/[\s\-\/]+/', '/', $date);
    
    // Try to parse and reformat date
    try {
        $parsed = \DateTime::createFromFormat('m/d/Y', $date);
        if ($parsed) {
            return $parsed->format('m/d/Y');
        }
        
        $parsed = \DateTime::createFromFormat('d/m/Y', $date);
        if ($parsed) {
            return $parsed->format('d/m/Y');
        }
    } catch (\Exception $e) {
        // Return original if parsing fails
    }
    
    return trim($date);
}

/**
 * Clean place field values
 */
private function cleanPlaceField($place)
{
    // Remove common OCR artifacts
    $place = preg_replace('/[^\w\s\.\-\,]/u', '', $place);
    
    // Fix capitalization for place names
    $place = ucwords(strtolower($place));
    
    return trim($place);
}



private function reconstructBrokenWords($text)
{
    $reconstructions = [
        '/\bCert\s+ific\s+ate\b/i' => 'Certificate',
        '/\bRep\s+ub\s+lic\b/i' => 'Republic',
        '/\bPhil\s+ip\s+pines\b/i' => 'Philippines',
        '/\bReg\s+is\s+trar\b/i' => 'Registrar',
        '/\bCiv\s+il\b/i' => 'Civil',
        '/\bReg\s+is\s+try\b/i' => 'Registry',
        '/\bOff\s+ice\b/i' => 'Office',
        '/\bDate\s+of\s+Birth\b/i' => 'Date of Birth',
        '/\bPlace\s+of\s+Birth\b/i' => 'Place of Birth',
        '/\bFull\s+Name\b/i' => 'Full Name',
        '/\bMoth\s+er\b/i' => 'Mother',
        '/\bFath\s+er\b/i' => 'Father',
    ];

    foreach ($reconstructions as $pattern => $replacement) {
        $text = preg_replace($pattern, $replacement, $text);
    }

    return $text;
}


private function findTesseractExecutable()
{
    $possiblePaths = [
        'C:\Program Files\Tesseract-OCR\tesseract.exe',
        'C:\Program Files (x86)\Tesseract-OCR\tesseract.exe',
        'tesseract' // If in PATH
    ];
    
    foreach ($possiblePaths as $path) {
        if ($path === 'tesseract') {
            $output = [];
            $returnCode = 0;
            exec('tesseract --version 2>nul', $output, $returnCode);
            if ($returnCode === 0) {
                return $path;
            }
        } elseif (file_exists($path)) {
            return $path;
        }
    }
    
    return null;
}

/**
 * Clean extracted text
 */
private function cleanExtractedText($text)
{
    // Remove extra whitespace and normalize
    $text = preg_replace('/\s+/', ' ', $text);
    $text = trim($text);
    
    // Fix common OCR errors for legal documents
    $replacements = [
        '/\bBirth\s+Certificate\b/i' => 'Birth Certificate',
        '/\bDeath\s+Certificate\b/i' => 'Death Certificate',
        '/\bMarriage\s+Certificate\b/i' => 'Marriage Certificate',
        '/\bRepublic\s+of\s+the\s+Philippines\b/i' => 'Republic of the Philippines',
        '/\bCivil\s+Registry\s+Office\b/i' => 'Civil Registry Office',
        '/\bDate\s+of\s+Birth\b/i' => 'Date of Birth',
        '/\bPlace\s+of\s+Birth\b/i' => 'Place of Birth',
        '/\bFull\s+Name\b/i' => 'Full Name',
    ];

    foreach ($replacements as $pattern => $replacement) {
        $text = preg_replace($pattern, $replacement, $text);
    }

    return $text;
}

/**
 * Extract specific fields from legal documents
 */
private function extractDocumentFields($text, $documentType)
{
    $fields = [];
    
    switch ($documentType) {
        case 'birth_certificate':
            $fields = $this->extractBirthCertificateFields($text, $text);
            break;
        case 'death_certificate':
            $fields = $this->extractDeathCertificateFields($text, $text);
            break;
        case 'marriage_certificate':
            $fields = $this->extractMarriageCertificateFields($text);
            break;
        default:
            $fields = $this->extractGenericFields($text);
    }
    
    return $fields;
}

/**
 * Extract birth certificate specific fields
 */
private function extractBirthCertificateFields($cleanedText, $rawText)
{
    Log::info('Starting document-aligned field extraction');

    $fields = [
        // 1. NAME Section
        'name_first' => '',
        'name_middle' => '',
        'name_last' => '',
        
        // 2. SEX Section
        'sex' => '',
        
        // 3. DATE OF BIRTH Section
        'birth_date_day' => '',
        'birth_date_month' => '',
        'birth_date_year' => '',
        
        // 4. PLACE OF BIRTH Section
        'birth_place_institution' => '',
        'birth_place_city' => '',
        'birth_place_province' => '',
        
        // 5. MOTHER'S MAIDEN NAME Section
        'mother_first_name' => '',
        'mother_middle_name' => '',
        'mother_last_name' => '',
        
        // 6. FATHER'S NAME Section
        'father_first_name' => '',
        'father_middle_name' => '',
        'father_last_name' => '',
        
        // Registry Information
        'registry_number' => '',
        'bren_number' => '',
        'citizenship' => '',
        'religion' => ''
    ];

    // Extract sections in order based on document structure
    $this->extractSection1_Name($cleanedText, $rawText, $fields);
    $this->extractSection2_Sex($cleanedText, $rawText, $fields);
    $this->extractSection3_DateOfBirth($cleanedText, $rawText, $fields);
    $this->extractSection4_PlaceOfBirth($cleanedText, $rawText, $fields);
    $this->extractSection5_MotherName($cleanedText, $rawText, $fields);
    $this->extractSection6_FatherName($cleanedText, $rawText, $fields);
    $this->extractRegistryInformation($cleanedText, $rawText, $fields);

    Log::info('Document-aligned field extraction completed', [
        'filled_fields' => count(array_filter($fields)),
        'total_fields' => count($fields)
    ]);

    return $fields;
}

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * DEBUG MODE: Name Field Extraction Only
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * This method focuses ONLY on extracting name fields for debugging.
 * It saves both RAW and ENHANCED crops so you can see:
 * 1. What region is being captured (raw crop)
 * 2. How enhancement affects readability (enhanced crop)
 * 
 * After viewing images, tell me:
 * - "Move DOWN" if you see headers/labels
 * - "Move UP" if you see the next section
 * - "Move LEFT/RIGHT" if wrong column
 * - "Make WIDER/TALLER" if text is cut off
 */
public function debugNameExtraction(Request $request)
{
    // Check if GD or Imagick is available
    $hasGD = extension_loaded('gd');
    $hasImagick = extension_loaded('imagick');
    
    Log::info('Image extension check', [
        'gd_loaded' => $hasGD,
        'imagick_loaded' => $hasImagick
    ]);
    
    if (!$hasGD && !$hasImagick) {
        return response()->json([
            'success' => false,
            'message' => 'Neither GD nor Imagick PHP extension is available.',
            'debug_info' => [
                'gd_loaded' => $hasGD,
                'imagick_loaded' => $hasImagick,
                'php_version' => PHP_VERSION,
                'loaded_extensions' => get_loaded_extensions()
            ],
            'solution' => 'Enable GD in php.ini: Remove semicolon from ;extension=gd and restart web server'
        ], 500);
    }

    $validator = Validator::make($request->all(), [
        'file' => 'required|file|mimes:jpg,jpeg,png|max:10240'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 422);
    }

    try {
        $file = $request->file('file');
        
        Log::info('=== DEBUG NAME EXTRACTION STARTED ===', [
            'filename' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'using_driver' => $hasGD ? 'GD' : 'Imagick'
        ]);

        // Save uploaded file temporarily
        $tempDir = storage_path('app/temp/debug');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        $tempFilename = 'debug_source_' . time() . '.' . $file->getClientOriginalExtension();
        $tempPath = $tempDir . DIRECTORY_SEPARATOR . $tempFilename;
        $file->move($tempDir, $tempFilename);

        // Ensure debug output directory exists
        $debugOutputDir = public_path('storage/debug');
        if (!file_exists($debugOutputDir)) {
            mkdir($debugOutputDir, 0755, true);
        }

        // Clean old debug files
        $this->cleanOldDebugFiles($debugOutputDir);

        // Run the name field debug extraction
        $result = $this->extractNameFieldsDebug($tempPath, $debugOutputDir);

        // Clean up source temp file
        if (file_exists($tempPath)) {
            unlink($tempPath);
        }

        return response()->json($result);

    } catch (\Exception $e) {
        Log::error('Debug name extraction failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Debug extraction failed: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Extract and debug ONLY name fields
 */
private function extractNameFieldsDebug(string $imagePath, string $debugOutputDir): array
{
    Log::info('Starting focused name field debug extraction', [
        'image_path' => $imagePath,
        'output_dir' => $debugOutputDir
    ]);

    // Load the original image
    $manager = new ImageManager(new Driver());
    $originalImage = $manager->read($imagePath);
    
    $imageWidth = $originalImage->width();
    $imageHeight = $originalImage->height();
    
    Log::info('Source image loaded', [
        'width' => $imageWidth,
        'height' => $imageHeight,
        'aspect_ratio' => round($imageWidth / $imageHeight, 3)
    ]);

    $timestamp = time();
    $debugResults = [];

    // ═══════════════════════════════════════════════════════════════
    // RECALIBRATED COORDINATES BASED ON YOUR DEBUG IMAGES
    // 
    // Your document structure (from the image):
    // - "1. NAME" label row is around Y = 17-18%
    // - Actual name DATA row is around Y = 19-21%
    // - Name fields: ANTONIO, JR. | TAHIT | SANQUENZA
    // ═══════════════════════════════════════════════════════════════
    
    $nameCoordinates = [
        'name_first' => [
            'x' => 0.28,       
            'y' => 0.195,      
            'width' => 0.14,   
            'height' => 0.025
        ],
        'name_middle' => [
            // NEW coordinates - shifted RIGHT to capture TAHIT
            // Positioned between First Name (ends ~0.42) and Last Name (starts ~0.44)
            'x' => 0.37,      // Start after First Name
            'y' => 0.195,      // Same row
            'width' => 0.15,   // Narrow - middle names are usually short
            'height' => 0.025
        ],
        'name_last' => [
            // KEEP AS-IS - correctly captured SANQUENZA
            'x' => 0.44,       
            'y' => 0.195,      
            'width' => 0.18,   
            'height' => 0.025
        ]
    ];

    // Also save full image with overlay for reference
    $overlayResult = $this->createNameFieldOverlay($imagePath, $nameCoordinates, $debugOutputDir, $timestamp);

    foreach ($nameCoordinates as $fieldName => $coords) {
        Log::info("Processing field: {$fieldName}", ['coordinates' => $coords]);

        try {
            // Calculate absolute pixel coordinates
            $x = (int)($coords['x'] * $imageWidth);
            $y = (int)($coords['y'] * $imageHeight);
            $width = (int)($coords['width'] * $imageWidth);
            $height = (int)($coords['height'] * $imageHeight);

            // Ensure bounds
            $x = max(0, min($x, $imageWidth - 10));
            $y = max(0, min($y, $imageHeight - 10));
            $width = min($width, $imageWidth - $x);
            $height = min($height, $imageHeight - $y);

            Log::info("Calculated pixels for {$fieldName}", [
                'x' => $x, 'y' => $y, 'width' => $width, 'height' => $height
            ]);

            // Skip if too small
            if ($width < 20 || $height < 10) {
                $debugResults[$fieldName] = [
                    'error' => 'Region too small',
                    'dimensions' => ['width' => $width, 'height' => $height]
                ];
                continue;
            }

            // ═══════════════════════════════════════════════════════════════
            // STEP 1: Create RAW crop (no enhancement)
            // ═══════════════════════════════════════════════════════════════
            
            // IMPORTANT: Create fresh copy from original for each crop
            $rawImage = $manager->read($imagePath);
            $rawImage->crop($width, $height, $x, $y);
            
            // Scale up for better visibility (2x)
            $rawImage->scale(width: $width * 2);
            
            $rawFilename = "debug_{$fieldName}_raw_{$timestamp}.jpg";
            $rawPath = $debugOutputDir . DIRECTORY_SEPARATOR . $rawFilename;
            $rawImage->save($rawPath, quality: 95);
            
            Log::info("Saved RAW crop: {$rawFilename}", ['path' => $rawPath]);

            // ═══════════════════════════════════════════════════════════════
            // STEP 2: Create ENHANCED crop (grayscale + contrast)
            // ═══════════════════════════════════════════════════════════════
            
            // Fresh copy for enhanced version
            $enhancedImage = $manager->read($imagePath);
            $enhancedImage->crop($width, $height, $x, $y);
            
            // Scale up BEFORE enhancement (better quality)
            $enhancedImage->scale(width: $width * 3);
            
            // Apply PSA security pattern removal filters
            $enhancedImage->greyscale();
            $enhancedImage->contrast(55);
            $enhancedImage->brightness(20);
            $enhancedImage->sharpen(15);
            $enhancedImage->contrast(40);
            
            $enhancedFilename = "debug_{$fieldName}_enhanced_{$timestamp}.jpg";
            $enhancedPath = $debugOutputDir . DIRECTORY_SEPARATOR . $enhancedFilename;
            $enhancedImage->save($enhancedPath, quality: 95);
            
            Log::info("Saved ENHANCED crop: {$enhancedFilename}");

            // ═══════════════════════════════════════════════════════════════
            // STEP 3: Create THRESHOLD version (pure black/white)
            // ═══════════════════════════════════════════════════════════════
            
            $thresholdImage = $manager->read($imagePath);
            $thresholdImage->crop($width, $height, $x, $y);
            $thresholdImage->scale(width: $width * 3);
            $thresholdImage->greyscale();
            $thresholdImage->contrast(70);
            $thresholdImage->brightness(25);
            
            $thresholdFilename = "debug_{$fieldName}_threshold_{$timestamp}.jpg";
            $thresholdPath = $debugOutputDir . DIRECTORY_SEPARATOR . $thresholdFilename;
            $thresholdImage->save($thresholdPath, quality: 95);
            
            Log::info("Saved THRESHOLD crop: {$thresholdFilename}");

            // ═══════════════════════════════════════════════════════════════
            // STEP 4: Run OCR on enhanced version
            // ═══════════════════════════════════════════════════════════════
            
            $ocrText = $this->runDebugOCR($enhancedPath, $fieldName);

            // Store results
            $debugResults[$fieldName] = [
                'raw_image' => [
                    'url' => asset('storage/debug/' . $rawFilename),
                    'filename' => $rawFilename
                ],
                'enhanced_image' => [
                    'url' => asset('storage/debug/' . $enhancedFilename),
                    'filename' => $enhancedFilename
                ],
                'threshold_image' => [
                    'url' => asset('storage/debug/' . $thresholdFilename),
                    'filename' => $thresholdFilename
                ],
                'coordinates' => [
                    'x_percent' => ($coords['x'] * 100) . '%',
                    'y_percent' => ($coords['y'] * 100) . '%',
                    'width_percent' => ($coords['width'] * 100) . '%',
                    'height_percent' => ($coords['height'] * 100) . '%',
                    'x_pixels' => $x,
                    'y_pixels' => $y,
                    'width_pixels' => $width,
                    'height_pixels' => $height
                ],
                'ocr_result' => $ocrText
            ];

            Log::info("Completed processing {$fieldName}", [
                'ocr_text' => $ocrText['text'] ?? 'none'
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to process {$fieldName}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $debugResults[$fieldName] = [
                'error' => $e->getMessage()
            ];
        }
    }

    return [
        'success' => true,
        'message' => 'Debug extraction complete with recalibrated coordinates.',
        'image_dimensions' => [
            'width' => $imageWidth,
            'height' => $imageHeight
        ],
        'overlay_image' => $overlayResult,
        'name_fields' => $debugResults,
        'current_coordinates' => $nameCoordinates,
        'notes' => [
            'Y moved from 0.175 to 0.195 to capture DATA row instead of LABEL row',
            'Widths tightened to avoid capturing adjacent columns',
            'Height reduced to 0.025 for single-line text'
        ]
    ];
}

/**
 * Create overlay image showing name field regions on full document
 */
private function createNameFieldOverlay(string $imagePath, array $coordinates, string $debugOutputDir, int $timestamp): ?array
{
    try {
        $manager = new ImageManager(new Driver());
        $image = $manager->read($imagePath);
        
        $width = $image->width();
        $height = $image->height();

        // Colors for different fields
        $colors = [
            'name_first' => 'ff0000',   // Red
            'name_middle' => '00ff00',  // Green
            'name_last' => '0000ff'     // Blue
        ];

        // Draw rectangles for each name field
        foreach ($coordinates as $fieldName => $coords) {
            $x = (int)($coords['x'] * $width);
            $y = (int)($coords['y'] * $height);
            $w = (int)($coords['width'] * $width);
            $h = (int)($coords['height'] * $height);
            
            $color = $colors[$fieldName] ?? 'ffff00';

            // Draw rectangle border manually using lines
            // Top line
            $image->drawLine(function ($line) use ($x, $y, $w, $color) {
                $line->from($x, $y);
                $line->to($x + $w, $y);
                $line->color($color);
                $line->width(3);
            });
            
            // Bottom line
            $image->drawLine(function ($line) use ($x, $y, $w, $h, $color) {
                $line->from($x, $y + $h);
                $line->to($x + $w, $y + $h);
                $line->color($color);
                $line->width(3);
            });
            
            // Left line
            $image->drawLine(function ($line) use ($x, $y, $h, $color) {
                $line->from($x, $y);
                $line->to($x, $y + $h);
                $line->color($color);
                $line->width(3);
            });
            
            // Right line
            $image->drawLine(function ($line) use ($x, $y, $w, $h, $color) {
                $line->from($x + $w, $y);
                $line->to($x + $w, $y + $h);
                $line->color($color);
                $line->width(3);
            });

            Log::info("Drew overlay for {$fieldName}", [
                'x' => $x, 'y' => $y, 'w' => $w, 'h' => $h
            ]);
        }

        // Save overlay
        $overlayFilename = "debug_name_overlay_{$timestamp}.jpg";
        $overlayPath = $debugOutputDir . DIRECTORY_SEPARATOR . $overlayFilename;
        $image->save($overlayPath, quality: 90);

        Log::info("Saved overlay image", ['path' => $overlayPath]);

        return [
            'url' => asset('storage/debug/' . $overlayFilename),
            'filename' => $overlayFilename,
            'legend' => [
                'RED box' => 'First Name region',
                'GREEN box' => 'Middle Name region',
                'BLUE box' => 'Last Name region'
            ]
        ];

    } catch (\Exception $e) {
        Log::error('Failed to create overlay', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return null;
    }
}

/**
 * Apply binary threshold to image (convert to pure black/white)
 */
private function applyBinaryThreshold($image, int $threshold = 140): void
{
    $width = $image->width();
    $height = $image->height();
    
    // Process each pixel
    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            try {
                $color = $image->pickColor($x, $y);
                $gray = $color->red()->toInt(); // Already grayscale, so R=G=B
                
                // Below threshold = black (text), above = white (background)
                if ($gray < $threshold) {
                    $image->drawPixel($x, $y, 'black');
                } else {
                    $image->drawPixel($x, $y, 'white');
                }
            } catch (\Exception $e) {
                // Skip pixel on error
                continue;
            }
        }
    }
}

/**
 * Run OCR on debug image for comparison
 */
private function runDebugOCR(string $imagePath, string $fieldName): array
{
    try {
        $tesseractPath = $this->findTesseractExecutable();
        
        if (!$tesseractPath) {
            return [
                'success' => false,
                'text' => '',
                'error' => 'Tesseract not found'
            ];
        }

        $ocr = new TesseractOCR($imagePath);
        
        if ($tesseractPath !== 'tesseract') {
            $ocr->executable($tesseractPath);
        }

        // PSM 7 = Single text line (critical for field extraction)
        $ocr->psm(7);
        $ocr->oem(1);
        $ocr->lang('eng');
        
        // Name field specific allowlist
        $ocr->allowlist('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz .-\'ÑñIII');
        
        $ocr->configVar('preserve_interword_spaces', '1');

        $text = trim($ocr->run());

        Log::info("OCR result for {$fieldName}", [
            'text' => $text,
            'length' => strlen($text)
        ]);

        return [
            'success' => true,
            'text' => $text,
            'cleaned' => $this->cleanNameFieldValue($text)
        ];

    } catch (\Exception $e) {
        Log::warning("OCR failed for {$fieldName}", ['error' => $e->getMessage()]);
        
        return [
            'success' => false,
            'text' => '',
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Clean name field value
 */
private function cleanNameFieldValue(string $value): string
{
    if (empty($value)) return '';
    
    // Remove extra whitespace
    $cleaned = preg_replace('/\s+/', ' ', trim($value));
    
    // Remove numbers and special characters (except valid name chars)
    $cleaned = preg_replace('/[^A-Za-z\s\-\'\.Ññ]/', '', $cleaned);
    
    // Remove common OCR artifacts
    $cleaned = preg_replace('/^[^A-Za-z]+/', '', $cleaned);
    $cleaned = preg_replace('/[^A-Za-z]+$/', '', $cleaned);
    
    // Title case if looks like a name
    if (strlen($cleaned) > 1 && preg_match('/^[A-Za-z\s\-\']+$/', $cleaned)) {
        $cleaned = ucwords(strtolower($cleaned));
    }
    
    return trim($cleaned);
}

/**
 * Clean old debug files (older than 30 minutes)
 */
private function cleanOldDebugFiles(string $debugDir): void
{
    try {
        $files = glob($debugDir . '/debug_*.jpg');
        $cutoffTime = time() - 1800; // 30 minutes

        $deleted = 0;
        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
                $deleted++;
            }
        }
        
        if ($deleted > 0) {
            Log::info("Cleaned {$deleted} old debug files");
        }
    } catch (\Exception $e) {
        Log::warning('Failed to clean debug files', ['error' => $e->getMessage()]);
    }
}

private function extractSection1_Name($cleanedText, $rawText, &$fields)
{
    Log::info('=== SECTION 1: NAME EXTRACTION (TEXT-BASED ONLY) ===');
    
    // Log the OCR text for debugging
    Log::info('Raw OCR text sample', [
        'raw_first_1000_chars' => substr($rawText, 0, 1000)
    ]);
    
    // Use text-based extraction ONLY - no coordinate-based fallback
    $success = $this->extractNamesFromOCRText($cleanedText, $rawText, $fields);
    
    if ($success) {
        Log::info('NAME extraction successful', [
            'first' => $fields['name_first'],
            'middle' => $fields['name_middle'],
            'last' => $fields['name_last']
        ]);
    } else {
        Log::warning('NAME extraction failed - no valid names found in OCR text');
    }
}

/**
 * Extract names using pure text-based approach (like SEX extraction)
 * This method searches the OCR'd text for name patterns near the "1. NAME" section
 */
private function extractNamesFromOCRText($cleanedText, $rawText, &$fields): bool
{
    Log::info('Starting text-based NAME extraction');
    
    // Log OCR text for debugging
    Log::info('OCR Text for NAME extraction (first 2000 chars)', [
        'raw_text' => substr($rawText, 0, 2000)
    ]);
    
    // Combine both text sources
    $fullText = $rawText . "\n" . $cleanedText;
    
    // ═══════════════════════════════════════════════════════════════════════
    // STEP 1: LOCATE THE NAME SECTION (Multiple patterns for robustness)
    // ═══════════════════════════════════════════════════════════════════════
    
    $nameSectionText = '';
    $sectionFound = false;
    
    // Try multiple patterns to find NAME section
    $sectionPatterns = [
        '/1[\.\s]*NAME\b([\s\S]*?)(?=2[\.\s]*SEX\b|$)/i',
        '/NAME[\s\r\n]+\(First\)([\s\S]*?)(?=SEX|$)/i',
        '/\bNAME\b[^\n]*\n([\s\S]*?)(?=\bSEX\b|$)/i',
        '/\(First\)[\s\S]*?\(Last\)([\s\S]*?)(?=SEX|$)/i',
    ];
    
    foreach ($sectionPatterns as $pattern) {
        if (preg_match($pattern, $fullText, $sectionMatch)) {
            $nameSectionText = $sectionMatch[1] ?? $sectionMatch[0];
            Log::info('Found NAME section with pattern', [
                'pattern' => $pattern,
                'section_length' => strlen($nameSectionText),
                'preview' => substr($nameSectionText, 0, 300)
            ]);
            $sectionFound = true;
            break;
        }
    }
    
    // If no section found, use a RESTRICTED fallback (not entire document)
    if (!$sectionFound) {
        Log::warning('Could not isolate NAME section via patterns');
        
        // Try to find any line with (First) (Middle) (Last) pattern
        if (preg_match('/.*\(First\).*\(Middle\).*\(Last\).*/i', $fullText, $labelMatch)) {
            $labelPos = strpos($fullText, $labelMatch[0]);
            // Take 500 chars after the label line
            $nameSectionText = substr($fullText, $labelPos, 500);
            Log::info('Using label-anchored section', ['preview' => substr($nameSectionText, 0, 200)]);
        } else {
            // Last resort: use first 800 chars (header area, but filtered)
            $nameSectionText = substr($fullText, 0, 800);
            Log::warning('Using top portion of document as fallback');
        }
    }
    
    // ═══════════════════════════════════════════════════════════════════════
    // STEP 2: EXTRACT NAMES USING MULTIPLE STRATEGIES (Priority Order)
    // ═══════════════════════════════════════════════════════════════════════
    
    // STRATEGY A: Direct pattern for Philippine names
    // Pattern: LASTNAME, FIRSTNAME MIDDLENAME or FIRSTNAME MIDDLENAME LASTNAME
    if ($this->extractNamesDirectPattern($nameSectionText, $fields)) {
        Log::info('Names extracted via direct pattern', $fields);
        return true;
    }
    
    // STRATEGY B: Look for labeled pattern (First) VALUE (Middle) VALUE (Last) VALUE
    if ($this->extractNamesFromLabeledPattern($nameSectionText, $fields)) {
        Log::info('Names extracted via labeled pattern', $fields);
        return true;
    }

    // STRATEGY C: Handle split rows where values and labels are separated
    // Example:
    //   CHRISTIAN
    //   (First)
    //   (Middle)
    //   ROBLE
    //   (Last)
    if ($this->extractNamesFromSeparatedLabelRows($nameSectionText, $fields)) {
        Log::info('Names extracted via separated label rows', $fields);
        return true;
    }
    
    // STRATEGY D: Line after (First) (Middle) (Last) labels
    if ($this->extractNamesAfterMarkers($nameSectionText, $fields)) {
        Log::info('Names extracted via markers', $fields);
        return true;
    }
    
    // STRATEGY E: Analyze each line for name data
    if ($this->extractNamesFromLineAnalysis($nameSectionText, $fields)) {
        Log::info('Names extracted via line analysis', $fields);
        return true;
    }
    
    Log::warning('All NAME extraction strategies failed');
    return false;
}

private function extractNamesDirectPattern($text, &$fields): bool
{
    // Pattern 1: FIRSTNAME, SUFFIX MIDDLENAME LASTNAME (comma after first)
    // Example: ANTONIO, JR. TAHIL SANGUENZA
    $patterns = [
        // FIRSTNAME, JR. MIDDLENAME LASTNAME
        '/\b([A-Z]{2,}),?\s*(JR\.?|SR\.?|II|III|IV|V)?\s+([A-Z]{2,})\s+([A-Z]{2,})\b/i',
        // FIRSTNAME MIDDLENAME LASTNAME (3 uppercase words)
        '/\b([A-Z][A-Z]+)\s+([A-Z][A-Z]+)\s+([A-Z][A-Z]+)\b/',
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $first = $this->formatNamePart($match[1] . (isset($match[2]) && !empty($match[2]) ? ', ' . $match[2] : ''));
                $middle = $this->formatNamePart($match[3] ?? $match[2]);
                $last = $this->formatNamePart($match[4] ?? $match[3]);
                
                // Validate all three parts strictly
                if ($this->isValidPersonNameStrict($first) && 
                    $this->isValidPersonNameStrict($last) &&
                    (empty($middle) || $this->isValidPersonNameStrict($middle))) {
                    
                    $fields['name_first'] = $first;
                    $fields['name_middle'] = $middle;
                    $fields['name_last'] = $last;
                    return true;
                }
            }
        }
    }
    
    return false;
}

private function extractNamesFromSeparatedLabelRows($text, &$fields): bool
{
    $rawLines = preg_split('/[\r\n]+/', $text);
    $lines = array_values(array_filter(array_map('trim', $rawLines), fn($line) => $line !== ''));

    if (count($lines) < 3) {
        return false;
    }

    $labelIndex = [
        'first' => null,
        'middle' => null,
        'last' => null,
    ];

    foreach ($lines as $i => $line) {
        if ($labelIndex['first'] === null && preg_match('/^\(?\s*first\s*\)?$/i', $line)) {
            $labelIndex['first'] = $i;
            continue;
        }
        if ($labelIndex['middle'] === null && preg_match('/^\(?\s*middle\s*\)?$/i', $line)) {
            $labelIndex['middle'] = $i;
            continue;
        }
        if ($labelIndex['last'] === null && preg_match('/^\(?\s*last\s*\)?$/i', $line)) {
            $labelIndex['last'] = $i;
            continue;
        }
    }

    if ($labelIndex['first'] === null || $labelIndex['last'] === null) {
        return false;
    }

    $used = [];
    $pickNearestValue = function (int $anchor) use (&$lines, &$used) {
        $maxDistance = min(4, count($lines));

        for ($distance = 1; $distance <= $maxDistance; $distance++) {
            $candidates = [$anchor - $distance, $anchor + $distance];

            foreach ($candidates as $idx) {
                if (!isset($lines[$idx]) || isset($used[$idx])) {
                    continue;
                }

                $candidate = trim($lines[$idx]);
                if ($candidate === '') {
                    continue;
                }
                if ($this->isLabelLine($candidate) || $this->isHeaderOrLabelLine($candidate)) {
                    continue;
                }
                if (!$this->isLikelyPersonNameValue($candidate)) {
                    continue;
                }

                $used[$idx] = true;
                return $this->cleanNameField($candidate);
            }
        }

        return '';
    };

    $first = $pickNearestValue($labelIndex['first']);
    $last = $pickNearestValue($labelIndex['last']);

    $middle = '';
    if ($labelIndex['middle'] !== null) {
        $middle = $pickNearestValue($labelIndex['middle']);
    }

    if (!$this->isLikelyPersonNameValue($first) || !$this->isLikelyPersonNameValue($last)) {
        return false;
    }

    if (!empty($middle) && !$this->isLikelyPersonNameValue($middle)) {
        $middle = '';
    }

    $fields['name_first'] = $first;
    $fields['name_middle'] = $middle;
    $fields['name_last'] = $last;

    return true;
}

private function extractNamesFromLineAnalysis($text, &$fields): bool
{
    $lines = preg_split('/[\r\n]+/', $text);
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strlen($line) < 5) continue;
        
        // Skip lines that are definitely labels/headers
        if ($this->isHeaderOrLabelLine($line)) {
            Log::debug('Skipping header/label line', ['line' => $line]);
            continue;
        }
        
        // Look for uppercase data lines (potential name row)
        if (preg_match('/^[A-Z][A-Z,\.\s]+$/', $line) && strlen($line) >= 8) {
            $names = $this->parseNameDataLine($line);
            
            if ($names && $this->validateExtractedNames($names)) {
                $fields['name_first'] = $names['first'];
                $fields['name_middle'] = $names['middle'];
                $fields['name_last'] = $names['last'];
                
                Log::info('Names extracted from uppercase line', [
                    'line' => $line,
                    'parsed' => $names
                ]);
                return true;
            }
        }
    }
    
    return false;
}

private function isHeaderOrLabelLine($line): bool
{
    $lineLower = strtolower(trim($line));
    $lineUpper = strtoupper(trim($line));
    
    // ═══════════════════════════════════════════════════════════════════════
    // COMPREHENSIVE BLACKLIST - Headers, Labels, and Form Text
    // ═══════════════════════════════════════════════════════════════════════
    
    $exactBlacklist = [
        // Document headers
        'republic of the philippines',
        'civil registrar general',
        'office of the civil registrar general',
        'certificate of live birth',
        'municipal form',
        'local civil registry',
        
        // Form labels
        '(first)', '(middle)', '(last)',
        '(fial)', '(fint)', '(fnlt)',
        '(mddle)', '(mddia)', '(middie)',
        '(laei)', '(las1)', '(lar1)', '(lasi)',
        'first', 'middle', 'last', 'name', 'surname',
        'fial', 'fint', 'fnlt',
        'mddle', 'mddia', 'middie',
        'laei', 'las1', 'lar1', 'lasi',
        '1. name', '2. sex', '3. date of birth',
        
        // Instructions
        'fill out completely',
        'to be accomplished',
        'accomplished la seat', // OCR garbage that was matched
    ];
    
    // Check exact matches
    foreach ($exactBlacklist as $blacklisted) {
        if ($lineLower === $blacklisted) {
            return true;
        }
    }
    
    // ═══════════════════════════════════════════════════════════════════════
    // PARTIAL MATCH PATTERNS - Contains these keywords
    // ═══════════════════════════════════════════════════════════════════════
    
    $containsBlacklist = [
        'republic', 'philippines', 'certificate', 'registrar', 'municipal',
        'civil', 'office', 'general', 'registry', 'local',
        'documentary', 'stamp', 'tax', 'paid',
        'fill out', 'accomplish', 'accurately', 'legibly',
        'form no', 'revised', 'best possible',
        'ocrg', 'ure only',
        'province', 'city', 'municipality', 'barangay',
        'birth order', 'citizenship', 'attendant', 'certification',
        'compintey', 'mocuratety', 'fegihly', // Common OCR garbage
    ];
    
    foreach ($containsBlacklist as $keyword) {
        if (strpos($lineLower, $keyword) !== false) {
            return true;
        }
    }
    
    // ═══════════════════════════════════════════════════════════════════════
    // PATTERN-BASED REJECTION
    // ═══════════════════════════════════════════════════════════════════════
    
    // Line contains (First), (Middle), (Last) labels
    if (preg_match('/\((first|middle|last|fial|fint|fnlt|mddle|mddia|middie|laei|las1|lar1|lasi)\)/i', $line)) {
        return true;
    }
    
    // Line starts with section number (1., 2., 3., etc.)
    if (preg_match('/^\s*\d+[\.\s]/', $line)) {
        return true;
    }
    
    // Keep very short fragments out, but allow short valid surnames (e.g., ROBLE)
    if (strlen(preg_replace('/\s+/', '', $line)) < 3) {
        return true;
    }
    
    // Line has too many non-letter characters (likely form field labels)
    $letterCount = preg_match_all('/[A-Za-z]/', $line);
    $totalChars = strlen($line);
    if ($totalChars > 0 && ($letterCount / $totalChars) < 0.7) {
        return true;
    }
    
    return false;
}

private function validateExtractedNames($names): bool
{
    if (empty($names['first']) || empty($names['last'])) {
        return false;
    }
    
    // All parts must pass strict validation
    if (!$this->isValidPersonNameStrict($names['first'])) {
        Log::debug('Rejected first name', ['name' => $names['first']]);
        return false;
    }
    
    if (!$this->isValidPersonNameStrict($names['last'])) {
        Log::debug('Rejected last name', ['name' => $names['last']]);
        return false;
    }
    
    if (!empty($names['middle']) && !$this->isValidPersonNameStrict($names['middle'])) {
        Log::debug('Rejected middle name', ['name' => $names['middle']]);
        return false;
    }
    
    return true;
}


/**
 * Check if a line is a label/header line (not data)
 */
private function isLabelLine($line): bool
{
    $line = strtolower(trim($line));

    if (preg_match('/^\(?\s*(first|middle|last|fial|fint|fnlt|mddle|mddia|middie|laei|las1|lar1|lasi)\s*\)?$/i', $line)) {
        return true;
    }
    
    // Common label patterns
    $labelPatterns = [
        '/^\s*\(?\s*(first|middle|last|name|surname|given)\s*\)?\s*$/i',
        '/^\s*\(?\s*(fial|fint|fnlt|mddle|mddia|middie|laei|las1|lar1|lasi)\s*\)?\s*$/i',
        '/^\s*(first|middle|last)\s+(name)?\s*$/i',
        '/^1[\.\s]*name$/i',
        '/civil\s*registrar/i',
        '/republic/i',
        '/certificate/i',
        '/philippines/i',
    ];
    
    foreach ($labelPatterns as $pattern) {
        if (preg_match($pattern, $line)) {
            return true;
        }
    }
    
    // Line is mostly labels if it contains (First), (Middle), (Last)
    if (preg_match('/\(First\).*\(Middle\).*\(Last\)/i', $line)) {
        return true;
    }
    
    return false;
}

/**
 * Parse a line that contains name data (typically all uppercase)
 * Example: "ANTONIO, JR.    TAHIL    SANGUENZA"
 */
private function parseNameDataLine($line): ?array
{
    Log::info('Parsing potential name data line', ['line' => $line]);
    
    // Clean the line
    $line = trim($line);
    
    // Remove any label text that might be mixed in
    $line = preg_replace('/\(?\s*(First|Middle|Last|Name)\s*\)?/i', '', $line);
    $line = preg_replace('/\s+/', ' ', trim($line));
    
    if (empty($line)) {
        return null;
    }
    
    // Try splitting by multiple spaces (common in tabular forms)
    $parts = preg_split('/\s{2,}/', $line);
    $parts = array_filter($parts, function($p) { 
        return strlen(trim($p)) >= 2; 
    });
    $parts = array_values($parts);
    
    Log::info('Split by multiple spaces', ['parts' => $parts]);
    
    if (count($parts) >= 3) {
        // We have First, Middle, Last separated by multiple spaces
        return [
            'first' => $this->formatNamePart($parts[0]),
            'middle' => $this->formatNamePart($parts[1]),
            'last' => $this->formatNamePart($parts[2])
        ];
    } elseif (count($parts) == 2) {
        // First and Last only
        return [
            'first' => $this->formatNamePart($parts[0]),
            'middle' => '',
            'last' => $this->formatNamePart($parts[1])
        ];
    }
    
    // If no multi-space separation, try single space with smarter parsing
    // Handle patterns like "ANTONIO, JR. TAHIL SANGUENZA"
    $words = preg_split('/\s+/', $line);
    $words = array_filter($words, function($w) { return strlen(trim($w)) >= 1; });
    $words = array_values($words);
    
    Log::info('Split by single spaces', ['words' => $words]);
    
    if (count($words) >= 3) {
        // Check for suffix attached to first name (ANTONIO, JR.)
        $firstName = $words[0];
        $startIndex = 1;
        
        // Handle comma after first name or suffix
        if (isset($words[1]) && preg_match('/^(JR\.?|SR\.?|II|III|IV|V)$/i', $words[1])) {
            $firstName .= ' ' . strtoupper($words[1]);
            $startIndex = 2;
        }
        
        // Get remaining words
        $remaining = array_slice($words, $startIndex);
        
        if (count($remaining) >= 2) {
            return [
                'first' => $this->formatNamePart($firstName),
                'middle' => $this->formatNamePart($remaining[0]),
                'last' => $this->formatNamePart(implode(' ', array_slice($remaining, 1)))
            ];
        } elseif (count($remaining) == 1) {
            return [
                'first' => $this->formatNamePart($firstName),
                'middle' => '',
                'last' => $this->formatNamePart($remaining[0])
            ];
        }
    }
    
    return null;
}

/**
 * Format a name part (proper case, clean up)
 */
private function formatNamePart($name): string
{
    if (empty($name)) return '';
    
    $name = trim($name);
    
    // Remove trailing commas
    $name = rtrim($name, ',');
    
    // Handle suffixes - keep them uppercase
    $suffixes = ['JR', 'JR.', 'SR', 'SR.', 'II', 'III', 'IV', 'V'];
    
    $words = preg_split('/\s+/', $name);
    $result = [];
    
    foreach ($words as $word) {
        $word = trim($word);
        if (empty($word)) continue;
        
        $cleanWord = rtrim($word, '.,');
        $upperWord = strtoupper($cleanWord);
        
        if (in_array($upperWord, $suffixes) || in_array($upperWord . '.', $suffixes)) {
            // Keep suffix uppercase
            $result[] = $upperWord . (substr($word, -1) === '.' ? '.' : '');
        } else {
            // Title case for regular names
            $result[] = ucfirst(strtolower($word));
        }
    }
    
    return implode(' ', $result);
}

/**
 * Extract names from labeled pattern like (First) ANTONIO (Middle) TAHIL (Last) SANGUENZA
 */
private function extractNamesFromLabeledPattern($text, &$fields): bool
{
    // Pattern 1: (First) VALUE (Middle) VALUE (Last) VALUE
    $pattern = '/\(First\)[^\(]*?([A-Z][A-Za-z\s,\.]+?)\s*\(Middle\)[^\(]*?([A-Z][A-Za-z\s\.]*?)\s*\(Last\)[^\(]*?([A-Z][A-Za-z\s]+)/is';
    
    if (preg_match($pattern, $text, $matches)) {
        $first = $this->formatNamePart(trim($matches[1]));
        $middle = $this->formatNamePart(trim($matches[2]));
        $last = $this->formatNamePart(trim($matches[3]));
        
        if ($this->isValidPersonNameStrict($first) && $this->isValidPersonNameStrict($last)) {
            $fields['name_first'] = $first;
            $fields['name_middle'] = $middle;
            $fields['name_last'] = $last;
            
            Log::info('Names extracted from labeled pattern', [
                'first' => $first, 'middle' => $middle, 'last' => $last
            ]);
            return true;
        }
    }
    
    return false;
}

/**
 * Extract names by finding text after First/Middle/Last markers
 */
private function extractNamesAfterMarkers($text, &$fields): bool
{
    $lines = preg_split('/[\r\n]+/', $text);
    
    // Find the label line containing (First), (Middle), (Last)
    $labelLineIndex = -1;
    foreach ($lines as $i => $line) {
        if (preg_match('/\(First\).*\(Middle\).*\(Last\)/i', $line) ||
            preg_match('/First.*Middle.*Last/i', $line)) {
            $labelLineIndex = $i;
            break;
        }
    }
    
    if ($labelLineIndex >= 0 && isset($lines[$labelLineIndex + 1])) {
        // The data should be on the next line
        $dataLine = trim($lines[$labelLineIndex + 1]);
        
        if (!empty($dataLine) && !$this->isLabelLine($dataLine)) {
            $names = $this->parseNameDataLine($dataLine);
            
            if ($names && !empty($names['first']) && !empty($names['last'])) {
                if ($this->isValidPersonNameStrict($names['first']) && 
                    $this->isValidPersonNameStrict($names['last'])) {
                    $fields['name_first'] = $names['first'];
                    $fields['name_middle'] = $names['middle'];
                    $fields['name_last'] = $names['last'];
                    
                    Log::info('Names extracted from line after markers', $names);
                    return true;
                }
            }
        }
    }
    
    return false;
}

/**
 * Find any proper name sequence in the text
 */
private function extractNamesFromProperNameSequence($text, &$fields): bool
{
    // Look for sequences of 2-3 capitalized proper names
    // Pattern: ProperName ProperName ProperName
    $pattern = '/\b([A-Z][a-z]+(?:,?\s+(?:JR|SR|II|III|IV|V)\.?)?)\s+([A-Z][a-z]+)\s+([A-Z][a-z]+)\b/';
    
    if (preg_match($pattern, $text, $matches)) {
        $first = $this->formatNamePart($matches[1]);
        $middle = $this->formatNamePart($matches[2]);
        $last = $this->formatNamePart($matches[3]);
        
        // Strict validation to avoid false positives
        if ($this->isValidPersonNameStrict($first) && 
            $this->isValidPersonNameStrict($middle) && 
            $this->isValidPersonNameStrict($last)) {
            
            $fields['name_first'] = $first;
            $fields['name_middle'] = $middle;
            $fields['name_last'] = $last;
            
            Log::info('Names extracted from proper name sequence', [
                'first' => $first, 'middle' => $middle, 'last' => $last
            ]);
            return true;
        }
    }
    
    return false;
}

/**
 * Strict validation for person names - prevents garbage extraction
 */
private function isValidPersonNameStrict($name): bool
{
    if (empty($name)) return false;
    
    $name = trim($name);
    $nameLower = strtolower($name);
    
    // Minimum length (but allow suffixes like "Jr")
    $validSuffixes = ['jr', 'jr.', 'sr', 'sr.', 'ii', 'iii', 'iv', 'v'];
    if (strlen($name) < 2) {
        return false;
    }
    if (strlen($name) <= 3 && !in_array($nameLower, $validSuffixes)) {
        Log::debug('Rejected name (too short)', ['name' => $name]);
        return false;
    }
    
    // ═══════════════════════════════════════════════════════════════════════
    // COMPREHENSIVE BLACKLIST
    // ═══════════════════════════════════════════════════════════════════════
    
    $blacklist = [
        // Single letters and articles
        'a', 'i', 'the', 'of', 'and', 'or', 'in', 'at', 'to', 'for', 'by', 'on',
        'la', 'el', 'de', 'del', 'ng', 'sa', 'ni', 'si', 'mga',
        
        // Form labels
        'first', 'middle', 'last', 'name', 'surname', 'given', 'maiden',
        'sex', 'male', 'female', 'birth', 'date', 'place', 'time',
        
        // Government/official terms
        'civil', 'registrar', 'general', 'republic', 'philippines', 'philippine',
        'certificate', 'municipal', 'office', 'local', 'national', 'registry',
        'city', 'province', 'municipality', 'barangay', 'hospital',
        
        // Document terms
        'form', 'page', 'copy', 'original', 'revised', 'documentary', 'stamp', 'tax',
        'accomplished', 'seat', 'fill', 'out', 'completely', 'accurately',
        
        // Header fragments
        'offige', 'offic', 're', 'arto', 'best', 'possible', 'image',
        
        // Common OCR garbage from this specific document
        'compintey', 'mocuratety', 'fegihly', 'ilar', 'type', 'evinne',
        'aepubile', 'clivit', 'hpewsita', 'epaeaen', 'seoond',
        'piypwician', 'hilod', 'spocity', 'bren', 'trlidm',
        'ocrg', 'ure',
        
        // Section labels
        'citizenship', 'attendant', 'certification', 'parents', 'mother', 'father',
        'order', 'multiple', 'single', 'twin', 'triple',
    ];
    
    // Exact match check
    if (in_array($nameLower, $blacklist)) {
        Log::debug('Rejected name (blacklist exact match)', ['name' => $name]);
        return false;
    }
    
    // Check each word in multi-word names
    $words = preg_split('/[\s\-]+/', $nameLower);
    foreach ($words as $word) {
        $word = trim($word, '.,');
        if (in_array($word, $blacklist)) {
            Log::debug('Rejected name (blacklist word match)', ['name' => $name, 'word' => $word]);
            return false;
        }
    }
    
    // ═══════════════════════════════════════════════════════════════════════
    // POSITIVE VALIDATION RULES
    // ═══════════════════════════════════════════════════════════════════════
    
    // Must start with a letter
    if (!preg_match('/^[A-Za-zÑñ]/', $name)) {
        Log::debug('Rejected name (does not start with letter)', ['name' => $name]);
        return false;
    }
    
    // Must be mostly letters (≥85%)
    $letterCount = preg_match_all('/[A-Za-zÑñ]/', $name);
    $totalChars = strlen(preg_replace('/\s/', '', $name));
    
    if ($totalChars > 0 && ($letterCount / $totalChars) < 0.85) {
        Log::debug('Rejected name (too few letters)', ['name' => $name, 'ratio' => $letterCount / $totalChars]);
        return false;
    }
    
    // Filipino names are typically 3-15 characters (excluding suffix)
    $nameWithoutSuffix = preg_replace('/,?\s*(JR\.?|SR\.?|II|III|IV|V)\s*$/i', '', $name);
    if (strlen($nameWithoutSuffix) > 20) {
        Log::debug('Rejected name (too long)', ['name' => $name]);
        return false;
    }
    
    Log::debug('Accepted as valid person name', ['name' => $name]);
    return true;
}

private function extractSection2_Sex($cleanedText, $rawText, &$fields)
{
    Log::info('Extracting Section 2: SEX');
    
    // Enhanced patterns for sex detection including checkbox marks
    $sexPatterns = [
        // Look for "2. SEX" section with checkbox marks
        '/2\.\s*SEX.*?(?:x|X|√|✓|■|☑|☒).*?(?:Male|M(?:ale)?)\b/i' => 'Male',
        '/2\.\s*SEX.*?(?:x|X|√|✓|■|☑|☒).*?(?:Female|F(?:emale)?)\b/i' => 'Female',
        
        // General sex patterns with marks
        '/(?:Sex|Gender).*?(?:x|X|√|✓|■|☑|☒).*?(?:Male|M(?:ale)?)\b/i' => 'Male',
        '/(?:Sex|Gender).*?(?:x|X|√|✓|■|☑|☒).*?(?:Female|F(?:emale)?)\b/i' => 'Female',
        
        // Look for checkbox before sex
        '/(?:x|X|√|✓|■|☑|☒).*?(?:Male|M(?:ale)?)\b/i' => 'Male',
        '/(?:x|X|√|✓|■|☑|☒).*?(?:Female|F(?:emale)?)\b/i' => 'Female',
        
        // Reverse: sex followed by mark
        '/(?:Male|M(?:ale)?).*?(?:x|X|√|✓|■|☑|☒)/i' => 'Male',
        '/(?:Female|F(?:emale)?).*?(?:x|X|√|✓|■|☑|☒)/i' => 'Female'
    ];
    
    foreach ($sexPatterns as $pattern => $sex) {
        if (preg_match($pattern, $rawText) || preg_match($pattern, $cleanedText)) {
            $fields['sex'] = $sex;
            Log::info('SEX section extracted successfully', ['sex' => $sex]);
            return;
        }
    }
    
    Log::warning('SEX section not detected');
}

private function extractSection3_DateOfBirth($cleanedText, $rawText, &$fields)
{
    Log::info('Extracting Section 3: DATE OF BIRTH');
    
    $datePatterns = [
        // Look for "3. DATE OF BIRTH" section with day/month/year structure
        '/3\.\s*DATE\s+OF\s+BIRTH.*?(?:Day|day).*?(\d{1,2}).*?(?:Month|month).*?([A-Za-z]+|\d{1,2}).*?(?:Year|year).*?(\d{4})/is',
        
        // Alternative pattern for tabular structure
        '/DATE\s+OF\s+BIRTH[\s\r\n]+(?:Day|day)[\s\t]+(?:Month|month)[\s\t]+(?:Year|year)[\s\r\n]+(\d{1,2})[\s\t]+([A-Za-z]+|\d{1,2})[\s\t]+(\d{4})/is',
        
        // General date patterns
        '/(?:DATE\s+OF\s+BIRTH|Birth\s+Date).*?(\d{1,2}).*?([A-Za-z]+|\d{1,2}).*?(\d{4})/is',
        
        // Direct date formats
        '/(\d{1,2})\s+(January|February|March|April|May|June|July|August|September|October|November|December)\s+(\d{4})/i',
        '/(\d{1,2})\s+(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+(\d{4})/i',
        '/(\d{1,2})[\s\/-](\d{1,2})[\s\/-](\d{4})/'
    ];
    
    foreach ($datePatterns as $pattern) {
        if (preg_match($pattern, $cleanedText, $matches)) {
            $fields['birth_date_day'] = str_pad(trim($matches[1]), 2, '0', STR_PAD_LEFT);
            $fields['birth_date_month'] = $this->normalizeMonth(trim($matches[2]));
            $fields['birth_date_year'] = trim($matches[3]);
            
            Log::info('DATE OF BIRTH section extracted successfully', [
                'day' => $fields['birth_date_day'],
                'month' => $fields['birth_date_month'],
                'year' => $fields['birth_date_year']
            ]);
            return;
        }
    }
    
    Log::warning('DATE OF BIRTH section not detected');
}

private function extractSection4_PlaceOfBirth($cleanedText, $rawText, &$fields)
{
    Log::info('Extracting Section 4: PLACE OF BIRTH');
    
    // Enhanced patterns for place of birth with multiple components
    $placePatterns = [
        // Look for "4. PLACE OF BIRTH" section with structured components
        '/4\.\s*PLACE\s+OF\s+BIRTH.*?(?:Name\s+of\s+Hospital|Institution|House\s+No).*?([A-Za-z0-9\s,.-]{5,100}).*?(?:City|Municipality).*?([A-Za-z\s,.-]{3,50}).*?(?:Province).*?([A-Za-z\s,.-]{3,50})/is',
        
        // Alternative: simpler place structure
        '/PLACE\s+OF\s+BIRTH.*?([A-Za-z\s,.-]{5,100})/is',
        
        // Look for specific place indicators
        '/(?:Born\s+in|Born\s+at|Birth\s+Place).*?([A-Za-z\s,.-]{5,100})/is'
    ];
    
    foreach ($placePatterns as $pattern) {
        if (preg_match($pattern, $cleanedText, $matches)) {
            if (count($matches) >= 4) {
                // Full structure found
                $fields['birth_place_institution'] = $this->cleanPlaceField(trim($matches[1]));
                $fields['birth_place_city'] = $this->cleanPlaceField(trim($matches[2]));
                $fields['birth_place_province'] = $this->cleanPlaceField(trim($matches[3]));
            } else {
                // Simple place found - try to parse components
                $fullPlace = $this->cleanPlaceField(trim($matches[1]));
                $fields['birth_place_institution'] = $fullPlace;
                
                // Try to extract city and province from the full place
                $this->parsePlaceComponents($fullPlace, $fields);
            }
            
            Log::info('PLACE OF BIRTH section extracted successfully', [
                'institution' => $fields['birth_place_institution'],
                'city' => $fields['birth_place_city'],
                'province' => $fields['birth_place_province']
            ]);
            return;
        }
    }
    
    Log::warning('PLACE OF BIRTH section not detected');
}

private function parsePlaceComponents($fullPlace, &$fields)
{
    // Common Philippine place patterns
    if (preg_match('/(.+?),\s*([^,]+),\s*([^,]+)$/', $fullPlace, $matches)) {
        $fields['birth_place_institution'] = trim($matches[1]);
        $fields['birth_place_city'] = trim($matches[2]);
        $fields['birth_place_province'] = trim($matches[3]);
    } elseif (preg_match('/(.+?),\s*([^,]+)$/', $fullPlace, $matches)) {
        $fields['birth_place_city'] = trim($matches[1]);
        $fields['birth_place_province'] = trim($matches[2]);
    }
}

private function extractSection5_MotherName($cleanedText, $rawText, &$fields)
{
    Log::info('Extracting Section 5: MOTHER\'S MAIDEN NAME with enhanced parsing');
    
    $motherPatterns = [
        // Pattern 1: Look for "MOTHER'S MAIDEN NAME" section with full names
        '/(?:MOTHER\'?S?\s+MAIDEN\s+NAME|Mother.*?Maiden.*?Name)[\s\r\n]+([A-Z][A-Za-z\s,.-]{5,80})/is',
        
        // Pattern 2: Look for "MAIDEN NAME" followed by names
        '/MAIDEN\s+NAME[\s\r\n]+([A-Z][A-Za-z\s,.-]{5,80})/is',
        
        // Pattern 3: Section number for mother's name (varies by form)
        '/(?:6|7)\.\s*(?:MOTHER|MAIDEN).*?NAME[^\n]*\n\s*([A-Z][A-Za-z\s,.-]{5,80})/is',
        
        // Pattern 4: Three uppercase words after MOTHER indicator (tabular)
        '/(?:MOTHER|Mother).*?([A-Z]{2,15})\s+([A-Z]{2,15})\s+([A-Z]{2,15})/is',
        
        // Pattern 5: Direct name pattern after mother indicators (relaxed min length)
        '/(?:MOTHER|Mother).*?([A-Z]{2,15}[\s,]+[A-Z]{2,15}[\s,]+[A-Z]{2,15})/is'
    ];
    
    foreach ($motherPatterns as $index => $pattern) {
        if (preg_match($pattern, $cleanedText, $matches)) {
            // Pattern 4 has 3 separate capture groups
            if ($index === 3 && count($matches) >= 4) {
                $first = $this->cleanNameField(trim($matches[1]));
                $middle = $this->cleanNameField(trim($matches[2]));
                $last = $this->cleanNameField(trim($matches[3]));
                
                if (strlen($first) >= 2 && strlen($last) >= 2) {
                    $fields['mother_first_name'] = $first;
                    $fields['mother_middle_name'] = $middle;
                    $fields['mother_last_name'] = $last;
                    Log::info('MOTHER\'S MAIDEN NAME extracted via tabular pattern', [
                        'first' => $first, 'middle' => $middle, 'last' => $last
                    ]);
                    return;
                }
            }
            
            $fullName = trim($matches[1]);
            
            // Parse the full name into components
            $nameParts = $this->parseFullName($fullName);
            
            if (count($nameParts) >= 2) {
                $fields['mother_first_name'] = $this->cleanNameField($nameParts[0]);
                $fields['mother_middle_name'] = count($nameParts) >= 3 ? $this->cleanNameField($nameParts[1]) : '';
                $fields['mother_last_name'] = $this->cleanNameField(end($nameParts));
                
                Log::info('MOTHER\'S MAIDEN NAME section extracted successfully', [
                    'full_name' => $fullName,
                    'first' => $fields['mother_first_name'],
                    'middle' => $fields['mother_middle_name'],
                    'last' => $fields['mother_last_name']
                ]);
                return;
            }
        }
    }
    
    // Fallback: Try to find any 3 consecutive uppercase words near MOTHER section
    if (preg_match('/(?:MOTHER|MAIDEN)[^\n]*(?:\n[^\n]*){0,3}\n\s*([A-Z]{2,20})\s+([A-Z]{2,20})\s+([A-Z]{2,20})/is', $cleanedText, $fallbackMatch)) {
        $first = $this->cleanNameField(trim($fallbackMatch[1]));
        $middle = $this->cleanNameField(trim($fallbackMatch[2]));
        $last = $this->cleanNameField(trim($fallbackMatch[3]));
        
        if (strlen($first) >= 2 && strlen($last) >= 2) {
            $fields['mother_first_name'] = $first;
            $fields['mother_middle_name'] = $middle;
            $fields['mother_last_name'] = $last;
            Log::info('MOTHER\'S MAIDEN NAME extracted via fallback uppercase pattern');
            return;
        }
    }
    
    Log::warning('MOTHER\'S MAIDEN NAME section not detected');
}

private function extractSection6_FatherName($cleanedText, $rawText, &$fields)
{
    Log::info('Extracting Section 6: FATHER\'S NAME with enhanced parsing');

    // First, handle the common structured section layout:
    // 13. NAME
    // LEONIL
    // (First)
    // ESTRERA
    // (Middle)
    // GITANES
    // (Last)
    if ($this->extractFatherFromStructuredSection($cleanedText, $fields)) {
        return;
    }
    
    $fatherPatterns = [
        // Pattern 1: Look for "FATHER'S NAME" section with full names
        '/(?:FATHER\'?S?\s+NAME|Father.*?Name)[\s\r\n]+([A-Z][A-Za-z\s,.-]{5,80})/is',
        
        // Pattern 2: Look for "13. NAME" (father section number) 
        '/13\.\s*NAME[\s\r\n]+([A-Z][A-Za-z\s,.-]{5,80})/is',
        
        // Pattern 3: Three uppercase words after FATHER indicator (tabular)
        '/(?:FATHER|Father).*?([A-Z]{2,15}(?:,?\s+(?:JR|SR)\\.?)?)\s+([A-Z]{2,15})\s+([A-Z]{2,15})/is',
        
        // Pattern 4: Direct name pattern after father indicators (relaxed)
        '/(?:FATHER|Father).*?([A-Z]{2,15}[\s,]+[A-Z]{2,15}[\s,]+[A-Z]{2,15})/is'
    ];
    
    foreach ($fatherPatterns as $index => $pattern) {
        if (preg_match($pattern, $cleanedText, $matches)) {
            // Pattern 3 has 3 separate capture groups (tabular format)
            if ($index === 2 && count($matches) >= 4) {
                $first = $this->cleanNameField(trim($matches[1]));
                $middle = $this->cleanNameField(trim($matches[2]));
                $last = $this->cleanNameField(trim($matches[3]));
                
                if ($this->isLikelyNameTriplet($first, $middle, $last)) {
                    $fields['father_first_name'] = $first;
                    $fields['father_middle_name'] = $middle;
                    $fields['father_last_name'] = $last;
                    Log::info('FATHER\'S NAME extracted via tabular pattern', [
                        'first' => $first, 'middle' => $middle, 'last' => $last
                    ]);
                    return;
                }
            }
            
            $fullName = trim($matches[1]);

            if ($this->containsAdministrativeNameNoise($fullName)) {
                Log::warning('Rejected noisy father name candidate', [
                    'candidate' => $fullName,
                    'pattern_index' => $index,
                ]);
                continue;
            }
            
            // Parse the full name into components
            $nameParts = $this->parseFullName($fullName);
            
            if (count($nameParts) >= 2) {
                $first = $this->cleanNameField($nameParts[0]);
                $middle = count($nameParts) >= 3 ? $this->cleanNameField($nameParts[1]) : '';
                $last = $this->cleanNameField(end($nameParts));

                if (!$this->isLikelyNameTriplet($first, $middle, $last)) {
                    Log::warning('Rejected invalid father name triplet', [
                        'full_name' => $fullName,
                        'first' => $first,
                        'middle' => $middle,
                        'last' => $last,
                    ]);
                    continue;
                }

                $fields['father_first_name'] = $first;
                $fields['father_middle_name'] = $middle;
                $fields['father_last_name'] = $last;
                
                Log::info('FATHER\'S NAME section extracted successfully', [
                    'full_name' => $fullName,
                    'first' => $fields['father_first_name'],
                    'middle' => $fields['father_middle_name'], 
                    'last' => $fields['father_last_name']
                ]);
                return;
            }
        }
    }
    
    // Fallback: Try to find any 3+ consecutive uppercase words near FATHER section
    if (preg_match('/(?:FATHER|13\.)[^\n]*(?:\n[^\n]*){0,3}\n\s*([A-Z]{2,20}(?:,?\s+(?:JR|SR|III|IV|II)\.?)?)\s+([A-Z]{2,20})\s+([A-Z]{2,20})/is', $cleanedText, $fallbackMatch)) {
        $first = $this->cleanNameField(trim($fallbackMatch[1]));
        $middle = $this->cleanNameField(trim($fallbackMatch[2]));
        $last = $this->cleanNameField(trim($fallbackMatch[3]));
        
        if ($this->isLikelyNameTriplet($first, $middle, $last)) {
            $fields['father_first_name'] = $first;
            $fields['father_middle_name'] = $middle;
            $fields['father_last_name'] = $last;
            Log::info('FATHER\'S NAME extracted via fallback uppercase pattern');
            return;
        }
    }
    
    Log::warning('FATHER\'S NAME section not detected');
}

private function extractFatherFromStructuredSection(string $cleanedText, array &$fields): bool
{
    $sectionPatterns = [
        '/13[\.\s]*NAME\b([\s\S]*?)(?=14[\.\s]*CITIZENSHIP\b|15[\.\s]*RELIGION\b|16[\.\s]*OCCUPATION\b|$)/i',
        '/FATHER\'?S?\s+NAME\b([\s\S]*?)(?=14[\.\s]*CITIZENSHIP\b|15[\.\s]*RELIGION\b|16[\.\s]*OCCUPATION\b|$)/i',
    ];

    foreach ($sectionPatterns as $pattern) {
        if (!preg_match($pattern, $cleanedText, $match)) {
            continue;
        }

        $fatherSectionText = trim($match[1] ?? '');
        if ($fatherSectionText === '') {
            continue;
        }

        if ($this->containsAdministrativeNameNoise($fatherSectionText)) {
            // Continue to other parsing strategies when this section candidate is polluted
            Log::debug('Structured father section contains administrative noise; trying additional strategies', [
                'preview' => substr($fatherSectionText, 0, 180),
            ]);
        }

        // Reuse the label-row extractor used by Section 1 NAME.
        $temp = ['name_first' => '', 'name_middle' => '', 'name_last' => ''];
        if ($this->extractNamesFromSeparatedLabelRows($fatherSectionText, $temp)) {
            $first = $this->cleanNameField((string) ($temp['name_first'] ?? ''));
            $middle = $this->cleanNameField((string) ($temp['name_middle'] ?? ''));
            $last = $this->cleanNameField((string) ($temp['name_last'] ?? ''));

            if ($this->isLikelyNameTriplet($first, $middle, $last)) {
                $fields['father_first_name'] = $first;
                $fields['father_middle_name'] = $middle;
                $fields['father_last_name'] = $last;

                Log::info('FATHER\'S NAME extracted via structured labeled section', [
                    'first' => $first,
                    'middle' => $middle,
                    'last' => $last,
                ]);
                return true;
            }
        }

        // Fallback for non-labeled variants: pick first 2-3 likely person-name lines.
        $lines = preg_split('/[\r\n]+/', $fatherSectionText);
        $candidates = [];
        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '' || $this->isLabelLine($line) || $this->isHeaderOrLabelLine($line)) {
                continue;
            }

            $line = $this->cleanNameField($line);
            if ($this->isLikelyPersonNameValue($line)) {
                $candidates[] = $line;
            }
        }

        if (count($candidates) >= 2) {
            $first = $candidates[0];
            $middle = count($candidates) >= 3 ? $candidates[1] : '';
            $last = count($candidates) >= 3 ? $candidates[2] : $candidates[1];

            if ($this->isLikelyNameTriplet($first, $middle, $last)) {
                $fields['father_first_name'] = $first;
                $fields['father_middle_name'] = $middle;
                $fields['father_last_name'] = $last;

                Log::info('FATHER\'S NAME extracted via structured section fallback', [
                    'first' => $first,
                    'middle' => $middle,
                    'last' => $last,
                ]);
                return true;
            }
        }
    }

    return false;
}

private function parseFullName($fullName)
{
    // Clean the name string
    $fullName = preg_replace('/\s+/', ' ', trim($fullName));
    $fullName = preg_replace('/[,.-]+/', ' ', $fullName);
    $fullName = trim($fullName);
    
    // Split by spaces and filter empty parts
    $parts = array_filter(explode(' ', $fullName));
    
    // Remove common suffixes and handle them
    $cleanedParts = [];
    foreach ($parts as $part) {
        if (preg_match('/^(JR|SR|III|IV|II)\.?$/i', $part)) {
            // Add suffix to first name if it exists
            if (!empty($cleanedParts)) {
                $cleanedParts[0] .= ' ' . $part;
            }
        } else {
            $cleanedParts[] = $part;
        }
    }
    
    Log::info('Parsed name components', [
        'original' => $fullName,
        'parts' => $cleanedParts
    ]);
    
    return $cleanedParts;
}

private function containsAdministrativeNameNoise(string $value): bool
{
    if (trim($value) === '') {
        return false;
    }

    return (bool) preg_match(
        '/\b(print|officer|position|signature|prepared|received|admin|registrar|office|civil|title|informant|witness|address|documentary|stamp|tax)\b/i',
        $value
    );
}

private function isLikelyPersonNameValue(string $value): bool
{
    $value = $this->cleanNameField($value);
    if ($value === '') {
        return false;
    }

    if ($this->containsAdministrativeNameNoise($value)) {
        return false;
    }

    if (preg_match('/\d/', $value)) {
        return false;
    }

    if (!preg_match('/^[A-Za-zÑñ\.\'\-\s]+$/', $value)) {
        return false;
    }

    $blockedWords = [
        'first', 'middle', 'last', 'name', 'sex', 'male', 'female',
        'father', 'mother', 'child', 'birth', 'date', 'place',
        'registry', 'civil', 'registrar', 'office', 'in', 'of', 'to', 'at', 'or'
    ];

    $words = preg_split('/\s+/', strtolower($value));
    foreach ($words as $word) {
        $word = trim($word, '.,');
        if ($word === '') {
            continue;
        }
        if (in_array($word, $blockedWords, true)) {
            return false;
        }
    }

    return strlen(preg_replace('/\s+/', '', $value)) >= 2;
}

private function isLikelyNameTriplet(string $first, string $middle, string $last): bool
{
    if (!$this->isLikelyPersonNameValue($first) || !$this->isLikelyPersonNameValue($last)) {
        return false;
    }

    if (!empty($middle) && !$this->isLikelyPersonNameValue($middle)) {
        return false;
    }

    return true;
}


private function extractRegistryInformation($cleanedText, $rawText, &$fields)
{
    Log::info('Extracting Registry Information');
    
    // Registry number patterns
    $registryPatterns = [
        '/(?:Registry\s+No\.?|Reg\.\s+No\.?)[\s:]*([A-Z0-9\-]+)/i',
        '/(?:REGISTRY\s+NUMBER)[\s:]*([A-Z0-9\-]+)/i'
    ];
    
    foreach ($registryPatterns as $pattern) {
        if (preg_match($pattern, $cleanedText, $matches)) {
            $fields['registry_number'] = trim($matches[1]);
            break;
        }
    }
    
    // BReN patterns (must start with digit to avoid matching "BEST POSSIBLE IMAGE")
    $brenPatterns = [
        '/(?:BReN|Birth\s+Reference\s+Number)[\s:]*(\d[\dA-Za-z\-]+)/i',
        '/(?:Reference\s+No\.?)[\s:]*(\d[\dA-Za-z\-]+)/i'
    ];
    
    foreach ($brenPatterns as $pattern) {
        if (preg_match($pattern, $cleanedText, $matches)) {
            $fields['bren_number'] = trim($matches[1]);
            break;
        }
    }
    
    // Citizenship
    if (preg_match('/(?:CITIZENSHIP|NATIONALITY)[\s:]*([A-Za-z\s]+)/i', $cleanedText, $matches)) {
        $fields['citizenship'] = trim($matches[1]);
    }
    
    // Religion (handle OCR mangling: REUGION, RELGION, etc.)
    if (preg_match('/(?:RE[LU]?[IL]?GI?ON|RELIGION)[\s:]*([A-Za-z\s,.:\']+)/i', $cleanedText, $matches)) {
        $religion = trim($matches[1]);
        // Limit to first 50 chars to avoid capturing too much trailing text
        if (strlen($religion) > 50) $religion = substr($religion, 0, 50);
        $fields['religion'] = $religion;
    }
}

/**
 * Spatial post-processing for birth certificate fields.
 * Uses bounding-box coordinates to fix/fill fields that regex missed or got wrong.
 * Only overrides fields that are empty or contain known-bad values.
 */
private function spatialEnhanceBirthFields(array $fields, array $boxes): array
{
    if (empty($boxes)) return $fields;
    Log::info('Starting spatial enhancement for birth certificate fields');

    // ── Parse boxes into workable items ──────────────────────────
    $items = [];
    foreach ($boxes as $b) {
        $box = $b['box'] ?? [];
        if (count($box) < 4) continue;
        $xMin = min($box[0][0], $box[3][0]);
        $xMax = max($box[1][0], $box[2][0]);
        $yMin = min($box[0][1], $box[1][1]);
        $yMax = max($box[2][1], $box[3][1]);
        $items[] = [
            'text'  => trim($b['text'] ?? ''),
            'conf'  => $b['confidence'] ?? 0,
            'x_min' => $xMin, 'x_max' => $xMax,
            'x_mid' => ($xMin + $xMax) / 2,
            'y_min' => $yMin, 'y_max' => $yMax,
            'y_mid' => ($yMin + $yMax) / 2,
        ];
    }
    usort($items, fn($a, $b) => $a['y_mid'] <=> $b['y_mid']);
    if (empty($items)) return $fields;

    $allXMax = max(array_column($items, 'x_max'));

    // ── Helper: find anchor Y by keyword ─────────────────────────
    $findAnchorY = function (array $keywords) use (&$items) {
        foreach ($items as $it) {
            $upper = strtoupper($it['text']);
            foreach ($keywords as $kw) {
                if (str_contains($upper, strtoupper($kw))) {
                    return $it['y_mid'];
                }
            }
        }
        return null;
    };

    // ── Helper: find items in Y/X region ─────────────────────────
    $findInRegion = function (float $yTarget, float $yTol, float $xFrom, float $xTo, array $exclude = []) use (&$items) {
        $found = [];
        foreach ($items as $it) {
            if (abs($it['y_mid'] - $yTarget) <= $yTol
                && $it['x_mid'] >= $xFrom && $it['x_mid'] <= $xTo) {
                $skip = false;
                foreach ($exclude as $ex) {
                    if (stripos($it['text'], $ex) !== false) { $skip = true; break; }
                }
                if (!$skip) $found[] = $it;
            }
        }
        usort($found, fn($a, $b) => $a['x_min'] <=> $b['x_min']);
        return $found;
    };

    // ── Helper: find section anchor when number/label are split across boxes ──
    $findNumberedSectionAnchorY = function (int $sectionNo, array $labelHints) use (&$items) {
        $sectionPattern = '/\b' . preg_quote((string) $sectionNo, '/') . '\b/';

        // Case 1: number + label already in one OCR box
        foreach ($items as $it) {
            $text = strtoupper(trim((string) $it['text']));
            if (!preg_match($sectionPattern, $text)) {
                continue;
            }

            foreach ($labelHints as $hint) {
                if (str_contains($text, strtoupper($hint))) {
                    return $it['y_mid'];
                }
            }
        }

        // Case 2: standalone section number box, with label in same row
        foreach ($items as $it) {
            $text = strtoupper(trim((string) $it['text']));
            if (!preg_match('/^' . preg_quote((string) $sectionNo, '/') . '[\.)]?$/', $text)) {
                continue;
            }

            foreach ($items as $peer) {
                if (abs($peer['y_mid'] - $it['y_mid']) > 14) {
                    continue;
                }

                $peerText = strtoupper(trim((string) $peer['text']));
                foreach ($labelHints as $hint) {
                    if (str_contains($peerText, strtoupper($hint))) {
                        return ($peer['y_mid'] + $it['y_mid']) / 2;
                    }
                }
            }
        }

        return null;
    };

    // Labels to exclude from data values
    $excl = [
        'NAME', 'FIRST', 'FNLT', 'FINT', 'MIDDLE', 'MDDLE', 'MDDIA',
        'LAST', 'LAR1', 'LAS1', 'MAIDEN', 'OCRG', 'URE', 'ONLY',
        'POPULATION', 'REFERENCE', 'PLACE', 'BIRTH', 'CERTIFICATE',
        'SEX', 'DATE', 'DAY', 'MONTH', 'YEAR', 'PROVINCE', 'CITY',
        'MUNICIPALITY', 'BARANGAY', 'HOSPITAL', 'INSTITUTION', 'HOUSE',
        'STREET', 'HOUTE', 'ROUTE', 'CITIZENSHIP', 'RELIGION', 'REUGION',
        'OCCUPATION', 'FORM', 'REGISTRY', 'REGISTRAR', 'AGE', 'MALE',
        'FEMALE', 'FAMALE', 'REPUBLIC', 'PHILIPPINES', 'OFFICE', 'CIVIL',
        'GENERAL', 'LIVE', 'DEATH', 'APPEOPRIETE', 'BETORE', 'ANTWER',
        'TYPE', 'MULTIPLE', 'SINGLE', 'TWIN', 'WEIGHT', 'ATTENDANT',
        'CERTIFICATION', 'INFORMANT', 'PREPARED', 'MUNKOIPSITY',
        'PROVINCO', 'PROVINCF',
    ];

    // Check if field value is empty or contains known-bad text
    $isBad = function ($val, array $badSignals = []) {
        if (empty(trim($val ?? ''))) return true;
        foreach ($badSignals as $sig) {
            if (stripos($val, $sig) !== false) return true;
        }
        return false;
    };

    // Filter out parenthetical labels and garbage
    $filterDataBoxes = function (array $boxes) {
        return array_values(array_filter($boxes, function ($b) {
            $t = trim($b['text']);
            if (str_starts_with($t, '(')) return false;
            if (strlen($t) < 2) return false;
            if (preg_match('/^[\d\s.]+$/', $t)) return false;
            if (!preg_match('/[A-Za-z]{2,}/', $t)) return false;
            return true;
        }));
    };

    // ── 1. CHILD NAME ─────────────────────────────────────────────
    $childNeedsSpatial = !$this->isLikelyPersonNameValue((string)($fields['name_first'] ?? ''))
        || !$this->isLikelyPersonNameValue((string)($fields['name_last'] ?? ''))
        || $this->containsAdministrativeNameNoise((string)($fields['name_first'] ?? ''))
        || $this->containsAdministrativeNameNoise((string)($fields['name_middle'] ?? ''))
        || $this->containsAdministrativeNameNoise((string)($fields['name_last'] ?? ''));

    if ($childNeedsSpatial) {
        $nameY = $findAnchorY(['1. NAME', '1.NAME', '1 NAME']);
        $sexY  = $findAnchorY(['2. SEX', '2.SEX', '2 SEX']);

        if ($nameY !== null && $sexY !== null) {
            $midY = ($nameY + $sexY) / 2 + 5;
            $tol  = ($sexY - $nameY) / 2 - 5;
            // Exclude right-side OCRG column (x > 70% of page)
            $nameBoxes = $findInRegion($midY, $tol, $allXMax * 0.2, $allXMax * 0.7, $excl);
            $nameBoxes = $filterDataBoxes($nameBoxes);

            if (count($nameBoxes) >= 3) {
                $fields['name_first']  = $this->cleanNameField($nameBoxes[0]['text']);
                $fields['name_middle'] = $this->cleanNameField($nameBoxes[1]['text']);
                $fields['name_last']   = $this->cleanNameField($nameBoxes[2]['text']);
            } elseif (count($nameBoxes) === 2) {
                $fields['name_first']  = $this->cleanNameField($nameBoxes[0]['text']);
                $fields['name_middle'] = '';
                $fields['name_last']   = $this->cleanNameField($nameBoxes[1]['text']);
            }
            Log::info('Spatial: child name', [
                'first' => $fields['name_first'], 'middle' => $fields['name_middle'], 'last' => $fields['name_last']
            ]);
        }
    }

    // ── 2. BIRTH DATE ──────────────────────────────────────────────
    $validMonths = ['january','february','march','april','may','june',
                    'july','august','september','october','november','december'];
    if (!in_array(strtolower($fields['birth_date_month'] ?? ''), $validMonths)) {
        $dateY = $findAnchorY(['DATE OF BIRTH']);
        if ($dateY !== null) {
            // Date values live in the right half of the row (x > 45%)
            $dateBoxes = $findInRegion($dateY, 40, $allXMax * 0.45, $allXMax * 0.95, $excl);
            foreach ($dateBoxes as $db) {
                $t = trim($db['text']);
                // "27." → day
                if (preg_match('/^(\d{1,2})\.?$/', $t, $m)) {
                    $fields['birth_date_day'] = str_pad($m[1], 2, '0', STR_PAD_LEFT);
                }
                // "May 2002" → month + year
                if (preg_match('/([A-Za-z]+)\s+(\d{4})/', $t, $m)) {
                    $norm = $this->normalizeMonth($m[1]);
                    if (in_array(strtolower($norm), $validMonths)) {
                        $fields['birth_date_month'] = $norm;
                    }
                    $fields['birth_date_year'] = $m[2];
                }
            }
            Log::info('Spatial: birth date', [
                'day' => $fields['birth_date_day'], 'month' => $fields['birth_date_month'], 'year' => $fields['birth_date_year']
            ]);
        }
    }

    // ── 3. PLACE OF BIRTH ──────────────────────────────────────────
    if ($isBad($fields['birth_place_institution'] ?? '', ['appeopriete', 'antwer', 'betore', 'before'])) {
        $pobY  = $findAnchorY(['PLACE OF']);
        $typeY = $findAnchorY(['TYPE OF BIRTH', '5a.']);

        if ($pobY !== null) {
            $bottom = $typeY ?? ($pobY + 80);
            // Value boxes live between the label sub-headers (y ≈ pobY+35) and TYPE OF BIRTH
            $valStart = $pobY + 35;
            $pobBoxes = $findInRegion(($valStart + $bottom) / 2, ($bottom - $valStart) / 2,
                $allXMax * 0.15, $allXMax * 0.85, $excl);
            $pobBoxes = $filterDataBoxes($pobBoxes);

            if (!empty($pobBoxes)) {
                $fullPlace = implode(', ', array_map(fn($b) => $b['text'], $pobBoxes));
                // Try to separate institution, city, province by commas
                $parts = array_map('trim', preg_split('/,/', $fullPlace));
                $parts = array_filter($parts, fn($p) => strlen($p) >= 2);
                $parts = array_values($parts);

                $fields['birth_place_institution'] = $parts[0] ?? '';
                $fields['birth_place_city']        = $parts[1] ?? '';
                $fields['birth_place_province']    = implode(', ', array_slice($parts, 2));
            }

            // Fallback: use header Province if birth province is empty
            if (empty($fields['birth_place_province'])) {
                $provY = $findAnchorY(['Province']);
                if ($provY !== null) {
                    $provBoxes = $findInRegion($provY, 20, $allXMax * 0.2, $allXMax * 0.55,
                        ['Province', 'Registry', 'CITY']);
                    $provBoxes = $filterDataBoxes($provBoxes);
                    if (!empty($provBoxes)) {
                        $fields['birth_place_province'] = implode(' ', array_map(fn($b) => $b['text'], $provBoxes));
                    }
                }
            }
            Log::info('Spatial: place of birth', [
                'institution' => $fields['birth_place_institution'],
                'city'        => $fields['birth_place_city'],
                'province'    => $fields['birth_place_province'],
            ]);
        }
    }

    // ── 4. MOTHER'S MAIDEN NAME ────────────────────────────────────
    if (empty($fields['mother_first_name'])) {
        $maidenY = $findAnchorY(['MAIDEN']);
        $citY    = $findAnchorY(['7. CITIZENSHIP', 'CITIZENSHIP']);

        if ($maidenY !== null) {
            $bottom   = $citY ?? ($maidenY + 80);
            $valStart = $maidenY + 20; // below MAIDEN label row
            $motherBoxes = $findInRegion(($valStart + $bottom) / 2, ($bottom - $valStart) / 2,
                $allXMax * 0.2, $allXMax * 0.7, $excl);
            $motherBoxes = $filterDataBoxes($motherBoxes);

            if (count($motherBoxes) >= 3) {
                $fields['mother_first_name']  = $this->cleanNameField($motherBoxes[0]['text']);
                $fields['mother_middle_name'] = $this->cleanNameField($motherBoxes[1]['text']);
                $fields['mother_last_name']   = $this->cleanNameField($motherBoxes[2]['text']);
            } elseif (count($motherBoxes) === 2) {
                $fields['mother_first_name']  = $this->cleanNameField($motherBoxes[0]['text']);
                $fields['mother_middle_name'] = '';
                $fields['mother_last_name']   = $this->cleanNameField($motherBoxes[1]['text']);
            }
            Log::info('Spatial: mother name', [
                'first' => $fields['mother_first_name'], 'middle' => $fields['mother_middle_name'], 'last' => $fields['mother_last_name']
            ]);
        }
    }

    // ── 5. FATHER'S NAME ─────────────────────────────────────────
    $fatherNeedsSpatial = !$this->isLikelyNameTriplet(
        (string) ($fields['father_first_name'] ?? ''),
        (string) ($fields['father_middle_name'] ?? ''),
        (string) ($fields['father_last_name'] ?? '')
    );

    if ($fatherNeedsSpatial) {
        $fatherY = $findAnchorY(['13. NAME', '13.NAME', '13 NAME']);
        $fatherBottomY = $findAnchorY(['14. CITIZENSHIP', '14.CITIZENSHIP', '14 CITIZENSHIP', '15. RELIGION']);

        if ($fatherY === null) {
            $fatherY = $findNumberedSectionAnchorY(13, ['NAME']);
        }
        if ($fatherBottomY === null) {
            $fatherBottomY = $findNumberedSectionAnchorY(14, ['CITIZENSHIP'])
                ?? $findNumberedSectionAnchorY(15, ['RELIGION']);
        }

        if ($fatherY !== null) {
            $bottom = $fatherBottomY ?? ($fatherY + 90);
            $valStart = $fatherY + 16;

            $fatherBoxes = $findInRegion(($valStart + $bottom) / 2, ($bottom - $valStart) / 2,
                $allXMax * 0.12, $allXMax * 0.86, $excl);

            $fatherBoxes = $filterDataBoxes($fatherBoxes);
            $fatherBoxes = array_values(array_filter(
                $fatherBoxes,
                fn($b) => $this->isLikelyPersonNameValue((string)($b['text'] ?? ''))
            ));

            if (count($fatherBoxes) >= 3) {
                $fields['father_first_name'] = $this->cleanNameField($fatherBoxes[0]['text']);
                $fields['father_middle_name'] = $this->cleanNameField($fatherBoxes[1]['text']);
                $fields['father_last_name'] = $this->cleanNameField($fatherBoxes[2]['text']);
            } elseif (count($fatherBoxes) === 2) {
                $fields['father_first_name'] = $this->cleanNameField($fatherBoxes[0]['text']);
                $fields['father_middle_name'] = '';
                $fields['father_last_name'] = $this->cleanNameField($fatherBoxes[1]['text']);
            }

            if ($this->isLikelyNameTriplet(
                (string) ($fields['father_first_name'] ?? ''),
                (string) ($fields['father_middle_name'] ?? ''),
                (string) ($fields['father_last_name'] ?? '')
            )) {
                Log::info('Spatial: father name', [
                    'first' => $fields['father_first_name'],
                    'middle' => $fields['father_middle_name'],
                    'last' => $fields['father_last_name'],
                ]);
            }
        }
    }

    // ── 6. CITIZENSHIP ─────────────────────────────────────────────
    if (empty($fields['citizenship'])) {
        $citY = $findAnchorY(['CITIZENSHIP']);
        if ($citY !== null) {
            // Look BELOW the label (citY+15 center, ±20 tolerance) to avoid mother name boxes above
            $citBoxes = $findInRegion($citY + 15, 20, $allXMax * 0.2, $allXMax * 0.55,
                ['CITIZENSHIP', 'NATIONALITY', 'RELIGION', 'REUGION', 'OCCUPATION']);
            foreach ($citBoxes as $cb) {
                $t = trim($cb['text']);
                if (preg_match('/^[A-Za-z\s]+$/', $t) && strlen($t) >= 3 && $cb['conf'] > 70) {
                    $fields['citizenship'] = $t;
                    Log::info('Spatial: citizenship', ['value' => $t]);
                    break;
                }
            }
        }
    }

    // ── 7. BReN NUMBER ─────────────────────────────────────────────
    if (empty($fields['bren_number']) || strtoupper($fields['bren_number']) === 'BEST') {
        $brenY = $findAnchorY(['BReN']);
        if ($brenY !== null) {
            // Look BELOW the BReN label at similar x (right half) to avoid other IDs
            $brenBoxes = $findInRegion($brenY + 20, 30, $allXMax * 0.35, $allXMax,
                ['BEST', 'POSSIBLE', 'IMAGE', 'Documentary', 'Stamp', 'Tax', 'Paid', 'BReN']);
            foreach ($brenBoxes as $bb) {
                $t = trim($bb['text']);
                // BReN format: digit-first, contains digits+letters+hyphens, length > 5
                if (preg_match('/^\d[\dA-Za-z\-]+$/', $t) && strlen($t) > 5) {
                    $fields['bren_number'] = $t;
                    Log::info('Spatial: BReN number', ['value' => $t]);
                    break;
                }
            }
        }
    }

    // ── 8. RELIGION ──────────────────────────────────────────────
    $curRel = trim($fields['religion'] ?? '');
    if (empty($curRel) || strlen($curRel) < 3 || substr_count($curRel, "\n") > 1) {
        $relY = $findAnchorY(['RELIGION', 'REUGION']);
        if ($relY !== null) {
            // Religion values are in the right column (x > 50%) BELOW the label
            $relBoxes = $findInRegion($relY + 20, 35, $allXMax * 0.5, $allXMax * 0.95,
                ['RELIGION', 'REUGION', 'CITIZENSHIP', 'OCCUPATION']);
            $bestRel = '';
            $bestConf = 0;
            foreach ($relBoxes as $rb) {
                $t = trim($rb['text']);
                if (preg_match('/[A-Za-z]{3,}/', $t) && $rb['conf'] > $bestConf && $rb['conf'] > 75) {
                    $bestRel = $t;
                    $bestConf = $rb['conf'];
                }
            }
            if (!empty($bestRel)) {
                $fields['religion'] = $bestRel;
                Log::info('Spatial: religion', ['value' => $bestRel]);
            }
        }
    }

    Log::info('Spatial birth certificate enhancement completed', [
        'filled_fields' => count(array_filter($fields)),
    ]);
    return $fields;
}

/**
 * Spatial post-processing for death certificate fields.
 * Uses bounding-box coordinates to fix/fill fields that regex missed.
 */
private function spatialEnhanceDeathFields(array $fields, array $boxes): array
{
    if (empty($boxes)) return $fields;
    Log::info('Starting spatial enhancement for death certificate fields');

    // ── Parse boxes ──────────────────────────────────────────────
    $items = [];
    foreach ($boxes as $b) {
        $box = $b['box'] ?? [];
        if (count($box) < 4) continue;
        $xMin = min($box[0][0], $box[3][0]);
        $xMax = max($box[1][0], $box[2][0]);
        $yMin = min($box[0][1], $box[1][1]);
        $yMax = max($box[2][1], $box[3][1]);
        $items[] = [
            'text'  => trim($b['text'] ?? ''),
            'conf'  => $b['confidence'] ?? 0,
            'x_min' => $xMin, 'x_max' => $xMax,
            'x_mid' => ($xMin + $xMax) / 2,
            'y_min' => $yMin, 'y_max' => $yMax,
            'y_mid' => ($yMin + $yMax) / 2,
        ];
    }
    usort($items, fn($a, $b) => $a['y_mid'] <=> $b['y_mid']);
    if (empty($items)) return $fields;

    $allXMax = max(array_column($items, 'x_max'));

    $findAnchorY = function (array $keywords) use (&$items) {
        foreach ($items as $it) {
            $upper = strtoupper($it['text']);
            foreach ($keywords as $kw) {
                if (str_contains($upper, strtoupper($kw))) {
                    return $it['y_mid'];
                }
            }
        }
        return null;
    };

    $findInRegion = function (float $yTarget, float $yTol, float $xFrom, float $xTo, array $exclude = []) use (&$items) {
        $found = [];
        foreach ($items as $it) {
            if (abs($it['y_mid'] - $yTarget) <= $yTol
                && $it['x_mid'] >= $xFrom && $it['x_mid'] <= $xTo) {
                $skip = false;
                foreach ($exclude as $ex) {
                    if (stripos($it['text'], $ex) !== false) { $skip = true; break; }
                }
                if (!$skip) $found[] = $it;
            }
        }
        usort($found, fn($a, $b) => $a['x_min'] <=> $b['x_min']);
        return $found;
    };

    $excl = [
        'NAME', 'FIRST', 'MIDDLE', 'LAST', 'FATHER', 'MOTHER', 'MAIDEN',
        'CERTIFICATE', 'DEATH', 'PROVINCE', 'CITY', 'MUNICIPALITY',
        'REGISTRY', 'REGISTRAR', 'OFFICE', 'CIVIL', 'REPUBLIC', 'PHILIPPINES',
        'INFORMANT', 'RELATIONSHIP', 'SIGNATURE', 'ADDRESS', 'DATE',
        'FORM', 'OCRG', 'BEST', 'POSSIBLE', 'IMAGE',
    ];

    $filterDataBoxes = function (array $boxes) {
        return array_values(array_filter($boxes, function ($b) {
            $t = trim($b['text']);
            if (str_starts_with($t, '(')) return false;
            if (strlen($t) < 2) return false;
            if (preg_match('/^[\d\s.]+$/', $t)) return false;
            if (!preg_match('/[A-Za-z]{2,}/', $t)) return false;
            return true;
        }));
    };

    $joinRegion = function (array $region): string {
        return implode(' ', array_map(fn($r) => $r['text'], $region));
    };

    // ── Father's Name ────────────────────────────────────────────
    if (empty($fields['father_name'])) {
        $fatherY = $findAnchorY(['NAME OF FATHER', 'FATHER']);
        if ($fatherY !== null) {
            $fatherBoxes = $findInRegion($fatherY + 15, 30, $allXMax * 0.15, $allXMax * 0.75, $excl);
            $fatherBoxes = $filterDataBoxes($fatherBoxes);
            if (!empty($fatherBoxes)) {
                $fields['father_name'] = $joinRegion($fatherBoxes);
                Log::info('Spatial death: father name', ['value' => $fields['father_name']]);
            }
        }
    }

    // ── Mother's Maiden Name ─────────────────────────────────────
    if (empty($fields['mother_maiden_name'])) {
        $motherY = $findAnchorY(['MAIDEN NAME', 'MOTHER']);
        if ($motherY !== null) {
            $motherBoxes = $findInRegion($motherY + 15, 30, $allXMax * 0.15, $allXMax * 0.75, $excl);
            $motherBoxes = $filterDataBoxes($motherBoxes);
            if (!empty($motherBoxes)) {
                $fields['mother_maiden_name'] = $joinRegion($motherBoxes);
                Log::info('Spatial death: mother maiden name', ['value' => $fields['mother_maiden_name']]);
            }
        }
    }

    // ── Certificate Number ───────────────────────────────────────
    if (empty($fields['certificate_number'])) {
        $certY = $findAnchorY(['Certificate No', 'CERTIFICATE NO']);
        if ($certY !== null) {
            $certBoxes = $findInRegion($certY, 30, 0, $allXMax,
                ['Certificate', 'CERTIFICATE', 'Death', 'DEATH']);
            foreach ($certBoxes as $cb) {
                if (preg_match('/\d{3,}[-\dA-Za-z]*/', $cb['text'], $m)) {
                    $fields['certificate_number'] = $m[0];
                    Log::info('Spatial death: certificate number', ['value' => $fields['certificate_number']]);
                    break;
                }
            }
        }
    }

    // ── Informant Name ───────────────────────────────────────────
    if (empty($fields['informant_name'])) {
        $infY = $findAnchorY(['INFORMANT']);
        if ($infY !== null) {
            $infBoxes = $findInRegion($infY + 15, 35, $allXMax * 0.15, $allXMax * 0.7, $excl);
            $infBoxes = $filterDataBoxes($infBoxes);
            if (!empty($infBoxes)) {
                $fields['informant_name'] = $joinRegion($infBoxes);
                Log::info('Spatial death: informant name', ['value' => $fields['informant_name']]);
            }
        }
    }

    // ── Informant Relationship ───────────────────────────────────
    if (empty($fields['informant_relationship'])) {
        $relY = $findAnchorY(['Relationship', 'RELATIONSHIP']);
        if ($relY !== null) {
            $relBoxes = $findInRegion($relY + 10, 25, $allXMax * 0.15, $allXMax * 0.6,
                ['Relationship', 'RELATIONSHIP', 'deceased', 'DECEASED']);
            $relBoxes = $filterDataBoxes($relBoxes);
            if (!empty($relBoxes)) {
                $fields['informant_relationship'] = $joinRegion($relBoxes);
                Log::info('Spatial death: informant relationship', ['value' => $fields['informant_relationship']]);
            }
        }
    }

    Log::info('Spatial death certificate enhancement completed', [
        'filled_fields' => count(array_filter($fields)),
    ]);
    return $fields;
}

/**
 * Normalize month name to standard format
 */
private function normalizeMonth($month)
{
    $monthMap = [
        'jan' => 'January', 'january' => 'January',
        'feb' => 'February', 'february' => 'February',
        'mar' => 'March', 'march' => 'March',
        'apr' => 'April', 'april' => 'April',
        'may' => 'May',
        'jun' => 'June', 'june' => 'June',
        'jul' => 'July', 'july' => 'July',
        'aug' => 'August', 'august' => 'August',
        'sep' => 'September', 'september' => 'September',
        'oct' => 'October', 'october' => 'October',
        'nov' => 'November', 'november' => 'November',
        'dec' => 'December', 'december' => 'December'
    ];
    
    $monthLower = strtolower(trim($month));
    return $monthMap[$monthLower] ?? $month;
}

private function containsValidNames($text)
{
    // Look for patterns that suggest names are present
    $nameIndicators = [
        '/[A-Z][a-z]+\s+[A-Z][a-z]+/', // Capitalized words pattern
        '/(?:Name|MOTHER|FATHER|CHILD)[\s:]+[A-Z]/', // Field labels followed by caps
        '/[A-Z]{2,}\s+[A-Z]{2,}/' // All caps names
    ];
    
    foreach ($nameIndicators as $pattern) {
        if (preg_match($pattern, $text)) return true;
    }
    
    return false;
}

/**
 * Extract death certificate specific fields
 */
private function extractDeathCertificateFields($cleanedText, $rawText)
{
    Log::info('Starting enhanced death certificate field extraction with comprehensive debugging');
    
    // Add debug analysis
    $this->debugOCRText($cleanedText, $rawText);
    
    $fields = [];
    
    // Enhanced extraction with error handling
    try {
        $this->extractDeceasedName($cleanedText, $rawText, $fields);
        $this->extractDeceasedSex($cleanedText, $rawText, $fields);
        $this->extractDeathDate($cleanedText, $rawText, $fields);
        $this->extractAgeAtDeath($cleanedText, $rawText, $fields);
        $this->extractDeathPlace($cleanedText, $rawText, $fields);
        $this->extractCauseOfDeath($cleanedText, $rawText, $fields);
        $this->extractRegistryNumber($cleanedText, $rawText, $fields);
        $this->extractDeathFatherName($cleanedText, $rawText, $fields);
        $this->extractDeathMotherMaidenName($cleanedText, $rawText, $fields);
        $this->extractDeathCertificateNumber($cleanedText, $rawText, $fields);
        $this->extractDeathInformant($cleanedText, $rawText, $fields);
        
        Log::info('Death certificate extraction completed with comprehensive analysis', [
            'extracted_fields_count' => count(array_filter($fields)),
            'total_fields' => count($fields),
            'filled_fields' => array_keys(array_filter($fields)),
            'all_fields' => $fields
        ]);
        
    } catch (\Exception $e) {
        Log::error('Error during death certificate field extraction', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
    
    return $fields;
}

/**
 * Extract deceased person's name from death certificate
 */
private function extractDeceasedName($cleanedText, $rawText, &$fields)
{
    Log::info('Extracting deceased person name with enhanced death certificate patterns');
    
    // Log comprehensive text analysis
    Log::info('Name extraction text analysis', [
        'cleaned_preview' => substr($cleanedText, 0, 500),
        'raw_preview' => substr($rawText, 0, 500),
        'cleaned_length' => strlen($cleanedText),
        'raw_length' => strlen($rawText)
    ]);

    // STEP 1: Try direct pattern matching for known certificate
    if ($this->tryDirectNameMatching($cleanedText, $rawText, $fields)) {
        return;
    }
    
    // STEP 2: Enhanced structural patterns for death certificates
    $namePatterns = [
        // Pattern 1: "1. NAME" section with proper structure
        '/1\.\s*NAME[^\n]*(?:\([^)]*\)){3}[^\n]*\n\s*([A-Z]{2,20}(?:\s+[A-Z]{1,20})*)\s+([A-Z]{2,20}(?:\s+[A-Z]{1,20})*)\s+([A-Z]{2,20}(?:\s+[A-Z]{1,20})*)/s',
        
        // Pattern 2: Two-part middle name like "DE GUZMAN", "DEL ROSARIO" etc.
        '/\b([A-Z]{3,20})\s+((?:DE|DEL|DELA|DE\s+LA|SAN|SANTA|STO)\s+[A-Z]{3,20})\s+([A-Z]{3,20})\b/i',
        
        // Pattern 3: Three consecutive all-caps names after NAME section
        '/NAME[^\n]*(?:\n[^\n]*){1,3}\n\s*([A-Z]{3,20})\s+([A-Z]{2,20}(?:\s+[A-Z]{2,20})?)\s+([A-Z]{3,20})/s',
        
        // Pattern 4: Tabular structure with First/Middle/Last headers
        '/(?:First|Given).*?(?:Middle|Mid).*?(?:Last|Sur|Family)[^\n]*\n\s*([A-Z]{2,20})\s+([A-Z]{2,20}(?:\s+[A-Z]{2,20})?)\s+([A-Z]{2,20})/is',
        
        // Pattern 5: Any three consecutive valid names (broad pattern)
        '/\b([A-Z]{3,20})\s+([A-Z]{2,20}(?:\s+[A-Z]{2,20})?)\s+([A-Z]{3,20})\b/m'
    ];

    foreach ($namePatterns as $index => $pattern) {
        $texts = [$cleanedText, $rawText];
        
        foreach ($texts as $textType => $text) {
            if (preg_match($pattern, $text, $matches)) {
                Log::info("Name pattern {$index} matched in " . ($textType === 0 ? 'cleaned' : 'raw') . " text", [
                    'matches' => $matches,
                    'pattern_preview' => substr($pattern, 0, 80) . '...'
                ]);
                
                if (count($matches) >= 4) {
                    $firstName = $this->cleanNameField(trim($matches[1]));
                    $middleName = $this->cleanNameField(trim($matches[2]));
                    $lastName = $this->cleanNameField(trim($matches[3]));
                    
                    if ($this->isValidDeathCertificateName($firstName, $middleName, $lastName)) {
                        $this->setNameFields($fields, $firstName, $middleName, $lastName);
                        
                        Log::info('Name extraction successful via pattern matching', [
                            'pattern_index' => $index,
                            'text_type' => $textType === 0 ? 'cleaned' : 'raw',
                            'first' => $firstName,
                            'middle' => $middleName,
                            'last' => $lastName
                        ]);
                        return;
                    } else {
                        Log::info('Names failed validation', [
                            'first' => $firstName,
                            'middle' => $middleName,
                            'last' => $lastName,
                            'pattern' => $index
                        ]);
                    }
                }
            }
        }
    }
    
    // STEP 3: Fallback to word sequence analysis
    $this->extractNamesFromWordSequences($cleanedText, $rawText, $fields);
    
    Log::warning('Could not extract deceased name using any enhanced pattern');
}

private function extractAgeAtDeath($cleanedText, $rawText, &$fields)
{
    Log::info('Extracting age at death with comprehensive patterns');
    
    // Log text for age analysis
    Log::info('Age extraction text analysis', [
        'text_contains_age' => strpos(strtoupper($cleanedText), 'AGE') !== false,
        'text_contains_46' => strpos($cleanedText, '46') !== false,
        'sample_text' => substr($cleanedText, 0, 400)
    ]);

    // Enhanced age patterns for death certificates
    $agePatterns = [
        // Pattern 1: "AGE AT THE TIME OF DEATH" section a (1 YEAR OR ABOVE)
        '/AGE\s+AT\s+THE\s+TIME\s+OF\s+DEATH.*?(?:If\s+)?1\s+YEAR\s+OR\s+ABOVE.*?(\d{1,3})/is',
        
        // Pattern 2: Simple "46" after age section
        '/AGE\s+AT\s+THE\s+TIME\s+OF\s+DEATH[^\d]*(\d{1,3})/is',
        
        // Pattern 3: Age in tabular format
        '/(?:Age|AGE)[\s:]+(\d{1,3})(?:\s+years?)?/i',
        
        // Pattern 4: Any number near "age" keyword
        '/(?:AGE|Age)[^\d]{0,30}(\d{1,3})\b/s',
        
        // Pattern 5: Any number between 1-150 after "age" keyword
        '/\b(?:age|AGE)\b[^\d]*(\d{1,3})\b/i',
        
        // Pattern 6: Number in "1 YEAR OR ABOVE" subsection
        '/1\s+YEAR\s+OR\s+ABOVE[^\d]*(\d{1,3})/is',
        
        // Pattern 7: Age section with various formats
        '/5\.\s*AGE\s+AT\s+THE\s+TIME\s+OF\s+DEATH[^\d]*(\d{1,3})/is'
    ];

    foreach ($agePatterns as $index => $pattern) {
        $texts = [$cleanedText, $rawText];
        
        foreach ($texts as $textType => $text) {
            if (preg_match($pattern, $text, $matches)) {
                $age = trim($matches[1]);
                
                Log::info("Age pattern {$index} matched in " . ($textType === 0 ? 'cleaned' : 'raw') . " text", [
                    'age_found' => $age,
                    'matches' => $matches
                ]);
                
                if (is_numeric($age) && $age > 0 && $age <= 150) {
                    $fields['age_at_death'] = $age;
                    $fields['deceased_age'] = $age; // Also set alternative field name
                    
                    Log::info('Age at death extracted successfully', [
                        'age' => $age,
                        'pattern_index' => $index,
                        'text_type' => $textType === 0 ? 'cleaned' : 'raw'
                    ]);
                    return;
                }
            }
        }
    }
    
    Log::warning('Could not extract age at death');
}

private function tryDirectNameMatching($cleanedText, $rawText, &$fields)
{
    // Generic structural name matching - looks for name patterns near "1. NAME" section
    $structuralPatterns = [
        // Pattern: After "1. NAME" header, find 3 uppercase name parts
        '/1\.\s*NAME[^\n]*\n(?:[^\n]*\n){0,3}\s*([A-Z][A-Za-z\-]{2,20})\s{2,}([A-Z][A-Za-z\-\s]{1,25})\s{2,}([A-Z][A-Za-z\-]{2,20})/s',
        // Pattern: (First) VALUE (Middle) VALUE (Last) VALUE
        '/\(First\)[^A-Z]*([A-Z][A-Za-z\-]{2,20})[^(]*\(Middle\)[^A-Z]*([A-Z][A-Za-z\-\s]{1,25})[^(]*\(Last\)[^A-Z]*([A-Z][A-Za-z\-]{2,20})/is',
    ];
    
    $texts = [$cleanedText, $rawText];
    
    foreach ($texts as $text) {
        foreach ($structuralPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $first = trim($matches[1]);
                $middle = trim($matches[2]);
                $last = trim($matches[3]);
                
                if ($this->isValidDeathCertificateName($first, $middle, $last)) {
                    $this->setNameFields($fields, $first, $middle, $last);
                    Log::info('Structural name matching successful', [
                        'first' => $first, 'middle' => $middle, 'last' => $last
                    ]);
                    return true;
                }
            }
        }
    }
    
    return false;
}

private function isValidDeathCertificateName($first, $middle, $last)
{
    // Enhanced validation for death certificate names
    if (strlen($first) < 2 || strlen($last) < 2) return false;
    
    // Exclude obvious OCR artifacts and form text
    $excludeWords = [
        'OFFICE', 'CIVIL', 'REGISTRAR', 'REPUBLIC', 'NATIONAL', 'STATISTICS',
        'GENERAL', 'CERTIFICATE', 'REGISTRY', 'DEATH', 'BIRTH', 'MARRIAGE',
        'DOCUMENT', 'FORM', 'PAGE', 'FIRST', 'MIDDLE', 'LAST', 'NAME',
        'ACTA', 'POE', 'ENER', 'BAI', 'TOMAR', 'TOBE' // Common OCR errors
    ];
    
    $fullName = strtoupper($first . ' ' . $middle . ' ' . $last);
    
    foreach ($excludeWords as $word) {
        if (strpos($fullName, $word) !== false) {
            return false;
        }
    }
    
    // Check if names contain only valid characters
    if (!preg_match('/^[A-Za-z\s\.\-\']+$/', $fullName)) {
        return false;
    }
    
    return true;
}

private function setNameFields(&$fields, $firstName, $middleName, $lastName)
{
    $fields['name_first'] = $firstName;
    $fields['name_middle'] = $middleName;
    $fields['name_last'] = $lastName;
    $fields['deceased_first_name'] = $firstName;
    $fields['deceased_middle_name'] = $middleName;
    $fields['deceased_last_name'] = $lastName;
}

private function debugOCRText($cleanedText, $rawText)
{
    Log::info('=== COMPREHENSIVE OCR DEBUG ===');
    
    // Log character-by-character analysis of first 200 characters
    $sample = substr($rawText, 0, 200);
    $chars = [];
    for ($i = 0; $i < strlen($sample); $i++) {
        $char = $sample[$i];
        $ascii = ord($char);
        $chars[] = [
            'char' => $char,
            'ascii' => $ascii,
            'is_alpha' => ctype_alpha($char),
            'is_digit' => ctype_digit($char),
            'is_space' => ctype_space($char)
        ];
    }
    
    Log::info('Character analysis', ['chars' => array_slice($chars, 0, 50)]);
    
    // Find all word patterns
    preg_match_all('/\b\w+\b/', $rawText, $allWords);
    Log::info('All words found in raw text', [
        'total_words' => count($allWords[0]),
        'first_20_words' => array_slice($allWords[0], 0, 20)
    ]);
    
    // Look for specific patterns that might contain names
    $patterns = [
        '/1\.\s*NAME.*?\n.*?\n.*?([^\n]+)/' => '1. NAME section',
        '/NAME.*?\n.*?([^\n]+)/' => 'NAME section',
        '/([A-Z]{2,})\s+([A-Z]{2,})\s+([A-Z]{2,})/' => 'Three caps words',
        '/([A-Za-z]{3,})\s+([A-Za-z]{3,})\s+([A-Za-z]{3,})/' => 'Three mixed case words'
    ];
    
    foreach ($patterns as $pattern => $description) {
        if (preg_match($pattern, $rawText, $matches)) {
            Log::info("Pattern match found: {$description}", ['matches' => $matches]);
        }
    }
    
    Log::info('=== END OCR DEBUG ===');
}

private function isValidNameSetEnhanced($first, $middle, $last)
{
    Log::info('Validating name set', [
        'first' => $first,
        'middle' => $middle,
        'last' => $last,
        'first_length' => strlen($first),
        'last_length' => strlen($last)
    ]);
    
    // More lenient length requirements
    if (strlen($first) < 1 || strlen($last) < 1) {
        Log::info('Names too short');
        return false;
    }
    
    // Check for obvious non-names but be more permissive
    $excludeWords = [
        'OFFICE', 'CIVIL', 'REGISTRAR', 'REPUBLIC', 'NATIONAL', 'STATISTICS', 
        'GENERAL', 'CERTIFICATE', 'REGISTRY', 'BIRTH', 'DEATH', 'MARRIAGE', 
        'DOCUMENT', 'FORM', 'PAGE', 'THE', 'OF', 'AND', 'OR', 'IN', 'AT', 'TO'
    ];
    
    $fullName = strtoupper($first . ' ' . $middle . ' ' . $last);
    
    foreach ($excludeWords as $word) {
        if ($fullName === $word || strpos($fullName, $word . ' ') === 0 || strpos($fullName, ' ' . $word) !== false) {
            Log::info('Name contains excluded word', ['word' => $word, 'full_name' => $fullName]);
            return false;
        }
    }
    
    // Check if it contains reasonable characters for names
    if (!preg_match('/^[A-Za-z\s\.\-\']+$/u', $fullName)) {
        Log::info('Name contains invalid characters');
        return false;
    }
    
    // More permissive validation - accept if it looks name-like
    if (preg_match('/^[A-Za-z]{1,}(\s+[A-Za-z]{1,})*$/', $first) && 
        preg_match('/^[A-Za-z]{1,}(\s+[A-Za-z]{1,})*$/', $last)) {
        Log::info('Names passed enhanced validation');
        return true;
    }
    
    Log::info('Names failed final validation check');
    return false;
}

// Add this new fallback method:
private function extractNamesFromWordSequences($cleanedText, $rawText, &$fields)
{
    Log::info('Attempting word sequence name extraction');
    
    // Get all words that could be names
    $texts = [$cleanedText, $rawText];
    
    foreach ($texts as $text) {
        // Find all potential name words (2+ characters, starts with letter)
        if (preg_match_all('/\b[A-Za-z]{2,25}\b/', $text, $wordMatches)) {
            $words = $wordMatches[0];
            
            Log::info('Found potential name words', [
                'word_count' => count($words),
                'sample_words' => array_slice($words, 0, 10)
            ]);
            
            // Try to find sequences of 3 consecutive name-like words
            for ($i = 0; $i <= count($words) - 3; $i++) {
                $firstName = $this->cleanNameField($words[$i]);
                $middleName = $this->cleanNameField($words[$i + 1]);
                $lastName = $this->cleanNameField($words[$i + 2]);
                
                if ($this->isValidNameSetEnhanced($firstName, $middleName, $lastName)) {
                    $fields['name_first'] = $firstName;
                    $fields['name_middle'] = $middleName;
                    $fields['name_last'] = $lastName;
                    $fields['deceased_first_name'] = $firstName;
                    $fields['deceased_middle_name'] = $middleName;
                    $fields['deceased_last_name'] = $lastName;
                    
                    Log::info('Word sequence extraction successful', [
                        'position' => $i,
                        'first' => $firstName,
                        'middle' => $middleName,
                        'last' => $lastName
                    ]);
                    return;
                }
            }
        }
    }
}

// Add this fallback extraction method:

/**
 * Extract deceased person's sex
 */
private function extractDeceasedSex($cleanedText, $rawText, &$fields)
{
    Log::info('Extracting sex with enhanced patterns');

    $sexPatterns = [
        // Pattern for marked checkbox or selected option
        '/(?:SEX|Gender)[^\n]*(?:\n[^\n]*)*?([A-Z]{4,6})(?:\s*(?:x|X|√|✓|■|☑|☒))?/i',
        '/(?:x|X|√|✓|■|☑|☒)\s*(MALE|FEMALE)/i',
        '/(MALE|FEMALE)\s*(?:x|X|√|✓|■|☑|☒)/i',
        '/2\.\s*SEX[^\n]*(?:\n[^\n]*)*?(MALE|FEMALE)/is',
        '/(MALE|FEMALE)/'
    ];

    foreach ($sexPatterns as $index => $pattern) {
        if (preg_match($pattern, $cleanedText, $matches) || preg_match($pattern, $rawText, $matches)) {
            $sex = strtoupper(trim($matches[1]));
            if (in_array($sex, ['MALE', 'FEMALE'])) {
                // FIXED: Use both field formats
                $fields['sex'] = $sex;
                $fields['deceased_sex'] = $sex;
                
                Log::info('Sex extracted successfully', ['sex' => $sex, 'pattern' => $index]);
                return;
            }
        }
    }
    
    Log::warning('Could not extract sex');
}

/**
 * Extract date of death
 */


private function extractDeathDate($cleanedText, $rawText, &$fields)
{
    Log::info('Extracting date of death');

    // Enhanced patterns based on "29 AUGUST 1999" format
    $datePatterns = [
        // Pattern for "29 AUGUST 1999" format
        '/(\d{1,2})\s+(JANUARY|FEBRUARY|MARCH|APRIL|MAY|JUNE|JULY|AUGUST|SEPTEMBER|OCTOBER|NOVEMBER|DECEMBER)\s+(\d{4})/i',
        // Traditional patterns
        '/DATE\s+OF\s+DEATH.*?(\d{1,2}).*?([A-Za-z]+|\d{1,2}).*?(\d{4})/is',
        '/(\d{1,2})\s+(January|February|March|April|May|June|July|August|September|October|November|December)\s+(\d{4})/i',
        '/(\d{1,2})[\s\/-](\d{1,2})[\s\/-](\d{4})/',
        // Look for date after "DATE OF DEATH" or similar
        '/(?:DATE\s+OF\s+DEATH|DEATH\s+DATE).*?(\d{1,2})\s+([A-Z]{3,})\s+(\d{4})/is',
        // Pattern for "3. DATE OF DEATH" section
        '/3\.\s*DATE\s+OF\s+DEATH.*?(\d{1,2})\s+([A-Z]{3,})\s+(\d{4})/is'
    ];

    foreach ($datePatterns as $pattern) {
        if (preg_match($pattern, $cleanedText, $matches)) {
            $fields['death_date_day'] = trim($matches[1]);
            $fields['death_date_month'] = trim($matches[2]);
            $fields['death_date_year'] = trim($matches[3]);
            
            Log::info('Death date extracted', [
                'day' => $fields['death_date_day'],
                'month' => $fields['death_date_month'],
                'year' => $fields['death_date_year']
            ]);
            return;
        }
    }
    
    Log::warning('Could not extract death date');
}

/**
 * Extract place of death
 */
private function extractDeathPlace($cleanedText, $rawText, &$fields)
{
    // Enhanced patterns - anchored to PLACE OF DEATH section first
    $placePatterns = [
        // Pattern 1: After "PLACE OF DEATH" label, find "CITY, PROVINCE" format
        '/(?:PLACE\s+OF\s+DEATH|LUGAR)[^\n]*(?:\n[^\n]*){0,3}([A-Z][A-Za-z\s]{2,25}),\s*([A-Z][A-Za-z\s]{2,25})/is',
        // Pattern 2: Traditional "PLACE OF DEATH" followed by place text
        '/PLACE\s+OF\s+DEATH.*?([A-Z][A-Za-z\s,]+)/is',
        // Pattern 3: Section number "4. PLACE OF DEATH"
        '/4\.\s*PLACE\s+OF\s+DEATH[^\n]*\n\s*([A-Z][A-Za-z\s,.-]+)/is',
        // Pattern 4: Standalone "CITY, PROVINCE" format (only after anchored patterns fail)
        '/\b([A-Z][a-z]{3,20}),\s*([A-Z][a-z]{3,20})\b/'
    ];

    foreach ($placePatterns as $pattern) {
        if (preg_match($pattern, $cleanedText, $matches)) {
            if (count($matches) >= 3) {
                // City, Province format
                $fields['death_place_city'] = trim($matches[1]);
                $fields['death_place_province'] = trim($matches[2]);
            } else {
                $place = trim($matches[1]);
                
                // Try to split by comma
                if (strpos($place, ',') !== false) {
                    $parts = array_map('trim', explode(',', $place));
                    $fields['death_place_city'] = $parts[0] ?? '';
                    $fields['death_place_province'] = $parts[1] ?? '';
                } else {
                    $fields['death_place_institution'] = $place;
                }
            }
            
            Log::info('Death place extracted', [
                'city' => $fields['death_place_city'] ?? '',
                'province' => $fields['death_place_province'] ?? ''
            ]);
            return;
        }
    }
}

/**
 * Extract cause of death
 */
private function extractCauseOfDeath($cleanedText, $rawText, &$fields)
{
    Log::info('Extracting cause of death from sections 19b, 19c, 19d in priority order');

    // UPDATED: Check sections in priority order as per requirements
    $causePatterns = [
        // Section 19b: Primary cause (highest priority)
        '/19b\.\s*CAUSE\s+OF\s+DEATH.*?Immediate\s+cause.*?([A-Z][A-Z\s,]+?)(?:\n|Antecedent)/s',
        // Alternative 19b pattern
        '/CAUSE\s+OF\s+DEATH.*?([A-Z]{3,}(?:\s+[A-Z]{3,})*)/s',
        // Section 19c: Maternal condition (if 19b empty)
        '/19c\.\s*MATERNAL\s+CONDITION.*?pregnant\s+not\s+in\s+labour.*?([A-Z][A-Za-z\s,]+)/s',
        // Section 19d: External causes (if 19b and 19c empty)
        '/19d\.\s*DEATH\s+BY\s+EXTERNAL\s+CAUSES.*?Manner\s+of\s+Death.*?([A-Z][A-Za-z\s,]+)/s',
        // Fallback pattern for any cause
        '/(CARDIOPULMONARY\s+ARREST|CARDIAC\s+ARREST|RESPIRATORY\s+FAILURE)/i'
    ];

    foreach ($causePatterns as $pattern) {
        if (preg_match($pattern, $cleanedText, $matches)) {
            $cause = trim($matches[1]);
            if (strlen($cause) > 3) {
                $fields['cause_of_death'] = $cause;
                Log::info('Cause of death extracted: ' . $cause);
                return;
            }
        }
    }
    
    Log::warning('Could not extract cause of death from any section');
}



/**
 * Extract death registry information
 */
private function extractRegistryNumber($cleanedText, $rawText, &$fields)
{
    Log::info('Extracting registry number from Registry No. section');

    // UPDATED: Target actual "Registry No." label on certificate
    $registryPatterns = [
        // Primary pattern for "Registry No." format
        '/Registry\s+No\.\s*(\d{4}-\d{5}|\d+)/i',
        // Alternative patterns
        '/REGISTRY\s+NO\.\s*(\d{4}-\d{5}|\d+)/i',
        '/Registry\s+Number\s*:?\s*(\d{4}-\d{5}|\d+)/i',
        // Fallback pattern for any registry format
        '/(\d{4}-\d{5})/'
    ];

    foreach ($registryPatterns as $pattern) {
        if (preg_match($pattern, $cleanedText, $matches)) {
            $registryNo = trim($matches[1]);
            if (!empty($registryNo)) {
                $fields['registry_number'] = $registryNo;
                Log::info('Registry number extracted: ' . $registryNo);
                return;
            }
        }
    }
    
    Log::warning('Could not extract registry number');
}

/**
 * Extract father's name from death certificate
 */
private function extractDeathFatherName($cleanedText, $rawText, &$fields)
{
    Log::info('Extracting father name from death certificate');

    $patterns = [
        // "Name of Father" section - common on Form 103A
        '/(?:FATHER|Father).*?(?:NAME|Name)[\s\r\n:]+([A-Z][A-Za-z\s,.-]{3,60})/is',
        '/(?:Name\s+of\s+Father)[\s\r\n:]+([A-Z][A-Za-z\s,.-]{3,60})/is',
        // Section number patterns
        '/(?:10|11)\.\s*(?:NAME\s+OF\s+FATHER|FATHER)[\s\r\n:]*([A-Z][A-Za-z\s,.-]{3,60})/is',
        // Three uppercase words after FATHER
        '/FATHER[^\n]*(?:\n[^\n]*){0,2}\n\s*([A-Z]{2,20})\s+([A-Z]{2,20})\s+([A-Z]{2,20})/is',
    ];

    foreach ($patterns as $index => $pattern) {
        if (preg_match($pattern, $cleanedText, $matches)) {
            if ($index === 3 && count($matches) >= 4) {
                $fields['father_name'] = trim($matches[1] . ' ' . $matches[2] . ' ' . $matches[3]);
            } else {
                $name = trim($matches[1]);
                // Clean up: stop at newline or section boundary
                $name = preg_replace('/\n.*$/s', '', $name);
                $name = preg_replace('/\s{2,}.*$/', '', $name);
                if (strlen($name) >= 3) {
                    $fields['father_name'] = $name;
                }
            }
            if (!empty($fields['father_name'])) {
                Log::info('Death cert father name extracted: ' . $fields['father_name']);
                return;
            }
        }
    }
    Log::warning('Could not extract father name from death certificate');
}

/**
 * Extract mother's maiden name from death certificate
 */
private function extractDeathMotherMaidenName($cleanedText, $rawText, &$fields)
{
    Log::info('Extracting mother maiden name from death certificate');

    $patterns = [
        // "Mother's Maiden Name" or "Maiden Name of Mother"
        '/(?:MOTHER|Mother).*?(?:MAIDEN|Maiden).*?(?:NAME|Name)[\s\r\n:]+([A-Z][A-Za-z\s,.-]{3,60})/is',
        '/(?:MAIDEN\s+NAME\s+OF\s+MOTHER|MOTHER.*?MAIDEN\s+NAME)[\s\r\n:]+([A-Z][A-Za-z\s,.-]{3,60})/is',
        // Section number patterns
        '/(?:11|12)\.\s*(?:MAIDEN|MOTHER).*?NAME[\s\r\n:]*([A-Z][A-Za-z\s,.-]{3,60})/is',
        // Three uppercase words after MAIDEN/MOTHER
        '/(?:MOTHER|MAIDEN)[^\n]*(?:\n[^\n]*){0,2}\n\s*([A-Z]{2,20})\s+([A-Z]{2,20})\s+([A-Z]{2,20})/is',
    ];

    foreach ($patterns as $index => $pattern) {
        if (preg_match($pattern, $cleanedText, $matches)) {
            if ($index === 3 && count($matches) >= 4) {
                $fields['mother_maiden_name'] = trim($matches[1] . ' ' . $matches[2] . ' ' . $matches[3]);
            } else {
                $name = trim($matches[1]);
                $name = preg_replace('/\n.*$/s', '', $name);
                $name = preg_replace('/\s{2,}.*$/', '', $name);
                if (strlen($name) >= 3) {
                    $fields['mother_maiden_name'] = $name;
                }
            }
            if (!empty($fields['mother_maiden_name'])) {
                Log::info('Death cert mother maiden name extracted: ' . $fields['mother_maiden_name']);
                return;
            }
        }
    }
    Log::warning('Could not extract mother maiden name from death certificate');
}

/**
 * Extract certificate number from death certificate
 */
private function extractDeathCertificateNumber($cleanedText, $rawText, &$fields)
{
    Log::info('Extracting certificate number from death certificate');

    $patterns = [
        '/(?:Certificate\s+No|Certificate\s+Number|Cert\.\s*No)\.?\s*[:\s]*([A-Z0-9\-]+)/i',
        '/(?:Death\s+Certificate\s+No)\.?\s*[:\s]*([A-Z0-9\-]+)/i',
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $cleanedText, $matches)) {
            $certNo = trim($matches[1]);
            if (strlen($certNo) >= 3) {
                $fields['certificate_number'] = $certNo;
                Log::info('Death cert certificate number extracted: ' . $certNo);
                return;
            }
        }
    }
    Log::warning('Could not extract certificate number from death certificate');
}

/**
 * Extract informant name and relationship from death certificate
 */
private function extractDeathInformant($cleanedText, $rawText, &$fields)
{
    Log::info('Extracting informant from death certificate');

    // Informant name
    $namePatterns = [
        '/(?:INFORMANT|Informant)[\s\r\n]*(?:Name|NAME)?[\s\r\n:]*([A-Z][A-Za-z\s,.-]{3,60})/is',
        '/(?:Signature|SIGNATURE)[\s\r\n]+(?:Name|NAME)[\s\r\n:]*([A-Z][A-Za-z\s,.-]{3,60})/is',
        '/(?:20|21)\.\s*(?:INFORMANT)[\s\r\n:]*(?:Name)?[\s\r\n:]*([A-Z][A-Za-z\s,.-]{3,60})/is',
    ];

    foreach ($namePatterns as $pattern) {
        if (preg_match($pattern, $cleanedText, $matches)) {
            $name = trim($matches[1]);
            $name = preg_replace('/\n.*$/s', '', $name);
            $name = preg_replace('/\s{2,}.*$/', '', $name);
            if (strlen($name) >= 3) {
                $fields['informant_name'] = $name;
                Log::info('Death cert informant name extracted: ' . $name);
                break;
            }
        }
    }

    // Informant relationship
    $relPatterns = [
        '/(?:Relationship|RELATIONSHIP)\s*(?:to\s+(?:the\s+)?deceased)?[\s\r\n:]*([A-Za-z\s,.-]{3,40})/is',
        '/(?:INFORMANT)[^\n]*(?:Relationship|RELATIONSHIP)[\s\r\n:]*([A-Za-z\s,.-]{3,40})/is',
    ];

    foreach ($relPatterns as $pattern) {
        if (preg_match($pattern, $cleanedText, $matches)) {
            $rel = trim($matches[1]);
            $rel = preg_replace('/\n.*$/s', '', $rel);
            if (strlen($rel) >= 3 && strlen($rel) <= 30) {
                $fields['informant_relationship'] = $rel;
                Log::info('Death cert informant relationship extracted: ' . $rel);
                break;
            }
        }
    }
}

/**
 * Extract marriage certificate specific fields
 */
private function extractMarriageCertificateFields($text)
{
    Log::info('Starting enhanced marriage certificate field extraction', [
        'text_length' => strlen($text),
        'text_preview' => substr($text, 0, 200)
    ]);
    
    $fields = [
        // Groom information
        'groom_first_name' => '',
        'groom_middle_name' => '',
        'groom_last_name' => '',
        'groom_birth_date' => '',
        'groom_citizenship' => '',
        'groom_birth_place' => '',
        'groom_civil_status' => '',
        'groom_religion' => '',
        'groom_residence' => '',
        'groom_father_name' => '',
        'groom_mother_name' => '',
        
        // Bride information
        'bride_first_name' => '',
        'bride_middle_name' => '',
        'bride_last_name' => '',
        'bride_birth_date' => '',
        'bride_citizenship' => '',
        'bride_birth_place' => '',
        'bride_civil_status' => '',
        'bride_religion' => '',
        'bride_residence' => '',
        'bride_father_name' => '',
        'bride_mother_name' => '',
        
        // Marriage details
        'marriage_date' => '',
        'marriage_place_city' => '',
        'marriage_place_province' => '',
        'marriage_officiant' => '',
        'marriage_officiant_position' => '',
        'marriage_license_number' => '',
        
        // Witnesses
        'witness_1_name' => '',
        'witness_2_name' => '',
        
        // Registry information
        'marriage_registry_number' => '',
        'marriage_book_number' => '',
        'marriage_page_number' => '',
        'marriage_volume_number' => ''
    ];
    
    // Clean and normalize text for better pattern matching
    $cleanedText = $this->enhancedTextCleaning($text);
    $lines = explode("\n", $cleanedText);
    
    try {
        // Extract names with multiple strategies
        $this->extractMarriageNames($cleanedText, $lines, $fields);
        
        // Extract marriage date
        $this->extractMarriageDate($cleanedText, $fields);
        
        // Extract marriage location
        $this->extractMarriageLocation($cleanedText, $fields);
        
        // Extract officiant information
        $this->extractMarriageOfficiant($cleanedText, $fields);
        
        // Extract registry information
        $this->extractMarriageRegistry($cleanedText, $fields);
        
        // Extract witnesses
        $this->extractMarriageWitnesses($cleanedText, $lines, $fields);
        
        Log::info('Marriage certificate extraction completed', [
            'extracted_fields' => array_filter($fields),
            'field_count' => count(array_filter($fields))
        ]);
        
    } catch (\Exception $e) {
        Log::error('Marriage certificate field extraction failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
    
    return array_filter($fields); // Remove empty values
}

// ═══════════════════════════════════════════════════════════════════════════
// SPATIAL extraction for marriage certificates using PaddleOCR bounding boxes
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Extract marriage certificate fields using bounding-box coordinates.
 *
 * Philippine Certificate of Marriage (Form No. 97) layout:
 *   - LEFT column  = Husband/Groom
 *   - RIGHT column = Wife/Bride
 *   - Rows are labelled by anchor keywords on the far left
 *
 * Strategy:
 *   1. Build a list of {text, x_center, y_center} from PaddleOCR boxes
 *   2. Detect the midpoint X that separates groom (left) from bride (right)
 *   3. Find anchor rows (Province, Name, Date of Birth, Place of Birth, etc.)
 *   4. For each anchor row, pick the closest data box in the correct column
 *   5. Fall back to regex for fields that spatial extraction misses
 */
private function extractMarriageCertificateFieldsSpatial(string $text, array $boxes): array
{
    Log::info('Starting SPATIAL marriage certificate extraction', [
        'boxes_count' => count($boxes),
        'text_length' => strlen($text),
    ]);

    $fields = [
        'groom_first_name' => '', 'groom_middle_name' => '', 'groom_last_name' => '',
        'groom_birth_date' => '', 'groom_citizenship' => '', 'groom_birth_place' => '',
        'groom_civil_status' => '', 'groom_religion' => '', 'groom_residence' => '',
        'groom_father_name' => '', 'groom_mother_name' => '',
        'bride_first_name' => '', 'bride_middle_name' => '', 'bride_last_name' => '',
        'bride_birth_date' => '', 'bride_citizenship' => '', 'bride_birth_place' => '',
        'bride_civil_status' => '', 'bride_religion' => '', 'bride_residence' => '',
        'bride_father_name' => '', 'bride_mother_name' => '',
        'marriage_date' => '', 'marriage_place_city' => '', 'marriage_place_province' => '',
        'marriage_officiant' => '', 'marriage_officiant_position' => '',
        'marriage_license_number' => '',
        'witness_1_name' => '', 'witness_2_name' => '',
        'marriage_registry_number' => '', 'marriage_book_number' => '',
        'marriage_page_number' => '', 'marriage_volume_number' => '',
    ];

    if (empty($boxes)) {
        Log::warning('No boxes for spatial extraction — falling back to regex');
        return $this->extractMarriageCertificateFields($text);
    }

    try {
        // ── 1. Parse boxes into a workable array ─────────────────────────
        $items = [];
        foreach ($boxes as $b) {
            $box = $b['box'] ?? [];
            if (count($box) < 4) continue;
            $xMin = min($box[0][0], $box[3][0]);
            $xMax = max($box[1][0], $box[2][0]);
            $yMin = min($box[0][1], $box[1][1]);
            $yMax = max($box[2][1], $box[3][1]);
            $items[] = [
                'text'   => trim($b['text'] ?? ''),
                'conf'   => $b['confidence'] ?? 0,
                'x_min'  => $xMin,
                'x_max'  => $xMax,
                'x_mid'  => ($xMin + $xMax) / 2,
                'y_min'  => $yMin,
                'y_max'  => $yMax,
                'y_mid'  => ($yMin + $yMax) / 2,
            ];
        }
        usort($items, fn($a, $b) => $a['y_mid'] <=> $b['y_mid']);

        // ── 2. Determine the midpoint X that splits groom|bride ──────────
        // Strategy: find the largest horizontal gap in the data-dense Y band
        // (top 60% of page where tabular data lives). This is more reliable
        // than searching for "(WIFE)" / "(HUSBAND)" labels which OCR often
        // misreads or misplaces.
        $allXMax = max(array_column($items, 'x_max') ?: [1000]);
        $allYMax = max(array_column($items, 'y_max') ?: [1000]);
        $midX = $allXMax / 2; // default fallback

        // Collect x_mid values for items in the top 60% of the page
        $dataBandItems = array_filter($items, fn($it) => $it['y_mid'] < $allYMax * 0.6);
        $xMids = array_map(fn($it) => $it['x_mid'], $dataBandItems);
        sort($xMids);

        if (count($xMids) >= 4) {
            // Look for the largest gap in x_mid values that's roughly in the middle third
            $pageThirdL = $allXMax * 0.3;
            $pageThirdR = $allXMax * 0.7;
            $bestGap = 0;
            $bestMid = $midX;
            for ($i = 1; $i < count($xMids); $i++) {
                $gap = $xMids[$i] - $xMids[$i - 1];
                $gapCenter = ($xMids[$i] + $xMids[$i - 1]) / 2;
                if ($gap > $bestGap && $gapCenter > $pageThirdL && $gapCenter < $pageThirdR) {
                    $bestGap = $gap;
                    $bestMid = $gapCenter;
                }
            }
            if ($bestGap > 20) {
                $midX = $bestMid;
            }
        }
        Log::info('Spatial midpoint X', ['midX' => round($midX), 'pageWidth' => round($allXMax)]);

        // ── 3. Helper closures ───────────────────────────────────────────
        // Find items near a Y row within tolerance, in a given X range
        $findInRegion = function (float $yTarget, float $yTol, float $xFrom, float $xTo, array $exclude = []) use (&$items) {
            $found = [];
            foreach ($items as $it) {
                if (abs($it['y_mid'] - $yTarget) <= $yTol && $it['x_mid'] >= $xFrom && $it['x_mid'] <= $xTo) {
                    $skip = false;
                    foreach ($exclude as $ex) {
                        if (stripos($it['text'], $ex) !== false) { $skip = true; break; }
                    }
                    if (!$skip) $found[] = $it;
                }
            }
            usort($found, fn($a, $b) => $a['x_min'] <=> $b['x_min']);
            return $found;
        };

        // Find an anchor row Y by keyword
        $findAnchorY = function (array $keywords) use (&$items) {
            foreach ($items as $it) {
                $upper = strtoupper($it['text']);
                foreach ($keywords as $kw) {
                    if (str_contains($upper, strtoupper($kw))) {
                        return $it['y_mid'];
                    }
                }
            }
            return null;
        };

        // Join text from a region into a single string
        $joinRegion = function (array $region): string {
            return implode(' ', array_map(fn($r) => $r['text'], $region));
        };

        // Join region but filter out low-confidence and parenthetical garbage
        $joinRegionClean = function (array $region, float $minConf = 80): string {
            $parts = [];
            foreach ($region as $r) {
                $conf = $r['conf'] ?? 0;
                if ($conf < $minConf) continue;
                $t = $r['text'];
                // Skip items that are parenthetical sub-labels
                if (str_starts_with(trim($t), '(')) continue;
                // Skip items with OCR garbage symbols
                if (preg_match('/[$π|\\\\{}]/', $t)) continue;
                // Must contain at least one letter
                if (!preg_match('/[A-Za-z]/', $t)) continue;
                $parts[] = $t;
            }
            return implode(' ', $parts);
        };

        $excludeLabels = [
            'NAME', 'FIRST', 'MIDDLE', 'LAST', 'HUSBAND', 'WIFE', 'GROOM', 'BRIDE',
            'CONTRACTING', 'PARTIES', 'BIRTH', 'AGE', 'PLACE', 'SEX', 'MALE', 'FEMALE',
            'CITIZENSHIP', 'RELIGION', 'CIVIL', 'STATUS', 'RESIDENCE', 'FATHER', 'MOTHER',
            'MAIDEN', 'DATE', 'OFFICE', 'REGISTRAR', 'REPUBLIC', 'PHILIPPINES', 'CERTIFICATE',
            'MARRIAGE', 'FORM', 'REGISTRY', 'PERSON', 'CONSENT', 'GAVE', 'RELATIONSHIP',
            'POPULATON', 'REFERENCE', 'OCRG', 'BEFILLED', 'ONATIOCO', 'CERTIPY',
        ];

        // ── Compute label band boundary ──────────────────────────────────
        // Labels on Form 97 are on the far left. We look at where
        // left-margin items START (x_min), not where they end (x_max),
        // because labels like "CityMunicipality San Fernando" start at
        // x=150 but their text extends to x=400+.
        // Groom data typically starts at x > 25% of page width.
        $dataXMin = $allXMax * 0.25;
        Log::info('Spatial data boundary', [
            'dataXMin'  => round($dataXMin),
            'midX'      => round($midX),
            'pageWidth' => round($allXMax),
        ]);

        // Cap bride column: exclude far-right margin labels like "(Husband)" at x>850
        $brideXMax = $allXMax * 0.85;

        // ── 4. Extract Province / City / Registry No ─────────────────────
        // Province/City are in the label area — use special handling
        $provY = $findAnchorY(['Province']);
        if ($provY !== null) {
            // Province data is to the RIGHT of the "Province" label, same Y band
            $provRegion = $findInRegion($provY, 30, $dataXMin, $midX, ['Province', 'City', 'Municipality', 'Registry']);
            if (!empty($provRegion)) {
                $fields['marriage_place_province'] = $joinRegion($provRegion);
            }
        }

        $cityY = $findAnchorY(['City/Municipality', 'CityMunicipality', 'Municipality']);
        if ($cityY !== null) {
            // City name is embedded in the same box as the label — extract from text
            foreach ($items as $it) {
                $upper = strtoupper($it['text']);
                if (str_contains($upper, 'CITYMUNICIPALITY') || str_contains($upper, 'CITY/MUNICIPALITY')) {
                    // Extract city name after the label
                    $cityText = preg_replace('/^(?:City\/?Municipality\s*)/i', '', $it['text']);
                    $cityText = trim($cityText);
                    if (strlen($cityText) >= 2) {
                        $fields['marriage_place_city'] = $cityText;
                    }
                    break;
                }
            }
        }

        // Registry No.
        $regY = $findAnchorY(['Registry No', 'Registry']);
        if ($regY !== null) {
            $regRegion = $findInRegion($regY, 40, $midX * 0.6, $allXMax, ['Registry', 'No', 'FOR', 'OCRG', 'USE', 'ONLY']);
            foreach ($regRegion as $r) {
                if (preg_match('/\d{3,}[-\d]*/', $r['text'], $m)) {
                    $fields['marriage_registry_number'] = $m[0];
                    break;
                }
            }
        }

        // ── 5. Extract Names (Name of Contracting Parties row) ───────────
        // Find the names row — try multiple OCR-mangled variants
        $nameY = $findAnchorY(['Contracting', 'Cortracth', 'Costacthag', 'Contacting']);
        if ($nameY === null) {
            $nameY = $findAnchorY(['Partes', 'Parties', 'Partles']);
        }
        // Last resort: find the "(Husband)" label Y
        if ($nameY === null) {
            $nameY = $findAnchorY(['Husband', 'HUSBAND']);
        }

        if ($nameY !== null) {
            // Groom = left column, Bride = right column, near nameY
            $groomParts = $findInRegion($nameY, 50, $dataXMin, $midX, $excludeLabels);
            $brideParts = $findInRegion($nameY, 50, $midX, $brideXMax, $excludeLabels);
            $this->assignNameParts($groomParts, $fields, 'groom');
            $this->assignNameParts($brideParts, $fields, 'bride');
        }

        // ── 6. Row-offset extraction ─────────────────────────────────────
        // Philippine Form 97 has rows in a fixed order below the Name row.
        // The OCR mangles row labels badly, so we step by Y offset instead.
        //
        // Approximate row offsets from the Name row (y_name):
        //   +50  Date of Birth/Age
        //   +90  Place of Birth
        //   +130 Sex (skip)
        //   +170 Citizenship
        //   +210 Residence (may span 2 lines)
        //   +250 Religion
        //   +290 Civil Status
        //   +330 Name of Father
        //   +400 Name of Mother
        //
        // We also try keyword anchors first; if that fails, fall back to offset.

        if ($nameY !== null) {
            $rowStep = $this->estimateRowStep($items, $nameY, $midX);
            Log::info('Row step estimate', ['rowStep' => round($rowStep), 'nameY' => round($nameY)]);

            // Date of Birth — try anchor then offset
            $dobY = $findAnchorY(['BindAp', 'BirthAge', 'Date of Birth', 'Birth/Age', 'DoofBind']);
            if ($dobY === null) $dobY = $nameY + $rowStep * 1.3;
            $groomDob = $findInRegion($dobY, $rowStep * 0.6, $dataXMin, $midX, $excludeLabels);
            $brideDob = $findInRegion($dobY, $rowStep * 0.6, $midX, $brideXMax, $excludeLabels);
            if (!empty($groomDob)) $fields['groom_birth_date'] = $this->assembleDateFromBoxes($groomDob);
            if (!empty($brideDob)) $fields['bride_birth_date'] = $this->assembleDateFromBoxes($brideDob);

            // Place of Birth
            $pobY = $findAnchorY(['Plice of', 'Place of Birth', 'Place of 5irth', 'Place of Birta']);
            if ($pobY === null) $pobY = $nameY + $rowStep * 2.3;
            $groomPob = $findInRegion($pobY, $rowStep * 0.6, $dataXMin, $midX, $excludeLabels);
            $bridePob = $findInRegion($pobY, $rowStep * 0.6, $midX, $brideXMax, $excludeLabels);
            if (!empty($groomPob)) $fields['groom_birth_place'] = $joinRegion($groomPob);
            if (!empty($bridePob)) $fields['bride_birth_place'] = $joinRegion($bridePob);

            // Citizenship
            $citY = $findAnchorY(['CRISP', 'Citizenship', 'CRP', 'Cittzenshlp', 'CRSP']);
            if ($citY === null) $citY = $nameY + $rowStep * 4.3;
            $groomCit = $findInRegion($citY, $rowStep * 0.6, $dataXMin, $midX, $excludeLabels);
            $brideCit = $findInRegion($citY, $rowStep * 0.6, $midX, $brideXMax, $excludeLabels);
            if (!empty($groomCit)) $fields['groom_citizenship'] = $joinRegion($groomCit);
            if (!empty($brideCit)) $fields['bride_citizenship'] = $joinRegion($brideCit);

            // Residence (may span 2 lines, use wider tolerance)
            $resY = $findAnchorY(['ResoeIce', 'Residence', 'Restrlce', 'Res trIce']);
            if ($resY === null) $resY = $nameY + $rowStep * 5.3;
            $groomRes = $findInRegion($resY, $rowStep * 0.9, $dataXMin, $midX, $excludeLabels);
            $brideRes = $findInRegion($resY, $rowStep * 0.9, $midX, $brideXMax, $excludeLabels);
            if (!empty($groomRes)) $fields['groom_residence'] = $joinRegion($groomRes);
            if (!empty($brideRes)) $fields['bride_residence'] = $joinRegion($brideRes);

            // Religion
            $relY = $findAnchorY(['Reigbs', 'Religion', 'Reigion', 'Relig', 'ReigbI']);
            if ($relY === null) $relY = $nameY + $rowStep * 6.3;
            $groomRel = $findInRegion($relY, $rowStep * 0.6, $dataXMin, $midX, $excludeLabels);
            $brideRel = $findInRegion($relY, $rowStep * 0.6, $midX, $brideXMax, $excludeLabels);
            if (!empty($groomRel)) $fields['groom_religion'] = $joinRegion($groomRel);
            if (!empty($brideRel)) $fields['bride_religion'] = $joinRegion($brideRel);

            // Civil Status
            $csY = $findAnchorY(['CrEStates', 'Civil Status', 'CveStates', 'CvilStates']);
            if ($csY === null) $csY = $nameY + $rowStep * 7.3;
            $groomCs = $findInRegion($csY, $rowStep * 0.6, $dataXMin, $midX, $excludeLabels);
            $brideCs = $findInRegion($csY, $rowStep * 0.6, $midX, $brideXMax, $excludeLabels);
            if (!empty($groomCs)) $fields['groom_civil_status'] = $joinRegion($groomCs);
            if (!empty($brideCs)) $fields['bride_civil_status'] = $joinRegion($brideCs);

            // Father's Name
            $fatherY = $findAnchorY(['Name offatler', 'Name of Father', 'Father']);
            if ($fatherY === null) $fatherY = $nameY + $rowStep * 8.3;
            $groomFa = $findInRegion($fatherY, $rowStep * 0.7, $dataXMin, $midX, $excludeLabels);
            $brideFa = $findInRegion($fatherY, $rowStep * 0.7, $midX, $brideXMax, $excludeLabels);
            if (!empty($groomFa)) $fields['groom_father_name'] = $joinRegionClean($groomFa);
            if (!empty($brideFa)) $fields['bride_father_name'] = $joinRegionClean($brideFa);

            // Mother's Name
            $motherY = $findAnchorY(['Name of Motler', 'Name of Mother', 'Maiden Name', 'Mother', 'Motler']);
            if ($motherY === null) $motherY = $nameY + $rowStep * 10.2;
            $groomMo = $findInRegion($motherY, $rowStep * 0.7, $dataXMin, $midX, $excludeLabels);
            $brideMo = $findInRegion($motherY, $rowStep * 0.7, $midX, $brideXMax, $excludeLabels);
            if (!empty($groomMo)) $fields['groom_mother_name'] = $joinRegionClean($groomMo);
            if (!empty($brideMo)) $fields['bride_mother_name'] = $joinRegionClean($brideMo);
        }

        // ── 14. Marriage Date (below the table, full-width) ──────────────
        $mdY = $findAnchorY(['THIS IS TO CERTIFY', 'Date:', 'Date :', 'certify']);
        if ($mdY !== null) {
            $dateRegion = $findInRegion($mdY, 60, 0, $allXMax, ['CERTIFY', 'THIS', 'WITNESS', 'both', 'legal', 'presence']);
            foreach ($dateRegion as $d) {
                if (preg_match('/\d{1,2}\s+(?:January|February|March|April|May|June|July|August|September|October|November|December)\s+\d{4}/i', $d['text'], $m)) {
                    $fields['marriage_date'] = $m[0];
                    break;
                }
            }
        }
        // Fallback: regex on full text
        if (empty($fields['marriage_date'])) {
            $this->extractMarriageDate($text, $fields);
        }

        // ── 15. Officiant ────────────────────────────────────────────────
        $this->extractMarriageOfficiant($text, $fields);

        // ── 15b. Officiant Position / Title ──────────────────────────────
        if (empty($fields['marriage_officiant_position'])) {
            // Strategy A: search near the officiant name box for title-like text
            if (!empty($fields['marriage_officiant'])) {
                $offName = $fields['marriage_officiant'];
                // Find the officiant name box
                $offY = null;
                foreach ($items as $it) {
                    if (stripos($it['text'], substr($offName, 0, 8)) !== false) {
                        $offY = $it['y_mid'];
                        break;
                    }
                }
                if ($offY !== null) {
                    // Look within 60px below the officiant name for position text
                    // Exclude the officiant name itself and common labels
                    $posExclude = ['Signature', 'Sigaruo', 'Solemnizing', 'WITNESSES', 'WITNESS', 'WIINESSES',
                         'Registry', 'Registrar', 'CERTIFY', 'CERTIPY', 'Print Name'];
                    // Also exclude items containing the officiant's name (first 8 chars)
                    $posExclude[] = substr($offName, 0, 8);
                    $posRegion = $findInRegion($offY + 30, 50, 0, $allXMax, $posExclude);
                    foreach ($posRegion as $p) {
                        $t = trim($p['text']);
                        // Must be 3+ letters, no parenthetical labels, no prefix (Rey./Fr./Rev.),
                        // confidence > 60, and NOT just a number
                        if (preg_match('/^[A-Za-z][A-Za-z\s\.]{2,}$/', $t)
                            && !str_starts_with($t, '(')
                            && !preg_match('/^(Rey|Fr|Rev)\b/i', $t)
                            && $p['conf'] > 60) {
                            $fields['marriage_officiant_position'] = $t;
                            break;
                        }
                    }
                }
            }

            // Strategy B: anchor-based search for Position/Designation label
            if (empty($fields['marriage_officiant_position'])) {
                $posY = $findAnchorY(['Position', 'Designation', 'Dorigatio', 'RriDorigatio']);
                if ($posY !== null) {
                    $posRegion = $findInRegion($posY, 40, 0, $allXMax,
                        ['Position', 'Designation', 'Dorigatio', 'Title', 'WITNESSES', 'WITNESS', 'WIINESSES', 'Registry']);
                    foreach ($posRegion as $p) {
                        $t = trim($p['text']);
                        if (preg_match('/[A-Za-z]{3,}/', $t) && strlen($t) >= 3 && !str_starts_with($t, '(') && $p['conf'] > 60) {
                            $fields['marriage_officiant_position'] = $t;
                            break;
                        }
                    }
                }
            }

            // Strategy C: regex fallback restricted to position-like patterns only
            if (empty($fields['marriage_officiant_position'])) {
                $posPatterns = [
                    '/(?:Position|Designation|Title)\s*[:\s]+([A-Za-z\s\.]+)/i',
                    '/\b(Parish\s+Priest|Pastor|Reverend|Municipal\s+Trial\s+Court\s+Judge|City\s+Mayor|Municipal\s+Mayor)\b/i',
                ];
                foreach ($posPatterns as $pat) {
                    if (preg_match($pat, $text, $pm)) {
                        $fields['marriage_officiant_position'] = trim($pm[1]);
                        break;
                    }
                }
            }
        }

        // ── 15c. Marriage License Number ─────────────────────────────────
        if (empty($fields['marriage_license_number'])) {
            // Fuzzy anchor match for OCR-mangled 'License No' (e.g. 'Licebre N')
            $licY = $findAnchorY(['License No', 'License Number', 'LICENSE', 'Licebre', 'Licen']);
            if ($licY !== null) {
                $licRegion = $findInRegion($licY, 40, 0, $allXMax,
                    ['License', 'LICENSE', 'Licebre', 'Number', 'Marriage', 'Marzin', 'Ondoz', 'Order', 'Art', 'Decree']);
                foreach ($licRegion as $l) {
                    // Require 4+ digits to avoid false positives like 'No. 209' (article references)
                    if (preg_match('/\d{4,}[-\d]*/', $l['text'], $lm)) {
                        $fields['marriage_license_number'] = $lm[0];
                        break;
                    }
                }
            }
        }

        // ── 16. Witnesses ────────────────────────────────────────────────
        // Include OCR-mangled variants: WIINESSES, WIINESSES, etc.
        $witY = $findAnchorY(['WITNESSES', 'WITNESS', 'WIINESSES', 'WIINESS', 'WITNES']);
        if ($witY !== null) {
            $witRegion = $findInRegion($witY, 60, 0, $allXMax, ['WITNESSES', 'WITNESS', 'WIINESSES', 'WIINESS', 'signed', 'Religious', 'Expiration']);
            $witNames = [];
            foreach ($witRegion as $w) {
                // Accept name-looking text (at least 2 words, no numbers)
                if (preg_match('/^[A-Za-z\s\.]+$/', $w['text']) && str_word_count($w['text']) >= 2) {
                    $witNames[] = $w['text'];
                }
            }
            if (isset($witNames[0])) $fields['witness_1_name'] = $witNames[0];
            if (isset($witNames[1])) $fields['witness_2_name'] = $witNames[1];
        }
        if (empty($fields['witness_1_name'])) {
            $lines = explode("\n", $text);
            $this->extractMarriageWitnesses($text, $lines, $fields);
        }

        // ── 17. Registry info via regex fallback ─────────────────────────
        // Always run to pick up license, book, page, volume numbers
        $this->extractMarriageRegistry($text, $fields);

        $filledCount = count(array_filter($fields));
        Log::info('Spatial marriage extraction completed', [
            'filled_fields' => $filledCount,
            'total_fields'  => count($fields),
            'fields'        => array_filter($fields),
        ]);

    } catch (\Exception $e) {
        Log::error('Spatial marriage extraction failed, falling back to regex', [
            'error' => $e->getMessage(),
        ]);
        return $this->extractMarriageCertificateFields($text);
    }

    return array_filter($fields);
}

/**
 * Split an array of box items into first / middle / last name parts.
 * Assigns to $fields['{prefix}_first_name'] etc.
 */
private function assignNameParts(array $parts, array &$fields, string $prefix): void
{
    // Filter out OCR noise: parenthetical sub-labels, low confidence, short junk
    $names = [];
    foreach ($parts as $p) {
        $t = trim($p['text'], " .\t\n\r()");
        $conf = $p['conf'] ?? 0;

        // Skip items with confidence < 85 (garbage sub-labels tend to be < 80)
        if ($conf < 85) continue;

        // Skip text starting with '(' or containing special chars common in OCR noise
        if (str_starts_with($p['text'], '(')) continue;
        if (preg_match('/[{}$π|\\\\]/', $t)) continue;

        // Must have at least 1 ASCII letter and be >= 1 char
        if (strlen($t) < 1 || !preg_match('/[A-Za-z]/', $t)) continue;

        // Skip pure numbers
        if (preg_match('/^\d+$/', $t)) continue;

        $names[] = $t;
    }

    if (empty($names)) return;

    // If only one box with full name, split by space
    if (count($names) === 1 && str_word_count($names[0]) >= 2) {
        $words = preg_split('/\s+/', $names[0]);
        $names = $words;
    }

    // Assign: first, middle (if 3+), last
    // Use cleanNameField for multi-char names, but preserve initials (1-2 chars like "A", "C")
    $cleanOrPreserve = function(string $n): string {
        // If it's an initial (1-2 chars, all letters), preserve with period
        if (preg_match('/^[A-Za-z]{1,2}$/', $n)) {
            return strtoupper($n) . '.';
        }
        return $this->cleanNameField($n);
    };

    if (count($names) >= 3) {
        $fields["{$prefix}_first_name"]  = $this->cleanNameField($names[0]);
        $fields["{$prefix}_middle_name"] = $cleanOrPreserve($names[1]);
        $fields["{$prefix}_last_name"]   = $this->cleanNameField($names[count($names) - 1]);
    } elseif (count($names) === 2) {
        $fields["{$prefix}_first_name"] = $this->cleanNameField($names[0]);
        $fields["{$prefix}_last_name"]  = $this->cleanNameField($names[1]);
    } else {
        $fields["{$prefix}_first_name"] = $this->cleanNameField($names[0]);
    }
}

/**
 * Assemble a date string from nearby boxes that may contain day, month, year parts.
 */
private function assembleDateFromBoxes(array $dateBoxes): string
{
    // Try to find a full date in any single box first
    foreach ($dateBoxes as $d) {
        if (preg_match('/\d{1,2}\s+(?:January|February|March|April|May|June|July|August|September|October|November|December)\s+\d{4}/i', $d['text'], $m)) {
            return $m[0];
        }
        if (preg_match('/\d{1,2}[-\/]\d{1,2}[-\/]\d{4}/', $d['text'], $m)) {
            return $m[0];
        }
    }

    // Concatenate all box text and try to find a date in the combined string
    $combined = implode(' ', array_map(fn($d) => $d['text'], $dateBoxes));
    if (preg_match('/(\d{1,2})\s+(January|February|March|April|May|June|July|August|September|October|November|December)\s+(\d{4})/i', $combined, $m)) {
        return $m[0];
    }
    if (preg_match('/(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})/', $combined, $m)) {
        return $m[0];
    }

    // Return the raw concatenation if no pattern matched
    return trim($combined);
}

/**
 * Estimate the row step (vertical spacing) from the data boxes.
 *
 * Looks at left-margin labels (x < 20% of page width) near the name row,
 * computes the median vertical distance between consecutive labels.
 * Falls back to 40px if not enough labels found.
 */
private function estimateRowStep(array $items, float $nameY, float $midX): float
{
    // Collect left-margin label Y values (x_mid < 25% of midX)
    $labelYs = [];
    $labelXThreshold = $midX * 0.5; // labels are on the far left

    foreach ($items as $it) {
        if ($it['x_mid'] < $labelXThreshold && $it['y_mid'] > $nameY - 20 && $it['y_mid'] < $nameY + 500) {
            $labelYs[] = $it['y_mid'];
        }
    }

    sort($labelYs);

    // Compute gaps between consecutive labels
    $gaps = [];
    for ($i = 1; $i < count($labelYs); $i++) {
        $gap = $labelYs[$i] - $labelYs[$i - 1];
        if ($gap > 15 && $gap < 80) { // reasonable row gap
            $gaps[] = $gap;
        }
    }

    if (count($gaps) >= 3) {
        sort($gaps);
        $median = $gaps[intdiv(count($gaps), 2)];
        return $median;
    }

    // Default: estimate from page height
    return 40.0;
}

/**
 * Extract marriage names with enhanced patterns
 */
private function extractMarriageNames($text, $lines, &$fields)
{
    Log::info('Extracting marriage names from text');
    
    // Clean and normalize text for better pattern matching
    $cleanedText = $this->enhancedTextCleaning($text);
    $lines = explode("\n", $cleanedText);
    
    Log::info('Marriage name extraction debug', [
        'text_length' => strlen($text),
        'lines_count' => count($lines),
        'text_sample' => substr($text, 0, 300)
    ]);
    
    // Pattern 1: Look for consecutive name-like words that could be groom/bride
    $nameLines = [];
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Skip short lines and common headers
        if (strlen($line) < 5 || $this->isMarriageFormHeader($line)) {
            continue;
        }
        
        // Look for lines with potential names — supports both Mixed Case and ALL CAPS
        // Mixed case: "Mark Anthony Dalog"
        if (preg_match('/^([A-Z][a-z]+(?:\s+[A-Z][a-z]*)*)\s+([A-Z][a-z]+(?:\s+[A-Z][a-z]*)*)\s*([A-Z][a-z]+(?:\s+[A-Z][a-z]*)*)?\s*$/', $line, $matches)) {
            $nameLines[] = [
                'line' => $line,
                'first' => trim($matches[1]),
                'middle' => isset($matches[2]) ? trim($matches[2]) : '',
                'last' => isset($matches[3]) ? trim($matches[3]) : ''
            ];
        }
        // ALL CAPS: "MARK ANTHONY DALOG" — split by multi-space or by 3 cap words
        elseif (preg_match('/^([A-Z]{2,20}(?:\s+[A-Z]{2,20})?)\s{2,}([A-Z]{2,20}(?:\s+[A-Z]{2,20})?)\s{2,}([A-Z]{2,20})\s*$/', $line, $matches)) {
            $nameLines[] = [
                'line' => $line,
                'first' => $this->cleanNameField(trim($matches[1])),
                'middle' => $this->cleanNameField(trim($matches[2])),
                'last' => $this->cleanNameField(trim($matches[3]))
            ];
        }
        elseif (preg_match('/^([A-Z]{2,20})\s+([A-Z]{2,20})\s+([A-Z]{2,20})\s*$/', $line, $matches)) {
            $nameLines[] = [
                'line' => $line,
                'first' => $this->cleanNameField(trim($matches[1])),
                'middle' => $this->cleanNameField(trim($matches[2])),
                'last' => $this->cleanNameField(trim($matches[3]))
            ];
        }
    }
    
    Log::info('Found potential name lines', [
        'count' => count($nameLines),
        'lines' => $nameLines
    ]);
    
    // Try general name extraction
    if (empty($fields['groom_first_name']) && empty($fields['bride_first_name'])) {
        Log::info('Trying general name extraction patterns');
        
        // Enhanced patterns for marriage certificate names
        $generalPatterns = [
            // Pattern 1: Look for "GROOM" or "HUSBAND" followed by names (any case)
            '/(?:GROOM|HUSBAND|MALE)[\s\r\n]+([A-Za-z]{2,20}(?:\s+[A-Za-z]{2,20})?)\s+([A-Za-z]{2,20}(?:\s+[A-Za-z]{2,20})?)\s+([A-Za-z]{2,20})/i',
            
            // Pattern 2: Look for "BRIDE" or "WIFE" followed by names (any case)
            '/(?:BRIDE|WIFE|FEMALE)[\s\r\n]+([A-Za-z]{2,20}(?:\s+[A-Za-z]{2,20})?)\s+([A-Za-z]{2,20}(?:\s+[A-Za-z]{2,20})?)\s+([A-Za-z]{2,20})/i',
            
            // Pattern 3: Three consecutive capitalized names (mixed case)
            '/\b([A-Z][a-z]{2,15})\s+([A-Z][a-z]{2,15}(?:\s+[A-Z][a-z]{2,15})?)\s+([A-Z][a-z]{2,15})\b/',
            
            // Pattern 4: Three consecutive ALL CAPS names (common in OCR output)
            '/\b([A-Z]{3,15})\s+([A-Z]{3,15})\s+([A-Z]{3,15})\b/',
            
            // Pattern 5: Names separated by multi-space (tabular form)
            '/([A-Za-z]{2,20}(?:\s+[A-Za-z]{2,20})?)\s{2,}([A-Za-z]{2,20}(?:\s+[A-Za-z]{2,20})?)\s{2,}([A-Za-z]{2,20})/i'
        ];
        
        foreach ($generalPatterns as $index => $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                Log::info("General pattern {$index} matched", ['matches' => $matches]);
                
                if (count($matches) >= 4) {
                    $firstName = $this->cleanNameField(trim($matches[1]));
                    $middleName = $this->cleanNameField(trim($matches[2]));
                    $lastName = $this->cleanNameField(trim($matches[3]));
                    
                    if ($this->isValidMarriageName($firstName, $middleName, $lastName)) {
                        // Determine if this is groom or bride based on pattern
                        if ($index === 0 || stripos($pattern, 'GROOM') !== false || stripos($pattern, 'HUSBAND') !== false) {
                            // This is likely the groom
                            $fields['groom_first_name'] = $firstName;
                            $fields['groom_middle_name'] = $middleName;
                            $fields['groom_last_name'] = $lastName;
                            Log::info('Groom name extracted via general pattern', [
                                'first' => $firstName,
                                'middle' => $middleName,
                                'last' => $lastName
                            ]);
                        } elseif ($index === 1 || stripos($pattern, 'BRIDE') !== false || stripos($pattern, 'WIFE') !== false) {
                            // This is likely the bride
                            $fields['bride_first_name'] = $firstName;
                            $fields['bride_middle_name'] = $middleName;
                            $fields['bride_last_name'] = $lastName;
                            Log::info('Bride name extracted via general pattern', [
                                'first' => $firstName,
                                'middle' => $middleName,
                                'last' => $lastName
                            ]);
                        } else {
                            // Generic pattern - assign to groom first, then bride
                            if (empty($fields['groom_first_name'])) {
                                $fields['groom_first_name'] = $firstName;
                                $fields['groom_middle_name'] = $middleName;
                                $fields['groom_last_name'] = $lastName;
                                Log::info('Name assigned to groom (first available)');
                            } elseif (empty($fields['bride_first_name'])) {
                                $fields['bride_first_name'] = $firstName;
                                $fields['bride_middle_name'] = $middleName;
                                $fields['bride_last_name'] = $lastName;
                                Log::info('Name assigned to bride (second available)');
                            }
                        }
                        return; // Exit after first successful extraction
                    }
                }
            }
        }
        
        // Fallback: Use name lines if available
        foreach ($nameLines as $index => $nameLine) {
            if (!empty($nameLine['first'])) {
                if ($index === 0 && empty($fields['groom_first_name'])) {
                    // First name line could be groom
                    $fields['groom_first_name'] = $nameLine['first'];
                    $fields['groom_middle_name'] = $nameLine['middle'];
                    $fields['groom_last_name'] = $nameLine['last'];
                    Log::info('Groom name extracted from name lines', $nameLine);
                } elseif ($index === 1 && empty($fields['bride_first_name'])) {
                    // Second name line could be bride
                    $fields['bride_first_name'] = $nameLine['first'];
                    $fields['bride_middle_name'] = $nameLine['middle'];
                    $fields['bride_last_name'] = $nameLine['last'];
                    Log::info('Bride name extracted from name lines', $nameLine);
                }
            }
        }
    }
    
    Log::info('Marriage name extraction completed', [
        'groom_extracted' => !empty($fields['groom_first_name']),
        'bride_extracted' => !empty($fields['bride_first_name']),
        'groom_name' => ($fields['groom_first_name'] ?? '') . ' ' . ($fields['groom_middle_name'] ?? '') . ' ' . ($fields['groom_last_name'] ?? ''),
        'bride_name' => ($fields['bride_first_name'] ?? '') . ' ' . ($fields['bride_middle_name'] ?? '') . ' ' . ($fields['bride_last_name'] ?? '')
    ]);
}

/**
 * Validate if extracted names are valid for marriage certificate
 */
private function isValidMarriageName($first, $middle, $last)
{
    // Basic validation
    if (strlen($first) < 2 || strlen($last) < 2) {
        return false;
    }
    
    // Exclude obvious form text and OCR artifacts
    $excludeWords = [
        'OFFICE', 'CIVIL', 'REGISTRAR', 'REPUBLIC', 'NATIONAL', 'STATISTICS',
        'GENERAL', 'CERTIFICATE', 'REGISTRY', 'MARRIAGE', 'BIRTH', 'DEATH',
        'DOCUMENT', 'FORM', 'PAGE', 'FIRST', 'MIDDLE', 'LAST', 'NAME',
        'GROOM', 'BRIDE', 'HUSBAND', 'WIFE', 'MALE', 'FEMALE',
        'SECTION', 'NUMBER', 'DATE', 'PLACE', 'ADDRESS'
    ];
    
    $fullName = strtoupper($first . ' ' . $middle . ' ' . $last);
    
    foreach ($excludeWords as $word) {
        if (strpos($fullName, $word) !== false) {
            Log::info('Name validation failed - contains excluded word', [
                'word' => $word,
                'full_name' => $fullName
            ]);
            return false;
        }
    }
    
    // Check if names contain only valid characters
    if (!preg_match('/^[A-Za-z\s\.\-\']+$/', $fullName)) {
        Log::info('Name validation failed - invalid characters', ['full_name' => $fullName]);
        return false;
    }
    
    // Check minimum length requirements
    if (strlen(trim($first)) < 2 || strlen(trim($last)) < 2) {
        Log::info('Name validation failed - too short', [
            'first_length' => strlen(trim($first)),
            'last_length' => strlen(trim($last))
        ]);
        return false;
    }
    
    Log::info('Name validation passed', [
        'first' => $first,
        'middle' => $middle,
        'last' => $last
    ]);
    
    return true;
}



/**
 * Extract marriage date
 */
private function extractMarriageDate($text, &$fields)
{
    // Pattern for various date formats
    $datePatterns = [
        '/(\d{1,2})\s+(January|February|March|April|May|June|July|August|September|October|November|December)\s+(\d{4})/i',
        '/(\d{1,2})\s+(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+(\d{4})/i',
        '/(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})/',
        '/(\d{4})[-\/](\d{1,2})[-\/](\d{1,2})/',
        '/(?:DATE|Date).*?(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})/i'
    ];
    
    foreach ($datePatterns as $pattern) {
        if (preg_match($pattern, $text, $matches)) {
            if (count($matches) >= 4) {
                $fields['marriage_date'] = trim($matches[0]); // Return the full matched date
                Log::info('Marriage date extracted: ' . $fields['marriage_date']);
                return;
            }
        }
    }
}

/**
 * Extract marriage location
 */
private function extractMarriageLocation($text, &$fields)
{
    // Look for city/municipality and province patterns
    $locationPatterns = [
        '/(?:City|Municipality|CITY|MUNICIPALITY)[\s:]*([A-Za-z\s]+)/i',
        '/(?:Province|PROVINCE)[\s:]*([A-Za-z\s]+)/i',
        '/([A-Z][a-z]+),\s*([A-Z][a-z]+)/' // City, Province format
    ];
    
    foreach ($locationPatterns as $pattern) {
        if (preg_match($pattern, $text, $matches)) {
            if (count($matches) >= 3) {
                $fields['marriage_place_city'] = trim($matches[1]);
                $fields['marriage_place_province'] = trim($matches[2]);
            } else {
                if (stripos($pattern, 'City') !== false || stripos($pattern, 'Municipality') !== false) {
                    $fields['marriage_place_city'] = trim($matches[1]);
                } elseif (stripos($pattern, 'Province') !== false) {
                    $fields['marriage_place_province'] = trim($matches[1]);
                }
            }
            Log::info('Marriage location extracted', [
                'city' => $fields['marriage_place_city'] ?? '',
                'province' => $fields['marriage_place_province'] ?? ''
            ]);
            return;
        }
    }
}

/**
 * Extract officiant information
 */
private function extractMarriageOfficiant($text, &$fields)
{
    // Look for officiant patterns
    $officiantPatterns = [
        '/(?:solemnized by|officiant|performed by|SOLEMNIZED BY|OFFICIANT|PERFORMED BY)[\s:]*([A-Za-z\s\.]+)/i',
        '/Rev\.?\s+([A-Za-z\s]+)/i',
        '/Fr\.?\s+([A-Za-z\s]+)/i',
        '/Judge\s+([A-Za-z\s]+)/i',
        '/Pastor\s+([A-Za-z\s]+)/i'
    ];
    
    foreach ($officiantPatterns as $pattern) {
        if (preg_match($pattern, $text, $matches)) {
            $fields['marriage_officiant'] = trim($matches[1]);
            Log::info('Marriage officiant extracted: ' . $fields['marriage_officiant']);
            break;
        }
    }

    // Extract officiant position/title if not already set
    if (empty($fields['marriage_officiant_position'])) {
        $positionPatterns = [
            '/(?:Position|Designation)\s*[\/:]?\s*(?:Designation)?\s*[:\s]+([A-Za-z\s\.,-]+?)(?:\n|$)/i',
            '/(?:Position\/Designation)\s*[:\s]+([A-Za-z\s\.,-]+?)(?:\n|$)/i',
        ];
        foreach ($positionPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $pos = trim($matches[1]);
                if (strlen($pos) >= 3) {
                    $fields['marriage_officiant_position'] = $pos;
                    Log::info('Marriage officiant position extracted: ' . $pos);
                    break;
                }
            }
        }
    }
}

/**
 * Extract registry information
 */
private function extractMarriageRegistry($text, &$fields)
{
    // Look for registry number
    $registryPatterns = [
        '/(?:Registry|REGISTRY)\s+(?:No|NO)\.?\s*(\d+[-\d]*)/i',
        '/(?:License|LICENSE)\s+(?:No|NO)\.?\s*(\d+[-\d]*)/i',
        '/(?:Book|BOOK)\s+(?:No|NO)\.?\s*(\d+)/i',
        '/(?:Page|PAGE)\s+(?:No|NO)\.?\s*(\d+)/i'
    ];
    
    foreach ($registryPatterns as $pattern) {
        if (preg_match($pattern, $text, $matches)) {
            $number = trim($matches[1]);
            if (stripos($pattern, 'Registry') !== false) {
                $fields['marriage_registry_number'] = $number;
            } elseif (stripos($pattern, 'License') !== false) {
                $fields['marriage_license_number'] = $number;
            } elseif (stripos($pattern, 'Book') !== false) {
                $fields['marriage_book_number'] = $number;
            } elseif (stripos($pattern, 'Page') !== false) {
                $fields['marriage_page_number'] = $number;
            }
            Log::info('Registry information extracted: ' . $number);
        }
    }
}

/**
 * Extract witnesses
 */
private function extractMarriageWitnesses($text, $lines, &$fields)
{
    $witnessCount = 0;
    
    // Look for witness patterns in lines
    foreach ($lines as $line) {
        if (preg_match('/(?:witness|WITNESS|signed|SIGNED)[\s:]*([A-Za-z\s]+)/i', $line, $matches)) {
            $witnessName = trim($matches[1]);
            if (strlen($witnessName) > 3) {
                if ($witnessCount == 0) {
                    $fields['witness_1_name'] = $witnessName;
                    $witnessCount++;
                } elseif ($witnessCount == 1) {
                    $fields['witness_2_name'] = $witnessName;
                    break;
                }
            }
        }
    }
    
    if ($witnessCount > 0) {
        Log::info('Witnesses extracted', [
            'witness_1' => $fields['witness_1_name'] ?? '',
            'witness_2' => $fields['witness_2_name'] ?? ''
        ]);
    }
}

/**
 * Check if line is a form header to skip
 */
private function isMarriageFormHeader($line)
{
    $upperLine = strtoupper(trim($line));
    $lineLen = strlen(trim($line));
    
    // Only reject SHORT lines that are purely header text (< 30 chars)
    // Longer lines likely contain data even if they include header keywords
    if ($lineLen >= 30) {
        return false;
    }
    
    $pureHeaders = [
        'CERTIFICATE OF MARRIAGE', 'REPUBLIC OF THE PHILIPPINES',
        'OFFICE OF THE CIVIL REGISTRAR', 'CIVIL REGISTRY FORM',
        'CERTIFICATE', 'MARRIAGE', 'REPUBLIC', 'PHILIPPINES',
        'OFFICE', 'REGISTRAR', 'CIVIL', 'REGISTRY', 'FORM',
        'SECTION', 'DETAILS', 'INFORMATION'
    ];
    
    foreach ($pureHeaders as $header) {
        if ($upperLine === $header || ($lineLen < 25 && strpos($upperLine, $header) !== false)) {
            return true;
        }
    }
    return false;
}

/**
 * Extract generic document fields
 */
private function extractGenericFields($text)
{
    $fields = [];
    
    // Extract any dates
    if (preg_match_all('/\d{1,2}[\s\/-]\d{1,2}[\s\/-]\d{4}/', $text, $matches)) {
        $fields['dates_found'] = $matches[0];
    }
    
    // Extract any names (capitalized words)
    if (preg_match_all('/[A-Z][a-z]+\s+[A-Z][a-z]+/', $text, $matches)) {
        $fields['potential_names'] = array_slice($matches[0], 0, 5); // Limit to first 5
    }
    
    return $fields;
}

/**
 * Calculate confidence score based on extracted content quality
 */
private function calculateConfidenceScore($text, $fields, $documentType)
{
    $score = 0;

    // 1. Text quality score (25 points) — based on how much clean text Tesseract produced
    $textLength = strlen($text);
    $letterCount = preg_match_all('/[A-Za-z]/', $text);
    if ($textLength > 100) $score += 5;
    if ($textLength > 500) $score += 5;
    if ($textLength > 1000) $score += 5;
    if ($letterCount > 200) $score += 5;
    if ($letterCount > 500) $score += 5;

    // 2. Field extraction ratio (35 points) — proportion of fields successfully filled
    $filledFields = count(array_filter($fields, function($v) {
        return is_string($v) ? strlen(trim($v)) > 0 : !empty($v);
    }));
    $totalFields = max(count($fields), 1);
    $fieldRatio = $filledFields / $totalFields;
    $score += round($fieldRatio * 35);

    // 3. Document structure keywords (20 points) — confirms we're reading a real certificate
    $typeKeywords = match ($documentType) {
        'birth_certificate' => ['birth', 'certificate', 'republic', 'philippines', 'civil', 'name', 'sex', 'date'],
        'death_certificate' => ['death', 'certificate', 'republic', 'philippines', 'civil', 'cause', 'age', 'name'],
        'marriage_certificate' => ['marriage', 'certificate', 'republic', 'philippines', 'groom', 'bride', 'witness', 'officiant'],
        default => ['certificate', 'republic', 'philippines', 'civil', 'registry', 'name']
    };
    $foundKeywords = 0;
    foreach ($typeKeywords as $keyword) {
        if (stripos($text, $keyword) !== false) {
            $foundKeywords++;
        }
    }
    $score += round(($foundKeywords / count($typeKeywords)) * 20);

    // 4. Name detection quality (20 points) — names are the most critical fields
    $nameScore = 0;
    $nameFields = match ($documentType) {
        'birth_certificate' => ['name_first', 'name_last', 'mother_first_name', 'father_first_name'],
        'death_certificate' => ['name_first', 'deceased_first_name', 'name_last', 'deceased_last_name'],
        'marriage_certificate' => ['groom_first_name', 'groom_last_name', 'bride_first_name', 'bride_last_name'],
        default => ['name_first', 'name_last']
    };
    foreach ($nameFields as $nf) {
        if (!empty($fields[$nf]) && strlen(trim($fields[$nf])) >= 2) {
            $nameScore += 5;
        }
    }
    $score += min(20, $nameScore);

    return min(100, round($score));
}

/**
 * Calculate COVERAGE score — what proportion of *important* fields were filled.
 *
 * Unlike calculateConfidenceScore (which mixes text quality + keywords + names),
 * this is a pure measure of extraction success:
 *   filled_important_fields / total_important_fields × 100
 *
 * "Important" fields excludes things like book_number, page_number,
 * volume_number which are often absent even on perfectly read documents.
 */
private function calculateCoverageScore(array $fields, string $documentType): float
{
    // Define the CRITICAL fields per document type
    $importantFields = $this->getImportantFieldsForDocumentType($documentType, array_keys($fields));

    $filled = 0;
    foreach ($importantFields as $key) {
        if (!empty($fields[$key]) && strlen(trim((string) $fields[$key])) >= 2) {
            $filled++;
        }
    }

    $total = max(count($importantFields), 1);
    $coverage = ($filled / $total) * 100;

    Log::info('Coverage score calculated', [
        'document_type' => $documentType,
        'filled' => $filled,
        'total_important' => $total,
        'coverage' => round($coverage, 2),
    ]);

    return round($coverage, 2);
}

/**
 * Return important field keys used for extraction quality checks.
 */
private function getImportantFieldsForDocumentType(string $documentType, array $default = []): array
{
    return match ($documentType) {
        'birth_certificate' => [
            'name_first', 'name_middle', 'name_last', 'sex', 'birth_date',
            'birth_place', 'mother_first_name', 'mother_last_name',
            'father_first_name', 'father_last_name',
        ],
        'death_certificate' => [
            'name_first', 'name_last', 'deceased_first_name', 'deceased_last_name',
            'sex', 'death_date', 'death_place', 'cause_of_death', 'age_at_death',
        ],
        'marriage_certificate' => [
            'groom_first_name', 'groom_last_name',
            'bride_first_name', 'bride_last_name',
            'groom_birth_date', 'bride_birth_date',
            'groom_birth_place', 'bride_birth_place',
            'groom_citizenship', 'bride_citizenship',
            'groom_religion', 'bride_religion',
            'groom_civil_status', 'bride_civil_status',
            'groom_residence', 'bride_residence',
            'groom_father_name', 'bride_father_name',
            'groom_mother_name', 'bride_mother_name',
            'marriage_date', 'marriage_place_city',
            'marriage_officiant',
        ],
        default => $default,
    };
}

/**
 * Save processed document from OCR scan
 */
public function saveProcessedDocument(Request $request)
{
    try {
        Log::info('Save processed document initiated', [
            'user_id' => Auth::id(),
            'document_type' => $request->input('document_type')
        ]);

        // Validate the request
        $request->validate([
            'document_type' => ['required', 'string', Rule::in($this->getAllSupportedDocumentTypes())],
            'processing_mode' => 'nullable|in:ocr,manual',
            'title' => 'required|string|max:255',
            'file' => 'required|file|mimes:jpeg,jpg,png,pdf|max:10240',
            'extracted_fields' => 'nullable|string',
            'ocr_confidence' => 'nullable|numeric|min:0|max:100'
        ]);

        $documentType = $request->input('document_type');
        $processingMode = $this->resolveProcessingMode($documentType, $request->input('processing_mode'));

        // Parse extracted fields JSON if provided
        $rawExtractedFields = [];
        $extractedFieldsRaw = $request->input('extracted_fields');

        if (!empty($extractedFieldsRaw)) {
            $rawExtractedFields = json_decode($extractedFieldsRaw, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid extracted fields JSON: ' . json_last_error_msg());
            }

            if (!is_array($rawExtractedFields)) {
                $rawExtractedFields = [];
            }
        }

        if ($processingMode === 'ocr' && empty($rawExtractedFields)) {
            return response()->json([
                'success' => false,
                'message' => 'Extracted OCR fields are required for OCR-supported document types.',
            ], 422);
        }

        $cleanedExtractedFields = $processingMode === 'ocr'
            ? $this->cleanExtractedFieldsForStorage($rawExtractedFields, $documentType)
            : array_filter([
                'manual_entry' => true,
                'manually_corrected' => (bool) $request->boolean('manually_corrected', false),
                'processing_note' => 'OCR skipped for non-OCR document type',
            ] + $rawExtractedFields, static fn ($value) => $value !== null);

        $uploadedFile = $request->file('file');

        // Generate organized storage path
        $year = date('Y');
        $month = date('m');
        $day = date('d');
        $documentType = $request->input('document_type');
        
        // Generate unique document ID
        $documentIdPrefix = match($documentType) {
            'birth_certificate' => 'BIR',
            'death_certificate' => 'DEA',
            'marriage_certificate' => 'MAR',
            default => 'DOC'
        };
        
        $sequenceNumber = \App\Models\Scan::where('document_type', $documentType)
            ->whereDate('created_at', today())
            ->count() + 1;
        
        $documentId = sprintf('%s-%s-%04d', 
            $documentIdPrefix,
            date('Ymd'),
            $sequenceNumber
        );
        
        // Directory structure
        $directory = "documents/{$year}/{$month}/{$day}/{$documentType}";

        // Storage architecture rule: persist scans as PDF regardless of upload source format.
        $storedPdf = $this->storeUploadedScanAsPdf($uploadedFile, $directory, $documentId);
        $storagePath = $storedPdf['storage_path'];
        $fileMimeType = $storedPdf['mime_type'];
        $fileSize = $storedPdf['file_size'];
        $originalFilename = $storedPdf['original_filename'];
        
        // Verify file saved
        $fullPath = storage_path('app/' . $storagePath);
        
        if (!file_exists($fullPath)) {
            throw new \Exception('File upload failed - file not saved to storage');
        }
        
        Log::info('File stored successfully', [
            'storage_path' => $storagePath,
            'full_path' => $fullPath,
            'file_size' => filesize($fullPath),
            'storage_disk' => 'local',
            'final_format' => 'pdf',
            'source_mime_type' => $storedPdf['source_mime_type']
        ]);
        
        // Extract file metadata from final archived file
        $fileSize = $fileSize ?? filesize($fullPath);
        $fileMimeType = $fileMimeType ?? (mime_content_type($fullPath) ?: 'application/octet-stream');
        $originalFilename = $originalFilename ?? $uploadedFile->getClientOriginalName();

        // 🔧 VALIDATION FIX: Use CLEANED fields for validation
        $tempScan = new \App\Models\Scan([
            'document_type' => $request->input('document_type'),
            'extracted_fields' => $cleanedExtractedFields, // ✅ Use cleaned data
            'ocr_confidence' => $processingMode === 'ocr' ? $request->input('ocr_confidence', 0) : 0,
            'processing_mode' => $processingMode,
        ]);

        if ($processingMode === 'ocr') {
            $tempScan->manual_completion_score = $tempScan->calculateManualCompletionScore();
            $tempScan->validation_score = $tempScan->calculateValidationScore();
            $validationErrors = $tempScan->validateForSubmission();
        } else {
            $tempScan->manual_completion_score = 100;
            $tempScan->validation_score = 100;
            $validationErrors = [];
        }
        
        if (!empty($validationErrors)) {
            // Clean up file on validation failure
            \Illuminate\Support\Facades\Storage::disk('local')->delete($storagePath);
            if (!empty($storedPdf['preview_storage_path'])) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($storedPdf['preview_storage_path']);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Document validation failed. Please complete all required fields.',
                'errors' => $validationErrors,
                'validation_details' => [
                    'manual_completion_score' => round($tempScan->manual_completion_score, 1),
                    'validation_score' => round($tempScan->validation_score, 1),
                    'missing_fields' => $tempScan->getMissingRequiredFields(),
                ]
            ], 422);
        }

        // 🔧 DOCUMENT DATA FIX: Use CLEANED fields
        $documentData = [
            'document_id' => $documentId,
            'title' => $request->input('title'),
            'description' => $request->input(
                'description',
                $processingMode === 'ocr'
                    ? 'Document processed via OCR with manual corrections'
                    : 'Document saved via manual mode (OCR skipped)'
            ),
            'processing_mode' => $processingMode,
            'extracted_fields' => $cleanedExtractedFields, // ✅ Clean corrected data
            'processing_time' => $request->input('processing_time', 0),
            'manually_corrected' => true, // ✅ Mark as manually corrected
            'file_path' => $storagePath,
            
            // File metadata fields
            'file_size' => $fileSize,
            'file_mime_type' => $fileMimeType,
            'original_filename' => $originalFilename,
            
            // User tracking fields 
            'user_id' => Auth::id(), 
            'created_by' => Auth::id(), 
            'processed_by' => Auth::id(), 
            'processed_at' => now(),
            
            // Optional notes field 
            'notes' => $request->input('notes', null),
        ];

        // 🔧 OCR DATA FIX: Store RAW fields separately for audit trail
        $ocrResults = [
            'processing_mode' => $processingMode,
            'confidence' => $processingMode === 'ocr' ? $request->input('ocr_confidence', 0) : 0,
            'raw_text' => $processingMode === 'ocr' ? $request->input('raw_ocr_text', '') : '',
            'word_count' => $processingMode === 'ocr'
                ? count(explode(' ', $request->input('raw_ocr_text', '')))
                : 0,
            'processing_time' => $request->input('processing_time', 0),
            'extracted_fields' => $rawExtractedFields // ✅ Original OCR data with metadata
        ];

        // Save document via DocumentService
        $documentService = app(\App\Services\DocumentService::class);
        
        $result = $documentService->saveDocument(
            $documentData,
            $fullPath,
            $request->input('document_type'),
            $ocrResults
        );

        if ($result['success']) {
            $scan = null;

            if (isset($result['database_id'])) {
                $scan = \App\Models\Scan::find($result['database_id']);
                
                if ($scan) {
                    // Calculate document hash using CLEANED fields
                    $documentHash = $this->calculateDocumentHash($cleanedExtractedFields, [$storagePath]);
                    $scan->document_hash = $documentHash;
                    if (Schema::hasColumn('scans', 'processing_mode')) {
                        $scan->processing_mode = $processingMode;
                    }

                    if ($processingMode !== 'ocr') {
                        $scan->ocr_confidence = 0;
                    }
                    
                    $scan->blockchain_enabled = $request->boolean('blockchain_validation', true);
                      
                    $scan->updateValidationStatus();

                    // Respect model-driven status decision for OCR/manual flows.
                    if ($scan->verification_status === 'completed') {
                        $scan->verified_by = Auth::id();
                        $scan->verified_at = now();
                    }
                    
                    $scan->save();
                    $scan->refresh();

                    // ── Create preview image next to the final PDF ──
                    // Must run AFTER DocumentService finalizes the file path
                    // so the preview filename matches the actual PDF filename.
                    $this->ensurePreviewImageExists($scan, $uploadedFile);
                    
                    // Auto-dispatch blockchain job if eligible
                    if ($scan->isReadyForBlockchainAnchoring()) {
                        $scan->blockchain_status = 'pending';
                        $scan->blockchain_submitted_at = now();
                        $scan->save();
                        
                        \App\Jobs\AnchorToBlockchainJob::dispatch($scan);
                        
                        Log::info('Blockchain anchoring job dispatched', [
                            'scan_id' => $scan->id,
                            'document_hash' => $documentHash,
                            'storage_disk' => $scan->storage_disk
                        ]);
                    }
                    
                    Log::info('Document saved with cleaned fields', [
                        'scan_id' => $scan->id,
                        'user_id' => $scan->user_id,
                        'created_by' => $scan->created_by,
                        'processed_by' => $scan->processed_by,
                        'verified_by' => $scan->verified_by,
                        'file_size' => $scan->file_size,
                        'file_mime_type' => $scan->file_mime_type,
                        'original_filename' => $scan->original_filename,
                        'document_hash' => $scan->document_hash,
                        'blockchain_enabled' => $scan->blockchain_enabled,
                        'processing_mode' => $processingMode,
                        'manually_corrected' => true,
                        'fields_cleaned' => true
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Document saved successfully',
                'document_id' => $result['document_id'],
                'database_id' => $result['database_id'],
                'file_path' => $storagePath,
                'storage_disk' => 'local',
                'redirect_url' => route('staff.upload'),
                'verification_status' => $scan?->verification_status ?? 'pending',
                'validation_score' => (float) ($scan?->validation_score ?? 0),
                'manual_completion_score' => (float) ($scan?->manual_completion_score ?? 0),
                'ocr_confidence' => (float) ($scan?->ocr_confidence ?? 0),
                'blockchain_eligible' => (bool) ($scan?->blockchain_eligible ?? false),
                'processing_mode' => $processingMode,
                
                'populated_fields' => [
                    'user_id' => $scan?->user_id,
                    'created_by' => $scan?->created_by,
                    'processed_by' => $scan?->processed_by,
                    'verified_by' => $scan?->verified_by,
                    'verified_at' => $scan?->verified_at,
                    'file_size' => $scan?->file_size,
                    'file_mime_type' => $scan?->file_mime_type,
                    'original_filename' => $scan?->original_filename,
                    'document_hash' => $scan?->document_hash,
                    'manually_corrected' => true
                ]
            ]);

        } else {
            \Illuminate\Support\Facades\Storage::disk('local')->delete($storagePath);
            if (!empty($storedPdf['preview_storage_path'])) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($storedPdf['preview_storage_path']);
            }
            throw new \Exception($result['message'] ?? 'Failed to save document');
        }

    } catch (\Exception $e) {
        Log::error('Save processed document failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to save document: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Store uploaded scan input as a PDF file in local storage.
 */
private function storeUploadedScanAsPdf(UploadedFile $uploadedFile, string $directory, string $documentId): array
{
    $targetFileName = $documentId . '.pdf';
    $storagePath = $directory . '/' . $targetFileName;

    $sourcePath = $uploadedFile->getRealPath();
    if (!$sourcePath || !file_exists($sourcePath)) {
        throw new \RuntimeException('Uploaded file temporary path is not readable.');
    }

    $sourceMimeType = $uploadedFile->getClientMimeType()
        ?: (mime_content_type($sourcePath) ?: 'application/octet-stream');
    $sourceExtension = strtolower($uploadedFile->getClientOriginalExtension() ?: '');

    if ($sourceMimeType === 'application/pdf' || $sourceExtension === 'pdf') {
        $storedPath = $uploadedFile->storeAs($directory, $targetFileName, 'local');

        if (!$storedPath) {
            throw new \RuntimeException('Failed to store uploaded PDF scan file.');
        }

        return [
            'storage_path' => $storedPath,
            'mime_type' => 'application/pdf',
            'file_size' => Storage::disk('local')->size($storedPath),
            'original_filename' => $uploadedFile->getClientOriginalName(),
            'source_mime_type' => $sourceMimeType,
        ];
    }

    if (!str_starts_with($sourceMimeType, 'image/')) {
        throw new \RuntimeException("Unsupported scan input type '{$sourceMimeType}'.");
    }

    $pdfBinary = $this->convertImageUploadToPdfBinary($sourcePath, $sourceMimeType);
    $writeSuccess = Storage::disk('local')->put($storagePath, $pdfBinary);

    if (!$writeSuccess) {
        throw new \RuntimeException('Failed to convert and store image scan as PDF.');
    }

    // NOTE: Preview image creation is now handled by ensurePreviewImageExists()
    // in saveProcessedDocument(), AFTER DocumentService finalizes the file path.
    // This ensures the preview filename matches the actual PDF filename.

    return [
        'storage_path' => $storagePath,
        'mime_type' => 'application/pdf',
        'file_size' => Storage::disk('local')->size($storagePath),
        'original_filename' => $uploadedFile->getClientOriginalName(),
        'source_mime_type' => $sourceMimeType,
    ];
}

/**
 * Store a JPEG preview alongside the PDF for image-based scans.
 */
private function storePreviewImage(string $sourcePath, string $directory, string $documentId): ?string
{
    $sourceBytes = @file_get_contents($sourcePath);
    if ($sourceBytes === false) {
        return null;
    }

    $image = @imagecreatefromstring($sourceBytes);
    if (!$image) {
        return null;
    }

    Storage::disk('public')->makeDirectory($directory);
    $previewStoragePath = $directory . '/' . $documentId . '.jpg';

    ob_start();
    $saved = imagejpeg($image, null, 85);
    $jpegBytes = ob_get_clean();
    imagedestroy($image);

    if (!$saved || $jpegBytes === false) {
        return null;
    }

    Storage::disk('public')->put($previewStoragePath, $jpegBytes);

    return $previewStoragePath;
}

/**
 * Ensure a JPEG/PNG preview image exists next to the scan's PDF file.
 * Called AFTER DocumentService has finalized the file path so the preview
 * filename matches the actual PDF filename used in the database.
 *
 * This is used by the search thumbnail and preview drawer.
 * Print & Release continues to use the PDF.
 */
private function ensurePreviewImageExists(Scan $scan, UploadedFile $uploadedFile): void
{
    try {
        $pdfPath = $scan->full_file_path;
        if (!$pdfPath || !file_exists($pdfPath)) {
            return;
        }

        // Only needed when the stored file is a PDF
        $storedMime = mime_content_type($pdfPath);
        if ($storedMime !== 'application/pdf') {
            return; // Already an image — preview route serves it directly
        }

        $previewDir = pathinfo($pdfPath, PATHINFO_DIRNAME);
        $baseName   = pathinfo($pdfPath, PATHINFO_FILENAME);

        // Check if a preview image already exists as a sibling file
        foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
            if (file_exists($previewDir . DIRECTORY_SEPARATOR . $baseName . '.' . $ext)) {
                Log::info('Preview image already exists', [
                    'path' => $previewDir . DIRECTORY_SEPARATOR . $baseName . '.' . $ext,
                    'scan_id' => $scan->id,
                ]);
                return;
            }
        }

        // Attempt to create a JPEG preview from the uploaded source file
        $sourcePath = $uploadedFile->getRealPath();
        $sourceMime = strtolower($uploadedFile->getClientMimeType() ?: '');

        if ($sourcePath && file_exists($sourcePath) && str_starts_with($sourceMime, 'image/')) {
            // Source is an image — convert to JPEG preview
            $sourceBytes = @file_get_contents($sourcePath);
            if ($sourceBytes === false) {
                return;
            }

            $image = @imagecreatefromstring($sourceBytes);
            if (!$image) {
                return;
            }

            $previewPath = $previewDir . DIRECTORY_SEPARATOR . $baseName . '.jpg';

            ob_start();
            imagejpeg($image, null, 85);
            $jpegBytes = ob_get_clean();
            imagedestroy($image);

            if ($jpegBytes) {
                @file_put_contents($previewPath, $jpegBytes);
                Log::info('Preview image created from uploaded source', [
                    'preview_path' => $previewPath,
                    'scan_id'      => $scan->id,
                ]);
            }
        } else {
            // Source is a PDF or temp file is gone — extract embedded image from the PDF
            $this->extractPreviewImageFromPdf($pdfPath, $previewDir . DIRECTORY_SEPARATOR . $baseName);
        }
    } catch (\Exception $e) {
        Log::warning('Failed to create preview image', [
            'scan_id' => $scan->id,
            'error'   => $e->getMessage(),
        ]);
    }
}

/**
 * Extract the first embedded JPEG or PNG from a PDF and save it as a preview.
 * DomPDF embeds the original image data in the PDF stream, so we can
 * recover it with simple binary signature matching.
 */
private function extractPreviewImageFromPdf(string $pdfPath, string $outputBasePath): void
{
    $pdfBytes = @file_get_contents($pdfPath);
    if ($pdfBytes === false) {
        return;
    }

    $candidates = [
        ['ext' => 'jpg', 'regex' => '/\xFF\xD8\xFF[\s\S]*?\xFF\xD9/'],
        ['ext' => 'png', 'regex' => '/\x89PNG\x0D\x0A\x1A\x0A[\s\S]*?IEND\xAE\x42\x60\x82/'],
    ];

    foreach ($candidates as $candidate) {
        if (preg_match($candidate['regex'], $pdfBytes, $matches) === 1) {
            $previewPath = $outputBasePath . '.' . $candidate['ext'];
            @file_put_contents($previewPath, $matches[0]);
            if (file_exists($previewPath) && filesize($previewPath) > 100) {
                Log::info('Preview image extracted from PDF', [
                    'preview_path' => $previewPath,
                ]);
                return;
            }
        }
    }
}

/**
 * Convert an uploaded image file to a single-page PDF binary.
 */
private function convertImageUploadToPdfBinary(string $sourcePath, string $sourceMimeType): string
{
    $sourceBytes = file_get_contents($sourcePath);

    if ($sourceBytes === false) {
        throw new \RuntimeException('Unable to read uploaded image bytes for PDF conversion.');
    }

    $normalizedMimeType = strtolower($sourceMimeType);
    if ($normalizedMimeType === 'image/jpg') {
        $normalizedMimeType = 'image/jpeg';
    }

    if (!in_array($normalizedMimeType, ['image/jpeg', 'image/png', 'image/gif', 'image/bmp', 'image/webp', 'image/tiff'], true)) {
        $normalizedMimeType = 'image/jpeg';
    }

    $dimensions = @getimagesize($sourcePath);
    $orientation = (is_array($dimensions) && isset($dimensions[0], $dimensions[1]) && $dimensions[0] > $dimensions[1])
        ? 'landscape'
        : 'portrait';

    $imageDataUri = 'data:' . $normalizedMimeType . ';base64,' . base64_encode($sourceBytes);
    $html = '<html><body style="margin:0; padding:0;">'
        . '<img src="' . $imageDataUri . '" style="display:block; width:100%; height:auto;" />'
        . '</body></html>';

    return Pdf::loadHTML($html)
        ->setPaper('a4', $orientation)
        ->output();
}


/**
 * Clean extracted fields by removing OCR metadata and preparing for storage
 * This ensures search and preview functions work correctly
 */
private function cleanExtractedFieldsForStorage(array $extractedFields, string $documentType): array
{
    Log::info('Cleaning extracted fields for storage', [
        'document_type' => $documentType,
        'raw_field_count' => count($extractedFields),
        'raw_fields' => array_keys($extractedFields)
    ]);
    
    // Define OCR metadata fields to remove
    $ocrMetadataFields = [
        'word_count',
        'fields_detected',
        'certificate_type',  
        'confidence_score',
        'ocr_engine',
        'processing_time',
        'raw_text',
        'cleaned_text',
        'document_type',  
        'processing_method',
        'quality_metrics',
        'line_count'
    ];
    
    // Remove OCR metadata
    $cleanedFields = array_filter($extractedFields, function($key) use ($ocrMetadataFields) {
        return !in_array($key, $ocrMetadataFields);
    }, ARRAY_FILTER_USE_KEY);
    
    // Add manual correction metadata
    $cleanedFields['manually_corrected'] = true;
    $cleanedFields['corrected_at'] = now()->toDateTimeString();
    $cleanedFields['corrected_by'] = auth()->id();
    
    // Document type specific validation
    $requiredFields = $this->getRequiredFieldsForDocumentType($documentType);
    $missingFields = array_diff($requiredFields, array_keys($cleanedFields));
    
    if (!empty($missingFields)) {
        Log::warning('Missing required fields after cleaning', [
            'document_type' => $documentType,
            'missing_fields' => $missingFields
        ]);
    }
    
    Log::info('Fields cleaned successfully', [
        'cleaned_field_count' => count($cleanedFields),
        'cleaned_fields' => array_keys($cleanedFields),
        'removed_metadata_count' => count($extractedFields) - count($cleanedFields) + 3 // +3 for added metadata
    ]);
    
    return $cleanedFields;
}

/**
 * Get required fields based on document type
 */
private function getRequiredFieldsForDocumentType(string $documentType): array
{
    return match($documentType) {
        'birth_certificate' => [
            'child_first_name',
            'child_last_name',
            'mother_first_name',
            'mother_last_name',
            'father_first_name',
            'father_last_name',
            'birth_date_day',
            'birth_date_month',
            'birth_date_year'
        ],
        'death_certificate' => [
            'deceased_first_name',
            'deceased_last_name',
            'death_date',
            'age_at_death'
        ],
        'marriage_certificate' => [
            'groom_first_name',
            'groom_last_name',
            'bride_first_name',
            'bride_last_name',
            'marriage_date'
        ],
        default => []
    };
}


/**
 * API endpoint: Alias for dashboard metrics polling.
 */
public function stats(Request $request)
{
    return $this->getMetrics($request);
}

/**
 * API endpoint: Return lightweight processing status for a document.
 */
public function status($id)
{
    $scan = Scan::find($id);

    if (!$scan) {
        return response()->json([
            'success' => false,
            'message' => 'Document not found',
        ], 404);
    }

    return response()->json([
        'success' => true,
        'data' => [
            'id' => $scan->id,
            'document_id' => $scan->document_id,
            'verification_status' => $scan->verification_status,
            'blockchain_status' => $scan->blockchain_status,
            'updated_at' => $scan->updated_at?->toIso8601String(),
        ],
    ]);
}

/**
 * Get real-time metrics for dashboard
 */
public function getMetrics(Request $request)
{
    try {
        $todayStart = Carbon::today();
        
        $metrics = [
            'today_processed' => Scan::whereDate('created_at', $todayStart)->count(),
            'blockchain_confirmed' => Scan::where('blockchain_status', 'confirmed')->count(),
            'pending_verification' => Scan::where('verification_status', 'pending')->count(),
            'total_documents' => Scan::count(),
            
            // MODEL A specific metrics
            'blockchain_eligible' => Scan::where('blockchain_eligible', true)->count(),
            'needs_review' => Scan::where('blockchain_eligible', false)
                                  ->where('verification_status', '!=', 'rejected')
                                  ->count(),
            'avg_validation_score' => round(Scan::avg('validation_score') ?? 0, 2),
            'pending_blockchain' => Scan::where('blockchain_status', 'pending')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $metrics
        ]);

    } catch (\Exception $e) {
        Log::error('Failed to get metrics', ['error' => $e->getMessage()]);
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to load metrics'
        ], 500);
    }
}

/**
 * Get paginated documents for dashboard
 */
public function getDocuments(Request $request)
{
    try {
        Log::info('📥 getDocuments called', [
            'page' => $request->get('page', 1),
            'search' => $request->get('search'),
            'status_filter' => $request->get('status_filter')
        ]);

        $query = Scan::query();
        
        $selectColumns = [
            'id',
            'document_id',
            'document_type',
            'file_path',
            'extracted_fields', 
            'verification_status',
            'blockchain_status',
            'blockchain_tx_hash',
            'blockchain_confirmed_at',
            'blockchain_block_number as block_number',
            'blockchain_gas_used as gas_used',
            'ocr_confidence',
            'manual_completion_score',
            'validation_score',
            'blockchain_eligible',
            'processed_at',
            'notes',
            'processed_by',
            'verified_at',
            'verified_by',
            'reviewed_at',
            'reviewed_by',
            'user_id',
            'created_by',
            'created_at',
            'updated_at'
        ];

        if (Schema::hasColumn('scans', 'processing_mode')) {
            $selectColumns[] = 'processing_mode';
        }

        $query->select($selectColumns);

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('document_id', 'like', "%{$search}%")
                  ->orWhere('document_type', 'like', "%{$search}%");
                
                // Only search in extracted_fields if it exists
                if (\Schema::hasColumn('scans', 'extracted_fields')) {
                    $q->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(extracted_fields, '$.*')) LIKE ?", ["%{$search}%"]);
                }
            });
        }

        // Apply status filter
        if ($request->filled('status_filter') && $request->get('status_filter') !== 'all') {
            $query->where('verification_status', $request->get('status_filter'));
        }

        // Apply document type filter
        if ($request->filled('document_type') && $request->get('document_type') !== '') {
            $query->where('document_type', $request->get('document_type'));
        }

        // Order by latest first
        $query->orderBy('created_at', 'desc');

        // Paginate
        $documents = $query->paginate(12);


        $documents->getCollection()->transform(function ($doc) {
            // Generate preview text from extracted_fields
            $doc->preview_text = $this->generatePreviewText($doc);
            
            // Format dates properly
            $doc->formatted_date = $doc->created_at ? $doc->created_at->format('Y-m-d H:i:s') : null;
            
            $doc->is_ready_for_anchoring = $doc->blockchain_eligible && 
                $doc->verification_status === 'completed' && 
               !$doc->blockchain_tx_hash;
            
            return $doc;
        });

        // Calculate enhanced metrics
        $metrics = [
            'total' => Scan::count(),
            'pending' => Scan::where('verification_status', 'pending')->count(),
            'completed' => Scan::where('verification_status', 'completed')->count(),
            'blockchain_anchored' => Scan::where('blockchain_status', 'confirmed')->count(),
            'today_processed' => Scan::whereDate('created_at', today())->count(),
            'blockchain_confirmed' => Scan::where('blockchain_status', 'confirmed')->count(),
            'pending_verification' => Scan::where('verification_status', 'pending')->count(),
            'total_documents' => Scan::count(),
        ];

        Log::info('✅ getDocuments successful', [
            'documents_count' => $documents->count(),
            'total_pages' => $documents->lastPage(),
            'current_page' => $documents->currentPage()
        ]);

        return response()->json([
            'success' => true,
            'data' => $documents,
            'metrics' => $metrics
        ]);

    } catch (\Exception $e) {
        Log::error('❌ getDocuments failed', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to load documents: ' . $e->getMessage(),
            'error' => $e->getMessage()
        ], 500);
    }
}

private function generatePreviewText($scan)
{
    if (!$scan->extracted_fields) {
        return null;
    }
    
    $fields = is_string($scan->extracted_fields) 
        ? json_decode($scan->extracted_fields, true) 
        : $scan->extracted_fields;
    
    if (!is_array($fields) || empty($fields)) {
        return null;
    }
    
    $preview = [];
    
    // Extract key information based on document type
    switch ($scan->document_type) {
        case 'birth_certificate':
            if (!empty($fields['name_first'])) $preview[] = $fields['name_first'];
            if (!empty($fields['name_last'])) $preview[] = $fields['name_last'];
            if (!empty($fields['birth_date_day'])) $preview[] = $fields['birth_date_day'];
            if (!empty($fields['birth_date_month'])) $preview[] = $fields['birth_date_month'];
            if (!empty($fields['birth_date_year'])) $preview[] = $fields['birth_date_year'];
            break;
            
        case 'death_certificate':
            if (!empty($fields['deceased_first_name'])) $preview[] = $fields['deceased_first_name'];
            if (!empty($fields['deceased_last_name'])) $preview[] = $fields['deceased_last_name'];
            if (!empty($fields['death_date'])) $preview[] = 'Died: ' . $fields['death_date'];
            if (!empty($fields['age_at_death'])) $preview[] = 'Age: ' . $fields['age_at_death'];
            break;
            
        case 'marriage_certificate':
            if (!empty($fields['groom_first_name'])) $preview[] = $fields['groom_first_name'];
            if (!empty($fields['bride_first_name'])) $preview[] = '& ' . $fields['bride_first_name'];
            if (!empty($fields['marriage_date'])) $preview[] = 'Married: ' . $fields['marriage_date'];
            break;
            
        default:
            // Generic preview - get first few non-empty fields
            $preview = array_slice(array_filter(array_values($fields)), 0, 3);
    }
    
    return !empty($preview) ? implode(' ', $preview) : null;
}

public function updateField(Request $request, Scan $scan)
{
    $request->validate([
        'field_name' => 'required|string',
        'field_value' => 'nullable|string', 
    ]);

    // Get current extracted fields
    $extractedFields = is_array($scan->extracted_fields) 
        ? $scan->extracted_fields 
        : json_decode($scan->extracted_fields, true) ?? [];
    
    // Update or remove field
    if ($request->field_value === null || $request->field_value === '') {
        unset($extractedFields[$request->field_name]);
    } else {
        $extractedFields[$request->field_name] = $request->field_value;
    }
    
    $scan->extracted_fields = $extractedFields;
    
    // MODEL A: CRITICAL FIX - Recalculate validation scores after field update
    $scan->updateValidationStatus();
    
    // Log the update for debugging
    Log::info('MODEL A: Field updated', [
        'scan_id' => $scan->id,
        'field' => $request->field_name,
        'manual_completion_before' => $scan->getOriginal('manual_completion_score'),
        'manual_completion_after' => $scan->manual_completion_score,
        'validation_score' => $scan->validation_score,
        'blockchain_eligible' => $scan->blockchain_eligible,
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Field updated successfully',
        'data' => [
            'manual_completion_score' => $scan->manual_completion_score,
            'validation_score' => $scan->validation_score,
            'blockchain_eligible' => $scan->blockchain_eligible,
            'is_ready_for_anchoring' => $scan->isReadyForBlockchainAnchoring(),
        ]
    ]);
}

public function getBlockchainDetails(Scan $scan)
{
    try {
        Log::info('Fetching blockchain details', [
            'scan_id' => $scan->id,
            'blockchain_status' => $scan->blockchain_status,
            'has_tx_hash' => !empty($scan->blockchain_tx_hash)
        ]);
        
        // Load relationships
        $scan->load(['processedBy', 'reviewedBy', 'createdBy']);
        
        // Return structured response
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $scan->id,
                'document_id' => $scan->document_id,
                'document_type' => $scan->document_type,
                'blockchain_status' => $scan->blockchain_status ?? 'not_started',
                'blockchain_tx_hash' => $scan->blockchain_tx_hash,
                'blockchain_confirmed_at' => $scan->blockchain_confirmed_at 
                    ? $scan->blockchain_confirmed_at->toISOString() 
                    : null,
                'blockchain_block_number' => $scan->blockchain_block_number,
                'blockchain_gas_used' => $scan->blockchain_gas_used,
                'blockchain_network_id' => $scan->blockchain_network_id ?? 5777,
                'blockchain_confirmations' => $scan->blockchain_confirmations ?? 0,
                'processed_by' => $scan->processedBy->name ?? 'System',
                'reviewed_by' => $scan->reviewedBy->name ?? null,
                'created_at' => $scan->created_at->toISOString(),
                'updated_at' => $scan->updated_at->toISOString(),
            ]
        ]);
        
    } catch (\Exception $e) {
        Log::error('Failed to fetch blockchain details', [
            'scan_id' => $scan->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to load blockchain details: ' . $e->getMessage()
        ], 500);
    }
}


  
public function triggerBlockchainAnchoring(Request $request, $scanId)
    {
        try {
            $scan = Scan::findOrFail($scanId);
            
            // MODEL A SAFETY CHECK
            if (!$scan->isReadyForBlockchainAnchoring()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document not ready for blockchain anchoring',
                    'reason' => [
                        'validation_score' => $scan->validation_score,
                        'threshold' => Scan::VALIDATION_THRESHOLD,
                        'blockchain_eligible' => $scan->blockchain_eligible,
                        'current_blockchain_status' => $scan->blockchain_status,
                    ],
                ], 422);
            }

            // Update blockchain status to pending
            $scan->blockchain_status = 'pending';
            $scan->blockchain_submitted_at = now();
            $scan->save();

            // Dispatch blockchain job
            \App\Jobs\AnchorToBlockchainJob::dispatch($scan);

            Log::info('MODEL A: Blockchain anchoring triggered', [
                'scan_id' => $scan->id,
                'validation_score' => $scan->validation_score,
                'triggered_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Blockchain anchoring initiated',
                'scan' => [
                    'id' => $scan->id,
                    'blockchain_status' => $scan->blockchain_status,
                    'validation_score' => $scan->validation_score,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('MODEL A: Blockchain anchoring failed', [
                'scan_id' => $scanId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate blockchain anchoring',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



public function updateVerificationStatus(Request $request, Scan $scan)
{
    try {
        $request->validate([
            'status' => 'required|in:pending,completed,verified,rejected',
            'notes' => 'nullable|string|max:500'
        ]);
        
        $oldStatus = $scan->verification_status;
        $newStatus = $request->status;
        $currentUser = Auth::user();
        $userRoles = $currentUser ? $currentUser->getRoleNames()->implode(',') : 'guest';
        
        Log::info('Manual verification status update requested', [
            'scan_id' => $scan->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'user_id' => Auth::id(),
            'user_role' => $userRoles
        ]);
        
        // Update verification status
        $scan->verification_status = $newStatus;
        
        
        $isAdmin = $currentUser ? $currentUser->hasRole('admin') : false;
        
        if (in_array($newStatus, ['completed', 'verified'])) {
            // Set verification fields (staff level)
            if (!$scan->verified_at) { 
                $scan->verified_at = now();
                $scan->verified_by = Auth::id();
            }
            
            // If admin is doing this, also set review fields
            if ($isAdmin) {
                $scan->reviewed_at = now();
                $scan->reviewed_by = Auth::id();
            }
        }
        
        // If admin rejects, set review fields
        if ($newStatus === 'rejected' && $isAdmin) {
            $scan->reviewed_at = now();
            $scan->reviewed_by = Auth::id();
        }
        
        // Add verification notes if provided
        if ($request->notes) {
            // Append to existing notes with timestamp
            $existingNotes = $scan->notes ?? '';
            $timestamp = now()->format('Y-m-d H:i:s');
            $userName = $currentUser->name ?? 'Unknown User';
            $newNote = "[{$timestamp}] {$userName}: {$request->notes}";
            
            $scan->notes = $existingNotes ? "{$existingNotes}\n\n{$newNote}" : $newNote;
        }
        
        $scan->save();
        
        // Recalculate validation scores
        $scan->calculateValidationScore();
        
        Log::info('Verification status updated successfully', [
            'scan_id' => $scan->id,
            'new_status' => $scan->verification_status,
            'validation_score' => $scan->validation_score,
            'is_ready_for_anchoring' => $scan->isReadyForBlockchainAnchoring(),
            'verified_by' => $scan->verified_by,
            'verified_at' => $scan->verified_at,
            'reviewed_by' => $scan->reviewed_by,
            'reviewed_at' => $scan->reviewed_at
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Verification status updated successfully',
            'data' => [
                'verification_status' => $scan->verification_status,
                'validation_score' => $scan->validation_score,
                'blockchain_eligible' => $scan->blockchain_eligible,
                'verified_by' => $scan->verified_by,
                'verified_at' => $scan->verified_at,
                'reviewed_by' => $scan->reviewed_by,
                'reviewed_at' => $scan->reviewed_at
            ]
        ]);
        
    } catch (\Exception $e) {
        Log::error('Failed to update verification status', [
            'scan_id' => $scan->id,
            'error' => $e->getMessage()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to update verification status: ' . $e->getMessage()
        ], 500);
    }
}


public function recalculateScores(Scan $scan)
{
    try {
        $oldValidationScore = $scan->validation_score;
        $oldManualScore = $scan->manual_completion_score;
        
        // Recalculate scores
        $scan->updateValidationStatus();
        
        Log::info('MODEL A: Scores recalculated', [
            'scan_id' => $scan->id,
            'manual_score_change' => "{$oldManualScore}% → {$scan->manual_completion_score}%",
            'validation_score_change' => "{$oldValidationScore}% → {$scan->validation_score}%",
            'blockchain_eligible' => $scan->blockchain_eligible,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Validation scores recalculated successfully',
            'data' => [
                'ocr_confidence' => (float) $scan->ocr_confidence,
                'manual_completion_score' => (float) $scan->manual_completion_score,
                'validation_score' => (float) $scan->validation_score,
                'blockchain_eligible' => (bool) $scan->blockchain_eligible,
                'is_ready_for_anchoring' => $scan->isReadyForBlockchainAnchoring(),
            ]
        ]);
        
    } catch (\Exception $e) {
        Log::error('MODEL A: Score recalculation failed', [
            'scan_id' => $scan->id,
            'error' => $e->getMessage()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to recalculate scores'
        ], 500);
    }
}

private function storeImageAsPdf(string $imagePath, string $directory, string $filename): string
{
    if (!file_exists($imagePath)) {
        throw new \RuntimeException('Uploaded image file not found for PDF conversion.');
    }

    $imageData = file_get_contents($imagePath);
    if ($imageData === false) {
        throw new \RuntimeException('Failed to read uploaded image for PDF conversion.');
    }

    $imageInfo = getimagesize($imagePath);
    if ($imageInfo === false || !isset($imageInfo['mime'])) {
        throw new \RuntimeException('Invalid image provided for PDF conversion.');
    }

    $width = max((int) ($imageInfo[0] ?? 800), 1);
    $height = max((int) ($imageInfo[1] ?? 1200), 1);
    $mimeType = $imageInfo['mime'];
    $base64 = base64_encode($imageData);

    $html = "<!doctype html><html><head><meta charset=\"utf-8\"><style>@page { margin: 0; size: {$width}px {$height}px; } html, body { margin:0; padding:0; } img { width:100%; height:100%; object-fit:contain; }</style></head><body><img src=\"data:{$mimeType};base64,{$base64}\" alt=\"Scanned document\"></body></html>";

    $pdfBinary = Pdf::loadHTML($html)->output();
    $storagePath = $directory . '/' . $filename;

    if (!Storage::disk('local')->put($storagePath, $pdfBinary)) {
        throw new \RuntimeException('Failed to save converted PDF to storage.');
    }

    return $storagePath;
}

}