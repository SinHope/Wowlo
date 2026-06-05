<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesEmail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentRequest extends FormRequest
{
    use NormalizesEmail;

    /**
     * Only tutors reach here (route is behind role:tutor), so authorize freely.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules. Works for both create and update — on update the
     * password is optional and the email-unique check ignores the current student.
     */
    public function rules(): array
    {
        $studentId = $this->route('student')?->id;
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($studentId)],
            'password' => [$isUpdate ? 'nullable' : 'required', 'confirmed', 'min:8'],

            // At least one phone is required (enforced in withValidator below).
            'phone_1' => ['nullable', 'string', 'max:30'],
            'phone_2' => ['nullable', 'string', 'max:30'],
            'phone_3' => ['nullable', 'string', 'max:30'],
            'phone_4' => ['nullable', 'string', 'max:30'],
            'phone_5' => ['nullable', 'string', 'max:30'],

            'address' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Require at least one of the five phone numbers.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $phones = array_filter([
                $this->phone_1, $this->phone_2, $this->phone_3, $this->phone_4, $this->phone_5,
            ], fn ($v) => filled($v));

            if (count($phones) === 0) {
                $validator->errors()->add('phone_1', 'Please provide at least one phone number.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'phone_1' => 'student phone',
            'phone_2' => "father's phone",
            'phone_3' => "mother's phone",
            'phone_4' => 'next-of-kin phone',
            'phone_5' => 'home phone',
        ];
    }
}
