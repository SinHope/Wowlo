<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-seo-meta
        title="Contact Us — Wowlo"
        description="Get in touch with Wowlo — questions about the tuition app, tutor access, or anything else." />

    <link rel="icon" type="image/x-icon" href="{{ asset('images/favicon/wowlo_favicon.ico') }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#7C3AED">
    <link rel="apple-touch-icon" href="{{ asset('images/pwa/icon-192.png') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=nunito:300,400,600,700,800,900&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>[x-cloak] { display: none !important; }</style>

    <x-posthog />
</head>
<body class="bg-cream font-sans text-ink antialiased">

    {{-- ───────────────────────── Nav ───────────────────────── --}}
    <header class="border-b border-gray-100 bg-cream/90 backdrop-blur">
        <nav class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3 sm:px-6">
            <a href="{{ url('/') }}" class="flex items-center gap-2">
                <img src="{{ asset('images/logo/wowlo_logo.png') }}" alt="Wowlo" class="h-20 w-auto">
            </a>
            <div class="flex items-center gap-4 sm:gap-6">
                <a href="{{ route('about') }}" class="text-sm font-semibold text-ink transition-colors hover:text-primary-dark cursor-pointer">About</a>
                <a href="{{ route('contact') }}" class="text-sm font-bold text-primary-dark cursor-pointer">Contact</a>
                @auth
                    <a href="{{ route('dashboard') }}"
                       class="rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white shadow-sm transition-colors duration-200 hover:bg-primary-dark cursor-pointer">
                        Go to Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}"
                       class="rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white shadow-sm transition-colors duration-200 hover:bg-primary-dark cursor-pointer">
                        Log in
                    </a>
                @endauth
            </div>
        </nav>
    </header>

    {{-- ───────────────────────── Contact ───────────────────────── --}}
    <main class="px-4 py-14 sm:px-6 sm:py-20">
        <div class="mx-auto max-w-2xl">
            <div class="text-center">
                <span class="inline-flex items-center gap-2 rounded-full bg-primary/10 px-3 py-1 text-xs font-bold text-primary-dark">
                    <span class="h-1.5 w-1.5 rounded-full bg-primary"></span>
                    We'd love to hear from you
                </span>
                <h1 class="mt-4 text-3xl font-extrabold tracking-tight sm:text-4xl">Contact us</h1>
                <p class="mx-auto mt-3 max-w-xl text-lg leading-relaxed text-muted">
                    Questions about Wowlo, getting set up as a tutor, or your account? Send a message and we'll get back to you.
                </p>
            </div>

            <div class="mt-10 rounded-3xl border border-gray-100 bg-white p-6 shadow-sm sm:p-8">

                @if (session('status'))
                    <div x-data="{ show: true }" x-show="show" x-transition
                         class="mb-6 flex items-center justify-between rounded-xl border border-success/30 bg-success/10 px-4 py-3">
                        <p class="text-sm font-semibold text-success">{{ session('status') }}</p>
                        <button @click="show = false" class="text-success cursor-pointer" aria-label="Dismiss">&times;</button>
                    </div>
                @endif

                <form method="POST" action="{{ route('contact.send') }}"
                      x-data="spinner('Sending message…')" @submit="start()" class="space-y-5">
                    @csrf

                    {{-- Honeypot — hidden from humans; bots that fill it are silently dropped. --}}
                    <div class="hidden" aria-hidden="true">
                        <label for="website">Website</label>
                        <input id="website" type="text" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-semibold text-ink" for="name">Your name</label>
                            <input id="name" name="name" type="text" value="{{ old('name') }}" required
                                   class="mt-1 block w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm transition-colors focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary @error('name') border-danger @enderror">
                            @error('name') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-ink" for="email">Email</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" required
                                   class="mt-1 block w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm transition-colors focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary @error('email') border-danger @enderror">
                            @error('email') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-ink" for="subject">Subject</label>
                        <input id="subject" name="subject" type="text" value="{{ old('subject') }}" required
                               class="mt-1 block w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm transition-colors focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary @error('subject') border-danger @enderror">
                        @error('subject') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-ink" for="message">Message</label>
                        <textarea id="message" name="message" rows="5" required
                                  class="mt-1 block w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm transition-colors focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary @error('message') border-danger @enderror">{{ old('message') }}</textarea>
                        @error('message') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-1">
                        <a href="{{ url('/') }}" class="text-sm font-semibold text-muted hover:text-ink">Cancel</a>
                        <button type="submit"
                                class="inline-flex items-center gap-2 rounded-xl bg-primary px-6 py-3 text-base font-bold text-white shadow-md transition-colors duration-200 hover:bg-primary-dark cursor-pointer">
                            Send message
                        </button>
                    </div>

                    <x-spinner-overlay />
                </form>
            </div>
        </div>
    </main>

    {{-- ───────────────────────── Footer ───────────────────────── --}}
    <footer class="border-t border-gray-100 bg-white px-4 py-10 sm:px-6">
        <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 sm:flex-row">
            <div class="flex items-center gap-2">
                <img src="{{ asset('images/logo/wowlo_logo.png') }}" alt="Wowlo" class="h-10 w-auto">
            </div>
            <nav class="flex items-center gap-6 text-sm font-semibold text-muted">
                <a href="{{ route('about') }}" class="transition-colors hover:text-primary-dark cursor-pointer">About</a>
                <a href="{{ route('privacy-policy') }}" class="transition-colors hover:text-primary-dark cursor-pointer">Privacy Policy</a>
                <a href="{{ route('contact') }}" class="transition-colors hover:text-primary-dark cursor-pointer">Contact</a>
                <a href="{{ route('login') }}" class="transition-colors hover:text-primary-dark cursor-pointer">Log in</a>
            </nav>
            <p class="text-sm text-muted">&copy; {{ date('Y') }} Wowlo. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
