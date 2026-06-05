/**
 * Legal Corrections (Petitions) – Staff Side JS
 * Handles dynamic form interactions for petition creation
 */
document.addEventListener('DOMContentLoaded', function () {

    // ── Document selector: Load fields on change ──
    const scanSelect = document.getElementById('scan_id');
    const fieldsContainer = document.getElementById('lc-fields-container');
    const fieldTemplate = document.getElementById('lc-field-change-template');
    const fieldSelectTemplate = document.getElementById('lc-field-select-options');

    if (scanSelect) {
        scanSelect.addEventListener('change', function () {
            const scanId = this.value;
            if (!scanId) {
                if (fieldsContainer) fieldsContainer.innerHTML = '';
                if (fieldSelectTemplate) fieldSelectTemplate.innerHTML = '<option value="">-- Select field --</option>';
                return;
            }
            loadDocumentFields(scanId);
        });

        // If pre-selected, trigger load
        if (scanSelect.value) {
            loadDocumentFields(scanSelect.value);
        }
    }

    function loadDocumentFields(scanId) {
        const url = document.getElementById('lc-document-fields-url');
        if (!url) return;

        const endpoint = url.value + '?scan_id=' + scanId;

        fetch(endpoint, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        })
        .then(response => response.json())
        .then(data => {
            // Store fields data globally for use in field change rows
            window.lcDocumentFields = data.fields || [];
            window.lcDocumentType = data.document_type || '';
            window.lcDocumentTitle = data.title || '';

            // Update document info display
            const docInfo = document.getElementById('lc-doc-info');
            if (docInfo) {
                docInfo.innerHTML = '<i class="fas fa-file-alt"></i> <strong>' +
                    (data.document_type || '').replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) +
                    '</strong> — ' + (data.title || data.document_id || 'Document');
                docInfo.style.display = 'flex';
            }

            // Update field select options in existing rows
            updateFieldSelects();
        })
        .catch(error => {
            console.error('Failed to load document fields:', error);
        });
    }

    function updateFieldSelects() {
        const selects = document.querySelectorAll('.lc-field-name-select');
        selects.forEach(function (select) {
            const currentVal = select.value;
            select.innerHTML = '<option value="">-- Select field --</option>';
            (window.lcDocumentFields || []).forEach(function (field) {
                const opt = document.createElement('option');
                opt.value = field.field_name;
                opt.textContent = field.field_label;
                opt.dataset.currentValue = field.current_value || '';
                if (field.field_name === currentVal) opt.selected = true;
                select.appendChild(opt);
            });
        });
    }

    // ── Add field change row ──
    const addFieldBtn = document.getElementById('lc-add-field-btn');
    if (addFieldBtn) {
        addFieldBtn.addEventListener('click', function () {
            addFieldChangeRow();
        });
    }

    window.lcFieldIndex = document.querySelectorAll('.lc-field-change-row').length || 0;

    function addFieldChangeRow() {
        const idx = window.lcFieldIndex++;
        const container = document.getElementById('lc-field-changes-list');
        if (!container) return;

        const row = document.createElement('div');
        row.className = 'lc-field-change-row';
        row.dataset.index = idx;

        let fieldOptions = '<option value="">-- Select field --</option>';
        (window.lcDocumentFields || []).forEach(function (field) {
            fieldOptions += '<option value="' + field.field_name + '" data-current-value="' + (field.current_value || '') + '">' + field.field_label + '</option>';
        });

        row.innerHTML =
            '<button type="button" class="lc-remove-field-btn" onclick="this.closest(\'.lc-field-change-row\').remove()" title="Remove">' +
            '<i class="fas fa-times"></i></button>' +
            '<div class="lc-form-grid">' +
            '<div class="lc-form-group">' +
            '<label>Field to Correct <span class="required">*</span></label>' +
            '<select name="field_changes[' + idx + '][field_name]" class="lc-field-name-select" required onchange="lcOnFieldSelect(this, ' + idx + ')">' +
            fieldOptions + '</select>' +
            '<input type="hidden" name="field_changes[' + idx + '][field_label]" class="lc-field-label-hidden">' +
            '</div>' +
            '<div class="lc-form-group">' +
            '<label>Current Value</label>' +
            '<input type="text" name="field_changes[' + idx + '][current_value]" class="lc-current-value" readonly placeholder="Auto-populated">' +
            '</div>' +
            '<div class="lc-form-group">' +
            '<label>Proposed New Value <span class="required">*</span></label>' +
            '<input type="text" name="field_changes[' + idx + '][proposed_value]" required placeholder="Enter corrected value">' +
            '</div>' +
            '<div class="lc-form-group full-width">' +
            '<label>Justification</label>' +
            '<textarea name="field_changes[' + idx + '][justification]" rows="2" placeholder="Why this field needs correction..."></textarea>' +
            '</div>' +
            '</div>';

        container.appendChild(row);
    }

    // ── Field select change handler ──
    window.lcOnFieldSelect = function (select, idx) {
        const row = select.closest('.lc-field-change-row');
        const selectedOption = select.options[select.selectedIndex];
        const currentValueInput = row.querySelector('.lc-current-value');
        const labelHidden = row.querySelector('.lc-field-label-hidden');

        if (selectedOption && selectedOption.value) {
            const currentVal = selectedOption.dataset.currentValue || '';
            if (currentValueInput) currentValueInput.value = currentVal;
            if (labelHidden) labelHidden.value = selectedOption.textContent;
        } else {
            if (currentValueInput) currentValueInput.value = '';
            if (labelHidden) labelHidden.value = '';
        }
    };

    // ── Attachments dynamic rows ──
    const addAttachBtn = document.getElementById('lc-add-attachment-btn');
    if (addAttachBtn) {
        addAttachBtn.addEventListener('click', function () {
            addAttachmentRow();
        });
    }

    window.lcAttachIndex = document.querySelectorAll('.lc-attachment-row').length || 0;

    function addAttachmentRow() {
        const idx = window.lcAttachIndex++;
        const container = document.getElementById('lc-attachments-list');
        if (!container) return;

        const row = document.createElement('div');
        row.className = 'lc-attachment-row';

        row.innerHTML =
            '<div class="lc-form-group">' +
            '<label>File</label>' +
            '<input type="file" name="attachments[' + idx + '][file]" accept=".pdf,.jpg,.jpeg,.png" required>' +
            '</div>' +
            '<div class="lc-form-group">' +
            '<label>Document Type</label>' +
            '<select name="attachments[' + idx + '][file_type]" required>' +
            '<option value="">-- Select type --</option>' +
            '<option value="supporting_affidavit">Supporting Affidavit</option>' +
            '<option value="government_id">Government-Issued ID</option>' +
            '<option value="baptismal_certificate">Baptismal Certificate</option>' +
            '<option value="school_record">School Record</option>' +
            '<option value="nso_copy">NSO/PSA Certified Copy</option>' +
            '<option value="medical_record">Medical Record</option>' +
            '<option value="cedula">Community Tax Certificate</option>' +
            '<option value="other">Other</option>' +
            '</select>' +
            '</div>' +
            '<button type="button" class="lc-remove-attach-btn" onclick="this.closest(\'.lc-attachment-row\').remove()" title="Remove">' +
            '<i class="fas fa-times"></i></button>';

        container.appendChild(row);
    }

    // ── Form submit confirmation ──
    const petitionForm = document.getElementById('lc-petition-form');
    if (petitionForm) {
        petitionForm.addEventListener('submit', function (e) {
            const fieldRows = document.querySelectorAll('.lc-field-change-row');
            if (fieldRows.length === 0) {
                e.preventDefault();
                alert('Please add at least one field correction before submitting.');
                return;
            }
        });
    }

    // ── Submit for approval confirmation ──
    const submitBtn = document.getElementById('lc-submit-for-approval-btn');
    if (submitBtn) {
        submitBtn.addEventListener('click', function (e) {
            if (!confirm('Submit this petition for LCRO/Admin approval? Once submitted, it cannot be edited.')) {
                e.preventDefault();
            }
        });
    }
});
