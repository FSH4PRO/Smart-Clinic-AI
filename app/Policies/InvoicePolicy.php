<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function pay(User $user, Invoice $invoice): bool
    {
        // FIX: Extract the raw string value from the Enum object safely
        $userRole = $user->role instanceof \BackedEnum ? $user->role->value : $user->role;

        // 1. Is the logged-in user actually a patient?
        if ($userRole !== 'patient') {
            return false;
        }

        // 2. Ensure the relationship exists and the user_id matches
        if (!$invoice->patient) {
            return false;
        }

        // Does this invoice actually belong to the logged-in patient?
        return $invoice->patient->user_id === $user->id;
    }
}