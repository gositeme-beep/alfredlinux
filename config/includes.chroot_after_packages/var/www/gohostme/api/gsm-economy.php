<?php
/**
 * GSM Unified Economy API
 * ══════════════════════════════════════════════════════════════
 * Single source of truth for ALL GSM token operations across the platform.
 * Bridges mining rewards (search_user_profiles) ↔ crypto ledger (crypto_gsm_balances).
 *
 * Endpoints:
 *   GET  ?action=balance              — Get unified GSM + SOL balance
 *   POST ?action=transfer             — P2P GSM transfer (player-to-player)
 *   POST ?action=spend                — Spend GSM (game entry, VR land, marketplace)
 *   POST ?action=earn                 — Credit GSM (mining sync, rewards, winnings)
 *   GET  ?action=ledger               — Full transaction history
 *   GET  ?action=leaderboard          — Cross-game GSM leaderboard
 *   POST ?action=stake                — Stake GSM for yield
 *   POST ?action=unstake              — Unstake GSM
 *   GET  ?action=staking              — Get staking positions + yield
 *   POST ?action=sync-mining          — Sync mining balance into unified ledger
 *   GET  ?action=economy-stats        — Global economy stats
 *   POST ?action=mint-setup           — Admin: configure token mint + treasury
 *   GET  ?action=mint-status          — Token mint + treasury status
 *
 * Architecture:
 *   crypto_gsm_balances  = canonical balance (unified)
 *   crypto_gsm_ledger    = canonical transaction log
 *   search_user_profiles = mining balance (synced INTO canonical on demand)
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

apiRateLimit(30, 60, 'gsm-economy');

// ── Auth ──────────────────────────────────────────────────────
session_start();
function gsmRequireAuth(): array {
    if (!empty($_SESSION['logged_in']) && !empty($_SESSION['client_id'])) {
        return [
            'client_id' => (int)$_SESSION['client_id'],
            'user_id'   => (int)($_SESSION['user_id'] ?? $_SESSION['client_id']),
            'name'      => $_SESSION['client_name'] ?? '',
            'email'     => $_SESSION['client_email'] ?? '',
        ];
    }
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/^Bearer\s+(.+)$/', $auth, $m)) {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, CONCAT(firstname,' ',lastname) AS name, email FROM clients WHERE api_key = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$m[1]]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($u) return ['client_id' => (int)$u['id'], 'user_id' => (int)$u['id'], 'name' => $u['name'], 'email' => $u['email']];
    }
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

function gsmIsCommander(): bool {
    return ($_SESSION['client_id'] ?? 0) == 33;
}

// ── DB Setup ──────────────────────────────────────────────────
$db = getDB();
if (!$db) { echo json_encode(['error' => 'Database unavailable']); exit; }

// Ensure all tables exist
ensureEconomyTables($db);

function ensureEconomyTables(PDO $db): void {
    // Canonical GSM balance
    $db->exec("CREATE TABLE IF NOT EXISTS crypto_gsm_balances (
        client_id       INT UNSIGNED PRIMARY KEY,
        balance         DECIMAL(20,9) NOT NULL DEFAULT 0,
        total_earned    DECIMAL(20,9) NOT NULL DEFAULT 0,
        total_spent     DECIMAL(20,9) NOT NULL DEFAULT 0,
        staked_amount   DECIMAL(20,9) NOT NULL DEFAULT 0,
        mining_synced   DECIMAL(20,9) NOT NULL DEFAULT 0,
        gaming_earned   DECIMAL(20,9) NOT NULL DEFAULT 0,
        gaming_spent    DECIMAL(20,9) NOT NULL DEFAULT 0,
        updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Add new columns if missing (safe migration)
    try { $db->exec("ALTER TABLE crypto_gsm_balances ADD COLUMN mining_synced DECIMAL(20,9) NOT NULL DEFAULT 0"); } catch(Exception $e) {}
    try { $db->exec("ALTER TABLE crypto_gsm_balances ADD COLUMN gaming_earned DECIMAL(20,9) NOT NULL DEFAULT 0"); } catch(Exception $e) {}
    try { $db->exec("ALTER TABLE crypto_gsm_balances ADD COLUMN gaming_spent DECIMAL(20,9) NOT NULL DEFAULT 0"); } catch(Exception $e) {}

    // Transaction ledger
    $db->exec("CREATE TABLE IF NOT EXISTS crypto_gsm_ledger (
        id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        client_id       INT UNSIGNED NOT NULL,
        tx_type         ENUM('earn','spend','transfer_in','transfer_out','mint','burn','reward','referral','trade','game_win','game_entry','mining_sync','staking_reward','vr_land','governance','prediction','achievement') NOT NULL,
        amount          DECIMAL(20,9) NOT NULL,
        balance_after   DECIMAL(20,9) NOT NULL,
        description     VARCHAR(500) DEFAULT NULL,
        reference_type  VARCHAR(50) DEFAULT NULL,
        reference_id    VARCHAR(128) DEFAULT NULL,
        counterparty_id INT UNSIGNED DEFAULT NULL,
        created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY idx_client (client_id),
        KEY idx_type (tx_type),
        KEY idx_ref (reference_type, reference_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Add counterparty column if missing
    try { $db->exec("ALTER TABLE crypto_gsm_ledger ADD COLUMN counterparty_id INT UNSIGNED DEFAULT NULL"); } catch(Exception $e) {}

    // Staking positions
    $db->exec("CREATE TABLE IF NOT EXISTS gsm_staking (
        id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        client_id       INT UNSIGNED NOT NULL,
        amount          DECIMAL(20,9) NOT NULL,
        tier            ENUM('bronze','silver','gold','platinum') NOT NULL,
        apy             DECIMAL(5,2) NOT NULL,
        start_date      DATETIME DEFAULT CURRENT_TIMESTAMP,
        last_yield_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
        unlock_date     DATETIME DEFAULT NULL,
        status          ENUM('active','withdrawn','expired') DEFAULT 'active',
        total_yield     DECIMAL(20,9) DEFAULT 0,
        KEY idx_client (client_id),
        KEY idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Cross-game achievements
    $db->exec("CREATE TABLE IF NOT EXISTS gsm_achievements (
        id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        client_id       INT UNSIGNED NOT NULL,
        achievement_key VARCHAR(64) NOT NULL,
        game_type       VARCHAR(32) DEFAULT NULL,
        title           VARCHAR(128) NOT NULL,
        description     VARCHAR(500) DEFAULT NULL,
        gsm_reward      DECIMAL(20,9) DEFAULT 0,
        nft_minted      TINYINT(1) DEFAULT 0,
        nft_mint_addr   VARCHAR(64) DEFAULT NULL,
        earned_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_client_achievement (client_id, achievement_key),
        KEY idx_game (game_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Prediction markets
    $db->exec("CREATE TABLE IF NOT EXISTS gsm_predictions (
        id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title           VARCHAR(256) NOT NULL,
        description     TEXT DEFAULT NULL,
        category        VARCHAR(32) DEFAULT 'general',
        outcomes        JSON NOT NULL,
        resolution      VARCHAR(64) DEFAULT NULL,
        total_pool      DECIMAL(20,9) DEFAULT 0,
        status          ENUM('open','locked','resolved','cancelled') DEFAULT 'open',
        created_by      INT UNSIGNED DEFAULT NULL,
        resolves_at     DATETIME DEFAULT NULL,
        resolved_at     DATETIME DEFAULT NULL,
        created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY idx_status (status),
        KEY idx_category (category)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Prediction bets
    $db->exec("CREATE TABLE IF NOT EXISTS gsm_prediction_bets (
        id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        prediction_id   INT UNSIGNED NOT NULL,
        client_id       INT UNSIGNED NOT NULL,
        outcome         VARCHAR(64) NOT NULL,
        amount          DECIMAL(20,9) NOT NULL,
        payout          DECIMAL(20,9) DEFAULT NULL,
        status          ENUM('active','won','lost','refunded') DEFAULT 'active',
        created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY idx_prediction (prediction_id),
        KEY idx_client (client_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Yield gaming boost
    $db->exec("CREATE TABLE IF NOT EXISTS gsm_yield_boosts (
        id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        client_id       INT UNSIGNED NOT NULL,
        boost_type      ENUM('staking_bonus','xp_multiplier','rake_share','exclusive_tournament','mining_boost') NOT NULL,
        multiplier      DECIMAL(5,2) DEFAULT 1.00,
        source          VARCHAR(64) DEFAULT NULL,
        expires_at      DATETIME DEFAULT NULL,
        created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY idx_client (client_id),
        KEY idx_type (boost_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Token mint config (admin)
    $db->exec("CREATE TABLE IF NOT EXISTS gsm_token_config (
        config_key      VARCHAR(64) PRIMARY KEY,
        config_value    VARCHAR(256) NOT NULL,
        updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

// ── Core Balance Functions ────────────────────────────────────

/**
 * Get unified GSM balance for a client. Syncs mining balance if needed.
 */
function getUnifiedBalance(PDO $db, int $clientId): array {
    // Ensure record exists
    $db->prepare("INSERT IGNORE INTO crypto_gsm_balances (client_id, balance) VALUES (?, 0)")->execute([$clientId]);

    // Check if mining balance needs sync
    $miningBalance = getMiningBalance($db, $clientId);
    $stmt = $db->prepare("SELECT mining_synced FROM crypto_gsm_balances WHERE client_id = ?");
    $stmt->execute([$clientId]);
    $synced = (float)$stmt->fetchColumn();

    $unsynced = $miningBalance - $synced;
    if ($unsynced > 0.000000001) {
        syncMiningBalance($db, $clientId, $unsynced);
    }

    // Get full balance
    $stmt = $db->prepare("SELECT * FROM crypto_gsm_balances WHERE client_id = ?");
    $stmt->execute([$clientId]);
    $bal = $stmt->fetch(PDO::FETCH_ASSOC);

    return [
        'gsm_balance'    => (float)$bal['balance'],
        'total_earned'   => (float)$bal['total_earned'],
        'total_spent'    => (float)$bal['total_spent'],
        'staked_amount'  => (float)$bal['staked_amount'],
        'mining_earned'  => (float)$bal['mining_synced'],
        'gaming_earned'  => (float)$bal['gaming_earned'],
        'gaming_spent'   => (float)$bal['gaming_spent'],
        'available'      => (float)$bal['balance'] - (float)$bal['staked_amount'],
    ];
}

/**
 * Get mining balance from search_user_profiles (the old system)
 */
function getMiningBalance(PDO $db, int $clientId): float {
    // Mining system uses user_id which maps to client_id
    $stmt = $db->prepare("SELECT COALESCE(total_gsm_earned, 0) FROM search_user_profiles WHERE user_id = ?");
    $stmt->execute([$clientId]);
    return (float)$stmt->fetchColumn();
}

/**
 * Sync unsynced mining GSM into the canonical balance
 */
function syncMiningBalance(PDO $db, int $clientId, float $amount): bool {
    if ($amount <= 0) return false;
    $db->beginTransaction();
    try {
        $stmt = $db->prepare("SELECT balance FROM crypto_gsm_balances WHERE client_id = ? FOR UPDATE");
        $stmt->execute([$clientId]);
        $current = (float)$stmt->fetchColumn();
        $newBal = $current + $amount;

        $db->prepare("UPDATE crypto_gsm_balances SET balance = ?, total_earned = total_earned + ?, mining_synced = mining_synced + ? WHERE client_id = ?")
            ->execute([$newBal, $amount, $amount, $clientId]);

        $db->prepare("INSERT INTO crypto_gsm_ledger (client_id, tx_type, amount, balance_after, description, reference_type) VALUES (?, 'mining_sync', ?, ?, 'Mining rewards synced', 'mining')")
            ->execute([$clientId, $amount, $newBal]);

        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Mining sync failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Credit GSM to a client (atomic)
 */
function creditGSM(PDO $db, int $clientId, float $amount, string $type, string $desc, ?string $refType = null, ?string $refId = null, ?int $counterparty = null): bool {
    if ($amount <= 0) return false;
    $db->beginTransaction();
    try {
        $db->prepare("INSERT IGNORE INTO crypto_gsm_balances (client_id, balance) VALUES (?, 0)")->execute([$clientId]);
        $stmt = $db->prepare("SELECT balance FROM crypto_gsm_balances WHERE client_id = ? FOR UPDATE");
        $stmt->execute([$clientId]);
        $current = (float)$stmt->fetchColumn();
        $newBal = $current + $amount;

        $earnCol = in_array($type, ['game_win', 'game_entry']) ? 'gaming_earned = gaming_earned + ?' : 'total_earned = total_earned + ?';
        $db->prepare("UPDATE crypto_gsm_balances SET balance = ?, {$earnCol} WHERE client_id = ?")
            ->execute([$newBal, $amount, $clientId]);

        $db->prepare("INSERT INTO crypto_gsm_ledger (client_id, tx_type, amount, balance_after, description, reference_type, reference_id, counterparty_id) VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$clientId, $type, $amount, $newBal, $desc, $refType, $refId, $counterparty]);

        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        error_log("GSM credit failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Debit GSM from a client (atomic, fails if insufficient)
 */
function debitGSM(PDO $db, int $clientId, float $amount, string $type, string $desc, ?string $refType = null, ?string $refId = null, ?int $counterparty = null): bool {
    if ($amount <= 0) return false;
    $db->beginTransaction();
    try {
        $db->prepare("INSERT IGNORE INTO crypto_gsm_balances (client_id, balance) VALUES (?, 0)")->execute([$clientId]);
        $stmt = $db->prepare("SELECT balance, staked_amount FROM crypto_gsm_balances WHERE client_id = ? FOR UPDATE");
        $stmt->execute([$clientId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $current = (float)$row['balance'];
        $staked = (float)$row['staked_amount'];
        $available = $current - $staked;

        if ($available < $amount) {
            $db->rollBack();
            return false;
        }

        $newBal = $current - $amount;
        $spendCol = in_array($type, ['game_entry']) ? 'gaming_spent = gaming_spent + ?' : 'total_spent = total_spent + ?';
        $db->prepare("UPDATE crypto_gsm_balances SET balance = ?, {$spendCol} WHERE client_id = ?")
            ->execute([$newBal, $amount, $clientId]);

        $db->prepare("INSERT INTO crypto_gsm_ledger (client_id, tx_type, amount, balance_after, description, reference_type, reference_id, counterparty_id) VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$clientId, $type, -$amount, $newBal, $desc, $refType, $refId, $counterparty]);

        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        error_log("GSM debit failed: " . $e->getMessage());
        return false;
    }
}

// ── Staking ──────────────────────────────────────────────────

const STAKING_TIERS = [
    'bronze'   => ['min' => 100,    'apy' => 5.0,  'lock_days' => 7],
    'silver'   => ['min' => 1000,   'apy' => 10.0, 'lock_days' => 30],
    'gold'     => ['min' => 10000,  'apy' => 18.0, 'lock_days' => 90],
    'platinum' => ['min' => 100000, 'apy' => 30.0, 'lock_days' => 180],
];

function calculatePendingYield(array $stake): float {
    $elapsed = time() - strtotime($stake['last_yield_at']);
    $yearSeconds = 365.25 * 86400;
    return (float)$stake['amount'] * ((float)$stake['apy'] / 100) * ($elapsed / $yearSeconds);
}

// ── Token Config ──────────────────────────────────────────────

function getTokenConfig(PDO $db): array {
    $stmt = $db->query("SELECT config_key, config_value FROM gsm_token_config");
    $config = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $config[$row['config_key']] = $row['config_value'];
    }
    return $config;
}

function setTokenConfig(PDO $db, string $key, string $value): void {
    $db->prepare("INSERT INTO gsm_token_config (config_key, config_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE config_value = ?, updated_at = NOW()")
        ->execute([$key, $value, $value]);
}

// ═══════════════════════════════════════════════════════════════
//  ACTION ROUTER
// ═══════════════════════════════════════════════════════════════

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

switch ($action) {

    // ═══ GET UNIFIED BALANCE ═══
    case 'balance':
        $user = gsmRequireAuth();
        $bal = getUnifiedBalance($db, $user['client_id']);

        // Also get SOL wallet if connected
        $solWallet = null;
        $stmt = $db->prepare("SELECT wallet_address FROM crypto_wallets WHERE client_id = ? AND is_primary = 1 LIMIT 1");
        $stmt->execute([$user['client_id']]);
        $walletAddr = $stmt->fetchColumn();
        if ($walletAddr) {
            require_once dirname(__DIR__) . '/pay/includes/solana-handler.php';
            $solBal = solanaGetBalance($walletAddr);
            $solWallet = ['address' => $walletAddr, 'balance_sol' => $solBal];
        }

        // Get active staking
        $stmt = $db->prepare("SELECT id, amount, tier, apy, start_date, last_yield_at, unlock_date, total_yield FROM gsm_staking WHERE client_id = ? AND status = 'active'");
        $stmt->execute([$user['client_id']]);
        $stakes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $pendingYield = 0;
        foreach ($stakes as &$s) {
            $s['pending_yield'] = round(calculatePendingYield($s), 6);
            $pendingYield += $s['pending_yield'];
        }

        // Get active game wagers
        $activeWagers = [];
        $stmt = $db->prepare("SELECT id, game_type, currency, amount_gsm, amount_sol, status FROM game_unified_wagers WHERE client_id = ? AND status IN ('pending','active') ORDER BY created_at DESC LIMIT 10");
        try { $stmt->execute([$user['client_id']]); $activeWagers = $stmt->fetchAll(PDO::FETCH_ASSOC); } catch(Exception $e) {}

        // Active boosts
        $stmt = $db->prepare("SELECT boost_type, multiplier, source, expires_at FROM gsm_yield_boosts WHERE client_id = ? AND (expires_at IS NULL OR expires_at > NOW())");
        $stmt->execute([$user['client_id']]);
        $boosts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'gsm'     => $bal,
            'sol'     => $solWallet,
            'staking' => ['positions' => $stakes, 'pending_yield' => round($pendingYield, 6)],
            'active_wagers' => $activeWagers,
            'boosts' => $boosts,
            'token_info' => [
                'symbol'   => 'GSM',
                'name'     => 'GoSiteMe Token',
                'decimals' => 8,
                'supply'   => 1000000000,
                'distribution' => [
                    'treasury_reserve'  => 300000000,
                    'mining_pool'       => 250000000,
                    'community_rewards' => 150000000,
                    'founder_team'      => 150000000,
                    'eden_trust'        =>  50000000,
                    'ecosystem_dev'     =>  50000000,
                    'dex_liquidity'     =>  50000000,
                ],
            ],
        ]);
        break;

    // ═══ P2P TRANSFER ═══
    case 'transfer':
        $user = gsmRequireAuth();
        $toEmail = trim($input['to_email'] ?? '');
        $toClientId = (int)($input['to_client_id'] ?? 0);
        $amount = (float)($input['amount'] ?? 0);
        $memo = substr(trim($input['memo'] ?? ''), 0, 200);

        if ($amount < 0.01) {
            echo json_encode(['error' => 'Minimum transfer: 0.01 GSM']); break;
        }
        if ($amount > 1000000) {
            echo json_encode(['error' => 'Maximum transfer: 1,000,000 GSM']); break;
        }

        // Resolve recipient
        if ($toEmail && !$toClientId) {
            $stmt = $db->prepare("SELECT id FROM clients WHERE email = ? AND status = 'active' LIMIT 1");
            $stmt->execute([$toEmail]);
            $toClientId = (int)$stmt->fetchColumn();
        }
        if (!$toClientId || $toClientId === $user['client_id']) {
            echo json_encode(['error' => 'Invalid recipient']); break;
        }

        // Verify recipient exists
        $stmt = $db->prepare("SELECT CONCAT(firstname,' ',lastname) FROM clients WHERE id = ? AND status = 'active'");
        $stmt->execute([$toClientId]);
        $recipientName = $stmt->fetchColumn();
        if (!$recipientName) {
            echo json_encode(['error' => 'Recipient not found']); break;
        }

        // Debit sender
        $desc = "Transfer to {$recipientName}" . ($memo ? ": {$memo}" : '');
        if (!debitGSM($db, $user['client_id'], $amount, 'transfer_out', $desc, 'p2p_transfer', null, $toClientId)) {
            echo json_encode(['error' => 'Insufficient balance']); break;
        }

        // Credit recipient
        $senderName = $user['name'] ?: 'Player';
        $descIn = "Transfer from {$senderName}" . ($memo ? ": {$memo}" : '');
        creditGSM($db, $toClientId, $amount, 'transfer_in', $descIn, 'p2p_transfer', null, $user['client_id']);

        echo json_encode([
            'success'   => true,
            'amount'    => $amount,
            'to'        => $recipientName,
            'memo'      => $memo,
            'new_balance' => getUnifiedBalance($db, $user['client_id'])['gsm_balance'],
        ]);
        break;

    // ═══ SPEND GSM ═══
    case 'spend':
        $user = gsmRequireAuth();
        $amount = (float)($input['amount'] ?? 0);
        $purpose = $input['purpose'] ?? '';
        $refType = $input['ref_type'] ?? null;
        $refId = $input['ref_id'] ?? null;

        if ($amount <= 0) { echo json_encode(['error' => 'Invalid amount']); break; }

        $validPurposes = ['game_entry', 'vr_land', 'marketplace', 'governance', 'prediction', 'premium'];
        if (!in_array($purpose, $validPurposes)) {
            echo json_encode(['error' => 'Invalid purpose']); break;
        }

        if (!debitGSM($db, $user['client_id'], $amount, $purpose, ucfirst(str_replace('_', ' ', $purpose)) . " purchase", $refType, $refId)) {
            echo json_encode(['error' => 'Insufficient GSM balance']); break;
        }

        echo json_encode([
            'success' => true,
            'spent' => $amount,
            'purpose' => $purpose,
            'new_balance' => getUnifiedBalance($db, $user['client_id'])['gsm_balance'],
        ]);
        break;

    // ═══ EARN GSM ═══
    case 'earn':
        $user = gsmRequireAuth();
        $amount = (float)($input['amount'] ?? 0);
        $source = $input['source'] ?? '';
        $refType = $input['ref_type'] ?? null;
        $refId = $input['ref_id'] ?? null;

        if ($amount <= 0) { echo json_encode(['error' => 'Invalid amount']); break; }

        $validSources = ['game_win', 'achievement', 'staking_reward', 'referral', 'reward', 'earn'];
        $type = in_array($source, $validSources) ? $source : 'earn';

        creditGSM($db, $user['client_id'], $amount, $type, ucfirst(str_replace('_', ' ', $source)) . " reward", $refType, $refId);

        echo json_encode([
            'success' => true,
            'earned' => $amount,
            'source' => $source,
            'new_balance' => getUnifiedBalance($db, $user['client_id'])['gsm_balance'],
        ]);
        break;

    // ═══ TRANSACTION LEDGER ═══
    case 'ledger':
        $user = gsmRequireAuth();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(100, max(10, (int)($_GET['limit'] ?? 50)));
        $typeFilter = $_GET['type'] ?? null;
        $offset = ($page - 1) * $limit;

        $where = "WHERE client_id = ?";
        $params = [$user['client_id']];
        if ($typeFilter) {
            $where .= " AND tx_type = ?";
            $params[] = $typeFilter;
        }

        $stmt = $db->prepare("SELECT tx_type, amount, balance_after, description, reference_type, reference_id, created_at FROM crypto_gsm_ledger {$where} ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $params[] = $limit;
        $params[] = $offset;
        $stmt->execute($params);
        $txns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $countStmt = $db->prepare("SELECT COUNT(*) FROM crypto_gsm_ledger {$where}");
        $countStmt->execute(array_slice($params, 0, -2));
        $total = (int)$countStmt->fetchColumn();

        echo json_encode([
            'success' => true,
            'transactions' => $txns,
            'page' => $page,
            'total' => $total,
            'pages' => ceil($total / $limit),
        ]);
        break;

    // ═══ CROSS-GAME LEADERBOARD ═══
    case 'leaderboard':
        $category = $_GET['category'] ?? 'total'; // total, gaming, mining, staking
        $limit = min(100, max(10, (int)($_GET['limit'] ?? 50)));

        $orderCol = match($category) {
            'gaming'  => 'gaming_earned',
            'mining'  => 'mining_synced',
            'staking' => 'staked_amount',
            default   => 'total_earned',
        };

        $stmt = $db->prepare("SELECT b.client_id, CONCAT(c.firstname,' ',c.lastname) AS name,
            b.balance, b.total_earned, b.mining_synced, b.gaming_earned, b.staked_amount
            FROM crypto_gsm_balances b
            LEFT JOIN clients c ON c.id = b.client_id
            WHERE b.{$orderCol} > 0
            ORDER BY b.{$orderCol} DESC LIMIT ?");
        $stmt->execute([$limit]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $board = [];
        foreach ($rows as $i => $r) {
            $name = $r['name'] ?: 'Anon';
            $board[] = [
                'rank'          => $i + 1,
                'name'          => mb_substr($name, 0, 1) . str_repeat('*', max(0, mb_strlen($name) - 2)) . mb_substr($name, -1),
                'total_earned'  => (float)$r['total_earned'],
                'gaming_earned' => (float)$r['gaming_earned'],
                'mining_earned' => (float)$r['mining_synced'],
                'staked'        => (float)$r['staked_amount'],
                'balance'       => (float)$r['balance'],
            ];
        }

        echo json_encode(['success' => true, 'category' => $category, 'leaderboard' => $board]);
        break;

    // ═══ STAKE GSM ═══
    case 'stake':
        $user = gsmRequireAuth();
        $amount = (float)($input['amount'] ?? 0);
        $tier = $input['tier'] ?? '';

        if (!isset(STAKING_TIERS[$tier])) {
            echo json_encode(['error' => 'Invalid tier', 'tiers' => STAKING_TIERS]); break;
        }
        $tierConfig = STAKING_TIERS[$tier];
        if ($amount < $tierConfig['min']) {
            echo json_encode(['error' => "Minimum for {$tier}: {$tierConfig['min']} GSM"]); break;
        }

        // Check available balance
        $bal = getUnifiedBalance($db, $user['client_id']);
        if ($bal['available'] < $amount) {
            echo json_encode(['error' => 'Insufficient available GSM']); break;
        }

        $unlockDate = date('Y-m-d H:i:s', strtotime("+{$tierConfig['lock_days']} days"));

        $db->beginTransaction();
        try {
            // Lock the GSM
            $db->prepare("UPDATE crypto_gsm_balances SET staked_amount = staked_amount + ? WHERE client_id = ?")
                ->execute([$amount, $user['client_id']]);

            // Create staking position
            $db->prepare("INSERT INTO gsm_staking (client_id, amount, tier, apy, unlock_date) VALUES (?,?,?,?,?)")
                ->execute([$user['client_id'], $amount, $tier, $tierConfig['apy'], $unlockDate]);
            $stakeId = $db->lastInsertId();

            // Ledger entry
            $stmt = $db->prepare("SELECT balance FROM crypto_gsm_balances WHERE client_id = ?");
            $stmt->execute([$user['client_id']]);
            $newBal = (float)$stmt->fetchColumn();

            $db->prepare("INSERT INTO crypto_gsm_ledger (client_id, tx_type, amount, balance_after, description, reference_type, reference_id) VALUES (?,?,?,?,?,?,?)")
                ->execute([$user['client_id'], 'spend', -$amount, $newBal, "Staked {$amount} GSM ({$tier} tier, {$tierConfig['apy']}% APY)", 'staking', $stakeId]);

            // Grant yield boost for gaming
            if ($tier === 'gold' || $tier === 'platinum') {
                $boostMultiplier = $tier === 'platinum' ? 1.50 : 1.25;
                $db->prepare("INSERT INTO gsm_yield_boosts (client_id, boost_type, multiplier, source, expires_at) VALUES (?,?,?,?,?)")
                    ->execute([$user['client_id'], 'xp_multiplier', $boostMultiplier, "staking_{$tier}", $unlockDate]);
            }

            $db->commit();

            echo json_encode([
                'success' => true,
                'stake_id' => (int)$stakeId,
                'amount' => $amount,
                'tier' => $tier,
                'apy' => $tierConfig['apy'],
                'unlock_date' => $unlockDate,
                'boost' => ($tier === 'gold' || $tier === 'platinum') ? "{$tier} staker gaming boost activated" : null,
            ]);
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['error' => 'Staking failed']);
        }
        break;

    // ═══ UNSTAKE GSM ═══
    case 'unstake':
        $user = gsmRequireAuth();
        $stakeId = (int)($input['stake_id'] ?? 0);

        $stmt = $db->prepare("SELECT * FROM gsm_staking WHERE id = ? AND client_id = ? AND status = 'active'");
        $stmt->execute([$stakeId, $user['client_id']]);
        $stake = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$stake) { echo json_encode(['error' => 'Stake not found']); break; }

        // Check lock period
        if ($stake['unlock_date'] && strtotime($stake['unlock_date']) > time()) {
            $daysLeft = ceil((strtotime($stake['unlock_date']) - time()) / 86400);
            echo json_encode(['error' => "Stake locked for {$daysLeft} more days", 'unlock_date' => $stake['unlock_date']]); break;
        }

        // Calculate final yield
        $pendingYield = calculatePendingYield($stake);
        $totalReturn = (float)$stake['amount'] + $pendingYield;

        $db->beginTransaction();
        try {
            // Return staked amount + yield
            $db->prepare("UPDATE crypto_gsm_balances SET staked_amount = GREATEST(0, staked_amount - ?), balance = balance + ? WHERE client_id = ?")
                ->execute([(float)$stake['amount'], $pendingYield, $user['client_id']]);

            // Mark stake as withdrawn
            $db->prepare("UPDATE gsm_staking SET status = 'withdrawn', total_yield = total_yield + ? WHERE id = ?")
                ->execute([$pendingYield, $stakeId]);

            // Credit yield
            if ($pendingYield > 0) {
                $stmt = $db->prepare("SELECT balance FROM crypto_gsm_balances WHERE client_id = ?");
                $stmt->execute([$user['client_id']]);
                $newBal = (float)$stmt->fetchColumn();

                $db->prepare("INSERT INTO crypto_gsm_ledger (client_id, tx_type, amount, balance_after, description, reference_type, reference_id) VALUES (?,?,?,?,?,?,?)")
                    ->execute([$user['client_id'], 'staking_reward', $pendingYield, $newBal, "Staking yield ({$stake['tier']} tier)", 'staking', $stakeId]);
            }

            $db->commit();

            echo json_encode([
                'success' => true,
                'returned' => (float)$stake['amount'],
                'yield' => round($pendingYield, 6),
                'total_return' => round($totalReturn, 6),
            ]);
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['error' => 'Unstake failed']);
        }
        break;

    // ═══ STAKING POSITIONS ═══
    case 'staking':
        $user = gsmRequireAuth();
        $stmt = $db->prepare("SELECT * FROM gsm_staking WHERE client_id = ? ORDER BY status ASC, start_date DESC");
        $stmt->execute([$user['client_id']]);
        $stakes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($stakes as &$s) {
            $s['pending_yield'] = $s['status'] === 'active' ? round(calculatePendingYield($s), 6) : 0;
            $s['locked'] = $s['unlock_date'] && strtotime($s['unlock_date']) > time();
        }

        echo json_encode(['success' => true, 'stakes' => $stakes, 'tiers' => STAKING_TIERS]);
        break;

    // ═══ SYNC MINING BALANCE ═══
    case 'sync-mining':
        $user = gsmRequireAuth();
        $miningBal = getMiningBalance($db, $user['client_id']);
        $stmt = $db->prepare("SELECT mining_synced FROM crypto_gsm_balances WHERE client_id = ?");
        $stmt->execute([$user['client_id']]);
        $synced = (float)($stmt->fetchColumn() ?: 0);
        $unsynced = $miningBal - $synced;

        if ($unsynced > 0.000000001) {
            syncMiningBalance($db, $user['client_id'], $unsynced);
            echo json_encode(['success' => true, 'synced' => $unsynced, 'total_mining' => $miningBal]);
        } else {
            echo json_encode(['success' => true, 'synced' => 0, 'message' => 'Already in sync']);
        }
        break;

    // ═══ ECONOMY STATS ═══
    case 'economy-stats':
        $totalGSM = (float)$db->query("SELECT COALESCE(SUM(balance), 0) FROM crypto_gsm_balances")->fetchColumn();
        $totalStaked = (float)$db->query("SELECT COALESCE(SUM(staked_amount), 0) FROM crypto_gsm_balances")->fetchColumn();
        $totalEarned = (float)$db->query("SELECT COALESCE(SUM(total_earned), 0) FROM crypto_gsm_balances")->fetchColumn();
        $holders = (int)$db->query("SELECT COUNT(*) FROM crypto_gsm_balances WHERE balance > 0")->fetchColumn();
        $stakingPositions = (int)$db->query("SELECT COUNT(*) FROM gsm_staking WHERE status = 'active'")->fetchColumn();
        $txns24h = (int)$db->query("SELECT COUNT(*) FROM crypto_gsm_ledger WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();
        $gamingVolume = (float)$db->query("SELECT COALESCE(SUM(gaming_earned), 0) FROM crypto_gsm_balances")->fetchColumn();

        // Mining pool stats
        $miningDistributed = (float)$db->query("SELECT COALESCE(SUM(total_gsm_earned), 0) FROM search_user_profiles")->fetchColumn();
        $activeMiners = (int)$db->query("SELECT COUNT(*) FROM search_user_profiles WHERE mining_enabled = 1")->fetchColumn();

        // Game wager stats
        $totalWagered = 0;
        try { $totalWagered = (float)$db->query("SELECT COALESCE(SUM(amount), 0) FROM game_wagers")->fetchColumn(); } catch(Exception $e) {}

        $tokenConfig = getTokenConfig($db);

        echo json_encode([
            'success' => true,
            'economy' => [
                'total_circulating'   => $totalGSM,
                'total_staked'        => $totalStaked,
                'total_earned'        => $totalEarned,
                'holders'             => $holders,
                'staking_positions'   => $stakingPositions,
                'txns_24h'            => $txns24h,
                'gaming_volume'       => $gamingVolume,
                'mining_distributed'  => $miningDistributed,
                'mining_pool_total'   => 250000000,
                'mining_remaining'    => 250000000 - $miningDistributed,
                'active_miners'       => $activeMiners,
                'total_wagered_cents' => $totalWagered,
            ],
            'token' => [
                'mint_address'   => $tokenConfig['token_mint'] ?? '',
                'treasury_wallet'=> $tokenConfig['treasury_wallet'] ?? '',
                'fee_wallet'     => $tokenConfig['fee_wallet'] ?? '',
                'minted'         => !empty($tokenConfig['token_mint']),
            ],
        ]);
        break;

    // ═══ ADMIN: MINT SETUP ═══
    case 'mint-setup':
        if (!gsmIsCommander()) { echo json_encode(['error' => 'Commander access required']); break; }
        $user = gsmRequireAuth();

        $mintAddr = trim($input['token_mint'] ?? '');
        $treasury = trim($input['treasury_wallet'] ?? '');
        $feeWallet = trim($input['fee_wallet'] ?? '');

        // Validate Solana addresses
        $addrRegex = '/^[1-9A-HJ-NP-Za-km-z]{32,44}$/';
        if ($mintAddr && !preg_match($addrRegex, $mintAddr)) { echo json_encode(['error' => 'Invalid mint address']); break; }
        if ($treasury && !preg_match($addrRegex, $treasury)) { echo json_encode(['error' => 'Invalid treasury wallet']); break; }
        if ($feeWallet && !preg_match($addrRegex, $feeWallet)) { echo json_encode(['error' => 'Invalid fee wallet']); break; }

        if ($mintAddr) setTokenConfig($db, 'token_mint', $mintAddr);
        if ($treasury) setTokenConfig($db, 'treasury_wallet', $treasury);
        if ($feeWallet) setTokenConfig($db, 'fee_wallet', $feeWallet);

        echo json_encode([
            'success' => true,
            'config' => getTokenConfig($db),
            'message' => 'Token configuration updated. On-chain mint requires Solana CLI.',
        ]);
        break;

    // ═══ MINT STATUS ═══
    case 'mint-status':
        $config = getTokenConfig($db);
        echo json_encode([
            'success' => true,
            'token_mint'     => $config['token_mint'] ?? '',
            'treasury_wallet'=> $config['treasury_wallet'] ?? '',
            'fee_wallet'     => $config['fee_wallet'] ?? '',
            'minted'         => !empty($config['token_mint']),
            'steps' => [
                ['step' => 'Create SPL token', 'status' => !empty($config['token_mint']) ? 'done' : 'pending', 'command' => 'spl-token create-token --decimals 9'],
                ['step' => 'Mint supply', 'status' => 'pending', 'command' => 'spl-token mint <TOKEN_MINT> 1000000000'],
                ['step' => 'Set treasury wallet', 'status' => !empty($config['treasury_wallet']) ? 'done' : 'pending'],
                ['step' => 'Set fee wallet', 'status' => !empty($config['fee_wallet']) ? 'done' : 'pending'],
                ['step' => 'Add to Jupiter', 'status' => 'pending', 'note' => 'Submit to jup.ag/tokens after minting'],
            ],
        ]);
        break;

    // ═══ PREDICTION MARKETS ═══
    case 'predictions':
        $status = $_GET['status'] ?? 'open';
        $stmt = $db->prepare("SELECT * FROM gsm_predictions WHERE status = ? ORDER BY created_at DESC LIMIT 50");
        $stmt->execute([$status]);
        $predictions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($predictions as &$p) {
            $p['outcomes'] = json_decode($p['outcomes'], true);
            // Get bet distribution
            $stmt2 = $db->prepare("SELECT outcome, COUNT(*) as bets, SUM(amount) as total FROM gsm_prediction_bets WHERE prediction_id = ? AND status = 'active' GROUP BY outcome");
            $stmt2->execute([$p['id']]);
            $p['bet_distribution'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        }
        echo json_encode(['success' => true, 'predictions' => $predictions]);
        break;

    case 'predict':
        $user = gsmRequireAuth();
        $predictionId = (int)($input['prediction_id'] ?? 0);
        $outcome = trim($input['outcome'] ?? '');
        $amount = (float)($input['amount'] ?? 0);

        if ($amount < 1) { echo json_encode(['error' => 'Minimum bet: 1 GSM']); break; }

        // Validate prediction exists and is open
        $stmt = $db->prepare("SELECT * FROM gsm_predictions WHERE id = ? AND status = 'open'");
        $stmt->execute([$predictionId]);
        $pred = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$pred) { echo json_encode(['error' => 'Prediction not found or closed']); break; }

        $outcomes = json_decode($pred['outcomes'], true);
        if (!in_array($outcome, $outcomes)) { echo json_encode(['error' => 'Invalid outcome']); break; }

        // Debit GSM
        if (!debitGSM($db, $user['client_id'], $amount, 'prediction', "Prediction bet: {$pred['title']} → {$outcome}", 'prediction', $predictionId)) {
            echo json_encode(['error' => 'Insufficient GSM']); break;
        }

        // Place bet
        $db->prepare("INSERT INTO gsm_prediction_bets (prediction_id, client_id, outcome, amount) VALUES (?,?,?,?)")
            ->execute([$predictionId, $user['client_id'], $outcome, $amount]);

        $db->prepare("UPDATE gsm_predictions SET total_pool = total_pool + ? WHERE id = ?")->execute([$amount, $predictionId]);

        echo json_encode(['success' => true, 'bet_amount' => $amount, 'outcome' => $outcome]);
        break;

    case 'create-prediction':
        if (!gsmIsCommander()) { echo json_encode(['error' => 'Commander access required']); break; }
        $user = gsmRequireAuth();

        $title = substr(trim($input['title'] ?? ''), 0, 256);
        $description = substr(trim($input['description'] ?? ''), 0, 2000);
        $category = $input['category'] ?? 'general';
        $outcomes = $input['outcomes'] ?? [];
        $resolvesAt = $input['resolves_at'] ?? null;

        if (!$title || count($outcomes) < 2) {
            echo json_encode(['error' => 'Title and at least 2 outcomes required']); break;
        }

        $db->prepare("INSERT INTO gsm_predictions (title, description, category, outcomes, created_by, resolves_at) VALUES (?,?,?,?,?,?)")
            ->execute([$title, $description, $category, json_encode($outcomes), $user['client_id'], $resolvesAt]);

        echo json_encode(['success' => true, 'prediction_id' => (int)$db->lastInsertId()]);
        break;

    case 'resolve-prediction':
        if (!gsmIsCommander()) { echo json_encode(['error' => 'Commander access required']); break; }
        $user = gsmRequireAuth();

        $predictionId = (int)($input['prediction_id'] ?? 0);
        $resolution = trim($input['resolution'] ?? '');

        $stmt = $db->prepare("SELECT * FROM gsm_predictions WHERE id = ? AND status IN ('open','locked')");
        $stmt->execute([$predictionId]);
        $pred = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$pred) { echo json_encode(['error' => 'Prediction not found']); break; }

        $outcomes = json_decode($pred['outcomes'], true);
        if (!in_array($resolution, $outcomes)) { echo json_encode(['error' => 'Invalid resolution']); break; }

        $db->beginTransaction();
        try {
            // Get total pool and winning pool
            $totalPool = (float)$pred['total_pool'];
            $stmt = $db->prepare("SELECT SUM(amount) FROM gsm_prediction_bets WHERE prediction_id = ? AND outcome = ? AND status = 'active'");
            $stmt->execute([$predictionId, $resolution]);
            $winnerPool = (float)$stmt->fetchColumn();

            // Payout winners proportionally
            if ($winnerPool > 0) {
                $stmt = $db->prepare("SELECT id, client_id, amount FROM gsm_prediction_bets WHERE prediction_id = ? AND outcome = ? AND status = 'active'");
                $stmt->execute([$predictionId, $resolution]);
                $winners = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($winners as $w) {
                    $share = ((float)$w['amount'] / $winnerPool) * $totalPool * 0.95; // 5% house
                    $db->prepare("UPDATE gsm_prediction_bets SET status = 'won', payout = ? WHERE id = ?")
                        ->execute([$share, $w['id']]);
                    creditGSM($db, (int)$w['client_id'], $share, 'prediction', "Prediction win: {$pred['title']}", 'prediction', $predictionId);
                }
            }

            // Mark losers
            $db->prepare("UPDATE gsm_prediction_bets SET status = 'lost' WHERE prediction_id = ? AND outcome != ? AND status = 'active'")
                ->execute([$predictionId, $resolution]);

            // Resolve prediction
            $db->prepare("UPDATE gsm_predictions SET status = 'resolved', resolution = ?, resolved_at = NOW() WHERE id = ?")
                ->execute([$resolution, $predictionId]);

            $db->commit();
            echo json_encode(['success' => true, 'resolution' => $resolution, 'winners_paid' => count($winners ?? [])]);
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['error' => 'Resolution failed']);
        }
        break;

    // ═══ ACHIEVEMENTS ═══
    case 'achievements':
        $user = gsmRequireAuth();
        $stmt = $db->prepare("SELECT * FROM gsm_achievements WHERE client_id = ? ORDER BY earned_at DESC");
        $stmt->execute([$user['client_id']]);
        $achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'achievements' => $achievements]);
        break;

    case 'check-achievements':
        $user = gsmRequireAuth();
        $newAchievements = checkAndAwardAchievements($db, $user['client_id']);
        echo json_encode(['success' => true, 'new_achievements' => $newAchievements]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action', 'actions' => [
            'balance', 'transfer', 'spend', 'earn', 'ledger', 'leaderboard',
            'stake', 'unstake', 'staking', 'sync-mining', 'economy-stats',
            'mint-setup', 'mint-status',
            'predictions', 'predict', 'create-prediction', 'resolve-prediction',
            'achievements', 'check-achievements',
        ]]);
}

// ═══════════════════════════════════════════════════════════════
//  ACHIEVEMENT SYSTEM
// ═══════════════════════════════════════════════════════════════

function checkAndAwardAchievements(PDO $db, int $clientId): array {
    $awarded = [];
    $bal = getUnifiedBalance($db, $clientId);

    // Get game stats
    $gameStats = ['total_wins' => 0, 'total_games' => 0, 'games' => []];
    try {
        $stmt = $db->prepare("SELECT * FROM game_player_stats WHERE session_id = ?");
        $stmt->execute([session_id()]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($stats) {
            $gameStats['total_wins'] = (int)($stats['wins'] ?? 0);
            $gameStats['total_games'] = (int)($stats['games_played'] ?? 0);
        }
    } catch(Exception $e) {}

    $achievements = [
        ['key' => 'first_gsm', 'title' => 'First Token', 'desc' => 'Earned your first GSM token', 'reward' => 1.0, 'check' => $bal['total_earned'] > 0],
        ['key' => 'miner_100', 'title' => 'Prospector', 'desc' => 'Earned 100 GSM from mining', 'reward' => 10.0, 'check' => $bal['mining_earned'] >= 100],
        ['key' => 'miner_10k', 'title' => 'Gold Rush', 'desc' => 'Earned 10,000 GSM from mining', 'reward' => 100.0, 'check' => $bal['mining_earned'] >= 10000],
        ['key' => 'gamer_first_win', 'title' => 'Victor', 'desc' => 'Won your first game', 'reward' => 5.0, 'check' => $gameStats['total_wins'] >= 1],
        ['key' => 'gamer_10_wins', 'title' => 'Champion', 'desc' => 'Won 10 games', 'reward' => 25.0, 'check' => $gameStats['total_wins'] >= 10],
        ['key' => 'gamer_100_wins', 'title' => 'Legend', 'desc' => 'Won 100 games across all games', 'reward' => 250.0, 'check' => $gameStats['total_wins'] >= 100],
        ['key' => 'staker_first', 'title' => 'Stakeholder', 'desc' => 'Made your first GSM stake', 'reward' => 5.0, 'check' => $bal['staked_amount'] > 0],
        ['key' => 'hodler_1k', 'title' => 'Diamond Hands', 'desc' => 'Hold 1,000+ GSM balance', 'reward' => 50.0, 'check' => $bal['gsm_balance'] >= 1000],
        ['key' => 'hodler_100k', 'title' => 'Whale', 'desc' => 'Hold 100,000+ GSM balance', 'reward' => 1000.0, 'check' => $bal['gsm_balance'] >= 100000],
        ['key' => 'earner_1m', 'title' => 'Millionaire', 'desc' => 'Total lifetime earnings exceed 1,000,000 GSM', 'reward' => 5000.0, 'check' => $bal['total_earned'] >= 1000000],
    ];

    foreach ($achievements as $a) {
        if (!$a['check']) continue;

        // Check if already awarded
        $stmt = $db->prepare("SELECT id FROM gsm_achievements WHERE client_id = ? AND achievement_key = ?");
        $stmt->execute([$clientId, $a['key']]);
        if ($stmt->fetch()) continue;

        // Award achievement
        $db->prepare("INSERT INTO gsm_achievements (client_id, achievement_key, game_type, title, description, gsm_reward) VALUES (?,?,?,?,?,?)")
            ->execute([$clientId, $a['key'], null, $a['title'], $a['desc'], $a['reward']]);

        // Credit GSM reward
        if ($a['reward'] > 0) {
            creditGSM($db, $clientId, $a['reward'], 'achievement', "Achievement: {$a['title']}", 'achievement', $a['key']);
        }

        $awarded[] = ['key' => $a['key'], 'title' => $a['title'], 'reward' => $a['reward']];
    }

    return $awarded;
}
