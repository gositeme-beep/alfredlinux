<?php
$page_title = 'Sovereign Browser — Powered by Alfred | GoSiteMe';
$page_description = 'A browser built for the sovereign web: Alfred Browser with sovereign domain routing, native trust controls, local gateway support, and a desktop posture designed for user-owned internet infrastructure.';
$page_canonical = 'https://root.com/sovereign-browser';
require_once __DIR__ . '/includes/site-header.inc.php';
?>
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<link rel="stylesheet" href="/assets/css/fonts.css">

<style>
:root {
    --sb-bg: #07111f;
    --sb-panel: rgba(10, 20, 36, 0.82);
    --sb-border: rgba(125, 211, 252, 0.16);
    --sb-text: #e2ecff;
    --sb-muted: rgba(226, 236, 255, 0.7);
    --sb-cyan: #67e8f9;
    --sb-blue: #60a5fa;
    --sb-gold: #fbbf24;
    --sb-green: #34d399;
    --sb-red: #f87171;
    --sb-grad: linear-gradient(135deg, #67e8f9 0%, #60a5fa 50%, #38bdf8 100%);
}

.sb-page {
    min-height: 100vh;
    background:
        radial-gradient(circle at top left, rgba(103, 232, 249, 0.12), transparent 28%),
        radial-gradient(circle at bottom right, rgba(96, 165, 250, 0.14), transparent 30%),
        linear-gradient(180deg, #050b14, var(--sb-bg));
    color: var(--sb-text);
    font-family: 'Inter', 'DM Sans', system-ui, sans-serif;
}

.sb-wrap {
    max-width: 1180px;
    margin: 0 auto;
    padding: 0 24px;
}

.sb-hero {
    padding: 110px 0 72px;
}

.sb-kicker {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    border-radius: 999px;
    background: rgba(103, 232, 249, 0.12);
    color: var(--sb-cyan);
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

.sb-hero h1 {
    margin: 20px 0 18px;
    font-size: clamp(44px, 7vw, 82px);
    line-height: 0.96;
    letter-spacing: -0.05em;
    font-weight: 900;
    background: var(--sb-grad);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.sb-hero p {
    max-width: 760px;
    color: var(--sb-muted);
    font-size: 18px;
    line-height: 1.75;
}

.sb-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
    margin-top: 28px;
}

.sb-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 14px 20px;
    border-radius: 14px;
    border: 1px solid var(--sb-border);
    color: #fff;
    text-decoration: none;
    font-weight: 700;
}

.sb-btn-primary {
    background: var(--sb-grad);
    border-color: rgba(103, 232, 249, 0.28);
    color: #04111d;
}

.sb-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
    gap: 18px;
    margin-top: 48px;
}

.sb-card {
    padding: 22px;
    border-radius: 20px;
    border: 1px solid var(--sb-border);
    background: var(--sb-panel);
    backdrop-filter: blur(14px);
}

.sb-card h3 {
    margin: 0 0 10px;
    font-size: 18px;
}

.sb-card p {
    margin: 0;
    color: var(--sb-muted);
    font-size: 14px;
    line-height: 1.7;
}

.sb-section {
    padding: 26px 0 80px;
}

.sb-section h2 {
    margin: 0 0 14px;
    font-size: clamp(28px, 5vw, 46px);
    letter-spacing: -0.04em;
}

.sb-section-copy {
    max-width: 760px;
    color: var(--sb-muted);
    font-size: 16px;
    line-height: 1.75;
}

.sb-compare {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 18px;
    margin-top: 32px;
}

.sb-compare .sb-card strong {
    display: block;
    margin-bottom: 8px;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

.sb-good { color: var(--sb-green); }
.sb-warn { color: var(--sb-gold); }
.sb-risk { color: var(--sb-red); }

.sb-status-strip {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 16px;
    margin-top: 28px;
}

.sb-status-pill {
    padding: 18px 20px;
    border-radius: 18px;
    border: 1px solid var(--sb-border);
    background: rgba(255,255,255,0.03);
}

.sb-status-pill strong {
    display: block;
    margin-bottom: 8px;
    font-size: 15px;
    color: #fff;
}

.sb-status-pill p {
    margin: 0;
    color: var(--sb-muted);
    font-size: 14px;
    line-height: 1.7;
}

@media (max-width: 720px) {
    .sb-hero {
        padding-top: 88px;
    }

    .sb-hero p {
        font-size: 16px;
    }
}
</style>

<main class="sb-page">
    <section class="sb-hero">
        <div class="sb-wrap">
            <span class="sb-kicker"><i class="fas fa-globe"></i> Sovereign Browser</span>
            <h1>The browser for your own web.</h1>
            <p>
                Sovereign Browser is the Alfred Browser posture focused on sovereign domains, local gateway routing,
                native trust boundaries, and a browsing model that serves user-owned infrastructure instead of platform lock-in.
            </p>
            <div class="sb-actions">
                <a class="sb-btn sb-btn-primary" href="/alfred-browser.php"><i class="fas fa-download"></i> Get Alfred Browser</a>
                <a class="sb-btn" href="/search.php"><i class="fas fa-magnifying-glass"></i> Explore Alfred Search</a>
                <a class="sb-btn" href="/internet-sovereignty.php"><i class="fas fa-network-wired"></i> Read the Sovereignty Vision</a>
            </div>
            <div class="sb-status-strip">
                <article class="sb-status-pill">
                    <strong>Public stable download</strong>
                    <p>Customers can download Alfred Browser 4.0.0 right now from the live download page — available on Windows, macOS, Linux, and Android.</p>
                </article>
                <article class="sb-status-pill">
                    <strong>Sovereign feature status</strong>
                    <p>Sovereign routing and browser-chrome work remain in active source development. The first Linux 4.0 preview packages are public now, but Windows and macOS for the newer posture are still pending packaging.</p>
                </article>
                <article class="sb-status-pill">
                    <strong>Trust posture</strong>
                    <p>Use this page to explain the direction and trust model, while being explicit about the release split: 4.0.0 is the cross-platform stable release, available on all platforms.</p>
                </article>
            </div>
            <div class="sb-grid">
                <article class="sb-card">
                    <h3>Sovereign routing</h3>
                    <p>Resolve custom TLDs through Alfred's local sovereign DNS and gateway path instead of depending on mainstream browser assumptions.</p>
                </article>
                <article class="sb-card">
                    <h3>Native trust boundary</h3>
                    <p>Remote web pages are restricted to low-risk browser utilities while crypto and keystore access stay behind trusted local app origins.</p>
                </article>
                <article class="sb-card">
                    <h3>Security posture surfaced</h3>
                    <p>The desktop client now exposes its own release-trust and native-gating state directly in the command center instead of hiding it behind code.</p>
                </article>
                <article class="sb-card">
                    <h3>Realtime sovereign apps</h3>
                    <p>The gateway and runtime bridge now cover fetch, forms, cookies, websocket upgrades, EventSource, and same-domain realtime traffic.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="sb-section">
        <div class="sb-wrap">
            <h2>Why this exists</h2>
            <p class="sb-section-copy">
                Mainstream browsers assume the mainstream web. Alfred Browser is being pushed toward the opposite model:
                first-class support for sovereign naming, local trust controls, privacy-first product surfaces, and an ecosystem where the browser is part of your own infrastructure rather than a rented gatekeeper.
            </p>

            <div class="sb-compare">
                <article class="sb-card">
                    <strong class="sb-good">Already done</strong>
                    <p>Injected browser chrome, sovereign gateway routing, runtime rewrite bridge, websocket upgrade proxying, native caller-origin gating, and visible security posture inside the command center.</p>
                </article>
                <article class="sb-card">
                    <strong class="sb-warn">Still in progress</strong>
                    <p>Signed updater trust, platform code signing, tamper-aware build verification, and deeper separation between local privileged UI and remote web content.</p>
                </article>
                <article class="sb-card">
                    <strong class="sb-risk">Not promised yet</strong>
                    <p>Tor-grade anonymity. Alfred Browser can warn and compartmentalize, but it is not yet a hardened Tor Browser replacement.</p>
                </article>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/site-footer.inc.php'; ?>