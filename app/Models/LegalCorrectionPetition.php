<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LegalCorrectionPetition extends Model
{
    use HasFactory;

    protected $table = 'legal_correction_petitions';

    protected $fillable = [
        'petition_number',
        'scan_id',
        'petition_type',
        'legal_basis',
        'petitioner_name',
        'petitioner_address',
        'petitioner_relationship',
        'reason',
        'supporting_affidavit',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'admin_notes',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending_approval';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_FORWARDED = 'forwarded_to_psa';

    // Petition type constants
    const TYPE_CLERICAL = 'ra_9048_clerical';
    const TYPE_FIRST_NAME = 'ra_9048_first_name';
    const TYPE_GENDER = 'ra_10172_gender';
    const TYPE_BIRTHDATE = 'ra_10172_birthdate';

    const PETITION_TYPES = [
        self::TYPE_CLERICAL => 'Clerical/Typographical Error (RA 9048)',
        self::TYPE_FIRST_NAME => 'Change of First Name (RA 9048)',
        self::TYPE_GENDER => 'Correction of Gender/Sex (RA 10172)',
        self::TYPE_BIRTHDATE => 'Correction of Day/Month of Birth (RA 10172)',
    ];

    const LEGAL_BASIS_MAP = [
        self::TYPE_CLERICAL => 'Republic Act No. 9048',
        self::TYPE_FIRST_NAME => 'Republic Act No. 9048',
        self::TYPE_GENDER => 'Republic Act No. 10172',
        self::TYPE_BIRTHDATE => 'Republic Act No. 10172',
    ];

    const RELATIONSHIP_TYPES = [
        'self' => 'Self (Owner of Document)',
        'parent' => 'Parent',
        'guardian' => 'Legal Guardian',
        'authorized_representative' => 'Authorized Representative',
    ];

    /**
     * Generate a unique petition number.
     */
    public static function generatePetitionNumber(): string
    {
        $year = date('Y');
        $prefix = 'LCP';
        $lastPetition = static::where('petition_number', 'like', "{$prefix}-{$year}-%")
            ->orderBy('id', 'desc')
            ->first();

        if ($lastPetition) {
            $lastSeq = (int) substr($lastPetition->petition_number, -5);
            $nextSeq = $lastSeq + 1;
        } else {
            $nextSeq = 1;
        }

        return sprintf('%s-%s-%05d', $prefix, $year, $nextSeq);
    }

    // ── Relationships ──

    public function scan(): BelongsTo
    {
        return $this->belongsTo(Scan::class, 'scan_id');
    }

    public function document(): BelongsTo
    {
        return $this->scan();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function fieldChanges(): HasMany
    {
        return $this->hasMany(PetitionFieldChange::class, 'petition_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(PetitionAttachment::class, 'petition_id');
    }

    public function annotations(): HasMany
    {
        return $this->hasMany(MarginalAnnotation::class, 'petition_id');
    }

    public function psaForwardingLog(): HasOne
    {
        return $this->hasOne(PsaForwardingLog::class, 'petition_id');
    }

    // ── Helpers ──

    public function getLegalBasisAttribute(): string
    {
        return self::LEGAL_BASIS_MAP[$this->petition_type] ?? 'N/A';
    }

    public function getPetitionTypeLabelAttribute(): string
    {
        return self::PETITION_TYPES[$this->petition_type] ?? 'Unknown';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PENDING => 'Pending Approval',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_FORWARDED => 'Forwarded to PSA',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'gray',
            self::STATUS_PENDING => 'amber',
            self::STATUS_APPROVED => 'green',
            self::STATUS_REJECTED => 'red',
            self::STATUS_FORWARDED => 'blue',
            default => 'gray',
        };
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isForwarded(): bool
    {
        return $this->status === self::STATUS_FORWARDED;
    }

    public function canBeEdited(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function canBeSubmitted(): bool
    {
        return $this->status === self::STATUS_DRAFT && $this->fieldChanges()->count() > 0;
    }

    public function canBeApproved(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canBeForwarded(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }
}
