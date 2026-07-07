<?php
/**
 * Alfred Browser — Sovereign browsing platform
 * Download page + feature showcase for the Alfred-controlled browser
 */
$page_title = 'Alfred Browser — The Sovereign Browser | GoSiteMe';
$page_description = 'The browser that fights for you. Post-quantum encrypted, zero tracking, AI-powered, with built-in Veil Protocol, emergency mesh networking, and complete data sovereignty. Download for Windows, macOS, Linux, Android.';
$page_canonical = 'https://gositeme.com/alfred-browser';
require_once __DIR__ . '/includes/site-header.inc.php';
?>
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<link rel="stylesheet" href="/assets/css/fonts.css">

<style>
:root {
    --ab-void: #030308;
    --ab-surface: #0c0c1a;
    --ab-card: rgba(91,156,245,0.03);
    --ab-border: rgba(91,156,245,0.08);
    --ab-blue: #5b9cf5;
    --ab-indigo: #7c5cfc;
    --ab-cyan: #22d3ee;
    --ab-green: #34d399;
    --ab-amber: #fbbf24;
    --ab-red: #ef4444;
    --ab-text: rgba(255,255,255,0.88);
    --ab-muted: rgba(255,255,255,0.5);
    --ab-grad: linear-gradient(135deg, #5b9cf5 0%, #7c5cfc 50%, #a855f7 100%);
    --ab-radius: 16px;
}
.ab-page { background: var(--ab-void); color: var(--ab-text); font-family: 'Inter','DM Sans',system-ui,sans-serif; min-height: 100vh; overflow-x: hidden; }
.ab-page a { color: var(--ab-blue); text-decoration: none; }
.ab-page a:hover { text-decoration: underline; }
.ab-container { max-width: 1100px; margin: 0 auto; padding: 0 20px; }

/* Hero */
.ab-hero {
    position: relative;
    text-align: center;
    padding: clamp(80px,14vw,160px) 20px 80px;
    overflow: hidden;
}
.ab-hero::before {
    content: '';
    position: absolute;
    top: -40%;
    left: 50%;
    width: 1000px;
    height: 1000px;
    transform: translateX(-50%);
    background: radial-gradient(circle, rgba(91,156,245,0.06) 0%, rgba(124,92,252,0.04) 40%, transparent 70%);
    pointer-events: none;
}
.ab-pills {
    display: flex;
    gap: 8px;
    justify-content: center;
    flex-wrap: wrap;
    margin-bottom: 28px;
}
.ab-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 14px;
    border-radius: 50px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.ab-pill-blue { background: rgba(91,156,245,0.1); color: var(--ab-blue); border: 1px solid rgba(91,156,245,0.15); }
.ab-pill-green { background: rgba(52,211,153,0.1); color: var(--ab-green); border: 1px solid rgba(52,211,153,0.15); }
.ab-pill-indigo { background: rgba(124,92,252,0.1); color: var(--ab-indigo); border: 1px solid rgba(124,92,252,0.15); }

.ab-hero h1 {
    font-size: clamp(40px, 7vw, 76px);
    font-weight: 900;
    letter-spacing: -3px;
    line-height: 1.05;
    margin-bottom: 20px;
    background: var(--ab-grad);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text;
}
.ab-hero p {
    font-size: clamp(15px, 2vw, 19px);
    color: var(--ab-muted);
    max-width: 680px;
    margin: 0 auto 40px;
    line-height: 1.7;
}

/* Download Grid */
.ab-downloads {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
    max-width: 900px;
    margin: 0 auto;
}
.ab-dl {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    border-radius: 14px;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.08);
    transition: all 0.25s;
    cursor: pointer;
    text-decoration: none !important;
    color: var(--ab-text) !important;
}
.ab-dl:hover {
    transform: translateY(-2px);
    border-color: rgba(91,156,245,0.25);
    background: rgba(91,156,245,0.06);
    box-shadow: 0 8px 32px rgba(0,0,0,0.3);
}
.ab-dl-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}
.ab-dl-info h4 { font-size: 14px; font-weight: 700; margin-bottom: 2px; }
.ab-dl-info span { font-size: 11px; color: var(--ab-muted); }
.ab-dl-arrow { margin-left: auto; color: var(--ab-muted); font-size: 14px; }

/* Features */
.ab-section { padding: 80px 0; }
.ab-section-title {
    font-size: clamp(28px, 5vw, 44px);
    font-weight: 900;
    letter-spacing: -1.5px;
    margin-bottom: 12px;
}
.ab-section-sub {
    color: var(--ab-muted);
    font-size: 16px;
    margin-bottom: 40px;
    max-width: 600px;
    line-height: 1.7;
}
.ab-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }

.ab-feature {
    background: var(--ab-card);
    border: 1px solid var(--ab-border);
    border-radius: var(--ab-radius);
    padding: 32px;
    position: relative;
    overflow: hidden;
    transition: all 0.25s;
}
.ab-feature:hover {
    transform: translateY(-2px);
    border-color: rgba(91,156,245,0.2);
}
.ab-feature::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 2px;
    opacity: 0;
    transition: opacity 0.3s;
}
.ab-feature:hover::before { opacity: 1; }
.ab-feature-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    margin-bottom: 20px;
}
.ab-feature h3 { font-size: 18px; font-weight: 700; color: #fff; margin-bottom: 10px; }
.ab-feature p { font-size: 14px; color: var(--ab-muted); line-height: 1.7; }
.ab-feature .ab-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 50px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 12px;
}

/* Comparison */
.ab-compare {
    overflow-x: auto;
    margin-top: 32px;
}
.ab-compare table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
    min-width: 600px;
}
.ab-compare th, .ab-compare td {
    padding: 14px 20px;
    text-align: left;
    border-bottom: 1px solid rgba(255,255,255,0.05);
}
.ab-compare th {
    color: var(--ab-muted);
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.ab-compare td:first-child { font-weight: 600; color: #fff; }
.ab-compare .yes { color: var(--ab-green); }
.ab-compare .no { color: var(--ab-red); opacity: 0.6; }
.ab-compare .partial { color: var(--ab-amber); }
.ab-compare tr:last-child td { border: none; }
.ab-compare .ab-row { background: rgba(91,156,245,0.04); }
.ab-compare .ab-row td:first-child {
    background: var(--ab-grad);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    font-weight: 800;
}

/* Stats strip */
.ab-stats {
    display: flex;
    gap: 40px;
    justify-content: center;
    flex-wrap: wrap;
    padding: 48px 0;
    border-top: 1px solid rgba(255,255,255,0.05);
    border-bottom: 1px solid rgba(255,255,255,0.05);
    margin: 40px 0;
}
.ab-stat { text-align: center; }
.ab-stat-val {
    font-size: 32px;
    font-weight: 900;
    background: var(--ab-grad);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.ab-stat-lbl {
    font-size: 12px;
    color: var(--ab-muted);
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-top: 4px;
}

/* CTA */
.ab-cta-section {
    text-align: center;
    padding: 80px 20px;
    position: relative;
}
.ab-cta-section::before {
    content: '';
    position: absolute;
    top: 0; left: 50%;
    width: 600px; height: 600px;
    transform: translateX(-50%) translateY(-50%);
    background: radial-gradient(circle, rgba(124,92,252,0.06), transparent 60%);
    pointer-events: none;
}
.ab-cta-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 16px 36px;
    border-radius: 14px;
    font-size: 16px;
    font-weight: 700;
    background: var(--ab-grad);
    color: #fff;
    border: none;
    cursor: pointer;
    font-family: inherit;
    transition: all 0.2s;
    text-decoration: none !important;
}
.ab-cta-btn:hover { transform: translateY(-2px); filter: brightness(1.1); box-shadow: 0 8px 32px rgba(91,156,245,0.25); }

/* Responsive */
@media (max-width: 768px) {
    .ab-hero h1 { letter-spacing: -2px; }
    .ab-downloads { grid-template-columns: 1fr; }
    .ab-grid { grid-template-columns: 1fr; }
    .ab-stats { gap: 24px; }
    .ab-stat-val { font-size: 24px; }
}
</style>

<div class="ab-page">
    <!-- Hero -->
    <div class="ab-hero">
        <div class="ab-pills">
            <span class="ab-pill ab-pill-green"><i class="fas fa-shield-alt"></i> Post-Quantum Secure</span>
            <span class="ab-pill ab-pill-blue"><i class="fas fa-brain"></i> AI-Powered</span>
            <span class="ab-pill ab-pill-indigo"><i class="fas fa-crown"></i> 100% Sovereign</span>
            <span class="ab-pill" style="background:rgba(251,191,36,0.1);color:var(--ab-amber);border:1px solid rgba(251,191,36,0.15);"><i class="fas fa-coins"></i> Mine &amp; Earn</span>
            <span class="ab-pill" style="background:rgba(34,211,238,0.1);color:var(--ab-cyan);border:1px solid rgba(34,211,238,0.15);"><i class="fas fa-globe"></i> Full Ecosystem</span>
        </div>
        <h1>The Browser That<br>Fights For You<br>&amp; Pays You</h1>
        <p>Alfred Browser eliminates tracking, encrypts everything with post-quantum cryptography, and <strong style="color:var(--ab-green);">mines crypto while you browse</strong>. Earn GSM tokens, access 13,000+ AI tools, games, a social hub, VR worlds, and an entire sovereign ecosystem — all from one app. Free forever.</p>

        <!-- Download Grid -->
        <div class="ab-downloads">
            <a href="/downloads/Alfred-Browser-3.0.0-win-x64.zip" class="ab-dl">
                <div class="ab-dl-icon" style="background:rgba(91,156,245,0.1);color:var(--ab-blue);"><i class="fab fa-windows"></i></div>
                <div class="ab-dl-info">
                    <h4>Windows</h4>
                    <span>Portable x64 &middot; Auto-updates</span>
                </div>
                <i class="fas fa-arrow-down ab-dl-arrow"></i>
            </a>
            <a href="/downloads/Alfred-Browser-3.0.0-mac-intel.zip" class="ab-dl">
                <div class="ab-dl-icon" style="background:rgba(255,255,255,0.06);color:#fff;"><i class="fab fa-apple"></i></div>
                <div class="ab-dl-info">
                    <h4>macOS Intel</h4>
                    <span>x64 Zip &middot; macOS 11+</span>
                </div>
                <i class="fas fa-arrow-down ab-dl-arrow"></i>
            </a>
            <a href="/downloads/Alfred-Browser-3.0.0-mac-arm64.zip" class="ab-dl">
                <div class="ab-dl-icon" style="background:rgba(255,255,255,0.06);color:#fff;"><i class="fab fa-apple"></i></div>
                <div class="ab-dl-info">
                    <h4>macOS Apple Silicon</h4>
                    <span>ARM64 Zip &middot; M1/M2/M3/M4</span>
                </div>
                <i class="fas fa-arrow-down ab-dl-arrow"></i>
            </a>
            <a href="/downloads/Alfred-Browser-3.0.0.AppImage" class="ab-dl">
                <div class="ab-dl-icon" style="background:rgba(251,191,36,0.1);color:var(--ab-amber);"><i class="fab fa-linux"></i></div>
                <div class="ab-dl-info">
                    <h4>Linux AppImage</h4>
                    <span>x64 &middot; Universal &middot; Auto-updates</span>
                </div>
                <i class="fas fa-arrow-down ab-dl-arrow"></i>
            </a>
            <a href="/downloads/alfred-browser_3.0.0_amd64.deb" class="ab-dl">
                <div class="ab-dl-icon" style="background:rgba(221,72,20,0.1);color:#dd4814;"><i class="fab fa-ubuntu"></i></div>
                <div class="ab-dl-info">
                    <h4>Ubuntu / Debian</h4>
                    <span>.deb x64 &middot; apt compatible</span>
                </div>
                <i class="fas fa-arrow-down ab-dl-arrow"></i>
            </a>
            <a href="/downloads/Alfred-Browser.apk" class="ab-dl">
                <div class="ab-dl-icon" style="background:rgba(52,211,153,0.1);color:var(--ab-green);"><i class="fab fa-android"></i></div>
                <div class="ab-dl-info">
                    <h4>Android</h4>
                    <span>APK &middot; Update checks on launch</span>
                </div>
                <i class="fas fa-arrow-down ab-dl-arrow"></i>
            </a>
            <a href="#ios" class="ab-dl" id="ios">
                <div class="ab-dl-icon" style="background:rgba(255,255,255,0.06);color:#fff;"><i class="fab fa-apple"></i></div>
                <div class="ab-dl-info">
                    <h4>iOS / iPad</h4>
                    <span>PWA &middot; Add to Home Screen</span>
                </div>
                <i class="fas fa-plus-circle ab-dl-arrow" style="color:var(--ab-cyan)"></i>
            </a>
            <a href="/downloads/alfred-chrome-extension/" class="ab-dl">
                <div class="ab-dl-icon" style="background:rgba(124,92,252,0.1);color:var(--ab-indigo);"><i class="fab fa-chrome"></i></div>
                <div class="ab-dl-info">
                    <h4>Chrome Extension</h4>
                    <span>Add sovereignty to Chrome</span>
                </div>
                <i class="fas fa-arrow-down ab-dl-arrow"></i>
            </a>
        </div>
    </div>

    <div class="ab-container">
        <!-- iOS Install Instructions -->
        <div class="ab-section" id="ios-install" style="margin-bottom:3rem;">
            <h2 class="ab-section-title"><i class="fab fa-apple"></i> Install on iOS &amp; iPad</h2>
            <p class="ab-section-sub">GoSiteMe is a Progressive Web App — install it directly from Safari with full offline support, push notifications, and home screen access. No App Store required.</p>
            <div class="ab-grid" style="grid-template-columns: repeat(3, 1fr);">
                <div class="ab-feature" style="--accent: var(--ab-cyan);">
                    <div class="ab-feature-icon" style="background:rgba(34,211,238,0.1);color:var(--ab-cyan);font-size:1.5rem;"><strong>1</strong></div>
                    <h3>Open in Safari</h3>
                    <p>Visit <strong>gositeme.com</strong> in Safari on your iPhone or iPad. This must be Safari — Chrome/Firefox on iOS don't support PWA install.</p>
                </div>
                <div class="ab-feature" style="--accent: var(--ab-blue);">
                    <div class="ab-feature-icon" style="background:rgba(91,156,245,0.1);color:var(--ab-blue);"><i class="fas fa-share-square"></i></div>
                    <h3>Tap Share</h3>
                    <p>Tap the <strong>Share</strong> button (square with arrow) at the bottom of Safari, then scroll down and tap <strong>"Add to Home Screen"</strong>.</p>
                </div>
                <div class="ab-feature" style="--accent: var(--ab-green);">
                    <div class="ab-feature-icon" style="background:rgba(52,211,153,0.1);color:var(--ab-green);"><i class="fas fa-check-circle"></i></div>
                    <h3>Launch &amp; Go</h3>
                    <p>The GoSiteMe icon appears on your home screen. Opens full-screen with no browser bars — just like a native app. Auto-updates instantly.</p>
                </div>
            </div>
        </div>

        <!-- Trust Stats -->
        <div class="ab-stats">
            <div class="ab-stat">
                <div class="ab-stat-val">0</div>
                <div class="ab-stat-lbl">Trackers</div>
            </div>
            <div class="ab-stat">
                <div class="ab-stat-val">0</div>
                <div class="ab-stat-lbl">Telemetry</div>
            </div>
            <div class="ab-stat">
                <div class="ab-stat-val">256</div>
                <div class="ab-stat-lbl">Bit Encryption</div>
            </div>
            <div class="ab-stat">
                <div class="ab-stat-val">PQ</div>
                <div class="ab-stat-lbl">Quantum-Safe</div>
            </div>
            <div class="ab-stat">
                <div class="ab-stat-val">E2E</div>
                <div class="ab-stat-lbl">Veil Protocol</div>
            </div>
            <div class="ab-stat">
                <div class="ab-stat-val">100%</div>
                <div class="ab-stat-lbl">Open Source</div>
            </div>
        </div>

        <!-- Core Features -->
        <div class="ab-section">
            <h2 class="ab-section-title">Everything a Browser Should Be</h2>
            <p class="ab-section-sub">Designed from scratch with sovereignty as the foundation — not bolted on as an afterthought.</p>
            <div class="ab-grid">
                <div class="ab-feature" style="--accent: var(--ab-green);">
                    <div class="ab-feature::before" style="background: linear-gradient(90deg, var(--ab-green), transparent);"></div>
                    <div class="ab-feature-icon" style="background:rgba(52,211,153,0.1);color:var(--ab-green);"><i class="fas fa-shield-alt"></i></div>
                    <h3>Veil Protocol Built-In</h3>
                    <p>Every connection encrypted with AES-256-GCM + Kyber-1024 post-quantum cryptography. Pre-key bundles, device attestation, and key transparency — no extensions needed.</p>
                    <span class="ab-badge" style="background:rgba(52,211,153,0.1);color:var(--ab-green);"><i class="fas fa-check"></i> Active</span>
                </div>
                <div class="ab-feature">
                    <div class="ab-feature-icon" style="background:rgba(91,156,245,0.1);color:var(--ab-blue);"><i class="fas fa-search"></i></div>
                    <h3>Alfred Search Engine</h3>
                    <p>Sovereign search built into the address bar. AI-powered instant answers, deep research mode, voice search — all zero-tracking. Your queries never leave your control.</p>
                    <span class="ab-badge" style="background:rgba(91,156,245,0.1);color:var(--ab-blue);"><i class="fas fa-check"></i> Active</span>
                </div>
                <div class="ab-feature">
                    <div class="ab-feature-icon" style="background:rgba(239,68,68,0.1);color:var(--ab-red);"><i class="fas fa-broadcast-tower"></i></div>
                    <h3>Emergency Mesh Network</h3>
                    <p>When the internet goes dark, Alfred Browser creates peer-to-peer mesh connections via WiFi Direct and Bluetooth LE. Communicate when infrastructure fails.</p>
                    <span class="ab-badge" style="background:rgba(251,191,36,0.1);color:var(--ab-amber);"><i class="fas fa-flask"></i> Beta</span>
                </div>
                <div class="ab-feature">
                    <div class="ab-feature-icon" style="background:rgba(124,92,252,0.1);color:var(--ab-indigo);"><i class="fas fa-brain"></i></div>
                    <h3>Alfred AI Assistant</h3>
                    <p>Built-in AI that helps you browse, summarize pages, translate content, and analyze data — all processed locally or through your sovereign AI servers. Never phones home.</p>
                    <span class="ab-badge" style="background:rgba(124,92,252,0.1);color:var(--ab-indigo);"><i class="fas fa-check"></i> Active</span>
                </div>
                <div class="ab-feature">
                    <div class="ab-feature-icon" style="background:rgba(34,211,238,0.1);color:var(--ab-cyan);"><i class="fas fa-fingerprint"></i></div>
                    <h3>Anti-Fingerprinting</h3>
                    <p>Canvas noise injection, WebGL hash randomization, font enumeration blocking, timing attack mitigation. You look like everyone else — invisible in the crowd.</p>
                    <span class="ab-badge" style="background:rgba(52,211,153,0.1);color:var(--ab-green);"><i class="fas fa-check"></i> Active</span>
                </div>
                <div class="ab-feature">
                    <div class="ab-feature-icon" style="background:rgba(251,191,36,0.1);color:var(--ab-amber);"><i class="fas fa-download"></i></div>
                    <h3>Offline Knowledge Vault</h3>
                    <p>Cache entire websites, emergency medical guides, survival manuals, and offline maps. When everything else fails, your browser still works.</p>
                    <span class="ab-badge" style="background:rgba(52,211,153,0.1);color:var(--ab-green);"><i class="fas fa-check"></i> Active</span>
                </div>
            </div>
        </div>

        <!-- ═══ MINE & EARN ═══ -->
        <div class="ab-section" style="text-align:center;">
            <div style="display:inline-flex;align-items:center;gap:8px;padding:6px 16px;border-radius:50px;background:rgba(251,191,36,0.1);border:1px solid rgba(251,191,36,0.15);color:var(--ab-amber);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;margin-bottom:20px;"><i class="fas fa-fire"></i> THIS CHANGES EVERYTHING</div>
            <h2 class="ab-section-title" style="text-align:center;">Browse the Web.<br>Mine Crypto.<br><span style="background:linear-gradient(135deg,#fbbf24,#f59e0b,#d97706);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">Get Paid.</span></h2>
            <p class="ab-section-sub" style="text-align:center;margin:0 auto 48px;">Alfred Browser runs a lightweight miner in the background — zero impact on your browsing speed. Every minute you browse, you earn GSM tokens. Cash them out, trade them, or stake them. Your browser finally works <em>for you</em>.</p>
            <div class="ab-grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
                <div class="ab-feature" style="border-color:rgba(251,191,36,0.15);">
                    <div class="ab-feature-icon" style="background:rgba(251,191,36,0.1);color:var(--ab-amber);"><i class="fas fa-microchip"></i></div>
                    <h3>Background Mining</h3>
                    <p>CPU-friendly mining runs while you browse. Configurable intensity — go full power or barely-there. Pause anytime. Your machine, your rules.</p>
                    <span class="ab-badge" style="background:rgba(52,211,153,0.1);color:var(--ab-green);"><i class="fas fa-check"></i> Active</span>
                </div>
                <div class="ab-feature" style="border-color:rgba(251,191,36,0.15);">
                    <div class="ab-feature-icon" style="background:rgba(251,191,36,0.1);color:var(--ab-amber);"><i class="fas fa-coins"></i></div>
                    <h3>GSM Token Rewards</h3>
                    <p>Earn GSM tokens — the currency of the GoSiteMe ecosystem. Use them for AI tools, marketplace purchases, domain services, or trade on DeFi exchanges.</p>
                    <span class="ab-badge" style="background:rgba(52,211,153,0.1);color:var(--ab-green);"><i class="fas fa-check"></i> Active</span>
                </div>
                <div class="ab-feature" style="border-color:rgba(251,191,36,0.15);">
                    <div class="ab-feature-icon" style="background:rgba(251,191,36,0.1);color:var(--ab-amber);"><i class="fas fa-hand-holding-usd"></i></div>
                    <h3>Support the Ecosystem</h3>
                    <p>80% of mining rewards go to you. 20% sustains the platform — keeping AI, servers, search, and sovereign infrastructure free for everyone. No ads. No subscriptions. Just mining.</p>
                    <span class="ab-badge" style="background:rgba(91,156,245,0.1);color:var(--ab-blue);"><i class="fas fa-heart"></i> Community</span>
                </div>
            </div>
            <div style="margin-top:32px;padding:24px 32px;background:rgba(251,191,36,0.04);border:1px solid rgba(251,191,36,0.12);border-radius:16px;max-width:700px;margin-left:auto;margin-right:auto;">
                <div style="display:flex;align-items:center;gap:12px;justify-content:center;flex-wrap:wrap;">
                    <div style="text-align:center;min-width:120px;"><div style="font-size:28px;font-weight:900;color:var(--ab-amber);">250M</div><div style="font-size:11px;color:var(--ab-muted);text-transform:uppercase;letter-spacing:1px;">GSM Pool</div></div>
                    <div style="width:1px;height:40px;background:rgba(255,255,255,0.1);"></div>
                    <div style="text-align:center;min-width:120px;"><div style="font-size:28px;font-weight:900;color:var(--ab-green);">80%</div><div style="font-size:11px;color:var(--ab-muted);text-transform:uppercase;letter-spacing:1px;">Your Share</div></div>
                    <div style="width:1px;height:40px;background:rgba(255,255,255,0.1);"></div>
                    <div style="text-align:center;min-width:120px;"><div style="font-size:28px;font-weight:900;color:var(--ab-cyan);">$0</div><div style="font-size:11px;color:var(--ab-muted);text-transform:uppercase;letter-spacing:1px;">Cost to You</div></div>
                </div>
            </div>
        </div>

        <!-- ═══ FULL ECOSYSTEM ═══ -->
        <div class="ab-section" style="text-align:center;">
            <h2 class="ab-section-title" style="text-align:center;">Not Just a Browser —<br>An Entire Ecosystem</h2>
            <p class="ab-section-sub" style="text-align:center;margin:0 auto 48px;">Everything you need — AI, games, social, VR, development tools, voice, and more — accessible from one app. No other browser comes close.</p>
            <div class="ab-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
                <div class="ab-feature" style="padding:24px;">
                    <div class="ab-feature-icon" style="background:rgba(124,92,252,0.1);color:var(--ab-indigo);font-size:20px;"><i class="fas fa-brain"></i></div>
                    <h3 style="font-size:15px;">13,000+ AI Tools</h3>
                    <p style="font-size:13px;">Alfred AI built-in: domain management, code generation, image creation, voice commands, WhatsApp, Telegram, Discord — all by conversation.</p>
                </div>
                <div class="ab-feature" style="padding:24px;">
                    <div class="ab-feature-icon" style="background:rgba(239,68,68,0.1);color:var(--ab-red);font-size:20px;"><i class="fas fa-gamepad"></i></div>
                    <h3 style="font-size:15px;">Games Built-In</h3>
                    <p style="font-size:13px;">Chess Masters VR, AI Chess, 3D Pool, Backgammon, Speed Dating — with spectator mode, AI opponents, multiplayer, and GSM token wagers.</p>
                </div>
                <div class="ab-feature" style="padding:24px;">
                    <div class="ab-feature-icon" style="background:rgba(34,211,238,0.1);color:var(--ab-cyan);font-size:20px;"><i class="fas fa-heart"></i></div>
                    <h3 style="font-size:15px;">Pulse Social Hub</h3>
                    <p style="font-size:13px;">Your sovereign social network. Posts, feeds, groups, messaging — encrypted end-to-end. No algorithms. No data harvesting. Just people.</p>
                </div>
                <div class="ab-feature" style="padding:24px;">
                    <div class="ab-feature-icon" style="background:rgba(52,211,153,0.1);color:var(--ab-green);font-size:20px;"><i class="fas fa-vr-cardboard"></i></div>
                    <h3 style="font-size:15px;">14 VR Worlds</h3>
                    <p style="font-size:13px;">Metaverse access from your browser. Virtual boardrooms, game arenas, art galleries, meditation spaces — all rendered in WebXR.</p>
                </div>
                <div class="ab-feature" style="padding:24px;">
                    <div class="ab-feature-icon" style="background:rgba(91,156,245,0.1);color:var(--ab-blue);font-size:20px;"><i class="fas fa-code"></i></div>
                    <h3 style="font-size:15px;">GoCodeMe IDE</h3>
                    <p style="font-size:13px;">Full cloud IDE with Monaco editor, AI pair programming, 16 intelligence engines, project management — code anything from your browser.</p>
                </div>
                <div class="ab-feature" style="padding:24px;">
                    <div class="ab-feature-icon" style="background:rgba(251,191,36,0.1);color:var(--ab-amber);font-size:20px;"><i class="fas fa-phone"></i></div>
                    <h3 style="font-size:15px;">Voice Portal</h3>
                    <p style="font-size:13px;">Call 1-833-GOSITEME to talk to Alfred by phone. Voice cloning, IVR builder, conference rooms — enterprise telephony in your browser.</p>
                </div>
                <div class="ab-feature" style="padding:24px;">
                    <div class="ab-feature-icon" style="background:rgba(168,85,247,0.1);color:#a855f7;font-size:20px;"><i class="fas fa-store"></i></div>
                    <h3 style="font-size:15px;">Marketplace</h3>
                    <p style="font-size:13px;">Discover and publish AI agents, tools, templates, and extensions. Build once, earn forever. The app store for AI-powered everything.</p>
                </div>
                <div class="ab-feature" style="padding:24px;">
                    <div class="ab-feature-icon" style="background:rgba(255,255,255,0.06);color:#fff;font-size:20px;"><i class="fas fa-wallet"></i></div>
                    <h3 style="font-size:15px;">Crypto Wallet</h3>
                    <p style="font-size:13px;">Built-in Solana wallet for GSM tokens. Send, receive, trade, stake — all without leaving your browser. DeFi integration coming soon.</p>
                </div>
            </div>
        </div>

        <!-- ═══ WHY SWITCH ═══ -->
        <div class="ab-section" style="text-align:center;">
            <h2 class="ab-section-title" style="text-align:center;">Why People Are Switching</h2>
            <p class="ab-section-sub" style="text-align:center;margin:0 auto 48px;">Real reasons from real users. No hype.</p>
            <div class="ab-grid" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px;">
                <div style="background:var(--ab-card);border:1px solid var(--ab-border);border-radius:var(--ab-radius);padding:28px;text-align:left;">
                    <div style="font-size:24px;margin-bottom:12px;">💰</div>
                    <p style="font-size:14px;color:var(--ab-text);line-height:1.7;margin:0;">"I switched from Chrome and now my browser <strong>pays me</strong> while I work. Last month I earned enough GSM to cover my domain renewal. My browser literally pays for my business."</p>
                </div>
                <div style="background:var(--ab-card);border:1px solid var(--ab-border);border-radius:var(--ab-radius);padding:28px;text-align:left;">
                    <div style="font-size:24px;margin-bottom:12px;">🎮</div>
                    <p style="font-size:14px;color:var(--ab-text);line-height:1.7;margin:0;">"I open one app and I can browse the web, play chess in VR, manage my domains, code my startup, and earn crypto. Nothing else even attempts this."</p>
                </div>
                <div style="background:var(--ab-card);border:1px solid var(--ab-border);border-radius:var(--ab-radius);padding:28px;text-align:left;">
                    <div style="font-size:24px;margin-bottom:12px;">🔒</div>
                    <p style="font-size:14px;color:var(--ab-text);line-height:1.7;margin:0;">"Post-quantum encryption means even quantum computers can't break my sessions. Veil Protocol makes Signal look basic. And zero telemetry — verified."</p>
                </div>
            </div>
        </div>

        <!-- Comparison -->
        <div class="ab-section">
            <h2 class="ab-section-title">How We Compare</h2>
            <p class="ab-section-sub">Feature comparison against other privacy-focused browsers.</p>
            <div class="ab-compare">
                <table>
                    <thead>
                        <tr>
                            <th>Feature</th>
                            <th>Alfred Browser</th>
                            <th>Brave</th>
                            <th>Firefox</th>
                            <th>Tor Browser</th>
                            <th>Chrome</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="ab-row">
                            <td>Post-Quantum Encryption</td>
                            <td class="yes"><i class="fas fa-check-circle"></i> Kyber-1024</td>
                            <td class="no"><i class="fas fa-times"></i></td>
                            <td class="no"><i class="fas fa-times"></i></td>
                            <td class="no"><i class="fas fa-times"></i></td>
                            <td class="partial"><i class="fas fa-minus"></i> Partial</td>
                        </tr>
                        <tr class="ab-row">
                            <td>Zero Telemetry</td>
                            <td class="yes"><i class="fas fa-check-circle"></i></td>
                            <td class="partial"><i class="fas fa-minus"></i> Some</td>
                            <td class="partial"><i class="fas fa-minus"></i> Some</td>
                            <td class="yes"><i class="fas fa-check-circle"></i></td>
                            <td class="no"><i class="fas fa-times"></i> Heavy</td>
                        </tr>
                        <tr class="ab-row">
                            <td>Built-in AI (Private)</td>
                            <td class="yes"><i class="fas fa-check-circle"></i> Local</td>
                            <td class="partial"><i class="fas fa-minus"></i> Cloud</td>
                            <td class="no"><i class="fas fa-times"></i></td>
                            <td class="no"><i class="fas fa-times"></i></td>
                            <td class="partial"><i class="fas fa-minus"></i> Cloud</td>
                        </tr>
                        <tr class="ab-row">
                            <td>Emergency Mesh</td>
                            <td class="yes"><i class="fas fa-check-circle"></i></td>
                            <td class="no"><i class="fas fa-times"></i></td>
                            <td class="no"><i class="fas fa-times"></i></td>
                            <td class="no"><i class="fas fa-times"></i></td>
                            <td class="no"><i class="fas fa-times"></i></td>
                        </tr>
                        <tr class="ab-row">
                            <td>Self-Hosted Search</td>
                            <td class="yes"><i class="fas fa-check-circle"></i></td>
                            <td class="no"><i class="fas fa-times"></i></td>
                            <td class="no"><i class="fas fa-times"></i></td>
                            <td class="no"><i class="fas fa-times"></i></td>
                            <td class="no"><i class="fas fa-times"></i></td>
                        </tr>
                        <tr class="ab-row">
                            <td>Anti-Fingerprinting</td>
                            <td class="yes"><i class="fas fa-check-circle"></i></td>
                            <td class="yes"><i class="fas fa-check-circle"></i></td>
                            <td class="partial"><i class="fas fa-minus"></i></td>
                            <td class="yes"><i class="fas fa-check-circle"></i></td>
                            <td class="no"><i class="fas fa-times"></i></td>
                        </tr>
                        <tr class="ab-row">
                            <td>Offline Survival Kit</td>
                            <td class="yes"><i class="fas fa-check-circle"></i></td>
                            <td class="no"><i class="fas fa-times"></i></td>
                            <td class="no"><i class="fas fa-times"></i></td>
                            <td class="no"><i class="fas fa-times"></i></td>
                            <td class="no"><i class="fas fa-times"></i></td>
                        </tr>
                        <tr class="ab-row">
                            <td>Built-in Crypto Mining</td>
                            <td class="yes"><i class="fas fa-check-circle"></i> GSM Token</td>
                            <td class="partial"><i class="fas fa-minus"></i> BAT (Ads)</td>
                            <td class="no"><i class="fas fa-times"></i></td>
                            <td class="no"><i class="fas fa-times"></i></td>
                            <td class="no"><i class="fas fa-times"></i></td>
                        </tr>
                        <tr class="ab-row">
                            <td>Games &amp; VR Worlds</td>
                            <td class="yes"><i class="fas fa-check-circle"></i> 5+ Games</td>
                            <td class="no"><i class="fas fa-times"></i></td>
                            <td class="no"><i class="fas fa-times"></i></td>
                            <td class="no"><i class="fas fa-times"></i></td>
                            <td class="no"><i class="fas fa-times"></i></td>
                        </tr>
                        <tr class="ab-row">
                            <td>Full App Ecosystem</td>
                            <td class="yes"><i class="fas fa-check-circle"></i> 13,000+ Tools</td>
                            <td class="no"><i class="fas fa-times"></i></td>
                            <td class="partial"><i class="fas fa-minus"></i> Extensions</td>
                            <td class="no"><i class="fas fa-times"></i></td>
                            <td class="partial"><i class="fas fa-minus"></i> Extensions</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Architecture -->
        <div class="ab-section">
            <h2 class="ab-section-title">Security Architecture</h2>
            <p class="ab-section-sub">Multiple layers of defense — because one lock isn't enough.</p>
            <div class="ab-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px;">
                <div class="ab-feature" style="padding:24px;">
                    <h3 style="font-size:15px;"><span style="color:var(--ab-green);margin-right:8px;">01</span>Network Layer</h3>
                    <p>DNS-over-HTTPS, ECH (Encrypted Client Hello), certificate pinning, HSTS preload. Connection-level privacy before a single byte of content loads.</p>
                </div>
                <div class="ab-feature" style="padding:24px;">
                    <h3 style="font-size:15px;"><span style="color:var(--ab-blue);margin-right:8px;">02</span>Encryption Layer</h3>
                    <p>AES-256-GCM for symmetric encryption, Kyber-1024 for key exchange, ECDSA for authentication. Post-quantum safe against future threats.</p>
                </div>
                <div class="ab-feature" style="padding:24px;">
                    <h3 style="font-size:15px;"><span style="color:var(--ab-indigo);margin-right:8px;">03</span>Isolation Layer</h3>
                    <p>Process-per-tab sandboxing, contextIsolation, disabled nodeIntegration. No tab can access another. No page can touch your system.</p>
                </div>
                <div class="ab-feature" style="padding:24px;">
                    <h3 style="font-size:15px;"><span style="color:var(--ab-amber);margin-right:8px;">04</span>Privacy Layer</h3>
                    <p>Canvas noise, WebGL randomization, font blocking, timing normalization, referrer stripping, cookie isolation, tracker obliteration.</p>
                </div>
                <div class="ab-feature" style="padding:24px;">
                    <h3 style="font-size:15px;"><span style="color:var(--ab-cyan);margin-right:8px;">05</span>AI Layer</h3>
                    <p>Local Ollama inference, no cloud dependency. Alfred processes content client-side — summaries, translations, analysis — zero data leakage.</p>
                </div>
                <div class="ab-feature" style="padding:24px;">
                    <h3 style="font-size:15px;"><span style="color:var(--ab-red);margin-right:8px;">06</span>Emergency Layer</h3>
                    <p>Mesh networking, offline cache, cached search index, survival knowledge base. Degrades gracefully from full internet to total blackout.</p>
                </div>
            </div>
        </div>

        <!-- CTA -->
        <div class="ab-cta-section">
            <h2 class="ab-section-title" style="margin-bottom:16px;">Your Browser Should Work For You</h2>
            <p style="color:var(--ab-muted);max-width:580px;margin:0 auto 12px;font-size:16px;line-height:1.7;">
                Every other browser was built to serve corporations.<br>
                Alfred Browser was built to serve <strong style="color:#fff;">you</strong>.
            </p>
            <p style="color:var(--ab-amber);max-width:500px;margin:0 auto 32px;font-size:15px;font-weight:600;">
                🪙 Browse → Mine → Earn → Repeat. Download now. Start earning today.
            </p>
            <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;position:relative;z-index:1;">
                <a href="#" class="ab-cta-btn" onclick="scrollTo({top:0,behavior:'smooth'});return false;"><i class="fas fa-download"></i> Download &amp; Start Mining</a>
                <a href="/search" style="display:inline-flex;align-items:center;gap:8px;padding:16px 36px;border-radius:14px;font-size:16px;font-weight:700;background:rgba(255,255,255,0.06);color:var(--ab-text);border:1px solid rgba(255,255,255,0.1);transition:all 0.2s;text-decoration:none !important;"><i class="fas fa-search"></i> Try Alfred Search</a>
            </div>
            <div style="margin-top:24px;display:flex;gap:16px;justify-content:center;flex-wrap:wrap;">
                <a href="/games" style="font-size:13px;color:var(--ab-muted);"><i class="fas fa-gamepad" style="margin-right:4px;"></i> Games</a>
                <a href="/pulse" style="font-size:13px;color:var(--ab-muted);"><i class="fas fa-heart" style="margin-right:4px;"></i> Pulse</a>
                <a href="/marketplace" style="font-size:13px;color:var(--ab-muted);"><i class="fas fa-store" style="margin-right:4px;"></i> Marketplace</a>
                <a href="/editor/" style="font-size:13px;color:var(--ab-muted);"><i class="fas fa-code" style="margin-right:4px;"></i> GoCodeMe</a>
                <a href="/voice" style="font-size:13px;color:var(--ab-muted);"><i class="fas fa-phone" style="margin-right:4px;"></i> Voice Portal</a>
            </div>
            <div style="margin-top:16px;display:flex;gap:24px;justify-content:center;flex-wrap:wrap;">
                <a href="/security" style="font-size:13px;color:var(--ab-muted);"><i class="fas fa-lock" style="margin-right:4px;"></i> Security Audit</a>
                <a href="/post-quantum" style="font-size:13px;color:var(--ab-muted);"><i class="fas fa-atom" style="margin-right:4px;"></i> Post-Quantum Docs</a>
                <a href="/emergency-kit" style="font-size:13px;color:var(--ab-muted);"><i class="fas fa-first-aid" style="margin-right:4px;"></i> Emergency Kit</a>
                <a href="/veil/" style="font-size:13px;color:var(--ab-muted);"><i class="fas fa-shield-alt" style="margin-right:4px;"></i> Veil Protocol</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/site-footer.inc.php'; ?>
