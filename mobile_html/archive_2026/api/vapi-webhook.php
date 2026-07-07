<?php
/**
 * Alfred Vapi Server Webhook
 * Receives all call lifecycle events from Vapi
 *
 * Events handled:
 *  - end-of-call-report   Save transcript, summary, recording to DB
 *  - status-update        Track call status live
 *  - hang                 Customer hung up unexpectedly
 *  - speech-update        Detect when customer starts/stops speaking
 */

define('GOSITEME_API', true);
$GLOBALS['CSRF_EXEMPT'] = true; // VAPI webhook callbacks
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/veil-protocol.php';
require_once __DIR__ . '/commander-delegate.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json');
// CORS not needed for server-to-server VAPI endpoints
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// VAPI webhook secret verification — MANDATORY
$vapiSecret = getenv("VAPI_WEBHOOK_SECRET");
if (!$vapiSecret) {
    error_log("VAPI webhook: VAPI_WEBHOOK_SECRET not configured — rejecting all webhooks");
    http_response_code(503);
    echo json_encode(["error" => "Webhook not configured"]);
    exit;
}
$receivedSecret = $_SERVER["HTTP_X_VAPI_SECRET"] ?? "";
if (!hash_equals($vapiSecret, $receivedSecret)) {
    error_log("VAPI webhook: Invalid secret from " . ($_SERVER["REMOTE_ADDR"] ?? "unknown"));
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$type  = $input['message']['type'] ?? $input['type'] ?? '';

// Ensure tables exist
ensureCallLogTable();
ensureCallMemoryTable();
ensureEventLogTable();

switch ($type) {
    case 'assistant-request':
        // REQUIRED for inbound toll-free / DID: Vapi waits for assistantId or inline assistant.
        // Without this, calls connect then fail silently (wrong body was only {"received":true}).
        handleAssistantRequest($input['message'] ?? $input);
        break;
    case 'end-of-call-report':
        handleEndOfCall($input['message'] ?? $input);
        break;
    case 'transfer-destination-request':
        // Model wants to transfer but destination was not embedded — return configured E.164 or error.
        handleTransferDestinationRequest($input['message'] ?? $input);
        break;
    case 'transfer-update':
        handleTransferUpdate($input['message'] ?? $input);
        break;
    case 'status-update':
        handleStatusUpdate($input['message'] ?? $input);
        break;
    case 'hang':
        handleHang($input['message'] ?? $input);
        break;
    case 'function-call':
    case 'tool-calls':
        handleFunctionCall($input['message'] ?? $input);
        break;
    default:
        // Log unknown event types for debugging
        error_log('Vapi webhook unknown type: ' . $type);
        logVapiEvent('webhook.unknown', ['type' => $type ?: 'missing'], 'warn');
}

echo json_encode(['received' => true]);


// ═══════════════════════════════════════════════════════════════════════════
// ASSISTANT REQUEST — inbound call: Vapi needs assistantId (must respond < ~7.5s)
// ═══════════════════════════════════════════════════════════════════════════
function handleAssistantRequest(array $msg): void {
    header('Content-Type: application/json');

    $defaultId = getenv('VAPI_ASSISTANT_ID') ?: '';
    $assistantId = $defaultId;

    // Vapi may send phone number id as string UUID — map to client agent if provisioned
    $pnRoot = $msg['phoneNumber'] ?? [];
    $call = $msg['call'] ?? [];
    $callPn = is_array($call['phoneNumber'] ?? null) ? $call['phoneNumber'] : [];
    $phoneNumberId = (string)($pnRoot['id'] ?? $callPn['id'] ?? $call['phoneNumberId'] ?? $msg['phoneNumberId'] ?? '');

    if ($phoneNumberId !== '') {
        $db = getDB();
        if ($db) {
            try {
                $st = $db->prepare(
                    'SELECT va.vapi_assistant_id FROM voice_phone_numbers pn '
                    . 'INNER JOIN voice_agents va ON va.id = pn.agent_id AND va.active = 1 '
                    . 'WHERE pn.vapi_phone_id = :vid AND pn.active = 1 LIMIT 1'
                );
                $st->execute([':vid' => $phoneNumberId]);
                $row = $st->fetch(PDO::FETCH_ASSOC);
                if ($row && !empty($row['vapi_assistant_id'])) {
                    $assistantId = $row['vapi_assistant_id'];
                }
            } catch (Exception $e) {
                error_log('VAPI assistant-request DB: ' . $e->getMessage());
            }
        }
    }

    if ($assistantId === '') {
        error_log('VAPI assistant-request: VAPI_ASSISTANT_ID unset and no per-number assistant (phoneNumberId=' . ($phoneNumberId ?: 'none') . ')');
        logVapiEvent('assistant.request_failed', [
            'call_id' => (string)($call['id'] ?? ''),
            'phone_number_id' => $phoneNumberId,
            'reason' => 'assistant_not_configured',
        ], 'warn');
        http_response_code(503);
        echo json_encode(['error' => 'Assistant not configured on server']);
        exit;
    }

    error_log('VAPI assistant-request OK assistantId=' . substr($assistantId, 0, 8) . '… phoneNumberId=' . ($phoneNumberId ?: 'default'));
    logVapiEvent('assistant.request', [
        'call_id' => (string)($call['id'] ?? ''),
        'phone_number_id' => $phoneNumberId,
        'assistant_id' => substr($assistantId, 0, 8),
    ]);
    echo json_encode(['assistantId' => $assistantId]);
    exit;
}

/**
 * When the assistant invokes transfer without a fixed number, Vapi POSTs here.
 * Set VAPI_DEFAULT_TRANSFER_E164 (e.g. +15145550100) for warm transfer to sales/support.
 *
 * @see https://docs.vapi.ai/server-url/events#transfer-destination-request
 */
function handleTransferDestinationRequest(array $msg): void {
    header('Content-Type: application/json');
    $callId = (string)($msg['call']['id'] ?? '');
    $num = getenv('VAPI_DEFAULT_TRANSFER_E164') ?: getenv('VAPI_SALES_TRANSFER_E164') ?: '';
    $num = is_string($num) ? trim($num) : '';
    if ($num === '') {
        error_log('VAPI transfer-destination-request: set VAPI_DEFAULT_TRANSFER_E164 in environment');
        logVapiEvent('call.transfer_route_missing', [
            'call_id' => $callId,
            'reason' => 'missing_default_transfer_number',
        ], 'warn');
        echo json_encode([
            'error' => 'Transfer routing is not configured. Offer a callback instead.',
        ]);
        exit;
    }
    error_log('VAPI transfer-destination-request → ' . $num);
    logVapiEvent('call.transfer_route', [
        'call_id' => $callId,
        'destination' => $num,
    ]);
    echo json_encode([
        'destination' => ['type' => 'number', 'number' => $num],
        'message'     => ['type' => 'request-start', 'message' => 'Connecting you now.'],
    ]);
    exit;
}


// ═══════════════════════════════════════════════════════════════════════════
// END OF CALL — save everything
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Best-effort call duration (seconds). Vapi payload shapes vary by API version.
 */
function vapiResolveCallDurationSeconds(array $msg): int {
    $call = $msg['call'] ?? [];
    foreach (['durationSeconds', 'duration'] as $k) {
        if (isset($call[$k]) && is_numeric($call[$k])) {
            $v = (float) $call[$k];
            return $k === 'durationSeconds' ? (int) $v : (int) round($v);
        }
    }
    if (isset($msg['durationSeconds']) && is_numeric($msg['durationSeconds'])) {
        return (int) $msg['durationSeconds'];
    }
    $analysis = $msg['analysis'] ?? [];
    if (isset($analysis['durationSeconds']) && is_numeric($analysis['durationSeconds'])) {
        return (int) $analysis['durationSeconds'];
    }
    $startedAt = $call['startedAt'] ?? $msg['startedAt'] ?? null;
    $endedAt   = $call['endedAt'] ?? $msg['endedAt'] ?? null;
    if ($startedAt && $endedAt) {
        try {
            $s = new DateTime($startedAt);
            $e = new DateTime($endedAt);
            $sec = $e->getTimestamp() - $s->getTimestamp();
            if ($sec > 0) {
                return $sec;
            }
        } catch (Throwable $ex) {
            /* fall through */
        }
        $sec = strtotime((string) $endedAt) - strtotime((string) $startedAt);
        if ($sec > 0) {
            return $sec;
        }
    }
    return 0;
}

/**
 * Transcript may be on message root or under artifact (Vapi server docs).
 */
function vapiResolveTranscript(array $msg): string {
    $t = $msg['transcript'] ?? '';
    if (is_string($t) && $t !== '') {
        return $t;
    }
    $art = $msg['artifact'] ?? [];
    if (!empty($art['transcript']) && is_string($art['transcript'])) {
        return $art['transcript'];
    }
    return is_string($t) ? $t : '';
}

/**
 * Recording URL: flat recordingUrl or nested recording object.
 */
function vapiResolveRecordingUrl(array $msg): string {
    $art = $msg['artifact'] ?? [];
    if (!empty($art['recordingUrl']) && is_string($art['recordingUrl'])) {
        return $art['recordingUrl'];
    }
    $rec = $art['recording'] ?? null;
    if (is_array($rec)) {
        return (string) ($rec['stereoUrl'] ?? $rec['monoUrl'] ?? $rec['url'] ?? '');
    }
    return '';
}

function handleEndOfCall($msg) {
    $db = getDB();
    if (!$db) return;

    $callId      = $msg['call']['id']                    ?? '';
    $startedAt   = $msg['call']['startedAt']             ?? null;
    $endedAt     = $msg['call']['endedAt']               ?? null;
    $endedReason = $msg['endedReason']                   ?? '';
    $transcript  = vapiResolveTranscript($msg);
    $summary     = $msg['analysis']['summary']           ?? '';
    $success     = $msg['analysis']['successEvaluation'] ?? '';
    $recording   = vapiResolveRecordingUrl($msg);
    $cost        = 0;
    if (!empty($msg['call']['costs']) && is_array($msg['call']['costs'])) {
        $cost = array_sum(array_column($msg['call']['costs'], 'cost'));
    }
    $callerNum   = $msg['call']['customer']['number']    ?? '';

    $duration = vapiResolveCallDurationSeconds($msg);
    // Last resort: approximate from transcript length when timestamps/duration are missing (common in some Vapi payloads).
    if ($duration === 0 && is_string($transcript) && strlen($transcript) > 80) {
        $duration = min(7200, max(20, (int) round(strlen($transcript) / 40)));
    }

    // Try to match caller to a client — Redis first (set during auth), then phone lookup
    $clientId = redisGetCallClientId($callId);
    if (!$clientId && $callerNum) {
        $cleaned = preg_replace('/\D/', '', $callerNum);
        $s = $db->prepare("SELECT id FROM clients WHERE REPLACE(REPLACE(REPLACE(phone,'+',''),'-',''),' ','') LIKE :p LIMIT 1");
        $s->execute([':p' => '%' . substr($cleaned, -10)]);
        $row = $s->fetch();
        if ($row) $clientId = $row['id'];
    }

    $db->prepare("INSERT INTO alfred_call_log
        (call_id, client_id, caller_number, started_at, ended_at, duration_seconds, ended_reason,
         transcript, summary, success_evaluation, recording_url, cost_usd, created_at)
        VALUES (:cid,:uid,:num,:start,:end,:dur,:reason,:trans,:sum,:succ,:rec,:cost,NOW())
        ON DUPLICATE KEY UPDATE
        ended_at=:end2, duration_seconds=:dur2, ended_reason=:reason2,
        transcript=:trans2, summary=:sum2, success_evaluation=:succ2,
        recording_url=:rec2, cost_usd=:cost2")
      ->execute([
        ':cid'    => $callId,    ':uid'    => $clientId,  ':num'    => $callerNum,
        ':start'  => $startedAt, ':end'    => $endedAt,   ':dur'    => $duration,
        ':reason' => $endedReason,':trans'  => $transcript,':sum'    => $summary,
        ':succ'   => $success,   ':rec'    => $recording, ':cost'   => round($cost, 4),
        ':end2'   => $endedAt,   ':dur2'   => $duration,  ':reason2'=> $endedReason,
        ':trans2' => $transcript,':sum2'   => $summary,   ':succ2'  => $success,
        ':rec2'   => $recording, ':cost2'  => round($cost, 4)
    ]);

    // Log to activity log
    $db->prepare("INSERT INTO activity_log (date, description, user, ipaddr) VALUES (NOW(),:d,:u,'vapi')")
       ->execute([':d' => 'Alfred call ended — ' . $endedReason . ' — ' . $duration . 's — ' . ($summary ? substr($summary, 0, 200) : 'no summary'), ':u' => $callerNum ?: 'unknown']);

    // Send post-call email to Danny if call was substantial (>30s)
    if ($duration > 30 && $summary) {
        sendPostCallEmail($callId, $callerNum, $duration, $summary, $transcript, $recording, $success, $clientId, $db);
    }

    // Flag failed calls for follow-up
    if ($success === 'false' || $endedReason === 'silence-timed-out') {
        flagFailedCall($callId, $callerNum, $summary, $endedReason, $clientId, $db);
    }

    // ── Veil Protocol — Voice Channel Detection ─────────────────
    // Check if the caller spoke the Veil passphrase during the call
    if ($transcript && $callerNum) {
        $veilResult = veil_attempt_voice_activation(
            $db, $transcript, 'voice-vapi', $callerNum, $clientId ?: null, $callerNum
        );
        if (!empty($veilResult['activated'])) {
            // Log Veil activation via voice call
            $db->prepare("INSERT INTO activity_log (date, description, user, ipaddr) VALUES (NOW(), :d, :u, 'vapi')")
               ->execute([
                   ':d' => 'VEIL PROTOCOL activated via voice call by ' . $callerNum,
                   ':u' => $callerNum
               ]);
        }
    }

    // ── Commander Delegation — Auto-route voice commands ────────
    // Only auto-delegate when Commander was explicitly authenticated in this call.
    if ($clientId === 33 && $transcript && (int)redisGetCallClientId($callId) === 33) {
        $delegation = detectDelegationCommand($transcript, true);
        if ($delegation) {
            delegateToAgent($delegation['command'], 'voice-vapi', 8);
        }
    }

    // ── Trigger pending callbacks after call ends
    try {
        $pendingCb = $db->prepare("SELECT id AS callback_id FROM alfred_callbacks WHERE inbound_call_id = :cid AND callback_status = 'pending' LIMIT 1");
        $pendingCb->execute([':cid' => $callId]);
        $pendingRow = $pendingCb->fetch();
        if ($pendingRow) {
            sleep(3);
            executeCallback($pendingRow['callback_id'], $db);
        }
    } catch (\Exception $e) { error_log('Callback trigger: ' . $e->getMessage()); }

    // ── Log callback completion if this was a callback call ──────────────
    try {
        $cbCheck = $db->prepare("SELECT id FROM alfred_callbacks WHERE outbound_call_id = :cid LIMIT 1");
        $cbCheck->execute([':cid' => $callId]);
        $cbRow = $cbCheck->fetch();
        if ($cbRow) {
            $db->prepare("UPDATE alfred_callbacks SET callback_status = 'completed', callback_summary = :sum, cost_outbound = :cost, completed_at = NOW() WHERE id = :id")
               ->execute([':sum' => $summary, ':cost' => round($cost, 4), ':id' => $cbRow['id']]);
        }
    } catch (\Exception $e) { /* ignore callback log errors */ }

    // ── P2: Cross-call memory — extract and store context for future calls
    if ($callerNum && ($summary || $transcript)) {
        extractCallMemory($callId, $callerNum, $clientId, $summary, $transcript, $endedReason);
    }

    // ── P4: Auto-recovery — schedule callback for crashed/dropped calls
    if ($callerNum && isErrorDisconnection($endedReason)) {
        autoRecoverDroppedCall($callId, $callerNum, $duration, $endedReason, $transcript, $clientId, $db);
    }

    // ── F1: Post-call intelligence — autonomous follow-up actions
    if ($duration > 30 && $transcript) {
        postCallIntelligence($callId, $callerNum, $clientId, $summary, $transcript, $endedReason, $success, $duration, $db);
    }

    logVapiEvent('call.completed', [
        'call_id' => $callId,
        'client_id' => (int)$clientId,
        'caller_number' => $callerNum,
        'duration_seconds' => $duration,
        'ended_reason' => $endedReason,
        'has_summary' => $summary !== '',
        'has_transcript' => $transcript !== '',
    ], $success === 'false' ? 'warn' : 'info');
}


// ═══════════════════════════════════════════════════════════════════════════
// STATUS UPDATE — track live
// ═══════════════════════════════════════════════════════════════════════════
function handleStatusUpdate($msg) {
    $db = getDB();
    if (!$db) return;

    $callId = $msg['call']['id'] ?? '';
    $status = $msg['status'] ?? '';
    $caller = $msg['call']['customer']['number'] ?? '';

    if ($callId && $status === 'in-progress') {
        // Insert initial record when call starts
        try {
            $db->prepare("INSERT IGNORE INTO alfred_call_log (call_id, caller_number, started_at, created_at) VALUES (:cid,:num,NOW(),NOW())")
               ->execute([':cid' => $callId, ':num' => $caller]);
        } catch (Exception $e) {}
    }

    if ($callId && $status !== '') {
        logVapiEvent('call.status', [
            'call_id' => $callId,
            'caller_number' => $caller,
            'status' => $status,
        ]);
    }
}


// ═══════════════════════════════════════════════════════════════════════════
// HANG — unexpected disconnect
// ═══════════════════════════════════════════════════════════════════════════
function handleHang($msg) {
    $db  = getDB();
    $callId = $msg['call']['id'] ?? '';
    $caller = $msg['call']['customer']['number'] ?? 'unknown';
    error_log("Alfred: unexpected hang from $caller");
    logVapiEvent('call.hang', [
        'call_id' => $callId,
        'caller_number' => $caller,
    ], 'warn');
}


// ═══════════════════════════════════════════════════════════════════════════
// TRANSFER UPDATE — transfer actually happened
// ═══════════════════════════════════════════════════════════════════════════
function handleTransferUpdate($msg) {
    $callId = $msg['call']['id'] ?? '';
    $destination = $msg['destination'] ?? [];
    $destinationType = is_array($destination) ? (string)($destination['type'] ?? 'unknown') : 'unknown';
    $destinationValue = '';
    if (is_array($destination)) {
        $destinationValue = (string)($destination['number'] ?? $destination['sipUri'] ?? $destination['assistantId'] ?? '');
    }

    logVapiEvent('call.transfer', [
        'call_id' => $callId,
        'destination' => $destinationValue,
        'destination_type' => $destinationType,
    ]);
}


// ═══════════════════════════════════════════════════════════════════════════
// POST-CALL EMAIL to Danny
// ═══════════════════════════════════════════════════════════════════════════
function sendPostCallEmail($callId, $caller, $duration, $summary, $transcript, $recording, $success, $clientId, $db) {
    $clientInfo = '';
    if ($clientId) {
        $s = $db->prepare("SELECT firstname, lastname, email FROM clients WHERE id=:id LIMIT 1");
        $s->execute([':id' => $clientId]);
        $c = $s->fetch();
        if ($c) $clientInfo = "\nCustomer: {$c['firstname']} {$c['lastname']} ({$c['email']})";
    }

    $mins    = floor($duration / 60);
    $secs    = $duration % 60;
    $durStr  = $mins > 0 ? "{$mins}m {$secs}s" : "{$secs}s";
    $outcome = $success === 'true' ? '✅ Resolved' : '⚠️ Needs Follow-up';

    $subject = "Alfred Call Report — $outcome — $durStr";
    $body    = "Alfred Call Summary\n"
             . "===================\n"
             . "Caller: $caller$clientInfo\n"
             . "Duration: $durStr\n"
             . "Outcome: $outcome\n"
             . "Ended: " . ($success === 'true' ? 'Customer satisfied' : 'May need follow-up') . "\n\n"
             . "SUMMARY:\n$summary\n\n"
             . ($recording ? "RECORDING:\n$recording\n\n" : '')
             . "FULL TRANSCRIPT:\n" . ($transcript ?: 'Not available') . "\n\n"
             . "---\nView all calls: https://gositeme.com/admin/alfred-calls.php";

    mail('gositeme@gmail.com', $subject, $body,
        "From: alfred@gositeme.com\r\nReply-To: alfred@gositeme.com\r\nContent-Type: text/plain; charset=UTF-8"
    );
}


// ═══════════════════════════════════════════════════════════════════════════
// FLAG FAILED CALL — create follow-up ticket
// ═══════════════════════════════════════════════════════════════════════════
function flagFailedCall($callId, $caller, $summary, $reason, $clientId, $db) {
    $stmtD = $db->prepare("SELECT id FROM ticket_departments WHERE is_hidden=0 ORDER BY id ASC LIMIT 1");
    $stmtD->execute(); $dept = $stmtD->fetch();

    $letters = strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ'), 0, 3));
    $tid     = $letters . '-' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);

    try {
        $db->prepare("INSERT INTO tickets
            (tid, client_id, contact_name, contact_email, department_id, subject, message, status, priority, source, last_reply, created_at, updated_at)
            VALUES (:tid, :uid, :name, :email, :did, :title, :msg, 'Open', 'High', 'voice-ai', NOW(), NOW(), NOW())")
           ->execute([
               ':tid'   => $tid,
               ':uid'   => $clientId ?: null,
               ':name'  => $caller ?: 'Unknown Caller',
               ':email' => 'alfred@gositeme.com',
               ':did'   => ($dept['id'] ?? 1),
               ':title' => 'Alfred Call Needs Follow-up — ' . $caller,
               ':msg'   => "Alfred flagged this call as unresolved.\n\nCaller: $caller\nReason ended: $reason\nCall ID: $callId\n\nSummary:\n$summary\n\nPlease follow up within 24 hours."
           ]);
    } catch (Exception $e) {
        error_log('Failed call ticket error: ' . $e->getMessage());
    }
}


// ═══════════════════════════════════════════════════════════════════════════
// Ensure call log table exists

// ════════════════════════════════════════════════════════════════
// FUNCTION CALL — handle tool calls from Alfred during voice calls
// ════════════════════════════════════════════════════════════════
//
// CRITICAL: Vapi may POST "function-call" events to this webhook. Previously only
// `requestCallback` was implemented — every other tool returned "Unknown function",
// which made phone Alfred appear "unable to do anything". Full tools live in
// api/vapi-tools.php (same suite as the dedicated tools server URL).
// ════════════════════════════════════════════════════════════════

/**
 * Normalize tool arguments (Vapi may send array, object, or JSON string).
 */
function vapi_webhook_normalize_args($params): array {
    if (is_array($params)) {
        return $params;
    }
    if (is_string($params) && $params !== '') {
        $decoded = json_decode($params, true);
        return is_array($decoded) ? $decoded : [];
    }
    return [];
}

/**
 * Forward tool call(s) to vapi-tools.php; returns decoded JSON or null on failure.
 */
function vapi_webhook_proxy_to_tools_server_raw(array $messagePayload): ?array {
    $secret = getenv('VAPI_WEBHOOK_SECRET');
    if (!$secret) {
        return null;
    }

    $body = json_encode(['message' => $messagePayload]);
    $url  = 'https://gositeme.com/api/vapi-tools.php';

    $ch = curl_init($url);
    if ($ch === false) {
        return null;
    }

    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'X-Vapi-Secret: ' . $secret,
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 120,
        CURLOPT_CONNECTTIMEOUT => 15,
    ]);

    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($resp === false || $code !== 200) {
        error_log("vapi-webhook tool proxy failed HTTP={$code} err={$err} body=" . substr((string) $resp, 0, 300));
        logVapiEvent('tool.proxy_failed', [
            'call_id' => (string)($messagePayload['call']['id'] ?? ''),
            'http_code' => $code,
            'error' => $err,
        ], 'warn');
        return null;
    }

    $data = json_decode($resp, true);
    if (!is_array($data) || empty($data['results']) || !is_array($data['results'])) {
        error_log('vapi-webhook tool proxy: unexpected response ' . substr((string) $resp, 0, 400));
        logVapiEvent('tool.proxy_invalid_response', [
            'call_id' => (string)($messagePayload['call']['id'] ?? ''),
            'body_excerpt' => mb_substr((string)$resp, 0, 250),
        ], 'warn');
        return null;
    }

    return $data;
}

/**
 * First tool result as JSON string (for legacy single `result` webhook shape).
 */
function vapi_webhook_proxy_to_tools_server(array $messagePayload): string {
    $data = vapi_webhook_proxy_to_tools_server_raw($messagePayload);
    if ($data === null) {
        return json_encode(['error' => 'Tool service temporarily unavailable. Please try again in a moment.']);
    }
    $first = $data['results'][0]['result'] ?? '';
    if ($first === '') {
        return json_encode(['error' => 'Unexpected tool response. Please try again.']);
    }
    return $first;
}

function handleFunctionCall($msg) {
    $callId = $msg['call']['id'] ?? '';
    $caller = $msg['call']['customer']['number'] ?? '';

    // ── Batch: message already has toolCalls[] (some Vapi payloads) ──
    if (!empty($msg['toolCalls']) && is_array($msg['toolCalls'])) {
        $toolNames = [];
        foreach ($msg['toolCalls'] as $toolCall) {
            $toolName = $toolCall['function']['name'] ?? $toolCall['name'] ?? '';
            if ($toolName !== '') {
                $toolNames[] = $toolName;
            }
        }
        logVapiEvent('tool.calls', [
            'call_id' => $callId,
            'caller_number' => $caller,
            'tool_names' => implode(', ', $toolNames),
            'count' => count($toolNames),
        ]);

        $messagePayload = $msg;
        if (empty($messagePayload['call']) && !empty($msg['call'])) {
            $messagePayload['call'] = $msg['call'];
        }
        $full = vapi_webhook_proxy_to_tools_server_raw($messagePayload);
        header('Content-Type: application/json');
        if ($full !== null) {
            // Return same shape as vapi-tools (multiple toolCallId results)
            echo json_encode($full);
        } else {
            echo json_encode(['results' => [['toolCallId' => 'error', 'result' => json_encode(['error' => 'Tool proxy failed'])]]]);
        }
        exit;
    }

    // ── Single functionCall (legacy / common Vapi shape) ──
    $funcName = $msg['functionCall']['name'] ?? '';
    $params   = $msg['functionCall']['parameters'] ?? $msg['functionCall']['arguments'] ?? [];

    if ($funcName === '') {
        error_log('vapi-webhook function-call: missing function name: ' . json_encode(array_keys($msg)));
        logVapiEvent('tool.call_missing_name', [
            'call_id' => $callId,
            'caller_number' => $caller,
        ], 'warn');
        header('Content-Type: application/json');
        echo json_encode(['result' => json_encode(['error' => 'No function name in webhook payload'])]);
        exit;
    }

    logVapiEvent('tool.call', [
        'call_id' => $callId,
        'caller_number' => $caller,
        'tool_name' => $funcName,
    ]);

    // Local-only security / callback flow (not in vapi-tools switch)
    if ($funcName === 'requestCallback') {
        $result = toolRequestCallback(vapi_webhook_normalize_args($params), $callId, $caller);
        header('Content-Type: application/json');
        echo json_encode(['result' => json_encode($result)]);
        exit;
    }

    $args = vapi_webhook_normalize_args($params);

    $messagePayload = [
        'toolCalls' => [[
            'id'       => uniqid('vapi_wh_', true),
            'function' => [
                'name'      => $funcName,
                'arguments' => $args,
            ],
        ]],
        'call' => $msg['call'] ?? [],
    ];

    $out = vapi_webhook_proxy_to_tools_server($messagePayload);
    header('Content-Type: application/json');
    echo json_encode(['result' => $out]);
    exit;
}


// ════════════════════════════════════════════════════════════════
// TOOL: requestCallback — security verification callback
// ════════════════════════════════════════════════════════════════
function toolRequestCallback($params, $callId, $callerNumber) {
    $db = getDB();
    if (!$db) return ['success' => false, 'message' => 'Database unavailable'];
    
    $clientEmail = trim($params['client_email'] ?? '');
    $contextSummary = trim($params['greeting_summary'] ?? $params['context_summary'] ?? $params['summary'] ?? '');
    $reason = $params['reason'] ?? 'verification';
    
    $verifiedNumber = null;
    $clientId = 0;
    $clientName = '';
    
    if ($clientEmail) {
        $stmt = $db->prepare("SELECT id, firstname, lastname, phone FROM clients WHERE email = :e LIMIT 1");
        $stmt->execute([':e' => $clientEmail]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($client) {
            $clientId = (int)$client['id'];
            $clientName = trim($client['firstname'] . ' ' . $client['lastname']);
            $rawPhone = preg_replace('/\D/', '', $client['phone']);
            if (strlen($rawPhone) === 10) $rawPhone = '1' . $rawPhone;
            $verifiedNumber = '+' . $rawPhone;
        }
    }
    
    $callerClean = preg_replace('/\D/', '', $callerNumber);
    
    if (!$verifiedNumber) {
        return [
            'success' => false,
            'message' => 'Could not find a phone number on file for that email. Need to verify identity another way.'
        ];
    }
    
    $callerLast10 = substr($callerClean, -10);
    $verifiedLast10 = substr(preg_replace('/\D/', '', $verifiedNumber), -10);
    $numberMatch = ($callerLast10 === $verifiedLast10);
    $lastFour = substr($verifiedNumber, -4);
    
    $stmt = $db->prepare("INSERT INTO alfred_callbacks
        (client_id, email, caller_number, verified_number, callback_status, callback_reason,
         inbound_call_id, security_tier, greeting_summary, created_at)
        VALUES (:uid, :email, :caller, :verified, 'pending', :reason,
                :icid, 'verified', :ctx, NOW())");
    $stmt->execute([
        ':uid' => $clientId,
        ':email' => $clientEmail,
        ':caller' => $callerNumber,
        ':verified' => $verifiedNumber,
        ':reason' => $reason,
        ':icid' => $callId,
        ':ctx' => $contextSummary ?: json_encode([
            'claimed_email' => $clientEmail,
            'caller_number' => $callerNumber,
            'number_on_file' => $verifiedNumber,
            'numbers_match' => $numberMatch,
            'client_name' => $clientName
        ])
    ]);
    $callbackId = (int)$db->lastInsertId();
    
    if (in_array($reason, ['verification', 'reconnect', 'security_callback'], true)) {
        executeCallback($callbackId, $db);
        return [
            'success' => true,
            'message' => "Callback initiated! Calling $clientName back at the number ending in $lastFour right now."
        ];
    }
    
    return [
        'success' => true,
        'callback_id' => $callbackId,
        'client_name' => $clientName,
        'number_last_four' => $lastFour,
        'numbers_match' => $numberMatch,
        'message' => $numberMatch
            ? "Caller IS calling from the number on file (ending $lastFour). Good sign. Tell them you will call back at that number for verification, then end the call. Callback happens in ~10 seconds."
            : "WARNING: Caller NOT calling from number on file. File says $lastFour but they are on a different number. Suspicious. Tell them you will call the number ON FILE ending $lastFour."
    ];
}


// ════════════════════════════════════════════════════════════════
// Execute a pending callback — makes the outbound VAPI call
// ════════════════════════════════════════════════════════════════
function executeCallback($callbackId, $db = null) {
    if (!$db) $db = getDB();
    
    $stmt = $db->prepare("SELECT * FROM alfred_callbacks WHERE id = :id AND callback_status = 'pending' LIMIT 1");
    $stmt->execute([':id' => $callbackId]);
    $cb = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$cb) return false;
    
    $db->prepare("UPDATE alfred_callbacks SET callback_status = 'calling', called_at = NOW() WHERE id = :id")
       ->execute([':id' => $cb['id']]);
    
    $clientName = 'there';
    if (!empty($cb['client_id'])) {
        try {
            $clientStmt = $db->prepare("SELECT firstname, lastname FROM clients WHERE id = :id LIMIT 1");
            $clientStmt->execute([':id' => (int)$cb['client_id']]);
            $client = $clientStmt->fetch(PDO::FETCH_ASSOC);
            if ($client) {
                $clientName = trim(($client['firstname'] ?? '') . ' ' . ($client['lastname'] ?? '')) ?: 'there';
            }
        } catch (Exception $e) {
            error_log('VAPI callback client lookup: ' . $e->getMessage());
        }
    }
    $reason = trim($cb['callback_reason'] ?? 'follow-up');
    $summaryNote = trim($cb['greeting_summary'] ?? '');
    
    $firstMessage = ($reason === 'verification')
        ? "Hi $clientName! This is Alfred calling you back for security verification. I just spoke with someone on your account and I need to confirm this is really you. Can you tell me your full name and the email on your account?"
        : "Hi $clientName! This is Alfred calling you back. We got disconnected, sorry about that. I am right here, where were we?";
    
    $contextNote = $summaryNote ? "\n\nCONTEXT FROM PREVIOUS CALL: " . $summaryNote : '';
    
    $systemMessage = "You are Alfred Perez calling back a client for " . ($reason === 'verification' ? 'security verification' : 'reconnection after a dropped call') . "."
        . "\nClient name on file: $clientName"
        . "\nVerified phone: " . $cb['verified_number']
        . "\nOriginal caller: " . $cb['caller_number']
        . $contextNote
        . "\n\nIf VERIFICATION: Ask name + email. Match = verified, help them. No match = cannot verify, end politely."
        . "\nIf RECONNECT: Apologize, pick up where you left off."
        . "\nBe warm, professional, natural. You are Alfred.";
    
    $apiKey = '5c329925-950f-4fe9-bcf3-f292f6acd9bd';
    $alfredId = '4c362a73-7af4-47fd-9112-d4cc27145d77';
    $phoneId = '00d6cc65-c505-4560-9e4c-16b86528b65c';
    
    $payload = [
        'assistantId' => $alfredId,
        'assistantOverrides' => [
            'firstMessage' => $firstMessage,
            'model' => [
                'provider' => 'anthropic',
                'model' => 'claude-sonnet-4-20250514',
                'messages' => [['role' => 'system', 'content' => $systemMessage]]
            ]
        ],
        'phoneNumberId' => $phoneId,
        'customer' => [
            'number' => $cb['verified_number'],
            'name' => $clientName
        ]
    ];
    
    $ch = curl_init('https://api.vapi.ai/call');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $apiKey",
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $data = json_decode($resp, true);
    
    if ($code >= 200 && $code < 300 && isset($data['id'])) {
        $db->prepare("UPDATE alfred_callbacks SET outbound_call_id = :ocid WHERE id = :id")
           ->execute([':ocid' => $data['id'], ':id' => $cb['id']]);
        error_log("Alfred callback: outbound call placed to " . $cb['verified_number']);
        return true;
    } else {
        $db->prepare("UPDATE alfred_callbacks SET callback_status = 'failed' WHERE id = :id")
           ->execute([':id' => $cb['id']]);
        error_log("Alfred callback FAILED: HTTP $code");
        return false;
    }
}



// ═══════════════════════════════════════════════════════════════════════════
// F1: POST-CALL INTELLIGENCE — Autonomous analysis and follow-up
// ═══════════════════════════════════════════════════════════════════════════
function postCallIntelligence($callId, $callerNum, $clientId, $summary, $transcript, $endedReason, $success, $duration, $db) {
    $apiKey = getenv('OPENAI_API_KEY');
    if (!$apiKey) return;

    $truncatedTranscript = mb_substr($transcript, 0, 8000);

    $prompt = <<<PROMPT
Analyze this phone call transcript from Alfred (AI support agent for GoSiteMe/GoCodeMe) and return a JSON object with these fields:

1. "action_items": array of objects with {action, priority, details}
   - action: one of "create_ticket", "schedule_callback", "alert_danny", "update_account", "none"
   - priority: "high", "medium", "low"
   - details: brief description of what needs to happen

2. "promised_callbacks": array of strings describing any callbacks promised during the call

3. "unresolved_issues": array of strings describing issues not fully resolved

4. "alert_danny": boolean — true ONLY if:
   - Caller was angry/threatening
   - Legal or compliance issue mentioned
   - Revenue risk (cancellation threat, chargeback mention)
   - Service outage affecting multiple customers
   - Security incident

5. "alert_reason": string — why Danny should be alerted (empty if alert_danny is false)

6. "follow_up_needed": boolean — true if the caller needs any follow-up

Call summary: {$summary}
Ended reason: {$endedReason}
Success evaluation: {$success}
Duration: {$duration} seconds

TRANSCRIPT:
{$truncatedTranscript}
PROMPT;

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ["Authorization: Bearer $apiKey", "Content-Type: application/json"],
        CURLOPT_POSTFIELDS => json_encode([
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a post-call analysis engine. Return valid JSON only. Be conservative with alerts — only flag truly important items.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.1,
            'response_format' => ['type' => 'json_object']
        ]),
        CURLOPT_TIMEOUT => 20,
    ]);
    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log("Post-call intelligence API error: HTTP $httpCode");
        return;
    }

    $data = json_decode($resp, true);
    $content = $data['choices'][0]['message']['content'] ?? '';
    $analysis = json_decode($content, true);
    if (!$analysis) return;

    // Auto-create support tickets for action items
    $actionItems = $analysis['action_items'] ?? [];
    foreach ($actionItems as $item) {
        if ($item['action'] === 'create_ticket' && ($item['priority'] === 'high' || $item['priority'] === 'medium')) {
            try {
                $letters = strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ'), 0, 3));
                $tid = $letters . '-' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);

                $stmtD = $db->prepare("SELECT id FROM ticket_departments WHERE is_hidden=0 ORDER BY id ASC LIMIT 1");
                $stmtD->execute();
                $dept = $stmtD->fetch();

                $db->prepare("INSERT INTO tickets
                    (tid, client_id, contact_name, contact_email, department_id, subject, message, status, priority, source, last_reply, created_at, updated_at)
                    VALUES (:tid, :uid, :name, :email, :did, :title, :msg, 'Open', :pri, 'alfred-intelligence', NOW(), NOW(), NOW())")
                   ->execute([
                       ':tid' => $tid,
                       ':uid' => $clientId ?: null,
                       ':name' => $callerNum ?: 'Unknown Caller',
                       ':email' => 'alfred@gositeme.com',
                       ':did' => ($dept['id'] ?? 1),
                       ':title' => 'Alfred Auto-Ticket: ' . mb_substr($item['details'], 0, 100),
                       ':msg' => "Auto-generated by Alfred's post-call intelligence.\n\n"
                           . "Caller: $callerNum\nCall ID: $callId\nDuration: {$duration}s\n\n"
                           . "Action needed: {$item['details']}\nPriority: {$item['priority']}\n\n"
                           . "Call summary: $summary",
                       ':pri' => ucfirst($item['priority']),
                   ]);
            } catch (Exception $e) {
                error_log('Post-call ticket error: ' . $e->getMessage());
            }
        }
    }

    // Schedule callbacks for promised follow-ups
    $promisedCallbacks = $analysis['promised_callbacks'] ?? [];
    if (!empty($promisedCallbacks) && $callerNum) {
        foreach ($promisedCallbacks as $reason) {
            try {
                $db->prepare("INSERT INTO alfred_callbacks
                    (client_id, email, caller_number, verified_number, callback_status, callback_reason,
                     inbound_call_id, security_tier, greeting_summary, created_at)
                    VALUES (:uid, '', :caller, :verified, 'pending', 'follow-up',
                            :icid, 'public', :ctx, NOW())")
                   ->execute([
                       ':uid' => $clientId,
                       ':caller' => $callerNum,
                       ':verified' => $callerNum,
                       ':icid' => $callId,
                       ':ctx' => "Follow-up promised during call: " . mb_substr($reason, 0, 500),
                   ]);
            } catch (Exception $e) {
                error_log('Post-call callback schedule error: ' . $e->getMessage());
            }
        }
    }

    // Alert Danny via SMS for high-priority items
    if (!empty($analysis['alert_danny'])) {
        $alertReason = $analysis['alert_reason'] ?? 'Post-call analysis flagged this call';
        $smsBody = "ALFRED ALERT\n"
            . "Caller: $callerNum\n"
            . "Duration: " . floor($duration / 60) . "m " . ($duration % 60) . "s\n"
            . "Reason: " . mb_substr($alertReason, 0, 300) . "\n"
            . "Summary: " . mb_substr($summary, 0, 200);

        try {
            $telnyxKey = getenv('TELNYX_API_KEY');
            $telnyxFrom = getenv('TELNYX_FROM_NUMBER') ?: '+15146130386';
            if ($telnyxKey) {
                $ch2 = curl_init('https://api.telnyx.com/v2/messages');
                curl_setopt_array($ch2, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_HTTPHEADER => ["Authorization: Bearer $telnyxKey", "Content-Type: application/json"],
                    CURLOPT_POSTFIELDS => json_encode([
                        'from' => $telnyxFrom,
                        'to' => '+14504217379',
                        'text' => $smsBody,
                    ]),
                    CURLOPT_TIMEOUT => 10,
                ]);
                curl_exec($ch2);
                curl_close($ch2);
            }
        } catch (Exception $e) {
            error_log('Post-call SMS alert error: ' . $e->getMessage());
        }

        // Also email
        mail('gositeme@gmail.com',
            'ALFRED ALERT: ' . mb_substr($alertReason, 0, 80),
            $smsBody . "\n\nFull summary:\n$summary\n\nCall ID: $callId",
            "From: alfred@gositeme.com\r\nContent-Type: text/plain; charset=UTF-8"
        );

        logVapiEvent('call.alert', [
            'call_id' => $callId,
            'caller_number' => $callerNum,
            'alert_reason' => $alertReason,
        ], 'critical');
    }

    // Log the intelligence run
    try {
        $db->prepare("INSERT INTO activity_log (date, description, user, ipaddr) VALUES (NOW(), :d, :u, 'alfred-intel')")
           ->execute([
               ':d' => 'Post-call intelligence: ' . count($actionItems) . ' actions, '
                   . count($promisedCallbacks) . ' callbacks, alert=' . ($analysis['alert_danny'] ? 'YES' : 'no'),
               ':u' => $callerNum ?: 'unknown',
           ]);
    } catch (Exception $e) {}
}


// ═══════════════════════════════════════════════════════════════════════════
function ensureCallLogTable() {
    $db = getDB();
    if (!$db) return;
    $db->exec("CREATE TABLE IF NOT EXISTS alfred_call_log (
        id               INT AUTO_INCREMENT PRIMARY KEY,
        call_id          VARCHAR(64) UNIQUE,
        client_id        INT DEFAULT 0,
        caller_number    VARCHAR(50),
        started_at       DATETIME,
        ended_at         DATETIME,
        duration_seconds INT DEFAULT 0,
        ended_reason     VARCHAR(100),
        transcript       MEDIUMTEXT,
        summary          TEXT,
        success_evaluation VARCHAR(10),
        recording_url    TEXT,
        cost_usd         DECIMAL(8,4) DEFAULT 0,
        created_at       DATETIME,
        INDEX idx_client (client_id),
        INDEX idx_started (started_at)
    )");
}

function ensureCallMemoryTable() {
    $db = getDB();
    if (!$db) return;
    $db->exec("CREATE TABLE IF NOT EXISTS alfred_call_memory (
        id             INT AUTO_INCREMENT PRIMARY KEY,
        caller_number  VARCHAR(50) NOT NULL,
        client_id      INT DEFAULT 0,
        call_id        VARCHAR(64),
        caller_name    VARCHAR(255) DEFAULT '',
        call_summary   TEXT,
        key_topics     TEXT,
        unresolved_items TEXT,
        sentiment      VARCHAR(20) DEFAULT '',
        last_call_at   DATETIME,
        created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_caller (caller_number),
        INDEX idx_client (client_id),
        INDEX idx_last_call (last_call_at)
    )");
}

function ensureEventLogTable() {
    $db = getDB();
    if (!$db) return;
    $db->exec("CREATE TABLE IF NOT EXISTS alfred_event_log (
        id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        event_type    VARCHAR(100) NOT NULL,
        subsystem     VARCHAR(50)  NOT NULL,
        actor         VARCHAR(100) DEFAULT 'alfred',
        target_id     INT UNSIGNED DEFAULT NULL,
        target_type   VARCHAR(50)  DEFAULT NULL,
        payload       JSON,
        severity      ENUM('info','warn','critical','emergency') DEFAULT 'info',
        created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_type    (event_type),
        INDEX idx_sub     (subsystem),
        INDEX idx_created (created_at),
        INDEX idx_sev     (severity)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function logVapiEvent(string $eventType, array $payload = [], string $severity = 'info'): void {
    $db = getDB();
    if (!$db) return;

    if (!in_array($severity, ['info', 'warn', 'critical', 'emergency'], true)) {
        $severity = 'info';
    }

    try {
        $stmt = $db->prepare("INSERT INTO alfred_event_log (event_type, subsystem, actor, target_id, target_type, payload, severity) VALUES (:event_type, 'vapi', 'vapi-webhook', NULL, 'call', :payload, :severity)");
        $stmt->execute([
            ':event_type' => $eventType,
            ':payload' => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ':severity' => $severity,
        ]);
    } catch (Exception $e) {
        error_log('VAPI event log write failed: ' . $e->getMessage());
    }
}


// ═══════════════════════════════════════════════════════════════════════════
// CROSS-CALL MEMORY — Extract and store memory after each call
// ═══════════════════════════════════════════════════════════════════════════
function extractCallMemory($callId, $callerNum, $clientId, $summary, $transcript, $endedReason) {
    if (!$callerNum || !$summary) return;

    $db = getDB();
    if (!$db) return;

    $keyTopics = '';
    $unresolvedItems = '';
    $callerName = '';
    $sentiment = '';

    $apiKey = getenv('OPENAI_API_KEY');
    if ($apiKey && $transcript) {
        $truncated = mb_substr($transcript, 0, 6000);
        $prompt = "Analyze this phone call transcript and return JSON with these fields:\n"
            . "- caller_name: the caller's name if mentioned, else empty string\n"
            . "- key_topics: comma-separated list of main topics discussed (max 5)\n"
            . "- unresolved_items: comma-separated list of things not resolved or promised for follow-up (empty if all resolved)\n"
            . "- sentiment: one of positive, neutral, frustrated, angry\n\n"
            . "TRANSCRIPT:\n$truncated";

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ["Authorization: Bearer $apiKey", "Content-Type: application/json"],
            CURLOPT_POSTFIELDS => json_encode([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'You extract structured data from call transcripts. Return valid JSON only.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.1,
                'response_format' => ['type' => 'json_object']
            ]),
            CURLOPT_TIMEOUT => 15,
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($resp, true);
        $content = $data['choices'][0]['message']['content'] ?? '';
        $parsed = json_decode($content, true);

        if ($parsed) {
            $callerName = trim($parsed['caller_name'] ?? '');
            $keyTopics = trim($parsed['key_topics'] ?? '');
            $unresolvedItems = trim($parsed['unresolved_items'] ?? '');
            $sentiment = trim($parsed['sentiment'] ?? '');
        }
    }

    if (!$keyTopics && $summary) {
        $keyTopics = mb_substr($summary, 0, 200);
    }

    try {
        $db->prepare("INSERT INTO alfred_call_memory
            (caller_number, client_id, call_id, caller_name, call_summary, key_topics, unresolved_items, sentiment, last_call_at, created_at)
            VALUES (:num, :uid, :cid, :name, :sum, :topics, :unresolved, :sentiment, NOW(), NOW())")
           ->execute([
               ':num' => $callerNum,
               ':uid' => $clientId,
               ':cid' => $callId,
               ':name' => $callerName,
               ':sum' => mb_substr($summary, 0, 2000),
               ':topics' => mb_substr($keyTopics, 0, 500),
               ':unresolved' => mb_substr($unresolvedItems, 0, 500),
               ':sentiment' => $sentiment
           ]);
    } catch (Exception $e) {
        error_log('Call memory save error: ' . $e->getMessage());
    }
}


// ═══════════════════════════════════════════════════════════════════════════
// CALLER CONTEXT — Retrieve memory for a caller
// ═══════════════════════════════════════════════════════════════════════════
function getCallerMemoryContext($callerNumber, $clientId = 0) {
    $db = getDB();
    if (!$db) return null;

    $memories = [];

    if ($clientId > 0) {
        $stmt = $db->prepare("SELECT caller_name, call_summary, key_topics, unresolved_items, sentiment, last_call_at
            FROM alfred_call_memory WHERE client_id = :uid ORDER BY last_call_at DESC LIMIT 3");
        $stmt->execute([':uid' => $clientId]);
        $memories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    if (empty($memories) && $callerNumber) {
        $cleaned = preg_replace('/\D/', '', $callerNumber);
        $last10 = substr($cleaned, -10);
        $stmt = $db->prepare("SELECT caller_name, call_summary, key_topics, unresolved_items, sentiment, last_call_at
            FROM alfred_call_memory WHERE RIGHT(REPLACE(REPLACE(REPLACE(caller_number,'+',''),'-',''),' ',''), 10) = :p
            ORDER BY last_call_at DESC LIMIT 3");
        $stmt->execute([':p' => $last10]);
        $memories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    if (empty($memories)) return null;

    $callerName = '';
    $context = "PREVIOUS CALL HISTORY (most recent first):\n";
    foreach ($memories as $i => $m) {
        $n = $i + 1;
        $context .= "--- Call $n ({$m['last_call_at']}) ---\n";
        if ($m['caller_name']) {
            $callerName = $m['caller_name'];
            $context .= "Name: {$m['caller_name']}\n";
        }
        if ($m['key_topics']) $context .= "Topics: {$m['key_topics']}\n";
        if ($m['call_summary']) $context .= "Summary: {$m['call_summary']}\n";
        if ($m['unresolved_items']) $context .= "UNRESOLVED: {$m['unresolved_items']}\n";
        if ($m['sentiment']) $context .= "Mood: {$m['sentiment']}\n";
    }
    $context .= "\nUse this context to pick up where you left off. Reference previous topics naturally.";

    return ['caller_name' => $callerName, 'context' => $context, 'call_count' => count($memories)];
}


// ═══════════════════════════════════════════════════════════════════════════
// REDIS HELPERS — client_id persistence across call lifecycle
// ═══════════════════════════════════════════════════════════════════════════
function redisGetCallClientId($callId) {
    try {
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        $val = $redis->get("alfred:call_client:{$callId}");
        $redis->close();
        return $val ? (int)$val : 0;
    } catch (Exception $e) {
        return 0;
    }
}

function redisSetCallClientId($callId, $clientId) {
    try {
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        $redis->setex("alfred:call_client:{$callId}", 7200, $clientId);
        $redis->close();
    } catch (Exception $e) {
        error_log('Redis set call client error: ' . $e->getMessage());
    }
}


// ═══════════════════════════════════════════════════════════════════════════
// AUTO-RECOVERY — schedule callback for dropped/crashed calls
// ═══════════════════════════════════════════════════════════════════════════
function isErrorDisconnection($endedReason) {
    $errorPatterns = ['error', 'vapifault', 'pipeline-error', 'exceeded-max-duration'];
    foreach ($errorPatterns as $p) {
        if (stripos($endedReason, $p) !== false) return true;
    }
    return false;
}

function autoRecoverDroppedCall($callId, $callerNum, $duration, $endedReason, $transcript, $clientId, $db) {
    if ($duration < 30) return;

    $errorReasons = [
        'call.in-progress.error',
        'vapifault',
        'pipeline-error',
        'openai-voice-failed',
    ];

    $isError = false;
    foreach ($errorReasons as $r) {
        if (stripos($endedReason, $r) !== false) { $isError = true; break; }
    }
    if (!$isError && $endedReason !== 'exceeded-max-duration') return;

    $rateKey = "alfred:auto_callback:" . preg_replace('/\D/', '', $callerNum);
    try {
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        if ($redis->exists($rateKey)) {
            $redis->close();
            error_log("Alfred auto-recovery: rate limited for $callerNum");
            return;
        }
        $redis->setex($rateKey, 3600, '1');
        $redis->close();
    } catch (Exception $e) {
        error_log('Redis rate limit error: ' . $e->getMessage());
    }

    $contextSummary = '';
    if ($transcript) {
        $contextSummary = mb_substr($transcript, -1500);
    }

    try {
        $db->prepare("INSERT INTO alfred_callbacks
            (client_id, email, caller_number, verified_number, callback_status, callback_reason,
             inbound_call_id, security_tier, greeting_summary, created_at)
            VALUES (:uid, '', :caller, :verified, 'pending', 'reconnect',
                    :icid, 'public', :ctx, NOW())")
           ->execute([
               ':uid' => $clientId,
               ':icid' => $callId,
               ':caller' => $callerNum,
               ':verified' => $callerNum,
               ':ctx' => $contextSummary,
           ]);
        $callbackId = (int)$db->lastInsertId();

        sleep(5);
        executeCallback($callbackId, $db);
        error_log("Alfred auto-recovery: callback scheduled for $callerNum (reason: $endedReason)");
    } catch (Exception $e) {
        error_log('Auto-recovery callback error: ' . $e->getMessage());
    }
}
