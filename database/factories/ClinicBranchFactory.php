<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ClinicBranchFactory extends Factory
{
    public function definition(): array
    {
        return [
            // clinic_id سيتم تمريره من الـ Seeder
            'name' => fake()->streetName() . ' Branch',
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'is_main' => false,
        ];
    }
}
