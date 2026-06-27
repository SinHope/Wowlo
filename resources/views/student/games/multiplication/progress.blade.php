<x-app-layout>
    <x-slot name="header">My Progress — Multiplication Rabbit</x-slot>

    <div class="mx-auto max-w-2xl space-y-5">

        @include('student.games.multiplication.partials.tabs', ['active' => 'progress'])

        <p class="text-sm text-gray-500">Every round you've played.</p>

        @forelse ($attempts as $attempt)
            @php
                $pct = $attempt->score_percent;
                $col = $pct >= 70 ? 'text-success' : ($pct >= 40 ? 'text-accent-dark' : 'text-danger');
            @endphp
            <a href="{{ route('student.games.multiplication.show', $attempt) }}"
               class="flex items-center justify-between rounded-2xl border border-gray-100 bg-white p-4 shadow-sm transition-colors duration-200 hover:border-teal-500/40 cursor-pointer">
                <div>
                    <p class="font-extrabold text-ink">{{ $attempt->level }}</p>
                    <p class="mt-0.5 text-xs text-gray-400">{{ $attempt->created_at->format('j M Y, g:i a') }}</p>
                    <div class="mt-1 flex items-center gap-2 text-xs">
                        @unless ($attempt->hasReflection())
                            <span class="rounded-full bg-teal-500/15 px-2 py-0.5 font-bold text-teal-700">Reflection needed</span>
                        @endunless
                        @if ($attempt->hasFeedback())
                            <span class="rounded-full bg-primary/10 px-2 py-0.5 font-bold text-primary-dark">Tutor feedback</span>
                        @endif
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-2xl font-extrabold {{ $col }}">{{ $pct }}%</p>
                    <p class="text-xs text-gray-400">{{ $attempt->correct_count }}/{{ $attempt->total_questions }}</p>
                </div>
            </a>
        @empty
            <div class="rounded-2xl border border-gray-100 bg-white p-10 text-center shadow-sm">
                <p class="text-gray-500">No rounds yet.</p>
                <a href="{{ route('student.games.multiplication.play') }}"
                   class="mt-4 inline-block rounded-xl bg-teal-500 px-6 py-3 text-sm font-extrabold text-white hover:bg-teal-600 cursor-pointer">
                    Play your first round
                </a>
            </div>
        @endforelse

        {{ $attempts->links() }}
    </div>
</x-app-layout>
