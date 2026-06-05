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
        Schema::table('scans', function (Blueprint $table) {
            // Add missing user columns if they don't exist
            if (!Schema::hasColumn('scans', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('processed_by');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            }
            
            if (!Schema::hasColumn('scans', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('user_id');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            }
            
            // Add indexes for better performance
            $table->index(['user_id', 'created_at']);
            $table->index(['created_by', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['created_by']);
            $table->dropColumn(['user_id', 'created_by']);
        });
    }
};