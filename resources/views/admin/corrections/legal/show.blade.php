@extends('layouts.admin')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin-legal-corrections.css') }}">
@endpush

@section('content')
<div class="admin-container alc-legal-view">
    <div class="admin-page-header">
        <div>
            <h1 class="admin-page-title">Petition {{ $petition->petition_number }}</h1>
            <p class="admin-page-subtitle">{{ $petition->petition_type_label }} &mdash; {{ $petition->legal_basis }}</p>
        </div>
        <a href="{{ route('admin.corrections.legal.index') }}" class="alc-btn alc-btn-secondary alc-btn-sm" style="background:#ffffff; border-color:#cbd5e1; color:#334155; padding: 0.45rem 0.85rem; border-radius: 999px;">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>

    <div class="admin-content alc-legal-content alc-layout">
        <div class="alc-main-column">
            <!-- Petition Info -->
            <div class="alc-detail-section alc-panel">
                <h3><i class="fas fa-info-circle"></i> Petition Information</h3>
                <div class="alc-detail-grid alc-grid-2">
                    <dl class="alc-detail-item">
                        <dt>Petition Number</dt>
                        <dd class="alc-monospace-value">{{ $petition->petition_number }}</dd>
                    </dl>
                    <dl class="alc-detail-item">
                        <dt>Legal Basis</dt>
                        <dd>{{ $petition->legal_basis }}</dd>
                    </dl>
                    <dl class="alc-detail-item">
                        <dt>Petition Type</dt>
                        <dd>{{ $petition->petition_type_label }}</dd>
                    </dl>
                    <dl class="alc-detail-item">
                        <dt>Status</dt>
                        <dd>
                            <span class="alc-badge alc-status-pill {{ $petition->status }}">
                                @if($petition->status === 'draft')
                                    <i class="fas fa-file-alt"></i>
                                @elseif($petition->status === 'pending_approval')
                                    <i class="fas fa-clock"></i>
                                @elseif($petition->status === 'approved')
                                    <i class="fas fa-check-circle"></i>
                                @elseif($petition->status === 'rejected')
                                    <i class="fas fa-times-circle"></i>
                                @elseif($petition->status === 'forwarded_to_psa')
                                    <i class="fas fa-share"></i>
                                @endif
                                {{ $petition->status_label }}
                            </span>
                        </dd>
                    </dl>
                    <dl class="alc-detail-item">
                        <dt>Filed By (Staff)</dt>
                        <dd>{{ $petition->creator->name ?? 'N/A' }}</dd>
                    </dl>
                    <dl class="alc-detail-item">
                        <dt>Date Filed</dt>
                        <dd>{{ $petition->created_at->format('F d, Y h:i A') }}</dd>
                    </dl>
                </div>
            </div>

            <!-- Document Info -->
            <div class="alc-detail-section alc-panel">
                <h3><i class="fas fa-file-alt"></i> Civil Registry Document</h3>
                @if($petition->scan)
                <div class="alc-detail-grid alc-grid-2">
                    <dl class="alc-detail-item">
                        <dt>Document Type</dt>
                        <dd>{{ ucwords(str_replace('_', ' ', $petition->scan->document_type)) }}</dd>
                    </dl>
                    <dl class="alc-detail-item">
                        <dt>Document ID</dt>
                        <dd>
                            <a href="{{ route('admin.documents.show', $petition->scan) }}" class="admin-link alc-doc-link" title="View Document Details">
                                <i class="fas fa-file-alt"></i>
                                {{ $petition->scan->document_id ?? $petition->scan->id }}
                            </a>
                        </dd>
                    </dl>
                    <dl class="alc-detail-item alc-span-full">
                        <dt>Title</dt>
                        <dd>{{ $petition->scan->title ?? 'N/A' }}</dd>
                    </dl>
                    <dl class="alc-detail-item">
                        <dt>Registry Number</dt>
                        <dd>{{ $petition->scan->registry_number ?? 'N/A' }}</dd>
                    </dl>
                </div>
                @else
                <p style="color: #9ca3af; font-size: 0.875rem;">Document information not available.</p>
                @endif
            </div>

            <!-- Petitioner Info -->
            <div class="alc-detail-section alc-panel">
                <h3><i class="fas fa-user"></i> Petitioner Information</h3>
                <div class="alc-detail-grid alc-grid-2">
                    <dl class="alc-detail-item">
                        <dt>Full Name</dt>
                        <dd>{{ $petition->petitioner_name }}</dd>
                    </dl>
                    <dl class="alc-detail-item">
                        <dt>Relationship</dt>
                        <dd>{{ \App\Models\LegalCorrectionPetition::RELATIONSHIP_TYPES[$petition->petitioner_relationship] ?? $petition->petitioner_relationship }}</dd>
                    </dl>
                    @if($petition->petitioner_address)
                    <dl class="alc-detail-item alc-span-full">
                        <dt>Address</dt>
                        <dd>{{ $petition->petitioner_address }}</dd>
                    </dl>
                    @endif
                </div>
            </div>

            <!-- Field Changes -->
            <div class="alc-detail-section alc-panel">
                <h3><i class="fas fa-pen-fancy"></i> Requested Corrections ({{ $petition->fieldChanges->count() }})</h3>
                @if($petition->fieldChanges->count() > 0)
                <div class="alc-table-wrap">
                    <table class="alc-changes-table alc-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Field</th>
                                <th>Current Value</th>
                                <th>Proposed Value</th>
                                <th>Justification</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($petition->fieldChanges as $i => $change)
                            <tr>
                                <td style="color:#64748b; font-weight: 600;">{{ $i + 1 }}</td>
                                <td class="alc-strong">{{ $change->field_label }}</td>
                                <td>
                                    <div class="alc-val-box alc-val-old">
                                        {{ $change->current_value ?: '(blank)' }}
                                    </div>
                                </td>
                                <td>
                                    <div class="alc-val-box alc-val-new">
                                        {{ $change->proposed_value }}
                                    </div>
                                </td>
                                <td class="alc-muted" style="max-width: 200px; white-space: normal; line-height:1.4;">{{ $change->justification ?: '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p style="color: #9ca3af; font-size: 0.875rem;">No field corrections specified.</p>
                @endif
            </div>

            <!-- Reason -->
            @if($petition->reason)
            <div class="alc-detail-section alc-panel">
                <h3><i class="fas fa-align-left"></i> Grounds / Reason</h3>
                <p class="alc-text-block alc-reason-block">{{ $petition->reason }}</p>
            </div>
            @endif

            <!-- Attachments -->
            @if($petition->attachments->count() > 0)
            <div class="alc-detail-section alc-panel">
                <h3><i class="fas fa-paperclip"></i> Supporting Documents ({{ $petition->attachments->count() }})</h3>
                <div class="alc-attachments-list">
                    @foreach($petition->attachments as $attachment)
                    <div class="alc-attachment-item">
                        <i class="fas fa-file-pdf alc-attachment-icon"></i>
                        <div class="alc-attachment-details">
                            <div class="alc-attachment-name">{{ $attachment->original_filename }}</div>
                            <div class="alc-attachment-meta">
                                {{ $attachment->file_type_label }} &bull; {{ $attachment->formatted_file_size }}
                                @if($attachment->description)
                                    <br><span class="alc-attachment-desc">{{ $attachment->description }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Affidavit Info -->
            @if($petition->affidavit_file_path)
            <div class="alc-detail-section alc-panel">
                <h3><i class="fas fa-file-signature"></i> Joint Affidavit</h3>
                <p class="alc-text-block">Affidavit document provided by petitioner.</p>
            </div>
            @endif

            <!-- Admin Notes -->
            @if($petition->admin_notes)
            <div class="alc-detail-section alc-panel">
                <h3><i class="fas fa-sticky-note"></i> Admin Notes</h3>
                <p class="alc-text-block alc-reason-block" style="background:#f8fafc; border-color:#e2e8f0;">{{ $petition->admin_notes }}</p>
            </div>
            @endif
        </div>

        <aside class="alc-side-column">
            <!-- Forward to PSA (if approved) -->
            @if($petition->canBeForwarded())
            <div class="alc-detail-section alc-panel alc-panel-forward">
                <h3 class="alc-forward-title"><i class="fas fa-share-square"></i> Forward to PSA</h3>
                <p class="alc-text-block" style="margin-bottom: 0.8rem; font-size: 0.8rem;">
                    This petition has been approved. You may now simulate forwarding it to the Philippine Statistics Authority (PSA) for central registry update.
                </p>
                <form method="POST" action="{{ route('admin.corrections.legal.forward-psa', $petition) }}">
                    @csrf
                    <button type="submit" class="alc-btn alc-btn-primary alc-btn-forward" id="alc-forward-psa-btn" style="width: 100%;">
                        <i class="fas fa-share"></i> Forward to PSA
                    </button>
                </form>
            </div>
            @endif

            <!-- Admin Action Panels (if pending) -->
            @if($petition->canBeApproved())
            <div class="alc-detail-section alc-panel alc-panel-decision">
                <h3><i class="fas fa-clipboard-check"></i> Admin Decision</h3>
                <div class="alc-action-row">
                    <button type="button" id="alc-show-approve" class="alc-btn alc-btn-approve">
                        <i class="fas fa-check-circle"></i> Approve
                    </button>
                    <button type="button" id="alc-show-reject" class="alc-btn alc-btn-reject">
                        <i class="fas fa-times-circle"></i> Reject
                    </button>
                </div>

                <!-- Approve Form (hidden by default) -->
                <div id="alc-approve-form" class="alc-action-form" style="display: none;">
                    <h4 class="alc-action-title alc-action-title-approve">
                        <i class="fas fa-check"></i> Approve Petition
                    </h4>
                    <form method="POST" action="{{ route('admin.corrections.legal.approve', $petition) }}">
                        @csrf
                        <div class="alc-form-group">
                            <label for="admin_notes" class="alc-form-label">Admin Notes (Optional)</label>
                            <textarea name="admin_notes" id="admin_notes" rows="3" placeholder="Add any notes regarding this approval..."
                                class="alc-form-input"></textarea>
                        </div>
                        <p class="alc-help-text">
                            <i class="fas fa-info-circle"></i> Approving will auto-generate a marginal annotation on the document.
                        </p>
                        <div class="alc-form-actions">
                            <button type="submit" class="alc-btn alc-btn-approve" id="alc-confirm-approve" style="flex:1;">
                                <i class="fas fa-check"></i> Confirm
                            </button>
                            <button type="button" id="alc-cancel-approve" class="alc-btn alc-btn-secondary alc-btn-sm" style="flex:1;">Cancel</button>
                        </div>
                    </form>
                </div>

                <!-- Reject Form (hidden by default) -->
                <div id="alc-reject-form" class="alc-action-form" style="display: none;">
                    <h4 class="alc-action-title alc-action-title-reject">
                        <i class="fas fa-times"></i> Reject Petition
                    </h4>
                    <form method="POST" action="{{ route('admin.corrections.legal.reject', $petition) }}">
                        @csrf
                        <div class="alc-form-group">
                            <label for="rejection_reason" class="alc-form-label">
                                Reason <span style="color: #ef4444;">*</span>
                            </label>
                            <textarea name="rejection_reason" id="rejection_reason" rows="3" required minlength="10"
                                placeholder="Provide a detailed reason..."
                                class="alc-form-input"></textarea>
                        </div>
                        <div class="alc-form-actions">
                            <button type="submit" class="alc-btn alc-btn-reject" id="alc-confirm-reject" style="flex:1;">
                                <i class="fas fa-times"></i> Confirm
                            </button>
                            <button type="button" id="alc-cancel-reject" class="alc-btn alc-btn-secondary alc-btn-sm" style="flex:1;">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
            @endif

            <!-- Marginal Annotation (if generated) -->
            @if($petition->annotations->count() > 0)
            <div class="alc-detail-section alc-panel alc-panel-annotation">
                <h3 class="alc-annotation-title"><i class="fas fa-stamp"></i> Marginal Annotation</h3>
                @foreach($petition->annotations as $annotation)
                <div class="alc-annotation-preview">{{ $annotation->annotation_text }}</div>
                <div class="alc-annotation-meta">
                    <dl class="alc-meta-list">
                        <dt>Decision No.:</dt><dd>{{ $annotation->lcro_decision_number }}</dd>
                        <dt>Date:</dt><dd>{{ $annotation->annotation_date ? $annotation->annotation_date->format('F d, Y') : 'N/A' }}</dd>
                        <dt>Legal Ref:</dt><dd>{{ $annotation->legal_reference }}</dd>
                        <dt>Annotator:</dt><dd>{{ $annotation->annotator->name ?? 'N/A' }}</dd>
                    </dl>
                </div>
                @endforeach
            </div>
            @endif

            <!-- Approval Info -->
            @if($petition->approver)
            <div class="alc-detail-section alc-panel alc-panel-approval">
                <h3 class="alc-approval-title"><i class="fas fa-user-check"></i> Approval Record</h3>
                <div class="alc-detail-grid">
                    <dl class="alc-detail-item">
                        <dt>Approved By</dt>
                        <dd>{{ $petition->approver->name }}</dd>
                    </dl>
                    <dl class="alc-detail-item">
                        <dt>Approved At</dt>
                        <dd>{{ $petition->approved_at ? $petition->approved_at->format('F d, Y h:i A') : 'N/A' }}</dd>
                    </dl>
                </div>
            </div>
            @endif

            <!-- Rejection Info -->
            @if($petition->isRejected())
            <div class="alc-detail-section alc-panel alc-panel-rejection">
                <h3 class="alc-rejection-title"><i class="fas fa-ban"></i> Rejection Details</h3>
                <div class="alc-detail-grid">
                    <dl class="alc-detail-item">
                        <dt>Rejected By</dt>
                        <dd>{{ $petition->rejector->name ?? 'N/A' }}</dd>
                    </dl>
                    <dl class="alc-detail-item">
                        <dt>Rejected At</dt>
                        <dd>{{ $petition->rejected_at ? $petition->rejected_at->format('F d, Y h:i A') : 'N/A' }}</dd>
                    </dl>
                    <dl class="alc-detail-item">
                        <dt>Reason</dt>
                        <dd style="color: #dc2626;">{{ $petition->rejection_reason }}</dd>
                    </dl>
                </div>
            </div>
            @endif

            <!-- PSA Forwarding Log -->
            @if($petition->psaForwardingLog)
            <div class="alc-detail-section alc-panel alc-panel-forward">
                <h3 class="alc-forward-title"><i class="fas fa-paper-plane"></i> PSA Forwarding Log</h3>
                <div class="alc-psa-block">{{ $petition->psaForwardingLog->transmittal_details }}</div>
                <div class="alc-detail-grid alc-grid-top">
                    <dl class="alc-detail-item">
                        <dt>Reference</dt>
                        <dd class="alc-monospace-value" style="font-size:0.8rem;">{{ $petition->psaForwardingLog->forwarding_reference }}</dd>
                    </dl>
                    <dl class="alc-detail-item">
                        <dt>Status</dt>
                        <dd><span class="alc-badge {{ $petition->psaForwardingLog->forwarding_status }}">{{ $petition->psaForwardingLog->status_label }}</span></dd>
                    </dl>
                    <dl class="alc-detail-item">
                        <dt>Forwarded At</dt>
                        <dd>{{ $petition->psaForwardingLog->forwarded_at ? $petition->psaForwardingLog->forwarded_at->format('M d, Y h:i A') : 'N/A' }}</dd>
                    </dl>
                    <dl class="alc-detail-item">
                        <dt>Forwarded By</dt>
                        <dd>{{ $petition->psaForwardingLog->forwarder->name ?? 'N/A' }}</dd>
                    </dl>
                </div>
            </div>
            @endif
        </aside>
    </div>
</div>
@endsection

@push('styles')
<style>
    .alc-legal-content {
        gap: 0.85rem;
    }

    .alc-status-banner {
        margin-bottom: 0.75rem;
    }

    .alc-status-pill {
        font-size: 0.85rem;
        padding: 0.38rem 0.9rem;
        border-radius: 999px;
        font-weight: 700;
        letter-spacing: 0.01em;
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
    }

    .alc-panel {
        border: 1px solid #e5e7eb;
        border-radius: 0.85rem;
        padding: 0.95rem 1.05rem;
        background: #ffffff;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
    }

    .alc-detail-section h3 {
        margin-bottom: 0.7rem;
    }

    .alc-grid-2 {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.8rem 1.2rem;
    }

    .alc-grid-top {
        margin-top: 0.75rem;
    }

    .alc-span-full {
        grid-column: 1 / -1;
    }

    .alc-detail-item dt {
        text-transform: none;
        letter-spacing: 0.02em;
        font-size: 0.72rem;
        font-weight: 700;
        color: #6b7280;
    }

    .alc-detail-item dd {
        font-size: 0.95rem;
        font-weight: 600;
        color: #111827;
        margin-top: 0.2rem;
    }

    .alc-text-block {
        font-size: 0.88rem;
        color: #374151;
        line-height: 1.65;
        margin: 0;
    }

    .alc-table-wrap {
        overflow-x: auto;
        border-radius: 0.75rem;
        border: 1px solid #e5e7eb;
    }

    .alc-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 0.85rem;
    }

    .alc-table thead th {
        text-align: left;
        padding: 0.7rem 0.85rem;
        background: #f8fafc;
        color: #4b5563;
        font-weight: 700;
        border-bottom: 1px solid #e5e7eb;
        position: sticky;
        top: 0;
        z-index: 1;
    }

    .alc-table tbody td {
        padding: 0.7rem 0.85rem;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: top;
    }

    .alc-table tbody tr:hover {
        background: #f9fafb;
    }

    .alc-strong {
        font-weight: 700;
        color: #111827;
    }

    .alc-muted {
        color: #6b7280;
    }

    .alc-attachment-icon {
        color: #6366f1;
        font-size: 1rem;
    }

    .alc-attachment-name {
        font-size: 0.82rem;
        color: #111827;
        font-weight: 600;
    }

    .alc-attachment-meta {
        font-size: 0.74rem;
        color: #9ca3af;
    }

    .alc-action-row {
        display: flex;
        flex-wrap: wrap;
        gap: 0.7rem;
        margin-bottom: 1rem;
    }

    .alc-action-form {
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        padding: 0.85rem 0.95rem;
        background: #f9fafb;
    }

    .alc-action-title {
        font-weight: 700;
        margin-bottom: 0.75rem;
    }

    .alc-action-title-approve {
        color: #166534;
    }

    .alc-action-title-reject {
        color: #991b1b;
    }

    .alc-form-group {
        margin-bottom: 0.9rem;
    }

    .alc-form-label {
        display: block;
        font-size: 0.8rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.3rem;
    }

    .alc-form-input {
        width: 100%;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        padding: 0.55rem 0.6rem;
        font-size: 0.88rem;
        background: #ffffff;
    }

    .alc-help-text {
        font-size: 0.75rem;
        color: #6b7280;
        margin-bottom: 0.7rem;
    }

    .alc-form-actions {
        display: flex;
        gap: 0.6rem;
        flex-wrap: wrap;
    }

    .alc-panel-decision {
        border-color: #c7d2fe;
        background: #f5f7ff;
    }

    .alc-panel-forward {
        border-color: #bfdbfe;
        background: #eff6ff;
    }

    .alc-panel-annotation {
        border-color: #fde68a;
        background: #fff7ed;
    }

    .alc-panel-rejection {
        border-color: #fecaca;
        background: #fef2f2;
    }

    .alc-panel-approval {
        border-color: #bbf7d0;
        background: #f0fdf4;
    }

    .alc-forward-title,
    .alc-annotation-title,
    .alc-rejection-title,
    .alc-approval-title {
        color: inherit;
    }

    .alc-forward-title { color: #2563eb; }
    .alc-annotation-title { color: #92400e; }
    .alc-rejection-title { color: #dc2626; }
    .alc-approval-title { color: #166534; }

    .alc-annotation-preview {
        background: #fff7ed;
        border: 1px solid #fed7aa;
        border-radius: 0.65rem;
        padding: 0.75rem 0.85rem;
        line-height: 1.6;
        font-size: 0.85rem;
        color: #7c2d12;
    }

    .alc-annotation-meta {
        margin-top: 0.55rem;
        font-size: 0.74rem;
        color: #6b7280;
    }

    @media (max-width: 900px) {
        .alc-grid-2 {
            grid-template-columns: 1fr;
        }

        .alc-action-row {
            flex-direction: column;
        }
    }
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/admin-legal-corrections.js') }}"></script>
@endpush
