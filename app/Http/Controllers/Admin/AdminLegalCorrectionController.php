<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LegalCorrectionPetition;
use App\Models\User;
use App\Services\LegalCorrectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminLegalCorrectionController extends Controller
{
    protected LegalCorrectionService $service;

    public function __construct(LegalCorrectionService $service)
    {
        $this->service = $service;
    }

    /**
     * Admin legal corrections dashboard.
     */
    public function index(Request $request)
    {
        $query = LegalCorrectionPetition::with(['scan', 'creator', 'fieldChanges']);

        // Filter by status (default: pending_approval)
        $status = $request->input('status', 'pending_approval');
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        // Filter by petition type
        if ($request->filled('petition_type')) {
            $query->where('petition_type', $request->petition_type);
        }

        // Filter by staff (creator)
        if ($request->filled('staff_id')) {
            $query->where('created_by', $request->staff_id);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('petition_number', 'like', "%{$search}%")
                  ->orWhere('petitioner_name', 'like', "%{$search}%");
            });
        }

        // Date filters
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $petitions = $query->orderBy('created_at', 'desc')->paginate(25);
        $stats = $this->service->getStats();

        // Staff list for filter dropdown
        $staffMembers = User::role('staff')
            ->whereIn('id', function ($q) {
                $q->select('created_by')
                    ->from('legal_correction_petitions')
                    ->whereNotNull('created_by')
                    ->distinct();
            })
            ->get();

        $petitionTypes = LegalCorrectionPetition::PETITION_TYPES;

        return view('admin.corrections.legal.index', compact(
            'petitions',
            'stats',
            'staffMembers',
            'petitionTypes'
        ));
    }

    /**
     * Show petition detail for admin review.
     */
    public function show(LegalCorrectionPetition $petition)
    {
        $petition->load([
            'scan',
            'creator',
            'approver',
            'rejector',
            'fieldChanges',
            'attachments',
            'annotations',
            'psaForwardingLog',
        ]);

        return view('admin.corrections.legal.show', compact('petition'));
    }

    /**
     * Approve a petition.
     */
    public function approve(Request $request, LegalCorrectionPetition $petition)
    {
        $request->validate([
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        if (!$petition->canBeApproved()) {
            return back()->with('error', 'This petition cannot be approved. It is not in pending status.');
        }

        try {
            $this->service->approvePetition(
                $petition,
                Auth::id(),
                $request->input('admin_notes')
            );

            return redirect()->route('admin.corrections.legal.show', $petition)
                ->with('success', 'Petition approved. Marginal annotation has been generated.');
        } catch (\Exception $e) {
            Log::error('Failed to approve legal correction petition', [
                'petition_id' => $petition->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to approve petition. Please try again.');
        }
    }

    /**
     * Reject a petition.
     */
    public function reject(Request $request, LegalCorrectionPetition $petition)
    {
        $request->validate([
            'rejection_reason' => 'required|string|min:10|max:2000',
            'admin_notes' => 'nullable|string|max:2000',
        ], [
            'rejection_reason.required' => 'A rejection reason is required.',
            'rejection_reason.min' => 'Rejection reason must be at least 10 characters.',
        ]);

        if (!$petition->canBeApproved()) {
            return back()->with('error', 'This petition cannot be rejected. It is not in pending status.');
        }

        try {
            $this->service->rejectPetition(
                $petition,
                Auth::id(),
                $request->input('rejection_reason'),
                $request->input('admin_notes')
            );

            return redirect()->route('admin.corrections.legal.show', $petition)
                ->with('success', 'Petition has been rejected.');
        } catch (\Exception $e) {
            Log::error('Failed to reject legal correction petition', [
                'petition_id' => $petition->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to reject petition. Please try again.');
        }
    }

    /**
     * Forward approved petition to PSA (simulated).
     */
    public function forwardToPsa(LegalCorrectionPetition $petition)
    {
        if (!$petition->canBeForwarded()) {
            return back()->with('error', 'This petition cannot be forwarded. It must be approved first.');
        }

        try {
            $log = $this->service->forwardToPsa($petition, Auth::id());

            return redirect()->route('admin.corrections.legal.show', $petition)
                ->with('success', 'Petition forwarded to PSA (simulated). Reference: ' . $log->forwarding_reference);
        } catch (\Exception $e) {
            Log::error('Failed to forward petition to PSA', [
                'petition_id' => $petition->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to forward petition. Please try again.');
        }
    }
}
