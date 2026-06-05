{{-- filepath: c:\Users\PC\final_capstone\resources\views\corrections\supervisor\show.blade.php --}}
@extends('layouts.supervisor')

@section('content')
<div class="py-6">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Page Header -->
        <div class="mb-6">
            <nav class="flex items-center text-sm text-gray-500 mb-2">
                <a href="{{ route('corrections.approval.dashboard') }}" class="hover:text-purple-600">Dashboard</a>
                <i class="fas fa-chevron-right mx-2 text-xs"></i>
                <a href="{{ route('corrections.approval.history') }}" class="hover:text-purple-600">History</a>
                <i class="fas fa-chevron-right mx-2 text-xs"></i>
                <span class="text-gray-900 font-medium">Request Details</span>
            </nav>
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Correction Request Details</h1>
                
                @if($correctionRequest->status === 'approved')
                    <span class="inline-flex items-center px-3 py-1.5 bg-green-100 text-green-800 rounded-lg text-sm font-medium">
                        <i class="fas fa-check-circle mr-2"></i>
                        Approved
                    </span>
                @else
                    <span class="inline-flex items-center px-3 py-1.5 bg-red-100 text-red-800 rounded-lg text-sm font-medium">
                        <i class="fas fa-times-circle mr-2"></i>
                        Rejected
                    </span>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- Left Column: Document & Request Info -->
            <div class="space-y-6">
                
                <!-- Document Information -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">
                            <i class="fas fa-file-alt mr-2 text-purple-600"></i>
                            Document Information
                        </h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs text-gray-500 uppercase tracking-wider">Document Type</label>
                                <p class="text-sm font-medium text-gray-900">{{ $scan->document_type_name ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 uppercase tracking-wider">Document ID</label>
                                <p class="text-sm font-medium text-purple-600">{{ $scan->document_id ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 uppercase tracking-wider">Registry Number</label>
                                <p class="text-sm font-medium text-gray-900">{{ $scan->registry_number ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 uppercase tracking-wider">Processed At</label>
                                <p class="text-sm font-medium text-gray-900">{{ $scan->processed_at?->format('M d, Y') ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Correction Details -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 bg-amber-50 border-b border-amber-200">
                        <h3 class="text-lg font-semibold text-amber-800">
                            <i class="fas fa-edit mr-2"></i>
                            Correction Details
                        </h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="text-xs text-gray-500 uppercase tracking-wider">Field Corrected</label>
                            <p class="text-sm font-semibold text-gray-900">{{ $correctionRequest->field_display_name }}</p>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-red-50 rounded-lg p-4">
                                <label class="text-xs text-red-600 uppercase tracking-wider">Original Value</label>
                                <p class="text-sm font-medium text-red-700 mt-1">{{ $correctionRequest->current_value ?: '(empty)' }}</p>
                            </div>
                            <div class="bg-green-50 rounded-lg p-4">
                                <label class="text-xs text-green-600 uppercase tracking-wider">New Value</label>
                                <p class="text-sm font-medium text-green-700 mt-1">{{ $correctionRequest->proposed_value }}</p>
                            </div>
                        </div>
                        
                        <div>
                            <label class="text-xs text-gray-500 uppercase tracking-wider">Reason for Correction</label>
                            <p class="text-sm text-gray-700 bg-gray-50 rounded-lg p-3 mt-1">{{ $correctionRequest->reason }}</p>
                        </div>
                    </div>
                </div>

                <!-- Request & Review Info -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">
                            <i class="fas fa-users mr-2 text-blue-600"></i>
                            Request & Review Info
                        </h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs text-gray-500 uppercase tracking-wider">Requested By</label>
                                <p class="text-sm font-medium text-gray-900">{{ $correctionRequest->requester->name ?? 'Unknown' }}</p>
                                <p class="text-xs text-gray-500">{{ $correctionRequest->requested_at?->format('M d, Y g:i A') }}</p>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 uppercase tracking-wider">Reviewed By</label>
                                <p class="text-sm font-medium text-gray-900">{{ $correctionRequest->reviewer->name ?? 'Unknown' }}</p>
                                <p class="text-xs text-gray-500">{{ $correctionRequest->reviewed_at?->format('M d, Y g:i A') }}</p>
                            </div>
                        </div>
                        
                        @if($correctionRequest->status === 'rejected' && $correctionRequest->rejection_reason)
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <label class="text-xs text-red-600 uppercase tracking-wider">Rejection Reason</label>
                            <p class="text-sm text-red-700 mt-1">{{ $correctionRequest->rejection_reason }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Right Column: Blockchain Info -->
            <div class="space-y-6">
                
                <!-- Original Document Blockchain -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 bg-emerald-50 border-b border-emerald-200">
                        <h3 class="text-lg font-semibold text-emerald-800">
                            <i class="fas fa-cube mr-2"></i>
                            Original Document Hash
                        </h3>
                    </div>
                    <div class="p-6">
                        @if($scan->blockchain_tx_hash)
                            <div>
                                <label class="text-xs text-gray-500 uppercase tracking-wider">Transaction Hash</label>
                                <div class="mt-1 bg-gray-100 rounded-lg p-3 font-mono text-xs text-gray-800 break-all">
                                    {{ $scan->blockchain_tx_hash }}
                                </div>
                            </div>
                            <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <label class="text-xs text-gray-500">Block #</label>
                                    <p class="font-medium text-gray-900">#{{ $scan->blockchain_block_number ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500">Confirmed At</label>
                                    <p class="font-medium text-gray-900">{{ $scan->blockchain_confirmed_at?->format('M d, Y g:i A') ?? 'N/A' }}</p>
                                </div>
                            </div>
                        @else
                            <p class="text-gray-500 text-sm">No blockchain record for original document</p>
                        @endif
                    </div>
                </div>

                <!-- Correction Blockchain (if approved) -->
                @if($correctionRequest->status === 'approved' && $correctionRecord)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 bg-amber-50 border-b border-amber-200">
                        <h3 class="text-lg font-semibold text-amber-800">
                            <i class="fas fa-link mr-2"></i>
                            Correction Transaction Hash
                        </h3>
                    </div>
                    <div class="p-6">
                        @if($correctionRecord->correction_tx_hash)
                            <div>
                                <label class="text-xs text-gray-500 uppercase tracking-wider">Correction TX Hash</label>
                                <div class="mt-1 bg-amber-50 border border-amber-200 rounded-lg p-3 font-mono text-xs text-amber-800 break-all">
                                    {{ $correctionRecord->correction_tx_hash }}
                                </div>
                            </div>
                            <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <label class="text-xs text-gray-500">Block #</label>
                                    @php
                                        $correctionMetadata = is_array($correctionRecord->blockchain_metadata)
                                            ? $correctionRecord->blockchain_metadata
                                            : [];
                                        $correctionBlockNumber = $correctionMetadata['block_number'] ?? null;
                                        $correctionBlockVerified = $correctionMetadata['block_number_verified'] ?? null;
                                    @endphp
                                    <p class="font-medium text-gray-900">
                                        @if($correctionBlockNumber !== null)
                                            #{{ number_format((int) $correctionBlockNumber) }}
                                        @elseif($correctionBlockVerified === false)
                                            <span class="text-xs text-amber-700">Unavailable on current Ganache chain</span>
                                        @else
                                            N/A
                                        @endif
                                    </p>
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500">Confirmed At</label>
                                    <p class="font-medium text-gray-900">{{ $correctionRecord->blockchain_confirmed_at?->format('M d, Y g:i A') ?? 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="mt-4">
                                <span class="inline-flex items-center px-2.5 py-1 bg-emerald-100 text-emerald-800 rounded-full text-xs font-medium">
                                    <i class="fas fa-check-circle mr-1"></i>
                                    Anchored to Blockchain
                                </span>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-clock text-amber-500 text-2xl mb-2"></i>
                                <p class="text-gray-500 text-sm">Blockchain anchoring pending</p>
                            </div>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Extracted Data Preview -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">
                            <i class="fas fa-list-alt mr-2 text-gray-600"></i>
                            Extracted Data
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="max-h-64 overflow-y-auto">
                            @forelse($extractedFields as $key => $value)
                                @if(!in_array($key, ['raw_text', 'ocr_text']))
                                <div class="flex justify-between py-2 border-b border-gray-100 last:border-0">
                                    <span class="text-xs text-gray-500">{{ ucwords(str_replace('_', ' ', $key)) }}</span>
                                    <span class="text-sm font-medium text-gray-900 {{ $key === $correctionRequest->field_name ? 'text-amber-600 bg-amber-50 px-2 rounded' : '' }}">
                                        {{ $value ?: '(empty)' }}
                                        @if($key === $correctionRequest->field_name)
                                            <i class="fas fa-arrow-left ml-1 text-amber-500 text-xs"></i>
                                        @endif
                                    </span>
                                </div>
                                @endif
                            @empty
                                <p class="text-gray-500 text-sm text-center py-4">No extracted data available</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Back Button -->
        <div class="mt-6">
            <a href="{{ route('corrections.approval.history') }}" 
               class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to History
            </a>
        </div>
    </div>
</div>
@endsection