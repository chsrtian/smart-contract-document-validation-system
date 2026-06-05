<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            // Add missing columns that DocumentService expects
            $table->string('document_id')->unique()->after('id');
            $table->enum('document_type', [
                'birth_certificate',
                'death_certificate', 
                'marriage_certificate',
                'cenomar',
                'affidavit',
                'court_document',
                'contract',
                'other'
            ])->after('document_id');
            $table->string('file_path')->after('document_type');
            $table->string('file_hash')->nullable()->after('file_path');
            $table->integer('ocr_confidence')->default(0)->after('file_hash');
            $table->enum('verification_status', ['draft', 'pending', 'completed', 'rejected'])
                  ->default('draft')->after('ocr_confidence');
            
            // User tracking columns
            $table->foreignId('processed_by')->nullable()->constrained('users')->after('verification_status');
            $table->foreignId('verified_by')->nullable()->constrained('users')->after('processed_by');
            
            // Additional timestamps
            $table->timestamp('processed_at')->nullable()->after('verified_by');
            $table->timestamp('verified_at')->nullable()->after('processed_at');
            
            // JSON fields for OCR and extracted data
            $table->json('ocr_data')->nullable()->after('updated_at');
            $table->json('extracted_fields')->nullable()->after('ocr_data');
            
            // Optional notes field
            $table->text('notes')->nullable()->after('extracted_fields');
            
            // Add indexes for performance
            $table->index(['document_type', 'verification_status'], 'idx_doc_type_status');
            $table->index(['processed_by', 'created_at'], 'idx_processed_by_date');
            $table->index(['verification_status', 'created_at'], 'idx_status_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            // Remove indexes first
            $table->dropIndex('idx_doc_type_status');
            $table->dropIndex('idx_processed_by_date');
            $table->dropIndex('idx_status_date');
            
            // Drop foreign key constraints
            $table->dropForeign(['processed_by']);
            $table->dropForeign(['verified_by']);
            
            // Drop added columns
            $table->dropColumn([
                'document_id',
                'document_type',
                'file_path',
                'file_hash',
                'ocr_confidence',
                'verification_status',
                'processed_by',
                'verified_by',
                'processed_at',
                'verified_at',
                'ocr_data',
                'extracted_fields',
                'notes'
            ]);
        });
    }
};