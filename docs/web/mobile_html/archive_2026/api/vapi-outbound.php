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
    case 'call_client':             callClient($input);         break;
    case 'call_unpaid':             callUnpaid();                break;
    case 'call_expiring':           callExpiring();              break;
    case 'call_new_signups':        callNewSignups();            break;
    case 'call_scheduled_callbacks':callScheduledCallbacks();    break;
    case 'call_dropped_recovery':   callDroppedRecovery();       break;
    case 'run_all_proactive':       runAllProactive();           break;
    case 'agent_escalation':        agentEscalation($input);     break;
    default:
        echo json_encode(['error' => 'Invalid action. Use: call_client, call_unpaid, call_expiring, call_new_signups, call_scheduled_callbacks, call_dropped_recovery, run_all_proactive, agent_escalation']);
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
// Courtesy call to new signups (created in last 24h, have phone on file)
// ═══════════════════════════════════════════════════════════════════════════
function callNewSignups() {
    $db = getDB();

    // Only call signups from last 24h who haven't been called yet
    $s = $db->query("
        SELECT c.id, c.firstname, c.phone, c.email, c.date_created
        FROM clients c
        WHERE c.date_created >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        AND c.phone != ''
        AND c.status = 'Active'
        AND c.id NOT IN (
            SELECT DISTINCT client_id FROM alfred_call_log
            WHERE client_id > 0
            AND started_at >= DATE_SUB(NOW(), INTERVAL 48 HOUR)
        )
        ORDER BY c.date_created DESC
        LIMIT 10
    ");
    $clients = $s->fetchAll();

    $results = [];
    foreach ($clients as $client) {
        $msg = "Hi {$client['firstname']}, this is Alfred from Go Site Me. Welcome aboard! "
             . "I am calling to make sure your account is all set up and to answer any questions you might have. "
             . "Do you have a moment?";
        $results[] = triggerOutboundCall($client['phone'], 'welcome', $msg, $client['id']);
        sleep(3);
    }
    echo json_encode(['action' => 'call_new_signups', 'called' => count($results), 'results' => $results]);
}


// ═══════════════════════════════════════════════════════════════════════════
// Process scheduled callbacks from post-call intelligence (F1)
// ═══════════════════════════════════════════════════════════════════════════
function callScheduledCallbacks() {
    $db = getDB();

    $s = $db->query("
        SELECT callback_id, caller_number, verified_number, client_id, context_summary
        FROM alfred_callbacks
        WHERE callback_status = 'scheduled'
        AND expires_at > NOW()
        AND requested_at < DATE_SUB(NOW(), INTERVAL 2 HOUR)
        ORDER BY requested_at ASC
        LIMIT 10
    ");
    $callbacks = $s->fetchAll(PDO::FETCH_ASSOC);

    $results = [];
    foreach ($callbacks as $cb) {
        $phone = $cb['verified_number'] ?: $cb['caller_number'];
        if (!$phone) continue;

        $context = $cb['context_summary'] ?? '';
        $msg = "Hi, this is Alfred from Go Site Me following up on our earlier conversation. "
             . ($context ? "I believe we discussed: " . mb_substr($context, 0, 200) . ". " : "")
             . "Is now a good time to continue?";

        $result = triggerOutboundCall($phone, 'callback', $msg, $cb['client_id']);
        $results[] = $result;

        $newStatus = ($result['success'] ?? false) ? 'calling' : 'failed';
        $db->prepare("UPDATE alfred_callbacks SET callback_status = :s, executed_at = NOW(), outbound_call_id = :ocid WHERE callback_id = :id")
           ->execute([':s' => $newStatus, ':ocid' => $result['call_id'] ?? '', ':id' => $cb['callback_id']]);

        sleep(3);
    }
    echo json_encode(['action' => 'call_scheduled_callbacks', 'called' => count($results), 'results' => $results]);
}


// ═══════════════════════════════════════════════════════════════════════════
// Recover dropped calls that auto-recovery missed (manual trigger)
// ═══════════════════════════════════════════════════════════════════════════
function callDroppedRecovery() {
    $db = getDB();

    $s = $db->query("
        SELECT cl.call_id, cl.caller_number, cl.client_id, cl.ended_reason, cl.transcript, cl.summary
        FROM alfred_call_log cl
        LEFT JOIN alfred_callbacks cb ON cb.inbound_call_id = cl.call_id
        WHERE cl.ended_reason LIKE '%error%'
        AND cl.duration_seconds > 30
        AND cl.started_at >= DATE_SUB(NOW(), INTERVAL 4 HOUR)
        AND cb.id IS NULL
        AND cl.caller_number != ''
        ORDER BY cl.started_at DESC
        LIMIT 5
    ");
    $dropped = $s->fetchAll(PDO::FETCH_ASSOC);

    $results = [];
    foreach ($dropped as $call) {
        $context = $call['summary'] ?: mb_substr($call['transcript'] ?? '', -500);
        $msg = "Hi, this is Alfred from Go Site Me. I apologize, it looks like we got disconnected during our earlier call. "
             . "I am calling you back to make sure everything is taken care of. Do you have a moment?";
        $result = triggerOutboundCall($call['caller_number'], 'callback', $msg, $call['client_id']);
        $results[] = $result;
        sleep(3);
    }
    echo json_encode(['action' => 'call_dropped_recovery', 'called' => count($results), 'results' => $results]);
}


// ═══════════════════════════════════════════════════════════════════════════
// Run all proactive call actions (for cron)
// ═══════════════════════════════════════════════════════════════════════════
function runAllProactive() {
    $db = getDB();
    $results = [];

    // Only run during business hours (9 AM - 6 PM ET)
    $hour = (int)date('G');
    if ($hour < 9 || $hour >= 18) {
        echo json_encode(['skipped' => true, 'reason' => 'Outside business hours (9-18 ET)', 'hour' => $hour]);
        return;
    }

    ob_start();

    callScheduledCallbacks();
    $results['scheduled_callbacks'] = json_decode(ob_get_clean(), true);
    ob_start();

    callDroppedRecovery();
    $results['dropped_recovery'] = json_decode(ob_get_clean(), true);
    ob_start();

    callExpiring();
    $results['expiring_domains'] = json_decode(ob_get_clean(), true);
    ob_start();

    callNewSignups();
    $results['new_signups'] = json_decode(ob_get_clean(), true);

    // Log the proactive run
    try {
        $totalCalled = array_sum(array_map(fn($r) => $r['called'] ?? 0, $results));
        $db->prepare("INSERT INTO activity_log (date, description, user, ipaddr) VALUES (NOW(), :d, 'alfred', 'cron')")
           ->execute([':d' => "Alfred proactive outbound: $totalCalled calls placed"]);
    } catch (Exception $e) {}

    echo json_encode(['action' => 'run_all_proactive', 'results' => $results]);
}


// ═══════════════════════════════════════════════════════════════════════════
// Phase 3B: Agent-Initiated Escalation — any agent can trigger Alfred to call Danny
// ═══════════════════════════════════════════════════════════════════════════
function agentEscalation($input) {
    $agentName = $input['agent_name'] ?? 'UNKNOWN';
    $reason = $input['reason'] ?? 'Agent escalation';
    $urgency = $input['urgency'] ?? 'high';
    $details = $input['details'] ?? '';

    // Rate limit: max 1 escalation call per hour
    try {
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        $key = "alfred:agent_escalation_cooldown";
        if ($redis->exists($key)) {
            $redis->close();
            echo json_encode(['success' => false, 'reason' => 'Escalation call on cooldown (1 per hour)']);
            return;
        }
        $redis->setex($key, 3600, '1');
        $redis->close();
    } catch (Exception $e) {}

    $msg = "Commander, this is Alfred. Agent $agentName has escalated an issue that requires your decision. "
         . "The reason: " . substr($reason, 0, 300) . ". "
         . ($details ? "Details: " . substr($details, 0, 200) . ". " : "")
         . "What would you like me to do?";

    $result = triggerOutboundCall('+14504217379', 'callback', $msg, 33);

    // Log the escalation
    $db = getDB();
    if ($db) {
        try {
            $db->prepare("INSERT INTO activity_log (date, description, user, ipaddr) VALUES (NOW(), :d, :u, 'agent-escalation')")
               ->execute([
                   ':d' => "Agent $agentName escalation call: " . substr($reason, 0, 200),
                   ':u' => $agentName,
               ]);
        } catch (Exception $e) {}
    }

    echo json_encode([
        'success' => $result['success'] ?? false,
        'agent' => $agentName,
        'call_id' => $result['call_id'] ?? null,
        'message' => ($result['success'] ?? false)
            ? "Escalation call placed to Commander for $agentName"
            : "Escalation call failed: " . ($result['error'] ?? 'unknown'),
    ]);
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

