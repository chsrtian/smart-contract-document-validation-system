<?php


namespace App\Http\Controllers;

use App\Models\CorrectionRequest;
use App\Models\Scan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CorrectionRequestController extends Controller
{
    /**
     * Display a listing of correction requests for the current staff member.
     */
    public function index(Request $request)
    {
        $userId = Auth::id();
        
        $query = CorrectionRequest::with(['scan', 'reviewer'])
            ->where('requested_by', $userId)
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by document type
        if ($request->filled('document_type')) {
            $query->whereHas('scan', function ($q) use ($request) {
                $q->where('document_type', $request->document_type);
            });
        }

        $requests = $query->paginate(15);

        // Get counts for statistics
        $pendingCount = CorrectionRequest::where('requested_by', $userId)->where('status', 'pending')->count();
        $approvedCount = CorrectionRequest::where('requested_by', $userId)->where('status', 'approved')->count();
        $rejectedCount = CorrectionRequest::where('requested_by', $userId)->where('status', 'rejected')->count();
        $cancelledCount = CorrectionRequest::where('requested_by', $userId)->where('status', 'cancelled')->count();

        return view('corrections.staff.index', compact(
            'requests',
            'pendingCount',
            'approvedCount',
            'rejectedCount',
            'cancelledCount'
        ));
    }

    /**
     * Show the form for creating a new correction request.
     */
    public function create(Request $request)
    {
        // Get eligible documents (blockchain-confirmed)
        $eligibleDocuments = Scan::where('blockchain_status', 'confirmed')
            ->whereNotNull('blockchain_tx_hash')
            ->orderBy('created_at', 'desc')
            ->get();

        // Pre-select document if scan_id is provided
        $selectedScanId = $request->query('scan_id');
        $selectedScan = null;

        if ($selectedScanId) {
            $selectedScan = Scan::find($selectedScanId);
            
            // Verify the document can accept corrections
            if ($selectedScan && $selectedScan->blockchain_status !== 'confirmed') {
                return redirect()->route('staff.scans.show', $selectedScan)
                    ->with('error', 'This document cannot accept correction requests. It must be anchored to blockchain first.');
            }
        }

        return view('corrections.staff.create', compact('eligibleDocuments', 'selectedScanId'));
    }

    /**
     * Store a newly created correction request.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'scan_id' => 'required|exists:scans,id',
            'field_name' => 'required|string|max:255',
            'current_value' => 'nullable|string|max:1000', 
            'proposed_value' => 'required|string|max:1000',
            'reason' => 'required|string|min:10|max:2000',
            'supporting_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ], [
            'reason.min' => 'The reason must be at least 10 characters to provide sufficient context.',
            'supporting_document.max' => 'The supporting document must not exceed 5MB.',
        ]);

        $scan = Scan::findOrFail($validated['scan_id']);
        $fieldName = $validated['field_name']; // Define $fieldName here!

        // Verify document can accept corrections
        if ($scan->blockchain_status !== 'confirmed') {
            return back()->with('error', 'This document cannot accept correction requests. It must be anchored to blockchain first.');
        }

        // Use current_value from form if provided, otherwise fetch from scan
        $currentValue = $validated['current_value'] ?? '';
        if (empty($currentValue)) {
            // Fallback: get from scan's correctable fields
            $correctableFields = $scan->getCorrectableFields();
            if (isset($correctableFields[$fieldName])) {
                $currentValue = $correctableFields[$fieldName]['value'] ?? '';
            } else {
                // Secondary fallback to raw extracted_fields
                $extractedFields = $scan->extracted_fields ?? [];
                if (is_string($extractedFields)) {
                    $extractedFields = json_decode($extractedFields, true) ?? [];
                }
                $rawValue = $extractedFields[$fieldName] ?? null;
                
                // Handle if the value itself is an array (nested structure)
                if (is_array($rawValue)) {
                    $currentValue = $rawValue['value'] ?? json_encode($rawValue);
                } else {
                    $currentValue = (string) ($rawValue ?? '');
                }
            }
        }

        // Check for duplicate pending requests for the same field
        $existingRequest = CorrectionRequest::where('scan_id', $scan->id)
            ->where('field_name', $fieldName)
            ->where('status', 'pending')
            ->first();

        if ($existingRequest) {
            return back()
                ->withInput()
                ->with('error', 'A correction request for this field is already pending review.');
        }

        try {
            // Handle supporting document upload
            $supportingDocPath = null;
            if ($request->hasFile('supporting_document')) {
                $supportingDocPath = $request->file('supporting_document')->store('correction-documents', 'public');
            }

            $correctionRequest = CorrectionRequest::create([
                'scan_id' => $scan->id,
                'original_tx_hash' => $scan->blockchain_tx_hash,
                'field_name' => $fieldName,
                'current_value' => $currentValue,
                'proposed_value' => $validated['proposed_value'],
                'reason' => $validated['reason'],
                'supporting_document_path' => $supportingDocPath,
                'requested_by' => Auth::id(),
                'requested_at' => now(),
                'status' => 'pending',
            ]);

            Log::info('Correction request created', [
                'request_id' => $correctionRequest->id,
                'scan_id' => $scan->id,
                'field_name' => $fieldName,
                'requested_by' => Auth::id(),
            ]);

            return redirect()->route('corrections.requests.show', $correctionRequest)
                ->with('success', 'Correction request submitted successfully. It is now pending supervisor approval.');

        } catch (\Exception $e) {
            Log::error('Failed to create correction request', [
                'error' => $e->getMessage(),
                'scan_id' => $scan->id,
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to submit correction request. Please try again.');
        }
    }

    /**
     * Display the specified correction request.
     */
    public function show(CorrectionRequest $correctionRequest)
    {
        // Staff can only view their own requests (unless admin/supervisor)
        if ($correctionRequest->requested_by !== Auth::id() && !Auth::user()->hasRole(['admin', 'supervisor'])) {
            abort(403, 'You can only view your own correction requests.');
        }

        $correctionRequest->load(['scan', 'requester', 'reviewer', 'correctionRecord']);

        // Rename to $request for view consistency
        $request = $correctionRequest;

        return view('corrections.staff.show', compact('request'));
    }

    /**
     * Cancel a pending correction request.
     */
    public function cancel(CorrectionRequest $correctionRequest)
    {
        // Staff can only cancel their own pending requests
        if ($correctionRequest->requested_by !== Auth::id()) {
            abort(403, 'You can only cancel your own correction requests.');
        }

        if ($correctionRequest->status !== 'pending') {
            return back()->with('error', 'Only pending requests can be cancelled.');
        }

        try {
            $correctionRequest->update(['status' => 'cancelled']);

            Log::info('Correction request cancelled', [
                'request_id' => $correctionRequest->id,
                'cancelled_by' => Auth::id(),
            ]);

            return redirect()->route('corrections.requests.index')
                ->with('success', 'Correction request cancelled successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to cancel correction request', [
                'request_id' => $correctionRequest->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to cancel correction request.');
        }
    }

    /**
     * Get list of documents eligible for correction requests.
     */
    public function eligibleDocuments(Request $request)
    {
        $query = Scan::where('blockchain_status', 'confirmed')
            ->whereNotNull('blockchain_tx_hash')
            ->orderBy('created_at', 'desc');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('document_id', 'like', "%{$search}%")
                  ->orWhere('registry_number', 'like', "%{$search}%");
            });
        }

        // Filter by document type
        if ($request->filled('document_type')) {
            $query->where('document_type', $request->document_type);
        }

        $documents = $query->paginate(15);

        return view('corrections.staff.eligible-documents', compact('documents'));
    }

    /**
     * API: Get field details for AJAX requests.
     */
    public function getFieldDetails(Request $request)
    {
        $request->validate([
            'scan_id' => 'required|exists:scans,id',
            'field_name' => 'required|string',
        ]);

        $scan = Scan::findOrFail($request->scan_id);
        $extractedFields = $scan->extracted_fields ?? [];
        $fieldName = $request->field_name;

        if (!isset($extractedFields[$fieldName])) {
            return response()->json(['error' => 'Field not found'], 404);
        }

        $hasPendingCorrection = CorrectionRequest::where('scan_id', $scan->id)
            ->where('field_name', $fieldName)
            ->where('status', 'pending')
            ->exists();

        return response()->json([
            'field_name' => $fieldName,
            'display_name' => ucwords(str_replace('_', ' ', $fieldName)),
            'current_value' => $extractedFields[$fieldName],
            'has_pending_correction' => $hasPendingCorrection,
        ]);
    }
}