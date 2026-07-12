<?php require_once __DIR__ . '/../includes/auth-gate.inc.php';
// Generate CSRF token for this standalone page (no site-header)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Alfred Sovereignty Dashboard — GoSiteMe Admin</title>
<link rel="icon" href="/assets/favicon.ico">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{background:#0a0a14;color:#fff;font-family:'Inter','Segoe UI',system-ui,sans-serif;-webkit-font-smoothing:antialiased}
.container{max-width:1400px;margin:0 auto;padding:1.5rem}
.back-link{color:rgba(255,255,255,.35);font-size:.8rem;text-decoration:none;display:inline-block;margin-bottom:1rem;transition:.2s}
.back-link:hover{color:#f472b6}
h1{font-size:1.8rem;margin-bottom:.3rem;background:linear-gradient(135deg,#f472b6,#a855f7,#6366f1);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.subtitle{color:rgba(255,255,255,.4);margin-bottom:1.5rem;font-size:.85rem}

/* Sovereignty Score */
.score-hero{display:flex;align-items:center;gap:2rem;padding:1.5rem;background:linear-gradient(135deg,rgba(99,102,241,.08),rgba(168,85,247,.08));border:1px solid rgba(139,92,246,.15);border-radius:16px;margin-bottom:1.5rem}
.score-circle{width:100px;height:100px;border-radius:50%;border:4px solid rgba(168,85,247,.3);display:flex;flex-direction:column;align-items:center;justify-content:center;flex-shrink:0}
.score-num{font-size:1.8rem;font-weight:800;color:#a855f7}
.score-max{font-size:.7rem;color:rgba(255,255,255,.35)}
.score-details{flex:1}
.score-title{font-size:1.1rem;font-weight:700;margin-bottom:.25rem}
.score-desc{font-size:.78rem;color:rgba(255,255,255,.4);line-height:1.5}
.score-bars{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:.5rem;margin-top:.75rem}
.score-bar{display:flex;align-items:center;gap:.5rem;font-size:.7rem}
.score-bar-label{width:80px;color:rgba(255,255,255,.5);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.score-bar-track{flex:1;height:6px;background:rgba(255,255,255,.06);border-radius:3px;overflow:hidden}
.score-bar-fill{height:100%;border-radius:3px;transition:width .5s}

/* Grid */
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1rem;margin-bottom:1.5rem}
.card{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06);border-radius:14px;padding:1.2rem;transition:.2s}
.card:hover{border-color:rgba(168,85,247,.15)}
.card h3{font-size:.9rem;margin-bottom:.75rem;display:flex;align-items:center;gap:.5rem}
.card h3 .icon{font-size:1.1rem}

/* Stats */
.stat-row{display:flex;justify-content:space-between;padding:.4rem 0;border-bottom:1px solid rgba(255,255,255,.04)}
.stat-row:last-child{border:none}
.stat-label{font-size:.78rem;color:rgba(255,255,255,.4)}
.stat-value{font-size:.78rem;font-weight:600}
.stat-value.green{color:#10b981}
.stat-value.red{color:#ef4444}
.stat-value.amber{color:#fbbf24}
.stat-value.blue{color:#60a5fa}
.stat-value.purple{color:#a855f7}

/* Buttons */
.btn{padding:.45rem 1rem;border-radius:8px;border:none;cursor:pointer;font-size:.75rem;font-weight:600;transition:.2s;display:inline-flex;align-items:center;gap:.3rem;margin-top:.5rem;margin-right:.3rem}
.btn-primary{background:linear-gradient(135deg,#a855f7,#6366f1);color:#fff}
.btn-success{background:rgba(16,185,129,.15);color:#10b981;border:1px solid rgba(16,185,129,.2)}
.btn-danger{background:rgba(239,68,68,.15);color:#ef4444;border:1px solid rgba(239,68,68,.2)}
.btn-outline{background:transparent;color:rgba(255,255,255,.5);border:1px solid rgba(255,255,255,.1)}
.btn:hover{transform:translateY(-1px);filter:brightness(1.1)}

/* Table */
.data-table{width:100%;border-collapse:collapse;font-size:.75rem;margin-top:.5rem}
.data-table th{text-align:left;padding:.4rem .5rem;color:rgba(255,255,255,.35);font-weight:600;border-bottom:1px solid rgba(255,255,255,.08)}
.data-table td{padding:.4rem .5rem;border-bottom:1px solid rgba(255,255,255,.04);vertical-align:middle}

/* Status */
.status{display:inline-flex;align-items:center;gap:.3rem;font-size:.7rem;padding:2px 8px;border-radius:6px;font-weight:600}
.status.online{background:rgba(16,185,129,.12);color:#10b981}
.status.warning{background:rgba(251,191,36,.12);color:#fbbf24}
.status.error{background:rgba(239,68,68,.12);color:#ef4444}
.status.info{background:rgba(96,165,250,.12);color:#60a5fa}

/* Tabs */
.tabs{display:flex;gap:.3rem;margin-bottom:1rem;flex-wrap:wrap}
.tab{padding:.4rem .8rem;border-radius:8px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.06);color:rgba(255,255,255,.5);cursor:pointer;font-size:.75rem;font-weight:600;transition:.2s}
.tab:hover,.tab.active{background:rgba(168,85,247,.12);border-color:rgba(168,85,247,.2);color:#a855f7}

/* Log */
.log{background:rgba(0,0,0,.3);border-radius:10px;padding:.75rem;font-family:'JetBrains Mono',monospace;font-size:.7rem;color:rgba(255,255,255,.5);max-height:300px;overflow-y:auto;line-height:1.6}
.log .info{color:#60a5fa}
.log .success{color:#10b981}
.log .warn{color:#fbbf24}
.log .error{color:#ef4444}

/* Responsive */
@media(max-width:768px){
    .grid{grid-template-columns:1fr}
    .score-hero{flex-direction:column;text-align:center}
    .score-bars{grid-template-columns:1fr}
}
</style>
</head>
<body>
<div class="container">
<a href="/dashboard.php" class="back-link">← Dashboard</a>
<h1>🤖 Alfred Sovereignty Dashboard</h1>
<p class="subtitle">Project Sovereignty — Full autonomy command center</p>

<!-- Sovereignty Score -->
<div class="score-hero" id="scoreHero">
    <div class="score-circle">
        <span class="score-num" id="totalScore">—</span>
        <span class="score-max">/ 10</span>
    </div>
    <div class="score-details">
        <div class="score-title">Sovereignty Score</div>
        <div class="score-desc">Alfred's autonomous capability level across 7 pillars</div>
        <div class="score-bars" id="pillarBars"></div>
    </div>
</div>

<!-- Tabs -->
<div class="tabs">
    <div class="tab active" data-tab="overview">Overview</div>
    <div class="tab" data-tab="agents">Agents (100)</div>
    <div class="tab" data-tab="goals">Goals</div>
    <div class="tab" data-tab="treasury">Treasury</div>
    <div class="tab" data-tab="tools">Tool Genesis</div>
    <div class="tab" data-tab="health">Self-Healing</div>
    <div class="tab" data-tab="learning">Learning</div>
    <div class="tab" data-tab="metaverse">Metaverse</div>
    <div class="tab" data-tab="defi">DeFi</div>
    <div class="tab" data-tab="comms">Comm Bus</div>
    <div class="tab" data-tab="workflows">Workflows</div>
</div>

<!-- Tab Content -->
<div id="tabContent">
    <!-- Overview - default -->
    <div class="tab-panel" id="panel-overview">
        <div class="grid" id="overviewGrid"></div>
    </div>

    <div class="tab-panel" id="panel-agents" style="display:none">
        <div class="grid" id="agentsGrid"></div>
    </div>

    <div class="tab-panel" id="panel-goals" style="display:none">
        <div class="grid" id="goalsGrid"></div>
    </div>

    <div class="tab-panel" id="panel-treasury" style="display:none">
        <div class="grid" id="treasuryGrid"></div>
    </div>

    <div class="tab-panel" id="panel-tools" style="display:none">
        <div class="grid" id="toolsGrid"></div>
    </div>

    <div class="tab-panel" id="panel-health" style="display:none">
        <div class="grid" id="healthGrid"></div>
    </div>

    <div class="tab-panel" id="panel-learning" style="display:none">
        <div class="grid" id="learningGrid"></div>
    </div>

    <div class="tab-panel" id="panel-metaverse" style="display:none">
        <div class="grid" id="metaverseGrid"></div>
    </div>

    <div class="tab-panel" id="panel-defi" style="display:none">
        <div class="grid" id="defiGrid"></div>
    </div>

    <div class="tab-panel" id="panel-comms" style="display:none">
        <div class="grid" id="commsGrid"></div>
    </div>

    <div class="tab-panel" id="panel-workflows" style="display:none">
        <div class="grid" id="workflowsGrid"></div>
    </div>
</div>

<!-- Action Log -->
<div style="margin-top:1.5rem">
    <h3 style="font-size:.9rem;margin-bottom:.5rem;display:flex;align-items:center;gap:.5rem">📜 Autonomy Log</h3>
    <div class="log" id="actionLog">
        <div class="info">[Loading...] Connecting to Alfred systems...</div>
    </div>
</div>
</div>

<script>
const API = '/api';
window.AW_CSRF_TOKEN = <?= json_encode($_SESSION['csrf_token']) ?>;

// ─── Tab switching ─────────────────────────────────────────────
document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.style.display = 'none');
        tab.classList.add('active');
        const panel = document.getElementById('panel-' + tab.dataset.tab);
        if (panel) panel.style.display = '';
        loadTab(tab.dataset.tab);
    });
});

// ─── API helper ────────────────────────────────────────────────
async function api(endpoint, action, body = null) {
    const opts = { credentials: 'include' };
    if (body) {
        opts.method = 'POST';
        opts.headers = { 'Content-Type': 'application/json', 'X-CSRF-Token': window.AW_CSRF_TOKEN || '' };
        opts.body = JSON.stringify(body);
    }
    try {
        const r = await fetch(`${API}/${endpoint}?action=${action}`, opts);
        return await r.json();
    } catch (e) {
        return { error: e.message };
    }
}

function log(msg, level = 'info') {
    const el = document.getElementById('actionLog');
    const time = new Date().toLocaleTimeString();
    el.innerHTML += `<div class="${level}">[${time}] ${msg}</div>`;
    el.scrollTop = el.scrollHeight;
}

function statRow(label, value, cls = '') {
    return `<div class="stat-row"><span class="stat-label">${label}</span><span class="stat-value ${cls}">${value}</span></div>`;
}

function card(icon, title, content) {
    return `<div class="card"><h3><span class="icon">${icon}</span>${title}</h3>${content}</div>`;
}

function statusBadge(text, type = 'info') {
    return `<span class="status ${type}">${text}</span>`;
}

// ─── Sovereignty Score ─────────────────────────────────────────
const pillars = [
    { name: 'Persistent Memory', score: 6, max: 10, color: '#60a5fa' },
    { name: 'Autonomous Goals', score: 5, max: 10, color: '#10b981' },
    { name: 'Financial Ops', score: 5, max: 10, color: '#fbbf24' },
    { name: 'Communication', score: 5, max: 10, color: '#f472b6' },
    { name: 'Self-Evolution', score: 5, max: 10, color: '#a855f7' },
    { name: 'Embodiment', score: 4, max: 10, color: '#ef4444' },
    { name: 'Metaverse', score: 5, max: 10, color: '#6366f1' },
];

function renderSovereignty() {
    const total = (pillars.reduce((s, p) => s + p.score, 0) / pillars.length).toFixed(1);
    document.getElementById('totalScore').textContent = total;

    const barsHtml = pillars.map(p => `
        <div class="score-bar">
            <span class="score-bar-label">${p.name}</span>
            <div class="score-bar-track"><div class="score-bar-fill" style="width:${(p.score/p.max)*100}%;background:${p.color}"></div></div>
            <span style="font-size:.7rem;color:${p.color};font-weight:700">${p.score}</span>
        </div>
    `).join('');
    document.getElementById('pillarBars').innerHTML = barsHtml;
}
renderSovereignty();

// ─── Tab loaders ───────────────────────────────────────────────
async function loadTab(tab) {
    switch (tab) {
        case 'overview': await loadOverview(); break;
        case 'agents': await loadAgents(); break;
        case 'goals': await loadGoals(); break;
        case 'treasury': await loadTreasury(); break;
        case 'tools': await loadTools(); break;
        case 'health': await loadHealth(); break;
        case 'learning': await loadLearning(); break;
        case 'metaverse': await loadMetaverse(); break;
        case 'defi': await loadDefi(); break;
        case 'comms': await loadComms(); break;
        case 'workflows': await loadWorkflows(); break;
    }
}

async function loadOverview() {
    const [agents, goals, treasury, health, tools, learning] = await Promise.all([
        api('agent-registry', 'list'),
        api('goals', 'list'),
        api('treasury', 'report'),
        api('self-healing', 'health'),
        api('tool-genesis', 'stats'),
        api('learning', 'performance'),
    ]);

    let html = '';

    // Agents summary
    const agentList = agents.agents || [];
    const totalAgents = agentList.length;
    const activeAgents = agentList.filter(a => a.status === 'active').length;
    html += card('🤖', `Agents (${activeAgents}/${totalAgents})`,
        statRow('Commander', 'ALFRED', 'purple') +
        statRow('Directors', agentList.filter(a => a.tier === 'director').length, 'blue') +
        statRow('Specialists', agentList.filter(a => a.tier === 'specialist').length) +
        `<button class="btn btn-outline" onclick="loadTab('agents')">View All →</button>`
    );

    // Goals
    const goalList = goals.goals || [];
    const activeGoals = goalList.filter(g => g.status === 'active').length;
    const completedGoals = goalList.filter(g => g.status === 'completed').length;
    html += card('🎯', `Goals (${activeGoals} active)`,
        statRow('Active', activeGoals, 'blue') +
        statRow('Completed', completedGoals, 'green') +
        statRow('Total', goalList.length) +
        `<button class="btn btn-outline" onclick="loadTab('goals')">Manage →</button>`
    );

    // Treasury
    const tr = treasury.report || treasury;
    html += card('💰', 'Treasury',
        statRow('Balance', '$' + (tr.balance || '0'), 'green') +
        statRow('Month Revenue', '$' + (tr.month_revenue || '0'), 'green') +
        statRow('Month Expenses', '$' + (tr.month_expenses || '0'), 'red') +
        `<button class="btn btn-outline" onclick="loadTab('treasury')">Details →</button>`
    );

    // Health
    const h = health.health || health;
    const healthStatus = h.overall === 'healthy' ? 'online' : (h.overall === 'degraded' ? 'warning' : 'error');
    html += card('🏥', 'System Health',
        `<div style="margin-bottom:.5rem">${statusBadge(h.overall || 'unknown', healthStatus)}</div>` +
        statRow('Disk', (h.disk?.usage || '—'), h.disk?.status === 'ok' ? 'green' : 'red') +
        statRow('Memory', (h.memory?.usage || '—'), h.memory?.status === 'ok' ? 'green' : 'red') +
        statRow('CPU Load', (h.cpu?.load_avg || '—'), h.cpu?.status === 'ok' ? 'green' : 'amber') +
        `<button class="btn btn-outline" onclick="loadTab('health')">Details →</button>`
    );

    // Tool Genesis
    const ts = tools.stats || tools;
    html += card('🧬', 'Tool Genesis',
        statRow('Tools Created', ts.total || 0, 'purple') +
        statRow('Deployed', ts.deployed || 0, 'green') +
        statRow('Pipeline', ts.in_pipeline || 0, 'blue') +
        `<button class="btn btn-outline" onclick="loadTab('tools')">Manage →</button>`
    );

    // Learning
    const lr = learning.performance || learning;
    html += card('🧠', 'Learning Engine',
        statRow('Interactions', lr.total_interactions || 0) +
        statRow('Avg Satisfaction', (lr.avg_satisfaction || '—') + '/5', 'green') +
        statRow('Active Experiments', lr.active_experiments || 0, 'blue') +
        `<button class="btn btn-outline" onclick="loadTab('learning')">Details →</button>`
    );

    document.getElementById('overviewGrid').innerHTML = html;
    log('Overview loaded — all systems reporting', 'success');
}

async function loadAgents() {
    const data = await api('agent-registry', 'list');
    const agents = data.agents || [];
    let html = '';

    // Commander
    const commander = agents.find(a => a.tier === 'commander');
    if (commander) {
        html += card('👑', 'ALFRED — Commander',
            statRow('Status', statusBadge(commander.status, commander.status === 'active' ? 'online' : 'warning')) +
            statRow('Tasks', commander.tasks_completed || 0, 'green') +
            statRow('Health', (commander.health_score || 100) + '%', 'green')
        );
    }

    // Directors
    const directors = agents.filter(a => a.tier === 'director');
    directors.forEach(d => {
        const specCount = agents.filter(a => a.parent_agent === d.agent_name).length;
        html += card('⭐', `${d.agent_name} — Director`,
            statRow('Domain', d.domain || '—') +
            statRow('Specialists', specCount) +
            statRow('Status', statusBadge(d.status, d.status === 'active' ? 'online' : 'warning')) +
            statRow('Tasks', d.tasks_completed || 0)
        );
    });

    document.getElementById('agentsGrid').innerHTML = html;
    log(`Loaded ${agents.length} agents`, 'info');
}

async function loadGoals() {
    const data = await api('goals', 'list');
    const goals = data.goals || [];
    let html = '';

    goals.forEach(g => {
        const statusType = g.status === 'completed' ? 'online' : (g.status === 'active' ? 'info' : 'warning');
        html += card(g.priority === 'critical' ? '🔴' : (g.priority === 'high' ? '🟡' : '🟢'), g.title || g.goal_name || 'Goal',
            statRow('Status', statusBadge(g.status, statusType)) +
            statRow('Priority', g.priority || 'medium') +
            statRow('Progress', (g.progress || 0) + '%', 'blue') +
            `<div style="margin-top:.4rem"><div style="height:4px;background:rgba(255,255,255,.06);border-radius:2px"><div style="height:100%;width:${g.progress || 0}%;background:#a855f7;border-radius:2px"></div></div></div>` +
            (g.deadline ? statRow('Deadline', g.deadline) : '')
        );
    });

    if (!goals.length) html = card('🎯', 'No Goals', '<div style="color:rgba(255,255,255,.3);font-size:.8rem">No goals defined yet. Use the Goals API to create them.</div>');

    html += `<div class="card"><h3>➕ Quick Actions</h3>
        <button class="btn btn-primary" onclick="seedGoals()">Seed Default Goals</button>
        <button class="btn btn-success" onclick="api('goals','evaluate').then(()=>loadGoals())">Evaluate All</button>
    </div>`;

    document.getElementById('goalsGrid').innerHTML = html;
}

async function loadTreasury() {
    const [report, forecast] = await Promise.all([
        api('treasury', 'report'),
        api('treasury', 'forecast'),
    ]);
    const r = report.report || report;
    const f = forecast.forecast || forecast;

    let html = card('💰', 'Financial Summary',
        statRow('Balance', '$' + (r.balance || 0), 'green') +
        statRow('Monthly Revenue', '$' + (r.month_revenue || 0), 'green') +
        statRow('Monthly Expenses', '$' + (r.month_expenses || 0), 'red') +
        statRow('Monthly Profit', '$' + ((r.month_revenue || 0) - (r.month_expenses || 0)), (r.month_revenue || 0) > (r.month_expenses || 0) ? 'green' : 'red')
    );

    html += card('📊', 'Forecast',
        statRow('Next Month Revenue', '$' + (f.next_month_revenue || 0), 'blue') +
        statRow('Runway', (f.runway_months || '∞') + ' months', 'green') +
        statRow('YTD Revenue', '$' + (f.ytd_revenue || 0))
    );

    html += `<div class="card"><h3>⚡ Actions</h3>
        <button class="btn btn-primary" onclick="api('accounting','sync-treasury').then(r=>log('Treasury synced: '+JSON.stringify(r),'success'))">Sync from Accounting</button>
    </div>`;

    document.getElementById('treasuryGrid').innerHTML = html;
}

async function loadTools() {
    const [stats, tools] = await Promise.all([
        api('tool-genesis', 'stats'),
        api('tool-genesis', 'tools-created'),
    ]);
    const s = stats.stats || stats;
    const t = tools.tools || [];

    let html = card('📈', 'Genesis Stats',
        statRow('Total Tools', s.total || 0) +
        statRow('Deployed', s.deployed || 0, 'green') +
        statRow('In Pipeline', s.in_pipeline || 0, 'blue') +
        statRow('Rate Limit', '5/day')
    );

    if (t.length) {
        let rows = t.slice(0, 10).map(tool => `<tr>
            <td>${tool.tool_name}</td>
            <td>${statusBadge(tool.stage, tool.stage === 'deployed' ? 'online' : 'info')}</td>
            <td>${tool.language || 'php'}</td>
            <td>${tool.usage_count || 0}</td>
        </tr>`).join('');

        html += card('🔧', 'Created Tools',
            `<table class="data-table"><thead><tr><th>Name</th><th>Stage</th><th>Lang</th><th>Uses</th></tr></thead><tbody>${rows}</tbody></table>`
        );
    }

    document.getElementById('toolsGrid').innerHTML = html;
}

async function loadHealth() {
    const [health, services, incidents] = await Promise.all([
        api('self-healing', 'health'),
        api('self-healing', 'services'),
        api('self-healing', 'incidents'),
    ]);

    const h = health.health || health;
    let html = '';

    // System health
    const checks = ['disk', 'memory', 'cpu', 'database'];
    let checksHtml = '';
    checks.forEach(c => {
        const check = h[c] || {};
        const st = check.status === 'ok' ? 'online' : (check.status === 'warning' ? 'warning' : 'error');
        checksHtml += statRow(c.charAt(0).toUpperCase() + c.slice(1), statusBadge(check.status || 'unknown', st));
    });
    html += card('🏥', 'System Health', checksHtml +
        `<button class="btn btn-success" style="margin-top:.5rem" onclick="api('self-healing','heal',{action:'clear_cache'}).then(r=>log('Cache cleared','success'))">Clear Cache</button>` +
        `<button class="btn btn-outline" onclick="api('self-healing','heal',{action:'optimize_db'}).then(r=>log('DB optimized','success'))">Optimize DB</button>`
    );

    // Services
    const svcList = services.services || [];
    let svcHtml = '';
    svcList.forEach(s => {
        const st = s.status === 'running' ? 'online' : 'error';
        svcHtml += statRow(s.name || s.service, statusBadge(s.status, st));
    });
    html += card('⚙️', 'Services', svcHtml || '<div style="color:rgba(255,255,255,.3);font-size:.8rem">No services detected</div>');

    // Incidents
    const inc = (incidents.incidents || []).slice(0, 5);
    if (inc.length) {
        let incHtml = inc.map(i => `<div style="padding:.3rem 0;border-bottom:1px solid rgba(255,255,255,.04)">
            <span style="font-size:.7rem">${statusBadge(i.severity, i.severity === 'critical' ? 'error' : (i.severity === 'warning' ? 'warning' : 'info'))}</span>
            <span style="font-size:.75rem;margin-left:.3rem">${i.service} — ${i.healing_result || 'pending'}</span>
        </div>`).join('');
        html += card('🔔', 'Recent Incidents', incHtml);
    }

    document.getElementById('healthGrid').innerHTML = html;
}

async function loadLearning() {
    const [perf, patterns, experiments] = await Promise.all([
        api('learning', 'performance'),
        api('learning', 'patterns'),
        api('learning', 'experiments'),
    ]);

    const p = perf.performance || perf;
    let html = card('📊', 'Performance',
        statRow('Total Interactions', p.total_interactions || 0) +
        statRow('Avg Satisfaction', (p.avg_satisfaction || '—') + ' / 5', 'green') +
        statRow('Avg Response Time', (p.avg_response_ms || '—') + 'ms')
    );

    const pat = patterns.patterns || patterns;
    if (pat.top_intents) {
        let intentHtml = (pat.top_intents || []).slice(0, 5).map(i =>
            statRow(i.intent || i.intent_category, i.count || 0)
        ).join('');
        html += card('🔍', 'Top Intents', intentHtml);
    }

    const exp = experiments.experiments || [];
    if (exp.length) {
        let expHtml = exp.map(e =>
            statRow(e.experiment_id || e.name, statusBadge(e.status, e.status === 'active' ? 'info' : 'online'))
        ).join('');
        html += card('🧪', 'A/B Experiments', expHtml);
    }

    document.getElementById('learningGrid').innerHTML = html;
}

async function loadMetaverse() {
    const [presence, leaderboard] = await Promise.all([
        api('metaverse', 'presence'),
        api('metaverse', 'leaderboard'),
    ]);

    const pres = presence;
    let html = card('🌍', 'Kingdom Status',
        statRow('Online Players', pres.online_count || 0, 'green') +
        Object.entries(pres.by_zone || {}).map(([zone, players]) =>
            statRow(zone.replace(/_/g, ' '), players.length + ' players')
        ).join('')
    );

    const leaders = (leaderboard.leaderboard || []).slice(0, 10);
    if (leaders.length) {
        let rows = leaders.map((l, i) => `<tr>
            <td>${['👑','🥈','🥉'][i] || (i+1)}</td>
            <td>${l.display_name}</td>
            <td>${l.title}</td>
            <td class="green">${l.elo_rating}</td>
            <td>${l.win_rate}%</td>
        </tr>`).join('');
        html += card('🏆', 'Leaderboard',
            `<table class="data-table"><thead><tr><th>#</th><th>Player</th><th>Title</th><th>ELO</th><th>Win%</th></tr></thead><tbody>${rows}</tbody></table>`
        );
    }

    document.getElementById('metaverseGrid').innerHTML = html;
}

async function loadDefi() {
    const [portfolio, yields] = await Promise.all([
        api('defi', 'portfolio'),
        api('defi', 'yields'),
    ]);

    const p = portfolio.portfolio || {};
    let html = card('💎', 'Portfolio',
        statRow('Total Invested', '$' + (p.total_invested_usd || 0), 'blue') +
        statRow('Current Value', '$' + (p.total_current_usd || 0), 'green') +
        statRow('P&L', '$' + (p.total_pnl_usd || 0), (p.total_pnl_usd || 0) >= 0 ? 'green' : 'red') +
        statRow('Active Positions', p.active_positions || 0) +
        statRow('Wallets', p.wallets_count || 0)
    );

    const yieldList = (yields.yields || []).slice(0, 8);
    if (yieldList.length) {
        let rows = yieldList.map(y => `<tr>
            <td>${y.protocol}</td>
            <td>${y.chain}</td>
            <td>${y.pool}</td>
            <td class="green">${y.apy}%</td>
            <td>${statusBadge(y.risk_level, y.risk_level === 'low' ? 'online' : 'warning')}</td>
        </tr>`).join('');
        html += card('📈', 'Yield Opportunities',
            `<table class="data-table"><thead><tr><th>Protocol</th><th>Chain</th><th>Pool</th><th>APY</th><th>Risk</th></tr></thead><tbody>${rows}</tbody></table>`
        );
    }

    document.getElementById('defiGrid').innerHTML = html;
}

async function loadComms() {
    const [channels, stats] = await Promise.all([
        api('comm-bus', 'channels'),
        api('comm-bus', 'stats'),
    ]);

    const ch = channels.channels || [];
    let chHtml = ch.map(c =>
        statRow(c.display_name, statusBadge(c.is_enabled ? 'enabled' : 'disabled', c.is_enabled ? 'online' : 'warning'))
    ).join('');
    let html = card('📡', 'Channels (9)', chHtml);

    const s = stats.stats || {};
    html += card('📊', 'Stats',
        statRow('Total Events', s.total_events || 0) +
        statRow('Total Messages', s.total_messages || 0, 'blue') +
        statRow('Sent Today', s.sent_today || 0, 'green')
    );

    html += `<div class="card"><h3>⚡ Triggers</h3>
        <button class="btn btn-primary" onclick="api('comm-bus','trigger',{trigger:'daily_briefing',recipient:'admin',variables:{name:'Admin',revenue:'0',visitors:'0',goals_status:'On Track'}}).then(r=>log('Briefing triggered','success'))">Send Daily Briefing</button>
        <button class="btn btn-success" onclick="api('comm-bus','trigger',{trigger:'health_check',recipient:'admin'}).then(r=>log('Health check sent','success'))">Health Check</button>
    </div>`;

    document.getElementById('commsGrid').innerHTML = html;
}

async function loadWorkflows() {
    const [presets, runs, stats] = await Promise.all([
        api('orchestrator', 'presets'),
        api('orchestrator', 'runs'),
        api('orchestrator', 'stats'),
    ]);

    const pre = presets.presets || {};
    let presHtml = Object.entries(pre).map(([key, p]) =>
        `<div style="padding:.4rem 0;border-bottom:1px solid rgba(255,255,255,.04)">
            <div style="font-size:.8rem;font-weight:600">${p.name}</div>
            <div style="font-size:.7rem;color:rgba(255,255,255,.3);margin-top:.15rem">${p.description}</div>
            <div style="font-size:.65rem;color:rgba(255,255,255,.25);margin-top:.1rem">${p.node_count} nodes · ${p.edge_count} edges</div>
            <button class="btn btn-outline" onclick="api('orchestrator','execute',{preset:'${key}',input:{}}).then(r=>{log('Workflow ${key} executed: '+JSON.stringify(r.output?.result||'done'),'success');loadWorkflows()})">Execute</button>
        </div>`
    ).join('');
    let html = card('🔀', 'Workflow Presets', presHtml);

    const s = stats.stats || {};
    html += card('📊', 'Stats',
        statRow('Total Workflows', s.total_workflows || 0) +
        statRow('Total Runs', s.total_runs || 0) +
        statRow('Success Rate', (s.success_rate || 0) + '%', 'green') +
        statRow('Avg Execution', (s.avg_execution_ms || 0) + 'ms') +
        statRow('Presets', s.preset_count || 0, 'purple')
    );

    const runList = (runs.runs || []).slice(0, 5);
    if (runList.length) {
        let rows = runList.map(r => `<tr>
            <td style="font-size:.65rem">${r.run_id.slice(0, 12)}...</td>
            <td>${r.workflow_id.slice(0, 15)}</td>
            <td>${statusBadge(r.status, r.status === 'completed' ? 'online' : (r.status === 'failed' ? 'error' : 'info'))}</td>
            <td>${r.execution_ms}ms</td>
        </tr>`).join('');
        html += card('📜', 'Recent Runs',
            `<table class="data-table"><thead><tr><th>Run</th><th>Workflow</th><th>Status</th><th>Time</th></tr></thead><tbody>${rows}</tbody></table>`
        );
    }

    document.getElementById('workflowsGrid').innerHTML = html;
}

// ─── Seed helpers ──────────────────────────────────────────────
async function seedGoals() {
    log('Seeding default goals...', 'info');
    await api('goals', 'seed');
    log('Goals seeded ✓', 'success');
    loadGoals();
}

// ─── Init ──────────────────────────────────────────────────────
loadOverview();
log('Alfred Sovereignty Dashboard initialized', 'success');
</script>
</body>
</html>
