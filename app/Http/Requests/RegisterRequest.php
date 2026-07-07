<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\UserRole;
use App\Enums\PatientGender;
use App\Enums\BloodType;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $baseRules = [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'phone' => ['required', 'string', 'regex:/^[0-9]{10,15}$/', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::in(array_map(fn($c) => $c->value, UserRole::cases()))],
            'avatar' => ['nullable', 'url', 'max:255'],
        ];

        $role = $this->input('role');

        // Dynamic validation rules based on role
        if ($role === UserRole::PATIENT->value) {
            $baseRules = array_merge($baseRules, [
                'date_of_birth' => ['required', 'date', 'before:today'],
                'gender' => ['required', 'string', Rule::in(array_map(fn($g) => $g->value, PatientGender::cases()))],
                'blood_type' => ['required', 'string', Rule::in(array_map(fn($b) => $b->value, BloodType::cases()))],
                'national_id' => ['nullable', 'string', 'max:50'],
                'emergency_contact_name' => ['nullable', 'string', 'max:100'],
                'emergency_contact_phone' => ['nullable', 'string', 'regex:/^[0-9]{10,15}$/'],
                'allergies' => ['nullable', 'array'],
                'chronic_conditions' => ['nullable', 'array'],
            ]);
        }

        if ($role === UserRole::DOCTOR->value) {
            $baseRules = array_merge($baseRules, [
                'specialty' => ['required', 'string', 'max:100'],
                'license_number' => ['required', 'string', 'max:50', 'unique:doctors,license_number'],
                'bio' => ['nullable', 'string', 'max:1000'],
                'years_experience' => ['nullable', 'integer', 'min:0', 'max:60'],
                'consultation_fee' => ['nullable', 'numeric', 'min:0'],
                'clinic_id' => ['required', 'uuid', 'exists:clinics,id'],
                'branch_id' => ['required', 'uuid', 'exists:clinic_branches,id'],
            ]);
        }

        return $baseRules;
    }

    public function messages(): array
    {
        return [
            'date_of_birth.before' => 'Date of birth must be in the past.',
            'license_number.unique' => 'This license number is already registered.',
            'phone.regex' => 'The phone number must be between 10 and 15 digits.',
            'emergency_contact_phone.regex' => 'The emergency contact phone must be between 10 and 15 digits.',
        ];
    }
}
