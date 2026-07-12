const API = '/api/small-biz.php';
function fmt(n) { return n === null || n === undefined || isNaN(n) ? '--' : '$' + Number(n).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}); }

async function bGet(action, params = '') {
    try { const r = await fetch(`${API}?action=${action}${params}`, {credentials:'same-origin'}); return await r.json(); }
    catch(e) { console.warn('API error:', action, e); return {success:false}; }
}

async function loadDashboardKPIs() {
    const d = await bGet('biz_dashboard');
    if (d.success && d.dashboard) {
        const k = d.dashboard;
        document.getElementById('kpi-contacts').textContent = k.total_contacts || 0;
        document.getElementById('kpi-projects').textContent = k.active_projects || 0;
        document.getElementById('kpi-tasks').textContent = k.open_tasks || 0;
        document.getElementById('kpi-hours').textContent = Math.round(k.hours_this_month || 0);
        document.getElementById('kpi-invoices').textContent = k.unpaid_invoices || 0;
    }
}

async function loadContacts() {
    const d = await bGet('contacts_list', '&limit=8');
    const el = document.getElementById('contacts-list');
    if (d.success && d.contacts && d.contacts.length > 0) {
        el.innerHTML = `<table class="bz-table"><thead><tr><th>Name</th><th>Company</th><th>Type</th></tr></thead><tbody>${
            d.contacts.map(c => `<tr><td>${c.first_name || ''} ${c.last_name || ''}</td><td>${c.company || '--'}</td><td><span class="bz-status bz-s-active">${c.type || 'lead'}</span></td></tr>`).join('')
        }</tbody></table>`;
    } else { el.innerHTML = '<div style="color:var(--bz-muted);text-align:center;padding:1rem;">No contacts yet — add your first!</div>'; }
}

async function loadProjects() {
    const d = await bGet('projects_list', '&status=active&limit=6');
    const el = document.getElementById('projects-list');
    if (d.success && d.projects && d.projects.length > 0) {
        el.innerHTML = `<table class="bz-table"><thead><tr><th>Project</th><th>Status</th><th>Budget</th></tr></thead><tbody>${
            d.projects.map(p => `<tr><td>${p.name}</td><td><span class="bz-status bz-s-active">${p.status}</span></td><td>${fmt(p.budget)}</td></tr>`).join('')
        }</tbody></table>`;
    } else { el.innerHTML = '<div style="color:var(--bz-muted);text-align:center;padding:1rem;">No active projects</div>'; }
}

async function loadTasks() {
    const d = await bGet('tasks_list', '&status=open&limit=8');
    const el = document.getElementById('tasks-list');
    if (d.success && d.tasks && d.tasks.length > 0) {
        el.innerHTML = d.tasks.map(t => `<div style="display:flex;justify-content:space-between;align-items:center;padding:.5rem 0;border-bottom:1px solid rgba(255,255,255,.05);">
            <span>${t.title}</span>
            <span class="bz-status ${t.priority === 'high' ? 'bz-s-overdue' : 'bz-s-pending'}">${t.priority || 'medium'}</span>
        </div>`).join('');
    } else { el.innerHTML = '<div style="color:var(--bz-muted);text-align:center;padding:1rem;">All caught up!</div>'; }
}

async function loadTimeLog() {
    const d = await bGet('time_log', '&limit=8');
    const el = document.getElementById('time-log');
    if (d.success && d.entries && d.entries.length > 0) {
        el.innerHTML = `<table class="bz-table"><thead><tr><th>Description</th><th>Hours</th><th>Date</th></tr></thead><tbody>${
            d.entries.map(e => `<tr><td>${e.description || '--'}</td><td>${e.hours || 0}h</td><td style="color:var(--bz-muted)">${e.work_date || ''}</td></tr>`).join('')
        }</tbody></table>`;
    } else { el.innerHTML = '<div style="color:var(--bz-muted);text-align:center;padding:1rem;">No time entries yet</div>'; }
}

async function loadInvoices() {
    const d = await bGet('invoice_list', '&limit=8');
    const el = document.getElementById('invoices-list');
    if (d.success && d.invoices && d.invoices.length > 0) {
        el.innerHTML = `<table class="bz-table"><thead><tr><th>Invoice #</th><th>Client</th><th>Amount</th><th>Status</th><th>Due</th></tr></thead><tbody>${
            d.invoices.map(i => {
                const sc = i.status === 'paid' ? 'bz-s-paid' : i.status === 'overdue' ? 'bz-s-overdue' : 'bz-s-pending';
                return `<tr><td>${i.invoice_number || '--'}</td><td>${i.contact_name || '--'}</td><td>${fmt(i.total)}</td><td><span class="bz-status ${sc}">${i.status}</span></td><td style="color:var(--bz-muted)">${i.due_date || ''}</td></tr>`;
            }).join('')
        }</tbody></table>`;
    } else { el.innerHTML = '<div style="color:var(--bz-muted);text-align:center;padding:1rem;">No invoices yet</div>'; }
}

function showNewContact() {
    const modal = document.createElement('div');
    modal.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.7);display:flex;align-items:center;justify-content:center;z-index:9999;';
    modal.innerHTML = `<div style="background:var(--bz-card);border:1px solid var(--bz-border);border-radius:1rem;padding:2rem;max-width:500px;width:90%;">
        <h2 style="margin:0 0 1rem;">New Contact</h2>
        <form onsubmit="return createContact(this)">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin-bottom:.75rem;">
                <input name="first_name" placeholder="First name" required style="padding:.5rem;border-radius:.4rem;border:1px solid var(--bz-border);background:var(--bz-bg);color:var(--bz-text);">
                <input name="last_name" placeholder="Last name" style="padding:.5rem;border-radius:.4rem;border:1px solid var(--bz-border);background:var(--bz-bg);color:var(--bz-text);">
            </div>
            <input name="email" type="email" placeholder="Email" style="width:100%;padding:.5rem;border-radius:.4rem;border:1px solid var(--bz-border);background:var(--bz-bg);color:var(--bz-text);margin-bottom:.75rem;box-sizing:border-box;">
            <input name="company" placeholder="Company" style="width:100%;padding:.5rem;border-radius:.4rem;border:1px solid var(--bz-border);background:var(--bz-bg);color:var(--bz-text);margin-bottom:.75rem;box-sizing:border-box;">
            <div style="display:flex;gap:.75rem;justify-content:flex-end;">
                <button type="button" class="bz-btn" onclick="this.closest('div[style]').parentElement.remove()">Cancel</button>
                <button type="submit" class="bz-btn bz-btn-primary">Add Contact</button>
            </div>
        </form>
    </div>`;
    modal.addEventListener('click', e => { if (e.target === modal) modal.remove(); });
    document.body.appendChild(modal);
}

async function createContact(form) {
    const fd = new FormData(form);
    const body = Object.fromEntries(fd);
    try {
        const r = await fetch(`${API}?action=contact_create`, {method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/json'}, body:JSON.stringify(body)});
        const d = await r.json();
        if (d.success) { form.closest('div[style*="fixed"]').remove(); refreshAll(); }
        else { alert(d.error || 'Failed to create contact'); }
    } catch(e) { alert('Network error'); }
    return false;
}

function refreshAll() { loadDashboardKPIs(); loadContacts(); loadProjects(); loadTasks(); loadTimeLog(); loadInvoices(); }
refreshAll();
