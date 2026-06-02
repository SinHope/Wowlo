<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HomeworkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route already behind role:tutor
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'subject' => ['required', Rule::in(config('wowlo.subjects'))],
            'description' => ['required', 'string'],
            'student_id' => ['required', Rule::exists('users', 'id')->where('role', 'student')],
            'start_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:start_date'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:25600'], // 25 MB
        ];
    }

    public function attributes(): array
    {
        return [
            'student_id' => 'student',
        ];
    }

    public function messages(): array
    {
        return [
            'subject.in' => 'Please choose a subject from the list.',
            'start_date.required' => 'Please choose a start date.',
            'attachment.uploaded' => 'The file is too large to upload (server limit). Please use a smaller file.',
            'attachment.max' => 'The attachment must be 25MB or smaller.',
            'attachment.mimes' => 'The attachment must be a PDF, Word document, or image (jpg/png).',
        ];
    }
}
