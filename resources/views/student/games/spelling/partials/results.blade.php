{{-- Score card + per-word review for one spelling round. ($attempt) --}}
@php
    $pct     = $attempt->score_percent;
    $ringCol = $pct >= 70 ? 'text-success' : ($pct >= 40 ? 'text-accent-dark' : 'text-danger');
@endphp

{{-- ============================ SCORE ============================ --}}
<div class="rounded-2xl border border-gray-100 bg-white p-6 text-center shadow-sm">
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
