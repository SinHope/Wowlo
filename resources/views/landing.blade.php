<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Wowlo — your tutor's homework, messages, tuition fees, exam papers and quizzes, all in one friendly app.">

    <title>Wowlo — Tuition, organised.</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('images/favicon/wowlo_favicon.ico') }}">

    {{-- PWA --}}
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#7C3AED">
    <link rel="apple-touch-icon" href="{{ asset('images/pwa/icon-192.png') }}">

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=nunito:300,400,600,700,800,900&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Brand entity (Knowledge Graph / branded search). Homepage = entity home.
         Built via json_encode so Blade never parses the @-prefixed keys as directives. --}}
    <script type="application/ld+json">
        @php echo json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            '@id' => url('/').'/#organization',
            'name' => 'Wowlo',
            'url' => url('/'),
            'logo' => asset('images/logo/wowlo_logo.png'),
            'description' => 'Wowlo is a tuition-management app built in Singapore that brings homework, messages, tuition fees, past-year papers and quizzes together in one place for tutors, students and parents.',
            'areaServed' => ['@type' => 'Country', 'name' => 'Singapore'],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT); @endphp
    </script>

    {{-- Spline web component (framework-agnostic — no React/build needed). Pinned for stability. --}}
    <script type="module" src="https://unpkg.com/@splinetool/viewer@1.12.96/build/spline-viewer.js"></script>

    <style>
        /* Reveal-on-scroll (disabled under prefers-reduced-motion via JS). */
        .reveal { opacity: 0; transform: translateY(20px); transition: opacity .6s ease, transform .6s ease; }
        .reveal.in { opacity: 1; transform: none; }
        [x-cloak] { display: none !important; }

        /* Hero spotlight sweep (ported from the 21st.dev / Aceternity component). */
        @keyframes spotlight {
            0%   { opacity: 0; transform: translate(-72%, -62%) scale(0.5); }
            100% { opacity: 1; transform: translate(-50%, -40%) scale(1); }
        }
        .animate-spotlight { animation: spotlight 2s ease .75s 1 forwards; }
        @media (prefers-reduced-motion: reduce) {
            .animate-spotlight { animation: none; opacity: 1; }
        }

        /* Fallback spinner shown while the 3D scene streams in. */
        .loader {
            width: 36px; height: 36px; border-radius: 9999px;
            border: 3px solid rgba(255, 255, 255, .25); border-top-color: #fff;
            animation: loader-spin .8s linear infinite;
        }
        @keyframes loader-spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body class="bg-cream font-sans text-ink antialiased">

    {{-- ───────────────────────── Nav ───────────────────────── --}}
    <header x-data="{ scrolled: false }" @scroll.window="scrolled = window.scrollY > 8"
            class="fixed inset-x-0 top-0 z-30 transition-all duration-200"
            :class="scrolled ? 'bg-cream/90 shadow-sm backdrop-blur' : ''">
        <nav class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3 sm:px-6">
            <a href="{{ url('/') }}" class="flex items-center gap-2">
                <img src="{{ asset('images/logo/wowlo_logo.png') }}" alt="Wowlo" class="h-20 w-auto">
            </a>
            <div class="flex items-center gap-4 sm:gap-6">
                <a href="{{ route('about') }}" class="text-sm font-semibold text-ink transition-colors hover:text-primary-dark cursor-pointer">About</a>
                <a href="{{ route('contact') }}" class="text-sm font-semibold text-ink transition-colors hover:text-primary-dark cursor-pointer">Contact</a>
                <x-install-app />
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

    {{-- ───────── Hero (NEW — dark spotlight + interactive 3D, ported from 21st.dev) ───────── --}}
    <section class="px-4 pb-16 pt-24 sm:px-6 sm:pt-32">
        <div class="mx-auto max-w-6xl">
            <div class="relative overflow-hidden rounded-3xl border border-white/10 bg-[#060010] shadow-2xl">
                {{-- Spotlight — pure animated SVG (the React version was just this SVG + CSS). --}}
                <svg class="animate-spotlight pointer-events-none absolute -top-40 left-0 z-[1] h-[169%] w-[138%] opacity-0 md:-top-20 md:left-60 lg:w-[84%]"
                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 3787 2842" fill="none" aria-hidden="true">
                    <g filter="url(#spotlight-filter)">
                        <ellipse cx="1924.71" cy="273.501" rx="1924.71" ry="273.501"
                                 transform="matrix(-0.822377 -0.568943 -0.568943 0.822377 3631.88 2291.09)"
                                 fill="white" fill-opacity="0.21" />
                    </g>
                    <defs>
                        <filter id="spotlight-filter" x="0.860352" y="0.838989" width="3785.16" height="2840.26"
                                filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                            <feFlood flood-opacity="0" result="BackgroundImageFix" />
                            <feBlend mode="normal" in="SourceGraphic" in2="BackgroundImageFix" result="shape" />
                            <feGaussianBlur stdDeviation="151" result="effect1_foregroundBlur_1065_8" />
                        </filter>
                    </defs>
                </svg>

                <div class="flex flex-col md:min-h-[520px] md:flex-row">
                    {{-- Left: copy (unchanged content) --}}
                    <div class="relative z-10 flex flex-1 flex-col justify-center p-8 text-center sm:p-12 md:text-left">
                        <span class="inline-flex items-center gap-2 self-center rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-bold text-primary-light md:self-start">
                            <span class="h-1.5 w-1.5 rounded-full bg-primary-light"></span>
                            For my students &amp; parents
                        </span>
                        <h1 class="mt-5 bg-gradient-to-b from-neutral-50 to-neutral-400 bg-clip-text text-4xl font-extrabold leading-tight tracking-tight text-transparent sm:text-5xl lg:text-6xl">
                            Tuition, <span class="text-primary-light">beautifully organised</span>.
                        </h1>
                        <p class="mx-auto mt-5 max-w-lg text-lg leading-relaxed text-neutral-300 md:mx-0">
                            Homework, messages, fees, past-year papers and quizzes — all in one friendly place.
                            Log in with the account your tutor set up for you.
                        </p>
                        <div class="mt-8 flex flex-col items-center gap-3 sm:flex-row md:justify-start">
                            @auth
                                <a href="{{ route('dashboard') }}"
                                   class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3.5 text-base font-bold text-white shadow-md transition-colors duration-200 hover:bg-primary-dark cursor-pointer sm:w-auto">
                                    Go to Dashboard
                                </a>
                            @else
                                <a href="{{ route('login') }}"
                                   class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3.5 text-base font-bold text-white shadow-md transition-colors duration-200 hover:bg-primary-dark cursor-pointer sm:w-auto">
                                    Log in to Wowlo
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                                </a>
                            @endauth
                            <a href="#features"
                               class="inline-flex w-full items-center justify-center rounded-xl border border-white/15 bg-white/5 px-6 py-3.5 text-base font-bold text-white transition-colors duration-200 hover:bg-white/10 cursor-pointer sm:w-auto">
                                See what's inside
                            </a>
                        </div>
                        <p class="mt-4 text-sm text-neutral-400">No sign-up needed — your tutor creates your account.</p>
                    </div>

                    {{-- Right: interactive 3D scene (Spline robot, same scene as the demo) --}}
                    <div class="relative min-h-[340px] flex-1 md:min-h-0">
                        {{-- Spinner shows until <spline-viewer> paints over it. --}}
                        <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
                            <span class="loader"></span>
                        </div>
                        <spline-viewer url="https://prod.spline.design/kZDDjO5HuC9GJUM2/scene.splinecode"
                                       class="absolute inset-0 h-full w-full"></spline-viewer>
                    </div>
                </div>
            </div>
        </div>
    </section>


    {{-- ───────────────────────── Trust band ───────────────────────── --}}
    <section class="border-y border-gray-100 bg-white">
        <div class="mx-auto grid max-w-6xl gap-6 px-4 py-10 sm:grid-cols-3 sm:px-6">
            @php
                $trust = [
                    ['t' => 'All in one place', 'd' => 'Homework, fees, papers and quizzes — no more scattered chats and files.'],
                    ['t' => 'Works on any phone', 'd' => 'Install it to your home screen like an app. Get notified about new homework.'],
                    ['t' => 'Private &amp; PDPA-ready', 'd' => 'Your data is only ever seen by you and your tutor.'],
                ];
            @endphp
            @foreach ($trust as $item)
                <div class="reveal flex items-start gap-3">
                    <span class="mt-0.5 grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-primary/10 text-primary">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                    </span>
                    <div>
                        <p class="font-extrabold text-ink">{!! $item['t'] !!}</p>
                        <p class="mt-0.5 text-sm leading-relaxed text-muted">{!! $item['d'] !!}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ───────────────────────── Features ───────────────────────── --}}
    <section id="features" class="px-4 py-20 sm:px-6">
        <div class="mx-auto max-w-6xl">
            <div class="reveal mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-extrabold sm:text-4xl">Everything for your tuition, in one app</h2>
                <p class="mt-3 text-lg text-muted">Built around how lessons actually run — simple for students, powerful for your tutor.</p>
            </div>

            @php
                $features = [
                    ['t' => 'Homework', 'd' => 'See what\'s due, open attachments, and mark it done when you finish.',
                     'i' => 'M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25'],
                    ['t' => 'Messages', 'd' => 'Get notes and reminders from your tutor, with unread badges so nothing slips.',
                     'i' => 'M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75'],
                    ['t' => 'Fees &amp; WhatsApp billing', 'd' => 'A clear fee statement for parents, and tidy WhatsApp bills your tutor can send in a tap.',
                     'i' => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z'],
                    ['t' => 'Exam papers', 'd' => 'Past-year papers organised by level, subject and year — download anytime.',
                     'i' => 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z'],
                    ['t' => 'Quizzes', 'd' => 'Take MCQ quizzes your tutor assigns, get marked instantly, and write corrections.',
                     'i' => 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z'],
                    ['t' => 'Install like an app', 'd' => 'Add Wowlo to your home screen and get push notifications for new homework.',
                     'i' => 'M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3'],
                ];
            @endphp

            <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($features as $f)
                    <div class="reveal group rounded-2xl border border-gray-100 bg-white p-6 shadow-sm transition-all duration-200 hover:-translate-y-1 hover:border-primary/30 hover:shadow-md">
                        <span class="grid h-12 w-12 place-items-center rounded-xl bg-primary/10 text-primary transition-colors duration-200 group-hover:bg-primary group-hover:text-white">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $f['i'] }}" /></svg>
                        </span>
                        <h3 class="mt-4 text-lg font-extrabold text-ink">{!! $f['t'] !!}</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-muted">{!! $f['d'] !!}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ───────────────────────── How it works ───────────────────────── --}}
    <section class="bg-white px-4 py-20 sm:px-6">
        <div class="mx-auto max-w-5xl">
            <div class="reveal mx-auto max-w-2xl text-center">
                <h2 class="text-3xl font-extrabold sm:text-4xl">Getting started is easy</h2>
                <p class="mt-3 text-lg text-muted">Three steps and you're in.</p>
            </div>

            @php
                $steps = [
                    ['n' => '1', 't' => 'Your tutor sets up your account', 'd' => 'You\'ll get your login details — there\'s no public sign-up.'],
                    ['n' => '2', 't' => 'Log in with email or Google', 'd' => 'On any phone, tablet or computer. Add it to your home screen if you like.'],
                    ['n' => '3', 't' => 'Stay on top of everything', 'd' => 'Homework, messages, fees, papers and quizzes — always in one place.'],
                ];
            @endphp

            <div class="mt-12 grid gap-6 sm:grid-cols-3">
                @foreach ($steps as $s)
                    <div class="reveal relative rounded-2xl border border-gray-100 bg-cream p-6">
                        <span class="grid h-11 w-11 place-items-center rounded-full bg-primary text-lg font-extrabold text-white">{{ $s['n'] }}</span>
                        <h3 class="mt-4 text-lg font-extrabold text-ink">{{ $s['t'] }}</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-muted">{{ $s['d'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ───────────────────────── Final CTA ───────────────────────── --}}
    <section class="px-4 py-20 sm:px-6">
        <div class="reveal mx-auto max-w-4xl overflow-hidden rounded-3xl bg-primary px-6 py-14 text-center shadow-lg">
            <h2 class="text-3xl font-extrabold text-white sm:text-4xl">Ready when you are</h2>
            <p class="mx-auto mt-3 max-w-xl text-lg text-white/85">
                Have an account from your tutor? Jump back in and see what's due.
            </p>
            <div class="mt-8">
                @auth
                    <a href="{{ route('dashboard') }}"
                       class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-7 py-3.5 text-base font-bold text-primary-dark shadow-md transition-transform duration-200 hover:scale-[1.02] cursor-pointer">
                        Go to Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}"
                       class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-7 py-3.5 text-base font-bold text-primary-dark shadow-md transition-transform duration-200 hover:scale-[1.02] cursor-pointer">
                        Log in to Wowlo
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                    </a>
                @endauth
            </div>
        </div>
    </section>

    {{-- ───────────────────────── Footer ───────────────────────── --}}
    <footer class="border-t border-gray-100 bg-white px-4 py-10 sm:px-6">
        <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 sm:flex-row">
            <div class="flex items-center gap-2">
                <img src="{{ asset('images/logo/wowlo_logo.png') }}" alt="Wowlo" class="h-40 w-auto">
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

    {{-- Reveal-on-scroll (skipped entirely under prefers-reduced-motion). --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const els = document.querySelectorAll('.reveal');
            const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (reduce || ! ('IntersectionObserver' in window)) {
                els.forEach(el => el.classList.add('in'));
                return;
            }
            const io = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('in');
                        io.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.12 });
            els.forEach(el => io.observe(el));
        });
    </script>
</body>
</html>
