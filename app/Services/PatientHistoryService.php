<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Patient;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class PatientHistoryService
{
    /**
     * Retrieve the comprehensive medical history for a specific patient.
     *
     * @param string $patientId
     * @return Patient
     * @throws ModelNotFoundException
     */
    public function getFullHistory(string $patientId): Patient
    {
        return Patient::query()
            ->with([
                // Fetch appointments ordered chronologically (newest first)
                'appointments' => fn ($query) => $query->orderBy('created_at', 'desc'),
                // Eager load the AI triage context and finalized medical notes
                'appointments.aiTriageSessions',
                'appointments.medicalRecord'
            ])
            ->findOrFail($patientId);
    }
}