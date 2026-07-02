<?php
$page_title = 'Agent Orchestrator — Autonomous Upgrade Engine | GoSiteMe';
$page_description = 'Deploy thousands of AI coding agents to continuously upgrade your platform. Real-time task management, persistent agent sessions, and automated quality gates.';
$page_canonical = 'https://root.com/agent-orchestrator.php';
$page_og_title = $page_title;
$page_og_description = $page_description;

include __DIR__ . '/includes/auth-gate.inc.php';
include __DIR__ . '/includes/site-header.inc.php';

$is_logged_in = !empty($_SESSION['logged_in']) && !empty($_SESSION['client_id']);
$client_id    = $is_logged_in ? (int)$_SESSION['client_id'] : 0;
$is_owner     = $client_id === 33;
?>
<style>
/* ═══════ Agent Orchestrator v1.0 ═══════ */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --ao-bg:#07070d;--ao-bg2:#0e0e18;--ao-card:#141422;--ao-card-hover:#1a1a2e;
  --ao-accent:#ff6b00;--ao-accent-glow:rgba(255,107,0,.25);--ao-accent2:#ff9248;
  --ao-green:#00e676;--ao-yellow:#ffd600;--ao-blue:#448aff;--ao-red:#ff5252;--ao-cyan:#18ffff;--ao-purple:#a855f7;
  --ao-text:#e8e8f0;--ao-text2:#9898b0;--ao-muted:#585878;
  --ao-border:rgba(255,255,255,.06);--ao-glass:rgba(255,255,255,.03);
  --ao-r:12px;--ao-r-sm:8px;--ao-r-lg:16px;
  --ao-font:'Segoe UI',system-ui,-apple-system,sans-serif;
  --ao-mono:'JetBrains Mono','Fira Code','Cascadia Code',monospace;
}

/* ═══════ LAYOUT ═══════ */
.ao-wrap{max-width:1440px;margin:0 auto;padding:0 1.5rem 4rem}

/* ═══════ HERO ═══════ */
.ao-hero{text-align:center;padding:3rem 1rem 2rem;position:relative}
.ao-hero::before{content:'';position:absolute;top:-40%;left:50%;transform:translateX(-50%);width:700px;height:700px;background:radial-gradient(circle,var(--ao-accent-glow) 0%,transparent 65%);pointer-events:none;animation:aoHeroPulse 5s ease-in-out infinite}
@keyframes aoHeroPulse{0%,100%{opacity:.3;transform:translateX(-50%) scale(1)}50%{opacity:.6;transform:translateX(-50%) scale(1.08)}}
.ao-hero h1{font-size:clamp(1.8rem,4.5vw,3rem);font-weight:800;letter-spacing:-.02em;position:relative}
.ao-hero .ao-hl{background:linear-gradient(135deg,var(--ao-accent),var(--ao-yellow));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.ao-hero .ao-sub{color:var(--ao-text2);font-size:1rem;margin-top:.6rem;position:relative}
.ao-hero-badges{display:flex;gap:.8rem;justify-content:center;margin-top:1.2rem;flex-wrap:wrap;position:relative}
.ao-hero-badge{display:inline-flex;align-items:center;gap:.4rem;background:var(--ao-card);border:1px solid var(--ao-border);padding:.4rem 1rem;border-radius:50px;font-size:.82rem;color:var(--ao-text2)}
.ao-hero-badge .ao-hb-val{font-weight:800;font-family:var(--ao-mono);color:var(--ao-green)}
.ao-hero-badge .ao-pulse{width:7px;height:7px;background:var(--ao-green);border-radius:50%;animation:aoPulse 2s ease-in-out infinite}
@keyframes aoPulse{0%,100%{box-shadow:0 0 0 0 rgba(0,230,118,.5)}50%{box-shadow:0 0 0 5px rgba(0,230,118,0)}}

/* ═══════ STATS ═══════ */
.ao-stats{display:grid;grid-template-columns:repeat(6,1fr);gap:1rem;margin-bottom:1.5rem}
.ao-stat{background:var(--ao-card);border:1px solid var(--ao-border);border-radius:var(--ao-r);padding:1rem;text-align:center;position:relative;overflow:hidden}
.ao-stat::before{content:'';position:absolute;top:0;left:0;right:0;height:2px}
.ao-stat:nth-child(1)::before{background:var(--ao-green)}
.ao-stat:nth-child(2)::before{background:var(--ao-accent)}
.ao-stat:nth-child(3)::before{background:var(--ao-blue)}
.ao-stat:nth-child(4)::before{background:var(--ao-yellow)}
.ao-stat:nth-child(5)::before{background:var(--ao-red)}
.ao-stat:nth-child(6)::before{background:var(--ao-purple)}
.ao-stat-val{font-size:1.6rem;font-weight:800;font-family:var(--ao-mono);line-height:1}
.ao-stat:nth-child(1) .ao-stat-val{color:var(--ao-green)}
.ao-stat:nth-child(2) .ao-stat-val{color:var(--ao-accent)}
.ao-stat:nth-child(3) .ao-stat-val{color:var(--ao-blue)}
.ao-stat:nth-child(4) .ao-stat-val{color:var(--ao-yellow)}
.ao-stat:nth-child(5) .ao-stat-val{color:var(--ao-red)}
.ao-stat:nth-child(6) .ao-stat-val{color:var(--ao-purple)}
.ao-stat-lbl{color:var(--ao-muted);font-size:.7rem;text-transform:uppercase;letter-spacing:.06em;margin-top:.3rem}

/* ═══════ TOOLBAR ═══════ */
.ao-toolbar{display:flex;gap:.8rem;margin-bottom:1.2rem;flex-wrap:wrap;align-items:center}
.ao-toolbar-left{display:flex;gap:.6rem;flex:1;flex-wrap:wrap;align-items:center}
.ao-search{background:var(--ao-bg2);border:1px solid var(--ao-border);border-radius:var(--ao-r-sm);padding:.55rem 1rem .55rem 2.2rem;color:var(--ao-text);font-size:.85rem;width:260px;outline:none;transition:border-color .2s;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23585878'%3E%3Cpath d='M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:.6rem center;background-size:1.1rem}
.ao-search:focus{border-color:var(--ao-accent)}
.ao-filter{background:var(--ao-bg2);border:1px solid var(--ao-border);border-radius:var(--ao-r-sm);padding:.5rem .8rem;color:var(--ao-text);font-size:.82rem;cursor:pointer;outline:none;appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23585878'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right .5rem center;background-size:1rem;padding-right:2rem}
.ao-toolbar-right{display:flex;gap:.6rem}

/* ═══════ BUTTONS ═══════ */
.ao-btn{display:inline-flex;align-items:center;gap:.4rem;padding:.55rem 1.1rem;border:none;border-radius:var(--ao-r-sm);font-size:.82rem;font-weight:700;cursor:pointer;transition:all .2s;font-family:var(--ao-font)}
.ao-btn-primary{background:linear-gradient(135deg,var(--ao-accent),#ff8533);color:#fff;box-shadow:0 2px 12px var(--ao-accent-glow)}
.ao-btn-primary:hover{transform:translateY(-1px);box-shadow:0 4px 20px rgba(255,107,0,.4)}
.ao-btn-secondary{background:var(--ao-card);border:1px solid var(--ao-border);color:var(--ao-text2)}
.ao-btn-secondary:hover{background:var(--ao-card-hover);color:var(--ao-text)}
.ao-btn-danger{background:rgba(255,82,82,.12);border:1px solid rgba(255,82,82,.2);color:var(--ao-red)}
.ao-btn-success{background:rgba(0,230,118,.12);border:1px solid rgba(0,230,118,.2);color:var(--ao-green)}
.ao-btn-sm{padding:.35rem .7rem;font-size:.75rem}

/* ═══════ MAIN GRID ═══════ */
.ao-main{display:grid;grid-template-columns:1fr 380px;gap:1.5rem}

/* ═══════ CARDS ═══════ */
.ao-card{background:var(--ao-card);border:1px solid var(--ao-border);border-radius:var(--ao-r-lg);padding:1.3rem;transition:border-color .25s}
.ao-card:hover{border-color:rgba(255,107,0,.15)}
.ao-card-hdr{display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem}
.ao-card-hdr h2{font-size:1rem;font-weight:700;display:flex;align-items:center;gap:.5rem}
.ao-card-hdr h2 i{color:var(--ao-accent);font-size:.9rem}
.ao-badge{font-size:.65rem;padding:.2rem .55rem;border-radius:50px;font-weight:700;text-transform:uppercase;letter-spacing:.04em}
.ao-badge-live{background:rgba(0,230,118,.12);color:var(--ao-green)}
.ao-badge-count{background:rgba(255,107,0,.12);color:var(--ao-accent2)}

/* ═══════ TASK LIST ═══════ */
.ao-tasks{display:flex;flex-direction:column;gap:.6rem;max-height:calc(100vh - 360px);min-height:400px;overflow-y:auto;padding-right:.3rem}
.ao-tasks::-webkit-scrollbar{width:4px}
.ao-tasks::-webkit-scrollbar-thumb{background:var(--ao-accent);border-radius:2px}
.ao-task{background:var(--ao-bg2);border:1px solid var(--ao-border);border-radius:var(--ao-r);padding:.9rem 1rem;transition:all .2s;cursor:pointer;position:relative;border-left:3px solid transparent}
.ao-task:hover{background:var(--ao-card-hover);border-color:rgba(255,107,0,.15)}
.ao-task.priority-P0{border-left-color:var(--ao-red)}
.ao-task.priority-P1{border-left-color:var(--ao-accent)}
.ao-task.priority-P2{border-left-color:var(--ao-blue)}
.ao-task.priority-P3{border-left-color:var(--ao-muted)}
.ao-task.priority-P4{border-left-color:var(--ao-purple)}
.ao-task-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:.4rem}
.ao-task-id{font-family:var(--ao-mono);font-size:.7rem;font-weight:700;color:var(--ao-accent2)}
.ao-task-status{font-size:.65rem;padding:.15rem .5rem;border-radius:50px;font-weight:700;text-transform:uppercase}
.status-pending{background:rgba(88,88,120,.15);color:var(--ao-muted)}
.status-claimed{background:rgba(255,214,0,.12);color:var(--ao-yellow)}
.status-running{background:rgba(0,230,118,.12);color:var(--ao-green);animation:statusPulse 2s ease-in-out infinite}
@keyframes statusPulse{0%,100%{opacity:1}50%{opacity:.6}}
.status-done{background:rgba(68,138,255,.12);color:var(--ao-blue)}
.status-failed{background:rgba(255,82,82,.12);color:var(--ao-red)}
.status-cancelled{background:rgba(88,88,120,.1);color:var(--ao-muted);text-decoration:line-through}
.ao-task-title{font-weight:600;font-size:.88rem;margin-bottom:.3rem;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.ao-task-meta{display:flex;gap:.8rem;font-size:.72rem;color:var(--ao-muted);align-items:center}
.ao-task-meta i{margin-right:.2rem;font-size:.65rem}
.ao-task-actions{display:flex;gap:.4rem;margin-top:.6rem}

/* ═══════ SIDEBAR ═══════ */
.ao-sidebar{display:flex;flex-direction:column;gap:1.2rem}

/* ═══════ CREATE TASK FORM ═══════ */
.ao-form-group{margin-bottom:.8rem}
.ao-form-group label{display:block;font-size:.72rem;font-weight:700;color:var(--ao-text2);margin-bottom:.3rem;text-transform:uppercase;letter-spacing:.04em}
.ao-input,.ao-select,.ao-textarea{width:100%;background:var(--ao-bg2);border:1px solid var(--ao-border);border-radius:var(--ao-r-sm);padding:.6rem .8rem;color:var(--ao-text);font-size:.85rem;font-family:var(--ao-font);outline:none;transition:border-color .2s}
.ao-input:focus,.ao-select:focus,.ao-textarea:focus{border-color:var(--ao-accent);box-shadow:0 0 0 2px var(--ao-accent-glow)}
.ao-textarea{resize:vertical;min-height:70px}
.ao-select{cursor:pointer;appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23585878'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right .5rem center;background-size:1rem;padding-right:2rem}
.ao-form-row{display:grid;grid-template-columns:1fr 1fr;gap:.6rem}

/* ═══════ ACTIVITY LOG ═══════ */
.ao-log-list{display:flex;flex-direction:column;gap:.3rem;max-height:300px;overflow-y:auto}
.ao-log-item{display:flex;gap:.5rem;padding:.5rem .6rem;border-radius:var(--ao-r-sm);background:var(--ao-bg2);font-size:.78rem;animation:aoSlideIn .3s ease-out;border-left:2px solid transparent}
@keyframes aoSlideIn{from{opacity:0;transform:translateX(-8px)}to{opacity:1;transform:translateX(0)}}
.ao-log-item.log-info{border-left-color:var(--ao-blue)}
.ao-log-item.log-success{border-left-color:var(--ao-green)}
.ao-log-item.log-warn{border-left-color:var(--ao-yellow)}
.ao-log-item.log-error{border-left-color:var(--ao-red)}
.ao-log-icon{width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.6rem;flex-shrink:0}
.log-info .ao-log-icon{background:rgba(68,138,255,.12);color:var(--ao-blue)}
.log-success .ao-log-icon{background:rgba(0,230,118,.12);color:var(--ao-green)}
.log-warn .ao-log-icon{background:rgba(255,214,0,.12);color:var(--ao-yellow)}
.log-error .ao-log-icon{background:rgba(255,82,82,.12);color:var(--ao-red)}
.ao-log-msg{flex:1;color:var(--ao-text2);min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.ao-log-msg strong{color:var(--ao-text);font-weight:700}
.ao-log-time{color:var(--ao-muted);font-size:.68rem;font-family:var(--ao-mono);flex-shrink:0}

/* ═══════ CATEGORY BREAKDOWN ═══════ */
.ao-cats{display:grid;grid-template-columns:1fr 1fr;gap:.5rem}
.ao-cat{background:var(--ao-bg2);border-radius:var(--ao-r-sm);padding:.6rem .7rem;display:flex;align-items:center;gap:.5rem;cursor:pointer;transition:background .2s;border:1px solid transparent}
.ao-cat:hover{background:var(--ao-card-hover);border-color:var(--ao-border)}
.ao-cat-icon{font-size:.85rem;width:28px;text-align:center}
.ao-cat-name{font-size:.75rem;color:var(--ao-text2);flex:1}
.ao-cat-count{font-family:var(--ao-mono);font-size:.78rem;font-weight:700;color:var(--ao-text)}

/* ═══════ SPAWN CONTROLS ═══════ */
.ao-spawn-panel{background:linear-gradient(135deg,rgba(255,107,0,.05),rgba(168,85,247,.05));border:1px solid rgba(255,107,0,.15);border-radius:var(--ao-r-lg);padding:1.3rem}
.ao-spawn-panel h3{font-size:.95rem;font-weight:700;margin-bottom:.8rem;display:flex;align-items:center;gap:.5rem}
.ao-spawn-panel h3 i{color:var(--ao-accent)}
.ao-spawn-info{font-size:.78rem;color:var(--ao-text2);margin-bottom:1rem;line-height:1.5}
.ao-spawn-actions{display:flex;flex-direction:column;gap:.5rem}
.ao-spawn-btn{display:flex;align-items:center;gap:.6rem;padding:.7rem 1rem;background:var(--ao-bg2);border:1px solid var(--ao-border);border-radius:var(--ao-r-sm);cursor:pointer;transition:all .2s;color:var(--ao-text)}
.ao-spawn-btn:hover{background:var(--ao-card-hover);border-color:var(--ao-accent);transform:translateX(3px)}
.ao-spawn-btn i{color:var(--ao-accent);font-size:1rem;width:24px;text-align:center}
.ao-spawn-btn .ao-sb-text{flex:1}
.ao-spawn-btn .ao-sb-title{font-weight:700;font-size:.82rem}
.ao-spawn-btn .ao-sb-desc{font-size:.7rem;color:var(--ao-muted)}

/* ═══════ TASK DETAIL MODAL ═══════ */
.ao-modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.7);backdrop-filter:blur(6px);z-index:1000;display:none;align-items:center;justify-content:center;padding:1rem}
.ao-modal-overlay.active{display:flex}
.ao-modal{background:var(--ao-card);border:1px solid var(--ao-border);border-radius:var(--ao-r-lg);max-width:640px;width:100%;max-height:85vh;overflow-y:auto;padding:1.5rem}
.ao-modal-hdr{display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem}
.ao-modal-hdr h3{font-size:1.1rem;font-weight:700}
.ao-modal-close{background:none;border:none;color:var(--ao-muted);font-size:1.2rem;cursor:pointer;padding:.3rem}
.ao-modal-close:hover{color:var(--ao-text)}
.ao-detail-row{display:flex;gap:1rem;margin-bottom:.6rem;font-size:.85rem}
.ao-detail-label{color:var(--ao-muted);min-width:100px;font-weight:600;text-transform:uppercase;font-size:.72rem}
.ao-detail-val{color:var(--ao-text);flex:1;word-break:break-word}
.ao-detail-val pre{background:var(--ao-bg2);border-radius:var(--ao-r-sm);padding:.5rem;font-family:var(--ao-mono);font-size:.78rem;white-space:pre-wrap;overflow-x:auto;margin-top:.3rem}

/* ═══════ RESPONSIVE ═══════ */
@media(max-width:1100px){.ao-main{grid-template-columns:1fr}.ao-sidebar{order:-1}}
@media(max-width:768px){
  .ao-stats{grid-template-columns:repeat(3,1fr)}
  .ao-hero h1{font-size:1.6rem}
  .ao-form-row{grid-template-columns:1fr}
  .ao-cats{grid-template-columns:1fr}
  .ao-toolbar{flex-direction:column;align-items:stretch}
  .ao-toolbar-left{flex-direction:column}
  .ao-search{width:100%}
}
@media(max-width:480px){.ao-stats{grid-template-columns:repeat(2,1fr)}}
@media(pointer:coarse){
  .ao-btn{padding:.7rem 1.2rem;font-size:.88rem}
  .ao-task{padding:1rem 1.1rem}
  .ao-spawn-btn{padding:.9rem 1.1rem}
}
</style>

<!-- Hero -->
<section class="ao-hero">
  <h1><span class="ao-hl">Agent Orchestrator</span></h1>
  <p class="ao-sub">Deploy autonomous AI agents to continuously upgrade your codebase — 24/7</p>
  <div class="ao-hero-badges">
    <div class="ao-hero-badge"><span class="ao-pulse"></span> <span class="ao-hb-val" id="heroRunning">0</span> agents running</div>
    <div class="ao-hero-badge"><span class="ao-hb-val" id="heroPending">0</span> tasks pending</div>
    <div class="ao-hero-badge"><span class="ao-hb-val" id="heroDone">0</span> completed</div>
    <div class="ao-hero-badge"><span class="ao-hb-val" id="heroRate">100</span>% success rate</div>
  </div>
</section>

<div class="ao-wrap">

  <!-- Stats Row -->
  <div class="ao-stats">
    <div class="ao-stat"><div class="ao-stat-val" id="statPending">0</div><div class="ao-stat-lbl">Pending</div></div>
    <div class="ao-stat"><div class="ao-stat-val" id="statRunning">0</div><div class="ao-stat-lbl">Running</div></div>
    <div class="ao-stat"><div class="ao-stat-val" id="statDone">0</div><div class="ao-stat-lbl">Done</div></div>
    <div class="ao-stat"><div class="ao-stat-val" id="statClaimed">0</div><div class="ao-stat-lbl">Claimed</div></div>
    <div class="ao-stat"><div class="ao-stat-val" id="statFailed">0</div><div class="ao-stat-lbl">Failed</div></div>
    <div class="ao-stat"><div class="ao-stat-val" id="statTotal">0</div><div class="ao-stat-lbl">Total</div></div>
  </div>

  <!-- Toolbar -->
  <div class="ao-toolbar">
    <div class="ao-toolbar-left">
      <input type="text" class="ao-search" id="searchInput" placeholder="Search tasks..." oninput="debouncedSearch()">
      <select class="ao-filter" id="filterStatus" onchange="loadTasks(1)">
        <option value="">All Status</option>
        <option value="pending">Pending</option>
        <option value="claimed">Claimed</option>
        <option value="running">Running</option>
        <option value="done">Done</option>
        <option value="failed">Failed</option>
      </select>
      <select class="ao-filter" id="filterCategory" onchange="loadTasks(1)">
        <option value="">All Categories</option>
        <option value="security">Security</option>
        <option value="frontend">Frontend</option>
        <option value="api">API</option>
        <option value="javascript">JavaScript</option>
        <option value="test">Tests</option>
        <option value="script">Scripts</option>
        <option value="docs">Docs</option>
        <option value="sdk">SDK</option>
        <option value="debt">Tech Debt</option>
        <option value="feature">New Feature</option>
      </select>
      <select class="ao-filter" id="filterPriority" onchange="loadTasks(1)">
        <option value="">All Priorities</option>
        <option value="P0">P0 - Critical</option>
        <option value="P1">P1 - High</option>
        <option value="P2">P2 - Medium</option>
        <option value="P3">P3 - Low</option>
        <option value="P4">P4 - R&D</option>
      </select>
    </div>
    <div class="ao-toolbar-right">
      <?php if ($is_owner): ?>
      <button class="ao-btn ao-btn-primary" onclick="importBacklog()"><i class="fas fa-file-import"></i> Import Backlog</button>
      <button class="ao-btn ao-btn-success" onclick="spawnAll()"><i class="fas fa-rocket"></i> Spawn All Pending</button>
      <?php endif; ?>
      <button class="ao-btn ao-btn-secondary" onclick="toggleCreateForm()"><i class="fas fa-plus"></i> New Task</button>
    </div>
  </div>

  <!-- Main Content -->
  <div class="ao-main">

    <!-- Task List -->
    <div>
      <div class="ao-card" style="padding:0;border:none;background:transparent">
        <div id="taskList" class="ao-tasks">
          <div style="text-align:center;padding:3rem;color:var(--ao-muted)">
            <i class="fas fa-spinner fa-spin" style="font-size:2rem;margin-bottom:1rem;display:block"></i>
            Loading tasks...
          </div>
        </div>
      </div>
      <div style="display:flex;justify-content:center;gap:.5rem;margin-top:1rem" id="pagination"></div>
    </div>

    <!-- Sidebar -->
    <div class="ao-sidebar">

      <!-- Spawn Controls -->
      <?php if ($is_owner): ?>
      <div class="ao-spawn-panel">
        <h3><i class="fas fa-bolt"></i> Agent Spawn Control</h3>
        <div class="ao-spawn-info">
          Launch persistent AI coding agents that run autonomously via the PM2 runner service.
          Each agent picks a task, edits code, validates, and reports back — no human intervention needed.
        </div>
        <div class="ao-spawn-actions">
          <div class="ao-spawn-btn" onclick="spawnBatch('security')">
            <i class="fas fa-shield-halved"></i>
            <div class="ao-sb-text">
              <div class="ao-sb-title">Security Sprint</div>
              <div class="ao-sb-desc">Spawn agents for all SEC-* tasks</div>
            </div>
          </div>
          <div class="ao-spawn-btn" onclick="spawnBatch('frontend')">
            <i class="fas fa-palette"></i>
            <div class="ao-sb-text">
              <div class="ao-sb-title">Frontend Sprint</div>
              <div class="ao-sb-desc">Spawn agents for all FE-* tasks</div>
            </div>
          </div>
          <div class="ao-spawn-btn" onclick="spawnBatch('api')">
            <i class="fas fa-server"></i>
            <div class="ao-sb-text">
              <div class="ao-sb-title">API Sprint</div>
              <div class="ao-sb-desc">Spawn agents for all API-* tasks</div>
            </div>
          </div>
          <div class="ao-spawn-btn" onclick="spawnBatch('test')">
            <i class="fas fa-vial"></i>
            <div class="ao-sb-text">
              <div class="ao-sb-title">Test Sprint</div>
              <div class="ao-sb-desc">Spawn agents for all TEST-* tasks</div>
            </div>
          </div>
          <div class="ao-spawn-btn" onclick="generatePrompt()">
            <i class="fas fa-terminal"></i>
            <div class="ao-sb-text">
              <div class="ao-sb-title">Generate CLI Command</div>
              <div class="ao-sb-desc">Get Claude Code CLI spawn command</div>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Create Task Form (hidden by default) -->
      <div class="ao-card" id="createForm" style="display:none">
        <div class="ao-card-hdr">
          <h2><i class="fas fa-plus-circle"></i> Create Task</h2>
          <button class="ao-btn ao-btn-sm ao-btn-secondary" onclick="toggleCreateForm()"><i class="fas fa-times"></i></button>
        </div>
        <form onsubmit="return createTask(event)">
          <div class="ao-form-group">
            <label>Title</label>
            <input type="text" class="ao-input" id="newTitle" placeholder="Upgrade agent-templates.php" required>
          </div>
          <div class="ao-form-group">
            <label>Description</label>
            <textarea class="ao-textarea" id="newDesc" placeholder="Add search, filtering, drag-drop builder..."></textarea>
          </div>
          <div class="ao-form-row">
            <div class="ao-form-group">
              <label>Category</label>
              <select class="ao-select" id="newCategory">
                <option value="frontend">Frontend</option>
                <option value="api">API</option>
                <option value="security">Security</option>
                <option value="javascript">JavaScript</option>
                <option value="test">Test</option>
                <option value="script">Script</option>
                <option value="docs">Docs</option>
                <option value="sdk">SDK</option>
                <option value="debt">Tech Debt</option>
                <option value="feature">New Feature</option>
              </select>
            </div>
            <div class="ao-form-group">
              <label>Priority</label>
              <select class="ao-select" id="newPriority">
                <option value="P0">P0 - Critical</option>
                <option value="P1">P1 - High</option>
                <option value="P2" selected>P2 - Medium</option>
                <option value="P3">P3 - Low</option>
                <option value="P4">P4 - R&D</option>
              </select>
            </div>
          </div>
          <div class="ao-form-group">
            <label>Target File (optional)</label>
            <input type="text" class="ao-input" id="newFile" placeholder="agent-templates.php">
          </div>
          <button type="submit" class="ao-btn ao-btn-primary" style="width:100%;margin-top:.5rem">
            <i class="fas fa-plus"></i> Create Task
          </button>
        </form>
      </div>

      <!-- Category Breakdown -->
      <div class="ao-card">
        <div class="ao-card-hdr">
          <h2><i class="fas fa-layer-group"></i> Categories</h2>
        </div>
        <div class="ao-cats" id="categoryGrid">
          <!-- Populated by JS -->
        </div>
      </div>

      <!-- Activity Log -->
      <div class="ao-card">
        <div class="ao-card-hdr">
          <h2><i class="fas fa-stream"></i> Activity Log</h2>
          <span class="ao-badge ao-badge-live"><i class="fas fa-circle" style="font-size:.35rem;vertical-align:middle;margin-right:.2rem"></i> LIVE</span>
        </div>
        <div class="ao-log-list" id="logList">
          <div style="text-align:center;padding:1rem;color:var(--ao-muted);font-size:.8rem">Loading activity...</div>
        </div>
      </div>

    </div><!-- /sidebar -->
  </div><!-- /main -->

</div><!-- /wrap -->

<!-- Task Detail Modal -->
<div class="ao-modal-overlay" id="taskModal">
  <div class="ao-modal">
    <div class="ao-modal-hdr">
      <h3 id="modalTitle">Task Details</h3>
      <button class="ao-modal-close" onclick="closeModal()">&times;</button>
    </div>
    <div id="modalBody"></div>
  </div>
</div>

<script>
window._orchIsOwner = <?php echo $is_owner ? 'true' : 'false'; ?>;
window._orchCsrfToken = '<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>';
</script>
<script src="/assets/js/agent-orchestrator-engine.js"></script>


<script type="application/ld+json">
{"@context":"https://schema.org","@type":"SoftwareApplication","name":"Agent Orchestrator","applicationCategory":"DeveloperApplication","operatingSystem":"Web","url":"https://root.com/agent-orchestrator.php","description":"Deploy thousands of AI coding agents to continuously upgrade your platform. Task management, agent spawning, and quality validation.","creator":{"@type":"Organization","name":"GoSiteMe","url":"https://root.com"}}
</script>
<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
