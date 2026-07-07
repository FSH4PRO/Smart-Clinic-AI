<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Enums\BookingSource;
use App\Models\Appointment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
final class AppointmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // required foreign keys are typically set in tests
            'appointment_date' => $this->faker->dateTimeBetween('-30 days', '+30 days')->format('Y-m-d'),
            'start_time' => $this->faker->time('H:i:s'),
            'end_time' => $this->faker->time('H:i:s'),

            'type' => AppointmentType::IN_PERSON->value,
            'status' => AppointmentStatus::PENDING->value,
            'booking_source' => BookingSource::APP->value,

            'chief_complaint' => null,
            'triage_score' => null,
            'no_show_risk' => null,
            'notes' => null,

            'cancelled_at' => null,
            'cancellation_reason' => null,
        ];
    }
}
