<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesEmail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TutorRequest extends FormRequest
{
    use NormalizesEmail;

    /**
     * Only the super_admin reaches here (route is behind role:super_admin).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Works for both create and update — on update the password is optional and
     * the email-unique check ignores the current tutor.
     */
    public function rules(): array
    {
        $tutorId = $this->route('tutor')?->id;
        $isUpdate = $this->isMethod('put') || $this->isMethod('patch');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($tutorId)],
            'password' => [$isUpdate ? 'nullable' : 'required', 'confirmed', 'min:8'],
            'phone_1' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
