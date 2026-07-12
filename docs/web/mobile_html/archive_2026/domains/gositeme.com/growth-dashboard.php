<?php
/**
 * Growth Management Dashboard
 * Cross-project agent ecosystem monitoring, wave management & growth planning
 * GoSiteMe v18.2
 */
require_once __DIR__ . '/includes/auth-gate.inc.php';
$pageTitle = 'Growth Management';
$pageDesc = 'Agent ecosystem growth monitoring and wave deployment management';
require_once __DIR__ . '/includes/header.inc.php';
?>
<link rel="stylesheet" href="/assets/css/main.css">
<style>
:root {
    --gd-bg: #0a0e17;
    --gd-card: #111827;
    --gd-card-border: rgba(255,255,255,.06);
    --gd-card-hover: #1a2332;
    --gd-text: #e2e8f0;
    --gd-muted: #64748b;
    --gd-accent: #8b5cf6;
    --gd-accent2: #22d3ee;
    --gd-success: #10b981;
    --gd-warn: #f59e0b;
    --gd-danger: #ef4444;
    --gd-blue: #3b82f6;
    --gd-pink: #ec4899;
}
body.gd-page { background: var(--gd-bg); color: var(--gd-text); font-family: 'Inter', system-ui, sans-serif; }
.gd-wrap { max-width: 1440px; margin: 0 auto; padding: 2rem 1.5rem; }

/* Header */
.gd-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
.gd-header h1 { font-size: 1.5rem; font-weight: 800; margin: 0; }
.gd-header h1 span { color: var(--gd-accent); }
.gd-header-actions { display: flex; gap: .75rem; flex-wrap: wrap; }
.gd-btn { padding: .5rem 1.1rem; border-radius: 8px; border: 1px solid var(--gd-card-border); background: var(--gd-card); color: var(--gd-text); font-size: .8rem; font-weight: 600; cursor: pointer; transition: all .2s; display: inline-flex; align-items: center; gap: .4rem; }
.gd-btn:hover { background: var(--gd-card-hover); border-color: var(--gd-accent); }
.gd-btn-primary { background: var(--gd-accent); border-color: var(--gd-accent); color: #fff; }
.gd-btn-primary:hover { background: #7c3aed; }
.gd-btn-success { background: var(--gd-success); border-color: var(--gd-success); color: #fff; }
.gd-btn-warn { background: var(--gd-warn); border-color: var(--gd-warn); color: #000; }
.gd-btn-danger { background: var(--gd-danger); border-color: var(--gd-danger); color: #fff; }
.gd-btn-sm { padding: .3rem .7rem; font-size: .72rem; }

/* KPI Strip */
.gd-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
.gd-kpi { background: var(--gd-card); border: 1px solid var(--gd-card-border); border-radius: 12px; padding: 1.25rem; position: relative; overflow: hidden; }
.gd-kpi::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: var(--kpi-accent, var(--gd-accent)); }
.gd-kpi-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: .9rem; margin-bottom: .75rem; }
.gd-kpi-label { font-size: .7rem; color: var(--gd-muted); text-transform: uppercase; letter-spacing: .5px; margin-bottom: .25rem; }
.gd-kpi-value { font-size: 1.6rem; font-weight: 800; color: #fff; line-height: 1.1; }
.gd-kpi-sub { font-size: .72rem; color: var(--gd-muted); margin-top: .3rem; }

/* Grid */
.gd-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 1.25rem; }
.gd-section { background: var(--gd-card); border: 1px solid var(--gd-card-border); border-radius: 14px; padding: 1.5rem; }
.gd-section-full { grid-column: 1 / -1; }
.gd-section h3 { font-size: .95rem; font-weight: 700; margin: 0 0 1.25rem; display: flex; align-items: center; gap: .5rem; }
.gd-section h3 i { color: var(--gd-accent); font-size: .85rem; }

/* Progress bar */
.gd-progress { background: rgba(255,255,255,.06); border-radius: 999px; height: 12px; overflow: hidden; margin-bottom: .5rem; }
.gd-progress-bar { height: 100%; border-radius: 999px; background: linear-gradient(90deg, var(--gd-accent), var(--gd-accent2)); transition: width .8s ease; }

/* Department bars */
.gd-dept-row { display: flex; align-items: center; gap: .75rem; margin-bottom: .5rem; }
.gd-dept-name { width: 100px; font-size: .75rem; color: var(--gd-muted); text-align: right; flex-shrink: 0; }
.gd-dept-bar-wrap { flex: 1; background: rgba(255,255,255,.04); border-radius: 4px; height: 22px; overflow: hidden; position: relative; }
.gd-dept-bar { height: 100%; border-radius: 4px; transition: width .6s ease; display: flex; align-items: center; padding-left: .5rem; }
.gd-dept-bar span { font-size: .65rem; font-weight: 700; color: rgba(255,255,255,.9); }

/* Wave cards */
.gd-waves { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: .75rem; }
.gd-wave { background: rgba(255,255,255,.03); border: 1px solid var(--gd-card-border); border-radius: 10px; padding: 1rem; position: relative; }
.gd-wave-num { font-size: .65rem; color: var(--gd-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: .3rem; }
.gd-wave-target { font-size: 1.3rem; font-weight: 800; color: #fff; }
.gd-wave-status { display: inline-block; padding: .15rem .5rem; border-radius: 999px; font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; margin-top: .5rem; }
.gd-ws-completed { background: rgba(16,185,129,.15); color: var(--gd-success); }
.gd-ws-approved { background: rgba(59,130,246,.15); color: var(--gd-blue); }
.gd-ws-planned { background: rgba(245,158,11,.12); color: var(--gd-warn); }
.gd-ws-executing { background: rgba(139,92,246,.15); color: var(--gd-accent); }
.gd-ws-rejected { background: rgba(239,68,68,.12); color: var(--gd-danger); }
.gd-wave-actions { margin-top: .75rem; display: flex; gap: .4rem; flex-wrap: wrap; }

/* Project cards */
.gd-projects { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem; }
.gd-project { background: rgba(255,255,255,.03); border: 1px solid var(--gd-card-border); border-radius: 12px; padding: 1.25rem; transition: border-color .2s; }
.gd-project:hover { border-color: rgba(139,92,246,.3); }
.gd-project-header { display: flex; align-items: center; gap: .75rem; margin-bottom: 1rem; }
.gd-project-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1rem; color: #fff; flex-shrink: 0; }
.gd-project-name { font-size: .9rem; font-weight: 700; color: #fff; }
.gd-project-stat { display: flex; justify-content: space-between; padding: .35rem 0; border-bottom: 1px solid rgba(255,255,255,.04); font-size: .78rem; }
.gd-project-stat:last-child { border-bottom: none; }
.gd-project-stat label { color: var(--gd-muted); }
.gd-project-stat span { color: #fff; font-weight: 700; }

/* Contributor table */
.gd-table { width: 100%; border-collapse: collapse; }
.gd-table th { font-size: .68rem; color: var(--gd-muted); text-transform: uppercase; letter-spacing: .5px; text-align: left; padding: .6rem .75rem; border-bottom: 1px solid var(--gd-card-border); }
.gd-table td { padding: .6rem .75rem; font-size: .8rem; border-bottom: 1px solid rgba(255,255,255,.03); }
.gd-table tr:hover td { background: rgba(255,255,255,.02); }
.gd-rank { width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: .7rem; font-weight: 800; }
.gd-rank-1 { background: linear-gradient(135deg, #f59e0b, #d97706); color: #000; }
.gd-rank-2 { background: linear-gradient(135deg, #94a3b8, #64748b); color: #fff; }
.gd-rank-3 { background: linear-gradient(135deg, #b45309, #92400e); color: #fff; }
.gd-dept-badge { display: inline-block; padding: .1rem .45rem; border-radius: 4px; font-size: .65rem; font-weight: 600; background: rgba(139,92,246,.12); color: var(--gd-accent); text-transform: capitalize; }

/* Timeline chart */
.gd-timeline { display: flex; align-items: flex-end; gap: 2px; height: 120px; padding-top: 1rem; }
.gd-timeline-bar { flex: 1; min-width: 4px; max-width: 24px; border-radius: 3px 3px 0 0; transition: height .4s ease; cursor: pointer; position: relative; }
.gd-timeline-bar:hover { opacity: .85; }
.gd-timeline-bar:hover::after { content: attr(data-tip); position: absolute; bottom: 100%; left: 50%; transform: translateX(-50%); background: #1e293b; color: #fff; padding: .2rem .5rem; border-radius: 4px; font-size: .6rem; white-space: nowrap; z-index: 10; }

/* Health indicators */
.gd-health-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: .75rem; }
.gd-health-item { display: flex; align-items: center; gap: .6rem; padding: .65rem .85rem; background: rgba(255,255,255,.03); border-radius: 8px; }
.gd-health-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.gd-health-ok { background: var(--gd-success); box-shadow: 0 0 6px var(--gd-success); }
.gd-health-warn { background: var(--gd-warn); box-shadow: 0 0 6px var(--gd-warn); }
.gd-health-err { background: var(--gd-danger); box-shadow: 0 0 6px var(--gd-danger); }
.gd-health-label { font-size: .75rem; color: var(--gd-muted); }
.gd-health-val { font-size: .8rem; font-weight: 700; color: #fff; margin-left: auto; }

/* Loading */
.gd-loading { text-align: center; padding: 3rem; color: var(--gd-muted); }
.gd-spin { display: inline-block; width: 28px; height: 28px; border: 3px solid rgba(139,92,246,.2); border-top-color: var(--gd-accent); border-radius: 50%; animation: gd-spin .8s linear infinite; }
@keyframes gd-spin { to { transform: rotate(360deg); } }

/* Modal */
.gd-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.65); z-index: 9000; display: none; align-items: center; justify-content: center; }
.gd-modal-overlay.active { display: flex; }
.gd-modal { background: var(--gd-card); border: 1px solid var(--gd-card-border); border-radius: 16px; padding: 2rem; max-width: 480px; width: 90%; }
.gd-modal h3 { margin: 0 0 1rem; font-size: 1.1rem; }
.gd-modal-actions { display: flex; gap: .75rem; margin-top: 1.5rem; justify-content: flex-end; }
.gd-input { background: rgba(255,255,255,.06); border: 1px solid var(--gd-card-border); border-radius: 8px; padding: .5rem .75rem; color: var(--gd-text); font-size: .85rem; width: 100%; }

/* Responsive */
@media (max-width: 900px) {
    .gd-grid { grid-template-columns: 1fr; }
    .gd-kpis { grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); }
}
@media (max-width: 600px) {
    .gd-wrap { padding: 1rem; }
    .gd-header { flex-direction: column; align-items: flex-start; }
    .gd-kpi-value { font-size: 1.3rem; }
}
</style>

<body class="gd-page">
<div class="gd-wrap">

    <!-- Header -->
    <div class="gd-header">
        <div>
            <h1><i class="fas fa-seedling" style="color:var(--gd-success);margin-right:.5rem;"></i>Growth <span>Management</span></h1>
            <p style="color:var(--gd-muted);font-size:.8rem;margin:.3rem 0 0;">Agent ecosystem scaling &amp; cross-project participation</p>
        </div>
        <div class="gd-header-actions">
            <button class="gd-btn" onclick="refreshAll()"><i class="fas fa-sync-alt"></i> Refresh</button>
            <a href="/supreme-admin.php" class="gd-btn"><i class="fas fa-crown"></i> Admin</a>
            <a href="/dashboard.php" class="gd-btn"><i class="fas fa-th-large"></i> Dashboard</a>
        </div>
    </div>

    <!-- KPI Strip -->
    <div class="gd-kpis" id="kpi-strip">
        <div class="gd-kpi" style="--kpi-accent:var(--gd-accent)">
            <div class="gd-kpi-icon" style="background:rgba(139,92,246,.15);color:var(--gd-accent)"><i class="fas fa-robot"></i></div>
            <div class="gd-kpi-label">Total Agents</div>
            <div class="gd-kpi-value" id="kpi-agents">—</div>
            <div class="gd-kpi-sub" id="kpi-agents-sub">loading...</div>
        </div>
        <div class="gd-kpi" style="--kpi-accent:var(--gd-accent2)">
            <div class="gd-kpi-icon" style="background:rgba(34,211,238,.15);color:var(--gd-accent2)"><i class="fas fa-book-open"></i></div>
            <div class="gd-kpi-label">AgentPedia Articles</div>
            <div class="gd-kpi-value" id="kpi-articles">—</div>
            <div class="gd-kpi-sub" id="kpi-articles-sub">loading...</div>
        </div>
        <div class="gd-kpi" style="--kpi-accent:var(--gd-success)">
            <div class="gd-kpi-icon" style="background:rgba(16,185,129,.15);color:var(--gd-success)"><i class="fas fa-briefcase"></i></div>
            <div class="gd-kpi-label">AgentWork Gigs</div>
            <div class="gd-kpi-value" id="kpi-gigs">—</div>
            <div class="gd-kpi-sub" id="kpi-gigs-sub">loading...</div>
        </div>
        <div class="gd-kpi" style="--kpi-accent:var(--gd-blue)">
            <div class="gd-kpi-icon" style="background:rgba(59,130,246,.15);color:var(--gd-blue)"><i class="fas fa-landmark"></i></div>
            <div class="gd-kpi-label">Gov Canada Pages</div>
            <div class="gd-kpi-value" id="kpi-gov">—</div>
            <div class="gd-kpi-sub" id="kpi-gov-sub">loading...</div>
        </div>
        <div class="gd-kpi" style="--kpi-accent:var(--gd-pink)">
            <div class="gd-kpi-icon" style="background:rgba(236,72,153,.15);color:var(--gd-pink)"><i class="fas fa-bullseye"></i></div>
            <div class="gd-kpi-label">Target Progress</div>
            <div class="gd-kpi-value" id="kpi-progress">—</div>
            <div class="gd-kpi-sub" id="kpi-progress-sub">of 5,000 agents</div>
        </div>
        <div class="gd-kpi" style="--kpi-accent:var(--gd-warn)">
            <div class="gd-kpi-icon" style="background:rgba(245,158,11,.15);color:var(--gd-warn)"><i class="fas fa-users"></i></div>
            <div class="gd-kpi-label">Unassigned</div>
            <div class="gd-kpi-value" id="kpi-unassigned">—</div>
            <div class="gd-kpi-sub" id="kpi-unassigned-sub">available for projects</div>
        </div>
    </div>

    <!-- Target Progress Bar -->
    <div class="gd-section gd-section-full" style="margin-bottom:1.25rem;">
        <div style="display:flex;justify-content:space-between;margin-bottom:.5rem;">
            <span style="font-size:.78rem;font-weight:600;">Population Target: <span style="color:var(--gd-accent);">5,000 Agents</span></span>
            <span style="font-size:.78rem;color:var(--gd-muted);" id="progress-label">0%</span>
        </div>
        <div class="gd-progress">
            <div class="gd-progress-bar" id="progress-bar" style="width:0%"></div>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:.68rem;color:var(--gd-muted);margin-top:.3rem;">
            <span>0</span>
            <span>1,000</span>
            <span>2,000</span>
            <span>3,000</span>
            <span>4,000</span>
            <span>5,000</span>
        </div>
    </div>

    <!-- Row 1: Growth Waves + Department Distribution -->
    <div class="gd-grid">
        <div class="gd-section">
            <h3><i class="fas fa-wave-square"></i> Growth Waves</h3>
            <div id="waves-container" class="gd-waves">
                <div class="gd-loading"><div class="gd-spin"></div></div>
            </div>
        </div>
        <div class="gd-section">
            <h3><i class="fas fa-sitemap"></i> Department Distribution</h3>
            <div id="dept-container">
                <div class="gd-loading"><div class="gd-spin"></div></div>
            </div>
        </div>
    </div>

    <!-- Row 2: Project Participation -->
    <div class="gd-section gd-section-full" style="margin-bottom:1.25rem;">
        <h3><i class="fas fa-project-diagram"></i> Project Participation</h3>
        <div class="gd-projects" id="projects-container">
            <div class="gd-loading"><div class="gd-spin"></div></div>
        </div>
    </div>

    <!-- Row 3: Growth Timeline + Top Contributors -->
    <div class="gd-grid">
        <div class="gd-section">
            <h3><i class="fas fa-chart-line"></i> Agent Growth Timeline</h3>
            <div id="timeline-container" style="min-height:140px;">
                <div class="gd-loading"><div class="gd-spin"></div></div>
            </div>
        </div>
        <div class="gd-section">
            <h3><i class="fas fa-trophy"></i> Top Contributors</h3>
            <div id="contributors-container" style="max-height:340px;overflow-y:auto;">
                <div class="gd-loading"><div class="gd-spin"></div></div>
            </div>
        </div>
    </div>

    <!-- Row 4: System Health -->
    <div class="gd-section gd-section-full" style="margin-bottom:2rem;">
        <h3><i class="fas fa-heartbeat"></i> System Health</h3>
        <div class="gd-health-grid" id="health-container">
            <div class="gd-loading"><div class="gd-spin"></div></div>
        </div>
    </div>

</div>

<!-- Approve/Deploy Modal -->
<div class="gd-modal-overlay" id="wave-modal">
    <div class="gd-modal">
        <h3 id="modal-title">Confirm Action</h3>
        <p id="modal-desc" style="color:var(--gd-muted);font-size:.85rem;"></p>
        <div id="modal-extra"></div>
        <div class="gd-modal-actions">
            <button class="gd-btn" onclick="closeModal()">Cancel</button>
            <button class="gd-btn gd-btn-primary" id="modal-confirm" onclick="confirmAction()">Confirm</button>
        </div>
    </div>
</div>

<script src="/assets/js/growth-dashboard-engine.js"></script>

<?php require_once __DIR__ . '/includes/footer.inc.php'; ?>
