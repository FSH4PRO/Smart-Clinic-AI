<?php

namespace App\Http\Resources;

use App\Http\Resources\PatientResource;
use App\Http\Resources\DoctorResource;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'doctor_id' => $this->doctor_id,
            'clinic_id' => $this->clinic_id,
            'branch_id' => $this->branch_id,

            'appointment_date' => $this->appointment_date?->format('Y-m-d'),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,

            'type' => $this->type?->value,
            'status' => $this->status?->value,
            'booking_source' => $this->booking_source?->value,

            'notes' => $this->notes,

            'triage_score' => $this->triage_score,
            'no_show_risk' => $this->no_show_risk,

            'cancelled_at' => optional($this->cancelled_at)?->toISOString(),
            'cancellation_reason' => $this->cancellation_reason,

            'created_at' => optional($this->created_at)?->toISOString(),
            'updated_at' => optional($this->updated_at)?->toISOString(),

            'patient' => $this->whenLoaded('patient', fn() => new PatientResource($this->patient)),
            'doctor' => $this->whenLoaded('doctor', fn() => new DoctorResource($this->doctor)),
        ];
    }
}
