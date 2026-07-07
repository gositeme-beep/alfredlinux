/**
 * GoSiteMe Toast Notification System v1.0
 * ────────────────────────────────────────
 * Unified toast notifications using GDS component CSS classes.
 * Auto-creates the toast container on first use.
 *
 * Usage:
 *   GDSToast.success('Saved successfully');
 *   GDSToast.error('Something went wrong');
 *   GDSToast.warning('Approaching limit');
 *   GDSToast.info('New update available');
 *   GDSToast.show('Custom message', { type: 'success', duration: 5000, icon: 'fa-check' });
 *
 * Requires: /assets/css/components.css (gds-toast classes)
 */

'use strict';

const GDSToast = (() => {
    let container = null;
    const DEFAULTS = { duration: 4000, position: 'top-right' };

    const ICONS = {
        success: 'fa-circle-check',
        danger:  'fa-circle-xmark',
        warning: 'fa-triangle-exclamation',
        info:    'fa-circle-info'
    };

    function getContainer() {
        if (container && document.body.contains(container)) return container;
        container = document.createElement('div');
        container.className = 'gds-toast-container';
        container.setAttribute('role', 'status');
        container.setAttribute('aria-live', 'polite');
        document.body.appendChild(container);
        return container;
    }

    function show(message, opts = {}) {
        const type = opts.type || 'info';
        const duration = opts.duration ?? DEFAULTS.duration;
        const icon = opts.icon || ICONS[type] || ICONS.info;

        const el = document.createElement('div');
        el.className = `gds-toast gds-toast-${type}`;

        el.innerHTML =
            `<i class="fas ${icon}" style="font-size:1.1em"></i>` +
            `<span>${escapeHtml(message)}</span>` +
            `<button style="background:none;border:none;color:inherit;cursor:pointer;margin-left:auto;padding:2px 4px;opacity:0.6" aria-label="Close">` +
            `<i class="fas fa-times"></i></button>`;

        el.querySelector('button').addEventListener('click', () => dismiss(el));

        getContainer().appendChild(el);

        if (duration > 0) {
            setTimeout(() => dismiss(el), duration);
        }

        return el;
    }

    function dismiss(el) {
        if (!el || !el.parentNode) return;
        el.style.animation = 'gds-toast-out 0.25s ease forwards';
        el.addEventListener('animationend', () => el.remove(), { once: true });
        // Fallback removal
        setTimeout(() => { if (el.parentNode) el.remove(); }, 300);
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // Add the toast-out animation if not present
    if (typeof document !== 'undefined') {
        const style = document.createElement('style');
        style.textContent = '@keyframes gds-toast-out{from{transform:translateX(0);opacity:1}to{transform:translateX(100%);opacity:0}}';
        document.head.appendChild(style);
    }

    return {
        show,
        success: (msg, opts) => show(msg, { ...opts, type: 'success' }),
        error:   (msg, opts) => show(msg, { ...opts, type: 'danger' }),
        danger:  (msg, opts) => show(msg, { ...opts, type: 'danger' }),
        warning: (msg, opts) => show(msg, { ...opts, type: 'warning' }),
        info:    (msg, opts) => show(msg, { ...opts, type: 'info' }),
        dismiss
    };
})();

if (typeof module !== 'undefined' && module.exports) {
    module.exports = GDSToast;
} else {
    window.GDSToast = GDSToast;
}
