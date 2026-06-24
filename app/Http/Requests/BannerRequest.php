<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BannerRequest extends FormRequest
{
    /**
     * Only the super_admin reaches here (route is behind role:super_admin).
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Raw rich-text HTML from the compose editor. It is sanitised down
            // to the allowlist in the controller before storage — this only
            // bounds the size and requires *some* content.
            'content' => ['required', 'string', 'max:20000'],
            'audience' => ['required', Rule::in(array_keys(config('wowlo.banner_audiences')))],
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'Please type the banner message.',
            'audience.in' => 'Choose who should see this banner.',
        ];
    }
}
