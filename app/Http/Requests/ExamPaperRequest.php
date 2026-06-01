<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExamPaperRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route already behind role:tutor
    }

    public function rules(): array
    {
        return [
            'title'   => ['required', 'string', 'max:255'],
            'level'   => ['required', Rule::in(config('wowlo.levels'))],
            'subject' => ['required', Rule::in(config('wowlo.subjects'))],
            'year'    => ['required', 'integer', 'min:1990', 'max:' . (now()->year + 2)],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'file'    => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'level.in'      => 'Please choose a level from the list.',
            'subject.in'    => 'Please choose a subject from the list.',
            'file.uploaded' => 'The file is too large to upload (server limit). Try a smaller file.',
            'file.max'      => 'The file must be 10 MB or smaller.',
            'file.mimes'    => 'Only PDF, JPG, or PNG files are allowed.',
        ];
    }
}
