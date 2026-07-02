<?php
/**
 * Universal Game Betting API
 * ══════════════════════════════════════════════════════════════
 * Single unified API for ALL game wagering across the platform.
 * Replaces per-game betting silos with one multi-currency engine.
 *
 * Currencies: GSM (via gsm-economy.php), SOL (lamports), USD (Stripe cents)
 * Games: chess, checkers, pool, backgammon, poker
 * Modes: ai (vs agent), pvp (player-vs-player)
 *
 * Endpoints:
 *   POST ?action=place-wager          — Create a new wager
 *   POST ?action=confirm-wager        — Confirm payment (SOL tx / Stripe PI)
 *   POST ?action=settle-wager         — Settle after game ends
 *   POST ?action=cancel-wager         — Cancel pending/active wager
 *   GET  ?action=active-wager         — Get current active wager
 *   GET  ?action=wager-history        — Get wager history
 *   GET  ?action=game-stats           — Per-game stats + streaks
 *   GET  ?action=leaderboard          — Cross-game betting leaderboard
 *   GET  ?action=odds                 — Current odds / payout tables
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

apiRateLimit(30, 60, 'universal-betting');

session_start();

// ── Auth ──────────────────────────────────────────────────────
function bettingRequireAuth(): array {
    if (!empty($_SESSION['logged_in']) && !empty($_SESSION['client_id'])) {
        return [
            'client_id' => (int)$_SESSION['client_id'],
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
        if ($u) return ['client_id' => (int)$u['id'], 'name' => $u['name'], 'email' => $u['email']];
    }
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

function isCommander(): bool {
    return ($_SESSION['client_id'] ?? 0) == 33;
}

// ── Constants ─────────────────────────────────────────────────

const SUPPORTED_GAMES = ['chess', 'checkers', 'pool', 'backgammon', 'poker', 'racing', 'command_conquer'];
const SUPPORTED_CURRENCIES = ['gsm', 'sol', 'usd'];
const GAME_MODES = ['ai', 'pvp'];

// Wager limits per currency
const WAGER_LIMITS = [
    'gsm' => ['min' => 1, 'max' => 10000, 'presets' => [1, 5, 10, 50, 100, 500]],
    'usd' => ['min' => 100, 'max' => 2500, 'presets' => [100, 300, 500, 1000, 2500]], // cents
    'sol' => ['min' => 10000000, 'max' => 5000000000, 'presets' => [10000000, 50000000, 100000000, 500000000, 1000000000]], // lamports
];

// Payout multipliers
const PAYOUT_AI_WIN    = 2.0;  // 2x on AI win
const PAYOUT_PVP_WIN   = 1.9;  // 2x minus 5% rake on PvP win
const PAYOUT_DRAW      = 1.0;  // refund on draw
const PAYOUT_LOSS      = 0.0;
const HOUSE_RAKE       = 0.05; // 5% on PvP
const SETTLEMENT_COOLDOWN = 30; // seconds between settlements

// Anti-cheat: minimum moves per game type
const MIN_MOVES = [
    'chess'           => ['win' => 6,  'draw' => 8,  'loss' => 0],
    'checkers'        => ['win' => 6,  'draw' => 4,  'loss' => 0],
    'pool'            => ['win' => 4,  'draw' => 0,  'loss' => 0],
    'backgammon'      => ['win' => 8,  'draw' => 6,  'loss' => 0],
    'poker'           => ['win' => 1,  'draw' => 1,  'loss' => 0],
    'racing'          => ['win' => 1,  'draw' => 0,  'loss' => 0],
    'command_conquer' => ['win' => 1,  'draw' => 0,  'loss' => 0],
];

// Anti-cheat: minimum game duration in seconds
const MIN_DURATION = [
    'chess'           => ['ai' => 20, 'pvp' => 30],
    'checkers'        => ['ai' => 15, 'pvp' => 20],
    'pool'            => ['ai' => 10, 'pvp' => 15],
    'backgammon'      => ['ai' => 20, 'pvp' => 25],
    'poker'           => ['ai' => 10, 'pvp' => 15],
    'racing'          => ['ai' => 15, 'pvp' => 20],
    'command_conquer' => ['ai' => 30, 'pvp' => 45],
];

// Agent roster (canonical)
const GAME_AGENTS = [
    'alfred'    => ['name' => 'Alfred',    'elo' => 1200, 'specialty' => 'all-rounder'],
    'nova'      => ['name' => 'Nova',      'elo' => 1100, 'specialty' => 'aggressive'],
    'sage'      => ['name' => 'Sage',      'elo' => 1300, 'specialty' => 'positional'],
    'atlas'     => ['name' => 'Atlas',     'elo' => 1400, 'specialty' => 'endgame'],
    'cipher'    => ['name' => 'Cipher',    'elo' => 1500, 'specialty' => 'tactical'],
    'architect' => ['name' => 'Architect', 'elo' => 1250, 'specialty' => 'strategic'],
    'pulse'     => ['name' => 'Pulse',     'elo' => 1150, 'specialty' => 'speed'],
    'pierre'    => ['name' => 'Pierre',    'elo' => 1350, 'specialty' => 'classical'],
];

// ── DB Setup ──────────────────────────────────────────────────

$db = getDB();
if (!$db) { echo json_encode(['error' => 'Database unavailable']); exit; }

ensureBettingTables($db);

function ensureBettingTables(PDO $db): void {
    // Unified wagers table
    $db->exec("CREATE TABLE IF NOT EXISTS game_unified_wagers (
        id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        client_id       INT UNSIGNED NOT NULL,
        match_id        VARCHAR(64) NOT NULL,
        game_type       ENUM('chess','checkers','pool','backgammon','poker') NOT NULL,
        game_mode       ENUM('ai','pvp') DEFAULT 'ai',
        opponent_id     INT UNSIGNED DEFAULT NULL COMMENT 'PvP opponent client_id',
        opponent_agent  VARCHAR(32) DEFAULT NULL COMMENT 'AI agent name',
        currency        ENUM('gsm','sol','usd') NOT NULL,
        amount_gsm      DECIMAL(20,9) DEFAULT 0,
        amount_sol      BIGINT DEFAULT 0 COMMENT 'lamports',
        amount_usd      INT DEFAULT 0 COMMENT 'cents',
        payout_gsm      DECIMAL(20,9) DEFAULT 0,
        payout_sol      BIGINT DEFAULT 0,
        payout_usd      INT DEFAULT 0,
        status          ENUM('pending','active','won','lost','draw','cancelled','expired','disputed') DEFAULT 'pending',
        stripe_pi       VARCHAR(128) DEFAULT NULL,
        solana_tx       VARCHAR(128) DEFAULT NULL,
        move_count      INT DEFAULT 0,
        game_duration   INT DEFAULT 0 COMMENT 'seconds',
        game_evidence   JSON DEFAULT NULL,
        game_pgn        TEXT DEFAULT NULL,
        created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
        settled_at      DATETIME DEFAULT NULL,
        KEY idx_client (client_id),
        KEY idx_match (match_id),
        KEY idx_game (game_type),
        KEY idx_status (status),
        KEY idx_opponent (opponent_id),
        KEY idx_currency (currency)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Per-client per-game stats
    $db->exec("CREATE TABLE IF NOT EXISTS game_unified_stats (
        id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        client_id       INT UNSIGNED NOT NULL,
        game_type       VARCHAR(32) NOT NULL,
        currency        VARCHAR(8) NOT NULL DEFAULT 'gsm',
        games_played    INT DEFAULT 0,
        wins            INT DEFAULT 0,
        losses          INT DEFAULT 0,
        draws           INT DEFAULT 0,
        total_wagered   DECIMAL(20,9) DEFAULT 0,
        total_won       DECIMAL(20,9) DEFAULT 0,
        total_lost      DECIMAL(20,9) DEFAULT 0,
        win_streak      INT DEFAULT 0,
        best_streak     INT DEFAULT 0,
        last_played_at  DATETIME DEFAULT NULL,
        UNIQUE KEY uk_client_game_cur (client_id, game_type, currency),
        KEY idx_client (client_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // PvP matchmaking queue
    $db->exec("CREATE TABLE IF NOT EXISTS game_pvp_queue (
        id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        client_id       INT UNSIGNED NOT NULL,
        wager_id        INT UNSIGNED NOT NULL,
        game_type       VARCHAR(32) NOT NULL,
        currency        VARCHAR(8) NOT NULL,
        amount          DECIMAL(20,9) NOT NULL,
        status          ENUM('waiting','matched','expired','cancelled') DEFAULT 'waiting',
        matched_with    INT UNSIGNED DEFAULT NULL,
        created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
        expires_at      DATETIME DEFAULT NULL,
        KEY idx_game_status (game_type, status),
        KEY idx_client (client_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Tournament system
    $db->exec("CREATE TABLE IF NOT EXISTS game_tournaments (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        game_type VARCHAR(32) NOT NULL,
        title VARCHAR(200) NOT NULL,
        description TEXT,
        entry_fee_gsm DECIMAL(20,9) DEFAULT 0,
        entry_fee_usd INT DEFAULT 0,
        prize_pool_gsm DECIMAL(20,9) DEFAULT 0,
        prize_splits JSON DEFAULT '[0.6, 0.3, 0.1]',
        format ENUM('single_elim','double_elim','round_robin','swiss') DEFAULT 'single_elim',
        max_players INT DEFAULT 8,
        current_players INT DEFAULT 0,
        status ENUM('upcoming','registration','active','completed','cancelled') DEFAULT 'upcoming',
        starts_at DATETIME NOT NULL,
        ends_at DATETIME DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY idx_game_status (game_type, status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS game_tournament_entries (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        tournament_id INT UNSIGNED NOT NULL,
        client_id INT UNSIGNED NOT NULL,
        seed INT DEFAULT 0,
        placement INT DEFAULT NULL,
        prize_won DECIMAL(20,9) DEFAULT 0,
        status ENUM('registered','active','eliminated','winner') DEFAULT 'registered',
        joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_tourn_client (tournament_id, client_id),
        KEY idx_tournament (tournament_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Agent side bets
    $db->exec("CREATE TABLE IF NOT EXISTS game_agent_bets (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        wager_id INT UNSIGNED NOT NULL,
        client_id INT UNSIGNED NOT NULL,
        agent_id VARCHAR(32) NOT NULL,
        bet_side ENUM('player_wins','agent_wins','draw') NOT NULL,
        amount_gsm DECIMAL(20,9) DEFAULT 0,
        odds DECIMAL(5,2) DEFAULT 1.00,
        payout_gsm DECIMAL(20,9) DEFAULT 0,
        status ENUM('active','won','lost','cancelled') DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY idx_wager (wager_id),
        KEY idx_client (client_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Achievements table
    $db->exec("CREATE TABLE IF NOT EXISTS gsm_achievements (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        client_id INT UNSIGNED NOT NULL,
        achievement_key VARCHAR(64) NOT NULL,
        game_type VARCHAR(32) DEFAULT NULL,
        title VARCHAR(120) NOT NULL,
        description VARCHAR(300) DEFAULT '',
        gsm_reward DECIMAL(20,9) DEFAULT 0,
        earned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_client_achievement (client_id, achievement_key),
        KEY idx_client (client_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Schema upgrades — expand ENUM to VARCHAR, add missing columns
    try { $db->exec("ALTER TABLE game_unified_wagers MODIFY COLUMN game_type VARCHAR(32) NOT NULL"); } catch (Exception $e) {}
    try { $db->exec("ALTER TABLE game_unified_wagers ADD COLUMN side VARCHAR(16) DEFAULT NULL AFTER opponent_agent"); } catch (Exception $e) {}
    try { $db->exec("ALTER TABLE game_unified_wagers ADD COLUMN tournament_id INT UNSIGNED DEFAULT NULL AFTER game_pgn"); } catch (Exception $e) {}
    try { $db->exec("ALTER TABLE game_unified_wagers ADD COLUMN rake_amount DECIMAL(20,9) DEFAULT 0 AFTER tournament_id"); } catch (Exception $e) {}
    try { $db->exec("ALTER TABLE game_unified_stats ADD COLUMN elo INT DEFAULT 1200 AFTER best_streak"); } catch (Exception $e) {}
}

// ── GSM Economy Bridge ────────────────────────────────────────

/**
 * Load the GSM economy functions (creditGSM, debitGSM, etc.)
 */
function loadGSMEconomy(): void {
    static $loaded = false;
    if ($loaded) return;

    // We need the functions but NOT the action router.
    // The gsm-economy.php file defines GOSITEME_API and calls session_start,
    // so we extract just the functions we need by including a helper.
    // Since gsm-economy.php is structured with functions at top and router at bottom,
    // we can safely require it if GOSITEME_API is already defined and session already started.
    // However, it also calls ensureEconomyTables and runs the router.
    // Solution: call creditGSM/debitGSM via internal HTTP or duplicate the atomic functions here.

    // For performance, we implement the GSM operations inline using the same DB pattern.
    $loaded = true;
}

/**
 * Debit GSM from client for a wager (atomic, FOR UPDATE locking)
 */
function debitGSMForWager(PDO $db, int $clientId, float $amount, string $matchId, string $gameType): bool {
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
        $db->prepare("UPDATE crypto_gsm_balances SET balance = ?, gaming_spent = gaming_spent + ? WHERE client_id = ?")
            ->execute([$newBal, $amount, $clientId]);

        $db->prepare("INSERT INTO crypto_gsm_ledger (client_id, tx_type, amount, balance_after, description, reference_type, reference_id) VALUES (?,?,?,?,?,?,?)")
            ->execute([$clientId, 'game_entry', -$amount, $newBal, "Wager on $gameType (match $matchId)", 'game_wager', $matchId]);

        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        error_log("GSM wager debit failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Credit GSM to client for a wager payout (atomic)
 */
function creditGSMForWager(PDO $db, int $clientId, float $amount, string $matchId, string $gameType, string $desc): bool {
    if ($amount <= 0) return false;
    $db->beginTransaction();
    try {
        $db->prepare("INSERT IGNORE INTO crypto_gsm_balances (client_id, balance) VALUES (?, 0)")->execute([$clientId]);
        $stmt = $db->prepare("SELECT balance FROM crypto_gsm_balances WHERE client_id = ? FOR UPDATE");
        $stmt->execute([$clientId]);
        $current = (float)$stmt->fetchColumn();
        $newBal = $current + $amount;

        $db->prepare("UPDATE crypto_gsm_balances SET balance = ?, gaming_earned = gaming_earned + ? WHERE client_id = ?")
            ->execute([$newBal, $amount, $clientId]);

        $db->prepare("INSERT INTO crypto_gsm_ledger (client_id, tx_type, amount, balance_after, description, reference_type, reference_id) VALUES (?,?,?,?,?,?,?)")
            ->execute([$clientId, 'game_win', $amount, $newBal, $desc, 'game_wager', $matchId]);

        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        error_log("GSM wager credit failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Get client's available GSM balance
 */
function getAvailableGSM(PDO $db, int $clientId): float {
    $db->prepare("INSERT IGNORE INTO crypto_gsm_balances (client_id, balance) VALUES (?, 0)")->execute([$clientId]);
    $stmt = $db->prepare("SELECT balance - staked_amount AS available FROM crypto_gsm_balances WHERE client_id = ?");
    $stmt->execute([$clientId]);
    return max(0, (float)$stmt->fetchColumn());
}

// ── Utility ───────────────────────────────────────────────────

function getWagerAmount(array $wager, string $field = null): float {
    if ($field) return (float)$wager[$field];
    return match($wager['currency']) {
        'gsm' => (float)$wager['amount_gsm'],
        'sol' => (float)$wager['amount_sol'],
        'usd' => (float)$wager['amount_usd'],
    };
}

function validateGameEvidence(string $gameType, string $result, ?array $evidence, int $moveCount): ?string {
    // Pool: win requires 8-ball pocketed + legitimacy flag
    if ($gameType === 'pool' && $result === 'won') {
        if (!$evidence) return 'Pool wins require game evidence';
        $pocketed = array_map('intval', is_array($evidence['pocketed'] ?? null) ? $evidence['pocketed'] : []);
        if (!in_array(8, $pocketed, true)) return 'Invalid pool finish: 8-ball not pocketed';
        if (empty($evidence['legitimate'])) return 'Invalid pool finish';
        $shots = max($moveCount, (int)($evidence['shots'] ?? 0));
        if ($shots < (MIN_MOVES['pool']['win'] ?? 4)) return 'Insufficient shots for pool win';
    }

    // Chess: win/draw requires PGN with move count validation
    if ($gameType === 'chess' && $result !== 'lost') {
        if (empty($evidence['pgn'])) return 'Chess wins/draws require PGN';
        $pgnMoves = countPgnHalfMoves($evidence['pgn']);
        if ($pgnMoves > 0 && abs($pgnMoves - $moveCount) > 2) return 'PGN move count mismatch';
    }

    // Poker: win requires hand evidence
    if ($gameType === 'poker' && $result === 'won') {
        if (!$evidence) return 'Poker wins require hand evidence';
        if (empty($evidence['hand_rank'])) return 'Poker wins require hand rank';
    }

    return null; // no error
}

/**
 * Count half-moves in a PGN string (thorough parser)
 */
function countPgnHalfMoves(string $pgn): int {
    $pgn = preg_replace('/\{[^}]*\}/', '', $pgn);
    $pgn = preg_replace('/\$\d+/', '', $pgn);
    $pgn = preg_replace('/\b(1-0|0-1|1\/2-1\/2|\*)\b/', '', $pgn);
    $pgn = preg_replace('/\d+\.\.\./', '', $pgn);
    $pgn = preg_replace('/\d+\./', '', $pgn);
    $tokens = preg_split('/\s+/', trim($pgn), -1, PREG_SPLIT_NO_EMPTY);
    return count($tokens);
}

// ═══════════════════════════════════════════════════════════════
//  ACTION ROUTER
// ═══════════════════════════════════════════════════════════════

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

switch ($action) {

    // ═══ PLACE WAGER ═══
    case 'create-wager': // backward compat alias
    case 'place-wager':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['error' => 'POST required']); break;
        }
        requireCSRF();
        $user = bettingRequireAuth();

        $gameType  = $input['game_type'] ?? '';
        $gameMode  = $input['game_mode'] ?? 'ai';
        $currency  = strtolower($input['currency'] ?? 'gsm');
        $amount    = $input['amount'] ?? 0;
        $opponent  = $input['opponent_agent'] ?? null;

        // Validate game type
        if (!in_array($gameType, SUPPORTED_GAMES)) {
            echo json_encode(['error' => 'Invalid game type', 'valid' => SUPPORTED_GAMES]); break;
        }

        // Validate currency
        if (!in_array($currency, SUPPORTED_CURRENCIES)) {
            echo json_encode(['error' => 'Invalid currency', 'valid' => SUPPORTED_CURRENCIES]); break;
        }

        // Validate game mode
        if (!in_array($gameMode, GAME_MODES)) {
            echo json_encode(['error' => 'Invalid game mode', 'valid' => GAME_MODES]); break;
        }

        // Validate amount within limits
        $limits = WAGER_LIMITS[$currency];
        if ($currency === 'gsm') {
            $amount = (float)$amount;
        } else {
            $amount = (int)$amount;
        }

        if ($amount < $limits['min'] || $amount > $limits['max']) {
            echo json_encode(['error' => "Amount out of range ({$limits['min']}–{$limits['max']})", 'limits' => $limits]); break;
        }

        // Validate AI opponent if AI mode
        if ($gameMode === 'ai') {
            if ($opponent) {
                $opponent = preg_replace('/[^a-z0-9\-]/', '', strtolower($opponent));
                if (!isset(GAME_AGENTS[$opponent])) {
                    echo json_encode(['error' => 'Invalid opponent agent', 'valid' => array_keys(GAME_AGENTS)]); break;
                }
            } else {
                $opponent = 'alfred'; // default
            }
        }

        // Check no existing active wager for this game
        $stmt = $db->prepare("SELECT id FROM game_unified_wagers WHERE client_id = ? AND game_type = ? AND status IN ('pending','active') LIMIT 1");
        $stmt->execute([$user['client_id'], $gameType]);
        if ($stmt->fetch()) {
            echo json_encode(['error' => 'You already have an active wager for this game. Settle or cancel it first.']); break;
        }

        $matchId = bin2hex(random_bytes(16));
        $paymentData = [];

        // Process payment by currency
        if ($currency === 'gsm') {
            // Debit GSM immediately
            if (!debitGSMForWager($db, $user['client_id'], $amount, $matchId, $gameType)) {
                echo json_encode(['error' => 'Insufficient GSM balance']); break;
            }
            // GSM wagers are immediately active (no payment confirmation needed)
            $initialStatus = 'active';

        } elseif ($currency === 'usd') {
            // Create Stripe PaymentIntent
            try {
                require_once dirname(__DIR__) . '/pay/vendor/autoload.php';
                \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

                $intent = \Stripe\PaymentIntent::create([
                    'amount' => $amount,
                    'currency' => 'usd',
                    'metadata' => [
                        'type'      => 'game_wager',
                        'match_id'  => $matchId,
                        'client_id' => $user['client_id'],
                        'game_type' => $gameType,
                        'game_mode' => $gameMode,
                    ],
                    'description' => ucfirst($gameType) . " Wager — Match $matchId",
                ]);

                $paymentData['client_secret'] = $intent->client_secret;
                $paymentData['payment_intent_id'] = $intent->id;
            } catch (\Exception $e) {
                error_log("Universal betting Stripe error: " . $e->getMessage());
                echo json_encode(['error' => 'Payment processing failed']); break;
            }
            $initialStatus = 'pending'; // needs confirm-wager

        } elseif ($currency === 'sol') {
            // SOL: client sends tx, then calls confirm-wager with tx signature
            $initialStatus = 'pending';
        }

        // Insert wager
        $stmt = $db->prepare("INSERT INTO game_unified_wagers
            (client_id, match_id, game_type, game_mode, opponent_agent, currency, amount_gsm, amount_sol, amount_usd, status, stripe_pi)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $user['client_id'], $matchId, $gameType, $gameMode, $opponent,
            $currency,
            $currency === 'gsm' ? $amount : 0,
            $currency === 'sol' ? $amount : 0,
            $currency === 'usd' ? $amount : 0,
            $initialStatus,
            $paymentData['payment_intent_id'] ?? null,
        ]);

        $wagerId = (int)$db->lastInsertId();

        // If PvP, add to matchmaking queue
        if ($gameMode === 'pvp') {
            $db->prepare("INSERT INTO game_pvp_queue (client_id, wager_id, game_type, currency, amount, expires_at) VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))")
                ->execute([$user['client_id'], $wagerId, $gameType, $currency, $amount]);
        }

        echo json_encode([
            'success'    => true,
            'wager_id'   => $wagerId,
            'match_id'   => $matchId,
            'game_type'  => $gameType,
            'game_mode'  => $gameMode,
            'currency'   => $currency,
            'amount'     => $amount,
            'status'     => $initialStatus,
            'opponent'   => $gameMode === 'ai' ? GAME_AGENTS[$opponent] ?? null : null,
            'payment'    => $paymentData ?: null,
        ]);
        break;

    // ═══ CONFIRM WAGER (after Stripe/SOL payment) ═══
    case 'confirm-wager':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['error' => 'POST required']); break;
        }
        requireCSRF();
        $user = bettingRequireAuth();

        $matchId     = $input['match_id'] ?? '';
        $txSignature = $input['tx_signature'] ?? null;

        if (!$matchId) {
            echo json_encode(['error' => 'match_id required']); break;
        }

        $stmt = $db->prepare("SELECT * FROM game_unified_wagers WHERE match_id = ? AND client_id = ? AND status = 'pending'");
        $stmt->execute([$matchId, $user['client_id']]);
        $wager = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$wager) {
            echo json_encode(['error' => 'Wager not found or already confirmed']); break;
        }

        if ($wager['currency'] === 'sol') {
            if (!$txSignature) {
                echo json_encode(['error' => 'SOL transaction signature required']); break;
            }
            require_once dirname(__DIR__) . '/pay/includes/crypto-config.php';
            require_once dirname(__DIR__) . '/pay/includes/solana-handler.php';

            $verified = solanaVerifyPayment($txSignature, $wager['amount_sol'] / 1e9, null);
            if (!$verified) {
                echo json_encode(['error' => 'SOL payment verification failed']); break;
            }

            $db->prepare("UPDATE game_unified_wagers SET solana_tx = ?, status = 'active' WHERE id = ?")
                ->execute([$txSignature, $wager['id']]);

        } elseif ($wager['currency'] === 'usd') {
            // Stripe confirms via webhook or manual
            $db->prepare("UPDATE game_unified_wagers SET status = 'active' WHERE id = ?")
                ->execute([$wager['id']]);
        }

        echo json_encode(['success' => true, 'status' => 'active', 'match_id' => $matchId]);
        break;

    // ═══ SETTLE WAGER ═══
    case 'settle-wager':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['error' => 'POST required']); break;
        }
        requireCSRF();
        $user = bettingRequireAuth();

        $matchId       = $input['match_id'] ?? '';
        $result        = $input['result'] ?? ''; // 'won', 'lost', 'draw'
        $moveCount     = max(0, (int)($input['move_count'] ?? $input['moves'] ?? 0));
        $gameDuration  = max(0, (int)($input['game_duration'] ?? 0));
        $gameEvidence  = is_array($input['game_evidence'] ?? null) ? $input['game_evidence'] : null;
        $pgn           = is_string($input['pgn'] ?? null) ? $input['pgn'] : null;

        if (!$matchId || !in_array($result, ['won', 'lost', 'draw'])) {
            echo json_encode(['error' => 'match_id and valid result (won/lost/draw) required']); break;
        }

        // Rate limit settlements
        $stmt = $db->prepare("SELECT settled_at FROM game_unified_wagers WHERE client_id = ? AND status NOT IN ('pending','active') ORDER BY settled_at DESC LIMIT 1");
        $stmt->execute([$user['client_id']]);
        $lastSettled = $stmt->fetchColumn();
        if ($lastSettled && (time() - strtotime($lastSettled)) < SETTLEMENT_COOLDOWN) {
            echo json_encode(['error' => 'Please wait before settling another wager']); break;
        }

        // Get active wager
        $stmt = $db->prepare("SELECT * FROM game_unified_wagers WHERE match_id = ? AND client_id = ? AND status = 'active'");
        $stmt->execute([$matchId, $user['client_id']]);
        $wager = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$wager) {
            echo json_encode(['error' => 'No active wager found for this match']); break;
        }

        $gameType = $wager['game_type'];
        $gameMode = $wager['game_mode'];

        // Anti-cheat: minimum duration
        $createdAt = strtotime($wager['created_at']);
        $elapsed = $gameDuration ?: (time() - $createdAt);
        $minDuration = MIN_DURATION[$gameType][$gameMode] ?? 10;
        if ($result !== 'lost' && $elapsed < $minDuration) {
            error_log("Anti-cheat: settlement too fast for match $matchId ({$elapsed}s < {$minDuration}s)");
            echo json_encode(['error' => 'Game completed too quickly to settle']); break;
        }

        // Anti-cheat: minimum moves
        $minMoves = MIN_MOVES[$gameType][$result === 'draw' ? 'draw' : ($result === 'won' ? 'win' : 'loss')] ?? 0;
        if ($moveCount < $minMoves) {
            error_log("Anti-cheat: suspicious $result in $moveCount moves for match $matchId");
            echo json_encode(['error' => 'Suspicious game result']); break;
        }

        // Anti-cheat: game-specific evidence validation
        $evidenceError = validateGameEvidence($gameType, $result, $gameEvidence, $moveCount);
        if ($evidenceError) {
            error_log("Anti-cheat: $evidenceError for match $matchId");
            echo json_encode(['error' => $evidenceError]); break;
        }

        // Calculate payout
        $wagerAmount = getWagerAmount($wager);
        $payoutMultiplier = match($result) {
            'won'  => $gameMode === 'pvp' ? PAYOUT_PVP_WIN : PAYOUT_AI_WIN,
            'draw' => PAYOUT_DRAW,
            'lost' => PAYOUT_LOSS,
        };
        $payoutAmount = $wagerAmount * $payoutMultiplier;

        $status = match($result) {
            'won'  => 'won',
            'lost' => 'lost',
            'draw' => 'draw',
        };

        // Update wager record
        $payoutCol = "payout_{$wager['currency']}";
        $stmt = $db->prepare("UPDATE game_unified_wagers SET status = ?, {$payoutCol} = ?, move_count = ?, game_duration = ?, game_evidence = ?, game_pgn = ?, settled_at = NOW() WHERE id = ?");
        $stmt->execute([
            $status,
            $payoutAmount,
            $moveCount,
            $elapsed,
            $gameEvidence ? json_encode($gameEvidence) : null,
            $pgn,
            $wager['id'],
        ]);

        // Process payout by currency
        if ($wager['currency'] === 'gsm' && $payoutAmount > 0) {
            $desc = match($result) {
                'won'  => ucfirst($gameType) . " win payout (match $matchId)",
                'draw' => ucfirst($gameType) . " draw refund (match $matchId)",
                default => '',
            };
            creditGSMForWager($db, $user['client_id'], $payoutAmount, $matchId, $gameType, $desc);
        }
        // USD/SOL payouts are handled via balance tables (same pattern as chess-betting)

        // Update per-game stats
        $db->prepare("INSERT INTO game_unified_stats (client_id, game_type, currency, games_played, wins, losses, draws, total_wagered, total_won, total_lost, win_streak, best_streak, last_played_at)
            VALUES (?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                games_played = games_played + 1,
                wins = wins + VALUES(wins),
                losses = losses + VALUES(losses),
                draws = draws + VALUES(draws),
                total_wagered = total_wagered + VALUES(total_wagered),
                total_won = total_won + VALUES(total_won),
                total_lost = total_lost + VALUES(total_lost),
                win_streak = IF(VALUES(wins) > 0, win_streak + 1, 0),
                best_streak = GREATEST(best_streak, IF(VALUES(wins) > 0, win_streak + 1, best_streak)),
                last_played_at = NOW()")
            ->execute([
                $user['client_id'], $gameType, $wager['currency'],
                $result === 'won' ? 1 : 0,
                $result === 'lost' ? 1 : 0,
                $result === 'draw' ? 1 : 0,
                $wagerAmount,
                $payoutAmount,
                $result === 'lost' ? $wagerAmount : 0,
                $result === 'won' ? 1 : 0,
                $result === 'won' ? 1 : 0,
            ]);

        // Award GSM achievements for milestones
        checkBettingAchievements($db, $user['client_id'], $gameType, $result);

        // Update ELO
        $eloChange = match($result) { 'won' => 15, 'lost' => -15, 'draw' => 0 };
        if ($eloChange !== 0) {
            $db->prepare("UPDATE game_unified_stats SET elo = GREATEST(100, elo + ?) WHERE client_id = ? AND game_type = ? AND currency = ?")
                ->execute([$eloChange, $user['client_id'], $gameType, $wager['currency']]);
        }

        // Settle any agent side bets on this wager
        try {
            $abStmt = $db->prepare("SELECT * FROM game_agent_bets WHERE wager_id = ? AND status = 'active'");
            $abStmt->execute([$wager['id']]);
            foreach ($abStmt->fetchAll(PDO::FETCH_ASSOC) as $ab) {
                $abWon = ($ab['bet_side'] === 'player_wins' && $result === 'won')
                      || ($ab['bet_side'] === 'agent_wins' && $result === 'lost')
                      || ($ab['bet_side'] === 'draw' && $result === 'draw');
                $abPayout = $abWon ? round((float)$ab['amount_gsm'] * (float)$ab['odds'], 9) : 0;
                $db->prepare("UPDATE game_agent_bets SET status = ?, payout_gsm = ? WHERE id = ?")
                    ->execute([$abWon ? 'won' : 'lost', $abPayout, $ab['id']]);
                if ($abWon && $abPayout > 0) {
                    creditGSMForWager($db, (int)$ab['client_id'], $abPayout, $wager['match_id'], $gameType, "Agent bet win (odds {$ab['odds']}x)");
                }
            }
        } catch (Exception $e) { /* agent_bets table may not exist yet */ }

        $displayPayout = match($wager['currency']) {
            'gsm' => number_format($payoutAmount, 2) . ' GSM',
            'usd' => '$' . number_format($payoutAmount / 100, 2),
            'sol' => number_format($payoutAmount / 1e9, 4) . ' SOL',
        };

        echo json_encode([
            'success'       => true,
            'result'        => $result,
            'currency'      => $wager['currency'],
            'wager_amount'  => $wagerAmount,
            'payout'        => $payoutAmount,
            'payout_display'=> $displayPayout,
            'move_count'    => $moveCount,
            'duration'      => $elapsed,
            'message'       => match($result) {
                'won'  => "You won {$displayPayout}!",
                'draw' => "Draw — {$displayPayout} returned.",
                'lost' => "Better luck next time!",
            },
        ]);
        break;

    // ═══ CANCEL WAGER ═══
    case 'cancel-wager':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['error' => 'POST required']); break;
        }
        requireCSRF();
        $user = bettingRequireAuth();

        $matchId = $input['match_id'] ?? '';
        if (!$matchId) { echo json_encode(['error' => 'match_id required']); break; }

        $stmt = $db->prepare("SELECT * FROM game_unified_wagers WHERE match_id = ? AND client_id = ? AND status IN ('pending','active')");
        $stmt->execute([$matchId, $user['client_id']]);
        $wager = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$wager) {
            echo json_encode(['error' => 'No cancellable wager found']); break;
        }

        // Refund by currency
        if ($wager['currency'] === 'gsm') {
            $refundAmount = (float)$wager['amount_gsm'];
            if ($refundAmount > 0) {
                creditGSMForWager($db, $user['client_id'], $refundAmount, $matchId, $wager['game_type'], 'Wager cancelled — refund');
            }
        } elseif ($wager['currency'] === 'usd' && $wager['stripe_pi']) {
            try {
                require_once dirname(__DIR__) . '/pay/vendor/autoload.php';
                \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
                \Stripe\Refund::create(['payment_intent' => $wager['stripe_pi']]);
            } catch (\Exception $e) {
                error_log("Wager refund Stripe error: " . $e->getMessage());
            }
        }
        // SOL refunds are manual / handled by smart contract

        $db->prepare("UPDATE game_unified_wagers SET status = 'cancelled', settled_at = NOW() WHERE id = ?")
            ->execute([$wager['id']]);

        // Remove from PvP queue if applicable
        $db->prepare("UPDATE game_pvp_queue SET status = 'cancelled' WHERE wager_id = ? AND status = 'waiting'")
            ->execute([$wager['id']]);

        echo json_encode(['success' => true, 'refunded' => true, 'currency' => $wager['currency']]);
        break;

    // ═══ GET ACTIVE WAGER ═══
    case 'get-active': // backward compat alias
    case 'active-wager':
        $user = bettingRequireAuth();
        $gameType = $_GET['game_type'] ?? null;

        $sql = "SELECT id, match_id, game_type, game_mode, opponent_agent, currency, amount_gsm, amount_sol, amount_usd, status, created_at
                FROM game_unified_wagers WHERE client_id = ? AND status IN ('pending','active')";
        $params = [$user['client_id']];

        if ($gameType && in_array($gameType, SUPPORTED_GAMES)) {
            $sql .= " AND game_type = ?";
            $params[] = $gameType;
        }
        $sql .= " ORDER BY created_at DESC LIMIT 5";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $wagers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'wagers' => $wagers]);
        break;

    // ═══ WAGER HISTORY ═══
    case 'get-wagers': // backward compat alias
    case 'wager-history':
        $user = bettingRequireAuth();
        $gameType = $_GET['game_type'] ?? null;
        $currency = $_GET['currency'] ?? null;
        $limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));
        $offset = max(0, (int)($_GET['offset'] ?? 0));

        $sql = "SELECT id, match_id, game_type, game_mode, opponent_agent, currency,
                    amount_gsm, amount_sol, amount_usd, payout_gsm, payout_sol, payout_usd,
                    status, move_count, game_duration, created_at, settled_at
                FROM game_unified_wagers WHERE client_id = ?";
        $params = [$user['client_id']];

        if ($gameType && in_array($gameType, SUPPORTED_GAMES)) {
            $sql .= " AND game_type = ?";
            $params[] = $gameType;
        }
        if ($currency && in_array($currency, SUPPORTED_CURRENCIES)) {
            $sql .= " AND currency = ?";
            $params[] = $currency;
        }

        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $db->prepare($sql);
        dbExecute($stmt, $params);
        $wagers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get total count
        $countSql = "SELECT COUNT(*) FROM game_unified_wagers WHERE client_id = ?";
        $countParams = [$user['client_id']];
        if ($gameType && in_array($gameType, SUPPORTED_GAMES)) {
            $countSql .= " AND game_type = ?";
            $countParams[] = $gameType;
        }
        $stmt = $db->prepare($countSql);
        $stmt->execute($countParams);
        $total = (int)$stmt->fetchColumn();

        echo json_encode(['success' => true, 'wagers' => $wagers, 'total' => $total, 'limit' => $limit, 'offset' => $offset]);
        break;

    // ═══ GAME STATS ═══
    case 'game-stats':
        $user = bettingRequireAuth();
        $gameType = $_GET['game_type'] ?? null;

        $sql = "SELECT game_type, currency, games_played, wins, losses, draws,
                    total_wagered, total_won, total_lost, win_streak, best_streak, last_played_at
                FROM game_unified_stats WHERE client_id = ?";
        $params = [$user['client_id']];

        if ($gameType && in_array($gameType, SUPPORTED_GAMES)) {
            $sql .= " AND game_type = ?";
            $params[] = $gameType;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Aggregate totals
        $totals = [
            'games_played' => 0, 'wins' => 0, 'losses' => 0, 'draws' => 0,
            'total_wagered_gsm' => 0, 'total_won_gsm' => 0,
            'win_rate' => '0%', 'best_streak' => 0,
        ];
        foreach ($stats as $s) {
            $totals['games_played'] += (int)$s['games_played'];
            $totals['wins'] += (int)$s['wins'];
            $totals['losses'] += (int)$s['losses'];
            $totals['draws'] += (int)$s['draws'];
            if ($s['currency'] === 'gsm') {
                $totals['total_wagered_gsm'] += (float)$s['total_wagered'];
                $totals['total_won_gsm'] += (float)$s['total_won'];
            }
            $totals['best_streak'] = max($totals['best_streak'], (int)$s['best_streak']);
        }
        if ($totals['games_played'] > 0) {
            $totals['win_rate'] = round($totals['wins'] / $totals['games_played'] * 100, 1) . '%';
        }

        echo json_encode(['success' => true, 'per_game' => $stats, 'totals' => $totals]);
        break;

    // ═══ CROSS-GAME LEADERBOARD ═══
    case 'game-leaderboard': // backward compat alias
    case 'leaderboard':
        $gameType = $_GET['game_type'] ?? null;
        $currency = $_GET['currency'] ?? 'gsm';
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));

        $sql = "SELECT s.client_id, c.firstname, c.lastname,
                    SUM(s.wins) AS total_wins, SUM(s.games_played) AS total_games,
                    SUM(s.total_won) AS total_won, MAX(s.best_streak) AS best_streak,
                    ROUND(SUM(s.wins) / GREATEST(SUM(s.games_played), 1) * 100, 1) AS win_rate
                FROM game_unified_stats s
                JOIN tblclients c ON c.id = s.client_id
                WHERE s.currency = ?";
        $params = [$currency];

        if ($gameType && in_array($gameType, SUPPORTED_GAMES)) {
            $sql .= " AND s.game_type = ?";
            $params[] = $gameType;
        }

        $sql .= " GROUP BY s.client_id ORDER BY total_won DESC LIMIT ?";
        $params[] = $limit;

        $stmt = $db->prepare($sql);
        dbExecute($stmt, $params);
        $leaders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Sanitize names
        foreach ($leaders as &$l) {
            $l['name'] = htmlspecialchars($l['firstname'] . ' ' . substr($l['lastname'], 0, 1) . '.', ENT_QUOTES, 'UTF-8');
            unset($l['firstname'], $l['lastname']);
        }

        echo json_encode(['success' => true, 'leaderboard' => $leaders, 'game_type' => $gameType, 'currency' => $currency]);
        break;

    // ═══ ODDS / PAYOUT TABLE ═══
    case 'odds':
        echo json_encode([
            'success'  => true,
            'payouts'  => [
                'ai_win'   => PAYOUT_AI_WIN,
                'pvp_win'  => PAYOUT_PVP_WIN,
                'draw'     => PAYOUT_DRAW,
                'loss'     => PAYOUT_LOSS,
            ],
            'rake'     => HOUSE_RAKE,
            'limits'   => WAGER_LIMITS,
            'games'    => SUPPORTED_GAMES,
            'agents'   => GAME_AGENTS,
            'currencies' => SUPPORTED_CURRENCIES,
        ]);
        break;

    // ═══ TOURNAMENT ENTRY ═══
    case 'tournament-entry':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        requireCSRF();
        $user = bettingRequireAuth();
        $tournamentId = (int)($input['tournament_id'] ?? 0);
        if (!$tournamentId) { echo json_encode(['error' => 'tournament_id required']); break; }
        $stmt = $db->prepare("SELECT * FROM game_tournaments WHERE id = ? AND status = 'registration'");
        $stmt->execute([$tournamentId]);
        $tournament = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$tournament) { echo json_encode(['error' => 'Tournament not found or not open for registration']); break; }
        if ((int)$tournament['current_players'] >= (int)$tournament['max_players']) { echo json_encode(['error' => 'Tournament is full']); break; }
        $stmt = $db->prepare("SELECT id FROM game_tournament_entries WHERE tournament_id = ? AND client_id = ?");
        $stmt->execute([$tournamentId, $user['client_id']]);
        if ($stmt->fetch()) { echo json_encode(['error' => 'Already entered this tournament']); break; }
        $entryFee = (float)$tournament['entry_fee_gsm'];
        if ($entryFee > 0) {
            $rake = round($entryFee * HOUSE_RAKE, 9);
            if (!debitGSMForWager($db, $user['client_id'], $entryFee, "tournament-$tournamentId", $tournament['game_type'])) {
                echo json_encode(['error' => 'Insufficient GSM balance for entry fee']); break;
            }
            $db->prepare("UPDATE game_tournaments SET prize_pool_gsm = prize_pool_gsm + ? WHERE id = ?")
                ->execute([$entryFee - $rake, $tournamentId]);
        }
        $db->prepare("INSERT INTO game_tournament_entries (tournament_id, client_id, seed) VALUES (?, ?, ?)")
            ->execute([$tournamentId, $user['client_id'], (int)$tournament['current_players'] + 1]);
        $db->prepare("UPDATE game_tournaments SET current_players = current_players + 1 WHERE id = ?")->execute([$tournamentId]);
        echo json_encode(['success' => true, 'tournament_id' => $tournamentId, 'entry_fee' => $entryFee, 'current_players' => (int)$tournament['current_players'] + 1]);
        break;

    // ═══ LIST TOURNAMENTS ═══
    case 'tournaments':
        $gameType = $_GET['game_type'] ?? null;
        $status = $_GET['status'] ?? null;
        $sql = "SELECT id, game_type, title, description, entry_fee_gsm, prize_pool_gsm, prize_splits, format, max_players, current_players, status, starts_at, ends_at FROM game_tournaments WHERE 1=1";
        $params = [];
        if ($gameType && in_array($gameType, SUPPORTED_GAMES)) { $sql .= " AND game_type = ?"; $params[] = $gameType; }
        if ($status && in_array($status, ['upcoming','registration','active','completed'])) { $sql .= " AND status = ?"; $params[] = $status; }
        $sql .= " ORDER BY starts_at ASC LIMIT 50";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $tournaments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($tournaments as &$t) { $t['prize_splits'] = json_decode($t['prize_splits'], true); }
        echo json_encode(['success' => true, 'tournaments' => $tournaments]);
        break;

    // ═══ AGENT SIDE BET ═══
    case 'agent-bet':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        requireCSRF();
        $user = bettingRequireAuth();
        $wagerId = (int)($input['wager_id'] ?? 0);
        $betSide = $input['bet_side'] ?? '';
        $amount = (float)($input['amount'] ?? 0);
        if (!$wagerId || !in_array($betSide, ['player_wins','agent_wins','draw'])) {
            echo json_encode(['error' => 'wager_id and valid bet_side (player_wins/agent_wins/draw) required']); break;
        }
        if ($amount < 1 || $amount > 1000) { echo json_encode(['error' => 'Agent bet amount must be 1-1000 GSM']); break; }
        $stmt = $db->prepare("SELECT * FROM game_unified_wagers WHERE id = ? AND status = 'active' AND game_mode = 'ai'");
        $stmt->execute([$wagerId]);
        $wager = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$wager) { echo json_encode(['error' => 'Active AI wager not found']); break; }
        $odds = match($betSide) { 'player_wins' => 1.80, 'agent_wins' => 2.20, 'draw' => 4.00 };
        if (!debitGSMForWager($db, $user['client_id'], $amount, "agent-bet-$wagerId", $wager['game_type'])) {
            echo json_encode(['error' => 'Insufficient GSM balance']); break;
        }
        $db->prepare("INSERT INTO game_agent_bets (wager_id, client_id, agent_id, bet_side, amount_gsm, odds) VALUES (?,?,?,?,?,?)")
            ->execute([$wagerId, $user['client_id'], $wager['opponent_agent'] ?? 'alfred', $betSide, $amount, $odds]);
        echo json_encode(['success' => true, 'bet_id' => (int)$db->lastInsertId(), 'odds' => $odds, 'potential_payout' => round($amount * $odds, 2)]);
        break;

    // ═══ GET BALANCE ═══
    case 'get-balance':
        $user = bettingRequireAuth();
        $available = getAvailableGSM($db, $user['client_id']);
        $stmt = $db->prepare("SELECT SUM(wins) AS tw, SUM(losses) AS tl, SUM(games_played) AS tg, SUM(total_won) AS twon, SUM(total_lost) AS tlost FROM game_unified_stats WHERE client_id = ?");
        $stmt->execute([$user['client_id']]);
        $s = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'gsm_available' => $available, 'total_wins' => (int)($s['tw'] ?? 0), 'total_losses' => (int)($s['tl'] ?? 0), 'total_games' => (int)($s['tg'] ?? 0), 'total_won_gsm' => (float)($s['twon'] ?? 0), 'total_lost_gsm' => (float)($s['tlost'] ?? 0)]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action', 'valid' => [
            'place-wager', 'confirm-wager', 'settle-wager', 'cancel-wager',
            'active-wager', 'wager-history', 'game-stats', 'leaderboard', 'odds',
            'tournament-entry', 'tournaments', 'agent-bet', 'get-balance',
        ]]);
}

// ── Achievement Checks ────────────────────────────────────────

function checkBettingAchievements(PDO $db, int $clientId, string $gameType, string $result): void {
    if ($result !== 'won') return;

    try {
        // Get current stats
        $stmt = $db->prepare("SELECT SUM(wins) AS total_wins, SUM(games_played) AS total_games, MAX(best_streak) AS best_streak FROM game_unified_stats WHERE client_id = ?");
        $stmt->execute([$clientId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        $totalWins = (int)($stats['total_wins'] ?? 0);
        $bestStreak = (int)($stats['best_streak'] ?? 0);

        $achievements = [
            ['key' => 'first_win',        'threshold' => 1,   'field' => 'wins',   'title' => 'First Blood',        'desc' => 'Won your first wagered game',     'reward' => 5],
            ['key' => 'ten_wins',         'threshold' => 10,  'field' => 'wins',   'title' => 'Getting Warmed Up',   'desc' => 'Won 10 wagered games',            'reward' => 25],
            ['key' => 'fifty_wins',       'threshold' => 50,  'field' => 'wins',   'title' => 'Veteran',             'desc' => 'Won 50 wagered games',            'reward' => 100],
            ['key' => 'hundred_wins',     'threshold' => 100, 'field' => 'wins',   'title' => 'Centurion',           'desc' => 'Won 100 wagered games',           'reward' => 500],
            ['key' => 'streak_five',      'threshold' => 5,   'field' => 'streak', 'title' => 'Hot Streak',          'desc' => '5-game winning streak',           'reward' => 50],
            ['key' => 'streak_ten',       'threshold' => 10,  'field' => 'streak', 'title' => 'Unstoppable',         'desc' => '10-game winning streak',          'reward' => 200],
        ];

        foreach ($achievements as $a) {
            $value = $a['field'] === 'streak' ? $bestStreak : $totalWins;
            if ($value >= $a['threshold']) {
                // Insert ignore — only awards once
                $stmt = $db->prepare("INSERT IGNORE INTO gsm_achievements (client_id, achievement_key, game_type, title, description, gsm_reward) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$clientId, $a['key'], $gameType, $a['title'], $a['desc'], $a['reward']]);

                // If newly inserted, credit the reward
                if ($stmt->rowCount() > 0 && $a['reward'] > 0) {
                    creditGSMForWager($db, $clientId, $a['reward'], 'achievement', $gameType, "Achievement: {$a['title']}");
                }
            }
        }
    } catch (Exception $e) {
        error_log("Achievement check error: " . $e->getMessage());
    }
}
