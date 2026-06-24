<x-app-layout>
    <x-slot name="header">New Banner Notification</x-slot>

    <div class="mx-auto max-w-2xl">
        <a href="{{ route('admin.banners.index') }}" class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-primary-dark">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            Back to banners
        </a>

        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm sm:p-8">
            <h2 class="mb-1 text-xl font-extrabold text-ink">Compose a banner</h2>
            <p class="mb-6 text-sm text-muted">It appears as a bar at the top of the app for everyone you pick, until they dismiss it.</p>

            <form method="POST" action="{{ route('admin.banners.store') }}"
                  x-data="spinner('Connecting to server…', 'Publishing banner…')" @submit="start()">
                @csrf
                @include('admin.banners._form', ['banner' => null])

                <div class="mt-8 flex items-center justify-end gap-3">
                    <a href="{{ route('admin.banners.index') }}" class="text-sm font-semibold text-muted hover:text-ink">Cancel</a>
                    <x-primary-button>Publish Banner</x-primary-button>
                </div>

                <x-spinner-overlay />
            </form>
        </div>
    </div>
</x-app-layout>
