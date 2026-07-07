<?php

namespace App\Services;

use App\Models\Invoice;
use Exception;
use Illuminate\Support\Facades\DB;
use Stripe\StripeClient;

class PaymentService
{
    protected StripeClient $stripe;

    public function __construct()
    {
        // Fallback array structure ensures configurations map natively inside your Windows workspace
        $this->stripe = new StripeClient(config('services.stripe.secret') ?? env('STRIPE_SECRET'));
    }

    /**
     * Initialize payment processing with the gateway.
     */
    public function initializePayment(Invoice $invoice, array $paymentDetails): array
    {
        // FIX: Extract string value from the enum object structure safely
        if ($invoice->status->value === 'paid' || $invoice->status->value === 'settled') {
            throw new Exception("This invoice has already been fully settled.");
        }

        return DB::transaction(function () use ($invoice, $paymentDetails) {
            
            // 1. Map intended transaction state details safely
            $invoice->update([
                'payment_method'  => $paymentDetails['payment_method'],
                'payment_gateway' => $paymentDetails['payment_gateway'],
            ]);

            // 2. Branch matching logic for gateways (Stripe Implementation)
            if ($paymentDetails['payment_gateway'] === 'stripe') {
                
                // Construct standard currency conversion constraints (Stripe calculates in cents)
                $amountInCents = (int) bcmul($invoice->amount, '100');

                // Initialize a Payment Intent with Stripe
                $intent = $this->stripe->paymentIntents->create([
                    'amount' => $amountInCents,
                    'currency' => strtolower($invoice->currency),
                    'metadata' => [
                        'invoice_id'     => $invoice->id,
                        'patient_id'     => $invoice->patient_id,
                        'clinic_id'      => $invoice->clinic_id,
                        'appointment_id' => $invoice->appointment_id,
                    ],
                ]);

                // Cache reference key for incoming asynchronous Webhook confirmation matching
                $invoice->update([
                    'gateway_ref' => $intent->id
                ]);

                return [
                    'gateway'       => 'stripe',
                    'client_secret' => $intent->client_secret, // Consumed directly by Flutter Stripe SDK
                    'invoice_id'    => $invoice->id,
                    'amount'        => (float) $invoice->amount,
                    'currency'      => $invoice->currency
                ];
            }

            throw new Exception("Selected billing gateway protocol is unsupported.");
        });
    }
}