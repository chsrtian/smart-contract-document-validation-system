@extends('layouts.supervisor')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/supervisor-audit-log.css') }}">
@endpush

@section('content')
<div class="audit-container">
    
    <!-- Page Header -->
    <div class="audit-header">
        <nav class="audit-breadcrumb">
            <a href="{{ route('corrections.approval.dashboard') }}">Dashboard</a>
            <i class="fas fa-chevron-right breadcrumb-separator"></i>
            <span class="breadcrumb-current">Blockchain Audit Log</span>
        </nav>
        
        <div class="audit-title-row">
            <div class="audit-title-content">
                <h1>Blockchain Audit Log</h1>
                <p>Complete record of all corrections anchored to the blockchain</p>
            </div>
            
            <div class="audit-total-badge">
                <i class="fas fa-link"></i>
                <span>{{ $totalRecords ?? 0 }} Anchored Records</span>
            </div>
        </div>
    </div>

    <!-- Blockchain Info Banner (High Contrast) -->
    <div class="audit-banner">
        <div class="audit-banner-icon">
            <i class="fas fa-shield-alt"></i>
        </div>
        <div class="audit-banner-content">
            <h3>Immutable Correction Records</h3>
            <p>Each correction record below has been cryptographically signed and anchored to the blockchain. These records are permanent and cannot be altered, providing a tamper-proof audit trail for all document corrections.</p>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="audit-filters">
        <form action="{{ route('corrections.approval.audit-log') }}" method="GET" class="filter-form">
            
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

            <!-- Transaction Hash Search -->
            <div class="filter-group filter-group--wide">
                <label for="tx_hash" class="filter-label">Transaction Hash</label>
                <input type="text" name="tx_hash" id="tx_hash" value="{{ request('tx_hash') }}"
                       placeholder="Search by hash (e.g., 0x8fdb...)"
                       class="filter-input filter-input--mono">
            </div>

            <!-- Date Range -->
            <div class="filter-group">
                <label for="date_from" class="filter-label">Start Date</label>
                <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" class="filter-input">
            </div>

            <div class="filter-group">
                <label for="date_to" class="filter-label">End Date</label>
                <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" class="filter-input">
            </div>

            <!-- Filter Actions -->
            <div class="filter-actions">
                <button type="submit" class="filter-btn filter-btn--apply">
                    <i class="fas fa-search"></i>
                    Search
                </button>
                @if(request()->hasAny(['document_type', 'tx_hash', 'date_from', 'date_to']))
                    <a href="{{ route('corrections.approval.audit-log') }}" class="filter-btn filter-btn--clear">
                        <i class="fas fa-trash-alt"></i>
                        Clear
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Audit Records List -->
    @if($records->count() > 0)
        <div class="audit-records">
            @foreach($records as $record)
                <div class="audit-card">
                    
                    <!-- Card Header with Verification Stamp -->
                    <div class="audit-card-header">
                        <div class="audit-card-title">
                            <div class="audit-card-icon">
                                <i class="fas fa-cube"></i>
                            </div>
                            <div class="audit-card-title-text">
                                <h3>Correction Record #{{ $record->id }}</h3>
                                <span>
                                    @if($record->blockchain_confirmed_at)
                                        Anchored {{ $record->blockchain_confirmed_at->diffForHumans() }}
                                    @else
                                        <span class="status-pending"><i class="fas fa-clock"></i> Pending Anchor</span>
                                    @endif
                                </span>
                            </div>
                        </div>
                        
                        <!-- Large Verification Stamp -->
                        @if($record->blockchain_status === 'confirmed')
                            <div class="verified-stamp">
                                <i class="fas fa-check-circle"></i>
                                BLOCKCHAIN VERIFIED
                            </div>
                        @else
                            <div class="verified-stamp verified-stamp--pending">
                                <i class="fas fa-hourglass-half"></i>
                                PENDING VERIFICATION
                            </div>
                        @endif
                    </div>

                    <!-- Card Body -->
                    <div class="audit-card-body">
                        <div class="audit-card-grid">
                            
                            <!-- Left Column: Human-Readable Info -->
                            <div class="audit-section">
                                
                                <!-- Document Information -->
                                <div>
                                    <div class="audit-section-header">
                                        <i class="fas fa-file-alt"></i>
                                        Document Information
                                    </div>
                                    <div class="audit-info-box">
                                        <div class="audit-info-row">
                                            <span class="audit-info-label">Type</span>
                                            <span class="audit-info-value">{{ $record->scan->document_type_name ?? 'Document' }}</span>
                                        </div>
                                        <div class="audit-info-row">
                                            <span class="audit-info-label">Document ID</span>
                                            <span class="audit-info-value audit-info-value--highlight">{{ $record->scan->document_id ?? '—' }}</span>
                                        </div>
                                        @if($record->scan && $record->scan->registry_number)
                                            <div class="audit-info-row">
                                                <span class="audit-info-label">Registry Number</span>
                                                <span class="audit-info-value">{{ $record->scan->registry_number }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Visual Diff: Correction Applied -->
                                <div>
                                    <div class="audit-section-header">
                                        <i class="fas fa-exchange-alt"></i>
                                        Correction Applied
                                    </div>
                                    <div class="visual-diff">
                                        <div class="visual-diff-field">
                                            <i class="fas fa-tag"></i>
                                            {{ $record->field_display_name }}
                                        </div>
                                        <div class="visual-diff-boxes">
                                            <div class="diff-box diff-box--before">
                                                <span class="diff-box-label">Previous Value</span>
                                                <span class="diff-box-value {{ empty($record->previous_value) ? 'diff-box-empty' : '' }}">
                                                    {{ $record->previous_value ?: '(empty)' }}
                                                </span>
                                            </div>
                                            <div class="diff-arrow">
                                                <i class="fas fa-arrow-right"></i>
                                            </div>
                                            <div class="diff-box diff-box--after">
                                                <span class="diff-box-label">New Value</span>
                                                <span class="diff-box-value">{{ $record->new_value }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Approval Details -->
                                <div>
                                    <div class="audit-section-header">
                                        <i class="fas fa-user-check"></i>
                                        Approval Details
                                    </div>
                                    <div class="audit-info-box">
                                        <div class="audit-info-row">
                                            <span class="audit-info-label">Requested By</span>
                                            <span class="audit-info-value">
                                                @if($record->staff)
                                                    {{ $record->staff->name }}
                                                @elseif($record->correctionRequest && $record->correctionRequest->requester)
                                                    {{ $record->correctionRequest->requester->name }}
                                                @else
                                                    <span class="status-unavailable">—</span>
                                                @endif
                                            </span>
                                        </div>
                                        <div class="audit-info-row">
                                            <span class="audit-info-label">Approved By</span>
                                            <span class="audit-info-value">
                                                @if($record->supervisor)
                                                    {{ $record->supervisor->name }}
                                                @elseif($record->correctionRequest && $record->correctionRequest->reviewer)
                                                    {{ $record->correctionRequest->reviewer->name }}
                                                @else
                                                    <span class="status-pending"><i class="fas fa-clock"></i> Pending</span>
                                                @endif
                                            </span>
                                        </div>
                                        @if($record->correctionRequest && $record->correctionRequest->reviewed_at)
                                            <div class="audit-info-row">
                                                <span class="audit-info-label">Approved At</span>
                                                <span class="audit-info-value">{{ $record->correctionRequest->reviewed_at->format('M d, Y · g:i A') }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column: Technical / Blockchain Data -->
                            <div class="audit-section">
                                <div class="audit-section-header">
                                    <i class="fas fa-link"></i>
                                    Blockchain Data
                                </div>
                                
                                <div class="tx-hash-container">
                                    
                                    <!-- Correction Transaction Hash -->
                                    <div class="tx-hash-box tx-hash-box--correction">
                                        <span class="tx-hash-label">
                                            <i class="fas fa-cube"></i>
                                            Correction Transaction
                                        </span>
                                        @if($record->correction_tx_hash)
                                            <div class="tx-hash-pill">
                                                <span class="tx-hash-value" title="{{ $record->correction_tx_hash }}">
                                                    {{ Str::limit($record->correction_tx_hash, 20, '...' . substr($record->correction_tx_hash, -8)) }}
                                                </span>
                                                <button type="button" class="tx-hash-copy" onclick="copyHash('{{ $record->correction_tx_hash }}')" title="Copy full hash">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </div>
                                            @if($record->blockchain_confirmed_at)
                                                <span class="tx-hash-timestamp">
                                                    <i class="fas fa-clock"></i>
                                                    {{ $record->blockchain_confirmed_at->format('M d, Y · g:i:s A') }}
                                                </span>
                                            @endif
                                        @else
                                            <span class="status-pending"><i class="fas fa-hourglass-half"></i> Awaiting Confirmation</span>
                                        @endif
                                    </div>

                                    <!-- Original Document Transaction (Reference) -->
                                    @if($record->reference_tx_hash)
                                        <div class="tx-hash-box tx-hash-box--original">
                                            <span class="tx-hash-label">
                                                <i class="fas fa-file-contract"></i>
                                                References Original Document
                                            </span>
                                            <div class="tx-hash-pill">
                                                <span class="tx-hash-value" title="{{ $record->reference_tx_hash }}">
                                                    {{ Str::limit($record->reference_tx_hash, 20, '...' . substr($record->reference_tx_hash, -8)) }}
                                                </span>
                                                <button type="button" class="tx-hash-copy" onclick="copyHash('{{ $record->reference_tx_hash }}')" title="Copy full hash">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Correction Data Hash -->
                                    @if($record->correction_document_hash)
                                        <div class="tx-hash-box tx-hash-box--data">
                                            <span class="tx-hash-label">
                                                <i class="fas fa-fingerprint"></i>
                                                Correction Data Hash
                                            </span>
                                            <div class="tx-hash-pill">
                                                <span class="tx-hash-value" title="{{ $record->correction_document_hash }}">
                                                    {{ Str::limit($record->correction_document_hash, 20, '...' . substr($record->correction_document_hash, -8)) }}
                                                </span>
                                                <button type="button" class="tx-hash-copy" onclick="copyHash('{{ $record->correction_document_hash }}')" title="Copy full hash">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endif

                                    @php
                                        $correctionMetadata = is_array($record->blockchain_metadata) ? $record->blockchain_metadata : [];
                                        $hasBlockNumber = array_key_exists('block_number', $correctionMetadata)
                                            && $correctionMetadata['block_number'] !== null;
                                        $blockNumberVerified = $correctionMetadata['block_number_verified'] ?? null;
                                    @endphp

                                    <!-- Block Number from verified metadata -->
                                    @if($hasBlockNumber)
                                        <div class="block-number-badge">
                                            <span class="block-number-label">
                                                <i class="fas fa-layer-group"></i>
                                                Block Number
                                            </span>
                                            <span class="block-number-value">#{{ number_format((int) $correctionMetadata['block_number']) }}</span>
                                        </div>
                                    @elseif($blockNumberVerified === false)
                                        <div class="block-number-badge">
                                            <span class="block-number-label">
                                                <i class="fas fa-layer-group"></i>
                                                Block Number
                                            </span>
                                            <span class="status-unavailable">Unavailable on current Ganache chain</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card Footer -->
                    <div class="audit-card-footer">
                        <span class="audit-card-footer-meta">
                            Record created: {{ $record->created_at?->format('M d, Y · g:i A') ?? '—' }}
                        </span>
                        @if($record->correctionRequest)
                            <a href="{{ route('corrections.approval.show', $record->correctionRequest) }}" class="audit-card-footer-action">
                                <i class="fas fa-eye"></i>
                                View Full Details
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        @if($records->hasPages())
            <div class="audit-pagination">
                {{ $records->withQueryString()->links() }}
            </div>
        @endif
    @else
        <!-- Empty State -->
        <div class="audit-empty-state">
            <div class="empty-state-illustration">
                <svg viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Background -->
                    <circle cx="100" cy="100" r="80" fill="var(--sv-primary-light)" opacity="0.3"/>
                    
                    <!-- Blockchain cubes -->
                    <rect x="40" y="80" width="35" height="35" rx="4" fill="var(--sv-bg-muted)" stroke="var(--sv-border-normal)" stroke-width="2"/>
                    <rect x="82" y="80" width="35" height="35" rx="4" fill="var(--sv-bg-muted)" stroke="var(--sv-border-normal)" stroke-width="2"/>
                    <rect x="124" y="80" width="35" height="35" rx="4" fill="var(--sv-bg-muted)" stroke="var(--sv-border-normal)" stroke-width="2"/>
                    
                    <!-- Chain links -->
                    <line x1="75" y1="97" x2="82" y2="97" stroke="var(--sv-primary)" stroke-width="3" stroke-linecap="round"/>
                    <line x1="117" y1="97" x2="124" y2="97" stroke="var(--sv-primary)" stroke-width="3" stroke-linecap="round"/>
                    
                    <!-- Lock icon in middle cube -->
                    <path d="M95 90 L95 85 Q99 80 104 85 L104 90" stroke="var(--sv-primary)" stroke-width="2" fill="none" stroke-linecap="round"/>
                    <rect x="92" y="90" width="15" height="12" rx="2" fill="var(--sv-primary)"/>
                    
                    <!-- Sparkles -->
                    <circle cx="50" cy="55" r="3" fill="var(--sv-primary)"/>
                    <circle cx="155" cy="60" r="2" fill="var(--sv-primary)"/>
                    <circle cx="145" cy="140" r="2.5" fill="var(--sv-primary)"/>
                </svg>
            </div>
            
            <div class="empty-state-content">
                @if(request()->hasAny(['document_type', 'tx_hash', 'date_from', 'date_to']))
                    <h3>No Records Found</h3>
                    <p>No blockchain records match your search criteria. Try adjusting your filters or searching with a different transaction hash.</p>
                    <a href="{{ route('corrections.approval.audit-log') }}" class="empty-state-action">
                        <i class="fas fa-times"></i>
                        Clear Search
                    </a>
                @else
                    <h3>No Blockchain Records Yet</h3>
                    <p>Once corrections are approved and anchored to the blockchain, they will appear here as immutable audit records.</p>
                    <a href="{{ route('corrections.approval.pending') }}" class="empty-state-action">
                        <i class="fas fa-inbox"></i>
                        Review Pending Requests
                    </a>
                @endif
            </div>
        </div>
    @endif

</div>

<!-- Copy Toast Notification -->
<div id="copy-toast" class="copy-toast">
    <i class="fas fa-check-circle"></i>
    Hash copied to clipboard
</div>
@endsection

@push('scripts')
<script>
    function copyHash(text) {
        navigator.clipboard.writeText(text).then(function() {
            // Show toast
            const toast = document.getElementById('copy-toast');
            toast.classList.add('show');
            
            // Hide after 2 seconds
            setTimeout(function() {
                toast.classList.remove('show');
            }, 2000);
        }).catch(function(err) {
            console.error('Failed to copy: ', err);
        });
    }
</script>
@endpush