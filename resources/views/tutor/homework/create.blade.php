<x-app-layout>
    <x-slot name="header">Create Homework</x-slot>

    <div class="mx-auto max-w-2xl">
        <a href="{{ route('tutor.homework.index') }}" class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-primary-dark">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            Back to homework
        </a>

        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm sm:p-8">
            <h2 class="mb-6 text-xl font-extrabold text-ink">New homework</h2>

            <form method="POST" action="{{ route('tutor.homework.store') }}" enctype="multipart/form-data"
                  x-data="homeworkForm()" @submit="onSubmit()">
                @csrf
                @include('tutor.homework._form', ['homework' => null])

                <div class="mt-8 flex items-center justify-end gap-3">
                    <a href="{{ route('tutor.homework.index') }}" class="text-sm font-semibold text-muted hover:text-ink">Cancel</a>
                    <x-primary-button>Assign Homework</x-primary-button>
                </div>

                {{-- Processing spinner with staged messages. @submit only fires after native validation passes. --}}
                <div x-show="submitting" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center bg-ink/40">
                    <div class="flex flex-col items-center gap-3 rounded-2xl bg-white px-8 py-6 shadow-xl">
                        <svg class="h-8 w-8 animate-spin text-primary" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <p class="text-sm font-semibold text-ink" x-text="stage"></p>
                    </div>
                </div>
            </form>

            <script>
                function homeworkForm() {
                    return {
                        submitting: false,
                        stage: 'Connecting to server…',
                        onSubmit() {
                            this.submitting = true;
                            this.stage = 'Connecting to server…';
                            // Switch the message once the request is on its way.
                            setTimeout(() => { this.stage = 'Saving to database…'; }, 900);
                        },
                    };
                }
            </script>
        </div>
    </div>
</x-app-layout>
