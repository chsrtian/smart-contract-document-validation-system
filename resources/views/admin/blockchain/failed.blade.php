@extends('layouts.admin')

@section('content')
    <div class="admin-container">
        <div class="admin-page-header">
            <div>
                <h1 class="admin-page-title">Failed Blockchain Transactions</h1>
                <p class="admin-page-subtitle">Review and retry failed anchoring attempts</p>
            </div>
            <a href="{{ route('admin.blockchain.index') }}" class="admin-btn admin-btn-secondary">
                Back to Dashboard
            </a>
        </div>

        <div class="admin-content">
            <!-- Bulk Actions -->
            <div class="admin-card" style="margin-bottom: 1.5rem;">
                <div class="admin-card-body">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h3 class="admin-section-title" style="margin-bottom: 0;"><i class="fas fa-times-circle"></i> Failed Transactions ({{ $documents->total() }})</h3>

                        <div class="admin-btn-group">
                            <!-- Retry Selected Button -->
                            <button onclick="retrySelected()"
                                    id="retrySelectedBtn"
                                    class="admin-btn admin-btn-primary"
                                    style="opacity: 0.5; cursor: not-allowed;"
                                    disabled>
                                <i class="fas fa-redo"></i> Retry Selected (<span id="selectedCount">0</span>)
                            </button>

                            <!-- Retry All Failed Button -->
                            <form action="{{ route('admin.blockchain.retry-all-failed') }}" method="POST"
                                  onsubmit="return confirm('Are you sure you want to retry ALL {{ $documents->total() }} failed transactions? This will queue them for blockchain anchoring.');">
                                @csrf
                                <button type="submit"
                                        class="admin-btn admin-btn-danger">
                                    <i class="fas fa-redo-alt"></i> Retry All Failed ({{ $documents->total() }})
                        </div>
                    </div>
                </div>
            </div>

            <!-- Failed Transactions Table -->
            <div class="admin-table-card">
                    @if($documents->isEmpty())
                        <div class="admin-empty-state">
                            <i class="fas fa-check-circle"></i>
                            <p>No failed transactions! All blockchain transactions are successful.</p>
                        </div>
                    @else
                        <form id="bulkRetryForm" action="{{ route('admin.blockchain.bulk-retry') }}" method="POST">
                            @csrf

                            <div class="admin-table-responsive">
                                <table class="admin-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 2rem;">
                                                <input type="checkbox" id="selectAll"
                                                       onchange="toggleSelectAll(this)">
                                            </th>
                                            <th>Document ID</th>
                                            <th>Type</th>
                                            <th>Uploaded By</th>
                                            <th>Failed At</th>
                                            <th>Error</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($documents as $document)
                                            <tr>
                                                <td>
                                                    <input type="checkbox"
                                                           name="document_ids[]"
                                                           value="{{ $document->id }}"
                                                           class="document-checkbox"
                                                           onchange="updateSelectedCount()">
                                                </td>
                                                <td>#{{ $document->id }}</td>
                                                <td>{{ ucwords(str_replace('_', ' ', $document->document_type)) }}</td>
                                                <td>{{ $document->user->name ?? 'N/A' }}</td>
                                                <td>{{ $document->updated_at->format('M d, Y H:i') }}</td>
                                                <td style="color: var(--admin-danger); max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                    {{ $document->blockchain_error ?? 'Unknown error' }}
                                                </td>
                                                <td>
                                                    <div class="admin-btn-group">
                                                        <form action="{{ route('admin.blockchain.retry', $document) }}" method="POST">
                                                            @csrf
                                                            <button type="submit" class="admin-link admin-link-primary">
                                                                <i class="fas fa-redo"></i> Retry
                                                            </button>
                                                        </form>

                                                        <a href="{{ route('admin.blockchain.transaction-details', $document) }}"
                                                           class="admin-link admin-link-primary">
                                                            <i class="fas fa-eye"></i> Details
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </form>

                        <!-- Pagination -->
                        <div class="admin-pagination">
                            {{ $documents->links() }}
                        </div>
                    @endif
            </div>
        </div>
    </div>

    <script>
        function toggleSelectAll(checkbox) {
            const checkboxes = document.querySelectorAll('.document-checkbox');
            checkboxes.forEach(cb => cb.checked = checkbox.checked);
            updateSelectedCount();
        }

        function updateSelectedCount() {
            const checked = document.querySelectorAll('.document-checkbox:checked').length;
            document.getElementById('selectedCount').textContent = checked;
            document.getElementById('retrySelectedBtn').disabled = checked === 0;
        }

        function retrySelected() {
            const form = document.getElementById('bulkRetryForm');
            const checked = document.querySelectorAll('.document-checkbox:checked').length;
            
            if (checked === 0) {
                alert('Please select at least one transaction to retry.');
                return;
            }

            if (confirm(`Are you sure you want to retry ${checked} selected transaction(s)?`)) {
                form.submit();
            }
        }
    </script>

@endsection