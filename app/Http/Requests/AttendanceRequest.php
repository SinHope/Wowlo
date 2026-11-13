<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Save (PUT) and Add-to-Billing (POST) share one payload: the ticks the tutor
 * changed (`added` / `removed`), the view state to return to, and — for billing
 * only — the date window the bill should cover.
 *
 * tutor_id / student_id are deliberately NOT accepted: the student comes from the
 * URL (ownership-checked in the controller) and the tutor from the session.
 */
class AttendanceRequest extends FormRequest
{
    /** Longest date window Add-to-Billing may cover (days, inclusive). */
    public const MAX_WINDOW_DAYS = 366;

    public function authorize(): bool
    {
        return true; // route already behind role:tutor,super_admin
    }

    public function rules(): array
    {
        // Later dates may be ticked and saved (the UI warns first) — but not absurdly far ahead.
        $latest = now()->addYear()->toDateString();
        $dateRules = ['date_format:Y-m-d', 'after_or_equal:2000-01-01', 'before_or_equal:'.$latest, 'distinct'];

        $window = $this->routeIs('tutor.attendance.billing') ? ['required'] : ['nullable'];

        return [
            'added' => ['nullable', 'array', 'max:400'],
            'added.*' => $dateRules,
            'removed' => ['nullable', 'array', 'max:400'],
            'removed.*' => $dateRules,

            // Where to land after saving.
            'view' => ['nullable', Rule::in(['calendar', 'list'])],
            'month' => ['nullable', 'date_format:Y-m'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],

            // The dates Add-to-Billing covers (the visible month / filtered range).
            'window_from' => [...$window, 'date_format:Y-m-d'],
            'window_to' => [...$window, 'date_format:Y-m-d', 'after_or_equal:window_from'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $from = $this->input('window_from');
                $to = $this->input('window_to');

                if (! $from || ! $to || $validator->errors()->hasAny(['window_from', 'window_to'])) {
                    return;
                }

                if (Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1 > self::MAX_WINDOW_DAYS) {
                    $validator->errors()->add('window_to', 'Pick a date range of '.self::MAX_WINDOW_DAYS.' days or fewer.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'added.*' => 'ticked date',
            'removed.*' => 'unticked date',
            'window_from' => 'start date',
            'window_to' => 'end date',
        ];
    }

    public function messages(): array
    {
        return [
            'added.*.before_or_equal' => 'A ticked date can be at most one year from today.',
            'removed.*.before_or_equal' => 'An unticked date can be at most one year from today.',
            'added.max' => 'Too many changes at once. Save in smaller batches.',
            'removed.max' => 'Too many changes at once. Save in smaller batches.',
        ];
    }
}
