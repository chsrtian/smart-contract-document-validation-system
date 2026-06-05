<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'timestamp',
        'user_id',
        'user_name',
        'action_type',
        'target_entity_type',
        'target_entity_id',
        'previous_value',
        'new_value',
        'ip_address',
        'severity',
        'notes',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'previous_value' => 'array',
        'new_value' => 'array',
    ];

    // Make audit logs immutable
    protected static function boot()
    {
        parent::boot();

        // Prevent updates
        static::updating(function () {
            return false;
        });

        // Prevent deletes
        static::deleting(function () {
            return false;
        });
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByAction($query, $actionType)
    {
        return $query->where('action_type', $actionType);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('timestamp', [$startDate, $endDate]);
    }

    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where('notes', 'like', "%{$search}%");
    }
}