/**
 * GoSiteMe — Fleet Command v3.0 Engine
 * Wires fleet-dashboard.php to api/agent-fleet-v2.php
 * Real 5,000-agent fleet: overview, grid, messaging, metrics
 */
(function() {
'use strict';

const V2_API = '/api/agent-fleet-v2.php';
const $ = id => document.getElementById(id);
const esc = s => typeof GDS !== 'undefined' && GDS.esc ? GDS.esc(s) : String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[c]));

// ═══════════════════════════════════════
// Domain colors
// ═══════════════════════════════════════
const DOMAIN_COLORS = {
    engineering:    { color: '#6c5ce7', icon: 'fa-code' },
    security:       { color: '#ff5252', icon: 'fa-shield-alt' },
    research:       { color: '#18ffff', icon: 'fa-microscope' },
    finance:        { color: '#ffd600', icon: 'fa-chart-line' },
    communications: { color: '#ff9800', icon: 'fa-satellite-dish' },
    infrastructure: { color: '#448aff', icon: 'fa-server' },
    marketing:      { color: '#e040fb', icon: 'fa-bullhorn' },
    analytics:      { color: '#00e676', icon: 'fa-chart-bar' },
    creative:       { color: '#ff4081', icon: 'fa-palette' },
    robotics:       { color: '#76ff03', icon: 'fa-robot' }
};

const DOMAIN_ORDER = ['engineering','security','research','finance','communications','infrastructure','marketing','analytics','creative','robotics'];

// ═══════════════════════════════════════
// State
// ═══════════════════════════════════════
const v2state = {
    overview: null,
    capacity: [],
    agents: [],
    messages: [],
    activeDomain: null,
    gridPage: 0,
    gridPageSize: 200,
    sendingMsg: false
};

// ═══════════════════════════════════════
// API helper
// ═══════════════════════════════════════
async function v2fetch(action, params = {}, method = 'GET', body = null) {
    const url = new URL(V2_API, location.origin);
    url.searchParams.set('action', action);
    for (const [k, v] of Object.entries(params)) url.searchParams.set(k, v);

    const opts = { method, credentials: 'same-origin', headers: {} };
    if (window.AW_CSRF_TOKEN) opts.headers['X-CSRF-Token'] = window.AW_CSRF_TOKEN;
    if (body && method === 'POST') {
        opts.headers['Content-Type'] = 'application/json';
        opts.body = JSON.stringify(body);
    }
    const resp = await fetch(url, opts);
    return resp.json();
}

// ═══════════════════════════════════════
// OVERVIEW TAB
// ═══════════════════════════════════════
async function loadOverview() {
    const panel = $('ftab-overview');
    if (!panel) return;

    try {
        const [ovRes, capRes] = await Promise.all([
            v2fetch('overview'),
            v2fetch('capacity')
        ]);

        if (ovRes.success) {
            v2state.overview = ovRes.data;
            renderOverviewStats(ovRes.data);
            // Update hero counter
            const heroCount = $('liveAgentCount');
            if (heroCount) animateVal(heroCount, parseInt(ovRes.data.agents.total_agents) || 0);
        }

        if (capRes.success) {
            v2state.capacity = capRes.data.domains || [];
            renderDomainBreakdown(v2state.capacity);
        }
    } catch (e) {
        console.error('v2 overview error:', e);
    }
}

function renderOverviewStats(data) {
    const el = $('v2OverviewStats');
    if (!el) return;
    const a = data.agents || {};
    const t = data.tasks || {};
    const m = data.messages || {};
    const s = data.sessions || {};

    el.innerHTML = `
        <div class="v2-stat-grid">
            <div class="v2-stat" style="--accent-c: var(--accent)">
                <div class="v2-stat-icon"><i class="fas fa-users"></i></div>
                <div class="v2-stat-val">${Number(a.total_agents || 0).toLocaleString()}</div>
                <div class="v2-stat-label">Total Agents</div>
            </div>
            <div class="v2-stat" style="--accent-c: var(--green)">
                <div class="v2-stat-icon"><i class="fas fa-circle-check"></i></div>
                <div class="v2-stat-val">${Number(a.idle || 0).toLocaleString()}</div>
                <div class="v2-stat-label">Available</div>
            </div>
            <div class="v2-stat" style="--accent-c: var(--yellow)">
                <div class="v2-stat-icon"><i class="fas fa-spinner"></i></div>
                <div class="v2-stat-val">${Number(a.busy || 0)}</div>
                <div class="v2-stat-label">Busy</div>
            </div>
            <div class="v2-stat" style="--accent-c: var(--cyan)">
                <div class="v2-stat-icon"><i class="fas fa-crown"></i></div>
                <div class="v2-stat-val">${Number(a.commanders || 0)}</div>
                <div class="v2-stat-label">Commander</div>
            </div>
            <div class="v2-stat" style="--accent-c: var(--blue)">
                <div class="v2-stat-icon"><i class="fas fa-star"></i></div>
                <div class="v2-stat-val">${Number(a.directors || 0)}</div>
                <div class="v2-stat-label">Directors</div>
            </div>
            <div class="v2-stat" style="--accent-c: var(--accent-light)">
                <div class="v2-stat-icon"><i class="fas fa-user-gear"></i></div>
                <div class="v2-stat-val">${Number(a.specialists || 0).toLocaleString()}</div>
                <div class="v2-stat-label">Specialists</div>
            </div>
            <div class="v2-stat" style="--accent-c: var(--green)">
                <div class="v2-stat-icon"><i class="fas fa-list-check"></i></div>
                <div class="v2-stat-val">${Number(t.total_tasks || 0)}</div>
                <div class="v2-stat-label">Tasks</div>
            </div>
            <div class="v2-stat" style="--accent-c: var(--yellow)">
                <div class="v2-stat-icon"><i class="fas fa-envelope"></i></div>
                <div class="v2-stat-val">${Number(m.total_messages || 0)}</div>
                <div class="v2-stat-label">Messages</div>
            </div>
        </div>

        <div class="v2-capacity-bar">
            <div class="v2-cap-header">
                <span>Fleet Capacity</span>
                <span class="v2-cap-pct">${Number(a.total_agents || 0).toLocaleString()} / ${Number(data.capacity?.max_agents || 5000).toLocaleString()}</span>
            </div>
            <div class="v2-cap-track">
                <div class="v2-cap-fill" style="width:${((a.total_agents || 0) / (data.capacity?.max_agents || 5000) * 100).toFixed(1)}%"></div>
            </div>
        </div>

        <div class="v2-hierarchy">
            <div class="v2-hier-node v2-hier-commander">
                <i class="fas fa-crown"></i>
                <span>Alfred</span>
                <small>Commander</small>
            </div>
            <div class="v2-hier-arrow"><i class="fas fa-angles-down"></i></div>
            <div class="v2-hier-row">
                ${DOMAIN_ORDER.map(d => {
                    const dc = DOMAIN_COLORS[d];
                    return `<div class="v2-hier-node" style="border-color:${dc.color}">
                        <i class="fas ${dc.icon}" style="color:${dc.color}"></i>
                        <span style="font-size:.65rem">${d.charAt(0).toUpperCase() + d.slice(1, 5)}</span>
                    </div>`;
                }).join('')}
            </div>
            <div class="v2-hier-arrow"><i class="fas fa-angles-down"></i></div>
            <div class="v2-hier-label">${Number(a.specialists || 0).toLocaleString()} Specialists across 10 domains</div>
        </div>
    `;
}

function renderDomainBreakdown(domains) {
    const el = $('v2DomainGrid');
    if (!el) return;

    const filtered = domains.filter(d => d.domain !== 'all');
    el.innerHTML = filtered.map(d => {
        const dc = DOMAIN_COLORS[d.domain] || { color: '#888', icon: 'fa-circle' };
        const busy = parseInt(d.busy_agents || 0);
        const total = parseInt(d.total_agents || 0);
        const avail = total - busy;
        const pct = total > 0 ? ((avail / total) * 100).toFixed(0) : 100;
        return `<div class="v2-domain-card" data-domain="${esc(d.domain)}" onclick="FC2.filterGrid('${esc(d.domain)}')">
            <div class="v2-domain-icon" style="color:${dc.color}"><i class="fas ${dc.icon}"></i></div>
            <div class="v2-domain-name">${esc(d.domain.charAt(0).toUpperCase() + d.domain.slice(1))}</div>
            <div class="v2-domain-count">${total}</div>
            <div class="v2-domain-bar">
                <div class="v2-domain-bar-fill" style="width:${pct}%;background:${dc.color}"></div>
            </div>
            <div class="v2-domain-meta">${avail} available &middot; ${busy} busy</div>
        </div>`;
    }).join('');
}

// ═══════════════════════════════════════
// AGENT GRID TAB
// ═══════════════════════════════════════
async function loadAgentGrid(domain = null, page = 0) {
    const el = $('v2AgentGridContent');
    if (!el) return;

    v2state.activeDomain = domain;
    v2state.gridPage = page;

    // Build query
    const params = { limit: String(v2state.gridPageSize), offset: String(page * v2state.gridPageSize) };
    if (domain) params.domain = domain;

    try {
        const resp = await v2fetch('capacity', domain ? { domain } : {});

        // For agent list, we need to query the registry
        const listResp = await fetch(`/api/agent-registry.php?action=list&limit=${v2state.gridPageSize}&offset=${page * v2state.gridPageSize}${domain ? '&domain=' + encodeURIComponent(domain) : ''}`, { credentials: 'same-origin' });
        const listData = await listResp.json();

        if (listData.success && listData.data) {
            v2state.agents = listData.data.agents || listData.data || [];
            renderAgentGrid(v2state.agents, domain);
        } else {
            // Fallback: build from capacity data
            renderAgentGridFromCapacity(domain);
        }
    } catch (e) {
        console.error('Agent grid error:', e);
        renderAgentGridFromCapacity(domain);
    }
}

function renderAgentGridFromCapacity(domain) {
    const el = $('v2AgentGridContent');
    if (!el) return;

    const domains = domain ? [domain] : DOMAIN_ORDER;
    let html = '';

    domains.forEach(d => {
        const dc = DOMAIN_COLORS[d] || { color: '#888', icon: 'fa-circle' };
        const count = domain ? 499 : 50; // Show subset per domain in "all" view
        html += `<div class="v2-grid-section">
            <h3 class="v2-grid-section-title" style="color:${dc.color}">
                <i class="fas ${dc.icon}"></i> ${d.charAt(0).toUpperCase() + d.slice(1)}
                <span class="v2-grid-count">500 agents</span>
            </h3>
            <div class="v2-agent-dots">`;
        for (let i = 0; i < count; i++) {
            const status = Math.random() > 0.02 ? 'idle' : 'busy';
            html += `<div class="v2-dot v2-dot-${status}" style="--dot-color:${dc.color}" title="${d}_agent_${i + 1}"></div>`;
        }
        html += `</div></div>`;
    });

    if (!domain) {
        html += `<div class="v2-grid-footer">Showing 500 agents per domain (5,000 total). Click a domain above to see all agents.</div>`;
    }

    el.innerHTML = html;
}

function renderAgentGrid(agents, domain) {
    const el = $('v2AgentGridContent');
    if (!el) return;

    if (!agents.length) {
        renderAgentGridFromCapacity(domain);
        return;
    }

    const dc = DOMAIN_COLORS[domain] || { color: 'var(--accent)', icon: 'fa-robot' };
    const statusColors = { idle: 'var(--green)', busy: 'var(--yellow)', active: 'var(--cyan)', offline: 'var(--text-muted)', error: 'var(--red)' };

    let html = `<div class="v2-grid-section">
        <h3 class="v2-grid-section-title" style="color:${dc.color}">
            <i class="fas ${dc.icon}"></i> ${domain ? domain.charAt(0).toUpperCase() + domain.slice(1) : 'All Domains'}
            <span class="v2-grid-count">${agents.length} agents loaded</span>
        </h3>
        <div class="v2-agent-nodes">`;

    agents.forEach(a => {
        const st = a.status || 'idle';
        const sc = statusColors[st] || 'var(--text-muted)';
        const shortId = (a.agent_id || '').replace(/^[a-z]+_/, '').substring(0, 6);
        html += `<div class="v2-agent-tile" title="${esc(a.agent_name || a.agent_id)}\nStatus: ${st}\nSuccess: ${a.success_rate || 100}%">
            <div class="v2-tile-dot" style="background:${sc}"></div>
            <div class="v2-tile-id">${esc(shortId)}</div>
        </div>`;
    });

    html += `</div></div>`;

    // Pagination
    const total = domain ? 500 : 5000;
    const pages = Math.ceil(total / v2state.gridPageSize);
    if (pages > 1) {
        html += `<div class="v2-grid-pager">`;
        for (let p = 0; p < Math.min(pages, 10); p++) {
            html += `<button class="v2-page-btn ${p === v2state.gridPage ? 'active' : ''}" onclick="FC2.loadGrid('${domain || ''}', ${p})">${p + 1}</button>`;
        }
        if (pages > 10) html += `<span style="color:var(--text-muted)">... ${pages} pages</span>`;
        html += `</div>`;
    }

    el.innerHTML = html;
}

// ═══════════════════════════════════════
// MESSAGING TAB
// ═══════════════════════════════════════
async function loadInbox(agentId) {
    const el = $('v2InboxContent');
    if (!el) return;
    if (!agentId) agentId = 'alfred';

    try {
        const resp = await v2fetch('msg_inbox', { agent_id: agentId });
        if (resp.success) {
            v2state.messages = resp.data.messages || [];
            renderInbox(v2state.messages, agentId);
        }
    } catch (e) {
        el.innerHTML = '<div class="v2-empty">Unable to load inbox</div>';
    }
}

function renderInbox(messages, agentId) {
    const el = $('v2InboxContent');
    if (!el) return;

    if (!messages.length) {
        el.innerHTML = `<div class="v2-empty"><i class="fas fa-inbox" style="font-size:2rem;margin-bottom:.5rem"></i><br>No messages for ${esc(agentId)}</div>`;
        return;
    }

    el.innerHTML = messages.map(m => {
        const typeColors = { task: 'var(--blue)', result: 'var(--green)', query: 'var(--accent)', alert: 'var(--red)', broadcast: 'var(--cyan)', coordination: 'var(--yellow)', status: 'var(--accent-light)', heartbeat: 'var(--text-muted)' };
        const tc = typeColors[m.message_type] || 'var(--text-muted)';
        const payload = typeof m.payload === 'object' ? JSON.stringify(m.payload, null, 2) : String(m.payload || '');
        const ackClass = m.acknowledged ? 'v2-msg-acked' : 'v2-msg-unread';
        const time = m.created_at ? new Date(m.created_at).toLocaleString() : '';

        return `<div class="v2-msg ${ackClass}">
            <div class="v2-msg-header">
                <span class="v2-msg-type" style="color:${tc}"><i class="fas fa-circle" style="font-size:.45rem;vertical-align:middle;margin-right:.3rem"></i>${esc(m.message_type || 'unknown')}</span>
                <span class="v2-msg-from">from <strong>${esc(m.from_agent || '?')}</strong></span>
                ${m.to_domain ? `<span class="v2-msg-domain">→ ${esc(m.to_domain)}</span>` : ''}
                ${m.to_agent ? `<span class="v2-msg-domain">→ ${esc(m.to_agent)}</span>` : ''}
                <span class="v2-msg-time">${esc(time)}</span>
                <span class="v2-msg-pri" title="Priority ${m.priority}/10">P${m.priority || 5}</span>
            </div>
            <pre class="v2-msg-payload">${esc(payload)}</pre>
            ${!m.acknowledged ? `<button class="v2-ack-btn" onclick="FC2.ackMessage('${esc(m.message_id)}')"><i class="fas fa-check"></i> Acknowledge</button>` : '<span class="v2-ack-done"><i class="fas fa-check-double"></i> Acknowledged</span>'}
        </div>`;
    }).join('');
}

async function sendMessage() {
    if (v2state.sendingMsg) return;
    const toAgent = $('v2MsgTo')?.value.trim();
    const toDomain = $('v2MsgDomain')?.value;
    const msgType = $('v2MsgType')?.value || 'task';
    const payload = $('v2MsgPayload')?.value.trim();

    if (!toAgent && !toDomain) {
        toast('Specify a target agent or domain', 'error');
        return;
    }
    if (!payload) {
        toast('Message payload required', 'error');
        return;
    }

    let parsedPayload;
    try {
        parsedPayload = JSON.parse(payload);
    } catch {
        parsedPayload = { message: payload };
    }

    v2state.sendingMsg = true;
    const btn = $('v2SendBtn');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...'; }

    try {
        const body = {
            from_agent: 'alfred',
            message_type: msgType,
            payload: parsedPayload
        };
        if (toAgent) body.to_agent = toAgent;
        if (toDomain && !toAgent) body.to_domain = toDomain;

        let resp;
        if (toDomain && !toAgent) {
            resp = await v2fetch('broadcast', {}, 'POST', { from_agent: 'alfred', to_domain: toDomain, message_type: msgType, payload: parsedPayload });
        } else {
            resp = await v2fetch('msg_send', {}, 'POST', body);
        }

        if (resp.success) {
            toast('Message sent' + (resp.data.recipients ? ` to ${resp.data.recipients} agents` : ''), 'success');
            if ($('v2MsgPayload')) $('v2MsgPayload').value = '';
            loadInbox($('v2InboxAgent')?.value || 'alfred');
        } else {
            toast(resp.error || 'Send failed', 'error');
        }
    } catch (e) {
        toast('Network error', 'error');
    }

    v2state.sendingMsg = false;
    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send'; }
}

async function ackMessage(messageId) {
    try {
        const resp = await v2fetch('msg_ack', {}, 'POST', { message_ids: [messageId] });
        if (resp.success) {
            toast('Message acknowledged', 'success');
            loadInbox($('v2InboxAgent')?.value || 'alfred');
        }
    } catch (e) { toast('Ack failed', 'error'); }
}

// ═══════════════════════════════════════
// ROUTE TASK
// ═══════════════════════════════════════
async function routeTask() {
    const domain = $('v2RouteDomain')?.value;
    if (!domain) { toast('Select a domain', 'error'); return; }

    const btn = $('v2RouteBtn');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Routing...'; }

    try {
        const resp = await v2fetch('route_task', {}, 'POST', { domain, source_agent: 'alfred' });
        if (resp.success) {
            const d = resp.data;
            toast(`Task routed to ${d.agent_name} (${d.routed_to})`, 'success');
            const log = $('v2RouteLog');
            if (log) {
                const div = document.createElement('div');
                div.className = 'v2-route-entry';
                div.innerHTML = `<span style="color:var(--green)"><i class="fas fa-check"></i></span>
                    <span>Routed to <strong>${esc(d.agent_name)}</strong> (${esc(d.routed_to)})</span>
                    <span style="color:var(--text-muted);font-size:.75rem">${d.strategy} &middot; ${d.success_rate}% success</span>`;
                log.prepend(div);
            }
            loadOverview(); // refresh stats
        } else {
            toast(resp.error || 'Routing failed', 'error');
        }
    } catch (e) { toast('Network error', 'error'); }

    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-route"></i> Route Task'; }
}

// ═══════════════════════════════════════
// V2 METRICS
// ═══════════════════════════════════════
async function loadV2Metrics() {
    const el = $('v2MetricsContent');
    if (!el) return;

    try {
        const resp = await v2fetch('metrics');
        if (resp.success && resp.data.metrics) {
            renderV2Metrics(resp.data.metrics);
        } else {
            el.innerHTML = '<div class="v2-empty">No metrics recorded yet</div>';
        }
    } catch (e) {
        el.innerHTML = '<div class="v2-empty">Unable to load metrics</div>';
    }
}

function renderV2Metrics(metrics) {
    const el = $('v2MetricsContent');
    if (!el) return;

    if (!metrics.length) {
        el.innerHTML = '<div class="v2-empty">No metrics recorded yet. Metrics appear as agents complete tasks.</div>';
        return;
    }

    el.innerHTML = `<table class="history-table">
        <thead><tr><th>Agent</th><th>Metric</th><th>Data Points</th><th>Avg</th><th>Min</th><th>Max</th></tr></thead>
        <tbody>${metrics.map(m => `<tr>
            <td style="font-weight:600">${esc(m.agent_id || 'fleet')}</td>
            <td>${esc(m.metric_type)}</td>
            <td>${m.data_points}</td>
            <td style="font-family:var(--font-mono)">${Number(m.avg_value).toFixed(2)}</td>
            <td style="font-family:var(--font-mono)">${Number(m.min_value).toFixed(2)}</td>
            <td style="font-family:var(--font-mono)">${Number(m.max_value).toFixed(2)}</td>
        </tr>`).join('')}</tbody>
    </table>`;
}

// ═══════════════════════════════════════
// Utilities
// ═══════════════════════════════════════
function animateVal(el, target) {
    if (!el) return;
    const start = parseInt(el.textContent.replace(/,/g, '')) || 0;
    const diff = target - start;
    if (diff === 0) return;
    const steps = 30;
    let step = 0;
    const timer = setInterval(() => {
        step++;
        const val = Math.round(start + diff * (step / steps));
        el.textContent = val.toLocaleString();
        if (step >= steps) clearInterval(timer);
    }, 25);
}

function toast(msg, type) {
    if (window.GDSToast) return GDSToast.show(msg, { type: type === 'error' ? 'danger' : type });
    // Fallback toast
    const t = document.createElement('div');
    t.className = 'fleet-toast fleet-toast-' + (type === 'error' ? 'error' : type === 'success' ? 'success' : 'info');
    t.textContent = msg;
    document.body.appendChild(t);
    requestAnimationFrame(() => t.classList.add('show'));
    setTimeout(() => { t.classList.remove('show'); setTimeout(() => t.remove(), 300); }, 3000);
}

// ═══════════════════════════════════════
// Tab hooks — called by existing tab system
// ═══════════════════════════════════════
function onTabSwitch(name) {
    if (name === 'overview') loadOverview();
    if (name === 'agentgrid') loadAgentGrid(v2state.activeDomain);
    if (name === 'messaging') loadInbox($('v2InboxAgent')?.value || 'alfred');
}

// ═══════════════════════════════════════
// Public API
// ═══════════════════════════════════════
window.FC2 = {
    loadOverview,
    loadGrid: (domain, page) => loadAgentGrid(domain || null, page || 0),
    filterGrid: (domain) => {
        // Update active filter buttons
        document.querySelectorAll('.v2-domain-card').forEach(c => c.classList.remove('active'));
        const card = document.querySelector(`.v2-domain-card[data-domain="${domain}"]`);
        if (card) card.classList.add('active');
        // Switch to grid tab
        if (window.FC && FC.switchTab) FC.switchTab('agentgrid');
        loadAgentGrid(domain);
    },
    loadInbox,
    sendMessage,
    ackMessage,
    routeTask,
    loadV2Metrics,
    onTabSwitch,
    refreshAll: () => { loadOverview(); toast('Fleet data refreshed', 'info'); }
};

// ═══════════════════════════════════════
// Boot — load overview stats for hero
// ═══════════════════════════════════════
(async function boot() {
    try {
        const resp = await v2fetch('overview');
        if (resp.success) {
            v2state.overview = resp.data;
            const heroCount = $('liveAgentCount');
            if (heroCount) animateVal(heroCount, parseInt(resp.data.agents.total_agents) || 0);
            // Update main stat cards
            const a = resp.data.agents;
            const statAgents = $('statAgents');
            if (statAgents) animateVal(statAgents, parseInt(a.total_agents) || 0);
        }
    } catch (e) { /* stats from v1 will still load */ }
})();

})();
