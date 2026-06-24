<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PatchNoteRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'version' => ['nullable', 'string', 'max:50'],
            // Raw rich-text HTML; sanitised to the allowlist in the controller.
            'body' => ['required', 'string', 'max:50000'],
            // Optional attached image (stored on R2).
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            // Edit only: tick to drop the current image.
            'remove_image' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Give the patch note a title.',
            'body.required' => 'Write what changed in this update.',
            'image.max' => 'The image must be 4 MB or smaller.',
        ];
    }
}
