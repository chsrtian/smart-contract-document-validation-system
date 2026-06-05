@extends('layouts.admin')

@section('content')
    @php
        $activeStatus = request('status', 'pending');
        $activeRequester = request('requester');
        $activeDocumentId = request('document_id');
        $activeDateFrom = request('date_from');
        $activeDateTo = request('date_to');
        $activeSearch = request('search');
    @endphp

    <div class="admin-container correction-oversight-page">
        <div class="admin-page-header correction-page-header">
            <div>
                <h1 class="admin-page-title">Correction Oversight</h1>
                <p class="admin-page-subtitle">Review and manage correction requests from staff</p>
            </div>
        </div>

        <div class="admin-content">
            <section class="admin-filter-panel co-filter-card co-control-panel" aria-labelledby="co-controls-heading">
                <div class="co-filter-header">
                    <h2 id="co-controls-heading" class="co-section-title">Filters & Search</h2>
                    <p class="co-section-meta">Narrow results by workflow status, requester, document, and date.</p>
                </div>

                <form method="GET" action="{{ route('admin.corrections.index') }}" class="co-filter-form">
                    <input type="hidden" name="search" value="{{ $activeSearch }}">

                    <div class="co-filter-grid">
                        <div class="co-field">
                            <label for="status" class="co-label">Status</label>
                            <select name="status" id="status" class="co-control co-select">
                                <option value="pending" {{ $activeStatus === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="all" {{ $activeStatus === 'all' ? 'selected' : '' }}>All statuses</option>
                                <option value="approved" {{ $activeStatus === 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="rejected" {{ $activeStatus === 'rejected' ? 'selected' : '' }}>Rejected</option>
                            </select>
                        </div>

                        <div class="co-field">
                            <label for="requester" class="co-label">Requester</label>
                            <select name="requester" id="requester" class="co-control co-select">
                                <option value="">All requesters</option>
                                @foreach($requesters as $req)
                                    <option value="{{ $req->id }}" {{ (string) $activeRequester === (string) $req->id ? 'selected' : '' }}>
                                        {{ $req->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="co-field">
                            <label for="document_id" class="co-label">Document ID</label>
                            <input
                                type="number"
                                name="document_id"
                                id="document_id"
                                value="{{ $activeDocumentId }}"
                                min="1"
                                inputmode="numeric"
                                autocomplete="off"
                                placeholder="e.g. DOC-1234"
                                class="co-control co-input"
                            >
                        </div>

                        <div class="co-field co-date-field">
                            <label class="co-label" for="date_from">Requested Date Range</label>
                            <div class="co-date-range" role="group" aria-label="Requested date range">
                                <div class="co-date-input-wrap">
                                    <label for="date_from" class="co-sublabel">From</label>
                                    <input type="date" name="date_from" id="date_from" value="{{ $activeDateFrom }}" class="co-control co-input" placeholder="MM/DD/YYYY">
                                </div>

                                <span class="co-date-separator" aria-hidden="true">to</span>

                                <div class="co-date-input-wrap">
                                    <label for="date_to" class="co-sublabel">To</label>
                                    <input type="date" name="date_to" id="date_to" value="{{ $activeDateTo }}" class="co-control co-input" placeholder="MM/DD/YYYY">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="co-filter-actions">
                        <button type="submit" class="admin-btn admin-btn-primary co-primary-btn">
                            <i class="fas fa-filter" aria-hidden="true"></i>
                            <span>Apply</span>
                        </button>

                        <a href="{{ route('admin.corrections.index') }}" class="admin-btn admin-btn-secondary co-secondary-btn">
                            <i class="fas fa-times" aria-hidden="true"></i>
                            <span>Clear</span>
                        </a>
                    </div>
                </form>

                <div class="co-panel-divider" aria-hidden="true"></div>

                <form method="GET" action="{{ route('admin.corrections.index') }}" class="co-search-form" role="search">
                    <input type="hidden" name="status" value="{{ $activeStatus }}">

                    @if($activeRequester)
                        <input type="hidden" name="requester" value="{{ $activeRequester }}">
                    @endif

                    @if($activeDocumentId)
                        <input type="hidden" name="document_id" value="{{ $activeDocumentId }}">
                    @endif

                    @if($activeDateFrom)
                        <input type="hidden" name="date_from" value="{{ $activeDateFrom }}">
                    @endif

                    @if($activeDateTo)
                        <input type="hidden" name="date_to" value="{{ $activeDateTo }}">
                    @endif

                    <div class="co-search-field">
                        <label for="search" id="co-search-heading" class="co-label co-search-label">Search correction queue</label>

                        <div class="co-search-input-group">
                            <span class="co-search-leading-icon" aria-hidden="true">
                                <i class="fas fa-search"></i>
                            </span>

                            <input
                                type="text"
                                name="search"
                                id="search"
                                value="{{ $activeSearch }}"
                                class="co-search-input"
                                placeholder="Search by request ID, document ID, or field name"
                                autocomplete="off"
                            >
                        </div>
                    </div>

                    <div class="co-search-actions">
                        <button type="submit" class="admin-btn admin-btn-primary co-primary-btn">
                            <i class="fas fa-search" aria-hidden="true"></i>
                            <span>Search</span>
                        </button>

                        <a
                            href="{{ route('admin.corrections.index', request()->except(['search', 'page'])) }}"
                            class="admin-btn admin-btn-secondary co-secondary-btn{{ $activeSearch ? '' : ' co-disabled-link' }}"
                            {{ $activeSearch ? '' : 'aria-disabled=true tabindex=-1' }}
                        >
                            <i class="fas fa-eraser" aria-hidden="true"></i>
                            <span>Clear Search</span>
                        </a>
                    </div>
                </form>
            </section>

            <div class="admin-table-card co-table-card">
                <div class="co-results-header">
                    <div>
                        <h2 class="co-section-title">Correction Requests</h2>
                        <p class="co-section-meta">
                            @if($corrections->total() > 0)
                                Showing {{ number_format($corrections->count()) }} of {{ number_format($corrections->total()) }} request{{ $corrections->total() === 1 ? '' : 's' }}
                            @else
                                0 requests found
                            @endif
                        </p>
                    </div>
                </div>

                <div class="admin-table-responsive">
                    <table class="admin-table co-table">
                        <thead>
                            <tr>
                                <th scope="col">Request ID</th>
                                <th scope="col">Document</th>
                                <th scope="col">Field</th>
                                <th scope="col">Requester</th>
                                <th scope="col">Status</th>
                                <th scope="col">Requested</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($corrections as $correction)
                                <tr>
                                    <td class="co-request-id">#{{ $correction->id }}</td>
                                    <td>
                                        <a href="{{ route('admin.documents.show', $correction->scan) }}" class="co-link-primary">
                                            Doc #{{ $correction->scan_id }}
                                        </a>
                                    </td>
                                    <td>{{ str_replace('_', ' ', ucfirst($correction->field_name)) }}</td>
                                    <td>{{ $correction->requester?->name ?? '-' }}</td>
                                    <td>
                                        <span class="admin-badge admin-badge-{{ $correction->status === 'approved' ? 'success' : ($correction->status === 'pending' ? 'warning' : 'danger') }}">
                                            {{ ucfirst($correction->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $correction->requested_at->format('M d, Y') }}</td>
                                    <td>
                                        <div class="co-row-actions">
                                            <a href="{{ route('admin.corrections.show', $correction) }}" class="co-action-link co-action-link-view">
                                                <i class="fas fa-eye" aria-hidden="true"></i>
                                                <span>View</span>
                                            </a>

                                            @if($correction->status === 'pending')
                                                <button type="button" onclick="approveCorrection({{ $correction->id }})" class="co-action-btn co-action-btn-approve">
                                                    <i class="fas fa-check" aria-hidden="true"></i>
                                                    <span>Approve</span>
                                                </button>

                                                <button type="button" onclick="showRejectModal({{ $correction->id }})" class="co-action-btn co-action-btn-reject">
                                                    <i class="fas fa-times" aria-hidden="true"></i>
                                                    <span>Reject</span>
                                                </button>
                                            @else
                                                <span class="co-action-muted" aria-label="No available actions">—</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="co-empty-cell">
                                        <div class="co-empty-state" role="status" aria-live="polite">
                                            <span class="co-empty-icon" aria-hidden="true">
                                                <i class="fas fa-inbox"></i>
                                            </span>
                                            <p class="co-empty-title">No correction requests yet</p>
                                            <p class="co-empty-subtitle">Requests will appear here once submitted</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="admin-pagination co-pagination-wrap">
                    {{ $corrections->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>

    <div id="rejectModal" class="admin-modal-overlay co-reject-modal-overlay" style="display:none;" aria-hidden="true">
        <div class="admin-modal co-reject-modal" role="dialog" aria-modal="true" aria-labelledby="rejectModalTitle">
            <div class="admin-modal-body">
                <h3 id="rejectModalTitle" class="co-modal-title">
                    <i class="fas fa-times-circle" aria-hidden="true"></i>
                    <span>Reject Correction Request</span>
                </h3>

                <form id="rejectForm" method="POST">
                    @csrf

                    <div class="admin-form-group">
                        <label for="rejection_reason" class="admin-form-label">Rejection reason (minimum 20 characters)</label>
                        <textarea
                            name="rejection_reason"
                            id="rejection_reason"
                            rows="4"
                            minlength="20"
                            maxlength="500"
                            required
                            class="co-control co-input"
                            placeholder="Explain the reason for rejecting this request"
                        ></textarea>
                        <small class="co-field-help">Be specific. This message is shown in the request audit trail.</small>
                    </div>

                    <div class="co-modal-actions">
                        <button type="button" onclick="closeRejectModal()" class="admin-btn admin-btn-secondary co-secondary-btn">Cancel</button>
                        <button type="submit" class="admin-btn admin-btn-danger co-danger-btn">Reject Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('styles')
    <style>
        .correction-page-header {
            margin-bottom: 1.5rem;
        }

        .co-control-panel {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            padding: 24px;
            border-radius: 16px;
            margin-bottom: 1.25rem;
            border: 1px solid var(--admin-border-light);
            background: #ffffff;
            box-shadow: var(--admin-shadow-card);
        }

        .co-filter-header,
        .co-results-header {
            margin-bottom: 1rem;
        }

        .co-section-title {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
            color: var(--admin-text-primary);
        }

        .co-section-meta {
            margin-top: 0.375rem;
            font-size: 0.8125rem;
            color: var(--admin-text-muted);
        }

        .co-filter-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .co-filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
        }

        .co-date-field {
            grid-column: span 2;
            min-width: 0;
        }

        .co-field {
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-width: 0;
        }

        .co-label,
        .co-sublabel {
            font-size: 0.8125rem;
            font-weight: 700;
            color: #1f2937;
        }

        .co-sublabel {
            margin-bottom: 6px;
        }

        .co-date-range {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
            gap: 12px;
            align-items: end;
            width: 100%;
            min-width: 0;
        }

        .co-date-input-wrap {
            min-width: 0;
        }

        .co-date-separator {
            font-size: 0.8125rem;
            font-weight: 700;
            color: var(--admin-text-secondary);
            padding-bottom: 0.75rem;
        }

        .co-control {
            width: 100%;
            min-height: 44px;
            border-radius: 8px;
            border: 1px solid var(--admin-border-normal);
            padding: 10px 12px;
            font-size: 0.875rem;
            color: var(--admin-text-primary);
            background: #fff;
            transition: border-color var(--admin-transition-fast), box-shadow var(--admin-transition-fast), background-color var(--admin-transition-fast);
        }

        .co-control::placeholder {
            color: #9ca3af;
        }

        .co-control:focus-visible,
        .co-primary-btn:focus-visible,
        .co-secondary-btn:focus-visible,
        .co-danger-btn:focus-visible,
        .co-action-btn:focus-visible,
        .co-action-link:focus-visible {
            outline: 2px solid rgba(99, 102, 241, 0.35);
            outline-offset: 2px;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.14);
            border-color: var(--admin-border-focus);
        }

        .co-filter-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            margin-top: 0.5rem;
        }

        .co-panel-divider {
            height: 1px;
            background: var(--admin-border-light);
            margin: 0.25rem 0 0;
        }

        .co-primary-btn,
        .co-secondary-btn,
        .co-danger-btn {
            min-height: 44px;
            padding: 0.625rem 1rem;
        }

        .co-secondary-btn {
            border-color: #cbd5e1;
            background: #f8fafc;
            color: #334155;
        }

        .co-secondary-btn:hover {
            background: #f1f5f9;
            border-color: #94a3b8;
            color: #0f172a;
        }

        .co-disabled-link {
            opacity: 0.6;
            pointer-events: none;
        }

        .co-search-form {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            align-items: end;
            padding-top: 0.25rem;
        }

        .co-search-field {
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex: 1 1 560px;
            min-width: 0;
        }

        .co-search-label {
            margin-bottom: 0;
        }

        .co-search-input-group {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 52px;
            border: 1px solid var(--admin-border-normal);
            border-radius: 10px;
            padding: 0 12px;
            background: #fff;
            transition: border-color var(--admin-transition-fast), box-shadow var(--admin-transition-fast);
            min-width: 0;
        }

        .co-search-leading-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: #64748b;
            flex-shrink: 0;
        }

        .co-search-input {
            flex: 1 1 auto;
            width: 100%;
            border: none;
            background: transparent;
            padding: 0;
            min-height: 44px;
            font-size: 0.875rem;
            color: var(--admin-text-primary);
            min-width: 0;
        }

        .co-search-input::placeholder {
            color: #94a3b8;
        }

        .co-search-input:focus {
            outline: none;
        }

        .co-search-input-group:focus-within {
            border-color: var(--admin-border-focus);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.22);
        }

        .co-search-actions {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
            flex: 0 0 auto;
        }

        .co-table-card {
            border-radius: 16px;
        }

        .co-results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            padding: 16px 20px;
            border-bottom: 1px solid var(--admin-border-light);
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        }

        .co-table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            table-layout: fixed;
        }

        .co-table th,
        .co-table td {
            padding: 12px 16px;
            vertical-align: middle;
            border-bottom: 1px solid var(--admin-border-light);
            overflow-wrap: anywhere;
        }

        .co-table tbody tr:last-child td {
            border-bottom: none;
        }

        .co-table th {
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            color: #374151;
        }

        .co-table tbody tr:hover td {
            background: #f8faff;
        }

        .co-table th:nth-child(1),
        .co-table td:nth-child(1) {
            width: 11%;
        }

        .co-table th:nth-child(2),
        .co-table td:nth-child(2) {
            width: 14%;
        }

        .co-table th:nth-child(3),
        .co-table td:nth-child(3) {
            width: 17%;
        }

        .co-table th:nth-child(4),
        .co-table td:nth-child(4) {
            width: 16%;
        }

        .co-table th:nth-child(5),
        .co-table td:nth-child(5) {
            width: 12%;
        }

        .co-table th:nth-child(6),
        .co-table td:nth-child(6) {
            width: 12%;
        }

        .co-table th:nth-child(7),
        .co-table td:nth-child(7) {
            width: 18%;
        }

        .co-request-id {
            color: #111827;
            font-weight: 700;
        }

        .co-link-primary {
            color: var(--admin-primary);
            font-weight: 600;
            text-decoration: none;
        }

        .co-link-primary:hover {
            text-decoration: underline;
        }

        .co-row-actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }

        .co-action-link,
        .co-action-btn {
            min-height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border-radius: 8px;
            padding: 0 12px;
            font-size: 0.8125rem;
            font-weight: 600;
            line-height: 1;
            transition: background-color var(--admin-transition-fast), color var(--admin-transition-fast), border-color var(--admin-transition-fast), box-shadow var(--admin-transition-fast);
        }

        .co-action-link {
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #1e293b;
            text-decoration: none;
        }

        .co-action-link:hover {
            border-color: #94a3b8;
            background: #f8fafc;
        }

        .co-action-btn {
            border: 1px solid transparent;
            cursor: pointer;
        }

        .co-action-btn-approve {
            background: #ecfdf5;
            border-color: #a7f3d0;
            color: #047857;
        }

        .co-action-btn-approve:hover {
            background: #d1fae5;
            color: #065f46;
        }

        .co-action-btn-reject {
            background: #fef2f2;
            border-color: #fecaca;
            color: #b91c1c;
        }

        .co-action-btn-reject:hover {
            background: #fee2e2;
            color: #991b1b;
        }

        .co-action-muted {
            display: inline-flex;
            align-items: center;
            min-height: 40px;
            color: #6b7280;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .co-empty-cell {
            padding: 0 !important;
        }

        .co-empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 48px 20px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        }

        .co-empty-icon {
            width: 64px;
            height: 64px;
            border-radius: 9999px;
            background: #eef2ff;
            color: #4f46e5;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 14px;
        }

        .co-empty-title {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
            color: #0f172a;
        }

        .co-empty-subtitle {
            margin-top: 6px;
            font-size: 0.875rem;
            color: #64748b;
        }

        .co-pagination-wrap {
            padding: 16px 20px;
            margin-top: 0;
        }

        .co-reject-modal-overlay {
            align-items: center;
            justify-content: center;
            padding-top: 1rem;
            padding-bottom: 1rem;
        }

        .co-reject-modal {
            width: min(560px, calc(100vw - 2rem));
        }

        .co-modal-title {
            margin: 0 0 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.125rem;
            color: #111827;
        }

        .co-field-help {
            display: block;
            margin-top: 0.5rem;
            font-size: 0.75rem;
            color: #6b7280;
        }

        .co-modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            flex-wrap: wrap;
        }

        @media (max-width: 992px) {
            .co-date-field {
                grid-column: span 1;
            }
        }

        @media (max-width: 640px) {
            .co-control-panel {
                padding: 16px;
                border-radius: 12px;
            }

            .co-filter-grid {
                grid-template-columns: 1fr;
            }

            .co-date-range {
                grid-template-columns: 1fr;
                gap: 8px;
            }

            .co-date-separator {
                padding-bottom: 0;
                justify-self: start;
            }

            .co-results-header {
                padding: 14px 16px;
            }

            .co-table th,
            .co-table td,
            .co-table-card .co-table th:first-child,
            .co-table-card .co-table td:first-child,
            .co-table-card .co-table th:last-child,
            .co-table-card .co-table td:last-child {
                padding-left: 12px;
                padding-right: 12px;
            }

            .co-pagination-wrap {
                padding: 12px 16px;
            }

            .co-filter-actions,
            .co-search-actions {
                width: 100%;
                align-items: stretch;
            }

            .co-filter-actions .admin-btn,
            .co-search-actions .admin-btn {
                flex: 1 1 100%;
                width: 100%;
                justify-content: center;
            }

            .co-search-form {
                flex-direction: column;
                align-items: stretch;
            }

            .co-search-field,
            .co-search-actions {
                width: 100%;
            }

            .co-search-actions .admin-btn {
                flex: 1 1 100%;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        const correctionBaseUrl = @json(url('/admin/corrections'));

        function approveCorrection(id) {
            if (!confirm('Are you sure you want to approve this correction request?')) {
                return;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = correctionBaseUrl + '/' + id + '/approve';

            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            form.appendChild(csrfToken);

            document.body.appendChild(form);
            form.submit();
        }

        function showRejectModal(id) {
            const modal = document.getElementById('rejectModal');
            const form = document.getElementById('rejectForm');
            const textarea = document.getElementById('rejection_reason');

            form.action = correctionBaseUrl + '/' + id + '/reject';
            modal.style.display = 'flex';
            modal.setAttribute('aria-hidden', 'false');
            textarea.focus();
        }

        function closeRejectModal() {
            const modal = document.getElementById('rejectModal');
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
            document.getElementById('rejection_reason').value = '';
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeRejectModal();
            }
        });

        document.getElementById('rejectModal').addEventListener('click', function (event) {
            if (event.target === this) {
                closeRejectModal();
            }
        });
    </script>
@endpush