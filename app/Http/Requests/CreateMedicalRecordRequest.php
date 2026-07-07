<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Doctor;
use App\Models\Appointment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

final class CreateMedicalRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = $this->user();

        if (! $user) {
            return false;
        }

        // Role is enforced by middleware, but keep defensive authorization here.
        if (! method_exists($user, 'doctor')) {
            return false;
        }

        return $user->doctor instanceof Doctor;
    }

    public function rules(): array
    {
        return [
            'appointment_id' => [
                'required',
                'uuid',
                'exists:appointments,id',
                // Ensures the appointment belongs to the authenticated doctor.
                function (string $attribute, mixed $value, callable $fail): void {
                    /** @var \App\Models\User $user */
                    $user = Auth::user();
                    $doctor = $user?->doctor;

                    if (! $doctor instanceof Doctor) {
                        $fail('Unauthorized. Doctor profile is missing.');
                        return;
                    }

                    $appointment = Appointment::query()->select(['id', 'doctor_id'])->find($value);
                    if (! $appointment) {
                        $fail('The selected appointment is invalid.');
                        return;
                    }

                    if ((string) $appointment->doctor_id !== (string) $doctor->id) {
                        $fail('You can only create medical records for your own appointments.');
                    }
                },
            ],
            'subjective' => ['required', 'string', 'max:10000'],
            'objective' => ['required', 'string', 'max:10000'],
            'assessment' => ['required', 'string', 'max:10000'],
            'plan' => ['required', 'string', 'max:10000'],
        ];
    }
}
