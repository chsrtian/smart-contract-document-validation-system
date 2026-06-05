@extends('layouts.admin')

@section('content')
    <div class="admin-container">
        <div class="admin-page-header">
            <div>
                <h1 class="admin-page-title">Audit Logs</h1>
                <p class="admin-page-subtitle">Complete system activity history</p>
            </div>
        </div>

        <div class="admin-content">
            <!-- Filter Panel -->
            <div class="admin-filter-panel" style="margin-bottom: 1.5rem;">
                <form method="GET" action="{{ route('admin.audit-logs.index') }}">
                    <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 1rem; margin-bottom: 1rem;">
                        <!-- User Filter -->
                        <div>
                            <label for="user_id" class="admin-form-label">User</label>
                            <select name="user_id" id="user_id" class="admin-form-select">
                                <option value="">All Users</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Action Type Filter -->
                        <div>
                            <label for="action_type" class="admin-form-label">Action Type</label>
                            <select name="action_type" id="action_type" class="admin-form-select">
                                <option value="">All Actions</option>
                                @foreach($actionTypes as $type)
                                    <option value="{{ $type }}" {{ request('action_type') == $type ? 'selected' : '' }}>
                                        {{ $type }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Severity Filter -->
                        <div>
                            <label for="severity" class="admin-form-label">Severity</label>
                            <select name="severity" id="severity" class="admin-form-select">
                                <option value="">All Severities</option>
                                <option value="info" {{ request('severity') == 'info' ? 'selected' : '' }}>Info</option>
                                <option value="warning" {{ request('severity') == 'warning' ? 'selected' : '' }}>Warning</option>
                                <option value="critical" {{ request('severity') == 'critical' ? 'selected' : '' }}>Critical</option>
                            </select>
                        </div>

                        <!-- Date From -->
                        <div>
                            <label for="date_from" class="admin-form-label">Date From</label>
                            <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" class="admin-form-input">
                        </div>

                        <!-- Date To -->
                        <div>
                            <label for="date_to" class="admin-form-label">Date To</label>
                            <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" class="admin-form-input">
                        </div>

                        <!-- Search -->
                        <div>
                            <label for="search" class="admin-form-label">Search Notes</label>
                            <input type="text" name="search" id="search" value="{{ request('search') }}"
                                placeholder="Search in notes..." class="admin-form-input">
                        </div>
                    </div>

                    <div class="admin-btn-group">
                        <button type="submit" class="admin-btn admin-btn-primary">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        <a href="{{ route('admin.audit-logs.index') }}" class="admin-btn admin-btn-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                        <a href="{{ route('admin.audit-logs.export-csv', request()->all()) }}"
                           class="admin-btn admin-btn-success">
                            <i class="fas fa-file-csv"></i> Export CSV
                        </a>
                        <a href="{{ route('admin.audit-logs.export-pdf', request()->all()) }}"
                           class="admin-btn admin-btn-danger">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </a>
                    </div>
                </form>
            </div>

            <!-- Audit Logs Table -->
            <div class="admin-tier-header">
                <span class="admin-tier-label">Activity Log</span>
                <span class="admin-tier-rule"></span>
            </div>

            <div class="admin-table-card">
                    <div class="admin-table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Timestamp</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Target</th>
                                    <th>Severity</th>
                                    <th>IP Address</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($auditLogs as $log)
                                    <tr>
                                        <td style="white-space: nowrap;">
                                            {{ $log->timestamp->format('M d, Y H:i:s') }}
                                        </td>
                                        <td style="white-space: nowrap;">{{ $log->user_name }}</td>
                                        <td style="white-space: nowrap;"><span class="admin-mono" style="padding: 0.125rem 0.5rem; font-size: 0.75rem;">{{ $log->action_type }}</span></td>
                                        <td style="white-space: nowrap;">
                                            @if($log->target_entity_type)
                                                {{ class_basename($log->target_entity_type) }} #{{ $log->target_entity_id }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td style="white-space: nowrap;">
                                            <span class="admin-badge
                                                {{ $log->severity === 'info' ? 'admin-badge-info' : '' }}
                                                {{ $log->severity === 'warning' ? 'admin-badge-warning' : '' }}
                                                {{ $log->severity === 'critical' ? 'admin-badge-danger' : '' }}">
                                                {{ ucfirst($log->severity) }}
                                            </span>
                                        </td>
                                        <td style="white-space: nowrap;"><span class="admin-mono" style="padding: 0.125rem 0.5rem; font-size: 0.75rem;">{{ $log->ip_address }}</span></td>
                                        <td>
                                            <button onclick="toggleDetails({{ $log->id }})"
                                                class="admin-link admin-link-primary">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>
                                    <tr id="details-{{ $log->id }}" style="display: none; background: var(--admin-bg-muted);">
                                        <td colspan="7" style="padding: 1rem 1.5rem;">
                                            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                                                @if($log->notes)
                                                    <div>
                                                        <span class="admin-detail-label">Notes</span>
                                                        <p class="admin-text-sm" style="margin-top: 0.25rem;">{{ $log->notes }}</p>
                                                    </div>
                                                @endif

                                                @if($log->previous_value)
                                                    <div>
                                                        <span class="admin-detail-label">Previous Value</span>
                                                        <pre class="admin-mono" style="margin-top: 0.25rem; font-size: 0.75rem;">{{ json_encode($log->previous_value, JSON_PRETTY_PRINT) }}</pre>
                                                    </div>
                                                @endif

                                                @if($log->new_value)
                                                    <div>
                                                        <span class="admin-detail-label">New Value</span>
                                                        <pre class="admin-mono" style="margin-top: 0.25rem; font-size: 0.75rem;">{{ json_encode($log->new_value, JSON_PRETTY_PRINT) }}</pre>
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7">
                                            <div class="admin-empty-state">
                                                <i class="fas fa-clipboard-list admin-empty-icon"></i>
                                                <p class="admin-empty-title">No audit logs found</p>
                                                <p class="admin-empty-description">Try adjusting your filters.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="admin-pagination">
                        {{ $auditLogs->links() }}
                    </div>
            </div>
        </div>
    </div>

    <script>
        function toggleDetails(id) {
            const detailsRow = document.getElementById('details-' + id);
            detailsRow.style.display = detailsRow.style.display === 'none' ? 'table-row' : 'none';
        }
    </script>

@endsection