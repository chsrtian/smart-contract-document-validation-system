<?php

namespace App\Services;

use App\Models\LegalCorrectionPetition;
use App\Models\MarginalAnnotation;
use App\Models\PetitionAttachment;
use App\Models\PetitionFieldChange;
use App\Models\PsaForwardingLog;
use App\Models\Scan;
use App\Services\AuditLogService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LegalCorrectionService
{
    /**
     * Create a new legal correction petition.
     */
    public function createPetition(array $data, int $userId): LegalCorrectionPetition
    {
        return DB::transaction(function () use ($data, $userId) {
            $petition = LegalCorrectionPetition::create([
                'petition_number' => LegalCorrectionPetition::generatePetitionNumber(),
                'scan_id' => $data['scan_id'],
                'petition_type' => $data['petition_type'],
                'legal_basis' => LegalCorrectionPetition::LEGAL_BASIS_MAP[$data['petition_type']] ?? 'N/A',
                'petitioner_name' => $data['petitioner_name'],
                'petitioner_address' => $data['petitioner_address'] ?? null,
                'petitioner_relationship' => $data['petitioner_relationship'] ?? null,
                'reason' => $data['reason'] ?? null,
                'supporting_affidavit' => $data['supporting_affidavit'] ?? null,
                'status' => LegalCorrectionPetition::STATUS_DRAFT,
                'created_by' => $userId,
            ]);

            // Create field changes
            if (!empty($data['field_changes'])) {
                foreach ($data['field_changes'] as $change) {
                    PetitionFieldChange::create([
                        'petition_id' => $petition->id,
                        'field_name' => $change['field_name'],
                        'field_label' => $change['field_label'] ?? null,
                        'current_value' => $change['current_value'] ?? null,
                        'proposed_value' => $change['proposed_value'],
                        'justification' => $change['justification'] ?? null,
                    ]);
                }
            }

            Log::info('Legal correction petition created', [
                'petition_id' => $petition->id,
                'petition_number' => $petition->petition_number,
                'scan_id' => $data['scan_id'],
                'created_by' => $userId,
            ]);

            return $petition;
        });
    }

    /**
     * Upload attachments to a petition.
     */
    public function uploadAttachments(LegalCorrectionPetition $petition, array $files, int $userId): array
    {
        $uploaded = [];

        foreach ($files as $fileData) {
            /** @var UploadedFile $file */
            $file = $fileData['file'];
            $fileType = $fileData['file_type'] ?? 'other';
            $description = $fileData['description'] ?? null;

            $path = $file->store('petition-attachments/' . $petition->id, 'public');

            $attachment = PetitionAttachment::create([
                'petition_id' => $petition->id,
                'file_path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'file_type' => $fileType,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'description' => $description,
                'uploaded_by' => $userId,
            ]);

            $uploaded[] = $attachment;
        }

        return $uploaded;
    }

    /**
     * Submit petition for approval (draft → pending_approval).
     */
    public function submitForApproval(LegalCorrectionPetition $petition): bool
    {
        if (!$petition->canBeSubmitted()) {
            return false;
        }

        $petition->update(['status' => LegalCorrectionPetition::STATUS_PENDING]);

        Log::info('Legal correction petition submitted for approval', [
            'petition_id' => $petition->id,
            'petition_number' => $petition->petition_number,
        ]);

        return true;
    }

    /**
     * Approve a petition (admin action).
     */
    public function approvePetition(LegalCorrectionPetition $petition, int $adminId, ?string $notes = null): bool
    {
        if (!$petition->canBeApproved()) {
            return false;
        }

        return DB::transaction(function () use ($petition, $adminId, $notes) {
            $petition->update([
                'status' => LegalCorrectionPetition::STATUS_APPROVED,
                'approved_by' => $adminId,
                'approved_at' => now(),
                'admin_notes' => $notes,
            ]);

            // Generate marginal annotation
            $this->generateMarginalAnnotation($petition, $adminId);

            Log::info('Legal correction petition approved', [
                'petition_id' => $petition->id,
                'petition_number' => $petition->petition_number,
                'approved_by' => $adminId,
            ]);

            return true;
        });
    }

    /**
     * Reject a petition (admin action).
     */
    public function rejectPetition(LegalCorrectionPetition $petition, int $adminId, string $reason, ?string $notes = null): bool
    {
        if (!$petition->canBeApproved()) {
            return false;
        }

        $petition->update([
            'status' => LegalCorrectionPetition::STATUS_REJECTED,
            'rejected_by' => $adminId,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
            'admin_notes' => $notes,
        ]);

        Log::info('Legal correction petition rejected', [
            'petition_id' => $petition->id,
            'petition_number' => $petition->petition_number,
            'rejected_by' => $adminId,
            'reason' => $reason,
        ]);

        return true;
    }

    /**
     * Generate an automatic marginal annotation upon approval.
     */
    public function generateMarginalAnnotation(LegalCorrectionPetition $petition, int $adminId): MarginalAnnotation
    {
        $scan = $petition->scan;
        $fieldChanges = $petition->fieldChanges;

        // Build annotation text
        $annotationType = match ($petition->petition_type) {
            LegalCorrectionPetition::TYPE_CLERICAL => 'correction',
            LegalCorrectionPetition::TYPE_FIRST_NAME => 'name_change',
            LegalCorrectionPetition::TYPE_GENDER => 'gender_change',
            LegalCorrectionPetition::TYPE_BIRTHDATE => 'birthdate_change',
            default => 'correction',
        };

        $legalRef = LegalCorrectionPetition::LEGAL_BASIS_MAP[$petition->petition_type] ?? 'N/A';
        $decisionNumber = 'LCRO-' . date('Y') . '-' . str_pad($petition->id, 5, '0', STR_PAD_LEFT);

        $changeLines = [];
        foreach ($fieldChanges as $change) {
            $changeLines[] = sprintf(
                '"%s" is hereby corrected from "%s" to "%s"',
                $change->field_label ?: ucwords(str_replace('_', ' ', $change->field_name)),
                $change->current_value ?? '(blank)',
                $change->proposed_value
            );
        }

        $changesText = implode('; ', $changeLines);

        $annotationText = sprintf(
            "By virtue of %s, pursuant to %s Decision No. %s dated %s, the following correction(s) are hereby ordered: %s. " .
            "Petition No. %s filed by %s (%s). This annotation is entered in the margin of the civil registry document.",
            $legalRef,
            'LCRO',
            $decisionNumber,
            now()->format('F d, Y'),
            $changesText,
            $petition->petition_number,
            $petition->petitioner_name,
            LegalCorrectionPetition::RELATIONSHIP_TYPES[$petition->petitioner_relationship] ?? $petition->petitioner_relationship
        );

        return MarginalAnnotation::create([
            'petition_id' => $petition->id,
            'scan_id' => $scan->id,
            'annotation_text' => $annotationText,
            'annotation_type' => $annotationType,
            'legal_reference' => $legalRef,
            'lcro_decision_number' => $decisionNumber,
            'annotation_date' => now()->toDateString(),
            'annotated_by' => $adminId,
        ]);
    }

    /**
     * Simulate forwarding the approved petition to PSA.
     */
    public function forwardToPsa(LegalCorrectionPetition $petition, int $adminId): PsaForwardingLog
    {
        if (!$petition->canBeForwarded()) {
            throw new \RuntimeException('Petition cannot be forwarded. It must be approved first.');
        }

        return DB::transaction(function () use ($petition, $adminId) {
            $petition->update([
                'status' => LegalCorrectionPetition::STATUS_FORWARDED,
            ]);

            $fieldChanges = $petition->fieldChanges;
            $changesSummary = $fieldChanges->map(function ($c) {
                return sprintf('%s: "%s" → "%s"', $c->field_label, $c->current_value ?? '(blank)', $c->proposed_value);
            })->implode('; ');

            $transmittalDetails = sprintf(
                "TRANSMITTAL TO PSA CIVIL REGISTRY SERVICE\n" .
                "-------------------------------------------\n" .
                "Petition No.: %s\n" .
                "Legal Basis: %s\n" .
                "Petition Type: %s\n" .
                "Document: %s (ID: %s)\n" .
                "Petitioner: %s\n" .
                "Corrections: %s\n" .
                "LCRO Decision: %s\n" .
                "Date Forwarded: %s\n" .
                "Forwarded By: %s\n" .
                "-------------------------------------------\n" .
                "Note: This is a simulated PSA transmittal for the Local Civil Registry System.",
                $petition->petition_number,
                $petition->legal_basis,
                $petition->petition_type_label,
                $petition->scan->document_type ?? 'N/A',
                $petition->scan->document_id ?? $petition->scan_id,
                $petition->petitioner_name,
                $changesSummary,
                $petition->annotations->first()->lcro_decision_number ?? 'N/A',
                now()->format('F d, Y h:i A'),
                Auth::user()->name ?? 'Admin'
            );

            $log = PsaForwardingLog::create([
                'petition_id' => $petition->id,
                'forwarding_reference' => PsaForwardingLog::generateForwardingReference(),
                'forwarding_status' => PsaForwardingLog::STATUS_TRANSMITTED,
                'forwarded_at' => now(),
                'transmittal_details' => $transmittalDetails,
                'forwarded_by' => $adminId,
            ]);

            Log::info('Legal correction petition forwarded to PSA (simulated)', [
                'petition_id' => $petition->id,
                'petition_number' => $petition->petition_number,
                'forwarding_reference' => $log->forwarding_reference,
                'forwarded_by' => $adminId,
            ]);

            return $log;
        });
    }

    /**
     * Get petition statistics for dashboards.
     */
    public function getStats(?int $userId = null): array
    {
        $query = LegalCorrectionPetition::query();

        if ($userId) {
            $query->where('created_by', $userId);
        }

        return [
            'total' => (clone $query)->count(),
            'draft' => (clone $query)->where('status', LegalCorrectionPetition::STATUS_DRAFT)->count(),
            // Keys match view expectations (index.blade.php uses pending_approval and forwarded_to_psa)
            'pending_approval' => (clone $query)->where('status', LegalCorrectionPetition::STATUS_PENDING)->count(),
            'approved' => (clone $query)->where('status', LegalCorrectionPetition::STATUS_APPROVED)->count(),
            'rejected' => (clone $query)->where('status', LegalCorrectionPetition::STATUS_REJECTED)->count(),
            'forwarded_to_psa' => (clone $query)->where('status', LegalCorrectionPetition::STATUS_FORWARDED)->count(),
        ];
    }
}
