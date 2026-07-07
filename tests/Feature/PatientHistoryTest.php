<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PatientHistoryTest extends TestCase
{
    use RefreshDatabase;

    private User $doctor;
    private User $patientUserA;
    private User $patientUserB;
    private Patient $patientA;
    private Patient $patientB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->doctor = User::factory()->create(['role' => 'doctor']);
        
        $this->patientUserA = User::factory()->create(['role' => 'patient']);
        $this->patientA = Patient::factory()->create(['user_id' => $this->patientUserA->id]);

        $this->patientUserB = User::factory()->create(['role' => 'patient']);
        $this->patientB = Patient::factory()->create(['user_id' => $this->patientUserB->id]);
    }

    public function test_doctor_can_view_any_patient_history(): void
    {
        $response = $this->actingAs($this->doctor, 'sanctum')
            ->getJson("/api/patients/{$this->patientA->id}/history");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $this->patientA->id);
    }

    public function test_patient_can_view_their_own_history(): void
    {
        $response = $this->actingAs($this->patientUserA, 'sanctum')
            ->getJson("/api/patients/{$this->patientA->id}/history");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $this->patientA->id);
    }

    public function test_patient_is_forbidden_from_viewing_another_patients_history(): void
    {
        // Patient A maliciously tries to view Patient B's history
        $response = $this->actingAs($this->patientUserA, 'sanctum')
            ->getJson("/api/patients/{$this->patientB->id}/history");

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_history(): void
    {
        $response = $this->getJson("/api/patients/{$this->patientA->id}/history");

        $response->assertStatus(401);
    }
}