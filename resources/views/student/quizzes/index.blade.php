<x-app-layout>
    <x-slot name="header">Quizzes</x-slot>

    <div class="mx-auto max-w-4xl space-y-5">

        @if (session('status'))
            <div x-data="{ show: true }" x-show="show" x-transition
                 class="flex items-center justify-between rounded-xl border border-success/30 bg-success/10 px-4 py-3">
                <p class="text-sm font-semibold text-success">{{ session('status') }}</p>
                <button @click="show = false" class="text-success cursor-pointer" aria-label="Dismiss">&times;</button>
            </div>
        @endif

        <div>
            <h2 class="text-2xl font-extrabold text-ink">My Quizzes</h2>
            <p class="text-muted">Quizzes your tutor has assigned to you</p>
        </div>

        @forelse ($quizzes as $quiz)
            @php($attempt = $attempts->get($quiz->id))
            {{-- Share the already-loaded quiz so isGraded()/percentage() don't re-query. --}}
            @php($attempt?->setRelation('quiz', $quiz))
            <div class="flex items-center gap-4 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10">
                    <svg class="h-5 w-5 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z" />
                    </svg>
                </div>

                <div class="min-w-0 flex-1">
                    <p class="truncate font-bold text-ink">{{ $quiz->title }}</p>
                    <div class="mt-0.5 flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-semibold text-primary">{{ $quiz->subject }}</span>
                        <span class="rounded-full bg-amber/10 px-2 py-0.5 text-xs font-semibold text-amber-600">{{ $quiz->examTypeLabel() }}</span>
                        <span class="text-xs text-muted">{{ $quiz->questions_count }} {{ Str::plural('question', $quiz->questions_count) }}</span>
                    </div>
                </div>

                <div class="flex shrink-0 flex-col items-end gap-2">
                    @if ($attempt && $attempt->isCompleted() && ! $attempt->isGraded())
                        <span class="rounded-full bg-amber/10 px-3 py-1 text-xs font-semibold text-amber-600">Awaiting marking</span>
                        <a href="{{ route('student.quizzes.result', $quiz) }}"
                           class="text-xs font-semibold text-primary hover:underline cursor-pointer">View &rarr;</a>
                    @elseif ($attempt && $attempt->isCompleted())
                        <span class="rounded-full bg-success/10 px-3 py-1 text-sm font-bold text-success">
                            {{ $attempt->obtained_marks }} / {{ $attempt->total_marks }} · {{ $attempt->percentage() }}%
                        </span>
                        <a href="{{ route('student.quizzes.result', $quiz) }}"
                           class="text-xs font-semibold text-primary hover:underline cursor-pointer">View results &rarr;</a>
                    @elseif ($attempt)
                        <span class="rounded-full bg-amber/10 px-3 py-1 text-xs font-semibold text-amber-600">In progress</span>
                        <a href="{{ route('student.quizzes.show', $quiz) }}"
                           class="rounded-lg bg-primary px-3 py-1.5 text-xs font-semibold text-white hover:bg-primary-dark cursor-pointer">Resume</a>
                    @else
                        <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-muted">Not started</span>
                        <a href="{{ route('student.quizzes.show', $quiz) }}"
                           class="rounded-lg bg-primary px-3 py-1.5 text-xs font-semibold text-white hover:bg-primary-dark cursor-pointer">Start quiz</a>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-gray-100 bg-white py-16 text-center shadow-sm">
                <p class="text-lg font-bold text-ink">No quizzes yet</p>
                <p class="mt-1 text-sm text-muted">Your tutor hasn't assigned any quizzes to you.</p>
            </div>
        @endforelse
    </div>
</x-app-layout>
