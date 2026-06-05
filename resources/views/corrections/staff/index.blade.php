@extends('layouts.staff')

@push('styles')
<style>
    .crq-page {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }

    .crq-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 0.25rem;
    }

    .crq-header h1 {
        font-size: 1.625rem;
        line-height: 1.2;
        font-weight: 700;
        color: #0f172a;
    }

    .crq-header p {
        margin-top: 0.325rem;
        font-size: 0.875rem;
        color: #64748b;
    }

    .crq-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.375rem;
        min-height: 2.5rem;
        padding: 0.6rem 1rem;
        border-radius: 0.5rem;
        border: 1px solid transparent;
        font-size: 0.875rem;
        font-weight: 600;
        transition: all 0.2s ease;
    }

    .crq-btn-primary {
        background: #2563eb;
        border-color: #2563eb;
        color: #ffffff;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.06);
    }

    .crq-btn-primary:hover {
        background: #1d4ed8;
        border-color: #1d4ed8;
    }

    .crq-btn-soft {
        background: #ffffff;
        border-color: #cbd5e1;
        color: #334155;
    }

    .crq-btn-soft:hover {
        background: #f8fafc;
        color: #0f172a;
    }

    .crq-btn-danger {
        background: #fff1f2;
        border-color: #fecdd3;
        color: #be123c;
    }

    .crq-btn-danger:hover {
        background: #ffe4e6;
        border-color: #fda4af;
        color: #9f1239;
    }

    .crq-section-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 0.875rem;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
    }

    .crq-stat-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.875rem;
    }

    .crq-stat-card {
        position: relative;
        padding: 1rem;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        background: #ffffff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
        overflow: hidden;
    }

    .crq-stat-card::before {
        content: "";
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        border-radius: 0.75rem 0 0 0.75rem;
    }

    .crq-stat-card--pending::before { background: #f59e0b; }
    .crq-stat-card--approved::before { background: #16a34a; }
    .crq-stat-card--rejected::before { background: #dc2626; }
    .crq-stat-card--cancelled::before { background: #64748b; }

    .crq-stat-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.35rem;
    }

    .crq-stat-label {
        font-size: 0.6875rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #64748b;
        font-weight: 700;
    }

    .crq-stat-icon {
        width: 1.85rem;
        height: 1.85rem;
        border-radius: 0.5rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
    }

    .crq-stat-icon--pending { background: #fef3c7; color: #b45309; }
    .crq-stat-icon--approved { background: #dcfce7; color: #15803d; }
    .crq-stat-icon--rejected { background: #fee2e2; color: #b91c1c; }
    .crq-stat-icon--cancelled { background: #e2e8f0; color: #475569; }

    .crq-stat-value {
        font-size: 2rem;
        line-height: 1;
        font-weight: 800;
        color: #0f172a;
    }

    .crq-filter-wrap {
        padding: 1rem;
    }

    .crq-filter-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: end;
        gap: 0.875rem;
    }

    .crq-filter-field {
        flex: 1 1 220px;
        min-width: 170px;
    }

    .crq-filter-field label {
        display: block;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #64748b;
        margin-bottom: 0.35rem;
    }

    .crq-filter-field select {
        width: 100%;
        min-height: 2.5rem;
        border-radius: 0.5rem;
        border: 1px solid #cbd5e1;
        box-shadow: none;
        color: #0f172a;
        font-size: 0.875rem;
        padding: 0.5rem 0.75rem;
    }

    .crq-filter-field select:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    .crq-filter-actions {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-left: auto;
        padding-left: 0.25rem;
    }

    .crq-list-card {
        overflow: hidden;
    }

    .crq-row {
        padding: 1rem 1.1rem;
        transition: background-color 0.2s ease;
    }

    .crq-row:hover {
        background: #f8fafc;
    }

    .crq-row-inner {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 0.9rem;
        align-items: center;
    }

    .crq-meta {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.45rem;
        min-height: 1.5rem;
    }

    .crq-status {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.2rem 0.5rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 700;
        line-height: 1;
        border: 1px solid transparent;
    }

    .crq-status--pending { background: #fffbeb; border-color: #fde68a; color: #92400e; }
    .crq-status--approved { background: #f0fdf4; border-color: #bbf7d0; color: #166534; }
    .crq-status--rejected { background: #fef2f2; border-color: #fecaca; color: #991b1b; }
    .crq-status--cancelled { background: #f1f5f9; border-color: #cbd5e1; color: #475569; }

    .crq-time {
        font-size: 0.75rem;
        color: #64748b;
    }

    .crq-doc-line {
        display: flex;
        flex-wrap: wrap;
        align-items: baseline;
        gap: 0.4rem;
        margin-bottom: 0.35rem;
    }

    .crq-doc-type {
        font-size: 0.95rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.2;
    }

    .crq-doc-id {
        font-size: 0.75rem;
        font-weight: 600;
        color: #64748b;
    }

    .crq-change {
        display: inline-flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.4rem;
        font-size: 0.8125rem;
        color: #475569;
    }

    .crq-change-label {
        font-weight: 700;
        color: #334155;
    }

    .crq-change-old {
        color: #b91c1c;
        text-decoration: line-through;
    }

    .crq-change-new {
        color: #166534;
        font-weight: 700;
    }

    .crq-reason {
        margin-top: 0.5rem;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        border: 1px solid #fecaca;
        background: #fef2f2;
        color: #b91c1c;
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 0.45rem;
    }

    .crq-actions {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        justify-self: end;
        padding-left: 0.5rem;
    }

    .crq-pagination {
        padding: 0.875rem 1.1rem;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
    }

    @media (min-width: 768px) {
        .crq-stat-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
    }

    @media (max-width: 768px) {
        .crq-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .crq-filter-actions {
            width: 100%;
            margin-left: 0;
            padding-left: 0;
        }

        .crq-row-inner {
            grid-template-columns: 1fr;
            gap: 0.75rem;
        }

        .crq-actions {
            justify-self: start;
            padding-left: 0;
            flex-wrap: wrap;
        }
    }
</style>
@endpush

@section('content')
<div class="staff-page-shell py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="crq-page">
        
        <!-- Page Header -->
        <div class="crq-header">
                <div>
                    <h1 class="staff-page-title">My Correction Requests</h1>
                    <p class="staff-page-subtitle">View and manage your document correction requests</p>
                </div>
                <a href="{{ route('corrections.requests.create') }}" 
                   class="crq-btn crq-btn-primary">
                    <i class="fas fa-plus"></i>
                    New Request
                </a>
        </div>

        <!-- Statistics Cards -->
        <div class="crq-stat-grid">
            <div class="crq-stat-card crq-stat-card--pending">
                <div class="crq-stat-top">
                    <span class="crq-stat-label">Pending</span>
                    <span class="crq-stat-icon crq-stat-icon--pending"><i class="fas fa-clock"></i></span>
                </div>
                <p class="crq-stat-value">{{ $pendingCount ?? 0 }}</p>
            </div>
            <div class="crq-stat-card crq-stat-card--approved">
                <div class="crq-stat-top">
                    <span class="crq-stat-label">Approved</span>
                    <span class="crq-stat-icon crq-stat-icon--approved"><i class="fas fa-check-circle"></i></span>
                </div>
                <p class="crq-stat-value">{{ $approvedCount ?? 0 }}</p>
            </div>
            <div class="crq-stat-card crq-stat-card--rejected">
                <div class="crq-stat-top">
                    <span class="crq-stat-label">Rejected</span>
                    <span class="crq-stat-icon crq-stat-icon--rejected"><i class="fas fa-times-circle"></i></span>
                </div>
                <p class="crq-stat-value">{{ $rejectedCount ?? 0 }}</p>
            </div>
            <div class="crq-stat-card crq-stat-card--cancelled">
                <div class="crq-stat-top">
                    <span class="crq-stat-label">Cancelled</span>
                    <span class="crq-stat-icon crq-stat-icon--cancelled"><i class="fas fa-minus-circle"></i></span>
                </div>
                <p class="crq-stat-value">{{ $cancelledCount ?? 0 }}</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="crq-section-card">
            <div class="crq-filter-wrap">
                <form action="{{ route('corrections.requests.index') }}" method="GET" class="crq-filter-toolbar">
                    
                    <!-- Status Filter -->
                    <div class="crq-filter-field">
                        <label for="status">Status</label>
                        <select name="status" id="status" 
                                class="text-gray-900">
                            <option value="">All Statuses</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>

                    <!-- Document Type Filter -->
                    <div class="crq-filter-field">
                        <label for="document_type">Document Type</label>
                        <select name="document_type" id="document_type" 
                                class="text-gray-900">
                            <option value="">All Types</option>
                            <option value="birth_certificate" {{ request('document_type') == 'birth_certificate' ? 'selected' : '' }}>Birth Certificate</option>
                            <option value="marriage_certificate" {{ request('document_type') == 'marriage_certificate' ? 'selected' : '' }}>Marriage Certificate</option>
                            <option value="death_certificate" {{ request('document_type') == 'death_certificate' ? 'selected' : '' }}>Death Certificate</option>
                        </select>
                    </div>

                    <!-- Filter Buttons -->
                    <div class="crq-filter-actions">
                        <button type="submit" class="crq-btn crq-btn-primary">
                            <i class="fas fa-filter"></i>
                            Filter
                        </button>
                        <a href="{{ route('corrections.requests.index') }}" class="crq-btn crq-btn-soft">
                            <i class="fas fa-times"></i>
                            Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Requests List -->
        @if($requests->count() > 0)
            <div class="crq-section-card crq-list-card">
                <div class="divide-y divide-gray-200">
                    @foreach($requests as $request)
                        <div class="crq-row">
                            <div class="crq-row-inner">
                                <div class="flex-1">
                                    <!-- Status & Time -->
                                    <div class="crq-meta">
                                        @if($request->status === 'pending')
                                            <span class="crq-status crq-status--pending">
                                                <i class="fas fa-clock mr-1"></i> Pending
                                            </span>
                                        @elseif($request->status === 'approved')
                                            <span class="crq-status crq-status--approved">
                                                <i class="fas fa-check-circle mr-1"></i> Approved
                                            </span>
                                        @elseif($request->status === 'rejected')
                                            <span class="crq-status crq-status--rejected">
                                                <i class="fas fa-times-circle mr-1"></i> Rejected
                                            </span>
                                        @else
                                            <span class="crq-status crq-status--cancelled">
                                                <i class="fas fa-ban mr-1"></i> Cancelled
                                            </span>
                                        @endif
                                        <span class="crq-time">
                                            {{ $request->requested_at->diffForHumans() }}
                                        </span>
                                    </div>

                                    <!-- Document Info -->
                                    <div class="crq-doc-line">
                                        <h3 class="crq-doc-type">{{ $request->scan->document_type_name ?? 'Document' }}</h3>
                                        <span class="crq-doc-id">#{{ $request->scan->document_id ?? 'N/A' }}</span>
                                    </div>

                                    <!-- Correction Details -->
                                    <div class="crq-change">
                                        <span class="crq-change-label">{{ $request->field_display_name }}:</span>
                                        <span class="crq-change-old">{{ Str::limit($request->current_value ?: '(empty)', 20) }}</span>
                                        <i class="fas fa-arrow-right mx-2 text-gray-400 text-xs"></i>
                                        <span class="crq-change-new">{{ Str::limit($request->proposed_value, 20) }}</span>
                                    </div>

                                    <!-- Rejection Reason Preview -->
                                    @if($request->status === 'rejected' && $request->rejection_reason)
                                        <div class="crq-reason">
                                            <i class="fas fa-exclamation-circle mr-1"></i>
                                            {{ Str::limit($request->rejection_reason, 50) }}
                                        </div>
                                    @endif
                                </div>
                                
                                <!-- Action Buttons -->
                                <div class="crq-actions">
                                    <a href="{{ route('corrections.requests.show', $request) }}" 
                                       class="crq-btn crq-btn-primary">
                                        <i class="fas fa-eye mr-1"></i>
                                        View
                                    </a>
                                    @if($request->status === 'pending')
                                        <form action="{{ route('corrections.requests.cancel', $request) }}" method="POST" 
                                              onsubmit="return confirm('Are you sure you want to cancel this request?');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="crq-btn crq-btn-danger">
                                                <i class="fas fa-times mr-1"></i>
                                                Cancel
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                @if($requests->hasPages())
                    <div class="crq-pagination">
                        {{ $requests->withQueryString()->links() }}
                    </div>
                @endif
            </div>
        @else
            <!-- Empty State -->
            <div class="crq-section-card p-12 text-center">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-gray-100 rounded-full mb-4">
                    <i class="fas fa-edit text-gray-400 text-3xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">No Correction Requests</h3>
                <p class="text-gray-500 mb-6">
                    @if(request()->hasAny(['status', 'document_type']))
                        No requests match your current filters.
                    @else
                        You haven't submitted any correction requests yet.
                    @endif
                </p>
                @if(request()->hasAny(['status', 'document_type']))
                    <a href="{{ route('corrections.requests.index') }}" 
                       class="crq-btn crq-btn-soft mr-2">
                        <i class="fas fa-times mr-2"></i>
                        Clear Filters
                    </a>
                @endif
                <a href="{{ route('corrections.requests.create') }}" 
                   class="crq-btn crq-btn-primary">
                    <i class="fas fa-plus mr-2"></i>
                    New Correction Request
                </a>
            </div>
        @endif

        </div>
    </div>
</div>
@endsection