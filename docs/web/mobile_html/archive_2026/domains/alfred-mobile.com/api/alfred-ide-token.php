<?php
/**
 * Alfred IDE Token API
 *
 * POST /api/alfred-ide-token.php
 *   Exchange an auth code for an access token
 *   Body: { grant_type, code, redirect_uri, client_id }
 *   Returns: { access_token, token_type, expires_in, user_name, user_email, client_id }
 *
 * POST /api/alfred-ide-token.php?action=revoke
 *   Revoke a token (header: Authorization: Bearer TOKEN)
 *
 * GET /api/alfred-ide-token.php?action=userinfo
 *   Get user profile (header: Authorization: Bearer TOKEN)
 */

define('GOSITEME_API', true);
$GLOBALS['CSRF_EXEMPT'] = true;
$GLOBALS['RATE_LIMIT_EXEMPT'] = true;
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$action = $_GET['action'] ?? 'token';

try {
    $db = getSharedDB();
} catch (\Throwable $e) {
    error_log('Alfred IDE token DB error: ' . $e->getMessage());
    jsonOut(['error' => 'server_error'], 500);
}

// ── Rate limit ───────────────────────────────────────────────────────────────
try {
    apiRateLimit(20, 60, 'alfred_ide_token');
} catch (\Throwable $e) {
    error_log('Alfred IDE token rate limit error: ' . $e->getMessage());
    jsonOut(['error' => 'server_error'], 500);
}

// ── Router ───────────────────────────────────────────────────────────────────
switch ($action) {
    case 'token':
        handleToken($db);
        break;
    case 'revoke':
        handleRevoke($db);
        break;
    case 'userinfo':
        handleUserInfo($db);
        break;
    default:
        jsonOut(['error' => 'unknown_action'], 400);
}

// ════════════════════════════════════════════════════════════════════════════
// HANDLERS
// ════════════════════════════════════════════════════════════════════════════

function handleToken(PDO $db): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonOut(['error' => 'method_not_allowed'], 405);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        jsonOut(['error' => 'invalid_request', 'message' => 'JSON body required'], 400);
    }

    $grantType   = $input['grant_type']   ?? '';
    $code        = $input['code']         ?? '';
    $redirectUri = $input['redirect_uri'] ?? '';
    $clientId    = $input['client_id']    ?? '';

    if ($grantType !== 'authorization_code') {
        jsonOut(['error' => 'unsupported_grant_type'], 400);
    }

    if (!$code || !$redirectUri || $clientId !== 'alfred-ide-builtin') {
        jsonOut(['error' => 'invalid_request', 'message' => 'code, redirect_uri, and client_id are required'], 400);
    }

    // Validate loopback redirect URI
    if (!preg_match('#^http://127\.0\.0\.1:\d{4,5}/callback$#', $redirectUri)) {
        jsonOut(['error' => 'invalid_redirect_uri'], 400);
    }

    // Look up the auth code
    $stmt = $db->prepare("
        SELECT c.*, a.id as app_id_check
        FROM alfred_oauth_codes c
        JOIN alfred_oauth_apps a ON a.id = c.app_id AND a.client_id = 'alfred-ide-builtin'
        WHERE c.code = ?
          AND c.used_at IS NULL
          AND c.expires_at > NOW()
          AND c.redirect_uri = ?
        LIMIT 1
    ");
    $stmt->execute([$code, $redirectUri]);
    $codeRow = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$codeRow) {
        jsonOut(['error' => 'invalid_grant', 'message' => 'Code is invalid, expired, or already used'], 400);
    }

    // Mark code as used
    $db->prepare("UPDATE alfred_oauth_codes SET used_at = NOW() WHERE id = ?")
       ->execute([$codeRow['id']]);

    // Generate access + refresh tokens
    $accessToken  = bin2hex(random_bytes(32));
    $refreshToken = bin2hex(random_bytes(32));
    $accessHash   = hash('sha256', $accessToken);
    $refreshHash  = hash('sha256', $refreshToken);
    $expiresAt    = date('Y-m-d H:i:s', time() + 3600);

    $db->prepare("
        INSERT INTO alfred_oauth_tokens (app_id, user_id, access_token, refresh_token, scopes, expires_at)
        VALUES (?, ?, ?, ?, ?, ?)
    ")->execute([$codeRow['app_id'], $codeRow['user_id'], $accessHash, $refreshHash, $codeRow['scopes'], $expiresAt]);

    // Get user info to include in response
    $stmt = $db->prepare("SELECT id, firstname, lastname, email FROM clients WHERE id = ? LIMIT 1");
    $stmt->execute([$codeRow['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    jsonOut([
        'access_token'  => $accessToken,
        'refresh_token' => $refreshToken,
        'token_type'    => 'Bearer',
        'expires_in'    => 3600,
        'user_name'     => trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? '')),
        'user_email'    => $user['email'] ?? '',
        'client_id'     => (int)$user['id'],
    ]);
}

function handleRevoke(PDO $db): void {
    $bearer = getBearerToken();
    if (!$bearer) {
        jsonOut(['error' => 'unauthorized'], 401);
    }

    $hash = hash('sha256', $bearer);
    $db->prepare("UPDATE alfred_oauth_tokens SET revoked_at = NOW() WHERE access_token = ? AND revoked_at IS NULL")
       ->execute([$hash]);

    jsonOut(['revoked' => true]);
}

function handleUserInfo(PDO $db): void {
    $bearer = getBearerToken();
    if (!$bearer) {
        jsonOut(['error' => 'unauthorized'], 401);
    }

    $hash = hash('sha256', $bearer);
    $stmt = $db->prepare("
        SELECT t.user_id, t.scopes, t.expires_at, c.firstname, c.lastname, c.email
        FROM alfred_oauth_tokens t
        JOIN clients c ON c.id = t.user_id
        WHERE t.access_token = ?
          AND t.revoked_at IS NULL
          AND t.expires_at > NOW()
        LIMIT 1
    ");
    $stmt->execute([$hash]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        jsonOut(['error' => 'invalid_token'], 401);
    }

    jsonOut([
        'client_id'  => (int)$row['user_id'],
        'name'       => trim($row['firstname'] . ' ' . $row['lastname']),
        'email'      => $row['email'],
        'scopes'     => json_decode($row['scopes'], true) ?: [],
        'expires_at' => $row['expires_at'],
    ]);
}

// ════════════════════════════════════════════════════════════════════════════
// HELPERS
// ════════════════════════════════════════════════════════════════════════════

function getBearerToken(): ?string {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? apache_request_headers()['Authorization'] ?? '';
    if (preg_match('/^Bearer\s+(.+)$/i', $header, $m)) {
        return $m[1];
    }
    return null;
}

function jsonOut(array $data, int $status = 200): never {
    http_response_code($status);
    echo json_encode($data);
    exit;
}
