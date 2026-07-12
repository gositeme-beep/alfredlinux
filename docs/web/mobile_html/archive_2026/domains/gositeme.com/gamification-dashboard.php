<?php
require_once __DIR__ . '/includes/auth-gate.inc.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gamification Hub — GoSiteMe</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <style>
        :root {
            --gm-bg: #0a0e17; --gm-card: #111827; --gm-border: #1f2937;
            --gm-gold: #f59e0b; --gm-green: #10b981; --gm-blue: #3b82f6;
            --gm-purple: #8b5cf6; --gm-red: #ef4444;
            --gm-text: #e5e7eb; --gm-muted: #9ca3af;
        }
        body.gm-page { background: var(--gm-bg); color: var(--gm-text); font-family: 'Inter', system-ui, sans-serif; margin: 0; }
        .gm-wrap { max-width: 1400px; margin: 0 auto; padding: 2rem 1.5rem; }
        .gm-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
        .gm-header h1 { font-size: 1.75rem; font-weight: 700; margin: 0; }
        .gm-header h1 span { color: var(--gm-gold); }
        .gm-btn { padding: .5rem 1rem; border-radius: .5rem; border: 1px solid var(--gm-border); background: var(--gm-card); color: var(--gm-text); cursor: pointer; font-size: .875rem; transition: all .2s; text-decoration: none; display: inline-flex; align-items: center; gap: .4rem; }
        .gm-btn:hover { border-color: var(--gm-gold); color: #fff; }
        .gm-btn-primary { background: var(--gm-gold); border-color: var(--gm-gold); color: #000; font-weight: 600; }

        .gm-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .gm-kpi { background: var(--gm-card); border: 1px solid var(--gm-border); border-radius: .75rem; padding: 1.25rem; text-align: center; }
        .gm-kpi-icon { font-size: 1.5rem; margin-bottom: .25rem; }
        .gm-kpi-label { font-size: .75rem; text-transform: uppercase; letter-spacing: .05em; color: var(--gm-muted); margin-bottom: .25rem; }
        .gm-kpi-value { font-size: 1.5rem; font-weight: 700; }

        .gm-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem; }
        .gm-section { background: var(--gm-card); border: 1px solid var(--gm-border); border-radius: .75rem; padding: 1.5rem; }
        .gm-section-full { grid-column: 1 / -1; }
        .gm-section h2 { font-size: 1.125rem; font-weight: 600; margin: 0 0 1rem 0; display: flex; align-items: center; gap: .5rem; }

        .gm-progress { background: rgba(255,255,255,.05); border-radius: .5rem; height: 12px; overflow: hidden; margin: .5rem 0; }
        .gm-progress-bar { height: 100%; background: linear-gradient(90deg, var(--gm-gold), var(--gm-green)); border-radius: .5rem; transition: width .5s ease; }

        .gm-badge { display: inline-flex; align-items: center; gap: .4rem; padding: .4rem .75rem; border-radius: 2rem; background: rgba(245,158,11,.1); border: 1px solid rgba(245,158,11,.3); font-size: .8rem; margin: .25rem; }
        .gm-badge.earned { background: rgba(16,185,129,.15); border-color: rgba(16,185,129,.4); }

        .gm-lb-row { display: flex; align-items: center; gap: .75rem; padding: .6rem .75rem; border-radius: .5rem; margin-bottom: .4rem; background: rgba(255,255,255,.02); }
        .gm-lb-rank { font-weight: 700; min-width: 1.5rem; color: var(--gm-gold); }
        .gm-lb-name { flex: 1; font-weight: 500; }
        .gm-lb-xp { color: var(--gm-gold); font-weight: 600; }

        .gm-streak { display: flex; align-items: center; gap: .5rem; font-size: 2rem; justify-content: center; padding: 1rem; }
        .gm-streak-day { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .8rem; }
        .gm-streak-day.active { background: var(--gm-gold); color: #000; }
        .gm-streak-day.inactive { background: rgba(255,255,255,.05); color: var(--gm-muted); }

        .gm-loading { display: flex; align-items: center; justify-content: center; padding: 2rem; color: var(--gm-muted); }
        .gm-spinner { width: 20px; height: 20px; border: 2px solid var(--gm-border); border-top-color: var(--gm-gold); border-radius: 50%; animation: gm-spin .8s linear infinite; margin-right: .5rem; }
        @keyframes gm-spin { to { transform: rotate(360deg); } }

        @media (max-width: 768px) { .gm-grid { grid-template-columns: 1fr; } .gm-kpis { grid-template-columns: repeat(2, 1fr); } }
    </style>
    <link rel="stylesheet" href="/assets/css/design-tokens.css?v=20260310">
    <link rel="stylesheet" href="/assets/css/components.css?v=20260310">
    <script src="/assets/js/gds-utils.js?v=20260310" defer></script>
    <script src="/assets/js/gds-toast.js?v=20260310" defer></script>
    <script src="/assets/js/gds-modal.js?v=20260310" defer></script>
</head>
<body class="gm-page">
<?php include __DIR__ . '/includes/header.inc.php'; ?>

<div class="gm-wrap">
    <div class="gm-header">
        <h1>🎮 Gamification <span>Hub</span></h1>
        <div style="display:flex;gap:.75rem;">
            <a href="/dashboard" class="gm-btn">← Dashboard</a>
            <button class="gm-btn" onclick="refreshAll()">↻ Refresh</button>
            <button class="gm-btn gm-btn-primary" onclick="claimDailyChallenge()">🎯 Daily Challenge</button>
        </div>
    </div>

    <div class="gm-kpis">
        <div class="gm-kpi">
            <div class="gm-kpi-icon">⭐</div>
            <div class="gm-kpi-label">Total XP</div>
            <div class="gm-kpi-value" id="kpi-xp">--</div>
        </div>
        <div class="gm-kpi">
            <div class="gm-kpi-icon">🏅</div>
            <div class="gm-kpi-label">Level</div>
            <div class="gm-kpi-value" id="kpi-level">--</div>
        </div>
        <div class="gm-kpi">
            <div class="gm-kpi-icon">🏆</div>
            <div class="gm-kpi-label">Achievements</div>
            <div class="gm-kpi-value" id="kpi-achievements">--</div>
        </div>
        <div class="gm-kpi">
            <div class="gm-kpi-icon">🔥</div>
            <div class="gm-kpi-label">Streak</div>
            <div class="gm-kpi-value" id="kpi-streak">--</div>
        </div>
        <div class="gm-kpi">
            <div class="gm-kpi-icon">📊</div>
            <div class="gm-kpi-label">Rank</div>
            <div class="gm-kpi-value" id="kpi-rank">--</div>
        </div>
    </div>

    <div id="level-progress-wrap" style="margin-bottom:2rem;display:none;">
        <div style="display:flex;justify-content:space-between;font-size:.85rem;margin-bottom:.25rem;">
            <span id="xp-label">Level --</span>
            <span id="xp-next" style="color:var(--gm-muted);">Next: -- XP</span>
        </div>
        <div class="gm-progress"><div class="gm-progress-bar" id="xp-bar" style="width:0%"></div></div>
    </div>

    <div class="gm-grid">
        <div class="gm-section">
            <h2>🔥 Streak Calendar</h2>
            <div id="streak-display"><div class="gm-loading"><div class="gm-spinner"></div> Loading...</div></div>
        </div>

        <div class="gm-section">
            <h2>🎯 Daily Challenge</h2>
            <div id="daily-challenge"><div class="gm-loading"><div class="gm-spinner"></div> Loading...</div></div>
        </div>

        <div class="gm-section">
            <h2>🥇 Leaderboard (Top 10)</h2>
            <div id="leaderboard"><div class="gm-loading"><div class="gm-spinner"></div> Loading...</div></div>
        </div>

        <div class="gm-section">
            <h2>🏆 Recent Achievements</h2>
            <div id="achievements"><div class="gm-loading"><div class="gm-spinner"></div> Loading...</div></div>
        </div>

        <div class="gm-section gm-section-full">
            <h2>📈 XP History</h2>
            <div id="xp-history"><div class="gm-loading"><div class="gm-spinner"></div> Loading...</div></div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.inc.php'; ?>

<script src="/assets/js/gamification-dashboard-engine.js"></script>
</body>
</html>
