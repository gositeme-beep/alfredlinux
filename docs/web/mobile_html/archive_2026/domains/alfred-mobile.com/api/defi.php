<?php
/**
 * Alfred Multi-Chain DeFi API — Phase 4: Financial Sovereignty
 * ─────────────────────────────────────────────────────────────
 * Autonomous portfolio management across Solana, EVM chains.
 * Yield tracking, low-risk investment, portfolio analytics.
 *
 * Endpoints:
 *   GET  ?action=portfolio       → Full portfolio summary
 *   GET  ?action=wallets         → Connected wallets
 *   POST ?action=add-wallet      → Register a wallet
 *   GET  ?action=positions       → Active yield positions
 *   POST ?action=enter-position  → Enter a yield position
 *   POST ?action=exit-position   → Exit a yield position
 *   GET  ?action=yields          → Available yield opportunities
 *   GET  ?action=transactions    → Transaction history
 *   GET  ?action=alerts          → Price / yield alerts
 *   POST ?action=set-alert       → Create / update alert
 *   GET  ?action=chains          → Supported chains
 *   GET  ?action=analytics       → Portfolio analytics
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Internal-Secret, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

requireCSRF();
apiRateLimit(20, 60, 'defi');

function requireAuth() {
    if (empty($_SESSION['logged_in']) || empty($_SESSION['client_id'])) jsonResponse(['error' => 'Authentication required'], 401);
}
function isAdmin() { return !empty($_SESSION['is_admin']) || ($_SESSION['client_id'] ?? 0) === 33; }
function isInternalCall() {
    $s = getenv('INTERNAL_SECRET') ?: '';
    return $s && isset($_SERVER['HTTP_X_INTERNAL_SECRET']) && hash_equals($s, $_SERVER['HTTP_X_INTERNAL_SECRET']);
}

// ─── Safety checks (MASTERPLAN rule: no fund transfers > $100 without approval) ────
define('MAX_AUTO_INVEST_USD', 100);

function ensureDefiSchema() {
    $db = getDB();
    if (!$db) return false;

    $db->exec("CREATE TABLE IF NOT EXISTS defi_wallets (
        id             INT AUTO_INCREMENT PRIMARY KEY,
        client_id      INT NOT NULL,
        chain          VARCHAR(30) NOT NULL,
        address        VARCHAR(100) NOT NULL,
        label          VARCHAR(100) DEFAULT NULL,
        is_primary     TINYINT(1) DEFAULT 0,
        created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_wallet (client_id, chain, address),
        INDEX idx_client (client_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS defi_positions (
        id             INT AUTO_INCREMENT PRIMARY KEY,
        client_id      INT NOT NULL,
        wallet_id      INT NOT NULL,
        protocol       VARCHAR(50) NOT NULL,
        chain          VARCHAR(30) NOT NULL,
        pool           VARCHAR(100) NOT NULL,
        position_type  ENUM('lending','staking','lp','vault','farming') NOT NULL,
        invested_usd   DECIMAL(18,2) NOT NULL,
        current_usd    DECIMAL(18,2) DEFAULT 0,
        apy            DECIMAL(8,4) DEFAULT 0,
        token_a        VARCHAR(20) DEFAULT NULL,
        token_b        VARCHAR(20) DEFAULT NULL,
        risk_level     ENUM('low','medium','high') DEFAULT 'low',
        status         ENUM('active','exited','liquidated') DEFAULT 'active',
        entered_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        exited_at      TIMESTAMP NULL DEFAULT NULL,
        pnl_usd        DECIMAL(18,2) DEFAULT 0,
        INDEX idx_client (client_id),
        INDEX idx_status (status),
        INDEX idx_chain (chain)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS defi_transactions (
        id             INT AUTO_INCREMENT PRIMARY KEY,
        client_id      INT NOT NULL,
        wallet_id      INT DEFAULT NULL,
        chain          VARCHAR(30) NOT NULL,
        tx_hash        VARCHAR(120) DEFAULT NULL,
        tx_type        ENUM('deposit','withdraw','swap','claim','transfer','approve') NOT NULL,
        protocol       VARCHAR(50) DEFAULT NULL,
        amount_usd     DECIMAL(18,2) NOT NULL,
        token          VARCHAR(20) DEFAULT NULL,
        token_amount   DECIMAL(24,8) DEFAULT 0,
        status         ENUM('pending','confirmed','failed') DEFAULT 'pending',
        created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_client (client_id),
        INDEX idx_chain (chain),
        INDEX idx_date (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS defi_alerts (
        id             INT AUTO_INCREMENT PRIMARY KEY,
        client_id      INT NOT NULL,
        alert_type     ENUM('price_above','price_below','apy_below','apy_above','pnl_target','loss_limit') NOT NULL,
        token          VARCHAR(20) DEFAULT NULL,
        position_id    INT DEFAULT NULL,
        threshold      DECIMAL(18,4) NOT NULL,
        is_active      TINYINT(1) DEFAULT 1,
        triggered_at   TIMESTAMP NULL DEFAULT NULL,
        created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_client (client_id),
        INDEX idx_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS defi_yield_opportunities (
        id             INT AUTO_INCREMENT PRIMARY KEY,
        protocol       VARCHAR(50) NOT NULL,
        chain          VARCHAR(30) NOT NULL,
        pool           VARCHAR(100) NOT NULL,
        position_type  ENUM('lending','staking','lp','vault','farming') NOT NULL,
        apy            DECIMAL(8,4) NOT NULL,
        tvl_usd        DECIMAL(18,2) DEFAULT 0,
        risk_level     ENUM('low','medium','high') NOT NULL,
        token_a        VARCHAR(20) NOT NULL,
        token_b        VARCHAR(20) DEFAULT NULL,
        min_deposit_usd DECIMAL(10,2) DEFAULT 0,
        is_active      TINYINT(1) DEFAULT 1,
        updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_pool (protocol, chain, pool),
        INDEX idx_risk (risk_level),
        INDEX idx_apy (apy)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Seed yield opportunities
    $count = $db->query("SELECT COUNT(*) FROM defi_yield_opportunities")->fetchColumn();
    if ($count == 0) {
        $opportunities = [
            ['Marinade', 'solana', 'mSOL Staking', 'staking', 7.20, 1500000, 'low', 'SOL', null, 0.1],
            ['Jito', 'solana', 'jitoSOL Staking', 'staking', 7.80, 2000000, 'low', 'SOL', null, 0.1],
            ['Raydium', 'solana', 'SOL-USDC LP', 'lp', 12.50, 5000000, 'medium', 'SOL', 'USDC', 10],
            ['Orca', 'solana', 'SOL-USDT Whirlpool', 'lp', 15.30, 3000000, 'medium', 'SOL', 'USDT', 10],
            ['Aave', 'ethereum', 'USDC Lending', 'lending', 4.20, 8000000, 'low', 'USDC', null, 100],
            ['Aave', 'ethereum', 'ETH Lending', 'lending', 2.80, 12000000, 'low', 'ETH', null, 50],
            ['Lido', 'ethereum', 'stETH Staking', 'staking', 3.90, 15000000, 'low', 'ETH', null, 10],
            ['Compound', 'ethereum', 'USDT Lending', 'lending', 3.50, 4000000, 'low', 'USDT', null, 100],
            ['Aave', 'polygon', 'USDC Lending', 'lending', 5.10, 2000000, 'low', 'USDC', null, 10],
            ['Beefy', 'polygon', 'MATIC-USDC Vault', 'vault', 8.50, 1000000, 'medium', 'MATIC', 'USDC', 5],
            ['Aave', 'arbitrum', 'USDC Lending', 'lending', 4.80, 3000000, 'low', 'USDC', null, 10],
            ['GMX', 'arbitrum', 'GLP Vault', 'vault', 18.00, 5000000, 'medium', 'GLP', null, 50],
            ['PancakeSwap', 'bsc', 'CAKE Staking', 'staking', 9.50, 4000000, 'low', 'CAKE', null, 1],
            ['Venus', 'bsc', 'USDT Lending', 'lending', 5.80, 3000000, 'low', 'USDT', null, 10],
        ];
        $stmt = $db->prepare("INSERT INTO defi_yield_opportunities (protocol, chain, pool, position_type, apy, tvl_usd, risk_level, token_a, token_b, min_deposit_usd) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($opportunities as $o) $stmt->execute($o);
    }

    return true;
}

$CHAINS = [
    'solana'   => ['name' => 'Solana', 'native' => 'SOL', 'explorer' => 'https://solscan.io'],
    'ethereum' => ['name' => 'Ethereum', 'native' => 'ETH', 'explorer' => 'https://etherscan.io'],
    'polygon'  => ['name' => 'Polygon', 'native' => 'MATIC', 'explorer' => 'https://polygonscan.com'],
    'arbitrum' => ['name' => 'Arbitrum', 'native' => 'ETH', 'explorer' => 'https://arbiscan.io'],
    'bsc'      => ['name' => 'BNB Chain', 'native' => 'BNB', 'explorer' => 'https://bscscan.com'],
    'base'     => ['name' => 'Base', 'native' => 'ETH', 'explorer' => 'https://basescan.org'],
];

$action = sanitize($_GET['action'] ?? '', 30);
$db = getDB();
if (!$db) jsonResponse(['error' => 'Database unavailable'], 503);
ensureDefiSchema();

switch ($action) {

    case 'portfolio':
        requireAuth();
        $clientId = $_SESSION['client_id'];

        $wallets = $db->prepare("SELECT * FROM defi_wallets WHERE client_id = ?");
        $wallets->execute([$clientId]);
        $walletList = $wallets->fetchAll();

        $positions = $db->prepare("SELECT * FROM defi_positions WHERE client_id = ? AND status = 'active'");
        $positions->execute([$clientId]);
        $positionList = $positions->fetchAll();

        $totalInvested = 0;
        $totalCurrent = 0;
        $byChain = [];

        foreach ($positionList as $pos) {
            $totalInvested += $pos['invested_usd'];
            $totalCurrent += $pos['current_usd'];
            $chain = $pos['chain'];
            if (!isset($byChain[$chain])) $byChain[$chain] = ['invested' => 0, 'current' => 0, 'positions' => 0];
            $byChain[$chain]['invested'] += $pos['invested_usd'];
            $byChain[$chain]['current'] += $pos['current_usd'];
            $byChain[$chain]['positions']++;
        }

        jsonResponse([
            'success' => true,
            'portfolio' => [
                'total_invested_usd' => round($totalInvested, 2),
                'total_current_usd' => round($totalCurrent, 2),
                'total_pnl_usd' => round($totalCurrent - $totalInvested, 2),
                'pnl_percent' => $totalInvested > 0 ? round((($totalCurrent - $totalInvested) / $totalInvested) * 100, 2) : 0,
                'active_positions' => count($positionList),
                'wallets_count' => count($walletList),
                'by_chain' => $byChain,
            ],
            'wallets' => $walletList,
            'positions' => $positionList,
        ]);
        break;

    case 'wallets':
        requireAuth();
        $stmt = $db->prepare("SELECT * FROM defi_wallets WHERE client_id = ?");
        $stmt->execute([$_SESSION['client_id']]);
        jsonResponse(['success' => true, 'wallets' => $stmt->fetchAll()]);
        break;

    case 'add-wallet':
        requireAuth();
        $input = json_decode(file_get_contents('php://input'), true);
        $chain = sanitize($input['chain'] ?? '', 30);
        $address = sanitize($input['address'] ?? '', 100);
        $label = sanitize($input['label'] ?? '', 100);

        global $CHAINS;
        if (!isset($CHAINS[$chain])) jsonResponse(['error' => 'Unsupported chain', 'supported' => array_keys($CHAINS)], 400);
        if (strlen($address) < 10) jsonResponse(['error' => 'Invalid wallet address'], 400);

        try {
            $db->prepare("INSERT INTO defi_wallets (client_id, chain, address, label) VALUES (?, ?, ?, ?)")->execute([
                $_SESSION['client_id'], $chain, $address, $label ?: null
            ]);
            jsonResponse(['success' => true]);
        } catch (PDOException $e) {
            jsonResponse(['error' => 'Wallet already registered'], 400);
        }
        break;

    case 'positions':
        requireAuth();
        $status = ($_GET['status'] ?? 'active');
        if (!in_array($status, ['active','exited','liquidated','all'])) $status = 'active';

        $where = "client_id = ?";
        $params = [$_SESSION['client_id']];
        if ($status !== 'all') { $where .= " AND status = ?"; $params[] = $status; }

        $stmt = $db->prepare("SELECT * FROM defi_positions WHERE $where ORDER BY entered_at DESC");
        dbExecute($stmt, $params);
        jsonResponse(['success' => true, 'positions' => $stmt->fetchAll()]);
        break;

    case 'enter-position':
        requireAuth();
        $input = json_decode(file_get_contents('php://input'), true);

        $opportunityId = intval($input['opportunity_id'] ?? 0);
        $walletId = intval($input['wallet_id'] ?? 0);
        $amountUsd = floatval($input['amount_usd'] ?? 0);

        if (!$opportunityId || !$walletId || $amountUsd <= 0) jsonResponse(['error' => 'opportunity_id, wallet_id, and positive amount_usd required'], 400);

        // Safety: MASTERPLAN rule
        if ($amountUsd > MAX_AUTO_INVEST_USD) {
            jsonResponse(['error' => 'Amount exceeds autonomous limit ($' . MAX_AUTO_INVEST_USD . '). Manual approval required.', 'requires_approval' => true], 403);
        }

        // Verify opportunity
        $opp = $db->prepare("SELECT * FROM defi_yield_opportunities WHERE id = ? AND is_active = 1");
        $opp->execute([$opportunityId]);
        $opportunity = $opp->fetch();
        if (!$opportunity) jsonResponse(['error' => 'Opportunity not found or inactive'], 404);

        if ($amountUsd < $opportunity['min_deposit_usd']) {
            jsonResponse(['error' => 'Below minimum deposit of $' . $opportunity['min_deposit_usd']], 400);
        }

        // Verify wallet belongs to user and matches chain
        $wallet = $db->prepare("SELECT * FROM defi_wallets WHERE id = ? AND client_id = ? AND chain = ?");
        $wallet->execute([$walletId, $_SESSION['client_id'], $opportunity['chain']]);
        if (!$wallet->fetch()) jsonResponse(['error' => 'Wallet not found or chain mismatch'], 400);

        $db->prepare("INSERT INTO defi_positions (client_id, wallet_id, protocol, chain, pool, position_type, invested_usd, current_usd, apy, token_a, token_b, risk_level) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")->execute([
            $_SESSION['client_id'], $walletId, $opportunity['protocol'], $opportunity['chain'], $opportunity['pool'],
            $opportunity['position_type'], $amountUsd, $amountUsd, $opportunity['apy'],
            $opportunity['token_a'], $opportunity['token_b'], $opportunity['risk_level']
        ]);

        $positionId = $db->lastInsertId();

        $db->prepare("INSERT INTO defi_transactions (client_id, wallet_id, chain, tx_type, protocol, amount_usd, token, status) VALUES (?, ?, ?, 'deposit', ?, ?, ?, 'confirmed')")->execute([
            $_SESSION['client_id'], $walletId, $opportunity['chain'], $opportunity['protocol'], $amountUsd, $opportunity['token_a']
        ]);

        jsonResponse(['success' => true, 'position_id' => $positionId, 'opportunity' => $opportunity]);
        break;

    case 'exit-position':
        requireAuth();
        $input = json_decode(file_get_contents('php://input'), true);
        $positionId = intval($input['position_id'] ?? 0);
        if (!$positionId) jsonResponse(['error' => 'position_id required'], 400);

        $pos = $db->prepare("SELECT * FROM defi_positions WHERE id = ? AND client_id = ? AND status = 'active'");
        $pos->execute([$positionId, $_SESSION['client_id']]);
        $position = $pos->fetch();
        if (!$position) jsonResponse(['error' => 'Active position not found'], 404);

        $pnl = $position['current_usd'] - $position['invested_usd'];

        $db->prepare("UPDATE defi_positions SET status = 'exited', exited_at = NOW(), pnl_usd = ? WHERE id = ?")->execute([$pnl, $positionId]);
        $db->prepare("INSERT INTO defi_transactions (client_id, wallet_id, chain, tx_type, protocol, amount_usd, token, status) VALUES (?, ?, ?, 'withdraw', ?, ?, ?, 'confirmed')")->execute([
            $_SESSION['client_id'], $position['wallet_id'], $position['chain'], $position['protocol'], $position['current_usd'], $position['token_a']
        ]);

        jsonResponse(['success' => true, 'pnl_usd' => round($pnl, 2), 'withdrawn_usd' => round($position['current_usd'], 2)]);
        break;

    case 'yields':
        if (!isInternalCall()) requireAuth();

        $chain = sanitize($_GET['chain'] ?? '', 30);
        $risk = sanitize($_GET['risk'] ?? '', 10);
        $type = sanitize($_GET['type'] ?? '', 20);
        $minApy = floatval($_GET['min_apy'] ?? 0);

        $where = "is_active = 1";
        $params = [];

        if ($chain) { $where .= " AND chain = ?"; $params[] = $chain; }
        if ($risk && in_array($risk, ['low','medium','high'])) { $where .= " AND risk_level = ?"; $params[] = $risk; }
        if ($type) { $where .= " AND position_type = ?"; $params[] = $type; }
        if ($minApy > 0) { $where .= " AND apy >= ?"; $params[] = $minApy; }

        $stmt = $db->prepare("SELECT * FROM defi_yield_opportunities WHERE $where ORDER BY apy DESC");
        dbExecute($stmt, $params);
        jsonResponse(['success' => true, 'yields' => $stmt->fetchAll()]);
        break;

    case 'transactions':
        requireAuth();
        $limit = min(max(intval($_GET['limit'] ?? 25), 1), 100);
        $chain = sanitize($_GET['chain'] ?? '', 30);

        $where = "client_id = ?";
        $params = [$_SESSION['client_id']];
        if ($chain) { $where .= " AND chain = ?"; $params[] = $chain; }

        $params[] = $limit;
        $stmt = $db->prepare("SELECT * FROM defi_transactions WHERE $where ORDER BY created_at DESC LIMIT ?");
        dbExecute($stmt, $params);
        jsonResponse(['success' => true, 'transactions' => $stmt->fetchAll()]);
        break;

    case 'alerts':
        requireAuth();
        $stmt = $db->prepare("SELECT * FROM defi_alerts WHERE client_id = ? ORDER BY created_at DESC");
        $stmt->execute([$_SESSION['client_id']]);
        jsonResponse(['success' => true, 'alerts' => $stmt->fetchAll()]);
        break;

    case 'set-alert':
        requireAuth();
        $input = json_decode(file_get_contents('php://input'), true);
        $alertType = sanitize($input['type'] ?? '', 20);
        $threshold = floatval($input['threshold'] ?? 0);

        $validTypes = ['price_above','price_below','apy_below','apy_above','pnl_target','loss_limit'];
        if (!in_array($alertType, $validTypes)) jsonResponse(['error' => 'Invalid alert type', 'valid_types' => $validTypes], 400);
        if ($threshold <= 0) jsonResponse(['error' => 'Positive threshold required'], 400);

        $db->prepare("INSERT INTO defi_alerts (client_id, alert_type, token, position_id, threshold) VALUES (?, ?, ?, ?, ?)")->execute([
            $_SESSION['client_id'], $alertType,
            sanitize($input['token'] ?? '', 20) ?: null,
            intval($input['position_id'] ?? 0) ?: null,
            $threshold
        ]);

        jsonResponse(['success' => true, 'alert_id' => $db->lastInsertId()]);
        break;

    case 'chains':
        global $CHAINS;
        jsonResponse(['success' => true, 'chains' => $CHAINS]);
        break;

    case 'analytics':
        requireAuth();
        $clientId = $_SESSION['client_id'];

        // Total PnL
        $pnl = $db->prepare("SELECT SUM(pnl_usd) as total_pnl, COUNT(*) as total_closed FROM defi_positions WHERE client_id = ? AND status = 'exited'");
        $pnl->execute([$clientId]);
        $pnlData = $pnl->fetch();

        // Active totals
        $active = $db->prepare("SELECT SUM(invested_usd) as invested, SUM(current_usd) as current_val, COUNT(*) as active_count, AVG(apy) as avg_apy FROM defi_positions WHERE client_id = ? AND status = 'active'");
        $active->execute([$clientId]);
        $activeData = $active->fetch();

        // By risk
        $byRisk = $db->prepare("SELECT risk_level, SUM(invested_usd) as invested, COUNT(*) as positions FROM defi_positions WHERE client_id = ? AND status = 'active' GROUP BY risk_level");
        $byRisk->execute([$clientId]);

        // By protocol
        $byProtocol = $db->prepare("SELECT protocol, SUM(invested_usd) as invested, COUNT(*) as positions, AVG(apy) as avg_apy FROM defi_positions WHERE client_id = ? AND status = 'active' GROUP BY protocol");
        $byProtocol->execute([$clientId]);

        jsonResponse([
            'success' => true,
            'analytics' => [
                'total_realized_pnl' => round(floatval($pnlData['total_pnl'] ?? 0), 2),
                'closed_positions' => (int)($pnlData['total_closed'] ?? 0),
                'active_invested' => round(floatval($activeData['invested'] ?? 0), 2),
                'active_value' => round(floatval($activeData['current_val'] ?? 0), 2),
                'unrealized_pnl' => round(floatval(($activeData['current_val'] ?? 0) - ($activeData['invested'] ?? 0)), 2),
                'active_positions' => (int)($activeData['active_count'] ?? 0),
                'avg_apy' => round(floatval($activeData['avg_apy'] ?? 0), 2),
                'by_risk' => $byRisk->fetchAll(),
                'by_protocol' => $byProtocol->fetchAll(),
            ],
        ]);
        break;

    default:
        jsonResponse(['error' => 'Unknown action', 'available_actions' => ['portfolio','wallets','add-wallet','positions','enter-position','exit-position','yields','transactions','alerts','set-alert','chains','analytics']], 400);
}
