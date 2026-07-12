<?php
/**
 * OpenCut — Self-Hosted Video Editor | GoSiteMe
 */
$page_title = 'OpenCut — Open-Source Video Editor, CapCut Alternative | GoSiteMe';
$page_description = 'Deploy OpenCut, the open-source CapCut alternative. Timeline-based video editing in the browser — no watermarks, no subscriptions, privacy-first. 46k+ GitHub stars. Included with GoSiteMe hosting.';
$page_canonical = 'https://gositeme.com/open-source/video-editor.php';
$page_og_title = 'OpenCut — Open-Source Video Editor';
$page_og_description = $page_description;
$page_twitter_description = $page_og_description;
$page_og_image = 'https://gositeme.com/assets/hero-banner.png';
$page_robots = 'index, follow';
$preload_hero = false;
require_once __DIR__ . '/../includes/site-header.inc.php';
?>
<style>
.tp-hero{padding:100px 0 60px;text-align:center;position:relative;overflow:hidden;background:linear-gradient(180deg,rgba(255,107,53,.08) 0%,transparent 60%)}
.tp-hero::before{content:'';position:absolute;top:-200px;right:-100px;width:600px;height:600px;background:radial-gradient(circle,rgba(255,107,53,.18),transparent 70%);border-radius:50%}
.tp-hero::after{content:'';position:absolute;bottom:-200px;left:-100px;width:500px;height:500px;background:radial-gradient(circle,rgba(255,165,0,.1),transparent 70%);border-radius:50%}
.tp-hero .container{position:relative;z-index:2;max-width:1100px;margin:0 auto;padding:0 24px}
.tp-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(255,107,53,.12);border:1px solid rgba(255,107,53,.3);padding:6px 16px;border-radius:20px;font-size:.85rem;color:#ff6b35;margin-bottom:20px;font-weight:600}
.tp-hero h1{font-family:'Space Grotesk',sans-serif;font-size:clamp(2rem,5vw,3rem);font-weight:800;color:#fff;margin:0 0 16px;line-height:1.15}
.tp-hero h1 span{background:linear-gradient(135deg,#ff6b35,#ffa500);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.tp-hero p.sub{color:#a0a0b8;font-size:1.1rem;max-width:700px;margin:0 auto 30px;line-height:1.7}
.tp-hero-btns{display:flex;justify-content:center;gap:16px;flex-wrap:wrap}
.tp-btn{display:inline-flex;align-items:center;gap:8px;padding:12px 28px;font-size:.9rem;font-weight:600;border-radius:8px;text-decoration:none;transition:all .3s;border:none;cursor:pointer;font-family:'Inter',sans-serif}
.tp-btn-primary{background:linear-gradient(135deg,#ff6b35,#ff8c00);color:#fff}
.tp-btn-primary:hover{transform:translateY(-2px);box-shadow:0 10px 40px rgba(255,107,53,.3)}
.tp-btn-ghost{background:rgba(255,255,255,.05);color:#fff;border:1px solid rgba(255,255,255,.15)}
.tp-btn-ghost:hover{background:rgba(255,255,255,.1)}
.tp-section{padding:80px 24px}
.tp-section .container{max-width:1100px;margin:0 auto}
.tp-section-alt{background:rgba(18,18,42,.4)}
.tp-title{font-family:'Space Grotesk',sans-serif;font-size:1.8rem;font-weight:700;color:#fff;text-align:center;margin-bottom:12px}
.tp-subtitle{color:#a0a0b8;text-align:center;margin-bottom:50px;font-size:1rem;max-width:600px;margin-left:auto;margin-right:auto}
.tp-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:24px}
.tp-card{background:rgba(18,18,42,.8);border:1px solid rgba(30,30,62,.8);border-radius:12px;padding:30px;transition:all .3s}
.tp-card:hover{border-color:rgba(255,107,53,.3);transform:translateY(-3px)}
.tp-card .icon{font-size:2rem;margin-bottom:16px}
.tp-card h3{font-family:'Space Grotesk',sans-serif;font-size:1.1rem;font-weight:600;color:#fff;margin-bottom:10px}
.tp-card p{color:#a0a0b8;font-size:.9rem;line-height:1.6}
.tp-highlight{display:grid;grid-template-columns:1fr 1fr;gap:40px;align-items:center;margin-top:40px}
.tp-highlight-text h3{font-family:'Space Grotesk',sans-serif;font-size:1.5rem;font-weight:700;color:#fff;margin-bottom:16px}
.tp-highlight-text p{color:#a0a0b8;line-height:1.7;margin-bottom:16px}
.tp-highlight-visual{background:rgba(18,18,42,.8);border:1px solid rgba(30,30,62,.8);border-radius:16px;padding:30px;text-align:center}
.tp-highlight-visual .big-emoji{font-size:5rem;margin-bottom:16px}
.tp-highlight-visual .caption{color:#a0a0b8;font-size:.85rem}
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
.tp-step::before{counter-increment:step;content:counter(step);font-family:'Space Grotesk',sans-serif;font-size:1.5rem;font-weight:700;color:#ff6b35;background:rgba(255,107,53,.1);width:50px;height:50px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.tp-step-content h3{font-family:'Space Grotesk',sans-serif;font-size:1.1rem;font-weight:600;color:#fff;margin-bottom:6px}
.tp-step-content p{color:#a0a0b8;font-size:.9rem;line-height:1.6}
.tp-specs{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-top:40px}
.tp-spec{background:rgba(18,18,42,.8);border:1px solid rgba(30,30,62,.8);border-radius:12px;padding:20px;text-align:center}
.tp-spec .val{font-family:'Space Grotesk',sans-serif;font-size:1.5rem;font-weight:700;color:#ff6b35}
.tp-spec .lbl{color:#a0a0b8;font-size:.8rem;margin-top:4px}
.tp-cta{padding:80px 24px;text-align:center}
.tp-cta .container{max-width:800px;margin:0 auto}
.tp-cta h2{font-family:'Space Grotesk',sans-serif;font-size:2rem;font-weight:700;color:#fff;margin-bottom:16px}
.tp-cta h2 span{background:linear-gradient(135deg,#ff6b35,#ffa500);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.tp-cta p{color:#a0a0b8;margin-bottom:30px;line-height:1.7}
.tp-cta-btns{display:flex;justify-content:center;gap:16px;flex-wrap:wrap}
@media(max-width:768px){.tp-vs{grid-template-columns:1fr}.tp-highlight{grid-template-columns:1fr}.tp-specs{grid-template-columns:1fr 1fr}}
</style>

<section class="tp-hero">
    <div class="container">
        <div class="tp-badge"><i class="fas fa-film"></i> Video Editor</div>
        <h1>The Open-Source <span>CapCut</span><br>For Your Business</h1>
        <p class="sub">OpenCut is the privacy-first video editor with 46,000+ GitHub stars. Timeline-based editing in the browser — no watermarks, no subscriptions, no data leaves your device. Self-host it on GoSiteMe.</p>
        <div class="tp-hero-btns">
            <a href="/store/ai-domain-hosting-connected-with-ai-editor" class="tp-btn tp-btn-primary"><i class="fas fa-rocket"></i> Deploy OpenCut</a>
            <a href="https://github.com/OpenCut-app/OpenCut" target="_blank" rel="noopener" class="tp-btn tp-btn-ghost"><i class="fab fa-github"></i> View on GitHub</a>
        </div>
    </div>
</section>

<section class="tp-section">
    <div class="container">
        <h2 class="tp-title">Features</h2>
        <p class="tp-subtitle">Professional video editing without the professional price tag.</p>
        <div class="tp-grid">
            <div class="tp-card"><div class="icon">🎬</div><h3>Timeline Editor</h3><p>Drag and drop clips to a multi-track timeline. Cut, trim, split, and arrange with precision. Undo/redo support.</p></div>
            <div class="tp-card"><div class="icon">🎨</div><h3>WebGL Effects</h3><p>GPU-accelerated visual effects including blur, color grading, transitions, and custom shaders — all rendered in real time.</p></div>
            <div class="tp-card"><div class="icon">👁️</div><h3>Real-Time Preview</h3><p>See changes instantly in the preview panel. No waiting for renders — what you see is what you export.</p></div>
            <div class="tp-card"><div class="icon">🔤</div><h3>Text & Fonts</h3><p>Add titles, captions, and lower thirds. Custom fonts, animations, and positioning — all from the browser.</p></div>
            <div class="tp-card"><div class="icon">🎵</div><h3>Audio Tracks</h3><p>Multi-track audio editing with waveform visualization. Adjust volume, add music, and sync sound effects.</p></div>
            <div class="tp-card"><div class="icon">📱</div><h3>Works Everywhere</h3><p>Web, desktop, and mobile support. Edit on any device with a modern browser — no installs required.</p></div>
        </div>
    </div>
</section>

<section class="tp-section tp-section-alt">
    <div class="container">
        <h2 class="tp-title">Why OpenCut?</h2>
        <div class="tp-highlight">
            <div class="tp-highlight-text">
                <h3>Privacy First</h3>
                <p>Your videos never leave your device. OpenCut processes everything locally — no uploads, no cloud rendering, no data collection.</p>
                <p>CapCut and other commercial editors upload your content to their servers. OpenCut keeps everything on your machine, or self-hosted on your GoSiteMe server.</p>
            </div>
            <div class="tp-highlight-visual">
                <div class="big-emoji">🔒</div>
                <div class="caption">Zero data sent to third parties.<br>Your content stays yours.</div>
            </div>
        </div>
    </div>
</section>

<section class="tp-section">
    <div class="container">
        <h2 class="tp-title">CapCut vs. OpenCut</h2>
        <p class="tp-subtitle">See why 46k+ developers chose OpenCut.</p>
        <div class="tp-vs">
            <div class="tp-vs-col them">
                <h3>CapCut Pro</h3>
                <ul>
                    <li>$7.99/month subscription</li>
                    <li>Watermarks on free exports</li>
                    <li>Videos uploaded to ByteDance servers</li>
                    <li>Paywalled features (background removal, etc.)</li>
                    <li>Proprietary — no source code access</li>
                    <li>Cannot self-host</li>
                </ul>
            </div>
            <div class="tp-vs-col us">
                <h3>OpenCut + GoSiteMe</h3>
                <ul>
                    <li>$0 — free and open source (MIT)</li>
                    <li>No watermarks, ever</li>
                    <li>Videos stay on your device/server</li>
                    <li>All features free, no paywalls</li>
                    <li>Full source code, contribute & extend</li>
                    <li>Self-host with Docker in minutes</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="tp-section tp-section-alt">
    <div class="container">
        <h2 class="tp-title">How It Works</h2>
        <p class="tp-subtitle">From zero to video editor in three steps.</p>
        <div class="tp-steps">
            <div class="tp-step"><div class="tp-step-content"><h3>Get a GoSiteMe Server</h3><p>Choose any hosting plan. OpenCut runs as a Next.js app in Docker, pre-configured and ready to deploy.</p></div></div>
            <div class="tp-step"><div class="tp-step-content"><h3>Deploy with One Click</h3><p>Launch OpenCut from your dashboard. <code>docker compose up -d</code> — that's it. SSL and domain auto-configured.</p></div></div>
            <div class="tp-step"><div class="tp-step-content"><h3>Edit & Export</h3><p>Open your browser, drop in video files, edit on the timeline, and export — all without leaving your network.</p></div></div>
        </div>
        <div class="tp-specs">
            <div class="tp-spec"><div class="val">46k+</div><div class="lbl">GitHub Stars</div></div>
            <div class="tp-spec"><div class="val">MIT</div><div class="lbl">License</div></div>
            <div class="tp-spec"><div class="val">92+</div><div class="lbl">Contributors</div></div>
            <div class="tp-spec"><div class="val">v0.2</div><div class="lbl">Latest Release</div></div>
        </div>
    </div>
</section>

<section class="tp-cta">
    <div class="container">
        <h2>Edit Videos <span>Your Way</span></h2>
        <p>No subscriptions. No watermarks. No data leaving your device. Just powerful video editing — self-hosted on your GoSiteMe server.</p>
        <div class="tp-cta-btns">
            <a href="/store/ai-domain-hosting-connected-with-ai-editor" class="tp-btn tp-btn-primary"><i class="fas fa-rocket"></i> Get Hosting + OpenCut</a>
            <a href="/open-source/" class="tp-btn tp-btn-ghost"><i class="fas fa-arrow-left"></i> All Tools</a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/site-footer.inc.php'; ?>
