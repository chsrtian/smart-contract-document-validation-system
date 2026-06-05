<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PetitionAttachment extends Model
{
    use HasFactory;

    protected $table = 'petition_attachments';

    protected $fillable = [
        'petition_id',
        'file_path',
        'original_filename',
        'file_type',
        'mime_type',
        'file_size',
        'description',
        'uploaded_by',
    ];

    const FILE_TYPES = [
        'supporting_affidavit' => 'Supporting Affidavit',
        'government_id' => 'Government-Issued ID',
        'baptismal_certificate' => 'Baptismal Certificate',
        'school_record' => 'School Record',
        'nso_copy' => 'NSO/PSA Certified Copy',
        'medical_record' => 'Medical Record',
        'cedula' => 'Community Tax Certificate (Cedula)',
        'other' => 'Other Supporting Document',
    ];

    public function petition(): BelongsTo
    {
        return $this->belongsTo(LegalCorrectionPetition::class, 'petition_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getFileTypeLabelAttribute(): string
    {
        return self::FILE_TYPES[$this->file_type] ?? 'Document';
    }

    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size ?? 0;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }
}
