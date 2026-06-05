<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorrectionRecord extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'correction_records';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'scan_id',
        'reference_tx_hash',
        'corrected_field',
        'previous_value',
        'new_value',
        'correction_reason',
        'corrected_by_staff',
        'approved_by_supervisor',
        'correction_tx_hash',
        'correction_document_hash',
        'blockchain_status',
        'blockchain_submitted_at',
        'blockchain_confirmed_at',
        'blockchain_metadata',
        'correction_request_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'blockchain_submitted_at' => 'datetime',
        'blockchain_confirmed_at' => 'datetime',
        'blockchain_metadata' => 'array',
    ];

    /**
     * Blockchain status constants
     */
    const BLOCKCHAIN_PENDING = 'pending';
    const BLOCKCHAIN_CONFIRMED = 'confirmed';
    const BLOCKCHAIN_FAILED = 'failed';

    /**
     * Get the document (scan) this correction is for.
     */
    public function scan(): BelongsTo
    {
        return $this->belongsTo(Scan::class, 'scan_id');
    }

    /**
     * Alias for scan relationship.
     */
    public function document(): BelongsTo
    {
        return $this->scan();
    }

    /**
     * Get the staff member who requested the correction.
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corrected_by_staff');
    }

    /**
     * Get the supervisor who approved the correction.
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_supervisor');
    }

    /**
     * Get the original correction request.
     */
    public function correctionRequest(): BelongsTo
    {
        return $this->belongsTo(CorrectionRequest::class, 'correction_request_id');
    }

    /**
     * Scope: Get corrections for a specific document.
     */
    public function scopeForDocument($query, $scanId)
    {
        return $query->where('scan_id', $scanId);
    }

    /**
     * Scope: Get corrections for a specific field.
     */
    public function scopeForField($query, $fieldName)
    {
        return $query->where('corrected_field', $fieldName);
    }

    /**
     * Scope: Get only confirmed corrections.
     */
    public function scopeConfirmed($query)
    {
        return $query->where('blockchain_status', self::BLOCKCHAIN_CONFIRMED);
    }

    /**
     * Scope: Get latest correction first.
     */
    public function scopeLatestFirst($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Check if correction is confirmed on blockchain.
     */
    public function isConfirmed(): bool
    {
        return $this->blockchain_status === self::BLOCKCHAIN_CONFIRMED;
    }

    /**
     * Check if correction is pending blockchain confirmation.
     */
    public function isPending(): bool
    {
        return $this->blockchain_status === self::BLOCKCHAIN_PENDING;
    }

    /**
     * Check if correction failed to anchor.
     */
    public function hasFailed(): bool
    {
        return $this->blockchain_status === self::BLOCKCHAIN_FAILED;
    }

    /**
     * Get formatted field name for display.
     */
    public function getFieldDisplayNameAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->corrected_field));
    }

    /**
     * Get status badge color class.
     */
    public function getBlockchainStatusColorAttribute(): string
    {
        return match($this->blockchain_status) {
            self::BLOCKCHAIN_PENDING => 'bg-yellow-100 text-yellow-800',
            self::BLOCKCHAIN_CONFIRMED => 'bg-green-100 text-green-800',
            self::BLOCKCHAIN_FAILED => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get human-readable blockchain status.
     */
    public function getBlockchainStatusLabelAttribute(): string
    {
        return match($this->blockchain_status) {
            self::BLOCKCHAIN_PENDING => 'Pending Confirmation',
            self::BLOCKCHAIN_CONFIRMED => 'Confirmed on Blockchain',
            self::BLOCKCHAIN_FAILED => 'Failed',
            default => 'Unknown',
        };
    }

    /**
     * Create a correction record from an approved request.
     */
    public static function createFromRequest(CorrectionRequest $request, User $supervisor): self
    {
        return self::create([
            'scan_id' => $request->scan_id,
            'reference_tx_hash' => $request->original_tx_hash,
            'corrected_field' => $request->field_name,
            'previous_value' => $request->current_value,
            'new_value' => $request->proposed_value,
            'correction_reason' => $request->reason,
            'corrected_by_staff' => $request->requested_by,
            'approved_by_supervisor' => $supervisor->id,
            'correction_request_id' => $request->id,
            'blockchain_status' => self::BLOCKCHAIN_PENDING,
        ]);
    }

    /**
     * Get data payload for blockchain anchoring.
     */
    public function getBlockchainPayload(): array
    {
        return [
            'type' => 'document_correction',
            'reference_tx_hash' => $this->reference_tx_hash,
            'corrected_field' => [
                'field_name' => $this->corrected_field,
                'previous_value' => $this->previous_value,
                'new_value' => $this->new_value,
            ],
            'correction_reason' => $this->correction_reason,
            'corrected_by_staff' => $this->corrected_by_staff,
            'approved_by_supervisor' => $this->approved_by_supervisor,
            'timestamp' => now()->toISOString(),
            'document_id' => $this->scan_id,
        ];
    }
}