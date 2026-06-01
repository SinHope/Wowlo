<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route already behind role:tutor
    }

    public function rules(): array
    {
        return [
            'title'     => ['required', 'string', 'max:255'],
            'level'     => ['required', Rule::in(config('wowlo.levels'))],
            'subject'   => ['required', Rule::in(config('wowlo.subjects'))],
            'topic'     => ['nullable', 'string', 'max:255'],
            'exam_type' => ['required', Rule::in(array_keys(config('wowlo.exam_types')))],

            'questions'                   => ['required', 'array', 'min:1'],
            'questions.*.question_text'   => ['required', 'string'],
            'questions.*.option_a'        => ['required', 'string', 'max:255'],
            'questions.*.option_b'        => ['required', 'string', 'max:255'],
            'questions.*.option_c'        => ['required', 'string', 'max:255'],
            'questions.*.option_d'        => ['required', 'string', 'max:255'],
            'questions.*.correct_answer'  => ['required', Rule::in(['A', 'B', 'C', 'D'])],
            'questions.*.marks'           => ['required', 'integer', 'min:1', 'max:100'],
            'questions.*.image'           => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,gif,webp', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'level.in'      => 'Please choose a level from the list.',
            'subject.in'    => 'Please choose a subject from the list.',
            'exam_type.in'  => 'Please choose an exam type from the list.',
            'questions.required' => 'Add at least one question.',
            'questions.min'      => 'Add at least one question.',
            'questions.*.question_text.required'  => 'Question text is required.',
            'questions.*.option_a.required'       => 'Option A is required.',
            'questions.*.option_b.required'       => 'Option B is required.',
            'questions.*.option_c.required'       => 'Option C is required.',
            'questions.*.option_d.required'       => 'Option D is required.',
            'questions.*.correct_answer.required' => 'Mark the correct answer.',
            'questions.*.marks.required'          => 'Marks are required.',
        ];
    }
}
