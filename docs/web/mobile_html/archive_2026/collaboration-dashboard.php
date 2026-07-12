<?php
require_once __DIR__ . '/includes/auth-gate.inc.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collaboration Hub — GoSiteMe</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <style>
        :root {
            --cb-bg: #0a0e17; --cb-card: #111827; --cb-border: #1f2937;
            --cb-purple: #8b5cf6; --cb-green: #10b981; --cb-blue: #3b82f6;
            --cb-amber: #f59e0b; --cb-red: #ef4444;
            --cb-text: #e5e7eb; --cb-muted: #9ca3af;
        }
        body.cb-page { background: var(--cb-bg); color: var(--cb-text); font-family: 'Inter', system-ui, sans-serif; margin: 0; }
        .cb-wrap { max-width: 1400px; margin: 0 auto; padding: 2rem 1.5rem; }
        .cb-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
        .cb-header h1 { font-size: 1.75rem; font-weight: 700; margin: 0; }
        .cb-header h1 span { color: var(--cb-purple); }
        .cb-btn { padding: .5rem 1rem; border-radius: .5rem; border: 1px solid var(--cb-border); background: var(--cb-card); color: var(--cb-text); cursor: pointer; font-size: .875rem; transition: all .2s; text-decoration: none; display: inline-flex; align-items: center; gap: .4rem; }
        .cb-btn:hover { border-color: var(--cb-purple); color: #fff; }
        .cb-btn-primary { background: var(--cb-purple); border-color: var(--cb-purple); color: #fff; font-weight: 600; }

        .cb-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .cb-kpi { background: var(--cb-card); border: 1px solid var(--cb-border); border-radius: .75rem; padding: 1.25rem; text-align: center; }
        .cb-kpi-icon { font-size: 1.5rem; margin-bottom: .25rem; }
        .cb-kpi-label { font-size: .75rem; text-transform: uppercase; letter-spacing: .05em; color: var(--cb-muted); margin-bottom: .25rem; }
        .cb-kpi-value { font-size: 1.5rem; font-weight: 700; }

        .cb-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem; }
        .cb-section { background: var(--cb-card); border: 1px solid var(--cb-border); border-radius: .75rem; padding: 1.5rem; }
        .cb-section-full { grid-column: 1 / -1; }
        .cb-section h2 { font-size: 1.125rem; font-weight: 600; margin: 0 0 1rem 0; display: flex; align-items: center; gap: .5rem; }

        .cb-table { width: 100%; border-collapse: collapse; font-size: .85rem; }
        .cb-table th { text-align: left; padding: .5rem; color: var(--cb-muted); font-weight: 500; border-bottom: 1px solid var(--cb-border); }
        .cb-table td { padding: .5rem; border-bottom: 1px solid rgba(255,255,255,.05); }
        .cb-status { display: inline-block; padding: .15rem .5rem; border-radius: .25rem; font-size: .75rem; font-weight: 600; }
        .cb-s-active { background: rgba(16,185,129,.15); color: var(--cb-green); }
        .cb-s-ended { background: rgba(156,163,175,.15); color: var(--cb-muted); }
        .cb-s-locked { background: rgba(239,68,68,.15); color: var(--cb-red); }

        .cb-session-card { background: rgba(139,92,246,.05); border: 1px solid rgba(139,92,246,.2); border-radius: .5rem; padding: 1rem; margin-bottom: .75rem; display: flex; justify-content: space-between; align-items: center; }
        .cb-session-card h3 { margin: 0; font-size: .95rem; }
        .cb-session-card .meta { font-size: .75rem; color: var(--cb-muted); margin-top: .25rem; }

        .cb-loading { display: flex; align-items: center; justify-content: center; padding: 2rem; color: var(--cb-muted); }
        .cb-spinner { width: 20px; height: 20px; border: 2px solid var(--cb-border); border-top-color: var(--cb-purple); border-radius: 50%; animation: cb-spin .8s linear infinite; margin-right: .5rem; }
        @keyframes cb-spin { to { transform: rotate(360deg); } }

        @media (max-width: 768px) { .cb-grid { grid-template-columns: 1fr; } .cb-kpis { grid-template-columns: repeat(2, 1fr); } }
    </style>
    <link rel="stylesheet" href="/assets/css/design-tokens.css?v=20260310">
    <link rel="stylesheet" href="/assets/css/components.css?v=20260310">
    <script src="/assets/js/gds-utils.js?v=20260310" defer></script>
    <script src="/assets/js/gds-toast.js?v=20260310" defer></script>
    <script src="/assets/js/gds-modal.js?v=20260310" defer></script>
</head>
<body class="cb-page">
<?php include __DIR__ . '/includes/header.inc.php'; ?>

<div class="cb-wrap">
    <div class="cb-header">
        <h1>👥 Collaboration <span>Hub</span></h1>
        <div style="display:flex;gap:.75rem;">
            <a href="/dashboard" class="cb-btn">← Dashboard</a>
            <button class="cb-btn" onclick="refreshAll()">↻ Refresh</button>
            <button class="cb-btn cb-btn-primary" onclick="createSession()">+ New Session</button>
        </div>
    </div>

    <div class="cb-kpis">
        <div class="cb-kpi">
            <div class="cb-kpi-icon">🗂️</div>
            <div class="cb-kpi-label">Active Sessions</div>
            <div class="cb-kpi-value" id="kpi-sessions">--</div>
        </div>
        <div class="cb-kpi">
            <div class="cb-kpi-icon">📝</div>
            <div class="cb-kpi-label">Documents</div>
            <div class="cb-kpi-value" id="kpi-docs">--</div>
        </div>
        <div class="cb-kpi">
            <div class="cb-kpi-icon">🎨</div>
            <div class="cb-kpi-label">Whiteboards</div>
            <div class="cb-kpi-value" id="kpi-boards">--</div>
        </div>
        <div class="cb-kpi">
            <div class="cb-kpi-icon">📹</div>
            <div class="cb-kpi-label">Conferences</div>
            <div class="cb-kpi-value" id="kpi-confs">--</div>
        </div>
        <div class="cb-kpi">
            <div class="cb-kpi-icon">📊</div>
            <div class="cb-kpi-label">Active Polls</div>
            <div class="cb-kpi-value" id="kpi-polls">--</div>
        </div>
    </div>

    <div class="cb-grid">
        <div class="cb-section cb-section-full">
            <h2>🗂️ Active Sessions</h2>
            <div id="sessions-list"><div class="cb-loading"><div class="cb-spinner"></div> Loading...</div></div>
        </div>

        <div class="cb-section">
            <h2>📝 Recent Documents</h2>
            <div id="docs-list"><div class="cb-loading"><div class="cb-spinner"></div> Loading...</div></div>
        </div>

        <div class="cb-section">
            <h2>🎨 Whiteboards</h2>
            <div id="boards-list"><div class="cb-loading"><div class="cb-spinner"></div> Loading...</div></div>
        </div>

        <div class="cb-section">
            <h2>📹 Conference Rooms</h2>
            <div id="conf-list"><div class="cb-loading"><div class="cb-spinner"></div> Loading...</div></div>
        </div>

        <div class="cb-section">
            <h2>📊 Recent Polls</h2>
            <div id="polls-list"><div class="cb-loading"><div class="cb-spinner"></div> Loading...</div></div>
        </div>

        <div class="cb-section cb-section-full">
            <h2>💬 Recent Chat</h2>
            <div id="chat-list"><div class="cb-loading"><div class="cb-spinner"></div> Loading...</div></div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.inc.php'; ?>

<script src="/assets/js/collaboration-dashboard-engine.js"></script>
</body>
</html>
