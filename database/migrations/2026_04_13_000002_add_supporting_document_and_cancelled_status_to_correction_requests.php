<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('correction_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('correction_requests', 'supporting_document_path')) {
                $table->string('supporting_document_path')->nullable()->after('reason');
            }
        });

        $driver = DB::getDriverName();

        // Extend enum safely on MySQL/MariaDB without dropping existing values.
        if ($driver === 'mysql') {
            DB::statement(
                "ALTER TABLE correction_requests MODIFY COLUMN status ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending'"
            );
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive to avoid losing persisted correction metadata.
    }
};
