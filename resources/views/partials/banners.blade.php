@php
    // The active banners this user should see (audience-filtered, newest first).
    // One light query per page load — consistent with the unread-count queries
    // the sidebar already runs. Guarded so it never breaks a page if something
    // goes wrong with the table.
    $banners = \App\Models\Banner::visibleTo(auth()->user())->get();
@endphp

@if ($banners->isNotEmpty())
    <div class="flex flex-col">
        @foreach ($banners as $banner)
            {{-- Dismissal is remembered per-banner in localStorage, keyed by the
                 banner's last-updated time so editing it re-shows the bar. --}}
            <div x-data="bannerDismiss({{ $banner->id }}, '{{ $banner->updated_at?->timestamp }}')"
                 x-show="show" x-cloak
                 class="relative border-b border-yellow-400 bg-yellow-300 px-4 py-3 text-sm font-semibold text-red-700 sm:px-6">
                <div class="mx-auto flex max-w-5xl items-start gap-3">
                    <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['megaphone'] ?? '' }}" />
                    </svg>
                    {{-- Sanitised allowlist HTML (see App\Support\HtmlSanitizer). --}}
                    <div class="banner-content min-w-0 flex-1 leading-relaxed [&_a]:font-bold [&_a]:underline">
                        {!! $banner->content !!}
                    </div>
                    <button type="button" @click="dismiss()" aria-label="Dismiss notification"
                            class="-mr-1 shrink-0 rounded p-1 text-red-700/70 hover:bg-red-700/10 hover:text-red-700 cursor-pointer">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    <script>
        function bannerDismiss(id, version) {
            const key = `wowlo.banner.${id}.${version}`;
            return {
                show: true,
                init() {
                    try {
                        this.show = localStorage.getItem(key) !== '1';
                    } catch (e) {
                        // Private mode / disabled storage: just always show.
                        this.show = true;
                    }
                },
                dismiss() {
                    this.show = false;
                    try { localStorage.setItem(key, '1'); } catch (e) {}
                },
            };
        }
    </script>
@endif
