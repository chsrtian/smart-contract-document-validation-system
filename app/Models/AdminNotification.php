<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AdminNotification extends Model
{
    protected $fillable = [
        'type',
        'severity',
        'title',
        'message',
        'data',
        'related_id',
        'related_type',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    // Notification types
    const TYPE_ESCALATION = 'escalation';
    const TYPE_BACKUP_FAILED = 'backup_failed';
    const TYPE_BLOCKCHAIN_FAILED = 'blockchain_failed';
    const TYPE_SYSTEM_ERROR = 'system_error';
    const TYPE_USER_DEACTIVATED = 'user_deactivated';
    const TYPE_DOCUMENT_FLAGGED = 'document_flagged';
    const TYPE_OVERRIDE_USED = 'override_used';
    const TYPE_MONTHLY_REPORT = 'monthly_report';
    const TYPE_BACKUP_SUCCESS = 'backup_success';

    // Severity levels
    const SEVERITY_INFO = 'info';
    const SEVERITY_WARNING = 'warning';
    const SEVERITY_CRITICAL = 'critical';

    /**
     * Polymorphic relationship to related entity
     */
    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope: Unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope: By severity
     */
    public function scopeSeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope: By type
     */
    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Mark as read
     */
    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Get severity badge color
     */
    public function getSeverityColorAttribute(): string
    {
        return match($this->severity) {
            self::SEVERITY_CRITICAL => 'red',
            self::SEVERITY_WARNING => 'yellow',
            self::SEVERITY_INFO => 'blue',
            default => 'gray',
        };
    }

    /**
     * Get severity icon
     */
    public function getSeverityIconAttribute(): string
    {
        return match($this->severity) {
            self::SEVERITY_CRITICAL => '🚨',
            self::SEVERITY_WARNING => '⚠️',
            self::SEVERITY_INFO => 'ℹ️',
            default => '📌',
        };
    }

    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            self::TYPE_ESCALATION => 'Escalation',
            self::TYPE_BACKUP_FAILED => 'Backup Failed',
            self::TYPE_BLOCKCHAIN_FAILED => 'Blockchain Failed',
            self::TYPE_SYSTEM_ERROR => 'System Error',
            self::TYPE_USER_DEACTIVATED => 'User Deactivated',
            self::TYPE_DOCUMENT_FLAGGED => 'Document Flagged',
            self::TYPE_OVERRIDE_USED => 'Override Used',
            self::TYPE_MONTHLY_REPORT => 'Monthly Report',
            self::TYPE_BACKUP_SUCCESS => 'Backup Success',
            default => ucwords(str_replace('_', ' ', $this->type)),
        };
    }
}