<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Services\AppointmentCancelService;
use App\Services\AppointmentShowService;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\CancelAppointmentRequest;

class AppointmentController extends BaseController
{
    public function show(Appointment $appointment, AppointmentShowService $service): JsonResponse
    {
        $appointment = $service->getAccessibleAppointment($appointment);

        return $this->successResponse(
            new AppointmentResource($appointment),
            'Appointment details retrieved successfully'
        );
    }

    public function cancel(Appointment $appointment, CancelAppointmentRequest $request, AppointmentCancelService $service): JsonResponse
    {
        $appointment = $service->cancel($appointment, $request->validated());

        return $this->successResponse(
            new AppointmentResource($appointment),
            'Appointment cancelled successfully'
        );
    }
}
