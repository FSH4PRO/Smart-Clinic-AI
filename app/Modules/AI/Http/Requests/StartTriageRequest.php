<?php

declare(strict_types=1);

namespace App\Modules\AI\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StartTriageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $patientId = $this->user()?->patient?->id;

        // Do NOT enforce patient ownership in request validation.
        // Ownership/authorization belongs in the controller to ensure consistent 403 responses (not 422).
        return [
            'appointment_id' => [
                'required',
                'uuid',
                'exists:appointments,id',
            ],
        ];
    }
}
