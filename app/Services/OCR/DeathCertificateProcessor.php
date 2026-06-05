<?php
// filepath: app\Services\OCR\DeathCertificateProcessor.php

namespace App\Services\OCR;

class DeathCertificateProcessor extends BaseOCRProcessor
{
    protected function extractStructuredFields($rawText, $documentConfig)
    {
        $fields = [];
        
        // Extract deceased person's name
        $namePatterns = ['NAME OF DECEASED', 'PANGALAN NG NAMATAY', 'DECEASED'];
        $fields['deceased_first_name'] = $this->findFieldValue($rawText, $namePatterns, 'name');
        
        // Extract death date
        $datePatterns = ['DATE OF DEATH', 'PETSA NG KAMATAYAN'];
        $dateComponents = $this->findFieldValue($rawText, $datePatterns, 'date');
        if ($dateComponents) {
            $fields['death_date_day'] = $dateComponents['day'];
            $fields['death_date_month'] = $dateComponents['month'];
            $fields['death_date_year'] = $dateComponents['year'];
        }
        
        // Extract place of death
        $placePatterns = ['PLACE OF DEATH', 'LUGAR NG KAMATAYAN'];
        $fields['death_place_city'] = $this->findFieldValue($rawText, $placePatterns);
        
        // Extract cause of death
        $causePatterns = ['CAUSE OF DEATH', 'DAHILAN NG KAMATAYAN', 'IMMEDIATE CAUSE'];
        $fields['cause_of_death'] = $this->findFieldValue($rawText, $causePatterns);
        
        // Extract age
        if (preg_match('/AGE\s*:?\s*(\d+)/i', $rawText, $matches)) {
            $fields['deceased_age'] = $matches[1];
        }
        
        // Extract sex
        if (preg_match('/SEX\s*:?\s*(MALE|FEMALE)/i', $rawText, $matches)) {
            $fields['deceased_sex'] = ucfirst(strtolower($matches[1]));
        }
        
        return array_filter($fields); // Remove empty values
    }
}