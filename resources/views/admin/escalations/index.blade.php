@extends('layouts.admin')

@section('content')
    <div class="admin-container">
        <div class="admin-page-header">
            <div>
                <h1 class="admin-page-title">Escalation Queue</h1>
                <p class="admin-page-subtitle">Corrections pending for more than 48 hours</p>
            </div>
            <a href="{{ route('admin.escalations.resolved') }}" class="admin-btn admin-btn-secondary">
                View Resolved
            </a>
        </div>

        <div class="admin-content">
            <!-- Info Banner -->
            <div class="admin-callout admin-callout-warning" style="margin-bottom: 1.5rem;">
                <p class="admin-text-sm">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Escalation Queue:</strong> These correction requests have been pending for more than 48 hours without supervisor action. Assign them to a supervisor or handle them directly.
                </p>
            </div>

            <!-- Escalations Table -->
            <div class="admin-table-card">
                    <div class="admin-table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Document</th>
                                    <th>Field</th>
                                    <th>Requester</th>
                                    <th>Requested</th>
                                    <th>Escalated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($escalations as $escalation)
                                    <tr>
                                        <td class="admin-font-semibold">#{{ $escalation->id }}</td>
                                        <td>
                                            <a href="{{ route('admin.documents.show', $escalation->scan) }}" class="admin-link">
                                                Doc #{{ $escalation->scan_id }}
                                            </a>
                                        </td>
                                        <td>{{ str_replace('_', ' ', ucfirst($escalation->field_name)) }}</td>
                                        <td>{{ $escalation->requester?->name ?? '-' }}</td>
                                        <td>
                                            {{ $escalation->requested_at->format('M d, Y') }}
                                            <span class="admin-text-xs" style="color: var(--admin-text-muted);">({{ $escalation->requested_at->diffForHumans() }})</span>
                                        </td>
                                        <td>
                                            {{ $escalation->escalated_at->format('M d, Y') }}
                                            <span class="admin-text-xs" style="color: var(--admin-warning);">({{ $escalation->escalated_at->diffForHumans() }})</span>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.corrections.show', $escalation) }}" class="admin-link">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <button onclick="showAssignModal({{ $escalation->id }})" class="admin-link admin-link-success" style="margin-left: 0.5rem;">
                                                <i class="fas fa-user-plus"></i> Assign
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7">
                                            <div class="admin-empty-state">
                                                <i class="fas fa-check-circle admin-empty-icon" style="color: var(--admin-success);"></i>
                                                <p class="admin-empty-title">No pending escalations</p>
                                                <p class="admin-empty-desc">Great job! The queue is clear.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="admin-pagination">
                        {{ $escalations->appends(request()->query())->links() }}
                    </div>
            </div>
        </div>
    </div>

    <!-- Assign Modal -->
    <div id="assignModal" class="admin-modal-overlay" style="display: none;">
        <div class="admin-modal">
            <div class="admin-modal-body">
                <h3 class="admin-section-title"><i class="fas fa-user-plus"></i> Assign to Supervisor</h3>
                <form id="assignForm" method="POST">
                    @csrf
                    <div style="margin-bottom: 1rem;">
                        <label for="supervisor_id" class="admin-form-label">Select Supervisor</label>
                        <select name="supervisor_id" id="supervisor_id" class="admin-form-select" required>
                            <option value="">-- Select Supervisor --</option>
                            @foreach($supervisors as $supervisor)
                                <option value="{{ $supervisor->id }}">{{ $supervisor->name }} ({{ $supervisor->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                        <button type="button" onclick="closeAssignModal()"
                            class="admin-btn admin-btn-secondary">
                            Cancel
                        </button>
                        <button type="submit"
                            class="admin-btn admin-btn-success">
                            <i class="fas fa-check"></i> Assign
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showAssignModal(correctionId) {
            const modal = document.getElementById('assignModal');
            const form = document.getElementById('assignForm');
            form.action = `/admin/escalations/${correctionId}/assign`;
            modal.style.display = 'flex';
        }

        function closeAssignModal() {
            const modal = document.getElementById('assignModal');
            modal.style.display = 'none';
            document.getElementById('supervisor_id').value = '';
        }
    </script>

@endsection