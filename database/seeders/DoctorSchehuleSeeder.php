<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\DoctorSchehule;
use Illuminate\Database\Seeder;

class DoctorSchehuleSeeder extends Seeder
{
    public function run(): void
    {
        $slotDuration = 30; // minutes
        $startTime = '09:00:00';
        $endTime = '17:00:00';

        $daysOfWeek = range(0, 6); // 0=Sun ... 6=Sat (matches DoctorSlotService)

        Doctor::query()->each(function (Doctor $doctor) use ($daysOfWeek, $startTime, $endTime, $slotDuration) {
            foreach ($daysOfWeek as $dayOfWeek) {
                DoctorSchehule::query()->updateOrCreate(
                    [
                        'doctor_id' => $doctor->id,
                        'day_of_week' => $dayOfWeek,
                    ],
                    [
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'slot_duration_minutes' => $slotDuration,
                        'is_active' => true,
                    ]
                );
            }
        });
    }
}
