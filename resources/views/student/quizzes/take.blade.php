<x-app-layout>
    <x-slot name="header">{{ $quiz->title }}</x-slot>

    <div class="mx-auto max-w-3xl" x-data="{ confirming: false, submitting: false }">
        <style>[x-cloak]{display:none!important;}</style>

        {{-- Header --}}
        <div class="mb-5 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <h2 class="text-xl font-extrabold text-ink">{{ $quiz->title }}</h2>
            <div class="mt-2 flex flex-wrap gap-2">
                <span class="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-semibold text-primary">{{ $quiz->level }}</span>
                <span class="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-semibold text-primary">{{ $quiz->subject }}</span>
                <span class="rounded-full bg-amber/10 px-2 py-0.5 text-xs font-semibold text-amber-600">{{ $quiz->examTypeLabel() }}</span>
            </div>
            <p class="mt-2 text-sm text-muted">
                {{ $quiz->questions->count() }} questions · {{ $quiz->totalMarks() }} total marks.
                Choose one answer per question. <span class="font-semibold text-amber-600">You can't change your answers after submitting.</span>
            </p>
        </div>

        <form method="POST" action="{{ route('student.quizzes.submit', $quiz) }}" class="space-y-4">
            @csrf

            @foreach ($quiz->questions as $i => $question)
                <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <p class="font-bold text-ink">{{ $i + 1 }}. {{ $question->question_text }}</p>
                        <span class="shrink-0 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-muted">{{ $question->marks }} {{ Str::plural('mark', $question->marks) }}</span>
                    </div>

                    @if ($question->hasImage())
                        <div class="mt-3">
                            @if ($question->imageIsPreviewable())
                                <img src="{{ route('student.quizzes.questions.image', $question) }}"
                                     alt="Diagram for question {{ $i + 1 }}"
                                     class="max-h-72 rounded-lg border border-gray-200">
                            @else
                                <a href="{{ route('student.quizzes.questions.image', $question) }}" target="_blank"
                                   class="inline-flex items-center gap-1.5 rounded-lg border border-primary/30 px-3 py-1.5 text-xs font-semibold text-primary hover:bg-primary/5">
                                    View attachment ({{ $question->image_name }})
                                </a>
                            @endif
                        </div>
                    @endif

                    @if ($question->isShortAnswer())
                        <div class="mt-4">
                            <textarea name="answers[{{ $question->id }}]" rows="3"
                                      placeholder="Type your answer…"
                                      class="block w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"></textarea>
                            <p class="mt-1 text-xs text-muted">Your tutor will mark this answer.</p>
                        </div>
                    @else
                        <div class="mt-4 space-y-2">
                            @foreach (['A', 'B', 'C', 'D'] as $letter)
                                <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 px-3 py-2.5 text-sm transition-colors hover:bg-primary/5 has-[:checked]:border-primary has-[:checked]:bg-primary/5">
                                    <input type="radio" name="answers[{{ $question->id }}]" value="{{ $letter }}"
                                           class="text-primary focus:ring-primary">
                                    <span class="font-bold text-muted">{{ $letter }}.</span>
                                    <span class="text-ink">{{ $question->optionText($letter) }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('student.quizzes.index') }}"
                   class="rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-semibold text-ink hover:bg-gray-50 cursor-pointer">Back</a>
                <button type="button" @click="confirming = true"
                        class="rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary-dark cursor-pointer">
                    Submit Quiz
                </button>
            </div>

            {{-- Confirm modal --}}
            <div x-show="confirming" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" x-transition.opacity>
                <div class="absolute inset-0 bg-ink/50" @click="confirming = false"></div>
                <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                    <h3 class="text-lg font-bold text-ink">Submit your quiz?</h3>
                    <p class="mt-1 text-sm text-muted">Once submitted, you can't change your answers. Make sure you've answered everything you want to.</p>
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="confirming = false"
                                class="rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-semibold text-ink hover:bg-gray-50 cursor-pointer">Keep working</button>
                        <button type="submit" @click="submitting = true"
                                class="rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-dark cursor-pointer">Yes, submit</button>
                    </div>
                </div>
            </div>

            {{-- Spinner --}}
            <div x-show="submitting" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center bg-ink/40">
                <div class="flex flex-col items-center gap-3 rounded-2xl bg-white px-8 py-6 shadow-xl">
                    <svg class="h-8 w-8 animate-spin text-primary" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <p class="text-sm font-semibold text-ink">Marking your quiz…</p>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
