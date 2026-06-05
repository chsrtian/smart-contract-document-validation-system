<?php
use App\Http\Controllers\ScanController;
use Illuminate\Support\Facades\Route;

Route::get('/scan/stats', [ScanController::class, 'stats'])->name('api.scan.stats'); // AJAX polling endpoint for stats
Route::get('/scan/status/{id}', [ScanController::class, 'status'])->name('api.scan.status'); // AJAX polling endpoint for job status

// Tip: For local testing, set QUEUE_CONNECTION=sync in .env to process jobs immediately without a queue worker.