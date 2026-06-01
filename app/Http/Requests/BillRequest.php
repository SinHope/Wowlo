<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route already behind role:tutor
    }

    /**
     * Drop completely-empty additional-charge rows before validating so the
     * tutor can leave spare rows blank.
     */
    protected function prepareForValidation(): void
    {
        $charges = collect($this->input('charges', []))
            ->reject(fn ($c) => blank($c['description'] ?? null) && blank($c['amount'] ?? null))
            ->values()
            ->all();

        $this->merge(['charges' => $charges]);
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', Rule::exists('users', 'id')->where('role', 'student')],
            'billing_month' => ['required', 'date_format:Y-m'],

            'lessons' => ['required', 'array', 'min:1'],
            'lessons.*.lesson_date' => ['required', 'date'],
            'lessons.*.hours' => ['required', 'numeric', 'min:0.25', 'max:24'],

            'charges' => ['nullable', 'array'],
            'charges.*.description' => ['required', 'string', 'max:255'],
            'charges.*.amount' => ['required', 'numeric', 'min:0', 'max:999999.99'],
        ];
    }

    public function attributes(): array
    {
        return [
            'student_id' => 'student',
            'lessons' => 'lessons',
            'lessons.*.lesson_date' => 'lesson date',
            'lessons.*.hours' => 'lesson hours',
            'charges.*.description' => 'charge description',
            'charges.*.amount' => 'charge amount',
        ];
    }

    public function messages(): array
    {
        return [
            'lessons.required' => 'Add at least one lesson line.',
            'lessons.min' => 'Add at least one lesson line.',
        ];
    }
}
