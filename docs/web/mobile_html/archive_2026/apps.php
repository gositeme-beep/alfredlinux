<?php
/**
 * GoSiteMe Apps — Download all desktop & mobile applications
 * Alfred Browser, Veil Messenger, Pulse Social
 */
$page_title = 'Download Our Apps — Alfred Browser, Veil Messenger, Pulse Social | GoSiteMe';
$page_description = 'Download GoSiteMe apps for Windows, macOS, Linux, Ubuntu, and Android. Alfred Browser for sovereign browsing, Veil Messenger for encrypted communications, and Pulse Social for privacy-first social networking.';
$page_canonical = 'https://gositeme.com/apps';
require_once __DIR__ . '/includes/site-header.inc.php';
?>
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<link rel="stylesheet" href="/assets/css/fonts.css">

<style>
:root {
    --apps-void: #030308;
    --apps-surface: #0a0a14;
    --apps-card: rgba(255,255,255,0.025);
    --apps-border: rgba(255,255,255,0.06);
    --apps-text: rgba(255,255,255,0.88);
    --apps-muted: rgba(255,255,255,0.5);
    --apps-radius: 16px;
    /* Alfred Browser */
    --ab-blue: #3B82F6;
    --ab-navy: #1E3A5F;
    --ab-gradient: linear-gradient(135deg, #5b9cf5 0%, #7c5cfc 50%, #a855f7 100%);
    /* Veil Messenger */
    --vm-purple: #8B5CF6;
    --vm-violet: #A78BFA;
    --vm-gradient: linear-gradient(135deg, #8B5CF6 0%, #A78BFA 50%, #C084FC 100%);
    /* Pulse Social */
    --ps-teal: #0D9488;
    --ps-emerald: #10B981;
    --ps-gradient: linear-gradient(135deg, #0D9488 0%, #10B981 50%, #34D399 100%);
}

.apps-page {
    background: var(--apps-void);
    min-height: 100vh;
    font-family: 'Inter', 'DM Sans', -apple-system, sans-serif;
    color: var(--apps-text);
}

/* Hero */
.apps-hero {
    text-align: center;
    padding: 80px 24px 60px;
    position: relative;
    overflow: hidden;
}
.apps-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    left: 50%;
    transform: translateX(-50%);
    width: 800px;
    height: 800px;
    background: radial-gradient(circle, rgba(91,156,245,0.06) 0%, rgba(124,92,252,0.03) 40%, transparent 70%);
    pointer-events: none;
}
.apps-hero h1 {
    font-size: clamp(2.2rem, 5vw, 3.6rem);
    font-weight: 900;
    letter-spacing: -2px;
    line-height: 1.1;
    margin-bottom: 16px;
    background: linear-gradient(135deg, #fff 0%, rgba(255,255,255,0.7) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    position: relative;
}
.apps-hero p {
    font-size: 17px;
    color: var(--apps-muted);
    max-width: 640px;
    margin: 0 auto;
    line-height: 1.7;
}
.apps-hero .badge-row {
    display: flex;
    gap: 10px;
    justify-content: center;
    flex-wrap: wrap;
    margin-bottom: 28px;
}
.apps-hero .badge {
    padding: 6px 14px;
    border-radius: 100px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

/* App Section */
.apps-container {
    max-width: 1100px;
    margin: 0 auto;
    padding: 0 24px 80px;
}

.app-section {
    margin-bottom: 80px;
    position: relative;
}

.app-header {
    display: flex;
    align-items: center;
    gap: 24px;
    margin-bottom: 32px;
    flex-wrap: wrap;
}
.app-icon {
    width: 96px;
    height: 96px;
    border-radius: 24px;
    object-fit: cover;
    flex-shrink: 0;
    box-shadow: 0 8px 32px rgba(0,0,0,0.4);
}
.app-meta h2 {
    font-size: 2rem;
    font-weight: 800;
    letter-spacing: -1px;
    margin: 0 0 6px;
}
.app-meta .app-tagline {
    font-size: 15px;
    color: var(--apps-muted);
    margin: 0 0 10px;
    line-height: 1.6;
    max-width: 480px;
}
.app-meta .app-version {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    padding: 4px 12px;
    border-radius: 100px;
    display: inline-block;
}

/* Feature pills */
.app-features {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 28px;
}
.app-feature-pill {
    padding: 8px 16px;
    border-radius: 100px;
    font-size: 12px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.06);
    color: var(--apps-text);
}

/* Download Grid */
.dl-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 12px;
}
.dl-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px 20px;
    border-radius: 14px;
    background: var(--apps-card);
    border: 1px solid var(--apps-border);
    text-decoration: none !important;
    color: var(--apps-text) !important;
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
}
.dl-card:hover {
    transform: translateY(-2px);
    border-color: rgba(255,255,255,0.12);
    box-shadow: 0 8px 32px rgba(0,0,0,0.3);
}
.dl-card .dl-icon {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}
.dl-card .dl-info h4 {
    font-size: 14px;
    font-weight: 700;
    margin: 0 0 2px;
}
.dl-card .dl-info span {
    font-size: 11px;
    color: var(--apps-muted);
}
.dl-card .dl-arrow {
    margin-left: auto;
    font-size: 14px;
    opacity: 0.4;
    transition: opacity 0.2s;
}
.dl-card:hover .dl-arrow {
    opacity: 1;
}

/* Color themes per app */
.app-alfred .dl-card:hover { border-color: rgba(59,130,246,0.3); box-shadow: 0 8px 32px rgba(59,130,246,0.1); }
.app-veil .dl-card:hover { border-color: rgba(139,92,246,0.3); box-shadow: 0 8px 32px rgba(139,92,246,0.1); }
.app-pulse .dl-card:hover { border-color: rgba(13,148,136,0.3); box-shadow: 0 8px 32px rgba(13,148,136,0.1); }

/* Divider */
.app-divider {
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.06), transparent);
    margin: 60px 0;
}

/* Platform summary strip */
.platform-strip {
    display: flex;
    gap: 20px;
    justify-content: center;
    flex-wrap: wrap;
    margin: 48px 0 60px;
    padding: 20px;
}
.platform-item {
    text-align: center;
    min-width: 80px;
}
.platform-item i {
    font-size: 28px;
    display: block;
    margin-bottom: 6px;
    opacity: 0.5;
}
.platform-item span {
    font-size: 11px;
    color: var(--apps-muted);
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 600;
}

/* iOS Instructions */
.ios-instructions {
    background: var(--apps-card);
    border: 1px solid var(--apps-border);
    border-radius: var(--apps-radius);
    padding: 32px;
    margin-top: 40px;
}
.ios-instructions h3 {
    font-size: 18px;
    font-weight: 700;
    margin: 0 0 8px;
}
.ios-instructions p {
    font-size: 14px;
    color: var(--apps-muted);
    margin: 0 0 20px;
    line-height: 1.6;
}
.ios-steps {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
}
.ios-step {
    background: rgba(255,255,255,0.02);
    border: 1px solid rgba(255,255,255,0.04);
    border-radius: 12px;
    padding: 20px;
    text-align: center;
}
.ios-step .step-num {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 16px;
    margin-bottom: 10px;
}
.ios-step h4 { font-size: 14px; font-weight: 700; margin: 0 0 4px; }
.ios-step p { font-size: 12px; color: var(--apps-muted); margin: 0; line-height: 1.5; }

/* Responsive */
@media (max-width: 768px) {
    .app-header { flex-direction: column; text-align: center; align-items: center; }
    .app-meta .app-tagline { max-width: none; }
    .app-features { justify-content: center; }
    .dl-grid { grid-template-columns: 1fr; }
    .apps-hero h1 { letter-spacing: -1px; }
}
</style>

<div class="apps-page">
    <!-- Hero -->
    <div class="apps-hero">
        <div class="badge-row">
            <span class="badge" style="background:rgba(59,130,246,0.1);color:var(--ab-blue);border:1px solid rgba(59,130,246,0.15);"><i class="fas fa-shield-alt"></i> Sovereign</span>
            <span class="badge" style="background:rgba(139,92,246,0.1);color:var(--vm-purple);border:1px solid rgba(139,92,246,0.15);"><i class="fas fa-lock"></i> E2E Encrypted</span>
            <span class="badge" style="background:rgba(13,148,136,0.1);color:var(--ps-teal);border:1px solid rgba(13,148,136,0.15);"><i class="fas fa-users"></i> Privacy-First</span>
        </div>
        <h1>GoSiteMe Apps</h1>
        <p>Three apps. Every platform. Zero tracking. Download Alfred Browser to browse sovereign, Veil Messenger to communicate privately, and Pulse Social to connect without surveillance.</p>
        <div class="platform-strip">
            <div class="platform-item"><i class="fab fa-windows"></i><span>Windows</span></div>
            <div class="platform-item"><i class="fab fa-apple"></i><span>macOS</span></div>
            <div class="platform-item"><i class="fab fa-ubuntu"></i><span>Ubuntu</span></div>
            <div class="platform-item"><i class="fab fa-linux"></i><span>Linux</span></div>
            <div class="platform-item"><i class="fab fa-android"></i><span>Android</span></div>
            <div class="platform-item"><i class="fab fa-apple"></i><span>iOS / iPad</span></div>
        </div>
    </div>

    <div class="apps-container">

        <!-- ═══════════════════════════════════════════════════ -->
        <!-- ALFRED BROWSER                                      -->
        <!-- ═══════════════════════════════════════════════════ -->
        <div class="app-section app-alfred" id="alfred-browser">
            <div class="app-header">
                <img src="/brand/icons/alfred-browser/playstore/ic_launcher.png" alt="Alfred Browser" class="app-icon" loading="lazy" loading="lazy" style="box-shadow: 0 8px 32px rgba(59,130,246,0.2);">
                <div class="app-meta">
                    <h2 style="background:var(--ab-gradient);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">Alfred Browser</h2>
                    <p class="app-tagline">The sovereign browser that fights for you and pays you. Post-quantum encrypted, AI-powered, with built-in mining, games, VR worlds, and 13,000+ tools.</p>
                    <span class="app-version" style="background:rgba(59,130,246,0.1);color:var(--ab-blue);border:1px solid rgba(59,130,246,0.15);">v3.0.0</span>
                </div>
            </div>

            <div class="app-features">
                <span class="app-feature-pill" style="border-color:rgba(59,130,246,0.15);"><i class="fas fa-shield-alt" style="color:var(--ab-blue);"></i> Post-Quantum Crypto</span>
                <span class="app-feature-pill" style="border-color:rgba(124,92,252,0.15);"><i class="fas fa-brain" style="color:#7c5cfc;"></i> 13,000+ AI Tools</span>
                <span class="app-feature-pill" style="border-color:rgba(52,211,153,0.15);"><i class="fas fa-coins" style="color:#34d399;"></i> Mine GSM Tokens</span>
                <span class="app-feature-pill" style="border-color:rgba(251,191,36,0.15);"><i class="fas fa-gamepad" style="color:#fbbf24;"></i> Built-in Games & VR</span>
                <span class="app-feature-pill" style="border-color:rgba(34,211,238,0.15);"><i class="fas fa-wifi" style="color:#22d3ee;"></i> Emergency Mesh</span>
                <span class="app-feature-pill" style="border-color:rgba(239,68,68,0.15);"><i class="fas fa-eye-slash" style="color:#ef4444;"></i> Zero Telemetry</span>
            </div>

            <div class="dl-grid">
                <a href="/downloads/Alfred-Browser-3.0.0-win-x64.zip" class="dl-card">
                    <div class="dl-icon" style="background:rgba(59,130,246,0.1);color:var(--ab-blue);"><i class="fab fa-windows"></i></div>
                    <div class="dl-info">
                        <h4>Windows</h4>
                        <span>Portable x64 &middot; Auto-updates</span>
                    </div>
                    <i class="fas fa-arrow-down dl-arrow"></i>
                </a>
                <a href="/downloads/Alfred-Browser-3.0.0-mac-intel.zip" class="dl-card">
                    <div class="dl-icon" style="background:rgba(255,255,255,0.04);color:#fff;"><i class="fab fa-apple"></i></div>
                    <div class="dl-info">
                        <h4>macOS Intel</h4>
                        <span>x64 Zip &middot; macOS 11+</span>
                    </div>
                    <i class="fas fa-arrow-down dl-arrow"></i>
                </a>
                <a href="/downloads/Alfred-Browser-3.0.0-mac-arm64.zip" class="dl-card">
                    <div class="dl-icon" style="background:rgba(255,255,255,0.04);color:#fff;"><i class="fab fa-apple"></i></div>
                    <div class="dl-info">
                        <h4>macOS Apple Silicon</h4>
                        <span>ARM64 &middot; M1/M2/M3/M4</span>
                    </div>
                    <i class="fas fa-arrow-down dl-arrow"></i>
                </a>
                <a href="/downloads/Alfred-Browser-3.0.0.AppImage" class="dl-card">
                    <div class="dl-icon" style="background:rgba(251,191,36,0.1);color:#fbbf24;"><i class="fab fa-linux"></i></div>
                    <div class="dl-info">
                        <h4>Linux AppImage</h4>
                        <span>x64 &middot; Universal &middot; Auto-updates</span>
                    </div>
                    <i class="fas fa-arrow-down dl-arrow"></i>
                </a>
                <a href="/downloads/alfred-browser_3.0.0_amd64.deb" class="dl-card">
                    <div class="dl-icon" style="background:rgba(221,72,20,0.1);color:#dd4814;"><i class="fab fa-ubuntu"></i></div>
                    <div class="dl-info">
                        <h4>Ubuntu / Debian</h4>
                        <span>.deb x64 &middot; apt compatible</span>
                    </div>
                    <i class="fas fa-arrow-down dl-arrow"></i>
                </a>
                <a href="/downloads/Alfred-Browser.apk" class="dl-card">
                    <div class="dl-icon" style="background:rgba(52,211,153,0.1);color:#34d399;"><i class="fab fa-android"></i></div>
                    <div class="dl-info">
                        <h4>Android</h4>
                        <span>APK &middot; Android 8.0+</span>
                    </div>
                    <i class="fas fa-arrow-down dl-arrow"></i>
                </a>
                <a href="#ios-install" class="dl-card">
                    <div class="dl-icon" style="background:rgba(255,255,255,0.04);color:#fff;"><i class="fab fa-apple"></i></div>
                    <div class="dl-info">
                        <h4>iOS / iPad</h4>
                        <span>PWA &middot; Add to Home Screen</span>
                    </div>
                    <i class="fas fa-plus-circle dl-arrow" style="color:#22d3ee;"></i>
                </a>
                <a href="/downloads/alfred-chrome-extension/" class="dl-card">
                    <div class="dl-icon" style="background:rgba(124,92,252,0.1);color:#7c5cfc;"><i class="fab fa-chrome"></i></div>
                    <div class="dl-info">
                        <h4>Chrome Extension</h4>
                        <span>Add sovereignty to Chrome</span>
                    </div>
                    <i class="fas fa-arrow-down dl-arrow"></i>
                </a>
            </div>

            <div style="margin-top:16px;text-align:center;">
                <a href="/alfred-browser" style="font-size:13px;color:var(--ab-blue);font-weight:600;"><i class="fas fa-external-link-alt" style="margin-right:4px;"></i> Full feature showcase & comparison</a>
            </div>
        </div>

        <div class="app-divider"></div>

        <!-- ═══════════════════════════════════════════════════ -->
        <!-- VEIL MESSENGER                                      -->
        <!-- ═══════════════════════════════════════════════════ -->
        <div class="app-section app-veil" id="veil-messenger">
            <div class="app-header">
                <img src="/brand/icons/veil-messenger/playstore/ic_launcher.png" alt="Veil Messenger" class="app-icon" loading="lazy" loading="lazy" style="box-shadow: 0 8px 32px rgba(139,92,246,0.2);">
                <div class="app-meta">
                    <h2 style="background:var(--vm-gradient);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">Veil Messenger</h2>
                    <p class="app-tagline">End-to-end encrypted communications. Private text, voice, video calls, and file sharing with zero-knowledge architecture. No metadata. No logs. No compromise.</p>
                    <span class="app-version" style="background:rgba(139,92,246,0.1);color:var(--vm-purple);border:1px solid rgba(139,92,246,0.15);">v1.0.0</span>
                </div>
            </div>

            <div class="app-features">
                <span class="app-feature-pill" style="border-color:rgba(139,92,246,0.15);"><i class="fas fa-lock" style="color:var(--vm-purple);"></i> AES-256-GCM</span>
                <span class="app-feature-pill" style="border-color:rgba(167,139,250,0.15);"><i class="fas fa-key" style="color:var(--vm-violet);"></i> X25519 Key Exchange</span>
                <span class="app-feature-pill" style="border-color:rgba(192,132,252,0.15);"><i class="fas fa-comments" style="color:#C084FC;"></i> Text, Voice & Video</span>
                <span class="app-feature-pill" style="border-color:rgba(139,92,246,0.15);"><i class="fas fa-users" style="color:var(--vm-purple);"></i> Conference Rooms</span>
                <span class="app-feature-pill" style="border-color:rgba(167,139,250,0.15);"><i class="fas fa-file-alt" style="color:var(--vm-violet);"></i> Encrypted File Sharing</span>
                <span class="app-feature-pill" style="border-color:rgba(192,132,252,0.15);"><i class="fas fa-eye-slash" style="color:#C084FC;"></i> Zero-Knowledge</span>
            </div>

            <div class="dl-grid" style="position:relative;">
                <div style="position:absolute;inset:0;background:rgba(0,0,0,0.6);backdrop-filter:blur(4px);border-radius:16px;display:flex;align-items:center;justify-content:center;z-index:2;flex-direction:column;gap:8px;">
                    <i class="fas fa-clock" style="font-size:2rem;color:var(--vm-purple);"></i>
                    <span style="font-size:1.1rem;font-weight:700;color:#fff;">Coming Soon</span>
                    <span style="font-size:0.8rem;color:rgba(255,255,255,0.6);">Desktop & mobile apps in development</span>
                </div>
                <div class="dl-card" style="pointer-events:none;opacity:0.3;">
                    <div class="dl-icon" style="background:rgba(139,92,246,0.1);color:var(--vm-purple);"><i class="fab fa-windows"></i></div>
                    <div class="dl-info">
                        <h4>Windows</h4>
                        <span>x64 &middot; Windows 10+</span>
                    </div>
                </div>
                <div class="dl-card" style="pointer-events:none;opacity:0.3;">
                    <div class="dl-icon" style="background:rgba(255,255,255,0.04);color:#fff;"><i class="fab fa-apple"></i></div>
                    <div class="dl-info">
                        <h4>macOS</h4>
                        <span>Intel &amp; Apple Silicon</span>
                    </div>
                </div>
                <div class="dl-card" style="pointer-events:none;opacity:0.3;">
                    <div class="dl-icon" style="background:rgba(251,191,36,0.1);color:#fbbf24;"><i class="fab fa-linux"></i></div>
                    <div class="dl-info">
                        <h4>Linux</h4>
                        <span>AppImage &amp; .deb</span>
                    </div>
                </div>
                <div class="dl-card" style="pointer-events:none;opacity:0.3;">
                    <div class="dl-icon" style="background:rgba(52,211,153,0.1);color:#34d399;"><i class="fab fa-android"></i></div>
                    <div class="dl-info">
                        <h4>Android</h4>
                        <span>APK &middot; Android 8.0+</span>
                    </div>
                </div>
                <a href="#ios-install" class="dl-card">
                    <div class="dl-icon" style="background:rgba(255,255,255,0.04);color:#fff;"><i class="fab fa-apple"></i></div>
                    <div class="dl-info">
                        <h4>iOS / iPad</h4>
                        <span>PWA &middot; Add to Home Screen</span>
                    </div>
                    <i class="fas fa-plus-circle dl-arrow" style="color:var(--vm-violet);"></i>
                </a>
            </div>

            <div style="margin-top:16px;text-align:center;">
                <a href="/veil/" style="font-size:13px;color:var(--vm-purple);font-weight:600;"><i class="fas fa-external-link-alt" style="margin-right:4px;"></i> Open Veil Messenger in browser</a>
            </div>
        </div>

        <div class="app-divider"></div>

        <!-- ═══════════════════════════════════════════════════ -->
        <!-- PULSE SOCIAL                                        -->
        <!-- ═══════════════════════════════════════════════════ -->
        <div class="app-section app-pulse" id="pulse-social">
            <div class="app-header">
                <img src="/brand/icons/pulse-social/playstore/ic_launcher.png" alt="Pulse Social" class="app-icon" loading="lazy" loading="lazy" style="box-shadow: 0 8px 32px rgba(13,148,136,0.2);">
                <div class="app-meta">
                    <h2 style="background:var(--ps-gradient);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">Pulse Social</h2>
                    <p class="app-tagline">The sovereign social network. Share posts, discover content, connect with people — without algorithms, data harvesting, or surveillance. Your feed. Your rules.</p>
                    <span class="app-version" style="background:rgba(13,148,136,0.1);color:var(--ps-teal);border:1px solid rgba(13,148,136,0.15);">v1.0.0</span>
                </div>
            </div>

            <div class="app-features">
                <span class="app-feature-pill" style="border-color:rgba(13,148,136,0.15);"><i class="fas fa-rss" style="color:var(--ps-teal);"></i> Chronological Feed</span>
                <span class="app-feature-pill" style="border-color:rgba(16,185,129,0.15);"><i class="fas fa-eye-slash" style="color:var(--ps-emerald);"></i> No Algorithms</span>
                <span class="app-feature-pill" style="border-color:rgba(52,211,153,0.15);"><i class="fas fa-search" style="color:#34D399;"></i> Discover & Trending</span>
                <span class="app-feature-pill" style="border-color:rgba(13,148,136,0.15);"><i class="fas fa-share-alt" style="color:var(--ps-teal);"></i> Native Sharing</span>
                <span class="app-feature-pill" style="border-color:rgba(16,185,129,0.15);"><i class="fas fa-bell" style="color:var(--ps-emerald);"></i> Push Notifications</span>
                <span class="app-feature-pill" style="border-color:rgba(52,211,153,0.15);"><i class="fas fa-user-shield" style="color:#34D399;"></i> Privacy-First</span>
            </div>

            <div class="dl-grid" style="position:relative;">
                <div style="position:absolute;inset:0;background:rgba(0,0,0,0.6);backdrop-filter:blur(4px);border-radius:16px;display:flex;align-items:center;justify-content:center;z-index:2;flex-direction:column;gap:8px;">
                    <i class="fas fa-clock" style="font-size:2rem;color:var(--ps-teal);"></i>
                    <span style="font-size:1.1rem;font-weight:700;color:#fff;">Coming Soon</span>
                    <span style="font-size:0.8rem;color:rgba(255,255,255,0.6);">Desktop & mobile apps in development</span>
                </div>
                <div class="dl-card" style="pointer-events:none;opacity:0.3;">
                    <div class="dl-icon" style="background:rgba(13,148,136,0.1);color:var(--ps-teal);"><i class="fab fa-windows"></i></div>
                    <div class="dl-info">
                        <h4>Windows</h4>
                        <span>x64 &middot; Windows 10+</span>
                    </div>
                </div>
                <div class="dl-card" style="pointer-events:none;opacity:0.3;">
                    <div class="dl-icon" style="background:rgba(255,255,255,0.04);color:#fff;"><i class="fab fa-apple"></i></div>
                    <div class="dl-info">
                        <h4>macOS</h4>
                        <span>Intel &amp; Apple Silicon</span>
                    </div>
                </div>
                <div class="dl-card" style="pointer-events:none;opacity:0.3;">
                    <div class="dl-icon" style="background:rgba(251,191,36,0.1);color:#fbbf24;"><i class="fab fa-linux"></i></div>
                    <div class="dl-info">
                        <h4>Linux</h4>
                        <span>AppImage &amp; .deb</span>
                    </div>
                </div>
                <div class="dl-card" style="pointer-events:none;opacity:0.3;">
                    <div class="dl-icon" style="background:rgba(52,211,153,0.1);color:#34d399;"><i class="fab fa-android"></i></div>
                    <div class="dl-info">
                        <h4>Android</h4>
                        <span>APK &middot; Android 8.0+</span>
                    </div>
                </div>
                <a href="#ios-install" class="dl-card">
                    <div class="dl-icon" style="background:rgba(255,255,255,0.04);color:#fff;"><i class="fab fa-apple"></i></div>
                    <div class="dl-info">
                        <h4>iOS / iPad</h4>
                        <span>PWA &middot; Add to Home Screen</span>
                    </div>
                    <i class="fas fa-plus-circle dl-arrow" style="color:var(--ps-emerald);"></i>
                </a>
            </div>

            <div style="margin-top:16px;text-align:center;">
                <a href="/pulse" style="font-size:13px;color:var(--ps-teal);font-weight:600;"><i class="fas fa-external-link-alt" style="margin-right:4px;"></i> Open Pulse Social in browser</a>
            </div>
        </div>

        <div class="app-divider"></div>

        <!-- ═══════════════════════════════════════════════════ -->
        <!-- iOS INSTALL INSTRUCTIONS                            -->
        <!-- ═══════════════════════════════════════════════════ -->
        <div class="ios-instructions" id="ios-install">
            <h3><i class="fab fa-apple" style="margin-right:8px;"></i> Install on iOS & iPad</h3>
            <p>All GoSiteMe apps are Progressive Web Apps — install them directly from Safari with full offline support, push notifications, and home screen access. No App Store required.</p>
            <div class="ios-steps">
                <div class="ios-step">
                    <div class="step-num" style="background:rgba(59,130,246,0.1);color:var(--ab-blue);">1</div>
                    <h4>Open in Safari</h4>
                    <p>Visit <strong>gositeme.com</strong> in Safari on your iPhone or iPad.</p>
                </div>
                <div class="ios-step">
                    <div class="step-num" style="background:rgba(139,92,246,0.1);color:var(--vm-purple);">2</div>
                    <h4>Tap Share</h4>
                    <p>Tap the <strong>Share</strong> button (box with arrow) at the bottom of Safari.</p>
                </div>
                <div class="ios-step">
                    <div class="step-num" style="background:rgba(13,148,136,0.1);color:var(--ps-teal);">3</div>
                    <h4>Add to Home Screen</h4>
                    <p>Scroll down and tap <strong>"Add to Home Screen"</strong>, then tap <strong>Add</strong>.</p>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════ -->
        <!-- COMPARISON STRIP                                    -->
        <!-- ═══════════════════════════════════════════════════ -->
        <div style="margin-top:60px;text-align:center;">
            <h2 style="font-size:1.6rem;font-weight:800;letter-spacing:-1px;margin-bottom:12px;">Three Apps, One Ecosystem</h2>
            <p style="color:var(--apps-muted);font-size:15px;max-width:520px;margin:0 auto 36px;line-height:1.7;">Each app is purpose-built for its role — but they all share the same sovereign DNA. No ads. No tracking. No compromise.</p>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px;">
                <div style="background:var(--apps-card);border:1px solid rgba(59,130,246,0.1);border-radius:var(--apps-radius);padding:28px;text-align:center;">
                    <img src="/brand/icons/alfred-browser/playstore/ic_launcher.png" alt="Alfred Browser" loading="lazy" style="width:56px;height:56px;border-radius:14px;margin-bottom:14px;">
                    <h3 style="font-size:16px;font-weight:700;margin:0 0 6px;">Alfred Browser</h3>
                    <p style="font-size:13px;color:var(--apps-muted);margin:0;line-height:1.6;">Browse, mine, code, play games, access VR — your entire digital life in one sovereign app.</p>
                    <a href="#alfred-browser" style="display:inline-block;margin-top:14px;font-size:12px;font-weight:700;color:var(--ab-blue);text-transform:uppercase;letter-spacing:1px;">Download <i class="fas fa-arrow-right" style="font-size:10px;"></i></a>
                </div>
                <div style="background:var(--apps-card);border:1px solid rgba(139,92,246,0.1);border-radius:var(--apps-radius);padding:28px;text-align:center;">
                    <img src="/brand/icons/veil-messenger/playstore/ic_launcher.png" alt="Veil Messenger" loading="lazy" style="width:56px;height:56px;border-radius:14px;margin-bottom:14px;">
                    <h3 style="font-size:16px;font-weight:700;margin:0 0 6px;">Veil Messenger</h3>
                    <p style="font-size:13px;color:var(--apps-muted);margin:0;line-height:1.6;">Private messaging, voice & video calls, conference rooms, encrypted file sharing. Zero metadata.</p>
                    <a href="#veil-messenger" style="display:inline-block;margin-top:14px;font-size:12px;font-weight:700;color:var(--vm-purple);text-transform:uppercase;letter-spacing:1px;">Download <i class="fas fa-arrow-right" style="font-size:10px;"></i></a>
                </div>
                <div style="background:var(--apps-card);border:1px solid rgba(13,148,136,0.1);border-radius:var(--apps-radius);padding:28px;text-align:center;">
                    <img src="/brand/icons/pulse-social/playstore/ic_launcher.png" alt="Pulse Social" loading="lazy" style="width:56px;height:56px;border-radius:14px;margin-bottom:14px;">
                    <h3 style="font-size:16px;font-weight:700;margin:0 0 6px;">Pulse Social</h3>
                    <p style="font-size:13px;color:var(--apps-muted);margin:0;line-height:1.6;">Social networking without surveillance. Chronological feed, trending content, zero algorithm manipulation.</p>
                    <a href="#pulse-social" style="display:inline-block;margin-top:14px;font-size:12px;font-weight:700;color:var(--ps-teal);text-transform:uppercase;letter-spacing:1px;">Download <i class="fas fa-arrow-right" style="font-size:10px;"></i></a>
                </div>
            </div>
        </div>

        <!-- CTA -->
        <div style="text-align:center;margin-top:60px;padding:48px 24px;background:rgba(255,255,255,0.015);border:1px solid var(--apps-border);border-radius:var(--apps-radius);">
            <h2 style="font-size:1.8rem;font-weight:800;letter-spacing:-1px;margin-bottom:12px;">Your Digital Life. Your Rules.</h2>
            <p style="color:var(--apps-muted);font-size:15px;max-width:480px;margin:0 auto 24px;line-height:1.7;">Every other platform was built to serve corporations. GoSiteMe apps were built to serve <strong style="color:#fff;">you</strong>.</p>
            <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
                <a href="#alfred-browser" style="display:inline-flex;align-items:center;gap:8px;padding:14px 28px;border-radius:12px;font-size:14px;font-weight:700;background:var(--ab-gradient);color:#fff;text-decoration:none;transition:all 0.2s;"><i class="fas fa-download"></i> Get Alfred Browser</a>
                <a href="#veil-messenger" style="display:inline-flex;align-items:center;gap:8px;padding:14px 28px;border-radius:12px;font-size:14px;font-weight:700;background:var(--vm-gradient);color:#fff;text-decoration:none;transition:all 0.2s;"><i class="fas fa-download"></i> Get Veil Messenger</a>
                <a href="#pulse-social" style="display:inline-flex;align-items:center;gap:8px;padding:14px 28px;border-radius:12px;font-size:14px;font-weight:700;background:var(--ps-gradient);color:#fff;text-decoration:none;transition:all 0.2s;"><i class="fas fa-download"></i> Get Pulse Social</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/site-footer.inc.php'; ?>
