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
        Schema::create('correction_requests', function (Blueprint $table) {
            $table->id();
            
            // Reference to original document
            $table->foreignId('scan_id')->constrained('scans')->onDelete('cascade');
            $table->string('original_tx_hash', 66);  // Original blockchain tx hash (0x + 64 chars)
            
            // Correction details
            $table->string('field_name');            // e.g., 'sex', 'first_name', 'birth_date'
            $table->text('current_value');           // Wrong value
            $table->text('proposed_value');          // Correct value
            $table->text('reason');                  // Min 10 characters, mandatory
            
            // Request tracking
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('requested_at')->useCurrent();
            
            // Approval workflow
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();  // Mandatory if rejected
            
            // After approval - links to correction record
            $table->unsignedBigInteger('correction_record_id')->nullable();
            
            $table->timestamps();
            
            // Indexes for quick lookups
            $table->index(['scan_id', 'status']);
            $table->index(['status', 'requested_at']);
            $table->index('original_tx_hash');
            $table->index('requested_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('correction_requests');
    }
};