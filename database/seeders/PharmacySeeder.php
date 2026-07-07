<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\Pharmacy;
use Illuminate\Database\Seeder;

class PharmacySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Get existing clinics in the system
        $clinics = Clinic::all();

        // 2. Fallback: If no clinics exist yet, generate 3 placeholder clinics
        if ($clinics->isEmpty()) {
            $clinics = Clinic::factory()->count(3)->create();
        }

        // 3. Attach 2 pharmacies to every clinic in the database
        foreach ($clinics as $clinic) {
            Pharmacy::factory()
                ->count(2)
                ->create([
                    'clinic_id' => $clinic->id,
                ]);
        }
    }
}