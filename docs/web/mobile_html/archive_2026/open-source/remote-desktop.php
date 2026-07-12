<?php
/**
 * RustDesk — Self-Hosted Remote Desktop | GoSiteMe
 */
$page_title = 'RustDesk — Self-Hosted Remote Desktop, TeamViewer Alternative | GoSiteMe';
$page_description = 'Deploy your own RustDesk remote desktop server. Open-source TeamViewer/AnyDesk alternative with end-to-end encryption. Unlimited devices, no per-seat fees. Included with GoSiteMe hosting.';
$page_canonical = 'https://gositeme.com/open-source/remote-desktop.php';
$page_og_title = 'RustDesk — Self-Hosted Remote Desktop';
$page_og_description = $page_description;
$page_twitter_description = $page_og_description;
$page_og_image = 'https://gositeme.com/assets/hero-banner.png';
$page_robots = 'index, follow';
$preload_hero = false;
require_once __DIR__ . '/../includes/site-header.inc.php';
?>
<style>
.tp-hero{padding:100px 0 60px;text-align:center;position:relative;overflow:hidden;background:linear-gradient(180deg,rgba(0,168,255,.1) 0%,transparent 60%)}
.tp-hero::before{content:'';position:absolute;top:-200px;right:-200px;width:600px;height:600px;background:radial-gradient(circle,rgba(0,168,255,.2),transparent 70%);border-radius:50%}
.tp-hero .container{position:relative;z-index:2;max-width:1100px;margin:0 auto;padding:0 24px}
.tp-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(0,168,255,.12);border:1px solid rgba(0,168,255,.3);padding:6px 16px;border-radius:20px;font-size:.85rem;color:#00A8FF;margin-bottom:20px;font-weight:600}
.tp-hero h1{font-family:'Space Grotesk',sans-serif;font-size:clamp(2rem,5vw,3rem);font-weight:800;color:#fff;margin:0 0 16px;line-height:1.15}
.tp-hero h1 span{color:#00A8FF}
.tp-hero p.sub{color:#a0a0b8;font-size:1.1rem;max-width:700px;margin:0 auto 30px;line-height:1.7}
.tp-hero-btns{display:flex;justify-content:center;gap:16px;flex-wrap:wrap}
.tp-btn{display:inline-flex;align-items:center;gap:8px;padding:12px 28px;font-size:.9rem;font-weight:600;border-radius:8px;text-decoration:none;transition:all .3s;border:none;cursor:pointer;font-family:'Inter',sans-serif}
.tp-btn-primary{background:#0074D9;color:#fff}
.tp-btn-primary:hover{background:#00A8FF;transform:translateY(-2px);box-shadow:0 10px 40px rgba(0,116,217,.3)}
.tp-btn-ghost{background:rgba(255,255,255,.05);color:#fff;border:1px solid rgba(255,255,255,.15)}
.tp-btn-ghost:hover{background:rgba(255,255,255,.1)}
.tp-section{padding:80px 24px}
.tp-section .container{max-width:1100px;margin:0 auto}
.tp-section-alt{background:rgba(18,18,42,.4)}
.tp-title{font-family:'Space Grotesk',sans-serif;font-size:1.8rem;font-weight:700;color:#fff;text-align:center;margin-bottom:12px}
.tp-subtitle{color:#a0a0b8;text-align:center;margin-bottom:50px;font-size:1rem;max-width:600px;margin-left:auto;margin-right:auto}
.tp-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:24px}
.tp-card{background:rgba(18,18,42,.8);border:1px solid rgba(30,30,62,.8);border-radius:12px;padding:30px;transition:all .3s}
.tp-card:hover{border-color:rgba(0,168,255,.3);transform:translateY(-3px)}
.tp-card .icon{font-size:2rem;margin-bottom:16px}
.tp-card h3{font-family:'Space Grotesk',sans-serif;font-size:1.1rem;font-weight:600;color:#fff;margin-bottom:10px}
.tp-card p{color:#a0a0b8;font-size:.9rem;line-height:1.6}
.tp-vs{display:grid;grid-template-columns:1fr 1fr;gap:30px;margin-top:40px}
.tp-vs-col{background:rgba(18,18,42,.8);border:1px solid rgba(30,30,62,.8);border-radius:12px;padding:30px}
.tp-vs-col h3{font-family:'Space Grotesk',sans-serif;font-size:1.2rem;font-weight:700;margin-bottom:20px}
.tp-vs-col.them h3{color:#ff4757}
.tp-vs-col.us h3{color:#00d4aa}
.tp-vs-col ul{list-style:none;padding:0}
.tp-vs-col ul li{padding:8px 0;font-size:.9rem;color:#c0c0d0;display:flex;align-items:center;gap:10px;border-bottom:1px solid rgba(30,30,62,.5)}
.tp-vs-col.them li::before{content:'✕';color:#ff4757;font-weight:700}
.tp-vs-col.us li::before{content:'✓';color:#00d4aa;font-weight:700}
.tp-steps{counter-reset:step}
.tp-step{display:flex;gap:24px;align-items:flex-start;margin-bottom:30px}
.tp-step::before{counter-increment:step;content:counter(step);font-family:'Space Grotesk',sans-serif;font-size:1.5rem;font-weight:700;color:#00A8FF;background:rgba(0,168,255,.1);width:50px;height:50px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.tp-step-content h3{font-family:'Space Grotesk',sans-serif;font-size:1.1rem;font-weight:600;color:#fff;margin-bottom:6px}
.tp-step-content p{color:#a0a0b8;font-size:.9rem;line-height:1.6}
.tp-cta{padding:80px 24px;text-align:center}
.tp-cta .container{max-width:800px;margin:0 auto}
.tp-cta h2{font-family:'Space Grotesk',sans-serif;font-size:2rem;font-weight:700;color:#fff;margin-bottom:16px}
.tp-cta h2 span{color:#00A8FF}
.tp-cta p{color:#a0a0b8;margin-bottom:30px;line-height:1.7}
.tp-cta-btns{display:flex;justify-content:center;gap:16px;flex-wrap:wrap}
.tp-specs{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-top:40px}
.tp-spec{background:rgba(18,18,42,.8);border:1px solid rgba(30,30,62,.8);border-radius:12px;padding:20px;text-align:center}
.tp-spec .val{font-family:'Space Grotesk',sans-serif;font-size:1.5rem;font-weight:700;color:#00A8FF}
.tp-spec .lbl{color:#a0a0b8;font-size:.8rem;margin-top:4px}
@media(max-width:768px){.tp-vs{grid-template-columns:1fr}.tp-specs{grid-template-columns:1fr 1fr}}
</style>

<section class="tp-hero">
    <div class="container">
        <div class="tp-badge"><i class="fas fa-desktop"></i> Remote Desktop</div>
        <h1>Your Own <span>TeamViewer</span><br>Without the Price Tag</h1>
        <p class="sub">RustDesk is the open-source remote desktop solution with 75,000+ GitHub stars. Self-host your relay server, support unlimited devices, and white-label it as your own — all included with GoSiteMe hosting.</p>
        <div class="tp-hero-btns">
            <a href="/store/ai-domain-hosting-connected-with-ai-editor" class="tp-btn tp-btn-primary"><i class="fas fa-rocket"></i> Deploy RustDesk</a>
            <a href="https://github.com/rustdesk/rustdesk" target="_blank" rel="noopener" class="tp-btn tp-btn-ghost"><i class="fab fa-github"></i> View on GitHub</a>
        </div>
    </div>
</section>

<section class="tp-section">
    <div class="container">
        <h2 class="tp-title">Key Features</h2>
        <p class="tp-subtitle">Everything you need for remote access — nothing you don't.</p>
        <div class="tp-grid">
            <div class="tp-card"><div class="icon">🔐</div><h3>End-to-End Encryption</h3><p>All connections encrypted with NaCl/libsodium. Your relay server means zero third-party involvement in the data path.</p></div>
            <div class="tp-card"><div class="icon">🌍</div><h3>Cross-Platform</h3><p>Native clients for Windows, macOS, Linux, iOS, and Android. Web client also available for quick access.</p></div>
            <div class="tp-card"><div class="icon">📁</div><h3>File Transfer</h3><p>Drag-and-drop file transfer between local and remote machines. Resume interrupted transfers automatically.</p></div>
            <div class="tp-card"><div class="icon">🖥️</div><h3>Multi-Monitor</h3><p>Switch between remote monitors seamlessly. View all screens at once or focus on one.</p></div>
            <div class="tp-card"><div class="icon">📋</div><h3>Clipboard Sync</h3><p>Copy and paste text, images, and files between connected machines. Bidirectional and instant.</p></div>
            <div class="tp-card"><div class="icon">🎨</div><h3>White-Label Ready</h3><p>Custom logo, app name, and server configuration baked into the client. Your brand on every connection.</p></div>
        </div>
    </div>
</section>

<section class="tp-section tp-section-alt">
    <div class="container">
        <h2 class="tp-title">TeamViewer vs. RustDesk</h2>
        <p class="tp-subtitle">Why businesses are switching to self-hosted remote desktop.</p>
        <div class="tp-vs">
            <div class="tp-vs-col them">
                <h3>TeamViewer Business</h3>
                <ul>
                    <li>$49.90/month per user</li>
                    <li>Device limits & "commercial use" blocks</li>
                    <li>Data routed through TeamViewer servers</li>
                    <li>Limited customization</li>
                    <li>Requires internet connection to relay</li>
                    <li>Vendor lock-in</li>
                </ul>
            </div>
            <div class="tp-vs-col us">
                <h3>RustDesk + GoSiteMe</h3>
                <ul>
                    <li>$0 extra — included with hosting</li>
                    <li>Unlimited devices, no restrictions</li>
                    <li>Data stays on YOUR server</li>
                    <li>Full white-label & source access</li>
                    <li>Works on LAN without internet</li>
                    <li>Open source — you own it forever</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="tp-section">
    <div class="container">
        <h2 class="tp-title">How It Works</h2>
        <p class="tp-subtitle">From zero to remote desktop in four steps.</p>
        <div class="tp-steps">
            <div class="tp-step"><div class="tp-step-content"><h3>Get a GoSiteMe Server</h3><p>Choose any hosting plan. RustDesk relay server is pre-configured in Docker and ready to deploy.</p></div></div>
            <div class="tp-step"><div class="tp-step-content"><h3>Deploy with One Click</h3><p>Launch the RustDesk relay (hbbs + hbbr) from your server dashboard. SSL certificate auto-provisioned.</p></div></div>
            <div class="tp-step"><div class="tp-step-content"><h3>Download & Connect</h3><p>Install the RustDesk client on any device. Enter your server address and public key. Done.</p></div></div>
            <div class="tp-step"><div class="tp-step-content"><h3>Manage & Scale</h3><p>Add team members, configure access policies, and monitor connections from the RustDesk API console.</p></div></div>
        </div>
        <div class="tp-specs">
            <div class="tp-spec"><div class="val">&lt;30ms</div><div class="lbl">Latency on LAN</div></div>
            <div class="tp-spec"><div class="val">75k+</div><div class="lbl">GitHub Stars</div></div>
            <div class="tp-spec"><div class="val">∞</div><div class="lbl">Devices</div></div>
            <div class="tp-spec"><div class="val">AGPL-3.0</div><div class="lbl">License</div></div>
        </div>
    </div>
</section>

<section class="tp-cta">
    <div class="container">
        <h2>Start Remote Access <span>Today</span></h2>
        <p>No per-device fees. No data leaving your network. Just fast, secure remote desktop — self-hosted on your GoSiteMe server.</p>
        <div class="tp-cta-btns">
            <a href="/store/ai-domain-hosting-connected-with-ai-editor" class="tp-btn tp-btn-primary"><i class="fas fa-rocket"></i> Get Hosting + RustDesk</a>
            <a href="/open-source/" class="tp-btn tp-btn-ghost"><i class="fas fa-arrow-left"></i> All Tools</a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/site-footer.inc.php'; ?>
