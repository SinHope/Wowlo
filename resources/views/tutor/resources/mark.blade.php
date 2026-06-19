<x-app-layout>
    <x-slot name="header">Mark sheet</x-slot>

    @php
        $marksInit = [];
        $qmarksInit = [];
        foreach ($sheet->questions as $q) {
            $marksInit[(string) $q->id]  = (float) old("marks.{$q->id}", $q->marks_awarded ?? 0);
            $qmarksInit[(string) $q->id] = (int) old("qmarks.{$q->id}", $q->marks);
        }
    @endphp

    <div class="mx-auto max-w-3xl space-y-5" x-data="markForm(@js($marksInit), @js($qmarksInit))">
        @if ($errors->any())
            <div class="rounded-xl border border-danger/30 bg-danger/10 px-4 py-3">
                <p class="text-sm font-semibold text-danger">Please fix the errors below before saving.</p>
            </div>
        @endif

        {{-- Header + live running total --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-6 text-center shadow-sm">
            <a href="{{ route('tutor.resources.show', $sheet) }}" class="float-left text-sm font-semibold text-primary hover:underline">&larr; Back</a>
            <p class="text-sm font-semibold text-muted">{{ $sheet->title }}</p>
            <p class="mt-1 text-lg font-extrabold text-ink">{{ $sheet->student->name }}</p>
            <p class="mt-2 text-3xl font-extrabold text-ink">
                <span x-text="awarded"></span> <span class="text-xl text-muted">/ <span x-text="total"></span></span>
            </p>
            <p class="mt-1 text-sm font-bold text-muted"><span x-text="pct"></span>% · running total</p>
        </div>

        <form method="POST" action="{{ route('tutor.resources.mark.save', $sheet) }}" class="space-y-4"
              @keydown.enter="if ($event.target.tagName === 'INPUT') $event.preventDefault()">
            @csrf

            @foreach ($sheet->questions as $i => $q)
                <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <span class="text-sm font-bold text-ink">Question {{ $i + 1 }}</span>
                        <span class="shrink-0 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-muted">
                            <span x-text="qmarks['{{ $q->id }}']"></span> marks
                        </span>
                    </div>

                    {{-- The student's answer --}}
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
                            <p class="text-xs font-bold uppercase tracking-wide text-muted">Their answer</p>
                            @if (filled($q->answer_text))
                                <p class="mt-1 whitespace-pre-line text-sm text-ink">{{ $q->answer_text }}</p>
                            @else
                                <p class="mt-1 text-sm font-semibold text-danger">Left blank.</p>
                            @endif
                        </div>
                    @endif

                    {{-- Grade radios --}}
                    <div class="mt-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-muted">Your mark</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach (['correct' => 'Correct', 'partial' => 'Partial', 'wrong' => 'Wrong'] as $value => $label)
                                @php($checked = old("grades.{$q->id}", $q->grade) === $value)
                                <label @class([
                                    'flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-1.5 text-xs font-semibold',
                                    'border-success bg-success/10 text-success' => $checked && $value === 'correct',
                                    'border-amber bg-amber/10 text-amber-600' => $checked && $value === 'partial',
                                    'border-danger bg-danger/10 text-danger' => $checked && $value === 'wrong',
                                    'border-gray-200 text-muted hover:bg-gray-50' => ! $checked,
                                ])>
                                    <input type="radio" name="grades[{{ $q->id }}]" value="{{ $value }}"
                                           @checked($checked)
                                           @change="if ('{{ $value }}' === 'correct') marks['{{ $q->id }}'] = qmarks['{{ $q->id }}']; if ('{{ $value }}' === 'wrong') marks['{{ $q->id }}'] = 0;"
                                           class="text-primary focus:ring-primary">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                        @error("grades.{$q->id}") <p class="mt-1 text-xs font-semibold text-danger">{{ $message }}</p> @enderror
                    </div>

                    {{-- Marks awarded, out of a total the tutor decides --}}
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <label class="text-sm font-semibold text-ink">Marks awarded</label>
                        <input type="number" name="marks[{{ $q->id }}]" min="0" :max="qmarks['{{ $q->id }}']" step="0.5"
                               x-model.number="marks['{{ $q->id }}']"
                               class="w-20 rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                        <span class="text-sm text-muted">out of</span>
                        <input type="number" name="qmarks[{{ $q->id }}]" min="1" max="100" step="1"
                               x-model.number="qmarks['{{ $q->id }}']"
                               class="w-20 rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                        <span class="text-sm text-muted">marks</span>
                        @error("marks.{$q->id}") <p class="w-full text-xs font-semibold text-danger">{{ $message }}</p> @enderror
                        @error("qmarks.{$q->id}") <p class="w-full text-xs font-semibold text-danger">{{ $message }}</p> @enderror
                    </div>

                    {{-- Per-question feedback --}}
                    <div class="mt-4 rounded-xl bg-primary/5 p-3">
                        <label class="block text-xs font-bold uppercase tracking-wide text-primary">Feedback (optional)</label>
                        <textarea name="qfeedback[{{ $q->id }}]" rows="2"
                                  placeholder="Explain the correct answer…"
                                  class="mt-1 block w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">{{ old("qfeedback.{$q->id}", $q->tutor_feedback) }}</textarea>
                        @error("qfeedback.{$q->id}") <p class="mt-1 text-xs font-semibold text-danger">{{ $message }}</p> @enderror
                    </div>
                </div>
            @endforeach

            {{-- Overall remarks --}}
            <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <label class="block text-sm font-bold text-ink">Overall remarks <span class="font-normal text-muted">(optional)</span></label>
                <p class="mt-0.5 text-xs text-muted">Sent to {{ $sheet->student->name }}'s inbox when you save.</p>
                <textarea name="feedback" rows="3"
                          placeholder="e.g. Good work overall — review Q4 and Q7."
                          class="mt-2 block w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">{{ old('feedback', $sheet->feedback) }}</textarea>
                @error('feedback') <p class="mt-1 text-xs font-semibold text-danger">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('tutor.resources.show', $sheet) }}"
                   class="rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-semibold text-ink hover:bg-gray-50 cursor-pointer">Cancel</a>
                <button type="submit"
                        class="rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary-dark cursor-pointer">
                    Save marks &amp; notify student
                </button>
            </div>
        </form>
    </div>

    <script>
        function markForm(initialMarks, initialQmarks) {
            return {
                marks: initialMarks,    // { questionId: marks awarded }
                qmarks: initialQmarks,  // { questionId: marks the question is worth }
                get awarded() {
                    const sum = Object.values(this.marks).reduce((s, v) => s + (parseFloat(v) || 0), 0);
                    return Math.round(sum * 100) / 100;
                },
                get total() {
                    return Object.values(this.qmarks).reduce((s, v) => s + (parseInt(v) || 0), 0);
                },
                get pct() {
                    return this.total > 0 ? Math.round(this.awarded / this.total * 100) : 0;
                },
            };
        }
    </script>
</x-app-layout>
