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
            // Add title column if it doesn't exist
            if (!Schema::hasColumn('scans', 'title')) {
                $table->string('title')->after('document_type');
            }
            
            // Add description column if it doesn't exist
            if (!Schema::hasColumn('scans', 'description')) {
                $table->text('description')->nullable()->after('title');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            $table->dropColumn(['title', 'description']);
        });
    }
};