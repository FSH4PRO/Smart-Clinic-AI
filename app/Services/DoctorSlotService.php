<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorSchehule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DoctorSlotService
{
    // Return available slots for a doctor on a given date (Y-m-d).

    public function getAvailableSlots(Doctor $doctor, string $date): Collection
    {
        $carbonDate = Carbon::parse($date);

        $dayOfWeek = (int) $carbonDate->dayOfWeek; // Carbon: 0=Sun ... 6=Sat

        $schedules = DoctorSchehule::query()
            ->where('doctor_id', $doctor->id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->get();

        if ($schedules->isEmpty()) {
            return collect();
        }

        $candidateSlots = $this->generateCandidateSlots($schedules, $date);

        $appointments = Appointment::query()
            ->where('doctor_id', $doctor->id)
            ->whereDate('appointment_date', $date)
            ->where('status', '!=', AppointmentStatus::CANCELLED->value)
            ->get(['start_time', 'end_time']);

        if ($appointments->isEmpty()) {
            return $candidateSlots;
        }

        return $candidateSlots->reject(function (array $slot) use ($appointments) {
            $slotStart = Carbon::parse($slot['start_time']);
            $slotEnd = Carbon::parse($slot['end_time']);

            return $appointments->contains(function ($appt) use ($slotStart, $slotEnd) {
                $apptStart = Carbon::parse($appt->start_time);
                $apptEnd = Carbon::parse($appt->end_time);

                // overlap check: [aStart,aEnd) intersects [bStart,bEnd)
                return $slotStart->lt($apptEnd) && $slotEnd->gt($apptStart);
            });
        })->values();
    }

    private function generateCandidateSlots($schedules, string $date): Collection
    {
        $candidateSlots = collect();

        foreach ($schedules as $schedule) {
            $slotDuration = (int) $schedule->slot_duration_minutes;

            $slotStart = Carbon::parse($schedule->start_time->format('H:i:s'));
            $slotEnd = Carbon::parse($schedule->end_time->format('H:i:s'));

            $cursor = Carbon::createFromFormat('Y-m-d H:i:s', $date . ' ' . $slotStart->format('H:i:s'));
            $end = Carbon::createFromFormat('Y-m-d H:i:s', $date . ' ' . $slotEnd->format('H:i:s'));

            while ($cursor->copy()->addMinutes($slotDuration)->lte($end)) {
                $candidateSlots->push([
                    'start_time' => $cursor->format('H:i:s'),
                    'end_time' => $cursor->copy()->addMinutes($slotDuration)->format('H:i:s'),
                ]);

                $cursor->addMinutes($slotDuration);
            }
        }

        return $candidateSlots
            ->unique(fn($s) => $s['start_time'] . '-' . $s['end_time'])
            ->values();
    }
}
