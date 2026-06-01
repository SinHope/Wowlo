<x-app-layout>
    <x-slot name="header">Homework</x-slot>

    <div class="mx-auto max-w-5xl space-y-5">

        @if (session('status'))
            <div x-data="{ show: true }" x-show="show" x-transition
                 class="flex items-center justify-between rounded-xl border border-success/30 bg-success/10 px-4 py-3">
                <p class="text-sm font-semibold text-success">{{ session('status') }}</p>
                <button @click="show = false" class="text-success cursor-pointer" aria-label="Dismiss">&times;</button>
            </div>
        @endif

        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-extrabold text-ink">Homework</h2>
                <p class="text-muted">{{ $homework->total() }} assigned</p>
            </div>
            <a href="{{ route('tutor.homework.create') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-primary-dark cursor-pointer">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Create Homework
            </a>
        </div>

        @forelse ($homework as $hw)
            <a href="{{ route('tutor.homework.edit', $hw) }}"
               class="flex items-center gap-4 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm transition-colors duration-200 hover:border-primary/40 hover:bg-primary/5 cursor-pointer">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="truncate font-bold text-ink">{{ $hw->title }}</p>
                        <x-homework-status-badge :status="$hw->status" />
                    </div>
                    <p class="mt-0.5 truncate text-sm text-muted">
                        {{ $hw->subject }} · {{ $hw->student->name }} · due {{ $hw->due_date->format('d M Y') }}
                        @if ($hw->hasAttachment()) · 📎 attachment @endif
                    </p>
                </div>
                <svg class="h-5 w-5 shrink-0 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            </a>
        @empty
            <div class="rounded-2xl border border-gray-100 bg-white py-16 text-center shadow-sm">
                <p class="text-lg font-bold text-ink">No homework yet</p>
                <p class="mt-1 text-sm text-muted">Create your first homework assignment.</p>
            </div>
        @endforelse

        @if ($homework->hasPages())
            <div>{{ $homework->links() }}</div>
        @endif
    </div>
</x-app-layout>
