<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PrescriptionIssueTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;
    private User $otherDoctor;
    private Appointment $appointment;
    private array $validPayload;

   protected function setUp(): void
    {
        parent::setUp();

        // 1. Create dependencies
        $clinic = \App\Models\Clinic::factory()->create();
        $this->pharmacy = \App\Models\Pharmacy::factory()->create(); // Create a Pharmacy

        // 2. Create Doctor & Patient
        $doctorUser = User::factory()->create(['role' => 'doctor']);
        $this->doctorProfile = \App\Models\Doctor::factory()->create([
            'user_id'   => $doctorUser->id,
            'clinic_id' => $clinic->id,
        ]);
        $this->doctor = $doctorUser; 

        $patientUser = User::factory()->create(['role' => 'patient']);
        $this->patientProfile = \App\Models\Patient::factory()->create(['user_id' => $patientUser->id]);

        // 3. Create the parent Medical Record & Appointment
        $this->appointment = \App\Models\Appointment::factory()->create([
            'doctor_id'  => $this->doctorProfile->id,
            'patient_id' => $this->patientProfile->id
        ]);

        $this->medicalRecord = \App\Models\MedicalRecord::factory()->create([
            'appointment_id' => $this->appointment->id,
            'patient_id'     => $this->patientProfile->id,
            'doctor_id'      => $this->doctorProfile->id,
        ]);

        // 4. Update payload to match what your API/Service needs
        $this->validPayload = [
            'appointment_id'    => $this->appointment->id,
            'pharmacy_id'       => $this->pharmacy->id, // Pass the pharmacy ID
            'status'            => 'issued',
            'notes'             => 'Take medications after meals.',
            
            // If you have a separate items table, keep these here; 
            // otherwise, they are ignored by the prescriptions table.
            'medication_name'   => 'Amoxicillin',
            'dosage'            => '500mg',
            'frequency'         => 'Three times daily',
            'duration'          => '7 days',
        ];
    }

    public function test_assigned_doctor_can_successfully_issue_prescription(): void
    {
        $response = $this->actingAs($this->doctor, 'sanctum')
            ->postJson('/api/prescriptions', $this->validPayload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.medication_name', 'Amoxicillin');

        $this->assertDatabaseHas('prescriptions', [
            'appointment_id'  => $this->appointment->id,
            'medication_name' => 'Amoxicillin',
        ]);
    }

    public function test_unassigned_doctor_is_forbidden_from_issuing_prescription(): void
    {
        // Other doctor tries to forge a script for an appointment they don't own
        $response = $this->actingAs($this->otherDoctor, 'sanctum')
            ->postJson('/api/prescriptions', $this->validPayload);

        $response->assertStatus(403);
    }

    public function test_missing_required_fields_triggers_validation_errors(): void
    {
        $invalidPayload = $this->validPayload;
        unset($invalidPayload['medication_name']); // Drop required field

        $response = $this->actingAs($this->doctor, 'sanctum')
            ->postJson('/api/prescriptions', $invalidPayload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['medication_name']);
    }

    public function test_unauthenticated_requests_are_blocked(): void
    {
        $response = $this->postJson('/api/prescriptions', $this->validPayload);

        $response->assertStatus(401);
    }
}