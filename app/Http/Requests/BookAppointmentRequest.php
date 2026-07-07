<?php

namespace App\Http\Requests;

use App\Enums\AppointmentType;
use App\Enums\BookingSource;
use Illuminate\Foundation\Http\FormRequest;

class BookAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Role authorization is handled by route middleware (role:patient)
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'doctor_id' => ['required', 'uuid', 'exists:doctors,id'],
            'clinic_id' => ['required', 'uuid', 'exists:clinics,id'],
            'branch_id' => ['required', 'uuid', 'exists:clinic_branches,id'],

            'appointment_date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i:s'],
            'end_time' => ['required', 'date_format:H:i:s', 'after:start_time'],

            'type' => ['nullable', 'in:' . implode(',', array_map(fn($e) => $e->value, AppointmentType::cases()))],

            'notes' => ['nullable', 'string', 'max:10000'],

            'booking_source' => ['nullable', 'in:' . implode(',', array_map(fn($e) => $e->value, BookingSource::cases()))],
        ];
    }

    public function messages(): array
    {
        return [
            'end_time.after' => 'The end_time must be after start_time.',
        ];
    }
}
