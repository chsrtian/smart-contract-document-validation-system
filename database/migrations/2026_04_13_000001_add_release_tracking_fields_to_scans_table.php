<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            // Add release-tracking columns without touching existing data.
            if (!Schema::hasColumn('scans', 'status')) {
                $table->string('status', 50)->nullable()->after('verification_status');
            }

            if (!Schema::hasColumn('scans', 'released')) {
                $table->boolean('released')->default(false)->after('status');
            }

            if (!Schema::hasColumn('scans', 'released_at')) {
                $table->timestamp('released_at')->nullable()->after('released');
            }

            if (!Schema::hasColumn('scans', 'released_by')) {
                $table->unsignedBigInteger('released_by')->nullable()->after('released_at');
                $table->index('released_by', 'idx_scans_released_by');
            }

            if (!Schema::hasColumn('scans', 'released_to')) {
                $table->string('released_to')->nullable()->after('released_by');
            }

            if (!Schema::hasColumn('scans', 'copies_printed')) {
                $table->unsignedInteger('copies_printed')->default(0)->after('released_to');
            }
        });
    }

    public function down(): void
    {
        // Intentionally non-destructive to preserve release audit data.
    }
};
