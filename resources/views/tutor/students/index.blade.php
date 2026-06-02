<x-app-layout>
    <x-slot name="header">Students</x-slot>

    <div class="mx-auto max-w-4xl space-y-5"
         x-data="spinner('Connecting to server…', 'Establishing a connection to database…')">

        @if (session('status'))
            <div x-data="{ show: true }" x-show="show" x-transition
                 class="flex items-center justify-between rounded-xl border border-success/30 bg-success/10 px-4 py-3">
                <p class="text-sm font-semibold text-success">{{ session('status') }}</p>
                <button @click="show = false" class="text-success cursor-pointer" aria-label="Dismiss">&times;</button>
            </div>
        @endif

        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-extrabold text-ink">Students</h2>
                <p class="text-muted">{{ $students->total() }} {{ Str::plural('student', $students->total()) }} total</p>
            </div>
            <a href="{{ route('tutor.students.create') }}" @click="start()"
               class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-primary-dark cursor-pointer">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Add Student
            </a>
        </div>

        @forelse ($students as $student)
            <a href="{{ route('tutor.students.edit', $student) }}"
               class="flex items-center gap-4 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm transition-colors duration-200 hover:border-primary/40 hover:bg-primary/5 cursor-pointer">
                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-primary/10 text-lg font-bold text-primary-dark">
                    {{ strtoupper(substr($student->name, 0, 1)) }}
                </span>
                <div class="min-w-0 flex-1">
                    <p class="truncate font-bold text-ink">{{ $student->name }}</p>
                    <p class="truncate text-sm text-muted">{{ $student->email }}</p>
                </div>
                @php $primaryPhone = collect([$student->phone_1, $student->phone_2, $student->phone_3, $student->phone_4, $student->phone_5])->first(fn ($p) => filled($p)); @endphp
                @if ($primaryPhone)
                    <span class="hidden text-sm text-muted sm:block">{{ $primaryPhone }}</span>
                @endif
                <svg class="h-5 w-5 shrink-0 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            </a>
        @empty
            <div class="rounded-2xl border border-gray-100 bg-white py-16 text-center shadow-sm">
                <p class="text-lg font-bold text-ink">No students yet</p>
                <p class="mt-1 text-sm text-muted">Add your first student to get started.</p>
                <a href="{{ route('tutor.students.create') }}" @click="start()"
                   class="mt-4 inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-primary-dark cursor-pointer">
                    Add Student
                </a>
            </div>
        @endforelse

        @if ($students->hasPages())
            <div>{{ $students->links() }}</div>
        @endif

        <x-spinner-overlay />
    </div>
</x-app-layout>
