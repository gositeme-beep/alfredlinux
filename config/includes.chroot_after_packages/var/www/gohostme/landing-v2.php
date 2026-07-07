<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GoHostMe — AI-Powered Cloud Hosting by GoSiteMe | 18 Global Regions</title>
<meta name="description" content="Enterprise cloud hosting powered by AI. VPS, dedicated servers, GPU instances across 18 global regions. GoHostMe control panel included. Canadian hydroelectric-powered infrastructure. By GoSiteMe.">
<meta name="keywords" content="cloud hosting, VPS, dedicated server, GPU hosting, AI hosting, Canadian hosting, GoSiteMe, GoHostMe">
<link rel="icon" href="/favicon.ico">
<meta property="og:title" content="GoHostMe — AI-Powered Cloud Hosting">
<meta property="og:description" content="Enterprise cloud hosting across 18 global regions with AI-native control panel. VPS from $29.99/mo.">
<meta property="og:type" content="website">
<meta property="og:url" content="https://gositeme.com/gohostme/">
<style>
:root {
    --bg: #0a0e1a;
    --surface: #111827;
    --surface2: #1a2236;
    --surface3: #222d42;
    --border: rgba(255,255,255,0.08);
    --text: #e4e8f0;
    --text-dim: #8892a6;
    --accent: #6366f1;
    --accent2: #818cf8;
    --accent-glow: rgba(99,102,241,0.25);
    --green: #10b981;
    --orange: #f59e0b;
    --red: #ef4444;
    --gold: #fbbf24;
    --cyan: #22d3ee;
}
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: var(--bg);
    color: var(--text);
    line-height: 1.6;
    overflow-x: hidden;
}
a { color: var(--accent2); text-decoration: none; }
a:hover { text-decoration: underline; }

/* NAV */
.nav {
    position: fixed; top: 0; left: 0; right: 0; z-index: 100;
    background: rgba(10,14,26,0.92);
    backdrop-filter: blur(16px);
    border-bottom: 1px solid var(--border);
    padding: 0 24px;
}
.nav-inner {
    max-width: 1200px; margin: 0 auto;
    display: flex; align-items: center; justify-content: space-between;
    height: 64px;
}
.nav-logo {
    display: flex; align-items: center; gap: 10px;
    font-size: 1.25rem; font-weight: 700; color: #fff;
}
.nav-logo svg { width: 32px; height: 32px; }
.nav-links { display: flex; gap: 24px; align-items: center; }
.nav-links a { color: var(--text-dim); font-size: 0.9rem; transition: color 0.2s; }
.nav-links a:hover { color: #fff; text-decoration: none; }
.btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 10px 22px; border-radius: 8px; font-weight: 600;
    font-size: 0.9rem; transition: all 0.2s; border: none; cursor: pointer;
}
.btn-primary {
    background: var(--accent); color: #fff;
    box-shadow: 0 0 20px var(--accent-glow);
}
.btn-primary:hover { background: #5558e8; text-decoration: none; transform: translateY(-1px); }
.btn-outline {
    background: transparent; color: var(--accent2);
    border: 1px solid var(--accent); padding: 9px 21px;
}
.btn-outline:hover { background: rgba(99,102,241,0.1); text-decoration: none; }
.btn-lg { padding: 14px 32px; font-size: 1rem; border-radius: 10px; }
.btn-gold { background: linear-gradient(135deg, #f59e0b, #d97706); color: #000; font-weight: 700; }
.btn-gold:hover { transform: translateY(-1px); text-decoration: none; }
.btn-cyan { background: linear-gradient(135deg, #06b6d4, #0891b2); color: #fff; }
.btn-cyan:hover { transform: translateY(-1px); text-decoration: none; }

/* HERO */
.hero {
    padding: 140px 24px 80px;
    text-align: center;
    position: relative;
    overflow: hidden;
}
.hero::before {
    content: '';
    position: absolute; top: -200px; left: 50%; transform: translateX(-50%);
    width: 900px; height: 900px;
    background: radial-gradient(circle, var(--accent-glow) 0%, rgba(34,211,238,0.08) 40%, transparent 70%);
    pointer-events: none;
}
.hero-badge {
    display: inline-flex; align-items: center; gap: 8px;
    background: var(--surface2); border: 1px solid var(--border);
    border-radius: 20px; padding: 6px 16px; font-size: 0.8rem;
    color: var(--green); margin-bottom: 24px;
}
.hero h1 {
    font-size: clamp(2.2rem, 5vw, 3.5rem);
    font-weight: 800; line-height: 1.15;
    max-width: 900px; margin: 0 auto 20px;
    background: linear-gradient(135deg, #fff 0%, var(--accent2) 50%, var(--cyan) 100%);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text;
}
.hero p {
    font-size: 1.15rem; color: var(--text-dim);
    max-width: 700px; margin: 0 auto 36px;
}
.hero-cta { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; }
.hero-stats {
    display: flex; gap: 36px; justify-content: center; margin-top: 60px;
    flex-wrap: wrap;
}
.hero-stat { text-align: center; }
.hero-stat .num { font-size: 2rem; font-weight: 800; color: #fff; }
.hero-stat .label { font-size: 0.78rem; color: var(--text-dim); margin-top: 4px; }

/* SECTIONS */
.section {
    padding: 80px 24px;
    max-width: 1200px; margin: 0 auto;
}
.section-label {
    text-transform: uppercase; font-size: 0.75rem; font-weight: 700;
    color: var(--accent2); letter-spacing: 2px; margin-bottom: 12px;
}
.section h2 {
    font-size: clamp(1.6rem, 3vw, 2.2rem); font-weight: 700;
    margin-bottom: 16px;
}
.section p.sub {
    color: var(--text-dim); max-width: 650px; margin-bottom: 48px;
}

/* INFRASTRUCTURE STATS */
.infra-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 16px; margin: 40px 0;
}
.infra-stat {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 20px 16px;
    text-align: center;
    transition: border-color 0.3s;
}
.infra-stat:hover { border-color: rgba(99,102,241,0.3); }
.infra-stat .num { font-size: 1.6rem; font-weight: 800; color: var(--cyan); }
.infra-stat .label { font-size: 0.72rem; color: var(--text-dim); margin-top: 6px; text-transform: uppercase; letter-spacing: 0.5px; }

/* FEATURES */
.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
}
.feature-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 28px;
    transition: border-color 0.3s, transform 0.2s;
}
.feature-card:hover {
    border-color: rgba(99,102,241,0.3);
    transform: translateY(-2px);
}
.feature-icon {
    width: 44px; height: 44px;
    background: rgba(99,102,241,0.12);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem; margin-bottom: 16px;
}
.feature-card h3 { font-size: 1.05rem; margin-bottom: 8px; }
.feature-card p { font-size: 0.88rem; color: var(--text-dim); line-height: 1.5; }

/* REGIONS */
.regions-section { padding: 80px 24px; max-width: 1200px; margin: 0 auto; }
.region-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 12px; margin-top: 32px;
}
.region-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 16px;
    text-align: center;
    transition: border-color 0.3s, transform 0.2s;
}
.region-card:hover { border-color: rgba(34,211,238,0.4); transform: translateY(-2px); }
.region-flag { font-size: 1.8rem; margin-bottom: 6px; }
.region-name { font-size: 0.82rem; font-weight: 600; color: #fff; }
.region-code { font-size: 0.7rem; color: var(--text-dim); margin-top: 2px; }

/* COMPARISON */
.comparison {
    padding: 80px 24px;
    max-width: 1000px; margin: 0 auto;
}
.comp-table {
    width: 100%;
    border-collapse: collapse;
    background: var(--surface);
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid var(--border);
}
.comp-table th, .comp-table td {
    padding: 14px 20px;
    text-align: left;
    border-bottom: 1px solid var(--border);
    font-size: 0.9rem;
}
.comp-table th {
    background: var(--surface2);
    font-weight: 600;
    color: var(--text-dim);
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.comp-table td:not(:first-child), .comp-table th:not(:first-child) {
    text-align: center;
}
.comp-table tr:last-child td { border-bottom: none; }
.check { color: var(--green); font-weight: 700; }
.cross { color: var(--text-dim); opacity: 0.4; }
.highlight-col { background: rgba(99,102,241,0.06); }

/* PRICING */
.pricing-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 20px;
    max-width: 1100px;
    margin: 0 auto;
}
.price-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 32px 28px;
    text-align: center;
    position: relative;
}
.price-card.popular {
    border-color: var(--accent);
    box-shadow: 0 0 30px var(--accent-glow);
}
.price-card.popular::before {
    content: 'Most Popular';
    position: absolute; top: -12px; left: 50%; transform: translateX(-50%);
    background: var(--accent); color: #fff;
    font-size: 0.7rem; font-weight: 700; text-transform: uppercase;
    padding: 4px 14px; border-radius: 10px; letter-spacing: 1px;
}
.price-card .tier { font-size: 0.85rem; color: var(--accent2); font-weight: 600; margin-bottom: 4px; }
.price-card .price { font-size: 2.4rem; font-weight: 800; color: #fff; margin-bottom: 4px; }
.price-card .period { font-size: 0.8rem; color: var(--text-dim); margin-bottom: 20px; }
.price-card ul { list-style: none; text-align: left; margin-bottom: 24px; }
.price-card li {
    font-size: 0.85rem; color: var(--text-dim);
    padding: 6px 0; display: flex; align-items: center; gap: 8px;
}
.price-card li::before {
    content: '✓'; color: var(--green); font-weight: 700;
    flex-shrink: 0;
}

/* DEDICATED */
.dedicated-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    max-width: 1200px; margin: 0 auto;
}
.dedicated-card {
    background: linear-gradient(135deg, var(--surface), var(--surface2));
    border: 1px solid rgba(251,191,36,0.15);
    border-radius: 14px;
    padding: 32px 28px;
    position: relative;
}
.dedicated-card h3 { font-size: 1.1rem; color: var(--gold); margin-bottom: 6px; }
.dedicated-card .price { font-size: 1.8rem; font-weight: 800; color: #fff; }
.dedicated-card .period { font-size: 0.8rem; color: var(--text-dim); margin-bottom: 16px; }
.dedicated-card ul { list-style: none; margin-bottom: 20px; }
.dedicated-card li {
    font-size: 0.85rem; color: var(--text-dim);
    padding: 5px 0; display: flex; align-items: center; gap: 8px;
}
.dedicated-card li::before { content: '→'; color: var(--gold); font-weight: 700; flex-shrink: 0; }

/* GPU */
.gpu-section {
    background: linear-gradient(180deg, rgba(99,102,241,0.05) 0%, transparent 100%);
    border-top: 1px solid rgba(99,102,241,0.15);
    border-bottom: 1px solid rgba(99,102,241,0.15);
    padding: 80px 24px;
}
.gpu-card {
    max-width: 800px; margin: 0 auto;
    background: linear-gradient(135deg, rgba(99,102,241,0.1), rgba(34,211,238,0.05));
    border: 1px solid rgba(99,102,241,0.25);
    border-radius: 16px;
    padding: 40px;
    text-align: center;
}
.gpu-card h3 { font-size: 1.5rem; color: #fff; margin-bottom: 8px; }
.gpu-card .gpu-name { color: var(--green); font-size: 1.1rem; font-weight: 700; margin-bottom: 20px; }
.gpu-specs {
    display: flex; gap: 32px; justify-content: center; flex-wrap: wrap;
    margin: 24px 0;
}
.gpu-spec { text-align: center; }
.gpu-spec .val { font-size: 1.3rem; font-weight: 800; color: var(--cyan); }
.gpu-spec .lbl { font-size: 0.72rem; color: var(--text-dim); text-transform: uppercase; margin-top: 4px; }

/* SERVICES SECTION */
.services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
}
.service-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 24px;
    text-align: center;
    transition: border-color 0.3s;
}
.service-card:hover { border-color: rgba(99,102,241,0.3); }
.service-icon { font-size: 2rem; margin-bottom: 12px; }
.service-card h4 { font-size: 0.95rem; margin-bottom: 6px; color: #fff; }
.service-card p { font-size: 0.82rem; color: var(--text-dim); }

/* CTA */
.cta-section {
    text-align: center;
    padding: 80px 24px;
    position: relative;
}
.cta-section::before {
    content: '';
    position: absolute; bottom: 0; left: 50%; transform: translateX(-50%);
    width: 600px; height: 400px;
    background: radial-gradient(circle, var(--accent-glow) 0%, transparent 70%);
    pointer-events: none;
}
.cta-section h2 { font-size: 2rem; font-weight: 700; margin-bottom: 16px; }
.cta-section p { color: var(--text-dim); max-width: 600px; margin: 0 auto 32px; }

/* GREEN BADGE */
.green-badge {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.2);
    border-radius: 20px; padding: 6px 16px; font-size: 0.78rem;
    color: var(--green); font-weight: 600;
}

/* FOOTER */
.footer {
    border-top: 1px solid var(--border);
    padding: 40px 24px;
    text-align: center;
    font-size: 0.8rem;
    color: var(--text-dim);
}
.footer-links { display: flex; gap: 24px; justify-content: center; margin-bottom: 16px; flex-wrap: wrap; }
.footer-links a { color: var(--text-dim); }
.footer-links a:hover { color: #fff; }

@media (max-width: 768px) {
    .nav-links { display: none; }
    .hero-stats { gap: 20px; }
    .hero-stat .num { font-size: 1.5rem; }
    .features-grid { grid-template-columns: 1fr; }
    .comp-table { font-size: 0.8rem; }
    .comp-table th, .comp-table td { padding: 10px 12px; }
    .pricing-grid { grid-template-columns: 1fr; max-width: 360px; }
    .dedicated-grid { grid-template-columns: 1fr; }
    .region-grid { grid-template-columns: repeat(3, 1fr); }
    .gpu-specs { gap: 20px; }
    .infra-grid { grid-template-columns: repeat(3, 1fr); }
}
</style>
</head>
<body>

<!-- NAV -->
<nav class="nav">
<div class="nav-inner">
    <div class="nav-logo">
        <svg viewBox="0 0 32 32" fill="none"><rect width="32" height="32" rx="8" fill="#6366f1"/><path d="M8 16h4v8H8zM14 10h4v14h-4zM20 13h4v11h-4z" fill="#fff"/></svg>
        GoHostMe
    </div>
    <div class="nav-links">
        <a href="#infrastructure">Infrastructure</a>
        <a href="#features">Features</a>
        <a href="#regions">Regions</a>
        <a href="#pricing">Pricing</a>
        <a href="#dedicated">Dedicated</a>
        <a href="https://gositeme.com/store">Store</a>
        <a href="https://gositeme.com/cart?a=add&pid=102" class="btn btn-primary">Get Started</a>
    </div>
</div>
</nav>

<!-- HERO -->
<section class="hero">
    <div class="hero-badge">&#9679; Enterprise Cloud Infrastructure &mdash; Live Now</div>
    <h1>AI-Powered Cloud Hosting Across 18 Global Regions</h1>
    <p>Deploy VPS, dedicated servers, and GPU instances on enterprise-grade infrastructure. GoHostMe AI control panel included with every plan. Powered by Canadian hydroelectricity.</p>
    <div class="hero-cta">
        <a href="https://gositeme.com/cart?a=add&pid=102" class="btn btn-primary btn-lg">Deploy VPS &mdash; $59.99/mo &rarr;</a>
        <a href="#pricing" class="btn btn-outline btn-lg">View All Plans</a>
    </div>
    <div class="hero-stats">
        <div class="hero-stat"><div class="num">18</div><div class="label">Global Regions</div></div>
        <div class="hero-stat"><div class="num">1,440</div><div class="label">Instance Configs</div></div>
        <div class="hero-stat"><div class="num">14,400</div><div class="label">VM Capacity</div></div>
        <div class="hero-stat"><div class="num">442</div><div class="label">OS Images</div></div>
        <div class="hero-stat"><div class="num">5.76 PB</div><div class="label">Storage Capacity</div></div>
    </div>
</section>


<!-- INFRASTRUCTURE -->
<section class="section" id="infrastructure">
    <div class="section-label">Infrastructure</div>
    <h2>Enterprise-Grade Cloud Infrastructure</h2>
    <p class="sub">Built on Tier 3+ datacenters across North America, Europe, and beyond. Real hardware, real capacity &mdash; not resold commodity cloud.</p>

    <div class="infra-grid">
        <div class="infra-stat"><div class="num">36,864</div><div class="label">CPU Cores Available</div></div>
        <div class="infra-stat"><div class="num">288 TB</div><div class="label">Total RAM</div></div>
        <div class="infra-stat"><div class="num">5.76 PB</div><div class="label">Total Storage</div></div>
        <div class="infra-stat"><div class="num">10 Gbps</div><div class="label">Port Capacity</div></div>
        <div class="infra-stat"><div class="num">2,400</div><div class="label">Floating IPs / Region</div></div>
        <div class="infra-stat"><div class="num">100</div><div class="label">Load Balancers / Region</div></div>
    </div>

    <div style="text-align:center; margin-top:32px;">
        <span class="green-badge">&#9889; Powered by Hydroelectricity &mdash; Beauharnois, Qu&eacute;bec, Canada</span>
    </div>
</section>


<!-- FEATURES -->
<section class="section" id="features">
    <div class="section-label">Features</div>
    <h2>Everything You Need to Run Hosting</h2>
    <p class="sub">Every plan comes with the full GoHostMe AI control panel &mdash; manage domains, email, DNS, databases, SSL, backups, and more.</p>
    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon">&#127760;</div>
            <h3>Domain Management</h3>
            <p>Create, manage, and configure domains with full DNS zone editing, DNSSEC, and domain aliases.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">&#128231;</div>
            <h3>Email Server</h3>
            <p>Full mail system with SPF, DKIM, DMARC, autoresponders, spam filters, and webmail access.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">&#128274;</div>
            <h3>Free SSL Certificates</h3>
            <p>Let's Encrypt SSL on every domain. Auto-renewal, custom certificate upload, and SNI support.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">&#128450;</div>
            <h3>Database Admin</h3>
            <p>MySQL/MariaDB, PostgreSQL, MongoDB, and Redis. phpMyAdmin included. Import/export built-in.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">&#128260;</div>
            <h3>Automated Backups</h3>
            <p>Daily automated backups with S3-compatible object storage. One-click restore. Archive tier for long-term retention.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">&#128640;</div>
            <h3>Git Deployment</h3>
            <p>Deploy from Git repos with webhooks. Auto-deploy on push, branch-based environments, and instant rollback.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">&#128736;</div>
            <h3>Real-Time Monitoring</h3>
            <p>Live CPU, RAM, disk, network dashboards. Service health checks. Alert notifications via email and webhook.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">&#128272;</div>
            <h3>Firewall &amp; DDoS Protection</h3>
            <p>Enterprise DDoS mitigation included. CSF/LFD integration, IP blocking, brute-force protection, 2FA.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">&#128204;</div>
            <h3>AI Migration Wizard</h3>
            <p>Migrate from cPanel, Plesk, DirectAdmin, or any SSH server. AI-powered, zero-downtime transfer.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">&#129302;</div>
            <h3>Full REST API</h3>
            <p>266+ API endpoints covering every panel feature. Build automations, custom integrations, and CLI workflows.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">&#128101;</div>
            <h3>Multi-Server Clustering</h3>
            <p>Manage multiple servers from one panel. Load balancing, DNS failover, and cross-region clustering.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">&#129497;</div>
            <h3>Managed Kubernetes</h3>
            <p>Free control plane with auto-scaling worker nodes. Deploy containerized apps with zero-downtime rolling updates.</p>
        </div>
    </div>
</section>


<!-- GLOBAL REGIONS -->
<section class="regions-section" id="regions">
    <div class="section-label">Global Network</div>
    <h2>18 Regions Across 3 Continents</h2>
    <p class="sub" style="max-width:650px;">Deploy your infrastructure where your users are. Every region has full VPS, storage, networking, and GPU capability.</p>

    <div class="region-grid">
        <div class="region-card">
            <div class="region-flag">&#127464;&#127462;</div>
            <div class="region-name">Beauharnois</div>
            <div class="region-code">BHS &bull; QC, Canada</div>
        </div>
        <div class="region-card">
            <div class="region-flag">&#127464;&#127462;</div>
            <div class="region-name">Toronto</div>
            <div class="region-code">CA-EAST-TOR</div>
        </div>
        <div class="region-card">
            <div class="region-flag">&#127467;&#127479;</div>
            <div class="region-name">Gravelines</div>
            <div class="region-code">GRA &bull; France</div>
        </div>
        <div class="region-card">
            <div class="region-flag">&#127467;&#127479;</div>
            <div class="region-name">Roubaix</div>
            <div class="region-code">RBX &bull; France</div>
        </div>
        <div class="region-card">
            <div class="region-flag">&#127467;&#127479;</div>
            <div class="region-name">Strasbourg</div>
            <div class="region-code">SBG &bull; France</div>
        </div>
        <div class="region-card">
            <div class="region-flag">&#127467;&#127479;</div>
            <div class="region-name">Paris</div>
            <div class="region-code">EU-WEST-PAR</div>
        </div>
        <div class="region-card">
            <div class="region-flag">&#127465;&#127466;</div>
            <div class="region-name">Frankfurt</div>
            <div class="region-code">DE1 &bull; Germany</div>
        </div>
        <div class="region-card">
            <div class="region-flag">&#127468;&#127463;</div>
            <div class="region-name">London</div>
            <div class="region-code">UK1 &bull; United Kingdom</div>
        </div>
        <div class="region-card">
            <div class="region-flag">&#127477;&#127473;</div>
            <div class="region-name">Warsaw</div>
            <div class="region-code">WAW1 &bull; Poland</div>
        </div>
        <div class="region-card">
            <div class="region-flag">&#127470;&#127481;</div>
            <div class="region-name">Milan</div>
            <div class="region-code">EU-SOUTH-MIL &bull; Italy</div>
        </div>
    </div>

    <div style="text-align:center; margin-top:32px; color:var(--text-dim); font-size:0.88rem;">
        + 8 additional regions available &mdash; <strong style="color:#fff;">up to 800 VMs per region</strong> &mdash; vRack private networking across all regions
    </div>
</section>


<!-- COMPARISON -->
<section class="comparison" id="compare">
    <div class="section-label">Compare</div>
    <h2>GoHostMe vs. The Competition</h2>
    <p class="sub" style="margin-bottom: 32px;">See how we compare to legacy hosting panels and commodity cloud.</p>
    <table class="comp-table">
        <thead>
            <tr>
                <th>Feature</th>
                <th>cPanel</th>
                <th>Plesk</th>
                <th>AWS/GCP</th>
                <th class="highlight-col">GoHostMe</th>
            </tr>
        </thead>
        <tbody>
            <tr><td>Full REST API</td><td class="cross">&mdash;</td><td class="cross">&mdash;</td><td class="check">&#10003;</td><td class="highlight-col check">&#10003;</td></tr>
            <tr><td>AI Control Panel</td><td class="cross">&mdash;</td><td class="cross">&mdash;</td><td class="cross">&mdash;</td><td class="highlight-col check">&#10003;</td></tr>
            <tr><td>AI Migration Wizard</td><td class="cross">&mdash;</td><td class="cross">&mdash;</td><td class="cross">&mdash;</td><td class="highlight-col check">&#10003;</td></tr>
            <tr><td>Built-in Monitoring</td><td class="cross">&mdash;</td><td class="check">&#10003;</td><td>Extra $</td><td class="highlight-col check">&#10003;</td></tr>
            <tr><td>Free SSL (All Domains)</td><td class="check">&#10003;</td><td class="check">&#10003;</td><td>Extra $</td><td class="highlight-col check">&#10003;</td></tr>
            <tr><td>DDoS Protection</td><td class="cross">&mdash;</td><td class="cross">&mdash;</td><td>Extra $</td><td class="highlight-col check">&#10003;</td></tr>
            <tr><td>Managed Kubernetes</td><td class="cross">&mdash;</td><td class="cross">&mdash;</td><td class="check">&#10003;</td><td class="highlight-col check">&#10003;</td></tr>
            <tr><td>GPU Instances</td><td class="cross">&mdash;</td><td class="cross">&mdash;</td><td class="check">&#10003;</td><td class="highlight-col check">&#10003;</td></tr>
            <tr><td>Private Network (vRack)</td><td class="cross">&mdash;</td><td class="cross">&mdash;</td><td>Extra $</td><td class="highlight-col check">&#10003;</td></tr>
            <tr><td>Panel License / month</td><td>$15+</td><td>$12+</td><td>N/A</td><td class="highlight-col"><strong style="color:var(--green)">$0</strong></td></tr>
            <tr><td>Bandwidth Overages</td><td>&#10003;</td><td>&#10003;</td><td>&#10003;</td><td class="highlight-col"><strong style="color:var(--green)">Unlimited</strong></td></tr>
            <tr><td>Green Energy</td><td class="cross">&mdash;</td><td class="cross">&mdash;</td><td class="cross">&mdash;</td><td class="highlight-col check">&#10003;</td></tr>
        </tbody>
    </table>
</section>


<!-- VPS PRICING -->
<section class="section" id="pricing">
    <div class="section-label">Cloud VPS</div>
    <h2>Cloud VPS &mdash; GoHostMe Panel Included Free</h2>
    <p class="sub">Every VPS includes the full AI control panel, free SSL, DDoS protection, unlimited bandwidth, and S3 backup storage. No hidden fees.</p>
    <div class="pricing-grid" style="grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));">
        <div class="price-card">
            <div class="tier">VPS Starter</div>
            <div class="price">$29.99</div>
            <div class="period">per month</div>
            <ul>
                <li>2 vCPUs</li>
                <li>4 GB RAM</li>
                <li>80 GB NVMe SSD</li>
                <li>1 Gbps unmetered</li>
                <li>GoHostMe panel</li>
                <li>Free SSL &amp; backups</li>
                <li>DDoS protection</li>
            </ul>
            <a href="https://gositeme.com/cart?a=add&pid=101" class="btn btn-outline" style="width:100%;justify-content:center;">Deploy Now</a>
        </div>
        <div class="price-card popular">
            <div class="tier">VPS Business</div>
            <div class="price">$59.99</div>
            <div class="period">per month</div>
            <ul>
                <li>4 vCPUs</li>
                <li>8 GB RAM</li>
                <li>160 GB NVMe SSD</li>
                <li>1 Gbps unmetered</li>
                <li>GoHostMe panel</li>
                <li>AI migration wizard</li>
                <li>Priority support</li>
            </ul>
            <a href="https://gositeme.com/cart?a=add&pid=102" class="btn btn-primary" style="width:100%;justify-content:center;">Deploy Now</a>
        </div>
        <div class="price-card">
            <div class="tier">VPS Pro</div>
            <div class="price">$99.99</div>
            <div class="period">per month</div>
            <ul>
                <li>8 vCPUs</li>
                <li>16 GB RAM</li>
                <li>320 GB NVMe SSD</li>
                <li>1 Gbps unmetered</li>
                <li>GoHostMe panel</li>
                <li>Monitoring &amp; alerts</li>
                <li>Dedicated support</li>
            </ul>
            <a href="https://gositeme.com/cart?a=add&pid=103" class="btn btn-outline" style="width:100%;justify-content:center;">Deploy Now</a>
        </div>
        <div class="price-card">
            <div class="tier">VPS Enterprise</div>
            <div class="price">$179.99</div>
            <div class="period">per month</div>
            <ul>
                <li>16 vCPUs</li>
                <li>32 GB RAM</li>
                <li>640 GB NVMe SSD</li>
                <li>1 Gbps unmetered</li>
                <li>GoHostMe panel</li>
                <li>Multi-server clustering</li>
                <li>White-label ready</li>
            </ul>
            <a href="https://gositeme.com/cart?a=add&pid=104" class="btn btn-outline" style="width:100%;justify-content:center;">Deploy Now</a>
        </div>
    </div>
    <p style="text-align:center; margin-top:24px; font-size:0.85rem; color:var(--text-dim);">
        All VPS plans include: 442 OS images &bull; IPv4 + IPv6 &bull; 10 Gbps port &bull; vRack private networking &bull;
        <a href="https://gositeme.com/store">annual discounts available</a>
    </p>
</section>


<!-- DEDICATED SERVERS -->
<section class="section" id="dedicated">
    <div class="section-label">Dedicated Servers</div>
    <h2>Bare-Metal Dedicated Servers</h2>
    <p class="sub">Full root access to real hardware. Intel Xeon processors, ECC RAM, datacenter-grade NVMe/SSD storage. No noisy neighbors.</p>

    <div class="dedicated-grid">
        <div class="dedicated-card">
            <h3>Dedicated Starter</h3>
            <div class="price">$149.99</div>
            <div class="period">per month</div>
            <ul>
                <li>Intel Xeon E-2274G (4C/8T)</li>
                <li>32 GB DDR4 ECC</li>
                <li>2&times; 500 GB NVMe SSD</li>
                <li>1 Gbps unmetered</li>
                <li>Full IPMI/KVM access</li>
                <li>DDoS protection included</li>
            </ul>
            <a href="https://gositeme.com/cart?a=add&pid=105" class="btn btn-gold" style="width:100%;justify-content:center;">Configure &amp; Order</a>
        </div>
        <div class="dedicated-card">
            <h3>Dedicated Business</h3>
            <div class="price">$249.99</div>
            <div class="period">per month</div>
            <ul>
                <li>Intel Xeon E-2386G (6C/12T)</li>
                <li>64 GB DDR4 ECC</li>
                <li>2&times; 1 TB NVMe SSD</li>
                <li>1 Gbps unmetered</li>
                <li>256 failover IPs available</li>
                <li>vRack private networking</li>
            </ul>
            <a href="https://gositeme.com/cart?a=add&pid=106" class="btn btn-gold" style="width:100%;justify-content:center;">Configure &amp; Order</a>
        </div>
        <div class="dedicated-card">
            <h3>Dedicated Pro</h3>
            <div class="price">$399.99</div>
            <div class="period">per month</div>
            <ul>
                <li>AMD EPYC / Intel Xeon Silver</li>
                <li>128 GB DDR4 ECC</li>
                <li>2&times; 2 TB NVMe SSD</li>
                <li>1 Gbps unmetered</li>
                <li>Hardware RAID</li>
                <li>Dedicated account manager</li>
            </ul>
            <a href="https://gositeme.com/cart?a=add&pid=107" class="btn btn-gold" style="width:100%;justify-content:center;">Configure &amp; Order</a>
        </div>
        <div class="dedicated-card">
            <h3>Dedicated Enterprise</h3>
            <div class="price">$699.99</div>
            <div class="period">per month</div>
            <ul>
                <li>Dual Intel Xeon Gold / EPYC</li>
                <li>256 GB DDR4 ECC</li>
                <li>4&times; 2 TB NVMe SSD</li>
                <li>10 Gbps unmetered</li>
                <li>Redundant power supply</li>
                <li>SOC2-ready infrastructure</li>
            </ul>
            <a href="https://gositeme.com/cart?a=add&pid=108" class="btn btn-gold" style="width:100%;justify-content:center;">Configure &amp; Order</a>
        </div>
    </div>
</section>


<!-- GPU SERVERS -->
<section class="gpu-section" id="gpu">
    <div style="max-width:1200px; margin:0 auto;">
        <div class="section-label" style="text-align:center;">AI &amp; Machine Learning</div>
        <h2 style="text-align:center; font-size:clamp(1.6rem,3vw,2.2rem); font-weight:700; margin-bottom:32px;">GPU Servers &mdash; NVIDIA A10 Instances</h2>

        <div class="gpu-card">
            <h3>Purpose-Built for AI/ML Workloads</h3>
            <div class="gpu-name">NVIDIA A10 Tensor Core GPU</div>
            <p style="color:var(--text-dim); max-width:500px; margin:0 auto 24px; font-size:0.92rem;">Train models, run inference, render 3D, power real-time AI agents. Available across multiple global regions with instant provisioning.</p>
            <div class="gpu-specs">
                <div class="gpu-spec"><div class="val">30&ndash;120</div><div class="lbl">vCPUs</div></div>
                <div class="gpu-spec"><div class="val">45&ndash;180</div><div class="lbl">GB RAM</div></div>
                <div class="gpu-spec"><div class="val">1&ndash;4</div><div class="lbl">GPUs</div></div>
                <div class="gpu-spec"><div class="val">31.2</div><div class="lbl">TFLOPS FP32</div></div>
                <div class="gpu-spec"><div class="val">24 GB</div><div class="lbl">VRAM / GPU</div></div>
            </div>
            <div style="margin-top:28px; display:flex; gap:16px; justify-content:center; flex-wrap:wrap;">
                <a href="https://gositeme.com/cart?a=add&pid=109" class="btn btn-primary btn-lg">GPU Server &mdash; from $2,499/mo &rarr;</a>
            </div>
            <p style="color:var(--text-dim); font-size:0.8rem; margin-top:16px;">Ideal for: LLM training, Stable Diffusion, AI voice agents, computer vision, scientific computing</p>
        </div>
    </div>
</section>


<!-- MANAGED SERVICES -->
<section class="section" id="services">
    <div class="section-label">Managed Services</div>
    <h2>Cloud Services &amp; Add-Ons</h2>
    <p class="sub">Scale beyond a single server with managed cloud services &mdash; all integrated into your GoHostMe panel.</p>

    <div class="services-grid">
        <div class="service-card">
            <div class="service-icon">&#128451;</div>
            <h4>Object Storage (S3)</h4>
            <p>S3-compatible storage for backups, media, and static assets. Archive tier available.</p>
        </div>
        <div class="service-card">
            <div class="service-icon">&#9881;</div>
            <h4>Managed Kubernetes</h4>
            <p>Free control plane. Auto-scaling workers. Zero-downtime deployments.</p>
        </div>
        <div class="service-card">
            <div class="service-icon">&#128451;</div>
            <h4>Managed Databases</h4>
            <p>PostgreSQL, MySQL, MongoDB, Redis, Kafka. Automated backups &amp; HA.</p>
        </div>
        <div class="service-card">
            <div class="service-icon">&#128272;</div>
            <h4>Key Management (KMS)</h4>
            <p>16,000 secrets per region. Enterprise-grade secret management.</p>
        </div>
        <div class="service-card">
            <div class="service-icon">&#9878;</div>
            <h4>Load Balancers</h4>
            <p>100 load balancers per region. HTTP, TCP, UDP. Health checks built-in.</p>
        </div>
        <div class="service-card">
            <div class="service-icon">&#128268;</div>
            <h4>vRack Private Network</h4>
            <p>Free private backbone across all servers and regions. Isolated, zero-latency.</p>
        </div>
        <div class="service-card">
            <div class="service-icon">&#128225;</div>
            <h4>Floating IPs &amp; Failover</h4>
            <p>2,400 floating IPs per region. Instant failover between instances.</p>
        </div>
        <div class="service-card">
            <div class="service-icon">&#128230;</div>
            <h4>Container Registry</h4>
            <p>Private Docker registry. CI/CD pipeline ready. Team collaboration.</p>
        </div>
    </div>
</section>


<!-- RESELLER -->
<section class="section" id="reseller" style="text-align:center;">
    <div class="section-label">Reseller &amp; White-Label</div>
    <h2>Build Your Own Hosting Company</h2>
    <p class="sub" style="margin:0 auto 32px;">White-label the entire GoHostMe platform under your brand. Resell VPS, dedicated servers, and managed services. Full API access for automation.</p>

    <div class="pricing-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); max-width:900px;">
        <div class="price-card">
            <div class="tier">Reseller Bronze</div>
            <div class="price">$399</div>
            <div class="period">per month</div>
            <ul>
                <li>Up to 50 client accounts</li>
                <li>White-label panel</li>
                <li>Full API access</li>
                <li>Custom branding</li>
                <li>Email support</li>
            </ul>
            <a href="https://gositeme.com/cart?a=add&pid=46" class="btn btn-outline" style="width:100%;justify-content:center;">Start Reselling</a>
        </div>
        <div class="price-card popular">
            <div class="tier">Reseller Silver</div>
            <div class="price">$899</div>
            <div class="period">per month</div>
            <ul>
                <li>Up to 200 client accounts</li>
                <li>White-label everything</li>
                <li>Priority support</li>
                <li>Custom DNS branding</li>
                <li>Revenue analytics</li>
            </ul>
            <a href="https://gositeme.com/cart?a=add&pid=47" class="btn btn-primary" style="width:100%;justify-content:center;">Start Reselling</a>
        </div>
        <div class="price-card">
            <div class="tier">Reseller Gold</div>
            <div class="price">$2,499</div>
            <div class="period">per month</div>
            <ul>
                <li>Unlimited client accounts</li>
                <li>Full white-label platform</li>
                <li>Dedicated account manager</li>
                <li>Custom integrations</li>
                <li>SLA guarantee</li>
            </ul>
            <a href="https://gositeme.com/cart?a=add&pid=48" class="btn btn-outline" style="width:100%;justify-content:center;">Contact Sales</a>
        </div>
    </div>
</section>


<!-- FINAL CTA -->
<section class="cta-section">
    <h2>Deploy Your Infrastructure Today</h2>
    <p>VPS from $29.99/mo. Dedicated from $149.99/mo. GPU from $2,499/mo. GoHostMe AI panel included free on every plan. 18 global regions. Unlimited bandwidth. Powered by Canadian hydroelectricity.</p>
    <div style="display:flex; gap:16px; justify-content:center; flex-wrap:wrap;">
        <a href="https://gositeme.com/cart?a=add&pid=102" class="btn btn-primary btn-lg">Get VPS Business &mdash; $59.99/mo &rarr;</a>
        <a href="https://gositeme.com/cart?a=add&pid=105" class="btn btn-gold btn-lg">Dedicated Server &mdash; $149.99/mo</a>
        <a href="https://gositeme.com/store" class="btn btn-outline btn-lg">Browse All Products</a>
    </div>
    <div style="margin-top: 32px;">
        <span class="green-badge">&#128205; Accept Stripe &amp; PayPal &bull; Instant Provisioning &bull; 24/7 AI Support</span>
    </div>
</section>


<!-- FOOTER -->
<footer class="footer">
    <div class="footer-links">
        <a href="https://gositeme.com">GoSiteMe</a>
        <a href="https://gositeme.com/store">Store</a>
        <a href="#pricing">VPS Plans</a>
        <a href="#dedicated">Dedicated Servers</a>
        <a href="#gpu">GPU Servers</a>
        <a href="#reseller">Reseller Plans</a>
        <a href="/alfred-ide.php">Alfred IDE</a>
        <a href="https://meta-dome.com">MetaDome</a>
    </div>
    <p style="margin-top:12px;">Infrastructure across 10 datacenters: Beauharnois &bull; Toronto &bull; Gravelines &bull; Roubaix &bull; Strasbourg &bull; Paris &bull; Frankfurt &bull; London &bull; Warsaw &bull; Milan</p>
    <p style="margin-top:8px;">&copy; <?php echo date('Y'); ?> GoSiteMe Inc. All rights reserved. GoHostMe is a product of GoSiteMe.</p>
</footer>

</body>
</html>
