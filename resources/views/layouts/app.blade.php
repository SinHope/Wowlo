<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Wowlo') }}</title>

        <link rel="icon" type="image/x-icon" href="{{ asset('images/favicon/wowlo_favicon.ico') }}">

        <!-- PWA -->
        <link rel="manifest" href="{{ asset('manifest.json') }}">
        <meta name="theme-color" content="#7C3AED">
        <link rel="apple-touch-icon" href="{{ asset('images/pwa/icon-192.png') }}">
        {{-- VAPID *public* key — safe to expose; needed by the browser to subscribe to push. --}}
        <meta name="vapid-public-key" content="{{ config('webpush.vapid.public_key') }}">
        <meta name="push-subscribe-url" content="{{ route('push.subscribe') }}">
        <meta name="push-unsubscribe-url" content="{{ route('push.unsubscribe') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=nunito:300,400,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>[x-cloak]{display:none!important;}</style>
    </head>
    <body class="font-sans antialiased text-ink">
        @php
            $authUser     = auth()->user();
            $actsAsTutor  = $authUser->actsAsTutor();
            $isSuperAdmin = $authUser->isSuperAdmin();

            // Role-based menu. Items without a real route yet point to '#'.
            $icons = [
                'home'     => 'm2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.5a.75.75 0 0 0 .75.75h4.5v-6a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75v6h4.5a.75.75 0 0 0 .75-.75V9.75',
                'users'    => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z',
                'book'     => 'M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25',
                'mail'     => 'M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75',
                'check'    => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
                'money'    => 'M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z',
                'doc'      => 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z',
                'quiz'     => 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z',
                'chat'     => 'M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155',
                'user'     => 'M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
                'folder'   => 'M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z',
                'game'     => 'M15.59 14.37a6 6 0 0 1-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 0 0 6.16-12.12A14.98 14.98 0 0 0 9.631 8.41m5.96 5.96a14.926 14.926 0 0 1-5.841 2.58m-.119-8.54a6 6 0 0 0-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 0 0-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 0 1-2.448-2.448 14.9 14.9 0 0 1 .06-.312m-2.24 2.39a4.493 4.493 0 0 0-1.757 4.306 4.493 4.493 0 0 0 4.306-1.758M16.5 9a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z',
                'megaphone' => 'M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.51l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 0 8.835-2.535m0 0A23.74 23.74 0 0 0 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 0 1 0 3.46',
                'sparkles'  => 'M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z',
            ];

            // The "Resources" group is shared by both roles — its children link to
            // the two answer-sheet types. The acting role decides which route prefix.
            $resourcesPrefix = $actsAsTutor ? 'tutor.resources.' : 'student.resources.';
            $resourcesGroup = [
                'label'    => 'Resources',
                'icon'     => 'folder',
                'active'   => request()->routeIs($resourcesPrefix . '*'),
                'children' => [
                    [
                        'label'  => 'MCQ/OAS Sheet',
                        'href'   => route($resourcesPrefix . 'index', 'mcq'),
                        'active' => request()->routeIs($resourcesPrefix . '*') && request()->route('type') === 'mcq',
                    ],
                    [
                        'label'  => 'Short Answers Sheet',
                        'href'   => route($resourcesPrefix . 'index', 'short_answer'),
                        'active' => request()->routeIs($resourcesPrefix . '*') && request()->route('type') === 'short_answer',
                    ],
                ],
            ];

            // The "Games" group. Students play + track progress; tutors review
            // their students' rounds. (Spelling Meow is the first game.)
            $gamesActive = request()->routeIs('games', 'games.*', '*.games.*');
            $gamesGroup = [
                'label'    => 'Games',
                'icon'     => 'game',
                'active'   => $gamesActive,
                'children' => $actsAsTutor ? [
                    [
                        'label'  => 'Spelling Meow',
                        'href'   => route('tutor.games.spelling.index'),
                        'active' => request()->routeIs('tutor.games.spelling.*'),
                    ],
                    [
                        'label'  => 'Multiplication Rabbit',
                        'href'   => route('tutor.games.multiplication.index'),
                        'active' => request()->routeIs('tutor.games.multiplication.*'),
                    ],
                    [
                        'label'  => 'Hangman Wheel Panda',
                        'href'   => route('tutor.games.hangman.wheels.index'),
                        'active' => request()->routeIs('tutor.games.hangman.*', 'games.hangman.*'),
                    ],
                    [
                        'label'  => 'Roll the Dice',
                        'href'   => route('games.roll-the-dice'),
                        'active' => request()->routeIs('games.roll-the-dice'),
                    ],
                ] : [
                    // Each child IS a game; its own pages (Play / My Progress) are
                    // sub-navigated inside the game, not from the sidebar.
                    [
                        'label'  => 'Spelling Meow',
                        'href'   => route('student.games.spelling.play'),
                        'active' => request()->routeIs('student.games.spelling.*'),
                    ],
                    [
                        'label'  => 'Multiplication Rabbit',
                        'href'   => route('student.games.multiplication.play'),
                        'active' => request()->routeIs('student.games.multiplication.*'),
                    ],
                    [
                        'label'  => 'Hangman Wheel Panda',
                        'href'   => route('games.hangman.play'),
                        'active' => request()->routeIs('games.hangman.*'),
                    ],
                    [
                        'label'  => 'Roll the Dice',
                        'href'   => route('games.roll-the-dice'),
                        'active' => request()->routeIs('games.roll-the-dice'),
                    ],
                ],
            ];

            $menu = $actsAsTutor ? array_values(array_filter([
                ['label' => 'Dashboard',         'icon' => 'home',  'href' => route('dashboard'), 'active' => request()->routeIs('dashboard')],
                // Super-admin only: manage tutor accounts.
                $isSuperAdmin
                    ? ['label' => 'Tutors',      'icon' => 'users', 'href' => route('admin.tutors.index'), 'active' => request()->routeIs('admin.tutors.*')]
                    : null,
                // Super-admin only: compose app-wide banner notifications.
                $isSuperAdmin
                    ? ['label' => 'Banner Notification', 'icon' => 'megaphone', 'href' => route('admin.banners.index'), 'active' => request()->routeIs('admin.banners.*')]
                    : null,
                ['label' => 'Students',          'icon' => 'users', 'href' => route('tutor.students.index'), 'active' => request()->routeIs('tutor.students.*')],
                ['label' => 'Homework',          'icon' => 'book',  'href' => route('tutor.homework.index'), 'active' => request()->routeIs('tutor.homework.index', 'tutor.homework.create', 'tutor.homework.edit')],
                ['label' => 'Homework Status',   'icon' => 'check', 'href' => route('tutor.homework.status'), 'active' => request()->routeIs('tutor.homework.status')],
                ['label' => 'Messages',          'icon' => 'mail',  'href' => route('tutor.messages.index'), 'active' => request()->routeIs('tutor.messages.index', 'tutor.messages.create', 'tutor.messages.show')],
                ['label' => 'Inbox',             'icon' => 'chat',  'href' => route('tutor.messages.inbox'), 'active' => request()->routeIs('tutor.messages.inbox'), 'badge' => $authUser->receivedMessages()->where('is_read', false)->count()],
                ['label' => 'Finance',           'icon' => 'money', 'href' => route('tutor.finance.index'), 'active' => request()->routeIs('tutor.finance.*')],
                ['label' => 'WhatsApp Billing',  'icon' => 'chat',  'href' => route('tutor.billing.index'), 'active' => request()->routeIs('tutor.billing.*')],
                ['label' => 'Exam Papers',       'icon' => 'doc',   'href' => route('tutor.exam-papers.index'), 'active' => request()->routeIs('tutor.exam-papers.*')],
                ['label' => 'Quizzes',           'icon' => 'quiz',  'href' => route('tutor.quizzes.index'), 'active' => request()->routeIs('tutor.quizzes.*')],
                $resourcesGroup,
                $gamesGroup,
                ['label' => 'Patch Notes',       'icon' => 'sparkles', 'href' => route('patch-notes.index'), 'active' => request()->routeIs('patch-notes.*', 'admin.patch-notes.*')],
            ])) : [
                ['label' => 'Dashboard',     'icon' => 'home',  'href' => route('dashboard'), 'active' => request()->routeIs('dashboard')],
                ['label' => 'Homework',      'icon' => 'book',  'href' => route('student.homework.index'), 'active' => request()->routeIs('student.homework.*')],
                ['label' => 'Messages',      'icon' => 'mail',  'href' => route('student.messages.index'), 'active' => request()->routeIs('student.messages.*'), 'badge' => auth()->user()->receivedMessages()->where('is_read', false)->count()],
                ['label' => 'Tuition Fee',   'icon' => 'money', 'href' => route('student.fees.index'), 'active' => request()->routeIs('student.fees.*')],
                ['label' => 'Exam Papers',   'icon' => 'doc',   'href' => route('student.exam-papers.index'), 'active' => request()->routeIs('student.exam-papers.*')],
                ['label' => 'Quizzes',       'icon' => 'quiz',  'href' => route('student.quizzes.index'), 'active' => request()->routeIs('student.quizzes.*')],
                $resourcesGroup,
                $gamesGroup,
                ['label' => 'Patch Notes',   'icon' => 'sparkles', 'href' => route('patch-notes.index'), 'active' => request()->routeIs('patch-notes.*', 'admin.patch-notes.*')],
                ['label' => 'Profile',       'icon' => 'user',  'href' => route('profile.edit'), 'active' => request()->routeIs('profile.*')],
            ];
        @endphp

        <div x-data="{ sidebarOpen: false }" class="min-h-screen bg-cream">

            <!-- Mobile backdrop -->
            <div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false"
                 class="fixed inset-0 z-30 bg-ink/40 lg:hidden" style="display:none"></div>

            <!-- Sidebar -->
            <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
                   class="fixed inset-y-0 left-0 z-40 flex w-64 transform flex-col overflow-y-auto bg-white border-r border-gray-200 transition-transform duration-200 lg:translate-x-0">
                <div class="flex shrink-0 items-center justify-center border-b border-gray-200 px-4 py-3">
                    <a href="{{ route('dashboard') }}" class="flex items-center justify-center">
                        <img src="{{ asset('images/logo/wowlo_logo.png') }}" alt="Wowlo" class="h-40 w-auto">
                    </a>
                </div>

                <nav class="flex flex-col gap-1 p-3">
                    @foreach ($menu as $item)
                        @if (! empty($item['children']))
                            {{-- Collapsible group (e.g. Resources). Open by default when a child is active. --}}
                            <div x-data="{ open: {{ $item['active'] ? 'true' : 'false' }} }">
                                <button type="button" @click="open = ! open"
                                        @class([
                                            'flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold transition-colors duration-200 cursor-pointer',
                                            'text-primary-dark' => $item['active'],
                                            'text-ink hover:bg-primary/10 hover:text-primary-dark' => ! $item['active'],
                                        ])>
                                    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$item['icon']] }}" />
                                    </svg>
                                    <span class="flex-1 text-left">{{ $item['label'] }}</span>
                                    <svg class="h-4 w-4 shrink-0 transition-transform duration-200" :class="open && 'rotate-180'"
                                         fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                    </svg>
                                </button>
                                <div x-show="open" x-cloak class="mt-1 ml-4 flex flex-col gap-1 border-l border-gray-200 pl-3">
                                    @foreach ($item['children'] as $child)
                                        <a href="{{ $child['href'] }}"
                                           @class([
                                               'rounded-lg px-3 py-2 text-sm font-semibold transition-colors duration-200 cursor-pointer',
                                               'bg-primary text-white' => $child['active'],
                                               'text-ink hover:bg-primary/10 hover:text-primary-dark' => ! $child['active'],
                                           ])>{{ $child['label'] }}</a>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <a href="{{ $item['href'] }}"
                               @class([
                                   'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold transition-colors duration-200 cursor-pointer',
                                   'bg-primary text-white' => $item['active'],
                                   'text-ink hover:bg-primary/10 hover:text-primary-dark' => ! $item['active'],
                               ])>
                                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$item['icon']] }}" />
                                </svg>
                                <span class="flex-1">{{ $item['label'] }}</span>
                                @if (! empty($item['badge']))
                                    <span @class([
                                        'grid h-5 min-w-[1.25rem] place-items-center rounded-full px-1.5 text-xs font-bold',
                                        'bg-white text-primary' => $item['active'],
                                        'bg-amber text-white' => ! $item['active'],
                                    ])>{{ $item['badge'] }}</span>
                                @endif
                            </a>
                        @endif
                    @endforeach
                </nav>
            </aside>

            <!-- Content column -->
            <div class="lg:pl-64">
                <!-- App-wide banner notifications (super_admin broadcasts) -->
                @include('partials.banners')

                <!-- Top bar -->
                <header class="sticky top-0 z-20 flex h-16 items-center gap-4 border-b border-gray-200 bg-white px-4 sm:px-6">
                    <!-- Mobile hamburger -->
                    <button @click="sidebarOpen = true" class="lg:hidden text-ink cursor-pointer" aria-label="Open menu">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>

                    <div class="flex-1">
                        @isset($header)
                            <h1 class="text-lg font-bold text-ink">{{ $header }}</h1>
                        @endisset
                    </div>

                    <!-- User dropdown -->
                    <div x-data="{ menu: false }" class="relative">
                        <button @click="menu = !menu" class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm font-semibold text-ink hover:bg-gray-100 cursor-pointer">
                            <span class="grid h-8 w-8 place-items-center rounded-full bg-primary text-white">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </span>
                            <span class="hidden sm:block">{{ auth()->user()->name }}</span>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>
                        <div x-show="menu" @click.outside="menu = false" x-transition style="display:none"
                             class="absolute right-0 mt-2 w-48 rounded-lg border border-gray-200 bg-white py-1 shadow-lg">
                            <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-ink hover:bg-gray-100">Profile</a>
                            <button type="button" @click="$dispatch('wowlo:replay-onboarding'); menu = false"
                                    class="block w-full px-4 py-2 text-left text-sm text-ink hover:bg-gray-100 cursor-pointer">Replay tutorial</button>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-danger hover:bg-gray-100 cursor-pointer">Log out</button>
                            </form>
                        </div>
                    </div>
                </header>

                <!-- Page content -->
                <main class="p-4 sm:p-6 lg:p-8">
                    {{ $slot }}
                </main>
            </div>
        </div>

        @include('partials.pwa-install')
        @include('partials.onboarding')
    </body>
</html>
