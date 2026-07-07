<?php
/**
 * Support PIN API
 * Works with GoSiteMe sessions
 *
 * POST /api/support-pin.php  { "action": "set",    "pin": "1234", "client_id": 123 }
 * POST /api/support-pin.php  { "action": "remove",                "client_id": 123 }
 * POST /api/support-pin.php  { "action": "status",                "client_id": 123 }
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

// 3. Client ID passed from JS (window.GOSITEME_CLIENT_ID)
//    Only trusted if it matches a valid active client
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
        echo json_encode(['success' => true, 'message' => '✅ Support PIN saved! Alfred will use this to verify you on your next call to 1-833-GOSITEME.']);
        break;

    case 'remove':
        $stmt = $db->prepare("UPDATE clients SET support_pin = NULL, support_pin_set_at = NULL WHERE id = :id");
        $stmt->execute([':id' => $clientId]);
        echo json_encode(['success' => true, 'message' => 'Support PIN removed. Alfred will use your phone number to verify you instead.']);
        break;

    case 'status':
        $stmt = $db->prepare("SELECT support_pin, support_pin_set_at FROM clients WHERE id = :id");
        $stmt->execute([':id' => $clientId]);
        $row = $stmt->fetch();
        echo json_encode(['success' => true, 'has_pin' => !empty($row['support_pin']), 'set_at' => $row['support_pin_set_at'] ?? null]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}
