<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\BookAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Services\AppointmentBookingService;
use Illuminate\Http\JsonResponse;

class AppointmentBookingController extends BaseController
{
    public function book(BookAppointmentRequest $request, AppointmentBookingService $service): JsonResponse
    {
        $appointment = $service->book($request->validated());

        return $this->successResponse(
            new AppointmentResource($appointment),
            'Appointment booked successfully',
            201
        );
    }
}
