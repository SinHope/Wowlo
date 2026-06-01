<x-app-layout>
    <x-slot name="header">Homework</x-slot>

    <div class="mx-auto max-w-2xl space-y-5">
        <a href="{{ route('student.homework.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-primary-dark">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            Back to my homework
        </a>

        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="text-2xl font-extrabold text-ink">{{ $homework->title }}</h2>
                <x-homework-status-badge :status="$homework->status" />
            </div>

            <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="font-semibold text-muted">Subject</p>
                    <p class="text-ink">{{ $homework->subject }}</p>
                </div>
                <div>
                    <p class="font-semibold text-muted">Due date</p>
                    <p class="text-ink">{{ $homework->due_date->format('d M Y') }}</p>
                </div>
                @if ($homework->start_date)
                    <div>
                        <p class="font-semibold text-muted">Start date</p>
                        <p class="text-ink">{{ $homework->start_date->format('d M Y') }}</p>
                    </div>
                @endif
                <div>
                    <p class="font-semibold text-muted">Given</p>
                    <p class="text-ink">{{ $homework->created_at->format('d M Y') }}</p>
                </div>
            </div>

            <div class="mt-6">
                <p class="font-semibold text-muted">Instructions</p>
                <p class="mt-1 whitespace-pre-line text-ink">{{ $homework->description }}</p>
            </div>

            @if ($homework->hasAttachment())
                <div class="mt-6">
                    <a href="{{ route('student.homework.download', $homework) }}"
                       class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-ink transition-colors duration-200 hover:bg-gray-50 cursor-pointer">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                        Download attachment
                    </a>
                </div>
            @endif
        </div>

        <!-- Mark done / not done -->
        <form method="POST" action="{{ route('student.homework.toggle', $homework) }}"
              class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            @csrf
            @method('PATCH')
            @if ($homework->isDone())
                <p class="mb-3 text-sm font-semibold text-success">✓ You marked this done{{ $homework->completed_at ? ' on '.$homework->completed_at->format('d M Y') : '' }}.</p>
                <button type="submit" class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-ink transition-colors duration-200 hover:bg-gray-50 cursor-pointer">
                    Mark as Not Done
                </button>
            @else
                <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-lg bg-success px-4 py-3 text-sm font-bold text-white transition-colors duration-200 hover:opacity-90 cursor-pointer">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                    Mark as Done
                </button>
            @endif
        </form>
    </div>
</x-app-layout>
