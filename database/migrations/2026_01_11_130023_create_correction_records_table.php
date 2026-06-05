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
        Schema::create('correction_records', function (Blueprint $table) {
            $table->id();
            
            // Reference to original document
            $table->foreignId('scan_id')->constrained('scans')->onDelete('cascade');
            $table->string('reference_tx_hash', 66);  // Original document's blockchain tx hash
            
            // Correction details (immutable after anchoring)
            $table->string('corrected_field');
            $table->text('previous_value');
            $table->text('new_value');
            $table->text('correction_reason');
            
            // Audit trail
            $table->foreignId('corrected_by_staff')->constrained('users');
            $table->foreignId('approved_by_supervisor')->constrained('users');
            
            // Blockchain anchoring (this correction's own transaction)
            $table->string('correction_tx_hash', 66)->nullable();        // NEW tx hash for correction
            $table->string('correction_document_hash', 66)->nullable();  // Hash of correction data
            $table->enum('blockchain_status', ['pending', 'confirmed', 'failed'])->default('pending');
            $table->timestamp('blockchain_submitted_at')->nullable();
            $table->timestamp('blockchain_confirmed_at')->nullable();
            $table->json('blockchain_metadata')->nullable();
            
            // Link back to the request
            $table->foreignId('correction_request_id')->constrained('correction_requests');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['scan_id', 'corrected_field']);
            $table->index('reference_tx_hash');
            $table->index('correction_tx_hash');
            $table->index(['scan_id', 'created_at']);  // For getting latest correction
        });

        // Add foreign key to correction_requests after correction_records exists
        Schema::table('correction_requests', function (Blueprint $table) {
            $table->foreign('correction_record_id')
                  ->references('id')
                  ->on('correction_records')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove foreign key first
        Schema::table('correction_requests', function (Blueprint $table) {
            $table->dropForeign(['correction_record_id']);
        });
        
        Schema::dropIfExists('correction_records');
    }
};