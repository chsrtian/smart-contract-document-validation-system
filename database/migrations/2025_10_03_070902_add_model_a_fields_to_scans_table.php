<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            // Add manual completion tracking
            if (!Schema::hasColumn('scans', 'manual_completion_score')) {
                $table->decimal('manual_completion_score', 5, 2)->default(0)->after('ocr_confidence');
            }
            
            // Add validation score (OCR + Manual)
            if (!Schema::hasColumn('scans', 'validation_score')) {
                $table->decimal('validation_score', 5, 2)->default(0)->after('manual_completion_score');
            }
            
            // Add blockchain eligibility flag
            if (!Schema::hasColumn('scans', 'blockchain_eligible')) {
                $table->boolean('blockchain_eligible')->default(false)->after('validation_score');
            }
            
            // Add reviewed fields (already exist, skip if present)
            if (!Schema::hasColumn('scans', 'reviewed_by')) {
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete()->after('blockchain_eligible');
            }
            
            if (!Schema::hasColumn('scans', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            }
            
            // Add index for performance
            $table->index(['validation_score', 'blockchain_eligible'], 'idx_model_a_eligibility');
        });
    }

    public function down(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            $table->dropIndex('idx_model_a_eligibility');
            $table->dropColumn([
                'manual_completion_score',
                'validation_score',
                'blockchain_eligible'
            ]);
        });
    }
};