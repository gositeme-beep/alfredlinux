<?php
/**
 * Support PIN API — Rotating One-Time Phone Verification PINs
 * 
 * Like Enom's transfer PIN system: customer generates a 6-digit OTP from their
 * dashboard, tells it to Alfred on the phone, and it dies after one use.
 * Even if wiretapped, the PIN is already dead. No sensitive info ever spoken.
 *
 * POST /api/support-pin.php
 *   { "action": "generate" }         → Generate new 6-digit OTP (shown once)
 *   { "action": "status" }           → Check if active PIN exists
 *   { "action": "revoke" }           → Kill active PIN immediately
 *   { "action": "set", "pin": "..." }→ Legacy: set static 4-digit PIN
 *   { "action": "remove" }           → Legacy: remove static PIN
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

// ── Identify the logged-in client ──────────────────────────────────────────
$clientId = null;

// 1. Custom GoSiteMe session
if (!empty($_SESSION['logged_in']) && !empty($_SESSION['client_id'])) {
    $clientId = (int)$_SESSION['client_id'];
}

// 2. Legacy session (uid key)
if (!$clientId && !empty($_SESSION['uid'])) {
    $clientId = (int)$_SESSION['uid'];
}

// SECURITY: client_id from request body removed (auth bypass risk)
// Only session-authenticated clients can manage their own PIN

if (!$clientId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated. Please log in first.']);
    exit;
}

$action = $input['action'] ?? '';
$db = getDB();
if (!$db) { http_response_code(500); echo json_encode(['success' => false, 'message' => 'Database error']); exit; }

switch ($action) {

    // ══════════════════════════════════════════════════════════════════
    // NEW: Generate a rotating one-time phone PIN (Enom-style)
    // ══════════════════════════════════════════════════════════════════
    case 'generate':
        // Revoke any existing active PINs for this client
        $db->prepare("UPDATE client_phone_pins SET revoked = 1 WHERE client_id = :id AND used_at IS NULL AND revoked = 0")
           ->execute([':id' => $clientId]);

        // Generate cryptographically secure 6-digit PIN
        $pin = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $hash = password_hash($pin, PASSWORD_BCRYPT);
        $expiresMinutes = 15;

        $stmt = $db->prepare("INSERT INTO client_phone_pins (client_id, pin_hash, expires_at) VALUES (:id, :hash, DATE_ADD(NOW(), INTERVAL :mins MINUTE))");
        $stmt->execute([':id' => $clientId, ':hash' => $hash, ':mins' => $expiresMinutes]);

        echo json_encode([
            'success'    => true,
            'pin'        => $pin,   // Shown ONCE — never stored in plaintext
            'expires_in' => $expiresMinutes * 60,
            'expires_at' => gmdate('Y-m-d\TH:i:s\Z', time() + $expiresMinutes * 60),
            'message'    => "Your phone PIN is: $pin — It expires in $expiresMinutes minutes. Call 1-833-GOSITEME and give Alfred only this PIN. Nothing else needed. This PIN can only be used once."
        ]);
        break;

    // ══════════════════════════════════════════════════════════════════
    // Check if customer has an active (unexpired, unused) OTP
    // ══════════════════════════════════════════════════════════════════
    case 'status':
        // Check for active OTP
        $stmt = $db->prepare("SELECT id, created_at, expires_at FROM client_phone_pins WHERE client_id = :id AND used_at IS NULL AND revoked = 0 AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([':id' => $clientId]);
        $activePin = $stmt->fetch();

        // Check for legacy static PIN
        $stmt2 = $db->prepare("SELECT support_pin, support_pin_set_at FROM clients WHERE id = :id");
        $stmt2->execute([':id' => $clientId]);
        $legacy = $stmt2->fetch();

        echo json_encode([
            'success'          => true,
            'has_active_otp'   => !empty($activePin),
            'otp_expires_at'   => $activePin['expires_at'] ?? null,
            'has_legacy_pin'   => !empty($legacy['support_pin']),
            'legacy_set_at'    => $legacy['support_pin_set_at'] ?? null
        ]);
        break;

    // ══════════════════════════════════════════════════════════════════
    // Revoke all active OTPs immediately
    // ══════════════════════════════════════════════════════════════════
    case 'revoke':
        $stmt = $db->prepare("UPDATE client_phone_pins SET revoked = 1 WHERE client_id = :id AND used_at IS NULL AND revoked = 0");
        $stmt->execute([':id' => $clientId]);
        $count = $stmt->rowCount();
        echo json_encode(['success' => true, 'revoked' => $count, 'message' => $count > 0 ? 'Active PIN revoked. Generate a new one when you need to call.' : 'No active PIN to revoke.']);
        break;

    // ══════════════════════════════════════════════════════════════════
    // Legacy: set static 4-digit PIN (kept for backward compat)
    // ══════════════════════════════════════════════════════════════════
    case 'set':
        $pin = trim($input['pin'] ?? '');
        if (!preg_match('/^\d{4}$/', $pin)) {
            echo json_encode(['success' => false, 'message' => 'PIN must be exactly 4 digits.']);
            exit;
        }
        $weak = ['0000','1111','2222','3333','4444','5555','6666','7777','8888','9999','1234','4321','0123','9876'];
        if (in_array($pin, $weak)) {
            echo json_encode(['success' => false, 'message' => 'That PIN is too easy to guess. Please choose something less predictable.']);
            exit;
        }
        $stmt = $db->prepare("UPDATE clients SET support_pin = :pin, support_pin_set_at = NOW() WHERE id = :id");
        $stmt->execute([':pin' => password_hash($pin, PASSWORD_BCRYPT), ':id' => $clientId]);
        echo json_encode(['success' => true, 'message' => 'Static support PIN saved. Consider using the new one-time PIN system instead — it is more secure against wiretapping.']);
        break;

    case 'remove':
        $stmt = $db->prepare("UPDATE clients SET support_pin = NULL, support_pin_set_at = NULL WHERE id = :id");
        $stmt->execute([':id' => $clientId]);
        echo json_encode(['success' => true, 'message' => 'Static support PIN removed.']);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action. Use: generate, status, revoke, set, or remove.']);
}
