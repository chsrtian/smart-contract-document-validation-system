@extends('layouts.admin')

@section('content')
    <div class="admin-container">
        <div class="admin-page-header">
            <div>
                <h1 class="admin-page-title">Resolved Escalations</h1>
                <p class="admin-page-subtitle">Completed escalation cases</p>
            </div>
            <a href="{{ route('admin.escalations.index') }}" class="admin-btn admin-btn-secondary">
                Back to Queue
            </a>
        </div>

        <div class="admin-content">
            <div class="admin-table-card">
                    <div class="admin-table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Document</th>
                                    <th>Requester</th>
                                    <th>Assigned To</th>
                                    <th>Resolved By</th>
                                    <th>Resolution</th>
                                    <th>Resolved</th>
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
                                        <td>{{ $escalation->requester?->name ?? '-' }}</td>
                                        <td>{{ $escalation->assignedSupervisor?->name ?? '-' }}</td>
                                        <td>{{ $escalation->reviewer?->name ?? '-' }}</td>
                                        <td>
                                            <span class="admin-badge {{ $escalation->status === 'approved' ? 'admin-badge-success' : 'admin-badge-danger' }}">
                                                {{ ucfirst($escalation->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $escalation->reviewed_at?->format('M d, Y') }}</td>
                                        <td>
                                            <a href="{{ route('admin.corrections.show', $escalation) }}" class="admin-link">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8">
                                            <div class="admin-empty-state">
                                                <i class="fas fa-clipboard-check admin-empty-icon"></i>
                                                <p class="admin-empty-title">No resolved escalations found</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="admin-pagination">
                        {{ $escalations->links() }}
                    </div>
            </div>
        </div>
    </div>

@endsection