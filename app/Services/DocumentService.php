<?php

namespace App\Services;

use App\Models\Scan;
use App\Models\User; 
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

/**
 * DocumentService - Handles all document-related operations
 * 
 * This service centralizes document processing, saving, statistics, and validation
 * Integrates with OCR results and provides data for dashboard statistics
 */
class DocumentService
{
    /**
     * Save a processed document to the database and local storage
     * 
     * @param array $documentData - Document metadata and CLEANED extracted fields
     * @param string $imagePath - Full path to the temporary image file
     * @param string $documentType - Type of document (birth/death/marriage)
     * @param array $ocrResults - RAW OCR processing results (for audit trail)
     * @return array - Save operation results
     */
    public function saveDocument($documentData, $imagePath, $documentType, $ocrResults = [])
    {
        Log::info('DocumentService: Starting document save process', [
            'document_type' => $documentType,
            'image_path' => $imagePath,
            'image_path_type' => gettype($imagePath),
            'image_exists' => file_exists($imagePath),
            'has_data' => !empty($documentData),
            'user_id' => Auth::id(),
            'has_cleaned_fields' => isset($documentData['extracted_fields']['manually_corrected'])
        ]);
    
        try {
            // Validate inputs
            if (!is_string($imagePath)) {
                throw new \InvalidArgumentException('Image path must be a string, ' . gettype($imagePath) . ' given');
            }
    
            if (!file_exists($imagePath)) {
                throw new \InvalidArgumentException('Image file does not exist at path: ' . $imagePath);
            }
    
            // Start database transaction for data integrity
            DB::beginTransaction();
    
            // Generate unique document identifier
            $documentId = $this->generateDocumentId($documentType);
            
            // Organize file storage by date
            $storageDate = Carbon::now()->format('Y/m/d');
            $storagePath = "documents/{$storageDate}/{$documentType}";
            
            // Move uploaded file to organized storage
            $finalImagePath = $this->organizeFileStorage($imagePath, $storagePath, $documentId);
            
            // 🔧 CRITICAL: Extract CLEANED fields from document data
            $cleanedExtractedFields = $documentData['extracted_fields'] ?? [];
            
            // Create new scan record
            $document = new Scan();
            $document->document_id = $documentId;
            $document->document_type = $documentType;
            $document->title = $documentData['title'] ?? "Document - {$documentId}";
            $document->description = $documentData['description'] ?? '';
            $document->file_path = $finalImagePath;
            $document->file_hash = hash_file('sha256', storage_path('app/public/' . $finalImagePath));
            $document->ocr_confidence = $ocrResults['confidence'] ?? 0;
            if (Schema::hasColumn('scans', 'processing_mode')) {
                $document->processing_mode = in_array(($documentData['processing_mode'] ?? 'ocr'), ['ocr', 'manual'], true)
                    ? $documentData['processing_mode']
                    : 'ocr';
            }
            
            // 🔧 CRITICAL: Store CLEANED extracted_fields
            $document->extracted_fields = json_encode($cleanedExtractedFields);
            
            // 🔧 CRITICAL: Store RAW OCR data separately for audit trail
            $document->ocr_data = json_encode($ocrResults);
            
            // Set verification status based on cleaned fields
            $document->verification_status = $this->determineVerificationStatus($cleanedExtractedFields, $ocrResults);
            
            // User tracking fields
            $document->processed_by = Auth::id();
            $document->user_id = Auth::id();
            $document->created_by = Auth::id();
            $document->processed_at = Carbon::now();
    
            // File metadata
            $document->file_size = $documentData['file_size'] ?? null;
            $document->file_mime_type = $documentData['file_mime_type'] ?? null;
            $document->original_filename = $documentData['original_filename'] ?? null;
    
            // If file metadata is missing, extract from filesystem
            if (!$document->file_size || !$document->file_mime_type) {
                $fullImagePath = storage_path('app/public/' . $finalImagePath);
                
                if (file_exists($fullImagePath)) {
                    $document->file_size = $document->file_size ?? filesize($fullImagePath);
                    $document->file_mime_type = $document->file_mime_type ?? mime_content_type($fullImagePath);
                    $document->original_filename = $document->original_filename ?? basename($fullImagePath);
                    
                    Log::info('DocumentService: Extracted file metadata from filesystem', [
                        'file_size' => $document->file_size,
                        'file_mime_type' => $document->file_mime_type,
                        'original_filename' => $document->original_filename
                    ]);
                }
            }
            
            // Save to database
            $success = $document->save();
            
            if (!$success) {
                throw new \Exception('Failed to save document to database');
            }
            
            Log::info('DocumentService: Document saved successfully with cleaned fields', [
                'document_id' => $documentId,
                'database_id' => $document->id,
                'file_path' => $finalImagePath,
                'verification_status' => $document->verification_status,
                'processing_mode' => $document->processing_mode ?? 'ocr',
                'extracted_fields_type' => 'cleaned',
                'ocr_data_type' => 'raw',
                'manually_corrected' => $cleanedExtractedFields['manually_corrected'] ?? false
            ]);
    
            // Commit transaction
            DB::commit();
    
            return [
                'success' => true,
                'document_id' => $documentId,
                'database_id' => $document->id,
                'scan_id' => $document->id, // Alias for compatibility
                'file_path' => $finalImagePath,
                'verification_status' => $document->verification_status,
                'message' => 'Document saved successfully'
            ];
    
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();
            
            Log::error('DocumentService: Document save failed', [
                'error' => $e->getMessage(),
                'document_type' => $documentType,
                'image_path_type' => gettype($imagePath),
                'trace' => $e->getTraceAsString()
            ]);
    
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to save document: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get comprehensive dashboard statistics
     * 
     * @return array - Statistics for dashboard display
     */
    public function getDashboardStatistics()
{
    try {
        Log::info('DocumentService: Generating dashboard statistics');

            $statusTypeBreakdown = [
                'pending' => [],
                'completed' => [],
                'rejected' => [],
            ];

            $statusTypeRows = Scan::select('verification_status', 'document_type', DB::raw('COUNT(*) as total'))
                ->whereIn('verification_status', ['pending', 'completed', 'rejected'])
                ->groupBy('verification_status', 'document_type')
                ->get();

            foreach ($statusTypeRows as $row) {
                $status = $row->verification_status;
                if (!array_key_exists($status, $statusTypeBreakdown)) {
                    continue;
                }

                $statusTypeBreakdown[$status][$row->document_type] = (int) $row->total;
            }

            $documentTypeOrder = [
                'birth_certificate',
                'death_certificate',
                'marriage_certificate',
                'cenomar',
                'affidavit',
                'court_document',
                'contract',
                'other',
            ];
            $documentTypeOrderMap = array_flip($documentTypeOrder);

            foreach ($statusTypeBreakdown as $status => $typeCounts) {
                uksort($typeCounts, function ($a, $b) use ($documentTypeOrderMap) {
                    $aOrder = $documentTypeOrderMap[$a] ?? PHP_INT_MAX;
                    $bOrder = $documentTypeOrderMap[$b] ?? PHP_INT_MAX;

                    if ($aOrder === $bOrder) {
                        return strcmp($a, $b);
                    }

                    return $aOrder <=> $bOrder;
                });

                $statusTypeBreakdown[$status] = $typeCounts;
            }

        $stats = [
            // Total documents processed
            'total_documents' => Scan::count(),
            
            // Documents by verification status
            'validated_documents' => Scan::where('verification_status', 'completed')->count(),
            'pending_documents' => Scan::where('verification_status', 'pending')->count(),
            'rejected_documents' => Scan::where('verification_status', 'rejected')->count(),
            
            // Documents by type (computed once, aliased for both plural and singular keys)
            'birth_certificates' => ($birthCount = Scan::where('document_type', 'birth_certificate')->count()),
            'death_certificates' => ($deathCount = Scan::where('document_type', 'death_certificate')->count()),
            'marriage_certificates' => ($marriageCount = Scan::where('document_type', 'marriage_certificate')->count()),
            // Singular aliases used by dashboard Blade template
            'birth_certificate' => $birthCount,
            'death_certificate' => $deathCount,
            'marriage_certificate' => $marriageCount,

            // Additional document type counts (used by dashboard "Other Documents" card)
            'cenomar' => Scan::where('document_type', 'cenomar')->count(),
            'affidavit' => Scan::where('document_type', 'affidavit')->count(),
            'court_document' => Scan::where('document_type', 'court_document')->count(),
            'contract' => Scan::where('document_type', 'contract')->count(),
            'other' => Scan::whereNotIn('document_type', [
                'birth_certificate', 'death_certificate', 'marriage_certificate',
                'cenomar', 'affidavit', 'court_document', 'contract'
            ])->count(),

            // Time-based statistics
            'today_documents' => Scan::whereDate('created_at', Carbon::today())->count(),
            'week_documents' => Scan::whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->count(),
            'month_documents' => Scan::whereMonth('created_at', Carbon::now()->month)->count(),
            
            // Performance metrics - FIX: Handle null values
            'average_confidence' => round(Scan::avg('ocr_confidence') ?? 0, 1),
            'success_rate' => $this->calculateSuccessRate(),
            
            // Recent activity count
            'recent_activity_count' => Scan::where('created_at', '>=', Carbon::now()->subHours(24))->count(),

            // Daily category counts (today only) — used by dashboard Row 3
            'daily_birth_certificate'    => Scan::where('document_type', 'birth_certificate')
                                                ->whereDate('created_at', Carbon::today())->count(),
            'daily_marriage_certificate' => Scan::where('document_type', 'marriage_certificate')
                                                ->whereDate('created_at', Carbon::today())->count(),
            'daily_death_certificate'    => Scan::where('document_type', 'death_certificate')
                                                ->whereDate('created_at', Carbon::today())->count(),
            'daily_other'                => Scan::whereNotIn('document_type', [
                                                'birth_certificate', 'death_certificate', 'marriage_certificate',
                                            ])->whereDate('created_at', Carbon::today())->count(),

            // Breakdown used by staff dashboard performance cards
            'status_type_breakdown' => $statusTypeBreakdown,
        ];

                // Calculate percentage changes (mock data for now - can be enhanced with historical comparison)
                $stats['total_documents_change'] = $this->calculatePercentageChange('total_documents');
                $stats['success_rate_change'] = $this->calculatePercentageChange('success_rate');

                Log::info('DocumentService: Statistics generated successfully', $stats);

                return $stats;

            } catch (\Exception $e) {
                Log::error('DocumentService: Statistics generation failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                // Return default stats on error
                return $this->getDefaultStatistics();
            }
        }

    /**
     * Get recent document activity for dashboard
     * 
     * @param int $limit - Number of recent activities to retrieve
     * @return array - Recent activity data
     */
    public function getRecentActivity($limit = 10)
    {
        try {
            $recentDocuments = Scan::with('user')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            $activities = [];

            foreach ($recentDocuments as $document) {
                $activities[] = [
                    'id' => $document->id,
                    'document_id' => $document->document_id,
                    'document_type' => $document->document_type,
                    'status' => $document->verification_status,
                    'file_name' => basename($document->file_path),
                    'processed_by' => $document->user->name ?? 'System',
                    'created_at' => $document->created_at,
                    'time_ago' => $document->created_at->diffForHumans(),
                    'status_class' => $this->getStatusClass($document->verification_status),
                    'status_icon' => $this->getStatusIcon($document->verification_status),
                    'message' => $this->generateActivityMessage($document)
                ];
            }

            Log::info('DocumentService: Recent activity retrieved', [
                'count' => count($activities)
            ]);

            return $activities;

        } catch (\Exception $e) {
            Log::error('DocumentService: Recent activity retrieval failed', [
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Update document verification status
     * 
     * @param int $documentId - Document database ID
     * @param string $status - New verification status
     * @param array $updatedFields - Updated field data
     * @return bool - Update success status
     */
    public function updateDocumentStatus($documentId, $status, $updatedFields = [])
    {
        try {
            $document = Scan::findOrFail($documentId);
            
            $document->verification_status = $status;
            $document->verified_by = Auth::id();
            $document->verified_at = Carbon::now();
            
            if (!empty($updatedFields)) {
                $document->extracted_fields = json_encode($updatedFields);
            }
            
            $document->save();
            
            Log::info('DocumentService: Document status updated', [
                'document_id' => $documentId,
                'new_status' => $status,
                'updated_by' => Auth::id()
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('DocumentService: Status update failed', [
                'document_id' => $documentId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Generate unique document identifier
     */
    private function generateDocumentId($documentType)
    {
        $prefix = strtoupper(substr($documentType, 0, 3)); // BIR, DEA, MAR
        $date = Carbon::now()->format('Ymd');
        $sequence = str_pad(Scan::whereDate('created_at', Carbon::today())->count() + 1, 4, '0', STR_PAD_LEFT);
        
        return "{$prefix}-{$date}-{$sequence}";
    }

    /**
 * Organize file storage with proper directory structure
 * 
 * @param string $sourcePath - Source file path
 * @param string $storagePath - Target storage path  
 * @param string $documentId - Document identifier
 * @return string - Final storage path
 */
private function organizeFileStorage($sourcePath, $storagePath, $documentId)
{
    Log::info('DocumentService: Organizing file storage', [
        'source_path' => $sourcePath,
        'source_path_type' => gettype($sourcePath),
        'storage_path' => $storagePath,
        'document_id' => $documentId,
        'source_exists' => file_exists($sourcePath)
    ]);

    // CRITICAL: Validate source path is string
    if (!is_string($sourcePath)) {
        throw new \InvalidArgumentException('Source path must be a string, ' . gettype($sourcePath) . ' given');
    }

    if (!file_exists($sourcePath)) {
        throw new \InvalidArgumentException('Source file does not exist: ' . $sourcePath);
    }

    // Ensure storage directory exists
    $fullStoragePath = storage_path("app/public/{$storagePath}");
    if (!file_exists($fullStoragePath)) {
        if (!mkdir($fullStoragePath, 0755, true)) {
            throw new \RuntimeException('Failed to create storage directory: ' . $fullStoragePath);
        }
    }

    // FIXED: Proper pathinfo usage with type validation
    $pathInfo = pathinfo($sourcePath);
    $extension = $pathInfo['extension'] ?? 'jpg';
    
    $finalFileName = "{$documentId}.{$extension}";
    $finalPath = "{$storagePath}/{$finalFileName}";
    $fullFinalPath = storage_path("app/public/{$finalPath}");

    $sourceRealPath = str_replace('\\', '/', realpath($sourcePath) ?: $sourcePath);
    $publicRoot = str_replace('\\', '/', storage_path('app/public/'));
    $appRoot = str_replace('\\', '/', storage_path('app/'));
    $moved = false;

    // When source is a transient app-local file, move it to avoid duplicate artifacts.
    if (str_starts_with($sourceRealPath, $appRoot) && !str_starts_with($sourceRealPath, $publicRoot)) {
        $moved = @rename($sourcePath, $fullFinalPath);
    }

    if (!$moved && !copy($sourcePath, $fullFinalPath)) {
        throw new \RuntimeException('Failed to copy file to final location');
    }

    // Clean up temporary source if we copied instead of moving.
    if (!$moved && strpos($sourcePath, 'temp/') !== false) {
        unlink($sourcePath);
        Log::info('DocumentService: Temporary file cleaned up', ['temp_path' => $sourcePath]);
    }

    Log::info('DocumentService: File storage organized successfully', [
        'final_path' => $finalPath,
        'full_final_path' => $fullFinalPath,
        'storage_action' => $moved ? 'moved' : 'copied'
    ]);

    return $finalPath;
}

    /**
     * Determine verification status based on OCR results and data completeness
     */
    private function determineVerificationStatus($documentData, $ocrResults)
    {
        $confidence = $ocrResults['confidence'] ?? 0;
        $fieldCount = count(array_filter($documentData));
        
        if ($confidence >= 80 && $fieldCount >= 5) {
            return 'completed';
        } elseif ($confidence >= 60 && $fieldCount >= 3) {
            return 'pending';
        } else {
            return 'rejected';
        }
    }

    /**
     * Calculate overall success rate
     */
    private function calculateSuccessRate()
        {
            $totalDocuments = Scan::count();
            if ($totalDocuments === 0) return 100;
            
            $completedDocuments = Scan::where('verification_status', 'completed')->count();
            return round(($completedDocuments / $totalDocuments) * 100, 1);
        }

    /**
     * Calculate percentage change (mock implementation)
     */
    private function calculatePercentageChange($metric)
    {
        // This would compare with previous period in a real implementation
        // For now, return random positive change between 5-15%
        return rand(5, 15);
    }

    /**
     * Get default statistics when database query fails
     */
    private function getDefaultStatistics()
    {
        return [
            'total_documents' => 0,
            'validated_documents' => 0,
            'pending_documents' => 0,
            'rejected_documents' => 0,
            'birth_certificates' => 0,
            'death_certificates' => 0,
            'marriage_certificates' => 0,
            'today_documents' => 0,
            'week_documents' => 0,
            'month_documents' => 0,
            'average_confidence' => 0,
            'success_rate' => 0,
            'recent_activity_count' => 0,
            'total_documents_change' => 0,
            'success_rate_change' => 0,
            // Daily category fallbacks
            'daily_birth_certificate'    => 0,
            'daily_marriage_certificate' => 0,
            'daily_death_certificate'    => 0,
            'daily_other'                => 0,
            'status_type_breakdown'      => [
                'pending' => [],
                'completed' => [],
                'rejected' => [],
            ],
        ];
    }

    /**
     * Get CSS class for verification status
     */
    private function getStatusClass($status)
{
    $classes = [
        'completed' => 'bg-green-100 text-green-800',
        'pending' => 'bg-yellow-100 text-yellow-800',
        'rejected' => 'bg-red-100 text-red-800',
        'draft' => 'bg-gray-100 text-gray-800'
    ];

    return $classes[$status] ?? $classes['pending'];
}

    /**
     * Get icon for verification status
     */
    private function getStatusIcon($status)
        {
            $icons = [
                'completed' => 'fas fa-check-circle',
                'pending' => 'fas fa-clock',
                'rejected' => 'fas fa-times-circle',
                'draft' => 'fas fa-edit'
            ];

            return $icons[$status] ?? $icons['pending'];
        }
    
    /**
 * Format file size for display
 */
    private function formatFileSize($bytes)
    {
        if ($bytes === 0) return '0 B';
        
        $units = ['B', 'KB', 'MB', 'GB'];
        $base = 1024;
        $i = floor(log($bytes) / log($base));
        
        return round($bytes / pow($base, $i), 2) . ' ' . $units[$i];
    }

    /**
     * Generate activity message for recent activity display
     */
    private function generateActivityMessage($document)
    {
        $typeNames = [
            'birth_certificate' => 'Birth Certificate',
            'death_certificate' => 'Death Certificate',
            'marriage_certificate' => 'Marriage Certificate'
        ];

        $typeName = $typeNames[$document->document_type] ?? 'Document';
        $fileName = basename($document->file_path);

        switch ($document->verification_status) {
            case 'completed':
                return "Document <span class=\"font-black text-green-700\">{$fileName}</span> validated successfully";
            case 'pending':
                return "{$typeName} <span class=\"font-black text-amber-700\">{$fileName}</span> pending review";
            case 'rejected':
                return "Validation failed for <span class=\"font-black text-red-700\">{$fileName}</span>";
            default:
                return "New document uploaded: <span class=\"font-black text-blue-700\">{$fileName}</span>";
        }
    }

    /**
 * Prepare document for blockchain validation
 * Generates document hash and prepares metadata
 */
public function prepareForBlockchain($documentId)
{
    try {
        $document = Scan::findOrFail($documentId);
        
        // Generate document hash for blockchain
        $documentData = json_decode($document->extracted_fields, true) ?? [];
        
        // Generate comprehensive document hash directly
        $documentHash = $this->generateDocumentHash($documentData, $document->file_path);
        
        // Update document with blockchain preparation
        $document->document_hash = $documentHash;
        $document->blockchain_status = 'pending';
        $document->blockchain_submitted_at = Carbon::now();
        $document->save();
        
        Log::info('DocumentService: Document prepared for blockchain', [
            'document_id' => $document->document_id,
            'hash' => $documentHash
        ]);
        
        return [
            'success' => true,
            'document_hash' => $documentHash,
            'message' => 'Document prepared for blockchain validation'
        ];
        
    } catch (\Exception $e) {
        Log::error('DocumentService: Blockchain preparation failed', [
            'document_id' => $documentId,
            'error' => $e->getMessage()
        ]);
        
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Generate document hash for blockchain validation
 */
private function generateDocumentHash($documentData, $filePath)
{
    $dataString = json_encode($documentData, JSON_SORT_KEYS);
    $fileHash = hash_file('sha256', storage_path('app/public/' . $filePath));
    return hash('sha256', $dataString . $fileHash);
}

/**
 * Get documents ready for verification
 * Returns documents that need manual verification
 */
public function getDocumentsForVerification($limit = 10)
{
    try {
        $documents = Scan::where('verification_status', 'pending')
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        $verificationQueue = [];
        
        foreach ($documents as $document) {
            $extractedFields = json_decode($document->extracted_fields, true) ?? [];
            $ocrData = json_decode($document->ocr_data, true) ?? [];
            
            $verificationQueue[] = [
                'id' => $document->id,
                'document_id' => $document->document_id,
                'document_type' => $document->document_type,
                'file_path' => $document->file_path,
                'extracted_fields' => $extractedFields,
                'ocr_confidence' => $document->ocr_confidence,
                'created_at' => $document->created_at,
                'processed_by' => $document->user->name ?? 'System',
                'verification_progress' => $this->calculateVerificationProgress($extractedFields),
                'required_fields' => $this->getRequiredFields($document->document_type),
                'missing_fields' => $this->getMissingFields($document->document_type, $extractedFields)
            ];
        }
        
        return $verificationQueue;
        
    } catch (\Exception $e) {
        Log::error('DocumentService: Get verification queue failed', [
            'error' => $e->getMessage()
        ]);
        
        return [];
    }
}

/**
 * Calculate verification progress percentage
 */
public function calculateVerificationProgress($extractedFields)
{
    if (empty($extractedFields) || !is_array($extractedFields)) {
        return 0;
    }
    
    $totalFields = count($this->getAllPossibleFields());
    $filledFields = count(array_filter($extractedFields, function($value) {
        return !empty(trim($value ?? ''));
    }));
    
    return $totalFields > 0 ? round(($filledFields / $totalFields) * 100) : 0;
}

/**
 * Get required fields for document type
 */
public function getRequiredFields($documentType)
{
    $requiredFields = [
        'birth_certificate' => ['full_name', 'date_of_birth', 'place_of_birth', 'parents_names'],
        'death_certificate' => ['full_name', 'date_of_death', 'place_of_death', 'cause_of_death'],
        'marriage_certificate' => ['groom_name', 'bride_name', 'date_of_marriage', 'place_of_marriage']
    ];
    
    return $requiredFields[$documentType] ?? [];
}

/**
 * Get missing required fields
 */
private function getMissingFields($documentType, $extractedFields)
{
    $requiredFields = $this->getRequiredFields($documentType);
    $missingFields = [];
    
    foreach ($requiredFields as $field) {
        if (empty($extractedFields[$field] ?? '')) {
            $missingFields[] = $field;
        }
    }
    
    return $missingFields;
}

/**
 * Get all possible fields for progress calculation
 */
private function getAllPossibleFields()
{
    return [
        'full_name', 'date_of_birth', 'place_of_birth', 'parents_names',
        'date_of_death', 'place_of_death', 'cause_of_death',
        'groom_name', 'bride_name', 'date_of_marriage', 'place_of_marriage',
        'registry_number', 'issued_date', 'issued_by'
    ];
}

/**
     * Get pending document counts for each category
     * Used for the 4 major category cards on the main dashboard
     * 
     * @return array - Pending counts per category
     */
    public function getCategoryPendingCounts()
    {
        try {
            Log::info('DocumentService: Generating category pending counts');

            $counts = [
                'birth_certificate' => Scan::where('document_type', 'birth_certificate')
                    ->where('verification_status', 'pending')
                    ->count(),
                    
                'death_certificate' => Scan::where('document_type', 'death_certificate')
                    ->where('verification_status', 'pending')
                    ->count(),
                    
                'marriage_certificate' => Scan::where('document_type', 'marriage_certificate')
                    ->where('verification_status', 'pending')
                    ->count(),
                    
                'cenomar' => Scan::whereIn('document_type', ['cenomar', 'advisory_on_marriages'])
                    ->where('verification_status', 'pending')
                    ->count(),
            ];

            Log::info('DocumentService: Category pending counts generated', $counts);

            return $counts;

        } catch (\Exception $e) {
            Log::error('DocumentService: Category pending counts failed', [
                'error' => $e->getMessage()
            ]);

            return [
                'birth_certificate' => 0,
                'death_certificate' => 0,
                'marriage_certificate' => 0,
                'cenomar' => 0,
            ];
        }
    }

    /**
     * Get recent documents formatted for the dashboard table
     * Columns: Document Type | Owner Name | Status | Date Submitted
     * 
     * @param int $limit - Number of documents to retrieve (default 10)
     * @return \Illuminate\Support\Collection - Formatted document data
     */
    public function getRecentDocumentsForTable($limit = 10)
    {
        try {
            Log::info('DocumentService: Fetching recent documents for table', ['limit' => $limit]);

            $documents = Scan::with('user')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($document) {
                    // Extract owner name from extracted_fields
                    $ownerName = $this->extractOwnerName($document);
                    
                    return [
                        'id' => $document->id,
                        'document_id' => $document->document_id,
                        'document_type' => $this->formatDocumentTypeName($document->document_type),
                        'document_type_raw' => $document->document_type,
                        'owner_name' => $ownerName,
                        'status' => $document->verification_status,
                        'status_label' => $this->formatStatusLabel($document->verification_status),
                        'status_class' => $this->getStatusBadgeClass($document->verification_status),
                        'date_submitted' => $document->created_at->format('M d, Y'),
                        'time_submitted' => $document->created_at->format('h:i A'),
                        'date_submitted_full' => $document->created_at->format('M d, Y - h:i A'),
                    ];
                });

            Log::info('DocumentService: Recent documents for table retrieved', [
                'count' => $documents->count()
            ]);

            return $documents;

        } catch (\Exception $e) {
            Log::error('DocumentService: Recent documents for table failed', [
                'error' => $e->getMessage()
            ]);

            return collect([]);
        }
    }

    /**
     * Get statistics for a specific document category (sub-page)
     * Returns: Pending, Validated, Released counts
     * 
     * @param string $category - Document category (birth_certificate, death_certificate, etc.)
     * @return array - Category-specific statistics
     */
    public function getCategoryStatistics($category)
    {
        try {
            Log::info('DocumentService: Generating category statistics', ['category' => $category]);

            // Handle CENOMAR which may have multiple document types
            $documentTypes = $this->getCategoryDocumentTypes($category);

            $stats = [
                'category' => $category,
                'category_name' => $this->formatDocumentTypeName($category),
                
                // Status counts
                'pending' => Scan::whereIn('document_type', $documentTypes)
                    ->where('verification_status', 'pending')
                    ->count(),
                    
                'validated' => Scan::whereIn('document_type', $documentTypes)
                    ->where('verification_status', 'completed')
                    ->count(),
                    
                'released' => Scan::whereIn('document_type', $documentTypes)
                    ->where('verification_status', 'released')
                    ->count(),
                
                // Total for this category
                'total' => Scan::whereIn('document_type', $documentTypes)->count(),
                
                // Today's activity for this category
                'today' => Scan::whereIn('document_type', $documentTypes)
                    ->whereDate('created_at', Carbon::today())
                    ->count(),
                    
                // This week's activity
                'this_week' => Scan::whereIn('document_type', $documentTypes)
                    ->whereBetween('created_at', [
                        Carbon::now()->startOfWeek(),
                        Carbon::now()->endOfWeek()
                    ])
                    ->count(),
                    
                // This month's activity
                'this_month' => Scan::whereIn('document_type', $documentTypes)
                    ->whereMonth('created_at', Carbon::now()->month)
                    ->whereYear('created_at', Carbon::now()->year)
                    ->count(),
            ];

            Log::info('DocumentService: Category statistics generated', $stats);

            return $stats;

        } catch (\Exception $e) {
            Log::error('DocumentService: Category statistics failed', [
                'category' => $category,
                'error' => $e->getMessage()
            ]);

            return [
                'category' => $category,
                'category_name' => $this->formatDocumentTypeName($category),
                'pending' => 0,
                'validated' => 0,
                'released' => 0,
                'total' => 0,
                'today' => 0,
                'this_week' => 0,
                'this_month' => 0,
            ];
        }
    }

    /**
     * Get chart data for a specific category filtered by time period
     * Used for the data visualization on sub-pages
     * 
     * @param string $category - Document category
     * @param string $period - Time period: 'today', 'this_week', 'this_month'
     * @return array - Chart-ready data
     */
    public function getCategoryChartData($category, $period = 'this_week')
    {
        try {
            Log::info('DocumentService: Generating chart data', [
                'category' => $category,
                'period' => $period
            ]);

            $documentTypes = $this->getCategoryDocumentTypes($category);
            
            // Get date range based on period
            $dateRange = $this->getDateRangeForPeriod($period);
            
            // Get status breakdown (for doughnut chart)
            $statusBreakdown = [
                'pending' => Scan::whereIn('document_type', $documentTypes)
                    ->where('verification_status', 'pending')
                    ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                    ->count(),
                    
                'validated' => Scan::whereIn('document_type', $documentTypes)
                    ->where('verification_status', 'completed')
                    ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                    ->count(),
                    
                'released' => Scan::whereIn('document_type', $documentTypes)
                    ->where('verification_status', 'released')
                    ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                    ->count(),
            ];

            // Get time-series data (for bar/line chart)
            $timeSeriesData = $this->getTimeSeriesData($documentTypes, $period, $dateRange);

            $chartData = [
                'period' => $period,
                'period_label' => $this->getPeriodLabel($period),
                'date_range' => [
                    'start' => $dateRange['start']->format('M d, Y'),
                    'end' => $dateRange['end']->format('M d, Y'),
                ],
                'status_breakdown' => $statusBreakdown,
                'status_labels' => ['Pending', 'Validated', 'Released'],
                'status_colors' => ['#f59e0b', '#10b981', '#3b82f6'],
                'time_series' => $timeSeriesData,
                'total_in_period' => array_sum($statusBreakdown),
            ];

            Log::info('DocumentService: Chart data generated', [
                'category' => $category,
                'period' => $period,
                'total' => $chartData['total_in_period']
            ]);

            return $chartData;

        } catch (\Exception $e) {
            Log::error('DocumentService: Chart data generation failed', [
                'category' => $category,
                'period' => $period,
                'error' => $e->getMessage()
            ]);

            return [
                'period' => $period,
                'period_label' => $this->getPeriodLabel($period),
                'date_range' => ['start' => '', 'end' => ''],
                'status_breakdown' => ['pending' => 0, 'validated' => 0, 'released' => 0],
                'status_labels' => ['Pending', 'Validated', 'Released'],
                'status_colors' => ['#f59e0b', '#10b981', '#3b82f6'],
                'time_series' => [],
                'total_in_period' => 0,
            ];
        }
    }

    /**
     * Get all documents for a specific category (for master list table on sub-pages)
     * 
     * @param string $category - Document category
     * @param array $filters - Optional filters (status, search, date range)
     * @return \Illuminate\Pagination\LengthAwarePaginator - Paginated results
     */
    public function getCategoryDocuments($category, $filters = [], $perPage = 15)
    {
        try {
            Log::info('DocumentService: Fetching category documents', [
                'category' => $category,
                'filters' => $filters
            ]);

            $documentTypes = $this->getCategoryDocumentTypes($category);

            $query = Scan::whereIn('document_type', $documentTypes)
                ->with('user')
                ->orderBy('created_at', 'desc');

            // Apply status filter
            if (!empty($filters['status'])) {
                $query->where('verification_status', $filters['status']);
            }

            // Apply search filter
            if (!empty($filters['search'])) {
                $searchTerm = $filters['search'];
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('document_id', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('title', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('extracted_fields', 'LIKE', "%{$searchTerm}%");
                });
            }

            // Apply date range filter
            if (!empty($filters['date_from'])) {
                $query->whereDate('created_at', '>=', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $query->whereDate('created_at', '<=', $filters['date_to']);
            }

            $documents = $query->paginate($perPage);

            // Transform the documents
            $documents->getCollection()->transform(function ($document) {
                $document->owner_name = $this->extractOwnerName($document);
                $document->status_label = $this->formatStatusLabel($document->verification_status);
                $document->status_class = $this->getStatusBadgeClass($document->verification_status);
                $document->type_name = $this->formatDocumentTypeName($document->document_type);
                $document->date_formatted = $document->created_at->format('M d, Y');
                $document->time_formatted = $document->created_at->format('h:i A');
                return $document;
            });

            return $documents;

        } catch (\Exception $e) {
            Log::error('DocumentService: Category documents fetch failed', [
                'category' => $category,
                'error' => $e->getMessage()
            ]);

            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage);
        }
    }

    /**
     * Get document types array for a category
     */
    private function getCategoryDocumentTypes($category)
    {
        $categoryMap = [
            'birth_certificate' => ['birth_certificate'],
            'death_certificate' => ['death_certificate'],
            'marriage_certificate' => ['marriage_certificate'],
            'cenomar' => ['cenomar', 'advisory_on_marriages'],
        ];

        return $categoryMap[$category] ?? [$category];
    }

    /**
     * Extract owner name from document's extracted_fields
     * 
     * Constructs a display name from individual component fields stored by the OCR/save pipeline.
     * Field names match those defined in config/documents_fields.php and the OCR extractors.
     */
    private function extractOwnerName($document)
    {
        $fields = $document->extracted_fields;
        
        // Handle if extracted_fields is already an array or needs decoding
        if (is_string($fields)) {
            $fields = json_decode($fields, true) ?? [];
        }
        
        if (!is_array($fields)) {
            $fields = [];
        }

        // Build name from component fields based on document type
        switch ($document->document_type) {
            case 'birth_certificate':
                $name = $this->buildNameFromComponents($fields, 'name_first', 'name_middle', 'name_last');
                if ($name) return $name;
                // Fallback: try alternate key patterns
                $name = $this->buildNameFromComponents($fields, 'child_first_name', 'child_middle_name', 'child_last_name');
                if ($name) return $name;
                break;

            case 'death_certificate':
                $name = $this->buildNameFromComponents($fields, 'deceased_first_name', 'deceased_middle_name', 'deceased_last_name');
                if ($name) return $name;
                // Fallback: some records may use name_first/name_last
                $name = $this->buildNameFromComponents($fields, 'name_first', 'name_middle', 'name_last');
                if ($name) return $name;
                break;

            case 'marriage_certificate':
                $groom = $this->buildNameFromComponents($fields, 'groom_first_name', 'groom_middle_name', 'groom_last_name');
                $bride = $this->buildNameFromComponents($fields, 'bride_first_name', 'bride_middle_name', 'bride_last_name');
                // Fallback: try husband/wife keys
                if (!$groom) $groom = $this->buildNameFromComponents($fields, 'husband_first_name', 'husband_middle_name', 'husband_last_name');
                if (!$bride) $bride = $this->buildNameFromComponents($fields, 'wife_first_name', 'wife_middle_name', 'wife_last_name');
                if ($groom && $bride) return $groom . ' & ' . $bride;
                if ($groom) return $groom;
                if ($bride) return $bride;
                break;

            case 'cenomar':
                $name = $this->buildNameFromComponents($fields, 'person_first_name', 'person_middle_name', 'person_last_name');
                if ($name) return $name;
                break;
        }

        // Generic fallback: try common composite key names (legacy data)
        $compositeKeys = [
            'child_name', 'full_name', 'name_of_child', 'deceased_name',
            'name_of_deceased', 'groom_name', 'bride_name', 'husband_name',
            'wife_name', 'name', 'registrant_name', 'owner_name'
        ];

        foreach ($compositeKeys as $key) {
            if (!empty($fields[$key])) {
                return $fields[$key];
            }
        }

        return 'Name not available';
    }

    /**
     * Build a full name string from individual first/middle/last component fields.
     * Returns null if no component is found.
     */
    private function buildNameFromComponents(array $fields, string $firstKey, string $middleKey, string $lastKey): ?string
    {
        $first = trim($fields[$firstKey] ?? '');
        $middle = trim($fields[$middleKey] ?? '');
        $last = trim($fields[$lastKey] ?? '');

        $parts = array_filter([$first, $middle, $last], fn($p) => $p !== '');

        return !empty($parts) ? implode(' ', $parts) : null;
    }

    /**
     * Format document type to human-readable name
     */
    private function formatDocumentTypeName($type)
    {
        $names = [
            'birth_certificate' => 'Birth Certificate',
            'death_certificate' => 'Death Certificate',
            'marriage_certificate' => 'Marriage Certificate',
            'cenomar' => 'CENOMAR / Advisory on Marriages',
            'advisory_on_marriages' => 'Advisory on Marriages',
        ];

        return $names[$type] ?? ucwords(str_replace('_', ' ', $type));
    }

    /**
     * Format verification status to display label
     */
    private function formatStatusLabel($status)
    {
        $labels = [
            'pending' => 'Pending',
            'completed' => 'Validated',
            'released' => 'Released',
            'rejected' => 'Rejected',
            'draft' => 'Draft',
        ];

        return $labels[$status] ?? ucfirst($status ?? 'Unknown');
    }

    /**
     * Get CSS badge class for status
     */
    private function getStatusBadgeClass($status)
    {
        $classes = [
            'pending' => 'bg-amber-100 text-amber-800 border-amber-200',
            'completed' => 'bg-green-100 text-green-800 border-green-200',
            'released' => 'bg-blue-100 text-blue-800 border-blue-200',
            'rejected' => 'bg-red-100 text-red-800 border-red-200',
            'draft' => 'bg-gray-100 text-gray-800 border-gray-200',
        ];

        return $classes[$status] ?? $classes['pending'];
    }

    /**
     * Get date range for a given period
     */
    private function getDateRangeForPeriod($period)
    {
        switch ($period) {
            case 'today':
                return [
                    'start' => Carbon::today()->startOfDay(),
                    'end' => Carbon::today()->endOfDay(),
                ];
            case 'this_week':
                return [
                    'start' => Carbon::now()->startOfWeek(),
                    'end' => Carbon::now()->endOfWeek(),
                ];
            case 'this_month':
                return [
                    'start' => Carbon::now()->startOfMonth(),
                    'end' => Carbon::now()->endOfMonth(),
                ];
            default:
                return [
                    'start' => Carbon::now()->startOfWeek(),
                    'end' => Carbon::now()->endOfWeek(),
                ];
        }
    }

    /**
     * Get human-readable label for period
     */
    private function getPeriodLabel($period)
    {
        $labels = [
            'today' => 'Today',
            'this_week' => 'This Week',
            'this_month' => 'This Month',
        ];

        return $labels[$period] ?? 'This Week';
    }

    /**
     * Get time-series data for charts
     */
    private function getTimeSeriesData($documentTypes, $period, $dateRange)
    {
        $data = [];
        
        switch ($period) {
            case 'today':
                // Hourly breakdown for today
                for ($hour = 0; $hour < 24; $hour++) {
                    $startHour = Carbon::today()->addHours($hour);
                    $endHour = Carbon::today()->addHours($hour + 1);
                    
                    $count = Scan::whereIn('document_type', $documentTypes)
                        ->whereBetween('created_at', [$startHour, $endHour])
                        ->count();
                    
                    $data[] = [
                        'label' => $startHour->format('g A'),
                        'value' => $count,
                    ];
                }
                break;
                
            case 'this_week':
                // Daily breakdown for this week
                $start = Carbon::now()->startOfWeek();
                for ($day = 0; $day < 7; $day++) {
                    $currentDay = $start->copy()->addDays($day);
                    
                    $count = Scan::whereIn('document_type', $documentTypes)
                        ->whereDate('created_at', $currentDay)
                        ->count();
                    
                    $data[] = [
                        'label' => $currentDay->format('D'),
                        'value' => $count,
                    ];
                }
                break;
                
            case 'this_month':
                // Weekly breakdown for this month
                $start = Carbon::now()->startOfMonth();
                $end = Carbon::now()->endOfMonth();
                $weekNumber = 1;
                
                while ($start->lte($end)) {
                    $weekEnd = $start->copy()->addDays(6)->min($end);
                    
                    $count = Scan::whereIn('document_type', $documentTypes)
                        ->whereBetween('created_at', [$start, $weekEnd->endOfDay()])
                        ->count();
                    
                    $data[] = [
                        'label' => 'Week ' . $weekNumber,
                        'value' => $count,
                    ];
                    
                    $start->addDays(7);
                    $weekNumber++;
                }
                break;
        }

        return $data;
    }


/**
 * Enhanced statistics with blockchain data
 */
public function getEnhancedDashboardStatistics()
{
    $baseStats = $this->getDashboardStatistics();
    
    // Check if blockchain columns exist before querying
    try {
        $blockchainStats = [];
        
        // Test if blockchain_status column exists
        if (Schema::hasColumn('scans', 'blockchain_status')) {
            $blockchainStats = [
                'blockchain_pending' => Scan::where('blockchain_status', 'pending')->count(),
                'blockchain_confirmed' => Scan::where('blockchain_status', 'confirmed')->count(),
                'blockchain_failed' => Scan::where('blockchain_status', 'failed')->count(),
            ];
        } else {
            // Blockchain columns don't exist yet
            $blockchainStats = [
                'blockchain_pending' => 0,
                'blockchain_confirmed' => 0,
                'blockchain_failed' => 0,
            ];
        }
        
        $blockchainStats['documents_ready_for_verification'] = Scan::where('verification_status', 'pending')->count();
        
        return array_merge($baseStats, $blockchainStats);
        
    } catch (\Exception $e) {
        Log::warning('Enhanced statistics blockchain query failed', [
            'error' => $e->getMessage()
        ]);
        
        // Return base stats if blockchain queries fail
        return array_merge($baseStats, [
            'blockchain_pending' => 0,
            'blockchain_confirmed' => 0,
            'blockchain_failed' => 0,
            'documents_ready_for_verification' => 0
        ]);
    }
}

/**
 * Get recent documents with formatted data for upload section
 * 
 * @param int $limit Number of documents to retrieve
 * @return array Formatted document data
 */
public function getRecentDocuments($limit = 10)
{
    try {
        $documents = Scan::latest()
            ->limit($limit)
            ->get()
            ->map(function ($document) {
                // Determine document type styling
                $typeConfig = $this->getDocumentTypeConfig($document->document_type);
                
                // Calculate file size
                $fileSize = 0;
                $filePath = storage_path('app/public/' . $document->file_path);
                if (file_exists($filePath)) {
                    $fileSize = filesize($filePath);
                }
                
                return [
                    'id' => $document->id,
                    'document_id' => $document->document_id,
                    'title' => $document->title ?? 'Document - ' . $document->document_id,
                    'document_type' => $document->document_type,
                    'verification_status' => $document->verification_status,
                    'created_at' => $document->created_at,
                    'file_path' => $document->file_path,
                    'file_exists' => file_exists(storage_path('app/public/' . $document->file_path)),
                    
                    // Formatted display data
                    'type_name' => $typeConfig['name'],
                    'type_icon' => $typeConfig['icon'],
                    'type_bg_class' => $typeConfig['bg_class'],
                    'category_name' => $typeConfig['category'],
                    'category_class' => $typeConfig['category_class'],
                    'status_name' => ucfirst($document->verification_status ?? 'pending'),
                    'status_class' => $this->getStatusClass($document->verification_status),
                    'status_icon' => $this->getStatusIcon($document->verification_status),
                    'file_size' => $this->formatFileSize($fileSize),
                    'created_date' => $document->created_at->format('M j, Y'),
                    'created_time' => $document->created_at->format('g:i A'),
                ];
            });

        Log::info('DocumentService: Recent documents formatted', [
            'count' => $documents->count(),
            'limit' => $limit
        ]);

        return $documents;

    } catch (\Exception $e) {
        Log::error('DocumentService: Failed to get recent documents', [
            'error' => $e->getMessage(),
            'limit' => $limit
        ]);

        return collect([]);
    }
}

/**
 * Get document type configuration
 */
private function getDocumentTypeConfig($type)
{
    $configs = [
        'birth_certificate' => [
            'name' => 'Birth Certificate',
            'icon' => 'fas fa-baby text-blue-600',
            'bg_class' => 'bg-blue-100',
            'category' => 'Civil Registry',
            'category_class' => 'bg-blue-100 text-blue-800'
        ],
        'death_certificate' => [
            'name' => 'Death Certificate',
            'icon' => 'fas fa-cross text-red-600',
            'bg_class' => 'bg-red-100',
            'category' => 'Civil Registry',
            'category_class' => 'bg-red-100 text-red-800'
        ],
        'marriage_certificate' => [
            'name' => 'Marriage Certificate',
            'icon' => 'fas fa-heart text-pink-600',
            'bg_class' => 'bg-pink-100',
            'category' => 'Civil Registry',
            'category_class' => 'bg-pink-100 text-pink-800'
        ],
        'default' => [
            'name' => 'Document',
            'icon' => 'fas fa-file-alt text-gray-600',
            'bg_class' => 'bg-gray-100',
            'category' => 'General',
            'category_class' => 'bg-gray-100 text-gray-800'
        ]
    ];

    return $configs[$type] ?? $configs['default'];
}

/**
 * Format bytes to human readable format
 */
private function formatBytes($bytes, $precision = 2)
{
    if ($bytes == 0) return '0 B';
    
    $units = ['B', 'KB', 'MB', 'GB'];
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Get status display name
 */
private function getStatusName($status)
{
    return match($status) {
        'completed' => 'Validated',
        'pending' => 'Processing',
        'rejected' => 'Rejected',
        'draft' => 'Draft',
        default => 'Unknown'
    };
}


}