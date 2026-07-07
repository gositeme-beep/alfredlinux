<?php
/**
 * Alfred Vapi Authentication Webhook
 * Verifies a caller's identity using email + 4-digit PIN
 * Falls back to email + last 4 of phone if no PIN is set
 *
 * POST /api/vapi-auth.php
 */

define('GOSITEME_API', true);
$GLOBALS['CSRF_EXEMPT'] = true; // VAPI server-secret verification
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

// CORS not needed for server-to-server VAPI endpoints
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['result' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// VAPI webhook secret verification
$vapiSecret = getenv("VAPI_WEBHOOK_SECRET");
if ($vapiSecret) {
    $receivedSecret = $_SERVER["HTTP_X_VAPI_SECRET"] ?? "";
    if (!hash_equals($vapiSecret, $receivedSecret)) {
        error_log("VAPI auth: Invalid secret from " . ($_SERVER["REMOTE_ADDR"] ?? "unknown"));
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized"]);
        exit;
    }
}

// Parse Vapi tool call format
$input = json_decode(file_get_contents('php://input'), true);

if (isset($input['message']['toolCalls'])) {
    $args      = $input['message']['toolCalls'][0]['function']['arguments'] ?? [];
    $toolCallId = $input['message']['toolCalls'][0]['id'] ?? null;
} else {
    $args       = $input;
    $toolCallId = null;
}

$email = strtolower(trim($args['email'] ?? ''));
$pin   = trim($args['pin'] ?? '');
$phone = preg_replace('/\D/', '', $args['phone'] ?? '');

// Need at least an email
if (empty($email)) {
    echo json_encode(vapiResponse($toolCallId, [
        'authenticated' => false,
        'message'       => 'I need your email address to look up your account. What email did you use to sign up?'
    ]));
    exit;
}

$db = getDB();
if (!$db) {
    echo json_encode(vapiResponse($toolCallId, [
        'authenticated' => false,
        'message'       => 'I am having trouble accessing account information right now. I will have our team call you back within 24 hours.'
    ]));
    exit;
}

try {
    // Look up account by email
    $stmt = $db->prepare("
        SELECT id, firstname, lastname, email, phonenumber, status, support_pin, support_pin_set_at
        FROM clients
        WHERE email = :email
        AND status = 'Active'
        LIMIT 1
    ");
    $stmt->execute([':email' => $email]);
    $client = $stmt->fetch();

    if (!$client) {
        echo json_encode(vapiResponse($toolCallId, [
            'authenticated' => false,
            'message'       => 'I could not find an active account with that email address. Could you double-check the spelling? Or I can have our team call you back.'
        ]));
        exit;
    }

    // --- Verify identity ---
    $verified = false;
    $method   = '';

    if (!empty($client['support_pin'])) {
        // Customer HAS a PIN set — require PIN
        if (empty($pin)) {
            // Ask for PIN
            echo json_encode(vapiResponse($toolCallId, [
                'authenticated' => false,
                'needs_pin'     => true,
                'message'       => 'I found your account! For security, can you give me your 4-digit support PIN?'
            ]));
            exit;
        }
        if (password_verify($pin, $client['support_pin'])) {
            $verified = true;
            $method   = 'pin';
        } else {
            echo json_encode(vapiResponse($toolCallId, [
                'authenticated' => false,
                'message'       => 'That PIN does not match. Please try again or I can have our team call you back to verify your identity.'
            ]));
            exit;
        }
    } else {
        // No PIN set — fall back to last 4 digits of phone
        if (empty($phone)) {
            echo json_encode(vapiResponse($toolCallId, [
                'authenticated' => false,
                'needs_phone'   => true,
                'message'       => 'I found your account! Since you have not set up a support PIN yet, can you give me the last 4 digits of your phone number on file?'
            ]));
            exit;
        }
        $last4       = substr($phone, -4);
        $storedPhone = preg_replace('/\D/', '', $client['phone']);
        if (str_ends_with($storedPhone, $last4)) {
            $verified = true;
            $method   = 'phone_last4';
        } else {
            echo json_encode(vapiResponse($toolCallId, [
                'authenticated' => false,
                'message'       => 'Those digits do not match what we have on file. You can also set up a 4-digit support PIN in your GoSiteMe dashboard for easier verification next time. Shall I have our team call you back?'
            ]));
            exit;
        }
    }

    // --- Authenticated! Fetch account details ---
    if ($verified) {
        // Active services
        $stmtServices = $db->prepare("
            SELECT s.domain, p.name as plan_name, s.next_due_date, s.status
            FROM services s
            JOIN products p ON s.product_id = p.id
            WHERE s.client_id = :uid AND s.status = 'Active'
            ORDER BY s.next_due_date ASC
            LIMIT 5
        ");
        $stmtServices->execute([':uid' => $client['id']]);
        $services = $stmtServices->fetchAll();

        // Unpaid invoices
        $stmtInv = $db->prepare("
            SELECT COUNT(*) as unpaid_count, COALESCE(SUM(total), 0) as unpaid_total
            FROM invoices
            WHERE userid = :uid AND status = 'Unpaid'
        ");
        $stmtInv->execute([':uid' => $client['id']]);
        $invoices = $stmtInv->fetch();

        $serviceList = array_map(fn($s) => $s['domain'] . ' (' . $s['plan_name'] . ')', $services);

        $hasPinSet = !empty($client['support_pin']);
        $pinNudge  = $hasPinSet ? '' : ' By the way, you can set up a 4-digit support PIN in your GoSiteMe dashboard for even faster verification next time!';

        echo json_encode(vapiResponse($toolCallId, [
            'authenticated'   => true,
            'client_id'       => $client['id'],
            'name'            => $client['firstname'] . ' ' . $client['lastname'],
            'first_name'      => $client['firstname'],
            'email'           => $client['email'],
            'auth_method'     => $method,
            'has_support_pin' => $hasPinSet,
            'active_services' => $serviceList,
            'unpaid_invoices' => (int)$invoices['unpaid_count'],
            'unpaid_total'    => number_format((float)$invoices['unpaid_total'], 2),
            'message'         => 'Identity verified! Welcome, ' . $client['firstname'] . '!' . $pinNudge
        ]));
    }

} catch (Exception $e) {
    error_log('Vapi Auth Error: ' . $e->getMessage());
    echo json_encode(vapiResponse($toolCallId, [
        'authenticated' => false,
        'message'       => 'I ran into a technical issue. I will have our team follow up with you within 24 hours.'
    ]));
}

function vapiResponse($toolCallId, $data) {
    if ($toolCallId) {
        return [
            'results' => [[
                'toolCallId' => $toolCallId,
                'result'     => json_encode($data)
            ]]
        ];
    }
    return $data;
}
