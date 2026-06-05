@extends('layouts.admin')

@section('content')
    <div class="admin-container admin-correction-view">
        <div class="admin-page-header">
            <div>
                <h1 class="admin-page-title">Correction Request Details: #{{ $correctionRequest->id }}</h1>
                <p class="admin-page-subtitle">Review and manage correction request</p>
            </div>
            <a href="{{ route('admin.corrections.index') }}" class="admin-btn admin-btn-secondary">
                Back to Corrections
            </a>
        </div>

        @if(session('success') || session('warning') || session('error') || $errors->any())
            <div class="admin-correction-feedback-stack">
                @if(session('success'))
                    <div class="admin-callout admin-callout-success admin-feedback-callout">
                        <i class="fas fa-check-circle"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif
                @if(session('warning'))
                    <div class="admin-callout admin-callout-warning admin-feedback-callout">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>{{ session('warning') }}</span>
                    </div>
                @endif
                @if(session('error'))
                    <div class="admin-callout admin-callout-danger admin-feedback-callout">
                        <i class="fas fa-times-circle"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif
                @if($errors->any())
                    <div class="admin-callout admin-callout-danger admin-feedback-callout">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif
            </div>
        @endif

        <div class="admin-content admin-correction-content admin-correction-layout">
            <div class="admin-correction-main">
                <!-- Request Information -->
                <div class="admin-card admin-correction-card">
                    <div class="admin-card-body">
                        <h3 class="admin-section-title"><i class="fas fa-clipboard-list"></i> Request Information</h3>
                        <div class="admin-correction-grid admin-correction-grid-2">
                            <div class="admin-correction-item">
                                <span class="admin-info-label">Request ID</span>
                                <span class="admin-info-value admin-correction-id-pill">#{{ $correctionRequest->id }}</span>
                            </div>

                            <div class="admin-correction-item">
                                <span class="admin-info-label">Status</span>
                                <span class="admin-badge admin-correction-status-pill admin-badge-{{ $correctionRequest->status === 'approved' ? 'success' : ($correctionRequest->status === 'pending' ? 'warning' : 'danger') }}">
                                    @if($correctionRequest->status === 'approved')
                                        <i class="fas fa-check-circle" aria-hidden="true"></i>
                                    @elseif($correctionRequest->status === 'pending')
                                        <i class="fas fa-clock" aria-hidden="true"></i>
                                    @else
                                        <i class="fas fa-times-circle" aria-hidden="true"></i>
                                    @endif
                                    {{ ucfirst($correctionRequest->status) }}
                                </span>
                            </div>

                            <div class="admin-correction-item">
                                <span class="admin-info-label">Document</span>
                                <a href="{{ route('admin.documents.show', $correctionRequest->scan) }}" class="admin-link admin-link-primary admin-document-chip">
                                    <i class="fas fa-file-alt" aria-hidden="true"></i>
                                    Document #{{ $correctionRequest->scan_id }}
                                </a>
                            </div>

                            <div class="admin-correction-item">
                                <span class="admin-info-label">Requested By</span>
                                <span class="admin-info-value">{{ $correctionRequest->requester?->name ?? '-' }}</span>
                            </div>

                            <div class="admin-correction-item">
                                <span class="admin-info-label">Requested At</span>
                                <span class="admin-info-value">{{ $correctionRequest->requested_at->format('M d, Y H:i:s') }}</span>
                            </div>

                            @if($correctionRequest->reviewer)
                                <div class="admin-correction-item">
                                    <span class="admin-info-label">Reviewed By</span>
                                    <span class="admin-info-value">{{ $correctionRequest->reviewer->name }}</span>
                                </div>
                            @endif

                            @if($correctionRequest->reviewed_at)
                                <div class="admin-correction-item">
                                    <span class="admin-info-label">Reviewed At</span>
                                    <span class="admin-info-value">{{ $correctionRequest->reviewed_at->format('M d, Y H:i:s') }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Correction Details -->
                <div class="admin-card admin-correction-card">
                    <div class="admin-card-body">
                        <h3 class="admin-section-title"><i class="fas fa-pen"></i> Correction Details</h3>
                        <div class="admin-correction-stack">
                            <div class="admin-correction-item">
                                <span class="admin-info-label">Field Name</span>
                                <span class="admin-info-value admin-correction-field-pill">{{ str_replace('_', ' ', ucfirst($correctionRequest->field_name)) }}</span>
                            </div>

                            <div class="admin-correction-compare-grid" role="group" aria-label="Correction comparison">
                                <div class="admin-correction-item admin-correction-compare-card admin-correction-compare-card-current">
                                    <span class="admin-info-label">Current Value</span>
                                    <p class="admin-text-sm admin-font-semibold admin-correction-value-box admin-correction-value-current">
                                        {{ $correctionRequest->current_value ?? '-' }}
                                    </p>
                                </div>

                                <div class="admin-correction-compare-arrow" aria-hidden="true">
                                    <i class="fas fa-arrow-right"></i>
                                </div>

                                <div class="admin-correction-item admin-correction-compare-card admin-correction-compare-card-proposed">
                                    <span class="admin-info-label">Proposed Value</span>
                                    <p class="admin-text-sm admin-font-semibold admin-correction-value-box admin-correction-value-proposed">
                                        {{ $correctionRequest->proposed_value }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reason -->
                <div class="admin-card admin-correction-card">
                    <div class="admin-card-body">
                        <h3 class="admin-section-title"><i class="fas fa-comment-alt"></i> Reason</h3>
                        <div class="admin-correction-stack">
                            <div class="admin-correction-item">
                                <span class="admin-info-label">Reason for Correction</span>
                                <p class="admin-text-sm admin-correction-reason-box">{{ $correctionRequest->reason }}</p>
                            </div>

                            @if($correctionRequest->rejection_reason)
                                <div class="admin-correction-item">
                                    <span class="admin-info-label">Rejection Reason</span>
                                    <p class="admin-text-sm admin-correction-rejection-box">{{ $correctionRequest->rejection_reason }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <aside class="admin-correction-side">
                <!-- Admin Override Section (if overridden) -->
                @if($correctionRequest->isOverridden())
                    <div class="admin-callout admin-callout-danger admin-correction-override-callout">
                        <h3 class="admin-section-title" style="color: var(--admin-danger);"><i class="fas fa-exclamation-triangle"></i> Admin Override Applied</h3>
                        <div class="admin-correction-grid admin-correction-grid-2">
                            <div class="admin-correction-item">
                                <span class="admin-info-label">Override Type</span>
                                <span class="admin-badge admin-badge-{{ $correctionRequest->override_type === 'force_approve' ? 'success' : 'danger' }}">
                                    {{ $correctionRequest->override_type === 'force_approve' ? 'Force Approved' : 'Force Rejected' }}
                                </span>
                            </div>
                            <div class="admin-correction-item">
                                <span class="admin-info-label">Overridden By</span>
                                <span class="admin-info-value">{{ $correctionRequest->overrider?->name ?? '-' }}</span>
                            </div>
                            <div class="admin-correction-item admin-correction-item-wide">
                                <span class="admin-info-label">Override Date</span>
                                <span class="admin-info-value">
                                    {{ $correctionRequest->override_at ? $correctionRequest->override_at->format('M d, Y H:i:s') : '-' }}
                                </span>
                            </div>
                        </div>

                        <div style="margin-top: 1rem;">
                            <span class="admin-info-label">Override Justification</span>
                            <p class="admin-text-sm admin-correction-override-box">
                                {{ $correctionRequest->override_justification ?? '-' }}
                            </p>
                        </div>

                        <div class="admin-callout admin-callout-warning" style="margin-top: 0.75rem;">
                            <p class="admin-text-xs">
                                <i class="fas fa-exclamation-circle"></i>
                                <strong>Note:</strong> This override action has been logged with CRITICAL severity in the audit logs.
                            </p>
                        </div>
                    </div>
                @endif

                <!-- Actions -->
                @if(!$isOverridden)
                    {{-- Normal pending actions --}}
                    @if($correctionRequest->status === 'pending')
                        <div class="admin-card admin-correction-card admin-correction-actions-card">
                            <div class="admin-card-body">
                                <h3 class="admin-section-title"><i class="fas fa-gavel"></i> Admin Actions</h3>
                                <div class="admin-correction-action-row" role="group" aria-label="Admin Actions">
                                    <div class="admin-correction-action-tile admin-correction-action-approve">
                                        <form method="POST" action="{{ route('admin.corrections.approve', $correctionRequest) }}"
                                            onsubmit="return confirm('Are you sure you want to approve this correction request? The document will be updated.');">
                                            @csrf
                                            <button type="submit" class="admin-btn admin-btn-success admin-correction-action-btn">
                                                <i class="fas fa-check"></i> Approve Correction
                                            </button>
                                        </form>
                                    </div>

                                    <div class="admin-correction-action-tile admin-correction-action-reject">
                                        <button onclick="showRejectModal()" class="admin-btn admin-btn-danger admin-correction-action-btn">
                                            <i class="fas fa-times"></i> Reject Correction
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Override actions --}}
                    @if($correctionRequest->status === 'rejected')
                        <div class="admin-card admin-correction-card">
                            <div class="admin-card-body">
                                <h3 class="admin-section-title"><i class="fas fa-exclamation-triangle"></i> Admin Override Actions</h3>
                                <div class="admin-callout admin-callout-warning" style="margin-bottom: 1rem;">
                                    <p class="admin-text-sm">
                                        <i class="fas fa-info-circle"></i>
                                        This correction is <strong>Rejected</strong>. You may override the decision.
                                    </p>
                                </div>
                                <button onclick="showForceApproveModal()" class="admin-btn admin-btn-warning">
                                    <i class="fas fa-redo"></i> Force Approve (Override)
                                </button>
                            </div>
                        </div>
                    @endif

                    @if($correctionRequest->status === 'approved')
                        <div class="admin-card admin-correction-card">
                            <div class="admin-card-body">
                                <h3 class="admin-section-title"><i class="fas fa-exclamation-triangle"></i> Admin Override Actions</h3>
                                <div class="admin-callout admin-callout-warning" style="margin-bottom: 1rem;">
                                    <p class="admin-text-sm">
                                        <i class="fas fa-info-circle"></i>
                                        This correction is <strong>Approved</strong>. You may override the decision.
                                    </p>
                                </div>
                                <button onclick="showForceRejectModal()" class="admin-btn admin-btn-danger">
                                    <i class="fas fa-ban"></i> Force Reject (Override)
                                </button>
                            </div>
                        </div>
                    @endif
                @endif
            </aside>
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="admin-modal-overlay" style="display:none; z-index: 9999;">
        <div class="admin-modal">
            <div class="admin-modal-body">
                <h3 class="admin-section-title" style="margin-bottom: 1rem;"><i class="fas fa-times-circle"></i> Reject Correction Request</h3>
                <form method="POST" action="{{ route('admin.corrections.reject', $correctionRequest) }}">
                    @csrf
                    <div class="admin-form-group">
                        <label for="rejection_reason" class="admin-form-label">
                            Rejection Reason (minimum 20 characters)
                        </label>
                        <textarea name="rejection_reason" id="rejection_reason" rows="4" required
                            class="admin-form-input"
                            placeholder="Please provide a detailed reason for rejection..."></textarea>
                    </div>
                    <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                        <button type="button" onclick="closeRejectModal()"
                            class="admin-btn admin-btn-secondary">
                            Cancel
                        </button>
                        <button type="submit"
                            class="admin-btn admin-btn-danger">
                            Reject
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Force Approve Modal -->
    <div id="forceApproveModal" class="admin-modal-overlay" style="display:none; z-index: 9999;">
        <div class="admin-modal" style="max-width: 42rem;">
            <div class="admin-modal-body">
                <h3 class="admin-section-title admin-text-danger" style="margin-bottom: 1rem;"><i class="fas fa-exclamation-triangle"></i> Force Approve Correction (ADMIN OVERRIDE)</h3>
                <div class="admin-callout admin-callout-danger" style="margin-bottom: 1rem;">
                    <p class="admin-text-sm">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>WARNING:</strong> This action will override the previous rejection and approve this correction request.
                        The document will be updated and re-anchored to the blockchain. This action will be logged with CRITICAL severity.
                    </p>
                </div>
                <form method="POST" action="{{ route('admin.corrections.force-approve', $correctionRequest) }}"
                    onsubmit="return confirmForceApprove();">
                    @csrf
                    <div class="admin-form-group">
                        <label for="force_approve_justification" class="admin-form-label">
                            Override Justification (minimum 50 characters) *
                        </label>
                        <textarea name="justification" id="force_approve_justification" rows="4" required
                            minlength="50" maxlength="500"
                            class="admin-form-input"
                            placeholder="Provide a detailed justification for overriding the rejection. This will be permanently logged..."></textarea>
                        <p class="admin-text-xs admin-text-muted" style="margin-top: 0.25rem;">Character count: <span id="force_approve_char_count">0</span> / 500</p>
                    </div>
                    <div class="admin-form-group">
                        <label style="display: flex; align-items: center;">
                            <input type="checkbox" id="force_approve_confirm" required>
                            <span class="admin-text-sm" style="margin-left: 0.5rem;">I understand this action overrides previous decisions and is logged as CRITICAL</span>
                        </label>
                    </div>
                    <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                        <button type="button" onclick="closeForceApproveModal()"
                            class="admin-btn admin-btn-secondary">
                            Cancel
                        </button>
                        <button type="submit"
                            class="admin-btn admin-btn-warning">
                            Force Approve
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Force Reject Modal -->
    <div id="forceRejectModal" class="admin-modal-overlay" style="display:none; z-index: 9999;">
        <div class="admin-modal" style="max-width: 42rem;">
            <div class="admin-modal-body">
                <h3 class="admin-section-title admin-text-danger" style="margin-bottom: 1rem;"><i class="fas fa-exclamation-triangle"></i> Force Reject Correction (ADMIN OVERRIDE)</h3>
                <div class="admin-callout admin-callout-danger" style="margin-bottom: 1rem;">
                    <p class="admin-text-sm">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>WARNING:</strong> This action will override the previous approval and reject this correction request.
                        If the correction was already anchored to blockchain, the original version is preserved (blockchain immutability).
                        This action will be logged with CRITICAL severity.
                    </p>
                </div>
                <form method="POST" action="{{ route('admin.corrections.force-reject', $correctionRequest) }}"
                    onsubmit="return confirmForceReject();">
                    @csrf
                    <div class="admin-form-group">
                        <label for="force_reject_justification" class="admin-form-label">
                            Override Justification (minimum 50 characters) *
                        </label>
                        <textarea name="justification" id="force_reject_justification" rows="4" required
                            minlength="50" maxlength="500"
                            class="admin-form-input"
                            placeholder="Provide a detailed justification for overriding the approval. This will be permanently logged..."></textarea>
                        <p class="admin-text-xs admin-text-muted" style="margin-top: 0.25rem;">Character count: <span id="force_reject_char_count">0</span> / 500</p>
                    </div>
                    <div class="admin-form-group">
                        <label style="display: flex; align-items: center;">
                            <input type="checkbox" id="force_reject_confirm" required>
                            <span class="admin-text-sm" style="margin-left: 0.5rem;">I understand this action overrides previous decisions and is logged as CRITICAL</span>
                        </label>
                    </div>
                    <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                        <button type="button" onclick="closeForceRejectModal()"
                            class="admin-btn admin-btn-secondary">
                            Cancel
                        </button>
                        <button type="submit"
                            class="admin-btn admin-btn-danger">
                            Force Reject
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Reject Modal
        function showRejectModal() {
            document.getElementById('rejectModal').style.display = 'flex';
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').style.display = 'none';
        }

        // Force Approve Modal
        function showForceApproveModal() {
            document.getElementById('forceApproveModal').style.display = 'flex';
        }

        function closeForceApproveModal() {
            document.getElementById('forceApproveModal').style.display = 'none';
            const ta = document.getElementById('force_approve_justification');
            if (ta) ta.value = '';
            const cb = document.getElementById('force_approve_confirm');
            if (cb) cb.checked = false;
            const cc = document.getElementById('force_approve_char_count');
            if (cc) cc.textContent = '0';
        }

        function confirmForceApprove() {
            const justification = document.getElementById('force_approve_justification')?.value || '';
            if (justification.length < 50) {
                alert('Justification must be at least 50 characters.');
                return false;
            }
            return confirm('Are you absolutely sure you want to FORCE APPROVE this correction? This action cannot be undone and will be logged as CRITICAL.');
        }

        // Force Reject Modal
        function showForceRejectModal() {
            document.getElementById('forceRejectModal').style.display = 'flex';
        }

        function closeForceRejectModal() {
            document.getElementById('forceRejectModal').style.display = 'none';
            const ta = document.getElementById('force_reject_justification');
            if (ta) ta.value = '';
            const cb = document.getElementById('force_reject_confirm');
            if (cb) cb.checked = false;
            const cc = document.getElementById('force_reject_char_count');
            if (cc) cc.textContent = '0';
        }

        function confirmForceReject() {
            const justification = document.getElementById('force_reject_justification')?.value || '';
            if (justification.length < 50) {
                alert('Justification must be at least 50 characters.');
                return false;
            }
            return confirm('Are you absolutely sure you want to FORCE REJECT this correction? This action cannot be undone and will be logged as CRITICAL.');
        }

        // Character counters
        document.getElementById('force_approve_justification')?.addEventListener('input', function() {
            document.getElementById('force_approve_char_count').textContent = this.value.length;
        });

        document.getElementById('force_reject_justification')?.addEventListener('input', function() {
            document.getElementById('force_reject_char_count').textContent = this.value.length;
        });
    </script>

@endsection

@push('styles')
<style>
    .admin-correction-content {
        gap: 1rem;
    }

    .admin-correction-feedback-stack {
        display: grid;
        gap: 0.6rem;
        margin-bottom: 1rem;
    }

    .admin-feedback-callout {
        display: flex;
        align-items: center;
        gap: 0.55rem;
        font-weight: 600;
    }

    .admin-correction-layout {
        display: grid;
        gap: 1rem;
        grid-template-columns: minmax(0, 1.55fr) minmax(300px, 0.9fr);
        align-items: start;
    }

    .admin-correction-main,
    .admin-correction-side {
        display: grid;
        gap: 1rem;
    }

    .admin-correction-side {
        position: sticky;
        top: 1rem;
    }

    .admin-correction-card .admin-card-body {
        padding: 1.08rem 1.14rem;
    }

    .admin-correction-card {
        border: 1px solid var(--admin-border-light);
        box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06);
    }

    .admin-correction-grid {
        display: grid;
        gap: 0.9rem;
    }

    .admin-correction-grid-2 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .admin-correction-item-wide {
        grid-column: 1 / -1;
    }

    .admin-correction-stack {
        display: flex;
        flex-direction: column;
        gap: 0.95rem;
    }

    .admin-correction-item {
        display: flex;
        flex-direction: column;
        gap: 0.28rem;
        min-width: 0;
    }

    .admin-correction-view .admin-info-label {
        text-transform: uppercase;
        letter-spacing: 0.04em;
        font-size: 0.73rem;
        font-weight: 800;
        color: #64748b;
    }

    .admin-correction-view .admin-info-value {
        font-size: 0.99rem;
        font-weight: 700;
        line-height: 1.4;
        color: var(--admin-text-primary);
    }

    .admin-correction-id-pill,
    .admin-correction-field-pill {
        display: inline-flex;
        align-items: center;
        width: fit-content;
        border: 1px solid #dbe5f3;
        background: #f8fbff;
        border-radius: 999px;
        padding: 0.3rem 0.72rem;
    }

    .admin-correction-id-pill {
        font-family: "Consolas", "SFMono-Regular", Menlo, Monaco, monospace;
        font-size: 0.9rem;
        letter-spacing: 0.01em;
    }

    .admin-document-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        width: fit-content;
        border: 1px solid #c7d2fe;
        background: #eef2ff;
        border-radius: 999px;
        padding: 0.26rem 0.66rem;
        font-weight: 700;
        text-decoration: none;
    }

    .admin-document-chip:hover {
        text-decoration: none;
        background: #e0e7ff;
    }

    .admin-correction-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        width: fit-content;
        border-radius: 999px;
        border-radius: 999px;
        font-weight: 700;
        letter-spacing: 0.01em;
        padding: 0.24rem 0.62rem;
    }

    .admin-correction-status-pill i {
        font-size: 0.82rem;
    }

    .admin-correction-compare-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
        gap: 0.85rem;
        align-items: stretch;
    }

    .admin-correction-compare-card {
        border-radius: var(--admin-radius-md);
        border: 1px solid var(--admin-border-light);
        padding: 0.72rem;
    }

    .admin-correction-compare-card-current {
        background: #fff8f8;
        border-color: #fecaca;
    }

    .admin-correction-compare-card-proposed {
        background: #f0fdf4;
        border-color: #bbf7d0;
    }

    .admin-correction-compare-arrow {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #64748b;
        font-size: 0.95rem;
        min-width: 1.7rem;
    }

    .admin-correction-value-box {
        margin-top: 0.18rem;
        padding: 0.75rem;
        border-radius: var(--admin-radius-md);
        border: 1px solid var(--admin-border-light);
        word-break: break-word;
    }

    .admin-correction-value-current {
        background: #fff1f2;
        border-color: #fda4af;
    }

    .admin-correction-value-proposed {
        background: #f0fdf4;
        border-color: #86efac;
    }

    .admin-correction-reason-box,
    .admin-correction-rejection-box {
        margin-top: 0.28rem;
        padding: 0.88rem;
        border-radius: var(--admin-radius-md);
        border: 1px solid var(--admin-border-light);
        line-height: 1.55;
        white-space: pre-wrap;
        word-break: break-word;
    }

    .admin-correction-reason-box {
        background: #f8fafc;
        border-color: #dbe5f3;
    }

    .admin-correction-rejection-box {
        background: #fef2f2;
        border-color: #fda4af;
    }

    .admin-correction-override-callout {
        border-width: 1px;
    }

    .admin-correction-override-box {
        background: var(--admin-bg-card);
        padding: 0.78rem;
        border-radius: var(--admin-radius-md);
        border: 2px solid var(--admin-danger);
        margin-top: 0.25rem;
        line-height: 1.55;
        white-space: pre-wrap;
        word-break: break-word;
    }

    .admin-correction-actions-card {
        border: 1px solid #e2e8f0;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
    }

    .admin-correction-action-row {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.7rem;
    }

    .admin-correction-action-row form {
        margin: 0;
    }

    .admin-correction-action-tile {
        border-radius: 0.75rem;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        padding: 0.65rem;
    }

    .admin-correction-action-approve {
        background: #f0fdf4;
        border-color: #86efac;
    }

    .admin-correction-action-reject {
        background: #fef2f2;
        border-color: #fca5a5;
    }

    .admin-correction-action-btn {
        width: 100%;
        min-height: 2.48rem;
        font-weight: 700;
    }

    .admin-correction-action-row .admin-btn-success {
        box-shadow: 0 0 0 1px rgba(22, 163, 74, 0.2) inset;
    }

    .admin-correction-action-row .admin-btn-danger {
        box-shadow: 0 0 0 1px rgba(220, 38, 38, 0.2) inset;
    }

    @media (max-width: 1200px) {
        .admin-correction-layout {
            grid-template-columns: 1fr;
        }

        .admin-correction-side {
            position: static;
        }
    }

    @media (max-width: 900px) {
        .admin-correction-grid-2 {
            grid-template-columns: 1fr;
        }

        .admin-correction-item-wide {
            grid-column: auto;
        }

        .admin-correction-compare-grid {
            grid-template-columns: 1fr;
            gap: 0.6rem;
        }

        .admin-correction-compare-arrow {
            transform: rotate(90deg);
            justify-content: flex-start;
            margin-left: 0.25rem;
        }

        .admin-correction-action-row {
            grid-template-columns: 1fr;
        }

        .admin-correction-action-row form {
            width: 100%;
        }
    }
</style>
@endpush
