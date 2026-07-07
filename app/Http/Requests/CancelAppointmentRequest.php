<?php

namespace App\Http\Requests;

use App\Enums\AppointmentStatus;
use Illuminate\Foundation\Http\FormRequest;

class CancelAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Role authorization is handled by route middleware.
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'cancellation_reason' => ['nullable', 'string', 'max:10000'],

            // Future-proofing (allows client to send expected status if you want)
            'expected_status' => ['nullable', 'in:' . implode(',', array_map(fn($e) => $e->value, AppointmentStatus::cases()))],
        ];
    }
}
