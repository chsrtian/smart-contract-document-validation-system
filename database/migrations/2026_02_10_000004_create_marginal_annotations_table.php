<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marginal_annotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('petition_id')->constrained('legal_correction_petitions')->cascadeOnDelete();
            $table->foreignId('scan_id')->constrained('scans')->cascadeOnDelete();
            $table->text('annotation_text');
            $table->string('annotation_type')->default('correction'); // correction, name_change, gender_change, birthdate_change
            $table->string('legal_reference')->nullable(); // e.g. "RA 9048, Section 1"
            $table->string('lcro_decision_number')->nullable();
            $table->date('annotation_date')->nullable();
            $table->foreignId('annotated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('petition_id');
            $table->index('scan_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marginal_annotations');
    }
};
