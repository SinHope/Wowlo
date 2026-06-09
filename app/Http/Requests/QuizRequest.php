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
            'questions.*.question_type'   => ['required', Rule::in(['mcq', 'short_answer'])],
            'questions.*.question_text'   => ['required', 'string'],
            // MCQ-only fields — required only when this question is an MCQ.
            'questions.*.option_a'        => ['nullable', 'required_if:questions.*.question_type,mcq', 'string', 'max:255'],
            'questions.*.option_b'        => ['nullable', 'required_if:questions.*.question_type,mcq', 'string', 'max:255'],
            'questions.*.option_c'        => ['nullable', 'required_if:questions.*.question_type,mcq', 'string', 'max:255'],
            'questions.*.option_d'        => ['nullable', 'required_if:questions.*.question_type,mcq', 'string', 'max:255'],
            'questions.*.correct_answer'  => ['nullable', 'required_if:questions.*.question_type,mcq', Rule::in(['A', 'B', 'C', 'D'])],
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
            'questions.*.question_text.required'     => 'Question text is required.',
            'questions.*.option_a.required_if'       => 'Option A is required for MCQ questions.',
            'questions.*.option_b.required_if'       => 'Option B is required for MCQ questions.',
            'questions.*.option_c.required_if'       => 'Option C is required for MCQ questions.',
            'questions.*.option_d.required_if'       => 'Option D is required for MCQ questions.',
            'questions.*.correct_answer.required_if' => 'Mark the correct answer for MCQ questions.',
            'questions.*.marks.required'             => 'Marks are required.',
        ];
    }
}
