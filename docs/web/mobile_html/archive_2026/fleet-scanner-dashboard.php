<?php
/**
 * Fleet Bug Scanner Dashboard
 * ════════════════════════════
 * Real-time dashboard showing scan progress, bug reports,
 * and aggregated results from the 25,000-agent fleet.
 */
$pageTitle = "Fleet Bug Scanner — Command Dashboard";
$pageDescription = "Real-time dashboard for the agent fleet bug scanning system";

// Owner-only access
session_start();
if (empty($_SESSION['logged_in']) || ($_SESSION['client_id'] ?? 0) != 33) {
    header('Location: /login.php?return=/fleet-scanner-dashboard.php');
    exit;
}

include 'includes/site-header.inc.php';
?>

<style>
    .fsd-wrap { max-width: 1400px; margin: 0 auto; padding: 2rem 1.5rem; }
    .fsd-hero { text-align: center; margin-bottom: 2rem; }
    .fsd-hero h1 { font-size: 2rem; color: var(--alfred-primary, #00d4ff); margin: 0 0 0.5rem; }
    .fsd-hero p { color: #94a3b8; font-size: 1rem; }

    .fsd-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
    .fsd-stat { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 1.25rem; text-align: center; }
    .fsd-stat-num { font-size: 2rem; font-weight: 700; color: var(--alfred-primary, #00d4ff); }
    .fsd-stat-label { color: #94a3b8; font-size: 0.85rem; margin-top: 0.3rem; }
    .fsd-stat--critical .fsd-stat-num { color: #ef4444; }
    .fsd-stat--high .fsd-stat-num { color: #f59e0b; }
    .fsd-stat--medium .fsd-stat-num { color: #eab308; }
    .fsd-stat--low .fsd-stat-num { color: #22c55e; }

    .fsd-section { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; }
    .fsd-section h2 { font-size: 1.2rem; color: #e2e8f0; margin: 0 0 1rem; display: flex; align-items: center; gap: 0.5rem; }

    .fsd-progress { background: rgba(255,255,255,0.05); border-radius: 8px; height: 24px; overflow: hidden; margin-bottom: 1rem; }
    .fsd-progress-bar { height: 100%; background: linear-gradient(90deg, #00d4ff, #0099cc); border-radius: 8px; transition: width 0.5s ease; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 600; color: #fff; }

    .fsd-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
    @media (max-width: 768px) { .fsd-grid { grid-template-columns: 1fr; } }

    .fsd-table { width: 100%; border-collapse: collapse; }
    .fsd-table th, .fsd-table td { padding: 0.6rem 0.8rem; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.06); font-size: 0.85rem; }
    .fsd-table th { color: #94a3b8; font-weight: 600; }
    .fsd-table td { color: #e2e8f0; }

    .fsd-badge { display: inline-block; padding: 0.15rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
    .fsd-badge--critical { background: rgba(239,68,68,0.15); color: #ef4444; }
    .fsd-badge--high { background: rgba(245,158,11,0.15); color: #f59e0b; }
    .fsd-badge--medium { background: rgba(234,179,8,0.15); color: #eab308; }
    .fsd-badge--low { background: rgba(34,197,94,0.15); color: #22c55e; }
    .fsd-badge--info { background: rgba(59,130,246,0.15); color: #3b82f6; }

    .fsd-bug-list { max-height: 600px; overflow-y: auto; }
    .fsd-bug { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 8px; padding: 1rem; margin-bottom: 0.5rem; }
    .fsd-bug-title { font-weight: 600; color: #e2e8f0; font-size: 0.9rem; }
    .fsd-bug-file { color: #94a3b8; font-size: 0.8rem; font-family: monospace; }
    .fsd-bug-desc { color: #cbd5e1; font-size: 0.85rem; margin-top: 0.5rem; }
    .fsd-bug-fix { background: rgba(34,197,94,0.08); border-left: 3px solid #22c55e; padding: 0.5rem 0.75rem; margin-top: 0.5rem; font-size: 0.8rem; color: #94a3b8; }
    .fsd-bug-code { background: rgba(0,0,0,0.3); padding: 0.5rem; border-radius: 4px; font-family: monospace; font-size: 0.8rem; color: #e2e8f0; overflow-x: auto; margin-top: 0.5rem; white-space: pre-wrap; }

    .fsd-filters { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1rem; }
    .fsd-filter { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; padding: 0.4rem 0.8rem; color: #e2e8f0; font-size: 0.85rem; cursor: pointer; }
    .fsd-filter:hover, .fsd-filter.active { background: rgba(0,212,255,0.15); border-color: var(--alfred-primary, #00d4ff); color: var(--alfred-primary, #00d4ff); }

    .fsd-chart-bar { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.4rem; }
    .fsd-chart-label { width: 120px; font-size: 0.8rem; color: #94a3b8; text-align: right; }
    .fsd-chart-fill { height: 20px; border-radius: 4px; background: var(--alfred-primary, #00d4ff); transition: width 0.5s ease; min-width: 2px; }
    .fsd-chart-count { font-size: 0.8rem; color: #e2e8f0; min-width: 40px; }

    .fsd-refresh-btn { background: var(--alfred-primary, #00d4ff); color: #000; border: none; padding: 0.5rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600; }
    .fsd-refresh-btn:hover { opacity: 0.9; }

    .fsd-loading { text-align: center; padding: 2rem; color: #94a3b8; }
</style>

<div class="fsd-wrap">
    <div class="fsd-hero">
        <h1><i class="fas fa-radar"></i> Fleet Bug Scanner Dashboard</h1>
        <p>Real-time monitoring of the agent fleet codebase analysis</p>
        <button class="fsd-refresh-btn" id="refreshBtn"><i class="fas fa-sync-alt"></i> Refresh Data</button>
    </div>

    <!-- Stats Row -->
    <div class="fsd-stats" id="statsRow">
        <div class="fsd-stat"><div class="fsd-stat-num" id="totalTasks">—</div><div class="fsd-stat-label">Total Tasks</div></div>
        <div class="fsd-stat"><div class="fsd-stat-num" id="completedTasks">—</div><div class="fsd-stat-label">Completed</div></div>
        <div class="fsd-stat"><div class="fsd-stat-num" id="totalBugs">—</div><div class="fsd-stat-label">Bugs Found</div></div>
        <div class="fsd-stat fsd-stat--critical"><div class="fsd-stat-num" id="criticalBugs">—</div><div class="fsd-stat-label">Critical</div></div>
        <div class="fsd-stat fsd-stat--high"><div class="fsd-stat-num" id="highBugs">—</div><div class="fsd-stat-label">High</div></div>
        <div class="fsd-stat fsd-stat--medium"><div class="fsd-stat-num" id="mediumBugs">—</div><div class="fsd-stat-label">Medium</div></div>
    </div>

    <!-- Progress Bar -->
    <div class="fsd-section">
        <h2><i class="fas fa-tasks"></i> Scan Progress</h2>
        <div class="fsd-progress"><div class="fsd-progress-bar" id="progressBar" style="width:0%">0%</div></div>
        <div style="display:flex;justify-content:space-between;font-size:0.85rem;color:#94a3b8;">
            <span id="scanStatus">Loading...</span>
            <span id="scanId"></span>
        </div>
    </div>

    <div class="fsd-grid">
        <!-- Bugs by Type -->
        <div class="fsd-section">
            <h2><i class="fas fa-chart-bar"></i> Bugs by Scan Type</h2>
            <div id="byTypeChart"></div>
        </div>

        <!-- Bugs by Severity -->
        <div class="fsd-section">
            <h2><i class="fas fa-exclamation-triangle"></i> Bugs by Severity</h2>
            <div id="bySeverityChart"></div>
        </div>
    </div>

    <!-- Hot Files -->
    <div class="fsd-section">
        <h2><i class="fas fa-fire"></i> Most Affected Files</h2>
        <div style="overflow-x:auto;">
            <table class="fsd-table" id="hotFilesTable">
                <thead><tr><th>File</th><th>Total</th><th>Critical</th><th>High</th></tr></thead>
                <tbody id="hotFilesBody"></tbody>
            </table>
        </div>
    </div>

    <!-- Bug List -->
    <div class="fsd-section">
        <h2><i class="fas fa-bug"></i> Bug Reports</h2>
        <div class="fsd-filters" id="filters">
            <span class="fsd-filter active" data-severity="">All</span>
            <span class="fsd-filter" data-severity="critical">Critical</span>
            <span class="fsd-filter" data-severity="high">High</span>
            <span class="fsd-filter" data-severity="medium">Medium</span>
            <span class="fsd-filter" data-severity="low">Low</span>
        </div>
        <div class="fsd-bug-list" id="bugList">
            <div class="fsd-loading">Loading bugs...</div>
        </div>
        <div style="text-align:center;margin-top:1rem;">
            <button class="fsd-filter" id="loadMoreBtn" style="display:none;">Load More</button>
        </div>
    </div>
</div>

<script src="/assets/js/fleet-scanner-engine.js"></script>

<?php include 'includes/site-footer.inc.php'; ?>
