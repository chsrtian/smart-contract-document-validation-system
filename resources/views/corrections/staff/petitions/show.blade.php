@extends('layouts.staff')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/legal-corrections.css') }}">
@endpush

@section('content')
<div class="py-6">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="lc-detail-header">
            <div>
                <div class="lc-detail-title">
                    <i class="fas fa-gavel" style="color: #6366f1;"></i>
                    Petition {{ $petition->petition_number }}
                </div>
                <div class="lc-detail-meta">
                    {{ $petition->petition_type_label }} &bull; 
                    Created {{ $petition->created_at->format('M d, Y h:i A') }}
                </div>
            </div>
            <div class="lc-detail-actions">
                @if($petition->isDraft())
                    <form method="POST" action="{{ route('corrections.petitions.submit', $petition) }}" style="display: inline;">
                        @csrf
                        <button type="submit" id="lc-submit-for-approval-btn" class="lc-btn lc-btn-success" {{ $petition->fieldChanges->count() === 0 ? 'disabled' : '' }}>
                            <i class="fas fa-paper-plane"></i> Submit for Approval
                        </button>
                    </form>
                @endif
                @if($petition->isApproved() || $petition->status === 'forwarded_to_psa')
                    <button type="button" onclick="openPrintRemarksModal()" class="lc-btn lc-btn-primary lc-btn-sm">
                        <i class="fas fa-print"></i> Print Approved Document
                    </button>
                @endif
                <a href="{{ route('corrections.petitions.index') }}" class="lc-btn lc-btn-secondary lc-btn-sm">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <!-- Status Banner -->
        <div style="margin-bottom: 1rem;">
            <span class="lc-badge {{ $petition->status }}" style="font-size: 0.875rem; padding: 0.375rem 1rem;">
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
        </div>

        <!-- Petition Info -->
        <div class="lc-detail-section">
            <h3><i class="fas fa-info-circle"></i> Petition Information</h3>
            <div class="lc-detail-grid">
                <dl class="lc-detail-item">
                    <dt>Petition Number</dt>
                    <dd>{{ $petition->petition_number }}</dd>
                </dl>
                <dl class="lc-detail-item">
                    <dt>Legal Basis</dt>
                    <dd>{{ $petition->legal_basis }}</dd>
                </dl>
                <dl class="lc-detail-item">
                    <dt>Petition Type</dt>
                    <dd>{{ $petition->petition_type_label }}</dd>
                </dl>
                <dl class="lc-detail-item">
                    <dt>Status</dt>
                    <dd>{{ $petition->status_label }}</dd>
                </dl>
                <dl class="lc-detail-item">
                    <dt>Filed By (Staff)</dt>
                    <dd>{{ $petition->creator->name ?? 'N/A' }}</dd>
                </dl>
                <dl class="lc-detail-item">
                    <dt>Date Filed</dt>
                    <dd>{{ $petition->created_at->format('F d, Y') }}</dd>
                </dl>
            </div>
        </div>

        <!-- Document Info -->
        <div class="lc-detail-section">
            <h3><i class="fas fa-file-alt"></i> Civil Registry Document</h3>
            @if($petition->scan)
            <div class="lc-detail-grid">
                <dl class="lc-detail-item">
                    <dt>Document Type</dt>
                    <dd>{{ ucwords(str_replace('_', ' ', $petition->scan->document_type)) }}</dd>
                </dl>
                <dl class="lc-detail-item">
                    <dt>Document ID</dt>
                    <dd>{{ $petition->scan->document_id ?? $petition->scan->id }}</dd>
                </dl>
                <dl class="lc-detail-item">
                    <dt>Title</dt>
                    <dd>{{ $petition->scan->title ?? 'N/A' }}</dd>
                </dl>
                <dl class="lc-detail-item">
                    <dt>Registry Number</dt>
                    <dd>{{ $petition->scan->registry_number ?? 'N/A' }}</dd>
                </dl>
            </div>
            @else
            <p style="color: #9ca3af; font-size: 0.875rem;">Document information not available.</p>
            @endif
        </div>

        <!-- Petitioner Info -->
        <div class="lc-detail-section">
            <h3><i class="fas fa-user"></i> Petitioner Information</h3>
            <div class="lc-detail-grid">
                <dl class="lc-detail-item">
                    <dt>Full Name</dt>
                    <dd>{{ $petition->petitioner_name }}</dd>
                </dl>
                <dl class="lc-detail-item">
                    <dt>Relationship</dt>
                    <dd>{{ \App\Models\LegalCorrectionPetition::RELATIONSHIP_TYPES[$petition->petitioner_relationship] ?? $petition->petitioner_relationship }}</dd>
                </dl>
                @if($petition->petitioner_address)
                <dl class="lc-detail-item" style="grid-column: 1 / -1;">
                    <dt>Address</dt>
                    <dd>{{ $petition->petitioner_address }}</dd>
                </dl>
                @endif
            </div>
        </div>

        <!-- Reason -->
        @if($petition->reason)
        <div class="lc-detail-section">
            <h3><i class="fas fa-align-left"></i> Grounds / Reason</h3>
            <p style="font-size: 0.875rem; color: #374151; line-height: 1.6;">{{ $petition->reason }}</p>
        </div>
        @endif

        <!-- Field Changes -->
        <div class="lc-detail-section">
            <h3><i class="fas fa-pen-fancy"></i> Requested Corrections ({{ $petition->fieldChanges->count() }})</h3>
            @if($petition->fieldChanges->count() > 0)
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; margin-top: 0.5rem;">
                    <thead>
                        <tr style="background: #f9fafb;">
                            <th style="padding: 0.625rem 0.75rem; font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase; text-align: left; border-bottom: 1px solid #e5e7eb;">#</th>
                            <th style="padding: 0.625rem 0.75rem; font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase; text-align: left; border-bottom: 1px solid #e5e7eb;">Field</th>
                            <th style="padding: 0.625rem 0.75rem; font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase; text-align: left; border-bottom: 1px solid #e5e7eb;">Current Value</th>
                            <th style="padding: 0.625rem 0.75rem; font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase; text-align: left; border-bottom: 1px solid #e5e7eb;">Proposed Value</th>
                            <th style="padding: 0.625rem 0.75rem; font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase; text-align: left; border-bottom: 1px solid #e5e7eb;">Justification</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($petition->fieldChanges as $i => $change)
                        <tr>
                            <td style="padding: 0.625rem 0.75rem; font-size: 0.8125rem; color: #6b7280; border-bottom: 1px solid #f3f4f6;">{{ $i + 1 }}</td>
                            <td style="padding: 0.625rem 0.75rem; font-size: 0.8125rem; font-weight: 600; color: #111827; border-bottom: 1px solid #f3f4f6;">{{ $change->field_label }}</td>
                            <td style="padding: 0.625rem 0.75rem; border-bottom: 1px solid #f3f4f6;">
                                <span style="background: #fef2f2; color: #991b1b; padding: 0.125rem 0.5rem; border-radius: 0.25rem; font-size: 0.8125rem; text-decoration: line-through;">
                                    {{ $change->current_value ?: '(blank)' }}
                                </span>
                            </td>
                            <td style="padding: 0.625rem 0.75rem; border-bottom: 1px solid #f3f4f6;">
                                <span style="background: #f0fdf4; color: #166534; padding: 0.125rem 0.5rem; border-radius: 0.25rem; font-weight: 600; font-size: 0.8125rem;">
                                    {{ $change->proposed_value }}
                                </span>
                            </td>
                            <td style="padding: 0.625rem 0.75rem; font-size: 0.8125rem; color: #6b7280; border-bottom: 1px solid #f3f4f6;">{{ $change->justification ?: '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <p style="color: #9ca3af; font-size: 0.875rem;">No field corrections specified yet.</p>
            @endif
        </div>

        <!-- Attachments -->
        @if($petition->attachments->count() > 0)
        <div class="lc-detail-section">
            <h3><i class="fas fa-paperclip"></i> Supporting Documents ({{ $petition->attachments->count() }})</h3>
            <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 0.5rem;">
                @foreach($petition->attachments as $attachment)
                <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.625rem 0.75rem; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 0.375rem;">
                    <i class="fas fa-file-pdf" style="color: #6366f1; font-size: 1rem;"></i>
                    <div>
                        <div style="font-size: 0.8125rem; color: #111827; font-weight: 500;">{{ $attachment->original_filename }}</div>
                        <div style="font-size: 0.75rem; color: #9ca3af;">{{ $attachment->file_type_label }} &bull; {{ $attachment->formatted_file_size }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Marginal Annotation (if approved) -->
        @if($petition->annotations->count() > 0)
        <div class="lc-detail-section">
            <h3><i class="fas fa-stamp"></i> Marginal Annotation</h3>
            @foreach($petition->annotations as $annotation)
            <div class="lc-annotation-preview">{{ $annotation->annotation_text }}</div>
            <div style="margin-top: 0.5rem; font-size: 0.75rem; color: #6b7280;">
                Decision No.: {{ $annotation->lcro_decision_number }} &bull; 
                Date: {{ $annotation->annotation_date ? $annotation->annotation_date->format('F d, Y') : 'N/A' }} &bull;
                Legal Reference: {{ $annotation->legal_reference }}
            </div>
            @endforeach
        </div>
        @endif

        <!-- PSA Forwarding (if forwarded) -->
        @if($petition->psaForwardingLog)
        <div class="lc-detail-section">
            <h3><i class="fas fa-share-square"></i> PSA Forwarding</h3>
            <div class="lc-psa-block">{{ $petition->psaForwardingLog->transmittal_details }}</div>
            <div style="margin-top: 0.5rem; font-size: 0.75rem; color: #6b7280;">
                Reference: {{ $petition->psaForwardingLog->forwarding_reference }} &bull;
                Status: {{ $petition->psaForwardingLog->status_label }} &bull;
                Forwarded: {{ $petition->psaForwardingLog->forwarded_at ? $petition->psaForwardingLog->forwarded_at->format('F d, Y h:i A') : 'N/A' }}
            </div>
        </div>
        @endif

        <!-- Rejection Info -->
        @if($petition->isRejected())
        <div class="lc-detail-section" style="border-color: #fecaca;">
            <h3 style="color: #dc2626;"><i class="fas fa-ban"></i> Rejection Details</h3>
            <div class="lc-detail-grid">
                <dl class="lc-detail-item">
                    <dt>Rejected By</dt>
                    <dd>{{ $petition->rejector->name ?? 'N/A' }}</dd>
                </dl>
                <dl class="lc-detail-item">
                    <dt>Rejected At</dt>
                    <dd>{{ $petition->rejected_at ? $petition->rejected_at->format('F d, Y h:i A') : 'N/A' }}</dd>
                </dl>
                <dl class="lc-detail-item" style="grid-column: 1 / -1;">
                    <dt>Reason</dt>
                    <dd style="color: #dc2626;">{{ $petition->rejection_reason }}</dd>
                </dl>
            </div>
        </div>
        @endif

        <!-- Admin Notes -->
        @if($petition->admin_notes)
        <div class="lc-detail-section">
            <h3><i class="fas fa-sticky-note"></i> Admin Notes</h3>
            <p style="font-size: 0.875rem; color: #374151; line-height: 1.6;">{{ $petition->admin_notes }}</p>
        </div>
        @endif
    </div>
</div>

{{-- ── Pre-Print Remarks Modal (shown only for approved petitions) ── --}}
@if($petition->isApproved() || $petition->status === 'forwarded_to_psa')
<div id="lc-print-remarks-overlay"
     style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.55); z-index:9000; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:0.625rem; box-shadow:0 8px 32px rgba(0,0,0,0.22); width:100%; max-width:480px; margin:1rem; overflow:hidden;">

        {{-- Modal header --}}
        <div style="display:flex; align-items:center; justify-content:space-between; padding:1rem 1.25rem; background:#f0fdf4; border-bottom:2px solid #16a34a;">
            <div style="display:flex; align-items:center; gap:0.625rem;">
                <i class="fas fa-edit" style="color:#16a34a; font-size:1.1rem;"></i>
                <div>
                    <div style="font-size:0.9375rem; font-weight:700; color:#166534;">LCRO Remarks Before Printing</div>
                    <div style="font-size:0.75rem; color:#4b7c59; margin-top:0.1rem;">{{ $petition->petition_number }} &mdash; {{ $petition->petition_type_label }}</div>
                </div>
            </div>
            <button type="button" onclick="closePrintRemarksModal()"
                    style="background:transparent; border:none; cursor:pointer; color:#4b7c59; font-size:1.2rem; line-height:1;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        {{-- Modal body --}}
        <div style="padding:1.25rem;">
            <div style="background:#fffbeb; border:1px solid #fde68a; border-radius:0.375rem; padding:0.75rem 1rem; margin-bottom:1rem; font-size:0.8125rem; color:#92400e; display:flex; gap:0.5rem;">
                <i class="fas fa-info-circle" style="margin-top:0.1rem; flex-shrink:0;"></i>
                <span>Enter the <strong>REMARKS / ANNOTATION</strong> text to be placed in the side placeholder area of the printed document. You may leave this blank if no additional remarks are needed.</span>
            </div>
            <label style="display:block; font-size:0.8125rem; font-weight:600; color:#374151; margin-bottom:0.375rem;">
                Remarks / Annotation <span style="font-weight:400; color:#9ca3af;">(optional)</span>
            </label>
            <textarea id="lc-print-remarks-input"
                      rows="5"
                      maxlength="1000"
                      placeholder="e.g. Per LCRO Order dated March 11, 2026. Certified true copy issued to petitioner. Duly noted by the LCRO Head."
                      style="width:100%; border:1px solid #d1d5db; border-radius:0.375rem; padding:0.625rem 0.75rem; font-size:0.875rem; line-height:1.55; resize:vertical; outline:none; font-family:inherit;"></textarea>
            <div style="text-align:right; font-size:0.75rem; color:#9ca3af; margin-top:0.25rem;"><span id="lc-remarks-char-count">0</span> / 1000</div>
        </div>

        {{-- Modal footer --}}
        <div style="display:flex; gap:0.625rem; justify-content:flex-end; padding:0.875rem 1.25rem; border-top:1px solid #e5e7eb; background:#f9fafb;">
            <button type="button" onclick="closePrintRemarksModal()" class="lc-btn lc-btn-secondary lc-btn-sm">
                Cancel
            </button>
            <button type="button" onclick="proceedToPrint()" class="lc-btn lc-btn-success">
                <i class="fas fa-print"></i> Proceed to Print
            </button>
        </div>
    </div>
</div>

<script>
    var lcPrintBaseUrl = '{{ route('corrections.petitions.print', $petition) }}';

    function openPrintRemarksModal() {
        var overlay = document.getElementById('lc-print-remarks-overlay');
        overlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        setTimeout(function () {
            var ta = document.getElementById('lc-print-remarks-input');
            if (ta) ta.focus();
        }, 80);
    }

    function closePrintRemarksModal() {
        document.getElementById('lc-print-remarks-overlay').style.display = 'none';
        document.body.style.overflow = '';
    }

    function proceedToPrint() {
        var remarks = (document.getElementById('lc-print-remarks-input').value || '').trim();
        var url = lcPrintBaseUrl + (remarks ? '?remarks=' + encodeURIComponent(remarks) : '');
        window.open(url, '_blank');
        closePrintRemarksModal();
    }

    // Character counter
    document.addEventListener('DOMContentLoaded', function () {
        var ta = document.getElementById('lc-print-remarks-input');
        var counter = document.getElementById('lc-remarks-char-count');
        if (ta && counter) {
            ta.addEventListener('input', function () {
                counter.textContent = ta.value.length;
            });
        }
        // Close on overlay click (outside the card)
        var overlay = document.getElementById('lc-print-remarks-overlay');
        if (overlay) {
            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) closePrintRemarksModal();
            });
        }
    });
</script>
@endif

@endsection

@push('scripts')
<script src="{{ asset('js/legal-corrections.js') }}"></script>
@endpush
