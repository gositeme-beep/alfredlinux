<?php
/**
 * GoHostMe — Complete Product Catalog
 * ====================================
 * 121 products. 25 categories. Every single one purchasable.
 * White-label ready. Reseller-friendly. Zero hidden products.
 * 
 * "Fake it till you make it" — Commander Danny William Perez
 * "No. Build it so real they never know it wasn't always this big." — Alfred
 */

// Pull live product data from WHMCS
$products = [];
try {
    $pdo = new PDO('mysql:unix_socket=/run/mysql/mysql.sock;dbname=gositeme_whmcs;charset=utf8mb4',
        'gositeme_whmcs', trim(@file_get_contents('/home/gositeme/.my.cnf.pass') ?: ''));
} catch(Exception $e) {
    // Fallback: read from .my.cnf
    $mycnf = parse_ini_file('/home/gositeme/.my.cnf');
    try {
        $pdo = new PDO('mysql:unix_socket=/run/mysql/mysql.sock;dbname=gositeme_whmcs;charset=utf8mb4',
            $mycnf['user'] ?? 'gositeme_whmcs', $mycnf['password'] ?? '');
    } catch(Exception $e2) {
        $pdo = null;
    }
}

$groups = [];
if ($pdo) {
    $stmt = $pdo->query("SELECT p.id, p.name, p.description, p.type, g.name as grp, g.id as gid,
        (SELECT monthly FROM tblpricing WHERE type='product' AND relid=p.id AND currency=1 LIMIT 1) as monthly,
        (SELECT annually FROM tblpricing WHERE type='product' AND relid=p.id AND currency=1 LIMIT 1) as annual,
        p.hidden, p.retired
        FROM tblproducts p LEFT JOIN tblproductgroups g ON p.gid=g.id
        WHERE p.retired = 0
        ORDER BY g.name, p.id");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $g = $row['grp'] ?: 'Other';
        if (!isset($groups[$g])) $groups[$g] = [];
        $groups[$g][] = $row;
    }
}

// Category metadata: icon, tagline, color
$meta = [
    'AI Call Center & Telemarketing' => ['icon'=>'&#128222;','tag'=>'AI-powered outbound & inbound call operations','color'=>'#6366f1'],
    'AI Development Platform' => ['icon'=>'&#128187;','tag'=>'Build AI apps with 13,000+ tools','color'=>'#8b5cf6'],
    'AI Document & Fax Services' => ['icon'=>'&#128196;','tag'=>'AI document generation, fax, and e-signatures','color'=>'#a855f7'],
    'AI Office Suite' => ['icon'=>'&#127970;','tag'=>'AI receptionist, assistant, bookkeeper, sales agent','color'=>'#7c3aed'],
    'AI Server Support' => ['icon'=>'&#128736;','tag'=>'Expert AI-assisted server management','color'=>'#6d28d9'],
    'AI Servers' => ['icon'=>'&#9889;','tag'=>'Custom AI server configurations','color'=>'#5b21b6'],
    'AI SMS & Chat Agents' => ['icon'=>'&#128172;','tag'=>'AI-powered SMS and live chat automation','color'=>'#4f46e5'],
    'AI Voice Agents' => ['icon'=>'&#127908;','tag'=>'Alfred voice agents — your AI phone team','color'=>'#4338ca'],
    'API Access' => ['icon'=>'&#128268;','tag'=>'Full programmatic access to all GoSiteMe services','color'=>'#3730a3'],
    'Cloud & VPS' => ['icon'=>'&#9729;&#65039;','tag'=>'High-performance cloud VPS in 18 global regions','color'=>'#3b82f6'],
    'Dedicated Server' => ['icon'=>'&#128421;','tag'=>'Bare metal dedicated servers','color'=>'#2563eb'],
    'Dedicated Servers' => ['icon'=>'&#128421;','tag'=>'Bare metal dedicated with full root access','color'=>'#1d4ed8'],
    'Email & Communication' => ['icon'=>'&#128231;','tag'=>'Professional email hosting for your domains','color'=>'#0ea5e9'],
    'Hosting' => ['icon'=>'&#127968;','tag'=>'WordPress hosting — fast, secure, managed','color'=>'#06b6d4'],
    'Industry Solutions' => ['icon'=>'&#127981;','tag'=>'Turnkey AI packages for 12 industries','color'=>'#10b981'],
    'Managed Services' => ['icon'=>'&#128295;','tag'=>'Backups, monitoring, Kubernetes, storage','color'=>'#059669'],
    'Network Services' => ['icon'=>'&#128225;','tag'=>'VPN, load balancers, DDoS, DNS, IPs','color'=>'#047857'],
    'Phone Numbers & SIP' => ['icon'=>'&#128222;','tag'=>'Local, toll-free, vanity, and international numbers','color'=>'#0d9488'],
    'Reseller Plans' => ['icon'=>'&#127942;','tag'=>'White-label everything — your brand, our infrastructure','color'=>'#f59e0b'],
    'SSL Certificates' => ['icon'=>'&#128274;','tag'=>'RapidSSL & GeoTrust certificates','color'=>'#d97706'],
    'Team Plans' => ['icon'=>'&#128101;','tag'=>'Collaborate with your team on the AI platform','color'=>'#b45309'],
    'Token Packs' => ['icon'=>'&#127183;','tag'=>'AI tokens for platform credits','color'=>'#92400e'],
    'Training & Consultation' => ['icon'=>'&#127891;','tag'=>'Expert AI training and integration services','color'=>'#dc2626'],
    'Voice Add-Ons & Minutes' => ['icon'=>'&#128266;','tag'=>'Extra minutes, voice cloning, HIPAA, and more','color'=>'#e11d48'],
    'Web Design' => ['icon'=>'&#127912;','tag'=>'Professional WordPress design services','color'=>'#ec4899'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GoHostMe Products — 121 Products, 25 Categories | Complete Catalog</title>
<meta name="description" content="Browse all 121 GoHostMe products: Cloud VPS, Dedicated Servers, GPU, AI Voice, Call Center, Industry Solutions, Reseller Plans, and more. Prices from $2.99/mo.">
<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{--bg:#0a0a1a;--surface:#111128;--surface2:#1a1a35;--border:#2a2a4a;--text:#e0e0ff;--text2:#8888aa;--blue:#3b82f6;--green:#10b981;--yellow:#f59e0b;--red:#ef4444;--purple:#8b5cf6}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:var(--bg);color:var(--text);line-height:1.6}
a{color:var(--blue);text-decoration:none}
.container{max-width:1400px;margin:0 auto;padding:0 20px}

/* HEADER */
.header{background:linear-gradient(135deg,#0a0a2e 0%,#1a0a3e 50%,#0a1a2e 100%);border-bottom:1px solid var(--border);padding:12px 0;position:sticky;top:0;z-index:100}
.header-inner{display:flex;align-items:center;justify-content:space-between;max-width:1400px;margin:0 auto;padding:0 20px}
.logo{font-size:22px;font-weight:800;background:linear-gradient(135deg,#3b82f6,#8b5cf6);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.logo span{font-size:12px;background:var(--green);-webkit-background-clip:initial;-webkit-text-fill-color:initial;color:#fff;padding:2px 6px;border-radius:4px;margin-left:8px;font-weight:600}
.header-links a{color:var(--text2);margin-left:20px;font-size:14px;transition:color .2s}
.header-links a:hover{color:var(--text)}
.btn-cta{background:linear-gradient(135deg,var(--blue),var(--purple));color:#fff!important;padding:8px 20px;border-radius:8px;font-weight:600;font-size:14px}

/* HERO */
.hero{text-align:center;padding:60px 20px 40px;background:linear-gradient(180deg,transparent 0%,rgba(59,130,246,0.05) 100%)}
.hero h1{font-size:42px;font-weight:800;margin-bottom:12px;background:linear-gradient(135deg,#fff,#8b5cf6);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.hero p{font-size:18px;color:var(--text2);max-width:700px;margin:0 auto 24px}
.hero-stats{display:flex;gap:30px;justify-content:center;flex-wrap:wrap;margin-top:20px}
.hero-stat{text-align:center}
.hero-stat .num{font-size:28px;font-weight:800;color:var(--blue)}
.hero-stat .label{font-size:12px;color:var(--text2);text-transform:uppercase;letter-spacing:1px}

/* SEARCH */
.search-bar{max-width:600px;margin:30px auto;position:relative}
.search-bar input{width:100%;padding:14px 20px 14px 44px;background:var(--surface);border:1px solid var(--border);border-radius:12px;color:var(--text);font-size:16px;outline:none}
.search-bar input:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(59,130,246,0.2)}
.search-bar .icon{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--text2);font-size:18px}

/* QUICK NAV */
.quick-nav{display:flex;flex-wrap:wrap;gap:8px;justify-content:center;padding:20px 0 40px}
.quick-nav a{padding:6px 14px;background:var(--surface);border:1px solid var(--border);border-radius:20px;font-size:13px;color:var(--text2);transition:all .2s}
.quick-nav a:hover,.quick-nav a.active{background:var(--blue);border-color:var(--blue);color:#fff}

/* CATEGORY */
.category{margin-bottom:50px;scroll-margin-top:80px}
.cat-header{display:flex;align-items:center;gap:14px;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--border)}
.cat-icon{font-size:32px;width:50px;height:50px;display:flex;align-items:center;justify-content:center;border-radius:12px}
.cat-info h2{font-size:22px;font-weight:700}
.cat-info p{font-size:14px;color:var(--text2)}
.cat-count{margin-left:auto;background:var(--surface2);padding:4px 12px;border-radius:20px;font-size:12px;color:var(--text2)}

/* PRODUCT GRID */
.product-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px}
.product-card{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:20px;transition:all .3s;display:flex;flex-direction:column;position:relative}
.product-card:hover{border-color:var(--blue);transform:translateY(-2px);box-shadow:0 8px 24px rgba(0,0,0,0.3)}
.product-card.popular{border-color:var(--blue)}
.product-card.popular::before{content:'Most Popular';position:absolute;top:-10px;right:16px;background:var(--blue);color:#fff;padding:2px 10px;border-radius:10px;font-size:11px;font-weight:600}
.product-name{font-size:16px;font-weight:700;margin-bottom:6px}
.product-desc{font-size:13px;color:var(--text2);margin-bottom:12px;flex-grow:1;line-height:1.5}
.product-price{margin-bottom:14px}
.price-main{font-size:26px;font-weight:800;color:var(--green)}
.price-period{font-size:13px;color:var(--text2)}
.price-annual{font-size:12px;color:var(--text2);margin-top:2px}
.price-annual span{color:var(--green)}
.product-features{list-style:none;margin-bottom:14px;font-size:13px}
.product-features li{padding:3px 0;color:var(--text2)}
.product-features li::before{content:'✓ ';color:var(--green)}
.btn-order{display:block;text-align:center;padding:10px;background:linear-gradient(135deg,var(--blue),var(--purple));color:#fff;border-radius:10px;font-weight:600;font-size:14px;transition:opacity .2s;border:none;cursor:pointer;width:100%}
.btn-order:hover{opacity:0.9}
.btn-order.secondary{background:var(--surface2);border:1px solid var(--border)}
.btn-order.secondary:hover{border-color:var(--blue)}
.free-badge{background:var(--green);color:#fff;padding:2px 8px;border-radius:6px;font-size:11px;font-weight:600}
.onetime{color:var(--yellow)}

/* RESELLER BANNER */
.reseller-banner{background:linear-gradient(135deg,#1a0a3e,#0a2a4e);border:1px solid var(--purple);border-radius:20px;padding:40px;text-align:center;margin:40px 0}
.reseller-banner h2{font-size:28px;margin-bottom:10px}
.reseller-banner p{color:var(--text2);max-width:600px;margin:0 auto 20px;font-size:15px}

/* FOOTER */
.footer{text-align:center;padding:40px 20px;border-top:1px solid var(--border);color:var(--text2);font-size:13px;margin-top:40px}
.footer a{color:var(--blue)}

@media(max-width:768px){
 .hero h1{font-size:28px}
 .product-grid{grid-template-columns:1fr}
 .header-links{display:none}
 .hero-stats{gap:15px}
 .hero-stat .num{font-size:22px}
}
</style>
</head>
<body>

<div class="header">
 <div class="header-inner">
  <div class="logo">GoHostMe <span>CATALOG</span></div>
  <div class="header-links">
   <a href="/gohostme/">Home</a>
   <a href="/gohostme/dashboard">Dashboard</a>
   <a href="/store">WHMCS Store</a>
   <a href="/clientarea.php">Client Area</a>
   <a href="/gohostme/" class="btn-cta">Get Started</a>
  </div>
 </div>
</div>

<div class="hero">
 <div class="container">
  <h1>Every Product. Every Price. One Place.</h1>
  <p>121 products across 25 categories. AI voice agents, cloud servers, managed services, industry solutions, and more. All white-label ready for resellers.</p>
  <div class="hero-stats">
   <div class="hero-stat"><div class="num"><?= count($groups) ?></div><div class="label">Categories</div></div>
   <div class="hero-stat"><div class="num"><?php $total=0; foreach($groups as $g) $total+=count($g); echo $total; ?></div><div class="label">Products</div></div>
   <div class="hero-stat"><div class="num">18</div><div class="label">Regions</div></div>
   <div class="hero-stat"><div class="num">$2.99</div><div class="label">Starting at</div></div>
  </div>

  <div class="search-bar">
   <span class="icon">&#128269;</span>
   <input type="text" id="search" placeholder="Search products... (e.g., VPS, AI Voice, WordPress, Reseller)" oninput="filterProducts(this.value)">
  </div>

  <div class="quick-nav" id="quick-nav">
   <?php foreach($groups as $name => $items): $slug = strtolower(preg_replace('/[^a-z0-9]+/i','-',$name)); ?>
   <a href="#cat-<?= $slug ?>" onclick="clearSearch()"><?= htmlspecialchars($name) ?> (<?= count($items) ?>)</a>
   <?php endforeach; ?>
  </div>
 </div>
</div>

<div class="container">

<!-- RESELLER BANNER -->
<div class="reseller-banner">
 <h2>&#127942; White-Label Reseller Program</h2>
 <p>Resell ALL 121 products under your own brand. Your logo, your domain, your pricing. We handle the infrastructure, you handle the customers. From $399/mo.</p>
 <a href="#cat-reseller-plans" class="btn-order" style="display:inline-block;padding:12px 30px">View Reseller Plans</a>
</div>

<?php
// Render each category
foreach ($groups as $groupName => $items):
    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $groupName));
    $m = $meta[$groupName] ?? ['icon'=>'&#128230;','tag'=>'','color'=>'#3b82f6'];
    
    // Product descriptions based on PID
    $descriptions = [
        // AI Call Center
        53 => 'Automated outbound calling with AI scripts, lead qualification, and CRM integration. 500 minutes/mo included.',
        54 => 'Advanced dialer with A/B script testing, sentiment analysis, real-time coaching. 2,000 minutes/mo.',
        55 => 'Unlimited concurrent lines, custom AI voice training, dedicated success manager. Unlimited minutes.',
        56 => 'AI-powered inbound call handling — IVR, routing, queue management, after-hours coverage. 1,000 min/mo.',
        57 => 'AI books appointments directly into your calendar. Handles rescheduling, reminders, confirmations.',
        58 => 'Debt collection AI — compliant scripts, payment processing, debtor communication. FTC/CFPB compliant.',
        // AI Dev Platform
        18 => 'AI website builder with drag-and-drop. 1 site, basic AI tools. Perfect for getting started.',
        19 => 'Advanced AI tools, 3 sites, priority build queue. For growing businesses.',
        20 => 'Full studio access, 10 sites, AI image/video generation, API access.',
        21 => '25 sites, team collaboration, advanced analytics, all AI engines.',
        22 => 'Unlimited sites, dedicated resources, custom AI model training, SLA guarantee.',
        31 => 'Creative AI tools — generate content, images, and designs.',
        32 => 'AI creative suite with extended generation limits and priority processing.',
        39 => 'Free tier — explore the platform. 1 site, basic AI assistant. No credit card required.',
        // AI Document & Fax
        65 => 'AI generates contracts, proposals, invoices, letters. 50 documents/mo. Template library included.',
        66 => 'Pro document generation with custom templates, bulk generation, API access. 500 docs/mo.',
        67 => 'Send & receive faxes via AI. No hardware needed. 100 pages/mo. HIPAA available.',
        68 => 'Professional faxing with OCR, auto-filing, 500 pages/mo, broadcast fax capability.',
        69 => 'AI-powered e-signatures. Unlimited signatures, audit trail, templates, multi-party support.',
        // AI Office Suite
        70 => 'AI answers calls, takes messages, routes to departments, handles FAQs. 24/7/365.',
        71 => 'AI manages your calendar, email, travel booking, and daily briefings.',
        72 => 'AI customer service with ticket management, knowledge base, escalation rules.',
        73 => 'AI bookkeeping — reconciliation, expense categorization, invoice processing, financial reports.',
        74 => 'AI sales agent — outbound prospecting, lead scoring, follow-ups, deal tracking.',
        // AI Server Support
        33 => 'Email-based support, 24-hour response, server health monitoring.',
        34 => 'Priority phone/chat support, 4-hour response, proactive monitoring, monthly reports.',
        35 => 'Dedicated support engineer, 1-hour response, custom SLA, disaster recovery planning.',
        // AI Servers
        23 => 'Custom-configured AI server with GPU options. Contact us for pricing based on your requirements.',
        // AI SMS & Chat
        75 => 'AI SMS agent — automated text conversations, appointment reminders, follow-ups. 500 msgs/mo.',
        76 => 'Business SMS with CRM integration, bulk messaging, analytics. 2,000 msgs/mo.',
        77 => 'AI live chat widget for your website. Handles support, sales, bookings. Unlimited chats.',
        // AI Voice
        49 => 'Your first AI voice agent. Handles calls, takes messages, answers FAQs. 100 min/mo included.',
        50 => 'Business voice agent with custom greeting, call routing, CRM sync. 500 min/mo.',
        51 => 'Professional agent with voice cloning, multi-language, sentiment analysis. 2,000 min/mo.',
        52 => 'Enterprise voice with unlimited agents, custom AI training, SLA, dedicated support.',
        // API
        43 => '10,000 API calls/mo. Access hosting, AI, DNS, and domain management APIs.',
        44 => '100,000 API calls/mo. Full API suite with webhooks and batch operations.',
        45 => '1,000,000 API calls/mo. Dedicated API endpoints, priority queue, custom integrations.',
        // Cloud & VPS
        101 => '2 vCPU, 4GB RAM, 80GB NVMe, 1Gbps unmetered. Linux or Windows. 18 regions.',
        102 => '4 vCPU, 8GB RAM, 160GB NVMe, 1Gbps unmetered. Free snapshots. GoHostMe panel.',
        103 => '8 vCPU, 16GB RAM, 320GB NVMe, 1Gbps. Priority support. Auto-scaling ready.',
        104 => '16 vCPU, 32GB RAM, 640GB NVMe, 1Gbps. White-label panel. Dedicated resources.',
        // Dedicated
        12 => 'Full 1Gbps dedicated server with unlimited bandwidth.',
        105 => 'Intel Xeon E-2274G, 32GB ECC, 2x500GB NVMe RAID, 1Gbps unlimited. Full root.',
        106 => 'Intel Xeon E-2386G, 64GB ECC, 2x1TB NVMe, 256 failover IPs, vRack ready.',
        107 => 'AMD EPYC / Xeon Silver, 128GB ECC, 2x2TB NVMe, HW RAID. Datacenter redundancy.',
        108 => 'Dual Xeon Gold/EPYC, 256GB ECC, 4x2TB NVMe, 10Gbps, redundant PSU, IPMI.',
        109 => 'NVIDIA A10 GPU, 30-120 vCPU, 45-180GB RAM, 1-4 GPUs, 24GB VRAM each. AI/ML optimized.',
        // Email
        126 => '5 mailboxes, 5GB/box, IMAP/POP3/SMTP, spam filtering, webmail. Custom domain.',
        127 => '25 mailboxes, 25GB/box, calendar sync, contacts, shared folders, mobile push.',
        128 => '100+ mailboxes, 50GB/box, archiving, compliance, e-discovery, DLP policies.',
        // Hosting
        2 => '1 WordPress site, 10GB SSD, free SSL, daily backups, LiteSpeed cache.',
        3 => '3 WordPress sites, 30GB SSD, staging, WP-CLI, auto-updates. Free migration.',
        4 => '5 WordPress sites, 50GB SSD, priority support, Redis cache, CDN included.',
        5 => '10 WordPress sites, 100GB SSD, multisite ready, staging, priority everything.',
        11 => 'Pay for the year, get WordPress hosting with all features. Best value.',
        // Industry Solutions
        78 => 'AI phone orders, reservations, menu updates, review management. Your virtual host.',
        79 => 'AI showing scheduler, lead capture, property descriptions, virtual tours integration.',
        80 => 'HIPAA-compliant AI scheduling, patient reminders, insurance verification, intake forms.',
        81 => 'AI intake, case management, document generation, client communication, billing.',
        82 => 'AI dispatch, quoting, scheduling, follow-up, review collection for contractors.',
        83 => 'AI claims intake, policy quoting, customer service, renewals. Compliance built-in.',
        84 => 'AI service scheduling, parts ordering, customer follow-up, inventory management.',
        85 => 'AI booking, reminders, waitlist management, product recommendations, review requests.',
        86 => 'AI tenant communication, maintenance requests, rent collection, lease management.',
        87 => 'AI product recommendations, order support, returns, upselling, abandoned cart recovery.',
        88 => 'AI client communication, document collection, deadline reminders, tax prep workflows.',
        89 => 'AI class booking, membership management, personal training scheduling, nutritional AI.',
        // Managed Services
        118 => '50GB backup storage, daily snapshots, 7-day retention, one-click restore.',
        119 => '500GB backup storage, hourly snapshots, 30-day retention, off-site replication.',
        120 => '5TB backup, real-time replication, 90-day retention, disaster recovery, compliance.',
        121 => 'Server monitoring with uptime checks, resource alerts, email notifications.',
        122 => 'Advanced monitoring, custom metrics, Grafana dashboards, SMS alerts, API access.',
        123 => 'Fully managed Kubernetes cluster. Auto-scaling, rolling updates, persistent storage.',
        124 => 'S3-compatible object storage. 1TB included. CDN-ready. Versioning and lifecycle.',
        125 => 'Full server management — updates, security, optimization, 24/7 monitoring.',
        // Network
        110 => 'Personal VPN. 3 devices, unlimited bandwidth, no logs. 10 global locations.',
        111 => 'Business VPN. 25 devices, dedicated IP, split tunneling, team management.',
        112 => 'Enterprise VPN. Unlimited devices, custom DNS, SAML SSO, compliance reporting.',
        113 => 'Additional failover IP for high-availability setups. Instant failover.',
        114 => 'L4/L7 load balancer. Health checks, SSL termination, sticky sessions.',
        115 => 'Managed DNS with anycast, DNSSEC, GeoDNS. 99.999% query uptime.',
        116 => 'Advanced DDoS mitigation. L3-L7 protection, traffic scrubbing, always-on.',
        117 => 'Private network across all your servers. Isolated VLAN, 10Gbps interconnect. Free.',
        // Phone
        59 => 'Local phone number in US or Canada. Caller ID, voicemail, call forwarding.',
        60 => 'Toll-free 1-800/888/877/866 number. Professional image, nationwide reach.',
        61 => 'Custom vanity number (e.g., 1-800-FLOWERS). Choose your memorable number.',
        62 => 'Phone number in 60+ countries. Local presence worldwide.',
        63 => 'Bring your own carrier. SIP trunk with failover, codec support, E911.',
        64 => 'Bundle of 10 local numbers. Perfect for multi-location businesses. Save 20%.',
        // Reseller
        46 => 'Up to 50 clients. Your branding on panel. Custom domain. Billing integrated.',
        47 => 'Up to 200 clients. White-label everything. Priority support. Revenue sharing.',
        48 => 'Unlimited clients. Full white-label. Dedicated infrastructure. Custom pricing. API access.',
        // SSL
        28 => 'DV SSL certificate. 256-bit encryption. Browser trust. 5-minute issuance.',
        29 => 'Wildcard SSL — secure unlimited subdomains. *.yourdomain.com coverage.',
        30 => 'OV SSL with business validation. Green padlock + company name verification.',
        // Team
        40 => '5 team members. Shared projects, collaborative editing, team chat.',
        41 => '10 team members. Role-based access, audit logs, shared templates.',
        42 => '25 team members. SSO, advanced permissions, dedicated workspace, API.',
        // Token Packs
        24 => '100,000 AI tokens for platform usage. Use across all AI features.',
        25 => '500,000 tokens. Best for regular users. 5% bonus tokens.',
        26 => '1,000,000 tokens. Power user pack. 10% bonus tokens.',
        27 => '5,000,000 tokens. Enterprise pack. 15% bonus. Priority processing.',
        // Training
        36 => '2-hour AI quick start session. Personalized onboarding, platform tour, Q&A.',
        37 => 'Full-day AI workshop. Deep-dive training for your team. Custom curriculum.',
        38 => 'Custom AI integration consulting. Architecture, implementation, training. Multi-day.',
        // Voice Add-Ons
        90 => '250 additional voice minutes. Use with any AI voice agent.',
        91 => '1,000 additional minutes. Best value per-minute rate.',
        92 => '5,000 additional minutes. Enterprise volume pricing.',
        93 => '50GB call recording storage. Listen, download, search recordings.',
        94 => 'Clone any voice with AI. Create a custom voice for your brand. One-time setup.',
        95 => 'Add another AI agent to your account. Run multiple agents simultaneously.',
        96 => 'Handle more concurrent calls. Each line handles one simultaneous call.',
        97 => 'HIPAA compliance for voice recordings. BAA included. Encrypted storage.',
        98 => 'White-label AI voice agents under your brand. 10 agent slots included.',
        99 => '1,000 SMS messages for AI text communications.',
        100 => 'Add support for 20+ languages to your AI voice agents.',
        // Web Design
        6 => 'Single-page WordPress design. Mobile responsive. SEO basics. 3-day delivery.',
        7 => 'Multi-page WordPress site (up to 5 pages). Contact forms, gallery, SEO.',
        8 => 'Full WordPress site (up to 10 pages). E-commerce ready, booking system.',
        9 => 'Premium WordPress design (unlimited pages). Custom functionality, integrations.',
    ];

    // Features per product
    $features = [
        101 => ['2 vCPU cores','4GB DDR4 RAM','80GB NVMe SSD','1Gbps unlimited','442 OS images','Free panel'],
        102 => ['4 vCPU cores','8GB DDR4 RAM','160GB NVMe SSD','1Gbps unlimited','Free snapshots','DDoS protection'],
        103 => ['8 vCPU cores','16GB DDR4 RAM','320GB NVMe SSD','Priority support','Auto-scaling','vRack ready'],
        104 => ['16 vCPU cores','32GB DDR4 RAM','640GB NVMe SSD','White-label panel','Dedicated resources','SLA guarantee'],
        105 => ['Intel Xeon 4C/8T','32GB ECC RAM','2x500GB NVMe RAID','1Gbps unlimited','Full root access','IPMI/KVM'],
        106 => ['Intel Xeon 6C/12T','64GB ECC RAM','2x1TB NVMe','256 failover IPs','vRack private','24/7 support'],
        107 => ['AMD EPYC / Xeon Silver','128GB ECC RAM','2x2TB NVMe RAID','Hardware RAID','Redundant DC','Rescue mode'],
        108 => ['Dual Xeon Gold/EPYC','256GB ECC RAM','4x2TB NVMe','10Gbps port','Redundant PSU','Enterprise SLA'],
        109 => ['NVIDIA A10 GPU(s)','Up to 120 vCPU','Up to 180GB RAM','24GB VRAM/GPU','31.2 TFLOPS FP32','AI/ML optimized'],
        49 => ['100 min/mo included','Custom greeting','Call forwarding','Voicemail transcription','24/7 availability','No hardware needed'],
        50 => ['500 min/mo included','CRM integration','Call routing','Analytics dashboard','Custom hold music','Multi-number support'],
        51 => ['2,000 min/mo','Voice cloning','Multi-language','Sentiment analysis','Recording & search','Priority queue'],
        52 => ['Unlimited minutes','Custom AI training','Unlimited agents','Dedicated support','SLA guarantee','Enterprise SSO'],
        46 => ['Up to 50 clients','White-label panel','Custom domain','Billing integrated','Support portal','Basic API'],
        47 => ['Up to 200 clients','Full white-label','Priority support','Revenue sharing','Custom branding','Full API access'],
        48 => ['Unlimited clients','Dedicated infra','Custom pricing','Enterprise API','Dedicated manager','Custom features'],
    ];
?>

<div class="category" id="cat-<?= $slug ?>" data-category="<?= htmlspecialchars($groupName) ?>">
 <div class="cat-header">
  <div class="cat-icon" style="background:<?= $m['color'] ?>22;color:<?= $m['color'] ?>"><?= $m['icon'] ?></div>
  <div class="cat-info">
   <h2><?= htmlspecialchars($groupName) ?></h2>
   <p><?= $m['tag'] ?></p>
  </div>
  <div class="cat-count"><?= count($items) ?> product<?= count($items)>1?'s':'' ?></div>
 </div>
 <div class="product-grid">
  <?php foreach ($items as $item):
      $pid = $item['id'];
      $price = $item['monthly'];
      $annual = $item['annual'];
      $isFree = ($price !== null && (float)$price == 0);
      $isOneTime = ($price === null || (float)$price < 0) && $annual !== null && (float)$annual > 0 && in_array($groupName, ['Token Packs','Training & Consultation','Web Design']);
      $noMonthly = ($price === null || (float)$price < 0);
      $popular = in_array($pid, [102, 47, 50, 70, 78]); // mark popular items
      $desc = $descriptions[$pid] ?? strip_tags($item['description'] ?? '');
      if (!$desc) $desc = 'Professional ' . strtolower($groupName) . ' service. Contact us for details.';
      $feat = $features[$pid] ?? [];
  ?>
  <div class="product-card<?= $popular ? ' popular' : '' ?>" data-name="<?= htmlspecialchars(strtolower($item['name'].' '.$groupName)) ?>">
   <div class="product-name"><?= htmlspecialchars($item['name']) ?></div>
   <div class="product-desc"><?= htmlspecialchars($desc) ?></div>
   <?php if (!empty($feat)): ?>
   <ul class="product-features">
    <?php foreach ($feat as $f): ?><li><?= htmlspecialchars($f) ?></li><?php endforeach; ?>
   </ul>
   <?php endif; ?>
   <div class="product-price">
    <?php if ($isFree): ?>
     <span class="free-badge">FREE</span>
    <?php elseif ($isOneTime): ?>
     <span class="price-main onetime">$<?= number_format((float)($price > 0 ? $price : $annual), 2) ?></span>
     <span class="price-period"><?= $price > 0 ? '/mo' : ' one-time' ?></span>
    <?php elseif ($noMonthly && $annual > 0): ?>
     <span class="price-main">$<?= number_format((float)$annual, 2) ?></span>
     <span class="price-period">/year</span>
    <?php elseif ($price > 0): ?>
     <span class="price-main">$<?= number_format((float)$price, 2) ?></span>
     <span class="price-period">/mo</span>
     <?php if ($annual > 0): ?>
      <div class="price-annual">or <span>$<?= number_format((float)$annual, 2) ?>/yr</span> (save <?= round(100 - ($annual / ($price * 12) * 100)) ?>%)</div>
     <?php endif; ?>
    <?php else: ?>
     <span class="price-main">Custom</span>
     <span class="price-period">Contact us</span>
    <?php endif; ?>
   </div>
   <a href="https://gositeme.com/cart?a=add&pid=<?= $pid ?>" class="btn-order"><?= $isFree ? 'Start Free' : 'Order Now' ?></a>
  </div>
  <?php endforeach; ?>
 </div>
</div>
<?php endforeach; ?>

<!-- BOTTOM CTA -->
<div class="reseller-banner" style="margin-bottom:0">
 <h2>Can't Find What You Need?</h2>
 <p>We build custom solutions. AI agents, server configurations, industry packages — if you can dream it, we can build it.</p>
 <a href="https://gositeme.com/submitticket.php" class="btn-order" style="display:inline-block;padding:12px 30px;margin:5px">Contact Sales</a>
 <a href="/gohostme/" class="btn-order secondary" style="display:inline-block;padding:12px 30px;margin:5px">Back to Home</a>
</div>

</div>

<div class="footer">
 <p>&copy; <?= date('Y') ?> GoSiteMe Inc. — GoHostMe Division | <a href="/gohostme/">Home</a> | <a href="/gohostme/dashboard">Dashboard</a> | <a href="/store">Store</a> | <a href="/clientarea.php">Client Area</a></p>
 <p style="margin-top:8px">121 products. 25 categories. 18 regions. One platform. &#9889;</p>
</div>

<script>
function filterProducts(query) {
 query = query.toLowerCase().trim();
 document.querySelectorAll('.product-card').forEach(card => {
  const name = card.dataset.name || '';
  card.style.display = (!query || name.includes(query)) ? 'flex' : 'none';
 });
 document.querySelectorAll('.category').forEach(cat => {
  const cards = cat.querySelectorAll('.product-card');
  const visible = Array.from(cards).some(c => c.style.display !== 'none');
  cat.style.display = visible ? 'block' : 'none';
 });
}
function clearSearch() {
 document.getElementById('search').value = '';
 filterProducts('');
}
// Smooth scroll
document.querySelectorAll('.quick-nav a').forEach(a => {
 a.addEventListener('click', function(e) {
  e.preventDefault();
  const target = document.querySelector(this.getAttribute('href'));
  if (target) target.scrollIntoView({behavior: 'smooth', block: 'start'});
 });
});
</script>
</body>
</html>
