/**
 * GoSiteMe Design System — Shared Utilities v1.0
 * ────────────────────────────────────────────────
 * Common utility functions used across many pages.
 * Loaded globally via site-header.inc.php.
 *
 * Usage:
 *   GDS.esc('user <input>')           → 'user &lt;input&gt;'
 *   GDS.timeAgo('2026-03-01T...')     → '9d ago'
 *   GDS.formatNumber(12345)           → '12.3K'
 *   GDS.formatBytes(1048576)          → '1.00 MB'
 *   GDS.debounce(fn, 300)             → debounced function
 *   GDS.throttle(fn, 200)             → throttled function
 *   GDS.copyToClipboard('text')       → Promise<void>
 *   GDS.formatCurrency(29.99, 'USD')  → '$29.99'
 */

'use strict';

const GDS = (() => {
    // ── HTML Escaping ──
    const _escDiv = document.createElement('div');
    function esc(s) {
        if (!s) return '';
        _escDiv.textContent = s;
        return _escDiv.innerHTML;
    }

    // ── Time Ago ──
    function timeAgo(dateStr) {
        if (!dateStr) return '';
        const now = Date.now();
        const then = new Date(dateStr).getTime();
        const diff = Math.floor((now - then) / 1000);
        if (diff < 0) return 'just now';
        if (diff < 60) return diff + 's ago';
        if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
        if (diff < 604800) return Math.floor(diff / 86400) + 'd ago';
        if (diff < 2592000) return Math.floor(diff / 604800) + 'w ago';
        return new Date(dateStr).toLocaleDateString();
    }

    // ── Number Formatting ──
    function formatNumber(n) {
        n = Number(n) || 0;
        if (Math.abs(n) >= 1e9) return (n / 1e9).toFixed(1) + 'B';
        if (Math.abs(n) >= 1e6) return (n / 1e6).toFixed(1) + 'M';
        if (Math.abs(n) >= 1e3) return (n / 1e3).toFixed(1) + 'K';
        return n.toLocaleString();
    }

    // ── Byte Formatting ──
    function formatBytes(bytes) {
        bytes = Number(bytes) || 0;
        if (bytes === 0) return '0 B';
        const units = ['B', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(1024));
        return (bytes / Math.pow(1024, i)).toFixed(2) + ' ' + units[i];
    }

    // ── Currency Formatting ──
    function formatCurrency(amount, currency) {
        currency = currency || 'USD';
        try {
            return new Intl.NumberFormat('en-US', { style: 'currency', currency: currency }).format(amount);
        } catch (e) {
            return '$' + Number(amount).toFixed(2);
        }
    }

    // ── Debounce ──
    function debounce(fn, ms) {
        let timer;
        return function (...args) {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, args), ms);
        };
    }

    // ── Throttle ──
    function throttle(fn, ms) {
        let last = 0;
        return function (...args) {
            const now = Date.now();
            if (now - last >= ms) {
                last = now;
                return fn.apply(this, args);
            }
        };
    }

    // ── Copy to Clipboard ──
    function copyToClipboard(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text);
        }
        // Fallback for older browsers
        return new Promise((resolve, reject) => {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.cssText = 'position:fixed;left:-9999px;opacity:0';
            document.body.appendChild(ta);
            ta.select();
            try {
                document.execCommand('copy');
                resolve();
            } catch (e) {
                reject(e);
            } finally {
                document.body.removeChild(ta);
            }
        });
    }

    // ── Query Shorthand ──
    function $(sel, ctx) { return (ctx || document).querySelector(sel); }
    function $$(sel, ctx) { return [...(ctx || document).querySelectorAll(sel)]; }

    // ── CSRF-aware Fetch ──
    function apiFetch(url, opts) {
        opts = opts || {};
        var method = (opts.method || 'GET').toUpperCase();
        if (method !== 'GET' && method !== 'HEAD' && method !== 'OPTIONS') {
            opts.headers = opts.headers || {};
            if (!(opts.headers instanceof Headers)) {
                opts.headers['X-CSRF-Token'] = opts.headers['X-CSRF-Token']
                    || window.AW_CSRF_TOKEN || '';
            }
        }
        if (!opts.credentials) opts.credentials = 'same-origin';
        return fetch(url, opts);
    }

    return {
        esc,
        timeAgo,
        formatNumber,
        formatBytes,
        formatCurrency,
        debounce,
        throttle,
        copyToClipboard,
        $,
        $$,
        fetch: apiFetch
    };
})();

if (typeof module !== 'undefined' && module.exports) {
    module.exports = GDS;
} else {
    window.GDS = GDS;
}
