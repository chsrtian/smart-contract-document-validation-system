@extends('layouts.admin')

@section('content')
    @php
        $escalationCount = \App\Models\CorrectionRequest::escalated()->escalationPending()->count();

        $documentsTotal = (int) ($stats['documents']['total'] ?? 0);
        $documentsToday = (int) ($stats['documents']['today'] ?? 0);
        $documentsPending = (int) ($stats['documents']['by_status']['pending'] ?? 0);
        $documentsCompleted = (int) ($stats['documents']['by_status']['completed'] ?? 0);
        $documentsRejected = (int) ($stats['documents']['by_status']['rejected'] ?? 0);
        $documentsDraft = (int) ($stats['documents']['by_status']['draft'] ?? 0);

        $completionBase = max(1, $documentsCompleted + $documentsRejected);
        $completionRate = (int) round(($documentsCompleted / $completionBase) * 100);

        $docStatusBase = max(1, $documentsCompleted + $documentsRejected + $documentsPending);
        $docCompletedWidth = ($documentsCompleted / $docStatusBase) * 100;
        $docRejectedWidth = ($documentsRejected / $docStatusBase) * 100;
        $docPendingWidth = 100 - $docCompletedWidth - $docRejectedWidth;

        $blockchainConfirmed = (int) ($stats['blockchain']['confirmed'] ?? 0);
        $blockchainPending = (int) ($stats['blockchain']['pending'] ?? 0);
        $blockchainFailed = (int) ($stats['blockchain']['failed'] ?? 0);
        $blockchainTotal = max(1, $blockchainConfirmed + $blockchainPending + $blockchainFailed);
        $blockchainConfirmedRate = (int) round(($blockchainConfirmed / $blockchainTotal) * 100);

        $alerts = [];
        if (($stats['blockchain']['failed'] ?? 0) > 0) {
            $alerts[] = [
                'label' => 'Failed Blockchain Transactions',
                'count' => (int) $stats['blockchain']['failed'],
                'href' => route('admin.blockchain.index', ['status' => 'failed']),
            ];
        }
        if (($stats['corrections']['pending'] ?? 0) > 0) {
            $alerts[] = [
                'label' => 'Pending Correction Requests',
                'count' => (int) $stats['corrections']['pending'],
                'href' => route('admin.corrections.index', ['status' => 'pending']),
            ];
        }
        if (($escalationCount ?? 0) > 0) {
            $alerts[] = [
                'label' => 'Escalated Cases (48h+)',
                'count' => (int) $escalationCount,
                'href' => route('admin.escalations.index'),
            ];
        }
        if (($stats['documents']['flagged_count'] ?? 0) > 0) {
            $alerts[] = [
                'label' => 'Flagged Documents',
                'count' => (int) $stats['documents']['flagged_count'],
                'href' => route('admin.documents.index', ['flagged' => '1']),
            ];
        }

        $alerts = array_slice($alerts, 0, 3);
    @endphp

    <div class="admin-container admin-dashboard-modern">
        <!-- Header -->
        <div class="admin-page-header dashboard-header">
            <div class="header-titles">
                <h1 class="admin-page-title">Admin Dashboard</h1>
                <p class="admin-page-subtitle">Administrative operations dashboard with prioritized live metrics</p>
            </div>
            <div class="header-context">
                <div class="header-user-info">
                    <span class="user-greeting">Welcome, {{ auth()->user()->name }}</span>
                    <span class="user-date" id="admin-dashboard-local-datetime">{{ now()->format('l, F j, Y • g:i A') }}</span>
                </div>
            </div>
        </div>

        <div class="admin-content dashboard-content">
            
            <!-- Priority Metrics Row -->
            <section class="priority-metrics-row" aria-label="Priority Metrics">
                <div class="priority-card">
                    <div class="priority-icon icon-blue">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <div class="priority-info">
                        <span class="priority-label">Total Documents</span>
                        <span class="priority-value">{{ number_format($documentsTotal) }}</span>
                    </div>
                </div>
                
                <div class="priority-card">
                    <div class="priority-icon icon-orange">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <div class="priority-info">
                        <span class="priority-label">Pending Corrections</span>
                        <span class="priority-value">{{ $stats['corrections']['pending'] }}</span>
                    </div>
                </div>

                <div class="priority-card">
                    <div class="priority-icon icon-green">
                        <i class="fas fa-link"></i>
                    </div>
                    <div class="priority-info">
                        <span class="priority-label">Blockchain Confirmed</span>
                        <span class="priority-value">{{ $blockchainConfirmedRate }}%</span>
                    </div>
                </div>

                <div class="priority-card">
                    <div class="priority-icon icon-neutral">
                        <i class="fas fa-server"></i>
                    </div>
                    <div class="priority-info">
                        <span class="priority-label">System Status</span>
                        <div class="priority-value status-online">
                            <span class="status-dot pulse"></span> Online
                        </div>
                    </div>
                </div>
            </section>

            <!-- Secondary Statistics Grid -->
            <section class="dashboard-grid-main" aria-label="Detailed Statistics">
                <!-- Document Flow -->
                <article class="dash-card card-docs">
                    <div class="dash-card-header">
                        <div class="card-title-group">
                            <h3><i class="fas fa-chart-area" style="color: #0ea5e9; margin-right: 0.4rem;"></i> Document Flow</h3>
                            <span class="card-meta">{{ $documentsToday }} Added Today</span>
                        </div>
                        <a href="{{ route('admin.documents.index') }}" class="card-action-link">View All &rarr;</a>
                    </div>
                    <div class="dash-card-body">
                        <!-- Chart visual -->
                        <div class="command-doc-chart" aria-hidden="true" style="margin-bottom: 0.5rem; height: 80px;">
                            <svg viewBox="0 0 440 86" preserveAspectRatio="none">
                                <path d="M0,64 C42,58 78,24 120,36 C164,48 188,72 226,62 C262,52 290,18 336,28 C372,36 398,58 440,46" class="command-doc-area"></path>
                                <path d="M0,64 C42,58 78,24 120,36 C164,48 188,72 226,62 C262,52 290,18 336,28 C372,36 398,58 440,46" class="command-doc-line"></path>
                            </svg>
                        </div>
                        
                        <div class="command-progress-wrap">
                            <div class="command-progress-head">
                                <span>Completion Flow</span>
                                <strong style="color: #16a34a;">{{ $completionRate }}% Confirmed</strong>
                            </div>
                            <div class="command-progress-bar">
                                <span class="segment segment--success" style="width: {{ max(0, $docCompletedWidth) }}%;"></span>
                                <span class="segment segment--warning" style="width: {{ max(0, $docPendingWidth) }}%;"></span>
                                <span class="segment segment--danger" style="width: {{ max(0, $docRejectedWidth) }}%;"></span>
                            </div>
                        </div>

                        <div class="command-status-chips">
                            <span class="status-pill status-pill--success">Completed: {{ $documentsCompleted }}</span>
                            <span class="status-pill status-pill--warning">Pending: {{ $documentsPending }}</span>
                            <span class="status-pill status-pill--danger">Rejected: {{ $documentsRejected }}</span>
                            <span class="status-pill status-pill--neutral">Draft: {{ $documentsDraft }}</span>
                        </div>
                    </div>
                </article>

                <!-- Blockchain Summary -->
                <article class="dash-card card-blockchain">
                    <div class="dash-card-header">
                        <div class="card-title-group">
                            <h3><i class="fas fa-shield-alt" style="color: #10b981; margin-right: 0.4rem;"></i> Blockchain Ledger</h3>
                            <span class="card-meta">Security Ledger Status</span>
                        </div>
                        <a href="{{ route('admin.blockchain.index') }}" class="card-action-link">View Ledger &rarr;</a>
                    </div>
                    <div class="dash-card-body ledger-body">
                        <div class="command-ledger-layout">
                            <div class="command-radial" style="--cc-radial: {{ $blockchainConfirmedRate }};">
                                <div class="command-radial-inner">
                                    <strong>{{ $blockchainConfirmedRate }}%</strong>
                                    <span>Confirmed</span>
                                </div>
                            </div>

                            <div class="metric-stats">
                                <div class="stat-row">
                                    <span class="stat-label">Total Submissions</span>
                                    <span class="stat-value" style="font-weight: 800;">{{ number_format($blockchainConfirmed + $blockchainPending + $blockchainFailed) }}</span>
                                </div>
                                <div class="stat-row">
                                    <span class="stat-label">Confirmed</span>
                                    <span class="stat-value stat-success">{{ $blockchainConfirmed }}</span>
                                </div>
                                <div class="stat-row">
                                    <span class="stat-label">Pending</span>
                                    <span class="stat-value stat-warning">{{ $blockchainPending }}</span>
                                </div>
                                <div class="stat-row">
                                    <span class="stat-label">Failed</span>
                                    <span class="stat-value stat-danger">{{ $blockchainFailed }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            </section>

            <section class="dashboard-grid-tri" aria-label="Operational Queues">
                <!-- Users -->
                <article class="dash-card">
                    <div class="dash-card-header">
                        <div class="card-title-group">
                            <h3><i class="fas fa-users" style="color: #6366f1; margin-right: 0.4rem;"></i> User Activity</h3>
                            <span class="card-meta"><span class="status-dot" style="background:#16a34a; margin-right:0.2rem;"></span> {{ $stats['users']['active'] }} Online Now</span>
                        </div>
                        <a href="{{ route('admin.users.index') }}" class="card-action-link">View All &rarr;</a>
                    </div>
                    <div class="dash-card-body content-center-flex">
                        <div class="dashboard-big-number">
                            {{ number_format($stats['users']['total']) }}
                            <span class="bg-label">Total Users</span>
                        </div>
                        
                        <div class="command-role-chips">
                            <span>Staff {{ $stats['users']['by_role']['staff'] }}</span>
                            <span>Supervisor {{ $stats['users']['by_role']['supervisor'] }}</span>
                            <span>Admin {{ $stats['users']['by_role']['admin'] }}</span>
                        </div>
                    </div>
                </article>

                <!-- Corrections Queue -->
                <article class="dash-card">
                    <div class="dash-card-header">
                        <div class="card-title-group">
                            <h3><i class="fas fa-clipboard-check" style="color: #f59e0b; margin-right: 0.4rem;"></i> Corrections Queue</h3>
                            <span class="card-meta">{{ number_format($stats['corrections']['total']) }} Total Logs</span>
                        </div>
                        <a href="{{ route('admin.corrections.index') }}" class="card-action-link">Open Queue &rarr;</a>
                    </div>
                    <div class="dash-card-body">
                        <div class="metric-stats">
                            <div class="stat-row">
                                <span class="stat-label">Pending Approval</span>
                                <span class="stat-value stat-warning">{{ $stats['corrections']['pending'] }}</span>
                            </div>
                            <div class="stat-row">
                                <span class="stat-label">Approved</span>
                                <span class="stat-value stat-success">{{ $stats['corrections']['approved'] }}</span>
                            </div>
                            <div class="stat-row">
                                <span class="stat-label">Rejected</span>
                                <span class="stat-value stat-danger">{{ $stats['corrections']['rejected'] }}</span>
                            </div>
                            @if($escalationCount > 0)
                            <div class="stat-row">
                                <span class="stat-label">Escalated</span>
                                <span class="stat-value stat-alert">{{ $escalationCount }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </article>

                <!-- Attention Required -->
                <article class="dash-card card-alerts">
                    <div class="dash-card-header">
                        <div class="card-title-group">
                            <h3 style="color: #b91c1c;"><i class="fas fa-exclamation-triangle" style="margin-right: 0.4rem;"></i> Attention Required</h3>
                            <span class="card-meta">Action items</span>
                        </div>
                        <a href="{{ route('admin.escalations.index') }}" class="card-action-link">Open Queue &rarr;</a>
                    </div>
                    <div class="dash-card-body" style="padding-top: 0.75rem;">
                        <div class="command-alert-list">
                            @forelse($alerts as $alert)
                                <a href="{{ $alert['href'] }}" class="command-alert-item">
                                    <span class="command-alert-count">{{ $alert['count'] }}</span>
                                    <span class="command-alert-label">{{ $alert['label'] }}</span>
                                    <i class="fas fa-chevron-right" style="margin-left: auto; color: #9ca3af; font-size: 0.7rem;"></i>
                                </a>
                            @empty
                                <div class="command-alert-empty">
                                    <i class="fas fa-check-circle" style="color: #10b981; margin-right: 0.4rem;"></i> No active alerts right now.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </article>
            </section>

            <!-- Quick Actions -->
            <section class="dashboard-quick-actions" aria-label="Quick Actions">
                <article class="dash-card card-actions" style="margin-bottom: 2rem;">
                     <div class="dash-card-header" style="background: #f8fafc;">
                         <div class="card-title-group">
                             <h3><i class="fas fa-bolt" style="color: #8b5cf6; margin-right: 0.4rem;"></i> Quick Actions &amp; Navigation</h3>
                         </div>
                         <span class="card-meta" style="display:flex; align-items:center; gap:0.5rem;">
                             <i class="fas fa-clock"></i> Last Backup: Not available | 
                             <a href="{{ route('admin.system-health.index') }}" style="color: #2563eb; text-decoration: none; font-weight: 600;">System Health &rarr;</a>
                         </span>
                     </div>
                     <div class="dash-card-body" style="padding: 1rem 1.25rem;">
                         <div class="actions-grid">
                             <a href="{{ route('admin.users.create') }}" class="quick-action-btn">
                                 <i class="fas fa-user-plus"></i>
                                 <span>Create User</span>
                             </a>
                             <a href="{{ route('admin.documents.index') }}" class="quick-action-btn">
                                 <i class="fas fa-file-alt"></i>
                                 <span>Documents</span>
                             </a>
                             <a href="{{ route('admin.corrections.index', ['status' => 'pending']) }}" class="quick-action-btn">
                                 <i class="fas fa-edit"></i>
                                 <span>Corrections</span>
                             </a>
                             <a href="{{ route('admin.audit-logs.index') }}" class="quick-action-btn">
                                 <i class="fas fa-clipboard-list"></i>
                                 <span>Audit Logs</span>
                             </a>
                             <a href="{{ route('admin.analytics.index') }}" class="quick-action-btn">
                                 <i class="fas fa-chart-line"></i>
                                 <span>Analytics</span>
                             </a>
                             <a href="{{ route('admin.blockchain.index') }}" class="quick-action-btn">
                                 <i class="fas fa-link"></i>
                                 <span>Blockchain</span>
                             </a>
                         </div>
                     </div>
                </article>
            </section>

        </div>
    </div>

@endsection

@push('styles')
<style>
    /* Dashboard Modern Redesign */
    .admin-dashboard-modern {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 0.5rem;
    }

    .header-user-info {
        background: #fff;
        padding: 0.6rem 1rem;
        border-radius: 0.5rem;
        border: 1px solid #e5e7eb;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        display: flex;
        flex-direction: column;
        align-items: flex-end;
    }

    .user-greeting {
        font-size: 0.95rem;
        font-weight: 700;
        color: #111827;
    }

    .user-date {
        font-size: 0.75rem;
        color: #6b7280;
        font-weight: 500;
        margin-top: 0.15rem;
    }

    /* Priority Metrics Card Row */
    .priority-metrics-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1.25rem;
        margin-bottom: 1.5rem;
    }

    .priority-card {
        background: #ffffff;
        border-radius: 0.75rem;
        padding: 1.2rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        border: 1px solid #e2e8f0;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .priority-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.04);
    }

    .priority-icon {
        width: 3.5rem;
        height: 3.5rem;
        border-radius: 0.65rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        flex-shrink: 0;
    }

    .icon-blue { background: #eff6ff; color: #3b82f6; border: 1px solid #bfdbfe; }
    .icon-orange { background: #fff7ed; color: #f97316; border: 1px solid #fed7aa; }
    .icon-green { background: #f0fdf4; color: #22c55e; border: 1px solid #bbf7d0; }
    .icon-neutral { background: #f8fafc; color: #475569; border: 1px solid #e2e8f0; }

    .priority-info {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .priority-label {
        font-size: 0.75rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .priority-value {
        font-size: 1.7rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1;
    }

    .status-online {
        color: #16a34a;
        font-size: 1.25rem;
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }

    .status-dot {
        width: 0.55rem;
        height: 0.55rem;
        border-radius: 50%;
        background-color: #22c55e;
        display: inline-block;
    }

    .pulse {
        animation: pulse-dot 2s infinite;
    }

    @keyframes pulse-dot {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(34, 197, 94, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
    }

    /* Grids */
    .dashboard-grid-main {
        display: grid;
        grid-template-columns: 3fr 2fr;
        gap: 1.25rem;
        margin-bottom: 1.25rem;
    }

    .dashboard-grid-tri {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.25rem;
        margin-bottom: 1.25rem;
    }

    @media (max-width: 1024px) {
        .dashboard-grid-main,
        .dashboard-grid-tri {
            grid-template-columns: 1fr;
        }
    }

    /* Cards shared */
    .dash-card {
        background: #ffffff;
        border-radius: 0.75rem;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .dash-card-header {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f8fafc;
    }

    .card-title-group h3 {
        font-size: 0.95rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
        display: flex;
        align-items: center;
    }
    
    .card-title-group .card-meta {
        display: block;
        margin-top: 0.2rem;
    }

    .card-meta {
        font-size: 0.72rem;
        color: #64748b;
        font-weight: 600;
    }

    .card-action-link {
        font-size: 0.75rem;
        font-weight: 600;
        color: #2563eb;
        text-decoration: none;
        background: #eff6ff;
        padding: 0.35rem 0.75rem;
        border-radius: 999px;
        transition: all 0.2s;
        border: 1px solid #dbeafe;
    }

    .card-action-link:hover {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .dash-card-body {
        padding: 1.25rem;
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    /* Document Flow */
    .command-doc-chart {
        height: 80px;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        background: linear-gradient(180deg, rgba(14, 165, 233, 0.08) 0%, rgba(14, 165, 233, 0.02) 100%);
        overflow: hidden;
    }

    .command-doc-chart svg { width: 100%; height: 100%; }
    .command-doc-line { fill: none; stroke: #0ea5e9; stroke-width: 2.6; stroke-linecap: round; }
    .command-doc-area { fill: rgba(14, 165, 233, 0.15); stroke: none; }

    .command-progress-wrap {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .command-progress-head {
        display: flex;
        justify-content: space-between;
        color: #64748b;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .command-progress-bar {
        display: flex;
        height: 0.6rem;
        border-radius: 999px;
        overflow: hidden;
        background: #e2e8f0;
    }

    .segment { height: 100%; }
    .segment--success { background: #22c55e; }
    .segment--warning { background: #f59e0b; }
    .segment--danger { background: #ef4444; }

    .command-status-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 0.25rem;
    }

    .status-pill {
        font-size: 0.72rem;
        font-weight: 700;
        border-radius: 0.35rem;
        padding: 0.35rem 0.65rem;
        border: 1px solid transparent;
    }

    .status-pill--success { background: #f0fdf4; color: #16a34a; border-color: #bbf7d0; }
    .status-pill--danger { background: #fef2f2; color: #dc2626; border-color: #fecaca; }
    .status-pill--warning { background: #fffbeb; color: #d97706; border-color: #fde68a; }
    .status-pill--neutral { background: #f8fafc; color: #475569; border-color: #e2e8f0; }

    /* Blockchain */
    .command-ledger-layout {
        display: grid;
        grid-template-columns: 120px 1fr;
        gap: 1.5rem;
        align-items: center;
    }
    
    @media (max-width: 600px) {
        .command-ledger-layout { grid-template-columns: 1fr; justify-items: center; }
    }

    .command-radial {
        --cc-radial-size: 110px;
        width: var(--cc-radial-size);
        height: var(--cc-radial-size);
        border-radius: 50%;
        background: conic-gradient(#10b981 calc(var(--cc-radial) * 1%), #e2e8f0 0);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .command-radial-inner {
        width: calc(var(--cc-radial-size) - 16px);
        height: calc(var(--cc-radial-size) - 16px);
        border-radius: 50%;
        background: #ffffff;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
    }

    .command-radial-inner strong {
        font-size: 1.4rem;
        line-height: 1;
        color: #0f172a;
        font-weight: 800;
    }

    .command-radial-inner span {
        font-size: 0.65rem;
        color: #64748b;
        text-transform: uppercase;
        font-weight: 700;
        margin-top: 0.1rem;
    }

    .metric-stats {
        display: flex;
        flex-direction: column;
        gap: 0.65rem;
        width: 100%;
    }

    .stat-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-bottom: 0.4rem;
        border-bottom: 1px dashed #e2e8f0;
    }
    
    .stat-row:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .stat-label {
        font-size: 0.8rem;
        color: #475569;
        font-weight: 600;
    }

    .stat-value {
        font-size: 0.85rem;
        font-weight: 700;
        color: #0f172a;
    }

    .stat-success { color: #16a34a; }
    .stat-warning { color: #d97706; }
    .stat-danger { color: #dc2626; }
    .stat-alert { color: #b91c1c; background: #fee2e2; padding: 0.1rem 0.4rem; border-radius: 0.25rem; }

    /* Users / Body Center elements */
    .content-center-flex {
        align-items: center;
        justify-content: center;
        text-align: center;
    }

    .dashboard-big-number {
        font-size: 2.5rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
    }
    
    .dashboard-big-number .bg-label {
        font-size: 0.8rem;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .command-role-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem;
        justify-content: center;
    }

    .command-role-chips span {
        font-size: 0.72rem;
        font-weight: 600;
        padding: 0.35rem 0.65rem;
        border-radius: 999px;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        color: #475569;
    }

    /* Alerts */
    .command-alert-list {
        display: flex;
        flex-direction: column;
        gap: 0.6rem;
    }

    .command-alert-item {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        text-decoration: none;
        padding: 0.65rem 0.75rem;
        background: #f8fafc;
        transition: all 0.2s;
    }

    .command-alert-item:hover {
        border-color: #fbcfe8;
        background: #fdf2f8;
    }

    .command-alert-item:hover .command-alert-label {
        color: #be185d;
    }

    .command-alert-count {
        min-width: 26px;
        height: 26px;
        border-radius: 0.4rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 800;
        color: #991b1b;
        background: #fee2e2;
    }

    .command-alert-label {
        color: #334155;
        font-size: 0.8rem;
        font-weight: 600;
        transition: color 0.2s;
    }

    .command-alert-empty {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
        border-radius: 0.5rem;
        border: 1px dashed #cbd5e1;
        color: #64748b;
        font-size: 0.85rem;
        background: #f8fafc;
    }

    /* Quick Actions */
    .actions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
        gap: 0.85rem;
    }

    .quick-action-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.6rem;
        padding: 1.1rem 0.5rem;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        color: #475569;
        text-decoration: none;
        transition: all 0.2s;
        font-size: 0.85rem;
        font-weight: 600;
        box-shadow: 0 1px 2px rgba(0,0,0,0.02);
    }

    .quick-action-btn i {
        font-size: 1.3rem;
        color: #6366f1;
        transition: transform 0.2s;
    }

    .quick-action-btn:hover {
        border-color: #a5b4fc;
        background: #e0e7ff;
        color: #3730a3;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }

    .quick-action-btn:hover i {
        transform: translateY(-2px);
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const dateTimeElement = document.getElementById('admin-dashboard-local-datetime');
        if (!dateTimeElement) {
            return;
        }

        const dateFormatter = new Intl.DateTimeFormat(undefined, {
            weekday: 'long',
            month: 'long',
            day: 'numeric',
            year: 'numeric'
        });

        const timeFormatter = new Intl.DateTimeFormat(undefined, {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });

        const updateLocalDateTime = function () {
            const now = new Date();
            dateTimeElement.textContent = dateFormatter.format(now) + ' • ' + timeFormatter.format(now);
        };

        updateLocalDateTime();
        setInterval(updateLocalDateTime, 30000);
    });
</script>
@endpush
