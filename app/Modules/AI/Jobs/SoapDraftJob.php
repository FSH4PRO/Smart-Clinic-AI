<?php

declare(strict_types=1);

namespace App\Modules\AI\Jobs;

use App\Enums\AiFeature;
use App\Modules\AI\DTO\SoapDraftDTO;
use App\Modules\AI\Services\GeminiService;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\AiTriageSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Throwable;

final class SoapDraftJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $medicalRecordId
    ) {}

    public function handle(GeminiService $geminiService): void
    {
        $medicalRecord = MedicalRecord::query()
            ->with([
                'patient',
                'doctor',
                'appointment',
            ])
            ->findOrFail($this->medicalRecordId);

        if (! $medicalRecord->is_draft) {
            return;
        }

        if (! empty($medicalRecord->ai_draft)) {
            // Never overwrite existing AI draft.
            return;
        }

        /** @var Patient $patient */
        $patient = $medicalRecord->patient;
        /** @var Doctor $doctor */
        $doctor = $medicalRecord->doctor;
        /** @var Appointment $appointment */
        $appointment = $medicalRecord->appointment;

        $triageSession = AiTriageSession::query()
            ->where('appointment_id', $medicalRecord->appointment_id)
            ->first();

        $triageMessages = $triageSession?->messages ?? [];
        $triageExtractedSymptoms = $triageSession?->extracted_symptoms ?? [];
        $triageResult = $triageSession?->triage_result ?? [];

        $systemInstruction = <<<TXT
You are a clinical documentation assistant. Draft a medically sound SOAP note.

Rules:
- Output STRICT JSON only (no markdown, no commentary).
- Must be safe: do not invent allergies/conditions beyond provided data.
- Use concise clinical language.
- Ensure JSON fields are exactly as requested.
TXT;

        $userPrompt = $this->buildPromptPayload(
            medicalRecord: $medicalRecord,
            patient: $patient,
            doctor: $doctor,
            appointment: $appointment,
            triageMessages: $triageMessages,
            triageExtractedSymptoms: $triageExtractedSymptoms,
            triageResult: $triageResult
        );

        try {
            $featureOutput = $geminiService->generateJson(
                feature: AiFeature::SOAP_DRAFT,
                clinicId: (string) $doctor->clinic_id,
                systemInstruction: $systemInstruction,
                userPrompt: $userPrompt,
                extraModelParams: [
                    'maxOutputTokens' => 2500,
                ]
            );

            $dto = SoapDraftDTO::fromArray($featureOutput);

            $medicalRecord->update([
                'ai_draft' => $dto->toArray(),
            ]);
        } catch (Throwable $e) {
            Log::error('SoapDraftJob failed', [
                'medical_record_id' => $this->medicalRecordId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * @param MedicalRecord $medicalRecord
     * @param Patient $patient
     * @param Doctor $doctor
     * @param Appointment $appointment
     * @param array<int,array<string,mixed>> $triageMessages
     * @param array<int|string,mixed> $triageExtractedSymptoms
     * @param array<string,mixed> $triageResult
     * @return string
     */
    private function buildPromptPayload(
        MedicalRecord $medicalRecord,
        Patient $patient,
        Doctor $doctor,
        Appointment $appointment,
        array $triageMessages,
        array $triageExtractedSymptoms,
        array $triageResult
    ): string {
        $vitals = $medicalRecord->vital_signs ?? [];

        return json_encode([
            'patient' => [
                'id' => $patient->id,
                'age' => $patient->date_of_birth ? (int) $patient->date_of_birth->diffInYears(now()) : null,
                'gender' => $patient->gender?->value ?? $patient->gender,
                'blood_type' => $patient->blood_type?->value ?? $patient->blood_type,
                'allergies' => $patient->allergies,
                'chronic_conditions' => $patient->chronic_conditions,
                'emergency_contact_name' => $patient->emergency_contact_name,
            ],
            'doctor' => [
                'id' => $doctor->id,
                'specialty' => $doctor->specialty,
            ],
            'appointment' => [
                'id' => $appointment->id,
                'date' => $appointment->appointment_date?->format('Y-m-d'),
                'start_time' => (string) $appointment->start_time,
                'type' => $appointment->type?->value ?? $appointment->type,
                'chief_complaint' => $appointment->chief_complaint,
                'triage_score' => $appointment->triage_score,
                'no_show_risk' => (string) $appointment->no_show_risk,
            ],
            'triage' => [
                'messages' => $triageMessages,
                'extracted_symptoms' => $triageExtractedSymptoms,
                'triage_result' => $triageResult,
            ],
            'vital_signs' => $vitals,
            'medical_record_existing' => [
                'subjective' => $medicalRecord->subjective,
                'objective' => $medicalRecord->objective,
                'assessment' => $medicalRecord->assessment,
                'plan' => $medicalRecord->plan,
            ],
            'output_format' => [
                'subjective' => 'string',
                'objective' => 'string',
                'assessment' => 'string',
                'plan' => 'string',
                'notes' => 'string|null',
                'risk_acknowledgement' => 'string|null',
            ],
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }
}

