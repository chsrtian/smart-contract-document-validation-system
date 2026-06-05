<?php

namespace App\Services;

use App\Models\AdminNotification;

class NotificationService
{
    /**
     * Create a new notification
     */
    public static function create(
        string $type,
        string $severity,
        string $title,
        string $message,
        $relatedModel = null,
        array $additionalData = []
    ): AdminNotification {
        return AdminNotification::create([
            'type' => $type,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'related_id' => $relatedModel?->id,
            'related_type' => $relatedModel ? get_class($relatedModel) : null,
            'data' => $additionalData,
        ]);
    }

    /**
     * Notify about escalation
     */
    public static function notifyEscalation($correctionRequest): void
    {
        self::create(
            AdminNotification::TYPE_ESCALATION,
            AdminNotification::SEVERITY_WARNING,
            'Correction Request Escalated',
            "Correction request #{$correctionRequest->id} has been escalated after 48 hours pending.",
            $correctionRequest,
            ['correction_id' => $correctionRequest->id]
        );
    }

    /**
     * Notify about backup failure
     */
    public static function notifyBackupFailed($backup, string $errorMessage): void
    {
        self::create(
            AdminNotification::TYPE_BACKUP_FAILED,
            AdminNotification::SEVERITY_CRITICAL,
            'Backup Failed',
            "Backup failed: {$errorMessage}",
            $backup,
            ['error' => $errorMessage]
        );
    }

    /**
     * Notify about backup success
     */
    public static function notifyBackupSuccess($backup): void
    {
        self::create(
            AdminNotification::TYPE_BACKUP_SUCCESS,
            AdminNotification::SEVERITY_INFO,
            'Backup Completed Successfully',
            "Backup '{$backup->description}' completed successfully. Size: " . round($backup->file_size / 1024 / 1024, 2) . " MB",
            $backup,
            ['file_size' => $backup->file_size]
        );
    }

    /**
     * Notify about blockchain failure
     */
    public static function notifyBlockchainFailed($document): void
    {
        self::create(
            AdminNotification::TYPE_BLOCKCHAIN_FAILED,
            AdminNotification::SEVERITY_WARNING,
            'Blockchain Transaction Failed',
            "Document #{$document->id} failed to anchor to blockchain.",
            $document,
            ['document_id' => $document->id]
        );
    }

    /**
     * Notify about document flagged
     */
    public static function notifyDocumentFlagged($document, string $reason): void
    {
        self::create(
            AdminNotification::TYPE_DOCUMENT_FLAGGED,
            AdminNotification::SEVERITY_WARNING,
            'Document Flagged',
            "Document #{$document->id} has been flagged: {$reason}",
            $document,
            ['reason' => $reason]
        );
    }

    /**
     * Notify about override used
     */
    public static function notifyOverrideUsed($correctionRequest, $adminUser): void
    {
        self::create(
            AdminNotification::TYPE_OVERRIDE_USED,
            AdminNotification::SEVERITY_WARNING,
            'Admin Override Used',
            "Admin {$adminUser->name} used override on correction request #{$correctionRequest->id}",
            $correctionRequest,
            [
                'admin_id' => $adminUser->id,
                'admin_name' => $adminUser->name,
            ]
        );
    }

    /**
     * Notify about user deactivated
     */
    public static function notifyUserDeactivated($user, $adminUser): void
    {
        self::create(
            AdminNotification::TYPE_USER_DEACTIVATED,
            AdminNotification::SEVERITY_INFO,
            'User Deactivated',
            "User {$user->name} was deactivated by {$adminUser->name}",
            $user,
            [
                'deactivated_user' => $user->name,
                'admin_name' => $adminUser->name,
            ]
        );
    }

    /**
     * Notify about monthly report generated
     */
    public static function notifyMonthlyReport(array $reportData): void
    {
        $period = $reportData['period'];
        self::create(
            AdminNotification::TYPE_MONTHLY_REPORT,
            AdminNotification::SEVERITY_INFO,
            'Monthly Report Generated',
            "Monthly report for {$period['month']} {$period['year']} is ready. {$reportData['documents']['total_uploaded']} documents processed.",
            null,
            ['period' => $period]
        );
    }

    /**
     * Get unread count
     */
    public static function getUnreadCount(): int
    {
        return AdminNotification::unread()->count();
    }

    /**
     * Get critical notifications count
     */
    public static function getCriticalCount(): int
    {
        return AdminNotification::unread()
            ->severity(AdminNotification::SEVERITY_CRITICAL)
            ->count();
    }

    /**
     * Mark all as read
     */
    public static function markAllAsRead(): void
    {
        AdminNotification::unread()->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Delete old notifications (older than 90 days)
     */
    public static function cleanupOld(): int
    {
        return AdminNotification::where('created_at', '<', now()->subDays(90))->delete();
    }
}