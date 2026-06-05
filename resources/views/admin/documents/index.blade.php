@extends('layouts.admin')

@section('content')
    @php
        $sortColumn = (string) ($sort ?? request('sort', 'created_at'));
        $sortDirection = strtolower((string) ($direction ?? request('direction', 'desc')));
        $sortDirection = $sortDirection === 'asc' ? 'asc' : 'desc';

        $allowedPerPage = [10, 25, 50, 100];
        $selectedPerPage = (int) ($perPage ?? request('per_page', 25));
        if (!in_array($selectedPerPage, $allowedPerPage, true)) {
            $selectedPerPage = 25;
        }

        $summaryStatus = $statistics['by_status'] ?? [];
        $summaryType = $statistics['by_type'] ?? [];
        $summaryBlockchain = $statistics['by_blockchain_status'] ?? [];

        $lastUpdated = $lastUpdatedAt ?? now();
        $lastUpdatedLabel = $lastUpdated->format('M d, Y H:i:s');
        $lastUpdatedIso = $lastUpdated->toIso8601String();

        $queryBase = request()->query();
        $sortUrlFor = function (string $column) use ($queryBase, $sortColumn, $sortDirection) {
            $nextDirection = ($sortColumn === $column && $sortDirection === 'asc') ? 'desc' : 'asc';
            return route('admin.documents.index', array_merge($queryBase, ['sort' => $column, 'direction' => $nextDirection, 'page' => 1]));
        };

        $sortIconFor = function (string $column) use ($sortColumn, $sortDirection) {
            if ($sortColumn !== $column) {
                return 'fas fa-sort';
            }

            return $sortDirection === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down';
        };

        $sortAriaFor = function (string $column) use ($sortColumn, $sortDirection) {
            if ($sortColumn !== $column) {
                return 'none';
            }

            return $sortDirection === 'asc' ? 'ascending' : 'descending';
        };

        $paginationQuery = request()->except('page');
        $pageUrl = function (int $page) use ($paginationQuery) {
            return route('admin.documents.index', array_merge($paginationQuery, ['page' => $page]));
        };

        $currentPage = $documents->currentPage();
        $lastPage = max(1, $documents->lastPage());
        $startPage = max(1, $currentPage - 2);
        $endPage = min($lastPage, $currentPage + 2);

        if (($endPage - $startPage) < 4) {
            $startPage = max(1, min($startPage, $endPage - 4));
            $endPage = min($lastPage, max($endPage, $startPage + 4));
        }

        $statusBadgeClass = function (?string $status): string {
            return match ($status) {
                'completed' => 'do-status-badge--completed',
                'pending' => 'do-status-badge--pending',
                'rejected' => 'do-status-badge--rejected',
                default => 'do-status-badge--draft',
            };
        };

        $blockchainBadgeClass = function (?string $status): string {
            return match ($status) {
                'confirmed' => 'do-chain-badge--confirmed',
                'pending' => 'do-chain-badge--pending',
                'failed' => 'do-chain-badge--failed',
                default => 'do-chain-badge--not-submitted',
            };
        };

        $blockchainLabel = function (?string $status): string {
            return match ($status) {
                'confirmed' => 'Confirmed',
                'pending' => 'Pending',
                'failed' => 'Failed',
                default => 'Not Submitted',
            };
        };

        $statusIconFor = function (?string $status): string {
            return match ($status) {
                'completed' => 'fas fa-circle-check',
                'pending' => 'fas fa-clock',
                'rejected' => 'fas fa-circle-xmark',
                'draft' => 'fas fa-file-lines',
                default => 'fas fa-circle',
            };
        };

        $blockchainIconFor = function (?string $status): string {
            return match ($status) {
                'confirmed' => 'fas fa-link',
                'pending' => 'fas fa-clock',
                'failed' => 'fas fa-triangle-exclamation',
                default => 'fas fa-minus',
            };
        };

        $currentStatus = (string) request('status', '');

        $statusFilters = [
            'pending' => [
                'label' => 'Pending',
                'count' => (int) ($summaryStatus['pending'] ?? 0),
            ],
            'completed' => [
                'label' => 'Completed',
                'count' => (int) ($summaryStatus['completed'] ?? 0),
            ],
            'rejected' => [
                'label' => 'Rejected',
                'count' => (int) ($summaryStatus['rejected'] ?? 0),
            ],
            'draft' => [
                'label' => 'Draft',
                'count' => (int) ($summaryStatus['draft'] ?? 0),
            ],
        ];

        $visibleStatusFilters = array_filter($statusFilters, function (array $statusInfo): bool {
            return $statusInfo['count'] > 0;
        });

        $statusFilterUrl = function (?string $status = null) use ($queryBase) {
            $query = $queryBase;
            $query['page'] = 1;

            if ($status === null) {
                unset($query['status']);
            } else {
                $query['status'] = $status;
            }

            return route('admin.documents.index', $query);
        };
    @endphp

    <div class="admin-container do-page">
        <header class="admin-page-header do-header">
            <div>
                <h1 class="admin-page-title">Document Oversight</h1>
                <p class="admin-page-subtitle">Monitor, filter, and manage documents across processing and blockchain workflows.</p>
            </div>

            <div class="do-header-controls" aria-label="Document oversight controls">
                <div class="do-last-updated" role="status" aria-live="polite" aria-atomic="true">
                    <span class="do-last-updated-label">Last updated:</span>
                    <span class="do-last-updated-time">{{ $lastUpdatedLabel }}</span>
                    <span class="do-last-updated-relative" id="do-last-updated-relative">0 min ago</span>
                </div>

                <a href="{{ route('admin.documents.index', request()->query()) }}" class="admin-btn admin-btn-secondary" aria-label="Refresh document oversight data">
                    <i class="fas fa-rotate-right" aria-hidden="true"></i>
                    Refresh
                </a>
            </div>
        </header>

        <section class="do-section" aria-label="Document summary">
            <div class="do-summary-grid">
                <article class="admin-card do-summary-card" aria-label="Total documents summary">
                    <div class="admin-card-body">
                        <div class="do-summary-head">
                            <span class="do-summary-title">Total Documents</span>
                            <span class="do-added-badge">{{ number_format((int) ($statistics['today'] ?? 0)) }} added today</span>
                        </div>
                        <p class="do-summary-value">{{ number_format((int) ($statistics['total'] ?? 0)) }}</p>
                        <p class="do-summary-subtext">All records available for oversight.</p>
                    </div>
                </article>

                <article class="admin-card do-summary-card" aria-label="Status overview summary">
                    <div class="admin-card-body">
                        <div class="do-summary-head">
                            <span class="do-summary-title">Status Overview</span>
                        </div>

                        <div class="do-status-chip-grid" role="list" aria-label="Quick status filters">
                            @forelse($visibleStatusFilters as $statusKey => $statusInfo)
                                @if($statusInfo['count'] > 0)
                                    <a
                                        href="{{ $statusFilterUrl($statusKey) }}"
                                        role="listitem"
                                        class="do-status-chip do-status-chip--{{ $statusKey }} {{ $currentStatus === $statusKey ? 'is-active' : '' }}"
                                        aria-current="{{ $currentStatus === $statusKey ? 'page' : 'false' }}"
                                        aria-label="Filter documents by {{ $statusInfo['label'] }} status"
                                    >
                                        <span class="do-status-chip-icon" aria-hidden="true">
                                            <i class="{{ $statusIconFor($statusKey) }}"></i>
                                        </span>
                                        <span class="do-status-chip-label">{{ $statusInfo['label'] }}</span>
                                        <span class="do-status-chip-count">{{ number_format($statusInfo['count']) }}</span>
                                    </a>
                                @endif
                            @empty
                                <p class="do-summary-subtext">No documents have a non-zero status count yet.</p>
                            @endforelse
                        </div>
                    </div>
                </article>

                <article class="admin-card do-summary-card" aria-label="Type overview summary">
                    <div class="admin-card-body">
                        <div class="do-summary-head">
                            <span class="do-summary-title">Type Overview</span>
                        </div>

                        <div class="do-summary-list">
                            <div class="do-summary-list-row"><span>Birth</span><strong>{{ number_format((int) ($summaryType['birth_certificate'] ?? 0)) }}</strong></div>
                            <div class="do-summary-list-row"><span>Death</span><strong>{{ number_format((int) ($summaryType['death_certificate'] ?? 0)) }}</strong></div>
                            <div class="do-summary-list-row"><span>Marriage</span><strong>{{ number_format((int) ($summaryType['marriage_certificate'] ?? 0)) }}</strong></div>
                            <div class="do-summary-list-row"><span>CENOMAR</span><strong>{{ number_format((int) ($summaryType['cenomar'] ?? 0)) }}</strong></div>
                        </div>
                    </div>
                </article>

                <article class="admin-card do-summary-card" aria-label="Blockchain overview summary">
                    <div class="admin-card-body">
                        <div class="do-summary-head">
                            <span class="do-summary-title">Blockchain Overview</span>
                        </div>

                        <div class="do-summary-list">
                            <div class="do-summary-list-row"><span>Pending</span><strong class="do-color-pending">{{ number_format((int) ($summaryBlockchain['pending'] ?? 0)) }}</strong></div>
                            <div class="do-summary-list-row"><span>Confirmed</span><strong class="do-color-completed">{{ number_format((int) ($summaryBlockchain['confirmed'] ?? 0)) }}</strong></div>
                            <div class="do-summary-list-row"><span>Failed</span><strong class="do-color-rejected">{{ number_format((int) ($summaryBlockchain['failed'] ?? 0)) }}</strong></div>
                            <div class="do-summary-list-row"><span>Not Submitted</span><strong>{{ number_format((int) ($summaryBlockchain['not_submitted'] ?? 0)) }}</strong></div>
                        </div>
                    </div>
                </article>
            </div>
        </section>

        <section class="admin-filter-panel do-filter-panel" aria-label="Document filters">
            <form method="GET" action="{{ route('admin.documents.index') }}" aria-label="Document filter form">
                <div class="do-filter-grid do-filter-grid--row-1">
                    <div class="do-filter-field">
                        <label class="admin-form-label" for="do-status">Status</label>
                        <select name="status" id="do-status" class="admin-form-select" aria-label="Filter by document status">
                            <option value="">All Status</option>
                            <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                    </div>

                    <div class="do-filter-field">
                        <label class="admin-form-label" for="do-type">Document Type</label>
                        <select name="type" id="do-type" class="admin-form-select" aria-label="Filter by document type">
                            <option value="">All Types</option>
                            <option value="birth_certificate" {{ request('type') == 'birth_certificate' ? 'selected' : '' }}>Birth Certificate</option>
                            <option value="death_certificate" {{ request('type') == 'death_certificate' ? 'selected' : '' }}>Death Certificate</option>
                            <option value="marriage_certificate" {{ request('type') == 'marriage_certificate' ? 'selected' : '' }}>Marriage Certificate</option>
                            <option value="cenomar" {{ request('type') == 'cenomar' ? 'selected' : '' }}>CENOMAR</option>
                            <option value="affidavit" {{ request('type') == 'affidavit' ? 'selected' : '' }}>Affidavit</option>
                            <option value="ausf" {{ request('type') == 'ausf' ? 'selected' : '' }}>AUSF</option>
                            <option value="other" {{ request('type') == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>

                    <div class="do-filter-field">
                        <label class="admin-form-label" for="do-processor">Processor</label>
                        <select name="processor" id="do-processor" class="admin-form-select" aria-label="Filter by processor">
                            <option value="">All Processors</option>
                            @foreach($processors as $proc)
                                <option value="{{ $proc->id }}" {{ request('processor') == $proc->id ? 'selected' : '' }}>{{ $proc->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="do-filter-field do-filter-field--range">
                        <label class="admin-form-label">Document Date Range</label>
                        <div class="do-date-range" aria-label="Filter by document date range">
                            <input type="date" name="date_from" value="{{ request('date_from') }}" class="admin-form-input" aria-label="Start date">
                            <span class="do-range-separator" aria-hidden="true">to</span>
                            <input type="date" name="date_to" value="{{ request('date_to') }}" class="admin-form-input" aria-label="End date">
                        </div>
                    </div>
                </div>

                <div class="do-filter-grid do-filter-grid--row-2">
                    <div class="do-filter-field">
                        <label class="admin-form-label" for="do-blockchain-status">Blockchain Status</label>
                        <select name="blockchain_status" id="do-blockchain-status" class="admin-form-select" aria-label="Filter by blockchain status">
                            <option value="">All Blockchain Status</option>
                            <option value="pending" {{ request('blockchain_status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="confirmed" {{ request('blockchain_status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                            <option value="failed" {{ request('blockchain_status') == 'failed' ? 'selected' : '' }}>Failed</option>
                            <option value="not_submitted" {{ request('blockchain_status') == 'not_submitted' ? 'selected' : '' }}>Not Submitted</option>
                        </select>
                    </div>

                    <div class="do-filter-field">
                        <label class="admin-form-label" for="do-search">Search</label>
                        <div class="do-search-group">
                            <span class="do-search-icon" aria-hidden="true"><i class="fas fa-magnifying-glass"></i></span>
                            <input type="search" name="search" id="do-search" value="{{ request('search') }}" placeholder="ID, title, type..." class="admin-form-input do-search-input" aria-label="Search documents">
                        </div>
                    </div>

                    <div class="do-filter-field">
                        <label class="admin-form-label">Quick Filters</label>
                        <div class="do-chip-group" role="group" aria-label="Quick filter toggles">
                            <label class="do-chip-toggle">
                                <input type="checkbox" class="do-chip-toggle-input" name="flagged" value="1" {{ request('flagged') == '1' ? 'checked' : '' }}>
                                <span class="do-chip-toggle-label"><i class="fas fa-flag" aria-hidden="true"></i> Flagged</span>
                            </label>

                            <label class="do-chip-toggle">
                                <input type="checkbox" class="do-chip-toggle-input" name="locked" value="1" {{ request('locked') == '1' ? 'checked' : '' }}>
                                <span class="do-chip-toggle-label"><i class="fas fa-lock" aria-hidden="true"></i> Locked</span>
                            </label>
                        </div>
                    </div>

                    <div class="do-filter-field do-filter-field--actions">
                        <div class="do-filter-actions">
                            <div class="do-per-page-select-wrap">
                                <label class="admin-form-label" for="do-per-page">Items per page</label>
                                <select name="per_page" id="do-per-page" class="admin-form-select" aria-label="Select items per page">
                                    @foreach($allowedPerPage as $size)
                                        <option value="{{ $size }}" {{ $selectedPerPage === $size ? 'selected' : '' }}>{{ $size }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <button type="submit" class="admin-btn admin-btn-primary">
                                <i class="fas fa-filter" aria-hidden="true"></i>
                                Apply Filters
                            </button>
                            <a href="{{ route('admin.documents.index') }}" class="admin-btn admin-btn-secondary">Clear All</a>
                        </div>
                    </div>
                </div>
            </form>
        </section>

        <section class="admin-table-card do-table-card" aria-label="Documents data table section">
            <form id="do-bulk-form" method="POST" action="{{ route('admin.documents.bulk-action') }}">
                @csrf
                <input type="hidden" name="action" id="do-bulk-action-input">

                <div class="do-bulk-toolbar" aria-label="Bulk actions toolbar">
                    <div class="do-bulk-toolbar-left">
                        <span class="do-selected-count" id="do-selected-count">0 items selected</span>
                    </div>

                    <div class="do-bulk-toolbar-actions">
                        <button type="button" class="admin-btn admin-btn-warning do-bulk-btn" data-action="flag" disabled>Flag Selected</button>
                        <button type="button" class="admin-btn admin-btn-secondary do-bulk-btn" data-action="lock" disabled>Lock Selected</button>
                    </div>
                </div>

                <div class="admin-table-responsive do-table-wrap">
                    <table class="admin-table do-table" aria-label="Document oversight table">
                        <thead>
                            <tr>
                                <th class="do-checkbox-col">
                                    <input type="checkbox" id="do-select-all-head" aria-label="Select all documents in table">
                                </th>
                                <th aria-sort="{{ $sortAriaFor('id') }}">
                                    <a href="{{ $sortUrlFor('id') }}" class="do-sort-link" aria-label="Sort by ID">
                                        ID <i class="{{ $sortIconFor('id') }}" aria-hidden="true"></i>
                                    </a>
                                </th>
                                <th aria-sort="{{ $sortAriaFor('document_type') }}">
                                    <a href="{{ $sortUrlFor('document_type') }}" class="do-sort-link" aria-label="Sort by document type">
                                        Type <i class="{{ $sortIconFor('document_type') }}" aria-hidden="true"></i>
                                    </a>
                                </th>
                                <th aria-sort="{{ $sortAriaFor('verification_status') }}">
                                    <a href="{{ $sortUrlFor('verification_status') }}" class="do-sort-link" aria-label="Sort by verification status">
                                        Status <i class="{{ $sortIconFor('verification_status') }}" aria-hidden="true"></i>
                                    </a>
                                </th>
                                <th>Uploaded By</th>
                                <th>Processed By</th>
                                <th aria-sort="{{ $sortAriaFor('blockchain_status') }}">
                                    <a href="{{ $sortUrlFor('blockchain_status') }}" class="do-sort-link" aria-label="Sort by blockchain status">
                                        Blockchain <i class="{{ $sortIconFor('blockchain_status') }}" aria-hidden="true"></i>
                                    </a>
                                </th>
                                <th aria-sort="{{ $sortAriaFor('created_at') }}">
                                    <a href="{{ $sortUrlFor('created_at') }}" class="do-sort-link" aria-label="Sort by created date">
                                        Date <i class="{{ $sortIconFor('created_at') }}" aria-hidden="true"></i>
                                    </a>
                                </th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($documents as $document)
                                @php
                                    $verificationStatus = (string) ($document->verification_status ?? 'draft');
                                    $chainStatus = $document->blockchain_status;
                                @endphp
                                <tr>
                                    <td class="do-checkbox-col">
                                        <input
                                            type="checkbox"
                                            class="do-row-checkbox"
                                            name="document_ids[]"
                                            value="{{ $document->id }}"
                                            aria-label="Select document {{ $document->id }}"
                                        >
                                    </td>
                                    <td class="admin-font-semibold">#{{ $document->id }}</td>
                                    <td>{{ ucwords(str_replace('_', ' ', $document->document_type ?? '-')) }}</td>
                                    <td>
                                        <div class="do-status-cell">
                                            <span class="do-status-badge {{ $statusBadgeClass($verificationStatus) }}">
                                                <i class="{{ $statusIconFor($verificationStatus) }}" aria-hidden="true"></i>
                                                {{ ucfirst($verificationStatus) }}
                                            </span>
                                            <div class="do-row-flags">
                                                @if($document->flagged)
                                                    <span class="do-flag-chip do-flag-chip--warning">Flagged</span>
                                                @endif
                                                @if($document->locked)
                                                    <span class="do-flag-chip do-flag-chip--danger">Locked</span>
                                                @endif
                                                @if($document->archived)
                                                    <span class="do-flag-chip do-flag-chip--neutral">Archived</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="do-person-cell">
                                            <span class="do-meta-label">Uploaded</span>
                                            <span class="do-info-value-inline">{{ $document->user->name ?? '-' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="do-person-cell">
                                            <span class="do-meta-label">Processed</span>
                                            <span class="do-info-value-inline">{{ $document->processedBy->name ?? '-' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="do-chain-badge {{ $blockchainBadgeClass($chainStatus) }}">
                                            <i class="{{ $blockchainIconFor($chainStatus) }}" aria-hidden="true"></i>
                                            {{ $blockchainLabel($chainStatus) }}
                                        </span>
                                    </td>
                                    <td>{{ $document->created_at->format('M d, Y') }}</td>
                                    <td>
                                        <a href="{{ route('admin.documents.show', $document) }}" class="admin-link admin-link-primary do-view-link" aria-label="View document {{ $document->id }}">
                                            <i class="fas fa-eye" aria-hidden="true"></i>
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9">
                                        <div class="admin-empty-state do-empty-state">
                                            <svg class="admin-empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7h18M7 3h10l1 4H6l1-4zm-2 4h14l-1 13H6L5 7z" />
                                            </svg>
                                            <h3 class="admin-empty-title">No documents found</h3>
                                            <p class="admin-empty-description">No records match the selected filters. Try adjusting the filter criteria.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </form>

            @if($documents->total() > 0)
                <div class="do-pagination" aria-label="Table pagination controls">
                    <div class="do-pagination-summary">
                        Showing {{ number_format((int) ($documents->firstItem() ?? 0)) }} to {{ number_format((int) ($documents->lastItem() ?? 0)) }} of {{ number_format((int) $documents->total()) }} results
                    </div>

                    <div class="do-pagination-controls">
                        <a href="{{ $documents->onFirstPage() ? '#' : $pageUrl(1) }}" class="do-page-btn {{ $documents->onFirstPage() ? 'is-disabled' : '' }}" aria-label="Go to first page">First</a>
                        <a href="{{ $documents->onFirstPage() ? '#' : $documents->previousPageUrl() }}" class="do-page-btn {{ $documents->onFirstPage() ? 'is-disabled' : '' }}" aria-label="Go to previous page">
                            <i class="fas fa-chevron-left" aria-hidden="true"></i>
                        </a>

                        @for($page = $startPage; $page <= $endPage; $page++)
                            <a href="{{ $pageUrl($page) }}" class="do-page-btn {{ $page === $currentPage ? 'is-active' : '' }}" aria-label="Go to page {{ $page }}">{{ $page }}</a>
                        @endfor

                        <a href="{{ $documents->hasMorePages() ? $documents->nextPageUrl() : '#' }}" class="do-page-btn {{ $documents->hasMorePages() ? '' : 'is-disabled' }}" aria-label="Go to next page">
                            <i class="fas fa-chevron-right" aria-hidden="true"></i>
                        </a>
                        <a href="{{ $currentPage >= $lastPage ? '#' : $pageUrl($lastPage) }}" class="do-page-btn {{ $currentPage >= $lastPage ? 'is-disabled' : '' }}" aria-label="Go to last page">Last</a>
                    </div>
                </div>
            @endif
        </section>
    </div>
@endsection

@push('styles')
<style>
    .do-page {
        --do-gap-8: 8px;
        --do-gap-12: 12px;
        --do-gap-16: 16px;
        --do-gap-24: 24px;
    }

    .do-page :is(a, button, input, select, [tabindex]):focus-visible {
        outline: 3px solid rgba(79, 70, 229, 0.42);
        outline-offset: 2px;
    }

    .do-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: var(--do-gap-16);
    }

    .do-header-controls {
        display: flex;
        align-items: center;
        gap: var(--do-gap-12);
        flex-wrap: wrap;
    }

    .do-last-updated {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        font-size: 13px;
        font-weight: 600;
        color: #334155;
    }

    .do-last-updated-label {
        color: #64748b;
    }

    .do-last-updated-relative {
        color: #0369a1;
    }

    .do-section {
        margin-bottom: var(--do-gap-24);
    }

    .do-summary-grid {
        display: grid;
        grid-template-columns: repeat(12, minmax(0, 1fr));
        gap: var(--do-gap-16);
        margin-bottom: var(--do-gap-24);
    }

    .do-summary-card {
        grid-column: span 12;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
    }

    .do-summary-card .admin-card-body {
        padding: 18px;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .do-summary-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 10px;
    }

    .do-summary-title {
        font-size: 14px;
        font-weight: 700;
        color: #334155;
        letter-spacing: 0.01em;
    }

    .do-added-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 6px 12px;
        border-radius: 999px;
        background: #e0e7ff;
        color: #3730a3;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        white-space: nowrap;
        text-align: center;
        line-height: 1.15;
        flex-shrink: 0;
    }

    .do-summary-value {
        font-size: 42px;
        line-height: 1.05;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 8px;
    }

    .do-summary-subtext {
        margin: 0;
        font-size: 13px;
        font-weight: 600;
        color: #64748b;
    }

    .do-status-chip-grid {
        display: grid;
        gap: 10px;
        margin-top: 2px;
    }

    .do-status-chip {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        align-items: center;
        gap: 10px;
        width: 100%;
        min-height: 48px;
        padding: 10px 14px;
        border-radius: 14px;
        border: 1px solid #cbd5e1;
        background: #f8fafc;
        color: #334155;
        text-decoration: none;
        font-size: 14px;
        font-weight: 700;
        transition: transform 140ms ease, box-shadow 140ms ease, background-color 140ms ease, border-color 140ms ease, color 140ms ease;
    }

    .do-status-chip:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 16px rgba(15, 23, 42, 0.08);
    }

    .do-status-chip-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1rem;
        flex-shrink: 0;
    }

    .do-status-chip-label {
        min-width: 0;
    }

    .do-status-chip-count {
        padding: 2px 8px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.7);
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.01em;
        justify-self: end;
    }

    .do-status-chip--pending {
        background: #fffbeb;
        border-color: #fde68a;
        color: #92400e;
    }

    .do-status-chip--completed {
        background: #ecfdf5;
        border-color: #bbf7d0;
        color: #166534;
    }

    .do-status-chip--rejected {
        background: #fef2f2;
        border-color: #fecaca;
        color: #991b1b;
    }

    .do-status-chip--draft {
        background: #f1f5f9;
        border-color: #cbd5e1;
        color: #475569;
    }

    .do-status-chip.is-active {
        background: #6366f1;
        color: #ffffff;
        border-color: #6366f1;
        box-shadow: 0 10px 18px rgba(99, 102, 241, 0.18);
    }

    .do-status-chip.is-active .do-status-chip-count {
        background: rgba(255, 255, 255, 0.2);
    }

    .do-summary-list {
        display: flex;
        flex-direction: column;
        gap: 6px;
        margin-top: auto;
    }

    .do-summary-list-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 14px;
        color: #334155;
        font-weight: 600;
    }

    .do-summary-list-row strong {
        font-size: 16px;
        font-weight: 800;
        color: #0f172a;
    }

    .do-color-completed {
        color: #15803d !important;
    }

    .do-color-pending {
        color: #b45309 !important;
    }

    .do-color-rejected {
        color: #b91c1c !important;
    }

    .do-filter-panel {
        border-radius: 12px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
    }

    .do-filter-grid {
        display: grid;
        grid-template-columns: repeat(12, minmax(0, 1fr));
        gap: 12px;
    }

    .do-filter-grid--row-1 {
        margin-bottom: 12px;
    }

    .do-filter-field {
        grid-column: span 12;
        min-width: 0;
    }

    .do-filter-field--range .do-date-range {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
        gap: 6px;
        align-items: center;
        width: 100%;
        min-width: 0;
    }

    .do-filter-field--range .do-date-range .admin-form-input {
        min-width: 0;
        width: 100%;
    }

    .do-range-separator {
        font-size: 12px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .do-search-group {
        display: flex;
        align-items: center;
        gap: 10px;
        min-height: 48px;
        padding: 0 12px;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        background: #ffffff;
        transition: border-color 140ms ease, box-shadow 140ms ease;
        width: 100%;
        min-width: 0;
    }

    .do-search-group:focus-within {
        border-color: #818cf8;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);
    }

    .do-search-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #64748b;
        flex-shrink: 0;
        width: 1rem;
    }

    .do-search-input {
        border: none;
        background: transparent;
        flex: 1 1 auto;
        min-width: 0;
        padding: 0.75rem 0;
    }

    .do-search-input:focus {
        box-shadow: none;
        outline: none;
    }

    .do-chip-group {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        min-height: 42px;
        align-items: center;
    }

    .do-chip-toggle {
        display: inline-flex;
        position: relative;
    }

    .do-chip-toggle-input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .do-chip-toggle-label {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        border: 1px solid #cbd5e1;
        padding: 7px 12px;
        background: #f8fafc;
        color: #334155;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        transition: all 160ms ease;
    }

    .do-chip-toggle-input:checked + .do-chip-toggle-label {
        background: #e0e7ff;
        color: #3730a3;
        border-color: #818cf8;
    }

    .do-filter-field--actions {
        display: flex;
        align-items: flex-end;
        min-width: 0;
    }

    .do-filter-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        width: 100%;
        align-items: flex-end;
    }

    .do-per-page-select-wrap {
        min-width: 140px;
    }

    .do-table-card {
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
    }

    .do-bulk-toolbar {
        padding: 14px 18px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        background: #f8fafc;
    }

    .do-bulk-toolbar-left {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .do-select-all-wrap {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        font-weight: 700;
        color: #334155;
    }

    .do-selected-count {
        font-size: 14px;
        color: #334155;
        font-weight: 700;
    }

    .do-bulk-toolbar-actions {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
    }

    .do-bulk-btn:disabled {
        opacity: 0.45;
        cursor: not-allowed;
    }

    .do-table-wrap {
        overflow-x: hidden;
    }

    .do-table {
        width: 100%;
        min-width: 0;
        table-layout: fixed;
    }

    .do-table th,
    .do-table td {
        padding: 0.875rem 1rem;
        overflow-wrap: anywhere;
    }

    .do-table-card .do-table th:first-child,
    .do-table-card .do-table td:first-child {
        padding-left: 1rem;
    }

    .do-table-card .do-table th:last-child,
    .do-table-card .do-table td:last-child {
        padding-right: 1rem;
    }

    .do-table th:nth-child(1),
    .do-table td:nth-child(1) {
        width: 4%;
    }

    .do-table th:nth-child(2),
    .do-table td:nth-child(2) {
        width: 6%;
    }

    .do-table th:nth-child(3),
    .do-table td:nth-child(3) {
        width: 10%;
    }

    .do-table th:nth-child(4),
    .do-table td:nth-child(4) {
        width: 15%;
    }

    .do-table th:nth-child(5),
    .do-table td:nth-child(5) {
        width: 15%;
    }

    .do-table th:nth-child(6),
    .do-table td:nth-child(6) {
        width: 15%;
    }

    .do-table th:nth-child(7),
    .do-table td:nth-child(7) {
        width: 12%;
    }

    .do-table th:nth-child(8),
    .do-table td:nth-child(8) {
        width: 10%;
    }

    .do-table th:nth-child(9),
    .do-table td:nth-child(9) {
        width: 13%;
    }

    .do-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: #f8fafc;
        border-bottom: 1px solid #dbe2ea;
        font-size: 14px;
        font-weight: 700;
        text-transform: none;
        letter-spacing: 0.01em;
        color: #334155;
    }

    .do-sort-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
        color: inherit;
        font-weight: 700;
    }

    .do-sort-link:hover {
        color: #3730a3;
    }

    .do-checkbox-col {
        width: 46px;
    }

    .do-table td {
        font-size: 14px;
        vertical-align: top;
        color: #334155;
    }

    .do-table tbody tr {
        transition: background-color 120ms ease;
    }

    .do-table tbody tr:hover td {
        background: #f8fafc;
    }

    .do-status-cell {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .do-status-badge,
    .do-chain-badge,
    .do-flag-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        border-radius: 999px;
        border: 1px solid transparent;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        padding: 4px 10px;
        line-height: 1.2;
    }

    .do-status-badge--completed {
        background: #ecfdf5;
        color: #166534;
        border-color: #bbf7d0;
    }

    .do-status-badge--pending {
        background: #fffbeb;
        color: #92400e;
        border-color: #fde68a;
    }

    .do-status-badge--rejected {
        background: #fef2f2;
        color: #991b1b;
        border-color: #fecaca;
    }

    .do-status-badge--draft {
        background: #f1f5f9;
        color: #475569;
        border-color: #cbd5e1;
    }

    .do-chain-badge--confirmed {
        background: #dcfce7;
        color: #166534;
        border-color: #86efac;
    }

    .do-chain-badge--pending {
        background: #ffedd5;
        color: #9a3412;
        border-color: #fed7aa;
    }

    .do-chain-badge--failed {
        background: #fee2e2;
        color: #991b1b;
        border-color: #fecaca;
    }

    .do-chain-badge--not-submitted {
        background: #f1f5f9;
        color: #475569;
        border-color: #cbd5e1;
    }

    .do-row-flags {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
    }

    .do-flag-chip {
        font-size: 10px;
        padding: 3px 8px;
    }

    .do-status-badge i,
    .do-chain-badge i {
        font-size: 0.78rem;
    }

    .do-flag-chip--warning {
        background: #fff7ed;
        color: #9a3412;
        border-color: #fed7aa;
    }

    .do-flag-chip--danger {
        background: #fef2f2;
        color: #991b1b;
        border-color: #fecaca;
    }

    .do-flag-chip--neutral {
        background: #f8fafc;
        color: #475569;
        border-color: #cbd5e1;
    }

    .do-person-cell {
        display: flex;
        flex-direction: column;
        gap: 4px;
        min-width: 0;
    }

    .do-person-cell .do-meta-label {
        margin-right: 0;
    }

    .do-info-value-inline {
        font-weight: 700;
        line-height: 1.4;
        color: #334155;
    }

    .do-meta-label {
        color: #64748b;
        font-weight: 700;
        font-size: 12px;
        margin-right: 4px;
    }

    .do-view-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 14px;
        font-weight: 700;
        white-space: nowrap;
    }

    .do-empty-state {
        margin: 16px;
    }

    .do-pagination {
        border-top: 1px solid #e2e8f0;
        padding: 16px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        background: #ffffff;
    }

    .do-pagination-summary {
        font-size: 14px;
        color: #334155;
        font-weight: 600;
    }

    .do-pagination-controls {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }

    .do-page-btn {
        min-width: 40px;
        height: 40px;
        padding: 0 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #334155;
        font-size: 14px;
        font-weight: 700;
        text-decoration: none;
        transition: all 140ms ease;
    }

    .do-page-btn:hover {
        border-color: #6366f1;
        color: #3730a3;
        background: #eef2ff;
    }

    .do-page-btn.is-active {
        background: #6366f1;
        border-color: #6366f1;
        color: #ffffff;
    }

    .do-page-btn.is-disabled {
        opacity: 0.45;
        pointer-events: none;
    }

    @media (min-width: 768px) {
        .do-summary-card {
            grid-column: span 6;
        }

        .do-filter-grid--row-1 .do-filter-field {
            grid-column: span 6;
        }

        .do-filter-grid--row-1 .do-filter-field--range {
            grid-column: span 12;
        }

        .do-filter-grid--row-2 .do-filter-field {
            grid-column: span 6;
        }

        .do-filter-grid--row-2 .do-filter-field--actions {
            grid-column: span 12;
        }
    }

    @media (min-width: 1200px) {
        .do-summary-card {
            grid-column: span 3;
        }

        .do-filter-grid--row-1,
        .do-filter-grid--row-2 {
            grid-template-columns: repeat(5, minmax(0, 1fr));
        }

        .do-filter-grid--row-1 .do-filter-field,
        .do-filter-grid--row-2 .do-filter-field {
            grid-column: span 1;
        }

        .do-filter-grid--row-1 .do-filter-field--range,
        .do-filter-grid--row-2 .do-filter-field--actions {
            grid-column: span 2;
        }

        .do-filter-actions {
            flex-wrap: nowrap;
        }
    }

    @media (max-width: 767px) {
        .do-header {
            flex-direction: column;
            align-items: stretch;
        }

        .do-header-controls {
            justify-content: flex-start;
        }

        .do-header-controls .admin-btn {
            width: 100%;
            justify-content: center;
        }

        .do-filter-actions {
            flex-direction: column;
            align-items: stretch;
        }

        .do-per-page-select-wrap {
            width: 100%;
        }

        .do-bulk-toolbar {
            flex-direction: column;
            align-items: flex-start;
        }

        .do-bulk-toolbar-actions {
            width: 100%;
        }

        .do-bulk-toolbar-actions .admin-btn {
            flex: 1 1 calc(50% - 4px);
            justify-content: center;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const lastUpdatedRelative = document.getElementById('do-last-updated-relative');
        const lastUpdatedIso = @json($lastUpdatedIso);

        if (lastUpdatedRelative && lastUpdatedIso) {
            const lastUpdatedAt = new Date(lastUpdatedIso);

            function updateRelativeTime() {
                const elapsedSeconds = Math.max(0, Math.floor((Date.now() - lastUpdatedAt.getTime()) / 1000));
                const elapsedMinutes = Math.floor(elapsedSeconds / 60);
                const label = elapsedMinutes < 1
                    ? elapsedSeconds + ' sec ago'
                    : elapsedMinutes + ' min ago';

                lastUpdatedRelative.textContent = label;
            }

            updateRelativeTime();
            window.setInterval(updateRelativeTime, 1000);
        }

        const bulkForm = document.getElementById('do-bulk-form');
        const selectAllHead = document.getElementById('do-select-all-head');
        const selectedCountEl = document.getElementById('do-selected-count');
        const actionInput = document.getElementById('do-bulk-action-input');
        const rowCheckboxes = Array.from(document.querySelectorAll('.do-row-checkbox'));
        const bulkButtons = Array.from(document.querySelectorAll('.do-bulk-btn'));

        function getSelectedCount() {
            return rowCheckboxes.filter(function (checkbox) {
                return checkbox.checked;
            }).length;
        }

        function syncSelectionState() {
            const selected = getSelectedCount();
            const allChecked = rowCheckboxes.length > 0 && selected === rowCheckboxes.length;
            const hasSelection = selected > 0;

            if (selectAllHead) {
                selectAllHead.checked = allChecked;
            }
            if (selectedCountEl) {
                selectedCountEl.textContent = selected === 1 ? '1 item selected' : selected + ' items selected';
            }
            bulkButtons.forEach(function (button) {
                button.disabled = !hasSelection;
            });
        }

        function setAllRowSelection(checked) {
            rowCheckboxes.forEach(function (checkbox) {
                checkbox.checked = checked;
            });
            syncSelectionState();
        }

        if (selectAllHead) {
            selectAllHead.addEventListener('change', function () {
                setAllRowSelection(selectAllHead.checked);
            });
        }

        rowCheckboxes.forEach(function (checkbox) {
            checkbox.addEventListener('change', syncSelectionState);
        });

        bulkButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                const selected = getSelectedCount();
                if (!bulkForm || !actionInput || selected === 0) {
                    return;
                }

                const action = button.getAttribute('data-action') || '';
                const actionLabel = action.charAt(0).toUpperCase() + action.slice(1);
                const confirmed = window.confirm('Apply "' + actionLabel + '" to ' + selected + ' selected document(s)?');

                if (!confirmed) {
                    return;
                }

                actionInput.value = action;
                bulkForm.submit();
            });
        });

        syncSelectionState();
    });
</script>
@endpush
