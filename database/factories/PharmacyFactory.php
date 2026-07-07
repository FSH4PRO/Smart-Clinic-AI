<?php

namespace Database\Factories;

use App\Models\Pharmacy;
use App\Models\Clinic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pharmacy>
 */
class PharmacyFactory extends Factory
{
    protected $model = Pharmacy::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Automatically creates a companion Clinic record if one isn't explicitly passed
            'clinic_id' => Clinic::factory(), 
            
            'name'      => $this->faker->company() . ' Pharmacy',
            'address'   => $this->faker->address(),
            'phone'     => $this->faker->phoneNumber(),
            'is_active' => $this->faker->boolean(90), // 90% chance of being active
        ];
    }
}