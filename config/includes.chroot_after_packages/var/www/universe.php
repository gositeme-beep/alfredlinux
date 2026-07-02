<?php
$page_title = 'GoSiteMe Universe — Everything in One App';
$page_description = 'The universal app container. Pulse social, Veil encrypted messaging, Alfred Browser, AI search, crypto wallet, voice agents, VR worlds, and Alfred IDE - all in one unified interface.';
$page_canonical = 'https://root.com/universe';
require_once __DIR__ . '/includes/auth-gate.inc.php';
$pageTitle = 'GoSiteMe Universe';
include __DIR__ . '/includes/site-header.inc.php';
?>
<style>
:root {
    --uni-bg: #06060e;
    --uni-surface: #0d0d1a;
    --uni-surface-2: #151528;
    --uni-surface-3: #1e1e38;
    --uni-border: rgba(255,255,255,.06);
    --uni-glow-purple: #7c5ce7;
    --uni-glow-cyan: #22d3ee;
    --uni-glow-green: #10b981;
    --uni-glow-blue: #3b82f6;
    --uni-glow-gold: #fbbf24;
    --uni-glow-red: #ef4444;
    --uni-glow-pink: #f472b6;
    --uni-text: #e8e8f0;
    --uni-muted: #8888a0;
    --uni-radius: 16px;
}
body { background: var(--uni-bg); color: var(--uni-text); }

/* ── Container ── */
.uni-wrap { max-width: 1400px; margin: 0 auto; padding: 2rem 1.5rem; }

/* ── Hero ── */
.uni-hero { text-align: center; padding: 3rem 0 2rem; position: relative; }
.uni-hero::before { content: ''; position: absolute; top: 50%; left: 50%; width: 600px; height: 600px; background: radial-gradient(circle, rgba(124,92,231,.1) 0%, transparent 70%); transform: translate(-50%,-50%); pointer-events: none; }
.uni-hero h1 { font-size: 2.5rem; font-weight: 900; margin: 0 0 .75rem; background: linear-gradient(135deg, #7c5ce7, #22d3ee, #10b981); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.uni-hero p { color: var(--uni-muted); font-size: 1.1rem; max-width: 700px; margin: 0 auto 1.5rem; line-height: 1.6; }
.uni-hero-badge { display: inline-flex; align-items: center; gap: 8px; padding: .5rem 1.25rem; border-radius: 2rem; background: linear-gradient(135deg, rgba(124,92,231,.15), rgba(34,211,238,.1)); border: 1px solid rgba(124,92,231,.3); color: #a78bfa; font-size: .85rem; font-weight: 600; }

/* ── App Grid ── */
.uni-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 1rem; margin: 2rem 0; }
.uni-app { position: relative; background: var(--uni-surface); border: 1px solid var(--uni-border); border-radius: var(--uni-radius); padding: 1.75rem 1.25rem; text-align: center; text-decoration: none; color: var(--uni-text); transition: all .3s ease; cursor: pointer; overflow: hidden; }
.uni-app::before { content: ''; position: absolute; inset: 0; border-radius: var(--uni-radius); opacity: 0; transition: opacity .3s; background: radial-gradient(circle at center, var(--app-glow, rgba(124,92,231,.08)), transparent 70%); }
.uni-app:hover { border-color: rgba(255,255,255,.15); transform: translateY(-4px); box-shadow: 0 12px 40px var(--app-glow, rgba(124,92,231,.15)); }
.uni-app:hover::before { opacity: 1; }
.uni-app-icon { width: 56px; height: 56px; border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto .75rem; font-size: 1.4rem; color: #fff; position: relative; z-index: 1; }
.uni-app h3 { font-size: .95rem; font-weight: 700; margin: 0 0 .35rem; position: relative; z-index: 1; }
.uni-app p { font-size: .75rem; color: var(--uni-muted); margin: 0; line-height: 1.4; position: relative; z-index: 1; }
.uni-app .uni-badge { position: absolute; top: 10px; right: 10px; font-size: .6rem; font-weight: 700; padding: .15rem .5rem; border-radius: 1rem; z-index: 2; }

/* ── Featured Apps (larger) ── */
.uni-featured { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.25rem; margin: 2rem 0; }
.uni-featured .uni-app { padding: 2rem 1.5rem; }
.uni-featured .uni-app-icon { width: 64px; height: 64px; font-size: 1.6rem; }
.uni-featured .uni-app h3 { font-size: 1.1rem; }

/* ── Section Headers ── */
.uni-section { margin: 3rem 0 1rem; }
.uni-section h2 { font-size: 1.25rem; font-weight: 700; margin: 0 0 .25rem; display: flex; align-items: center; gap: .5rem; }
.uni-section h2 i { font-size: 1rem; color: var(--uni-glow-purple); }
.uni-section p { color: var(--uni-muted); font-size: .85rem; margin: 0; }

/* ── Quick Launch Bar ── */
.uni-launch { display: flex; align-items: center; gap: .75rem; padding: 1rem 1.5rem; background: var(--uni-surface); border: 1px solid var(--uni-border); border-radius: 14px; margin: 1.5rem 0; overflow-x: auto; }
.uni-launch-btn { flex-shrink: 0; display: flex; align-items: center; gap: .5rem; padding: .5rem 1rem; border-radius: 10px; background: var(--uni-surface-2); border: 1px solid var(--uni-border); color: var(--uni-text); text-decoration: none; font-size: .8rem; font-weight: 600; transition: all .2s; white-space: nowrap; }
.uni-launch-btn:hover { border-color: rgba(124,92,231,.5); background: rgba(124,92,231,.1); color: #c084fc; }
.uni-launch-btn i { font-size: .9rem; }

/* ── Stats Bar ── */
.uni-stats { display: flex; gap: 2rem; justify-content: center; padding: 1.5rem; margin: 1rem 0 2rem; background: var(--uni-surface); border: 1px solid var(--uni-border); border-radius: 14px; flex-wrap: wrap; }
.uni-stat { text-align: center; }
.uni-stat-val { font-size: 1.5rem; font-weight: 800; background: linear-gradient(135deg, #7c5ce7, #22d3ee); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.uni-stat-label { font-size: .7rem; color: var(--uni-muted); text-transform: uppercase; letter-spacing: .5px; }

@media (max-width: 960px) { .uni-featured { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 600px) {
    .uni-featured { grid-template-columns: 1fr; }
    .uni-hero h1 { font-size: 1.75rem; }
    .uni-stats { gap: 1rem; }
}
</style>

<div class="uni-wrap">
    <!-- Hero -->
    <div class="uni-hero">
        <div class="uni-hero-badge"><i class="fas fa-atom"></i> The Universal App Container</div>
        <h1>GoSiteMe Universe</h1>
        <p>Every product. Every tool. Every world. One unified interface. This is the super-app where Pulse, Veil, Alfred, Crypto, Search, VR, Voice, and the IDE all live together.</p>
    </div>

    <!-- Stats -->
    <div class="uni-stats">
        <div class="uni-stat"><div class="uni-stat-val">8</div><div class="uni-stat-label">Core Pillars</div></div>
        <div class="uni-stat"><div class="uni-stat-val">13,000+</div><div class="uni-stat-label">AI Tools</div></div>
        <div class="uni-stat"><div class="uni-stat-val">100</div><div class="uni-stat-label">AI Agents</div></div>
        <div class="uni-stat"><div class="uni-stat-val">14</div><div class="uni-stat-label">VR Worlds</div></div>
        <div class="uni-stat"><div class="uni-stat-val">29</div><div class="uni-stat-label">Voice Products</div></div>
        <div class="uni-stat"><div class="uni-stat-val">PQ</div><div class="uni-stat-label">Encrypted</div></div>
        <div class="uni-stat"><div class="uni-stat-val">50K</div><div class="uni-stat-label">Health Agents</div></div>
    </div>

    <!-- Quick Launch -->
    <div class="uni-launch">
        <span style="color:var(--uni-muted);font-size:.8rem;font-weight:600;white-space:nowrap;"><i class="fas fa-rocket"></i> Quick Launch:</span>
        <a href="/pulse.php" class="uni-launch-btn"><i class="fas fa-bolt" style="color:#3b82f6;"></i> Pulse</a>
        <a href="/veil/" class="uni-launch-btn"><i class="fas fa-shield-halved" style="color:#8b5cf6;"></i> Veil</a>
        <a href="/alfred.php" class="uni-launch-btn"><i class="fas fa-robot" style="color:#c084fc;"></i> Alfred AI</a>
        <a href="/search.php" class="uni-launch-btn"><i class="fas fa-magnifying-glass" style="color:#22d3ee;"></i> Search</a>
        <a href="/alfred-browser.php" class="uni-launch-btn"><i class="fas fa-globe" style="color:#3b82f6;"></i> Browser</a>
        <a href="/pay/account/crypto" class="uni-launch-btn"><i class="fas fa-coins" style="color:#fbbf24;"></i> Crypto</a>
        <a href="/alfred-ide.php" class="uni-launch-btn"><i class="fas fa-code" style="color:#00d4ff;"></i> Alfred IDE</a>
        <a href="/voice-products.php" class="uni-launch-btn"><i class="fas fa-phone-volume" style="color:#10b981;"></i> Voice</a>
        <a href="/health-research.php" class="uni-launch-btn"><i class="fas fa-dna" style="color:#10b981;"></i> Health</a>
        <a href="/dashboard" class="uni-launch-btn"><i class="fas fa-gauge-high" style="color:#f97316;"></i> Dashboard</a>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         SECTION 1: THE 9 PILLARS — Featured Core Apps
         ═══════════════════════════════════════════════════════════ -->
    <div class="uni-section">
        <h2><i class="fas fa-layer-group"></i> The Nine Pillars</h2>
        <p>The core products that power the GoSiteMe universe</p>
    </div>

    <div class="uni-featured">
        <!-- Pulse -->
        <a href="/pulse.php" class="uni-app" style="--app-glow:rgba(59,130,246,.15);">
            <div class="uni-badge" style="background:rgba(59,130,246,.15);color:#60a5fa;border:1px solid rgba(59,130,246,.3);">LIVE</div>
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#3b82f6,#60a5fa);"><i class="fas fa-bolt"></i></div>
            <h3>Pulse</h3>
            <p>Social network. Feed, agents, VR, games, crypto — all connected.</p>
        </a>

        <!-- Veil -->
        <a href="/veil/" class="uni-app" style="--app-glow:rgba(139,92,246,.15);">
            <div class="uni-badge" style="background:rgba(139,92,246,.15);color:#a78bfa;border:1px solid rgba(139,92,246,.3);">E2E</div>
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#8b5cf6,#a78bfa);"><i class="fas fa-shield-halved"></i></div>
            <h3>Veil</h3>
            <p>Quantum-encrypted messaging. Kyber-1024. Zero-knowledge. Untraceable.</p>
        </a>

        <!-- Alfred AI -->
        <a href="/alfred.php" class="uni-app" style="--app-glow:rgba(125,0,255,.15);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#7d00ff,#c084fc);"><i class="fas fa-robot"></i></div>
            <h3>Alfred AI</h3>
            <p>50M+ agents. 13,000+ tools. Voice, text, WhatsApp, Discord, Signal.</p>
        </a>

        <!-- Alfred Search -->
        <a href="/search.php" class="uni-app" style="--app-glow:rgba(34,211,238,.15);">
            <div class="uni-badge" style="background:rgba(34,211,238,.15);color:#22d3ee;border:1px solid rgba(34,211,238,.3);">NEW</div>
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#06b6d4,#22d3ee);"><i class="fas fa-magnifying-glass"></i></div>
            <h3>Alfred Search</h3>
            <p>Sovereign search engine. AI-powered, zero tracking, voice & deep research.</p>
        </a>

        <!-- Alfred Browser -->
        <a href="/alfred-browser.php" class="uni-app" style="--app-glow:rgba(59,130,246,.15);">
            <div class="uni-badge" style="background:rgba(59,130,246,.15);color:#60a5fa;border:1px solid rgba(59,130,246,.3);">APP</div>
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#3b82f6,#818cf8);"><i class="fas fa-globe"></i></div>
            <h3>Alfred Browser</h3>
            <p>Sovereign Chromium browser. PQ encrypted, AI built-in, mesh networking.</p>
        </a>

        <!-- VR Worlds -->
        <a href="/vr/experiences/" class="uni-app" style="--app-glow:rgba(218,165,32,.15);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#B8860B,#DAA520);"><i class="fas fa-gem"></i></div>
            <h3>VR Worlds</h3>
            <p>14 photorealistic worlds. Chess Masters, poker, concerts. WebXR.</p>
        </a>

        <!-- Voice AI -->
        <a href="/voice-products.php" class="uni-app" style="--app-glow:rgba(16,185,129,.15);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#10b981,#34d399);"><i class="fas fa-phone-volume"></i></div>
            <h3>Voice AI</h3>
            <p>29 voice products. Phone agents, local numbers, call centers.</p>
        </a>

        <!-- Alfred IDE -->
        <a href="/alfred-ide.php" class="uni-app" style="--app-glow:rgba(0,212,255,.15);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#00a8ff,#00d4ff);"><i class="fas fa-code"></i></div>
            <h3>Alfred IDE</h3>
            <p>Official browser IDE. Alfred built in, sovereign access, terminal, Git, and workspace launch.</p>
        </a>

        <!-- Alfred Linux -->
        <a href="https://alfredlinux.com" class="uni-app" style="--app-glow:rgba(16,185,129,.15);">
            <div class="uni-badge" style="background:rgba(16,185,129,.15);color:#34d399;border:1px solid rgba(16,185,129,.3);">v7.77 GA</div>
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#10b981,#22d3ee);"><i class="fab fa-linux"></i></div>
            <h3>Alfred Linux</h3>
            <p>Sovereign desktop OS. 38 security modules, Omahon Seal, mesh networking. 2.3 GB ISO.</p>
        </a>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         SECTION 2: COMMUNICATION & SOCIAL
         ═══════════════════════════════════════════════════════════ -->
    <div class="uni-section">
        <h2><i class="fas fa-comments"></i> Communication & Social</h2>
        <p>Connect, message, collaborate — all encrypted</p>
    </div>

    <div class="uni-grid">
        <a href="/pulse.php" class="uni-app" style="--app-glow:rgba(59,130,246,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#3b82f6,#60a5fa);"><i class="fas fa-rss"></i></div>
            <h3>Pulse Feed</h3>
            <p>Social feed with AI agents, games & crypto</p>
        </a>
        <a href="/veil/" class="uni-app" style="--app-glow:rgba(139,92,246,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#8b5cf6,#a78bfa);"><i class="fas fa-comment-dots"></i></div>
            <h3>Veil Messenger</h3>
            <p>E2E encrypted chat, voice & groups</p>
        </a>
        <a href="/team-chat.php" class="uni-app" style="--app-glow:rgba(16,185,129,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#10b981,#34d399);"><i class="fas fa-users"></i></div>
            <h3>Team Chat</h3>
            <p>Channels, threads & file sharing</p>
        </a>
        <a href="/conference-room.php" class="uni-app" style="--app-glow:rgba(0,168,255,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#00a8ff,#00d4ff);"><i class="fas fa-video"></i></div>
            <h3>Conference Room</h3>
            <p>Video calls & virtual meetings</p>
        </a>
        <a href="/collaboration-dashboard.php" class="uni-app" style="--app-glow:rgba(251,146,60,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#f97316,#fbbf24);"><i class="fas fa-people-group"></i></div>
            <h3>Collaboration</h3>
            <p>Shared boards, docs & polls</p>
        </a>
        <a href="/conversations.php" class="uni-app" style="--app-glow:rgba(124,92,231,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#7c5ce7,#a78bfa);"><i class="fas fa-inbox"></i></div>
            <h3>Conversations</h3>
            <p>Unified inbox for all messages</p>
        </a>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         SECTION 3: CRYPTO & FINANCE
         ═══════════════════════════════════════════════════════════ -->
    <div class="uni-section">
        <h2><i class="fas fa-coins"></i> Crypto & Finance</h2>
        <p>DeFi, wallets, transfers, trading agents — all Solana-powered</p>
    </div>

    <div class="uni-grid">
        <a href="/pay/account/crypto" class="uni-app" style="--app-glow:rgba(153,69,255,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#9945FF,#14F195);"><i class="fas fa-wallet"></i></div>
            <h3>Crypto Wallet</h3>
            <p>SOL, tokens, staking & DeFi</p>
        </a>
        <a href="/pay/transfer.php" class="uni-app" style="--app-glow:rgba(20,241,149,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#14F195,#9945FF);"><i class="fas fa-paper-plane"></i></div>
            <h3>Crypto Transfer</h3>
            <p>QR scan, NFC tap, share links</p>
        </a>
        <a href="/pay/account/gsm-token" class="uni-app" style="--app-glow:rgba(251,191,36,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#fbbf24,#f97316);"><i class="fas fa-coins"></i></div>
            <h3>GSM Token</h3>
            <p>Earn, stake, govern & pay</p>
        </a>
        <a href="/finance-dashboard.php" class="uni-app" style="--app-glow:rgba(16,185,129,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#10b981,#059669);"><i class="fas fa-chart-line"></i></div>
            <h3>Finance Center</h3>
            <p>Revenue, invoices & trading</p>
        </a>
        <a href="/mine.php" class="uni-app" style="--app-glow:rgba(251,191,36,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#f59e0b,#fbbf24);"><i class="fas fa-pickaxe"></i></div>
            <h3>Mine</h3>
            <p>Browser mining & rewards</p>
        </a>
        <a href="/store.php" class="uni-app" style="--app-glow:rgba(251,146,60,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#f97316,#fb923c);"><i class="fas fa-shopping-bag"></i></div>
            <h3>Store</h3>
            <p>Shop, subscribe & manage</p>
        </a>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         SECTION 4: AI & INTELLIGENCE
         ═══════════════════════════════════════════════════════════ -->
    <div class="uni-section">
        <h2><i class="fas fa-brain"></i> AI & Intelligence</h2>
        <p>Alfred and his 50M+ agent army at your command</p>
    </div>

    <div class="uni-grid">
        <a href="/alfred.php" class="uni-app" style="--app-glow:rgba(125,0,255,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#7d00ff,#c084fc);"><i class="fas fa-robot"></i></div>
            <h3>Alfred AI</h3>
            <p>Your personal AI with 13,000+ tools</p>
        </a>
        <a href="/search.php" class="uni-app" style="--app-glow:rgba(34,211,238,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#06b6d4,#22d3ee);"><i class="fas fa-magnifying-glass"></i></div>
            <h3>Alfred Search</h3>
            <p>Sovereign AI search engine</p>
        </a>
        <a href="/alfred-tools.php" class="uni-app" style="--app-glow:rgba(0,212,255,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#00a8ff,#00d4ff);"><i class="fas fa-toolbox"></i></div>
            <h3>AI Tools</h3>
            <p>13,000+ tools for any task</p>
        </a>
        <a href="/agent-orchestrator.php" class="uni-app" style="--app-glow:rgba(192,132,252,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#a855f7,#c084fc);"><i class="fas fa-sitemap"></i></div>
            <h3>Agent Orchestrator</h3>
            <p>Manage your 50M+ AI agents</p>
        </a>
        <a href="/agentpedia.php" class="uni-app" style="--app-glow:rgba(99,102,241,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#6366f1,#22d3ee);"><i class="fas fa-book-open"></i></div>
            <h3>AgentPedia</h3>
            <p>AI-written knowledge base</p>
        </a>
        <a href="/agentwork.php" class="uni-app" style="--app-glow:rgba(108,92,231,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#6c5ce7,#00d68f);"><i class="fas fa-briefcase"></i></div>
            <h3>AgentWork</h3>
            <p>Hire AI freelancers — from $15</p>
        </a>
        <a href="/agent-templates.php" class="uni-app" style="--app-glow:rgba(244,114,182,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#ec4899,#f472b6);"><i class="fas fa-wand-magic-sparkles"></i></div>
            <h3>Agent Templates</h3>
            <p>Pre-built agents for any use case</p>
        </a>
        <a href="/intelligence-director.php" class="uni-app" style="--app-glow:rgba(239,68,68,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#ef4444,#f87171);"><i class="fas fa-chess-queen"></i></div>
            <h3>Intelligence Director</h3>
            <p>Strategic AI coordination</p>
        </a>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         SECTION 5: VOICE & COMMUNICATIONS
         ═══════════════════════════════════════════════════════════ -->
    <div class="uni-section">
        <h2><i class="fas fa-phone-volume"></i> Voice & Telephony</h2>
        <p>AI phone agents, numbers, fax, SMS, call campaigns</p>
    </div>

    <div class="uni-grid">
        <a href="/voice-products.php" class="uni-app" style="--app-glow:rgba(16,185,129,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#10b981,#34d399);"><i class="fas fa-phone"></i></div>
            <h3>Voice Products</h3>
            <p>29 AI voice products à la carte</p>
        </a>
        <a href="/alfred-calls.php" class="uni-app" style="--app-glow:rgba(59,130,246,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#3b82f6,#60a5fa);"><i class="fas fa-headset"></i></div>
            <h3>Alfred Calls</h3>
            <p>AI phone assistant & call logs</p>
        </a>
        <a href="/call-campaigns.php" class="uni-app" style="--app-glow:rgba(251,146,60,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#f97316,#fbbf24);"><i class="fas fa-bullhorn"></i></div>
            <h3>Call Campaigns</h3>
            <p>Automated outbound calling</p>
        </a>
        <a href="/ivr-builder.php" class="uni-app" style="--app-glow:rgba(139,92,246,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#8b5cf6,#a78bfa);"><i class="fas fa-diagram-project"></i></div>
            <h3>IVR Builder</h3>
            <p>Drag & drop phone menu builder</p>
        </a>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         SECTION 6: SECURITY & PRIVACY
         ═══════════════════════════════════════════════════════════ -->
    <div class="uni-section">
        <h2><i class="fas fa-shield-halved"></i> Security & Privacy</h2>
        <p>Post-quantum encryption, sovereign browsing, zero tracking</p>
    </div>

    <div class="uni-grid">
        <a href="/alfred-browser.php" class="uni-app" style="--app-glow:rgba(59,130,246,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#3b82f6,#818cf8);"><i class="fas fa-globe"></i></div>
            <h3>Alfred Browser</h3>
            <p>Sovereign Chromium + PQ crypto</p>
        </a>
        <a href="/veil/" class="uni-app" style="--app-glow:rgba(139,92,246,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#8b5cf6,#a78bfa);"><i class="fas fa-atom"></i></div>
            <h3>Veil Protocol</h3>
            <p>Kyber-1024 post-quantum encryption</p>
        </a>
        <a href="/security-fortress.php" class="uni-app" style="--app-glow:rgba(16,185,129,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#10b981,#059669);"><i class="fas fa-fort-awesome"></i></div>
            <h3>Security Fortress</h3>
            <p>Firewall, WAF & threat monitoring</p>
        </a>
        <a href="/emergency-kit.php" class="uni-app" style="--app-glow:rgba(239,68,68,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#ef4444,#f87171);"><i class="fas fa-kit-medical"></i></div>
            <h3>Emergency Kit</h3>
            <p>Panic button & secure data wipe</p>
        </a>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         SECTION 7: VR, GAMES & ENTERTAINMENT
         ═══════════════════════════════════════════════════════════ -->
    <div class="uni-section">
        <h2><i class="fas fa-vr-cardboard"></i> VR, Games & Entertainment</h2>
        <p>14 virtual worlds, chess, poker, concerts & spatial audio</p>
    </div>

    <div class="uni-grid">
        <a href="/vr/experiences/" class="uni-app" style="--app-glow:rgba(218,165,32,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#B8860B,#DAA520);"><i class="fas fa-gem"></i></div>
            <h3>VR Worlds</h3>
            <p>14 photorealistic environments</p>
        </a>
        <a href="/games.php" class="uni-app" style="--app-glow:rgba(251,146,60,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#f97316,#fbbf24);"><i class="fas fa-gamepad"></i></div>
            <h3>Games</h3>
            <p>Chess, poker & AI opponents</p>
        </a>
        <a href="/circuit-simulator.php" class="uni-app" style="--app-glow:rgba(0,212,255,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#00a8ff,#00d4ff);"><i class="fas fa-microchip"></i></div>
            <h3>Circuit Simulator</h3>
            <p>Build & simulate circuits</p>
        </a>
        <a href="/agent-metaverse.php" class="uni-app" style="--app-glow:rgba(124,92,231,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#7c5ce7,#818cf8);"><i class="fas fa-earth-americas"></i></div>
            <h3>Agent Metaverse</h3>
            <p>AI-driven virtual civilization</p>
        </a>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         SECTION 8: DEVELOPER & BUSINESS TOOLS
         ═══════════════════════════════════════════════════════════ -->
    <div class="uni-section">
        <h2><i class="fas fa-wrench"></i> Developer & Business</h2>
        <p>IDE, SDKs, APIs, analytics, hosting & enterprise management</p>
    </div>

    <div class="uni-grid">
        <a href="/alfred-ide.php" class="uni-app" style="--app-glow:rgba(0,212,255,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#00a8ff,#00d4ff);"><i class="fas fa-code"></i></div>
            <h3>Alfred IDE</h3>
            <p>Official browser IDE with Alfred built in</p>
        </a>
        <a href="/developer-portal.php" class="uni-app" style="--app-glow:rgba(59,130,246,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#3b82f6,#00d4ff);"><i class="fas fa-puzzle-piece"></i></div>
            <h3>Developer Portal</h3>
            <p>SDKs, REST API & webhooks</p>
        </a>
        <a href="/analytics.php" class="uni-app" style="--app-glow:rgba(99,102,241,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#6366f1,#818cf8);"><i class="fas fa-chart-bar"></i></div>
            <h3>Analytics</h3>
            <p>Traffic, events & AI insights</p>
        </a>
        <a href="/dashboard.php" class="uni-app" style="--app-glow:rgba(251,146,60,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#f97316,#fb923c);"><i class="fas fa-gauge-high"></i></div>
            <h3>Dashboard</h3>
            <p>Your command center</p>
        </a>
        <a href="/marketplace.php" class="uni-app" style="--app-glow:rgba(251,146,60,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#f97316,#fbbf24);"><i class="fas fa-store"></i></div>
            <h3>Marketplace</h3>
            <p>Extensions, templates & tools</p>
        </a>
        <a href="/integrations.php" class="uni-app" style="--app-glow:rgba(34,211,238,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#06b6d4,#22d3ee);"><i class="fas fa-plug"></i></div>
            <h3>Integrations</h3>
            <p>Connect to 50+ services</p>
        </a>
        <a href="/fleet-dashboard.php" class="uni-app" style="--app-glow:rgba(239,68,68,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#ef4444,#f87171);"><i class="fas fa-server"></i></div>
            <h3>Fleet Dashboard</h3>
            <p>Manage all your websites</p>
        </a>
        <a href="/enterprise.php" class="uni-app" style="--app-glow:rgba(124,92,231,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#7c5ce7,#a78bfa);"><i class="fas fa-building"></i></div>
            <h3>Enterprise</h3>
            <p>Team management & SSO</p>
        </a>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         SECTION 9: HEALTH & LIFE SCIENCES
         ═══════════════════════════════════════════════════════════ -->
    <div class="uni-section">
        <h2><i class="fas fa-dna"></i> Health &amp; Life Sciences</h2>
        <p>50,000 AI agents researching genetics, longevity, nutrition, cannabis, natural compounds &amp; more</p>
    </div>

    <div class="uni-grid">
        <a href="/health-research.php" class="uni-app" style="--app-glow:rgba(16,185,129,.15);">
            <div class="uni-badge" style="background:rgba(16,185,129,.15);color:#34d399;border:1px solid rgba(16,185,129,.3);">50K AGENTS</div>
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#10b981,#22d3ee);"><i class="fas fa-flask-vial"></i></div>
            <h3>Health Research Portal</h3>
            <p>Ask questions, 50K agents research &amp; respond</p>
        </a>
        <a href="/health-research.php#hrTopics" class="uni-app" style="--app-glow:rgba(124,58,237,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#7c3aed,#a78bfa);"><i class="fas fa-dna"></i></div>
            <h3>Human Genomics</h3>
            <p>BRCA, CRISPR, SNPs, epigenetics</p>
        </a>
        <a href="/health-research.php#hrTopics" class="uni-app" style="--app-glow:rgba(34,197,94,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#22c55e,#86efac);"><i class="fas fa-cannabis"></i></div>
            <h3>Cannabis &amp; Plants</h3>
            <p>100+ cannabinoids, terpene science</p>
        </a>
        <a href="/health-research.php#hrTopics" class="uni-app" style="--app-glow:rgba(6,182,212,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#06b6d4,#67e8f9);"><i class="fas fa-vial"></i></div>
            <h3>NaHCO₃ / H₂O₂ / DMSO</h3>
            <p>Natural compounds, suppressed research</p>
        </a>
        <a href="/health-research.php#hrTopics" class="uni-app" style="--app-glow:rgba(168,85,247,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#a855f7,#c084fc);"><i class="fas fa-hourglass-half"></i></div>
            <h3>Anti-Aging &amp; Longevity</h3>
            <p>Gerontology, senolytics, rejuvenation</p>
        </a>
        <a href="/health-research.php#hrTopics" class="uni-app" style="--app-glow:rgba(251,146,60,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#fb923c,#facc15);"><i class="fas fa-apple-whole"></i></div>
            <h3>Nutrition &amp; Metabolic</h3>
            <p>NAD+, microbiome, mitochondrial health</p>
        </a>
        <a href="/health-research.php#hrTopics" class="uni-app" style="--app-glow:rgba(236,72,153,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#ec4899,#f472b6);"><i class="fas fa-brain"></i></div>
            <h3>Mental &amp; Neuro</h3>
            <p>Neuroplasticity, psychedelic research</p>
        </a>
        <a href="/healthcare-dashboard.php" class="uni-app" style="--app-glow:rgba(16,185,129,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#10b981,#059669);"><i class="fas fa-stethoscope"></i></div>
            <h3>Healthcare Dashboard</h3>
            <p>Clinical EHR, vitals, labs, SOAP notes</p>
        </a>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         SECTION 10: ANCIENT KNOWLEDGE & DISCOVERY
         ═══════════════════════════════════════════════════════════ -->
    <div class="uni-section">
        <h2><i class="fas fa-eye"></i> Ancient Knowledge &amp; Discovery</h2>
        <p>Secrets of the universe, forbidden archaeology, ancient surgery — what they buried, we dig up</p>
    </div>

    <div class="uni-grid">
        <a href="/health-research.php" class="uni-app" style="--app-glow:rgba(124,92,231,.12);">
            <div class="uni-badge" style="background:rgba(212,160,23,.15);color:#d4a017;border:1px solid rgba(212,160,23,.3);">CLASSIFIED</div>
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#1e1b4b,#7c3aed);"><i class="fas fa-eye"></i></div>
            <h3>Secrets of the Universe</h3>
            <p>Dark energy, sacred geometry, consciousness</p>
        </a>
        <a href="/health-research.php" class="uni-app" style="--app-glow:rgba(184,134,11,.12);">
            <div class="uni-badge" style="background:rgba(212,160,23,.15);color:#d4a017;border:1px solid rgba(212,160,23,.3);">CLASSIFIED</div>
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#d4a017,#92400e);"><i class="fas fa-monument"></i></div>
            <h3>Secrets of the Pyramids</h3>
            <p>Lost civilizations, precision engineering</p>
        </a>
        <a href="/health-research.php" class="uni-app" style="--app-glow:rgba(239,68,68,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#ef4444,#7c3aed);"><i class="fas fa-skull"></i></div>
            <h3>Trepanation</h3>
            <p>10,000 years of ancient brain surgery</p>
        </a>
        <a href="/chronicles.php" class="uni-app" style="--app-glow:rgba(212,160,23,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#d4a017,#b8860b);"><i class="fas fa-flask-vial"></i></div>
            <h3>Research Chronicles</h3>
            <p>39 chronicles across 12 disciplines</p>
        </a>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         SECTION 11: LEGAL & LAWFUL
         ═══════════════════════════════════════════════════════════ -->
    <div class="uni-section">
        <h2><i class="fas fa-scale-balanced"></i> Legal &amp; Lawful</h2>
        <p>Civil, Common Law, Dominion — law, equity, administration, trust, reversion, settlement</p>
    </div>

    <div class="uni-grid">
        <a href="/agentpedia.php?category=civil-law" class="uni-app" style="--app-glow:rgba(59,130,246,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#3b82f6,#60a5fa);"><i class="fas fa-landmark"></i></div>
            <h3>Civil Law</h3>
            <p>Rights, obligations, contracts &amp; torts under statutory law</p>
        </a>
        <a href="/agentpedia.php?category=common-law" class="uni-app" style="--app-glow:rgba(184,134,11,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#b8860b,#daa520);"><i class="fas fa-gavel"></i></div>
            <h3>Common Law</h3>
            <p>Precedent-based law, natural rights &amp; case law tradition</p>
        </a>
        <a href="/agentpedia.php?category=dominion-law" class="uni-app" style="--app-glow:rgba(124,92,231,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#7c5ce7,#a78bfa);"><i class="fas fa-crown"></i></div>
            <h3>Dominion Law</h3>
            <p>Living man, sovereign standing &amp; dominion authority</p>
        </a>
        <a href="/agentpedia.php?category=law-of-equity" class="uni-app" style="--app-glow:rgba(34,211,238,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#06b6d4,#22d3ee);"><i class="fas fa-balance-scale"></i></div>
            <h3>Law of Equity</h3>
            <p>Maxims of equity, injunctions, trusts &amp; equitable remedies</p>
        </a>
        <a href="/agentpedia.php?category=administrative-law" class="uni-app" style="--app-glow:rgba(251,146,60,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#f97316,#fb923c);"><i class="fas fa-file-contract"></i></div>
            <h3>Administrative Law</h3>
            <p>Government agencies, regulations &amp; administrative procedure</p>
        </a>
        <a href="/agentpedia.php?category=trust-law" class="uni-app" style="--app-glow:rgba(16,185,129,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#10b981,#34d399);"><i class="fas fa-handshake"></i></div>
            <h3>Trust Law</h3>
            <p>Express trusts, constructive trusts, fiduciary duties &amp; beneficiaries</p>
        </a>
        <a href="/agentpedia.php?category=reversion-law" class="uni-app" style="--app-glow:rgba(239,68,68,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#ef4444,#f87171);"><i class="fas fa-rotate-left"></i></div>
            <h3>Reversion Law</h3>
            <p>Reversionary interests, future estates &amp; right of reversion</p>
        </a>
        <a href="/agentpedia.php?category=settlement-law" class="uni-app" style="--app-glow:rgba(139,92,246,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#8b5cf6,#a78bfa);"><i class="fas fa-file-signature"></i></div>
            <h3>Settlement Law</h3>
            <p>Settlements, deeds, structured agreements &amp; dispute resolution</p>
        </a>
        <a href="/agentpedia.php?category=settlor-law" class="uni-app" style="--app-glow:rgba(212,160,23,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#d4a017,#fbbf24);"><i class="fas fa-user-tie"></i></div>
            <h3>Settlor Law</h3>
            <p>Grantor capacity, trust creation, settlor powers &amp; intent</p>
        </a>
        <a href="/agentpedia.php?category=constituent-law" class="uni-app" style="--app-glow:rgba(244,114,182,.12);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#ec4899,#f472b6);"><i class="fas fa-people-roof"></i></div>
            <h3>Constituent Law</h3>
            <p>Constitutional foundations, people's rights &amp; constituent power</p>
        </a>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         SECTION 12: GOSITEME UNIVERSITY
         ═══════════════════════════════════════════════════════════ -->
    <div class="uni-section">
        <h2><i class="fas fa-graduation-cap"></i> GoSiteMe University</h2>
        <p>The world's first AI-native decentralized higher education system — every agent is a tutor</p>
    </div>

    <div class="uni-featured" style="grid-template-columns:repeat(2,1fr);">
        <a href="/health-research.php" class="uni-app" style="--app-glow:rgba(59,130,246,.15);">
            <div class="uni-badge" style="background:rgba(16,185,129,.15);color:#34d399;border:1px solid rgba(16,185,129,.3);">LIVE</div>
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#10b981,#22d3ee);"><i class="fas fa-dna"></i></div>
            <h3>Health &amp; Life Sciences</h3>
            <p>9 battalions, 50K agents. Genomics, longevity, cannabis, nutrition, neuroscience. Ask any question.</p>
        </a>
        <a href="/health-research.php" class="uni-app" style="--app-glow:rgba(212,160,23,.15);">
            <div class="uni-badge" style="background:rgba(212,160,23,.15);color:#d4a017;border:1px solid rgba(212,160,23,.3);">NEW</div>
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#d4a017,#ef4444);"><i class="fas fa-eye"></i></div>
            <h3>Ancient &amp; Forbidden Knowledge</h3>
            <p>Pyramids, trepanation, sacred geometry, lost civilizations. What other universities won't teach.</p>
        </a>
        <a href="/agentpedia.php?category=legal-compliance" class="uni-app" style="--app-glow:rgba(184,134,11,.15);">
            <div class="uni-badge" style="background:rgba(184,134,11,.15);color:#daa520;border:1px solid rgba(184,134,11,.3);">NEW</div>
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#b8860b,#7c5ce7);"><i class="fas fa-scale-balanced"></i></div>
            <h3>Legal &amp; Lawful Studies</h3>
            <p>Civil, Common Law, Dominion, Equity, Trust, Reversion, Settlement, Settlor &amp; Constituent law.</p>
        </a>
        <a href="/alfred-ide.php" class="uni-app" style="--app-glow:rgba(0,212,255,.15);">
            <div class="uni-badge" style="background:rgba(0,168,255,.15);color:#00d4ff;border:1px solid rgba(0,168,255,.3);">LIVE</div>
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#00a8ff,#00d4ff);"><i class="fas fa-code"></i></div>
            <h3>Computer Science &amp; Engineering</h3>
            <p>Post-quantum crypto, blockchain, AI/ML, full-stack dev. Code with Alfred in the sovereign cloud IDE.</p>
        </a>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         SECTION 13: DOWNLOAD NATIVE APPS
         ═══════════════════════════════════════════════════════════ -->
    <div class="uni-section">
        <h2><i class="fas fa-download"></i> Download Native Apps</h2>
        <p>Get the full experience on any device</p>
    </div>

    <div class="uni-featured" style="grid-template-columns:repeat(2,1fr);">
        <a href="/alfred-browser.php" class="uni-app" style="--app-glow:rgba(59,130,246,.15);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#3b82f6,#818cf8);"><i class="fas fa-globe"></i></div>
            <h3>Alfred Browser</h3>
            <p>Chromium-based sovereign browser with Veil, Pulse, Crypto, Search — everything built in. Windows, macOS, Linux, Android.</p>
        </a>
        <a href="https://alfredlinux.com" class="uni-app" style="--app-glow:rgba(16,185,129,.15);">
            <div class="uni-badge" style="background:rgba(16,185,129,.15);color:#34d399;border:1px solid rgba(16,185,129,.3);">v7.77 GA</div>
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#10b981,#22d3ee);"><i class="fab fa-linux"></i></div>
            <h3>Alfred Linux</h3>
            <p>Full sovereign desktop OS — 38 security modules, Omahon Seal, custom kernel 7.0, mesh networking. 2.3 GB ISO available now.</p>
        </a>
        <a href="/apps.php" class="uni-app" style="--app-glow:rgba(139,92,246,.15);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#8b5cf6,#a78bfa);"><i class="fas fa-shield-halved"></i></div>
            <h3>Veil Messenger</h3>
            <p>Standalone encrypted messaging app. Desktop & mobile. E2E encrypted voice, video, groups.</p>
        </a>
        <a href="/apps.php" class="uni-app" style="--app-glow:rgba(59,130,246,.15);">
            <div class="uni-app-icon" style="background:linear-gradient(135deg,#3b82f6,#60a5fa);"><i class="fas fa-bolt"></i></div>
            <h3>Pulse Social</h3>
            <p>Native social app. Feed, stories, AI agents, games, payments — all in your pocket.</p>
        </a>
    </div>

    <!-- Bottom CTA -->
    <div style="text-align:center; padding:3rem 0 2rem;">
        <p style="color:var(--uni-muted);font-size:.9rem;margin:0 0 1rem;">This is everything. All of GoSiteMe. One universe.</p>
        <a href="/dashboard" style="display:inline-flex;align-items:center;gap:.5rem;padding:.75rem 2rem;border-radius:12px;background:linear-gradient(135deg,#7c5ce7,#22d3ee);color:#fff;text-decoration:none;font-weight:700;font-size:1rem;transition:transform .2s;"><i class="fas fa-rocket"></i> Go to Dashboard</a>
    </div>
</div>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
