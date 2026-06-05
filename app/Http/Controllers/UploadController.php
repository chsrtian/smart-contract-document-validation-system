<?php


namespace App\Http\Controllers;

use App\Models\Scan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    /**
     * Display the upload dashboard
     */
    public function index()
    {
        return view('staff.upload');
    }

    /**
     * Get dashboard metrics (API endpoint)
     */
    public function getDashboardMetrics()
    {
        try {
            $metrics = [
                'today_processed' => Scan::whereDate('created_at', today())->count(),
                'total_documents' => Scan::count(),
                'blockchain_confirmed' => Scan::where('blockchain_status', 'confirmed')->count(),
                'pending_verification' => Scan::where('verification_status', 'pending')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $metrics
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch metrics'
            ], 500);
        }
    }

    /**
     * Get processed documents list (API endpoint)
     */
    public function getDocumentsList(Request $request)
{
    try {
        $query = Scan::select([
            'id',
            'document_id',
            'document_type', 
            'title',
            'created_at',
            'updated_at',
            'verification_status',
            'blockchain_status',
            'processed_by',
            'file_path',
            'ocr_data',
            'extracted_fields',
            'ocr_confidence'
        ]);

        // Filter by document type (handle underscore format)
        if ($request->has('document_type') && $request->document_type != '') {
            $documentType = strtolower(str_replace(' ', '_', $request->document_type));
            $query->where('document_type', $documentType);
        }

        // Filter by verification status  
        if ($request->has('status') && $request->status != '') {
            $query->where('verification_status', $request->status);
        }

        // Filter by blockchain status
        if ($request->has('blockchain_status') && $request->blockchain_status != '') {
            $query->where('blockchain_status', $request->blockchain_status);
        }

        // Filter by date range
        if ($request->has('date_from') && $request->date_from != '') {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->has('date_to') && $request->date_to != '') {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search by text content, title, or ID
        if ($request->has('search') && $request->search != '') {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('id', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('document_id', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('title', 'LIKE', "%{$searchTerm}%")
                  ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(ocr_data, '$.raw_text')) LIKE ?", ["%{$searchTerm}%"]);
            });
        }

        $documents = $query->orderBy('created_at', 'desc')
                          ->paginate(15);

        // Add additional data for display
        $documents->getCollection()->transform(function ($document) {
            // Extract preview text from JSON ocr_data
            $preview_text = 'No text extracted';
            if ($document->ocr_data) {
                $ocrData = json_decode($document->ocr_data, true);
                if (isset($ocrData['raw_text'])) {
                    $preview_text = Str::limit(strip_tags($ocrData['raw_text']), 100);
                }
            }
            
            $document->preview_text = $preview_text;
            $document->formatted_date = $document->created_at->format('M d, Y H:i');
            $document->file_exists = $document->file_path && file_exists(storage_path('app/' . $document->file_path));
            
            // Format document type for display
            $document->display_type = ucwords(str_replace('_', ' ', $document->document_type));
            
            return $document;
        });

        return response()->json([
            'success' => true,
            'data' => $documents
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch documents: ' . $e->getMessage()
        ], 500);
    }
}

}