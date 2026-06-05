@extends('layouts.admin')

@section('content')
    <div class="admin-container">
        <div class="admin-page-header">
            <div>
                <h1 class="admin-page-title">Confirmed Blockchain Transactions</h1>
                <p class="admin-page-subtitle">Successfully anchored documents</p>
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
                                    <th>Transaction Hash</th>
                                    <th>Block Number</th>
                                    <th>Anchored Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($documents as $document)
                                    <tr>
                                        <td>#{{ $document->id }}</td>
                                        <td>{{ str_replace('_', ' ', ucfirst($document->document_type)) }}</td>
                                        <td style="font-family: monospace; font-size: 0.75rem;">
                                            @if($document->blockchain_tx_hash)
                                                <span title="{{ $document->blockchain_tx_hash }}">
                                                    {{ substr($document->blockchain_tx_hash, 0, 10) }}...{{ substr($document->blockchain_tx_hash, -8) }}
                                                </span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $document->blockchain_block_number ?? '-' }}</td>
                                        <td>{{ $document->blockchain_confirmed_at ? $document->blockchain_confirmed_at->format('M d, Y H:i') : '-' }}</td>
                                        <td>
                                            <div class="admin-btn-group">
                                                <a href="{{ route('admin.documents.show', $document) }}" class="admin-link admin-link-primary">
                                                    <i class="fas fa-file-alt"></i> View Doc
                                                </a>
                                                @if($document->blockchain_tx_hash)
                                                    <a href="{{ route('admin.blockchain.transaction-details', $document) }}" class="admin-link admin-link-success">
                                                        <i class="fas fa-link"></i> TX Details
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="admin-empty-state">
                                            <i class="fas fa-link"></i>
                                            <p>No confirmed blockchain transactions found.</p>
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