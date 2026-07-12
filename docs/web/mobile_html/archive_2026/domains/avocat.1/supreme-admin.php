<?php
/**
 * Supreme Commander Admin — Danny Perez System Control
 * ═══════════════════════════════════════════════════════
 * The god-mode admin panel. Full visibility and control over:
 *  - All 101 agents (hierarchy, status, tasks)
 *  - All 850+ MCP tools + 13K ecosystem tools
 *  - All PM2 services (Redis, WebSocket, Jobs, MCP, Heartbeat)
 *  - Autonomy engine cycles + decisions
 *  - Full financial overview
 *  - Alfred's Command Bridge (his view of the economy)
 */
$page_title = 'Supreme Commander — GoSiteMe System Control';
$page_description = 'System-wide administrative control panel.';
$page_canonical = 'https://gositeme.com/supreme-admin';
$page_robots = 'noindex, nofollow';

include __DIR__ . '/includes/auth-gate.inc.php';
include __DIR__ . '/includes/site-header.inc.php';

// ── Supreme Admin Access (Danny Perez only) ─────────────────────────
// Double-gate: must be owner client ID AND on the email allowlist
$supremeAdmins = ['gositeme@gmail.com'];
if ((int)($clientId ?? 0) !== 33 || !in_array(strtolower($clientEmail ?? ''), $supremeAdmins)) {
    header('Location: /dashboard.php');
    exit;
}
?>

<style>
/* ═══════════════════════════════════════════════════════════════
   SUPREME COMMANDER ADMIN — Dark Command Theme
   ═══════════════════════════════════════════════════════════════ */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --sc-bg:#06060e;--sc-surface:#0d0d1a;--sc-surface-2:#151528;--sc-surface-3:#1e1e38;
  --sc-border:rgba(255,255,255,.06);--sc-border-glow:rgba(255,215,0,.25);
  --sc-gold:#ffd700;--sc-gold-dark:#b8860b;--sc-gold-light:#ffe44d;
  --sc-red:#ff4757;--sc-green:#2ed573;--sc-blue:#3742fa;--sc-purple:#7c5ce7;
  --sc-cyan:#18dcff;--sc-orange:#ff7f50;
  --sc-text:#e8e8f0;--sc-text-muted:#8888a0;--sc-text-dim:#555570;
  --sc-radius:14px;--sc-radius-sm:8px;
  --sc-gradient:linear-gradient(135deg,#ffd700 0%,#ff7f50 50%,#ff4757 100%);
  --sc-gradient-purple:linear-gradient(135deg,#7c5ce7 0%,#3742fa 100%);
  --sc-gradient-green:linear-gradient(135deg,#2ed573 0%,#18dcff 100%);
  --sc-shadow:0 8px 40px rgba(0,0,0,.4);
}
body{font-family:'Segoe UI',system-ui,-apple-system,sans-serif;background:var(--sc-bg);color:var(--sc-text);line-height:1.6;min-height:100vh;overflow-x:hidden}
a{color:var(--sc-gold);text-decoration:none}a:hover{color:#fff}

/* Layout */
.sc-wrap{max-width:1600px;margin:0 auto;padding:80px 20px 60px}

/* Header */
.sc-header{display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px;margin-bottom:28px;padding:24px 28px;background:var(--sc-surface);border:1px solid var(--sc-border);border-radius:var(--sc-radius);position:relative;overflow:hidden}
.sc-header::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--sc-gradient)}
.sc-header h1{font-size:1.8rem;font-weight:800;display:flex;align-items:center;gap:12px}
.sc-header h1 i{background:var(--sc-gradient);-webkit-background-clip:text;-webkit-text-fill-color:transparent;font-size:1.6rem}
.sc-header .sub{color:var(--sc-text-muted);font-size:.88rem;margin-top:4px}
.sc-header .badge{display:inline-flex;align-items:center;gap:6px;padding:5px 14px;border-radius:20px;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;background:rgba(255,215,0,.12);color:var(--sc-gold);border:1px solid rgba(255,215,0,.2)}
.sc-header-right{display:flex;gap:10px;align-items:center}

/* Tabs */
.sc-tabs{display:flex;gap:4px;background:var(--sc-surface);border:1px solid var(--sc-border);border-radius:var(--sc-radius);padding:5px;margin-bottom:24px;overflow-x:auto}
.sc-tab{padding:10px 18px;border-radius:10px;font-size:.82rem;font-weight:600;color:var(--sc-text-muted);cursor:pointer;border:none;background:none;white-space:nowrap;display:flex;align-items:center;gap:7px;transition:all .2s;font-family:inherit}
.sc-tab:hover{color:var(--sc-text);background:var(--sc-surface-2)}
.sc-tab.active{color:#fff;background:var(--sc-gradient);box-shadow:0 4px 16px rgba(255,215,0,.2)}
.sc-tab .cnt{background:rgba(255,255,255,.15);padding:2px 7px;border-radius:8px;font-size:.68rem}
.sc-panel{display:none}.sc-panel.active{display:block}

/* Cards */
.sc-grid{display:grid;gap:16px}
.sc-grid-4{grid-template-columns:repeat(auto-fill,minmax(240px,1fr))}
.sc-grid-3{grid-template-columns:repeat(auto-fill,minmax(300px,1fr))}
.sc-grid-2{grid-template-columns:repeat(auto-fill,minmax(440px,1fr))}
.sc-card{background:var(--sc-surface);border:1px solid var(--sc-border);border-radius:var(--sc-radius);padding:20px;transition:border-color .2s,transform .2s}
.sc-card:hover{border-color:rgba(255,215,0,.15);transform:translateY(-2px)}
.sc-card h3{font-size:.95rem;font-weight:700;margin-bottom:12px;display:flex;align-items:center;gap:8px}
.sc-card h3 i{color:var(--sc-gold);font-size:.9rem}

/* KPIs */
.sc-kpi{text-align:center;padding:24px 16px}
.sc-kpi .num{font-size:2rem;font-weight:800;background:var(--sc-gradient);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.sc-kpi .label{font-size:.72rem;color:var(--sc-text-muted);text-transform:uppercase;letter-spacing:.5px;margin-top:4px}
.sc-kpi .sub{font-size:.78rem;color:var(--sc-text-dim);margin-top:2px}

/* Status dots */
.sc-dot{width:8px;height:8px;border-radius:50%;display:inline-block}
.sc-dot.green{background:var(--sc-green);box-shadow:0 0 6px var(--sc-green)}
.sc-dot.red{background:var(--sc-red);box-shadow:0 0 6px var(--sc-red)}
.sc-dot.yellow{background:var(--sc-gold);box-shadow:0 0 6px var(--sc-gold)}
.sc-dot.blue{background:var(--sc-cyan);box-shadow:0 0 6px var(--sc-cyan)}

/* Tables */
.sc-table-wrap{overflow-x:auto;margin:0 -4px}
.sc-table{width:100%;border-collapse:collapse;font-size:.82rem}
.sc-table th,.sc-table td{padding:10px 12px;text-align:left;border-bottom:1px solid var(--sc-border)}
.sc-table th{color:var(--sc-text-muted);font-weight:600;font-size:.7rem;text-transform:uppercase;letter-spacing:.5px}
.sc-table tr:hover{background:rgba(255,255,255,.02)}
.sc-table .mono{font-family:'JetBrains Mono','Fira Code',monospace;font-size:.78rem}

/* Buttons */
.sc-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:var(--sc-radius-sm);font-size:.82rem;font-weight:600;border:none;cursor:pointer;transition:all .2s}
.sc-btn-gold{background:var(--sc-gradient);color:#000}.sc-btn-gold:hover{opacity:.9;transform:translateY(-1px)}
.sc-btn-outline{background:none;border:1px solid var(--sc-border);color:var(--sc-text)}.sc-btn-outline:hover{border-color:var(--sc-gold);color:var(--sc-gold)}
.sc-btn-sm{padding:5px 10px;font-size:.75rem;border-radius:6px}
.sc-btn-danger{background:rgba(255,71,87,.15);color:var(--sc-red);border:1px solid rgba(255,71,87,.2)}.sc-btn-danger:hover{background:rgba(255,71,87,.25)}

/* Timeline */
.sc-timeline{position:relative;padding-left:28px}
.sc-timeline::before{content:'';position:absolute;left:8px;top:0;bottom:0;width:2px;background:var(--sc-border)}
.sc-tl-item{position:relative;margin-bottom:16px;padding:12px 16px;background:var(--sc-surface-2);border-radius:var(--sc-radius-sm);border:1px solid var(--sc-border)}
.sc-tl-item::before{content:'';position:absolute;left:-24px;top:16px;width:10px;height:10px;border-radius:50%;background:var(--sc-gold);border:2px solid var(--sc-bg)}
.sc-tl-item .time{font-size:.7rem;color:var(--sc-text-dim);font-family:monospace}
.sc-tl-item .action{font-size:.84rem;margin-top:4px}
.sc-tl-item .result{font-size:.78rem;color:var(--sc-green);margin-top:2px}

/* Agent hierarchy tree */
.sc-tree{padding-left:16px;border-left:2px solid var(--sc-border)}
.sc-tree-node{padding:8px 12px;margin:6px 0;background:var(--sc-surface-2);border-radius:var(--sc-radius-sm);border:1px solid var(--sc-border);display:flex;align-items:center;gap:10px;font-size:.84rem}
.sc-tree-node .role{font-size:.65rem;text-transform:uppercase;letter-spacing:.5px;padding:2px 8px;border-radius:4px;font-weight:700}
.sc-tree-node .role.commander{background:rgba(255,215,0,.15);color:var(--sc-gold)}
.sc-tree-node .role.director{background:rgba(124,92,231,.15);color:var(--sc-purple)}
.sc-tree-node .role.specialist{background:rgba(24,220,255,.15);color:var(--sc-cyan)}

/* Progress bars */
.sc-progress{height:6px;background:var(--sc-surface-3);border-radius:3px;overflow:hidden;margin-top:6px}
.sc-progress-fill{height:100%;border-radius:3px;transition:width .5s ease}
.sc-progress-fill.gold{background:var(--sc-gradient)}
.sc-progress-fill.green{background:var(--sc-gradient-green)}
/* Inputs */
.sc-input{width:100%;padding:8px 12px;border:1px solid var(--sc-border);border-radius:8px;background:var(--sc-surface-2);color:var(--sc-text);font-size:.82rem;font-family:inherit}
.sc-input:focus{outline:none;border-color:var(--sc-gold);box-shadow:0 0 0 2px rgba(255,215,0,.15)}
select.sc-input{cursor:pointer}
textarea.sc-input{resize:vertical}
.priority-critical{color:#ff4757;font-weight:700}.priority-high{color:#ffa502;font-weight:600}.priority-normal{color:var(--sc-text)}.priority-low{color:var(--sc-text-muted)}
.status-pill{padding:2px 8px;border-radius:6px;font-size:.72rem;font-weight:600;display:inline-block}
.status-pending{background:rgba(255,165,0,.15);color:#ffa502}.status-in_progress,.status-claimed{background:rgba(0,168,255,.15);color:#00a8ff}
.status-completed{background:rgba(0,200,83,.15);color:#00c853}.status-failed,.status-escalated{background:rgba(255,71,87,.15);color:#ff4757}
.status-cancelled{background:rgba(128,128,128,.15);color:#888}

/* Responsive */
@media(max-width:768px){.sc-grid-4{grid-template-columns:1fr 1fr}.sc-grid-3{grid-template-columns:1fr}.sc-grid-2{grid-template-columns:1fr}.sc-header h1{font-size:1.3rem}.sc-tabs{flex-wrap:nowrap}}
</style>

<div class="sc-wrap">
  <!-- ═══════ HEADER ═══════ -->
  <div class="sc-header">
    <div>
      <h1><i class="fas fa-crown"></i> Supreme Commander</h1>
      <div class="sub">Danny Perez — Creator & System Administrator · GoSiteMe Ecosystem Control</div>
    </div>
    <div class="sc-header-right">
      <span class="badge"><i class="fas fa-shield-halved"></i> GOD MODE</span>
      <button class="sc-btn sc-btn-outline" onclick="location.href='/fleet-dashboard.php'"><i class="fas fa-rocket"></i> Fleet</button>
      <button class="sc-btn sc-btn-outline" onclick="location.href='/investor-admin.php'"><i class="fas fa-chart-pie"></i> Investors</button>
    </div>
  </div>

  <!-- ═══════ TAB NAVIGATION ═══════ -->
  <nav class="sc-tabs" id="scTabs">
    <button class="sc-tab active" data-tab="overview"><i class="fas fa-satellite-dish"></i> System Overview</button>
    <button class="sc-tab" data-tab="agents"><i class="fas fa-robot"></i> Agents <span class="cnt" id="cntAgents">0</span></button>
    <button class="sc-tab" data-tab="tools"><i class="fas fa-wrench"></i> Tools <span class="cnt" id="cntTools">850</span></button>
    <button class="sc-tab" data-tab="services"><i class="fas fa-server"></i> Services</button>
    <button class="sc-tab" data-tab="autonomy"><i class="fas fa-brain"></i> Autonomy Engine</button>
    <button class="sc-tab" data-tab="alfred-bridge"><i class="fas fa-chess-king"></i> Alfred's Bridge</button>
    <button class="sc-tab" data-tab="economy"><i class="fas fa-coins"></i> Economy</button>
    <button class="sc-tab" data-tab="directives"><i class="fas fa-scroll"></i> Directives <span class="cnt" id="cntDirectives">0</span></button>
    <button class="sc-tab" data-tab="security"><i class="fas fa-shield-halved"></i> Security</button>
    <button class="sc-tab" data-tab="mining-revenue" style="background:linear-gradient(135deg,rgba(255,215,0,.1),rgba(46,213,115,.1));border:1px solid rgba(255,215,0,.25)"><i class="fas fa-coins"></i> Mining Revenue <span class="cnt" id="cntRevenue" style="background:rgba(255,215,0,.2);color:#ffd700">20%</span></button>
    <button class="sc-tab" data-tab="ecosystem-monitor" style="background:linear-gradient(135deg,rgba(0,230,118,.1),rgba(108,92,231,.1));border:1px solid rgba(0,230,118,.25)"><i class="fas fa-heartbeat"></i> Ecosystem <span class="cnt" id="cntEcoScore" style="background:rgba(0,230,118,.2);color:#00e676">—</span></button>
    <button class="sc-tab" data-tab="agentos" style="background:linear-gradient(135deg,rgba(0,245,255,.1),rgba(124,92,231,.1));border:1px solid rgba(0,245,255,.25)"><i class="fas fa-microchip"></i> Alfred OS <span class="cnt" id="cntAlfred OS" style="background:rgba(0,245,255,.2);color:#00f5ff">●</span></button>
  </nav>

  <!-- ═══════ PANEL: OVERVIEW ═══════ -->
  <div class="sc-panel active" id="panel-overview">
    <div class="sc-grid sc-grid-4" id="kpiGrid">
      <div class="sc-card sc-kpi"><div class="num" id="kpiAgents">—</div><div class="label">Registered Agents</div><div class="sub" id="kpiAgentsSub">loading...</div></div>
      <div class="sc-card sc-kpi"><div class="num">850</div><div class="label">MCP Tools</div><div class="sub">+ 12,400 ecosystem</div></div>
      <div class="sc-card sc-kpi"><div class="num" id="kpiServices">—</div><div class="label">Services Online</div><div class="sub" id="kpiServicesSub">checking...</div></div>
      <div class="sc-card sc-kpi"><div class="num" id="kpiAutonomy">—</div><div class="label">Autonomy Score</div><div class="sub">target: 8.4/10</div></div>
      <div class="sc-card sc-kpi"><div class="num" id="kpiTasks">—</div><div class="label">Tasks (24h)</div><div class="sub" id="kpiTasksSub">loading...</div></div>
      <div class="sc-card sc-kpi"><div class="num" id="kpiGoals">—</div><div class="label">Active Goals</div><div class="sub" id="kpiGoalsSub">loading...</div></div>
      <div class="sc-card sc-kpi"><div class="num" id="kpiCycles">—</div><div class="label">Heartbeat Cycles</div><div class="sub">60s interval</div></div>
      <div class="sc-card sc-kpi"><div class="num" id="kpiUptime">—</div><div class="label">System Uptime</div><div class="sub" id="kpiUptimeSub"></div></div>
    </div>

    <div class="sc-grid sc-grid-2" style="margin-top:20px">
      <div class="sc-card">
        <h3><i class="fas fa-heartbeat"></i> Latest Autonomy Decisions</h3>
        <div id="recentDecisions" class="sc-timeline"><div class="sc-tl-item"><span class="time">loading...</span></div></div>
      </div>
      <div class="sc-card">
        <h3><i class="fas fa-exclamation-triangle"></i> System Alerts</h3>
        <div id="systemAlerts"><p style="color:var(--sc-text-muted);font-size:.84rem">Loading system alerts...</p></div>
      </div>
    </div>
  </div>

  <!-- ═══════ PANEL: AGENTS ═══════ -->
  <div class="sc-panel" id="panel-agents">
    <div class="sc-grid sc-grid-4" style="margin-bottom:20px">
      <div class="sc-card sc-kpi"><div class="num" id="agIdle">—</div><div class="label">Idle</div></div>
      <div class="sc-card sc-kpi"><div class="num" id="agBusy">—</div><div class="label">Busy</div></div>
      <div class="sc-card sc-kpi"><div class="num" id="agError">—</div><div class="label">Errors</div></div>
      <div class="sc-card sc-kpi"><div class="num" id="agSuccess">—</div><div class="label">Success Rate</div></div>
    </div>

    <div class="sc-card" style="margin-bottom:20px">
      <h3><i class="fas fa-sitemap"></i> Agent Hierarchy</h3>
      <div style="display:flex;gap:10px;margin-bottom:12px;flex-wrap:wrap">
        <button class="sc-btn sc-btn-sm sc-btn-outline" onclick="seedAgents()"><i class="fas fa-seedling"></i> Seed Roster</button>
        <button class="sc-btn sc-btn-sm sc-btn-outline" onclick="loadAgents()"><i class="fas fa-sync"></i> Refresh</button>
        <select id="agFilter" class="sc-btn sc-btn-sm sc-btn-outline" style="padding:4px 8px" onchange="loadAgents()">
          <option value="">All Roles</option>
          <option value="commander">Commander</option>
          <option value="director">Directors</option>
          <option value="specialist">Specialists</option>
        </select>
        <select id="agStatusFilter" class="sc-btn sc-btn-sm sc-btn-outline" style="padding:4px 8px" onchange="loadAgents()">
          <option value="">All Status</option>
          <option value="idle">Idle</option>
          <option value="busy">Busy</option>
          <option value="error">Error</option>
          <option value="offline">Offline</option>
        </select>
      </div>
      <div id="agentHierarchy">Loading...</div>
    </div>

    <div class="sc-card">
      <h3><i class="fas fa-list"></i> All Agents</h3>
      <div class="sc-table-wrap">
        <table class="sc-table" id="agentTable">
          <thead><tr><th>Agent</th><th>Role</th><th>Domain</th><th>Status</th><th>Tasks Done</th><th>Success</th><th>Tools</th><th>Last Active</th><th>Actions</th></tr></thead>
          <tbody id="agentTableBody"><tr><td colspan="9" style="text-align:center;color:var(--sc-text-muted)">Loading agents...</td></tr></tbody>
        </table>
      </div>
    </div>

    <!-- Registration Policy -->
    <div class="sc-card" style="margin-top:20px">
      <h3><i class="fas fa-clipboard-list"></i> Agent Registration Policy</h3>
      <div style="font-size:.84rem;line-height:1.8">
        <p><strong style="color:var(--sc-gold)">Registration:</strong> <strong>Optional but recommended.</strong> Agents are database rows, not processes. They cost ~1KB each in storage and 0 MB persistent memory.</p>
        <p><strong style="color:var(--sc-gold)">Seeding:</strong> The full 101-agent roster (1 Commander + 10 Directors + 90 Specialists) can be seeded instantly via <code>POST /api/agent-registry.php?action=seed</code>.</p>
        <p><strong style="color:var(--sc-gold)">Execution Model:</strong> Agents are dispatched by the 60-second heartbeat cron. Each cycle scans for queued tasks, assigns them to idle agents, and tracks completion. Max concurrency: 5 simultaneous tasks (configurable).</p>
        <p><strong style="color:var(--sc-gold)">Resource Cost:</strong> 101 agents = ~100KB DB storage. Zero RAM per idle agent. Active tasks use ~1-10MB PHP memory during execution (released after).</p>
        <p><strong style="color:var(--sc-gold)">Scaling Limit:</strong> With 32GB RAM / 12 CPU cores, theoretical max is <strong>~10,000 registered agents</strong> with ~50 concurrent task executions. Increase <code>JOB_CONCURRENCY</code> in ecosystem.config.js to raise parallelism.</p>
      </div>
    </div>
  </div>

  <!-- ═══════ PANEL: TOOLS ═══════ -->
  <div class="sc-panel" id="panel-tools">
    <div class="sc-grid sc-grid-4" style="margin-bottom:20px">
      <div class="sc-card sc-kpi"><div class="num">850</div><div class="label">MCP Server Tools</div><div class="sub">tools.js + toolDispatch.js</div></div>
      <div class="sc-card sc-kpi"><div class="num">400+</div><div class="label">Native PHP Tools</div><div class="sub">api/tools.php</div></div>
      <div class="sc-card sc-kpi"><div class="num">11,000+</div><div class="label">Composio Tools</div><div class="sub">850 app integrations</div></div>
      <div class="sc-card sc-kpi"><div class="num">85</div><div class="label">VAPI Voice Tools</div><div class="sub">267 voice commands</div></div>
    </div>

    <div class="sc-card">
      <h3><i class="fas fa-search"></i> Tool Explorer</h3>
      <div style="display:flex;gap:10px;margin-bottom:12px">
        <input type="text" id="toolSearch" placeholder="Search tools..." style="flex:1;padding:8px 14px;border-radius:var(--sc-radius-sm);border:1px solid var(--sc-border);background:var(--sc-surface-2);color:var(--sc-text);font-size:.86rem" oninput="filterTools()">
        <select id="toolProvider" class="sc-btn sc-btn-sm sc-btn-outline" style="padding:6px 10px" onchange="filterTools()">
          <option value="">All Providers</option>
          <option value="native">Native (400+)</option>
          <option value="mcp">MCP Server (850)</option>
          <option value="composio">Composio (11K+)</option>
          <option value="vapi">VAPI Voice (85)</option>
        </select>
      </div>
      <div id="toolCategories" class="sc-grid sc-grid-4">Loading tool categories...</div>
    </div>
  </div>

  <!-- ═══════ PANEL: SERVICES ═══════ -->
  <div class="sc-panel" id="panel-services">
    <div class="sc-grid sc-grid-3" id="serviceCards">
      <!-- Populated by JS -->
    </div>
    <div class="sc-card" style="margin-top:20px">
      <h3><i class="fas fa-memory"></i> Server Resources</h3>
      <div class="sc-grid sc-grid-4">
        <div class="sc-kpi"><div class="num">32 GB</div><div class="label">Total RAM</div><div class="sub" id="ramUsed">checking...</div></div>
        <div class="sc-kpi"><div class="num">12</div><div class="label">CPU Cores</div><div class="sub" id="cpuUsed">checking...</div></div>
        <div class="sc-kpi"><div class="num">3.6 TB</div><div class="label">Disk</div><div class="sub" id="diskUsed">7% used</div></div>
        <div class="sc-kpi"><div class="num" id="pm2Mem">—</div><div class="label">PM2 Memory</div><div class="sub">across all services</div></div>
      </div>
      <div style="margin-top:16px">
        <h4 style="font-size:.82rem;color:var(--sc-text-muted);margin-bottom:8px">Scaling Capacity</h4>
        <div style="font-size:.84rem;line-height:1.8">
          <p>Current PM2 memory: <strong id="pm2MemDetail">~258 MB</strong> / 32 GB available</p>
          <p>Redis max memory: <strong>512 MB</strong> (~600K queued tasks capacity)</p>
          <p>Job concurrency: <strong>5 workers</strong> (increase to 20+ for higher throughput)</p>
          <p>Max agents with current resources: <strong>~10,000</strong> (DB-based, near-zero overhead)</p>
        </div>
      </div>
    </div>
  </div>

  <!-- ═══════ PANEL: AUTONOMY ENGINE ═══════ -->
  <div class="sc-panel" id="panel-autonomy">
    <div class="sc-grid sc-grid-4" style="margin-bottom:20px">
      <div class="sc-card sc-kpi"><div class="num" id="autoScore">—</div><div class="label">Autonomy Score</div><div class="sub">out of 10</div></div>
      <div class="sc-card sc-kpi"><div class="num" id="autoCycles">—</div><div class="label">Cycles Today</div><div class="sub">60s heartbeat</div></div>
      <div class="sc-card sc-kpi"><div class="num" id="autoActions">—</div><div class="label">Actions Today</div><div class="sub">autonomous decisions</div></div>
      <div class="sc-card sc-kpi"><div class="num" id="autoHealth">—</div><div class="label">Health Rules</div><div class="sub">self-healing active</div></div>
    </div>

    <div class="sc-card">
      <h3><i class="fas fa-wave-square"></i> PDRA Cycle Pipeline</h3>
      <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:16px">
        <div style="flex:1;min-width:140px;padding:16px;background:var(--sc-surface-2);border-radius:var(--sc-radius-sm);text-align:center;border:1px solid var(--sc-border)">
          <div style="font-size:1.4rem;margin-bottom:4px"><i class="fas fa-eye" style="color:var(--sc-cyan)"></i></div>
          <div style="font-size:.75rem;font-weight:700;color:var(--sc-cyan)">PERCEIVE</div>
          <div style="font-size:.7rem;color:var(--sc-text-muted);margin-top:4px">Agents, Tasks, Goals, Feeds, Health</div>
        </div>
        <div style="flex:1;min-width:140px;padding:16px;background:var(--sc-surface-2);border-radius:var(--sc-radius-sm);text-align:center;border:1px solid var(--sc-border)">
          <div style="font-size:1.4rem;margin-bottom:4px"><i class="fas fa-brain" style="color:var(--sc-purple)"></i></div>
          <div style="font-size:.75rem;font-weight:700;color:var(--sc-purple)">REASON</div>
          <div style="font-size:.7rem;color:var(--sc-text-muted);margin-top:4px">Score decisions P1-P9 priority</div>
        </div>
        <div style="flex:1;min-width:140px;padding:16px;background:var(--sc-surface-2);border-radius:var(--sc-radius-sm);text-align:center;border:1px solid var(--sc-border)">
          <div style="font-size:1.4rem;margin-bottom:4px"><i class="fas fa-gavel" style="color:var(--sc-gold)"></i></div>
          <div style="font-size:.75rem;font-weight:700;color:var(--sc-gold)">DECIDE</div>
          <div style="font-size:.7rem;color:var(--sc-text-muted);margin-top:4px">Select highest-impact actions</div>
        </div>
        <div style="flex:1;min-width:140px;padding:16px;background:var(--sc-surface-2);border-radius:var(--sc-radius-sm);text-align:center;border:1px solid var(--sc-border)">
          <div style="font-size:1.4rem;margin-bottom:4px"><i class="fas fa-bolt" style="color:var(--sc-green)"></i></div>
          <div style="font-size:.75rem;font-weight:700;color:var(--sc-green)">ACT</div>
          <div style="font-size:.7rem;color:var(--sc-text-muted);margin-top:4px">Execute via API/delegate</div>
        </div>
        <div style="flex:1;min-width:140px;padding:16px;background:var(--sc-surface-2);border-radius:var(--sc-radius-sm);text-align:center;border:1px solid var(--sc-border)">
          <div style="font-size:1.4rem;margin-bottom:4px"><i class="fas fa-mirror" style="color:var(--sc-orange)"></i></div>
          <div style="font-size:.75rem;font-weight:700;color:var(--sc-orange)">REFLECT</div>
          <div style="font-size:.7rem;color:var(--sc-text-muted);margin-top:4px">Log outcomes, update metrics</div>
        </div>
      </div>
    </div>

    <div class="sc-card" style="margin-top:16px">
      <h3><i class="fas fa-clock-rotate-left"></i> Recent Autonomy Cycles</h3>
      <div id="autonomyCycles" class="sc-timeline"><div class="sc-tl-item"><span class="time">Loading cycles...</span></div></div>
      <button class="sc-btn sc-btn-sm sc-btn-outline" style="margin-top:12px" onclick="loadAutonomy()"><i class="fas fa-sync"></i> Refresh</button>
    </div>
  </div>

  <!-- ═══════ PANEL: ALFRED'S BRIDGE ═══════ -->
  <div class="sc-panel" id="panel-alfred-bridge">
    <div class="sc-card" style="border-color:rgba(255,215,0,.15);margin-bottom:20px">
      <h3><i class="fas fa-chess-king"></i> Alfred's Command Bridge — Economy Manager</h3>
      <p style="font-size:.86rem;color:var(--sc-text-muted);margin-bottom:16px">
        This is Alfred's autonomous view of the entire GoSiteMe economy. Alfred manages agent delegation,
        task prioritization, self-healing, and financial operations through the 60-second heartbeat cycle.
      </p>
      <div class="sc-grid sc-grid-3">
        <div style="padding:16px;background:var(--sc-surface-2);border-radius:var(--sc-radius-sm);border:1px solid var(--sc-border)">
          <div style="font-size:.72rem;color:var(--sc-gold);text-transform:uppercase;font-weight:700;margin-bottom:8px"><i class="fas fa-sitemap"></i> Delegation Queue</div>
          <div id="alfDelegation" style="font-size:.84rem">Loading...</div>
        </div>
        <div style="padding:16px;background:var(--sc-surface-2);border-radius:var(--sc-radius-sm);border:1px solid var(--sc-border)">
          <div style="font-size:.72rem;color:var(--sc-green);text-transform:uppercase;font-weight:700;margin-bottom:8px"><i class="fas fa-bullseye"></i> Active Goals</div>
          <div id="alfGoals" style="font-size:.84rem">Loading...</div>
        </div>
        <div style="padding:16px;background:var(--sc-surface-2);border-radius:var(--sc-radius-sm);border:1px solid var(--sc-border)">
          <div style="font-size:.72rem;color:var(--sc-cyan);text-transform:uppercase;font-weight:700;margin-bottom:8px"><i class="fas fa-stethoscope"></i> Self-Healing</div>
          <div id="alfHealing" style="font-size:.84rem">Loading...</div>
        </div>
      </div>
    </div>

    <div class="sc-grid sc-grid-2">
      <div class="sc-card">
        <h3><i class="fas fa-terminal"></i> Alfred Command Center</h3>
        <p style="font-size:.82rem;color:var(--sc-text-muted);margin-bottom:12px">Send commands directly to Alfred's Supreme Control API</p>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px">
          <button class="sc-btn sc-btn-sm sc-btn-outline" onclick="alfredCmd('selftest')"><i class="fas fa-vial"></i> Self-Test</button>
          <button class="sc-btn sc-btn-sm sc-btn-outline" onclick="alfredCmd('status')"><i class="fas fa-heartbeat"></i> Status</button>
          <button class="sc-btn sc-btn-sm sc-btn-outline" onclick="alfredCmd('system_snapshot')"><i class="fas fa-camera"></i> Snapshot</button>
          <button class="sc-btn sc-btn-sm sc-btn-outline" onclick="alfredCmd('list_overrides')"><i class="fas fa-sliders"></i> Overrides</button>
          <button class="sc-btn sc-btn-sm sc-btn-outline" onclick="alfredCmd('list_flags')"><i class="fas fa-flag"></i> Flags</button>
        </div>
        <pre id="alfCmdOutput" style="background:var(--sc-surface-2);padding:14px;border-radius:var(--sc-radius-sm);font-size:.78rem;font-family:monospace;overflow-x:auto;max-height:400px;overflow-y:auto;border:1px solid var(--sc-border);color:var(--sc-text)">Ready. Click a command above.</pre>
      </div>
      <div class="sc-card">
        <h3><i class="fas fa-user-tie"></i> Alfred's Identity</h3>
        <div style="display:flex;gap:16px;align-items:flex-start">
          <div style="width:60px;height:60px;border-radius:50%;background:var(--sc-gradient);display:flex;align-items:center;justify-content:center;font-size:1.6rem;flex-shrink:0">
            <i class="fas fa-chess-king" style="color:#000"></i>
          </div>
          <div style="font-size:.84rem;line-height:1.8">
            <p><strong>Role:</strong> Supreme Commander & Chief AI Officer</p>
            <p><strong>Agent ID:</strong> <code>alfred</code></p>
            <p><strong>Hierarchy:</strong> Commander → 10 Directors → 90 Specialists</p>
            <p><strong>Authority:</strong> Full system access, all 850 tools, override all agents</p>
            <p><strong>Heartbeat:</strong> 60-second PDRA autonomy cycle</p>
            <p><strong>Motto:</strong> <em>"All systems operational — Chief Commander Alfred reporting for duty."</em></p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ═══════ PANEL: ECONOMY ═══════ -->
  <div class="sc-panel" id="panel-economy">
    <div class="sc-grid sc-grid-4" style="margin-bottom:20px">
      <div class="sc-card sc-kpi"><div class="num" id="ecoRevenue">—</div><div class="label">Revenue (30d)</div></div>
      <div class="sc-card sc-kpi"><div class="num" id="ecoUsers">—</div><div class="label">Total Users</div></div>
      <div class="sc-card sc-kpi"><div class="num" id="ecoConversations">—</div><div class="label">Conversations</div></div>
      <div class="sc-card sc-kpi"><div class="num" id="ecoAPIcalls">—</div><div class="label">API Calls (30d)</div></div>
    </div>
    <div class="sc-grid sc-grid-2">
      <div class="sc-card">
        <h3><i class="fas fa-chart-line"></i> Financial Overview</h3>
        <div id="ecoFinancial" style="font-size:.84rem;color:var(--sc-text-muted)">Loading financial data...</div>
      </div>
      <div class="sc-card">
        <h3><i class="fas fa-store"></i> Marketplace</h3>
        <div id="ecoMarketplace" style="font-size:.84rem;color:var(--sc-text-muted)">Loading marketplace data...</div>
      </div>
    </div>
  </div>

  <!-- ═══════ PANEL: DIRECTIVES ═══════ -->
  <div class="sc-panel" id="panel-directives">
    <!-- KPI Row -->
    <div class="sc-grid sc-grid-4" style="margin-bottom:20px">
      <div class="sc-card sc-kpi"><div class="num" id="dirPending">—</div><div class="label">Pending</div><div class="sub">Awaiting assignment</div></div>
      <div class="sc-card sc-kpi"><div class="num" id="dirActive">—</div><div class="label">In Progress</div><div class="sub">Being worked on</div></div>
      <div class="sc-card sc-kpi"><div class="num" id="dirCompleted">—</div><div class="label">Completed</div><div class="sub" id="dirCompletedSub">total</div></div>
      <div class="sc-card sc-kpi"><div class="num" id="dirSLA">—</div><div class="label">SLA Breaches</div><div class="sub">overdue directives</div></div>
    </div>

    <!-- Issue New Directive -->
    <div class="sc-card" style="margin-bottom:20px">
      <h3><i class="fas fa-plus-circle" style="color:var(--sc-gold)"></i> Issue New Directive</h3>
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:12px;align-items:end;margin-top:12px">
        <div>
          <label style="font-size:.72rem;color:var(--sc-text-muted);display:block;margin-bottom:4px">Type</label>
          <select id="dirType" class="sc-input">
            <option value="repair">Repair</option>
            <option value="upgrade">Upgrade</option>
            <option value="investigate">Investigate</option>
            <option value="maintain">Maintain</option>
            <option value="deploy">Deploy</option>
          </select>
        </div>
        <div>
          <label style="font-size:.72rem;color:var(--sc-text-muted);display:block;margin-bottom:4px">Priority</label>
          <select id="dirPriority" class="sc-input">
            <option value="low">Low</option>
            <option value="normal" selected>Normal</option>
            <option value="high">High</option>
            <option value="critical">Critical</option>
          </select>
        </div>
        <div>
          <label style="font-size:.72rem;color:var(--sc-text-muted);display:block;margin-bottom:4px">SLA (minutes)</label>
          <input type="number" id="dirSLAMin" class="sc-input" value="60" min="5" max="10080">
        </div>
        <button class="sc-btn sc-btn-gold" onclick="showDirectiveForm()" style="height:38px"><i class="fas fa-paper-plane"></i> Issue</button>
      </div>
      <div id="dirFormExpanded" style="display:none;margin-top:12px">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
          <div>
            <label style="font-size:.72rem;color:var(--sc-text-muted);display:block;margin-bottom:4px">Title</label>
            <input type="text" id="dirTitle" class="sc-input" placeholder="e.g. Fix billing API 302 errors">
          </div>
          <div>
            <label style="font-size:.72rem;color:var(--sc-text-muted);display:block;margin-bottom:4px">Assign To (optional)</label>
            <input type="text" id="dirAssign" class="sc-input" placeholder="Agent ID or leave blank for auto-assign">
          </div>
        </div>
        <div style="margin-bottom:12px">
          <label style="font-size:.72rem;color:var(--sc-text-muted);display:block;margin-bottom:4px">Description</label>
          <textarea id="dirDesc" class="sc-input" rows="3" placeholder="What needs to be done..."></textarea>
        </div>
        <div style="display:flex;gap:12px">
          <button class="sc-btn sc-btn-gold" onclick="submitDirective()"><i class="fas fa-check"></i> Submit Directive</button>
          <button class="sc-btn sc-btn-outline" onclick="document.getElementById('dirFormExpanded').style.display='none'">Cancel</button>
        </div>
      </div>
    </div>

    <!-- Quick Templates -->
    <div class="sc-card" style="margin-bottom:20px">
      <h3><i class="fas fa-bolt" style="color:var(--sc-gold)"></i> Quick Directives</h3>
      <div class="sc-grid sc-grid-4" id="dirTemplates" style="margin-top:12px">
        <button class="sc-btn sc-btn-outline sc-btn-sm" onclick="issueTemplate('health-check')"><i class="fas fa-heartbeat"></i> Health Check</button>
        <button class="sc-btn sc-btn-outline sc-btn-sm" onclick="issueTemplate('feed-sweep')"><i class="fas fa-rss"></i> Feed Sweep</button>
        <button class="sc-btn sc-btn-outline sc-btn-sm" onclick="issueTemplate('security-scan')"><i class="fas fa-shield-halved"></i> Security Scan</button>
        <button class="sc-btn sc-btn-outline sc-btn-sm" onclick="issueTemplate('db-optimize')"><i class="fas fa-database"></i> DB Optimize</button>
        <button class="sc-btn sc-btn-outline sc-btn-sm" onclick="issueTemplate('agent-perf-review')"><i class="fas fa-chart-line"></i> Agent Review</button>
        <button class="sc-btn sc-btn-outline sc-btn-sm" onclick="issueTemplate('service-recovery')"><i class="fas fa-wrench"></i> Service Recovery</button>
        <button class="sc-btn sc-btn-outline sc-btn-sm" onclick="issueTemplate('treasury-report')"><i class="fas fa-coins"></i> Treasury Report</button>
        <button class="sc-btn sc-btn-outline sc-btn-sm" onclick="issueTemplate('deploy-feature-flag')"><i class="fas fa-flag"></i> Feature Flag</button>
      </div>
    </div>

    <div class="sc-grid sc-grid-2">
      <!-- Directive Queue -->
      <div class="sc-card">
        <h3><i class="fas fa-list-check" style="color:var(--sc-gold)"></i> Active Queue</h3>
        <div class="sc-table-wrap" style="margin-top:12px">
          <table class="sc-table">
            <thead><tr><th>ID</th><th>Type</th><th>Title</th><th>Priority</th><th>Status</th><th>Agent</th><th>SLA</th><th></th></tr></thead>
            <tbody id="dirQueueBody"><tr><td colspan="8" style="text-align:center;color:var(--sc-text-muted)">Loading...</td></tr></tbody>
          </table>
        </div>
      </div>

      <!-- Standing Orders + Agent Performance -->
      <div>
        <div class="sc-card" style="margin-bottom:20px">
          <h3><i class="fas fa-repeat" style="color:var(--sc-gold)"></i> Standing Orders</h3>
          <div id="dirStandingOrders" style="margin-top:12px;font-size:.84rem">Loading...</div>
        </div>
        <div class="sc-card">
          <h3><i class="fas fa-trophy" style="color:var(--sc-gold)"></i> Agent Performance (Top 5)</h3>
          <div id="dirAgentPerf" style="margin-top:12px;font-size:.84rem">Loading...</div>
        </div>
      </div>
    </div>

    <!-- Timeline -->
    <div class="sc-card" style="margin-top:20px">
      <h3><i class="fas fa-clock-rotate-left" style="color:var(--sc-gold)"></i> Recent Activity</h3>
      <div id="dirTimeline" style="margin-top:12px;max-height:300px;overflow-y:auto">Loading...</div>
    </div>
  </div>

  <!-- ═══════ PANEL: MINING REVENUE (20% Platform Share) ═══════ -->
  <div class="sc-panel" id="panel-mining-revenue">
    <div class="sc-card" style="border-color:rgba(255,215,0,.2);margin-bottom:20px;background:linear-gradient(135deg,rgba(255,215,0,.03),rgba(46,213,115,.03))">
      <h3 style="font-size:1.1rem"><i class="fas fa-coins" style="color:var(--sc-gold)"></i> Platform Revenue — 20% Mining Treasury</h3>
      <p style="font-size:.84rem;color:var(--sc-text-muted);margin-bottom:0">All GSM generated through browser mining and search rewards. Platform retains 20% — users keep 80%. This treasury funds trading agents, infrastructure, R&D, and ecosystem growth.</p>
    </div>

    <!-- Treasury KPIs -->
    <div class="sc-grid sc-grid-4" style="margin-bottom:20px" id="revKpis">
      <div class="sc-card sc-kpi"><div class="num" id="revGross" style="font-size:1.6rem">—</div><div class="label">Gross GSM Generated</div><div class="sub">total mining + search</div></div>
      <div class="sc-card sc-kpi"><div class="num" id="revPlatform" style="font-size:1.6rem;background:linear-gradient(135deg,#ffd700,#ff7f50);-webkit-background-clip:text;-webkit-text-fill-color:transparent">—</div><div class="label">Platform 20% Share</div><div class="sub">treasury balance</div></div>
      <div class="sc-card sc-kpi"><div class="num" id="revUser" style="font-size:1.6rem">—</div><div class="label">Users 80% Share</div><div class="sub">distributed to miners</div></div>
      <div class="sc-card sc-kpi"><div class="num" id="revMiners" style="font-size:1.6rem">—</div><div class="label">Active Miners</div><div class="sub" id="revMinersSub">unique participants</div></div>
    </div>

    <!-- Today's Revenue -->
    <div class="sc-grid sc-grid-4" style="margin-bottom:20px">
      <div class="sc-card sc-kpi"><div class="num" id="revToday" style="color:var(--sc-green)">—</div><div class="label">Today Platform GSM</div></div>
      <div class="sc-card sc-kpi"><div class="num" id="revMiningGSM">—</div><div class="label">Mining Revenue</div></div>
      <div class="sc-card sc-kpi"><div class="num" id="revSearchGSM">—</div><div class="label">Search Revenue</div></div>
      <div class="sc-card sc-kpi"><div class="num" id="revPoolPct">—</div><div class="label">Pool Distributed</div><div class="sub">of 250M GSM</div></div>
    </div>

    <div class="sc-grid sc-grid-2">
      <!-- 7-Day Trend Chart -->
      <div class="sc-card">
        <h3><i class="fas fa-chart-area" style="color:var(--sc-gold)"></i> 7-Day Revenue Trend</h3>
        <div id="revChart" style="height:200px;display:flex;align-items:flex-end;gap:6px;padding:16px 0"></div>
      </div>

      <!-- Treasury State -->
      <div class="sc-card">
        <h3><i class="fas fa-vault" style="color:var(--sc-gold)"></i> Treasury State</h3>
        <div id="revTreasury" style="font-size:.84rem">Loading treasury...</div>
      </div>
    </div>

    <!-- Fund Allocation -->
    <div class="sc-card" style="margin-top:16px">
      <h3><i class="fas fa-hand-holding-dollar" style="color:var(--sc-gold)"></i> Fund Allocation</h3>
      <p style="font-size:.82rem;color:var(--sc-text-muted);margin-bottom:12px">Allocate platform treasury funds to ecosystem programs</p>
      <div style="display:grid;grid-template-columns:1fr 120px 2fr auto;gap:12px;align-items:end;margin-bottom:16px">
        <div>
          <label style="font-size:.72rem;color:var(--sc-text-muted);display:block;margin-bottom:4px">Program</label>
          <select id="allocProgram" class="sc-input">
            <option value="trading_agents">Financial Trading Agents</option>
            <option value="infrastructure">Infrastructure & Servers</option>
            <option value="research_robotics">Robotics R&D</option>
            <option value="research_zpe">Zero Point Energy R&D</option>
            <option value="intelligence">Intelligence Operations</option>
            <option value="marketing">Marketing & Growth</option>
            <option value="development">Platform Development</option>
            <option value="community">Community Rewards</option>
          </select>
        </div>
        <div>
          <label style="font-size:.72rem;color:var(--sc-text-muted);display:block;margin-bottom:4px">Amount (GSM)</label>
          <input type="number" id="allocAmount" class="sc-input" step="0.001" min="0">
        </div>
        <div>
          <label style="font-size:.72rem;color:var(--sc-text-muted);display:block;margin-bottom:4px">Description</label>
          <input type="text" id="allocDesc" class="sc-input" placeholder="Allocation purpose...">
        </div>
        <button class="sc-btn sc-btn-gold" onclick="allocateFunds()" style="height:38px"><i class="fas fa-paper-plane"></i> Allocate</button>
      </div>

      <!-- Recent Allocations -->
      <div class="sc-table-wrap">
        <table class="sc-table">
          <thead><tr><th>Program</th><th>Amount</th><th>Status</th><th>Description</th><th>Date</th></tr></thead>
          <tbody id="allocTableBody"><tr><td colspan="5" style="text-align:center;color:var(--sc-text-muted)">Loading allocations...</td></tr></tbody>
        </table>
      </div>
    </div>

    <!-- Top Miners -->
    <div class="sc-card" style="margin-top:16px">
      <h3><i class="fas fa-trophy" style="color:var(--sc-gold)"></i> Top Miners — Platform Revenue Contributors</h3>
      <div class="sc-table-wrap">
        <table class="sc-table">
          <thead><tr><th>#</th><th>User ID</th><th>Total Earned (GSM)</th><th>Platform Share (GSM)</th><th>Rewards</th></tr></thead>
          <tbody id="topMinersBody"><tr><td colspan="5" style="text-align:center;color:var(--sc-text-muted)">Loading...</td></tr></tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ═══════ PANEL: ECOSYSTEM MONITOR ═══════ -->
  <div class="sc-panel" id="panel-ecosystem-monitor">
    <div class="sc-card" style="border-color:rgba(0,230,118,.2);margin-bottom:20px;background:linear-gradient(135deg,rgba(0,230,118,.03),rgba(108,92,231,.03))">
      <h3 style="font-size:1.1rem"><i class="fas fa-heartbeat" style="color:#00e676"></i> Ecosystem Autonomy Monitor</h3>
      <p style="font-size:.84rem;color:var(--sc-text-muted);margin-bottom:0">Unified view of all systems — PM2 services, subsystems, agents, intelligence, trading, mining, and billing. Self-healing controls and continuity tracking.</p>
    </div>

    <!-- Ecosystem Score + Threat Level -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px">
      <div class="sc-card" style="text-align:center;padding:16px">
        <div style="font-size:2.2rem;font-weight:800;font-family:var(--sc-font-mono)" id="ecoScore">—</div>
        <div style="font-size:.75rem;color:var(--sc-text-muted);text-transform:uppercase">Ecosystem Score</div>
      </div>
      <div class="sc-card" style="text-align:center;padding:16px">
        <div style="font-size:1.4rem;font-weight:800;font-family:var(--sc-font-mono)" id="ecoThreat">—</div>
        <div style="font-size:.75rem;color:var(--sc-text-muted);text-transform:uppercase">Threat Level</div>
      </div>
      <div class="sc-card" style="text-align:center;padding:16px">
        <div style="font-size:1.6rem;font-weight:800;font-family:var(--sc-font-mono)" id="ecoServicesUp">—</div>
        <div style="font-size:.75rem;color:var(--sc-text-muted);text-transform:uppercase">Services Online</div>
      </div>
      <div class="sc-card" style="text-align:center;padding:16px">
        <div style="font-size:1.6rem;font-weight:800;font-family:var(--sc-font-mono)" id="ecoSubsHealthy">—</div>
        <div style="font-size:.75rem;color:var(--sc-text-muted);text-transform:uppercase">Subsystems OK</div>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
      <!-- PM2 Services -->
      <div class="sc-card">
        <h4 style="margin:0 0 12px"><i class="fas fa-server" style="color:var(--sc-green)"></i> PM2 Services</h4>
        <div id="ecoPm2List" style="font-size:.84rem">Loading...</div>
      </div>
      <!-- Subsystems -->
      <div class="sc-card">
        <h4 style="margin:0 0 12px"><i class="fas fa-cubes" style="color:var(--sc-accent)"></i> Subsystem Health</h4>
        <div id="ecoSubsystemList" style="font-size:.84rem">Loading...</div>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px">
      <!-- Agent Census -->
      <div class="sc-card">
        <h4 style="margin:0 0 12px"><i class="fas fa-users-cog" style="color:var(--sc-cyan)"></i> Agent Census</h4>
        <div id="ecoAgentCensus" style="font-size:.84rem">Loading...</div>
      </div>
      <!-- Healing Controls -->
      <div class="sc-card">
        <h4 style="margin:0 0 12px"><i class="fas fa-medkit" style="color:var(--sc-red)"></i> Healing Controls</h4>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px">
          <button class="sc-btn" onclick="healService('redis')" style="font-size:.78rem"><i class="fas fa-database"></i> Redis</button>
          <button class="sc-btn" onclick="healService('meilisearch')" style="font-size:.78rem"><i class="fas fa-search"></i> Meili</button>
          <button class="sc-btn" onclick="healService('alfred-ws')" style="font-size:.78rem"><i class="fas fa-plug"></i> WebSocket</button>
          <button class="sc-btn" onclick="healService('alfred-jobs')" style="font-size:.78rem"><i class="fas fa-tasks"></i> Jobs</button>
          <button class="sc-btn" onclick="healService('alfred-mcp')" style="font-size:.78rem"><i class="fas fa-brain"></i> MCP</button>
          <button class="sc-btn" onclick="healService('alfred-discord')" style="font-size:.78rem"><i class="fab fa-discord"></i> Discord</button>
          <button class="sc-btn" onclick="healService('alfred-heartbeat')" style="font-size:.78rem"><i class="fas fa-heartbeat"></i> Heartbeat</button>
          <button class="sc-btn" onclick="healService('ollama')" style="font-size:.78rem"><i class="fas fa-robot"></i> Ollama</button>
        </div>
        <div id="ecoHealLog" style="font-size:.78rem;max-height:200px;overflow-y:auto">No recent healing actions.</div>
      </div>
    </div>

    <!-- Continuity -->
    <div class="sc-card" style="margin-top:16px">
      <h4 style="margin:0 0 12px"><i class="fas fa-chart-line" style="color:var(--sc-gold)"></i> Continuity Metrics</h4>
      <div id="ecoContinuity" style="font-size:.84rem">Loading...</div>
    </div>
  </div>

  <!-- ═══════ PANEL: AGENT OS COMMAND HUB ═══════ -->
  <div class="sc-panel" id="panel-agentos">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
      <div>
        <h2 style="margin:0;font-size:1.4rem;background:linear-gradient(90deg,#00f5ff,#7c5ce7);-webkit-background-clip:text;-webkit-text-fill-color:transparent"><i class="fas fa-microchip" style="-webkit-text-fill-color:#00f5ff"></i> Alfred OS Command Hub</h2>
        <p style="margin:4px 0 0;color:#888;font-size:.82rem">Central registry of all agent administration panels — current and future</p>
      </div>
      <button class="sc-btn" onclick="location.href='/agentos-dashboard.php'" style="background:linear-gradient(135deg,#00f5ff,#7c5ce7);color:#000;font-weight:600"><i class="fas fa-external-link-alt"></i> Open Full Dashboard</button>
    </div>

    <!-- Live KPI Row -->
    <div class="sc-grid sc-grid-4" id="agentosKpis">
      <div class="sc-card sc-kpi"><div class="num" id="aosCapabilities">—</div><div class="label">Capabilities</div></div>
      <div class="sc-card sc-kpi"><div class="num" id="aosTasks">—</div><div class="label">Tasks (24h)</div></div>
      <div class="sc-card sc-kpi"><div class="num" id="aosApprovals">—</div><div class="label">Pending Approvals</div></div>
      <div class="sc-card sc-kpi"><div class="num" id="aosDevices">—</div><div class="label">Devices</div></div>
    </div>

    <!-- Panel Directory -->
    <h3 style="margin:24px 0 12px;font-size:1rem;color:#ccc"><i class="fas fa-th"></i> Admin Panel Directory</h3>
    <div class="sc-grid sc-grid-3" id="aosPanelGrid">

      <!-- Core Alfred OS Dashboard -->
      <div class="sc-card" style="border-left:3px solid #00f5ff;cursor:pointer" onclick="location.href='/agentos-dashboard.php'">
        <h4 style="margin:0 0 6px"><i class="fas fa-microchip" style="color:#00f5ff"></i> Alfred OS Dashboard</h4>
        <p style="font-size:.8rem;color:#888;margin:0 0 8px">Core agent operating system — capabilities, skills, tasks, memory, world state, policies, simulations, audit, runtime loop, device bridge</p>
        <div style="display:flex;gap:6px;flex-wrap:wrap">
          <span style="font-size:.7rem;padding:2px 8px;border-radius:99px;background:rgba(0,245,255,.1);color:#00f5ff">11 Panels</span>
          <span style="font-size:.7rem;padding:2px 8px;border-radius:99px;background:rgba(34,197,94,.1);color:#22c55e">Phase 3 Complete</span>
        </div>
      </div>

      <!-- Fleet Dashboard -->
      <div class="sc-card" style="border-left:3px solid #f59e0b;cursor:pointer" onclick="location.href='/fleet-dashboard.php'">
        <h4 style="margin:0 0 6px"><i class="fas fa-users-cog" style="color:#f59e0b"></i> Fleet Dashboard</h4>
        <p style="font-size:.8rem;color:#888;margin:0 0 8px">Multi-agent fleet management — agent roster, task assignment, performance monitoring, inter-agent messaging</p>
        <div style="display:flex;gap:6px;flex-wrap:wrap">
          <span style="font-size:.7rem;padding:2px 8px;border-radius:99px;background:rgba(245,158,11,.1);color:#f59e0b">Fleet Ops</span>
        </div>
      </div>

      <!-- Alfred Tools -->
      <div class="sc-card" style="border-left:3px solid #7c5ce7;cursor:pointer" onclick="location.href='/alfred-tools.php'">
        <h4 style="margin:0 0 6px"><i class="fas fa-wrench" style="color:#7c5ce7"></i> Alfred Tools</h4>
        <p style="font-size:.8rem;color:#888;margin:0 0 8px">Master tool registry — 850+ MCP tools, 400 native PHP tools, Composio & VAPI integrations, tool search & execution</p>
        <div style="display:flex;gap:6px;flex-wrap:wrap">
          <span style="font-size:.7rem;padding:2px 8px;border-radius:99px;background:rgba(124,92,231,.1);color:#7c5ce7">1220+ Tools</span>
        </div>
      </div>

      <!-- Intelligence Director -->
      <div class="sc-card" style="border-left:3px solid #ef4444;cursor:pointer" onclick="location.href='/intelligence-director.php'">
        <h4 style="margin:0 0 6px"><i class="fas fa-satellite" style="color:#ef4444"></i> Intelligence Director</h4>
        <p style="font-size:.8rem;color:#888;margin:0 0 8px">System intelligence — threat monitoring, anomaly detection, competitive analysis, strategic recommendations</p>
        <div style="display:flex;gap:6px;flex-wrap:wrap">
          <span style="font-size:.7rem;padding:2px 8px;border-radius:99px;background:rgba(239,68,68,.1);color:#ef4444">Intel Ops</span>
        </div>
      </div>

      <!-- Agent Templates -->
      <div class="sc-card" style="border-left:3px solid #22c55e;cursor:pointer" onclick="location.href='/agent-templates.php'">
        <h4 style="margin:0 0 6px"><i class="fas fa-clone" style="color:#22c55e"></i> Agent Templates</h4>
        <p style="font-size:.8rem;color:#888;margin:0 0 8px">Agent blueprints — pre-built agent configurations, custom template builder, deployment presets</p>
        <div style="display:flex;gap:6px;flex-wrap:wrap">
          <span style="font-size:.7rem;padding:2px 8px;border-radius:99px;background:rgba(34,197,94,.1);color:#22c55e">Templates</span>
        </div>
      </div>

      <!-- Call Campaigns -->
      <div class="sc-card" style="border-left:3px solid #3b82f6;cursor:pointer" onclick="location.href='/call-campaigns.php'">
        <h4 style="margin:0 0 6px"><i class="fas fa-phone-volume" style="color:#3b82f6"></i> Call Campaigns</h4>
        <p style="font-size:.8rem;color:#888;margin:0 0 8px">Voice agent campaigns — outbound/inbound call flows, VAPI integration, campaign analytics, voice cloning</p>
        <div style="display:flex;gap:6px;flex-wrap:wrap">
          <span style="font-size:.7rem;padding:2px 8px;border-radius:99px;background:rgba(59,130,246,.1);color:#3b82f6">Voice AI</span>
        </div>
      </div>

      <!-- Collaboration Dashboard -->
      <div class="sc-card" style="border-left:3px solid #ec4899;cursor:pointer" onclick="location.href='/collaboration-dashboard.php'">
        <h4 style="margin:0 0 6px"><i class="fas fa-handshake" style="color:#ec4899"></i> Collaboration</h4>
        <p style="font-size:.8rem;color:#888;margin:0 0 8px">Multi-agent collaboration — shared goals, task delegation, consensus protocols, agent communication channels</p>
        <div style="display:flex;gap:6px;flex-wrap:wrap">
          <span style="font-size:.7rem;padding:2px 8px;border-radius:99px;background:rgba(236,72,153,.1);color:#ec4899">Collaboration</span>
        </div>
      </div>

      <!-- Gamification -->
      <div class="sc-card" style="border-left:3px solid #eab308;cursor:pointer" onclick="location.href='/gamification-dashboard.php'">
        <h4 style="margin:0 0 6px"><i class="fas fa-trophy" style="color:#eab308"></i> Gamification</h4>
        <p style="font-size:.8rem;color:#888;margin:0 0 8px">Agent incentives — XP systems, achievement badges, leaderboards, reward mechanisms, performance gamification</p>
        <div style="display:flex;gap:6px;flex-wrap:wrap">
          <span style="font-size:.7rem;padding:2px 8px;border-radius:99px;background:rgba(234,179,8,.1);color:#eab308">Rewards</span>
        </div>
      </div>

      <!-- Developer Portal -->
      <div class="sc-card" style="border-left:3px solid #14b8a6;cursor:pointer" onclick="location.href='/developer-portal.php'">
        <h4 style="margin:0 0 6px"><i class="fas fa-code" style="color:#14b8a6"></i> Developer Portal</h4>
        <p style="font-size:.8rem;color:#888;margin:0 0 8px">API documentation, SDK downloads, webhook management, extension development, third-party integrations</p>
        <div style="display:flex;gap:6px;flex-wrap:wrap">
          <span style="font-size:.7rem;padding:2px 8px;border-radius:99px;background:rgba(20,184,166,.1);color:#14b8a6">Dev Tools</span>
        </div>
      </div>

      <!-- Reporting -->
      <div class="sc-card" style="border-left:3px solid #8b5cf6;cursor:pointer" onclick="location.href='/reporting-dashboard.php'">
        <h4 style="margin:0 0 6px"><i class="fas fa-chart-bar" style="color:#8b5cf6"></i> Reporting</h4>
        <p style="font-size:.8rem;color:#888;margin:0 0 8px">Analytics & reports — agent performance metrics, system health, cost analysis, usage trends, custom report builder</p>
        <div style="display:flex;gap:6px;flex-wrap:wrap">
          <span style="font-size:.7rem;padding:2px 8px;border-radius:99px;background:rgba(139,92,246,.1);color:#8b5cf6">Analytics</span>
        </div>
      </div>

      <!-- Marketplace -->
      <div class="sc-card" style="border-left:3px solid #f97316;cursor:pointer" onclick="location.href='/marketplace.php'">
        <h4 style="margin:0 0 6px"><i class="fas fa-store" style="color:#f97316"></i> Marketplace</h4>
        <p style="font-size:.8rem;color:#888;margin:0 0 8px">Agent & tool marketplace — publish/install agents, skill packs, tool extensions, community contributions</p>
        <div style="display:flex;gap:6px;flex-wrap:wrap">
          <span style="font-size:.7rem;padding:2px 8px;border-radius:99px;background:rgba(249,115,22,.1);color:#f97316">Marketplace</span>
        </div>
      </div>

      <!-- Future Panel Placeholder -->
      <div class="sc-card" style="border-left:3px solid #555;border-style:dashed;opacity:.6">
        <h4 style="margin:0 0 6px;color:#666"><i class="fas fa-plus-circle" style="color:#555"></i> Future Panels</h4>
        <p style="font-size:.8rem;color:#666;margin:0 0 8px">New agent admin panels will automatically appear here as they're built — IoT fleet, digital twins, sensor networks, robotics control rooms</p>
        <div style="display:flex;gap:6px;flex-wrap:wrap">
          <span style="font-size:.7rem;padding:2px 8px;border-radius:99px;background:rgba(255,255,255,.05);color:#666">Coming Soon</span>
        </div>
      </div>

    </div>

    <!-- Quick Actions Row -->
    <h3 style="margin:24px 0 12px;font-size:1rem;color:#ccc"><i class="fas fa-bolt"></i> Quick Actions</h3>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
      <button class="sc-btn" onclick="location.href='/agentos-dashboard.php#runtime'"><i class="fas fa-play"></i> Execute Agent Goal</button>
      <button class="sc-btn" onclick="location.href='/agentos-dashboard.php#approvals'"><i class="fas fa-check-circle"></i> Review Approvals</button>
      <button class="sc-btn" onclick="location.href='/agentos-dashboard.php#audit'"><i class="fas fa-history"></i> Audit Trail</button>
      <button class="sc-btn sc-btn-outline" onclick="location.href='/agentos-dashboard.php#bridge'"><i class="fas fa-plug"></i> Device Bridge</button>
      <button class="sc-btn sc-btn-outline" onclick="location.href='/agentos-dashboard.php#policies'"><i class="fas fa-gavel"></i> Safety Policies</button>
    </div>
  </div>

  <!-- ═══════ PANEL: SECURITY ═══════ -->
  <div class="sc-panel" id="panel-security">
    <div class="sc-grid sc-grid-3">
      <div class="sc-card">
        <h3><i class="fas fa-key"></i> Secrets Status</h3>
        <div id="secSecrets" style="font-size:.84rem">Loading...</div>
      </div>
      <div class="sc-card">
        <h3><i class="fas fa-shield-halved"></i> Security Checks</h3>
        <div id="secChecks" style="font-size:.84rem">Loading...</div>
      </div>
      <div class="sc-card">
        <h3><i class="fas fa-scroll"></i> Audit Log</h3>
        <div id="secAudit" style="font-size:.84rem">Loading...</div>
      </div>
    </div>
  </div>
</div>


<script src="/assets/js/supreme-admin-engine.js"></script>


<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
