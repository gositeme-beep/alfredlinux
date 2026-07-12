<?php
require_once __DIR__ . '/includes/auth-gate.inc.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Tools — GoSiteMe</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <style>
        :root {
            --bz-bg: #0a0e17; --bz-card: #111827; --bz-border: #1f2937;
            --bz-green: #10b981; --bz-blue: #3b82f6; --bz-purple: #8b5cf6;
            --bz-amber: #f59e0b; --bz-red: #ef4444;
            --bz-text: #e5e7eb; --bz-muted: #9ca3af;
        }
        body.bz-page { background: var(--bz-bg); color: var(--bz-text); font-family: 'Inter', system-ui, sans-serif; margin: 0; }
        .bz-wrap { max-width: 1400px; margin: 0 auto; padding: 2rem 1.5rem; }
        .bz-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
        .bz-header h1 { font-size: 1.75rem; font-weight: 700; margin: 0; }
        .bz-header h1 span { color: var(--bz-green); }
        .bz-btn { padding: .5rem 1rem; border-radius: .5rem; border: 1px solid var(--bz-border); background: var(--bz-card); color: var(--bz-text); cursor: pointer; font-size: .875rem; transition: all .2s; text-decoration: none; display: inline-flex; align-items: center; gap: .4rem; }
        .bz-btn:hover { border-color: var(--bz-green); color: #fff; }
        .bz-btn-primary { background: var(--bz-green); border-color: var(--bz-green); color: #000; font-weight: 600; }

        .bz-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .bz-kpi { background: var(--bz-card); border: 1px solid var(--bz-border); border-radius: .75rem; padding: 1.25rem; }
        .bz-kpi-label { font-size: .75rem; text-transform: uppercase; letter-spacing: .05em; color: var(--bz-muted); margin-bottom: .25rem; }
        .bz-kpi-value { font-size: 1.5rem; font-weight: 700; }

        .bz-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem; }
        .bz-section { background: var(--bz-card); border: 1px solid var(--bz-border); border-radius: .75rem; padding: 1.5rem; }
        .bz-section-full { grid-column: 1 / -1; }
        .bz-section h2 { font-size: 1.125rem; font-weight: 600; margin: 0 0 1rem 0; display: flex; align-items: center; gap: .5rem; }

        .bz-table { width: 100%; border-collapse: collapse; font-size: .85rem; }
        .bz-table th { text-align: left; padding: .5rem; color: var(--bz-muted); font-weight: 500; border-bottom: 1px solid var(--bz-border); }
        .bz-table td { padding: .5rem; border-bottom: 1px solid rgba(255,255,255,.05); }
        .bz-status { display: inline-block; padding: .15rem .5rem; border-radius: .25rem; font-size: .75rem; font-weight: 600; }
        .bz-s-active { background: rgba(16,185,129,.15); color: var(--bz-green); }
        .bz-s-pending { background: rgba(245,158,11,.15); color: var(--bz-amber); }
        .bz-s-overdue { background: rgba(239,68,68,.15); color: var(--bz-red); }
        .bz-s-paid { background: rgba(59,130,246,.15); color: var(--bz-blue); }

        .bz-loading { display: flex; align-items: center; justify-content: center; padding: 2rem; color: var(--bz-muted); }
        .bz-spinner { width: 20px; height: 20px; border: 2px solid var(--bz-border); border-top-color: var(--bz-green); border-radius: 50%; animation: bz-spin .8s linear infinite; margin-right: .5rem; }
        @keyframes bz-spin { to { transform: rotate(360deg); } }

        .bz-tabs { display: flex; gap: .5rem; margin-bottom: 1rem; flex-wrap: wrap; }
        .bz-tab { padding: .4rem .8rem; border-radius: .4rem; border: 1px solid var(--bz-border); background: transparent; color: var(--bz-muted); cursor: pointer; font-size: .8rem; }
        .bz-tab.active { background: var(--bz-green); border-color: var(--bz-green); color: #000; }

        @media (max-width: 768px) { .bz-grid { grid-template-columns: 1fr; } .bz-kpis { grid-template-columns: repeat(2, 1fr); } }
    </style>
    <link rel="stylesheet" href="/assets/css/design-tokens.css?v=20260310">
    <link rel="stylesheet" href="/assets/css/components.css?v=20260310">
    <script src="/assets/js/gds-utils.js?v=20260310" defer></script>
    <script src="/assets/js/gds-toast.js?v=20260310" defer></script>
    <script src="/assets/js/gds-modal.js?v=20260310" defer></script>
</head>
<body class="bz-page">
<?php include __DIR__ . '/includes/header.inc.php'; ?>

<div class="bz-wrap">
    <div class="bz-header">
        <h1>💼 Business <span>Tools</span></h1>
        <div style="display:flex;gap:.75rem;">
            <a href="/dashboard" class="bz-btn">← Dashboard</a>
            <button class="bz-btn" onclick="refreshAll()">↻ Refresh</button>
            <button class="bz-btn bz-btn-primary" onclick="showNewContact()">+ New Contact</button>
        </div>
    </div>

    <div class="bz-kpis">
        <div class="bz-kpi">
            <div class="bz-kpi-label">Total Contacts</div>
            <div class="bz-kpi-value" id="kpi-contacts">--</div>
        </div>
        <div class="bz-kpi">
            <div class="bz-kpi-label">Active Projects</div>
            <div class="bz-kpi-value" id="kpi-projects">--</div>
        </div>
        <div class="bz-kpi">
            <div class="bz-kpi-label">Open Tasks</div>
            <div class="bz-kpi-value" id="kpi-tasks">--</div>
        </div>
        <div class="bz-kpi">
            <div class="bz-kpi-label">Hours This Month</div>
            <div class="bz-kpi-value" id="kpi-hours">--</div>
        </div>
        <div class="bz-kpi">
            <div class="bz-kpi-label">Unpaid Invoices</div>
            <div class="bz-kpi-value" id="kpi-invoices">--</div>
        </div>
    </div>

    <div class="bz-grid">
        <div class="bz-section">
            <h2>👤 Recent Contacts</h2>
            <div id="contacts-list"><div class="bz-loading"><div class="bz-spinner"></div> Loading...</div></div>
        </div>

        <div class="bz-section">
            <h2>📋 Active Projects</h2>
            <div id="projects-list"><div class="bz-loading"><div class="bz-spinner"></div> Loading...</div></div>
        </div>

        <div class="bz-section">
            <h2>✅ Open Tasks</h2>
            <div id="tasks-list"><div class="bz-loading"><div class="bz-spinner"></div> Loading...</div></div>
        </div>

        <div class="bz-section">
            <h2>⏱️ Time Log</h2>
            <div id="time-log"><div class="bz-loading"><div class="bz-spinner"></div> Loading...</div></div>
        </div>

        <div class="bz-section bz-section-full">
            <h2>🧾 Recent Invoices</h2>
            <div id="invoices-list"><div class="bz-loading"><div class="bz-spinner"></div> Loading...</div></div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.inc.php'; ?>

<script src="/assets/js/biz-dashboard-engine.js"></script>
</body>
</html>
