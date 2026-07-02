<?php
/**
 * Alfred API v1 Router
 * 
 * Main entry point for the public REST API.
 * Handles authentication, rate limiting, routing, CORS, and usage logging.
 * 
 * @version 1.0.0
 * @since 2026-03-04
 */

declare(strict_types=1);

// Prevent the legacy config from dying on direct access
define('GOSITEME_API', true);

// ─── Bootstrap ──────────────────────────────────────────────────────────────

$rootDir = dirname(__DIR__, 2);
require_once dirname(__DIR__) . '/config.php';

// ─── Constants ──────────────────────────────────────────────────────────────

define('API_VERSION', '1.0.0');
define('API_PREFIX', '/api/v1');
define('RATE_LIMIT_DIR', $rootDir . '/cache/rate_limits');

// Ensure rate limit directory exists
if (!is_dir(RATE_LIMIT_DIR)) {
    @mkdir(RATE_LIMIT_DIR, 0755, true);
}

// Rate limit tiers: [requests_per_minute, requests_per_hour, requests_per_day]
define('RATE_TIERS', [
    'free'       => ['per_minute' => 10,  'per_hour' => 100,   'per_day' => 500],
    'starter'    => ['per_minute' => 30,  'per_hour' => 500,   'per_day' => 5000],
    'pro'        => ['per_minute' => 60,  'per_hour' => 2000,  'per_day' => 20000],
    'professional' => ['per_minute' => 60, 'per_hour' => 2000, 'per_day' => 20000],
    'enterprise' => ['per_minute' => 200, 'per_hour' => 10000, 'per_day' => 100000],
]);

// ─── CORS Headers ───────────────────────────────────────────────────────────

$allowedOrigins = ['https://gositeme.com', 'https://www.gositeme.com'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    header('Access-Control-Allow-Origin: https://gositeme.com');
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Request-ID');
header('Access-Control-Expose-Headers: X-RateLimit-Limit, X-RateLimit-Remaining, X-RateLimit-Reset, X-Request-ID');
header('Content-Type: application/json; charset=utf-8');
header('X-API-Version: ' . API_VERSION);

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    header('Access-Control-Max-Age: 86400');
    exit;
}

// ─── Request ID ─────────────────────────────────────────────────────────────

$requestId = $_SERVER['HTTP_X_REQUEST_ID'] ?? bin2hex(random_bytes(16));
header('X-Request-ID: ' . $requestId);

// ─── Start Timing ───────────────────────────────────────────────────────────

$requestStart = microtime(true);

// ─── Parse Route ────────────────────────────────────────────────────────────

$route = parseRoute();
$method = strtoupper($_SERVER['REQUEST_METHOD']);

// ─── Public routes (no auth needed) ─────────────────────────────────────────

if ($route['resource'] === '' && $method === 'GET') {
    respond([
        'name'    => 'Alfred API',
        'version' => API_VERSION,
        'status'  => 'operational',
        'docs'    => SITE_URL . '/docs/api-reference.php',
        'endpoints' => [
            'chat'        => API_PREFIX . '/chat',
            'tools'       => API_PREFIX . '/tools',
            'agents'      => API_PREFIX . '/agents',
            'voice'       => API_PREFIX . '/voice',
            'fleets'      => API_PREFIX . '/fleets',
            'marketplace' => API_PREFIX . '/marketplace',
            'usage'       => API_PREFIX . '/usage',
            'billing'     => API_PREFIX . '/billing',
        ]
    ]);
}

// ─── Authenticate ───────────────────────────────────────────────────────────

$auth = authenticate();
if ($auth === null) {
    respondError('Authentication required. Provide a valid API key or OAuth token via Authorization: Bearer <token>', 401, 'auth_required');
}

// ─── Rate Limiting ──────────────────────────────────────────────────────────

$rateLimitKey = ($auth['key_prefix'] ?? 'oauth') . '_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
$rateResult = rateLimit($auth['tier'] ?? 'free', $rateLimitKey);

// Set rate limit headers
header('X-RateLimit-Limit: ' . $rateResult['limit']);
header('X-RateLimit-Remaining: ' . $rateResult['remaining']);
header('X-RateLimit-Reset: ' . $rateResult['reset']);

if (!$rateResult['allowed']) {
    respondError('Rate limit exceeded. Retry after ' . ($rateResult['reset'] - time()) . ' seconds.', 429, 'rate_limit_exceeded');
}

// ─── Route Dispatch ─────────────────────────────────────────────────────────

$resourceHandlers = [
    'tools'       => __DIR__ . '/resources/tools.php',
    'agents'      => __DIR__ . '/resources/agents.php',
    'voice'       => __DIR__ . '/resources/voice.php',
    'fleets'      => __DIR__ . '/resources/fleets.php',
    'marketplace' => __DIR__ . '/resources/marketplace.php',
    'usage'       => __DIR__ . '/resources/usage.php',
    'chat'        => __DIR__ . '/resources/chat.php',
    'billing'     => __DIR__ . '/resources/usage.php',  // billing handled by usage handler
];

$resource = $route['resource'];

if (!isset($resourceHandlers[$resource])) {
    respondError("Unknown resource '{$resource}'. Available: " . implode(', ', array_keys($resourceHandlers)), 404, 'resource_not_found');
}

$handlerFile = $resourceHandlers[$resource];
if (!file_exists($handlerFile)) {
    respondError("Resource handler not available for '{$resource}'.", 503, 'service_unavailable');
}

// Make context available to resource handlers
$apiContext = [
    'auth'       => $auth,
    'route'      => $route,
    'method'     => $method,
    'request_id' => $requestId,
    'start_time' => $requestStart,
    'body'       => getRequestBody(),
    'query'      => $_GET,
];

require_once $handlerFile;

// The resource handler should call a handle function
// For billing, map to usage handler
$handlerResource = $resource === 'billing' ? 'usage' : $resource;
$handlerFunction = 'handle' . ucfirst($handlerResource) . 'Request';

// For billing, override the route to point to billing sub-resource
if ($resource === 'billing') {
    $apiContext['route']['resource'] = 'usage';
    $apiContext['route']['id'] = 'billing';
}

if (function_exists($handlerFunction)) {
    $handlerFunction($apiContext);
} else {
    respondError("Resource handler misconfigured for '{$resource}'.", 500, 'internal_error');
}

// ═══════════════════════════════════════════════════════════════════════════
// Helper Functions
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Parse the URL path into resource, id, and sub-resource components
 *
 * @return array{resource: string, id: string|null, sub: string|null, extra: string|null}
 */
function parseRoute(): array
{
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    
    // Remove query string
    $path = parse_url($requestUri, PHP_URL_PATH);
    
    // Remove the /api/v1/ prefix
    $prefix = '/api/v1/';
    if (str_starts_with($path, $prefix)) {
        $path = substr($path, strlen($prefix));
    } else {
        $path = ltrim($path, '/');
    }
    
    // Remove trailing slash
    $path = rtrim($path, '/');
    
    // Split into segments
    $segments = $path !== '' ? explode('/', $path) : [];
    
    return [
        'resource' => $segments[0] ?? '',
        'id'       => $segments[1] ?? null,
        'sub'      => $segments[2] ?? null,
        'extra'    => $segments[3] ?? null,
    ];
}

/**
 * Authenticate the request via API Key or OAuth2 Bearer token
 *
 * API Key format:  ak_live_<prefix>_<secret>
 * OAuth2 format:   Standard Bearer token from alfred_oauth_tokens
 *
 * @return array|null Authentication context or null if invalid
 */
function authenticate(): ?array
{
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] 
        ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] 
        ?? '';
    
    // Also check for api_key query param (less secure, for testing)
    $queryKey = $_GET['api_key'] ?? '';
    
    if (empty($authHeader) && empty($queryKey)) {
        return null;
    }
    
    // Extract bearer token
    $token = '';
    if (preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
        $token = trim($matches[1]);
    } elseif (!empty($queryKey)) {
        $token = trim($queryKey);
    }
    
    if (empty($token)) {
        return null;
    }
    
    $db = getDB();
    if (!$db) {
        error_log('API v1: Database connection failed during authentication');
        return null;
    }
    
    // Determine if this is an API key or OAuth token
    if (str_starts_with($token, 'ak_live_') || str_starts_with($token, 'ak_test_')) {
        return authenticateApiKey($db, $token);
    } else {
        return authenticateOAuthToken($db, $token);
    }
}

/**
 * Authenticate via API Key
 * Key format: ak_live_<prefix>_<secret>  or  ak_test_<prefix>_<secret>
 */
function authenticateApiKey(PDO $db, string $token): ?array
{
    // Parse the token: ak_live_PREFIX_SECRET  or  ak_test_PREFIX_SECRET
    // Prefix is first 8 chars after ak_live_ / ak_test_
    $parts = explode('_', $token);
    // Expected: ['ak', 'live'|'test', '<prefix>', '<secret>']
    if (count($parts) < 4) {
        return null;
    }
    
    $environment = $parts[1]; // 'live' or 'test'
    $prefix = $parts[2];
    $secretPart = implode('_', array_slice($parts, 3));
    
    // Compute the hash of the full token
    $tokenHash = hash('sha256', $token);
    
    try {
        $stmt = $db->prepare("
            SELECT ak.*, ak.user_id, ak.scopes, ak.rate_limit_tier, ak.is_active, ak.name as key_name
            FROM alfred_api_keys ak
            WHERE ak.key_prefix = :prefix
              AND ak.key_hash = :hash
              AND ak.is_active = 1
            LIMIT 1
        ");
        $stmt->execute([
            ':prefix' => $prefix,
            ':hash'   => $tokenHash,
        ]);
        
        $key = $stmt->fetch();
        
        if (!$key) {
            return null;
        }
        
        // Check expiration
        if (!empty($key['expires_at']) && strtotime($key['expires_at']) < time()) {
            return null;
        }
        
        // Update last_used_at
        $updateStmt = $db->prepare("UPDATE alfred_api_keys SET last_used_at = NOW() WHERE id = :id");
        $updateStmt->execute([':id' => $key['id']]);
        
        $scopes = !empty($key['scopes']) ? json_decode($key['scopes'], true) : ['*'];
        if (!is_array($scopes)) {
            $scopes = ['*'];
        }
        
        return [
            'type'        => 'api_key',
            'user_id'     => (int) $key['user_id'],
            'key_id'      => (int) $key['id'],
            'key_name'    => $key['key_name'] ?? '',
            'key_prefix'  => $prefix,
            'scopes'      => $scopes,
            'tier'        => $key['rate_limit_tier'] ?? 'free',
            'environment' => $environment,
        ];
    } catch (PDOException $e) {
        error_log('API v1: API key auth failed: ' . $e->getMessage());
        return null;
    }
}

/**
 * Authenticate via OAuth2 Bearer token
 */
function authenticateOAuthToken(PDO $db, string $token): ?array
{
    try {
        $tokenHash = hash('sha256', $token);
        
        $stmt = $db->prepare("
            SELECT ot.*, oa.name as app_name, oa.rate_limit_tier
            FROM alfred_oauth_tokens ot
            JOIN alfred_oauth_apps oa ON ot.app_id = oa.id
            WHERE ot.access_token_hash = :hash
              AND ot.revoked = 0
              AND ot.expires_at > NOW()
            LIMIT 1
        ");
        $stmt->execute([':hash' => $tokenHash]);
        
        $tokenRow = $stmt->fetch();
        
        if (!$tokenRow) {
            return null;
        }
        
        $scopes = !empty($tokenRow['scopes']) ? json_decode($tokenRow['scopes'], true) : ['*'];
        if (!is_array($scopes)) {
            $scopes = ['*'];
        }
        
        return [
            'type'       => 'oauth',
            'user_id'    => (int) $tokenRow['user_id'],
            'app_id'     => (int) $tokenRow['app_id'],
            'app_name'   => $tokenRow['app_name'] ?? '',
            'key_prefix' => 'oauth_' . $tokenRow['app_id'],
            'scopes'     => $scopes,
            'tier'       => $tokenRow['rate_limit_tier'] ?? 'free',
        ];
    } catch (PDOException $e) {
        error_log('API v1: OAuth auth failed: ' . $e->getMessage());
        return null;
    }
}

/**
 * File-based rate limiting
 *
 * Uses cache/rate_limits/ directory with per-minute sliding window.
 *
 * @param string $tier     Rate limit tier (free, starter, pro, enterprise)
 * @param string $limitKey Unique key (IP + API key prefix)
 * @return array{allowed: bool, limit: int, remaining: int, reset: int}
 */
function rateLimit(string $tier, string $limitKey): array
{
    $tiers = RATE_TIERS;
    $limits = $tiers[$tier] ?? $tiers['free'];
    $perMinute = $limits['per_minute'];
    
    $safeKey = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $limitKey);
    $file = RATE_LIMIT_DIR . '/' . $safeKey . '.json';
    
    $now = time();
    $windowStart = $now - 60; // 1-minute sliding window
    $resetTime = $now + 60;
    
    $requests = [];
    
    // Read existing rate data
    if (file_exists($file)) {
        $data = @file_get_contents($file);
        if ($data !== false) {
            $requests = json_decode($data, true) ?: [];
        }
    }
    
    // Remove expired entries (older than 1 minute)
    $requests = array_values(array_filter($requests, fn(int $ts) => $ts > $windowStart));
    
    $currentCount = count($requests);
    $remaining = max(0, $perMinute - $currentCount);
    $allowed = $currentCount < $perMinute;
    
    if ($allowed) {
        $requests[] = $now;
        @file_put_contents($file, json_encode($requests), LOCK_EX);
    }
    
    return [
        'allowed'   => $allowed,
        'limit'     => $perMinute,
        'remaining' => $allowed ? $remaining - 1 : 0,
        'reset'     => $resetTime,
    ];
}

/**
 * Send a successful JSON response
 *
 * @param mixed $data       Response data
 * @param int   $statusCode HTTP status code
 */
function respond(mixed $data, int $statusCode = 200): never
{
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Send a JSON error response
 *
 * @param string $message    Error message
 * @param int    $statusCode HTTP status code
 * @param string $errorCode  Machine-readable error code
 */
function respondError(string $message, int $statusCode, string $errorCode = 'error'): never
{
    http_response_code($statusCode);
    echo json_encode([
        'error' => [
            'code'    => $errorCode,
            'message' => $message,
            'status'  => $statusCode,
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Log API usage to the alfred_usage table
 *
 * @param int    $userId   The user making the request
 * @param string $resource The resource accessed (tools, agents, etc.)
 * @param int    $quantity Number of units consumed
 * @param string $endpoint Specific endpoint path
 */
function logUsage(int $userId, string $resource, int $quantity = 1, string $endpoint = ''): void
{
    try {
        $db = getDB();
        if (!$db) return;
        
        $stmt = $db->prepare("
            INSERT INTO alfred_usage (user_id, resource_type, endpoint, quantity, ip_address, created_at)
            VALUES (:user_id, :resource, :endpoint, :quantity, :ip, NOW())
        ");
        $stmt->execute([
            ':user_id'  => $userId,
            ':resource' => $resource,
            ':endpoint' => $endpoint ?: ($_SERVER['REQUEST_URI'] ?? ''),
            ':quantity' => $quantity,
            ':ip'       => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);
    } catch (PDOException $e) {
        error_log('API v1: Usage log failed: ' . $e->getMessage());
    }
}

/**
 * Validate that the authenticated user has the required scopes
 *
 * @param array|string $required Required scopes
 * @param array        $granted  Granted scopes from auth context
 * @return bool
 */
function validateScopes(array|string $required, array $granted): bool
{
    // Wildcard grants all access
    if (in_array('*', $granted, true)) {
        return true;
    }
    
    if (is_string($required)) {
        $required = [$required];
    }
    
    foreach ($required as $scope) {
        if (!in_array($scope, $granted, true)) {
            // Check for wildcard within category (e.g., "tools:*" grants "tools:read")
            $category = explode(':', $scope)[0] ?? '';
            if (!in_array($category . ':*', $granted, true)) {
                return false;
            }
        }
    }
    
    return true;
}

/**
 * Dispatch a webhook event asynchronously
 *
 * @param int    $userId  User ID
 * @param string $event   Event name (e.g., 'tool.executed', 'agent.deployed')
 * @param array  $payload Event payload
 */
function dispatchWebhook(int $userId, string $event, array $payload): void
{
    try {
        $db = getDB();
        if (!$db) return;
        
        // Find active webhooks for this user and event
        $stmt = $db->prepare("
            SELECT id, url, secret, events
            FROM alfred_webhooks
            WHERE user_id = :user_id
              AND is_active = 1
        ");
        $stmt->execute([':user_id' => $userId]);
        $webhooks = $stmt->fetchAll();
        
        foreach ($webhooks as $webhook) {
            $events = json_decode($webhook['events'], true) ?: [];
            
            // Check if this webhook subscribes to this event
            $eventMatches = in_array($event, $events, true) || in_array('*', $events, true);
            
            // Check wildcard category match (e.g., "tool.*" matches "tool.executed")
            if (!$eventMatches) {
                $eventCategory = explode('.', $event)[0] ?? '';
                $eventMatches = in_array($eventCategory . '.*', $events, true);
            }
            
            if (!$eventMatches) continue;
            
            $deliveryId = bin2hex(random_bytes(16));
            $deliveryPayload = json_encode([
                'id'        => $deliveryId,
                'event'     => $event,
                'timestamp' => date('c'),
                'data'      => $payload,
            ]);
            
            // Create delivery record
            $deliverStmt = $db->prepare("
                INSERT INTO alfred_webhook_deliveries 
                    (webhook_id, event, payload, status, created_at)
                VALUES (:wh_id, :event, :payload, 'pending', NOW())
            ");
            $deliverStmt->execute([
                ':wh_id'   => $webhook['id'],
                ':event'   => $event,
                ':payload' => $deliveryPayload,
            ]);
            $deliveryDbId = $db->lastInsertId();
            
            // Sign the payload
            $signature = hash_hmac('sha256', $deliveryPayload, $webhook['secret'] ?? '');
            
            // Fire-and-forget HTTP POST (non-blocking)
            $ch = curl_init($webhook['url']);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $deliveryPayload,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'X-Webhook-Signature: sha256=' . $signature,
                    'X-Webhook-Event: ' . $event,
                    'X-Webhook-Delivery: ' . $deliveryId,
                    'User-Agent: Alfred-Webhooks/1.0',
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            // Update delivery status
            $status = ($httpCode >= 200 && $httpCode < 300) ? 'delivered' : 'failed';
            $updateStmt = $db->prepare("
                UPDATE alfred_webhook_deliveries 
                SET status = :status, 
                    response_code = :code, 
                    response_body = :body,
                    delivered_at = NOW()
                WHERE id = :id
            ");
            $updateStmt->execute([
                ':status' => $status,
                ':code'   => $httpCode,
                ':body'   => substr($response ?: $error, 0, 1000),
                ':id'     => $deliveryDbId,
            ]);
        }
    } catch (PDOException $e) {
        error_log('API v1: Webhook dispatch failed: ' . $e->getMessage());
    }
}

/**
 * Get the parsed JSON request body
 *
 * @return array
 */
function getRequestBody(): array
{
    $raw = file_get_contents('php://input');
    if (empty($raw)) {
        return [];
    }
    
    $data = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [];
    }
    
    return $data ?: [];
}

/**
 * Build a paginated response envelope
 *
 * @param array $data    Data items for current page
 * @param int   $total   Total number of items
 * @param int   $page    Current page number
 * @param int   $perPage Items per page
 * @return array
 */
function paginatedResponse(array $data, int $total, int $page, int $perPage): array
{
    return [
        'data' => $data,
        'meta' => [
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => (int) ceil($total / max(1, $perPage)),
        ],
    ];
}

/**
 * Get pagination parameters from query string
 *
 * @return array{page: int, per_page: int, offset: int}
 */
function getPagination(): array
{
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $perPage = min(100, max(1, (int) ($_GET['per_page'] ?? 20)));
    $offset = ($page - 1) * $perPage;
    
    return [
        'page'     => $page,
        'per_page' => $perPage,
        'offset'   => $offset,
    ];
}

/**
 * Require specific scopes or respond with 403
 *
 * @param array  $auth     Auth context
 * @param string|array $scopes Required scopes
 */
function requireScopes(array $auth, string|array $scopes): void
{
    if (!validateScopes($scopes, $auth['scopes'])) {
        $scopeList = is_array($scopes) ? implode(', ', $scopes) : $scopes;
        respondError("Insufficient permissions. Required scope(s): {$scopeList}", 403, 'insufficient_scope');
    }
}

/**
 * Validate that required fields are present in the request body
 *
 * @param array $body     Request body
 * @param array $required Required field names
 * @return array Validated data (trimmed strings)
 */
function validateRequired(array $body, array $required): array
{
    $missing = [];
    $validated = [];
    
    foreach ($required as $field) {
        if (!isset($body[$field]) || (is_string($body[$field]) && trim($body[$field]) === '')) {
            $missing[] = $field;
        } else {
            $validated[$field] = is_string($body[$field]) ? trim($body[$field]) : $body[$field];
        }
    }
    
    if (!empty($missing)) {
        respondError('Missing required fields: ' . implode(', ', $missing), 400, 'validation_error');
    }
    
    return $validated;
}
