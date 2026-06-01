<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicalRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'appointment_id',
        'subjective',
        'objective',
        'assessment',
        'plan',
        'ai_draft',
        'icd10_codes',
        'vital_signs',
        'attachments',
        'is_draft',
        'signed_at',
    ];

    protected $casts = [
        'ai_draft' => 'array',
        'icd10_codes' => 'array',
        'vital_signs' => 'array',
        'attachments' => 'array',
        'is_draft' => 'boolean',
        'signed_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }
}
