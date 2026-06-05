<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\LegalCorrectionPetition;
use App\Models\PetitionAttachment;
use App\Models\Scan;
use App\Services\LegalCorrectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LegalCorrectionPetitionController extends Controller
{
    protected LegalCorrectionService $service;

    public function __construct(LegalCorrectionService $service)
    {
        $this->service = $service;
    }

    /**
     * Display list of petitions for current staff.
     */
    public function index(Request $request)
    {
        $userId = Auth::id();
        $query = LegalCorrectionPetition::with(['scan', 'fieldChanges'])
            ->where('created_by', $userId)
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by petition type
        if ($request->filled('petition_type')) {
            $query->where('petition_type', $request->petition_type);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('petition_number', 'like', "%{$search}%")
                  ->orWhere('petitioner_name', 'like', "%{$search}%");
            });
        }

        $petitions = $query->paginate(15);
        $stats = $this->service->getStats($userId);

        return view('corrections.staff.petitions.index', compact('petitions', 'stats'));
    }

    /**
     * Show petition creation form.
     */
    public function create(Request $request)
    {
        // Get eligible documents (blockchain confirmed birth/death/marriage certs)
        $eligibleDocuments = Scan::where('blockchain_status', 'confirmed')
            ->whereNotNull('blockchain_tx_hash')
            ->whereIn('document_type', ['birth_certificate', 'death_certificate', 'marriage_certificate'])
            ->orderBy('created_at', 'desc')
            ->get();

        $selectedScanId = $request->query('scan_id');
        $petitionTypes = LegalCorrectionPetition::PETITION_TYPES;
        $relationshipTypes = LegalCorrectionPetition::RELATIONSHIP_TYPES;

        return view('corrections.staff.petitions.create', compact(
            'eligibleDocuments',
            'selectedScanId',
            'petitionTypes',
            'relationshipTypes'
        ));
    }

    /**
     * Store a new petition.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'scan_id' => 'required|exists:scans,id',
            'petition_type' => 'required|in:' . implode(',', array_keys(LegalCorrectionPetition::PETITION_TYPES)),
            'petitioner_name' => 'required|string|max:255',
            'petitioner_address' => 'nullable|string|max:500',
            'petitioner_relationship' => 'required|in:' . implode(',', array_keys(LegalCorrectionPetition::RELATIONSHIP_TYPES)),
            'reason' => 'required|string|min:10|max:2000',
            'supporting_affidavit' => 'nullable|string|max:5000',
            // Field changes
            'field_changes' => 'required|array|min:1',
            'field_changes.*.field_name' => 'required|string|max:255',
            'field_changes.*.field_label' => 'nullable|string|max:255',
            'field_changes.*.current_value' => 'nullable|string|max:1000',
            'field_changes.*.proposed_value' => 'required|string|max:1000',
            'field_changes.*.justification' => 'nullable|string|max:1000',
            // Attachments
            'attachments' => 'nullable|array',
            'attachments.*.file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'attachments.*.file_type' => 'required|in:' . implode(',', array_keys(PetitionAttachment::FILE_TYPES)),
            'attachments.*.description' => 'nullable|string|max:500',
        ], [
            'field_changes.required' => 'At least one field correction must be specified.',
            'field_changes.min' => 'At least one field correction must be specified.',
            'reason.min' => 'The reason must be at least 10 characters.',
        ]);

        // Verify the scan is eligible
        $scan = Scan::findOrFail($validated['scan_id']);
        if ($scan->blockchain_status !== 'confirmed') {
            return back()->with('error', 'This document is not eligible for legal correction petitions.');
        }

        try {
            $petition = $this->service->createPetition($validated, Auth::id());

            // Upload attachments if provided
            if ($request->hasFile('attachments')) {
                $files = [];
                foreach ($request->file('attachments') as $index => $file) {
                    $files[] = [
                        'file' => $file['file'] ?? $file,
                        'file_type' => $validated['attachments'][$index]['file_type'] ?? 'other',
                        'description' => $validated['attachments'][$index]['description'] ?? null,
                    ];
                }
                $this->service->uploadAttachments($petition, $files, Auth::id());
            }

            return redirect()->route('corrections.petitions.show', $petition)
                ->with('success', 'Legal correction petition created successfully as draft.');

        } catch (\Exception $e) {
            Log::error('Failed to create legal correction petition', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()->withInput()
                ->with('error', 'Failed to create petition. Please try again.');
        }
    }

    /**
     * Show petition details.
     */
    public function show(LegalCorrectionPetition $petition)
    {
        // Staff can only view own petitions (unless admin)
        if ($petition->created_by !== Auth::id() && !Auth::user()->hasRole('admin')) {
            abort(403, 'You can only view your own petitions.');
        }

        $petition->load(['scan', 'fieldChanges', 'attachments', 'annotations', 'psaForwardingLog', 'creator', 'approver']);

        return view('corrections.staff.petitions.show', compact('petition'));
    }

    /**
     * Open a print-ready view for an approved petition.
     */
    public function printView(Request $request, LegalCorrectionPetition $petition)
    {
        if ($petition->created_by !== Auth::id() && !Auth::user()->hasRole('admin')) {
            abort(403);
        }

        if (!in_array($petition->status, [
            LegalCorrectionPetition::STATUS_APPROVED,
            LegalCorrectionPetition::STATUS_FORWARDED,
        ])) {
            abort(403, 'Only approved petitions can be printed.');
        }

        $petition->load(['scan', 'fieldChanges', 'annotations.annotator', 'creator', 'approver', 'psaForwardingLog']);

        // Remarks entered by the staff member via the pre-print modal.
        // Strip HTML to prevent XSS; Blade will also auto-escape on output.
        $remarks = strip_tags($request->input('remarks', ''));

        return view('corrections.staff.petitions.print', compact('petition', 'remarks'));
    }

    /**
     * Submit petition for approval (draft → pending_approval).
     */
    public function submit(LegalCorrectionPetition $petition)
    {
        if ($petition->created_by !== Auth::id()) {
            abort(403, 'You can only submit your own petitions.');
        }

        if ($this->service->submitForApproval($petition)) {
            return redirect()->route('corrections.petitions.show', $petition)
                ->with('success', 'Petition submitted for LCRO/Admin approval.');
        }

        return back()->with('error', 'Petition cannot be submitted. Ensure it is still in draft status and has at least one field change.');
    }

    /**
     * AJAX: Get correctable fields for a selected document.
     */
    public function getDocumentFields(Request $request)
    {
        $request->validate(['scan_id' => 'required|exists:scans,id']);

        $scan = Scan::findOrFail($request->scan_id);
        $extractedFields = $scan->extracted_fields ?? [];

        if (is_string($extractedFields)) {
            $extractedFields = json_decode($extractedFields, true) ?? [];
        }

        $fields = [];
        foreach ($extractedFields as $key => $value) {
            $displayValue = is_array($value) ? ($value['value'] ?? json_encode($value)) : (string) $value;
            $fields[] = [
                'field_name' => $key,
                'field_label' => ucwords(str_replace('_', ' ', $key)),
                'current_value' => $displayValue,
            ];
        }

        return response()->json([
            'document_type' => $scan->document_type,
            'document_id' => $scan->document_id,
            'title' => $scan->title,
            'fields' => $fields,
        ]);
    }
}
