<x-app-layout>
    <x-slot name="header">Spelling Meow — Student Rounds</x-slot>

    <div class="mx-auto max-w-3xl space-y-4">
        <p class="text-sm text-gray-500">Spelling rounds your students have completed. Open one to leave feedback.</p>

        @forelse ($attempts as $attempt)
            @php
                $pct = $attempt->score_percent;
                $col = $pct >= 70 ? 'text-success' : ($pct >= 40 ? 'text-accent-dark' : 'text-danger');
            @endphp
            <a href="{{ route('tutor.games.spelling.show', $attempt) }}"
               class="flex items-center justify-between rounded-2xl border border-gray-100 bg-white p-4 shadow-sm transition-colors duration-200 hover:border-primary/40 cursor-pointer">
                <div>
                    <p class="font-extrabold text-ink">{{ $attempt->student?->name ?? 'Student' }}</p>
                    <p class="mt-0.5 text-xs text-gray-500">{{ $attempt->level }} · {{ $attempt->created_at->format('j M Y, g:i a') }}</p>
                    <div class="mt-1 flex items-center gap-2 text-xs">
                        @if ($attempt->hasFeedback())
                            <span class="rounded-full bg-success/10 px-2 py-0.5 font-bold text-success">Feedback given</span>
                        @else
                            <span class="rounded-full bg-accent/15 px-2 py-0.5 font-bold text-accent-dark">Awaiting feedback</span>
                        @endif
                        @unless ($attempt->hasReflection())
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 font-bold text-gray-500">No reflection yet</span>
                        @endunless
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-2xl font-extrabold {{ $col }}">{{ $pct }}%</p>
                    <p class="text-xs text-gray-400">{{ $attempt->correct_count }}/{{ $attempt->total_questions }}</p>
                </div>
            </a>
        @empty
            <div class="rounded-2xl border border-gray-100 bg-white p-10 text-center shadow-sm">
                <p class="text-gray-500">No spelling rounds from your students yet.</p>
            </div>
        @endforelse

        {{ $attempts->links() }}
    </div>
</x-app-layout>
