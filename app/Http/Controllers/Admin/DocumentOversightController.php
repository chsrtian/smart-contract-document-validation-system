<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Scan;
use App\Models\User;
use App\Services\DocumentStatisticsService;
use Illuminate\Http\Request;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\DB;

class DocumentOversightController extends Controller
{
    public function index(Request $request)
    {
        $query = Scan::with(['user', 'processedBy']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('verification_status', $request->status);
        }

        // Filter by document type
        if ($request->filled('type')) {
            $query->where('document_type', $request->type);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by processor (staff member)
        if ($request->filled('processor')) {
            $query->where('processed_by', $request->processor);
        }

        // Filter by blockchain status
        if ($request->filled('blockchain_status')) {
            if ($request->blockchain_status === 'not_submitted') {
                $query->where(function ($q) {
                    $q->whereNull('blockchain_status')
                        ->orWhere('blockchain_status', '');
                });
            } else {
                $query->where('blockchain_status', $request->blockchain_status);
            }
        }

        // Filter flagged documents
        if ($request->filled('flagged') && $request->flagged == '1') {
            $query->where('flagged', true);
        }

        // Filter locked documents
        if ($request->filled('locked') && $request->locked == '1') {
            $query->where('locked', true);
        }

        // Search
        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhere('document_id', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('document_type', 'like', "%{$search}%");
            });
        }

        $allowedSortColumns = ['id', 'document_type', 'verification_status', 'blockchain_status', 'created_at'];
        $sort = (string) $request->input('sort', 'created_at');
        if (!in_array($sort, $allowedSortColumns, true)) {
            $sort = 'created_at';
        }

        $direction = strtolower((string) $request->input('direction', 'desc'));
        $direction = $direction === 'asc' ? 'asc' : 'desc';

        $allowedPerPage = [10, 25, 50, 100];
        $perPage = (int) $request->input('per_page', 25);
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 25;
        }

        $documents = $query
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->appends($request->query());

        // Get statistics
        $statistics = DocumentStatisticsService::getStats();

        // Get processors for filter dropdown
        $processors = User::role('staff')
            ->whereIn('id', function ($query) {
                $query->select('processed_by')
                    ->from('scans')
                    ->whereNotNull('processed_by')
                    ->distinct();
            })
            ->get();

        $lastUpdatedAt = now();

        return view('admin.documents.index', compact(
            'documents',
            'statistics',
            'processors',
            'sort',
            'direction',
            'perPage',
            'lastUpdatedAt'
        ));
    }

    public function bulkAction(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|in:flag,lock',
            'document_ids' => 'required|array|min:1',
            'document_ids.*' => 'exists:scans,id',
        ]);

        $documents = Scan::whereIn('id', $validated['document_ids'])->get();

        if ($documents->isEmpty()) {
            return redirect()->back()->with('error', 'No documents found for the selected bulk action.');
        }

        $action = $validated['action'];
        $processed = 0;

        DB::beginTransaction();
        try {
            foreach ($documents as $document) {
                if ($this->applyBulkActionToDocument($document, $action)) {
                    $processed++;
                }
            }

            DB::commit();
            DocumentStatisticsService::clearCache();
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Bulk action failed: ' . $e->getMessage());
        }

        if ($processed === 0) {
            return redirect()->back()->with('info', 'No selected documents were updated.');
        }

        $actionLabel = match ($action) {
            'flag' => 'flagged',
            'lock' => 'locked',
            default => 'updated',
        };

        return redirect()->back()->with('success', "Bulk action complete: {$processed} document(s) {$actionLabel}.");
    }

    public function show(Scan $document)
    {
        $document->load(['user', 'processedBy', 'verifiedBy', 'reviewedBy']);
        return view('admin.documents.show', compact('document'));
    }

    public function lock(Request $request, Scan $document)
{
    $request->validate([
        'reason' => 'required|string|min:20|max:500',
    ]);

    if ($document->isLocked()) {
        return redirect()->back()->with('error', 'Document is already locked.');
    }

    DB::beginTransaction();
    try {
        $document->update([
            'locked' => true,
            'locked_by' => auth()->id(),
            'locked_at' => now(),
            'lock_reason' => $request->reason,
        ]);

        AuditLogService::log(
            'document.locked',
            $document,
            ['locked' => false],
            ['locked' => true, 'locked_by' => auth()->user()->name],
            'warning',
            "Document locked: {$request->reason}"
        );

        DB::commit();

        return redirect()
            ->back()
            ->with('success', 'Document locked successfully. Staff cannot edit this document.');

    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()->with('error', 'Failed to lock document: ' . $e->getMessage());
    }
}

public function unlock(Scan $document)
{
    if (!$document->isLocked()) {
        return redirect()->back()->with('error', 'Document is not locked.');
    }

    DB::beginTransaction();
    try {
        $document->update([
            'locked' => false,
            // Keep locked_by, locked_at, lock_reason for history
        ]);

        AuditLogService::log(
            'document.unlocked',
            $document,
            ['locked' => true],
            ['locked' => false, 'unlocked_by' => auth()->user()->name],
            'info',
            'Document unlocked by admin'
        );

        DB::commit();

        return redirect()
            ->back()
            ->with('success', 'Document unlocked successfully.');

    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()->with('error', 'Failed to unlock document: ' . $e->getMessage());
    }
}

public function archive(Request $request, Scan $document)
{
    $request->validate([
        'reason' => 'required|string|min:20|max:500',
    ]);

    if ($document->isArchived()) {
        return redirect()->back()->with('error', 'Document is already archived.');
    }

    DB::beginTransaction();
    try {
        $document->update([
            'archived' => true,
            'archived_by' => auth()->id(),
            'archived_at' => now(),
            'archive_reason' => $request->reason,
        ]);

        AuditLogService::log(
            'document.archived',
            $document,
            ['archived' => false],
            ['archived' => true, 'archived_by' => auth()->user()->name],
            'info',
            "Document archived: {$request->reason}"
        );

        DB::commit();

        return redirect()
            ->back()
            ->with('success', 'Document archived successfully. It will be hidden from default staff views.');

    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()->with('error', 'Failed to archive document: ' . $e->getMessage());
    }
}

public function unarchive(Scan $document)
{
    if (!$document->isArchived()) {
        return redirect()->back()->with('error', 'Document is not archived.');
    }

    DB::beginTransaction();
    try {
        $document->update([
            'archived' => false,
            // Keep archived_by, archived_at, archive_reason for history
        ]);

        AuditLogService::log(
            'document.unarchived',
            $document,
            ['archived' => true],
            ['archived' => false, 'unarchived_by' => auth()->user()->name],
            'info',
            'Document unarchived by admin'
        );

        DB::commit();

        return redirect()
            ->back()
            ->with('success', 'Document unarchived successfully.');

    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()->with('error', 'Failed to unarchive document: ' . $e->getMessage());
    }
}

public function flag(Request $request, Scan $document)
{
    $request->validate([
        'notes' => 'required|string|min:10|max:500',
    ]);

    if ($document->isFlagged()) {
        return redirect()->back()->with('error', 'Document is already flagged.');
    }

    DB::beginTransaction();
    try {
        $document->update([
            'flagged' => true,
            'flagged_by' => auth()->id(),
            'flagged_at' => now(),
            'flag_notes' => $request->notes,
        ]);

        AuditLogService::log(
            'document.flagged',
            $document,
            ['flagged' => false],
            ['flagged' => true, 'flagged_by' => auth()->user()->name],
            'warning',
            "Document flagged: {$request->notes}"
        );

        DB::commit();

        return redirect()
            ->back()
            ->with('success', 'Document flagged for review.');

    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()->with('error', 'Failed to flag document: ' . $e->getMessage());
    }
}

public function unflag(Scan $document)
{
    if (!$document->isFlagged()) {
        return redirect()->back()->with('error', 'Document is not flagged.');
    }

    DB::beginTransaction();
    try {
        $document->update([
            'flagged' => false,
            // Keep flagged_by, flagged_at, flag_notes for history
        ]);

        AuditLogService::log(
            'document.unflagged',
            $document,
            ['flagged' => true],
            ['flagged' => false, 'unflagged_by' => auth()->user()->name],
            'info',
            'Document flag removed by admin'
        );

        DB::commit();

        return redirect()
            ->back()
            ->with('success', 'Document flag removed.');

    } catch (\Exception $e) {
        DB::rollBack();
        return redirect()->back()->with('error', 'Failed to unflag document: ' . $e->getMessage());
    }
}

    protected function applyBulkActionToDocument(Scan $document, string $action): bool
    {
        return match ($action) {
            'flag' => $this->bulkFlag($document),
            'lock' => $this->bulkLock($document),
            default => false,
        };
    }

    protected function bulkFlag(Scan $document): bool
    {
        if ($document->isFlagged()) {
            return false;
        }

        $document->update([
            'flagged' => true,
            'flagged_by' => auth()->id(),
            'flagged_at' => now(),
            'flag_notes' => 'Flagged via bulk action by admin',
        ]);

        AuditLogService::log(
            'document.bulk_flagged',
            $document,
            ['flagged' => false],
            ['flagged' => true, 'flagged_by' => auth()->user()->name],
            'warning',
            'Document flagged via bulk action'
        );

        return true;
    }

    protected function bulkLock(Scan $document): bool
    {
        if ($document->isLocked()) {
            return false;
        }

        $document->update([
            'locked' => true,
            'locked_by' => auth()->id(),
            'locked_at' => now(),
            'lock_reason' => 'Locked via bulk action by admin',
        ]);

        AuditLogService::log(
            'document.bulk_locked',
            $document,
            ['locked' => false],
            ['locked' => true, 'locked_by' => auth()->user()->name],
            'warning',
            'Document locked via bulk action'
        );

        return true;
    }


}