<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('petition_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('petition_id')->constrained('legal_correction_petitions')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('original_filename');
            $table->string('file_type'); // supporting_affidavit, government_id, baptismal_certificate, school_record, nso_copy, other
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index('petition_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petition_attachments');
    }
};
