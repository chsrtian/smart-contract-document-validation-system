@extends('layouts.supervisor')

@section('content')
<link rel="stylesheet" href="{{ asset('css/supervisor-theme.css') }}">
<link rel="stylesheet" href="{{ asset('css/supervisor-dashboard.css') }}">

<div class="sv-dashboard">
    
   
    <div class="sv-dashboard-header">
        <h1>Supervisor Dashboard</h1>
        <p>Overview of correction requests and approval workflow</p>
    </div>

    <!-- Utility Links (top-right) -->
    <div class="sv-util-links">
        <a href="{{ route('corrections.approval.audit-log') }}" class="sv-util-link">
            <i class="fas fa-clipboard-list"></i>
            <span>Audit Log</span>
        </a>
        <a href="{{ route('corrections.approval.history') }}" class="sv-util-link">
            <i class="fas fa-history"></i>
            <span>Review History</span>
        </a>
    </div>

    <!-- Statistics Cards (Clickable) -->
    <div class="sv-stats-grid">
        
        <!-- Pending Card -->
        <a href="{{ route('corrections.approval.pending') }}" class="sv-stat-card pending">
            <div class="sv-stat-content">
                <div class="sv-stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="sv-stat-info">
                    <div class="sv-stat-label">Pending Requests</div>
                    <div class="sv-stat-value">{{ $pendingCount }}</div>
                </div>
            </div>
            <div class="sv-stat-footer">
                <span class="sv-stat-footer-text">
                    View all pending <i class="fas fa-arrow-right"></i>
                </span>
            </div>
        </a>

        <!-- Approved Card -->
        <a href="{{ route('corrections.approval.history', ['status' => 'approved']) }}" class="sv-stat-card approved">
            <div class="sv-stat-content">
                <div class="sv-stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="sv-stat-info">
                    <div class="sv-stat-label">Approved Today</div>
                    <div class="sv-stat-value">{{ $approvedToday }}</div>
                </div>
            </div>
            <div class="sv-stat-footer">
                <span class="sv-stat-footer-text">
                    View approved <i class="fas fa-arrow-right"></i>
                </span>
            </div>
        </a>

        <!-- Rejected Card -->
        <a href="{{ route('corrections.approval.history', ['status' => 'rejected']) }}" class="sv-stat-card rejected">
            <div class="sv-stat-content">
                <div class="sv-stat-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="sv-stat-info">
                    <div class="sv-stat-label">Rejected Today</div>
                    <div class="sv-stat-value">{{ $rejectedToday }}</div>
                </div>
            </div>
            <div class="sv-stat-footer">
                <span class="sv-stat-footer-text">
                    View rejected <i class="fas fa-arrow-right"></i>
                </span>
            </div>
        </a>
    </div>

    <!-- Recent Pending Requests -->
    <div class="sv-requests-panel">
        <div class="sv-requests-header">
            <h2>
                <i class="fas fa-inbox"></i>
                Recent Pending Requests
            </h2>
            @if($recentRequests->count())
                <a href="{{ route('corrections.approval.pending') }}" class="sv-btn-viewall">
                    <span>View All</span>
                    <i class="fas fa-arrow-right"></i>
                </a>
            @endif
        </div>

        <div class="sv-requests-list">
            @forelse($recentRequests as $req)
                <div class="sv-request-row">
                    <div class="sv-request-info">
                        <div class="sv-request-top">
                            <span class="sv-request-tag pending">
                                <i class="fas fa-clock"></i> Pending
                            </span>
                            <span class="sv-request-time">{{ $req->requested_at->diffForHumans() }}</span>
                        </div>
                        <div class="sv-request-title">
                            {{ $req->scan->document_type_name ?? 'Document' }} - {{ $req->scan->document_id ?? 'N/A' }}
                        </div>
                        <div class="sv-request-change">
                            <span class="sv-request-old">{{ Str::limit($req->current_value, 25) ?: '(empty)' }}</span>
                            <i class="fas fa-long-arrow-alt-right sv-request-arrow"></i>
                            <span class="sv-request-new">{{ Str::limit($req->proposed_value, 25) }}</span>
                        </div>
                        <div class="sv-request-meta">
                            <i class="fas fa-user"></i>
                            Requested by: {{ $req->requester->name ?? 'Unknown' }}
                        </div>
                    </div>
                    <a href="{{ route('corrections.approval.review', $req) }}" class="sv-btn-review">
                        <i class="fas fa-eye"></i>
                        <span>Review</span>
                    </a>
                </div>
            @empty
                <div class="sv-empty-state">
                    <i class="fas fa-check-double"></i>
                    <p>All caught up! No pending requests.</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Workflow Reminder (Collapsible) -->
    <div class="sv-workflow-box" id="workflowBox">
        <div class="sv-workflow-header" onclick="this.parentElement.classList.toggle('open')">
            <h3>
                <i class="fas fa-info-circle"></i>
                Correction Workflow Reminder
            </h3>
            <i class="fas fa-chevron-down sv-workflow-toggle"></i>
        </div>
        <div class="sv-workflow-body">
            <ul>
                <li>Original blockchain transactions are <strong>never</strong> modified</li>
                <li>Approved corrections create a <strong>new</strong> blockchain transaction</li>
                <li>Each correction references the original transaction hash</li>
                <li>Rejections require a reason (minimum 10 characters)</li>
            </ul>
        </div>
    </div>
</div>
@endsection