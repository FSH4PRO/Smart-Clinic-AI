<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Enums\BookingSource;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class AppointmentSeeder extends Seeder
{
    public function run(): void
    {
        $startDate = Carbon::today()->addDay(); // tomorrow
        $daysToSeed = 7; // next 7 days

        $appointmentsPerDoctorPerDay = 2; // reasonable density

        $patients = Patient::query()->inRandomOrder()->get();
        if ($patients->isEmpty()) {
            return;
        }

        $appointmentTypes = [
            AppointmentType::IN_PERSON->value,
            AppointmentType::VIDEO->value,
            AppointmentType::HOME_VISIT->value,
        ];

        $statuses = [
            AppointmentStatus::CONFIRMED->value,
            AppointmentStatus::PENDING->value,
        ];

        $sources = [
            BookingSource::APP->value,
            BookingSource::WALK_IN->value,
            BookingSource::ADMIN->value,
        ];

        Doctor::query()->with('branch')->each(function (Doctor $doctor) use (
            $patients,
            $startDate,
            $daysToSeed,
            $appointmentsPerDoctorPerDay,
            $appointmentTypes,
            $statuses,
            $sources
        ) {
            // Build appointment windows that match DoctorSchehuleSeeder (09:00-17:00, 30-min slots)
            $candidateStarts = ['09:00:00', '09:30:00', '10:00:00', '10:30:00', '11:00:00', '11:30:00', '12:00:00', '12:30:00', '13:00:00', '13:30:00', '14:00:00', '14:30:00', '15:00:00', '15:30:00', '16:00:00', '16:30:00'];

            for ($d = 0; $d < $daysToSeed; $d++) {
                $date = $startDate->copy()->addDays($d);

                // Shuffle patients to vary who books
                $shuffledPatients = $patients->shuffle();

                // Create a couple of appointments for this doctor/day, avoiding overlaps for the same doctor/day.
                $created = 0;
                $usedSlots = [];

                foreach (collect($candidateStarts)->shuffle() as $startTime) {
                    if ($created >= $appointmentsPerDoctorPerDay) {
                        break;
                    }

                    $endTime = Carbon::parse($startTime)->addMinutes(30)->format('H:i:s');

                    // skip overlap with already-created appointments of this doctor/day
                    $overlaps = collect($usedSlots)->contains(function ($slot) use ($startTime, $endTime) {
                        $slotStart = Carbon::parse($slot['start_time']);
                        $slotEnd = Carbon::parse($slot['end_time']);
                        $newStart = Carbon::parse($startTime);
                        $newEnd = Carbon::parse($endTime);

                        return $newStart->lt($slotEnd) && $newEnd->gt($slotStart);
                    });

                    if ($overlaps) {
                        continue;
                    }

                    $patient = $shuffledPatients->shift();
                    if (!$patient) {
                        break;
                    }

                    $type = $appointmentTypes[array_rand($appointmentTypes)];
                    $status = $statuses[array_rand($statuses)];
                    $source = $sources[array_rand($sources)];

                    // Ensure clinic/branch consistency based on doctor
                    Appointment::create([
                        'patient_id' => $patient->id,
                        'doctor_id' => $doctor->id,
                        'clinic_id' => $doctor->clinic_id,
                        'branch_id' => $doctor->branch_id,
                        'appointment_date' => $date->toDateString(),
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'type' => $type,
                        'status' => $status,
                        'booking_source' => $source,
                        'chief_complaint' => 'General consultation',
                        'triage_score' => fake()->numberBetween(0, 10),
                        'no_show_risk' => fake()->randomFloat(2, 0.0, 0.9),
                        'notes' => null,
                        'cancelled_at' => null,
                        'cancellation_reason' => null,
                    ]);

                    $usedSlots[] = [
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                    ];

                    $created++;
                }
            }
        });
    }
}
