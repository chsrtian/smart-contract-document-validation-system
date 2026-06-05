<form id="other-search-form" class="space-y-6" data-document-type="other">
    @csrf
    <input type="hidden" name="document_type" value="other">
    @php
        $specificDocumentTypes = [
            'admission_of_paternity' => 'Admission of Paternity',
            'ausf' => 'Affidavit to Use the Surname of the Father (AUSF)',
            'legitimation' => 'Legitimation',
            'affidavit_of_reappearance' => 'Affidavit of Reappearance',
            'marriage_settlement' => 'Marriage Settlement',
            'parental_authorization_ai' => 'Parental Authorization / AI Ratification',
            'late_registration' => 'Late Registration',
            'supplemental_report' => 'Supplemental Report',
            'certificate_of_foundling' => 'Certificate of Foundling',
            'adoption_document' => 'Adoption Document',
            'judicial_correction_rule_108' => 'Judicial Correction of Entries (Rule 108)',
            'annulment_or_nullity' => 'Annulment / Declaration of Nullity',
            'recognition_of_foreign_divorce' => 'Recognition of Foreign Divorce',
            'marriage_license' => 'Marriage License',
            'certificate_legal_capacity_to_marry' => 'Certificate of Legal Capacity to Contract Marriage',
            'cenomar' => 'CENOMAR (Certificate of No Marriage)',
            'affidavit' => 'Affidavit',
            'court_document' => 'Court Document',
            'contract' => 'Contract',
            'other' => 'Other Legal Document',
        ];
    @endphp
    
    <div class="space-y-6">
        <!-- Search Form Section -->
        <div class="space-y-6">
            <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                <h4 class="font-semibold text-green-900 mb-2">Other Documents Search</h4>
                <p class="text-sm text-green-700">Search non-OCR civil registry records by document title.</p>
            </div>

            <!-- Document Type Selection -->
            <div class="bg-white p-6 rounded-lg border">
                <h5 class="font-semibold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-folder-open mr-2 text-green-600"></i>
                    Search Fields
                </h5>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Document Type *</label>
                        <input type="text"
                               value="Other Documents"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-700"
                               readonly>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Specific Document Type *</label>
                        <select name="specific_document_type" data-search="true" id="specific-document-type"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                required>
                            <option value="">Select Document Type</option>
                            @foreach($specificDocumentTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Document Title *</label>
                        <input type="text" name="document_title" data-search="true"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                               placeholder="Enter document title"
                               required>
                    </div>
                </div>
            </div>

            <!-- Search Actions -->
            <div class="flex space-x-4">
                <button type="button" id="clear-form-other" 
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-green-500">
                    Clear Form
                </button>
                <button type="submit" 
                        class="flex-1 bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                    Search Documents
                </button>
            </div>
        </div>

        <!-- Live Preview Section -->
        <div id="live-preview-other" class="bg-white rounded-lg border p-4">
            <div class="ss-no-results">
                <i class="fas fa-search"></i>
                <p>Start typing to see matching documents</p>
            </div>
        </div>
    </div>
</form>

{{-- Script removed: All search JS is now handled by /js/staff-search.js --}}