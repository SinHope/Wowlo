import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';

Alpine.plugin(collapse);
window.Alpine = Alpine;

/**
 * Reusable processing-spinner state. Use on a form (@submit="start()") or a
 * navigation link (@click="start()"), paired with <x-spinner-overlay />.
 * Optionally pass a second message that appears shortly after the first.
 */
Alpine.data('spinner', (stage1 = 'Connecting to server…', stage2 = null) => ({
    busy: false,
    stage: stage1,
    start() {
        this.busy = true;
        this.stage = stage1;
        if (stage2) {
            setTimeout(() => { this.stage = stage2; }, 900);
        }
    },
}));

Alpine.start();

// Register the PWA service worker (installability + web push).
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // Best-effort: if registration fails the app still works normally.
        });
    });
}
