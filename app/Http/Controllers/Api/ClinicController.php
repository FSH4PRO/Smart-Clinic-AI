<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ClinicResource;
use App\Http\Resources\DoctorResource;
use App\Models\Clinic;
use App\Services\ClinicService;
use Illuminate\Http\JsonResponse;

class ClinicController extends BaseController
{
    protected ClinicService $clinicService;

    public function __construct(ClinicService $clinicService)
    {
        $this->clinicService = $clinicService;
    }

    public function show(string $slug): JsonResponse
    {
        $clinic = $this->clinicService->getClinicBySlug($slug);

        return $this->successResponse(new ClinicResource($clinic), 'Clinic details retrieved successfully');
    }

    public function doctors(Clinic $clinic): JsonResponse
    {
        $doctors = $this->clinicService->getClinicsDoctors($clinic);

        return $this->successResponse(DoctorResource::collection($doctors), 'Clinic doctors retrieved successfully');
    }
}
