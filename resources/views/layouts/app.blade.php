<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Wowlo') }}</title>

        <link rel="icon" type="image/x-icon" href="{{ asset('images/favicon/wowlo_favicon.ico') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=nunito:300,400,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-ink">
        @php
            $isTutor = auth()->user()->isTutor();

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
            ];

            $menu = $isTutor ? [
                ['label' => 'Dashboard',         'icon' => 'home',  'href' => route('dashboard'), 'active' => request()->routeIs('dashboard')],
                ['label' => 'Students',          'icon' => 'users', 'href' => route('tutor.students.index'), 'active' => request()->routeIs('tutor.students.*')],
                ['label' => 'Homework',          'icon' => 'book',  'href' => route('tutor.homework.index'), 'active' => request()->routeIs('tutor.homework.index', 'tutor.homework.create', 'tutor.homework.edit')],
                ['label' => 'Homework Status',   'icon' => 'check', 'href' => route('tutor.homework.status'), 'active' => request()->routeIs('tutor.homework.status')],
                ['label' => 'Messages',          'icon' => 'mail',  'href' => route('tutor.messages.index'), 'active' => request()->routeIs('tutor.messages.*')],
                ['label' => 'Finance',           'icon' => 'money', 'href' => route('tutor.finance.index'), 'active' => request()->routeIs('tutor.finance.*')],
                ['label' => 'WhatsApp Billing',  'icon' => 'chat',  'href' => route('tutor.billing.index'), 'active' => request()->routeIs('tutor.billing.*')],
                ['label' => 'Exam Papers',       'icon' => 'doc',   'href' => route('tutor.exam-papers.index'), 'active' => request()->routeIs('tutor.exam-papers.*')],
                ['label' => 'Quizzes',           'icon' => 'quiz',  'href' => route('tutor.quizzes.index'), 'active' => request()->routeIs('tutor.quizzes.*')],
            ] : [
                ['label' => 'Dashboard',     'icon' => 'home',  'href' => route('dashboard'), 'active' => request()->routeIs('dashboard')],
                ['label' => 'Homework',      'icon' => 'book',  'href' => route('student.homework.index'), 'active' => request()->routeIs('student.homework.*')],
                ['label' => 'Messages',      'icon' => 'mail',  'href' => route('student.messages.index'), 'active' => request()->routeIs('student.messages.*'), 'badge' => auth()->user()->receivedMessages()->where('is_read', false)->count()],
                ['label' => 'Tuition Fee',   'icon' => 'money', 'href' => route('student.fees.index'), 'active' => request()->routeIs('student.fees.*')],
                ['label' => 'Exam Papers',   'icon' => 'doc',   'href' => route('student.exam-papers.index'), 'active' => request()->routeIs('student.exam-papers.*')],
                ['label' => 'Quizzes',       'icon' => 'quiz',  'href' => route('student.quizzes.index'), 'active' => request()->routeIs('student.quizzes.*')],
                ['label' => 'Profile',       'icon' => 'user',  'href' => route('profile.edit'), 'active' => request()->routeIs('profile.*')],
            ];
        @endphp

        <div x-data="{ sidebarOpen: false }" class="min-h-screen bg-cream">

            <!-- Mobile backdrop -->
            <div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false"
                 class="fixed inset-0 z-30 bg-ink/40 lg:hidden" style="display:none"></div>

            <!-- Sidebar -->
            <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
                   class="fixed inset-y-0 left-0 z-40 w-64 transform bg-white border-r border-gray-200 transition-transform duration-200 lg:translate-x-0">
                <div class="flex h-16 items-center gap-2 border-b border-gray-200 px-4">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                        <img src="{{ asset('images/logo/wowlo_logo.png') }}" alt="Wowlo" class="h-10 w-auto">
                    </a>
                </div>

                <nav class="flex flex-col gap-1 p-3">
                    @foreach ($menu as $item)
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
                    @endforeach
                </nav>
            </aside>

            <!-- Content column -->
            <div class="lg:pl-64">
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
    </body>
</html>
