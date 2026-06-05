<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;


/**
 * Scan Model - Represents scanned documents in the system
 * 
 * Handles document storage, OCR results, and verification status
 * Compatible with DocumentService for document processing workflow
 */
class Scan extends Model
{
    use HasFactory;

    /**
     * The table associated with the model
     */
    protected $table = 'scans';
    
    /**
     * MODEL A CONFIGURATION
     */

    const VALIDATION_THRESHOLD = 75.0;
    const OCR_WEIGHT = 0.4;
    const MANUAL_WEIGHT = 0.6;

    /**
     * The attributes that are mass assignable
     */
        protected $fillable = [
        'document_id',
        'document_type',
        'processing_mode',
        'title',
        'description',
        'file_path',
        'file_hash',
        'file_size', 
        'extracted_fields',
        'original_filename',
        'file_mime_type',
        'document_hash',          
        'blockchain_status',
        'blockchain_tx_hash',
        'blockchain_block_number',
        'blockchain_hash',
        'blockchain_enabled',
        'blockchain_submitted_at',
        'blockchain_confirmed_at',
        'blockchain_metadata',   
        'blockchain_from_address',
        'blockchain_to_address',
        'blockchain_gas_used',
        'blockchain_gas_price',
        'blockchain_gas_limit',
        'blockchain_nonce',
        'blockchain_status_code',
        'blockchain_network_id',
        'blockchain_confirmations',
        'blockchain_block_hash',
        'blockchain_transaction_index',
        'blockchain_input_data',
        'blockchain_value', 
        'ocr_confidence',
        'verification_status',
        'status',
        'released',
        'released_at',
        'released_by',
        'released_to',
        'copies_printed',
        'processed_by',
        'verified_by',
        'processed_at',
        'verified_at',
        'ocr_data',
        'extracted_fields',
        'notes',
        'created_at',
        'manual_completion_score',
        'validation_score',
        'blockchain_eligible',
        'reviewed_by',
        'reviewed_at',
        'locked',
        'locked_by',
        'locked_at',
        'lock_reason',
        'archived',
        'archived_by',
        'archived_at',
        'archive_reason',
        'flagged',
        'flagged_by',
        'flagged_at',
        'flag_notes',
        'blockchain_failure_reason',
        'updated_at'
        
    ];

    // Also update the $casts array
    protected $casts = [
        'ocr_confidence' => 'decimal:2',
        'processing_mode' => 'string',
        'released' => 'boolean',
        'released_at' => 'datetime',
        'copies_printed' => 'integer',
        'processed_at' => 'datetime',
        'verified_at' => 'datetime',
        'blockchain_submitted_at' => 'datetime',  
        'blockchain_confirmed_at' => 'datetime', 
        'blockchain_metadata' => 'array',
        'ocr_data' => 'array',
        'blockchain_metadata' => 'array',         
        'created_at' => 'datetime',
        'manual_completion_score' => 'decimal:2',
        'validation_score' => 'decimal:2',
        'blockchain_eligible' => 'boolean',
        'reviewed_at' => 'datetime',
        'file_size' => 'integer',
        'blockchain_gas_used' => 'integer',
        'blockchain_gas_limit' => 'integer',
        'blockchain_nonce' => 'integer',
        'blockchain_confirmations' => 'integer',
        'blockchain_transaction_index' => 'integer',
        'blockchain_metadata' => 'array',
        'locked' => 'boolean',
        'archived' => 'boolean',
        'flagged' => 'boolean',
        'locked_at' => 'datetime',
        'archived_at' => 'datetime',
        'flagged_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * The attributes that should be hidden for serialization
     */
    protected $hidden = [
        'file_hash'
    ];

    /**
     * Document types available in the system
     */
    const DOCUMENT_TYPES = [
        'birth_certificate',
        'death_certificate', 
        'marriage_certificate',
        'admission_of_paternity',
        'ausf',
        'legitimation',
        'affidavit_of_reappearance',
        'marriage_settlement',
        'parental_authorization_ai',
        'late_registration',
        'supplemental_report',
        'certificate_of_foundling',
        'adoption_document',
        'judicial_correction_rule_108',
        'annulment_or_nullity',
        'recognition_of_foreign_divorce',
        'marriage_license',
        'certificate_legal_capacity_to_marry',
        'cenomar',
        'affidavit',
        'court_document',
        'contract',
        'other'
    ];

    const OCR_DOCUMENT_TYPES = [
        'birth_certificate',
        'death_certificate',
        'marriage_certificate',
    ];

    /**
     * Verification status options
     */
    const VERIFICATION_STATUSES = [
        'draft',      // Initial upload, not processed
        'pending',    // OCR processed, awaiting manual verification
        'completed',  // Verified and approved
        'rejected'    // Failed verification or rejected
    ];

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
    
    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // Intervention Relationships
    public function lockedByUser()
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function archivedByUser()
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    public function flaggedByUser()
    {
        return $this->belongsTo(User::class, 'flagged_by');
    }

    // Intervention Methods
    public function isLocked(): bool
    {
        return $this->locked === true;
    }

    public function isArchived(): bool
    {
        return $this->archived === true;
    }

    public function isFlagged(): bool
    {
        return $this->flagged === true;
    }

    // Scopes
    public function scopeLocked($query)
    {
        return $query->where('locked', true);
    }

    public function scopeArchived($query)
    {
        return $query->where('archived', true);
    }

    public function scopeFlagged($query)
    {
        return $query->where('flagged', true);
    }

    public function scopeNotArchived($query)
    {
        return $query->where('archived', false);
    }

    /**
     * Normalize stored file paths so legacy "/storage/..." values still resolve.
     */
    private function normalizeStoredFilePath(?string $filePath): ?string
    {
        if (!$filePath) {
            return null;
        }

        $normalizedPath = str_replace('\\', '/', trim($filePath));
        $normalizedPath = ltrim($normalizedPath, '/');

        foreach (['storage/app/public/', 'storage/', 'app/public/', 'public/'] as $prefix) {
            if (str_starts_with($normalizedPath, $prefix)) {
                $normalizedPath = substr($normalizedPath, strlen($prefix));
                break;
            }
        }

        return ltrim($normalizedPath, '/');
    }

    /**
 * Get full file system path for file operations
 */
public function getFullFilePathAttribute()
{
    if (!$this->file_path) {
        return null;
    }

    $storedPath = $this->normalizeStoredFilePath($this->file_path);

    if (!$storedPath) {
        return null;
    }
    
    // Strategy 1: Check storage/app/documents (local disk - RECOMMENDED)
    $localPath = storage_path('app/' . $storedPath);
    if (file_exists($localPath)) {
        return $localPath;
    }
    
    // Strategy 2: Check storage/app/public/documents (public disk without symlink)
    $publicDiskPath = storage_path('app/public/' . $storedPath);
    if (file_exists($publicDiskPath)) {
        return $publicDiskPath;
    }

    // Strategy 3: Check public/storage/documents (symlinked - LEGACY)
    $publicPath = public_path('storage/' . $storedPath);
    if (file_exists($publicPath)) {
        return $publicPath;
    }
    
    // Strategy 4: Check if file_path already contains full path
    if (file_exists($this->file_path)) {
        return $this->file_path;
    }
    
    return null;
}

public function getStorageDiskAttribute()
{
    $fullPath = $this->full_file_path;
    
    if (!$fullPath) {
        return 'none';
    }
    
    // Normalize path separators for consistent checking
    $normalizedPath = str_replace('\\', '/', $fullPath);
    $storageAppPath = str_replace('\\', '/', storage_path('app'));
    $storagePublicPath = str_replace('\\', '/', storage_path('app/public'));
    $publicStoragePath = str_replace('\\', '/', public_path('storage'));

    if (strpos($normalizedPath, $storagePublicPath) !== false) {
        return 'public'; // storage/app/public/
    }
    
    if (strpos($normalizedPath, $storageAppPath) !== false) {
        return 'local'; // storage/app/
    }
    
    if (strpos($normalizedPath, $publicStoragePath) !== false) {
        return 'public'; // public/storage/
    }
    
    return 'unknown';
}

/**
 * Check if file is stored in recommended location (storage/app)
 */
public function isStoredLocally()
{
    return $this->storage_disk === 'local';
}

/**
 * Check if file is stored in public directory (less secure)
 */
public function isStoredPublicly()
{
    return $this->storage_disk === 'public';
}


/**
 * Check if file exists in storage
 */
public function fileExists()
{
    return $this->full_file_path !== null;
}

/**
 * Get URL for file access (via route)
 */
public function getFileUrlAttribute()
{
    if (!$this->file_path) {
        return null;
    }
    
    // Always use route-based access for security
    return route('scans.image.preview', $this);
}

/**
 * Get file size in bytes
 */
public function getFileSizeFormattedAttribute()
{
    if (!$this->full_file_path) {
        return 'N/A';
    }
    
    $bytes = filesize($this->full_file_path);
    $units = ['B', 'KB', 'MB', 'GB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, 2) . ' ' . $units[$i];
}

    /**
     * MODEL A: Mark as reviewed and approved
     */
    public function markAsReviewedAndApproved(int $userId): void
    {
        $this->update([
            'reviewed_by' => $userId,
            'reviewed_at' => now(),
            'verification_status' => 'completed',
        ]);
        
        // Recalculate validation status after review
        $this->updateValidationStatus();
    }

public function scopeBlockchainEligible($query)
    {
        return $query->where('blockchain_eligible', true);
    }

    public function scopeNeedsReview($query)
    {
        return $query->where('blockchain_eligible', false)
                     ->where('verification_status', '!=', 'rejected');
    }

    public function scopePendingBlockchain($query)
    {
        return $query->where('blockchain_status', 'pending');
    }

    /**
     * Get the processor (alias for user relationship)
     */
    public function processor(): BelongsTo
    {
        return $this->user();
    }

    /**
     * Get the user who verified this document
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Scope to filter by document type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('document_type', $type);
    }

    /**
     * Scope to filter by verification status
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('verification_status', $status);
    }

    /**
     * Scope for recent documents (last 7 days)
     */
    public function scopeRecent($query)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays(7));
    }

    /**
     * Scope for today's documents
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', Carbon::today());
    }

    /**
     * Scope for this week's documents
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek()
        ]);
    }

    /**
     * Scope for this month's documents
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', Carbon::now()->month)
                    ->whereYear('created_at', Carbon::now()->year);
    }

    /**
     * Check if document is completed
     */
    public function isCompleted(): bool
    {
        return $this->verification_status === 'completed';
    }

    /**
     * Check if document is pending verification
     */
    public function isPending(): bool
    {
        return $this->verification_status === 'pending';
    }

    /**
     * Check if document is rejected
     */
    public function isRejected(): bool
    {
        return $this->verification_status === 'rejected';
    }


    
    /**
     * Get human-readable document type
     */
    public function getDocumentTypeNameAttribute(): string
    {
        $types = [
            'birth_certificate' => 'Birth Certificate',
            'death_certificate' => 'Death Certificate',
            'marriage_certificate' => 'Marriage Certificate',
            'cenomar' => 'CENOMAR',
            'affidavit' => 'Affidavit',
            'court_document' => 'Court Document',
            'contract' => 'Contract',
            'other' => 'Other Document'
        ];

        return $types[$this->document_type] ?? 'Unknown Document';
    }

    /**
     * Get the extracted_fields attribute, handling double-encoded JSON.
     *
     * @param  mixed  $value
     * @return array
     */
    public function getExtractedFieldsAttribute($value): array
    {
        // If already an array, return it
        if (is_array($value)) {
            return $value;
        }

        // If null or empty, return empty array
        if (empty($value)) {
            return [];
        }

        // First decode attempt
        $decoded = json_decode($value, true);

        // If still a string (double-encoded), decode again
        if (is_string($decoded)) {
            $decoded = json_decode($decoded, true);
        }

        // Return the decoded array or empty array if decoding failed
        return is_array($decoded) ? $decoded : [];
    }


    /**
     * Get the registry number from extracted fields.
     * Handles various field naming conventions across document types.
     *
     * @return string|null
     */
    public function getRegistryNumberAttribute(): ?string
    {
        // Use the accessor to get properly decoded fields
        $fields = $this->extracted_fields;

        if (empty($fields) || !is_array($fields)) {
            return null;
        }

        // Check possible registry number field names
        $registryKeys = [
            'registry_number',
            'marriage_registry_number',
            'birth_registry_number',
            'death_registry_number',
            'certificate_number',
            'reg_no',
            'registration_number'
        ];

        foreach ($registryKeys as $key) {
            if (isset($fields[$key]) && !empty(trim($fields[$key]))) {
                return $fields[$key];
            }
        }

        return null;
    }

    /**
     * Get human-readable verification status
     */
    public function getVerificationStatusNameAttribute(): string
    {
        $statuses = [
            'draft' => 'Draft',
            'pending' => 'Pending Review',
            'completed' => 'Completed',
            'rejected' => 'Rejected'
        ];

        return $statuses[$this->verification_status] ?? 'Unknown Status';
    }

    /**
     * Get file size if file exists
     */
    public function getFileSizeAttribute()
    {
        $fullPath = storage_path('app/public/' . $this->file_path);
        return file_exists($fullPath) ? filesize($fullPath) : null;
    }

    /**
     * Get human-readable file size
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        
        if (!$bytes) {
            return 'Unknown';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Get processing duration in human-readable format
     */
    public function getProcessingDurationAttribute(): string
    {
        if (!$this->processed_at || !$this->created_at) {
            return 'Unknown';
        }

        return $this->created_at->diffForHumans($this->processed_at);
    }

    /**
     * Get CSS class for verification status
     */
    public function getStatusCssClassAttribute(): string
    {
        return match($this->verification_status) {
            'completed' => 'bg-green-100 text-green-800',
            'pending' => 'bg-yellow-100 text-yellow-800',
            'rejected' => 'bg-red-100 text-red-800',
            'draft' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }

    /**
     * MODEL A: Calculate validation score
     * Formula: (OCR Confidence × 0.4) + (Manual Completion × 0.6)
     */
    public function calculateValidationScore(): float
    {
        $ocrScore = $this->ocr_confidence ?? 0;
        $manualCompletionPercentage = $this->manual_completion_score ?? 0;

        // Manual-mode documents intentionally skip OCR, so their validation
        // should be based on manual completion only.
        if (($this->processing_mode ?? null) === 'manual') {
            return round($manualCompletionPercentage, 2);
        }
        
        // OCR contributes up to 40% of final score
        $ocrContribution = ($ocrScore / 100) * 40;
        
        // Manual completion contributes up to 60% of final score
        // If manual_completion_score = 100%, this gives 60%
        // If manual_completion_score = 50%, this gives 30%
        $manualContribution = ($manualCompletionPercentage / 100) * 60;
        
        $validationScore = $ocrContribution + $manualContribution;
        
        Log::debug('MODEL A: Validation score calculated', [
            'scan_id' => $this->id,
            'ocr_confidence' => $ocrScore,
            'manual_completion_percentage' => $manualCompletionPercentage,
            'ocr_contribution' => $ocrContribution,
            'manual_contribution' => $manualContribution,
            'final_validation_score' => $validationScore,
        ]);
        
        return round($validationScore, 2);
    }

    /**
     * MODEL A: Calculate manual completion score
     * Based on filled required fields
     */
    public function calculateManualCompletionScore(): float
{
    Log::info('MODEL A: Starting manual completion calculation', [
        'scan_id' => $this->id,
        'document_type' => $this->document_type,
        'extracted_fields_type' => gettype($this->extracted_fields),
        'extracted_fields_raw' => $this->extracted_fields
    ]);

    if (!$this->extracted_fields) {
        Log::debug('MODEL A: No extracted fields', ['scan_id' => $this->id]);
        return 0.0;
    }

    // Parse extracted fields if stored as JSON string
    $fields = is_string($this->extracted_fields) 
        ? json_decode($this->extracted_fields, true) 
        : $this->extracted_fields;

    if (!is_array($fields)) {
        Log::warning('MODEL A: Extracted fields is not an array', [
            'scan_id' => $this->id,
            'type' => gettype($this->extracted_fields),
            'raw_data' => $this->extracted_fields
        ]);
        return 0.0;
    }

    $requiredFields = $this->getRequiredFieldsForType($this->document_type);
    
    if (empty($requiredFields)) {
        Log::debug('MODEL A: No required fields defined for type', [
            'scan_id' => $this->id,
            'document_type' => $this->document_type
        ]);
        return 100.0;
    }

    $filledCount = 0;
    $missingFields = [];
    $fieldDebug = [];
    
    foreach ($requiredFields as $field) {
        // CRITICAL: Check if field exists and has non-empty value
        $hasValue = isset($fields[$field]) && trim((string)$fields[$field]) !== '';
        
        if ($hasValue) {
            $filledCount++;
        } else {
            $missingFields[] = $field;
        }
        
        // Enhanced debugging
        $fieldDebug[$field] = [
            'exists' => isset($fields[$field]),
            'value' => $fields[$field] ?? 'NOT_SET',
            'trimmed_length' => isset($fields[$field]) ? strlen(trim((string)$fields[$field])) : 0,
            'is_filled' => $hasValue
        ];
    }

    $completionScore = ($filledCount / count($requiredFields)) * 100;
    
    Log::info('MODEL A: Manual completion calculated with detailed debugging', [
        'scan_id' => $this->id,
        'document_type' => $this->document_type,
        'required_fields' => $requiredFields,
        'field_debug' => $fieldDebug,
        'available_fields' => array_keys($fields),
        'required_fields_count' => count($requiredFields),
        'filled_fields_count' => $filledCount,
        'missing_fields' => $missingFields,
        'completion_score' => $completionScore,
    ]);
    
    return round($completionScore, 2);
}

    /**
     * MODEL A: Get required fields based on document type
     */
    private function getRequiredFieldsForType(string $documentType): array
{
    $requiredFields = [
        'birth_certificate' => [
            'name_first',
            'name_last',
            'birth_date_day',
            'birth_date_month',
            'birth_date_year',
            'mother_first_name',
            'mother_last_name',
            'father_first_name',
            'father_last_name',
        ],
        'death_certificate' => [
            'deceased_first_name',
            'deceased_last_name',
            'death_date_day',
            'death_date_month',
            'death_date_year',
            'cause_of_death',
        ],
        'marriage_certificate' => [
            'groom_first_name',
            'groom_last_name',
            'bride_first_name',
            'bride_last_name',
            'marriage_date',
        ],
    ];

    return $requiredFields[$documentType] ?? [];
}

    /**
     * MODEL A: Update validation scores and blockchain eligibility
     */
    public function updateValidationStatus(): void
{
    $validationScore = $this->calculateValidationScore();
    $manualScore = $this->calculateManualCompletionScore();
    
    Log::info('MODEL A: Updating validation status', [
        'scan_id' => $this->id,
        'validation_score' => $validationScore,
        'manual_completion' => $manualScore,
        'threshold' => self::VALIDATION_THRESHOLD,
        'old_status' => $this->verification_status
    ]);
    
    // CRITICAL FIX: More aggressive completion logic for your specific case
    if ($manualScore >= 100 && $validationScore >= self::VALIDATION_THRESHOLD) {
        // Perfect: Manual complete + validation passes threshold
        $this->verification_status = 'completed';
        $this->blockchain_eligible = true;
        
    } elseif ($manualScore >= 100 && $validationScore >= 60) {
        // FIXED: Lower threshold for 100% manual completion (your case: 93.2%)
        $this->verification_status = 'completed';
        $this->blockchain_eligible = true;
        
    } elseif ($manualScore >= 100) {
        // ANY document with 100% manual completion should be completed
        $this->verification_status = 'completed';
        $this->blockchain_eligible = true;
        
    } elseif ($manualScore >= 70 && $validationScore >= 60) {
        // Good progress - pending review
        $this->verification_status = 'pending';
        $this->blockchain_eligible = false;
        
    } elseif ($manualScore >= 50) {
        // Partially complete - needs more work
        $this->verification_status = 'pending';
        $this->blockchain_eligible = false;
        
    } else {
        // Incomplete - rejected
        $this->verification_status = 'rejected';
        $this->blockchain_eligible = false;
    }
    
    // Update validation scores
    $this->manual_completion_score = $manualScore;
    $this->validation_score = $validationScore;
    
    Log::info('MODEL A: Validation status updated', [
        'scan_id' => $this->id,
        'new_status' => $this->verification_status,
        'blockchain_eligible' => $this->blockchain_eligible,
        'validation_score' => $validationScore,
        'manual_completion' => $manualScore
    ]);
    
    $this->save();
}

    /**
     * MODEL A: Check if ready for blockchain anchoring
     * THIS IS THE CRITICAL MISSING METHOD
     */
    public function isReadyForBlockchainAnchoring(): bool
    {
        // Must be completed verification first
        if ($this->verification_status !== 'completed') {
            return false;
        }
        
        // Must not be already anchored or failed
        if (in_array($this->blockchain_status, ['confirmed', 'failed'])) {
            return false;
        }
        
        // Check validation criteria
        $validationScore = $this->validation_score ?? $this->calculateValidationScore();
        $manualScore = $this->manual_completion_score ?? $this->calculateManualCompletionScore();
        
        // ENHANCED CRITERIA: More flexible for manually completed documents
        $isEligible = false;
        
        // Criteria 1: Meets validation threshold
        if ($validationScore >= self::VALIDATION_THRESHOLD) {
            $isEligible = true;
        }
        
        // Criteria 2: Manual completion is perfect (allow slightly lower validation score)
        elseif ($manualScore >= 100 && $validationScore >= (self::VALIDATION_THRESHOLD - 10)) {
            $isEligible = true;
        }
        
        // Criteria 3: Very high manual completion with decent OCR
        elseif ($manualScore >= 100 && $this->ocr_confidence >= 40) {
            $isEligible = true;
        }
        
        Log::debug('MODEL A: Blockchain eligibility check', [
            'scan_id' => $this->id,
            'validation_score' => $validationScore,
            'manual_completion' => $manualScore,
            'ocr_confidence' => $this->ocr_confidence,
            'verification_status' => $this->verification_status,
            'blockchain_status' => $this->blockchain_status,
            'is_eligible' => $isEligible
        ]);
        
        return $isEligible;
    }

    /**
 * MODEL A: Check if all required fields are filled
 */
    public function hasAllRequiredFieldsFilled(): bool
    {
        if (!$this->extracted_fields) {
            return false;
        }

        $fields = is_array($this->extracted_fields) 
            ? $this->extracted_fields 
            : json_decode($this->extracted_fields, true);

        if (!is_array($fields)) {
            return false;
        }

        $requiredFields = $this->getRequiredFieldsForType($this->document_type);
        
        if (empty($requiredFields)) {
            return true; // No required fields = considered complete
        }

        foreach ($requiredFields as $field) {
            if (!isset($fields[$field]) || trim($fields[$field]) === '') {
                return false; // Found an empty required field
            }
        }

        return true; // All required fields are filled
    }

    /**
 * MODEL A: Get list of missing required fields
 */
public function getMissingRequiredFields(): array
    {
        if (!$this->extracted_fields) {
            return $this->getRequiredFieldsForType($this->document_type);
        }

        $fields = is_array($this->extracted_fields) 
            ? $this->extracted_fields 
            : json_decode($this->extracted_fields, true);

        if (!is_array($fields)) {
            return $this->getRequiredFieldsForType($this->document_type);
        }

        $requiredFields = $this->getRequiredFieldsForType($this->document_type);
        $missingFields = [];

        foreach ($requiredFields as $field) {
            if (!isset($fields[$field]) || trim($fields[$field]) === '') {
                $missingFields[] = $field;
            }
        }

        return $missingFields;
    }

/**
 * MODEL A: Validate document is ready for submission
 */
public function validateForSubmission(): array
    {
        $errors = [];

        // Check if all required fields are filled
        if (!$this->hasAllRequiredFieldsFilled()) {
            $missingFields = $this->getMissingRequiredFields();
            $errors[] = 'Missing required fields: ' . implode(', ', array_map(function($field) {
                return str_replace('_', ' ', ucfirst($field));
            }, $missingFields));
        }

        // Check if manual completion is at 100%
        if ($this->manual_completion_score < 100) {
            $errors[] = "Manual completion must be 100% before submission. Current: {$this->manual_completion_score}%";
        }

        // Check if validation score meets threshold (only if manual is complete)
        if ($this->manual_completion_score >= 100 && $this->validation_score < self::VALIDATION_THRESHOLD) {
            $errors[] = "Validation score ({$this->validation_score}%) is below threshold (" . self::VALIDATION_THRESHOLD . "%)";
        }

        return $errors;
    }


    /**
     * Boot method for model events
     */
protected static function boot()
    {
    parent::boot();

    // Automatically set processing timestamp when status changes
    static::updating(function ($scan) {
        if ($scan->isDirty('verification_status') && 
            $scan->verification_status !== 'draft' && 
            !$scan->processed_at) {
            $scan->processed_at = Carbon::now();
        }
    });

    // MODEL A: Auto-calculate validation scores on create/update
    static::saving(function ($scan) {
        // Calculate scores if OCR data exists but scores are NULL
        if ($scan->ocr_confidence !== null && 
            ($scan->validation_score === null || $scan->manual_completion_score === null)) {
            
            $scan->manual_completion_score = $scan->calculateManualCompletionScore();
            $scan->validation_score = $scan->calculateValidationScore();
            $scan->blockchain_eligible = $scan->validation_score >= self::VALIDATION_THRESHOLD;
        }
    });
    }


/**
 * Get formatted gas cost in ETH
 * 
 * WHY: Display human-readable cost to users
 * 
 * @return string
 */
public function getGasCostEthAttribute(): string
{
    if (!$this->blockchain_gas_used || !$this->blockchain_gas_price) {
        return 'N/A';
    }
    
    // Calculate: gas_used × gas_price (in Wei)
    $costWei = $this->blockchain_gas_used * (int)$this->blockchain_gas_price;
    
    // Convert Wei to ETH (1 ETH = 10^18 Wei)
    $costEth = $costWei / 1000000000000000000;
    
    return number_format($costEth, 10) . ' ETH';
}

/**
 * Get shortened transaction hash for display
 * 
 * WHY: Show abbreviated hash (0x1234...abcd)
 * 
 * @return string
 */
public function getShortTxHashAttribute(): string
{
    if (!$this->blockchain_tx_hash) {
        return 'N/A';
    }
    
    return substr($this->blockchain_tx_hash, 0, 10) . '...' . substr($this->blockchain_tx_hash, -8);
}

/**
 * Get Ganache explorer URL for transaction
 * 
 * WHY: Link users to view transaction in Ganache
 * 
 * @return string|null
 */
public function getGanacheExplorerUrlAttribute(): ?string
{
    if (!$this->blockchain_tx_hash) {
        return null;
    }
    
    // Ganache doesn't have built-in explorer, but you can add Ganache UI link
    return env('GANACHE_URL', 'http://127.0.0.1:7545') . '/tx/' . $this->blockchain_tx_hash;
}

/**
 * Check if transaction was successful
 * 
 * WHY: Quick boolean check for UI display
 * 
 * @return bool
 */
public function isBlockchainTransactionSuccessful(): bool
{
    return $this->blockchain_status_code === '0x1';
}
/**
     * Get all correction requests for this document.
     */
    public function correctionRequests()
    {
        return $this->hasMany(CorrectionRequest::class, 'scan_id');
    }

    /**
     * Get all correction records for this document.
     */
    public function correctionRecords()
    {
        return $this->hasMany(CorrectionRecord::class, 'scan_id');
    }

    /**
     * Get pending correction requests for this document.
     */
    public function pendingCorrectionRequests()
    {
        return $this->correctionRequests()->pending();
    }

    /**
     * Get confirmed corrections for this document.
     */
    public function confirmedCorrections()
    {
        return $this->correctionRecords()->confirmed()->latestFirst();
    }

    /**
     * Check if document has any corrections.
     */
    public function hasCorrections(): bool
    {
        return $this->correctionRecords()->confirmed()->exists();
    }

    /**
     * Get the latest corrected value for a specific field.
     * Returns original value if no correction exists.
     */
    public function getCorrectedValue(string $fieldName): ?string
    {
        $latestCorrection = $this->correctionRecords()
            ->confirmed()
            ->forField($fieldName)
            ->latestFirst()
            ->first();

        if ($latestCorrection) {
            return $latestCorrection->new_value;
        }

        // Return original value from extracted_fields
        $extractedFields = $this->extracted_fields;
        return $extractedFields[$fieldName] ?? null;
    }

    /**
     * Get all extracted fields with corrections applied.
     * Returns array with both original and corrected values.
     */
    public function getFieldsWithCorrections(): array
    {
        $originalFields = $this->extracted_fields ?? [];
        $corrections = $this->correctionRecords()
            ->confirmed()
            ->latestFirst()
            ->get()
            ->groupBy('corrected_field');

        $result = [];

        foreach ($originalFields as $fieldName => $originalValue) {
            $fieldCorrections = $corrections->get($fieldName);
            $latestCorrection = $fieldCorrections?->first();

            $result[$fieldName] = [
                'original_value' => $originalValue,
                'current_value' => $latestCorrection ? $latestCorrection->new_value : $originalValue,
                'has_correction' => $latestCorrection !== null,
                'correction_count' => $fieldCorrections?->count() ?? 0,
                'latest_correction' => $latestCorrection,
            ];
        }

        return $result;
    }

    /**
     * Get correction history for this document.
     */
    public function getCorrectionHistory()
    {
        return $this->correctionRecords()
            ->with(['staff', 'supervisor'])
            ->latestFirst()
            ->get();
    }

    /**
     * Check if document can accept correction requests.
     * Only blockchain-confirmed documents can be corrected.
     */
    public function canRequestCorrection(): bool
    {
        return $this->blockchain_status === 'confirmed' 
            && !empty($this->blockchain_tx_hash);
    }

    /**
     * Get correctable fields for this document.
     * Returns fields that can be corrected via correction request.
     */
    public function getCorrectableFields(): array
    {
        $extractedFields = $this->extracted_fields;
        $result = [];

        // Handle case where extracted_fields might be a string (JSON) or null
        if (is_string($extractedFields)) {
            $extractedFields = json_decode($extractedFields, true);
        }

        // Ensure we have an array
        if (!is_array($extractedFields)) {
            $extractedFields = [];
        }

        // Fields to exclude from correction (metadata fields)
        $excludedFields = [
            'confidence', 
            'word_count', 
            'fields_detected', 
            'manually_corrected', 
            'raw_text', 
            'certificate_type',
            'ocr_confidence',
            'validation_score',
            'document_type',
            'processing_notes',
        ];

        foreach ($extractedFields as $fieldName => $value) {
            // Skip excluded metadata fields
            if (in_array($fieldName, $excludedFields)) {
                continue;
            }

            // Skip fields with numeric keys (usually arrays)
            if (is_numeric($fieldName)) {
                continue;
            }

            // Skip nested arrays/objects - only allow scalar values
            if (is_array($value) || is_object($value)) {
                continue;
            }

            $result[$fieldName] = [
                'label' => ucwords(str_replace('_', ' ', $fieldName)),
                'value' => is_null($value) ? '' : (string) $value,
            ];
        }

        return $result;
    }

}