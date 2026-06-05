@extends('layouts.admin')

@section('content')
    @php
        $naText = 'Not available';
        $documentHash = $document->document_hash ?: null;
        $txHash = $document->blockchain_tx_hash ?: null;
        $blockHash = $document->blockchain_block_hash ?: null;
        $fromAddress = $document->blockchain_from_address ?: null;
        $toAddress = $document->blockchain_to_address ?: null;
        $statusCode = $document->blockchain_status_code;
        $statusCodeNormalized = is_null($statusCode) ? null : (int) $statusCode;
        $isSuccess = $statusCodeNormalized === 1;
        $isFailure = $statusCodeNormalized === 0;

        $gasPriceRaw = $document->blockchain_gas_price;
        $gasPriceDisplay = $naText;
        if (is_numeric($gasPriceRaw)) {
            $gasPriceDisplay = number_format(((float) $gasPriceRaw) / 1000000000, 2) . ' Gwei';
        } elseif (!empty($gasPriceRaw)) {
            $gasPriceDisplay = $gasPriceRaw;
        }

        $submittedAt = $document->blockchain_submitted_at;
        $confirmedAt = $document->blockchain_confirmed_at;
        $processingTimeDisplay = $naText;
        if ($submittedAt && $confirmedAt) {
            $seconds = $submittedAt->diffInSeconds($confirmedAt);
            if ($seconds < 60) {
                $processingTimeDisplay = $seconds . ' second' . ($seconds === 1 ? '' : 's');
            } else {
                $minutes = intdiv($seconds, 60);
                $remainingSeconds = $seconds % 60;
                $processingTimeDisplay = $minutes . ' minute' . ($minutes === 1 ? '' : 's');
                if ($remainingSeconds > 0) {
                    $processingTimeDisplay .= ' ' . $remainingSeconds . ' second' . ($remainingSeconds === 1 ? '' : 's');
                }
            }
        }
    @endphp

    <div class="admin-container">
        <nav class="tx-details-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('admin.dashboard') }}">Dashboard</a>
            <span aria-hidden="true">/</span>
            <a href="{{ route('admin.blockchain.confirmed') }}">Confirmed Transactions</a>
            <span aria-hidden="true">/</span>
            <span class="tx-details-breadcrumb-current" aria-current="page">Transaction #{{ $document->id }}</span>
        </nav>

        <div class="admin-page-header">
            <div>
                <h1 class="admin-page-title">Blockchain Transaction Details</h1>
                <p class="admin-page-subtitle">Immutable transaction information</p>
            </div>
            <a href="{{ route('admin.blockchain.confirmed') }}" class="admin-btn admin-btn-secondary">
                Back to Confirmed
            </a>
        </div>

        <div class="admin-content">
            <div class="tx-details-callout" role="note" aria-label="Blockchain immutability notice">
                <i class="fas fa-lock" aria-hidden="true"></i>
                <p>
                    <strong>Blockchain Immutability:</strong>
                    This transaction has been permanently recorded on the blockchain and cannot be modified or deleted.
                    Admin can only view this information for verification and auditing purposes.
                </p>
            </div>

            <div class="tx-details-card">
                <div class="tx-details-card-header">
                    <h3 class="admin-section-title"><i class="fas fa-file-alt"></i> Document</h3>
                </div>
                <div class="tx-details-grid">
                    <div class="tx-details-row">
                        <span class="tx-details-label">Document ID</span>
                        <span class="tx-details-value">#{{ $document->id }}</span>
                    </div>
                    <div class="tx-details-row">
                        <span class="tx-details-label">Document type</span>
                        <span class="tx-details-value">{{ str_replace('_', ' ', ucfirst($document->document_type)) }}</span>
                    </div>
                    <div class="tx-details-row">
                        <span class="tx-details-label">Document hash</span>
                        <div class="tx-details-copy-wrap">
                            <code class="tx-details-mono" title="{{ $documentHash ?? $naText }}">{{ $documentHash ?? $naText }}</code>
                            @if($documentHash)
                                <button type="button" class="tx-copy-btn" data-copy-value="{{ $documentHash }}" aria-label="Copy document hash">
                                    <i class="far fa-copy" aria-hidden="true"></i>
                                    <span>Copy</span>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="tx-details-card tx-details-read-only">
                <div class="tx-details-card-header">
                    <h3 class="admin-section-title"><i class="fas fa-link"></i> Transaction <span class="tx-details-read-only-badge"><i class="fas fa-lock"></i> Read-only</span></h3>
                </div>
                <div class="tx-details-grid">
                    <div class="tx-details-row">
                        <span class="tx-details-label">Transaction hash</span>
                        <div class="tx-details-copy-wrap">
                            <code class="tx-details-mono tx-details-highlight" title="{{ $txHash ?? $naText }}">{{ $txHash ?? $naText }}</code>
                            @if($txHash)
                                <button type="button" class="tx-copy-btn" data-copy-value="{{ $txHash }}" aria-label="Copy transaction hash">
                                    <i class="far fa-copy" aria-hidden="true"></i>
                                    <span>Copy</span>
                                </button>
                            @endif
                        </div>
                    </div>
                    <div class="tx-details-row">
                        <span class="tx-details-label">Block number</span>
                        <span class="tx-details-value">{{ $document->blockchain_block_number ?? $naText }}</span>
                    </div>
                    <div class="tx-details-row">
                        <span class="tx-details-label">Block hash</span>
                        <div class="tx-details-copy-wrap">
                            <code class="tx-details-mono" title="{{ $blockHash ?? $naText }}">{{ $blockHash ?? $naText }}</code>
                            @if($blockHash)
                                <button type="button" class="tx-copy-btn" data-copy-value="{{ $blockHash }}" aria-label="Copy block hash">
                                    <i class="far fa-copy" aria-hidden="true"></i>
                                    <span>Copy</span>
                                </button>
                            @endif
                        </div>
                    </div>
                    <div class="tx-details-row">
                        <span class="tx-details-label">Confirmations</span>
                        <span class="tx-details-value">{{ $document->blockchain_confirmations ?? $naText }}</span>
                    </div>
                    <div class="tx-details-row">
                        <span class="tx-details-label">From address</span>
                        <div class="tx-details-copy-wrap">
                            <code class="tx-details-mono" title="{{ $fromAddress ?? $naText }}">{{ $fromAddress ?? $naText }}</code>
                            @if($fromAddress)
                                <button type="button" class="tx-copy-btn" data-copy-value="{{ $fromAddress }}" aria-label="Copy sender address">
                                    <i class="far fa-copy" aria-hidden="true"></i>
                                    <span>Copy</span>
                                </button>
                            @endif
                        </div>
                    </div>
                    <div class="tx-details-row">
                        <span class="tx-details-label">To address</span>
                        <div class="tx-details-copy-wrap">
                            <code class="tx-details-mono" title="{{ $toAddress ?? $naText }}">{{ $toAddress ?? $naText }}</code>
                            @if($toAddress)
                                <button type="button" class="tx-copy-btn" data-copy-value="{{ $toAddress }}" aria-label="Copy recipient address">
                                    <i class="far fa-copy" aria-hidden="true"></i>
                                    <span>Copy</span>
                                </button>
                            @endif
                        </div>
                    </div>
                    <div class="tx-details-row">
                        <span class="tx-details-label">Gas used</span>
                        <span class="tx-details-value">{{ $document->blockchain_gas_used ? number_format($document->blockchain_gas_used) : $naText }}</span>
                    </div>
                    <div class="tx-details-row">
                        <span class="tx-details-label">Gas price</span>
                        <span class="tx-details-value" title="{{ $gasPriceRaw ?? $naText }}">{{ $gasPriceDisplay }}</span>
                    </div>
                    <div class="tx-details-row">
                        <span class="tx-details-label">Network ID</span>
                        <span class="tx-details-value">{{ $document->blockchain_network_id ?? $naText }}</span>
                    </div>
                    <div class="tx-details-row">
                        <span class="tx-details-label">Status</span>
                        <div class="tx-details-value">
                            @if($isSuccess)
                                <span class="tx-status-badge tx-status-success" title="Status code {{ $statusCodeNormalized }}">
                                    <i class="fas fa-check-circle" aria-hidden="true"></i> Success
                                </span>
                            @elseif($isFailure)
                                <span class="tx-status-badge tx-status-failed" title="Status code {{ $statusCodeNormalized }}">
                                    <i class="fas fa-exclamation-circle" aria-hidden="true"></i> Failed
                                </span>
                            @else
                                <span class="tx-status-badge tx-status-unknown" title="Status code {{ $statusCode ?? $naText }}">
                                    <i class="fas fa-question-circle" aria-hidden="true"></i> Unknown
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="tx-details-card">
                <div class="tx-details-card-header">
                    <h3 class="admin-section-title"><i class="fas fa-clock"></i> Timestamps</h3>
                </div>
                <div class="tx-timestamp-grid">
                    <div class="tx-details-row">
                        <span class="tx-details-label">Submitted</span>
                        <span class="tx-details-value">{{ $submittedAt ? $submittedAt->format('M d, Y H:i:s') : $naText }}</span>
                    </div>
                    <div class="tx-details-row">
                        <span class="tx-details-label">Confirmed</span>
                        <span class="tx-details-value">{{ $confirmedAt ? $confirmedAt->format('M d, Y H:i:s') : $naText }}</span>
                    </div>
                    <div class="tx-details-row">
                        <span class="tx-details-label">Processing time</span>
                        <span class="tx-details-value">{{ $processingTimeDisplay }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('styles')
<style>
    .tx-details-breadcrumb {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.45rem;
        margin-bottom: 1rem;
        font-size: 0.875rem;
        color: var(--admin-text-muted);
    }

    .tx-details-breadcrumb a {
        color: var(--admin-primary);
        text-decoration: none;
    }

    .tx-details-breadcrumb a:hover {
        text-decoration: underline;
    }

    .tx-details-breadcrumb-current {
        font-weight: 600;
        color: var(--admin-text-primary);
    }

    .tx-details-callout {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        background: #eef6ff;
        border: 1px solid #c7dbff;
        border-radius: 12px;
        padding: 0.9rem 1rem;
        color: #16406e;
    }

    .tx-details-callout i {
        margin-top: 0.2rem;
    }

    .tx-details-callout p {
        margin: 0;
    }

    .tx-details-card {
        background: #fff;
        border: 1px solid #dbe4ee;
        border-radius: 14px;
        overflow: hidden;
    }

    .tx-details-card-header {
        padding: 1rem 1.1rem;
        border-bottom: 1px solid #edf1f7;
        background: #f8fafc;
    }

    .tx-details-read-only {
        background: #fcfdff;
    }

    .tx-details-read-only-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        margin-left: 0.5rem;
        font-size: 0.72rem;
        color: #58667a;
        padding: 0.2rem 0.5rem;
        border-radius: 999px;
        border: 1px solid #ced7e5;
        vertical-align: middle;
    }

    .tx-details-grid,
    .tx-timestamp-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0;
    }

    .tx-timestamp-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .tx-details-row {
        padding: 0.9rem 1.1rem;
        border-bottom: 1px solid #edf1f7;
        display: flex;
        flex-direction: column;
        gap: 0.45rem;
    }

    .tx-details-grid .tx-details-row:nth-last-child(-n+2),
    .tx-timestamp-grid .tx-details-row:last-child {
        border-bottom: 0;
    }

    .tx-details-label {
        font-size: 0.82rem;
        color: #5f7086;
        font-weight: 600;
        letter-spacing: 0.01em;
    }

    .tx-details-value {
        color: #0f213f;
        font-size: 0.95rem;
        font-weight: 600;
        overflow-wrap: anywhere;
    }

    .tx-details-mono {
        display: inline-block;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        font-size: 0.82rem;
        line-height: 1.45;
        background: #f5f8fc;
        border: 1px solid #deE6f0;
        border-radius: 8px;
        padding: 0.45rem 0.55rem;
        color: #1e3556;
        overflow-wrap: anywhere;
        word-break: break-all;
    }

    .tx-details-highlight {
        background: #edfdf5;
        border-color: #9adbb7;
    }

    .tx-details-copy-wrap {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        min-width: 0;
    }

    .tx-details-copy-wrap .tx-details-mono {
        flex: 1;
    }

    .tx-copy-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        min-width: 44px;
        min-height: 44px;
        padding: 0.3rem 0.65rem;
        border: 1px solid #cad4e1;
        border-radius: 8px;
        background: #fff;
        color: #324863;
        font-size: 0.78rem;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 0.2s ease, border-color 0.2s ease;
    }

    .tx-copy-btn:hover,
    .tx-copy-btn:focus-visible {
        background: #eff5ff;
        border-color: #8eb0e5;
        outline: none;
    }

    .tx-copy-btn.is-copied {
        background: #e9fbf1;
        border-color: #79c99d;
        color: #1c6a3d;
    }

    .tx-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.32rem 0.6rem;
        border-radius: 999px;
        font-size: 0.8rem;
        font-weight: 700;
    }

    .tx-status-success {
        background: #e8f8ef;
        color: #14683d;
        border: 1px solid #9fdab8;
    }

    .tx-status-failed {
        background: #feeeee;
        color: #9f2020;
        border: 1px solid #f0b6b6;
    }

    .tx-status-unknown {
        background: #f2f5fa;
        color: #4f5f77;
        border: 1px solid #d5deea;
    }

    @media (max-width: 1080px) {
        .tx-details-grid,
        .tx-timestamp-grid {
            grid-template-columns: 1fr;
        }

        .tx-details-grid .tx-details-row,
        .tx-timestamp-grid .tx-details-row {
            border-bottom: 1px solid #edf1f7;
        }

        .tx-details-grid .tx-details-row:last-child,
        .tx-timestamp-grid .tx-details-row:last-child {
            border-bottom: 0;
        }
    }

    @media (max-width: 640px) {
        .tx-details-copy-wrap {
            flex-direction: column;
            align-items: stretch;
        }

        .tx-copy-btn {
            width: 100%;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const copyButtons = document.querySelectorAll('.tx-copy-btn[data-copy-value]');

        copyButtons.forEach(function (button) {
            button.addEventListener('click', async function () {
                const value = button.getAttribute('data-copy-value');
                if (!value) {
                    return;
                }

                try {
                    await navigator.clipboard.writeText(value);
                    const labelSpan = button.querySelector('span');
                    const originalLabel = labelSpan ? labelSpan.textContent : '';

                    button.classList.add('is-copied');
                    if (labelSpan) {
                        labelSpan.textContent = 'Copied!';
                    }

                    window.setTimeout(function () {
                        button.classList.remove('is-copied');
                        if (labelSpan) {
                            labelSpan.textContent = originalLabel || 'Copy';
                        }
                    }, 1200);
                } catch (error) {
                    console.warn('Clipboard copy failed', error);
                }
            });
        });
    });
</script>
@endpush