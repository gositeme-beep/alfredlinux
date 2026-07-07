<?php
/**
 * GoSiteMe Business Revenue Agents API
 * Autonomous business intelligence agents that audit products,
 * find revenue opportunities, and coordinate monetization strategy
 */
define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// ─── Auth ────────────────────────────────────────────────────────
session_start();
$client_id = $_SESSION['client_id'] ?? null;
$is_admin = ($client_id == 33);

$internal = $_SERVER['HTTP_X_INTERNAL_SECRET'] ?? $_REQUEST['internal_secret'] ?? '';
$is_internal = !empty(INTERNAL_SECRET) && hash_equals(INTERNAL_SECRET, $internal);

if (!$client_id && !$is_internal) {
    echo json_encode(['error' => 'Authentication required']); exit;
}
if (!$is_admin && !$is_internal) {
    echo json_encode(['error' => 'Commander access required']); exit;
require_once dirname(__DIR__) . '/includes/api-security.php';
}

// ─── DB Setup ────────────────────────────────────────────────────
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $pdo->exec("CREATE TABLE IF NOT EXISTS `revenue_agents` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `agent_id` VARCHAR(50) UNIQUE NOT NULL,
        `name` VARCHAR(100) NOT NULL,
        `role` VARCHAR(100) NOT NULL,
        `division` VARCHAR(50) NOT NULL,
        `specialty` TEXT,
        `status` ENUM('active','analyzing','idle','reporting') DEFAULT 'active',
        `trust_score` INT DEFAULT 85,
        `tasks_completed` INT DEFAULT 0,
        `revenue_generated` DECIMAL(12,2) DEFAULT 0,
        `last_report` TEXT,
        `last_active` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `revenue_audits` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `audit_id` VARCHAR(50) UNIQUE NOT NULL,
        `agent_id` VARCHAR(50) NOT NULL,
        `product_category` VARCHAR(50) NOT NULL,
        `product_name` VARCHAR(150) NOT NULL,
        `current_revenue` DECIMAL(12,2) DEFAULT 0,
        `potential_revenue` DECIMAL(12,2) DEFAULT 0,
        `opportunity_score` INT DEFAULT 0,
        `findings` TEXT,
        `recommendations` TEXT,
        `priority` ENUM('critical','high','medium','low') DEFAULT 'medium',
        `status` ENUM('pending','in-progress','completed','actionable') DEFAULT 'pending',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `revenue_opportunities` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `opp_id` VARCHAR(50) UNIQUE NOT NULL,
        `title` VARCHAR(200) NOT NULL,
        `category` VARCHAR(50) NOT NULL,
        `description` TEXT,
        `projected_monthly` DECIMAL(12,2) DEFAULT 0,
        `projected_annual` DECIMAL(12,2) DEFAULT 0,
        `effort_level` ENUM('low','medium','high') DEFAULT 'medium',
        `time_to_revenue` VARCHAR(50),
        `assigned_agents` TEXT,
        `status` ENUM('identified','researching','approved','building','launched','monitoring') DEFAULT 'identified',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `revenue_reports` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `report_id` VARCHAR(50) UNIQUE NOT NULL,
        `report_type` VARCHAR(50) NOT NULL,
        `title` VARCHAR(200) NOT NULL,
        `summary` TEXT,
        `data` JSON,
        `generated_by` VARCHAR(50),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error']); exit;
}

$action = $_REQUEST['action'] ?? 'dashboard';

switch ($action) {

// ─── Dashboard Overview ─────────────────────────────────────────
case 'dashboard':
    $agents = $pdo->query("SELECT COUNT(*) as total, 
        SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status='analyzing' THEN 1 ELSE 0 END) as analyzing,
        SUM(tasks_completed) as total_tasks,
        SUM(revenue_generated) as total_revenue,
        AVG(trust_score) as avg_trust
        FROM revenue_agents")->fetch();

    $audits = $pdo->query("SELECT COUNT(*) as total,
        SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN priority='critical' THEN 1 ELSE 0 END) as critical,
        SUM(potential_revenue) as total_potential
        FROM revenue_audits")->fetch();

    $opps = $pdo->query("SELECT COUNT(*) as total,
        SUM(projected_monthly) as monthly_potential,
        SUM(projected_annual) as annual_potential,
        SUM(CASE WHEN status='launched' THEN 1 ELSE 0 END) as launched
        FROM revenue_opportunities")->fetch();

    $divisions = $pdo->query("SELECT division, COUNT(*) as count, AVG(trust_score) as trust 
        FROM revenue_agents GROUP BY division ORDER BY count DESC")->fetchAll();

    echo json_encode([
        'success' => true,
        'dashboard' => [
            'agents' => $agents,
            'audits' => $audits,
            'opportunities' => $opps,
            'divisions' => $divisions,
            'ecosystem_health' => 'OPERATIONAL'
        ]
    ]);
    break;

// ─── List Agents ─────────────────────────────────────────────────
case 'agents':
    $division = $_GET['division'] ?? null;
    $sql = "SELECT * FROM revenue_agents";
    $params = [];
    if ($division) {
        $sql .= " WHERE division = ?";
        $params[] = $division;
    }
    $sql .= " ORDER BY division, trust_score DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode(['success' => true, 'agents' => $stmt->fetchAll()]);
    break;

// ─── Full Ecosystem Audit ────────────────────────────────────────
case 'run-audit':
    $products = [
        // SaaS
        ['SaaS', 'Free Tier', 0, 500, 'Lead generation funnel, converts to paid at ~3-5%'],
        ['SaaS', 'Starter Plan ($3.99/mo)', 3.99, 2000, 'Entry-level conversion target'],
        ['SaaS', 'Professional Plan ($9.99/mo)', 9.99, 5000, 'Core revenue driver, sweet spot pricing'],
        ['SaaS', 'Enterprise Plan ($24.99/mo)', 24.99, 8000, 'Mid-market teams, high retention'],
        ['SaaS', 'Enterprise Plus ($99/mo)', 99, 15000, 'Premium tier, voice cloning included'],
        ['SaaS', 'Custom Plan ($299+/mo)', 299, 25000, 'White-label, dedicated infra'],
        // Voice/Telecom
        ['Voice', 'AI Phone Agents ($29/mo)', 29, 12000, 'High-margin voice product'],
        ['Voice', 'Local Numbers ($3/mo)', 3, 3000, 'Sticky recurring service'],
        ['Voice', 'Voice Cloning ($5/profile)', 5, 4000, 'Unique differentiator'],
        ['Voice', 'Conference Rooms', 0, 6000, 'Freemium, upgrade path to enterprise'],
        ['Voice', 'IVR Builder', 0, 3000, 'Feature that drives enterprise adoption'],
        ['Voice', 'Call Center Suite', 49, 20000, 'Highest-value voice product'],
        // Marketplace
        ['Marketplace', 'AI Tool Sales (30% commission)', 0, 8000, '70/30 creator split, scales with creators'],
        ['Marketplace', 'Marketplace Creator Tools', 0, 2000, 'Ecosystem growth driver'],
        // Gaming
        ['Gaming', 'Chess with AI ($1-5 wagers)', 0, 3000, 'Engagement + micro-transactions'],
        ['Gaming', 'Game Arcade (8+ games)', 0, 5000, 'Retention tool, ad potential'],
        ['Gaming', 'WebXR/VR Games', 0, 7000, 'Cutting-edge differentiator'],
        ['Gaming', 'Voice-Activated Games', 0, 2000, 'Unique voice game experience'],
        // Crypto/DeFi
        ['Crypto', 'GSM Token Economy', 0, 15000, 'Platform currency, exchange listings'],
        ['Crypto', 'Token Launch Center', 0, 10000, 'Surpasses Dexlab, creator fees'],
        ['Crypto', 'DeFi Yield/Staking', 0, 8000, 'Passive income for users and platform'],
        ['Crypto', 'QR/NFC Crypto Transfers', 0, 5000, 'Real-world payment infrastructure'],
        ['Crypto', 'Token Swap (Jupiter)', 0, 4000, 'Swap fee revenue'],
        // Developer
        ['Developer', 'API Access (tiered)', 0, 6000, '13,000+ tools, developer adoption'],
        ['Developer', 'SDKs (Node, Python, PHP)', 0, 3000, 'Ecosystem lock-in'],
        ['Developer', 'Game Engine SDK', 0, 2000, 'Third-party game development'],
        // Infrastructure
        ['Infrastructure', 'AI Server Hardware', 0, 20000, 'High-ticket hardware sales'],
        ['Infrastructure', 'White-Label Platform', 0, 15000, 'Recurring enterprise licensing'],
        ['Infrastructure', 'Reseller Program', 0, 8000, 'Channel sales multiplication'],
        // Creative
        ['Creative', 'AI Image Generation', 0, 4000, 'Credit-based micro-transactions'],
        ['Creative', 'Website Builder (AI)', 0, 6000, 'Multi-agent pipeline'],
        ['Creative', 'GoCodeMe IDE', 0, 5000, 'Developer tooling revenue'],
        // Business Services
        ['Business', 'Affiliate Program (20-30%)', 0, 5000, 'Acquisition cost arbitrage'],
        ['Business', 'Investor Portal (SAFE)', 0, 50000, 'Seed investments $1K+'],
        ['Business', 'Domain Registration', 0, 3000, 'Recurring domain renewals'],
        // Emerging
        ['Emerging', 'ZPE Research Lab', 0, 10000, 'Patent + licensing potential'],
        ['Emerging', 'Metaverse Presence', 0, 8000, 'Virtual land/avatar sales'],
        ['Emerging', 'Circuit Designer App', 0, 5000, 'EDA tool subscription model'],
        ['Emerging', 'Android App (TWA)', 0, 4000, 'Mobile user acquisition'],
    ];

    $audit_count = 0;
    foreach ($products as $p) {
        $audit_id = 'AUD-' . strtoupper(substr(md5($p[1] . time()), 0, 8));
        $agents_in_div = $pdo->prepare("SELECT agent_id FROM revenue_agents WHERE division = ? ORDER BY trust_score DESC LIMIT 1");
        $agents_in_div->execute([mapDivision($p[0])]);
        $agent = $agents_in_div->fetch();
        $agent_id = $agent['agent_id'] ?? 'BRA-CHIEF';

        $opp_score = min(100, intval(($p[3] / 500) * 10));
        $priority = $opp_score >= 80 ? 'critical' : ($opp_score >= 60 ? 'high' : ($opp_score >= 40 ? 'medium' : 'low'));

        $recommendations = generateRecommendations($p[0], $p[1], $p[2], $p[3]);

        $stmt = $pdo->prepare("INSERT INTO revenue_audits (audit_id, agent_id, product_category, product_name, current_revenue, potential_revenue, opportunity_score, findings, recommendations, priority, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed')
            ON DUPLICATE KEY UPDATE findings=VALUES(findings), recommendations=VALUES(recommendations), status='completed'");
        $stmt->execute([$audit_id, $agent_id, $p[0], $p[1], $p[2], $p[3], $opp_score, $p[4], $recommendations, $priority]);
        $audit_count++;
    }

    // Generate summary report
    $total_potential = $pdo->query("SELECT SUM(potential_revenue) FROM revenue_audits")->fetchColumn();
    $critical_count = $pdo->query("SELECT COUNT(*) FROM revenue_audits WHERE priority='critical'")->fetchColumn();

    $report_id = 'RPT-BIZ-' . date('Ymd-His');
    $pdo->prepare("INSERT INTO revenue_reports (report_id, report_type, title, summary, data, generated_by) VALUES (?,?,?,?,?,?)")
        ->execute([$report_id, 'ecosystem-audit', 'Full Ecosystem Revenue Audit',
            "Audited {$audit_count} products across 10 categories. Total monthly potential: \${$total_potential}. Critical opportunities: {$critical_count}.",
            json_encode(['products_audited' => $audit_count, 'total_potential' => $total_potential, 'critical' => $critical_count]),
            'BRA-CHIEF'
        ]);

    // Update agent task counts
    $pdo->exec("UPDATE revenue_agents SET tasks_completed = tasks_completed + 1, last_active = NOW() WHERE status = 'active'");

    echo json_encode([
        'success' => true,
        'message' => "Ecosystem audit complete",
        'products_audited' => $audit_count,
        'total_monthly_potential' => floatval($total_potential),
        'critical_opportunities' => intval($critical_count),
        'report_id' => $report_id
    ]);
    break;

// ─── Get Audit Results ───────────────────────────────────────────
case 'audits':
    $category = $_GET['category'] ?? null;
    $priority = $_GET['priority'] ?? null;
    $sql = "SELECT * FROM revenue_audits WHERE 1=1";
    $params = [];
    if ($category) { $sql .= " AND product_category = ?"; $params[] = $category; }
    if ($priority) { $sql .= " AND priority = ?"; $params[] = $priority; }
    $sql .= " ORDER BY potential_revenue DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode(['success' => true, 'audits' => $stmt->fetchAll()]);
    break;

// ─── Revenue Opportunities ──────────────────────────────────────
case 'opportunities':
    $status = $_GET['status'] ?? null;
    $sql = "SELECT * FROM revenue_opportunities";
    $params = [];
    if ($status) { $sql .= " WHERE status = ?"; $params[] = $status; }
    $sql .= " ORDER BY projected_annual DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode(['success' => true, 'opportunities' => $stmt->fetchAll()]);
    break;

// ─── Agent Coordination ─────────────────────────────────────────
case 'coordinate':
    $topic = $_POST['topic'] ?? $_GET['topic'] ?? '';
    if (empty($topic)) {
        echo json_encode(['error' => 'Topic required']); exit;
    }

    // Find relevant agents based on topic
    $agents = $pdo->prepare("SELECT * FROM revenue_agents WHERE specialty LIKE ? OR division LIKE ? ORDER BY trust_score DESC LIMIT 5");
    $search = "%{$topic}%";
    $agents->execute([$search, $search]);
    $relevant = $agents->fetchAll();

    if (empty($relevant)) {
        $relevant = $pdo->query("SELECT * FROM revenue_agents ORDER BY trust_score DESC LIMIT 5")->fetchAll();
    }

    $coordination = [
        'topic' => $topic,
        'agents_assigned' => count($relevant),
        'team' => array_map(function($a) {
            return ['agent_id' => $a['agent_id'], 'name' => $a['name'], 'role' => $a['role'], 'trust' => $a['trust_score']];
        }, $relevant),
        'action_plan' => generateActionPlan($topic),
        'timestamp' => date('c')
    ];

    echo json_encode(['success' => true, 'coordination' => $coordination]);
    break;

// ─── Generate New Product Ideas ──────────────────────────────────
case 'ideate':
    $ideas = [
        ['Mobile Game Pack: 10 Casual Games', 'Gaming', 'Bundle of casual mobile games (puzzle, arcade, word) with AI opponents and GSM token rewards. Sell as premium pack or individual games.', 3000, 36000, 'medium', '2-3 months', 'BRA-GAMING,BRA-PRODUCT'],
        ['AI Voice Assistant App (Standalone)', 'Voice', 'Standalone voice assistant app powered by Alfred AI. Always-on voice control for phone. Freemium with premium features.', 5000, 60000, 'high', '3-4 months', 'BRA-VOICE,BRA-PRODUCT'],
        ['Crypto Payment Gateway for Merchants', 'Crypto', 'White-label crypto payment gateway. Accept SOL/ETH/BTC/GSM. 1% transaction fee. Competes with BitPay/Coinbase Commerce.', 8000, 96000, 'high', '2-3 months', 'BRA-CRYPTO,BRA-ENTERPRISE'],
        ['AI-Powered Resume Builder', 'Creative', 'AI resume/CV builder with templates. Freemium model. High-volume low-cost product.', 4000, 48000, 'low', '1-2 months', 'BRA-PRODUCT,BRA-GROWTH'],
        ['Educational Course Platform', 'Business', 'AI/coding/crypto courses with certificates. Subscription or per-course pricing.', 6000, 72000, 'medium', '3-4 months', 'BRA-CONTENT,BRA-GROWTH'],
        ['AI Chatbot Widget (Embeddable)', 'Developer', 'Embeddable AI chatbot for websites. $19-99/mo per site. Easy install. Competes with Intercom/Drift.', 7000, 84000, 'medium', '2-3 months', 'BRA-PRODUCT,BRA-ENTERPRISE'],
        ['Digital Art NFT Generator', 'Creative', 'AI image → NFT minting pipeline on Solana. Mint fee + marketplace royalties.', 3000, 36000, 'medium', '2-3 months', 'BRA-CRYPTO,BRA-CREATIVE'],
        ['VR Meeting Rooms (Premium)', 'Emerging', 'Premium VR conference rooms with custom environments, avatars, screen sharing. $29/mo per room.', 4000, 48000, 'high', '4-5 months', 'BRA-METAVERSE,BRA-ENTERPRISE'],
        ['AI Security Audit Service', 'Business', 'Automated security scanning for client websites. Report generation. $49-199/scan.', 5000, 60000, 'low', '2-3 months', 'BRA-SECURITY,BRA-ENTERPRISE'],
        ['Browser Extension Marketplace', 'Developer', 'Curated AI browser extensions. 30% commission on premium extensions.', 2000, 24000, 'low', '3-4 months', 'BRA-PRODUCT,BRA-GROWTH'],
        ['SMS/WhatsApp Marketing Bot', 'Voice', 'AI-powered marketing campaigns via SMS and WhatsApp. Per-message pricing.', 6000, 72000, 'medium', '1-2 months', 'BRA-VOICE,BRA-GROWTH'],
        ['ZPE Circuit Kits (Physical+Digital)', 'Emerging', 'Sell physical circuit experiment kits with digital simulation companion app. Educational science kits.', 2000, 24000, 'high', '4-6 months', 'BRA-ZPE,BRA-PRODUCT'],
    ];

    $inserted = 0;
    foreach ($ideas as $idea) {
        $opp_id = 'OPP-' . strtoupper(substr(md5($idea[0]), 0, 8));
        $stmt = $pdo->prepare("INSERT IGNORE INTO revenue_opportunities (opp_id, title, category, description, projected_monthly, projected_annual, effort_level, time_to_revenue, assigned_agents, status) 
            VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$opp_id, $idea[0], $idea[1], $idea[2], $idea[3], $idea[4], $idea[5], $idea[6], $idea[7], 'identified']);
        $inserted += $stmt->rowCount();
    }

    echo json_encode([
        'success' => true,
        'message' => "$inserted new product ideas generated",
        'total_ideas' => count($ideas),
        'total_projected_annual' => array_sum(array_column($ideas, 4))
    ]);
    break;

// ─── Revenue Reports ─────────────────────────────────────────────
case 'reports':
    $reports = $pdo->query("SELECT * FROM revenue_reports ORDER BY created_at DESC LIMIT 20")->fetchAll();
    echo json_encode(['success' => true, 'reports' => $reports]);
    break;

// ─── Generate Revenue Report ─────────────────────────────────────
case 'generate-report':
    $type = $_POST['type'] ?? $_GET['type'] ?? 'summary';

    $agents = $pdo->query("SELECT * FROM revenue_agents ORDER BY revenue_generated DESC")->fetchAll();
    $audits = $pdo->query("SELECT product_category, COUNT(*) as count, SUM(current_revenue) as current, SUM(potential_revenue) as potential FROM revenue_audits GROUP BY product_category ORDER BY potential DESC")->fetchAll();
    $opps = $pdo->query("SELECT * FROM revenue_opportunities ORDER BY projected_annual DESC")->fetchAll();

    $total_current = array_sum(array_column($audits, 'current'));
    $total_potential = array_sum(array_column($audits, 'potential'));
    $total_opp_annual = array_sum(array_column($opps, 'projected_annual'));

    $report_data = [
        'agent_count' => count($agents),
        'top_agents' => array_slice(array_map(fn($a) => ['name' => $a['name'], 'revenue' => $a['revenue_generated'], 'trust' => $a['trust_score']], $agents), 0, 5),
        'revenue_by_category' => $audits,
        'current_monthly_revenue' => $total_current,
        'potential_monthly_revenue' => $total_potential,
        'growth_opportunity' => $total_potential - $total_current,
        'new_opportunities' => count($opps),
        'opportunity_annual_value' => $total_opp_annual,
        'top_opportunities' => array_slice(array_map(fn($o) => ['title' => $o['title'], 'annual' => $o['projected_annual'], 'effort' => $o['effort_level']], $opps), 0, 5),
        'recommendations' => [
            'Focus on Crypto Payment Gateway — highest projected revenue ($96K/yr)',
            'Launch AI Chatbot Widget — competes with $10B+ market (Intercom, Drift)',
            'Bundle casual games — low effort, high engagement, GSM token integration',
            'Scale affiliate program — current 20-30% commission drives acquisition',
            'Push Enterprise Plus tier — highest margin at $99/mo',
            'Expand voice products — 52 products but needs marketing push',
            'List GSM token on centralized exchanges — massive liquidity event'
        ]
    ];

    $report_id = 'RPT-REV-' . date('Ymd-His');
    $pdo->prepare("INSERT INTO revenue_reports (report_id, report_type, title, summary, data, generated_by) VALUES (?,?,?,?,?,?)")
        ->execute([$report_id, $type, 'Revenue Intelligence Report — ' . date('M j, Y'),
            "Current: \${$total_current}/mo | Potential: \${$total_potential}/mo | New Opportunities: " . count($opps) . " (\${$total_opp_annual}/yr)",
            json_encode($report_data),
            'BRA-CHIEF'
        ]);

    echo json_encode([
        'success' => true,
        'report_id' => $report_id,
        'report' => $report_data
    ]);
    break;

// ─── Update Agent Status ─────────────────────────────────────────
case 'update-agent':
    $agent_id = $_POST['agent_id'] ?? '';
    $status = $_POST['status'] ?? '';
    $trust_delta = intval($_POST['trust_delta'] ?? 0);
    $revenue_add = floatval($_POST['revenue_add'] ?? 0);

    if (empty($agent_id)) { echo json_encode(['error' => 'agent_id required']); exit; }

    $updates = [];
    $params = [];
    if ($status) { $updates[] = "status = ?"; $params[] = $status; }
    if ($trust_delta) { $updates[] = "trust_score = LEAST(100, GREATEST(0, trust_score + ?))"; $params[] = $trust_delta; }
    if ($revenue_add) { $updates[] = "revenue_generated = revenue_generated + ?"; $params[] = $revenue_add; }
    $updates[] = "last_active = NOW()";
    $params[] = $agent_id;

    $pdo->prepare("UPDATE revenue_agents SET " . implode(', ', $updates) . " WHERE agent_id = ?")->execute($params);
    echo json_encode(['success' => true, 'message' => "Agent {$agent_id} updated"]);
    break;

// ─── Seed Business Revenue Agents ────────────────────────────────
case 'seed':
    $agents = [
        // Revenue Command
        ['BRA-CHIEF', 'Chief Revenue Officer', 'Revenue Strategy Director', 'Revenue Command', 'Overall revenue strategy, P&L ownership, growth targets, investor relations', 95],
        ['BRA-ANALYTICS', 'Revenue Analyst Prime', 'Data Analytics Lead', 'Revenue Command', 'Revenue forecasting, trend analysis, KPI tracking, financial modeling', 92],
        ['BRA-STRATEGY', 'Strategic Planner', 'Business Strategy Lead', 'Revenue Command', 'Market analysis, competitive intelligence, strategic planning', 90],

        // Product Development
        ['BRA-PRODUCT', 'Product Architect', 'Product Development Lead', 'Product Dev', 'New product ideation, MVP design, feature prioritization, roadmapping', 88],
        ['BRA-GAMING', 'Gaming Revenue Agent', 'Game Monetization Specialist', 'Product Dev', 'Game design, monetization models, wager systems, tournament revenue', 85],
        ['BRA-CREATIVE', 'Creative Commerce Agent', 'Digital Products Specialist', 'Product Dev', 'AI art, NFTs, digital goods, template marketplace, content creation', 84],
        ['BRA-VOICE', 'Voice Commerce Agent', 'Voice Product Specialist', 'Product Dev', 'Voice apps, IVR products, call center solutions, voice cloning sales', 86],

        // Sales & Growth
        ['BRA-GROWTH', 'Growth Hacker', 'User Acquisition Lead', 'Sales & Growth', 'SEO, content marketing, viral loops, referral programs, conversion optimization', 87],
        ['BRA-ENTERPRISE', 'Enterprise Sales Agent', 'Enterprise Account Executive', 'Sales & Growth', 'Enterprise deals, white-label partnerships, SLA negotiations, upselling', 89],
        ['BRA-PARTNER', 'Partnership Agent', 'Strategic Partnerships', 'Sales & Growth', 'API partnerships, integration deals, co-marketing, channel partners', 85],
        ['BRA-AFFILIATE', 'Affiliate Manager', 'Affiliate Program Director', 'Sales & Growth', 'Affiliate recruitment, commission optimization, performance tracking', 83],

        // Crypto & DeFi
        ['BRA-CRYPTO', 'Crypto Revenue Agent', 'Token Economy Specialist', 'Crypto Ops', 'GSM token strategy, exchange listings, liquidity, DeFi revenue', 88],
        ['BRA-DEFI', 'DeFi Yield Agent', 'Yield Optimization Specialist', 'Crypto Ops', 'Yield farming, staking rewards, LP management, protocol revenue', 84],
        ['BRA-PAYMENTS', 'Payment Gateway Agent', 'Payment Infrastructure', 'Crypto Ops', 'Payment processing, merchant onboarding, transaction fees, compliance', 86],

        // Market Intelligence
        ['BRA-INTEL', 'Market Intelligence Agent', 'Competitive Analysis', 'Intelligence', 'Competitor monitoring, market trends, pricing intelligence, SWOT analysis', 87],
        ['BRA-PRICING', 'Pricing Optimization Agent', 'Dynamic Pricing Specialist', 'Intelligence', 'Price testing, tier optimization, discount strategy, bundle pricing', 85],
        ['BRA-CONTENT', 'Content Revenue Agent', 'Content Monetization', 'Intelligence', 'Course creation, content sales, documentation licensing, tutorial revenue', 82],

        // Operations
        ['BRA-COMPLIANCE', 'Compliance Agent', 'Revenue Compliance Officer', 'Operations', 'Tax compliance, payment regulations, licensing, audit trails', 90],
        ['BRA-RETENTION', 'Retention Agent', 'Customer Retention Specialist', 'Operations', 'Churn prevention, upgrade paths, loyalty programs, win-back campaigns', 86],
        ['BRA-SECURITY', 'Revenue Security Agent', 'Anti-Fraud Specialist', 'Operations', 'Fraud detection, payment security, refund management, risk assessment', 91],

        // Emerging Markets
        ['BRA-METAVERSE', 'Metaverse Commerce Agent', 'Virtual Economy Specialist', 'Emerging', 'VR land sales, avatar commerce, virtual events, metaverse advertising', 83],
        ['BRA-ZPE', 'ZPE Commerce Agent', 'Research Monetization', 'Emerging', 'Patent licensing, research grants, circuit kit sales, educational content', 80],
        ['BRA-MOBILE', 'Mobile Revenue Agent', 'App Monetization Specialist', 'Emerging', 'In-app purchases, mobile ads, app store optimization, mobile-first products', 84],
    ];

    $count = 0;
    foreach ($agents as $a) {
        $stmt = $pdo->prepare("INSERT INTO revenue_agents (agent_id, name, role, division, specialty, trust_score, status) 
            VALUES (?,?,?,?,?,?,'active') ON DUPLICATE KEY UPDATE name=VALUES(name), role=VALUES(role)");
        $stmt->execute([$a[0], $a[1], $a[2], $a[3], $a[4], $a[5]]);
        $count++;
    }

    echo json_encode([
        'success' => true,
        'message' => "$count business revenue agents deployed",
        'divisions' => ['Revenue Command' => 3, 'Product Dev' => 4, 'Sales & Growth' => 4, 'Crypto Ops' => 3, 'Intelligence' => 3, 'Operations' => 3, 'Emerging' => 3],
        'total_agents' => $count
    ]);
    break;

default:
    echo json_encode(['error' => 'Unknown action', 'actions' => ['dashboard','agents','run-audit','audits','opportunities','coordinate','ideate','reports','generate-report','update-agent','seed']]);
}

// ─── Helper Functions ────────────────────────────────────────────

function mapDivision(string $category): string {
    $map = [
        'SaaS' => 'Revenue Command', 'Voice' => 'Product Dev', 'Marketplace' => 'Sales & Growth',
        'Gaming' => 'Product Dev', 'Crypto' => 'Crypto Ops', 'Developer' => 'Intelligence',
        'Infrastructure' => 'Operations', 'Creative' => 'Product Dev', 'Business' => 'Sales & Growth',
        'Emerging' => 'Emerging'
    ];
    return $map[$category] ?? 'Revenue Command';
}

function generateRecommendations(string $cat, string $name, float $current, float $potential): string {
    $recs = [];
    if ($current == 0 && $potential > 5000) {
        $recs[] = "HIGH PRIORITY: {$name} has zero current revenue but \${$potential}/mo potential. Needs immediate go-to-market strategy.";
    }
    if ($cat === 'Gaming') {
        $recs[] = "Add GSM token rewards to increase engagement. Implement tournament system with entry fees. Create mobile-optimized versions.";
    }
    if ($cat === 'Crypto') {
        $recs[] = "Pursue exchange listings for GSM. Implement swap fees (0.1-0.3%). Build merchant adoption pipeline.";
    }
    if ($cat === 'Voice') {
        $recs[] = "Bundle voice products for enterprise. Create industry-specific voice solutions. Implement usage-based pricing for scalability.";
    }
    if ($cat === 'SaaS') {
        $recs[] = "Optimize free→paid conversion funnel. A/B test pricing. Add annual billing discount (20%).";
    }
    if ($cat === 'Developer') {
        $recs[] = "Launch developer community program. Create hackathon events. Publish API usage case studies.";
    }
    if ($cat === 'Emerging') {
        $recs[] = "Early mover advantage — establish market position now. Build partnerships with research institutions. Create educational content funnel.";
    }
    if ($potential > 10000) {
        $recs[] = "Assign dedicated sales agent. Create landing page. Run targeted ads.";
    }
    return implode(' | ', $recs) ?: "Monitor performance. Optimize conversion. Scale on traction.";
}

function generateActionPlan(string $topic): array {
    return [
        ['step' => 1, 'action' => "Research: Gather market data on '{$topic}'", 'timeline' => '1-2 days'],
        ['step' => 2, 'action' => "Analyze: Run competitive analysis and SWOT for '{$topic}'", 'timeline' => '2-3 days'],
        ['step' => 3, 'action' => "Plan: Create go-to-market strategy with pricing", 'timeline' => '3-5 days'],
        ['step' => 4, 'action' => "Build: Develop MVP or prototype", 'timeline' => '1-2 weeks'],
        ['step' => 5, 'action' => "Test: Beta launch with select users", 'timeline' => '1 week'],
        ['step' => 6, 'action' => "Launch: Full deployment with marketing push", 'timeline' => '1-2 weeks'],
        ['step' => 7, 'action' => "Optimize: Monitor metrics and iterate", 'timeline' => 'Ongoing'],
    ];
}
