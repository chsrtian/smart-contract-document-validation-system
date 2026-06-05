@extends('layouts.supervisor')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/supervisor-history.css') }}">
@endpush

@section('content')
<div class="history-container">
    
    <!-- Page Header -->
    <div class="history-header">
        <nav class="history-breadcrumb">
            <a href="{{ route('corrections.approval.dashboard') }}">Dashboard</a>
            <i class="fas fa-chevron-right breadcrumb-separator"></i>
            <span class="breadcrumb-current">Review History</span>
        </nav>
        
        <div class="history-title-row">
            <div class="history-title-content">
                <h1>Review History</h1>
                <p>View all previously reviewed correction requests</p>
            </div>
            
            <!-- Summary Cards (Clickable Filters) -->
            <div class="history-summary-cards">
                <a href="{{ route('corrections.approval.history', ['status' => 'approved']) }}" 
                   class="summary-card summary-card--approved {{ request('status') === 'approved' ? 'active' : '' }}">
                    <div class="summary-card-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <span class="summary-card-count">{{ $approvedCount ?? 0 }}</span>
                    <span class="summary-card-label">Approved</span>
                </a>
                
                <a href="{{ route('corrections.approval.history', ['status' => 'rejected']) }}" 
                   class="summary-card summary-card--rejected {{ request('status') === 'rejected' ? 'active' : '' }}">
                    <div class="summary-card-icon">
                        <i class="fas fa-times"></i>
                    </div>
                    <span class="summary-card-count">{{ $rejectedCount ?? 0 }}</span>
                    <span class="summary-card-label">Rejected</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="history-filters">
        <form action="{{ route('corrections.approval.history') }}" method="GET" class="filter-form">
            
            <!-- Status Filter -->
            <div class="filter-group">
                <label for="status" class="filter-label">Status</label>
                <select name="status" id="status" class="filter-select">
                    <option value="">All Statuses</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>

            <!-- Document Type Filter -->
            <div class="filter-group">
                <label for="document_type" class="filter-label">Document Type</label>
                <select name="document_type" id="document_type" class="filter-select">
                    <option value="">All Types</option>
                    <option value="birth_certificate" {{ request('document_type') == 'birth_certificate' ? 'selected' : '' }}>Birth Certificate</option>
                    <option value="marriage_certificate" {{ request('document_type') == 'marriage_certificate' ? 'selected' : '' }}>Marriage Certificate</option>
                    <option value="death_certificate" {{ request('document_type') == 'death_certificate' ? 'selected' : '' }}>Death Certificate</option>
                </select>
            </div>

            <!-- Date Range -->
            <div class="filter-group filter-group--date">
                <label for="date_from" class="filter-label">Start Date</label>
                <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}"
                       class="filter-input" placeholder="Start Date">
            </div>

            <div class="filter-group filter-group--date">
                <label for="date_to" class="filter-label">End Date</label>
                <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}"
                       class="filter-input" placeholder="End Date">
            </div>

            <!-- Filter Actions -->
            <div class="filter-actions">
                <button type="submit" class="filter-btn filter-btn--apply">
                    <i class="fas fa-search"></i>
                    Apply Filters
                </button>
                @if(request()->hasAny(['status', 'document_type', 'date_from', 'date_to']))
                    <a href="{{ route('corrections.approval.history') }}" class="filter-btn filter-btn--clear">
                        <i class="fas fa-trash-alt"></i>
                        Clear
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- History List -->
    @if($requests->count() > 0)
        <div class="history-table-wrapper">
            
            <!-- Table Header (Desktop) -->
            <div class="history-table-header">
                <span>Document</span>
                <span>Correction</span>
                <span>Requester</span>
                <span>Status</span>
                <span>Reviewed</span>
            </div>

            <div class="history-table-body">
                @foreach($requests as $request)
                    <div class="history-row">
                        
                        <!-- Mobile Layout -->
                        <div class="history-row-mobile">
                            <div class="history-row-mobile-header">
                                <span class="doc-title">{{ $request->scan->document_type_name ?? 'Document' }}</span>
                                @if($request->status === 'approved')
                                    <span class="status-badge status-badge--approved">
                                        <i class="fas fa-check-circle"></i> Approved
                                    </span>
                                @else
                                    <span class="status-badge status-badge--rejected">
                                        <i class="fas fa-times-circle"></i> Rejected
                                    </span>
                                @endif
                            </div>
                            <div class="history-row-mobile-change">
                                <strong>{{ $request->field_display_name }}:</strong>
                                <span class="old-value">{{ Str::limit($request->current_value, 15) }}</span>
                                <i class="fas fa-arrow-right change-arrow"></i>
                                <span class="new-value">{{ Str::limit($request->proposed_value, 15) }}</span>
                            </div>
                            <div class="history-row-mobile-footer">
                                <span>By: {{ $request->requester->name ?? 'Unknown' }}</span>
                                <span>{{ $request->reviewed_at?->format('M d, Y') }}</span>
                            </div>
                        </div>

                        <!-- Desktop Layout -->
                        <div class="history-row-desktop">
                            <!-- Document -->
                            <div class="col-document">
                                <div class="doc-type">{{ $request->scan->document_type_name ?? 'Document' }}</div>
                                <div class="doc-id">{{ $request->scan->document_id ?? 'N/A' }}</div>
                                <div class="doc-registry">Reg: {{ $request->scan->registry_number ?? 'N/A' }}</div>
                            </div>

                            <!-- Correction -->
                            <div class="col-correction">
                                <div class="field-name">{{ $request->field_display_name }}</div>
                                <div class="change-values">
                                    <span class="old-value">{{ Str::limit($request->current_value ?: '(empty)', 20) }}</span>
                                    <i class="fas fa-arrow-right change-arrow"></i>
                                    <span class="new-value">{{ Str::limit($request->proposed_value, 20) }}</span>
                                </div>
                            </div>

                            <!-- Requester -->
                            <div class="col-requester">
                                <div class="requester-name">{{ $request->requester->name ?? 'Unknown' }}</div>
                                <div class="request-date">{{ $request->requested_at?->format('M d, Y') }}</div>
                            </div>

                            <!-- Status -->
                            <div class="col-status">
                                @if($request->status === 'approved')
                                    <span class="status-badge status-badge--approved">
                                        <i class="fas fa-check-circle"></i> Approved
                                    </span>
                                    @if($request->correctionRecord)
                                        <span class="anchored-indicator">
                                            <i class="fas fa-link"></i> Anchored
                                        </span>
                                    @endif
                                @else
                                    <span class="status-badge status-badge--rejected">
                                        <i class="fas fa-times-circle"></i> Rejected
                                    </span>
                                    @if($request->rejection_reason)
                                        <span class="rejection-reason" title="{{ $request->rejection_reason }}">
                                            {{ Str::limit($request->rejection_reason, 25) }}
                                        </span>
                                    @endif
                                @endif
                            </div>

                            <!-- Reviewed -->
                            <div class="col-reviewed">
                                <div class="review-info">
                                    <div class="review-date">{{ $request->reviewed_at?->format('M d, Y') }}</div>
                                    <div class="review-time">{{ $request->reviewed_at?->format('g:i A') }}</div>
                                    <div class="reviewer">By: {{ $request->reviewer->name ?? 'System' }}</div>
                                </div>
                                <a href="{{ route('corrections.approval.show', $request) }}" class="view-btn">
                                    <i class="fas fa-eye"></i>
                                    View
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            @if($requests->hasPages())
                <div class="history-pagination">
                    {{ $requests->withQueryString()->links() }}
                </div>
            @endif
        </div>
    @else
        <!-- Empty State -->
        <div class="history-empty-state">
            <div class="empty-state-illustration">
                <!-- Inline SVG Illustration - Clean and theme-aware -->
                <svg viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Background circle -->
                    <circle cx="100" cy="100" r="80" class="empty-illustration-primary" opacity="0.3"/>
                    
                    <!-- Folder base -->
                    <path d="M50 70 L50 150 Q50 160 60 160 L140 160 Q150 160 150 150 L150 70 Z" 
                          class="empty-illustration-secondary" stroke-width="2"/>
                    
                    <!-- Folder tab -->
                    <path d="M50 70 L50 55 Q50 50 55 50 L85 50 L95 60 L95 70 Z" 
                          class="empty-illustration-secondary" stroke-width="2"/>
                    
                    <!-- Document 1 -->
                    <rect x="65" y="85" width="50" height="60" rx="4" class="empty-illustration-secondary" stroke-width="2"/>
                    <line x1="75" y1="100" x2="105" y2="100" stroke="var(--sv-border-normal)" stroke-width="2" stroke-linecap="round"/>
                    <line x1="75" y1="112" x2="95" y2="112" stroke="var(--sv-border-normal)" stroke-width="2" stroke-linecap="round"/>
                    <line x1="75" y1="124" x2="100" y2="124" stroke="var(--sv-border-normal)" stroke-width="2" stroke-linecap="round"/>
                    
                    <!-- Magnifying glass -->
                    <circle cx="135" cy="125" r="20" fill="none" stroke="var(--sv-primary)" stroke-width="4"/>
                    <line x1="150" y1="140" x2="165" y2="155" stroke="var(--sv-primary)" stroke-width="4" stroke-linecap="round"/>
                    
                    <!-- Sparkle elements -->
                    <circle cx="45" cy="45" r="3" class="empty-illustration-accent"/>
                    <circle cx="160" cy="55" r="2" class="empty-illustration-accent"/>
                    <circle cx="170" cy="90" r="2.5" class="empty-illustration-accent"/>
                </svg>
            </div>
            
            <div class="empty-state-content">
                @if(request()->hasAny(['status', 'document_type', 'date_from', 'date_to']))
                    <h3>No Records Found</h3>
                    <p>No correction requests match your current filter criteria. Try adjusting your filters or clearing them to see all records.</p>
                    <a href="{{ route('corrections.approval.history') }}" class="empty-state-action">
                        <i class="fas fa-times"></i>
                        Clear All Filters
                    </a>
                @else
                    <h3>No Review History Yet</h3>
                    <p>Once you start reviewing correction requests, they will appear here. Check the Pending queue for requests waiting to be reviewed.</p>
                    <a href="{{ route('corrections.approval.pending') }}" class="empty-state-action">
                        <i class="fas fa-inbox"></i>
                        View Pending Requests
                    </a>
                @endif
            </div>
        </div>
    @endif

</div>
@endsection