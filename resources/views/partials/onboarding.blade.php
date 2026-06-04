{{--
    Welcome tour. A role-aware card carousel shown the first time a user reaches
    the dashboard (auto-opens when users.onboarded_at is NULL), and replayable
    anytime via the "Replay tutorial" item in the name menu.

    Modal (not DOM-spotlight) so it's rock-solid on the phone/tablet PWA, where
    the nav lives in a collapsed sidebar. Steps name each menu item and the last
    one links straight to the password change. See docs/onboarding-feature.md.
--}}
@php
    $u = auth()->user();

    // Single-path Heroicons used by the tour cards.
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

    // Steps shared by every teaching account (tutor + super_admin).
    $pw  = ['icon' => $ic['lock'],  'title' => 'Change your password', 'body' => 'Important: set your own password now so your account is secure. You can change it anytime from your Profile, under “Update Password”.', 'action' => 'password'];
    $end = ['icon' => $ic['check'], 'title' => "You're all set", 'body' => 'That\'s the tour! You can replay it anytime from your name menu at the top-right. Enjoy Wowlo.'];

    if ($u->isSuperAdmin()) {
        $steps = [
            ['icon' => $ic['spark'], 'title' => 'Welcome to Wowlo', 'body' => "You're the owner. You teach your own students and manage the whole platform. This quick tour shows where everything lives — you can replay it anytime."],
            ['icon' => $ic['home'],  'title' => 'Dashboard',  'body' => 'Your home base — a snapshot of your roster and recent homework.', 'menu' => 'Dashboard'],
            ['icon' => $ic['users'], 'title' => 'Tutors',     'body' => 'Create and manage tutor accounts. Each tutor gets a private roster and can never see another tutor’s students or data.', 'menu' => 'Tutors'],
            ['icon' => $ic['users'], 'title' => 'Students',   'body' => 'Add your own students. Each one belongs only to you and can only ever see their own homework, messages, fees and quizzes.', 'menu' => 'Students'],
            ['icon' => $ic['book'],  'title' => 'Homework',   'body' => 'Assign homework with a due date and optional file. Track who’s done vs pending under Homework Status.', 'menu' => 'Homework'],
            ['icon' => $ic['mail'],  'title' => 'Messages & Inbox', 'body' => 'Send messages to students under Messages. Replies and exam-paper approval notices arrive in your Inbox — the badge shows unread.', 'menu' => 'Messages / Inbox'],
            ['icon' => $ic['money'], 'title' => 'Finance & WhatsApp Billing', 'body' => 'Set each student’s rate, record payments, and see what’s outstanding. Generate a monthly bill and send it over WhatsApp in a tap.', 'menu' => 'Finance / WhatsApp Billing'],
            ['icon' => $ic['doc'],   'title' => 'Exam Papers', 'body' => 'A shared library of past-year papers. As admin, you approve papers tutors submit before they go live for everyone.', 'menu' => 'Exam Papers'],
            ['icon' => $ic['quiz'],  'title' => 'Quizzes',    'body' => 'Create MCQ quizzes, assign them to students, and review their results.', 'menu' => 'Quizzes'],
            $pw,
            $end,
        ];
    } elseif ($u->actsAsTutor()) {
        $steps = [
            ['icon' => $ic['spark'], 'title' => 'Welcome to Wowlo', 'body' => 'Your students, homework, fees, papers and quizzes — all in one place. This quick tour shows where everything lives. You can replay it anytime.'],
            ['icon' => $ic['home'],  'title' => 'Dashboard',  'body' => 'Your home base — a snapshot of your roster and recent homework.', 'menu' => 'Dashboard'],
            ['icon' => $ic['users'], 'title' => 'Students',   'body' => 'Add your students. Each one belongs only to you and can only ever see their own data.', 'menu' => 'Students'],
            ['icon' => $ic['book'],  'title' => 'Homework',   'body' => 'Assign homework with a due date and optional file. Track who’s done vs pending under Homework Status.', 'menu' => 'Homework'],
            ['icon' => $ic['mail'],  'title' => 'Messages & Inbox', 'body' => 'Send messages to your students under Messages. Replies and notices arrive in your Inbox — the badge shows unread.', 'menu' => 'Messages / Inbox'],
            ['icon' => $ic['money'], 'title' => 'Finance & WhatsApp Billing', 'body' => 'Set each student’s rate, record payments, and see what’s outstanding. Generate a monthly bill and send it over WhatsApp in a tap.', 'menu' => 'Finance / WhatsApp Billing'],
            ['icon' => $ic['doc'],   'title' => 'Exam Papers', 'body' => 'A shared library of past-year papers. Upload your own — they go live for everyone once the admin approves them.', 'menu' => 'Exam Papers'],
            ['icon' => $ic['quiz'],  'title' => 'Quizzes',    'body' => 'Create MCQ quizzes, assign them to students, and review their results.', 'menu' => 'Quizzes'],
            $pw,
            $end,
        ];
    } else {
        $steps = [
            ['icon' => $ic['spark'], 'title' => 'Welcome to Wowlo', 'body' => 'Here’s how to stay on top of your tuition. A quick tour — you can replay it anytime from your name menu.'],
            ['icon' => $ic['home'],  'title' => 'Dashboard',  'body' => 'Your home base — homework and progress at a glance.', 'menu' => 'Dashboard'],
            ['icon' => $ic['book'],  'title' => 'Homework',   'body' => 'See what’s due, open attachments, and mark homework done when you finish.', 'menu' => 'Homework'],
            ['icon' => $ic['mail'],  'title' => 'Messages',   'body' => 'Read messages from your tutor here. The badge shows unread messages.', 'menu' => 'Messages'],
            ['icon' => $ic['money'], 'title' => 'Tuition Fee', 'body' => 'View your fees and payment history. This section is protected by a password your parent or tutor sets.', 'menu' => 'Tuition Fee'],
            ['icon' => $ic['doc'],   'title' => 'Exam Papers', 'body' => 'Browse and download past-year exam papers to practise.', 'menu' => 'Exam Papers'],
            ['icon' => $ic['quiz'],  'title' => 'Quizzes',    'body' => 'Take quizzes your tutor assigns, see your results, and write corrections.', 'menu' => 'Quizzes'],
            $pw,
            $end,
        ];
    }
@endphp

{{-- Auto-open only on the dashboard (the natural post-login landing); elsewhere
     it stays dormant but is still replayable from the name menu. --}}
<div x-data="onboarding({ auto: {{ $u->needsOnboarding() && request()->routeIs('dashboard') ? 'true' : 'false' }} })"
     x-show="open" x-cloak
     class="fixed inset-0 z-[60] flex items-end justify-center bg-ink/50 p-0 sm:items-center sm:p-4"
     @keydown.escape.window="finish()"
     @wowlo:replay-onboarding.window="replay()">

    <div x-show="open" x-transition
         @touchstart="touchX = $event.changedTouches[0].clientX"
         @touchend="swipe($event.changedTouches[0].clientX)"
         class="flex w-full max-w-md flex-col rounded-t-3xl bg-white shadow-2xl sm:rounded-3xl">

        {{-- Header: progress dots + skip --}}
        <div class="flex items-center justify-between px-6 pt-5">
            <div class="flex gap-1.5">
                <template x-for="(s, i) in steps" :key="i">
                    <span class="h-1.5 rounded-full transition-all duration-200"
                          :class="i === step ? 'w-5 bg-primary' : 'w-1.5 bg-gray-200'"></span>
                </template>
            </div>
            <button @click="finish()" class="text-sm font-semibold text-muted hover:text-ink cursor-pointer">Skip</button>
        </div>

        {{-- Card body --}}
        <div class="px-6 pb-2 pt-6 text-center">
            <div class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-primary/10 text-primary-dark">
                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" :d="steps[step].icon" />
                </svg>
            </div>

            <h2 class="mt-5 text-xl font-extrabold text-ink" x-text="steps[step].title"></h2>

            <template x-if="steps[step].menu">
                <span class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-accent/15 px-3 py-1 text-xs font-bold text-accent-dark">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                    <span>Menu: <span x-text="steps[step].menu"></span></span>
                </span>
            </template>

            <p class="mx-auto mt-3 max-w-sm text-sm leading-relaxed text-muted" x-text="steps[step].body"></p>

            {{-- Password step: a direct shortcut --}}
            <template x-if="steps[step].action === 'password'">
                <a href="{{ route('profile.edit') }}#update-password" @click="markSeen()"
                   class="mt-5 inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-bold text-white hover:bg-primary-dark cursor-pointer">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $ic['lock'] }}" /></svg>
                    Change my password now
                </a>
            </template>
        </div>

        {{-- Footer: Back / Next / Finish --}}
        <div class="flex items-center justify-between gap-3 px-6 pb-6 pt-4">
            <button @click="prev()" x-show="step > 0"
                    class="rounded-xl px-4 py-2.5 text-sm font-bold text-ink hover:bg-gray-100 cursor-pointer">Back</button>
            <div x-show="step === 0" class="flex-1"></div>

            <button @click="next()" x-show="step < steps.length - 1"
                    class="ml-auto rounded-xl bg-primary px-6 py-2.5 text-sm font-bold text-white hover:bg-primary-dark cursor-pointer">Next</button>
            <button @click="finish()" x-show="step === steps.length - 1"
                    class="ml-auto rounded-xl bg-primary px-6 py-2.5 text-sm font-bold text-white hover:bg-primary-dark cursor-pointer">Got it, take me in</button>
        </div>
    </div>
</div>

<script>
    function onboarding({ auto }) {
        return {
            open: false,
            step: 0,
            seen: false,
            touchX: 0,
            steps: @js($steps),
            completeUrl: @js(route('onboarding.complete')),
            init() {
                if (auto) this.open = true;   // first time on the dashboard
            },
            next() { if (this.step < this.steps.length - 1) this.step++; },
            prev() { if (this.step > 0) this.step--; },
            swipe(endX) {
                const dx = endX - this.touchX;
                if (dx < -40) this.next();
                else if (dx > 40) this.prev();
            },
            replay() { this.step = 0; this.open = true; },   // from the name menu
            finish() { this.markSeen(); this.open = false; },
            markSeen() {
                if (this.seen) return;          // idempotent
                this.seen = true;
                const token = document.querySelector('meta[name=csrf-token]')?.content;
                // keepalive so the request still completes if we navigate away.
                fetch(this.completeUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                    keepalive: true,
                }).catch(() => {});
            },
        };
    }
</script>
