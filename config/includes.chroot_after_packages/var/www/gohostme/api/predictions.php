<?php
/**
 * Predictions Market API — GoSiteMe GSM Economy
 * ══════════════════════════════════════════════════════════════
 * Binary outcome prediction markets. Users stake GSM on yes/no outcomes.
 * Market-maker model: odds shift based on pool ratios.
 *
 * Actions:
 *   list_markets    — Browse active/upcoming prediction markets
 *   market_detail   — Full details + pool data for a market
 *   place_bet       — Stake GSM on yes or no
 *   my_bets         — User's active and settled predictions
 *   resolve         — Admin: resolve a market outcome (Commander only)
 *   create_market   — Admin: create a new prediction market
 *   categories      — List prediction categories
 *   leaderboard     — Top predictors by accuracy
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

session_start();
$client_id = $_SESSION['client_id'] ?? null;
$action = $_REQUEST['action'] ?? 'list_markets';
$db = getDB();

// ── Schema ────────────────────────────────────────────────────
$db->exec("CREATE TABLE IF NOT EXISTS prediction_markets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(300) NOT NULL,
    description TEXT,
    category VARCHAR(60) DEFAULT 'general',
    outcome_yes_label VARCHAR(120) DEFAULT 'Yes',
    outcome_no_label VARCHAR(120) DEFAULT 'No',
    pool_yes DECIMAL(20,9) DEFAULT 0,
    pool_no DECIMAL(20,9) DEFAULT 0,
    total_bettors INT DEFAULT 0,
    resolved_outcome ENUM('yes','no','cancelled') DEFAULT NULL,
    status ENUM('upcoming','active','closed','resolved','cancelled') DEFAULT 'active',
    creator_id INT UNSIGNED DEFAULT NULL,
    closes_at DATETIME DEFAULT NULL,
    resolved_at DATETIME DEFAULT NULL,
    resolution_source VARCHAR(500) DEFAULT '',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY idx_status (status),
    KEY idx_category (category),
    KEY idx_closes (closes_at),
    FULLTEXT idx_search (title, description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS prediction_bets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    market_id INT UNSIGNED NOT NULL,
    client_id INT UNSIGNED NOT NULL,
    side ENUM('yes','no') NOT NULL,
    amount_gsm DECIMAL(20,9) NOT NULL,
    odds_at_time DECIMAL(8,4) NOT NULL,
    payout_gsm DECIMAL(20,9) DEFAULT 0,
    status ENUM('active','won','lost','refunded') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    settled_at DATETIME DEFAULT NULL,
    KEY idx_market (market_id),
    KEY idx_client (client_id),
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Auth Helper ───────────────────────────────────────────────
function predRequireAuth(): array {
    if (!empty($_SESSION['logged_in']) && !empty($_SESSION['client_id'])) {
        return ['client_id' => (int)$_SESSION['client_id']];
    }
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

function isCommander(): bool {
    return ($_SESSION['client_id'] ?? 0) == 33;
}

function calcOdds(float $poolYes, float $poolNo): array {
    $total = $poolYes + $poolNo;
    if ($total <= 0) return ['yes' => 2.00, 'no' => 2.00, 'yes_pct' => 50.0, 'no_pct' => 50.0];
    $yesPct = round($poolYes / $total * 100, 1);
    $noPct = round($poolNo / $total * 100, 1);
    $yesOdds = $poolNo > 0 ? round($total / $poolYes, 4) : 2.00;
    $noOdds = $poolYes > 0 ? round($total / $poolNo, 4) : 2.00;
    return ['yes' => max(1.01, $yesOdds), 'no' => max(1.01, $noOdds), 'yes_pct' => $yesPct, 'no_pct' => $noPct];
}

// ── GSM helpers (inline, same pattern as universal-betting) ──
function debitGSM(PDO $db, int $clientId, float $amount, string $desc): bool {
    if ($amount <= 0) return false;
    $db->beginTransaction();
    try {
        $db->prepare("INSERT IGNORE INTO crypto_gsm_balances (client_id, balance) VALUES (?, 0)")->execute([$clientId]);
        $stmt = $db->prepare("SELECT balance, staked_amount FROM crypto_gsm_balances WHERE client_id = ? FOR UPDATE");
        $stmt->execute([$clientId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $available = (float)$row['balance'] - (float)$row['staked_amount'];
        if ($available < $amount) { $db->rollBack(); return false; }
        $newBal = (float)$row['balance'] - $amount;
        $db->prepare("UPDATE crypto_gsm_balances SET balance = ? WHERE client_id = ?")->execute([$newBal, $clientId]);
        $db->prepare("INSERT INTO crypto_gsm_ledger (client_id, tx_type, amount, balance_after, description) VALUES (?,?,?,?,?)")
            ->execute([$clientId, 'prediction_bet', -$amount, $newBal, $desc]);
        $db->commit();
        return true;
    } catch (Exception $e) { $db->rollBack(); return false; }
}

function creditGSM(PDO $db, int $clientId, float $amount, string $desc): bool {
    if ($amount <= 0) return false;
    $db->beginTransaction();
    try {
        $db->prepare("INSERT IGNORE INTO crypto_gsm_balances (client_id, balance) VALUES (?, 0)")->execute([$clientId]);
        $stmt = $db->prepare("SELECT balance FROM crypto_gsm_balances WHERE client_id = ? FOR UPDATE");
        $stmt->execute([$clientId]);
        $current = (float)$stmt->fetchColumn();
        $newBal = $current + $amount;
        $db->prepare("UPDATE crypto_gsm_balances SET balance = ? WHERE client_id = ?")->execute([$newBal, $clientId]);
        $db->prepare("INSERT INTO crypto_gsm_ledger (client_id, tx_type, amount, balance_after, description) VALUES (?,?,?,?,?)")
            ->execute([$clientId, 'prediction_win', $amount, $newBal, $desc]);
        $db->commit();
        return true;
    } catch (Exception $e) { $db->rollBack(); return false; }
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

// ── Action Router ─────────────────────────────────────────────
try {
    switch ($action) {

        case 'list_markets':
            $category = $_GET['category'] ?? null;
            $status = $_GET['status'] ?? 'active';
            $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
            $offset = max(0, (int)($_GET['offset'] ?? 0));

            $sql = "SELECT id, title, description, category, outcome_yes_label, outcome_no_label, pool_yes, pool_no, total_bettors, status, closes_at, created_at FROM prediction_markets WHERE status = ?";
            $params = [$status];
            if ($category) { $sql .= " AND category = ?"; $params[] = $category; }
            $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit; $params[] = $offset;

            $stmt = $db->prepare($sql);
            dbExecute($stmt, $params);
            $markets = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($markets as &$m) {
                $m['odds'] = calcOdds((float)$m['pool_yes'], (float)$m['pool_no']);
            }
            echo json_encode(['success' => true, 'markets' => $markets]);
            break;

        case 'market_detail':
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) { echo json_encode(['error' => 'id required']); break; }
            $stmt = $db->prepare("SELECT * FROM prediction_markets WHERE id = ?");
            $stmt->execute([$id]);
            $market = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$market) { echo json_encode(['error' => 'Market not found']); break; }
            $market['odds'] = calcOdds((float)$market['pool_yes'], (float)$market['pool_no']);

            // Recent bets
            $stmt = $db->prepare("SELECT pb.side, pb.amount_gsm, pb.odds_at_time, pb.created_at, c.firstname FROM prediction_bets pb JOIN tblclients c ON c.id = pb.client_id WHERE pb.market_id = ? ORDER BY pb.created_at DESC LIMIT 20");
            $stmt->execute([$id]);
            $recentBets = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($recentBets as &$b) { $b['firstname'] = htmlspecialchars(substr($b['firstname'], 0, 1) . '***', ENT_QUOTES, 'UTF-8'); }

            echo json_encode(['success' => true, 'market' => $market, 'recent_bets' => $recentBets]);
            break;

        case 'place_bet':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
            requireCSRF();
            $user = predRequireAuth();
            $marketId = (int)($input['market_id'] ?? 0);
            $side = $input['side'] ?? '';
            $amount = (float)($input['amount'] ?? 0);

            if (!$marketId || !in_array($side, ['yes', 'no'])) {
                echo json_encode(['error' => 'market_id and side (yes/no) required']); break;
            }
            if ($amount < 1 || $amount > 10000) {
                echo json_encode(['error' => 'Amount must be 1-10,000 GSM']); break;
            }

            $stmt = $db->prepare("SELECT * FROM prediction_markets WHERE id = ? AND status = 'active'");
            $stmt->execute([$marketId]);
            $market = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$market) { echo json_encode(['error' => 'Market not found or not active']); break; }
            if ($market['closes_at'] && strtotime($market['closes_at']) < time()) {
                echo json_encode(['error' => 'Market is closed for betting']); break;
            }

            $odds = calcOdds((float)$market['pool_yes'], (float)$market['pool_no']);
            $currentOdds = $odds[$side];

            if (!debitGSM($db, $user['client_id'], $amount, "Prediction bet on market #$marketId ($side)")) {
                echo json_encode(['error' => 'Insufficient GSM balance']); break;
            }

            $poolCol = $side === 'yes' ? 'pool_yes' : 'pool_no';
            $db->prepare("UPDATE prediction_markets SET $poolCol = $poolCol + ?, total_bettors = total_bettors + 1 WHERE id = ?")
                ->execute([$amount, $marketId]);

            $db->prepare("INSERT INTO prediction_bets (market_id, client_id, side, amount_gsm, odds_at_time) VALUES (?,?,?,?,?)")
                ->execute([$marketId, $user['client_id'], $side, $amount, $currentOdds]);
            $betId = (int)$db->lastInsertId();

            $newOdds = calcOdds((float)$market['pool_yes'] + ($side === 'yes' ? $amount : 0),
                                (float)$market['pool_no'] + ($side === 'no' ? $amount : 0));

            echo json_encode(['success' => true, 'bet_id' => $betId, 'side' => $side, 'amount' => $amount, 'odds_locked' => $currentOdds, 'new_odds' => $newOdds]);
            break;

        case 'my_bets':
            $user = predRequireAuth();
            $status = $_GET['status'] ?? null;
            $sql = "SELECT pb.*, pm.title, pm.status AS market_status, pm.resolved_outcome FROM prediction_bets pb JOIN prediction_markets pm ON pm.id = pb.market_id WHERE pb.client_id = ?";
            $params = [$user['client_id']];
            if ($status && in_array($status, ['active','won','lost','refunded'])) { $sql .= " AND pb.status = ?"; $params[] = $status; }
            $sql .= " ORDER BY pb.created_at DESC LIMIT 50";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['success' => true, 'bets' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'resolve':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
            if (!isCommander()) { echo json_encode(['error' => 'Commander only']); break; }
            $marketId = (int)($input['market_id'] ?? 0);
            $outcome = $input['outcome'] ?? '';
            $source = $input['source'] ?? '';

            if (!$marketId || !in_array($outcome, ['yes', 'no', 'cancelled'])) {
                echo json_encode(['error' => 'market_id and outcome (yes/no/cancelled) required']); break;
            }

            $stmt = $db->prepare("SELECT * FROM prediction_markets WHERE id = ? AND status IN ('active','closed')");
            $stmt->execute([$marketId]);
            $market = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$market) { echo json_encode(['error' => 'Market not found or already resolved']); break; }

            $db->prepare("UPDATE prediction_markets SET status = 'resolved', resolved_outcome = ?, resolved_at = NOW(), resolution_source = ? WHERE id = ?")
                ->execute([$outcome, $source, $marketId]);

            if ($outcome === 'cancelled') {
                // Refund all bets
                $bets = $db->prepare("SELECT * FROM prediction_bets WHERE market_id = ? AND status = 'active'");
                $bets->execute([$marketId]);
                foreach ($bets->fetchAll(PDO::FETCH_ASSOC) as $bet) {
                    creditGSM($db, (int)$bet['client_id'], (float)$bet['amount_gsm'], "Prediction refund — market #$marketId cancelled");
                    $db->prepare("UPDATE prediction_bets SET status = 'refunded', settled_at = NOW() WHERE id = ?")->execute([$bet['id']]);
                }
            } else {
                // Pay winners proportionally from the losing pool
                $totalPool = (float)$market['pool_yes'] + (float)$market['pool_no'];
                $winnerPool = $outcome === 'yes' ? (float)$market['pool_yes'] : (float)$market['pool_no'];
                $rake = round($totalPool * 0.03, 9); // 3% platform fee on predictions
                $distributable = $totalPool - $rake;

                $bets = $db->prepare("SELECT * FROM prediction_bets WHERE market_id = ? AND status = 'active'");
                $bets->execute([$marketId]);
                foreach ($bets->fetchAll(PDO::FETCH_ASSOC) as $bet) {
                    if ($bet['side'] === $outcome) {
                        // Winner — proportional share of total pool
                        $share = $winnerPool > 0 ? (float)$bet['amount_gsm'] / $winnerPool : 0;
                        $payout = round($distributable * $share, 9);
                        creditGSM($db, (int)$bet['client_id'], $payout, "Prediction win — market #$marketId");
                        $db->prepare("UPDATE prediction_bets SET status = 'won', payout_gsm = ?, settled_at = NOW() WHERE id = ?")->execute([$payout, $bet['id']]);
                    } else {
                        $db->prepare("UPDATE prediction_bets SET status = 'lost', settled_at = NOW() WHERE id = ?")->execute([$bet['id']]);
                    }
                }
            }

            echo json_encode(['success' => true, 'market_id' => $marketId, 'outcome' => $outcome]);
            break;

        case 'create_market':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
            if (!isCommander()) { echo json_encode(['error' => 'Commander only']); break; }

            $title = trim($input['title'] ?? '');
            $description = trim($input['description'] ?? '');
            $category = trim($input['category'] ?? 'general');
            $yesLabel = trim($input['yes_label'] ?? 'Yes');
            $noLabel = trim($input['no_label'] ?? 'No');
            $closesAt = $input['closes_at'] ?? null;

            if (strlen($title) < 10) { echo json_encode(['error' => 'Title must be at least 10 characters']); break; }

            $db->prepare("INSERT INTO prediction_markets (title, description, category, outcome_yes_label, outcome_no_label, closes_at, creator_id) VALUES (?,?,?,?,?,?,?)")
                ->execute([$title, $description, $category, $yesLabel, $noLabel, $closesAt, 33]);

            echo json_encode(['success' => true, 'market_id' => (int)$db->lastInsertId()]);
            break;

        case 'categories':
            $stmt = $db->query("SELECT category, COUNT(*) AS cnt FROM prediction_markets WHERE status IN ('active','closed') GROUP BY category ORDER BY cnt DESC");
            echo json_encode(['success' => true, 'categories' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'leaderboard':
            $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
            $stmt = $db->prepare("SELECT pb.client_id, c.firstname, COUNT(*) AS total_bets, SUM(CASE WHEN pb.status='won' THEN 1 ELSE 0 END) AS wins, SUM(pb.payout_gsm) AS total_won, ROUND(SUM(CASE WHEN pb.status='won' THEN 1 ELSE 0 END)/GREATEST(COUNT(*),1)*100,1) AS accuracy FROM prediction_bets pb JOIN tblclients c ON c.id = pb.client_id WHERE pb.status IN ('won','lost') GROUP BY pb.client_id ORDER BY wins DESC, accuracy DESC LIMIT ?");
            dbExecute($stmt, [$limit]);
            $leaders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($leaders as &$l) { $l['name'] = htmlspecialchars($l['firstname'] . '.', ENT_QUOTES, 'UTF-8'); unset($l['firstname']); }
            echo json_encode(['success' => true, 'leaderboard' => $leaders]);
            break;

        default:
            echo json_encode(['error' => 'Unknown action', 'valid' => ['list_markets','market_detail','place_bet','my_bets','resolve','create_market','categories','leaderboard']]);
    }
} catch (Exception $e) {
    error_log("Predictions API error: " . $e->getMessage());
    echo json_encode(['error' => 'Internal error']);
}
