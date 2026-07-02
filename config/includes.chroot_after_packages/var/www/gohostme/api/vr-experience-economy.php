<?php
/**
 * VR Experience Economy API
 * ══════════════════════════════════════════════════════════════
 * Tipping, tickets, premium access for non-game VR experiences.
 * Supports: concert, dj-studio, gallery, lounge, sanctuary,
 *           speed-dating, office, commander-tour, circuit-lab
 *
 * Actions:
 *   tip              — Send GSM tip to an experience/performer
 *   buy_ticket        — Purchase entry ticket for event
 *   my_tickets        — List user's purchased tickets
 *   unlock_premium    — Unlock premium content/features
 *   my_unlocks        — List user's premium unlocks
 *   experience_stats  — Revenue stats for an experience
 *   tip_leaderboard   — Top tippers across experiences
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

apiRateLimit(30, 60, 'vr-experience-econ');
session_start();

$client_id = $_SESSION['client_id'] ?? null;
$action = $_REQUEST['action'] ?? '';
$db = getDB();
if (!$db) { echo json_encode(['error' => 'Database unavailable']); exit; }

// ── Schema ──────────────────────────────────────────────────
$db->exec("CREATE TABLE IF NOT EXISTS vr_tips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    experience VARCHAR(60) NOT NULL,
    performer VARCHAR(120) DEFAULT NULL,
    amount_gsm DECIMAL(18,8) NOT NULL,
    message VARCHAR(300) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_exp (experience),
    INDEX idx_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS vr_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    experience VARCHAR(60) NOT NULL,
    event_name VARCHAR(200) NOT NULL,
    ticket_type ENUM('general','vip','backstage') DEFAULT 'general',
    price_gsm DECIMAL(18,8) NOT NULL,
    status ENUM('active','used','expired','refunded') DEFAULT 'active',
    event_date DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_ticket (client_id, experience, event_name, ticket_type),
    INDEX idx_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS vr_premium_unlocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    experience VARCHAR(60) NOT NULL,
    feature_key VARCHAR(100) NOT NULL,
    price_gsm DECIMAL(18,8) NOT NULL,
    unlocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_unlock (client_id, experience, feature_key),
    INDEX idx_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Helpers ─────────────────────────────────────────────────
$VALID_EXPERIENCES = ['concert','dj-studio','gallery','lounge','sanctuary','speed-dating','office','commander-tour','circuit-lab','kingdom','hub'];

function requireAuth(): int {
    global $client_id;
    if (!$client_id) { echo json_encode(['error' => 'Login required']); exit; }
    return (int)$client_id;
}

function gsmBalance(PDO $db, int $uid): float {
    $s = $db->prepare("SELECT balance FROM gsm_balances WHERE user_id = ?");
    $s->execute([$uid]);
    return (float)($s->fetchColumn() ?: 0);
}

function gsmDebit(PDO $db, int $uid, float $amount, string $desc, string $refType, int $refId): bool {
    $upd = $db->prepare("UPDATE gsm_balances SET balance = balance - ? WHERE user_id = ? AND balance >= ?");
    $upd->execute([$amount, $uid, $amount]);
    if ($upd->rowCount() === 0) return false;
    $db->prepare("INSERT INTO gsm_transactions (user_id, type, amount, description, reference_type, reference_id) VALUES (?, 'debit', ?, ?, ?, ?)")
       ->execute([$uid, $amount, $desc, $refType, $refId]);
    return true;
}

function platformFee(PDO $db, float $amount, float $rate = 0.03): float {
    $fee = round($amount * $rate, 8);
    if ($fee > 0) {
        $db->exec("INSERT INTO gsm_balances (user_id, balance) VALUES (0, $fee) ON DUPLICATE KEY UPDATE balance = balance + $fee");
    }
    return $fee;
}

// ── Action Router ───────────────────────────────────────────
switch ($action) {

    case 'tip':
        $uid = requireAuth();
        requireCSRF();
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $experience = $input['experience'] ?? '';
        $amount = (float)($input['amount'] ?? 0);
        $performer = substr($input['performer'] ?? '', 0, 120);
        $message = substr($input['message'] ?? '', 0, 300);

        if (!in_array($experience, $VALID_EXPERIENCES)) { echo json_encode(['error' => 'Invalid experience']); break; }
        if ($amount < 0.1 || $amount > 10000) { echo json_encode(['error' => 'Tip must be 0.1–10,000 GSM']); break; }

        $db->beginTransaction();
        try {
            if (!gsmDebit($db, $uid, $amount, "Tip in $experience" . ($performer ? " for $performer" : ''), 'vr_tip', 0)) {
                $db->rollBack();
                echo json_encode(['error' => 'Insufficient GSM balance', 'balance' => gsmBalance($db, $uid)]);
                break;
            }
            $s = $db->prepare("INSERT INTO vr_tips (client_id, experience, performer, amount_gsm, message) VALUES (?, ?, ?, ?, ?)");
            $s->execute([$uid, $experience, $performer ?: null, $amount, $message]);
            $tipId = (int)$db->lastInsertId();
            // Update reference
            $db->prepare("UPDATE gsm_transactions SET reference_id = ? WHERE reference_type = 'vr_tip' AND reference_id = 0 AND user_id = ? ORDER BY id DESC LIMIT 1")->execute([$tipId, $uid]);
            platformFee($db, $amount, 0.05);
            $db->commit();
            echo json_encode(['success' => true, 'tip_id' => $tipId, 'new_balance' => gsmBalance($db, $uid)]);
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['error' => 'Tip failed']);
        }
        break;

    case 'buy_ticket':
        $uid = requireAuth();
        requireCSRF();
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $experience = $input['experience'] ?? '';
        $eventName = substr($input['event_name'] ?? '', 0, 200);
        $ticketType = $input['ticket_type'] ?? 'general';
        $eventDate = $input['event_date'] ?? null;

        if (!in_array($experience, $VALID_EXPERIENCES)) { echo json_encode(['error' => 'Invalid experience']); break; }
        if (!in_array($ticketType, ['general','vip','backstage'])) { echo json_encode(['error' => 'Invalid ticket type']); break; }
        if (!$eventName) { echo json_encode(['error' => 'event_name required']); break; }

        $prices = ['general' => 5, 'vip' => 25, 'backstage' => 100];
        $price = $prices[$ticketType];

        $db->beginTransaction();
        try {
            // Check duplicate
            $dup = $db->prepare("SELECT 1 FROM vr_tickets WHERE client_id = ? AND experience = ? AND event_name = ? AND ticket_type = ?");
            $dup->execute([$uid, $experience, $eventName, $ticketType]);
            if ($dup->fetchColumn()) { $db->rollBack(); echo json_encode(['error' => 'Already purchased']); break; }

            if (!gsmDebit($db, $uid, $price, "Ticket: $ticketType for $eventName ($experience)", 'vr_ticket', 0)) {
                $db->rollBack();
                echo json_encode(['error' => 'Insufficient GSM balance']);
                break;
            }
            $s = $db->prepare("INSERT INTO vr_tickets (client_id, experience, event_name, ticket_type, price_gsm, event_date) VALUES (?, ?, ?, ?, ?, ?)");
            $s->execute([$uid, $experience, $eventName, $ticketType, $price, $eventDate]);
            $ticketId = (int)$db->lastInsertId();
            $db->prepare("UPDATE gsm_transactions SET reference_id = ? WHERE reference_type = 'vr_ticket' AND reference_id = 0 AND user_id = ? ORDER BY id DESC LIMIT 1")->execute([$ticketId, $uid]);
            platformFee($db, $price, 0.03);
            $db->commit();
            echo json_encode(['success' => true, 'ticket_id' => $ticketId, 'ticket_type' => $ticketType, 'price' => $price, 'new_balance' => gsmBalance($db, $uid)]);
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['error' => 'Purchase failed']);
        }
        break;

    case 'my_tickets':
        $uid = requireAuth();
        $exp = $_GET['experience'] ?? null;
        $sql = "SELECT id, experience, event_name, ticket_type, price_gsm, status, event_date, created_at FROM vr_tickets WHERE client_id = ?";
        $params = [$uid];
        if ($exp && in_array($exp, $VALID_EXPERIENCES)) { $sql .= " AND experience = ?"; $params[] = $exp; }
        $sql .= " ORDER BY created_at DESC LIMIT 50";
        $s = $db->prepare($sql); $s->execute($params);
        echo json_encode(['success' => true, 'tickets' => $s->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'unlock_premium':
        $uid = requireAuth();
        requireCSRF();
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $experience = $input['experience'] ?? '';
        $featureKey = preg_replace('/[^a-z0-9_\-]/', '', strtolower($input['feature_key'] ?? ''));
        $price = (float)($input['price'] ?? 10);

        if (!in_array($experience, $VALID_EXPERIENCES)) { echo json_encode(['error' => 'Invalid experience']); break; }
        if (!$featureKey) { echo json_encode(['error' => 'feature_key required']); break; }
        if ($price < 1 || $price > 5000) { echo json_encode(['error' => 'Price out of range']); break; }

        $db->beginTransaction();
        try {
            $dup = $db->prepare("SELECT 1 FROM vr_premium_unlocks WHERE client_id = ? AND experience = ? AND feature_key = ?");
            $dup->execute([$uid, $experience, $featureKey]);
            if ($dup->fetchColumn()) { $db->rollBack(); echo json_encode(['error' => 'Already unlocked']); break; }

            if (!gsmDebit($db, $uid, $price, "Premium unlock: $featureKey ($experience)", 'vr_premium', 0)) {
                $db->rollBack();
                echo json_encode(['error' => 'Insufficient GSM balance']);
                break;
            }
            $s = $db->prepare("INSERT INTO vr_premium_unlocks (client_id, experience, feature_key, price_gsm) VALUES (?, ?, ?, ?)");
            $s->execute([$uid, $experience, $featureKey, $price]);
            platformFee($db, $price, 0.03);
            $db->commit();
            echo json_encode(['success' => true, 'unlocked' => $featureKey, 'new_balance' => gsmBalance($db, $uid)]);
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['error' => 'Unlock failed']);
        }
        break;

    case 'my_unlocks':
        $uid = requireAuth();
        $s = $db->prepare("SELECT experience, feature_key, price_gsm, unlocked_at FROM vr_premium_unlocks WHERE client_id = ? ORDER BY unlocked_at DESC");
        $s->execute([$uid]);
        echo json_encode(['success' => true, 'unlocks' => $s->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'experience_stats':
        $exp = $_GET['experience'] ?? '';
        if (!in_array($exp, $VALID_EXPERIENCES)) { echo json_encode(['error' => 'Invalid experience']); break; }
        $tips = $db->prepare("SELECT COUNT(*) as tip_count, COALESCE(SUM(amount_gsm),0) as total_tips FROM vr_tips WHERE experience = ?");
        $tips->execute([$exp]);
        $tipData = $tips->fetch(PDO::FETCH_ASSOC);
        $tickets = $db->prepare("SELECT COUNT(*) as ticket_count, COALESCE(SUM(price_gsm),0) as total_ticket_revenue FROM vr_tickets WHERE experience = ?");
        $tickets->execute([$exp]);
        $ticketData = $tickets->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'experience' => $exp, 'stats' => array_merge($tipData, $ticketData)]);
        break;

    case 'tip_leaderboard':
        $exp = $_GET['experience'] ?? null;
        $sql = "SELECT t.client_id, COALESCE(c.firstname,'Anon') as name, COUNT(*) as tip_count, SUM(t.amount_gsm) as total_tipped FROM vr_tips t LEFT JOIN tblclients c ON c.id = t.client_id";
        $params = [];
        if ($exp && in_array($exp, $VALID_EXPERIENCES)) { $sql .= " WHERE t.experience = ?"; $params[] = $exp; }
        $sql .= " GROUP BY t.client_id ORDER BY total_tipped DESC LIMIT 30";
        $s = $db->prepare($sql); $s->execute($params);
        echo json_encode(['success' => true, 'leaderboard' => $s->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action', 'actions' => ['tip','buy_ticket','my_tickets','unlock_premium','my_unlocks','experience_stats','tip_leaderboard']]);
}
