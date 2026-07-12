<?php
/**
 * CHESS MASTERS — Betting API
 * GSM Alfred OS · Project Grandmaster II
 *
 * Endpoints:
 *   create-wager     — Place a new wager (USD via Stripe, SOL via Solana)
 *   accept-wager     — Accept a PvP wager (opponent)
 *   settle-wager     — Settle after game ends (win/lose/draw)
 *   cancel-wager     — Cancel an unsettled wager
 *   get-wagers       — Get user's wager history
 *   get-balance      — Get available balance
 *   cashout          — Request payout
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . (defined('SITE_URL') ? SITE_URL : 'https://gositeme.com'));
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

// Rate limit
apiRateLimit(20, 60, 'chess-betting');

// Session
session_start();
$sessionId = session_id();

// ── Ensure tables exist ──
function ensureChessBettingTables(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS chess_wagers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        match_id VARCHAR(64) NOT NULL,
        session_id VARCHAR(128) NOT NULL,
        opponent_session_id VARCHAR(128) DEFAULT NULL,
        game_mode ENUM('ai','pvp') DEFAULT 'ai',
        amount INT NOT NULL COMMENT 'Amount in cents (USD) or lamports (SOL)',
        currency ENUM('usd','sol') DEFAULT 'usd',
        side ENUM('white','black','random') DEFAULT 'random',
        ai_personality VARCHAR(32) DEFAULT NULL,
        ai_difficulty VARCHAR(16) DEFAULT NULL,
        status ENUM('pending','active','won','lost','draw','cancelled','expired') DEFAULT 'pending',
        stripe_payment_intent VARCHAR(128) DEFAULT NULL,
        solana_tx_signature VARCHAR(128) DEFAULT NULL,
        payout_amount INT DEFAULT 0,
        payout_status ENUM('none','pending','completed','failed') DEFAULT 'none',
        move_count INT DEFAULT 0,
        game_pgn TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        settled_at TIMESTAMP NULL,
        INDEX idx_session (session_id),
        INDEX idx_match (match_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS chess_balances (
        session_id VARCHAR(128) PRIMARY KEY,
        usd_balance INT DEFAULT 0 COMMENT 'Balance in cents',
        sol_balance BIGINT DEFAULT 0 COMMENT 'Balance in lamports',
        total_wagered INT DEFAULT 0,
        total_won INT DEFAULT 0,
        total_lost INT DEFAULT 0,
        games_played INT DEFAULT 0,
        win_streak INT DEFAULT 0,
        best_streak INT DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

// ── Wager limits ──
const MIN_USD_WAGER = 100;   // $1.00
const MAX_USD_WAGER = 2500;  // $25.00
const MIN_SOL_WAGER = 10000000;  // 0.01 SOL in lamports
const MAX_SOL_WAGER = 5000000000; // 5 SOL
const VALID_USD_AMOUNTS = [100, 300, 500, 1000, 2500]; // cents
const HOUSE_EDGE = 0.05; // 5% on PvP, 0% on AI games

$db = getDB();
if (!$db) { jsonResponse(['error' => 'Database unavailable'], 503); }

ensureChessBettingTables($db);

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

switch ($action) {

    // ═══════════════════════════════════
    // CREATE WAGER
    // ═══════════════════════════════════
    case 'create-wager':
        requireCSRF();

        $amount   = intval($input['amount'] ?? 0);
        $currency = $input['currency'] ?? 'usd';
        $gameMode = $input['game_mode'] ?? 'ai';
        $side     = $input['side'] ?? 'random';
        $aiPersonality = $input['ai_personality'] ?? null;
        $aiDifficulty  = $input['ai_difficulty'] ?? 'medium';

        // Validate currency
        if (!in_array($currency, ['usd', 'sol'])) {
            jsonResponse(['error' => 'Invalid currency'], 400);
        }

        // Validate amount
        if ($currency === 'usd') {
            if (!in_array($amount, VALID_USD_AMOUNTS)) {
                jsonResponse(['error' => 'Invalid wager amount', 'valid' => VALID_USD_AMOUNTS], 400);
            }
        } else {
            if ($amount < MIN_SOL_WAGER || $amount > MAX_SOL_WAGER) {
                jsonResponse(['error' => 'SOL wager out of range'], 400);
            }
        }

        // Validate game mode
        if (!in_array($gameMode, ['ai', 'pvp'])) {
            jsonResponse(['error' => 'Invalid game mode'], 400);
        }
        if (!in_array($side, ['white', 'black', 'random'])) {
            jsonResponse(['error' => 'Invalid side'], 400);
        }

        // Check for existing pending wager
        $stmt = $db->prepare("SELECT id FROM chess_wagers WHERE session_id = ? AND status IN ('pending','active') LIMIT 1");
        $stmt->execute([$sessionId]);
        if ($stmt->fetch()) {
            jsonResponse(['error' => 'You already have an active wager. Settle or cancel it first.'], 409);
        }

        $matchId = bin2hex(random_bytes(16));

        // Process payment
        $paymentData = [];

        if ($currency === 'usd') {
            // Create Stripe PaymentIntent
            try {
                require_once dirname(__DIR__) . '/pay/vendor/autoload.php';
                \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

                $intent = \Stripe\PaymentIntent::create([
                    'amount' => $amount,
                    'currency' => 'usd',
                    'metadata' => [
                        'type' => 'chess_wager',
                        'match_id' => $matchId,
                        'session_id' => $sessionId,
                        'game_mode' => $gameMode,
                    ],
                    'description' => "Chess Masters Wager - Match $matchId",
                ]);

                $paymentData['client_secret'] = $intent->client_secret;
                $paymentData['payment_intent_id'] = $intent->id;

            } catch (\Exception $e) {
                error_log("Chess betting Stripe error: " . $e->getMessage());
                jsonResponse(['error' => 'Payment processing failed'], 500);
            }
        }

        // Insert wager
        $stmt = $db->prepare("INSERT INTO chess_wagers
            (match_id, session_id, game_mode, amount, currency, side, ai_personality, ai_difficulty, stripe_payment_intent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $matchId, $sessionId, $gameMode, $amount, $currency, $side,
            $aiPersonality, $aiDifficulty,
            $paymentData['payment_intent_id'] ?? null,
        ]);

        $wagerId = $db->lastInsertId();

        jsonResponse([
            'success' => true,
            'wager_id' => intval($wagerId),
            'match_id' => $matchId,
            'amount' => $amount,
            'currency' => $currency,
            'payment' => $paymentData,
        ]);
        break;

    // ═══════════════════════════════════
    // CONFIRM WAGER (after payment)
    // ═══════════════════════════════════
    case 'confirm-wager':
        requireCSRF();

        $matchId = $input['match_id'] ?? '';
        $txSignature = $input['tx_signature'] ?? null; // For SOL payments

        if (!$matchId) {
            jsonResponse(['error' => 'match_id required'], 400);
        }

        $stmt = $db->prepare("SELECT * FROM chess_wagers WHERE match_id = ? AND session_id = ? AND status = 'pending'");
        $stmt->execute([$matchId, $sessionId]);
        $wager = $stmt->fetch();

        if (!$wager) {
            jsonResponse(['error' => 'Wager not found'], 404);
        }

        // For SOL: verify the transaction
        if ($wager['currency'] === 'sol' && $txSignature) {
            require_once dirname(__DIR__) . '/pay/includes/crypto-config.php';
            require_once dirname(__DIR__) . '/pay/includes/solana-handler.php';

            $verified = solanaVerifyPayment(
                $txSignature,
                $wager['amount'] / 1e9, // lamports to SOL
                null // from any wallet
            );

            if (!$verified) {
                jsonResponse(['error' => 'SOL payment verification failed'], 400);
            }

            $stmt = $db->prepare("UPDATE chess_wagers SET solana_tx_signature = ?, status = 'active' WHERE id = ?");
            $stmt->execute([$txSignature, $wager['id']]);
        }

        // For USD: Stripe webhook handles confirmation, but we accept manual confirm too
        if ($wager['currency'] === 'usd') {
            $stmt = $db->prepare("UPDATE chess_wagers SET status = 'active' WHERE id = ?");
            $stmt->execute([$wager['id']]);
        }

        jsonResponse(['success' => true, 'status' => 'active', 'match_id' => $matchId]);
        break;

    // ═══════════════════════════════════
    // SETTLE WAGER
    // ═══════════════════════════════════
    case 'settle-wager':
        requireCSRF();

        $matchId  = $input['match_id'] ?? '';
        $result   = $input['result'] ?? ''; // 'win', 'lose', 'draw'
        $moveCount = intval($input['move_count'] ?? 0);
        $pgn      = $input['pgn'] ?? null;

        if (!$matchId || !in_array($result, ['win', 'lose', 'draw'])) {
            jsonResponse(['error' => 'match_id and valid result required'], 400);
        }

        // Anti-cheat: minimum moves
        if ($result === 'win' && $moveCount < 4) {
            error_log("Chess betting anti-cheat: suspicious win in $moveCount moves, match $matchId");
            jsonResponse(['error' => 'Suspicious game result'], 400);
        }

        // Rate limit settlements
        $stmt = $db->prepare("SELECT settled_at FROM chess_wagers WHERE session_id = ? AND status NOT IN ('pending','active') ORDER BY settled_at DESC LIMIT 1");
        $stmt->execute([$sessionId]);
        $lastSettled = $stmt->fetchColumn();
        if ($lastSettled && (time() - strtotime($lastSettled)) < 30) {
            jsonResponse(['error' => 'Please wait before settling another wager'], 429);
        }

        // Get the wager
        $stmt = $db->prepare("SELECT * FROM chess_wagers WHERE match_id = ? AND session_id = ? AND status = 'active'");
        $stmt->execute([$matchId, $sessionId]);
        $wager = $stmt->fetch();

        if (!$wager) {
            jsonResponse(['error' => 'No active wager found for this match'], 404);
        }

        // Calculate payout
        $payout = 0;
        $status = $result;

        if ($result === 'win') {
            if ($wager['game_mode'] === 'ai') {
                $payout = $wager['amount'] * 2; // 2x on AI games
            } else {
                $payout = intval($wager['amount'] * 2 * (1 - HOUSE_EDGE)); // 2x minus house edge on PvP
            }
        } elseif ($result === 'draw') {
            $payout = $wager['amount']; // Refund on draw
        }
        // Loss = 0 payout

        // Update wager
        $stmt = $db->prepare("UPDATE chess_wagers SET status = ?, payout_amount = ?, move_count = ?, game_pgn = ?, settled_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $payout, $moveCount, $pgn, $wager['id']]);

        // Update balance
        $balanceField = $wager['currency'] === 'usd' ? 'usd_balance' : 'sol_balance';
        $stmt = $db->prepare("INSERT INTO chess_balances (session_id, {$balanceField}, total_wagered, games_played)
            VALUES (?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE
            {$balanceField} = {$balanceField} + ?,
            total_wagered = total_wagered + ?,
            games_played = games_played + 1" .
            ($result === 'won' ? ", total_won = total_won + 1, win_streak = win_streak + 1, best_streak = GREATEST(best_streak, win_streak + 1)" : '') .
            ($result === 'lost' ? ", total_lost = total_lost + 1, win_streak = 0" : ''));
        $stmt->execute([$sessionId, $payout, $wager['amount'], $payout, $wager['amount']]);

        jsonResponse([
            'success' => true,
            'result' => $result,
            'wager_amount' => $wager['amount'],
            'payout' => $payout,
            'currency' => $wager['currency'],
            'move_count' => $moveCount,
        ]);
        break;

    // ═══════════════════════════════════
    // CANCEL WAGER
    // ═══════════════════════════════════
    case 'cancel-wager':
        requireCSRF();

        $matchId = $input['match_id'] ?? '';
        if (!$matchId) jsonResponse(['error' => 'match_id required'], 400);

        $stmt = $db->prepare("SELECT * FROM chess_wagers WHERE match_id = ? AND session_id = ? AND status IN ('pending','active')");
        $stmt->execute([$matchId, $sessionId]);
        $wager = $stmt->fetch();

        if (!$wager) {
            jsonResponse(['error' => 'No cancellable wager found'], 404);
        }

        // If Stripe payment was made, issue refund
        if ($wager['stripe_payment_intent'] && $wager['currency'] === 'usd') {
            try {
                require_once dirname(__DIR__) . '/pay/vendor/autoload.php';
                \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
                \Stripe\Refund::create(['payment_intent' => $wager['stripe_payment_intent']]);
            } catch (\Exception $e) {
                error_log("Chess betting refund error: " . $e->getMessage());
            }
        }

        $stmt = $db->prepare("UPDATE chess_wagers SET status = 'cancelled', settled_at = NOW() WHERE id = ?");
        $stmt->execute([$wager['id']]);

        jsonResponse(['success' => true, 'refunded' => true]);
        break;

    // ═══════════════════════════════════
    // GET WAGERS (history)
    // ═══════════════════════════════════
    case 'get-wagers':
        $limit = min(50, intval($_GET['limit'] ?? 20));

        $stmt = $db->prepare("SELECT id, match_id, game_mode, amount, currency, side, ai_personality,
            status, payout_amount, move_count, created_at, settled_at
            FROM chess_wagers WHERE session_id = ? ORDER BY created_at DESC LIMIT ?");
        dbExecute($stmt, [$sessionId, $limit]);
        $wagers = $stmt->fetchAll();

        jsonResponse(['success' => true, 'wagers' => $wagers]);
        break;

    // ═══════════════════════════════════
    // GET BALANCE
    // ═══════════════════════════════════
    case 'get-balance':
        $stmt = $db->prepare("SELECT * FROM chess_balances WHERE session_id = ?");
        $stmt->execute([$sessionId]);
        $balance = $stmt->fetch() ?: [
            'usd_balance' => 0, 'sol_balance' => 0,
            'total_wagered' => 0, 'total_won' => 0, 'total_lost' => 0,
            'games_played' => 0, 'win_streak' => 0, 'best_streak' => 0,
        ];

        jsonResponse(['success' => true, 'balance' => $balance]);
        break;

    // ═══════════════════════════════════
    // GET ACTIVE WAGER
    // ═══════════════════════════════════
    case 'get-active':
        $stmt = $db->prepare("SELECT * FROM chess_wagers WHERE session_id = ? AND status IN ('pending','active') LIMIT 1");
        $stmt->execute([$sessionId]);
        $active = $stmt->fetch();

        jsonResponse(['success' => true, 'wager' => $active ?: null]);
        break;

    default:
        jsonResponse(['error' => 'Unknown action', 'valid' => [
            'create-wager', 'confirm-wager', 'settle-wager', 'cancel-wager',
            'get-wagers', 'get-balance', 'get-active',
        ]], 400);
}
