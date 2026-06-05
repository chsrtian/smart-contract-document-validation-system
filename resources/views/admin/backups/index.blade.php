@extends('layouts.admin')

@section('content')
    @php
        $hasActiveBackups = $backups->contains(function ($backup) {
            return in_array($backup->status, ['pending', 'in_progress'], true);
        });
    @endphp

    <div class="admin-container backup-page">
        <header class="admin-page-header backup-header">
            <div class="backup-header-copy">
                <h1 class="admin-page-title">System Backups</h1>
                <p class="admin-page-subtitle">Database and file system backups</p>
            </div>

            <div class="backup-header-actions" aria-label="Backup controls">
                <button type="button" id="backup-filter-toggle" class="admin-btn admin-btn-secondary">
                    <i class="fas fa-calendar-days" aria-hidden="true"></i>
                    Filter by Date
                </button>

                <form action="{{ route('admin.backups.create') }}" method="POST" id="create-backup-form">
                    @csrf
                    <input type="hidden" name="timeframe_mode" id="backup-timeframe-mode-input">
                    <input type="hidden" name="timeframe_start" id="backup-timeframe-start-input">
                    <input type="hidden" name="timeframe_end" id="backup-timeframe-end-input">
                    <input type="hidden" name="timeframe_label" id="backup-timeframe-label-input">

                    <button type="button" class="admin-btn admin-btn-primary" id="create-backup-button">
                        <i class="fas fa-database" aria-hidden="true"></i>
                        Create Manual Backup
                    </button>
                </form>
            </div>
        </header>

        <div id="backup-timeframe-modal" class="backup-modal-overlay" hidden aria-hidden="true">
            <div class="backup-modal" role="dialog" aria-modal="true" aria-labelledby="backup-timeframe-title" aria-describedby="backup-timeframe-desc">
                <div class="backup-modal-header">
                    <h2 id="backup-timeframe-title">Select Backup Timeframe</h2>
                    <p id="backup-timeframe-desc">Choose the period before starting manual backup. Backup will not start until you confirm.</p>
                </div>

                <div class="backup-modal-body">
                    <div class="backup-modal-field">
                        <label for="backup-timeframe-mode" class="admin-form-label">Timeframe</label>
                        <select id="backup-timeframe-mode" class="admin-form-select">
                            <option value="today">Today</option>
                            <option value="this_week">This Week</option>
                            <option value="this_month" selected>This Month</option>
                            <option value="specific_day">Specific Day</option>
                            <option value="specific_month">Specific Month</option>
                            <option value="custom_range">Custom Range</option>
                        </select>
                    </div>

                    <div id="backup-timeframe-specific-day" class="backup-modal-field" hidden>
                        <label for="backup-specific-day" class="admin-form-label">Day</label>
                        <input type="date" id="backup-specific-day" class="admin-form-input">
                    </div>

                    <div id="backup-timeframe-specific-month" class="backup-modal-field" hidden>
                        <label for="backup-specific-month" class="admin-form-label">Month</label>
                        <input type="month" id="backup-specific-month" class="admin-form-input">
                    </div>

                    <div id="backup-timeframe-custom" class="backup-modal-grid" hidden>
                        <div class="backup-modal-field">
                            <label for="backup-timeframe-custom-from" class="admin-form-label">From</label>
                            <input type="datetime-local" id="backup-timeframe-custom-from" class="admin-form-input">
                        </div>

                        <div class="backup-modal-field">
                            <label for="backup-timeframe-custom-to" class="admin-form-label">To</label>
                            <input type="datetime-local" id="backup-timeframe-custom-to" class="admin-form-input">
                        </div>
                    </div>

                    <div class="backup-timeframe-preview" id="backup-timeframe-preview" aria-live="polite"></div>
                </div>

                <div class="backup-modal-actions">
                    <button type="button" class="admin-btn admin-btn-secondary" id="backup-timeframe-cancel">Cancel</button>
                    <button type="button" class="admin-btn admin-btn-primary" id="backup-timeframe-confirm">
                        <i class="fas fa-play" aria-hidden="true"></i>
                        Start Backup
                    </button>
                </div>
            </div>
        </div>

        <section class="admin-filter-panel backup-filter-panel" id="backup-date-filter-panel" hidden>
            <div class="backup-filter-grid">
                <div class="backup-filter-field">
                    <label for="backup-filter-from" class="admin-form-label">From</label>
                    <input type="datetime-local" id="backup-filter-from" class="admin-form-input" aria-label="Filter start date and time">
                </div>

                <div class="backup-filter-field">
                    <label for="backup-filter-to" class="admin-form-label">To</label>
                    <input type="datetime-local" id="backup-filter-to" class="admin-form-input" aria-label="Filter end date and time">
                </div>

                <div class="backup-filter-actions">
                    <button type="button" class="admin-btn admin-btn-primary admin-btn-sm" id="backup-filter-apply">
                        <i class="fas fa-filter" aria-hidden="true"></i>
                        Apply
                    </button>
                    <button type="button" class="admin-btn admin-btn-secondary admin-btn-sm" id="backup-filter-clear">
                        <i class="fas fa-rotate-left" aria-hidden="true"></i>
                        Reset
                    </button>
                </div>
            </div>

            <p id="backup-filter-summary" class="backup-filter-summary" aria-live="polite">
                Showing all backups on this page.
            </p>
        </section>

        <div class="admin-content">
            @if($hasActiveBackups)
                <div class="admin-callout admin-callout-warning backup-state-callout">
                    <div class="backup-state-callout-row">
                        <p class="admin-text-sm">
                            <i class="fas fa-sync-alt" aria-hidden="true"></i>
                            <strong>Processing state:</strong> Backups are running in background. Refresh status when you need the latest state.
                        </p>
                        <button type="button" id="backup-refresh-status" class="admin-btn admin-btn-secondary admin-btn-sm">
                            <i class="fas fa-rotate-right" aria-hidden="true"></i>
                            Refresh Status
                        </button>
                    </div>
                </div>
            @endif

            <div class="admin-callout admin-callout-info backup-scope-callout">
                <p class="admin-text-sm">
                    <i class="fas fa-circle-info" aria-hidden="true"></i>
                    <strong>Backup Scope:</strong> Each backup includes the full database, document files, and audit logs. Artifacts are compressed and checksum-verified.
                </p>
            </div>

            <div class="admin-table-card">
                <div class="admin-table-responsive">
                    <table class="admin-table backup-table" id="backup-table">
                        <colgroup>
                            <col style="width: 18%;">
                            <col style="width: 14%;">
                            <col style="width: 14%;">
                            <col style="width: 12%;">
                            <col style="width: 17%;">
                            <col style="width: 8%;">
                            <col style="width: 17%;">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Backup</th>
                                <th>Started</th>
                                <th>Completed</th>
                                <th>Status</th>
                                <th>File</th>
                                <th>Size</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($backups as $backup)
                                @php
                                    $fileName = $backup->file_path ? basename($backup->file_path) : '-';
                                    $fileType = $backup->file_path ? strtoupper(pathinfo($backup->file_path, PATHINFO_EXTENSION)) : '-';
                                    $storageLocation = $backup->file_path ? ('storage/app/' . $backup->file_path) : '-';
                                    $startedIso = $backup->started_at ? $backup->started_at->toIso8601String() : '';
                                    $startedMain = $backup->started_at ? $backup->started_at->format('M d, Y H:i') : '-';
                                    $startedRelative = $backup->started_at ? $backup->started_at->diffForHumans() : null;
                                    $completedMain = $backup->completed_at ? $backup->completed_at->format('M d, Y H:i') : '-';
                                    $completedRelative = $backup->completed_at ? $backup->completed_at->diffForHumans() : null;
                                    $statusMeta = match($backup->status) {
                                        'completed' => ['class' => 'is-completed', 'icon' => 'fa-circle-check', 'label' => 'Completed'],
                                        'failed' => ['class' => 'is-failed', 'icon' => 'fa-triangle-exclamation', 'label' => 'Failed'],
                                        'in_progress' => ['class' => 'is-progress', 'icon' => 'fa-spinner fa-spin', 'label' => 'In Progress'],
                                        default => ['class' => 'is-pending', 'icon' => 'fa-clock', 'label' => 'Pending'],
                                    };
                                @endphp
                                <tr class="backup-row" data-started-at="{{ $startedIso }}">
                                    <td>
                                        <p class="backup-id">#{{ $backup->id }}</p>
                                        <div class="backup-meta-line">
                                            <span class="admin-badge {{ $backup->type === 'manual' ? 'admin-badge-info' : 'admin-badge-warning' }}">{{ ucfirst($backup->type) }}</span>
                                        </div>
                                        <p class="backup-meta-sub">By {{ $backup->initiator?->name ?? 'System' }}</p>
                                    </td>

                                    <td>
                                        <p class="backup-date-main">{{ $startedMain }}</p>
                                        @if($startedRelative)
                                            <p class="backup-date-sub">{{ $startedRelative }}</p>
                                        @endif
                                    </td>

                                    <td>
                                        <p class="backup-date-main">{{ $completedMain }}</p>
                                        @if($completedRelative)
                                            <p class="backup-date-sub">{{ $completedRelative }}</p>
                                        @endif
                                    </td>

                                    <td>
                                        <span class="backup-status {{ $statusMeta['class'] }}">
                                            <i class="fas {{ $statusMeta['icon'] }}" aria-hidden="true"></i>
                                            {{ $statusMeta['label'] }}
                                        </span>
                                    </td>

                                    <td>
                                        <span class="backup-file-name" title="{{ $fileName }}">{{ $fileName }}</span>
                                        <span class="backup-file-meta">{{ $fileType !== '-' ? $fileType . ' file' : 'No artifact yet' }}</span>
                                    </td>

                                    <td>
                                        <span class="backup-size">{{ $backup->file_size_human }}</span>
                                    </td>

                                    <td class="backup-actions-cell">
                                        @if($backup->status === 'completed')
                                            <div class="backup-actions-stack">
                                                <a href="{{ route('admin.backups.download', $backup) }}" class="admin-btn admin-btn-primary admin-btn-sm backup-action-btn backup-action-primary">
                                                    <i class="fas fa-download" aria-hidden="true"></i>
                                                    Download
                                                </a>

                                                <button type="button" onclick="verifyBackup({{ $backup->id }}, this)" class="admin-btn admin-btn-secondary admin-btn-sm backup-action-btn backup-action-secondary">
                                                    <i class="fas fa-shield-check" aria-hidden="true"></i>
                                                    Verify
                                                </button>

                                                <details class="backup-inline-details">
                                                    <summary class="backup-action-btn backup-action-details">
                                                        <i class="fas fa-circle-info" aria-hidden="true"></i>
                                                        Details
                                                    </summary>
                                                    <div class="backup-detail-content">
                                                        <p><strong>Checksum:</strong></p>
                                                        <p class="backup-detail-value" title="{{ $backup->checksum }}">{{ $backup->checksum }}</p>
                                                        <p><strong>Storage location:</strong></p>
                                                        <p class="backup-detail-value" title="{{ $storageLocation }}">{{ $storageLocation }}</p>
                                                        <p><strong>Download URL:</strong></p>
                                                        <p class="backup-detail-value" title="{{ route('admin.backups.download', $backup) }}">{{ route('admin.backups.download', $backup) }}</p>
                                                    </div>
                                                </details>
                                            </div>
                                        @elseif($backup->status === 'failed')
                                            <div class="backup-actions-stack">
                                                <details class="backup-inline-details backup-inline-details--error">
                                                    <summary class="backup-action-btn backup-action-details">
                                                        <i class="fas fa-circle-exclamation" aria-hidden="true"></i>
                                                        View Error
                                                    </summary>
                                                    <pre>{{ $backup->failure_reason ?: $backup->error_message ?: 'No error details recorded.' }}</pre>
                                                </details>
                                            </div>
                                        @elseif($backup->status === 'in_progress')
                                            <span class="backup-processing">
                                                <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
                                                Backup is running
                                            </span>
                                        @else
                                            <span class="backup-processing">
                                                <i class="fas fa-clock" aria-hidden="true"></i>
                                                Queued for processing
                                            </span>
                                        @endif

                                        <div id="verify-result-{{ $backup->id }}" class="backup-verify-result" aria-live="polite"></div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">
                                        <div class="admin-empty-state">
                                            <i class="fas fa-database admin-empty-icon" aria-hidden="true"></i>
                                            <p class="admin-empty-title">No backups found</p>
                                            <p class="admin-empty-description">Create your first backup to start building restore points.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="admin-pagination">
                    {{ $backups->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>

@endsection

@push('styles')
<style>
    .backup-page {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .backup-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: 0;
    }

    .backup-header-actions {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        flex-wrap: wrap;
    }

    .backup-filter-panel {
        margin-bottom: 0;
    }

    .backup-filter-panel[hidden] {
        display: none !important;
    }

    .backup-filter-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) auto;
        gap: 0.75rem;
        align-items: end;
    }

    .backup-filter-field {
        min-width: 0;
    }

    .backup-filter-actions {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .backup-filter-summary {
        margin: 0.75rem 0 0;
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--admin-text-secondary);
    }

    .backup-state-callout,
    .backup-scope-callout {
        margin-bottom: 1rem;
    }

    .backup-state-callout-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.8rem;
        flex-wrap: wrap;
    }

    .backup-table th,
    .backup-table td {
        padding: 0.75rem 1rem;
        vertical-align: top;
    }

    .backup-id {
        margin: 0;
        color: var(--admin-text-primary);
        font-weight: 800;
        font-size: 0.95rem;
    }

    .backup-meta-line {
        margin-top: 0.35rem;
    }

    .backup-meta-sub {
        margin: 0.35rem 0 0;
        font-size: 0.8rem;
        color: var(--admin-text-muted);
    }

    .backup-date-main {
        margin: 0;
        color: var(--admin-text-primary);
        font-size: 0.86rem;
        font-weight: 700;
        line-height: 1.3;
    }

    .backup-date-sub {
        margin: 0.2rem 0 0;
        color: var(--admin-text-muted);
        font-size: 0.76rem;
        line-height: 1.3;
    }

    .backup-status {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.3rem 0.65rem;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        border: 1px solid transparent;
    }

    .backup-status.is-completed {
        background: #ecfdf3;
        color: #166534;
        border-color: #86efac;
    }

    .backup-status.is-failed {
        background: #fef2f2;
        color: #991b1b;
        border-color: #fecaca;
    }

    .backup-status.is-progress {
        background: #fff7ed;
        color: #9a3412;
        border-color: #fdba74;
    }

    .backup-status.is-pending {
        background: #eff6ff;
        color: #1e40af;
        border-color: #93c5fd;
    }

    .backup-file-name {
        display: block;
        max-width: 230px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size: 0.82rem;
        font-weight: 700;
        color: var(--admin-text-primary);
    }

    .backup-file-meta {
        display: block;
        margin-top: 0.2rem;
        font-size: 0.76rem;
        color: var(--admin-text-muted);
    }

    .backup-size {
        font-weight: 700;
        color: var(--admin-text-primary);
    }

    .backup-actions-cell {
        min-width: 190px;
    }

    .backup-actions-stack {
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
        align-items: stretch;
    }

    .backup-action-primary,
    .backup-action-secondary {
        margin-bottom: 0;
    }

    .backup-action-btn {
        display: inline-flex;
        width: 100%;
        min-height: 2.35rem;
        justify-content: center;
        gap: 0.45rem;
        padding: 0.4rem 0.75rem;
        font-size: 0.86rem;
        font-weight: 700;
        border-radius: var(--admin-radius-md);
    }

    .backup-action-btn i {
        width: 1rem;
        font-size: 0.88rem;
        text-align: center;
    }

    .backup-action-details {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--admin-text-secondary);
        background: var(--admin-bg-muted);
        border: 1px solid var(--admin-border-light);
        cursor: pointer;
        user-select: none;
    }

    .backup-action-details:hover {
        background: var(--admin-border-light);
    }

    .backup-processing {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.82rem;
        color: var(--admin-text-secondary);
        font-weight: 600;
    }

    .backup-inline-details {
        margin-top: 0;
        border: 1px solid var(--admin-border-light);
        border-radius: var(--admin-radius-md);
        background: #f8fafc;
        padding: 0;
    }

    .backup-inline-details summary {
        list-style: none;
        margin: 0;
    }

    .backup-inline-details summary::-webkit-details-marker {
        display: none;
    }

    .backup-inline-details--error summary {
        color: #991b1b;
    }

    .backup-detail-content {
        border-top: 1px solid var(--admin-border-light);
        padding: 0.45rem 0.5rem 0.2rem;
    }

    .backup-detail-content p {
        margin: 0 0 0.2rem;
        font-size: 0.77rem;
        color: var(--admin-text-secondary);
        line-height: 1.35;
    }

    .backup-detail-value {
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        word-break: break-word;
    }

    .backup-inline-details--error pre {
        margin: 0.45rem 0.5rem 0.5rem;
        padding: 0.5rem;
        border-radius: var(--admin-radius-sm);
        border: 1px solid #fecaca;
        background: #fff;
        color: #7f1d1d;
        font-size: 0.77rem;
        line-height: 1.4;
        white-space: pre-wrap;
    }

    .backup-verify-result {
        margin-top: 0.35rem;
        font-size: 0.77rem;
        font-weight: 700;
    }

    .backup-verify-result.is-success {
        color: #166534;
    }

    .backup-verify-result.is-error {
        color: #991b1b;
    }

    .backup-page :is(a, button, input, summary):focus-visible {
        outline: 3px solid rgba(79, 70, 229, 0.35);
        outline-offset: 2px;
    }

    .backup-modal-overlay {
        position: fixed;
        inset: 0;
        z-index: 1200;
        background: rgba(15, 23, 42, 0.45);
        display: none;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }

    .backup-modal-overlay.is-open {
        display: flex;
    }

    .backup-modal-overlay[hidden] {
        display: none !important;
    }

    .backup-modal-field[hidden],
    .backup-modal-grid[hidden] {
        display: none !important;
    }

    .backup-modal {
        width: min(640px, 100%);
        background: #ffffff;
        border: 1px solid var(--admin-border-light);
        border-radius: var(--admin-radius-lg);
        box-shadow: var(--admin-shadow-xl);
        overflow: hidden;
    }

    .backup-modal-header {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--admin-border-light);
    }

    .backup-modal-header h2 {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 800;
        color: var(--admin-text-primary);
    }

    .backup-modal-header p {
        margin: 0.35rem 0 0;
        color: var(--admin-text-muted);
        font-size: 0.86rem;
    }

    .backup-modal-body {
        padding: 1rem 1.25rem;
        display: flex;
        flex-direction: column;
        gap: 0.85rem;
    }

    .backup-modal-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.75rem;
    }

    .backup-timeframe-preview {
        border: 1px solid var(--admin-border-light);
        background: var(--admin-bg-muted);
        border-radius: var(--admin-radius-md);
        padding: 0.65rem 0.75rem;
        color: var(--admin-text-secondary);
        font-size: 0.83rem;
        font-weight: 600;
    }

    .backup-modal-actions {
        padding: 0.9rem 1.25rem;
        border-top: 1px solid var(--admin-border-light);
        display: flex;
        justify-content: flex-end;
        gap: 0.6rem;
    }

    @media (max-width: 1080px) {
        .backup-filter-grid {
            grid-template-columns: 1fr;
        }

        .backup-actions-cell {
            min-width: 165px;
        }

        .backup-modal-grid {
            grid-template-columns: 1fr;
        }

        .backup-state-callout-row {
            align-items: stretch;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    (function () {
        const filterToggle = document.getElementById('backup-filter-toggle');
        const filterPanel = document.getElementById('backup-date-filter-panel');
        const fromInput = document.getElementById('backup-filter-from');
        const toInput = document.getElementById('backup-filter-to');
        const applyButton = document.getElementById('backup-filter-apply');
        const clearButton = document.getElementById('backup-filter-clear');
        const summary = document.getElementById('backup-filter-summary');
        const rows = Array.from(document.querySelectorAll('.backup-row'));
        const refreshButton = document.getElementById('backup-refresh-status');

        const createForm = document.getElementById('create-backup-form');
        const createButton = document.getElementById('create-backup-button');
        const timeframeModeInput = document.getElementById('backup-timeframe-mode-input');
        const timeframeStartInput = document.getElementById('backup-timeframe-start-input');
        const timeframeEndInput = document.getElementById('backup-timeframe-end-input');
        const timeframeLabelInput = document.getElementById('backup-timeframe-label-input');

        const timeframeModal = document.getElementById('backup-timeframe-modal');
        const timeframeMode = document.getElementById('backup-timeframe-mode');
        const timeframeSpecificDayWrap = document.getElementById('backup-timeframe-specific-day');
        const timeframeSpecificMonthWrap = document.getElementById('backup-timeframe-specific-month');
        const timeframeCustomWrap = document.getElementById('backup-timeframe-custom');
        const timeframeSpecificDay = document.getElementById('backup-specific-day');
        const timeframeSpecificMonth = document.getElementById('backup-specific-month');
        const timeframeCustomFrom = document.getElementById('backup-timeframe-custom-from');
        const timeframeCustomTo = document.getElementById('backup-timeframe-custom-to');
        const timeframePreview = document.getElementById('backup-timeframe-preview');
        const timeframeCancel = document.getElementById('backup-timeframe-cancel');
        const timeframeConfirm = document.getElementById('backup-timeframe-confirm');

        const closeFilterPanel = function () {
            if (!filterPanel) {
                return;
            }
            filterPanel.hidden = true;
            if (filterToggle) {
                filterToggle.setAttribute('aria-expanded', 'false');
            }
        };

        closeFilterPanel();

        if (timeframeModal) {
            timeframeModal.hidden = true;
            timeframeModal.classList.remove('is-open');
            timeframeModal.setAttribute('aria-hidden', 'true');
        }

        const toDate = function (value) {
            if (!value) {
                return null;
            }
            const parsed = new Date(value);
            return Number.isNaN(parsed.getTime()) ? null : parsed;
        };

        const applyDateFilter = function () {
            const fromDate = toDate(fromInput ? fromInput.value : '');
            const toDateValue = toDate(toInput ? toInput.value : '');
            const hasFilter = Boolean(fromDate || toDateValue);
            let visible = 0;

            rows.forEach(function (row) {
                const startedDate = toDate(row.dataset.startedAt || '');
                let isVisible = true;

                if (hasFilter) {
                    if (!startedDate) {
                        isVisible = false;
                    }

                    if (isVisible && fromDate && startedDate < fromDate) {
                        isVisible = false;
                    }

                    if (isVisible && toDateValue && startedDate > toDateValue) {
                        isVisible = false;
                    }
                }

                row.style.display = isVisible ? '' : 'none';
                if (isVisible) {
                    visible += 1;
                }
            });

            if (summary) {
                if (!hasFilter) {
                    summary.textContent = `Showing all ${rows.length} backups on this page.`;
                } else {
                    summary.textContent = `Showing ${visible} of ${rows.length} backups for the selected date range.`;
                }
            }
        };

        if (filterToggle && filterPanel) {
            filterToggle.addEventListener('click', function () {
                const willOpen = filterPanel.hidden;
                filterPanel.hidden = !willOpen;
                filterToggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            });
        }

        if (applyButton) {
            applyButton.addEventListener('click', applyDateFilter);
        }

        if (clearButton) {
            clearButton.addEventListener('click', function () {
                if (fromInput) {
                    fromInput.value = '';
                }
                if (toInput) {
                    toInput.value = '';
                }
                applyDateFilter();
            });
        }

        if (refreshButton) {
            refreshButton.addEventListener('click', function () {
                window.location.reload();
            });
        }

        if (createForm && createButton) {
            const pad = function (value) {
                return String(value).padStart(2, '0');
            };

            const formatLocalDateTime = function (dateObj) {
                return `${dateObj.getFullYear()}-${pad(dateObj.getMonth() + 1)}-${pad(dateObj.getDate())}T${pad(dateObj.getHours())}:${pad(dateObj.getMinutes())}`;
            };

            const formatDateLabel = function (dateObj) {
                return dateObj.toLocaleString(undefined, {
                    year: 'numeric',
                    month: 'short',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            };

            const startOfDay = function (dateObj) {
                const d = new Date(dateObj);
                d.setHours(0, 0, 0, 0);
                return d;
            };

            const endOfDay = function (dateObj) {
                const d = new Date(dateObj);
                d.setHours(23, 59, 59, 999);
                return d;
            };

            const setModalVisibility = function (isOpen) {
                if (!timeframeModal) {
                    return;
                }

                timeframeModal.classList.toggle('is-open', isOpen);
                timeframeModal.hidden = !isOpen;
                timeframeModal.setAttribute('aria-hidden', isOpen ? 'false' : 'true');

                if (isOpen && timeframeMode) {
                    timeframeMode.focus();
                }
            };

            const computeTimeframe = function () {
                const mode = timeframeMode ? timeframeMode.value : 'this_month';
                const now = new Date();
                let start = null;
                let end = null;
                let label = '';

                if (mode === 'today') {
                    start = startOfDay(now);
                    end = endOfDay(now);
                    label = 'Today';
                } else if (mode === 'this_week') {
                    const day = now.getDay();
                    const mondayShift = day === 0 ? -6 : 1 - day;
                    start = startOfDay(new Date(now.getFullYear(), now.getMonth(), now.getDate() + mondayShift));
                    end = endOfDay(new Date(start.getFullYear(), start.getMonth(), start.getDate() + 6));
                    label = 'This Week';
                } else if (mode === 'this_month') {
                    start = startOfDay(new Date(now.getFullYear(), now.getMonth(), 1));
                    end = endOfDay(new Date(now.getFullYear(), now.getMonth() + 1, 0));
                    label = 'This Month';
                } else if (mode === 'specific_day') {
                    if (!timeframeSpecificDay || !timeframeSpecificDay.value) {
                        return null;
                    }
                    const dayDate = new Date(`${timeframeSpecificDay.value}T00:00:00`);
                    start = startOfDay(dayDate);
                    end = endOfDay(dayDate);
                    label = `Day: ${dayDate.toLocaleDateString()}`;
                } else if (mode === 'specific_month') {
                    if (!timeframeSpecificMonth || !timeframeSpecificMonth.value) {
                        return null;
                    }
                    const [year, month] = timeframeSpecificMonth.value.split('-').map(Number);
                    start = startOfDay(new Date(year, month - 1, 1));
                    end = endOfDay(new Date(year, month, 0));
                    label = `Month: ${start.toLocaleDateString(undefined, { month: 'long', year: 'numeric' })}`;
                } else {
                    if (!timeframeCustomFrom || !timeframeCustomTo || !timeframeCustomFrom.value || !timeframeCustomTo.value) {
                        return null;
                    }
                    start = new Date(timeframeCustomFrom.value);
                    end = new Date(timeframeCustomTo.value);
                    if (end < start) {
                        return null;
                    }
                    label = 'Custom Range';
                }

                return {
                    mode,
                    start,
                    end,
                    label,
                };
            };

            const refreshModalInputs = function () {
                if (!timeframeMode) {
                    return;
                }

                const mode = timeframeMode.value;
                timeframeSpecificDayWrap.hidden = mode !== 'specific_day';
                timeframeSpecificMonthWrap.hidden = mode !== 'specific_month';
                timeframeCustomWrap.hidden = mode !== 'custom_range';

                const timeframe = computeTimeframe();
                if (!timeframe || !timeframePreview) {
                    if (timeframePreview) {
                        timeframePreview.textContent = 'Select a valid timeframe to continue.';
                    }
                    return;
                }

                timeframePreview.textContent = `${timeframe.label}: ${formatDateLabel(timeframe.start)} to ${formatDateLabel(timeframe.end)}.`;
            };

            createButton.addEventListener('click', function () {
                if (fromInput && toInput && fromInput.value && toInput.value && timeframeMode && timeframeCustomFrom && timeframeCustomTo) {
                    timeframeMode.value = 'custom_range';
                    timeframeCustomFrom.value = fromInput.value;
                    timeframeCustomTo.value = toInput.value;
                }

                setModalVisibility(true);
                refreshModalInputs();
            });

            if (timeframeMode) {
                timeframeMode.addEventListener('change', refreshModalInputs);
            }
            if (timeframeSpecificDay) {
                timeframeSpecificDay.addEventListener('change', refreshModalInputs);
            }
            if (timeframeSpecificMonth) {
                timeframeSpecificMonth.addEventListener('change', refreshModalInputs);
            }
            if (timeframeCustomFrom) {
                timeframeCustomFrom.addEventListener('change', refreshModalInputs);
            }
            if (timeframeCustomTo) {
                timeframeCustomTo.addEventListener('change', refreshModalInputs);
            }

            if (timeframeCancel) {
                timeframeCancel.addEventListener('click', function () {
                    setModalVisibility(false);
                });
            }

            if (timeframeModal) {
                timeframeModal.addEventListener('click', function (event) {
                    if (event.target === timeframeModal) {
                        setModalVisibility(false);
                    }
                });
            }

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && timeframeModal && !timeframeModal.hidden) {
                    setModalVisibility(false);
                }
            });

            if (timeframeConfirm) {
                timeframeConfirm.addEventListener('click', function () {
                    const timeframe = computeTimeframe();
                    if (!timeframe || !timeframeStartInput || !timeframeEndInput || !timeframeModeInput || !timeframeLabelInput) {
                        if (timeframePreview) {
                            timeframePreview.textContent = 'Please choose a valid timeframe before starting backup.';
                        }
                        return;
                    }

                    timeframeModeInput.value = timeframe.mode;
                    timeframeStartInput.value = formatLocalDateTime(timeframe.start);
                    timeframeEndInput.value = formatLocalDateTime(timeframe.end);
                    timeframeLabelInput.value = `${timeframe.label}: ${formatDateLabel(timeframe.start)} to ${formatDateLabel(timeframe.end)}`;

                    if (fromInput && toInput) {
                        fromInput.value = timeframeStartInput.value;
                        toInput.value = timeframeEndInput.value;
                    }

                    setModalVisibility(false);

                    createButton.disabled = true;
                    createButton.innerHTML = '<i class="fas fa-circle-notch fa-spin" aria-hidden="true"></i> Creating Backup...';
                    createForm.submit();
                });
            }

            // Initialize defaults
            const now = new Date();
            if (timeframeSpecificDay && !timeframeSpecificDay.value) {
                timeframeSpecificDay.value = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
            }
            if (timeframeSpecificMonth && !timeframeSpecificMonth.value) {
                timeframeSpecificMonth.value = `${now.getFullYear()}-${pad(now.getMonth() + 1)}`;
            }
            if (timeframeCustomFrom && !timeframeCustomFrom.value) {
                const start = new Date(now);
                start.setDate(now.getDate() - 7);
                timeframeCustomFrom.value = formatLocalDateTime(start);
            }
            if (timeframeCustomTo && !timeframeCustomTo.value) {
                timeframeCustomTo.value = formatLocalDateTime(now);
            }

            refreshModalInputs();

        }

        window.addEventListener('pageshow', function () {
            closeFilterPanel();

            if (timeframeModal) {
                timeframeModal.classList.remove('is-open');
                timeframeModal.hidden = true;
                timeframeModal.setAttribute('aria-hidden', 'true');
            }
        });
    })();

    async function verifyBackup(id, button) {
        const resultDiv = document.getElementById(`verify-result-${id}`);
        if (!resultDiv) {
            return;
        }

        const originalLabel = button ? button.innerHTML : null;
        if (button) {
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-circle-notch fa-spin" aria-hidden="true"></i> Verifying...';
        }

        resultDiv.className = 'backup-verify-result';
        resultDiv.textContent = 'Verifying backup integrity...';

        try {
            const response = await fetch(`/admin/backups/${id}/verify`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            const data = await response.json();

            if (data.valid) {
                resultDiv.className = 'backup-verify-result is-success';
                resultDiv.textContent = 'Checksum verified. Backup integrity is valid.';
            } else {
                resultDiv.className = 'backup-verify-result is-error';
                resultDiv.textContent = 'Checksum mismatch detected. Verify backup contents before use.';
            }
        } catch (error) {
            resultDiv.className = 'backup-verify-result is-error';
            resultDiv.textContent = `Verification error: ${error.message}`;
        } finally {
            if (button) {
                button.disabled = false;
                button.innerHTML = originalLabel;
            }
        }
    }
</script>
@endpush