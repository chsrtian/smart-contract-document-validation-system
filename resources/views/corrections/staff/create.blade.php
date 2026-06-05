@extends('layouts.staff')

@section('content')
<div class="py-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Page Header -->
        <div class="mb-6">
            <nav class="flex items-center text-sm text-gray-500 mb-2">
                <a href="{{ route('corrections.requests.index') }}" class="hover:text-blue-600">My Requests</a>
                <i class="fas fa-chevron-right mx-2 text-xs"></i>
                <span class="text-gray-900 font-medium">New Request</span>
            </nav>
            <h1 class="text-2xl font-bold text-gray-900">Submit Correction Request</h1>
            <p class="mt-1 text-sm text-gray-600">Request a correction for a blockchain-anchored document</p>
        </div>

        <!-- Error Messages -->
        @if(session('error'))
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-600 mr-3"></i>
                    <p class="text-sm text-red-700">{{ session('error') }}</p>
                </div>
            </div>
        @endif

        <!-- Info Banner -->
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
            <div class="flex items-start">
                <i class="fas fa-info-circle text-blue-600 mt-0.5"></i>
                <div class="ml-3">
                    <p class="text-sm text-gray-900">
                        <strong>Note:</strong> Correction requests require supervisor approval. 
                        The original blockchain record will never be modified - approved corrections create a new transaction.
                    </p>
                </div>
            </div>
        </div>

        @if($eligibleDocuments->isEmpty())
            <!-- No Eligible Documents -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-amber-100 rounded-full mb-4">
                    <i class="fas fa-exclamation-triangle text-amber-500 text-3xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">No Eligible Documents</h3>
                <p class="text-gray-500 mb-6">
                    There are no blockchain-anchored documents available for correction requests.
                    Documents must be anchored to the blockchain before corrections can be requested.
                </p>
                <a href="{{ route('staff.upload') }}" 
                   class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Documents
                </a>
            </div>
        @else
            <form action="{{ route('corrections.requests.store') }}" method="POST" enctype="multipart/form-data" id="correctionForm">
                @csrf

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    
                    <!-- Step 1: Select Document -->
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h2 class="text-lg font-semibold text-gray-900">
                            <span class="inline-flex items-center justify-center w-6 h-6 bg-blue-600 text-white text-sm font-bold rounded-full mr-2">1</span>
                            Select Document
                        </h2>
                    </div>
                    <div class="p-6 border-b border-gray-200">
                        <div class="mb-4">
                            <label for="scan_id" class="block text-sm font-medium text-gray-700 mb-1">
                                Document <span class="text-gray-900">*</span>
                            </label>
                            <select name="scan_id" id="scan_id" required
                                    class="text-gray-900 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm @error('scan_id') border-red-500 @enderror">
                                <option value="">-- Select a document --</option>
                                @foreach($eligibleDocuments as $scan)
                                    <option value="{{ $scan->id }}" 
                                            data-fields="{{ json_encode($scan->getCorrectableFields()) }}"
                                            data-type="{{ $scan->document_type_name ?? $scan->document_type }}"
                                            data-registry="{{ $scan->registry_number ?? 'N/A' }}"
                                            data-txhash="{{ $scan->blockchain_tx_hash ?? 'Not available' }}"
                                            {{ (old('scan_id', $selectedScanId) == $scan->id) ? 'selected' : '' }}>
                                        {{ $scan->document_type_name ?? ucwords(str_replace('_', ' ', $scan->document_type)) }} - {{ $scan->document_id }} (Reg: {{ $scan->registry_number ?? 'N/A' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('scan_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">Only blockchain-anchored documents are available for correction</p>
                        </div>

                        <!-- Selected Document Info -->
                        <div id="documentInfo" class="hidden mt-4 p-4 bg-gray-50 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-700 mb-3">Selected Document</h4>
                            <div id="documentDetails" class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Type:</span>
                                    <span id="docType" class="font-medium text-gray-900"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Registry No:</span>
                                    <span id="docRegistry" class="font-medium text-gray-900"></span>
                                </div>
                            </div>
                            
                            <!-- Transaction Hash Display -->
                            <div id="txHashSection" class="mt-4 pt-4 border-t border-gray-200">
                                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">
                                    <i class="fas fa-link mr-1"></i> Blockchain Transaction Hash
                                </label>
                                <div class="bg-white border border-gray-200 rounded-lg p-3">
                                    <code id="docTxHash" class="text-xs font-mono text-gray-700 break-all"></code>
                                </div>
                                <p class="mt-2 text-xs text-gray-500">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    This hash is required by the supervisor to verify your correction request.
                                </p>
                            </div>
                        </div>

                    <!-- Step 2: Field to Correct -->
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h2 class="text-lg font-semibold text-gray-900">
                            <span class="inline-flex items-center justify-center w-6 h-6 bg-blue-600 text-white text-sm font-bold rounded-full mr-2">2</span>
                            Select Field to Correct
                        </h2>
                    </div>
                    <div class="p-6 border-b border-gray-200">
                        <div class="mb-4">
                            <label for="field_name" class="block text-sm font-medium text-gray-700 mb-1">
                                Field <span class="text-red-500">*</span>
                            </label>
                            <select name="field_name" id="field_name" required disabled
                                    class="text-gray-900 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm @error('field_name') border-red-500 @enderror">
                                <option value="">-- Select document first --</option>
                            </select>
                            <!-- Hidden input to pass current value -->
                            <input type="hidden" name="current_value" id="current_value_input" value="">
                            @error('field_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Current Value Display -->
                        <div id="currentValueSection" class="hidden">
                            <label class="block text-sm font-medium text-gray-900 mb-1">Current Value</label>
                            <div id="currentValue" class="p-3 bg-red-50 border border-red-200 rounded-lg text-gray-900 font-medium"></div>
                        </div>
                    </div>

                    <!-- Step 3: Proposed Correction -->
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h2 class="text-lg font-semibold text-gray-900">
                            <span class="inline-flex items-center justify-center w-6 h-6 bg-blue-600 text-white text-sm font-bold rounded-full mr-2">3</span>
                            Enter Correction Details
                        </h2>
                    </div>
                    <div class="p-6">
                        <!-- Proposed Value -->
                        <div class="mb-4">
                            <label for="proposed_value" class="block text-sm font-medium text-gray-900 mb-1">
                                Corrected Value <span class="text-gray-900">*</span>
                            </label>
                            <input type="text" name="proposed_value" id="proposed_value" required
                                   value="{{ old('proposed_value') }}"
                                   placeholder="Enter the correct value"
                                   class="text-gray-900 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm @error('proposed_value') border-red-500 @enderror">
                            @error('proposed_value')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Reason -->
                        <div class="mb-4">
                            <label for="reason" class="block text-sm font-medium text-gray-700 mb-1">
                                Reason for Correction <span class="text-gray-900">*</span>
                            </label>
                            <textarea name="reason" id="reason" rows="4" required minlength="10"
                                      placeholder="Explain why this correction is needed (minimum 10 characters)"
                                      class="text-gray-900 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm @error('reason') border-red-500 @enderror">{{ old('reason') }}</textarea>
                            @error('reason')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Supporting Document -->
                        <div class="mb-6">
                            <label for="supporting_document" class="block text-sm font-medium text-gray-700 mb-1">
                                Supporting Document (Optional)
                            </label>
                            <input type="file" name="supporting_document" id="supporting_document"
                                   accept=".pdf,.jpg,.jpeg,.png"
                                   class="w-full rounded-lg border border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 @error('supporting_document') border-red-500 @enderror">
                            @error('supporting_document')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">Accepted formats: PDF, JPG, PNG. Max size: 5MB</p>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                            <a href="{{ route('corrections.requests.index') }}" 
                               class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors">
                                <i class="fas fa-times mr-2"></i>
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="inline-flex items-center px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                                <i class="fas fa-paper-plane mr-2"></i>
                                Submit Request
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        @endif

    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const scanSelect = document.getElementById('scan_id');
        const fieldSelect = document.getElementById('field_name');
        const documentInfo = document.getElementById('documentInfo');
        const currentValueSection = document.getElementById('currentValueSection');
        const currentValue = document.getElementById('currentValue');
        const currentValueInput = document.getElementById('current_value_input');

        // Handle document selection
        function handleDocumentChange() {
            const selectedOption = scanSelect.options[scanSelect.selectedIndex];
            
            if (scanSelect.value) {
                // Get fields data
                const fieldsData = selectedOption.dataset.fields;
                let fields = {};
                
                try {
                    fields = JSON.parse(fieldsData || '{}');
                } catch (e) {
                    console.error('Failed to parse fields data:', e);
                    fields = {};
                }
                
                // Enable and populate field select
                fieldSelect.disabled = false;
                fieldSelect.innerHTML = '<option value="">-- Select a field --</option>';
                
                for (const [fieldName, fieldInfo] of Object.entries(fields)) {
                    const option = document.createElement('option');
                    option.value = fieldName;
                    option.textContent = fieldInfo.label || fieldName.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    option.dataset.currentValue = fieldInfo.value || '';
                    fieldSelect.appendChild(option);
                }

                // Show document info with tx hash
                documentInfo.classList.remove('hidden');
                
                // Populate document details
                document.getElementById('docType').textContent = selectedOption.dataset.type || 'N/A';
                document.getElementById('docRegistry').textContent = selectedOption.dataset.registry || 'N/A';
                document.getElementById('docTxHash').textContent = selectedOption.dataset.txhash || 'Not available';
                
                // Reset field selection
                currentValueSection.classList.add('hidden');
                currentValueInput.value = '';
            } else {
                // No document selected - reset everything
                fieldSelect.disabled = true;
                fieldSelect.innerHTML = '<option value="">-- Select document first --</option>';
                documentInfo.classList.add('hidden');
                currentValueSection.classList.add('hidden');
                currentValueInput.value = '';
            }
        }

        // Handle field selection
        function handleFieldChange() {
            const selectedOption = fieldSelect.options[fieldSelect.selectedIndex];
            
            if (fieldSelect.value && selectedOption) {
                const value = selectedOption.dataset.currentValue || '';
                currentValue.textContent = value || '(empty)';
                currentValueInput.value = value; // Set hidden input for form submission
                currentValueSection.classList.remove('hidden');
            } else {
                currentValueSection.classList.add('hidden');
                currentValueInput.value = '';
            }
        }

        // Attach event listeners
        scanSelect.addEventListener('change', handleDocumentChange);
        fieldSelect.addEventListener('change', handleFieldChange);

        if (scanSelect.value) {
            handleDocumentChange();
        }
    });
</script>
@endpush
@endsection