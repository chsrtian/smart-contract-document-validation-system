<?php

namespace App\Http\Controllers;

use App\Models\Scan;
use App\Services\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * VerificationController - Handles document verification interface
 * 
 * Provides manual verification workflow for OCR processed documents
 */
class VerificationController extends Controller
{
    private $documentService;

    public function __construct(DocumentService $documentService)
    {
        $this->documentService = $documentService;
    }

    /**
     * Show verification queue
     */
    public function index()
    {
        $documents = $this->documentService->getDocumentsForVerification(20);
        
        return view('staff.verification.index', [
            'documents' => $documents,
            'pendingCount' => count($documents)
        ]);
    }

    /**
     * Show individual document verification interface
     */
    public function verify($documentId)
    {
        try {
            $document = Scan::findOrFail($documentId);
            $extractedFields = json_decode($document->extracted_fields, true) ?? [];
            $ocrData = json_decode($document->ocr_data, true) ?? [];
            
            $verificationData = [
                'document' => $document,
                'extracted_fields' => $extractedFields,
                'ocr_data' => $ocrData,
                'required_fields' => $this->documentService->getRequiredFields($document->document_type),
                'verification_progress' => $this->documentService->calculateVerificationProgress($extractedFields)
            ];
            
            return view('staff.verification.verify', $verificationData);
            
        } catch (\Exception $e) {
            Log::error('VerificationController: Document verification view failed', [
                'document_id' => $documentId,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('staff.verification.index')->with('error', 'Document not found');
        }
    }

    /**
     * Update document verification
     */
    public function update(Request $request, $documentId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'verification_status' => 'required|in:completed,rejected,pending',
                'extracted_fields' => 'required|array',
                'notes' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $document = Scan::findOrFail($documentId);
            
            // Update document with verified data
            $result = $this->documentService->updateDocumentStatus(
                $documentId,
                $request->input('verification_status'),
                $request->input('extracted_fields')
            );

            if ($result && $request->input('verification_status') === 'completed') {
                // Prepare for blockchain if verification is completed
                $blockchainResult = $this->documentService->prepareForBlockchain($documentId);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Document verified and prepared for blockchain',
                    'blockchain_ready' => $blockchainResult['success'] ?? false
                ]);
            }

            return response()->json([
                'success' => $result,
                'message' => $result ? 'Document verification updated' : 'Failed to update verification'
            ]);

        } catch (\Exception $e) {
            Log::error('VerificationController: Update verification failed', [
                'document_id' => $documentId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}