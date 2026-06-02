<x-app-layout>
    <x-slot name="header">Quiz Results</x-slot>

    <div class="mx-auto max-w-3xl space-y-5">

        @if (session('status'))
            <div x-data="{ show: true }" x-show="show" x-transition
                 class="flex items-center justify-between rounded-xl border border-success/30 bg-success/10 px-4 py-3">
                <p class="text-sm font-semibold text-success">{{ session('status') }}</p>
                <button @click="show = false" class="text-success cursor-pointer" aria-label="Dismiss">&times;</button>
            </div>
        @endif

        {{-- Score header --}}
        @php($pct = $attempt->total_marks > 0 ? round($attempt->obtained_marks / $attempt->total_marks * 100) : 0)
        <div class="rounded-2xl border border-gray-100 bg-white p-6 text-center shadow-sm">
            <a href="{{ route('student.quizzes.index') }}" class="float-left text-sm font-semibold text-primary hover:underline">&larr; My quizzes</a>
            <p class="text-sm font-semibold text-muted">{{ $quiz->title }}</p>
            <p class="mt-2 text-4xl font-extrabold text-ink">{{ $attempt->obtained_marks }} <span class="text-2xl text-muted">/ {{ $attempt->total_marks }}</span></p>
            <p class="mt-1 text-lg font-bold {{ $pct >= 50 ? 'text-success' : 'text-danger' }}">{{ $pct }}%</p>
        </div>

        {{-- Per-question review + corrections --}}
        <form method="POST" action="{{ route('student.quizzes.corrections', $quiz) }}" class="space-y-4">
            @csrf

            @foreach ($quiz->questions as $i => $question)
                @php($answer = $answersByQuestion->get($question->id))
                @php($chosen = $answer?->student_answer)
                @php($correct = $answer && $answer->is_correct)

                <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <p class="font-bold text-ink">{{ $i + 1 }}. {{ $question->question_text }}</p>
                        @if ($correct)
                            <span class="shrink-0 rounded-full bg-success/10 px-3 py-1 text-xs font-bold text-success">+{{ $answer->marks_awarded }}</span>
                        @else
                            <span class="shrink-0 rounded-full bg-danger/10 px-3 py-1 text-xs font-bold text-danger">0 / {{ $question->marks }}</span>
                        @endif
                    </div>

                    @if ($question->hasImage())
                        <div class="mt-3">
                            @if ($question->imageIsPreviewable())
                                <img src="{{ route('student.quizzes.questions.image', $question) }}" alt="Diagram" class="max-h-60 rounded-lg border border-gray-200">
                            @else
                                <a href="{{ route('student.quizzes.questions.image', $question) }}" target="_blank" class="text-xs font-semibold text-primary hover:underline">View attachment</a>
                            @endif
                        </div>
                    @endif

                    <div class="mt-4 space-y-2">
                        @foreach (['A', 'B', 'C', 'D'] as $letter)
                            @php($isCorrectOption = $question->correct_answer === $letter)
                            @php($isChosen = $chosen === $letter)
                            <div @class([
                                'flex items-center gap-2 rounded-lg border px-3 py-2.5 text-sm',
                                'border-success bg-success/10' => $isCorrectOption,
                                'border-danger bg-danger/10' => $isChosen && ! $isCorrectOption,
                                'border-gray-200' => ! $isCorrectOption && ! $isChosen,
                            ])>
                                <span class="font-bold text-muted">{{ $letter }}.</span>
                                <span class="text-ink">{{ $question->optionText($letter) }}</span>
                                <span class="ml-auto flex items-center gap-2 text-xs font-semibold">
                                    @if ($isChosen)
                                        <span class="text-muted">Your answer</span>
                                    @endif
                                    @if ($isCorrectOption)
                                        <span class="text-success">Correct</span>
                                    @endif
                                </span>
                            </div>
                        @endforeach
                        @unless ($chosen)
                            <p class="text-xs font-semibold text-danger">You didn't answer this question.</p>
                        @endunless
                    </div>

                    {{-- Corrections box (only for wrong answers) --}}
                    @unless ($correct)
                        <div class="mt-4 rounded-xl bg-amber/5 p-3">
                            <label class="block text-xs font-bold uppercase tracking-wide text-amber-600">Your correction</label>
                            <p class="mb-1.5 text-xs text-muted">Write out the correct working/answer in your own words to help you remember.</p>
                            <textarea name="corrections[{{ $answer->id }}]" rows="2"
                                      placeholder="e.g. The answer is B because…"
                                      class="block w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">{{ $answer->correction }}</textarea>
                        </div>
                    @endunless
                </div>
            @endforeach

            @php($hasWrong = $attempt->answers->contains(fn ($a) => ! $a->is_correct))
            @if ($hasWrong)
                <div class="flex justify-end">
                    <button type="submit"
                            class="rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-dark cursor-pointer">
                        Save Corrections
                    </button>
                </div>
            @endif
        </form>
    </div>
</x-app-layout>
