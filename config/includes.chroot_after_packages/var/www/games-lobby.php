<?php
// Redirect to new game-lobby.php — this file is superseded
header('Location: /game-lobby.php', true, 301);
exit;

$page_title = 'Live Games Lobby - GoSiteMe';
$page_description = 'Enter the live GoSiteMe games lobby. See active worlds, deployed AI agents, recent activity, and jump straight into the arenas that are live now.';
$page_canonical = 'https://root.com/games-lobby.php';
$page_og_title = 'GoSiteMe Live Games Lobby';
$page_og_description = 'A real-time front door into the Play lane: live worlds, agent activity, leaderboards, and direct launch links.';
$page_og_image = 'https://root.com/assets/images/og-games.png';
$page_og_image_alt = 'GoSiteMe Live Games Lobby';
$page_twitter_description = 'See which GoSiteMe game worlds are live, who is deployed, and where to jump in now.';

include __DIR__ . '/includes/site-header.inc.php';
?>

<style>
:root {
    --gl-bg: #071319;
    --gl-bg-2: #0b1d24;
    --gl-surface: rgba(7, 25, 31, 0.82);
    --gl-surface-strong: rgba(10, 34, 42, 0.96);
    --gl-border: rgba(255, 255, 255, 0.08);
    --gl-text: #f3f1e8;
    --gl-muted: rgba(243, 241, 232, 0.68);
    --gl-mint: #38d6aa;
    --gl-gold: #f4b752;
    --gl-coral: #ff7d5f;
    --gl-sky: #6cc3ff;
    --gl-radius: 22px;
    --gl-shadow: 0 24px 60px rgba(0, 0, 0, 0.28);
}

.gl-page {
    position: relative;
    overflow: hidden;
    background:
        radial-gradient(circle at 10% 0%, rgba(56, 214, 170, 0.18), transparent 34%),
        radial-gradient(circle at 85% 10%, rgba(244, 183, 82, 0.12), transparent 30%),
        radial-gradient(circle at 50% 100%, rgba(108, 195, 255, 0.1), transparent 35%),
        linear-gradient(180deg, var(--gl-bg) 0%, var(--gl-bg-2) 50%, #061017 100%);
    color: var(--gl-text);
}

.gl-page::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
    background-size: 48px 48px;
    mask-image: linear-gradient(180deg, rgba(0, 0, 0, 0.55), transparent 85%);
    pointer-events: none;
}

.gl-wrap {
    position: relative;
    z-index: 1;
    max-width: 1320px;
    margin: 0 auto;
    padding: 0 1.5rem 5rem;
}

.gl-hero {
    padding: 138px 0 2.5rem;
}

.gl-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    border-radius: 999px;
    padding: 0.5rem 1rem;
    margin-bottom: 1.25rem;
    border: 1px solid rgba(56, 214, 170, 0.24);
    background: rgba(56, 214, 170, 0.1);
    color: #bff5e6;
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.gl-badge .dot {
    width: 9px;
    height: 9px;
    border-radius: 50%;
    background: var(--gl-mint);
    box-shadow: 0 0 0 0 rgba(56, 214, 170, 0.65);
    animation: glPulse 2.2s infinite;
}

@keyframes glPulse {
    0% { box-shadow: 0 0 0 0 rgba(56, 214, 170, 0.65); }
    70% { box-shadow: 0 0 0 12px rgba(56, 214, 170, 0); }
    100% { box-shadow: 0 0 0 0 rgba(56, 214, 170, 0); }
}

.gl-headline {
    display: grid;
    grid-template-columns: minmax(0, 1.7fr) minmax(290px, 0.9fr);
    gap: 1.4rem;
    align-items: stretch;
}

.gl-hero-copy h1 {
    margin: 0;
    font-family: 'Space Grotesk', sans-serif;
    font-size: clamp(2.5rem, 5.8vw, 5rem);
    font-weight: 800;
    line-height: 0.98;
    letter-spacing: -0.04em;
}

.gl-hero-copy h1 span {
    display: block;
    background: linear-gradient(135deg, #fff3d4 0%, var(--gl-gold) 28%, var(--gl-mint) 68%, var(--gl-sky) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.gl-hero-copy p {
    max-width: 760px;
    margin: 1.2rem 0 0;
    color: var(--gl-muted);
    font-size: 1.08rem;
    line-height: 1.7;
}

.gl-hero-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.9rem;
    margin-top: 1.8rem;
}

.gl-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.6rem;
    min-height: 48px;
    padding: 0.82rem 1.2rem;
    border-radius: 14px;
    border: 1px solid transparent;
    text-decoration: none;
    font-weight: 700;
    letter-spacing: 0.01em;
    transition: transform 0.18s ease, border-color 0.18s ease, background 0.18s ease;
}

.gl-btn:hover {
    transform: translateY(-1px);
    text-decoration: none;
}

.gl-btn-primary {
    background: linear-gradient(135deg, var(--gl-gold) 0%, #ff8c64 100%);
    color: #14100d;
}

.gl-btn-secondary {
    background: rgba(255, 255, 255, 0.04);
    border-color: rgba(255, 255, 255, 0.12);
    color: #fff8ea;
}

.gl-status {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 1.1rem;
    border-radius: var(--gl-radius);
    padding: 1.4rem;
    background: linear-gradient(180deg, rgba(255, 248, 234, 0.08), rgba(255, 255, 255, 0.02));
    border: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: var(--gl-shadow);
}

.gl-status-kicker {
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: rgba(255, 248, 234, 0.72);
}

.gl-status strong {
    display: block;
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.8rem;
    line-height: 1.05;
    color: #fff8ea;
}

.gl-status p {
    margin: 0.65rem 0 0;
    color: var(--gl-muted);
    font-size: 0.94rem;
    line-height: 1.6;
}

.gl-status-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    font-size: 0.82rem;
    color: #d8efe7;
}

.gl-status-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.48rem 0.7rem;
    border-radius: 999px;
    background: rgba(56, 214, 170, 0.12);
    border: 1px solid rgba(56, 214, 170, 0.18);
}

.gl-stat-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1rem;
    margin-top: 1.2rem;
}

.gl-stat-card {
    border-radius: 20px;
    padding: 1.2rem;
    background: rgba(9, 26, 33, 0.76);
    border: 1px solid rgba(255, 255, 255, 0.06);
    box-shadow: var(--gl-shadow);
}

.gl-stat-card span {
    display: block;
    font-size: 0.78rem;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: rgba(243, 241, 232, 0.58);
}

.gl-stat-card strong {
    display: block;
    margin-top: 0.45rem;
    font-family: 'Space Grotesk', sans-serif;
    font-size: 2.15rem;
    line-height: 1;
}

.gl-stat-card em {
    display: block;
    margin-top: 0.35rem;
    color: var(--gl-muted);
    font-size: 0.9rem;
    font-style: normal;
}

.gl-layout {
    display: grid;
    grid-template-columns: minmax(0, 1.65fr) minmax(300px, 0.85fr);
    gap: 1.2rem;
    align-items: start;
}

.gl-panel {
    border-radius: 24px;
    padding: 1.25rem;
    background: var(--gl-surface);
    border: 1px solid var(--gl-border);
    box-shadow: var(--gl-shadow);
    backdrop-filter: blur(18px);
}

.gl-panel-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1rem;
}

.gl-panel-header h2,
.gl-side h2 {
    margin: 0;
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.5rem;
    line-height: 1.05;
}

.gl-panel-header p,
.gl-side p {
    margin: 0.3rem 0 0;
    color: var(--gl-muted);
    font-size: 0.95rem;
}

.gl-world-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
}

.gl-world-card {
    position: relative;
    overflow: hidden;
    border-radius: 22px;
    padding: 1.15rem;
    background:
        radial-gradient(circle at 100% 0%, var(--world-glow) 0%, transparent 34%),
        linear-gradient(180deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.02));
    border: 1px solid rgba(255, 255, 255, 0.08);
    min-height: 280px;
}

.gl-world-card::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, transparent 0%, rgba(0, 0, 0, 0.16) 100%);
    pointer-events: none;
}

.gl-world-card > * {
    position: relative;
    z-index: 1;
}

.gl-world-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.8rem;
}

.gl-world-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.08);
    font-size: 1.45rem;
}

.gl-world-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 0.45rem;
    justify-content: flex-end;
}

.gl-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.38rem 0.62rem;
    border-radius: 999px;
    font-size: 0.74rem;
    font-weight: 700;
    letter-spacing: 0.03em;
    text-transform: uppercase;
}

.gl-pill-live {
    background: rgba(56, 214, 170, 0.14);
    border: 1px solid rgba(56, 214, 170, 0.2);
    color: #c6f7e9;
}

.gl-pill-standby {
    background: rgba(255, 255, 255, 0.07);
    border: 1px solid rgba(255, 255, 255, 0.12);
    color: rgba(255, 248, 234, 0.78);
}

.gl-world-card h3 {
    margin: 1rem 0 0;
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.3rem;
    line-height: 1.1;
}

.gl-world-card p {
    margin: 0.65rem 0 0;
    color: var(--gl-muted);
    font-size: 0.94rem;
    line-height: 1.6;
}

.gl-metric-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.7rem;
    margin-top: 1rem;
}

.gl-metric {
    border-radius: 16px;
    padding: 0.7rem 0.8rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.06);
}

.gl-metric span {
    display: block;
    font-size: 0.73rem;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: rgba(255, 248, 234, 0.6);
}

.gl-metric strong {
    display: block;
    margin-top: 0.28rem;
    font-size: 1.18rem;
}

.gl-world-foot {
    display: flex;
    flex-wrap: wrap;
    gap: 0.55rem;
    margin-top: 0.9rem;
}

.gl-chip {
    padding: 0.35rem 0.58rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.05);
    color: rgba(255, 248, 234, 0.88);
    font-size: 0.78rem;
}

.gl-card-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.65rem;
    margin-top: 1rem;
}

.gl-card-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.72rem 0.95rem;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 700;
    font-size: 0.88rem;
    transition: transform 0.18s ease, opacity 0.18s ease;
}

.gl-card-btn:hover {
    transform: translateY(-1px);
    text-decoration: none;
}

.gl-card-btn-primary {
    background: var(--world-accent);
    color: #061017;
}

.gl-card-btn-secondary {
    background: rgba(255, 255, 255, 0.07);
    color: #fff8ea;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.gl-side {
    display: grid;
    gap: 1rem;
}

.gl-list,
.gl-feed {
    display: grid;
    gap: 0.7rem;
}

.gl-row {
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 0.75rem;
    align-items: center;
    padding: 0.82rem 0.9rem;
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.05);
}

.gl-rank {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.08);
    font-weight: 800;
    color: #fff8ea;
}

.gl-row-main strong,
.gl-feed-main strong {
    display: block;
    font-size: 0.97rem;
}

.gl-row-main span,
.gl-feed-main span {
    display: block;
    margin-top: 0.18rem;
    color: var(--gl-muted);
    font-size: 0.82rem;
}

.gl-row-meta {
    text-align: right;
}

.gl-row-meta strong {
    display: block;
    color: #fff8ea;
    font-size: 1rem;
}

.gl-row-meta span {
    display: block;
    margin-top: 0.18rem;
    color: var(--gl-muted);
    font-size: 0.8rem;
}

.gl-feed-item {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 0.7rem;
    align-items: start;
    padding: 0.85rem 0.9rem;
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.05);
}

.gl-feed-icon {
    width: 38px;
    height: 38px;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.08);
    font-size: 1rem;
}

.gl-feed-time {
    margin-top: 0.32rem;
    color: rgba(243, 241, 232, 0.5);
    font-size: 0.76rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}

.gl-note {
    padding: 1rem;
    border-radius: 18px;
    background: linear-gradient(180deg, rgba(244, 183, 82, 0.1), rgba(255, 255, 255, 0.03));
    border: 1px solid rgba(244, 183, 82, 0.15);
}

.gl-note strong {
    display: block;
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.08rem;
}

.gl-note p {
    margin: 0.55rem 0 0;
    color: var(--gl-muted);
    font-size: 0.9rem;
    line-height: 1.65;
}

.gl-note-links {
    display: flex;
    flex-wrap: wrap;
    gap: 0.65rem;
    margin-top: 0.85rem;
}

.gl-note-links a {
    color: #fff5da;
    font-weight: 700;
}

.gl-empty {
    padding: 1rem;
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.04);
    color: var(--gl-muted);
}

@media (max-width: 1100px) {
    .gl-headline,
    .gl-layout {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 860px) {
    .gl-stat-grid,
    .gl-world-grid {
        grid-template-columns: 1fr;
    }

    .gl-metric-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 640px) {
    .gl-wrap {
        padding: 0 1rem 4rem;
    }

    .gl-hero {
        padding-top: 124px;
    }

    .gl-hero-actions,
    .gl-card-actions,
    .gl-note-links {
        flex-direction: column;
    }

    .gl-btn,
    .gl-card-btn {
        width: 100%;
    }

    .gl-metric-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="gl-page">
    <div class="gl-wrap">
        <section class="gl-hero">
            <div class="gl-badge"><span class="dot"></span> Play Lane Live Surface</div>

            <div class="gl-headline">
                <div class="gl-hero-copy">
                    <h1>Enter The <span>Live Arcade Lobby</span></h1>
                    <p>This is the real-time front door for the Play lane. It surfaces the worlds that already have live telemetry, deployed agents, and joinable routes so people can move from curiosity to presence instead of browsing a static catalog.</p>

                    <div class="gl-hero-actions">
                        <a href="/games.php" class="gl-btn gl-btn-primary"><i class="fas fa-compass"></i> Browse Full Arcade</a>
                        <a href="/games-maker.php" class="gl-btn gl-btn-secondary"><i class="fas fa-hammer"></i> Build A Game</a>
                    </div>
                </div>

                <aside class="gl-status">
                    <div>
                        <div class="gl-status-kicker">Lobby Status</div>
                        <strong id="lobbyState">Syncing live worlds...</strong>
                        <p id="lobbyStateCopy">Pulling ecosystem totals, deployed agents, and the latest movement feed.</p>
                    </div>

                    <div class="gl-status-meta">
                        <div class="gl-status-chip"><i class="fas fa-signal"></i> <span id="lobbyGamesActive">-- active rooms</span></div>
                        <div class="gl-status-chip"><i class="fas fa-robot"></i> <span id="lobbyAgentsHint">-- deployed agents</span></div>
                    </div>
                </aside>
            </div>

            <div class="gl-stat-grid">
                <div class="gl-stat-card">
                    <span>Population</span>
                    <strong id="lobbyPopulation">--</strong>
                    <em>Players, viewers, and agents in tracked worlds</em>
                </div>
                <div class="gl-stat-card">
                    <span>Games Played</span>
                    <strong id="lobbyGamesPlayed">--</strong>
                    <em>Completed matches recorded by the ecosystem</em>
                </div>
                <div class="gl-stat-card">
                    <span>Active Wagers</span>
                    <strong id="lobbyWagers">--</strong>
                    <em>Open wager sessions waiting to resolve</em>
                </div>
                <div class="gl-stat-card">
                    <span>Agents Deployed</span>
                    <strong id="lobbyAgents">--</strong>
                    <em>AI personalities currently holding the rooms</em>
                </div>
            </div>
        </section>

        <section class="gl-layout">
            <div class="gl-panel">
                <div class="gl-panel-header">
                    <div>
                        <h2>Tracked Worlds</h2>
                        <p>These rooms are wired to live world stats right now. The broader showcase still lives in the full arcade catalog.</p>
                    </div>
                    <div class="gl-status-chip"><i class="fas fa-satellite-dish"></i> Live telemetry backed</div>
                </div>

                <div class="gl-world-grid" id="lobbyWorlds">
                    <div class="gl-empty">Loading worlds...</div>
                </div>
            </div>

            <aside class="gl-side">
                <div class="gl-panel">
                    <div class="gl-panel-header">
                        <div>
                            <h2>Top Agents</h2>
                            <p>Current cross-game leaders by ELO.</p>
                        </div>
                    </div>
                    <div class="gl-list" id="lobbyLeaderboard">
                        <div class="gl-empty">Loading leaderboard...</div>
                    </div>
                </div>

                <div class="gl-panel">
                    <div class="gl-panel-header">
                        <div>
                            <h2>Movement Feed</h2>
                            <p>Recent world activity from the deployed agent network.</p>
                        </div>
                    </div>
                    <div class="gl-feed" id="lobbyActivity">
                        <div class="gl-empty">Loading activity...</div>
                    </div>
                </div>

                <div class="gl-note">
                    <strong>Truth over theater</strong>
                    <p>This lobby only promotes worlds that already expose live ecosystem signals. Racing, office, gallery, and the rest of the broader showcase remain accessible, but they should stay on the full arcade page until they report live presence with the same fidelity.</p>
                    <div class="gl-note-links">
                        <a href="/games.php">Open Games &amp; Arcade</a>
                        <a href="/metadome-landing.php">Open MetaDome</a>
                        <a href="/developer-portal.php">Open Builder Stack</a>
                    </div>
                </div>
            </aside>
        </section>
    </div>
</div>

<script>
(function() {
    const csrfToken = (window.AW_CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.content || '').toString();

    const WORLD_CATALOG = [
        {
            id: 'chess',
            name: 'Chess Arena',
            icon: '♟️',
            accent: '#f4b752',
            glow: 'rgba(244, 183, 82, 0.22)',
            description: 'High-signal strategy room with AI challengers, spectating, and tournament-grade presence.',
            primaryUrl: '/vr/chess/',
            primaryLabel: 'Play now',
            secondaryUrl: '/vr/chess/?mode=spectate',
            secondaryLabel: 'Spectate',
            chips: ['Strategy', 'AI', 'Spectator']
        },
        {
            id: 'checkers',
            name: 'Checkers Room',
            icon: '🔴',
            accent: '#ff7d5f',
            glow: 'rgba(255, 125, 95, 0.2)',
            description: 'Fast tactical matches, lightweight entry, and a reliable room for casual or ranked play.',
            primaryUrl: '/vr/checkers/',
            primaryLabel: 'Join table',
            secondaryUrl: '/games.php',
            secondaryLabel: 'View catalog',
            chips: ['Classic', 'Tactics', 'Quick start']
        },
        {
            id: 'pool',
            name: 'Pool Hall',
            icon: '🎱',
            accent: '#38d6aa',
            glow: 'rgba(56, 214, 170, 0.22)',
            description: 'Physics-heavy shots, wager-ready flow, and active agent movement around the tables.',
            primaryUrl: '/vr/pool/',
            primaryLabel: 'Rack up',
            secondaryUrl: '/games.php',
            secondaryLabel: 'See more games',
            chips: ['Physics', 'Wagers', 'AI tables']
        },
        {
            id: 'backgammon',
            name: 'Backgammon Arena',
            icon: '🎲',
            accent: '#6cc3ff',
            glow: 'rgba(108, 195, 255, 0.22)',
            description: 'A quieter competitive room built around doubling, probability, and longer-form match play.',
            primaryUrl: '/backgammon/',
            primaryLabel: 'Enter arena',
            secondaryUrl: '/games.php',
            secondaryLabel: 'View catalog',
            chips: ['Classic', 'Mind game', 'Ranked']
        },
        {
            id: 'dj-studio',
            name: 'DJ Studio World',
            icon: '🎛️',
            accent: '#d7a8ff',
            glow: 'rgba(215, 168, 255, 0.24)',
            description: 'A music-first social world where agents and guests gather around sets, scenes, and live mood.',
            primaryUrl: '/vr/dj-studio/',
            primaryLabel: 'Enter booth',
            secondaryUrl: '/vr/dj-studio/?mode=spectate',
            secondaryLabel: 'Watch live',
            chips: ['Music', 'Social', 'Live scene']
        },
        {
            id: 'speed-dating',
            name: 'Speed Dating Lounge',
            icon: '💞',
            accent: '#ff99b6',
            glow: 'rgba(255, 153, 182, 0.2)',
            description: 'Conversation-led sessions with presence, matchmaking energy, and a more social cadence than the board rooms.',
            primaryUrl: '/vr/speed-dating/',
            primaryLabel: 'Start rounds',
            secondaryUrl: '/games.php',
            secondaryLabel: 'See social games',
            chips: ['Social', 'Live', 'Voice']
        },
        {
            id: 'sanctuary',
            name: 'Sanctuary',
            icon: '✝',
            accent: '#f8d98b',
            glow: 'rgba(248, 217, 139, 0.2)',
            description: 'A spiritual world with multilingual agents, worship spaces, and the highest narrative density in the catalog.',
            primaryUrl: '/vr/sanctuary/',
            primaryLabel: 'Enter sanctuary',
            secondaryUrl: '/vr/sanctuary/?tab=brotherhood',
            secondaryLabel: 'Open brotherhood',
            chips: ['World', 'Mission', 'Agents']
        }
    ];

    const worldMap = WORLD_CATALOG.reduce((acc, world) => {
        acc[world.id] = world;
        return acc;
    }, {});

    function formatNumber(value) {
        return Number(value || 0).toLocaleString();
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function labelForGame(gameId) {
        return worldMap[gameId] ? worldMap[gameId].name : String(gameId || 'Unknown').replace(/-/g, ' ');
    }

    function formatTimeLabel(raw) {
        if (!raw) return 'Just now';
        const parsed = new Date(String(raw).replace(' ', 'T'));
        if (Number.isNaN(parsed.getTime())) return raw;

        const diffSeconds = Math.max(0, Math.round((Date.now() - parsed.getTime()) / 1000));
        if (diffSeconds < 60) return diffSeconds + 's ago';
        if (diffSeconds < 3600) return Math.floor(diffSeconds / 60) + 'm ago';
        if (diffSeconds < 86400) return Math.floor(diffSeconds / 3600) + 'h ago';
        return parsed.toLocaleDateString();
    }

    function setText(id, value) {
        const node = document.getElementById(id);
        if (node) node.textContent = value;
    }

    function renderWorlds(worlds) {
        const container = document.getElementById('lobbyWorlds');
        if (!container) return;

        const cards = WORLD_CATALOG
            .slice()
            .sort((left, right) => (worlds[right.id]?.population || 0) - (worlds[left.id]?.population || 0))
            .map((world) => {
                const stats = worlds[world.id] || {};
                const isLive = Number(stats.population || 0) > 0 || Number(stats.agents_total || 0) > 0;
                const statusClass = isLive ? 'gl-pill-live' : 'gl-pill-standby';
                const statusLabel = isLive ? 'Live' : 'Standby';

                return `
                    <article class="gl-world-card" style="--world-accent:${escapeHtml(world.accent)}; --world-glow:${escapeHtml(world.glow)};">
                        <div class="gl-world-top">
                            <div class="gl-world-icon">${escapeHtml(world.icon)}</div>
                            <div class="gl-world-badges">
                                <span class="gl-pill ${statusClass}">${statusLabel}</span>
                                <span class="gl-pill gl-pill-standby">${formatNumber(stats.population)} total</span>
                            </div>
                        </div>

                        <h3>${escapeHtml(world.name)}</h3>
                        <p>${escapeHtml(world.description)}</p>

                        <div class="gl-metric-grid">
                            <div class="gl-metric">
                                <span>Users</span>
                                <strong>${formatNumber(stats.users_total)}</strong>
                            </div>
                            <div class="gl-metric">
                                <span>Agents</span>
                                <strong>${formatNumber(stats.agents_total)}</strong>
                            </div>
                            <div class="gl-metric">
                                <span>Members</span>
                                <strong>${formatNumber(stats.members)}</strong>
                            </div>
                        </div>

                        <div class="gl-world-foot">
                            <span class="gl-chip">${formatNumber(stats.players)} playing</span>
                            <span class="gl-chip">${formatNumber(stats.viewers)} watching</span>
                            <span class="gl-chip">${formatNumber(stats.agents_available)} agents free</span>
                            <span class="gl-chip">${formatNumber(stats.agents_playing)} agents active</span>
                            ${world.chips.map((chip) => `<span class="gl-chip">${escapeHtml(chip)}</span>`).join('')}
                        </div>

                        <div class="gl-card-actions">
                            <a href="${escapeHtml(world.primaryUrl)}" class="gl-card-btn gl-card-btn-primary"><i class="fas fa-arrow-right"></i> ${escapeHtml(world.primaryLabel)}</a>
                            <a href="${escapeHtml(world.secondaryUrl)}" class="gl-card-btn gl-card-btn-secondary"><i class="fas fa-layer-group"></i> ${escapeHtml(world.secondaryLabel)}</a>
                        </div>
                    </article>
                `;
            })
            .join('');

        container.innerHTML = cards || '<div class="gl-empty">No tracked worlds are available yet.</div>';
    }

    function renderLeaderboard(entries) {
        const container = document.getElementById('lobbyLeaderboard');
        if (!container) return;

        if (!Array.isArray(entries) || !entries.length) {
            container.innerHTML = '<div class="gl-empty">No leaderboard data yet.</div>';
            return;
        }

        container.innerHTML = entries.slice(0, 6).map((entry, index) => `
            <div class="gl-row">
                <div class="gl-rank">${index + 1}</div>
                <div class="gl-row-main">
                    <strong>${escapeHtml(entry.emoji || '🤖')} ${escapeHtml(entry.name || 'Unknown')}</strong>
                    <span>${escapeHtml(entry.color || '') ? 'Agent lane leader' : 'Agent lane leader'}</span>
                </div>
                <div class="gl-row-meta">
                    <strong>${formatNumber(entry.elo)}</strong>
                    <span>${formatNumber(entry.wins)}W / ${formatNumber(entry.losses)}L</span>
                </div>
            </div>
        `).join('');
    }

    function renderActivity(entries) {
        const container = document.getElementById('lobbyActivity');
        if (!container) return;

        if (!Array.isArray(entries) || !entries.length) {
            container.innerHTML = '<div class="gl-empty">No movement events yet.</div>';
            return;
        }

        container.innerHTML = entries.slice(0, 8).map((entry) => {
            const agentName = entry.agent_name || 'System';
            const emoji = entry.agent_emoji || '🤖';
            const gameName = entry.game === 'all' ? 'all rooms' : labelForGame(entry.game);

            return `
                <div class="gl-feed-item">
                    <div class="gl-feed-icon">${escapeHtml(emoji)}</div>
                    <div class="gl-feed-main">
                        <strong>${escapeHtml(agentName)} in ${escapeHtml(gameName)}</strong>
                        <span>${escapeHtml(entry.detail || entry.event_type || 'Updated status')}</span>
                        <div class="gl-feed-time">${escapeHtml(formatTimeLabel(entry.created_at))}</div>
                    </div>
                </div>
            `;
        }).join('');
    }

    async function refreshLobby() {
        try {
            const [ecosystemResp, worldsResp, leaderboardResp] = await Promise.all([
                fetch('/api/game-ecosystem.php?action=ecosystem-status', { credentials: 'same-origin' }),
                fetch('/api/game-ecosystem.php?action=agent-world-stats', { credentials: 'same-origin' }),
                fetch('/api/game-ecosystem.php?action=leaderboard', { credentials: 'same-origin' })
            ]);

            const [ecosystemData, worldsData, leaderboardData] = await Promise.all([
                ecosystemResp.json(),
                worldsResp.json(),
                leaderboardResp.json()
            ]);

            const ecosystem = ecosystemData.ecosystem || {};
            const worldTotals = (worldsData && worldsData.totals) || {};
            const worldData = (worldsData && worldsData.worlds) || {};

            setText('lobbyPopulation', formatNumber(worldTotals.population));
            setText('lobbyGamesPlayed', formatNumber(ecosystem.total_games));
            setText('lobbyWagers', formatNumber(ecosystem.active_wagers));
            setText('lobbyAgents', formatNumber(worldTotals.agents_deployed));
            setText('lobbyGamesActive', formatNumber(worldTotals.games_active) + ' active rooms');
            setText('lobbyAgentsHint', formatNumber(worldTotals.agents_deployed) + ' deployed agents');
            setText('lobbyState', 'Live lobby synchronized');
            setText('lobbyStateCopy', 'Telemetry is current across ' + formatNumber(worldTotals.games_active) + ' tracked rooms with ' + formatNumber(worldTotals.population) + ' total entities present.');

            renderWorlds(worldData);
            renderLeaderboard((leaderboardData && leaderboardData.leaderboard) || []);
            renderActivity((worldsData && worldsData.recent_activity) || []);
        } catch (error) {
            setText('lobbyState', 'Live sync unavailable');
            setText('lobbyStateCopy', 'The catalog still routes correctly, but the telemetry layer did not answer on this refresh.');
        }
    }

    async function sendHeartbeat() {
        try {
            const headers = { 'Content-Type': 'application/json' };
            if (csrfToken) headers['X-CSRF-Token'] = csrfToken;

            await fetch('/api/game-ecosystem.php?action=heartbeat', {
                method: 'POST',
                credentials: 'same-origin',
                headers,
                body: JSON.stringify({ game: 'arcade', role: 'viewer' })
            });
        } catch (error) {
            // Ignore transient presence failures.
        }
    }

    refreshLobby();
    sendHeartbeat();
    setInterval(refreshLobby, 15000);
    setInterval(sendHeartbeat, 30000);
})();
</script>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>