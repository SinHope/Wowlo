<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Wowlo is a tuition-management app built in Singapore that keeps homework, messages, fees, past-year papers and quizzes in one place for tutors, students and parents.">

    <title>About Wowlo — Tuition, all in one place.</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('images/favicon/wowlo_favicon.ico') }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#7C3AED">
    <link rel="apple-touch-icon" href="{{ asset('images/pwa/icon-192.png') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=nunito:300,400,600,700,800,900&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Entity signals: establish "Wowlo" as a distinct brand + software entity.
         Built via json_encode so Blade never parses the @-prefixed keys as directives. --}}
    <script type="application/ld+json">
        @php echo json_encode([
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => url('/').'/#organization',
                    'name' => 'Wowlo',
                    'url' => url('/'),
                    'logo' => asset('images/logo/wowlo_logo.png'),
                    'description' => 'Wowlo is a tuition-management app built in Singapore that brings homework, messages, tuition fees, past-year papers and quizzes together in one place for tutors, students and parents.',
                    'areaServed' => ['@type' => 'Country', 'name' => 'Singapore'],
                ],
                [
                    '@type' => 'SoftwareApplication',
                    '@id' => url('/').'/#app',
                    'name' => 'Wowlo',
                    'url' => url('/'),
                    'applicationCategory' => 'EducationalApplication',
                    'operatingSystem' => 'Web, iOS, Android (PWA)',
                    'publisher' => ['@id' => url('/').'/#organization'],
                    'description' => 'Tuition-management app for tutors, students and parents — homework, messages, tuition fees, past-year exam papers and MCQ quizzes in one place.',
                    'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'SGD'],
                ],
                [
                    '@type' => 'AboutPage',
                    '@id' => route('about').'/#webpage',
                    'url' => route('about'),
                    'name' => 'About Wowlo',
                    'about' => ['@id' => url('/').'/#organization'],
                ],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT); @endphp
    </script>
</head>
<body class="bg-cream font-sans text-ink antialiased">

    {{-- ───────────────────────── Nav ───────────────────────── --}}
    <header class="border-b border-gray-100 bg-cream/90 backdrop-blur">
        <nav class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3 sm:px-6">
            <a href="{{ url('/') }}" class="flex items-center gap-2">
                <img src="{{ asset('images/logo/wowlo_logo.png') }}" alt="Wowlo" class="h-20 w-auto">
            </a>
            <div class="flex items-center gap-4 sm:gap-6">
                <a href="{{ route('about') }}" class="text-sm font-bold text-primary-dark cursor-pointer">About</a>
                <a href="{{ route('contact') }}" class="text-sm font-semibold text-ink transition-colors hover:text-primary-dark cursor-pointer">Contact</a>
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

    <main class="px-4 py-14 sm:px-6 sm:py-20">
        <div class="mx-auto max-w-3xl">

            {{-- ── Hero / entity-first lead ── --}}
            <div class="text-center">
                <span class="inline-flex items-center gap-2 rounded-full bg-primary/10 px-3 py-1 text-xs font-bold text-primary-dark">
                    <span class="h-1.5 w-1.5 rounded-full bg-primary"></span>
                    About Wowlo
                </span>
                <h1 class="mt-4 text-3xl font-extrabold tracking-tight sm:text-5xl">
                    Tuition, <span class="text-primary">all in one place</span>.
                </h1>
                <p class="mx-auto mt-5 max-w-2xl text-lg leading-relaxed text-muted">
                    Wowlo is a tuition-management app built in Singapore. It brings homework, messages, tuition fees,
                    past-year papers and quizzes together in one place — for tutors, students and parents.
                </p>
            </div>

            {{-- ── Why we built it (the problem) ── --}}
            <section class="mt-14">
                <h2 class="text-2xl font-extrabold sm:text-3xl">Why we built Wowlo</h2>
                <p class="mt-3 leading-relaxed text-muted">
                    Tuition has a lot of moving parts, and they're usually scattered across chats, notebooks and
                    spreadsheets. Things slip through the cracks — for everyone involved:
                </p>
                <div class="mt-6 space-y-4">
                    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                        <h3 class="font-extrabold text-ink">Tutors lose time to admin</h3>
                        <p class="mt-1 text-sm leading-relaxed text-muted">
                            With many students, no tutor can remember every detail for each one. Freelance tutors also
                            spend hours working out hours, rates and who still owes what.
                        </p>
                    </div>
                    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                        <h3 class="font-extrabold text-ink">Students lose track</h3>
                        <p class="mt-1 text-sm leading-relaxed text-muted">
                            It's easy to forget which homework is due — and harder still to see your own progress and
                            the gaps you should be revising.
                        </p>
                    </div>
                    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                        <h3 class="font-extrabold text-ink">Parents are kept in the dark</h3>
                        <p class="mt-1 text-sm leading-relaxed text-muted">
                            Between busy work-weeks, parents rarely get a clear, up-to-date picture of how their child
                            is actually doing.
                        </p>
                    </div>
                </div>
            </section>

            {{-- ── What Wowlo does (the solution) ── --}}
            <section class="mt-14">
                <h2 class="text-2xl font-extrabold sm:text-3xl">How Wowlo helps</h2>
                <p class="mt-3 leading-relaxed text-muted">
                    Wowlo gives everyone the same organised view, so nothing gets lost:
                </p>
                <div class="mt-6 grid gap-4 sm:grid-cols-3">
                    <div class="rounded-2xl bg-primary/5 p-5">
                        <h3 class="font-extrabold text-ink">For tutors</h3>
                        <p class="mt-1 text-sm leading-relaxed text-muted">
                            Less admin, more teaching. Homework, fees and papers are handled in a tap — so you can focus
                            on helping students ace their exams.
                        </p>
                    </div>
                    <div class="rounded-2xl bg-primary/5 p-5">
                        <h3 class="font-extrabold text-ink">For students</h3>
                        <p class="mt-1 text-sm leading-relaxed text-muted">
                            Always know what's due, what to study, and how you're tracking — no more forgotten homework.
                        </p>
                    </div>
                    <div class="rounded-2xl bg-primary/5 p-5">
                        <h3 class="font-extrabold text-ink">For parents</h3>
                        <p class="mt-1 text-sm leading-relaxed text-muted">
                            See real progress at a glance, and step in to support your child from your own end.
                        </p>
                    </div>
                </div>
            </section>

            {{-- ── What we believe (trust signals) ── --}}
            <section class="mt-14">
                <h2 class="text-2xl font-extrabold sm:text-3xl">What we believe</h2>
                <div class="mt-6 space-y-3">
                    @foreach ([
                        ['All in one place', 'Homework, messages, fees, papers and quizzes — no more scattered chats and files.'],
                        ['Private by design', 'Your data is only ever seen by you and your tutor. Built to respect Singapore\'s PDPA.'],
                        ['Made for Singapore tuition', 'Levels, subjects and exam types that match how lessons here actually run.'],
                    ] as $belief)
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-primary/10 text-primary">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            </span>
                            <p class="leading-relaxed text-muted"><span class="font-bold text-ink">{{ $belief[0] }}.</span> {{ $belief[1] }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- ── CTA ── --}}
            <section class="mt-16 overflow-hidden rounded-3xl bg-primary px-6 py-12 text-center shadow-lg">
                <h2 class="text-2xl font-extrabold text-white sm:text-3xl">The simplest way to manage tuition</h2>
                <p class="mx-auto mt-3 max-w-xl text-white/85">
                    Have an account from your tutor? Jump in. New tutor? Get in touch and we'll set you up.
                </p>
                <div class="mt-7 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <a href="{{ route('login') }}"
                       class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-7 py-3.5 text-base font-bold text-primary-dark shadow-md transition-transform duration-200 hover:scale-[1.02] cursor-pointer">
                        Log in to Wowlo
                    </a>
                    <a href="{{ route('contact') }}"
                       class="inline-flex items-center justify-center rounded-xl border border-white/40 px-7 py-3.5 text-base font-bold text-white transition-colors duration-200 hover:bg-white/10 cursor-pointer">
                        Contact us
                    </a>
                </div>
            </section>
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
