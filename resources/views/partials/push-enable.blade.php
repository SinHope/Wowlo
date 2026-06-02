{{-- "Enable notifications" card. Best-effort: hides itself if push is unsupported or already on. --}}
<div x-data="pushEnable()" x-show="show" x-cloak
     class="flex items-center gap-4 rounded-2xl border border-primary/20 bg-primary/5 p-4">
    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10">
        <svg class="h-5 w-5 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
        </svg>
    </div>
    <div class="min-w-0 flex-1">
        <p class="font-bold text-ink">Turn on notifications</p>
        <p class="text-sm text-muted" x-text="message"></p>
    </div>
    <button @click="enable()" x-show="! enabled" :disabled="busy"
            class="shrink-0 rounded-lg bg-primary px-3 py-2 text-sm font-semibold text-white hover:bg-primary-dark disabled:opacity-50 cursor-pointer">
        <span x-text="busy ? 'Enabling…' : 'Enable'"></span>
    </button>
    <span x-show="enabled" class="shrink-0 rounded-full bg-success/10 px-3 py-1 text-sm font-bold text-success">On</span>
</div>

<script>
    function pushEnable() {
        return {
            show: false,
            enabled: false,
            busy: false,
            message: 'Get alerted about new homework and messages.',
            async init() {
                // Only show if the browser supports push and permission isn't denied.
                if (! ('serviceWorker' in navigator) || ! ('PushManager' in window) || ! ('Notification' in window)) return;
                if (Notification.permission === 'denied') return;

                const reg = await navigator.serviceWorker.ready.catch(() => null);
                if (! reg) return;

                const sub = await reg.pushManager.getSubscription().catch(() => null);
                this.enabled = !! sub;
                this.show = true;
                if (this.enabled) this.message = "You'll be notified about new homework and messages.";
            },
            async enable() {
                this.busy = true;
                try {
                    const permission = await Notification.requestPermission();
                    if (permission !== 'granted') {
                        this.message = 'Notifications were blocked. You can re-enable them in your browser settings.';
                        this.busy = false;
                        return;
                    }

                    const reg = await navigator.serviceWorker.ready;
                    const key = document.querySelector('meta[name="vapid-public-key"]').content;
                    const sub = await reg.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: urlBase64ToUint8Array(key),
                    });

                    const res = await fetch(document.querySelector('meta[name="push-subscribe-url"]').content, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify(sub.toJSON()),
                    });

                    if (res.ok) {
                        this.enabled = true;
                        this.message = "You'll be notified about new homework and messages.";
                    } else {
                        this.message = 'Could not enable notifications. Please try again.';
                    }
                } catch (e) {
                    this.message = 'Could not enable notifications on this device.';
                } finally {
                    this.busy = false;
                }
            },
        };
    }

    // VAPID public key (base64url) → Uint8Array, as the Push API requires.
    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const raw = window.atob(base64);
        const output = new Uint8Array(raw.length);
        for (let i = 0; i < raw.length; ++i) output[i] = raw.charCodeAt(i);
        return output;
    }
</script>
