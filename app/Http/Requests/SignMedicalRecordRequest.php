<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\MedicalRecord;
use Illuminate\Foundation\Http\FormRequest;

final class SignMedicalRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        $record = MedicalRecord::query()
            ->with('appointment')
            ->find($this->route('id'));

        if (! $record || ! $record->appointment) {
            return false;
        }

        // Defensive ownership check: doctor may only sign records for their own appointment.
        return (string) $record->appointment->doctor_id === (string) $user->doctor?->id;
    }

    public function rules(): array
    {
        // Route-model/id is validated implicitly in authorize() via lookup.
        return [];
    }
}
