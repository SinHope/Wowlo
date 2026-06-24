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

/**
 * Reusable rich-text editor backing <x-rich-text-editor>. Wraps a
 * contenteditable surface + the browser's execCommand, and — crucially —
 * tracks which commands are ACTIVE at the cursor (queryCommandState) so the
 * toolbar can highlight the buttons currently in effect (Bold on/off, etc.).
 */
Alpine.data('richEditor', () => ({
    content: '',
    // Pre-declared so Alpine tracks them reactively (toolbar highlight).
    active: { bold: false, italic: false, underline: false, strikeThrough: false, insertUnorderedList: false },
    init() {
        // styleWithCSS makes foreColor emit <span style="color:…"> not <font>.
        try { document.execCommand('styleWithCSS', false, true); } catch (e) {}
        this.content = this.$refs.editor.innerHTML;
        this.refresh();
        // Re-read the active formatting whenever the caret/selection moves
        // inside our editor (clicking around, arrow keys, etc.).
        this._onSelection = () => { if (this.inEditor()) this.refresh(); };
        document.addEventListener('selectionchange', this._onSelection);
    },
    destroy() {
        document.removeEventListener('selectionchange', this._onSelection);
    },
    inEditor() {
        const sel = window.getSelection();
        return sel && sel.rangeCount > 0 && this.$refs.editor.contains(sel.anchorNode);
    },
    refresh() {
        for (const cmd of Object.keys(this.active)) {
            try { this.active[cmd] = document.queryCommandState(cmd); } catch (e) { this.active[cmd] = false; }
        }
    },
    cmd(command, value = null) {
        this.$refs.editor.focus();
        try { document.execCommand('styleWithCSS', false, true); } catch (e) {}
        document.execCommand(command, false, value);
        this.sync();
        this.refresh();
    },
    sync() {
        this.content = this.$refs.editor.innerHTML;
    },
    addLink() {
        const url = window.prompt('Link URL (must start with https:// or mailto:):', 'https://');
        if (!url) return;
        this.cmd('createLink', url);
    },
}));

Alpine.start();

// Capture the install prompt as early as possible (it can fire before any
// Alpine component mounts). Both the install button and the install toast read
// window.__wowloDeferredInstall so neither misses the event. iOS never fires
// this — those users get manual "Add to Home Screen" instructions instead.
window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    window.__wowloDeferredInstall = e;
    window.dispatchEvent(new Event('wowlo:installable'));
});
window.addEventListener('appinstalled', () => {
    window.__wowloDeferredInstall = null;
    window.dispatchEvent(new Event('wowlo:installed'));
});

// Register the PWA service worker (installability + web push).
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // Best-effort: if registration fails the app still works normally.
        });
    });
}
