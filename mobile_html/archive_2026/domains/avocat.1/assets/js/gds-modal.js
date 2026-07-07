/**
 * GoSiteMe Modal & Confirm Dialog v1.0
 * ──────────────────────────────────────
 * Lightweight modal system using GDS component CSS classes.
 *
 * Usage — Modal:
 *   const m = GDSModal.open({ title: 'Edit User', body: '<form>...</form>' });
 *   GDSModal.open({ title: 'Delete?', body: 'Are you sure?', footer: '<button class="gds-btn gds-btn-danger">Delete</button>' });
 *   GDSModal.close(m);
 *   GDSModal.closeAll();
 *
 * Usage — Confirm:
 *   const ok = await GDSModal.confirm('Delete this item?');
 *   const ok = await GDSModal.confirm('Reset all data?', { confirmText: 'Reset', danger: true });
 *
 * Requires: /assets/css/components.css (gds-modal classes)
 */

'use strict';

const GDSModal = (() => {
    const stack = [];

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function open(opts = {}) {
        const backdrop = document.createElement('div');
        backdrop.className = 'gds-modal-backdrop';
        backdrop.setAttribute('role', 'dialog');
        backdrop.setAttribute('aria-modal', 'true');
        if (opts.title) backdrop.setAttribute('aria-label', opts.title);

        const modal = document.createElement('div');
        modal.className = 'gds-modal';

        // Header
        if (opts.title) {
            const header = document.createElement('div');
            header.className = 'gds-modal-header';
            header.innerHTML =
                `<h3 class="gds-modal-title">${escapeHtml(opts.title)}</h3>` +
                `<button class="gds-modal-close" aria-label="Close">&times;</button>`;
            header.querySelector('.gds-modal-close').addEventListener('click', () => close(backdrop));
            modal.appendChild(header);
        }

        // Body
        const body = document.createElement('div');
        body.className = 'gds-modal-body';
        if (typeof opts.body === 'string') {
            body.innerHTML = opts.body;
        } else if (opts.body instanceof HTMLElement) {
            body.appendChild(opts.body);
        }
        modal.appendChild(body);

        // Footer
        if (opts.footer) {
            const footer = document.createElement('div');
            footer.className = 'gds-modal-footer';
            if (typeof opts.footer === 'string') {
                footer.innerHTML = opts.footer;
            } else if (opts.footer instanceof HTMLElement) {
                footer.appendChild(opts.footer);
            }
            modal.appendChild(footer);
        }

        backdrop.appendChild(modal);

        // Click-outside to close
        backdrop.addEventListener('click', (e) => {
            if (e.target === backdrop && opts.dismissible !== false) close(backdrop);
        });

        // Escape key
        const onKey = (e) => {
            if (e.key === 'Escape' && opts.dismissible !== false) {
                close(backdrop);
                document.removeEventListener('keydown', onKey);
            }
        };
        document.addEventListener('keydown', onKey);
        backdrop._onKey = onKey;

        document.body.appendChild(backdrop);
        requestAnimationFrame(() => backdrop.classList.add('active'));

        stack.push(backdrop);

        if (opts.onOpen) opts.onOpen(modal, backdrop);

        return backdrop;
    }

    function close(backdrop) {
        if (!backdrop || !backdrop.parentNode) return;
        backdrop.classList.remove('active');
        if (backdrop._onKey) document.removeEventListener('keydown', backdrop._onKey);
        backdrop.addEventListener('transitionend', () => backdrop.remove(), { once: true });
        setTimeout(() => { if (backdrop.parentNode) backdrop.remove(); }, 400);
        const idx = stack.indexOf(backdrop);
        if (idx > -1) stack.splice(idx, 1);
    }

    function closeAll() {
        [...stack].forEach(close);
    }

    function confirm(message, opts = {}) {
        return new Promise((resolve) => {
            const confirmText = opts.confirmText || 'Confirm';
            const cancelText = opts.cancelText || 'Cancel';
            const danger = opts.danger ? ' gds-btn-danger' : ' gds-btn-primary';

            const footer = document.createElement('div');

            const cancelBtn = document.createElement('button');
            cancelBtn.className = 'gds-btn gds-btn-ghost';
            cancelBtn.textContent = cancelText;

            const confirmBtn = document.createElement('button');
            confirmBtn.className = 'gds-btn' + danger;
            confirmBtn.textContent = confirmText;

            footer.appendChild(cancelBtn);
            footer.appendChild(confirmBtn);

            const backdrop = open({
                title: opts.title || 'Confirm',
                body: `<p style="color:var(--gds-color-text-muted);font-size:var(--gds-font-md)">${escapeHtml(message)}</p>`,
                footer: footer,
                dismissible: false
            });

            cancelBtn.addEventListener('click', () => { close(backdrop); resolve(false); });
            confirmBtn.addEventListener('click', () => { close(backdrop); resolve(true); });
        });
    }

    return { open, close, closeAll, confirm };
})();

if (typeof module !== 'undefined' && module.exports) {
    module.exports = GDSModal;
} else {
    window.GDSModal = GDSModal;
}
