/**
 * Wowlo service worker.
 * MVP scope: make the app installable + receive web-push notifications.
 * No offline caching in Phase 1 (that's Phase 2) — fetches pass through.
 */

self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

// A fetch handler is required for the app to qualify as installable.
// We just let the network handle everything (no cache yet).
self.addEventListener('fetch', () => {});

// A push arrived from our server → show a notification.
self.addEventListener('push', (event) => {
    let payload = {};
    try {
        payload = event.data ? event.data.json() : {};
    } catch (e) {
        payload = { title: 'Wowlo', body: event.data ? event.data.text() : '' };
    }

    const title = payload.title || 'Wowlo';
    const url = (payload.data && payload.data.url) || payload.url || '/dashboard';

    const options = {
        body: payload.body || '',
        icon: payload.icon || '/images/pwa/icon-192.png',
        badge: '/images/pwa/icon-192.png',
        data: { url },
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

// Clicking a notification focuses an open tab or opens the app at the right page.
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const target = (event.notification.data && event.notification.data.url) || '/dashboard';

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (const client of clientList) {
                if (client.url.includes(target) && 'focus' in client) {
                    return client.focus();
                }
            }
            if (self.clients.openWindow) {
                return self.clients.openWindow(target);
            }
        })
    );
});
