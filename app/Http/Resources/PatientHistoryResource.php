<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PatientHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'first_name'    => $this->first_name,
            'last_name'     => $this->last_name,
            'date_of_birth' => $this->date_of_birth,
            'gender'        => $this->gender,
            // Map the appointments into a unified chronological timeline
            'timeline'      => $this->whenLoaded('appointments', function () {
                return $this->appointments->map(function ($appointment) {
                    return [
                        'appointment_id' => $appointment->id,
                        'date'           => $appointment->created_at?->toIso8601String(),
                        'doctor_id'      => $appointment->doctor_id,
                        'status'         => $appointment->status,
                        
                        // Include the AI Triage summary if completed
                        'triage' => $appointment->aiTriageSessions
                            ?->whereNotNull('completed_at')
                            ->first()?->triage_result,
                            
                        // Include the Doctor's SOAP note if signed/finalized
                        'medical_record' => $appointment->medicalRecord && !$appointment->medicalRecord->is_draft
                            ? [
                                'subjective' => $appointment->medicalRecord->subjective,
                                'objective'  => $appointment->medicalRecord->objective,
                                'assessment' => $appointment->medicalRecord->assessment,
                                'plan'       => $appointment->medicalRecord->plan,
                                'signed_at'  => $appointment->medicalRecord->signed_at?->toIso8601String(),
                            ]
                            : null,
                    ];
                });
            }),
        ];
    }
}