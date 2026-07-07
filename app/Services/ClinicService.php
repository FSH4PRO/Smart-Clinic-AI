<?php

namespace App\Services;

use App\Models\Clinic;

class ClinicService
{
    public function getClinicBySlug(string $slug)
    {
        
        return Clinic::with(['doctors'])
            ->where('slug', $slug)
            ->firstOrFail();
    }

    public function getClinicsDoctors(Clinic $clinic)
    {
        return $clinic->doctors->load('schedules');
    }
}
