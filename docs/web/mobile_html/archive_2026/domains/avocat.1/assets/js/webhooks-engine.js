// ===================================================================
// Webhook Management JS
// ===================================================================
const API_BASE = '/api/webhooks.php';
let webhooksCache = [];

// Helpers
function showToast(msg, type = 'success') {
    if (window.GDSToast) return GDSToast.show(msg, { type: type === 'error' ? 'danger' : type });
}

async function apiFetch(action, opts = {}) {
    const method = opts.method || 'GET';
    const url = API_BASE + '?action=' + action + (opts.params || '');
    const fetchOpts = { method, credentials: 'same-origin', headers: {} };
    if (opts.body) {
        fetchOpts.headers['Content-Type'] = 'application/json';
        fetchOpts.body = JSON.stringify(opts.body);
    }
    const res = await fetch(url, fetchOpts);
    return res.json();
}

// ===== LOAD WEBHOOKS =====
async function loadWebhooks() {
    const loading = document.getElementById('webhooksLoading');
    const empty   = document.getElementById('webhooksEmpty');
    const table   = document.getElementById('webhooksTableWrap');

    if (loading) loading.style.display = 'block';
    if (empty) empty.style.display = 'none';
    if (table) table.style.display = 'none';

    try {
        const data = await apiFetch('list');
        webhooksCache = data.webhooks || [];

        if (loading) loading.style.display = 'none';

        if (webhooksCache.length === 0) {
            if (empty) empty.style.display = 'block';
        } else {
            if (table) table.style.display = 'block';
            renderWebhooks(webhooksCache);
        }
    } catch (e) {
        if (loading) loading.style.display = 'none';
        showToast('Failed to load webhooks', 'error');
    }
}

function renderWebhooks(webhooks) {
    const tbody = document.getElementById('webhooksTableBody');
    const count = document.getElementById('webhookCount');
    if (count) count.textContent = '(' + webhooks.length + ')';

    tbody.innerHTML = webhooks.map(wh => {
        const evtCount = Array.isArray(wh.events) ? wh.events.length : 0;
        const evtLabel = evtCount === 1 ? wh.events[0] : evtCount + ' events';
        const name = wh.name || new URL(wh.url).hostname;

        let statusClass = 'active', statusLabel = 'Active';
        if (!wh.is_active && wh.failure_count >= 10) {
            statusClass = 'disabled'; statusLabel = 'Disabled';
        } else if (!wh.is_active) {
            statusClass = 'paused'; statusLabel = 'Paused';
        }

        const lastTriggered = wh.last_triggered
            ? new Date(wh.last_triggered).toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
            : '—';

        return `<tr>
            <td>
                <div style="font-weight:600;margin-bottom:2px;">${escHtml(name)}</div>
                <div style="font-size:.78rem;color:var(--wh-text-muted);word-break:break-all;">${escHtml(wh.url)}</div>
            </td>
            <td><span class="wh-pill wh-pill--active" style="background:var(--wh-gradient-soft);color:var(--wh-accent-light);">${escHtml(evtLabel)}</span></td>
            <td><span class="wh-pill wh-pill--${statusClass}">${statusLabel}</span></td>
            <td style="font-size:.85rem;">${lastTriggered}</td>
            <td style="font-size:.85rem;${wh.failure_count > 0 ? 'color:var(--wh-orange);font-weight:600;' : ''}">${wh.failure_count}</td>
            <td>
                <div class="wh-actions">
                    <button class="wh-icon-btn" title="Test" onclick="testWebhook(${wh.id})"><i class="fas fa-paper-plane"></i></button>
                    <button class="wh-icon-btn" title="Deliveries" onclick="viewDeliveries(${wh.id}, '${escHtml(name)}')"><i class="fas fa-history"></i></button>
                    <button class="wh-icon-btn" title="Edit" onclick="editWebhook(${wh.id})"><i class="fas fa-pen"></i></button>
                    <button class="wh-icon-btn" title="${wh.is_active ? 'Pause' : 'Resume'}" onclick="toggleWebhook(${wh.id}, ${!wh.is_active})">
                        <i class="fas ${wh.is_active ? 'fa-pause' : 'fa-play'}"></i>
                    </button>
                    <button class="wh-icon-btn wh-icon-btn--danger" title="Delete" onclick="deleteWebhook(${wh.id})"><i class="fas fa-trash"></i></button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

function escHtml(s) {
    const d = document.createElement('div'); d.textContent = s; return d.innerHTML;
}

// ===== CREATE / EDIT MODAL =====
function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Create Webhook';
    document.getElementById('formWebhookId').value = '';
    document.getElementById('formName').value = '';
    document.getElementById('formUrl').value = '';
    document.getElementById('formSubmitBtn').querySelector('span').textContent = 'Create Webhook';
    document.getElementById('secretBox').classList.remove('active');
    document.getElementById('webhookForm').style.display = 'block';
    // Uncheck all
    document.querySelectorAll('#eventsGrid input[type="checkbox"]').forEach(cb => cb.checked = false);
    document.getElementById('webhookModal').classList.add('active');
}

function editWebhook(id) {
    const wh = webhooksCache.find(w => w.id === id);
    if (!wh) return;
    document.getElementById('modalTitle').textContent = 'Edit Webhook';
    document.getElementById('formWebhookId').value = id;
    document.getElementById('formName').value = wh.name || '';
    document.getElementById('formUrl').value = wh.url;
    document.getElementById('formSubmitBtn').querySelector('span').textContent = 'Update Webhook';
    document.getElementById('secretBox').classList.remove('active');
    document.getElementById('webhookForm').style.display = 'block';
    // Set checkboxes
    document.querySelectorAll('#eventsGrid input[type="checkbox"]').forEach(cb => {
        cb.checked = (wh.events || []).includes(cb.value);
    });
    document.getElementById('webhookModal').classList.add('active');
}

function closeModal() {
    document.getElementById('webhookModal').classList.remove('active');
}

async function handleFormSubmit(e) {
    e.preventDefault();
    const id  = document.getElementById('formWebhookId').value;
    const name = document.getElementById('formName').value.trim();
    const url  = document.getElementById('formUrl').value.trim();
    const events = Array.from(document.querySelectorAll('#eventsGrid input[type="checkbox"]:checked')).map(cb => cb.value);

    if (!url.startsWith('https://')) {
        showToast('URL must use HTTPS', 'error');
        return false;
    }
    if (events.length === 0) {
        showToast('Select at least one event', 'error');
        return false;
    }

    const btn = document.getElementById('formSubmitBtn');
    const origText = btn.querySelector('span').textContent;
    btn.querySelector('span').textContent = 'Saving...';
    btn.disabled = true;

    try {
        if (id) {
            // Update
            const data = await apiFetch('update', { method: 'POST', body: { id: parseInt(id), url, events, name: name || null } });
            if (data.error) { showToast(data.error, 'error'); return false; }
            showToast('Webhook updated');
            closeModal();
            loadWebhooks();
        } else {
            // Create
            const data = await apiFetch('create', { method: 'POST', body: { url, events, name: name || null } });
            if (data.error) { showToast(data.error, 'error'); return false; }
            // Show secret
            document.getElementById('webhookForm').style.display = 'none';
            document.getElementById('secretValue').textContent = data.webhook.secret;
            document.getElementById('secretBox').classList.add('active');
            showToast('Webhook created');
            loadWebhooks();
        }
    } catch (err) {
        showToast('Request failed', 'error');
    } finally {
        btn.querySelector('span').textContent = origText;
        btn.disabled = false;
    }
    return false;
}

function copySecret() {
    const sec = document.getElementById('secretValue').textContent;
    navigator.clipboard.writeText(sec).then(() => showToast('Secret copied to clipboard'));
}

// ===== TOGGLE / DELETE =====
async function toggleWebhook(id, newState) {
    try {
        const data = await apiFetch('update', { method: 'POST', body: { id, is_active: newState } });
        if (data.error) { showToast(data.error, 'error'); return; }
        showToast(newState ? 'Webhook enabled' : 'Webhook paused');
        loadWebhooks();
    } catch (e) { showToast('Failed', 'error'); }
}

async function deleteWebhook(id) {
    if (!confirm('Delete this webhook? This cannot be undone.')) return;
    try {
        const data = await apiFetch('delete', { method: 'POST', body: { id } });
        if (data.error) { showToast(data.error, 'error'); return; }
        showToast('Webhook deleted');
        loadWebhooks();
    } catch (e) { showToast('Failed', 'error'); }
}

async function testWebhook(id) {
    showToast('Sending test event...');
    try {
        const data = await apiFetch('test', { method: 'POST', body: { id } });
        if (data.success) {
            showToast('Test delivered — ' + data.status_code + ' (' + data.duration_ms + 'ms)');
        } else {
            showToast('Test failed — ' + (data.error || 'HTTP ' + data.status_code), 'error');
        }
    } catch (e) { showToast('Test request failed', 'error'); }
}

// ===== DELIVERIES MODAL =====
async function viewDeliveries(webhookId, name) {
    document.getElementById('deliveryWebhookName').textContent = name;
    document.getElementById('deliveriesContent').innerHTML = '<div style="text-align:center;padding:30px;"><div class="wh-spinner"></div></div>';
    document.getElementById('deliveriesModal').classList.add('active');

    try {
        const data = await apiFetch('deliveries', { params: '&webhook_id=' + webhookId + '&limit=30' });
        if (!data.deliveries || data.deliveries.length === 0) {
            document.getElementById('deliveriesContent').innerHTML = '<div class="wh-empty"><i class="fas fa-inbox"></i><h3>No deliveries yet</h3><p>Send a test event to see delivery logs here.</p></div>';
            return;
        }
        let html = '<div class="wh-table-wrap"><table class="wh-table wh-delivery-mini"><thead><tr><th>Event</th><th>Status</th><th>HTTP</th><th>Duration</th><th>Time</th></tr></thead><tbody>';
        data.deliveries.forEach(d => {
            const statusClass = d.status === 'success' ? 'success' : 'failed';
            const time = new Date(d.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
            html += `<tr>
                <td><code style="color:var(--wh-accent-light);font-size:.8rem;">${escHtml(d.event)}</code></td>
                <td><span class="wh-pill wh-pill--${statusClass}">${d.status}</span></td>
                <td style="font-weight:600;${d.response_code >= 200 && d.response_code < 300 ? 'color:var(--wh-green)' : 'color:var(--wh-red)'}">${d.response_code || '—'}</td>
                <td>${d.duration_ms}ms</td>
                <td style="font-size:.82rem;">${time}</td>
            </tr>`;
            if (d.error_message) {
                html += `<tr><td colspan="5" style="padding:4px 16px 12px;font-size:.8rem;color:var(--wh-red);">Error: ${escHtml(d.error_message)}</td></tr>`;
            }
        });
        html += '</tbody></table></div>';
        if (data.total > 30) {
            html += '<p style="text-align:center;color:var(--wh-text-muted);font-size:.82rem;margin-top:12px;">Showing 30 of ' + data.total + ' deliveries</p>';
        }
        document.getElementById('deliveriesContent').innerHTML = html;
    } catch (e) {
        document.getElementById('deliveriesContent').innerHTML = '<div class="wh-empty"><p style="color:var(--wh-red);">Failed to load deliveries.</p></div>';
    }
}

function closeDeliveriesModal() {
    document.getElementById('deliveriesModal').classList.remove('active');
}

// ===== EVENT CATEGORY SELECT ALL =====
function toggleCategoryEvents(btn, category) {
    const cbs = document.querySelectorAll(`#eventsGrid input[data-category="${category}"]`);
    const allChecked = Array.from(cbs).every(cb => cb.checked);
    cbs.forEach(cb => cb.checked = !allChecked);
    btn.textContent = allChecked ? 'Select All' : 'Deselect All';
}

// ===== ACCORDION =====
function toggleAccordion(header) {
    const item = header.closest('.wh-acc-item');
    item.classList.toggle('open');
}

// ===== TABS =====
function switchTab(btn, panelId) {
    btn.closest('.wh-card').querySelectorAll('.wh-tab').forEach(t => t.classList.remove('active'));
    btn.closest('.wh-card').querySelectorAll('.wh-tab-panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById(panelId).classList.add('active');
}

// ===== CLOSE MODALS ON OVERLAY CLICK =====
document.querySelectorAll('.wh-modal-overlay').forEach(ov => {
    ov.addEventListener('click', e => {
        if (e.target === ov) {
            ov.classList.remove('active');
        }
    });
});

// ===== INIT =====
if (window._webhooksLoggedIn) {
    document.addEventListener('DOMContentLoaded', loadWebhooks);
}
