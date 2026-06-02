<?php

namespace App\Models;

use App\Enums\BloodType;
use App\Enums\PatientGender;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    use HasFactory ,HasUuids ;

    protected $fillable = [
        'user_id',
        'date_of_birth',
        'gender',
        'blood_type',
        'national_id',
        'emergency_contact_name',
        'emergency_contact_phone',
        'allergies',
        'chronic_conditions',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'allergies' => 'array',
        'chronic_conditions' => 'array',
        'gender' => PatientGender::class,
        'blood_type' => BloodType::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function medicalRecords(): HasMany
    {
        return $this->hasMany(MedicalRecord::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
