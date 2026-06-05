<?php
// Storage setup and verification script
require_once 'vendor/autoload.php';

$directories = [
    'storage/app/temp',
    'storage/app/temp/ocr',
    'storage/app/uploads',
    'storage/app/uploads/scans'
];

echo "Setting up storage directories for OCR...\n\n";

foreach ($directories as $dir) {
    $fullPath = __DIR__ . DIRECTORY_SEPARATOR . $dir;
    $fullPath = str_replace('/', DIRECTORY_SEPARATOR, $fullPath);
    
    if (!file_exists($fullPath)) {
        if (mkdir($fullPath, 0755, true)) {
            echo "✅ Created: $fullPath\n";
        } else {
            echo "❌ Failed to create: $fullPath\n";
        }
    } else {
        echo "✅ Exists: $fullPath\n";
    }
    
    // Check permissions
    if (is_writable($fullPath)) {
        echo "   📝 Writable: YES\n";
    } else {
        echo "   📝 Writable: NO (Fix permissions!)\n";
    }
    
    echo "\n";
}

echo "Storage setup complete!\n";
?>