<x-app-layout>
    <x-slot name="header">Homework Status</x-slot>

    <div class="mx-auto max-w-4xl space-y-5">
        <div>
            <h2 class="text-2xl font-extrabold text-ink">Homework Status</h2>
            <p class="text-muted">Pick a student to see their homework and completion.</p>
        </div>

        <form method="GET" action="{{ route('tutor.homework.status') }}" class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
            <x-input-label for="student_id" value="Student" />
            <div class="mt-1 flex gap-2">
                <select id="student_id" name="student_id" onchange="this.form.submit()"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                    <option value="">Select a student…</option>
                    @foreach ($students as $student)
                        <option value="{{ $student->id }}" @selected($selectedId == $student->id)>{{ $student->name }}</option>
                    @endforeach
                </select>
            </div>
        </form>

        @if ($selectedId)
            @forelse ($homework as $hw)
                <a href="{{ route('tutor.homework.edit', $hw) }}"
                   class="flex items-center gap-4 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm transition-colors duration-200 hover:border-primary/40 hover:bg-primary/5 cursor-pointer">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="truncate font-bold text-ink">{{ $hw->title }}</p>
                            <x-homework-status-badge :status="$hw->status" />
                        </div>
                        <p class="mt-0.5 text-sm text-muted">{{ $hw->subject }} · due {{ $hw->due_date->format('d M Y') }}</p>
                    </div>
                </a>
            @empty
                <div class="rounded-2xl border border-gray-100 bg-white py-12 text-center shadow-sm">
                    <p class="font-semibold text-ink">No homework for this student yet.</p>
                </div>
            @endforelse
        @endif
    </div>
</x-app-layout>
