/**
 * ================================================================
 *  Staff Search Module — staff-search.js
 *  Handles: category selection, AJAX form loading, live search,
 *  result rendering, drawer, full preview modal, print & release,
 *  form submission, and dynamic fields for "other" documents.
 * ================================================================
 */
(function () {
    'use strict';

    /* ---------- Config ---------- */
    var DEBOUNCE_MS = 500;
    var MAX_LIVE_RESULTS = 5;

    /* ---------- State ---------- */
    var currentDocumentType = null;
    var searchTimeout = null;

    /* ---------- DOM cache (populated on DOMContentLoaded) ---------- */
    var el = {};

    /* ==========================================================
       Initialisation
       ========================================================== */
    document.addEventListener('DOMContentLoaded', init);

    function init() {
        el = {
            categoryCards:    document.querySelectorAll('.ss-category-card'),
            emptyState:       document.getElementById('ss-empty-state'),
            formContainer:    document.getElementById('ss-form-container'),
            formTitle:        document.getElementById('ss-form-title'),
            formContent:      document.getElementById('ss-form-content'),
            formClose:        document.getElementById('ss-form-close'),
            resultsContainer: document.getElementById('ss-results-container'),
            resultsContent:   document.getElementById('ss-results-content'),
            drawer:           document.getElementById('ss-drawer'),
            drawerBackdrop:   document.getElementById('ss-drawer-backdrop'),
            drawerClose:      document.getElementById('ss-drawer-close'),
            drawerTitle:      document.getElementById('ss-drawer-title'),
            drawerBody:       document.getElementById('ss-drawer-body'),
            drawerPrintBtn:   document.getElementById('ss-drawer-print-btn')
        };

        setupCategoryCards();
        setupDrawer();
        setupKeyboard();
    }

    /* ==========================================================
       Category Cards
       ========================================================== */
    function setupCategoryCards() {
        el.categoryCards.forEach(function (card) {
            card.addEventListener('click', function () { selectCategory(card); });
            card.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    selectCategory(card);
                }
            });
        });

        if (el.formClose) {
            el.formClose.addEventListener('click', closeForm);
        }
    }

    var TITLES = {
        birth_certificate:    'Birth Certificate Search',
        marriage_certificate: 'Marriage Certificate Search',
        death_certificate:    'Death Certificate Search',
        other:                'Other Documents Search'
    };

    var TITLE_ICONS = {
        birth_certificate:    'fas fa-baby',
        marriage_certificate: 'fas fa-heart',
        death_certificate:    'fas fa-cross',
        other:                'fas fa-folder-open'
    };

    function selectCategory(card) {
        var type = card.dataset.type;
        currentDocumentType = type;

        // Active state
        el.categoryCards.forEach(function (c) { c.classList.remove('active'); });
        card.classList.add('active');

        // Show form, hide empty state
        if (el.emptyState) el.emptyState.classList.add('hidden');
        if (el.formContainer) el.formContainer.classList.remove('hidden');
        if (el.resultsContainer) el.resultsContainer.classList.add('hidden');

        // Title
        if (el.formTitle) {
            el.formTitle.innerHTML = '<i class="' + (TITLE_ICONS[type] || 'fas fa-search') + '"></i> ' + (TITLES[type] || 'Document Search');
        }

        loadSearchForm(type);
    }

    function closeForm() {
        if (el.formContainer) el.formContainer.classList.add('hidden');
        if (el.resultsContainer) el.resultsContainer.classList.add('hidden');
        if (el.emptyState) el.emptyState.classList.remove('hidden');
        el.categoryCards.forEach(function (c) { c.classList.remove('active'); });
        currentDocumentType = null;
    }

    /* ==========================================================
       Form Loading (AJAX)
       ========================================================== */
    function loadSearchForm(documentType) {
        if (!el.formContent) return;
        el.formContent.innerHTML = '<div class="ss-spinner"></div>';

        fetch('/staff/search/form/' + documentType)
            .then(function (r) { return r.text(); })
            .then(function (html) {
                // Strip any <script> tags from the partial (they won't execute in innerHTML anyway)
                var cleaned = html.replace(/<script[\s\S]*?<\/script>/gi, '');
                el.formContent.innerHTML = cleaned;
                initFormEvents();
            })
            .catch(function (err) {
                console.error('Error loading form:', err);
                el.formContent.innerHTML = '<p class="ss-no-results"><i class="fas fa-exclamation-triangle"></i><br>Failed to load search form. Please try again.</p>';
            });
    }

    /* ==========================================================
       Form Event Wiring (after AJAX load)
       ========================================================== */
    function initFormEvents() {
        if (!el.formContent) return;

        // Live search on data-search inputs
        var inputs = el.formContent.querySelectorAll('input[data-search], select[data-search], textarea[data-search]');
        inputs.forEach(function (input) {
            var handler = function () {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(performLiveSearch, DEBOUNCE_MS);
            };
            input.addEventListener('input', handler);
            if (input.tagName === 'SELECT') {
                input.addEventListener('change', handler);
            }
        });

        // Clear form — each partial uses a different ID
        var clearIds = ['clear-form', 'clear-form-marriage', 'clear-form-death', 'clear-form-other'];
        clearIds.forEach(function (id) {
            var btn = document.getElementById(id);
            if (btn) {
                btn.addEventListener('click', function () {
                    var form = el.formContent.querySelector('form');
                    if (form) form.reset();
                    // Hide dynamic additional fields (other form)
                    var af = document.getElementById('additional-fields');
                    if (af) af.style.display = 'none';
                    resetLivePreview();
                });
            }
        });

        // Form submission
        var form = el.formContent.querySelector('form');
        if (form) {
            form.addEventListener('submit', handleFormSubmit);
        }

        // Other-form dynamic fields
        var specificSelect = document.getElementById('specific-document-type');
        if (specificSelect) {
            specificSelect.addEventListener('change', function () {
                showAdditionalFields(this.value);
            });
        }
    }

    /* ==========================================================
       "Other" Document — Dynamic Additional Fields
       ========================================================== */
    function showAdditionalFields(docType) {
        var container = document.getElementById('additional-fields');
        var dynamic   = document.getElementById('dynamic-fields');
        if (!container || !dynamic) return;

        var html = '';

        switch (docType) {
            case 'cenomar':
                html = '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">' +
                    '<div><label class="block text-sm font-medium text-gray-700 mb-2">Purpose of CENOMAR</label>' +
                    '<input type="text" name="purpose" data-search="true" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="e.g., Marriage, Employment"></div>' +
                    '<div><label class="block text-sm font-medium text-gray-700 mb-2">Age Range</label>' +
                    '<select name="age_range" data-search="true" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">' +
                    '<option value="">Select Age Range</option><option value="18-25">18-25 years</option><option value="26-35">26-35 years</option>' +
                    '<option value="36-45">36-45 years</option><option value="46-55">46-55 years</option><option value="56+">56+ years</option></select></div></div>';
                break;
            case 'late_birth_registration':
                html = '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">' +
                    '<div><label class="block text-sm font-medium text-gray-700 mb-2">Reason for Late Registration</label>' +
                    '<textarea name="late_registration_reason" data-search="true" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Enter reason"></textarea></div>' +
                    '<div><label class="block text-sm font-medium text-gray-700 mb-2">Birth Hospital/Clinic</label>' +
                    '<input type="text" name="birth_place_institution" data-search="true" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Hospital/Clinic name"></div></div>';
                break;
            case 'affidavit':
                html = '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">' +
                    '<div><label class="block text-sm font-medium text-gray-700 mb-2">Affidavit Type</label>' +
                    '<select name="affidavit_type" data-search="true" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">' +
                    '<option value="">Select Type</option><option value="loss">Affidavit of Loss</option><option value="discrepancy">Affidavit of Discrepancy</option>' +
                    '<option value="one_same_person">Affidavit of One and Same Person</option><option value="other">Other</option></select></div>' +
                    '<div><label class="block text-sm font-medium text-gray-700 mb-2">Notary Public</label>' +
                    '<input type="text" name="notary_public" data-search="true" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="Notary public name"></div></div>';
                break;
        }

        if (html) {
            dynamic.innerHTML = html;
            container.style.display = 'block';
            // Attach live search to new inputs
            var newInputs = dynamic.querySelectorAll('input[data-search], select[data-search], textarea[data-search]');
            newInputs.forEach(function (input) {
                var handler = function () {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(performLiveSearch, DEBOUNCE_MS);
                };
                input.addEventListener('input', handler);
                if (input.tagName === 'SELECT') input.addEventListener('change', handler);
            });
        } else {
            container.style.display = 'none';
        }
    }

    /* ==========================================================
       Live Search
       ========================================================== */
    function performLiveSearch() {
        if (!el.formContent) return;
        var form = el.formContent.querySelector('form');
        if (!form) return;

        var formData = new FormData(form);
        var params = new URLSearchParams();

        for (var pair of formData.entries()) {
            if (pair[1] && pair[1].toString().trim()) {
                params.append(pair[0], pair[1]);
            }
        }

        if (!params.toString()) return;

        fetch('/staff/search/live?' + params.toString())
            .then(function (r) { return r.json(); })
            .then(function (results) { renderLivePreview(results); })
            .catch(function (err) { console.error('Live search error:', err); });
    }

    /* ==========================================================
       Live Preview Rendering
       ========================================================== */
    // All known preview container IDs across partials
    var PREVIEW_IDS = ['live-preview', 'live-preview-marriage', 'live-preview-death', 'live-preview-other'];

    function findPreviewContainer() {
        for (var i = 0; i < PREVIEW_IDS.length; i++) {
            var c = document.getElementById(PREVIEW_IDS[i]);
            if (c) return c;
        }
        return null;
    }

    function renderLivePreview(results) {
        var container = findPreviewContainer();
        if (!container) return;

        if (!results || results.length === 0) {
            container.innerHTML =
                '<div class="ss-no-results">' +
                    '<i class="fas fa-search"></i>' +
                    '<p>No matching documents found</p>' +
                '</div>';
            return;
        }

        var html = '<div class="ss-preview-header"><i class="fas fa-list"></i> Matching Documents (' + results.length + ')</div>';

        results.slice(0, MAX_LIVE_RESULTS).forEach(function (result) {
            var fields = parseFields(result.extracted_fields);
            var metaHtml = buildResultMeta(fields, result.document_type || currentDocumentType);
            var thumbUrl = getPreviewUrl(result) || '/images/placeholder-document.png';

            html +=
                '<div class="ss-result-card" onclick="StaffSearch.openDrawer(' + result.id + ')" tabindex="0" role="button" aria-label="Preview ' + escapeHtml(result.title) + '">' +
                    '<img src="' + escapeHtml(thumbUrl) + '" alt="" class="ss-result-thumb" onerror="this.onerror=null; this.src=\'/images/placeholder-document.png\'">' +
                    '<div class="ss-result-info">' +
                        '<div class="ss-result-title">' + escapeHtml(result.title || 'Untitled') + '</div>' +
                        '<div class="ss-result-id">' + escapeHtml(result.document_id || '') + '</div>' +
                        metaHtml +
                        '<div class="ss-result-footer">' +
                            '<span class="ss-result-badge ' + (result.status_class || '') + '">' + escapeHtml(result.status || 'Unknown') + '</span>' +
                            '<span class="ss-result-preview-link"><i class="fas fa-eye"></i> Preview</span>' +
                        '</div>' +
                    '</div>' +
                '</div>';
        });

        container.innerHTML = html;
    }

    /** Build type-aware metadata snippet */
    function buildResultMeta(fields, docType) {
        var m = '<div class="ss-result-meta">';
        switch (docType) {
            case 'birth_certificate':
                m += '<div><strong>Child:</strong> ' + (fields.name_first || fields.child_first_name || '—') + ' ' + (fields.name_last || fields.child_last_name || '') + '</div>';
                m += '<div><strong>Birth Date:</strong> ' + (fields.date_of_birth || [fields.birth_date_month, fields.birth_date_day, fields.birth_date_year].filter(Boolean).join('/') || '—') + '</div>';
                m += '<div><strong>Mother:</strong> ' + (fields.mother_first_name || '—') + ' ' + (fields.mother_last_name || '') + '</div>';
                break;
            case 'marriage_certificate':
                m += '<div><strong>Husband:</strong> ' + (fields.groom_first_name || fields.husband_first_name || '—') + ' ' + (fields.groom_last_name || fields.husband_last_name || '') + '</div>';
                m += '<div><strong>Wife:</strong> ' + (fields.bride_first_name || fields.wife_first_name || '—') + ' ' + (fields.bride_last_name || fields.wife_last_name || '') + '</div>';
                m += '<div><strong>Marriage Date:</strong> ' + (fields.marriage_date || fields.date_of_marriage || '—') + '</div>';
                break;
            case 'death_certificate':
                m += '<div><strong>Deceased:</strong> ' + (fields.deceased_first_name || fields.name_first || '—') + ' ' + (fields.deceased_last_name || fields.name_last || '') + '</div>';
                m += '<div><strong>Death Date:</strong> ' + (fields.date_of_death || [fields.death_date_month, fields.death_date_day, fields.death_date_year].filter(Boolean).join('/') || '—') + '</div>';
                m += '<div><strong>Age:</strong> ' + (fields.age_at_death || '—') + '</div>';
                break;
            case 'other':
                m += '<div><strong>Type:</strong> Other Document</div>';
                m += '<div><strong>Specific:</strong> ' + humanizeDocumentType(fields.specific_document_type || 'other') + '</div>';
                m += '<div><strong>Issued:</strong> ' + (fields.date_issued || '—') + '</div>';
                break;
            default:
                m += '<div><strong>Type:</strong> ' + humanizeDocumentType(docType || fields.specific_document_type || 'other') + '</div>';
                m += '<div><strong>Issued:</strong> ' + (fields.date_issued || '—') + '</div>';
        }
        m += '</div>';
        return m;
    }

    function resetLivePreview() {
        PREVIEW_IDS.forEach(function (id) {
            var c = document.getElementById(id);
            if (c) {
                c.innerHTML =
                    '<div class="ss-no-results">' +
                        '<i class="fas fa-search"></i>' +
                        '<p>Start typing to see matching documents</p>' +
                    '</div>';
            }
        });
    }

    /* ==========================================================
       Form Submission (POST search)
       ========================================================== */
    function handleFormSubmit(e) {
        e.preventDefault();
        var form = e.target;
        var formData = new FormData(form);
        var params = new URLSearchParams(formData);

        var submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Searching...';
        }

        fetch('/staff/search', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: params
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                displaySearchResults(data.results);
            } else {
                alert(data.message || 'Search returned no results.');
            }
        })
        .catch(function (err) {
            console.error('Search error:', err);
            alert('Search failed. Please try again.');
        })
        .finally(function () {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-search"></i> Search Documents';
            }
        });
    }

    function displaySearchResults(results) {
        if (!el.resultsContainer || !el.resultsContent) return;
        el.resultsContainer.classList.remove('hidden');

        if (!results || results.length === 0) {
            el.resultsContent.innerHTML =
                '<div class="ss-no-results">' +
                    '<i class="fas fa-folder-open"></i>' +
                    '<p>No documents found matching your criteria</p>' +
                '</div>';
            return;
        }

        var html =
            '<div class="ss-results-header">' +
                '<span class="ss-results-count"><i class="fas fa-file-alt"></i> ' + results.length + ' document(s) found</span>' +
            '</div>' +
            '<div class="ss-results-body">';

        results.forEach(function (result) {
            var fields = parseFields(result.extracted_fields);
            var metaHtml = buildResultMeta(fields, result.document_type || currentDocumentType);
            var thumbUrl = getPreviewUrl(result) || '/images/placeholder-document.png';

            html +=
                '<div class="ss-result-card" onclick="StaffSearch.openDrawer(' + result.id + ')" tabindex="0" role="button">' +
                    '<img src="' + escapeHtml(thumbUrl) + '" alt="" class="ss-result-thumb" onerror="this.onerror=null; this.src=\'/images/placeholder-document.png\'">' +
                    '<div class="ss-result-info">' +
                        '<div class="ss-result-title">' + escapeHtml(result.title || 'Untitled') + '</div>' +
                        '<div class="ss-result-id">' + escapeHtml(result.document_id || '') + '</div>' +
                        metaHtml +
                        '<div class="ss-result-footer">' +
                            '<span class="ss-result-badge ' + (result.status_class || '') + '">' + escapeHtml(result.status || 'Unknown') + '</span>' +
                            '<span class="ss-result-preview-link"><i class="fas fa-eye"></i> Preview</span>' +
                        '</div>' +
                    '</div>' +
                '</div>';
        });

        html += '</div>';
        el.resultsContent.innerHTML = html;
    }

    /* ==========================================================
       Slide-in Drawer
       ========================================================== */
    function setupDrawer() {
        if (el.drawerClose) {
            el.drawerClose.addEventListener('click', closeDrawer);
        }
        if (el.drawerBackdrop) {
            el.drawerBackdrop.addEventListener('click', closeDrawer);
        }
    }

    function openDrawer(scanId) {
        fetch('/staff/search/document/' + scanId)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) {
                    alert(data.message || 'Error loading document');
                    return;
                }
                populateDrawer(data.document);
                el.drawer.classList.add('open');
                el.drawerBackdrop.classList.add('open');
                document.body.style.overflow = 'hidden';
            })
            .catch(function (err) {
                console.error('Failed to load document:', err);
                alert('Failed to load document details.');
            });
    }

    function populateDrawer(doc) {
        var fields = parseFields(doc.extracted_fields);
        var metaHtml = '';
        var skip = ['raw_text'];

        Object.keys(fields).forEach(function (key) {
            if (skip.indexOf(key) !== -1 || !fields[key]) return;
            var label = key.replace(/_/g, ' ').replace(/\b\w/g, function (l) { return l.toUpperCase(); });
            metaHtml +=
                '<div class="ss-drawer-field">' +
                    '<span class="ss-drawer-field-label">' + escapeHtml(label) + '</span>' +
                    '<span class="ss-drawer-field-value">' + escapeHtml(String(fields[key])) + '</span>' +
                '</div>';
        });

        var mediaHtml = buildDrawerMediaHtml(doc);

        el.drawerTitle.textContent = doc.title || 'Document Details';
        el.drawerBody.innerHTML =
            mediaHtml +
            '<div>' + (metaHtml || '<p class="ss-no-results" style="padding:1rem 0"><i class="fas fa-info-circle"></i><br>No extracted data available</p>') + '</div>';

        el.drawerPrintBtn.onclick = function () {
            openPreviewModal(doc);
        };
    }

    function closeDrawer() {
        if (el.drawer) el.drawer.classList.remove('open');
        if (el.drawerBackdrop) el.drawerBackdrop.classList.remove('open');
        document.body.style.overflow = '';
    }

    /* ==========================================================
       Full Preview Modal (Print & Release)
       ========================================================== */
    function openPreviewModal(doc) {
        closeDrawer();

        var fields = parseFields(doc.extracted_fields);

        // Build optional certificate details
        var certHtml = '';
        if (doc.document_type === 'birth_certificate') {
            certHtml =
                '<div class="ss-modal-details">' +
                    '<div class="ss-modal-section-title">Certificate Details</div>' +
                    '<div class="ss-modal-field-row"><div class="ss-modal-field-label">Child\'s Name</div>' +
                        '<div class="ss-modal-field-value">' + escapeHtml((fields.name_first || '') + ' ' + (fields.name_middle || '') + ' ' + (fields.name_last || 'N/A')) + '</div></div>' +
                    '<div class="ss-modal-field-row"><div class="ss-modal-field-label">Date of Birth</div>' +
                        '<div class="ss-modal-field-value">' + escapeHtml([fields.birth_date_day, fields.birth_date_month, fields.birth_date_year].filter(Boolean).join(' ') || 'Not specified') + '</div></div>' +
                '</div>';
        } else if (doc.document_type === 'marriage_certificate') {
            certHtml =
                '<div class="ss-modal-details">' +
                    '<div class="ss-modal-section-title">Certificate Details</div>' +
                    '<div class="ss-modal-field-row"><div class="ss-modal-field-label">Husband</div>' +
                        '<div class="ss-modal-field-value">' + escapeHtml((fields.groom_first_name || fields.husband_first_name || '') + ' ' + (fields.groom_last_name || fields.husband_last_name || 'N/A')) + '</div></div>' +
                    '<div class="ss-modal-field-row"><div class="ss-modal-field-label">Wife</div>' +
                        '<div class="ss-modal-field-value">' + escapeHtml((fields.bride_first_name || fields.wife_first_name || '') + ' ' + (fields.bride_last_name || fields.wife_last_name || 'N/A')) + '</div></div>' +
                    '<div class="ss-modal-field-row"><div class="ss-modal-field-label">Marriage Date</div>' +
                        '<div class="ss-modal-field-value">' + escapeHtml(fields.marriage_date || fields.date_of_marriage || 'Not specified') + '</div></div>' +
                '</div>';
        } else if (doc.document_type === 'death_certificate') {
            certHtml =
                '<div class="ss-modal-details">' +
                    '<div class="ss-modal-section-title">Certificate Details</div>' +
                    '<div class="ss-modal-field-row"><div class="ss-modal-field-label">Deceased</div>' +
                        '<div class="ss-modal-field-value">' + escapeHtml((fields.deceased_first_name || fields.name_first || '') + ' ' + (fields.deceased_last_name || fields.name_last || 'N/A')) + '</div></div>' +
                    '<div class="ss-modal-field-row"><div class="ss-modal-field-label">Date of Death</div>' +
                        '<div class="ss-modal-field-value">' + escapeHtml(fields.date_of_death || [fields.death_date_month, fields.death_date_day, fields.death_date_year].filter(Boolean).join('/') || 'Not specified') + '</div></div>' +
                '</div>';
        }

        var previewUrl = getPreviewUrl(doc) || '/images/placeholder-document.png';
        var printSource = previewUrl;

        var previewBodyHtml =
            '<img id="ss-print-target" src="' + escapeHtml(printSource) + '" alt="Document" class="ss-modal-preview-img" ' +
                'onerror="this.onerror=null; this.src=\'/images/placeholder-document.png\'">';

        var modalHtml =
            '<div id="ss-preview-modal" class="ss-modal-overlay" data-print-src="' + escapeHtml(printSource || '') + '">' +
                '<div class="ss-modal-preview">' +
                    '<div class="ss-modal-preview-bar">' +
                        '<i class="fas fa-file-alt"></i>' +
                        '<div>' +
                            '<div class="ss-modal-preview-title">Document Preview</div>' +
                            '<div class="ss-modal-preview-sub">' + escapeHtml(doc.document_id || '') + '</div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="ss-modal-preview-body">' +
                        previewBodyHtml +
                    '</div>' +
                '</div>' +
                '<div class="ss-modal-sidebar">' +
                    '<div class="ss-modal-sidebar-header">' +
                        '<span class="ss-modal-sidebar-title">Print & Release</span>' +
                        '<button onclick="StaffSearch.closePreviewModal()" class="ss-drawer-close"><i class="fas fa-times"></i></button>' +
                    '</div>' +
                    '<div class="ss-modal-sidebar-body">' +
                        certHtml +
                        '<div class="ss-modal-section-title">Print Settings</div>' +
                        '<div class="ss-modal-info-box">' +
                            '<i class="fas fa-info-circle"></i>' +
                            '<div>' +
                                '<div class="ss-modal-info-title">System Printer via Print Dialog</div>' +
                                '<div class="ss-modal-info-text">Click Print & Release to open the browser print dialog. Any printer already connected and recognized by this computer will be available there.</div>' +
                            '</div>' +
                        '</div>' +
                        '<div class="ss-modal-form-group">' +
                            '<label class="ss-modal-label">Number of Copies</label>' +
                            '<input type="number" id="ss-print-copies" value="1" min="1" max="10" class="ss-input">' +
                        '</div>' +
                        '<div class="ss-modal-form-group">' +
                            '<label class="ss-modal-label">Release To (Client Name)</label>' +
                            '<input type="text" id="ss-client-name" placeholder="Enter client name" class="ss-input">' +
                            '<div class="ss-modal-hint">Required for audit trail</div>' +
                        '</div>' +
                        '<div class="ss-modal-form-group">' +
                            '<label class="ss-modal-label">Reason</label>' +
                            '<input type="text" id="ss-release-reason" placeholder="Enter reason" class="ss-input">' +
                            '<div class="ss-modal-hint">Required for release logging</div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="ss-modal-sidebar-footer">' +
                        '<button onclick="StaffSearch.executePrintRelease(' + doc.id + ')" class="ss-btn ss-btn-primary ss-btn-full">' +
                            '<i class="fas fa-print"></i> Print & Release Document' +
                        '</button>' +
                        '<button onclick="StaffSearch.closePreviewModal()" class="ss-btn ss-btn-secondary ss-btn-full ss-btn-mt">' +
                            'Cancel' +
                        '</button>' +
                    '</div>' +
                '</div>' +
            '</div>';

        document.body.insertAdjacentHTML('beforeend', modalHtml);
        document.body.style.overflow = 'hidden';
    }

    function closePreviewModal() {
        var modal = document.getElementById('ss-preview-modal');
        if (modal) {
            modal.remove();
            document.body.style.overflow = '';
        }
    }

    function executePrintRelease(scanId) {
        var copies = (document.getElementById('ss-print-copies') || {}).value || 1;
        var clientName = (document.getElementById('ss-client-name') || {}).value || '';
        var reason = (document.getElementById('ss-release-reason') || {}).value || '';

        if (!clientName.trim()) {
            alert('Please enter client name for audit trail.');
            var nameInput = document.getElementById('ss-client-name');
            if (nameInput) nameInput.focus();
            return;
        }

        if (!reason.trim()) {
            alert('Please enter reason for document release.');
            var reasonInput = document.getElementById('ss-release-reason');
            if (reasonInput) reasonInput.focus();
            return;
        }

        if (!confirm('Release document to: ' + clientName + '\nReason: ' + reason + '\nCopies: ' + copies + '\n\nThis will:\n1. Open print dialog\n2. Mark document as released\n3. Log this transaction\n\nProceed?')) {
            return;
        }

        fetch('/corrections/requests/staff/scans/' + scanId + '/log-release', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                copies: copies,
                client_name: clientName,
                reason: reason,
                released_at: new Date().toISOString()
            })
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                printDocument(scanId, clientName);
            } else {
                alert('Failed to log release: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(function (err) {
            console.error('Error logging release:', err);
            alert('Warning: Could not log release, but printing will continue.');
            printDocument(scanId, clientName);
        });
    }

    function printDocument(scanId, clientName) {
        var modal = document.getElementById('ss-preview-modal');
        var printSource = modal ? (modal.getAttribute('data-print-src') || '') : '';

        if (!printSource) {
            alert('Document preview source is unavailable for printing.');
            return;
        }

        printImageDocument(scanId, printSource);

        setTimeout(function () { closePreviewModal(); }, 2000);
    }

    function printImageDocument(scanId, imageSrc) {
        var w = window.open('', '_blank', 'width=800,height=600');
        if (!w) {
            alert('Unable to open print window. Please allow pop-ups and try again.');
            return;
        }

        w.document.write(
            '<!DOCTYPE html><html><head><title>Print Document - ' + scanId + '</title>' +
            '<style>@page{size:A4;margin:0}*{margin:0;padding:0;box-sizing:border-box}' +
            'body{display:flex;justify-content:center;align-items:center;min-height:100vh}' +
            'img{max-width:100%;max-height:100vh;display:block}' +
            '@media print{body{margin:0;padding:0}img{max-width:100%;height:auto;page-break-inside:avoid}}</style>' +
            '</head><body><img src="' + escapeHtml(imageSrc) + '" alt="Document" onload="window.print();">' +
            '<script>window.onafterprint=function(){window.close();};<\/script>' +
            '</body></html>'
        );
        w.document.close();
    }

    /* ==========================================================
       Keyboard Handling
       ========================================================== */
    function setupKeyboard() {
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                var modal = document.getElementById('ss-preview-modal');
                if (modal) {
                    closePreviewModal();
                } else if (el.drawer && el.drawer.classList.contains('open')) {
                    closeDrawer();
                }
            }
        });
    }

    /* ==========================================================
       Utility Functions
       ========================================================== */
    function getPreviewUrl(doc) {
        if (!doc || typeof doc !== 'object') return '';
        return doc.preview_url || doc.image_url || '';
    }

    function getRawPreviewUrl(doc) {
        if (!doc || typeof doc !== 'object') return '';
        return doc.preview_raw_url || doc.preview_url || doc.image_url || '';
    }

    function isPdfDocument(doc) {
        if (!doc || typeof doc !== 'object') return false;

        if (doc.is_pdf === true || doc.is_pdf === 1 || doc.is_pdf === '1') {
            return true;
        }

        var mime = String(doc.file_mime_type || '').toLowerCase();
        if (mime.indexOf('application/pdf') !== -1) {
            return true;
        }

        var previewUrl = String(getPreviewUrl(doc)).toLowerCase();
        var rawPreviewUrl = String(getRawPreviewUrl(doc)).toLowerCase();

        return /\.pdf($|[?#])/.test(previewUrl) || /\.pdf($|[?#])/.test(rawPreviewUrl);
    }

    function buildDrawerMediaHtml(doc) {
        var previewUrl = getPreviewUrl(doc) || '/images/placeholder-document.png';

        return '<img src="' + escapeHtml(previewUrl) + '" alt="Document preview" class="ss-drawer-image" onerror="this.onerror=null; this.src=\'/images/placeholder-document.png\';">';
    }

    function parseFields(fields) {
        if (!fields) return {};
        if (typeof fields === 'string') {
            try { return JSON.parse(fields); } catch (e) { return {}; }
        }
        if (typeof fields === 'object') return fields;
        return {};
    }

    function escapeHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.textContent = String(str);
        return div.innerHTML;
    }

    function humanizeDocumentType(type) {
        if (!type) return 'Other';
        return String(type)
            .replace(/_/g, ' ')
            .replace(/\b\w/g, function (c) { return c.toUpperCase(); });
    }

    /* ==========================================================
       Public API — exposed on window.StaffSearch
       ========================================================== */
    window.StaffSearch = {
        openDrawer:         openDrawer,
        closePreviewModal:  closePreviewModal,
        executePrintRelease: executePrintRelease
    };

    // Legacy backwards-compat globals
    window.openDrawer            = openDrawer;
    window.viewDocumentDetails   = openDrawer;
    window.closeDocumentModal    = closePreviewModal;

})();
