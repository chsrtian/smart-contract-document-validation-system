<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Scan;
use App\Jobs\AnchorToBlockchainJob;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class BlockchainMonitoringController extends Controller
{
    public function index(Request $request)
    {
        $query = Scan::query()->whereNotNull('blockchain_status');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('blockchain_status', $request->status);
        }

        if ($request->filled('document_type')) {
            $query->where('document_type', $request->document_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $documents = $query->with('user')->latest()->paginate(20);

        $pending = Scan::where('blockchain_status', 'pending')->count();
        $confirmed = Scan::where('blockchain_status', 'confirmed')->count();
        $failed = Scan::where('blockchain_status', 'failed')->count();
        $totalAnchored = $confirmed;
        $totalSubmitted = $pending + $confirmed + $failed;
        $pendingRatio = $totalSubmitted > 0 ? round(($pending / $totalSubmitted) * 100, 1) : 0;

        $stats = [
            'pending' => $pending,
            'confirmed' => $confirmed,
            'failed' => $failed,
            'total_anchored' => $totalAnchored,
            'total_submitted' => $totalSubmitted,
            'pending_ratio' => $pendingRatio,
            'success_rate' => $totalSubmitted > 0 ? round(($confirmed / $totalSubmitted) * 100, 1) : 0,
            'avg_confirmation_time' => $this->getAvgConfirmationTime(),
        ];

        $lastUpdatedAt = now();

        return view('admin.blockchain.index', compact('documents', 'stats', 'lastUpdatedAt'));
    }

    public function pending()
    {
        $documents = Scan::where('blockchain_status', 'pending')
            ->with('user')
            ->latest()
            ->paginate(20);

        return view('admin.blockchain.pending', compact('documents'));
    }

    public function confirmed()
    {
        $documents = Scan::where('blockchain_status', 'confirmed')
            ->with('user')
            ->latest()
            ->paginate(20);

        return view('admin.blockchain.confirmed', compact('documents'));
    }

    public function failed()
    {
        $documents = Scan::where('blockchain_status', 'failed')
            ->with('user')
            ->latest()
            ->paginate(20);

        return view('admin.blockchain.failed', compact('documents'));
    }

    public function retry(Scan $document)
    {
        if ($document->blockchain_status !== 'failed') {
            return back()->with('error', 'Only failed transactions can be retried.');
        }

        // Update status to pending
        $document->update(['blockchain_status' => 'pending']);

        // Dispatch blockchain job
        AnchorToBlockchainJob::dispatch($document);

        // Log audit entry
        AuditLogService::log(
            'blockchain.retry',
            $document,
            ['blockchain_status' => 'failed'],
            ['blockchain_status' => 'pending'],
            'info',
            "Admin manually retried failed blockchain transaction for document {$document->id}"
        );

        return back()->with('success', 'Blockchain transaction retry initiated.');
    }

    public function bulkRetry(Request $request)
    {
        $request->validate([
            'document_ids' => 'required|array|min:1',
            'document_ids.*' => 'exists:scans,id',
        ]);

        $documents = Scan::whereIn('id', $request->document_ids)
            ->where('blockchain_status', 'failed')
            ->get();

        if ($documents->isEmpty()) {
            return back()->with('error', 'No failed transactions found to retry.');
        }

        $retryCount = 0;

        foreach ($documents as $document) {
            $document->update(['blockchain_status' => 'pending']);
            AnchorToBlockchainJob::dispatch($document);
            $retryCount++;
        }

        // Log bulk retry
        AuditLogService::log(
            'blockchain.bulk_retry',
            null,
            ['count' => 0],
            ['count' => $retryCount, 'document_ids' => $request->document_ids],
            'info',
            "Admin initiated bulk retry for {$retryCount} failed blockchain transaction(s)"
        );

        return back()->with('success', "Successfully queued {$retryCount} transaction(s) for retry.");
    }

    public function retryAllFailed(Request $request)
    {
        $failedDocuments = Scan::where('blockchain_status', 'failed')->get();

        if ($failedDocuments->isEmpty()) {
            return back()->with('info', 'No failed transactions to retry.');
        }

        $retryCount = 0;

        foreach ($failedDocuments as $document) {
            $document->update(['blockchain_status' => 'pending']);
            AnchorToBlockchainJob::dispatch($document);
            $retryCount++;
        }

        // Log bulk retry all
        AuditLogService::log(
            'blockchain.retry_all_failed',
            null,
            ['count' => 0],
            ['count' => $retryCount],
            'warning',
            "Admin initiated retry for ALL {$retryCount} failed blockchain transactions"
        );

        return back()->with('success', "Successfully queued all {$retryCount} failed transaction(s) for retry.");
    }

    public function transactionDetails(Scan $document)
    {
        return view('admin.blockchain.transaction-details', compact('document'));
    }

    /**
     * Get average confirmation time in minutes for confirmed blockchain transactions.
     */
    private function getAvgConfirmationTime(): string
    {
        $avg = Scan::where('blockchain_status', 'confirmed')
            ->whereNotNull('blockchain_confirmed_at')
            ->whereNotNull('blockchain_submitted_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, blockchain_submitted_at, blockchain_confirmed_at)) as avg_time')
            ->value('avg_time');

        return $avg ? round($avg, 1) : 'N/A';
    }
}