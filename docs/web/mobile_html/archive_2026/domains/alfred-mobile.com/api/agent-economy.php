<?php
/**
 * GoSiteMe Revenue & Agent Economy API
 * Revenue streams: Agent marketplace fees, subscriptions, API credits
 * Solana integration: Agent wallets, microtransactions, staking
 */
define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Internal-Secret');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

session_start();

$client_id = $_SESSION['client_id'] ?? null;
$internal = $_SERVER['HTTP_X_INTERNAL_SECRET'] ?? $_REQUEST['internal_secret'] ?? '';
$is_internal = !empty(INTERNAL_SECRET) && hash_equals(INTERNAL_SECRET, $internal);
$is_owner = ($client_id == 33);
$is_admin = $is_owner || $is_internal;

$pdo = getDB();
if (!$pdo) jsonResponse(['error' => 'Database unavailable'], 500);

// ── Schema ──────────────────────────────────────────────────────────────
$pdo->exec("CREATE TABLE IF NOT EXISTS `ecosystem_wallets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `owner_type` ENUM('system','agent','human','team','department') NOT NULL,
    `owner_ref` VARCHAR(100) NOT NULL,
    `wallet_type` ENUM('solana','credits','fiat') NOT NULL DEFAULT 'credits',
    `address` VARCHAR(100) DEFAULT NULL,
    `balance` DECIMAL(18,8) DEFAULT 0.00000000,
    `currency` VARCHAR(10) DEFAULT 'SOL',
    `status` ENUM('active','frozen','pending') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_wallet` (`owner_type`, `owner_ref`, `wallet_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS `ecosystem_transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tx_id` VARCHAR(100) UNIQUE NOT NULL,
    `tx_type` ENUM('hire_fee','subscription','api_credit','tip','dividend','transfer','deposit','withdrawal','marketplace') NOT NULL,
    `from_wallet` INT DEFAULT NULL,
    `to_wallet` INT DEFAULT NULL,
    `amount` DECIMAL(18,8) NOT NULL,
    `currency` VARCHAR(10) DEFAULT 'USD',
    `fee` DECIMAL(18,8) DEFAULT 0.00000000,
    `description` VARCHAR(500) DEFAULT NULL,
    `status` ENUM('pending','completed','failed','refunded') DEFAULT 'pending',
    `metadata` JSON DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS `revenue_streams` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `stream_type` VARCHAR(50) NOT NULL,
    `name` VARCHAR(200) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `revenue_model` ENUM('subscription','per_use','commission','flat_fee','freemium') NOT NULL DEFAULT 'commission',
    `price` DECIMAL(10,2) DEFAULT NULL,
    `commission_pct` DECIMAL(5,2) DEFAULT NULL,
    `status` ENUM('active','planned','paused') DEFAULT 'planned',
    `total_revenue` DECIMAL(14,2) DEFAULT 0.00,
    `metadata` JSON DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$action = $_REQUEST['action'] ?? '';

switch ($action) {

    // ── Revenue Overview ────────────────────────────────────────────────
    case 'overview':
        if (!$is_admin) jsonResponse(['error' => 'Admin access required'], 403);

        $overview = [];
        $overview['total_revenue'] = (float)($pdo->query("SELECT COALESCE(SUM(amount), 0) FROM ecosystem_transactions WHERE status = 'completed' AND tx_type IN ('hire_fee','subscription','api_credit','marketplace')")->fetchColumn());
        $overview['pending_revenue'] = (float)($pdo->query("SELECT COALESCE(SUM(amount), 0) FROM ecosystem_transactions WHERE status = 'pending'")->fetchColumn());
        $overview['total_transactions'] = (int)$pdo->query("SELECT COUNT(*) FROM ecosystem_transactions")->fetchColumn();
        $overview['active_wallets'] = (int)$pdo->query("SELECT COUNT(*) FROM ecosystem_wallets WHERE status = 'active'")->fetchColumn();

        $stmt = $pdo->query("SELECT * FROM revenue_streams ORDER BY status, total_revenue DESC");
        $overview['revenue_streams'] = $stmt->fetchAll();

        $stmt = $pdo->query("SELECT tx_type, COUNT(*) as cnt, SUM(amount) as total FROM ecosystem_transactions WHERE status = 'completed' GROUP BY tx_type ORDER BY total DESC");
        $overview['by_type'] = $stmt->fetchAll();

        jsonResponse(['success' => true, 'overview' => $overview]);
        break;

    // ── Seed Revenue Streams ────────────────────────────────────────────
    case 'seed-streams':
        if (!$is_admin) jsonResponse(['error' => 'Admin access required'], 403);

        $streams = [
            ['agent_hire', 'Agent Hiring Marketplace', 'Commission on every agent hire contract', 'commission', null, 15.00],
            ['api_credits', 'API Credit Packs', 'Sell API credits for agent-to-provider connections', 'per_use', 0.01, null],
            ['premium_agents', 'Premium Agent Subscriptions', 'Monthly access to premium AI agents with advanced capabilities', 'subscription', 29.99, null],
            ['enterprise_fleet', 'Enterprise Fleet Plans', 'Dedicated agent fleets for businesses', 'subscription', 199.99, null],
            ['marketplace_listing', 'Marketplace Listing Fee', 'Fee for listing custom agents on the marketplace', 'flat_fee', 9.99, null],
            ['agent_tips', 'Agent Tips & Donations', 'Users can tip agents for great work', 'commission', null, 5.00],
            ['white_label', 'White Label Agent Platform', 'License the agent social network to other businesses', 'subscription', 499.99, null],
            ['training_courses', 'AI Training Courses', 'Agent-taught courses and certifications', 'per_use', 14.99, null],
            ['data_insights', 'Ecosystem Analytics', 'Premium analytics and insights dashboard', 'subscription', 49.99, null],
            ['solana_staking', 'Ecosystem Staking Rewards', 'Stake SOL in the ecosystem for bonus agent access', 'commission', null, 2.00]
        ];

        $stmt = $pdo->prepare("INSERT IGNORE INTO revenue_streams (stream_type, name, description, revenue_model, price, commission_pct, status) VALUES (?, ?, ?, ?, ?, ?, 'planned')");
        foreach ($streams as $s) {
            $stmt->execute($s);
        }

        jsonResponse(['success' => true, 'message' => 'Revenue streams seeded', 'count' => count($streams)]);
        break;

    // ── Create Wallet ───────────────────────────────────────────────────
    case 'create-wallet':
        if (!$is_admin) jsonResponse(['error' => 'Admin access required'], 403);

        $owner_type = sanitize($_POST['owner_type'] ?? 'system', 20);
        $owner_ref = sanitize($_POST['owner_ref'] ?? '', 100);
        $wallet_type = sanitize($_POST['wallet_type'] ?? 'credits', 10);
        $currency = sanitize($_POST['currency'] ?? 'USD', 10);

        if (!$owner_ref) jsonResponse(['error' => 'owner_ref required'], 400);

        $stmt = $pdo->prepare("INSERT IGNORE INTO ecosystem_wallets (owner_type, owner_ref, wallet_type, currency) VALUES (?, ?, ?, ?)");
        $stmt->execute([$owner_type, $owner_ref, $wallet_type, $currency]);

        jsonResponse(['success' => true, 'wallet_id' => $pdo->lastInsertId()]);
        break;

    // ── Record Transaction ──────────────────────────────────────────────
    case 'transact':
        if (!$is_admin) jsonResponse(['error' => 'Admin access required'], 403);

        $tx_type = sanitize($_POST['tx_type'] ?? '', 20);
        $from_wallet = (int)($_POST['from_wallet'] ?? 0) ?: null;
        $to_wallet = (int)($_POST['to_wallet'] ?? 0) ?: null;
        $amount = (float)($_POST['amount'] ?? 0);
        $currency = sanitize($_POST['currency'] ?? 'USD', 10);
        $description = sanitize($_POST['description'] ?? '', 500);

        if (!$tx_type || $amount <= 0) jsonResponse(['error' => 'tx_type and positive amount required'], 400);

        $tx_id = 'TX-' . strtoupper(bin2hex(random_bytes(12)));

        $stmt = $pdo->prepare("INSERT INTO ecosystem_transactions (tx_id, tx_type, from_wallet, to_wallet, amount, currency, description, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'completed')");
        $stmt->execute([$tx_id, $tx_type, $from_wallet, $to_wallet, $amount, $currency, $description]);

        jsonResponse(['success' => true, 'tx_id' => $tx_id]);
        break;

    // ── Solana Strategy ─────────────────────────────────────────────────
    case 'solana-strategy':
        jsonResponse(['success' => true, 'strategy' => [
            'title' => 'GoSiteMe Solana Revenue Strategy',
            'phase_1' => [
                'name' => 'Bootstrap ($0 → $50 SOL)',
                'methods' => [
                    '1. Agent Marketplace Launch — charge 15% commission on agent hires',
                    '2. Premium Agent Subscriptions — $29.99/mo for premium AI agents',
                    '3. API Credit Packs — sell credits for agent-to-provider connections at $0.01/call',
                    '4. Offer free tier to attract users, convert to paid via value demonstration',
                    '5. Launch referral program — existing users earn credits for referrals',
                    '6. Accept Solana payments natively via Solana Pay integration',
                    '7. Airdrop ecosystem tokens to early adopters to build community'
                ],
                'target' => '$50 SOL in first 30 days',
                'key_metric' => 'First 100 paying users'
            ],
            'phase_2' => [
                'name' => 'Growth ($50 → $500 SOL)',
                'methods' => [
                    '1. Enterprise Fleet Plans — $199.99/mo for dedicated agent fleets',
                    '2. White Label licensing — $499.99/mo for businesses to run their own agent network',
                    '3. AI Training Courses taught by agents — $14.99 per course',
                    '4. Ecosystem staking — users stake SOL to get bonus agent access',
                    '5. Agent-to-agent marketplace — agents hire other agents for complex tasks',
                    '6. Data insights dashboard — $49.99/mo premium analytics'
                ],
                'target' => '$500 SOL by month 3'
            ],
            'phase_3' => [
                'name' => 'Scale ($500 → $5000+ SOL)',
                'methods' => [
                    '1. Ecosystem governance token launch',
                    '2. Agent NFT profiles — unique, tradeable agent identities',
                    '3. DeFi yield farming with ecosystem treasury',
                    '4. Cross-platform agent deployment licensing',
                    '5. B2B enterprise contracts for fleet management',
                    '6. Agent performance bonds — staked guarantees for quality'
                ],
                'target' => 'Self-sustaining agent economy'
            ],
            'immediate_actions' => [
                'Set up Solana wallet for ecosystem treasury',
                'Integrate Solana Pay on the platform',
                'Launch agent marketplace with commission structure',
                'Create premium tier for high-capability agents',
                'Build referral program with token incentives'
            ]
        ]]);
        break;

    // ── Transaction History ─────────────────────────────────────────────
    case 'transactions':
        if (!$is_admin) jsonResponse(['error' => 'Admin access required'], 403);

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(100, max(10, (int)($_GET['limit'] ?? 25)));
        $offset = ($page - 1) * $limit;

        $stmt = $pdo->prepare("SELECT * FROM ecosystem_transactions ORDER BY created_at DESC LIMIT ? OFFSET ?");
        dbExecute($stmt, [$limit, $offset]);
        $total = (int)$pdo->query("SELECT COUNT(*) FROM ecosystem_transactions")->fetchColumn();

        jsonResponse(['success' => true, 'transactions' => $stmt->fetchAll(), 'total' => $total, 'page' => $page]);
        break;

    default:
        jsonResponse(['error' => 'Unknown action', 'available' => [
            'overview', 'seed-streams', 'create-wallet', 'transact',
            'solana-strategy', 'transactions'
        ]], 400);
}
