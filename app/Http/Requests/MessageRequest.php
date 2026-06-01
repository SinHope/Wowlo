<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route already behind role:tutor
    }

    public function rules(): array
    {
        return [
            'receiver_id' => ['required', Rule::exists('users', 'id')->where('role', 'student')],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'receiver_id' => 'student',
        ];
    }
}
