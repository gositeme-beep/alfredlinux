<?php
/**
 * GSM Alfred OS — Command Dashboard v1.0
 * ═══════════════════════════════════════════════════════════════
 * The Agent Operating System — Where AI perceives, plans,
 * simulates, acts, verifies, and learns.
 * 
 * Capabilities • Skills • Tasks • Memory • World State
 * Policies • Simulations • Audit Trail • Device Bridge
 * 
 * ACCESS: Supreme Commander only
 */
$page_title = 'Alfred OS — Command Dashboard';
$page_description = 'Agent Operating System command and control dashboard.';
$page_canonical = 'https://root.com/agentos-dashboard';
$page_robots = 'noindex, nofollow';

include __DIR__ . '/includes/auth-gate.inc.php';

$supremeAdmins = ['root@gmail.com'];
if (!$clientEmail || !in_array(strtolower($clientEmail), $supremeAdmins)) {
    header('Location: /dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Alfred OS — Command Dashboard</title>
<link rel="stylesheet" href="/assets/css/fonts.css">
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<style>
:root{
    --os-bg:#04040c;
    --os-surface:#0a0a18;
    --os-surface-2:#111128;
    --os-surface-3:#1a1a3a;
    --os-border:rgba(100,120,255,.08);
    --os-border-active:rgba(100,120,255,.25);
    --os-primary:#6366f1;
    --os-primary-glow:rgba(99,102,241,.15);
    --os-cyan:#22d3ee;
    --os-green:#22c55e;
    --os-amber:#f59e0b;
    --os-red:#ef4444;
    --os-purple:#a855f7;
    --os-blue:#3b82f6;
    --os-pink:#ec4899;
    --os-text:rgba(255,255,255,.88);
    --os-muted:rgba(255,255,255,.5);
    --os-dim:rgba(255,255,255,.25);
    --os-r:14px;
    --os-r-sm:8px;
    --os-font:'Inter',system-ui,sans-serif;
    --os-mono:'JetBrains Mono',monospace;
}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:var(--os-font);background:var(--os-bg);color:var(--os-text);min-height:100vh;overflow-x:hidden}
a{color:var(--os-cyan);text-decoration:none}
a:hover{text-decoration:underline}

/* ── Layout ──────────────────────────────────────────── */
.os-wrap{max-width:1600px;margin:0 auto;padding:24px 32px 80px}
.os-header{display:flex;align-items:center;gap:20px;padding:24px 0 32px;border-bottom:1px solid var(--os-border)}
.os-header .logo{font-size:32px;color:var(--os-primary)}
.os-header h1{font-size:28px;font-weight:700;letter-spacing:-.5px}
.os-header h1 span{color:var(--os-cyan);font-weight:400}
.os-header .badge{background:var(--os-primary-glow);color:var(--os-primary);padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600;border:1px solid rgba(99,102,241,.2)}
.os-header .right{margin-left:auto;display:flex;gap:12px;align-items:center}
.os-header .status{display:flex;align-items:center;gap:6px;font-size:13px;color:var(--os-green)}
.os-header .status .dot{width:8px;height:8px;border-radius:50%;background:var(--os-green);animation:pulse-dot 2s infinite}
@keyframes pulse-dot{0%,100%{opacity:1}50%{opacity:.4}}
.back-link{color:var(--os-muted);font-size:13px;display:flex;align-items:center;gap:6px}
.back-link:hover{color:var(--os-text);text-decoration:none}

/* ── Tabs ─────────────────────────────────────────── */
.os-tabs{display:flex;gap:4px;padding:20px 0 24px;overflow-x:auto;scrollbar-width:none}
.os-tabs::-webkit-scrollbar{display:none}
.os-tab{padding:10px 18px;border-radius:var(--os-r-sm);font-size:13px;font-weight:500;cursor:pointer;border:1px solid transparent;background:transparent;color:var(--os-muted);transition:all .2s;white-space:nowrap;display:flex;align-items:center;gap:8px}
.os-tab:hover{color:var(--os-text);background:var(--os-surface)}
.os-tab.active{color:var(--os-text);background:var(--os-surface-2);border-color:var(--os-border-active)}
.os-tab .count{background:var(--os-primary-glow);color:var(--os-primary);padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600}

/* ── Panels ───────────────────────────────────────── */
.os-panel{display:none}
.os-panel.active{display:block;animation:panelIn .3s}
@keyframes panelIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}

/* ── Stats Grid ───────────────────────────────────── */
.os-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:28px}
.os-stat{background:var(--os-surface);border:1px solid var(--os-border);border-radius:var(--os-r);padding:20px;position:relative;overflow:hidden}
.os-stat::before{content:'';position:absolute;top:0;left:0;right:0;height:3px}
.os-stat.cyan::before{background:var(--os-cyan)}
.os-stat.green::before{background:var(--os-green)}
.os-stat.purple::before{background:var(--os-purple)}
.os-stat.amber::before{background:var(--os-amber)}
.os-stat.blue::before{background:var(--os-blue)}
.os-stat.pink::before{background:var(--os-pink)}
.os-stat.red::before{background:var(--os-red)}
.os-stat .label{font-size:12px;color:var(--os-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px}
.os-stat .val{font-size:28px;font-weight:700;font-family:var(--os-mono)}
.os-stat .sub{font-size:12px;color:var(--os-dim);margin-top:6px}

/* ── Section ──────────────────────────────────────── */
.os-section{margin-bottom:28px}
.os-section-title{font-size:16px;font-weight:600;margin-bottom:14px;display:flex;align-items:center;gap:10px}
.os-section-title i{color:var(--os-primary);font-size:14px}

/* ── Tables ───────────────────────────────────────── */
.os-table-wrap{background:var(--os-surface);border:1px solid var(--os-border);border-radius:var(--os-r);overflow:hidden}
.os-table{width:100%;border-collapse:collapse}
.os-table th{text-align:left;padding:12px 16px;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--os-muted);background:var(--os-surface-2);border-bottom:1px solid var(--os-border);font-weight:600}
.os-table td{padding:12px 16px;font-size:13px;border-bottom:1px solid var(--os-border)}
.os-table tr:last-child td{border-bottom:none}
.os-table tr:hover td{background:rgba(99,102,241,.03)}
.os-table .mono{font-family:var(--os-mono);font-size:12px}

/* ── Badges ───────────────────────────────────────── */
.badge-pill{padding:3px 10px;border-radius:12px;font-size:11px;font-weight:600;display:inline-block}
.badge-pill.low{background:rgba(34,197,94,.12);color:#22c55e}
.badge-pill.medium{background:rgba(245,158,11,.12);color:#f59e0b}
.badge-pill.high{background:rgba(239,68,68,.12);color:#ef4444}
.badge-pill.critical{background:rgba(239,68,68,.2);color:#ff6b6b}
.badge-pill.online{background:rgba(34,197,94,.12);color:#22c55e}
.badge-pill.offline{background:rgba(255,255,255,.06);color:var(--os-dim)}
.badge-pill.pending{background:rgba(245,158,11,.12);color:#f59e0b}
.badge-pill.running{background:rgba(59,130,246,.12);color:#3b82f6}
.badge-pill.completed{background:rgba(34,197,94,.12);color:#22c55e}
.badge-pill.failed{background:rgba(239,68,68,.12);color:#ef4444}
.badge-pill.allow{background:rgba(34,197,94,.12);color:#22c55e}
.badge-pill.deny{background:rgba(239,68,68,.12);color:#ef4444}
.badge-pill.require_approval{background:rgba(245,158,11,.12);color:#f59e0b}
.badge-pill.require_simulation{background:rgba(168,85,247,.12);color:#a855f7}

/* ── Cards ────────────────────────────────────────── */
.os-card{background:var(--os-surface);border:1px solid var(--os-border);border-radius:var(--os-r);padding:20px}
.os-card h3{font-size:15px;margin-bottom:12px;display:flex;align-items:center;gap:8px}
.os-card h3 i{color:var(--os-primary);font-size:13px}

/* ── Buttons ──────────────────────────────────────── */
.os-btn{padding:8px 18px;border-radius:var(--os-r-sm);font-size:13px;font-weight:500;cursor:pointer;border:1px solid var(--os-border);background:var(--os-surface-2);color:var(--os-text);transition:all .2s;display:inline-flex;align-items:center;gap:6px}
.os-btn:hover{background:var(--os-surface-3);border-color:var(--os-border-active)}
.os-btn.primary{background:var(--os-primary);border-color:var(--os-primary);color:#fff}
.os-btn.primary:hover{background:#5457e5}
.os-btn.danger{background:rgba(239,68,68,.15);border-color:rgba(239,68,68,.3);color:var(--os-red)}
.os-btn.danger:hover{background:rgba(239,68,68,.25)}
.loop-phase{padding:6px 12px;border-radius:8px;font-size:11px;background:var(--os-surface);color:var(--os-muted);border:1px solid rgba(255,255,255,.06);transition:all .3s ease;display:flex;align-items:center;gap:6px}
.loop-phase.active{background:rgba(99,102,241,.2);border-color:var(--os-primary);color:var(--os-primary);box-shadow:0 0 12px rgba(99,102,241,.3)}
.loop-phase.done{background:rgba(34,197,94,.15);border-color:rgba(34,197,94,.3);color:var(--os-green)}
.loop-phase.failed{background:rgba(239,68,68,.15);border-color:rgba(239,68,68,.3);color:var(--os-red)}
.loop-arrow{color:var(--os-muted);font-size:12px;opacity:.4}
.os-btn.sm{padding:5px 12px;font-size:12px}

/* ── Agent Loop Viz ───────────────────────────────── */
.agent-loop{display:flex;flex-wrap:wrap;gap:12px;margin-bottom:24px}
.loop-step{flex:1;min-width:120px;background:var(--os-surface);border:1px solid var(--os-border);border-radius:var(--os-r);padding:14px;text-align:center;position:relative}
.loop-step .step-icon{font-size:20px;margin-bottom:6px}
.loop-step .step-name{font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
.loop-step .step-time{font-size:11px;color:var(--os-dim);margin-top:4px;font-family:var(--os-mono)}
.loop-step.observe .step-icon{color:var(--os-cyan)}
.loop-step.recall .step-icon{color:var(--os-purple)}
.loop-step.plan .step-icon{color:var(--os-blue)}
.loop-step.simulate .step-icon{color:var(--os-amber)}
.loop-step.policy .step-icon{color:var(--os-pink)}
.loop-step.act .step-icon{color:var(--os-green)}
.loop-step.verify .step-icon{color:var(--os-cyan)}
.loop-step.learn .step-icon{color:var(--os-primary)}
.loop-step.active{border-color:var(--os-border-active);box-shadow:0 0 20px var(--os-primary-glow)}

/* ── Memory Rings ─────────────────────────────────── */
.memory-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px}
.memory-type{background:var(--os-surface);border:1px solid var(--os-border);border-radius:var(--os-r);padding:18px;text-align:center}
.memory-type .m-icon{font-size:24px;margin-bottom:8px}
.memory-type .m-label{font-size:12px;color:var(--os-muted);text-transform:uppercase;letter-spacing:.5px}
.memory-type .m-count{font-size:22px;font-weight:700;font-family:var(--os-mono);margin-top:4px}

/* ── Empty State ──────────────────────────────────── */
.empty-state{text-align:center;padding:48px;color:var(--os-muted)}
.empty-state i{font-size:36px;margin-bottom:12px;display:block;opacity:.3}

/* ── Action Bar ───────────────────────────────────── */
.os-actions{display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap}

/* ── Fleet V2 Panel ────────────────────────────────── */
.os-fleet-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:20px}
.os-fleet-stat{background:var(--os-surface);border:1px solid var(--os-border);border-radius:var(--os-r);padding:16px;text-align:center}
.os-fleet-stat .fval{font-size:24px;font-weight:800;font-family:var(--os-mono)}
.os-fleet-stat .flbl{font-size:10px;color:var(--os-muted);text-transform:uppercase;letter-spacing:.5px;margin-top:4px}
.os-fleet-domains{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px;margin-bottom:20px}
.os-fleet-domain{background:var(--os-surface);border:1px solid var(--os-border);border-radius:var(--os-r);padding:14px;cursor:pointer;transition:all .2s}
.os-fleet-domain:hover{border-color:var(--os-border-active);transform:translateY(-1px)}
.os-fleet-domain .fd-name{font-size:13px;font-weight:600;text-transform:capitalize;display:flex;align-items:center;gap:8px;margin-bottom:8px}
.os-fleet-domain .fd-bar{height:5px;border-radius:3px;background:rgba(255,255,255,.06);overflow:hidden;margin-bottom:6px}
.os-fleet-domain .fd-fill{height:100%;border-radius:3px;transition:width .5s}
.os-fleet-domain .fd-stats{display:flex;gap:10px;font-size:11px;color:var(--os-muted)}
.os-fleet-hier{display:flex;align-items:center;justify-content:center;gap:20px;padding:20px;background:var(--os-surface);border:1px solid var(--os-border);border-radius:var(--os-r);margin-bottom:20px;flex-wrap:wrap}
.os-hier-node{text-align:center;padding:12px 18px;border-radius:10px;border:1px solid var(--os-border)}
.os-hier-node .hval{font-size:20px;font-weight:900;font-family:var(--os-mono)}
.os-hier-node .hlbl{font-size:10px;color:var(--os-muted);text-transform:uppercase;letter-spacing:.5px;margin-top:2px}
.os-hier-arrow{color:var(--os-dim);font-size:14px}

/* ── Responsive ──────────────────────────────────── */
@media(max-width:768px){
    .os-wrap{padding:16px}
    .os-header{flex-wrap:wrap;gap:12px}
    .os-header .right{width:100%;justify-content:flex-end}
    .os-stats{grid-template-columns:repeat(2,1fr)}
    .agent-loop{flex-direction:column}
    .os-fleet-domains{grid-template-columns:1fr}
    .os-fleet-stats{grid-template-columns:repeat(2,1fr)}
}
</style>
    <link rel="stylesheet" href="/assets/css/design-tokens.css?v=20260310">
    <link rel="stylesheet" href="/assets/css/components.css?v=20260310">
    <script src="/assets/js/gds-utils.js?v=20260310" defer></script>
    <script src="/assets/js/gds-toast.js?v=20260310" defer></script>
    <script src="/assets/js/gds-modal.js?v=20260310" defer></script>
</head>
<body>
<div class="os-wrap">
    <!-- ═══ Header ═══════════════════════════════════════ -->
    <div class="os-header">
        <div class="logo"><i class="fas fa-microchip"></i></div>
        <div>
            <h1>Alfred <span>OS</span></h1>
            <a href="/dashboard" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
        <span class="badge">v2.0</span>
        <div class="right">
            <div class="status"><span class="dot"></span> System Online</div>
            <button class="os-btn danger" onclick="emergencyStop()"><i class="fas fa-power-off"></i> E-STOP</button>
        </div>
    </div>

    <!-- ═══ Tabs ═════════════════════════════════════════ -->
    <div class="os-tabs">
        <button class="os-tab active" onclick="showPanel('overview')"><i class="fas fa-th-large"></i> Overview</button>
        <button class="os-tab" onclick="showPanel('capabilities')"><i class="fas fa-puzzle-piece"></i> Capabilities <span class="count" id="cap-count">-</span></button>
        <button class="os-tab" onclick="showPanel('skills')"><i class="fas fa-wand-magic-sparkles"></i> Skills</button>
        <button class="os-tab" onclick="showPanel('tasks')"><i class="fas fa-list-check"></i> Tasks</button>
        <button class="os-tab" onclick="showPanel('memory')"><i class="fas fa-brain"></i> Memory</button>
        <button class="os-tab" onclick="showPanel('world')"><i class="fas fa-globe"></i> World State</button>
        <button class="os-tab" onclick="showPanel('policy')"><i class="fas fa-shield-halved"></i> Policies</button>
        <button class="os-tab" onclick="showPanel('simulation')"><i class="fas fa-flask"></i> Simulations</button>
        <button class="os-tab" onclick="showPanel('audit')"><i class="fas fa-scroll"></i> Audit Trail</button>
        <button class="os-tab" onclick="showPanel('fleet')"><i class="fas fa-rocket"></i> Fleet <span class="count" id="fleet-count" style="background:rgba(34,211,238,.2);color:#22d3ee">10K</span></button>
        <button class="os-tab" onclick="showPanel('approvals')"><i class="fas fa-check-circle"></i> Approvals <span class="count" id="approval-count" style="background:rgba(245,158,11,.2);color:#f59e0b">-</span></button>
        <button class="os-tab" onclick="showPanel('bridge')"><i class="fas fa-robot"></i> Device Bridge</button>
        <button class="os-tab" onclick="showPanel('runtime')"><i class="fas fa-play"></i> Runtime</button>
    </div>

    <!-- ═══ OVERVIEW PANEL ═══════════════════════════════ -->
    <div class="os-panel active" id="panel-overview">
        <div class="os-stats" id="overview-stats">
            <div class="os-stat cyan"><div class="label">Capabilities</div><div class="val" id="stat-caps">-</div><div class="sub">Registered tools</div></div>
            <div class="os-stat purple"><div class="label">Skills</div><div class="val" id="stat-skills">-</div><div class="sub">Composed behaviors</div></div>
            <div class="os-stat blue"><div class="label">Active Tasks</div><div class="val" id="stat-tasks">-</div><div class="sub">Running / queued</div></div>
            <div class="os-stat green"><div class="label">Memories</div><div class="val" id="stat-memories">-</div><div class="sub">Across 5 types</div></div>
            <div class="os-stat amber"><div class="label">Policies</div><div class="val" id="stat-policies">-</div><div class="sub">Active rules</div></div>
            <div class="os-stat pink"><div class="label">Simulations</div><div class="val" id="stat-sims">-</div><div class="sub">Run today</div></div>
        </div>

        <!-- Agent Loop Visualization -->
        <div class="os-section">
            <div class="os-section-title"><i class="fas fa-infinity"></i> Agent Execution Loop</div>
            <div class="agent-loop">
                <div class="loop-step observe"><div class="step-icon"><i class="fas fa-eye"></i></div><div class="step-name">Observe</div><div class="step-time">-</div></div>
                <div class="loop-step recall"><div class="step-icon"><i class="fas fa-brain"></i></div><div class="step-name">Recall</div><div class="step-time">-</div></div>
                <div class="loop-step plan"><div class="step-icon"><i class="fas fa-project-diagram"></i></div><div class="step-name">Plan</div><div class="step-time">-</div></div>
                <div class="loop-step simulate"><div class="step-icon"><i class="fas fa-flask"></i></div><div class="step-name">Simulate</div><div class="step-time">-</div></div>
                <div class="loop-step policy"><div class="step-icon"><i class="fas fa-shield-halved"></i></div><div class="step-name">Policy</div><div class="step-time">-</div></div>
                <div class="loop-step act"><div class="step-icon"><i class="fas fa-bolt"></i></div><div class="step-name">Act</div><div class="step-time">-</div></div>
                <div class="loop-step verify"><div class="step-icon"><i class="fas fa-check-double"></i></div><div class="step-name">Verify</div><div class="step-time">-</div></div>
                <div class="loop-step learn"><div class="step-icon"><i class="fas fa-graduation-cap"></i></div><div class="step-name">Learn</div><div class="step-time">-</div></div>
            </div>
        </div>

        <!-- Recent Audit -->
        <div class="os-section">
            <div class="os-section-title"><i class="fas fa-clock-rotate-left"></i> Recent Activity</div>
            <div class="os-table-wrap">
                <table class="os-table"><thead><tr><th>Time</th><th>Agent</th><th>Action</th><th>Capability</th><th>Status</th><th>Risk</th></tr></thead>
                <tbody id="recent-audit"></tbody></table>
            </div>
        </div>
    </div>

    <!-- ═══ CAPABILITIES PANEL ═══════════════════════════ -->
    <div class="os-panel" id="panel-capabilities">
        <div class="os-actions">
            <input type="text" id="cap-search" placeholder="Search capabilities..." class="os-btn" style="flex:1;max-width:400px" oninput="filterCaps(this.value)">
        </div>
        <div class="os-table-wrap">
            <table class="os-table"><thead><tr><th>ID</th><th>Name</th><th>Category</th><th>Type</th><th>Risk</th><th>Sim Required</th><th>Approval</th></tr></thead>
            <tbody id="caps-table"></tbody></table>
        </div>
    </div>

    <!-- ═══ SKILLS PANEL ═════════════════════════════════ -->
    <div class="os-panel" id="panel-skills">
        <div class="os-actions">
            <button class="os-btn primary" onclick="createSkillModal()"><i class="fas fa-plus"></i> New Skill</button>
        </div>
        <div class="os-table-wrap">
            <table class="os-table"><thead><tr><th>Skill</th><th>Category</th><th>Risk</th><th>Approval</th><th>Version</th><th>Author</th><th>Actions</th></tr></thead>
            <tbody id="skills-table"></tbody></table>
        </div>
    </div>

    <!-- ═══ TASKS PANEL ══════════════════════════════════ -->
    <div class="os-panel" id="panel-tasks">
        <div class="os-actions">
            <button class="os-btn primary" onclick="createTaskPrompt()"><i class="fas fa-plus"></i> New Task</button>
            <button class="os-btn" onclick="loadTasks('pending')"><i class="fas fa-clock"></i> Pending</button>
            <button class="os-btn" onclick="loadTasks('running')"><i class="fas fa-spinner"></i> Running</button>
            <button class="os-btn" onclick="loadTasks('completed')"><i class="fas fa-check"></i> Completed</button>
            <button class="os-btn" onclick="loadTasks()"><i class="fas fa-list"></i> All</button>
        </div>
        <div class="os-table-wrap">
            <table class="os-table"><thead><tr><th>Task ID</th><th>Goal</th><th>Agent</th><th>Priority</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
            <tbody id="tasks-table"></tbody></table>
        </div>
    </div>

    <!-- ═══ MEMORY PANEL ═════════════════════════════════ -->
    <div class="os-panel" id="panel-memory">
        <div class="os-actions">
            <input type="text" id="mem-search" placeholder="Search memories..." class="os-btn" style="flex:1;max-width:400px">
            <button class="os-btn" onclick="searchMemories()"><i class="fas fa-search"></i> Search</button>
            <button class="os-btn" onclick="consolidateMemory()"><i class="fas fa-compress-arrows-alt"></i> Consolidate</button>
        </div>
        <div class="memory-grid" id="memory-stats-grid"></div>
        <div class="os-section" style="margin-top:20px">
            <div class="os-section-title"><i class="fas fa-search"></i> Search Results</div>
            <div class="os-table-wrap">
                <table class="os-table"><thead><tr><th>Type</th><th>Label</th><th>Score</th><th>Created</th></tr></thead>
                <tbody id="memory-results"></tbody></table>
            </div>
        </div>
    </div>

    <!-- ═══ WORLD STATE PANEL ════════════════════════════ -->
    <div class="os-panel" id="panel-world">
        <div class="os-stats" id="world-stats"></div>
        <div class="os-section">
            <div class="os-section-title"><i class="fas fa-cube"></i> World Entities</div>
            <div class="os-table-wrap">
                <table class="os-table"><thead><tr><th>Entity ID</th><th>Type</th><th>Name</th><th>Status</th><th>Last Heartbeat</th></tr></thead>
                <tbody id="entities-table"></tbody></table>
            </div>
        </div>
        <div class="os-section">
            <div class="os-section-title"><i class="fas fa-database"></i> State Variables</div>
            <div class="os-table-wrap">
                <table class="os-table"><thead><tr><th>Key</th><th>Value</th><th>Type</th><th>Drift</th><th>Observed</th></tr></thead>
                <tbody id="state-table"></tbody></table>
            </div>
        </div>
    </div>

    <!-- ═══ POLICY PANEL ═════════════════════════════════ -->
    <div class="os-panel" id="panel-policy">
        <div class="os-actions" style="margin-bottom:16px">
            <button class="os-btn primary" onclick="policyCheckPrompt()"><i class="fas fa-shield-check"></i> Test Policy Check</button>
            <button class="os-btn danger" onclick="killSwitch()"><i class="fas fa-skull-crossbones"></i> KILL SWITCH (All Agents)</button>
        </div>
        <div class="os-table-wrap">
            <table class="os-table"><thead><tr><th>Policy</th><th>Scope</th><th>Target</th><th>Priority</th><th>Rules</th><th>Enabled</th></tr></thead>
            <tbody id="policies-table"></tbody></table>
        </div>
    </div>

    <!-- ═══ SIMULATION PANEL ═════════════════════════════ -->
    <div class="os-panel" id="panel-simulation">
        <div class="os-actions">
            <button class="os-btn primary" onclick="runSimPrompt()"><i class="fas fa-flask"></i> Run Simulation</button>
        </div>
        <div class="os-table-wrap">
            <table class="os-table"><thead><tr><th>Sim ID</th><th>Type</th><th>Outcome</th><th>Risk</th><th>Duration</th><th>Created</th></tr></thead>
            <tbody id="sims-table"></tbody></table>
        </div>
    </div>

    <!-- ═══ AUDIT PANEL ══════════════════════════════════ -->
    <div class="os-panel" id="panel-audit">
        <div class="os-stats" id="audit-stats-grid"></div>
        <div class="os-section" style="margin-bottom:20px">
            <div class="os-section-title"><i class="fas fa-exclamation-triangle"></i> Anomalies</div>
            <div id="anomaly-list" style="font-size:13px;color:var(--os-muted)">Loading...</div>
        </div>
        <div class="os-section" style="margin-bottom:20px">
            <div class="os-section-title"><i class="fas fa-film"></i> Task Replay</div>
            <div style="display:flex;gap:10px;margin-bottom:12px">
                <input type="text" id="replay-task-id" placeholder="Enter task_id to replay..." 
                    class="os-btn" style="flex:1;text-align:left" onkeydown="if(event.key==='Enter')replayTask()">
                <button class="os-btn primary" onclick="replayTask()"><i class="fas fa-play-circle"></i> Replay</button>
            </div>
            <div id="replay-output" class="os-card" style="display:none;font-family:var(--os-mono);font-size:12px;max-height:400px;overflow-y:auto"></div>
        </div>
        <div class="os-section">
            <div class="os-section-title"><i class="fas fa-scroll"></i> Audit Log</div>
            <div class="os-table-wrap">
                <table class="os-table"><thead><tr><th>Time</th><th>Trace</th><th>Agent</th><th>Action</th><th>Capability</th><th>Risk</th><th>Status</th><th>Duration</th></tr></thead>
                <tbody id="audit-table"></tbody></table>
            </div>
        </div>
    </div>

    <!-- ═══ FLEET PANEL (V2 — 10,000 Agents) ═══════════════ -->
    <div class="os-panel" id="panel-fleet">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <div class="os-section-title" style="margin:0;"><i class="fas fa-rocket"></i> Agent Fleet — 10,000 Agents</div>
            <div style="display:flex;gap:8px;">
                <button class="os-btn" onclick="OSV2.loadFleet()"><i class="fas fa-sync"></i> Refresh</button>
                <a class="os-btn primary" href="/fleet-dashboard" style="text-decoration:none;"><i class="fas fa-external-link-alt"></i> Fleet Dashboard</a>
                <a class="os-btn" href="/mission-control" style="text-decoration:none;"><i class="fas fa-satellite-dish"></i> Mission Control</a>
            </div>
        </div>

        <!-- Fleet Stats -->
        <div class="os-fleet-stats" id="os-fleet-stats">
            <div class="os-fleet-stat"><div class="fval" style="color:var(--os-primary);" id="osf-total">—</div><div class="flbl">Total Agents</div></div>
            <div class="os-fleet-stat"><div class="fval" style="color:var(--os-green);" id="osf-avail">—</div><div class="flbl">Available</div></div>
            <div class="os-fleet-stat"><div class="fval" style="color:var(--os-amber);" id="osf-busy">—</div><div class="flbl">Busy</div></div>
            <div class="os-fleet-stat"><div class="fval" style="color:var(--os-cyan);" id="osf-domains">—</div><div class="flbl">Domains</div></div>
            <div class="os-fleet-stat"><div class="fval" style="color:var(--os-purple);" id="osf-success">—</div><div class="flbl">Avg Success %</div></div>
            <div class="os-fleet-stat"><div class="fval" style="color:var(--os-blue);" id="osf-tasks">—</div><div class="flbl">Tasks Done</div></div>
        </div>

        <!-- Hierarchy -->
        <div class="os-fleet-hier">
            <div class="os-hier-node" style="border-color:var(--os-amber);">
                <div class="hval" style="color:var(--os-amber);">1</div>
                <div class="hlbl">Commander</div>
            </div>
            <div class="os-hier-arrow"><i class="fas fa-chevron-right"></i></div>
            <div class="os-hier-node" style="border-color:var(--os-purple);">
                <div class="hval" style="color:var(--os-purple);" id="osf-directors">10</div>
                <div class="hlbl">Directors</div>
            </div>
            <div class="os-hier-arrow"><i class="fas fa-chevron-right"></i></div>
            <div class="os-hier-node" style="border-color:var(--os-cyan);">
                <div class="hval" style="color:var(--os-cyan);" id="osf-specialists">4,989</div>
                <div class="hlbl">Specialists</div>
            </div>
        </div>

        <!-- Domain Breakdown -->
        <div class="os-section">
            <div class="os-section-title"><i class="fas fa-th-large"></i> Domain Breakdown</div>
            <div class="os-fleet-domains" id="os-fleet-domains"><div class="empty-state"><i class="fas fa-spinner fa-spin"></i> Loading...</div></div>
        </div>

        <!-- Agent Search -->
        <div class="os-section">
            <div class="os-section-title"><i class="fas fa-search"></i> Agent Lookup</div>
            <div class="os-actions">
                <select id="osf-domain-filter" class="os-btn" style="width:160px;">
                    <option value="">All Domains</option>
                    <option value="engineering">Engineering</option>
                    <option value="security">Security</option>
                    <option value="research">Research</option>
                    <option value="finance">Finance</option>
                    <option value="communications">Communications</option>
                    <option value="infrastructure">Infrastructure</option>
                    <option value="marketing">Marketing</option>
                    <option value="analytics">Analytics</option>
                    <option value="creative">Creative</option>
                    <option value="robotics">Robotics</option>
                </select>
                <input type="text" id="osf-search" placeholder="Search by name or ID..." class="os-btn" style="flex:1;max-width:300px;text-align:left;">
                <button class="os-btn primary" onclick="OSV2.searchAgents()"><i class="fas fa-search"></i> Search</button>
            </div>
            <div class="os-table-wrap" style="margin-top:12px;">
                <div id="os-fleet-agents"><div class="empty-state">Select a domain or search to view agents.</div></div>
            </div>
        </div>

        <!-- Route Task -->
        <div class="os-section">
            <div class="os-section-title"><i class="fas fa-route"></i> Quick Task Router</div>
            <div class="os-card">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                    <select id="osf-route-domain" class="os-btn" style="width:100%;">
                        <option value="engineering">Engineering</option>
                        <option value="security">Security</option>
                        <option value="research">Research</option>
                        <option value="finance">Finance</option>
                        <option value="infrastructure">Infrastructure</option>
                    </select>
                    <select id="osf-route-priority" class="os-btn" style="width:100%;">
                        <option value="normal">Normal</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
                <input type="text" id="osf-route-desc" placeholder="Task description..." class="os-btn" style="width:100%;margin-top:8px;text-align:left;">
                <div style="display:flex;gap:8px;margin-top:10px;">
                    <button class="os-btn primary" onclick="OSV2.routeTask()"><i class="fas fa-paper-plane"></i> Route</button>
                </div>
                <div id="osf-route-result" style="margin-top:10px;"></div>
            </div>
        </div>
    </div>

    <!-- ═══ APPROVALS PANEL ══════════════════════════════════ -->
    <div class="os-panel" id="panel-approvals">
        <div class="os-stats" id="approval-stats"></div>
        <div class="os-actions" style="margin-bottom:16px">
            <button class="os-btn" onclick="loadApprovals()"><i class="fas fa-sync"></i> Refresh</button>
            <button class="os-btn" onclick="loadApprovals('pending')"><i class="fas fa-hourglass-half"></i> Pending</button>
            <button class="os-btn" onclick="loadApprovals('approved')"><i class="fas fa-check"></i> Approved</button>
            <button class="os-btn" onclick="loadApprovals('denied')"><i class="fas fa-times"></i> Denied</button>
            <button class="os-btn" onclick="loadApprovals('expired')"><i class="fas fa-clock"></i> Expired</button>
            <button class="os-btn danger" onclick="expireApprovals()"><i class="fas fa-broom"></i> Expire Stale</button>
        </div>
        <div class="os-table-wrap">
            <table class="os-table">
                <thead><tr>
                    <th>ID</th><th>Task / Goal</th><th>Capability</th><th>Risk</th><th>Status</th>
                    <th>Wait Time</th><th>Expires</th><th>Actions</th>
                </tr></thead>
                <tbody id="approvals-table"></tbody>
            </table>
        </div>
    </div>

    <!-- ═══ BRIDGE PANEL ═════════════════════════════════ -->
    <div class="os-panel" id="panel-bridge">
        <div class="os-actions">
            <button class="os-btn primary" onclick="registerDevicePrompt()"><i class="fas fa-plus"></i> Register Device</button>
            <button class="os-btn" onclick="createGroupPrompt()"><i class="fas fa-layer-group"></i> Create Group</button>
            <button class="os-btn danger" onclick="emergencyStop()"><i class="fas fa-power-off"></i> Emergency Stop ALL</button>
        </div>

        <!-- Fleet Status Overview -->
        <div id="fleet-status" style="margin-bottom:20px"></div>

        <!-- Device Groups -->
        <div id="device-groups" style="margin-bottom:20px"></div>

        <!-- Device Table -->
        <h3 style="margin:0 0 10px;font-size:.95rem;color:var(--os-dim)"><i class="fas fa-microchip"></i> Registered Devices</h3>
        <div class="os-table-wrap">
            <table class="os-table"><thead><tr><th>Device ID</th><th>Name</th><th>Type</th><th>Protocol</th><th>Status</th><th>Last Heartbeat</th><th>Actions</th></tr></thead>
            <tbody id="devices-table"></tbody></table>
        </div>

        <!-- Sensor Pipeline (live aggregation) -->
        <div style="margin-top:20px">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                <h3 style="margin:0;font-size:.95rem;color:var(--os-dim)"><i class="fas fa-wave-square"></i> Sensor Pipeline</h3>
                <div style="display:flex;gap:8px;align-items:center">
                    <select id="pipeline-window" class="os-btn sm" style="width:100px">
                        <option value="1">1 min</option>
                        <option value="5" selected>5 min</option>
                        <option value="15">15 min</option>
                        <option value="60">1 hour</option>
                    </select>
                    <button class="os-btn sm" onclick="loadSensorPipeline()"><i class="fas fa-sync"></i></button>
                </div>
            </div>
            <div id="sensor-pipeline" class="empty-state">No sensor data yet</div>
        </div>

        <!-- Twin Snapshots Viewer -->
        <div style="margin-top:20px">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                <h3 style="margin:0;font-size:.95rem;color:var(--os-dim)"><i class="fas fa-camera"></i> Digital Twin Snapshots</h3>
                <div style="display:flex;gap:8px;align-items:center">
                    <input type="text" id="twin-device-id" placeholder="Device ID" class="os-btn sm" style="width:160px;text-align:left">
                    <button class="os-btn sm" onclick="loadTwinSnapshots()"><i class="fas fa-search"></i> View</button>
                    <button class="os-btn sm primary" onclick="createTwinSnapshot()"><i class="fas fa-camera"></i> Snapshot</button>
                </div>
            </div>
            <div id="twin-snapshots" class="empty-state">Enter a device ID to view snapshots</div>
        </div>

        <!-- Telemetry History Chart-like viewer -->
        <div style="margin-top:20px">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                <h3 style="margin:0;font-size:.95rem;color:var(--os-dim)"><i class="fas fa-chart-line"></i> Telemetry History</h3>
                <div style="display:flex;gap:8px;align-items:center">
                    <input type="text" id="telemetry-device-id" placeholder="Device ID" class="os-btn sm" style="width:130px;text-align:left">
                    <input type="text" id="telemetry-metric" placeholder="Metric (optional)" class="os-btn sm" style="width:130px;text-align:left">
                    <select id="telemetry-hours" class="os-btn sm" style="width:80px">
                        <option value="1">1h</option>
                        <option value="6">6h</option>
                        <option value="24" selected>24h</option>
                        <option value="168">7d</option>
                    </select>
                    <button class="os-btn sm" onclick="loadTelemetryHistory()"><i class="fas fa-chart-area"></i> Load</button>
                </div>
            </div>
            <div id="telemetry-history" class="empty-state">Enter a device ID to view telemetry history</div>
        </div>
    </div>

    <!-- ═══ RUNTIME PANEL ════════════════════════════════ -->
    <div class="os-panel" id="panel-runtime">
        <div class="os-card" style="margin-bottom:20px">
            <h3><i class="fas fa-play-circle"></i> Execute Agent Goal</h3>
            <p style="color:var(--os-muted);font-size:13px;margin-bottom:16px">
                Enter a natural language goal. The agent will observe → recall → plan → simulate → check policy → act → verify → learn.
            </p>
            <div style="display:flex;gap:10px;margin-bottom:16px">
                <input type="text" id="runtime-goal" placeholder="e.g. Check server resources and report status" 
                    class="os-btn" style="flex:1;text-align:left" onkeydown="if(event.key==='Enter')executeGoal()">
                <select id="runtime-agent" class="os-btn" style="width:140px">
                    <option value="alfred">Alfred</option>
                    <option value="nova">Nova</option>
                    <option value="sage">Sage</option>
                    <option value="atlas">Atlas</option>
                    <option value="forge">Forge</option>
                </select>
                <label class="os-btn" style="display:flex;align-items:center;gap:6px;cursor:pointer">
                    <input type="checkbox" id="runtime-dryrun" style="accent-color:var(--os-primary)"> Dry Run
                </label>
                <button class="os-btn primary" onclick="executeGoal()"><i class="fas fa-rocket"></i> Execute</button>
                <button class="os-btn" style="background:rgba(245,158,11,.15);color:#f59e0b" onclick="sandboxGoal()"><i class="fas fa-flask-vial"></i> Sandbox</button>
            </div>
        </div>

        <!-- Live Agent Loop Monitor -->
        <div class="os-section" style="margin-bottom:20px">
            <div class="os-section-title"><i class="fas fa-heartbeat"></i> Agent Loop Monitor</div>
            <div class="os-card" id="loop-monitor" style="padding:20px">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
                    <div>
                        <span id="loop-status-badge" style="background:var(--os-surface);padding:4px 12px;border-radius:12px;font-size:12px;color:var(--os-muted)">
                            <i class="fas fa-circle" style="font-size:8px"></i> Idle
                        </span>
                        <span id="loop-task-id" style="font-family:var(--os-mono);font-size:11px;color:var(--os-muted);margin-left:12px"></span>
                    </div>
                    <div id="loop-round-counter" style="font-size:13px;color:var(--os-muted)"></div>
                </div>
                <div style="display:flex;gap:4px;align-items:center;margin-bottom:16px" id="loop-phases">
                    <div class="loop-phase" data-phase="observe"><i class="fas fa-eye"></i> Observe</div>
                    <div class="loop-arrow">→</div>
                    <div class="loop-phase" data-phase="recall"><i class="fas fa-brain"></i> Recall</div>
                    <div class="loop-arrow">→</div>
                    <div class="loop-phase" data-phase="plan"><i class="fas fa-route"></i> Plan</div>
                    <div class="loop-arrow">→</div>
                    <div class="loop-phase" data-phase="simulate"><i class="fas fa-flask"></i> Simulate</div>
                    <div class="loop-arrow">→</div>
                    <div class="loop-phase" data-phase="policy"><i class="fas fa-shield-alt"></i> Policy</div>
                    <div class="loop-arrow">→</div>
                    <div class="loop-phase" data-phase="act"><i class="fas fa-bolt"></i> Act</div>
                    <div class="loop-arrow">→</div>
                    <div class="loop-phase" data-phase="verify"><i class="fas fa-check-double"></i> Verify</div>
                    <div class="loop-arrow">→</div>
                    <div class="loop-phase" data-phase="learn"><i class="fas fa-graduation-cap"></i> Learn</div>
                </div>
                <div id="loop-detail" style="font-size:12px;color:var(--os-muted);font-family:var(--os-mono);min-height:24px"></div>
            </div>
        </div>

        <div class="os-section">
            <div class="os-section-title"><i class="fas fa-terminal"></i> Execution Log</div>
            <div id="runtime-log" class="os-card" style="font-family:var(--os-mono);font-size:12px;min-height:200px;max-height:600px;overflow-y:auto;color:var(--os-muted)">
                Waiting for goal...
            </div>
        </div>
    </div>
</div>

<!-- ═══ JavaScript ════════════════════════════════════════ -->
<script src="/assets/js/agentos-client.js"></script>
<script src="/assets/js/agentos-dashboard-engine.js"></script>
<script src="/assets/js/agentos-v2.js"></script>

</body>
</html>
