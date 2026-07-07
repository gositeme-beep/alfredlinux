<?php
require_once __DIR__ . '/includes/auth-gate.inc.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics — GoSiteMe</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <style>
        :root {
            --rp-bg: #0a0e17; --rp-card: #111827; --rp-border: #1f2937;
            --rp-blue: #3b82f6; --rp-green: #10b981; --rp-purple: #8b5cf6;
            --rp-amber: #f59e0b; --rp-red: #ef4444;
            --rp-text: #e5e7eb; --rp-muted: #9ca3af;
        }
        body.rp-page { background: var(--rp-bg); color: var(--rp-text); font-family: 'Inter', system-ui, sans-serif; margin: 0; }
        .rp-wrap { max-width: 1400px; margin: 0 auto; padding: 2rem 1.5rem; }
        .rp-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
        .rp-header h1 { font-size: 1.75rem; font-weight: 700; margin: 0; }
        .rp-header h1 span { color: var(--rp-blue); }
        .rp-btn { padding: .5rem 1rem; border-radius: .5rem; border: 1px solid var(--rp-border); background: var(--rp-card); color: var(--rp-text); cursor: pointer; font-size: .875rem; transition: all .2s; text-decoration: none; display: inline-flex; align-items: center; gap: .4rem; }
        .rp-btn:hover { border-color: var(--rp-blue); color: #fff; }
        .rp-btn-primary { background: var(--rp-blue); border-color: var(--rp-blue); color: #fff; font-weight: 600; }

        .rp-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .rp-kpi { background: var(--rp-card); border: 1px solid var(--rp-border); border-radius: .75rem; padding: 1.25rem; }
        .rp-kpi-label { font-size: .75rem; text-transform: uppercase; letter-spacing: .05em; color: var(--rp-muted); margin-bottom: .25rem; }
        .rp-kpi-value { font-size: 1.5rem; font-weight: 700; }
        .rp-kpi-sub { font-size: .75rem; color: var(--rp-muted); margin-top: .25rem; }

        .rp-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem; }
        .rp-section { background: var(--rp-card); border: 1px solid var(--rp-border); border-radius: .75rem; padding: 1.5rem; }
        .rp-section-full { grid-column: 1 / -1; }
        .rp-section h2 { font-size: 1.125rem; font-weight: 600; margin: 0 0 1rem 0; display: flex; align-items: center; gap:.5rem; }

        .rp-table { width: 100%; border-collapse: collapse; font-size: .85rem; }
        .rp-table th { text-align: left; padding: .5rem; color: var(--rp-muted); font-weight: 500; border-bottom: 1px solid var(--rp-border); }
        .rp-table td { padding: .5rem; border-bottom: 1px solid rgba(255,255,255,.05); }

        .rp-chart { height: 200px; display: flex; align-items: flex-end; gap: 4px; padding: 1rem 0; }
        .rp-bar { flex: 1; background: linear-gradient(to top, var(--rp-blue), var(--rp-purple)); border-radius: 4px 4px 0 0; min-height: 8px; transition: height .5s ease; position: relative; }
        .rp-bar-label { position: absolute; bottom: -1.5rem; left: 50%; transform: translateX(-50%); font-size: .6rem; color: var(--rp-muted); white-space: nowrap; }

        .rp-tabs { display: flex; gap: .5rem; margin-bottom: 1rem; flex-wrap: wrap; }
        .rp-tab { padding: .4rem .8rem; border-radius: .4rem; border: 1px solid var(--rp-border); background: transparent; color: var(--rp-muted); cursor: pointer; font-size: .8rem; }
        .rp-tab.active { background: var(--rp-blue); border-color: var(--rp-blue); color: #fff; }

        .rp-loading { display: flex; align-items: center; justify-content: center; padding: 2rem; color: var(--rp-muted); }
        .rp-spinner { width: 20px; height: 20px; border: 2px solid var(--rp-border); border-top-color: var(--rp-blue); border-radius: 50%; animation: rp-spin .8s linear infinite; margin-right: .5rem; }
        @keyframes rp-spin { to { transform: rotate(360deg); } }

        @media (max-width: 768px) { .rp-grid { grid-template-columns: 1fr; } .rp-kpis { grid-template-columns: repeat(2, 1fr); } }
    </style>
    <link rel="stylesheet" href="/assets/css/design-tokens.css?v=20260310">
    <link rel="stylesheet" href="/assets/css/components.css?v=20260310">
    <script src="/assets/js/gds-utils.js?v=20260310" defer></script>
    <script src="/assets/js/gds-toast.js?v=20260310" defer></script>
    <script src="/assets/js/gds-modal.js?v=20260310" defer></script>
</head>
<body class="rp-page">
<?php include __DIR__ . '/includes/header.inc.php'; ?>

<div class="rp-wrap">
    <div class="rp-header">
        <h1>📊 Reports & <span>Analytics</span></h1>
        <div style="display:flex;gap:.75rem;">
            <a href="/dashboard" class="rp-btn">← Dashboard</a>
            <button class="rp-btn" onclick="refreshAll()">↻ Refresh</button>
            <button class="rp-btn rp-btn-primary" onclick="exportReport()">⬇ Export CSV</button>
        </div>
    </div>

    <div class="rp-kpis">
        <div class="rp-kpi">
            <div class="rp-kpi-label">Total Conversations</div>
            <div class="rp-kpi-value" id="kpi-convos">--</div>
        </div>
        <div class="rp-kpi">
            <div class="rp-kpi-label">Tool Calls (30d)</div>
            <div class="rp-kpi-value" id="kpi-tools">--</div>
        </div>
        <div class="rp-kpi">
            <div class="rp-kpi-label">Active Users (30d)</div>
            <div class="rp-kpi-value" id="kpi-users">--</div>
        </div>
        <div class="rp-kpi">
            <div class="rp-kpi-label">Revenue (MTD)</div>
            <div class="rp-kpi-value" id="kpi-revenue">--</div>
        </div>
        <div class="rp-kpi">
            <div class="rp-kpi-label">Growth Rate</div>
            <div class="rp-kpi-value" id="kpi-growth">--</div>
        </div>
    </div>

    <div class="rp-grid">
        <div class="rp-section rp-section-full">
            <h2>📈 Usage Trend</h2>
            <div class="rp-tabs">
                <button class="rp-tab active" onclick="loadUsage('daily', this)">Daily</button>
                <button class="rp-tab" onclick="loadUsage('weekly', this)">Weekly</button>
                <button class="rp-tab" onclick="loadUsage('monthly', this)">Monthly</button>
            </div>
            <div class="rp-chart" id="usage-chart"><div class="rp-loading"><div class="rp-spinner"></div> Loading...</div></div>
        </div>

        <div class="rp-section">
            <h2>🔧 Top Tools</h2>
            <div id="tool-usage"><div class="rp-loading"><div class="rp-spinner"></div> Loading...</div></div>
        </div>

        <div class="rp-section">
            <h2>🤖 Agent Performance</h2>
            <div id="agent-perf"><div class="rp-loading"><div class="rp-spinner"></div> Loading...</div></div>
        </div>

        <div class="rp-section">
            <h2>📈 Growth Metrics</h2>
            <div id="growth-metrics"><div class="rp-loading"><div class="rp-spinner"></div> Loading...</div></div>
        </div>

        <div class="rp-section">
            <h2>💾 Saved Reports</h2>
            <div id="saved-reports"><div class="rp-loading"><div class="rp-spinner"></div> Loading...</div></div>
        </div>

        <div class="rp-section rp-section-full">
            <h2>💬 Conversation Stats</h2>
            <div id="convo-stats"><div class="rp-loading"><div class="rp-spinner"></div> Loading...</div></div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.inc.php'; ?>

<script src="/assets/js/reporting-dashboard-engine.js"></script>
</body>
</html>
