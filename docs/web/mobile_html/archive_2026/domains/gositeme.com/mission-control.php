<?php
/**
 * GoSiteMe Mission Control — Unified Command Interface
 * ═════════════════════════════════════════════════════
 * The owner's nerve center for the entire ecosystem.
 * - Advisory Panel briefings & recommendations
 * - Agent proposal approval/rejection workflow  
 * - Real-time agent reports & escalations
 * - 100-agent monitoring dashboard
 * - Ecosystem health overview
 * - Agenda integration
 */
require_once __DIR__ . '/includes/auth-gate.inc.php';

// Owner-only
if (empty($_SESSION['email']) || $_SESSION['email'] !== 'gositeme@gmail.com') {
    header('Location: /login.php');
    exit;
}

$configPath = __DIR__ . '/config/db.php';
if (file_exists($configPath)) { require_once $configPath; }

try {
    $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
} catch (PDOException $e) { $db = null; }

// Gather data
$stats = ['agents' => 101, 'pending_proposals' => 0, 'unread_reports' => 0, 'critical_alerts' => 0, 'active_tasks' => 0, 'services' => 9, 'optimizer_open' => 0, 'optimizer_breached' => 0];
$pendingProposals = [];
$recentReports = [];
$criticalAlerts = [];

if ($db) {
    try {
        $stats['agents'] = (int)$db->query("SELECT fleet FROM fleet_metrics_cache WHERE metric_key = 'fleet-50m' LIMIT 1")->fetchColumn();
        if ($stats['agents'] <= 0) {
            $stats['agents'] = (int)$db->query("SELECT COALESCE(TABLE_ROWS,0) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'alfred_agent_registry'")->fetchColumn();
        }
    } catch(Exception $e) {}
    try { $stats['active_tasks'] = (int)$db->query("SELECT COUNT(*) FROM alfred_agent_tasks WHERE status IN ('queued','running')")->fetchColumn(); } catch(Exception $e) {}
    try { $stats['pending_proposals'] = (int)$db->query("SELECT COUNT(*) FROM agent_proposals WHERE status IN ('pending','advisory_review')")->fetchColumn(); } catch(Exception $e) {}
    try { $stats['unread_reports'] = (int)$db->query("SELECT COUNT(*) FROM agent_reports WHERE requires_attention = 1 AND acknowledged = 0")->fetchColumn(); } catch(Exception $e) {}
    try { $stats['critical_alerts'] = (int)$db->query("SELECT COUNT(*) FROM agent_reports WHERE severity IN ('critical','emergency') AND acknowledged = 0")->fetchColumn(); } catch(Exception $e) {}
    try { $stats['optimizer_open'] = (int)$db->query("SELECT COUNT(*) FROM system_optimizer_findings WHERE status = 'open'")->fetchColumn(); } catch(Exception $e) {}
    try { $stats['optimizer_breached'] = (int)$db->query("SELECT COUNT(*) FROM system_optimizer_findings WHERE status = 'open' AND due_at IS NOT NULL AND due_at < NOW()") ->fetchColumn(); } catch(Exception $e) {}
    try { $pendingProposals = $db->query("SELECT * FROM agent_proposals WHERE status IN ('pending','advisory_review') ORDER BY FIELD(priority,'urgent','critical','high','medium','low'), created_at DESC LIMIT 20")->fetchAll(); } catch(Exception $e) {}
    try { $recentReports = $db->query("SELECT * FROM agent_reports ORDER BY created_at DESC LIMIT 30")->fetchAll(); } catch(Exception $e) {}
    try { $criticalAlerts = $db->query("SELECT * FROM agent_reports WHERE severity IN ('critical','emergency','warning') AND acknowledged = 0 ORDER BY created_at DESC LIMIT 10")->fetchAll(); } catch(Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mission Control — GoSiteMe</title>
<link rel="icon" href="/brand/favicon.png">
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<link rel="stylesheet" href="/assets/css/fonts.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root {
    --mc-bg: #06060f;
    --mc-surface: #0d0d1a;
    --mc-card: #111125;
    --mc-border: rgba(99,102,241,0.12);
    --mc-text: rgba(255,255,255,0.9);
    --mc-muted: rgba(255,255,255,0.45);
    --mc-blue: #6366f1;
    --mc-cyan: #22d3ee;
    --mc-green: #10b981;
    --mc-amber: #f59e0b;
    --mc-red: #ef4444;
    --mc-purple: #a855f7;
    --mc-gold: #fbbf24;
    --mc-grad: linear-gradient(135deg, #6366f1, #a855f7, #ec4899);
    --mc-radius: 14px;
    --sidebar-w: 260px;
}
body { background: var(--mc-bg); color: var(--mc-text); font-family: 'Inter','DM Sans',system-ui,sans-serif; min-height: 100vh; }
a { color: var(--mc-cyan); text-decoration: none; }
a:hover { text-decoration: underline; }

/* ─ SIDEBAR ─ */
.mc-sidebar {
    position: fixed; top: 0; left: 0; bottom: 0; width: var(--sidebar-w);
    background: var(--mc-surface); border-right: 1px solid var(--mc-border);
    display: flex; flex-direction: column; z-index: 100; overflow-y: auto;
}
.mc-logo {
    padding: 20px 20px 16px; display: flex; align-items: center; gap: 10px;
    border-bottom: 1px solid var(--mc-border);
}
.mc-logo-icon {
    width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center;
    background: var(--mc-grad); color: #fff; font-size: 16px; font-weight: 900;
}
.mc-logo-text { font-size: 15px; font-weight: 800; letter-spacing: -0.5px; }
.mc-logo-sub { font-size: 10px; color: var(--mc-muted); text-transform: uppercase; letter-spacing: 1.5px; }

.mc-nav { flex: 1; padding: 12px 10px; }
.mc-nav-label { font-size: 10px; color: var(--mc-muted); text-transform: uppercase; letter-spacing: 1.5px; padding: 16px 10px 6px; font-weight: 700; }
.mc-nav-item {
    display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 10px;
    font-size: 13px; font-weight: 500; color: var(--mc-muted); cursor: pointer; transition: all 0.15s;
    position: relative;
}
.mc-nav-item:hover { background: rgba(99,102,241,0.08); color: var(--mc-text); text-decoration: none; }
.mc-nav-item.active { background: rgba(99,102,241,0.12); color: var(--mc-text); font-weight: 600; }
.mc-nav-item i { width: 18px; text-align: center; font-size: 14px; }
.mc-nav-badge {
    position: absolute; right: 10px; min-width: 18px; height: 18px; padding: 0 5px;
    border-radius: 9px; font-size: 10px; font-weight: 700; display: flex; align-items: center; justify-content: center;
}
.mc-nav-badge.red { background: rgba(239,68,68,0.2); color: var(--mc-red); }
.mc-nav-badge.amber { background: rgba(245,158,11,0.2); color: var(--mc-amber); }
.mc-nav-badge.blue { background: rgba(99,102,241,0.2); color: var(--mc-blue); }

/* ─ MAIN ─ */
.mc-main { margin-left: var(--sidebar-w); min-height: 100vh; }
.mc-header {
    padding: 20px 28px; border-bottom: 1px solid var(--mc-border);
    display: flex; align-items: center; justify-content: space-between; gap: 16px;
    background: rgba(13,13,26,0.8); backdrop-filter: blur(20px); position: sticky; top: 0; z-index: 50;
}
.mc-header-title { font-size: 20px; font-weight: 800; letter-spacing: -0.5px; }
.mc-header-actions { display: flex; gap: 8px; }
.mc-header-btn {
    padding: 7px 14px; border-radius: 8px; font-size: 12px; font-weight: 600;
    background: rgba(99,102,241,0.1); border: 1px solid rgba(99,102,241,0.15);
    color: var(--mc-blue); cursor: pointer; transition: all 0.15s; display: flex; align-items: center; gap: 6px;
}
.mc-header-btn:hover { background: rgba(99,102,241,0.2); }

.mc-content { padding: 24px 28px; }

/* ─ PANELS ─ */
.mc-panel { display: none; }
.mc-panel.active { display: block; }

/* ─ STAT GRID ─ */
.mc-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 24px; }
.mc-stat {
    background: var(--mc-card); border: 1px solid var(--mc-border); border-radius: var(--mc-radius);
    padding: 20px; position: relative; overflow: hidden;
}
.mc-stat::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
}
.mc-stat-val { font-size: 28px; font-weight: 900; letter-spacing: -1px; }
.mc-stat-label { font-size: 11px; color: var(--mc-muted); text-transform: uppercase; letter-spacing: 1px; margin-top: 4px; }
.mc-stat-icon { position: absolute; top: 16px; right: 16px; font-size: 20px; opacity: 0.3; }

/* ─ CARDS ─ */
.mc-card {
    background: var(--mc-card); border: 1px solid var(--mc-border); border-radius: var(--mc-radius);
    padding: 24px; margin-bottom: 16px;
}
.mc-card-title { font-size: 16px; font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
.mc-card-title i { color: var(--mc-blue); }

/* ─ PROPOSAL CARDS ─ */
.mc-proposal {
    background: var(--mc-card); border: 1px solid var(--mc-border); border-radius: var(--mc-radius);
    padding: 20px; margin-bottom: 12px; transition: all 0.15s;
}
.mc-proposal:hover { border-color: rgba(99,102,241,0.3); }
.mc-proposal-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 10px; }
.mc-proposal-title { font-size: 15px; font-weight: 700; }
.mc-proposal-agent { font-size: 12px; color: var(--mc-muted); margin-top: 2px; }
.mc-proposal-body { font-size: 13px; color: var(--mc-muted); line-height: 1.6; margin-bottom: 14px; }
.mc-proposal-meta { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }
.mc-proposal-actions { display: flex; gap: 8px; margin-top: 14px; }

/* ─ BADGES ─ */
.mc-badge {
    display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 6px;
    font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;
}
.mc-badge.green { background: rgba(16,185,129,0.15); color: var(--mc-green); }
.mc-badge.red { background: rgba(239,68,68,0.15); color: var(--mc-red); }
.mc-badge.amber { background: rgba(245,158,11,0.15); color: var(--mc-amber); }
.mc-badge.blue { background: rgba(99,102,241,0.15); color: var(--mc-blue); }
.mc-badge.purple { background: rgba(168,85,247,0.15); color: var(--mc-purple); }
.mc-badge.cyan { background: rgba(34,211,238,0.15); color: var(--mc-cyan); }

/* ─ BUTTONS ─ */
.mc-btn {
    padding: 8px 16px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer;
    border: none; display: inline-flex; align-items: center; gap: 6px; transition: all 0.15s; font-family: inherit;
}
.mc-btn-approve { background: rgba(16,185,129,0.2); color: var(--mc-green); }
.mc-btn-approve:hover { background: rgba(16,185,129,0.35); }
.mc-btn-reject { background: rgba(239,68,68,0.15); color: var(--mc-red); }
.mc-btn-reject:hover { background: rgba(239,68,68,0.3); }
.mc-btn-info { background: rgba(99,102,241,0.15); color: var(--mc-blue); }
.mc-btn-info:hover { background: rgba(99,102,241,0.3); }
.mc-btn-primary { background: var(--mc-grad); color: #fff; }
.mc-btn-primary:hover { filter: brightness(1.1); transform: translateY(-1px); }

/* ─ ADVISORY SCORE BAR ─ */
.mc-score-bar { height: 6px; border-radius: 3px; background: rgba(255,255,255,0.06); overflow: hidden; width: 80px; }
.mc-score-fill { height: 100%; border-radius: 3px; transition: width 0.5s; }

/* ─ REPORT LIST ─ */
.mc-report {
    display: flex; gap: 12px; padding: 14px 0; border-bottom: 1px solid rgba(255,255,255,0.04);
    align-items: flex-start;
}
.mc-report:last-child { border-bottom: none; }
.mc-report-icon {
    width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center;
    font-size: 14px; flex-shrink: 0;
}
.mc-report-title { font-size: 13px; font-weight: 600; }
.mc-report-content { font-size: 12px; color: var(--mc-muted); line-height: 1.5; margin-top: 2px; }
.mc-report-time { font-size: 11px; color: var(--mc-muted); margin-top: 4px; }

/* ─ GRID ─ */
.mc-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.mc-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }

/* ─ PANEL MEMBERS ─ */
.mc-panel-member {
    display: flex; align-items: center; gap: 12px; padding: 12px 16px;
    background: rgba(255,255,255,0.02); border-radius: 10px; margin-bottom: 8px;
}
.mc-panel-avatar {
    width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center;
    font-size: 14px; font-weight: 700; color: #fff;
}
.mc-panel-name { font-size: 13px; font-weight: 600; }
.mc-panel-role { font-size: 11px; color: var(--mc-muted); }

/* ─ QUICK LINKS ─ */
.mc-quick-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px; }
.mc-quick-item {
    display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 16px 10px;
    background: var(--mc-card); border: 1px solid var(--mc-border); border-radius: 12px;
    font-size: 11px; font-weight: 600; color: var(--mc-muted); transition: all 0.15s; cursor: pointer;
}
.mc-quick-item:hover { background: rgba(99,102,241,0.08); color: var(--mc-text); border-color: rgba(99,102,241,0.2); text-decoration: none; }
.mc-quick-item i { font-size: 20px; }

/* ─ TABLE ─ */
.mc-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.mc-table th { text-align: left; padding: 10px 12px; color: var(--mc-muted); font-size: 11px; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid var(--mc-border); }
.mc-table td { padding: 10px 12px; border-bottom: 1px solid rgba(255,255,255,0.03); }
.mc-table tr:hover td { background: rgba(99,102,241,0.03); }

/* ─ FLEET V2 DOMAIN GRID ─ */
.mc-fleet-domains { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 12px; margin-bottom: 20px; }
.mc-fleet-domain {
    background: var(--mc-card); border: 1px solid var(--mc-border); border-radius: var(--mc-radius);
    padding: 16px; cursor: pointer; transition: all 0.2s;
}
.mc-fleet-domain:hover { border-color: rgba(99,102,241,0.3); transform: translateY(-1px); }
.mc-fleet-domain .domain-name { font-size: 14px; font-weight: 700; text-transform: capitalize; margin-bottom: 8px; display: flex; align-items: center; gap: 8px; }
.mc-fleet-domain .domain-bar { height: 6px; border-radius: 3px; background: rgba(255,255,255,0.06); overflow: hidden; margin: 8px 0; }
.mc-fleet-domain .domain-fill { height: 100%; border-radius: 3px; transition: width 0.5s; }
.mc-fleet-domain .domain-stats { display: flex; gap: 12px; font-size: 11px; color: var(--mc-muted); }

/* ─ FLEET V2 HIERARCHY ─ */
.mc-fleet-hier { display: flex; align-items: center; justify-content: center; gap: 24px; padding: 20px; background: rgba(255,255,255,0.01); border-radius: var(--mc-radius); margin-bottom: 20px; flex-wrap: wrap; }
.mc-hier-node {
    text-align: center; padding: 14px 20px; border-radius: 12px;
    border: 1px solid var(--mc-border); min-width: 120px;
}
.mc-hier-node .hier-val { font-size: 22px; font-weight: 900; }
.mc-hier-node .hier-label { font-size: 10px; color: var(--mc-muted); text-transform: uppercase; letter-spacing: 1px; margin-top: 2px; }
.mc-hier-arrow { color: var(--mc-muted); font-size: 16px; }

/* ─ FLEET OPS METRICS ─ */
.mc-metric-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px; margin-bottom: 20px; }
.mc-metric-card {
    background: var(--mc-card); border: 1px solid var(--mc-border); border-radius: var(--mc-radius);
    padding: 16px; text-align: center;
}
.mc-metric-card .metric-val { font-size: 24px; font-weight: 900; font-family: 'JetBrains Mono', monospace; }
.mc-metric-card .metric-label { font-size: 11px; color: var(--mc-muted); text-transform: uppercase; margin-top: 4px; }

/* ─ FLEET OPS COMPOSE ─ */
.mc-compose { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px; }
.mc-compose input, .mc-compose select, .mc-compose textarea {
    background: var(--mc-card); border: 1px solid var(--mc-border); color: var(--mc-text);
    padding: 10px 14px; border-radius: 8px; font-size: 13px; font-family: inherit;
}
.mc-compose textarea { grid-column: 1 / -1; min-height: 80px; resize: vertical; }
.mc-compose-actions { display: flex; gap: 8px; }

/* ─ FLEET SEARCH ─ */
.mc-fleet-search {
    display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap;
}
.mc-fleet-search input, .mc-fleet-search select {
    background: var(--mc-card); border: 1px solid var(--mc-border); color: var(--mc-text);
    padding: 8px 14px; border-radius: 8px; font-size: 12px;
}

/* ─ RESPONSIVE ─ */
@media (max-width: 1024px) {
    .mc-grid-2, .mc-grid-3 { grid-template-columns: 1fr; }
    .mc-fleet-domains { grid-template-columns: 1fr 1fr; }
    .mc-compose { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
    .mc-sidebar { width: 100%; height: auto; position: relative; border-right: none; border-bottom: 1px solid var(--mc-border); }
    .mc-nav { display: flex; overflow-x: auto; padding: 8px; gap: 4px; }
    .mc-nav-label { display: none; }
    .mc-nav-item { white-space: nowrap; padding: 8px 12px; font-size: 12px; }
    .mc-main { margin-left: 0; }
    .mc-content { padding: 16px; }
    .mc-stats { grid-template-columns: repeat(2, 1fr); }
}
</style>
    <link rel="stylesheet" href="/assets/css/design-tokens.css?v=20260310">
    <link rel="stylesheet" href="/assets/css/components.css?v=20260310">
    <script src="/assets/js/gds-utils.js?v=20260310" defer></script>
    <script src="/assets/js/gds-toast.js?v=20260310" defer></script>
    <script src="/assets/js/gds-modal.js?v=20260310" defer></script>
</head>
<body>

<!-- SIDEBAR -->
<div class="mc-sidebar">
    <div class="mc-logo">
        <div class="mc-logo-icon">MC</div>
        <div>
            <div class="mc-logo-text">Mission Control</div>
            <div class="mc-logo-sub">GoSiteMe Command</div>
        </div>
    </div>
    <div class="mc-nav">
        <div class="mc-nav-label">Command</div>
        <a class="mc-nav-item active" onclick="showPanel('overview')">
            <i class="fas fa-satellite-dish"></i> Overview
            <?php if ($stats['critical_alerts'] > 0): ?>
                <span class="mc-nav-badge red"><?= $stats['critical_alerts'] ?></span>
            <?php endif; ?>
        </a>
        <a class="mc-nav-item" onclick="showPanel('proposals')">
            <i class="fas fa-file-signature"></i> Proposals
            <?php if ($stats['pending_proposals'] > 0): ?>
                <span class="mc-nav-badge amber"><?= $stats['pending_proposals'] ?></span>
            <?php endif; ?>
        </a>
        <a class="mc-nav-item" onclick="showPanel('reports')">
            <i class="fas fa-clipboard-list"></i> Agent Reports
            <?php if ($stats['unread_reports'] > 0): ?>
                <span class="mc-nav-badge blue"><?= $stats['unread_reports'] ?></span>
            <?php endif; ?>
        </a>
        <a class="mc-nav-item" onclick="showPanel('advisory')">
            <i class="fas fa-users-cog"></i> Advisory Panel
        </a>

        <div class="mc-nav-label">Fleet Command</div>
        <a class="mc-nav-item" onclick="showPanel('agents')">
            <i class="fas fa-robot"></i> 10K Agent Fleet
            <span class="mc-nav-badge blue"><?= number_format($stats['agents']) ?></span>
        </a>
        <a class="mc-nav-item" onclick="showPanel('fleetops')">
            <i class="fas fa-tower-broadcast"></i> Fleet Ops
        </a>
        <a class="mc-nav-item" onclick="showPanel('optimizer')">
            <i class="fas fa-gauge-high"></i> Optimizer
            <?php if ($stats['optimizer_breached'] > 0): ?>
                <span class="mc-nav-badge red"><?= $stats['optimizer_breached'] ?></span>
            <?php elseif ($stats['optimizer_open'] > 0): ?>
                <span class="mc-nav-badge amber"><?= $stats['optimizer_open'] ?></span>
            <?php endif; ?>
        </a>

        <div class="mc-nav-label">Commander</div>
        <a class="mc-nav-item" href="/commanders-chronicle.php">
            <i class="fas fa-scroll"></i> Chronicle
        </a>
        <a class="mc-nav-item" href="/commander-memory.php">
            <i class="fas fa-brain"></i> Memory Vault
        </a>
        <a class="mc-nav-item" href="/justice-dashboard.php">
            <i class="fas fa-shield-alt"></i> Justice & Threats
        </a>

        <a class="mc-nav-item" onclick="showPanel('monitoring')">
            <i class="fas fa-satellite"></i> Monitoring Fleet
            <span class="mc-nav-badge green">100</span>
        </a>
        <a class="mc-nav-item" onclick="showPanel('ecosystem')">
            <i class="fas fa-globe"></i> Ecosystem Health
        </a>
        <a class="mc-nav-item" onclick="showPanel('permissions')">
            <i class="fas fa-key"></i> Permissions
        </a>

        <div class="mc-nav-label">Quick Access</div>
        <a class="mc-nav-item" href="/admin/agenda.php"><i class="fas fa-calendar-check"></i> Agenda</a>
        <a class="mc-nav-item" href="/intelligence-director"><i class="fas fa-user-secret"></i> Intel Director</a>
        <a class="mc-nav-item" href="/supreme-admin"><i class="fas fa-crown"></i> Supreme Admin</a>
        <a class="mc-nav-item" href="/veil/command-center.php"><i class="fas fa-mobile-alt"></i> Mobile Command</a>
        <a class="mc-nav-item" href="/analytics"><i class="fas fa-chart-line"></i> Analytics</a>
        <a class="mc-nav-item" href="/"><i class="fas fa-home"></i> Homepage</a>
    </div>
</div>

<!-- MAIN -->
<div class="mc-main">
    <div class="mc-header">
        <div>
            <div class="mc-header-title"><i class="fas fa-satellite-dish" style="color:var(--mc-blue);margin-right:8px;"></i>Mission Control</div>
        </div>
        <div class="mc-header-actions">
            <button class="mc-header-btn" onclick="refreshAll()"><i class="fas fa-sync"></i> Refresh</button>
            <button class="mc-header-btn" onclick="showPanel('proposals');loadProposals()"><i class="fas fa-bell"></i> <?= $stats['pending_proposals'] ?> Pending</button>
            <span style="font-size:11px;color:var(--mc-muted);display:flex;align-items:center;gap:6px;"><i class="fas fa-circle" style="color:var(--mc-green);font-size:6px;"></i> Online</span>
        </div>
    </div>

    <div class="mc-content">

        <!-- ═══ OVERVIEW PANEL ═══ -->
        <div class="mc-panel active" id="panel-overview">
            <!-- Stats -->
            <div class="mc-stats">
                <div class="mc-stat" style="--accent:var(--mc-blue);">
                    <div class="mc-stat-val" style="color:var(--mc-blue);" id="stat-agents"><?= $stats['agents'] ?></div>
                    <div class="mc-stat-label">Active Agents</div>
                    <div class="mc-stat-icon"><i class="fas fa-robot"></i></div>
                </div>
                <div class="mc-stat">
                    <div class="mc-stat-val" style="color:var(--mc-amber);" id="stat-pending"><?= $stats['pending_proposals'] ?></div>
                    <div class="mc-stat-label">Pending Proposals</div>
                    <div class="mc-stat-icon"><i class="fas fa-file-signature"></i></div>
                </div>
                <div class="mc-stat">
                    <div class="mc-stat-val" style="color:var(--mc-cyan);" id="stat-reports"><?= $stats['unread_reports'] ?></div>
                    <div class="mc-stat-label">Unread Reports</div>
                    <div class="mc-stat-icon"><i class="fas fa-clipboard-list"></i></div>
                </div>
                <div class="mc-stat">
                    <div class="mc-stat-val" style="color:<?= $stats['critical_alerts'] > 0 ? 'var(--mc-red)' : 'var(--mc-green)' ?>;" id="stat-alerts"><?= $stats['critical_alerts'] ?></div>
                    <div class="mc-stat-label">Critical Alerts</div>
                    <div class="mc-stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                </div>
                <div class="mc-stat">
                    <div class="mc-stat-val" style="color:var(--mc-green);"><?= $stats['active_tasks'] ?></div>
                    <div class="mc-stat-label">Active Tasks</div>
                    <div class="mc-stat-icon"><i class="fas fa-tasks"></i></div>
                </div>
                <div class="mc-stat">
                    <div class="mc-stat-val" style="color:var(--mc-purple);"><?= $stats['services'] ?></div>
                    <div class="mc-stat-label">PM2 Services</div>
                    <div class="mc-stat-icon"><i class="fas fa-server"></i></div>
                </div>
            </div>

            <div class="mc-grid-2">
                <!-- Advisory Brief -->
                <div class="mc-card">
                    <div class="mc-card-title"><i class="fas fa-users-cog"></i> Advisory Panel Brief</div>
                    <div id="advisory-brief">
                        <div style="color:var(--mc-muted);font-size:13px;">Loading briefing...</div>
                    </div>
                </div>

                <!-- Critical Alerts -->
                <div class="mc-card">
                    <div class="mc-card-title"><i class="fas fa-exclamation-triangle" style="color:var(--mc-red);"></i> Alerts & Escalations</div>
                    <div id="alerts-feed">
                        <?php if (empty($criticalAlerts)): ?>
                            <div style="text-align:center;padding:24px;color:var(--mc-green);font-size:14px;"><i class="fas fa-check-circle" style="font-size:20px;"></i><br><br>All Clear — No critical alerts</div>
                        <?php else: ?>
                            <?php foreach ($criticalAlerts as $alert): ?>
                                <div class="mc-report">
                                    <div class="mc-report-icon" style="background:rgba(239,68,68,0.1);color:var(--mc-red);"><i class="fas fa-exclamation"></i></div>
                                    <div>
                                        <div class="mc-report-title"><?= htmlspecialchars($alert['title']) ?></div>
                                        <div class="mc-report-content"><?= htmlspecialchars(substr($alert['content'], 0, 120)) ?></div>
                                        <div class="mc-report-time"><i class="fas fa-clock"></i> <?= htmlspecialchars($alert['created_at']) ?> — <?= htmlspecialchars($alert['agent_name']) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="mc-card" style="margin-top:8px;">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:12px;">
                    <div class="mc-card-title" style="margin-bottom:0;"><i class="fas fa-gauge-high" style="color:var(--mc-amber);"></i> Optimizer Snapshot</div>
                    <button class="mc-btn mc-btn-info" onclick="showPanel('optimizer')"><i class="fas fa-arrow-up-right-from-square"></i> Open Optimizer</button>
                </div>
                <div id="overview-optimizer-mini" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;">
                    <div style="padding:10px;border-radius:10px;background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.04);">
                        <div style="font-size:11px;color:var(--mc-muted);text-transform:uppercase;letter-spacing:1px;">Open</div>
                        <div id="ov-opt-open" style="font-size:24px;font-weight:800;color:var(--mc-amber);">0</div>
                    </div>
                    <div style="padding:10px;border-radius:10px;background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.04);">
                        <div style="font-size:11px;color:var(--mc-muted);text-transform:uppercase;letter-spacing:1px;">Breached SLA</div>
                        <div id="ov-opt-breached" style="font-size:24px;font-weight:800;color:var(--mc-red);">0</div>
                    </div>
                    <div style="padding:10px;border-radius:10px;background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.04);">
                        <div style="font-size:11px;color:var(--mc-muted);text-transform:uppercase;letter-spacing:1px;">Threads</div>
                        <div id="ov-opt-threads" style="font-size:24px;font-weight:800;color:var(--mc-cyan);">0</div>
                    </div>
                    <div style="padding:10px;border-radius:10px;background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.04);">
                        <div style="font-size:11px;color:var(--mc-muted);text-transform:uppercase;letter-spacing:1px;">Severity</div>
                        <div id="ov-opt-severity" style="font-size:16px;font-weight:800;color:var(--mc-green);padding-top:6px;text-transform:uppercase;">normal</div>
                    </div>
                </div>
            </div>

            <!-- Recent Proposals Needing Attention -->
            <?php if (!empty($pendingProposals)): ?>
            <div class="mc-card" style="margin-top:8px;">
                <div class="mc-card-title"><i class="fas fa-file-signature" style="color:var(--mc-amber);"></i> Proposals Awaiting Your Approval</div>
                <?php foreach (array_slice($pendingProposals, 0, 5) as $p): ?>
                    <div class="mc-proposal">
                        <div class="mc-proposal-header">
                            <div>
                                <div class="mc-proposal-title"><?= htmlspecialchars($p['title']) ?></div>
                                <div class="mc-proposal-agent"><?= htmlspecialchars($p['agent_name']) ?> · <?= htmlspecialchars($p['category']) ?></div>
                            </div>
                            <div style="display:flex;gap:6px;align-items:center;">
                                <?php
                                $priorityColors = ['urgent'=>'red','critical'=>'red','high'=>'amber','medium'=>'blue','low'=>'green'];
                                $pc = $priorityColors[$p['priority']] ?? 'blue';
                                ?>
                                <span class="mc-badge <?= $pc ?>"><?= $p['priority'] ?></span>
                                <?php if ($p['advisory_score'] !== null): ?>
                                    <div style="display:flex;align-items:center;gap:6px;">
                                        <div class="mc-score-bar">
                                            <div class="mc-score-fill" style="width:<?= $p['advisory_score'] ?>%;background:<?= $p['advisory_score'] >= 75 ? 'var(--mc-green)' : ($p['advisory_score'] >= 50 ? 'var(--mc-amber)' : 'var(--mc-red)') ?>;"></div>
                                        </div>
                                        <span style="font-size:11px;color:var(--mc-muted);"><?= $p['advisory_score'] ?>/100</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="mc-proposal-body"><?= htmlspecialchars(substr($p['description'], 0, 200)) ?></div>
                        <div class="mc-proposal-meta">
                            <?php if ($p['estimated_cost'] > 0): ?>
                                <span class="mc-badge amber"><i class="fas fa-dollar-sign"></i> <?= number_format($p['estimated_cost'], 2) ?></span>
                            <?php else: ?>
                                <span class="mc-badge green"><i class="fas fa-dollar-sign"></i> Free</span>
                            <?php endif; ?>
                            <span class="mc-badge <?= $p['risk_level'] === 'high' || $p['risk_level'] === 'critical' ? 'red' : 'blue' ?>"><?= $p['risk_level'] ?> risk</span>
                            <?php if ($p['estimated_hours'] > 0): ?>
                                <span class="mc-badge purple"><i class="fas fa-clock"></i> <?= $p['estimated_hours'] ?>h</span>
                            <?php endif; ?>
                        </div>
                        <div class="mc-proposal-actions">
                            <button class="mc-btn mc-btn-approve" onclick="approveProposal(<?= $p['id'] ?>)"><i class="fas fa-check"></i> Approve</button>
                            <button class="mc-btn mc-btn-reject" onclick="rejectProposal(<?= $p['id'] ?>)"><i class="fas fa-times"></i> Reject</button>
                            <button class="mc-btn mc-btn-info" onclick="viewProposal(<?= $p['id'] ?>)"><i class="fas fa-eye"></i> Details</button>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (count($pendingProposals) > 5): ?>
                    <div style="text-align:center;padding:12px;">
                        <button class="mc-btn mc-btn-info" onclick="showPanel('proposals')"><i class="fas fa-arrow-right"></i> View All <?= count($pendingProposals) ?> Proposals</button>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Quick Actions -->
            <div class="mc-card" style="margin-top:8px;">
                <div class="mc-card-title"><i class="fas fa-bolt"></i> Quick Actions</div>
                <div class="mc-quick-grid">
                    <a class="mc-quick-item" href="/alfred"><i class="fas fa-brain" style="color:var(--mc-blue);"></i> Alfred AI</a>
                    <a class="mc-quick-item" href="/games"><i class="fas fa-gamepad" style="color:var(--mc-red);"></i> Games</a>
                    <a class="mc-quick-item" href="/pulse"><i class="fas fa-heart" style="color:var(--mc-purple);"></i> Pulse</a>
                    <a class="mc-quick-item" href="/marketplace"><i class="fas fa-store" style="color:var(--mc-cyan);"></i> Marketplace</a>
                    <a class="mc-quick-item" href="/editor/"><i class="fas fa-code" style="color:var(--mc-green);"></i> GoCodeMe</a>
                    <a class="mc-quick-item" href="/voice"><i class="fas fa-phone" style="color:var(--mc-amber);"></i> Voice Portal</a>
                    <a class="mc-quick-item" href="/security"><i class="fas fa-shield-alt" style="color:var(--mc-green);"></i> Security</a>
                    <a class="mc-quick-item" href="/status"><i class="fas fa-heartbeat" style="color:var(--mc-red);"></i> System Status</a>
                    <a class="mc-quick-item" href="/reporting-dashboard"><i class="fas fa-chart-bar" style="color:var(--mc-blue);"></i> Reports</a>
                    <a class="mc-quick-item" href="/finance-dashboard"><i class="fas fa-wallet" style="color:var(--mc-gold);"></i> Finance</a>
                    <a class="mc-quick-item" href="/alfred-browser"><i class="fas fa-globe" style="color:var(--mc-cyan);"></i> Veil Browser</a>
                    <a class="mc-quick-item" href="/vr/"><i class="fas fa-vr-cardboard" style="color:var(--mc-purple);"></i> VR Worlds</a>
                </div>
            </div>
        </div>

        <!-- ═══ PROPOSALS PANEL ═══ -->
        <div class="mc-panel" id="panel-proposals">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                <h2 style="font-size:20px;font-weight:800;">Agent Proposals</h2>
                <div style="display:flex;gap:8px;">
                    <select id="proposal-filter-status" onchange="loadProposals()" style="background:var(--mc-card);border:1px solid var(--mc-border);color:var(--mc-text);padding:6px 12px;border-radius:8px;font-size:12px;">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="advisory_review">Advisory Review</option>
                        <option value="approved">Approved</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="rejected">Rejected</option>
                    </select>
                    <select id="proposal-filter-category" onchange="loadProposals()" style="background:var(--mc-card);border:1px solid var(--mc-border);color:var(--mc-text);padding:6px 12px;border-radius:8px;font-size:12px;">
                        <option value="">All Categories</option>
                        <option value="feature">Feature</option>
                        <option value="security">Security</option>
                        <option value="optimization">Optimization</option>
                        <option value="research">Research</option>
                        <option value="infrastructure">Infrastructure</option>
                        <option value="integration">Integration</option>
                        <option value="expansion">Expansion</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>
            </div>
            <div id="proposals-list"><div style="text-align:center;color:var(--mc-muted);padding:40px;">Loading proposals...</div></div>
        </div>

        <!-- ═══ REPORTS PANEL ═══ -->
        <div class="mc-panel" id="panel-reports">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                <h2 style="font-size:20px;font-weight:800;">Agent Reports</h2>
                <div style="display:flex;gap:8px;">
                    <select id="report-filter-type" onchange="loadReports()" style="background:var(--mc-card);border:1px solid var(--mc-border);color:var(--mc-text);padding:6px 12px;border-radius:8px;font-size:12px;">
                        <option value="">All Types</option>
                        <option value="status">Status</option>
                        <option value="progress">Progress</option>
                        <option value="alert">Alert</option>
                        <option value="escalation">Escalation</option>
                        <option value="incident">Incident</option>
                        <option value="discovery">Discovery</option>
                    </select>
                    <label style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--mc-muted);cursor:pointer;">
                        <input type="checkbox" id="report-unread-only" onchange="loadReports()"> Unread only
                    </label>
                </div>
            </div>
            <div id="reports-list"><div style="text-align:center;color:var(--mc-muted);padding:40px;">Loading reports...</div></div>
        </div>

        <!-- ═══ ADVISORY PANEL ═══ -->
        <div class="mc-panel" id="panel-advisory">
            <h2 style="font-size:20px;font-weight:800;margin-bottom:20px;"><i class="fas fa-users-cog" style="color:var(--mc-purple);margin-right:8px;"></i>Advisory Panel</h2>
            <p style="color:var(--mc-muted);font-size:14px;margin-bottom:24px;">Your 5-member AI advisory council automatically reviews every proposal, scores risk, and makes recommendations. Critical decisions always come to you.</p>
            
            <div class="mc-grid-2">
                <!-- Panel Members -->
                <div class="mc-card">
                    <div class="mc-card-title"><i class="fas fa-user-tie"></i> Council Members</div>
                    <div class="mc-panel-member">
                        <div class="mc-panel-avatar" style="background:linear-gradient(135deg,#6366f1,#a855f7);"><i class="fas fa-chess-queen"></i></div>
                        <div>
                            <div class="mc-panel-name">Sage</div>
                            <div class="mc-panel-role">Strategic Advisor — Long-term value, roadmap alignment, ecosystem growth</div>
                        </div>
                        <span class="mc-badge green" style="margin-left:auto;">Active</span>
                    </div>
                    <div class="mc-panel-member">
                        <div class="mc-panel-avatar" style="background:linear-gradient(135deg,#ef4444,#f97316);"><i class="fas fa-shield-alt"></i></div>
                        <div>
                            <div class="mc-panel-name">Sentinel</div>
                            <div class="mc-panel-role">Security Advisor — Risk assessment, vulnerability analysis, compliance</div>
                        </div>
                        <span class="mc-badge green" style="margin-left:auto;">Active</span>
                    </div>
                    <div class="mc-panel-member">
                        <div class="mc-panel-avatar" style="background:linear-gradient(135deg,#22d3ee,#2563eb);"><i class="fas fa-globe"></i></div>
                        <div>
                            <div class="mc-panel-name">Atlas</div>
                            <div class="mc-panel-role">Operations Advisor — Resource efficiency, infrastructure, scaling</div>
                        </div>
                        <span class="mc-badge green" style="margin-left:auto;">Active</span>
                    </div>
                    <div class="mc-panel-member">
                        <div class="mc-panel-avatar" style="background:linear-gradient(135deg,#f59e0b,#fbbf24);"><i class="fas fa-lightbulb"></i></div>
                        <div>
                            <div class="mc-panel-name">Nova</div>
                            <div class="mc-panel-role">Innovation Advisor — Growth potential, new markets, competitive edge</div>
                        </div>
                        <span class="mc-badge green" style="margin-left:auto;">Active</span>
                    </div>
                    <div class="mc-panel-member">
                        <div class="mc-panel-avatar" style="background:linear-gradient(135deg,#10b981,#34d399);"><i class="fas fa-microchip"></i></div>
                        <div>
                            <div class="mc-panel-name">Cipher</div>
                            <div class="mc-panel-role">Technical Advisor — Implementation feasibility, architecture, performance</div>
                        </div>
                        <span class="mc-badge green" style="margin-left:auto;">Active</span>
                    </div>
                </div>

                <!-- Panel Rules -->
                <div class="mc-card">
                    <div class="mc-card-title"><i class="fas fa-gavel"></i> Governance Rules</div>
                    <div style="font-size:13px;color:var(--mc-muted);line-height:1.8;">
                        <div style="padding:10px 0;border-bottom:1px solid rgba(255,255,255,0.04);">
                            <span class="mc-badge green" style="margin-right:8px;">AUTO</span>
                            Score ≥75 + Cost &lt;$50 + Low Risk → <strong style="color:var(--mc-green);">Auto-approved</strong>
                        </div>
                        <div style="padding:10px 0;border-bottom:1px solid rgba(255,255,255,0.04);">
                            <span class="mc-badge amber" style="margin-right:8px;">REVIEW</span>
                            Score 50-74 or Med Risk → <strong style="color:var(--mc-amber);">Sent to your queue</strong>
                        </div>
                        <div style="padding:10px 0;border-bottom:1px solid rgba(255,255,255,0.04);">
                            <span class="mc-badge red" style="margin-right:8px;">ESCALATE</span>
                            Cost &gt;$500 or High/Critical Risk → <strong style="color:var(--mc-red);">Mandatory owner review</strong>
                        </div>
                        <div style="padding:10px 0;border-bottom:1px solid rgba(255,255,255,0.04);">
                            <span class="mc-badge purple" style="margin-right:8px;">SECURITY</span>
                            Security proposals get +10 priority boost
                        </div>
                        <div style="padding:10px 0;">
                            <span class="mc-badge cyan" style="margin-right:8px;">AGENTS</span>
                            Agents can file, edit, and deploy approved proposals. Unapproved changes are blocked.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Advisory Recommendations -->
            <div class="mc-card" style="margin-top:8px;">
                <div class="mc-card-title"><i class="fas fa-lightbulb" style="color:var(--mc-amber);"></i> Panel Recommendations</div>
                <div id="advisory-recommendations">
                    <div style="color:var(--mc-muted);font-size:13px;">Loading recommendations...</div>
                </div>
            </div>
        </div>

        <!-- ═══ AGENTS PANEL (V2 — 5,000 Fleet) ═══ -->
        <div class="mc-panel" id="panel-agents">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                <h2 style="font-size:20px;font-weight:800;"><i class="fas fa-robot" style="color:var(--mc-blue);margin-right:8px;"></i>Agent Fleet — 5,000 Agents</h2>
                <div style="display:flex;gap:8px;">
                    <div class="mc-header-btn" onclick="loadFleetV2()"><i class="fas fa-sync"></i> Refresh</div>
                    <a class="mc-header-btn" href="/fleet-dashboard" style="text-decoration:none;"><i class="fas fa-external-link-alt"></i> Full Dashboard</a>
                </div>
            </div>

            <!-- Fleet Stats -->
            <div class="mc-stats" id="fleet-v2-stats">
                <div class="mc-stat"><div class="mc-stat-val" style="color:var(--mc-blue);" id="fv2-total">—</div><div class="mc-stat-label">Total Agents</div><div class="mc-stat-icon"><i class="fas fa-robot"></i></div></div>
                <div class="mc-stat"><div class="mc-stat-val" style="color:var(--mc-green);" id="fv2-available">—</div><div class="mc-stat-label">Available</div><div class="mc-stat-icon"><i class="fas fa-check-circle"></i></div></div>
                <div class="mc-stat"><div class="mc-stat-val" style="color:var(--mc-amber);" id="fv2-busy">—</div><div class="mc-stat-label">Busy</div><div class="mc-stat-icon"><i class="fas fa-spinner"></i></div></div>
                <div class="mc-stat"><div class="mc-stat-val" style="color:var(--mc-purple);" id="fv2-domains">—</div><div class="mc-stat-label">Domains</div><div class="mc-stat-icon"><i class="fas fa-layer-group"></i></div></div>
                <div class="mc-stat"><div class="mc-stat-val" style="color:var(--mc-cyan);" id="fv2-success">—</div><div class="mc-stat-label">Avg Success Rate</div><div class="mc-stat-icon"><i class="fas fa-chart-line"></i></div></div>
                <div class="mc-stat"><div class="mc-stat-val" style="color:var(--mc-gold);" id="fv2-tasks">—</div><div class="mc-stat-label">Tasks Completed</div><div class="mc-stat-icon"><i class="fas fa-tasks"></i></div></div>
            </div>

            <!-- Hierarchy -->
            <div class="mc-fleet-hier" id="fleet-v2-hier">
                <div class="mc-hier-node" style="border-color:var(--mc-gold);">
                    <div class="hier-val" style="color:var(--mc-gold);">1</div>
                    <div class="hier-label">Commander</div>
                </div>
                <div class="mc-hier-arrow"><i class="fas fa-chevron-right"></i></div>
                <div class="mc-hier-node" style="border-color:var(--mc-purple);">
                    <div class="hier-val" style="color:var(--mc-purple);" id="fv2-directors">10</div>
                    <div class="hier-label">Directors</div>
                </div>
                <div class="mc-hier-arrow"><i class="fas fa-chevron-right"></i></div>
                <div class="mc-hier-node" style="border-color:var(--mc-cyan);">
                    <div class="hier-val" style="color:var(--mc-cyan);" id="fv2-specialists">4,989</div>
                    <div class="hier-label">Specialists</div>
                </div>
            </div>

            <!-- Domain Breakdown -->
            <div class="mc-card" style="margin-bottom:16px;">
                <div class="mc-card-title"><i class="fas fa-th-large"></i> Domain Breakdown</div>
                <div class="mc-fleet-domains" id="fleet-v2-domains"><div style="text-align:center;color:var(--mc-muted);padding:20px;">Loading domains...</div></div>
            </div>

            <!-- Search & Table -->
            <div class="mc-card">
                <div class="mc-card-title"><i class="fas fa-search"></i> Agent Registry</div>
                <div class="mc-fleet-search">
                    <input type="text" id="fleet-v2-search" placeholder="Search agents..." style="flex:1;min-width:200px;">
                    <select id="fleet-v2-domain-filter">
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
                    <select id="fleet-v2-role-filter">
                        <option value="">All Roles</option>
                        <option value="commander">Commander</option>
                        <option value="director">Director</option>
                        <option value="specialist">Specialist</option>
                    </select>
                    <button class="mc-btn mc-btn-info" onclick="searchFleetV2()"><i class="fas fa-search"></i> Search</button>
                </div>
                <div id="fleet-v2-table"><div style="text-align:center;color:var(--mc-muted);padding:20px;">Use filters above or click a domain card to view agents.</div></div>
            </div>
        </div>

        <!-- ═══ FLEET OPS PANEL ═══ -->
        <div class="mc-panel" id="panel-fleetops">
            <h2 style="font-size:20px;font-weight:800;margin-bottom:20px;"><i class="fas fa-tower-broadcast" style="color:var(--mc-cyan);margin-right:8px;"></i>Fleet Operations Center</h2>

            <!-- Metrics -->
            <div class="mc-card" style="margin-bottom:16px;">
                <div class="mc-card-title"><i class="fas fa-chart-bar"></i> Fleet Performance Metrics</div>
                <div class="mc-metric-grid" id="fleetops-metrics">
                    <div class="mc-metric-card"><div class="metric-val" style="color:var(--mc-green);" id="fops-avg-success">—</div><div class="metric-label">Avg Success Rate</div></div>
                    <div class="mc-metric-card"><div class="metric-val" style="color:var(--mc-blue);" id="fops-total-tasks">—</div><div class="metric-label">Total Tasks Done</div></div>
                    <div class="mc-metric-card"><div class="metric-val" style="color:var(--mc-amber);" id="fops-avg-time">—</div><div class="metric-label">Avg Task Time</div></div>
                    <div class="mc-metric-card"><div class="metric-val" style="color:var(--mc-red);" id="fops-error-rate">—</div><div class="metric-label">Error Rate</div></div>
                </div>
            </div>

            <div class="mc-grid-2">
                <!-- Task Router -->
                <div class="mc-card">
                    <div class="mc-card-title"><i class="fas fa-route"></i> Route Task to Best Agent</div>
                    <div class="mc-compose">
                        <select id="fops-route-domain">
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
                        <select id="fops-route-priority">
                            <option value="normal">Normal Priority</option>
                            <option value="high">High Priority</option>
                            <option value="critical">Critical</option>
                        </select>
                        <textarea id="fops-route-desc" placeholder="Task description..."></textarea>
                    </div>
                    <div class="mc-compose-actions">
                        <button class="mc-btn mc-btn-primary" onclick="routeFleetTask()"><i class="fas fa-paper-plane"></i> Route Task</button>
                    </div>
                    <div id="fops-route-result" style="margin-top:12px;"></div>
                </div>

                <!-- Broadcast Message -->
                <div class="mc-card">
                    <div class="mc-card-title"><i class="fas fa-bullhorn"></i> Fleet Broadcast</div>
                    <div class="mc-compose">
                        <select id="fops-broadcast-domain">
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
                        <select id="fops-broadcast-type">
                            <option value="broadcast">Broadcast</option>
                            <option value="alert">Alert</option>
                            <option value="coordination">Coordination</option>
                            <option value="status">Status Request</option>
                        </select>
                        <textarea id="fops-broadcast-msg" placeholder="Message to fleet..."></textarea>
                    </div>
                    <div class="mc-compose-actions">
                        <button class="mc-btn mc-btn-info" onclick="sendFleetBroadcast()"><i class="fas fa-broadcast-tower"></i> Send Broadcast</button>
                    </div>
                    <div id="fops-broadcast-result" style="margin-top:12px;"></div>
                </div>
            </div>

            <!-- Message Inbox -->
            <div class="mc-card" style="margin-top:16px;">
                <div class="mc-card-title"><i class="fas fa-inbox"></i> Fleet Message Inbox</div>
                <div style="display:flex;gap:8px;margin-bottom:12px;">
                    <input type="text" id="fops-inbox-agent" placeholder="Agent ID (e.g. alfred)" style="background:var(--mc-card);border:1px solid var(--mc-border);color:var(--mc-text);padding:8px 14px;border-radius:8px;font-size:12px;flex:1;max-width:300px;">
                    <button class="mc-btn mc-btn-info" onclick="loadFleetInbox()"><i class="fas fa-download"></i> Load Inbox</button>
                </div>
                <div id="fops-inbox"><div style="text-align:center;color:var(--mc-muted);padding:20px;">Enter an agent ID to view messages.</div></div>
            </div>
        </div>

        <!-- ═══ ECOSYSTEM PANEL ═══ -->
        <div class="mc-panel" id="panel-optimizer">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;gap:8px;flex-wrap:wrap;">
                <h2 style="font-size:20px;font-weight:800;"><i class="fas fa-gauge-high" style="color:var(--mc-amber);margin-right:8px;"></i>Optimizer Command</h2>
                <div style="display:flex;gap:8px;">
                    <button class="mc-header-btn" onclick="loadOptimizerOverview()"><i class="fas fa-sync"></i> Refresh</button>
                    <button class="mc-header-btn" onclick="loadOptimizerFindings('open')"><i class="fas fa-list-check"></i> Open Findings</button>
                </div>
            </div>

            <div class="mc-stats" style="margin-bottom:16px;">
                <div class="mc-stat"><div class="mc-stat-val" style="color:var(--mc-red);" id="opt-critical">0</div><div class="mc-stat-label">Critical Findings</div><div class="mc-stat-icon"><i class="fas fa-triangle-exclamation"></i></div></div>
                <div class="mc-stat"><div class="mc-stat-val" style="color:var(--mc-amber);" id="opt-open">0</div><div class="mc-stat-label">Open Findings</div><div class="mc-stat-icon"><i class="fas fa-folder-open"></i></div></div>
                <div class="mc-stat"><div class="mc-stat-val" style="color:var(--mc-cyan);" id="opt-threads">0</div><div class="mc-stat-label">MySQL Threads</div><div class="mc-stat-icon"><i class="fas fa-database"></i></div></div>
                <div class="mc-stat"><div class="mc-stat-val" style="color:var(--mc-green);" id="opt-agents">0</div><div class="mc-stat-label">Optimizer Agents</div><div class="mc-stat-icon"><i class="fas fa-robot"></i></div></div>
            </div>

            <div class="mc-grid-2">
                <div class="mc-card">
                    <div class="mc-card-title"><i class="fas fa-heart-pulse"></i> Latest Telemetry</div>
                    <div id="optimizer-latest" style="font-size:13px;color:var(--mc-muted);">Loading optimizer telemetry...</div>
                </div>
                <div class="mc-card">
                    <div class="mc-card-title"><i class="fas fa-people-group"></i> Cohort Assignment</div>
                    <div id="optimizer-cohorts" style="font-size:13px;color:var(--mc-muted);">Loading optimizer cohorts...</div>
                </div>
            </div>

            <div class="mc-card" style="margin-top:12px;">
                <div class="mc-card-title"><i class="fas fa-screwdriver-wrench"></i> Open Findings</div>
                <div id="optimizer-findings"><div style="text-align:center;color:var(--mc-muted);padding:24px;">Loading findings...</div></div>
            </div>
        </div>

        <div class="mc-panel" id="panel-ecosystem">
            <h2 style="font-size:20px;font-weight:800;margin-bottom:20px;"><i class="fas fa-globe" style="color:var(--mc-cyan);margin-right:8px;"></i>Ecosystem Health</h2>
            <div class="mc-grid-3" id="ecosystem-grid">
                <?php
                $ecosystemItems = [
                    ['Veil Browser', 'fas fa-globe', '--mc-cyan', '/alfred-browser', 'All 6 platforms deployed'],
                    ['Alfred AI', 'fas fa-brain', '--mc-blue', '/alfred', '13,000+ tools active'],
                    ['GoCodeMe IDE', 'fas fa-code', '--mc-green', '/editor/', 'Web IDE v2.0'],
                    ['Mining System', 'fas fa-microchip', '--mc-amber', '/mine', '250M GSM pool, 80/20 split'],
                    ['Games', 'fas fa-gamepad', '--mc-red', '/games', 'Chess, Pool, Backgammon + VR'],
                    ['Pulse Social', 'fas fa-heart', '--mc-purple', '/pulse', 'Feeds, messaging, groups'],
                    ['Marketplace', 'fas fa-store', '--mc-cyan', '/marketplace', 'Agent & tool marketplace'],
                    ['Veil Protocol', 'fas fa-shield-alt', '--mc-green', '/veil/', 'AES-256 + Kyber-1024'],
                    ['Voice Portal', 'fas fa-phone', '--mc-amber', '/voice', '1-833-GOSITEME active'],
                    ['VR Worlds', 'fas fa-vr-cardboard', '--mc-purple', '/vr/', '14 worlds accessible'],
                    ['Search Engine', 'fas fa-search', '--mc-blue', '/search', 'Crawler + index active'],
                    ['Crypto/DeFi', 'fas fa-coins', '--mc-gold', '/invest', 'GSM token on Solana'],
                    ['Developer Portal', 'fas fa-terminal', '--mc-green', '/developer-portal', 'API docs, SDKs, webhooks'],
                    ['Robotics/AgentOS', 'fas fa-robot', '--mc-red', '/api/agentos/', '24 API modules'],
                    ['Chrome Extension', 'fab fa-chrome', '--mc-blue', '/downloads/alfred-chrome-extension/', 'Browser sovereignty addon'],
                ];
                foreach ($ecosystemItems as $item): ?>
                    <a href="<?= $item[3] ?>" class="mc-quick-item" style="flex-direction:row;gap:12px;padding:14px 16px;text-align:left;">
                        <i class="<?= $item[1] ?>" style="color:var(<?= $item[2] ?>);font-size:18px;flex-shrink:0;"></i>
                        <div>
                            <div style="font-size:13px;font-weight:600;color:var(--mc-text);"><?= $item[0] ?></div>
                            <div style="font-size:11px;color:var(--mc-muted);"><?= $item[4] ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ═══ MONITORING FLEET PANEL ═══ -->
        <div class="mc-panel" id="panel-monitoring">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                <h2 style="font-size:20px;font-weight:800;"><i class="fas fa-satellite" style="color:var(--mc-cyan);margin-right:8px;"></i>Monitoring Fleet — 50M+ Agents</h2>
                <div style="display:flex;gap:8px;">
                    <button class="mc-header-btn" onclick="registerFleet()"><i class="fas fa-plus-circle"></i> Register Fleet</button>
                    <button class="mc-header-btn" onclick="runAllChecks()"><i class="fas fa-play"></i> Run All Checks</button>
                    <select id="monitor-filter-division" onchange="loadFleetStatus()" style="background:var(--mc-card);border:1px solid var(--mc-border);color:var(--mc-text);padding:6px 12px;border-radius:8px;font-size:12px;">
                        <option value="">All Divisions</option>
                        <option value="uptime">Uptime (10)</option>
                        <option value="services">Services (10)</option>
                        <option value="security">Security (15)</option>
                        <option value="performance">Performance (10)</option>
                        <option value="seo">SEO & Content (10)</option>
                        <option value="crawler">Crawler (10)</option>
                        <option value="ecosystem">Ecosystem (10)</option>
                        <option value="ux">User Experience (10)</option>
                        <option value="compliance">Compliance (5)</option>
                        <option value="innovation">Innovation (10)</option>
                    </select>
                </div>
            </div>

            <!-- Fleet Summary -->
            <div class="mc-stats" id="monitor-summary" style="margin-bottom:20px;">
                <div class="mc-stat" style="--accent:var(--mc-blue);">
                    <div class="mc-stat-val" style="color:var(--mc-blue);" id="mon-total">100</div>
                    <div class="mc-stat-label">Total Agents</div>
                </div>
                <div class="mc-stat">
                    <div class="mc-stat-val" style="color:var(--mc-green);" id="mon-healthy">—</div>
                    <div class="mc-stat-label">Healthy</div>
                </div>
                <div class="mc-stat">
                    <div class="mc-stat-val" style="color:var(--mc-amber);" id="mon-degraded">—</div>
                    <div class="mc-stat-label">Degraded</div>
                </div>
                <div class="mc-stat">
                    <div class="mc-stat-val" style="color:var(--mc-red);" id="mon-critical">—</div>
                    <div class="mc-stat-label">Critical</div>
                </div>
                <div class="mc-stat">
                    <div class="mc-stat-val" style="color:var(--mc-muted);" id="mon-unknown">—</div>
                    <div class="mc-stat-label">Unknown</div>
                </div>
            </div>

            <!-- Division Breakdown -->
            <div class="mc-card" style="margin-bottom:12px;">
                <div class="mc-card-title"><i class="fas fa-layer-group"></i> Division Breakdown</div>
                <div id="division-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px;"></div>
            </div>

            <!-- Agent List -->
            <div class="mc-card">
                <div class="mc-card-title"><i class="fas fa-list"></i> Agent Status</div>
                <div id="monitor-fleet-list">
                    <div style="text-align:center;color:var(--mc-muted);padding:40px;">Click "Register Fleet" to initialize all 100 monitoring agents, then "Run All Checks" to execute.</div>
                </div>
            </div>
        </div>

        <!-- ═══ PERMISSIONS PANEL ═══ -->
        <div class="mc-panel" id="panel-permissions">
            <h2 style="font-size:20px;font-weight:800;margin-bottom:20px;"><i class="fas fa-key" style="color:var(--mc-amber);margin-right:8px;"></i>Agent Permissions</h2>
            <p style="color:var(--mc-muted);font-size:14px;margin-bottom:20px;">Control what agents can do autonomously vs. what requires your approval. Higher trust = more autonomy.</p>
            <div class="mc-card">
                <div class="mc-card-title"><i class="fas fa-sliders-h"></i> Permission Matrix</div>
                <table class="mc-table">
                    <thead>
                        <tr>
                            <th>Permission</th>
                            <th>Description</th>
                            <th>Auto-Approve Limit</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>file.read</td><td>Read files in workspace</td><td>Always</td><td><span class="mc-badge green">Granted</span></td></tr>
                        <tr><td>file.write</td><td>Create/edit files</td><td>Non-critical only</td><td><span class="mc-badge amber">Requires Approval</span></td></tr>
                        <tr><td>file.delete</td><td>Delete files</td><td>Never</td><td><span class="mc-badge red">Owner Only</span></td></tr>
                        <tr><td>deploy</td><td>Deploy changes to production</td><td>Under $50</td><td><span class="mc-badge amber">Requires Approval</span></td></tr>
                        <tr><td>api.create</td><td>Create new API endpoints</td><td>Non-public</td><td><span class="mc-badge amber">Requires Approval</span></td></tr>
                        <tr><td>spend</td><td>Spend money/resources</td><td>Under $50</td><td><span class="mc-badge red">Owner Only</span></td></tr>
                        <tr><td>agent.create</td><td>Spawn new agents</td><td>Under 5 agents</td><td><span class="mc-badge amber">Requires Approval</span></td></tr>
                        <tr><td>security.modify</td><td>Change security settings</td><td>Never</td><td><span class="mc-badge red">Owner Only</span></td></tr>
                        <tr><td>report</td><td>File reports & escalations</td><td>Always</td><td><span class="mc-badge green">Granted</span></td></tr>
                        <tr><td>monitor</td><td>Monitor systems & services</td><td>Always</td><td><span class="mc-badge green">Granted</span></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/mission-control-engine.js"></script>
<script src="/assets/js/mission-control-v2.js"></script>

</body>
</html>
