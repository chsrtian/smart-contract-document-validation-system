@extends('layouts.staff')

@section('title', 'Document Search')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/staff-search.css') }}">
@endpush

@section('content')
<div class="ss-page">
    <div class="ss-container">

        {{-- ======== Page Header ======== --}}
        <div class="ss-page-header">
            <h1 class="ss-page-title staff-page-title">Document Search</h1>
            <p class="ss-page-subtitle staff-page-subtitle">Search and retrieve civil registry documents</p>
        </div>

        {{-- ======== Category Cards (responsive 4-col grid) ======== --}}
        <div class="ss-categories">
            {{-- Birth Certificates --}}
            <div class="ss-category-card" data-type="birth_certificate" tabindex="0"
                 role="button" aria-label="Search Birth Certificates">
                <div class="ss-category-icon birth">
                    <i class="fas fa-baby"></i>
                </div>
                <div class="ss-category-info">
                    <div class="ss-category-name">Birth Certificates</div>
                    <div class="ss-category-count">{{ \App\Models\Scan::where('document_type', 'birth_certificate')->count() }} Records</div>
                </div>
                <i class="fas fa-chevron-right ss-category-arrow"></i>
            </div>

            {{-- Marriage Certificates --}}
            <div class="ss-category-card" data-type="marriage_certificate" tabindex="0"
                 role="button" aria-label="Search Marriage Certificates">
                <div class="ss-category-icon marriage">
                    <i class="fas fa-heart"></i>
                </div>
                <div class="ss-category-info">
                    <div class="ss-category-name">Marriage Certificates</div>
                    <div class="ss-category-count">{{ \App\Models\Scan::where('document_type', 'marriage_certificate')->count() }} Records</div>
                </div>
                <i class="fas fa-chevron-right ss-category-arrow"></i>
            </div>

            {{-- Death Certificates --}}
            <div class="ss-category-card" data-type="death_certificate" tabindex="0"
                 role="button" aria-label="Search Death Certificates">
                <div class="ss-category-icon death">
                    <i class="fas fa-cross"></i>
                </div>
                <div class="ss-category-info">
                    <div class="ss-category-name">Death Certificates</div>
                    <div class="ss-category-count">{{ \App\Models\Scan::where('document_type', 'death_certificate')->count() }} Records</div>
                </div>
                <i class="fas fa-chevron-right ss-category-arrow"></i>
            </div>

            {{-- Other Documents --}}
            <div class="ss-category-card" data-type="other" tabindex="0"
                 role="button" aria-label="Search Other Documents">
                <div class="ss-category-icon other">
                    <i class="fas fa-folder-open"></i>
                </div>
                <div class="ss-category-info">
                    <div class="ss-category-name">Other Documents</div>
                    <div class="ss-category-count">{{ \App\Models\Scan::whereNotIn('document_type', \App\Models\Scan::OCR_DOCUMENT_TYPES)->count() }} Records</div>
                </div>
                <i class="fas fa-chevron-right ss-category-arrow"></i>
            </div>
        </div>

        {{-- ======== Empty State (visible when no category selected) ======== --}}
        <div id="ss-empty-state" class="ss-empty-state">
            <div class="ss-empty-icon">
                <i class="fas fa-search"></i>
            </div>
            <div class="ss-empty-title">Select a Document Category</div>
            <p class="ss-empty-text">Choose a category above to start searching for civil registry documents</p>
            <div class="ss-empty-hint">
                <i class="fas fa-arrow-up"></i> Click any card to begin
            </div>
        </div>

        {{-- ======== Search Form Container (hidden until category selected) ======== --}}
        <div id="ss-form-container" class="ss-form-wrap hidden">
            <div class="ss-form-card">
                <div class="ss-form-header">
                    <div id="ss-form-title" class="ss-form-title">
                        <i class="fas fa-search"></i> Document Search Form
                    </div>
                    <button id="ss-form-close" class="ss-form-close" aria-label="Close search form">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="ss-form-content" class="ss-form-body">
                    {{-- Dynamic form content loaded via AJAX --}}
                </div>
            </div>
        </div>

        {{-- ======== Search Results Container (shown after form submit) ======== --}}
        <div id="ss-results-container" class="ss-results-wrap hidden">
            <div class="ss-results-card">
                <div id="ss-results-content">
                    {{-- Populated by JS after POST search --}}
                </div>
            </div>
        </div>

    </div>
</div>

{{-- ======== Side Drawer (document quick-preview) ======== --}}
<div id="ss-drawer" class="ss-drawer" aria-label="Document preview drawer">
    <div class="ss-drawer-header">
        <span id="ss-drawer-title" class="ss-drawer-title">Document Details</span>
        <button id="ss-drawer-close" class="ss-drawer-close" aria-label="Close drawer">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div id="ss-drawer-body" class="ss-drawer-body">
        {{-- Populated by JS --}}
    </div>
    <div class="ss-drawer-footer">
        <button id="ss-drawer-print-btn" class="ss-btn ss-btn-primary ss-btn-full">
            <i class="fas fa-print"></i> Print &amp; Release
        </button>
    </div>
</div>
<div id="ss-drawer-backdrop" class="ss-drawer-backdrop"></div>
@endsection

@push('scripts')
<script src="{{ asset('js/staff-search.js') }}"></script>
@endpush
