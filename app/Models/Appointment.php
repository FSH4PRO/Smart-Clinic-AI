<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Enums\BookingSource;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Appointment extends Model
{
    use HasFactory ,HasUuids;

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'clinic_id',
        'branch_id',
        'appointment_date',
        'start_time',
        'end_time',
        'type',
        'status',
        'booking_source',
        'chief_complaint',
        'triage_score',
        'no_show_risk',
        'notes',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'start_time' => 'time',
        'end_time' => 'time',
        'cancelled_at' => 'datetime',
        'triage_score' => 'integer',
        'no_show_risk' => 'decimal:2',
        'type' => AppointmentType::class,
        'status' => AppointmentStatus::class,
        'booking_source' => BookingSource::class,
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(ClinicBranch::class, 'branch_id');
    }

    public function aiTriageSession(): HasOne
    {
        return $this->hasOne(AiTriageSession::class);
    }

    public function medicalRecord(): HasOne
    {
        return $this->hasOne(MedicalRecord::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }
}
