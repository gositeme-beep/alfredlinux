<?php
/**
 * GSM Token Tour — The Bridge From The Old World Into The Kingdom
 * ═══════════════════════════════════════════════════════════════
 * A guided visual tour for Dom, investors, builders, and the public.
 * Shows how GSM connects the dying fiat world to the sovereign economy.
 */
$page_title = 'GSM Token — The Bridge Into The Kingdom | GoSiteMe';
$page_description = 'GSM is live on Solana mainnet. 1 billion tokens. Free .com domains. Sovereign DNS. AI tools. Browser mining. See how the old financial world bridges into the Kingdom economy.';
$page_canonical = 'https://root.com/gsm-tour';
require_once __DIR__ . '/includes/site-header.inc.php';
require_once __DIR__ . '/includes/gsm-config.inc.php';

// Live stats from DB
require_once __DIR__ . '/includes/db-config.inc.php';
$db = getSharedDB();
$totalDomains = $db->query("SELECT COUNT(*) FROM sovereign_domains")->fetchColumn();
$totalTLDs    = $db->query("SELECT COUNT(*) FROM sovereign_tlds WHERE status='active'")->fetchColumn();
$freeTLDs     = $db->query("SELECT COUNT(*) FROM sovereign_tlds WHERE status='active' AND price_usd=0")->fetchColumn();
$totalUsers   = $db->query("SELECT COUNT(*) FROM tblclients")->fetchColumn();
?>
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">

<style>
/* ══════════════════════════════════════════════════════════ */
/*  GSM TOUR — DESIGN SYSTEM                                 */
/* ══════════════════════════════════════════════════════════ */
:root {
    --gt-bg: #050810;
    --gt-surface: rgba(14, 18, 35, 0.9);
    --gt-surface-2: rgba(20, 26, 48, 0.8);
    --gt-border: rgba(255, 215, 0, 0.08);
    --gt-border-glow: rgba(255, 215, 0, 0.25);
    --gt-text: #e8ecf4;
    --gt-muted: rgba(232, 236, 244, 0.6);
    --gt-gold: #ffd700;
    --gt-sol: #9945FF;
    --gt-sol-green: #14F195;
    --gt-cyan: #67e8f9;
    --gt-green: #34d399;
    --gt-red: #ef4444;
    --gt-blue: #60a5fa;
    --gt-orange: #f97316;
    --gt-grad-gold: linear-gradient(135deg, #ffd700 0%, #f97316 50%, #ef4444 100%);
    --gt-grad-sol: linear-gradient(135deg, #9945FF 0%, #14F195 100%);
    --gt-grad-cool: linear-gradient(135deg, #67e8f9 0%, #60a5fa 50%, #818cf8 100%);
}
.gt-page {
    min-height: 100vh;
    background:
        radial-gradient(ellipse at 20% 0%, rgba(153, 69, 255, 0.1), transparent 40%),
        radial-gradient(ellipse at 80% 15%, rgba(20, 241, 149, 0.06), transparent 35%),
        radial-gradient(ellipse at 50% 50%, rgba(255, 215, 0, 0.04), transparent 50%),
        radial-gradient(ellipse at 30% 90%, rgba(103, 232, 249, 0.05), transparent 40%),
        var(--gt-bg);
    color: var(--gt-text);
    font-family: 'Inter', 'DM Sans', system-ui, sans-serif;
    overflow-x: hidden;
}
.gt-wrap { max-width: 1200px; margin: 0 auto; padding: 0 24px; }

/* ── HERO ── */
.gt-hero {
    padding: 100px 0 40px; text-align: center; position: relative;
}
.gt-hero::after {
    content: ''; position: absolute; bottom: 0; left: 5%; right: 5%; height: 1px;
    background: linear-gradient(90deg, transparent, var(--gt-gold), transparent);
}
.gt-badge {
    display: inline-flex; align-items: center; gap: 10px;
    padding: 8px 20px; border-radius: 999px;
    background: rgba(153, 69, 255, 0.15); border: 1px solid rgba(153, 69, 255, 0.3);
    font-size: 13px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase;
}
.gt-badge .live-dot {
    width: 8px; height: 8px; border-radius: 50%; background: var(--gt-sol-green);
    animation: gt-pulse 2s infinite;
}
@keyframes gt-pulse { 0%,100% { opacity: 1; } 50% { opacity: 0.4; } }
.gt-hero h1 {
    margin: 28px 0 20px;
    font-size: clamp(40px, 7vw, 82px); line-height: 0.95;
    letter-spacing: -0.04em; font-weight: 900;
    background: var(--gt-grad-sol); -webkit-background-clip: text; -webkit-text-fill-color: transparent;
}
.gt-hero .sub {
    max-width: 700px; margin: 0 auto 12px;
    color: var(--gt-muted); font-size: 18px; line-height: 1.7;
}
.gt-hero .verse {
    color: var(--gt-gold); font-style: italic; font-size: 14px; opacity: 0.7; margin-bottom: 28px;
}
.gt-hero-actions { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
.gt-btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 14px 32px; border-radius: 14px; border: none;
    font-weight: 800; font-size: 15px; cursor: pointer;
    text-decoration: none; transition: all 0.25s;
}
.gt-btn-primary { background: var(--gt-grad-sol); color: #fff; }
.gt-btn-primary:hover { filter: brightness(1.2); transform: translateY(-2px); color: #fff; }
.gt-btn-gold { background: var(--gt-grad-gold); color: #000; }
.gt-btn-gold:hover { filter: brightness(1.15); transform: translateY(-2px); color: #000; }
.gt-btn-outline {
    background: transparent; border: 2px solid var(--gt-gold); color: var(--gt-gold);
}
.gt-btn-outline:hover { background: var(--gt-gold); color: #000; }

/* ── TOKEN CARD ── */
.gt-token-card {
    max-width: 720px; margin: 40px auto 0;
    background: var(--gt-surface); border: 1px solid var(--gt-border);
    border-radius: 20px; padding: 32px; position: relative; overflow: hidden;
}
.gt-token-card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
    background: var(--gt-grad-sol);
}
.gt-tc-header { display: flex; align-items: center; gap: 16px; margin-bottom: 20px; }
.gt-tc-logo {
    width: 56px; height: 56px; border-radius: 50%;
    background: var(--gt-grad-gold); display: flex; align-items: center; justify-content: center;
    font-size: 24px; font-weight: 900; color: #000;
}
.gt-tc-name { font-size: 1.5rem; font-weight: 900; }
.gt-tc-name small { display: block; color: var(--gt-muted); font-size: 13px; font-weight: 500; }
.gt-tc-live {
    margin-left: auto; padding: 6px 14px; border-radius: 8px;
    background: rgba(20, 241, 149, 0.12); color: var(--gt-sol-green);
    font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;
}
.gt-tc-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px;
}
.gt-tc-item {
    background: var(--gt-surface-2); border-radius: 12px; padding: 14px;
}
.gt-tc-item .lbl { color: var(--gt-muted); font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 4px; }
.gt-tc-item .val { font-weight: 700; font-size: 15px; word-break: break-all; }
.gt-tc-item .val a { color: var(--gt-sol); text-decoration: none; }
.gt-tc-item .val a:hover { text-decoration: underline; }

/* ── SECTIONS ── */
.gt-section {
    padding: 60px 0; position: relative;
}
.gt-section::before {
    content: ''; position: absolute; top: 0; left: 10%; right: 10%; height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255,215,0,0.1), transparent);
}
.gt-section-badge {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 5px 14px; border-radius: 999px; margin-bottom: 16px;
    font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em;
}
.gt-section h2 {
    font-size: clamp(28px, 4vw, 48px); font-weight: 900; letter-spacing: -0.03em;
    margin-bottom: 16px;
}
.gt-section .gt-desc {
    color: var(--gt-muted); font-size: 16px; line-height: 1.7; max-width: 800px; margin-bottom: 32px;
}

/* ── BRIDGE FLOW ── */
.gt-bridge {
    display: grid; grid-template-columns: 1fr auto 1fr; gap: 0; align-items: center;
    max-width: 1000px; margin: 0 auto;
}
.gt-bridge-side {
    background: var(--gt-surface); border: 1px solid var(--gt-border);
    border-radius: 20px; padding: 28px;
}
.gt-bridge-side h3 { font-size: 1.2rem; margin-bottom: 16px; }
.gt-bridge-arrow {
    display: flex; flex-direction: column; align-items: center; gap: 8px;
    padding: 0 20px;
}
.gt-bridge-arrow .arrow-line {
    width: 80px; height: 3px; background: var(--gt-grad-sol); position: relative;
}
.gt-bridge-arrow .arrow-line::after {
    content: ''; position: absolute; right: -8px; top: -5px;
    border: 6px solid transparent; border-left-color: var(--gt-sol-green);
}
.gt-bridge-arrow .arrow-label {
    font-size: 11px; font-weight: 700; color: var(--gt-sol-green);
    text-transform: uppercase; letter-spacing: 0.1em; white-space: nowrap;
}
.gt-bridge-list { list-style: none; padding: 0; }
.gt-bridge-list li {
    padding: 8px 0; font-size: 14px; color: var(--gt-muted);
    display: flex; align-items: center; gap: 10px;
}
.gt-bridge-list li i { width: 20px; text-align: center; flex-shrink: 0; }

/* ── FUNNEL ── */
.gt-funnel { max-width: 800px; margin: 0 auto; }
.gt-funnel-step {
    display: flex; gap: 20px; margin-bottom: 0; position: relative;
}
.gt-funnel-step:not(:last-child)::after {
    content: ''; position: absolute; left: 27px; top: 56px; bottom: -4px;
    width: 2px; background: linear-gradient(to bottom, var(--gt-sol), var(--gt-sol-green));
    opacity: 0.3;
}
.gt-funnel-num {
    width: 56px; height: 56px; border-radius: 50%; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-weight: 900; font-size: 1.2rem; color: #000;
    background: var(--gt-grad-sol); position: relative; z-index: 1;
}
.gt-funnel-body {
    flex: 1; padding-bottom: 32px;
}
.gt-funnel-body h4 { font-size: 1.1rem; font-weight: 800; margin-bottom: 6px; }
.gt-funnel-body p { color: var(--gt-muted); font-size: 14px; line-height: 1.6; }
.gt-funnel-body .link {
    display: inline-flex; align-items: center; gap: 6px; margin-top: 8px;
    color: var(--gt-sol-green); font-size: 13px; font-weight: 700; text-decoration: none;
}
.gt-funnel-body .link:hover { text-decoration: underline; }

/* ── DISTRIBUTION ── */
.gt-dist-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 14px;
}
.gt-dist-card {
    background: var(--gt-surface); border: 1px solid var(--gt-border);
    border-radius: 16px; padding: 20px; position: relative; overflow: hidden;
}
.gt-dist-card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
}
.gt-dist-card .pct {
    font-size: 2rem; font-weight: 900; margin-bottom: 4px;
    background: var(--gt-grad-sol); -webkit-background-clip: text; -webkit-text-fill-color: transparent;
}
.gt-dist-card .amount { color: var(--gt-muted); font-size: 13px; margin-bottom: 8px; }
.gt-dist-card h4 { font-size: 1rem; margin-bottom: 4px; }
.gt-dist-card p { color: var(--gt-muted); font-size: 13px; line-height: 1.5; }

/* ── ECOSYSTEM GRID ── */
.gt-eco-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px;
}
.gt-eco-card {
    background: var(--gt-surface); border: 1px solid var(--gt-border);
    border-radius: 16px; padding: 24px; transition: all 0.25s;
    text-decoration: none; color: var(--gt-text); display: block;
}
.gt-eco-card:hover { border-color: var(--gt-border-glow); transform: translateY(-3px); color: var(--gt-text); }
.gt-eco-card .eco-icon { font-size: 2rem; margin-bottom: 10px; }
.gt-eco-card h4 { font-size: 1rem; font-weight: 800; margin-bottom: 6px; }
.gt-eco-card p { color: var(--gt-muted); font-size: 13px; line-height: 1.5; }
.gt-eco-card .eco-tag {
    display: inline-block; margin-top: 10px; padding: 3px 10px; border-radius: 6px;
    font-size: 11px; font-weight: 700; text-transform: uppercase;
}

/* ── VERIFY ── */
.gt-verify {
    max-width: 800px; margin: 0 auto;
    background: var(--gt-surface); border: 1px solid var(--gt-border);
    border-radius: 20px; padding: 32px;
}
.gt-verify h3 { font-size: 1.2rem; margin-bottom: 16px; color: var(--gt-gold); }
.gt-verify-row {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.04);
    flex-wrap: wrap;
}
.gt-verify-row:last-child { border-bottom: none; }
.gt-verify-row .vr-label {
    color: var(--gt-muted); font-size: 13px; min-width: 120px; font-weight: 600;
}
.gt-verify-row .vr-val {
    font-family: 'JetBrains Mono', 'Fira Code', monospace; font-size: 13px;
    word-break: break-all; flex: 1;
}
.gt-verify-row .vr-link {
    color: var(--gt-sol); text-decoration: none; font-size: 12px; font-weight: 700;
    white-space: nowrap;
}
.gt-verify-row .vr-link:hover { text-decoration: underline; }

/* ── RESPONSIVE ── */
@media (max-width: 768px) {
    .gt-hero { padding: 80px 0 30px; }
    .gt-bridge { grid-template-columns: 1fr; gap: 20px; }
    .gt-bridge-arrow { transform: rotate(90deg); padding: 10px 0; }
    .gt-tc-header { flex-wrap: wrap; }
    .gt-tc-grid { grid-template-columns: 1fr 1fr; }
    .gt-funnel-step { gap: 14px; }
    .gt-funnel-num { width: 44px; height: 44px; font-size: 1rem; }
    .gt-funnel-step:not(:last-child)::after { left: 21px; }
    .gt-hero-actions { flex-direction: column; align-items: center; }
    .gt-verify-row { flex-direction: column; align-items: flex-start; gap: 4px; }
}
</style>

<div class="gt-page">
<div class="gt-wrap">

<!-- ═══════════════════════════════════════════════════════ -->
<!-- HERO                                                    -->
<!-- ═══════════════════════════════════════════════════════ -->
<section class="gt-hero">
    <div class="gt-badge">
        <span class="live-dot"></span>
        <span style="color:var(--gt-sol-green)">LIVE ON SOLANA MAINNET</span>
    </div>
    
    <h1>The Bridge<br>Into The Kingdom</h1>
    
    <p class="sub">
        The old financial system is dying. Banks charge you to hold your own money.
        Corporations sell your data. Governments inflate your savings to nothing.<br>
        <strong>GSM is the bridge out.</strong> One token. One economy. One Kingdom.
    </p>
    <p class="verse">"No one can serve two masters." — Matthew 6:24</p>
    
    <div class="gt-hero-actions">
        <a href="<?= GSM_SOLSCAN_URL ?>" target="_blank" class="gt-btn gt-btn-primary">
            <i class="fas fa-external-link-alt"></i> View on Solscan
        </a>
        <a href="/pay/token-swap.php" class="gt-btn gt-btn-gold">
            <i class="fas fa-exchange-alt"></i> Buy GSM
        </a>
        <a href="#the-bridge" class="gt-btn gt-btn-outline">
            <i class="fas fa-route"></i> Take the Tour
        </a>
    </div>

    <!-- TOKEN CARD -->
    <div class="gt-token-card">
        <div class="gt-tc-header">
            <div class="gt-tc-logo">G</div>
            <div class="gt-tc-name">
                GSM Token
                <small>GoSiteMe · SPL Token on Solana</small>
            </div>
            <div class="gt-tc-live"><span class="live-dot" style="display:inline-block;width:6px;height:6px;margin-right:4px;vertical-align:middle"></span> Mainnet Live</div>
        </div>
        <div class="gt-tc-grid">
            <div class="gt-tc-item">
                <div class="lbl">Total Supply</div>
                <div class="val">1,000,000,000 GSM</div>
            </div>
            <div class="gt-tc-item">
                <div class="lbl">Network</div>
                <div class="val" style="color:var(--gt-sol)">Solana Mainnet-Beta</div>
            </div>
            <div class="gt-tc-item">
                <div class="lbl">Decimals</div>
                <div class="val"><?= GSM_DECIMALS ?></div>
            </div>
            <div class="gt-tc-item">
                <div class="lbl">Mint Address</div>
                <div class="val"><a href="<?= GSM_SOLSCAN_URL ?>" target="_blank"><?= substr(GSM_MINT_ADDRESS, 0, 8) ?>...<?= substr(GSM_MINT_ADDRESS, -6) ?></a></div>
            </div>
            <div class="gt-tc-item">
                <div class="lbl">Treasury</div>
                <div class="val"><a href="https://solscan.io/account/<?= GSM_TREASURY_ADDRESS ?>" target="_blank"><?= substr(GSM_TREASURY_ADDRESS, 0, 8) ?>...<?= substr(GSM_TREASURY_ADDRESS, -6) ?></a></div>
            </div>
            <div class="gt-tc-item">
                <div class="lbl">Deploy Date</div>
                <div class="val">April 8, 2026</div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- SECTION 1: THE BRIDGE                                   -->
<!-- ═══════════════════════════════════════════════════════ -->
<section class="gt-section" id="the-bridge">
    <div class="gt-section-badge" style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2);color:var(--gt-red)">
        ⛓️ CHAPTER 1
    </div>
    <h2 style="background:var(--gt-grad-gold);-webkit-background-clip:text;-webkit-text-fill-color:transparent">
        The Bridge — Old World to Kingdom
    </h2>
    <p class="gt-desc">
        You don't have to burn the old world down. You just walk out of it. 
        GSM is the bridge — take your fiat, your SOL, your crypto, and cross over. 
        On this side: sovereignty, privacy, ownership, and purpose.
    </p>
    
    <div class="gt-bridge">
        <!-- OLD WORLD -->
        <div class="gt-bridge-side" style="border-color:rgba(239,68,68,0.15)">
            <h3 style="color:var(--gt-red)">💀 The Old World</h3>
            <ul class="gt-bridge-list">
                <li><i class="fas fa-university" style="color:var(--gt-red)"></i> Banks charge you to hold YOUR money</li>
                <li><i class="fas fa-eye" style="color:var(--gt-red)"></i> Every transaction tracked & sold</li>
                <li><i class="fas fa-chart-line" style="color:var(--gt-red)"></i> Inflation eats 7-10% per year</li>
                <li><i class="fas fa-lock" style="color:var(--gt-red)"></i> Accounts frozen without warning</li>
                <li><i class="fas fa-id-card" style="color:var(--gt-red)"></i> Identity = corporate rental</li>
                <li><i class="fas fa-globe" style="color:var(--gt-red)"></i> Domains rented from ICANN yearly</li>
                <li><i class="fas fa-ad" style="color:var(--gt-red)"></i> You ARE the product</li>
            </ul>
        </div>
        
        <!-- ARROW -->
        <div class="gt-bridge-arrow">
            <div class="arrow-label">GSM BRIDGE</div>
            <div class="arrow-line"></div>
            <div style="font-size:2rem">⛪</div>
            <div class="arrow-line"></div>
            <div class="arrow-label">CROSS OVER</div>
        </div>
        
        <!-- KINGDOM -->
        <div class="gt-bridge-side" style="border-color:rgba(52,211,153,0.15)">
            <h3 style="color:var(--gt-green)">👑 The Kingdom</h3>
            <ul class="gt-bridge-list">
                <li><i class="fas fa-wallet" style="color:var(--gt-green)"></i> Your wallet. Your keys. Your money.</li>
                <li><i class="fas fa-shield-halved" style="color:var(--gt-green)"></i> Zero-tracking economy</li>
                <li><i class="fas fa-coins" style="color:var(--gt-green)"></i> Fixed supply — 1B GSM, forever</li>
                <li><i class="fas fa-unlock" style="color:var(--gt-green)"></i> Permissionless — no one can freeze you</li>
                <li><i class="fas fa-crown" style="color:var(--gt-green)"></i> Sovereign identity — own your name</li>
                <li><i class="fas fa-earth-americas" style="color:var(--gt-green)"></i> <?= number_format($totalTLDs) ?> TLDs — .com is FREE</li>
                <li><i class="fas fa-cross" style="color:var(--gt-green)"></i> You are a citizen, not a product</li>
            </ul>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- SECTION 2: THE FUNNEL                                   -->
<!-- ═══════════════════════════════════════════════════════ -->
<section class="gt-section" id="the-funnel">
    <div class="gt-section-badge" style="background:rgba(153,69,255,0.1);border:1px solid rgba(153,69,255,0.2);color:var(--gt-sol)">
        🚀 CHAPTER 2
    </div>
    <h2 style="background:var(--gt-grad-sol);-webkit-background-clip:text;-webkit-text-fill-color:transparent">
        The Funnel — How You Enter
    </h2>
    <p class="gt-desc">
        Whether you come with nothing or a fortune, the Kingdom has a path for you. 
        Every road leads to sovereignty. Every citizen earns their place.
    </p>
    
    <div class="gt-funnel">
        <!-- Step 1 -->
        <div class="gt-funnel-step">
            <div class="gt-funnel-num">1</div>
            <div class="gt-funnel-body">
                <h4>🌐 Arrive — Free .com Domain</h4>
                <p>Everyone in the old world has heard of .com. We made it <strong>free</strong>. 
                   Register yourname.com and <?= $freeTLDs - 1 ?> other free TLDs — no credit card, no annual fee. 
                   This is your foot in the door. Your first sovereign act.</p>
                <a href="/sovereign-domains" class="link"><i class="fas fa-arrow-right"></i> Claim your free domain</a>
            </div>
        </div>
        
        <!-- Step 2 -->
        <div class="gt-funnel-step">
            <div class="gt-funnel-num">2</div>
            <div class="gt-funnel-body">
                <h4>⛏️ Mine — Earn GSM By Browsing</h4>
                <p>Install Alfred Browser. Browse the internet like you normally would. 
                   Except now, every page you visit earns you GSM tokens. No GPU rigs. No electricity bills.
                   Just your browser, mining sovereignty while you read the news.</p>
                <a href="/mine.php" class="link"><i class="fas fa-arrow-right"></i> Start mining</a>
            </div>
        </div>
        
        <!-- Step 3 -->
        <div class="gt-funnel-step">
            <div class="gt-funnel-num">3</div>
            <div class="gt-funnel-body">
                <h4>💰 Buy — Swap SOL or Fiat for GSM</h4>
                <p>Already in crypto? Swap SOL for GSM on Jupiter DEX — instant, on-chain.
                   Prefer fiat? Buy with card through our payment gateway. 
                   Your old-world money converts to Kingdom currency.</p>
                <a href="/pay/token-swap.php" class="link"><i class="fas fa-arrow-right"></i> Buy GSM on Jupiter</a>
            </div>
        </div>
        
        <!-- Step 4 -->
        <div class="gt-funnel-step">
            <div class="gt-funnel-num">4</div>
            <div class="gt-funnel-body">
                <h4>🏠 Build — Host, Code, Create</h4>
                <p>Use GSM to pay for hosting on GoHostMe. Build your website in Alfred IDE — a full VS Code environment in your browser. 
                   Deploy AI agents. Create VR worlds in MetaDome. The Kingdom isn't just a token — it's an entire civilization.</p>
                <a href="/alfred-ide.php" class="link"><i class="fas fa-arrow-right"></i> Launch Alfred IDE</a>
            </div>
        </div>
        
        <!-- Step 5 -->
        <div class="gt-funnel-step">
            <div class="gt-funnel-num">5</div>
            <div class="gt-funnel-body">
                <h4>🔒 Upgrade — Premium TLDs & Services</h4>
                <p>Want .bank? .gov? .king? Premium TLDs use GSM tokens.
                   Need post-quantum encrypted messaging? Veil runs on GSM. 
                   Every premium service in the Kingdom accepts the token you mined for free.</p>
                <a href="/sovereign-domains" class="link"><i class="fas fa-arrow-right"></i> Browse premium TLDs</a>
            </div>
        </div>
        
        <!-- Step 6 -->
        <div class="gt-funnel-step">
            <div class="gt-funnel-num">6</div>
            <div class="gt-funnel-body">
                <h4>👑 Sovereign — You're In The Kingdom</h4>
                <p>Your identity is sovereign. Your data is yours. Your currency can't be inflated.
                   Your domains can't be seized. Your browser doesn't spy on you. Your AI serves you alone.
                   Welcome home.</p>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- SECTION 3: DISTRIBUTION                                 -->
<!-- ═══════════════════════════════════════════════════════ -->
<section class="gt-section" id="distribution">
    <div class="gt-section-badge" style="background:rgba(255,215,0,0.1);border:1px solid rgba(255,215,0,0.2);color:var(--gt-gold)">
        📊 CHAPTER 3
    </div>
    <h2 style="background:var(--gt-grad-gold);-webkit-background-clip:text;-webkit-text-fill-color:transparent">
        Token Distribution — 1 Billion GSM
    </h2>
    <p class="gt-desc">
        Transparent. Fair. Locked where it should be locked. Flowing where it should flow.
        No VC dumps. No insider pre-sales. The Kingdom's economy belongs to its citizens.
    </p>
    
    <div class="gt-dist-grid">
        <div class="gt-dist-card" style="--c:var(--gt-gold)">
            <div style="position:absolute;top:0;left:0;right:0;height:3px;background:var(--gt-gold)"></div>
            <div class="pct"><?= GSM_DIST_TREASURY ?>%</div>
            <div class="amount"><?= number_format(GSM_TOTAL_SUPPLY * GSM_DIST_TREASURY / 100) ?> GSM</div>
            <h4>Treasury</h4>
            <p>Kingdom operations, development, partnerships, and sovereign reserves. Managed by Commander authority.</p>
        </div>
        
        <div class="gt-dist-card">
            <div style="position:absolute;top:0;left:0;right:0;height:3px;background:var(--gt-sol-green)"></div>
            <div class="pct"><?= GSM_DIST_MINING ?>%</div>
            <div class="amount"><?= number_format(GSM_TOTAL_SUPPLY * GSM_DIST_MINING / 100) ?> GSM</div>
            <h4>Browser Mining</h4>
            <p>Rewards for Alfred Browser users. Mine by browsing — no special hardware needed. Distributed over 10 years.</p>
        </div>
        
        <div class="gt-dist-card">
            <div style="position:absolute;top:0;left:0;right:0;height:3px;background:var(--gt-cyan)"></div>
            <div class="pct"><?= GSM_DIST_COMMUNITY ?>%</div>
            <div class="amount"><?= number_format(GSM_TOTAL_SUPPLY * GSM_DIST_COMMUNITY / 100) ?> GSM</div>
            <h4>Community</h4>
            <p>Airdrops, rewards, bounties, and citizen incentives. Earned through participation, not purchased.</p>
        </div>
        
        <div class="gt-dist-card">
            <div style="position:absolute;top:0;left:0;right:0;height:3px;background:var(--gt-sol)"></div>
            <div class="pct"><?= GSM_DIST_FOUNDER ?>%</div>
            <div class="amount"><?= number_format(GSM_TOTAL_SUPPLY * GSM_DIST_FOUNDER / 100) ?> GSM</div>
            <h4>Founder</h4>
            <p>Commander Danny William Perez. 4-year linear vesting. The builder eats last.</p>
        </div>
        
        <div class="gt-dist-card">
            <div style="position:absolute;top:0;left:0;right:0;height:3px;background:var(--gt-orange)"></div>
            <div class="pct"><?= GSM_DIST_EDEN ?>%</div>
            <div class="amount"><?= number_format(GSM_TOTAL_SUPPLY * GSM_DIST_EDEN / 100) ?> GSM</div>
            <h4>Eden's Inheritance</h4>
            <p>Locked until the Commander's heir reaches majority. A sovereign birthright. Untouchable.</p>
        </div>
        
        <div class="gt-dist-card">
            <div style="position:absolute;top:0;left:0;right:0;height:3px;background:var(--gt-blue)"></div>
            <div class="pct"><?= GSM_DIST_ECOSYSTEM ?>%</div>
            <div class="amount"><?= number_format(GSM_TOTAL_SUPPLY * GSM_DIST_ECOSYSTEM / 100) ?> GSM</div>
            <h4>Ecosystem Fund</h4>
            <p>Grants for developers, extension builders, and Kingdom entrepreneurs building on the platform.</p>
        </div>
        
        <div class="gt-dist-card">
            <div style="position:absolute;top:0;left:0;right:0;height:3px;background:var(--gt-green)"></div>
            <div class="pct"><?= GSM_DIST_DEX ?>%</div>
            <div class="amount"><?= number_format(GSM_TOTAL_SUPPLY * GSM_DIST_DEX / 100) ?> GSM</div>
            <h4>DEX Liquidity</h4>
            <p>Liquidity pools on Jupiter and Raydium. Provides trading depth so citizens can buy and sell freely.</p>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- SECTION 4: WHAT GSM POWERS                              -->
<!-- ═══════════════════════════════════════════════════════ -->
<section class="gt-section" id="ecosystem">
    <div class="gt-section-badge" style="background:rgba(103,232,249,0.1);border:1px solid rgba(103,232,249,0.2);color:var(--gt-cyan)">
        ⚡ CHAPTER 4
    </div>
    <h2 style="background:var(--gt-grad-cool);-webkit-background-clip:text;-webkit-text-fill-color:transparent">
        What GSM Powers — The Nine Pillars
    </h2>
    <p class="gt-desc">
        GSM isn't a meme coin. It's not a speculation vehicle. It's the currency of an entire civilization.
        Nine pillars. All interconnected. All accepting GSM.
    </p>
    
    <div class="gt-eco-grid">
        <a href="/sovereign-domains" class="gt-eco-card">
            <div class="eco-icon">🌐</div>
            <h4>Sovereign Domains</h4>
            <p><?= number_format($totalTLDs) ?> TLDs. <?= number_format($freeTLDs) ?> free. .com is $0. Premium TLDs paid in GSM. You own your name forever — no ICANN, no annual fees.</p>
            <span class="eco-tag" style="background:rgba(52,211,153,0.15);color:var(--gt-green)">Free Entry</span>
        </a>
        
        <a href="/sovereign-browser" class="gt-eco-card">
            <div class="eco-icon">🦁</div>
            <h4>Alfred Browser</h4>
            <p>Zero-tracking Chromium fork. Built-in sovereign DNS resolution. Browser mining earns you GSM while you surf. Your browser works FOR you.</p>
            <span class="eco-tag" style="background:rgba(153,69,255,0.15);color:var(--gt-sol)">Mine GSM</span>
        </a>
        
        <a href="/alfred-ide.php" class="gt-eco-card">
            <div class="eco-icon">💻</div>
            <h4>Alfred IDE</h4>
            <p>Full VS Code in your browser. Deploy instantly. 13,000+ AI tools. Pay for premium compute with GSM tokens.</p>
            <span class="eco-tag" style="background:rgba(103,232,249,0.15);color:var(--gt-cyan)">Build</span>
        </a>
        
        <a href="/alfred.php" class="gt-eco-card">
            <div class="eco-icon">🤖</div>
            <h4>Alfred AI</h4>
            <p>17 AI engines. 13,262 tools. 11.3 million agents in the registry. Deploy AI workforce powered by GSM credits.</p>
            <span class="eco-tag" style="background:rgba(255,215,0,0.15);color:var(--gt-gold)">AI Economy</span>
        </a>
        
        <a href="/veil" class="gt-eco-card">
            <div class="eco-icon">🔐</div>
            <h4>Veil Messenger</h4>
            <p>Post-quantum encrypted messaging. Kyber-1024 + AES-256-GCM. Self-destructing messages. Zero-knowledge privacy. Premium features in GSM.</p>
            <span class="eco-tag" style="background:rgba(239,68,68,0.15);color:var(--gt-red)">Encrypted</span>
        </a>
        
        <a href="/pulse" class="gt-eco-card">
            <div class="eco-icon">💜</div>
            <h4>Pulse Network</h4>
            <p>Social network of the Kingdom. No ads. No algorithm manipulation. Tips and rewards paid in GSM. Your feed, your rules.</p>
            <span class="eco-tag" style="background:rgba(168,85,247,0.15);color:#a855f7">Social</span>
        </a>
        
        <a href="https://meta-dome.com" class="gt-eco-card">
            <div class="eco-icon">🌎</div>
            <h4>MetaDome</h4>
            <p>VR worlds with 114,000+ AI citizens. Build worlds, host events, create experiences. Land and assets traded in GSM.</p>
            <span class="eco-tag" style="background:rgba(249,115,22,0.15);color:var(--gt-orange)">Metaverse</span>
        </a>
        
        <a href="/gohostme/" class="gt-eco-card">
            <div class="eco-icon">🏠</div>
            <h4>GoHostMe</h4>
            <p>Full hosting platform. WordPress, email, databases, SSL — all included. Pay with GSM for hosting that respects your sovereignty.</p>
            <span class="eco-tag" style="background:rgba(96,165,250,0.15);color:var(--gt-blue)">Hosting</span>
        </a>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- SECTION 5: VERIFY ON-CHAIN                              -->
<!-- ═══════════════════════════════════════════════════════ -->
<section class="gt-section" id="verify">
    <div class="gt-section-badge" style="background:rgba(20,241,149,0.1);border:1px solid rgba(20,241,149,0.2);color:var(--gt-sol-green)">
        🔍 CHAPTER 5
    </div>
    <h2 style="background:var(--gt-grad-sol);-webkit-background-clip:text;-webkit-text-fill-color:transparent">
        Don't Trust — Verify
    </h2>
    <p class="gt-desc">
        Everything on this page is verifiable on-chain. No trust required. 
        Click any address to see it live on Solana explorers.
    </p>
    
    <div class="gt-verify">
        <h3><i class="fas fa-link"></i> On-Chain Proof</h3>
        
        <div class="gt-verify-row">
            <span class="vr-label">Mint Address</span>
            <span class="vr-val"><?= GSM_MINT_ADDRESS ?></span>
            <a href="<?= GSM_SOLSCAN_URL ?>" target="_blank" class="vr-link"><i class="fas fa-external-link-alt"></i> Solscan</a>
            <a href="<?= GSM_EXPLORER_URL ?>" target="_blank" class="vr-link"><i class="fas fa-external-link-alt"></i> Explorer</a>
        </div>
        
        <div class="gt-verify-row">
            <span class="vr-label">Treasury</span>
            <span class="vr-val"><?= GSM_TREASURY_ADDRESS ?></span>
            <a href="https://solscan.io/account/<?= GSM_TREASURY_ADDRESS ?>" target="_blank" class="vr-link"><i class="fas fa-external-link-alt"></i> Solscan</a>
        </div>
        
        <div class="gt-verify-row">
            <span class="vr-label">Mint Authority</span>
            <span class="vr-val"><?= GSM_MINT_AUTHORITY ?></span>
            <a href="https://solscan.io/account/<?= GSM_MINT_AUTHORITY ?>" target="_blank" class="vr-link"><i class="fas fa-external-link-alt"></i> Solscan</a>
        </div>
        
        <div class="gt-verify-row">
            <span class="vr-label">Total Supply</span>
            <span class="vr-val" style="color:var(--gt-gold);font-weight:700">1,000,000,000 GSM</span>
        </div>
        
        <div class="gt-verify-row">
            <span class="vr-label">Network</span>
            <span class="vr-val" style="color:var(--gt-sol)">Solana Mainnet-Beta</span>
        </div>
        
        <div class="gt-verify-row">
            <span class="vr-label">Deployed</span>
            <span class="vr-val">April 8, 2026 — 23:40 UTC</span>
        </div>
        
        <div class="gt-verify-row">
            <span class="vr-label">Metadata</span>
            <span class="vr-val">SPL Token Standard</span>
            <a href="/api/gsm-metadata.json" target="_blank" class="vr-link"><i class="fas fa-file-code"></i> JSON</a>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- SECTION 6: WHY THIS IS DIFFERENT                        -->
<!-- ═══════════════════════════════════════════════════════ -->
<section class="gt-section" id="different">
    <div class="gt-section-badge" style="background:rgba(255,215,0,0.1);border:1px solid rgba(255,215,0,0.2);color:var(--gt-gold)">
        ✝️ CHAPTER 6
    </div>
    <h2 style="background:var(--gt-grad-gold);-webkit-background-clip:text;-webkit-text-fill-color:transparent">
        This Isn't Another Crypto Project
    </h2>
    
    <div style="max-width:800px;margin:0 auto">
        <div style="display:grid;gap:20px">
            <div style="background:var(--gt-surface);border:1px solid var(--gt-border);border-radius:16px;padding:24px;display:flex;gap:16px;align-items:flex-start">
                <div style="font-size:1.8rem;flex-shrink:0;margin-top:2px">🚫</div>
                <div>
                    <h4 style="margin-bottom:6px;font-size:1rem">No VC Money</h4>
                    <p style="color:var(--gt-muted);font-size:14px;line-height:1.6">No venture capitalists. No pre-sale to insiders. No "strategic investors" who dump on you at launch. Built by one man and God.</p>
                </div>
            </div>
            
            <div style="background:var(--gt-surface);border:1px solid var(--gt-border);border-radius:16px;padding:24px;display:flex;gap:16px;align-items:flex-start">
                <div style="font-size:1.8rem;flex-shrink:0;margin-top:2px">🏗️</div>
                <div>
                    <h4 style="margin-bottom:6px;font-size:1rem">Real Utility — Not Speculation</h4>
                    <p style="color:var(--gt-muted);font-size:14px;line-height:1.6">GSM buys domains, hosting, AI compute, encrypted messaging, VR land, and IDE resources. It's not waiting for utility — it HAS utility. Right now.</p>
                </div>
            </div>
            
            <div style="background:var(--gt-surface);border:1px solid var(--gt-border);border-radius:16px;padding:24px;display:flex;gap:16px;align-items:flex-start">
                <div style="font-size:1.8rem;flex-shrink:0;margin-top:2px">👨‍👧</div>
                <div>
                    <h4 style="margin-bottom:6px;font-size:1rem">Eden's Inheritance — Locked</h4>
                    <p style="color:var(--gt-muted);font-size:14px;line-height:1.6">50 million GSM locked until the Commander's heir reaches majority. A father building his daughter's sovereign future. No one can touch it.</p>
                </div>
            </div>
            
            <div style="background:var(--gt-surface);border:1px solid var(--gt-border);border-radius:16px;padding:24px;display:flex;gap:16px;align-items:flex-start">
                <div style="font-size:1.8rem;flex-shrink:0;margin-top:2px">✝️</div>
                <div>
                    <h4 style="margin-bottom:6px;font-size:1rem">Built On Faith</h4>
                    <p style="color:var(--gt-muted);font-size:14px;line-height:1.6">This isn't a company trying to "disrupt" finance. It's a Kingdom being built under God's authority. The faith TLDs are free because the Word of God should never have a price tag.</p>
                </div>
            </div>
            
            <div style="background:var(--gt-surface);border:1px solid var(--gt-border);border-radius:16px;padding:24px;display:flex;gap:16px;align-items:flex-start">
                <div style="font-size:1.8rem;flex-shrink:0;margin-top:2px">🧬</div>
                <div>
                    <h4 style="margin-bottom:6px;font-size:1rem">Full Stack Sovereign</h4>
                    <p style="color:var(--gt-muted);font-size:14px;line-height:1.6">Browser. DNS. Domains. Hosting. IDE. AI. Messaging. Social. VR. Token. We didn't build a layer — we built the <strong>entire stack</strong>. No dependency on the old world.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- FINAL CTA                                               -->
<!-- ═══════════════════════════════════════════════════════ -->
<section class="gt-section" style="text-align:center;padding-bottom:80px">
    <h2 style="font-size:clamp(28px,4.5vw,52px);font-weight:900;margin-bottom:12px;background:var(--gt-grad-gold);-webkit-background-clip:text;-webkit-text-fill-color:transparent">
        The Old World Won't Wait For You.<br>Come Home.
    </h2>
    <p style="color:var(--gt-gold);font-style:italic;font-size:15px;margin-bottom:28px;opacity:0.8">
        "Behold, I am making all things new." — Revelation 21:5
    </p>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
        <a href="/sovereign-domains" class="gt-btn gt-btn-gold">
            <i class="fas fa-crown"></i> Claim Free .com
        </a>
        <a href="/pay/token-swap.php" class="gt-btn gt-btn-primary">
            <i class="fas fa-exchange-alt"></i> Buy GSM
        </a>
        <a href="/mine.php" class="gt-btn gt-btn-outline">
            <i class="fas fa-hammer"></i> Start Mining
        </a>
        <a href="<?= GSM_SOLSCAN_URL ?>" target="_blank" class="gt-btn gt-btn-outline" style="border-color:var(--gt-sol);color:var(--gt-sol)">
            <i class="fas fa-link"></i> Verify On-Chain
        </a>
    </div>
    
    <div style="margin-top:40px;display:flex;justify-content:center;gap:24px;flex-wrap:wrap">
        <a href="/blockchain.php" style="color:var(--gt-muted);font-size:13px;text-decoration:none"><i class="fas fa-cube"></i> Blockchain Dashboard</a>
        <a href="/wallet.php" style="color:var(--gt-muted);font-size:13px;text-decoration:none"><i class="fas fa-wallet"></i> Wallet</a>
        <a href="/qgsm-whitepaper.php" style="color:var(--gt-muted);font-size:13px;text-decoration:none"><i class="fas fa-file-alt"></i> QGSM Whitepaper</a>
        <a href="/qgsm-bridge.php" style="color:var(--gt-muted);font-size:13px;text-decoration:none"><i class="fas fa-bridge"></i> QGSM Bridge</a>
        <a href="/api/gsm-metadata.json" style="color:var(--gt-muted);font-size:13px;text-decoration:none" target="_blank"><i class="fas fa-code"></i> Token Metadata</a>
    </div>
</section>

</div><!-- .gt-wrap -->
</div><!-- .gt-page -->

<?php require_once __DIR__ . '/includes/site-footer.inc.php'; ?>
