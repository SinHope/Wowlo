<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Send the user off to Google's consent screen.
     */
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle the callback from Google.
     *
     * Accounts are created by the tutor only — we NEVER auto-create here.
     * We only log in (and link google_id to) an account that already exists.
     */
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Google sign-in failed. Please try again.']);
        }

        // 1. Google must have verified the email address.
        $verified = $googleUser->user['email_verified']
            ?? $googleUser->user['verified_email']
            ?? false;

        if (! $verified) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Your Google email is not verified.']);
        }

        // 2. Already linked? Log straight in.
        $user = User::where('google_id', $googleUser->getId())->first();

        if (! $user) {
            // 3. Match a tutor-created account by email and link it.
            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user && $user->google_id === null) {
                $user->update(['google_id' => $googleUser->getId()]);
            } elseif ($user && $user->google_id !== null) {
                // Email belongs to an account already linked to a different Google ID.
                return redirect()->route('login')
                    ->withErrors(['email' => 'This account is linked to a different Google account.']);
            }
        }

        // 4. No matching account → do not create one.
        if (! $user) {
            return redirect()->route('login')->withErrors([
                'email' => 'No account found for this Google account. Please ask your tutor to create your account.',
            ]);
        }

        Auth::login($user, remember: true);

        return redirect()->intended(route('dashboard'));
    }
}
