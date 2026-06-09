<x-app-layout>
    <x-slot name="header">Student Answers</x-slot>

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
            <a href="{{ route('tutor.quizzes.show', $quiz) }}" class="float-left text-sm font-semibold text-primary hover:underline">&larr; Back to quiz</a>
            <p class="text-sm font-semibold text-muted">{{ $quiz->title }}</p>
            <p class="mt-1 text-lg font-extrabold text-ink">{{ $attempt->student->name }}</p>
            <p class="mt-2 text-4xl font-extrabold text-ink">{{ $attempt->obtained_marks }} <span class="text-2xl text-muted">/ {{ $attempt->total_marks }}</span></p>
            <p class="mt-1 text-lg font-bold {{ $pct >= 50 ? 'text-success' : 'text-danger' }}">{{ $pct }}%</p>
            @if ($attempt->completed_at)
                <p class="mt-1 text-xs text-muted">Submitted {{ $attempt->completed_at->format('j M Y, g:ia') }}</p>
            @endif
        </div>

        {{-- Feedback to the student (only completed attempts reach this page) --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <h3 class="text-lg font-bold text-ink">Feedback for {{ $attempt->student->name }}</h3>
                @if (filled($attempt->feedback))
                    <span class="rounded-full bg-success/10 px-3 py-1 text-xs font-bold text-success">Sent</span>
                @endif
            </div>
            <p class="mt-0.5 text-sm text-muted">
                When you send this, {{ $attempt->student->name }} gets it in their inbox as a notification.
            </p>

            <form method="POST" action="{{ route('tutor.quizzes.attempts.feedback', [$quiz, $attempt]) }}" class="mt-4">
                @csrf
                <textarea name="feedback" rows="4" required
                          placeholder="e.g. Great work on the fractions questions. Revisit Q3 — re-read the question before choosing."
                          class="block w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">{{ old('feedback', $attempt->feedback) }}</textarea>
                @error('feedback')
                    <p class="mt-1 text-xs font-semibold text-danger">{{ $message }}</p>
                @enderror
                <div class="mt-3 flex justify-end">
                    <button type="submit"
                            class="rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-dark cursor-pointer">
                        {{ filled($attempt->feedback) ? 'Update & resend feedback' : 'Send feedback' }}
                    </button>
                </div>
            </form>
        </div>

        @if ($quiz->hasShortAnswers())
            <div class="flex justify-end">
                <a href="{{ route('tutor.quizzes.attempts.grade', [$quiz, $attempt]) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg border border-primary/30 px-3 py-2 text-sm font-semibold text-primary hover:bg-primary/5 cursor-pointer">
                    Edit marking
                </a>
            </div>
        @endif

        {{-- Per-question review (read-only) --}}
        @foreach ($quiz->questions as $i => $question)
            @php($answer = $answersByQuestion->get($question->id))

            <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <p class="font-bold text-ink">{{ $i + 1 }}. {{ $question->question_text }}</p>
                    @if ($question->isShortAnswer())
                        @php($grade = $answer?->grade)
                        <span @class([
                            'shrink-0 rounded-full px-3 py-1 text-xs font-bold',
                            'bg-success/10 text-success' => $grade === 'correct',
                            'bg-amber/10 text-amber-600' => $grade === 'partial',
                            'bg-danger/10 text-danger' => $grade === 'wrong',
                            'bg-gray-100 text-muted' => $grade === null,
                        ])>
                            {{ $answer?->gradeLabel() ?? 'Not marked' }} · {{ $answer->marks_awarded ?? 0 }} / {{ $question->marks }}
                        </span>
                    @elseif ($answer && $answer->is_correct)
                        <span class="shrink-0 rounded-full bg-success/10 px-3 py-1 text-xs font-bold text-success">+{{ $answer->marks_awarded }}</span>
                    @else
                        <span class="shrink-0 rounded-full bg-danger/10 px-3 py-1 text-xs font-bold text-danger">0 / {{ $question->marks }}</span>
                    @endif
                </div>

                @if ($question->hasImage())
                    <div class="mt-3">
                        @if ($question->imageIsPreviewable())
                            <img src="{{ route('tutor.quizzes.questions.image', $question) }}" alt="Diagram" class="max-h-60 rounded-lg border border-gray-200">
                        @else
                            <a href="{{ route('tutor.quizzes.questions.image', $question) }}" target="_blank" class="text-xs font-semibold text-primary hover:underline">View attachment</a>
                        @endif
                    </div>
                @endif

                @if ($question->isShortAnswer())
                    <div class="mt-4 rounded-xl border border-gray-100 bg-gray-50 p-3">
                        <p class="text-xs font-bold uppercase tracking-wide text-muted">Their answer</p>
                        @if (filled($answer?->student_answer))
                            <p class="mt-1 whitespace-pre-line text-sm text-ink">{{ $answer->student_answer }}</p>
                        @else
                            <p class="mt-1 text-sm font-semibold text-danger">Left blank.</p>
                        @endif
                    </div>
                @else
                    @php($chosen = $answer?->student_answer)
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
                                        <span class="text-muted">Their answer</span>
                                    @endif
                                    @if ($isCorrectOption)
                                        <span class="text-success">Correct</span>
                                    @endif
                                </span>
                            </div>
                        @endforeach
                        @unless ($chosen)
                            <p class="text-xs font-semibold text-danger">Left this question unanswered.</p>
                        @endunless
                    </div>
                @endif

                {{-- The feedback the tutor gave on this question --}}
                @if ($answer && (filled($answer->tutor_feedback) || $answer->hasTutorFeedbackImage()))
                    <div class="mt-4 rounded-xl bg-primary/5 p-3">
                        <p class="text-xs font-bold uppercase tracking-wide text-primary">Your feedback</p>
                        @if (filled($answer->tutor_feedback))
                            <p class="mt-1 whitespace-pre-line text-sm text-ink">{{ $answer->tutor_feedback }}</p>
                        @endif
                        @if ($answer->hasTutorFeedbackImage())
                            <img src="{{ route('tutor.quizzes.answers.feedback-image', $answer) }}" alt="Feedback image" class="mt-2 max-h-60 rounded-lg border border-gray-200">
                        @endif
                    </div>
                @endif

                {{-- The student's written correction, if they wrote one --}}
                @if ($answer && filled($answer->correction))
                    <div class="mt-4 rounded-xl bg-amber/5 p-3">
                        <p class="text-xs font-bold uppercase tracking-wide text-amber-600">Their correction</p>
                        <p class="mt-1 whitespace-pre-line text-sm text-ink">{{ $answer->correction }}</p>
                    </div>
                @elseif ($answer && $answer->needsCorrection())
                    <p class="mt-4 text-xs font-semibold text-muted">No correction written yet.</p>
                @endif
            </div>
        @endforeach
    </div>
</x-app-layout>
