<x-app-layout>
    <x-slot name="header">Attendance Tracking</x-slot>

    {{-- First view: centred student picker. Picking a student opens their calendar. --}}
    <div class="flex min-h-[60vh] items-center justify-center"
         x-data="{ busy: false, stage: '', studentUrl: '',
                   go() { if (! this.studentUrl) return; this.stage = 'Loading attendance…'; this.busy = true; window.location.href = this.studentUrl; } }"
         @pageshow.window="busy = false">

        <div class="w-full max-w-sm rounded-2xl border border-gray-200 bg-white p-6 text-center shadow-sm">
            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 text-primary-dark">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
            </span>
            <h2 class="mt-3 text-2xl font-extrabold text-ink">Attendance Tracking</h2>
            <p class="mt-1 text-sm text-muted">Pick a student to mark the lessons they attended.</p>

            @forelse ($students as $student)
                @if ($loop->first)
                    <div class="mt-5 text-left">
                        <label for="attendance-student" class="block text-sm font-semibold text-ink">Student</label>
                        <select id="attendance-student" x-model="studentUrl" @change="go()"
                                class="mt-1 block w-full cursor-pointer rounded-lg border-gray-300 text-sm focus:border-primary focus:ring-primary">
                            <option value="">Select a student…</option>
                @endif
                            <option value="{{ route('tutor.attendance.show', $student->id) }}">{{ $student->name }}</option>
                @if ($loop->last)
                        </select>
                    </div>
                @endif
            @empty
                <div class="mt-5 rounded-xl bg-primary/5 px-4 py-5">
                    <p class="font-bold text-ink">No students yet</p>
                    <p class="mt-1 text-sm text-muted">Add a student first, then you can track their attendance.</p>
                    <a href="{{ route('tutor.students.index') }}"
                       class="mt-3 inline-block cursor-pointer rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white transition-colors duration-200 hover:bg-primary-dark">Go to Students</a>
                </div>
            @endforelse
        </div>

        <x-spinner-overlay />
    </div>
</x-app-layout>
