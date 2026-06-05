@extends('layouts.staff')

@section('content')
<style>

.scanner-section {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 1rem;
    margin-top: 0;
    font-family: sans-serif;
}

.scanner-card {
    background-color: #ffffff;
    border: 2px solid #4169E1;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 4px 6px rgba(65, 105, 225, 0.1);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 15px;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.icon-wrapper {
    background-color: #EEF2FF;
    padding: 12px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.icon-wrapper i {
    color: #4169E1;
    font-size: 1.5rem;
}

.text-content h1 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: bold;
    color: #000000;
}

.text-content p {
    margin: 4px 0 0 0;
    color: #000000ff;
    font-size: 0.95rem;
}

.header-right {
    display: flex;
    align-items: center;
    gap: 12px;
}

.status-badge {
    background-color: #FEF3C7;
    color: #92400E;
    padding: 4px 12px;
    border-radius: 9999px;
    font-size: 0.875rem;
    font-weight: 500;
    display: flex;
    align-items: center;
}

.btn-refresh {
    background-color: #4169E1;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    display: flex;
    align-items: center;
    transition: background-color 0.2s;
}

.btn-refresh:hover {
    background-color: #2563EB;
}

.mr-2 { margin-right: 8px; }

select, input[type="text"], input[type="number"] {
    background-color: #ffffff !important;
    color: #111827 !important;
}

option {
    background-color: #ffffff;
    color: #000000;
}

.field-box {
    width: 100%;
    padding: 8px 12px;
    background-color: white;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    color: #111827;
    transition: all 0.2s ease-in-out;
}

.field-box:focus {
    outline: none;
    ring: 2px;
    ring-color: #3b82f6;
    border-color: #3b82f6;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}

.field-box::placeholder {
    color: #6b7280;
    font-weight: 400;
}

.field-group {
    margin-bottom: 24px;
    padding: 16px;
    background-color: #ffffff;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}

.field-group[style*="background-color"],
.field-group[style*="border-color"] {
    background-color: #ffffff !important;
    border-color: #e2e8f0 !important;
}

.field-row {
    display: grid;
    gap: 12px;
    margin-bottom: 12px;
}

.field-row.three-cols {
    grid-template-columns: 1fr 1fr 1fr;
}

.field-row.two-cols {
    grid-template-columns: 1fr 1fr;
}

.field-row.single-col {
    grid-template-columns: 1fr;
}

.field-label {
    display: block;
    font-size: 0.78rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 4px;
}

.section-header {
    font-weight: 700;
    color: #111827;
    border-bottom: 1px solid #e5e7eb;
    padding-bottom: 10px;
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    gap: 0.35rem;
}

.section-header i {
    color: #64748b !important;
}

.field-group h5 {
    color: #111827 !important;
    font-weight: 700;
    letter-spacing: 0.01em;
}

.field-group h5 > span:first-child {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.75rem;
    height: 1.75rem;
    border-radius: 9999px;
    margin-right: 0.6rem;
    background-color: #0f172a !important;
    color: #ffffff !important;
    font-size: 0.75rem;
    font-weight: 700;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.25);
}

.field-box.ocr-filled {
    background-color: #ffffff;
    border-color: #d1d5db;
}

.field-box.ocr-edited {
    background-color: #ffffff;
    border-color: #d1d5db;
}

.field-box.ocr-error {
    background-color: #fef2f2;
    border-color: #ef4444;
    box-shadow: 0 0 0 1px rgba(239, 68, 68, 0.12);
}

.ocr-action-btn {
    padding: 0.5rem 1rem;
    color: white;
    border-radius: 0.375rem;
    font-weight: 500;
    font-size: 0.875rem;
    transition: all 0.2s;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

.ocr-action-btn:hover {
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    transform: scale(1.05);
}

/* ── Preview container: scales image to fit, no internal scroll ── */
#preview-container {
    position: relative;
    width: 100%;
    box-sizing: border-box;
    min-width: 0;
    height: clamp(24rem, 64vh, 46rem);
    overflow: hidden;
    display: block;
    border: 2px dashed #d1d5db;
    border-radius: 0.5rem;
    background: #f9fafb;
}

/* Empty state: centered placeholder */
#preview-container #empty-state {
    position: absolute;
    inset: 0;
    box-sizing: border-box;
    display: grid;
    place-content: center;
    justify-items: center;
    padding: 1rem;
    text-align: center;
    gap: 0.5rem;
}

#preview-container #empty-state h3 {
    color: #111827;
    font-weight: 600;
    line-height: 1.3;
}

#preview-container #empty-state p {
    color: #374151;
    max-width: 28rem;
    line-height: 1.5;
}

/* Preview content: holds the image + filename, no scroll */
#preview-content {
    width: 100%;
    height: 100%;
    min-height: 0;
    overflow: hidden;
    box-sizing: border-box;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 12px;
}

#preview-content.hidden {
    display: none !important;
}

/* Image: scale-to-fit, never causes overflow or scroll */
#preview-content img {
    display: block !important;
    max-width: 100% !important;
    max-height: 100% !important;
    width: 100% !important;
    height: 100% !important;
    object-fit: contain !important;
    object-position: center center;
    margin: auto;
    border-radius: 6px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.12);
}

#preview-content > * {
    max-width: 100%;
    max-height: 100%;
}

/* ── Scanned-files row: flex-wrap so it never overflows ── */
.file-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    margin-bottom: 0.5rem;
}
.file-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex: 1 1 0%;
    min-width: 0;
}
.file-name {
    font-size: 0.875rem;
    font-weight: 500;
    color: #111827;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.file-size {
    font-size: 0.75rem;
    color: #6b7280;
    white-space: nowrap;
}
.file-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem;
    flex-shrink: 0;
}

#extract-document-data {
    background-color: #16a34a !important;
    color: #ffffff !important;
    border: 2px solid #15803d !important;
    font-weight: 700 !important;
    font-size: 0.875rem !important;
    padding: 0.75rem 1.25rem !important;
    border-radius: 0.5rem !important;
    box-shadow: 0 4px 6px rgba(21, 128, 61, 0.4), 0 1px 3px rgba(0,0,0,0.1) !important;
    transition: all 0.2s ease-in-out !important;
    text-shadow: 0 1px 2px rgba(0,0,0,0.15) !important;
    letter-spacing: 0.01em !important;
}

#extract-document-data:hover:not(:disabled) {
    background-color: #15803d !important;
    box-shadow: 0 6px 12px rgba(21, 128, 61, 0.5), 0 2px 4px rgba(0,0,0,0.15) !important;
    transform: translateY(-1px) scale(1.02) !important;
}

#extract-document-data:active:not(:disabled) {
    transform: translateY(0) scale(0.98) !important;
    box-shadow: 0 2px 4px rgba(21, 128, 61, 0.3) !important;
}

#extract-document-data:disabled {
    background-color: #9ca3af !important;
    border-color: #6b7280 !important;
    color: #e5e7eb !important;
    box-shadow: none !important;
    cursor: not-allowed !important;
    transform: none !important;
    opacity: 0.65 !important;
}


#extraction-control {
    background-color: #f0fdf4 !important;
    border: 2px solid #16a34a !important;
    border-radius: 0.5rem !important;
    box-shadow: 0 2px 6px rgba(21, 128, 61, 0.15) !important;
}


/* ── Split-view top panels ── */
.scan-split-row {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
    align-items: start;
}

@media (min-width: 1024px) {
    .scan-split-row {
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        align-items: stretch;   
    }
}

/* Controls panel: normal scroll */
.scan-controls-panel {
    height: 100%;
    min-width: 0;
}

.scan-preview-panel {
    min-width: 0;
}

/* Preview panel: sticky on desktop so it stays visible while user fills fields */
@media (min-width: 1024px) {
    .scan-preview-panel {
        position: sticky;
        top: 5rem;           /* clears fixed navbar */
        align-self: start;
    }
}
</style>


<div class="staff-page-shell min-h-screen bg-gradient-to-b from-gray-100 to-white pb-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="pb-8 relative z-10">

            {{-- ── Page Header ── --}}
            <div class="mb-12 border-b border-gray-200 pb-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="staff-page-title">Document Scanner</h1>
                        <p class="staff-page-subtitle">Scan and digitize documents with OCR and blockchain validation</p>
                    </div>

                    <div class="flex items-center space-x-3">
                        <div class="inline-flex items-center space-x-2 px-4 py-2 bg-white rounded-full border border-gray-300" style="box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                            <div class="relative">
                                <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                                <div class="absolute inset-0 w-3 h-3 rounded-full bg-yellow-500 animate-ping opacity-75"></div>
                            </div>
                            <span id="scanner-status" class="text-xs font-bold uppercase tracking-wide" style="color: #b45309;">
                                Detecting...
                            </span>
                        </div>

                        <button id="refresh-scanners" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                            <i class="fas fa-sync-alt mr-2"></i> Refresh
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ════════════════════════════════════════════
             TOP SECTION — 2-column split view
             LEFT : Scanner Controls
             RIGHT: Document Preview
        ════════════════════════════════════════════ --}}
        <div class="scan-split-row mb-8">

            {{-- ── LEFT: Scanner Controls ── --}}
            <div class="scan-controls-panel">
                <div class="bg-white rounded-lg shadow-sm border border-gray-600 p-6 h-full">
                    <h2 class="text-lg font-semibold text-gray-900 mb-6 flex items-center">
                        <i class="fas fa-cogs text-gray-600 mr-2"></i>
                        Scanner Controls
                    </h2>

                    <!-- Scanner Selection -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Scanner</label>
                        <select id="scanner-select" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Detecting scanners...</option>
                        </select>
                    </div>

                    <!-- Scanner Settings -->
                    <div class="space-y-5 mb-6">
                        <div class="rounded-xl border border-black px-4 py-4 bg-white">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="rounded-lg border border-gray-300 bg-gray-50 px-4 py-3">
                                <p class="text-xs font-medium text-gray-500">Quality (DPI)</p>
                                <p class="text-sm font-semibold text-gray-900">150 DPI</p>
                            </div>
                            <div class="rounded-lg border border-gray-300 bg-gray-50 px-4 py-3">
                                <p class="text-xs font-medium text-gray-500">Color Mode</p>
                                <p class="text-sm font-semibold text-gray-900">Color</p>
                            </div>
                            <div class="rounded-lg border border-gray-300 bg-gray-50 px-4 py-3">
                                <p class="text-xs font-medium text-gray-500">Output Format</p>
                                <p class="text-sm font-semibold text-gray-900">PDF</p>
                            </div>
                            </div>
                        </div>
                        <input type="hidden" id="scan-dpi" value="150">
                        <input type="hidden" id="color-mode" value="color">
                        <input type="hidden" id="output-format" value="pdf">

                        <!-- Advanced Settings Toggle -->
                        <button id="toggle-advanced" class="hidden text-blue-600 hover:text-blue-700 text-sm font-medium">
                            <i class="fas fa-chevron-down mr-1"></i>Advanced Settings
                        </button>

                        <div id="advanced-settings" class="hidden space-y-4 pt-4 border-t border-gray-200">
                            <div class="flex items-center justify-between">
                                <label class="text-sm font-medium text-gray-700">OCR (Text Recognition)</label>
                                <input type="checkbox" id="ocr-enabled" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            </div>
                            <div class="flex items-center justify-between">
                                <label class="text-sm font-medium text-gray-700">Auto Crop</label>
                                <input type="checkbox" id="auto-crop" checked class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Brightness</label>
                                <input type="range" id="brightness" min="-50" max="50" value="0" class="w-full">
                                <div class="flex justify-between text-xs text-gray-500 mt-1">
                                    <span>-50</span><span>0</span><span>+50</span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Contrast</label>
                                <input type="range" id="contrast" min="-50" max="50" value="0" class="w-full">
                                <div class="flex justify-between text-xs text-gray-500 mt-1">
                                    <span>-50</span><span>0</span><span>+50</span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Image Quality</label>
                                <select id="image-quality" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm">
                                    <option value="auto">Auto</option>
                                    <option value="high">High Quality</option>
                                    <option value="medium" selected>Medium Quality</option>
                                    <option value="low">Fast Processing</option>
                                </select>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="space-y-3">
                            <button id="scan-preview" class="w-full px-4 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors duration-200 font-medium">
                                <i class="fas fa-eye mr-2"></i>Preview Scan
                            </button>
                            <button id="start-scan" class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 font-medium" disabled>
                                <i class="fas fa-play mr-2"></i>Start Scan
                            </button>
                        </div>

                        <!-- Fallback Options -->
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <h3 class="text-sm font-medium text-gray-700 mb-3">Fallback Options</h3>
                            <div class="space-y-2">
                                <label class="flex items-center justify-center w-full px-4 py-3 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition-colors duration-200">
                                    <input type="file" id="camera-capture" accept="image/*" capture="environment" class="hidden">
                                    <i class="fas fa-camera text-gray-400 mr-2"></i>
                                    <span class="text-sm font-medium text-gray-600">Camera Capture</span>
                                </label>
                                <label class="flex items-center justify-center w-full px-4 py-3 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition-colors duration-200">
                                    <input type="file" id="file-upload" accept="image/*,application/pdf" multiple class="hidden">
                                    <i class="fas fa-upload text-gray-400 mr-2"></i>
                                    <span class="text-sm font-medium text-gray-600">Upload Files</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- ── END LEFT ── --}}

            {{-- ── RIGHT: Document Preview ── --}}
            <div class="scan-preview-panel">
                <div class="bg-white rounded-lg shadow-sm border border-gray-600 p-6 h-full">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                            <i class="fas fa-images text-gray-600 mr-2"></i>
                            Document Preview
                        </h2>
                        <div class="flex flex-wrap items-center gap-3">
                            <!-- Apply OCR Button (hidden until file present) -->
                            <button id="apply-ocr" class="hidden inline-flex items-center whitespace-nowrap px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors duration-200 font-medium">
                                <i class="fas fa-text-width mr-2"></i>Apply OCR
                            </button>
                            <button id="save-document" class="inline-flex items-center whitespace-nowrap mr-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 font-medium" disabled>
                                <i class="fas fa-save mr-2"></i>Save
                            </button>
                            <button id="clear-all" class="inline-flex items-center whitespace-nowrap ml-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors duration-200 font-medium">
                                <i class="fas fa-trash mr-2"></i>Clear All
                            </button>
                        </div>
                    </div>

                    <!-- Preview Area — fixed-height scrollable viewport -->
                    <div id="preview-container">
                        <div id="empty-state">
                            <i class="fas fa-file-image text-6xl text-gray-300 mb-4"></i>
                            <h3 class="text-lg font-medium text-gray-500 mb-2">No documents scanned</h3>
                            <p class="text-sm text-gray-400">Start scanning or upload files to see preview</p>
                        </div>
                        <div id="preview-content" class="hidden"></div>
                    </div>

                    <!-- File List -->
                    <div id="file-list" class="hidden mt-6">
                        <h3 class="text-md font-medium text-gray-900 mb-4">Scanned Files</h3>
                        <div id="files-container" class="space-y-2"></div>
                    </div>

                    <!-- Document Details Form -->
                    <div id="document-details" class="hidden mt-6 pt-6 border-t border-gray-200">
                        <h3 class="text-md font-medium text-gray-900 mb-4">Document Information</h3>
                        <form id="document-form" class="space-y-4">
                            @csrf
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Document Title</label>
                                    <input type="text" id="document-title" name="title"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                           required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Document Type</label>
                                    <select id="document-type" name="type"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            onchange="documentScanner.handleDocumentTypeChange(this.value)">
                                        <option value="birth_certificate">Birth Certificate (PSA/NSO)</option>
                                        <option value="death_certificate">Death Certificate (PSA/NSO)</option>
                                        <option value="marriage_certificate">Marriage Certificate (PSA/NSO)</option>
                                        <option value="admission_of_paternity">Admission of Paternity</option>
                                        <option value="ausf">Affidavit to Use the Surname of the Father (AUSF)</option>
                                        <option value="legitimation">Legitimation</option>
                                        <option value="affidavit_of_reappearance">Affidavit of Reappearance</option>
                                        <option value="marriage_settlement">Marriage Settlement</option>
                                        <option value="parental_authorization_ai">Parental Authorization / AI Ratification</option>
                                        <option value="late_registration">Late Registration</option>
                                        <option value="supplemental_report">Supplemental Report</option>
                                        <option value="certificate_of_foundling">Certificate of Foundling</option>
                                        <option value="adoption_document">Adoption Document</option>
                                        <option value="judicial_correction_rule_108">Judicial Correction of Entries (Rule 108)</option>
                                        <option value="annulment_or_nullity">Annulment / Declaration of Nullity</option>
                                        <option value="recognition_of_foreign_divorce">Recognition of Foreign Divorce</option>
                                        <option value="marriage_license">Marriage License</option>
                                        <option value="certificate_legal_capacity_to_marry">Certificate of Legal Capacity to Contract Marriage</option>
                                        <option value="cenomar">CENOMAR (Certificate of No Marriage)</option>
                                        <option value="affidavit">Affidavit</option>
                                        <option value="court_document">Court Document</option>
                                        <option value="contract">Contract</option>
                                        <option value="other">Other Legal Document</option>
                                    </select>

                                    <!-- Document Type Info Panel -->
                                    <div id="document-type-info" class="mt-2 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                        <div class="flex items-start space-x-2">
                                            <i class="fas fa-info-circle text-blue-600 mt-0.5"></i>
                                            <div>
                                                <p id="document-type-description" class="text-sm text-blue-800 font-medium">
                                                    Birth Certificate - Extracts child's information, parents' details, and registry data
                                                </p>
                                                <p id="document-type-fields" class="text-xs text-blue-600 mt-1">
                                                    Fields: Name, Sex, Birth Date, Place of Birth, Parents' Names, Registry Info
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Extraction Control -->
                                    <div id="extraction-control" class="hidden mt-4 p-4 rounded-lg"
                                        style="background-color: #f0fdf4; border: 2px solid #16a34a; border-radius: 0.5rem; box-shadow: 0 2px 6px rgba(21,128,61,0.15);">

                                        <div class="flex flex-col gap-3">
                                            <!-- Header Row -->
                                            <div class="flex items-start space-x-3">
                                                <i class="fas fa-text-width text-green-600 mt-1 text-lg flex-shrink-0"></i>
                                                <div>
                                                    <h4 class="text-sm font-semibold text-green-800">Ready for Data Extraction</h4>
                                                    <p class="text-xs text-green-700 mt-1">
                                                        Click the button to extract data from your uploaded document based on the selected document type.
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Button Row — full width on narrow containers -->
                                            <button id="extract-document-data"
                                                    class="w-full flex items-center justify-center gap-2"
                                                    style="background-color: #16a34a;
                                                        color: #ffffff;
                                                        border: 2px solid #15803d;
                                                        font-weight: 700;
                                                        font-size: 0.875rem;
                                                        padding: 0.65rem 1rem;
                                                        border-radius: 0.5rem;
                                                        box-shadow: 0 4px 6px rgba(21,128,61,0.4);
                                                        cursor: pointer;
                                                        letter-spacing: 0.01em;"
                                                    disabled>
                                                <i class="fas fa-magic"></i>
                                                <span>Extract Data from Document</span>
                                            </button>
                                        </div>

                                        <!-- Extraction Status -->
                                        <div id="extraction-status" class="hidden mt-3 p-2 rounded-md">
                                            <div class="flex items-center space-x-2">
                                                <i id="extraction-status-icon" class="fas fa-spinner fa-spin text-blue-600"></i>
                                                <span id="extraction-status-text" class="text-sm font-medium">Preparing extraction...</span>
                                            </div>
                                        </div>
                                    </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            {{-- ── END RIGHT ── --}}

        </div>
        {{-- ── END TOP SPLIT ROW ── --}}

        <div id="ocr-results-panel" class="hidden bg-white rounded-lg shadow-lg border border-gray-200 p-6 mt-8 w-full">
            <!-- Header -->
            <div class="flex flex-col items-start justify-start mb-6 gap-4 w-full">
                <h3 class="text-xl font-bold text-gray-900 flex items-center">
                    <i class="fas fa-text-width text-blue-600 mr-3"></i>
                    OCR Extraction Results
                </h3>
                <div class="flex items-center space-x-3">
                    <span id="ocr-status" class="px-4 py-2 rounded-full text-sm font-medium bg-green-100 text-green-800">
                        <i class="fas fa-check-circle mr-1"></i>Processing Complete
                    </span>
                </div>
            </div>

            <!-- OCR Metrics Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8 w-full">
                <div class="bg-gradient-to-br from-green-50 to-green-100 p-6 rounded-xl text-center border border-green-200">
                    <div class="text-sm font-semibold text-green-700 mb-2">Accuracy Rate</div>
                    <div id="confidence-score" class="text-3xl font-bold text-green-600">0%</div>
                </div>
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-6 rounded-xl text-center border border-blue-200">
                    <div class="text-sm font-semibold text-blue-700 mb-2">Words Extracted</div>
                    <div id="words-extracted" class="text-3xl font-bold text-blue-600">0</div>
                </div>
                <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-6 rounded-xl text-center border border-purple-200">
                    <div class="text-sm font-semibold text-purple-700 mb-2">Fields Detected</div>
                    <div id="fields-detected" class="text-3xl font-bold text-purple-600">0</div>
                </div>
            </div>

            <!-- OCR Actions Section -->
            <div class="mb-8 pb-6 border-b border-gray-200 w-full">
                <h4 class="text-lg font-bold text-gray-800 flex items-center mb-4">
                    <i class="fas fa-tools text-blue-600 mr-3"></i>
                    OCR Actions
                </h4>
                <p class="text-sm text-gray-600 mb-6">Manage your extracted OCR data with the following actions:</p>

                <div class="flex flex-wrap justify-center gap-4 w-full max-w-6xl mx-auto">
                    <button type="button" id="copy-text-btn"
                            class="flex flex-col items-center justify-center px-6 py-4 bg-blue-600 text-white rounded-xl hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 font-medium text-sm shadow-lg transition-all duration-300 transform hover:scale-105 flex-1 min-w-[140px] max-w-[180px] min-h-[100px]">
                        <i class="fas fa-copy text-xl mb-2"></i>
                        <span class="text-xs text-center font-semibold">Copy Text</span>
                    </button>

                    <button type="button" id="export-data-btn"
                            class="flex flex-col items-center justify-center px-6 py-4 bg-red-600 text-white rounded-xl hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 font-medium text-sm shadow-lg transition-all duration-300 transform hover:scale-105 flex-1 min-w-[140px] max-w-[180px] min-h-[100px]">
                        <i class="fas fa-download text-xl mb-2"></i>
                        <span class="text-xs text-center font-semibold">Export Data</span>
                    </button>

                    <button type="button" id="reprocess-btn"
                            class="flex flex-col items-center justify-center px-6 py-4 bg-blue-600 text-white rounded-xl hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 font-medium text-sm shadow-lg transition-all duration-300 transform hover:scale-105 flex-1 min-w-[140px] max-w-[180px] min-h-[100px]">
                        <i class="fas fa-redo text-xl mb-2"></i>
                        <span class="text-xs text-center font-semibold">Reprocess</span>
                    </button>

                    <button type="button" id="save-corrections-btn"
                            class="flex flex-col items-center justify-center px-6 py-4 bg-red-600 text-white rounded-xl hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 font-medium text-sm shadow-lg transition-all duration-300 transform hover:scale-105 flex-1 min-w-[140px] max-w-[180px] min-h-[100px]">
                        <i class="fas fa-check text-xl mb-2"></i>
                        <span class="text-xs text-center font-semibold">Save Corrections</span>
                    </button>

                    <button type="button" id="reset-fields-btn"
                            class="flex flex-col items-center justify-center px-6 py-4 bg-blue-600 text-white rounded-xl hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 font-medium text-sm shadow-lg transition-all duration-300 transform hover:scale-105 flex-1 min-w-[140px] max-w-[180px] min-h-[100px]">
                        <i class="fas fa-undo text-xl mb-2"></i>
                        <span class="text-xs text-center font-semibold">Reset Fields</span>
                    </button>

                    <button type="button" id="toggle-edit-mode-btn"
                            class="flex flex-col items-center justify-center px-6 py-4 bg-red-600 text-white rounded-xl hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 font-medium text-sm shadow-lg transition-all duration-300 transform hover:scale-105 flex-1 min-w-[140px] max-w-[180px] min-h-[100px]">
                        <i class="fas fa-edit text-xl mb-2"></i>
                        <span class="text-xs text-center font-semibold">Edit Mode</span>
                    </button>
                </div>
            </div>

            <!-- Birth Certificate Fields -->
            <div id="birth-certificate-layout" class="grid grid-cols-1 lg:grid-cols-2 gap-8 w-full">

                <!-- Left Column: Child's Information & Birth Details -->
                <div class="space-y-6">
                    <h4 class="section-header">
                        <i class="fas fa-child text-blue-500 mr-2"></i>Child's Information
                    </h4>

                    <div class="field-group" style="background-color: #dbeafe; border-color: #3b82f6;">
                        <h5 class="font-medium text-blue-800 mb-3 flex items-center">
                            <span class="bg-blue-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm mr-2">1</span>
                            NAME
                        </h5>
                        <div class="field-row three-cols">
                            <div>
                                <label class="field-label">First</label>
                                <input type="text" id="name-first" class="field-box" placeholder="First name">
                            </div>
                            <div>
                                <label class="field-label">Middle</label>
                                <input type="text" id="name-middle" class="field-box" placeholder="Middle name">
                            </div>
                            <div>
                                <label class="field-label">Last</label>
                                <input type="text" id="name-last" class="field-box" placeholder="Last name">
                            </div>
                        </div>
                    </div>

                    <div class="field-group" style="background-color: #dcfce7; border-color: #10b981;">
                        <h5 class="font-medium text-black-800 mb-3 flex items-center">
                            <span class="bg-green-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm mr-2">2</span>
                            <span class="text-black">SEX</span>
                        </h5>
                        <div class="field-row single-col">
                            <div>
                                <div class="flex space-x-4 mb-3">
                                    <label class="flex items-center">
                                        <input type="radio" id="sex-male" name="detected-sex" value="Male"
                                               class="h-4 w-4 text-green-600 border-gray-300 focus:ring-green-500">
                                        <span class="ml-2 text-sm font-medium text-black">Male</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="radio" id="sex-female" name="detected-sex" value="Female"
                                               class="h-4 w-4 text-green-600 border-gray-300 focus:ring-green-500">
                                        <span class="ml-2 text-sm font-medium text-black">Female</span>
                                    </label>
                                </div>
                                <input type="text" id="sex-detected" class="field-box" placeholder="Detected sex">
                            </div>
                        </div>
                    </div>

                    <div class="field-group" style="background-color: #f3e8ff; border-color: #8b5cf6;">
                        <h5 class="font-medium text-black mb-3 flex items-center">
                            <span class="bg-purple-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm mr-2">3</span>
                            <span class="text-black">DATE OF BIRTH</span>
                        </h5>
                        <div class="field-row three-cols">
                            <div>
                                <label class="field-label">Day</label>
                                <input type="text" id="birth-date-day" class="field-box" placeholder="DD" maxlength="2">
                            </div>
                            <div>
                                <label class="field-label">Month</label>
                                <input type="text" id="birth-date-month" class="field-box" placeholder="Month">
                            </div>
                            <div>
                                <label class="field-label">Year</label>
                                <input type="text" id="birth-date-year" class="field-box" placeholder="YYYY" maxlength="4">
                            </div>
                        </div>
                    </div>

                    <div class="field-group" style="background-color: #fed7aa; border-color: #f97316;">
                        <h5 class="font-medium text-black mb-3 flex items-center">
                            <span class="bg-orange-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm mr-2">4</span>
                            <span class="text-black">PLACE OF BIRTH</span>
                        </h5>
                        <div class="space-y-3">
                            <div class="field-row single-col">
                                <div>
                                    <label class="field-label">Name of Hospital/Clinic/Institution/House No., Street, Barangay</label>
                                    <input type="text" id="birth-place-institution" class="field-box" placeholder="Institution or address">
                                </div>
                            </div>
                            <div class="field-row two-cols">
                                <div>
                                    <label class="field-label">City/Municipality</label>
                                    <input type="text" id="birth-place-city" class="field-box" placeholder="City/Municipality">
                                </div>
                                <div>
                                    <label class="field-label">Province</label>
                                    <input type="text" id="birth-place-province" class="field-box" placeholder="Province">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Parents' Information & Additional Data -->
                <div class="space-y-6">
                    <h4 class="section-header">
                        <i class="fas fa-users text-green-500 mr-2"></i>Parents' Information
                    </h4>

                    <div class="field-group" style="background-color: #fce7f3; border-color: #ec4899;">
                        <h5 class="font-medium text-black mb-3 flex items-center">
                            <span class="bg-pink-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm mr-2">5</span>
                            MOTHER'S MAIDEN NAME
                        </h5>
                        <div class="field-row three-cols">
                            <div>
                                <label class="field-label">First Name</label>
                                <input type="text" id="mother-first-name" class="field-box" placeholder="Mother's first name">
                            </div>
                            <div>
                                <label class="field-label">Middle Name</label>
                                <input type="text" id="mother-middle-name" class="field-box" placeholder="Mother's middle name">
                            </div>
                            <div>
                                <label class="field-label">Last Name</label>
                                <input type="text" id="mother-last-name" class="field-box" placeholder="Mother's last name">
                            </div>
                        </div>
                    </div>

                    <div class="field-group" style="background-color: #ccfbf1; border-color: #14b8a6;">
                        <h5 class="font-medium text-black mb-3 flex items-center">
                            <span class="bg-teal-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm mr-2">6</span>
                            <span class="text-black">FATHER'S NAME</span>
                        </h5>
                        <div class="field-row three-cols">
                            <div>
                                <label class="field-label">First Name</label>
                                <input type="text" id="father-first-name" class="field-box" placeholder="Father's first name">
                            </div>
                            <div>
                                <label class="field-label">Middle Name</label>
                                <input type="text" id="father-middle-name" class="field-box" placeholder="Father's middle name">
                            </div>
                            <div>
                                <label class="field-label">Last Name</label>
                                <input type="text" id="father-last-name" class="field-box" placeholder="Father's last name">
                            </div>
                        </div>
                    </div>

                    <div class="field-group" style="background-color: #e0e7ff; border-color: #6366f1;">
                        <h5 class="font-medium text-black mb-3 flex items-center">
                            <span class="bg-indigo-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm mr-2">7</span>
                            <span class="text-black">Registry Information</span>
                        </h5>
                        <div class="field-row two-cols">
                            <div>
                                <label class="field-label">Registry No.</label>
                                <input type="text" id="registry-number" class="field-box" placeholder="Registry number">
                            </div>
                            <div>
                                <label class="field-label">BReN</label>
                                <input type="text" id="bren-number" class="field-box" placeholder="BReN number">
                            </div>
                        </div>
                    </div>

                    <div class="field-group">
                        <h5 class="font-medium text-gray-800 mb-3">Additional Information</h5>
                        <div class="field-row two-cols">
                            <div>
                                <label class="field-label">Citizenship</label>
                                <input type="text" id="citizenship" class="field-box" placeholder="Citizenship">
                            </div>
                            <div>
                                <label class="field-label">Religion</label>
                                <input type="text" id="religion" class="field-box" placeholder="Religion">
                            </div>
                        </div>
                    </div>

                    <div class="field-group">
                        <h5 class="font-medium text-gray-800 mb-3 flex items-center">
                            <i class="fas fa-file-alt text-gray-500 mr-2"></i>Raw OCR Text
                        </h5>
                        <button type="button" id="toggle-raw-text-birth"
                                class="flex items-center text-sm font-medium text-gray-700 hover:text-gray-900 mb-3 transition-colors duration-200">
                            <i class="fas fa-chevron-right mr-2 transition-transform duration-200"></i>
                            Show Raw OCR Text
                        </button>
                        <div id="raw-text-container-birth" class="hidden overflow-hidden transition-all duration-300 ease-in-out">
                            <div class="mt-3 space-y-2">
                                <label class="field-label">Raw Extracted Text</label>
                                <textarea id="raw-ocr-text-birth" rows="15"
                                        class="field-box font-mono w-full resize-none max-h-96 overflow-y-auto"
                                        placeholder="Raw OCR text will appear here..." readonly></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- END birth-certificate-layout --}}

            <!-- Death Certificate Fields -->
            <div id="death-certificate-layout" class="hidden grid grid-cols-1 lg:grid-cols-2 gap-8 w-full">

                <div class="space-y-6">
                    <h4 class="section-header">
                        <i class="fas fa-user-times text-red-500 mr-2"></i>Deceased Person's Information
                    </h4>

                    <div class="field-group" style="background-color: #dbeafe; border-color: #3b82f6;">
                        <h5 class="font-medium text-blue-800 mb-3 flex items-center">
                            <span class="bg-blue-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm mr-2">1</span>
                            DECEASED NAME
                        </h5>
                        <div class="field-row three-cols">
                            <div>
                                <label class="field-label">First Name</label>
                                <input type="text" id="deceased-first-name" class="field-box" placeholder="First name">
                            </div>
                            <div>
                                <label class="field-label">Middle Name</label>
                                <input type="text" id="deceased-middle-name" class="field-box" placeholder="Middle name">
                            </div>
                            <div>
                                <label class="field-label">Last Name</label>
                                <input type="text" id="deceased-last-name" class="field-box" placeholder="Last name">
                            </div>
                        </div>
                    </div>

                    <div class="field-group" style="background-color: #dcfce7; border-color: #10b981;">
                        <h5 class="font-medium text-black mb-3 flex items-center">
                            <span class="bg-green-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm mr-2">2</span>
                            <span class="text-black">SEX &amp; AGE</span>
                        </h5>
                        <div class="field-row two-cols">
                            <div>
                                <label class="field-label">Sex</label>
                                <input type="text" id="deceased-sex" class="field-box" placeholder="Deceased sex">
                            </div>
                            <div>
                                <label class="field-label">Age at Death</label>
                                <input type="text" id="deceased-age" class="field-box" placeholder="Age at death">
                            </div>
                        </div>
                    </div>

                    <div class="field-group" style="background-color: #f3e8ff; border-color: #8b5cf6;">
                        <h5 class="font-medium text-black mb-3 flex items-center">
                            <span class="bg-purple-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm mr-2">3</span>
                            <span class="text-black">DATE OF DEATH</span>
                        </h5>
                        <div class="field-row three-cols">
                            <div>
                                <label class="field-label">Day</label>
                                <input type="text" id="death-date-day" class="field-box" placeholder="DD" maxlength="2">
                            </div>
                            <div>
                                <label class="field-label">Month</label>
                                <input type="text" id="death-date-month" class="field-box" placeholder="Month">
                            </div>
                            <div>
                                <label class="field-label">Year</label>
                                <input type="text" id="death-date-year" class="field-box" placeholder="YYYY" maxlength="4">
                            </div>
                        </div>
                    </div>

                    <div class="field-group" style="background-color: #fed7aa; border-color: #f97316;">
                        <h5 class="font-medium text-black mb-3 flex items-center">
                            <span class="bg-orange-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm mr-2">4</span>
                            <span class="text-black">PLACE OF DEATH</span>
                        </h5>
                        <div class="space-y-3">
                            <div class="field-row single-col">
                                <div>
                                    <label class="field-label">Institution/Address</label>
                                    <input type="text" id="death-place-institution" class="field-box" placeholder="Hospital/Institution or address">
                                </div>
                            </div>
                            <div class="field-row two-cols">
                                <div>
                                    <label class="field-label">City/Municipality</label>
                                    <input type="text" id="death-place-city" class="field-box" placeholder="City/Municipality">
                                </div>
                                <div>
                                    <label class="field-label">Province</label>
                                    <input type="text" id="death-place-province" class="field-box" placeholder="Province">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <h4 class="section-header">
                        <i class="fas fa-clipboard-list text-red-500 mr-2"></i>Death Details
                    </h4>

                    <div class="field-group" style="background-color: #fce7f3; border-color: #ec4899;">
                        <h5 class="font-medium text-black mb-3 flex items-center">
                            <span class="bg-pink-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm mr-2">5</span>
                            CAUSE OF DEATH
                        </h5>
                        <div class="field-row single-col">
                            <div>
                                <label class="field-label">Cause of Death</label>
                                <textarea id="cause-of-death" class="field-box" rows="3" placeholder="Cause of death"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="field-group" style="background-color: #ccfbf1; border-color: #14b8a6;">
                        <h5 class="font-medium text-black mb-3 flex items-center">
                            <span class="bg-teal-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm mr-2">6</span>
                            <span class="text-black">PARENTS INFORMATION</span>
                        </h5>
                        <div class="field-row two-cols">
                            <div>
                                <label class="field-label">Father's Name</label>
                                <input type="text" id="father-name" class="field-box" placeholder="Father's name">
                            </div>
                            <div>
                                <label class="field-label">Mother's Maiden Name</label>
                                <input type="text" id="mother-maiden-name" class="field-box" placeholder="Mother's maiden name">
                            </div>
                        </div>
                    </div>

                    <div class="field-group" style="background-color: #e0e7ff; border-color: #6366f1;">
                        <h5 class="font-medium text-black mb-3 flex items-center">
                            <span class="bg-indigo-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm mr-2">7</span>
                            <span class="text-black">Registry Information</span>
                        </h5>
                        <div class="field-row two-cols">
                            <div>
                                <label class="field-label">Registry No.</label>
                                <input type="text" id="registry-number-death" class="field-box" placeholder="Registry number">
                            </div>
                            <div>
                                <label class="field-label">Certificate No.</label>
                                <input type="text" id="certificate-number" class="field-box" placeholder="Certificate number">
                            </div>
                        </div>
                    </div>

                    <div class="field-group" style="background-color: #f1f5f9; border-color: #64748b;">
                        <h5 class="font-medium text-black mb-3 flex items-center">
                            <span class="bg-slate-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm mr-2">8</span>
                            <span class="text-black">Informant Information</span>
                        </h5>
                        <div class="field-row two-cols">
                            <div>
                                <label class="field-label">Informant Name</label>
                                <input type="text" id="informant-name" class="field-box" placeholder="Informant name">
                            </div>
                            <div>
                                <label class="field-label">Relationship</label>
                                <input type="text" id="informant-relationship" class="field-box" placeholder="Relationship to deceased">
                            </div>
                        </div>
                    </div>

                    <div class="field-group">
                        <h5 class="font-medium text-gray-800 mb-3 flex items-center">
                            <i class="fas fa-file-alt text-gray-500 mr-2"></i>Raw OCR Text
                        </h5>
                        <button type="button" id="toggle-raw-text-death"
                                class="flex items-center text-sm font-medium text-gray-700 hover:text-gray-900 mb-3 transition-colors duration-200">
                            <i class="fas fa-chevron-right mr-2 transition-transform duration-200"></i>
                            Show Raw OCR Text
                        </button>
                        <div id="raw-text-container-death" class="hidden overflow-hidden transition-all duration-300 ease-in-out">
                            <div class="mt-3 space-y-2">
                                <label class="field-label">Raw Extracted Text</label>
                                <textarea id="raw-ocr-text-death" rows="15"
                                        class="field-box font-mono w-full resize-none max-h-96 overflow-y-auto"
                                        placeholder="Raw OCR text will appear here..." readonly></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- END death-certificate-layout --}}

            <!-- Marriage Certificate Fields -->
            <div id="marriage-certificate-layout" class="hidden grid grid-cols-1 lg:grid-cols-2 gap-8 w-full">

                <div class="space-y-6">
                    <h4 class="section-header">
                        <i class="fas fa-male text-blue-500 mr-2"></i>Groom's Information
                    </h4>

                    <div class="field-group" style="background-color: #dbeafe; border-color: #3b82f6;">
                        <h5 class="font-medium text-blue-800 mb-3 flex items-center">
                            <span class="bg-blue-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm mr-2">1</span>
                            GROOM'S NAME
                        </h5>
                        <div class="field-row three-cols">
                            <div>
                                <label class="field-label">First Name</label>
                                <input type="text" id="groom-first-name" class="field-box" placeholder="Groom's first name">
                            </div>
                            <div>
                                <label class="field-label">Middle Name</label>
                                <input type="text" id="groom-middle-name" class="field-box" placeholder="Groom's middle name">
                            </div>
                            <div>
                                <label class="field-label">Last Name</label>
                                <input type="text" id="groom-last-name" class="field-box" placeholder="Groom's last name">
                            </div>
                        </div>
                    </div>

                    <div class="field-group" style="background-color: #dcfce7; border-color: #10b981;">
                        <h5 class="font-medium text-black mb-3 flex items-center">
                            <span class="bg-green-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm mr-2">2</span>
                            <span class="text-black">GROOM'S DETAILS</span>
                        </h5>
                        <div class="space-y-3">
                            <div class="field-row two-cols">
                                <div>
                                    <label class="field-label">Birth Date</label>
                                    <input type="text" id="groom-birth-date" class="field-box" placeholder="Groom's birth date">
                                </div>
                                <div>
                                    <label class="field-label">Citizenship</label>
                                    <input type="text" id="groom-citizenship" class="field-box" placeholder="Groom's citizenship">
                                </div>
                            </div>
                            <div class="field-row single-col">
                                <div>
                                    <label class="field-label">Birth Place</label>
                                    <input type="text" id="groom-birth-place" class="field-box" placeholder="Groom's birth place">
                                </div>
                            </div>
                            <div class="field-row two-cols">
                                <div>
                                    <label class="field-label">Civil Status</label>
                                    <input type="text" id="groom-civil-status" class="field-box" placeholder="Civil status">
                                </div>
                                <div>
                                    <label class="field-label">Religion</label>
                                    <input type="text" id="groom-religion" class="field-box" placeholder="Groom's religion">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="field-group" style="background-color: #f3e8ff; border-color: #8b5cf6;">
                        <h5 class="font-medium text-black mb-3 flex items-center">
                            <span class="bg-purple-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm mr-2">3</span>
                            <span class="text-black">GROOM'S RESIDENCE</span>
                        </h5>
                        <div class="field-row single-col">
                            <div>
                                <label class="field-label">Residence Address</label>
                                <input type="text" id="groom-residence" class="field-box" placeholder="Groom's residence address">
                            </div>
                        </div>
                    </div>

                    <div class="field-group" style="background-color: #fed7aa; border-color: #f97316;">
                        <h5 class="font-medium text-black mb-3 flex items-center">
                            <span class="bg-orange-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm mr-2">4</span>
                            <span class="text-black">GROOM'S PARENTS</span>
                        </h5>
                        <div class="space-y-3">
                            <div class="field-row single-col">
                                <div>
                                    <label class="field-label">Father's Name</label>
                                    <input type="text" id="groom-father-name" class="field-box" placeholder="Groom's father's name">
                                </div>
                            </div>
                            <div class="field-row single-col">
                                <div>
                                    <label class="field-label">Mother's Maiden Name</label>
                                    <input type="text" id="groom-mother-name" class="field-box" placeholder="Groom's mother's maiden name">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <h4 class="section-header">
                        <i class="fas fa-female text-pink-500 mr-2"></i>Bride's Information
                    </h4>

                    <div class="field-group" style="background-color: #fce7f3; border-color: #ec4899;">
                        <h5 class="font-medium text-black mb-3 flex items-center">
                            <span class="bg-pink-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm mr-2">5</span>
                            BRIDE'S NAME
                        </h5>
                        <div class="field-row three-cols">
                            <div>
                                <label class="field-label">First Name</label>
                                <input type="text" id="bride-first-name" class="field-box" placeholder="Bride's first name">
                            </div>
                            <div>
                                <label class="field-label">Middle Name</label>
                                <input type="text" id="bride-middle-name" class="field-box" placeholder="Bride's middle name">
                            </div>
                            <div>
                                <label class="field-label">Last Name</label>
                                <input type="text" id="bride-last-name" class="field-box" placeholder="Bride's last name">
                            </div>
                        </div>
                    </div>

                    <div class="field-group" style="background-color: #ccfbf1; border-color: #14b8a6;">
                        <h5 class="font-medium text-black mb-3 flex items-center">
                            <span class="bg-teal-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm mr-2">6</span>
                            <span class="text-black">BRIDE'S DETAILS</span>
                        </h5>
                        <div class="space-y-3">
                            <div class="field-row two-cols">
                                <div>
                                    <label class="field-label">Birth Date</label>
                                    <input type="text" id="bride-birth-date" class="field-box" placeholder="Bride's birth date">
                                </div>
                                <div>
                                    <label class="field-label">Citizenship</label>
                                    <input type="text" id="bride-citizenship" class="field-box" placeholder="Bride's citizenship">
                                </div>
                            </div>
                            <div class="field-row single-col">
                                <div>
                                    <label class="field-label">Birth Place</label>
                                    <input type="text" id="bride-birth-place" class="field-box" placeholder="Bride's birth place">
                                </div>
                            </div>
                            <div class="field-row two-cols">
                                <div>
                                    <label class="field-label">Civil Status</label>
                                    <input type="text" id="bride-civil-status" class="field-box" placeholder="Civil status">
                                </div>
                                <div>
                                    <label class="field-label">Religion</label>
                                    <input type="text" id="bride-religion" class="field-box" placeholder="Bride's religion">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="field-group" style="background-color: #e0e7ff; border-color: #6366f1;">
                        <h5 class="font-medium text-black mb-3 flex items-center">
                            <span class="bg-indigo-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm mr-2">7</span>
                            <span class="text-black">BRIDE'S RESIDENCE</span>
                        </h5>
                        <div class="field-row single-col">
                            <div>
                                <label class="field-label">Residence Address</label>
                                <input type="text" id="bride-residence" class="field-box" placeholder="Bride's residence address">
                            </div>
                        </div>
                    </div>

                    <div class="field-group" style="background-color: #f1f5f9; border-color: #64748b;">
                        <h5 class="font-medium text-black mb-3 flex items-center">
                            <span class="bg-slate-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm mr-2">8</span>
                            <span class="text-black">BRIDE'S PARENTS</span>
                        </h5>
                        <div class="space-y-3">
                            <div class="field-row single-col">
                                <div>
                                    <label class="field-label">Father's Name</label>
                                    <input type="text" id="bride-father-name" class="field-box" placeholder="Bride's father's name">
                                </div>
                            </div>
                            <div class="field-row single-col">
                                <div>
                                    <label class="field-label">Mother's Maiden Name</label>
                                    <input type="text" id="bride-mother-name" class="field-box" placeholder="Bride's mother's maiden name">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Full-width marriage details -->
                <div class="col-span-1 lg:col-span-2 space-y-6">
                    <h4 class="section-header">
                        <i class="fas fa-heart text-red-500 mr-2"></i>Marriage Details &amp; Registry Information
                    </h4>

                    <div class="field-group" style="background-color: #fef3c7; border-color: #f59e0b;">
                        <h5 class="font-medium text-black mb-3 flex items-center">
                            <span class="bg-yellow-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm mr-2">9</span>
                            <span class="text-black">MARRIAGE DETAILS</span>
                        </h5>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="space-y-3">
                                <div>
                                    <label class="field-label">Marriage Date</label>
                                    <input type="text" id="marriage-date" class="field-box" placeholder="Marriage date">
                                </div>
                                <div>
                                    <label class="field-label">Marriage Place (City)</label>
                                    <input type="text" id="marriage-place-city" class="field-box" placeholder="City/Municipality">
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div>
                                    <label class="field-label">Marriage Place (Province)</label>
                                    <input type="text" id="marriage-place-province" class="field-box" placeholder="Province">
                                </div>
                                <div>
                                    <label class="field-label">Officiant/Solemnized By</label>
                                    <input type="text" id="marriage-officiant" class="field-box" placeholder="Officiant name">
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div>
                                    <label class="field-label">Position/Title</label>
                                    <input type="text" id="marriage-officiant-position" class="field-box" placeholder="Position/Title">
                                </div>
                                <div>
                                    <label class="field-label">License Number</label>
                                    <input type="text" id="marriage-license-number" class="field-box" placeholder="License number">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="field-group" style="background-color: #ede9fe; border-color: #7c3aed;">
                        <h5 class="font-medium text-black mb-3 flex items-center">
                            <span class="bg-violet-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm mr-2">10</span>
                            <span class="text-black">WITNESSES</span>
                        </h5>
                        <div class="field-row two-cols">
                            <div>
                                <label class="field-label">Witness 1 Name</label>
                                <input type="text" id="marriage-witness-1" class="field-box" placeholder="First witness name">
                            </div>
                            <div>
                                <label class="field-label">Witness 2 Name</label>
                                <input type="text" id="marriage-witness-2" class="field-box" placeholder="Second witness name">
                            </div>
                        </div>
                    </div>

                    <div class="field-group" style="background-color: #f0fdf4; border-color: #16a34a;">
                        <h5 class="font-medium text-black mb-3 flex items-center">
                            <span class="bg-green-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm mr-2">11</span>
                            <span class="text-black">REGISTRY INFORMATION</span>
                        </h5>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="field-label">Registry Number</label>
                                <input type="text" id="marriage-registry-number" class="field-box" placeholder="Registry number">
                            </div>
                            <div>
                                <label class="field-label">Book Number</label>
                                <input type="text" id="marriage-book-number" class="field-box" placeholder="Book number">
                            </div>
                            <div>
                                <label class="field-label">Page Number</label>
                                <input type="text" id="marriage-page-number" class="field-box" placeholder="Page number">
                            </div>
                            <div>
                                <label class="field-label">Volume Number</label>
                                <input type="text" id="marriage-volume-number" class="field-box" placeholder="Volume number">
                            </div>
                        </div>
                    </div>

                    <div class="field-group">
                        <h5 class="font-medium text-gray-800 mb-3 flex items-center">
                            <i class="fas fa-file-alt text-gray-500 mr-2"></i>Raw OCR Text
                        </h5>
                        <button type="button" id="toggle-raw-text-marriage"
                                class="flex items-center text-sm font-medium text-gray-700 hover:text-gray-900 mb-3 transition-colors duration-200">
                            <i class="fas fa-chevron-right mr-2 transition-transform duration-200"></i>
                            Show Raw OCR Text
                        </button>
                        <div id="raw-text-container-marriage" class="hidden overflow-hidden transition-all duration-300 ease-in-out">
                            <div class="mt-3 space-y-2">
                                <label class="field-label">Raw Extracted Text</label>
                                <textarea id="raw-ocr-text-marriage" rows="15"
                                          class="field-box font-mono w-full resize-none max-h-96 overflow-y-auto"
                                          placeholder="Raw OCR text will appear here..." readonly></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- END marriage-certificate-layout --}}

        </div>
        {{-- END ocr-results-panel --}}

        <!-- Progress Modal — ID preserved -->
        <div id="progress-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
            <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                <div class="text-center">
                    <div class="mb-4">
                        <i id="progress-icon" class="fas fa-spinner fa-spin text-3xl text-blue-600"></i>
                    </div>
                    <h3 id="progress-title" class="text-lg font-medium text-gray-900 mb-2">Processing...</h3>
                    <p id="progress-message" class="text-gray-600 mb-4">Please wait while we process your request.</p>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div id="progress-bar" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>


<script>
class DocumentScanner {
    constructor() {
        this.scanners = [];
        this.currentFiles = [];
        this.selectedScanner = null;
        this.ocrResults = {};
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.detectScanners();
        this.setupCsrfToken();
        const initialDocumentType = document.getElementById('document-type')?.value || 'birth_certificate';
        this.handleDocumentTypeChange(initialDocumentType);
    }

    setupCsrfToken() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    }

    isOcrSupportedDocumentType(documentType) {
        return ['birth_certificate', 'death_certificate', 'marriage_certificate'].includes(documentType);
    }

setupEventListeners() {
    // Scanner control events
    document.getElementById('refresh-scanners').addEventListener('click', () => this.detectScanners());
    document.getElementById('scanner-select').addEventListener('change', (e) => this.selectScanner(e.target.value));
    document.getElementById('scan-preview').addEventListener('click', () => this.previewScan());
    document.getElementById('start-scan').addEventListener('click', () => this.startScan());

    // File upload events - DISABLE auto-OCR
    const cameraCapture = document.getElementById('camera-capture');
    const fileUpload = document.getElementById('file-upload');
    
    if (cameraCapture) {
        cameraCapture.addEventListener('change', (e) => this.handleFileUpload(e));
    }
    
    if (fileUpload) {
        fileUpload.addEventListener('change', (e) => this.handleFileUpload(e));
    }

    // NEW: Manual extraction button
    const extractBtn = document.getElementById('extract-document-data');
    if (extractBtn) {
        extractBtn.addEventListener('click', () => this.performManualExtraction());
    }

    // Document type change handler
    const documentTypeSelect = document.getElementById('document-type');
    if (documentTypeSelect) {
        documentTypeSelect.addEventListener('change', (e) => this.handleDocumentTypeChange(e.target.value));
    }

    // Document management events
    document.getElementById('save-document').addEventListener('click', () => this.saveDocument());
    document.getElementById('clear-all').addEventListener('click', () => this.clearAll());
    
    // Advanced settings toggle
    const toggleAdvancedButton = document.getElementById('toggle-advanced');
    if (toggleAdvancedButton) {
        toggleAdvancedButton.addEventListener('click', () => this.toggleAdvancedSettings());
    }

    // FIXED: Raw text toggle buttons - ADD THESE MISSING EVENT LISTENERS
    const toggleRawTextBirth = document.getElementById('toggle-raw-text-birth');
    const toggleRawTextDeath = document.getElementById('toggle-raw-text-death');
    const toggleRawTextMarriage = document.getElementById('toggle-raw-text-marriage');

    if (toggleRawTextBirth) {
        toggleRawTextBirth.addEventListener('click', (e) => {
            e.preventDefault();
            this.toggleRawText('birth');
        });
        console.log('✓ Birth certificate raw text toggle bound');
    }
    
    if (toggleRawTextDeath) {
        toggleRawTextDeath.addEventListener('click', (e) => {
            e.preventDefault();
            this.toggleRawText('death');
        });
        console.log('✓ Death certificate raw text toggle bound');
    }
    
    if (toggleRawTextMarriage) {
    toggleRawTextMarriage.addEventListener('click', (e) => {
        e.preventDefault();
        this.toggleRawText('marriage');
    });
    console.log('✓ Marriage certificate raw text toggle bound');
    }

    // Form validation
    const documentTitle = document.getElementById('document-title');
    if (documentTitle) {
        documentTitle.addEventListener('input', () => this.validateForm());
    }
    
    const blockchainValidation = document.getElementById('blockchain-validation');
    if (blockchainValidation) {
        blockchainValidation.addEventListener('change', () => this.handleBlockchainValidation());
    }
}

    async detectScanners() {
    this.updateScannerStatus('info', 'Connecting to scanner bridge...');
    
    try {
        const response = await fetch('http://127.0.0.1:3000/scanners', {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error('Scanner bridge not responding');
        }
        
        const data = await response.json();
        
        if (data.success && data.scanners && data.scanners.length > 0) {
            // FIX: Store in this.scanners instead of this.availableScanners
            this.scanners = data.scanners;
            this.populateScannerSelect();
            this.updateScannerStatus('success', data.message);
        } else {
            this.updateScannerStatus('warning', 'No scanners detected');
            this.loadMockScanners(); 
        }
        
    } catch (error) {
        console.error('Scanner bridge connection failed:', error);
        this.updateScannerStatus('error', 'Scanner bridge offline. Using mock scanners.');
        this.loadMockScanners(); 
    }
}

/**
 * Handle file upload from camera capture or file input
 * @param {Event} event - The change event from the file input
 */
handleFileUpload(event) {
    const files = event.target.files;
    
    if (!files || files.length === 0) {
        console.warn('No files selected');
        return;
    }
    
    console.log(`Processing ${files.length} uploaded file(s)`);
    
    // Process each uploaded file
    Array.from(files).forEach(file => {
        // Validate file type (images only for OCR processing)
        const validImageTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/bmp', 'image/tiff'];
        const isPDF = file.type === 'application/pdf';
        const isImage = validImageTypes.includes(file.type);
        
        if (!isImage && !isPDF) {
            this.showNotification(`Unsupported file type: ${file.type}. Please upload images or PDF files.`, 'warning');
            console.warn('Unsupported file type:', file.type);
            return;
        }
        
        // Create file object for internal tracking
        const fileObj = {
            id: 'upload_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
            name: file.name,
            file: file,
            type: isImage ? 'image' : 'pdf',
            size: this.formatFileSize(file.size),
            isScanned: false,
            ocrProcessed: false,
            path: URL.createObjectURL(file),
            uploadedAt: new Date().toISOString()
        };
        
        // Add to current files array
        this.currentFiles.push(fileObj);
        console.log('File added:', fileObj.name, `(${fileObj.size})`);
        
        // Update UI
        this.showPreview(fileObj.path, fileObj.name, fileObj.type);
        this.showDocumentDetails();
        this.showExtractionControl();
        
        // Show notification
        this.showNotification(
            `File uploaded: ${file.name} (${this.formatFileSize(file.size)})`, 
            'success'
        );
    });
    
    // Update file list display
    this.updateFileList();
    
    // Reset the file input so the same file can be selected again if needed
    event.target.value = '';
    
    console.log(`Total files in queue: ${this.currentFiles.length}`);
}


populateScannerSelect() {
    const select = document.getElementById('scanner-select');
    if (!select) {
        console.error('Scanner select element not found');
        return;
    }
    
    select.innerHTML = '<option value="">Select a scanner...</option>';
    
    this.scanners.forEach(scanner => {
        const option = document.createElement('option');
        option.value = scanner.id;
        // FIX: Handle missing status property
        const status = scanner.status || 'Ready';
        option.textContent = `${scanner.name} (${status})`;
        select.appendChild(option);
    });
    
    console.log('Scanner select populated with', this.scanners.length, 'options');
}

    loadMockScanners() {
        console.log('Loading mock scanners for development...');
        this.scanners = [
            {
                id: 'brother_mfc_t4500dw',
                name: 'Brother MFC-T4500DW (Mock)',
                status: 'Ready',
                maxResolution: '1200 DPI',
                capabilities: ['color', 'grayscale', 'bw', 'duplex', 'ocr']
            },
            {
                id: 'virtual_scanner',
                name: 'Virtual Scanner (Development)',
                status: 'Ready',
                maxResolution: '600 DPI',
                capabilities: ['color', 'grayscale', 'bw']
            }
        ];
        this.populateScannerSelect();
        this.updateScannerStatus('ready', `${this.scanners.length} scanner(s) available (mock)`);
    }

    populateScannerSelect() {
        const select = document.getElementById('scanner-select');
        if (!select) {
            console.error('Scanner select element not found');
            return;
        }
        
        select.innerHTML = '<option value="">Select a scanner...</option>';
        
        this.scanners.forEach(scanner => {
            const option = document.createElement('option');
            option.value = scanner.id;
            option.textContent = `${scanner.name}${scanner.status ? ' (' + scanner.status + ')' : ''}`;
            select.appendChild(option);
        });
        
        console.log('Scanner select populated with', this.scanners.length, 'options');
    }

    selectScanner(scannerId) {
        this.selectedScanner = this.scanners.find(s => s.id === scannerId);
        const startButton = document.getElementById('start-scan');
        
        if (this.selectedScanner) {
            startButton.disabled = false;
            this.updateScannerStatus('selected', `${this.selectedScanner.name} selected`);
            console.log('Scanner selected:', this.selectedScanner);
        } else {
            startButton.disabled = true;
            this.updateScannerStatus('ready', 'Please select a scanner');
        }
    }

    updateScannerStatus(type, message) {
        const statusElement = document.getElementById('scanner-status');
        if (!statusElement) {
            console.error('Scanner status element not found');
            return;
        }
        
        const iconMap = {
            detecting: 'fa-search',
            ready: 'fa-check-circle',
            selected: 'fa-check-circle',
            scanning: 'fa-spinner fa-spin',
            error: 'fa-exclamation-triangle',
            none: 'fa-times-circle'
        };
        
        const colorMap = {
            detecting: 'bg-yellow-100 text-yellow-800',
            ready: 'bg-green-100 text-green-800',
            selected: 'bg-blue-100 text-blue-800',
            scanning: 'bg-blue-100 text-blue-800',
            error: 'bg-red-100 text-red-800',
            none: 'bg-gray-100 text-gray-800'
        };
        
        statusElement.className = `px-3 py-1 rounded-full text-sm font-medium ${colorMap[type]}`;
        statusElement.innerHTML = `<i class="fas ${iconMap[type]} text-xs mr-1"></i>${message}`;
        
        console.log('Scanner status updated:', type, message);
    }

    toggleAdvancedSettings() {
        const settings = document.getElementById('advanced-settings');
        const button = document.getElementById('toggle-advanced');
        
        if (!settings || !button) {
            console.error('Advanced settings elements not found');
            return;
        }
        
        const icon = button.querySelector('i');
        
        if (settings.classList.contains('hidden')) {
            settings.classList.remove('hidden');
            if (icon) {
                icon.className = 'fas fa-chevron-up mr-1';
            }
            button.innerHTML = '<i class="fas fa-chevron-up mr-1"></i>Hide Advanced Settings';
            console.log('Advanced settings shown');
        } else {
            settings.classList.add('hidden');
            if (icon) {
                icon.className = 'fas fa-chevron-down mr-1';
            }
            button.innerHTML = '<i class="fas fa-chevron-down mr-1"></i>Advanced Settings';
            console.log('Advanced settings hidden');
        }
    }

    async previewScan() {
        if (!this.selectedScanner) {
            this.showNotification('Please select a scanner first', 'warning');
            return;
        }
        this.showNotification('Preview functionality coming soon', 'info');
    }

    async startScan() {
    if (!this.selectedScanner) {
        this.showNotification('Please select a scanner first', 'warning');
        return;
    }
    
    console.log('🖨️ Starting automated scan with scanner:', this.selectedScanner);
    
    // Enforce fixed scanner defaults.
    const dpi = 150;
    const colorMode = 'color';
    const format = 'PDF';
    
    // Disable button during scan
    const startButton = document.getElementById('start-scan');
    if (!startButton) {
        console.error('Start scan button not found');
        return;
    }
    
    const originalText = startButton.textContent;
    startButton.disabled = true;
    startButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Scanning...';
    
    // Update status
    this.updateScannerStatus('scanning', 'Scanning document...');
    
    try {
        console.log('Sending scan request to Node.js bridge...');
        console.log('Device ID:', this.selectedScanner.deviceId);
        console.log('Settings:', { dpi, colorMode, format });
        console.log('=== SCAN REQUEST DIAGNOSTIC ===');
        console.log('DPI default:', dpi, typeof dpi);
        console.log('Color mode default:', colorMode);
        console.log('Format default:', format);
        console.log('Payload being sent:', {
            scanner_id: this.selectedScanner.id,
            deviceId: this.selectedScanner.deviceId,
            dpi: dpi,
            colorMode: colorMode,
            format: format,
            pageCount: 1
        });
        console.log('===============================');
        // Call Node.js scanner bridge (automated version)
        const response = await fetch('http://127.0.0.1:3000/scan', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                scanner_id: this.selectedScanner.id,
                deviceId: this.selectedScanner.deviceId,
                dpi: dpi,
                colorMode: colorMode,
                format: format,
                pageCount: 1
            })
        });
        
        console.log('Scan response status:', response.status);
        
        if (!response.ok) {
            let errorMessage = `HTTP Error ${response.status}`;
            let diagnostics = null;
            try {
                const errorData = await response.json();
                errorMessage = errorData.message || errorData.error || errorMessage;
                diagnostics = errorData.diagnostics; // Capture diagnostics from backend
                
                // LOG DETAILED ERROR INFO IN DEV MODE
                console.error('=== SCAN ERROR DIAGNOSTICS ===');
                console.error('Status:', response.status);
                console.error('Message:', errorMessage);
                if (diagnostics) {
                    console.error('Settings sent:', diagnostics.settings);
                    console.error('PowerShell stdout:', diagnostics.stdout);
                    console.error('PowerShell stderr:', diagnostics.stderr);
                    console.error('Exit code:', diagnostics.exit_code);
                }
                console.error('==============================');
                
            } catch (e) {
                console.error('Could not parse error response:', e);
            }
            throw new Error(errorMessage);
        }
        
        const result = await response.json();
        console.log('✓ Scan API response:', result);
        
        if (!result.success || !result.data) {
            throw new Error(result.message || 'Scan failed - no data returned');
        }
        
        // Extract scan data
        const scanData = result.data;
        const base64Data = scanData.image_base64;
        const fileName = scanData.file_name;
        const fileSize = scanData.file_size;
        const mimeType = scanData.mime_type || `image/${format.toLowerCase()}`;
        const isPdfScan = mimeType === 'application/pdf' || (fileName || '').toLowerCase().endsWith('.pdf');
        const scanFileType = isPdfScan ? 'pdf' : 'image';
        
        console.log('Processing scanned image:', fileName, `(${fileSize} bytes)`);
        
        // Convert base64 to blob
        const byteCharacters = atob(base64Data);
        const byteNumbers = new Array(byteCharacters.length);
        for (let i = 0; i < byteCharacters.length; i++) {
            byteNumbers[i] = byteCharacters.charCodeAt(i);
        }
        const byteArray = new Uint8Array(byteNumbers);
        const blob = new Blob([byteArray], { type: mimeType });
        
        // Create File object
        const file = new File([blob], fileName, { 
            type: mimeType,
            lastModified: Date.now()
        });
        
        console.log('✓ File object created:', file.name, file.size, 'bytes');
        
        // Create file object for display
        const fileObj = {
            id: 'scan_' + Date.now(),
            name: fileName,
            file: file,
            type: scanFileType,
            size: this.formatFileSize(file.size),
            isScanned: true,
            ocrProcessed: false,
            path: URL.createObjectURL(blob),
            scanData: scanData  // Store full scan metadata
        };
        
        // Add to current files
        this.currentFiles.push(fileObj);
        console.log('✓ File added to currentFiles:', this.currentFiles.length, 'total files');
        
        // Update UI
        this.showPreview(fileObj.path, fileName, scanFileType);
        this.updateFileList();
        this.showDocumentDetails();
        this.showExtractionControl();
        
        // Update status
        this.updateScannerStatus('ready', `Scan completed: ${fileName}`);
        const selectedType = document.getElementById('document-type')?.value || 'birth_certificate';
        const nextStepHint = this.isOcrSupportedDocumentType(selectedType)
            ? (scanFileType === 'image'
                ? 'Select document type and extract data.'
                : 'Scanned file is PDF. Save directly, or upload an image file if OCR extraction is required.')
            : 'Enter document title and save directly (OCR not required).';
        this.showNotification(
            `✓ Document scanned successfully! (${this.formatFileSize(file.size)})\n${nextStepHint}`,
            'success'
        );
        
        console.log('✓✓✓ Scan completed successfully! ✓✓✓');
        
    } catch (error) {
        console.error('✗✗✗ Scan failed:', error);
        console.error('Error details:', error.message);
        console.error('Error stack:', error.stack);
        
        this.updateScannerStatus('error', `Scan failed`);
        
        // Show user-friendly error messages
        let userMessage = 'Scan failed: ';
        if (error.message.includes('Failed to fetch')) {
            userMessage += 'Cannot connect to scanner service. Please ensure Node.js scanner bridge is running.';
        } else if (error.message.includes('Device not found')) {
            userMessage += 'Scanner not found. Please reconnect scanner and refresh scanner list.';
        } else if (error.message.includes('timeout')) {
            userMessage += 'Scanner timeout. Please check if scanner is ready and has paper loaded.';
        } else {
            userMessage += error.message;
        }
        
        this.showNotification(userMessage, 'error');
        
    } finally {
        // Re-enable button
        if (startButton) {
            startButton.disabled = false;
            startButton.innerHTML = '<i class="fas fa-file-pdf mr-2"></i>Start Scan';
        }
    }
}
    
    // NEW: Show extraction control panel
showExtractionControl() {
    const extractionControl = document.getElementById('extraction-control');
    const extractBtn = document.getElementById('extract-document-data');
    const documentType = document.getElementById('document-type')?.value || 'birth_certificate';
    const shouldShowOcrControls = this.isOcrSupportedDocumentType(documentType);
    
    if (extractionControl && shouldShowOcrControls && this.hasImageFiles()) {
        extractionControl.classList.remove('hidden');
        
        // Enable button if image files are present
        if (extractBtn) {
            extractBtn.disabled = false;
        }
        
        console.log('Extraction control panel shown');
    } else if (extractionControl) {
        extractionControl.classList.add('hidden');
    }
}

// NEW: Hide extraction control panel
hideExtractionControl() {
    const extractionControl = document.getElementById('extraction-control');
    const extractBtn = document.getElementById('extract-document-data');
    
    if (extractionControl) {
        extractionControl.classList.add('hidden');
        
        if (extractBtn) {
            extractBtn.disabled = true;
        }
        
        console.log('Extraction control panel hidden');
    }
}

// NEW: Manual extraction handler
async performManualExtraction() {
    const documentType = document.getElementById('document-type').value;

    if (!this.isOcrSupportedDocumentType(documentType)) {
        this.showNotification('OCR is not required for this document type. You can save the document directly.', 'info');
        return;
    }

    const imageFile = this.currentFiles.find(file => 
        (file.type === 'image' || file.file?.type.startsWith('image/')) && !file.ocrProcessed
    );
    
    if (!imageFile) {
        this.showNotification('No image file available for extraction', 'warning');
        return;
    }
    
    if (!documentType) {
        this.showNotification('Please select a document type first', 'warning');
        return;
    }
    
    console.log('Starting manual extraction for document type:', documentType);
    
    // Show extraction status
    this.showExtractionStatus('processing', 'Extracting data from document...');
    
    try {
        await this.processFileOCRWithType(imageFile, documentType);
    } catch (error) {
        console.error('Manual extraction failed:', error);
        this.showExtractionStatus('error', 'Extraction failed: ' + error.message);
    }
}

// NEW: Show extraction status
showExtractionStatus(type, message) {
    const statusContainer = document.getElementById('extraction-status');
    const statusIcon = document.getElementById('extraction-status-icon');
    const statusText = document.getElementById('extraction-status-text');
    
    if (!statusContainer || !statusIcon || !statusText) return;
    
    statusContainer.classList.remove('hidden');
    
    const configs = {
        processing: {
            icon: 'fas fa-spinner fa-spin text-blue-600',
            bgClass: 'bg-blue-50 border-blue-200',
            textClass: 'text-blue-800'
        },
        success: {
            icon: 'fas fa-check-circle text-green-600',
            bgClass: 'bg-green-50 border-green-200',
            textClass: 'text-green-800'
        },
        error: {
            icon: 'fas fa-exclamation-triangle text-red-600',
            bgClass: 'bg-red-50 border-red-200',
            textClass: 'text-red-800'
        }
    };
    
    const config = configs[type] || configs.processing;
    
    statusIcon.className = config.icon;
    statusContainer.className = `mt-3 p-2 rounded-md border ${config.bgClass}`;
    statusText.className = `text-sm font-medium ${config.textClass}`;
    statusText.textContent = message;
    
    // Auto-hide success/error messages after 3 seconds
    if (type !== 'processing') {
        setTimeout(() => {
            statusContainer.classList.add('hidden');
        }, 3000);
    }
}

hideExtractionStatus() {
    const statusContainer = document.getElementById('extraction-status');
    if (statusContainer) {
        statusContainer.classList.add('hidden');
        console.log('Extraction status hidden');
    }
}

handleDocumentTypeChange(documentType) {
    const descriptions = {
        birth_certificate: {
            description: 'Birth Certificate - Extracts child\'s information, parents\' details, and registry data',
            fields: 'Fields: Name, Sex, Birth Date, Place of Birth, Parents\' Names, Registry Info'
        },
        death_certificate: {
            description: 'Death Certificate - Extracts deceased person\'s information and death details',
            fields: 'Fields: Deceased Name, Date of Death, Place of Death, Cause of Death, Age, Informant'
        },
        marriage_certificate: {
            description: 'Marriage Certificate - Extracts bride and groom information and marriage details',
            fields: 'Fields: Bride/Groom Names, Marriage Date, Marriage Place, Witnesses, Officiant'
        },
        admission_of_paternity: {
            description: 'Admission of Paternity - Voluntary acknowledgment document processed without OCR.',
            fields: 'OCR is not required. Scan/upload and save directly.'
        },
        ausf: {
            description: 'AUSF - Affidavit to Use the Surname of the Father.',
            fields: 'OCR is not required. Scan/upload and save directly.'
        },
        legitimation: {
            description: 'Legitimation affidavit record for child status update.',
            fields: 'OCR is not required. Scan/upload and save directly.'
        },
        affidavit_of_reappearance: {
            description: 'Affidavit of Reappearance for re-establishing marital capacity.',
            fields: 'OCR is not required. Scan/upload and save directly.'
        },
        marriage_settlement: {
            description: 'Marriage settlement or property agreement document.',
            fields: 'OCR is not required. Scan/upload and save directly.'
        },
        parental_authorization_ai: {
            description: 'Parental authorization or AI ratification legal document.',
            fields: 'OCR is not required. Scan/upload and save directly.'
        },
        late_registration: {
            description: 'Late registration document filed beyond statutory period.',
            fields: 'OCR is not required. Scan/upload and save directly.'
        },
        supplemental_report: {
            description: 'Supplemental report for omitted civil registry information.',
            fields: 'OCR is not required. Scan/upload and save directly.'
        },
        certificate_of_foundling: {
            description: 'Certificate of Foundling registration document.',
            fields: 'OCR is not required. Scan/upload and save directly.'
        },
        adoption_document: {
            description: 'Adoption decree or annotation record document.',
            fields: 'OCR is not required. Scan/upload and save directly.'
        },
        judicial_correction_rule_108: {
            description: 'Judicial correction of entries (Rule 108) document.',
            fields: 'OCR is not required. Scan/upload and save directly.'
        },
        annulment_or_nullity: {
            description: 'Annulment or declaration of nullity annotation document.',
            fields: 'OCR is not required. Scan/upload and save directly.'
        },
        recognition_of_foreign_divorce: {
            description: 'Recognition of foreign divorce registration document.',
            fields: 'OCR is not required. Scan/upload and save directly.'
        },
        marriage_license: {
            description: 'Marriage license application/issuance document.',
            fields: 'OCR is not required. Scan/upload and save directly.'
        },
        certificate_legal_capacity_to_marry: {
            description: 'Certificate of Legal Capacity to Contract Marriage.',
            fields: 'OCR is not required. Scan/upload and save directly.'
        },
        cenomar: {
            description: 'CENOMAR - Extracts personal information and certification details',
            fields: 'OCR is not required. Scan/upload and save directly.'
        },
        affidavit: {
            description: 'Affidavit legal instrument document.',
            fields: 'OCR is not required. Scan/upload and save directly.'
        },
        court_document: {
            description: 'Court decision or legal instrument document.',
            fields: 'OCR is not required. Scan/upload and save directly.'
        },
        contract: {
            description: 'Contract or agreement document.',
            fields: 'OCR is not required. Scan/upload and save directly.'
        },
        other: {
            description: 'Other Legal Document - General document information extraction',
            fields: 'OCR is not required. Scan/upload and save directly.'
        }
    };
    
    const info = descriptions[documentType] || descriptions.other;
    
    // Update info panel
    const descriptionEl = document.getElementById('document-type-description');
    const fieldsEl = document.getElementById('document-type-fields');
    
    if (descriptionEl) descriptionEl.textContent = info.description;
    if (fieldsEl) fieldsEl.textContent = info.fields;

    const isOcrSupported = this.isOcrSupportedDocumentType(documentType);
    const extractionControl = document.getElementById('extraction-control');
    const extractBtn = document.getElementById('extract-document-data');
    const ocrPanel = document.getElementById('ocr-results-panel');
    const applyOcrBtn = document.getElementById('apply-ocr');
    
    if (isOcrSupported) {
        // Switch field layouts based on OCR-supported document type
        switch (documentType) {
            case 'death_certificate':
                this.switchToDeathCertificateFields();
                break;
            case 'marriage_certificate':
                console.log('Marriage certificate selected - layout will show after extraction');
                break;
            case 'birth_certificate':
            default:
                this.switchToBirthCertificateFields();
                break;
        }

        const hasFiles = this.hasImageFiles();
        if (extractBtn) {
            extractBtn.disabled = !hasFiles;
            extractBtn.innerHTML = `<i class="fas fa-magic mr-2"></i>Extract Data from ${documentType.replace('_', ' ').toUpperCase()}`;
        }

        if (extractionControl && hasFiles) {
            extractionControl.classList.remove('hidden');
        }

        this.showApplyOCRButton();
    } else {
        ['birth-certificate-layout', 'death-certificate-layout', 'marriage-certificate-layout'].forEach((layoutId) => {
            const layout = document.getElementById(layoutId);
            if (layout) {
                layout.classList.add('hidden');
                layout.style.display = 'none';
            }
        });

        if (extractionControl) {
            extractionControl.classList.add('hidden');
        }

        if (extractBtn) {
            extractBtn.disabled = true;
            extractBtn.innerHTML = '<i class="fas fa-check-circle mr-2"></i>OCR Not Required';
        }

        if (applyOcrBtn) {
            applyOcrBtn.classList.add('hidden');
        }

        if (ocrPanel) {
            ocrPanel.classList.add('hidden');
        }

        if (this.currentFiles.length > 0) {
            this.showNotification('OCR is not required for this document type. You can save directly after entering a title.', 'info');
        }
    }
    
    console.log('Document type changed to:', documentType);
}

// ENHANCED: Process OCR with document type
async processFileOCRWithType(fileObj, documentType) {
    if (!this.isOcrSupportedDocumentType(documentType)) {
        this.showExtractionStatus('success', 'OCR skipped for this document type. Save is ready once title is provided.');
        this.showNotification('OCR is not required for this document type.', 'info');
        return;
    }

    if (!fileObj.file || !fileObj.file.type.startsWith('image/')) {
        this.showNotification('OCR only supports image files', 'warning');
        return;
    }

    console.log('Starting OCR processing for:', fileObj.name, 'Type:', documentType);
    this.showOCRProcessing();
    this.showProgress('Processing OCR for ' + fileObj.name, 'fas fa-text-width');

    try {
        const formData = new FormData();
        formData.append('file', fileObj.file);
        formData.append('_token', this.csrfToken);
        formData.append('document_type', documentType); // Pass selected document type

        console.log('Sending OCR request for document type:', documentType);

        const response = await fetch('/staff/scan/ocr-process', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': this.csrfToken,
                'Accept': 'application/json'
            },
            body: formData
        });

        console.log('OCR Response received:', response.status, response.statusText);

        const data = await response.json();
        console.log('OCR Response data:', data);

        if (response.ok && data.success) {
            fileObj.ocrProcessed = true;
            fileObj.ocrData = data.ocr_results;
            this.ocrResults[fileObj.id] = data.ocr_results;
            
            // Show appropriate fields based on document type
            this.displayOCRResultsByType(data.ocr_results, documentType);
            this.hideProgress();
            this.showExtractionStatus('success', 'Data extraction completed successfully!');
            this.showNotification('Document data extracted successfully!', 'success');
            
            // Auto-fill document title
            const titleField = document.getElementById('document-title');
            if (!titleField.value && data.ocr_results.extracted_fields) {
                const title = this.generateDocumentTitle(data.ocr_results.extracted_fields, documentType);
                if (title) {
                    titleField.value = title;
                }
            }
            
            this.updateFileList();
            this.hideExtractionControl(); // Hide the extraction button after successful extraction
            
        } else {
            throw new Error(data.message || `HTTP ${response.status}: ${response.statusText}`);
        }

        } catch (error) {
        this.hideProgress();
        this.hideExtractionStatus(); // ADDED: Hide extraction status
        this.showExtractionStatus('error', 'Extraction failed: ' + error.message);
        console.error('OCR Error:', error);
        
        let errorMessage = 'OCR processing failed: ' + error.message;
        this.showNotification(errorMessage, 'error');
        this.showOCRError(errorMessage);
    }
}

displayOCRResultsByType(ocrResults, documentType) {
    console.log('=== DISPLAY OCR RESULTS DEBUG ===');
    console.log('Document type:', documentType);
    console.log('OCR results:', ocrResults);
    
    // CRITICAL: Show the OCR results panel FIRST
    this.showOCRResultsPanel();
    
    // Update metrics BEFORE switching layouts
    this.updateOCRMetrics(ocrResults);
    
    // Get layout elements
    const birthLayout = document.getElementById('birth-certificate-layout');
    const deathLayout = document.getElementById('death-certificate-layout');
    const marriageLayout = document.getElementById('marriage-certificate-layout');

    console.log('Layout elements found:', {
        birth: !!birthLayout,
        death: !!deathLayout,
        marriage: !!marriageLayout,
    });
    
    // FIXED: Always ensure proper layout switching
    switch (documentType) {
    case 'death_certificate':
        console.log('Switching to death certificate fields');
        if (birthLayout) {
            birthLayout.classList.add('hidden');
            birthLayout.style.display = 'none';
        }
        if (marriageLayout) {
            marriageLayout.classList.add('hidden');
            marriageLayout.style.display = 'none';
        }
        if (deathLayout) {
            deathLayout.classList.remove('hidden');
            deathLayout.style.display = 'grid';
        }
        // Populate death certificate fields
        this.displayDeathCertificateFields(ocrResults.extracted_fields || {});
        break;
        
    case 'marriage_certificate':
        console.log('Switching to marriage certificate fields');
        if (birthLayout) {
            birthLayout.classList.add('hidden');
            birthLayout.style.display = 'none';
        }
        if (deathLayout) {
            deathLayout.classList.add('hidden');
            deathLayout.style.display = 'none';
        }
        if (marriageLayout) {
            marriageLayout.classList.remove('hidden');
            marriageLayout.style.display = 'grid';
        }
        // Populate marriage certificate fields
        this.displayMarriageCertificateFields(ocrResults.extracted_fields || {});
        break;
        
    case 'birth_certificate':
    default:
        console.log('Switching to birth certificate fields');
        if (deathLayout) {
            deathLayout.classList.add('hidden');
            deathLayout.style.display = 'none';
        }
        if (marriageLayout) {
            marriageLayout.classList.add('hidden');
            marriageLayout.style.display = 'none';
        }
        if (birthLayout) {
            birthLayout.classList.remove('hidden');
            birthLayout.style.display = 'grid';
        }
        // Populate birth certificate fields
        this.displayBirthCertificateFields(ocrResults.extracted_fields || {});
        break;
}
    
    // FIXED: Display raw text but keep it hidden until toggle
    if (ocrResults.raw_text) {
        this.displayRawText(ocrResults.raw_text);
    }
    
    // CRITICAL: Setup OCR action buttons after results are displayed
    setTimeout(() => {
        this.setupEditMode();
    }, 300);
    
    // CRITICAL: Scroll to results panel
    setTimeout(() => {
        const panel = document.getElementById('ocr-results-panel');
        if (panel) {
            panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }, 100);
    
    console.log('=== END DISPLAY OCR RESULTS DEBUG ===');
}

showOCRResultsPanel() {
    const panel = document.getElementById('ocr-results-panel');
    if (panel) {
        panel.classList.remove('hidden');
        console.log('OCR results panel shown');
    }
}

updateOCRMetrics(ocrResults) {
    // Update confidence score
    const confidenceEl = document.getElementById('confidence-score');
    if (confidenceEl && ocrResults.confidence !== undefined) {
        confidenceEl.textContent = ocrResults.confidence + '%';
    }
    
    // Update words extracted
    const wordsEl = document.getElementById('words-extracted');
    if (wordsEl && ocrResults.word_count !== undefined) {
        wordsEl.textContent = ocrResults.word_count;
    }
    
    // Update fields detected
    const fieldsEl = document.getElementById('fields-detected');
    if (fieldsEl && ocrResults.extracted_fields) {
        const filledFields = Object.values(ocrResults.extracted_fields).filter(field => field && field.toString().trim() !== '').length;
        fieldsEl.textContent = filledFields;
    }
    
    console.log('OCR metrics updated');
}

displayBirthCertificateFields(fields) {
    console.log('Displaying birth certificate fields:', fields);
    
    if (!fields) return;
    
    // Fill name fields
    this.fillField('name-first', fields.name_first);
    this.fillField('name-middle', fields.name_middle);
    this.fillField('name-last', fields.name_last);
    
    // Fill sex field
    if (fields.sex) {
        this.fillField('sex-detected', fields.sex);
        // Also check radio button
        if (fields.sex.toLowerCase() === 'male') {
            this.checkRadio('sex-male');
        } else if (fields.sex.toLowerCase() === 'female') {
            this.checkRadio('sex-female');
        }
    }
    
    // Fill birth date fields
    this.fillField('birth-date-day', fields.birth_date_day);
    this.fillField('birth-date-month', fields.birth_date_month);
    this.fillField('birth-date-year', fields.birth_date_year);
    
    // Fill place of birth fields
    this.fillField('birth-place-institution', fields.birth_place_institution);
    this.fillField('birth-place-city', fields.birth_place_city);
    this.fillField('birth-place-province', fields.birth_place_province);
    
    // Fill mother's name fields
    this.fillField('mother-first-name', fields.mother_first_name);
    this.fillField('mother-middle-name', fields.mother_middle_name);
    this.fillField('mother-last-name', fields.mother_last_name);
    
    // Fill father's name fields
    this.fillField('father-first-name', fields.father_first_name);
    this.fillField('father-middle-name', fields.father_middle_name);
    this.fillField('father-last-name', fields.father_last_name);
    
    // Fill registry information
    this.fillField('registry-number', fields.registry_number);
    this.fillField('bren-number', fields.bren_number);
    this.fillField('citizenship', fields.citizenship);
    this.fillField('religion', fields.religion);
}

displayDeathCertificateFields(fields) {
    console.log('=== DEATH CERTIFICATE FIELD MAPPING DEBUG ===');
    console.log('Raw fields received:', fields);
    console.log('Available field keys:', Object.keys(fields || {}));
    
    if (!fields) {
        console.error('No fields received for death certificate display');
        return;
    }
    
    // First, show death certificate field structure
    this.switchToDeathCertificateFields();
    
    // ENHANCED: Multiple field mapping attempts with debug
    const nameFirst = fields.name_first || fields.deceased_first_name || fields.first_name || '';
    const nameMiddle = fields.name_middle || fields.deceased_middle_name || fields.middle_name || '';
    const nameLast = fields.name_last || fields.deceased_last_name || fields.last_name || '';
    
    console.log('Name mapping results:', {
        first: nameFirst,
        middle: nameMiddle,
        last: nameLast
    });
    
    this.fillField('deceased-first-name', nameFirst);
    this.fillField('deceased-middle-name', nameMiddle);
    this.fillField('deceased-last-name', nameLast);
    
    // Fill other fields with multiple fallbacks
    const sex = fields.sex || fields.deceased_sex || '';
    const age = fields.age_at_death || fields.deceased_age || fields.age || '';
    
    console.log('Other field mappings:', { sex, age });
    
    this.fillField('deceased-sex', sex);
    this.fillField('deceased-age', age);
    
    // Fill death date
    this.fillField('death-date-day', fields.death_date_day || '');
    this.fillField('death-date-month', fields.death_date_month || '');
    this.fillField('death-date-year', fields.death_date_year || '');
    
    // Fill place of death
    this.fillField('death-place-institution', fields.death_place_institution || '');
    this.fillField('death-place-city', fields.death_place_city || '');
    this.fillField('death-place-province', fields.death_place_province || '');
    
    // Fill cause of death
    this.fillField('cause-of-death', fields.cause_of_death || '');
    
    // Fill registry information
    this.fillField('registry-number', fields.registry_number || '');
    
    // Fill parent names
    this.fillField('father-name', fields.father_name || '');
    this.fillField('mother-maiden-name', fields.mother_maiden_name || '');
    
    // Fill certificate number
    this.fillField('certificate-number', fields.certificate_number || '');
    
    // Fill informant information
    this.fillField('informant-name', fields.informant_name || '');
    this.fillField('informant-relationship', fields.informant_relationship || '');
    
    console.log('=== END DEATH CERTIFICATE FIELD MAPPING ===');
    console.log('Death certificate fields populated with enhanced mapping');
}

// NEW: Display marriage certificate fields with enhanced mapping
displayMarriageCertificateFields(fields) {
    console.log('=== MARRIAGE CERTIFICATE FIELD MAPPING DEBUG ===');
    console.log('Raw fields received:', fields);
    console.log('Available field keys:', Object.keys(fields || {}));
    
    if (!fields) {
        console.error('No fields received for marriage certificate display');
        return;
    }
    
    // Helper function with enhanced error checking - reuse existing pattern
    const fillFieldSafely = (fieldId, value) => {
        const field = document.getElementById(fieldId);
        console.log(`Attempting to fill field ${fieldId}:`, {
            element: !!field,
            value: value
        });

        if (!field) {
            console.error(`✗ Field ${fieldId} not found in DOM`);
            return;
        }

        this.fillField(fieldId, value || '');

        if (value && value.toString().trim() !== '') {
            console.log(`✓ Filled field ${fieldId} with:`, value);
        } else {
            console.log(`- Field ${fieldId} not filled - no data`);
        }
    };
    
    // GROOM'S INFORMATION - Enhanced field mapping with multiple fallbacks
    const groomFirstName = fields.groom_first_name || fields.husband_first_name || fields.male_first_name || '';
    const groomMiddleName = fields.groom_middle_name || fields.husband_middle_name || fields.male_middle_name || '';
    const groomLastName = fields.groom_last_name || fields.husband_last_name || fields.male_last_name || '';
    
    console.log('Groom name mapping results:', {
        first: groomFirstName,
        middle: groomMiddleName,
        last: groomLastName
    });
    
    // Fill groom's name fields
    fillFieldSafely('groom-first-name', groomFirstName);
    fillFieldSafely('groom-middle-name', groomMiddleName);
    fillFieldSafely('groom-last-name', groomLastName);
    
    // Fill groom's details
    fillFieldSafely('groom-birth-date', fields.groom_birth_date || fields.husband_birth_date || '');
    fillFieldSafely('groom-citizenship', fields.groom_citizenship || fields.husband_citizenship || '');
    fillFieldSafely('groom-birth-place', fields.groom_birth_place || fields.husband_birth_place || '');
    fillFieldSafely('groom-civil-status', fields.groom_civil_status || fields.husband_civil_status || '');
    fillFieldSafely('groom-religion', fields.groom_religion || fields.husband_religion || '');
    fillFieldSafely('groom-residence', fields.groom_residence || fields.husband_residence || '');
    
    // Fill groom's parents
    fillFieldSafely('groom-father-name', fields.groom_father_name || fields.husband_father_name || '');
    fillFieldSafely('groom-mother-name', fields.groom_mother_name || fields.husband_mother_name || '');
    
    // BRIDE'S INFORMATION - Enhanced field mapping with multiple fallbacks
    const brideFirstName = fields.bride_first_name || fields.wife_first_name || fields.female_first_name || '';
    const brideMiddleName = fields.bride_middle_name || fields.wife_middle_name || fields.female_middle_name || '';
    const brideLastName = fields.bride_last_name || fields.wife_last_name || fields.female_last_name || '';
    
    console.log('Bride name mapping results:', {
        first: brideFirstName,
        middle: brideMiddleName,
        last: brideLastName
    });
    
    // Fill bride's name fields
    fillFieldSafely('bride-first-name', brideFirstName);
    fillFieldSafely('bride-middle-name', brideMiddleName);
    fillFieldSafely('bride-last-name', brideLastName);
    
    // Fill bride's details
    fillFieldSafely('bride-birth-date', fields.bride_birth_date || fields.wife_birth_date || '');
    fillFieldSafely('bride-citizenship', fields.bride_citizenship || fields.wife_citizenship || '');
    fillFieldSafely('bride-birth-place', fields.bride_birth_place || fields.wife_birth_place || '');
    fillFieldSafely('bride-civil-status', fields.bride_civil_status || fields.wife_civil_status || '');
    fillFieldSafely('bride-religion', fields.bride_religion || fields.wife_religion || '');
    fillFieldSafely('bride-residence', fields.bride_residence || fields.wife_residence || '');
    
    // Fill bride's parents
    fillFieldSafely('bride-father-name', fields.bride_father_name || fields.wife_father_name || '');
    fillFieldSafely('bride-mother-name', fields.bride_mother_name || fields.wife_mother_name || '');
    
    // MARRIAGE DETAILS
    fillFieldSafely('marriage-date', fields.marriage_date || fields.wedding_date || '');
    fillFieldSafely('marriage-place-city', fields.marriage_place_city || fields.wedding_place_city || '');
    fillFieldSafely('marriage-place-province', fields.marriage_place_province || fields.wedding_place_province || '');
    fillFieldSafely('marriage-officiant', fields.marriage_officiant || fields.officiant_name || fields.solemnized_by || '');
    fillFieldSafely('marriage-officiant-position', fields.marriage_officiant_position || fields.officiant_title || '');
    fillFieldSafely('marriage-license-number', fields.marriage_license_number || fields.license_number || '');
    
    // WITNESSES
    fillFieldSafely('marriage-witness-1', fields.witness_1_name || fields.witness_1 || '');
    fillFieldSafely('marriage-witness-2', fields.witness_2_name || fields.witness_2 || '');
    
    // REGISTRY INFORMATION
    fillFieldSafely('marriage-registry-number', fields.marriage_registry_number || fields.registry_number || '');
    fillFieldSafely('marriage-book-number', fields.marriage_book_number || fields.book_number || '');
    fillFieldSafely('marriage-page-number', fields.marriage_page_number || fields.page_number || '');
    fillFieldSafely('marriage-volume-number', fields.marriage_volume_number || fields.volume_number || '');
    
    console.log('=== END MARRIAGE CERTIFICATE FIELD MAPPING ===');
    console.log('Marriage certificate fields populated with enhanced mapping');
}

// NEW: Display generic fields
displayGenericFields(fields) {
    console.log('Displaying generic document fields:', fields);
    
    if (!fields) return;
    
    // Handle generic field display
    Object.keys(fields).forEach(key => {
        if (fields[key]) {
            this.fillField(key.replace('_', '-'), fields[key]);
        }
    });
}

// NEW: Fill individual field with visual feedback
fillField(fieldId, value) {
    const field = document.getElementById(fieldId);
    if (!field) return;

    const normalizedValue = value ? value.toString().trim() : '';
    field.value = normalizedValue;
    field.classList.remove('ocr-filled', 'ocr-edited', 'ocr-error');

    if (normalizedValue) {
        field.classList.add('ocr-filled');
        console.log(`Filled field ${fieldId}:`, normalizedValue);
    } else {
        field.classList.add('ocr-error');
        console.log(`Field ${fieldId} not filled - no data`);
    }

    if (!field.hasAttribute('data-ocr-listener-added')) {
        field.addEventListener('input', function() {
            const currentValue = (this.value || '').trim();
            this.classList.remove('ocr-filled', 'ocr-edited', 'ocr-error');

            if (currentValue) {
                this.classList.add('ocr-edited');
            } else {
                this.classList.add('ocr-error');
            }
        });

        field.setAttribute('data-ocr-listener-added', 'true');
    }
}

// NEW: Check radio button
checkRadio(radioId) {
    const radio = document.getElementById(radioId);
    if (radio) {
        radio.checked = true;
        console.log(`Radio ${radioId} checked`);
    }
}

// NEW: Display raw text
displayRawText(rawText) {
    const rawTextElement = document.getElementById('raw-ocr-text');
    if (rawTextElement) {
        rawTextElement.value = rawText;
        console.log('Raw OCR text displayed');
    }
}

// NEW: Switch to death certificate fields layout
switchToDeathCertificateFields() {
    const birthLayout = document.getElementById('birth-certificate-layout');
    const deathLayout = document.getElementById('death-certificate-layout');
    const marriageLayout = document.getElementById('marriage-certificate-layout');
    
    if (birthLayout) {
        birthLayout.classList.add('hidden');
        birthLayout.style.display = 'none';
    }
    
    if (deathLayout) {
        deathLayout.classList.remove('hidden');
        deathLayout.style.display = 'grid';
    }
    
    // ADDED: Hide marriage certificate layout
    if (marriageLayout) {
        marriageLayout.classList.add('hidden');
        marriageLayout.style.display = 'none';
        console.log('✓ Marriage certificate layout hidden');
    }
    
    console.log('Switched to death certificate horizontal layout (marriage layout hidden)');
}

switchToBirthCertificateFields() {
    const birthLayout = document.getElementById('birth-certificate-layout');
    const deathLayout = document.getElementById('death-certificate-layout');
    const marriageLayout = document.getElementById('marriage-certificate-layout');
    
    if (birthLayout) {
        birthLayout.classList.remove('hidden');
        birthLayout.style.display = 'grid';
    }
    
    if (deathLayout) {
        deathLayout.classList.add('hidden');
        deathLayout.style.display = 'none';
    }
    
    // ADDED: Hide marriage certificate layout
    if (marriageLayout) {
        marriageLayout.classList.add('hidden');
        marriageLayout.style.display = 'none';
        console.log('✓ Marriage certificate layout hidden');
    }
    
    console.log('Switched to birth certificate horizontal layout (marriage layout hidden)');
}

switchToMarriageCertificateFields() {
    const birthLayout = document.getElementById('birth-certificate-layout');
    const deathLayout = document.getElementById('death-certificate-layout');
    const marriageLayout = document.getElementById('marriage-certificate-layout');
    
    console.log('=== SWITCHING TO MARRIAGE CERTIFICATE LAYOUT (OCR PROCESSING ONLY) ===');
    console.log('Layout elements found:', {
        birth: !!birthLayout,
        death: !!deathLayout,
        marriage: !!marriageLayout
    });
    
    // Hide other certificate layouts
    if (birthLayout) {
        birthLayout.classList.add('hidden');
        birthLayout.style.display = 'none';
        console.log('✓ Birth certificate layout hidden');
    }
    
    if (deathLayout) {
        deathLayout.classList.add('hidden');
        deathLayout.style.display = 'none';
        console.log('✓ Death certificate layout hidden');
    }
    
    // Show marriage certificate layout ONLY during OCR processing
    if (marriageLayout) {
        marriageLayout.classList.remove('hidden');
        marriageLayout.style.display = 'grid';
        console.log('✓ Marriage certificate layout shown (OCR processing initiated)');
        
        // Show OCR results panel only during processing
        const ocrPanel = document.getElementById('ocr-results-panel');
        if (ocrPanel) {
            ocrPanel.classList.remove('hidden');
            console.log('✓ OCR results panel shown');
        }
    } else {
        console.error('✗ Marriage certificate layout not found in DOM');
    }
    
    console.log('Marriage certificate layout shown ONLY for OCR processing - not for document type selection');
}

displayRawText(rawText) {
    console.log('=== DISPLAY RAW TEXT DEBUG ===');
    console.log('Raw text to display:', rawText ? rawText.substring(0, 100) + '...' : 'No text');
    
    // Determine which layout is active
    const birthLayout = document.getElementById('birth-certificate-layout');
    const deathLayout = document.getElementById('death-certificate-layout');
    const marriageLayout = document.getElementById('marriage-certificate-layout');
    
    let rawTextElement, containerType;
    
    if (birthLayout && !birthLayout.classList.contains('hidden')) {
        rawTextElement = document.getElementById('raw-ocr-text-birth');
        containerType = 'birth';
        console.log('Using birth certificate raw text element');
    } else if (deathLayout && !deathLayout.classList.contains('hidden')) {
        rawTextElement = document.getElementById('raw-ocr-text-death');
        containerType = 'death';
        console.log('Using death certificate raw text element');
    } else if (marriageLayout && !marriageLayout.classList.contains('hidden')) {
        rawTextElement = document.getElementById('raw-ocr-text-marriage');
        containerType = 'marriage';
        console.log('Using marriage certificate raw text element');
    } else {
        // Fallback to birth certificate
        rawTextElement = document.getElementById('raw-ocr-text-birth');
        containerType = 'birth';
        console.log('Fallback to birth certificate raw text element');
    }
    
    if (rawTextElement && rawText) {
        rawTextElement.value = rawText;
        console.log('✓ Raw OCR text populated in:', containerType, 'field');
        
        // FIXED: Only populate the text, don't force show the container
        // The toggle button will handle visibility
        console.log('Raw text populated but container remains hidden until toggle button is clicked');
        
        // Add a subtle notification that raw text is available
        this.showNotification('Raw OCR text is available. Click "Show Raw OCR Text" to view.', 'info');
    } else {
        console.error('Raw text element not found or no text to display');
        console.error('Element:', rawTextElement);
        console.error('Text length:', rawText ? rawText.length : 0);
    }
    
    console.log('=== END DISPLAY RAW TEXT DEBUG ===');
}

// NEW: Generate document title based on extracted fields and type
generateDocumentTitle(fields, documentType) {
    switch (documentType) {
        case 'birth_certificate':
            if (fields.name_first || fields.name_last) {
                return `BIRTH CERTIFICATE - ${[fields.name_first, fields.name_middle, fields.name_last].filter(Boolean).join(' ')}`;
            }
            break;
            
        case 'death_certificate':
            if (fields.deceased_first_name || fields.deceased_last_name) {
                return `DEATH CERTIFICATE - ${[fields.deceased_first_name, fields.deceased_middle_name, fields.deceased_last_name].filter(Boolean).join(' ')}`;
            }
            break;
            
        case 'marriage_certificate':
            const groomName = [fields.groom_first_name || fields.husband_first_name, 
                            fields.groom_last_name || fields.husband_last_name].filter(Boolean).join(' ');
            const brideName = [fields.bride_first_name || fields.wife_first_name, 
                            fields.bride_last_name || fields.wife_last_name].filter(Boolean).join(' ');
            
            if (groomName || brideName) {
                return `MARRIAGE CERTIFICATE - ${[groomName, brideName].filter(Boolean).join(' & ')}`;
            }
            break;
            
        default:
            return `${documentType.replace('_', ' ').toUpperCase()} - ${new Date().toLocaleDateString()}`;
    }
    
    return null;
}

showApplyOCRButton() {
        const button = document.getElementById('apply-ocr');
        const documentType = document.getElementById('document-type')?.value || 'birth_certificate';
        if (!button) {
            console.error('Apply OCR button not found');
            return;
        }
        
        if (this.isOcrSupportedDocumentType(documentType) && this.hasImageFiles() && !this.isOCRProcessed()) {
            button.classList.remove('hidden');
            console.log('Apply OCR button shown');
        } else {
            button.classList.add('hidden');
            console.log('Apply OCR button hidden');
        }
    }

    hasImageFiles() {
        return this.currentFiles.some(file => file.type === 'image' || file.file?.type.startsWith('image/'));
    }

    isOCRProcessed() {
        return this.currentFiles.some(file => file.ocrProcessed);
    }

    async applyOCRToCurrentFile() {
        console.log('Apply OCR button clicked');

        const documentType = document.getElementById('document-type')?.value || 'birth_certificate';
        if (!this.isOcrSupportedDocumentType(documentType)) {
            this.showNotification('OCR is only available for Birth, Death, and Marriage Certificates.', 'info');
            return;
        }
        
        const imageFile = this.currentFiles.find(file => 
            (file.type === 'image' || file.file?.type.startsWith('image/')) && !file.ocrProcessed
        );
        
        if (!imageFile) {
            this.showNotification('No image file available for OCR processing', 'warning');
            return;
        }
        
        console.log('Processing OCR for file:', imageFile.name);
        await this.processFileOCR(imageFile);
    }

    async processFileOCR(fileObj) {
        const documentType = document.getElementById('document-type')?.value || 'birth_certificate';
        if (!this.isOcrSupportedDocumentType(documentType)) {
            this.showNotification('OCR is not required for this document type.', 'info');
            return;
        }

        if (!fileObj.file || !fileObj.file.type.startsWith('image/')) {
            this.showNotification('OCR only supports image files', 'warning');
            return;
        }

        console.log('Starting OCR processing for:', fileObj.name);
        this.showOCRProcessing();
        this.showProgress('Processing OCR for ' + fileObj.name, 'fas fa-text-width');

        try {
            const formData = new FormData();
            formData.append('file', fileObj.file);
            formData.append('_token', this.csrfToken);
            formData.append('document_type', documentType);

            console.log('Sending OCR request...');

            const response = await fetch('/staff/scan/ocr-process', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            });

            console.log('OCR Response received:', response.status, response.statusText);

            const data = await response.json();
            console.log('OCR Response data:', data);

            if (response.ok && data.success) {
                fileObj.ocrProcessed = true;
                fileObj.ocrData = data.ocr_results;
                this.ocrResults[fileObj.id] = data.ocr_results;
                
                this.displayOCRResults(data.ocr_results);
                this.hideProgress();
                this.showNotification('OCR processing completed successfully!', 'success');
                
                // Auto-fill document title from extracted data
                const titleField = document.getElementById('document-title');
                if (!titleField.value && data.ocr_results.extracted_fields) {
                    const fields = data.ocr_results.extracted_fields;
                    let titleName = '';
                    
                    if (fields.child_first_name || fields.child_last_name) {
                        titleName = [fields.child_first_name, fields.child_middle_name, fields.child_last_name]
                            .filter(Boolean).join(' ');
                    }
                    
                    if (titleName) {
                        const docType = data.ocr_results.document_type.replace('_', ' ').toUpperCase();
                        titleField.value = `${docType} - ${titleName}`;
                    }
                }
                
                this.updateFileList();
                this.showApplyOCRButton();
            } else {
                throw new Error(data.message || `HTTP ${response.status}: ${response.statusText}`);
            }

        } catch (error) {
            this.hideProgress();
            console.error('OCR Error:', error);
            
            let errorMessage = 'OCR processing failed: ' + error.message;
            this.showNotification(errorMessage, 'error');
            this.showOCRError(errorMessage);
        }
    }

    showOCRError(errorMessage) {
    const panel = document.getElementById('ocr-results-panel');
    if (panel) {
        panel.classList.remove('hidden');
        
        const status = document.getElementById('ocr-status');
        if (status) {
            status.className = 'px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800';
            status.innerHTML = '<i class="fas fa-times-circle mr-1"></i>Processing Failed';
        }
        
        // Clear previous results
        const confidenceEl = document.getElementById('confidence-score');
        const wordsEl = document.getElementById('words-extracted');
        const fieldsEl = document.getElementById('fields-detected');
        const rawTextEl = document.getElementById('raw-ocr-text');
        
        if (confidenceEl) confidenceEl.textContent = '0%';
        if (wordsEl) wordsEl.textContent = '0';
        if (fieldsEl) fieldsEl.textContent = '0';
        if (rawTextEl) rawTextEl.value = 'OCR Error: ' + errorMessage;
        
        // UPDATED: Hide extraction status and progress elements
        this.hideExtractionStatus();
        this.hideProgress();
        
        // UPDATED: Clear extracted fields - aligned with certificate structure
        const fieldIds = [
            'name-first', 'name-middle', 'name-last', 'sex-detected',
            'birth-date-day', 'birth-date-month', 'birth-date-year',
            'birth-place-institution', 'birth-place-city', 'birth-place-province',
            'mother-first-name', 'mother-middle-name', 'mother-last-name',
            'father-first-name', 'father-middle-name', 'father-last-name',
            'registry-number', 'bren-number', 'citizenship', 'religion',
            'deceased-first-name', 'deceased-middle-name', 'deceased-last-name',
            'deceased-sex', 'deceased-age', 'death-date-day', 'death-date-month',
            'death-date-year', 'cause-of-death', 'registry-number'
        ];
        
        fieldIds.forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.value = '';
                element.classList.remove('ocr-filled', 'ocr-edited', 'ocr-error');
            }
        });
    }
}



    displayOCRResults(results) {
        console.log('Displaying enhanced OCR results with editable fields:', results);
        
        const panel = document.getElementById('ocr-results-panel');
        if (!panel) {
            console.error('OCR results panel not found');
            return;
        }
        
        panel.classList.remove('hidden');
        
        // Update metrics
        const confidenceEl = document.getElementById('confidence-score');
        const wordsEl = document.getElementById('words-extracted');
        const fieldsEl = document.getElementById('fields-detected');
        
        if (confidenceEl) confidenceEl.textContent = (results.confidence || 0) + '%';
        if (wordsEl) wordsEl.textContent = results.word_count || 0;
        if (fieldsEl) fieldsEl.textContent = this.countFilledFields(results.extracted_fields) || 0;
        
        // Update status with better color coding
        const status = document.getElementById('ocr-status');
        if (status) {
            const confidence = results.confidence || 0;
            if (confidence >= 80) {
                status.className = 'px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800';
                status.innerHTML = '<i class="fas fa-check-circle mr-1"></i>Excellent Quality';
            } else if (confidence >= 60) {
                status.className = 'px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800';
                status.innerHTML = '<i class="fas fa-exclamation-triangle mr-1"></i>Good Quality';
            } else {
                status.className = 'px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800';
                status.innerHTML = '<i class="fas fa-times-circle mr-1"></i>Low Quality - Manual Review Needed';
            }
        }
        
        // Populate fields with extracted data
        const fields = results.extracted_fields || {};
        
        // Helper function to set input values with visual feedback
        const setInputValue = (id, value, confidence = 100) => {
            const element = document.getElementById(id);
            if (element) {
                element.value = value || '';
                
                // Enhanced visual feedback based on field confidence
                this.updateFieldVisualState(element, value, confidence);
                
                // Add change listener for manual edits
                if (!element.hasAttribute('data-listener-added')) {
                    element.addEventListener('input', (e) => this.handleFieldEdit(e));
                    element.setAttribute('data-listener-added', 'true');
                }
            }
        };
        
        // 1. NAME Section
        setInputValue('name-first', fields.name_first, this.getFieldConfidence(fields.name_first));
        setInputValue('name-middle', fields.name_middle, this.getFieldConfidence(fields.name_middle));
        setInputValue('name-last', fields.name_last, this.getFieldConfidence(fields.name_last));
        
        // 2. SEX Section
        setInputValue('sex-detected', fields.sex);
        // Update radio buttons
        if (fields.sex) {
            const sexLower = fields.sex.toLowerCase();
            const maleRadio = document.getElementById('sex-male');
            const femaleRadio = document.getElementById('sex-female');
            
            if (sexLower.includes('male') && !sexLower.includes('female')) {
                if (maleRadio) maleRadio.checked = true;
                if (femaleRadio) femaleRadio.checked = false;
            } else if (sexLower.includes('female')) {
                if (femaleRadio) femaleRadio.checked = true;
                if (maleRadio) maleRadio.checked = false;
            }
        }
        
        // 3. DATE OF BIRTH Section
        setInputValue('birth-date-day', fields.birth_date_day);
        setInputValue('birth-date-month', fields.birth_date_month);
        setInputValue('birth-date-year', fields.birth_date_year);
        
        // 4. PLACE OF BIRTH Section
        setInputValue('birth-place-institution', fields.birth_place_institution);
        setInputValue('birth-place-city', fields.birth_place_city);
        setInputValue('birth-place-province', fields.birth_place_province);
        
        // 5. MOTHER'S MAIDEN NAME Section
        setInputValue('mother-first-name', fields.mother_first_name);
        setInputValue('mother-middle-name', fields.mother_middle_name);
        setInputValue('mother-last-name', fields.mother_last_name);
        
        // 6. FATHER'S NAME Section
        setInputValue('father-first-name', fields.father_first_name);
        setInputValue('father-middle-name', fields.father_middle_name);
        setInputValue('father-last-name', fields.father_last_name);
        
        // Registry Information
        setInputValue('registry-number', fields.registry_number);
        setInputValue('bren-number', fields.bren_number);
        
        // Additional Information
        setInputValue('citizenship', fields.citizenship);
        setInputValue('religion', fields.religion);
        
        // Raw text
        setInputValue('raw-ocr-text', results.raw_text);
        
        // Setup edit mode functionality
        this.setupEditMode();
        
        // Show helpful message for low confidence results
        if ((results.confidence || 0) < 70) {
            this.showNotification('OCR confidence is low. Please review and correct the extracted fields manually.', 'warning');
        }
        
        console.log('Enhanced OCR results displayed with editable fields');
        this.showSectionExtractionStatus(fields);
    }

    updateFieldVisualState(element, value, confidence = 100) {
        // Remove all existing state classes
        element.classList.remove('ocr-filled', 'ocr-edited', 'ocr-error');

        if (value && value.trim()) {
            element.classList.add('ocr-filled');
        } else {
            element.classList.add('ocr-error');
        }
    }

    getFieldConfidence(value) {
        if (!value || !value.trim()) return 0;
        
        let confidence = 50; // Base confidence
        
        // Length-based confidence
        if (value.length >= 3) confidence += 20;
        if (value.length >= 6) confidence += 10;
        
        // Pattern-based confidence
        if (/^[A-Z][a-z]+$/.test(value)) confidence += 20; // Proper capitalization
        if (!/[0-9]/.test(value) && value.includes(' ')) confidence += 10; // No numbers in names
        
        return Math.min(100, confidence);
    }

    // Handle manual field edits
    handleFieldEdit(event) {
        const field = event.target;
        const value = field.value;
        
        // Mark field as manually edited
        field.setAttribute('data-manually-edited', 'true');
        
        // Update visual state for edited fields
        field.classList.remove('ocr-filled', 'ocr-edited', 'ocr-error');
        
        if (value && value.trim()) {
            field.classList.add('ocr-edited');
        } else {
            field.classList.add('ocr-error');
        }
        
        console.log('Field manually edited:', field.id, 'New value:', value);
    }

    // FIXED: Setup edit mode functionality with proper button handlers
    setupEditMode() {
    console.log('Setting up OCR action buttons with enhanced binding...');
    
    // Force delay to ensure DOM is ready and OCR panel is visible
    setTimeout(() => {
        // Button configurations with proper handlers
        const buttonConfigs = [
            { id: 'copy-text-btn', handler: () => this.copyOCRTextEnhanced() },
            { id: 'export-data-btn', handler: () => this.exportOCRData() },
            { id: 'reprocess-btn', handler: () => this.reprocessOCR() },
            { id: 'save-corrections-btn', handler: () => this.saveCorrections() },
            { id: 'reset-fields-btn', handler: () => this.resetFields() },
            { id: 'toggle-edit-mode-btn', handler: () => this.toggleEditMode() }
        ];
        
        buttonConfigs.forEach(config => {
            const button = document.getElementById(config.id);
            if (button) {
                // Remove any existing event listeners by cloning
                const newButton = button.cloneNode(true);
                button.parentNode.replaceChild(newButton, button);
                
                // Add multiple event types for better compatibility
                ['click', 'touchend'].forEach(eventType => {
                    newButton.addEventListener(eventType, (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        console.log(`OCR Action triggered: ${config.id} via ${eventType}`);
                        
                        // Add visual feedback
                        newButton.style.transform = 'scale(0.95)';
                        setTimeout(() => {
                            newButton.style.transform = '';
                        }, 150);
                        
                        try {
                            config.handler();
                        } catch (error) {
                            console.error(`Error in ${config.id} handler:`, error);
                            this.showNotification(`Action failed: ${error.message}`, 'error');
                        }
                    }, { passive: false });
                });
                
                // Add hover effects
                newButton.addEventListener('mouseenter', () => {
                    newButton.style.transform = 'scale(1.02)';
                });
                
                newButton.addEventListener('mouseleave', () => {
                    newButton.style.transform = '';
                });
                
                console.log(`✓ Enhanced event listeners bound to: ${config.id}`);
            } else {
                console.error(`✗ Button not found: ${config.id}`);
            }
        });
        
        // Setup radio button listeners for sex field
        const sexRadios = document.querySelectorAll('input[name="detected-sex"]');
        sexRadios.forEach(radio => {
            radio.addEventListener('change', (e) => {
                const sexDetectedField = document.getElementById('sex-detected');
                if (sexDetectedField) {
                    sexDetectedField.value = e.target.value;
                    this.handleFieldEdit({ target: sexDetectedField });
                }
            });
        });
        
        console.log('✓ OCR action buttons setup completed with enhanced binding and error handling');
    }, 500); // Increased delay to ensure OCR panel is fully rendered
}

copyOCRTextEnhanced() {
    console.log('Copy OCR text function called');
    
    // Try to get raw text from the appropriate certificate type
    const birthLayout = document.getElementById('birth-certificate-layout');
    const deathLayout = document.getElementById('death-certificate-layout');
    
    let textArea;
    if (birthLayout && !birthLayout.classList.contains('hidden')) {
        textArea = document.getElementById('raw-ocr-text-birth');
    } else if (deathLayout && !deathLayout.classList.contains('hidden')) {
        textArea = document.getElementById('raw-ocr-text-death');
    } else {
        // Fallback to generic or first available
        textArea = document.getElementById('raw-ocr-text-birth') || document.getElementById('raw-ocr-text-death');
    }
    
    if (!textArea || !textArea.value.trim()) {
        this.showNotification('No OCR text available to copy', 'warning');
        return;
    }
    
    // Modern clipboard API with fallback
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(textArea.value).then(() => {
            this.showNotification('OCR text copied to clipboard!', 'success');
            console.log('✓ Text copied using modern clipboard API');
        }).catch(err => {
            console.error('Modern clipboard API failed:', err);
            this.fallbackCopyText(textArea);
        });
    } else {
        this.fallbackCopyText(textArea);
    }
}

// Fallback copy method
fallbackCopyText(textArea) {
    try {
        textArea.select();
        textArea.setSelectionRange(0, 99999); // For mobile devices
        const successful = document.execCommand('copy');
        
        if (successful) {
            this.showNotification('OCR text copied to clipboard!', 'success');
            console.log('✓ Text copied using fallback method');
        } else {
            throw new Error('Copy command failed');
        }
    } catch (err) {
        console.error('Fallback copy failed:', err);
        this.showNotification('Failed to copy text. Please select and copy manually.', 'error');
    }
}


    // Toggle edit mode (currently all fields are editable by default)
    toggleEditMode() {
        const button = document.getElementById('toggle-edit-mode-btn');
        const firstField = document.getElementById('name-first');
        const isReadOnly = firstField && firstField.hasAttribute('readonly');
        
        // Toggle readonly state for all OCR fields
        const ocrFields = [
            'name-first', 'name-middle', 'name-last', 'sex-detected',
            'birth-date-day', 'birth-date-month', 'birth-date-year',
            'birth-place-institution', 'birth-place-city', 'birth-place-province',
            'mother-first-name', 'mother-middle-name', 'mother-last-name',
            'father-first-name', 'father-middle-name', 'father-last-name',
            'registry-number', 'bren-number', 'citizenship', 'religion', 'raw-ocr-text'
        ];
        
        ocrFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                if (isReadOnly) {
                    field.removeAttribute('readonly');
                    field.classList.remove('bg-gray-50');
                } else {
                    field.setAttribute('readonly', true);
                    field.classList.add('bg-gray-50');
                }
            }
        });
        
        // Toggle radio buttons
        const sexRadios = document.querySelectorAll('input[name="detected-sex"]');
        sexRadios.forEach(radio => {
            radio.disabled = !isReadOnly;
        });
        
        // Update button text and icon
        if (button) {
            if (isReadOnly) {
                button.innerHTML = '<i class="fas fa-lock mr-2"></i>Lock Fields';
                this.showNotification('Fields are now editable. You can make corrections manually.', 'info');
            } else {
                button.innerHTML = '<i class="fas fa-edit mr-2"></i>Edit Mode';
                this.showNotification('Fields are now locked to prevent accidental changes.', 'info');
            }
        }
    }

// Save corrections to OCR data
saveCorrections() {
    console.log('=== SAVE CORRECTIONS TO DATABASE (ENHANCED) ===');
    
    const correctedData = this.collectOCRData();
    const documentType = document.getElementById('document-type').value;
    const documentTitle = document.getElementById('document-title')?.value || this.generateDefaultTitle(correctedData, documentType);
    
    console.log('Save initiation:', {
        documentType: documentType,
        title: documentTitle,
        fieldsCount: Object.keys(correctedData).length,
        nonEmptyFields: Object.values(correctedData).filter(v => v && v.toString().trim() !== '').length
    });
    
    if (!documentType) {
        this.showNotification('Please select a document type before saving', 'error');
        return;
    }
    
    // CLIENT-SIDE VALIDATION: Check for required fields
    const requiredFieldsByType = {
        birth_certificate: [
            'name_first', 'name_last', 'birth_date_day', 'birth_date_month', 
            'birth_date_year', 'mother_first_name', 'mother_last_name', 
            'father_first_name', 'father_last_name'
        ],
        death_certificate: [
            'deceased_first_name', 'deceased_last_name', 'death_date_day',
            'death_date_month', 'death_date_year', 'cause_of_death'
        ],
        marriage_certificate: [
            'groom_first_name', 'groom_last_name', 'bride_first_name', 
            'bride_last_name', 'marriage_date'
        ],
    };
    
    const requiredFields = requiredFieldsByType[documentType] || [];
    const missingFields = requiredFields.filter(field => {
        const value = correctedData[field];
        const isEmpty = !value || value.toString().trim() === '';
        if (isEmpty) {
            console.warn(`❌ Required field missing: ${field}`);
        }
        return isEmpty;
    });
    
    if (missingFields.length > 0) {
    const fieldNames = missingFields.map(f => 
        f.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
    ).join(', ');
    
    console.error('CLIENT-SIDE VALIDATION FAILED:', {
        missingFields: missingFields,
        collectedData: correctedData,
        requiredFields: requiredFields
    });
    
    this.showNotification(
        `⚠️ Missing Required Fields:\n\n${fieldNames}\n\n` +
        `Please fill all required fields before saving.\n\n` +
        `Check browser console (F12) for detailed field status.`,
        'error'
    );
    
    // ENHANCED: Highlight missing fields with better visual feedback
    missingFields.forEach(field => {
        const fieldId = field.replace(/_/g, '-');
        const element = document.getElementById(fieldId);
        
        if (element) {
            // Add red border and background
            element.classList.add('border-red-500', 'bg-red-50', 'border-2');
            element.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.3)';
            
            // Scroll to first missing field
            if (missingFields.indexOf(field) === 0) {
                element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                element.focus();
            }
            
            // Add tooltip
            const tooltip = document.createElement('div');
            tooltip.className = 'absolute z-50 bg-red-600 text-white text-xs px-2 py-1 rounded mt-1';
            tooltip.textContent = 'This field is required';
            tooltip.style.position = 'absolute';
            tooltip.style.top = (element.offsetTop + element.offsetHeight) + 'px';
            tooltip.style.left = element.offsetLeft + 'px';
            
            element.parentElement.style.position = 'relative';
            element.parentElement.appendChild(tooltip);
            
            // Remove highlights and tooltip on input
            element.addEventListener('input', function handler() {
                this.classList.remove('border-red-500', 'bg-red-50', 'border-2');
                this.style.boxShadow = '';
                
                if (tooltip && tooltip.parentElement) {
                    tooltip.remove();
                }
                
                this.removeEventListener('input', handler);
            });
            
            // Auto-remove tooltip after 5 seconds
            setTimeout(() => {
                if (tooltip && tooltip.parentElement) {
                    tooltip.remove();
                }
            }, 5000);
        } else {
            console.error(`Cannot highlight field ${fieldId} - element not found in DOM`);
        }
    });
    
    return;
}
    
    console.log('✅ CLIENT-SIDE VALIDATION PASSED');
    
    // Get current image file
    const imageFile = this.currentFiles.find(file => 
        (file.type === 'image' || file.file?.type.startsWith('image/'))
    );
    
    if (!imageFile || !imageFile.file) {
        this.showNotification('No image file available to save', 'error');
        return;
    }
    
    // Show saving progress
    this.showNotification('Saving document to database...', 'info');
    this.showProgress('Saving document with OCR data...', 'fas fa-save');
    
    // Prepare FormData for DocumentService integration
    const formData = new FormData();
    formData.append('_token', this.csrfToken);
    
    // Document metadata
    formData.append('document_type', documentType);
    formData.append('title', documentTitle);
    formData.append('description', 'Document processed via OCR with manual corrections');
    
    // CRITICAL: Send extracted fields as JSON string
    formData.append('extracted_fields', JSON.stringify(correctedData));
    formData.append('ocr_confidence', correctedData.confidence || 0);
    formData.append('raw_ocr_text', correctedData.raw_text || '');
    formData.append('processing_time', Date.now() - (this.processingStartTime || Date.now()));
    formData.append('manually_corrected', this.hasManualCorrections());
    
    // Image file
    formData.append('file', imageFile.file);
    
    console.log('Sending save request with:', {
        document_type: documentType,
        title: documentTitle,
        extracted_fields_length: JSON.stringify(correctedData).length,
        has_file: !!imageFile.file
    });
    
    // Send to DocumentService endpoint
    fetch('/staff/scan/save-document', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': this.csrfToken,
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(response => {
        console.log('Save response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Save response data:', data);
        
        if (data.success) {
            this.hideProgress();
            
            // SUCCESS: Document saved to database
            this.showNotification(`✅ Document saved successfully!\n\nDocument ID: ${data.document_id}\nValidation Score: ${data.validation_score || 'N/A'}%\n\nRedirecting to dashboard...`, 'success');
            
            // Mark current file as saved
            if (imageFile) {
                imageFile.savedToDatabase = true;
                imageFile.databaseId = data.database_id;
                imageFile.documentId = data.document_id;
            }
            
            // Log success details
            console.log('✅ DOCUMENT SAVED SUCCESSFULLY:', {
                documentId: data.document_id,
                databaseId: data.database_id,
                verificationStatus: data.verification_status,
                validationScore: data.validation_score
            });
            
            // Redirect to dashboard after 2 seconds
            setTimeout(() => {
                if (data.redirect_url) {
                    window.location.href = data.redirect_url;
                } else {
                    window.location.href = '/staff/dashboard?updated=1';
                }
            }, 2000);
            
        } else {
            this.hideProgress();
            
            // SERVER-SIDE VALIDATION FAILED
            console.error('❌ SERVER-SIDE VALIDATION FAILED:', data);
            
            let errorMessage = 'Save failed: ' + (data.message || 'Unknown error');
            
            if (data.errors && data.errors.length > 0) {
                errorMessage += '\n\nErrors:\n' + data.errors.join('\n');
            }
            
            if (data.validation_details) {
                errorMessage += '\n\nValidation Details:';
                errorMessage += `\nManual Completion: ${data.validation_details.manual_completion_score}%`;
                errorMessage += `\nValidation Score: ${data.validation_details.validation_score}%`;
                errorMessage += `\nRequired Threshold: ${data.validation_details.required_threshold}%`;
                
                if (data.validation_details.missing_fields) {
                    errorMessage += '\n\nMissing Fields:';
                    errorMessage += '\n' + data.validation_details.missing_fields.map(f => 
                        f.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
                    ).join(', ');
                }
            }
            
            this.showNotification(errorMessage, 'error');
        }
    })
    .catch(error => {
        this.hideProgress();
        console.error('❌ NETWORK ERROR:', error);
        this.showNotification('Network error occurred while saving: ' + error.message, 'error');
    });
}

    // FIXED: Enhanced reprocess function
    reprocessOCR() {
        const currentFile = this.currentFiles.find(f => f.ocrProcessed);
        if (currentFile) {
            // Reset OCR status
            currentFile.ocrProcessed = false;
            delete currentFile.ocrData;
            
            // Clear existing results
            this.clearOCRFields();
            
            // Show processing status
            this.showNotification('Reprocessing OCR...', 'info');
            
            // Restart OCR
            this.processFileOCR(currentFile);
        } else {
            this.showNotification('No file available for reprocessing', 'warning');
        }
    }

generateDefaultTitle(correctedData, documentType) {
    const timestamp = new Date().toLocaleDateString();
    
    switch (documentType) {
        case 'birth_certificate':
            const birthName = [correctedData.name_first, correctedData.name_last].filter(Boolean).join(' ');
            return birthName ? `Birth Certificate - ${birthName}` : `Birth Certificate - ${timestamp}`;
            
        case 'death_certificate':
            const deathName = [correctedData.deceased_first_name, correctedData.deceased_last_name].filter(Boolean).join(' ');
            return deathName ? `Death Certificate - ${deathName}` : `Death Certificate - ${timestamp}`;
            
        case 'marriage_certificate':
            const groomName = [correctedData.groom_first_name, correctedData.groom_last_name].filter(Boolean).join(' ');
            const brideName = [correctedData.bride_first_name, correctedData.bride_last_name].filter(Boolean).join(' ');
            const couple = [groomName, brideName].filter(Boolean).join(' & ');
            return couple ? `Marriage Certificate - ${couple}` : `Marriage Certificate - ${timestamp}`;
            
        default:
            return `${documentType.replace('_', ' ').toUpperCase()} - ${timestamp}`;
    }
}

// Helper method to check for manual corrections
hasManualCorrections() {
    // Check if any fields have been manually edited
    const ocrFields = document.querySelectorAll('.field-box');
    return Array.from(ocrFields).some(field => field.hasAttribute('data-manually-edited'));
}

    // FIXED: Helper to count corrections
    getCorrectionsCount() {
        const ocrFields = [
            'name-first', 'name-middle', 'name-last', 'sex-detected',
            'birth-date-day', 'birth-date-month', 'birth-date-year',
            'birth-place-institution', 'birth-place-city', 'birth-place-province',
            'mother-first-name', 'mother-middle-name', 'mother-last-name',
            'father-first-name', 'father-middle-name', 'father-last-name',
            'registry-number', 'bren-number', 'citizenship', 'religion'
        ];
        
        return ocrFields.filter(fieldId => {
            const field = document.getElementById(fieldId);
            return field && field.hasAttribute('data-manually-edited');
        }).length;
    }

    // FIXED: Helper to clear OCR fields
    clearOCRFields() {
        const ocrFields = [
            'name-first', 'name-middle', 'name-last', 'sex-detected',
            'birth-date-day', 'birth-date-month', 'birth-date-year',
            'birth-place-institution', 'birth-place-city', 'birth-place-province',
            'mother-first-name', 'mother-middle-name', 'mother-last-name',
            'father-first-name', 'father-middle-name', 'father-last-name',
            'registry-number', 'bren-number', 'citizenship', 'religion', 'raw-ocr-text'
        ];
        
        ocrFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.value = '';
                field.removeAttribute('data-manually-edited');
                field.classList.remove('ocr-filled', 'ocr-edited', 'ocr-error');
            }
        });
        
        // Clear radio buttons
        document.querySelectorAll('input[name="detected-sex"]').forEach(radio => {
            radio.checked = false;
        });
    }

    showSectionExtractionStatus(fields) {
        const sections = [
            { name: 'NAME', fields: ['name_first', 'name_middle', 'name_last'] },
            { name: 'SEX', fields: ['sex'] },
            { name: 'DATE OF BIRTH', fields: ['birth_date_day', 'birth_date_month', 'birth_date_year'] },
            { name: 'PLACE OF BIRTH', fields: ['birth_place_institution', 'birth_place_city', 'birth_place_province'] },
            { name: 'MOTHER\'S NAME', fields: ['mother_first_name', 'mother_middle_name', 'mother_last_name'] },
            { name: 'FATHER\'S NAME', fields: ['father_first_name', 'father_middle_name', 'father_last_name'] }
        ];
        
        sections.forEach(section => {
            const filledFields = section.fields.filter(field => fields[field] && fields[field].trim()).length;
            const totalFields = section.fields.length;
            const percentage = Math.round((filledFields / totalFields) * 100);
            
            console.log(`Section ${section.name}: ${filledFields}/${totalFields} fields (${percentage}%)`);
        });
    }

    countFilledFields(fields) {
        if (!fields) return 0;
        return Object.values(fields).filter(value => value && value.toString().trim().length > 0).length;
    }

    toggleRawText(type = 'birth') {
    const container = document.getElementById(`raw-text-container-${type}`);
    const button = document.getElementById(`toggle-raw-text-${type}`);
    
    if (!container || !button) {
        console.error(`Raw text toggle elements not found for type: ${type}`);
        return;
    }
    
    const icon = button.querySelector('i.fa-chevron-right, i.fa-chevron-down');
    const isHidden = container.classList.contains('hidden');
    
    console.log(`Toggling raw text for ${type}, currently hidden: ${isHidden}`);
    
    if (isHidden) {
        // Show the container
        container.classList.remove('hidden');
        
        // Update icon
        if (icon) {
            icon.classList.remove('fa-chevron-right');
            icon.classList.add('fa-chevron-down');
        }
        
        // Update button text while preserving the icon
        const iconHTML = icon ? icon.outerHTML : '<i class="fas fa-chevron-down mr-2 transition-transform duration-200"></i>';
        button.innerHTML = `${iconHTML}Hide Raw OCR Text`;
        
        console.log(`✓ Raw OCR text shown for ${type} certificate`);
    } else {
        // Hide the container
        container.classList.add('hidden');
        
        // Update icon
        if (icon) {
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-right');
        }
        
        // Update button text while preserving the icon
        const iconHTML = icon ? icon.outerHTML : '<i class="fas fa-chevron-right mr-2 transition-transform duration-200"></i>';
        button.innerHTML = `${iconHTML}Show Raw OCR Text`;
        
        console.log(`✓ Raw OCR text hidden for ${type} certificate`);
    }
}

    showOCRProcessing() {
        const panel = document.getElementById('ocr-results-panel');
        if (panel) {
            panel.classList.remove('hidden');
            
            const status = document.getElementById('ocr-status');
            if (status) {
                status.className = 'px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800';
                status.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Processing...';
            }
        }
    }

    handleOCRToggle(event) {
        if (event.target.checked) {
            this.showNotification('OCR enabled - files will be automatically processed', 'info');
            
            // Process existing unprocessed image files
            this.currentFiles.forEach(file => {
                if (!file.ocrProcessed && (file.type === 'image' || file.file?.type.startsWith('image/'))) {
                    this.processFileOCR(file);
                }
            });
        } else {
            this.showNotification('OCR disabled', 'info');
        }
    }

    showPreview(path, name, type) {
        console.log('Showing preview:', { path, name, type });

        const emptyState    = document.getElementById('empty-state');
        const previewContent = document.getElementById('preview-content');

        if (!emptyState || !previewContent) {
            console.error('Preview elements not found');
            return;
        }

        // Hide empty state, reveal preview area
        emptyState.classList.add('hidden');
        emptyState.style.display = 'none';
        previewContent.classList.remove('hidden');
        previewContent.style.display = '';

        let content = '';
        if (type === 'image' || type.startsWith('image/')) {
            // Image scales and stays centered within preview container.
            content = `
                <img src="${path}" alt="${name}">
            `;
        } else if (type === 'pdf' || type === 'application/pdf') {
            content = `
                <div style="display:flex; flex-direction:column; align-items:center;
                            text-align:center; padding:24px; background:#fef2f2;
                            border:1px solid #fecaca; border-radius:8px;
                            width:100%; box-sizing:border-box; flex-shrink:0;">
                    <i class="fas fa-file-pdf" style="font-size:4rem; color:#ef4444; margin-bottom:12px;"></i>
                    <p style="color:#374151; font-weight:600; overflow:hidden;
                              text-overflow:ellipsis; white-space:nowrap; width:100%;">${name}</p>
                    <p style="color:#6b7280; font-size:0.875rem; margin-top:6px;">PDF preview not available</p>
                    <a href="${path}" target="_blank"
                       style="display:inline-block; margin-top:14px; padding:8px 16px;
                              background:#dc2626; color:#fff; border-radius:8px;
                              text-decoration:none; font-size:0.875rem;">
                        <i class="fas fa-external-link-alt" style="margin-right:6px;"></i>Open PDF
                    </a>
                </div>
            `;
        }

        previewContent.innerHTML = content;
        console.log('Preview content updated');

        // Show Apply OCR button if image file
        if (type === 'image' || type.startsWith('image/')) {
            this.showApplyOCRButton();
        }
    }

    updateFileList() {
        const fileListContainer = document.getElementById('file-list');
        const filesContainer = document.getElementById('files-container');
        
        if (!fileListContainer || !filesContainer) {
            console.error('File list elements not found');
            return;
        }
        
        if (this.currentFiles.length > 0) {
            fileListContainer.classList.remove('hidden');
            
            filesContainer.innerHTML = this.currentFiles.map((file, index) => {
                const ocrBadge = file.ocrProcessed ? 
                    '<span class="px-2 py-0.5 text-xs bg-green-100 text-green-700 rounded">OCR ✓</span>' : 
                    (document.getElementById('ocr-enabled')?.checked ? 
                        '<span class="px-2 py-0.5 text-xs bg-yellow-100 text-yellow-700 rounded">OCR Pending</span>' : 
                        '');
                
                return `
                    <div class="file-row">
                        <div class="file-info">
                            <i class="fas ${file.type === 'pdf' ? 'fa-file-pdf text-red-500' : 'fa-file-image text-blue-500'}"></i>
                            <span class="file-name">${file.name}</span>
                            <span class="file-size">${file.size} ${file.isScanned ? '• Scanned' : '• Uploaded'}</span>
                            ${ocrBadge}
                        </div>
                        <div class="file-actions">
                            <button onclick="documentScanner.previewFile(${index})" class="px-3 py-1 text-xs bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition-colors">
                                <i class="fas fa-eye mr-1"></i>Preview
                            </button>
                            ${file.type === 'image' && !file.ocrProcessed && this.isOcrSupportedDocumentType(document.getElementById('document-type')?.value || 'birth_certificate') ? 
                                `<button onclick="documentScanner.processFileOCR(documentScanner.currentFiles[${index}])" class="px-3 py-1 text-xs bg-green-100 text-green-700 rounded hover:bg-green-200 transition-colors">
                                    <i class="fas fa-text-width mr-1"></i>OCR
                                </button>` : ''}
                            <button onclick="documentScanner.removeFile(${index})" class="px-3 py-1 text-xs bg-red-100 text-red-700 rounded hover:bg-red-200 transition-colors">
                                <i class="fas fa-trash mr-1"></i>Remove
                            </button>
                        </div>
                    </div>
                `;
            }).join('');
        } else {
            fileListContainer.classList.add('hidden');
        }
        
        // Update save button state
        const saveButton = document.getElementById('save-document');
        if (saveButton) {
            saveButton.disabled = this.currentFiles.length === 0;
        }
    }

    previewFile(index) {
        const file = this.currentFiles[index];
        if (file) {
            this.showPreview(file.path, file.name, file.type);
            
            // Show OCR results if available
            if (file.ocrProcessed && file.ocrData) {
                this.displayOCRResults(file.ocrData);
            }
        }
    }

    removeFile(index) {
        const file = this.currentFiles[index];
        
        if (file) {
            // Revoke object URL if it was created for uploaded files
            if (file.path && file.path.startsWith('blob:')) {
                URL.revokeObjectURL(file.path);
            }
            
            // Remove OCR data
            if (file.id && this.ocrResults[file.id]) {
                delete this.ocrResults[file.id];
            }
            
            this.currentFiles.splice(index, 1);
            this.updateFileList();
            
            if (this.currentFiles.length === 0) {
                this.resetPreviewState();
                this.hideDocumentDetails();
            }
            
            // Update Apply OCR button visibility
            this.showApplyOCRButton();
            
            this.showNotification('File removed successfully', 'success');
        }
    }

    resetPreviewState() {
        const emptyState = document.getElementById('empty-state');
        const previewContent = document.getElementById('preview-content');
        const ocrPanel = document.getElementById('ocr-results-panel');
        const applyOcrBtn = document.getElementById('apply-ocr');

        if (previewContent) {
            previewContent.innerHTML = '';
            previewContent.classList.add('hidden');
            previewContent.style.display = 'none';
        }

        if (emptyState) {
            emptyState.classList.remove('hidden');
            emptyState.style.display = '';
        }

        if (ocrPanel) {
            ocrPanel.classList.add('hidden');
        }

        if (applyOcrBtn) {
            applyOcrBtn.classList.add('hidden');
        }

        this.hideExtractionStatus();
        this.hideExtractionControl();
    }

    showDocumentDetails() {
        const details = document.getElementById('document-details');
        if (details) {
            details.classList.remove('hidden');
            this.validateForm();
        }
    }

    hideDocumentDetails() {
        const details = document.getElementById('document-details');
        if (details) {
            details.classList.add('hidden');
        }
    }

    validateForm() {
        const title = document.getElementById('document-title')?.value?.trim() || '';
        const saveButton = document.getElementById('save-document');
        if (saveButton) {
            saveButton.disabled = !title || this.currentFiles.length === 0;
        }
    }

    async saveDocument() {
    const formData = new FormData();
    const title = document.getElementById('document-title')?.value?.trim() || '';
    const documentType = document.getElementById('document-type')?.value || 'birth_certificate';
    const description = (document.getElementById('document-description')?.value || '').trim();
    const processingMode = this.isOcrSupportedDocumentType(documentType) ? 'ocr' : 'manual';

    if (!title) {
        this.showNotification('Please enter a document title', 'warning');
        return;
    }

    if (this.currentFiles.length === 0) {
        this.showNotification('No files to save', 'warning');
        return;
    }

    const primaryFile = this.currentFiles.find(file => file.file);
    if (!primaryFile || !primaryFile.file) {
        this.showNotification('No valid file found for saving', 'error');
        return;
    }

    this.showProgress('Validating document data...', 'fas fa-check-circle');

    let extractedData = {};

    if (processingMode === 'ocr') {
        extractedData = this.collectOCRData();

        const requiredFieldsByType = {
            birth_certificate: [
                'name_first', 'name_last', 'birth_date_day', 'birth_date_month',
                'birth_date_year', 'mother_first_name', 'mother_last_name',
                'father_first_name', 'father_last_name'
            ],
            death_certificate: [
                'deceased_first_name', 'deceased_last_name', 'death_date_day',
                'death_date_month', 'death_date_year', 'cause_of_death'
            ],
            marriage_certificate: [
                'groom_first_name', 'groom_last_name', 'bride_first_name',
                'bride_last_name', 'marriage_date'
            ],
        };

        const requiredFields = requiredFieldsByType[documentType] || [];
        const missingFields = requiredFields.filter(field => {
            const value = extractedData[field];
            return !value || value.toString().trim() === '';
        });

        if (missingFields.length > 0) {
            this.hideProgress();
            this.showNotification(
                `⚠️ Missing Required Fields:\n\n${missingFields.map(f => f.replace(/_/g, ' ')).join(', ')}`,
                'error'
            );
            return;
        }
    } else {
        extractedData = {
            manual_entry: true,
            document_type: documentType,
        };
    }

    this.showProgress('Saving document...', 'fas fa-save');

    formData.append('_token', this.csrfToken);
    formData.append('document_type', documentType);
    formData.append('processing_mode', processingMode);
    formData.append('title', title);
    formData.append('description', description || (processingMode === 'ocr'
        ? 'Document processed via OCR with manual corrections'
        : 'Document saved via manual mode (OCR skipped)'));
    formData.append('extracted_fields', JSON.stringify(extractedData));
    formData.append('ocr_confidence', processingMode === 'ocr' ? (extractedData.confidence || 0) : 0);
    formData.append('raw_ocr_text', processingMode === 'ocr' ? (extractedData.raw_text || '') : '');
    formData.append('processing_time', Date.now() - (this.processingStartTime || Date.now()));
    formData.append('manually_corrected', this.hasManualCorrections());
    formData.append('file', primaryFile.file);

    try {
        const response = await fetch('/staff/scan/save-document', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': this.csrfToken,
                'Accept': 'application/json'
            },
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            this.hideProgress();
            this.showNotification(
                `✅ Document saved successfully!\n\nMode: ${(data.processing_mode || processingMode).toUpperCase()}\nDocument ID: ${data.document_id}`,
                'success'
            );

            setTimeout(() => {
                window.location.href = '/staff/upload';
            }, 1800);
            return;
        }

        this.hideProgress();
        this.showNotification('Save failed: ' + (data.message || 'Unknown error'), 'error');

    } catch (error) {
        this.hideProgress();
        console.error('Save error:', error);
        this.showNotification('Network error: ' + error.message, 'error');
    }
}

collectOCRData() {
    console.log('=== COLLECTING OCR DATA (ENHANCED WITH DEBUG) ===');
    
    // Determine active certificate type
    const birthLayout = document.getElementById('birth-certificate-layout');
    const deathLayout = document.getElementById('death-certificate-layout');
    const marriageLayout = document.getElementById('marriage-certificate-layout');
    
    let certificateType = 'birth_certificate'; // default
    
    if (deathLayout && !deathLayout.classList.contains('hidden')) {
        certificateType = 'death_certificate';
    } else if (marriageLayout && !marriageLayout.classList.contains('hidden')) {
        certificateType = 'marriage_certificate';
    }
    
    console.log('Certificate type detected:', certificateType);
    
    // ENHANCED: Helper function with extensive debugging
    const getFieldValue = (fieldId) => {
        const element = document.getElementById(fieldId);
        
        if (!element) {
            console.error(`❌ FIELD NOT FOUND: ${fieldId}`);
            return '';
        }
        
        const value = (element.value || '').trim();
        
        if (value) {
            console.log(`✅ ${fieldId}: "${value}"`);
        } else {
            console.warn(`⚠️ ${fieldId}: EMPTY`);
        }
        
        return value;
    };
    
    let data = {
        certificate_type: certificateType,
        // Common metadata
        confidence: parseInt(document.getElementById('confidence-score')?.textContent) || 0,
        word_count: parseInt(document.getElementById('words-extracted')?.textContent) || 0,
        fields_detected: parseInt(document.getElementById('fields-detected')?.textContent) || 0,
        manually_corrected: this.hasManualCorrections()
    };
    
    // Add certificate-specific fields
    if (certificateType === 'birth_certificate') {
        console.log('--- COLLECTING BIRTH CERTIFICATE FIELDS ---');
        
        data = {
            ...data,
            // Child information
            name_first: getFieldValue('name-first'),
            name_middle: getFieldValue('name-middle'),
            name_last: getFieldValue('name-last'),
            sex: getFieldValue('sex-detected'),
            
            // Birth date
            birth_date_day: getFieldValue('birth-date-day'),
            birth_date_month: getFieldValue('birth-date-month'),
            birth_date_year: getFieldValue('birth-date-year'),
            
            // Place of birth
            birth_place_institution: getFieldValue('birth-place-institution'),
            birth_place_city: getFieldValue('birth-place-city'),
            birth_place_province: getFieldValue('birth-place-province'),
            
            // Mother's information
            mother_first_name: getFieldValue('mother-first-name'),
            mother_middle_name: getFieldValue('mother-middle-name'),
            mother_last_name: getFieldValue('mother-last-name'),
            
            // Father's information
            father_first_name: getFieldValue('father-first-name'),
            father_middle_name: getFieldValue('father-middle-name'),
            father_last_name: getFieldValue('father-last-name'),
            
            // Registry information
            registry_number: getFieldValue('registry-number'),
            bren_number: getFieldValue('bren-number'),
            
            // Additional information
            citizenship: getFieldValue('citizenship'),
            religion: getFieldValue('religion'),
            
            // Raw text
            raw_text: getFieldValue('raw-ocr-text-birth')
        };
        
        console.log('--- BIRTH CERTIFICATE COLLECTION COMPLETE ---');
        
    } else if (certificateType === 'death_certificate') {
        console.log('--- COLLECTING DEATH CERTIFICATE FIELDS ---');
        
        data = {
            ...data,
            deceased_first_name: getFieldValue('deceased-first-name'),
            deceased_middle_name: getFieldValue('deceased-middle-name'),
            deceased_last_name: getFieldValue('deceased-last-name'),
            deceased_sex: getFieldValue('deceased-sex'),
            deceased_age: getFieldValue('deceased-age'),
            
            death_date_day: getFieldValue('death-date-day'),
            death_date_month: getFieldValue('death-date-month'),
            death_date_year: getFieldValue('death-date-year'),
            
            death_place_institution: getFieldValue('death-place-institution'),
            death_place_city: getFieldValue('death-place-city'),
            death_place_province: getFieldValue('death-place-province'),
            
            cause_of_death: getFieldValue('cause-of-death'),
            
            father_name: getFieldValue('father-name'),
            mother_maiden_name: getFieldValue('mother-maiden-name'),
            
            registry_number: getFieldValue('registry-number-death'),
            certificate_number: getFieldValue('certificate-number'),
            
            informant_name: getFieldValue('informant-name'),
            informant_relationship: getFieldValue('informant-relationship'),
            
            raw_text: getFieldValue('raw-ocr-text-death')
        };
        
    } else if (certificateType === 'marriage_certificate') {
        console.log('--- COLLECTING MARRIAGE CERTIFICATE FIELDS ---');
        
        data = {
            ...data,
            groom_first_name: getFieldValue('groom-first-name'),
            groom_middle_name: getFieldValue('groom-middle-name'),
            groom_last_name: getFieldValue('groom-last-name'),
            groom_birth_date: getFieldValue('groom-birth-date'),
            groom_citizenship: getFieldValue('groom-citizenship'),
            groom_birth_place: getFieldValue('groom-birth-place'),
            groom_civil_status: getFieldValue('groom-civil-status'),
            groom_religion: getFieldValue('groom-religion'),
            groom_residence: getFieldValue('groom-residence'),
            groom_father_name: getFieldValue('groom-father-name'),
            groom_mother_name: getFieldValue('groom-mother-name'),
            
            bride_first_name: getFieldValue('bride-first-name'),
            bride_middle_name: getFieldValue('bride-middle-name'),
            bride_last_name: getFieldValue('bride-last-name'),
            bride_birth_date: getFieldValue('bride-birth-date'),
            bride_citizenship: getFieldValue('bride-citizenship'),
            bride_birth_place: getFieldValue('bride-birth-place'),
            bride_civil_status: getFieldValue('bride-civil-status'),
            bride_religion: getFieldValue('bride-religion'),
            bride_residence: getFieldValue('bride-residence'),
            bride_father_name: getFieldValue('bride-father-name'),
            bride_mother_name: getFieldValue('bride-mother-name'),
            
            marriage_date: getFieldValue('marriage-date'),
            marriage_place_city: getFieldValue('marriage-place-city'),
            marriage_place_province: getFieldValue('marriage-place-province'),
            marriage_officiant: getFieldValue('marriage-officiant'),
            marriage_officiant_position: getFieldValue('marriage-officiant-position'),
            marriage_license_number: getFieldValue('marriage-license-number'),
            
            marriage_witness_1: getFieldValue('marriage-witness-1'),
            marriage_witness_2: getFieldValue('marriage-witness-2'),
            
            marriage_registry_number: getFieldValue('marriage-registry-number'),
            marriage_book_number: getFieldValue('marriage-book-number'),
            marriage_page_number: getFieldValue('marriage-page-number'),
            marriage_volume_number: getFieldValue('marriage-volume-number'),
            
            raw_text: getFieldValue('raw-ocr-text-marriage')
        };
    }
    
    console.log('=== FINAL DATA SUMMARY ===');
    console.log('Total fields:', Object.keys(data).length);
    console.log('Filled fields:', Object.values(data).filter(v => v && v.toString().trim() !== '').length);
    console.log('Empty fields:', Object.values(data).filter(v => !v || v.toString().trim() === '').length);
    
    
    const requiredFieldsByType = {
        birth_certificate: [
            'name_first', 'name_last', 'birth_date_day', 'birth_date_month', 
            'birth_date_year', 'mother_first_name', 'mother_last_name', 
            'father_first_name', 'father_last_name'
        ]
    };
    
    const requiredFields = requiredFieldsByType[certificateType] || [];
    const missingRequired = requiredFields.filter(field => !data[field] || data[field].trim() === '');
    
    if (missingRequired.length > 0) {
        console.error('❌ MISSING REQUIRED FIELDS:', missingRequired);
    } else {
        console.log('✅ ALL REQUIRED FIELDS PRESENT');
    }
    
    console.log('Complete data object:', data);
    console.log('=== END DATA COLLECTION ===');
    
    return data;
}


hasManualCorrections() {
        const ocrFields = [
            'name-first', 'name-middle', 'name-last', 'sex-detected',
            'birth-date-day', 'birth-date-month', 'birth-date-year',
            'birth-place-institution', 'birth-place-city', 'birth-place-province',
            'mother-first-name', 'mother-middle-name', 'mother-last-name',
            'father-first-name', 'father-middle-name', 'father-last-name',
            'registry-number', 'bren-number', 'citizenship', 'religion'
        ];
        
        return ocrFields.some(fieldId => {
            const field = document.getElementById(fieldId);
            return field && field.hasAttribute('data-manually-edited');
        });
    }

    // FIXED: Enhanced export function
    exportOCRData() {
        const ocrData = this.collectOCRData();
        const timestamp = new Date().toISOString().slice(0, 19).replace(/:/g, '-');
        
        const exportData = {
            document_info: {
                title: document.getElementById('document-title')?.value || 'Untitled Document',
                type: document.getElementById('document-type')?.value || 'birth_certificate',
                extraction_date: new Date().toISOString(),
                confidence: ocrData.confidence || 0
            },
            extracted_fields: ocrData,
            metadata: {
                manually_corrected: this.hasManualCorrections(),
                corrections_count: this.getCorrectionsCount()
            }
        };
        
        const dataStr = JSON.stringify(exportData, null, 2);
        const dataBlob = new Blob([dataStr], {type: 'application/json'});
        const url = URL.createObjectURL(dataBlob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `ocr_extract_${timestamp}.json`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
        
        this.showNotification('OCR data exported successfully!', 'success');
    }

    async submitToBlockchain(documentHash, documentId) {
        console.log('TODO: Submit to blockchain', { documentHash, documentId });
        // Placeholder for smart contract integration
    }

    handleBlockchainValidation() {
        const checkbox = document.getElementById('blockchain-validation');
        if (checkbox && checkbox.checked) {
            this.showNotification('Blockchain validation enabled - document hash will be generated', 'info');
        }
    }

    clearAll() {
        // Clear files and revoke object URLs
        this.currentFiles.forEach(file => {
            if (file.path && file.path.startsWith('blob:')) {
                URL.revokeObjectURL(file.path);
            }
        });
        
        this.currentFiles = [];
        this.ocrResults = {};
        this.updateFileList();

        this.resetPreviewState();
        
        this.hideDocumentDetails();
        
        // Clear form
        const form = document.getElementById('document-form');
        if (form) form.reset();
        
        this.showNotification('All files and OCR data cleared', 'success');
    }

    // Utility methods
    showProgress(message, icon = 'fas fa-spinner fa-spin') {
        const modal = document.getElementById('progress-modal');
        const iconElement = document.getElementById('progress-icon');
        const messageElement = document.getElementById('progress-message');
        
        if (modal && iconElement && messageElement) {
            iconElement.className = `${icon} text-3xl text-blue-600`;
            messageElement.textContent = message;
            modal.classList.remove('hidden');
        }
    }

    hideProgress() {
    const modal = document.getElementById('progress-modal');
    if (modal) {
        modal.classList.add('hidden');
        console.log('Progress modal hidden');
    }
    
        // ADDED: Also hide extraction status when hiding progress
        this.hideExtractionStatus();
    }

    showNotification(message, type = 'info') {
        // Remove existing notifications
        document.querySelectorAll('.notification-alert').forEach(el => el.remove());
        
        const colors = {
            success: 'bg-green-100 text-green-800 border-green-200',
            error: 'bg-red-100 text-red-800 border-red-200',
            warning: 'bg-yellow-100 text-yellow-800 border-yellow-200',
            info: 'bg-blue-100 text-blue-800 border-blue-200'
        };
        
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
        
        const notification = document.createElement('div');
        notification.className = `notification-alert fixed top-4 right-4 z-50 p-4 rounded-lg border ${colors[type]} shadow-lg max-w-sm`;
        notification.innerHTML = `
            <div class="flex items-center">
                <i class="fas ${icons[type]} mr-2"></i>
                <span class="font-medium">${message}</span>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
}

function copyOCRText() {
    const textArea = document.getElementById('raw-ocr-text');
    if (textArea) {
        textArea.select();
        document.execCommand('copy');
        if (window.documentScanner) {
            window.documentScanner.showNotification('OCR text copied to clipboard!', 'success');
        }
    }
}


document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing Document Scanner...');
    try {
        window.documentScanner = new DocumentScanner();
        console.log('Document Scanner initialized successfully');
    } catch (error) {
        console.error('Failed to initialize Document Scanner:', error);
    }
});

</script>
@endsection
