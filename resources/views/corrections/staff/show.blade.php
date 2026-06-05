@extends('layouts.staff')

@push('styles')
<style>
    .crd-page {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .crd-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 0.875rem;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
        overflow: hidden;
    }

    .crd-header {
        padding: 1.05rem 1.25rem;
    }

    .crd-breadcrumb {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        font-size: 0.75rem;
        color: #64748b;
        margin-bottom: 0.625rem;
    }

    .crd-breadcrumb a {
        color: #475569;
        font-weight: 600;
    }

    .crd-breadcrumb a:hover {
        color: #2563eb;
    }

    .crd-header-main {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 0.75rem;
    }

    .crd-title {
        font-size: 1.55rem;
        line-height: 1.2;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
    }

    .crd-submeta {
        display: inline-flex;
        align-items: center;
        margin-top: 0.35rem;
        padding: 0.2rem 0.55rem;
        border-radius: 9999px;
        border: 1px solid #dbe5f0;
        background: #f8fafc;
        color: #475569;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .crd-status {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.4rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.8125rem;
        font-weight: 700;
        border: 1px solid transparent;
        line-height: 1;
        white-space: nowrap;
    }

    .crd-status--pending {
        background: #fffbeb;
        color: #92400e;
        border-color: #fde68a;
    }

    .crd-status--approved {
        background: #ecfdf3;
        color: #166534;
        border-color: #bbf7d0;
    }

    .crd-status--rejected {
        background: #fef2f2;
        color: #991b1b;
        border-color: #fecaca;
    }

    .crd-status--cancelled {
        background: #f1f5f9;
        color: #475569;
        border-color: #cbd5e1;
    }

    .crd-alert {
        border-radius: 0.75rem;
        border: 1px solid;
        padding: 0.9rem 1rem;
    }

    .crd-alert-row {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
    }

    .crd-alert-row i {
        margin-top: 0.125rem;
    }

    .crd-alert-success {
        background: #f0fdf4;
        border-color: #bbf7d0;
    }

    .crd-alert-danger {
        background: #fef2f2;
        border-color: #fecaca;
    }

    .crd-alert-approved {
        background: #ecfdf3;
        border-color: #bbf7d0;
    }

    .crd-section-head {
        padding: 0.8rem 1rem;
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc;
    }

    .crd-section-head-inner {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .crd-section-head h2 {
        margin: 0;
        font-size: 1rem;
        line-height: 1.2;
        font-weight: 700;
        color: #0f172a;
    }

    .crd-section-body {
        padding: 1rem;
    }

    .crd-meta-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.65rem;
    }

    .crd-meta-item {
        border: 1px solid #e2e8f0;
        border-radius: 0.6rem;
        padding: 0.6rem 0.7rem;
        background: #ffffff;
        min-height: 68px;
    }

    .crd-meta-label {
        display: block;
        font-size: 0.6875rem;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        font-weight: 700;
        color: #64748b;
        margin-bottom: 0.35rem;
    }

    .crd-meta-value {
        font-size: 0.9rem;
        color: #0f172a;
        font-weight: 600;
        line-height: 1.3;
        word-break: break-word;
    }

    .crd-link-row {
        margin-top: 0.8rem;
        padding-top: 0.7rem;
        border-top: 1px solid #e2e8f0;
    }

    .crd-link {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.8125rem;
        font-weight: 600;
        color: #2563eb;
    }

    .crd-link:hover {
        color: #1d4ed8;
    }

    .crd-field-row {
        margin-bottom: 0.8rem;
    }

    .crd-field-label {
        font-size: 0.6875rem;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        font-weight: 700;
        color: #64748b;
        margin-bottom: 0.3rem;
    }

    .crd-field-value {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        border: 1px solid #cbd5e1;
        background: #f8fafc;
        border-radius: 0.55rem;
        padding: 0.4rem 0.6rem;
        font-size: 0.875rem;
        font-weight: 700;
        color: #334155;
    }

    .crd-compare {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.65rem;
        margin-bottom: 0.8rem;
    }

    .crd-compare-item {
        border: 1px solid #e2e8f0;
        border-radius: 0.65rem;
        padding: 0.7rem 0.8rem;
    }

    .crd-compare-item--before {
        background: #fff7f7;
        border-color: #fecaca;
    }

    .crd-compare-item--after {
        background: #f4fdf6;
        border-color: #bbf7d0;
    }

    .crd-compare-title {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.75rem;
        font-weight: 700;
        color: #475569;
        margin-bottom: 0.35rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }

    .crd-compare-value {
        font-size: 0.95rem;
        color: #0f172a;
        line-height: 1.35;
        word-break: break-word;
    }

    .crd-compare-item--before .crd-compare-value {
        text-decoration: line-through;
        color: #991b1b;
    }

    .crd-compare-item--after .crd-compare-value {
        color: #166534;
        font-weight: 700;
    }

    .crd-reason-box {
        border: 1px solid #dbe5f0;
        background: #f8fafc;
        border-radius: 0.65rem;
        padding: 0.75rem 0.8rem;
        font-size: 0.875rem;
        line-height: 1.45;
        color: #334155;
        white-space: pre-wrap;
    }

    .crd-attachment-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #334155;
        border-radius: 0.55rem;
        padding: 0.45rem 0.7rem;
        font-size: 0.8125rem;
        font-weight: 600;
    }

    .crd-attachment-btn:hover {
        background: #f8fafc;
        color: #0f172a;
    }

    .crd-timeline {
        list-style: none;
        margin: 0;
        padding: 0;
        position: relative;
    }

    .crd-timeline-item {
        position: relative;
        display: grid;
        grid-template-columns: 28px 1fr;
        gap: 0.6rem;
        padding-bottom: 0.65rem;
    }

    .crd-timeline-item:last-child {
        padding-bottom: 0;
    }

    .crd-timeline-item:not(:last-child)::after {
        content: "";
        position: absolute;
        left: 13px;
        top: 27px;
        bottom: 0;
        width: 2px;
        background: #e2e8f0;
    }

    .crd-timeline-icon {
        width: 28px;
        height: 28px;
        border-radius: 9999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        border: 1px solid transparent;
        z-index: 1;
        background: #ffffff;
    }

    .crd-timeline-content {
        padding-top: 0.1rem;
    }

    .crd-timeline-title {
        font-size: 0.875rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 0.125rem;
        line-height: 1.25;
    }

    .crd-timeline-time {
        font-size: 0.75rem;
        color: #64748b;
        margin: 0;
    }

    .crd-actionbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        background: #f8fafc;
    }

    .crd-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        min-height: 2.35rem;
        border-radius: 0.5rem;
        border: 1px solid transparent;
        font-size: 0.875rem;
        font-weight: 600;
        padding: 0.5rem 0.85rem;
        transition: all 0.2s ease;
    }

    .crd-btn-back {
        background: #ffffff;
        border-color: #cbd5e1;
        color: #334155;
    }

    .crd-btn-back:hover {
        background: #f8fafc;
        color: #0f172a;
    }

    .crd-btn-cancel {
        background: #fff1f2;
        border-color: #fecdd3;
        color: #be123c;
    }

    .crd-btn-cancel:hover {
        background: #ffe4e6;
        border-color: #fda4af;
        color: #9f1239;
    }

    @media (max-width: 768px) {
        .crd-header-main {
            flex-direction: column;
            align-items: flex-start;
        }

        .crd-meta-grid,
        .crd-compare {
            grid-template-columns: 1fr;
        }

        .crd-actionbar {
            flex-direction: column;
            align-items: stretch;
        }

        .crd-btn,
        .crd-actionbar form {
            width: 100%;
        }

        .crd-actionbar form button {
            width: 100%;
        }
    }
</style>
@endpush

@section('content')
<div class="py-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="crd-page">
        
        <!-- Page Header -->
        <div class="crd-card crd-header">
            <nav class="crd-breadcrumb">
                <a href="{{ route('corrections.requests.index') }}">My Requests</a>
                <i class="fas fa-chevron-right text-[10px]"></i>
                <span class="font-medium text-slate-800">Request #{{ $request->id }}</span>
            </nav>
            <div class="crd-header-main">
                <div>
                    <h1 class="crd-title">Correction Request Details</h1>
                    <span class="crd-submeta">Request #{{ $request->id }}</span>
                </div>
                @if($request->status === 'pending')
                    <span class="crd-status crd-status--pending">
                        <i class="fas fa-clock"></i>
                        Pending Review
                    </span>
                @elseif($request->status === 'approved')
                    <span class="crd-status crd-status--approved">
                        <i class="fas fa-check-circle"></i>
                        Approved
                    </span>
                @elseif($request->status === 'rejected')
                    <span class="crd-status crd-status--rejected">
                        <i class="fas fa-times-circle"></i>
                        Rejected
                    </span>
                @else
                    <span class="crd-status crd-status--cancelled">
                        <i class="fas fa-minus-circle"></i>
                        Cancelled
                    </span>
                @endif
            </div>
        </div>

        <!-- Success/Info Messages -->
        @if(session('success'))
            <div class="crd-alert crd-alert-success">
                <div class="crd-alert-row">
                    <i class="fas fa-check-circle text-green-600"></i>
                    <p class="text-sm text-gray-900 leading-relaxed">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        <!-- Rejection Alert -->
        @if($request->status === 'rejected' && $request->rejection_reason)
            <div class="crd-alert crd-alert-danger">
                <div class="crd-alert-row">
                    <i class="fas fa-exclamation-circle text-red-600"></i>
                    <div>
                        <h3 class="text-base font-semibold text-red-900 mb-1">Request Rejected</h3>
                        <p class="text-sm text-red-700 leading-relaxed">{{ $request->rejection_reason }}</p>
                        <p class="text-xs text-red-500 mt-1.5">
                            Rejected by {{ $request->reviewer->name ?? 'Supervisor' }} on {{ $request->reviewed_at?->format('M d, Y g:i A') }}
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Approved & Blockchain Info -->
        @if($request->status === 'approved' && $request->correctionRecord)
            <div class="crd-alert crd-alert-approved">
                <div class="crd-alert-row">
                    <i class="fas fa-check-circle text-green-600"></i>
                    <div class="flex-1">
                        <h3 class="text-base font-semibold text-green-900 mb-1">Correction Approved & Anchored</h3>
                        <p class="text-sm text-green-700 mb-2">
                            Your correction has been approved and recorded on the blockchain.
                        </p>
                        <div class="bg-white/50 rounded-lg p-3">
                            <p class="text-xs text-green-600 mb-1">Blockchain Transaction Hash:</p>
                            <code class="block text-xs text-green-800 font-mono truncate" title="{{ $request->correctionRecord->correction_tx_hash }}">
                                {{ $request->correctionRecord->correction_tx_hash }}
                            </code>
                        </div>
                        <p class="text-xs text-green-500 mt-1.5">
                            Approved by {{ $request->reviewer->name ?? 'Supervisor' }} on {{ $request->reviewed_at?->format('M d, Y g:i A') }}
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <div class="space-y-4">
            
            <!-- Document Information -->
            <div class="crd-card">
                <div class="crd-section-head">
                    <div class="crd-section-head-inner">
                        <i class="fas fa-file-alt text-blue-600"></i>
                        <h2>Document Information</h2>
                    </div>
                </div>
                <div class="crd-section-body">
                    <dl class="crd-meta-grid">
                        <div class="crd-meta-item">
                            <dt class="crd-meta-label">Document Type</dt>
                            <dd class="crd-meta-value">{{ $request->scan->document_type_name ?? 'N/A' }}</dd>
                        </div>
                        <div class="crd-meta-item">
                            <dt class="crd-meta-label">Document ID</dt>
                            <dd class="crd-meta-value">{{ $request->scan->document_id ?? 'N/A' }}</dd>
                        </div>
                        <div class="crd-meta-item">
                            <dt class="crd-meta-label">Registry Number</dt>
                            <dd class="crd-meta-value">{{ $request->scan->registry_number ?? 'N/A' }}</dd>
                        </div>
                        <div class="crd-meta-item">
                            <dt class="crd-meta-label">Original Scan Date</dt>
                            <dd class="crd-meta-value">{{ $request->scan->created_at?->format('M d, Y') ?? 'N/A' }}</dd>
                        </div>
                    </dl>
                    <div class="crd-link-row">
                        <a href="{{ route('staff.scans.show', $request->scan) }}" 
                           target="_blank"
                           class="crd-link">
                            <i class="fas fa-external-link-alt"></i>
                            View Full Document
                        </a>
                    </div>
                </div>
            </div>

            <!-- Correction Details -->
            <div class="crd-card">
                <div class="crd-section-head">
                    <div class="crd-section-head-inner">
                        <i class="fas fa-edit text-blue-600"></i>
                        <h2>Correction Details</h2>
                    </div>
                </div>
                <div class="crd-section-body">
                    <!-- Field -->
                    <div class="crd-field-row">
                        <dt class="crd-field-label">Field Being Corrected</dt>
                        <dd>
                            <span class="crd-field-value">
                                {{ $request->field_display_name }}
                            </span>
                        </dd>
                    </div>

                    <!-- Value Comparison -->
                    <div class="crd-compare">
                        <div class="crd-compare-item crd-compare-item--before">
                            <dt class="crd-compare-title">
                                <i class="fas fa-times-circle text-red-500"></i> Original Value
                            </dt>
                            <dd class="crd-compare-value">
                                {{ $request->current_value ?: '(empty)' }}
                            </dd>
                        </div>
                        <div class="crd-compare-item crd-compare-item--after">
                            <dt class="crd-compare-title">
                                <i class="fas fa-check-circle text-green-500"></i> Corrected Value
                            </dt>
                            <dd class="crd-compare-value">
                                {{ $request->proposed_value }}
                            </dd>
                        </div>
                    </div>

                    <!-- Reason -->
                    <div class="mb-3">
                        <dt class="crd-field-label">Reason for Correction</dt>
                        <dd class="crd-reason-box">
                            {{ $request->reason }}
                        </dd>
                    </div>

                    <!-- Supporting Document -->
                    @if($request->supporting_document_path)
                        <div>
                            <dt class="crd-field-label">Supporting Document</dt>
                            <dd>
                                <a href="{{ Storage::url($request->supporting_document_path) }}" 
                                   target="_blank"
                                   class="crd-attachment-btn">
                                    <i class="fas fa-paperclip"></i>
                                    View Attachment
                                    <i class="fas fa-external-link-alt text-xs"></i>
                                </a>
                            </dd>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Timeline -->
            <div class="crd-card">
                <div class="crd-section-head">
                    <div class="crd-section-head-inner">
                        <i class="fas fa-history text-blue-600"></i>
                        <h2>Timeline</h2>
                    </div>
                </div>
                <div class="crd-section-body">
                    <div>
                        <ul class="crd-timeline">
                            <!-- Submitted -->
                            <li class="crd-timeline-item">
                                <span class="crd-timeline-icon bg-blue-100 border-blue-200">
                                            <i class="fas fa-paper-plane text-blue-600 text-sm"></i>
                                </span>
                                <div class="crd-timeline-content">
                                    <p class="crd-timeline-title">Request Submitted</p>
                                    <p class="crd-timeline-time">{{ $request->requested_at?->format('M d, Y g:i A') }}</p>
                                </div>
                            </li>

                            <!-- Review/Cancel -->
                            @if($request->reviewed_at)
                                <li class="crd-timeline-item">
                                            @if($request->status === 'approved')
                                                <span class="crd-timeline-icon bg-green-100 border-green-200">
                                                    <i class="fas fa-check text-green-600 text-sm"></i>
                                                </span>
                                            @else
                                                <span class="crd-timeline-icon bg-red-100 border-red-200">
                                                    <i class="fas fa-times text-red-600 text-sm"></i>
                                                </span>
                                            @endif
                                    <div class="crd-timeline-content">
                                        <p class="crd-timeline-title">
                                            {{ $request->status === 'approved' ? 'Approved' : 'Rejected' }} by {{ $request->reviewer->name ?? 'Supervisor' }}
                                        </p>
                                        <p class="crd-timeline-time">{{ $request->reviewed_at?->format('M d, Y g:i A') }}</p>
                                    </div>
                                </li>
                            @elseif($request->status === 'cancelled')
                                <li class="crd-timeline-item">
                                            <span class="crd-timeline-icon bg-gray-100 border-gray-200">
                                                <i class="fas fa-ban text-gray-600 text-sm"></i>
                                            </span>
                                    <div class="crd-timeline-content">
                                        <p class="crd-timeline-title">Cancelled by You</p>
                                        <p class="crd-timeline-time">{{ $request->updated_at?->format('M d, Y g:i A') }}</p>
                                    </div>
                                </li>
                            @endif

                            <!-- Blockchain -->
                            @if($request->correctionRecord)
                                <li class="crd-timeline-item">
                                            <span class="crd-timeline-icon bg-emerald-100 border-emerald-200">
                                                <i class="fas fa-cube text-emerald-600 text-sm"></i>
                                            </span>
                                    <div class="crd-timeline-content">
                                        <p class="crd-timeline-title">Anchored to Blockchain</p>
                                        <p class="crd-timeline-time">{{ $request->correctionRecord->anchored_at?->format('M d, Y g:i A') }}</p>
                                    </div>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="crd-actionbar">
                <a href="{{ route('corrections.requests.index') }}" 
                   class="crd-btn crd-btn-back">
                    <i class="fas fa-arrow-left"></i>
                    Back to My Requests
                </a>
                
                @if($request->status === 'pending')
                    <form action="{{ route('corrections.requests.cancel', $request) }}" method="POST"
                          onsubmit="return confirm('Are you sure you want to cancel this request?');">
                        @csrf
                        @method('PATCH')
                        <button type="submit" 
                                class="crd-btn crd-btn-cancel">
                            <i class="fas fa-times"></i>
                            Cancel Request
                        </button>
                    </form>
                @endif
            </div>
        </div>
        </div>

    </div>
</div>
@endsection