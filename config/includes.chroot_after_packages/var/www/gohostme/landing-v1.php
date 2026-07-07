<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GoHostMe — AI-Powered Hosting Control Panel</title>
<meta name="description" content="The modern hosting control panel built by GoSiteMe. Manage domains, emails, DNS, databases, and more with an AI-native interface.">
<link rel="icon" href="/favicon.ico">
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
    background: rgba(10,14,26,0.85);
    backdrop-filter: blur(12px);
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
.nav-links { display: flex; gap: 28px; align-items: center; }
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
    width: 800px; height: 800px;
    background: radial-gradient(circle, var(--accent-glow) 0%, transparent 70%);
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
    max-width: 800px; margin: 0 auto 20px;
    background: linear-gradient(135deg, #fff 0%, var(--accent2) 100%);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text;
}
.hero p {
    font-size: 1.15rem; color: var(--text-dim);
    max-width: 600px; margin: 0 auto 36px;
}
.hero-cta { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; }
.hero-stats {
    display: flex; gap: 48px; justify-content: center; margin-top: 60px;
    flex-wrap: wrap;
}
.hero-stat { text-align: center; }
.hero-stat .num { font-size: 2rem; font-weight: 800; color: #fff; }
.hero-stat .label { font-size: 0.8rem; color: var(--text-dim); margin-top: 4px; }

/* FEATURES */
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
    color: var(--text-dim); max-width: 600px; margin-bottom: 48px;
}
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
.comp-table th:first-child { text-align: left; }
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
    max-width: 1000px;
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
.cta-section p { color: var(--text-dim); max-width: 500px; margin: 0 auto 32px; }

/* FOOTER */
.footer {
    border-top: 1px solid var(--border);
    padding: 40px 24px;
    text-align: center;
    font-size: 0.8rem;
    color: var(--text-dim);
}
.footer-links { display: flex; gap: 24px; justify-content: center; margin-bottom: 16px; }
.footer-links a { color: var(--text-dim); }
.footer-links a:hover { color: #fff; }

@media (max-width: 768px) {
    .nav-links { display: none; }
    .hero-stats { gap: 24px; }
    .hero-stat .num { font-size: 1.5rem; }
    .features-grid { grid-template-columns: 1fr; }
    .comp-table { font-size: 0.8rem; }
    .comp-table th, .comp-table td { padding: 10px 12px; }
    .pricing-grid { grid-template-columns: 1fr; max-width: 360px; }
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
        <a href="#features">Features</a>
        <a href="#compare">Compare</a>
        <a href="#pricing">Pricing</a>
        <a href="https://gositeme.com">GoSiteMe</a>
        <a href="/gohostme/panel" class="btn btn-primary">Login to Panel</a>
    </div>
</div>
</nav>

<!-- HERO -->
<section class="hero">
    <div class="hero-badge">&#9679; Now Available &mdash; v1.0</div>
    <h1>The Hosting Control Panel Built for the AI Era</h1>
    <p>Manage domains, emails, databases, DNS, SSL, and more — with a modern interface, powerful API, and AI-ready architecture. By GoSiteMe.</p>
    <div class="hero-cta">
        <a href="/gohostme/panel" class="btn btn-primary btn-lg">Open Panel &rarr;</a>
        <a href="#features" class="btn btn-outline btn-lg">Explore Features</a>
    </div>
    <div class="hero-stats">
        <div class="hero-stat"><div class="num">266+</div><div class="label">API Endpoints</div></div>
        <div class="hero-stat"><div class="num">20+</div><div class="label">Modules</div></div>
        <div class="hero-stat"><div class="num">100%</div><div class="label">API Coverage</div></div>
        <div class="hero-stat"><div class="num">SOC2</div><div class="label">Security Standards</div></div>
    </div>
</section>

<!-- FEATURES -->
<section class="section" id="features">
    <div class="section-label">Features</div>
    <h2>Everything You Need to Run Hosting</h2>
    <p class="sub">A complete hosting management platform with every tool built-in — no plugins, no extra licenses.</p>
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
            <h3>SSL Management</h3>
            <p>Free Let's Encrypt SSL, custom certificate upload, auto-renewal, and SNI support.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">&#128450;</div>
            <h3>Database Admin</h3>
            <p>MySQL/MariaDB databases with phpMyAdmin integration, user management, and import/export.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">&#128260;</div>
            <h3>Backup &amp; Restore</h3>
            <p>Automated backups with one-click restore. Full server, domain-level, and file-level recovery.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">&#128640;</div>
            <h3>Git Deployment</h3>
            <p>Deploy from Git repositories with webhooks, auto-deploy on push, and rollback support.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">&#128736;</div>
            <h3>Server Monitoring</h3>
            <p>Real-time CPU, RAM, disk, and network stats. Service health checks and alert notifications.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">&#128272;</div>
            <h3>Firewall &amp; Security</h3>
            <p>CSF/LFD integration, IP blocking, brute-force protection, two-factor auth, and audit logs.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">&#128204;</div>
            <h3>AI Migration Tool</h3>
            <p>Migrate from cPanel, Plesk, DirectAdmin, or any SSH server with our AI-powered migration wizard.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">&#128176;</div>
            <h3>Billing &amp; Licensing</h3>
            <p>Built-in billing system with invoice generation, Stripe integration, and license management.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">&#129302;</div>
            <h3>Full REST API</h3>
            <p>266+ API endpoints covering every panel feature. Build custom integrations and automations.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">&#128101;</div>
            <h3>Multi-Server</h3>
            <p>Manage multiple servers from a single panel. Clustering, load balancing, and DNS failover.</p>
        </div>
    </div>
</section>

<!-- COMPARISON -->
<section class="comparison" id="compare">
    <div class="section-label">Compare</div>
    <h2>How GoHostMe Stacks Up</h2>
    <p class="sub" style="margin-bottom: 32px;">See how we compare to legacy hosting panels.</p>
    <table class="comp-table">
        <thead>
            <tr>
                <th>Feature</th>
                <th>cPanel</th>
                <th>Plesk</th>
                <th>DirectAdmin</th>
                <th class="highlight-col">GoHostMe</th>
            </tr>
        </thead>
        <tbody>
            <tr><td>Full REST API</td><td class="cross">&mdash;</td><td class="cross">&mdash;</td><td class="cross">&mdash;</td><td class="highlight-col check">&#10003;</td></tr>
            <tr><td>AI Migration Wizard</td><td class="cross">&mdash;</td><td class="cross">&mdash;</td><td class="cross">&mdash;</td><td class="highlight-col check">&#10003;</td></tr>
            <tr><td>Built-in Monitoring</td><td class="cross">&mdash;</td><td class="check">&#10003;</td><td class="cross">&mdash;</td><td class="highlight-col check">&#10003;</td></tr>
            <tr><td>Git Deploy</td><td class="check">&#10003;</td><td class="check">&#10003;</td><td class="cross">&mdash;</td><td class="highlight-col check">&#10003;</td></tr>
            <tr><td>2FA + PIN Security</td><td class="check">&#10003;</td><td class="check">&#10003;</td><td class="cross">&mdash;</td><td class="highlight-col check">&#10003;</td></tr>
            <tr><td>Google SSO</td><td class="cross">&mdash;</td><td class="cross">&mdash;</td><td class="cross">&mdash;</td><td class="highlight-col check">&#10003;</td></tr>
            <tr><td>License Cost / month</td><td>$15+</td><td>$12+</td><td>$5+</td><td class="highlight-col"><strong>$9.99</strong></td></tr>
            <tr><td>Open API Endpoints</td><td>~50</td><td>~80</td><td>~40</td><td class="highlight-col"><strong>266+</strong></td></tr>
            <tr><td>AI-Native Architecture</td><td class="cross">&mdash;</td><td class="cross">&mdash;</td><td class="cross">&mdash;</td><td class="highlight-col check">&#10003;</td></tr>
        </tbody>
    </table>
</section>

<!-- PRICING -->
<section class="section" id="pricing">
    <div class="section-label">Pricing</div>
    <h2>Cloud VPS Hosting — GoHostMe Panel Included</h2>
    <p class="sub">Every plan includes the full GoHostMe AI control panel. No license fees, no per-addon charges.</p>
    <div class="pricing-grid">
        <div class="price-card">
            <div class="tier">VPS Starter</div>
            <div class="price">$29.99</div>
            <div class="period">per month</div>
            <ul>
                <li>2 vCPUs, 4 GB RAM</li>
                <li>80 GB NVMe SSD</li>
                <li>GoHostMe panel included</li>
                <li>Free SSL &amp; backups</li>
                <li>Email &amp; DNS management</li>
            </ul>
            <a href="https://gositeme.com/cart?a=add&pid=101" class="btn btn-outline" style="width:100%;justify-content:center;">Order Now</a>
        </div>
        <div class="price-card popular">
            <div class="tier">VPS Business</div>
            <div class="price">$59.99</div>
            <div class="period">per month</div>
            <ul>
                <li>4 vCPUs, 8 GB RAM</li>
                <li>160 GB NVMe SSD</li>
                <li>GoHostMe panel included</li>
                <li>AI migration wizard</li>
                <li>Priority support</li>
            </ul>
            <a href="https://gositeme.com/cart?a=add&pid=102" class="btn btn-primary" style="width:100%;justify-content:center;">Order Now</a>
        </div>
        <div class="price-card">
            <div class="tier">VPS Pro</div>
            <div class="price">$99.99</div>
            <div class="period">per month</div>
            <ul>
                <li>8 vCPUs, 16 GB RAM</li>
                <li>320 GB NVMe SSD</li>
                <li>GoHostMe panel included</li>
                <li>Monitoring &amp; alerts</li>
                <li>Dedicated support</li>
            </ul>
            <a href="https://gositeme.com/cart?a=add&pid=103" class="btn btn-outline" style="width:100%;justify-content:center;">Order Now</a>
        </div>
    </div>
    <div style="text-align:center;margin-top:40px;">
        <p style="color:var(--text-dim);margin-bottom:16px;">Need more power?</p>
        <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;">
            <a href="https://gositeme.com/cart?a=add&pid=104" class="btn btn-outline">VPS Enterprise — $179.99/mo</a>
            <a href="https://gositeme.com/cart?a=add&pid=105" class="btn btn-outline">Dedicated Server — $149.99/mo</a>
            <a href="https://gositeme.com/cart?a=add&pid=109" class="btn btn-outline">GPU Server (AI/ML) — $2,499/mo</a>
        </div>
        <p style="color:var(--text-dim);margin-top:20px;font-size:0.85rem;"><a href="https://gositeme.com/store">View all products &rarr;</a></p>
    </div>
</section>

<!-- CTA -->
<section class="cta-section">
    <h2>Ready to Modernize Your Hosting?</h2>
    <p>Deploy your AI-powered VPS in minutes. GoHostMe panel included with every plan.</p>
    <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;">
        <a href="https://gositeme.com/cart?a=add&pid=102" class="btn btn-primary btn-lg">Get VPS Business — $59.99/mo &rarr;</a>
        <a href="https://gositeme.com/store" class="btn btn-outline btn-lg">Browse All Plans</a>
    </div>
</section>

<!-- FOOTER -->
<footer class="footer">
    <div class="footer-links">
        <a href="https://gositeme.com">GoSiteMe</a>
        <a href="#pricing">Pricing</a>
        <a href="https://gositeme.com/store">Store</a>
        <a href="/gohostme/panel">Panel Login</a>
    </div>
    <p>&copy; <?php echo date('Y'); ?> GoSiteMe Inc. All rights reserved. GoHostMe is a product of GoSiteMe.</p>
</footer>

</body>
</html>
