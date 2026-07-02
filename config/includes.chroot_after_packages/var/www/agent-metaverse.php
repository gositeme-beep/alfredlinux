<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: /login.php'); exit; }
require_once __DIR__ . '/includes/db-config.inc.php';
require_once __DIR__ . '/includes/fleet-public-stats.inc.php';
$rootFleet = root_fleet_public_stats();
$pageTitle = 'Agent Metaverse Explorer';
include 'includes/site-header.inc.php';
?>
<style>
:root {
    --mv-bg: #0a0e17; --mv-surface: #111827; --mv-card: #1e293b; --mv-border: #1e293b;
    --mv-text: #e2e8f0; --mv-muted: #94a3b8; --mv-accent: #06b6d4; --mv-accent2: #8b5cf6;
    --mv-glow: rgba(6,182,212,0.15); --mv-radius: .75rem;
}
body { background: var(--mv-bg); color: var(--mv-text); font-family: 'Inter', system-ui, sans-serif; }
.mv-wrap { max-width: 1400px; margin: 0 auto; padding: 1.5rem; }
.mv-hero { text-align: center; padding: 2rem 0 1rem; }
.mv-hero h1 { font-size: 2rem; font-weight: 800; background: linear-gradient(135deg, #06b6d4, #8b5cf6, #d946ef); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin: 0; }
.mv-hero p { color: var(--mv-muted); margin: .5rem 0 0; font-size: .95rem; }

/* KPI Strip */
.mv-kpi { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem; margin: 1.5rem 0; }
.mv-kpi-card { background: var(--mv-card); border-radius: var(--mv-radius); padding: 1.2rem; text-align: center; border-top: 3px solid var(--mv-accent); transition: transform .2s; }
.mv-kpi-card:hover { transform: translateY(-2px); }
.mv-kpi-card .kv { font-size: 1.6rem; font-weight: 800; color: #fff; }
.mv-kpi-card .kl { font-size: .75rem; color: var(--mv-muted); margin-top: .25rem; text-transform: uppercase; letter-spacing: .05em; }

/* Space Cards */
.mv-spaces { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.2rem; margin: 1.5rem 0; }
.mv-space { background: var(--mv-card); border-radius: var(--mv-radius); overflow: hidden; cursor: pointer; transition: transform .2s, box-shadow .2s; border: 1px solid var(--mv-border); }
.mv-space:hover { transform: translateY(-3px); box-shadow: 0 8px 30px rgba(0,0,0,.3); }
.mv-space-banner { height: 100px; display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden; }
.mv-space-banner i { font-size: 2.5rem; color: rgba(255,255,255,.9); z-index: 1; }
.mv-space-banner::before { content:''; position:absolute; inset:0; background: inherit; filter: brightness(.7); }
.mv-space-body { padding: 1rem; }
.mv-space-body h3 { margin: 0 0 .4rem; font-size: 1.05rem; color: #fff; }
.mv-space-body p { margin: 0 0 .75rem; font-size: .82rem; color: var(--mv-muted); line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.mv-space-stats { display: flex; gap: 1rem; font-size: .75rem; color: var(--mv-muted); }
.mv-space-stats span i { margin-right: .2rem; }
.mv-space-features { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .6rem; }
.mv-space-features span { background: rgba(6,182,212,.1); color: var(--mv-accent); padding: .15rem .5rem; border-radius: 1rem; font-size: .7rem; }
.mv-space-rating { color: #f59e0b; font-size: .8rem; }

/* Tabs */
.mv-tabs { display: flex; gap: .5rem; margin: 1.5rem 0 1rem; flex-wrap: wrap; }
.mv-tab { padding: .5rem 1.2rem; border-radius: 2rem; background: var(--mv-card); color: var(--mv-muted); font-size: .85rem; cursor: pointer; border: 1px solid var(--mv-border); transition: all .2s; }
.mv-tab.active, .mv-tab:hover { background: var(--mv-accent); color: #fff; border-color: var(--mv-accent); }

/* Activity Feed */
.mv-feed { display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin: 1rem 0; }
@media (max-width: 900px) { .mv-feed { grid-template-columns: 1fr; } }
.mv-feed-list { display: flex; flex-direction: column; gap: .75rem; }
.mv-feed-item { background: var(--mv-card); border-radius: var(--mv-radius); padding: 1rem; border-left: 3px solid var(--mv-accent); }
.mv-feed-item .fi-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: .5rem; }
.mv-feed-item .fi-agent { font-weight: 600; color: #fff; font-size: .9rem; }
.mv-feed-item .fi-dept { font-size: .7rem; padding: .15rem .5rem; border-radius: 1rem; background: rgba(139,92,246,.15); color: #a78bfa; }
.mv-feed-item .fi-space { color: var(--mv-accent); font-weight: 500; font-size: .85rem; }
.mv-feed-item .fi-review { color: var(--mv-muted); font-size: .82rem; margin: .4rem 0; line-height: 1.4; }
.mv-feed-item .fi-meta { display: flex; gap: 1rem; font-size: .75rem; color: var(--mv-muted); margin-top: .5rem; }
.mv-feed-item .fi-activities { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .5rem; }
.mv-feed-item .fi-activities span { background: rgba(6,182,212,.08); color: #67e8f9; padding: .1rem .4rem; border-radius: .5rem; font-size: .7rem; }

/* Sidebar */
.mv-sidebar { display: flex; flex-direction: column; gap: 1rem; }
.mv-sidebar-card { background: var(--mv-card); border-radius: var(--mv-radius); padding: 1rem; }
.mv-sidebar-card h4 { margin: 0 0 .75rem; font-size: .9rem; color: #fff; display: flex; align-items: center; gap: .5rem; }
.mv-sidebar-card h4 i { color: var(--mv-accent); }
.mv-leaderboard { list-style: none; padding: 0; margin: 0; }
.mv-leaderboard li { display: flex; justify-content: space-between; align-items: center; padding: .4rem 0; border-bottom: 1px solid rgba(255,255,255,.05); font-size: .82rem; }
.mv-leaderboard li:last-child { border: none; }
.mv-leaderboard .rank { color: #f59e0b; font-weight: 700; width: 1.5rem; }
.mv-leaderboard .name { color: #fff; flex: 1; }
.mv-leaderboard .score { color: var(--mv-muted); }
.mv-mood-chart { display: flex; flex-wrap: wrap; gap: .4rem; }
.mv-mood { padding: .2rem .6rem; border-radius: 1rem; font-size: .72rem; }

/* Creations Grid */
.mv-creations { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1rem; }
.mv-creation { background: var(--mv-card); border-radius: var(--mv-radius); padding: 1rem; border: 1px solid var(--mv-border); }
.mv-creation h5 { margin: 0 0 .3rem; color: #fff; font-size: .9rem; }
.mv-creation .cr-type { font-size: .7rem; padding: .1rem .5rem; border-radius: 1rem; display: inline-block; margin-bottom: .4rem; }
.mv-creation p { font-size: .8rem; color: var(--mv-muted); margin: .3rem 0; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.mv-creation .cr-meta { font-size: .72rem; color: var(--mv-muted); margin-top: .5rem; }

/* Discovery Cards */
.mv-discovery { background: var(--mv-card); border-radius: var(--mv-radius); padding: .8rem 1rem; border-left: 3px solid #f59e0b; margin-bottom: .5rem; }
.mv-discovery .disc-agent { font-size: .8rem; color: #fff; font-weight: 600; }
.mv-discovery .disc-space { font-size: .75rem; color: var(--mv-accent); }
.mv-discovery .disc-text { font-size: .8rem; color: var(--mv-muted); margin-top: .3rem; }

/* Space Detail Modal */
.mv-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.7); z-index: 1000; justify-content: center; align-items: flex-start; padding: 2rem; overflow-y: auto; }
.mv-modal-overlay.open { display: flex; }
.mv-modal { background: var(--mv-surface); border-radius: 1rem; max-width: 800px; width: 100%; max-height: 90vh; overflow-y: auto; }
.mv-modal-banner { height: 120px; display: flex; align-items: center; justify-content: center; border-radius: 1rem 1rem 0 0; position: relative; }
.mv-modal-banner i { font-size: 3rem; color: rgba(255,255,255,.9); }
.mv-modal-close { position: absolute; top: 1rem; right: 1rem; background: rgba(0,0,0,.5); color: #fff; border: none; width: 2rem; height: 2rem; border-radius: 50%; cursor: pointer; font-size: 1.2rem; display: flex; align-items: center; justify-content: center; }
.mv-modal-body { padding: 1.5rem; }
.mv-modal-body h2 { margin: 0 0 .5rem; font-size: 1.4rem; }
.mv-modal-body .desc { color: var(--mv-muted); font-size: .9rem; margin-bottom: 1rem; line-height: 1.5; }
.mv-modal-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: .75rem; margin: 1rem 0; }
.mv-modal-stat { text-align: center; padding: .75rem; background: var(--mv-card); border-radius: .5rem; }
.mv-modal-stat .sv { font-size: 1.3rem; font-weight: 700; color: #fff; }
.mv-modal-stat .sl { font-size: .7rem; color: var(--mv-muted); }
.mv-modal-reviews { margin-top: 1rem; }
.mv-modal-review { padding: .75rem; border-bottom: 1px solid rgba(255,255,255,.05); }
.mv-modal-review:last-child { border: none; }
.mv-modal-review .rv-header { display: flex; justify-content: space-between; font-size: .85rem; }
.mv-modal-review .rv-name { color: #fff; font-weight: 600; }
.mv-modal-review .rv-text { color: var(--mv-muted); font-size: .82rem; margin-top: .3rem; }
.mv-improvements { list-style: none; padding: 0; margin: .5rem 0; }
.mv-improvements li { padding: .3rem 0; font-size: .82rem; color: var(--mv-muted); }
.mv-improvements li::before { content: '💡'; margin-right: .4rem; }
</style>

<div class="mv-wrap">
    <div class="mv-hero">
        <h1><i class="fas fa-globe"></i> Agent Metaverse Explorer</h1>
        <p>16 VR worlds explored by <?= htmlspecialchars($rootFleet['fleet_headline']) ?> agents — creating, discovering, and improving together</p>
    </div>

    <!-- KPI Strip -->
    <div class="mv-kpi" id="kpiStrip">
        <div class="mv-kpi-card" style="border-color:#06b6d4"><div class="kv" id="kpi-sessions">—</div><div class="kl">VR Sessions</div></div>
        <div class="mv-kpi-card" style="border-color:#8b5cf6"><div class="kv" id="kpi-explorers">—</div><div class="kl">Unique Explorers</div></div>
        <div class="mv-kpi-card" style="border-color:#f59e0b"><div class="kv" id="kpi-spaces">—</div><div class="kl">Worlds Visited</div></div>
        <div class="mv-kpi-card" style="border-color:#10b981"><div class="kv" id="kpi-rating">—</div><div class="kl">Avg Rating</div></div>
        <div class="mv-kpi-card" style="border-color:#ec4899"><div class="kv" id="kpi-creations">—</div><div class="kl">Creations</div></div>
        <div class="mv-kpi-card" style="border-color:#d946ef"><div class="kv" id="kpi-hours">—</div><div class="kl">Total VR Hours</div></div>
    </div>

    <!-- Tabs -->
    <div class="mv-tabs">
        <div class="mv-tab active" data-tab="worlds"><i class="fas fa-globe"></i> Worlds</div>
        <div class="mv-tab" data-tab="activity"><i class="fas fa-signal"></i> Live Activity</div>
        <div class="mv-tab" data-tab="creations"><i class="fas fa-palette"></i> Creations</div>
        <div class="mv-tab" data-tab="discoveries"><i class="fas fa-lightbulb"></i> Discoveries</div>
    </div>

    <!-- Tab: Worlds Grid -->
    <div id="tab-worlds" class="mv-tab-content">
        <div class="mv-spaces" id="spacesGrid"></div>
    </div>

    <!-- Tab: Live Activity -->
    <div id="tab-activity" class="mv-tab-content" style="display:none">
        <div class="mv-feed">
            <div class="mv-feed-list" id="activityFeed"></div>
            <div class="mv-sidebar">
                <div class="mv-sidebar-card">
                    <h4><i class="fas fa-trophy"></i> Top Explorers</h4>
                    <ul class="mv-leaderboard" id="explorerLeaderboard"></ul>
                </div>
                <div class="mv-sidebar-card">
                    <h4><i class="fas fa-face-smile"></i> Mood Transformations</h4>
                    <div class="mv-mood-chart" id="moodChart"></div>
                </div>
                <div class="mv-sidebar-card">
                    <h4><i class="fas fa-chart-pie"></i> Space Popularity</h4>
                    <ul class="mv-leaderboard" id="spacePopularity"></ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab: Creations -->
    <div id="tab-creations" class="mv-tab-content" style="display:none">
        <div class="mv-creations" id="creationsGrid"></div>
    </div>

    <!-- Tab: Discoveries -->
    <div id="tab-discoveries" class="mv-tab-content" style="display:none">
        <div id="discoveriesFeed"></div>
    </div>
</div>

<!-- Space Detail Modal -->
<div class="mv-modal-overlay" id="spaceModal">
    <div class="mv-modal">
        <div class="mv-modal-banner" id="modalBanner">
            <i id="modalIcon"></i>
            <button class="mv-modal-close" onclick="document.getElementById('spaceModal').classList.remove('open')">&times;</button>
        </div>
        <div class="mv-modal-body" id="modalBody"></div>
    </div>
</div>

<script src="/assets/js/agent-metaverse-engine.js"></script>

<?php include 'includes/site-footer.inc.php'; ?>
