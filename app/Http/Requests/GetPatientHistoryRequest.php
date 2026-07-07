<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Patient;
use Illuminate\Foundation\Http\FormRequest;

final class GetPatientHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $requestedPatientId = (string) $this->route('id');

        // 1. Safely extract the role whether it is a raw string or a PHP Enum
        $role = $user->role instanceof \UnitEnum ? $user->role->value : $user->role;

        // 2. Doctors get global read access
        if ($role === 'doctor') {
            return true;
        }

        // 3. Patients get strict self-scoped access
        if ($role === 'patient') {
            // Safely resolve the Patient ID by explicitly querying the database 
            // This prevents failures if the $user->patient() relationship isn't configured.
            $patientRecord = Patient::query()->where('user_id', $user->id)->first();
            
            $owningPatientId = $patientRecord ? (string) $patientRecord->id : (string) $user->id;

            return $owningPatientId === $requestedPatientId;
        }

        return false;
    }

    public function rules(): array
    {
        return [];
    }
}