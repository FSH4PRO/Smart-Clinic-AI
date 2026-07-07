<?php

declare(strict_types=1);

namespace App\Modules\AI\Jobs;

use App\Enums\AiFeature;
use App\Modules\AI\DTO\DrugInteractionResultDTO;
use App\Modules\AI\Services\GeminiService;
use App\Models\AiTriageSession;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Throwable;

final class PrescriptionInteractionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $prescriptionId
    ) {}

    public function handle(GeminiService $geminiService): void
    {
        $prescription = Prescription::query()
            ->with([
                'patient',
                'items',
            ])
            ->findOrFail($this->prescriptionId);

        /** @var Patient $patient */
        $patient = $prescription->patient;

        $activeItems = $prescription->items;

        $drugList = $activeItems->map(function (PrescriptionItem $item): array {
            return [
                'drug_name' => $item->drug_name,
                'dosage' => $item->dosage,
                'frequency' => $item->frequency,
                'duration_days' => $item->duration_days,
            ];
        })->values()->all();

        if (count($drugList) < 2) {
            // No meaningful multi-drug interaction check if only 0-1 drugs.
            foreach ($activeItems as $item) {
                $item->update([
                    'ai_interaction_flag' => false,
                    'ai_interaction_detail' => null,
                ]);
            }
            return;
        }

        $systemInstruction = <<<TXT
You are a clinical pharmacist assistant.

Task:
Given a list of active medications for a patient, detect potential contraindications or multi-drug interactions.

Output STRICT JSON only with this shape:
{
  "ai_interaction_flag": boolean,
  "ai_interaction_detail": string|null
}

Safety rules:
- Do not give emergency instructions.
- If uncertain, set ai_interaction_flag=false and detail=null.
TXT;

        $userPrompt = json_encode([
            'patient' => [
                'id' => $patient->id,
                'allergies' => $patient->allergies,
                'chronic_conditions' => $patient->chronic_conditions,
            ],
            'active_prescription_items' => $drugList,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        try {
            $featureOutput = $geminiService->generateJson(
                feature: AiFeature::DRUG_CHECK,
                clinicId: (string) (optional($prescription->doctor)->clinic_id ?? 0),
                systemInstruction: $systemInstruction,
                userPrompt: $userPrompt,
                extraModelParams: [
                    'maxOutputTokens' => 650,
                ]
            );

            $dto = DrugInteractionResultDTO::fromArray($featureOutput);

            foreach ($activeItems as $item) {
                $item->update([
                    'ai_interaction_flag' => $dto->ai_interaction_flag,
                    'ai_interaction_detail' => $dto->ai_interaction_detail,
                ]);
            }
        } catch (Throwable $e) {
            Log::error('PrescriptionInteractionJob failed', [
                'prescription_id' => $this->prescriptionId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
