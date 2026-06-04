{{--
    "Install app" affordance for the home + login pages.

    - Android / Chrome / Edge: clicking fires the browser's native install prompt.
    - iOS Safari: there is NO native prompt event, so we show "Add to Home Screen"
      steps instead. (This is a hard iOS limitation, not a bug.)
    - Already installed (running standalone): the button hides itself.

    Gated by config('wowlo.promote_install') so we don't invite installs on a
    throwaway domain — see config/wowlo.php for why.

    Usage:  <x-install-app />              (compact pill, for a nav bar)
            <x-install-app variant="block" /> (full-width, for the login card)
--}}
@props(['variant' => 'pill'])

@if (config('wowlo.promote_install'))
    @once
        <style>[x-cloak]{display:none !important;}</style>
    @endonce

    <div x-data="installApp()" x-show="supported" x-cloak>
        @if ($variant === 'block')
            <button type="button" @click="trigger()"
                    class="flex w-full items-center justify-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-ink transition-colors duration-200 hover:bg-gray-50 cursor-pointer">
                <svg class="h-5 w-5 text-primary-dark" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                <span>Install Wowlo on your phone, tablet or desktop</span>
            </button>
        @else
            <button type="button" @click="trigger()"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-primary/30 bg-primary/5 px-3 py-2 text-sm font-bold text-primary-dark transition-colors hover:bg-primary/10 cursor-pointer"
                    title="Install Wowlo on your phone, tablet or desktop">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                <span>Install app</span>
            </button>
        @endif

        {{-- Instructions modal (iOS, or any browser without a native prompt) --}}
        <template x-teleport="body">
            <div x-show="modal" x-cloak
                 class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 p-4 sm:items-center"
                 @click.self="modal = false" @keydown.escape.window="modal = false">
                <div x-show="modal" x-transition
                     class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                    <div class="flex items-start gap-3">
                        <img src="{{ asset('images/pwa/icon-192.png') }}" alt="Wowlo" class="h-11 w-11 rounded-xl">
                        <div class="min-w-0 flex-1">
                            <h2 class="text-lg font-extrabold text-ink">Install Wowlo</h2>
                            <p class="mt-0.5 text-sm text-muted">Add Wowlo to your device for a faster, full-screen app.</p>
                        </div>
                        <button @click="modal = false" class="text-gray-400 hover:text-ink cursor-pointer" aria-label="Close">&times;</button>
                    </div>

                    {{-- iOS --}}
                    <ol x-show="platform === 'ios'" class="mt-4 space-y-2 text-sm text-ink">
                        <li class="flex gap-2"><span class="font-bold text-primary-dark">1.</span> Tap the <span class="font-semibold">Share</span> button (the square with an upward arrow) at the bottom of Safari.</li>
                        <li class="flex gap-2"><span class="font-bold text-primary-dark">2.</span> Scroll down and tap <span class="font-semibold">Add to Home Screen</span>.</li>
                        <li class="flex gap-2"><span class="font-bold text-primary-dark">3.</span> Tap <span class="font-semibold">Add</span> — Wowlo will appear on your home screen.</li>
                    </ol>

                    {{-- Android --}}
                    <ol x-show="platform === 'android'" class="mt-4 space-y-2 text-sm text-ink">
                        <li class="flex gap-2"><span class="font-bold text-primary-dark">1.</span> Tap the <span class="font-semibold">⋮</span> menu (top-right of Chrome).</li>
                        <li class="flex gap-2"><span class="font-bold text-primary-dark">2.</span> Tap <span class="font-semibold">Install app</span> (or “Add to Home screen”).</li>
                        <li class="flex gap-2"><span class="font-bold text-primary-dark">3.</span> Confirm — Wowlo will appear with your apps.</li>
                    </ol>

                    {{-- Desktop / other --}}
                    <ol x-show="platform === 'desktop'" class="mt-4 space-y-2 text-sm text-ink">
                        <li class="flex gap-2"><span class="font-bold text-primary-dark">1.</span> In Chrome or Edge, click the <span class="font-semibold">install icon</span> in the address bar (a small screen with a down-arrow).</li>
                        <li class="flex gap-2"><span class="font-bold text-primary-dark">2.</span> Or open the browser menu and choose <span class="font-semibold">Install Wowlo</span>.</li>
                        <li class="flex gap-2"><span class="font-bold text-primary-dark">3.</span> Wowlo opens in its own window, like a desktop app.</li>
                    </ol>

                    <button @click="modal = false"
                            class="mt-5 w-full rounded-lg bg-primary px-4 py-2.5 text-sm font-bold text-white hover:bg-primary-dark cursor-pointer">
                        Got it
                    </button>
                </div>
            </div>
        </template>
    </div>

    @once
        <script>
            function installApp() {
                return {
                    supported: false,   // show the button at all?
                    modal: false,
                    platform: 'desktop',
                    init() {
                        // Already installed (running standalone)? Never show.
                        const standalone = window.matchMedia('(display-mode: standalone)').matches
                            || window.navigator.standalone === true;
                        if (standalone) return;

                        this.platform = this.detectPlatform();
                        this.supported = true;

                        window.addEventListener('wowlo:installed', () => {
                            this.supported = false;
                            this.modal = false;
                        });
                    },
                    detectPlatform() {
                        const ua = navigator.userAgent || '';
                        const iOS = /iphone|ipad|ipod/i.test(ua)
                            || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
                        if (iOS) return 'ios';
                        if (/android/i.test(ua)) return 'android';
                        return 'desktop';
                    },
                    async trigger() {
                        const deferred = window.__wowloDeferredInstall;
                        if (deferred) {
                            // Native install prompt (Android / Chrome / Edge).
                            deferred.prompt();
                            await deferred.userChoice;
                            window.__wowloDeferredInstall = null;
                            return;
                        }
                        // No native prompt (iOS, or already triggered) → show steps.
                        this.modal = true;
                    },
                };
            }
        </script>
    @endonce
@endif
