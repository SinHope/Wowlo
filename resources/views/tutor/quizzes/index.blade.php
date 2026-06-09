<x-app-layout>
    <x-slot name="header">Quizzes</x-slot>

    <div class="mx-auto max-w-5xl space-y-5">

        @if (session('status'))
            <div x-data="{ show: true }" x-show="show" x-transition
                 class="flex items-center justify-between rounded-xl border border-success/30 bg-success/10 px-4 py-3">
                <p class="text-sm font-semibold text-success">{{ session('status') }}</p>
                <button @click="show = false" class="text-success cursor-pointer" aria-label="Dismiss">&times;</button>
            </div>
        @endif

        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-extrabold text-ink">Quizzes</h2>
                <p class="text-muted">{{ $quizzes->total() }} created</p>
            </div>
            <a href="{{ route('tutor.quizzes.create') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-primary-dark cursor-pointer">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Create Quiz
            </a>
        </div>

        @forelse ($quizzes as $quiz)
            <a href="{{ route('tutor.quizzes.show', $quiz) }}"
               class="flex items-center gap-4 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm transition-colors duration-200 hover:border-primary/40 hover:bg-primary/5 cursor-pointer">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10">
                    <svg class="h-5 w-5 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z" />
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="truncate font-bold text-ink">{{ $quiz->title }}</p>
                        @if ($quiz->to_mark_count > 0)
                            <span class="inline-flex items-center gap-1 rounded-full bg-accent px-2 py-0.5 text-xs font-bold text-white">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" /></svg>
                                {{ $quiz->to_mark_count }} pending for marking
                            </span>
                        @endif
                    </div>
                    <div class="mt-0.5 flex flex-wrap gap-2">
                        <span class="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-semibold text-primary">{{ $quiz->level }}</span>
                        <span class="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-semibold text-primary">{{ $quiz->subject }}</span>
                        <span class="rounded-full bg-amber/10 px-2 py-0.5 text-xs font-semibold text-amber-600">{{ $quiz->exam_type }}</span>
                    </div>
                    @php($notAttempted = max(0, $quiz->assignments_count - $quiz->attempts_count))
                    <p class="mt-1 text-xs text-muted">
                        {{ $quiz->questions_count }} {{ Str::plural('question', $quiz->questions_count) }}
                        · {{ $quiz->assignments_count }} assigned
                        · {{ $quiz->attempts_count }} {{ Str::plural('attempt', $quiz->attempts_count) }}
                        @if ($notAttempted > 0)
                            · <span class="font-semibold text-muted">{{ $notAttempted }} not attempted</span>
                        @endif
                    </p>
                </div>
                <svg class="h-5 w-5 shrink-0 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            </a>
        @empty
            <div class="rounded-2xl border border-gray-100 bg-white py-16 text-center shadow-sm">
                <p class="text-lg font-bold text-ink">No quizzes yet</p>
                <p class="mt-1 text-sm text-muted">Create your first MCQ quiz and assign it to students.</p>
                <a href="{{ route('tutor.quizzes.create') }}"
                   class="mt-4 inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-dark cursor-pointer">
                    Create First Quiz
                </a>
            </div>
        @endforelse

        @if ($quizzes->hasPages())
            <div>{{ $quizzes->links() }}</div>
        @endif
    </div>
</x-app-layout>
