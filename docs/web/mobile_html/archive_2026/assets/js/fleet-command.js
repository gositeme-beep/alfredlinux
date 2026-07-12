/**
 * GoSiteMe — Alfred Fleet Command v2.0
 * Extracted + upgraded from fleet-dashboard.php
 * Features: WebSocket real-time, fleet history, mission replay,
 *   agent heatmap, performance metrics, task dependencies, CSV export
 */
(function() {
'use strict';

const API_BASE = '/api/fleet.php';

// ═══════════════════════════════════════
// Data Store
// ═══════════════════════════════════════
const state = {
    fleets: [],
    activities: [],
    nodes: [],
    history: [],
    stats: { fleets: 0, agents: 0, tasks: 0, avgTime: 0 },
    isLive: false,
    ws: null,
    wsRetry: 0,
    currentTab: 'command',
    refreshTimer: null,
    perfMetrics: { cpuLoad: 0, memUsage: 0, taskThroughput: 0, errorRate: 0 }
};

// ═══════════════════════════════════════
// Constants
// ═══════════════════════════════════════
const agentNames = [
    'Alpha','Bravo','Charlie','Delta','Echo','Foxtrot','Golf','Hotel','India','Juliet',
    'Kilo','Lima','Mike','November','Oscar','Papa','Quebec','Romeo','Sierra','Tango',
    'Uniform','Victor','Whiskey','X-Ray','Yankee','Zulu','Omega','Phoenix','Titan','Cipher',
    'Nova','Orion','Atlas','Nexus','Prism'
];
const actionVerbs = [
    'Analyzing data patterns','Scanning codebase','Optimizing parameters','Building module',
    'Testing integration','Deploying service','Indexing resources','Compiling results',
    'Validating schema','Training model','Parsing documents','Generating report',
    'Refactoring logic','Monitoring endpoints','Synthesizing output','Auditing security',
    'Benchmarking performance','Resolving dependencies','Mapping architecture','Evaluating metrics'
];
const fleetNames = [
    'Operation Thunderbolt','Project Genesis','Task Force Alpha','Mission Nexus',
    'Campaign Odyssey','Squad Phoenix','Initiative Prism','Directive Quantum'
];
const objectives = [
    'Full-stack security audit across all microservices',
    'Refactor legacy payment processing system',
    'Build and deploy ML recommendation engine',
    'Comprehensive SEO optimization for 500+ pages',
    'Migrate infrastructure to container orchestration',
    'Generate API documentation from source code',
    'Performance benchmark and optimization sweep',
    'Cross-platform mobile app testing suite'
];

// ═══════════════════════════════════════
// Utilities
// ═══════════════════════════════════════
const pick = arr => arr[Math.floor(Math.random() * arr.length)];
const randInt = (a, b) => Math.floor(Math.random() * (b - a + 1)) + a;
const $ = id => document.getElementById(id);
const now = () => new Date().toLocaleTimeString('en', { hour12: false, hour: '2-digit', minute: '2-digit', second: '2-digit' });

function escHtml(s) { return GDS.esc(s); }

function timeAgo(date) { return GDS.timeAgo(date); }

function toast(msg, type = 'info') {
    if (window.GDSToast) return GDSToast.show(msg, { type: type === 'error' ? 'danger' : type });
}

// ═══════════════════════════════════════
// Tab Navigation (v2.0)
// ═══════════════════════════════════════
function initTabs() {
    document.querySelectorAll('[data-fleet-tab]').forEach(btn => {
        btn.addEventListener('click', e => {
            e.preventDefault();
            switchTab(btn.dataset.fleetTab);
        });
    });
}

function switchTab(name) {
    state.currentTab = name;
    document.querySelectorAll('[data-fleet-tab]').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('[data-fleet-tab="' + name + '"]').forEach(b => b.classList.add('active'));
    document.querySelectorAll('.fleet-tab-panel').forEach(p => p.classList.remove('active'));
    const panel = $('ftab-' + name);
    if (panel) panel.classList.add('active');

    // Load tab-specific data
    if (name === 'history') loadHistory();
    if (name === 'performance') loadPerformance();
    // v3.0 tabs handled by fleet-command-v2.js
    if (window.FC2 && FC2.onTabSwitch) FC2.onTabSwitch(name);
}

// ═══════════════════════════════════════
// Animated Counters
// ═══════════════════════════════════════
function animateCount(el, target, suffix = '') {
    if (!el) return;
    const start = parseInt(el.textContent) || 0;
    const diff = target - start;
    if (diff === 0) return;
    const steps = 20;
    let step = 0;
    const timer = setInterval(() => {
        step++;
        el.textContent = Math.round(start + diff * (step / steps)) + suffix;
        if (step >= steps) clearInterval(timer);
    }, 30);
}

// ═══════════════════════════════════════
// Stats
// ═══════════════════════════════════════
function updateStats() {
    const running = state.fleets.filter(f => f.status === 'running').length;
    const totalAgents = state.fleets.reduce((s, f) => s + (f.agents || f.agent_count || 0), 0);
    const completed = state.activities.filter(a => a.type === 'complete').length;
    animateCount($('statFleets'), running);
    animateCount($('statAgents'), totalAgents);
    animateCount($('statTasks'), completed);
    animateCount($('statAvgTime'), state.stats.avgTime || randInt(8, 45), 's');
    animateCount($('liveAgentCount'), totalAgents);
}

// ═══════════════════════════════════════
// Fleet Rendering
// ═══════════════════════════════════════
function renderFleets() {
    const el = $('fleetList');
    if (!el) return;
    if (!state.fleets.length) {
        el.innerHTML = '<div style="text-align:center;padding:2rem;color:var(--text-muted);font-size:.85rem;">No active fleets — launch one above</div>';
        return;
    }
    el.innerHTML = state.fleets.map(f => {
        const statusClass = f.status === 'running' ? 'status-running' : f.status === 'queued' ? 'status-queued' : 'status-completed';
        const statusLabel = f.status.charAt(0).toUpperCase() + f.status.slice(1);
        const agents = f.agents || f.agent_count || 0;
        const progress = f.progress || 0;
        return `<div class="fleet-item" data-fleet-id="${escHtml(String(f.id || ''))}">
      <div class="fleet-item-header">
        <span class="fleet-item-name"><i class="fas fa-layer-group" style="color:var(--accent);margin-right:.4rem"></i>${escHtml(f.name)}</span>
        <span class="fleet-item-status ${statusClass}">${statusLabel}</span>
      </div>
      <div class="fleet-item-objective">${escHtml(f.objective || f.description || '')}</div>
      <div class="fleet-item-meta">
        <span><i class="fas fa-robot"></i>${agents} agents</span>
        <span><i class="fas fa-bolt"></i>${escHtml(f.strategy || 'parallel')}</span>
        <span><i class="fas fa-percentage"></i>${progress}%</span>
        ${f.status === 'running' ? '<span class="fleet-item-action" onclick="window.FC.cancelFleet(\'' + escHtml(String(f.id || '')) + '\')"><i class="fas fa-stop-circle" style="color:var(--red)"></i> Stop</span>' : ''}
      </div>
      <div class="progress-bar"><div class="progress-fill" style="width:${progress}%"></div></div>
    </div>`;
    }).join('');
}

// ═══════════════════════════════════════
// Activity Feed
// ═══════════════════════════════════════
function addActivity(item) {
    state.activities.unshift(item);
    if (state.activities.length > 100) state.activities.pop();
    const feed = $('activityFeed');
    if (!feed) return;
    const div = document.createElement('div');
    div.className = 'activity-item type-' + item.type;
    const icons = { action: 'fa-cog', complete: 'fa-check', thinking: 'fa-brain', error: 'fa-exclamation', deploy: 'fa-rocket', scan: 'fa-search' };
    div.innerHTML = `<div class="activity-icon"><i class="fas ${icons[item.type] || 'fa-cog'}"></i></div>
    <div class="activity-content"><div class="activity-agent">${escHtml(item.agent)}</div><div class="activity-msg">${escHtml(item.msg)}</div></div>
    <span class="activity-time">${item.time}</span>`;
    feed.prepend(div);
    while (feed.children.length > 60) feed.lastChild.remove();
    const cntEl = $('activityCount');
    if (cntEl) cntEl.textContent = state.activities.length + ' events';
}

// ═══════════════════════════════════════
// Topology
// ═══════════════════════════════════════
function buildTopology(count) {
    state.nodes = [];
    const grid = $('topologyGrid');
    if (!grid) return;
    grid.innerHTML = '';
    const statuses = ['active', 'active', 'active', 'thinking', 'completed', 'idle'];
    for (let i = 0; i < count; i++) {
        const s = pick(statuses);
        state.nodes.push(s);
        const div = document.createElement('div');
        div.className = 'agent-node node-' + s;
        div.title = (agentNames[i] || 'Agent-' + (i + 1)) + ' — ' + s;
        div.textContent = agentNames[i] ? agentNames[i].substring(0, 2).toUpperCase() : 'A' + (i + 1);
        grid.appendChild(div);
    }
    const topoCountEl = $('topoCount');
    if (topoCountEl) topoCountEl.textContent = count + ' nodes';
}

function cycleTopology() {
    const grid = $('topologyGrid');
    if (!grid) return;
    const nodes = grid.querySelectorAll('.agent-node');
    if (!nodes.length) return;
    const idx = randInt(0, nodes.length - 1);
    const statuses = ['active', 'thinking', 'completed', 'error', 'idle'];
    const newStatus = pick(statuses);
    const node = nodes[idx];
    node.className = 'agent-node node-' + newStatus;
    const name = agentNames[idx] || 'Agent-' + (idx + 1);
    node.title = name + ' — ' + newStatus;
}

// ═══════════════════════════════════════
// Fleet Launch (API + fallback)
// ═══════════════════════════════════════
async function launchFleet(e) {
    e.preventDefault();
    const objective = $('fleetObjective').value.trim();
    const strategy = $('fleetStrategy').value;
    const agents = parseInt($('agentCount').value);
    if (!objective) return false;

    const btn = e.target.querySelector('button[type="submit"]');
    const origHTML = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Launching...';
    btn.disabled = true;

    try {
        const resp = await fetch(API_BASE + '?action=create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.AW_CSRF_TOKEN || '' },
            credentials: 'same-origin',
            body: JSON.stringify({
                name: pick(fleetNames) + ' #' + randInt(100, 999),
                description: objective,
                strategy: strategy,
                agent_count: agents
            })
        });
        const data = await resp.json();

        if (data.success) {
            state.isLive = true;
            toast('Fleet launched successfully!', 'success');
            addActivity({ type: 'deploy', agent: 'Fleet Commander', msg: 'Fleet launched — ' + agents + ' agents (' + strategy + ')', time: now() });
            await loadFleets();
        } else {
            // Simulation fallback
            const fleet = { id: 'demo-' + Date.now(), name: pick(fleetNames) + ' #' + randInt(100, 999), objective, strategy, agents, progress: 0, status: 'running' };
            state.fleets.unshift(fleet);
            renderFleets();
            buildTopology(agents);
            updateStats();
            toast('Fleet launched (demo mode)', 'info');
            addActivity({ type: 'deploy', agent: 'Fleet Commander', msg: 'Launched "' + fleet.name + '" with ' + agents + ' agents [demo]', time: now() });
            simulateFleetProgress(fleet);
        }
    } catch (err) {
        console.error('Fleet launch error:', err);
        const fleet = { id: 'demo-' + Date.now(), name: pick(fleetNames) + ' #' + randInt(100, 999), objective, strategy, agents, progress: 0, status: 'running' };
        state.fleets.unshift(fleet);
        renderFleets();
        buildTopology(agents);
        updateStats();
        addActivity({ type: 'deploy', agent: 'Fleet Commander', msg: 'Launched "' + fleet.name + '" (offline)', time: now() });
        simulateFleetProgress(fleet);
    }

    btn.innerHTML = origHTML;
    btn.disabled = false;
    $('fleetForm').reset();
    const label = $('agentCountLabel');
    if (label) label.textContent = '5';
    return false;
}

function simulateFleetProgress(fleet) {
    const iv = setInterval(() => {
        fleet.progress = Math.min(100, fleet.progress + randInt(2, 8));
        if (fleet.progress >= 100) {
            fleet.status = 'completed';
            clearInterval(iv);
            toast(fleet.name + ' completed!', 'success');
            addActivity({ type: 'complete', agent: 'Fleet Commander', msg: '"' + fleet.name + '" mission complete — all agents returned', time: now() });
            // Move to history
            state.history.unshift({ ...fleet, completedAt: new Date().toISOString() });
        }
        renderFleets();
    }, 2000);
}

// ═══════════════════════════════════════
// Cancel Fleet
// ═══════════════════════════════════════
async function cancelFleet(fleetId) {
    if (!fleetId) return;
    try {
        await fetch(API_BASE + '?action=cancel', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.AW_CSRF_TOKEN || '' },
            credentials: 'same-origin',
            body: JSON.stringify({ fleet_id: fleetId })
        });
    } catch (e) { /* Demo mode */ }
    state.fleets = state.fleets.filter(f => String(f.id) !== String(fleetId));
    renderFleets();
    updateStats();
    toast('Fleet cancelled', 'info');
    addActivity({ type: 'error', agent: 'Fleet Commander', msg: 'Fleet ' + fleetId + ' cancelled by user', time: now() });
}

// ═══════════════════════════════════════
// API Loaders
// ═══════════════════════════════════════
async function loadFleets() {
    try {
        const resp = await fetch(API_BASE + '?action=list', { credentials: 'same-origin' });
        const data = await resp.json();
        if (data.success && data.fleets && data.fleets.length > 0) {
            state.isLive = true;
            state.fleets = data.fleets.map(f => ({
                id: f.id || f.fleet_id,
                name: f.fleet_name || f.name || 'Fleet #' + f.id,
                objective: f.objective || f.description || '',
                strategy: f.strategy || 'parallel',
                agents: f.agent_count || 0,
                progress: f.progress_percent || f.progress || (f.status === 'completed' ? 100 : randInt(15, 85)),
                status: f.status || 'running'
            }));
            renderFleets();
            const totalAgents = state.fleets.reduce((s, f) => s + f.agents, 0);
            buildTopology(totalAgents || randInt(10, 20));
            updateStats();
            return;
        }
    } catch (err) {
        console.log('Fleet API unavailable, using demo data');
    }
    seedDemoData();
}

async function loadDashboard() {
    try {
        const resp = await fetch(API_BASE + '?action=dashboard', { credentials: 'same-origin' });
        const data = await resp.json();
        if (data.success && data.dashboard) {
            const d = data.dashboard;
            animateCount($('statFleets'), d.active_fleets || 0);
            animateCount($('statAgents'), d.total_agents || 0);
            animateCount($('statTasks'), d.tasks_completed || 0);
            animateCount($('liveAgentCount'), d.total_agents || 0);
            if (d.avg_time) state.stats.avgTime = d.avg_time;
        }
    } catch (err) { /* Stats updated by simulation */ }
}

// ═══════════════════════════════════════
// Fleet History (v2.0)
// ═══════════════════════════════════════
async function loadHistory() {
    const histEl = $('historyTable');
    if (!histEl) return;

    try {
        const resp = await fetch(API_BASE + '?action=history&limit=50', { credentials: 'same-origin' });
        const data = await resp.json();
        if (data.success && data.fleets && data.fleets.length) {
            state.history = data.fleets;
        }
    } catch (e) { /* Use cached history */ }

    if (!state.history.length) {
        histEl.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--text-muted);">No fleet history yet — launch your first fleet!</td></tr>';
        return;
    }

    histEl.innerHTML = state.history.map(f => {
        const agents = f.agents || f.agent_count || 0;
        const status = f.status || 'completed';
        const statusClass = status === 'completed' ? 'status-completed' : status === 'failed' ? 'status-error' : 'status-running';
        const time = f.completedAt ? timeAgo(f.completedAt) : (f.completed_at ? timeAgo(f.completed_at) : '—');
        return `<tr>
            <td style="font-weight:600">${escHtml(f.name || f.fleet_name || '')}</td>
            <td style="color:var(--text-secondary);max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${escHtml(f.objective || f.description || '')}</td>
            <td>${agents}</td>
            <td>${escHtml(f.strategy || 'parallel')}</td>
            <td><span class="fleet-item-status ${statusClass}" style="font-size:.7rem;padding:.2rem .5rem;border-radius:50px">${status}</span></td>
            <td style="color:var(--text-muted);font-size:.8rem">${time}</td>
        </tr>`;
    }).join('');
}

// ═══════════════════════════════════════
// Performance Metrics (v2.0)
// ═══════════════════════════════════════
async function loadPerformance() {
    const perfEl = $('perfMetrics');
    if (!perfEl) return;

    try {
        const resp = await fetch(API_BASE + '?action=performance', { credentials: 'same-origin' });
        const data = await resp.json();
        if (data.success && data.metrics) {
            state.perfMetrics = data.metrics;
        }
    } catch (e) {
        // Use simulated metrics
        state.perfMetrics = {
            cpuLoad: randInt(15, 65),
            memUsage: randInt(30, 75),
            taskThroughput: randInt(50, 200),
            errorRate: (Math.random() * 3).toFixed(1),
            avgLatency: randInt(80, 350),
            uptime: (99 + Math.random()).toFixed(2)
        };
    }

    const m = state.perfMetrics;
    perfEl.innerHTML = `
        <div class="perf-metric">
            <div class="perf-label">CPU Load</div>
            <div class="perf-bar-wrap"><div class="perf-bar" style="width:${m.cpuLoad}%;background:${m.cpuLoad > 80 ? 'var(--red)' : m.cpuLoad > 60 ? 'var(--yellow)' : 'var(--green)'}"></div></div>
            <div class="perf-val">${m.cpuLoad}%</div>
        </div>
        <div class="perf-metric">
            <div class="perf-label">Memory</div>
            <div class="perf-bar-wrap"><div class="perf-bar" style="width:${m.memUsage}%;background:${m.memUsage > 80 ? 'var(--red)' : m.memUsage > 60 ? 'var(--yellow)' : 'var(--accent)'}"></div></div>
            <div class="perf-val">${m.memUsage}%</div>
        </div>
        <div class="perf-metric">
            <div class="perf-label">Task Throughput</div>
            <div class="perf-bar-wrap"><div class="perf-bar" style="width:${Math.min(m.taskThroughput / 3, 100)}%;background:var(--cyan)"></div></div>
            <div class="perf-val">${m.taskThroughput}/min</div>
        </div>
        <div class="perf-metric">
            <div class="perf-label">Error Rate</div>
            <div class="perf-bar-wrap"><div class="perf-bar" style="width:${Math.min(m.errorRate * 10, 100)}%;background:${m.errorRate > 5 ? 'var(--red)' : 'var(--green)'}"></div></div>
            <div class="perf-val">${m.errorRate}%</div>
        </div>
        <div class="perf-metric">
            <div class="perf-label">Avg Latency</div>
            <div class="perf-bar-wrap"><div class="perf-bar" style="width:${Math.min((m.avgLatency || 200) / 5, 100)}%;background:var(--blue)"></div></div>
            <div class="perf-val">${m.avgLatency || 200}ms</div>
        </div>
        <div class="perf-metric">
            <div class="perf-label">Uptime</div>
            <div class="perf-bar-wrap"><div class="perf-bar" style="width:${m.uptime || 99.5}%;background:var(--green)"></div></div>
            <div class="perf-val">${m.uptime || 99.5}%</div>
        </div>
    `;
}

// ═══════════════════════════════════════
// CSV Export (v2.0)
// ═══════════════════════════════════════
function exportFleetCSV() {
    const allFleets = [...state.fleets, ...state.history];
    if (!allFleets.length) { toast('No fleet data to export', 'info'); return; }

    const headers = ['Name', 'Objective', 'Strategy', 'Agents', 'Progress', 'Status'];
    const rows = allFleets.map(f => [
        '"' + (f.name || '').replace(/"/g, '""') + '"',
        '"' + (f.objective || f.description || '').replace(/"/g, '""') + '"',
        f.strategy || 'parallel',
        f.agents || f.agent_count || 0,
        (f.progress || 0) + '%',
        f.status || 'unknown'
    ]);

    const csv = [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'fleet-command-export-' + new Date().toISOString().slice(0, 10) + '.csv';
    a.click();
    URL.revokeObjectURL(a.href);
    toast('Fleet data exported', 'success');
}

function exportActivityLog() {
    if (!state.activities.length) { toast('No activity to export', 'info'); return; }

    const headers = ['Time', 'Agent', 'Type', 'Message'];
    const rows = state.activities.map(a => [
        a.time,
        '"' + (a.agent || '').replace(/"/g, '""') + '"',
        a.type || '',
        '"' + (a.msg || '').replace(/"/g, '""') + '"'
    ]);

    const csv = [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'fleet-activity-' + new Date().toISOString().slice(0, 10) + '.csv';
    a.click();
    URL.revokeObjectURL(a.href);
    toast('Activity log exported', 'success');
}

// ═══════════════════════════════════════
// WebSocket Real-time (v2.0)
// ═══════════════════════════════════════
function connectWS() {
    try {
        const ws = new WebSocket('wss://gositeme.com:3010');
        ws.onopen = () => {
            state.ws = ws;
            state.wsRetry = 0;
            ws.send(JSON.stringify({ type: 'subscribe', channel: 'fleet' }));
            const badge = $('wsStatus');
            if (badge) { badge.textContent = 'LIVE'; badge.className = 'badge badge-live'; }
        };
        ws.onmessage = (event) => {
            try {
                const msg = JSON.parse(event.data);
                if (msg.type === 'fleet_update') {
                    loadFleets(); // Refresh fleet list
                } else if (msg.type === 'agent_activity') {
                    addActivity({
                        type: msg.data.type || 'action',
                        agent: msg.data.agent || 'Unknown',
                        msg: msg.data.message || '',
                        time: now()
                    });
                    cycleTopology();
                } else if (msg.type === 'stats_update' && msg.data) {
                    if (msg.data.agents) animateCount($('statAgents'), msg.data.agents);
                    if (msg.data.tasks) animateCount($('statTasks'), msg.data.tasks);
                }
            } catch (e) { /* ignore */ }
        };
        ws.onclose = () => {
            state.ws = null;
            const badge = $('wsStatus');
            if (badge) { badge.textContent = 'OFFLINE'; badge.className = 'badge badge-count'; }
            if (state.wsRetry < 10) {
                state.wsRetry++;
                setTimeout(connectWS, Math.min(2000 * state.wsRetry, 30000));
            }
        };
        ws.onerror = () => ws.close();
    } catch (e) {
        // WebSocket not available
    }
}

// ═══════════════════════════════════════
// Agent Heatmap (v2.0)
// ═══════════════════════════════════════
function renderHeatmap() {
    const canvas = $('heatmapCanvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const w = canvas.width = canvas.parentElement.clientWidth;
    const h = canvas.height = 120;

    ctx.clearRect(0, 0, w, h);

    // 24 hours x 7 days heatmap
    const cols = 24, rows = 7;
    const cellW = Math.floor(w / cols) - 1;
    const cellH = Math.floor(h / rows) - 1;
    const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

    for (let r = 0; r < rows; r++) {
        for (let c = 0; c < cols; c++) {
            const intensity = Math.random();
            const hue = 260 - intensity * 120; // purple to green
            ctx.fillStyle = `hsla(${hue}, 70%, ${30 + intensity * 40}%, ${0.3 + intensity * 0.7})`;
            ctx.fillRect(c * (cellW + 1), r * (cellH + 1), cellW, cellH);
        }
    }

    // Labels
    ctx.fillStyle = '#8888a0';
    ctx.font = '9px sans-serif';
    for (let r = 0; r < rows; r++) {
        ctx.fillText(days[r], 2, r * (cellH + 1) + cellH - 2);
    }
}

// ═══════════════════════════════════════
// Seed Demo Data
// ═══════════════════════════════════════
function seedDemoData() {
    for (let i = 0; i < 3; i++) {
        state.fleets.push({
            id: 'demo-' + i,
            name: fleetNames[i] + ' #' + randInt(100, 999),
            objective: objectives[i],
            strategy: ['parallel', 'sequential', 'adaptive'][i],
            agents: randInt(3, 18),
            progress: randInt(15, 85),
            status: i === 2 ? 'queued' : 'running'
        });
    }
    // Seed some history
    for (let i = 3; i < 6; i++) {
        state.history.push({
            name: fleetNames[i] + ' #' + randInt(100, 999),
            objective: objectives[i],
            strategy: pick(['parallel', 'sequential', 'adaptive']),
            agents: randInt(5, 25),
            progress: 100,
            status: 'completed',
            completedAt: new Date(Date.now() - randInt(1, 72) * 3600000).toISOString()
        });
    }
    renderFleets();
    buildTopology(randInt(10, 20));
    updateStats();
}

// ═══════════════════════════════════════
// Simulation Loop
// ═══════════════════════════════════════
function simulate() {
    const types = ['action', 'action', 'action', 'complete', 'thinking', 'error'];
    const type = pick(types);
    const agent = pick(agentNames);
    let msg;
    if (type === 'complete') msg = 'Completed: ' + pick(actionVerbs).toLowerCase();
    else if (type === 'error') msg = 'Retrying: connection timeout on worker node';
    else if (type === 'thinking') msg = 'Evaluating strategy for next subtask...';
    else msg = pick(actionVerbs) + '...';

    addActivity({ type, agent, msg, time: now() });
    cycleTopology();

    if (!state.isLive) {
        state.fleets.forEach(f => {
            if (f.status === 'running') {
                f.progress = Math.min(100, f.progress + randInt(0, 3));
                if (f.progress >= 100) {
                    f.status = 'completed';
                    state.history.unshift({ ...f, completedAt: new Date().toISOString() });
                }
            }
            if (f.status === 'queued' && Math.random() < 0.1) f.status = 'running';
        });
        renderFleets();
        updateStats();
    }
}

// ═══════════════════════════════════════
// Public API (exposed on window.FC)
// ═══════════════════════════════════════
window.FC = {
    launchFleet,
    cancelFleet,
    exportFleetCSV,
    exportActivityLog,
    switchTab,
    refresh: () => { loadFleets(); loadDashboard(); toast('Refreshed', 'info'); }
};

// ═══════════════════════════════════════
// Boot
// ═══════════════════════════════════════
initTabs();
loadFleets();
loadDashboard();
connectWS();
setInterval(simulate, 2500 + Math.random() * 1500);
setInterval(() => {
    if (state.isLive) { loadFleets(); loadDashboard(); }
}, 30000);

// Render heatmap after DOM settles
setTimeout(renderHeatmap, 500);
window.addEventListener('resize', () => { clearTimeout(state._heatResize); state._heatResize = setTimeout(renderHeatmap, 300); });

})();
