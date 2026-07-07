<?php
/**
 * OnlyOffice — Self-Hosted Office Suite | GoSiteMe
 */
$page_title = 'OnlyOffice — Self-Hosted Office Suite, Google Docs Alternative | GoSiteMe';
$page_description = 'Deploy OnlyOffice on your own server. Full office suite — docs, sheets, slides, PDF, forms — with real-time collaboration. Replaces Microsoft 365 and Google Workspace. Included with GoSiteMe hosting.';
$page_canonical = 'https://gositeme.com/open-source/office-suite.php';
$page_og_title = 'OnlyOffice — Self-Hosted Office Suite';
$page_og_description = $page_description;
$page_twitter_description = $page_og_description;
$page_og_image = 'https://gositeme.com/assets/hero-banner.png';
$page_robots = 'index, follow';
$preload_hero = false;
require_once __DIR__ . '/../includes/site-header.inc.php';
?>
<style>
.tp-hero{padding:100px 0 60px;text-align:center;position:relative;overflow:hidden;background:linear-gradient(180deg,rgba(125,0,255,.1) 0%,transparent 60%)}
.tp-hero::before{content:'';position:absolute;top:-200px;left:-200px;width:600px;height:600px;background:radial-gradient(circle,rgba(125,0,255,.2),transparent 70%);border-radius:50%}
.tp-hero .container{position:relative;z-index:2;max-width:1100px;margin:0 auto;padding:0 24px}
.tp-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(125,0,255,.12);border:1px solid rgba(125,0,255,.3);padding:6px 16px;border-radius:20px;font-size:.85rem;color:#a78bfa;margin-bottom:20px;font-weight:600}
.tp-hero h1{font-family:'Space Grotesk',sans-serif;font-size:clamp(2rem,5vw,3rem);font-weight:800;color:#fff;margin:0 0 16px;line-height:1.15}
.tp-hero h1 span{color:#a78bfa}
.tp-hero p.sub{color:#a0a0b8;font-size:1.1rem;max-width:700px;margin:0 auto 30px;line-height:1.7}
.tp-hero-btns{display:flex;justify-content:center;gap:16px;flex-wrap:wrap}
.tp-btn{display:inline-flex;align-items:center;gap:8px;padding:12px 28px;font-size:.9rem;font-weight:600;border-radius:8px;text-decoration:none;transition:all .3s;border:none;cursor:pointer;font-family:'Inter',sans-serif}
.tp-btn-primary{background:#7D00FF;color:#fff}
.tp-btn-primary:hover{background:#9b30ff;transform:translateY(-2px);box-shadow:0 10px 40px rgba(125,0,255,.3)}
.tp-btn-ghost{background:rgba(255,255,255,.05);color:#fff;border:1px solid rgba(255,255,255,.15)}
.tp-btn-ghost:hover{background:rgba(255,255,255,.1)}
.tp-section{padding:80px 24px}
.tp-section .container{max-width:1100px;margin:0 auto}
.tp-section-alt{background:rgba(18,18,42,.4)}
.tp-title{font-family:'Space Grotesk',sans-serif;font-size:1.8rem;font-weight:700;color:#fff;text-align:center;margin-bottom:12px}
.tp-subtitle{color:#a0a0b8;text-align:center;margin-bottom:50px;font-size:1rem;max-width:600px;margin-left:auto;margin-right:auto}
.tp-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:24px}
.tp-card{background:rgba(18,18,42,.8);border:1px solid rgba(30,30,62,.8);border-radius:12px;padding:30px;transition:all .3s}
.tp-card:hover{border-color:rgba(125,0,255,.3);transform:translateY(-3px)}
.tp-card .icon{font-size:2rem;margin-bottom:16px}
.tp-card h3{font-family:'Space Grotesk',sans-serif;font-size:1.1rem;font-weight:600;color:#fff;margin-bottom:10px}
.tp-card p{color:#a0a0b8;font-size:.9rem;line-height:1.6}
.tp-apps{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:20px;margin-top:40px}
.tp-app{background:rgba(18,18,42,.8);border:1px solid rgba(30,30,62,.8);border-radius:12px;padding:24px;text-align:center;transition:all .3s}
.tp-app:hover{border-color:rgba(125,0,255,.3);transform:translateY(-3px)}
.tp-app .emoji{font-size:2.5rem;margin-bottom:12px}
.tp-app h4{font-family:'Space Grotesk',sans-serif;color:#fff;font-size:1rem;margin-bottom:6px}
.tp-app p{color:#a0a0b8;font-size:.8rem;line-height:1.5}
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
.tp-step::before{counter-increment:step;content:counter(step);font-family:'Space Grotesk',sans-serif;font-size:1.5rem;font-weight:700;color:#a78bfa;background:rgba(125,0,255,.1);width:50px;height:50px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.tp-step-content h3{font-family:'Space Grotesk',sans-serif;font-size:1.1rem;font-weight:600;color:#fff;margin-bottom:6px}
.tp-step-content p{color:#a0a0b8;font-size:.9rem;line-height:1.6}
.tp-cta{padding:80px 24px;text-align:center}
.tp-cta .container{max-width:800px;margin:0 auto}
.tp-cta h2{font-family:'Space Grotesk',sans-serif;font-size:2rem;font-weight:700;color:#fff;margin-bottom:16px}
.tp-cta h2 span{color:#a78bfa}
.tp-cta p{color:#a0a0b8;margin-bottom:30px;line-height:1.7}
.tp-cta-btns{display:flex;justify-content:center;gap:16px;flex-wrap:wrap}
@media(max-width:768px){.tp-vs{grid-template-columns:1fr}.tp-apps{grid-template-columns:1fr 1fr}}
</style>

<section class="tp-hero">
    <div class="container">
        <div class="tp-badge"><i class="fas fa-file-word"></i> Office Suite</div>
        <h1>Your Own <span>Google Workspace</span><br>On Your Server</h1>
        <p class="sub">OnlyOffice delivers a full office suite — documents, spreadsheets, presentations, PDF editing, and forms — with real-time collaboration. 100% compatible with Microsoft Office files.</p>
        <div class="tp-hero-btns">
            <a href="/store/ai-domain-hosting-connected-with-ai-editor" class="tp-btn tp-btn-primary"><i class="fas fa-rocket"></i> Deploy OnlyOffice</a>
            <a href="https://github.com/ONLYOFFICE/DocumentServer" target="_blank" rel="noopener" class="tp-btn tp-btn-ghost"><i class="fab fa-github"></i> View on GitHub</a>
        </div>
    </div>
</section>

<section class="tp-section">
    <div class="container">
        <h2 class="tp-title">Office Apps Included</h2>
        <p class="tp-subtitle">Every tool your team needs, running in the browser.</p>
        <div class="tp-apps">
            <div class="tp-app"><div class="emoji">📝</div><h4>Documents</h4><p>Rich text editing with headers, tables, images, charts, tracked changes, and mail merge.</p></div>
            <div class="tp-app"><div class="emoji">📊</div><h4>Spreadsheets</h4><p>400+ functions, pivot tables, conditional formatting, macros, and charts.</p></div>
            <div class="tp-app"><div class="emoji">📽️</div><h4>Presentations</h4><p>Slide master layouts, transitions, animations, presenter mode, and media embedding.</p></div>
            <div class="tp-app"><div class="emoji">📋</div><h4>Forms</h4><p>Fillable forms with text fields, dropdowns, checkboxes, and digital signatures.</p></div>
            <div class="tp-app"><div class="emoji">📄</div><h4>PDF Editor</h4><p>View, annotate, fill, sign, and convert PDFs directly in the browser.</p></div>
        </div>
    </div>
</section>

<section class="tp-section tp-section-alt">
    <div class="container">
        <h2 class="tp-title">Key Features</h2>
        <p class="tp-subtitle">Enterprise-grade collaboration without the enterprise price tag.</p>
        <div class="tp-grid">
            <div class="tp-card"><div class="icon">👥</div><h3>Real-Time Co-Editing</h3><p>Multiple users edit the same document simultaneously. See cursors, selections, and changes in real time. WOPI protocol support.</p></div>
            <div class="tp-card"><div class="icon">📁</div><h3>100% MS Office Compatible</h3><p>Open and save .docx, .xlsx, .pptx natively. No conversion artifacts — pixel-perfect rendering of complex documents.</p></div>
            <div class="tp-card"><div class="icon">💬</div><h3>Comments & Track Changes</h3><p>Threaded comments, suggested edits, version comparison, and revision history. Enterprise review workflows built in.</p></div>
            <div class="tp-card"><div class="icon">🔌</div><h3>Plugin System</h3><p>Extend with plugins: AI writing assistant, YouTube embedding, Zotero citations, code highlighting, and more.</p></div>
            <div class="tp-card"><div class="icon">🔗</div><h3>Integrations</h3><p>Connects with Nextcloud, ownCloud, Seafile, Moodle, Alfresco, SharePoint, and any WOPI-compatible platform.</p></div>
            <div class="tp-card"><div class="icon">🌐</div><h3>30+ Languages</h3><p>Full UI localization including RTL support. Spell checking in 30+ languages with custom dictionaries.</p></div>
        </div>
    </div>
</section>

<section class="tp-section">
    <div class="container">
        <h2 class="tp-title">Microsoft 365 vs. OnlyOffice</h2>
        <p class="tp-subtitle">Why businesses are ditching per-seat SaaS pricing.</p>
        <div class="tp-vs">
            <div class="tp-vs-col them">
                <h3>Microsoft 365 Business</h3>
                <ul>
                    <li>$12.50/user/month ($1,500/yr for 10)</li>
                    <li>Data stored on Microsoft servers</li>
                    <li>Requires internet connection</li>
                    <li>Limited customization</li>
                    <li>Vendor lock-in with proprietary formats</li>
                    <li>No self-hosting option</li>
                </ul>
            </div>
            <div class="tp-vs-col us">
                <h3>OnlyOffice + GoSiteMe</h3>
                <ul>
                    <li>$0 extra — included with hosting</li>
                    <li>Data on YOUR server, full ownership</li>
                    <li>Works offline on LAN</li>
                    <li>Full source access, plugin system</li>
                    <li>Native .docx/.xlsx/.pptx support</li>
                    <li>Open source — no lock-in ever</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="tp-section tp-section-alt">
    <div class="container">
        <h2 class="tp-title">How It Works</h2>
        <p class="tp-subtitle">From zero to office suite in three steps.</p>
        <div class="tp-steps">
            <div class="tp-step"><div class="tp-step-content"><h3>Get a GoSiteMe Server</h3><p>Choose any hosting plan. OnlyOffice Document Server is pre-configured in Docker and ready to deploy.</p></div></div>
            <div class="tp-step"><div class="tp-step-content"><h3>Deploy with One Click</h3><p>Launch the OnlyOffice container from your dashboard. Auto-configured with SSL, fonts, and spell checking.</p></div></div>
            <div class="tp-step"><div class="tp-step-content"><h3>Start Collaborating</h3><p>Access documents from any browser. Share links, co-edit in real time, and manage files through the built-in portal.</p></div></div>
        </div>
    </div>
</section>

<section class="tp-cta">
    <div class="container">
        <h2>Your Office Suite, <span>Your Server</span></h2>
        <p>Stop paying per seat. Deploy OnlyOffice on your GoSiteMe server and give your entire team professional office tools — free.</p>
        <div class="tp-cta-btns">
            <a href="/store/ai-domain-hosting-connected-with-ai-editor" class="tp-btn tp-btn-primary"><i class="fas fa-rocket"></i> Get Hosting + OnlyOffice</a>
            <a href="/open-source/" class="tp-btn tp-btn-ghost"><i class="fas fa-arrow-left"></i> All Tools</a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/site-footer.inc.php'; ?>
