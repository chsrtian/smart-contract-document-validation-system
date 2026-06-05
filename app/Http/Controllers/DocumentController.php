<?php

namespace App\Http\Controllers;

use App\Models\Scan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DocumentController extends Controller
{
    // REMOVED: Problematic middleware call - use route middleware instead
    
    /**
     * Display the specified document
     */
    public function show($id)
    {
        try {
            Log::info("DocumentController: Show document ID: {$id} for user: " . Auth::id());
            
            // FIXED: Use only existing columns
            $document = Scan::where('id', $id)
                ->where('processed_by', Auth::id())
                ->first();

            if (!$document) {
                Log::warning("DocumentController: Document not found for ID: {$id}");
                return response()->json([
                    'success' => false,
                    'message' => 'Document not found or access denied'
                ], 404);
            }

            // FIXED: Safe handling of extracted_fields
            $extractedFields = [];
            if ($document->extracted_fields) {
                if (is_string($document->extracted_fields)) {
                    $extractedFields = json_decode($document->extracted_fields, true) ?? [];
                } elseif (is_array($document->extracted_fields)) {
                    $extractedFields = $document->extracted_fields;
                }
            }

            return response()->json([
                'success' => true,
                'document' => [
                    'id' => $document->id,
                    'document_id' => $document->document_id ?? 'DOC-' . $document->id,
                    'document_type' => $document->document_type ?? 'unknown',
                    'title' => $document->title ?? ($document->document_id ?? 'Document'),
                    'verification_status' => $document->verification_status ?? 'pending',
                    'extracted_fields' => $extractedFields,
                    'created_at' => $document->created_at,
                    'file_path' => $document->file_path
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('DocumentController: Show failed', [
                'document_id' => $id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error loading document: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * View document in modal/popup
     */
    public function view($id)
    {
        try {
            Log::info("DocumentController: View document ID: {$id} for user: " . Auth::id());
            
            // FIXED: Use only existing columns initially, then expand after migration
            $query = Scan::where('id', $id);
            
            // Check if new columns exist and use them
            if (Schema::hasColumn('scans', 'user_id') && Schema::hasColumn('scans', 'created_by')) {
                $query->where(function($subQuery) {
                    $subQuery->where('processed_by', Auth::id())
                             ->orWhere('user_id', Auth::id())
                             ->orWhere('created_by', Auth::id());
                });
            } else {
                $query->where('processed_by', Auth::id());
            }
            
            $document = $query->first();

            if (!$document) {
                Log::warning("DocumentController: Document not found for view ID: {$id}");
                return response()->json([
                    'success' => false,
                    'message' => 'Document not found or access denied'
                ], 404);
            }

            // FIXED: Build file URL with proper path checking
            $fileUrl = null;
            if ($document->full_file_path) {
                $fileUrl = route('scans.image.preview', ['scan' => $document->id]);
            }

            // FIXED: Safe extracted fields handling
            $extractedFields = [];
            if ($document->extracted_fields) {
                if (is_string($document->extracted_fields)) {
                    $decoded = json_decode($document->extracted_fields, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $extractedFields = $decoded;
                    }
                } elseif (is_array($document->extracted_fields)) {
                    $extractedFields = $document->extracted_fields;
                }
            }

            return response()->json([
                'success' => true,
                'document' => [
                    'id' => $document->id,
                    'document_id' => $document->document_id ?? 'DOC-' . $document->id,
                    'document_type' => $document->document_type ?? 'unknown',
                    'title' => $document->title ?? ($document->document_id ?? 'Document'),
                    'file_url' => $fileUrl,
                    'verification_status' => $document->verification_status ?? 'pending',
                    'extracted_fields' => $extractedFields,
                    'created_at' => $document->created_at ? $document->created_at->format('M j, Y g:i A') : 'Unknown',
                    'ocr_confidence' => $document->ocr_confidence ?? 0
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('DocumentController: View failed', [
                'document_id' => $id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error viewing document: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download the specified document
     */
    public function download($id)
    {
        try {
            Log::info("DocumentController: Download document ID: {$id} for user: " . Auth::id());
            
            // FIXED: Use safe column checking
            $query = Scan::where('id', $id);
            
            if (Schema::hasColumn('scans', 'user_id') && Schema::hasColumn('scans', 'created_by')) {
                $query->where(function($subQuery) {
                    $subQuery->where('processed_by', Auth::id())
                             ->orWhere('user_id', Auth::id())
                             ->orWhere('created_by', Auth::id());
                });
            } else {
                $query->where('processed_by', Auth::id());
            }
            
            $document = $query->first();

            if (!$document) {
                Log::warning("DocumentController: Document not found for download ID: {$id}");
                abort(404, 'Document not found or access denied');
            }

            // FIXED: Try multiple possible file paths
            $filePath = null;
            $possiblePaths = [
                storage_path('app/public/' . $document->file_path),
                storage_path('app/public/documents/' . $document->file_path),
                storage_path('app/public/scans/' . $document->file_path),
                storage_path('app/public/uploads/' . $document->file_path)
            ];

            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    $filePath = $path;
                    break;
                }
            }
            
            if (!$filePath) {
                Log::warning("DocumentController: File not found", [
                    'document_id' => $id,
                    'file_path' => $document->file_path,
                    'tried_paths' => $possiblePaths
                ]);
                abort(404, 'File not found');
            }

            // FIXED: Generate proper filename
            $extension = pathinfo($document->file_path, PATHINFO_EXTENSION) ?: 'pdf';
            $downloadName = ($document->document_id ?? 'document-' . $document->id) . '.' . $extension;
            
            Log::info("DocumentController: Starting download", [
                'file_path' => $filePath,
                'download_name' => $downloadName
            ]);

            return response()->download($filePath, $downloadName);

        } catch (\Exception $e) {
            Log::error('DocumentController: Download failed', [
                'document_id' => $id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            
            abort(500, 'Download failed: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified document
     */
    public function destroy($id)
    {
        try {
            Log::info("DocumentController: Delete document ID: {$id} for user: " . Auth::id());
            
            // FIXED: Use safe column checking
            $query = Scan::where('id', $id);
            
            if (Schema::hasColumn('scans', 'user_id') && Schema::hasColumn('scans', 'created_by')) {
                $query->where(function($subQuery) {
                    $subQuery->where('processed_by', Auth::id())
                             ->orWhere('user_id', Auth::id())
                             ->orWhere('created_by', Auth::id());
                });
            } else {
                $query->where('processed_by', Auth::id());
            }
            
            $document = $query->first();

            if (!$document) {
                Log::warning("DocumentController: Document not found for delete ID: {$id}");
                return response()->json([
                    'success' => false,
                    'message' => 'Document not found or access denied'
                ], 404);
            }

            // FIXED: Delete file from multiple possible locations
            if ($document->file_path) {
                $possiblePaths = [
                    $document->file_path,
                    'documents/' . $document->file_path,
                    'scans/' . $document->file_path,
                    'uploads/' . $document->file_path
                ];
                
                foreach ($possiblePaths as $path) {
                    if (Storage::disk('public')->exists($path)) {
                        Storage::disk('public')->delete($path);
                        Log::info("DocumentController: File deleted from: " . $path);
                        break;
                    }
                }
            }

            $documentId = $document->document_id ?? $document->id;
            $document->delete();
            
            Log::info("DocumentController: Document deleted successfully: {$documentId}");

            return response()->json([
                'success' => true,
                'message' => 'Document deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('DocumentController: Delete failed', [
                'document_id' => $id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete document: ' . $e->getMessage()
            ], 500);
        }
    }
}