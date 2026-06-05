<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::table('scans', function (Blueprint $table) {
           
            $table->boolean('blockchain_enabled')
                  ->default(true) 
                  ->after('document_hash')
                  ->comment('Flag indicating if blockchain anchoring is enabled for this document');
        });
    }

    
    public function down(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            $table->dropColumn('blockchain_enabled');
        });
    }
};