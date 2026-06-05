<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationsController extends Controller
{
    public function index(Request $request)
    {
        $query = AdminNotification::query()
            ->orderBy('is_read', 'asc')
            ->orderByRaw(
                'CASE severity WHEN ? THEN 1 WHEN ? THEN 2 ELSE 3 END',
                [AdminNotification::SEVERITY_CRITICAL, AdminNotification::SEVERITY_WARNING]
            )
            ->latest('created_at');

        // Filter by read/unread
        if ($request->filled('status')) {
            if ($request->status === 'unread') {
                $query->unread();
            } elseif ($request->status === 'read') {
                $query->where('is_read', true);
            }
        }

        // Filter by severity
        if ($request->filled('severity')) {
            $query->severity($request->severity);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->type($request->type);
        }

        $notifications = $query->paginate(20);

        $stats = [
            'unread' => AdminNotification::unread()->count(),
            'critical' => AdminNotification::unread()->severity(AdminNotification::SEVERITY_CRITICAL)->count(),
            'warning' => AdminNotification::unread()->severity(AdminNotification::SEVERITY_WARNING)->count(),
            'info' => AdminNotification::unread()->severity(AdminNotification::SEVERITY_INFO)->count(),
            'read' => AdminNotification::where('is_read', true)->count(),
            'total' => AdminNotification::count(),
        ];

        return view('admin.notifications.index', compact('notifications', 'stats'));
    }

    public function show(AdminNotification $notification)
    {
        // Mark as read when viewing
        if (!$notification->is_read) {
            $notification->markAsRead();
        }

        return view('admin.notifications.show', compact('notification'));
    }

    public function markAsRead(AdminNotification $notification)
    {
        $notification->markAsRead();
        return back()->with('success', 'Notification marked as read.');
    }

    public function markAllAsRead()
    {
        NotificationService::markAllAsRead();
        return back()->with('success', 'All notifications marked as read.');
    }

    public function destroy(AdminNotification $notification)
    {
        $notification->delete();
        return back()->with('success', 'Notification deleted.');
    }

    public function getUnreadCount()
    {
        return response()->json([
            'count' => NotificationService::getUnreadCount(),
            'critical' => NotificationService::getCriticalCount(),
        ]);
    }
}