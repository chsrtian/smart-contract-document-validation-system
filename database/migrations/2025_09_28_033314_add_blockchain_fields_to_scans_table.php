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
            // Add blockchain-related fields
            $table->string('document_hash')->nullable()->after('file_hash');
            $table->string('blockchain_tx_hash')->nullable()->after('document_hash');
            $table->string('blockchain_block_number')->nullable()->after('blockchain_tx_hash');
            $table->enum('blockchain_status', ['pending', 'confirmed', 'failed'])->nullable()->after('blockchain_block_number');
            $table->timestamp('blockchain_submitted_at')->nullable()->after('blockchain_status');
            $table->timestamp('blockchain_confirmed_at')->nullable()->after('blockchain_submitted_at');
            $table->json('blockchain_metadata')->nullable()->after('blockchain_confirmed_at');
            
            // Add index for blockchain queries
            $table->index(['blockchain_status', 'blockchain_submitted_at'], 'idx_blockchain_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            $table->dropIndex('idx_blockchain_status');
            $table->dropColumn([
                'document_hash',
                'blockchain_tx_hash', 
                'blockchain_block_number',
                'blockchain_status',
                'blockchain_submitted_at',
                'blockchain_confirmed_at',
                'blockchain_metadata'
            ]);
        });
    }
};