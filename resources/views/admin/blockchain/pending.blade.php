@extends('layouts.admin')

@section('content')
    <div class="admin-container">
        <div class="admin-page-header">
            <div>
                <h1 class="admin-page-title">Pending Blockchain Queue</h1>
                <p class="admin-page-subtitle">Documents waiting for blockchain confirmation</p>
            </div>
            <a href="{{ route('admin.blockchain.index') }}" class="admin-btn admin-btn-secondary">
                Back to Dashboard
            </a>
        </div>

        <div class="admin-content">
            <div class="admin-table-card">
                    <div class="admin-table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Document ID</th>
                                    <th>Type</th>
                                    <th>Submitted By</th>
                                    <th>Submitted Date</th>
                                    <th>Time in Queue</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($documents as $document)
                                    <tr {{ $document->blockchain_submitted_at && $document->blockchain_submitted_at->diffInHours(now()) > 24 ? 'style="background: #fffbeb;"' : '' }}>
                                        <td>#{{ $document->id }}</td>
                                        <td>{{ str_replace('_', ' ', ucfirst($document->document_type)) }}</td>
                                        <td>{{ $document->processedBy?->name ?? $document->user?->name ?? '-' }}</td>
                                        <td>{{ $document->blockchain_submitted_at ? $document->blockchain_submitted_at->format('M d, Y H:i') : '-' }}</td>
                                        <td>
                                            @if($document->blockchain_submitted_at)
                                                <span {{ $document->blockchain_submitted_at->diffInHours(now()) > 24 ? 'style="color: var(--admin-warning); font-weight: 600;"' : '' }}>
                                                    {{ $document->blockchain_submitted_at->diffForHumans() }}
                                                </span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.documents.show', $document) }}" class="admin-link admin-link-primary">
                                                <i class="fas fa-eye"></i> View Document
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="admin-empty-state">
                                            <i class="fas fa-check-circle"></i>
                                            <p>No pending blockchain transactions. All documents are processed!</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="admin-pagination">
                        {{ $documents->links() }}
                    </div>
            </div>
        </div>
    </div>

@endsection