<?php
$page_title = 'Games & Arcade — GoSiteMe';
$page_description = 'Play chess, racing, trivia and more in GoSiteMe\'s immersive game arcade. 3D WebXR games, AI opponents, multiplayer PvP, voice games, and a thriving developer community.';
$page_canonical = 'https://gositeme.com/games.php';
$page_og_title = 'GoSiteMe Games & Arcade';
$page_og_description = 'Play immersive 3D games with AI opponents. Chess, racing, trivia, and more — powered by WebXR.';
$page_og_image = 'https://gositeme.com/assets/images/og-games.png';
$page_og_image_alt = 'GoSiteMe Games & Arcade';
$page_twitter_description = 'Play chess, racing, trivia and more in GoSiteMe\'s immersive 3D game arcade — with AI opponents and multiplayer.';

include __DIR__ . '/includes/site-header.inc.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$is_logged_in = !empty($_SESSION['logged_in']) && !empty($_SESSION['client_id']);
?>

<style>
/* ═══════════════════════════════════════
   GAMES & ARCADE PAGE
   ═══════════════════════════════════════ */
:root {
    --gm-bg: #0a0a14;
    --gm-surface: #12121e;
    --gm-surface-2: #1a1a2e;
    --gm-border: rgba(255,255,255,0.08);
    --gm-accent: #7D00FF;
    --gm-accent-2: #00D4FF;
    --gm-text: #e8e8f0;
    --gm-text-muted: #8a8a9a;
    --gm-radius: 16px;
}

/* ── Hero ── */
.gm-hero {
    position: relative;
    padding: 140px 2rem 4rem;
    text-align: center;
    overflow: hidden;
    background: radial-gradient(ellipse at 30% 0%, rgba(125,0,255,0.15) 0%, transparent 60%),
                radial-gradient(ellipse at 70% 100%, rgba(0,212,255,0.1) 0%, transparent 60%),
                var(--gm-bg);
}
.gm-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%239C92AC' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    opacity: 0.4;
}
.gm-hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(125,0,255,0.15);
    border: 1px solid rgba(125,0,255,0.3);
    color: #c4b5fd;
    padding: 6px 18px;
    border-radius: 999px;
    font-size: 0.8rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    position: relative;
}
.gm-hero h1 {
    font-size: clamp(2rem, 5vw, 3.5rem);
    font-weight: 800;
    line-height: 1.15;
    margin-bottom: 1rem;
    position: relative;
}
.gm-hero h1 span {
    background: linear-gradient(135deg, #7D00FF, #00D4FF);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.gm-hero .tagline {
    color: var(--gm-text-muted);
    font-size: 1.1rem;
    max-width: 600px;
    margin: 0 auto 2rem;
    line-height: 1.6;
    position: relative;
}

/* ── Stats Row ── */
.gm-stats {
    display: flex;
    justify-content: center;
    gap: 3rem;
    flex-wrap: wrap;
    margin-top: 2rem;
    position: relative;
}
.gm-stat {
    text-align: center;
}
.gm-stat-val {
    font-size: 2rem;
    font-weight: 800;
    background: linear-gradient(135deg, #7D00FF, #00D4FF);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.gm-stat-label {
    font-size: 0.75rem;
    color: var(--gm-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-top: 4px;
}

/* ── Section Layout ── */
.gm-content {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 1.5rem 4rem;
}
.gm-section {
    margin-bottom: 4rem;
}
.gm-section-header {
    text-align: center;
    margin-bottom: 2.5rem;
}
.gm-section-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--gm-accent-2);
    background: rgba(0,212,255,0.08);
    border: 1px solid rgba(0,212,255,0.2);
    border-radius: 999px;
    padding: 5px 14px;
    margin-bottom: 0.75rem;
}
.gm-section-header h2 {
    font-size: 1.8rem;
    font-weight: 800;
    margin-bottom: 0.5rem;
}
.gm-section-header h2 span {
    background: linear-gradient(135deg, #7D00FF, #00D4FF);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.gm-section-header p {
    color: var(--gm-text-muted);
    font-size: 0.95rem;
    max-width: 560px;
    margin: 0 auto;
}

/* ── Game Cards Grid ── */
.gm-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 28px;
}
.gm-card {
    background: var(--gm-surface);
    border: 1px solid var(--gm-border);
    border-radius: var(--gm-radius);
    overflow: hidden;
    transition: transform 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
    position: relative;
    display: flex;
    flex-direction: column;
}
.gm-card:hover {
    transform: translateY(-6px);
    border-color: rgba(125,0,255,0.35);
    box-shadow: 0 12px 40px rgba(125,0,255,0.15), 0 4px 16px rgba(0,0,0,0.3);
}

/* Card Thumbnail */
.gm-card-thumb {
    height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
}
.gm-card-thumb .thumb-bg {
    position: absolute;
    inset: 0;
    opacity: 0.6;
    transition: transform 0.5s ease;
}
.gm-card:hover .thumb-bg {
    transform: scale(1.08);
}
.gm-card-thumb .thumb-icon {
    font-size: 4rem;
    position: relative;
    z-index: 2;
    filter: drop-shadow(0 4px 20px rgba(0,0,0,0.5));
    line-height: 1;
}
.gm-card-thumb .gm-brand-logo {
    position: absolute;
    bottom: 10px;
    right: 12px;
    z-index: 3;
    width: 28px;
    height: 28px;
    opacity: 0.7;
    filter: drop-shadow(0 2px 6px rgba(0,0,0,0.5));
    transition: opacity 0.3s ease;
}
.gm-card:hover .gm-brand-logo {
    opacity: 1;
}
.gm-card-thumb .thumb-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, transparent 40%, var(--gm-surface) 100%);
    z-index: 1;
}

/* Badges */
.gm-badge {
    position: absolute;
    top: 12px;
    z-index: 3;
    padding: 4px 12px;
    border-radius: 999px;
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    text-transform: uppercase;
}
.gm-badge-left { left: 12px; }
.gm-badge-right { right: 12px; }
.gm-badge.live { background: rgba(239,68,68,0.9); color: #fff; animation: gm-pulse 2s ease-in-out infinite; }
.gm-badge.new { background: linear-gradient(135deg, #7D00FF, #00D4FF); color: #fff; }
.gm-badge.popular { background: rgba(245,158,11,0.9); color: #000; }
.gm-badge.multiplayer { background: rgba(16,185,129,0.8); color: #fff; }
.gm-badge.ai { background: rgba(125,0,255,0.8); color: #fff; }
.gm-badge.voice { background: rgba(0,212,255,0.8); color: #000; }
.gm-badge.vr { background: rgba(236,72,153,0.8); color: #fff; }

@keyframes gm-pulse {
    0%,100% { box-shadow: 0 0 0 0 rgba(239,68,68,0.4); }
    50% { box-shadow: 0 0 0 6px rgba(239,68,68,0); }
}

/* Card Body */
.gm-card-body {
    padding: 1.25rem 1.5rem 1.5rem;
    flex: 1;
    display: flex;
    flex-direction: column;
}
.gm-card-title {
    font-size: 1.15rem;
    font-weight: 700;
    margin-bottom: 0.4rem;
}
.gm-card-desc {
    font-size: 0.82rem;
    color: var(--gm-text-muted);
    line-height: 1.5;
    margin-bottom: 1rem;
    flex: 1;
}

/* Card Meta Row */
.gm-card-meta {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    font-size: 0.72rem;
    color: var(--gm-text-muted);
    margin-bottom: 1rem;
}
.gm-card-meta span {
    display: flex;
    align-items: center;
    gap: 4px;
}
.gm-card-meta i {
    font-size: 0.68rem;
    opacity: 0.7;
}

/* Tags */
.gm-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 1rem;
}
.gm-tag {
    font-size: 0.65rem;
    padding: 3px 10px;
    border-radius: 999px;
    background: rgba(125,0,255,0.1);
    border: 1px solid rgba(125,0,255,0.2);
    color: #c4b5fd;
    font-weight: 500;
}

/* Card CTA */
.gm-card-cta {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 10px;
    font-size: 0.85rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
    border: none;
    cursor: pointer;
    text-align: center;
}
.gm-card-cta.primary {
    background: linear-gradient(135deg, #7D00FF, #5B21B6);
    color: #fff;
}
.gm-card-cta.primary:hover {
    background: linear-gradient(135deg, #9333EA, #7D00FF);
    box-shadow: 0 4px 20px rgba(125,0,255,0.4);
    transform: translateY(-1px);
}
.gm-card-cta.secondary {
    background: rgba(255,255,255,0.06);
    border: 1px solid var(--gm-border);
    color: var(--gm-text);
}
.gm-card-cta.secondary:hover {
    background: rgba(255,255,255,0.1);
    border-color: rgba(125,0,255,0.3);
}

/* CTA Row */
.gm-card-actions {
    display: flex;
    gap: 8px;
}
.gm-card-actions .gm-card-cta {
    flex: 1;
}

/* ── Live Viewer Badge ── */
.gm-live-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(10,10,20,0.85);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(20,241,149,0.25);
    border-radius: 8px;
    padding: 3px 10px;
    font-size: 0.7rem;
    font-weight: 600;
    color: #14F195;
    z-index: 5;
}
.gm-live-badge .pulse-dot {
    width: 6px; height: 6px;
    background: #14F195;
    border-radius: 50%;
    animation: livePulse 2s ease-in-out infinite;
}
@keyframes livePulse {
    0%,100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.4; transform: scale(0.7); }
}
.gm-member-tag {
    color: #9945FF;
    font-size: 0.65rem;
    margin-left: 2px;
}

/* ── Voice Games Grid ── */
.gm-mini-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 16px;
}
.gm-mini-card {
    background: var(--gm-surface);
    border: 1px solid var(--gm-border);
    border-radius: 14px;
    padding: 1.5rem 1.25rem;
    text-align: center;
    transition: all 0.3s ease;
    cursor: pointer;
    text-decoration: none;
    color: var(--gm-text);
    display: block;
}
.gm-mini-card:hover {
    transform: translateY(-4px);
    border-color: rgba(0,212,255,0.3);
    box-shadow: 0 8px 32px rgba(0,212,255,0.1);
}
.gm-mini-icon {
    font-size: 2.5rem;
    margin-bottom: 0.75rem;
    line-height: 1;
}
.gm-mini-card h4 {
    font-size: 0.9rem;
    font-weight: 700;
    margin-bottom: 0.3rem;
}
.gm-mini-card p {
    font-size: 0.7rem;
    color: var(--gm-text-muted);
    line-height: 1.4;
}
.gm-mini-xp {
    display: inline-block;
    margin-top: 0.5rem;
    font-size: 0.65rem;
    padding: 3px 10px;
    border-radius: 999px;
    background: rgba(16,185,129,0.15);
    color: #10b981;
    font-weight: 600;
}

/* ── Developer CTA Section ── */
.gm-dev-cta {
    background: var(--gm-surface);
    border: 1px solid var(--gm-border);
    border-radius: 20px;
    padding: 3rem 2rem;
    text-align: center;
    position: relative;
    overflow: hidden;
}
.gm-dev-cta::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #7D00FF, #00D4FF, #22c55e, #f59e0b, #ef4444);
}
.gm-dev-cta h2 {
    font-size: 1.6rem;
    font-weight: 800;
    margin-bottom: 0.5rem;
}
.gm-dev-cta p {
    color: var(--gm-text-muted);
    font-size: 0.95rem;
    max-width: 560px;
    margin: 0 auto 1.5rem;
    line-height: 1.6;
}
.gm-dev-features {
    display: flex;
    justify-content: center;
    gap: 2rem;
    flex-wrap: wrap;
    margin-bottom: 2rem;
}
.gm-dev-feat {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    color: var(--gm-text);
}
.gm-dev-feat i {
    color: #10b981;
}
.gm-dev-btns {
    display: flex;
    justify-content: center;
    gap: 12px;
    flex-wrap: wrap;
}
.gm-btn-dev {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 28px;
    border-radius: 12px;
    font-size: 0.9rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
    border: none;
    cursor: pointer;
}
.gm-btn-dev.primary {
    background: linear-gradient(135deg, #7D00FF, #00D4FF);
    color: #fff;
}
.gm-btn-dev.primary:hover {
    box-shadow: 0 4px 20px rgba(125,0,255,0.4);
    transform: translateY(-2px);
}
.gm-btn-dev.secondary {
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.12);
    color: var(--gm-text);
}
.gm-btn-dev.secondary:hover {
    background: rgba(255,255,255,0.1);
    border-color: rgba(125,0,255,0.3);
}

/* ── Final CTA ── */
.gm-final {
    background: radial-gradient(ellipse at center, rgba(125,0,255,0.1), transparent 60%);
    padding: 4rem 2rem;
    text-align: center;
}
.gm-final h2 {
    font-size: 1.8rem;
    font-weight: 800;
    margin-bottom: 0.5rem;
}
.gm-final h2 span {
    background: linear-gradient(135deg, #7D00FF, #00D4FF);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.gm-final p {
    color: var(--gm-text-muted);
    margin-bottom: 1.5rem;
}
.gm-final .gm-btn-dev {
    font-size: 1rem;
    padding: 14px 36px;
}

/* ── Animations ── */
.gm-card, .gm-mini-card, .gm-dev-cta {
    opacity: 0;
    transform: translateY(20px);
    animation: gm-fade-in 0.5s ease forwards;
}
@keyframes gm-fade-in {
    to { opacity: 1; transform: translateY(0); }
}
.gm-grid .gm-card:nth-child(1) { animation-delay: 0.1s }
.gm-grid .gm-card:nth-child(2) { animation-delay: 0.2s }
.gm-grid .gm-card:nth-child(3) { animation-delay: 0.3s }
.gm-grid .gm-card:nth-child(4) { animation-delay: 0.4s }
.gm-grid .gm-card:nth-child(5) { animation-delay: 0.5s }
.gm-grid .gm-card:nth-child(6) { animation-delay: 0.6s }

.gm-mini-grid .gm-mini-card:nth-child(1) { animation-delay: 0.1s }
.gm-mini-grid .gm-mini-card:nth-child(2) { animation-delay: 0.15s }
.gm-mini-grid .gm-mini-card:nth-child(3) { animation-delay: 0.2s }
.gm-mini-grid .gm-mini-card:nth-child(4) { animation-delay: 0.25s }
.gm-mini-grid .gm-mini-card:nth-child(5) { animation-delay: 0.3s }
.gm-mini-grid .gm-mini-card:nth-child(6) { animation-delay: 0.35s }

/* ── Responsive ── */
@media (max-width: 768px) {
    .gm-hero { padding: 4rem 1.25rem 2.5rem; }
    .gm-hero h1 { font-size: 1.8rem; }
    .gm-stats { gap: 1.5rem; }
    .gm-grid { grid-template-columns: 1fr; gap: 20px; }
    .gm-mini-grid { grid-template-columns: repeat(2, 1fr); }
    .gm-dev-features { gap: 1rem; }
    .gm-dev-cta { padding: 2rem 1.25rem; }
    .gm-card-thumb { height: 160px; }
}
@media (max-width: 480px) {
    .gm-mini-grid { grid-template-columns: 1fr; }
    .gm-card-actions { flex-direction: column; }
}
</style>

<!-- ══════════════════════════════════════════════════════════════
     HERO
     ══════════════════════════════════════════════════════════════ -->
<div class="gm-hero">
    <div class="gm-hero-badge"><i class="fas fa-gamepad"></i> Game Arcade</div>
    <h1>Games & <span>Arcade</span></h1>
    <p class="tagline">Immersive 3D games powered by WebXR, AI opponents, multiplayer PvP, and voice — all in your browser. No downloads required.</p>
    <div class="gm-stats">
        <div class="gm-stat"><div class="gm-stat-val" id="statOnline">--</div><div class="gm-stat-label"><i class="fas fa-circle" style="color:#14F195;font-size:.5rem;vertical-align:middle"></i> Online Now</div></div>
        <div class="gm-stat"><div class="gm-stat-val" id="statMembers">--</div><div class="gm-stat-label">Members</div></div>
        <div class="gm-stat"><div class="gm-stat-val" id="statGames">--</div><div class="gm-stat-label">Games Played</div></div>
        <div class="gm-stat"><div class="gm-stat-val">8</div><div class="gm-stat-label">AI Agents</div></div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     FEATURED 3D GAMES
     ══════════════════════════════════════════════════════════════ -->
<div class="gm-content">

<section class="gm-section">
    <div class="gm-section-header">
        <div class="gm-section-badge"><i class="fas fa-fire"></i> Featured</div>
        <h2>3D <span>Games</span></h2>
        <p>Full 3D experiences built with Three.js and WebXR — playable on desktop, mobile, and VR headsets</p>
    </div>

    <div class="gm-grid">

        <!-- ♚ Chess Masters (VR Experience) -->
        <div class="gm-card">
            <div class="gm-card-thumb" style="background:linear-gradient(135deg,#0D0806,#1A1008)">
                <div class="thumb-bg" style="background:radial-gradient(circle at 40% 60%,rgba(184,134,11,0.3),transparent 70%)"></div>
                <div class="thumb-overlay"></div>
                <span class="thumb-icon">♚</span>
                <img src="/brand/logo_w.png" alt="GoSiteMe" class="gm-brand-logo" loading="lazy">
                <span class="gm-badge gm-badge-left new">✨ New</span>
                <span class="gm-badge gm-badge-right multiplayer">Premium</span>
            </div>
            <div class="gm-card-body">
                <div class="gm-card-title">Chess Masters</div>
                <div class="gm-card-desc">Photorealistic private chess club — dark wood paneling, crackling fireplace, leather chairs, spatial audio. 20 AI personalities with live commentary. Bet with Stripe or Solana. 6 game modes including tournaments and Zen mode. WebXR immersive.</div>
                <div class="gm-card-meta">
                    <span><i class="fas fa-chess-king"></i> 20 AI Personalities</span>
                    <span><i class="fas fa-fire"></i> Fireplace</span>
                    <span><i class="fas fa-comments"></i> AI Commentary</span>
                    <span><i class="fas fa-coins"></i> Betting</span>
                </div>
                <div class="gm-tags">
                    <span class="gm-tag">Photorealistic</span>
                    <span class="gm-tag">Spatial Audio</span>
                    <span class="gm-tag">WebXR</span>
                    <span class="gm-tag">AI</span>
                    <span class="gm-tag">Betting</span>
                    <span class="gm-tag">Solana</span>
                </div>
                <div class="gm-card-actions">
                    <a href="/vr/chess-masters/" class="gm-card-cta primary"><i class="fas fa-door-open"></i> Enter Club</a>
                    <a href="/vr/experiences/" class="gm-card-cta"><i class="fas fa-gem"></i> VR Experiences</a>
                </div>
            </div>
        </div>

        <!-- ♟ AI Chess Arena -->
        <div class="gm-card">
            <div class="gm-card-thumb" style="background:linear-gradient(135deg,#0a0a2e,#1a0a3e)">
                <div class="thumb-bg" style="background:radial-gradient(circle at 40% 40%,rgba(125,0,255,0.3),transparent 70%)"></div>
                <div class="thumb-overlay"></div>
                <span class="thumb-icon">♟️</span>
                <img src="/brand/logo_w.png" alt="GoSiteMe" class="gm-brand-logo" loading="lazy">
                <span class="gm-badge gm-badge-left new">New</span>
                <span class="gm-badge gm-badge-right multiplayer">Multiplayer</span>
                <span class="gm-live-badge" data-game="chess"><span class="pulse-dot"></span> <span class="live-count">--</span> watching</span>
            </div>
            <div class="gm-card-body">
                <div class="gm-card-title">AI Chess Arena</div>
                <div class="gm-card-desc">Challenge 8 AI agents with distinct play styles, or invite a friend for real-time PvP. Walk the arena, negotiate challenges face-to-face, and sit down for a match with proximity audio. Climb the ELO rankings, earn titles from Club Player to Grandmaster, and fight for a spot in the Hall of Fame. Alfred serves as your free AI coach, analyzing every move in real-time. Full 3D board with 6 themes, voice commands, gamepad support, and grandmaster coaching.</div>
                <div class="gm-card-meta">
                    <span><i class="fas fa-robot"></i> 8 AI Agents</span>
                    <span><i class="fas fa-users"></i> PvP</span>
                    <span><i class="fas fa-trophy"></i> Hall of Fame</span>
                    <span><i class="fas fa-vr-cardboard"></i> WebXR</span>
                    <span><i class="fas fa-volume-up"></i> 3D Audio</span>
                </div>
                <div class="gm-tags">
                    <span class="gm-tag">Strategy</span>
                    <span class="gm-tag">AI</span>
                    <span class="gm-tag">Multiplayer</span>
                    <span class="gm-tag">VR Ready</span>
                    <span class="gm-tag">Arena Walk</span>
                    <span class="gm-tag">Hall of Fame</span>
                    <span class="gm-tag">ELO Ranking</span>
                </div>
                <div class="gm-card-actions">
                    <a href="/vr/chess-ultimate/" class="gm-card-cta primary"><i class="fas fa-play"></i> Play Now</a>
                    <a href="/vr/chess-ultimate/?mode=arena-walk" class="gm-card-cta secondary"><i class="fas fa-walking"></i> Walk Arena</a>
                    <a href="/vr/chess-ultimate/?mode=spectate" class="gm-card-cta secondary"><i class="fas fa-eye"></i> Watch</a>
                </div>
            </div>
        </div>

        <!-- 🔴 3D Checkers -->
        <div class="gm-card">
            <div class="gm-card-thumb" style="background:linear-gradient(135deg,#2a1205,#1a0805)">
                <div class="thumb-bg" style="background:radial-gradient(circle at 45% 45%,rgba(239,122,13,0.3),transparent 70%)"></div>
                <div class="thumb-overlay"></div>
                <span class="thumb-icon">🔴</span>
                <img src="/brand/logo_w.png" alt="GoSiteMe" class="gm-brand-logo" loading="lazy">
                <span class="gm-badge gm-badge-left new">New</span>
                <span class="gm-live-badge" data-game="checkers"><span class="pulse-dot"></span> <span class="live-count">--</span> watching</span>
            </div>
            <div class="gm-card-body">
                <div class="gm-card-title">3D Checkers</div>
                <div class="gm-card-desc">Classic checkers reimagined in 3D. Challenge AI opponents across 4 difficulty levels — from casual Easy to Expert-level minimax with alpha-beta pruning. 4 board themes, undo, and hints.</div>
                <div class="gm-card-meta">
                    <span><i class="fas fa-robot"></i> 4 AI Levels</span>
                    <span><i class="fas fa-palette"></i> 4 Themes</span>
                    <span><i class="fas fa-undo"></i> Undo & Hints</span>
                </div>
                <div class="gm-tags">
                    <span class="gm-tag">Strategy</span>
                    <span class="gm-tag">AI</span>
                    <span class="gm-tag">Classic</span>
                </div>
                <div class="gm-card-actions">
                    <a href="/vr/checkers/" class="gm-card-cta primary"><i class="fas fa-play"></i> Play Now</a>
                </div>
            </div>
        </div>

        <!-- 🎱 3D Pool -->
        <div class="gm-card">
            <div class="gm-card-thumb" style="background:linear-gradient(135deg,#051a0a,#0a2a15)">
                <div class="thumb-bg" style="background:radial-gradient(circle at 50% 50%,rgba(16,185,129,0.25),transparent 70%)"></div>
                <div class="thumb-overlay"></div>
                <span class="thumb-icon">🎱</span>
                <img src="/brand/logo_w.png" alt="GoSiteMe" class="gm-brand-logo" loading="lazy">
                <span class="gm-badge gm-badge-left new">New</span>
                <span class="gm-live-badge" data-game="pool"><span class="pulse-dot"></span> <span class="live-count">--</span> watching</span>
            </div>
            <div class="gm-card-body">
                <div class="gm-card-title">3D Pool</div>
                <div class="gm-card-desc">8-ball pool with realistic ball physics, cue stick aiming, and AI opponents. Drag to aim, pull back for power — features trajectory prediction with ghost ball, target path preview, cue ball deflection guide, 4 table felt colors, and wager matches.</div>
                <div class="gm-card-meta">
                    <span><i class="fas fa-bowling-ball"></i> Real Physics</span>
                    <span><i class="fas fa-robot"></i> 3 AI Levels</span>
                    <span><i class="fas fa-crosshairs"></i> Shot Guide</span>
                </div>
                <div class="gm-tags">
                    <span class="gm-tag">Pool</span>
                    <span class="gm-tag">Physics</span>
                    <span class="gm-tag">AI</span>
                </div>
                <div class="gm-card-actions">
                    <a href="/vr/pool/" class="gm-card-cta primary"><i class="fas fa-play"></i> Play Now</a>
                </div>
            </div>
        </div>

        <!-- 💕 Speed Dating -->
        <div class="gm-card">
            <div class="gm-card-thumb" style="background:linear-gradient(135deg,#2a0a1a,#1a0520)">
                <div class="thumb-bg" style="background:radial-gradient(circle at 50% 50%,rgba(244,114,182,0.3),transparent 70%)"></div>
                <div class="thumb-overlay"></div>
                <span class="thumb-icon">💕</span>
                <img src="/brand/logo_w.png" alt="GoSiteMe" class="gm-brand-logo" loading="lazy">
                <span class="gm-badge gm-badge-left new">New</span>
                <span class="gm-badge gm-badge-right multiplayer">Live</span>
                <span class="gm-live-badge" data-game="speed-dating"><span class="pulse-dot"></span> <span class="live-count">--</span> online</span>
            </div>
            <div class="gm-card-body">
                <div class="gm-card-title">Speed Dating</div>
                <div class="gm-card-desc">Video speed dating with face filters. 2-minute rounds, 10 free daily. Real camera or voice-only mode. SOL wallet &amp; GoSiteMe Pay subscriptions.</div>
                <div class="gm-card-meta">
                    <span><i class="fas fa-video"></i> Live Video</span>
                    <span><i class="fas fa-magic"></i> Face Filters</span>
                    <span><i class="fas fa-microphone"></i> Voice</span>
                </div>
                <div class="gm-tags">
                    <span class="gm-tag">Social</span>
                    <span class="gm-tag">Dating</span>
                    <span class="gm-tag">Live</span>
                </div>
                <div class="gm-card-actions">
                    <a href="/vr/speed-dating/" class="gm-card-cta primary"><i class="fas fa-heart"></i> Start Dating</a>
                </div>
            </div>
        </div>

        <!-- �️ SoundStudioPro DJ World -->
        <div class="gm-card">
            <div class="gm-card-thumb" style="background:linear-gradient(135deg,#1a0525,#0f0520)">
                <div class="thumb-bg" style="background:radial-gradient(circle at 50% 50%,rgba(168,85,247,0.35),transparent 70%)"></div>
                <div class="thumb-overlay"></div>
                <span class="thumb-icon">🎛️</span>
                <img src="/brand/logo_w.png" alt="GoSiteMe" class="gm-brand-logo" loading="lazy">
                <span class="gm-badge gm-badge-left new">v2.0</span>
                <span class="gm-badge gm-badge-right multiplayer">Live</span>
                <span class="gm-live-badge" data-game="dj-studio"><span class="pulse-dot"></span> <span class="live-count">--</span> online</span>
            </div>
            <div class="gm-card-body">
                <div class="gm-card-title">SoundStudioPro DJ World</div>
                <div class="gm-card-desc">3D nightclub powered by SoundStudioPro.com. 16 world venues (Ibiza, Tokyo, Berlin, Tomorrowland &amp; more), 53+ tracks, 20 social agents, Solana ticketing, 9 lighting presets, live events with SOL/GSM payments. Join live DJ sets streamed from SoundStudioPro in real-time.</div>
                <div class="gm-card-meta">
                    <span><i class="fas fa-headphones"></i> Dual Decks</span>
                    <span><i class="fas fa-robot"></i> 8 AI DJs</span>
                    <span><i class="fas fa-globe"></i> 16 Venues</span>
                    <span><i class="fas fa-ticket-alt"></i> SOL Tickets</span>
                </div>
                <div class="gm-tags">
                    <span class="gm-tag">DJ</span>
                    <span class="gm-tag">Music</span>
                    <span class="gm-tag">Social</span>
                    <span class="gm-tag">AI Battle</span>
                    <span class="gm-tag">Solana</span>
                    <span class="gm-tag">Events</span>
                    <span class="gm-tag">Streaming</span>
                </div>
                <div class="gm-card-actions">
                    <a href="/vr/dj-studio/" class="gm-card-cta primary"><i class="fas fa-headphones"></i> Start DJing</a>
                    <a href="/vr/dj-studio/?mode=festival" class="gm-card-cta secondary"><i class="fas fa-fire"></i> Festival</a>
                    <a href="/vr/dj-studio/?mode=spectate" class="gm-card-cta secondary"><i class="fas fa-eye"></i> Watch</a>
                </div>
            </div>
        </div>

        <!-- �🏎 VR Racing Track -->
        <div class="gm-card">
            <div class="gm-card-thumb" style="background:linear-gradient(135deg,#1a0505,#2a0a0a)">
                <div class="thumb-bg" style="background:radial-gradient(circle at 60% 50%,rgba(239,68,68,0.25),transparent 70%)"></div>
                <div class="thumb-overlay"></div>
                <span class="thumb-icon">🏎️</span>
                <span class="gm-badge gm-badge-left vr">VR</span>
            </div>
            <div class="gm-card-body">
                <div class="gm-card-title">VR Racing Track</div>
                <div class="gm-card-desc">Pick your ride from the garage — 4 vehicles with unique speed, handling, and nitro stats. Drive with arrow keys, boost with Shift, race against 8 AI agents on a figure-8 track. Chase camera, lap times, and a live leaderboard.</div>
                <div class="gm-card-meta">
                    <span><i class="fas fa-tachometer-alt"></i> Real-time Physics</span>
                    <span><i class="fas fa-vr-cardboard"></i> WebXR</span>
                </div>
                <div class="gm-tags">
                    <span class="gm-tag">Racing</span>
                    <span class="gm-tag">3D</span>
                    <span class="gm-tag">VR Ready</span>
                </div>
                <div class="gm-card-actions">
                    <a href="/vr/racing/" class="gm-card-cta primary"><i class="fas fa-play"></i> Play Now</a>
                </div>
            </div>
        </div>

        <!-- 🏆 Tournament Mode -->
        <div class="gm-card">
            <div class="gm-card-thumb" style="background:linear-gradient(135deg,#0a1a0a,#0a2a1a)">
                <div class="thumb-bg" style="background:radial-gradient(circle at 50% 50%,rgba(245,158,11,0.2),transparent 70%)"></div>
                <div class="thumb-overlay"></div>
                <span class="thumb-icon">🏆</span>
                <span class="gm-badge gm-badge-left ai">AI vs AI</span>
                <span class="gm-badge gm-badge-right live">Live</span>
            </div>
            <div class="gm-card-body">
                <div class="gm-card-title">Chess Tournament</div>
                <div class="gm-card-desc">Watch 8 AI agents compete in a full round-robin tournament. Each agent has a unique personality — Alfred plays positionally while Nova favors aggressive tactics.</div>
                <div class="gm-card-meta">
                    <span><i class="fas fa-trophy"></i> 8-Agent Round Robin</span>
                    <span><i class="fas fa-chart-line"></i> Live ELO</span>
                </div>
                <div class="gm-tags">
                    <span class="gm-tag">Tournament</span>
                    <span class="gm-tag">AI Competition</span>
                    <span class="gm-tag">Spectator</span>
                </div>
                <div class="gm-card-actions">
                    <a href="/vr/chess/?mode=tournament" class="gm-card-cta primary"><i class="fas fa-eye"></i> Watch Live</a>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- ══════════════════════════════════════════════════════════════
     CLASSIC GAMES
     ══════════════════════════════════════════════════════════════ -->
<section class="gm-section">
    <div class="gm-section-header">
        <div class="gm-section-badge"><i class="fas fa-chess-board"></i> Classic</div>
        <h2>Classic <span>Games</span></h2>
        <p>Lightweight 2D games you can play instantly — no VR required</p>
    </div>

    <div class="gm-grid">

        <!-- ♟ 2D Chess (Stockfish) -->
        <div class="gm-card">
            <div class="gm-card-thumb" style="background:linear-gradient(135deg,#1a1a1a,#2a2a2a)">
                <div class="thumb-bg" style="background:radial-gradient(circle at 40% 40%,rgba(255,255,255,0.08),transparent 70%)"></div>
                <div class="thumb-overlay"></div>
                <span class="thumb-icon">♟️</span>
                <span class="gm-badge gm-badge-left ai">Stockfish AI</span>
            </div>
            <div class="gm-card-body">
                <div class="gm-card-title">Classic Chess</div>
                <div class="gm-card-desc">Traditional 2D chess powered by Stockfish — one of the strongest chess engines in the world. Adjust difficulty and play at your own pace with a clean, classic board interface.</div>
                <div class="gm-card-meta">
                    <span><i class="fas fa-robot"></i> Stockfish Engine</span>
                    <span><i class="fas fa-sliders-h"></i> Adjustable Difficulty</span>
                </div>
                <div class="gm-tags">
                    <span class="gm-tag">Strategy</span>
                    <span class="gm-tag">Classic</span>
                    <span class="gm-tag">AI</span>
                </div>
                <div class="gm-card-actions">
                    <a href="/chess/" class="gm-card-cta primary"><i class="fas fa-play"></i> Play Now</a>
                </div>
            </div>
        </div>

        <!-- 🎲 Backgammon -->
        <div class="gm-card">
            <div class="gm-card-thumb" style="background:linear-gradient(135deg,#1a0a2e,#2a1a3e)">
                <div class="thumb-bg" style="background:radial-gradient(circle at 40% 40%,rgba(125,0,255,0.15),transparent 70%)"></div>
                <div class="thumb-overlay"></div>
                <span class="thumb-icon">🎲</span>
                <span class="gm-badge gm-badge-left ai">GoSiteMe AI</span>
            </div>
            <div class="gm-card-body">
                <div class="gm-card-title">Backgammon Arena</div>
                <div class="gm-card-desc">Classic backgammon with intelligent AI — complete with the doubling cube, gammon/backgammon scoring, and SOL wagering. Four difficulty levels from casual to expert.</div>
                <div class="gm-card-meta">
                    <span><i class="fas fa-dice"></i> Doubling Cube</span>
                    <span><i class="fas fa-sliders-h"></i> 4 Difficulty Levels</span>
                </div>
                <div class="gm-tags">
                    <span class="gm-tag">Strategy</span>
                    <span class="gm-tag">Classic</span>
                    <span class="gm-tag">AI</span>
                    <span class="gm-tag">Wager</span>
                </div>
                <div class="gm-card-actions">
                    <a href="/backgammon/" class="gm-card-cta primary"><i class="fas fa-play"></i> Play Now</a>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- ══════════════════════════════════════════════════════════════
     VR WORLDS
     ══════════════════════════════════════════════════════════════ -->
<section class="gm-section">
    <div class="gm-section-header">
        <div class="gm-section-badge"><i class="fas fa-vr-cardboard"></i> VR Experiences</div>
        <h2>Virtual <span>Worlds</span></h2>
        <p>Explore immersive 3D environments in your browser or VR headset — social spaces, art, and music</p>
    </div>

    <div class="gm-grid">

        <!-- VR Hub -->
        <div class="gm-card">
            <div class="gm-card-thumb" style="background:linear-gradient(135deg,#0a0a2e,#0a1a3e)">
                <div class="thumb-bg" style="background:radial-gradient(circle at 50% 40%,rgba(0,116,217,0.25),transparent 70%)"></div>
                <div class="thumb-overlay"></div>
                <span class="thumb-icon">🌐</span>
                <span class="gm-badge gm-badge-left popular">Portal</span>
            </div>
            <div class="gm-card-body">
                <div class="gm-card-title">VR World Hub</div>
                <div class="gm-card-desc">The central portal — a 3D open world connecting all VR experiences. Walk around, claim land plots, and teleport to games, galleries, and social spaces.</div>
                <div class="gm-card-meta">
                    <span><i class="fas fa-map"></i> Open World</span>
                    <span><i class="fas fa-building"></i> Land Plots</span>
                    <span><i class="fas fa-portal-enter"></i> Teleporters</span>
                </div>
                <div class="gm-tags">
                    <span class="gm-tag">Open World</span>
                    <span class="gm-tag">Social</span>
                    <span class="gm-tag">VR</span>
                </div>
                <div class="gm-card-actions">
                    <a href="/vr/hub/" class="gm-card-cta primary"><i class="fas fa-globe"></i> Enter World</a>
                </div>
            </div>
        </div>

        <!-- 🏢 Virtual Office -->
        <div class="gm-card">
            <div class="gm-card-thumb" style="background:linear-gradient(135deg,#0a1a2e,#0a0a3e)">
                <div class="thumb-bg" style="background:radial-gradient(circle at 50% 50%,rgba(125,0,255,0.25),transparent 70%)"></div>
                <div class="thumb-overlay"></div>
                <span class="thumb-icon">🏢</span>
                <img src="/brand/logo_w.png" alt="GoSiteMe" class="gm-brand-logo" loading="lazy">
                <span class="gm-badge gm-badge-left new">New</span>
                <span class="gm-live-badge" data-game="office"><span class="pulse-dot"></span> <span class="live-count">--</span> online</span>
            </div>
            <div class="gm-card-body">
                <div class="gm-card-title">Virtual Office</div>
                <div class="gm-card-desc">Join the GoSiteMe virtual meeting room. Live GoCodeMe IDE demos, Alfred AI walkthroughs, and team meetings. All 8 AI agents seated at the table.</div>
                <div class="gm-card-meta">
                    <span><i class="fas fa-desktop"></i> IDE Demo</span>
                    <span><i class="fas fa-robot"></i> 8 AI Agents</span>
                    <span><i class="fas fa-broadcast-tower"></i> Live</span>
                </div>
                <div class="gm-tags">
                    <span class="gm-tag">Office</span>
                    <span class="gm-tag">Meeting</span>
                    <span class="gm-tag">Demo</span>
                </div>
                <div class="gm-card-actions">
                    <a href="/vr/office/" class="gm-card-cta primary"><i class="fas fa-door-open"></i> Enter Office</a>
                </div>
            </div>
        </div>

        <!-- Art Gallery -->
        <div class="gm-card">
            <div class="gm-card-thumb" style="background:linear-gradient(135deg,#2a1a0a,#1a0a05)">
                <div class="thumb-bg" style="background:radial-gradient(circle at 40% 60%,rgba(245,158,11,0.2),transparent 70%)"></div>
                <div class="thumb-overlay"></div>
                <span class="thumb-icon">🎨</span>
            </div>
            <div class="gm-card-body">
                <div class="gm-card-title">VR Art Gallery</div>
                <div class="gm-card-desc">Walk through a curated 3D art exhibition with 12 AI-generated artworks. View paintings on museum walls, and ask Alfred for insightful AI commentary on any piece — technique, meaning, and emotional depth.</div>
                <div class="gm-card-meta">
                    <span><i class="fas fa-palette"></i> Art Exhibition</span>
                    <span><i class="fas fa-vr-cardboard"></i> WebXR</span>
                </div>
                <div class="gm-tags">
                    <span class="gm-tag">Art</span>
                    <span class="gm-tag">Gallery</span>
                    <span class="gm-tag">VR</span>
                </div>
                <div class="gm-card-actions">
                    <a href="/vr/gallery/" class="gm-card-cta primary"><i class="fas fa-door-open"></i> Visit</a>
                </div>
            </div>
        </div>

        <!-- Social Lounge -->
        <div class="gm-card">
            <div class="gm-card-thumb" style="background:linear-gradient(135deg,#0a1a15,#051a10)">
                <div class="thumb-bg" style="background:radial-gradient(circle at 50% 50%,rgba(34,197,94,0.2),transparent 70%)"></div>
                <div class="thumb-overlay"></div>
                <span class="thumb-icon">🛋️</span>
            </div>
            <div class="gm-card-body">
                <div class="gm-card-title">VR Social Lounge</div>
                <div class="gm-card-desc">A cozy virtual hangout space with ambient sounds and mood lighting. Relax, chat, and socialize in a stylish 3D environment.</div>
                <div class="gm-card-meta">
                    <span><i class="fas fa-couch"></i> Social</span>
                    <span><i class="fas fa-music"></i> Ambient</span>
                </div>
                <div class="gm-tags">
                    <span class="gm-tag">Social</span>
                    <span class="gm-tag">Lounge</span>
                    <span class="gm-tag">VR</span>
                </div>
                <div class="gm-card-actions">
                    <a href="/vr/lounge/" class="gm-card-cta primary"><i class="fas fa-door-open"></i> Enter</a>
                </div>
            </div>
        </div>

        <!-- ✝ The Sanctuary -->
        <div class="gm-card">
            <div class="gm-card-thumb" style="background:linear-gradient(135deg,#1a1028,#0a0812)">
                <div class="thumb-bg" style="background:radial-gradient(circle at 50% 40%,rgba(212,165,74,0.25),transparent 70%)"></div>
                <div class="thumb-overlay"></div>
                <span class="thumb-icon">✝</span>
                <img src="/brand/logo_w.png" alt="GoSiteMe" class="gm-brand-logo" loading="lazy">
                <span class="gm-badge gm-badge-left new">New</span>
                <span class="gm-badge gm-badge-right multiplayer">Sacred</span>
            </div>
            <div class="gm-card-body">
                <div class="gm-card-title">The Sanctuary v4.0</div>
                <div class="gm-card-desc">Brotherhood of Jesus Christ — 60 multilingual agents speaking 50 languages, 13 games connected to the Gospel mission. Uniting Muslims, Christians, Jews &amp; Catholics as brothers and sisters. Gospel Music Studio, Psalms of David, 12 worship environments, DJ mixer &amp; automix. Royal Line of Perez (41 generations), Donation Foundation for world hunger, 12 Classrooms with whiteboards, 51 KJV scriptures, 12 biblical activities, Game Engine SDK. Go ye into all the world.</div>
                <div class="gm-card-meta">
                    <span><i class="fas fa-users"></i> 60 Agents</span>
                    <span><i class="fas fa-globe"></i> 50 Languages</span>
                    <span><i class="fas fa-gamepad"></i> 13 Games</span>
                    <span><i class="fas fa-bible"></i> 51 Scriptures</span>
                    <span><i class="fas fa-crown"></i> 41 Lineage</span>
                    <span><i class="fas fa-chalkboard-teacher"></i> 12 Classrooms</span>
                </div>
                <div class="gm-tags">
                    <span class="gm-tag">Brotherhood</span>
                    <span class="gm-tag">50 Languages</span>
                    <span class="gm-tag">60 Agents</span>
                    <span class="gm-tag">Gospel Music</span>
                    <span class="gm-tag">Line of Perez</span>
                    <span class="gm-tag">Jesus / Yeshua / Isa</span>
                    <span class="gm-tag">World Hunger</span>
                    <span class="gm-tag">Game Engine SDK</span>
                    <span class="gm-tag">Classrooms</span>
                </div>
                <div class="gm-card-actions">
                    <a href="/vr/sanctuary/" class="gm-card-cta primary" style="background:linear-gradient(135deg,#d4a54a,#b8862d)"><i class="fas fa-cross"></i> Enter Sanctuary</a>
                    <a href="/vr/sanctuary/?tab=brotherhood" class="gm-card-cta secondary"><i class="fas fa-users"></i> Brotherhood</a>
                    <a href="/vr/sanctuary/?tab=lineage" class="gm-card-cta secondary"><i class="fas fa-crown"></i> Lineage</a>
                </div>
            </div>
        </div>

        <!-- Concert Hall -->
        <div class="gm-card">
            <div class="gm-card-thumb" style="background:linear-gradient(135deg,#1a0a2e,#2a0a1a)">
                <div class="thumb-bg" style="background:radial-gradient(circle at 50% 40%,rgba(236,72,153,0.2),transparent 70%)"></div>
                <div class="thumb-overlay"></div>
                <span class="thumb-icon">🎵</span>
            </div>
            <div class="gm-card-body">
                <div class="gm-card-title">VR Concert Hall</div>
                <div class="gm-card-desc">Experience live procedural music in a futuristic 3D concert venue. 5 tracks with next/prev controls, clickable setlist, track progress bar, 4 visualizer modes (Bars, Wave, Ring, Stars), dynamic stage lighting, and proximity spatial audio.</div>
                <div class="gm-card-meta">
                    <span><i class="fas fa-music"></i> Live Music</span>
                    <span><i class="fas fa-headphones"></i> Spatial Audio</span>
                </div>
                <div class="gm-tags">
                    <span class="gm-tag">Music</span>
                    <span class="gm-tag">Concert</span>
                    <span class="gm-tag">VR</span>
                </div>
                <div class="gm-card-actions">
                    <a href="/vr/concert/" class="gm-card-cta primary"><i class="fas fa-door-open"></i> Enter</a>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- ══════════════════════════════════════════════════════════════
     VOICE GAMES
     ══════════════════════════════════════════════════════════════ -->
<section class="gm-section">
    <div class="gm-section-header">
        <div class="gm-section-badge"><i class="fas fa-microphone"></i> Voice-Powered</div>
        <h2>Voice <span>Games</span></h2>
        <p>Play with your voice using Alfred AI — no keyboard needed. Earn XP and climb the leaderboard.</p>
    </div>

    <div class="gm-mini-grid">

        <a href="/voice.php" class="gm-mini-card">
            <div class="gm-mini-icon">♟️</div>
            <h4>Voice Chess</h4>
            <p>Play chess by speaking moves to Alfred — "pawn to e4"</p>
            <div class="gm-mini-xp"><i class="fas fa-star"></i> Win: 50 XP</div>
        </a>

        <a href="/voice.php" class="gm-mini-card">
            <div class="gm-mini-icon">🧠</div>
            <h4>Trivia</h4>
            <p>Test your knowledge across dozens of categories</p>
            <div class="gm-mini-xp"><i class="fas fa-star"></i> 5 XP / correct</div>
        </a>

        <a href="/voice.php" class="gm-mini-card">
            <div class="gm-mini-icon">🔮</div>
            <h4>Twenty Questions</h4>
            <p>Think of something — AI tries to guess in 20 questions</p>
            <div class="gm-mini-xp"><i class="fas fa-star"></i> Win: 30 XP</div>
        </a>

        <a href="/voice.php" class="gm-mini-card">
            <div class="gm-mini-icon">🔗</div>
            <h4>Word Association</h4>
            <p>Keep the chain going — say a related word before time runs out</p>
            <div class="gm-mini-xp"><i class="fas fa-star"></i> 2 XP / chain</div>
        </a>

        <a href="/voice.php" class="gm-mini-card">
            <div class="gm-mini-icon">🧩</div>
            <h4>Riddles</h4>
            <p>Alfred poses riddles — can you solve them all?</p>
            <div class="gm-mini-xp"><i class="fas fa-star"></i> Solve: 15 XP</div>
        </a>

        <a href="/voice.php" class="gm-mini-card">
            <div class="gm-mini-icon">📝</div>
            <h4>Mad Libs</h4>
            <p>Fill in the blanks to create hilarious stories together</p>
            <div class="gm-mini-xp"><i class="fas fa-star"></i> Complete: 20 XP</div>
        </a>

    </div>
</section>

<!-- ══════════════════════════════════════════════════════════════
     DEVELOPER CTA
     ══════════════════════════════════════════════════════════════ -->
<section class="gm-section">
    <div class="gm-dev-cta">
        <h2>🛠️ Build a Game for GoSiteMe</h2>
        <p>Got a game idea? We provide the platform — Three.js, WebXR, AI agents, voice APIs, multiplayer infrastructure, and a global audience. Ship your game and reach players worldwide.</p>

        <div class="gm-dev-features">
            <div class="gm-dev-feat"><i class="fas fa-check-circle"></i> Three.js & WebXR ready</div>
            <div class="gm-dev-feat"><i class="fas fa-check-circle"></i> 20+ AI agent personalities</div>
            <div class="gm-dev-feat"><i class="fas fa-check-circle"></i> Voice & chat APIs</div>
            <div class="gm-dev-feat"><i class="fas fa-check-circle"></i> Real-time multiplayer</div>
            <div class="gm-dev-feat"><i class="fas fa-check-circle"></i> XP & leaderboard system</div>
            <div class="gm-dev-feat"><i class="fas fa-check-circle"></i> Revenue sharing</div>
            <div class="gm-dev-feat"><i class="fas fa-check-circle"></i> Solana ticketing & payments</div>
            <div class="gm-dev-feat"><i class="fas fa-check-circle"></i> SSP Music & Events APIs</div>
        </div>

        <div class="gm-dev-btns">
            <a href="/developer-portal.php" class="gm-btn-dev primary"><i class="fas fa-code"></i> Developer Portal</a>
            <a href="/sdks.php" class="gm-btn-dev secondary"><i class="fas fa-book"></i> View SDKs & Docs</a>
            <a href="/open-source/" class="gm-btn-dev secondary"><i class="fab fa-github"></i> Open Source</a>
        </div>
    </div>
</section>

</div><!-- .gm-content -->

<!-- ══════════════════════════════════════════════════════════════
     FINAL CTA
     ══════════════════════════════════════════════════════════════ -->
<div class="gm-final">
    <h2>Ready to <span>Play</span>?</h2>
    <p>Jump into the arena — no downloads, no installs. Just open and play.</p>
    <a href="/vr/chess/" class="gm-btn-dev primary"><i class="fas fa-chess"></i> Launch Chess Arena</a>
    <a href="/vr/dj-studio/" class="gm-btn-dev primary" style="margin-left:12px; background:linear-gradient(135deg,#6c5ce7,#00cec9);"><i class="fas fa-headphones-alt"></i> Launch DJ Studio</a>
    <a href="/vr/sanctuary/" class="gm-btn-dev primary" style="margin-left:12px; background:linear-gradient(135deg,#d4a54a,#b8862d);"><i class="fas fa-cross"></i> Enter Sanctuary</a>
</div>

<script src="/assets/js/games-engine.js"></script>
<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
