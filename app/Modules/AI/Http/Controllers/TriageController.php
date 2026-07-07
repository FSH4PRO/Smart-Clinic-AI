<?php

declare(strict_types=1);

namespace App\Modules\AI\Http\Controllers;

use App\Http\Controllers\Api\BaseController;
use App\Modules\AI\DTO\TriageSessionMessageDTO;
use App\Modules\AI\Http\Requests\StartTriageRequest;
use App\Modules\AI\Http\Requests\TriageMessageRequest;
use App\Modules\AI\Jobs\SendToGeminiTriageJob;
use App\Models\AiTriageSession;
use App\Models\Appointment;
use App\Enums\AiFeature;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;



final class TriageController extends BaseController
{
    /**
     * Initialize a triage session.
     */
    public function start(StartTriageRequest $request): JsonResponse
    {
        $patient = $request->user()->patient;

        $validated = $request->validated();
        $appointmentId = $validated['appointment_id'];

        $appointment = Appointment::query()
            ->with('clinic')
            ->find($appointmentId);

        if (! $appointment) {
            return $this->errorResponse(message: 'Appointment not found.', status: 403);
        }

        // Enforce ownership/authorization: only the authenticated patient's own appointment can be used.
        if ((string) $appointment->patient_id !== (string) $patient->id) {
            return $this->errorResponse(message: 'Unauthorized action.', status: 403);
        }

        $appointment->loadMissing(['doctor']);


        $session = AiTriageSession::create([
            'id' => (string) Str::uuid(),
            'appointment_id' => $appointment->id,
            'messages' => [],
            'extracted_symptoms' => [],
            'triage_result' => [],
            'tokens_used' => 0,
            'completed_at' => null,
        ]);

        // System starter message for better conversation quality.
        $starter = new TriageSessionMessageDTO(
            role: 'ai',
            content: 'I’m a qualified triage nurse. Please describe your main symptoms and I will ask a few short questions to assess urgency.',
            timestamp: Carbon::now()->toAtomString(),
        );

        $session->messages = [
            $starter->toArray(),
        ];
        $session->save();

        return $this->successResponse([
            'session_id' => $session->id,
        ], 'Triage session started');
    }

    /**
     * Append patient message and dispatch async Gemini call.
     */
    public function message(AiTriageSession $session, TriageMessageRequest $request): JsonResponse
    {
        $patient = $request->user()->patient;

        $appointment = $session->appointment()->with('patient')->first();
        if ($appointment === null || (string) $appointment->patient?->id !== (string) $patient->id) {
            return $this->errorResponse(message: 'Unauthorized session access.', status: 403);
        }

        $dto = new TriageSessionMessageDTO(
            role: 'patient',
            content: (string) $request->validated()['message'],
            timestamp: Carbon::now()->toAtomString(),
        );

        DB::transaction(function () use ($session, $dto) {
            $messages = $session->messages ?? [];
            $messages[] = $dto->toArray();
            $session->messages = $messages;
            $session->save();
        });

        SendToGeminiTriageJob::dispatch(
            sessionId: (string) $session->id,
            clinicId: (string) optional($session->appointment?->clinic_id)->toString(),
            feature: AiFeature::TRIAGE,
        );


        return $this->successResponse(null, 'Triage message submitted');
    }

    /**
     * Get triage result (polling).
     */
    public function result(AiTriageSession $session): JsonResponse
    {
        $session->loadMissing(['appointment']);

        $completed = $session->completed_at !== null;

        return $this->successResponse([
            'completed' => $completed,
            'extracted_symptoms' => $completed ? ($session->extracted_symptoms ?? []) : [],
            'triage_result' => $completed ? ($session->triage_result ?? []) : null,
        ], 'Triage result retrieved');
    }
}
