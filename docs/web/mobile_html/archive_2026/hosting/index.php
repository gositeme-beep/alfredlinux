<?php
/**
 * Cloud & VPS Hosting — Sales Funnel Landing Page
 * Products: Starter ($29.99), Business ($59.99), Pro ($99.99), Enterprise ($179.99)
 * VPS IDs: 101, 102, 103, 104
 */
$pageTitle = "Cloud VPS Hosting — Lightning Fast, AI-Powered";
$pageDesc  = "Enterprise cloud VPS hosting powered by OVH infrastructure. Blazing fast NVMe SSD, DDoS protection included, managed by AI.";

try {
    require_once dirname(__DIR__) . '/includes/db-config.inc.php';
    $pdo = new PDO('mysql:unix_socket=/run/mysql/mysql.sock;dbname=' . GOSITEME_DB_NAME, GOSITEME_DB_USER, GOSITEME_DB_PASS);
    $clientCount = $pdo->query("SELECT COUNT(*) FROM tblclients WHERE status='Active'")->fetchColumn();
} catch (Exception $e) { $clientCount = 22; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?></title>
<meta name="description" content="<?= htmlspecialchars($pageDesc) ?>">
<meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
<meta property="og:description" content="<?= htmlspecialchars($pageDesc) ?>">
<link rel="canonical" href="https://gositeme.com/hosting/">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#0a0a0f;color:#e0e0e0;overflow-x:hidden}
a{color:#00d4ff;text-decoration:none}
.container{max-width:1200px;margin:0 auto;padding:0 24px}

.topbar{background:#000;padding:8px 0;text-align:center;font-size:13px;color:#888;border-bottom:1px solid #1a1a2e}
.topbar .live{color:#00ff88;font-weight:600}

nav{background:rgba(10,10,15,0.95);backdrop-filter:blur(20px);padding:16px 0;position:sticky;top:0;z-index:100;border-bottom:1px solid rgba(0,212,255,0.1)}
nav .container{display:flex;align-items:center;justify-content:space-between}
nav .logo{font-size:22px;font-weight:800;background:linear-gradient(135deg,#00d4ff,#00ff88);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
nav .links{display:flex;gap:28px;font-size:14px}
nav .links a{color:#999;transition:color .2s}
nav .links a:hover{color:#fff}
.cta-btn{background:linear-gradient(135deg,#00d4ff,#00ff88);color:#000!important;padding:10px 24px;border-radius:8px;font-weight:700;font-size:14px;display:inline-block;transition:all .3s;border:none;cursor:pointer;-webkit-text-fill-color:#000}
.cta-btn:hover{transform:translateY(-2px);box-shadow:0 8px 30px rgba(0,212,255,.3)}
.cta-btn-lg{padding:16px 40px;font-size:18px;border-radius:12px}
.cta-btn-outline{background:transparent;border:2px solid #00d4ff;color:#00d4ff!important;-webkit-text-fill-color:#00d4ff}
.cta-btn-outline:hover{background:rgba(0,212,255,.1)}

/* Hero */
.hero{padding:100px 0 80px;text-align:center;position:relative}
.hero::before{content:'';position:absolute;top:0;left:50%;transform:translateX(-50%);width:600px;height:600px;background:radial-gradient(circle,rgba(0,255,136,.1),transparent 60%);pointer-events:none}
.hero .badge{display:inline-flex;align-items:center;gap:8px;background:rgba(0,255,136,.1);border:1px solid rgba(0,255,136,.3);color:#00ff88;padding:6px 16px;border-radius:20px;font-size:13px;font-weight:600;margin-bottom:24px}
.hero h1{font-size:clamp(36px,5vw,64px);font-weight:900;line-height:1.1;margin-bottom:20px;background:linear-gradient(135deg,#fff,#00ff88);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.hero p{font-size:20px;color:#888;max-width:680px;margin:0 auto 40px;line-height:1.6}
.hero-buttons{display:flex;gap:16px;justify-content:center;flex-wrap:wrap}

/* Terminal Demo */
.terminal{max-width:600px;margin:40px auto 0;background:#0d0d14;border:1px solid #222;border-radius:12px;overflow:hidden;font-family:'Fira Code','Courier New',monospace;font-size:13px;box-shadow:0 20px 60px rgba(0,0,0,.5)}
.terminal-bar{background:#1a1a2e;padding:10px 16px;display:flex;align-items:center;gap:8px}
.terminal-bar .dot{width:10px;height:10px;border-radius:50%}
.terminal-bar .dot.r{background:#ff5555}
.terminal-bar .dot.y{background:#ffcc00}
.terminal-bar .dot.g{background:#00ff88}
.terminal-bar span{color:#666;font-size:12px;margin-left:auto}
.terminal-body{padding:16px}
.terminal-body .line{margin-bottom:4px;color:#888}
.terminal-body .cmd{color:#00ff88}
.terminal-body .out{color:#bbb}
.terminal-body .highlight{color:#00d4ff}

/* Stats */
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;padding:60px 0;border-bottom:1px solid #1a1a2e}
.stat{text-align:center;padding:24px}
.stat .num{font-size:36px;font-weight:900;background:linear-gradient(135deg,#00d4ff,#00ff88);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.stat .label{color:#888;font-size:14px;margin-top:6px}

/* Features */
.features{padding:80px 0}
.features h2{text-align:center;font-size:36px;font-weight:900;margin-bottom:48px}
.feat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:24px}
.feat-card{background:#111;border:1px solid #222;border-radius:16px;padding:32px;transition:all .3s}
.feat-card:hover{border-color:rgba(0,255,136,.3);transform:translateY(-4px)}
.feat-card .icon{font-size:32px;margin-bottom:16px}
.feat-card h3{font-size:18px;font-weight:700;margin-bottom:8px;color:#00ff88}
.feat-card p{color:#888;font-size:14px;line-height:1.6}

/* Comparison */
.compare{padding:80px 0;background:#0d0d14}
.compare h2{text-align:center;font-size:36px;font-weight:900;margin-bottom:48px}
.compare-table{width:100%;border-collapse:collapse;max-width:900px;margin:0 auto}
.compare-table th{background:#1a1a2e;padding:14px 20px;text-align:left;font-size:14px;color:#888;border-bottom:1px solid #333}
.compare-table td{padding:14px 20px;border-bottom:1px solid #1a1a2e;font-size:14px}
.compare-table tr:hover{background:rgba(0,212,255,.03)}
.compare-table .us{color:#00ff88;font-weight:700}
.compare-table .them{color:#888}

/* Pricing */
.pricing{padding:80px 0}
.pricing h2{text-align:center;font-size:36px;font-weight:900;margin-bottom:16px}
.pricing .sub{text-align:center;color:#888;font-size:18px;margin-bottom:48px}
.price-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:24px}
.price-card{background:#111;border:1px solid #222;border-radius:16px;padding:36px;text-align:center;transition:all .3s;position:relative}
.price-card:hover{border-color:rgba(0,255,136,.3);transform:translateY(-4px)}
.price-card.featured{border-color:rgba(0,255,136,.4);background:linear-gradient(180deg,rgba(0,255,136,.08),#111)}
.price-card.featured::before{content:'BEST VALUE';position:absolute;top:-12px;left:50%;transform:translateX(-50%);background:linear-gradient(135deg,#00d4ff,#00ff88);color:#000;padding:4px 16px;border-radius:12px;font-size:11px;font-weight:800}
.price-card .tier{font-size:14px;color:#888;font-weight:600;text-transform:uppercase;letter-spacing:1px;margin-bottom:12px}
.price-card .amount{font-size:48px;font-weight:900;color:#fff;margin-bottom:4px}
.price-card .amount sup{font-size:20px;color:#888}
.price-card .period{color:#666;font-size:14px;margin-bottom:24px}
.price-card ul{list-style:none;text-align:left;margin-bottom:32px}
.price-card ul li{padding:8px 0;font-size:14px;color:#bbb;border-bottom:1px solid #1a1a2e;display:flex;align-items:center;gap:8px}
.price-card ul li::before{content:'✓';color:#00ff88;font-weight:700}

/* Add-ons */
.addons{padding:80px 0;background:#0d0d14}
.addons h2{text-align:center;font-size:36px;font-weight:900;margin-bottom:48px}
.addon-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;max-width:900px;margin:0 auto}
.addon-card{background:#111;border:1px solid #222;border-radius:12px;padding:20px;text-align:center;transition:all .3s}
.addon-card:hover{border-color:rgba(0,212,255,.3)}
.addon-card .icon{font-size:24px;margin-bottom:8px}
.addon-card .name{font-size:14px;font-weight:700;color:#ddd;margin-bottom:4px}
.addon-card .price{color:#00ff88;font-size:16px;font-weight:700}

/* CTA */
.cta-section{padding:80px 0;text-align:center;background:linear-gradient(180deg,#0a0a0f,#0d0d1a)}
.cta-section h2{font-size:36px;font-weight:900;margin-bottom:16px}
.cta-section p{color:#888;font-size:18px;margin-bottom:32px;max-width:600px;margin-left:auto;margin-right:auto}

footer{padding:32px 0;text-align:center;color:#555;font-size:13px;border-top:1px solid #1a1a2e}
footer a{color:#888}

@media(max-width:768px){
.stats{grid-template-columns:repeat(2,1fr)}
.price-grid{grid-template-columns:1fr}
nav .links{display:none}
.compare-table{font-size:12px}
.compare-table th,.compare-table td{padding:10px 12px}
}
</style>
</head>
<body>

<!-- Top Bar -->
<div class="topbar">
    <span class="live">● LIVE</span> — OVH Cloud Canada Infrastructure &nbsp;|&nbsp; 99.99% Uptime SLA &nbsp;|&nbsp; <a href="#pricing">Deploy in 60 Seconds →</a>
</div>

<!-- Navigation -->
<nav>
    <div class="container">
        <div class="logo">GoHostMe Cloud</div>
        <div class="links">
            <a href="#features">Features</a>
            <a href="#compare">Compare</a>
            <a href="#pricing">Pricing</a>
            <a href="#addons">Add-Ons</a>
            <a href="https://gositeme.com/gohostme/" target="_blank">Dashboard</a>
        </div>
        <a href="#pricing" class="cta-btn">Deploy Now</a>
    </div>
</nav>

<!-- Hero -->
<section class="hero">
    <div class="container">
        <div class="badge">⚡ NVMe SSD + Anti-DDoS Included on All Plans</div>
        <h1>Cloud Hosting That<br>Actually Performs</h1>
        <p>Enterprise-grade VPS hosting on OVH's global infrastructure. NVMe storage, unmetered bandwidth, DDoS protection, and an AI-powered management panel — all included.</p>
        <div class="hero-buttons">
            <a href="#pricing" class="cta-btn cta-btn-lg">See Plans & Pricing</a>
            <a href="https://gositeme.com/gohostme/" class="cta-btn cta-btn-lg cta-btn-outline">Live Demo →</a>
        </div>

        <!-- Terminal Demo -->
        <div class="terminal">
            <div class="terminal-bar">
                <div class="dot r"></div><div class="dot y"></div><div class="dot g"></div>
                <span>~ GoHostMe CLI</span>
            </div>
            <div class="terminal-body">
                <div class="line"><span class="cmd">$</span> gohostme deploy --plan business --region us-east</div>
                <div class="line"><span class="out">⚡ Provisioning VPS Business (4 vCPU, 8GB RAM)...</span></div>
                <div class="line"><span class="out">📦 Installing Ubuntu 22.04 LTS...</span></div>
                <div class="line"><span class="out">🔒 Configuring firewall + DDoS protection...</span></div>
                <div class="line"><span class="out">🌐 Assigning IP: </span><span class="highlight">203.0.113.42</span></div>
                <div class="line"><span class="out">✅ </span><span class="highlight">VPS ready in 47 seconds!</span></div>
                <div class="line"><span class="cmd">$</span> ssh root@203.0.113.42</div>
                <div class="line"><span class="highlight">Welcome to GoHostMe Cloud VPS</span></div>
            </div>
        </div>
    </div>
</section>

<!-- Stats -->
<section class="stats">
    <div class="container" style="display:grid;grid-template-columns:repeat(4,1fr);gap:20px">
        <div class="stat"><div class="num">99.99%</div><div class="label">Uptime SLA</div></div>
        <div class="stat"><div class="num">18</div><div class="label">Global Regions</div></div>
        <div class="stat"><div class="num">&lt;60s</div><div class="label">Deployment Time</div></div>
        <div class="stat"><div class="num">∞</div><div class="label">Bandwidth</div></div>
    </div>
</section>

<!-- Features -->
<section class="features" id="features">
    <div class="container">
        <h2>Infrastructure That Scales With You</h2>
        <div class="feat-grid">
            <div class="feat-card">
                <div class="icon">⚡</div>
                <h3>NVMe SSD Storage</h3>
                <p>All plans include NVMe solid-state drives. Up to 10x faster than traditional SSD. Your apps load instantly.</p>
            </div>
            <div class="feat-card">
                <div class="icon">🛡</div>
                <h3>Anti-DDoS Protection</h3>
                <p>Enterprise DDoS mitigation included free on every plan. Your server stays online no matter what.</p>
            </div>
            <div class="feat-card">
                <div class="icon">🌍</div>
                <h3>18 Global Regions</h3>
                <p>Deploy close to your users. Data centers across North America, Europe, and Asia-Pacific.</p>
            </div>
            <div class="feat-card">
                <div class="icon">🤖</div>
                <h3>AI-Powered Panel</h3>
                <p>GoHostMe panel with 46+ management endpoints. Monitor resources, scale on demand, deploy with one click.</p>
            </div>
            <div class="feat-card">
                <div class="icon">🔒</div>
                <h3>Full Root Access</h3>
                <p>Complete control over your server. Install anything. SSH, SFTP, and console access included.</p>
            </div>
            <div class="feat-card">
                <div class="icon">📊</div>
                <h3>Real-Time Monitoring</h3>
                <p>CPU, RAM, disk, and network monitoring built into your dashboard. Alerts for anomalies — powered by AI.</p>
            </div>
            <div class="feat-card">
                <div class="icon">💾</div>
                <h3>Automated Backups</h3>
                <p>Daily automated backups with one-click restore. Additional backup storage from $9.99/mo.</p>
            </div>
            <div class="feat-card">
                <div class="icon">🔄</div>
                <h3>Instant Scaling</h3>
                <p>Upgrade CPU, RAM, and storage without downtime. Scale up for traffic spikes, scale down to save money.</p>
            </div>
            <div class="feat-card">
                <div class="icon">🌐</div>
                <h3>Unmetered Bandwidth</h3>
                <p>No bandwidth caps or overage charges. Use as much traffic as you need. Truly unlimited.</p>
            </div>
        </div>
    </div>
</section>

<!-- Comparison -->
<section class="compare" id="compare">
    <div class="container">
        <h2>Why GoHostMe Beats the Rest</h2>
        <table class="compare-table">
            <thead>
            <tr><th>Feature</th><th>GoHostMe</th><th>DigitalOcean</th><th>Linode</th><th>AWS Lightsail</th></tr>
            </thead>
            <tbody>
            <tr><td>4 vCPU / 8GB Starter</td><td class="us">$59.99/mo</td><td class="them">$48/mo</td><td class="them">$36/mo</td><td class="them">$40/mo</td></tr>
            <tr><td>NVMe SSD</td><td class="us">✓ All Plans</td><td class="them">✓</td><td class="them">✓</td><td class="them">SSD only</td></tr>
            <tr><td>DDoS Protection</td><td class="us">✓ Included</td><td class="them">$10+/mo extra</td><td class="them">Limited</td><td class="them">Basic only</td></tr>
            <tr><td>AI Management Panel</td><td class="us">✓ 46+ Endpoints</td><td class="them">✗</td><td class="them">✗</td><td class="them">✗</td></tr>
            <tr><td>Built-in IDE</td><td class="us">✓ Alfred IDE</td><td class="them">✗</td><td class="them">✗</td><td class="them">✗</td></tr>
            <tr><td>AI Support Agent</td><td class="us">✓ 24/7</td><td class="them">Chat only</td><td class="them">Ticket only</td><td class="them">$29+ Plans</td></tr>
            <tr><td>Bandwidth</td><td class="us">Unmetered</td><td class="them">4-8TB cap</td><td class="them">4-8TB cap</td><td class="them">3-5TB cap</td></tr>
            <tr><td>AI Voice Agent Add-on</td><td class="us">✓ from $29/mo</td><td class="them">✗</td><td class="them">✗</td><td class="them">✗</td></tr>
            </tbody>
        </table>
    </div>
</section>

<!-- Pricing -->
<section class="pricing" id="pricing">
    <div class="container">
        <h2>Cloud VPS Plans</h2>
        <div class="sub">Full root access. NVMe SSD. DDoS protection. Deploy in under 60 seconds.</div>
        <div class="price-grid">
            <div class="price-card">
                <div class="tier">Starter</div>
                <div class="amount"><sup>$</sup>29<sup>.99/mo</sup></div>
                <div class="period">For small projects & dev</div>
                <ul>
                    <li>2 vCPU Cores</li>
                    <li>4 GB RAM</li>
                    <li>80 GB NVMe SSD</li>
                    <li>Unmetered Bandwidth</li>
                    <li>1 IPv4 Address</li>
                    <li>Anti-DDoS Protection</li>
                    <li>GoHostMe Panel</li>
                </ul>
                <a href="https://gositeme.com/cart.php?a=add&pid=101" class="cta-btn" style="width:100%;text-align:center">Deploy Now</a>
            </div>
            <div class="price-card featured">
                <div class="tier">Business</div>
                <div class="amount"><sup>$</sup>59<sup>.99/mo</sup></div>
                <div class="period">Best for growing businesses</div>
                <ul>
                    <li>4 vCPU Cores</li>
                    <li>8 GB RAM</li>
                    <li>160 GB NVMe SSD</li>
                    <li>Unmetered Bandwidth</li>
                    <li>1 IPv4 + /64 IPv6</li>
                    <li>Anti-DDoS Protection</li>
                    <li>GoHostMe Panel + Alfred IDE</li>
                </ul>
                <a href="https://gositeme.com/cart.php?a=add&pid=102" class="cta-btn" style="width:100%;text-align:center">Deploy Now</a>
            </div>
            <div class="price-card">
                <div class="tier">Pro</div>
                <div class="amount"><sup>$</sup>99<sup>.99/mo</sup></div>
                <div class="period">For production workloads</div>
                <ul>
                    <li>8 vCPU Cores</li>
                    <li>16 GB RAM</li>
                    <li>320 GB NVMe SSD</li>
                    <li>Unmetered Bandwidth</li>
                    <li>2 IPv4 + /64 IPv6</li>
                    <li>Anti-DDoS Pro</li>
                    <li>Priority Support</li>
                </ul>
                <a href="https://gositeme.com/cart.php?a=add&pid=103" class="cta-btn" style="width:100%;text-align:center">Deploy Now</a>
            </div>
            <div class="price-card">
                <div class="tier">Enterprise</div>
                <div class="amount"><sup>$</sup>179<sup>.99/mo</sup></div>
                <div class="period">Maximum performance</div>
                <ul>
                    <li>16 vCPU Cores</li>
                    <li>32 GB RAM</li>
                    <li>640 GB NVMe SSD</li>
                    <li>Unmetered Bandwidth</li>
                    <li>4 IPv4 + /64 IPv6</li>
                    <li>Anti-DDoS Advanced</li>
                    <li>Dedicated Account Manager</li>
                </ul>
                <a href="https://gositeme.com/cart.php?a=add&pid=104" class="cta-btn" style="width:100%;text-align:center">Deploy Now</a>
            </div>
        </div>
    </div>
</section>

<!-- Add-ons -->
<section class="addons" id="addons">
    <div class="container">
        <h2>Powerful Add-Ons</h2>
        <div class="addon-grid">
            <a href="https://gositeme.com/cart.php?a=add&pid=113" class="addon-card"><div class="icon">🌐</div><div class="name">Failover IP</div><div class="price">$9.99/mo</div></a>
            <a href="https://gositeme.com/cart.php?a=add&pid=114" class="addon-card"><div class="icon">⚖️</div><div class="name">Load Balancer</div><div class="price">$29.99/mo</div></a>
            <a href="https://gositeme.com/cart.php?a=add&pid=116" class="addon-card"><div class="icon">🛡</div><div class="name">DDoS Protection Pro</div><div class="price">$49.99/mo</div></a>
            <a href="https://gositeme.com/cart.php?a=add&pid=118" class="addon-card"><div class="icon">💾</div><div class="name">Backup — Basic</div><div class="price">$9.99/mo</div></a>
            <a href="https://gositeme.com/cart.php?a=add&pid=121" class="addon-card"><div class="icon">📊</div><div class="name">Monitoring</div><div class="price">$4.99/mo</div></a>
            <a href="https://gositeme.com/cart.php?a=add&pid=125" class="addon-card"><div class="icon">🔧</div><div class="name">Server Management</div><div class="price">$49.99/mo</div></a>
            <a href="https://gositeme.com/cart.php?a=add&pid=110" class="addon-card"><div class="icon">🔐</div><div class="name">VPN — Personal</div><div class="price">$4.99/mo</div></a>
            <a href="https://gositeme.com/cart.php?a=add&pid=115" class="addon-card"><div class="icon">📡</div><div class="name">Managed DNS</div><div class="price">$2.99/mo</div></a>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta-section">
    <div class="container">
        <h2>Deploy Your VPS in 60 Seconds</h2>
        <p>Join <?= number_format($clientCount) ?>+ businesses running on GoHostMe Cloud. No hidden fees. Cancel anytime.</p>
        <a href="https://gositeme.com/cart.php?a=add&pid=101" class="cta-btn cta-btn-lg">Start at $29.99/mo →</a>
    </div>
</section>

<footer>
    <div class="container">
        <p>&copy; <?= date('Y') ?> GoSiteMe — GoHostMe Cloud Hosting &nbsp;|&nbsp; <a href="https://gositeme.com/">Home</a> &nbsp;|&nbsp; <a href="https://gositeme.com/voice-ai/">AI Voice</a> &nbsp;|&nbsp; <a href="https://gositeme.com/ide/">Alfred IDE</a> &nbsp;|&nbsp; <a href="https://gositeme.com/tos.php">Terms</a> &nbsp;|&nbsp; <a href="https://gositeme.com/privacy.php">Privacy</a></p>
    </div>
</footer>
</body>
</html>
