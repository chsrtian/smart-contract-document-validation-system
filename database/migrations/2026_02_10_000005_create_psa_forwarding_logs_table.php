<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('psa_forwarding_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('petition_id')->constrained('legal_correction_petitions')->cascadeOnDelete();
            $table->string('forwarding_reference')->unique();
            $table->string('forwarding_status')->default('pending'); // pending, transmitted, acknowledged, completed
            $table->timestamp('forwarded_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('psa_remarks')->nullable();
            $table->text('transmittal_details')->nullable();
            $table->foreignId('forwarded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('petition_id');
            $table->index('forwarding_reference');
            $table->index('forwarding_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('psa_forwarding_logs');
    }
};
