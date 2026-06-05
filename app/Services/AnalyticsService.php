<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\CorrectionRequest;
use App\Models\Scan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * Get admin activity analytics
     */
    public static function getAdminActivityAnalytics($filters = 30): array
    {
        $context = self::resolveContext($filters);
        $startDate = $context['start_date'];
        $endDate = $context['end_date'];
        $search = $context['search'];

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
                'days' => $context['days'],
                'range' => $context['range'],
                'label' => $context['range_label'],
                'search' => $search,
            ],
            'admin_actions' => self::getAdminActionsSummary($startDate, $endDate, $search),
            'action_trends' => self::getActionTrends($startDate, $endDate, $search),
            'top_admins' => self::getTopAdmins($startDate, $endDate, $search),
            'action_breakdown' => self::getActionBreakdown($startDate, $endDate, $search),
            'critical_actions' => self::getCriticalActions($startDate, $endDate, $search),
            'user_management_stats' => self::getUserManagementStats($startDate, $endDate, $search),
            'document_intervention_stats' => self::getDocumentInterventionStats($startDate, $endDate, $search),
            'correction_override_stats' => self::getCorrectionOverrideStats($startDate, $endDate, $search),
        ];
    }

    /**
     * Get admin actions summary
     */
    protected static function getAdminActionsSummary(Carbon $startDate, Carbon $endDate, string $search = ''): array
    {
        $baseQuery = self::auditLogsQuery($startDate, $endDate, $search);

        $totalActions = (clone $baseQuery)->count();
        $criticalActions = (clone $baseQuery)->where('severity', 'critical')->count();
        $warningActions = (clone $baseQuery)->where('severity', 'warning')->count();
        $periodDays = max(1, $startDate->diffInDays($endDate) + 1);

        return [
            'total_actions' => $totalActions,
            'critical_actions' => $criticalActions,
            'warning_actions' => $warningActions,
            'info_actions' => $totalActions - $criticalActions - $warningActions,
            'daily_average' => round($totalActions / $periodDays, 2),
        ];
    }

    /**
     * Get action trends (daily breakdown)
     */
    protected static function getActionTrends(Carbon $startDate, Carbon $endDate, string $search = ''): array
    {
        $trendMap = self::auditLogsQuery($startDate, $endDate, $search)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $trends = [];
        $cursor = $startDate->copy()->startOfDay();
        $periodEnd = $endDate->copy()->startOfDay();

        while ($cursor->lte($periodEnd)) {
            $dateKey = $cursor->toDateString();
            $trends[] = [
                'date' => $cursor->format('M d'),
                'count' => (int) ($trendMap[$dateKey] ?? 0),
            ];
            $cursor->addDay();
        }

        return $trends;
    }

    /**
     * Get top admins by activity
     */
    protected static function getTopAdmins(Carbon $startDate, Carbon $endDate, string $search = ''): array
    {
        $topAdminsQuery = AuditLog::query()
            ->join('users', 'audit_logs.user_id', '=', 'users.id')
            ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('roles.name', 'admin')
            ->whereBetween('audit_logs.created_at', [$startDate, $endDate]);

        if ($search !== '') {
            $like = '%' . self::escapeLike($search) . '%';
            $topAdminsQuery->where(function ($query) use ($like) {
                $query->where('audit_logs.action_type', 'like', $like)
                    ->orWhere('audit_logs.notes', 'like', $like)
                    ->orWhere('audit_logs.user_name', 'like', $like)
                    ->orWhere('users.name', 'like', $like)
                    ->orWhere('users.email', 'like', $like);
            });
        }

        $topAdmins = $topAdminsQuery
            ->select(
                'users.id',
                'users.name',
                DB::raw('COUNT(*) as action_count'),
                DB::raw('COUNT(CASE WHEN audit_logs.severity = "critical" THEN 1 END) as critical_count'),
                DB::raw('COUNT(CASE WHEN audit_logs.severity = "warning" THEN 1 END) as warning_count')
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('action_count')
            ->limit(10)
            ->get();

        return $topAdmins->map(function ($admin) {
            return [
                'id' => $admin->id,
                'name' => $admin->name,
                'total_actions' => $admin->action_count,
                'critical_actions' => $admin->critical_count,
                'warning_actions' => $admin->warning_count,
            ];
        })->toArray();
    }

    /**
     * Get action breakdown by type
     */
    protected static function getActionBreakdown(Carbon $startDate, Carbon $endDate, string $search = ''): array
    {
        $breakdown = self::auditLogsQuery($startDate, $endDate, $search)
            ->select('action_type', DB::raw('COUNT(*) as count'))
            ->groupBy('action_type')
            ->orderByDesc('count')
            ->limit(15)
            ->get()
            ->map(function ($item) {
                return [
                    'action' => self::formatActionType($item->action_type),
                    'count' => $item->count,
                ];
            });

        return $breakdown->toArray();
    }

    /**
     * Get critical actions requiring attention
     */
    protected static function getCriticalActions(Carbon $startDate, Carbon $endDate, string $search = ''): array
    {
        $critical = self::auditLogsQuery($startDate, $endDate, $search)
            ->where('severity', 'critical')
            ->with('user')
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($log) {
                return [
                    'admin' => $log->user?->name ?? 'Unknown',
                    'action' => self::formatActionType($log->action_type),
                    'description' => $log->notes,
                    'timestamp' => $log->created_at->format('M d, Y H:i'),
                ];
            });

        return $critical->toArray();
    }

    /**
     * Get user management statistics
     */
    protected static function getUserManagementStats(Carbon $startDate, Carbon $endDate, string $search = ''): array
    {
        $baseQuery = self::auditLogsQuery($startDate, $endDate, $search);

        return [
            'users_created' => (clone $baseQuery)->where('action_type', 'user.created')->count(),
            'users_deactivated' => (clone $baseQuery)->where('action_type', 'user.deactivated')->count(),
            'users_reactivated' => (clone $baseQuery)->where('action_type', 'user.reactivated')->count(),
            'roles_assigned' => (clone $baseQuery)->where('action_type', 'user.role_assigned')->count(),
        ];
    }

    /**
     * Get document intervention statistics
     */
    protected static function getDocumentInterventionStats(Carbon $startDate, Carbon $endDate, string $search = ''): array
    {
        if ($search !== '') {
            $baseQuery = self::auditLogsQuery($startDate, $endDate, $search);

            return [
                'documents_locked' => (clone $baseQuery)->where('action_type', 'document.locked')->count(),
                'documents_unlocked' => (clone $baseQuery)->where('action_type', 'document.unlocked')->count(),
                'documents_archived' => (clone $baseQuery)->where('action_type', 'document.archived')->count(),
                'documents_flagged' => (clone $baseQuery)->where('action_type', 'document.flagged')->count(),
            ];
        }

        return [
            'documents_locked' => Scan::whereBetween('locked_at', [$startDate, $endDate])->count(),
            'documents_unlocked' => AuditLog::where('action_type', 'document.unlocked')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
            'documents_archived' => Scan::whereBetween('archived_at', [$startDate, $endDate])->count(),
            'documents_flagged' => Scan::whereBetween('flagged_at', [$startDate, $endDate])->count(),
        ];
    }

    /**
     * Get correction override statistics
     */
    protected static function getCorrectionOverrideStats(Carbon $startDate, Carbon $endDate, string $search = ''): array
    {
        $overrideQuery = CorrectionRequest::query()
            ->whereNotNull('override_by')
            ->whereBetween('override_at', [$startDate, $endDate]);

        $requestQuery = CorrectionRequest::query()
            ->whereBetween('requested_at', [$startDate, $endDate]);

        if ($search !== '') {
            $like = '%' . self::escapeLike($search) . '%';

            $overrideQuery->where(function ($query) use ($like) {
                $query->where('override_type', 'like', $like)
                    ->orWhere('override_justification', 'like', $like)
                    ->orWhere('field_name', 'like', $like)
                    ->orWhereHas('overrider', function ($userQuery) use ($like) {
                        $userQuery->where('name', 'like', $like)
                            ->orWhere('email', 'like', $like);
                    })
                    ->orWhereHas('requester', function ($userQuery) use ($like) {
                        $userQuery->where('name', 'like', $like)
                            ->orWhere('email', 'like', $like);
                    });
            });

            $requestQuery->where(function ($query) use ($like) {
                $query->where('field_name', 'like', $like)
                    ->orWhere('reason', 'like', $like)
                    ->orWhere('current_value', 'like', $like)
                    ->orWhere('proposed_value', 'like', $like)
                    ->orWhereHas('requester', function ($userQuery) use ($like) {
                        $userQuery->where('name', 'like', $like)
                            ->orWhere('email', 'like', $like);
                    });
            });
        }

        $overrides = $overrideQuery->get();
        $requestedCount = $requestQuery->count();

        return [
            'total_overrides' => $overrides->count(),
            'force_approved' => $overrides->where('override_type', 'force_approve')->count(),
            'force_rejected' => $overrides->where('override_type', 'force_reject')->count(),
            'override_rate' => $requestedCount > 0
                ? round(($overrides->count() / $requestedCount) * 100, 2)
                : 0,
        ];
    }

    /**
     * Format action type for display
     */
    protected static function formatActionType(string $actionType): string
    {
        return ucwords(str_replace(['.', '_'], ' ', $actionType));
    }

    /**
     * Get hourly activity pattern
     */
    public static function getHourlyActivityPattern($filters = 30): array
    {
        $context = self::resolveContext($filters);
        $startDate = $context['start_date'];
        $endDate = $context['end_date'];
        $search = $context['search'];

        $hourlyActivity = self::auditLogsQuery($startDate, $endDate, $search)
            ->select(
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->pluck('count', 'hour')
            ->toArray();

        // Fill missing hours with 0
        $result = [];
        for ($i = 0; $i < 24; $i++) {
            $result[] = [
                'hour' => sprintf('%02d:00', $i),
                'count' => $hourlyActivity[$i] ?? 0,
            ];
        }

        return $result;
    }

    /**
     * Get comparative analytics (current vs previous period)
     */
    public static function getComparativeAnalytics($filters = 30): array
    {
        $context = self::resolveContext($filters);
        $currentStart = $context['start_date'];
        $currentEnd = $context['end_date'];
        $search = $context['search'];
        $periodDays = $context['days'];

        $previousEnd = $currentStart->copy()->subDay()->endOfDay();
        $previousStart = $previousEnd->copy()->subDays($periodDays - 1)->startOfDay();

        $currentCount = self::auditLogsQuery($currentStart, $currentEnd, $search)->count();
        $previousCount = self::auditLogsQuery($previousStart, $previousEnd, $search)->count();

        $percentageChange = $previousCount > 0
            ? round((($currentCount - $previousCount) / $previousCount) * 100, 2)
            : 0;

        return [
            'current_period' => $currentCount,
            'previous_period' => $previousCount,
            'change' => $currentCount - $previousCount,
            'percentage_change' => $percentageChange,
            'trend' => $percentageChange > 0 ? 'up' : ($percentageChange < 0 ? 'down' : 'stable'),
            'has_comparable_data' => $currentCount > 0 && $previousCount > 0,
        ];
    }

    protected static function resolveContext($filters): array
    {
        if (!is_array($filters)) {
            $filters = ['days' => (int) $filters];
        }

        $now = now();
        $days = max(1, (int) ($filters['days'] ?? 30));
        $range = (string) ($filters['range'] ?? 'last_30_days');
        $rangeLabel = (string) ($filters['range_label'] ?? '');
        $search = trim((string) ($filters['search'] ?? ''));

        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;

        if (!$startDate instanceof Carbon) {
            if (is_string($startDate) && $startDate !== '') {
                try {
                    $startDate = Carbon::parse($startDate)->startOfDay();
                } catch (\Throwable $e) {
                    $startDate = null;
                }
            }
        }

        if (!$endDate instanceof Carbon) {
            if (is_string($endDate) && $endDate !== '') {
                try {
                    $endDate = Carbon::parse($endDate)->endOfDay();
                } catch (\Throwable $e) {
                    $endDate = null;
                }
            }
        }

        if (!$startDate instanceof Carbon || !$endDate instanceof Carbon) {
            $startDate = $now->copy()->subDays($days - 1)->startOfDay();
            $endDate = $now->copy()->endOfDay();
        }

        if ($startDate->gt($endDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }

        $resolvedDays = max(1, $startDate->diffInDays($endDate) + 1);
        if ($rangeLabel === '') {
            $rangeLabel = ucfirst(str_replace('_', ' ', $range));
        }

        return [
            'range' => $range,
            'range_label' => $rangeLabel,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days' => $resolvedDays,
            'search' => $search,
        ];
    }

    protected static function auditLogsQuery(Carbon $startDate, Carbon $endDate, string $search = ''): Builder
    {
        $query = AuditLog::query()
            ->whereIn('user_id', self::getAdminUserIds())
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($search !== '') {
            $like = '%' . self::escapeLike($search) . '%';
            $query->where(function (Builder $searchQuery) use ($like) {
                $searchQuery->where('action_type', 'like', $like)
                    ->orWhere('notes', 'like', $like)
                    ->orWhere('user_name', 'like', $like)
                    ->orWhereHas('user', function ($userQuery) use ($like) {
                        $userQuery->where('name', 'like', $like)
                            ->orWhere('email', 'like', $like);
                    });
            });
        }

        return $query;
    }

    protected static function getAdminUserIds()
    {
        static $adminUserIds = null;

        if ($adminUserIds === null) {
            $adminUserIds = User::role('admin')->pluck('id');
        }

        return $adminUserIds;
    }

    protected static function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}