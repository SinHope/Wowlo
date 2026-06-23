<x-app-layout>
    <x-slot name="header">Spelling Results</x-slot>

    @php
        $pct     = $attempt->score_percent;
        $ringCol = $pct >= 70 ? 'text-success' : ($pct >= 40 ? 'text-accent-dark' : 'text-danger');
    @endphp

    <div class="mx-auto max-w-2xl space-y-5">

        @if (session('status'))
            <div class="rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm font-semibold text-success">
                {{ session('status') }}
            </div>
        @endif

        {{-- ============================ SCORE ============================ --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-6 text-center shadow-sm">
            <a href="{{ route('student.games.spelling.progress') }}" class="float-left text-sm font-semibold text-primary hover:underline">&larr; My Progress</a>
            <p class="text-sm font-semibold uppercase tracking-wide text-gray-500">{{ $attempt->level }}</p>
            <p class="mt-4 text-6xl font-extrabold {{ $ringCol }}">{{ $pct }}%</p>
            <p class="mt-2 text-base font-bold text-ink">
                {{ $attempt->correct_count }} of {{ $attempt->total_questions }} spelt correctly
            </p>
            <p class="mt-1 text-xs text-gray-400">{{ $attempt->created_at->format('j M Y, g:i a') }}</p>
        </div>

        {{-- ======================= PER-WORD REVIEW ======================= --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <h3 class="mb-4 text-base font-extrabold text-ink">Your words</h3>
            <ul class="divide-y divide-gray-100">
                @foreach ($attempt->results as $r)
                    <li class="flex items-start gap-3 py-3">
                        @if ($r['is_correct'])
                            <svg class="mt-0.5 h-5 w-5 shrink-0 text-success" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                        @else
                            <svg class="mt-0.5 h-5 w-5 shrink-0 text-danger" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="text-xs text-gray-400">Shown: <span class="line-through">{{ $r['shown'] }}</span></p>
                            <p class="text-sm font-bold text-ink">
                                You wrote:
                                <span class="{{ $r['is_correct'] ? 'text-success' : 'text-danger' }}">{{ $r['response'] !== '' ? $r['response'] : '—' }}</span>
                            </p>
                            @unless ($r['is_correct'])
                                <p class="text-sm font-semibold text-gray-600">Correct: <span class="text-success">{{ $r['answer'] }}</span></p>
                            @endunless
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- ===================== STUDENT REFLECTION ====================== --}}
        @if ($attempt->hasReflection())
            <div x-data="{ editing: false }" class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <h3 class="text-base font-extrabold text-ink">My Reflection / Learning Points</h3>
                <p class="mt-1 text-xs text-gray-500">What did you find tricky? What will you remember next time?</p>

                <div x-show="! editing" class="mt-3">
                    <p class="whitespace-pre-line text-sm text-ink">{{ $attempt->reflection }}</p>
                    <button type="button" @click="editing = true" class="mt-3 text-sm font-semibold text-primary hover:underline cursor-pointer">Edit</button>
                </div>

                <form x-show="editing" x-cloak method="POST" action="{{ route('student.games.spelling.reflection', $attempt) }}" class="mt-3"
                      x-data="{ text: @js($attempt->reflection) }">
                    @csrf
                    @method('PATCH')
                    <textarea name="reflection" rows="4" required x-model="text"
                              class="w-full rounded-xl border-gray-300 text-sm focus:border-primary focus:ring-primary"
                              placeholder="I learnt that…">{{ $attempt->reflection }}</textarea>
                    <button type="submit" :disabled="text.trim() === ''"
                            class="mt-3 rounded-xl bg-primary px-5 py-2.5 text-sm font-extrabold text-white transition-colors duration-200 hover:bg-primary-dark cursor-pointer disabled:cursor-not-allowed disabled:opacity-50">
                        Save reflection
                    </button>
                </form>
            </div>
        @else
            <div class="rounded-2xl border-2 border-accent/40 bg-accent/5 p-6">
                <h3 class="text-base font-extrabold text-ink">My Reflection / Learning Points</h3>
                <p class="mt-1 text-sm text-accent-dark">Write your reflection in the pop-up to finish and unlock the app.</p>
            </div>
        @endif

        {{-- ======================= TUTOR FEEDBACK ======================= --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <h3 class="text-base font-extrabold text-ink">Tutor Feedback</h3>
            @if ($attempt->hasFeedback())
                <p class="mt-2 whitespace-pre-line text-sm text-ink">{{ $attempt->feedback }}</p>
                <p class="mt-3 text-xs text-gray-400">
                    — {{ $attempt->feedbackBy?->name ?? 'Your tutor' }}{{ $attempt->feedback_at ? ', ' . $attempt->feedback_at->format('j M Y') : '' }}
                </p>
            @else
                <p class="mt-2 text-sm text-gray-400">No feedback yet — your tutor will add notes here.</p>
            @endif
        </div>

        <div class="text-center">
            <a href="{{ route('student.games.spelling.play') }}"
               class="inline-block rounded-2xl bg-accent px-8 py-3 text-base font-extrabold text-white shadow-sm transition-colors duration-200 hover:brightness-95 cursor-pointer">
                Play again
            </a>
        </div>
    </div>

    {{-- ============== REFLECTION GATE (blocks the whole app) ============== --}}
    {{-- Until the student writes SOMETHING, this fixed overlay sits above the
         sidebar/top-bar (z-[100]) and swallows every click — they can only type
         their reflection and save. Backdrop has no dismiss. --}}
    @unless ($attempt->hasReflection())
        <div x-data="{ text: @js(old('reflection', '')) }"
             x-init="$nextTick(() => $refs.reflectBox?.focus())"
             class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/60"></div>

            <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                <h3 class="text-lg font-extrabold text-ink">One last step — your reflection</h3>
                <p class="mt-1 text-sm text-gray-600">
                    Write what you learnt this round. You can't go anywhere else until you do
                    — but there's no minimum, even a few words is fine.
                </p>

                <form method="POST" action="{{ route('student.games.spelling.reflection', $attempt) }}" class="mt-4">
                    @csrf
                    @method('PATCH')
                    <textarea x-ref="reflectBox" name="reflection" rows="4" required x-model="text"
                              class="w-full rounded-xl border-gray-300 text-sm focus:border-primary focus:ring-primary"
                              placeholder="I learnt that…">{{ old('reflection') }}</textarea>
                    @error('reflection')
                        <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                    @enderror
                    <button type="submit" :disabled="text.trim() === ''"
                            class="mt-3 w-full rounded-xl bg-primary px-5 py-3 text-base font-extrabold text-white transition-colors duration-200 hover:bg-primary-dark cursor-pointer disabled:cursor-not-allowed disabled:opacity-50">
                        Save &amp; continue
                    </button>
                </form>
            </div>
        </div>
    @endunless
</x-app-layout>
