<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$inputPath = $argv[1] ?? (__DIR__ . '/../database/a55a05a4-c045-4da8-bf45-f8a420ef1a53.jpeg');
$imagePath = realpath($inputPath);
if ($imagePath === false || !file_exists($imagePath)) {
    fwrite(STDERR, "Image not found\n");
    exit(1);
}

$ocrService = new App\Services\OCRService();
$result = $ocrService->extractText($imagePath, 'birth_certificate');
if (!($result['success'] ?? false)) {
    fwrite(STDERR, "OCR failed: " . ($result['error'] ?? 'unknown') . PHP_EOL);
    exit(1);
}

$controller = new App\Http\Controllers\ScanController();
$ref = new ReflectionClass($controller);
$method = $ref->getMethod('enhancedFieldExtraction');
$method->setAccessible(true);

$rawText = $result['raw_text'] ?? '';
$boxes = $result['boxes'] ?? [];
$fields = $method->invokeArgs($controller, [$rawText, 'birth_certificate', $rawText, $boxes]);

echo json_encode([
    'father_first_name' => $fields['father_first_name'] ?? '',
    'father_middle_name' => $fields['father_middle_name'] ?? '',
    'father_last_name' => $fields['father_last_name'] ?? '',
    'name_first' => $fields['name_first'] ?? '',
    'name_middle' => $fields['name_middle'] ?? '',
    'name_last' => $fields['name_last'] ?? '',
    'box_count' => $result['box_count'] ?? 0,
    'engine_used' => $result['engine_used'] ?? null,
], JSON_PRETTY_PRINT) . PHP_EOL;
