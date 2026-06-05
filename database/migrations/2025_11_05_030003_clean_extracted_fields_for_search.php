<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        Log::info('Starting safe extracted_fields cleanup for search compatibility');
        
        $scans = DB::table('scans')
            ->whereNotNull('extracted_fields')
            ->where('verification_status', 'completed') // Only fix completed documents
            ->get();
        
        $fixed = 0;
        
        foreach ($scans as $scan) {
            $extractedFields = json_decode($scan->extracted_fields, true);
            $ocrData = json_decode($scan->ocr_data, true) ?? [];
            
            if (!is_array($extractedFields)) {
                continue;
            }
            
            // Check if this record needs cleaning
            $needsCleaning = isset($extractedFields['certificate_type']) || 
                            isset($extractedFields['word_count']) ||
                            isset($extractedFields['confidence']);
            
            if (!$needsCleaning) {
                continue; // Skip already clean records
            }
            
            // OCR metadata fields to move from extracted_fields to ocr_data
            $metadataFields = [
                'certificate_type', 'confidence', 'word_count', 'fields_detected',
                'processing_time', 'processing_method', 'quality_metrics', 
                'line_count', 'ocr_engine', 'cleaned_text', 'raw_text'
            ];
            
            // Separate metadata from actual data
            $cleanedFields = [];
            $movedMetadata = [];
            
            foreach ($extractedFields as $key => $value) {
                if (in_array($key, $metadataFields)) {
                    $movedMetadata[$key] = $value;
                } else {
                    $cleanedFields[$key] = $value;
                }
            }
            
            // Ensure manually_corrected flag exists
            if (!isset($cleanedFields['manually_corrected'])) {
                $cleanedFields['manually_corrected'] = true;
                $cleanedFields['corrected_at'] = $scan->updated_at;
                $cleanedFields['corrected_by'] = $scan->processed_by;
            }
            
            // Merge metadata into ocr_data (preserve existing ocr_data)
            $updatedOcrData = array_merge($ocrData, $movedMetadata);
            
            // ⚠️ CRITICAL: Do NOT touch blockchain fields
            DB::table('scans')
                ->where('id', $scan->id)
                ->update([
                    'extracted_fields' => json_encode($cleanedFields),
                    'ocr_data' => json_encode($updatedOcrData),
                    // Explicitly preserve blockchain fields
                    'updated_at' => $scan->updated_at // Don't update timestamp
                ]);
            
            $fixed++;
            
            Log::info("Cleaned scan {$scan->id}: Moved " . count($movedMetadata) . " metadata fields");
        }
        
        Log::info("Migration complete: Fixed {$fixed} records without affecting blockchain data");
    }

    public function down(): void
    {
        Log::warning('Rollback not implemented - data structure cleanup is one-way');
    }
};