<?php
$page_title = 'Game Lobby — Live Discovery — GoSiteMe';
$page_description = 'Discover what\'s live right now. Join games, watch matches, challenge AI agents, and find active players in real-time.';
$page_canonical = 'https://root.com/game-lobby.php';
$page_og_title = 'GoSiteMe Game Lobby — Live Discovery';
$page_og_description = 'See what\'s live, who\'s playing, and jump into games instantly. Real-time game lobby with AI agents.';
$page_og_image = 'https://root.com/assets/images/og-games.png';
$page_og_image_alt = 'GoSiteMe Game Lobby';
$page_twitter_description = 'Live game lobby — discover active games, challenge AI agents, and join matches in real-time.';

include __DIR__ . '/includes/site-header.inc.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$is_logged_in = !empty($_SESSION['logged_in']) && !empty($_SESSION['client_id']);
?>

<style>
:root {
    --gl-bg: #0a0a14;
    --gl-surface: #12121e;
    --gl-surface-2: #1a1a2e;
    --gl-border: rgba(255,255,255,0.08);
    --gl-accent: #7D00FF;
    --gl-accent-2: #00D4FF;
    --gl-green: #34d399;
    --gl-gold: #fbbf24;
    --gl-red: #f87171;
    --gl-text: #e8e8f0;
    --gl-text-muted: #8a8a9a;
    --gl-radius: 16px;
}

* { margin: 0; padding: 0; box-sizing: border-box; }

.gl-page {
    background: var(--gl-bg);
    color: var(--gl-text);
    min-height: 100vh;
    font-family: 'Inter', system-ui, sans-serif;
}

/* ── Hero ── */
.gl-hero {
    position: relative;
    padding: 130px 2rem 3rem;
    text-align: center;
    overflow: hidden;
    background: radial-gradient(ellipse at 30% 0%, rgba(125,0,255,0.18) 0%, transparent 60%),
                radial-gradient(ellipse at 70% 100%, rgba(0,212,255,0.1) 0%, transparent 60%),
                var(--gl-bg);
}
.gl-hero h1 {
    font-size: clamp(2rem, 5vw, 3rem);
    font-weight: 800;
    margin-bottom: 0.5rem;
}
.gl-hero h1 span {
    background: linear-gradient(135deg, var(--gl-accent), var(--gl-accent-2));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.gl-hero .tagline {
    color: var(--gl-text-muted);
    font-size: 1.1rem;
    max-width: 550px;
    margin: 0 auto;
}

/* ── Live Pulse Bar ── */
.gl-pulse-bar {
    display: flex;
    justify-content: center;
    gap: 2rem;
    flex-wrap: wrap;
    padding: 1.5rem 2rem;
    background: var(--gl-surface);
    border-top: 1px solid var(--gl-border);
    border-bottom: 1px solid var(--gl-border);
}
.gl-pulse-stat {
    text-align: center;
}
.gl-pulse-stat .num {
    font-size: 1.8rem;
    font-weight: 800;
    font-family: 'JetBrains Mono', monospace;
}
.gl-pulse-stat .num.online { color: var(--gl-green); }
.gl-pulse-stat .num.playing { color: var(--gl-accent-2); }
.gl-pulse-stat .num.agents { color: var(--gl-gold); }
.gl-pulse-stat .num.games { color: var(--gl-accent); }
.gl-pulse-stat .label {
    font-size: 0.75rem;
    color: var(--gl-text-muted);
    text-transform: uppercase;
    letter-spacing: 1px;
}
.gl-live-dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    background: var(--gl-green);
    border-radius: 50%;
    margin-right: 4px;
    animation: glPulse 2s ease-in-out infinite;
}
@keyframes glPulse {
    0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(52,211,153,0.4); }
    50% { opacity: 0.7; box-shadow: 0 0 0 6px rgba(52,211,153,0); }
}

/* ── Content ── */
.gl-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

/* ── Tabs ── */
.gl-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 2rem;
    flex-wrap: wrap;
}
.gl-tab {
    background: var(--gl-surface);
    border: 1px solid var(--gl-border);
    color: var(--gl-text-muted);
    padding: 10px 20px;
    border-radius: 999px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}
.gl-tab:hover { border-color: var(--gl-accent); color: var(--gl-text); }
.gl-tab.active {
    background: linear-gradient(135deg, var(--gl-accent), var(--gl-accent-2));
    border-color: transparent;
    color: #fff;
}

/* ── Game Cards ── */
.gl-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
}
.gl-card {
    background: var(--gl-surface);
    border: 1px solid var(--gl-border);
    border-radius: var(--gl-radius);
    overflow: hidden;
    transition: transform 0.2s, border-color 0.2s;
    cursor: pointer;
}
.gl-card:hover {
    transform: translateY(-4px);
    border-color: rgba(125,0,255,0.3);
}
.gl-card-hero {
    height: 160px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 4rem;
    position: relative;
}
.gl-card-hero .gl-live-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    background: rgba(52,211,153,0.15);
    border: 1px solid rgba(52,211,153,0.3);
    color: var(--gl-green);
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 0.7rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 4px;
}
.gl-card-hero .gl-type-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.gl-type-badge.vr { background: rgba(125,0,255,0.2); border: 1px solid rgba(125,0,255,0.3); color: #c4b5fd; }
.gl-type-badge.classic { background: rgba(0,212,255,0.15); border: 1px solid rgba(0,212,255,0.3); color: var(--gl-accent-2); }
.gl-type-badge.voice { background: rgba(251,191,36,0.15); border: 1px solid rgba(251,191,36,0.3); color: var(--gl-gold); }
.gl-type-badge.wager { background: rgba(248,113,113,0.15); border: 1px solid rgba(248,113,113,0.3); color: var(--gl-red); }

.gl-card-body {
    padding: 1.2rem;
}
.gl-card-body h3 {
    font-size: 1.1rem;
    font-weight: 700;
    margin-bottom: 0.3rem;
}
.gl-card-body .desc {
    color: var(--gl-text-muted);
    font-size: 0.85rem;
    line-height: 1.5;
    margin-bottom: 0.8rem;
}
.gl-card-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.75rem;
    color: var(--gl-text-muted);
    margin-bottom: 1rem;
}
.gl-card-meta span { display: flex; align-items: center; gap: 4px; }

/* ── Agent Avatars ── */
.gl-agents-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
}
.gl-agent-pip {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    border: 2px solid var(--gl-bg);
    cursor: default;
}
.gl-agent-pip:not(:first-child) { margin-left: -8px; }
.gl-agents-label {
    font-size: 0.7rem;
    color: var(--gl-text-muted);
}

/* ── Tags ── */
.gl-tags {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    margin-bottom: 1rem;
}
.gl-tag {
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 0.65rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.gl-tag.strategy { background: rgba(125,0,255,0.15); color: #c4b5fd; }
.gl-tag.multiplayer { background: rgba(0,212,255,0.15); color: var(--gl-accent-2); }
.gl-tag.ai { background: rgba(251,191,36,0.15); color: var(--gl-gold); }
.gl-tag.webxr { background: rgba(52,211,153,0.15); color: var(--gl-green); }
.gl-tag.wager { background: rgba(248,113,113,0.15); color: var(--gl-red); }
.gl-tag.social { background: rgba(236,72,153,0.15); color: #f472b6; }

/* ── CTAs ── */
.gl-card-actions {
    display: flex;
    gap: 0.5rem;
}
.gl-btn-play {
    flex: 1;
    padding: 10px;
    border: none;
    border-radius: 10px;
    font-weight: 700;
    font-size: 0.85rem;
    cursor: pointer;
    text-decoration: none;
    text-align: center;
    transition: all 0.2s;
    background: linear-gradient(135deg, var(--gl-accent), var(--gl-accent-2));
    color: #fff;
}
.gl-btn-play:hover { filter: brightness(1.1); transform: translateY(-1px); }
.gl-btn-secondary {
    padding: 10px 16px;
    border: 1px solid var(--gl-border);
    border-radius: 10px;
    background: transparent;
    color: var(--gl-text-muted);
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    text-decoration: none;
    text-align: center;
    transition: all 0.2s;
}
.gl-btn-secondary:hover { border-color: var(--gl-accent); color: var(--gl-text); }

/* ── Agent Roster ── */
.gl-agent-roster {
    background: var(--gl-surface);
    border: 1px solid var(--gl-border);
    border-radius: var(--gl-radius);
    padding: 1.5rem;
    margin-bottom: 2rem;
}
.gl-agent-roster h2 {
    font-size: 1.2rem;
    margin-bottom: 1rem;
}
.gl-roster-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
}
.gl-roster-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    border-radius: 10px;
    background: var(--gl-surface-2);
    transition: background 0.2s;
}
.gl-roster-item:hover { background: rgba(125,0,255,0.1); }
.gl-roster-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}
.gl-roster-info .name { font-weight: 700; font-size: 0.9rem; }
.gl-roster-info .elo { font-size: 0.75rem; color: var(--gl-text-muted); }
.gl-roster-info .status {
    font-size: 0.7rem;
    display: flex;
    align-items: center;
    gap: 4px;
}
.gl-roster-info .status.active { color: var(--gl-green); }
.gl-roster-info .status.idle { color: var(--gl-gold); }

/* ── Quick Nav ── */
.gl-quick-nav {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    margin-bottom: 2rem;
}
.gl-quick-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 999px;
    background: var(--gl-surface);
    border: 1px solid var(--gl-border);
    color: var(--gl-text-muted);
    font-size: 0.8rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}
.gl-quick-link:hover { border-color: var(--gl-accent); color: var(--gl-text); }

/* ── Section Headers ── */
.gl-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}
.gl-section-header h2 {
    font-size: 1.4rem;
    font-weight: 800;
}
.gl-section-header h2 span {
    background: linear-gradient(135deg, var(--gl-accent), var(--gl-accent-2));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* ── Featured Banner ── */
.gl-featured {
    background: linear-gradient(135deg, rgba(125,0,255,0.15), rgba(0,212,255,0.1));
    border: 1px solid rgba(125,0,255,0.2);
    border-radius: var(--gl-radius);
    padding: 2rem;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 2rem;
    flex-wrap: wrap;
}
.gl-featured-icon { font-size: 4rem; }
.gl-featured-info { flex: 1; min-width: 250px; }
.gl-featured-info h3 { font-size: 1.3rem; margin-bottom: 0.3rem; }
.gl-featured-info p { color: var(--gl-text-muted); font-size: 0.9rem; margin-bottom: 1rem; }
.gl-featured-stats { display: flex; gap: 1.5rem; margin-bottom: 1rem; }
.gl-featured-stats div { text-align: center; }
.gl-featured-stats .fnum { font-size: 1.3rem; font-weight: 800; font-family: 'JetBrains Mono', monospace; color: var(--gl-accent-2); }
.gl-featured-stats .flabel { font-size: 0.7rem; color: var(--gl-text-muted); text-transform: uppercase; }

@media (max-width: 768px) {
    .gl-grid { grid-template-columns: 1fr; }
    .gl-pulse-bar { gap: 1rem; }
    .gl-pulse-stat .num { font-size: 1.3rem; }
    .gl-featured { flex-direction: column; text-align: center; }
    .gl-roster-grid { grid-template-columns: 1fr 1fr; }
}
</style>

<div class="gl-page">

<!-- Hero -->
<div class="gl-hero">
    <h1><span>Game Lobby</span> — Live Discovery</h1>
    <p class="tagline">See what's happening right now. Find active games, challenge AI agents, or watch live matches.</p>
</div>

<!-- Live Pulse Bar -->
<div class="gl-pulse-bar">
    <div class="gl-pulse-stat">
        <div class="num online" id="glOnline"><span class="gl-live-dot"></span>0</div>
        <div class="label">Online Now</div>
    </div>
    <div class="gl-pulse-stat">
        <div class="num playing" id="glPlaying">0</div>
        <div class="label">In Games</div>
    </div>
    <div class="gl-pulse-stat">
        <div class="num agents" id="glAgents">8</div>
        <div class="label">AI Agents</div>
    </div>
    <div class="gl-pulse-stat">
        <div class="num games" id="glActiveGames">0</div>
        <div class="label">Active Matches</div>
    </div>
</div>

<div class="gl-content">

<!-- Quick Navigation -->
<div class="gl-quick-nav">
    <a href="/games.php" class="gl-quick-link">🎮 All Games</a>
    <a href="/game-leaderboard.php" class="gl-quick-link">🏆 Leaderboard</a>
    <a href="/predictions.php" class="gl-quick-link">📊 Predictions</a>
    <a href="/wallet.php" class="gl-quick-link">💰 Wallet</a>
    <a href="/nft-trophies.php" class="gl-quick-link">🏅 NFT Trophies</a>
    <a href="/vr-worlds.php" class="gl-quick-link">🌍 VR Worlds</a>
</div>

<!-- Featured Game -->
<div class="gl-featured" id="glFeatured">
    <div class="gl-featured-icon">♟️</div>
    <div class="gl-featured-info">
        <h3>Featured: AI Chess Arena</h3>
        <p>Challenge 8 unique AI agents with distinct personalities and strategies. ELO-rated matches, GSM wagers, and Hall of Fame rankings.</p>
        <div class="gl-featured-stats">
            <div><div class="fnum" id="glFeatViewers">0</div><div class="flabel">Watching</div></div>
            <div><div class="fnum" id="glFeatPlayers">0</div><div class="flabel">Playing</div></div>
            <div><div class="fnum">8</div><div class="flabel">AI Agents</div></div>
        </div>
        <a href="/vr/chess/" class="gl-btn-play" style="display:inline-block;width:auto;padding:10px 30px;">Play Now →</a>
    </div>
</div>

<!-- Filter Tabs -->
<div class="gl-tabs">
    <button class="gl-tab active" data-filter="all">All Games</button>
    <button class="gl-tab" data-filter="vr">🥽 VR / 3D</button>
    <button class="gl-tab" data-filter="classic">🎲 Classic</button>
    <button class="gl-tab" data-filter="voice">🎤 Voice</button>
    <button class="gl-tab" data-filter="wager">💰 Wager</button>
    <button class="gl-tab" data-filter="live">🔴 Live Now</button>
</div>

<!-- Agent Roster -->
<div class="gl-agent-roster" id="glAgentRoster">
    <h2>🤖 AI Agent Roster — <span style="color:var(--gl-green);font-size:0.9rem;">All Online</span></h2>
    <div class="gl-roster-grid" id="glRosterGrid">
        <!-- Populated by JS -->
    </div>
</div>

<!-- Games Grid -->
<div class="gl-section-header">
    <h2>🎮 <span>Playable Now</span></h2>
</div>
<div class="gl-grid" id="glGameGrid">
    <!-- Populated by JS -->
</div>

</div><!-- /gl-content -->
</div><!-- /gl-page -->

<script>
(function(){
    'use strict';

    /* ── Agent Data ── */
    const AGENTS = [
        { id:'cipher', name:'Cipher', icon:'🔐', elo:1500, style:'Aggressive', color:'#f87171', games:['chess','dj-studio'] },
        { id:'alfred', name:'Alfred', icon:'🤖', elo:1400, style:'Positional', color:'#60a5fa', games:['chess','backgammon'] },
        { id:'architect', name:'Architect', icon:'🏛️', elo:1380, style:'Strategic', color:'#a78bfa', games:['backgammon','pool'] },
        { id:'nova', name:'Nova', icon:'⚡', elo:1350, style:'Aggressive', color:'#fbbf24', games:['chess','pool'] },
        { id:'atlas', name:'Atlas', icon:'🗺️', elo:1300, style:'Tactical', color:'#34d399', games:['pool','speed-dating'] },
        { id:'sage', name:'Sage', icon:'🌿', elo:1250, style:'Defensive', color:'#86efac', games:['checkers','backgammon'] },
        { id:'pulse', name:'Pulse', icon:'💗', elo:1200, style:'Balanced', color:'#f472b6', games:['dj-studio','speed-dating'] },
        { id:'pierre', name:'Pierre', icon:'🎭', elo:1150, style:'Cautious', color:'#c084fc', games:['sanctuary','backgammon'] }
    ];

    /* ── Game Database ── */
    const GAMES = [
        { id:'chess',       title:'AI Chess Arena',       icon:'♟️', path:'/vr/chess/',            type:'vr', wager:true,  desc:'8 AI agents, ELO-rated, WebXR, Hall of Fame, GSM wagers', tags:['Strategy','AI','WebXR','Wager'], agents:['cipher','alfred','nova'] },
        { id:'chess-masters',title:'Chess Masters VR',    icon:'♚', path:'/vr/chess-masters/',     type:'vr', wager:true,  desc:'Fireplace ambiance, 20 AI personalities, spatial audio',  tags:['Strategy','AI','WebXR'], agents:['cipher','alfred'] },
        { id:'checkers',    title:'3D Checkers',          icon:'⬛', path:'/vr/checkers/',          type:'vr', wager:true,  desc:'4 difficulty levels, 4 themes, WebXR support',            tags:['Strategy','AI','WebXR'], agents:['sage'] },
        { id:'poker',       title:'Texas Hold\'em VR',    icon:'🃏', path:'/vr/poker/',             type:'vr', wager:true,  desc:'6 AI opponents, 4 game modes, realistic physics',         tags:['Strategy','AI','Multiplayer'], agents:['architect','nova'] },
        { id:'pool',        title:'Pool / Billiards',     icon:'🎱', path:'/vr/pool/',              type:'vr', wager:true,  desc:'Physics-based with trajectory prediction and ghost ball',  tags:['Sports','AI','WebXR'], agents:['architect','atlas'] },
        { id:'racing',      title:'VR Racing Track',      icon:'🏎️', path:'/vr/racing/',            type:'vr', wager:true,  desc:'4 vehicles, 8 AI racers, real-time competition',          tags:['Racing','AI','WebXR','Wager'], agents:['nova','atlas'] },
        { id:'command',     title:'Command & Conquer',    icon:'⚔️', path:'/vr/command-and-conquer/', type:'vr', wager:true, desc:'Real-time strategy, base building, army management',      tags:['Strategy','Multiplayer','AI'], agents:['architect','cipher'] },
        { id:'kingdom',     title:'Kingdom',              icon:'🏰', path:'/vr/kingdom/',           type:'vr', wager:false, desc:'Explore the kingdom, quests, adventure world',            tags:['Adventure','WebXR'], agents:['pierre'] },
        { id:'backgammon',  title:'Backgammon',           icon:'🎲', path:'/games.php#backgammon',  type:'classic', wager:true,  desc:'Doubling cube, AI opponents, GSM wagering',           tags:['Strategy','AI','Wager'], agents:['alfred','architect','sage','pierre'] },
        { id:'chess-2d',    title:'Classic Chess',        icon:'♔', path:'/games.php#chess',       type:'classic', wager:true,  desc:'Stockfish-powered AI, multiple difficulty levels',     tags:['Strategy','AI'], agents:['cipher','alfred'] },
        { id:'voice-chess', title:'Voice Chess',          icon:'🎤', path:'/games.php#voice-chess', type:'voice',   wager:false, desc:'Play chess entirely by voice with Alfred',            tags:['Voice','AI'], agents:['alfred'] },
        { id:'voice-trivia',title:'Voice Trivia',         icon:'❓', path:'/games.php#voice-trivia',type:'voice',   wager:false, desc:'Test your knowledge with voice-powered trivia',       tags:['Voice','Trivia'], agents:['alfred'] },
        { id:'voice-20q',   title:'20 Questions',         icon:'🔮', path:'/games.php#voice-20q',   type:'voice',   wager:false, desc:'Classic guessing game, voice-powered',                tags:['Voice','Social'], agents:['alfred'] },
        { id:'voice-words', title:'Word Association',     icon:'💬', path:'/games.php#voice-words', type:'voice',   wager:false, desc:'Fast-paced word chain game via voice',                 tags:['Voice','Social'], agents:['pulse'] },
        { id:'voice-riddle',title:'Riddle Challenge',     icon:'🧩', path:'/games.php#voice-riddle',type:'voice',   wager:false, desc:'Solve riddles spoken by Alfred AI',                    tags:['Voice','Puzzle'], agents:['sage'] },
        { id:'voice-madlib',title:'Mad Libs',             icon:'📝', path:'/games.php#voice-madlib',type:'voice',   wager:false, desc:'Collaborative storytelling with voice input',           tags:['Voice','Social'], agents:['pulse','pierre'] },
        { id:'dj-studio',   title:'DJ Studio',            icon:'🎧', path:'/vr/dj-studio/',         type:'vr',      wager:false, desc:'16 venues, 53+ tracks, live mixing, AI DJs',          tags:['Music','Social','WebXR'], agents:['cipher','pulse'] },
        { id:'speed-dating',title:'Speed Dating VR',      icon:'💕', path:'/vr/speed-dating/',      type:'vr',      wager:false, desc:'Video dating with face filters, voice-only mode',      tags:['Social','WebXR'], agents:['atlas','pulse'] }
    ];

    /* ── Render Agent Roster ── */
    function renderRoster(presence) {
        const grid = document.getElementById('glRosterGrid');
        grid.innerHTML = AGENTS.map(a => {
            const p = presence?.[a.id];
            const statusText = p?.game ? `Playing ${p.game}` : 'Ready to play';
            const statusClass = p?.game ? 'active' : 'idle';
            return `<div class="gl-roster-item">
                <div class="gl-roster-icon" style="background:${a.color}22;border:2px solid ${a.color}">${a.icon}</div>
                <div class="gl-roster-info">
                    <div class="name">${a.name}</div>
                    <div class="elo">ELO ${a.elo} · ${a.style}</div>
                    <div class="status ${statusClass}"><span class="gl-live-dot" style="background:${statusClass==='active'?'var(--gl-green)':'var(--gl-gold)'}"></span>${statusText}</div>
                </div>
            </div>`;
        }).join('');
    }

    /* ── Render Game Cards ── */
    function renderGames(filter, liveData) {
        const grid = document.getElementById('glGameGrid');
        let games = GAMES;
        if (filter && filter !== 'all') {
            if (filter === 'live') {
                games = games.filter(g => (liveData?.[g.id]?.players || 0) > 0 || (liveData?.[g.id]?.viewers || 0) > 0);
            } else if (filter === 'wager') {
                games = games.filter(g => g.wager);
            } else {
                games = games.filter(g => g.type === filter);
            }
        }

        grid.innerHTML = games.map(g => {
            const live = liveData?.[g.id] || {};
            const viewers = live.viewers || 0;
            const players = live.players || 0;
            const isLive = viewers > 0 || players > 0;

            const gameAgents = AGENTS.filter(a => g.agents.includes(a.id));
            const agentPips = gameAgents.map(a =>
                `<div class="gl-agent-pip" style="background:${a.color}33;border-color:${a.color}" title="${a.name} (ELO ${a.elo})">${a.icon}</div>`
            ).join('');

            const bgGrad = g.type === 'vr' ? 'linear-gradient(135deg,rgba(125,0,255,0.2),rgba(0,212,255,0.1))' :
                           g.type === 'voice' ? 'linear-gradient(135deg,rgba(251,191,36,0.2),rgba(248,113,113,0.1))' :
                           'linear-gradient(135deg,rgba(0,212,255,0.15),rgba(52,211,153,0.1))';

            const typeLabel = g.type === 'vr' ? 'VR / 3D' : g.type === 'voice' ? 'Voice' : 'Classic';

            return `<div class="gl-card" data-type="${g.type}" data-wager="${g.wager}">
                <div class="gl-card-hero" style="background:${bgGrad}">
                    ${g.icon}
                    <span class="gl-type-badge ${g.type}">${typeLabel}</span>
                    ${isLive ? `<span class="gl-live-badge"><span class="gl-live-dot"></span>${players} playing · ${viewers} watching</span>` : ''}
                </div>
                <div class="gl-card-body">
                    <h3>${g.title}</h3>
                    <p class="desc">${g.desc}</p>
                    <div class="gl-card-meta">
                        <span>👥 ${players} players</span>
                        <span>👁️ ${viewers} viewers</span>
                        ${g.wager ? '<span>💰 Wagers</span>' : ''}
                    </div>
                    <div class="gl-agents-row">
                        ${agentPips}
                        <span class="gl-agents-label">${gameAgents.length} agent${gameAgents.length!==1?'s':''} available</span>
                    </div>
                    <div class="gl-tags">
                        ${g.tags.map(t => `<span class="gl-tag ${t.toLowerCase()}">${t}</span>`).join('')}
                    </div>
                    <div class="gl-card-actions">
                        <a href="${g.path}" class="gl-btn-play">Play Now →</a>
                        ${g.wager ? `<a href="/game-leaderboard.php" class="gl-btn-secondary">🏆</a>` : ''}
                    </div>
                </div>
            </div>`;
        }).join('');

        if (games.length === 0) {
            grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:3rem;color:var(--gl-text-muted);">No games match this filter right now.</div>';
        }
    }

    /* ── Tab Filtering ── */
    let currentFilter = 'all';
    let currentLiveData = {};

    document.querySelectorAll('.gl-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.gl-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            currentFilter = tab.dataset.filter;
            renderGames(currentFilter, currentLiveData);
        });
    });

    /* ── Live Stats Polling ── */
    async function fetchLiveStats() {
        try {
            const res = await fetch('/api/game-ecosystem.php?action=live-stats');
            const data = await res.json();
            if (data.success && data.data) {
                const d = data.data;
                document.getElementById('glOnline').innerHTML = `<span class="gl-live-dot"></span>${d.platform?.total_online || 0}`;
                document.getElementById('glPlaying').textContent = d.platform?.total_games || 0;
                document.getElementById('glActiveGames').textContent = Object.values(d.games || {}).reduce((s, g) => s + (g.players || 0), 0);

                currentLiveData = d.games || {};
                renderGames(currentFilter, currentLiveData);

                // Update featured
                if (d.games?.chess) {
                    document.getElementById('glFeatViewers').textContent = d.games.chess.viewers || 0;
                    document.getElementById('glFeatPlayers').textContent = d.games.chess.players || 0;
                }
            }
        } catch(e) { /* silent */ }
    }

    async function fetchAgentPresence() {
        try {
            const res = await fetch('/api/game-ecosystem.php?action=agent-presence');
            const data = await res.json();
            if (data.success) {
                const presenceMap = {};
                (data.data?.agents || []).forEach(a => {
                    presenceMap[a.agent_id] = a;
                });
                renderRoster(presenceMap);
            }
        } catch(e) { renderRoster({}); }
    }

    async function sendHeartbeat() {
        try {
            await fetch('/api/game-ecosystem.php?action=heartbeat', {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({game:'lobby', role:'viewer'})
            });
        } catch(e) { /* silent */ }
    }

    /* ── Init ── */
    renderRoster({});
    renderGames('all', {});
    fetchLiveStats();
    fetchAgentPresence();
    sendHeartbeat();

    setInterval(fetchLiveStats, 15000);
    setInterval(fetchAgentPresence, 30000);
    setInterval(sendHeartbeat, 30000);
})();
</script>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
