<?php

namespace App\Service;

use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    /**
     * Create a pending invoice entry safely within a database transaction.
     */
    public function createInvoice(array $data): Invoice
    {
        return DB::transaction(function () use ($data) {
            // Set Blueprint implicit defaults for a newly issued manual invoice
            $data['status'] = 'pending';
            
            // Set payment gateway variables if card is chosen upfront
            if ($data['payment_method'] === 'card') {
                // If it is card, prep configuration for Phase 4 gateway routing
                $data['payment_gateway'] = 'stripe'; 
            } else {
                $data['payment_gateway'] = null;
            }

            $data['gateway_ref'] = null;
            $data['paid_at'] = null;

            return Invoice::create($data);
        });
    }
}
