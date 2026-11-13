{{-- Standalone error layout. Deliberately does NOT extend layouts.app: on a 500 the
     database (sessions, auth, sidebar queries) may be the thing that's down, so this
     page touches no DB, no auth(), no route() lookups that could throw.
     Child pages set: code, title, message, icon (Heroicon path), optional hint. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex">
        <title>@yield('title') — {{ config('app.name', 'Wowlo') }}</title>
        <link rel="icon" type="image/x-icon" href="{{ asset('images/favicon/wowlo_favicon.ico') }}">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=nunito:400,600,700,800&display=swap" rel="stylesheet" />
        {{-- Only ask Vite for CSS if a build/dev server exists, so a missing manifest can never turn a 500 page into a second 500. --}}
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css'])
        @endif
    </head>
    <body class="font-sans antialiased text-ink bg-cream">
        <main class="flex min-h-screen items-center justify-center px-4 py-10">
            <div class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-8 text-center shadow-sm">
                <img src="{{ asset('images/logo/wowlo_logo.png') }}" alt="Wowlo" class="mx-auto h-24 w-auto">

                <span class="mx-auto mt-4 flex h-14 w-14 items-center justify-center rounded-full bg-primary/10 text-primary-dark">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="@yield('icon')" />
                    </svg>
                </span>

                <p class="mt-4 text-sm font-bold tracking-widest text-primary-dark">ERROR @yield('code')</p>
                <h1 class="mt-1 text-2xl font-extrabold text-ink">@yield('title')</h1>
                <p class="mt-2 text-sm text-muted">@yield('message')</p>
                @hasSection('hint')
                    <p class="mt-2 text-xs text-muted">@yield('hint')</p>
                @endif

                <div class="mt-6 flex flex-col gap-2 sm:flex-row sm:justify-center">
                    <button type="button" onclick="if (history.length > 1) { history.back(); } else { location.href = '{{ url('/dashboard') }}'; }"
                            class="cursor-pointer rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-ink transition-colors duration-200 hover:bg-primary/10">
                        Go back
                    </button>
                    <a href="{{ url('/dashboard') }}"
                       class="cursor-pointer rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white transition-colors duration-200 hover:bg-primary-dark">
                        Go to Dashboard
                    </a>
                </div>
                <p class="mt-5 text-xs text-muted">
                    Still stuck? <a href="{{ url('/contact') }}" class="font-semibold text-primary-dark underline">Contact us</a>
                </p>
            </div>
        </main>
    </body>
</html>
