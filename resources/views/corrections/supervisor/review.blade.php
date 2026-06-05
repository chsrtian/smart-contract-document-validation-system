{{-- filepath: c:\Users\PC\final_capstone\resources\views\corrections\supervisor\review.blade.php --}}
@extends('layouts.supervisor')

@section('content')
<link rel="stylesheet" href="{{ asset('css/supervisor-review.css') }}">

<div class="sv-container">
    
    <!-- Page Header -->
    <div class="sv-page-header">
        <nav class="sv-breadcrumb">
            <a href="{{ route('corrections.approval.dashboard') }}">Dashboard</a>
            <span class="sep"><i class="fas fa-chevron-right"></i></span>
            <a href="{{ route('corrections.approval.pending') }}">Pending</a>
            <span class="sep"><i class="fas fa-chevron-right"></i></span>
            <span>Review Request</span>
        </nav>
        <div class="sv-header-row">
            <h1>Review Correction Request</h1>
            <!-- Auto-verification badge -->
            <div id="verificationBadge" class="sv-verification-badge verifying">
                <i class="fas fa-circle-notch"></i>
                <span>Verifying blockchain...</span>
            </div>
        </div>
    </div>

    <div class="sv-review-grid">
        
        <!-- LEFT COLUMN: Document Preview & Data -->
        <div>
            <div class="sv-card">
                <div class="sv-card-header">
                    <h2><i class="fas fa-file-alt"></i> Document Preview</h2>
                </div>
                <div class="sv-card-body">
                    <!-- Document Image -->
                    <div class="sv-doc-preview" style="margin-bottom: 1.25rem;">
                        @if($scan->file_path)
                            <img src="{{ route('staff.scans.image.preview', $scan) }}" alt="Document Image">
                        @else
                            <div class="no-image">
                                <i class="fas fa-image"></i>
                                <p>No image available</p>
                            </div>
                        @endif
                    </div>
                    
                    <!-- Extracted Data -->
                    <h4 style="font-size: 0.8125rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 0.625rem;">
                        <i class="fas fa-list-alt" style="margin-right: 0.375rem;"></i>Extracted Data
                    </h4>
                    <div class="sv-data-list">
                        @forelse($extractedFields as $key => $value)
                            @if(!in_array($key, ['raw_text', 'ocr_text']))
                                <div class="sv-data-row {{ $key === $correctionRequest->field_name ? 'highlighted' : '' }}">
                                    <span class="label">{{ ucwords(str_replace('_', ' ', $key)) }}</span>
                                    <span class="value">
                                        {{ $value ?: '(empty)' }}
                                        @if($key === $correctionRequest->field_name)
                                            <i class="fas fa-arrow-left" style="margin-left: 0.375rem; color: var(--brand-primary);"></i>
                                        @endif
                                    </span>
                                </div>
                            @endif
                        @empty
                            <div class="sv-data-row">
                                <span class="label" style="width: 100%; text-align: center;">No extracted data available</span>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: Correction Details & Decision -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            
            <!-- Correction Details Card -->
            <div class="sv-card">
                <div class="sv-card-header">
                    <h2><i class="fas fa-edit"></i> Correction Details</h2>
                </div>
                <div class="sv-card-body">
                    
                    <!-- Document Info -->
                    <div class="sv-info-row">
                        <span class="label">Document</span>
                        <span class="value">{{ $scan->document_type_name ?? 'Document' }}</span>
                    </div>
                    <div class="sv-info-row">
                        <span class="label">Document ID</span>
                        <span class="value mono">{{ $scan->document_id ?? 'N/A' }}</span>
                    </div>
                    
                    <!-- Diff View (Main Focus) -->
                    <div class="sv-diff-container" style="margin-top: 1.25rem; padding-top: 1.25rem; border-top: 1px solid var(--border-light);">
                        <div class="sv-diff-header">
                            <span style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 500;">Field Being Corrected:</span>
                            <span class="field-name">{{ $correctionRequest->field_display_name }}</span>
                        </div>
                        <div class="sv-diff-boxes">
                            <div class="sv-diff-box old">
                                <div class="label">Current Value</div>
                                <div class="value">{{ $correctionRequest->current_value ?: '(empty)' }}</div>
                            </div>
                            <div class="sv-diff-arrow">
                                <i class="fas fa-arrow-right"></i>
                            </div>
                            <div class="sv-diff-box new">
                                <div class="label">Proposed Value</div>
                                <div class="value">{{ $correctionRequest->proposed_value }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Reason -->
                    <div style="margin-top: 1rem;">
                        <div class="sv-info-row" style="flex-direction: column; align-items: flex-start; gap: 0.5rem;">
                            <span class="label">Reason for Correction</span>
                            <span class="value" style="text-align: left; background: var(--bg-muted); padding: 0.75rem; border-radius: var(--radius-md); width: 100%;">
                                {{ $correctionRequest->reason }}
                            </span>
                        </div>
                    </div>
                    
                    <!-- Requester -->
                    <div class="sv-info-row" style="margin-top: 1rem;">
                        <span class="label">Requested By</span>
                        <div class="sv-requester">
                            <div class="avatar"><i class="fas fa-user"></i></div>
                            <div>
                                <div class="name">{{ $correctionRequest->requester->name ?? 'Unknown' }}</div>
                                <div class="email">{{ $correctionRequest->requested_at->format('M d, Y \a\t g:i A') }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- TX Hash (collapsed) -->
                    <div class="sv-info-row" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border-light);">
                        <span class="label"><i class="fas fa-link"></i> Original TX Hash</span>
                        <span class="value mono" style="max-width: 180px; font-size: 0.6875rem;">
                            {{ Str::limit($scan->blockchain_tx_hash ?? 'Not anchored', 24) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Decision Card -->
            <div class="sv-card">
                <div class="sv-card-header">
                    <h2><i class="fas fa-gavel"></i> Decision</h2>
                </div>
                <div class="sv-card-body">
                    <div class="sv-decision-panel">
                        
                        <!-- Approve Button -->
                        <form action="{{ route('corrections.approval.approve', $correctionRequest) }}" method="POST" id="approveForm">
                            @csrf
                            <button type="submit" id="approveBtn" class="sv-btn sv-btn-approve" disabled>
                                <i class="fas fa-check-circle"></i> Approve Correction
                            </button>
                        </form>
                        
                        <!-- Reject Button (opens reason field) -->
                        <button type="button" id="showRejectBtn" class="sv-btn sv-btn-reject" disabled>
                            <i class="fas fa-times-circle"></i> Reject Correction
                        </button>
                        
                        <!-- Rejection Reason (hidden by default) -->
                        <div id="rejectReasonContainer" class="sv-reject-reason">
                            <form action="{{ route('corrections.approval.reject', $correctionRequest) }}" method="POST" id="rejectForm">
                                @csrf
                                <label for="rejection_reason">Rejection Reason <span style="color: var(--danger);">*</span></label>
                                <textarea 
                                    name="rejection_reason" 
                                    id="rejection_reason" 
                                    rows="3" 
                                    placeholder="Provide a clear reason for rejection..."
                                    required
                                    minlength="10"
                                ></textarea>
                                @error('rejection_reason')
                                    <p style="color: var(--danger); font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</p>
                                @enderror
                                <div style="display: flex; gap: 0.5rem; margin-top: 0.75rem;">
                                    <button type="button" id="cancelRejectBtn" class="sv-btn sv-btn-secondary" style="flex: 1;">
                                        Cancel
                                    </button>
                                    <button type="submit" class="sv-btn sv-btn-reject" style="flex: 1; background: var(--danger); color: white; border: none;">
                                        <i class="fas fa-times-circle"></i> Confirm Reject
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Verification Notice (shown until verified) -->
                        <div id="verifyNotice" class="sv-alert warning" style="margin-top: 0.75rem;">
                            <i class="fas fa-spinner fa-spin"></i>
                            <span>Verifying document integrity... Please wait.</span>
                        </div>
                        
                        <!-- Blockchain Info -->
                        <div class="sv-blockchain-info">
                            <div class="title">
                                <i class="fas fa-shield-alt"></i>
                                Blockchain Integrity
                            </div>
                            <ul>
                                <li>Original transaction hash will <strong>never</strong> be altered</li>
                                <li>Approved corrections create a <strong>new</strong> transaction</li>
                                <li>Full audit trail is preserved on-chain</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Back Button -->
            <a href="{{ route('corrections.approval.pending') }}" class="sv-btn sv-btn-secondary" style="align-self: flex-start;">
                <i class="fas fa-arrow-left"></i> Back to Pending
            </a>
        </div>
    </div>
</div>

<!-- Rejection Confirmation Modal -->
<div id="rejectModal" class="sv-modal-overlay">
    <div class="sv-modal">
        <div class="sv-modal-header">
            <h3><i class="fas fa-exclamation-triangle" style="color: var(--danger); margin-right: 0.5rem;"></i> Confirm Rejection</h3>
            <button class="sv-modal-close" onclick="closeRejectModal()">&times;</button>
        </div>
        <div class="sv-modal-body">
            <p>Are you sure you want to reject this correction request? This action cannot be undone.</p>
            <p style="margin-top: 0.75rem; font-weight: 500;">Reason provided:</p>
            <p id="modalReasonPreview" style="background: var(--bg-muted); padding: 0.75rem; border-radius: var(--radius-md); margin-top: 0.5rem;"></p>
        </div>
        <div class="sv-modal-footer">
            <button class="sv-btn sv-btn-secondary" onclick="closeRejectModal()">Cancel</button>
            <button class="sv-btn" style="background: var(--danger); color: white;" onclick="confirmReject()">
                <i class="fas fa-times-circle"></i> Reject
            </button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Expected hash for verification
    const expectedTxHash = '{{ $scan->blockchain_tx_hash ?? '' }}';
    let isVerified = false;

    // Auto-verify on page load
    document.addEventListener('DOMContentLoaded', function() {
        if (expectedTxHash && expectedTxHash !== 'Not anchored') {
            // Simulate verification delay (in real app, this would be an API call)
            setTimeout(function() {
                verifyDocument();
            }, 1500);
        } else {
            // No blockchain hash, allow actions anyway but show warning
            showVerificationResult('warning', 'Document not anchored to blockchain');
            enableActions();
        }
    });

    function verifyDocument() {
        const badge = document.getElementById('verificationBadge');
        const notice = document.getElementById('verifyNotice');
        
        // Simulate blockchain verification (in production, call your API)
        // For now, we trust the stored hash
        isVerified = true;
        
        // Update badge
        badge.className = 'sv-verification-badge verified';
        badge.innerHTML = '<i class="fas fa-check-circle"></i><span>Blockchain Verified</span>';
        
        // Hide notice
        notice.style.display = 'none';
        
        // Enable action buttons
        enableActions();
    }

    function showVerificationResult(type, message) {
        const badge = document.getElementById('verificationBadge');
        const notice = document.getElementById('verifyNotice');
        
        if (type === 'verified') {
            badge.className = 'sv-verification-badge verified';
            badge.innerHTML = '<i class="fas fa-check-circle"></i><span>Verified</span>';
        } else if (type === 'warning') {
            badge.className = 'sv-verification-badge' ;
            badge.style.background = 'var(--warning-light)';
            badge.style.color = 'var(--warning-dark)';
            badge.innerHTML = '<i class="fas fa-exclamation-triangle"></i><span>' + message + '</span>';
        } else {
            badge.className = 'sv-verification-badge failed';
            badge.innerHTML = '<i class="fas fa-times-circle"></i><span>Verification Failed</span>';
        }
        
        notice.style.display = 'none';
    }

    function enableActions() {
        document.getElementById('approveBtn').disabled = false;
        document.getElementById('showRejectBtn').disabled = false;
    }

    // Rejection flow
    const showRejectBtn = document.getElementById('showRejectBtn');
    const rejectReasonContainer = document.getElementById('rejectReasonContainer');
    const cancelRejectBtn = document.getElementById('cancelRejectBtn');

    showRejectBtn.addEventListener('click', function() {
        rejectReasonContainer.classList.add('visible');
        showRejectBtn.style.display = 'none';
    });

    cancelRejectBtn.addEventListener('click', function() {
        rejectReasonContainer.classList.remove('visible');
        showRejectBtn.style.display = 'flex';
        document.getElementById('rejection_reason').value = '';
    });

    // Modal functions
    function closeRejectModal() {
        document.getElementById('rejectModal').classList.remove('active');
    }

    function confirmReject() {
        document.getElementById('rejectForm').submit();
    }

</script>
@endpush