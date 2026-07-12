<?php
require_once dirname(__DIR__) . '/includes/api-security.php';
/**
 * Alfred Callback Security System — vapi-callback.php
 * 
 * ARCHITECTURE:
 * ═════════════════════════════════════════════════════════════════════════
 * 
 * OLD FLOW (expensive, slow, less secure):
 *   Customer → 1-833-GOSITEME (toll-free) → press 0 → Alfred answers
 *   → Customer stays on expensive toll-free line for entire conversation
 *   → Auth happens over phone (can be spoofed)
 *   Cost: ~$0.05-0.12/min inbound toll-free + VAPI per-minute fees
 * 
 * NEW FLOW (cheaper, clearer, secure):
 *   Customer → 1-833-GOSITEME (toll-free) → press 0 → Alfred answers
 *   → Alfred greets, answers basic/public questions (domain availability,
 *     pricing, business hours, plan features)
 *   → For ANYTHING requiring auth/account access:
 *     1. Alfred asks: "What's your email address?"
 *     2. Looks up phone number on file
 *     3. Says: "For security, I'll call you right back on the number
 *        ending in XXXX that we have on file. Is that okay?"
 *     4. Hangs up the inbound toll-free call
 *     5. Immediately triggers OUTBOUND call to the verified number
 *     6. Outbound call: Alfred says "Hi [name], this is Alfred calling
 *        you back on your verified number. You're now authenticated.
 *        How can I help?"
 *     7. Full 40-tool access granted (already authenticated by phone match)
 *   Cost: ~$0.01-0.02/min outbound local + VAPI fees (no toll-free premium)
 *
 * SECURITY BENEFITS:
 *   ✓ Phone number on file proves identity (like bank callback verification)
 *   ✓ No PIN/secret needed — the phone itself IS the auth factor
 *   ✓ Cannot be socially engineered (attacker can't receive the callback)
 *   ✓ No toll-free cost for the long conversation portion
 *   ✓ Better audio quality (direct call vs toll-free relay)
 *   ✓ Call log creates an audit trail with verified number
 *
 * ENDPOINTS:
 *   POST /api/vapi-callback.php?action=verify     — Check email, return phone hint
 *   POST /api/vapi-callback.php?action=initiate   — Trigger outbound callback
 *   POST /api/vapi-callback.php?action=status      — Check callback status
 *   POST /api/vapi-callback.php?action=update_phone — Customer updates their number
 *   POST /api/vapi-callback.php?action=log         — Log callback result
 * 
 * DB TABLE: alfred_callbacks (auto-created)
 * ═════════════════════════════════════════════════════════════════════════
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';

// CORS not needed for server-to-server VAPI endpoints
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    { http_response_code(405); exit; }

$action = $_GET['action'] ?? '';
$input  = json_decode(file_get_contents('php://input'), true);

// ═══════════════════════════════════════════════════════════════════════════
// AUTH: Accept VAPI tool call format OR direct API calls
// ═══════════════════════════════════════════════════════════════════════════
$args = [];
$toolCallId = null;

// VAPI tool-call format
if (!empty($input['message']['toolCalls'])) {
    $tc         = $input['message']['toolCalls'][0];
    $toolCallId = $tc['id'] ?? uniqid();
    $args       = $tc['function']['arguments'] ?? [];
    $action     = $action ?: ($args['action'] ?? 'verify');
} else {
    // Direct API call
    $args = $input;
}

// API key auth for direct calls (not VAPI)
// SECURITY: Removed $isVapi = !empty($toolCallId) bypass — toolCallId is user-spoofable
$isServer  = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1', '15.235.50.60']);
$apiKey    = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['HTTP_X_API_KEY'] ?? '';
$apiKey    = preg_replace('/^Bearer\s+/i', '', $apiKey);
$isAuthed  = $isServer || ($apiKey === OUTBOUND_SECRET);

if (!$isAuthed) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// ENSURE DB TABLE EXISTS
// ═══════════════════════════════════════════════════════════════════════════
$db = getDB();
if ($db) {
    $db->exec("CREATE TABLE IF NOT EXISTS alfred_callbacks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL DEFAULT 0,
        email VARCHAR(255) NOT NULL DEFAULT '',
        caller_number VARCHAR(40) NOT NULL DEFAULT '',
        verified_number VARCHAR(40) NOT NULL DEFAULT '',
        callback_status ENUM('pending','calling','connected','failed','completed','expired') DEFAULT 'pending',
        callback_reason VARCHAR(255) DEFAULT '',
        vapi_call_id VARCHAR(100) DEFAULT '',
        inbound_call_id VARCHAR(100) DEFAULT '',
        outbound_call_id VARCHAR(100) DEFAULT '',
        security_tier ENUM('public','verified','full') DEFAULT 'public',
        greeting_summary TEXT,
        callback_summary TEXT,
        cost_inbound DECIMAL(8,4) DEFAULT 0,
        cost_outbound DECIMAL(8,4) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        called_at DATETIME NULL,
        completed_at DATETIME NULL,
        INDEX idx_email (email),
        INDEX idx_client (client_id),
        INDEX idx_status (callback_status),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

// ═══════════════════════════════════════════════════════════════════════════
// ROUTE ACTION
// ═══════════════════════════════════════════════════════════════════════════
try {
    $result = match ($action) {
        'verify'       => actionVerify($args, $db),
        'initiate'     => actionInitiate($args, $db),
        'status'       => actionStatus($args, $db),
        'update_phone' => actionUpdatePhone($args, $db),
        'log'          => actionLog($args, $db),
        'tier_check'   => actionTierCheck($args, $db),
        default        => ['error' => 'Unknown action: ' . $action]
    };
} catch (\Exception $e) {
    error_log('[vapi-callback] ' . $e->getMessage());
    $result = ['error' => 'Internal error'];
}

// Wrap for VAPI tool-call format if needed
if ($toolCallId) {
    echo json_encode(['results' => [[
        'toolCallId' => $toolCallId,
        'result'     => json_encode($result)
    ]]]);
} else {
    echo json_encode($result);
}


// ═══════════════════════════════════════════════════════════════════════════
// ACTION: VERIFY — Look up email, return phone hint + security tier
// ═══════════════════════════════════════════════════════════════════════════
function actionVerify($args, $db) {
    $email       = strtolower(trim($args['email'] ?? ''));
    $callerPhone = trim($args['caller_phone'] ?? '');
    
    if (empty($email)) {
        return [
            'found'   => false,
            'tier'    => 'public',
            'message' => 'I need your email address to look up your account. What email did you sign up with?'
        ];
    }

    if (!$db) return ['found' => false, 'tier' => 'public',
        'message' => 'I am having a technical issue right now. Let me help you with general questions instead.'];

    // Look up client
    $stmt = $db->prepare("
        SELECT c.id, c.firstname, c.lastname, c.email, c.phone, 
               c.city, c.date_created, c.status
        FROM clients c WHERE c.email = :e LIMIT 1
    ");
    $stmt->execute([':e' => $email]);
    $client = $stmt->fetch();

    if (!$client) {
        return [
            'found'   => false,
            'tier'    => 'public',
            'message' => 'I could not find an account with that email address. Could you try another email? Or I can still help you with general questions like domain availability or plan pricing.'
        ];
    }

    if ($client['status'] !== 'Active') {
        return [
            'found'   => true,
            'tier'    => 'public',
            'active'  => false,
            'message' => 'I found an account with that email but it appears to be inactive. Would you like me to create a support ticket to reactivate it?'
        ];
    }

    // Clean phone number
    $storedPhone = preg_replace('/\D/', '', $client['phone'] ?? '');
    $hasPhone    = strlen($storedPhone) >= 7;
    $phoneLast4  = $hasPhone ? substr($storedPhone, -4) : '';
    
    // Check if caller's number matches the number on file
    $callerClean = preg_replace('/\D/', '', $callerPhone);
    $callerMatch = $hasPhone && strlen($callerClean) >= 10 && 
                   str_ends_with($callerClean, substr($storedPhone, -10));

    if (!$hasPhone) {
        return [
            'found'      => true,
            'client_id'  => $client['id'],
            'first_name' => $client['firstname'],
            'tier'       => 'public',
            'has_phone'  => false,
            'message'    => 'I found your account, ' . $client['firstname'] . '! However, we don\'t have a phone number on file for you. For security, I can only help with general questions right now. To get full account access, please add a phone number at gositeme.com/pay in your profile, and next time I\'ll be able to verify you by calling that number back. I can still check domain availability, answer pricing questions, or create a support ticket for you.'
        ];
    }

    // If caller is already on their verified number
    if ($callerMatch) {
        return [
            'found'        => true,
            'client_id'    => $client['id'],
            'first_name'   => $client['firstname'],
            'full_name'    => $client['firstname'] . ' ' . $client['lastname'],
            'tier'         => 'full',
            'caller_match' => true,
            'authenticated'=> true,
            'message'      => 'I see you\'re calling from the phone number on your account. Welcome back, ' . $client['firstname'] . '! You\'re fully verified. How can I help you today?'
        ];
    }

    return [
        'found'       => true,
        'client_id'   => $client['id'],
        'first_name'  => $client['firstname'],
        'tier'        => 'public',
        'has_phone'   => true,
        'phone_hint'  => '****' . $phoneLast4,
        'can_callback'=> true,
        'message'     => 'I found your account, ' . $client['firstname'] . '! I can answer general questions right now. But for anything involving your account — like billing, DNS changes, or support tickets — I\'ll need to verify your identity first. For security, I can call you right back on the number ending in ' . $phoneLast4 . ' that we have on file. The call will be clearer and it\'ll be free for you. Shall I call you back?'
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// ACTION: INITIATE — Trigger the secure outbound callback
// ═══════════════════════════════════════════════════════════════════════════
function actionInitiate($args, $db) {
    $clientId      = (int)($args['client_id'] ?? 0);
    $email         = strtolower(trim($args['email'] ?? ''));
    $inboundCallId = trim($args['inbound_call_id'] ?? '');
    $reason        = trim($args['reason'] ?? 'security_callback');
    $summary       = trim($args['greeting_summary'] ?? '');

    if (!$clientId && !$email) {
        return ['error' => 'Need client_id or email to initiate callback.'];
    }

    if (!$db) return ['error' => 'Database unavailable.'];

    // Resolve client
    if ($clientId) {
        $stmt = $db->prepare("SELECT id, firstname, lastname, email, phone FROM clients WHERE id = :id AND status = 'Active' LIMIT 1");
        $stmt->execute([':id' => $clientId]);
    } else {
        $stmt = $db->prepare("SELECT id, firstname, lastname, email, phone FROM clients WHERE email = :e AND status = 'Active' LIMIT 1");
        $stmt->execute([':e' => $email]);
    }
    $client = $stmt->fetch();

    if (!$client) {
        return ['error' => 'Client not found or inactive.'];
    }

    $phone = preg_replace('/\D/', '', $client['phone']);
    if (strlen($phone) < 7) {
        return ['error' => 'No valid phone number on file for this client.'];
    }

    // Format for VAPI (E.164)
    if (!str_starts_with($phone, '1') && strlen($phone) === 10) {
        $phone = '1' . $phone;
    }
    $phoneE164 = '+' . $phone;

    // Rate limit: max 3 callbacks per client per hour
    $rateCheck = $db->prepare("
        SELECT COUNT(*) as cnt FROM alfred_callbacks 
        WHERE client_id = :cid AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $rateCheck->execute([':cid' => $client['id']]);
    if ($rateCheck->fetch()['cnt'] >= 3) {
        return [
            'rate_limited' => true,
            'message'      => 'For security, we limit callbacks to 3 per hour. Please try again later or visit gositeme.com/pay to manage your account online.'
        ];
    }

    // Create callback record
    $db->prepare("
        INSERT INTO alfred_callbacks 
        (client_id, email, verified_number, callback_status, callback_reason, 
         inbound_call_id, security_tier, greeting_summary, created_at)
        VALUES (:cid, :email, :phone, 'pending', :reason, :inbound, 'verified', :summary, NOW())
    ")->execute([
        ':cid'     => $client['id'],
        ':email'   => $client['email'],
        ':phone'   => $phoneE164,
        ':reason'  => $reason,
        ':inbound' => $inboundCallId,
        ':summary' => $summary
    ]);
    $callbackId = $db->lastInsertId();

    // Build the callback greeting
    $firstName   = $client['firstname'];
    $phoneLast4  = substr($phone, -4);
    $greeting = "Hi $firstName, this is Alfred from GoSiteMe calling you back on your verified number ending in $phoneLast4. "
              . "You're now fully authenticated and I have full access to your account. "
              . "How can I help you today?";

    // ── Trigger VAPI outbound call ──────────────────────────────────
    $VAPI_KEY  = getenv('VAPI_API_KEY') ?: '';
    $ASST_ID   = getenv('VAPI_ASSISTANT_ID') ?: '';
    $PHONE_ID  = getenv('VAPI_PHONE_ID') ?: '';

    $payload = [
        'assistantId' => $ASST_ID,
        'assistant'   => [
            'firstMessage' => $greeting,
            // Pass metadata so the assistant knows this is a verified callback
            'metadata' => [
                'callback_id'    => $callbackId,
                'client_id'      => $client['id'],
                'first_name'     => $firstName,
                'email'          => $client['email'],
                'security_tier'  => 'full',
                'authenticated'  => true,
                'auth_method'    => 'callback_verification',
                'callback_reason'=> $reason,
                'greeting_summary' => $summary
            ]
        ],
        'customer' => [
            'number' => $phoneE164,
            'name'   => $firstName . ' ' . $client['lastname']
        ]
    ];

    if ($PHONE_ID) {
        $payload['phoneNumberId'] = $PHONE_ID;
    }

    $opts = [
        'http' => [
            'method'  => 'POST',
            'header'  => "Authorization: Bearer $VAPI_KEY\r\nContent-Type: application/json\r\n",
            'content' => json_encode($payload),
            'timeout' => 15,
        ],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true]
    ];

    $response = @file_get_contents('https://api.vapi.ai/call/phone', false, stream_context_create($opts));
    $vapiResult = $response ? json_decode($response, true) : null;

    if ($vapiResult && !empty($vapiResult['id'])) {
        // Update callback record with VAPI call ID
        $db->prepare("
            UPDATE alfred_callbacks 
            SET callback_status = 'calling', 
                outbound_call_id = :callid,
                called_at = NOW()
            WHERE id = :id
        ")->execute([':callid' => $vapiResult['id'], ':id' => $callbackId]);

        // Log to activity
        $db->prepare("INSERT INTO activity_log (date, description, user, userid, ipaddress)
            VALUES (NOW(), :desc, 'Alfred AI', 0, '127.0.0.1')"
        )->execute([':desc' => "Alfred callback initiated: {$client['email']} → {$phoneE164} (callback #{$callbackId})"]);

        return [
            'success'     => true,
            'callback_id' => $callbackId,
            'calling'     => $phoneE164,
            'phone_hint'  => '****' . $phoneLast4,
            'message'     => "I'm calling you back now on the number ending in $phoneLast4. Please hang up this call — I'll ring you in just a moment! When I call back, you'll have full account access."
        ];
    }

    // VAPI call failed — update record
    $db->prepare("UPDATE alfred_callbacks SET callback_status = 'failed' WHERE id = :id")
       ->execute([':id' => $callbackId]);

    // Log failure
    $errDetail = $vapiResult ? json_encode($vapiResult) : 'No response from VAPI';
    $db->prepare("INSERT INTO activity_log (date, description, user, userid, ipaddress)
        VALUES (NOW(), :desc, 'Alfred AI', 0, '127.0.0.1')"
    )->execute([':desc' => "Alfred callback FAILED: {$client['email']} — $errDetail"]);

    return [
        'success' => false,
        'message' => "I wasn't able to place the callback right now. Let me create a support ticket instead so our team can call you back within 24 hours. What's the best time to reach you?"
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// ACTION: STATUS — Check callback status (for VAPI assistant context)
// ═══════════════════════════════════════════════════════════════════════════
function actionStatus($args, $db) {
    $callbackId = (int)($args['callback_id'] ?? 0);
    $clientId   = (int)($args['client_id'] ?? 0);

    if (!$db) return ['error' => 'Database unavailable.'];

    if ($callbackId) {
        $stmt = $db->prepare("SELECT * FROM alfred_callbacks WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $callbackId]);
    } elseif ($clientId) {
        $stmt = $db->prepare("SELECT * FROM alfred_callbacks WHERE client_id = :cid ORDER BY id DESC LIMIT 1");
        $stmt->execute([':cid' => $clientId]);
    } else {
        return ['error' => 'Need callback_id or client_id.'];
    }

    $cb = $stmt->fetch();
    if (!$cb) return ['found' => false];

    return [
        'found'        => true,
        'callback_id'  => $cb['id'],
        'status'       => $cb['callback_status'],
        'security_tier'=> $cb['security_tier'],
        'created_at'   => $cb['created_at'],
        'called_at'    => $cb['called_at'],
        'completed_at' => $cb['completed_at']
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// ACTION: TIER_CHECK — Quick check: what tier is this caller at?
// ═══════════════════════════════════════════════════════════════════════════
function actionTierCheck($args, $db) {
    $clientId = (int)($args['client_id'] ?? 0);
    $vapiCallId = trim($args['call_id'] ?? '');

    if (!$db) return ['tier' => 'public'];

    // Check if this is a callback call (outbound)
    if ($vapiCallId) {
        $stmt = $db->prepare("
            SELECT * FROM alfred_callbacks 
            WHERE outbound_call_id = :callid AND callback_status IN ('calling','connected')
            LIMIT 1
        ");
        $stmt->execute([':callid' => $vapiCallId]);
        $cb = $stmt->fetch();

        if ($cb) {
            // This IS a callback — full access
            $db->prepare("UPDATE alfred_callbacks SET callback_status = 'connected' WHERE id = :id")
               ->execute([':id' => $cb['id']]);
            return [
                'tier'         => 'full',
                'client_id'    => $cb['client_id'],
                'authenticated'=> true,
                'auth_method'  => 'callback_verification',
                'callback_id'  => $cb['id']
            ];
        }
    }

    return ['tier' => 'public', 'authenticated' => false];
}


// ═══════════════════════════════════════════════════════════════════════════
// ACTION: UPDATE_PHONE — Let customer add/update phone for future callbacks
// ═══════════════════════════════════════════════════════════════════════════
function actionUpdatePhone($args, $db) {
    $clientId = (int)($args['client_id'] ?? 0);
    $newPhone = preg_replace('/\D/', '', trim($args['phone'] ?? ''));

    if (!$clientId) return ['error' => 'Client ID required.'];
    if (strlen($newPhone) < 10) return ['error' => 'Please provide a valid 10-digit phone number.'];

    if (!$db) return ['error' => 'Database unavailable.'];

    // Format nicely
    if (strlen($newPhone) === 10) {
        $formatted = '+1' . $newPhone;
    } elseif (strlen($newPhone) === 11 && str_starts_with($newPhone, '1')) {
        $formatted = '+' . $newPhone;
    } else {
        $formatted = '+' . $newPhone;
    }

    // Update client record
    $db->prepare("UPDATE clients SET phone = :phone WHERE id = :id")
       ->execute([':phone' => $formatted, ':id' => $clientId]);

    $db->prepare("INSERT INTO activity_log (date, description, user, userid, ipaddress)
        VALUES (NOW(), :desc, 'Alfred AI', :uid, '127.0.0.1')"
    )->execute([
        ':desc' => "Phone updated via Alfred callback security: $formatted",
        ':uid'  => $clientId
    ]);

    return [
        'success'    => true,
        'phone_hint' => '****' . substr($newPhone, -4),
        'message'    => 'Your phone number has been updated. Next time you call, I can verify you by calling back this number.'
    ];
}


// ═══════════════════════════════════════════════════════════════════════════
// ACTION: LOG — Log callback completion (called by VAPI end-of-call webhook)
// ═══════════════════════════════════════════════════════════════════════════
function actionLog($args, $db) {
    $callbackId    = (int)($args['callback_id'] ?? 0);
    $outboundCallId= trim($args['outbound_call_id'] ?? '');
    $status        = trim($args['status'] ?? 'completed');
    $summary       = trim($args['summary'] ?? '');
    $costOutbound  = (float)($args['cost'] ?? 0);

    if (!$db) return ['error' => 'Database unavailable.'];

    $where = '';
    $params = [':status' => $status, ':summary' => $summary, ':cost' => $costOutbound];

    if ($callbackId) {
        $where = 'id = :id';
        $params[':id'] = $callbackId;
    } elseif ($outboundCallId) {
        $where = 'outbound_call_id = :callid';
        $params[':callid'] = $outboundCallId;
    } else {
        return ['error' => 'Need callback_id or outbound_call_id.'];
    }

    $db->prepare("
        UPDATE alfred_callbacks 
        SET callback_status = :status, 
            callback_summary = :summary,
            cost_outbound = :cost,
            completed_at = NOW()
        WHERE $where
    ")->execute($params);

    return ['success' => true, 'logged' => true];
}
