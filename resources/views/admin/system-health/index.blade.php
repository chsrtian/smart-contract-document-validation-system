@extends('layouts.admin')

@push('styles')
<style>
    :root {
        --shx-critical: #DC2626;
        --shx-warning: #F59E0B;
        --shx-healthy: #10B981;
        --shx-bg: #f8fafc;
        --shx-card: #ffffff;
        --shx-border: #e2e8f0;
        --shx-text: #0f172a;
        --shx-muted: #64748b;
        --shx-focus: #4f46e5;
    }

    .shx-page {
        max-width: 1440px;
        margin: 0 auto;
        display: flex;
        flex-direction: column;
        gap: 24px;
        padding-bottom: 24px;
    }

    .shx-card {
        background: var(--shx-card);
        border: 1px solid var(--shx-border);
        border-radius: 16px;
        box-shadow: 0 8px 20px -16px rgba(15, 23, 42, 0.35);
    }

    .shx-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 16px;
        flex-wrap: wrap;
    }

    .shx-title {
        margin: 0;
        line-height: 1.2;
    }

    .shx-subtitle {
        margin: 0.25rem 0 0;
    }

    .shx-header-actions {
        display: flex;
        gap: 12px;
        align-items: center;
        flex-wrap: wrap;
    }

    .shx-last-checked {
        color: #475569;
        font-size: 0.8125rem;
        font-weight: 600;
        padding: 10px 12px;
        border: 1px solid var(--shx-border);
        border-radius: 10px;
        background: #ffffff;
        min-height: 40px;
        display: inline-flex;
        align-items: center;
    }

    .shx-btn {
        min-height: 40px;
        border-radius: 10px;
        border: 1px solid transparent;
        padding: 0 14px;
        font-size: 0.875rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        cursor: pointer;
        text-decoration: none;
        transition: background-color 160ms ease, color 160ms ease, border-color 160ms ease, box-shadow 160ms ease;
    }

    .shx-btn-primary {
        background: #4f46e5;
        color: #ffffff;
        border-color: #4f46e5;
    }

    .shx-btn-primary:hover {
        background: #4338ca;
        border-color: #4338ca;
    }

    .shx-btn-secondary {
        background: #f8fafc;
        border-color: #cbd5e1;
        color: #0f172a;
    }

    .shx-btn-secondary:hover {
        background: #f1f5f9;
        border-color: #94a3b8;
    }

    .shx-btn:focus-visible,
    .shx-alert-link:focus-visible {
        outline: none;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.24);
    }

    .shx-overview {
        padding: 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    }

    .shx-overview-status {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .shx-overview-message {
        margin: 0;
        color: #1e293b;
        font-size: 1rem;
        font-weight: 600;
    }

    .shx-overview-meta {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #475569;
        font-size: 0.875rem;
        flex-wrap: wrap;
    }

    .shx-overview-sep {
        color: #94a3b8;
    }

    .shx-badge {
        border-radius: 9999px;
        padding: 4px 10px;
        min-height: 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.6875rem;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }

    .shx-badge--critical {
        background: #DC2626;
        color: #ffffff;
    }

    .shx-badge--warning {
        background: #F59E0B;
        color: #1f2937;
    }

    .shx-badge--healthy {
        background: #10B981;
        color: #ffffff;
    }

    .shx-grid-12 {
        display: grid;
        grid-template-columns: repeat(12, minmax(0, 1fr));
        gap: 24px;
    }

    .shx-col-12 {
        grid-column: span 12;
    }

    .shx-section-card {
        padding: 24px;
    }

    .shx-section-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        flex-wrap: wrap;
        margin-bottom: 16px;
    }

    .shx-section-title {
        margin: 0;
        color: var(--shx-text);
        font-size: 1.0625rem;
        font-weight: 800;
        line-height: 1.2;
    }

    .shx-section-subtitle {
        margin: 6px 0 0;
        color: var(--shx-muted);
        font-size: 0.875rem;
    }

    .shx-section-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .shx-component-tile {
        border: 1px solid var(--shx-border);
        border-radius: 14px;
        padding: 20px;
        background: #ffffff;
        display: flex;
        flex-direction: column;
        gap: 12px;
        min-height: 220px;
    }

    .shx-component-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
    }

    .shx-component-title-wrap {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }

    .shx-component-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: #eef2ff;
        color: #4338ca;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 0.95rem;
    }

    .shx-component-title {
        margin: 0;
        color: var(--shx-text);
        font-size: 0.95rem;
        font-weight: 800;
        line-height: 1.25;
    }

    .shx-component-message {
        margin: 0;
        color: #334155;
        font-size: 0.875rem;
        line-height: 1.45;
        min-height: 40px;
    }

    .shx-metric-label {
        margin: 0;
        color: #64748b;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .shx-metric-value {
        margin: 2px 0 0;
        color: #0f172a;
        font-size: 1.25rem;
        font-weight: 800;
        line-height: 1.1;
        word-break: break-word;
    }

    .shx-storage-progress {
        width: 100%;
        height: 10px;
        border-radius: 999px;
        background: #e2e8f0;
        overflow: hidden;
        margin-top: 4px;
    }

    .shx-storage-progress-fill {
        height: 100%;
        border-radius: inherit;
    }

    .shx-storage-progress-fill--critical {
        background: #DC2626;
    }

    .shx-storage-progress-fill--warning {
        background: #F59E0B;
    }

    .shx-storage-progress-fill--healthy {
        background: #10B981;
    }

    .shx-component-meta {
        margin: 0;
        color: #64748b;
        font-size: 0.8125rem;
        line-height: 1.4;
    }

    .shx-metric-card {
        border: 1px solid var(--shx-border);
        border-radius: 14px;
        padding: 20px;
        background: #ffffff;
    }

    .shx-metric-card-label {
        margin: 0;
        color: #64748b;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .shx-metric-card-value {
        margin: 8px 0 0;
        color: #0f172a;
        font-size: 1.625rem;
        line-height: 1.1;
        font-weight: 800;
    }

    .shx-metric-card-meta {
        margin: 8px 0 0;
        color: #64748b;
        font-size: 0.8125rem;
    }

    .shx-alert-panel {
        border: 1px solid var(--shx-border);
        border-radius: 14px;
        background: #ffffff;
        padding: 16px;
    }

    .shx-alert-group + .shx-alert-group {
        margin-top: 16px;
    }

    .shx-alert-group-title {
        margin: 0 0 10px;
        color: #0f172a;
        font-size: 0.875rem;
        font-weight: 800;
    }

    .shx-alert-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .shx-alert-item {
        border-radius: 12px;
        border: 1px solid var(--shx-border);
        padding: 12px;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
    }

    .shx-alert-item--critical {
        background: #fef2f2;
        border-color: #fecaca;
    }

    .shx-alert-item--warning {
        background: #fffbeb;
        border-color: #fcd34d;
    }

    .shx-alert-title {
        margin: 0;
        font-size: 0.875rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.3;
    }

    .shx-alert-message {
        margin: 4px 0 0;
        font-size: 0.875rem;
        color: #334155;
        line-height: 1.4;
    }

    .shx-alert-time {
        margin: 0;
        color: #64748b;
        font-size: 0.75rem;
        white-space: nowrap;
    }

    .shx-alert-empty {
        margin: 0;
        padding: 18px;
        border-radius: 12px;
        background: #f8fafc;
        border: 1px solid var(--shx-border);
        color: #334155;
        font-size: 0.875rem;
    }

    .shx-refresh-spin {
        animation: shx-spin 1s linear infinite;
    }

    @keyframes shx-spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    @media (min-width: 768px) {
        .shx-col-md-6 {
            grid-column: span 6;
        }
    }

    @media (min-width: 1200px) {
        .shx-col-xl-4 {
            grid-column: span 4;
        }

        .shx-col-xl-3 {
            grid-column: span 3;
        }
    }

    @media (max-width: 767px) {
        .shx-page {
            gap: 16px;
        }

        .shx-grid-12 {
            gap: 16px;
        }

        .shx-overview,
        .shx-section-card {
            padding: 16px;
        }

        .shx-alert-item {
            flex-direction: column;
        }

    }
</style>
@endpush

@section('content')
    @php
        $statusClassMap = [
            'critical' => 'shx-badge--critical',
            'danger' => 'shx-badge--critical',
            'warning' => 'shx-badge--warning',
            'healthy' => 'shx-badge--healthy',
        ];

        $statusLabelMap = [
            'critical' => 'CRITICAL',
            'danger' => 'CRITICAL',
            'warning' => 'WARNING',
            'healthy' => 'HEALTHY',
        ];

        $componentIcons = [
            'backups' => 'fa-archive',
            'database' => 'fa-database',
            'storage' => 'fa-hard-drive',
            'queue' => 'fa-list-check',
            'blockchain' => 'fa-link',
        ];

        $componentOrder = ['backups', 'database', 'storage', 'queue', 'blockchain'];
        $overallStatus = strtolower($health['overall_status']['status'] ?? 'warning');
        $overallBadgeClass = $statusClassMap[$overallStatus] ?? 'shx-badge--warning';
        $overallBadgeLabel = $statusLabelMap[$overallStatus] ?? strtoupper($overallStatus);

        $criticalAlerts = $health['alerts']['critical_alerts'] ?? [];
        $warningAlerts = $health['alerts']['warning_alerts'] ?? [];
        $criticalCount = $health['alerts']['critical_count'] ?? count($criticalAlerts);
        $warningCount = $health['alerts']['warning_count'] ?? count($warningAlerts);
        $totalAlerts = $criticalCount + $warningCount;

        $systemLoad = $health['performance']['system_load']['1min'] ?? 0;
    @endphp

    <main class="shx-page" aria-label="System health dashboard">
        <section class="shx-header" aria-label="System health heading and controls">
            <div>
                <h1 class="admin-page-title shx-title">System Health Monitor</h1>
                <p class="admin-page-subtitle shx-subtitle">Operational status, active alerts, and component health telemetry</p>
            </div>

            <div class="shx-header-actions">
                <p class="shx-last-checked">Last checked: <span id="lastChecked">{{ $health['last_checked'] }}</span></p>

                <button type="button" class="shx-btn shx-btn-primary" id="refreshBtn" onclick="refreshHealth()">
                    <i class="fas fa-sync-alt" id="refreshIcon" aria-hidden="true"></i>
                    <span>Refresh</span>
                </button>
            </div>
        </section>

        <section class="shx-card shx-overview" aria-labelledby="shx-overview-title">
            <div class="shx-overview-status">
                <h2 id="shx-overview-title" class="shx-section-title" style="margin:0;">Overall Status</h2>
                <span class="shx-badge {{ $overallBadgeClass }}">{{ $overallBadgeLabel }}</span>
                <p class="shx-overview-message" id="overallMessage">{{ $health['overall_status']['message'] }}</p>
            </div>

            <div class="shx-overview-meta">
                <span>Uptime: <strong id="uptime">{{ $health['overall_status']['uptime'] }}</strong></span>
                <span class="shx-overview-sep">|</span>
                <span>Last Incident: <strong id="lastIncident">{{ $health['overall_status']['last_incident'] }}</strong></span>
                <span class="shx-overview-sep">|</span>
                <span>Active Alerts: <strong>{{ $totalAlerts }}</strong></span>
            </div>
        </section>

        <section class="shx-card shx-section-card" aria-labelledby="shx-active-alerts-title" aria-label="Active system alerts" aria-live="polite">
            <div class="shx-section-head">
                <div>
                    <h2 id="shx-active-alerts-title" class="shx-section-title">Active Alerts</h2>
                    <p class="shx-section-subtitle">Critical issues are prioritized and surfaced first.</p>
                </div>

                <div class="shx-section-actions">
                    <a href="{{ route('admin.notifications.index') }}" class="shx-btn shx-btn-primary">View All Alerts</a>

                    @if($totalAlerts > 0)
                        <form method="POST" action="{{ route('admin.notifications.mark-all-read') }}">
                            @csrf
                            <button type="submit" class="shx-btn shx-btn-secondary">Acknowledge Alerts</button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="shx-alert-panel">
                @if($totalAlerts > 0)
                    @if($criticalCount > 0)
                        <div class="shx-alert-group">
                            <h3 class="shx-alert-group-title">Critical ({{ $criticalCount }})</h3>

                            <div class="shx-alert-list" role="list" aria-label="Critical alerts list">
                                @foreach($criticalAlerts as $alert)
                                    <article class="shx-alert-item shx-alert-item--critical" role="listitem">
                                        <div>
                                            <p class="shx-alert-title">{{ $alert['title'] }}</p>
                                            <p class="shx-alert-message">{{ $alert['message'] }}</p>
                                        </div>

                                        <p class="shx-alert-time">{{ $alert['created_at'] }}</p>
                                    </article>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($warningCount > 0)
                        <div class="shx-alert-group">
                            <h3 class="shx-alert-group-title">Warning ({{ $warningCount }})</h3>

                            <div class="shx-alert-list" role="list" aria-label="Warning alerts list">
                                @foreach($warningAlerts as $alert)
                                    <article class="shx-alert-item shx-alert-item--warning" role="listitem">
                                        <div>
                                            <p class="shx-alert-title">{{ $alert['title'] }}</p>
                                            <p class="shx-alert-message">{{ $alert['message'] }}</p>
                                        </div>

                                        <p class="shx-alert-time">{{ $alert['created_at'] }}</p>
                                    </article>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @else
                    <p class="shx-alert-empty">No active alerts. The system is currently operating without critical or warning notifications.</p>
                @endif
            </div>
        </section>

        <section class="shx-card shx-section-card" aria-labelledby="shx-component-health-title">
            <div class="shx-section-head">
                <div>
                    <h2 id="shx-component-health-title" class="shx-section-title">Component Health</h2>
                    <p class="shx-section-subtitle">Unified operational view for Backups, Database, Storage, Queue, and Blockchain.</p>
                </div>
            </div>

            <div class="shx-grid-12">
                @foreach($componentOrder as $componentKey)
                    @php
                        $component = $health[$componentKey] ?? null;
                    @endphp

                    @if($component)
                        @php
                            $statusKey = strtolower($component['status'] ?? 'warning');
                            $badgeClass = $statusClassMap[$statusKey] ?? 'shx-badge--warning';
                            $badgeLabel = $statusLabelMap[$statusKey] ?? strtoupper($statusKey);
                            $metricLabel = $component['metric_label'] ?? 'Key Metric';
                            $metricValue = $component['metric_value'] ?? 'N/A';

                            $storageUsedPct = 0;
                            $storageBarClass = 'healthy';

                            if ($componentKey === 'storage') {
                                $storageUsedPct = (float) ($component['details']['used_percentage'] ?? 0);
                                $storageUsedPct = min(100, max(0, $storageUsedPct));
                                $storageBarClass = $storageUsedPct > 85 ? 'critical' : ($storageUsedPct >= 60 ? 'warning' : 'healthy');
                            }
                        @endphp

                        <article class="shx-component-tile shx-col-12 shx-col-md-6 shx-col-xl-4" aria-label="{{ $component['name'] }} health status">
                            <div class="shx-component-head">
                                <div class="shx-component-title-wrap">
                                    <span class="shx-component-icon" aria-hidden="true">
                                        <i class="fas {{ $componentIcons[$componentKey] ?? 'fa-circle-info' }}"></i>
                                    </span>

                                    <h3 class="shx-component-title">{{ $component['name'] }}</h3>
                                </div>

                                <span class="shx-badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
                            </div>

                            <p class="shx-component-message">{{ $component['message'] }}</p>

                            <div>
                                <p class="shx-metric-label">{{ $metricLabel }}</p>
                                <p class="shx-metric-value">{{ $metricValue }}</p>
                            </div>

                            @if($componentKey === 'storage')
                                <div>
                                    <div class="shx-storage-progress" aria-label="Storage usage {{ number_format($storageUsedPct, 2) }} percent">
                                        <div class="shx-storage-progress-fill shx-storage-progress-fill--{{ $storageBarClass }}" style="width: {{ $storageUsedPct }}%;"></div>
                                    </div>

                                    <p class="shx-component-meta" style="margin-top:8px;">
                                        Used: {{ $component['details']['used'] ?? 'N/A' }} / Total: {{ $component['details']['total'] ?? 'N/A' }}
                                    </p>
                                </div>
                            @elseif($componentKey === 'backups')
                                <p class="shx-component-meta">Failed this week: {{ $component['details']['failed_this_week'] ?? 0 }}</p>
                            @elseif($componentKey === 'blockchain')
                                <p class="shx-component-meta">Pending: {{ $component['details']['pending'] ?? 0 }} | Failure Rate: {{ $component['details']['failure_rate'] ?? '0%' }}</p>
                            @elseif($componentKey === 'queue')
                                <p class="shx-component-meta">Jobs queued today: {{ $component['details']['jobs_today'] ?? 0 }}</p>
                            @elseif($componentKey === 'database')
                                <p class="shx-component-meta">Connection: {{ strtoupper($component['details']['connection'] ?? 'N/A') }}</p>
                            @endif
                        </article>
                    @endif
                @endforeach
            </div>
        </section>

        <section class="shx-card shx-section-card" aria-labelledby="shx-performance-title">
            <div class="shx-section-head">
                <div>
                    <h2 id="shx-performance-title" class="shx-section-title">Performance Metrics</h2>
                    <p class="shx-section-subtitle">Core throughput and processing indicators for the current system state.</p>
                </div>
            </div>

            <div class="shx-grid-12">
                <article class="shx-metric-card shx-col-12 shx-col-md-6 shx-col-xl-3" aria-label="Documents today metric">
                    <p class="shx-metric-card-label">Documents Today</p>
                    <p class="shx-metric-card-value">{{ number_format($health['performance']['documents_today'] ?? 0) }}</p>
                </article>

                <article class="shx-metric-card shx-col-12 shx-col-md-6 shx-col-xl-3" aria-label="Pending corrections metric">
                    <p class="shx-metric-card-label">Pending Corrections</p>
                    <p class="shx-metric-card-value">{{ number_format($health['performance']['corrections_pending'] ?? 0) }}</p>
                </article>

                <article class="shx-metric-card shx-col-12 shx-col-md-6 shx-col-xl-3" aria-label="Average processing time metric">
                    <p class="shx-metric-card-label">Avg Processing Time</p>
                    <p class="shx-metric-card-value">{{ $health['performance']['avg_document_processing_time'] ?? 'N/A' }}</p>
                    <p class="shx-metric-card-meta">Based on recent processed documents</p>
                </article>

                <article class="shx-metric-card shx-col-12 shx-col-md-6 shx-col-xl-3" aria-label="System load metric">
                    <p class="shx-metric-card-label">System Load</p>
                    <p class="shx-metric-card-value">{{ $systemLoad }}</p>
                    <p class="shx-metric-card-meta">1-minute average load</p>
                </article>
            </div>
        </section>
    </main>
@endsection

@push('scripts')
<script>
    let refreshInProgress = false;

    function refreshHealth() {
        if (refreshInProgress) {
            return;
        }

        const btn = document.getElementById('refreshBtn');
        const icon = document.getElementById('refreshIcon');
        refreshInProgress = true;

        btn.disabled = true;
        icon.classList.add('shx-refresh-spin');

        fetch('{{ route("admin.system-health.refresh") }}')
            .then(function (response) {
                return response.json();
            })
            .then(function () {
                window.location.reload();
            })
            .catch(function () {
                window.location.reload();
            })
            .finally(function () {
                refreshInProgress = false;
                btn.disabled = false;
                icon.classList.remove('shx-refresh-spin');
            });
    }

    setInterval(refreshHealth, 60000);
</script>
@endpush