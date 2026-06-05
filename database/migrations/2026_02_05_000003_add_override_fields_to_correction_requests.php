<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('correction_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('override_by')->nullable()->after('rejection_reason');
            $table->timestamp('override_at')->nullable()->after('override_by');
            $table->text('override_justification')->nullable()->after('override_at');
            $table->enum('override_type', ['force_approve', 'force_reject'])->nullable()->after('override_justification');
            
            // Indexes
            $table->index('override_by');
            $table->index('override_at');
            
            // Foreign key
            $table->foreign('override_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('correction_requests', function (Blueprint $table) {
            $table->dropForeign(['override_by']);
            $table->dropIndex(['override_by']);
            $table->dropIndex(['override_at']);
            $table->dropColumn(['override_by', 'override_at', 'override_justification', 'override_type']);
        });
    }
};