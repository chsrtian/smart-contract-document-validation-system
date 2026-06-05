@extends('layouts.admin')

@section('content')
    @php
        $range = (string) ($filters['range'] ?? ($analytics['period']['range'] ?? 'last_30_days'));
        $rangeLabel = (string) ($filters['range_label'] ?? ($analytics['period']['label'] ?? 'Last 30 Days'));
        $searchTerm = trim((string) ($filters['search'] ?? ($analytics['period']['search'] ?? '')));
        $startDateInput = (string) ($filters['start_date_input'] ?? ($analytics['period']['start'] ?? now()->toDateString()));
        $endDateInput = (string) ($filters['end_date_input'] ?? ($analytics['period']['end'] ?? now()->toDateString()));

        $totalActions = (int) ($analytics['admin_actions']['total_actions'] ?? 0);
        $criticalActions = (int) ($analytics['admin_actions']['critical_actions'] ?? 0);
        $warningActions = (int) ($analytics['admin_actions']['warning_actions'] ?? 0);
        $infoActions = (int) ($analytics['admin_actions']['info_actions'] ?? 0);
        $dailyAverage = (float) ($analytics['admin_actions']['daily_average'] ?? 0);

        $criticalPct = $totalActions > 0 ? round(($criticalActions / $totalActions) * 100, 1) : 0;
        $warningPct = $totalActions > 0 ? round(($warningActions / $totalActions) * 100, 1) : 0;
        $infoPct = $totalActions > 0 ? round(($infoActions / $totalActions) * 100, 1) : 0;

        $currentPeriod = (int) ($comparative['current_period'] ?? 0);
        $previousPeriod = (int) ($comparative['previous_period'] ?? 0);
        $rawPercentageChange = (float) ($comparative['percentage_change'] ?? 0);
        $showPercentageChange = $currentPeriod > 0 && $previousPeriod > 0;

        $comparisonTone = 'neutral';
        $comparisonValue = 'No comparable activity yet';
        $comparisonDescription = 'Comparison requires activity in both periods.';

        if ($showPercentageChange) {
            $comparisonTone = $rawPercentageChange > 0 ? 'positive' : ($rawPercentageChange < 0 ? 'negative' : 'neutral');
            $comparisonValue = ($rawPercentageChange > 0 ? '+' : '') . number_format($rawPercentageChange, 1) . '%';
            $comparisonDescription = $rawPercentageChange > 0
                ? 'Increase from previous period'
                : ($rawPercentageChange < 0 ? 'Decrease from previous period' : 'No change from previous period');
        } elseif ($currentPeriod === 0 && $previousPeriod > 0) {
            $comparisonValue = 'No activity in current period';
            $comparisonDescription = 'Use a wider range to compare trends.';
        } elseif ($currentPeriod > 0 && $previousPeriod === 0) {
            $comparisonValue = 'Baseline unavailable';
            $comparisonDescription = 'Previous period has no activity to compare.';
        }

        $trendPoints = collect($analytics['action_trends'] ?? []);
        $hourlyPoints = collect($hourlyPattern ?? []);

        $dailyLabels = $trendPoints->pluck('date')->values();
        $dailyCounts = $trendPoints->pluck('count')->map(fn ($count) => (int) $count)->values();
        $hourlyLabels = $hourlyPoints->pluck('hour')->values();
        $hourlyCounts = $hourlyPoints->pluck('count')->map(fn ($count) => (int) $count)->values();

        $hasDailyData = $dailyCounts->sum() > 0;
        $hasHourlyData = $hourlyCounts->sum() > 0;
        $hasActivity = $totalActions > 0;

        $topAdmins = $analytics['top_admins'] ?? [];
        $actionBreakdown = $analytics['action_breakdown'] ?? [];
        $criticalActionLogs = $analytics['critical_actions'] ?? [];

        $userManagementStats = $analytics['user_management_stats'] ?? [];
        $documentInterventionStats = $analytics['document_intervention_stats'] ?? [];
        $correctionOverrideStats = $analytics['correction_override_stats'] ?? [];

        $showTopAdmins = !empty($topAdmins);
        $showActionBreakdown = !empty($actionBreakdown);
        $showCriticalActions = !empty($criticalActionLogs);

        $showUserStats = array_sum($userManagementStats) > 0;
        $showDocumentStats = array_sum($documentInterventionStats) > 0;
        $showOverrideStats = (int) ($correctionOverrideStats['total_overrides'] ?? 0) > 0;
        $showOperationalStats = $showUserStats || $showDocumentStats || $showOverrideStats;

        $queryParams = [
            'range' => $range,
            'start_date' => $startDateInput,
            'end_date' => $endDateInput,
            'search' => $searchTerm,
        ];
    @endphp

    <div class="admin-container ada-dashboard">
        <header class="admin-page-header ada-header">
            <div>
                <h1 class="admin-page-title">Admin Activity Analytics</h1>
                <p class="admin-page-subtitle">
                    {{ $hasActivity ? 'Track administrative actions, trends, and high-severity operations from one view.' : 'No admin activity recorded for this filtered period.' }}
                </p>
            </div>

            <div class="ada-header-actions">
                <a href="{{ route('admin.analytics.export', $queryParams) }}" class="admin-btn admin-btn-success">
                    <i class="fas fa-file-export" aria-hidden="true"></i>
                    Export CSV
                </a>
                <a href="{{ route('admin.analytics.index', $queryParams) }}" class="admin-btn admin-btn-secondary">
                    <i class="fas fa-rotate-right" aria-hidden="true"></i>
                    Refresh Data
                </a>
            </div>
        </header>

        <section class="admin-card ada-filter-card" id="analytics-global-filters" aria-label="Global analytics controls">
            <div class="admin-card-body">
                <form method="GET" class="ada-filter-grid" aria-label="Global analytics filter form">
                    <div class="ada-filter-field">
                        <label class="admin-form-label" for="analytics-range">Date Range</label>
                        <select name="range" id="analytics-range" class="admin-form-select" aria-label="Select analytics date range">
                            <option value="today" {{ $range === 'today' ? 'selected' : '' }}>Today</option>
                            <option value="this_week" {{ $range === 'this_week' ? 'selected' : '' }}>This Week</option>
                            <option value="last_30_days" {{ $range === 'last_30_days' ? 'selected' : '' }}>Last 30 Days</option>
                            <option value="custom" {{ $range === 'custom' ? 'selected' : '' }}>Custom</option>
                        </select>
                    </div>

                    <div class="ada-filter-field ada-filter-field--date {{ $range === 'custom' ? '' : 'is-hidden' }}" data-custom-field>
                        <label class="admin-form-label" for="analytics-start-date">From</label>
                        <input
                            id="analytics-start-date"
                            type="date"
                            name="start_date"
                            value="{{ $startDateInput }}"
                            class="admin-form-input"
                            aria-label="Custom start date"
                        >
                    </div>

                    <div class="ada-filter-field ada-filter-field--date {{ $range === 'custom' ? '' : 'is-hidden' }}" data-custom-field>
                        <label class="admin-form-label" for="analytics-end-date">To</label>
                        <input
                            id="analytics-end-date"
                            type="date"
                            name="end_date"
                            value="{{ $endDateInput }}"
                            class="admin-form-input"
                            aria-label="Custom end date"
                        >
                    </div>

                    <div class="ada-filter-field ada-filter-field--search">
                        <label class="admin-form-label" for="analytics-search">Search Activity</label>
                        <div class="ada-search-control">
                            <span class="ada-search-icon" aria-hidden="true">
                                <i class="fas fa-magnifying-glass"></i>
                            </span>
                            <input
                                id="analytics-search"
                                type="search"
                                name="search"
                                value="{{ $searchTerm }}"
                                class="admin-form-input ada-search-input"
                                placeholder="Search by admin, action type, or notes"
                                aria-label="Search analytics data"
                            >
                        </div>
                    </div>

                    <div class="ada-filter-field ada-filter-field--actions">
                        <button type="submit" class="admin-btn admin-btn-primary">
                            <i class="fas fa-filter" aria-hidden="true"></i>
                            Apply Filters
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <section class="ada-section" aria-label="Activity summary">
            <div class="admin-tier-header">
                <span class="admin-tier-label">Activity Summary</span>
                <span class="admin-tier-rule"></span>
            </div>

            <div class="ada-grid" role="list" aria-label="Analytics summary metrics">
                <article class="admin-card ada-metric-card ada-col-3" role="listitem" aria-label="Total actions metric">
                    <div class="admin-card-body">
                        <div class="ada-metric-head">
                            <span class="ada-metric-label">Total Actions</span>
                            <span class="admin-badge admin-badge-info">Overview</span>
                        </div>
                        <p class="ada-metric-value {{ $hasActivity ? '' : 'is-empty' }}">
                            {{ $hasActivity ? number_format($totalActions) : 'No activity recorded' }}
                        </p>
                        <p class="ada-metric-meta">
                            {{ $hasActivity ? 'Average ' . number_format($dailyAverage, 2) . ' actions per day' : 'Adjust date range or search to inspect another period.' }}
                        </p>
                    </div>
                </article>

                <article class="admin-card ada-metric-card ada-col-3" role="listitem" aria-label="Critical actions metric">
                    <div class="admin-card-body">
                        <div class="ada-metric-head">
                            <span class="ada-metric-label">Critical Actions</span>
                            <span class="admin-badge admin-badge-danger">Critical</span>
                        </div>
                        <p class="ada-metric-value ada-metric-value--danger {{ $hasActivity ? '' : 'is-empty' }}">
                            {{ $hasActivity ? number_format($criticalActions) : 'No activity recorded' }}
                        </p>
                        <p class="ada-metric-meta">
                            {{ $hasActivity ? number_format($criticalPct, 1) . '% of total actions' : 'Critical activity appears when high-risk operations occur.' }}
                        </p>
                    </div>
                </article>

                <article class="admin-card ada-metric-card ada-col-3" role="listitem" aria-label="Warning actions metric">
                    <div class="admin-card-body">
                        <div class="ada-metric-head">
                            <span class="ada-metric-label">Warning Actions</span>
                            <span class="admin-badge admin-badge-warning">Warning</span>
                        </div>
                        <p class="ada-metric-value ada-metric-value--warning {{ $hasActivity ? '' : 'is-empty' }}">
                            {{ $hasActivity ? number_format($warningActions) : 'No activity recorded' }}
                        </p>
                        <p class="ada-metric-meta">
                            {{ $hasActivity ? number_format($warningPct, 1) . '% of total actions' : 'Warning activity appears when moderate-risk operations occur.' }}
                        </p>
                    </div>
                </article>

                <article class="admin-card ada-metric-card ada-col-3" role="listitem" aria-label="Info actions metric">
                    <div class="admin-card-body">
                        <div class="ada-metric-head">
                            <span class="ada-metric-label">Informational Actions</span>
                            <span class="admin-badge admin-badge-success">Informational</span>
                        </div>
                        <p class="ada-metric-value ada-metric-value--info {{ $hasActivity ? '' : 'is-empty' }}">
                            {{ $hasActivity ? number_format($infoActions) : 'No activity recorded' }}
                        </p>
                        <p class="ada-metric-meta">
                            {{ $hasActivity ? number_format($infoPct, 1) . '% of total actions' : 'Informational actions track standard admin operations.' }}
                        </p>
                    </div>
                </article>
            </div>
        </section>

        <section class="ada-section" aria-label="Period comparison">
            <div class="admin-tier-header">
                <span class="admin-tier-label">Period Comparison</span>
                <span class="admin-tier-rule"></span>
            </div>

            <article class="admin-card">
                <div class="admin-card-body">
                    <p class="ada-comparison-caption">Comparing {{ $rangeLabel }} against the previous equivalent period.</p>

                    <div class="ada-grid">
                        <div class="ada-compare-item ada-col-4" aria-label="Current period activity count">
                            <span class="ada-compare-label">Current Period</span>
                            <span class="ada-compare-value">{{ number_format($currentPeriod) }}</span>
                        </div>

                        <div class="ada-compare-item ada-col-4" aria-label="Previous period activity count">
                            <span class="ada-compare-label">Previous Period</span>
                            <span class="ada-compare-value">{{ number_format($previousPeriod) }}</span>
                        </div>

                        <div class="ada-compare-item ada-col-4" aria-label="Period over period change">
                            <span class="ada-compare-label">Change</span>
                            <span class="ada-compare-value ada-compare-value--{{ $comparisonTone }}">{{ $comparisonValue }}</span>
                            <span class="ada-compare-note">{{ $comparisonDescription }}</span>
                        </div>
                    </div>
                </div>
            </article>
        </section>

        @if(!$hasActivity)
            <section class="ada-section" aria-label="No analytics data">
                @include('admin.analytics.partials.empty-state', [
                    'title' => 'No admin activity in this period',
                    'message' => 'Try adjusting the date range or check back later.',
                    'primaryUrl' => '#analytics-global-filters',
                    'primaryLabel' => 'Select Date Range',
                    'secondaryUrl' => route('admin.analytics.export', $queryParams),
                    'secondaryLabel' => 'Export Sample Data',
                ])
            </section>
        @else
            <section class="ada-section" aria-label="Activity trends">
                <div class="admin-tier-header">
                    <span class="admin-tier-label">Activity Trends</span>
                    <span class="admin-tier-rule"></span>
                </div>

                <div class="ada-grid">
                    <article class="admin-card ada-col-6">
                        <div class="admin-card-body">
                            <div class="ada-section-heading-wrap">
                                <h3 class="ada-section-heading">Daily Activity Trend</h3>
                                <p class="ada-section-subtext">Daily action counts for the selected range.</p>
                            </div>
                            <div class="ada-chart-shell">
                                <canvas id="analytics-daily-chart" role="img" tabindex="0" aria-label="Daily admin activity chart"></canvas>
                            </div>
                            <p id="daily-chart-empty-label" class="ada-chart-empty-label {{ $hasDailyData ? 'is-hidden' : '' }}">No daily activity data</p>
                        </div>
                    </article>

                    <article class="admin-card ada-col-6">
                        <div class="admin-card-body">
                            <div class="ada-section-heading-wrap">
                                <h3 class="ada-section-heading">Hourly Activity Pattern</h3>
                                <p class="ada-section-subtext">Hourly distribution of actions in local time.</p>
                            </div>
                            <div class="ada-chart-shell">
                                <canvas id="analytics-hourly-chart" role="img" tabindex="0" aria-label="Hourly admin activity chart"></canvas>
                            </div>
                            <p id="hourly-chart-empty-label" class="ada-chart-empty-label {{ $hasHourlyData ? 'is-hidden' : '' }}">No hourly activity data</p>
                        </div>
                    </article>
                </div>
            </section>

            @if($showTopAdmins || $showActionBreakdown || $showOperationalStats)
                <section class="ada-section" aria-label="Breakdown and details">
                    <div class="admin-tier-header">
                        <span class="admin-tier-label">Breakdown and Details</span>
                        <span class="admin-tier-rule"></span>
                    </div>

                    <div class="ada-grid">
                        @if($showTopAdmins)
                            <article class="admin-card ada-col-6">
                                <div class="admin-card-body">
                                    <h3 class="ada-section-heading">Top Active Admins</h3>
                                    <div class="admin-table-responsive">
                                        <table class="admin-table ada-table" aria-label="Top active admins table">
                                            <thead>
                                                <tr>
                                                    <th>Rank</th>
                                                    <th>Admin</th>
                                                    <th>Total Actions</th>
                                                    <th>Critical</th>
                                                    <th>Warnings</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($topAdmins as $index => $admin)
                                                    <tr>
                                                        <td class="admin-font-semibold">#{{ $index + 1 }}</td>
                                                        <td>{{ $admin['name'] }}</td>
                                                        <td>{{ number_format($admin['total_actions']) }}</td>
                                                        <td class="ada-text-danger">{{ number_format($admin['critical_actions']) }}</td>
                                                        <td class="ada-text-warning">{{ number_format($admin['warning_actions']) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </article>
                        @endif

                        @if($showActionBreakdown || $showOperationalStats)
                            <article class="admin-card ada-col-6">
                                <div class="admin-card-body">
                                    @if($showActionBreakdown)
                                        <h3 class="ada-section-heading">Action Breakdown</h3>
                                        <div class="ada-breakdown-list">
                                            @foreach($actionBreakdown as $action)
                                                @php
                                                    $normalizedAction = strtolower($action['action']);
                                                    $severity = 'info';
                                                    $severityLabel = 'Informational';

                                                    if (str_contains($normalizedAction, 'delete') || str_contains($normalizedAction, 'reject') || str_contains($normalizedAction, 'force reject')) {
                                                        $severity = 'critical';
                                                        $severityLabel = 'Critical';
                                                    } elseif (str_contains($normalizedAction, 'override') || str_contains($normalizedAction, 'flag') || str_contains($normalizedAction, 'archive') || str_contains($normalizedAction, 'lock')) {
                                                        $severity = 'warning';
                                                        $severityLabel = 'Warning';
                                                    }

                                                    $breakdownPercentage = round(($action['count'] / max($totalActions, 1)) * 100, 1);
                                                @endphp

                                                <div class="ada-breakdown-item">
                                                    <div class="ada-breakdown-top-row">
                                                        <span class="ada-breakdown-title">{{ $action['action'] }}</span>
                                                        <span class="ada-breakdown-count">{{ number_format($action['count']) }}</span>
                                                    </div>
                                                    <div class="ada-breakdown-meta-row">
                                                        <span class="admin-badge {{ $severity === 'critical' ? 'admin-badge-danger' : ($severity === 'warning' ? 'admin-badge-warning' : 'admin-badge-info') }}">{{ $severityLabel }}</span>
                                                        <span>{{ number_format($breakdownPercentage, 1) }}% of total</span>
                                                    </div>
                                                    <div class="ada-breakdown-progress" aria-hidden="true">
                                                        <span class="ada-breakdown-progress-bar ada-breakdown-progress-bar--{{ $severity }}" style="width: {{ $breakdownPercentage }}%"></span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if($showOperationalStats)
                                        <div class="ada-operational-stats {{ $showActionBreakdown ? 'has-divider' : '' }}">
                                            <h3 class="ada-section-heading">Operational Statistics</h3>

                                            @if($showUserStats)
                                                <div class="ada-stat-cluster">
                                                    <h4 class="ada-stat-cluster-title">User Management</h4>
                                                    <div class="ada-stat-grid">
                                                        <div class="ada-stat-item"><span>Created</span><strong>{{ number_format($userManagementStats['users_created'] ?? 0) }}</strong></div>
                                                        <div class="ada-stat-item"><span>Deactivated</span><strong>{{ number_format($userManagementStats['users_deactivated'] ?? 0) }}</strong></div>
                                                        <div class="ada-stat-item"><span>Reactivated</span><strong>{{ number_format($userManagementStats['users_reactivated'] ?? 0) }}</strong></div>
                                                        <div class="ada-stat-item"><span>Roles Assigned</span><strong>{{ number_format($userManagementStats['roles_assigned'] ?? 0) }}</strong></div>
                                                    </div>
                                                </div>
                                            @endif

                                            @if($showDocumentStats)
                                                <div class="ada-stat-cluster">
                                                    <h4 class="ada-stat-cluster-title">Document Interventions</h4>
                                                    <div class="ada-stat-grid">
                                                        <div class="ada-stat-item"><span>Locked</span><strong>{{ number_format($documentInterventionStats['documents_locked'] ?? 0) }}</strong></div>
                                                        <div class="ada-stat-item"><span>Unlocked</span><strong>{{ number_format($documentInterventionStats['documents_unlocked'] ?? 0) }}</strong></div>
                                                        <div class="ada-stat-item"><span>Archived</span><strong>{{ number_format($documentInterventionStats['documents_archived'] ?? 0) }}</strong></div>
                                                        <div class="ada-stat-item"><span>Flagged</span><strong>{{ number_format($documentInterventionStats['documents_flagged'] ?? 0) }}</strong></div>
                                                    </div>
                                                </div>
                                            @endif

                                            @if($showOverrideStats)
                                                <div class="ada-stat-cluster">
                                                    <h4 class="ada-stat-cluster-title">
                                                        Correction Overrides
                                                        <button
                                                            type="button"
                                                            class="ada-tooltip-trigger"
                                                            title="Override rate is the percentage of correction requests that were overridden."
                                                            aria-label="Override rate definition"
                                                        >
                                                            <i class="fas fa-circle-info" aria-hidden="true"></i>
                                                        </button>
                                                    </h4>
                                                    <div class="ada-stat-grid">
                                                        <div class="ada-stat-item"><span>Total Overrides</span><strong>{{ number_format($correctionOverrideStats['total_overrides'] ?? 0) }}</strong></div>
                                                        <div class="ada-stat-item"><span>Override Rate</span><strong>{{ number_format((float) ($correctionOverrideStats['override_rate'] ?? 0), 2) }}%</strong></div>
                                                        <div class="ada-stat-item"><span>Force Approved</span><strong>{{ number_format($correctionOverrideStats['force_approved'] ?? 0) }}</strong></div>
                                                        <div class="ada-stat-item"><span>Force Rejected</span><strong>{{ number_format($correctionOverrideStats['force_rejected'] ?? 0) }}</strong></div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </article>
                        @endif
                    </div>
                </section>
            @endif

            @if($showCriticalActions)
                <section class="ada-section" aria-label="Recent critical actions">
                    <div class="admin-tier-header">
                        <span class="admin-tier-label">Recent Critical Actions</span>
                        <span class="admin-tier-rule"></span>
                    </div>

                    <article class="admin-card">
                        <div class="admin-card-body">
                            <div class="ada-critical-list">
                                @foreach($criticalActionLogs as $action)
                                    <div class="admin-callout admin-callout-danger">
                                        <div>
                                            <div class="admin-font-semibold">{{ $action['action'] }}</div>
                                            <div class="admin-text-sm ada-critical-description">{{ $action['description'] }}</div>
                                            <div class="admin-text-xs admin-text-muted ada-critical-meta">By {{ $action['admin'] }} at {{ $action['timestamp'] }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </article>
                </section>
            @endif
        @endif
    </div>
@endsection

@push('styles')
<style>
    .ada-dashboard {
        --ada-gap: 24px;
        --ada-card-padding: 20px;
        --ada-card-radius: 10px;
    }

    .ada-dashboard .admin-card {
        border-radius: var(--ada-card-radius);
    }

    .ada-dashboard .admin-card-body {
        padding: var(--ada-card-padding);
    }

    .ada-dashboard :is(a, button, input, select, textarea, [tabindex]):focus-visible {
        outline: 3px solid rgba(79, 70, 229, 0.45);
        outline-offset: 2px;
    }

    .ada-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 24px;
    }

    .ada-header-actions {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .ada-filter-card {
        margin-bottom: 24px;
    }

    .ada-filter-grid {
        display: grid;
        grid-template-columns: repeat(12, minmax(0, 1fr));
        gap: 16px;
        align-items: end;
    }

    .ada-filter-field {
        grid-column: span 12;
    }

    .ada-filter-field--search {
        grid-column: span 12;
    }

    .ada-filter-field--actions {
        display: flex;
        justify-content: flex-start;
    }

    .ada-filter-field.is-hidden {
        display: none;
    }

    .ada-search-control {
        display: flex;
        align-items: center;
        border: 1px solid var(--admin-border-normal);
        border-radius: var(--admin-radius-md);
        background: #ffffff;
        overflow: hidden;
    }

    .ada-search-icon {
        width: 40px;
        display: inline-flex;
        justify-content: center;
        color: #64748b;
    }

    .ada-search-input {
        border: none;
        border-left: 1px solid #e2e8f0;
        border-radius: 0;
    }

    .ada-search-input:focus {
        box-shadow: none;
    }

    .ada-section {
        margin-bottom: 24px;
    }

    .ada-grid {
        display: grid;
        grid-template-columns: repeat(12, minmax(0, 1fr));
        gap: var(--ada-gap);
    }

    .ada-col-3,
    .ada-col-4,
    .ada-col-6,
    .ada-col-12 {
        grid-column: span 12;
    }

    .ada-metric-card {
        min-height: 176px;
    }

    .ada-metric-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        margin-bottom: 12px;
    }

    .ada-metric-label {
        font-size: 14px;
        line-height: 1.35;
        font-weight: 700;
        color: #1e293b;
    }

    .ada-metric-value {
        font-size: 34px;
        line-height: 1.1;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 10px;
    }

    .ada-metric-value.is-empty {
        font-size: 18px;
        line-height: 1.4;
        font-weight: 700;
        color: #475569;
    }

    .ada-metric-value--danger {
        color: #b91c1c;
    }

    .ada-metric-value--warning {
        color: #b45309;
    }

    .ada-metric-value--info {
        color: #0369a1;
    }

    .ada-metric-meta {
        font-size: 13px;
        font-weight: 600;
        line-height: 1.45;
        color: #64748b;
    }

    .ada-comparison-caption {
        font-size: 13px;
        font-weight: 600;
        color: #64748b;
        margin-bottom: 16px;
    }

    .ada-compare-item {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 16px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .ada-compare-label {
        font-size: 13px;
        font-weight: 700;
        color: #475569;
    }

    .ada-compare-value {
        font-size: 28px;
        line-height: 1.15;
        font-weight: 800;
        color: #0f172a;
    }

    .ada-compare-value--positive {
        color: #047857;
    }

    .ada-compare-value--negative {
        color: #b91c1c;
    }

    .ada-compare-value--neutral {
        color: #334155;
        font-size: 20px;
        line-height: 1.35;
    }

    .ada-compare-note {
        font-size: 13px;
        font-weight: 600;
        color: #64748b;
    }

    .ada-section-heading-wrap {
        margin-bottom: 12px;
    }

    .ada-section-heading {
        font-size: 20px;
        font-weight: 700;
        color: #0f172a;
    }

    .ada-section-subtext {
        margin-top: 4px;
        font-size: 13px;
        font-weight: 600;
        color: #64748b;
    }

    .ada-chart-shell {
        height: 300px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 12px;
        background: #f8fafc;
    }

    .ada-chart-empty-label {
        margin-top: 10px;
        font-size: 13px;
        font-weight: 600;
        color: #64748b;
        text-align: center;
    }

    .ada-chart-empty-label.is-hidden {
        display: none;
    }

    .ada-table th {
        font-size: 14px;
        font-weight: 600;
        color: #334155;
        text-transform: none;
        letter-spacing: 0.01em;
    }

    .ada-table td {
        font-size: 14px;
    }

    .ada-table tbody tr:hover td {
        background: #f8fafc;
    }

    .ada-text-danger {
        color: #b91c1c;
        font-weight: 700;
    }

    .ada-text-warning {
        color: #b45309;
        font-weight: 700;
    }

    .ada-breakdown-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .ada-breakdown-item {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 12px;
        background: #f8fafc;
    }

    .ada-breakdown-top-row {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 8px;
    }

    .ada-breakdown-title {
        font-size: 14px;
        font-weight: 700;
        color: #1e293b;
    }

    .ada-breakdown-count {
        font-size: 15px;
        font-weight: 800;
        color: #0f172a;
    }

    .ada-breakdown-meta-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
        font-size: 13px;
        font-weight: 600;
        color: #64748b;
    }

    .ada-breakdown-progress {
        width: 100%;
        height: 8px;
        border-radius: 999px;
        background: #dbe4ef;
        overflow: hidden;
    }

    .ada-breakdown-progress-bar {
        display: block;
        height: 100%;
        border-radius: 999px;
    }

    .ada-breakdown-progress-bar--critical {
        background: #dc2626;
    }

    .ada-breakdown-progress-bar--warning {
        background: #d97706;
    }

    .ada-breakdown-progress-bar--info {
        background: #0284c7;
    }

    .ada-operational-stats.has-divider {
        border-top: 1px solid #e2e8f0;
        margin-top: 20px;
        padding-top: 20px;
    }

    .ada-stat-cluster {
        margin-top: 12px;
    }

    .ada-stat-cluster-title {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 16px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 10px;
    }

    .ada-tooltip-trigger {
        width: 24px;
        height: 24px;
        border-radius: 999px;
        border: 1px solid #cbd5e1;
        color: #475569;
        background: #ffffff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: default;
    }

    .ada-stat-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .ada-stat-item {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #f8fafc;
        padding: 12px;
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: 10px;
    }

    .ada-stat-item span {
        font-size: 13px;
        font-weight: 700;
        color: #64748b;
    }

    .ada-stat-item strong {
        font-size: 18px;
        font-weight: 800;
        color: #0f172a;
    }

    .ada-critical-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .ada-critical-description {
        margin-top: 4px;
        color: #1e293b;
    }

    .ada-critical-meta {
        margin-top: 6px;
    }

    .ada-empty-state {
        border: 1px dashed #cbd5e1;
        border-radius: 12px;
        padding: 32px 20px;
        text-align: center;
        background: #ffffff;
    }

    .ada-empty-icon {
        width: 56px;
        height: 56px;
        border-radius: 999px;
        margin: 0 auto 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #e0e7ff;
        color: #4338ca;
        font-size: 22px;
    }

    .ada-empty-title {
        font-size: 24px;
        line-height: 1.25;
        color: #0f172a;
        margin-bottom: 8px;
    }

    .ada-empty-message {
        font-size: 14px;
        line-height: 1.5;
        color: #475569;
        max-width: 560px;
        margin: 0 auto;
    }

    .ada-empty-actions {
        margin-top: 16px;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    @media (min-width: 768px) {
        .ada-filter-field {
            grid-column: span 6;
        }

        .ada-filter-field--search {
            grid-column: span 12;
        }

        .ada-col-3,
        .ada-col-4,
        .ada-col-6 {
            grid-column: span 6;
        }
    }

    @media (min-width: 1200px) {
        .ada-filter-field {
            grid-column: span 2;
        }

        .ada-filter-field--search {
            grid-column: span 4;
        }

        .ada-filter-field--actions {
            grid-column: span 2;
            justify-content: flex-end;
        }

        .ada-col-3 {
            grid-column: span 3;
        }

        .ada-col-4 {
            grid-column: span 4;
        }

        .ada-col-6 {
            grid-column: span 6;
        }

        .ada-col-12 {
            grid-column: span 12;
        }
    }

    @media (max-width: 767px) {
        .ada-header {
            flex-direction: column;
            align-items: stretch;
        }

        .ada-header-actions {
            width: 100%;
        }

        .ada-header-actions .admin-btn {
            width: 100%;
            justify-content: center;
        }

        .ada-filter-field--actions .admin-btn {
            width: 100%;
        }

        .ada-stat-grid {
            grid-template-columns: 1fr;
        }

        .ada-chart-shell {
            height: 260px;
        }

        .ada-compare-value {
            font-size: 24px;
        }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const rangeSelect = document.getElementById('analytics-range');
        const customDateFields = Array.from(document.querySelectorAll('[data-custom-field]'));

        function toggleCustomDateFields() {
            if (!rangeSelect) {
                return;
            }

            const isCustom = rangeSelect.value === 'custom';
            customDateFields.forEach(function (field) {
                field.classList.toggle('is-hidden', !isCustom);
                const input = field.querySelector('input[type="date"]');
                if (input) {
                    input.required = isCustom;
                }
            });
        }

        if (rangeSelect) {
            rangeSelect.addEventListener('change', toggleCustomDateFields);
            toggleCustomDateFields();
        }

        if (typeof Chart === 'undefined') {
            return;
        }

        const dailyConfig = {
            labels: @json($dailyLabels->all()),
            data: @json($dailyCounts->all()),
        };

        const hourlyConfig = {
            labels: @json($hourlyLabels->all()),
            data: @json($hourlyCounts->all()),
        };

        function mountChart(config) {
            const canvas = document.getElementById(config.canvasId);
            if (!canvas) {
                return;
            }

            const emptyLabel = document.getElementById(config.emptyLabelId);
            const hasData = config.series.some(function (value) {
                return Number(value) > 0;
            });

            if (emptyLabel) {
                emptyLabel.classList.toggle('is-hidden', hasData);
            }

            const chartData = hasData ? config.series : config.series.map(function () {
                return 0;
            });

            new Chart(canvas.getContext('2d'), {
                type: config.type,
                data: {
                    labels: config.labels,
                    datasets: [{
                        label: config.label,
                        data: chartData,
                        borderColor: config.color,
                        backgroundColor: config.backgroundColor,
                        borderWidth: 2,
                        borderRadius: config.type === 'bar' ? 8 : 0,
                        pointRadius: config.type === 'line' ? 3 : 0,
                        pointHoverRadius: config.type === 'line' ? 5 : 0,
                        fill: config.type === 'line',
                        tension: config.type === 'line' ? 0.3 : 0,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            display: false,
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return context.parsed.y + ' actions';
                                },
                            },
                        },
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false,
                            },
                            ticks: {
                                color: '#475569',
                                autoSkip: true,
                                maxTicksLimit: 8,
                                maxRotation: 0,
                                minRotation: 0,
                                font: {
                                    size: 11,
                                    weight: '600',
                                },
                            },
                        },
                        y: {
                            beginAtZero: true,
                            suggestedMax: hasData ? undefined : 1,
                            ticks: {
                                precision: 0,
                                color: '#475569',
                                font: {
                                    size: 11,
                                    weight: '600',
                                },
                            },
                            grid: {
                                color: 'rgba(148, 163, 184, 0.22)',
                            },
                        },
                    },
                },
            });
        }

        mountChart({
            canvasId: 'analytics-daily-chart',
            emptyLabelId: 'daily-chart-empty-label',
            labels: dailyConfig.labels,
            series: dailyConfig.data,
            label: 'Daily Activity',
            type: 'bar',
            color: '#4f46e5',
            backgroundColor: 'rgba(99, 102, 241, 0.35)',
        });

        mountChart({
            canvasId: 'analytics-hourly-chart',
            emptyLabelId: 'hourly-chart-empty-label',
            labels: hourlyConfig.labels,
            series: hourlyConfig.data,
            label: 'Hourly Activity',
            type: 'line',
            color: '#0f766e',
            backgroundColor: 'rgba(20, 184, 166, 0.18)',
        });
    });
</script>
@endpush
