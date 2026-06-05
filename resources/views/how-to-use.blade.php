<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="A quick guide to using Wowlo — how students and tutors find homework, messages, fees, past-year papers and quizzes, all in one place.">

    <title>How to use Wowlo</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('images/favicon/wowlo_favicon.ico') }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#7C3AED">
    <link rel="apple-touch-icon" href="{{ asset('images/pwa/icon-192.png') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=nunito:300,400,600,700,800,900&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>[x-cloak] { display: none !important; }</style>
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

    @php
        // Single-path Heroicons (kept in sync with partials/onboarding.blade.php).
        $ic = [
            'spark' => 'M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z',
            'home'  => 'm2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.5a.75.75 0 0 0 .75.75h4.5v-6a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75v6h4.5a.75.75 0 0 0 .75-.75V9.75',
            'users' => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z',
            'book'  => 'M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25',
            'mail'  => 'M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75',
            'money' => 'M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z',
            'doc'   => 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z',
            'quiz'  => 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z',
            'lock'  => 'M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z',
            'check' => 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.249-8.25-3.285Z',
        ];

        // Walkthrough steps, mirroring the in-app tour (partials/onboarding.blade.php).
        // The page is public, so we offer both audiences via a toggle rather than
        // reading the logged-in role.
        //
        // Each step may name screenshots ('imgs') saved in public/images/how-to-use/.
        // An image is shown only if the file exists — otherwise the card falls back
        // to its icon — so screenshots can be added/removed without breaking the page.
        $end       = ['icon' => $ic['check'], 'title' => "That's the tour", 'body' => 'That’s it! You can replay this anytime from the menu by your name once you’re logged in. Ready when you are.'];
        $pwStudent = ['icon' => $ic['lock'], 'title' => 'Set your own password', 'menu' => 'Profile', 'imgs' => ['student-profile.jpeg'], 'body' => 'One important step after your first login: open your Profile and set your own password. It keeps your account yours alone.'];
        $pwTutor   = ['icon' => $ic['lock'], 'title' => 'Set your own password', 'menu' => 'Profile', 'imgs' => ['tutor-profile.jpeg'],   'body' => 'Right after your first login, open your Profile and set your own password so your account stays secure.'];

        $studentSteps = [
            ['icon' => $ic['spark'], 'title' => 'Welcome to Wowlo', 'body' => 'This is your tuition home — homework, messages, fees, papers and quizzes, all in one place. Here’s a quick tour; take your time and tap Next.'],
            ['icon' => $ic['home'],  'title' => 'Dashboard',   'menu' => 'Dashboard',   'imgs' => ['student-dashboard.jpeg'],                       'body' => 'Your starting point. See what’s due and how you’re doing the moment you log in — no more digging through chats.'],
            ['icon' => $ic['book'],  'title' => 'Homework',    'menu' => 'Homework',    'imgs' => ['student-homework.jpeg', 'student-homework-2.jpeg'], 'body' => 'Every assignment with its due date and files in one list. Open the attachment, do the work, then tap to mark it done.'],
            ['icon' => $ic['mail'],  'title' => 'Messages',    'menu' => 'Messages',    'imgs' => ['student-messages.jpeg', 'student-messages-2.jpeg'], 'body' => 'Notes and reminders from your tutor land here. The badge tells you when something’s unread, so nothing slips past you.'],
            ['icon' => $ic['money'], 'title' => 'Tuition Fee', 'menu' => 'Tuition Fee', 'imgs' => ['student-fees.jpeg'],                            'body' => 'A clear view of your fees and what’s been paid. It’s locked behind a password your parent or tutor sets, so it stays private.'],
            ['icon' => $ic['doc'],   'title' => 'Exam Papers', 'menu' => 'Exam Papers', 'imgs' => ['student-exam-papers.jpeg'],                     'body' => 'A library of past-year papers sorted by level, subject and year. Find one, download it, and start practising.'],
            ['icon' => $ic['quiz'],  'title' => 'Quizzes',     'menu' => 'Quizzes',     'imgs' => ['student-quizzes.jpeg', 'tuition-quizzes-2.jpeg'], 'body' => 'Take the quizzes your tutor assigns and get marked instantly. Review what you missed and write corrections to lock it in.'],
            $pwStudent,
            $end,
        ];

        $tutorSteps = [
            ['icon' => $ic['spark'], 'title' => 'Welcome to Wowlo', 'body' => 'Welcome! Your students, homework, fees, papers and quizzes live together here. Here’s a quick tour of where everything is.'],
            ['icon' => $ic['home'],  'title' => 'Dashboard',   'menu' => 'Dashboard',   'imgs' => ['tutor-dashboard.jpeg'],                          'body' => 'Your command centre — a snapshot of your roster and the latest homework the moment you log in.'],
            ['icon' => $ic['users'], 'title' => 'Students',    'menu' => 'Students',    'imgs' => ['tutor-students.jpeg'],                           'body' => 'Add and manage your students here. Each one is private to you — no other tutor can ever see them or their data.'],
            ['icon' => $ic['book'],  'title' => 'Homework',    'menu' => 'Homework / Homework Status', 'imgs' => ['tutor-homework.jpeg', 'tutor-homework-status.jpeg', 'tutor-homework-status-2.jpeg'], 'body' => 'Assign homework with a due date and an optional file, then track who’s done and who’s pending under Homework Status.'],
            ['icon' => $ic['mail'],  'title' => 'Messages & Inbox', 'menu' => 'Messages / Inbox', 'imgs' => ['tutor-messages.jpeg', 'tutor-messages-2.jpeg'], 'body' => 'Message your students from Messages. Their replies and notices — like exam-paper approvals — arrive in your Inbox, with an unread badge.'],
            ['icon' => $ic['money'], 'title' => 'Finance & WhatsApp Billing', 'menu' => 'Finance / WhatsApp Billing', 'imgs' => ['tutor-finance.jpeg', 'tutor-whatsapp-billing.jpeg'], 'body' => 'Set each student’s rate, log payments, and see who still owes. Generate a tidy monthly bill and send it over WhatsApp in one tap.'],
            ['icon' => $ic['doc'],   'title' => 'Exam Papers', 'menu' => 'Exam Papers', 'imgs' => ['tutor-exam-papers.jpeg'],                        'body' => 'Browse the shared past-year library and upload your own. Your uploads go live for everyone once the owner approves them.'],
            ['icon' => $ic['quiz'],  'title' => 'Quizzes',     'menu' => 'Quizzes',     'imgs' => ['tutor-quizzes.jpeg'],                            'body' => 'Build MCQ quizzes, assign them to students, and review their results — marking happens automatically.'],
            $pwTutor,
            $end,
        ];

        // Resolve each 'imgs' filename to a public URL — keeping only files that
        // actually exist, so missing screenshots gracefully fall back to the icon.
        $withImages = function (array $steps) {
            return array_map(function ($s) {
                $urls = [];
                foreach ($s['imgs'] ?? [] as $file) {
                    if (file_exists(public_path("images/how-to-use/{$file}"))) {
                        $urls[] = asset("images/how-to-use/{$file}");
                    }
                }
                $s['imgs'] = $urls;
                return $s;
            }, $steps);
        };
        $studentSteps = $withImages($studentSteps);
        $tutorSteps   = $withImages($tutorSteps);
    @endphp

    <main class="px-4 py-14 sm:px-6 sm:py-20">
        <div class="mx-auto max-w-4xl">

            {{-- ── Header ── --}}
            <div class="text-center">
                <span class="inline-flex items-center gap-2 rounded-full bg-primary/10 px-3 py-1 text-xs font-bold text-primary-dark">
                    <span class="h-1.5 w-1.5 rounded-full bg-primary"></span>
                    Quick guide
                </span>
                <h1 class="mt-4 text-3xl font-extrabold tracking-tight sm:text-5xl">
                    How to use <span class="text-primary">Wowlo</span>
                </h1>
                <p class="mx-auto mt-5 max-w-2xl text-lg leading-relaxed text-muted">
                    This is the same walkthrough you’ll see the first time you log in — step
                    through it here at your own pace. Pick your view below.
                </p>
            </div>

            {{-- ── The tour, inline (same carousel as the in-app popup, on the page) ── --}}
            <div x-data="howToUse({ student: @js($studentSteps), tutor: @js($tutorSteps) })" class="mt-10">

                {{-- Audience toggle --}}
                <div class="flex justify-center">
                    <div class="inline-flex rounded-2xl border border-gray-200 bg-white p-1 shadow-sm">
                        <button @click="setAudience('student')" type="button"
                                class="rounded-xl px-5 py-2.5 text-sm font-bold transition-colors cursor-pointer"
                                :class="audience === 'student' ? 'bg-primary text-white shadow-sm' : 'text-ink hover:text-primary-dark'">
                            For students
                        </button>
                        <button @click="setAudience('tutor')" type="button"
                                class="rounded-xl px-5 py-2.5 text-sm font-bold transition-colors cursor-pointer"
                                :class="audience === 'tutor' ? 'bg-primary text-white shadow-sm' : 'text-ink hover:text-primary-dark'">
                            For tutors
                        </button>
                    </div>
                </div>

                {{-- Carousel card --}}
                <div class="mt-8 flex min-h-[460px] flex-col rounded-3xl border border-gray-100 bg-white shadow-xl"
                     @touchstart="touchX = $event.changedTouches[0].clientX"
                     @touchend="swipe($event.changedTouches[0].clientX)">

                    {{-- Progress dots + step counter --}}
                    <div class="flex items-center justify-between px-6 pt-6 sm:px-8">
                        <div class="flex flex-wrap gap-1.5">
                            <template x-for="(s, i) in steps" :key="i">
                                <span class="h-1.5 rounded-full transition-all duration-200"
                                      :class="i === step ? 'w-5 bg-primary' : 'w-1.5 bg-gray-200'"></span>
                            </template>
                        </div>
                        <span class="text-sm font-semibold text-muted" x-text="(step + 1) + ' / ' + steps.length"></span>
                    </div>

                    {{-- Card body --}}
                    <div class="flex flex-1 flex-col justify-center px-6 pb-2 pt-6 sm:px-10">

                        {{-- With screenshots: gallery on the left, text on the right (stacks on mobile) --}}
                        <template x-if="steps[step].imgs.length">
                            <div class="grid w-full items-center gap-6 sm:gap-10 md:grid-cols-2">
                                {{-- Screenshot gallery (scrolls sideways if a step has several) --}}
                                <div class="flex justify-center gap-3 overflow-x-auto pb-1 md:justify-start"
                                     role="group" :aria-label="steps[step].title + ' screenshots'">
                                    <template x-for="(src, i) in steps[step].imgs" :key="i">
                                        <img :src="src" :alt="steps[step].title + ' screen'" loading="lazy"
                                             class="h-64 w-auto shrink-0 rounded-2xl border border-gray-200 shadow-sm sm:h-80" />
                                    </template>
                                </div>
                                {{-- Text --}}
                                <div class="text-center md:text-left">
                                    <h2 class="text-2xl font-extrabold text-ink" x-text="steps[step].title"></h2>
                                    <template x-if="steps[step].menu">
                                        <span class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-accent/15 px-3 py-1 text-xs font-bold text-accent-dark">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                                            <span>Menu: <span x-text="steps[step].menu"></span></span>
                                        </span>
                                    </template>
                                    <p class="mt-4 text-base leading-relaxed text-muted" x-text="steps[step].body"></p>
                                </div>
                            </div>
                        </template>

                        {{-- No screenshot (Welcome / Done): centered icon layout --}}
                        <template x-if="! steps[step].imgs.length">
                            <div class="flex flex-col items-center justify-center text-center">
                                <div class="grid h-20 w-20 place-items-center rounded-2xl bg-primary/10 text-primary-dark">
                                    <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" :d="steps[step].icon" />
                                    </svg>
                                </div>
                                <h2 class="mt-6 text-2xl font-extrabold text-ink" x-text="steps[step].title"></h2>
                                <p class="mx-auto mt-4 max-w-md text-base leading-relaxed text-muted" x-text="steps[step].body"></p>
                            </div>
                        </template>
                    </div>

                    {{-- Footer: Back / Next (and a Log in CTA on the last card) --}}
                    <div class="flex items-center justify-between gap-3 px-6 pb-6 pt-4 sm:px-8">
                        <button @click="prev()" x-show="step > 0"
                                class="rounded-xl px-4 py-2.5 text-sm font-bold text-ink hover:bg-gray-100 cursor-pointer">Back</button>
                        <div x-show="step === 0" class="flex-1"></div>

                        <button @click="next()" x-show="step < steps.length - 1"
                                class="ml-auto rounded-xl bg-primary px-6 py-2.5 text-sm font-bold text-white hover:bg-primary-dark cursor-pointer">Next</button>
                        @auth
                            <a href="{{ route('dashboard') }}" x-show="step === steps.length - 1"
                               class="ml-auto rounded-xl bg-primary px-6 py-2.5 text-sm font-bold text-white hover:bg-primary-dark cursor-pointer">Go to Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" x-show="step === steps.length - 1"
                               class="ml-auto rounded-xl bg-primary px-6 py-2.5 text-sm font-bold text-white hover:bg-primary-dark cursor-pointer">Log in to Wowlo</a>
                        @endauth
                    </div>
                </div>

                <p class="mt-4 text-center text-sm text-muted">Tip: use the arrows, or swipe left/right on touch.</p>
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
                <a href="{{ route('how-to-use') }}" class="transition-colors hover:text-primary-dark cursor-pointer">How to use</a>
                <a href="{{ route('privacy-policy') }}" class="transition-colors hover:text-primary-dark cursor-pointer">Privacy Policy</a>
                <a href="{{ route('contact') }}" class="transition-colors hover:text-primary-dark cursor-pointer">Contact</a>
                <a href="{{ route('login') }}" class="transition-colors hover:text-primary-dark cursor-pointer">Log in</a>
            </nav>
            <p class="text-sm text-muted">&copy; {{ date('Y') }} Wowlo. All rights reserved.</p>
        </div>
    </footer>

    <script>
        function howToUse({ student, tutor }) {
            return {
                audience: 'student',
                step: 0,
                touchX: 0,
                sets: { student, tutor },
                get steps() { return this.sets[this.audience]; },
                setAudience(a) { this.audience = a; this.step = 0; },
                next() { if (this.step < this.steps.length - 1) this.step++; },
                prev() { if (this.step > 0) this.step--; },
                swipe(endX) {
                    const dx = endX - this.touchX;
                    if (dx < -40) this.next();
                    else if (dx > 40) this.prev();
                },
            };
        }
    </script>
</body>
</html>
