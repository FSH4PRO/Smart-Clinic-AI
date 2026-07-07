<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Doctor;
use App\Models\MedicalRecord;
use App\Models\Appointment;
use Illuminate\Support\Facades\DB;

final class MedicalRecordCreateService
{
    public function createForDoctor(array $data): MedicalRecord
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $doctor = $user?->doctor;

        if (! $doctor instanceof Doctor) {
            // Let FormRequest handle typical authorization; keep a safety net.
            abort(403, 'Unauthorized: doctor profile missing.');
        }

        return DB::transaction(function () use ($data, $doctor): MedicalRecord {
            /** @var Appointment $appointment */
            $appointment = Appointment::query()
                ->select(['id', 'patient_id', 'doctor_id'])
                ->findOrFail($data['appointment_id']);

            // Enforce doctor ownership at service-level too (best practice defense in depth).
            if ((string) $appointment->doctor_id !== (string) $doctor->id) {
                abort(403, 'Unauthorized action.');
            }

            $record = MedicalRecord::query()->create([
                'patient_id' => $appointment->patient_id,
                'doctor_id' => $doctor->id,
                'appointment_id' => $appointment->id,

                'subjective' => $data['subjective'] ?? null,
                'objective' => $data['objective'] ?? null,
                'assessment' => $data['assessment'] ?? null,
                'plan' => $data['plan'] ?? null,

                'ai_draft' => [],
                'is_draft' => true,
                'icd10_codes' => [],
                'vital_signs' => [],
                'attachments' => [],

                'signed_at' => null,
            ]);

            return $record->load(['patient', 'doctor', 'appointment']);
        });
    }
}
