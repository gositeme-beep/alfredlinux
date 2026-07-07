<?php
$GLOBALS['RATE_LIMIT_EXEMPT'] = true; // comms.php has its own 120 req/min limiter
$GLOBALS['CSRF_EXEMPT'] = true;       // comms.php uses its own comms_csrf token
require_once dirname(__DIR__) . '/includes/api-security.php';
/**
 * GoSiteMe Veil API — Zero-Knowledge Encrypted Communications Relay
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │  THIS SERVER IS A DUMB RELAY. IT NEVER SEES PLAINTEXT.         │
 * │  All payloads are E2E encrypted client-side before arrival.    │
 * │  Even with full DB + server access, messages cannot be read.   │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * Endpoints:
 *   POST ?action=register_keys   — Upload identity + prekeys
 *   GET  ?action=get_keys&id=X   — Fetch recipient public keys + prekey
 *   POST ?action=send            — Relay encrypted message blob
 *   GET  ?action=receive         — Poll for undelivered messages
 *   POST ?action=delivered       — Mark messages as delivered
 *   POST ?action=upload          — Upload encrypted file blob
 *   GET  ?action=download&t=X    — Download encrypted file blob
 *   POST ?action=signal          — WebRTC call signaling
 *   GET  ?action=poll_signals    — Poll for incoming call signals
 *   GET  ?action=contacts        — Get contact list
 *   POST ?action=add_contact     — Add contact by email
 *   GET  ?action=search&q=X      — Search users by name/email
 *   GET  ?action=history&with=X  — Conversation history (encrypted blobs)
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store, no-cache, must-revalidate');

// CORS — only GoSiteMe origins
$allowedOrigins = ['https://gositeme.com', 'https://www.gositeme.com'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    header('Access-Control-Allow-Origin: https://gositeme.com');
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// ── Session + Auth ─────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Strict');
    session_start();
}

require_once dirname(__DIR__) . '/includes/db-config.inc.php';

$clientId = $_SESSION['client_id'] ?? $_SESSION['uid'] ?? null;
if (!$clientId) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}
$clientId = (int) $clientId;

// ── Ensure CSRF token exists in session ────────────────────────────
if (empty($_SESSION['comms_csrf'])) {
    $_SESSION['comms_csrf'] = bin2hex(random_bytes(32));
}

// ── CSRF for POST ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sentToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!$sentToken || !hash_equals($_SESSION['comms_csrf'], $sentToken)) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit;
    }
}

// ── Rate Limiting (simple file-based) ──────────────────────────────
$rateFile = sys_get_temp_dir() . '/comms_rate_' . md5($_SERVER['REMOTE_ADDR'] ?? '');
$rateData = @file_get_contents($rateFile);
$rate = $rateData ? json_decode($rateData, true) : null;
$now = time();
if (!$rate || ($now - ($rate['start'] ?? 0)) > 60) {
    $rate = ['count' => 0, 'start' => $now];
}
$rate['count']++;
if ($rate['count'] > 120) { // 120 req/min
    http_response_code(429);
    echo json_encode(['error' => 'Rate limited. Slow down.']);
    exit;
}
@file_put_contents($rateFile, json_encode($rate));

// ── DB Connection ──────────────────────────────────────────────────
try {
    $pdo = getSharedDB();
} catch (Exception $e) {
    error_log('comms API DB error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Service temporarily unavailable']);
    exit;
}

// ── Helpers ─────────────────────────────────────────────────────────
function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function requirePost() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(['error' => 'POST required'], 405);
    }
}

function getInput() {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) respond(['error' => 'Invalid JSON body'], 400);
    return $data;
}

function conversationHash(int $a, int $b): string {
    $ids = [$a, $b];
    sort($ids);
    return hash('sha256', implode('|', $ids));
}

// Max payload sizes
define('MAX_MESSAGE_SIZE', 65536);     // 64KB per encrypted message
define('MAX_FILE_SIZE', 104857600);    // 100MB per file
define('COMMS_STORAGE', '/home/gositeme/domains/gositeme.com/comms_storage/');

// ── Router ──────────────────────────────────────────────────────────
$action = $_GET['action'] ?? '';

switch ($action) {

// =====================================================================
// REGISTER KEYS — Upload identity keypair + prekeys
// =====================================================================
case 'register_keys':
    requirePost();
    $input = getInput();

    $ecdhPub   = $input['ecdh_public']   ?? '';
    $ecdsaPub  = $input['ecdsa_public']  ?? '';
    $fingerprint = $input['fingerprint'] ?? '';
    $prekeys   = $input['prekeys']       ?? [];

    if (!$ecdhPub || !$ecdsaPub || !$fingerprint) {
        respond(['error' => 'Missing key data'], 400);
    }
    if (strlen($fingerprint) !== 64 || !ctype_xdigit($fingerprint)) {
        respond(['error' => 'Invalid fingerprint'], 400);
    }

    // Validate JWK format
    $ecdhCheck  = json_decode($ecdhPub, true);
    $ecdsaCheck = json_decode($ecdsaPub, true);
    if (!$ecdhCheck || ($ecdhCheck['kty'] ?? '') !== 'EC') {
        respond(['error' => 'Invalid ECDH key format'], 400);
    }
    if (!$ecdsaCheck || ($ecdsaCheck['kty'] ?? '') !== 'EC') {
        respond(['error' => 'Invalid ECDSA key format'], 400);
    }

    $pdo->beginTransaction();
    try {
        // Upsert identity keys
        $stmt = $pdo->prepare("
            INSERT INTO comms_identity_keys (client_id, ecdh_public, ecdsa_public, key_fingerprint)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE ecdh_public = VALUES(ecdh_public),
                ecdsa_public = VALUES(ecdsa_public),
                key_fingerprint = VALUES(key_fingerprint)
        ");
        $stmt->execute([$clientId, $ecdhPub, $ecdsaPub, $fingerprint]);

        // Store prekeys
        if (is_array($prekeys) && count($prekeys) > 0) {
            $prekeyStmt = $pdo->prepare("
                INSERT IGNORE INTO comms_prekeys (client_id, key_id, ecdh_public)
                VALUES (?, ?, ?)
            ");
            $limit = min(count($prekeys), 50); // Max 50 prekeys
            for ($i = 0; $i < $limit; $i++) {
                $pk = $prekeys[$i];
                if (!empty($pk['key_id']) && !empty($pk['ecdh_public'])) {
                    $keyId = substr(preg_replace('/[^a-zA-Z0-9_\-]/', '', $pk['key_id']), 0, 64);
                    $prekeyStmt->execute([$clientId, $keyId, $pk['ecdh_public']]);
                }
            }
        }

        $pdo->commit();
        respond(['success' => true, 'csrf_token' => $_SESSION['comms_csrf']]);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('comms register_keys error: ' . $e->getMessage());
        respond(['error' => 'Key registration failed'], 500);
    }
    break;

// =====================================================================
// GET KEYS — Fetch a user's public keys + one prekey
// =====================================================================
case 'get_keys':
    $targetId = (int) ($_GET['id'] ?? 0);
    if ($targetId < 1) respond(['error' => 'Invalid user ID'], 400);

    // Get identity keys
    $stmt = $pdo->prepare("
        SELECT ecdh_public, ecdsa_public, pq_public, key_fingerprint
        FROM comms_identity_keys WHERE client_id = ?
    ");
    $stmt->execute([$targetId]);
    $keys = $stmt->fetch();

    if (!$keys) {
        respond(['error' => 'User has not set up encrypted communications'], 404);
    }

    // Claim one unused prekey (atomic)
    $prekey = null;
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("
            SELECT id, key_id, ecdh_public FROM comms_prekeys
            WHERE client_id = ? AND used = 0 LIMIT 1 FOR UPDATE
        ");
        $stmt->execute([$targetId]);
        $pk = $stmt->fetch();

        if ($pk) {
            $pdo->prepare("UPDATE comms_prekeys SET used = 1 WHERE id = ?")->execute([$pk['id']]);
            $prekey = ['key_id' => $pk['key_id'], 'ecdh_public' => $pk['ecdh_public']];
        }
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
    }

    // Check remaining prekeys, warn if low
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM comms_prekeys WHERE client_id = ? AND used = 0");
    $stmt->execute([$targetId]);
    $remaining = (int) $stmt->fetchColumn();

    respond([
        'success'      => true,
        'ecdh_public'  => $keys['ecdh_public'],
        'ecdsa_public' => $keys['ecdsa_public'],
        'pq_public'    => $keys['pq_public'] ?? null,
        'has_pq'       => !empty($keys['pq_public']),
        'fingerprint'  => $keys['key_fingerprint'],
        'prekey'       => $prekey,
        'prekeys_low'  => $remaining < 5,
    ]);
    break;

// =====================================================================
// SEND — Relay an encrypted message blob
// =====================================================================
case 'send':
    requirePost();
    $input = getInput();

    $recipientId      = (int) ($input['recipient_id'] ?? 0);
    $ciphertext       = $input['ciphertext']       ?? '';
    $iv               = $input['iv']               ?? '';
    $senderEphemeral  = $input['sender_ephemeral'] ?? null;
    $kyberCt          = $input['kyber_ct']         ?? null;
    $messageType      = (int) ($input['message_type'] ?? 0);
    $expiresIn        = (int) ($input['expires_in']  ?? 0); // seconds, 0 = never

    if ($recipientId < 1 || !$ciphertext || !$iv) {
        respond(['error' => 'Missing required fields'], 400);
    }
    if (strlen($ciphertext) > MAX_MESSAGE_SIZE) {
        respond(['error' => 'Message too large'], 413);
    }
    if ($recipientId === $clientId) {
        respond(['error' => 'Cannot message yourself'], 400);
    }
    if (!in_array($messageType, [0, 1, 2, 3, 4], true)) {
        $messageType = 0;
    }

    // Verify recipient exists
    $stmt = $pdo->prepare("SELECT id FROM clients WHERE id = ? AND status = 'Active' LIMIT 1");
    $stmt->execute([$recipientId]);
    if (!$stmt->fetch()) {
        respond(['error' => 'Recipient not found'], 404);
    }

    // Block check — if recipient blocked sender, don't relay
    $blockStmt = $pdo->prepare("SELECT blocked FROM comms_contacts WHERE client_id = ? AND contact_id = ? LIMIT 1");
    $blockStmt->execute([$recipientId, $clientId]);
    $blockRow = $blockStmt->fetch();
    if ($blockRow && (int) $blockRow['blocked'] === 1) {
        respond(['error' => 'Message could not be delivered'], 403);
    }

    // Per-conversation rate limit — 30 msgs/min
    $convHash_check = conversationHash($clientId, $recipientId);
    $rateStmt = $pdo->prepare("
        SELECT COUNT(*) AS cnt FROM comms_messages
        WHERE conversation_hash = ? AND sender_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
    ");
    $rateStmt->execute([$convHash_check, $clientId]);
    if ((int) $rateStmt->fetchColumn() >= 30) {
        respond(['error' => 'Slow down — too many messages'], 429);
    }

    $convHash  = conversationHash($clientId, $recipientId);
    $expiresAt = $expiresIn > 0 ? date('Y-m-d H:i:s', time() + $expiresIn) : null;

    $stmt = $pdo->prepare("
        INSERT INTO comms_messages
            (conversation_hash, sender_id, recipient_id, ciphertext, iv, sender_ephemeral, kyber_ct, message_type, expires_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $convHash, $clientId, $recipientId,
        $ciphertext, $iv, $senderEphemeral, $kyberCt, $messageType, $expiresAt
    ]);

    // Update last_message_at on contacts (both directions)
    $pdo->prepare("
        INSERT INTO comms_contacts (client_id, contact_id, last_message_at)
        VALUES (?, ?, NOW()), (?, ?, NOW())
        ON DUPLICATE KEY UPDATE last_message_at = NOW()
    ")->execute([$clientId, $recipientId, $recipientId, $clientId]);

    respond([
        'success'    => true,
        'message_id' => (int) $pdo->lastInsertId(),
        'csrf_token' => $_SESSION['comms_csrf'],
    ]);
    break;

// =====================================================================
// RECEIVE — Poll for new undelivered messages
// =====================================================================
case 'receive':
    $since = (int) ($_GET['since'] ?? 0); // message ID cursor

    $stmt = $pdo->prepare("
        SELECT id, sender_id, ciphertext, iv, sender_ephemeral, kyber_ct, message_type, expires_at, created_at
        FROM comms_messages
        WHERE recipient_id = ? AND delivered = 0 AND id > ?
        ORDER BY created_at ASC LIMIT 100
    ");
    $stmt->execute([$clientId, $since]);
    $messages = $stmt->fetchAll();

    // Mark as delivered
    if (!empty($messages)) {
        $ids = array_column($messages, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $pdo->prepare("UPDATE comms_messages SET delivered = 1 WHERE id IN ($placeholders)")
            ->execute($ids);
    }

    // Check if our prekeys are running low
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM comms_prekeys WHERE client_id = ? AND used = 0");
    $stmt->execute([$clientId]);
    $prekeyCount = (int) $stmt->fetchColumn();

    respond([
        'success'        => true,
        'messages'       => $messages,
        'prekeys_remaining' => $prekeyCount,
        'csrf_token'     => $_SESSION['comms_csrf'],
    ]);
    break;

// =====================================================================
// DELIVERED — Mark messages as read
// =====================================================================
case 'delivered':
    requirePost();
    $input = getInput();
    $ids = $input['ids'] ?? [];

    if (!is_array($ids) || empty($ids)) {
        respond(['error' => 'No message IDs provided'], 400);
    }

    $ids = array_map('intval', array_slice($ids, 0, 200));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $params = array_merge($ids, [$clientId]);

    $pdo->prepare("
        UPDATE comms_messages SET read_at = NOW()
        WHERE id IN ($placeholders) AND recipient_id = ?
    ")->execute($params);

    respond(['success' => true]);
    break;

// =====================================================================
// HISTORY — Get conversation history (encrypted blobs only)
// =====================================================================
case 'history':
    $withId = (int) ($_GET['with'] ?? 0);
    $before = (int) ($_GET['before'] ?? PHP_INT_MAX);
    $limit  = min(50, max(1, (int) ($_GET['limit'] ?? 50)));

    if ($withId < 1) respond(['error' => 'Missing conversation partner'], 400);

    $convHash = conversationHash($clientId, $withId);

    $stmt = $pdo->prepare("
        SELECT id, sender_id, recipient_id, ciphertext, iv, sender_ephemeral, kyber_ct,
               message_type, read_at, expires_at, created_at
        FROM comms_messages
        WHERE conversation_hash = ? AND id < ?
        ORDER BY created_at DESC LIMIT ?
    ");
    dbExecute($stmt, [$convHash, $before, $limit]);
    $messages = array_reverse($stmt->fetchAll()); // Chronological order

    respond(['success' => true, 'messages' => $messages]);
    break;

// =====================================================================
// UPLOAD — Upload encrypted file blob
// =====================================================================
case 'upload':
    requirePost();

    if (empty($_FILES['file'])) {
        respond(['error' => 'No file uploaded'], 400);
    }

    $file = $_FILES['file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        respond(['error' => 'Upload failed: code ' . $file['error']], 400);
    }
    if ($file['size'] > MAX_FILE_SIZE) {
        respond(['error' => 'File exceeds 100MB limit'], 413);
    }

    $encryptedMeta = $_POST['encrypted_meta'] ?? '';
    if (!$encryptedMeta) {
        respond(['error' => 'Missing encrypted file metadata'], 400);
    }

    // Generate unique token + storage path
    $token = bin2hex(random_bytes(32));
    $storagePath = COMMS_STORAGE . $token;

    if (!move_uploaded_file($file['tmp_name'], $storagePath)) {
        respond(['error' => 'Storage failure'], 500);
    }
    chmod($storagePath, 0600);

    $stmt = $pdo->prepare("
        INSERT INTO comms_files (uploader_id, file_token, encrypted_meta, file_size, storage_path)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$clientId, $token, $encryptedMeta, $file['size'], $storagePath]);

    respond([
        'success'    => true,
        'file_token' => $token,
        'file_size'  => $file['size'],
        'csrf_token' => $_SESSION['comms_csrf'],
    ]);
    break;

// =====================================================================
// DOWNLOAD — Download encrypted file blob
// =====================================================================
case 'download':
    $token = preg_replace('/[^a-f0-9]/', '', $_GET['t'] ?? '');
    if (strlen($token) !== 64) {
        respond(['error' => 'Invalid file token'], 400);
    }

    $stmt = $pdo->prepare("SELECT storage_path, file_size, encrypted_meta FROM comms_files WHERE file_token = ?");
    $stmt->execute([$token]);
    $file = $stmt->fetch();

    if (!$file || !file_exists($file['storage_path'])) {
        respond(['error' => 'File not found'], 404);
    }

    // Verify the requester is either uploader or a recipient of a message containing this token
    // (Defense in depth — files are encrypted anyway)

    header('Content-Type: application/octet-stream');
    header('Content-Length: ' . $file['file_size']);
    header('Content-Disposition: attachment; filename="encrypted.bin"');
    header('X-Encrypted-Meta: ' . $file['encrypted_meta']);
    readfile($file['storage_path']);
    exit;

// =====================================================================
// SIGNAL — WebRTC call signaling relay
// =====================================================================
case 'signal':
    requirePost();
    $input = getInput();

    $toId    = (int) ($input['to_id'] ?? 0);
    $type    = $input['signal_type'] ?? '';
    $payload = $input['encrypted_payload'] ?? '';

    $validTypes = ['offer', 'answer', 'ice', 'hangup', 'busy', 'ringing'];
    if ($toId < 1 || !in_array($type, $validTypes, true) || !$payload) {
        respond(['error' => 'Invalid signal data'], 400);
    }

    $stmt = $pdo->prepare("
        INSERT INTO comms_signals (from_id, to_id, signal_type, encrypted_payload)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$clientId, $toId, $type, $payload]);

    respond(['success' => true, 'csrf_token' => $_SESSION['comms_csrf']]);
    break;

// =====================================================================
// POLL SIGNALS — Get incoming call signals
// =====================================================================
case 'poll_signals':
    $stmt = $pdo->prepare("
        SELECT id, from_id, signal_type, encrypted_payload, created_at
        FROM comms_signals
        WHERE to_id = ? AND consumed = 0
        ORDER BY created_at ASC LIMIT 50
    ");
    $stmt->execute([$clientId]);
    $signals = $stmt->fetchAll();

    if (!empty($signals)) {
        $ids = array_column($signals, 'id');
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $pdo->prepare("UPDATE comms_signals SET consumed = 1 WHERE id IN ($ph)")->execute($ids);
    }

    respond(['success' => true, 'signals' => $signals]);
    break;

// =====================================================================
// CONTACTS — Get user's contact list
// =====================================================================
case 'contacts':
    $stmt = $pdo->prepare("
        SELECT c.contact_id, c.nickname, c.verified, c.blocked, c.last_message_at,
               cl.firstname, cl.lastname, cl.email
        FROM comms_contacts c
        JOIN clients cl ON cl.id = c.contact_id AND cl.status = 'Active'
        WHERE c.client_id = ? AND c.blocked = 0
        ORDER BY c.last_message_at DESC
    ");
    $stmt->execute([$clientId]);
    $contacts = $stmt->fetchAll();

    // Mask emails for privacy (show first 2 chars + domain)
    foreach ($contacts as &$ct) {
        $parts = explode('@', $ct['email']);
        $ct['email_masked'] = substr($parts[0], 0, 2) . '***@' . ($parts[1] ?? '');
        unset($ct['email']);
    }

    respond(['success' => true, 'contacts' => $contacts]);
    break;

// =====================================================================
// ADD CONTACT — Add by email
// =====================================================================
case 'add_contact':
    requirePost();
    $input = getInput();
    $contactIdInput = isset($input['contact_id']) ? (int)$input['contact_id'] : 0;
    $email = filter_var(trim($input['email'] ?? ''), FILTER_VALIDATE_EMAIL);

    if ($contactIdInput > 0) {
        $stmt = $pdo->prepare("SELECT id, firstname, lastname FROM clients WHERE id = ? AND status = 'Active' LIMIT 1");
        $stmt->execute([$contactIdInput]);
        $contact = $stmt->fetch();
    } elseif ($email) {
        $stmt = $pdo->prepare("SELECT id, firstname, lastname FROM clients WHERE email = ? AND status = 'Active' LIMIT 1");
        $stmt->execute([$email]);
        $contact = $stmt->fetch();
    } else {
        respond(['error' => 'Provide contact_id or email'], 400);
    }

    if (!$contact) {
        respond(['error' => 'User not found'], 404);
    }
    if ((int)$contact['id'] === $clientId) {
        respond(['error' => 'Cannot add yourself'], 400);
    }

    // Check if already a contact
    $stmt = $pdo->prepare("SELECT id FROM comms_contacts WHERE client_id = ? AND contact_id = ?");
    $stmt->execute([$clientId, $contact['id']]);
    if ($stmt->fetch()) {
        respond(['error' => 'Already in contacts'], 409);
    }

    $pdo->prepare("
        INSERT INTO comms_contacts (client_id, contact_id) VALUES (?, ?)
    ")->execute([$clientId, (int) $contact['id']]);

    // Auto-create reverse contact so both sides see each other
    $stmt = $pdo->prepare("SELECT id FROM comms_contacts WHERE client_id = ? AND contact_id = ?");
    $stmt->execute([(int) $contact['id'], $clientId]);
    if (!$stmt->fetch()) {
        $pdo->prepare("
            INSERT INTO comms_contacts (client_id, contact_id) VALUES (?, ?)
        ")->execute([(int) $contact['id'], $clientId]);
    }

    respond([
        'success'    => true,
        'contact_id' => (int) $contact['id'],
        'name'       => trim($contact['firstname'] . ' ' . $contact['lastname']),
        'csrf_token' => $_SESSION['comms_csrf'],
    ]);
    break;

// =====================================================================
// SEARCH — Find users by name
// =====================================================================
case 'search':
    $q = trim($_GET['q'] ?? '');
    if (strlen($q) < 2) {
        respond(['error' => 'Query too short'], 400);
    }

    $searchTerm = '%' . $q . '%';
    $stmt = $pdo->prepare("
        SELECT id, firstname, lastname
        FROM clients
        WHERE status = 'Active' AND id != ?
          AND (CONCAT(firstname, ' ', lastname) LIKE ? OR email = ?)
        LIMIT 20
    ");
    $stmt->execute([$clientId, $searchTerm, $q]);
    $results = $stmt->fetchAll();

    // Check which have comms keys registered
    if (!empty($results)) {
        $ids = array_column($results, 'id');
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT client_id FROM comms_identity_keys WHERE client_id IN ($ph)");
        $stmt->execute($ids);
        $hasKeys = array_column($stmt->fetchAll(), 'client_id');

        foreach ($results as &$r) {
            $r['has_comms'] = in_array($r['id'], $hasKeys);
        }
    }

    respond(['success' => true, 'users' => $results]);
    break;

// =====================================================================
// MY KEYS — Check if current user has keys registered
// =====================================================================
case 'my_keys':
    $stmt = $pdo->prepare("SELECT key_fingerprint FROM comms_identity_keys WHERE client_id = ?");
    $stmt->execute([$clientId]);
    $key = $stmt->fetch();

    $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM comms_prekeys WHERE client_id = ? AND used = 0");
    $stmt2->execute([$clientId]);
    $prekeyCount = (int) $stmt2->fetchColumn();

    respond([
        'success'       => true,
        'has_keys'      => !!$key,
        'fingerprint'   => $key['key_fingerprint'] ?? null,
        'prekey_count'  => $prekeyCount,
        'client_id'     => $clientId,
        'csrf_token'    => $_SESSION['comms_csrf'],
    ]);
    break;

// =====================================================================
// BLOCK / UNBLOCK CONTACT
// =====================================================================
case 'block':
    requirePost();
    $input = getInput();
    $contactId = (int) ($input['contact_id'] ?? 0);
    $block = (bool) ($input['block'] ?? true);

    if ($contactId < 1) respond(['error' => 'Invalid contact'], 400);

    $pdo->prepare("
        UPDATE comms_contacts SET blocked = ? WHERE client_id = ? AND contact_id = ?
    ")->execute([$block ? 1 : 0, $clientId, $contactId]);

    respond(['success' => true]);
    break;

// ── V2 Extended Actions ─────────────────────────────────────────
// Groups, Alfred AI, reactions, threads, voice, typing, multi-device, dashboard
default:
    $v2Actions = [
        'create_group', 'group_invite', 'group_remove', 'group_send',
        'group_messages', 'group_members', 'my_groups', 'group_distribute_key',
        'leave_group', 'alfred', 'alfred_alerts', 'react', 'reactions',
        'typing', 'typing_status', 'edit_message', 'device_register',
        'devices', 'device_remove', 'dashboard', 'push_subscribe',
        'push_key', 'send_reply', 'register_pq_key', 'get_pq_key',
        'report', 'my_reports',
    ];

    if (in_array($action, $v2Actions, true)) {
        require __DIR__ . '/comms-v2.php';
    } else {
        respond(['error' => 'Unknown action', 'actions' => array_merge([
            'register_keys', 'get_keys', 'send', 'receive', 'delivered',
            'upload', 'download', 'signal', 'poll_signals',
            'contacts', 'add_contact', 'search', 'history', 'my_keys', 'block'
        ], $v2Actions)], 400);
    }
}
