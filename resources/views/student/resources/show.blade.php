<x-app-layout>
    <x-slot name="header">{{ $sheet->title }}</x-slot>

    <div class="mx-auto max-w-3xl space-y-5">
        {{-- Header --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <a href="{{ route('student.resources.index', $sheet->type) }}" class="text-sm font-semibold text-primary hover:underline">&larr; Back to {{ $sheet->typeLabel() }}</a>
            <div class="mt-3 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-xl font-extrabold text-ink">{{ $sheet->title }}</h2>
                    <span class="mt-2 inline-block rounded-full bg-primary/10 px-2 py-0.5 text-xs font-semibold text-primary">{{ $sheet->subject }}</span>
                </div>
                @if ($sheet->isMarked())
                    <span class="rounded-full bg-success/10 px-3 py-1 text-sm font-bold text-success">
                        {{ rtrim(rtrim(number_format($sheet->obtained_marks, 1), '0'), '.') }} / {{ $sheet->total_marks }} · {{ $sheet->percentage() }}%
                    </span>
                @elseif ($sheet->isSubmitted())
                    <span class="rounded-full bg-amber/10 px-3 py-1 text-xs font-bold text-amber-600">Awaiting marking</span>
                @endif
            </div>
        </div>

        @if ($sheet->isSent())
            {{-- FILL MODE --}}
            <div class="rounded-xl border border-primary/20 bg-primary/5 px-4 py-3 text-sm text-ink">
                @if ($sheet->isMcq())
                    Select one option per question, then submit. <span class="font-semibold text-amber-600">You can't change answers after submitting.</span>
                @else
                    Write your answer for each question, then submit. <span class="font-semibold text-amber-600">You can't change answers after submitting.</span>
                @endif
            </div>

            <form method="POST" action="{{ route('student.resources.submit', $sheet) }}" class="space-y-4"
                  x-data="{ confirming: false, submitting: false }">
                @csrf

                @foreach ($sheet->questions as $i => $q)
                    <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <span class="font-bold text-ink">Question {{ $i + 1 }}</span>
                            <span class="shrink-0 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-muted">{{ $q->marks }} {{ Str::plural('mark', $q->marks) }}</span>
                        </div>

                        @if ($sheet->isMcq())
                            <div class="mt-4 flex flex-wrap gap-3">
                                @for ($n = 1; $n <= $q->num_options; $n++)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="answers[{{ $q->id }}]" value="{{ $n }}" @checked(old("answers.{$q->id}") == $n) class="peer sr-only">
                                        <span class="grid h-12 w-12 place-items-center rounded-full border border-gray-300 text-base font-bold text-muted transition-colors hover:border-primary peer-checked:border-primary peer-checked:bg-primary peer-checked:text-white">{{ $n }}</span>
                                    </label>
                                @endfor
                            </div>
                        @else
                            <textarea name="answers[{{ $q->id }}]" rows="3"
                                      placeholder="Type your answer…"
                                      class="mt-4 block w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">{{ old("answers.{$q->id}") }}</textarea>
                        @endif
                        @error("answers.{$q->id}") <p class="mt-1 text-xs font-semibold text-danger">{{ $message }}</p> @enderror
                    </div>
                @endforeach

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('student.resources.index', $sheet->type) }}"
                       class="rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-semibold text-ink hover:bg-gray-50 cursor-pointer">Back</a>
                    <button type="button" @click="confirming = true"
                            class="rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary-dark cursor-pointer">Submit</button>
                </div>

                {{-- Confirm --}}
                <div x-show="confirming" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" x-transition.opacity>
                    <div class="absolute inset-0 bg-ink/50" @click="confirming = false"></div>
                    <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                        <h3 class="text-lg font-bold text-ink">Submit your answers?</h3>
                        <p class="mt-1 text-sm text-muted">Once submitted, you can't change them. Your tutor will mark this sheet.</p>
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
                        <p class="text-sm font-semibold text-ink">Submitting…</p>
                    </div>
                </div>
            </form>
        @else
            {{-- SUBMITTED (read-only) or MARKED (with results) --}}
            <div class="space-y-3">
                @foreach ($sheet->questions as $i => $q)
                    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <span class="text-sm font-bold text-ink">Question {{ $i + 1 }}</span>
                            <span class="shrink-0 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-muted">{{ $q->marks }} {{ Str::plural('mark', $q->marks) }}</span>
                        </div>

                        @if ($sheet->isMcq())
                            <div class="mt-3 flex flex-wrap gap-2">
                                @for ($n = 1; $n <= $q->num_options; $n++)
                                    <span @class([
                                        'grid h-10 w-10 place-items-center rounded-full border text-sm font-bold',
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
                    <p class="text-xs font-bold uppercase tracking-wide text-muted">Tutor's remarks</p>
                    <p class="mt-1 whitespace-pre-line text-sm text-ink">{{ $sheet->feedback }}</p>
                </div>
            @elseif ($sheet->isSubmitted())
                <div class="rounded-2xl border border-gray-100 bg-white p-6 text-center shadow-sm">
                    <p class="font-bold text-ink">Submitted — waiting for your tutor to mark it.</p>
                </div>
            @endif
        @endif
    </div>
</x-app-layout>
