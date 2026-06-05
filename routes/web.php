<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Staff\StaffDashboardController;
use App\Http\Controllers\Staff\StaffDocumentController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Staff\CategoryDashboardController;
use App\Http\Controllers\CorrectionRequestController;
use App\Http\Controllers\CorrectionApprovalController;
use App\Http\Controllers\Staff\StaffAnalyticsController;
use App\Http\Controllers\Staff\StaffReportsController;
use App\Http\Controllers\Staff\LegalCorrectionPetitionController;
use App\Http\Controllers\Admin\AdminLegalCorrectionController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    if (Auth::check()) {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }
        
        if ($user->hasRole('supervisor')) {
            return redirect()->route('corrections.approval.dashboard');
        }
        
        return redirect()->route('staff.dashboard');
    }
    return redirect()->route('login');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    
    // User Management Routes
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\UserManagementController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Admin\UserManagementController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Admin\UserManagementController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [App\Http\Controllers\Admin\UserManagementController::class, 'edit'])->name('edit');
        Route::put('/{user}', [App\Http\Controllers\Admin\UserManagementController::class, 'update'])->name('update');
        Route::post('/{user}/assign-role', [App\Http\Controllers\Admin\UserManagementController::class, 'assignRole'])->name('assign-role');
        Route::post('/{user}/deactivate', [App\Http\Controllers\Admin\UserManagementController::class, 'deactivate'])->name('deactivate');
        Route::post('/{user}/reactivate', [App\Http\Controllers\Admin\UserManagementController::class, 'reactivate'])->name('reactivate');
    });

    // Audit Logs Routes
    Route::prefix('audit-logs')->name('audit-logs.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('index');
        Route::get('/export/csv', [App\Http\Controllers\Admin\AuditLogController::class, 'exportCsv'])->name('export-csv');
        Route::get('/export/pdf', [App\Http\Controllers\Admin\AuditLogController::class, 'exportPdf'])->name('export-pdf');
    });

    // Document Oversight Routes
    Route::prefix('documents')->name('documents.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\DocumentOversightController::class, 'index'])->name('index');
        Route::post('/bulk-action', [App\Http\Controllers\Admin\DocumentOversightController::class, 'bulkAction'])->name('bulk-action');
        Route::get('/{document}', [App\Http\Controllers\Admin\DocumentOversightController::class, 'show'])->name('show');
        
        Route::post('/{document}/lock', [App\Http\Controllers\Admin\DocumentOversightController::class, 'lock'])->name('lock');
        Route::post('/{document}/unlock', [App\Http\Controllers\Admin\DocumentOversightController::class, 'unlock'])->name('unlock');
        Route::post('/{document}/archive', [App\Http\Controllers\Admin\DocumentOversightController::class, 'archive'])->name('archive');
        Route::post('/{document}/unarchive', [App\Http\Controllers\Admin\DocumentOversightController::class, 'unarchive'])->name('unarchive');
        Route::post('/{document}/flag', [App\Http\Controllers\Admin\DocumentOversightController::class, 'flag'])->name('flag');
        Route::post('/{document}/unflag', [App\Http\Controllers\Admin\DocumentOversightController::class, 'unflag'])->name('unflag');
    });

    // Correction Oversight Routes
    Route::prefix('corrections')->name('corrections.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\CorrectionOversightController::class, 'index'])->name('index');

        // Static/prefix routes MUST come before parameterized routes to avoid route capture conflict.
        // e.g. GET /admin/corrections/legal would be captured by /{correctionRequest} if defined first.
        Route::get('/overrides/history', [App\Http\Controllers\Admin\CorrectionOversightController::class, 'overrideHistory'])->name('overrides.history');

        // Legal Correction Petition Routes (Admin)
        Route::prefix('legal')->name('legal.')->group(function () {
            Route::get('/', [AdminLegalCorrectionController::class, 'index'])->name('index');
            Route::get('/{petition}', [AdminLegalCorrectionController::class, 'show'])->name('show');
            Route::post('/{petition}/approve', [AdminLegalCorrectionController::class, 'approve'])->name('approve');
            Route::post('/{petition}/reject', [AdminLegalCorrectionController::class, 'reject'])->name('reject');
            Route::post('/{petition}/forward-psa', [AdminLegalCorrectionController::class, 'forwardToPsa'])->name('forward-psa');
        });

        // Parameterized routes AFTER static ones; constrained to numeric IDs only
        Route::get('/{correctionRequest}', [App\Http\Controllers\Admin\CorrectionOversightController::class, 'show'])->name('show')->where('correctionRequest', '[0-9]+');
        Route::post('/{correctionRequest}/approve', [App\Http\Controllers\Admin\CorrectionOversightController::class, 'approve'])->name('approve')->where('correctionRequest', '[0-9]+');
        Route::post('/{correctionRequest}/reject', [App\Http\Controllers\Admin\CorrectionOversightController::class, 'reject'])->name('reject')->where('correctionRequest', '[0-9]+');
        Route::post('/{correctionRequest}/force-approve', [App\Http\Controllers\Admin\CorrectionOversightController::class, 'forceApprove'])->name('force-approve')->where('correctionRequest', '[0-9]+');
        Route::post('/{correctionRequest}/force-reject', [App\Http\Controllers\Admin\CorrectionOversightController::class, 'forceReject'])->name('force-reject')->where('correctionRequest', '[0-9]+');
    });

    // Escalation Routes
    Route::prefix('escalations')->name('escalations.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\EscalationController::class, 'index'])->name('index');
        Route::get('/resolved', [App\Http\Controllers\Admin\EscalationController::class, 'resolved'])->name('resolved');
        Route::post('/{correctionRequest}/assign', [App\Http\Controllers\Admin\EscalationController::class, 'assign'])->name('assign');
    });

    // Blockchain Monitoring Routes
    Route::prefix('blockchain')->name('blockchain.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\BlockchainMonitoringController::class, 'index'])->name('index');
        Route::get('/pending', [App\Http\Controllers\Admin\BlockchainMonitoringController::class, 'pending'])->name('pending');
        Route::get('/confirmed', [App\Http\Controllers\Admin\BlockchainMonitoringController::class, 'confirmed'])->name('confirmed');
        Route::get('/failed', [App\Http\Controllers\Admin\BlockchainMonitoringController::class, 'failed'])->name('failed');
        Route::post('/{document}/retry', [App\Http\Controllers\Admin\BlockchainMonitoringController::class, 'retry'])->name('retry');
        Route::get('/transaction/{document}', [App\Http\Controllers\Admin\BlockchainMonitoringController::class, 'transactionDetails'])->name('transaction-details');
        
        // Bulk retry routes
        Route::post('/bulk-retry', [App\Http\Controllers\Admin\BlockchainMonitoringController::class, 'bulkRetry'])->name('bulk-retry');
        Route::post('/retry-all-failed', [App\Http\Controllers\Admin\BlockchainMonitoringController::class, 'retryAllFailed'])->name('retry-all-failed');
    });

    // Backup Routes
    Route::prefix('backups')->name('backups.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\BackupController::class, 'index'])->name('index');
        Route::post('/create', [App\Http\Controllers\Admin\BackupController::class, 'create'])->name('create');
        Route::get('/{backup}/download', [App\Http\Controllers\Admin\BackupController::class, 'download'])->name('download');
        Route::post('/{backup}/verify', [App\Http\Controllers\Admin\BackupController::class, 'verify'])->name('verify');
    });

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\ReportsController::class, 'index'])->name('index');
        Route::post('/generate', [App\Http\Controllers\Admin\ReportsController::class, 'generate'])->name('generate');
        Route::get('/download/{filename}', [App\Http\Controllers\Admin\ReportsController::class, 'download'])->name('download');
    });

    // Notifications Routes 
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\NotificationsController::class, 'index'])->name('index');
        Route::get('/{notification}', [App\Http\Controllers\Admin\NotificationsController::class, 'show'])->name('show');
        Route::post('/{notification}/read', [App\Http\Controllers\Admin\NotificationsController::class, 'markAsRead'])->name('mark-read');
        Route::post('/mark-all-read', [App\Http\Controllers\Admin\NotificationsController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::delete('/{notification}', [App\Http\Controllers\Admin\NotificationsController::class, 'destroy'])->name('destroy');
        Route::get('/api/unread-count', [App\Http\Controllers\Admin\NotificationsController::class, 'getUnreadCount'])->name('unread-count');
    });
    
    // System Health Routes 
    Route::prefix('system-health')->name('system-health.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\SystemHealthController::class, 'index'])->name('index');
        Route::get('/refresh', [App\Http\Controllers\Admin\SystemHealthController::class, 'refresh'])->name('refresh');
    });

    // Analytics Routes 
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\AnalyticsController::class, 'index'])->name('index');
        Route::get('/export', [App\Http\Controllers\Admin\AnalyticsController::class, 'export'])->name('export');
    });
});


Route::middleware(['auth', 'verified', 'role:staff|admin', \App\Http\Middleware\PreventBackHistory::class])->group(function () {
    Route::get('/staff/dashboard', [StaffDashboardController::class, 'index'])->name('staff.dashboard');
   
    Route::get('/history', [StaffDocumentController::class, 'history'])->name('staff.history');
});

//STAFF ROUTES
Route::middleware(['auth', 'role:staff|admin'])->prefix('staff')->name('staff.')->group(function () {
    Route::prefix('categories')->name('categories.')->group(function () {
        // Birth Certificate Dashboard
        Route::get('/birth', [CategoryDashboardController::class, 'birth'])->name('birth');
        
        // Death Certificate Dashboard
        Route::get('/death', [CategoryDashboardController::class, 'death'])->name('death');
        
        // Marriage Certificate Dashboard
        Route::get('/marriage', [CategoryDashboardController::class, 'marriage'])->name('marriage');
        
        // CENOMAR / Advisory on Marriages Dashboard
        Route::get('/others', [CategoryDashboardController::class, 'others'])->name('others');
        
        // API endpoint for chart data (AJAX calls from sub-pages)
        Route::get('/{category}/chart-data', [CategoryDashboardController::class, 'getChartData'])
            ->name('chart-data')
            ->where('category', 'birth|death|marriage|cenomar');
        
        // API endpoint for filtered documents table (AJAX calls from sub-pages)
        Route::get('/{category}/documents', [CategoryDashboardController::class, 'getDocuments'])
            ->name('documents')
            ->where('category', 'birth|death|marriage|cenomar');
    });

    // SEARCH ROUTES - Use SearchController for all search functionality
    Route::get('/search', [SearchController::class, 'index'])->name('search');
    Route::get('/search/form/{documentType}', [SearchController::class, 'loadSearchForm'])->name('search.form');
    Route::get('/search/live', [SearchController::class, 'liveSearch'])->name('search.live');
    Route::post('/search', [SearchController::class, 'search'])->name('search.perform');
    Route::get('/search/document/{scanId}', [SearchController::class, 'getDocument'])->name('search.document');
    Route::post('/search/print/{scanId}', [SearchController::class, 'printDocument'])->name('search.print');
    
    Route::get('/scans/{scan}', [ScanController::class, 'show'])->name('scans.show');
    
    // SCANNER ROUTES
    Route::get('/scan', [ScanController::class, 'index'])->name('scan');
    Route::get('/scan/detect-scanners', [ScanController::class, 'detectScanners'])->name('scan.detect-scanners');
    Route::post('/scan/preview', [ScanController::class, 'preview'])->name('scan.preview');
    Route::post('/scan/start', [ScanController::class, 'scan'])->name('scan.start');
    Route::post('/scan/ocr-process', [ScanController::class, 'processOCR'])->name('scan.ocr-process');
    Route::post('/scan/save-document', [ScanController::class, 'saveProcessedDocument'])->name('scan.save-document');
    
    Route::get('/scans/{scan}/blockchain-details', [ScanController::class, 'getBlockchainDetails'])
    ->name('scans.blockchain-details');
    
    // UPLOAD SECTION ROUTES
    Route::get('/upload', [UploadController::class, 'index'])->name('upload');
    Route::get('/upload/metrics', [ScanController::class, 'getMetrics'])->name('upload.metrics');
    Route::get('/upload/documents', [ScanController::class, 'getDocuments'])->name('upload.documents');    
    
    // Enhanced blockchain integration routes 
    Route::post('/scans/{scan}/update-field', [ScanController::class, 'updateField'])->name('scans.updateField');
    Route::post('/scans/{scan}/trigger-blockchain', [ScanController::class, 'triggerBlockchainAnchoring'])->name('scans.triggerBlockchain');
    Route::post('/scans/{scan}/recalculate-scores', [ScanController::class, 'recalculateScores'])
    ->name('scans.recalculateScores');

    Route::post('/scans/{scan}/update-verification-status', [ScanController::class, 'updateVerificationStatus'])->name('scans.updateVerificationStatus');
        
    // Document operations
    Route::prefix('documents')->name('documents.')->group(function () {
        Route::get('/{id}', [DocumentController::class, 'show'])->name('show')
            ->where('id', '[0-9]+')->middleware('throttle:60,1');
        Route::get('/{id}/view', [DocumentController::class, 'view'])->name('view')
            ->where('id', '[0-9]+')->middleware('throttle:60,1');
        Route::get('/{id}/download', [DocumentController::class, 'download'])->name('download')
            ->where('id', '[0-9]+')->middleware('throttle:60,1');
        Route::delete('/{id}', [DocumentController::class, 'destroy'])->name('destroy')
            ->where('id', '[0-9]+')->middleware('throttle:10,1');
    });

    // Scan image preview and download routes
    Route::get('/scans/{scan}/image/preview', function (\Illuminate\Http\Request $request, \App\Models\Scan $scan) {
        // Verify file exists
        $filePath = $scan->full_file_path;
        
        if (!$filePath) {
            abort(404, 'File not found');
        }
        
        // Get MIME type
        $mimeType = mime_content_type($filePath);

        // Raw mode keeps the original file bytes (used by PDF.js page rendering).
        if ($request->boolean('raw') || $request->boolean('raw_pdf')) {
            return response()->file($filePath, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . basename($filePath) . '"'
            ]);
        }

        // For archival PDFs generated from scans, try to extract and serve the embedded image.
        if ($mimeType === 'application/pdf') {
            $previewExtensions = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'webp' => 'image/webp',
                'gif' => 'image/gif',
            ];

            $previewDir = pathinfo($filePath, PATHINFO_DIRNAME);
            $previewBaseName = pathinfo($filePath, PATHINFO_FILENAME);

            foreach ($previewExtensions as $ext => $previewMime) {
                $candidatePath = $previewDir . DIRECTORY_SEPARATOR . $previewBaseName . '.' . $ext;
                if (file_exists($candidatePath)) {
                    return response()->file($candidatePath, [
                        'Content-Type' => $previewMime,
                        'Content-Disposition' => 'inline; filename="' . basename($candidatePath) . '"'
                    ]);
                }
            }

            $previewRoot = storage_path('app/temp/pdf_previews');
            if (!is_dir($previewRoot)) {
                @mkdir($previewRoot, 0755, true);
            }

            $cacheKey = md5($filePath . '|' . @filemtime($filePath));
            $previewBase = $previewRoot . DIRECTORY_SEPARATOR . 'scan_' . $scan->id . '_' . $cacheKey;

            $imageCandidates = [
                [
                    'ext' => 'jpg',
                    'mime' => 'image/jpeg',
                    'regex' => '/\xFF\xD8\xFF[\s\S]*?\xFF\xD9/',
                ],
                [
                    'ext' => 'png',
                    'mime' => 'image/png',
                    'regex' => '/\x89PNG\x0D\x0A\x1A\x0A[\s\S]*?IEND\xAE\x42\x60\x82/',
                ],
            ];

            foreach ($imageCandidates as $candidate) {
                $candidatePath = $previewBase . '.' . $candidate['ext'];
                if (file_exists($candidatePath)) {
                    return response()->file($candidatePath, [
                        'Content-Type' => $candidate['mime'],
                        'Content-Disposition' => 'inline; filename="' . basename($candidatePath) . '"'
                    ]);
                }
            }

            $pdfBytes = @file_get_contents($filePath);
            if ($pdfBytes !== false) {
                foreach ($imageCandidates as $candidate) {
                    if (preg_match($candidate['regex'], $pdfBytes, $matches) === 1) {
                        $candidatePath = $previewBase . '.' . $candidate['ext'];
                        @file_put_contents($candidatePath, $matches[0]);
                        break;
                    }
                }
            }

            foreach ($imageCandidates as $candidate) {
                $candidatePath = $previewBase . '.' . $candidate['ext'];
                if (file_exists($candidatePath)) {
                    return response()->file($candidatePath, [
                        'Content-Type' => $candidate['mime'],
                        'Content-Disposition' => 'inline; filename="' . basename($candidatePath) . '"'
                    ]);
                }
            }
        }
        
        // Serve file inline (display in browser)
        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . basename($filePath) . '"'
        ]);
    })->name('scans.image.preview');
    
    Route::get('/scans/{scan}/image/download', function (\App\Models\Scan $scan) {
        // Verify file exists
        $filePath = $scan->full_file_path;
        
        if (!$filePath) {
            abort(404, 'File not found');
        }
        
        // Generate friendly filename
        $filename = sprintf(
            '%s_%s.%s',
            $scan->document_type,
            $scan->document_id,
            pathinfo($filePath, PATHINFO_EXTENSION)
        );
        
        // Force download
        return response()->download($filePath, $filename);
    })->name('scans.image.download');

    // Blockchain integration endpoints
    Route::post('/scan/calculate-hash', [ScanController::class, 'calculateHash'])->name('scan.calculate-hash');
    Route::post('/scan/submit-blockchain', [ScanController::class, 'submitToBlockchain'])->name('scan.submit-blockchain');
    Route::get('/scan/verify-blockchain/{scan}', [ScanController::class, 'verifyBlockchain'])->name('scan.verify-blockchain');
    
    // API endpoints - Keep only the ones that don't conflict with SearchController
    Route::post('/upload-handle', [StaffController::class, 'handleUpload'])->name('upload.handle');
    Route::post('/validate-session', [StaffController::class, 'validateSession'])->name('validate-session');
    
    Route::post('/scan/debug-names', [App\Http\Controllers\ScanController::class, 'debugNameExtraction'])
    ->middleware(['auth'])
    ->name('scan.debug-names');
    
    // Staff Analytics Routes
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', [StaffAnalyticsController::class, 'index'])->name('index');
        Route::get('/chart-data', [StaffAnalyticsController::class, 'chartData'])->name('chart-data');
    });

    // Staff Analytics and Reports routes
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [StaffReportsController::class, 'index'])->name('index');
        Route::get('/pdf', [StaffReportsController::class, 'pdf'])->name('pdf');
        Route::get('/print', [StaffReportsController::class, 'print'])->name('print');
    });
});


// Staff Correction Request Routes
Route::middleware(['auth', 'role:staff|admin'])->prefix('corrections')->name('corrections.')->group(function () {
    
    // Correction Requests (Staff)
    Route::prefix('requests')->name('requests.')->group(function () {
        Route::get('/', [CorrectionRequestController::class, 'index'])->name('index');
        Route::get('/eligible-documents', [CorrectionRequestController::class, 'eligibleDocuments'])->name('eligible-documents');
        Route::get('/create', [CorrectionRequestController::class, 'create'])->name('create');
        // AJAX endpoint must be BEFORE parameterized /{correctionRequest} route
        Route::get('/field-details', [CorrectionRequestController::class, 'getFieldDetails'])->name('field-details');
        Route::post('/', [CorrectionRequestController::class, 'store'])->name('store');
        Route::get('/{correctionRequest}', [CorrectionRequestController::class, 'show'])->name('show');
        Route::patch('/{correctionRequest}/cancel', [CorrectionRequestController::class, 'cancel'])->name('cancel');

        Route::post('/staff/scans/{scan}/log-release', [SearchController::class, 'logDocumentRelease'])
        ->name('staff.scans.log-release');
});

    // Legal Correction Petitions (Staff)
    Route::prefix('petitions')->name('petitions.')->group(function () {
        Route::get('/', [LegalCorrectionPetitionController::class, 'index'])->name('index');
        Route::get('/create', [LegalCorrectionPetitionController::class, 'create'])->name('create');
        // Static/API routes before parameterized routes to avoid route capture conflict
        Route::get('/api/document-fields', [LegalCorrectionPetitionController::class, 'getDocumentFields'])->name('document-fields');
        Route::post('/', [LegalCorrectionPetitionController::class, 'store'])->name('store');
        Route::get('/{petition}', [LegalCorrectionPetitionController::class, 'show'])->name('show');
        Route::get('/{petition}/print', [LegalCorrectionPetitionController::class, 'printView'])->name('print');
        Route::post('/{petition}/submit', [LegalCorrectionPetitionController::class, 'submit'])->name('submit');
    });
    
});

// Supervisor Correction Approval Routes
Route::middleware(['auth', 'role:supervisor|admin'])->prefix('corrections/approval')->name('corrections.approval.')->group(function () {
    
    Route::get('/dashboard', [CorrectionApprovalController::class, 'dashboard'])->name('dashboard');
    Route::get('/pending', [CorrectionApprovalController::class, 'pending'])->name('pending');
    Route::get('/review/{correctionRequest}', [CorrectionApprovalController::class, 'review'])->name('review');
    Route::post('/approve/{correctionRequest}', [CorrectionApprovalController::class, 'approve'])->name('approve');
    Route::post('/reject/{correctionRequest}', [CorrectionApprovalController::class, 'reject'])->name('reject');
    Route::get('/history', [CorrectionApprovalController::class, 'history'])->name('history');
    Route::get('/audit-log', [CorrectionApprovalController::class, 'auditLog'])->name('audit-log');
    
    Route::get('/show/{correctionRequest}', [CorrectionApprovalController::class, 'show'])->name('show');
    
    Route::get('/correction/{correctionRecord}', [CorrectionApprovalController::class, 'viewCorrection'])->name('view-correction');
    Route::get('/document/{scan}/with-corrections', [CorrectionApprovalController::class, 'viewDocumentWithCorrections'])->name('document-with-corrections');
    Route::post('/correction/{correctionRecord}/retry', [CorrectionApprovalController::class, 'retryBlockchainAnchoring'])->name('retry-anchoring');
    
    // API endpoint
    Route::get('/statistics', [CorrectionApprovalController::class, 'statistics'])->name('statistics');
});

// Shared route for viewing document with corrections (accessible by staff and supervisor)
Route::middleware(['auth', 'role:staff|supervisor|admin'])->group(function () {
    Route::get('/documents/{scan}/correction-history', [CorrectionApprovalController::class, 'viewDocumentWithCorrections'])
        ->name('documents.correction-history');
});

require __DIR__.'/auth.php';