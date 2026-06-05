<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->resolveFilters($request);

        $analytics = AnalyticsService::getAdminActivityAnalytics($filters);
        $hourlyPattern = AnalyticsService::getHourlyActivityPattern($filters);
        $comparative = AnalyticsService::getComparativeAnalytics($filters);

        return view('admin.analytics.index', compact('analytics', 'hourlyPattern', 'comparative', 'filters'));
    }

    public function export(Request $request)
    {
        $filters = $this->resolveFilters($request);
        $analytics = AnalyticsService::getAdminActivityAnalytics($filters);

        $csv = $this->generateCsv($analytics);

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, 'admin_analytics_' . now()->format('Y-m-d') . '.csv');
    }

    protected function generateCsv($analytics): string
    {
        $csv = "Admin Activity Analytics Report\n";
        $csv .= "Generated: " . now()->toDateTimeString() . "\n\n";
        
        $csv .= "Period: {$analytics['period']['start']} to {$analytics['period']['end']}\n\n";
        
        $csv .= "Summary\n";
        $csv .= "Total Actions,{$analytics['admin_actions']['total_actions']}\n";
        $csv .= "Critical Actions,{$analytics['admin_actions']['critical_actions']}\n";
        $csv .= "Warning Actions,{$analytics['admin_actions']['warning_actions']}\n";
        $csv .= "Daily Average,{$analytics['admin_actions']['daily_average']}\n\n";
        
        $csv .= "Top Admins\n";
        $csv .= "Name,Total Actions,Critical,Warnings\n";
        foreach ($analytics['top_admins'] as $admin) {
            $csv .= "{$admin['name']},{$admin['total_actions']},{$admin['critical_actions']},{$admin['warning_actions']}\n";
        }
        
        return $csv;
    }

    protected function resolveFilters(Request $request): array
    {
        $range = (string) $request->input('range', '');
        $days = (int) $request->input('days', 30);
        $search = trim((string) $request->input('search', ''));

        if ($range === '') {
            $range = match ($days) {
                1 => 'today',
                7 => 'last_7_days',
                90 => 'last_90_days',
                default => 'last_30_days',
            };
        }

        $now = now();
        $startDate = null;
        $endDate = null;
        $rangeLabel = 'Last 30 Days';

        switch ($range) {
            case 'today':
                $startDate = $now->copy()->startOfDay();
                $endDate = $now->copy()->endOfDay();
                $rangeLabel = 'Today';
                break;

            case 'this_week':
                $startDate = $now->copy()->startOfWeek();
                $endDate = $now->copy()->endOfDay();
                $rangeLabel = 'This Week';
                break;

            case 'custom':
                $startInput = (string) $request->input('start_date', '');
                $endInput = (string) $request->input('end_date', '');

                if ($startInput !== '' && $endInput !== '') {
                    try {
                        $startDate = Carbon::parse($startInput)->startOfDay();
                        $endDate = Carbon::parse($endInput)->endOfDay();
                        if ($startDate->gt($endDate)) {
                            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
                        }
                        $rangeLabel = 'Custom Range';
                        break;
                    } catch (\Throwable $e) {
                        // Fall through to default when invalid date input is supplied.
                    }
                }

                $range = 'last_30_days';
                // no break

            case 'last_7_days':
                if ($range === 'last_7_days') {
                    $startDate = $now->copy()->subDays(6)->startOfDay();
                    $endDate = $now->copy()->endOfDay();
                    $rangeLabel = 'Last 7 Days';
                    break;
                }
                // no break

            case 'last_90_days':
                if ($range === 'last_90_days') {
                    $startDate = $now->copy()->subDays(89)->startOfDay();
                    $endDate = $now->copy()->endOfDay();
                    $rangeLabel = 'Last 90 Days';
                    break;
                }
                // no break

            default:
                $range = 'last_30_days';
                $startDate = $now->copy()->subDays(29)->startOfDay();
                $endDate = $now->copy()->endOfDay();
                $rangeLabel = 'Last 30 Days';
                break;
        }

        $resolvedDays = max(1, $startDate->diffInDays($endDate) + 1);

        return [
            'range' => $range,
            'range_label' => $rangeLabel,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'start_date_input' => $startDate->toDateString(),
            'end_date_input' => $endDate->toDateString(),
            'days' => $resolvedDays,
            'search' => $search,
        ];
    }
}