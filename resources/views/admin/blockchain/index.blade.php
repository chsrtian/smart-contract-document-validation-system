@extends('layouts.admin')

@section('content')
    @php
        $failedCount = (int) ($stats['failed'] ?? 0);
        $pendingCount = (int) ($stats['pending'] ?? 0);
        $confirmedCount = (int) ($stats['confirmed'] ?? 0);
        $totalAnchored = (int) ($stats['total_anchored'] ?? 0);
        $successRate = (float) ($stats['success_rate'] ?? 0);
        $totalSubmitted = (int) ($stats['total_submitted'] ?? ($pendingCount + $confirmedCount + $failedCount));
        $pendingRatio = (float) ($stats['pending_ratio'] ?? 0);

        $avgConfirmationTime = $stats['avg_confirmation_time'] ?? 'N/A';
        $avgConfirmationTimeText = is_numeric($avgConfirmationTime)
            ? number_format((float) $avgConfirmationTime, 1) . ' min'
            : 'N/A';

        $failedStatusLabel = $failedCount > 0 ? 'Attention needed' : 'Stable';
        $failedCardClass = $failedCount > 0 ? 'bmd-card--failed' : 'bmd-card--idle';

        $lastUpdated = $lastUpdatedAt ?? now();
        $lastUpdatedLabel = $lastUpdated->format('M d, Y H:i:s');
        $lastUpdatedIso = $lastUpdated->toIso8601String();

        $successRateClamped = max(0, min($successRate, 100));
    @endphp

    <div class="admin-container bmd-page">
        <header class="admin-page-header bmd-header">
            <div class="bmd-title-wrap">
                <h1 class="admin-page-title">Blockchain Monitoring Dashboard</h1>
                <p class="admin-page-subtitle">Monitor document anchoring and transaction health in real time.</p>
            </div>

            <div class="bmd-controls" aria-label="Dashboard controls">
                <div class="bmd-last-updated" role="status" aria-live="polite" aria-atomic="true">
                    <span class="bmd-last-updated-label">Last updated:</span>
                    <span class="bmd-last-updated-time" id="bmd-last-updated-time">{{ $lastUpdatedLabel }}</span>
                    <span class="bmd-last-updated-relative" id="bmd-last-updated-relative">0s ago</span>
                </div>

                <div class="bmd-auto-refresh" aria-label="Auto refresh status">
                    <span class="bmd-auto-refresh-dot" aria-hidden="true"></span>
                    <span>Auto-refresh in <strong id="bmd-refresh-countdown">60</strong>s</span>
                </div>

                <a
                    href="{{ route('admin.blockchain.index') }}"
                    class="admin-btn admin-btn-secondary"
                    aria-label="Refresh blockchain monitoring dashboard data"
                >
                    <i class="fas fa-rotate-right" aria-hidden="true"></i>
                    Refresh Data
                </a>
            </div>
        </header>

        <section class="bmd-section" aria-label="Blockchain key metrics">
            <div class="admin-tier-header">
                <span class="admin-tier-label">Overview</span>
                <span class="admin-tier-rule"></span>
            </div>

            <div class="bmd-grid">
                <article class="admin-card bmd-metric-card bmd-col-3 bmd-card--anchored" aria-label="{{ number_format($totalAnchored) }} anchored blockchain transactions">
                    <div class="admin-card-body">
                        <div class="bmd-metric-header">
                            <span class="bmd-metric-label">Total Anchored</span>
                            <span class="bmd-status-chip bmd-status-chip--green">
                                <span class="bmd-status-dot" aria-hidden="true"></span>
                                Confirmed
                            </span>
                        </div>
                        <div class="bmd-metric-value">{{ number_format($totalAnchored) }}</div>
                        <p class="bmd-metric-subtext">Successfully confirmed on chain.</p>
                    </div>
                </article>

                <article class="admin-card bmd-metric-card bmd-col-3 bmd-card--pending" aria-label="{{ number_format($pendingCount) }} documents pending in queue">
                    <div class="admin-card-body">
                        <div class="bmd-metric-header">
                            <span class="bmd-metric-label">Pending Queue</span>
                            <span class="bmd-status-chip bmd-status-chip--amber">
                                <span class="bmd-status-dot" aria-hidden="true"></span>
                                In Progress
                            </span>
                        </div>
                        <div class="bmd-metric-value">{{ number_format($pendingCount) }}</div>
                        <p class="bmd-metric-subtext">{{ number_format($pendingCount) }} documents awaiting confirmation.</p>
                        <div class="bmd-progress" aria-hidden="true">
                            <span class="bmd-progress-fill" style="width: {{ $pendingRatio }}%"></span>
                        </div>
                    </div>
                </article>

                <article class="admin-card bmd-metric-card bmd-col-3 {{ $failedCardClass }}" aria-label="{{ number_format($failedCount) }} failed blockchain transactions">
                    <div class="admin-card-body">
                        <div class="bmd-metric-header">
                            <span class="bmd-metric-label">Failed Transactions</span>
                            <span class="bmd-status-chip {{ $failedCount > 0 ? 'bmd-status-chip--red' : 'bmd-status-chip--gray' }}">
                                <span class="bmd-status-dot" aria-hidden="true"></span>
                                {{ $failedStatusLabel }}
                            </span>
                        </div>
                        <div class="bmd-metric-value">{{ number_format($failedCount) }}</div>
                        <p class="bmd-metric-subtext">
                            {{ $failedCount > 0 ? 'Failures require manual retry or inspection.' : 'No failed transactions in the current cycle.' }}
                        </p>
                    </div>
                </article>

                <article class="admin-card bmd-metric-card bmd-col-3 bmd-card--success-rate" aria-label="Current blockchain success rate is {{ number_format($successRate, 1) }} percent">
                    <div class="admin-card-body">
                        <div class="bmd-metric-header">
                            <span class="bmd-metric-label">Success Rate</span>
                            <span class="bmd-status-chip bmd-status-chip--green">
                                <span class="bmd-status-dot" aria-hidden="true"></span>
                                Reliability
                            </span>
                        </div>

                        <div class="bmd-success-layout">
                            <div class="bmd-progress-ring" style="--progress: {{ $successRateClamped }};" aria-hidden="true">
                                <div class="bmd-progress-ring-inner">{{ number_format($successRate, 1) }}%</div>
                            </div>
                        </div>

                        <p class="bmd-metric-subtext">Avg. confirmation time: {{ $avgConfirmationTimeText }}</p>
                    </div>
                </article>
            </div>
        </section>

        <section class="bmd-section bmd-section--actions" aria-label="Blockchain navigation">
            <div class="admin-tier-header">
                <span class="admin-tier-label">Transactions</span>
                <span class="admin-tier-rule"></span>
            </div>

            <div class="bmd-grid">
                <a href="{{ route('admin.blockchain.pending') }}" class="admin-card bmd-action-card bmd-col-4" aria-label="Open pending queue. {{ number_format($pendingCount) }} documents pending.">
                    <div class="admin-card-body">
                        <div class="bmd-action-icon bmd-action-icon--amber" aria-hidden="true">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="bmd-action-content">
                            <h3 class="bmd-action-title">Pending Queue</h3>
                            <p class="bmd-action-text">Review items waiting for blockchain confirmation.</p>
                            <span class="bmd-action-meta">{{ number_format($pendingCount) }} pending · View all <i class="fas fa-arrow-right" aria-hidden="true"></i></span>
                        </div>
                    </div>
                </a>

                <a href="{{ route('admin.blockchain.confirmed') }}" class="admin-card bmd-action-card bmd-col-4" aria-label="Open confirmed transactions. {{ number_format($confirmedCount) }} documents confirmed.">
                    <div class="admin-card-body">
                        <div class="bmd-action-icon bmd-action-icon--green" aria-hidden="true">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="bmd-action-content">
                            <h3 class="bmd-action-title">Confirmed Transactions</h3>
                            <p class="bmd-action-text">Inspect successfully anchored blockchain entries.</p>
                            <span class="bmd-action-meta">{{ number_format($confirmedCount) }} confirmed · View all <i class="fas fa-arrow-right" aria-hidden="true"></i></span>
                        </div>
                    </div>
                </a>

                <a href="{{ route('admin.blockchain.failed') }}" class="admin-card bmd-action-card bmd-col-4" aria-label="Open failed transactions. {{ number_format($failedCount) }} failed transactions.">
                    <div class="admin-card-body">
                        <div class="bmd-action-icon {{ $failedCount > 0 ? 'bmd-action-icon--red' : 'bmd-action-icon--gray' }}" aria-hidden="true">
                            <i class="fas fa-triangle-exclamation"></i>
                        </div>
                        <div class="bmd-action-content">
                            <h3 class="bmd-action-title">Failed Transactions</h3>
                            <p class="bmd-action-text">Analyze errors and retry failed anchoring attempts.</p>
                            <span class="bmd-action-meta">{{ number_format($failedCount) }} failed · View all <i class="fas fa-arrow-right" aria-hidden="true"></i></span>
                        </div>
                    </div>
                </a>
            </div>
        </section>
    </div>
@endsection

@push('styles')
<style>
    .bmd-page {
        --bmd-gap: 24px;
    }

    .bmd-page :is(a, button, [tabindex]):focus-visible {
        outline: 3px solid rgba(79, 70, 229, 0.45);
        outline-offset: 2px;
    }

    .bmd-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        margin-bottom: 24px;
    }

    .bmd-title-wrap {
        max-width: 720px;
    }

    .bmd-controls {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .bmd-last-updated,
    .bmd-auto-refresh {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #ffffff;
        padding: 8px 12px;
        font-size: 13px;
        color: #334155;
        font-weight: 600;
    }

    .bmd-last-updated-label {
        color: #64748b;
    }

    .bmd-last-updated-relative {
        color: #0f766e;
    }

    .bmd-auto-refresh-dot {
        width: 8px;
        height: 8px;
        border-radius: 999px;
        background: #16a34a;
        box-shadow: 0 0 0 5px rgba(22, 163, 74, 0.16);
    }

    .bmd-section {
        margin-bottom: 24px;
    }

    .bmd-section--actions {
        border-top: 1px solid #dbe2ea;
        padding-top: 24px;
    }

    .bmd-grid {
        display: grid;
        grid-template-columns: repeat(12, minmax(0, 1fr));
        gap: var(--bmd-gap);
    }

    .bmd-col-3,
    .bmd-col-4 {
        grid-column: span 12;
    }

    .bmd-metric-card .admin-card-body {
        padding: 20px;
    }

    .bmd-metric-card {
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
    }

    .bmd-card--anchored {
        background: linear-gradient(180deg, rgba(16, 185, 129, 0.09), #ffffff 60%);
    }

    .bmd-card--pending {
        background: linear-gradient(180deg, rgba(245, 158, 11, 0.1), #ffffff 60%);
    }

    .bmd-card--failed {
        background: linear-gradient(180deg, rgba(239, 68, 68, 0.11), #ffffff 60%);
    }

    .bmd-card--idle {
        background: linear-gradient(180deg, rgba(148, 163, 184, 0.16), #ffffff 60%);
    }

    .bmd-card--success-rate {
        background: linear-gradient(180deg, rgba(34, 197, 94, 0.1), #ffffff 60%);
    }

    .bmd-metric-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 14px;
    }

    .bmd-metric-label {
        font-size: 14px;
        font-weight: 700;
        line-height: 1.4;
        color: #334155;
        letter-spacing: 0.01em;
    }

    .bmd-status-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        border: 1px solid transparent;
    }

    .bmd-status-dot {
        width: 7px;
        height: 7px;
        border-radius: 999px;
    }

    .bmd-status-chip--green {
        color: #166534;
        background: #ecfdf5;
        border-color: #bbf7d0;
    }

    .bmd-status-chip--green .bmd-status-dot {
        background: #16a34a;
    }

    .bmd-status-chip--amber {
        color: #92400e;
        background: #fffbeb;
        border-color: #fde68a;
    }

    .bmd-status-chip--amber .bmd-status-dot {
        background: #f59e0b;
    }

    .bmd-status-chip--red {
        color: #991b1b;
        background: #fef2f2;
        border-color: #fecaca;
    }

    .bmd-status-chip--red .bmd-status-dot {
        background: #ef4444;
    }

    .bmd-status-chip--gray {
        color: #475569;
        background: #f8fafc;
        border-color: #cbd5e1;
    }

    .bmd-status-chip--gray .bmd-status-dot {
        background: #64748b;
    }

    .bmd-metric-value {
        font-size: 42px;
        font-weight: 800;
        line-height: 1.05;
        color: #0f172a;
        margin-bottom: 10px;
    }

    .bmd-metric-subtext {
        margin: 0;
        font-size: 13px;
        font-weight: 600;
        line-height: 1.45;
        color: #475569;
    }

    .bmd-progress {
        width: 100%;
        height: 8px;
        border-radius: 999px;
        background: #e2e8f0;
        margin-top: 12px;
        overflow: hidden;
    }

    .bmd-progress-fill {
        display: block;
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, #f59e0b, #f97316);
    }

    .bmd-success-layout {
        display: flex;
        justify-content: flex-start;
        margin-bottom: 10px;
    }

    .bmd-progress-ring {
        --size: 96px;
        width: var(--size);
        height: var(--size);
        border-radius: 999px;
        background: conic-gradient(#16a34a calc(var(--progress) * 1%), #d1fae5 0);
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }

    .bmd-progress-ring::after {
        content: '';
        position: absolute;
        inset: 8px;
        background: #ffffff;
        border-radius: 999px;
    }

    .bmd-progress-ring-inner {
        position: relative;
        z-index: 1;
        font-size: 16px;
        font-weight: 800;
        color: #065f46;
    }

    .bmd-action-card {
        grid-column: span 12;
        text-decoration: none;
        color: inherit;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        transition: transform 180ms ease, box-shadow 180ms ease, border-color 180ms ease;
    }

    .bmd-action-card .admin-card-body {
        padding: 20px;
        display: flex;
        align-items: flex-start;
        gap: 14px;
    }

    .bmd-action-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 14px 24px -12px rgba(15, 23, 42, 0.25);
        border-color: #cbd5e1;
        cursor: pointer;
    }

    .bmd-action-icon {
        width: 46px;
        height: 46px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }

    .bmd-action-icon--amber {
        background: #fff7ed;
        color: #c2410c;
    }

    .bmd-action-icon--green {
        background: #ecfdf5;
        color: #15803d;
    }

    .bmd-action-icon--red {
        background: #fef2f2;
        color: #dc2626;
    }

    .bmd-action-icon--gray {
        background: #f1f5f9;
        color: #475569;
    }

    .bmd-action-content {
        min-width: 0;
    }

    .bmd-action-title {
        margin: 0 0 4px;
        font-size: 24px;
        line-height: 1.2;
        font-weight: 700;
        color: #0f172a;
    }

    .bmd-action-text {
        margin: 0;
        font-size: 14px;
        line-height: 1.45;
        font-weight: 600;
        color: #475569;
    }

    .bmd-action-meta {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 10px;
        font-size: 13px;
        font-weight: 700;
        color: #0f766e;
    }

    @media (min-width: 768px) {
        .bmd-col-3 {
            grid-column: span 6;
        }

        .bmd-col-4 {
            grid-column: span 6;
        }
    }

    @media (min-width: 1200px) {
        .bmd-col-3 {
            grid-column: span 3;
        }

        .bmd-col-4 {
            grid-column: span 4;
        }
    }

    @media (max-width: 767px) {
        .bmd-header {
            flex-direction: column;
            align-items: stretch;
        }

        .bmd-controls {
            justify-content: flex-start;
        }

        .bmd-controls .admin-btn {
            width: 100%;
            justify-content: center;
        }

        .bmd-last-updated,
        .bmd-auto-refresh {
            width: 100%;
            justify-content: space-between;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const relativeEl = document.getElementById('bmd-last-updated-relative');
        const countdownEl = document.getElementById('bmd-refresh-countdown');
        const lastUpdatedIso = @json($lastUpdatedIso);
        const refreshUrl = @json(route('admin.blockchain.index'));

        if (!relativeEl || !countdownEl || !lastUpdatedIso) {
            return;
        }

        const lastUpdatedAt = new Date(lastUpdatedIso);
        const refreshWindowSeconds = 60;
        let remaining = refreshWindowSeconds;

        function updateRelativeTime() {
            const seconds = Math.max(0, Math.floor((Date.now() - lastUpdatedAt.getTime()) / 1000));
            relativeEl.textContent = seconds + 's ago';
        }

        function updateRefreshCountdown() {
            countdownEl.textContent = String(remaining);
            if (remaining <= 0) {
                window.location.href = refreshUrl;
                return;
            }
            remaining -= 1;
        }

        updateRelativeTime();
        updateRefreshCountdown();

        window.setInterval(function () {
            updateRelativeTime();
            updateRefreshCountdown();
        }, 1000);
    });
</script>
@endpush
