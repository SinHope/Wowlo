<x-app-layout>
    <x-slot name="header">New Wheel</x-slot>

    <div class="mx-auto max-w-2xl">
        <a href="{{ route('tutor.games.hangman.wheels.index') }}" class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-primary-dark">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            Back to wheels
        </a>

        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm sm:p-8">
            <h2 class="mb-1 text-xl font-extrabold text-ink">Build a wheel</h2>
            <p class="mb-6 text-sm text-muted">Give it a name and add the slices students can land on when they spin.</p>

            <form method="POST" action="{{ route('tutor.games.hangman.wheels.store') }}">
                @csrf
                @include('tutor.games.hangman.wheels._form', ['wheel' => null])

                <div class="mt-8 flex items-center justify-end gap-3">
                    <a href="{{ route('tutor.games.hangman.wheels.index') }}" class="text-sm font-semibold text-muted hover:text-ink">Cancel</a>
                    <x-primary-button>Create Wheel</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
