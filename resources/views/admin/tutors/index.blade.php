<x-app-layout>
    <x-slot name="header">Tutors</x-slot>

    <div class="mx-auto max-w-4xl space-y-5">

        @if (session('status'))
            <div x-data="{ show: true }" x-show="show" x-transition
                 class="flex items-center justify-between rounded-xl border border-success/30 bg-success/10 px-4 py-3">
                <p class="text-sm font-semibold text-success">{{ session('status') }}</p>
                <button @click="show = false" class="text-success cursor-pointer" aria-label="Dismiss">&times;</button>
            </div>
        @endif

        @error('tutor')
            <div class="rounded-xl border border-danger/30 bg-danger/10 px-4 py-3">
                <p class="text-sm font-semibold text-danger">{{ $message }}</p>
            </div>
        @enderror

        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-extrabold text-ink">Tutors</h2>
                <p class="text-muted">{{ $tutors->total() }} {{ Str::plural('tutor', $tutors->total()) }} · you provision every account (no public sign-up yet)</p>
            </div>
            <a href="{{ route('admin.tutors.create') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-primary-dark cursor-pointer">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Add Tutor
            </a>
        </div>

        @forelse ($tutors as $tutor)
            <a href="{{ route('admin.tutors.edit', $tutor) }}"
               class="flex items-center gap-4 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm transition-colors duration-200 hover:border-primary/40 hover:bg-primary/5 cursor-pointer">
                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-primary/10 text-lg font-bold text-primary-dark">
                    {{ strtoupper(substr($tutor->name, 0, 1)) }}
                </span>
                <div class="min-w-0 flex-1">
                    <p class="truncate font-bold text-ink">{{ $tutor->name }}</p>
                    <p class="truncate text-sm text-muted">{{ $tutor->email }}</p>
                </div>
                <span class="hidden rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-muted sm:block">
                    {{ $tutor->students_count }} {{ Str::plural('student', $tutor->students_count) }}
                </span>
                <svg class="h-5 w-5 shrink-0 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            </a>
        @empty
            <div class="rounded-2xl border border-gray-100 bg-white py-16 text-center shadow-sm">
                <p class="text-lg font-bold text-ink">No other tutors yet</p>
                <p class="mt-1 text-sm text-muted">Create an account for a tutor you want to invite.</p>
                <a href="{{ route('admin.tutors.create') }}"
                   class="mt-4 inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-primary-dark cursor-pointer">
                    Add Tutor
                </a>
            </div>
        @endforelse

        @if ($tutors->hasPages())
            <div>{{ $tutors->links() }}</div>
        @endif
    </div>
</x-app-layout>
