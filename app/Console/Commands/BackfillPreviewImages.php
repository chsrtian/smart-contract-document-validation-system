<?php

namespace App\Console\Commands;

use App\Models\Scan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Artisan command to generate missing preview images for old PDF-only scan records.
 *
 * Two extraction strategies are used depending on how DomPDF embedded the image:
 *   1. DCTDecode  – raw JPEG bytes sitting inside the PDF (older DomPDF output).
 *   2. FlateDecode – zlib-compressed raw RGB pixel data (newer DomPDF output).
 *                    Supports PNG predictor (Predictor 15) used by modern DomPDF.
 *
 * Usage:
 *   php artisan scan:backfill-previews            # dry-run (shows what would be done)
 *   php artisan scan:backfill-previews --execute   # actually generate the files
 *   php artisan scan:backfill-previews --execute --limit=5   # process only 5 records
 */
class BackfillPreviewImages extends Command
{
    protected $signature = 'scan:backfill-previews
                            {--execute : Actually generate preview files (dry-run by default)}
                            {--limit=0 : Max number of records to process (0 = all)}';

    protected $description = 'Generate missing JPG/PNG preview images for old PDF-only scan records';

    /** Counters for the summary. */
    private int $scanned = 0;
    private int $skipped = 0;
    private int $generated = 0;
    private int $failed = 0;

    public function handle(): int
    {
        $isDryRun = !$this->option('execute');
        $limit    = (int) $this->option('limit');

        $this->info('');
        $this->info($isDryRun
            ? '🔍  DRY-RUN MODE — no files will be written. Add --execute to generate.'
            : '🚀  EXECUTE MODE — preview images will be generated.'
        );
        $this->info('');

        $query = Scan::query()->whereNotNull('file_path')->orderBy('id');
        if ($limit > 0) {
            $query->limit($limit);
        }

        $bar = $this->output->createProgressBar($limit > 0 ? $limit : Scan::count());
        $bar->start();

        $query->chunk(50, function ($scans) use ($isDryRun, &$bar) {
            foreach ($scans as $scan) {
                $this->processScan($scan, $isDryRun);
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->table(
            ['Metric', 'Count'],
            [
                ['Scanned',   $this->scanned],
                ['Skipped (already has preview or is image)', $this->skipped],
                ['Generated', $this->generated],
                ['Failed',    $this->failed],
            ]
        );

        if ($isDryRun && $this->generated > 0) {
            $this->warn("Run again with --execute to actually create the {$this->generated} preview file(s).");
        }

        return self::SUCCESS;
    }

    private function processScan(Scan $scan, bool $isDryRun): void
    {
        $this->scanned++;

        $pdfPath = $scan->full_file_path;

        // No file at all
        if (!$pdfPath || !file_exists($pdfPath)) {
            $this->skipped++;
            return;
        }

        $mime = @mime_content_type($pdfPath);

        // Already an image file — no preview needed
        if ($mime !== 'application/pdf') {
            $this->skipped++;
            return;
        }

        $dir  = pathinfo($pdfPath, PATHINFO_DIRNAME);
        $base = pathinfo($pdfPath, PATHINFO_FILENAME);

        // Check if sibling preview already exists
        foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
            if (file_exists($dir . DIRECTORY_SEPARATOR . $base . '.' . $ext)) {
                $this->skipped++;
                return;
            }
        }

        // This record needs a preview image
        if ($isDryRun) {
            $this->generated++;
            return;
        }

        // Try to generate
        $previewPath = $this->generatePreviewFromPdf($pdfPath, $dir, $base, $scan->id);

        if ($previewPath) {
            $this->generated++;
            Log::info('BackfillPreviewImages: Preview generated', [
                'scan_id'      => $scan->id,
                'preview_path' => $previewPath,
                'size'         => filesize($previewPath),
            ]);
        } else {
            $this->failed++;
            Log::warning('BackfillPreviewImages: Could not generate preview', [
                'scan_id'  => $scan->id,
                'pdf_path' => $pdfPath,
            ]);
        }
    }

    /**
     * Generate a preview image from a DomPDF-generated PDF.
     *
     * Strategy 1: Extract raw JPEG via binary signature (DCTDecode PDFs).
     * Strategy 2: Decompress FlateDecode stream and rebuild from raw pixels.
     *
     * @return string|null  Absolute path of the generated preview, or null on failure.
     */
    private function generatePreviewFromPdf(string $pdfPath, string $dir, string $base, int $scanId): ?string
    {
        $pdfBytes = @file_get_contents($pdfPath);
        if ($pdfBytes === false) {
            return null;
        }

        // ── Strategy 1: DCTDecode — raw JPEG inside the PDF ─────────────
        if (preg_match('/\xFF\xD8\xFF[\s\S]*?\xFF\xD9/', $pdfBytes, $m)) {
            $jpegBytes = $m[0];
            if (strlen($jpegBytes) > 500) {
                // Validate with GD
                $img = @imagecreatefromstring($jpegBytes);
                if ($img) {
                    imagedestroy($img);
                    $outputPath = $dir . DIRECTORY_SEPARATOR . $base . '.jpg';
                    if (@file_put_contents($outputPath, $jpegBytes) !== false) {
                        return $outputPath;
                    }
                }
            }
        }

        // ── Strategy 2: FlateDecode — compressed pixel data ─────────────
        $outputPath = $this->extractFlateDecodedImage($pdfBytes, $dir, $base);
        if ($outputPath) {
            return $outputPath;
        }

        return null;
    }

    /**
     * Extract an image from a FlateDecode stream inside a DomPDF PDF.
     *
     * Modern DomPDF stores images as PDF Image XObjects with:
     *   /Subtype /Image
     *   /Width <w>  /Height <h>
     *   /ColorSpace /DeviceRGB (or /DeviceGray)
     *   /BitsPerComponent 8
     *   /Filter /FlateDecode
     *   /DecodeParms << /Predictor 15 /Colors 3 /Columns <w> ... >>
     *   /Length <n>
     *   stream ... endstream
     *
     * We decompress the zlib stream and un-apply the PNG sub/up/average/paeth
     * prediction filter, then rebuild the image from raw pixel bytes using GD.
     */
    private function extractFlateDecodedImage(string $pdfBytes, string $dir, string $base): ?string
    {
        // Find every occurrence of /Subtype /Image in the PDF
        $searchNeedle = '/Subtype /Image';
        $searchOffset = 0;

        while (($imagePos = strpos($pdfBytes, $searchNeedle, $searchOffset)) !== false) {
            $searchOffset = $imagePos + strlen($searchNeedle);

            // Walk backwards to find "N 0 obj\n<<" for this image object
            $lookBack = min(500, $imagePos);
            $beforeChunk = substr($pdfBytes, $imagePos - $lookBack, $lookBack);

            // Find the last "N 0 obj" in the chunk before our needle
            if (!preg_match_all('/(\d+)\s+0\s+obj\s*\n?<</s', $beforeChunk, $objMatches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
                continue;
            }
            // Take the LAST match (closest obj before /Subtype /Image)
            $lastObjMatch = end($objMatches);
            $objAbsOffset = ($imagePos - $lookBack) + $lastObjMatch[0][1];

            // Walk forwards from /Subtype /Image to find >>...stream\n
            $afterChunk = substr($pdfBytes, $imagePos, 1000);
            if (!preg_match('/>>[\s\r\n]*stream\r?\n/', $afterChunk, $streamMatch, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            $streamStart = $imagePos + $streamMatch[0][1] + strlen($streamMatch[0][0]);

            // Build the dictionary text from obj start to the >> before stream
            $dictText = substr($pdfBytes, $objAbsOffset, ($imagePos + $streamMatch[0][1]) - $objAbsOffset);

            // Parse Width, Height
            if (!preg_match('/\/Width\s+(\d+)/', $dictText, $wm)) continue;
            if (!preg_match('/\/Height\s+(\d+)/', $dictText, $hm)) continue;
            $width  = (int) $wm[1];
            $height = (int) $hm[1];
            if ($width < 10 || $height < 10) continue;

            // Parse Length (may be right before >> with no trailing space)
            if (!preg_match('/\/Length\s+(\d+)/', $dictText, $lm)) continue;
            $streamLength = (int) $lm[1];
            if ($streamLength < 100) continue;

            // Must be FlateDecode
            if (strpos($dictText, 'FlateDecode') === false) continue;

            // Determine color space
            $isGray   = (bool) preg_match('/\/ColorSpace\s*\/DeviceGray/', $dictText);
            $channels = $isGray ? 1 : 3;

            // Check for PNG predictor
            $hasPredictor = (bool) preg_match('/\/Predictor\s+1[0-5]/', $dictText);

            // Extract raw stream bytes
            $rawStream = substr($pdfBytes, $streamStart, $streamLength);
            if (strlen($rawStream) < $streamLength) continue;

            // Decompress
            $decompressed = @gzuncompress($rawStream);
            if ($decompressed === false) {
                $decompressed = @gzinflate($rawStream);
            }
            if ($decompressed === false) continue;

            // Determine pixel data
            $pixelData = $decompressed;

            if ($hasPredictor) {
                // PNG prediction: each row has a 1-byte filter type prefix
                // followed by (width * channels) bytes of filtered pixel data
                $rowBytes = $width * $channels;
                $expectedWithFilter = ($rowBytes + 1) * $height;

                if (strlen($decompressed) >= $expectedWithFilter) {
                    $pixelData = $this->undoPngPrediction($decompressed, $width, $height, $channels);
                    if ($pixelData === null) continue;
                }
            } else {
                $expectedSize = $width * $height * $channels;
                if (abs(strlen($decompressed) - $expectedSize) > $width * $channels) continue;
            }

            // Rebuild image with GD
            $img = @imagecreatetruecolor($width, $height);
            if (!$img) continue;

            $offset = 0;
            $dataLen = strlen($pixelData);

            for ($y = 0; $y < $height; $y++) {
                for ($x = 0; $x < $width; $x++) {
                    if ($offset + $channels > $dataLen) break 2;

                    if ($isGray) {
                        $g = ord($pixelData[$offset++]);
                        $color = imagecolorallocate($img, $g, $g, $g);
                    } else {
                        $r = ord($pixelData[$offset++]);
                        $g = ord($pixelData[$offset++]);
                        $b = ord($pixelData[$offset++]);
                        $color = imagecolorallocate($img, $r, $g, $b);
                    }
                    imagesetpixel($img, $x, $y, $color);
                }
            }

            // Save as JPEG
            $outputPath = $dir . DIRECTORY_SEPARATOR . $base . '.jpg';
            $saved = imagejpeg($img, $outputPath, 85);
            imagedestroy($img);

            if ($saved && file_exists($outputPath) && filesize($outputPath) > 100) {
                return $outputPath;
            }
        }

        return null;
    }

    /**
     * Reverse the PNG prediction filter applied to deflated image data.
     *
     * PDF Predictor 15 uses the PNG "optimum" filter: each scanline is prefixed
     * by a filter-type byte (0=None, 1=Sub, 2=Up, 3=Average, 4=Paeth).
     *
     * @return string|null  Raw pixel data (width*height*channels bytes), or null on failure.
     */
    private function undoPngPrediction(string $data, int $width, int $height, int $channels): ?string
    {
        $rowBytes = $width * $channels;
        $result   = '';
        $prevRow  = str_repeat("\x00", $rowBytes);
        $offset   = 0;

        for ($y = 0; $y < $height; $y++) {
            if ($offset >= strlen($data)) return null;

            $filterType = ord($data[$offset++]);
            $currentRow = substr($data, $offset, $rowBytes);
            if (strlen($currentRow) < $rowBytes) return null;
            $offset += $rowBytes;

            $decoded = '';

            for ($i = 0; $i < $rowBytes; $i++) {
                $raw  = ord($currentRow[$i]);
                $a    = $i >= $channels ? ord($decoded[$i - $channels]) : 0; // left
                $b    = ord($prevRow[$i]);                                    // above
                $c    = ($i >= $channels) ? ord($prevRow[$i - $channels]) : 0; // upper-left

                switch ($filterType) {
                    case 0: // None
                        $decoded .= chr($raw);
                        break;
                    case 1: // Sub
                        $decoded .= chr(($raw + $a) & 0xFF);
                        break;
                    case 2: // Up
                        $decoded .= chr(($raw + $b) & 0xFF);
                        break;
                    case 3: // Average
                        $decoded .= chr(($raw + (int) floor(($a + $b) / 2)) & 0xFF);
                        break;
                    case 4: // Paeth
                        $decoded .= chr(($raw + $this->paethPredictor($a, $b, $c)) & 0xFF);
                        break;
                    default:
                        return null; // Unknown filter
                }
            }

            $result .= $decoded;
            $prevRow = $decoded;
        }

        return $result;
    }

    /**
     * Paeth prediction function (PNG spec).
     */
    private function paethPredictor(int $a, int $b, int $c): int
    {
        $p  = $a + $b - $c;
        $pa = abs($p - $a);
        $pb = abs($p - $b);
        $pc = abs($p - $c);

        if ($pa <= $pb && $pa <= $pc) return $a;
        if ($pb <= $pc) return $b;
        return $c;
    }
}
