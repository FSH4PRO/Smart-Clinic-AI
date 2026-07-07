<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\MedicalRecord;
use App\Models\User;
use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Enums\BookingSource;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MedicalRecordSignTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctor_can_sign_own_medical_record(): void
    {
        $doctorUser = User::factory()->create(['role' => UserRole::DOCTOR->value]);
        $otherDoctorUser = User::factory()->create(['role' => UserRole::DOCTOR->value]);

        $clinicOwner = User::factory()->create(['role' => \App\Enums\UserRole::CLINIC_ADMIN->value]);
        $clinic = \App\Models\Clinic::factory()->create(['owner_id' => $clinicOwner->id]);
        $branch = \App\Models\ClinicBranch::factory()->create(['clinic_id' => $clinic->id]);


        $appointmentDoctor = Doctor::factory()->create([
            'user_id' => $doctorUser->id,
            'clinic_id' => $clinic->id,
            'branch_id' => $branch->id,
        ]);

        $otherDoctor = Doctor::factory()->create([
            'user_id' => $otherDoctorUser->id,
            'clinic_id' => $clinic->id,
            'branch_id' => $branch->id,
        ]);

        $patientUser = User::factory()->create(['role' => UserRole::PATIENT->value]);
        $patient = \App\Models\Patient::factory()->create(['user_id' => $patientUser->id]);



        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $appointmentDoctor->id,
            'clinic_id' => $appointmentDoctor->clinic_id,
            'branch_id' => $appointmentDoctor->branch_id,
            'appointment_date' => now()->toDateString(),
            'start_time' => now()->format('H:i:s'),
            'end_time' => now()->addHour()->format('H:i:s'),
            'type' => AppointmentType::IN_PERSON->value,
            'status' => AppointmentStatus::PENDING->value,
            'booking_source' => BookingSource::APP->value,
        ]);

        $record = MedicalRecord::query()->create([
            'appointment_id' => $appointment->id,

            'doctor_id' => $appointmentDoctor->id,
            'patient_id' => $patient->id,
            'subjective' => 's',
            'objective' => 'o',
            'assessment' => 'a',
            'plan' => 'p',
            'ai_draft' => [],
            'icd10_codes' => [],
            'vital_signs' => [],
            'attachments' => [],
            'is_draft' => true,
            'signed_at' => null,
        ]);


        $response = $this->actingAs($doctorUser, 'sanctum')
            ->patchJson("/api/medical-records/{$record->id}/sign");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_draft', false)
            ->assertJsonStructure(['data' => ['signed_at', 'id']]);

        $this->assertDatabaseHas('medical_records', [
            'id' => $record->id,
            'is_draft' => 0,
        ]);
    }

    public function test_other_doctor_cannot_sign(): void
    {
        $doctorUser = User::factory()->create(['role' => UserRole::DOCTOR->value]);
        $otherDoctorUser = User::factory()->create(['role' => UserRole::DOCTOR->value]);

        $clinicOwner = User::factory()->create(['role' => \App\Enums\UserRole::CLINIC_ADMIN->value]);
        $clinic = \App\Models\Clinic::factory()->create(['owner_id' => $clinicOwner->id]);
        $branch = \App\Models\ClinicBranch::factory()->create(['clinic_id' => $clinic->id]);

        $doctor = Doctor::factory()->create([
            'user_id' => $doctorUser->id,
            'clinic_id' => $clinic->id,
            'branch_id' => $branch->id,
        ]);

        $otherDoctor = Doctor::factory()->create([
            'user_id' => $otherDoctorUser->id,
            'clinic_id' => $clinic->id,
            'branch_id' => $branch->id,
        ]);


        $patient = \App\Models\Patient::factory()->create([
            'user_id' => User::factory()->create(['role' => UserRole::PATIENT->value])->id,
        ]);

        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'clinic_id' => $doctor->clinic_id,
            'branch_id' => $doctor->branch_id,
            'appointment_date' => now()->toDateString(),
            'start_time' => now()->format('H:i:s'),
            'end_time' => now()->addHour()->format('H:i:s'),
            'type' => AppointmentType::IN_PERSON->value,
            'status' => AppointmentStatus::PENDING->value,
            'booking_source' => BookingSource::APP->value,
        ]);

        $record = MedicalRecord::query()->create([
            'appointment_id' => $appointment->id,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'subjective' => 's',
            'objective' => 'o',
            'assessment' => 'a',
            'plan' => 'p',
            'ai_draft' => [],
            'icd10_codes' => [],
            'vital_signs' => [],
            'attachments' => [],
            'is_draft' => true,
            'signed_at' => null,
        ]);


        $response = $this->actingAs($otherDoctorUser, 'sanctum')
            ->patchJson("/api/medical-records/{$record->id}/sign");

        $response->assertStatus(403);
    }

    public function test_cannot_sign_already_finalized_record(): void
    {
        $doctorUser = User::factory()->create(['role' => UserRole::DOCTOR->value]);

        $clinicOwner = User::factory()->create(['role' => \App\Enums\UserRole::CLINIC_ADMIN->value]);
        $clinic = \App\Models\Clinic::factory()->create(['owner_id' => $clinicOwner->id]);
        $branch = \App\Models\ClinicBranch::factory()->create(['clinic_id' => $clinic->id]);

        $doctor = Doctor::factory()->create([
            'user_id' => $doctorUser->id,
            'clinic_id' => $clinic->id,
            'branch_id' => $branch->id,
        ]);



        $patient = \App\Models\Patient::factory()->create([
            'user_id' => User::factory()->create(['role' => UserRole::PATIENT->value])->id,
        ]);

        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'clinic_id' => $doctor->clinic_id,
            'branch_id' => $doctor->branch_id,
            'appointment_date' => now()->toDateString(),
            'start_time' => now()->format('H:i:s'),
            'end_time' => now()->addHour()->format('H:i:s'),
            'type' => AppointmentType::IN_PERSON->value,
            'status' => AppointmentStatus::PENDING->value,
            'booking_source' => BookingSource::APP->value,
        ]);

        $record = MedicalRecord::query()->create([
            'appointment_id' => $appointment->id,
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'subjective' => 's',
            'objective' => 'o',
            'assessment' => 'a',
            'plan' => 'p',
            'ai_draft' => [],
            'icd10_codes' => [],
            'vital_signs' => [],
            'attachments' => [],
            'is_draft' => false,
            'signed_at' => now()->subDay(),
        ]);


        $response = $this->actingAs($doctorUser, 'sanctum')
            ->patchJson("/api/medical-records/{$record->id}/sign");

        $response->assertStatus(500);
    }
}
