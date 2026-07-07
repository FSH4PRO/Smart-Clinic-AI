<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ClinicFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->company() . ' Medical Center';

        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(4),
            'license_number' => fake()->unique()->numerify('CL-######'),
            'subscription_plan' => fake()->randomElement(['free', 'basic', 'pro', 'enterprise']),
            'subscription_ends_at' => now()->addYear(),
            'country' => fake()->country(),
            'city' => fake()->city(),
            'address' => fake()->address(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'settings' => [
                'currency' => 'USD',
                'locale' => 'en',
                'theme_color' => '#3490dc',
            ],
        ];
    }
}
