<?php
$page_title = 'Sovereign Domains — Own Your Name Forever | GoSiteMe';
$page_description = 'The old internet rented you a name. The Kingdom gives you one. 176 TLDs, free .com, zero-tracking DNS, built on faith and sovereignty. Register your domain in the Sovereign Web.';
$page_canonical = 'https://gositeme.com/sovereign-domains';
$page_og_image = 'https://gositeme.com/assets/images/sovereign-domains-og.png';
$page_og_image_width = 1200;
$page_og_image_height = 630;
$page_og_image_alt = 'Sovereign Domains — 176+ TLDs, 3,271+ Domains, Free .com. Own Your Name Forever.';
require_once __DIR__ . '/includes/site-header.inc.php';

// ── Pull live data ──────────────────────────────
require_once __DIR__ . '/includes/db-config.inc.php';
$db = getSharedDB();

$totalTLDs = $db->query("SELECT COUNT(*) FROM sovereign_tlds WHERE status='active'")->fetchColumn();
$totalDomains = $db->query("SELECT COUNT(*) FROM sovereign_domains")->fetchColumn();
$freeTLDs = $db->query("SELECT COUNT(*) FROM sovereign_tlds WHERE status='active' AND price_usd=0")->fetchColumn();
$categories = $db->query("SELECT category, COUNT(*) as cnt FROM sovereign_tlds WHERE status='active' GROUP BY category ORDER BY cnt DESC")->fetchAll(PDO::FETCH_ASSOC);

// Get TLDs organized by category
$tldsByCategory = [];
$allTLDs = $db->query("SELECT * FROM sovereign_tlds WHERE status IN ('active','coming_soon') ORDER BY price_usd ASC, tld ASC")->fetchAll(PDO::FETCH_ASSOC);
foreach ($allTLDs as $t) {
    $tldsByCategory[$t['category']][] = $t;
}

$categoryMeta = [
    'community' => ['label' => 'Faith & Community', 'icon' => '🙏', 'color' => '#34d399', 'desc' => 'For churches, ministries, communities, education, and the body of Christ. All faith TLDs are FREE — forever.'],
    'ecosystem' => ['label' => 'Ecosystem & AI', 'icon' => '⚡', 'color' => '#67e8f9', 'desc' => 'The core infrastructure of the Kingdom — AI, search, voice, browser, code, and sovereign mesh services.'],
    'identity' => ['label' => 'Identity & Royalty', 'icon' => '👑', 'color' => '#fbbf24', 'desc' => 'Claim your royal title. From .king to .legend, your identity in the Kingdom is sovereign.'],
    'commerce' => ['label' => 'Commerce & Finance', 'icon' => '💰', 'color' => '#f97316', 'desc' => 'Banking, crypto, payments, and enterprise. Built for the GSM economy and global trade.'],
    'creative' => ['label' => 'Creative & Media', 'icon' => '🎬', 'color' => '#f472b6', 'desc' => 'Art, music, film, gaming, VR worlds — the creative engine of the Kingdom.'],
    'infrastructure' => ['label' => 'Infrastructure & Tech', 'icon' => '🖥️', 'color' => '#60a5fa', 'desc' => 'Servers, DNS, APIs, nodes, data — the backbone of sovereign infrastructure.'],
    'security' => ['label' => 'Security & Defense', 'icon' => '🛡️', 'color' => '#ef4444', 'desc' => 'Military-grade security, quantum encryption, and sovereign defense systems.'],
];
?>
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<link rel="stylesheet" href="/assets/css/fonts.css">

<style>
:root {
    --sd-bg: #050a12;
    --sd-surface: rgba(12, 20, 38, 0.85);
    --sd-border: rgba(255, 215, 0, 0.1);
    --sd-border-glow: rgba(255, 215, 0, 0.3);
    --sd-text: #e8ecf4;
    --sd-muted: rgba(232, 236, 244, 0.65);
    --sd-gold: #ffd700;
    --sd-gold-light: #ffe44d;
    --sd-cyan: #67e8f9;
    --sd-green: #34d399;
    --sd-red: #ef4444;
    --sd-blue: #60a5fa;
    --sd-grad: linear-gradient(135deg, #ffd700 0%, #f97316 50%, #ef4444 100%);
    --sd-grad-cool: linear-gradient(135deg, #67e8f9 0%, #60a5fa 50%, #818cf8 100%);
}
.sd-page {
    min-height: 100vh;
    background:
        radial-gradient(ellipse at 20% 0%, rgba(255, 215, 0, 0.08), transparent 40%),
        radial-gradient(ellipse at 80% 20%, rgba(239, 68, 68, 0.06), transparent 35%),
        radial-gradient(ellipse at 50% 80%, rgba(103, 232, 249, 0.05), transparent 40%),
        var(--sd-bg);
    color: var(--sd-text);
    font-family: 'Inter', 'DM Sans', system-ui, sans-serif;
}
.sd-wrap { max-width: 1200px; margin: 0 auto; padding: 0 24px; }

/* ── HERO ── */
.sd-hero { padding: 100px 0 60px; text-align: center; position: relative; }
.sd-hero::after {
    content: '';
    position: absolute; bottom: 0; left: 10%; right: 10%; height: 1px;
    background: linear-gradient(90deg, transparent, var(--sd-gold), transparent);
}
.sd-kicker {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 6px 16px; border-radius: 999px;
    background: rgba(255, 215, 0, 0.1); border: 1px solid rgba(255, 215, 0, 0.2);
    color: var(--sd-gold); font-size: 12px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.1em;
}
.sd-hero h1 {
    margin: 24px 0 20px;
    font-size: clamp(42px, 7vw, 86px);
    line-height: 0.95; letter-spacing: -0.04em; font-weight: 900;
    background: var(--sd-grad);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}
.sd-hero .sd-sub {
    max-width: 720px; margin: 0 auto 12px;
    color: var(--sd-muted); font-size: 19px; line-height: 1.7;
}
.sd-hero .sd-verse {
    color: var(--sd-gold); font-style: italic; font-size: 15px; opacity: 0.8; margin-bottom: 32px;
}

/* ── SEARCH ── */
.sd-search-wrap {
    max-width: 680px; margin: 0 auto 20px; position: relative;
}
.sd-search-input {
    width: 100%; padding: 18px 160px 18px 24px;
    background: var(--sd-surface); border: 2px solid var(--sd-border);
    border-radius: 16px; color: var(--sd-text); font-size: 18px;
    outline: none; transition: border-color 0.3s;
}
.sd-search-input:focus { border-color: var(--sd-gold); }
.sd-search-input::placeholder { color: rgba(255,255,255,0.3); }
.sd-search-btn {
    position: absolute; right: 6px; top: 6px; bottom: 6px;
    padding: 0 28px; border: none; border-radius: 12px;
    background: var(--sd-grad); color: #000; font-weight: 800;
    font-size: 15px; cursor: pointer; letter-spacing: 0.02em;
    transition: filter 0.2s;
}
.sd-search-btn:hover { filter: brightness(1.15); }
.sd-search-results { max-width: 680px; margin: 0 auto; }

/* ── STAT STRIP ── */
.sd-stats {
    display: flex; justify-content: center; gap: 40px; flex-wrap: wrap;
    padding: 40px 0; margin-bottom: 20px;
}
.sd-stat { text-align: center; }
.sd-stat .num {
    font-size: 2.5rem; font-weight: 900;
    background: var(--sd-grad); -webkit-background-clip: text; -webkit-text-fill-color: transparent;
}
.sd-stat .label { color: var(--sd-muted); font-size: 13px; text-transform: uppercase; letter-spacing: 1px; margin-top: 2px; }

/* ── MANIFESTO ── */
.sd-manifesto {
    text-align: center; padding: 60px 0; position: relative;
}
.sd-manifesto::before, .sd-manifesto::after {
    content: ''; position: absolute; left: 10%; right: 10%; height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255,215,0,0.15), transparent);
}
.sd-manifesto::before { top: 0; }
.sd-manifesto::after { bottom: 0; }
.sd-manifesto h2 {
    font-size: clamp(28px, 4vw, 48px); font-weight: 900; letter-spacing: -0.03em;
    margin-bottom: 24px;
    background: var(--sd-grad-cool); -webkit-background-clip: text; -webkit-text-fill-color: transparent;
}
.sd-manifesto .sd-compare {
    display: grid; grid-template-columns: 1fr 1fr; gap: 24px;
    max-width: 900px; margin: 0 auto 40px;
}
.sd-old, .sd-new {
    padding: 30px; border-radius: 16px; text-align: left;
}
.sd-old {
    background: rgba(239, 68, 68, 0.06); border: 1px solid rgba(239, 68, 68, 0.15);
}
.sd-new {
    background: rgba(52, 211, 153, 0.06); border: 1px solid rgba(52, 211, 153, 0.2);
}
.sd-old h3 { color: var(--sd-red); margin-bottom: 16px; font-size: 1.1rem; }
.sd-new h3 { color: var(--sd-green); margin-bottom: 16px; font-size: 1.1rem; }
.sd-old li, .sd-new li {
    list-style: none; padding: 6px 0; font-size: 15px; color: var(--sd-muted);
    display: flex; align-items: flex-start; gap: 8px;
}
.sd-old li::before { content: '✕'; color: var(--sd-red); font-weight: 700; flex-shrink: 0; }
.sd-new li::before { content: '✓'; color: var(--sd-green); font-weight: 700; flex-shrink: 0; }

/* ── TLD CATEGORIES ── */
.sd-categories { padding: 40px 0 60px; }
.sd-categories > h2 {
    text-align: center; font-size: clamp(28px, 4vw, 44px); font-weight: 900;
    margin-bottom: 12px; letter-spacing: -0.03em;
    background: var(--sd-grad); -webkit-background-clip: text; -webkit-text-fill-color: transparent;
}
.sd-categories > p { text-align: center; color: var(--sd-muted); margin-bottom: 40px; font-size: 16px; }

.sd-cat-section { margin-bottom: 48px; }
.sd-cat-header {
    display: flex; align-items: center; gap: 12px; margin-bottom: 16px;
    padding-bottom: 12px; border-bottom: 1px solid var(--sd-border);
}
.sd-cat-header .cat-icon { font-size: 1.5rem; }
.sd-cat-header h3 { font-size: 1.3rem; font-weight: 800; }
.sd-cat-header .cat-count {
    margin-left: auto; color: var(--sd-muted); font-size: 13px;
    background: rgba(255,255,255,0.05); padding: 4px 12px; border-radius: 20px;
}
.sd-cat-desc { color: var(--sd-muted); font-size: 14px; margin-bottom: 16px; }

.sd-tld-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 10px;
}
.sd-tld-card {
    background: var(--sd-surface); border: 1px solid var(--sd-border);
    border-radius: 12px; padding: 14px 16px;
    display: flex; align-items: center; gap: 10px;
    cursor: pointer; transition: all 0.25s;
}
.sd-tld-card:hover { border-color: var(--sd-border-glow); transform: translateY(-2px); }
.sd-tld-card .tld-icon { font-size: 1.3rem; flex-shrink: 0; }
.sd-tld-card .tld-name { font-weight: 700; font-size: 15px; }
.sd-tld-card .tld-price {
    margin-left: auto; font-size: 12px; font-weight: 700; white-space: nowrap;
}
.sd-tld-card .tld-price.free {
    background: linear-gradient(135deg, var(--sd-green), var(--sd-cyan));
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
}
.sd-tld-card .tld-price.paid { color: var(--sd-muted); }
.sd-tld-card.is-free { border-color: rgba(52, 211, 153, 0.15); }
.sd-tld-card.is-free:hover { border-color: rgba(52, 211, 153, 0.4); }

/* ── HOW IT WORKS ── */
.sd-how {
    padding: 60px 0; position: relative;
}
.sd-how::before {
    content: ''; position: absolute; top: 0; left: 10%; right: 10%; height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255,215,0,0.15), transparent);
}
.sd-how h2 {
    text-align: center; font-size: clamp(28px, 4vw, 44px); font-weight: 900;
    margin-bottom: 40px;
    background: var(--sd-grad); -webkit-background-clip: text; -webkit-text-fill-color: transparent;
}
.sd-steps {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 24px;
}
.sd-step {
    background: var(--sd-surface); border: 1px solid var(--sd-border);
    border-radius: 16px; padding: 28px; text-align: center;
    position: relative; overflow: hidden;
}
.sd-step::before {
    content: attr(data-step);
    position: absolute; top: -10px; right: -5px;
    font-size: 6rem; font-weight: 900; opacity: 0.04; color: var(--sd-gold);
    line-height: 1;
}
.sd-step .step-icon { font-size: 2rem; margin-bottom: 12px; }
.sd-step h4 { font-size: 1.1rem; margin-bottom: 8px; color: var(--sd-gold); }
.sd-step p { color: var(--sd-muted); font-size: 14px; line-height: 1.6; }

/* ── AUTHORITY ── */
.sd-authority {
    padding: 60px 0; text-align: center;
}
.sd-authority h2 {
    font-size: clamp(24px, 3.5vw, 40px); font-weight: 900; margin-bottom: 16px;
    background: var(--sd-grad); -webkit-background-clip: text; -webkit-text-fill-color: transparent;
}
.sd-authority .sd-auth-desc { color: var(--sd-muted); max-width: 800px; margin: 0 auto 32px; font-size: 16px; line-height: 1.7; }
.sd-pillars {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;
    max-width: 1000px; margin: 0 auto;
}
.sd-pillar {
    background: var(--sd-surface); border: 1px solid var(--sd-border);
    border-radius: 14px; padding: 24px 18px; text-align: center;
}
.sd-pillar .p-icon { font-size: 2rem; margin-bottom: 8px; }
.sd-pillar h4 { color: var(--sd-gold); font-size: 0.95rem; margin-bottom: 6px; }
.sd-pillar p { color: var(--sd-muted); font-size: 13px; line-height: 1.5; }

/* ── CTA ── */
.sd-cta {
    text-align: center; padding: 60px 0 80px;
}
.sd-cta h2 {
    font-size: clamp(28px, 4vw, 48px); font-weight: 900; margin-bottom: 16px;
    background: var(--sd-grad); -webkit-background-clip: text; -webkit-text-fill-color: transparent;
}
.sd-cta p { color: var(--sd-muted); margin-bottom: 24px; font-size: 16px; }
.sd-btn-primary {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 16px 40px; border-radius: 14px; border: none;
    background: var(--sd-grad); color: #000; font-weight: 800;
    font-size: 17px; cursor: pointer; letter-spacing: 0.02em;
    text-decoration: none; transition: filter 0.2s, transform 0.2s;
}
.sd-btn-primary:hover { filter: brightness(1.15); transform: translateY(-2px); color: #000; }
.sd-btn-outline {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 14px 32px; border-radius: 14px;
    border: 2px solid var(--sd-gold); background: transparent;
    color: var(--sd-gold); font-weight: 700; font-size: 15px;
    cursor: pointer; text-decoration: none; margin-left: 12px;
    transition: all 0.2s;
}
.sd-btn-outline:hover { background: var(--sd-gold); color: #000; }

/* ── SEARCH RESULTS ── */
.sr-item {
    display: flex; align-items: center; gap: 14px;
    padding: 14px 20px; margin-bottom: 8px;
    background: var(--sd-surface); border: 1px solid var(--sd-border);
    border-radius: 12px; transition: all 0.2s;
}
.sr-item:hover { border-color: var(--sd-border-glow); }
.sr-item .sr-icon { font-size: 1.3rem; flex-shrink: 0; }
.sr-item .sr-domain { font-weight: 700; font-size: 16px; flex: 1; }
.sr-item .sr-status { font-size: 13px; font-weight: 700; }
.sr-item .sr-status.avail { color: var(--sd-green); }
.sr-item .sr-status.taken { color: var(--sd-red); opacity: 0.6; }
.sr-item .sr-price { font-size: 13px; color: var(--sd-muted); margin-left: 8px; }
.sr-item .sr-register {
    padding: 6px 16px; border-radius: 8px; border: none;
    background: var(--sd-green); color: #000; font-weight: 700;
    font-size: 13px; cursor: pointer; white-space: nowrap;
}
.sr-item .sr-register:hover { filter: brightness(1.1); }

/* ── RESPONSIVE ── */
@media (max-width: 768px) {
    .sd-hero { padding: 80px 0 40px; }
    .sd-manifesto .sd-compare { grid-template-columns: 1fr; }
    .sd-tld-grid { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); }
    .sd-stats { gap: 20px; }
    .sd-search-input { padding-right: 120px; font-size: 16px; }
    .sd-btn-outline { margin-left: 0; margin-top: 8px; }
}
</style>

<div class="sd-page">
<div class="sd-wrap">

<!-- ═══════════════════════════════════════════════════════ -->
<!-- HERO                                                    -->
<!-- ═══════════════════════════════════════════════════════ -->
<section class="sd-hero">
    <div class="sd-kicker">✝️ FOR THE KINGDOM OF GOD — <?= number_format($totalTLDs) ?> TLDs LIVE</div>
    <h1>Own Your Name.<br>Forever.</h1>
    <p class="sd-sub">
        The old internet rented you a name and charged you every year to keep it. 
        ICANN, Verisign, GoDaddy — they built tollbooths on your identity.<br>
        <strong>That era is over.</strong>
    </p>
    <p class="sd-verse">"The earth is the LORD's, and everything in it." — Psalm 24:1</p>
    
    <!-- SEARCH -->
    <div class="sd-search-wrap">
        <input type="text" class="sd-search-input" id="domain-search" 
               placeholder="Search your name... (e.g., yourname)" 
               onkeydown="if(event.key==='Enter')searchDomain()" autofocus>
        <button class="sd-search-btn" onclick="searchDomain()">
            <i class="fas fa-search"></i> Search
        </button>
    </div>
    <div class="sd-search-results" id="search-results"></div>
</section>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- STATS                                                   -->
<!-- ═══════════════════════════════════════════════════════ -->
<div class="sd-stats">
    <div class="sd-stat"><div class="num"><?= number_format($totalTLDs) ?></div><div class="label">Sovereign TLDs</div></div>
    <div class="sd-stat"><div class="num"><?= number_format($freeTLDs) ?></div><div class="label">Free TLDs</div></div>
    <div class="sd-stat"><div class="num"><?= number_format($totalDomains) ?></div><div class="label">Domains Registered</div></div>
    <div class="sd-stat"><div class="num">$0</div><div class="label">.com Price</div></div>
    <div class="sd-stat"><div class="num">∞</div><div class="label">Ownership Period</div></div>
</div>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- MANIFESTO — Old World vs Kingdom                        -->
<!-- ═══════════════════════════════════════════════════════ -->
<section class="sd-manifesto">
    <h2>The Old World vs The Kingdom</h2>
    <div class="sd-compare">
        <div class="sd-old">
            <h3>❌ The Old World (ICANN / Verisign)</h3>
            <ul>
                <li>Pay $12-50/year to rent a .com — forever</li>
                <li>WHOIS exposes your real name and address</li>
                <li>Domains can be seized by governments</li>
                <li>Registrars sell your data to marketers</li>
                <li>DNS controlled by corporations</li>
                <li>No privacy without paying extra</li>
                <li>Forget to renew? Someone else takes your name</li>
                <li>You never truly own anything</li>
            </ul>
        </div>
        <div class="sd-new">
            <h3>✅ The Kingdom (Sovereign Web)</h3>
            <ul>
                <li>.com is FREE — no annual fees, ever</li>
                <li>Privacy by default — zero data harvesting</li>
                <li>Sovereign DNS — no government seizure</li>
                <li>Your data stays yours — we don't sell anything</li>
                <li>DNS controlled by YOU, rooted in the Kingdom</li>
                <li>Pay with GSM token, SOL, or cash — your choice</li>
                <li>Auto-renewal built in — your name is yours</li>
                <li>YOU own it. Period. Under God's authority.</li>
            </ul>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- TLD CATEGORIES                                          -->
<!-- ═══════════════════════════════════════════════════════ -->
<section class="sd-categories">
    <h2><?= number_format($totalTLDs) ?> TLDs — Every Domain You Could Dream Of</h2>
    <p>From .god to .gov, .king to .code — the entire namespace, secured for the Kingdom.</p>

    <?php foreach ($categoryMeta as $catKey => $meta):
        if (!isset($tldsByCategory[$catKey])) continue;
        $catTLDs = $tldsByCategory[$catKey];
    ?>
    <div class="sd-cat-section">
        <div class="sd-cat-header">
            <span class="cat-icon"><?= $meta['icon'] ?></span>
            <h3 style="color:<?= $meta['color'] ?>"><?= $meta['label'] ?></h3>
            <span class="cat-count"><?= count($catTLDs) ?> TLDs</span>
        </div>
        <p class="sd-cat-desc"><?= $meta['desc'] ?></p>
        <div class="sd-tld-grid">
            <?php foreach ($catTLDs as $t):
                $isFree = (float)$t['price_usd'] == 0;
            ?>
            <div class="sd-tld-card <?= $isFree ? 'is-free' : '' ?>" onclick="searchSpecific('<?= htmlspecialchars($t['tld']) ?>')">
                <span class="tld-icon"><?= $t['icon'] ?></span>
                <span class="tld-name">.<?= htmlspecialchars($t['tld']) ?></span>
                <span class="tld-price <?= $isFree ? 'free' : 'paid' ?>">
                    <?= $isFree ? 'FREE' : '$' . number_format($t['price_usd'], 2) ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</section>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- HOW IT WORKS                                            -->
<!-- ═══════════════════════════════════════════════════════ -->
<section class="sd-how">
    <h2>How It Works</h2>
    <div class="sd-steps">
        <div class="sd-step" data-step="1">
            <div class="step-icon">🔍</div>
            <h4>Search Your Name</h4>
            <p>Type any name and see it available across <?= number_format($totalTLDs) ?> TLDs instantly. Your identity, your choice.</p>
        </div>
        <div class="sd-step" data-step="2">
            <div class="step-icon">💳</div>
            <h4>Register — Free or Premium</h4>
            <p><?= $freeTLDs ?> TLDs are completely free. Premium TLDs accept GSM tokens, SOL, or traditional payment. No hidden fees.</p>
        </div>
        <div class="sd-step" data-step="3">
            <div class="step-icon">🌐</div>
            <h4>Point Your DNS</h4>
            <p>Set your A record to any IP. Host on GoHostMe, your own server, or anywhere. Your domain, your infrastructure.</p>
        </div>
        <div class="sd-step" data-step="4">
            <div class="step-icon">👑</div>
            <h4>Sovereign Forever</h4>
            <p>Your domain resolves through Sovereign DNS. No ICANN politics. No corporate DNS hijacking. The Kingdom protects your name.</p>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- AUTHORITY                                               -->
<!-- ═══════════════════════════════════════════════════════ -->
<section class="sd-authority">
    <h2>We Don't Register Through ICANN.<br>We ARE the Authority.</h2>
    <p class="sd-auth-desc">
        GoSiteMe operates as the root namespace authority of the Sovereign Web. 
        We built the browser. We built the DNS. We built the registrar. We built the hosting.
        From root authority to end user — one Kingdom, one chain of trust, under God.
    </p>
    <div class="sd-pillars">
        <div class="sd-pillar">
            <div class="p-icon">🌐</div>
            <h4>Root Authority</h4>
            <p>We control the TLD registry — <?= number_format($totalTLDs) ?> TLDs and growing. We ARE the ICANN of the Sovereign Web.</p>
        </div>
        <div class="sd-pillar">
            <div class="p-icon">🔗</div>
            <h4>Sovereign DNS</h4>
            <p>Our DNS resolvers run 24/7, translating sovereign domains to IPs. No middlemen. No censorship.</p>
        </div>
        <div class="sd-pillar">
            <div class="p-icon">🦁</div>
            <h4>Alfred Browser</h4>
            <p>Native sovereign domain resolution built into the browser. No extensions. No hacks. Just type and go.</p>
        </div>
        <div class="sd-pillar">
            <div class="p-icon">🏦</div>
            <h4>GSM Economy</h4>
            <p>Pay for premium domains with GSM tokens — live on Solana mainnet. The currency of the Kingdom.</p>
        </div>
        <div class="sd-pillar">
            <div class="p-icon">🏠</div>
            <h4>GoHostMe</h4>
            <p>Full hosting platform. Point your sovereign domain to our servers — websites, email, everything.</p>
        </div>
        <div class="sd-pillar">
            <div class="p-icon">🛡️</div>
            <h4>Military Security</h4>
            <p>Post-quantum encryption. Zero-knowledge privacy. 27-rank military governance system protecting the namespace.</p>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- ENTERPRISE                                              -->
<!-- ═══════════════════════════════════════════════════════ -->
<section style="padding:40px 0;text-align:center">
    <h2 style="font-size:clamp(24px,3.5vw,38px);font-weight:900;margin-bottom:12px;background:var(--sd-grad-cool);-webkit-background-clip:text;-webkit-text-fill-color:transparent">
        Enterprise & Government
    </h2>
    <p style="color:var(--sd-muted);max-width:700px;margin:0 auto 28px;font-size:15px;line-height:1.7">
        Corporations, governments, and institutions can license their own TLDs within the Kingdom.
        Your company name becomes a top-level domain — <strong>.nike</strong>, <strong>.nasa</strong>, <strong>.harvard</strong>.
        You operate as registrar within your TLD. We maintain root authority.
    </p>
    <div style="display:inline-flex;gap:16px;flex-wrap:wrap;justify-content:center">
        <div style="background:var(--sd-surface);border:1px solid var(--sd-border);border-radius:12px;padding:20px 28px;text-align:center">
            <div style="font-size:2rem;margin-bottom:4px">🏢</div>
            <div style="color:var(--sd-gold);font-weight:700;font-size:14px">.corp / .inc / .enterprise</div>
            <div style="color:var(--sd-muted);font-size:12px">Corporate identity</div>
        </div>
        <div style="background:var(--sd-surface);border:1px solid var(--sd-border);border-radius:12px;padding:20px 28px;text-align:center">
            <div style="font-size:2rem;margin-bottom:4px">🏛️</div>
            <div style="color:var(--sd-gold);font-weight:700;font-size:14px">.gov / .state / .nation</div>
            <div style="color:var(--sd-muted);font-size:12px">Sovereign governance</div>
        </div>
        <div style="background:var(--sd-surface);border:1px solid var(--sd-border);border-radius:12px;padding:20px 28px;text-align:center">
            <div style="font-size:2rem;margin-bottom:4px">🎓</div>
            <div style="color:var(--sd-gold);font-weight:700;font-size:14px">.edu / .academy / .school</div>
            <div style="color:var(--sd-muted);font-size:12px">Education</div>
        </div>
        <div style="background:var(--sd-surface);border:1px solid var(--sd-border);border-radius:12px;padding:20px 28px;text-align:center">
            <div style="font-size:2rem;margin-bottom:4px">🎖️</div>
            <div style="color:var(--sd-gold);font-weight:700;font-size:14px">.military / .defense / .intel</div>
            <div style="color:var(--sd-muted);font-size:12px">Defense & intelligence</div>
        </div>
    </div>
    <p style="color:var(--sd-muted);font-size:13px;margin-top:20px">
        Interested in a custom TLD? <a href="/contact" style="color:var(--sd-gold)">Contact the Registry Authority</a>
    </p>
</section>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- FINAL CTA                                               -->
<!-- ═══════════════════════════════════════════════════════ -->
<section class="sd-cta">
    <h2>Your Name. Your Kingdom.<br>Claim It Now.</h2>
    <p>"For where your treasure is, there your heart will be also." — Matthew 6:21</p>
    <div>
        <a href="#" class="sd-btn-primary" onclick="document.getElementById('domain-search').focus();window.scrollTo({top:0,behavior:'smooth'});return false">
            <i class="fas fa-crown"></i> Search Domains
        </a>
        <a href="/sovereign-browser" class="sd-btn-outline">
            <i class="fas fa-globe"></i> Get Alfred Browser
        </a>
    </div>
</section>

</div><!-- .sd-wrap -->
</div><!-- .sd-page -->

<script>
async function searchDomain() {
    const input = document.getElementById('domain-search').value.trim().toLowerCase().replace(/[^a-z0-9-]/g, '');
    if (!input) return;
    
    const results = document.getElementById('search-results');
    results.innerHTML = '<div style="text-align:center;padding:20px;color:var(--sd-muted)"><i class="fas fa-spinner fa-spin"></i> Searching across <?= number_format($totalTLDs) ?> TLDs...</div>';
    
    try {
        const resp = await fetch('/api/sovereign-domains.php?action=search&domain=' + encodeURIComponent(input));
        const data = await resp.json();
        
        if (!data.results || !data.results.length) {
            results.innerHTML = '<div style="text-align:center;padding:20px;color:var(--sd-muted)">No results found</div>';
            return;
        }
        
        // Sort: available first, free first within available
        data.results.sort((a, b) => {
            if (a.available !== b.available) return a.available ? -1 : 1;
            if (a.available) return a.price_usd - b.price_usd;
            return 0;
        });
        
        let html = '';
        let shown = 0;
        for (const r of data.results) {
            if (shown >= 30) break;
            const isFree = r.price_usd === 0;
            html += '<div class="sr-item">';
            html += '<span class="sr-icon">' + (r.icon || '🌐') + '</span>';
            html += '<span class="sr-domain">' + esc(r.domain) + '</span>';
            if (r.available) {
                html += '<span class="sr-status avail">✅ Available</span>';
                html += '<span class="sr-price">' + (isFree ? '<span style="color:var(--sd-green);font-weight:700">FREE</span>' : '$' + r.price_usd.toFixed(2)) + '</span>';
                html += '<button class="sr-register" onclick="claimDomain(\'' + esc(r.domain) + '\')">Claim</button>';
            } else {
                html += '<span class="sr-status taken">Taken</span>';
            }
            html += '</div>';
            shown++;
        }
        
        const avail = data.results.filter(r => r.available).length;
        const total = data.results.length;
        html = '<div style="color:var(--sd-muted);font-size:13px;margin-bottom:12px;text-align:center">' + 
               avail + ' of ' + total + ' TLDs available for <strong style="color:var(--sd-gold)">' + esc(input) + '</strong></div>' + html;
        
        if (data.results.length > 30) {
            html += '<div style="text-align:center;padding:12px;color:var(--sd-muted);font-size:13px">Showing top 30 results. ' + (data.results.length - 30) + ' more available.</div>';
        }
        
        results.innerHTML = html;
    } catch (e) {
        results.innerHTML = '<div style="text-align:center;padding:20px;color:var(--sd-red)">Search failed. Try again.</div>';
    }
}

function searchSpecific(tld) {
    const input = document.getElementById('domain-search');
    if (!input.value.trim()) {
        input.value = 'yourname';
    }
    input.focus();
    searchDomain();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function claimDomain(domain) {
    // For now, redirect to registration flow
    window.location.href = '/register?domain=' + encodeURIComponent(domain) + '&from=sovereign';
}

function esc(s) {
    if (!s) return '';
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

// Auto-search on URL param
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('search')) {
    document.getElementById('domain-search').value = urlParams.get('search');
    searchDomain();
}
</script>

<?php require_once __DIR__ . '/includes/site-footer.inc.php'; ?>
