<?php
/**
 * Alfred IDE — Desktop Pairing API
 *
 * Allows desktop Alfred IDE (Windows/Linux) to authenticate with a GoSiteMe account.
 *
 * Flow:
 *   1. Desktop calls POST /api/alfred-ide-pair.php?action=request  → gets pairing_code + pair_id
 *   2. User visits https://gositeme.com/alfred-ide-pair.php?code=XXXX  → logs in and confirms
 *   3. Desktop polls  GET /api/alfred-ide-pair.php?action=poll&pair_id=xxx → gets token when approved
 *
 * The token is the same format as web session tokens (sha256 stored in DB).
 */

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Alfred-IDE-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../includes/db-config.inc.php';

$db = getSharedDB();

// Ensure pairing table exists
$db->exec("CREATE TABLE IF NOT EXISTS alfred_ide_pairing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pair_id VARCHAR(64) NOT NULL UNIQUE,
    pairing_code VARCHAR(8) NOT NULL,
    device_name VARCHAR(255) DEFAULT 'Desktop',
    status ENUM('pending','approved','expired') DEFAULT 'pending',
    user_id INT DEFAULT NULL,
    raw_token VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    approved_at DATETIME DEFAULT NULL,
    expires_at DATETIME DEFAULT NULL,
    INDEX idx_code (pairing_code),
    INDEX idx_pair_id (pair_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$action = strtolower(trim($_GET['action'] ?? ''));

// ============================================================
// ACTION: request — Desktop requests a pairing code
// ============================================================
if ($action === 'request' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $deviceName = trim(substr($input['device_name'] ?? 'Desktop', 0, 255));

    // Generate a 6-char uppercase code (easy to type)
    $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
    $pairId = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', time() + 600); // 10 minutes

    // Clean expired pairings
    $db->exec("DELETE FROM alfred_ide_pairing WHERE expires_at < NOW() AND status = 'pending'");

    $stmt = $db->prepare("INSERT INTO alfred_ide_pairing (pair_id, pairing_code, device_name, status, expires_at) VALUES (?, ?, ?, 'pending', ?)");
    $stmt->execute([$pairId, $code, $deviceName, $expiresAt]);

    echo json_encode([
        'success'      => true,
        'pair_id'      => $pairId,
        'pairing_code' => $code,
        'expires_in'   => 600,
        'pair_url'     => "https://gositeme.com/alfred-ide-pair.php?code=$code",
        'instructions' => "Open the URL above or go to gositeme.com/alfred-ide-pair.php and enter code: $code",
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// ============================================================
// ACTION: poll — Desktop polls for approval
// ============================================================
if ($action === 'poll') {
    $pairId = trim($_GET['pair_id'] ?? '');
    if ($pairId === '' || strlen($pairId) !== 64) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid pair_id']);
        exit;
    }

    $stmt = $db->prepare("SELECT status, raw_token, user_id, approved_at, expires_at FROM alfred_ide_pairing WHERE pair_id = ? LIMIT 1");
    $stmt->execute([$pairId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        echo json_encode(['error' => 'Pairing not found']);
        exit;
    }

    if ($row['status'] === 'approved' && $row['raw_token']) {
        // Return token and clean up
        $token = $row['raw_token'];
        $userId = (int)$row['user_id'];

        // Clear the raw token from pairing table (one-time read)
        $db->prepare("UPDATE alfred_ide_pairing SET raw_token = NULL WHERE pair_id = ?")->execute([$pairId]);

        // Look up user info
        $userStmt = $db->prepare("SELECT id, email, display_name, google_name, google_avatar FROM alfred_ide_users WHERE id = ? LIMIT 1");
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'approved',
            'token'  => $token,
            'user'   => $user ? [
                'id'     => (int)$user['id'],
                'email'  => $user['email'],
                'name'   => $user['display_name'] ?: $user['google_name'],
                'avatar' => $user['google_avatar'],
            ] : null,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if (strtotime($row['expires_at']) < time()) {
        $db->prepare("UPDATE alfred_ide_pairing SET status = 'expired' WHERE pair_id = ?")->execute([$pairId]);
        echo json_encode(['status' => 'expired']);
        exit;
    }

    echo json_encode(['status' => 'pending', 'retry_in' => 3]);
    exit;
}

// ============================================================
// ACTION: approve — Web page confirms pairing (requires logged-in user)
// ============================================================
if ($action === 'approve' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $code = strtoupper(trim($input['code'] ?? ''));
    $userId = (int)($input['user_id'] ?? 0);

    if ($code === '' || $userId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing code or user_id']);
        exit;
    }

    $stmt = $db->prepare("SELECT id, pair_id, status, expires_at FROM alfred_ide_pairing WHERE pairing_code = ? AND status = 'pending' ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$code]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        http_response_code(404);
        echo json_encode(['error' => 'Code not found or already used']);
        exit;
    }

    if (strtotime($row['expires_at']) < time()) {
        $db->prepare("UPDATE alfred_ide_pairing SET status = 'expired' WHERE id = ?")->execute([$row['id']]);
        http_response_code(410);
        echo json_encode(['error' => 'Code expired']);
        exit;
    }

    // Generate a new session token for desktop
    $rawToken = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $rawToken);
    $expires = date('Y-m-d H:i:s', time() + 90 * 86400); // 90 days for desktop

    // Update user's session token
    $upd = $db->prepare("UPDATE alfred_ide_users SET session_token = ?, token_expires = ? WHERE id = ?");
    $upd->execute([$tokenHash, $expires, $userId]);

    // Mark pairing approved with one-time readable raw token
    $appr = $db->prepare("UPDATE alfred_ide_pairing SET status = 'approved', user_id = ?, raw_token = ?, approved_at = NOW() WHERE id = ?");
    $appr->execute([$userId, $rawToken, $row['id']]);

    echo json_encode(['success' => true, 'paired' => true]);
    exit;
}

// Default
http_response_code(400);
echo json_encode(['error' => 'Unknown action. Use: request, poll, approve']);
