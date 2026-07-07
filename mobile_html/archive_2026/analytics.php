<?php
/**
 * Analytics Dashboard — Alfred AI
 * Comprehensive analytics and usage insights
 */
$page_title       = 'Analytics - Alfred AI Dashboard';
$page_description = 'Analytics and insights for your Alfred AI usage';
$page_canonical   = 'https://gositeme.com/analytics';
$page_robots      = 'noindex, nofollow';
require_once __DIR__ . '/includes/auth-gate.inc.php';
require_once __DIR__ . '/includes/site-header.inc.php';
?>

<style>
/* ─── Analytics Dashboard Styles ─── */
:root {
    --al-bg: #0a0a14;
    --al-surface: #12121e;
    --al-surface-2: #1a1a2e;
    --al-surface-3: #222240;
    --al-accent: #6c5ce7;
    --al-accent-light: #a29bfe;
    --al-blue: #0984e3;
    --al-green: #00b894;
    --al-yellow: #fdcb6e;
    --al-red: #e17055;
    --al-cyan: #00cec9;
    --al-text: #e0e0e0;
    --al-text-muted: #8a8ab0;
    --al-border: rgba(255,255,255,0.06);
    --al-radius: 14px;
}

.analytics-wrap {
    min-height: 100vh;
    background: linear-gradient(135deg, var(--al-bg) 0%, var(--al-surface-2) 50%, #0f0f1a 100%);
    background-attachment: fixed;
    padding: 20px 0 80px 0;
    color: var(--al-text);
    font-family: 'Inter', -apple-system, sans-serif;
}

.analytics-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 32px 24px;
}

/* Header */
.analytics-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 32px;
}

.analytics-header h1 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 2rem;
    font-weight: 700;
    background: linear-gradient(135deg, var(--al-accent-light), var(--al-cyan));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.analytics-header .subtitle {
    font-size: 0.95rem;
    color: var(--al-text-muted);
    margin-top: 4px;
}

.analytics-header .back-link {
    color: var(--al-accent-light);
    text-decoration: none;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 6px;
}

/* Period Selector */
.period-selector {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.period-btn {
    padding: 8px 18px;
    border-radius: 8px;
    border: 1px solid var(--al-border);
    background: var(--al-surface);
    color: var(--al-text-muted);
    cursor: pointer;
    font-size: 0.85rem;
    font-weight: 500;
    transition: all 0.2s;
}

.period-btn:hover {
    border-color: var(--al-accent);
    color: var(--al-text);
}

.period-btn.active {
    background: var(--al-accent);
    color: #fff;
    border-color: var(--al-accent);
}

/* Overview Cards */
.overview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 32px;
}

.overview-card {
    background: var(--al-surface);
    border: 1px solid var(--al-border);
    border-radius: var(--al-radius);
    padding: 24px;
    transition: transform 0.2s, border-color 0.2s;
}

.overview-card:hover {
    transform: translateY(-2px);
    border-color: var(--al-accent);
}

.overview-card .card-icon {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    margin-bottom: 14px;
}

.overview-card .card-icon.purple { background: rgba(108,92,231,0.15); color: var(--al-accent); }
.overview-card .card-icon.blue   { background: rgba(9,132,227,0.15);  color: var(--al-blue); }
.overview-card .card-icon.green  { background: rgba(0,184,148,0.15);  color: var(--al-green); }
.overview-card .card-icon.cyan   { background: rgba(0,206,201,0.15);  color: var(--al-cyan); }
.overview-card .card-icon.yellow { background: rgba(253,203,110,0.15); color: var(--al-yellow); }
.overview-card .card-icon.red    { background: rgba(225,112,85,0.15);  color: var(--al-red); }

.overview-card .card-label {
    font-size: 0.8rem;
    color: var(--al-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
}

.overview-card .card-value {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.7rem;
    font-weight: 700;
    line-height: 1.1;
}

.overview-card .card-change {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.78rem;
    font-weight: 600;
    margin-top: 8px;
    padding: 3px 8px;
    border-radius: 6px;
}

.card-change.up   { background: rgba(0,184,148,0.12); color: var(--al-green); }
.card-change.down { background: rgba(225,112,85,0.12); color: var(--al-red); }
.card-change.flat { background: rgba(138,138,176,0.12); color: var(--al-text-muted); }

/* Chart Sections */
.charts-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-bottom: 32px;
}

.chart-card {
    background: var(--al-surface);
    border: 1px solid var(--al-border);
    border-radius: var(--al-radius);
    padding: 24px;
}

.chart-card h3 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 16px;
    color: var(--al-text);
}

.chart-card canvas {
    max-height: 300px;
}

/* Agent Performance Table */
.section-card {
    background: var(--al-surface);
    border: 1px solid var(--al-border);
    border-radius: var(--al-radius);
    padding: 24px;
    margin-bottom: 24px;
}

.section-card h2 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-card h2 i {
    color: var(--al-accent);
}

.agent-table {
    width: 100%;
    border-collapse: collapse;
}

.agent-table th {
    text-align: left;
    padding: 12px 14px;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--al-text-muted);
    border-bottom: 1px solid var(--al-border);
}

.agent-table td {
    padding: 14px;
    border-bottom: 1px solid var(--al-border);
    font-size: 0.9rem;
}

.agent-table tr:last-child td { border-bottom: none; }

.agent-table tr:hover td {
    background: rgba(108,92,231,0.04);
}

.status-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: capitalize;
}

.status-badge.active  { background: rgba(0,184,148,0.15); color: var(--al-green); }
.status-badge.paused  { background: rgba(253,203,110,0.15); color: var(--al-yellow); }
.status-badge.error   { background: rgba(225,112,85,0.15); color: var(--al-red); }
.status-badge.draft   { background: rgba(138,138,176,0.15); color: var(--al-text-muted); }

/* Cost / Limits */
.cost-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 32px;
}

.progress-bar-wrap {
    margin-bottom: 14px;
}

.progress-label {
    display: flex;
    justify-content: space-between;
    font-size: 0.82rem;
    margin-bottom: 6px;
}

.progress-label .limit-name { color: var(--al-text); }
.progress-label .limit-val  { color: var(--al-text-muted); }

.progress-track {
    height: 8px;
    background: var(--al-surface-3);
    border-radius: 6px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    border-radius: 6px;
    transition: width 0.6s ease;
    background: var(--al-accent);
}

.progress-fill.warn   { background: var(--al-yellow); }
.progress-fill.danger { background: var(--al-red); }

.projected-cost {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 2rem;
    font-weight: 700;
    color: var(--al-accent-light);
}

.projected-label {
    font-size: 0.85rem;
    color: var(--al-text-muted);
    margin-top: 4px;
}

.overage-alert {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    border-radius: 10px;
    background: rgba(225,112,85,0.08);
    border: 1px solid rgba(225,112,85,0.2);
    color: var(--al-red);
    font-size: 0.85rem;
    margin-top: 12px;
}

/* Activity Log */
.activity-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.activity-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid var(--al-border);
}

.activity-item:last-child { border-bottom: none; }

.activity-icon {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    flex-shrink: 0;
}

.activity-icon.api_call      { background: rgba(9,132,227,0.15); color: var(--al-blue); }
.activity-icon.tool_call     { background: rgba(108,92,231,0.15); color: var(--al-accent); }
.activity-icon.voice_call    { background: rgba(0,206,201,0.15); color: var(--al-cyan); }
.activity-icon.conversation  { background: rgba(0,184,148,0.15); color: var(--al-green); }
.activity-icon.chat          { background: rgba(0,184,148,0.15); color: var(--al-green); }
.activity-icon.error         { background: rgba(225,112,85,0.15); color: var(--al-red); }
.activity-icon.default       { background: rgba(138,138,176,0.12); color: var(--al-text-muted); }

.activity-content {
    flex: 1;
    min-width: 0;
}

.activity-title {
    font-size: 0.88rem;
    font-weight: 500;
    color: var(--al-text);
}

.activity-meta {
    font-size: 0.78rem;
    color: var(--al-text-muted);
    margin-top: 3px;
}

.activity-filter-bar {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 16px;
}

.filter-chip {
    padding: 5px 12px;
    border-radius: 6px;
    border: 1px solid var(--al-border);
    background: transparent;
    color: var(--al-text-muted);
    cursor: pointer;
    font-size: 0.78rem;
    transition: all 0.2s;
}

.filter-chip:hover,
.filter-chip.active {
    background: var(--al-accent);
    color: #fff;
    border-color: var(--al-accent);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 48px 24px;
    color: var(--al-text-muted);
}

.empty-state i {
    font-size: 2.5rem;
    margin-bottom: 16px;
    opacity: 0.4;
}

.empty-state p {
    font-size: 0.95rem;
}

/* Loading skeleton */
.skeleton {
    background: linear-gradient(90deg, var(--al-surface-2) 25%, var(--al-surface-3) 50%, var(--al-surface-2) 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
    border-radius: 6px;
}

@keyframes shimmer {
    0%   { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

.skeleton-value { width: 80px; height: 30px; margin-bottom: 8px; }
.skeleton-line  { width: 100%; height: 14px; margin-bottom: 6px; }

/* Responsive */
@media (max-width: 1024px) {
    .charts-grid { grid-template-columns: 1fr; }
    .cost-grid   { grid-template-columns: 1fr; }
}

@media (max-width: 768px) {
    .analytics-container { padding: 20px 16px; }
    .analytics-header h1 { font-size: 1.5rem; }
    .overview-grid { grid-template-columns: repeat(2, 1fr); }
    .analytics-header { flex-direction: column; align-items: flex-start; }
}

@media (max-width: 480px) {
    .overview-grid { grid-template-columns: 1fr; }
}

/* ─── v2.0 Enhancements ─── */
.an-toast{position:fixed;bottom:1.5rem;right:1.5rem;padding:.8rem 1.5rem;border-radius:10px;font-size:.85rem;font-weight:600;z-index:9999;transform:translateY(20px);opacity:0;transition:all .3s;backdrop-filter:blur(12px);border:1px solid var(--al-border)}
.an-toast.show{transform:translateY(0);opacity:1}
.an-toast-success{background:rgba(0,184,148,.15);color:var(--al-green);border-color:rgba(0,184,148,.3)}
.an-toast-info{background:rgba(108,92,231,.15);color:var(--al-accent-light);border-color:rgba(108,92,231,.3)}
.an-toast-error{background:rgba(225,112,85,.15);color:var(--al-red);border-color:rgba(225,112,85,.3)}

.export-toolbar{display:flex;gap:.5rem;flex-wrap:wrap;align-items:center}
.export-btn{background:var(--al-surface);border:1px solid var(--al-border);color:var(--al-text-muted);padding:6px 14px;border-radius:8px;cursor:pointer;font-size:.78rem;transition:all .2s;display:inline-flex;align-items:center;gap:5px}
.export-btn:hover{border-color:var(--al-accent);color:var(--al-accent-light);background:rgba(108,92,231,.08)}
.export-btn.active{background:var(--al-accent);color:#fff;border-color:var(--al-accent)}

.anomaly-alert{display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:10px;font-size:.83rem;margin-bottom:8px}
.anomaly-warn{background:rgba(253,203,110,.08);border:1px solid rgba(253,203,110,.2);color:var(--al-yellow)}
.anomaly-danger{background:rgba(225,112,85,.08);border:1px solid rgba(225,112,85,.2);color:var(--al-red)}

.comparison-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-top:12px}
.comparison-item{background:var(--al-surface-2);border:1px solid var(--al-border);border-radius:10px;padding:14px;text-align:center}
.comparison-label{font-size:.75rem;text-transform:uppercase;letter-spacing:.5px;color:var(--al-text-muted);margin-bottom:6px}
.comparison-values{display:flex;align-items:center;justify-content:center;gap:8px}
.comp-current{font-weight:700;font-size:1.1rem;color:var(--al-text)}
.comp-vs{color:var(--al-text-muted);font-size:.7rem}
.comp-previous{color:var(--al-text-muted);font-size:.9rem}
</style>

<div class="analytics-wrap">
    <div class="analytics-container">

        <!-- Header -->
        <div class="analytics-header">
            <div>
                <a href="/dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                <h1><i class="fas fa-chart-line" style="margin-right:8px;"></i> Analytics <span style="font-size:.5em;color:var(--al-accent);font-weight:600;vertical-align:super">v2.0</span></h1>
                <div class="subtitle">Usage insights for <?php echo htmlspecialchars($clientName); ?></div>
            </div>
            <div>
                <div class="export-toolbar" style="margin-bottom:8px">
                    <button class="export-btn" onclick="window.Analytics&&Analytics.exportOverviewCSV()"><i class="fas fa-download"></i> Overview CSV</button>
                    <button class="export-btn" onclick="window.Analytics&&Analytics.exportAgentsCSV()"><i class="fas fa-robot"></i> Agents CSV</button>
                    <button class="export-btn" onclick="window.Analytics&&Analytics.exportActivityCSV()"><i class="fas fa-stream"></i> Activity CSV</button>
                    <button class="export-btn" onclick="window.Analytics&&Analytics.exportJSON()"><i class="fas fa-code"></i> JSON</button>
                    <button class="export-btn" id="comparisonToggle" onclick="window.Analytics&&Analytics.toggleComparison()"><i class="fas fa-columns"></i> Compare Periods</button>
                </div>
                <div class="period-selector">
                <button class="period-btn" data-period="today">Today</button>
                <button class="period-btn" data-period="7d">7 Days</button>
                <button class="period-btn active" data-period="30d">30 Days</button>
                <button class="period-btn" data-period="90d">90 Days</button>
            </div>
            </div>
        </div>

        <!-- v2.0 Comparison Panel -->
        <div id="comparisonPanel" style="display:none;margin-bottom:24px;">
            <div class="section-card">
                <h2><i class="fas fa-columns"></i> Period Comparison</h2>
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;">
                    <button class="export-btn" onclick="window.Analytics&&Analytics.runComparison('today')">vs Today</button>
                    <button class="export-btn" onclick="window.Analytics&&Analytics.runComparison('7d')">vs 7 Days</button>
                    <button class="export-btn" onclick="window.Analytics&&Analytics.runComparison('30d')">vs 30 Days</button>
                    <button class="export-btn" onclick="window.Analytics&&Analytics.runComparison('90d')">vs 90 Days</button>
                </div>
                <div id="comparisonResults" style="color:var(--al-text-muted);font-size:.85rem;">Select a period to compare against the current view</div>
            </div>
        </div>

        <!-- v2.0 Anomaly Alerts -->
        <div id="anomalyAlerts" style="margin-bottom:16px;"></div>

        <!-- Section 1: Overview Cards -->
        <div class="overview-grid" id="overviewGrid">
            <div class="overview-card">
                <div class="card-icon purple"><i class="fas fa-server"></i></div>
                <div class="card-label">Total API Calls</div>
                <div class="card-value" id="ov-api-calls"><div class="skeleton skeleton-value"></div></div>
                <div class="card-change flat" id="ov-api-calls-change">—</div>
            </div>
            <div class="overview-card">
                <div class="card-icon cyan"><i class="fas fa-microphone-alt"></i></div>
                <div class="card-label">Voice Minutes</div>
                <div class="card-value" id="ov-voice-min"><div class="skeleton skeleton-value"></div></div>
                <div class="card-change flat" id="ov-voice-min-change">—</div>
            </div>
            <div class="overview-card">
                <div class="card-icon green"><i class="fas fa-robot"></i></div>
                <div class="card-label">Active Agents</div>
                <div class="card-value" id="ov-agents"><div class="skeleton skeleton-value"></div></div>
                <div class="card-change flat" id="ov-agents-change">—</div>
            </div>
            <div class="overview-card">
                <div class="card-icon blue"><i class="fas fa-wrench"></i></div>
                <div class="card-label">Tools Executed</div>
                <div class="card-value" id="ov-tools"><div class="skeleton skeleton-value"></div></div>
                <div class="card-change flat" id="ov-tools-change">—</div>
            </div>
            <div class="overview-card">
                <div class="card-icon yellow"><i class="fas fa-signal"></i></div>
                <div class="card-label">Fleet Uptime</div>
                <div class="card-value" id="ov-uptime"><div class="skeleton skeleton-value"></div></div>
                <div class="card-change flat" id="ov-uptime-change">—</div>
            </div>
            <div class="overview-card">
                <div class="card-icon red"><i class="fas fa-database"></i></div>
                <div class="card-label">Storage Used</div>
                <div class="card-value" id="ov-storage"><div class="skeleton skeleton-value"></div></div>
                <div class="card-change flat" id="ov-storage-change">—</div>
            </div>
        </div>

        <!-- Section 2: Usage Charts -->
        <div class="charts-grid">
            <div class="chart-card">
                <h3><i class="fas fa-chart-area" style="color:var(--al-blue);margin-right:6px;"></i> API Calls Over Time</h3>
                <canvas id="chartApiCalls"></canvas>
            </div>
            <div class="chart-card">
                <h3><i class="fas fa-chart-bar" style="color:var(--al-cyan);margin-right:6px;"></i> Voice Minutes Over Time</h3>
                <canvas id="chartVoice"></canvas>
            </div>
            <div class="chart-card">
                <h3><i class="fas fa-ranking-star" style="color:var(--al-accent);margin-right:6px;"></i> Top 10 Most Used Tools</h3>
                <canvas id="chartTopTools"></canvas>
            </div>
            <div class="chart-card">
                <h3><i class="fas fa-clock" style="color:var(--al-green);margin-right:6px;"></i> Usage by Hour of Day</h3>
                <canvas id="chartHourly"></canvas>
            </div>
        </div>

        <!-- Section 3: Agent Performance -->
        <div class="section-card">
            <h2><i class="fas fa-robot"></i> Agent Performance</h2>
            <div style="overflow-x:auto;">
                <table class="agent-table" id="agentTable">
                    <thead>
                        <tr>
                            <th>Agent Name</th>
                            <th>Conversations</th>
                            <th>Avg Response (ms)</th>
                            <th>Satisfaction</th>
                            <th>Errors</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="agentTableBody">
                        <tr><td colspan="6" class="empty-state"><i class="fas fa-spinner fa-spin"></i><p>Loading agents…</p></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 4: Cost Analysis -->
        <div class="cost-grid">
            <div class="section-card">
                <h2><i class="fas fa-chart-pie"></i> Cost Breakdown</h2>
                <canvas id="chartCost" style="max-height:280px;"></canvas>
            </div>
            <div class="section-card">
                <h2><i class="fas fa-gauge-high"></i> Usage vs Plan Limits</h2>
                <div id="planLimits">
                    <div class="empty-state"><i class="fas fa-spinner fa-spin"></i><p>Calculating…</p></div>
                </div>
                <div style="margin-top:24px;padding-top:16px;border-top:1px solid var(--al-border);">
                    <div class="projected-label">Projected Monthly Cost</div>
                    <div class="projected-cost" id="projectedCost">$—</div>
                </div>
                <div id="overageAlerts"></div>
            </div>
        </div>

        <!-- Section 5: Activity Log -->
        <div class="section-card">
            <h2><i class="fas fa-stream"></i> Recent Activity</h2>
            <div class="activity-filter-bar">
                <button class="filter-chip active" data-type="">All</button>
                <button class="filter-chip" data-type="api_call">API Calls</button>
                <button class="filter-chip" data-type="tool_call">Tools</button>
                <button class="filter-chip" data-type="voice_call">Voice</button>
                <button class="filter-chip" data-type="conversation">Conversations</button>
                <button class="filter-chip" data-type="error">Errors</button>
            </div>
            <ul class="activity-list" id="activityList">
                <li class="empty-state"><i class="fas fa-spinner fa-spin"></i><p>Loading activity…</p></li>
            </ul>
            <div style="text-align:center;margin-top:16px;">
                <button class="period-btn" id="loadMoreActivity" style="display:none;">Load More</button>
            </div>
        </div>

    </div>
</div>

<!-- Chart.js (local) -->
<script src="/assets/js/vendor/chart.umd.min.js"></script>
<!-- Analytics Engine v2.0 -->
<script src="/assets/js/analytics-engine.js"></script>
<script>
// Connect WebSocket with user ID
if (window.Analytics && window.Analytics.connectWS) {
    window.Analytics.connectWS(<?php echo json_encode($clientId); ?>);
}
</script>

<?php require_once 'includes/site-footer.inc.php'; ?>
