<?php
/**
 * Gitea — Self-Hosted Git Platform | GoSiteMe
 */
$page_title = 'Gitea — Self-Hosted Git Platform, GitHub Alternative | GoSiteMe';
$page_description = 'Deploy Gitea on your own server. Lightweight GitHub alternative with repos, issues, PRs, CI/CD, container registry — all in a single binary. MIT licensed. Included with GoSiteMe hosting.';
$page_canonical = 'https://gositeme.com/open-source/git-platform.php';
$page_og_title = 'Gitea — Self-Hosted Git Platform';
$page_og_description = $page_description;
$page_twitter_description = $page_og_description;
$page_og_image = 'https://gositeme.com/assets/hero-banner.png';
$page_robots = 'index, follow';
$preload_hero = false;
require_once __DIR__ . '/../includes/site-header.inc.php';
?>
<style>
.tp-hero{padding:100px 0 60px;text-align:center;position:relative;overflow:hidden;background:linear-gradient(180deg,rgba(255,214,0,.06) 0%,transparent 60%)}
.tp-hero::before{content:'';position:absolute;top:-200px;right:-200px;width:600px;height:600px;background:radial-gradient(circle,rgba(255,214,0,.15),transparent 70%);border-radius:50%}
.tp-hero .container{position:relative;z-index:2;max-width:1100px;margin:0 auto;padding:0 24px}
.tp-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(255,214,0,.12);border:1px solid rgba(255,214,0,.3);padding:6px 16px;border-radius:20px;font-size:.85rem;color:#ffd600;margin-bottom:20px;font-weight:600}
.tp-hero h1{font-family:'Space Grotesk',sans-serif;font-size:clamp(2rem,5vw,3rem);font-weight:800;color:#fff;margin:0 0 16px;line-height:1.15}
.tp-hero h1 span{color:#ffd600}
.tp-hero p.sub{color:#a0a0b8;font-size:1.1rem;max-width:700px;margin:0 auto 30px;line-height:1.7}
.tp-hero-btns{display:flex;justify-content:center;gap:16px;flex-wrap:wrap}
.tp-btn{display:inline-flex;align-items:center;gap:8px;padding:12px 28px;font-size:.9rem;font-weight:600;border-radius:8px;text-decoration:none;transition:all .3s;border:none;cursor:pointer;font-family:'Inter',sans-serif}
.tp-btn-primary{background:#ffd600;color:#000;font-weight:700}
.tp-btn-primary:hover{background:#ffe233;transform:translateY(-2px);box-shadow:0 10px 40px rgba(255,214,0,.3)}
.tp-btn-ghost{background:rgba(255,255,255,.05);color:#fff;border:1px solid rgba(255,255,255,.15)}
.tp-btn-ghost:hover{background:rgba(255,255,255,.1)}
.tp-section{padding:80px 24px}
.tp-section .container{max-width:1100px;margin:0 auto}
.tp-section-alt{background:rgba(18,18,42,.4)}
.tp-title{font-family:'Space Grotesk',sans-serif;font-size:1.8rem;font-weight:700;color:#fff;text-align:center;margin-bottom:12px}
.tp-subtitle{color:#a0a0b8;text-align:center;margin-bottom:50px;font-size:1rem;max-width:600px;margin-left:auto;margin-right:auto}
.tp-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:24px}
.tp-card{background:rgba(18,18,42,.8);border:1px solid rgba(30,30,62,.8);border-radius:12px;padding:30px;transition:all .3s}
.tp-card:hover{border-color:rgba(255,214,0,.3);transform:translateY(-3px)}
.tp-card .icon{font-size:2rem;margin-bottom:16px}
.tp-card h3{font-family:'Space Grotesk',sans-serif;font-size:1.1rem;font-weight:600;color:#fff;margin-bottom:10px}
.tp-card p{color:#a0a0b8;font-size:.9rem;line-height:1.6}
.tp-perf{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-top:40px}
.tp-perf-item{background:rgba(18,18,42,.8);border:1px solid rgba(30,30,62,.8);border-radius:12px;padding:24px;text-align:center}
.tp-perf-item .val{font-family:'Space Grotesk',sans-serif;font-size:1.8rem;font-weight:700;color:#ffd600}
.tp-perf-item .lbl{color:#a0a0b8;font-size:.8rem;margin-top:6px}
.tp-vs{display:grid;grid-template-columns:1fr 1fr;gap:30px;margin-top:40px}
.tp-vs-col{background:rgba(18,18,42,.8);border:1px solid rgba(30,30,62,.8);border-radius:12px;padding:30px}
.tp-vs-col h3{font-family:'Space Grotesk',sans-serif;font-size:1.2rem;font-weight:700;margin-bottom:20px}
.tp-vs-col.them h3{color:#ff4757}
.tp-vs-col.us h3{color:#00d4aa}
.tp-vs-col ul{list-style:none;padding:0}
.tp-vs-col ul li{padding:8px 0;font-size:.9rem;color:#c0c0d0;display:flex;align-items:center;gap:10px;border-bottom:1px solid rgba(30,30,62,.5)}
.tp-vs-col.them li::before{content:'✕';color:#ff4757;font-weight:700}
.tp-vs-col.us li::before{content:'✓';color:#00d4aa;font-weight:700}
.tp-migrate{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px;margin-top:40px}
.tp-migrate-item{background:rgba(18,18,42,.8);border:1px solid rgba(30,30,62,.8);border-radius:12px;padding:20px;text-align:center;transition:all .3s}
.tp-migrate-item:hover{border-color:rgba(255,214,0,.3)}
.tp-migrate-item .emoji{font-size:2rem;margin-bottom:8px}
.tp-migrate-item h4{color:#fff;font-size:.9rem;font-family:'Space Grotesk',sans-serif}
.tp-migrate-item p{color:#a0a0b8;font-size:.75rem;margin-top:4px}
.tp-steps{counter-reset:step}
.tp-step{display:flex;gap:24px;align-items:flex-start;margin-bottom:30px}
.tp-step::before{counter-increment:step;content:counter(step);font-family:'Space Grotesk',sans-serif;font-size:1.5rem;font-weight:700;color:#ffd600;background:rgba(255,214,0,.1);width:50px;height:50px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.tp-step-content h3{font-family:'Space Grotesk',sans-serif;font-size:1.1rem;font-weight:600;color:#fff;margin-bottom:6px}
.tp-step-content p{color:#a0a0b8;font-size:.9rem;line-height:1.6}
.tp-cta{padding:80px 24px;text-align:center}
.tp-cta .container{max-width:800px;margin:0 auto}
.tp-cta h2{font-family:'Space Grotesk',sans-serif;font-size:2rem;font-weight:700;color:#fff;margin-bottom:16px}
.tp-cta h2 span{color:#ffd600}
.tp-cta p{color:#a0a0b8;margin-bottom:30px;line-height:1.7}
.tp-cta-btns{display:flex;justify-content:center;gap:16px;flex-wrap:wrap}
@media(max-width:768px){.tp-vs{grid-template-columns:1fr}.tp-perf{grid-template-columns:1fr 1fr}.tp-migrate{grid-template-columns:1fr 1fr}}
</style>

<section class="tp-hero">
    <div class="container">
        <div class="tp-badge"><i class="fas fa-code-branch"></i> Git Platform</div>
        <h1>Your Own <span>GitHub</span><br>In a Single Binary</h1>
        <p class="sub">Gitea is a lightweight, self-hosted Git platform with 46k+ stars. Repos, issues, PRs, CI/CD, container registry, packages — all in under 100MB. MIT licensed.</p>
        <div class="tp-hero-btns">
            <a href="/store/ai-domain-hosting-connected-with-ai-editor" class="tp-btn tp-btn-primary"><i class="fas fa-rocket"></i> Deploy Gitea</a>
            <a href="https://github.com/go-gitea/gitea" target="_blank" rel="noopener" class="tp-btn tp-btn-ghost"><i class="fab fa-github"></i> View on GitHub</a>
        </div>
    </div>
</section>

<section class="tp-section">
    <div class="container">
        <h2 class="tp-title">Features</h2>
        <p class="tp-subtitle">Everything you expect from GitHub, on your infrastructure.</p>
        <div class="tp-grid">
            <div class="tp-card"><div class="icon">📦</div><h3>Git Repositories</h3><p>Unlimited repos with web UI. Branches, tags, protected branches, webhooks, deploy keys. LFS for large files.</p></div>
            <div class="tp-card"><div class="icon">🔀</div><h3>Pull Requests</h3><p>Code review with inline comments, approvals, merge strategies (squash, rebase, merge commit). Status checks and CODEOWNERS.</p></div>
            <div class="tp-card"><div class="icon">📋</div><h3>Issues & Boards</h3><p>Issue tracker with labels, milestones, assignees. Kanban boards for project management. Time tracking built in.</p></div>
            <div class="tp-card"><div class="icon">⚙️</div><h3>Gitea Actions (CI/CD)</h3><p>GitHub Actions-compatible CI/CD. Run workflows on push, PR, or schedule. Docker and native runners supported.</p></div>
            <div class="tp-card"><div class="icon">📦</div><h3>Package Registry</h3><p>Host npm, PyPI, Maven, NuGet, Go, Cargo, Docker, and Helm packages. Built-in container registry.</p></div>
            <div class="tp-card"><div class="icon">📖</div><h3>Wiki & Docs</h3><p>Git-backed wikis for each repository. Markdown rendering with Mermaid diagrams, math, and syntax highlighting.</p></div>
        </div>
    </div>
</section>

<section class="tp-section tp-section-alt">
    <div class="container">
        <h2 class="tp-title">Blazing Fast & Lightweight</h2>
        <p class="tp-subtitle">Gitea runs on minimal resources — perfect for small teams and large enterprises alike.</p>
        <div class="tp-perf">
            <div class="tp-perf-item"><div class="val">&lt;100MB</div><div class="lbl">Binary Size</div></div>
            <div class="tp-perf-item"><div class="val">&lt;256MB</div><div class="lbl">RAM Usage</div></div>
            <div class="tp-perf-item"><div class="val">46k+</div><div class="lbl">GitHub Stars</div></div>
            <div class="tp-perf-item"><div class="val">MIT</div><div class="lbl">License</div></div>
        </div>
    </div>
</section>

<section class="tp-section">
    <div class="container">
        <h2 class="tp-title">Migrate From Anywhere</h2>
        <p class="tp-subtitle">One-click migration from your current platform — repos, issues, PRs, and wikis.</p>
        <div class="tp-migrate">
            <div class="tp-migrate-item"><div class="emoji">🐙</div><h4>GitHub</h4><p>Full migration with issues & PRs</p></div>
            <div class="tp-migrate-item"><div class="emoji">🦊</div><h4>GitLab</h4><p>Repos, issues, milestones</p></div>
            <div class="tp-migrate-item"><div class="emoji">🪣</div><h4>Bitbucket</h4><p>Repos and wikis</p></div>
            <div class="tp-migrate-item"><div class="emoji">🔷</div><h4>Azure DevOps</h4><p>Git repos</p></div>
            <div class="tp-migrate-item"><div class="emoji">📁</div><h4>Gogs</h4><p>Direct database migration</p></div>
        </div>
    </div>
</section>

<section class="tp-section tp-section-alt">
    <div class="container">
        <h2 class="tp-title">GitHub Team vs. Gitea</h2>
        <p class="tp-subtitle">Keep your code without keeping the subscription.</p>
        <div class="tp-vs">
            <div class="tp-vs-col them">
                <h3>GitHub Team</h3>
                <ul>
                    <li>$4/user/month ($480/yr for 10)</li>
                    <li>Code hosted on GitHub servers</li>
                    <li>GitHub controls access & policies</li>
                    <li>Limited Actions minutes on free tier</li>
                    <li>Proprietary — no source access</li>
                    <li>US jurisdiction (CLOUD Act)</li>
                </ul>
            </div>
            <div class="tp-vs-col us">
                <h3>Gitea + GoSiteMe</h3>
                <ul>
                    <li>$0 extra — included with hosting</li>
                    <li>Code on YOUR server</li>
                    <li>You set the rules</li>
                    <li>Unlimited CI/CD on your hardware</li>
                    <li>MIT license — full transparency</li>
                    <li>Your jurisdiction, your compliance</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="tp-section">
    <div class="container">
        <h2 class="tp-title">How It Works</h2>
        <p class="tp-subtitle">From zero to Git platform in three steps.</p>
        <div class="tp-steps">
            <div class="tp-step"><div class="tp-step-content"><h3>Get a GoSiteMe Server</h3><p>Choose any hosting plan. Gitea is a single Go binary — deploys instantly with Docker or standalone.</p></div></div>
            <div class="tp-step"><div class="tp-step-content"><h3>Deploy with One Click</h3><p>Launch Gitea from your dashboard. Auto-configured with PostgreSQL, SSL, SSH keys, and your custom domain.</p></div></div>
            <div class="tp-step"><div class="tp-step-content"><h3>Push Your Code</h3><p>Create repos, invite collaborators, set up Actions workflows, and start shipping. Migrate existing repos with the built-in importer.</p></div></div>
        </div>
    </div>
</section>

<section class="tp-cta">
    <div class="container">
        <h2>Own Your <span>Code Platform</span></h2>
        <p>No per-seat fees. No vendor lock-in. No code on someone else's server. Deploy Gitea on your GoSiteMe server and take back control.</p>
        <div class="tp-cta-btns">
            <a href="/store/ai-domain-hosting-connected-with-ai-editor" class="tp-btn tp-btn-primary"><i class="fas fa-rocket"></i> Get Hosting + Gitea</a>
            <a href="/open-source/" class="tp-btn tp-btn-ghost"><i class="fas fa-arrow-left"></i> All Tools</a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/site-footer.inc.php'; ?>
