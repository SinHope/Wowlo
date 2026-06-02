{{-- One-time "Add to home screen" prompt. Shown once; dismissal is remembered. --}}
<div x-data="pwaInstall()" x-show="visible" x-cloak x-transition
     class="fixed inset-x-4 bottom-4 z-40 mx-auto max-w-md rounded-2xl border border-gray-200 bg-white p-4 shadow-xl sm:left-auto sm:right-4 sm:mx-0">
    <div class="flex items-start gap-3">
        <img src="{{ asset('images/pwa/icon-192.png') }}" alt="Wowlo" class="h-11 w-11 rounded-xl">
        <div class="min-w-0 flex-1">
            <p class="font-bold text-ink">Add Wowlo to your home screen</p>
            <p class="mt-0.5 text-sm text-muted">Install the app for a faster, full-screen experience and notifications.</p>
            <div class="mt-3 flex gap-2">
                <button @click="install()"
                        class="rounded-lg bg-primary px-3 py-1.5 text-sm font-semibold text-white hover:bg-primary-dark cursor-pointer">
                    Install
                </button>
                <button @click="dismiss()"
                        class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm font-semibold text-ink hover:bg-gray-50 cursor-pointer">
                    Not now
                </button>
            </div>
        </div>
        <button @click="dismiss()" class="text-gray-400 hover:text-ink cursor-pointer" aria-label="Dismiss">&times;</button>
    </div>
</div>

<script>
    function pwaInstall() {
        return {
            visible: false,
            deferred: null,
            init() {
                // Already installed (running standalone)? Never show.
                if (window.matchMedia('(display-mode: standalone)').matches) return;
                // Dismissed/installed before? Never show again.
                if (localStorage.getItem('wowlo_pwa_prompt') === 'dismissed') return;

                // The browser fires this when the app is installable.
                window.addEventListener('beforeinstallprompt', (e) => {
                    e.preventDefault();
                    this.deferred = e;
                    this.visible = true;
                });
            },
            async install() {
                if (! this.deferred) { this.done(); return; }
                this.deferred.prompt();
                await this.deferred.userChoice;
                this.done();
            },
            dismiss() { this.done(); },
            done() {
                this.visible = false;
                this.deferred = null;
                localStorage.setItem('wowlo_pwa_prompt', 'dismissed'); // shown once
            },
        };
    }
</script>
