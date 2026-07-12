/**
 * AgentOS v2 Engine — Fleet API Integration
 * Connects the AgentOS Dashboard to the 5,000-agent fleet via api/agent-fleet-v2.php
 */
(function() {
    'use strict';

    const API = '/api/agent-fleet-v2.php';

    const DOMAIN_COLORS = {
        engineering:     { color: '#6366f1', icon: 'fa-code' },
        security:        { color: '#ef4444', icon: 'fa-shield-alt' },
        research:        { color: '#22d3ee', icon: 'fa-flask' },
        finance:         { color: '#f59e0b', icon: 'fa-coins' },
        communications:  { color: '#a855f7', icon: 'fa-satellite-dish' },
        infrastructure:  { color: '#10b981', icon: 'fa-server' },
        marketing:       { color: '#ec4899', icon: 'fa-bullhorn' },
        analytics:       { color: '#3b82f6', icon: 'fa-chart-pie' },
        creative:        { color: '#fbbf24', icon: 'fa-paint-brush' },
        robotics:        { color: '#f97316', icon: 'fa-robot' }
    };

    function esc(s) { return typeof GDS !== 'undefined' ? GDS.esc(s) : String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[m]); }

    async function v2fetch(action, params = {}, method = 'GET', body = null) {
        let url = API + '?action=' + action;
        Object.entries(params).forEach(([k, v]) => { if (v) url += '&' + k + '=' + encodeURIComponent(v); });
        const opts = { method, credentials: 'same-origin', headers: {} };
        if (body) { opts.headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(body); }
        const resp = await fetch(url, opts);
        return resp.json();
    }

    function setText(id, val) {
        const el = document.getElementById(id);
        if (el) el.textContent = val;
    }

    // ═══ Fleet Overview ═══
    async function loadFleet() {
        try {
            const [overview, capacity] = await Promise.all([
                v2fetch('overview'),
                v2fetch('capacity')
            ]);

            const ov = overview.data || overview;
            const total = ov.total_agents || 0;
            const roles = ov.by_role || {};
            const statuses = ov.by_status || {};
            const available = (statuses.active || 0) + (statuses.idle || 0);
            const busy = statuses.busy || 0;
            const domains = ov.by_domain ? Object.keys(ov.by_domain).length : 0;

            setText('osf-total', total.toLocaleString());
            setText('osf-avail', available.toLocaleString());
            setText('osf-busy', busy.toLocaleString());
            setText('osf-domains', domains);
            setText('osf-success', (ov.avg_success_rate || 0) + '%');
            setText('osf-tasks', (ov.total_tasks_completed || 0).toLocaleString());
            setText('osf-directors', (roles.director || 10).toLocaleString());
            setText('osf-specialists', (roles.specialist || 0).toLocaleString());
            setText('fleet-count', total.toLocaleString());

            // Domain breakdown
            renderDomains(ov.by_domain || {}, capacity);
        } catch (e) {
            console.error('OSV2 fleet load error:', e);
        }
    }

    function renderDomains(byDomain, capacity) {
        const el = document.getElementById('os-fleet-domains');
        if (!el) return;

        const capData = (capacity.data || capacity);
        const capDomains = capData.domains || {};

        el.innerHTML = Object.entries(byDomain).map(([domain, count]) => {
            const dc = DOMAIN_COLORS[domain] || { color: '#6366f1', icon: 'fa-folder' };
            const cap = capDomains[domain] || {};
            const avail = cap.available || 0;
            const busyCount = cap.busy || 0;
            const pct = count > 0 ? Math.round(avail / count * 100) : 0;
            const barColor = pct >= 80 ? 'var(--os-green)' : pct >= 50 ? 'var(--os-amber)' : 'var(--os-red)';

            return `<div class="os-fleet-domain" onclick="OSV2.filterDomain('${esc(domain)}')">
                <div class="fd-name">
                    <i class="fas ${dc.icon}" style="color:${dc.color};"></i>
                    ${esc(domain)}
                    <span style="margin-left:auto;font-weight:800;color:${dc.color};">${count}</span>
                </div>
                <div class="fd-bar"><div class="fd-fill" style="width:${pct}%;background:${barColor};"></div></div>
                <div class="fd-stats">
                    <span style="color:var(--os-green);"><i class="fas fa-check"></i> ${avail}</span>
                    <span style="color:var(--os-amber);"><i class="fas fa-spinner"></i> ${busyCount}</span>
                    <span>${pct}%</span>
                </div>
            </div>`;
        }).join('');
    }

    function filterDomain(domain) {
        const sel = document.getElementById('osf-domain-filter');
        if (sel) sel.value = domain;
        searchAgents();
    }

    // ═══ Agent Search ═══
    async function searchAgents() {
        const domain = document.getElementById('osf-domain-filter')?.value || '';
        const search = document.getElementById('osf-search')?.value || '';
        const el = document.getElementById('os-fleet-agents');
        if (!el) return;

        el.innerHTML = '<div style="text-align:center;padding:20px;color:var(--os-muted);"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';

        try {
            const data = await v2fetch('capacity', { domain: domain || undefined });
            const capData = data.data || data;

            if (domain) {
                const domainData = capData.domains ? capData.domains[domain] : capData;
                const agents = domainData?.agents || [];
                const filtered = search ? agents.filter(a =>
                    (a.agent_name || '').toLowerCase().includes(search.toLowerCase()) ||
                    (a.agent_id || '').toLowerCase().includes(search.toLowerCase())
                ) : agents;

                if (filtered.length > 0) {
                    el.innerHTML = `<table class="os-table"><thead><tr>
                        <th>Agent</th><th>Role</th><th>Status</th><th>Success</th><th>Tasks</th>
                    </tr></thead><tbody>` + filtered.slice(0, 100).map(a => {
                        const st = a.status === 'active' ? 'online' : a.status === 'idle' ? 'pending' : a.status === 'busy' ? 'running' : 'offline';
                        return `<tr>
                            <td style="font-weight:600;font-size:12px;">${esc(a.agent_name || a.agent_id)}</td>
                            <td>${statusBadge(a.agent_role)}</td>
                            <td><span class="badge-pill ${st}">${esc(a.status || '-')}</span></td>
                            <td class="mono">${a.success_rate ? a.success_rate + '%' : '-'}</td>
                            <td class="mono">${(a.tasks_completed || 0).toLocaleString()}</td>
                        </tr>`;
                    }).join('') + '</tbody></table>' +
                    (filtered.length > 100 ? '<div style="text-align:center;padding:8px;font-size:12px;color:var(--os-muted);">Showing 100 of ' + filtered.length + '</div>' : '');
                } else {
                    el.innerHTML = '<div class="empty-state"><i class="fas fa-search"></i> No agents match.</div>';
                }
            } else {
                const domains = capData.domains || {};
                el.innerHTML = Object.entries(domains).map(([d, info]) => {
                    const dc = DOMAIN_COLORS[d] || { color: '#6366f1' };
                    return `<div style="display:flex;justify-content:space-between;align-items:center;padding:10px 16px;border-bottom:1px solid var(--os-border);">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <i class="fas ${(DOMAIN_COLORS[d]||{}).icon||'fa-folder'}" style="color:${dc.color};"></i>
                            <span style="font-weight:600;text-transform:capitalize;">${esc(d)}</span>
                        </div>
                        <div style="display:flex;gap:16px;font-size:12px;color:var(--os-muted);">
                            <span>${info.total || 0} total</span>
                            <span style="color:var(--os-green);">${info.available || 0} avail</span>
                        </div>
                        <button class="os-btn sm" onclick="OSV2.filterDomain('${esc(d)}')"><i class="fas fa-eye"></i></button>
                    </div>`;
                }).join('') || '<div class="empty-state">No data</div>';
            }
        } catch (e) {
            el.innerHTML = '<div style="color:var(--os-red);text-align:center;padding:20px;">Error: ' + esc(e.message) + '</div>';
        }
    }

    function statusBadge(s) { return '<span class="badge-pill ' + (s || '') + '">' + esc(s || '-') + '</span>'; }

    // ═══ Route Task ═══
    async function routeTask() {
        const domain = document.getElementById('osf-route-domain')?.value;
        const priority = document.getElementById('osf-route-priority')?.value;
        const desc = document.getElementById('osf-route-desc')?.value;
        const el = document.getElementById('osf-route-result');

        if (!desc) { el.innerHTML = '<span style="color:var(--os-amber);">Enter a task description.</span>'; return; }

        el.innerHTML = '<i class="fas fa-spinner fa-spin" style="color:var(--os-primary);"></i> Routing...';

        try {
            const data = await v2fetch('route_task', {}, 'POST', { domain, priority, task_description: desc });
            const result = data.data || data;
            if (result.assigned_agent) {
                el.innerHTML = `<div style="padding:10px;background:rgba(34,197,94,.1);border-radius:8px;border:1px solid rgba(34,197,94,.2);">
                    <span style="color:var(--os-green);font-weight:700;"><i class="fas fa-check-circle"></i> Routed to ${esc(result.assigned_agent.agent_name || result.assigned_agent.agent_id)}</span>
                    <span style="font-size:12px;color:var(--os-muted);margin-left:8px;">(${esc(result.assigned_agent.domain)} · ${result.assigned_agent.success_rate || '?'}%)</span>
                </div>`;
                document.getElementById('osf-route-desc').value = '';
            } else {
                el.innerHTML = '<span style="color:var(--os-amber);">' + esc(data.message || 'No agent available') + '</span>';
            }
        } catch (e) {
            el.innerHTML = '<span style="color:var(--os-red);">Error: ' + esc(e.message) + '</span>';
        }
    }

    // ═══ Public API ═══
    window.OSV2 = { loadFleet, filterDomain, searchAgents, routeTask };
})();
