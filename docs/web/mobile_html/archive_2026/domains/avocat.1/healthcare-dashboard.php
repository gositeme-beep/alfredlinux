<?php
require_once __DIR__ . '/includes/auth-gate.inc.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Healthcare Dashboard — GoSiteMe</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <style>
        :root {
            --hc-bg: #0a0e17; --hc-card: #111827; --hc-border: #1f2937;
            --hc-teal: #14b8a6; --hc-green: #10b981; --hc-blue: #3b82f6;
            --hc-amber: #f59e0b; --hc-red: #ef4444; --hc-purple: #8b5cf6;
            --hc-text: #e5e7eb; --hc-muted: #9ca3af;
        }
        body.hc-page { background: var(--hc-bg); color: var(--hc-text); font-family: 'Inter', system-ui, sans-serif; margin: 0; }
        .hc-wrap { max-width: 1400px; margin: 0 auto; padding: 2rem 1.5rem; }
        .hc-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
        .hc-header h1 { font-size: 1.75rem; font-weight: 700; margin: 0; }
        .hc-header h1 span { color: var(--hc-teal); }
        .hc-hipaa { font-size: .7rem; padding: .25rem .6rem; border-radius: .25rem; background: rgba(20,184,166,.15); color: var(--hc-teal); font-weight: 600; letter-spacing: .05em; }
        .hc-btn { padding: .5rem 1rem; border-radius: .5rem; border: 1px solid var(--hc-border); background: var(--hc-card); color: var(--hc-text); cursor: pointer; font-size: .875rem; transition: all .2s; text-decoration: none; display: inline-flex; align-items: center; gap: .4rem; }
        .hc-btn:hover { border-color: var(--hc-teal); color: #fff; }
        .hc-btn-primary { background: var(--hc-teal); border-color: var(--hc-teal); color: #000; font-weight: 600; }

        .hc-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .hc-kpi { background: var(--hc-card); border: 1px solid var(--hc-border); border-radius: .75rem; padding: 1.25rem; text-align: center; }
        .hc-kpi-icon { font-size: 1.5rem; margin-bottom: .25rem; }
        .hc-kpi-label { font-size: .75rem; text-transform: uppercase; letter-spacing: .05em; color: var(--hc-muted); margin-bottom: .25rem; }
        .hc-kpi-value { font-size: 1.5rem; font-weight: 700; }

        .hc-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem; }
        .hc-section { background: var(--hc-card); border: 1px solid var(--hc-border); border-radius: .75rem; padding: 1.5rem; }
        .hc-section-full { grid-column: 1 / -1; }
        .hc-section h2 { font-size: 1.125rem; font-weight: 600; margin: 0 0 1rem 0; display: flex; align-items: center; gap: .5rem; }

        .hc-table { width: 100%; border-collapse: collapse; font-size: .85rem; }
        .hc-table th { text-align: left; padding: .5rem; color: var(--hc-muted); font-weight: 500; border-bottom: 1px solid var(--hc-border); }
        .hc-table td { padding: .5rem; border-bottom: 1px solid rgba(255,255,255,.05); }
        .hc-status { display: inline-block; padding: .15rem .5rem; border-radius: .25rem; font-size: .75rem; font-weight: 600; }
        .hc-s-active { background: rgba(20,184,166,.15); color: var(--hc-teal); }
        .hc-s-scheduled { background: rgba(59,130,246,.15); color: var(--hc-blue); }
        .hc-s-completed { background: rgba(16,185,129,.15); color: var(--hc-green); }
        .hc-s-cancelled { background: rgba(156,163,175,.15); color: var(--hc-muted); }
        .hc-s-pending { background: rgba(245,158,11,.15); color: var(--hc-amber); }
        .hc-s-abnormal { background: rgba(239,68,68,.15); color: var(--hc-red); }

        .hc-vitals-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: .75rem; }
        .hc-vital { text-align: center; padding: .75rem; border-radius: .5rem; background: rgba(255,255,255,.03); border: 1px solid var(--hc-border); }
        .hc-vital-label { font-size: .7rem; text-transform: uppercase; color: var(--hc-muted); margin-bottom: .25rem; }
        .hc-vital-value { font-size: 1.1rem; font-weight: 700; }

        .hc-loading { display: flex; align-items: center; justify-content: center; padding: 2rem; color: var(--hc-muted); }
        .hc-spinner { width: 20px; height: 20px; border: 2px solid var(--hc-border); border-top-color: var(--hc-teal); border-radius: 50%; animation: hc-spin .8s linear infinite; margin-right: .5rem; }
        @keyframes hc-spin { to { transform: rotate(360deg); } }

        @media (max-width: 768px) { .hc-grid { grid-template-columns: 1fr; } .hc-kpis { grid-template-columns: repeat(2, 1fr); } }
    </style>
    <link rel="stylesheet" href="/assets/css/design-tokens.css?v=20260310">
    <link rel="stylesheet" href="/assets/css/components.css?v=20260310">
    <script src="/assets/js/gds-utils.js?v=20260310" defer></script>
    <script src="/assets/js/gds-toast.js?v=20260310" defer></script>
    <script src="/assets/js/gds-modal.js?v=20260310" defer></script>
</head>
<body class="hc-page">
<?php include __DIR__ . '/includes/header.inc.php'; ?>

<div class="hc-wrap">
    <div class="hc-header">
        <div style="display:flex;align-items:center;gap:1rem;">
            <h1>🏥 Healthcare <span>Dashboard</span></h1>
            <span class="hc-hipaa">HIPAA COMPLIANT</span>
        </div>
        <div style="display:flex;gap:.75rem;">
            <a href="/dashboard" class="hc-btn">← Dashboard</a>
            <button class="hc-btn" onclick="refreshAll()">↻ Refresh</button>
            <button class="hc-btn hc-btn-primary" onclick="showNewPatient()">+ New Patient</button>
        </div>
    </div>

    <div class="hc-kpis">
        <div class="hc-kpi">
            <div class="hc-kpi-icon">👥</div>
            <div class="hc-kpi-label">Total Patients</div>
            <div class="hc-kpi-value" id="kpi-patients">--</div>
        </div>
        <div class="hc-kpi">
            <div class="hc-kpi-icon">📅</div>
            <div class="hc-kpi-label">Today's Appts</div>
            <div class="hc-kpi-value" id="kpi-appts">--</div>
        </div>
        <div class="hc-kpi">
            <div class="hc-kpi-icon">📋</div>
            <div class="hc-kpi-label">Unsigned Notes</div>
            <div class="hc-kpi-value" id="kpi-unsigned">--</div>
        </div>
        <div class="hc-kpi">
            <div class="hc-kpi-icon">🔬</div>
            <div class="hc-kpi-label">Pending Labs</div>
            <div class="hc-kpi-value" id="kpi-labs">--</div>
        </div>
        <div class="hc-kpi">
            <div class="hc-kpi-icon">📝</div>
            <div class="hc-kpi-label">Pending Intakes</div>
            <div class="hc-kpi-value" id="kpi-intakes">--</div>
        </div>
    </div>

    <div class="hc-grid">
        <div class="hc-section cb-section-full" style="grid-column:1/-1;">
            <h2>📅 Today's Appointments</h2>
            <div id="todays-appts"><div class="hc-loading"><div class="hc-spinner"></div> Loading...</div></div>
        </div>

        <div class="hc-section">
            <h2>👥 Recent Patients</h2>
            <div id="patients-list"><div class="hc-loading"><div class="hc-spinner"></div> Loading...</div></div>
        </div>

        <div class="hc-section">
            <h2>📋 Recent SOAP Notes</h2>
            <div id="soap-list"><div class="hc-loading"><div class="hc-spinner"></div> Loading...</div></div>
        </div>

        <div class="hc-section">
            <h2>💊 Active Medications</h2>
            <div id="meds-list"><div class="hc-loading"><div class="hc-spinner"></div> Loading...</div></div>
        </div>

        <div class="hc-section">
            <h2>🔬 Lab Orders</h2>
            <div id="labs-list"><div class="hc-loading"><div class="hc-spinner"></div> Loading...</div></div>
        </div>

        <div class="hc-section hc-section-full" style="grid-column:1/-1;">
            <h2>📊 Recent Vitals</h2>
            <div id="vitals-display"><div class="hc-loading"><div class="hc-spinner"></div> Loading...</div></div>
        </div>

        <div class="hc-section hc-section-full" style="grid-column:1/-1;">
            <h2>🔒 Audit Log (Last 10)</h2>
            <div id="audit-log"><div class="hc-loading"><div class="hc-spinner"></div> Loading...</div></div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.inc.php'; ?>

<script src="/assets/js/healthcare-dashboard-engine.js"></script>
</body>
</html>
