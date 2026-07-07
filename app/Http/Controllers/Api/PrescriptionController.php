<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IssuePrescriptionRequest;
use App\Http\Resources\PrescriptionResource;
use App\Services\PrescriptionIssueService;
use Illuminate\Http\JsonResponse;
use Exception;

final class PrescriptionController extends BaseController
{
    public function __construct(
        private readonly PrescriptionIssueService $prescriptionService
    ) {}

    /**
     * Store a newly issued prescription.
     */
    public function store(IssuePrescriptionRequest $request): JsonResponse
    {
        try {
            $prescription = $this->prescriptionService->issue($request->validated());

            return $this->successResponse(
                new PrescriptionResource($prescription),
                'Prescription issued successfully.',
                201
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                null,
                'Failed to issue prescription.'
            );
        }
    }
}
