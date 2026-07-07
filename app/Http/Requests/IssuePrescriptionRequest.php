<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Appointment;
use Illuminate\Foundation\Http\FormRequest;

final class IssuePrescriptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $appointmentId = $this->input('appointment_id');

        if (!$appointmentId) {
            return false;
        }

        $appointment = Appointment::query()->find($appointmentId);

        if ($appointment === null) {
            return false;
        }

        // 1. Fetch the Doctor profile belonging to the authenticated User
        $doctor = \App\Models\Doctor::query()
            ->where('user_id', $this->user()->id)
            ->first();

        if ($doctor === null) {
            return false;
        }

        // 2. Compare the Doctor Profile ID against the Appointment's Doctor ID
        return (string) $appointment->doctor_id === (string) $doctor->id;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'appointment_id'  => ['required', 'uuid', 'exists:appointments,id'],
            'medical_record_id' => ['required', 'uuid', 'exists:medical_records,id'],
            'pharmacy_id'     => ['required', 'uuid', 'exists:pharmacies,id'],
            'status'          => ['nullable', 'in:draft,issued,dispensed,cancelled'],
            'notes'           => ['nullable', 'string'],

            // Optional: If you have a prescription_items table for the actual drugs:
            'items'                 => 'required|array|min:1',
            'items.*.drug_name'     => 'required|string|max:120',
            'items.*.dosage'        => 'required|string|max:80',
            'items.*.frequency'     => 'required|string|max:80',
            'items.*.duration_days' => 'required|integer|min:1',
            'items.*.instructions'  => 'nullable|string',

        ];
    }
}
