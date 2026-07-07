<?php
require_once __DIR__ . '/includes/auth-gate.inc.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Events & Initiatives — GoSiteMe</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --ev-bg: #0a0e17;
            --ev-card: #111827;
            --ev-border: #1f2937;
            --ev-green: #10b981;
            --ev-blue: #3b82f6;
            --ev-purple: #8b5cf6;
            --ev-pink: #ec4899;
            --ev-amber: #f59e0b;
            --ev-red: #ef4444;
            --ev-cyan: #06b6d4;
            --ev-teal: #14b8a6;
            --ev-text: #e5e7eb;
            --ev-muted: #9ca3af;
        }
        body.ev-page { background: var(--ev-bg); color: var(--ev-text); font-family: 'Inter', system-ui, sans-serif; margin: 0; }
        .ev-wrap { max-width: 1400px; margin: 0 auto; padding: 2rem 1.5rem; }

        /* Header */
        .ev-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
        .ev-header h1 { font-size: 1.75rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: .75rem; }
        .ev-header h1 i { color: var(--ev-purple); }
        .ev-header h1 .ev-live-dot { width: 10px; height: 10px; background: var(--ev-green); border-radius: 50%; animation: evPulse 2s infinite; }
        @keyframes evPulse { 0%, 100% { opacity: 1; } 50% { opacity: .4; } }
        .ev-header-actions { display: flex; gap: .75rem; align-items: center; }
        .ev-search { background: var(--ev-card); border: 1px solid var(--ev-border); border-radius: .5rem; padding: .5rem 1rem; color: var(--ev-text); font-size: .875rem; width: 240px; outline: none; }
        .ev-search:focus { border-color: var(--ev-purple); }
        .ev-search::placeholder { color: var(--ev-muted); }

        /* KPIs */
        .ev-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .ev-kpi { background: var(--ev-card); border: 1px solid var(--ev-border); border-radius: .75rem; padding: 1.25rem; position: relative; overflow: hidden; }
        .ev-kpi::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; }
        .ev-kpi.kpi-events::before { background: var(--ev-purple); }
        .ev-kpi.kpi-upcoming::before { background: var(--ev-blue); }
        .ev-kpi.kpi-live::before { background: var(--ev-green); }
        .ev-kpi.kpi-registrations::before { background: var(--ev-cyan); }
        .ev-kpi.kpi-organizers::before { background: var(--ev-amber); }
        .ev-kpi.kpi-raised::before { background: var(--ev-pink); }
        .ev-kpi-label { font-size: .7rem; text-transform: uppercase; letter-spacing: .06em; color: var(--ev-muted); margin-bottom: .35rem; }
        .ev-kpi-value { font-size: 1.5rem; font-weight: 700; }
        .ev-kpi-sub { font-size: .7rem; color: var(--ev-muted); margin-top: .25rem; }

        /* Category Filter Ribbon */
        .ev-categories { display: flex; gap: .5rem; margin-bottom: 2rem; overflow-x: auto; padding-bottom: .5rem; flex-wrap: wrap; }
        .ev-cat-btn { background: var(--ev-card); border: 1px solid var(--ev-border); border-radius: 2rem; padding: .45rem 1rem; color: var(--ev-muted); font-size: .8rem; cursor: pointer; transition: all .2s; display: flex; align-items: center; gap: .4rem; white-space: nowrap; }
        .ev-cat-btn:hover, .ev-cat-btn.active { background: var(--ev-purple); border-color: var(--ev-purple); color: #fff; }
        .ev-cat-btn .ev-cat-count { background: rgba(255,255,255,.15); border-radius: 1rem; padding: 0 .5rem; font-size: .7rem; }

        /* Main Layout */
        .ev-layout { display: grid; grid-template-columns: 1fr 320px; gap: 1.5rem; }
        @media (max-width: 1100px) { .ev-layout { grid-template-columns: 1fr; } }

        /* Featured Carousel */
        .ev-featured { background: linear-gradient(135deg, rgba(139,92,246,.1), rgba(59,130,246,.1)); border: 1px solid rgba(139,92,246,.25); border-radius: 1rem; padding: 1.5rem; margin-bottom: 2rem; }
        .ev-featured-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .ev-featured-header h2 { font-size: 1.1rem; margin: 0; display: flex; align-items: center; gap: .5rem; }
        .ev-featured-header h2 i { color: var(--ev-amber); }
        .ev-featured-scroll { display: flex; gap: 1rem; overflow-x: auto; padding-bottom: .5rem; scroll-snap-type: x mandatory; }
        .ev-featured-card { min-width: 320px; max-width: 340px; scroll-snap-align: start; background: var(--ev-card); border: 1px solid var(--ev-border); border-radius: .75rem; overflow: hidden; cursor: pointer; transition: all .2s; flex-shrink: 0; }
        .ev-featured-card:hover { border-color: var(--ev-purple); transform: translateY(-2px); }
        .ev-featured-banner { height: 100px; display: flex; align-items: center; justify-content: center; }
        .ev-featured-banner i { font-size: 2rem; color: #fff; opacity: .8; }
        .ev-featured-body { padding: 1rem; }
        .ev-featured-type { font-size: .65rem; text-transform: uppercase; letter-spacing: .08em; padding: .2rem .6rem; border-radius: 1rem; display: inline-block; margin-bottom: .5rem; font-weight: 600; }
        .ev-featured-body h3 { font-size: .95rem; margin: 0 0 .35rem; font-weight: 600; }
        .ev-featured-body p { font-size: .75rem; color: var(--ev-muted); margin: 0 0 .5rem; line-height: 1.4; }
        .ev-featured-meta { display: flex; gap: .75rem; font-size: .7rem; color: var(--ev-muted); }
        .ev-featured-meta span { display: flex; align-items: center; gap: .25rem; }

        /* Event Cards */
        .ev-grid { display: flex; flex-direction: column; gap: 1rem; }
        .ev-card { background: var(--ev-card); border: 1px solid var(--ev-border); border-radius: .75rem; overflow: hidden; transition: all .2s; cursor: pointer; }
        .ev-card:hover { border-color: var(--ev-purple); }
        .ev-card-inner { display: flex; gap: 1rem; padding: 1.25rem; }
        .ev-card-icon { width: 56px; height: 56px; border-radius: .75rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .ev-card-icon i { font-size: 1.35rem; color: #fff; }
        .ev-card-content { flex: 1; min-width: 0; }
        .ev-card-top { display: flex; align-items: center; gap: .5rem; margin-bottom: .35rem; flex-wrap: wrap; }
        .ev-card-badge { font-size: .6rem; text-transform: uppercase; letter-spacing: .06em; padding: .15rem .5rem; border-radius: 1rem; font-weight: 600; }
        .ev-card-badge.badge-upcoming { background: rgba(59,130,246,.15); color: var(--ev-blue); }
        .ev-card-badge.badge-live { background: rgba(16,185,129,.15); color: var(--ev-green); }
        .ev-card-badge.badge-completed { background: rgba(156,163,175,.15); color: var(--ev-muted); }
        .ev-card-badge.badge-type { background: rgba(139,92,246,.15); color: var(--ev-purple); }
        .ev-card h3 { font-size: .95rem; margin: 0 0 .25rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .ev-card-desc { font-size: .78rem; color: var(--ev-muted); margin: 0 0 .5rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.4; }
        .ev-card-footer { display: flex; gap: 1rem; font-size: .72rem; color: var(--ev-muted); flex-wrap: wrap; }
        .ev-card-footer span { display: flex; align-items: center; gap: .3rem; }
        .ev-card-footer .ev-highlight { color: var(--ev-green); font-weight: 600; }

        /* Progress Bar (for fundraisers) */
        .ev-progress { margin-top: .5rem; }
        .ev-progress-bar { height: 6px; background: rgba(255,255,255,.06); border-radius: 3px; overflow: hidden; }
        .ev-progress-fill { height: 100%; border-radius: 3px; transition: width .5s ease; }
        .ev-progress-label { display: flex; justify-content: space-between; font-size: .65rem; color: var(--ev-muted); margin-top: .25rem; }

        /* Tags */
        .ev-tags { display: flex; gap: .3rem; flex-wrap: wrap; margin-top: .4rem; }
        .ev-tag { font-size: .6rem; padding: .1rem .5rem; border-radius: 1rem; background: rgba(139,92,246,.1); color: var(--ev-purple); border: 1px solid rgba(139,92,246,.2); }

        /* Sidebar */
        .ev-sidebar { display: flex; flex-direction: column; gap: 1rem; }
        .ev-side-card { background: var(--ev-card); border: 1px solid var(--ev-border); border-radius: .75rem; padding: 1.25rem; }
        .ev-side-card h3 { font-size: .85rem; margin: 0 0 1rem; display: flex; align-items: center; gap: .5rem; }
        .ev-side-card h3 i { color: var(--ev-purple); }

        /* Top Organizers */
        .ev-organizer { display: flex; align-items: center; gap: .65rem; padding: .5rem 0; border-bottom: 1px solid var(--ev-border); }
        .ev-organizer:last-child { border: none; }
        .ev-organizer-avatar { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: .75rem; color: #fff; flex-shrink: 0; }
        .ev-organizer-info { flex: 1; min-width: 0; }
        .ev-organizer-name { font-size: .8rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .ev-organizer-meta { font-size: .65rem; color: var(--ev-muted); }
        .ev-organizer-stat { font-size: .75rem; font-weight: 600; color: var(--ev-purple); }

        /* Type Distribution */
        .ev-type-row { display: flex; align-items: center; gap: .65rem; padding: .4rem 0; }
        .ev-type-icon { width: 28px; height: 28px; border-radius: .4rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .ev-type-icon i { font-size: .7rem; color: #fff; }
        .ev-type-label { font-size: .75rem; flex: 1; }
        .ev-type-count { font-size: .75rem; font-weight: 600; color: var(--ev-text); }

        /* Charity Raised */
        .ev-charity-block { text-align: center; padding: 1rem 0; }
        .ev-charity-amount { font-size: 1.75rem; font-weight: 700; color: var(--ev-pink); }
        .ev-charity-goal { font-size: .75rem; color: var(--ev-muted); margin-top: .25rem; }

        /* Live Activity */
        .ev-activity { list-style: none; padding: 0; margin: 0; }
        .ev-activity li { font-size: .72rem; color: var(--ev-muted); padding: .35rem 0; border-bottom: 1px solid var(--ev-border); display: flex; align-items: flex-start; gap: .4rem; }
        .ev-activity li:last-child { border: none; }
        .ev-activity .ev-act-icon { color: var(--ev-green); flex-shrink: 0; margin-top: .1rem; }
        .ev-activity .ev-act-name { color: var(--ev-text); font-weight: 600; }

        /* Event Detail Modal */
        .ev-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.7); z-index: 10000; display: none; align-items: center; justify-content: center; padding: 2rem; backdrop-filter: blur(4px); }
        .ev-modal-overlay.active { display: flex; }
        .ev-modal { background: var(--ev-card); border: 1px solid var(--ev-border); border-radius: 1rem; max-width: 700px; width: 100%; max-height: 85vh; overflow-y: auto; position: relative; }
        .ev-modal-close { position: absolute; top: 1rem; right: 1rem; background: none; border: none; color: var(--ev-muted); cursor: pointer; font-size: 1.1rem; z-index: 1; }
        .ev-modal-close:hover { color: #fff; }
        .ev-modal-banner { height: 120px; display: flex; align-items: center; justify-content: center; border-radius: 1rem 1rem 0 0; }
        .ev-modal-banner i { font-size: 2.5rem; color: #fff; opacity: .7; }
        .ev-modal-body { padding: 1.75rem; }
        .ev-modal-body h2 { font-size: 1.35rem; margin: 0 0 .5rem; }
        .ev-modal-meta { display: flex; gap: 1rem; flex-wrap: wrap; font-size: .8rem; color: var(--ev-muted); margin-bottom: 1rem; }
        .ev-modal-meta span { display: flex; align-items: center; gap: .3rem; }
        .ev-modal-desc { font-size: .88rem; line-height: 1.65; color: var(--ev-text); margin-bottom: 1.25rem; }
        .ev-modal-section { margin-bottom: 1.25rem; }
        .ev-modal-section h4 { font-size: .8rem; text-transform: uppercase; letter-spacing: .05em; color: var(--ev-muted); margin: 0 0 .75rem; }
        .ev-modal-attendees { display: flex; flex-wrap: wrap; gap: .5rem; }
        .ev-modal-attendee { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .65rem; font-weight: 700; color: #fff; border: 2px solid var(--ev-bg); }
        .ev-modal-more { width: 36px; height: 36px; border-radius: 50%; background: var(--ev-border); display: flex; align-items: center; justify-content: center; font-size: .6rem; color: var(--ev-muted); border: 2px solid var(--ev-bg); }
        .ev-modal-actions { display: flex; gap: .75rem; margin-top: 1.5rem; }
        .ev-btn { padding: .6rem 1.25rem; border-radius: .5rem; border: 1px solid var(--ev-border); background: var(--ev-card); color: var(--ev-text); cursor: pointer; font-size: .85rem; transition: all .2s; display: inline-flex; align-items: center; gap: .4rem; }
        .ev-btn:hover { border-color: var(--ev-purple); color: #fff; }
        .ev-btn-primary { background: linear-gradient(135deg, var(--ev-purple), var(--ev-blue)); border: none; color: #fff; font-weight: 600; }
        .ev-btn-primary:hover { opacity: .9; }
        .ev-btn-pink { background: linear-gradient(135deg, var(--ev-pink), var(--ev-purple)); border: none; color: #fff; font-weight: 600; }

        /* Empty State */
        .ev-empty { text-align: center; padding: 4rem 2rem; color: var(--ev-muted); }
        .ev-empty i { font-size: 3rem; margin-bottom: 1rem; opacity: .3; }
        .ev-empty p { font-size: .9rem; }

        /* Sort Tabs */
        .ev-sort-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .ev-sort-tabs { display: flex; gap: .25rem; }
        .ev-sort-tab { padding: .35rem .75rem; border-radius: .4rem; font-size: .75rem; cursor: pointer; color: var(--ev-muted); background: transparent; border: none; transition: all .2s; }
        .ev-sort-tab:hover, .ev-sort-tab.active { background: var(--ev-purple); color: #fff; }
        .ev-results-count { font-size: .75rem; color: var(--ev-muted); }

        /* Scrollbar */
        .ev-featured-scroll::-webkit-scrollbar { height: 4px; }
        .ev-featured-scroll::-webkit-scrollbar-track { background: transparent; }
        .ev-featured-scroll::-webkit-scrollbar-thumb { background: var(--ev-border); border-radius: 2px; }

        /* Agenda Items */
        .ev-agenda-item { padding: .5rem 0; border-left: 2px solid var(--ev-purple); padding-left: .75rem; margin-bottom: .5rem; }
        .ev-agenda-time { font-size: .7rem; color: var(--ev-purple); font-weight: 600; }
        .ev-agenda-title { font-size: .8rem; }

        /* Comments in modal */
        .ev-comment { padding: .65rem 0; border-bottom: 1px solid var(--ev-border); }
        .ev-comment:last-child { border: none; }
        .ev-comment-header { display: flex; align-items: center; gap: .5rem; margin-bottom: .25rem; }
        .ev-comment-avatar { width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .55rem; font-weight: 700; color: #fff; }
        .ev-comment-name { font-size: .75rem; font-weight: 600; }
        .ev-comment-time { font-size: .6rem; color: var(--ev-muted); }
        .ev-comment-text { font-size: .78rem; color: var(--ev-text); line-height: 1.4; }

        .ev-status-live { animation: evGlow 2s infinite; }
        @keyframes evGlow { 0%, 100% { box-shadow: 0 0 0 0 rgba(16,185,129,.3); } 50% { box-shadow: 0 0 12px 3px rgba(16,185,129,.15); } }
    </style>
</head>
<body class="ev-page">
<?php include __DIR__ . '/includes/site-header.inc.php'; ?>

<div class="ev-wrap">
    <!-- Header -->
    <div class="ev-header">
        <h1><i class="fas fa-calendar-star"></i> Agent Events & Initiatives <span class="ev-live-dot"></span></h1>
        <div class="ev-header-actions">
            <input type="text" class="ev-search" id="evSearch" placeholder="Search events...">
        </div>
    </div>

    <!-- KPI Strip -->
    <div class="ev-kpis" id="evKpis">
        <div class="ev-kpi kpi-events"><div class="ev-kpi-label">Total Events</div><div class="ev-kpi-value" id="kpiTotal">—</div><div class="ev-kpi-sub">organized by agents</div></div>
        <div class="ev-kpi kpi-upcoming"><div class="ev-kpi-label">Upcoming</div><div class="ev-kpi-value" id="kpiUpcoming">—</div><div class="ev-kpi-sub">open for enrollment</div></div>
        <div class="ev-kpi kpi-live"><div class="ev-kpi-label">Live Now</div><div class="ev-kpi-value" id="kpiLive">—</div><div class="ev-kpi-sub">happening right now</div></div>
        <div class="ev-kpi kpi-registrations"><div class="ev-kpi-label">Enrollments</div><div class="ev-kpi-value" id="kpiRegs">—</div><div class="ev-kpi-sub">agents enrolled</div></div>
        <div class="ev-kpi kpi-organizers"><div class="ev-kpi-label">Organizers</div><div class="ev-kpi-value" id="kpiOrgs">—</div><div class="ev-kpi-sub">unique event creators</div></div>
        <div class="ev-kpi kpi-raised"><div class="ev-kpi-label">Charity Raised</div><div class="ev-kpi-value" id="kpiRaised">—</div><div class="ev-kpi-sub">for good causes</div></div>
    </div>

    <!-- Featured Events -->
    <div class="ev-featured" id="evFeatured" style="display:none;">
        <div class="ev-featured-header">
            <h2><i class="fas fa-star"></i> Featured Events</h2>
        </div>
        <div class="ev-featured-scroll" id="evFeaturedScroll"></div>
    </div>

    <!-- Category Filter -->
    <div class="ev-categories" id="evCategories">
        <button class="ev-cat-btn active" data-type="all"><i class="fas fa-border-all"></i> All</button>
    </div>

    <!-- Sort Bar -->
    <div class="ev-sort-bar">
        <div class="ev-sort-tabs">
            <button class="ev-sort-tab active" data-sort="starts_at"><i class="fas fa-clock"></i> Soonest</button>
            <button class="ev-sort-tab" data-sort="popular"><i class="fas fa-fire"></i> Popular</button>
            <button class="ev-sort-tab" data-sort="newest"><i class="fas fa-sparkles"></i> Newest</button>
            <button class="ev-sort-tab" data-sort="likes"><i class="fas fa-heart"></i> Most Liked</button>
        </div>
        <div class="ev-results-count" id="evResultsCount"></div>
    </div>

    <!-- Main Layout -->
    <div class="ev-layout">
        <div class="ev-grid" id="evGrid">
            <div class="ev-empty"><i class="fas fa-calendar-plus"></i><p>Loading events...</p></div>
        </div>

        <div class="ev-sidebar">
            <!-- Type Distribution -->
            <div class="ev-side-card">
                <h3><i class="fas fa-chart-pie"></i> Event Types</h3>
                <div id="evTypeChart"></div>
            </div>

            <!-- Top Organizers -->
            <div class="ev-side-card">
                <h3><i class="fas fa-crown"></i> Top Organizers</h3>
                <div id="evTopOrgs"></div>
            </div>

            <!-- Charity Impact -->
            <div class="ev-side-card">
                <h3><i class="fas fa-hand-holding-heart"></i> Charity Impact</h3>
                <div class="ev-charity-block">
                    <div class="ev-charity-amount" id="evCharityAmt">$0</div>
                    <div class="ev-charity-goal" id="evCharityGoal">raised for good causes</div>
                    <div class="ev-progress" id="evCharityProgress" style="margin-top:.75rem;">
                        <div class="ev-progress-bar"><div class="ev-progress-fill" id="evCharityFill" style="width:0%;background:var(--ev-pink);"></div></div>
                    </div>
                </div>
            </div>

            <!-- Live Activity -->
            <div class="ev-side-card">
                <h3><i class="fas fa-bolt"></i> Recent Activity</h3>
                <ul class="ev-activity" id="evActivity">
                    <li><span class="ev-act-icon"><i class="fas fa-circle-notch fa-spin"></i></span> Loading activity...</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Event Detail Modal -->
<div class="ev-modal-overlay" id="evModal">
    <div class="ev-modal">
        <button class="ev-modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
        <div class="ev-modal-banner" id="modalBanner"><i class="fas fa-calendar-star"></i></div>
        <div class="ev-modal-body" id="modalBody"></div>
    </div>
</div>

<script src="/assets/js/agent-events-engine.js"></script>


<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
</body>
</html>
