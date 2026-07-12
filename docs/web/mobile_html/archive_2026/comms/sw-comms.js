/**
 * GoSiteMe Veil — Service Worker
 * Offline caching, push notifications, background sync
 */
const CACHE_NAME = 'veil-v2.1-pq';
const PRECACHE = [
    '/comms/',
    '/comms/css/comms.css',
    '/comms/js/comms-crypto.js',
    '/comms/js/comms-pqc.js',
    '/comms/js/comms-rtc.js',
    '/comms/js/comms-app.js',
    '/comms/js/comms-voice.js',
    '/comms/js/comms-groups.js',
    '/comms/js/comms-alfred.js',
    '/comms/js/comms-pwa.js',
    '/assets/fontawesome/css/all.min.css',
    '/assets/fontawesome/webfonts/fa-solid-900.woff2',
    '/brand/favicon.ico',
];

// Install: precache core assets
self.addEventListener('install', (e) => {
    e.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(PRECACHE))
            .then(() => self.skipWaiting())
    );
});

// Activate: clean old caches
self.addEventListener('activate', (e) => {
    e.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
        ).then(() => self.clients.claim())
    );
});

// Fetch: network-first for API, cache-first for assets
self.addEventListener('fetch', (e) => {
    const url = new URL(e.request.url);

    // Never cache API calls
    if (url.pathname.startsWith('/api/')) {
        e.respondWith(
            fetch(e.request).catch(() =>
                new Response(JSON.stringify({ error: 'Offline', offline: true }), {
                    headers: { 'Content-Type': 'application/json' }
                })
            )
        );
        return;
    }

    // Cache-first for assets
    if (url.pathname.startsWith('/comms/') || url.pathname.startsWith('/assets/') || url.pathname.startsWith('/brand/')) {
        e.respondWith(
            caches.match(e.request).then(cached => {
                if (cached) return cached;
                return fetch(e.request).then(resp => {
                    if (resp.ok) {
                        const clone = resp.clone();
                        caches.open(CACHE_NAME).then(cache => cache.put(e.request, clone));
                    }
                    return resp;
                });
            })
        );
        return;
    }
});

// Push notifications
self.addEventListener('push', (e) => {
    let data = { title: 'GoSiteMe Veil', body: 'New encrypted message' };
    try {
        if (e.data) data = e.data.json();
    } catch { /* use defaults */ }

    e.waitUntil(
        self.registration.showNotification(data.title || 'GoSiteMe Veil', {
            body: data.body || 'New encrypted message',
            icon: '/brand/favicon.ico',
            badge: '/brand/favicon.ico',
            tag: 'comms-message',
            renotify: true,
            data: data,
        })
    );

    // Notify all clients
    e.waitUntil(
        self.clients.matchAll().then(clients => {
            clients.forEach(client => {
                client.postMessage({ type: 'COMMS_PUSH', payload: data });
            });
        })
    );
});

// Notification click — open app
self.addEventListener('notificationclick', (e) => {
    e.notification.close();
    e.waitUntil(
        self.clients.matchAll({ type: 'window' }).then(clients => {
            for (const client of clients) {
                if (client.url.includes('/comms') && 'focus' in client) {
                    return client.focus();
                }
            }
            return self.clients.openWindow('/comms/');
        })
    );
});
