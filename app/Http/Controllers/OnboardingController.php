<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    /**
     * Mark the welcome tour as finished (or skipped) for the current user, so it
     * never auto-shows again. Idempotent — safe to call more than once.
     *
     * Called via fetch() from the onboarding modal; returns 204 No Content.
     */
    public function complete(Request $request)
    {
        $user = $request->user();

        if (is_null($user->onboarded_at)) {
            $user->forceFill(['onboarded_at' => now()])->save();
        }

        return response()->noContent();
    }
}
