<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('petition_field_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('petition_id')->constrained('legal_correction_petitions')->cascadeOnDelete();
            $table->string('field_name');
            $table->string('field_label')->nullable();
            $table->text('current_value')->nullable();
            $table->text('proposed_value');
            $table->text('justification')->nullable();
            $table->timestamps();

            $table->index('petition_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petition_field_changes');
    }
};
