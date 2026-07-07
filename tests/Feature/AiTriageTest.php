<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AiFeature;
use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Enums\BookingSource;
use App\Enums\UserRole;
use App\Models\AiTriageSession;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\ClinicBranch;
use App\Models\Doctor;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\User;
use App\Modules\AI\Jobs\SendToGeminiTriageJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

final class AiTriageTest extends TestCase
{

    use RefreshDatabase;

    public function test_unauthenticated_requests_return_401(): void
    {
        $this->postJson('/api/v1/triage/start', [])
            ->assertStatus(401);

        $this->postJson('/api/v1/triage/' . (string) Str::uuid() . '/message', ['message' => 'hi'])
            ->assertStatus(401);

        $this->getJson('/api/v1/triage/' . (string) Str::uuid() . '/result')
            ->assertStatus(401);
    }

    public function test_non_patient_roles_cannot_start_or_send(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'role' => UserRole::DOCTOR->value,
        ]);

        $owner = User::factory()->create(['role' => UserRole::CLINIC_ADMIN->value]);
        $clinic = Clinic::factory()->create(['owner_id' => $owner->id]);
        $branch = ClinicBranch::factory()->create(['clinic_id' => $clinic->id]);
        $doctorUser = User::factory()->create(['role' => UserRole::DOCTOR->value]);
        $doctor = Doctor::factory()->create([
            'user_id' => $doctorUser->id,
            'clinic_id' => $clinic->id,
            'branch_id' => $branch->id,
        ]);





        $patientUser = User::factory()->create(['role' => UserRole::PATIENT->value]);
        $patient = Patient::factory()->create(['user_id' => $patientUser->id]);

        $appointment = Appointment::factory()->create([

            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinic->id,
            'branch_id' => $branch->id,
            'appointment_date' => now()->toDateString(),
            'start_time' => now()->format('H:i:s'),
            'end_time' => now()->addHour()->format('H:i:s'),
            'type' => AppointmentType::IN_PERSON->value,
            'status' => AppointmentStatus::PENDING->value,
            'booking_source' => BookingSource::APP->value,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/triage/start', ['appointment_id' => $appointment->id])
            ->assertStatus(403);

        $session = (new \App\Models\AiTriageSession())->forceFill([


            'appointment_id' => $appointment->id,
            'messages' => [],
            'extracted_symptoms' => [],
            'triage_result' => [],
            'tokens_used' => 0,
            'completed_at' => null,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/triage/' . $session->id . '/message', ['message' => 'pain'])
            ->assertStatus(403);
    }

    public function test_patient_cannot_start_triage_for_other_patient_appointment(): void
    {
        $owner = User::factory()->create(['role' => UserRole::CLINIC_ADMIN->value]);
        $clinic = Clinic::factory()->create(['owner_id' => $owner->id]);
        $branch = ClinicBranch::factory()->create(['clinic_id' => $clinic->id]);
        $doctorUser = User::factory()->create(['role' => UserRole::DOCTOR->value]);
        $doctor = Doctor::factory()->create([
            'user_id' => $doctorUser->id,
            'clinic_id' => $clinic->id,
            'branch_id' => $branch->id,
        ]);



        $patientAUser = User::factory()->create(['role' => UserRole::PATIENT->value]);
        $patientA = Patient::factory()->create(['user_id' => $patientAUser->id]);

        $patientBUser = User::factory()->create(['role' => UserRole::PATIENT->value]);
        $patientB = Patient::factory()->create(['user_id' => $patientBUser->id]);


        // Ensure users->patient relationship is correctly persisted for authz checks.
        // Your project uses Patients table with a NOT NULL user_id.
        $userA = $patientAUser = User::factory()->create(['role' => UserRole::PATIENT->value]);
        $userA->patient()->save($patientA);


        $appointmentB = Appointment::factory()->create([
            'patient_id' => $patientB->id,
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinic->id,
            'branch_id' => $branch->id,
            'appointment_date' => now()->toDateString(),
            'start_time' => now()->format('H:i:s'),
            'end_time' => now()->addHour()->format('H:i:s'),
            'type' => AppointmentType::IN_PERSON->value,
            'status' => AppointmentStatus::PENDING->value,
            'booking_source' => BookingSource::APP->value,
        ]);

        $this->actingAs($userA, 'sanctum')
            ->postJson('/api/v1/triage/start', ['appointment_id' => $appointmentB->id])
            ->assertStatus(403);
    }

    public function test_patient_flow_start_message_poll_incomplete_and_completed(): void
    {
        Queue::fake();

        $owner = User::factory()->create(['role' => UserRole::CLINIC_ADMIN->value]);
        $clinic = Clinic::factory()->create(['owner_id' => $owner->id]);
        $branch = ClinicBranch::factory()->create(['clinic_id' => $clinic->id]);
        $doctorUser = User::factory()->create(['role' => UserRole::DOCTOR->value]);
        $doctor = Doctor::factory()->create([
            'user_id' => $doctorUser->id,
            'clinic_id' => $clinic->id,
            'branch_id' => $branch->id,
        ]);




        $patientUser = User::factory()->create(['role' => UserRole::PATIENT->value]);
        $patient = Patient::factory()->create(['user_id' => $patientUser->id]);

        $user = $patientUser;

        $user->patient()->associate($patient);
        $user->save();

        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinic->id,
            'branch_id' => $branch->id,
            'appointment_date' => now()->toDateString(),
            'start_time' => now()->format('H:i:s'),
            'end_time' => now()->addHour()->format('H:i:s'),
            'type' => AppointmentType::IN_PERSON->value,
            'status' => AppointmentStatus::PENDING->value,
            'booking_source' => BookingSource::APP->value,
            'chief_complaint' => null,
            'triage_score' => null,
            'no_show_risk' => null,
        ]);

        // Start
        $startResponse = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/triage/start', ['appointment_id' => $appointment->id])
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json) => $json
                    ->where('success', true)
                    ->where('message', 'Triage session started')
                    ->whereNull('errors')
                    ->has('data.session_id')
            );

        $sessionId = $startResponse->json('data.session_id');
        $this->assertDatabaseHas('ai_triage_sessions', ['id' => $sessionId, 'appointment_id' => $appointment->id]);

        // Message
        $messageResponse = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/triage/{$sessionId}/message", ['message' => 'My throat hurts'])
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json) => $json
                    ->where('success', true)
                    ->where('message', 'Triage message submitted')
                    ->whereNull('errors')
                    ->where('data', null)
            );

        $this->assertDatabaseHas('ai_triage_sessions', ['id' => $sessionId]);

        Queue::assertPushed(SendToGeminiTriageJob::class, function (SendToGeminiTriageJob $job) use ($sessionId) {
            return $job->sessionId === $sessionId;
        });

        $session = AiTriageSession::query()->findOrFail($sessionId);
        $this->assertNotEmpty($session->messages);
        $this->assertSame('patient', $session->messages[array_key_last($session->messages)]['role']);

        // Incomplete result polling
        $resultResponse = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/triage/{$sessionId}/result")
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json) => $json
                    ->where('success', true)
                    ->where('message', 'Triage result retrieved')
                    ->whereNull('errors')
                    ->where('data.completed', false)
                    ->where('data.extracted_symptoms', [])
                    ->where('data.triage_result', null)
            );

        // Completed result polling
        $session->update([
            'completed_at' => now(),
            'extracted_symptoms' => ['sore throat', 'fever'],
            'triage_result' => [
                'urgency_score' => 3,
                'recommended_specialty' => 'ENT',
                'extracted_symptoms' => ['sore throat', 'fever'],
                'red_flags' => [],
            ],
        ]);

        $completedResponse = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/triage/{$sessionId}/result")
            ->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json) => $json
                    ->where('success', true)
                    ->where('message', 'Triage result retrieved')
                    ->whereNull('errors')
                    ->where('data.completed', true)
                    ->where('data.extracted_symptoms.0', 'sore throat')
                    ->whereType('data.triage_result', 'array')
            );
    }
}
