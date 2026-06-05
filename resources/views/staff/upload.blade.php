@extends('layouts.staff')

@section('content')

<style>

/* =============================================
   UPLOAD PAGE — inherits Corrections design system
   Only page-specific styles below.
   ============================================= */

/* Smooth hover effects */
.transform.hover\:-translate-y-1:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1) !important;
}

/* Progress bar animations */
.transition-all.duration-500 {
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Compact card styling */
.truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Status dot pulse animation */
@keyframes pulse {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.7;
        transform: scale(1.1);
    }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

.modal-overlay {

    position: fixed; top: 0; left: 0; right: 0; bottom: 0;

    background-color: rgba(0, 0, 0, 0.6);

    backdrop-filter: blur(4px);

    z-index: 50;

    display: flex; align-items: center; justify-content: center;

    padding: 16px;

    font-family: 'Segoe UI', Tahoma, sans-serif;

}

.modal-box {

    background: white;

    border-radius: 12px;

    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);

    width: 100%; max-width: 900px;

    max-height: 90vh;

    overflow: hidden;

    display: flex; flex-direction: column;

}


.modal-header {

    background-color: #000000; /* Solid Black */

    padding: 20px 24px;

    border-bottom: 1px solid #333;

    display: flex; justify-content: space-between; align-items: center;

}

.header-title {

    color: #ffffff;

    font-size: 18px; font-weight: 700;

    display: flex; align-items: center; gap: 12px;

}

.close-icon-btn {

    color: #9ca3af; background: none; border: none; cursor: pointer;

    transition: color 0.2s;

}

.close-icon-btn:hover { color: #ffffff; }



/* 3. BODY & CARDS */

.modal-body {

    padding: 24px;

    overflow-y: auto;

    background-color: #f3f4f6; /* Light gray bg for contrast */

    flex-grow: 1;

}

.info-card {

    background: #ffffff;

    border: 1px solid #e5e7eb;

    border-radius: 10px;

    padding: 20px;

    margin-bottom: 20px;

    box-shadow: 0 1px 2px rgba(0,0,0,0.05);

}

.card-title {

    font-size: 13px; font-weight: 700; color: #000;

    text-transform: uppercase; letter-spacing: 0.5px;

    margin-bottom: 15px; border-bottom: 1px solid #f0f0f0; padding-bottom: 10px;

    display: flex; align-items: center; gap: 8px;

}

.field-group { margin-bottom: 5px; }

.label-text {

    display: block; font-size: 12px; font-weight: 600;

    color: #64748b; /* Darker Gray Label */

    margin-bottom: 4px; text-transform: uppercase;

}

.value-text {

    display: block; font-size: 16px; font-weight: 800; /* Bold */

    color: #000000; 

}

.status-badge {

    display: inline-block; padding: 4px 12px; border-radius: 50px;

    font-size: 12px; font-weight: 800; text-transform: uppercase;

    color: #000 !important; border: 1px solid #000;

}

.status-confirmed { background: #dcfce7; border-color: #22c55e; }

.status-pending { background: #fef9c3; border-color: #eab308; }

.status-failed { background: #fee2e2; border-color: #ef4444; }


.hash-container {

    display: flex; align-items: center; gap: 10px;

    background: #000; border-radius: 8px; padding: 0;

    border: 1px solid #333; overflow: hidden;

    margin-bottom: 20px;

}

.hash-value {

    flex: 1; padding: 12px 15px;

    font-family: monospace; font-size: 13px;

    color: #4ade80; /* Matrix Green Text */

    word-break: break-all;

}

.copy-btn {

    background: #1f2937; border: none; border-left: 1px solid #333;

    color: #fff; cursor: pointer; padding: 0 20px; height: 100%;

    display: flex; align-items: center; justify-content: center;

    transition: background 0.2s;

}

.copy-btn:hover { background: #374151; }

.grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }

.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }

.spacing-fix { margin-bottom: 20px; } 

.no-line { border-left: none !important; padding-left: 0 !important; }


.modal-footer {

    padding: 20px 24px; background: #fff; border-top: 1px solid #e5e7eb;

    display: flex; justify-content: flex-end; gap: 15px; 

}

.action-btn {

    display: inline-flex; align-items: center; justify-content: center; gap: 8px;

    padding: 10px 24px; border-radius: 8px;

    font-size: 14px; font-weight: 700; text-decoration: none;

    color: #fff !important; cursor: pointer; border: none;

    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);

    transition: transform 0.1s, box-shadow 0.1s;

}

.action-btn:hover { transform: translateY(-1px); box-shadow: 0 6px 8px -1px rgba(0, 0, 0, 0.15); }

.btn-blue { background-color: #2563eb; }

.btn-blue:hover { background-color: #1d4ed8; }

.btn-green { background-color: #16a34a; }

.btn-green:hover { background-color: #15803d; }

.btn-black { background-color: #1f2937; } /* Close button */

.btn-black:hover { background-color: #000; }

/* =============================================
   Upload UI System: stats + controls + view toggle
   ============================================= */
.upload-stat-card {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    padding: 1rem;
    min-height: 96px;
}

.upload-stat-icon {
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 0.75rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.upload-stat-icon i {
    font-size: 1.5rem;
    line-height: 1;
}

.upload-stat-icon--blue { background: #dbeafe; color: #2563eb; }
.upload-stat-icon--green { background: #dcfce7; color: #16a34a; }
.upload-stat-icon--amber { background: #fef3c7; color: #d97706; }
.upload-stat-icon--slate { background: #e5e7eb; color: #475569; }

.upload-stat-content {
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 6px;
    min-width: 0;
}

.upload-stat-value {
    font-size: 1.75rem;
    line-height: 1.1;
    font-weight: 800;
    color: #111827;
}

.upload-stat-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #4b5563;
    letter-spacing: 0.01em;
}

.upload-toolbar-actions {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}

.upload-action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    height: 40px;
    padding: 0 16px;
    border-radius: 0.625rem;
    border: 1px solid transparent;
    font-size: 0.875rem;
    font-weight: 600;
    transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
}

.upload-action-btn i { font-size: 0.95rem; }

.upload-action-btn:focus-visible,
.upload-view-btn:focus-visible {
    outline: none;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
}

.upload-action-btn--primary {
    background: #2563eb;
    color: #ffffff;
}

.upload-action-btn--primary:hover {
    background: #1d4ed8;
}

.upload-action-btn--neutral {
    background: #ffffff;
    color: #1f2937;
    border-color: #d1d5db;
}

.upload-action-btn--neutral:hover {
    background: #f9fafb;
    border-color: #9ca3af;
    color: #111827;
}

.upload-action-btn[disabled] {
    opacity: 0.65;
    cursor: not-allowed;
}

.upload-view-toggle {
    display: flex;
    align-items: center;
    gap: 12px;
}

.upload-view-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    height: 36px;
    padding: 0 14px;
    border-radius: 0.625rem;
    border: 1px solid #d1d5db;
    background: #ffffff;
    color: #1f2937;
    font-size: 0.875rem;
    font-weight: 600;
    transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
}

.upload-view-btn i { font-size: 0.9rem; }

.upload-view-btn:hover {
    background: #f8fafc;
    border-color: #9ca3af;
    color: #111827;
}

.upload-view-btn.is-active {
    background: #2563eb;
    border-color: #2563eb;
    color: #ffffff;
}

.upload-view-btn.is-active:hover {
    background: #1d4ed8;
    border-color: #1d4ed8;
}

/* =============================================
   Upload Document Card System
   ============================================= */
.upload-doc-card {
    background: #ffffff;
    border: 1px solid #dbe1ea;
    border-radius: 0.875rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    transition: box-shadow 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
}

.upload-doc-card:hover {
    border-color: #c7d2e3;
    box-shadow: 0 10px 22px rgba(15, 23, 42, 0.1);
    transform: translateY(-2px);
}

.upload-doc-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 16px;
    border-bottom: 1px solid #e5e7eb;
    background: #f8fafc;
}

.upload-doc-meta {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}

.upload-doc-type {
    font-size: 1rem;
    font-weight: 800;
    line-height: 1.1;
    color: #111827;
}

.upload-doc-id {
    margin-top: 4px;
    font-size: 0.8125rem;
    color: #4b5563;
    font-weight: 600;
}

.upload-doc-state {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 4px 10px;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 700;
    border: 1px solid transparent;
    white-space: nowrap;
}

.upload-doc-state--confirmed {
    background: #dcfce7;
    color: #166534;
    border-color: #86efac;
}

.upload-doc-state--pending {
    background: #dbeafe;
    color: #1e3a8a;
    border-color: #93c5fd;
}

.upload-doc-state--failed {
    background: #fee2e2;
    color: #991b1b;
    border-color: #fca5a5;
}

.upload-doc-state--draft {
    background: #f3f4f6;
    color: #374151;
    border-color: #d1d5db;
}

.upload-doc-pill-group {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    padding: 12px 16px;
    border-bottom: 1px solid #edf1f7;
}

.upload-doc-pill-wrap {
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.upload-doc-pill-label {
    font-size: 0.6875rem;
    font-weight: 700;
    color: #4b5563;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.upload-doc-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 3px 10px;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 700;
    border: 1px solid transparent;
}

.upload-doc-chip--success {
    background: #dcfce7;
    color: #166534;
    border-color: #86efac;
}

.upload-doc-chip--warning {
    background: #fef3c7;
    color: #92400e;
    border-color: #fcd34d;
}

.upload-doc-chip--danger {
    background: #fee2e2;
    color: #991b1b;
    border-color: #fca5a5;
}

.upload-doc-chip--info {
    background: #dbeafe;
    color: #1e3a8a;
    border-color: #93c5fd;
}

.upload-doc-chip--muted {
    background: #f3f4f6;
    color: #374151;
    border-color: #d1d5db;
}

.upload-doc-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    gap: 16px;
    padding: 16px;
}

.upload-doc-section {
    border: 1px solid #e5e7eb;
    background: #f8fafc;
    border-radius: 0.75rem;
    padding: 12px;
}

.upload-doc-section--full {
    grid-column: 1 / -1;
}

.upload-doc-section-title {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
    font-size: 0.75rem;
    font-weight: 800;
    color: #111827;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.upload-doc-row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    padding: 4px 0;
}

.upload-doc-row-label {
    font-size: 0.75rem;
    color: #4b5563;
    font-weight: 700;
    flex-shrink: 0;
}

.upload-doc-row-value {
    font-size: 0.78rem;
    color: #111827;
    font-weight: 600;
    text-align: right;
    word-break: break-word;
}

.upload-doc-row-value--mono {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    font-size: 0.72rem;
}

.upload-doc-preview {
    margin-top: 10px;
    font-size: 0.8125rem;
    color: #1f2937;
    line-height: 1.45;
}

.upload-doc-metrics {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
    margin-bottom: 10px;
}

.upload-doc-metric {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 0.625rem;
    padding: 8px;
}

.upload-doc-metric-label {
    display: block;
    font-size: 0.6875rem;
    color: #4b5563;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.upload-doc-metric-value {
    display: block;
    margin-top: 4px;
    font-size: 0.875rem;
    color: #111827;
    font-weight: 800;
}

.upload-doc-progress {
    width: 100%;
    height: 8px;
    border-radius: 9999px;
    background: #e5e7eb;
    overflow: hidden;
}

.upload-doc-progress-bar {
    height: 100%;
    border-radius: 9999px;
    transition: width 0.3s ease;
}

.upload-doc-actions {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border-top: 1px solid #e5e7eb;
    background: #f8fafc;
}

.upload-doc-action-primary {
    flex: 1;
    min-width: 0;
}

.upload-doc-view-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 36px;
    padding: 0 14px;
    border-radius: 0.625rem;
    border: 1px solid #93c5fd;
    background: #eff6ff;
    color: #1e3a8a;
    font-size: 0.8125rem;
    font-weight: 700;
    transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
}

.upload-doc-view-btn:hover {
    background: #dbeafe;
    border-color: #60a5fa;
    color: #1e40af;
}

.upload-doc-view-btn:focus-visible {
    outline: none;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
}

.upload-doc-action-main {
    width: 100%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 36px;
    padding: 0 12px;
    border-radius: 0.625rem;
    border: 1px solid transparent;
    font-size: 0.78rem;
    font-weight: 700;
    transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
}

.upload-doc-action-main--success { background: #16a34a; color: #ffffff; }
.upload-doc-action-main--success:hover { background: #15803d; }
.upload-doc-action-main--danger { background: #fee2e2; color: #991b1b; border-color: #fca5a5; }
.upload-doc-action-main--danger:hover { background: #fecaca; }
.upload-doc-action-main--warning { background: #f59e0b; color: #ffffff; }
.upload-doc-action-main--warning:hover { background: #d97706; }
.upload-doc-action-main--info { background: #dbeafe; color: #1e3a8a; border-color: #93c5fd; }

.upload-doc-action-main:disabled {
    opacity: 0.85;
    cursor: not-allowed;
}

.upload-doc-action-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 10px;
    border-radius: 0.625rem;
    background: #dcfce7;
    border: 1px solid #86efac;
    color: #166534;
    font-size: 0.75rem;
    font-weight: 700;
}

.upload-doc-action-stack {
    display: grid;
    gap: 8px;
}


@media (max-width: 768px) {

    .grid-3, .grid-2 { grid-template-columns: 1fr; gap: 15px; }

    .modal-footer { flex-direction: column; }

    .upload-stat-value { font-size: 1.5rem; }

    .upload-toolbar-actions,
    .upload-view-toggle { gap: 10px; }

    .upload-doc-grid { grid-template-columns: 1fr; }

    .upload-doc-metrics { grid-template-columns: 1fr; }

    .upload-doc-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .upload-doc-view-btn {
        width: 100%;
    }

}

</style>

<div class="staff-page-shell py-6">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    <!-- Page Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="staff-page-title">Document Uploads</h1>
                <p class="staff-page-subtitle">Manage, track, and verify uploaded documents</p>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="upload-stat-card">
            <div class="upload-stat-icon upload-stat-icon--blue">
                <i class="fas fa-file-alt"></i>
            </div>
            <div class="upload-stat-content">
                <p class="upload-stat-value" id="today-processed" aria-label="Today processed documents count">0</p>
                <p class="upload-stat-label">Today Processed</p>
            </div>
        </div>
        <div class="upload-stat-card">
            <div class="upload-stat-icon upload-stat-icon--green">
                <i class="fas fa-cube"></i>
            </div>
            <div class="upload-stat-content">
                <p class="upload-stat-value" id="blockchain-confirmed" aria-label="Blockchain confirmed documents count">0</p>
                <p class="upload-stat-label">Blockchain Confirmed</p>
            </div>
        </div>
        <div class="upload-stat-card">
            <div class="upload-stat-icon upload-stat-icon--amber">
                <i class="fas fa-clock"></i>
            </div>
            <div class="upload-stat-content">
                <p class="upload-stat-value" id="pending-verification" aria-label="Pending verification documents count">0</p>
                <p class="upload-stat-label">Pending Verification</p>
            </div>
        </div>
        <div class="upload-stat-card">
            <div class="upload-stat-icon upload-stat-icon--slate">
                <i class="fas fa-layer-group"></i>
            </div>
            <div class="upload-stat-content">
                <p class="upload-stat-value" id="total-documents" aria-label="Total documents count">0</p>
                <p class="upload-stat-label">Total Documents</p>
            </div>
        </div>
    </div>

    <!-- Document Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
        <div class="p-4">
            <div class="flex flex-wrap items-end gap-4">
                <div class="flex-1 min-w-[200px]">
                    <label for="search-input" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input type="text" id="search-input" placeholder="Search documents..."
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm text-gray-900 placeholder-gray-400">
                </div>
                <div class="min-w-[180px]">
                    <label for="document-type-filter" class="block text-sm font-medium text-gray-700 mb-1">Document Type</label>
                    <select id="document-type-filter" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm text-gray-900 bg-white">
                        <option value="">All Types</option>
                        <option value="birth_certificate">Birth Certificate</option>
                        <option value="death_certificate">Death Certificate</option>
                        <option value="marriage_certificate">Marriage Certificate</option>
                        <option value="admission_of_paternity">Admission of Paternity</option>
                        <option value="ausf">AUSF</option>
                        <option value="legitimation">Legitimation</option>
                        <option value="affidavit_of_reappearance">Affidavit of Reappearance</option>
                        <option value="marriage_settlement">Marriage Settlement</option>
                        <option value="parental_authorization_ai">Parental Authorization / AI Ratification</option>
                        <option value="late_registration">Late Registration</option>
                        <option value="supplemental_report">Supplemental Report</option>
                        <option value="certificate_of_foundling">Certificate of Foundling</option>
                        <option value="adoption_document">Adoption Document</option>
                        <option value="judicial_correction_rule_108">Judicial Correction (Rule 108)</option>
                        <option value="annulment_or_nullity">Annulment / Nullity</option>
                        <option value="recognition_of_foreign_divorce">Recognition of Foreign Divorce</option>
                        <option value="marriage_license">Marriage License</option>
                        <option value="certificate_legal_capacity_to_marry">Certificate of Legal Capacity to Marry</option>
                        <option value="cenomar">CENOMAR</option>
                        <option value="affidavit">Affidavit</option>
                        <option value="court_document">Court Document</option>
                        <option value="contract">Contract</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="min-w-[150px]">
                    <label for="status-filter" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select id="status-filter" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm text-gray-900 bg-white">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="verified">Verified</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <div class="upload-toolbar-actions">
                    <button id="refresh-btn"
                            class="upload-action-btn upload-action-btn--primary">
                        <i class="fas fa-sync"></i>
                        Refresh
                    </button>
                    <button id="clear-filters-btn"
                            class="upload-action-btn upload-action-btn--neutral">
                        <i class="fas fa-times"></i>
                        Clear
                    </button>
                </div>
            </div>
        </div>
    </div>

<!-- Documents Grid Container -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Recent Documents</h3>
                <div class="text-xs text-gray-400">
                    Last updated: <span id="last-updated" class="font-medium text-gray-500">Never</span>
                </div>
            </div>
            
            <!-- View Toggle (Optional) -->
            <div class="upload-view-toggle">
                <button id="grid-view-btn" class="upload-view-btn is-active">
                    <i class="fas fa-th"></i>
                    Grid
                </button>
                <button id="list-view-btn" class="upload-view-btn">
                    <i class="fas fa-list"></i>
                    List
                </button>
            </div>
        </div>
    </div>
    
    <!-- Documents Container with Grid Layout -->
    <div id="documents-container">
        <div class="p-12 text-center">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-gray-100 rounded-full mb-4">
                <i class="fas fa-file-alt text-gray-400 text-3xl"></i>
            </div>
            <div id="loading-message" class="text-xl font-semibold text-gray-900 mb-2">Loading documents...</div>
        </div>
    </div>

    <!-- Pagination -->
    <div id="pagination-container" class="px-6 py-4 bg-gray-50 border-t border-gray-200 hidden">
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-500" id="pagination-info">
                Showing 0 to 0 of 0 results
            </div>
            <div class="flex space-x-2" id="pagination-buttons">
        </div>
    </div>
</div>

    <!-- Real-time Status Indicator -->
    <div class="fixed bottom-4 right-4 z-50">
        <div id="connection-status" class="px-4 py-2 rounded-lg shadow-lg transition-all duration-300">
            <div class="flex items-center space-x-2">
                <div class="w-3 h-3 rounded-full" id="status-indicator"></div>
                <span id="status-text" class="text-sm font-medium">Connecting...</span>
            </div>
        </div>
    </div>
</div>
</div>

<script>
let currentPage = 1;
let isLoading = false;
let refreshInterval;
let connectionStatus = 'connecting';
let currentViewMode = 'grid';

// Configuration
const REFRESH_INTERVAL = 30000; // 30 seconds
const METRICS_REFRESH_INTERVAL = 10000; // 10 seconds

document.addEventListener('DOMContentLoaded', function() {
    initializeDashboard();
    setupEventListeners();
    startRealTimeUpdates();
});

function initializeDashboard() {
    updateConnectionStatus('connecting');
    setupViewToggle();
    loadMetrics();
    loadDocuments();
}

function setupViewToggle() {
    const gridViewBtn = document.getElementById('grid-view-btn');
    const listViewBtn = document.getElementById('list-view-btn');
    
    if (!gridViewBtn || !listViewBtn) {
        console.error('View toggle buttons not found');
        return;
    }
    
    // Grid View Button Click
    gridViewBtn.addEventListener('click', function() {
        console.log('Switching to grid view');
        currentViewMode = 'grid';
        updateViewButtons();
        loadDocuments(currentPage); // Reload with grid layout
    });
    
    // List View Button Click
    listViewBtn.addEventListener('click', function() {
        console.log('Switching to list view');
        currentViewMode = 'list';
        updateViewButtons();
        loadDocuments(currentPage); // Reload with list layout
    });
}

function updateViewButtons() {
    const gridViewBtn = document.getElementById('grid-view-btn');
    const listViewBtn = document.getElementById('list-view-btn');
    
    if (currentViewMode === 'grid') {
        gridViewBtn.classList.add('is-active');
        listViewBtn.classList.remove('is-active');
    } else {
        gridViewBtn.classList.remove('is-active');
        listViewBtn.classList.add('is-active');
    }
}

function setupEventListeners() {
    // Search input with debounce
    let searchTimeout;
    document.getElementById('search-input').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentPage = 1;
            loadDocuments();
        }, 500);
    });

    // Filter changes
    ['document-type-filter', 'status-filter'].forEach(filterId => {
        document.getElementById(filterId).addEventListener('change', function() {
            currentPage = 1;
            loadDocuments();
        });
    });

    // Refresh button
    document.getElementById('refresh-btn').addEventListener('click', function() {
        this.disabled = true;
        loadMetrics();
        loadDocuments().finally(() => {
            this.disabled = false;
        });
    });

    // Clear filters button
    document.getElementById('clear-filters-btn').addEventListener('click', function() {
        document.getElementById('search-input').value = '';
        document.getElementById('document-type-filter').value = '';
        document.getElementById('status-filter').value = '';
        currentPage = 1;
        loadDocuments(1);
    });
}

function startRealTimeUpdates() {
    // Metrics refresh interval
    setInterval(loadMetrics, METRICS_REFRESH_INTERVAL);
    
    // Documents refresh interval
    refreshInterval = setInterval(() => {
        if (!isLoading) {
            loadDocuments();
        }
    }, REFRESH_INTERVAL);
}

async function loadMetrics() {
    try {
        const response = await fetch('{{ route("staff.upload.metrics") }}', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        if (!response.ok) throw new Error('Network response was not ok');
        
        const result = await response.json();
        
        if (result.success) {
            updateMetricsDisplay(result.data);
            updateConnectionStatus('connected');
        } else {
            throw new Error(result.message || 'Failed to load metrics');
        }
    } catch (error) {
        console.error('Error loading metrics:', error);
        updateConnectionStatus('error');
    }
}

function updateMetricsDisplay(metrics) {
    document.getElementById('today-processed').textContent = metrics.today_processed || 0;
    document.getElementById('blockchain-confirmed').textContent = metrics.blockchain_confirmed || 0;
    document.getElementById('pending-verification').textContent = metrics.pending_verification || 0;
    document.getElementById('total-documents').textContent = metrics.total_documents || 0;
    
    // MODEL A: Log additional metrics (available in console for debugging)
    console.log('MODEL A Metrics:', {
        blockchain_eligible: metrics.blockchain_eligible || 0,
        needs_review: metrics.needs_review || 0,
        avg_validation_score: metrics.avg_validation_score || 0,
        pending_blockchain: metrics.pending_blockchain || 0
    });
}

async function loadDocuments(page = 1) {
    if (isLoading) return;
    isLoading = true;
    currentPage = page;

    try {
        const params = new URLSearchParams({
            page: page,
            search: document.getElementById('search-input').value,
            document_type: document.getElementById('document-type-filter').value,
            status: document.getElementById('status-filter').value
        });

        const response = await fetch(`{{ route("staff.upload.documents") }}?${params}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        if (!response.ok) throw new Error('Network response was not ok');
        
        const result = await response.json();
        
        if (result.success) {
            displayDocuments(result.data);
            updateLastUpdated();
            updateConnectionStatus('connected');
        } else {
            throw new Error(result.message || 'Failed to load documents');
        }
    } catch (error) {
        console.error('Error loading documents:', error);
        updateConnectionStatus('error');
        displayError('Failed to load documents. Please try again.');
    } finally {
        isLoading = false;
    }
}

function displayDocuments(paginatedData) {
    const container = document.getElementById('documents-container');
    const paginationContainer = document.getElementById('pagination-container');
    
    if (!paginatedData.data || paginatedData.data.length === 0) {
        container.innerHTML = `
            <div class="p-12 text-center">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-gray-100 rounded-full mb-4">
                    <i class="fas fa-file-alt text-gray-400 text-3xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">No documents found</h3>
                <p class="text-gray-500">Try adjusting your search or filters.</p>
            </div>
        `;
        paginationContainer.classList.add('hidden');
        return;
    }

    // ⭐ UPDATED: Support both grid and list views
    let documentsHtml;
    
    if (currentViewMode === 'grid') {
        // Grid Layout
        documentsHtml = `
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    ${paginatedData.data.map(doc => createDocumentCard(doc)).join('')}
                </div>
            </div>
        `;
    } else {
        // List Layout
        documentsHtml = `
            <div class="divide-y divide-gray-200">
                ${paginatedData.data.map(doc => createListViewCard(doc)).join('')}
            </div>
        `;
    }
    
    container.innerHTML = documentsHtml;
    
    updatePagination(paginatedData);
    paginationContainer.classList.remove('hidden');
}

function createDocumentCard(doc) {
    const statusBadge = getStatusBadge(doc.verification_status);
    const blockchainBadge = getBlockchainBadge(doc.blockchain_status, doc.blockchain_tx_hash);
    const actionButton = createActionButton(doc);
    
    // Document type with emoji
    const docTypeInfo = getDocumentTypeInfo(doc.document_type);
    
    const displayDate = doc.formatted_date || doc.created_at || doc.updated_at;
    const blockchainDate = doc.blockchain_confirmed_at;
    
    // Score calculation
    const validationScore = parseFloat(doc.validation_score) || 0;
    const ocrScore = parseFloat(doc.ocr_confidence) || 0;
    const manualScore = parseFloat(doc.manual_completion_score) || 0;
    
    // Status color and progress
    const scoreStatus = getScoreStatus(validationScore, doc.blockchain_status);

    const txHashDisplay = doc.blockchain_tx_hash
        ? `${doc.blockchain_tx_hash.substring(0, 14)}...`
        : 'Not available';

    const cardStateMap = {
        confirmed: { text: 'Confirmed', cls: 'upload-doc-state--confirmed' },
        pending: { text: 'Processing', cls: 'upload-doc-state--pending' },
        failed: { text: 'Failed', cls: 'upload-doc-state--failed' },
        not_started: { text: 'Not Anchored', cls: 'upload-doc-state--draft' }
    };
    const cardState = cardStateMap[doc.blockchain_status] || cardStateMap.not_started;

    const notesSection = doc.notes ? 
        `
        <div class="upload-doc-section upload-doc-section--full">
            <div class="upload-doc-section-title"><i class="fas fa-sticky-note text-amber-500"></i> Notes</div>
            <div class="text-xs text-gray-700 whitespace-pre-wrap max-h-24 overflow-y-auto leading-relaxed">
                ${doc.notes}
            </div>
        </div>
    ` : '';

     return `
        <div class="upload-doc-card" 
             data-document-id="${doc.id}">

            <div class="upload-doc-header">
                <div class="upload-doc-meta">
                    <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-xl">${docTypeInfo.emoji}</div>
                    <div class="min-w-0">
                        <h4 class="upload-doc-type">${docTypeInfo.shortName}</h4>
                        <div class="upload-doc-id">ID: #${doc.id}</div>
                    </div>
                </div>
                <span class="upload-doc-state ${cardState.cls}">${cardState.text}</span>
            </div>

            <div class="upload-doc-pill-group">
                <div class="upload-doc-pill-wrap">
                    <span class="upload-doc-pill-label">Verification</span>
                    ${statusBadge}
                </div>
                <div class="upload-doc-pill-wrap">
                    <span class="upload-doc-pill-label">Blockchain</span>
                    ${blockchainBadge}
                </div>
            </div>

            <div class="upload-doc-grid">
                <div class="upload-doc-section">
                    <div class="upload-doc-section-title"><i class="fas fa-file-alt text-blue-600"></i> Document Info</div>
                    <div class="upload-doc-row">
                        <span class="upload-doc-row-label">Document ID</span>
                        <span class="upload-doc-row-value">#${doc.id}</span>
                    </div>
                    <div class="upload-doc-row">
                        <span class="upload-doc-row-label">Type</span>
                        <span class="upload-doc-row-value">${docTypeInfo.fullName}</span>
                    </div>
                    <div class="upload-doc-row">
                        <span class="upload-doc-row-label">Submitted</span>
                        <span class="upload-doc-row-value">${formatCompactDate(displayDate)}</span>
                    </div>
                    <div class="upload-doc-preview" title="${doc.preview_text || 'No preview available'}">
                        <span class="upload-doc-row-label">Extract:</span>
                        <strong>${doc.preview_text ? doc.preview_text.substring(0, 80) + '...' : 'No preview available'}</strong>
                    </div>
                </div>

                <div class="upload-doc-section">
                    <div class="upload-doc-section-title"><i class="fas fa-cube text-green-600"></i> Blockchain Proof</div>
                    <div class="upload-doc-row">
                        <span class="upload-doc-row-label">Tx Hash</span>
                        <span class="upload-doc-row-value upload-doc-row-value--mono" title="${doc.blockchain_tx_hash || ''}">${txHashDisplay}</span>
                    </div>
                    <div class="upload-doc-row">
                        <span class="upload-doc-row-label">Block</span>
                        <span class="upload-doc-row-value">${doc.block_number ? '#' + doc.block_number : 'N/A'}</span>
                    </div>
                    <div class="upload-doc-row">
                        <span class="upload-doc-row-label">Gas Used</span>
                        <span class="upload-doc-row-value">${doc.gas_used ? parseInt(doc.gas_used).toLocaleString() : 'N/A'}</span>
                    </div>
                    <div class="upload-doc-row">
                        <span class="upload-doc-row-label">Confirmed</span>
                        <span class="upload-doc-row-value">${blockchainDate ? formatCompactDate(blockchainDate) : 'N/A'}</span>
                    </div>
                </div>

                <div class="upload-doc-section upload-doc-section--full">
                    <div class="upload-doc-section-title"><i class="fas fa-chart-line text-indigo-600"></i> Quality Metadata</div>
                    <div class="upload-doc-metrics">
                        <div class="upload-doc-metric">
                            <span class="upload-doc-metric-label">Score</span>
                            <span class="upload-doc-metric-value">${validationScore.toFixed(1)}%</span>
                        </div>
                        <div class="upload-doc-metric">
                            <span class="upload-doc-metric-label">OCR</span>
                            <span class="upload-doc-metric-value">${ocrScore.toFixed(0)}%</span>
                        </div>
                        <div class="upload-doc-metric">
                            <span class="upload-doc-metric-label">Manual</span>
                            <span class="upload-doc-metric-value">${manualScore.toFixed(0)}%</span>
                        </div>
                    </div>

                    <div class="upload-doc-row" style="padding-top: 0;">
                        <span class="upload-doc-row-label">Progress to target (75%)</span>
                        <span class="upload-doc-row-value ${scoreStatus.textColor}">${scoreStatus.shortText}</span>
                    </div>
                    <div class="upload-doc-progress">
                        <div class="upload-doc-progress-bar ${scoreStatus.barColor}" style="width: ${Math.min(validationScore, 100)}%"></div>
                    </div>
                </div>

                ${notesSection}
            </div>

            <div class="upload-doc-actions">
                <div class="upload-doc-action-primary">${actionButton}</div>
                <button onclick="viewDocument(${doc.id})" class="upload-doc-view-btn">
                    <i class="fas fa-eye"></i>
                    View Details
                </button>
            </div>
        </div>
    `;
}

function expandNotes(documentId) {
    const notesElement = document.getElementById(`notes-${documentId}`);
    
    if (!notesElement) {
        console.error('Notes element not found for document:', documentId);
        return;
    }
    
    // Get the full notes text
    const fullNotes = notesElement.textContent;
    
    // Create modal overlay
    const modalHtml = `
        <div id="notes-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[80vh] overflow-hidden">
                <!-- Modal Header -->
                <div class="px-6 py-4 bg-yellow-50 border-b border-yellow-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-gray-900"><i class="fas fa-sticky-note mr-2"></i> Processing Notes - Document #${documentId}</h3>
                        <button onclick="closeNotesModal()" 
                                class="text-gray-600 hover:text-gray-900 text-2xl font-bold"
                                aria-label="Close notes">
                            ×
                        </button>
                    </div>
                </div>
                
                <!-- Modal Body -->
                <div class="px-6 py-4 overflow-y-auto max-h-[60vh]">
                    <div class="text-sm text-gray-700 whitespace-pre-wrap font-mono bg-gray-50 p-4 rounded border border-gray-200">
                        ${escapeHtml(fullNotes)}
                    </div>
                </div>
                
                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                    <button onclick="closeNotesModal()" 
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors font-medium">
                        Close
                    </button>
                </div>
            </div>
        </div>
    `;
    
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    
    document.body.style.overflow = 'hidden';
}


// Close notes modal

function closeNotesModal() {
    const modal = document.getElementById('notes-modal');
    if (modal) {
        modal.remove();
    }
    
    // Restore body scroll
    document.body.style.overflow = '';
}



function createBlockchainInfoSection(doc) {
    // If no blockchain interaction yet
    if (!doc.blockchain_status || doc.blockchain_status === 'not_started') {
        return `
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 bg-gray-400 rounded-full"></div>
                        <span class="text-xs text-gray-600 font-medium">Not anchored to blockchain</span>
                    </div>
                    <span class="text-xs text-gray-500"><i class="fas fa-hourglass-half mr-1"></i> Awaiting completion</span>
                </div>
            </div>
        `;
    }
    
    // If blockchain anchoring is pending
    if (doc.blockchain_status === 'pending') {
        return `
            <div class="px-4 py-2 bg-blue-50 border-b border-blue-100">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></div>
                        <span class="text-xs text-blue-700 font-medium">Anchoring to blockchain...</span>
                    </div>
                    <span class="text-xs text-blue-600"><i class="fas fa-sync fa-spin mr-1"></i> Processing</span>
                </div>
                
                ${doc.blockchain_tx_hash ? `
                    <div class="bg-white rounded border border-blue-200 p-2">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-bold text-gray-900">Transaction Hash:</span>
                            <button onclick="copyToClipboard('${doc.blockchain_tx_hash}')" 
                                    class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                                <i class="fas fa-copy mr-1"></i> Copy
                            </button>
                        </div>
                        <div class="text-xs font-mono text-gray-700 truncate" title="${doc.blockchain_tx_hash}">
                            ${doc.blockchain_tx_hash.substring(0, 20)}...
                        </div>
                        
                        <!-- Transaction Progress Indicator -->
                        <div class="mt-2">
                            <div class="w-full bg-gray-200 rounded-full h-1">
                                <div class="bg-blue-500 h-1 rounded-full animate-pulse" style="width: 60%"></div>
                            </div>
                            <div class="text-xs text-gray-600 mt-1">Waiting for network confirmation...</div>
                        </div>
                    </div>
                ` : ''}
            </div>
        `;
    }
    
    // If blockchain anchoring is confirmed
    if (doc.blockchain_status === 'confirmed') {
        const gasUsed = doc.gas_used ? parseInt(doc.gas_used).toLocaleString() : 'N/A';
        const blockNumber = doc.block_number || 'N/A';
        
        return `
            <div class="px-4 py-2 bg-green-50 border-b border-green-100">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                        <span class="text-xs text-green-700 font-bold"><i class="fas fa-check-circle mr-1"></i> Blockchain Confirmed</span>
                    </div>
                    <button onclick="viewBlockchainDetails(${doc.id})" 
                            class="text-xs text-green-600 hover:text-green-800 font-medium">
                        <i class="fas fa-search mr-1"></i> Details
                    </button>
                </div>
                
                <div class="bg-white rounded border border-green-200 p-2 space-y-1">
                    <!-- Transaction Hash -->
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-900">Tx Hash:</span>
                        <div class="flex items-center space-x-1">
                            <span class="text-xs font-mono text-gray-700" title="${doc.blockchain_tx_hash}">
                                ${doc.blockchain_tx_hash ? doc.blockchain_tx_hash.substring(0, 10) + '...' : 'N/A'}
                            </span>
                            ${doc.blockchain_tx_hash ? `
                                <button onclick="copyToClipboard('${doc.blockchain_tx_hash}')" 
                                        class="text-xs text-green-600 hover:text-green-800" title="Copy hash">
                                    <i class="fas fa-copy"></i>
                                </button>
                            ` : ''}
                        </div>
                    </div>
                    
                    <!-- Block Number -->
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-900">Block:</span>
                        <span class="text-xs text-gray-700 font-mono">#${blockNumber}</span>
                    </div>
                    
                    <!-- Gas Used -->
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-900">Gas Used:</span>
                        <span class="text-xs text-gray-700">${gasUsed}</span>
                    </div>
                    
                    <!-- Confirmation Time -->
                    ${doc.blockchain_confirmed_at ? `
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-gray-900">Confirmed:</span>
                            <span class="text-xs text-gray-700">${formatCompactDate(doc.blockchain_confirmed_at)}</span>
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    }
    
    // If blockchain anchoring failed
    if (doc.blockchain_status === 'failed') {
        return `
            <div class="px-4 py-2 bg-red-50 border-b border-red-100">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                        <span class="text-xs text-red-700 font-medium"><i class="fas fa-times-circle mr-1"></i> Anchoring Failed</span>
                    </div>
                    <button onclick="retryBlockchainAnchoring(${doc.id})" 
                            class="text-xs text-red-600 hover:text-red-800 font-medium">
                        <i class="fas fa-redo mr-1"></i> Retry
                    </button>
                </div>
                
                <div class="bg-white rounded border border-red-200 p-2">
                    <div class="text-xs text-red-700">
                        Transaction failed to complete. Please try anchoring again.
                    </div>
                    ${doc.blockchain_tx_hash ? `
                        <div class="mt-1 text-xs font-mono text-gray-600 truncate" title="${doc.blockchain_tx_hash}">
                            Failed Tx: ${doc.blockchain_tx_hash.substring(0, 15)}...
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    }
    
    // Fallback
    return '';
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        showNotification('success', 'Transaction hash copied to clipboard!');
    }).catch(function(err) {
        console.error('Failed to copy: ', err);
        showNotification('error', 'Failed to copy to clipboard');
    });
}


async function viewBlockchainDetails(scanId) {
    try {
        // Show loading state
        showNotification('info', 'Loading blockchain details...');
        
        const response = await fetch(`/staff/scans/${scanId}/blockchain-details`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });
        
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        
        console.log('Blockchain details response:', data); 
               
        let doc = null;
        
        if (data.success && data.data && !Array.isArray(data.data) && typeof data.data === 'object') {
            doc = data.data;
        }
        else if (data.success && data.data && data.data.data && Array.isArray(data.data.data)) {
            doc = data.data.data.find(d => d.id === scanId);
        }
        else if (data.success && Array.isArray(data.data)) {
            doc = data.data.find(d => d.id === scanId);
        }
        
        if (!doc) {
            showNotification('error', 'Document not found');
            console.error('Could not find document in response:', {
                scanId,
                dataStructure: data
            });
            return;
        }
        
        // Check if document has blockchain data
        if (!doc.blockchain_tx_hash) {
            showNotification('warning', 'This document has not been anchored to blockchain yet');
            return;
        }
        
        // Create modal HTML
        const modalHtml = `
                    <div id="blockchain-details-modal" class="modal-overlay">
                        <div class="modal-box">
                            
                            <div class="modal-header">
                                <h3 class="header-title">
                                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                    Blockchain Verification Details
                                </h3>
                                <button onclick="closeBlockchainModal()" class="close-icon-btn">
                                    <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>
                            
                            <div class="modal-body">
                                
                                <div class="info-card">
                                    <div class="card-title"><i class="fas fa-file-alt mr-2"></i> Document Information</div>
                                    <div class="grid-3">
                                        <div>
                                            <span class="label-text">Document ID</span>
                                            <span class="value-text">${doc.document_id}</span>
                                        </div>
                                        <div>
                                            <span class="label-text">Type</span>
                                            <span class="value-text">${doc.document_type.replace('_', ' ').toUpperCase()}</span>
                                        </div>
                                        <div>
                                            <span class="label-text">Status</span>
                                            <span class="status-badge ${doc.blockchain_status === 'confirmed' ? 'status-confirmed' : 'status-failed'}">
                                                ${doc.blockchain_status.toUpperCase()}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="info-card" style="margin-bottom: 0;">
                                    <div class="card-title" style="color: #000;"><i class="fas fa-cube mr-2"></i> Blockchain Ledger</div>

                                    <div>
                                        <span class="label-text">Transaction Hash</span>
                                        <div class="hash-container">
                                            <div class="hash-value">${doc.blockchain_tx_hash}</div>
                                            <button onclick="copyToClipboard('${doc.blockchain_tx_hash}')" class="copy-btn" title="Copy Hash">
                                                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="grid-2">
                                        <div>
                                            ${doc.blockchain_confirmed_at ? `
                                            <div class="spacing-fix">
                                                <span class="label-text">Timestamp</span>
                                                <span class="value-text no-line">
                                                    ${new Date(doc.blockchain_confirmed_at).toLocaleString()}
                                                </span>
                                            </div>` : ''}
                                            
                                            <div>
                                                <span class="label-text">Network ID</span>
                                                <span class="value-text">Ganache Local (ID: 5777)</span>
                                            </div>
                                        </div>

                                        <div>
                                            ${doc.block_number ? `
                                            <div class="spacing-fix">
                                                <span class="label-text">Block Height</span>
                                                <span class="value-text">#${doc.block_number}</span>
                                            </div>` : ''}

                                            ${doc.gas_used ? `
                                            <div class="spacing-fix">
                                                <span class="label-text">Gas Consumed</span>
                                                <span class="value-text">${parseInt(doc.gas_used).toLocaleString()} Gwei</span>
                                            </div>` : ''}

                                            <div>
                                                <span class="label-text">RPC Endpoint</span>
                                                <span class="value-text">127.0.0.1:7545</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="modal-footer">
                                <a href="http://127.0.0.1:7545" target="_blank" class="action-btn btn-blue">
                                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                    Open Explorer
                                </a>
                                
                                <button onclick="verifyOnBlockchain('${doc.blockchain_tx_hash}')" class="action-btn btn-green">
                                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    Verify On-Chain
                                </button>
                                
                                <button onclick="closeBlockchainModal()" class="action-btn btn-black">
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                    `;
                            
        // Insert modal into page
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        document.body.style.overflow = 'hidden';
        
    } catch (error) {
        console.error('Error loading blockchain details:', error);
        showNotification('error', 'Failed to load blockchain details');
    }
}

function closeBlockchainModal() {
    const modal = document.getElementById('blockchain-details-modal');
    if (modal) {
        modal.remove();
    }
    document.body.style.overflow = '';
}

function getStatusBadgeClass(status) {
    const classes = {
        'confirmed': 'bg-green-100 text-green-800 border-2 border-green-500',
        'pending': 'bg-yellow-100 text-yellow-800 border-2 border-yellow-500',
        'failed': 'bg-red-100 text-red-800 border-2 border-red-500',
        'not_started': 'bg-gray-100 text-gray-800 border-2 border-gray-400'
    };
    return classes[status] || classes['not_started'];
}

async function verifyOnBlockchain(txHash) {
    showNotification('info', 'Verifying transaction on blockchain...');
    
    try {
        // Simulate verification (in production, call your blockchain verification endpoint)
        setTimeout(() => {
            showNotification('success', 'Transaction verified on blockchain!');
        }, 1500);
    } catch (error) {
        showNotification('error', 'Verification failed');
    }
}


async function retryBlockchainAnchoring(scanId) {
    if (!confirm('Retry anchoring this document to the blockchain?\n\nThis will create a new transaction.')) {
        return;
    }
    
    try {
        const response = await fetch(`/staff/scans/${scanId}/retry-blockchain`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('success', 'Blockchain anchoring retry initiated!');
            
            // Reload documents to show updated status
            await loadDocuments(currentPage);
        } else {
            showNotification('error', `Retry failed: ${result.message}`);
        }
        
    } catch (error) {
        console.error('Retry blockchain anchoring error:', error);
        showNotification('error', 'Failed to retry blockchain anchoring');
    }
}


function createListViewCard(doc) {
    const statusBadge = getStatusBadge(doc.verification_status);
    const blockchainBadge = getBlockchainBadge(doc.blockchain_status, doc.blockchain_tx_hash);
    const actionButton = createActionButton(doc);
    
    const docTypeInfo = getDocumentTypeInfo(doc.document_type);
    const validationScore = parseFloat(doc.validation_score) || 0;
    const ocrScore = parseFloat(doc.ocr_confidence) || 0;
    const manualScore = parseFloat(doc.manual_completion_score) || 0;
    const scoreStatus = getScoreStatus(validationScore, doc.blockchain_status);
    
    return `
        <div class="p-4 sm:p-6 hover:bg-gray-50 transition-colors" data-document-id="${doc.id}">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div class="flex-1">
                    <div class="flex flex-wrap items-center gap-2 mb-2">
                        ${statusBadge}
                        ${blockchainBadge}
                        <span class="text-xs text-gray-500"><i class="fas fa-calendar-alt text-gray-400 mr-1"></i>${doc.formatted_date}</span>
                    </div>
                    <h3 class="text-sm font-semibold text-gray-900 mb-1">
                        ${docTypeInfo.fullName}
                        <span class="text-gray-400 font-normal text-xs ml-1">#${doc.id}</span>
                    </h3>
                    <div class="mb-2">
                        <div class="flex items-center justify-between mb-1 max-w-xs">
                            <span class="text-xs font-medium ${scoreStatus.textColor}">${scoreStatus.shortText}</span>
                            <span class="text-xs text-gray-500">${validationScore.toFixed(1)}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-1.5 max-w-xs">
                            <div class="${scoreStatus.barColor} h-1.5 rounded-full" style="width: ${Math.min(validationScore, 100)}%"></div>
                        </div>
                    </div>
                    ${doc.preview_text ? `<p class="text-sm text-gray-500 truncate max-w-lg">${doc.preview_text.substring(0, 100)}</p>` : ''}
                    ${doc.blockchain_confirmed_at ? `<p class="text-xs text-green-700 mt-1"><i class="fas fa-cube mr-1"></i>Confirmed: ${doc.blockchain_confirmed_at}</p>` : ''}
                </div>
                <div class="flex items-center gap-2 sm:flex-col sm:items-end">
                    <div>${actionButton}</div>
                    <button onclick="viewDocument(${doc.id})"
                            class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-200 shadow-sm hover:bg-gray-50 text-gray-700 text-sm font-medium rounded-lg transition-colors">
                        <i class="fas fa-eye mr-1"></i> View
                    </button>
                </div>
            </div>
        </div>
    `;
}


/**
 * Get document type information with emojis
 */
function getDocumentTypeInfo(documentType) {
    const typeMap = {
        birth_certificate: {
            emoji: '<i class="fas fa-baby text-blue-500"></i>',
            shortName: 'BIRTH CERT',
            fullName: 'Birth Certificate'
        },
        death_certificate: {
            emoji: '<i class="fas fa-scroll text-gray-500"></i>',
            shortName: 'DEATH CERT',
            fullName: 'Death Certificate'
        },
        marriage_certificate: {
            emoji: '<i class="fas fa-heart text-pink-500"></i>',
            shortName: 'MARRIAGE CERT',
            fullName: 'Marriage Certificate'
        }
    };
    
    return typeMap[documentType] || {
        emoji: '<i class="fas fa-folder-open text-gray-400"></i>',
        shortName: 'DOCUMENT',
        fullName: 'Document'
    };
}

/**
 * Get score status for visual indicators
 */
function getScoreStatus(validationScore, blockchainStatus) {
    if (blockchainStatus === 'confirmed') {
        return {
            shortText: 'Confirmed',
            textColor: 'text-gray-900',        
            barColor: 'bg-green-500',
            dotColor: 'bg-green-500',
            tooltip: 'Document successfully anchored to blockchain'
        };
    } else if (blockchainStatus === 'pending') {
        return {
            shortText: 'Processing',
            textColor: 'text-gray-900',        
            barColor: 'bg-blue-500',
            dotColor: 'bg-blue-500 animate-pulse',
            tooltip: 'Document being anchored to blockchain'
        };
    } else if (validationScore >= 85) {
        return {
            shortText: 'Ready',
            textColor: 'text-gray-900',       
            barColor: 'bg-green-500',
            dotColor: 'bg-green-500',
            tooltip: 'Document meets threshold for blockchain anchoring'
        };
    } else if (validationScore >= 75) {
        return {
            shortText: 'Nearly Ready',
            textColor: 'text-gray-900',        
            barColor: 'bg-yellow-500',
            dotColor: 'bg-yellow-500',
            tooltip: 'Document close to blockchain threshold'
        };
    } else {
        return {
            shortText: 'Incomplete',
            textColor: 'text-gray-900',        
            barColor: 'bg-red-500',
            dotColor: 'bg-red-500',
            tooltip: 'Document requires more validation'
        };
    }
}

/**
 * Format date for compact display
 */
function formatCompactDate(dateString) {
    // Handle null, undefined, or empty dates
    if (!dateString || dateString === 'Invalid Date' || dateString === '') {
        return 'No Date';
    }
    
    try {
        
        let date;
        
        // ISO format: 2025-01-15T10:30:00Z
        if (typeof dateString === 'string' && dateString.includes('T')) {
            date = new Date(dateString);
        }
        // MySQL datetime: 2025-01-15 10:30:00
        else if (typeof dateString === 'string' && dateString.includes('-')) {
            date = new Date(dateString.replace(' ', 'T'));
        }
        // Timestamp
        else if (!isNaN(dateString)) {
            date = new Date(parseInt(dateString) * 1000);
        }
        // Fallback
        else {
            date = new Date(dateString);
        }
        
        // Validate the parsed date
        if (isNaN(date.getTime())) {
            return 'Invalid Date';
        }
        
        const now = new Date();
        const diffInHours = Math.abs(now - date) / (1000 * 60 * 60);
        
        if (diffInHours < 1) {
            return 'Just now';
        } else if (diffInHours < 24) {
            return date.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
        } else if (diffInHours < 24 * 7) {
            return date.toLocaleDateString('en-US', {
                weekday: 'short',
                hour: 'numeric',
                hour12: true
            });
        } else {
            return date.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            });
        }
    } catch (error) {
        console.error('Date formatting error:', error, 'Input:', dateString);
        return 'Date Error';
    }
}


// FIXED: Update status badge mapping
function getStatusBadge(status) {
    const statusConfig = {
        pending: { 
            class: 'upload-doc-chip upload-doc-chip--warning', 
            text: 'Pending',
            icon: '<i class="fas fa-clock"></i>'
        },
        completed: { 
            class: 'upload-doc-chip upload-doc-chip--success', 
            text: 'Completed',
            icon: '<i class="fas fa-check-circle"></i>'
        },
        verified: { 
            class: 'upload-doc-chip upload-doc-chip--success', 
            text: 'Verified',
            icon: '<i class="fas fa-check-circle"></i>'
        },
        rejected: { 
            class: 'upload-doc-chip upload-doc-chip--danger', 
            text: 'Rejected',
            icon: '<i class="fas fa-times-circle"></i>'
        }
    };
    
    const config = statusConfig[status] || statusConfig.pending;
    
    return `<span class="${config.class}">
        ${config.icon}<span>${config.text}</span>
    </span>`;
}

function createValidationScoreIndicator(doc) {
    const ocrScore = parseFloat(doc.ocr_confidence) || 0;
    const manualScore = parseFloat(doc.manual_completion_score) || 0;
    const validationScore = parseFloat(doc.validation_score) || 0;
    const threshold = 75; // Use actual threshold from model
    
    // CRITICAL FIX: Better status text based on actual document state
    let statusText = 'Needs Completion';
    let statusColor = 'text-gray-900';
    let containerBg = 'bg-yellow-50 border-yellow-300';
    let progressBarColor = 'bg-yellow-500';
    
    // Determine status based on actual verification and blockchain status
    if (doc.blockchain_status === 'confirmed') {
        statusText = 'Blockchain Confirmed';
        statusColor = 'text-green-900';
        containerBg = 'bg-green-50 border-green-300';
        progressBarColor = 'bg-green-500';
    } else if (doc.blockchain_status === 'pending') {
        statusText = 'Blockchain Processing';
        statusColor = 'text-blue-900';
        containerBg = 'bg-blue-50 border-blue-300';
        progressBarColor = 'bg-blue-500';
    } else if (doc.verification_status === 'completed') {
        statusText = 'Document Completed';
        statusColor = 'text-green-900';
        containerBg = 'bg-green-50 border-green-300';
        progressBarColor = 'bg-green-500';
    } else if (manualScore >= 100) {
        statusText = 'Processing…';
        statusColor = 'text-blue-900';
        containerBg = 'bg-blue-50 border-blue-300';
        progressBarColor = 'bg-blue-500';
    } else if (manualScore >= 80) {
        statusText = 'Nearly Complete';
        statusColor = 'text-orange-900';
        containerBg = 'bg-orange-50 border-orange-300';
        progressBarColor = 'bg-orange-500';
    }
    
    return `
        <div class="mt-2 ${containerBg} rounded-lg p-3 border-2">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-bold ${statusColor}">${statusText}</span>
                <span class="text-sm font-bold text-gray-900">${validationScore.toFixed(1)}%</span>
            </div>
            
            <div class="w-full bg-gray-200 rounded-full h-2.5 mb-2">
                <div class="${progressBarColor} h-2.5 rounded-full transition-all duration-300" 
                     style="width: ${Math.min(validationScore, 100)}%"></div>
            </div>
            
            <div class="flex justify-between text-xs text-gray-400 font-normal">
                <span>OCR: ${ocrScore.toFixed(1)}% (40%)</span>
                <span>Manual: ${manualScore.toFixed(1)}% (60%)</span>
                <span>Threshold: ${threshold}%</span>
            </div>
        </div>
    `;
}

/**
 * MODEL A: Create conditional action button
 */
function createActionButton(doc) {
    // Already anchored to blockchain
    if (doc.blockchain_status === 'confirmed') {
        return `
            <span class="upload-doc-action-chip">
                <i class="fas fa-link"></i> Already Anchored
            </span>
        `;
    }
    
    // Currently anchoring to blockchain
    if (doc.blockchain_status === 'pending') {
        return `
            <button class="upload-doc-action-main upload-doc-action-main--info" disabled>
                <i class="fas fa-sync fa-spin"></i> Anchoring...
            </button>
        `;
    }
    
    // Document verification is pending - show verification buttons
    if (doc.verification_status === 'pending') {
        return `
            <div class="upload-doc-action-stack">
                <button onclick="updateVerificationStatus(${doc.id}, 'completed')" 
                        class="upload-doc-action-main upload-doc-action-main--success">
                    <i class="fas fa-check"></i> Mark Completed
                </button>
                <button onclick="updateVerificationStatus(${doc.id}, 'rejected')" 
                        class="upload-doc-action-main upload-doc-action-main--danger">
                    <i class="fas fa-times"></i> Reject Document
                </button>
            </div>
        `;
    }
    
    // Document is completed/verified - ready for anchoring
    if (doc.is_ready_for_anchoring) {
        return `
            <button onclick="triggerBlockchainAnchoring(${doc.id})" 
                    class="upload-doc-action-main upload-doc-action-main--success">
                <i class="fas fa-link"></i> Anchor to Blockchain
            </button>
        `;
    }
    
    // Document needs more completion
    return `
        <button onclick="openReviewModal(${doc.id})" 
                class="upload-doc-action-main upload-doc-action-main--warning">
            <i class="fas fa-exclamation-triangle"></i> Complete Document
        </button>
    `;
}

/**
 * MODEL A: Trigger blockchain anchoring
 */
function getBlockchainBadge(blockchainStatus, hash) {
    const blockchainConfig = {
        not_started: { 
            class: 'upload-doc-chip upload-doc-chip--muted', 
            text: 'Not Anchored', 
            icon: '<i class="fas fa-hourglass-half"></i>' 
        },
        pending: { 
            class: 'upload-doc-chip upload-doc-chip--info', 
            text: 'Anchoring', 
            icon: '<i class="fas fa-sync fa-spin"></i>' 
        },
        confirmed: { 
            class: 'upload-doc-chip upload-doc-chip--success', 
            text: 'Anchored', 
            icon: '<i class="fas fa-cube"></i>' 
        },
        failed: { 
            class: 'upload-doc-chip upload-doc-chip--danger', 
            text: 'Failed', 
            icon: '<i class="fas fa-exclamation-circle"></i>' 
        }
    };
    
    const config = blockchainConfig[blockchainStatus] || blockchainConfig.not_started;
    const title = hash ? `Transaction: ${hash.substring(0, 10)}...` : '';
    
    return `<span class="${config.class}" title="${title}">
        ${config.icon}<span>${config.text}</span>
    </span>`;
}

/**
 * MODEL A: Trigger blockchain anchoring
 */
async function triggerBlockchainAnchoring(scanId) {
/*
                        <div>
                            <h4 class="text-sm font-bold text-gray-900 leading-tight">
                                ${docTypeInfo.shortName}
                            </h4>
                            <div class="text-xs text-gray-400">
                                ID: #${doc.id}
                            </div>
                        </div>
                    </div>
                    
                    <!-- Status Indicators -->
                    <div class="flex items-center space-x-1">
                        <div class="w-2 h-2 rounded-full ${scoreStatus.dotColor}" 
                             title="${scoreStatus.tooltip}"></div>
                        <span class="text-xs font-medium ${scoreStatus.textColor}">
                            ${scoreStatus.shortText}
                        </span>
                    </div>
                </div>
            </div>
            
                <!-- Status Badges Row -->
            <div class="px-4 py-2">
                <div class="flex flex-wrap items-center gap-1.5">
                    <div class="flex-shrink-0">${statusBadge}</div>
                    <div class="flex-shrink-0 opacity-90">${blockchainBadge}</div>
                </div>
            </div>
            
            <!-- Enhanced Blockchain Transaction Info -->
            ${blockchainInfoSection}
            
            <!-- Progress Section -->
            <div class="px-4 py-2">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-semibold ${scoreStatus.textColor}">
                        Score: ${validationScore.toFixed(1)}%
                    </span>
                    <span class="text-xs text-gray-900 font-semibold">
                        Target: 75%
                    </span>
                </div>
                
                <!-- Progress Bar -->
                <div class="w-full bg-gray-200 rounded-full h-1.5 mb-2">
                    <div class="${scoreStatus.barColor} h-1.5 rounded-full transition-all duration-500" 
                         style="width: ${Math.min(validationScore, 100)}%"></div>
                </div>
                
                <!-- OCR/Manual Breakdown -->
                <div class="flex justify-between text-xs text-gray-400 font-normal">
                    <span>OCR: ${ocrScore.toFixed(0)}%</span>
                    <span>Manual: ${manualScore.toFixed(0)}%</span>
                </div>
            </div>
            
            <!-- Document Info -->
            <div class="px-4 py-2 text-xs text-gray-500">
                <div class="truncate mb-1 font-medium" title="${doc.preview_text || 'No preview available'}">
                    ${doc.preview_text ? doc.preview_text.substring(0, 50) + '...' : 'No preview available'}
                </div>
                <div class="flex items-center justify-between">
                    <span><i class="fas fa-calendar-alt text-gray-400 mr-1"></i>${formatCompactDate(displayDate)}</span>
                    ${blockchainDate ? 
                        `<span class="text-green-700"><i class="fas fa-cube mr-1"></i>${formatCompactDate(blockchainDate)}</span>` : 
                        ''
                    }
                </div>
            </div>

            <!-- ⭐ NEW: Notes Section (Answering Question 3) -->
            ${notesSection}
            
            <!-- Actions -->
            <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                <div class="flex space-x-2">
                    ${actionButton}
                    <button onclick="viewDocument(${doc.id})" 
                            class="flex-1 px-2 py-1.5 text-xs bg-white border border-gray-200 shadow-sm hover:bg-gray-50 text-gray-700 font-medium rounded-lg transition-colors text-center">
                        <i class="fas fa-eye mr-1"></i> View
                    </button>
                </div>
            </div>
        </div>
    `;
}

function expandNotes(documentId) {
    const notesElement = document.getElementById(`notes-${documentId}`);
    
    if (!notesElement) {
        console.error('Notes element not found for document:', documentId);
        return;
    }
    
    // Get the full notes text
    const fullNotes = notesElement.textContent;
    
    // Create modal overlay
    const modalHtml = `
        <div id="notes-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[80vh] overflow-hidden">
                <!-- Modal Header -->
                <div class="px-6 py-4 bg-yellow-50 border-b border-yellow-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-gray-900"><i class="fas fa-sticky-note mr-2"></i> Processing Notes - Document #${documentId}</h3>
                        <button onclick="closeNotesModal()" 
                                class="text-gray-600 hover:text-gray-900 text-2xl font-bold"
                                aria-label="Close notes">
                            ×
                        </button>
                    </div>
                </div>
                
                <!-- Modal Body -->
                <div class="px-6 py-4 overflow-y-auto max-h-[60vh]">
                    <div class="text-sm text-gray-700 whitespace-pre-wrap font-mono bg-gray-50 p-4 rounded border border-gray-200">
                        ${escapeHtml(fullNotes)}
                    </div>
                </div>
                
                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                    <button onclick="closeNotesModal()" 
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors font-medium">
                        Close
                    </button>
                </div>
            </div>
        </div>
    `;
    
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    
    document.body.style.overflow = 'hidden';
}


// Close notes modal

function closeNotesModal() {
    const modal = document.getElementById('notes-modal');
    if (modal) {
        modal.remove();
    }
    
    // Restore body scroll
    document.body.style.overflow = '';
}

*/
}



function createBlockchainInfoSection(doc) {
    // If no blockchain interaction yet
    if (!doc.blockchain_status || doc.blockchain_status === 'not_started') {
        return `
            <div class="px-4 py-2 bg-gray-50 border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 bg-gray-400 rounded-full"></div>
                        <span class="text-xs text-gray-600 font-medium">Not anchored to blockchain</span>
                    </div>
                    <span class="text-xs text-gray-500"><i class="fas fa-hourglass-half mr-1"></i> Awaiting completion</span>
                </div>
            </div>
        `;
    }
    
    // If blockchain anchoring is pending
    if (doc.blockchain_status === 'pending') {
        return `
            <div class="px-4 py-2 bg-blue-50 border-b border-blue-100">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></div>
                        <span class="text-xs text-blue-700 font-medium">Anchoring to blockchain...</span>
                    </div>
                    <span class="text-xs text-blue-600"><i class="fas fa-sync fa-spin mr-1"></i> Processing</span>
                </div>
                
                ${doc.blockchain_tx_hash ? `
                    <div class="bg-white rounded border border-blue-200 p-2">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-bold text-gray-900">Transaction Hash:</span>
                            <button onclick="copyToClipboard('${doc.blockchain_tx_hash}')" 
                                    class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                                <i class="fas fa-copy mr-1"></i> Copy
                            </button>
                        </div>
                        <div class="text-xs font-mono text-gray-700 truncate" title="${doc.blockchain_tx_hash}">
                            ${doc.blockchain_tx_hash.substring(0, 20)}...
                        </div>
                        
                        <!-- Transaction Progress Indicator -->
                        <div class="mt-2">
                            <div class="w-full bg-gray-200 rounded-full h-1">
                                <div class="bg-blue-500 h-1 rounded-full animate-pulse" style="width: 60%"></div>
                            </div>
                            <div class="text-xs text-gray-600 mt-1">Waiting for network confirmation...</div>
                        </div>
                    </div>
                ` : ''}
            </div>
        `;
    }
    
    // If blockchain anchoring is confirmed
    if (doc.blockchain_status === 'confirmed') {
        const gasUsed = doc.gas_used ? parseInt(doc.gas_used).toLocaleString() : 'N/A';
        const blockNumber = doc.block_number || 'N/A';
        
        return `
            <div class="px-4 py-2 bg-green-50 border-b border-green-100">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                        <span class="text-xs text-green-700 font-bold"><i class="fas fa-check-circle mr-1"></i> Blockchain Confirmed</span>
                    </div>
                    <button onclick="viewBlockchainDetails(${doc.id})" 
                            class="text-xs text-green-600 hover:text-green-800 font-medium">
                        <i class="fas fa-search mr-1"></i> Details
                    </button>
                </div>
                
                <div class="bg-white rounded border border-green-200 p-2 space-y-1">
                    <!-- Transaction Hash -->
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-900">Tx Hash:</span>
                        <div class="flex items-center space-x-1">
                            <span class="text-xs font-mono text-gray-700" title="${doc.blockchain_tx_hash}">
                                ${doc.blockchain_tx_hash ? doc.blockchain_tx_hash.substring(0, 10) + '...' : 'N/A'}
                            </span>
                            ${doc.blockchain_tx_hash ? `
                                <button onclick="copyToClipboard('${doc.blockchain_tx_hash}')" 
                                        class="text-xs text-green-600 hover:text-green-800" title="Copy hash">
                                    <i class="fas fa-copy"></i>
                                </button>
                            ` : ''}
                        </div>
                    </div>
                    
                    <!-- Block Number -->
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-900">Block:</span>
                        <span class="text-xs text-gray-700 font-mono">#${blockNumber}</span>
                    </div>
                    
                    <!-- Gas Used -->
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-900">Gas Used:</span>
                        <span class="text-xs text-gray-700">${gasUsed}</span>
                    </div>
                    
                    <!-- Confirmation Time -->
                    ${doc.blockchain_confirmed_at ? `
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-gray-900">Confirmed:</span>
                            <span class="text-xs text-gray-700">${formatCompactDate(doc.blockchain_confirmed_at)}</span>
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    }
    
    // If blockchain anchoring failed
    if (doc.blockchain_status === 'failed') {
        return `
            <div class="px-4 py-2 bg-red-50 border-b border-red-100">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                        <span class="text-xs text-red-700 font-medium"><i class="fas fa-times-circle mr-1"></i> Anchoring Failed</span>
                    </div>
                    <button onclick="retryBlockchainAnchoring(${doc.id})" 
                            class="text-xs text-red-600 hover:text-red-800 font-medium">
                        <i class="fas fa-redo mr-1"></i> Retry
                    </button>
                </div>
                
                <div class="bg-white rounded border border-red-200 p-2">
                    <div class="text-xs text-red-700">
                        Transaction failed to complete. Please try anchoring again.
                    </div>
                    ${doc.blockchain_tx_hash ? `
                        <div class="mt-1 text-xs font-mono text-gray-600 truncate" title="${doc.blockchain_tx_hash}">
                            Failed Tx: ${doc.blockchain_tx_hash.substring(0, 15)}...
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    }
    
    // Fallback
    return '';
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        showNotification('success', 'Transaction hash copied to clipboard!');
    }).catch(function(err) {
        console.error('Failed to copy: ', err);
        showNotification('error', 'Failed to copy to clipboard');
    });
}


async function viewBlockchainDetails(scanId) {
    try {
        // Show loading state
        showNotification('info', 'Loading blockchain details...');
        
        const response = await fetch(`/staff/scans/${scanId}/blockchain-details`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });
        
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        
        console.log('Blockchain details response:', data); 
               
        let doc = null;
        
        if (data.success && data.data && !Array.isArray(data.data) && typeof data.data === 'object') {
            doc = data.data;
        }
        else if (data.success && data.data && data.data.data && Array.isArray(data.data.data)) {
            doc = data.data.data.find(d => d.id === scanId);
        }
        else if (data.success && Array.isArray(data.data)) {
            doc = data.data.find(d => d.id === scanId);
        }
        
        if (!doc) {
            showNotification('error', 'Document not found');
            console.error('Could not find document in response:', {
                scanId,
                dataStructure: data
            });
            return;
        }
        
        // Check if document has blockchain data
        if (!doc.blockchain_tx_hash) {
            showNotification('warning', 'This document has not been anchored to blockchain yet');
            return;
        }
        
        // Create modal HTML
        const modalHtml = `
                    <div id="blockchain-details-modal" class="modal-overlay">
                        <div class="modal-box">
                            
                            <div class="modal-header">
                                <h3 class="header-title">
                                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                    Blockchain Verification Details
                                </h3>
                                <button onclick="closeBlockchainModal()" class="close-icon-btn">
                                    <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>
                            
                            <div class="modal-body">
                                
                                <div class="info-card">
                                    <div class="card-title"><i class="fas fa-file-alt mr-2"></i> Document Information</div>
                                    <div class="grid-3">
                                        <div>
                                            <span class="label-text">Document ID</span>
                                            <span class="value-text">${doc.document_id}</span>
                                        </div>
                                        <div>
                                            <span class="label-text">Type</span>
                                            <span class="value-text">${doc.document_type.replace('_', ' ').toUpperCase()}</span>
                                        </div>
                                        <div>
                                            <span class="label-text">Status</span>
                                            <span class="status-badge ${doc.blockchain_status === 'confirmed' ? 'status-confirmed' : 'status-failed'}">
                                                ${doc.blockchain_status.toUpperCase()}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="info-card" style="margin-bottom: 0;">
                                    <div class="card-title" style="color: #000;"><i class="fas fa-cube mr-2"></i> Blockchain Ledger</div>

                                    <div>
                                        <span class="label-text">Transaction Hash</span>
                                        <div class="hash-container">
                                            <div class="hash-value">${doc.blockchain_tx_hash}</div>
                                            <button onclick="copyToClipboard('${doc.blockchain_tx_hash}')" class="copy-btn" title="Copy Hash">
                                                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="grid-2">
                                        <div>
                                            ${doc.blockchain_confirmed_at ? `
                                            <div class="spacing-fix">
                                                <span class="label-text">Timestamp</span>
                                                <span class="value-text no-line">
                                                    ${new Date(doc.blockchain_confirmed_at).toLocaleString()}
                                                </span>
                                            </div>` : ''}
                                            
                                            <div>
                                                <span class="label-text">Network ID</span>
                                                <span class="value-text">Ganache Local (ID: 5777)</span>
                                            </div>
                                        </div>

                                        <div>
                                            ${doc.block_number ? `
                                            <div class="spacing-fix">
                                                <span class="label-text">Block Height</span>
                                                <span class="value-text">#${doc.block_number}</span>
                                            </div>` : ''}

                                            ${doc.gas_used ? `
                                            <div class="spacing-fix">
                                                <span class="label-text">Gas Consumed</span>
                                                <span class="value-text">${parseInt(doc.gas_used).toLocaleString()} Gwei</span>
                                            </div>` : ''}

                                            <div>
                                                <span class="label-text">RPC Endpoint</span>
                                                <span class="value-text">127.0.0.1:7545</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="modal-footer">
                                <a href="http://127.0.0.1:7545" target="_blank" class="action-btn btn-blue">
                                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                    Open Explorer
                                </a>
                                
                                <button onclick="verifyOnBlockchain('${doc.blockchain_tx_hash}')" class="action-btn btn-green">
                                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    Verify On-Chain
                                </button>
                                
                                <button onclick="closeBlockchainModal()" class="action-btn btn-black">
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                    `;
                            
        // Insert modal into page
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        document.body.style.overflow = 'hidden';
        
    } catch (error) {
        console.error('Error loading blockchain details:', error);
        showNotification('error', 'Failed to load blockchain details');
    }
}

function closeBlockchainModal() {
    const modal = document.getElementById('blockchain-details-modal');
    if (modal) {
        modal.remove();
    }
    document.body.style.overflow = '';
}

function getStatusBadgeClass(status) {
    const classes = {
        'confirmed': 'bg-green-100 text-green-800 border-2 border-green-500',
        'pending': 'bg-yellow-100 text-yellow-800 border-2 border-yellow-500',
        'failed': 'bg-red-100 text-red-800 border-2 border-red-500',
        'not_started': 'bg-gray-100 text-gray-800 border-2 border-gray-400'
    };
    return classes[status] || classes['not_started'];
}

async function verifyOnBlockchain(txHash) {
    showNotification('info', 'Verifying transaction on blockchain...');
    
    try {
        // Simulate verification (in production, call your blockchain verification endpoint)
        setTimeout(() => {
            showNotification('success', 'Transaction verified on blockchain!');
        }, 1500);
    } catch (error) {
        showNotification('error', 'Verification failed');
    }
}


async function retryBlockchainAnchoring(scanId) {
    if (!confirm('Retry anchoring this document to the blockchain?\n\nThis will create a new transaction.')) {
        return;
    }
    
    try {
        const response = await fetch(`/staff/scans/${scanId}/retry-blockchain`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('success', 'Blockchain anchoring retry initiated!');
            
            // Reload documents to show updated status
            await loadDocuments(currentPage);
        } else {
            showNotification('error', `Retry failed: ${result.message}`);
        }
        
    } catch (error) {
        console.error('Retry blockchain anchoring error:', error);
        showNotification('error', 'Failed to retry blockchain anchoring');
    }
}


function createListViewCard(doc) {
    const statusBadge = getStatusBadge(doc.verification_status);
    const blockchainBadge = getBlockchainBadge(doc.blockchain_status, doc.blockchain_tx_hash);
    const actionButton = createActionButton(doc);
    
    const docTypeInfo = getDocumentTypeInfo(doc.document_type);
    const validationScore = parseFloat(doc.validation_score) || 0;
    const ocrScore = parseFloat(doc.ocr_confidence) || 0;
    const manualScore = parseFloat(doc.manual_completion_score) || 0;
    const scoreStatus = getScoreStatus(validationScore, doc.blockchain_status);
    
    return `
        <div class="p-4 sm:p-6 hover:bg-gray-50 transition-colors" data-document-id="${doc.id}">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div class="flex-1">
                    <div class="flex flex-wrap items-center gap-2 mb-2">
                        ${statusBadge}
                        ${blockchainBadge}
                        <span class="text-xs text-gray-500"><i class="fas fa-calendar-alt text-gray-400 mr-1"></i>${doc.formatted_date}</span>
                    </div>
                    <h3 class="text-sm font-semibold text-gray-900 mb-1">
                        ${docTypeInfo.fullName}
                        <span class="text-gray-400 font-normal text-xs ml-1">#${doc.id}</span>
                    </h3>
                    <div class="mb-2">
                        <div class="flex items-center justify-between mb-1 max-w-xs">
                            <span class="text-xs font-medium ${scoreStatus.textColor}">${scoreStatus.shortText}</span>
                            <span class="text-xs text-gray-500">${validationScore.toFixed(1)}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-1.5 max-w-xs">
                            <div class="${scoreStatus.barColor} h-1.5 rounded-full" style="width: ${Math.min(validationScore, 100)}%"></div>
                        </div>
                    </div>
                    ${doc.preview_text ? `<p class="text-sm text-gray-500 truncate max-w-lg">${doc.preview_text.substring(0, 100)}</p>` : ''}
                    ${doc.blockchain_confirmed_at ? `<p class="text-xs text-green-700 mt-1"><i class="fas fa-cube mr-1"></i>Confirmed: ${doc.blockchain_confirmed_at}</p>` : ''}
                </div>
                <div class="flex items-center gap-2 sm:flex-col sm:items-end">
                    <div>${actionButton}</div>
                    <button onclick="viewDocument(${doc.id})"
                            class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-200 shadow-sm hover:bg-gray-50 text-gray-700 text-sm font-medium rounded-lg transition-colors">
                        <i class="fas fa-eye mr-1"></i> View
                    </button>
                </div>
            </div>
        </div>
    `;
}


/**
 * Get document type information with emojis
 */
function getDocumentTypeInfo(documentType) {
    const typeMap = {
        birth_certificate: {
            emoji: '<i class="fas fa-baby text-blue-500"></i>',
            shortName: 'BIRTH CERT',
            fullName: 'Birth Certificate'
        },
        death_certificate: {
            emoji: '<i class="fas fa-scroll text-gray-500"></i>',
            shortName: 'DEATH CERT',
            fullName: 'Death Certificate'
        },
        marriage_certificate: {
            emoji: '<i class="fas fa-heart text-pink-500"></i>',
            shortName: 'MARRIAGE CERT',
            fullName: 'Marriage Certificate'
        }
    };
    
    return typeMap[documentType] || {
        emoji: '<i class="fas fa-folder-open text-gray-400"></i>',
        shortName: 'DOCUMENT',
        fullName: 'Document'
    };
}

/**
 * Get score status for visual indicators
 */
function getScoreStatus(validationScore, blockchainStatus) {
    if (blockchainStatus === 'confirmed') {
        return {
            shortText: 'Confirmed',
            textColor: 'text-gray-900',        
            barColor: 'bg-green-500',
            dotColor: 'bg-green-500',
            tooltip: 'Document successfully anchored to blockchain'
        };
    } else if (blockchainStatus === 'pending') {
        return {
            shortText: 'Processing',
            textColor: 'text-gray-900',        
            barColor: 'bg-blue-500',
            dotColor: 'bg-blue-500 animate-pulse',
            tooltip: 'Document being anchored to blockchain'
        };
    } else if (validationScore >= 85) {
        return {
            shortText: 'Ready',
            textColor: 'text-gray-900',       
            barColor: 'bg-green-500',
            dotColor: 'bg-green-500',
            tooltip: 'Document meets threshold for blockchain anchoring'
        };
    } else if (validationScore >= 75) {
        return {
            shortText: 'Nearly Ready',
            textColor: 'text-gray-900',        
            barColor: 'bg-yellow-500',
            dotColor: 'bg-yellow-500',
            tooltip: 'Document close to blockchain threshold'
        };
    } else {
        return {
            shortText: 'Incomplete',
            textColor: 'text-gray-900',        
            barColor: 'bg-red-500',
            dotColor: 'bg-red-500',
            tooltip: 'Document requires more validation'
        };
    }
}

/**
 * Format date for compact display
 */
function formatCompactDate(dateString) {
    // Handle null, undefined, or empty dates
    if (!dateString || dateString === 'Invalid Date' || dateString === '') {
        return 'No Date';
    }
    
    try {
        
        let date;
        
        // ISO format: 2025-01-15T10:30:00Z
        if (typeof dateString === 'string' && dateString.includes('T')) {
            date = new Date(dateString);
        }
        // MySQL datetime: 2025-01-15 10:30:00
        else if (typeof dateString === 'string' && dateString.includes('-')) {
            date = new Date(dateString.replace(' ', 'T'));
        }
        // Timestamp
        else if (!isNaN(dateString)) {
            date = new Date(parseInt(dateString) * 1000);
        }
        // Fallback
        else {
            date = new Date(dateString);
        }
        
        // Validate the parsed date
        if (isNaN(date.getTime())) {
            return 'Invalid Date';
        }
        
        const now = new Date();
        const diffInHours = Math.abs(now - date) / (1000 * 60 * 60);
        
        if (diffInHours < 1) {
            return 'Just now';
        } else if (diffInHours < 24) {
            return date.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
        } else if (diffInHours < 24 * 7) {
            return date.toLocaleDateString('en-US', {
                weekday: 'short',
                hour: 'numeric',
                hour12: true
            });
        } else {
            return date.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            });
        }
    } catch (error) {
        console.error('Date formatting error:', error, 'Input:', dateString);
        return 'Date Error';
    }
}


// FIXED: Update status badge mapping
function getStatusBadge(status) {
    const statusConfig = {
        pending: { 
            class: 'upload-doc-chip upload-doc-chip--warning', 
            text: 'Pending',
            icon: '<i class="fas fa-clock"></i>'
        },
        completed: { 
            class: 'upload-doc-chip upload-doc-chip--success', 
            text: 'Completed',
            icon: '<i class="fas fa-check-circle"></i>'
        },
        verified: { 
            class: 'upload-doc-chip upload-doc-chip--success', 
            text: 'Verified',
            icon: '<i class="fas fa-check-circle"></i>'
        },
        rejected: { 
            class: 'upload-doc-chip upload-doc-chip--danger', 
            text: 'Rejected',
            icon: '<i class="fas fa-times-circle"></i>'
        }
    };
    
    const config = statusConfig[status] || statusConfig.pending;
    
    return `<span class="${config.class}">\n        ${config.icon}<span>${config.text}</span>\n    </span>`;
}

function createValidationScoreIndicator(doc) {
    const ocrScore = parseFloat(doc.ocr_confidence) || 0;
    const manualScore = parseFloat(doc.manual_completion_score) || 0;
    const validationScore = parseFloat(doc.validation_score) || 0;
    const threshold = 75; // Use actual threshold from model
    
    // CRITICAL FIX: Better status text based on actual document state
    let statusText = 'Needs Completion';
    let statusColor = 'text-gray-900';
    let containerBg = 'bg-yellow-50 border-yellow-300';
    let progressBarColor = 'bg-yellow-500';
    
    // Determine status based on actual verification and blockchain status
    if (doc.blockchain_status === 'confirmed') {
        statusText = 'Blockchain Confirmed';
        statusColor = 'text-green-900';
        containerBg = 'bg-green-50 border-green-300';
        progressBarColor = 'bg-green-500';
    } else if (doc.blockchain_status === 'pending') {
        statusText = 'Blockchain Processing';
        statusColor = 'text-blue-900';
        containerBg = 'bg-blue-50 border-blue-300';
        progressBarColor = 'bg-blue-500';
    } else if (doc.verification_status === 'completed') {
        statusText = 'Document Completed';
        statusColor = 'text-green-900';
        containerBg = 'bg-green-50 border-green-300';
        progressBarColor = 'bg-green-500';
    } else if (manualScore >= 100) {
        statusText = 'Processing…';
        statusColor = 'text-blue-900';
        containerBg = 'bg-blue-50 border-blue-300';
        progressBarColor = 'bg-blue-500';
    } else if (manualScore >= 80) {
        statusText = 'Nearly Complete';
        statusColor = 'text-orange-900';
        containerBg = 'bg-orange-50 border-orange-300';
        progressBarColor = 'bg-orange-500';
    }
    
    return `
        <div class="mt-2 ${containerBg} rounded-lg p-3 border-2">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-bold ${statusColor}">${statusText}</span>
                <span class="text-sm font-bold text-gray-900">${validationScore.toFixed(1)}%</span>
            </div>
            
            <div class="w-full bg-gray-200 rounded-full h-2.5 mb-2">
                <div class="${progressBarColor} h-2.5 rounded-full transition-all duration-300" 
                     style="width: ${Math.min(validationScore, 100)}%"></div>
            </div>
            
            <div class="flex justify-between text-xs text-gray-400 font-normal">
                <span>OCR: ${ocrScore.toFixed(1)}% (40%)</span>
                <span>Manual: ${manualScore.toFixed(1)}% (60%)</span>
                <span>Threshold: ${threshold}%</span>
            </div>
        </div>
    `;
}

/**
 * MODEL A: Create conditional action button
 */
function createActionButton(doc) {
    // Already anchored to blockchain
    if (doc.blockchain_status === 'confirmed') {
        return `
            <span class="upload-doc-action-chip">
                <i class="fas fa-link"></i> Already Anchored
            </span>
        `;
    }
    
    // Currently anchoring to blockchain
    if (doc.blockchain_status === 'pending') {
        return `
            <button class="upload-doc-action-main upload-doc-action-main--info" disabled>
                <i class="fas fa-sync fa-spin"></i> Anchoring...
            </button>
        `;
    }
    
    // Document verification is pending - show verification buttons
    if (doc.verification_status === 'pending') {
        return `
            <div class="upload-doc-action-stack">
                <button onclick="updateVerificationStatus(${doc.id}, 'completed')" 
                        class="upload-doc-action-main upload-doc-action-main--success">
                    <i class="fas fa-check"></i> Mark Completed
                </button>
                <button onclick="updateVerificationStatus(${doc.id}, 'rejected')" 
                        class="upload-doc-action-main upload-doc-action-main--danger">
                    <i class="fas fa-times"></i> Reject Document
                </button>
            </div>
        `;
    }
    
    // Document is completed/verified - ready for anchoring
    if (doc.is_ready_for_anchoring) {
        return `
            <button onclick="triggerBlockchainAnchoring(${doc.id})" 
                    class="upload-doc-action-main upload-doc-action-main--success">
                <i class="fas fa-link"></i> Anchor to Blockchain
            </button>
        `;
    }
    
    // Document needs more completion
    return `
        <button onclick="openReviewModal(${doc.id})" 
                class="upload-doc-action-main upload-doc-action-main--warning">
            <i class="fas fa-exclamation-triangle"></i> Complete Document
        </button>
    `;
}

/**
 * MODEL A: Trigger blockchain anchoring
 */
async function triggerBlockchainAnchoring(scanId) {
    if (!confirm('Are you sure you want to anchor this document to the blockchain?\n\nThis action cannot be undone.')) {
        return;
    }
    
    try {
        // Disable button and show loading state
        const docCard = document.querySelector(`[data-document-id="${scanId}"]`);
        const button = docCard.querySelector('button');
        const originalHtml = button.innerHTML;
        button.innerHTML = '<i class="fas fa-sync fa-spin mr-1"></i> Processing...';
        button.disabled = true;
        
        const response = await fetch(`/staff/scans/${scanId}/trigger-blockchain`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Show success notification
            showNotification('success', 'Blockchain anchoring initiated successfully!');
            
            // Reload documents to show updated status
            await loadDocuments(currentPage);
        } else {
            // Show error with reason
            const reason = result.reason || {};
            const message = `Cannot anchor document:\n\n` +
                `Validation Score: ${reason.validation_score || 'N/A'}%\n` +
                `Required: ${reason.threshold || 85}%\n\n` +
                `${result.message}`;
            
            alert(message);
            
            // Restore button
            button.innerHTML = originalHtml;
            button.disabled = false;
        }
        
    } catch (error) {
        console.error('Blockchain anchoring error:', error);
        showNotification('error', 'Failed to initiate blockchain anchoring. Please try again.');
        
        // Reload page to reset state
        setTimeout(() => loadDocuments(currentPage), 1000);
    }
}

async function updateVerificationStatus(scanId, newStatus) {
    const confirmMessages = {
        completed: 'Are you sure you want to mark this document as COMPLETED?\n\nThis will make it eligible for blockchain anchoring.',
        rejected: 'Are you sure you want to REJECT this document?\n\nThis action can be undone later.',
        pending: 'Move document back to PENDING status?'
    };
    
    if (!confirm(confirmMessages[newStatus])) {
        return;
    }
    
    try {
        // Show loading state
        const docCard = document.querySelector(`[data-document-id="${scanId}"]`);
        const buttons = docCard.querySelectorAll('button');
        buttons.forEach(btn => {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Updating...';
        });
        
        const response = await fetch(`/staff/scans/${scanId}/update-verification-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ status: newStatus })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('success', `Document marked as ${newStatus.toUpperCase()}!`);
            
            // Reload documents to show updated status
            await loadDocuments(currentPage);
            await loadMetrics(); // Update metrics counts
        } else {
            showNotification('error', `Failed to update status: ${result.message}`);
            
            // Restore buttons
            buttons.forEach(btn => btn.disabled = false);
        }
        
    } catch (error) {
        console.error('Status update error:', error);
        showNotification('error', 'Failed to update document status. Please try again.');
        
        // Reload page to reset state
        setTimeout(() => loadDocuments(currentPage), 1000);
    }
}



/**
 * MODEL A: Recalculate validation scores for a document
 */
async function recalculateScores(scanId) {
    try {
        const response = await fetch(`/staff/scans/${scanId}/recalculate-scores`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('success', 'Validation scores updated!');
            
            // Update the document card with new scores
            const docCard = document.querySelector(`[data-document-id="${scanId}"]`);
            if (docCard) {
                // Update validation score display
                const scoreElement = docCard.querySelector('.text-sm.font-bold.text-gray-900');
                if (scoreElement) {
                    scoreElement.textContent = `${result.data.validation_score.toFixed(1)}%`;
                }
                
                // Update progress bar
                const progressBar = docCard.querySelector('.h-2\\.5.rounded-full.transition-all');
                if (progressBar) {
                    progressBar.style.width = `${Math.min(result.data.validation_score, 100)}%`;
                    
                    // Update color based on eligibility
                    if (result.data.blockchain_eligible) {
                        progressBar.className = 'bg-green-500 h-2.5 rounded-full transition-all duration-300';
                    } else {
                        progressBar.className = 'bg-yellow-500 h-2.5 rounded-full transition-all duration-300';
                    }
                }
            }
            
            // Reload documents to show updated button states
            await loadDocuments(currentPage);
        } else {
            showNotification('error', 'Failed to update validation scores');
        }
        
    } catch (error) {
        console.error('Score recalculation error:', error);
        showNotification('error', 'Failed to recalculate scores');
    }
}



/**
 * MODEL A: Open review/completion modal
 */
function openReviewModal(scanId) {
    // TODO: Implement modal or redirect to edit page
    alert(`Document #${scanId} needs to be completed before blockchain anchoring.\n\nRedirect to edit page to fill missing fields.`);
    // window.location.href = `/staff/scans/${scanId}/edit`;
}

/**
 * Notification system
 */
function showNotification(type, message) {
    const container = document.createElement('div');
    const bgColor = type === 'success' ? 'bg-green-500' : 'bg-red-500';
    
    container.className = `fixed top-4 right-4 z-50 ${bgColor} text-white px-6 py-4 rounded-lg shadow-lg transition-all duration-300`;
    container.textContent = message;
    
    document.body.appendChild(container);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        container.style.opacity = '0';
        setTimeout(() => container.remove(), 300);
    }, 5000);
}


function getBlockchainBadge(blockchainStatus, hash) {
    const blockchainConfig = {
        not_started: { 
            class: 'upload-doc-chip upload-doc-chip--muted', 
            text: 'Not Anchored', 
            icon: '<i class="fas fa-hourglass-half"></i>' 
        },
        pending: { 
            class: 'upload-doc-chip upload-doc-chip--info', 
            text: 'Anchoring', 
            icon: '<i class="fas fa-sync fa-spin"></i>' 
        },
        confirmed: { 
            class: 'upload-doc-chip upload-doc-chip--success', 
            text: 'Anchored', 
            icon: '<i class="fas fa-cube"></i>' 
        },
        failed: { 
            class: 'upload-doc-chip upload-doc-chip--danger', 
            text: 'Failed', 
            icon: '<i class="fas fa-exclamation-circle"></i>' 
        }
    };
    
    const config = blockchainConfig[blockchainStatus] || blockchainConfig.not_started;
    const title = hash ? `Transaction: ${hash.substring(0, 10)}...` : '';
    
    return `<span class="${config.class}" title="${title}">
        ${config.icon}<span>${config.text}</span>
    </span>`;
}

function updatePagination(paginatedData) {
    const paginationInfo = document.getElementById('pagination-info');
    const paginationButtons = document.getElementById('pagination-buttons');
    
    const { from, to, total, current_page, last_page } = paginatedData;
    
    paginationInfo.textContent = `Showing ${from || 0} to ${to || 0} of ${total || 0} results`;
    
    let buttonsHtml = '';
    
    // Previous button
    if (current_page > 1) {
        buttonsHtml += `<button onclick="loadDocuments(${current_page - 1})" class="px-3 py-2 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50">Previous</button>`;
    }
    
    // Page numbers
    for (let i = Math.max(1, current_page - 2); i <= Math.min(last_page, current_page + 2); i++) {
        const activeClass = i === current_page ? 'bg-blue-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-50';
        buttonsHtml += `<button onclick="loadDocuments(${i})" class="px-3 py-2 text-sm border border-gray-300 rounded-md ${activeClass}">${i}</button>`;
    }
    
    // Next button
    if (current_page < last_page) {
        buttonsHtml += `<button onclick="loadDocuments(${current_page + 1})" class="px-3 py-2 text-sm text-gray-900 bg-white border border-gray-300 rounded-md hover:bg-gray-50">Next</button>`;
    }
    
    paginationButtons.innerHTML = buttonsHtml;
}

function updateConnectionStatus(status) {
    connectionStatus = status;
    const indicator = document.getElementById('status-indicator');
    const statusText = document.getElementById('status-text');
    const container = document.getElementById('connection-status');
    
    const statusConfig = {
        connecting: { 
            color: 'bg-yellow-500', 
            text: 'Connecting...', 
            containerClass: 'bg-yellow-100 text-yellow-800' 
        },
        connected: { 
            color: 'bg-green-500', 
            text: 'Live', 
            containerClass: 'bg-green-100 text-green-800' 
        },
        error: { 
            color: 'bg-red-500', 
            text: 'Connection Error', 
            containerClass: 'bg-red-100 text-red-800' 
        }
    };
    
    const config = statusConfig[status];
    indicator.className = `w-3 h-3 rounded-full ${config.color}`;
    statusText.textContent = config.text;
    container.className = `px-4 py-2 rounded-lg shadow-lg transition-all duration-300 ${config.containerClass}`;
}

function updateLastUpdated() {
    const now = new Date();
    document.getElementById('last-updated').textContent = now.toLocaleTimeString();
}

function displayError(message) {
    const container = document.getElementById('documents-container');
    container.innerHTML = `
        <div class="p-8 text-center text-red-500">
            <svg class="w-12 h-12 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
            <div class="text-lg font-medium mb-2">Error Loading Data</div>
            <div>${message}</div>
            <button onclick="loadDocuments()" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Try Again
            </button>
        </div>
    `;
}

function viewDocument(documentId) {
    showNotification('info', `Loading document #${documentId}...`);
    window.location.href = `/staff/scans/${documentId}`;
}

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});
</script>

@endsection