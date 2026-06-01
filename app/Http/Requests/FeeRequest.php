<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route already behind role:tutor
    }

    public function rules(): array
    {
        return [
            'fee_rate_per_hour' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
