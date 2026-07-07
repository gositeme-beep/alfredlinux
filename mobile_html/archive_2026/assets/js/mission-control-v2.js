/**
 * Mission Control v2 Engine — Fleet API Integration
 * Connects Mission Control to the 5,000-agent fleet via api/agent-fleet-v2.php
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

    // ═══ Fleet Overview (Agents Panel) ═══
    async function loadFleet() {
        try {
            const [overview, capacity] = await Promise.all([
                v2fetch('overview'),
                v2fetch('capacity')
            ]);

            // Stats
            const ov = overview.data || overview;
            const total = ov.total_agents || 0;
            const roles = ov.by_role || {};
            const statuses = ov.by_status || {};
            const available = (statuses.active || 0) + (statuses.idle || 0);
            const busy = statuses.busy || 0;
            const domains = ov.by_domain ? Object.keys(ov.by_domain).length : 0;
            const successRate = ov.avg_success_rate || 0;
            const totalTasks = ov.total_tasks_completed || 0;

            setText('fv2-total', total.toLocaleString());
            setText('fv2-available', available.toLocaleString());
            setText('fv2-busy', busy.toLocaleString());
            setText('fv2-domains', domains);
            setText('fv2-success', successRate + '%');
            setText('fv2-tasks', totalTasks.toLocaleString());
            setText('fv2-directors', (roles.director || 10).toLocaleString());
            setText('fv2-specialists', ((roles.specialist || 0)).toLocaleString());

            // Also update overview panel agent count
            const overviewStat = document.getElementById('stat-agents');
            if (overviewStat) overviewStat.textContent = total.toLocaleString();

            // Domain breakdown
            renderDomains(ov.by_domain || {}, capacity);

        } catch (e) {
            console.error('Fleet load error:', e);
        }
    }

    function renderDomains(byDomain, capacity) {
        const el = document.getElementById('fleet-v2-domains');
        if (!el) return;

        const capData = (capacity.data || capacity);
        const capDomains = capData.domains || {};

        el.innerHTML = Object.entries(byDomain).map(([domain, count]) => {
            const dc = DOMAIN_COLORS[domain] || { color: '#6366f1', icon: 'fa-folder' };
            const cap = capDomains[domain] || {};
            const avail = cap.available || 0;
            const busyCount = cap.busy || 0;
            const pct = count > 0 ? Math.round(avail / count * 100) : 0;
            const barColor = pct >= 80 ? 'var(--mc-green)' : pct >= 50 ? 'var(--mc-amber)' : 'var(--mc-red)';

            return `<div class="mc-fleet-domain" onclick="MCV2.filterDomain('${esc(domain)}')">
                <div class="domain-name">
                    <i class="fas ${dc.icon}" style="color:${dc.color};"></i>
                    ${esc(domain)}
                    <span style="margin-left:auto;font-size:13px;font-weight:800;color:${dc.color};">${count}</span>
                </div>
                <div class="domain-bar"><div class="domain-fill" style="width:${pct}%;background:${barColor};"></div></div>
                <div class="domain-stats">
                    <span style="color:var(--mc-green);"><i class="fas fa-check"></i> ${avail} avail</span>
                    <span style="color:var(--mc-amber);"><i class="fas fa-spinner"></i> ${busyCount} busy</span>
                    <span>${pct}% capacity</span>
                </div>
            </div>`;
        }).join('');
    }

    function filterDomain(domain) {
        const sel = document.getElementById('fleet-v2-domain-filter');
        if (sel) sel.value = domain;
        searchFleetV2();
    }

    // ═══ Agent Search ═══
    async function searchFleetV2() {
        const domain = document.getElementById('fleet-v2-domain-filter')?.value || '';
        const role = document.getElementById('fleet-v2-role-filter')?.value || '';
        const search = document.getElementById('fleet-v2-search')?.value || '';
        const tableEl = document.getElementById('fleet-v2-table');
        if (!tableEl) return;

        tableEl.innerHTML = '<div style="text-align:center;padding:20px;color:var(--mc-muted);"><i class="fas fa-spinner fa-spin"></i> Loading agents...</div>';

        try {
            const data = await v2fetch('capacity', { domain: domain });
            const capData = data.data || data;

            // If searching within a domain, show agents
            if (domain) {
                const domainData = capData.domains ? capData.domains[domain] : capData;
                const agents = domainData?.agents || [];
                const filtered = agents.filter(a => {
                    if (role && a.agent_role !== role) return false;
                    if (search && !a.agent_name?.toLowerCase().includes(search.toLowerCase()) && !a.agent_id?.toLowerCase().includes(search.toLowerCase())) return false;
                    return true;
                });

                if (filtered.length > 0) {
                    tableEl.innerHTML = `<table class="mc-table"><thead><tr>
                        <th>Agent</th><th>Role</th><th>Domain</th><th>Status</th><th>Success</th><th>Tasks</th><th>Last Active</th>
                    </tr></thead><tbody>` + filtered.slice(0, 100).map(a => {
                        const sc = a.status === 'active' ? 'green' : a.status === 'idle' ? 'amber' : a.status === 'busy' ? 'cyan' : 'red';
                        return `<tr>
                            <td style="font-weight:600;font-size:12px;">${esc(a.agent_name || a.agent_id)}</td>
                            <td><span class="mc-badge ${a.agent_role === 'commander' ? 'amber' : a.agent_role === 'director' ? 'purple' : 'blue'}">${esc(a.agent_role || '-')}</span></td>
                            <td><span class="mc-badge cyan">${esc(a.domain || '-')}</span></td>
                            <td><span class="mc-badge ${sc}">${esc(a.status || '-')}</span></td>
                            <td>${a.success_rate ? a.success_rate + '%' : '-'}</td>
                            <td>${(a.tasks_completed || 0).toLocaleString()}</td>
                            <td style="font-size:11px;color:var(--mc-muted);">${a.last_active || '-'}</td>
                        </tr>`;
                    }).join('') + '</tbody></table>' +
                    (filtered.length > 100 ? `<div style="text-align:center;color:var(--mc-muted);padding:8px;font-size:12px;">Showing 100 of ${filtered.length} agents</div>` : '');
                } else {
                    tableEl.innerHTML = '<div style="text-align:center;color:var(--mc-muted);padding:20px;">No agents match filters.</div>';
                }
            } else {
                // Show all-domain summary
                const domains = capData.domains || {};
                const allAgentsHtml = Object.entries(domains).map(([d, info]) => {
                    const dc = DOMAIN_COLORS[d] || { color: '#6366f1' };
                    return `<div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid rgba(255,255,255,0.04);">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <i class="fas ${(DOMAIN_COLORS[d]||{}).icon||'fa-folder'}" style="color:${dc.color};"></i>
                            <span style="font-weight:600;text-transform:capitalize;">${esc(d)}</span>
                        </div>
                        <div style="display:flex;gap:16px;font-size:12px;color:var(--mc-muted);">
                            <span>${info.total || 0} total</span>
                            <span style="color:var(--mc-green);">${info.available || 0} avail</span>
                            <span style="color:var(--mc-amber);">${info.busy || 0} busy</span>
                        </div>
                        <button class="mc-btn mc-btn-info" style="padding:4px 10px;font-size:11px;" onclick="MCV2.filterDomain('${esc(d)}')"><i class="fas fa-eye"></i> View</button>
                    </div>`;
                }).join('');
                tableEl.innerHTML = allAgentsHtml || '<div style="text-align:center;color:var(--mc-muted);padding:20px;">No domain data available.</div>';
            }
        } catch (e) {
            tableEl.innerHTML = '<div style="color:var(--mc-red);text-align:center;padding:20px;">Error loading agents: ' + esc(e.message) + '</div>';
        }
    }

    // ═══ Fleet Ops ═══
    async function loadFleetOps() {
        try {
            const metricsData = await v2fetch('metrics', { metric_name: 'task_complete' });
            const md = metricsData.data || metricsData;
            const agg = md.aggregated || {};

            setText('fops-avg-success', (agg.avg_value ? parseFloat(agg.avg_value).toFixed(1) + '%' : '—'));
            setText('fops-total-tasks', (agg.total_records || 0).toLocaleString());
            setText('fops-avg-time', (agg.avg_value ? parseFloat(agg.avg_value).toFixed(1) + 's' : '—'));
            setText('fops-error-rate', (agg.min_value ? parseFloat(agg.min_value).toFixed(1) + '%' : '0%'));
        } catch (e) {
            console.error('Fleet ops metrics error:', e);
        }
    }

    // ═══ Route Task ═══
    async function routeFleetTask() {
        const domain = document.getElementById('fops-route-domain')?.value;
        const priority = document.getElementById('fops-route-priority')?.value;
        const desc = document.getElementById('fops-route-desc')?.value;
        const resultEl = document.getElementById('fops-route-result');

        if (!desc) { resultEl.innerHTML = '<span style="color:var(--mc-amber);">Please enter a task description.</span>'; return; }

        resultEl.innerHTML = '<i class="fas fa-spinner fa-spin" style="color:var(--mc-blue);"></i> Routing...';

        try {
            const data = await v2fetch('route_task', {}, 'POST', { domain, priority, task_description: desc });
            const result = data.data || data;
            if (result.assigned_agent) {
                resultEl.innerHTML = `<div style="padding:12px;background:rgba(16,185,129,0.1);border-radius:8px;border:1px solid rgba(16,185,129,0.2);">
                    <div style="color:var(--mc-green);font-weight:700;margin-bottom:4px;"><i class="fas fa-check-circle"></i> Task Routed</div>
                    <div style="font-size:12px;color:var(--mc-muted);">Assigned to <strong style="color:var(--mc-text);">${esc(result.assigned_agent.agent_name || result.assigned_agent.agent_id)}</strong>
                    (${esc(result.assigned_agent.domain)}) — Success Rate: ${result.assigned_agent.success_rate || '?'}%</div>
                </div>`;
                document.getElementById('fops-route-desc').value = '';
            } else {
                resultEl.innerHTML = '<span style="color:var(--mc-amber);">' + esc(data.message || 'No agent available') + '</span>';
            }
        } catch (e) {
            resultEl.innerHTML = '<span style="color:var(--mc-red);">Error: ' + esc(e.message) + '</span>';
        }
    }

    // ═══ Broadcast ═══
    async function sendFleetBroadcast() {
        const domain = document.getElementById('fops-broadcast-domain')?.value;
        const type = document.getElementById('fops-broadcast-type')?.value || 'broadcast';
        const msg = document.getElementById('fops-broadcast-msg')?.value;
        const resultEl = document.getElementById('fops-broadcast-result');

        if (!msg) { resultEl.innerHTML = '<span style="color:var(--mc-amber);">Please enter a message.</span>'; return; }

        resultEl.innerHTML = '<i class="fas fa-spinner fa-spin" style="color:var(--mc-blue);"></i> Sending...';

        try {
            const body = { message_type: type, payload: { message: msg, from: 'mission-control' } };
            let data;
            if (domain) {
                body.domain = domain;
                data = await v2fetch('broadcast', {}, 'POST', body);
            } else {
                data = await v2fetch('broadcast', {}, 'POST', body);
            }
            const result = data.data || data;
            resultEl.innerHTML = `<div style="padding:10px;background:rgba(99,102,241,0.1);border-radius:8px;border:1px solid rgba(99,102,241,0.15);">
                <span style="color:var(--mc-blue);font-weight:600;"><i class="fas fa-check"></i> Broadcast sent</span>
                <span style="font-size:12px;color:var(--mc-muted);margin-left:8px;">${result.recipients_count || '?'} recipients${domain ? ' in ' + esc(domain) : ''}</span>
            </div>`;
            document.getElementById('fops-broadcast-msg').value = '';
        } catch (e) {
            resultEl.innerHTML = '<span style="color:var(--mc-red);">Error: ' + esc(e.message) + '</span>';
        }
    }

    // ═══ Inbox ═══
    async function loadFleetInbox() {
        const agentId = document.getElementById('fops-inbox-agent')?.value;
        const el = document.getElementById('fops-inbox');
        if (!agentId) { el.innerHTML = '<div style="color:var(--mc-amber);text-align:center;padding:12px;">Enter an agent ID first.</div>'; return; }

        el.innerHTML = '<div style="text-align:center;padding:20px;"><i class="fas fa-spinner fa-spin" style="color:var(--mc-blue);"></i> Loading...</div>';

        try {
            const data = await v2fetch('msg_inbox', { agent_id: agentId });
            const messages = (data.data || data).messages || [];

            if (messages.length === 0) {
                el.innerHTML = '<div style="text-align:center;color:var(--mc-muted);padding:20px;"><i class="fas fa-inbox"></i> No messages for ' + esc(agentId) + '</div>';
                return;
            }

            el.innerHTML = messages.map(m => {
                const typeColors = { task:'blue', result:'green', alert:'red', broadcast:'purple', coordination:'cyan', status:'amber', heartbeat:'green', query:'blue' };
                const tc = typeColors[m.message_type] || 'blue';
                return `<div style="padding:12px 16px;background:rgba(255,255,255,0.02);border-radius:8px;margin-bottom:8px;border:1px solid rgba(255,255,255,0.04);">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                        <div style="display:flex;gap:6px;align-items:center;">
                            <span class="mc-badge ${tc}">${esc(m.message_type)}</span>
                            <span style="font-size:12px;font-weight:600;">From: ${esc(m.from_agent_id || 'system')}</span>
                        </div>
                        <span style="font-size:11px;color:var(--mc-muted);">${esc(m.created_at || '')}</span>
                    </div>
                    <div style="font-size:12px;color:var(--mc-muted);font-family:'JetBrains Mono',monospace;word-break:break-word;">
                        ${esc(typeof m.payload === 'string' ? m.payload : JSON.stringify(m.payload || {}).substring(0, 200))}
                    </div>
                    ${m.acknowledged ? '<span style="font-size:10px;color:var(--mc-green);"><i class="fas fa-check-double"></i> Acknowledged</span>' : 
                    `<button class="mc-btn mc-btn-info" style="margin-top:6px;padding:3px 10px;font-size:11px;" onclick="MCV2.ackMessage(${m.id})"><i class="fas fa-check"></i> Ack</button>`}
                </div>`;
            }).join('');
        } catch (e) {
            el.innerHTML = '<div style="color:var(--mc-red);text-align:center;padding:12px;">Error: ' + esc(e.message) + '</div>';
        }
    }

    async function ackMessage(msgId) {
        try {
            await v2fetch('msg_ack', {}, 'POST', { message_ids: [msgId] });
            loadFleetInbox();
        } catch (e) {
            console.error('Ack error:', e);
        }
    }

    // ═══ Helpers ═══
    function setText(id, val) {
        const el = document.getElementById(id);
        if (el) el.textContent = val;
    }

    // ═══ Init ═══
    // Update overview agent count on page load
    v2fetch('overview').then(data => {
        const ov = data.data || data;
        setText('stat-agents', (ov.total_agents || 0).toLocaleString());
    }).catch(() => {});

    // ═══ Public API ═══
    window.MCV2 = {
        loadFleet,
        loadFleetOps,
        filterDomain,
        searchFleetV2,
        routeFleetTask,
        sendFleetBroadcast,
        loadFleetInbox,
        ackMessage
    };

    // Expose search function globally for onclick
    window.searchFleetV2 = searchFleetV2;
    window.routeFleetTask = routeFleetTask;
    window.sendFleetBroadcast = sendFleetBroadcast;
    window.loadFleetInbox = loadFleetInbox;
    window.loadFleetV2 = loadFleet;
})();
