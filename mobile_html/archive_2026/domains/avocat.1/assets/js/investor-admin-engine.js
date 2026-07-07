/**
 * GoSiteMe Investor Admin Engine v2.0
 * Extracted from investor-admin.php inline JS
 */
(function(){
'use strict';

/* ──────── State ──────── */
let allInvestors = [];
let filteredInvestors = [];
let stats = {};
let metrics = {};
let currentFilter = 'all';
let searchQuery = '';
let sortColumn = 'created_at';
let sortDirection = 'desc';
let currentPage = 1;
const PAGE_SIZE = 20;
let selectedIds = new Set();
let expandedIds = new Set();
let lastRefresh = null;

/* ──────── Init ──────── */
document.addEventListener('DOMContentLoaded', () => {
    initTabs();
    initFilters();
    initSorting();
    loadData();
    handleHashTab();
    window.addEventListener('hashchange', handleHashTab);
});

/* ──────── Tab System ──────── */
function initTabs() {
    document.querySelectorAll('.icc-tab').forEach(tab => {
        tab.addEventListener('click', () => switchTab(tab.dataset.tab));
    });
}

window.switchTab = function(tabName) {
    document.querySelectorAll('.icc-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.icc-tab-panel').forEach(p => p.classList.remove('active'));
    const tab = document.querySelector(`.icc-tab[data-tab="${tabName}"]`);
    const panel = document.getElementById(`panel-${tabName}`);
    if (tab) tab.classList.add('active');
    if (panel) panel.classList.add('active');
    window.location.hash = tabName;
};

function handleHashTab() {
    const hash = window.location.hash.replace('#', '');
    if (hash && document.getElementById(`panel-${hash}`)) {
        switchTab(hash);
    }
}

/* ──────── Filters ──────── */
function initFilters() {
    document.getElementById('statusFilters').addEventListener('click', e => {
        const btn = e.target.closest('.icc-filter');
        if (!btn) return;
        document.querySelectorAll('.icc-filter').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentFilter = btn.dataset.filter;
        currentPage = 1;
        applyFilters();
    });
}

/* ──────── Sorting ──────── */
function initSorting() {
    document.querySelectorAll('.icc-table th[data-sort]').forEach(th => {
        th.addEventListener('click', () => {
            const col = th.dataset.sort;
            if (sortColumn === col) {
                sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                sortColumn = col;
                sortDirection = 'asc';
            }
            document.querySelectorAll('.icc-table th').forEach(h => h.classList.remove('sorted'));
            th.classList.add('sorted');
            const icon = th.querySelector('.sort-icon');
            if (icon) icon.className = `fas fa-sort-${sortDirection === 'asc' ? 'up' : 'down'} sort-icon`;
            applyFilters();
        });
    });
}

/* ──────── Data Loading ──────── */
async function loadData() {
    try {
        const res = await fetch('/api/investor.php?action=admin');
        const data = await res.json();
        document.getElementById('loadingState').style.display = 'none';

        if (!data.success) {
            toast('Access denied: ' + (data.error || 'Unknown'), 'error');
            return;
        }

        allInvestors = data.investors || [];
        stats = data.stats || {};
        metrics = data.metrics || {};
        lastRefresh = new Date();

        document.getElementById('mainContent').style.display = 'block';
        document.getElementById('tabCountInvestors').textContent = allInvestors.length;
        const lr = document.getElementById('lastRefreshed');
        if (lr) lr.textContent = lastRefresh.toLocaleString();

        renderKPIs();
        renderFunnel();
        renderOverviewMetrics();
        renderActivityFeed();
        applyFilters();
        renderKanban();
        renderAnalytics();
    } catch (err) {
        document.getElementById('loadingState').innerHTML = `
            <i class="fas fa-exclamation-triangle" style="font-size:2.5rem;color:var(--inv-red);"></i>
            <p style="color:var(--inv-text-muted);margin-top:16px;">Failed to load data: ${err.message}</p>
            <button class="icc-btn primary" onclick="location.reload()" style="margin-top:12px;"><i class="fas fa-redo"></i> Retry</button>
        `;
    }
}

window.refreshData = function() {
    document.getElementById('loadingState').style.display = 'block';
    document.getElementById('loadingState').innerHTML = '<i class="fas fa-spinner fa-spin" style="font-size:2.5rem;background:var(--inv-gradient);-webkit-background-clip:text;-webkit-text-fill-color:transparent;"></i><p style="color:var(--inv-text-muted);margin-top:16px;">Refreshing data...</p>';
    document.getElementById('mainContent').style.display = 'none';
    loadData();
};

/* ──────── KPI Rendering ──────── */
function renderKPIs() {
    const avgInvestment = allInvestors.length > 0 ? (stats.total_pledged || 0) / allInvestors.length : 0;

    const kpis = [
        { icon: 'fas fa-users', cls: 'green', label: 'Total Submissions', value: stats.total || 0, sub: allInvestors.length + ' records' },
        { icon: 'fas fa-clock', cls: 'purple', label: 'Pending Review', value: stats.pending || 0, sub: 'awaiting action' },
        { icon: 'fas fa-check-double', cls: 'green', label: 'Funded Count', value: stats.funded || 0, sub: 'investments closed' },
        { icon: 'fas fa-dollar-sign', cls: 'gold', label: 'Total Raised', value: '$' + fmtNum(stats.total_raised || 0), sub: 'from funded investors' },
        { icon: 'fas fa-calculator', cls: 'blue', label: 'Average Investment', value: '$' + fmtNum(avgInvestment), sub: 'per submission' },
        { icon: 'fas fa-coins', cls: 'orange', label: 'Total Pledged', value: '$' + fmtNum(stats.total_pledged || 0), sub: 'across all tiers' },
        { icon: 'fas fa-chart-line', cls: 'cyan', label: 'MRR', value: '$' + fmtNum(metrics.mrr || 0), sub: (metrics.active_services || 0) + ' active services' },
        { icon: 'fas fa-user-check', cls: 'blue', label: 'Active Users', value: fmtNum(metrics.active_users || 0), sub: fmtNum(metrics.total_php_files || 0) + ' files in codebase' },
    ];

    document.getElementById('kpiGrid').innerHTML = kpis.map(k => `
        <div class="icc-kpi">
            <div class="icc-kpi-icon ${k.cls}"><i class="${k.icon}"></i></div>
            <div class="icc-kpi-label">${k.label}</div>
            <div class="icc-kpi-value">${k.value}</div>
            <div class="icc-kpi-sub">${k.sub}</div>
        </div>
    `).join('');
}

/* ──────── Funnel ──────── */
function renderFunnel() {
    const total = stats.total || 1;
    const steps = [
        { label: 'Pending', count: stats.pending || 0, icon: 'fa-clock' },
        { label: 'Contacted', count: stats.contacted || 0, icon: 'fa-phone' },
        { label: 'Approved', count: stats.approved || 0, icon: 'fa-thumbs-up' },
        { label: 'Funded', count: stats.funded || 0, icon: 'fa-money-bill-wave' },
        { label: 'Declined', count: stats.declined || 0, icon: 'fa-times-circle' },
    ];

    document.getElementById('funnelVis').innerHTML = steps.map((s, i) => `
        ${i > 0 ? '<div class="icc-funnel-arrow"><i class="fas fa-chevron-down"></i></div>' : ''}
        <div class="icc-funnel-step">
            <span class="funnel-label"><i class="fas ${s.icon}"></i>&ensp;${s.label}</span>
            <span class="funnel-count">${s.count}</span>
            <span class="funnel-pct">${((s.count / total) * 100).toFixed(1)}%</span>
        </div>
    `).join('');
}

/* ──────── Overview Metrics ──────── */
function renderOverviewMetrics() {
    const items = [
        { l: 'AI Tools', v: metrics.total_tools },
        { l: 'API Endpoints', v: metrics.api_endpoints },
        { l: 'Use Cases', v: metrics.use_case_pages },
        { l: 'Articles', v: metrics.articles },
        { l: 'Compare Pages', v: metrics.compare_pages },
        { l: 'Industry Verticals', v: metrics.industry_verticals },
        { l: 'Voice Tools', v: metrics.voice_tools },
        { l: 'SDKs', v: metrics.sdks },
        { l: 'Pricing Tiers', v: metrics.pricing_tiers },
        { l: 'Codebase', v: (metrics.codebase_mb || 0) + ' MB' },
    ];

    document.getElementById('overviewMetrics').innerHTML = items.map(m => `
        <div style="background:var(--inv-surface-2);border-radius:var(--inv-radius-xs);padding:12px;text-align:center;">
            <div style="font-size:.68rem;color:var(--inv-text-muted);text-transform:uppercase;letter-spacing:.5px;">${m.l}</div>
            <div style="font-family:'Space Grotesk',sans-serif;font-size:1.2rem;font-weight:700;color:var(--inv-accent-light);">${m.v ?? '—'}</div>
        </div>
    `).join('');
}

/* ──────── Activity Feed ──────── */
function renderActivityFeed() {
    const recent = [...allInvestors].sort((a, b) => new Date(b.created_at) - new Date(a.created_at)).slice(0, 8);
    const icons = { pending: 'purple', contacted: 'blue', approved: 'gold', funded: 'green' };
    const verbs = { pending: 'submitted interest', contacted: 'was contacted', approved: 'was approved', funded: 'investment funded' };

    document.getElementById('activityFeed').innerHTML = recent.length ? recent.map(inv => `
        <li>
            <span class="act-icon ${icons[inv.status] || 'blue'}"><i class="fas fa-${inv.status === 'funded' ? 'check' : inv.status === 'pending' ? 'plus' : 'arrow-right'}"></i></span>
            <div>
                <div class="act-text"><strong>${esc(inv.name)}</strong> ${verbs[inv.status] || inv.status} &mdash; <span class="amt">$${fmtNum(inv.amount)}</span></div>
                <div class="act-time">${timeAgo(inv.updated_at || inv.created_at)}</div>
            </div>
        </li>
    `).join('') : '<li><span class="act-text" style="color:var(--inv-text-muted)">No recent activity</span></li>';
}

/* ──────── Apply Filters + Search ──────── */
function applyFilters() {
    let data = [...allInvestors];

    // status filter
    if (currentFilter !== 'all') {
        data = data.filter(i => i.status === currentFilter);
    }

    // search
    if (searchQuery) {
        const q = searchQuery.toLowerCase();
        data = data.filter(i =>
            (i.name || '').toLowerCase().includes(q) ||
            (i.email || '').toLowerCase().includes(q) ||
            (i.ref_code || '').toLowerCase().includes(q) ||
            (i.tier || '').toLowerCase().includes(q) ||
            (i.phone || '').toLowerCase().includes(q)
        );
    }

    // sort
    data.sort((a, b) => {
        let va = a[sortColumn] ?? '';
        let vb = b[sortColumn] ?? '';
        if (sortColumn === 'amount') { va = parseFloat(va); vb = parseFloat(vb); }
        else if (sortColumn === 'created_at') { va = new Date(va); vb = new Date(vb); }
        else { va = String(va).toLowerCase(); vb = String(vb).toLowerCase(); }
        if (va < vb) return sortDirection === 'asc' ? -1 : 1;
        if (va > vb) return sortDirection === 'asc' ? 1 : -1;
        return 0;
    });

    filteredInvestors = data;
    renderTable();
    renderPagination();
}

window.handleSearch = function() {
    searchQuery = document.getElementById('investorSearch').value.trim();
    currentPage = 1;
    applyFilters();
};

/* ──────── Table Rendering ──────── */
function renderTable() {
    const start = (currentPage - 1) * PAGE_SIZE;
    const pageData = filteredInvestors.slice(start, start + PAGE_SIZE);
    const tbody = document.getElementById('investorRows');
    const noRes = document.getElementById('noResults');

    if (filteredInvestors.length === 0) {
        tbody.innerHTML = '';
        noRes.style.display = 'block';
        return;
    }
    noRes.style.display = 'none';

    let html = '';
    pageData.forEach(inv => {
        const checked = selectedIds.has(inv.id) ? 'checked' : '';
        const expanded = expandedIds.has(inv.id);
        const parentClass = expanded ? ' expanded-parent' : '';

        html += `<tr data-id="${inv.id}" class="${parentClass}">
            <td class="checkbox-col"><input type="checkbox" value="${inv.id}" ${checked} onchange="toggleSelect(${inv.id})"></td>
            <td><code style="color:var(--inv-accent-light);font-size:.76rem;">${inv.ref_code}</code></td>
            <td><strong style="color:#fff;cursor:pointer;" onclick="toggleExpand(${inv.id})">${esc(inv.name)}</strong></td>
            <td style="font-size:.82rem;">${esc(inv.email)}</td>
            <td style="font-size:.82rem;">${esc(inv.phone || '—')}</td>
            <td><span class="tier-badge ${inv.tier}">${inv.tier}</span></td>
            <td class="amt">$${fmtNum(inv.amount)}</td>
            <td><span class="st-badge ${inv.status}"><i class="fas fa-circle" style="font-size:.35rem;"></i> ${inv.status}</span></td>
            <td style="font-size:.76rem;color:var(--inv-text-muted);">${new Date(inv.created_at).toLocaleDateString()}</td>
            <td>
                <div style="display:flex;gap:4px;flex-wrap:nowrap;">
                    <button class="icc-btn icc-btn-sm blue" onclick="editInvestor(${inv.id})" title="Edit / View"><i class="fas fa-pen"></i></button>
                    ${inv.status === 'pending' ? `<button class="icc-btn icc-btn-sm green" onclick="quickStatus(${inv.id},'contacted')" title="Mark Contacted"><i class="fas fa-phone"></i></button>` : ''}
                    ${inv.status === 'contacted' ? `<button class="icc-btn icc-btn-sm gold" onclick="quickStatus(${inv.id},'approved')" title="Approve"><i class="fas fa-check"></i></button>` : ''}
                    ${inv.status === 'approved' ? `<button class="icc-btn icc-btn-sm green" onclick="quickStatus(${inv.id},'funded')" title="Mark Funded"><i class="fas fa-money-bill"></i></button>` : ''}
                </div>
            </td>
        </tr>`;

        if (expanded) {
            html += `<tr class="expand-row"><td colspan="10">
                <div class="expand-details">
                    <div class="expand-detail"><div class="ed-label">Full Name</div><div class="ed-value">${esc(inv.name)}</div></div>
                    <div class="expand-detail"><div class="ed-label">Email</div><div class="ed-value">${esc(inv.email)}</div></div>
                    <div class="expand-detail"><div class="ed-label">Phone</div><div class="ed-value">${esc(inv.phone || '—')}</div></div>
                    <div class="expand-detail"><div class="ed-label">IP Address</div><div class="ed-value">${esc(inv.ip_address || '—')}</div></div>
                    <div class="expand-detail"><div class="ed-label">Message</div><div class="ed-value">${esc(inv.message || '—')}</div></div>
                    <div class="expand-detail"><div class="ed-label">Notes</div><div class="ed-value">${esc(inv.notes || '—')}</div></div>
                    <div class="expand-detail"><div class="ed-label">Created</div><div class="ed-value">${inv.created_at}</div></div>
                    <div class="expand-detail"><div class="ed-label">Updated</div><div class="ed-value">${inv.updated_at || '—'}</div></div>
                    <div class="expand-detail"><div class="ed-label">Funded At</div><div class="ed-value">${inv.funded_at || '—'}</div></div>
                </div>
            </td></tr>`;
        }
    });

    tbody.innerHTML = html;
    updateBulkBar();
}

/* ──────── Pagination ──────── */
function renderPagination() {
    const total = filteredInvestors.length;
    const pages = Math.ceil(total / PAGE_SIZE);
    const pag = document.getElementById('pagination');

    if (pages <= 1) { pag.innerHTML = ''; return; }

    let html = `<button ${currentPage === 1 ? 'disabled' : ''} onclick="goPage(${currentPage - 1})"><i class="fas fa-chevron-left"></i></button>`;
    const maxVisible = 7;
    let startP = Math.max(1, currentPage - Math.floor(maxVisible / 2));
    let endP = Math.min(pages, startP + maxVisible - 1);
    if (endP - startP < maxVisible - 1) startP = Math.max(1, endP - maxVisible + 1);

    if (startP > 1) { html += `<button onclick="goPage(1)">1</button>`; if (startP > 2) html += `<span class="page-info">…</span>`; }
    for (let i = startP; i <= endP; i++) {
        html += `<button class="${i === currentPage ? 'active' : ''}" onclick="goPage(${i})">${i}</button>`;
    }
    if (endP < pages) { if (endP < pages - 1) html += `<span class="page-info">…</span>`; html += `<button onclick="goPage(${pages})">${pages}</button>`; }

    html += `<button ${currentPage === pages ? 'disabled' : ''} onclick="goPage(${currentPage + 1})"><i class="fas fa-chevron-right"></i></button>`;
    html += `<span class="page-info">Showing ${(currentPage-1)*PAGE_SIZE+1}–${Math.min(currentPage*PAGE_SIZE, total)} of ${total}</span>`;
    pag.innerHTML = html;
}

window.goPage = function(p) {
    const pages = Math.ceil(filteredInvestors.length / PAGE_SIZE);
    if (p < 1 || p > pages) return;
    currentPage = p;
    renderTable();
    renderPagination();
    document.querySelector('.icc-table-wrap')?.scrollTo(0, 0);
};

/* ──────── Expand Row ──────── */
window.toggleExpand = function(id) {
    if (expandedIds.has(id)) expandedIds.delete(id);
    else expandedIds.add(id);
    renderTable();
};

/* ──────── Selection / Bulk ──────── */
window.toggleSelect = function(id) {
    if (selectedIds.has(id)) selectedIds.delete(id);
    else selectedIds.add(id);
    updateBulkBar();
};

window.toggleSelectAll = function() {
    const checked = document.getElementById('selectAll').checked;
    const start = (currentPage - 1) * PAGE_SIZE;
    const pageData = filteredInvestors.slice(start, start + PAGE_SIZE);
    pageData.forEach(inv => { if (checked) selectedIds.add(inv.id); else selectedIds.delete(inv.id); });
    renderTable();
};

function updateBulkBar() {
    const bar = document.getElementById('bulkBar');
    const cnt = document.getElementById('bulkCount');
    if (selectedIds.size > 0) {
        bar.style.display = 'flex';
        cnt.textContent = selectedIds.size + ' selected';
    } else {
        bar.style.display = 'none';
    }
}

window.applyBulkAction = async function() {
    const action = document.getElementById('bulkAction').value;
    if (!action || selectedIds.size === 0) { toast('Select investors and an action', 'error'); return; }
    if (!confirm(`Update ${selectedIds.size} investor(s) to "${action}"?`)) return;

    let success = 0, fail = 0;
    for (const id of selectedIds) {
        try {
            const res = await fetch('/api/investor.php?action=admin_update', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, status: action })
            });
            const data = await res.json();
            if (data.success) success++; else fail++;
        } catch { fail++; }
    }
    toast(`Bulk update: ${success} updated, ${fail} failed`, success > 0 ? 'success' : 'error');
    selectedIds.clear();
    document.getElementById('bulkAction').value = '';
    loadData();
};

/* ──────── Kanban Board ──────── */
function renderKanban() {
    const statuses = ['pending', 'contacted', 'approved', 'funded', 'declined'];
    const labels = { pending: 'Pending', contacted: 'Contacted', approved: 'Approved', funded: 'Funded', declined: 'Declined' };

    const board = document.getElementById('kanbanBoard');
    board.innerHTML = statuses.map(status => {
        const items = allInvestors.filter(i => i.status === status);
        return `<div class="icc-kanban-col ${status}">
            <div class="icc-kanban-col-header">
                <span>${labels[status]}</span>
                <span class="col-count">${items.length}</span>
            </div>
            ${items.length ? items.map(inv => `
                <div class="icc-kanban-card" onclick="editInvestor(${inv.id})">
                    <div class="card-name">${esc(inv.name)}</div>
                    <div class="card-amount">$${fmtNum(inv.amount)}</div>
                    <div class="card-meta">
                        <span class="tier-badge ${inv.tier}">${inv.tier}</span>
                        <span>${new Date(inv.created_at).toLocaleDateString()}</span>
                    </div>
                </div>
            `).join('') : '<div style="text-align:center;color:var(--inv-text-dim);font-size:.82rem;padding:20px;">No investors</div>'}
        </div>`;
    }).join('');
}

/* ──────── Analytics Charts ──────── */
function renderAnalytics() {
    // Tier Distribution
    const tiers = { seed: { label: 'Seed', total: 0, count: 0, cls: 'purple' }, growth: { label: 'Growth', total: 0, count: 0, cls: 'green' }, strategic: { label: 'Strategic', total: 0, count: 0, cls: 'gold' } };
    allInvestors.forEach(i => { if (tiers[i.tier]) { tiers[i.tier].total += parseFloat(i.amount); tiers[i.tier].count++; } });
    const maxTier = Math.max(...Object.values(tiers).map(t => t.total), 1);

    document.getElementById('tierChart').innerHTML = Object.values(tiers).map(t => `
        <div class="icc-bar">
            <div class="icc-bar-value">$${fmtNum(t.total)}</div>
            <div class="icc-bar-fill ${t.cls}" style="height:${Math.max((t.total / maxTier) * 160, 8)}px;"></div>
            <div class="icc-bar-label">${t.label}<br><small style="color:var(--inv-text-dim)">${t.count} investors</small></div>
        </div>
    `).join('');

    // Monthly submissions
    const months = {};
    allInvestors.forEach(i => {
        const d = new Date(i.created_at);
        const key = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
        months[key] = (months[key] || 0) + 1;
    });
    const sortedMonths = Object.entries(months).sort((a, b) => a[0].localeCompare(b[0])).slice(-8);
    const maxMonth = Math.max(...sortedMonths.map(m => m[1]), 1);

    document.getElementById('monthlyChart').innerHTML = sortedMonths.map(([month, count]) => `
        <div class="icc-bar">
            <div class="icc-bar-value">${count}</div>
            <div class="icc-bar-fill gradient" style="height:${Math.max((count / maxMonth) * 160, 8)}px;"></div>
            <div class="icc-bar-label">${month.slice(5)}<br><small style="color:var(--inv-text-dim)">${month.slice(0, 4)}</small></div>
        </div>
    `).join('');

    // Platform Health
    const platformItems = [
        { label: 'Tools', value: metrics.total_tools || 0, cls: 'green' },
        { label: 'APIs', value: metrics.api_endpoints || 0, cls: 'blue' },
        { label: 'Use Cases', value: metrics.use_case_pages || 0, cls: 'purple' },
        { label: 'Articles', value: metrics.articles || 0, cls: 'gold' },
        { label: 'Voice', value: metrics.voice_tools || 0, cls: 'green' },
        { label: 'Compares', value: metrics.compare_pages || 0, cls: 'blue' },
    ];
    const maxP = Math.max(...platformItems.map(p => p.value), 1);

    document.getElementById('platformChart').innerHTML = platformItems.map(p => `
        <div class="icc-bar">
            <div class="icc-bar-value">${p.value}</div>
            <div class="icc-bar-fill ${p.cls}" style="height:${Math.max((p.value / maxP) * 140, 6)}px;"></div>
            <div class="icc-bar-label">${p.label}</div>
        </div>
    `).join('');

    // Financial Projections
    const mrr = metrics.mrr || 0;
    const shareRates = { seed: 0.5, growth: 1.0, strategic: 2.0 };
    const growthFactors = [
        { label: 'Current', factor: 1, icon: 'fa-equals' },
        { label: '2x Growth', factor: 2, icon: 'fa-arrow-up' },
        { label: '5x Growth', factor: 5, icon: 'fa-rocket' },
        { label: '10x Growth', factor: 10, icon: 'fa-fire' },
    ];

    document.getElementById('projectionsGrid').innerHTML = growthFactors.map(g => {
        const proj = mrr * g.factor;
        return `<div style="background:var(--inv-surface-2);border:1px solid var(--inv-border);border-radius:var(--inv-radius-sm);padding:20px;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
                <i class="fas ${g.icon}" style="color:var(--inv-accent-light);"></i>
                <span style="font-weight:700;color:#fff;">${g.label}</span>
            </div>
            <div style="font-family:'Space Grotesk',sans-serif;font-size:1.6rem;font-weight:800;background:var(--inv-gradient);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">$${fmtNum(proj)}/mo</div>
            <div style="margin-top:10px;font-size:.78rem;color:var(--inv-text-muted);">
                <div>Seed share: $${fmtNum(proj * 0.005)}/mo</div>
                <div>Growth share: $${fmtNum(proj * 0.01)}/mo</div>
                <div>Strategic share: $${fmtNum(proj * 0.02)}/mo</div>
            </div>
        </div>`;
    }).join('');
}

/* ──────── Edit Modal ──────── */
window.editInvestor = function(id) {
    const inv = allInvestors.find(i => i.id === id || i.id === String(id));
    if (!inv) { toast('Investor not found', 'error'); return; }

    document.getElementById('editId').value = inv.id;
    document.getElementById('editRef').value = inv.ref_code;
    document.getElementById('editDate').value = inv.created_at;
    document.getElementById('editName').value = inv.name;
    document.getElementById('editEmail').value = inv.email;
    document.getElementById('editPhone').value = inv.phone || '';
    document.getElementById('editIP').value = inv.ip_address || '';
    document.getElementById('editTier').value = inv.tier;
    document.getElementById('editAmount').value = '$' + fmtNum(inv.amount);
    document.getElementById('editMessage').value = inv.message || '';
    document.getElementById('editStatus').value = inv.status;
    document.getElementById('editNotes').value = inv.notes || '';
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-edit"></i> ' + esc(inv.ref_code) + ' &mdash; ' + esc(inv.name);

    // Note history
    const noteHist = document.getElementById('noteHistory');
    if (inv.notes) {
        noteHist.style.display = 'block';
        noteHist.innerHTML = '<strong style="color:var(--inv-text);">Previous Notes:</strong><br>' + esc(inv.notes);
    } else {
        noteHist.style.display = 'none';
    }

    checkStatusWarning();
    document.getElementById('editModal').classList.add('open');
};

document.getElementById('editStatus')?.addEventListener('change', checkStatusWarning);
function checkStatusWarning() {
    const status = document.getElementById('editStatus').value;
    const warn = document.getElementById('statusWarning');
    warn.style.display = (status === 'funded' || status === 'declined' || status === 'approved') ? 'block' : 'none';
}

window.closeModal = function() {
    document.getElementById('editModal').classList.remove('open');
};

window.saveInvestor = async function(e) {
    if (e) e.preventDefault();
    const id = document.getElementById('editId').value;
    const status = document.getElementById('editStatus').value;
    const notes = document.getElementById('editNotes').value;

    // Confirmation for sensitive statuses
    if (status === 'funded' && !confirm('Confirm marking this investor as FUNDED? This will send them a notification email.')) return false;
    if (status === 'declined' && !confirm('Confirm DECLINING this investor? This will send them a notification email.')) return false;

    try {
        const res = await fetch('/api/investor.php?action=admin_update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, status, notes })
        });
        const data = await res.json();
        if (data.success) {
            toast('Investor updated successfully', 'success');
            closeModal();
            loadData();
        } else {
            toast(data.error || 'Update failed', 'error');
        }
    } catch (err) {
        toast('Network error: ' + err.message, 'error');
    }
    return false;
};

window.quickStatus = async function(id, newStatus) {
    if (newStatus === 'funded' && !confirm('Confirm marking as FUNDED?')) return;
    try {
        const res = await fetch('/api/investor.php?action=admin_update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, status: newStatus })
        });
        const data = await res.json();
        if (data.success) {
            toast(`Status → ${newStatus}`, 'success');
            loadData();
        } else {
            toast(data.error || 'Update failed', 'error');
        }
    } catch (err) {
        toast('Network error', 'error');
    }
};

/* ──────── Send Custom Email ──────── */
window.sendCustomEmail = function() {
    const email = document.getElementById('editEmail').value;
    const name = document.getElementById('editName').value;
    if (!email) { toast('No email found', 'error'); return; }
    const subject = encodeURIComponent('GoSiteMe Investment Update');
    const body = encodeURIComponent(`Hi ${name},\n\nThank you for your interest in GoSiteMe.\n\n[Your message here]\n\nBest regards,\nGoSiteMe Investment Team\n1-833-GOSITEME`);
    window.open(`mailto:${email}?subject=${subject}&body=${body}`, '_blank');
    toast('Opening mail client...', 'info');
};

window.emailAllInvestors = function() {
    const emails = allInvestors.map(i => i.email).filter(Boolean);
    if (emails.length === 0) { toast('No investors found', 'error'); return; }
    const subject = encodeURIComponent('GoSiteMe — Investor Update');
    const body = encodeURIComponent('Dear Investors,\n\n[Your update here]\n\nBest regards,\nGoSiteMe Investment Team');
    window.open(`mailto:?bcc=${emails.join(',')}&subject=${subject}&body=${body}`, '_blank');
    toast(`Opening mail client for ${emails.length} investors...`, 'info');
};

/* ──────── CSV Export ──────── */
window.exportAllCSV = function() { downloadCSV(allInvestors, 'gositeme-investors-all'); };
window.exportFilteredCSV = function() { downloadCSV(filteredInvestors, 'gositeme-investors-filtered'); };

function downloadCSV(data, filename) {
    if (!data.length) { toast('No data to export', 'error'); return; }
    const headers = ['ID', 'Ref Code', 'Name', 'Email', 'Phone', 'Tier', 'Amount', 'Status', 'Message', 'IP', 'Notes', 'Created', 'Updated', 'Funded At'];
    const rows = data.map(i => [
        i.id, i.ref_code, `"${(i.name||'').replace(/"/g,'""')}"`, i.email, i.phone || '',
        i.tier, i.amount, i.status, `"${(i.message||'').replace(/"/g,'""')}"`,
        i.ip_address || '', `"${(i.notes||'').replace(/"/g,'""')}"`,
        i.created_at, i.updated_at || '', i.funded_at || ''
    ]);
    const csv = [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = `${filename}-${new Date().toISOString().slice(0, 10)}.csv`;
    a.click(); URL.revokeObjectURL(url);
    toast(`Exported ${data.length} records`, 'success');
}

window.exportJSON = function() {
    const blob = new Blob([JSON.stringify({ investors: allInvestors, stats, metrics, exported: new Date().toISOString() }, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = `gositeme-investors-${new Date().toISOString().slice(0, 10)}.json`;
    a.click(); URL.revokeObjectURL(url);
    toast('JSON exported', 'success');
};

/* ──────── Toast ──────── */
window.toast = function(msg, type = 'success') {
    const t = document.getElementById('toast');
    const icons = { success: 'fa-check-circle', error: 'fa-exclamation-circle', info: 'fa-info-circle' };
    t.innerHTML = `<i class="fas ${icons[type] || icons.success}"></i> ${msg}`;
    t.className = 'icc-toast ' + type + ' show';
    clearTimeout(t._timer);
    t._timer = setTimeout(() => t.classList.remove('show'), 3500);
};

/* ──────── Helpers ──────── */
function esc(s) { return GDS.esc(s); }
function fmtNum(n) { return parseFloat(n || 0).toLocaleString(undefined, { maximumFractionDigits: 2 }); }
function timeAgo(dateStr) { return GDS.timeAgo(dateStr); }

/* ──────── Modal Overlay Click ──────── */
document.getElementById('editModal')?.addEventListener('click', e => {
    if (e.target === e.currentTarget) closeModal();
});

/* ──────── Keyboard Shortcuts ──────── */
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeModal();
    if (e.key === '/' && !['INPUT','TEXTAREA','SELECT'].includes(document.activeElement.tagName)) {
        e.preventDefault();
        switchTab('investors');
        setTimeout(() => document.getElementById('investorSearch')?.focus(), 100);
    }
});

})();
