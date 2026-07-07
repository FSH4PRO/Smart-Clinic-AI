<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Enums\BookingSource;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Services\DoctorSlotService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AppointmentBookingService
{
    public function __construct(
        protected DoctorSlotService $doctorSlotService,
    ) {}

    public function book(array $data): Appointment
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $patient = $user->patient;

        if (! $patient instanceof Patient) {
            throw ValidationException::withMessages([
                'patient' => ['Patient profile is missing for this account.'],
            ]);
        }

        /** @var Doctor $doctor */
        $doctor = Doctor::query()->findOrFail($data['doctor_id']);

        // Ensure clinic/branch are consistent with doctor
        $expectedClinicId = $doctor->clinic_id;
        $expectedBranchId = $doctor->branch_id;

        if ((string) $data['clinic_id'] !== (string) $expectedClinicId || (string) $data['branch_id'] !== (string) $expectedBranchId) {
            throw ValidationException::withMessages([
                'doctor_id' => ['The selected doctor does not match the provided clinic/branch.'],
            ]);
        }

        $appointmentDate = Carbon::parse($data['appointment_date'])->format('Y-m-d');
        $startTime = Carbon::parse($data['start_time'])->format('H:i:s');
        $endTime = Carbon::parse($data['end_time'])->format('H:i:s');

        $slotDurationSeconds = Carbon::parse($endTime)->diffInSeconds(Carbon::parse($startTime));

        // Strict slot matching: requested window must exist in available slots.
        $availableSlots = $this->doctorSlotService->getAvailableSlots($doctor, $appointmentDate);

        $matches = $availableSlots->contains(
            fn($slot) =>
            (string) $slot['start_time'] === (string) $startTime && (string) $slot['end_time'] === (string) $endTime
        );

        if (! $matches) {
            throw ValidationException::withMessages([
                'start_time' => ['Selected time slot is not available.'],
            ]);
        }

        return DB::transaction(function () use ($patient, $doctor, $data, $appointmentDate, $startTime, $endTime) {
            $appointment = Appointment::create([
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'clinic_id' => $doctor->clinic_id,
                'branch_id' => $doctor->branch_id,

                'appointment_date' => $appointmentDate,
                'start_time' => $startTime,
                'end_time' => $endTime,

                'type' => $data['type'] ?? AppointmentType::IN_PERSON->value,
                'status' => AppointmentStatus::PENDING,
                'booking_source' => $data['booking_source'] ?? BookingSource::APP->value,

                'chief_complaint' => 'N/A',
                'triage_score' => null,
                'no_show_risk' => 0,
                'notes' => $data['notes'] ?? null,
                'cancelled_at' => null,
                'cancellation_reason' => null,
            ]);

            return $appointment->load(['patient', 'doctor', 'clinic', 'branch']);
        });
    }
}
