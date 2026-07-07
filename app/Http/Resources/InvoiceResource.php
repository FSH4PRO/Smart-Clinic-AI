<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'clinic_id'       => $this->clinic_id,
            'patient_id'      => $this->patient_id,
            'appointment_id'  => $this->appointment_id,
            'amount'          => (float) $this->amount,
            'currency'        => $this->currency,
            'status'          => $this->status,
            'payment_method'  => $this->payment_method,
            'payment_gateway' => $this->payment_gateway,
            'gateway_ref'     => $this->gateway_ref,
            'paid_at'         => $this->paid_at ? $this->paid_at->toDateTimeString() : null,
            'created_at'      => $this->created_at->toDateTimeString(),
        ];
    }
}
