<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\MedicalRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class MedicalRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var MedicalRecord $record */
        $record = $this->resource;

        return [
            'id' => (string) $record->id,
            'patient_id' => (string) $record->patient_id,
            'doctor_id' => (string) $record->doctor_id,
            'appointment_id' => (string) $record->appointment_id,

            'subjective' => $record->subjective,
            'objective' => $record->objective,
            'assessment' => $record->assessment,
            'plan' => $record->plan,

            'ai_draft' => $record->ai_draft,
            'is_draft' => (bool) $record->is_draft,
            'signed_at' => $record->signed_at?->toISOString(),
            'created_at' => $record->created_at?->toISOString(),
            'updated_at' => $record->updated_at?->toISOString(),
        ];
    }
}
