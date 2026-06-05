<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            $table->text('blockchain_failure_reason')->nullable()->after('blockchain_value');
            $table->index('blockchain_failure_reason');
        });
    }

    public function down(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            $table->dropIndex(['blockchain_failure_reason']);
            $table->dropColumn('blockchain_failure_reason');
        });
    }
};