<x-app-layout>
    <x-slot name="header">{{ $attempt->student?->name ?? 'Student' }} — Spelling</x-slot>

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
            <a href="{{ route('tutor.games.spelling.index') }}" class="float-left text-sm font-semibold text-primary hover:underline">&larr; All rounds</a>
            <p class="text-sm font-semibold uppercase tracking-wide text-gray-500">{{ $attempt->student?->name }} · {{ $attempt->level }}</p>
            <p class="mt-4 text-6xl font-extrabold {{ $ringCol }}">{{ $pct }}%</p>
            <p class="mt-2 text-base font-bold text-ink">{{ $attempt->correct_count }} of {{ $attempt->total_questions }} correct</p>
            <p class="mt-1 text-xs text-gray-400">{{ $attempt->created_at->format('j M Y, g:i a') }}</p>
        </div>

        {{-- ======================= PER-WORD REVIEW ======================= --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <h3 class="mb-4 text-base font-extrabold text-ink">Words</h3>
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
                                Wrote:
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

        {{-- ==================== STUDENT'S REFLECTION ===================== --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <h3 class="text-base font-extrabold text-ink">Student's Reflection</h3>
            @if ($attempt->hasReflection())
                <p class="mt-2 whitespace-pre-line text-sm text-ink">{{ $attempt->reflection }}</p>
            @else
                <p class="mt-2 text-sm text-gray-400">The student hasn't written their reflection yet.</p>
            @endif
        </div>

        {{-- ======================= TUTOR FEEDBACK ======================= --}}
        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            <h3 class="text-base font-extrabold text-ink">Your Feedback</h3>
            <p class="mt-1 text-xs text-gray-500">The student sees this on their results page and is notified.</p>
            <form method="POST" action="{{ route('tutor.games.spelling.feedback', $attempt) }}" class="mt-3">
                @csrf
                <textarea name="feedback" rows="4" required
                          class="w-full rounded-xl border-gray-300 text-sm focus:border-primary focus:ring-primary"
                          placeholder="Well done on…  Next time, watch out for…">{{ old('feedback', $attempt->feedback) }}</textarea>
                @error('feedback')
                    <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                @enderror
                <button type="submit" class="mt-3 rounded-xl bg-primary px-5 py-2.5 text-sm font-extrabold text-white transition-colors duration-200 hover:bg-primary-dark cursor-pointer">
                    {{ $attempt->hasFeedback() ? 'Update feedback' : 'Send feedback' }}
                </button>
            </form>
        </div>
    </div>
</x-app-layout>
