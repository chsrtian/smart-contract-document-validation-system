<?php
// filepath: app\Services\OCR\BaseOCRProcessor.php

namespace App\Services\OCR;

use thiagoalessio\TesseractOCR\TesseractOCR;

abstract class BaseOCRProcessor
{
    protected $tesseract;
    
    public function __construct()
    {
        $this->tesseract = new TesseractOCR();
        $this->configureTesseract();
    }
    
    protected function configureTesseract()
    {
        $this->tesseract
            ->lang('eng', 'fil') // English + Filipino
            ->psm(6) // Uniform block of text
            ->oem(3) // Default OCR Engine Mode
            ->allowlist('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789.,()-/:; ');
    }
    
    public function extractFields($file, $documentConfig)
    {
        $startTime = microtime(true);
        
        // Save uploaded file temporarily
        $tempPath = storage_path('app/temp/' . time() . '_' . $file->getClientOriginalName());
        $file->move(dirname($tempPath), basename($tempPath));
        
        try {
            // Extract raw text
            $rawText = $this->tesseract->image($tempPath)->run();
            
            // Calculate confidence and word count
            $confidence = $this->calculateConfidence($rawText);
            $wordCount = str_word_count($rawText);
            
            // Extract structured fields based on document type
            $extractedFields = $this->extractStructuredFields($rawText, $documentConfig);
            
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);
            
            return [
                'raw_text' => $rawText,
                'confidence' => $confidence,
                'word_count' => $wordCount,
                'extracted_fields' => $extractedFields,
                'processing_time' => $processingTime
            ];
            
        } finally {
            // Clean up temporary file
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
        }
    }
    
    abstract protected function extractStructuredFields($rawText, $documentConfig);
    
    protected function calculateConfidence($text)
    {
        if (empty($text)) return 0;
        
        $confidence = 50; // Base confidence
        
        // Text quality indicators
        if (preg_match('/[A-Z][a-z]+/', $text)) $confidence += 20; // Proper capitalization
        if (strlen($text) > 100) $confidence += 15; // Sufficient text length
        if (preg_match('/\d{4}/', $text)) $confidence += 10; // Contains years
        if (!preg_match('/[^\w\s.,()-\/:;]/', $text)) $confidence += 5; // No strange characters
        
        return min(100, $confidence);
    }
    
    protected function findFieldValue($text, $patterns, $type = 'text')
    {
        foreach ($patterns as $pattern) {
            if (preg_match('/' . preg_quote($pattern, '/') . '\s*:?\s*([^\n\r]+)/i', $text, $matches)) {
                $value = trim($matches[1]);
                
                if ($type === 'name') {
                    return $this->cleanNameField($value);
                } elseif ($type === 'date') {
                    return $this->extractDateComponents($value);
                }
                
                return $value;
            }
        }
        
        return null;
    }
    
    protected function cleanNameField($value)
    {
        // Remove common OCR artifacts and clean name
        $value = preg_replace('/[^a-zA-Z\s.-]/', '', $value);
        $value = preg_replace('/\s+/', ' ', $value);
        return ucwords(strtolower(trim($value)));
    }
    
    protected function extractDateComponents($dateText)
    {
        // Extract day, month, year from various date formats
        $patterns = [
            '/(\d{1,2})[\/\-\s]+([A-Za-z]+|\d{1,2})[\/\-\s]+(\d{4})/',
            '/([A-Za-z]+)\s+(\d{1,2}),?\s+(\d{4})/',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $dateText, $matches)) {
                return [
                    'day' => $matches[1],
                    'month' => $matches[2],
                    'year' => $matches[3]
                ];
            }
        }
        
        return null;
    }
}