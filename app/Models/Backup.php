<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Backup extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'description',
        'status',
        'initiated_by',
        'started_at',
        'completed_at',
        'file_path',
        'file_size',
        'checksum',
        'includes_database',
        'includes_documents',
        'includes_audit_logs',
        'failure_reason',
        'error_message',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'includes_database' => 'boolean',
        'includes_documents' => 'boolean',
        'includes_audit_logs' => 'boolean',
    ];

    // Relationships
    public function initiator()
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    // Accessors
    public function getFileSizeHumanAttribute()
    {
        if (!$this->file_size) {
            return '-';
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeManual($query)
    {
        return $query->where('type', 'manual');
    }
}