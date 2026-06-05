<?php
// filepath: database/migrations/2024_10_10_150000_add_ganache_and_file_metadata_to_scans_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * PURPOSE: Add Ganache blockchain fields + file metadata to scans table
     * 
     * WHY WE NEED THIS:
     * 1. Track detailed Ganache transaction data (gas, addresses, confirmations)
     * 2. Store file metadata (size, MIME type, original filename)
     * 3. Enable comprehensive blockchain audit trail
     * 4. Improve file management and debugging
     */
    public function up(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            
            // ═══════════════════════════════════════════════════════════════
            // SECTION 1: FILE METADATA FIELDS
            // ═══════════════════════════════════════════════════════════════
            // Purpose: Track file information for storage management
            // Why: Enables file validation, storage monitoring, debugging
            // ═══════════════════════════════════════════════════════════════
            
            if (!Schema::hasColumn('scans', 'file_size')) {
                $table->unsignedBigInteger('file_size')
                    ->nullable()
                    ->after('file_path')
                    ->comment('File size in bytes');
                // WHY: Track storage usage, validate uploads, display to users
                // EXAMPLE: 2456789 (bytes) → "2.34 MB"
            }
            
            if (!Schema::hasColumn('scans', 'file_mime_type')) {
                $table->string('file_mime_type', 100)
                    ->nullable()
                    ->after('file_size')
                    ->comment('MIME type (image/jpeg, application/pdf, etc.)');
                // WHY: Validate file types, determine how to display file
                // EXAMPLE: "image/jpeg", "application/pdf"
            }
            
            if (!Schema::hasColumn('scans', 'original_filename')) {
                $table->string('original_filename', 255)
                    ->nullable()
                    ->after('file_mime_type')
                    ->comment('Original uploaded filename');
                // WHY: Preserve original name for user reference
                // EXAMPLE: "John_Birth_Certificate_2023.jpg"
            }
            
            // ═══════════════════════════════════════════════════════════════
            // SECTION 2: GANACHE BLOCKCHAIN TRANSACTION FIELDS
            // ═══════════════════════════════════════════════════════════════
            // Purpose: Store detailed Ganache transaction metadata
            // Why: Enable transaction verification, gas tracking, debugging
            // ═══════════════════════════════════════════════════════════════
            
            if (!Schema::hasColumn('scans', 'blockchain_from_address')) {
                $table->string('blockchain_from_address', 42)
                    ->nullable()
                    ->after('blockchain_tx_hash')
                    ->comment('Sender address (Ganache account)');
                // WHY: Track which Ganache account sent the transaction
                // EXAMPLE: "0x1234567890123456789012345678901234567890"
                // FORMAT: 0x + 40 hexadecimal characters = 42 total
            }
            
            if (!Schema::hasColumn('scans', 'blockchain_to_address')) {
                $table->string('blockchain_to_address', 42)
                    ->nullable()
                    ->after('blockchain_from_address')
                    ->comment('Recipient/contract address');
                // WHY: Track smart contract address or recipient
                // EXAMPLE: "0xABCDEF1234567890ABCDEF1234567890ABCDEF12"
            }
            
            if (!Schema::hasColumn('scans', 'blockchain_gas_used')) {
                $table->unsignedBigInteger('blockchain_gas_used')
                    ->nullable()
                    ->after('blockchain_to_address')
                    ->comment('Actual gas consumed by transaction');
                // WHY: Track transaction cost, optimize future transactions
                // EXAMPLE: 21000 (minimum for simple transfer)
                //          45000 (typical for contract interaction)
            }
            
            if (!Schema::hasColumn('scans', 'blockchain_gas_price')) {
                $table->string('blockchain_gas_price', 30)
                    ->nullable()
                    ->after('blockchain_gas_used')
                    ->comment('Gas price in Wei (smallest ETH unit)');
                // WHY: Calculate total transaction cost
                // EXAMPLE: "20000000000" (20 Gwei)
                // CALCULATION: Total cost = gas_used × gas_price
                // STRING: Because Wei values can be very large (>64-bit int)
            }
            
            if (!Schema::hasColumn('scans', 'blockchain_gas_limit')) {
                $table->unsignedBigInteger('blockchain_gas_limit')
                    ->nullable()
                    ->after('blockchain_gas_price')
                    ->comment('Maximum gas allowed for transaction');
                // WHY: Prevent runaway transactions
                // EXAMPLE: 100000 (set by our code)
                // NOTE: Actual gas_used will be <= gas_limit
            }
            
            if (!Schema::hasColumn('scans', 'blockchain_nonce')) {
                $table->unsignedBigInteger('blockchain_nonce')
                    ->nullable()
                    ->after('blockchain_gas_limit')
                    ->comment('Transaction sequence number for sender');
                // WHY: Prevent transaction replay attacks, track tx order
                // EXAMPLE: 0 (first transaction), 1 (second), etc.
                // CRITICAL: Each account's nonce must increment by 1
            }
            
            if (!Schema::hasColumn('scans', 'blockchain_status_code')) {
                $table->string('blockchain_status_code', 10)
                    ->nullable()
                    ->after('blockchain_nonce')
                    ->comment('Transaction receipt status (0x0=failed, 0x1=success)');
                // WHY: Quick check if transaction succeeded or failed
                // VALUES: "0x0" = Failed, "0x1" = Success
                // USAGE: if ($scan->blockchain_status_code === '0x1') { /* success */ }
            }
            
            if (!Schema::hasColumn('scans', 'blockchain_network_id')) {
                $table->string('blockchain_network_id', 20)
                    ->nullable()
                    ->after('blockchain_status_code')
                    ->comment('Network ID (1=mainnet, 5777=Ganache default)');
                // WHY: Identify which blockchain network (Ganache vs live)
                // EXAMPLE: "5777" (Ganache default)
                //          "1" (Ethereum mainnet - if you go live)
                //          "3" (Ropsten testnet)
            }
            
            if (!Schema::hasColumn('scans', 'blockchain_confirmations')) {
                $table->unsignedInteger('blockchain_confirmations')
                    ->default(0)
                    ->after('blockchain_network_id')
                    ->comment('Number of block confirmations');
                // WHY: Track transaction finality (more confirmations = more secure)
                // EXAMPLE: 0 (pending), 1 (in one block), 12 (generally considered final)
                // GANACHE: Usually instant confirmation (1 block)
            }
            
            if (!Schema::hasColumn('scans', 'blockchain_block_hash')) {
                $table->string('blockchain_block_hash', 66)
                    ->nullable()
                    ->after('blockchain_confirmations')
                    ->comment('Hash of block containing transaction');
                // WHY: Link transaction to specific block, verify inclusion
                // EXAMPLE: "0x1234...abcd" (64 hex chars + 0x prefix = 66 total)
            }
            
            if (!Schema::hasColumn('scans', 'blockchain_transaction_index')) {
                $table->unsignedInteger('blockchain_transaction_index')
                    ->nullable()
                    ->after('blockchain_block_hash')
                    ->comment('Position of transaction within block');
                // WHY: Track transaction order within block
                // EXAMPLE: 0 (first tx in block), 1 (second), etc.
            }
            
            if (!Schema::hasColumn('scans', 'blockchain_input_data')) {
                $table->text('blockchain_input_data')
                    ->nullable()
                    ->after('blockchain_transaction_index')
                    ->comment('Transaction input data (encoded function call)');
                // WHY: Store smart contract function call data
                // EXAMPLE: "0x1234abcd..." (hex-encoded function call + parameters)
                // USAGE: Decode to see what function was called
            }
            
            if (!Schema::hasColumn('scans', 'blockchain_value')) {
                $table->string('blockchain_value', 30)
                    ->default('0')
                    ->after('blockchain_input_data')
                    ->comment('ETH value sent with transaction (in Wei)');
                // WHY: Track if ETH was sent along with transaction
                // EXAMPLE: "0" (no ETH sent - typical for data transactions)
                //          "1000000000000000000" (1 ETH in Wei)
                // STRING: Wei values can exceed 64-bit integer limits
            }
            
            // ═══════════════════════════════════════════════════════════════
            // SECTION 3: DATABASE INDEXES
            // ═══════════════════════════════════════════════════════════════
            // Purpose: Speed up queries on frequently searched columns
            // Why: Faster searches, better performance as data grows
            // ═══════════════════════════════════════════════════════════════
            
            // Index for file path lookups
            $table->index('file_path', 'idx_scans_file_path');
            // WHY: Speed up queries like: WHERE file_path = '...'
            
            // Index for blockchain transaction hash lookups
            $table->index('blockchain_tx_hash', 'idx_scans_blockchain_tx_hash');
            // WHY: Speed up blockchain verification queries
            
            // Index for blockchain status filtering
            $table->index('blockchain_status', 'idx_scans_blockchain_status');
            // WHY: Speed up dashboard queries: WHERE blockchain_status = 'confirmed'
        });
    }

    /**
     * Reverse the migrations.
     * 
     * PURPOSE: Rollback changes if migration fails or needs to be undone
     * 
     * WHY WE NEED THIS:
     * - Testing: Undo migration during development
     * - Errors: Revert if migration causes issues
     * - Maintenance: Clean rollback if design changes
     */
    public function down(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            
            // Drop indexes first (must drop before dropping columns)
            $table->dropIndex('idx_scans_file_path');
            $table->dropIndex('idx_scans_blockchain_tx_hash');
            $table->dropIndex('idx_scans_blockchain_status');
            
            // Drop all added columns in reverse order
            $table->dropColumn([
                // File metadata
                'file_size',
                'file_mime_type',
                'original_filename',
                
                // Ganache blockchain fields
                'blockchain_from_address',
                'blockchain_to_address',
                'blockchain_gas_used',
                'blockchain_gas_price',
                'blockchain_gas_limit',
                'blockchain_nonce',
                'blockchain_status_code',
                'blockchain_network_id',
                'blockchain_confirmations',
                'blockchain_block_hash',
                'blockchain_transaction_index',
                'blockchain_input_data',
                'blockchain_value',
            ]);
        });
    }
};