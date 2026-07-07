<?php

declare(strict_types=1);

namespace App\Services;

use App\Modules\AI\Services\GeminiService;
use App\Models\MedicalRecord;
use App\Models\Patient;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

final class MedicalRecordService
{
    public function __construct(
        private readonly GeminiService $geminiService,
    ) {}

    /**
     * Gather a patient's historical SOAP entries and synthesize into a plain-language
     * Health Passport narrative suitable for appending to an exported PDF.
     *
     * @param Patient $patient
     * @return string
     */
    public function summarizeForHealthPassport(Patient $patient): string
    {
        $records = MedicalRecord::query()
            ->where('patient_id', $patient->id)
            ->orderByDesc('created_at')
            ->limit(30)
            ->get(['id', 'ai_draft', 'subjective', 'objective', 'assessment', 'plan', 'created_at']);

        $soapTexts = [];

        foreach ($records as $record) {
            /** @var array<string,mixed>|null $aiDraft */
            $aiDraft = $record->ai_draft;

            // If we have an AI draft JSON, prefer it; otherwise fallback to signed SOAP sections.
            if (is_array($aiDraft) && ! empty($aiDraft)) {
                $soapTexts[] = $this->formatAiDraftForSummarization($aiDraft);
                continue;
            }

            $soapTexts[] = $this->formatSignedSoapForSummarization($record);
        }

        $soapContext = implode("\n\n", $soapTexts);

        $systemPrompt = 'You are a clinical documentation assistant. Output MUST be strict JSON with exactly one key: "narrative".';

        $userPrompt = <<<PROMPT
Summarize the following patient SOAP-style clinical entries into a coherent plain-language clinical narrative.
- Target length: ~300 words.
- Do NOT mention that these are SOAP notes.
- Use medically accurate language.
- Keep allergies, chronic conditions, and key diagnoses/assessments when available.
- Output narrative only (no headings), in clear paragraph form.

Patient overview:
- patient_id: {$patient->id}

Clinical entries:
{$soapContext}
PROMPT;

        $response = $this->geminiService->generateJson(
            feature: \App\Enums\AiFeature::SOAP_DRAFT,
            clinicId: (string) ($patient->clinic_id ?? '00000000-0000-0000-0000-000000000000'),
            systemInstruction: $systemPrompt,
            userPrompt: $userPrompt
        );

        $narrative = (string) Arr::get($response, 'narrative', '');
        $narrative = trim($narrative);

        if ($narrative === '') {
            return 'No AI Health Passport summary is available yet.';
        }

        // Ensure it is clean paragraph text.
        $narrative = Str::replace("\n", " ", $narrative);
        $narrative = preg_replace('/\s+/', ' ', $narrative) ?? $narrative;

        return trim($narrative);
    }

    /**
     * @param array<string,mixed> $aiDraft
     * @return string
     */
    private function formatAiDraftForSummarization(array $aiDraft): string
    {
        $subjective = (string) Arr::get($aiDraft, 'subjective', '');
        $objective = (string) Arr::get($aiDraft, 'objective', '');
        $assessment = (string) Arr::get($aiDraft, 'assessment', '');
        $plan = (string) Arr::get($aiDraft, 'plan', '');

        return trim(implode(" | ", array_filter([$subjective, $objective, $assessment, $plan])));
    }

    /**
     * @param MedicalRecord $record
     * @return string
     */
    private function formatSignedSoapForSummarization(MedicalRecord $record): string
    {
        return trim(implode(" | ", array_filter([
            (string) $record->subjective,
            (string) $record->objective,
            (string) $record->assessment,
            (string) $record->plan,
        ])));
    }
}

