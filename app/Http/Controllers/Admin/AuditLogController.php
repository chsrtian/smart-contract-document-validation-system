<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user');

        // Filter by user
        if ($request->filled('user_id')) {
            $query->byUser($request->user_id);
        }

        // Filter by action type
        if ($request->filled('action_type')) {
            $query->byAction($request->action_type);
        }

        // Filter by severity
        if ($request->filled('severity')) {
            $query->bySeverity($request->severity);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('timestamp', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('timestamp', '<=', $request->date_to . ' 23:59:59');
        }

        // Search in notes
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $auditLogs = $query->orderBy('timestamp', 'desc')->paginate(50);

        // Get distinct action types for filter dropdown
        $actionTypes = AuditLog::select('action_type')
            ->distinct()
            ->orderBy('action_type')
            ->pluck('action_type');

        // Get users who have audit logs
        $users = User::whereIn('id', function($query) {
            $query->select('user_id')
                ->from('audit_logs')
                ->whereNotNull('user_id')
                ->distinct();
        })->get();

        return view('admin.audit-logs.index', compact('auditLogs', 'actionTypes', 'users'));
    }

        public function exportCsv(Request $request)
    {
        $query = \App\Models\AuditLog::with('user');

        // Apply same filters as index
        $this->applyFilters($query, $request);

        // Limit to prevent timeout
        $auditLogs = $query->orderBy('timestamp', 'desc')->limit(50000)->get();

        $filename = 'audit_logs_' . now()->format('Y-m-d_H-i-s') . '.csv';

        // Log export
        AuditLogService::log(
            'audit_log.export',
            null,
            null,
            ['format' => 'CSV', 'row_count' => $auditLogs->count()],
            'info',
            "Audit logs exported to CSV ({$auditLogs->count()} rows)"
        );

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($auditLogs) {
            $file = fopen('php://output', 'w');
            
            // UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Headers
            fputcsv($file, [
                'Timestamp',
                'User',
                'Action Type',
                'Target Entity Type',
                'Target Entity ID',
                'Previous Value',
                'New Value',
                'IP Address',
                'Severity',
                'Notes'
            ]);

            // Data
            foreach ($auditLogs as $log) {
                fputcsv($file, [
                    $log->timestamp->format('Y-m-d H:i:s'),
                    $log->user_name,
                    $log->action_type,
                    $log->target_entity_type ? class_basename($log->target_entity_type) : '',
                    $log->target_entity_id ?? '',
                    $log->previous_value ? json_encode($log->previous_value) : '',
                    $log->new_value ? json_encode($log->new_value) : '',
                    $log->ip_address ?? '',
                    $log->severity,
                    $log->notes ?? ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportPdf(Request $request)
    {
        $query = \App\Models\AuditLog::with('user');

        // Apply same filters as index
        $this->applyFilters($query, $request);

        // Limit for PDF (smaller than CSV)
        $auditLogs = $query->orderBy('timestamp', 'desc')->limit(1000)->get();

        // Log export
        AuditLogService::log(
            'audit_log.export',
            null,
            null,
            ['format' => 'PDF', 'row_count' => $auditLogs->count()],
            'info',
            "Audit logs exported to PDF ({$auditLogs->count()} rows)"
        );

        $pdf = Pdf::loadView('admin.audit-logs.pdf', [
            'auditLogs' => $auditLogs,
            'filters' => $request->all(),
            'exportDate' => now(),
        ]);

        $filename = 'audit_logs_' . now()->format('Y-m-d_H-i-s') . '.pdf';

        return $pdf->download($filename);
    }

    // Helper method to apply filters
    private function applyFilters($query, $request)
    {
        if ($request->filled('user_id')) {
            $query->byUser($request->user_id);
        }

        if ($request->filled('action_type')) {
            $query->byAction($request->action_type);
        }

        if ($request->filled('severity')) {
            $query->bySeverity($request->severity);
        }

        if ($request->filled('date_from')) {
            $query->where('timestamp', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('timestamp', '<=', $request->date_to . ' 23:59:59');
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }
    }


}