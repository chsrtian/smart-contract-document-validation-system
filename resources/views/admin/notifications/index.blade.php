@extends('layouts.admin')

@section('content')
    @php
        $typeOptions = [
            'escalation' => 'Escalation',
            'backup_failed' => 'Backup Failed',
            'blockchain_failed' => 'Blockchain Failed',
            'system_error' => 'System Error',
            'document_flagged' => 'Document Flagged',
            'override_used' => 'Override Used',
            'user_deactivated' => 'User Deactivated',
            'monthly_report' => 'Monthly Report',
            'backup_success' => 'Backup Success',
        ];

        $selectedStatus = (string) request('status', '');
        $selectedSeverity = (string) request('severity', '');
        $selectedType = (string) request('type', '');
        $attentionCount = (int) ($stats['critical'] ?? 0) + (int) ($stats['warning'] ?? 0);

        $activeFilters = collect([
            $selectedStatus !== '' ? ['label' => 'Status', 'value' => ucfirst($selectedStatus)] : null,
            $selectedSeverity !== '' ? ['label' => 'Severity', 'value' => ucfirst($selectedSeverity)] : null,
            $selectedType !== '' ? ['label' => 'Type', 'value' => ($typeOptions[$selectedType] ?? ucwords(str_replace('_', ' ', $selectedType)))] : null,
        ])->filter()->values();

        $notificationCollection = $notifications->getCollection();
        $notificationKey = static function ($notification) {
            return implode('|', [
                $notification->severity,
                $notification->type,
                trim((string) $notification->title),
                trim((string) $notification->message),
            ]);
        };
        $duplicateCounts = $notificationCollection->groupBy($notificationKey)->map->count();
    @endphp

    <div class="admin-container anp-page">
        <div class="admin-page-header anp-header">
            <div>
                <h1 class="admin-page-title">Notifications</h1>
                <p class="admin-page-subtitle">Safety-focused alert queue with severity-first prioritization</p>
            </div>

            <div class="anp-header-actions" aria-label="Notification bulk actions">
                @if(($stats['unread'] ?? 0) > 0)
                    <form action="{{ route('admin.notifications.mark-all-read') }}" method="POST" onsubmit="return confirm('Mark all unread notifications as read? This may hide pending alerts from the unread queue.');">
                        @csrf
                        <button type="submit" class="admin-btn admin-btn-secondary">
                            <i class="fas fa-check-double" aria-hidden="true"></i>
                            Mark All Unread as Read
                        </button>
                    </form>
                @endif
                <span class="anp-header-hint" aria-live="polite">Unread: {{ number_format((int) ($stats['unread'] ?? 0)) }}</span>
            </div>
        </div>

        <section class="anp-stats" aria-label="Notification summary metrics">
            <article class="anp-stat anp-stat--attention" aria-label="{{ number_format($attentionCount) }} unread notifications require attention">
                <p class="anp-stat-label">Needs Attention</p>
                <p class="anp-stat-value">{{ number_format($attentionCount) }}</p>
                <p class="anp-stat-meta">Unread critical and warning alerts</p>
            </article>

            <article class="anp-stat anp-stat--critical" aria-label="{{ number_format((int) ($stats['critical'] ?? 0)) }} unread critical notifications">
                <p class="anp-stat-label">Critical Unread</p>
                <p class="anp-stat-value">{{ number_format((int) ($stats['critical'] ?? 0)) }}</p>
                <p class="anp-stat-meta">Highest priority incidents</p>
            </article>

            <article class="anp-stat anp-stat--unread" aria-label="{{ number_format((int) ($stats['unread'] ?? 0)) }} unread notifications">
                <p class="anp-stat-label">Unread Total</p>
                <p class="anp-stat-value">{{ number_format((int) ($stats['unread'] ?? 0)) }}</p>
                <p class="anp-stat-meta">Pending review in queue</p>
            </article>
        </section>

        <section class="admin-filter-panel anp-filter-panel" aria-label="Notification filters">
            <form method="GET" action="{{ route('admin.notifications.index') }}" class="anp-filter-form">
                <div class="anp-filter-grid">
                    <div class="anp-filter-field">
                        <label for="anp-status" class="admin-form-label">Read Status</label>
                        <select id="anp-status" name="status" class="admin-form-select">
                            <option value="">All statuses</option>
                            <option value="unread" {{ $selectedStatus === 'unread' ? 'selected' : '' }}>Unread</option>
                            <option value="read" {{ $selectedStatus === 'read' ? 'selected' : '' }}>Read</option>
                        </select>
                    </div>

                    <div class="anp-filter-field">
                        <label for="anp-severity" class="admin-form-label">Severity</label>
                        <select id="anp-severity" name="severity" class="admin-form-select">
                            <option value="">All severities</option>
                            <option value="critical" {{ $selectedSeverity === 'critical' ? 'selected' : '' }}>Critical</option>
                            <option value="warning" {{ $selectedSeverity === 'warning' ? 'selected' : '' }}>Warning</option>
                            <option value="info" {{ $selectedSeverity === 'info' ? 'selected' : '' }}>Info</option>
                        </select>
                    </div>

                    <div class="anp-filter-field">
                        <label for="anp-type" class="admin-form-label">Type</label>
                        <select id="anp-type" name="type" class="admin-form-select">
                            <option value="">All types</option>
                            @foreach($typeOptions as $typeValue => $typeLabel)
                                <option value="{{ $typeValue }}" {{ $selectedType === $typeValue ? 'selected' : '' }}>{{ $typeLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="anp-filter-actions">
                        <button type="submit" class="admin-btn admin-btn-primary">
                            <i class="fas fa-filter" aria-hidden="true"></i>
                            Apply Filters
                        </button>

                        @if($activeFilters->isNotEmpty())
                            <a href="{{ route('admin.notifications.index') }}" class="admin-btn admin-btn-secondary">
                                <i class="fas fa-rotate-left" aria-hidden="true"></i>
                                Reset
                            </a>
                        @endif
                    </div>
                </div>

                @if($activeFilters->isNotEmpty())
                    <div class="anp-active-filters" aria-live="polite">
                        <span class="anp-active-filters-label">Active filters:</span>
                        @foreach($activeFilters as $filter)
                            <span class="anp-filter-chip">{{ $filter['label'] }}: {{ $filter['value'] }}</span>
                        @endforeach
                    </div>
                @endif
            </form>
        </section>

        <section class="admin-card anp-list-card" aria-label="Notifications list">
            <div class="admin-card-body">
                <div class="anp-list-header">
                    <h2 class="anp-list-title">Notification Queue</h2>
                    <p class="anp-list-meta">Showing {{ number_format($notifications->count()) }} of {{ number_format($notifications->total()) }} notification{{ $notifications->total() === 1 ? '' : 's' }}</p>
                </div>

                @if($notifications->isEmpty())
                    <div class="admin-empty-state">
                        <i class="fas fa-bell admin-empty-icon" aria-hidden="true"></i>
                        <p class="admin-empty-title">No notifications in this view</p>
                        <p class="admin-empty-description">Try adjusting filters or check again after new system activity.</p>
                    </div>
                @else
                    <div class="anp-list" role="list" aria-label="Notification results">
                        @foreach($notifications as $notification)
                            @php
                                $severity = (string) $notification->severity;
                                $severityIcon = match ($severity) {
                                    'critical' => 'fa-circle-exclamation',
                                    'warning' => 'fa-triangle-exclamation',
                                    default => 'fa-circle-info',
                                };
                                $isUnread = !$notification->is_read;
                                $duplicateCount = (int) ($duplicateCounts->get($notificationKey($notification), 1));
                            @endphp

                            <article class="anp-item anp-item--{{ $severity }} {{ $isUnread ? 'anp-item--unread' : 'anp-item--read' }}" role="listitem">
                                <div class="anp-item-main">
                                    <div class="anp-item-meta-row">
                                        <span class="anp-severity-badge anp-severity-badge--{{ $severity }}" aria-label="Severity {{ strtoupper($severity) }}">
                                            <i class="fas {{ $severityIcon }}" aria-hidden="true"></i>
                                            {{ strtoupper($severity) }}
                                        </span>

                                        <span class="anp-type-badge">{{ $notification->type_label }}</span>

                                        <span class="anp-status-badge {{ $isUnread ? 'is-unread' : 'is-read' }}">
                                            {{ $isUnread ? 'Unread' : 'Read' }}
                                        </span>

                                        @if($duplicateCount > 1)
                                            <span class="anp-duplicate-badge">Repeated x{{ $duplicateCount }}</span>
                                        @endif
                                    </div>

                                    <h3 class="anp-item-title">{{ $notification->title }}</h3>
                                    <p class="anp-item-message">{{ $notification->message }}</p>

                                    <div class="anp-item-foot">
                                        <span><i class="fas fa-clock" aria-hidden="true"></i> Created {{ $notification->created_at->diffForHumans() }}</span>
                                        @if($notification->read_at)
                                            <span><i class="fas fa-check" aria-hidden="true"></i> Read {{ $notification->read_at->diffForHumans() }}</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="anp-item-actions" aria-label="Actions for {{ $notification->title }}">
                                    @if($isUnread)
                                        <form action="{{ route('admin.notifications.mark-read', $notification) }}" method="POST" class="anp-inline-form">
                                            @csrf
                                            <button type="submit" class="admin-btn admin-btn-secondary admin-btn-sm">
                                                <i class="fas fa-check" aria-hidden="true"></i>
                                                Mark Read
                                            </button>
                                        </form>
                                    @else
                                        <span class="anp-action-muted" aria-hidden="true">Already read</span>
                                    @endif

                                    <form id="anp-delete-form-{{ $notification->id }}" action="{{ route('admin.notifications.destroy', $notification) }}" method="POST" class="anp-inline-form">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="button"
                                            class="anp-delete-trigger"
                                            data-delete-form="anp-delete-form-{{ $notification->id }}"
                                            data-notification-title="{{ e(\Illuminate\Support\Str::limit($notification->title, 100)) }}"
                                            aria-label="Delete notification {{ $notification->title }}"
                                        >
                                            <i class="fas fa-trash" aria-hidden="true"></i>
                                            Delete...
                                        </button>
                                    </form>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="admin-pagination">
                        {{ $notifications->withQueryString()->links() }}
                    </div>
                @endif
            </div>
        </section>
    </div>

    <div id="anp-delete-modal" class="admin-modal-overlay anp-modal-overlay" style="display: none;" aria-hidden="true">
        <div class="admin-modal admin-modal-sm" role="dialog" aria-modal="true" aria-labelledby="anp-delete-modal-title" aria-describedby="anp-delete-modal-desc">
            <div class="admin-modal-header">
                <h3 id="anp-delete-modal-title" class="admin-modal-title admin-modal-title-danger">
                    <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
                    Confirm Notification Deletion
                </h3>
            </div>
            <div class="admin-modal-body">
                <p id="anp-delete-modal-desc" class="anp-modal-text">This permanently removes the notification from the admin queue.</p>
                <p class="anp-modal-target" id="anp-delete-modal-target"></p>
                <p class="anp-modal-warning">
                    <i class="fas fa-shield-alt" aria-hidden="true"></i>
                    Delete only when the alert is no longer needed for operational follow-up.
                </p>
            </div>
            <div class="admin-modal-footer">
                <button type="button" id="anp-delete-cancel" class="admin-btn admin-btn-secondary">Cancel</button>
                <button type="button" id="anp-delete-confirm" class="admin-btn admin-btn-danger">Delete Notification</button>
            </div>
        </div>
    </div>

@endsection

@push('styles')
<style>
    .anp-page {
        --anp-critical-bg: #fef2f2;
        --anp-critical-border: #fecaca;
        --anp-critical-text: #8f1d1d;
        --anp-warning-bg: #fff7ed;
        --anp-warning-border: #fed7aa;
        --anp-warning-text: #9a3412;
        --anp-info-bg: #eef6ff;
        --anp-info-border: #bfdbfe;
        --anp-info-text: #1e40af;
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }

    .anp-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .anp-header-actions {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .anp-header-hint {
        display: inline-flex;
        align-items: center;
        min-height: 2.25rem;
        padding: 0.4rem 0.75rem;
        border-radius: 999px;
        border: 1px solid var(--admin-border-light);
        background: var(--admin-bg-card);
        color: var(--admin-text-secondary);
        font-weight: 700;
        font-size: 0.8125rem;
    }

    .anp-stats {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1rem;
    }

    .anp-stat {
        background: var(--admin-bg-card);
        border: 1px solid var(--admin-border-light);
        border-radius: var(--admin-radius-lg);
        padding: 1rem 1.15rem;
        box-shadow: var(--admin-shadow-card);
    }

    .anp-stat-label {
        margin: 0;
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--admin-text-muted);
    }

    .anp-stat-value {
        margin: 0.4rem 0 0;
        font-size: 2rem;
        line-height: 1;
        font-weight: 800;
        color: var(--admin-text-primary);
    }

    .anp-stat-meta {
        margin: 0.4rem 0 0;
        font-size: 0.8125rem;
        color: var(--admin-text-muted);
    }

    .anp-stat--attention {
        border-left: 4px solid var(--admin-danger);
    }

    .anp-stat--critical {
        border-left: 4px solid #b91c1c;
    }

    .anp-stat--critical .anp-stat-value,
    .anp-stat--attention .anp-stat-value {
        color: #991b1b;
    }

    .anp-stat--unread {
        border-left: 4px solid var(--admin-primary);
    }

    .anp-filter-panel {
        margin-bottom: 0;
    }

    .anp-filter-form {
        display: flex;
        flex-direction: column;
        gap: 0.9rem;
    }

    .anp-filter-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr)) auto;
        gap: 0.75rem;
        align-items: end;
    }

    .anp-filter-field {
        min-width: 0;
    }

    .anp-filter-actions {
        display: flex;
        gap: 0.5rem;
        align-items: center;
        flex-wrap: wrap;
    }

    .anp-active-filters {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        flex-wrap: wrap;
    }

    .anp-active-filters-label {
        font-size: 0.8125rem;
        font-weight: 700;
        color: var(--admin-text-secondary);
    }

    .anp-filter-chip {
        display: inline-flex;
        align-items: center;
        padding: 0.2rem 0.55rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--admin-text-secondary);
        background: var(--admin-bg-muted);
        border: 1px solid var(--admin-border-light);
    }

    .anp-list-card {
        overflow: visible;
    }

    .anp-list-header {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: 0.75rem;
        flex-wrap: wrap;
        margin-bottom: 1rem;
    }

    .anp-list-title {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--admin-text-primary);
    }

    .anp-list-meta {
        margin: 0;
        font-size: 0.8125rem;
        color: var(--admin-text-muted);
    }

    .anp-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .anp-item {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        border: 1px solid var(--admin-border-light);
        border-left-width: 5px;
        border-radius: var(--admin-radius-lg);
        padding: 1rem;
        background: var(--admin-bg-card);
    }

    .anp-item--critical {
        border-left-color: #b91c1c;
    }

    .anp-item--warning {
        border-left-color: #c2410c;
    }

    .anp-item--info {
        border-left-color: #1d4ed8;
    }

    .anp-item--unread.anp-item--critical {
        background: var(--anp-critical-bg);
        border-color: var(--anp-critical-border);
    }

    .anp-item--unread.anp-item--warning {
        background: var(--anp-warning-bg);
        border-color: var(--anp-warning-border);
    }

    .anp-item--unread.anp-item--info {
        background: var(--anp-info-bg);
        border-color: var(--anp-info-border);
    }

    .anp-item-main {
        flex: 1;
        min-width: 0;
    }

    .anp-item-meta-row {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
        margin-bottom: 0.6rem;
    }

    .anp-severity-badge,
    .anp-type-badge,
    .anp-status-badge,
    .anp-duplicate-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        min-height: 1.55rem;
        padding: 0.15rem 0.6rem;
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        border: 1px solid transparent;
    }

    .anp-severity-badge--critical {
        background: var(--anp-critical-bg);
        color: var(--anp-critical-text);
        border-color: var(--anp-critical-border);
    }

    .anp-severity-badge--warning {
        background: var(--anp-warning-bg);
        color: var(--anp-warning-text);
        border-color: var(--anp-warning-border);
    }

    .anp-severity-badge--info {
        background: var(--anp-info-bg);
        color: var(--anp-info-text);
        border-color: var(--anp-info-border);
    }

    .anp-type-badge {
        color: var(--admin-text-secondary);
        background: #f8fafc;
        border-color: #cbd5e1;
    }

    .anp-status-badge.is-unread {
        color: #1e3a8a;
        background: #dbeafe;
        border-color: #93c5fd;
    }

    .anp-status-badge.is-read {
        color: #475569;
        background: #f1f5f9;
        border-color: #cbd5e1;
    }

    .anp-duplicate-badge {
        color: #92400e;
        background: #fffbeb;
        border-color: #fcd34d;
    }

    .anp-item-title {
        margin: 0;
        font-size: 1rem;
        line-height: 1.35;
        font-weight: 800;
        color: var(--admin-text-primary);
    }

    .anp-item-message {
        margin: 0.35rem 0 0;
        font-size: 0.9rem;
        color: #334155;
        line-height: 1.45;
        max-width: 88ch;
    }

    .anp-item-foot {
        margin-top: 0.55rem;
        display: flex;
        flex-wrap: wrap;
        gap: 0.8rem;
        font-size: 0.78rem;
        color: #64748b;
    }

    .anp-item-actions {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.5rem;
        min-width: 11rem;
    }

    .anp-inline-form {
        margin: 0;
    }

    .anp-action-muted {
        font-size: 0.75rem;
        color: var(--admin-text-muted);
        font-weight: 600;
        padding: 0.35rem 0;
    }

    .anp-delete-trigger {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        min-height: 2rem;
        min-width: 8.8rem;
        padding: 0.35rem 0.8rem;
        font-size: 0.8rem;
        font-weight: 700;
        color: #991b1b;
        background: #fff;
        border: 1px solid #fecaca;
        border-radius: var(--admin-radius-md);
        cursor: pointer;
        transition: all var(--admin-transition-fast);
    }

    .anp-delete-trigger:hover {
        background: #fef2f2;
        border-color: #fca5a5;
    }

    .anp-delete-trigger:focus-visible,
    .anp-page :is(a, button, select):focus-visible {
        outline: 3px solid rgba(79, 70, 229, 0.35);
        outline-offset: 2px;
    }

    .anp-modal-overlay {
        align-items: center;
        justify-content: center;
    }

    .anp-modal-text {
        margin: 0;
        font-size: 0.9rem;
        color: var(--admin-text-secondary);
    }

    .anp-modal-target {
        margin: 0.6rem 0 0;
        padding: 0.6rem 0.75rem;
        border-radius: var(--admin-radius-md);
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: var(--admin-text-primary);
        font-size: 0.85rem;
        font-weight: 600;
        word-break: break-word;
    }

    .anp-modal-warning {
        margin: 0.7rem 0 0;
        display: flex;
        align-items: flex-start;
        gap: 0.45rem;
        font-size: 0.82rem;
        color: #7f1d1d;
        background: #fff1f2;
        border: 1px solid #fecdd3;
        border-radius: var(--admin-radius-md);
        padding: 0.55rem 0.65rem;
    }

    @media (max-width: 960px) {
        .anp-stats {
            grid-template-columns: 1fr;
        }

        .anp-filter-grid {
            grid-template-columns: 1fr;
        }

        .anp-item {
            flex-direction: column;
        }

        .anp-item-actions {
            width: 100%;
            min-width: 0;
            flex-direction: row;
            justify-content: flex-start;
            align-items: center;
            flex-wrap: wrap;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    (function () {
        const modal = document.getElementById('anp-delete-modal');
        const cancelButton = document.getElementById('anp-delete-cancel');
        const confirmButton = document.getElementById('anp-delete-confirm');
        const targetText = document.getElementById('anp-delete-modal-target');
        const deleteTriggers = document.querySelectorAll('.anp-delete-trigger');

        if (!modal || !cancelButton || !confirmButton || !targetText || deleteTriggers.length === 0) {
            return;
        }

        let activeFormId = null;

        const closeModal = function () {
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
            activeFormId = null;
        };

        deleteTriggers.forEach(function (trigger) {
            trigger.addEventListener('click', function () {
                activeFormId = trigger.getAttribute('data-delete-form');
                targetText.textContent = trigger.getAttribute('data-notification-title') || 'Selected notification';
                modal.style.display = 'flex';
                modal.setAttribute('aria-hidden', 'false');
                cancelButton.focus();
            });
        });

        cancelButton.addEventListener('click', closeModal);

        confirmButton.addEventListener('click', function () {
            if (!activeFormId) {
                closeModal();
                return;
            }

            const form = document.getElementById(activeFormId);
            if (form) {
                form.submit();
                return;
            }

            closeModal();
        });

        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') {
                closeModal();
            }
        });
    })();
</script>
@endpush