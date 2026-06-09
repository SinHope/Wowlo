<x-app-layout>
    <x-slot name="header">Dashboard</x-slot>

    <div class="mx-auto max-w-5xl space-y-6">
        <div>
            <h2 class="text-2xl font-extrabold text-ink">Hi, {{ explode(' ', auth()->user()->name)[0] }} 👋</h2>
            <p class="text-muted">Here's what's coming up.</p>
        </div>

        @include('partials.push-enable')

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <!-- Upcoming homework -->
            <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center gap-2">
                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-primary/10 text-primary-dark">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" /></svg>
                    </span>
                    <h3 class="text-lg font-bold text-ink">Upcoming Homework</h3>
                </div>
                @forelse ($upcomingHomework as $hw)
                    <a href="{{ route('student.homework.show', $hw) }}" class="flex items-center justify-between gap-3 rounded-xl px-3 py-2.5 transition-colors hover:bg-cream">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-ink">{{ $hw->title }}</p>
                            <p class="truncate text-xs text-muted">{{ $hw->subject }} · due {{ $hw->due_date->format('d M Y') }}</p>
                        </div>
                        <svg class="h-4 w-4 shrink-0 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                    </a>
                @empty
                    <div class="rounded-xl bg-cream py-8 text-center">
                        <p class="font-semibold text-ink">All caught up! 🎉</p>
                        <p class="text-sm text-muted">No homework due right now.</p>
                    </div>
                @endforelse
                @if ($pendingCount > 3)
                    <a href="{{ route('student.homework.index') }}" class="mt-2 block text-center text-sm font-semibold text-primary-dark hover:underline">View all {{ $pendingCount }} pending</a>
                @endif
            </div>

            <!-- Quizzes to do -->
            <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center gap-2">
                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-primary/10 text-primary-dark">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z" /></svg>
                    </span>
                    <h3 class="text-lg font-bold text-ink">Quizzes to do</h3>
                    @if (($pendingQuizCount ?? 0) > 0)
                        <span class="ml-auto rounded-full bg-accent px-2 py-0.5 text-xs font-bold text-white">{{ $pendingQuizCount }} to do</span>
                    @endif
                </div>
                @forelse ($pendingQuizzes as $quiz)
                    <a href="{{ route('student.quizzes.show', $quiz) }}" class="flex items-center justify-between gap-3 rounded-xl px-3 py-2.5 transition-colors hover:bg-cream">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-ink">{{ $quiz->title }}</p>
                            <p class="truncate text-xs text-muted">{{ $quiz->subject }} · {{ $quiz->examTypeLabel() }}</p>
                        </div>
                        <svg class="h-4 w-4 shrink-0 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                    </a>
                @empty
                    <div class="rounded-xl bg-cream py-8 text-center">
                        <p class="font-semibold text-ink">All caught up! 🎉</p>
                        <p class="text-sm text-muted">No quizzes to do right now.</p>
                    </div>
                @endforelse
                @if (($pendingQuizCount ?? 0) > 3)
                    <a href="{{ route('student.quizzes.index') }}" class="mt-2 block text-center text-sm font-semibold text-primary-dark hover:underline">View all {{ $pendingQuizCount }} quizzes</a>
                @endif
            </div>

            <!-- Latest message -->
            <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center gap-2">
                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-accent/10 text-accent-dark">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg>
                    </span>
                    <h3 class="text-lg font-bold text-ink">Latest Message</h3>
                    @if (($unreadMessages ?? 0) > 0)
                        <span class="ml-auto rounded-full bg-amber px-2 py-0.5 text-xs font-bold text-white">{{ $unreadMessages }} new</span>
                    @endif
                </div>
                @if ($latestMessage)
                    <a href="{{ route('student.messages.show', $latestMessage) }}" class="block rounded-xl px-3 py-2.5 transition-colors hover:bg-cream">
                        <div class="flex items-center gap-2">
                            <p @class(['truncate text-ink', 'font-extrabold' => ! $latestMessage->is_read, 'font-semibold' => $latestMessage->is_read])>{{ $latestMessage->subject }}</p>
                            @unless ($latestMessage->is_read)
                                <span class="h-2 w-2 shrink-0 rounded-full bg-primary"></span>
                            @endunless
                        </div>
                        <p class="mt-0.5 line-clamp-2 text-sm text-muted">{{ $latestMessage->body }}</p>
                        <p class="mt-1 text-xs text-muted">{{ $latestMessage->created_at->format('d M Y') }}</p>
                    </a>
                    <a href="{{ route('student.messages.index') }}" class="mt-2 block text-center text-sm font-semibold text-primary-dark hover:underline">View all messages</a>
                @else
                    <div class="rounded-xl bg-cream py-8 text-center">
                        <p class="font-semibold text-ink">No messages yet</p>
                        <p class="text-sm text-muted">Messages from your tutor will appear here.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Outstanding payment -->
        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-muted">Outstanding Payment</p>
                    <p class="mt-1 text-2xl font-extrabold text-ink">Locked 🔒</p>
                    <p class="text-xs text-muted">Ask your parent to unlock the fee section.</p>
                </div>
                <a href="{{ route('student.fees.index') }}" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white transition-colors duration-200 hover:bg-primary-dark cursor-pointer">View fees</a>
            </div>
        </div>
    </div>
</x-app-layout>
