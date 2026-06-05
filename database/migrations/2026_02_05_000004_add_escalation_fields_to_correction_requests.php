<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('correction_requests', function (Blueprint $table) {
            $table->timestamp('escalated_at')->nullable()->after('override_type');
            $table->text('escalated_reason')->nullable()->after('escalated_at');
            $table->unsignedBigInteger('assigned_to')->nullable()->after('escalated_reason');
            $table->enum('escalation_status', ['pending', 'assigned', 'resolved'])->nullable()->after('assigned_to');
            
            // Indexes
            $table->index('escalated_at');
            $table->index('assigned_to');
            $table->index('escalation_status');
            
            // Foreign key
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('correction_requests', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
            $table->dropIndex(['escalated_at']);
            $table->dropIndex(['assigned_to']);
            $table->dropIndex(['escalation_status']);
            $table->dropColumn(['escalated_at', 'escalated_reason', 'assigned_to', 'escalation_status']);
        });
    }
};