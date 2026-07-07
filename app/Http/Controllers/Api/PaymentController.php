<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProcessPaymentRequest;
use App\Models\Invoice;
use App\Services\PaymentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    use AuthorizesRequests;
    
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Process invoice payment via gateway endpoint interface.
     */
    public function pay(ProcessPaymentRequest $request, Invoice $invoice): JsonResponse
    {
        try {
            // 1. Enforce privacy policy authorization check
            $this->authorize('pay', $invoice);

            // 2. Pass validated payload array parameters directly into your domain service
            $paymentPayload = $this->paymentService->initializePayment($invoice, $request->validated());

            // 3. Dispatch structured JSON envelope wrapper back to client app
            return response()->json([
                'success' => true,
                'data'    => $paymentPayload,
                'message' => 'Payment gateway intent initialized successfully.'
            ], 200);

        } catch (\Throwable $e) {
            // Log the complete traceback footprint for engineering audits
            Log::error('Gateway Transaction Initialization Aborted: ' . $e->getMessage(), [
                'invoice_id' => $invoice->id,
                'exception'  => get_class($e),
                'file'       => $e->getFile(),
                'line'       => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'Transaction failure: ' . $e->getMessage(),
                'errors'  => [
                    'exception_type' => get_class($e),
                    'line'           => $e->getLine()
                ]
            ], 500);
        }
    }
}