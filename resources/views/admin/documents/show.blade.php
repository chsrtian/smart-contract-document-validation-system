@extends('layouts.admin')

@section('content')
    <div class="admin-container admin-doc-view">
        <div class="admin-page-header">
            <div>
                <h1 class="admin-page-title">Document Details: #{{ $document->id }}</h1>
                <p class="admin-page-subtitle">Complete document information and admin actions</p>
            </div>
            <a href="{{ route('admin.documents.index') }}" class="admin-btn admin-btn-secondary">
                Back to Documents
            </a>
        </div>

        @if(session('success') || session('warning') || session('error') || $errors->any())
            <div class="admin-doc-feedback-stack">
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

        <div class="admin-content admin-doc-content admin-doc-layout">
            <div class="admin-doc-main-column">
                <!-- Basic Information -->
                <div class="admin-card admin-doc-card">
                    <div class="admin-card-body">
                        <h3 class="admin-section-title"><i class="fas fa-info-circle"></i> Basic Information</h3>
                        <div class="admin-doc-grid admin-doc-grid-2">
                            <div class="admin-doc-item">
                                <span class="admin-info-label">Document ID</span>
                                <span class="admin-info-value admin-copyable-row admin-tech-pill">
                                    <span class="admin-tech-value">{{ $document->document_id ?? '-' }}</span>
                                    @if($document->document_id)
                                        <button type="button" class="admin-copy-btn" onclick="copyValue(this, @js($document->document_id))" aria-label="Copy Document ID" title="Copy Document ID">
                                            <i class="far fa-copy"></i>
                                        </button>
                                    @endif
                                </span>
                            </div>
                            <div class="admin-doc-item">
                                <span class="admin-info-label">Status</span>
                                <span class="admin-badge admin-status-pill admin-badge-{{ $document->verification_status === 'completed' ? 'success' : ($document->verification_status === 'pending' ? 'warning' : ($document->verification_status === 'rejected' ? 'danger' : 'info')) }}">
                                    @if($document->verification_status === 'completed')
                                        <i class="fas fa-check-circle" aria-hidden="true"></i>
                                    @elseif($document->verification_status === 'pending')
                                        <i class="fas fa-clock" aria-hidden="true"></i>
                                    @elseif($document->verification_status === 'rejected')
                                        <i class="fas fa-times-circle" aria-hidden="true"></i>
                                    @else
                                        <i class="fas fa-info-circle" aria-hidden="true"></i>
                                    @endif
                                    {{ ucfirst($document->verification_status) }}
                                </span>
                            </div>
                            <div class="admin-doc-item">
                                <span class="admin-info-label">Document Type</span>
                                <span class="admin-info-value">{{ str_replace('_', ' ', ucfirst($document->document_type)) }}</span>
                            </div>
                            <div class="admin-doc-item">
                                <span class="admin-info-label">Processed By</span>
                                <span class="admin-info-value">{{ $document->processedBy?->name ?? $document->user?->name ?? '-' }}</span>
                            </div>
                            <div class="admin-doc-item admin-doc-item-wide">
                                <span class="admin-info-label">Title</span>
                                <span class="admin-info-value">{{ $document->title ?? '-' }}</span>
                            </div>
                            <div class="admin-doc-item">
                                <span class="admin-info-label">Created At</span>
                                <span class="admin-info-value">{{ $document->created_at->format('M d, Y H:i:s') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Blockchain Information -->
                @if($document->blockchain_status)
                    <div class="admin-card admin-doc-card">
                        <div class="admin-card-body">
                            <h3 class="admin-section-title"><i class="fas fa-link"></i> Blockchain Information</h3>
                            <div class="admin-doc-grid admin-doc-grid-2 admin-blockchain-grid">
                                <div class="admin-doc-item">
                                    <span class="admin-info-label">Status</span>
                                    <span class="admin-badge admin-status-pill admin-badge-{{ $document->blockchain_status === 'confirmed' ? 'success' : ($document->blockchain_status === 'pending' ? 'warning' : ($document->blockchain_status === 'failed' ? 'danger' : 'info')) }}">
                                        @if($document->blockchain_status === 'confirmed')
                                            <i class="fas fa-check-circle" aria-hidden="true"></i>
                                        @elseif($document->blockchain_status === 'pending')
                                            <i class="fas fa-clock" aria-hidden="true"></i>
                                        @elseif($document->blockchain_status === 'failed')
                                            <i class="fas fa-times-circle" aria-hidden="true"></i>
                                        @else
                                            <i class="fas fa-info-circle" aria-hidden="true"></i>
                                        @endif
                                        {{ ucfirst($document->blockchain_status) }}
                                    </span>
                                </div>
                                @if($document->blockchain_block_number)
                                    <div class="admin-doc-item">
                                        <span class="admin-info-label">Block Number</span>
                                        <span class="admin-info-value">
                                            <a href="{{ route('admin.blockchain.transaction-details', $document) }}" class="admin-link admin-link-primary admin-tech-link admin-block-number-link">
                                                <i class="fas fa-cube" aria-hidden="true"></i>
                                                {{ $document->blockchain_block_number }}
                                            </a>
                                        </span>
                                    </div>
                                @endif
                                @if($document->blockchain_tx_hash)
                                    <div class="admin-doc-item admin-doc-item-wide admin-blockchain-hash">
                                        <span class="admin-info-label">Transaction Hash</span>
                                        <span class="admin-info-value admin-copyable-row admin-tech-pill">
                                            <a href="{{ route('admin.blockchain.transaction-details', $document) }}" class="admin-link admin-link-primary admin-tech-link admin-tech-value">{{ $document->blockchain_tx_hash }}</a>
                                            <button type="button" class="admin-copy-btn" onclick="copyValue(this, @js($document->blockchain_tx_hash))" aria-label="Copy Transaction Hash" title="Copy Transaction Hash">
                                                <i class="far fa-copy"></i>
                                            </button>
                                        </span>
                                    </div>
                                @endif
                                @if($document->blockchain_confirmed_at)
                                    <div class="admin-doc-item">
                                        <span class="admin-info-label">Confirmed At</span>
                                        <span class="admin-info-value">{{ $document->blockchain_confirmed_at->format('M d, Y H:i:s') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                <!-- File Information -->
                <div class="admin-card admin-doc-card">
                    <div class="admin-card-body">
                        <h3 class="admin-section-title"><i class="fas fa-file-alt"></i> File Information</h3>
                        <div class="admin-doc-grid admin-doc-grid-2 admin-file-grid">
                            <div class="admin-doc-item admin-doc-item-wide">
                                <span class="admin-info-label">Original Filename</span>
                                <span class="admin-info-value admin-filename">{{ $document->original_filename ?? '-' }}</span>
                            </div>
                            <div class="admin-doc-item admin-file-metric">
                                <span class="admin-info-label">File Size</span>
                                <span class="admin-info-value"><i class="fas fa-database" aria-hidden="true"></i> {{ $document->file_size ? number_format($document->file_size / 1024, 2) . ' KB' : '-' }}</span>
                            </div>
                            <div class="admin-doc-item admin-file-metric">
                                <span class="admin-info-label">MIME Type</span>
                                <span class="admin-info-value"><i class="fas fa-file-code" aria-hidden="true"></i> {{ $document->file_mime_type ?? '-' }}</span>
                            </div>
                            <div class="admin-doc-item admin-ocr-item">
                                <span class="admin-info-label">OCR Confidence</span>
                                <span class="admin-info-value">{{ $document->ocr_confidence ? $document->ocr_confidence . '%' : '-' }}</span>
                                @if($document->ocr_confidence)
                                    <div class="admin-ocr-meter" role="progressbar" aria-label="OCR Confidence" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ max(0, min(100, (float) $document->ocr_confidence)) }}">
                                        <span class="admin-ocr-meter-fill" style="width: {{ max(0, min(100, (float) $document->ocr_confidence)) }}%;"></span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <aside class="admin-doc-side-column">
                <!-- Intervention Status -->
                @if($document->isLocked() || $document->isArchived() || $document->isFlagged())
                    <div class="admin-callout admin-callout-warning admin-doc-interventions">
                        <h3 class="admin-section-title"><i class="fas fa-exclamation-triangle"></i> Document Interventions</h3>

                        @if($document->isLocked())
                            <div class="admin-intervention admin-intervention-lock">
                                <div class="admin-intervention-head">
                                    <i class="fas fa-lock admin-text-danger" style="margin-right: 0.5rem;"></i>
                                    <span class="admin-font-semibold admin-text-danger">LOCKED</span>
                                </div>
                                <div class="admin-doc-grid admin-doc-grid-2 admin-intervention-grid">
                                    <div class="admin-doc-item">
                                        <span class="admin-info-label">Locked By:</span>
                                        <span class="admin-info-value">{{ $document->lockedByUser?->name ?? '-' }}</span>
                                    </div>
                                    <div class="admin-doc-item">
                                        <span class="admin-info-label">Locked At:</span>
                                        <span class="admin-info-value">{{ $document->locked_at?->format('M d, Y H:i:s') }}</span>
                                    </div>
                                </div>
                                <div class="admin-intervention-note-wrap">
                                    <span class="admin-info-label">Reason:</span>
                                    <p class="admin-text-sm admin-intervention-note">{{ $document->lock_reason }}</p>
                                </div>
                            </div>
                        @endif

                        @if($document->isArchived())
                            <div class="admin-intervention admin-intervention-archive">
                                <div class="admin-intervention-head">
                                    <i class="fas fa-archive admin-text-muted" style="margin-right: 0.5rem;"></i>
                                    <span class="admin-font-semibold">ARCHIVED</span>
                                </div>
                                <div class="admin-doc-grid admin-doc-grid-2 admin-intervention-grid">
                                    <div class="admin-doc-item">
                                        <span class="admin-info-label">Archived By:</span>
                                        <span class="admin-info-value">{{ $document->archivedByUser?->name ?? '-' }}</span>
                                    </div>
                                    <div class="admin-doc-item">
                                        <span class="admin-info-label">Archived At:</span>
                                        <span class="admin-info-value">{{ $document->archived_at?->format('M d, Y H:i:s') }}</span>
                                    </div>
                                </div>
                                <div class="admin-intervention-note-wrap">
                                    <span class="admin-info-label">Reason:</span>
                                    <p class="admin-text-sm admin-intervention-note">{{ $document->archive_reason }}</p>
                                </div>
                            </div>
                        @endif

                        @if($document->isFlagged())
                            <div class="admin-intervention admin-intervention-flag">
                                <div class="admin-intervention-head">
                                    <i class="fas fa-flag admin-text-warning" style="margin-right: 0.5rem;"></i>
                                    <span class="admin-font-semibold" style="color: #9a3412;">FLAGGED FOR REVIEW</span>
                                </div>
                                <div class="admin-doc-grid admin-doc-grid-2 admin-intervention-grid">
                                    <div class="admin-doc-item">
                                        <span class="admin-info-label">Flagged By:</span>
                                        <span class="admin-info-value">{{ $document->flaggedByUser?->name ?? '-' }}</span>
                                    </div>
                                    <div class="admin-doc-item">
                                        <span class="admin-info-label">Flagged At:</span>
                                        <span class="admin-info-value">{{ $document->flagged_at?->format('M d, Y H:i:s') }}</span>
                                    </div>
                                </div>
                                <div class="admin-intervention-note-wrap">
                                    <span class="admin-info-label">Notes:</span>
                                    <p class="admin-text-sm admin-intervention-note">{{ $document->flag_notes }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Admin Actions -->
                <div class="admin-card admin-doc-card admin-doc-actions-card">
                    <div class="admin-card-body">
                        <h3 class="admin-section-title"><i class="fas fa-cogs"></i> Admin Actions</h3>
                        <div class="admin-actions-grid">
                            <!-- Lock/Unlock -->
                            <div class="admin-action-tile admin-action-lock">
                                @if($document->isLocked())
                                    <form action="{{ route('admin.documents.unlock', $document) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="admin-btn admin-btn-success admin-action-btn" style="width: 100%;">
                                            <i class="fas fa-lock-open"></i> Unlock Document
                                        </button>
                                    </form>
                                @else
                                    <button onclick="showLockModal()" class="admin-btn admin-btn-danger admin-action-btn" style="width: 100%;">
                                        <i class="fas fa-lock"></i> Lock Document
                                    </button>
                                @endif
                            </div>

                            <!-- Archive/Unarchive -->
                            <div class="admin-action-tile admin-action-archive">
                                @if($document->isArchived())
                                    <form action="{{ route('admin.documents.unarchive', $document) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="admin-btn admin-btn-success admin-action-btn" style="width: 100%;">
                                            <i class="fas fa-box-open"></i> Unarchive Document
                                        </button>
                                    </form>
                                @else
                                    <button onclick="showArchiveModal()" class="admin-btn admin-btn-secondary admin-action-btn" style="width: 100%;">
                                        <i class="fas fa-archive"></i> Archive Document
                                    </button>
                                @endif
                            </div>

                            <!-- Flag/Unflag -->
                            <div class="admin-action-tile admin-action-flag">
                                @if($document->isFlagged())
                                    <form action="{{ route('admin.documents.unflag', $document) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="admin-btn admin-btn-success admin-action-btn" style="width: 100%;">
                                            <i class="fas fa-check"></i> Remove Flag
                                        </button>
                                    </form>
                                @else
                                    <button onclick="showFlagModal()" class="admin-btn admin-btn-warning admin-action-btn" style="width: 100%;">
                                        <i class="fas fa-flag"></i> Flag Document
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>


    <!-- Lock Modal -->
    <div id="lockModal" class="admin-modal-overlay" style="display:none;">
        <div class="admin-modal">
            <div class="admin-modal-body">
                <h3 class="admin-section-title" style="margin-bottom: 1rem;"><i class="fas fa-lock"></i> Lock Document</h3>
                <form method="POST" action="{{ route('admin.documents.lock', $document) }}">
                    @csrf
                    <div class="admin-form-group">
                        <label for="lock_reason" class="admin-form-label">
                            Lock Reason (minimum 20 characters) *
                        </label>
                        <textarea name="reason" id="lock_reason" rows="3" required minlength="20" maxlength="500"
                            class="admin-form-input"
                            placeholder="Explain why this document is being locked..."></textarea>
                    </div>
                    <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                        <button type="button" onclick="closeLockModal()" 
                            class="admin-btn admin-btn-secondary">
                            Cancel
                        </button>
                        <button type="submit" 
                            class="admin-btn admin-btn-danger">
                            Lock Document
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Archive Modal -->
    <div id="archiveModal" class="admin-modal-overlay" style="display:none;">
        <div class="admin-modal">
            <div class="admin-modal-body">
                <h3 class="admin-section-title" style="margin-bottom: 1rem;"><i class="fas fa-archive"></i> Archive Document</h3>
                <form method="POST" action="{{ route('admin.documents.archive', $document) }}">
                    @csrf
                    <div class="admin-form-group">
                        <label for="archive_reason" class="admin-form-label">
                            Archive Reason (minimum 20 characters) *
                        </label>
                        <textarea name="reason" id="archive_reason" rows="3" required minlength="20" maxlength="500"
                            class="admin-form-input"
                            placeholder="Explain why this document is being archived..."></textarea>
                    </div>
                    <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                        <button type="button" onclick="closeArchiveModal()" 
                            class="admin-btn admin-btn-secondary">
                            Cancel
                        </button>
                        <button type="submit" 
                            class="admin-btn admin-btn-secondary">
                            Archive Document
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Flag Modal -->
    <div id="flagModal" class="admin-modal-overlay" style="display:none;">
        <div class="admin-modal">
            <div class="admin-modal-body">
                <h3 class="admin-section-title" style="margin-bottom: 1rem;"><i class="fas fa-flag"></i> Flag Document for Review</h3>
                <form method="POST" action="{{ route('admin.documents.flag', $document) }}">
                    @csrf
                    <div class="admin-form-group">
                        <label for="flag_notes" class="admin-form-label">
                            Flag Notes (minimum 10 characters) *
                        </label>
                        <textarea name="notes" id="flag_notes" rows="3" required minlength="10" maxlength="500"
                            class="admin-form-input"
                            placeholder="Describe what needs review or attention..."></textarea>
                    </div>
                    <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                        <button type="button" onclick="closeFlagModal()" 
                            class="admin-btn admin-btn-secondary">
                            Cancel
                        </button>
                        <button type="submit" 
                            class="admin-btn admin-btn-danger">
                            Flag Document
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showLockModal() { document.getElementById('lockModal').style.display = 'flex'; }
        function closeLockModal() { document.getElementById('lockModal').style.display = 'none'; }
        
        function showArchiveModal() { document.getElementById('archiveModal').style.display = 'flex'; }
        function closeArchiveModal() { document.getElementById('archiveModal').style.display = 'none'; }
        
        function showFlagModal() { document.getElementById('flagModal').style.display = 'flex'; }
        function closeFlagModal() { document.getElementById('flagModal').style.display = 'none'; }

        function copyValue(button, value) {
            if (!value) return;

            var text = String(value);

            function markCopied() {
                button.classList.add('is-copied');
                setTimeout(function () {
                    button.classList.remove('is-copied');
                }, 900);
            }

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(markCopied).catch(function () {
                    fallbackCopy(text, markCopied);
                });
                return;
            }

            fallbackCopy(text, markCopied);
        }

        function fallbackCopy(text, done) {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.setAttribute('readonly', '');
            ta.style.position = 'absolute';
            ta.style.left = '-9999px';
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            done();
        }
    </script>

@endsection

@push('styles')
<style>
    .admin-doc-content {
        gap: 1rem;
    }

    .admin-doc-feedback-stack {
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

    .admin-doc-layout {
        display: grid;
        gap: 1rem;
        grid-template-columns: minmax(0, 1.6fr) minmax(300px, 0.9fr);
        align-items: start;
    }

    .admin-doc-main-column,
    .admin-doc-side-column {
        display: grid;
        gap: 1rem;
    }

    .admin-doc-card {
        border: 1px solid var(--admin-border-light);
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.06);
    }

    .admin-doc-card .admin-card-body {
        padding: 1.1rem 1.15rem;
    }

    .admin-doc-grid {
        display: grid;
        gap: 0.85rem;
    }

    .admin-doc-grid-2 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .admin-doc-item {
        display: flex;
        flex-direction: column;
        gap: 0.28rem;
        min-width: 0;
    }

    .admin-doc-item-wide {
        grid-column: 1 / -1;
    }

    .admin-doc-view .admin-info-label {
        font-size: 0.73rem;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #64748b;
    }

    .admin-doc-view .admin-info-value {
        font-size: 0.99rem;
        font-weight: 700;
        line-height: 1.4;
        color: var(--admin-text-primary);
    }

    .admin-tech-pill {
        border: 1px solid #dbe5f3;
        background: #f8fbff;
        border-radius: 0.7rem;
        padding: 0.48rem 0.58rem;
        width: 100%;
        justify-content: space-between;
    }

    .admin-tech-value {
        font-family: "Consolas", "SFMono-Regular", Menlo, Monaco, monospace;
        font-size: 0.83rem;
        line-height: 1.45;
        letter-spacing: 0.01em;
        word-break: break-all;
        color: #1e293b;
    }

    .admin-copyable-row {
        display: inline-flex;
        align-items: flex-start;
        gap: 0.55rem;
    }

    .admin-copy-btn {
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #334155;
        border-radius: 0.45rem;
        width: 1.8rem;
        height: 1.8rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
        flex-shrink: 0;
        margin-top: 0.05rem;
    }

    .admin-copy-btn:hover,
    .admin-copy-btn:focus-visible {
        background: #eef2ff;
        border-color: #c7d2fe;
        color: #3730a3;
        outline: none;
    }

    .admin-copy-btn.is-copied {
        background: #dcfce7;
        border-color: #86efac;
        color: #166534;
    }

    .admin-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.42rem;
        width: fit-content;
        font-weight: 700;
        letter-spacing: 0.01em;
        border-radius: 999px;
        padding: 0.26rem 0.66rem;
    }

    .admin-status-pill i {
        font-size: 0.82rem;
    }

    .admin-blockchain-grid {
        grid-auto-flow: dense;
    }

    .admin-blockchain-hash {
        border: 1px solid #dbe5f3;
        border-radius: var(--admin-radius-md);
        background: #f8fbff;
        padding: 0.6rem 0.7rem;
    }

    .admin-tech-link {
        text-decoration: none;
    }

    .admin-tech-link:hover {
        text-decoration: underline;
    }

    .admin-block-number-link {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-weight: 700;
    }

    .admin-file-grid {
        align-items: start;
    }

    .admin-filename {
        word-break: break-word;
    }

    .admin-file-metric .admin-info-value {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
    }

    .admin-file-metric .admin-info-value i {
        color: #64748b;
    }

    .admin-ocr-item .admin-info-value {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }

    .admin-ocr-meter {
        margin-top: 0.22rem;
        width: 100%;
        max-width: 280px;
        height: 0.56rem;
        border-radius: 999px;
        background: #e2e8f0;
        overflow: hidden;
        border: 1px solid #cbd5e1;
    }

    .admin-ocr-meter-fill {
        display: block;
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, #ef4444 0%, #f59e0b 45%, #16a34a 100%);
    }

    .admin-doc-interventions {
        border-width: 1px;
    }

    .admin-doc-interventions .admin-section-title {
        color: #92400e;
        margin-bottom: 0.8rem;
    }

    .admin-intervention {
        margin-bottom: 0.7rem;
        padding: 0.85rem;
        border-radius: var(--admin-radius-lg);
        border: 1px solid var(--admin-border-light);
    }

    .admin-intervention:last-child {
        margin-bottom: 0;
    }

    .admin-intervention-lock {
        background: var(--admin-danger-bg, #fef2f2);
        border-color: var(--admin-danger-border, #fecaca);
    }

    .admin-intervention-archive {
        background: var(--admin-bg-muted, #f3f4f6);
    }

    .admin-intervention-flag {
        background: #fff7ed;
        border-color: #fed7aa;
    }

    .admin-intervention-head {
        display: flex;
        align-items: center;
        margin-bottom: 0.45rem;
    }

    .admin-intervention-grid {
        gap: 0.55rem;
    }

    .admin-intervention-note-wrap {
        margin-top: 0.5rem;
    }

    .admin-intervention-note {
        background: #ffffff;
        padding: 0.55rem 0.65rem;
        border-radius: var(--admin-radius-md);
        margin-top: 0.25rem;
        border: 1px solid var(--admin-border-light);
    }

    .admin-doc-side-column {
        position: sticky;
        top: 1rem;
    }

    .admin-actions-grid {
        display: grid;
        gap: 0.8rem;
    }

    .admin-action-tile {
        padding: 0.62rem;
        border-radius: 0.72rem;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
    }

    .admin-action-lock {
        border-color: #fecaca;
        background: #fff5f5;
    }

    .admin-action-archive {
        border-color: #cbd5e1;
        background: #f8fafc;
    }

    .admin-action-flag {
        border-color: #fed7aa;
        background: #fffbeb;
    }

    .admin-action-btn {
        min-height: 2.45rem;
        font-weight: 700;
    }

    .admin-doc-actions-card .admin-btn-danger {
        box-shadow: 0 0 0 1px rgba(220, 38, 38, 0.15) inset;
    }

    .admin-doc-actions-card .admin-btn-warning {
        box-shadow: 0 0 0 1px rgba(245, 158, 11, 0.2) inset;
    }

    .admin-doc-actions-card .admin-btn-secondary {
        box-shadow: 0 0 0 1px rgba(100, 116, 139, 0.12) inset;
    }

    @media (max-width: 1200px) {
        .admin-doc-layout {
            grid-template-columns: 1fr;
        }

        .admin-doc-side-column {
            position: static;
        }
    }

    @media (max-width: 992px) {
        .admin-doc-grid-2 {
            grid-template-columns: 1fr;
        }

        .admin-doc-item-wide {
            grid-column: auto;
        }

        .admin-tech-pill {
            align-items: center;
        }

        .admin-tech-value {
            max-width: calc(100vw - 10rem);
        }
    }
</style>
@endpush