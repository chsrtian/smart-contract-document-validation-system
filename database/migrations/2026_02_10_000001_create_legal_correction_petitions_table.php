<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_correction_petitions', function (Blueprint $table) {
            $table->id();
            $table->string('petition_number')->unique();
            $table->foreignId('scan_id')->constrained('scans')->cascadeOnDelete();
            $table->string('petition_type'); // ra_9048_clerical, ra_9048_first_name, ra_10172_gender, ra_10172_birthdate
            $table->string('legal_basis'); // RA 9048, RA 10172
            $table->string('petitioner_name');
            $table->text('petitioner_address')->nullable();
            $table->string('petitioner_relationship')->nullable(); // self, parent, guardian, authorized_representative
            $table->text('reason')->nullable();
            $table->text('supporting_affidavit')->nullable();
            $table->string('status')->default('draft'); // draft, pending_approval, approved, rejected, forwarded_to_psa
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            $table->index('petition_number');
            $table->index('status');
            $table->index('petition_type');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_correction_petitions');
    }
};
