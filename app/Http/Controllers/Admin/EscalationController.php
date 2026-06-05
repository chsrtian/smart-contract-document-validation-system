<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CorrectionRequest;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class EscalationController extends Controller
{
    public function index()
    {
        $escalations = CorrectionRequest::with(['scan', 'requester', 'assignedSupervisor'])
            ->escalated()
            ->where('escalation_status', 'pending')
            ->orderBy('escalated_at', 'asc')
            ->paginate(25);

        // Get supervisors for assignment dropdown
        $supervisors = User::role('supervisor')->active()->get();

        return view('admin.escalations.index', compact('escalations', 'supervisors'));
    }

    public function resolved()
    {
        $escalations = CorrectionRequest::with(['scan', 'requester', 'assignedSupervisor', 'reviewer'])
            ->escalated()
            ->where('escalation_status', 'resolved')
            ->orderBy('reviewed_at', 'desc')
            ->paginate(25);

        return view('admin.escalations.resolved', compact('escalations'));
    }

    public function assign(Request $request, CorrectionRequest $correctionRequest)
    {
        $request->validate([
            'supervisor_id' => 'required|exists:users,id',
        ]);

        // Verify supervisor role
        $supervisor = User::findOrFail($request->supervisor_id);
        if (!$supervisor->hasRole('supervisor')) {
            return redirect()->back()->with('error', 'Selected user is not a supervisor.');
        }

        if (!$correctionRequest->isEscalated()) {
            return redirect()->back()->with('error', 'This correction request is not escalated.');
        }

        $correctionRequest->update([
            'assigned_to' => $request->supervisor_id,
            'escalation_status' => 'assigned',
        ]);

        // Log audit
        AuditLogService::log(
            'correction.escalation_assigned',
            $correctionRequest,
            ['assigned_to' => null, 'escalation_status' => 'pending'],
            ['assigned_to' => $supervisor->name, 'escalation_status' => 'assigned'],
            'info',
            "Escalated correction assigned to supervisor: {$supervisor->name}"
        );

        // TODO: Send notification to supervisor (implement in future)

        return redirect()
            ->back()
            ->with('success', "Escalation assigned to {$supervisor->name} successfully.");
    }
}