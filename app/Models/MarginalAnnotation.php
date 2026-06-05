<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarginalAnnotation extends Model
{
    use HasFactory;

    protected $table = 'marginal_annotations';

    protected $fillable = [
        'petition_id',
        'scan_id',
        'annotation_text',
        'annotation_type',
        'legal_reference',
        'lcro_decision_number',
        'annotation_date',
        'annotated_by',
    ];

    protected $casts = [
        'annotation_date' => 'date',
    ];

    const ANNOTATION_TYPES = [
        'correction' => 'Clerical Correction',
        'name_change' => 'Change of First Name',
        'gender_change' => 'Gender/Sex Correction',
        'birthdate_change' => 'Day/Month of Birth Correction',
    ];

    public function petition(): BelongsTo
    {
        return $this->belongsTo(LegalCorrectionPetition::class, 'petition_id');
    }

    public function scan(): BelongsTo
    {
        return $this->belongsTo(Scan::class, 'scan_id');
    }

    public function annotator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'annotated_by');
    }

    public function getAnnotationTypeLabelAttribute(): string
    {
        return self::ANNOTATION_TYPES[$this->annotation_type] ?? 'Annotation';
    }
}
