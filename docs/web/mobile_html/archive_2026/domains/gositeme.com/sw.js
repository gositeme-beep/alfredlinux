/* ═══════════════════════════════════════════════════════════════════════════════
   GoSiteMe Service Worker v3.1
   PWA support for Alfred Voice Widget + offline caching + background sync + push
   ═══════════════════════════════════════════════════════════════════════════════ */

const CACHE_NAME = 'gositeme-v9';
const VOICE_CACHE = 'gositeme-voice-v1';
const API_CACHE = 'gositeme-api-v1';
const OFFLINE_QUEUE = 'gositeme-offline-queue';

const PRECACHE_URLS = [
  '/',
  '/offline.html',
  '/assets/css/site.css',
  '/assets/css/alfred-widget.min.css',
  '/assets/js/alfred-widget.min.js',
  '/manifest.json',
  '/brand/favicon.png',
  '/tools/',
  '/use-cases/',
  '/articles/',
  '/pricing.php',
  '/about.php',
  '/docs/',
  '/changelog.php',
  '/compare.php',
  '/enterprise.php',
  '/pay/account/crypto',
  '/pay/account/gsm-token',
  '/chess/'
];

const OFFLINE_PAGE = '/offline.html';

/* ── Install ── */
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(PRECACHE_URLS))
      .then(() => self.skipWaiting())
  );
});

/* ── Activate ── */
self.addEventListener('activate', event => {
  const keepCaches = [CACHE_NAME, VOICE_CACHE, API_CACHE];
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(
        keys.filter(k => !keepCaches.includes(k))
            .map(k => caches.delete(k))
      )
    ).then(() => self.clients.claim())
  );
});

/* ── Fetch ── */
self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);

  // Skip non-GET, WebSocket, cross-origin requests, and mutating API calls
  if (event.request.method !== 'GET') return;
  if (url.origin !== self.location.origin) return;
  if (url.protocol === 'ws:' || url.protocol === 'wss:') return;
  if (url.pathname.startsWith('/whmcs/') && url.pathname.includes('.php')) return;
  if (url.pathname.startsWith('/store/')) return;

  // Cache safe read-only API endpoints (stale-while-revalidate, 5 min TTL)
  const cachedApiPaths = ['/api/health.php', '/api/smart-search.php'];
  if (url.pathname.startsWith('/api/') && cachedApiPaths.some(p => url.pathname === p)) {
    event.respondWith(
      caches.open(API_CACHE).then(cache =>
        cache.match(event.request).then(cached => {
          const fetchPromise = fetch(event.request).then(response => {
            if (response.ok) cache.put(event.request, response.clone());
            return response;
          }).catch(() => cached);
          return cached || fetchPromise;
        })
      )
    );
    return;
  }

  // Skip all other API calls
  if (url.pathname.startsWith('/api/')) return;

  // Network-first for HTML pages
  if (event.request.headers.get('accept')?.includes('text/html')) {
    event.respondWith(
      fetch(event.request)
        .then(response => {
          const clone = response.clone();
          caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
          return response;
        })
        .catch(() => caches.match(event.request).then(r => r || caches.match(OFFLINE_PAGE)))
    );
    return;
  }

  // Stale-while-revalidate for static assets (respects cache-busting ?v= params)
  if (url.pathname.match(/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff2?|ttf|eot)$/)) {
    event.respondWith(
      caches.match(event.request).then(cached => {
        // Always fetch fresh copy in background
        const fetchPromise = fetch(event.request).then(response => {
          if (response.ok) {
            const clone = response.clone();
            caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
          }
          return response;
        }).catch(() => cached);
        // Return cached immediately if available, otherwise wait for network
        return cached || fetchPromise;
      })
    );
    return;
  }

  // Default: network with cache fallback
  event.respondWith(
    fetch(event.request).catch(() => caches.match(event.request))
  );
});

/* ── Push Notifications ── */
self.addEventListener('push', event => {
  const data = event.data?.json() || {};
  const options = {
    body: data.body || 'You have a new message from Alfred',
    icon: data.icon || '/assets/img/icon-192.png',
    badge: '/assets/img/icon-96.png',
    tag: data.tag || 'alfred-' + (data.type || 'notification'),
    renotify: !!data.renotify,
    requireInteraction: data.type === 'call' || data.type === 'urgent',
    data: { url: data.url || '/', type: data.type || 'general' },
    actions: data.actions || [],
    vibrate: data.type === 'call' ? [200, 100, 200, 100, 200] : [100, 50, 100],
  };
  if (data.image) options.image = data.image;
  event.waitUntil(self.registration.showNotification(data.title || 'GoSiteMe', options));
});

self.addEventListener('notificationclick', event => {
  event.notification.close();
  const action = event.action;
  const data = event.notification.data || {};
  let url = data.url || '/';

  // Handle notification action buttons
  if (action === 'answer' && data.type === 'call') {
    url = '/conference-room.php?auto_answer=1';
  } else if (action === 'dismiss') {
    return;
  } else if (action === 'reply') {
    url = '/team-chat.php';
  }

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then(windowClients => {
      // Focus existing tab if available
      for (const client of windowClients) {
        if (client.url.includes(new URL(url, self.location.origin).pathname) && 'focus' in client) {
          return client.focus();
        }
      }
      return clients.openWindow(url);
    })
  );
});

/* ── Background Sync ── */
self.addEventListener('sync', event => {
  if (event.tag === 'alfred-offline-queue') {
    event.waitUntil(processOfflineQueue());
  }
});

async function processOfflineQueue() {
  try {
    const cache = await caches.open(API_CACHE);
    const keys = await cache.keys();
    const queuedRequests = keys.filter(k => k.url.includes('__queued__'));

    for (const request of queuedRequests) {
      const cachedResponse = await cache.match(request);
      if (!cachedResponse) continue;
      const body = await cachedResponse.text();
      const originalUrl = request.url.replace('__queued__/', '');

      try {
        await fetch(originalUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body,
        });
        await cache.delete(request);
      } catch (e) {
        // Will retry on next sync
      }
    }
  } catch (e) {
    // Queue processing failed, will retry
  }
}

/* ── Periodic Background Sync (dashboard refresh) ── */
self.addEventListener('periodicsync', event => {
  if (event.tag === 'alfred-dashboard-refresh') {
    event.waitUntil(refreshDashboardData());
  }
});

async function refreshDashboardData() {
  try {
    const response = await fetch('/api/health.php');
    if (response.ok) {
      const cache = await caches.open(API_CACHE);
      await cache.put('/api/health.php', response);
    }
  } catch (e) {
    // Offline, skip
  }
}

/* ── Message handler for client communication ── */
self.addEventListener('message', event => {
  if (event.data?.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
  if (event.data?.type === 'QUEUE_REQUEST') {
    const { url, body } = event.data;
    caches.open(API_CACHE).then(cache => {
      cache.put(
        new Request(url.replace('/api/', '/api/__queued__/')),
        new Response(JSON.stringify(body))
      );
    });
  }
});
