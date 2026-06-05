<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CorrectionRequest extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'correction_requests';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'scan_id',
        'original_tx_hash',
        'field_name',
        'current_value',
        'proposed_value',
        'reason',
        'supporting_document_path',
        'requested_by',
        'requested_at',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
        'correction_record_id',
        'override_by',
        'override_at',
        'override_justification',
        'override_type',
        'escalated_at',
        'escalated_reason',
        'assigned_to',
        'escalation_status',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'requested_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'override_at' => 'datetime',
        'escalated_at' => 'datetime',

    ];

    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Get the document (scan) this correction request is for.
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
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the supervisor who reviewed the request.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get the correction record (if approved and created).
     */
    public function correctionRecord(): HasOne
    {
        return $this->hasOne(CorrectionRecord::class, 'correction_request_id');
    }

    /**
     * Scope: Get only pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope: Get only approved requests.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope: Get only rejected requests.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Scope: Get requests by a specific staff member.
     */
    public function scopeByRequester($query, $userId)
    {
        return $query->where('requested_by', $userId);
    }

    /**
     * Scope: Get requests for a specific document.
     */
    public function scopeForDocument($query, $scanId)
    {
        return $query->where('scan_id', $scanId);
    }

    /**
     * Check if the request is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if the request is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if the request is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Check if the request is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Approve the request (called by supervisor).
     */
    public function approve(User $supervisor): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_APPROVED,
            'reviewed_by' => $supervisor->id,
            'reviewed_at' => now(),
        ]);

        return true;
    }

    /**
     * Reject the request (called by supervisor).
     */
    public function reject(User $supervisor, string $reason): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        if (strlen($reason) < 10) {
            throw new \InvalidArgumentException('Rejection reason must be at least 10 characters.');
        }

        $this->update([
            'status' => self::STATUS_REJECTED,
            'reviewed_by' => $supervisor->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);

        return true;
    }

    /**
     * Get human-readable status.
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Pending Review',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_CANCELLED => 'Cancelled',
            default => 'Unknown',
        };
    }

    /**
     * Get status badge color class.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'bg-yellow-100 text-yellow-800',
            self::STATUS_APPROVED => 'bg-green-100 text-green-800',
            self::STATUS_REJECTED => 'bg-red-100 text-red-800',
            self::STATUS_CANCELLED => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get formatted field name for display.
     */
    public function getFieldDisplayNameAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->field_name));
    }

    /**
 * Get the admin who performed the override.
 */
    public function overrider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'override_by');
    }

    /**
     * Check if the request was overridden.
     */
    public function isOverridden(): bool
    {
        return !is_null($this->override_by);
    }

    /**
     * Check if the request can be overridden.
     */
    public function canBeOverridden(): bool
    {
        return in_array($this->status, [self::STATUS_REJECTED, self::STATUS_APPROVED]);
    }

    /**
     * Scope: Get only overridden requests.
     */
    public function scopeOverridden($query)
    {
        return $query->whereNotNull('override_by');
    }

    /**
 * Get the supervisor assigned to handle this escalation.
 */
    public function assignedSupervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Check if the request is escalated.
     */
    public function isEscalated(): bool
    {
        return !is_null($this->escalated_at);
    }

    /**
     * Check if escalation is pending assignment.
     */
    public function isEscalationPending(): bool
    {
        return $this->escalation_status === 'pending';
    }

    /**
     * Scope: Get escalated requests.
     */
    public function scopeEscalated($query)
    {
        return $query->whereNotNull('escalated_at');
    }

    /**
     * Scope: Get pending escalations.
     */
    public function scopeEscalationPending($query)
    {
        return $query->where('escalation_status', 'pending');
    }
    
}