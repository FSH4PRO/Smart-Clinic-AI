<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Carbon\Carbon;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AppointmentCancelService
{
    public function cancel(Appointment $appointment, array $payload): Appointment
    {
        $appointment = app(AppointmentShowService::class)->getAccessibleAppointment($appointment);

        // Business rules

        if (in_array($appointment->status, [AppointmentStatus::CANCELLED, AppointmentStatus::COMPLETED], true)) {
            throw new BadRequestHttpException('This appointment cannot be cancelled.');
        }

        // Optional rule: only future appointments can be cancelled
        // (Keeps behavior safe even if UI allows cancellation late)
        $appointmentStart = Carbon::parse($appointment->appointment_date?->format('Y-m-d') . ' ' . $appointment->start_time);
        if ($appointmentStart->isPast()) {
            throw new BadRequestHttpException('This appointment is already started and cannot be cancelled.');
        }

        if (isset($payload['expected_status']) && $payload['expected_status']) {
            if ($appointment->status !== AppointmentStatus::from($payload['expected_status'])) {
                throw new BadRequestHttpException('Appointment status has changed.');
            }
        }

        $appointment->status = AppointmentStatus::CANCELLED;
        $appointment->cancelled_at = now();
        $appointment->cancellation_reason = $payload['cancellation_reason'] ?? null;

        $appointment->save();

        $appointment->load(['patient', 'doctor', 'clinic', 'branch']);

        return $appointment;
    }
}
