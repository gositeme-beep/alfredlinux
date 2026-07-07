<?php
/**
 * Alfred AI — OAuth2 Authorization Server
 * 
 * Full OAuth2 Authorization Code Grant flow, API key management,
 * and developer app registration.
 *
 * Endpoints (via ?action=...):
 *   GET  authorize         — Show consent screen
 *   POST authorize/grant   — User grants permission
 *   POST authorize/deny    — User denies permission
 *   POST token             — Exchange code for tokens / refresh
 *   POST revoke            — Revoke a token
 *   GET  userinfo          — Get authenticated user's profile
 *   GET  keys              — List user's API keys
 *   POST keys/create       — Generate new API key
 *   DELETE keys/revoke     — Revoke API key
 *   GET  apps              — List user's OAuth apps
 *   POST apps/create       — Register new OAuth app
 *   PUT  apps/update       — Update OAuth app
 *   DELETE apps/delete     — Deactivate OAuth app
 */

define('GOSITEME_API', true);
// Determine action early so we can configure API security exemptions.
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// OAuth `token`/`revoke` are client-authenticated, non-cookie endpoints.
// They must not require browser CSRF tokens.
if (in_array($action, ['token', 'revoke'], true)) {
    $GLOBALS['CSRF_EXEMPT'] = true;
}

require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

// Only start a PHP session for browser/user endpoints.
if (!in_array($action, ['token', 'revoke'], true)) {
    if (session_status() === PHP_SESSION_NONE) session_start();
}

// ── Constants ──────────────────────────────────────────────────────────────────

const AUTH_CODE_TTL      = 600;      // 10 minutes
const ACCESS_TOKEN_TTL   = 3600;     // 1 hour
const REFRESH_TOKEN_TTL  = 2592000;  // 30 days

const VALID_SCOPES = [
    'tools:read'       => 'View available tools',
    'tools:execute'    => 'Execute tools on your behalf',
    'agents:read'      => 'View your agents',
    'agents:write'     => 'Create and manage agents',
    'fleet:read'       => 'View fleet status',
    'fleet:write'      => 'Manage and deploy fleets',
    'voice:read'       => 'View call history',
    'voice:write'      => 'Make calls and manage rooms',
    'billing:read'     => 'View usage and billing info',
    'marketplace:read' => 'Browse marketplace',
    'marketplace:write'=> 'Publish to marketplace',
    'profile:read'     => 'View your profile',
];

// ── CORS / Preflight ───────────────────────────────────────────────────────────

header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── Routing ────────────────────────────────────────────────────────────────────

// Strict rate limits for OAuth token operations (abuse protection)
if (in_array($action, ['token', 'revoke', 'authorize/grant', 'keys/create'], true)) {
    apiRateLimit(10, 60, 'oauth_sensitive');
}

try {
    switch ($action) {
        // ── Authorization Flow ─────────────────────────────────────────────
        case 'authorize':
            if ($method === 'GET') {
                handleAuthorize();
            } else {
                jsonResponse(['error' => 'method_not_allowed', 'message' => 'Use GET for authorize'], 405);
            }
            break;

        case 'authorize/grant':
            requireMethod('POST');
            handleAuthorizeGrant();
            break;

        case 'authorize/deny':
            requireMethod('POST');
            handleAuthorizeDeny();
            break;

        // ── Token Exchange ─────────────────────────────────────────────────
        case 'token':
            requireMethod('POST');
            handleToken();
            break;

        case 'revoke':
            requireMethod('POST');
            handleRevoke();
            break;

        // ── User Info ──────────────────────────────────────────────────────
        case 'userinfo':
            requireMethod('GET');
            handleUserInfo();
            break;

        // ── API Key Management ─────────────────────────────────────────────
        case 'keys':
            requireMethod('GET');
            handleListKeys();
            break;

        case 'keys/create':
            requireMethod('POST');
            handleCreateKey();
            break;

        case 'keys/revoke':
            requireMethod('DELETE');
            handleRevokeKey();
            break;

        // ── OAuth App Management ───────────────────────────────────────────
        case 'apps':
            requireMethod('GET');
            handleListApps();
            break;

        case 'apps/create':
            requireMethod('POST');
            handleCreateApp();
            break;

        case 'apps/update':
            requireMethod('PUT');
            handleUpdateApp();
            break;

        case 'apps/delete':
            requireMethod('DELETE');
            handleDeleteApp();
            break;

        default:
            jsonResponse([
                'error'   => 'invalid_action',
                'message' => 'Unknown action. Valid actions: authorize, token, revoke, userinfo, keys, keys/create, keys/revoke, apps, apps/create, apps/update, apps/delete'
            ], 400);
    }
} catch (PDOException $e) {
    error_log('OAuth API DB error: ' . $e->getMessage());
    jsonResponse(['error' => 'server_error', 'message' => 'Internal server error'], 500);
} catch (Exception $e) {
    error_log('OAuth API error: ' . $e->getMessage());
    jsonResponse(['error' => 'server_error', 'message' => 'Internal server error'], 500);
}

// ══════════════════════════════════════════════════════════════════════════════
//  HELPERS
// ══════════════════════════════════════════════════════════════════════════════

function requireMethod(string $expected): void {
    if ($_SERVER['REQUEST_METHOD'] !== $expected) {
        jsonResponse(['error' => 'method_not_allowed', 'message' => "Expected $expected"], 405);
    }
}

function requireSession(): int {
    if (empty($_SESSION['logged_in']) || empty($_SESSION['client_id'])) {
        jsonResponse(['error' => 'unauthorized', 'message' => 'Login required'], 401);
    }
    return (int) $_SESSION['client_id'];
}

function getInputJSON(): array {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?: [];
    }
    return $_POST;
}

function generateToken(string $prefix, int $bytes): string {
    return $prefix . bin2hex(random_bytes($bytes));
}

function hashToken(string $token): string {
    return hash('sha256', $token);
}

function validateScopes(array $requested): array {
    return array_values(array_filter($requested, function ($s) {
        return array_key_exists($s, VALID_SCOPES);
    }));
}

function authenticateBearer(): array {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION']
        ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        ?? '';

    if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $m)) {
        jsonResponse(['error' => 'unauthorized', 'message' => 'Bearer token required'], 401);
    }

    $token = trim($m[1]);
    $hash  = hashToken($token);
    $db    = getDB();

    $stmt = $db->prepare("
        SELECT ot.id, ot.user_id, ot.app_id, ot.scopes, ot.expires_at,
               oa.name AS app_name
        FROM alfred_oauth_tokens ot
        JOIN alfred_oauth_apps oa ON ot.app_id = oa.id
        WHERE ot.access_token = :hash
          AND ot.revoked_at IS NULL
          AND ot.expires_at > NOW()
        LIMIT 1
    ");
    $stmt->execute([':hash' => $hash]);
    $row = $stmt->fetch();

    if (!$row) {
        jsonResponse(['error' => 'invalid_token', 'message' => 'Token expired or revoked'], 401);
    }

    $scopes = json_decode($row['scopes'], true) ?: [];
    return [
        'user_id'  => (int) $row['user_id'],
        'app_id'   => (int) $row['app_id'],
        'app_name' => $row['app_name'],
        'scopes'   => $scopes,
    ];
}

function authenticateClient(array $input): array {
    $clientId     = $input['client_id']     ?? '';
    $clientSecret = $input['client_secret'] ?? '';

    if (empty($clientId) || empty($clientSecret)) {
        jsonResponse(['error' => 'invalid_client', 'message' => 'client_id and client_secret required'], 401);
    }

    $db   = getDB();
    $stmt = $db->prepare("SELECT * FROM alfred_oauth_apps WHERE client_id = :cid LIMIT 1");
    $stmt->execute([':cid' => $clientId]);
    $app = $stmt->fetch();

    if (!$app) {
        jsonResponse(['error' => 'invalid_client', 'message' => 'Unknown client_id'], 401);
    }

    // client_secret is stored as SHA-256 hash
    if (!hash_equals($app['client_secret'], hashToken($clientSecret))) {
        jsonResponse(['error' => 'invalid_client', 'message' => 'Invalid client_secret'], 401);
    }

    return $app;
}

// ══════════════════════════════════════════════════════════════════════════════
//  AUTHORIZATION FLOW
// ══════════════════════════════════════════════════════════════════════════════

function handleAuthorize(): void {
    $clientId     = $_GET['client_id']     ?? '';
    $redirectUri  = $_GET['redirect_uri']  ?? '';
    $scope        = $_GET['scope']         ?? '';
    $responseType = $_GET['response_type'] ?? '';
    $state        = $_GET['state']         ?? '';

    // Validate response_type
    if ($responseType !== 'code') {
        jsonResponse(['error' => 'unsupported_response_type', 'message' => 'Only response_type=code is supported'], 400);
    }

    if (empty($clientId) || empty($redirectUri)) {
        jsonResponse(['error' => 'invalid_request', 'message' => 'client_id and redirect_uri are required'], 400);
    }

    // Look up the app
    $db   = getDB();
    $stmt = $db->prepare("SELECT * FROM alfred_oauth_apps WHERE client_id = :cid LIMIT 1");
    $stmt->execute([':cid' => $clientId]);
    $app = $stmt->fetch();

    if (!$app) {
        jsonResponse(['error' => 'invalid_client', 'message' => 'Unknown client_id'], 400);
    }

    // Validate redirect_uri against registered URIs
    $registeredUris = json_decode($app['redirect_uris'], true) ?: [];
    if (!in_array($redirectUri, $registeredUris, true)) {
        jsonResponse(['error' => 'invalid_redirect_uri', 'message' => 'redirect_uri does not match any registered URI'], 400);
    }

    // Parse & validate scopes
    $requestedScopes = array_filter(explode(' ', $scope));
    $validScopes     = validateScopes($requestedScopes);
    if (empty($validScopes) && !empty($requestedScopes)) {
        redirectWithError($redirectUri, 'invalid_scope', 'None of the requested scopes are valid', $state);
    }
    if (empty($validScopes)) {
        $validScopes = ['profile:read'];
    }

    // Check if user is logged in
    $userId = $_SESSION['client_id'] ?? 0;
    if (!$userId) {
        $returnUrl = SITE_URL . '/api/oauth.php?' . http_build_query($_GET);
        header('Location: ' . SITE_URL . '/login.php?return=' . urlencode($returnUrl));
        exit;
    }

    // Get user email for display
    $userStmt = $db->prepare("SELECT email, first_name, last_name FROM alfred_users WHERE id = :uid LIMIT 1");
    $userStmt->execute([':uid' => $userId]);
    $user = $userStmt->fetch();
    $userEmail = $user ? ($user['email'] ?? 'unknown') : 'unknown';
    $userName  = $user ? trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) : '';

    // Generate CSRF token
    $csrfToken = generateCSRFToken();

    // Render consent page
    renderConsentPage($app, $validScopes, $redirectUri, $state, $csrfToken, $userEmail, $userName);
}

function handleAuthorizeGrant(): void {
    $userId = requireSession();

    $input       = $_POST;
    $csrfToken   = $input['csrf_token']   ?? '';
    $clientId    = $input['client_id']    ?? '';
    $redirectUri = $input['redirect_uri'] ?? '';
    $scope       = $input['scope']        ?? '';
    $state       = $input['state']        ?? '';

    // CSRF check
    if (!verifyCSRFToken($csrfToken)) {
        jsonResponse(['error' => 'invalid_request', 'message' => 'CSRF token validation failed'], 403);
    }

    // Validate app
    $db   = getDB();
    $stmt = $db->prepare("SELECT * FROM alfred_oauth_apps WHERE client_id = :cid LIMIT 1");
    $stmt->execute([':cid' => $clientId]);
    $app = $stmt->fetch();

    if (!$app) {
        jsonResponse(['error' => 'invalid_client', 'message' => 'Unknown client_id'], 400);
    }

    // Validate redirect_uri
    $registeredUris = json_decode($app['redirect_uris'], true) ?: [];
    if (!in_array($redirectUri, $registeredUris, true)) {
        jsonResponse(['error' => 'invalid_redirect_uri', 'message' => 'redirect_uri mismatch'], 400);
    }

    // Parse scopes
    $scopes = array_filter(explode(' ', $scope));
    $scopes = validateScopes($scopes);
    if (empty($scopes)) {
        $scopes = ['profile:read'];
    }

    // Generate authorization code (32-byte random hex)
    $code     = bin2hex(random_bytes(32));
    $codeHash = hashToken($code);

    // Store hashed code
    $stmt = $db->prepare("
        INSERT INTO alfred_oauth_codes (app_id, user_id, code, redirect_uri, scopes, expires_at)
        VALUES (:app_id, :user_id, :code, :redirect_uri, :scopes, :expires_at)
    ");
    $stmt->execute([
        ':app_id'       => $app['id'],
        ':user_id'      => $userId,
        ':code'         => $codeHash,
        ':redirect_uri' => $redirectUri,
        ':scopes'       => json_encode($scopes),
        ':expires_at'   => date('Y-m-d H:i:s', time() + AUTH_CODE_TTL),
    ]);

    // Redirect back to the app with the UNHASHED code
    $params = ['code' => $code];
    if (!empty($state)) {
        $params['state'] = $state;
    }
    $sep = (strpos($redirectUri, '?') !== false) ? '&' : '?';
    header('Location: ' . $redirectUri . $sep . http_build_query($params));
    exit;
}

function handleAuthorizeDeny(): void {
    $input       = $_POST;
    $csrfToken   = $input['csrf_token']   ?? '';
    $redirectUri = $input['redirect_uri'] ?? '';
    $state       = $input['state']        ?? '';

    // CSRF check
    if (!verifyCSRFToken($csrfToken)) {
        jsonResponse(['error' => 'invalid_request', 'message' => 'CSRF token validation failed'], 403);
    }

    if (empty($redirectUri)) {
        jsonResponse(['error' => 'invalid_request', 'message' => 'redirect_uri required'], 400);
    }

    redirectWithError($redirectUri, 'access_denied', 'The user denied the request', $state);
}

function redirectWithError(string $redirectUri, string $error, string $description, string $state): void {
    $params = ['error' => $error, 'error_description' => $description];
    if (!empty($state)) {
        $params['state'] = $state;
    }
    $sep = (strpos($redirectUri, '?') !== false) ? '&' : '?';
    header('Location: ' . $redirectUri . $sep . http_build_query($params));
    exit;
}

// ══════════════════════════════════════════════════════════════════════════════
//  TOKEN EXCHANGE
// ══════════════════════════════════════════════════════════════════════════════

function handleToken(): void {
    $input     = getInputJSON();
    $grantType = $input['grant_type'] ?? '';

    switch ($grantType) {
        case 'authorization_code':
            handleTokenAuthCode($input);
            break;
        case 'refresh_token':
            handleTokenRefresh($input);
            break;
        default:
            jsonResponse(['error' => 'unsupported_grant_type', 'message' => 'Supported: authorization_code, refresh_token'], 400);
    }
}

function handleTokenAuthCode(array $input): void {
    $app = authenticateClient($input);

    $code        = $input['code']         ?? '';
    $redirectUri = $input['redirect_uri'] ?? '';

    if (empty($code) || empty($redirectUri)) {
        jsonResponse(['error' => 'invalid_request', 'message' => 'code and redirect_uri are required'], 400);
    }

    $codeHash = hashToken($code);
    $db       = getDB();

    // Look up the authorization code
    $stmt = $db->prepare("
        SELECT * FROM alfred_oauth_codes
        WHERE code = :code
          AND app_id = :app_id
          AND used_at IS NULL
          AND expires_at > NOW()
        LIMIT 1
    ");
    $stmt->execute([':code' => $codeHash, ':app_id' => $app['id']]);
    $codeRow = $stmt->fetch();

    if (!$codeRow) {
        jsonResponse(['error' => 'invalid_grant', 'message' => 'Authorization code is invalid, expired, or already used'], 400);
    }

    // Validate redirect_uri matches
    if ($codeRow['redirect_uri'] !== $redirectUri) {
        jsonResponse(['error' => 'invalid_grant', 'message' => 'redirect_uri does not match the one used in authorization'], 400);
    }

    // Mark code as used
    $markStmt = $db->prepare("UPDATE alfred_oauth_codes SET used_at = NOW() WHERE id = :id");
    $markStmt->execute([':id' => $codeRow['id']]);

    // Generate tokens
    $accessToken  = generateToken('aat_', 64);
    $refreshToken = generateToken('art_', 64);
    $scopes       = json_decode($codeRow['scopes'], true) ?: [];

    // Store hashed tokens
    $stmt = $db->prepare("
        INSERT INTO alfred_oauth_tokens (app_id, user_id, access_token, refresh_token, scopes, expires_at)
        VALUES (:app_id, :user_id, :access_token, :refresh_token, :scopes, :expires_at)
    ");
    $stmt->execute([
        ':app_id'        => $app['id'],
        ':user_id'       => $codeRow['user_id'],
        ':access_token'  => hashToken($accessToken),
        ':refresh_token' => hashToken($refreshToken),
        ':scopes'        => json_encode($scopes),
        ':expires_at'    => date('Y-m-d H:i:s', time() + ACCESS_TOKEN_TTL),
    ]);

    header('Cache-Control: no-store');
    header('Pragma: no-cache');

    jsonResponse([
        'access_token'  => $accessToken,
        'token_type'    => 'bearer',
        'expires_in'    => ACCESS_TOKEN_TTL,
        'refresh_token' => $refreshToken,
        'scope'         => implode(' ', $scopes),
    ]);
}

function handleTokenRefresh(array $input): void {
    $app = authenticateClient($input);

    $refreshToken = $input['refresh_token'] ?? '';
    if (empty($refreshToken)) {
        jsonResponse(['error' => 'invalid_request', 'message' => 'refresh_token is required'], 400);
    }

    $hash = hashToken($refreshToken);
    $db   = getDB();

    // Find the token row
    $stmt = $db->prepare("
        SELECT * FROM alfred_oauth_tokens
        WHERE refresh_token = :hash
          AND app_id = :app_id
          AND revoked_at IS NULL
        LIMIT 1
    ");
    $stmt->execute([':hash' => $hash, ':app_id' => $app['id']]);
    $tokenRow = $stmt->fetch();

    if (!$tokenRow) {
        jsonResponse(['error' => 'invalid_grant', 'message' => 'Refresh token is invalid or revoked'], 400);
    }

    // Check refresh token age (30 days)
    $createdAt = strtotime($tokenRow['created_at']);
    if (time() - $createdAt > REFRESH_TOKEN_TTL) {
        // Revoke the old token
        $db->prepare("UPDATE alfred_oauth_tokens SET revoked_at = NOW() WHERE id = :id")
           ->execute([':id' => $tokenRow['id']]);
        jsonResponse(['error' => 'invalid_grant', 'message' => 'Refresh token has expired'], 400);
    }

    // Revoke old token pair
    $db->prepare("UPDATE alfred_oauth_tokens SET revoked_at = NOW() WHERE id = :id")
       ->execute([':id' => $tokenRow['id']]);

    // Issue new tokens
    $newAccessToken  = generateToken('aat_', 64);
    $newRefreshToken = generateToken('art_', 64);
    $scopes          = json_decode($tokenRow['scopes'], true) ?: [];

    $stmt = $db->prepare("
        INSERT INTO alfred_oauth_tokens (app_id, user_id, access_token, refresh_token, scopes, expires_at)
        VALUES (:app_id, :user_id, :access_token, :refresh_token, :scopes, :expires_at)
    ");
    $stmt->execute([
        ':app_id'        => $app['id'],
        ':user_id'       => $tokenRow['user_id'],
        ':access_token'  => hashToken($newAccessToken),
        ':refresh_token' => hashToken($newRefreshToken),
        ':scopes'        => json_encode($scopes),
        ':expires_at'    => date('Y-m-d H:i:s', time() + ACCESS_TOKEN_TTL),
    ]);

    header('Cache-Control: no-store');
    header('Pragma: no-cache');

    jsonResponse([
        'access_token'  => $newAccessToken,
        'token_type'    => 'bearer',
        'expires_in'    => ACCESS_TOKEN_TTL,
        'refresh_token' => $newRefreshToken,
        'scope'         => implode(' ', $scopes),
    ]);
}

// ══════════════════════════════════════════════════════════════════════════════
//  TOKEN REVOCATION
// ══════════════════════════════════════════════════════════════════════════════

function handleRevoke(): void {
    $input = getInputJSON();
    $app   = authenticateClient($input);
    $token = $input['token'] ?? '';

    if (empty($token)) {
        jsonResponse(['error' => 'invalid_request', 'message' => 'token is required'], 400);
    }

    $hash = hashToken($token);
    $db   = getDB();

    // Try access_token first, then refresh_token
    $stmt = $db->prepare("
        UPDATE alfred_oauth_tokens
        SET revoked_at = NOW()
        WHERE (access_token = :hash OR refresh_token = :hash2)
          AND app_id = :app_id
          AND revoked_at IS NULL
    ");
    $stmt->execute([':hash' => $hash, ':hash2' => $hash, ':app_id' => $app['id']]);

    // Per RFC 7009, always return 200 even if token was not found
    jsonResponse(['success' => true, 'message' => 'Token revoked']);
}

// ══════════════════════════════════════════════════════════════════════════════
//  USER INFO (Bearer-protected)
// ══════════════════════════════════════════════════════════════════════════════

function handleUserInfo(): void {
    $auth = authenticateBearer();

    $db   = getDB();
    $stmt = $db->prepare("
        SELECT id, email, first_name, last_name
        FROM alfred_users
        WHERE id = :uid
        LIMIT 1
    ");
    $stmt->execute([':uid' => $auth['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        jsonResponse(['error' => 'not_found', 'message' => 'User not found'], 404);
    }

    $name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));

    // Get org membership if any
    $orgId = null;
    $orgStmt = $db->prepare("
        SELECT org_id FROM alfred_org_members
        WHERE user_id = :uid AND status = 'active'
        LIMIT 1
    ");
    try {
        $orgStmt->execute([':uid' => $auth['user_id']]);
        $orgRow = $orgStmt->fetch();
        $orgId  = $orgRow ? (int) $orgRow['org_id'] : null;
    } catch (PDOException $e) {
        // Table may not exist yet — that's fine
        $orgId = null;
    }

    // Get user's plan
    $plan = 'free';
    try {
        $planStmt = $db->prepare("
            SELECT plan FROM alfred_api_keys
            WHERE user_id = :uid AND revoked_at IS NULL
            ORDER BY created_at DESC LIMIT 1
        ");
        $planStmt->execute([':uid' => $auth['user_id']]);
        $planRow = $planStmt->fetch();
        if ($planRow && !empty($planRow['plan'])) {
            $plan = $planRow['plan'];
        }
    } catch (PDOException $e) {
        // Column may not exist
    }

    jsonResponse([
        'id'     => (int) $user['id'],
        'email'  => $user['email'],
        'name'   => $name ?: null,
        'plan'   => $plan,
        'org_id' => $orgId,
    ]);
}

// ══════════════════════════════════════════════════════════════════════════════
//  API KEY MANAGEMENT (Session-protected)
// ══════════════════════════════════════════════════════════════════════════════

function handleListKeys(): void {
    $userId = requireSession();
    $db     = getDB();

    $stmt = $db->prepare("
        SELECT id, key_prefix, name, scopes, rate_limit_tier, last_used_at, expires_at, created_at,
               CASE WHEN revoked_at IS NOT NULL THEN 1 ELSE 0 END AS revoked
        FROM alfred_api_keys
        WHERE user_id = :uid
        ORDER BY created_at DESC
    ");
    $stmt->execute([':uid' => $userId]);
    $keys = $stmt->fetchAll();

    // Decode scopes JSON
    foreach ($keys as &$k) {
        $k['scopes']  = json_decode($k['scopes'], true) ?: [];
        $k['revoked'] = (bool) $k['revoked'];
    }
    unset($k);

    jsonResponse(['keys' => $keys]);
}

function handleCreateKey(): void {
    $userId = requireSession();
    $input  = getInputJSON();

    $name   = sanitize($input['name'] ?? '', 255);
    $scopes = $input['scopes'] ?? ['*'];

    if (empty($name)) {
        jsonResponse(['error' => 'invalid_request', 'message' => 'name is required'], 400);
    }

    if (!is_array($scopes)) {
        $scopes = ['*'];
    } else {
        $scopes = validateScopes($scopes);
        if (empty($scopes)) {
            $scopes = ['*'];
        }
    }

    // Generate the full key: ak_live_ + 48-byte hex
    $secret    = bin2hex(random_bytes(48));
    $prefix    = substr($secret, 0, 8);
    $fullKey   = 'ak_live_' . $secret;
    $keyHash   = hashToken($fullKey);
    $keyPrefix = substr($fullKey, 0, 12);

    $db   = getDB();
    $stmt = $db->prepare("
        INSERT INTO alfred_api_keys (user_id, key_prefix, key_hash, name, scopes, rate_limit_tier)
        VALUES (:user_id, :prefix, :hash, :name, :scopes, 'free')
    ");
    $stmt->execute([
        ':user_id' => $userId,
        ':prefix'  => $prefix,
        ':hash'    => $keyHash,
        ':name'    => $name,
        ':scopes'  => json_encode($scopes),
    ]);

    jsonResponse([
        'key'        => $fullKey,
        'key_prefix' => $keyPrefix,
        'name'       => $name,
        'scopes'     => $scopes,
        'created_at' => date('c'),
        'message'    => 'Save this key now. It will not be shown again.',
    ], 201);
}

function handleRevokeKey(): void {
    $userId = requireSession();
    $input  = getInputJSON();
    $keyId  = (int) ($input['id'] ?? $_GET['id'] ?? 0);

    if (!$keyId) {
        jsonResponse(['error' => 'invalid_request', 'message' => 'id is required'], 400);
    }

    $db   = getDB();
    $stmt = $db->prepare("
        UPDATE alfred_api_keys
        SET revoked_at = NOW()
        WHERE id = :id AND user_id = :uid AND revoked_at IS NULL
    ");
    $stmt->execute([':id' => $keyId, ':uid' => $userId]);

    if ($stmt->rowCount() === 0) {
        jsonResponse(['error' => 'not_found', 'message' => 'Key not found or already revoked'], 404);
    }

    jsonResponse(['success' => true, 'message' => 'API key revoked']);
}

// ══════════════════════════════════════════════════════════════════════════════
//  OAUTH APP MANAGEMENT (Session-protected, for developers)
// ══════════════════════════════════════════════════════════════════════════════

function handleListApps(): void {
    $userId = requireSession();
    $db     = getDB();

    $stmt = $db->prepare("
        SELECT id, client_id, name, description, redirect_uris, scopes,
               logo_url, website_url, is_approved, created_at
        FROM alfred_oauth_apps
        WHERE user_id = :uid
        ORDER BY created_at DESC
    ");
    $stmt->execute([':uid' => $userId]);
    $apps = $stmt->fetchAll();

    foreach ($apps as &$a) {
        $a['redirect_uris'] = json_decode($a['redirect_uris'], true) ?: [];
        $a['scopes']        = json_decode($a['scopes'], true) ?: [];
        $a['is_approved']   = (bool) $a['is_approved'];
    }
    unset($a);

    jsonResponse(['apps' => $apps]);
}

function handleCreateApp(): void {
    $userId = requireSession();
    $input  = getInputJSON();

    $name         = sanitize($input['name'] ?? '', 255);
    $description  = sanitize($input['description'] ?? '', 2000);
    $redirectUris = $input['redirect_uris'] ?? [];
    $websiteUrl   = sanitize($input['website_url'] ?? '', 500);
    $logoUrl      = sanitize($input['logo_url'] ?? '', 500);

    if (empty($name)) {
        jsonResponse(['error' => 'invalid_request', 'message' => 'name is required'], 400);
    }

    if (!is_array($redirectUris) || empty($redirectUris)) {
        jsonResponse(['error' => 'invalid_request', 'message' => 'At least one redirect_uri is required'], 400);
    }

    // Validate each redirect URI
    foreach ($redirectUris as $uri) {
        if (!filter_var($uri, FILTER_VALIDATE_URL) && $uri !== 'urn:ietf:wg:oauth:2.0:oob') {
            jsonResponse(['error' => 'invalid_request', 'message' => "Invalid redirect_uri: $uri"], 400);
        }
    }

    // Generate credentials
    $clientId     = generateToken('alf_', 32);
    $clientSecret = generateToken('als_', 48);
    $secretHash   = hashToken($clientSecret);

    // Default scopes
    $defaultScopes = array_keys(VALID_SCOPES);

    $db   = getDB();
    $stmt = $db->prepare("
        INSERT INTO alfred_oauth_apps (user_id, client_id, client_secret, name, description, redirect_uris, scopes, logo_url, website_url)
        VALUES (:user_id, :client_id, :client_secret, :name, :description, :redirect_uris, :scopes, :logo_url, :website_url)
    ");
    $stmt->execute([
        ':user_id'       => $userId,
        ':client_id'     => $clientId,
        ':client_secret' => $secretHash,
        ':name'          => $name,
        ':description'   => $description,
        ':redirect_uris' => json_encode($redirectUris),
        ':scopes'        => json_encode($defaultScopes),
        ':logo_url'      => $logoUrl ?: null,
        ':website_url'   => $websiteUrl ?: null,
    ]);

    jsonResponse([
        'id'            => (int) $db->lastInsertId(),
        'client_id'     => $clientId,
        'client_secret' => $clientSecret,
        'name'          => $name,
        'message'       => 'Save the client_secret now. It will not be shown again.',
    ], 201);
}

function handleUpdateApp(): void {
    $userId = requireSession();
    $input  = getInputJSON();
    $appId  = (int) ($input['id'] ?? $_GET['id'] ?? 0);

    if (!$appId) {
        jsonResponse(['error' => 'invalid_request', 'message' => 'id is required'], 400);
    }

    $db   = getDB();
    // Verify ownership
    $stmt = $db->prepare("SELECT id FROM alfred_oauth_apps WHERE id = :id AND user_id = :uid LIMIT 1");
    $stmt->execute([':id' => $appId, ':uid' => $userId]);
    if (!$stmt->fetch()) {
        jsonResponse(['error' => 'not_found', 'message' => 'App not found'], 404);
    }

    $updates = [];
    $params  = [':id' => $appId];

    if (isset($input['name'])) {
        $updates[]       = 'name = :name';
        $params[':name'] = sanitize($input['name'], 255);
    }
    if (isset($input['description'])) {
        $updates[]              = 'description = :description';
        $params[':description'] = sanitize($input['description'], 2000);
    }
    if (isset($input['redirect_uris']) && is_array($input['redirect_uris'])) {
        foreach ($input['redirect_uris'] as $uri) {
            if (!filter_var($uri, FILTER_VALIDATE_URL) && $uri !== 'urn:ietf:wg:oauth:2.0:oob') {
                jsonResponse(['error' => 'invalid_request', 'message' => "Invalid redirect_uri: $uri"], 400);
            }
        }
        $updates[]                  = 'redirect_uris = :redirect_uris';
        $params[':redirect_uris']   = json_encode($input['redirect_uris']);
    }
    if (isset($input['website_url'])) {
        $updates[]              = 'website_url = :website_url';
        $params[':website_url'] = sanitize($input['website_url'], 500);
    }
    if (isset($input['logo_url'])) {
        $updates[]           = 'logo_url = :logo_url';
        $params[':logo_url'] = sanitize($input['logo_url'], 500);
    }

    if (empty($updates)) {
        jsonResponse(['error' => 'invalid_request', 'message' => 'No fields to update'], 400);
    }

    $sql = "UPDATE alfred_oauth_apps SET " . implode(', ', $updates) . " WHERE id = :id";
    $db->prepare($sql)->execute($params);

    jsonResponse(['success' => true, 'message' => 'App updated']);
}

function handleDeleteApp(): void {
    $userId = requireSession();
    $input  = getInputJSON();
    $appId  = (int) ($input['id'] ?? $_GET['id'] ?? 0);

    if (!$appId) {
        jsonResponse(['error' => 'invalid_request', 'message' => 'id is required'], 400);
    }

    $db = getDB();

    // Verify ownership
    $stmt = $db->prepare("SELECT id FROM alfred_oauth_apps WHERE id = :id AND user_id = :uid LIMIT 1");
    $stmt->execute([':id' => $appId, ':uid' => $userId]);
    if (!$stmt->fetch()) {
        jsonResponse(['error' => 'not_found', 'message' => 'App not found'], 404);
    }

    // Revoke all tokens for this app
    $db->prepare("UPDATE alfred_oauth_tokens SET revoked_at = NOW() WHERE app_id = :aid AND revoked_at IS NULL")
       ->execute([':aid' => $appId]);

    // Soft-delete: set is_approved = 0 and clear redirect_uris (prevents new auths)
    $db->prepare("UPDATE alfred_oauth_apps SET is_approved = 0, redirect_uris = '[]' WHERE id = :id")
       ->execute([':id' => $appId]);

    jsonResponse(['success' => true, 'message' => 'App deactivated and all tokens revoked']);
}

// ══════════════════════════════════════════════════════════════════════════════
//  CONSENT PAGE RENDERER
// ══════════════════════════════════════════════════════════════════════════════

function renderConsentPage(array $app, array $scopes, string $redirectUri, string $state, string $csrfToken, string $userEmail, string $userName): void {
    $appName    = htmlspecialchars($app['name'], ENT_QUOTES, 'UTF-8');
    $appLogo    = htmlspecialchars($app['logo_url'] ?? '', ENT_QUOTES, 'UTF-8');
    $appWebsite = htmlspecialchars($app['website_url'] ?? '', ENT_QUOTES, 'UTF-8');
    $clientId   = htmlspecialchars($app['client_id'], ENT_QUOTES, 'UTF-8');
    $safeEmail  = htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8');
    $safeName   = htmlspecialchars($userName, ENT_QUOTES, 'UTF-8');
    $safeRedirect = htmlspecialchars($redirectUri, ENT_QUOTES, 'UTF-8');
    $safeState    = htmlspecialchars($state, ENT_QUOTES, 'UTF-8');
    $safeScope    = htmlspecialchars(implode(' ', $scopes), ENT_QUOTES, 'UTF-8');
    $safeCsrf     = htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8');

    // Build scope list HTML
    $scopeListHtml = '';
    foreach ($scopes as $s) {
        $label = htmlspecialchars(VALID_SCOPES[$s] ?? $s, ENT_QUOTES, 'UTF-8');
        $code  = htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $scopeListHtml .= "<li><span class=\"scope-code\">{$code}</span> &mdash; {$label}</li>\n";
    }

    $logoHtml = '';
    if (!empty($appLogo)) {
        $logoHtml = "<img src=\"{$appLogo}\" alt=\"{$appName}\" class=\"app-logo\">";
    } else {
        $initial = mb_strtoupper(mb_substr($app['name'], 0, 1));
        $logoHtml = "<div class=\"app-logo-placeholder\">{$initial}</div>";
    }

    $websiteHtml = '';
    if (!empty($appWebsite)) {
        $websiteHtml = "<a href=\"{$appWebsite}\" target=\"_blank\" rel=\"noopener\" class=\"app-website\">{$appWebsite}</a>";
    }

    header('Content-Type: text/html; charset=UTF-8');
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Authorize {$appName} — Alfred AI</title>
<style>
:root {
    --al-bg: #0a0a14;
    --al-surface: #12121e;
    --al-surface-2: #1a1a2e;
    --al-accent: #6c5ce7;
    --al-accent-hover: #7d6ff0;
    --al-text: #e0e0e0;
    --al-text-muted: #888;
    --al-border: #2a2a3e;
    --al-danger: #e74c3c;
    --al-danger-hover: #c0392b;
    --al-success: #2ecc71;
    --al-radius: 12px;
}
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: var(--al-bg);
    color: var(--al-text);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.consent-card {
    background: var(--al-surface);
    border: 1px solid var(--al-border);
    border-radius: var(--al-radius);
    max-width: 480px;
    width: 100%;
    overflow: hidden;
}
.consent-header {
    background: var(--al-surface-2);
    padding: 20px 24px;
    border-bottom: 1px solid var(--al-border);
    text-align: center;
}
.user-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(108,92,231,0.15);
    border: 1px solid rgba(108,92,231,0.3);
    border-radius: 20px;
    padding: 4px 14px;
    font-size: 13px;
    color: var(--al-accent);
    margin-bottom: 12px;
}
.user-badge svg { width: 14px; height: 14px; fill: currentColor; }
.app-logo {
    width: 64px;
    height: 64px;
    border-radius: 16px;
    object-fit: cover;
    margin: 12px auto;
    display: block;
    border: 2px solid var(--al-border);
}
.app-logo-placeholder {
    width: 64px;
    height: 64px;
    border-radius: 16px;
    background: var(--al-accent);
    color: #fff;
    font-size: 28px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 12px auto;
}
.consent-header h1 {
    font-size: 20px;
    font-weight: 600;
    margin-top: 8px;
}
.consent-header h1 strong { color: var(--al-accent); }
.app-website {
    display: block;
    font-size: 12px;
    color: var(--al-text-muted);
    margin-top: 4px;
    text-decoration: none;
}
.app-website:hover { color: var(--al-accent); }
.consent-body { padding: 24px; }
.section-label {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--al-text-muted);
    margin-bottom: 12px;
    font-weight: 600;
}
.scope-list {
    list-style: none;
    margin-bottom: 20px;
}
.scope-list li {
    padding: 10px 14px;
    background: var(--al-surface-2);
    border: 1px solid var(--al-border);
    border-radius: 8px;
    margin-bottom: 6px;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.scope-list li::before {
    content: '✓';
    color: var(--al-success);
    font-weight: 700;
    flex-shrink: 0;
}
.scope-code {
    font-family: 'SF Mono', 'Fira Code', monospace;
    background: rgba(108,92,231,0.15);
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 12px;
    color: var(--al-accent);
}
.warning-box {
    background: rgba(231,76,60,0.1);
    border: 1px solid rgba(231,76,60,0.3);
    border-radius: 8px;
    padding: 12px 14px;
    font-size: 13px;
    color: #e8a09a;
    margin-bottom: 20px;
    display: flex;
    gap: 8px;
    align-items: flex-start;
}
.warning-box svg { width: 18px; height: 18px; fill: var(--al-danger); flex-shrink: 0; margin-top: 1px; }
.consent-actions {
    display: flex;
    gap: 12px;
    padding: 0 24px 24px;
}
.btn {
    flex: 1;
    padding: 12px 20px;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}
.btn-allow {
    background: var(--al-accent);
    color: #fff;
}
.btn-allow:hover { background: var(--al-accent-hover); transform: translateY(-1px); }
.btn-deny {
    background: transparent;
    color: var(--al-text-muted);
    border: 1px solid var(--al-border);
}
.btn-deny:hover { background: var(--al-surface-2); color: var(--al-danger); border-color: var(--al-danger); }
.alfred-footer {
    text-align: center;
    padding: 16px;
    border-top: 1px solid var(--al-border);
    font-size: 12px;
    color: var(--al-text-muted);
}
.alfred-footer a { color: var(--al-accent); text-decoration: none; }
.alfred-footer a:hover { text-decoration: underline; }
</style>
</head>
<body>
<div class="consent-card">
    <div class="consent-header">
        <div class="user-badge">
            <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
            Logged in as {$safeEmail}
        </div>
        {$logoHtml}
        <h1><strong>{$appName}</strong> wants to access your Alfred account</h1>
        {$websiteHtml}
    </div>

    <div class="consent-body">
        <div class="section-label">This app will be able to:</div>
        <ul class="scope-list">
            {$scopeListHtml}
        </ul>

        <div class="warning-box">
            <svg viewBox="0 0 24 24"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>
            <div>
                Authorizing this app grants it access to perform the actions listed above on your behalf.
                You can revoke access at any time from your <a href="/developer-portal.php" style="color:var(--al-accent)">Developer Portal</a>.
            </div>
        </div>
    </div>

    <div class="consent-actions">
        <form method="POST" action="/api/oauth.php?action=authorize/deny" style="flex:1;display:flex">
            <input type="hidden" name="csrf_token" value="{$safeCsrf}">
            <input type="hidden" name="redirect_uri" value="{$safeRedirect}">
            <input type="hidden" name="state" value="{$safeState}">
            <button type="submit" class="btn btn-deny" style="width:100%">Deny</button>
        </form>
        <form method="POST" action="/api/oauth.php?action=authorize/grant" style="flex:1;display:flex">
            <input type="hidden" name="csrf_token" value="{$safeCsrf}">
            <input type="hidden" name="client_id" value="{$clientId}">
            <input type="hidden" name="redirect_uri" value="{$safeRedirect}">
            <input type="hidden" name="scope" value="{$safeScope}">
            <input type="hidden" name="state" value="{$safeState}">
            <button type="submit" class="btn btn-allow" style="width:100%">Allow</button>
        </form>
    </div>

    <div class="alfred-footer">
        Powered by <a href="https://gositeme.com">Alfred AI</a> &mdash; OAuth 2.0 Authorization
    </div>
</div>
</body>
</html>
HTML;
    exit;
}
