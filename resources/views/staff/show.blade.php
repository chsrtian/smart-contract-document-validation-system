@extends('layouts.staff')

@section('content')
<link href="{{ asset('css/show.css') }}" rel="stylesheet">

<div class="scan-container">
    
    <div class="nav-area">
        <a href="{{ route('staff.upload') }}" class="back-btn">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
            <span>Back to Documents</span>
        </a>
        <span class="doc-id-breadcrumb">/ {{ $scan->document_id }}</span>
    </div>
    
    <div class="custom-card header-card">
        <div class="header-top">
            <h1 class="doc-title">📄 {{ $scan->title ?? $scan->document_id }}</h1>
            
            <div class="status-badge status-{{ $scan->verification_status }}">
                <span class="status-dot"></span>
                {{ ucfirst($scan->verification_status) }}
            </div>
        </div>
        
        <div class="meta-grid">
            <div class="meta-item">
                <label>Document ID</label>
                <p>{{ $scan->document_id }}</p>
            </div>
            <div class="meta-item">
                <label>Type</label>
                <p>{{ str_replace('_', ' ', ucwords($scan->document_type)) }}</p>
            </div>
            <div class="meta-item">
                <label>Processed By</label>
                <p>{{ $scan->processedBy->name ?? 'System' }}</p>
            </div>
            <div class="meta-item">
                <label>Processed At</label>
                <p>{{ $scan->processed_at ? $scan->processed_at->format('M j, Y g:i A') : 'N/A' }}</p>
            </div>
        </div>
        
        <div class="metrics-section">
            <h3 class="section-label">Validation Metrics</h3>
            <div class="metrics-grid">
                <div class="metric-card theme-blue">
                    <div class="metric-circle">
                        {{ round($scan->validation_score ?? 0) }}%
                    </div>
                    <div class="metric-info">
                        <span class="metric-title">Validation Score</span>
                        <span class="metric-desc">Overall Accuracy</span>
                    </div>
                </div>
                
                <div class="metric-card theme-green">
                    <div class="metric-circle">
                        {{ round($scan->ocr_confidence ?? 0) }}%
                    </div>
                    <div class="metric-info">
                        <span class="metric-title">OCR Confidence</span>
                        <span class="metric-desc">Text Recognition</span>
                    </div>
                </div>
                
                <div class="metric-card theme-purple">
                    <div class="metric-circle">
                        {{ round($scan->manual_completion_score ?? 0) }}%
                    </div>
                    <div class="metric-info">
                        <span class="metric-title">Manual Completion</span>
                        <span class="metric-desc">Human Reviewed</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="content-split">
        
        <div class="left-col">
            <div class="custom-card full-height">
                <div class="card-header">
                    <h2>📋 Extracted Data</h2>
                    <span class="readonly-badge">Read-only</span>
                </div>
                
                @if(!empty($extractedFields))
                    <div class="data-list">
                        @foreach($extractedFields as $key => $value)
                            <div class="data-row">
                                <span class="data-label">{{ ucwords(str_replace('_', ' ', $key)) }}</span>
                                <span class="data-value">{{ $value ?: '—' }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="no-data">No extracted fields available</p>
                @endif

                @if($scan->notes)
                <div class="notes-section">
                    <h3>📝 Processing Notes</h3>
                    <div class="notes-box">
                        {{ $scan->notes }}
                    </div>
                </div>
                @endif
            </div>
        </div>
        
        <div class="right-col">
            <div class="custom-card image-card">
                <div class="card-header">
                    <h2>📸 Original Document</h2>
                </div>
                
                @if($scan->file_path)
                    @php
                        $previewUrl = route('staff.scans.image.preview', $scan->id);
                        $fallbackUrl = asset('images/mags.jpg');
                        $isPdfDocument = ($scan->file_mime_type === 'application/pdf')
                            || \Illuminate\Support\Str::endsWith(strtolower((string) $scan->file_path), '.pdf');
                        $fileExists = $scan->fileExists();
                    @endphp
                    <div class="image-wrapper">
                        @if($fileExists)
                            <img src="{{ $previewUrl }}"
                                 alt="Doc"
                                 onerror="this.onerror=null; this.src='{{ $fallbackUrl }}';">
                        @else
                            <img src="{{ $fallbackUrl }}" alt="Document placeholder">
                        @endif
                    </div>
                    <div class="download-area">
                        <a href="{{ route('staff.scans.image.download', $scan->id) }}" class="download-btn">
                            {{ $isPdfDocument ? 'Download Original PDF' : 'Download Original Image' }}
                        </a>
                    </div>
                @else
                    <div class="no-image">No image available</div>
                @endif
            </div>
            
            @if($scan->blockchain_status === 'confirmed')
            <div class="custom-card blockchain-card">
            <div class="card-header bg-header-green">
                <h2>⛓️ Blockchain Verification</h2>
            </div>
            <div class="blockchain-body">
                {{-- Original Transaction Hash --}}
                <div class="bc-row">
                    <label>ORIGINAL TRANSACTION HASH</label>
                    <div class="hash-box">{{ $scan->blockchain_tx_hash }}</div>
                </div>
                
                <div class="bc-grid">
                    <div class="bc-item">
                        <label>CONFIRMED AT</label>
                        <p>{{ $scan->blockchain_confirmed_at ? $scan->blockchain_confirmed_at->format('M j, Y g:i A') : 'N/A' }}</p>
                    </div>
                    @if($scan->blockchain_block_number)
                    <div class="bc-item">
                        <label>BLOCK #</label>
                        <p>#{{ $scan->blockchain_block_number }}</p>
                    </div>
                    @endif
                </div>

                {{-- Correction Transaction Hashes (if any corrections exist) --}}
                @php
                    $confirmedCorrections = $scan->correctionRecords()
                        ->where('blockchain_status', 'confirmed')
                        ->whereNotNull('correction_tx_hash')
                        ->orderBy('blockchain_confirmed_at', 'desc')
                        ->get();
                @endphp

                @if($confirmedCorrections->isNotEmpty())
                <div class="mt-6 pt-4 border-t-2 border-amber-300">
                    <h3 class="text-sm font-bold text-amber-700 mb-3 flex items-center">
                        <i class="fas fa-edit mr-2"></i>
                        CORRECTION TRANSACTION HASHES (REVISED DOCUMENT)
                    </h3>
                    
                    @foreach($confirmedCorrections as $correction)
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-3">
                        <div class="mb-2">
                            <label class="text-xs text-amber-600 font-semibold">CORRECTION #{{ $loop->iteration }} - {{ strtoupper(str_replace('_', ' ', $correction->corrected_field)) }}</label>
                        </div>
                        <div class="hash-box bg-white border-amber-300">{{ $correction->correction_tx_hash }}</div>
                        <div class="mt-2 flex justify-between text-xs text-gray-600">
                            <span>
                                <i class="fas fa-clock mr-1"></i>
                                {{ $correction->blockchain_confirmed_at ? $correction->blockchain_confirmed_at->format('M j, Y g:i A') : 'N/A' }}
                            </span>
                            @if($correction->blockchain_metadata['block_number'] ?? null)
                            <span>
                                <i class="fas fa-cube mr-1"></i>
                                Block #{{ $correction->blockchain_metadata['block_number'] }}
                            </span>
                            @endif
                        </div>
                        <div class="mt-2 text-xs">
                            <span class="text-gray-500">Changed:</span>
                            <span class="text-red-600 line-through">{{ Str::limit($correction->previous_value ?: '(empty)', 30) }}</span>
                            <i class="fas fa-arrow-right mx-1 text-gray-400"></i>
                            <span class="text-green-600 font-medium">{{ Str::limit($correction->new_value, 30) }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif

                {{-- Request Correction Button --}}
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <p class="text-xs text-gray-900 mb-3">
                        <i class="fas fa-info-circle mr-1"></i>
                        Need to correct data? Submit a correction request for supervisor approval.
                    </p>
                    <a href="{{ route('corrections.requests.create', ['scan_id' => $scan->id]) }}" 
                        class="inline-flex items-center px-4 py-2 bg-amber-500 hover:bg-amber-600 text-black text-sm font-semibold rounded-lg transition-all duration-200 shadow-md hover:shadow-lg">
                        <i class="fas fa-edit mr-2"></i>
                        Request Correction
                    </a>
                </div>
            </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection