<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\DoctorSlotsRequest;
use App\Http\Resources\DoctorSlotResource;
use App\Models\Doctor;
use App\Services\DoctorSlotService;
use Illuminate\Http\JsonResponse;

class DoctorSlotController extends BaseController
{
    public function slots(DoctorSlotsRequest $request, DoctorSlotService $slotService, Doctor $doctor): JsonResponse
    {
        $date = $request->validated('date');

        $doctor = $doctor->load('schedules');

        $slots = $slotService->getAvailableSlots($doctor, $date);

        return $this->successResponse(
            DoctorSlotResource::collection($slots),
            'Available slots retrieved successfully'
        );
    }
}
