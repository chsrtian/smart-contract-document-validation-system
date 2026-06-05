<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CorrectionRequest;
use App\Models\CorrectionRecord;
use App\Models\Scan;
use App\Models\User;
use App\Services\AuditLogService;
use App\Jobs\AnchorToBlockchainJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CorrectionOversightController extends Controller
{
    public function index(Request $request)
{
    $query = CorrectionRequest::with(['scan', 'requester', 'reviewer']);

    // Filter by status (default to pending for most relevant admin action)
    $status = $request->input('status', 'pending');
    if ($status && $status !== 'all') {
        $query->where('status', $status);
    }

    // Filter by requester
    if ($request->filled('requester')) {
        $query->where('requested_by', $request->requester);
    }

    // Filter by document ID
    if ($request->filled('document_id')) {
        $query->where('scan_id', $request->document_id);
    }

    // Filter by date range
    if ($request->filled('date_from')) {
        $query->whereDate('requested_at', '>=', $request->date_from);
    }
    if ($request->filled('date_to')) {
        $query->whereDate('requested_at', '<=', $request->date_to);
    }

    // Search
    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('id', 'like', "%{$search}%")
              ->orWhere('scan_id', 'like', "%{$search}%")
              ->orWhere('field_name', 'like', "%{$search}%");
        });
    }

    $corrections = $query->orderBy('requested_at', 'desc')->paginate(25);

    // Get requesters for filter dropdown
    $requesters = User::role('staff')
    ->whereIn('id', function($query) {
        $query->select('requested_by')
            ->from('correction_requests')
            ->whereNotNull('requested_by')
            ->distinct();
    })
    ->get();

return view('admin.corrections.index', compact('corrections', 'requesters'));
}

    public function show(CorrectionRequest $correctionRequest)
    {
        $correctionRequest->load(['scan', 'requester', 'reviewer', 'correctionRecord']);
        $isOverridden = $correctionRequest->isOverridden();
        return view('admin.corrections.show', compact('correctionRequest', 'isOverridden'));
    }

    public function approve(Request $request, CorrectionRequest $correctionRequest)
    {
        // Check if already approved
        if ($correctionRequest->status !== CorrectionRequest::STATUS_PENDING) {
            return redirect()->back()->with('error', 'This correction request is not pending.');
        }

        DB::beginTransaction();
        try {
            // Update correction request
            $correctionRequest->update([
                'status' => CorrectionRequest::STATUS_APPROVED,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

            // Apply correction to document
            $scan = $correctionRequest->scan;
            $fieldName = $correctionRequest->field_name;
            $oldValue = $correctionRequest->current_value;
            $newValue = $correctionRequest->proposed_value;

            // Update the field in extracted_fields JSON
            $extractedFields = $scan->extracted_fields ?? [];
            $extractedFields[$fieldName] = $newValue;
            $scan->extracted_fields = $extractedFields;
            $scan->save();

            // Create correction record
            $correctionRecord = CorrectionRecord::create([
                'scan_id' => $scan->id,
                'correction_request_id' => $correctionRequest->id,
                'corrected_field' => $fieldName,
                'previous_value' => $oldValue,
                'new_value' => $newValue,
                'corrected_by_staff' => $correctionRequest->requested_by,
                'approved_by_supervisor' => auth()->id(),
                'reference_tx_hash' => $scan->blockchain_tx_hash ?? 'pending_' . $scan->id,
                'correction_reason' => $correctionRequest->reason,
                'blockchain_status' => 'pending',
            ]);

            // Link correction record to request
            $correctionRequest->update(['correction_record_id' => $correctionRecord->id]);

            // Trigger re-anchoring if document was previously anchored
            if ($scan->blockchain_status === 'confirmed') {
                AnchorToBlockchainJob::dispatch($scan);
            }

            // Log audit
            AuditLogService::log(
                'correction.approved',
                $correctionRequest,
                ['status' => CorrectionRequest::STATUS_PENDING],
                ['status' => CorrectionRequest::STATUS_APPROVED, 'approved_by' => auth()->user()->name],
                'info',
                "Admin approved correction request for {$fieldName} on document #{$scan->id}"
            );

            DB::commit();

            if ($correctionRequest->isEscalated() && $correctionRequest->escalation_status !== 'resolved') {
                $correctionRequest->update(['escalation_status' => 'resolved']);
            }

            return redirect()
                ->route('admin.corrections.index')
                ->with('success', 'Correction request approved successfully. Document has been updated.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to approve correction: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, CorrectionRequest $correctionRequest)
    {
        // Check if already rejected
        if ($correctionRequest->status !== CorrectionRequest::STATUS_PENDING) {
            return redirect()->back()->with('error', 'This correction request is not pending.');
        }

        // Validate rejection reason
        $request->validate([
            'rejection_reason' => 'required|string|min:20|max:500',
        ]);

        DB::beginTransaction();
        try {
            // Update correction request
            $correctionRequest->update([
                'status' => CorrectionRequest::STATUS_REJECTED,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'rejection_reason' => $request->rejection_reason,
            ]);

            // Log audit
            AuditLogService::log(
                'correction.rejected',
                $correctionRequest,
                ['status' => CorrectionRequest::STATUS_PENDING],
                ['status' => CorrectionRequest::STATUS_REJECTED, 'rejected_by' => auth()->user()->name],
                'info',
                "Admin rejected correction request: {$request->rejection_reason}"
            );

            DB::commit();

            if ($correctionRequest->isEscalated() && $correctionRequest->escalation_status !== 'resolved') {
                $correctionRequest->update(['escalation_status' => 'resolved']);
            }

            return redirect()
                ->route('admin.corrections.index')
                ->with('success', 'Correction request rejected.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to reject correction: ' . $e->getMessage());
        }
    }

    public function forceApprove(Request $request, CorrectionRequest $correctionRequest)
{
    // Validate justification
    $request->validate([
        'justification' => 'required|string|min:50|max:500',
    ]);

    // Check if can be overridden
    if (!in_array($correctionRequest->status, ['rejected', 'cancelled', CorrectionRequest::STATUS_REJECTED])) {
        return redirect()->back()->with('error', 'Only rejected or cancelled corrections can be force approved.');
    }

    DB::beginTransaction();
    try {
        // Apply correction to document
        $scan = $correctionRequest->scan;
        $fieldName = $correctionRequest->field_name;
        $oldValue = $correctionRequest->current_value;
        $newValue = $correctionRequest->proposed_value;

        // Update the field in extracted_fields JSON
        $extractedFields = $scan->extracted_fields ?? [];
        $extractedFields[$fieldName] = $newValue;
        $scan->extracted_fields = $extractedFields;
        $scan->save();

        // Create correction record if doesn't exist
        if (!$correctionRequest->correctionRecord) {
            $correctionRecord = CorrectionRecord::create([
                'scan_id' => $scan->id,
                'correction_request_id' => $correctionRequest->id,
                'corrected_field' => $fieldName,
                'previous_value' => $oldValue,
                'new_value' => $newValue,
                'corrected_by_staff' => $correctionRequest->requested_by,
                'approved_by_supervisor' => auth()->id(),
                'reference_tx_hash' => $scan->blockchain_tx_hash ?? 'pending_' . $scan->id,
                'correction_reason' => $correctionRequest->reason,
                'blockchain_status' => 'pending',
            ]);

            $correctionRequest->update(['correction_record_id' => $correctionRecord->id]);
        }

        // Update correction request with override information
        $correctionRequest->update([
            'status' => CorrectionRequest::STATUS_APPROVED,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'override_by' => auth()->id(),
            'override_at' => now(),
            'override_justification' => $request->justification,
            'override_type' => 'force_approve',
        ]);

        // Trigger re-anchoring
        if ($scan->blockchain_status === 'confirmed') {
            AnchorToBlockchainJob::dispatch($scan);
        }

        // Log audit with CRITICAL severity
        AuditLogService::log(
            'correction.force_approved',
            $correctionRequest,
            ['status' => $correctionRequest->getOriginal('status')],
            ['status' => CorrectionRequest::STATUS_APPROVED, 'override_by' => auth()->user()->name],
            'critical',
            "ADMIN OVERRIDE - Force Approved: {$request->justification}"
        );

        DB::commit();

        if ($correctionRequest->isEscalated() && $correctionRequest->escalation_status !== 'resolved') {
            $correctionRequest->update(['escalation_status' => 'resolved']);
        }

        return redirect()
            ->route('admin.corrections.show', $correctionRequest)
       
            ->with('success', 'Correction request force approved. Document has been updated. This action has been logged as CRITICAL.');
    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()->with('error', 'Failed to force approve: ' . $e->getMessage());
    }
}

    public function forceReject(Request $request, CorrectionRequest $correctionRequest)
    {
        // Validate justification
        $request->validate([
            'justification' => 'required|string|min:50|max:500',
        ]);

        // Check if already applied to blockchain
        if ($correctionRequest->correctionRecord && 
            $correctionRequest->correctionRecord->blockchain_status === 'confirmed') {
            return redirect()->back()->with('error', 'Cannot force reject: Correction has already been anchored to blockchain. Submit a new correction request to revert changes.');
        }

        DB::beginTransaction();
        try {
            // Update correction request with override information
            $correctionRequest->update([
                'status' => CorrectionRequest::STATUS_REJECTED,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'override_by' => auth()->id(),
                'override_at' => now(),
                'override_justification' => $request->justification,
                'override_type' => 'force_reject',
                'rejection_reason' => $request->justification,
            ]);

            // Note: Do NOT revert document changes if already anchored
            // The blockchain is immutable - original version is preserved on chain

            // Log audit with CRITICAL severity
            AuditLogService::log(
                'correction.force_rejected',
                $correctionRequest,
                ['status' => $correctionRequest->getOriginal('status')],
                ['status' => CorrectionRequest::STATUS_REJECTED, 'override_by' => auth()->user()->name],
                'critical',
                "ADMIN OVERRIDE - Force Rejected: {$request->justification}"
            );

            DB::commit();

        if ($correctionRequest->isEscalated() && $correctionRequest->escalation_status !== 'resolved') {
            $correctionRequest->update(['escalation_status' => 'resolved']);
        }

        return redirect()
            ->route('admin.corrections.show', $correctionRequest)
            ->with('success', 'Correction request force rejected. This action has been logged as CRITICAL.');
        
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to force reject: ' . $e->getMessage());
        }
    }

    public function overrideHistory(Request $request)
    {
        $query = CorrectionRequest::with(['scan', 'requester', 'overrider'])
            ->overridden();

        // Filter by override type
        if ($request->filled('override_type')) {
            $query->where('override_type', $request->override_type);
        }

        $overrides = $query->orderBy('override_at', 'desc')->paginate(25);

        return view('admin.corrections.override-history', compact('overrides'));
    }
}