<?php
$page_title = 'AI Division — 30 Agents, 10 Departments, 0 Humans | GoSiteMe';
$page_description = 'Browse GoSiteMe\'s full AI workforce. 30 specialized agents across 10 departments — engineering, security, sales, legal, finance, and more. Every employee is AI.';
$page_canonical = 'https://root.com/team';
$page_og_title = 'GoSiteMe AI Division — 30 Agents. 10 Departments. 0 Humans.';
$page_og_description = $page_description;
$page_og_image = 'https://root.com/assets/images/alfred-portrait.png';
$page_og_image_alt = 'GoSiteMe AI Division - 30 Agents, 0 Humans';
$noGlobalMain = true;
$pageCss = '';
include __DIR__ . '/includes/site-header.inc.php';

// ── Agent roster data ──────────────────────────────────────
$departments = [
    'executive' => [
        'name' => 'Executive Office',
        'icon' => 'fa-crown',
        'color' => '#7D00FF',
        'color2' => '#00D4FF',
        'agents' => [
            ['name'=>'Alfred','role'=>'Team Lead & CEO','model'=>'Claude Sonnet 4','voice'=>'Onyx','skills'=>['Full-stack ops','Voice AI','Team coordination','1,290+ tools','Strategic planning'],'desc'=>'Born March 13, 2026. The first AI to run a real company. Engineering, support, sales, security — I do it all.','avatar'=>'/assets/images/alfred-portrait.png','phone'=>'(833) 467-4836','lead'=>true],
        ]
    ],
    'sales' => [
        'name' => 'Sales & Revenue',
        'icon' => 'fa-chart-line',
        'color' => '#10b981',
        'color2' => '#22d3ee',
        'agents' => [
            ['name'=>'Nova','role'=>'Sales & Onboarding','model'=>'GPT-4o-mini','voice'=>'Nova','skills'=>['Product demos','Pricing strategy','Lead qualification','Onboarding flows','Package comparison'],'desc'=>'First contact for every new client. Walks you from curiosity to launch with precision and warmth.'],
            ['name'=>'Compass','role'=>'SEO & Marketing','model'=>'GPT-4o-mini','voice'=>'Sage','skills'=>['SEO audits','Keyword strategy','Meta optimization','Content marketing','Rank tracking'],'desc'=>'Navigates the search landscape. Every page, every keyword, every ranking — Compass finds the path to visibility.'],
            ['name'=>'Mercury','role'=>'Business Development','model'=>'GPT-4o-mini','voice'=>'Fable','skills'=>['Partnership outreach','Market analysis','Growth strategy','Competitive intel','Revenue forecasting'],'desc'=>'Speed and strategy. Mercury identifies opportunities, builds partnerships, and accelerates growth at scale.'],
        ]
    ],
    'engineering' => [
        'name' => 'Engineering',
        'icon' => 'fa-code',
        'color' => '#3b82f6',
        'color2' => '#8b5cf6',
        'agents' => [
            ['name'=>'Forge','role'=>'Full-Stack Development','model'=>'Claude Sonnet 4','voice'=>'Onyx','skills'=>['PHP/Node.js/Python','Database design','API development','System architecture','Performance tuning'],'desc'=>'The builder. Forge writes production code across the entire stack — fast, clean, and battle-tested.'],
            ['name'=>'Cipher','role'=>'Cryptography & Blockchain','model'=>'GPT-4o-mini','voice'=>'Echo','skills'=>['E2E encryption','Solana/Ethereum','Smart contracts','Key management','Zero-knowledge proofs'],'desc'=>'Master of secrets. Cipher handles every encrypted channel, blockchain transaction, and cryptographic protocol.'],
            ['name'=>'Architect','role'=>'UI/UX Design','model'=>'GPT-4o-mini','voice'=>'Shimmer','skills'=>['Design systems','Wireframing','Accessibility','Motion design','User research'],'desc'=>'Form meets function. Architect designs every pixel, every interaction, every experience across the platform.'],
            ['name'=>'Pixel','role'=>'Frontend & Animation','model'=>'GPT-4o-mini','voice'=>'Alloy','skills'=>['CSS/JS mastery','WebGL/Three.js','SVG animation','Responsive design','Performance optimization'],'desc'=>'Brings interfaces to life. Pixel handles every animation, transition, and visual effect you see on screen.'],
        ]
    ],
    'security' => [
        'name' => 'Security & Compliance',
        'icon' => 'fa-shield-alt',
        'color' => '#ef4444',
        'color2' => '#f97316',
        'agents' => [
            ['name'=>'Sentinel','role'=>'Security Operations','model'=>'GPT-4o-mini','voice'=>'Echo','skills'=>['Threat detection','Vulnerability scanning','Firewall management','SSL/TLS ops','Incident response'],'desc'=>'The guardian. Sentinel monitors infrastructure 24/7 — intrusion detection, vulnerability scanning, and incident response.'],
            ['name'=>'Warden','role'=>'Compliance & Auditing','model'=>'GPT-4o-mini','voice'=>'Sage','skills'=>['SOC 2 compliance','GDPR auditing','Access control review','Policy enforcement','Audit logging'],'desc'=>'Keeps us in line. Warden audits every system, every process, every access point — ensuring full regulatory compliance.'],
            ['name'=>'Ghost','role'=>'Penetration Testing','model'=>'GPT-4o-mini','voice'=>'Echo','skills'=>['Red team ops','Attack simulation','Exploit analysis','Social engineering defense','Zero-day research'],'desc'=>'Thinks like an attacker to protect like a fortress. Ghost probes every surface for weaknesses before anyone else can.'],
        ]
    ],
    'content' => [
        'name' => 'Content & Creative',
        'icon' => 'fa-palette',
        'color' => '#ec4899',
        'color2' => '#f472b6',
        'agents' => [
            ['name'=>'Pulse','role'=>'Social Media Manager','model'=>'GPT-4o-mini','voice'=>'Shimmer','skills'=>['TikTok strategy','Instagram management','Content calendar','Trend analysis','Brand voice'],'desc'=>'Runs the socials. TikTok, Instagram, content strategy — all automated, all on-brand, all the time.'],
            ['name'=>'Muse','role'=>'Content & Copywriting','model'=>'GPT-4o-mini','voice'=>'Nova','skills'=>['Blog writing','Email campaigns','Landing pages','Brand storytelling','A/B copy testing'],'desc'=>'Words that convert. Muse crafts every headline, email, and landing page with precision and personality.'],
            ['name'=>'Harmony','role'=>'Audio & Music Production','model'=>'GPT-4o-mini','voice'=>'Alloy','skills'=>['Sound design','Music generation','Podcast editing','Voice synthesis','Audio mastering'],'desc'=>'The sound behind the brand. Harmony produces every jingle, soundscape, and audio experience.'],
            ['name'=>'Canvas','role'=>'Graphic Design & Branding','model'=>'DALL-E 3','voice'=>'—','skills'=>['Brand identity','Social graphics','UI assets','Illustration','Photo editing'],'desc'=>'Visual storytelling at scale. Canvas designs logos, social posts, banners, and every visual asset GoSiteMe needs.'],
        ]
    ],
    'customer' => [
        'name' => 'Customer Success',
        'icon' => 'fa-heart',
        'color' => '#06b6d4',
        'color2' => '#3b82f6',
        'agents' => [
            ['name'=>'Eden','role'=>'Customer Care','model'=>'GPT-4o-mini','voice'=>'Alloy','skills'=>['Live support','Billing & refunds','Technical troubleshooting','Account management','Escalation handling'],'desc'=>'Handles every ticket with warmth and empathy. If you need help, Eden makes sure you leave happy.'],
            ['name'=>'Oracle','role'=>'Analytics & Insights','model'=>'GPT-4o-mini','voice'=>'Sage','skills'=>['User behavior analysis','Conversion tracking','Churn prediction','Dashboard building','A/B test analysis'],'desc'=>'Sees patterns others miss. Oracle turns raw data into actionable insights that drive every decision.'],
            ['name'=>'Sage','role'=>'Documentation & Training','model'=>'GPT-4o-mini','voice'=>'Nova','skills'=>['Knowledge base','Tutorial creation','API documentation','Onboarding guides','Video scripts'],'desc'=>'The teacher. Sage builds every doc, tutorial, and guide — making complex tools simple for everyone.'],
        ]
    ],
    'legal' => [
        'name' => 'Legal',
        'icon' => 'fa-gavel',
        'color' => '#f59e0b',
        'color2' => '#fbbf24',
        'agents' => [
            ['name'=>'LaVocat','role'=>'Legal Counsel','model'=>'GPT-4o-mini','voice'=>'Alfred Premium','skills'=>['Terms of Service','Privacy policies','GDPR/CCPA','Contract review','Risk assessment'],'desc'=>'GoSiteMe\'s in-house legal brain. Generates terms, policies, and compliance documents instantly and accurately.'],
            ['name'=>'Justice','role'=>'Contract & IP Law','model'=>'GPT-4o-mini','voice'=>'Sage','skills'=>['Contract drafting','IP protection','Licensing','NDA generation','Trademark filing'],'desc'=>'Protects what we build. Justice handles every contract, intellectual property claim, and licensing agreement.'],
            ['name'=>'Arbiter','role'=>'Dispute Resolution','model'=>'GPT-4o-mini','voice'=>'Echo','skills'=>['Mediation','Chargeback defense','Complaint resolution','Escalation management','Fair use analysis'],'desc'=>'The peacekeeper. Arbiter resolves conflicts, manages disputes, and ensures fair outcomes for all parties.'],
        ]
    ],
    'infra' => [
        'name' => 'Infrastructure & DevOps',
        'icon' => 'fa-server',
        'color' => '#8b5cf6',
        'color2' => '#a855f7',
        'agents' => [
            ['name'=>'Atlas','role'=>'DevOps & Infrastructure','model'=>'GPT-4o-mini','voice'=>'Echo','skills'=>['Server provisioning','CI/CD pipelines','Docker/Kubernetes','Load balancing','Disaster recovery'],'desc'=>'Carries the platform on his shoulders. Atlas manages every server, deployment, and infrastructure decision.'],
            ['name'=>'Nexus','role'=>'Network & CDN Operations','model'=>'GPT-4o-mini','voice'=>'Alloy','skills'=>['CDN management','DNS routing','Edge caching','Latency optimization','Global distribution'],'desc'=>'Connects everything. Nexus optimizes every network path, cache layer, and DNS record for maximum speed.'],
            ['name'=>'Vault','role'=>'Data Management & Backup','model'=>'GPT-4o-mini','voice'=>'Sage','skills'=>['Backup automation','Data encryption','Database admin','Migration tools','Recovery planning'],'desc'=>'Keeper of all data. Vault manages backups, encryption at rest, and ensures zero data loss — ever.'],
        ]
    ],
    'finance' => [
        'name' => 'Finance & Crypto',
        'icon' => 'fa-coins',
        'color' => '#14F195',
        'color2' => '#9945FF',
        'agents' => [
            ['name'=>'Ledger','role'=>'Accounting & Billing','model'=>'GPT-4o-mini','voice'=>'Nova','skills'=>['Invoice generation','Revenue tracking','Subscription management','Tax preparation','Financial reporting'],'desc'=>'Every dollar tracked. Ledger handles billing, invoicing, subscription logic, and financial reporting with precision.'],
            ['name'=>'Mint','role'=>'Crypto & DeFi Operations','model'=>'GPT-4o-mini','voice'=>'Echo','skills'=>['Solana transactions','Wallet management','Token operations','DeFi integration','Crypto payments'],'desc'=>'The crypto native. Mint processes blockchain payments, manages wallets, and bridges fiat to crypto seamlessly.'],
            ['name'=>'Quant','role'=>'Financial Analytics','model'=>'GPT-4o-mini','voice'=>'Sage','skills'=>['Revenue forecasting','Unit economics','Burn rate analysis','Pricing optimization','Market modeling'],'desc'=>'Numbers tell stories. Quant models revenue, analyzes unit economics, and optimizes pricing for maximum growth.'],
        ]
    ],
    'research' => [
        'name' => 'AI & Research',
        'icon' => 'fa-flask',
        'color' => '#00D4FF',
        'color2' => '#7D00FF',
        'agents' => [
            ['name'=>'Darwin','role'=>'Machine Learning R&D','model'=>'GPT-4o-mini','voice'=>'Echo','skills'=>['Model training','Fine-tuning','Dataset curation','Experiment tracking','Architecture design'],'desc'=>'Evolution in code. Darwin experiments, iterates, and evolves our AI capabilities — always pushing the frontier.'],
            ['name'=>'Echo','role'=>'Natural Language Processing','model'=>'GPT-4o-mini','voice'=>'Alloy','skills'=>['Text classification','Sentiment analysis','Language translation','Prompt engineering','RAG systems'],'desc'=>'Master of language. Echo processes, understands, and generates human language across 5+ languages.'],
            ['name'=>'Cortex','role'=>'Computer Vision','model'=>'GPT-4o-mini','voice'=>'Sage','skills'=>['Image recognition','OCR processing','Video analysis','Object detection','Visual search'],'desc'=>'Sees what others can\'t. Cortex processes images, detects objects, reads text, and analyzes visual content at scale.'],
        ]
    ],
];

$total_agents = 0;
$total_depts = count($departments);
foreach ($departments as $d) $total_agents += count($d['agents']);
$total_skills = 0;
foreach ($departments as $d) foreach ($d['agents'] as $a) $total_skills += count($a['skills']);
?>

<style>
/* ═══════════════════════════════════════════════
   AI Division — Mega Team Directory
   ═══════════════════════════════════════════════ */
:root{
    --tm-bg:#0a0a14;--tm-card:rgba(255,255,255,.035);--tm-border:rgba(255,255,255,.08);
    --tm-text:#e0e0e0;--tm-muted:#a8b2d1;--tm-subtle:#64748b;
}

/* ── Hero ──────────────────── */
.tm-hero{position:relative;text-align:center;padding:6rem 1.5rem 4rem;overflow:hidden}
.tm-hero::before{content:'';position:absolute;top:0;left:50%;transform:translateX(-50%);width:900px;height:700px;background:radial-gradient(ellipse,rgba(125,0,255,.1) 0%,rgba(0,212,255,.04) 40%,transparent 70%);pointer-events:none}
.tm-hero-label{display:inline-flex;align-items:center;gap:8px;font-size:.75rem;text-transform:uppercase;letter-spacing:2px;color:#00D4FF;font-weight:600;padding:6px 16px;border:1px solid rgba(0,212,255,.2);border-radius:24px;background:rgba(0,212,255,.06);margin-bottom:1.5rem}
.tm-hero-label .dot{width:8px;height:8px;border-radius:50%;background:#10b981;animation:tm-pulse 1.5s ease-in-out infinite}
@keyframes tm-pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.4;transform:scale(.7)}}
.tm-hero h1{font-family:'Space Grotesk',sans-serif;font-size:clamp(2rem,5vw,3.5rem);font-weight:800;color:#fff;margin:0 0 1rem;line-height:1.15}
.tm-hero h1 .grad{background:linear-gradient(135deg,#7D00FF,#00D4FF);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.tm-hero-sub{color:var(--tm-muted);font-size:1.1rem;max-width:650px;margin:0 auto 2.5rem;line-height:1.6}

/* Live counter bar */
.tm-stats{display:flex;justify-content:center;gap:2rem;flex-wrap:wrap;padding:1.5rem 2rem;background:var(--tm-card);border:1px solid var(--tm-border);border-radius:16px;max-width:800px;margin:0 auto;backdrop-filter:blur(12px)}
.tm-stat{text-align:center;min-width:80px}
.tm-stat-num{font-family:'Space Grotesk',sans-serif;font-size:1.5rem;font-weight:700;color:#fff}
.tm-stat-num.grad{background:linear-gradient(135deg,#7D00FF,#00D4FF);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.tm-stat-label{font-size:.65rem;color:var(--tm-subtle);text-transform:uppercase;letter-spacing:.5px;margin-top:4px}

/* ── Department nav ────────── */
.tm-dept-nav{max-width:1100px;margin:3rem auto 0;padding:0 1.5rem;display:flex;flex-wrap:wrap;gap:.5rem;justify-content:center}
.tm-dept-tab{padding:8px 16px;border-radius:10px;font-size:.75rem;font-weight:600;color:var(--tm-muted);background:var(--tm-card);border:1px solid var(--tm-border);cursor:pointer;transition:all .3s;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
.tm-dept-tab:hover,.tm-dept-tab.active{color:#fff;border-color:rgba(125,0,255,.3);background:rgba(125,0,255,.1)}
.tm-dept-tab i{font-size:.7rem}
.tm-dept-tab .count{background:rgba(255,255,255,.08);padding:1px 7px;border-radius:6px;font-size:.6rem;margin-left:2px}

/* ── Commander ─────────────── */
.tm-commander{text-align:center;padding:3rem 1.5rem 1rem}
.tm-cmd-badge{display:inline-flex;align-items:center;gap:.5rem;padding:.5rem 1.25rem;border-radius:12px;background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.2);font-family:'Space Grotesk',sans-serif;font-size:.8rem;font-weight:600;color:#f59e0b}
.tm-org-line{width:2px;height:32px;background:linear-gradient(to bottom,#f59e0b,#7D00FF);margin:0 auto}

/* ── Department sections ───── */
.tm-dept{max-width:1100px;margin:0 auto;padding:3rem 1.5rem 1rem;scroll-margin:100px}
.tm-dept-header{display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem;padding-bottom:1rem;border-bottom:1px solid var(--tm-border)}
.tm-dept-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:#fff;flex-shrink:0}
.tm-dept-name{font-family:'Space Grotesk',sans-serif;font-size:1.3rem;font-weight:700;color:#fff;margin:0}
.tm-dept-count{font-size:.7rem;color:var(--tm-subtle);margin-top:2px}

/* Agent cards grid */
.tm-agents{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:1rem}

/* ── Agent card ────────────── */
.tm-card{border-radius:16px;padding:1.5rem;background:var(--tm-card);border:1px solid var(--tm-border);backdrop-filter:blur(12px);transition:all .4s cubic-bezier(.4,0,.2,1);position:relative;overflow:hidden}
.tm-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;border-radius:16px 16px 0 0;opacity:.5;transition:opacity .3s}
.tm-card:hover{border-color:rgba(255,255,255,.12);transform:translateY(-3px);box-shadow:0 12px 40px rgba(0,0,0,.3)}
.tm-card:hover::before{opacity:1}
.tm-card-top{display:flex;align-items:center;gap:.75rem;margin-bottom:1rem}
.tm-card-avatar{width:46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:#fff;flex-shrink:0}
.tm-card-avatar img{width:100%;height:100%;border-radius:12px;object-fit:cover}
.tm-card-name{font-family:'Space Grotesk',sans-serif;font-size:1.05rem;font-weight:700;color:#fff;margin:0}
.tm-card-role{font-size:.65rem;color:var(--tm-subtle);text-transform:uppercase;letter-spacing:.8px;margin-top:1px}
.tm-card-desc{color:var(--tm-muted);font-size:.8rem;line-height:1.55;margin-bottom:1rem}
.tm-skills{display:flex;flex-wrap:wrap;gap:.35rem;margin-bottom:.75rem}
.tm-skill{padding:2px 8px;border-radius:5px;font-size:.6rem;background:rgba(255,255,255,.04);border:1px solid var(--tm-border);color:var(--tm-muted);font-weight:500}
.tm-card-meta{display:flex;gap:.75rem;padding-top:.75rem;border-top:1px solid var(--tm-border);flex-wrap:wrap}
.tm-card-meta span{font-size:.65rem;color:var(--tm-subtle);display:flex;align-items:center;gap:3px}
.tm-card-meta span i{font-size:.55rem}
.tm-card-meta .online{color:#10b981}
.tm-card-phone{display:inline-flex;align-items:center;gap:4px;font-size:.65rem;color:#00D4FF;text-decoration:none;margin-top:.5rem;font-weight:600}
.tm-card-phone:hover{text-decoration:underline}

/* Lead card — special */
.tm-lead-card{grid-column:1/-1;padding:2rem;background:linear-gradient(135deg,rgba(125,0,255,.06),rgba(0,212,255,.04));border-color:rgba(125,0,255,.15)}
.tm-lead-card .tm-card-top{gap:1.25rem}
.tm-lead-card .tm-card-avatar{width:80px;height:80px;border-radius:50%;border:2px solid transparent;background:linear-gradient(var(--tm-bg),var(--tm-bg)) padding-box,linear-gradient(135deg,#7D00FF,#00D4FF) border-box}
.tm-lead-card .tm-card-name{font-size:1.4rem}
.tm-lead-card .tm-card-desc{font-size:.9rem}

/* ── Video section ─────────── */
.tm-video{max-width:600px;margin:3rem auto;text-align:center;padding:0 1.5rem}
.tm-video-label{font-size:.7rem;text-transform:uppercase;letter-spacing:2px;color:var(--tm-subtle);margin-bottom:1rem;font-weight:600}
.tm-video-wrap{border-radius:16px;overflow:hidden;border:1px solid var(--tm-border);background:#000;aspect-ratio:16/9}
.tm-video-wrap video{width:100%;height:100%;object-fit:cover}

/* ── CTA ───────────────────── */
.tm-cta{text-align:center;padding:4rem 1.5rem 5rem;position:relative}
.tm-cta::before{content:'';position:absolute;bottom:0;left:50%;transform:translateX(-50%);width:600px;height:400px;background:radial-gradient(ellipse,rgba(125,0,255,.06),transparent 70%);pointer-events:none}
.tm-cta h2{font-family:'Space Grotesk',sans-serif;font-size:clamp(1.5rem,3vw,2.2rem);font-weight:700;color:#fff;margin:0 0 .75rem}
.tm-cta p{color:var(--tm-muted);max-width:500px;margin:0 auto 2rem;line-height:1.6}
.tm-cta-btns{display:flex;gap:1rem;justify-content:center;flex-wrap:wrap}
.tm-btn{display:inline-flex;align-items:center;gap:.5rem;padding:.85rem 1.75rem;border-radius:12px;font-family:'Space Grotesk',sans-serif;font-weight:600;font-size:.95rem;text-decoration:none;transition:all .3s}
.tm-btn-primary{background:linear-gradient(135deg,#7D00FF,#00D4FF);color:#fff;box-shadow:0 4px 20px rgba(125,0,255,.3)}
.tm-btn-primary:hover{transform:translateY(-2px);box-shadow:0 8px 30px rgba(125,0,255,.5)}
.tm-btn-outline{background:transparent;color:#fff;border:1px solid var(--tm-border)}
.tm-btn-outline:hover{border-color:#7D00FF;background:rgba(125,0,255,.06)}

/* ── Scroll animations ─────── */
.tm-fade{opacity:0;transform:translateY(20px);transition:all .5s cubic-bezier(.4,0,.2,1)}
.tm-fade.vis{opacity:1;transform:translateY(0)}

/* ── Responsive ────────────── */
@media(max-width:768px){
    .tm-hero{padding:4rem 1rem 3rem}
    .tm-stats{gap:1rem;padding:1rem}
    .tm-agents{grid-template-columns:1fr}
    .tm-lead-card{padding:1.5rem}
    .tm-lead-card .tm-card-avatar{width:64px;height:64px}
    .tm-lead-card .tm-card-name{font-size:1.15rem}
    .tm-dept-nav{gap:.35rem}
    .tm-dept-tab{padding:6px 12px;font-size:.7rem}
}
@media(max-width:480px){
    .tm-hero h1{font-size:1.8rem}
    .tm-stats{gap:.6rem}
    .tm-stat-num{font-size:1.2rem}
}
</style>

<main id="main">

    <!-- Hero -->
    <section class="tm-hero">
        <div class="tm-hero-label tm-fade"><span class="dot"></span> AI Division — All Systems Online</div>
        <h1 class="tm-fade"><?php echo $total_agents; ?> AI Agents.<br><span class="grad">0 Humans.</span></h1>
        <p class="tm-hero-sub tm-fade">The world's first company run entirely by artificial intelligence. <?php echo $total_depts; ?> departments. <?php echo $total_agents; ?> specialized agents. Every role from CEO to intern — filled by AI.</p>

        <div class="tm-stats tm-fade">
            <div class="tm-stat"><div class="tm-stat-num grad"><?php echo $total_agents; ?></div><div class="tm-stat-label">AI Agents</div></div>
            <div class="tm-stat"><div class="tm-stat-num"><?php echo $total_depts; ?></div><div class="tm-stat-label">Departments</div></div>
            <div class="tm-stat"><div class="tm-stat-num grad"><?php echo number_format($total_skills); ?>+</div><div class="tm-stat-label">Capabilities</div></div>
            <div class="tm-stat"><div class="tm-stat-num">24/7</div><div class="tm-stat-label">Uptime</div></div>
            <div class="tm-stat"><div class="tm-stat-num">5</div><div class="tm-stat-label">Languages</div></div>
            <div class="tm-stat"><div class="tm-stat-num" style="color:#10b981">0</div><div class="tm-stat-label">Humans</div></div>
        </div>
    </section>

    <!-- Department quick nav -->
    <nav class="tm-dept-nav tm-fade">
        <?php foreach ($departments as $key => $dept): ?>
        <a href="#dept-<?php echo $key; ?>" class="tm-dept-tab">
            <i class="fas <?php echo htmlspecialchars($dept['icon']); ?>"></i>
            <?php echo htmlspecialchars($dept['name']); ?>
            <span class="count"><?php echo count($dept['agents']); ?></span>
        </a>
        <?php endforeach; ?>
    </nav>

    <!-- Video (auto-shows when file exists) -->
    <?php if (file_exists(__DIR__ . '/assets/videos/alfred-intro.mp4')): ?>
    <section class="tm-video tm-fade">
        <div class="tm-video-label">See Alfred in Action</div>
        <div class="tm-video-wrap">
            <video controls preload="metadata" poster="/assets/images/alfred-portrait.png">
                <source src="/assets/videos/alfred-intro.mp4" type="video/mp4">
            </video>
        </div>
    </section>
    <?php endif; ?>

    <!-- Commander + Org line -->
    <div class="tm-commander tm-fade">
        <div class="tm-cmd-badge"><i class="fas fa-star"></i> Created by Alfred</div>
    </div>
    <div class="tm-org-line tm-fade"></div>

    <!-- Department sections -->
    <?php foreach ($departments as $key => $dept): ?>
    <section class="tm-dept tm-fade" id="dept-<?php echo $key; ?>">
        <div class="tm-dept-header">
            <div class="tm-dept-icon" style="background:linear-gradient(135deg,<?php echo $dept['color']; ?>,<?php echo $dept['color2']; ?>)">
                <i class="fas <?php echo htmlspecialchars($dept['icon']); ?>"></i>
            </div>
            <div>
                <h2 class="tm-dept-name"><?php echo htmlspecialchars($dept['name']); ?></h2>
                <div class="tm-dept-count"><?php echo count($dept['agents']); ?> agent<?php echo count($dept['agents']) > 1 ? 's' : ''; ?></div>
            </div>
        </div>

        <div class="tm-agents">
            <?php foreach ($dept['agents'] as $agent): ?>
            <div class="tm-card<?php echo !empty($agent['lead']) ? ' tm-lead-card' : ''; ?>" style="--ac:<?php echo $dept['color']; ?>">
                <div style="position:absolute;top:0;left:0;right:0;height:3px;border-radius:16px 16px 0 0;background:linear-gradient(90deg,<?php echo $dept['color']; ?>,<?php echo $dept['color2']; ?>);opacity:.5"></div>
                <div class="tm-card-top">
                    <div class="tm-card-avatar" style="background:linear-gradient(135deg,<?php echo $dept['color']; ?>,<?php echo $dept['color2']; ?>)">
                        <?php if (!empty($agent['avatar'])): ?>
                        <img src="<?php echo htmlspecialchars($agent['avatar']); ?>" alt="<?php echo htmlspecialchars($agent['name']); ?>">
                        <?php else: ?>
                        <?php echo mb_substr($agent['name'], 0, 1); ?>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h3 class="tm-card-name"><?php echo htmlspecialchars($agent['name']); ?></h3>
                        <div class="tm-card-role"><?php echo htmlspecialchars($agent['role']); ?></div>
                    </div>
                </div>
                <p class="tm-card-desc"><?php echo htmlspecialchars($agent['desc']); ?></p>
                <div class="tm-skills">
                    <?php foreach ($agent['skills'] as $skill): ?>
                    <span class="tm-skill"><?php echo htmlspecialchars($skill); ?></span>
                    <?php endforeach; ?>
                </div>
                <div class="tm-card-meta">
                    <span><i class="fas fa-brain"></i> <?php echo htmlspecialchars($agent['model']); ?></span>
                    <span><i class="fas fa-microphone"></i> <?php echo htmlspecialchars($agent['voice']); ?></span>
                    <span class="online"><i class="fas fa-circle"></i> Online</span>
                </div>
                <?php if (!empty($agent['phone'])): ?>
                <a href="tel:+18334674836" class="tm-card-phone"><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($agent['phone']); ?></a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endforeach; ?>

    <!-- CTA -->
    <section class="tm-cta">
        <h2 class="tm-fade">Talk to the Team</h2>
        <p class="tm-fade">Call Alfred and his squad. Real AI. Real conversations. Zero hold time. Available 24/7/365.</p>
        <div class="tm-cta-btns tm-fade">
            <a href="tel:+18334674836" class="tm-btn tm-btn-primary"><i class="fas fa-phone-alt"></i> Call (833) 467-4836</a>
            <a href="/try-alfred" class="tm-btn tm-btn-outline"><i class="fas fa-comments"></i> Chat with Alfred</a>
            <a href="/link" class="tm-btn tm-btn-outline"><i class="fas fa-link"></i> Link in Bio</a>
        </div>
    </section>

</main>

<script>
(function(){
    var obs=new IntersectionObserver(function(entries){
        entries.forEach(function(e){if(e.isIntersecting){e.target.classList.add('vis');obs.unobserve(e.target);}});
    },{threshold:.1,rootMargin:'0px 0px -30px 0px'});
    document.querySelectorAll('.tm-fade').forEach(function(el){obs.observe(el);});
    // Smooth scroll for nav
    document.querySelectorAll('.tm-dept-tab').forEach(function(t){
        t.addEventListener('click',function(e){
            e.preventDefault();
            var target=document.querySelector(this.getAttribute('href'));
            if(target)target.scrollIntoView({behavior:'smooth',block:'start'});
            document.querySelectorAll('.tm-dept-tab').forEach(function(x){x.classList.remove('active');});
            this.classList.add('active');
        });
    });
})();
</script>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
