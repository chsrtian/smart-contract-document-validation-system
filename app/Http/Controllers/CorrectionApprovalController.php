<?php

namespace App\Http\Controllers;

use App\Models\CorrectionRequest;
use App\Models\CorrectionRecord;
use App\Models\Scan;
use App\Services\BlockchainService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CorrectionApprovalController extends Controller
{
    protected BlockchainService $blockchainService;

    public function __construct(BlockchainService $blockchainService)
    {
        $this->blockchainService = $blockchainService;
    }

    /**
     * Display dashboard with pending correction requests for supervisor.
     */
    public function dashboard()
    {
        $pendingCount = CorrectionRequest::pending()->count();
        $approvedToday = CorrectionRequest::approved()
            ->whereDate('reviewed_at', today())
            ->count();
        $rejectedToday = CorrectionRequest::rejected()
            ->whereDate('reviewed_at', today())
            ->count();

        $recentRequests = CorrectionRequest::with(['scan', 'requester'])
            ->pending()
            ->orderBy('requested_at', 'asc') 
            ->take(10)
            ->get();

        return view('corrections.supervisor.dashboard', compact(
            'pendingCount',
            'approvedToday',
            'rejectedToday',
            'recentRequests'
        ));
    }

    /**
     * Display all pending correction requests.
     */
    public function pending(Request $request)
    {
        $query = CorrectionRequest::with(['scan', 'requester'])
            ->pending()
            ->orderBy('requested_at', 'asc'); 

        // Filter by document type
        if ($request->filled('document_type')) {
            $query->whereHas('scan', function ($q) use ($request) {
                $q->where('document_type', $request->document_type);
            });
        }

        if ($request->filled('requester_id')) {
            $query->where('requested_by', $request->requester_id);
        }

        $requests = $query->paginate(15);

        return view('corrections.supervisor.pending', compact('requests'));
    }

    /**
     * Display a specific correction request for review.
     */
    public function review(CorrectionRequest $correctionRequest)
    {
        if (!$correctionRequest->isPending()) {
            return redirect()->route('corrections.approval.history')
                ->with('info', 'This request has already been reviewed.');
        }

        $correctionRequest->load(['scan', 'requester']);

        // Get the original document details
        $scan = $correctionRequest->scan;
        $extractedFields = $scan->extracted_fields ?? [];

        // Get any existing corrections for this document
        $existingCorrections = $scan->getCorrectionHistory();

        // Get the current effective value (considering previous corrections)
        $currentEffectiveValue = $scan->getCorrectedValue($correctionRequest->field_name);

        return view('corrections.supervisor.review', compact(
            'correctionRequest',
            'scan',
            'extractedFields',
            'existingCorrections',
            'currentEffectiveValue'
        ));
    }

    /**
     * Display details of a reviewed (approved/rejected) correction request.
     */
    public function show(CorrectionRequest $correctionRequest)
    {
        // Load relationships
        $correctionRequest->load(['scan', 'requester', 'reviewer', 'correctionRecord']);

        // Get the scan details
        $scan = $correctionRequest->scan;
        $extractedFields = $scan->extracted_fields ?? [];
        
        // Handle double-encoded JSON
        if (is_string($extractedFields)) {
            $extractedFields = json_decode($extractedFields, true) ?? [];
        }
        if (is_string($extractedFields)) {
            $extractedFields = json_decode($extractedFields, true) ?? [];
        }

        // Get correction record if exists
        $correctionRecord = $correctionRequest->correctionRecord;

        return view('corrections.supervisor.show', compact(
            'correctionRequest',
            'scan',
            'extractedFields',
            'correctionRecord'
        ));
    }
    
    /**
     * Approve a correction request.
     */
    public function approve(Request $request, CorrectionRequest $correctionRequest)
    {
        if (!$correctionRequest->isPending()) {
            return back()->with('error', 'This request has already been reviewed.');
        }

        DB::beginTransaction();

        try {
            // 1. Approve the request
            $correctionRequest->approve(Auth::user());

            // 2. Create the correction record
            $correctionRecord = CorrectionRecord::createFromRequest($correctionRequest, Auth::user());

            // 3. Link the correction record to the request
            $correctionRequest->update(['correction_record_id' => $correctionRecord->id]);

            // 4. Anchor the correction to blockchain
            $blockchainResult = $this->blockchainService->anchorCorrection($correctionRecord);

            if (!$blockchainResult['success']) {
                throw new \Exception('Failed to anchor correction to blockchain: ' . ($blockchainResult['error'] ?? 'Unknown error'));
            }

            DB::commit();

            Log::info('Correction request approved and anchored', [
                'request_id' => $correctionRequest->id,
                'correction_record_id' => $correctionRecord->id,
                'correction_tx_hash' => $blockchainResult['correction_tx_hash'],
                'approved_by' => Auth::id(),
            ]);

            return redirect()->route('corrections.approval.pending')
                ->with('success', 'Correction approved and anchored to blockchain successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to approve correction request', [
                'request_id' => $correctionRequest->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Failed to approve correction: ' . $e->getMessage());
        }
    }

    /**
     * Reject a correction request.
     */
    public function reject(Request $request, CorrectionRequest $correctionRequest)
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|min:10|max:1000',
        ], [
            'rejection_reason.required' => 'You must provide a reason for rejection.',
            'rejection_reason.min' => 'The rejection reason must be at least 10 characters.',
        ]);

        if (!$correctionRequest->isPending()) {
            return back()->with('error', 'This request has already been reviewed.');
        }

        try {
            $correctionRequest->reject(Auth::user(), $validated['rejection_reason']);

            Log::info('Correction request rejected', [
                'request_id' => $correctionRequest->id,
                'rejected_by' => Auth::id(),
                'reason' => $validated['rejection_reason'],
            ]);

            return redirect()->route('corrections.approval.pending')
                ->with('success', 'Correction request rejected.');

        } catch (\Exception $e) {
            Log::error('Failed to reject correction request', [
                'request_id' => $correctionRequest->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to reject correction request.');
        }
    }

    /**
     * Display history of all reviewed requests.
     */
    public function history(Request $request)
    {
        $query = CorrectionRequest::with(['scan', 'requester', 'reviewer'])
            ->whereIn('status', [CorrectionRequest::STATUS_APPROVED, CorrectionRequest::STATUS_REJECTED])
            ->orderBy('reviewed_at', 'desc');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('reviewed_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('reviewed_at', '<=', $request->date_to);
        }

        // Filter by reviewer
        if ($request->filled('reviewer_id')) {
            $query->where('reviewed_by', $request->reviewer_id);
        }

        $requests = $query->paginate(20);

        // Add counts for the view
        $approvedCount = CorrectionRequest::approved()->count();
        $rejectedCount = CorrectionRequest::rejected()->count();

        return view('corrections.supervisor.history', compact('requests', 'approvedCount', 'rejectedCount'));
    }

    /**
     * Display full audit log of all corrections.
     */
    public function auditLog(Request $request)
    {
        $query = CorrectionRecord::with(['scan', 'staff', 'supervisor', 'correctionRequest'])
            ->orderBy('created_at', 'desc');

        // Filter by blockchain status
        if ($request->filled('blockchain_status')) {
            $query->where('blockchain_status', $request->blockchain_status);
        }

        // Filter by document
        if ($request->filled('scan_id')) {
            $query->where('scan_id', $request->scan_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $records = $query->paginate(20);

        // Total records count for the header
        $totalRecords = CorrectionRecord::count();

        // Get statistics
        $stats = [
            'total_corrections' => $totalRecords,
            'confirmed_on_blockchain' => CorrectionRecord::confirmed()->count(),
            'pending_blockchain' => CorrectionRecord::where('blockchain_status', 'pending')->count(),
            'failed_blockchain' => CorrectionRecord::where('blockchain_status', 'failed')->count(),
        ];

        return view('corrections.supervisor.audit-log', compact('records', 'totalRecords', 'stats'));
    }

    /**
     * View details of a specific correction record.
     */
    public function viewCorrection(CorrectionRecord $correctionRecord)
    {
        $correctionRecord->load(['scan', 'staff', 'supervisor', 'correctionRequest']);

        return view('corrections.supervisor.correction-detail', compact('correctionRecord'));
    }

    /**
     * View original document with all corrections applied.
     */
    public function viewDocumentWithCorrections(Scan $scan)
    {
        $fieldsWithCorrections = $scan->getFieldsWithCorrections();
        $correctionHistory = $scan->getCorrectionHistory();
        $pendingRequests = $scan->pendingCorrectionRequests()->with('requester')->get();

        return view('corrections.supervisor.document-view', compact(
            'scan',
            'fieldsWithCorrections',
            'correctionHistory',
            'pendingRequests'
        ));
    }

    /**
     * API: Get correction statistics.
     */
    public function statistics()
    {
        $stats = [
            'pending_requests' => CorrectionRequest::pending()->count(),
            'approved_today' => CorrectionRequest::approved()->whereDate('reviewed_at', today())->count(),
            'rejected_today' => CorrectionRequest::rejected()->whereDate('reviewed_at', today())->count(),
            'total_corrections' => CorrectionRecord::confirmed()->count(),
            'corrections_this_week' => CorrectionRecord::confirmed()
                ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->count(),
            'corrections_this_month' => CorrectionRecord::confirmed()
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Retry failed blockchain anchoring for a correction.
     */
    public function retryBlockchainAnchoring(CorrectionRecord $correctionRecord)
    {
        if ($correctionRecord->blockchain_status !== CorrectionRecord::BLOCKCHAIN_FAILED) {
            return back()->with('error', 'Only failed corrections can be retried.');
        }

        try {
            $result = $this->blockchainService->anchorCorrection($correctionRecord);

            if ($result['success']) {
                Log::info('Correction blockchain anchoring retried successfully', [
                    'correction_id' => $correctionRecord->id,
                    'tx_hash' => $result['correction_tx_hash'],
                ]);

                return back()->with('success', 'Correction successfully anchored to blockchain.');
            }

            return back()->with('error', 'Failed to anchor correction: ' . ($result['error'] ?? 'Unknown error'));

        } catch (\Exception $e) {
            Log::error('Failed to retry correction anchoring', [
                'correction_id' => $correctionRecord->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to retry: ' . $e->getMessage());
        }
    }
}