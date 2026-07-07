<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class DoctorFactory extends Factory
{
    public function definition(): array
    {
        $user = \App\Models\User::factory()->create(['role' => \App\Enums\UserRole::DOCTOR->value]);

        return [

            // user_id, clinic_id, branch_id سيتم تمريرهم من الـ Seeder
            'specialty' => fake()->randomElement(['Cardiology', 'General', 'Pediatrics', 'Neurology', 'Dentistry']),
            'bio' => fake()->paragraph(),
            'consultation_fee' => fake()->randomFloat(2, 50, 300),
            'license_number' => fake()->unique()->numerify('MD-#######'),
            'years_experience' => fake()->numberBetween(2, 30),
            'ai_summary_enabled' => true,
        ];
    }
}
