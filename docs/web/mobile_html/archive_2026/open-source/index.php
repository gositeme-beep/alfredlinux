<?php
/**
 * Open-Source Tools Hub — GoSiteMe
 * Self-hosted, white-label tools bundled with hosting
 */

$page_title = 'Open-Source Tools — Remote Desktop, Office, Video Editor, Messaging, Git | GoSiteMe';
$page_description = 'Deploy powerful open-source tools on your own server. RustDesk remote desktop, OnlyOffice suite, OpenCut video editor, Element messaging, and Gitea git platform — all self-hosted with GoSiteMe.';
$page_canonical = 'https://gositeme.com/open-source/';
$page_og_title = 'Open-Source Tools — Self-Hosted & White-Label';
$page_og_description = $page_description;
$page_twitter_description = $page_og_description;
$page_og_image = 'https://gositeme.com/assets/hero-banner.png';
$page_robots = 'index, follow';
$preload_hero = false;

require_once __DIR__ . '/../includes/site-header.inc.php';
?>

<style>
.th-hero{padding:100px 0 60px;text-align:center;position:relative;overflow:hidden;background:linear-gradient(180deg,rgba(0,168,255,.08) 0%,transparent 60%)}
.th-hero::before{content:'';position:absolute;top:-200px;right:-200px;width:600px;height:600px;background:radial-gradient(circle,rgba(0,116,217,.15),transparent 70%);border-radius:50%}
.th-hero .container{position:relative;z-index:2;max-width:1200px;margin:0 auto;padding:0 24px}
.th-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(0,212,170,.12);border:1px solid rgba(0,212,170,.3);padding:6px 16px;border-radius:20px;font-size:.85rem;color:#00d4aa;margin-bottom:20px;font-weight:600}
.th-hero h1{font-family:'Space Grotesk',sans-serif;font-size:clamp(2rem,5vw,3.2rem);font-weight:800;color:#fff;margin:0 0 16px;line-height:1.15}
.th-hero h1 span{color:#00A8FF}
.th-hero p{color:#a0a0b8;font-size:1.1rem;max-width:700px;margin:0 auto 30px;line-height:1.7}
.th-stats{display:flex;justify-content:center;gap:40px;flex-wrap:wrap;margin-top:30px}
.th-stat{text-align:center}
.th-stat .num{font-family:'Space Grotesk',sans-serif;font-size:2rem;font-weight:700;color:#fff}
.th-stat .label{color:#a0a0b8;font-size:.8rem;margin-top:4px}
.th-why{padding:80px 24px}
.th-why .container{max-width:1200px;margin:0 auto}
.th-section-title{font-family:'Space Grotesk',sans-serif;font-size:2rem;font-weight:700;color:#fff;text-align:center;margin-bottom:12px}
.th-section-sub{color:#a0a0b8;text-align:center;margin-bottom:50px;font-size:1rem}
.th-why-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:24px}
.th-why-card{background:rgba(18,18,42,.8);border:1px solid rgba(30,30,62,.8);border-radius:12px;padding:30px;transition:all .3s}
.th-why-card:hover{border-color:rgba(0,168,255,.3);transform:translateY(-3px)}
.th-why-card .icon{font-size:2rem;margin-bottom:16px}
.th-why-card h3{font-family:'Space Grotesk',sans-serif;font-size:1.15rem;font-weight:600;color:#fff;margin-bottom:10px}
.th-why-card p{color:#a0a0b8;font-size:.9rem;line-height:1.6}
.th-tools{padding:0 24px 80px}
.th-tools .container{max-width:1200px;margin:0 auto}
.th-tools-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(350px,1fr));gap:24px}
.th-tool{background:rgba(18,18,42,.8);border:1px solid rgba(30,30,62,.8);border-radius:16px;overflow:hidden;transition:all .4s}
.th-tool:hover{border-color:rgba(0,168,255,.3);transform:translateY(-5px);box-shadow:0 20px 60px rgba(0,0,0,.3)}
.th-tool-header{padding:30px 30px 20px;display:flex;align-items:center;gap:16px}
.th-tool-icon{width:60px;height:60px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.8rem;flex-shrink:0}
.th-tool-header h3{font-family:'Space Grotesk',sans-serif;font-size:1.3rem;font-weight:700;color:#fff;margin:0}
.th-tool-header .tagline{color:#a0a0b8;font-size:.85rem;margin-top:4px}
.th-tool-body{padding:0 30px 20px}
.th-tool-body p{color:#a0a0b8;font-size:.9rem;line-height:1.6;margin-bottom:16px}
.th-tool-features{list-style:none;padding:0;margin:0 0 20px}
.th-tool-features li{color:#c0c0d0;font-size:.85rem;padding:6px 0;display:flex;align-items:center;gap:10px}
.th-tool-features li::before{content:'✓';color:#00d4aa;font-weight:700}
.th-tool-meta{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:16px}
.th-tool-tag{background:rgba(255,255,255,.05);border:1px solid rgba(30,30,62,.8);padding:4px 12px;border-radius:100px;font-size:.7rem;color:#a0a0b8;font-weight:500}
.th-tool-footer{padding:20px 30px;border-top:1px solid rgba(30,30,62,.8);display:flex;justify-content:space-between;align-items:center}
.th-tool-price{font-family:'Space Grotesk',sans-serif;font-size:1.1rem;font-weight:700;color:#00d4aa}
.th-tool-price small{color:#a0a0b8;font-weight:400;font-size:.75rem}
.th-btn{display:inline-flex;align-items:center;gap:8px;padding:10px 24px;font-size:.85rem;font-weight:600;border-radius:8px;text-decoration:none;transition:all .3s;border:none;cursor:pointer;font-family:'Inter',sans-serif}
.th-btn-primary{background:#0074D9;color:#fff}
.th-btn-primary:hover{background:#00A8FF;transform:translateY(-1px)}
.th-btn-ghost{background:rgba(255,255,255,.05);color:#fff;border:1px solid rgba(255,255,255,.1)}
.th-btn-ghost:hover{background:rgba(255,255,255,.1)}
.th-compare{padding:80px 24px;background:rgba(18,18,42,.4)}
.th-compare .container{max-width:1200px;margin:0 auto}
.th-table-wrap{overflow-x:auto;margin-top:40px}
.th-table{width:100%;border-collapse:collapse;background:rgba(18,18,42,.8);border:1px solid rgba(30,30,62,.8);border-radius:12px;overflow:hidden}
.th-table th,.th-table td{padding:14px 20px;text-align:left;font-size:.85rem;border-bottom:1px solid rgba(30,30,62,.5)}
.th-table th{background:rgba(0,116,217,.1);color:#00A8FF;font-family:'Space Grotesk',sans-serif;font-weight:600}
.th-table td{color:#c0c0d0}
.th-table .check{color:#00d4aa;font-weight:700}
.th-cta{padding:80px 24px;text-align:center}
.th-cta .container{max-width:800px;margin:0 auto}
.th-cta h2{font-family:'Space Grotesk',sans-serif;font-size:2rem;font-weight:700;color:#fff;margin-bottom:16px}
.th-cta h2 span{color:#00A8FF}
.th-cta p{color:#a0a0b8;margin-bottom:30px;font-size:1rem;line-height:1.7}
.th-cta-buttons{display:flex;justify-content:center;gap:16px;flex-wrap:wrap}
.th-btn-lg{padding:14px 32px;font-size:.95rem;border-radius:10px}
@media(max-width:768px){.th-stats{gap:20px}.th-tools-grid{grid-template-columns:1fr}}
</style>

<section class="th-hero">
    <div class="container">
        <div class="th-badge"><i class="fas fa-cube"></i> Open-Source Tools</div>
        <h1>Self-Hosted <span>Power Tools</span><br>For Your Business</h1>
        <p>Deploy enterprise-grade open-source software on your own server. No vendor lock-in, full data ownership, white-label ready. All included with GoSiteMe hosting.</p>
        <div class="th-stats">
            <div class="th-stat"><div class="num">6</div><div class="label">Tools</div></div>
            <div class="th-stat"><div class="num">100%</div><div class="label">Open Source</div></div>
            <div class="th-stat"><div class="num">$0</div><div class="label">Extra Software Cost</div></div>
            <div class="th-stat"><div class="num">∞</div><div class="label">Users Included</div></div>
        </div>
    </div>
</section>

<section class="th-why">
    <div class="container">
        <h2 class="th-section-title">Why Self-Host?</h2>
        <p class="th-section-sub">Take control of your tools, your data, and your costs.</p>
        <div class="th-why-grid">
            <div class="th-why-card"><div class="icon">🔒</div><h3>Full Data Ownership</h3><p>Your data stays on your server. No third-party access, no data mining, no surprises. GDPR, HIPAA, and SOC 2 compliant by design.</p></div>
            <div class="th-why-card"><div class="icon">💰</div><h3>Eliminate Per-Seat Pricing</h3><p>No more $15/user/month. Host unlimited users on your own server. One flat hosting cost replaces thousands in SaaS subscriptions.</p></div>
            <div class="th-why-card"><div class="icon">🎨</div><h3>White-Label Everything</h3><p>Brand each tool as your own. Custom logos, colors, domains. Your clients see your brand, not someone else's.</p></div>
            <div class="th-why-card"><div class="icon">🚀</div><h3>One-Click Deploy</h3><p>Docker-based deployment on GoSiteMe servers. We handle updates, backups, and SSL. You focus on your business.</p></div>
            <div class="th-why-card"><div class="icon">🔧</div><h3>Full Customization</h3><p>Modify anything — source code included. Add features, integrations, or automations specific to your workflow.</p></div>
            <div class="th-why-card"><div class="icon">🌐</div><h3>No Internet Required</h3><p>Tools run on your network. Perfect for air-gapped environments, remote offices, or anywhere with spotty connectivity.</p></div>
        </div>
    </div>
</section>

<section class="th-tools" id="tools">
    <div class="container">
        <h2 class="th-section-title">The Toolkit</h2>
        <p class="th-section-sub">Six essential tools that replace expensive SaaS subscriptions.</p>
        <div class="th-tools-grid">

            <div class="th-tool">
                <div class="th-tool-header">
                    <div class="th-tool-icon" style="background:rgba(0,168,255,.15)">🖥️</div>
                    <div><h3>RustDesk</h3><div class="tagline">Remote Desktop</div></div>
                </div>
                <div class="th-tool-body">
                    <p>Open-source TeamViewer / AnyDesk alternative. Fast, secure remote access with your own relay server.</p>
                    <ul class="th-tool-features">
                        <li>Self-hosted relay &amp; signaling server</li>
                        <li>End-to-end encryption</li>
                        <li>Windows, Mac, Linux, iOS, Android</li>
                        <li>File transfer, clipboard sync, multi-monitor</li>
                        <li>White-label with custom branding</li>
                    </ul>
                    <div class="th-tool-meta"><span class="th-tool-tag">AGPL-3.0</span><span class="th-tool-tag">Rust</span><span class="th-tool-tag">75k+ ★</span></div>
                </div>
                <div class="th-tool-footer">
                    <div class="th-tool-price">Included <small>with hosting</small></div>
                    <a href="/open-source/remote-desktop.php" class="th-btn th-btn-primary">Learn More →</a>
                </div>
            </div>

            <div class="th-tool">
                <div class="th-tool-header">
                    <div class="th-tool-icon" style="background:rgba(125,0,255,.15)">📄</div>
                    <div><h3>OnlyOffice</h3><div class="tagline">Office Suite</div></div>
                </div>
                <div class="th-tool-body">
                    <p>Full office suite in the browser. Documents, spreadsheets, presentations — with real-time collaboration.</p>
                    <ul class="th-tool-features">
                        <li>100% MS Office file compatibility</li>
                        <li>Real-time co-editing (WOPI protocol)</li>
                        <li>PDF editor, e-signatures, forms</li>
                        <li>Chat, comments, track changes</li>
                        <li>Integrates with Nextcloud, Seafile, etc.</li>
                    </ul>
                    <div class="th-tool-meta"><span class="th-tool-tag">AGPL-3.0</span><span class="th-tool-tag">Docker</span><span class="th-tool-tag">5k+ ★</span></div>
                </div>
                <div class="th-tool-footer">
                    <div class="th-tool-price">Included <small>with hosting</small></div>
                    <a href="/open-source/office-suite.php" class="th-btn th-btn-primary">Learn More →</a>
                </div>
            </div>

            <div class="th-tool">
                <div class="th-tool-header">
                    <div class="th-tool-icon" style="background:rgba(255,107,53,.15)">🎬</div>
                    <div><h3>OpenCut</h3><div class="tagline">Video Editor</div></div>
                </div>
                <div class="th-tool-body">
                    <p>The open-source CapCut alternative. Timeline-based video editing in the browser — no watermarks, no subscriptions.</p>
                    <ul class="th-tool-features">
                        <li>Timeline-based editing with multi-track</li>
                        <li>Real-time preview, WebGL effects</li>
                        <li>Privacy-first — videos stay on device</li>
                        <li>Web, desktop, and mobile support</li>
                        <li>No watermarks or export limits</li>
                    </ul>
                    <div class="th-tool-meta"><span class="th-tool-tag">MIT</span><span class="th-tool-tag">Next.js</span><span class="th-tool-tag">46k+ ★</span></div>
                </div>
                <div class="th-tool-footer">
                    <div class="th-tool-price">Included <small>with hosting</small></div>
                    <a href="/open-source/video-editor.php" class="th-btn th-btn-primary">Learn More →</a>
                </div>
            </div>

            <div class="th-tool">
                <div class="th-tool-header">
                    <div class="th-tool-icon" style="background:rgba(0,212,170,.15)">💬</div>
                    <div><h3>Element</h3><div class="tagline">Secure Messaging</div></div>
                </div>
                <div class="th-tool-body">
                    <p>End-to-end encrypted messaging on Matrix protocol. Slack/Teams alternative with federation support.</p>
                    <ul class="th-tool-features">
                        <li>End-to-end encryption (Olm/Megolm)</li>
                        <li>Voice &amp; video calls, screen sharing</li>
                        <li>Spaces, threads, reactions, rich text</li>
                        <li>Federation — talk to any Matrix server</li>
                        <li>Bridges: Slack, Discord, Telegram, IRC</li>
                    </ul>
                    <div class="th-tool-meta"><span class="th-tool-tag">AGPL-3.0</span><span class="th-tool-tag">Matrix</span><span class="th-tool-tag">11k+ ★</span></div>
                </div>
                <div class="th-tool-footer">
                    <div class="th-tool-price">Included <small>with hosting</small></div>
                    <a href="/open-source/messaging.php" class="th-btn th-btn-primary">Learn More →</a>
                </div>
            </div>

            <div class="th-tool">
                <div class="th-tool-header">
                    <div class="th-tool-icon" style="background:rgba(255,214,0,.15)">🐙</div>
                    <div><h3>Gitea</h3><div class="tagline">Git Platform</div></div>
                </div>
                <div class="th-tool-body">
                    <p>Lightweight, self-hosted GitHub alternative. Git repos, issues, pull requests, CI/CD — in a single binary.</p>
                    <ul class="th-tool-features">
                        <li>Git hosting with web UI (PRs, issues, wikis)</li>
                        <li>Built-in CI/CD (Gitea Actions)</li>
                        <li>Packages, container registry, LFS</li>
                        <li>LDAP, OAuth2, 2FA authentication</li>
                        <li>Migrate from GitHub, GitLab, Bitbucket</li>
                    </ul>
                    <div class="th-tool-meta"><span class="th-tool-tag">MIT</span><span class="th-tool-tag">Go</span><span class="th-tool-tag">46k+ ★</span></div>
                </div>
                <div class="th-tool-footer">
                    <div class="th-tool-price">Included <small>with hosting</small></div>
                    <a href="/open-source/git-platform.php" class="th-btn th-btn-primary">Learn More →</a>
                </div>
            </div>

            <div class="th-tool" style="border-color:rgba(0,212,255,.2);background:linear-gradient(135deg,rgba(18,18,42,.95),rgba(0,212,255,.03))">
                <div class="th-tool-header">
                    <div class="th-tool-icon" style="background:rgba(0,212,255,.15)">⚡</div>
                    <div><h3>Circuit Simulator</h3><div class="tagline">GoSiteMe Exclusive</div></div>
                </div>
                <div class="th-tool-body">
                    <p>Build and simulate electronic circuits in your browser. Drag-and-drop 20+ components with real-time Ohm's law simulation.</p>
                    <ul class="th-tool-features">
                        <li>Resistors, capacitors, inductors, transistors, op-amps</li>
                        <li>Real-time voltage, current &amp; power simulation</li>
                        <li>Prebuilt circuits: LED, 555 timer, bridge rectifier</li>
                        <li>Export, share via URL, undo/redo</li>
                        <li>GSM energy rewards for contributions</li>
                    </ul>
                    <div class="th-tool-meta"><span class="th-tool-tag" style="background:rgba(0,212,255,.1);border-color:rgba(0,212,255,.3);color:#00D4FF">PROPRIETARY</span><span class="th-tool-tag">Canvas</span><span class="th-tool-tag">GoSiteMe Only</span></div>
                </div>
                <div class="th-tool-footer">
                    <div class="th-tool-price">Free <small>for all users</small></div>
                    <a href="/circuit-simulator.php" class="th-btn th-btn-primary">Launch Simulator →</a>
                </div>
            </div>

        </div>
    </div>
</section>

<section class="th-compare" id="compare">
    <div class="container">
        <h2 class="th-section-title">SaaS vs. Self-Hosted</h2>
        <p class="th-section-sub">See how much you save by switching to self-hosted tools.</p>
        <div class="th-table-wrap">
            <table class="th-table">
                <thead><tr><th>Tool Category</th><th>SaaS Equivalent</th><th>SaaS Cost (10 users/yr)</th><th>GoSiteMe Self-Hosted</th></tr></thead>
                <tbody>
                    <tr><td>Remote Desktop</td><td>TeamViewer Business</td><td>$5,988/yr</td><td class="check">$0 — Included</td></tr>
                    <tr><td>Office Suite</td><td>Microsoft 365 Business</td><td>$1,500/yr</td><td class="check">$0 — Included</td></tr>
                    <tr><td>Video Editor</td><td>Adobe Premiere Pro</td><td>$2,639/yr</td><td class="check">$0 — Included</td></tr>
                    <tr><td>Team Messaging</td><td>Slack Pro</td><td>$1,050/yr</td><td class="check">$0 — Included</td></tr>
                    <tr><td>Git Platform</td><td>GitHub Team</td><td>$480/yr</td><td class="check">$0 — Included</td></tr>
                    <tr><td>Circuit Simulator</td><td>Multisim / Tinkercad</td><td>$1,200/yr</td><td class="check">$0 — Included</td></tr>
                    <tr style="font-weight:700;background:rgba(0,212,170,.05)"><td>TOTAL</td><td></td><td style="color:#ff4757">$12,857/yr</td><td class="check">$0 with hosting</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<section class="th-cta">
    <div class="container">
        <h2>Ready to <span>Own Your Tools</span>?</h2>
        <p>Get all five tools pre-installed on a GoSiteMe server. Docker-managed, auto-updated, SSL-secured. Start deploying in minutes.</p>
        <div class="th-cta-buttons">
            <a href="/store/ai-domain-hosting-connected-with-ai-editor" class="th-btn th-btn-primary th-btn-lg"><i class="fas fa-rocket"></i> Get Hosting + Tools</a>
            <a href="#tools" class="th-btn th-btn-ghost th-btn-lg"><i class="fas fa-cube"></i> Explore Tools</a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/site-footer.inc.php'; ?>
