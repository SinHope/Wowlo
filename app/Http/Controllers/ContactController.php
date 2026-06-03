<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function show(): View
    {
        return view('contact');
    }

    public function send(Request $request): RedirectResponse
    {
        // Honeypot: bots fill the hidden "website" field; humans never see it.
        // Pretend success so the bot moves on without learning it was caught.
        if (filled($request->input('website'))) {
            return back()->with('status', 'Thanks! Your message has been sent.');
        }

        $data = $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'email'   => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        try {
            Mail::to(config('wowlo.contact_email'))->send(new ContactMessage($data));
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->withErrors(['message' => 'Sorry — something went wrong sending your message. Please try again shortly.']);
        }

        return back()->with('status', "Thanks! Your message has been sent. We'll get back to you soon.");
    }
}
