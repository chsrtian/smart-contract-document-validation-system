<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PsaForwardingLog extends Model
{
    use HasFactory;

    protected $table = 'psa_forwarding_logs';

    protected $fillable = [
        'petition_id',
        'forwarding_reference',
        'forwarding_status',
        'forwarded_at',
        'acknowledged_at',
        'completed_at',
        'psa_remarks',
        'transmittal_details',
        'forwarded_by',
    ];

    protected $casts = [
        'forwarded_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_TRANSMITTED = 'transmitted';
    const STATUS_ACKNOWLEDGED = 'acknowledged';
    const STATUS_COMPLETED = 'completed';

    /**
     * Generate unique forwarding reference.
     */
    public static function generateForwardingReference(): string
    {
        $year = date('Y');
        $month = date('m');
        $prefix = 'PSA-FWD';
        $lastLog = static::where('forwarding_reference', 'like', "{$prefix}-{$year}{$month}-%")
            ->orderBy('id', 'desc')
            ->first();

        if ($lastLog) {
            $lastSeq = (int) substr($lastLog->forwarding_reference, -4);
            $nextSeq = $lastSeq + 1;
        } else {
            $nextSeq = 1;
        }

        return sprintf('%s-%s%s-%04d', $prefix, $year, $month, $nextSeq);
    }

    public function petition(): BelongsTo
    {
        return $this->belongsTo(LegalCorrectionPetition::class, 'petition_id');
    }

    public function forwarder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'forwarded_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->forwarding_status) {
            self::STATUS_PENDING => 'Pending Transmittal',
            self::STATUS_TRANSMITTED => 'Transmitted to PSA',
            self::STATUS_ACKNOWLEDGED => 'Acknowledged by PSA',
            self::STATUS_COMPLETED => 'Completed',
            default => ucfirst($this->forwarding_status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->forwarding_status) {
            self::STATUS_PENDING => 'amber',
            self::STATUS_TRANSMITTED => 'blue',
            self::STATUS_ACKNOWLEDGED => 'indigo',
            self::STATUS_COMPLETED => 'green',
            default => 'gray',
        };
    }
}
