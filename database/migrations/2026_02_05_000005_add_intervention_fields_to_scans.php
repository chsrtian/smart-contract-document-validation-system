<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            // Lock fields
            $table->boolean('locked')->default(false)->after('blockchain_value');
            $table->unsignedBigInteger('locked_by')->nullable()->after('locked');
            $table->timestamp('locked_at')->nullable()->after('locked_by');
            $table->text('lock_reason')->nullable()->after('locked_at');
            
            // Archive fields
            $table->boolean('archived')->default(false)->after('lock_reason');
            $table->unsignedBigInteger('archived_by')->nullable()->after('archived');
            $table->timestamp('archived_at')->nullable()->after('archived_by');
            $table->text('archive_reason')->nullable()->after('archived_at');
            
            // Flag fields
            $table->boolean('flagged')->default(false)->after('archive_reason');
            $table->unsignedBigInteger('flagged_by')->nullable()->after('flagged');
            $table->timestamp('flagged_at')->nullable()->after('flagged_by');
            $table->text('flag_notes')->nullable()->after('flagged_at');
            
            // Indexes
            $table->index('locked');
            $table->index('archived');
            $table->index('flagged');
            $table->index('locked_by');
            $table->index('archived_by');
            $table->index('flagged_by');
            
            // Foreign keys
            $table->foreign('locked_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('archived_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('flagged_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            $table->dropForeign(['locked_by']);
            $table->dropForeign(['archived_by']);
            $table->dropForeign(['flagged_by']);
            
            $table->dropIndex(['locked']);
            $table->dropIndex(['archived']);
            $table->dropIndex(['flagged']);
            $table->dropIndex(['locked_by']);
            $table->dropIndex(['archived_by']);
            $table->dropIndex(['flagged_by']);
            
            $table->dropColumn([
                'locked', 'locked_by', 'locked_at', 'lock_reason',
                'archived', 'archived_by', 'archived_at', 'archive_reason',
                'flagged', 'flagged_by', 'flagged_at', 'flag_notes'
            ]);
        });
    }
};