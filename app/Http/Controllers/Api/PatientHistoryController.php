<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetPatientHistoryRequest;
use App\Http\Resources\PatientHistoryResource;
use App\Services\PatientHistoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class PatientHistoryController extends Controller
{
    public function __construct(
        private readonly PatientHistoryService $historyService
    ) {}

    public function show(GetPatientHistoryRequest $request, string $id): JsonResponse
    {
        try {
            $patient = $this->historyService->getFullHistory($id);

            return (new PatientHistoryResource($patient))
                ->additional([
                    'success' => true,
                    'message' => 'Patient medical history retrieved successfully.',
                    'errors'  => null
                ])
                ->response()
                ->setStatusCode(200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'Patient not found.',
                'errors'  => ['patient' => ['The requested patient profile does not exist.']]
            ], 404);
        }
    }
}