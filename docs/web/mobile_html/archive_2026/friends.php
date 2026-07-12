<?php
$page_title = "Friend Finder — GoSiteMe";
$page_description = "Find and connect with friends on GoSiteMe. Discover people, send friend requests, and start chatting securely with Veil encryption.";
$page_canonical = "https://gositeme.com/friends";
include 'includes/site-header.inc.php';

// Auth check
$isLoggedIn = !empty($_SESSION['client_id']) || !empty($_SESSION['uid']);
$clientId = (int) ($_SESSION['client_id'] ?? $_SESSION['uid'] ?? 0);
?>

<style>
/* ── Friend Finder Layout ───────────────────────────────────────── */
.ff-page {
    max-width: 900px;
    margin: 0 auto;
    padding: 2rem 1.5rem 4rem;
    min-height: 70vh;
}

.ff-page h1 {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}
.ff-page h1 i { color: var(--alfred-primary, #6c5ce7); }
.ff-subtitle {
    color: var(--text-secondary, #888);
    margin-bottom: 2rem;
}

/* ── Search Bar ─────────────────────────────────────────────────── */
.ff-search-wrap {
    position: relative;
    margin-bottom: 2rem;
}
.ff-search-wrap i.search-icon {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-secondary, #888);
    font-size: 1.1rem;
}
.ff-search {
    width: 100%;
    padding: 14px 14px 14px 48px;
    border-radius: 12px;
    border: 1px solid var(--border, #333);
    background: var(--surface-1, #12121a);
    color: var(--text-primary, #fff);
    font-size: 1rem;
    box-sizing: border-box;
    transition: border-color 0.2s;
}
.ff-search:focus {
    outline: none;
    border-color: var(--alfred-primary, #6c5ce7);
}
.ff-search::placeholder { color: var(--text-secondary, #666); }

/* ── Results Grid ───────────────────────────────────────────────── */
.ff-results {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 1rem;
}
.ff-empty {
    text-align: center;
    color: var(--text-secondary, #888);
    padding: 3rem 1rem;
    grid-column: 1 / -1;
}
.ff-empty i { font-size: 3rem; margin-bottom: 1rem; display: block; color: var(--alfred-primary, #6c5ce7); opacity: 0.5; }

/* ── User Card ──────────────────────────────────────────────────── */
.ff-card {
    background: var(--surface-1, #12121a);
    border: 1px solid var(--border, #333);
    border-radius: 16px;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    transition: border-color 0.2s, transform 0.2s;
}
.ff-card:hover {
    border-color: var(--alfred-primary, #6c5ce7);
    transform: translateY(-2px);
}
.ff-avatar {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--alfred-primary, #6c5ce7), #a855f7);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 0.75rem;
}
.ff-name {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}
.ff-status {
    font-size: 0.8rem;
    color: var(--text-secondary, #888);
    margin-bottom: 1rem;
}
.ff-status.has-veil {
    color: var(--green, #22c55e);
}
.ff-actions {
    display: flex;
    gap: 0.5rem;
    width: 100%;
}
.ff-btn {
    flex: 1;
    padding: 10px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    font-weight: 600;
    font-size: 0.85rem;
    transition: opacity 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}
.ff-btn:hover { opacity: 0.85; }
.ff-btn-primary {
    background: var(--alfred-primary, #6c5ce7);
    color: #fff;
}
.ff-btn-secondary {
    background: var(--surface-2, #1e1e2e);
    color: var(--text-primary, #fff);
    border: 1px solid var(--border, #333);
}
.ff-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* ── My Contacts Section ────────────────────────────────────────── */
.ff-contacts-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
    margin-top: 2.5rem;
}
.ff-contacts-header h2 { font-size: 1.3rem; }
.ff-badge {
    background: var(--alfred-primary, #6c5ce7);
    color: #fff;
    font-size: 0.75rem;
    padding: 2px 10px;
    border-radius: 99px;
    font-weight: 600;
}

/* ── Login prompt ───────────────────────────────────────────────── */
.ff-login-prompt {
    text-align: center;
    padding: 4rem 1.5rem;
}
.ff-login-prompt i { font-size: 4rem; color: var(--alfred-primary, #6c5ce7); margin-bottom: 1.5rem; }
.ff-login-prompt h2 { margin-bottom: 0.75rem; }
.ff-login-prompt p { color: var(--text-secondary, #888); margin-bottom: 1.5rem; }
.ff-login-btn {
    display: inline-block;
    padding: 14px 32px;
    background: var(--alfred-primary, #6c5ce7);
    color: #fff;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 600;
    font-size: 1rem;
}

/* ── Toast ──────────────────────────────────────────────────────── */
.ff-toast {
    position: fixed;
    bottom: 2rem;
    left: 50%;
    transform: translateX(-50%) translateY(100px);
    padding: 12px 24px;
    border-radius: 12px;
    font-weight: 600;
    z-index: 9999;
    transition: transform 0.3s ease;
    font-size: 0.9rem;
}
.ff-toast.show { transform: translateX(-50%) translateY(0); }
.ff-toast.success { background: var(--green, #22c55e); color: #fff; }
.ff-toast.error { background: var(--danger, #ef4444); color: #fff; }

@media (max-width: 768px) {
    .ff-page { padding: 1.5rem 1rem 3rem; }
    .ff-page h1 { font-size: 1.5rem; }
    .ff-results { grid-template-columns: 1fr; }
}
</style>

<?php if (!$isLoggedIn): ?>
<div class="ff-page">
    <div class="ff-login-prompt">
        <i class="fas fa-user-friends"></i>
        <h2>Find Your Friends on GoSiteMe</h2>
        <p>Sign in to search for friends, add contacts, and start chatting with end-to-end encryption.</p>
        <a href="/login?redirect=friends" class="ff-login-btn"><i class="fas fa-sign-in-alt"></i> Sign In</a>
    </div>
</div>
<?php else: ?>
<div class="ff-page">
    <h1><i class="fas fa-user-friends"></i> Friend Finder</h1>
    <p class="ff-subtitle">Search by name or email to find friends on GoSiteMe and start chatting securely.</p>

    <div class="ff-search-wrap">
        <i class="fas fa-search search-icon"></i>
        <input type="text" class="ff-search" id="ff-search" placeholder="Search by name or email address..." autocomplete="off" autofocus>
    </div>

    <div class="ff-results" id="ff-results">
        <div class="ff-empty">
            <i class="fas fa-search"></i>
            <p>Type a name or email to find people</p>
        </div>
    </div>

    <!-- My Contacts -->
    <div class="ff-contacts-header">
        <h2><i class="fas fa-address-book"></i> My Contacts</h2>
        <span class="ff-badge" id="ff-contact-count">0</span>
    </div>
    <div class="ff-results" id="ff-contacts"></div>
</div>

<div class="ff-toast" id="ff-toast"></div>

<script>
(function() {
    const CSRF = '<?php echo htmlspecialchars($_SESSION['comms_csrf'] ?? '', ENT_QUOTES, 'UTF-8'); ?>';
    let csrfToken = CSRF;
    let searchTimer = null;
    let myContacts = [];

    // ── API helper ──────────────────────────────────────────────
    async function api(action, opts = {}) {
        const method = opts.method || 'GET';
        const url = '/api/comms.php?action=' + action + (opts.query || '');
        const headers = { 'Content-Type': 'application/json' };
        if (method === 'POST') headers['X-CSRF-Token'] = csrfToken;

        const res = await fetch(url, {
            method,
            headers,
            credentials: 'same-origin',
            body: opts.body ? JSON.stringify(opts.body) : undefined,
        });
        const data = await res.json();
        if (data.csrf_token) csrfToken = data.csrf_token;
        if (!res.ok) throw new Error(data.error || 'Request failed');
        return data;
    }

    // ── Toast helper ────────────────────────────────────────────
    function toast(msg, type = 'success') {
        const el = document.getElementById('ff-toast');
        el.textContent = msg;
        el.className = 'ff-toast ' + type + ' show';
        setTimeout(() => el.classList.remove('show'), 3000);
    }

    // ── Initials from name ──────────────────────────────────────
    function initials(name) {
        return name.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
    }

    function escapeHtml(s) {
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    // ── Search ──────────────────────────────────────────────────
    document.getElementById('ff-search').addEventListener('input', (e) => {
        clearTimeout(searchTimer);
        const q = e.target.value.trim();
        if (q.length < 2) {
            document.getElementById('ff-results').innerHTML = '<div class="ff-empty"><i class="fas fa-search"></i><p>Type a name or email to find people</p></div>';
            return;
        }
        searchTimer = setTimeout(() => doSearch(q), 400);
    });

    async function doSearch(q) {
        const container = document.getElementById('ff-results');
        container.innerHTML = '<div class="ff-empty"><i class="fas fa-spinner fa-spin"></i><p>Searching...</p></div>';

        try {
            const data = await api('search', { query: '&q=' + encodeURIComponent(q) });
            if (!data.users || data.users.length === 0) {
                container.innerHTML = '<div class="ff-empty"><i class="fas fa-user-slash"></i><p>No users found for "' + escapeHtml(q) + '"</p></div>';
                return;
            }

            const contactIds = myContacts.map(c => c.contact_id);
            container.innerHTML = data.users.map(u => {
                const name = escapeHtml((u.firstname || '') + ' ' + (u.lastname || '')).trim();
                const isContact = contactIds.includes(u.id);
                return `
                    <div class="ff-card">
                        <div class="ff-avatar">${initials(name || '?')}</div>
                        <div class="ff-name">${name}</div>
                        <div class="ff-status ${u.has_comms ? 'has-veil' : ''}">
                            ${u.has_comms ? '<i class="fas fa-shield-halved"></i> Veil Encrypted' : '<i class="fas fa-user"></i> GoSiteMe Member'}
                        </div>
                        <div class="ff-actions">
                            ${isContact
                                ? `<a href="/veil/" class="ff-btn ff-btn-primary"><i class="fas fa-comment-dots"></i> Chat</a>`
                                : `<button class="ff-btn ff-btn-primary" onclick="window._ffAdd(${u.id})" id="ff-add-${u.id}"><i class="fas fa-user-plus"></i> Add</button>`
                            }
                            ${u.has_comms && !isContact ? `<a href="/veil/" class="ff-btn ff-btn-secondary"><i class="fas fa-lock"></i> Veil</a>` : ''}
                        </div>
                    </div>`;
            }).join('');
        } catch (err) {
            container.innerHTML = '<div class="ff-empty"><i class="fas fa-exclamation-triangle"></i><p>' + escapeHtml(err.message) + '</p></div>';
        }
    }

    // ── Add Contact ─────────────────────────────────────────────
    window._ffAdd = async function(userId) {
        const btn = document.getElementById('ff-add-' + userId);
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'; }
        try {
            await api('add_contact', { method: 'POST', body: { contact_id: userId } });
            toast('Contact added! Open Veil to start chatting.', 'success');
            if (btn) btn.innerHTML = '<i class="fas fa-check"></i> Added';
            loadContacts();
        } catch (err) {
            toast(err.message, 'error');
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-user-plus"></i> Add'; }
        }
    };

    // ── Load My Contacts ────────────────────────────────────────
    async function loadContacts() {
        try {
            const data = await api('contacts');
            myContacts = data.contacts || [];
            document.getElementById('ff-contact-count').textContent = myContacts.length;

            const container = document.getElementById('ff-contacts');
            if (myContacts.length === 0) {
                container.innerHTML = '<div class="ff-empty"><i class="fas fa-address-book"></i><p>No contacts yet — search above to find friends!</p></div>';
                return;
            }

            container.innerHTML = myContacts.map(c => {
                const name = escapeHtml((c.firstname || '') + ' ' + (c.lastname || '')).trim() || 'Unknown';
                return `
                    <div class="ff-card">
                        <div class="ff-avatar">${initials(name)}</div>
                        <div class="ff-name">${name}</div>
                        <div class="ff-status has-veil"><i class="fas fa-shield-halved"></i> Connected</div>
                        <div class="ff-actions">
                            <a href="/veil/" class="ff-btn ff-btn-primary"><i class="fas fa-comment-dots"></i> Chat</a>
                        </div>
                    </div>`;
            }).join('');
        } catch (err) {
            document.getElementById('ff-contacts').innerHTML = '<div class="ff-empty"><p>Could not load contacts</p></div>';
        }
    }

    loadContacts();
})();
</script>
<?php endif; ?>

<?php include 'includes/site-footer.inc.php'; ?>
