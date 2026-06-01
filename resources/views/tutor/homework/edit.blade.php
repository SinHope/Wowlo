<x-app-layout>
    <x-slot name="header">Edit Homework</x-slot>

    <div class="mx-auto max-w-2xl space-y-6">
        <a href="{{ route('tutor.homework.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-primary-dark">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            Back to homework
        </a>

        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm sm:p-8">
            <div class="mb-6 flex items-center gap-3">
                <h2 class="text-xl font-extrabold text-ink">{{ $homework->title }}</h2>
                <x-homework-status-badge :status="$homework->status" />
            </div>

            <form method="POST" action="{{ route('tutor.homework.update', $homework) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('tutor.homework._form', ['homework' => $homework])

                <div class="mt-8 flex items-center justify-end gap-3">
                    <a href="{{ route('tutor.homework.index') }}" class="text-sm font-semibold text-muted hover:text-ink">Cancel</a>
                    <x-primary-button>Save Changes</x-primary-button>
                </div>
            </form>
        </div>

        <!-- Danger zone -->
        <div class="rounded-2xl border border-danger/20 bg-white p-6 shadow-sm sm:p-8">
            <h3 class="text-lg font-bold text-ink">Delete homework</h3>
            <p class="mt-1 text-sm text-muted">This permanently removes this assignment{{ $homework->hasAttachment() ? ' and its attachment' : '' }}.</p>
            <x-danger-button class="mt-4" x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-homework-deletion')">
                Delete Homework
            </x-danger-button>
        </div>
    </div>

    <x-modal name="confirm-homework-deletion" focusable>
        <form method="POST" action="{{ route('tutor.homework.destroy', $homework) }}" class="p-6">
            @csrf
            @method('DELETE')
            <h2 class="text-lg font-bold text-ink">Delete "{{ $homework->title }}"?</h2>
            <p class="mt-2 text-sm text-muted">Are you sure? This cannot be undone.</p>
            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                <x-danger-button>Delete Homework</x-danger-button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
