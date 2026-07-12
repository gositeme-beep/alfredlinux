/**
 * GoSiteMe DraftGuard — Universal Auto-Save & Crash Recovery
 * ───────────────────────────────────────────────────────────
 * Automatically saves form inputs, textareas, and contenteditable fields
 * to IndexedDB every few seconds. On page load, checks for unsaved drafts
 * and offers recovery. Optionally syncs to the server for cross-device access.
 *
 * Usage:
 *   Automatically active on all pages with forms.
 *   No configuration needed — just include in footer.
 *
 * Manual control:
 *   DraftGuard.save()           — force save now
 *   DraftGuard.clear()          — clear drafts for this page
 *   DraftGuard.clearAll()       — clear all drafts
 *   DraftGuard.disable()        — pause auto-save
 *   DraftGuard.enable()         — resume auto-save
 */

(function () {
    'use strict';

    const DB_NAME = 'GoSiteMe_DraftGuard';
    const DB_VERSION = 1;
    const STORE_NAME = 'drafts';
    const SAVE_INTERVAL = 5000;       // 5 seconds
    const MAX_DRAFT_AGE = 7 * 24 * 60 * 60 * 1000; // 7 days
    const SYNC_ENDPOINT = '/api/drafts.php';

    let db = null;
    let saveTimer = null;
    let enabled = true;
    let lastSnapshot = '';
    let recovering = false;

    // ── IndexedDB Setup ──────────────────────────────────

    function openDB() {
        return new Promise((resolve, reject) => {
            const req = indexedDB.open(DB_NAME, DB_VERSION);
            req.onupgradeneeded = (e) => {
                const idb = e.target.result;
                if (!idb.objectStoreNames.contains(STORE_NAME)) {
                    const store = idb.createObjectStore(STORE_NAME, { keyPath: 'pageKey' });
                    store.createIndex('updatedAt', 'updatedAt', { unique: false });
                }
            };
            req.onsuccess = (e) => { db = e.target.result; resolve(db); };
            req.onerror = (e) => reject(e.target.error);
        });
    }

    function getPageKey() {
        return location.pathname + location.search;
    }

    // ── Snapshot: Capture all form/input state ───────────

    function captureSnapshot() {
        const data = {};
        let hasData = false;

        // All named inputs, selects, textareas
        document.querySelectorAll('input, textarea, select').forEach(el => {
            const key = el.name || el.id;
            if (!key) return;
            // Skip hidden CSRF tokens, password fields, file inputs
            if (el.type === 'password' || el.type === 'file' || el.type === 'hidden') return;
            // Skip search bars and browser chrome
            if (el.type === 'search' && el.closest('header, nav, .site-header')) return;

            if (el.type === 'checkbox') {
                data[key] = el.checked;
            } else if (el.type === 'radio') {
                if (el.checked) data[key] = el.value;
            } else {
                const val = el.value;
                if (val && val.trim()) {
                    data[key] = val;
                    hasData = true;
                }
            }
        });

        // Contenteditable fields
        document.querySelectorAll('[contenteditable="true"]').forEach((el, i) => {
            const key = el.id || el.dataset.draftKey || `ce_${i}`;
            const content = el.innerHTML;
            if (content && content.trim() && content !== '<br>') {
                data[`__ce_${key}`] = content;
                hasData = true;
            }
        });

        return hasData ? data : null;
    }

    function restoreSnapshot(data) {
        if (!data) return;

        Object.entries(data).forEach(([key, value]) => {
            // Contenteditable
            if (key.startsWith('__ce_')) {
                const ceKey = key.slice(5);
                const el = document.getElementById(ceKey)
                    || document.querySelector(`[data-draft-key="${ceKey}"]`);
                if (el && el.getAttribute('contenteditable') === 'true') {
                    el.innerHTML = value;
                }
                return;
            }

            // Regular inputs
            const el = document.querySelector(`[name="${CSS.escape(key)}"]`)
                    || document.getElementById(key);
            if (!el) return;

            if (el.type === 'checkbox') {
                el.checked = !!value;
            } else if (el.type === 'radio') {
                const radio = document.querySelector(`[name="${CSS.escape(key)}"][value="${CSS.escape(value)}"]`);
                if (radio) radio.checked = true;
            } else {
                el.value = value;
                // Trigger input event so any JS listeners update
                el.dispatchEvent(new Event('input', { bubbles: true }));
            }
        });
    }

    // ── Save / Load / Delete ─────────────────────────────

    async function saveDraft() {
        if (!enabled || !db || recovering) return;

        const data = captureSnapshot();
        if (!data) return;

        const json = JSON.stringify(data);
        // Skip if nothing changed since last save
        if (json === lastSnapshot) return;
        lastSnapshot = json;

        const draft = {
            pageKey: getPageKey(),
            data: data,
            pageTitle: document.title,
            updatedAt: Date.now(),
            url: location.href
        };

        try {
            const tx = db.transaction(STORE_NAME, 'readwrite');
            tx.objectStore(STORE_NAME).put(draft);
            await tx.complete;
            updateIndicator('saved');
        } catch (e) {
            // Silently fail — don't break the page
        }
    }

    async function loadDraft(pageKey) {
        if (!db) return null;
        return new Promise((resolve) => {
            const tx = db.transaction(STORE_NAME, 'readonly');
            const req = tx.objectStore(STORE_NAME).get(pageKey || getPageKey());
            req.onsuccess = () => resolve(req.result || null);
            req.onerror = () => resolve(null);
        });
    }

    async function deleteDraft(pageKey) {
        if (!db) return;
        const tx = db.transaction(STORE_NAME, 'readwrite');
        tx.objectStore(STORE_NAME).delete(pageKey || getPageKey());
    }

    async function deleteAllDrafts() {
        if (!db) return;
        const tx = db.transaction(STORE_NAME, 'readwrite');
        tx.objectStore(STORE_NAME).clear();
    }

    async function cleanExpired() {
        if (!db) return;
        const cutoff = Date.now() - MAX_DRAFT_AGE;
        const tx = db.transaction(STORE_NAME, 'readwrite');
        const store = tx.objectStore(STORE_NAME);
        const index = store.index('updatedAt');
        const range = IDBKeyRange.upperBound(cutoff);
        const req = index.openCursor(range);
        req.onsuccess = (e) => {
            const cursor = e.target.result;
            if (cursor) {
                cursor.delete();
                cursor.continue();
            }
        };
    }

    // ── Server Sync (optional, for logged-in users) ──────

    async function syncToServer(draft) {
        // Only sync if user is logged in (check for session indicator)
        const loggedIn = document.querySelector('[data-client-id]') || document.body.dataset.clientId;
        if (!loggedIn) return;

        try {
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const headers = { 'Content-Type': 'application/json' };
            if (csrfMeta) headers['X-CSRF-Token'] = csrfMeta.content;

            await fetch(SYNC_ENDPOINT + '?action=save', {
                method: 'POST',
                credentials: 'same-origin',
                headers,
                body: JSON.stringify({
                    page_key: draft.pageKey,
                    data: draft.data,
                    page_title: draft.pageTitle
                })
            });
        } catch (e) {
            // Offline or server error — local draft is the fallback
        }
    }

    // ── Recovery Banner ──────────────────────────────────

    function showRecoveryBanner(draft) {
        // Don't show on login/logout pages
        if (/\/(login|logout|register)/.test(location.pathname)) return;

        const age = Date.now() - draft.updatedAt;
        const ageText = age < 60000 ? 'just now'
            : age < 3600000 ? Math.floor(age / 60000) + ' min ago'
            : age < 86400000 ? Math.floor(age / 3600000) + ' hr ago'
            : Math.floor(age / 86400000) + ' day(s) ago';

        const fieldCount = Object.keys(draft.data).length;

        const banner = document.createElement('div');
        banner.id = 'dg-recovery-banner';
        banner.innerHTML = `
            <div class="dg-banner-inner">
                <div class="dg-banner-icon"><i class="fas fa-shield-alt"></i></div>
                <div class="dg-banner-text">
                    <strong>Unsaved draft recovered</strong>
                    <span>${fieldCount} field(s) saved ${ageText}</span>
                </div>
                <div class="dg-banner-actions">
                    <button class="dg-btn-restore" onclick="DraftGuard.restore()">
                        <i class="fas fa-undo"></i> Restore
                    </button>
                    <button class="dg-btn-discard" onclick="DraftGuard.discard()">
                        <i class="fas fa-trash-alt"></i> Discard
                    </button>
                </div>
            </div>
        `;
        document.body.appendChild(banner);

        // Animate in
        requestAnimationFrame(() => banner.classList.add('dg-visible'));
    }

    function hideRecoveryBanner() {
        const banner = document.getElementById('dg-recovery-banner');
        if (banner) {
            banner.classList.remove('dg-visible');
            setTimeout(() => banner.remove(), 300);
        }
    }

    // ── Save Indicator (subtle) ──────────────────────────

    let indicatorTimeout = null;
    function updateIndicator(state) {
        let indicator = document.getElementById('dg-indicator');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.id = 'dg-indicator';
            document.body.appendChild(indicator);
        }
        indicator.className = `dg-indicator dg-${state}`;
        indicator.innerHTML = state === 'saved'
            ? '<i class="fas fa-check-circle"></i>'
            : '<i class="fas fa-circle-notch fa-spin"></i>';

        // Show briefly then fade
        indicator.style.opacity = '1';
        clearTimeout(indicatorTimeout);
        indicatorTimeout = setTimeout(() => {
            indicator.style.opacity = '0';
        }, 2000);
    }

    // ── Inject CSS ───────────────────────────────────────

    function injectStyles() {
        if (document.getElementById('dg-styles')) return;
        const style = document.createElement('style');
        style.id = 'dg-styles';
        style.textContent = `
            #dg-recovery-banner {
                position: fixed; bottom: -80px; left: 50%; transform: translateX(-50%);
                z-index: 100000; transition: bottom 0.4s cubic-bezier(0.34,1.56,0.64,1);
                max-width: 560px; width: calc(100% - 32px);
            }
            #dg-recovery-banner.dg-visible { bottom: 20px; }
            .dg-banner-inner {
                display: flex; align-items: center; gap: 14px; padding: 14px 20px;
                background: rgba(15,23,42,0.95); border: 1px solid rgba(99,102,241,0.3);
                border-radius: 14px; backdrop-filter: blur(16px);
                box-shadow: 0 8px 32px rgba(0,0,0,0.4);
            }
            .dg-banner-icon {
                width: 40px; height: 40px; border-radius: 10px;
                background: linear-gradient(135deg, #6366f1, #a855f7);
                display: flex; align-items: center; justify-content: center;
                color: white; font-size: 18px; flex-shrink: 0;
            }
            .dg-banner-text { flex: 1; min-width: 0; }
            .dg-banner-text strong { display: block; color: #e0e0ff; font-size: 14px; font-weight: 700; }
            .dg-banner-text span { color: #94a3b8; font-size: 12px; }
            .dg-banner-actions { display: flex; gap: 8px; flex-shrink: 0; }
            .dg-btn-restore, .dg-btn-discard {
                padding: 8px 14px; border-radius: 8px; font-size: 12px;
                font-weight: 700; cursor: pointer; border: none;
                display: inline-flex; align-items: center; gap: 5px; transition: all 0.2s;
            }
            .dg-btn-restore {
                background: linear-gradient(135deg, #22c55e, #10b981); color: white;
            }
            .dg-btn-restore:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(34,197,94,0.4); }
            .dg-btn-discard {
                background: rgba(239,68,68,0.1); color: #fca5a5;
                border: 1px solid rgba(239,68,68,0.2);
            }
            .dg-btn-discard:hover { background: rgba(239,68,68,0.2); }

            #dg-indicator {
                position: fixed; bottom: 12px; left: 12px; z-index: 99998;
                width: 28px; height: 28px; border-radius: 50%;
                display: flex; align-items: center; justify-content: center;
                font-size: 12px; opacity: 0; transition: opacity 0.3s;
                pointer-events: none;
            }
            .dg-saved { color: #22c55e; background: rgba(34,197,94,0.1); }
            .dg-saving { color: #6366f1; background: rgba(99,102,241,0.1); }

            @media (max-width: 500px) {
                .dg-banner-inner { flex-wrap: wrap; padding: 12px 14px; gap: 10px; }
                .dg-banner-actions { width: 100%; justify-content: flex-end; }
            }
        `;
        document.head.appendChild(style);
    }

    // ── Clear on Successful Submit ───────────────────────

    function watchFormSubmits() {
        document.addEventListener('submit', (e) => {
            const form = e.target;
            if (!form || form.tagName !== 'FORM') return;
            // Clear draft when form is successfully submitted
            deleteDraft();
            lastSnapshot = '';
        });
    }

    // ── Page Visibility / Unload Save ────────────────────

    function watchPageEvents() {
        // Save when user leaves / switches tabs
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) saveDraft();
        });

        // Save on beforeunload
        window.addEventListener('beforeunload', () => {
            if (!enabled) return;
            const data = captureSnapshot();
            if (!data) return;
            const json = JSON.stringify(data);
            if (json === lastSnapshot) return;

            // Synchronous save via localStorage fallback for unload
            try {
                const draft = {
                    pageKey: getPageKey(),
                    data: data,
                    pageTitle: document.title,
                    updatedAt: Date.now()
                };
                localStorage.setItem('dg_emergency_' + getPageKey(), JSON.stringify(draft));
            } catch (e) { /* best effort */ }
        });
    }

    // ── Check for Emergency Saves (localStorage fallback) ─

    async function checkEmergencySave() {
        const emergencyKey = 'dg_emergency_' + getPageKey();
        const raw = localStorage.getItem(emergencyKey);
        if (!raw) return null;

        try {
            const draft = JSON.parse(raw);
            // Move from localStorage to IndexedDB
            if (db && draft.data) {
                const tx = db.transaction(STORE_NAME, 'readwrite');
                tx.objectStore(STORE_NAME).put(draft);
            }
            localStorage.removeItem(emergencyKey);
            return draft;
        } catch (e) {
            localStorage.removeItem(emergencyKey);
            return null;
        }
    }

    // ── Init ─────────────────────────────────────────────

    let pendingDraft = null;

    async function init() {
        // Don't run in iframes or on error pages
        if (window !== window.top) return;
        if (document.querySelector('.error-page, .maintenance-page')) return;

        // Check if page has any input fields worth saving
        const hasInputs = document.querySelectorAll(
            'input:not([type="hidden"]):not([type="password"]):not([type="file"]):not([type="search"]), textarea, select, [contenteditable="true"]'
        ).length > 0;

        if (!hasInputs) return;

        injectStyles();

        try {
            await openDB();
        } catch (e) {
            return; // IndexedDB not available
        }

        // Clean up old drafts
        cleanExpired();

        // Check for emergency localStorage saves
        const emergency = await checkEmergencySave();

        // Check for existing draft
        const existing = emergency || await loadDraft();
        if (existing && existing.data && Object.keys(existing.data).length > 0) {
            // Only show recovery if the form is currently empty
            const currentData = captureSnapshot();
            if (!currentData || Object.keys(currentData).length === 0) {
                pendingDraft = existing;
                recovering = true;
                showRecoveryBanner(existing);
            }
        }

        // Start auto-save loop
        saveTimer = setInterval(saveDraft, SAVE_INTERVAL);

        // Watch for form submits and page events
        watchFormSubmits();
        watchPageEvents();
    }

    // ── Public API ───────────────────────────────────────

    window.DraftGuard = {
        save: saveDraft,
        clear: deleteDraft,
        clearAll: deleteAllDrafts,

        restore() {
            if (pendingDraft) {
                restoreSnapshot(pendingDraft.data);
                recovering = false;
                pendingDraft = null;
                hideRecoveryBanner();
                // Update snapshot so we don't re-show banner
                lastSnapshot = JSON.stringify(captureSnapshot());
            }
        },

        discard() {
            deleteDraft();
            recovering = false;
            pendingDraft = null;
            hideRecoveryBanner();
            lastSnapshot = '';
        },

        disable() {
            enabled = false;
            if (saveTimer) clearInterval(saveTimer);
        },

        enable() {
            enabled = true;
            saveTimer = setInterval(saveDraft, SAVE_INTERVAL);
        }
    };

    // Boot when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
