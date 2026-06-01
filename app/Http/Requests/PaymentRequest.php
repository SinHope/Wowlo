<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route already behind role:tutor
    }

    public function rules(): array
    {
        return [
            'amount_paid' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'payment_date' => ['required', 'date', 'before_or_equal:today'],
            'payment_method' => ['required', Rule::in(['cash', 'bank_transfer', 'paynow', 'paypal'])],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
