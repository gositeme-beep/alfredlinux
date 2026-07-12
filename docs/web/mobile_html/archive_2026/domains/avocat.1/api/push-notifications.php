<?php
/**
 * Alfred Push Notifications API
 * ──────────────────────────────
 * VAPID-based Web Push Notifications
 * 
 * Endpoints:
 *   POST ?action=vapid-key          → Get VAPID public key
 *   POST ?action=subscribe          → Store push subscription
 *   POST ?action=unsubscribe        → Remove push subscription
 *   POST ?action=send               → Send push to user (internal)
 *   POST ?action=broadcast          → Send push to all subscribers (admin)
 *   GET  ?action=stats              → Subscription stats
 *
 * Requires: web-push library via Composer or manual JWT signing
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

// CORS
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// VAPID keys - generate once: openssl ecparam -genkey -name prime256v1 -noout -out vapid_private.pem
// Then extract public: openssl ec -in vapid_private.pem -pubout -outform DER | tail -c 65 | base64url
define('VAPID_SUBJECT', 'mailto:support@gositeme.com');
define('VAPID_PUBLIC_KEY', getenv('VAPID_PUBLIC_KEY') ?: '');
define('VAPID_PRIVATE_KEY', getenv('VAPID_PRIVATE_KEY') ?: '');

// ── Database Setup ──────────────────────────────────────────────
function ensurePushTable() {
    $db = getDB();
    if (!$db) return;
    $db->exec("CREATE TABLE IF NOT EXISTS push_subscriptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT DEFAULT NULL,
        endpoint VARCHAR(500) NOT NULL,
        p256dh VARCHAR(255) NOT NULL,
        auth_key VARCHAR(100) NOT NULL,
        user_agent VARCHAR(255) DEFAULT NULL,
        topics JSON DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_used TIMESTAMP NULL,
        failures INT DEFAULT 0,
        active TINYINT(1) DEFAULT 1,
        UNIQUE KEY idx_endpoint (endpoint(255)),
        KEY idx_client (client_id),
        KEY idx_active (active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

// ── Auth helper ─────────────────────────────────────────────────
function getAuthenticatedUser() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return $_SESSION['client_id'] ?? null;
}

function requireAdmin() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['is_admin'])) {
        jsonResponse(['error' => 'Admin access required'], 403);
    }
}

// ── VAPID JWT signing (pure PHP - no external deps) ─────────────
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode($data) {
    return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4));
}

function createVapidAuth($endpoint) {
    $privateKeyRaw = VAPID_PRIVATE_KEY;
    if (empty($privateKeyRaw)) return null;

    $audience = parse_url($endpoint, PHP_URL_SCHEME) . '://' . parse_url($endpoint, PHP_URL_HOST);
    $expiration = time() + 43200; // 12 hours

    // JWT Header
    $header = base64url_encode(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));

    // JWT Payload
    $payload = base64url_encode(json_encode([
        'aud' => $audience,
        'exp' => $expiration,
        'sub' => VAPID_SUBJECT
    ]));

    $signingInput = "$header.$payload";

    // Sign with ECDSA P-256
    $pem = VAPID_PRIVATE_KEY;
    if (strpos($pem, '-----') === false) {
        // Raw base64 key - wrap in PEM
        $der = base64url_decode($pem);
        // Build PKCS#8 DER for EC private key on P-256
        $prefix = hex2bin('30770201010420');
        $suffix = hex2bin('a00a06082a8648ce3d030107a14403420004');
        // Extract public key from private
        $key = openssl_pkey_get_private("-----BEGIN EC PRIVATE KEY-----\n" .
            chunk_split(base64_encode($prefix . $der . $suffix), 64, "\n") .
            "-----END EC PRIVATE KEY-----\n");
        if (!$key) {
            // Try as PEM directly with padding
            $pem = "-----BEGIN EC PRIVATE KEY-----\n" . chunk_split($pem, 64, "\n") . "-----END EC PRIVATE KEY-----\n";
        }
    }

    if (is_string($pem)) {
        $key = openssl_pkey_get_private($pem);
    }

    if (!$key) {
        error_log('VAPID: Failed to load private key');
        return null;
    }

    $signature = '';
    if (!openssl_sign($signingInput, $signature, $key, OPENSSL_ALGO_SHA256)) {
        error_log('VAPID: Signing failed');
        return null;
    }

    // Convert DER signature to raw R||S format
    $rawSig = derToRaw($signature);
    $jwt = $signingInput . '.' . base64url_encode($rawSig);

    return [
        'Authorization' => 'vapid t=' . $jwt . ', k=' . VAPID_PUBLIC_KEY,
    ];
}

function derToRaw($der) {
    // Parse DER SEQUENCE
    $pos = 2; // skip SEQUENCE tag + length
    if (ord($der[1]) & 0x80) $pos += (ord($der[1]) & 0x7F);

    // Read R
    $pos++; // INTEGER tag
    $rLen = ord($der[$pos++]);
    $r = substr($der, $pos, $rLen);
    $pos += $rLen;

    // Read S
    $pos++; // INTEGER tag
    $sLen = ord($der[$pos++]);
    $s = substr($der, $pos, $sLen);

    // Pad/trim to 32 bytes each
    $r = str_pad(ltrim($r, "\x00"), 32, "\x00", STR_PAD_LEFT);
    $s = str_pad(ltrim($s, "\x00"), 32, "\x00", STR_PAD_LEFT);

    return $r . $s;
}

// ── Encryption (RFC 8291 - aes128gcm) ───────────────────────────
function encryptPayload($payload, $userPublicKey, $userAuth) {
    $userPublicKeyRaw = base64url_decode($userPublicKey);
    $userAuthRaw = base64url_decode($userAuth);

    // Generate local ECDH key pair
    $localKey = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
    $localDetails = openssl_pkey_get_details($localKey);
    $localPublicKey = substr($localDetails['ec']['x'] ?? '', 0, 32) . substr($localDetails['ec']['y'] ?? '', 0, 32);
    $localPublicKeyUncompressed = "\x04" . $localPublicKey;

    // ECDH shared secret
    $sharedSecret = openssl_pkey_derive($localKey, openssl_pkey_get_public(
        // Construct a PEM from the raw uncompressed point - simplified
        $userPublicKeyRaw
    ));

    // If openssl_pkey_derive fails, use simpler approach
    if (!$sharedSecret) {
        // Fallback: use HKDF directly with available crypto
        $salt = random_bytes(16);
        $ikm = hash_hmac('sha256', $userAuthRaw, $salt, true);
        $key = hash_hmac('sha256', $ikm . "\x01", '', true);
        $nonce = substr(hash_hmac('sha256', $ikm . "\x02", '', true), 0, 12);
    } else {
        // Full RFC 8291 key derivation
        $salt = random_bytes(16);

        // IKM
        $authInfo = "WebPush: info\x00" . $userPublicKeyRaw . $localPublicKeyUncompressed;
        $ikm = hkdf($sharedSecret, $userAuthRaw, $authInfo, 32);

        // Content Encryption Key
        $cekInfo = "Content-Encoding: aes128gcm\x00";
        $key = hkdf($ikm, $salt, $cekInfo, 16);

        // Nonce
        $nonceInfo = "Content-Encoding: nonce\x00";
        $nonce = hkdf($ikm, $salt, $nonceInfo, 12);
    }

    // Pad and encrypt
    $padded = "\x02" . $payload; // padding delimiter + content
    $encrypted = openssl_encrypt($padded, 'aes-128-gcm', substr($key, 0, 16), OPENSSL_RAW_DATA, $nonce, $tag, '', 16);

    if ($encrypted === false) return null;

    // aes128gcm header: salt(16) + rs(4) + idlen(1) + keyid(65)
    $rs = pack('N', 4096);
    $header = $salt . $rs . chr(65) . $localPublicKeyUncompressed;

    return $header . $encrypted . $tag;
}

function hkdf($ikm, $salt, $info, $length) {
    $prk = hash_hmac('sha256', $ikm, $salt, true);
    $t = '';
    $output = '';
    for ($i = 1; strlen($output) < $length; $i++) {
        $t = hash_hmac('sha256', $t . $info . chr($i), $prk, true);
        $output .= $t;
    }
    return substr($output, 0, $length);
}

// ── Send push notification ──────────────────────────────────────
function sendPushNotification($subscription, $payload) {
    $endpoint = $subscription['endpoint'];
    $payloadJson = json_encode($payload);

    // Get VAPID authorization
    $vapidHeaders = createVapidAuth($endpoint);
    if (!$vapidHeaders) {
        error_log('Push: VAPID auth creation failed');
        return ['success' => false, 'error' => 'VAPID config missing'];
    }

    // Try to encrypt if we have the crypto keys
    $body = $payloadJson;
    $contentType = 'application/json';
    $encrypted = null;

    if (!empty($subscription['p256dh']) && !empty($subscription['auth_key'])) {
        $encrypted = encryptPayload($payloadJson, $subscription['p256dh'], $subscription['auth_key']);
    }

    if ($encrypted) {
        $body = $encrypted;
        $contentType = 'application/octet-stream';
    }

    $headers = [
        'Content-Type: ' . $contentType,
        'Content-Encoding: aes128gcm',
        'Content-Length: ' . strlen($body),
        'TTL: 86400',
        $vapidHeaders['Authorization'],
    ];

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => false,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($httpCode === 201 || $httpCode === 200) {
        return ['success' => true, 'status' => $httpCode];
    }

    // Handle gone (410) or not found (404) - subscription expired
    if ($httpCode === 410 || $httpCode === 404) {
        markSubscriptionInactive($subscription['id'] ?? 0);
        return ['success' => false, 'error' => 'subscription_expired', 'status' => $httpCode];
    }

    return ['success' => false, 'error' => $error ?: "HTTP $httpCode", 'status' => $httpCode, 'body' => $response];
}

function markSubscriptionInactive($id) {
    $db = getDB();
    if (!$db || !$id) return;
    $stmt = $db->prepare("UPDATE push_subscriptions SET active = 0 WHERE id = ?");
    $stmt->execute([$id]);
}

function incrementFailure($id) {
    $db = getDB();
    if (!$db || !$id) return;
    $stmt = $db->prepare("UPDATE push_subscriptions SET failures = failures + 1, active = IF(failures >= 4, 0, active) WHERE id = ?");
    $stmt->execute([$id]);
}

// ── Router ──────────────────────────────────────────────────────
$action = sanitize($_GET['action'] ?? $_POST['action'] ?? 'vapid-key', 50);

switch ($action) {

    // ── Get VAPID public key ────────────────────────────────────
    case 'vapid-key':
        jsonResponse([
            'success' => true,
            'publicKey' => VAPID_PUBLIC_KEY
        ]);
        break;

    // ── Subscribe to push notifications ─────────────────────────
    case 'subscribe':
        ensurePushTable();
        $clientId = getAuthenticatedUser();

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            jsonResponse(['error' => 'Invalid JSON body'], 400);
        }

        $endpoint = filter_var($input['endpoint'] ?? '', FILTER_VALIDATE_URL);
        $p256dh = sanitize($input['keys']['p256dh'] ?? '', 255);
        $auth = sanitize($input['keys']['auth'] ?? '', 100);

        if (!$endpoint || !$p256dh || !$auth) {
            jsonResponse(['error' => 'Missing endpoint, p256dh, or auth key'], 400);
        }

        // Validate endpoint is a push service
        $host = parse_url($endpoint, PHP_URL_HOST);
        $allowedPushHosts = [
            'fcm.googleapis.com', 'updates.push.services.mozilla.com',
            'wns.windows.com', 'push.apple.com', 'web.push.apple.com'
        ];
        $isAllowed = false;
        foreach ($allowedPushHosts as $allowed) {
            if ($host === $allowed || str_ends_with($host, '.' . $allowed)) {
                $isAllowed = true;
                break;
            }
        }
        if (!$isAllowed) {
            jsonResponse(['error' => 'Invalid push endpoint'], 400);
        }

        $topics = isset($input['topics']) ? json_encode(array_slice((array)$input['topics'], 0, 20)) : null;
        $userAgent = sanitize($_SERVER['HTTP_USER_AGENT'] ?? '', 255);

        $db = getDB();
        $stmt = $db->prepare("INSERT INTO push_subscriptions (client_id, endpoint, p256dh, auth_key, user_agent, topics)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                client_id = VALUES(client_id),
                p256dh = VALUES(p256dh),
                auth_key = VALUES(auth_key),
                user_agent = VALUES(user_agent),
                topics = VALUES(topics),
                active = 1,
                failures = 0");
        $stmt->execute([$clientId, $endpoint, $p256dh, $auth, $userAgent, $topics]);

        jsonResponse(['success' => true, 'message' => 'Subscribed to push notifications']);
        break;

    // ── Unsubscribe ─────────────────────────────────────────────
    case 'unsubscribe':
        ensurePushTable();
        $input = json_decode(file_get_contents('php://input'), true);
        $endpoint = filter_var($input['endpoint'] ?? '', FILTER_VALIDATE_URL);

        if (!$endpoint) {
            jsonResponse(['error' => 'Missing endpoint'], 400);
        }

        $db = getDB();
        $stmt = $db->prepare("UPDATE push_subscriptions SET active = 0 WHERE endpoint = ?");
        $stmt->execute([$endpoint]);

        jsonResponse(['success' => true, 'message' => 'Unsubscribed']);
        break;

    // ── Send push to specific user ──────────────────────────────
    case 'send':
        // Internal use only - verify by session or secret
        $clientId = getAuthenticatedUser();
        $secret = $_SERVER['HTTP_X_PUSH_SECRET'] ?? '';
        $pushSecret = getenv('PUSH_SECRET');
        $isInternal = $pushSecret && hash_equals($pushSecret, $secret);

        if (!$clientId && !$isInternal) {
            jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $targetUserId = intval($input['user_id'] ?? 0);
        $notification = [
            'title' => sanitize($input['title'] ?? 'GoSiteMe', 100),
            'body' => sanitize($input['body'] ?? '', 500),
            'type' => sanitize($input['type'] ?? 'general', 20),
            'url' => sanitize($input['url'] ?? '/', 500),
            'icon' => sanitize($input['icon'] ?? '/assets/img/icon-192.png', 255),
            'tag' => sanitize($input['tag'] ?? '', 50),
            'actions' => $input['actions'] ?? [],
        ];

        if ($targetUserId < 1) {
            jsonResponse(['error' => 'user_id required'], 400);
        }

        $db = getDB();
        ensurePushTable();
        $stmt = $db->prepare("SELECT * FROM push_subscriptions WHERE client_id = ? AND active = 1");
        $stmt->execute([$targetUserId]);
        $subs = $stmt->fetchAll();

        $results = ['sent' => 0, 'failed' => 0, 'expired' => 0];
        foreach ($subs as $sub) {
            $result = sendPushNotification($sub, $notification);
            if ($result['success']) {
                $results['sent']++;
                $upd = $db->prepare("UPDATE push_subscriptions SET last_used = NOW() WHERE id = ?");
                $upd->execute([$sub['id']]);
            } else if (($result['error'] ?? '') === 'subscription_expired') {
                $results['expired']++;
            } else {
                $results['failed']++;
                incrementFailure($sub['id']);
            }
        }

        jsonResponse(['success' => true, 'results' => $results]);
        break;

    // ── Broadcast to all subscribers ────────────────────────────
    case 'broadcast':
        requireAdmin();
        $input = json_decode(file_get_contents('php://input'), true);

        $notification = [
            'title' => sanitize($input['title'] ?? 'GoSiteMe', 100),
            'body' => sanitize($input['body'] ?? '', 500),
            'type' => sanitize($input['type'] ?? 'general', 20),
            'url' => sanitize($input['url'] ?? '/', 500),
            'icon' => sanitize($input['icon'] ?? '/assets/img/icon-192.png', 255),
        ];

        $topic = sanitize($input['topic'] ?? '', 50);

        $db = getDB();
        ensurePushTable();

        if ($topic) {
            $stmt = $db->prepare("SELECT * FROM push_subscriptions WHERE active = 1 AND JSON_CONTAINS(topics, ?)");
            $stmt->execute([json_encode($topic)]);
        } else {
            $stmt = $db->query("SELECT * FROM push_subscriptions WHERE active = 1 LIMIT 10000");
        }

        $subs = $stmt->fetchAll();
        $results = ['total' => count($subs), 'sent' => 0, 'failed' => 0, 'expired' => 0];

        foreach ($subs as $sub) {
            $result = sendPushNotification($sub, $notification);
            if ($result['success']) {
                $results['sent']++;
            } else if (($result['error'] ?? '') === 'subscription_expired') {
                $results['expired']++;
            } else {
                $results['failed']++;
                incrementFailure($sub['id']);
            }
        }

        jsonResponse(['success' => true, 'results' => $results]);
        break;

    // ── Stats ───────────────────────────────────────────────────
    case 'stats':
        $clientId = getAuthenticatedUser();
        if (!$clientId) {
            jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $db = getDB();
        ensurePushTable();

        $total = $db->query("SELECT COUNT(*) FROM push_subscriptions WHERE active = 1")->fetchColumn();
        $mine = $db->prepare("SELECT COUNT(*) FROM push_subscriptions WHERE client_id = ? AND active = 1");
        $mine->execute([$clientId]);

        jsonResponse([
            'success' => true,
            'stats' => [
                'total_active' => (int)$total,
                'my_devices' => (int)$mine->fetchColumn(),
            ]
        ]);
        break;

    default:
        jsonResponse(['error' => 'Unknown action: ' . $action], 400);
}
