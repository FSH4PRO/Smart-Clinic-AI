<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Service\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class InvoiceController extends BaseController
{
    protected InvoiceService $invoiceService;

    // Standard structural dependency injection
    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    public function store(CreateInvoiceRequest $request): JsonResponse
    {
        try {
            $invoice = $this->invoiceService->createInvoice($request->validated());

            return $this->successResponse(new InvoiceResource($invoice), 'Invoice generated successfully.', 201);
        } catch (Exception $e) {
            Log::error('Invoice Generation Failure: ' . $e->getMessage(), [
                'input' => $request->all()
            ]);

            return $this->errorResponse(null, 'Failed to generate invoice. Please try again later.', 500);
        }
    }
}
