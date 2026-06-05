<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OCRService – Thin HTTP wrapper for the PaddleOCR micro-service.
 *
 * Drop-in replacement for the old Tesseract calls inside ScanController.
 * The Python service must be running at PADDLE_OCR_URL (default http://127.0.0.1:8001).
 *
 * Response contract (matches what ScanController::processOCR already expects):
 *   [
 *       'success'    => bool,
 *       'raw_text'   => string,   // full page text, newline-separated
 *       'confidence' => float,    // 0-100
 *       'word_count' => int,
 *       'error'      => string|null,
 *   ]
 */
class OCRService
{
    /**
     * Base URL of the PaddleOCR micro-service.
     */
    protected string $baseUrl;

    /**
     * HTTP timeout in seconds for OCR requests.
     */
    protected int $timeout;

    /**
     * Whether to allow Tesseract fallback when PaddleOCR fails.
     * Set PADDLE_OCR_DISABLE_FALLBACK=true in .env to test PaddleOCR in isolation.
     */
    protected bool $disableFallback;

    /**
     * Cache TTL (seconds) for recent health-check status.
     */
    protected int $healthCacheTtl;

    /**
     * Process-level health cache shared across requests in the same PHP worker.
     */
    protected static ?bool $cachedHealthStatus = null;
    protected static int $cachedHealthCheckedAt = 0;

    public function __construct()
    {
        $this->baseUrl = rtrim(env('PADDLE_OCR_URL', 'http://127.0.0.1:8001'), '/');
        $this->timeout = (int) env('PADDLE_OCR_TIMEOUT', 120);
        $this->disableFallback = filter_var(env('PADDLE_OCR_DISABLE_FALLBACK', false), FILTER_VALIDATE_BOOLEAN);
        $this->healthCacheTtl = max(0, (int) env('PADDLE_OCR_HEALTH_CACHE_TTL', 8));
    }

    /**
     * Whether Tesseract fallback is disabled (PaddleOCR-only testing mode).
     */
    public function isFallbackDisabled(): bool
    {
        return $this->disableFallback;
    }

    // ------------------------------------------------------------------
    // Public API
    // ------------------------------------------------------------------

    /**
     * Send an image file to the PaddleOCR service and return raw OCR results.
     *
     * @param  string  $imagePath     Absolute path to the image on disk.
     * @param  string  $documentType  birth_certificate|death_certificate|marriage_certificate|other
     * @return array   Always contains 'success' key.
     */
    public function extractText(string $imagePath, string $documentType = 'birth_certificate'): array
    {
        Log::info('OCRService: Sending image to PaddleOCR', [
            'image_path'    => $imagePath,
            'document_type' => $documentType,
            'service_url'   => $this->baseUrl,
        ]);

        // 1. Validate file exists
        if (!file_exists($imagePath)) {
            Log::error('OCRService: Image file not found', ['path' => $imagePath]);
            return [
                'success' => false,
                'error'   => 'Image file not found: ' . $imagePath,
            ];
        }

        // 2. Check service health (quick — 5s timeout)
        if (!$this->isHealthy()) {
            Log::error('OCRService: PaddleOCR service is unreachable', [
                'url' => $this->baseUrl,
            ]);
            return [
                'success' => false,
                'error'   => 'PaddleOCR service is not running. Start it with: python paddle_ocr_service/main.py',
            ];
        }

        // 3. Send image via multipart POST (long timeout — OCR on CPU is slow)
        try {
            $response = Http::timeout($this->timeout)
                ->connectTimeout(10)
                ->attach(
                    'file',
                    file_get_contents($imagePath),
                    basename($imagePath)
                )
                ->post("{$this->baseUrl}/ocr", [
                    'document_type' => $documentType,
                ]);

            if (!$response->successful()) {
                Log::error('OCRService: PaddleOCR returned error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return [
                    'success' => false,
                    'error'   => 'PaddleOCR returned HTTP ' . $response->status(),
                ];
            }

            $data = $response->json();

            if (!($data['success'] ?? false)) {
                return [
                    'success' => false,
                    'error'   => $data['error'] ?? 'Unknown PaddleOCR error',
                ];
            }

            $roi = is_array($data['roi'] ?? null) ? $data['roi'] : [];
            $roiAttempted = (bool) ($roi['attempted'] ?? false);
            $roiApplied = (bool) ($roi['applied'] ?? false);
            $roiUsed = (bool) ($roi['used'] ?? false);
            $roiFallbackUsed = (bool) ($roi['fallback_used'] ?? false);
            $roiFallbackReasons = $roi['fallback_reasons'] ?? [];
            $roiAreaRatio = $roi['area_ratio'] ?? null;
            $roiTimingMs = $data['timings_ms']['roi_probe_total'] ?? null;

            if ($roi === []) {
                Log::warning('OCRService: ROI metadata missing in OCR response', [
                    'document_type' => $documentType,
                    'service_url' => $this->baseUrl,
                ]);
            }

            Log::info('OCRService: PaddleOCR extraction successful', [
                'engine_used'      => $data['engine_used'] ?? 'baseline_detector',
                'fallback_used'    => $data['fallback_used'] ?? false,
                'fallback_reasons' => $data['fallback_reasons'] ?? [],
                'confidence'       => $data['confidence'] ?? 0,
                'word_count'       => $data['word_count'] ?? 0,
                'box_count'        => $data['box_count'] ?? count($data['boxes'] ?? []),
                'normalization'    => $data['normalization'] ?? [],
                'roi'              => $roi,
                'roi_attempted'    => $roiAttempted,
                'roi_applied'      => $roiApplied,
                'roi_used'         => $roiUsed,
                'roi_fallback_used'=> $roiFallbackUsed,
                'roi_fallback_reasons' => $roiFallbackReasons,
                'roi_area_ratio'   => $roiAreaRatio,
                'roi_timing_ms'    => $roiTimingMs,
                'processing_ms'    => $data['processing_time_ms'] ?? null,
                'pass2_used'       => $data['pass2_used'] ?? false,
                'timings_ms'       => $data['timings_ms'] ?? null,
            ]);

            return [
                'success'    => true,
                'raw_text'   => $data['raw_text'] ?? '',
                'confidence' => $data['confidence'] ?? 0,
                'word_count' => $data['word_count'] ?? 0,
                'line_count' => $data['line_count'] ?? 0,
                'boxes'      => $data['boxes'] ?? [],
                'box_count'  => $data['box_count'] ?? count($data['boxes'] ?? []),
                'processing_time_ms' => $data['processing_time_ms'] ?? null,
                'timings_ms' => $data['timings_ms'] ?? [],
                'pass2_used' => $data['pass2_used'] ?? false,
                'engine_used' => $data['engine_used'] ?? 'baseline_detector',
                'fallback_used' => $data['fallback_used'] ?? false,
                'fallback_reasons' => $data['fallback_reasons'] ?? [],
                'normalization' => $data['normalization'] ?? [],
                'roi' => $roi,
                'roi_attempted' => $roiAttempted,
                'roi_applied' => $roiApplied,
                'roi_used' => $roiUsed,
                'roi_fallback_used' => $roiFallbackUsed,
                'roi_fallback_reasons' => $roiFallbackReasons,
                'roi_area_ratio' => $roiAreaRatio,
                'roi_timing_ms' => $roiTimingMs,
            ];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('OCRService: Connection to PaddleOCR failed', [
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'error'   => 'Cannot connect to PaddleOCR service: ' . $e->getMessage(),
            ];
        } catch (\Exception $e) {
            Log::error('OCRService: Unexpected error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'error'   => 'OCR service error: ' . $e->getMessage(),
            ];
        }
    }

    // ------------------------------------------------------------------
    // Health check
    // ------------------------------------------------------------------

    /**
     * Quick check whether the PaddleOCR service is reachable.
     */
    public function isHealthy(): bool
    {
        $now = time();
        $isCacheFresh = self::$cachedHealthStatus !== null
            && ($now - self::$cachedHealthCheckedAt) < $this->healthCacheTtl;

        if ($isCacheFresh) {
            return self::$cachedHealthStatus;
        }

        try {
            $response = Http::timeout(3)->get("{$this->baseUrl}/health");
            $status = $response->successful() && ($response->json('status') === 'ok');
            self::$cachedHealthStatus = $status;
            self::$cachedHealthCheckedAt = $now;

            return $status;
        } catch (\Exception $e) {
            self::$cachedHealthStatus = false;
            self::$cachedHealthCheckedAt = $now;
            return false;
        }
    }
}
