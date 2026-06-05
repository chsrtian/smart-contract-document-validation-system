<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $expandedDocumentTypes = [
        'birth_certificate',
        'death_certificate',
        'marriage_certificate',
        'admission_of_paternity',
        'ausf',
        'legitimation',
        'affidavit_of_reappearance',
        'marriage_settlement',
        'parental_authorization_ai',
        'late_registration',
        'supplemental_report',
        'certificate_of_foundling',
        'adoption_document',
        'judicial_correction_rule_108',
        'annulment_or_nullity',
        'recognition_of_foreign_divorce',
        'marriage_license',
        'certificate_legal_capacity_to_marry',
        'cenomar',
        'affidavit',
        'court_document',
        'contract',
        'other',
    ];

    private array $legacyDocumentTypes = [
        'birth_certificate',
        'death_certificate',
        'marriage_certificate',
        'cenomar',
        'affidavit',
        'court_document',
        'contract',
        'other',
    ];

    public function up(): void
    {
        Schema::table('scans', function (Blueprint $table) {
            if (!Schema::hasColumn('scans', 'processing_mode')) {
                $table->enum('processing_mode', ['ocr', 'manual'])
                    ->default('ocr')
                    ->after('document_type');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            $enumValues = "'" . implode("','", $this->expandedDocumentTypes) . "'";
            DB::statement("ALTER TABLE scans MODIFY document_type ENUM({$enumValues}) NOT NULL");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            $enumValues = "'" . implode("','", $this->legacyDocumentTypes) . "'";
            DB::statement("ALTER TABLE scans MODIFY document_type ENUM({$enumValues}) NOT NULL");
        }

        Schema::table('scans', function (Blueprint $table) {
            if (Schema::hasColumn('scans', 'processing_mode')) {
                $table->dropColumn('processing_mode');
            }
        });
    }
};
