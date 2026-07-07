<?php
/**
 * GOSITEME INFRASTRUCTURE CAPABILITIES
 * =====================================
 * Team-accessible view of our network capacity.
 * Shows WHAT we can do — not HOW MUCH we pay.
 * 
 * Access: Any logged-in GoSiteMe team member
 * Commander sees everything at /docs/ovh-intelligence
 */
require_once __DIR__ . '/../includes/auth-gate.inc.php';

// Any authenticated user can view this (team access)
// Commander's private report is at /docs/ovh-intelligence

$isCommander = ((int)($_SESSION['client_id'] ?? 0) === 33);
$userName = htmlspecialchars($_SESSION['firstname'] ?? 'Team Member', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Infrastructure Capabilities — GoSiteMe</title>
    <meta name="robots" content="noindex, nofollow">
    <link href="/assets/vendor/fonts/space-grotesk/space-grotesk.css" />
    <link rel="stylesheet" href="/assets/vendor/fonts/jetbrains-mono/jetbrains-mono.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/vendor/fontawesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #0a0a1a; color: #c8d0e7; font-family: 'Space Grotesk', sans-serif; line-height: 1.8; }
        .container { max-width: 1100px; margin: 0 auto; padding: 20px 24px; }

        .header { padding: 60px 0 30px; text-align: center; border-bottom: 1px solid rgba(125,0,255,0.2); margin-bottom: 40px; }
        .header h1 { font-size: clamp(1.8rem, 3.5vw, 2.8rem); font-weight: 900; background: linear-gradient(135deg, #fff, #c084fc, #00D4FF); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 8px; }
        .header .subtitle { color: #7D00FF; font-size: 0.85rem; letter-spacing: 2px; text-transform: uppercase; font-weight: 700; }
        .header .welcome { color: #a8b2d1; font-size: 0.95rem; margin-top: 12px; }

        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; margin-bottom: 40px; }
        .card { background: rgba(255,255,255,0.03); border: 1px solid rgba(125,0,255,0.15); border-radius: 16px; padding: 28px; transition: all 0.3s; }
        .card:hover { border-color: rgba(125,0,255,0.4); transform: translateY(-2px); box-shadow: 0 8px 32px rgba(125,0,255,0.1); }
        .card h2 { font-size: 1.15rem; font-weight: 700; color: #fff; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; }
        .card h2 i { color: #7D00FF; font-size: 1.1rem; }
        .card .stat { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.04); }
        .card .stat:last-child { border-bottom: none; }
        .card .stat-label { color: #8892b0; font-size: 0.85rem; }
        .card .stat-value { color: #00D4FF; font-weight: 600; font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; }

        .section-title { font-size: 1.4rem; font-weight: 700; color: #fff; margin: 48px 0 20px; padding-bottom: 12px; border-bottom: 1px solid rgba(125,0,255,0.15); display: flex; align-items: center; gap: 12px; }
        .section-title i { color: #7D00FF; }

        .region-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 12px; margin-bottom: 40px; }
        .region { background: rgba(125,0,255,0.06); border: 1px solid rgba(125,0,255,0.12); border-radius: 10px; padding: 14px; text-align: center; font-size: 0.82rem; transition: all 0.3s; }
        .region:hover { border-color: #7D00FF; background: rgba(125,0,255,0.12); }
        .region .flag { font-size: 1.4rem; margin-bottom: 4px; }
        .region .name { color: #fff; font-weight: 600; }
        .region .code { color: #8892b0; font-size: 0.75rem; font-family: 'JetBrains Mono', monospace; }

        .tier-table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
        .tier-table th { text-align: left; padding: 12px 16px; background: rgba(125,0,255,0.1); color: #c084fc; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; }
        .tier-table td { padding: 12px 16px; border-bottom: 1px solid rgba(255,255,255,0.04); font-size: 0.85rem; }
        .tier-table tr:hover td { background: rgba(125,0,255,0.04); }
        .tier-table .available { color: #22c55e; font-weight: 600; }
        .tier-table .limited { color: #f59e0b; font-weight: 600; }

        .capability-list { list-style: none; padding: 0; }
        .capability-list li { padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.04); display: flex; align-items: center; gap: 10px; font-size: 0.9rem; }
        .capability-list li i { color: #22c55e; font-size: 0.75rem; width: 18px; text-align: center; }

        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase; }
        .badge-live { background: rgba(34,197,94,0.15); color: #22c55e; border: 1px solid rgba(34,197,94,0.3); }
        .badge-ready { background: rgba(0,212,255,0.15); color: #00D4FF; border: 1px solid rgba(0,212,255,0.3); }
        .badge-gpu { background: rgba(245,158,11,0.15); color: #f59e0b; border: 1px solid rgba(245,158,11,0.3); }

        .dept-access { background: rgba(125,0,255,0.06); border: 1px solid rgba(125,0,255,0.2); border-radius: 16px; padding: 28px; margin: 40px 0; }
        .dept-access h3 { color: #fff; font-size: 1.1rem; margin-bottom: 16px; }
        .dept-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; }
        .dept { background: rgba(255,255,255,0.03); border-radius: 10px; padding: 16px; border-left: 3px solid #7D00FF; }
        .dept h4 { color: #c084fc; font-size: 0.9rem; margin-bottom: 6px; }
        .dept p { color: #8892b0; font-size: 0.8rem; line-height: 1.5; }

        .commander-note { background: rgba(245,158,11,0.06); border: 1px solid rgba(245,158,11,0.2); border-radius: 12px; padding: 20px; margin-top: 20px; display: flex; align-items: center; gap: 12px; }
        .commander-note i { color: #f59e0b; font-size: 1.2rem; }
        .commander-note p { color: #a8b2d1; font-size: 0.85rem; }
        .commander-note a { color: #f59e0b; text-decoration: none; font-weight: 600; }

        .footer { text-align: center; padding: 40px 0 30px; border-top: 1px solid rgba(125,0,255,0.1); margin-top: 60px; color: #4a5568; font-size: 0.8rem; }

        @media (max-width: 768px) {
            .grid { grid-template-columns: 1fr; }
            .region-grid { grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); }
            .tier-table { font-size: 0.78rem; }
        }
    </style>
</head>
<body>
<div class="container">

    <div class="header">
        <div class="subtitle">Department Resource Guide</div>
        <h1><i class="fas fa-network-wired"></i> Infrastructure Capabilities</h1>
        <p class="welcome">Welcome, <?= $userName ?>. Here's what our network can do.</p>
    </div>

    <!-- ===== QUICK OVERVIEW ===== -->
    <div class="grid">
        <div class="card">
            <h2><i class="fas fa-server"></i> Primary Infrastructure</h2>
            <div class="stat"><span class="stat-label">Provider</span><span class="stat-value">OVHcloud (Canada)</span></div>
            <div class="stat"><span class="stat-label">Data Sovereignty</span><span class="stat-value">Canadian-hosted</span></div>
            <div class="stat"><span class="stat-label">Dedicated Server</span><span class="stat-value"><span class="badge badge-live">LIVE</span></span></div>
            <div class="stat"><span class="stat-label">Processor</span><span class="stat-value">Intel Xeon-E 2386G</span></div>
            <div class="stat"><span class="stat-label">Cores / Threads</span><span class="stat-value">6C / 12T @ 3.5 GHz</span></div>
            <div class="stat"><span class="stat-label">Memory</span><span class="stat-value">32 GB DDR4 ECC</span></div>
            <div class="stat"><span class="stat-label">Storage</span><span class="stat-value">2 × 4 TB SATA (RAID)</span></div>
            <div class="stat"><span class="stat-label">Bandwidth</span><span class="stat-value">Unlimited @ 1 Gbps</span></div>
        </div>

        <div class="card">
            <h2><i class="fas fa-cloud"></i> Cloud Capacity</h2>
            <div class="stat"><span class="stat-label">Cloud Project</span><span class="stat-value"><span class="badge badge-ready">PROVISIONED</span></span></div>
            <div class="stat"><span class="stat-label">Available Regions</span><span class="stat-value">18 worldwide</span></div>
            <div class="stat"><span class="stat-label">Instance Types</span><span class="stat-value">176+ flavors</span></div>
            <div class="stat"><span class="stat-label">Max CPU Config</span><span class="stat-value">160 vCPU / 640 GB RAM</span></div>
            <div class="stat"><span class="stat-label">GPU Available</span><span class="stat-value"><span class="badge badge-gpu">18 GPU TYPES</span></span></div>
            <div class="stat"><span class="stat-label">OS Support</span><span class="stat-value">Linux + Windows</span></div>
            <div class="stat"><span class="stat-label">Bare Metal Cloud</span><span class="stat-value">3 tiers (S/M/L)</span></div>
            <div class="stat"><span class="stat-label">Private Network</span><span class="stat-value">vRack ready</span></div>
        </div>

        <div class="card">
            <h2><i class="fas fa-shield-halved"></i> Network & Security</h2>
            <div class="stat"><span class="stat-label">DDoS Protection</span><span class="stat-value"><span class="badge badge-live">ACTIVE</span></span></div>
            <div class="stat"><span class="stat-label">Failover IPs</span><span class="stat-value">256 available</span></div>
            <div class="stat"><span class="stat-label">IPv6</span><span class="stat-value">/64 block assigned</span></div>
            <div class="stat"><span class="stat-label">IPMI Access</span><span class="stat-value">Out-of-band mgmt</span></div>
            <div class="stat"><span class="stat-label">SSL/TLS</span><span class="stat-value">Let's Encrypt auto</span></div>
            <div class="stat"><span class="stat-label">Firewall</span><span class="stat-value">iptables + fail2ban</span></div>
            <div class="stat"><span class="stat-label">WAF</span><span class="stat-value">ModSecurity rules</span></div>
            <div class="stat"><span class="stat-label">Encryption</span><span class="stat-value">AES-256-GCM vault</span></div>
        </div>
    </div>

    <!-- ===== GLOBAL REGIONS ===== -->
    <h2 class="section-title"><i class="fas fa-globe"></i> Global Cloud Regions</h2>
    <p style="color: #8892b0; margin-bottom: 20px; font-size: 0.9rem;">Deploy workloads in any of these 18 regions on-demand. Spin up in minutes.</p>
    <div class="region-grid">
        <div class="region"><div class="flag">🇨🇦</div><div class="name">Beauharnois</div><div class="code">BHS5</div></div>
        <div class="region"><div class="flag">🇫🇷</div><div class="name">Gravelines</div><div class="code">GRA5 / GRA7 / GRA9 / GRA11</div></div>
        <div class="region"><div class="flag">🇬🇧</div><div class="name">London</div><div class="code">UK1</div></div>
        <div class="region"><div class="flag">🇩🇪</div><div class="name">Frankfurt</div><div class="code">DE1</div></div>
        <div class="region"><div class="flag">🇵🇱</div><div class="name">Warsaw</div><div class="code">WAW1</div></div>
        <div class="region"><div class="flag">🇺🇸</div><div class="name">Hillsboro</div><div class="code">US-WEST-OR-1</div></div>
        <div class="region"><div class="flag">🇺🇸</div><div class="name">Virginia</div><div class="code">US-EAST-VA-1 / VA-2</div></div>
        <div class="region"><div class="flag">🇸🇬</div><div class="name">Singapore</div><div class="code">SGP1</div></div>
        <div class="region"><div class="flag">🇦🇺</div><div class="name">Sydney</div><div class="code">SYD1</div></div>
        <div class="region"><div class="flag">🇮🇳</div><div class="name">Mumbai</div><div class="code">IN-WEST-MU-1</div></div>
        <div class="region"><div class="flag">🇪🇸</div><div class="name">Madrid</div><div class="code">MAD1</div></div>
        <div class="region"><div class="flag">🇨🇦</div><div class="name">Toronto</div><div class="code">CA-EAST-TO-1</div></div>
    </div>

    <!-- ===== GPU TIERS ===== -->
    <h2 class="section-title"><i class="fas fa-microchip"></i> GPU & Compute Tiers</h2>
    <p style="color: #8892b0; margin-bottom: 20px; font-size: 0.9rem;">Available GPU instances for AI/ML workloads, rendering, and inference. Pay hourly or monthly.</p>
    <table class="tier-table">
        <thead>
            <tr><th>Tier</th><th>vCPU</th><th>RAM</th><th>Storage</th><th>Bandwidth</th><th>Status</th></tr>
        </thead>
        <tbody>
            <tr><td><strong>T1-45</strong> (Entry GPU)</td><td>8</td><td>45 GB</td><td>400 GB NVMe</td><td>2 Gbps</td><td><span class="available">Available</span></td></tr>
            <tr><td><strong>T2-45</strong> (Mid GPU)</td><td>15</td><td>45 GB</td><td>400 GB NVMe</td><td>2 Gbps</td><td><span class="available">Available</span></td></tr>
            <tr><td><strong>T1-90</strong> (Performance)</td><td>16</td><td>90 GB</td><td>800 GB NVMe</td><td>4 Gbps</td><td><span class="available">Available</span></td></tr>
            <tr><td><strong>T2-90</strong> (High Perf)</td><td>30</td><td>90 GB</td><td>800 GB NVMe</td><td>4 Gbps</td><td><span class="available">Available</span></td></tr>
            <tr><td><strong>T1-180</strong> (Enterprise)</td><td>32</td><td>180 GB</td><td>400 GB NVMe</td><td>10 Gbps</td><td><span class="limited">Limited</span></td></tr>
            <tr><td><strong>T2-180</strong> (Max GPU)</td><td>60</td><td>180 GB</td><td>500 GB NVMe</td><td>10 Gbps</td><td><span class="available">Available</span></td></tr>
        </tbody>
    </table>

    <!-- ===== GENERAL COMPUTE ===== -->
    <h2 class="section-title"><i class="fas fa-layer-group"></i> General Compute Tiers</h2>
    <table class="tier-table">
        <thead>
            <tr><th>Series</th><th>Purpose</th><th>vCPU Range</th><th>RAM Range</th><th>Bare Metal</th><th>Status</th></tr>
        </thead>
        <tbody>
            <tr><td><strong>B2/B3</strong></td><td>General Purpose (balanced)</td><td>2 – 160</td><td>7 – 640 GB</td><td>—</td><td><span class="available">Available</span></td></tr>
            <tr><td><strong>C2/C3</strong></td><td>CPU Optimized (compute)</td><td>2 – 160</td><td>4 – 320 GB</td><td>—</td><td><span class="available">Available</span></td></tr>
            <tr><td><strong>R2/R3</strong></td><td>RAM Optimized (memory)</td><td>2 – 128</td><td>15 – 1024 GB</td><td>—</td><td><span class="available">Available</span></td></tr>
            <tr><td><strong>D2</strong></td><td>Disk Intensive (storage)</td><td>2 – 4</td><td>4 – 8 GB</td><td>—</td><td><span class="available">Available</span></td></tr>
            <tr><td><strong>BM-S/M/L</strong></td><td>Bare Metal Cloud</td><td>8 – 32</td><td>32 – 128 GB</td><td>960 GB SSD</td><td><span class="available">Available</span></td></tr>
        </tbody>
    </table>

    <!-- ===== PLATFORM SERVICES ===== -->
    <h2 class="section-title"><i class="fas fa-cubes"></i> Platform Services Running</h2>
    <div class="grid">
        <div class="card">
            <h2><i class="fas fa-globe"></i> Web & Hosting</h2>
            <ul class="capability-list">
                <li><i class="fas fa-check"></i> Apache + PHP 8.3 (FastCGI)</li>
                <li><i class="fas fa-check"></i> DirectAdmin control panel</li>
                <li><i class="fas fa-check"></i> MySQL / MariaDB databases</li>
                <li><i class="fas fa-check"></i> Multi-domain hosting (15+ sites)</li>
                <li><i class="fas fa-check"></i> Sovereign email (Dovecot/Postfix)</li>
                <li><i class="fas fa-check"></i> Automated SSL certificates</li>
            </ul>
        </div>
        <div class="card">
            <h2><i class="fas fa-robot"></i> AI & Voice</h2>
            <ul class="capability-list">
                <li><i class="fas fa-check"></i> Alfred AI — autonomous assistant</li>
                <li><i class="fas fa-check"></i> VAPI voice pipeline integration</li>
                <li><i class="fas fa-check"></i> Callture PBX (multi-extension)</li>
                <li><i class="fas fa-check"></i> Real-time WebSocket channels</li>
                <li><i class="fas fa-check"></i> Toll-free: (833) 467-4836</li>
                <li><i class="fas fa-check"></i> AI-powered call routing</li>
            </ul>
        </div>
        <div class="card">
            <h2><i class="fas fa-code"></i> Developer Tools</h2>
            <ul class="capability-list">
                <li><i class="fas fa-check"></i> GoCodeMe IDE (browser-based)</li>
                <li><i class="fas fa-check"></i> Web terminal access</li>
                <li><i class="fas fa-check"></i> Git version control</li>
                <li><i class="fas fa-check"></i> API endpoints (REST)</li>
                <li><i class="fas fa-check"></i> Playwright automation</li>
                <li><i class="fas fa-check"></i> Chrome headless browser</li>
            </ul>
        </div>
        <div class="card">
            <h2><i class="fas fa-lock"></i> Security & Compliance</h2>
            <ul class="capability-list">
                <li><i class="fas fa-check"></i> AES-256-GCM credential vault</li>
                <li><i class="fas fa-check"></i> fail2ban intrusion prevention</li>
                <li><i class="fas fa-check"></i> SQL injection / XSS protection</li>
                <li><i class="fas fa-check"></i> HSTS + CSP security headers</li>
                <li><i class="fas fa-check"></i> Commander-only access controls</li>
                <li><i class="fas fa-check"></i> Code integrity verification</li>
            </ul>
        </div>
    </div>

    <!-- ===== DEPARTMENT USE CASES ===== -->
    <div class="dept-access">
        <h3><i class="fas fa-sitemap" style="color: #7D00FF;"></i> Department Use Cases</h3>
        <p style="color: #8892b0; margin-bottom: 20px; font-size: 0.85rem;">How each department can leverage our infrastructure for growth:</p>
        <div class="dept-grid">
            <div class="dept">
                <h4><i class="fas fa-bullhorn"></i> Marketing & Sales</h4>
                <p>Spin up landing pages in any region. A/B test with per-region deployments. Use GPU instances for AI-generated content and image creation.</p>
            </div>
            <div class="dept">
                <h4><i class="fas fa-headset"></i> Customer Success</h4>
                <p>Alfred AI handles voice + chat. Analyze call transcripts with GPU compute. Deploy support bots across multiple channels simultaneously.</p>
            </div>
            <div class="dept">
                <h4><i class="fas fa-code-branch"></i> Engineering</h4>
                <p>GoCodeMe IDE for team development. Spin up staging environments on-demand across 18 regions. Bare metal for heavy build/test pipelines.</p>
            </div>
            <div class="dept">
                <h4><i class="fas fa-brain"></i> AI Research</h4>
                <p>T1/T2 GPU instances for model training. Up to 180 GB VRAM configs. Run inference endpoints close to users via regional deployment.</p>
            </div>
            <div class="dept">
                <h4><i class="fas fa-chart-line"></i> Operations</h4>
                <p>Monitor uptime across all 15+ hosted domains. vRack private networking for internal services. 256 failover IPs for redundancy.</p>
            </div>
            <div class="dept">
                <h4><i class="fas fa-store"></i> Reseller / White-Label</h4>
                <p>Full hosting stack ready for resale. White-label control panel. Dedicated server + cloud hybrid for client isolation.</p>
            </div>
        </div>
    </div>

    <!-- ===== GROWTH TIMELINE ===== -->
    <h2 class="section-title"><i class="fas fa-rocket"></i> Growth Capacity</h2>
    <div class="grid">
        <div class="card">
            <h2><i class="fas fa-seedling"></i> Current (Phase 1)</h2>
            <ul class="capability-list">
                <li><i class="fas fa-check"></i> 1 dedicated server — all services live</li>
                <li><i class="fas fa-check"></i> Cloud project provisioned & ready</li>
                <li><i class="fas fa-check"></i> 15+ domains managed</li>
                <li><i class="fas fa-check"></i> Full AI voice pipeline active</li>
                <li><i class="fas fa-check"></i> Sovereign email running</li>
            </ul>
        </div>
        <div class="card">
            <h2><i class="fas fa-chart-bar"></i> Near-Term (Phase 2)</h2>
            <ul class="capability-list">
                <li><i class="fas fa-arrow-right"></i> Spin up first cloud instances</li>
                <li><i class="fas fa-arrow-right"></i> GPU instance for AI training</li>
                <li><i class="fas fa-arrow-right"></i> Multi-region deployment</li>
                <li><i class="fas fa-arrow-right"></i> Client hosting onboarding</li>
                <li><i class="fas fa-arrow-right"></i> Team workspace provisioning</li>
            </ul>
        </div>
        <div class="card">
            <h2><i class="fas fa-mountain-sun"></i> Scale (Phase 3)</h2>
            <ul class="capability-list">
                <li><i class="fas fa-star"></i> OVH Partner/Reseller program</li>
                <li><i class="fas fa-star"></i> White-label hosting for clients</li>
                <li><i class="fas fa-star"></i> Multi-server cluster (vRack)</li>
                <li><i class="fas fa-star"></i> Edge deployments (SGP/SYD/IN)</li>
                <li><i class="fas fa-star"></i> Enterprise GPU fleet</li>
            </ul>
        </div>
    </div>

    <?php if ($isCommander): ?>
    <div class="commander-note">
        <i class="fas fa-crown"></i>
        <p>Commander — this is the <strong>team view</strong>. Your full intelligence report with billing, account details, and strategic analysis is at <a href="/docs/ovh-intelligence">/docs/ovh-intelligence</a>.</p>
    </div>
    <?php endif; ?>

    <div class="footer">
        <p>GoSiteMe Infrastructure Capabilities — Internal Use Only</p>
        <p style="margin-top: 4px;">Powered by OVHcloud Canada &bull; Data Sovereign &bull; Updated March 2026</p>
    </div>

</div>
</body>
</html>
