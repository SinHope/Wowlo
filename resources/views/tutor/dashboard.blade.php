<x-app-layout>
    <x-slot name="header">Dashboard</x-slot>

    <div class="mx-auto max-w-7xl space-y-6">
        <div>
            <h2 class="text-2xl font-extrabold text-ink">Welcome back, {{ explode(' ', auth()->user()->name)[0] }} 👋</h2>
            <p class="text-muted">Here's a quick look at your tuition.</p>
        </div>

        <!-- Stat cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <a href="{{ route('tutor.students.index') }}" class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm transition-colors duration-200 hover:border-primary/40 hover:bg-primary/5 cursor-pointer">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-muted">Total Students</p>
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-primary/10 text-primary-dark">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
                    </span>
                </div>
                <p class="mt-3 text-3xl font-extrabold text-ink">{{ $studentCount }}</p>
            </a>

            <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-muted">Pending Homework</p>
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-accent/10 text-accent-dark">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" /></svg>
                    </span>
                </div>
                <p class="mt-3 text-3xl font-extrabold text-ink">{{ $pendingHomework }}</p>
            </div>

            <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-muted">This Week</p>
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-success/10 text-success">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                    </span>
                </div>
                <p class="mt-3 text-3xl font-extrabold text-ink">{{ $createdThisWeek }}</p>
                <p class="text-xs text-muted">new homework created</p>
            </div>
        </div>

        <!-- Recent homework -->
        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-bold text-ink">Recent Homework</h3>
                <a href="{{ route('tutor.homework.index') }}" class="text-sm font-semibold text-primary-dark hover:underline">View all</a>
            </div>
            @forelse ($recentHomework as $hw)
                <a href="{{ route('tutor.homework.edit', $hw) }}" class="flex items-center justify-between gap-3 rounded-xl px-3 py-2.5 transition-colors hover:bg-cream">
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-ink">{{ $hw->title }}</p>
                        <p class="truncate text-xs text-muted">{{ $hw->subject }} · {{ $hw->student->name }} · due {{ $hw->due_date->format('d M') }}</p>
                    </div>
                    <x-homework-status-badge :status="$hw->status" />
                </a>
            @empty
                <div class="rounded-xl bg-cream py-10 text-center">
                    <p class="font-semibold text-ink">Nothing here yet</p>
                    <p class="text-sm text-muted">Activity will show up as you assign homework.</p>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
