/**
 * GoSiteMe PWA Manager v1.0
 * ─────────────────────────
 * Handles: install prompts, push subscriptions, background sync, update detection.
 */
(function() {
  'use strict';

  const PWA = window.GoSiteMePWA = {
    deferredPrompt: null,
    swRegistration: null,
    isInstalled: false,

    /** Initialize PWA features */
    init() {
      this.isInstalled = window.matchMedia('(display-mode: standalone)').matches
                      || navigator.standalone === true;

      // Capture install prompt
      window.addEventListener('beforeinstallprompt', e => {
        e.preventDefault();
        this.deferredPrompt = e;
        this._showInstallUI();
      });

      // Detect app installed
      window.addEventListener('appinstalled', () => {
        this.isInstalled = true;
        this.deferredPrompt = null;
        this._hideInstallUI();
      });

      // Register/update service worker
      if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').then(reg => {
          this.swRegistration = reg;
          // Check for updates periodically
          setInterval(() => reg.update(), 60 * 60 * 1000); // hourly
          // Detect waiting SW
          reg.addEventListener('updatefound', () => {
            const newWorker = reg.installing;
            newWorker.addEventListener('statechange', () => {
              if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                this._showUpdateBanner();
              }
            });
          });
        }).catch(() => {});
      }

      // Register periodic sync if supported
      this._registerPeriodicSync();
    },

    /** Trigger install prompt */
    async promptInstall() {
      if (!this.deferredPrompt) return false;
      this.deferredPrompt.prompt();
      const result = await this.deferredPrompt.userChoice;
      this.deferredPrompt = null;
      return result.outcome === 'accepted';
    },

    /** Subscribe to push notifications */
    async subscribePush(vapidPublicKey) {
      if (!this.swRegistration || !('PushManager' in window)) return null;
      try {
        const existing = await this.swRegistration.pushManager.getSubscription();
        if (existing) return existing;

        const sub = await this.swRegistration.pushManager.subscribe({
          userVisibleOnly: true,
          applicationServerKey: this._urlBase64ToUint8Array(vapidPublicKey),
        });
        // Send subscription to server
        await fetch('/api/notifications.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'subscribe', subscription: sub.toJSON() }),
        });
        return sub;
      } catch (e) {
        return null;
      }
    },

    /** Queue an API request for background sync (offline support) */
    queueRequest(url, body) {
      if (!navigator.serviceWorker?.controller) return false;
      navigator.serviceWorker.controller.postMessage({
        type: 'QUEUE_REQUEST', url, body,
      });
      // Request background sync
      if (this.swRegistration?.sync) {
        this.swRegistration.sync.register('alfred-offline-queue').catch(() => {});
      }
      return true;
    },

    /** Force update to new service worker */
    applyUpdate() {
      if (this.swRegistration?.waiting) {
        this.swRegistration.waiting.postMessage({ type: 'SKIP_WAITING' });
        window.location.reload();
      }
    },

    // ── Private ──

    async _registerPeriodicSync() {
      if (!this.swRegistration || !('periodicSync' in this.swRegistration)) return;
      const status = await navigator.permissions.query({ name: 'periodic-background-sync' });
      if (status.state === 'granted') {
        try {
          await this.swRegistration.periodicSync.register('alfred-dashboard-refresh', {
            minInterval: 12 * 60 * 60 * 1000, // 12 hours
          });
        } catch (e) {}
      }
    },

    _showInstallUI() {
      // Create install banner if not exists
      if (this.isInstalled || document.getElementById('pwa-install-banner')) return;
      const banner = document.createElement('div');
      banner.id = 'pwa-install-banner';
      banner.innerHTML = `
        <div style="position:fixed;bottom:20px;left:50%;transform:translateX(-50%);background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;padding:12px 24px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.3);display:flex;align-items:center;gap:12px;z-index:99999;font-family:-apple-system,system-ui,sans-serif;max-width:90vw">
          <span style="font-size:24px">📱</span>
          <div style="flex:1">
            <div style="font-weight:600;font-size:14px">Install GoSiteMe</div>
            <div style="font-size:12px;opacity:.85">Quick access to Alfred AI — works offline</div>
          </div>
          <button onclick="GoSiteMePWA.promptInstall()" style="background:#fff;color:#764ba2;border:none;border-radius:8px;padding:8px 16px;font-weight:600;cursor:pointer;font-size:13px;white-space:nowrap">Install</button>
          <button onclick="this.closest('#pwa-install-banner').remove()" style="background:none;border:none;color:#fff;cursor:pointer;font-size:18px;padding:0 4px">&times;</button>
        </div>`;
      document.body.appendChild(banner);
    },

    _hideInstallUI() {
      document.getElementById('pwa-install-banner')?.remove();
    },

    _showUpdateBanner() {
      if (document.getElementById('pwa-update-banner')) return;
      const banner = document.createElement('div');
      banner.id = 'pwa-update-banner';
      banner.innerHTML = `
        <div style="position:fixed;top:20px;left:50%;transform:translateX(-50%);background:#1a1a2e;color:#fff;padding:12px 24px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.3);display:flex;align-items:center;gap:12px;z-index:99999;font-family:-apple-system,system-ui,sans-serif">
          <span style="font-size:20px">🔄</span>
          <span style="font-size:13px">A new version is available</span>
          <button onclick="GoSiteMePWA.applyUpdate()" style="background:#667eea;color:#fff;border:none;border-radius:8px;padding:6px 14px;font-weight:600;cursor:pointer;font-size:12px">Update</button>
          <button onclick="this.closest('#pwa-update-banner').remove()" style="background:none;border:none;color:#fff;cursor:pointer;font-size:16px">&times;</button>
        </div>`;
      document.body.appendChild(banner);
    },

    _urlBase64ToUint8Array(base64String) {
      const padding = '='.repeat((4 - base64String.length % 4) % 4);
      const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
      const raw = atob(base64);
      const arr = new Uint8Array(raw.length);
      for (let i = 0; i < raw.length; i++) arr[i] = raw.charCodeAt(i);
      return arr;
    },
  };

  // Auto-init when DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => PWA.init());
  } else {
    PWA.init();
  }
})();
