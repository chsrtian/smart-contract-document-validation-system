@extends('layouts.admin')

@section('content')
    <div class="admin-container">
        <div class="admin-page-header">
            <div>
                <h1 class="admin-page-title">Override History</h1>
                <p class="admin-page-subtitle">Critical admin override actions log</p>
            </div>
            <a href="{{ route('admin.corrections.index') }}" class="admin-btn admin-btn-secondary">
                Back to Corrections
            </a>
        </div>

        <div class="admin-content">
            <!-- Warning Banner -->
            <div class="admin-callout admin-callout-danger" style="margin-bottom: 1.5rem;">
                <p class="admin-text-sm">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>CRITICAL ACTIONS LOG:</strong> All entries on this page represent admin overrides of correction decisions. These actions are logged with CRITICAL severity for accountability and compliance.
                </p>
            </div>

            <!-- Filter Panel -->
            <div class="admin-filter-panel" style="margin-bottom: 1.5rem;">
                    <form method="GET" action="{{ route('admin.corrections.overrides.history') }}">
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                            <div>
                                <label for="override_type" class="admin-form-label">Override Type</label>
                                <select name="override_type" id="override_type" class="admin-form-select">
                                    <option value="">All Types</option>
                                    <option value="force_approve" {{ request('override_type') == 'force_approve' ? 'selected' : '' }}>Force Approve</option>
                                    <option value="force_reject" {{ request('override_type') == 'force_reject' ? 'selected' : '' }}>Force Reject</option>
                                </select>
                            </div>
                            <div style="display: flex; align-items: flex-end; grid-column: span 2;">
                                <div class="admin-btn-group">
                                    <button type="submit" class="admin-btn admin-btn-primary">
                                        <i class="fas fa-filter"></i> Apply Filter
                                    </button>
                                    <a href="{{ route('admin.corrections.overrides.history') }}" class="admin-btn admin-btn-secondary">
                                        <i class="fas fa-times"></i> Clear
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
            </div>

            <!-- Overrides Table -->
            <div class="admin-table-card">
                    <div class="admin-table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Correction ID</th>
                                    <th>Document</th>
                                    <th>Original Status</th>
                                    <th>Override Type</th>
                                    <th>Overridden By</th>
                                    <th>Override Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($overrides as $override)
                                    <tr>
                                        <td>#{{ $override->id }}</td>
                                        <td>
                                            <a href="{{ route('admin.documents.show', $override->scan) }}" class="admin-link admin-link-primary">
                                                Doc #{{ $override->scan_id }}
                                            </a>
                                        </td>
                                        <td>
                                            @if($override->override_type === 'force_approve')
                                                <span class="admin-badge admin-badge-danger">Rejected</span>
                                            @else
                                                <span class="admin-badge admin-badge-success">Approved</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="admin-badge admin-badge-danger">
                                                {{ $override->override_type === 'force_approve' ? 'Force Approve' : 'Force Reject' }}
                                            </span>
                                        </td>
                                        <td>{{ $override->overrider?->name ?? '-' }}</td>
                                        <td>{{ $override->override_at->format('M d, Y H:i:s') }}</td>
                                        <td>
                                            <div class="admin-btn-group">
                                                <button onclick="viewJustification({{ $override->id }})" class="admin-link admin-link-primary">
                                                    <i class="fas fa-eye"></i> View Justification
                                                </button>
                                                <a href="{{ route('admin.corrections.show', $override) }}" class="admin-link admin-link-primary">
                                                    <i class="fas fa-external-link-alt"></i> View Details
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr id="justification-{{ $override->id }}" style="display: none; background: #fff5f5; border-left: 3px solid var(--admin-danger);">
                                        <td colspan="7" style="padding: 1rem 1.5rem;">
                                            <p class="admin-text-sm admin-font-semibold" style="color: var(--admin-text-secondary); margin-bottom: 0.5rem;">Override Justification:</p>
                                            <p class="admin-text-sm" style="background: var(--admin-bg-card); padding: 0.75rem; border-radius: var(--admin-radius-md); border: 1px solid var(--admin-border-light);">{{ $override->override_justification }}</p>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="admin-empty-state">
                                            <i class="fas fa-history"></i>
                                            <p>No override actions found.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="admin-pagination">
                        {{ $overrides->links() }}
                    </div>
            </div>
        </div>
    </div>

    <script>
        function viewJustification(id) {
            const row = document.getElementById('justification-' + id);
            row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
        }
    </script>

@endsection