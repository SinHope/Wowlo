<x-app-layout>
    <x-slot name="header">Finance</x-slot>

    <div class="mx-auto max-w-5xl space-y-5">

        @if (session('status'))
            <div x-data="{ show: true }" x-show="show" x-transition
                 class="flex items-center justify-between rounded-xl border border-success/30 bg-success/10 px-4 py-3">
                <p class="text-sm font-semibold text-success">{{ session('status') }}</p>
                <button @click="show = false" class="text-success cursor-pointer" aria-label="Dismiss">&times;</button>
            </div>
        @endif

        <div>
            <h2 class="text-2xl font-extrabold text-ink">Finance</h2>
            <p class="text-muted">Hourly rate and outstanding balance per student.</p>
        </div>

        @forelse ($students as $student)
            <a href="{{ route('tutor.finance.show', $student) }}"
               class="flex items-center gap-4 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm transition-colors duration-200 hover:border-primary/40 hover:bg-primary/5 cursor-pointer">
                <div class="min-w-0 flex-1">
                    <p class="truncate font-bold text-ink">{{ $student->name }}</p>
                    <p class="mt-0.5 text-sm text-muted">
                        @if ($student->tuitionFee)
                            {{ $student->tuitionFee->currency }} {{ number_format($student->tuitionFee->fee_rate_per_hour, 2) }}/hr
                        @else
                            <span class="text-amber-600">No rate set yet</span>
                        @endif
                    </p>
                </div>
                <div class="shrink-0 text-right">
                    <x-outstanding-badge :amount="$student->outstanding" :currency="config('wowlo.currency')" />
                </div>
                <svg class="h-5 w-5 shrink-0 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            </a>
        @empty
            <div class="rounded-2xl border border-gray-100 bg-white py-16 text-center shadow-sm">
                <p class="text-lg font-bold text-ink">No students yet</p>
                <p class="mt-1 text-sm text-muted">Add students before setting up fees.</p>
            </div>
        @endforelse
    </div>
</x-app-layout>
