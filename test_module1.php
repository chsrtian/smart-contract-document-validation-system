<?php
/**
 * Module 1 diagnostic: test all doc type field extraction
 */
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$controller = new \App\Http\Controllers\ScanController();
$ref = new ReflectionMethod($controller, 'enhancedFieldExtraction');
$ref->setAccessible(true);

// ── BIRTH CERT ──────────────────────────────────────────
echo "=== BIRTH CERT EXTRACTION RESULTS ===\n";
$json = json_decode(file_get_contents('paddle_ocr_service/birth_test3.json'), true);
$text = $json['raw_text'] ?? '';
$boxes = $json['boxes'] ?? [];
$fields = $ref->invoke($controller, $text, 'birth_certificate', $text, $boxes);
$filled = array_filter($fields, fn($v) => !empty($v));
echo "Filled: " . count($filled) . " / " . count($fields) . "\n";
foreach ($filled as $k => $v) {
    echo sprintf("  %-30s => [%s]\n", $k, str_replace("\n", "\\n", $v));
}
$empty = array_filter($fields, fn($v) => empty($v));
if (!empty($empty)) {
    echo "EMPTY: " . implode(', ', array_keys($empty)) . "\n";
}

// ── MARRIAGE CERT ───────────────────────────────────────
echo "\n=== MARRIAGE CERT EXTRACTION RESULTS ===\n";
$json2 = json_decode(file_get_contents('paddle_ocr_service/marriage_test3.json'), true);
$text2 = $json2['raw_text'] ?? '';
$boxes2 = $json2['boxes'] ?? [];
$fields2 = $ref->invoke($controller, $text2, 'marriage_certificate', $text2, $boxes2);
$filled2 = array_filter($fields2, fn($v) => !empty($v));
echo "Filled: " . count($filled2) . " / " . count(array_merge($fields2, $filled2)) . "\n";
foreach ($filled2 as $k => $v) {
    echo sprintf("  %-35s => [%s]\n", $k, str_replace("\n", "\\n", $v));
}

// Check specifically for new fields
$target = ['marriage_officiant_position', 'marriage_license_number', 'witness_1_name', 'witness_2_name'];
echo "\n--- TARGET MARRIAGE FIELDS ---\n";
foreach ($target as $t) {
    $val = $fields2[$t] ?? '(not in output)';
    echo sprintf("  %-35s => [%s]\n", $t, $val);
}
