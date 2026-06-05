@extends('layouts.staff')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/legal-corrections.css') }}">
@endpush

@section('content')
<div class="py-6">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Page Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">New Legal Correction Petition</h1>
                    <p class="mt-1 text-sm text-gray-600">RA 9048 / RA 10172 — File a petition for correction of civil registry entry</p>
                </div>
                <a href="{{ route('corrections.petitions.index') }}" class="lc-btn lc-btn-secondary lc-btn-sm">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
            </div>
        </div>

        <!-- Hidden URL for AJAX -->
        <input type="hidden" id="lc-document-fields-url" value="{{ route('corrections.petitions.document-fields') }}">

        <form id="lc-petition-form" method="POST" action="{{ route('corrections.petitions.store') }}" enctype="multipart/form-data">
            @csrf

            @if ($errors->any())
                <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 0.5rem; padding: 1rem; margin-bottom: 1.5rem;">
                    <h4 style="color: #991b1b; font-weight: 600; margin-bottom: 0.5rem;"><i class="fas fa-exclamation-triangle"></i> Please fix the following errors:</h4>
                    <ul style="list-style: disc; padding-left: 1.25rem; color: #dc2626; font-size: 0.8125rem;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Section 1: Document Selection -->
            <div class="lc-form-section">
                <div class="lc-form-section-title">
                    <i class="fas fa-file-alt"></i> Step 1: Select Document
                </div>
                <div class="lc-form-grid">
                    <div class="lc-form-group full-width">
                        <label for="scan_id">Civil Registry Document <span class="required">*</span></label>
                        <select name="scan_id" id="scan_id" required>
                            <option value="">-- Select a blockchain-confirmed document --</option>
                            @foreach($eligibleDocuments as $doc)
                                <option value="{{ $doc->id }}" {{ (old('scan_id', $selectedScanId) == $doc->id) ? 'selected' : '' }}>
                                    [{{ strtoupper(str_replace('_', ' ', $doc->document_type)) }}]
                                    {{ $doc->title ?? $doc->document_id }}
                                    — Registry #{{ $doc->registry_number ?? 'N/A' }}
                                </option>
                            @endforeach
                        </select>
                        <span class="help-text">Only blockchain-confirmed birth, death, and marriage certificates are eligible.</span>
                    </div>
                </div>
                <div id="lc-doc-info" style="display: none; align-items: center; gap: 0.5rem; margin-top: 0.75rem; padding: 0.5rem 0.75rem; background: #eff6ff; border-radius: 0.375rem; font-size: 0.8125rem; color: #1e40af;"></div>
            </div>

            <!-- Section 2: Petition Information -->
            <div class="lc-form-section">
                <div class="lc-form-section-title">
                    <i class="fas fa-gavel"></i> Step 2: Petition Details
                </div>
                <div class="lc-form-grid">
                    <div class="lc-form-group">
                        <label for="petition_type">Petition Type <span class="required">*</span></label>
                        <select name="petition_type" id="petition_type" required>
                            <option value="">-- Select type --</option>
                            @foreach($petitionTypes as $key => $label)
                                <option value="{{ $key }}" {{ old('petition_type') == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="help-text">The legal basis is determined automatically.</span>
                    </div>
                    <div class="lc-form-group">
                        <label for="petitioner_relationship">Petitioner Relationship <span class="required">*</span></label>
                        <select name="petitioner_relationship" id="petitioner_relationship" required>
                            <option value="">-- Select relationship --</option>
                            @foreach($relationshipTypes as $key => $label)
                                <option value="{{ $key }}" {{ old('petitioner_relationship') == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lc-form-group full-width">
                        <label for="petitioner_name">Petitioner Full Name <span class="required">*</span></label>
                        <input type="text" name="petitioner_name" id="petitioner_name" value="{{ old('petitioner_name') }}" required placeholder="Full legal name of petitioner" maxlength="255">
                    </div>
                    <div class="lc-form-group full-width">
                        <label for="petitioner_address">Petitioner Address</label>
                        <textarea name="petitioner_address" id="petitioner_address" rows="2" placeholder="Complete address of petitioner">{{ old('petitioner_address') }}</textarea>
                    </div>
                    <div class="lc-form-group full-width">
                        <label for="reason">Reason/Grounds for Petition <span class="required">*</span></label>
                        <textarea name="reason" id="reason" rows="3" required placeholder="Explain the grounds for this correction petition... (min. 10 characters)" minlength="10" maxlength="2000">{{ old('reason') }}</textarea>
                    </div>
                    <div class="lc-form-group full-width">
                        <label for="supporting_affidavit">Supporting Affidavit Text</label>
                        <textarea name="supporting_affidavit" id="supporting_affidavit" rows="3" placeholder="Optional: Paste or type the supporting affidavit text">{{ old('supporting_affidavit') }}</textarea>
                        <span class="help-text">You may also upload the affidavit as an attachment below.</span>
                    </div>
                </div>
            </div>

            <!-- Section 3: Field Corrections -->
            <div class="lc-form-section">
                <div class="lc-form-section-title">
                    <i class="fas fa-pen-fancy"></i> Step 3: Field Corrections
                </div>
                <p style="font-size: 0.8125rem; color: #6b7280; margin-bottom: 1rem;">
                    Specify each field that needs to be corrected. Select a document above to populate available fields.
                </p>

                <div id="lc-field-changes-list" class="lc-field-changes-list">
                    <!-- Dynamic field change rows added here by JS -->
                </div>

                <button type="button" id="lc-add-field-btn" class="lc-add-field-btn">
                    <i class="fas fa-plus-circle"></i> Add Field Correction
                </button>
            </div>

            <!-- Section 4: Attachments -->
            <div class="lc-form-section">
                <div class="lc-form-section-title">
                    <i class="fas fa-paperclip"></i> Step 4: Supporting Documents (Optional)
                </div>
                <p style="font-size: 0.8125rem; color: #6b7280; margin-bottom: 1rem;">
                    Upload supporting documents such as affidavits, IDs, baptismal certificates, school records, etc.
                </p>

                <div id="lc-attachments-list">
                    <!-- Dynamic attachment rows added here by JS -->
                </div>

                <button type="button" id="lc-add-attachment-btn" class="lc-add-field-btn" style="margin-top: 0.5rem;">
                    <i class="fas fa-plus-circle"></i> Add Attachment
                </button>
            </div>

            <!-- Submit -->
            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1rem;">
                <a href="{{ route('corrections.petitions.index') }}" class="lc-btn lc-btn-secondary">Cancel</a>
                <button type="submit" class="lc-btn lc-btn-primary">
                    <i class="fas fa-save"></i> Save as Draft
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/legal-corrections.js') }}"></script>
@endpush
