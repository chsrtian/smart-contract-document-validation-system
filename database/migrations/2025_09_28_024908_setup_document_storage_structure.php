<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations - Setup document storage structure for OCR workflow
     */
    public function up(): void
    {
        Log::info('Setting up document storage structure for OCR and blockchain workflow');

        // Define storage paths for organized document management
        $publicStoragePath = storage_path('app/public');
        
        // Create directory structure aligned with DocumentService expectations
        $directories = [
            // Main document storage by year/month/day structure
            'documents',
            'documents/' . date('Y'),
            'documents/' . date('Y') . '/' . date('m'),
            'documents/' . date('Y') . '/' . date('m') . '/' . date('d'),
            
            // Document type specific directories
            'documents/' . date('Y') . '/' . date('m') . '/' . date('d') . '/birth_certificate',
            'documents/' . date('Y') . '/' . date('m') . '/' . date('d') . '/death_certificate',
            'documents/' . date('Y') . '/' . date('m') . '/' . date('d') . '/marriage_certificate',
            
            // Processing directories for OCR workflow
            'temp',              // Temporary uploads
            'processed',         // OCR processed files
            'verified',          // Manually verified documents
            'blockchain_ready',  // Documents ready for blockchain submission
            
            // Cache and optimization
            'ocr_cache',         // OCR processing cache
            'thumbnails',        // Document thumbnails for preview
            
            // Backup and archive
            'archives',          // Long-term storage
            'exports'            // Export files
        ];

        $createdDirs = [];
        $errors = [];

        foreach ($directories as $directory) {
            $fullPath = $publicStoragePath . '/' . $directory;
            
            try {
                if (!File::exists($fullPath)) {
                    File::makeDirectory($fullPath, 0755, true);
                    
                    // Create .gitkeep to ensure directory is tracked in version control
                    File::put($fullPath . '/.gitkeep', '# Keep this directory in version control');
                    
                    $createdDirs[] = $directory;
                }
            } catch (\Exception $e) {
                $errors[] = "Failed to create {$directory}: " . $e->getMessage();
                Log::error("Directory creation failed", [
                    'directory' => $directory,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Create symbolic link for public access if it doesn't exist
        $linkPath = public_path('storage');
        $targetPath = storage_path('app/public');
        
        if (!File::exists($linkPath)) {
            try {
                // On Windows, use junction instead of symlink
                if (PHP_OS_FAMILY === 'Windows') {
                    exec("mklink /J \"{$linkPath}\" \"{$targetPath}\"", $output, $returnCode);
                    if ($returnCode === 0) {
                        Log::info('Storage link created successfully (Windows junction)');
                    }
                } else {
                    symlink($targetPath, $linkPath);
                    Log::info('Storage symlink created successfully');
                }
            } catch (\Exception $e) {
                Log::warning('Could not create storage link', ['error' => $e->getMessage()]);
            }
        }

        Log::info('Document storage structure setup completed', [
            'directories_created' => count($createdDirs),
            'created_paths' => $createdDirs,
            'errors' => $errors,
            'total_directories' => count($directories)
        ]);

        if (!empty($errors)) {
            Log::error('Some directories failed to create', ['errors' => $errors]);
        }
    }

    /**
     * Reverse the migrations - Clean up created directories
     */
    public function down(): void
    {
        Log::info('Rolling back document storage structure');

        $publicStoragePath = storage_path('app/public');
        
        // Only remove empty directories to avoid data loss
        $directoriesToCheck = [
            'ocr_cache',
            'thumbnails',
            'temp',
            'exports'
        ];

        foreach ($directoriesToCheck as $directory) {
            $fullPath = $publicStoragePath . '/' . $directory;
            
            if (File::exists($fullPath) && File::isDirectory($fullPath)) {
                // Only remove if directory is empty (only contains .gitkeep)
                $files = File::allFiles($fullPath);
                $gitkeepOnly = count($files) === 0 || 
                              (count($files) === 1 && basename($files[0]) === '.gitkeep');
                
                if ($gitkeepOnly) {
                    try {
                        File::deleteDirectory($fullPath);
                        Log::info("Removed empty directory: {$directory}");
                    } catch (\Exception $e) {
                        Log::warning("Could not remove directory: {$directory}", [
                            'error' => $e->getMessage()
                        ]);
                    }
                } else {
                    Log::info("Skipped directory removal (contains files): {$directory}");
                }
            }
        }

        Log::info('Document storage structure rollback completed');
    }
};