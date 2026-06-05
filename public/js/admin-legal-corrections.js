/**
 * Legal Corrections (Petitions) – Admin Side JS
 * Handles admin approval/rejection interactions
 */
document.addEventListener('DOMContentLoaded', function () {

    const approveForm = document.getElementById('alc-approve-form');
    const rejectForm  = document.getElementById('alc-reject-form');

    // ── Toggle approve form ──
    const approveToggleBtn = document.getElementById('alc-show-approve');
    if (approveToggleBtn && approveForm) {
        approveToggleBtn.addEventListener('click', function () {
            approveForm.style.display = approveForm.style.display === 'none' ? 'block' : 'none';
            // Hide reject form if open
            if (rejectForm) rejectForm.style.display = 'none';
        });
    }

    // ── Toggle reject form ──
    const rejectToggleBtn = document.getElementById('alc-show-reject');
    if (rejectToggleBtn && rejectForm) {
        rejectToggleBtn.addEventListener('click', function () {
            rejectForm.style.display = rejectForm.style.display === 'none' ? 'block' : 'none';
            // Hide approve form if open
            if (approveForm) approveForm.style.display = 'none';
        });
    }

    // ── Cancel buttons ──
    const cancelApproveBtn = document.getElementById('alc-cancel-approve');
    if (cancelApproveBtn && approveForm) {
        cancelApproveBtn.addEventListener('click', function () {
            approveForm.style.display = 'none';
        });
    }

    const cancelRejectBtn = document.getElementById('alc-cancel-reject');
    if (cancelRejectBtn && rejectForm) {
        cancelRejectBtn.addEventListener('click', function () {
            rejectForm.style.display = 'none';
        });
    }

    // ── Confirm approve ──
    const approveSubmitBtn = document.getElementById('alc-confirm-approve');
    if (approveSubmitBtn) {
        approveSubmitBtn.addEventListener('click', function (e) {
            if (!confirm('Approve this Legal Correction Petition? A marginal annotation will be generated automatically.')) {
                e.preventDefault();
            }
        });
    }

    // ── Confirm reject ──
    const rejectSubmitBtn = document.getElementById('alc-confirm-reject');
    if (rejectSubmitBtn) {
        rejectSubmitBtn.addEventListener('click', function (e) {
            const reasonField = document.getElementById('rejection_reason');
            if (reasonField && reasonField.value.trim().length < 10) {
                e.preventDefault();
                alert('Please provide a rejection reason of at least 10 characters.');
                reasonField.focus();
                return;
            }
            if (!confirm('Reject this Legal Correction Petition? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    }

    // ── Confirm forward to PSA ──
    const forwardBtn = document.getElementById('alc-forward-psa-btn');
    if (forwardBtn) {
        forwardBtn.addEventListener('click', function (e) {
            if (!confirm('Forward this approved petition to PSA (simulated)? This will mark the petition as "Forwarded to PSA".')) {
                e.preventDefault();
            }
        });
    }
});
