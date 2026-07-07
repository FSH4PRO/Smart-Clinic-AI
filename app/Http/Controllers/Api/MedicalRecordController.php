<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\CreateMedicalRecordRequest;
use App\Http\Resources\MedicalRecordResource;
use App\Services\MedicalRecordCreateService;
use Illuminate\Http\JsonResponse;

final class MedicalRecordController extends BaseController
{
    public function store(CreateMedicalRecordRequest $request, MedicalRecordCreateService $service): JsonResponse
    {
        $record = $service->createForDoctor($request->validated());

        return $this->successResponse(
            new MedicalRecordResource($record),
            'Medical record draft created successfully',
            201
        );
    }

    public function sign(\App\Http\Requests\SignMedicalRecordRequest $request, \App\Services\MedicalRecordSignService $service): JsonResponse
    {
        $record = $service->sign((string) $request->route('id'));


        return $this->successResponse(
            new MedicalRecordResource($record),
            'Medical record signed successfully',
            200
        );
    }
}
