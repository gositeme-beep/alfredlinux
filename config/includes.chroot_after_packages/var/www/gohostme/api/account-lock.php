<?php
/**
 * Account Lock API — Self-Service Account Protection
 * ───────────────────────────────────────────────────
 * Users can lock their own account for security. Unlocking requires:
 *   - Audio voice passphrase (Whisper transcription + phrase matching)
 *   - OR email verification code
 *   - OR admin/Veil override
 *
 * Endpoints:
 *   POST ?action=lock          Lock the authenticated user's account
 *   POST ?action=unlock_voice  Unlock via voice passphrase (audio upload)
 *   POST ?action=unlock_code   Unlock via emailed verification code
 *   POST ?action=set_phrase    Set/update the voice unlock passphrase
 *   GET  ?action=status        Check lock status
 *   POST ?action=admin_unlock  Admin/Veil force unlock (admin only)
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/veil-protocol.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$db = getDB();
if (!$db) { echo json_encode(['error' => 'Service unavailable']); exit; }

// Ensure schema
$db->exec("CREATE TABLE IF NOT EXISTS account_locks (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    client_id       INT UNSIGNED NOT NULL UNIQUE,
    locked_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    unlock_phrase   VARCHAR(255) DEFAULT NULL COMMENT 'hashed voice passphrase',
    unlock_code     VARCHAR(10) DEFAULT NULL COMMENT 'temporary email code',
    code_expires    DATETIME DEFAULT NULL,
    unlock_attempts INT DEFAULT 0,
    max_attempts    INT DEFAULT 5,
    reason          VARCHAR(255) DEFAULT 'self_lock',
    ip_locked       VARCHAR(45) DEFAULT NULL,
    INDEX idx_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$isLoggedIn = !empty($_SESSION['logged_in']) && !empty($_SESSION['client_id']);
$clientId = $isLoggedIn ? (int)$_SESSION['client_id'] : 0;
$isAdmin = !empty($_SESSION['is_admin']) || $clientId === 33;
$isVeil = veil_is_active();

// ─── Lock Account ──────────────────────────────────────────────────────
if ($action === 'lock' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isLoggedIn) { echo json_encode(['error' => 'Authentication required']); exit; }

    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $reason = mb_substr(trim($input['reason'] ?? 'self_lock'), 0, 255);

    // Check if already locked
    $stmt = $db->prepare("SELECT id FROM account_locks WHERE client_id = ?");
    $stmt->execute([$clientId]);
    if ($stmt->fetch()) {
        echo json_encode(['error' => 'Account is already locked']);
        exit;
    }

    // Get or set voice passphrase
    $phrase = trim($input['voice_phrase'] ?? '');
    $hashedPhrase = $phrase ? password_hash(strtolower($phrase), PASSWORD_BCRYPT) : null;

    $stmt = $db->prepare("INSERT INTO account_locks (client_id, locked_at, unlock_phrase, reason, ip_locked) VALUES (?, NOW(), ?, ?, ?)");
    $stmt->execute([$clientId, $hashedPhrase, $reason, $_SERVER['REMOTE_ADDR'] ?? '']);

    // Generate email unlock code
    $code = sprintf('%06d', random_int(100000, 999999));
    $codeExpiry = date('Y-m-d H:i:s', time() + 3600); // 1 hour
    $db->prepare("UPDATE account_locks SET unlock_code = ?, code_expires = ? WHERE client_id = ?")->execute([
        password_hash($code, PASSWORD_BCRYPT), $codeExpiry, $clientId
    ]);

    // Send unlock code via email
    $email = '';
    $stmt = $db->prepare("SELECT email, firstname FROM clients WHERE id = ?");
    $stmt->execute([$clientId]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($client && $client['email']) {
        $email = $client['email'];
        $name = $client['firstname'] ?? 'User';
        // Use SendGrid or mail()
        $subject = 'GoSiteMe Account Lock Confirmation — Unlock Code Inside';
        $body = "Hi $name,\n\nYour account has been locked as requested.\n\nYour unlock code: $code\n(Valid for 1 hour)\n\nTo unlock:\n- Use your voice passphrase on the unlock page\n- Or enter this code\n- Or contact support\n\nIf you did not lock your account, contact us immediately at 1-833-GOSITEME.\n\n— Alfred Security Team";
        @mail($email, $subject, $body, "From: security@gositeme.com\r\nContent-Type: text/plain; charset=utf-8");
    }

    // Log the lock
    $db->prepare("INSERT INTO activity_log (date, description, user, ipaddr) VALUES (NOW(), ?, ?, ?)")->execute([
        'Account self-locked' . ($reason !== 'self_lock' ? ": $reason" : ''),
        $client['email'] ?? "client:$clientId",
        $_SERVER['REMOTE_ADDR'] ?? ''
    ]);

    // Destroy session (force logout)
    $_SESSION = [];
    session_destroy();

    echo json_encode([
        'success' => true,
        'message' => 'Account locked. Unlock code sent to your email.',
        'has_voice_phrase' => !empty($hashedPhrase),
        'email_masked' => $email ? substr($email, 0, 3) . '***@' . explode('@', $email)[1] : null,
    ]);
    exit;
}

// ─── Unlock via Voice ──────────────────────────────────────────────────
if ($action === 'unlock_voice' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $targetEmail = mb_substr(trim($input['email'] ?? ''), 0, 255);

    if (!$targetEmail) { echo json_encode(['error' => 'Email required']); exit; }

    // Find the client
    $stmt = $db->prepare("SELECT id FROM clients WHERE email = ?");
    $stmt->execute([$targetEmail]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$client) { echo json_encode(['error' => 'Account not found']); exit; }

    $targetId = (int)$client['id'];

    // Check lock exists
    $stmt = $db->prepare("SELECT * FROM account_locks WHERE client_id = ?");
    $stmt->execute([$targetId]);
    $lock = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$lock) { echo json_encode(['error' => 'Account is not locked']); exit; }

    // Check max attempts
    if ((int)$lock['unlock_attempts'] >= (int)$lock['max_attempts']) {
        echo json_encode(['error' => 'Too many unlock attempts. Contact support at 1-833-GOSITEME.']);
        exit;
    }

    // Voice input: either transcribed text or audio file
    $spokenPhrase = '';

    // Option A: Audio file uploaded — transcribe it
    if (!empty($_FILES['audio'])) {
        require_once __DIR__ . '/alfred-chat.php';
        $spokenPhrase = transcribeAudio($_FILES['audio']['tmp_name'], $_FILES['audio']['type'] ?? 'audio/webm');
    }
    // Option B: Pre-transcribed text sent directly
    if (empty($spokenPhrase) && !empty($input['phrase'])) {
        $spokenPhrase = mb_substr(trim($input['phrase']), 0, 500);
    }

    if (!$spokenPhrase) {
        echo json_encode(['error' => 'Voice phrase could not be understood. Try again clearly.']);
        exit;
    }

    // Verify against stored phrase
    if (empty($lock['unlock_phrase'])) {
        echo json_encode(['error' => 'No voice phrase set. Use email code to unlock.']);
        exit;
    }

    $db->prepare("UPDATE account_locks SET unlock_attempts = unlock_attempts + 1 WHERE client_id = ?")->execute([$targetId]);

    if (password_verify(strtolower(trim($spokenPhrase)), $lock['unlock_phrase'])) {
        // Success — unlock account
        $db->prepare("DELETE FROM account_locks WHERE client_id = ?")->execute([$targetId]);
        $db->prepare("INSERT INTO activity_log (date, description, user, ipaddr) VALUES (NOW(), ?, ?, ?)")->execute([
            'Account unlocked via voice passphrase', "client:$targetId", $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
        echo json_encode(['success' => true, 'message' => '🔓 Account unlocked successfully. You can now log in.']);
    } else {
        $remaining = (int)$lock['max_attempts'] - (int)$lock['unlock_attempts'] - 1;
        echo json_encode(['error' => "Voice phrase didn't match. $remaining attempts remaining."]);
    }
    exit;
}

// ─── Unlock via Email Code ─────────────────────────────────────────────
if ($action === 'unlock_code' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $targetEmail = mb_substr(trim($input['email'] ?? ''), 0, 255);
    $code = preg_replace('/\D/', '', $input['code'] ?? '');

    if (!$targetEmail || !$code) { echo json_encode(['error' => 'Email and code required']); exit; }

    $stmt = $db->prepare("SELECT c.id, al.* FROM clients c JOIN account_locks al ON c.id = al.client_id WHERE c.email = ?");
    $stmt->execute([$targetEmail]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) { echo json_encode(['error' => 'No locked account found']); exit; }

    if ((int)$row['unlock_attempts'] >= (int)$row['max_attempts']) {
        echo json_encode(['error' => 'Too many attempts. Contact support.']); exit;
    }

    if ($row['code_expires'] && strtotime($row['code_expires']) < time()) {
        echo json_encode(['error' => 'Code expired. Request a new one.']); exit;
    }

    $db->prepare("UPDATE account_locks SET unlock_attempts = unlock_attempts + 1 WHERE client_id = ?")->execute([$row['client_id']]);

    if (password_verify($code, $row['unlock_code'])) {
        $db->prepare("DELETE FROM account_locks WHERE client_id = ?")->execute([$row['client_id']]);
        $db->prepare("INSERT INTO activity_log (date, description, user, ipaddr) VALUES (NOW(), ?, ?, ?)")->execute([
            'Account unlocked via email code', $targetEmail, $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
        echo json_encode(['success' => true, 'message' => '🔓 Account unlocked. You can now log in.']);
    } else {
        echo json_encode(['error' => 'Invalid code.']);
    }
    exit;
}

// ─── Set Voice Phrase ──────────────────────────────────────────────────
if ($action === 'set_phrase' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isLoggedIn) { echo json_encode(['error' => 'Login required']); exit; }
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $phrase = mb_substr(trim($input['phrase'] ?? ''), 0, 500);
    if (strlen($phrase) < 4) { echo json_encode(['error' => 'Phrase must be at least 4 characters']); exit; }

    $hashed = password_hash(strtolower($phrase), PASSWORD_BCRYPT);

    // Update if lock exists, otherwise store for future use
    $stmt = $db->prepare("SELECT id FROM account_locks WHERE client_id = ?");
    $stmt->execute([$clientId]);
    if ($stmt->fetch()) {
        $db->prepare("UPDATE account_locks SET unlock_phrase = ? WHERE client_id = ?")->execute([$hashed, $clientId]);
    } else {
        // Store phrase in a pre-lock config table
        $db->exec("CREATE TABLE IF NOT EXISTS account_voice_phrases (
            client_id INT UNSIGNED NOT NULL PRIMARY KEY,
            phrase_hash VARCHAR(255) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");
        $db->prepare("INSERT INTO account_voice_phrases (client_id, phrase_hash) VALUES (?, ?)
                      ON DUPLICATE KEY UPDATE phrase_hash = VALUES(phrase_hash)")->execute([$clientId, $hashed]);
    }

    echo json_encode(['success' => true, 'message' => 'Voice unlock phrase updated.']);
    exit;
}

// ─── Check Status ──────────────────────────────────────────────────────
if ($action === 'status') {
    if (!$isLoggedIn && !$isAdmin && !$isVeil) {
        echo json_encode(['error' => 'Auth required']); exit;
    }
    $checkId = $isAdmin ? (int)($_GET['client_id'] ?? $clientId) : $clientId;
    $stmt = $db->prepare("SELECT locked_at, reason, unlock_attempts, max_attempts FROM account_locks WHERE client_id = ?");
    $stmt->execute([$checkId]);
    $lock = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode([
        'locked' => (bool)$lock,
        'details' => $lock ?: null,
    ]);
    exit;
}

// ─── Admin Force Unlock ────────────────────────────────────────────────
if ($action === 'admin_unlock' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isAdmin && !$isVeil) { http_response_code(403); echo json_encode(['error' => 'Admin access required']); exit; }
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $targetId = (int)($input['client_id'] ?? 0);
    if (!$targetId) { echo json_encode(['error' => 'client_id required']); exit; }

    $db->prepare("DELETE FROM account_locks WHERE client_id = ?")->execute([$targetId]);
    $db->prepare("INSERT INTO activity_log (date, description, user, ipaddr) VALUES (NOW(), ?, ?, ?)")->execute([
        "Account force-unlocked by admin (client:$clientId)", "client:$targetId", $_SERVER['REMOTE_ADDR'] ?? ''
    ]);
    echo json_encode(['success' => true, 'message' => "Account $targetId unlocked."]);
    exit;
}

echo json_encode(['error' => 'Unknown action', 'actions' => ['lock', 'unlock_voice', 'unlock_code', 'set_phrase', 'status', 'admin_unlock']]);
