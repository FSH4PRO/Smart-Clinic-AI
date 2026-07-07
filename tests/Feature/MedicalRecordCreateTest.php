<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Enums\BookingSource;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\ClinicBranch;
use App\Models\Doctor;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\User;
use App\Modules\AI\Jobs\SoapDraftJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

final class MedicalRecordCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctor_can_create_medical_record_and_ai_draft_is_triggered(): void
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

        $payload = [
            'appointment_id' => $appointment->id,
            'subjective' => 'Patient reports headache.',
            'objective' => 'BP 120/80.',
            'assessment' => 'Tension headache.',
            'plan' => 'Rest and hydration, consider analgesic.',
        ];

        $response = $this->actingAs($doctorUser, 'sanctum')
            ->postJson('/api/medical-records', $payload)
            ->assertStatus(201)
            ->assertJsonFragment(['success' => true, 'message' => 'Medical record draft created successfully']);

        $recordId = $response->json('data.id');
        $this->assertDatabaseHas('medical_records', [
            'id' => $recordId,
            'appointment_id' => $appointment->id,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'is_draft' => 1,
        ]);

        Queue::assertPushed(SoapDraftJob::class, function (SoapDraftJob $job) use ($recordId) {
            return $job->medicalRecordId === $recordId;
        });
    }
}
