<x-app-layout>
    <x-slot name="header">{{ $sheet->title }}</x-slot>

    <div class="mx-auto max-w-3xl space-y-5" x-data="{ confirmDelete: false }">
        @if (session('status'))
            <div x-data="{ show: true }" x-show="show" x-transition
                 class="flex items-center justify-between rounded-xl border border-success/30 bg-success/10 px-4 py-3">
                <p class="text-sm font-semibold text-success">{{ session('status') }}</p>
                <button @click="show = false" class="text-success cursor-pointer" aria-label="Dismiss">&times;</button>
            </div>
        @endif

        {{-- Header --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <a href="{{ route('tutor.resources.index', $sheet->type) }}" class="text-sm font-semibold text-primary hover:underline">&larr; Back to {{ $sheet->typeLabel() }}</a>
            <div class="mt-3 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-xl font-extrabold text-ink">{{ $sheet->title }}</h2>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-semibold text-primary">{{ $sheet->subject }}</span>
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-muted">{{ $sheet->student->name }}</span>
                    </div>
                </div>
                @if ($sheet->isMarked())
                    <span class="rounded-full bg-success/10 px-3 py-1 text-sm font-bold text-success">
                        {{ rtrim(rtrim(number_format($sheet->obtained_marks, 1), '0'), '.') }} / {{ $sheet->total_marks }} · {{ $sheet->percentage() }}%
                    </span>
                @elseif ($sheet->isSubmitted())
                    <span class="rounded-full bg-amber/10 px-3 py-1 text-xs font-bold text-amber-600">Awaiting marking</span>
                @else
                    <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-bold text-muted">Awaiting student</span>
                @endif
            </div>
        </div>

        @if (filled($sheet->remarks))
            <div class="rounded-2xl border border-primary/20 bg-primary/5 p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-primary">Your remarks to the student</p>
                <p class="mt-1 whitespace-pre-line text-sm text-ink">{{ $sheet->remarks }}</p>
            </div>
        @endif

        @if ($sheet->isSent())
            <div class="rounded-2xl border border-gray-100 bg-white p-6 text-center shadow-sm">
                <p class="font-bold text-ink">Sent — waiting for {{ $sheet->student->name }} to fill it in.</p>
                <p class="mt-1 text-sm text-muted">{{ $sheet->questions->count() }} {{ Str::plural('question', $sheet->questions->count()) }} · {{ $sheet->totalMarks() }} total marks.</p>
            </div>
        @else
            {{-- Submitted or marked — show the student's answers --}}
            <div class="space-y-3">
                @foreach ($sheet->questions as $i => $q)
                    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <span class="text-sm font-bold text-ink">Question {{ $i + 1 }}</span>
                            <span class="shrink-0 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-muted">{{ $q->marks }} {{ Str::plural('mark', $q->marks) }}</span>
                        </div>

                        @if (filled($q->remarks))
                            <p class="mt-2 rounded-lg bg-accent/10 px-3 py-2 text-xs text-ink"><span class="font-bold text-accent-dark">Remarks:</span> {{ $q->remarks }}</p>
                        @endif

                        @if ($sheet->isMcq())
                            <div class="mt-3 flex flex-wrap gap-2">
                                @for ($n = 1; $n <= $q->num_options; $n++)
                                    <span @class([
                                        'grid h-9 w-9 place-items-center rounded-full border text-sm font-bold',
                                        'border-primary bg-primary text-white' => $q->choice === $n,
                                        'border-gray-300 text-muted' => $q->choice !== $n,
                                    ])>{{ $n }}</span>
                                @endfor
                                @if ($q->choice === null)
                                    <span class="self-center text-xs font-semibold text-danger">Left blank</span>
                                @endif
                            </div>
                        @else
                            <div class="mt-3 rounded-xl border border-gray-100 bg-gray-50 p-3">
                                @if (filled($q->answer_text))
                                    <p class="whitespace-pre-line text-sm text-ink">{{ $q->answer_text }}</p>
                                @else
                                    <p class="text-sm font-semibold text-danger">Left blank.</p>
                                @endif
                            </div>
                        @endif

                        @if ($sheet->isMarked())
                            <div class="mt-3 flex flex-wrap items-center gap-2 text-xs font-semibold">
                                @if ($q->grade === 'correct')
                                    <span class="rounded-full bg-success/10 px-2 py-0.5 text-success">Correct</span>
                                @elseif ($q->grade === 'partial')
                                    <span class="rounded-full bg-amber/10 px-2 py-0.5 text-amber-600">Partial</span>
                                @else
                                    <span class="rounded-full bg-danger/10 px-2 py-0.5 text-danger">Wrong</span>
                                @endif
                                <span class="text-muted">{{ rtrim(rtrim(number_format($q->marks_awarded, 1), '0'), '.') }} / {{ $q->marks }}</span>
                            </div>
                            @if (filled($q->tutor_feedback))
                                <p class="mt-2 rounded-lg bg-primary/5 px-3 py-2 text-sm text-ink">{{ $q->tutor_feedback }}</p>
                            @endif
                        @endif
                    </div>
                @endforeach
            </div>

            @if ($sheet->isMarked() && filled($sheet->feedback))
                <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wide text-muted">Overall remarks</p>
                    <p class="mt-1 whitespace-pre-line text-sm text-ink">{{ $sheet->feedback }}</p>
                </div>
            @endif
        @endif

        {{-- Actions --}}
        <div class="flex items-center justify-between gap-3">
            <button type="button" @click="confirmDelete = true"
                    class="rounded-lg border border-danger/30 px-4 py-2.5 text-sm font-semibold text-danger hover:bg-danger/5 cursor-pointer">Delete</button>
            @if ($sheet->isSubmitted())
                <a href="{{ route('tutor.resources.mark', $sheet) }}"
                   class="rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary-dark cursor-pointer">Mark this sheet</a>
            @elseif ($sheet->isMarked())
                <a href="{{ route('tutor.resources.mark', $sheet) }}"
                   class="rounded-lg border border-gray-200 px-5 py-2.5 text-sm font-semibold text-ink hover:bg-gray-50 cursor-pointer">Edit marks</a>
            @endif
        </div>

        {{-- Delete confirm --}}
        <div x-show="confirmDelete" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" x-transition.opacity>
            <div class="absolute inset-0 bg-ink/50" @click="confirmDelete = false"></div>
            <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <h3 class="text-lg font-bold text-ink">Delete this sheet?</h3>
                <p class="mt-1 text-sm text-muted">This permanently removes the sheet and the student's answers. This can't be undone.</p>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="confirmDelete = false"
                            class="rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-semibold text-ink hover:bg-gray-50 cursor-pointer">Cancel</button>
                    <form method="POST" action="{{ route('tutor.resources.destroy', $sheet) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-lg bg-danger px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90 cursor-pointer">Yes, delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
