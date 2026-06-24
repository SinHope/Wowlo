<x-app-layout>
    <x-slot name="header">Banner Notifications</x-slot>

    <div class="mx-auto max-w-4xl space-y-5">

        @if (session('status'))
            <div x-data="{ show: true }" x-show="show" x-transition
                 class="flex items-center justify-between rounded-xl border border-success/30 bg-success/10 px-4 py-3">
                <p class="text-sm font-semibold text-success">{{ session('status') }}</p>
                <button @click="show = false" class="text-success cursor-pointer" aria-label="Dismiss">&times;</button>
            </div>
        @endif

        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-extrabold text-ink">Banner Notifications</h2>
                <p class="text-muted">App-wide announcements shown at the top of the app to the audience you choose.</p>
            </div>
            <a href="{{ route('admin.banners.create') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-primary-dark cursor-pointer">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                New Banner
            </a>
        </div>

        @forelse ($banners as $banner)
            <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm sm:p-5">
                <div class="flex flex-wrap items-center gap-2">
                    @if ($banner->is_active)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-success/10 px-2.5 py-0.5 text-xs font-semibold text-success">
                            <span class="h-1.5 w-1.5 rounded-full bg-success"></span> Live
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-muted">
                            <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span> Off
                        </span>
                    @endif
                    <span class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-semibold text-primary-dark">
                        {{ config('wowlo.banner_audiences')[$banner->audience] ?? $banner->audience }}
                    </span>
                    <span class="text-xs text-muted">{{ $banner->created_at->format('j M Y, g:i a') }}</span>
                </div>

                {{-- Preview, rendered exactly as users see it (already sanitised). --}}
                <div class="mt-3 rounded-lg border border-yellow-400 bg-yellow-300 px-4 py-2.5 text-sm font-semibold text-red-700 [&_a]:font-bold [&_a]:underline">
                    {!! $banner->content !!}
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <form method="POST" action="{{ route('admin.banners.toggle', $banner) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                                class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-semibold text-ink hover:bg-gray-50 cursor-pointer">
                            {{ $banner->is_active ? 'Switch off' : 'Switch on' }}
                        </button>
                    </form>
                    <a href="{{ route('admin.banners.edit', $banner) }}"
                       class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-semibold text-ink hover:bg-gray-50 cursor-pointer">
                        Edit
                    </a>
                    <form method="POST" action="{{ route('admin.banners.destroy', $banner) }}"
                          onsubmit="return confirm('Delete this banner for good?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="rounded-lg border border-danger/30 px-3 py-1.5 text-sm font-semibold text-danger hover:bg-danger/10 cursor-pointer">
                            Delete
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-gray-100 bg-white py-16 text-center shadow-sm">
                <p class="text-lg font-bold text-ink">No banners yet</p>
                <p class="mt-1 text-sm text-muted">Compose one to broadcast a notice to your users.</p>
                <a href="{{ route('admin.banners.create') }}"
                   class="mt-4 inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-primary-dark cursor-pointer">
                    New Banner
                </a>
            </div>
        @endforelse

        @if ($banners->hasPages())
            <div>{{ $banners->links() }}</div>
        @endif
    </div>
</x-app-layout>
