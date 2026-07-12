<?php
/**
 * Alfred Outbound Call API
 * Triggers Alfred to call a customer proactively
 *
 * POST /api/vapi-outbound.php
 * { "action": "call", "client_id": 33, "reason": "unpaid_invoice" }
 * { "action": "call", "phone": "+14504217379", "reason": "domain_expiry", "message": "..." }
 *
 * Actions:
 *  - call_client       Call a specific client by ID
 *  - call_unpaid       Call all clients with unpaid invoices >7 days
 *  - call_expiring     Call clients with domains expiring in 14 days
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json');

// Simple secret key check
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['HTTP_X_API_KEY'] ?? '';
$apiKey     = trim(str_replace('Bearer ', '', $authHeader));
if ($apiKey !== OUTBOUND_SECRET) {
    // Also allow from same server
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!in_array($ip, ['127.0.0.1', '15.235.50.60', '::1'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
}

$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'call_client':    callClient($input);   break;
    case 'call_unpaid':    callUnpaid();          break;
    case 'call_expiring':  callExpiring();        break;
    default:
        echo json_encode(['error' => 'Invalid action. Use: call_client, call_unpaid, call_expiring']);
}


// ═══════════════════════════════════════════════════════════════════════════
// Call a specific client
// ═══════════════════════════════════════════════════════════════════════════
function callClient($input) {
    $clientId = (int)($input['client_id'] ?? 0);
    $phone    = trim($input['phone'] ?? '');
    $reason   = trim($input['reason'] ?? 'support');
    $message  = trim($input['message'] ?? '');

    $db = getDB();

    if ($clientId && !$phone) {
        $s = $db->prepare("SELECT phone, firstname FROM clients WHERE id=:id LIMIT 1");
        $s->execute([':id' => $clientId]);
        $c = $s->fetch();
        if ($c) { $phone = $c['phone']; $name = $c['firstname']; }
    }

    if (!$phone) { echo json_encode(['error' => 'Phone number required']); return; }

    $result = triggerOutboundCall($phone, $reason, $message, $clientId);
    echo json_encode($result);
}


// ═══════════════════════════════════════════════════════════════════════════
// Call all clients with unpaid invoices older than 7 days
// ═══════════════════════════════════════════════════════════════════════════
function callUnpaid() {
    $db = getDB();
    $s  = $db->query("
        SELECT c.id, c.firstname, c.phone,
               COUNT(i.id) as invoice_count,
               SUM(i.total) as total_due
        FROM clients c
        JOIN invoices i ON i.client_id = c.id
        WHERE i.status = 'Unpaid'
        AND i.due_date < DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        AND c.phone != ''
        AND c.status = 'Active'
        GROUP BY c.id
        HAVING total_due > 0
        ORDER BY total_due DESC
        LIMIT 20
    ");
    $clients = $s->fetchAll();

    $results = [];
    foreach ($clients as $client) {
        $msg = "Hi {$client['firstname']}, this is Alfred from GoSiteMe. You have {$client['invoice_count']} unpaid invoice(s) totalling $" . number_format($client['total_due'], 2) . " that are overdue. I am calling to help you get these sorted out quickly.";
        $results[] = triggerOutboundCall($client['phone'], 'unpaid_invoice', $msg, $client['id']);
        sleep(2); // Rate limit
    }
    echo json_encode(['called' => count($results), 'results' => $results]);
}


// ═══════════════════════════════════════════════════════════════════════════
// Call clients with domains expiring in 14 days
// ═══════════════════════════════════════════════════════════════════════════
function callExpiring() {
    $db = getDB();
    $s  = $db->query("
        SELECT c.id, c.firstname, c.phone, d.domain, d.expiry_date
        FROM clients c
        JOIN domains d ON d.client_id = c.id
        WHERE d.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 14 DAY)
        AND d.status = 'Active'
        AND c.phone != ''
        AND c.status = 'Active'
        LIMIT 20
    ");
    $clients = $s->fetchAll();

    $results = [];
    foreach ($clients as $client) {
        $days = ceil((strtotime($client['expiry_date']) - time()) / 86400);
        $msg  = "Hi {$client['firstname']}, this is Alfred from GoSiteMe. Your domain {$client['domain']} is expiring in $days days on {$client['expiry_date']}. I am calling to make sure you do not lose it. I can help you renew it right now.";
        $results[] = triggerOutboundCall($client['phone'], 'domain_expiry', $msg, $client['id']);
        sleep(2);
    }
    echo json_encode(['called' => count($results), 'results' => $results]);
}


// ═══════════════════════════════════════════════════════════════════════════
// Core: trigger a Vapi outbound call
// ═══════════════════════════════════════════════════════════════════════════
function triggerOutboundCall($phone, $reason, $firstMessage = '', $clientId = 0) {
    $VAPI_KEY  = getenv('VAPI_API_KEY') ?: '';
    $ASST_ID   = getenv('VAPI_ASSISTANT_ID') ?: '';
    $PHONE_ID  = getenv('VAPI_PHONE_ID') ?: '';

    // Clean phone number
    $phone = preg_replace('/[^\d+]/', '', $phone);
    if (!str_starts_with($phone, '+')) $phone = '+1' . ltrim($phone, '1');

    // Build first message based on reason
    $messages = [
        'unpaid_invoice' => "Hi, this is Alfred from GoSiteMe calling about an overdue invoice on your account. Is now a good time to talk?",
        'domain_expiry'  => "Hi, this is Alfred from GoSiteMe. I am calling because one of your domain names is about to expire. Do you have a moment?",
        'support'        => "Hi, this is Alfred from GoSiteMe support returning your call. How can I help you today?",
        'callback'       => "Hi, this is Alfred from GoSiteMe. You requested a callback from our support team. I am here to help — how can I assist you today?",
        'welcome'        => "Hi, this is Alfred from GoSiteMe. Welcome to GoSiteMe! I am calling to make sure your account is all set up and to answer any questions you have.",
    ];

    $greeting = $firstMessage ?: ($messages[$reason] ?? $messages['support']);

    $payload = [
        'assistant'  => ['firstMessage' => $greeting],
        'assistantId'=> $ASST_ID,
        'customer'   => ['number' => $phone],
    ];

    if ($PHONE_ID) $payload['phoneNumberId'] = $PHONE_ID;

    $ch = curl_init('https://api.vapi.ai/call/phone');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer $VAPI_KEY", "Content-Type: application/json"],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    $result = $response ? json_decode($response, true) : null;

    // Log the outbound call attempt
    try {
        $db = getDB();
        $db->prepare("INSERT INTO activity_log (date, description, user, ipaddr) VALUES (NOW(),:d,:u,'outbound')")
           ->execute([':d' => "Alfred outbound call triggered — $reason — $phone", ':u' => $phone]);
    } catch (Exception $e) {}

    $errorMsg = null;
    if (!isset($result['id'])) {
        if ($curlErr) {
            $errorMsg = "Connection error: $curlErr";
        } elseif (isset($result['message'])) {
            $errorMsg = is_array($result['message']) ? implode('; ', $result['message']) : $result['message'];
        } elseif (isset($result['error'])) {
            $errorMsg = $result['error'];
        } else {
            $errorMsg = "VAPI returned HTTP $httpCode";
        }
    }

    return [
        'success' => isset($result['id']),
        'call_id' => $result['id'] ?? null,
        'phone'   => $phone,
        'reason'  => $reason,
        'error'   => $errorMsg
    ];
}

