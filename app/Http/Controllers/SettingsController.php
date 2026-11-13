<?php

namespace App\Http\Controllers;

use App\Support\AppBackgrounds;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Per-user appearance settings (theme + app background). Always acts on the
 * authenticated user — no ID in the URL, so there is nothing to cross-access.
 */
class SettingsController extends Controller
{
    public function edit(Request $request): View
    {
        return view('settings.edit', [
            'user'        => $request->user(),
            'backgrounds' => AppBackgrounds::all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'theme'          => ['required', Rule::in(config('wowlo.themes'))],
            'card_opacity'   => ['sometimes', 'integer', 'between:' . config('wowlo.card_opacity_min') . ',100'],
            'app_background' => ['nullable', 'string', 'max:255', function ($attr, $value, $fail) {
                if (! AppBackgrounds::isValid($value)) {
                    $fail('That background is not available.');
                }
            }],
        ]);

        $changes = ['theme' => $data['theme']];
        if (array_key_exists('card_opacity', $data)) {
            $changes['card_opacity'] = (int) $data['card_opacity'];
        }
        // The form always sends app_background ("" = none). If it's absent, leave it alone.
        if (array_key_exists('app_background', $data)) {
            $changes['app_background'] = $data['app_background'] ?: null;
        }

        $request->user()->forceFill($changes)->save();

        return back()->with('status', 'settings-updated');
    }
}
