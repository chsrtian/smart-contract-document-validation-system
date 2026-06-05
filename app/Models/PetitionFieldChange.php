<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PetitionFieldChange extends Model
{
    use HasFactory;

    protected $table = 'petition_field_changes';

    protected $fillable = [
        'petition_id',
        'field_name',
        'field_label',
        'current_value',
        'proposed_value',
        'justification',
    ];

    public function petition(): BelongsTo
    {
        return $this->belongsTo(LegalCorrectionPetition::class, 'petition_id');
    }

    public function getFieldLabelAttribute($value): string
    {
        return $value ?: ucwords(str_replace('_', ' ', $this->field_name));
    }
}
