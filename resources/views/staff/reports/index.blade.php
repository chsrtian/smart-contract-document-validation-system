@extends('layouts.staff')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/staff-dashboard.css') }}">
@endpush

@section('content')
<div class="dashboard-container">
    
    {{-- ═══════════════════════════════════════════════════════════════
         PAGE HEADER
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="dashboard-header reports-header">
        <div class="dashboard-header-content">
            <h1 class="staff-page-title">Reports</h1>
            <p class="staff-page-subtitle">Filter processed records, review summary totals, and inspect detailed records.</p>
        </div>
        
        <div class="reports-header-actions">
            <a href="{{ route('staff.reports.pdf', $filters) }}" 
               class="reports-action-btn reports-action-btn--primary"
               aria-label="Download report as PDF">
                <i class="fas fa-file-pdf mr-2"></i> Download PDF
            </a>
            <a href="{{ route('staff.reports.print', $filters) }}" target="_blank" 
               class="reports-action-btn reports-action-btn--secondary"
               aria-label="Open print-friendly version">
                <i class="fas fa-print mr-2"></i> Print Friendly
            </a>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         REPORT FILTERS
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="tier-header mb-6">
        <span class="tier-header-label">Report Filters</span>
        <div class="tier-header-rule"></div>
    </div>

    {{-- Client-side date validation error --}}
    <div id="reports-date-error" class="reports-date-error mb-4" role="alert">
        <i class="fas fa-exclamation-triangle"></i>
        <span>"Date From" cannot be later than "Date To". Please correct the dates.</span>
    </div>

    <div class="reports-filter-card bg-white rounded-lg shadow-sm border border-gray-200 mb-8">
        <div class="reports-filter-inner p-5 lg:p-6">
            <form id="reports-filter-form" method="GET" action="{{ route('staff.reports.index') }}">
                
                <div class="reports-filter-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-x-6 gap-y-4 lg:gap-y-5">
                    <div class="reports-filter-field">
                        <label for="date_from" class="block text-sm font-semibold text-gray-700 mb-2">Date From</label>
                        <input type="date" name="date_from" id="date_from" value="{{ $filters['date_from'] }}" 
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm text-gray-900 py-2.5 px-3">
                    </div>

                    <div class="reports-filter-field">
                        <label for="date_to" class="block text-sm font-semibold text-gray-700 mb-2">Date To</label>
                        <input type="date" name="date_to" id="date_to" value="{{ $filters['date_to'] }}" 
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm text-gray-900 py-2.5 px-3">
                    </div>

                    <div class="reports-filter-field">
                        <label for="document_type" class="block text-sm font-semibold text-gray-700 mb-2">Document Type</label>
                        <select name="document_type" id="document_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm text-gray-900 py-2.5 px-3">
                            <option value="">All Types</option>
                            @foreach($documentTypes as $type)
                                <option value="{{ $type }}" @selected(($filters['document_type'] ?? null) === $type)>
                                    {{ $typeLabels[$type] ?? ucwords(str_replace('_', ' ', $type)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="reports-filter-field">
                        <label for="verification_status" class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                        <select name="verification_status" id="verification_status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm text-gray-900 py-2.5 px-3">
                            <option value="">All Statuses</option>
                            <option value="pending" @selected(($filters['verification_status'] ?? null) === 'pending')>Pending Review</option>
                            <option value="completed" @selected(($filters['verification_status'] ?? null) === 'completed')>Validated</option>
                            <option value="rejected" @selected(($filters['verification_status'] ?? null) === 'rejected')>Rejected</option>
                        </select>
                    </div>
                </div>

                <div class="reports-filter-actions">
                    <button type="submit" id="reports-apply-btn" class="reports-action-btn reports-action-btn--primary">
                        <i class="fas fa-filter mr-2" id="reports-apply-icon"></i>
                        <span id="reports-apply-text">Apply Filters</span>
                    </button>
                    <a href="{{ route('staff.reports.index') }}" id="reports-reset-link" class="reports-action-btn reports-action-btn--secondary" aria-label="Reset all filters">
                        <i class="fas fa-redo-alt mr-2" id="reports-reset-icon"></i>
                        <span id="reports-reset-text">Reset</span>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         ACTIVE FILTER CHIPS
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="reports-active-filters mb-8">
        <span class="reports-active-filters-label"><i class="fas fa-filter" style="margin-right:5px;font-size:0.75rem;"></i> Active Filters:</span>
        <span class="reports-filter-chip">
            <i class="fas fa-calendar-alt"></i>
            {{ \Carbon\Carbon::parse($filters['date_from'])->format('M d, Y') }} – {{ \Carbon\Carbon::parse($filters['date_to'])->format('M d, Y') }}
        </span>
        <span class="reports-filter-chip">
            <i class="fas fa-file-alt"></i>
            {{ $filters['document_type'] ? ($typeLabels[$filters['document_type']] ?? ucwords(str_replace('_', ' ', $filters['document_type']))) : 'All Types' }}
        </span>
        <span class="reports-filter-chip">
            <i class="fas fa-check-circle"></i>
            {{ $filters['verification_status'] ? ($statusLabels[$filters['verification_status']] ?? ucfirst($filters['verification_status'])) : 'All Statuses' }}
        </span>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         SUMMARY TOTALS
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="tier-header mb-6">
        <span class="tier-header-label">Summary Totals</span>
        <div class="tier-header-rule"></div>
    </div>

    <div class="reports-summary-card bg-white rounded-lg shadow-sm border border-gray-200 mb-8 p-4 sm:p-5">
        <div class="reports-summary-grid-head" aria-hidden="true">
            <span class="reports-summary-head-label">Document Type</span>
            <span class="reports-summary-head-label">Pending</span>
            <span class="reports-summary-head-label">Validated</span>
            <span class="reports-summary-head-label">Rejected</span>
            <span class="reports-summary-head-label reports-summary-head-label--total">Total</span>
        </div>

        <div class="reports-summary-grid-body" role="table" aria-label="Summary totals by document type">
            @forelse($reportData['summary_table'] as $type => $row)
                <div class="reports-summary-row-card" role="row">
                    <div class="reports-summary-cell reports-summary-cell--type" role="cell">
                        <span class="reports-summary-type">{{ $typeLabels[$type] ?? ucwords(str_replace('_', ' ', $type)) }}</span>
                    </div>

                    <div class="reports-summary-metrics" role="cell">
                        <div class="reports-summary-metric">
                            <span class="reports-summary-metric-label">Pending</span>
                            <span class="reports-summary-value">{{ number_format($row['pending']) }}</span>
                        </div>
                        <div class="reports-summary-metric reports-summary-metric--validated">
                            <span class="reports-summary-metric-label">Validated</span>
                            <span class="reports-summary-value reports-summary-value--accent">{{ number_format($row['completed']) }}</span>
                        </div>
                        <div class="reports-summary-metric">
                            <span class="reports-summary-metric-label">Rejected</span>
                            <span class="reports-summary-value">{{ number_format($row['rejected']) }}</span>
                        </div>
                    </div>

                    <div class="reports-summary-cell reports-summary-cell--total" role="cell">
                        <span class="reports-summary-total">{{ number_format($row['total']) }}</span>
                    </div>
                </div>
            @empty
                <div class="reports-summary-empty">No records in selected range.</div>
            @endforelse

            <div class="reports-summary-row-card reports-summary-row-card--grand" role="row">
                <div class="reports-summary-cell reports-summary-cell--type" role="cell">
                    <span class="reports-summary-type">Grand Total</span>
                </div>

                <div class="reports-summary-metrics" role="cell">
                    <div class="reports-summary-metric">
                        <span class="reports-summary-metric-label">Pending</span>
                        <span class="reports-summary-value">{{ number_format($reportData['grand_total']['pending']) }}</span>
                    </div>
                    <div class="reports-summary-metric reports-summary-metric--validated">
                        <span class="reports-summary-metric-label">Validated</span>
                        <span class="reports-summary-value reports-summary-value--accent">{{ number_format($reportData['grand_total']['completed']) }}</span>
                    </div>
                    <div class="reports-summary-metric">
                        <span class="reports-summary-metric-label">Rejected</span>
                        <span class="reports-summary-value">{{ number_format($reportData['grand_total']['rejected']) }}</span>
                    </div>
                </div>

                <div class="reports-summary-cell reports-summary-cell--total" role="cell">
                    <span class="reports-summary-total reports-grand-highlight">{{ number_format($reportData['grand_total']['total']) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════
         DETAILED RECORDS
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="tier-header mb-6">
        <span class="tier-header-label">Detailed Records</span>
        <div class="tier-header-rule"></div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-6">

        {{-- Table toolbar --}}
        <div class="reports-table-toolbar">
            <span class="reports-record-count">
                @if($reportData['records'] instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    Showing <strong>{{ $reportData['records']->firstItem() ?? 0 }}–{{ $reportData['records']->lastItem() ?? 0 }}</strong> of <strong>{{ number_format($reportData['records']->total()) }}</strong> records
                @else
                    Showing <strong>{{ $reportData['records']->count() }}</strong> records
                @endif
            </span>
            <div class="reports-quick-search">
                <i class="fas fa-search reports-quick-search-icon"></i>
                <input type="text" id="reports-quick-search-input" placeholder="Quick search..." 
                       class="reports-quick-search-input"
                       aria-label="Quick search in table">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full table-fixed divide-y divide-gray-200 text-sm" id="reports-detail-table">
                <colgroup>
                    <col style="width: 14%;">
                    <col style="width: 13%;">
                    <col style="width: 22%;">
                    <col style="width: 15%;">
                    <col style="width: 15%;">
                    <col style="width: 10%;">
                    <col style="width: 11%;">
                </colgroup>
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Registry No.</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Document Type</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Name / Parties</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Date Received</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Date Processed</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Remarks</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($reportData['records'] as $row)
                        <tr class="hover:bg-gray-50 transition-colors reports-detail-row">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-semibold text-gray-900 reports-registry-text" title="{{ $row['registry_no'] }}">{{ $row['registry_no'] }}</span>
                            </td>
                            @php
                                $documentTypeText = $typeLabels[$row['document_type']] ?? ucwords(str_replace('_', ' ', $row['document_type']));
                            @endphp
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <span class="reports-doc-type-text" title="{{ $documentTypeText }}">{{ $documentTypeText }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 reports-name-cell" title="{{ $row['name_or_parties'] }}">
                                <span class="reports-name-text">{{ $row['name_or_parties'] }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($row['date_received'])
                                    @php [$recDate, $recTime] = array_pad(preg_split('/(?<=\d{4})\s+/', $row['date_received'], 2), 2, ''); @endphp
                                    <div class="text-sm text-gray-900 font-medium">{{ $recDate }}</div>
                                    @if($recTime) <div class="reports-date-time">{{ $recTime }}</div> @endif
                                @else
                                    <span class="text-sm text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($row['date_processed'] && $row['date_processed'] !== 'Not processed')
                                    @php [$procDate, $procTime] = array_pad(preg_split('/(?<=\d{4})\s+/', $row['date_processed'], 2), 2, ''); @endphp
                                    <div class="text-sm text-gray-900 font-medium">{{ $procDate }}</div>
                                    @if($procTime) <div class="reports-date-time">{{ $procTime }}</div> @endif
                                @else
                                    <span class="text-sm text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $statusClass = match($row['status']) {
                                        'completed', 'approved' => 'reports-status reports-status--validated',
                                        'rejected', 'declined' => 'reports-status reports-status--rejected',
                                        default => 'reports-status reports-status--pending'
                                    };
                                @endphp
                                <span class="{{ $statusClass }}">
                                    {{ $statusLabels[$row['status']] ?? ucfirst($row['status']) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($row['remarks'] && $row['remarks'] !== 'N/A')
                                    <span class="reports-remarks-text" title="{{ $row['remarks'] }}">{{ Str::limit($row['remarks'], 45) }}</span>
                                @else
                                    <span class="reports-remarks-na">No remarks</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mb-3">
                                        <i class="fas fa-search text-gray-400"></i>
                                    </div>
                                    <p class="text-gray-900 font-medium">No records found</p>
                                    <p class="text-gray-500 text-sm mt-1">Try adjusting your filters or search criteria.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- No-results message for quick search --}}
        <div id="reports-no-search-results" class="px-6 py-12 text-center text-gray-500" style="display: none;">
            <div class="flex flex-col items-center justify-center">
                <i class="fas fa-search text-gray-300 text-2xl mb-2"></i>
                <p>No rows match your search.</p>
            </div>
        </div>

        {{-- Pagination --}}
        @if($reportData['records'] instanceof \Illuminate\Pagination\LengthAwarePaginator && $reportData['records']->hasPages())
            <div class="reports-pagination">
                {{ $reportData['records']->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── 1. Client-side date validation ─────────────────────────────────
    var form = document.getElementById('reports-filter-form');
    var dateFrom = document.getElementById('date_from');
    var dateTo = document.getElementById('date_to');
    var dateError = document.getElementById('reports-date-error');

    form.addEventListener('submit', function (e) {
        dateError.classList.remove('visible');

        if (dateFrom.value && dateTo.value) {
            var from = new Date(dateFrom.value);
            var to = new Date(dateTo.value);
            if (from > to) {
                e.preventDefault();
                dateError.classList.add('visible');
                dateFrom.focus();
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }
        }

        // ── 2. Loading state on Apply ──────────────────────────────────
        var btn = document.getElementById('reports-apply-btn');
        var icon = document.getElementById('reports-apply-icon');
        var text = document.getElementById('reports-apply-text');

        btn.classList.add('reports-btn-loading');
        icon.className = 'fas fa-spinner fa-spin mr-2';
        text.textContent = 'Applying\u2026';
    });

    // ── 3. Quick search ────────────────────────────────────────────────
    var searchInput = document.getElementById('reports-quick-search-input');
    var tableBody = document.querySelector('#reports-detail-table tbody');
    var noResults = document.getElementById('reports-no-search-results');
    var resetLink = document.getElementById('reports-reset-link');
    var documentTypeFilter = document.getElementById('document_type');
    var statusFilter = document.getElementById('verification_status');

    if (searchInput && tableBody) {
        searchInput.addEventListener('input', function () {
            var query = this.value.toLowerCase().trim();
            var rows = tableBody.querySelectorAll('tr.reports-detail-row');
            var visibleCount = 0;

            rows.forEach(function (row) {
                var text = row.textContent.toLowerCase();
                var match = !query || text.indexOf(query) !== -1;
                row.style.display = match ? '' : 'none';
                if (match) visibleCount++;
            });

            if (noResults) {
                noResults.style.display = (visibleCount === 0 && query) ? 'block' : 'none';
            }
        });
    }

    // ── 4. Reset confirmation + loading feedback ─────────────────────
    if (resetLink) {
        resetLink.addEventListener('click', function (e) {
            var hasQuickSearch = searchInput && searchInput.value.trim() !== '';
            var hasAdvancedFilters = (documentTypeFilter && documentTypeFilter.value) || (statusFilter && statusFilter.value);

            if (hasQuickSearch || hasAdvancedFilters) {
                var confirmed = window.confirm('Clear selected filters and reload the default report view?');
                if (!confirmed) {
                    e.preventDefault();
                    return;
                }
            }

            var resetIcon = document.getElementById('reports-reset-icon');
            var resetText = document.getElementById('reports-reset-text');

            resetLink.classList.add('reports-btn-loading');

            if (resetIcon) {
                resetIcon.className = 'fas fa-spinner fa-spin mr-2';
            }

            if (resetText) {
                resetText.textContent = 'Resetting...';
            }
        });
    }

});
</script>
@endpush
