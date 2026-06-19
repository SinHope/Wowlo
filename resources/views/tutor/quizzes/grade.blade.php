<x-app-layout>
    <x-slot name="header">Mark Quiz</x-slot>

    @php
        // MCQ marks are fixed (auto-marked); short-answer marks are what the
        // tutor is typing now. The running total below sums both live.
        $autoMarks = $quiz->questions
            ->filter(fn ($q) => $q->isMcq())
            ->sum(fn ($q) => optional($answersByQuestion->get($q->id))->marks_awarded ?? 0);

        $marksInit = [];
        foreach ($quiz->questions as $q) {
            if ($q->isShortAnswer() && ($a = $answersByQuestion->get($q->id))) {
                $marksInit[(string) $a->id] = (float) old("marks.{$a->id}", $a->marks_awarded ?? 0);
            }
        }
    @endphp

    <div class="mx-auto max-w-3xl space-y-5"
         x-data="gradeForm(@js($marksInit), {{ $autoMarks }}, {{ $quiz->totalMarks() }})">
        <style>[x-cloak]{display:none!important;}</style>

        @if ($errors->any())
            <div class="rounded-xl border border-danger/30 bg-danger/10 px-4 py-3">
                <p class="text-sm font-semibold text-danger">Please fix the errors below before saving.</p>
            </div>
        @endif

        {{-- Header + live running total --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-6 text-center shadow-sm">
            <a href="{{ route('tutor.quizzes.show', $quiz) }}" class="float-left text-sm font-semibold text-primary hover:underline">&larr; Back to quiz</a>
            @if ($attempt->isGraded())
                <span class="float-right rounded-full bg-success/10 px-3 py-1 text-xs font-bold text-success">Graded</span>
            @else
                <span class="float-right rounded-full bg-amber/10 px-3 py-1 text-xs font-bold text-amber-600">Awaiting marking</span>
            @endif
            <p class="text-sm font-semibold text-muted">{{ $quiz->title }}</p>
            <p class="mt-1 text-lg font-extrabold text-ink">{{ $attempt->student->name }}</p>
            <p class="mt-2 text-3xl font-extrabold text-ink">
                <span x-text="awarded"></span> <span class="text-xl text-muted">/ {{ $quiz->totalMarks() }}</span>
            </p>
            <p class="mt-1 text-sm font-bold text-muted"><span x-text="pct"></span>% · running total</p>
        </div>

        <form method="POST" action="{{ route('tutor.quizzes.attempts.grade.save', [$quiz, $attempt]) }}"
              enctype="multipart/form-data" class="space-y-4"
              @keydown.enter="if ($event.target.tagName === 'INPUT') $event.preventDefault()">
            @csrf

            @foreach ($quiz->questions as $i => $question)
                @php($answer = $answersByQuestion->get($question->id))

                <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <p class="font-bold text-ink">{{ $i + 1 }}. {{ $question->question_text }}</p>
                        <span class="shrink-0 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-muted">
                            {{ $question->marks }} {{ Str::plural('mark', $question->marks) }}
                        </span>
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
                        {{-- The student's typed answer --}}
                        <div class="mt-4 rounded-xl border border-gray-100 bg-gray-50 p-3">
                            <p class="text-xs font-bold uppercase tracking-wide text-muted">Their answer</p>
                            @if (filled($answer?->student_answer))
                                <p class="mt-1 whitespace-pre-line text-sm text-ink">{{ $answer->student_answer }}</p>
                            @else
                                <p class="mt-1 text-sm font-semibold text-danger">Left blank.</p>
                            @endif
                        </div>

                        {{-- Grade radios --}}
                        <div class="mt-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-muted">Your mark</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach (['correct' => 'Correct', 'partial' => 'Partial', 'wrong' => 'Wrong'] as $value => $label)
                                    @php($checked = old("grades.{$answer->id}", $answer?->grade) === $value)
                                    <label @class([
                                        'flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-1.5 text-xs font-semibold',
                                        'border-success bg-success/10 text-success' => $checked && $value === 'correct',
                                        'border-amber bg-amber/10 text-amber-600' => $checked && $value === 'partial',
                                        'border-danger bg-danger/10 text-danger' => $checked && $value === 'wrong',
                                        'border-gray-200 text-muted hover:bg-gray-50' => ! $checked,
                                    ])>
                                        <input type="radio" name="grades[{{ $answer->id }}]" value="{{ $value }}"
                                               @checked($checked) class="text-primary focus:ring-primary">
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                            @error("grades.{$answer->id}") <p class="mt-1 text-xs font-semibold text-danger">{{ $message }}</p> @enderror
                        </div>

                        {{-- Marks awarded --}}
                        <div class="mt-3 flex items-center gap-2">
                            <label class="text-sm font-semibold text-ink">Marks awarded</label>
                            <input type="number" name="marks[{{ $answer->id }}]" min="0" max="{{ $question->marks }}" step="0.5"
                                   x-model.number="marks['{{ $answer->id }}']"
                                   class="w-24 rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                            <span class="text-sm text-muted">/ {{ $question->marks }}</span>
                            @error("marks.{$answer->id}") <p class="text-xs font-semibold text-danger">{{ $message }}</p> @enderror
                        </div>
                    @else
                        {{-- MCQ: read-only auto-mark summary --}}
                        @php($chosen = $answer?->student_answer)
                        <div class="mt-4 space-y-2">
                            @foreach (['A', 'B', 'C', 'D'] as $letter)
                                @php($isCorrectOption = $question->correct_answer === $letter)
                                @php($isChosen = $chosen === $letter)
                                <div @class([
                                    'flex items-center gap-2 rounded-lg border px-3 py-2 text-sm',
                                    'border-success bg-success/10' => $isCorrectOption,
                                    'border-danger bg-danger/10' => $isChosen && ! $isCorrectOption,
                                    'border-gray-200' => ! $isCorrectOption && ! $isChosen,
                                ])>
                                    <span class="font-bold text-muted">{{ $letter }}.</span>
                                    <span class="text-ink">{{ $question->optionText($letter) }}</span>
                                    <span class="ml-auto flex items-center gap-2 text-xs font-semibold">
                                        @if ($isChosen) <span class="text-muted">Their answer</span> @endif
                                        @if ($isCorrectOption) <span class="text-success">Correct</span> @endif
                                    </span>
                                </div>
                            @endforeach
                            <p class="text-xs font-semibold {{ $answer && $answer->is_correct ? 'text-success' : 'text-muted' }}">
                                Auto-marked: {{ $answer->marks_awarded ?? 0 }} / {{ $question->marks }}
                            </p>
                        </div>
                    @endif

                    {{-- Per-question feedback (text + optional drawn image) — for any question --}}
                    <div class="mt-4 rounded-xl bg-primary/5 p-3">
                        <label class="block text-xs font-bold uppercase tracking-wide text-primary">Feedback (optional)</label>
                        <textarea name="tutor_feedback[{{ $answer->id }}]" rows="2"
                                  placeholder="Explain how to get to the answer…"
                                  class="mt-1 block w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">{{ old("tutor_feedback.{$answer->id}", $answer?->tutor_feedback) }}</textarea>

                        @if ($answer?->hasTutorFeedbackImage())
                            <div class="mt-2">
                                <img src="{{ route('tutor.quizzes.answers.feedback-image', $answer) }}" alt="Feedback image" class="max-h-48 rounded-lg border border-gray-200">
                                <p class="mt-1 text-xs text-muted">Uploading a new image replaces this one.</p>
                            </div>
                        @endif

                        <input type="file" name="feedback_image[{{ $answer->id }}]" accept=".jpg,.jpeg,.png"
                               class="mt-2 block w-full rounded-lg border border-gray-200 px-3 py-2 text-sm
                                      file:mr-3 file:rounded-md file:border-0 file:bg-primary/10 file:px-3 file:py-1
                                      file:text-xs file:font-semibold file:text-primary hover:file:bg-primary/20">
                        <p class="mt-1 text-xs text-muted">Optional drawing/explanation (JPG or PNG, max 10 MB).</p>
                        @error("feedback_image.{$answer->id}") <p class="mt-1 text-xs font-semibold text-danger">{{ $message }}</p> @enderror
                    </div>
                </div>
            @endforeach

            {{-- Overall remarks (reuses the attempt's feedback field) --}}
            <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <label class="block text-sm font-bold text-ink">Overall remarks <span class="font-normal text-muted">(optional)</span></label>
                <p class="mt-0.5 text-xs text-muted">Sent to {{ $attempt->student->name }}'s inbox when you save.</p>
                <textarea name="remarks" rows="3"
                          placeholder="e.g. Strong working on Q2. Re-read Q4 — you had the right idea."
                          class="mt-2 block w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">{{ old('remarks', $attempt->feedback) }}</textarea>
                @error('remarks') <p class="mt-1 text-xs font-semibold text-danger">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('tutor.quizzes.show', $quiz) }}"
                   class="rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-semibold text-ink hover:bg-gray-50 cursor-pointer">Cancel</a>
                <button type="submit"
                        class="rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary-dark cursor-pointer">
                    Save marks &amp; notify student
                </button>
            </div>
        </form>
    </div>

    <script>
        function gradeForm(initialMarks, autoMarks, total) {
            return {
                marks: initialMarks,   // { answerId: marks } for short answers
                autoMarks,
                total,
                get awarded() {
                    const short = Object.values(this.marks).reduce((s, v) => s + (parseFloat(v) || 0), 0);
                    // Keep the half-mark precision tidy (avoid 1.5000001 display).
                    return Math.round((this.autoMarks + short) * 100) / 100;
                },
                get pct() {
                    return this.total > 0 ? Math.round(this.awarded / this.total * 100) : 0;
                },
            };
        }
    </script>
</x-app-layout>
