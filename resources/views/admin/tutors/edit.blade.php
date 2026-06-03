<x-app-layout>
    <x-slot name="header">Edit Tutor</x-slot>

    <div class="mx-auto max-w-2xl space-y-6">
        <a href="{{ route('admin.tutors.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-primary-dark">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            Back to tutors
        </a>

        <!-- Edit form -->
        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm sm:p-8">
            <h2 class="mb-6 text-xl font-extrabold text-ink">{{ $tutor->name }}</h2>

            <form method="POST" action="{{ route('admin.tutors.update', $tutor) }}">
                @csrf
                @method('PUT')
                @include('admin.tutors._form', ['tutor' => $tutor])

                <div class="mt-8 flex items-center justify-end gap-3">
                    <a href="{{ route('admin.tutors.index') }}" class="text-sm font-semibold text-muted hover:text-ink">Cancel</a>
                    <x-primary-button>Save Changes</x-primary-button>
                </div>
            </form>
        </div>

        <!-- Danger zone -->
        <div class="rounded-2xl border border-danger/20 bg-white p-6 shadow-sm sm:p-8">
            <h3 class="text-lg font-bold text-ink">Delete tutor</h3>
            <p class="mt-1 text-sm text-muted">
                Permanently removes {{ $tutor->name }}'s account.
                @if ($tutor->students()->exists())
                    <span class="font-semibold text-danger">They still have {{ $tutor->students()->count() }} {{ Str::plural('student', $tutor->students()->count()) }} — remove or reassign those first.</span>
                @else
                    This cannot be undone.
                @endif
            </p>
            <x-danger-button class="mt-4" x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-tutor-deletion')">
                Delete Tutor
            </x-danger-button>
        </div>
    </div>

    <!-- Delete confirmation modal -->
    <x-modal name="confirm-tutor-deletion" focusable>
        <form method="POST" action="{{ route('admin.tutors.destroy', $tutor) }}" class="p-6">
            @csrf
            @method('DELETE')
            <h2 class="text-lg font-bold text-ink">Delete {{ $tutor->name }}?</h2>
            <p class="mt-2 text-sm text-muted">Are you sure? This permanently deletes the tutor account and cannot be undone.</p>
            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                <x-danger-button>Delete Tutor</x-danger-button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
