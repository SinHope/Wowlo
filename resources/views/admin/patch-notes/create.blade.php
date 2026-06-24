<x-app-layout>
    <x-slot name="header">New Patch Note</x-slot>

    <div class="mx-auto max-w-2xl">
        <a href="{{ route('patch-notes.index') }}" class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-primary-dark">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            Back to patch notes
        </a>

        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm sm:p-8">
            <h2 class="mb-1 text-xl font-extrabold text-ink">Write a patch note</h2>
            <p class="mb-6 text-sm text-muted">It will be visible to everyone on the Patch Notes page.</p>

            <form method="POST" action="{{ route('admin.patch-notes.store') }}" enctype="multipart/form-data"
                  x-data="spinner('Connecting to server…', 'Publishing note…')" @submit="start()">
                @csrf
                @include('admin.patch-notes._form', ['note' => null])

                <div class="mt-8 flex items-center justify-end gap-3">
                    <a href="{{ route('patch-notes.index') }}" class="text-sm font-semibold text-muted hover:text-ink">Cancel</a>
                    <x-primary-button>Publish Note</x-primary-button>
                </div>

                <x-spinner-overlay />
            </form>
        </div>
    </div>
</x-app-layout>
