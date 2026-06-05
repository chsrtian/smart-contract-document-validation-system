<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditLogService
{
    /**
     * Log an action to the audit log
     *
     * @param string $actionType The type of action (e.g., 'user.created', 'document.locked')
     * @param mixed $targetEntity The target entity (model instance or null)
     * @param array|null $previousValue The previous state (null for create actions)
     * @param array|null $newValue The new state (null for delete actions)
     * @param string $severity 'info', 'warning', or 'critical'
     * @param string|null $notes Additional context notes
     * @return AuditLog
     */
    public static function log(
        string $actionType,
        $targetEntity = null,
        ?array $previousValue = null,
        ?array $newValue = null,
        string $severity = 'info',
        ?string $notes = null
    ): AuditLog {
        $user = Auth::user();

        return AuditLog::create([
            'timestamp' => now(),
            'user_id' => $user?->id,
            'user_name' => $user?->name ?? 'System',
            'action_type' => $actionType,
            'target_entity_type' => $targetEntity ? get_class($targetEntity) : null,
            'target_entity_id' => $targetEntity?->id ?? null,
            'previous_value' => $previousValue,
            'new_value' => $newValue,
            'ip_address' => request()->ip(),
            'severity' => $severity,
            'notes' => $notes,
        ]);
    }
}