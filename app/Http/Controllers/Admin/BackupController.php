<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Backup;
use App\Services\BackupService;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BackupController extends Controller
{
    public function index()
    {
        BackupService::ensureBackupQueueProcessing();
        BackupService::reconcileBackupStatuses();

        $backups = Backup::with('initiator')
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return view('admin.backups.index', compact('backups'));
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'timeframe_mode' => ['required', 'in:today,this_week,this_month,specific_day,specific_month,custom_range'],
            'timeframe_start' => ['required', 'date'],
            'timeframe_end' => ['required', 'date', 'after_or_equal:timeframe_start'],
            'timeframe_label' => ['required', 'string', 'max:190'],
        ]);

        $timeframe = [
            'mode' => $validated['timeframe_mode'],
            'start' => $validated['timeframe_start'],
            'end' => $validated['timeframe_end'],
            'label' => $validated['timeframe_label'],
        ];

        BackupService::createManualBackup(auth()->user(), $timeframe);

        return redirect()
            ->route('admin.backups.index')
            ->with('success', 'Backup initiated for ' . $timeframe['label'] . '. You will be notified when complete. Check back in a few minutes.');
    }

    public function download(Backup $backup)
    {
        if ($backup->status !== 'completed') {
            return redirect()->back()->with('error', 'Backup is not completed yet.');
        }

        if (!Storage::exists($backup->file_path)) {
            return redirect()->back()->with('error', 'Backup file not found.');
        }

        // Log download
        AuditLogService::log(
            'backup.downloaded',
            $backup,
            null,
            null,
            'info',
            'Backup file downloaded by admin'
        );

        return Storage::download($backup->file_path);
    }

    public function verify(Backup $backup)
    {
        if ($backup->status !== 'completed') {
            return response()->json([
                'valid' => false,
                'error' => 'Backup is not completed.',
            ]);
        }

        if (!Storage::exists($backup->file_path)) {
            return response()->json([
                'valid' => false,
                'error' => 'Backup file not found.',
            ]);
        }

        $filePath = Storage::path($backup->file_path);
        $calculatedChecksum = hash_file('sha256', $filePath);
        $valid = $calculatedChecksum === $backup->checksum;

        // Log verification
        AuditLogService::log(
            'backup.verified',
            $backup,
            null,
            ['valid' => $valid, 'calculated_checksum' => $calculatedChecksum],
            $valid ? 'info' : 'warning',
            $valid ? 'Backup integrity verified successfully' : 'Backup integrity verification FAILED'
        );

        return response()->json([
            'valid' => $valid,
            'stored_checksum' => $backup->checksum,
            'calculated_checksum' => $calculatedChecksum,
        ]);
    }
}