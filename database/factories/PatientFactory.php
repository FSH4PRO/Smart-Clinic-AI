<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PatientFactory extends Factory
{
    public function definition(): array
    {
        return [
            // user_id سيتم تمريره من الـ Seeder

            'date_of_birth' => fake()->dateTimeBetween('-60 years', '-5 years')->format('Y-m-d'),

            'gender' => fake()->randomElement(['male', 'female', 'other']),
            'blood_type' => fake()->randomElement(['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-']),
            'national_id' => fake()->unique()->numerify('ID-##########'),
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_phone' => fake()->phoneNumber(),
            'allergies' => fake()->randomElements(['Penicillin', 'Peanuts', 'Dust', 'Latex'], fake()->numberBetween(0, 2)),
            'chronic_conditions' => fake()->randomElements(['Diabetes', 'Hypertension', 'Asthma'], fake()->numberBetween(0, 2)),
        ];
    }
}
