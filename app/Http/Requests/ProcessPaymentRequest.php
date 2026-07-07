<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProcessPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Allow switching to a live card gateway or wallet integration during checkout
            'payment_method'  => ['required', 'string', 'in:card,wallet'],
            'payment_gateway' => ['required', 'string', 'in:stripe,myfatoorah'],
        ];
    }
}
