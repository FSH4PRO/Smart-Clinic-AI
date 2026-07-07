<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property string $id
 * @property string $appointment_id
 * @property string $patient_id
 * @property string $doctor_id
 * @property string $medication_name
 * @property string $dosage
 * @property string $frequency
 * @property string $duration
 * @property string|null $instructions
 * @property int $refills
 * @property \Illuminate\Support\Carbon|null $issued_at
 */
final class PrescriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'medical_record_id' => $this->medical_record_id, // 
            'patient_id' => $this->patient_id,               // 
            'doctor_id' => $this->doctor_id,                 // 
            'status' => $this->status,                       // 
            'notes' => $this->notes,                         // 
            'items' => $this->items->map(function($item) {   // Nested list of items 
                return [
                    'id' => $item->id,
                    'drug_name' => $item->drug_name,
                    'dosage' => $item->dosage,
                    'frequency' => $item->frequency,
                    'duration_days' => $item->duration_days,
                    'instructions' => $item->instructions,
                    'ai_interaction_flag' => $item->ai_interaction_flag,
                    'ai_interaction_detail' => $item->ai_interaction_detail,
                ];
            }),
            'dispensed_at' => $this->dispensed_at,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}