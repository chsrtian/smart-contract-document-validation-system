<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Clean the data first (remove double-encoding)
        $scans = DB::table('scans')->whereNotNull('extracted_fields')->get();
        
        foreach ($scans as $scan) {
            $fields = $scan->extracted_fields;
            
            // Remove outer quotes if present
            if (substr($fields, 0, 2) === '""' && substr($fields, -2) === '""') {
                $fields = substr($fields, 1, -1); // Remove first and last quote
                $fields = str_replace('\\"', '"', $fields); // Unescape quotes
            }
            
            // Validate it's proper JSON
            $decoded = json_decode($fields, true);
            if (is_array($decoded)) {
                DB::table('scans')
                    ->where('id', $scan->id)
                    ->update(['extracted_fields' => $fields]);
            }
        }
        
        // Step 2: Change column type to JSON
        Schema::table('scans', function (Blueprint $table) {
            $table->json('extracted_fields')->nullable()->change();
            $table->json('ocr_data')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            $table->longText('extracted_fields')->nullable()->change();
            $table->longText('ocr_data')->nullable()->change();
        });
    }
};