<x-app-layout>
    <x-slot name="header">Create Homework</x-slot>

    <div class="mx-auto max-w-2xl">
        <a href="{{ route('tutor.homework.index') }}" class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-primary-dark">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            Back to homework
        </a>

        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm sm:p-8">
            <h2 class="mb-6 text-xl font-extrabold text-ink">New homework</h2>

            <form method="POST" action="{{ route('tutor.homework.store') }}" enctype="multipart/form-data">
                @csrf
                @include('tutor.homework._form', ['homework' => null])

                <div class="mt-8 flex items-center justify-end gap-3">
                    <a href="{{ route('tutor.homework.index') }}" class="text-sm font-semibold text-muted hover:text-ink">Cancel</a>
                    <x-primary-button>Assign Homework</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
