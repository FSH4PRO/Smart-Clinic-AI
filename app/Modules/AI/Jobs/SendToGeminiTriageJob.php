<?php

declare(strict_types=1);

namespace App\Modules\AI\Jobs;

use App\Enums\AiFeature;
use App\Modules\AI\DTO\TriageResultDTO;
use App\Modules\AI\Services\GeminiService;
use App\Models\AiTriageSession;
use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class SendToGeminiTriageJob implements ShouldQueue
{
    use \Illuminate\Foundation\Bus\Dispatchable;
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Job timeout in seconds.
     */
    public int $timeout = 90;

    public function __construct(
        public readonly string $sessionId,
        public readonly ?string $clinicId,
        public readonly AiFeature $feature,
    ) {
        $this->onQueue('ai');
    }

    public function handle(GeminiService $geminiService): void
    {
        $session = AiTriageSession::query()->with('appointment')->findOrFail($this->sessionId);
        
        /** @var Appointment $appointment */
        $appointment = $session->appointment;

        // إصلاح: التحقق من النص الفارغ والقيمة null معاً لضمان عدم تمرير نص فارغ إلى الخدمة
        $clinicId = ($this->clinicId !== null && $this->clinicId !== '') 
            ? $this->clinicId 
            : (string) ($appointment->clinic_id ?? '');

        if ($session->completed_at !== null) {
            return;
        }

        $messages = $session->messages ?? [];

        // تحسين الـ System Prompt ليمتلك القدرة على الاختيار بين طرح سؤال جديد أو إنهاء التقييم
        $systemPrompt = implode("\n", [
            'You are a qualified clinical triage nurse agent.',
            'Your task is to review the conversation history and decide whether to ask a follow-up question or finalize the evaluation.',
            'Gather critical data sequentially: symptom location, severity (1-10), duration, and systemic red flags.',
            'If you need more details, set "completed" to false and provide the next question in "next_question".',
            'If you have sufficient data (or after 3-5 turns), set "completed" to true, populate the medical metrics, and set "next_question" to null.',
            'Output MUST be STRICT JSON ONLY matching this exact structural schema:',
            '{',
            '  "completed": boolean,',
            '  "next_question": string or null,',
            '  "urgency_score": integer (1-5) or null,',
            '  "recommended_specialty": string or null,',
            '  "extracted_symptoms": [string, ...],',
            '  "red_flags": [string, ...]',
            '}',
            'Do not wrap inside markdown format block. Do not provide code blocks or conversational commentary outside the JSON object.',
        ]);

        $userPrompt = json_encode([
            'appointment_id' => $appointment->id,
            'conversation_transcript' => $messages,
            'notes' => 'Analyze history and respond using the requested structural JSON schema properties.'
        ], JSON_UNESCAPED_SLASHES);

        try {
            $decoded = $geminiService->generateJson(
                feature: $this->feature,
                clinicId: $clinicId,
                systemInstruction: $systemPrompt,
                userPrompt: $userPrompt,
                extraModelParams: [],
            );

            if (!is_array($decoded)) {
                throw new \RuntimeException('GeminiService returned an invalid response payload structure.');
            }

            // قراءة حالة الاكتمال من استجابة الذكاء الاصطناعي الحالية
            $isAICompleted = (bool) ($decoded['completed'] ?? false);
            $nextQuestion = $decoded['next_question'] ?? null;

            DB::transaction(function () use ($session, $appointment, $decoded, $isAICompleted, $nextQuestion) {
                // تحديث مصفوفة الأعراض المؤرشفة محلياً داخل الجلسة
                $session->extracted_symptoms = $decoded['extracted_symptoms'] ?? [];
                $session->triage_result = $decoded;
                
                $currentMessages = $session->messages ?? [];

                if ($isAICompleted) {
                    // إذا قرر الممرض الاصطناعي اكتمال البيانات، نغلق الجلسة ونحفظ التقييم النهائي في الموعد
                    $session->completed_at = now();
                    
                    $appointment->chief_complaint = implode(', ', $decoded['extracted_symptoms'] ?? []);
                    $appointment->triage_score = $decoded['urgency_score'] ?? null;
                    $appointment->save();

                    $currentMessages[] = [
                        'role' => 'ai',
                        'content' => 'Triage processing finalized. Urgency evaluation level: ' . ($decoded['urgency_score'] ?? 'N/A'),
                        'timestamp' => now()->toISOString(),
                    ];
                } else {
                    // إذا لم تكتمل، نضيف سؤال الـ AI الجديد إلى صندوق الرسائل ليظهر للمريض في تطبيق الـ Flutter
                    $currentMessages[] = [
                        'role' => 'ai',
                        'content' => $nextQuestion ?? 'Could you please describe your current symptoms in more detail?',
                        'timestamp' => now()->toISOString(),
                    ];
                }

                $session->messages = $currentMessages;
                $session->save();
            });

        } catch (Throwable $e) {
            Log::error('SendToGeminiTriageJob processing error failure log', [
                'session_id' => $this->sessionId,
                'clinic_id' => $clinicId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}