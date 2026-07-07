/**
 * GoSiteMe Veil v2 — PWA + Offline Queue
 *
 * Progressive Web App support:
 * - Service Worker for offline caching
 * - Message queue when offline (auto-send on reconnect)
 * - Web Push notifications
 * - Install prompt handler
 */
const CommsPWA = (() => {
    'use strict';

    const OFFLINE_QUEUE_KEY = 'comms_offline_queue';
    let isOnline = navigator.onLine;
    let swRegistration = null;

    /**
     * Initialize PWA features
     */
    async function init() {
        // Track online/offline status
        window.addEventListener('online', onOnline);
        window.addEventListener('offline', onOffline);

        // Register service worker
        if ('serviceWorker' in navigator) {
            try {
                swRegistration = await navigator.serviceWorker.register('/comms/sw-comms.js', {
                    scope: '/comms/'
                });

                // Listen for push messages from SW
                navigator.serviceWorker.addEventListener('message', (event) => {
                    if (event.data?.type === 'COMMS_PUSH') {
                        handlePushMessage(event.data.payload);
                    }
                });
            } catch (err) {
                console.warn('SW registration failed:', err);
            }
        }

        // Handle install prompt
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            window.commsInstallPrompt = e;
            showInstallBanner();
        });
    }

    /**
     * Request push notification permission and subscribe
     */
    async function subscribePush() {
        if (!swRegistration) return null;

        const permission = await Notification.requestPermission();
        if (permission !== 'granted') return null;

        try {
            // Get VAPID public key from server
            const resp = await fetch('/api/comms.php?action=push_key', { credentials: 'same-origin' });
            const data = await resp.json();
            if (!data.success || !data.vapid_public) return null;

            const subscription = await swRegistration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(data.vapid_public),
            });

            // Send subscription to server
            await fetch('/api/comms.php?action=push_subscribe', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': window.CommsApp?.csrfToken || '',
                },
                body: JSON.stringify({ subscription: subscription.toJSON() }),
            });

            return subscription;
        } catch (err) {
            console.warn('Push subscription failed:', err);
            return null;
        }
    }

    // ── Offline Queue ──────────────────────────────────────────────

    /**
     * Queue a message for sending when back online
     */
    function queueMessage(msg) {
        const queue = getQueue();
        queue.push({ ...msg, queued_at: Date.now() });
        saveQueue(queue);
    }

    /**
     * Process the offline queue
     */
    async function flushQueue() {
        const queue = getQueue();
        if (queue.length === 0) return;

        const failed = [];
        for (const msg of queue) {
            try {
                const csrfToken = window.CommsApp?.csrfToken || '';
                const resp = await fetch('/api/comms.php?action=send', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken,
                    },
                    body: JSON.stringify(msg),
                });
                const data = await resp.json();
                if (data.csrf_token) window.CommsApp.csrfToken = data.csrf_token;
                if (!data.success) failed.push(msg);
            } catch {
                failed.push(msg);
            }
        }
        saveQueue(failed);

        const sent = queue.length - failed.length;
        if (sent > 0 && window.CommsApp) {
            const toast = document.createElement('div');
            toast.className = 'toast success';
            toast.innerHTML = `<i class="fas fa-check-circle"></i> ${sent} queued message${sent > 1 ? 's' : ''} sent`;
            document.getElementById('toast-container')?.appendChild(toast);
            setTimeout(() => toast.remove(), 4000);
        }
    }

    function getQueue() {
        try {
            return JSON.parse(localStorage.getItem(OFFLINE_QUEUE_KEY) || '[]');
        } catch {
            return [];
        }
    }

    function saveQueue(queue) {
        localStorage.setItem(OFFLINE_QUEUE_KEY, JSON.stringify(queue));
    }

    // ── Network Status ─────────────────────────────────────────────

    function onOnline() {
        isOnline = true;
        document.body.classList.remove('comms-offline');
        flushQueue();
    }

    function onOffline() {
        isOnline = false;
        document.body.classList.add('comms-offline');
    }

    // ── Install Banner ─────────────────────────────────────────────

    function showInstallBanner() {
        if (document.getElementById('pwa-install-banner')) return;

        const banner = document.createElement('div');
        banner.id = 'pwa-install-banner';
        banner.className = 'pwa-install-banner';
        banner.innerHTML = `
            <i class="fas fa-download" style="font-size:1.2rem;color:var(--accent)"></i>
            <div>
                <div style="font-weight:600;font-size:0.85rem">Install GoSiteMe Veil</div>
                <div style="font-size:0.75rem;color:var(--text-secondary)">Get notifications & use offline</div>
            </div>
            <button class="btn btn-primary btn-sm" id="pwa-install-btn">Install</button>
            <button class="icon-btn" id="pwa-dismiss-btn"><i class="fas fa-xmark"></i></button>
        `;
        document.body.appendChild(banner);

        document.getElementById('pwa-install-btn').addEventListener('click', async () => {
            if (window.commsInstallPrompt) {
                window.commsInstallPrompt.prompt();
                const result = await window.commsInstallPrompt.userChoice;
                if (result.outcome === 'accepted') {
                    banner.remove();
                }
                window.commsInstallPrompt = null;
            }
        });

        document.getElementById('pwa-dismiss-btn').addEventListener('click', () => banner.remove());
    }

    // ── Push Message Handler ───────────────────────────────────────

    function handlePushMessage(payload) {
        // Re-poll messages
        if (typeof CommsApp !== 'undefined') {
            CommsApp.pollMessages?.();
        }
    }

    // ── Utility ────────────────────────────────────────────────────

    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = atob(base64);
        return new Uint8Array([...rawData].map(c => c.charCodeAt(0)));
    }

    return {
        init,
        subscribePush,
        queueMessage,
        flushQueue,
        get isOnline() { return isOnline; },
        get queueLength() { return getQueue().length; },
    };
})();
