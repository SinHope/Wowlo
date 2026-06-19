<x-app-layout>
    <x-slot name="header">{{ $typeLabel }}</x-slot>

    <div class="mx-auto max-w-4xl space-y-5">
        @if (session('status'))
            <div x-data="{ show: true }" x-show="show" x-transition
                 class="flex items-center justify-between rounded-xl border border-success/30 bg-success/10 px-4 py-3">
                <p class="text-sm font-semibold text-success">{{ session('status') }}</p>
                <button @click="show = false" class="text-success cursor-pointer" aria-label="Dismiss">&times;</button>
            </div>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-2xl font-extrabold text-ink">{{ $typeLabel }}</h2>
                <p class="text-muted">
                    @if ($type === 'mcq')
                        Send a student a blank OAS to fill in, then mark it.
                    @else
                        Send a student a short-answer sheet to fill in, then mark it.
                    @endif
                </p>
            </div>
            <a href="{{ route('tutor.resources.create', $type) }}"
               class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-dark cursor-pointer">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                New sheet
            </a>
        </div>

        @forelse ($sheets as $sheet)
            <a href="{{ route('tutor.resources.show', $sheet) }}"
               class="flex items-center gap-4 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm transition-colors hover:border-primary/30">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10">
                    <svg class="h-5 w-5 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z" />
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate font-bold text-ink">{{ $sheet->title }}</p>
                    <div class="mt-0.5 flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-semibold text-primary">{{ $sheet->subject }}</span>
                        <span class="text-xs text-muted">To {{ $sheet->student->name }}</span>
                        <span class="text-xs text-muted">· {{ $sheet->questions_count }} {{ Str::plural('question', $sheet->questions_count) }}</span>
                    </div>
                </div>
                <div class="flex shrink-0 flex-col items-end gap-1">
                    @if ($sheet->isMarked())
                        <span class="rounded-full bg-success/10 px-3 py-1 text-sm font-bold text-success">
                            {{ rtrim(rtrim(number_format($sheet->obtained_marks, 1), '0'), '.') }} / {{ $sheet->total_marks }}
                        </span>
                    @elseif ($sheet->isSubmitted())
                        <span class="rounded-full bg-amber/10 px-3 py-1 text-xs font-semibold text-amber-600">To mark</span>
                    @else
                        <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-muted">Awaiting student</span>
                    @endif
                </div>
            </a>
        @empty
            <div class="rounded-2xl border border-gray-100 bg-white py-16 text-center shadow-sm">
                <p class="text-lg font-bold text-ink">No sheets yet</p>
                <p class="mt-1 text-sm text-muted">Create a sheet and send it to a student to fill in.</p>
            </div>
        @endforelse

        @if ($sheets->hasPages())
            <div>{{ $sheets->links() }}</div>
        @endif
    </div>
</x-app-layout>
