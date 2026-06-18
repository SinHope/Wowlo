<x-app-layout>
    <x-slot name="header">Homework Status</x-slot>

    <div class="mx-auto max-w-4xl space-y-5">
        <div>
            <h2 class="text-2xl font-extrabold text-ink">Homework Status</h2>
            <p class="text-muted">Pick a student, then mark each homework done or not done and leave feedback.</p>
        </div>

        @if (session('status'))
            <div x-data="{ show: true }" x-show="show" x-transition
                 class="flex items-center justify-between rounded-xl border border-success/30 bg-success/10 px-4 py-3">
                <p class="text-sm font-semibold text-success">{{ session('status') }}</p>
                <button @click="show = false" class="text-success cursor-pointer" aria-label="Dismiss">&times;</button>
            </div>
        @endif

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
                <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm sm:p-5">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-bold text-ink">{{ $hw->title }}</p>
                                <x-homework-status-badge :status="$hw->status" :overdue="$hw->isOverdue()" />
                            </div>
                            <p class="mt-0.5 text-sm text-muted">{{ $hw->subject }} · due {{ $hw->due_date->format('d M Y') }}</p>
                        </div>
                        <a href="{{ route('tutor.homework.edit', $hw) }}"
                           class="shrink-0 text-sm font-semibold text-primary-dark hover:underline cursor-pointer">Edit</a>
                    </div>

                    @if ($hw->isSubmitted())
                        <p class="mt-3 inline-flex items-center gap-1.5 rounded-lg bg-primary/5 px-3 py-1.5 text-sm font-semibold text-primary-dark">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                            Student says they've done this — please check.
                        </p>
                    @endif

                    <form method="POST" action="{{ route('tutor.homework.verdict', $hw) }}" class="mt-4 space-y-3">
                        @csrf
                        @method('PATCH')

                        <div>
                            <x-input-label :for="'feedback-'.$hw->id" value="Feedback (the student & parent will see this)" />
                            <textarea id="feedback-{{ $hw->id }}" name="feedback" rows="2" maxlength="2000"
                                      class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary"
                                      placeholder="Optional — e.g. “Q3–5 missing, please redo.”">{{ old('feedback', $hw->feedback) }}</textarea>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button type="submit" name="status" value="done"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-success px-4 py-2 text-sm font-bold text-white transition-colors duration-200 hover:opacity-90 cursor-pointer">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                Mark Done
                            </button>
                            <button type="submit" name="status" value="not_done"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-danger px-4 py-2 text-sm font-bold text-white transition-colors duration-200 hover:opacity-90 cursor-pointer">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                                Mark Not Done
                            </button>
                        </div>
                        <p class="text-xs text-muted">Pick a verdict to save — your feedback note saves with it.</p>
                    </form>
                </div>
            @empty
                <div class="rounded-2xl border border-gray-100 bg-white py-12 text-center shadow-sm">
                    <p class="font-semibold text-ink">No homework for this student yet.</p>
                </div>
            @endforelse
        @endif
    </div>
</x-app-layout>
