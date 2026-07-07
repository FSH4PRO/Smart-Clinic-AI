<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Clinic;
use App\Models\ClinicBranch;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Clinic admin (idempotent)
        $clinicOwner = User::firstOrCreate(
            ['email' => 'admin@smartclinic.ai'],
            ['role' => 'clinic_admin']
        );

        // 2. Clinic (idempotent by slug)
        $clinic = Clinic::query()->updateOrCreate(
            ['slug' => 'smartclinic-main'],
            [
                'owner_id' => $clinicOwner->id,
                'name' => 'SmartClinic Main Hospital',
                'subscription_plan' => 'enterprise',
            ]
        );

        // 3. Branches (idempotent-ish)
        $mainBranch = ClinicBranch::query()->updateOrCreate(
            ['clinic_id' => $clinic->id, 'name' => 'Main Headquarters'],
            [
                'address' => fake()->address(),
                'phone' => fake()->phoneNumber(),
                'is_main' => true,
            ]
        );

        $subBranch = ClinicBranch::query()->updateOrCreate(
            ['clinic_id' => $clinic->id, 'name' => 'Second Branch'],
            [
                'address' => fake()->address(),
                'phone' => fake()->phoneNumber(),
                'is_main' => false,
            ]
        );

        // 4. Doctors (ensure 5 exist; create missing)
        $existingDoctorUsersCount = User::query()
            ->where('role', 'doctor')
            ->count();

        $missingDoctorCount = max(0, 5 - $existingDoctorUsersCount);

        if ($missingDoctorCount > 0) {
            User::factory()->count($missingDoctorCount)->create(['role' => 'doctor']);
        }

        $doctorUsers = User::query()->where('role', 'doctor')->get();

        foreach ($doctorUsers as $user) {
            Doctor::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'clinic_id' => $clinic->id,
                    'branch_id' => fake()->randomElement([$mainBranch->id, $subBranch->id]),
                    // Provide required doctor fields if they are missing in existing rows
                    'specialty' => 'General',
                    'bio' => $user->name ? ('Doctor profile for ' . $user->name) : 'Doctor bio',
                    'consultation_fee' => 100.00,
                    'license_number' => 'MD-' . substr((string) $user->id, 0, 6),
                    'years_experience' => 5,
                    'ai_summary_enabled' => true,
                ]
            );
        }


        // 5. Patients (ensure 15 exist; create missing)
        $existingPatientsCount = User::query()->where('role', 'patient')->count();
        $missingPatientCount = max(0, 15 - $existingPatientsCount);

        if ($missingPatientCount > 0) {
            User::factory()->count($missingPatientCount)->create(['role' => 'patient']);
        }

        $patientUsers = User::query()->where('role', 'patient')->get();

        foreach ($patientUsers as $user) {
            Patient::query()->updateOrCreate(
                ['user_id' => $user->id],
                []
            );
        }

        // 6. Doctor schedules
        $this->call(DoctorSchehuleSeeder::class);

        // 7. Appointments
        $this->call(AppointmentSeeder::class);
    }
}
