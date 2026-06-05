<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReportsController extends Controller
{
    public function index()
    {
        // Get all generated reports
        $reports = collect(Storage::files('reports'))
            ->filter(fn($file) => str_ends_with($file, '.txt') || str_ends_with($file, '.json'))
            ->map(function ($file) {
                return [
                    'filename' => basename($file),
                    'path' => $file,
                    'size' => Storage::size($file),
                    'modified' => Storage::lastModified($file),
                ];
            })
            ->sortByDesc('modified')
            ->values();

        return view('admin.reports.index', compact('reports'));
    }

    public function generate(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2030',
        ]);

        $reportData = ReportService::generateMonthlyReport($request->month, $request->year);
        $pdf = Pdf::loadView('admin.reports.pdf', [
            'reportData' => $reportData,
            'generatedAt' => now(),
        ]);

        $filename = sprintf(
            'monthly_report_%d_%02d.pdf',
            (int) $request->year,
            (int) $request->month
        );

        return $pdf->download($filename);
    }

    public function download($filename)
    {
        $filePath = "reports/{$filename}";

        if (!Storage::exists($filePath)) {
            abort(404, 'Report not found.');
        }

        return Storage::download($filePath);
    }
}