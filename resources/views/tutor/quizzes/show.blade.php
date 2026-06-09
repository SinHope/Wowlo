<x-app-layout>
    <x-slot name="header">Quiz</x-slot>

    <div class="mx-auto max-w-4xl space-y-5">

        @if (session('status'))
            <div x-data="{ show: true }" x-show="show" x-transition
                 class="flex items-center justify-between rounded-xl border border-success/30 bg-success/10 px-4 py-3">
                <p class="text-sm font-semibold text-success">{{ session('status') }}</p>
                <button @click="show = false" class="text-success cursor-pointer" aria-label="Dismiss">&times;</button>
            </div>
        @endif

        {{-- Header --}}
        <div class="flex items-start justify-between gap-4">
            <div>
                <a href="{{ route('tutor.quizzes.index') }}" class="text-sm font-semibold text-primary hover:underline">&larr; All quizzes</a>
                <h2 class="mt-1 text-2xl font-extrabold text-ink">{{ $quiz->title }}</h2>
                <div class="mt-2 flex flex-wrap gap-2">
                    <span class="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-semibold text-primary">{{ $quiz->level }}</span>
                    <span class="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-semibold text-primary">{{ $quiz->subject }}</span>
                    @if ($quiz->topic)
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-muted">{{ $quiz->topic }}</span>
                    @endif
                    <span class="rounded-full bg-amber/10 px-2 py-0.5 text-xs font-semibold text-amber-600">{{ $quiz->examTypeLabel() }}</span>
                </div>
                <p class="mt-2 text-sm text-muted">{{ $quiz->questions->count() }} questions · {{ $quiz->totalMarks() }} total marks</p>
            </div>
            <form method="POST" action="{{ route('tutor.quizzes.destroy', $quiz) }}"
                  onsubmit="return confirm('Delete this quiz? All assignments and student attempts will be removed.')">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-danger/30 px-3 py-2 text-sm font-semibold text-danger hover:bg-danger/5 cursor-pointer">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                    </svg>
                    Delete
                </button>
            </form>
        </div>

        {{-- Assign to students --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-bold text-ink">Assign to students</h3>
            <p class="mt-0.5 text-sm text-muted">Tick the students who should see this quiz, then save.</p>

            @if ($students->isEmpty())
                <p class="mt-4 text-sm text-muted">No student accounts yet.</p>
            @else
                <form method="POST" action="{{ route('tutor.quizzes.assign', $quiz) }}" class="mt-4">
                    @csrf
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        @foreach ($students as $student)
                            <label class="flex items-center gap-2.5 rounded-lg border border-gray-200 px-3 py-2.5 text-sm hover:bg-gray-50 cursor-pointer">
                                <input type="checkbox" name="student_ids[]" value="{{ $student->id }}"
                                       @checked(in_array($student->id, $assignedIds))
                                       class="rounded text-primary focus:ring-primary">
                                <span class="font-semibold text-ink">{{ $student->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    <div class="mt-4 flex justify-end">
                        <button type="submit"
                                class="rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-dark cursor-pointer">
                            Save Assignments
                        </button>
                    </div>
                </form>
            @endif
        </div>

        {{-- Submissions — every assigned student + their status --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-bold text-ink">Submissions</h3>
            @php($assignedStudents = $students->whereIn('id', $assignedIds))
            @forelse ($assignedStudents as $student)
                @php($attempt = $attemptsByStudent->get($student->id))
                @if (! $attempt)
                    <div class="mt-3 flex items-center justify-between rounded-lg border border-gray-100 px-4 py-3">
                        <span class="font-semibold text-ink">{{ $student->name }}</span>
                        <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-muted">Not attempted</span>
                    </div>
                @elseif ($attempt->needsMarking())
                    <a href="{{ route('tutor.quizzes.attempts.grade', [$quiz, $attempt]) }}"
                       class="mt-3 flex items-center justify-between gap-3 rounded-lg border border-amber/30 bg-amber/5 px-4 py-3 hover:bg-amber/10 cursor-pointer">
                        <span class="font-semibold text-ink">{{ $student->name }}</span>
                        <span class="flex items-center gap-2">
                            <span class="rounded-full bg-amber/10 px-3 py-1 text-xs font-bold text-amber-600">Awaiting marking</span>
                            <span class="text-sm font-semibold text-primary">Mark now &rarr;</span>
                        </span>
                    </a>
                @elseif ($attempt->isGraded())
                    <a href="{{ route('tutor.quizzes.attempts.show', [$quiz, $attempt]) }}"
                       class="mt-3 flex items-center justify-between gap-3 rounded-lg border border-gray-100 px-4 py-3 hover:bg-gray-50 cursor-pointer">
                        <span class="font-semibold text-ink">{{ $student->name }}</span>
                        <span class="flex items-center gap-2">
                            <span class="rounded-full bg-success/10 px-3 py-1 text-sm font-bold text-success">
                                {{ $attempt->obtained_marks }} / {{ $attempt->total_marks }} · {{ $attempt->percentage() }}%
                            </span>
                            <span class="text-sm font-semibold text-primary">View answers &rarr;</span>
                        </span>
                    </a>
                @else
                    <div class="mt-3 flex items-center justify-between rounded-lg border border-gray-100 px-4 py-3">
                        <span class="font-semibold text-ink">{{ $student->name }}</span>
                        <span class="rounded-full bg-amber/10 px-3 py-1 text-xs font-semibold text-amber-600">In progress</span>
                    </div>
                @endif
            @empty
                <p class="mt-3 text-sm text-muted">No students assigned yet.</p>
            @endforelse
        </div>

        {{-- Question preview (with correct answers) --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <h3 class="mb-4 text-lg font-bold text-ink">Questions &amp; answer key</h3>
            <div class="space-y-4">
                @foreach ($quiz->questions as $i => $question)
                    <div class="rounded-xl border border-gray-100 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <p class="font-semibold text-ink">{{ $i + 1 }}. {{ $question->question_text }}</p>
                            <span class="shrink-0 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-muted">{{ $question->marks }} {{ Str::plural('mark', $question->marks) }}</span>
                        </div>

                        @if ($question->hasImage())
                            <div class="mt-2">
                                @if ($question->imageIsPreviewable())
                                    <img src="{{ route('tutor.quizzes.questions.image', $question) }}"
                                         alt="Diagram for question {{ $i + 1 }}"
                                         class="max-h-64 rounded-lg border border-gray-200">
                                @else
                                    <a href="{{ route('tutor.quizzes.questions.image', $question) }}" target="_blank"
                                       class="inline-flex items-center gap-1.5 rounded-lg border border-primary/30 px-3 py-1.5 text-xs font-semibold text-primary hover:bg-primary/5">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                                        View attachment ({{ $question->image_name }})
                                    </a>
                                @endif
                            </div>
                        @endif

                        @if ($question->isShortAnswer())
                            <p class="mt-2 inline-flex items-center gap-1.5 rounded-md bg-amber/10 px-2 py-1 text-xs font-semibold text-amber-600">
                                Short answer — marked manually
                            </p>
                        @else
                            <div class="mt-2 space-y-1">
                                @foreach (['A', 'B', 'C', 'D'] as $letter)
                                    <div @class([
                                        'flex items-center gap-2 rounded-md px-2 py-1 text-sm',
                                        'bg-success/10 font-semibold text-success' => $question->correct_answer === $letter,
                                        'text-ink' => $question->correct_answer !== $letter,
                                    ])>
                                        <span class="font-bold">{{ $letter }}.</span>
                                        <span>{{ $question->optionText($letter) }}</span>
                                        @if ($question->correct_answer === $letter)
                                            <svg class="ml-auto h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
