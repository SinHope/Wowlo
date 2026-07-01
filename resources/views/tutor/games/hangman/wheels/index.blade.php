<x-app-layout>
    <x-slot name="header">Hangman Wheel Panda</x-slot>

    <div class="mx-auto max-w-3xl">
        {{-- intro + actions --}}
        <div class="mb-6 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xl font-extrabold text-ink">Your spinning wheels</h2>
                    <p class="mt-1 text-sm text-muted">
                        Build wheels your students spin before each guess. The slice they land on is a
                        fun surprise — like <span class="font-semibold">“+2 Free Guesses”</span>.
                    </p>
                </div>
                <div class="flex shrink-0 gap-2">
                    <a href="{{ route('games.hangman.play') }}"
                       class="inline-flex items-center gap-1.5 rounded-xl border-2 border-emerald-500/30 px-4 py-2 text-sm font-extrabold text-emerald-700 transition-colors hover:bg-emerald-500/5 cursor-pointer">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 0 1 0 1.971l-11.54 6.347a1.125 1.125 0 0 1-1.667-.985V5.653Z"/></svg>
                        Play the game
                    </a>
                    <a href="{{ route('tutor.games.hangman.wheels.create') }}"
                       class="inline-flex items-center gap-1.5 rounded-xl bg-primary px-4 py-2 text-sm font-extrabold text-white shadow-sm transition-colors hover:bg-primary-dark cursor-pointer">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        New wheel
                    </a>
                </div>
            </div>
        </div>

        @if (session('status'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @forelse ($wheels as $wheel)
            <div class="mb-3 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="truncate text-base font-extrabold text-ink">{{ $wheel->name }}</h3>
                            @if ($wheel->isStandard())
                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-extrabold uppercase tracking-wide text-amber-700">Standard · everyone</span>
                            @else
                                <span class="rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-extrabold uppercase tracking-wide text-primary-dark">My wheel</span>
                            @endif
                            <span class="text-xs font-semibold text-muted">{{ count($wheel->slices) }} slices</span>
                        </div>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @foreach ($wheel->slices as $slice)
                                <span class="rounded-lg bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-700">{{ $slice }}</span>
                            @endforeach
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <a href="{{ route('tutor.games.hangman.wheels.edit', $wheel) }}"
                           class="rounded-lg p-2 text-muted transition-colors hover:bg-gray-100 hover:text-primary-dark cursor-pointer" title="Edit">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/></svg>
                        </a>
                        <form method="POST" action="{{ route('tutor.games.hangman.wheels.destroy', $wheel) }}"
                              onsubmit="return confirm('Delete this wheel? Students will no longer be able to spin it.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-lg p-2 text-muted transition-colors hover:bg-danger/10 hover:text-danger cursor-pointer" title="Delete">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-gray-200 bg-white p-10 text-center">
                <p class="text-sm font-semibold text-muted">You haven’t made any wheels yet.</p>
                <p class="mt-1 text-sm text-muted">Your students can still play with the standard wheel — make your own for a personal touch.</p>
            </div>
        @endforelse
    </div>
</x-app-layout>
