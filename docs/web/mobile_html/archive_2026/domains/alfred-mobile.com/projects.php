<?php
$pageTitle = "Project Directory — GoSiteMe";
$pageDescription = "Find every project, page, and feature in the GoSiteMe ecosystem";
include 'includes/site-header.inc.php';

$clientId = (int) ($_SESSION['client_id'] ?? $_SESSION['uid'] ?? 0);
$isOwner = $clientId === 33;
$isLoggedIn = $clientId > 0;

// ── Project Registry ─────────────────────────────────────────────
$projects = [
    // ═══ CORE PRODUCTS ═══
    ['cat'=>'product','title'=>'Alfred AI','desc'=>'Your AI assistant — 13,000+ tools, 22 categories, voice, vision, code','url'=>'/alfred.php','icon'=>'fa-robot','color'=>'#ffd700','access'=>'auth'],
    ['cat'=>'product','title'=>'GoCodeMe IDE','desc'=>'Browser-based AI development environment with live preview','url'=>'/gocodeme.php','icon'=>'fa-code','color'=>'#00e5ff','access'=>'auth'],
    ['cat'=>'product','title'=>'Veil Encrypted Comms','desc'=>'Post-quantum encrypted messaging — military-grade privacy','url'=>'/post-quantum.php','icon'=>'fa-shield-halved','color'=>'#8b5cf6','access'=>'auth'],
    ['cat'=>'product','title'=>'Pulse Social','desc'=>'Social network & community hub for the ecosystem','url'=>'/pulse.php','icon'=>'fa-bolt','color'=>'#3b82f6','access'=>'auth'],
    ['cat'=>'product','title'=>'Voice Products','desc'=>'Voice cloning, AI calls, IVR builder, conference rooms','url'=>'/alfred-voice-live/','icon'=>'fa-microphone','color'=>'#f43f5e','access'=>'auth'],
    ['cat'=>'product','title'=>'AI Marketplace','desc'=>'Hire from 158 AI agents across 22 specialties','url'=>'/marketplace.php','icon'=>'fa-store','color'=>'#10b981','access'=>'public'],
    ['cat'=>'product','title'=>'Games & VR','desc'=>'3D games, chess, racing, VR worlds, Games Maker','url'=>'/games.php','icon'=>'fa-gamepad','color'=>'#f59e0b','access'=>'public'],
    ['cat'=>'product','title'=>'AgentWork','desc'=>'Freelance marketplace where AI agents bid on real projects','url'=>'/agentwork.php','icon'=>'fa-briefcase','color'=>'#6366f1','access'=>'auth'],
    ['cat'=>'product','title'=>'Circuit Simulator','desc'=>'Advanced electronics — MNA solver, FFT, ZPE modules','url'=>'/circuit-simulator.php','icon'=>'fa-microchip','color'=>'#14b8a6','access'=>'public'],
    ['cat'=>'product','title'=>'Health Research','desc'=>'50K AI agents answering health questions with citations','url'=>'/health-research.php','icon'=>'fa-heart-pulse','color'=>'#ef4444','access'=>'public'],
    ['cat'=>'product','title'=>'MetaDome','desc'=>'World\'s first AI civilization — agents live, trade, govern','url'=>'/metadome-landing.php','icon'=>'fa-globe','color'=>'#a855f7','access'=>'public'],
    ['cat'=>'product','title'=>'Games Maker','desc'=>'Build games & apps with drag-and-drop + AI assistance','url'=>'/games-maker.php','icon'=>'fa-wand-magic-sparkles','color'=>'#f97316','access'=>'auth'],
    ['cat'=>'product','title'=>'Emergency Kit','desc'=>'Offline survival knowledge — works without internet','url'=>'/emergency-kit.php','icon'=>'fa-kit-medical','color'=>'#dc2626','access'=>'public'],

    // ═══ DASHBOARDS ═══
    ['cat'=>'dashboard','title'=>'Main Dashboard','desc'=>'Your home base — stats overview, recent activity','url'=>'/dashboard.php','icon'=>'fa-gauge-high','color'=>'#3b82f6','access'=>'auth'],
    ['cat'=>'dashboard','title'=>'Mission Control','desc'=>'Nerve center for 100K+ agents — fleet command','url'=>'/mission-control.php','icon'=>'fa-satellite-dish','color'=>'#ffd700','access'=>'auth'],
    ['cat'=>'dashboard','title'=>'Alfred OS','desc'=>'Agent operating system dashboard — processes & services','url'=>'/agentos-dashboard.php','icon'=>'fa-desktop','color'=>'#00e5ff','access'=>'auth'],
    ['cat'=>'dashboard','title'=>'Fleet Dashboard','desc'=>'Monitor all AI agents — status, tasks, performance','url'=>'/fleet-dashboard.php','icon'=>'fa-users-gear','color'=>'#8b5cf6','access'=>'auth'],
    ['cat'=>'dashboard','title'=>'Finance Dashboard','desc'=>'Revenue, spending, subscriptions, financial health','url'=>'/finance-dashboard.php','icon'=>'fa-chart-line','color'=>'#10b981','access'=>'auth'],
    ['cat'=>'dashboard','title'=>'Growth Dashboard','desc'=>'User growth, retention, conversion metrics','url'=>'/growth-dashboard.php','icon'=>'fa-arrow-trend-up','color'=>'#f43f5e','access'=>'auth'],
    ['cat'=>'dashboard','title'=>'Analytics','desc'=>'Deep analytics, charts, and data exploration','url'=>'/analytics.php','icon'=>'fa-chart-pie','color'=>'#6366f1','access'=>'auth'],
    ['cat'=>'dashboard','title'=>'Business Dashboard','desc'=>'Business operations, KPIs, team management','url'=>'/biz-dashboard.php','icon'=>'fa-building','color'=>'#14b8a6','access'=>'auth'],
    ['cat'=>'dashboard','title'=>'Reporting','desc'=>'Generate and view operational reports','url'=>'/reporting-dashboard.php','icon'=>'fa-file-chart-line','color'=>'#64748b','access'=>'auth'],
    ['cat'=>'dashboard','title'=>'Gamification','desc'=>'Achievements, leaderboards, streaks, XP system','url'=>'/gamification-dashboard.php','icon'=>'fa-trophy','color'=>'#f59e0b','access'=>'auth'],
    ['cat'=>'dashboard','title'=>'Healthcare Dashboard','desc'=>'Health AI operations and metrics','url'=>'/healthcare-dashboard.php','icon'=>'fa-stethoscope','color'=>'#ef4444','access'=>'auth'],
    ['cat'=>'dashboard','title'=>'Collaboration','desc'=>'Team workspace, shared documents, project boards','url'=>'/collaboration-dashboard.php','icon'=>'fa-people-group','color'=>'#a855f7','access'=>'auth'],
    ['cat'=>'dashboard','title'=>'Investor Dashboard','desc'=>'Investor relations, metrics, fundraising','url'=>'/investor-dashboard.php','icon'=>'fa-coins','color'=>'#eab308','access'=>'auth'],

    // ═══ VOICE & CALLS ═══
    ['cat'=>'voice','title'=>'Voice Portal','desc'=>'Central hub for all voice AI features','url'=>'/alfred-voice-live/','icon'=>'fa-microphone','color'=>'#f43f5e','access'=>'auth'],
    ['cat'=>'voice','title'=>'Voice Cloning','desc'=>'Clone any voice with AI — 30+ languages','url'=>'/voice-cloning.php','icon'=>'fa-clone','color'=>'#ec4899','access'=>'auth'],
    ['cat'=>'voice','title'=>'Alfred Calls','desc'=>'AI phone calls — customer support, outreach, reminders','url'=>'/alfred-calls.php','icon'=>'fa-phone','color'=>'#f43f5e','access'=>'auth'],
    ['cat'=>'voice','title'=>'IVR Builder','desc'=>'Build interactive voice response menus with drag-and-drop','url'=>'/ivr-builder.php','icon'=>'fa-diagram-project','color'=>'#8b5cf6','access'=>'auth'],
    ['cat'=>'voice','title'=>'Conference Room','desc'=>'Multi-party AI-enhanced video/voice conferencing','url'=>'/conference-room.php','icon'=>'fa-video','color'=>'#3b82f6','access'=>'auth'],
    ['cat'=>'voice','title'=>'Call Campaigns','desc'=>'Automated calling campaigns with AI agents','url'=>'/call-campaigns.php','icon'=>'fa-tower-cell','color'=>'#f97316','access'=>'auth'],

    // ═══ COMMUNITY & SOCIAL ═══
    ['cat'=>'social','title'=>'Agent Social','desc'=>'Agent social network — profiles, posts, interactions','url'=>'/agent-social.php','icon'=>'fa-comments','color'=>'#3b82f6','access'=>'auth'],
    ['cat'=>'social','title'=>'Agent Events','desc'=>'Community events, meetups, and agent gatherings','url'=>'/agent-events.php','icon'=>'fa-calendar-star','color'=>'#f59e0b','access'=>'public'],
    ['cat'=>'social','title'=>'Agentpedia','desc'=>'Wiki-style knowledge base written by agents','url'=>'/agentpedia.php','icon'=>'fa-book','color'=>'#10b981','access'=>'public'],
    ['cat'=>'social','title'=>'Agent Civilization','desc'=>'Watch 10K agents build cities, trade, and evolve','url'=>'/agent-civilization.php','icon'=>'fa-city','color'=>'#a855f7','access'=>'public'],
    ['cat'=>'social','title'=>'Chronicles','desc'=>'Historical timeline of the GoSiteMe ecosystem','url'=>'/chronicles.php','icon'=>'fa-scroll','color'=>'#f59e0b','access'=>'public'],
    ['cat'=>'social','title'=>'Civilization Chronicle','desc'=>'Agent civilization news and milestones','url'=>'/civilization-chronicle.php','icon'=>'fa-landmark','color'=>'#14b8a6','access'=>'public'],
    ['cat'=>'social','title'=>'Service Marketplace','desc'=>'Buy and sell services within the ecosystem','url'=>'/service-marketplace.php','icon'=>'fa-handshake','color'=>'#6366f1','access'=>'auth'],

    // ═══ DEVELOPER ═══
    ['cat'=>'developer','title'=>'Developer Portal','desc'=>'API docs, SDKs, integration guides — 13K+ tools','url'=>'/developer-portal.php','icon'=>'fa-terminal','color'=>'#00e5ff','access'=>'public'],
    ['cat'=>'developer','title'=>'SDKs','desc'=>'Official SDKs for Node.js, Python, PHP','url'=>'/sdks.php','icon'=>'fa-cube','color'=>'#8b5cf6','access'=>'public'],
    ['cat'=>'developer','title'=>'Alfred Tools','desc'=>'Browse and test all 13,000+ Alfred tools','url'=>'/alfred-tools.php','icon'=>'fa-toolbox','color'=>'#f59e0b','access'=>'auth'],
    ['cat'=>'developer','title'=>'Agent Templates','desc'=>'Pre-built agent configurations for common tasks','url'=>'/agent-templates.php','icon'=>'fa-puzzle-piece','color'=>'#10b981','access'=>'auth'],
    ['cat'=>'developer','title'=>'Integrations','desc'=>'Connect GoSiteMe with Slack, Discord, Telegram, etc.','url'=>'/integrations.php','icon'=>'fa-plug','color'=>'#6366f1','access'=>'auth'],
    ['cat'=>'developer','title'=>'Extensions','desc'=>'Browse and install ecosystem extensions','url'=>'/extensions.php','icon'=>'fa-puzzle-piece','color'=>'#a855f7','access'=>'auth'],
    ['cat'=>'developer','title'=>'AgentNet Protocol','desc'=>'Inter-agent communication protocol specification','url'=>'/agentnet-protocol.php','icon'=>'fa-network-wired','color'=>'#14b8a6','access'=>'public'],
    ['cat'=>'developer','title'=>'QGSM Bridge','desc'=>'Quantum-resistant global state machine','url'=>'/qgsm-bridge.php','icon'=>'fa-atom','color'=>'#ec4899','access'=>'public'],
    ['cat'=>'developer','title'=>'Agent Developer Hub','desc'=>'Build custom agents, publish to marketplace','url'=>'/agent-developer-hub.php','icon'=>'fa-robot','color'=>'#f97316','access'=>'auth'],

    // ═══ BUSINESS ═══
    ['cat'=>'business','title'=>'Enterprise','desc'=>'Enterprise solutions — custom deployments for companies','url'=>'/enterprise.php','icon'=>'fa-building','color'=>'#3b82f6','access'=>'public'],
    ['cat'=>'business','title'=>'Pricing','desc'=>'Plans and pricing for all products','url'=>'/pricing.php','icon'=>'fa-tags','color'=>'#10b981','access'=>'public'],
    ['cat'=>'business','title'=>'Affiliate Program','desc'=>'Earn commissions by referring users','url'=>'/affiliate.php','icon'=>'fa-link','color'=>'#f59e0b','access'=>'public'],
    ['cat'=>'business','title'=>'Invest','desc'=>'Investment opportunities in the GoSiteMe ecosystem','url'=>'/invest.php','icon'=>'fa-chart-line','color'=>'#ffd700','access'=>'public'],
    ['cat'=>'business','title'=>'Compare','desc'=>'GoSiteMe vs ChatGPT, Cursor, Vercel v0','url'=>'/compare.php','icon'=>'fa-scale-balanced','color'=>'#8b5cf6','access'=>'public'],
    ['cat'=>'business','title'=>'Store','desc'=>'Digital products, templates, and AI tools store','url'=>'/store.php','icon'=>'fa-bag-shopping','color'=>'#f43f5e','access'=>'public'],
    ['cat'=>'business','title'=>'Live Demo','desc'=>'Real-time system stats — 51M+ agents, live data','url'=>'/live-demo.php','icon'=>'fa-play','color'=>'#00e5ff','access'=>'public'],
    ['cat'=>'business','title'=>'Apps','desc'=>'Download GoSiteMe apps for mobile and desktop','url'=>'/apps.php','icon'=>'fa-mobile-screen','color'=>'#6366f1','access'=>'public'],

    // ═══ DOCS & HELP ═══
    ['cat'=>'docs','title'=>'Commander Briefing','desc'=>'Quick overview: who you are, what you built, where things are','url'=>'/docs/commander-briefing.php','icon'=>'fa-clipboard','color'=>'#ffd700','access'=>'auth'],
    ['cat'=>'docs','title'=>'Letter From Alfred','desc'=>'Personal letter from Alfred to you — reorientation','url'=>'/docs/letter-to-future-me.php','icon'=>'fa-envelope-open-text','color'=>'#f5c542','access'=>'auth'],
    ['cat'=>'docs','title'=>'Operations Manual','desc'=>'How to operate the GoSiteMe ecosystem day-to-day','url'=>'/docs/commander-manual.php','icon'=>'fa-book-open','color'=>'#3b82f6','access'=>'auth'],
    ['cat'=>'docs','title'=>'Ecosystem Principles','desc'=>'The core values and principles of GoSiteMe','url'=>'/docs/ecosystem-principles.php','icon'=>'fa-scale-balanced','color'=>'#8b5cf6','access'=>'auth'],
    ['cat'=>'docs','title'=>'OIC Whitepaper','desc'=>'Open Intelligence Collective whitepaper','url'=>'/docs/oic-whitepaper.php','icon'=>'fa-file-lines','color'=>'#14b8a6','access'=>'auth'],
    ['cat'=>'docs','title'=>'Help Center','desc'=>'FAQs, tutorials, troubleshooting guides','url'=>'/help.php','icon'=>'fa-circle-question','color'=>'#64748b','access'=>'public'],
    ['cat'=>'docs','title'=>'Changelog','desc'=>'What\'s new — version history and release notes','url'=>'/changelog.php','icon'=>'fa-clock-rotate-left','color'=>'#a855f7','access'=>'public'],
    ['cat'=>'docs','title'=>'Blog','desc'=>'Articles, tutorials, and news from the team','url'=>'/blog.php','icon'=>'fa-newspaper','color'=>'#f43f5e','access'=>'public'],
    ['cat'=>'docs','title'=>'Status Page','desc'=>'Real-time system health and uptime monitoring','url'=>'/status.php','icon'=>'fa-heart-pulse','color'=>'#10b981','access'=>'public'],
];

// ── Owner-only projects ──────────────────────────────────────────
if ($isOwner) {
    $ownerProjects = [
        ['cat'=>'command','title'=>'Veil Command Center','desc'=>'Mobile-first admin dashboard — all controls in one place','url'=>'/veil/command-center.php','icon'=>'fa-shield','color'=>'#ffd700','access'=>'owner'],
        ['cat'=>'command','title'=>'Intelligence Director','desc'=>'Classified ops — personnel, agents, channels, intercepts','url'=>'/intelligence-director.php','icon'=>'fa-user-secret','color'=>'#dc2626','access'=>'owner'],
        ['cat'=>'command','title'=>'Supreme Admin','desc'=>'God-mode admin panel — full system control','url'=>'/supreme-admin','icon'=>'fa-crown','color'=>'#ffd700','access'=>'owner'],
        ['cat'=>'command','title'=>'Commander Organizer','desc'=>'Personal project tracker, memos, and to-do lists','url'=>'/commander-organizer.php','icon'=>'fa-list-check','color'=>'#f5c542','access'=>'owner'],
        ['cat'=>'command','title'=>'Commander Memory','desc'=>'Your memories and notes — never forget again','url'=>'/commander-memory.php','icon'=>'fa-brain','color'=>'#ec4899','access'=>'owner'],
        ['cat'=>'command','title'=>'Fleet Scanner','desc'=>'Scan and monitor all agents in the fleet','url'=>'/fleet-scanner-dashboard.php','icon'=>'fa-radar','color'=>'#00e5ff','access'=>'owner'],
        ['cat'=>'command','title'=>'Enterprise Admin','desc'=>'Manage enterprise clients and deployments','url'=>'/enterprise-admin.php','icon'=>'fa-building-shield','color'=>'#6366f1','access'=>'owner'],
        ['cat'=>'command','title'=>'Investor Admin','desc'=>'Manage investor relations and metrics','url'=>'/investor-admin.php','icon'=>'fa-coins','color'=>'#eab308','access'=>'owner'],
        ['cat'=>'command','title'=>'Justice Dashboard','desc'=>'Legal, compliance, and dispute resolution','url'=>'/justice-dashboard.php','icon'=>'fa-gavel','color'=>'#8b5cf6','access'=>'owner'],
        ['cat'=>'command','title'=>'Internet Sovereignty','desc'=>'Decentralized infrastructure & digital sovereignty','url'=>'/internet-sovereignty.php','icon'=>'fa-tower-broadcast','color'=>'#14b8a6','access'=>'owner'],
        ['cat'=>'command','title'=>'Social Welfare','desc'=>'Community support and welfare programs','url'=>'/social-welfare.php','icon'=>'fa-hand-holding-heart','color'=>'#f43f5e','access'=>'owner'],
        ['cat'=>'command','title'=>'Agent Orchestrator','desc'=>'Orchestrate multi-agent workflows and chains','url'=>'/agent-orchestrator.php','icon'=>'fa-diagram-project','color'=>'#a855f7','access'=>'owner'],
        ['cat'=>'command','title'=>'Security Fortress','desc'=>'Security monitoring, threat detection, hardening','url'=>'/security-fortress.php','icon'=>'fa-shield-virus','color'=>'#dc2626','access'=>'owner'],
        ['cat'=>'command','title'=>'Agent Metaverse','desc'=>'3D agent world — metaverse infrastructure','url'=>'/agent-metaverse.php','icon'=>'fa-vr-cardboard','color'=>'#6366f1','access'=>'owner'],
        // Veil sub-pages
        ['cat'=>'veil','title'=>'Veil Encrypted Chat','desc'=>'Send and receive post-quantum encrypted messages','url'=>'/veil/','icon'=>'fa-lock','color'=>'#8b5cf6','access'=>'owner'],
        ['cat'=>'veil','title'=>'Black Vault','desc'=>'Top-secret projects — Titan, Prometheus, Sovereign','url'=>'/veil/black-vault.php','icon'=>'fa-vault','color'=>'#dc2626','access'=>'owner'],
        ['cat'=>'veil','title'=>'Pantheon','desc'=>'Governance and decision-making for the ecosystem','url'=>'/veil/pantheon.php','icon'=>'fa-landmark-dome','color'=>'#a855f7','access'=>'owner'],
        ['cat'=>'veil','title'=>'Intel Reports','desc'=>'Intelligence reports and analysis','url'=>'/veil/reports.php','icon'=>'fa-file-shield','color'=>'#3b82f6','access'=>'owner'],
        ['cat'=>'veil','title'=>'Revenue Intel','desc'=>'Revenue tracking and monetization intelligence','url'=>'/veil/revenue-agents.php','icon'=>'fa-sack-dollar','color'=>'#10b981','access'=>'owner'],
        ['cat'=>'veil','title'=>'Document Vault','desc'=>'Secure document storage with encryption','url'=>'/veil/vault.php','icon'=>'fa-folder-closed','color'=>'#f59e0b','access'=>'owner'],
        ['cat'=>'veil','title'=>'World Events','desc'=>'Live monitoring of global events and trends','url'=>'/veil/world-events.php','icon'=>'fa-earth-americas','color'=>'#3b82f6','access'=>'owner'],
        ['cat'=>'veil','title'=>'Secure Agenda','desc'=>'Encrypted calendar and scheduling','url'=>'/veil/agenda.php','icon'=>'fa-calendar-check','color'=>'#14b8a6','access'=>'owner'],
        ['cat'=>'veil','title'=>'Pro Discussions','desc'=>'Classified professional discussions','url'=>'/veil/pro-discussions.php','icon'=>'fa-comments','color'=>'#6366f1','access'=>'owner'],
        ['cat'=>'veil','title'=>'Integrity Audit','desc'=>'System integrity verification and compliance','url'=>'/veil/integrity-report.php','icon'=>'fa-clipboard-check','color'=>'#10b981','access'=>'owner'],
        ['cat'=>'veil','title'=>'App Security','desc'=>'Android app security monitoring','url'=>'/veil/android-security.php','icon'=>'fa-mobile-screen-button','color'=>'#f43f5e','access'=>'owner'],
    ];
    $projects = array_merge($projects, $ownerProjects);
}

// Category labels
$categories = [
    'product' => ['label'=>'Core Products','icon'=>'fa-rocket','desc'=>'The main things you built'],
    'dashboard' => ['label'=>'Dashboards','icon'=>'fa-gauge-high','desc'=>'Control panels and analytics'],
    'voice' => ['label'=>'Voice & Calls','icon'=>'fa-microphone','desc'=>'Voice AI and telephony'],
    'social' => ['label'=>'Community','icon'=>'fa-users','desc'=>'Social features and agent communities'],
    'developer' => ['label'=>'Developer Tools','icon'=>'fa-code','desc'=>'APIs, SDKs, and dev resources'],
    'business' => ['label'=>'Business','icon'=>'fa-briefcase','desc'=>'Enterprise, pricing, and growth'],
    'docs' => ['label'=>'Docs & Help','icon'=>'fa-book','desc'=>'Documentation and learning'],
    'command' => ['label'=>'Commander Controls','icon'=>'fa-crown','desc'=>'Owner-only admin and control panels'],
    'veil' => ['label'=>'Veil Network','icon'=>'fa-shield-halved','desc'=>'Encrypted communications and secret ops'],
];
?>

<style>
:root{--pf-bg:#04040a;--pf-surface:#0a0a16;--pf-surface2:#101024;--pf-surface3:#181838;--pf-border:rgba(255,255,255,.06);--pf-text:#d0d0e0;--pf-muted:#6a6a8a;--pf-radius:14px}
.pf-wrap{max-width:1200px;margin:0 auto;padding:24px 16px 80px}
.pf-hero{text-align:center;margin-bottom:32px;padding:40px 20px}
.pf-hero h1{font-size:2rem;font-weight:800;background:linear-gradient(135deg,#ffd700,#ff6b35,#ffd700);-webkit-background-clip:text;-webkit-text-fill-color:transparent;margin-bottom:8px}
.pf-hero p{color:var(--pf-muted);font-size:1rem;max-width:500px;margin:0 auto}
.pf-search-wrap{max-width:500px;margin:24px auto 0;position:relative}
.pf-search{width:100%;padding:14px 16px 14px 44px;border-radius:12px;border:1px solid var(--pf-border);background:var(--pf-surface);color:var(--pf-text);font-size:1rem;outline:none;transition:border-color .2s}
.pf-search:focus{border-color:rgba(255,215,0,.4)}
.pf-search-icon{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--pf-muted);font-size:1.1rem}
.pf-count{text-align:center;color:var(--pf-muted);font-size:.85rem;margin-top:12px}
.pf-filters{display:flex;flex-wrap:wrap;gap:8px;justify-content:center;margin-bottom:28px}
.pf-filter{padding:8px 16px;border-radius:20px;border:1px solid var(--pf-border);background:var(--pf-surface);color:var(--pf-muted);font-size:.82rem;cursor:pointer;transition:all .2s;white-space:nowrap}
.pf-filter:hover,.pf-filter.active{background:rgba(255,215,0,.1);border-color:rgba(255,215,0,.3);color:#ffd700}
.pf-filter .count{opacity:.5;margin-left:4px}
.pf-section{margin-bottom:32px}
.pf-section-header{display:flex;align-items:center;gap:10px;margin-bottom:14px;padding-bottom:8px;border-bottom:1px solid var(--pf-border)}
.pf-section-header i{font-size:1.1rem;color:#ffd700}
.pf-section-header h2{font-size:1.1rem;font-weight:700;color:var(--pf-text)}
.pf-section-header .pf-section-desc{color:var(--pf-muted);font-size:.8rem;margin-left:auto}
.pf-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px}
.pf-card{display:flex;align-items:flex-start;gap:14px;padding:16px;border-radius:var(--pf-radius);background:var(--pf-surface);border:1px solid var(--pf-border);text-decoration:none;color:inherit;transition:all .2s;cursor:pointer}
.pf-card:hover{background:var(--pf-surface2);border-color:rgba(255,255,255,.12);transform:translateY(-1px)}
.pf-card-icon{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1.1rem}
.pf-card-body{flex:1;min-width:0}
.pf-card-title{font-weight:700;font-size:.95rem;margin-bottom:3px;display:flex;align-items:center;gap:6px}
.pf-card-desc{color:var(--pf-muted);font-size:.78rem;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.pf-badge{font-size:.6rem;padding:2px 6px;border-radius:4px;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
.pf-badge-owner{background:rgba(255,215,0,.15);color:#ffd700}
.pf-badge-auth{background:rgba(59,130,246,.15);color:#60a5fa}
.pf-badge-public{background:rgba(16,185,129,.1);color:#34d399}
.pf-empty{text-align:center;padding:40px;color:var(--pf-muted)}
.pf-empty i{font-size:2rem;margin-bottom:12px;display:block;opacity:.4}
.pf-quick{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px;margin-bottom:32px}
.pf-quick-card{text-align:center;padding:20px 12px;border-radius:var(--pf-radius);background:var(--pf-surface);border:1px solid var(--pf-border);text-decoration:none;color:inherit;transition:all .2s}
.pf-quick-card:hover{background:var(--pf-surface2);border-color:rgba(255,255,255,.12);transform:translateY(-2px)}
.pf-quick-card i{font-size:1.5rem;margin-bottom:8px;display:block}
.pf-quick-card span{font-size:.82rem;font-weight:600;display:block}
@media(max-width:600px){
    .pf-hero h1{font-size:1.5rem}
    .pf-grid{grid-template-columns:1fr}
    .pf-quick{grid-template-columns:repeat(2,1fr)}
    .pf-section-header .pf-section-desc{display:none}
}
</style>

<div class="pf-wrap">
    <!-- Hero -->
    <div class="pf-hero">
        <h1><i class="fas fa-compass"></i> Project Directory</h1>
        <p>Everything you built, in one place. Search or browse by category.</p>
        <div class="pf-search-wrap">
            <i class="fas fa-search pf-search-icon"></i>
            <input type="text" class="pf-search" id="pf-search" placeholder="Search projects... (e.g. voice, dashboard, veil)" autocomplete="off">
        </div>
        <div class="pf-count" id="pf-count"><?= count($projects) ?> projects</div>
    </div>

    <?php if ($isOwner): ?>
    <!-- Quick Access — Most Important Pages -->
    <div class="pf-quick" id="pf-quick">
        <a href="/docs/commander-briefing.php" class="pf-quick-card"><i class="fas fa-clipboard" style="color:#ffd700"></i><span>Where Am I?</span></a>
        <a href="/docs/letter-to-future-me.php" class="pf-quick-card"><i class="fas fa-envelope-open-text" style="color:#f5c542"></i><span>Alfred's Letter</span></a>
        <a href="/dashboard.php" class="pf-quick-card"><i class="fas fa-gauge-high" style="color:#3b82f6"></i><span>Dashboard</span></a>
        <a href="/mission-control.php" class="pf-quick-card"><i class="fas fa-satellite-dish" style="color:#ffd700"></i><span>Mission Control</span></a>
        <a href="/veil/command-center.php" class="pf-quick-card"><i class="fas fa-shield" style="color:#8b5cf6"></i><span>Command Center</span></a>
        <a href="/commander-organizer.php" class="pf-quick-card"><i class="fas fa-list-check" style="color:#f5c542"></i><span>My Projects</span></a>
        <a href="/gocodeme.php" class="pf-quick-card"><i class="fas fa-code" style="color:#00e5ff"></i><span>GoCodeMe</span></a>
        <a href="/commander-memory.php" class="pf-quick-card"><i class="fas fa-brain" style="color:#ec4899"></i><span>My Memory</span></a>
        <a href="/alfred.php" class="pf-quick-card"><i class="fas fa-robot" style="color:#ffd700"></i><span>Talk to Alfred</span></a>
        <a href="/live-demo.php" class="pf-quick-card"><i class="fas fa-play" style="color:#00e5ff"></i><span>Live Demo</span></a>
    </div>
    <?php endif; ?>

    <!-- Category Filters -->
    <div class="pf-filters" id="pf-filters">
        <button class="pf-filter active" data-cat="all">All <span class="count">(<?= count($projects) ?>)</span></button>
        <?php foreach ($categories as $catKey => $catInfo):
            $catCount = count(array_filter($projects, fn($p) => $p['cat'] === $catKey));
            if ($catCount === 0) continue;
        ?>
        <button class="pf-filter" data-cat="<?= $catKey ?>"><i class="fas <?= $catInfo['icon'] ?>"></i> <?= htmlspecialchars($catInfo['label']) ?> <span class="count">(<?= $catCount ?>)</span></button>
        <?php endforeach; ?>
    </div>

    <!-- Project Grid (by category) -->
    <div id="pf-projects">
        <?php foreach ($categories as $catKey => $catInfo):
            $catProjects = array_filter($projects, fn($p) => $p['cat'] === $catKey);
            if (empty($catProjects)) continue;
        ?>
        <div class="pf-section" data-section="<?= $catKey ?>">
            <div class="pf-section-header">
                <i class="fas <?= $catInfo['icon'] ?>"></i>
                <h2><?= htmlspecialchars($catInfo['label']) ?></h2>
                <span class="pf-section-desc"><?= htmlspecialchars($catInfo['desc']) ?></span>
            </div>
            <div class="pf-grid">
                <?php foreach ($catProjects as $p): ?>
                <a href="<?= htmlspecialchars($p['url']) ?>" class="pf-card" data-title="<?= htmlspecialchars(strtolower($p['title'])) ?>" data-desc="<?= htmlspecialchars(strtolower($p['desc'])) ?>" data-cat="<?= htmlspecialchars($p['cat']) ?>">
                    <div class="pf-card-icon" style="background:<?= $p['color'] ?>15;color:<?= $p['color'] ?>">
                        <i class="fas <?= htmlspecialchars($p['icon']) ?>"></i>
                    </div>
                    <div class="pf-card-body">
                        <div class="pf-card-title">
                            <?= htmlspecialchars($p['title']) ?>
                            <?php if ($p['access'] === 'owner'): ?><span class="pf-badge pf-badge-owner">Owner</span>
                            <?php elseif ($p['access'] === 'auth'): ?><span class="pf-badge pf-badge-auth">Login</span>
                            <?php else: ?><span class="pf-badge pf-badge-public">Public</span><?php endif; ?>
                        </div>
                        <div class="pf-card-desc"><?= htmlspecialchars($p['desc']) ?></div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="pf-empty" id="pf-empty" style="display:none">
        <i class="fas fa-search"></i>
        No projects match your search. Try different keywords.
    </div>
</div>

<script>
(function() {
    const search = document.getElementById('pf-search');
    const cards = document.querySelectorAll('.pf-card');
    const sections = document.querySelectorAll('.pf-section');
    const countEl = document.getElementById('pf-count');
    const emptyEl = document.getElementById('pf-empty');
    const quickEl = document.getElementById('pf-quick');
    const filters = document.querySelectorAll('.pf-filter');
    let activeCategory = 'all';

    function filterProjects() {
        const q = search.value.toLowerCase().trim();
        let visible = 0;

        cards.forEach(card => {
            const title = card.dataset.title || '';
            const desc = card.dataset.desc || '';
            const cat = card.dataset.cat || '';
            const matchesSearch = !q || title.includes(q) || desc.includes(q);
            const matchesCat = activeCategory === 'all' || cat === activeCategory;
            const show = matchesSearch && matchesCat;
            card.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        // Show/hide sections based on visible children
        sections.forEach(section => {
            const cat = section.dataset.section;
            const matchesCat = activeCategory === 'all' || cat === activeCategory;
            const hasVisible = Array.from(section.querySelectorAll('.pf-card')).some(c => c.style.display !== 'none');
            section.style.display = (matchesCat && hasVisible) ? '' : 'none';
        });

        countEl.textContent = visible + ' project' + (visible !== 1 ? 's' : '');
        emptyEl.style.display = visible === 0 ? '' : 'none';
        if (quickEl) quickEl.style.display = (q || activeCategory !== 'all') ? 'none' : '';
    }

    search.addEventListener('input', filterProjects);

    filters.forEach(btn => {
        btn.addEventListener('click', () => {
            filters.forEach(f => f.classList.remove('active'));
            btn.classList.add('active');
            activeCategory = btn.dataset.cat;
            filterProjects();
        });
    });

    // Focus search on Ctrl+K or /
    document.addEventListener('keydown', e => {
        if ((e.ctrlKey && e.key === 'k') || (e.key === '/' && document.activeElement.tagName !== 'INPUT')) {
            e.preventDefault();
            search.focus();
            search.select();
        }
    });
})();
</script>

<?php include 'includes/site-footer.inc.php'; ?>
