<?php
/**
 * Alfred OS — Sovereignty API Gateway
 * 
 * Single controlled proxy for ALL external API calls.
 * Online: Proxies to external APIs, caches responses.
 * Offline: Serves cached responses with graceful degradation.
 * 
 * Usage:
 *   POST /api/gateway.php
 *   Body: { "service": "openai", "endpoint": "/chat/completions", "payload": {...} }
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json');
header('X-Gateway-Mode: ' . (SovereigntyGateway::isOffline() ? 'offline' : 'online'));

// ─── Service Registry ───────────────────────────────────────────────
// Each service defines: base_url, auth_header, api_key_env
$SERVICE_REGISTRY = [
    'openai' => [
        'base_url'    => 'https://api.openai.com/v1',
        'auth_header' => 'Authorization: Bearer {key}',
        'api_key_env' => 'OPENAI_API_KEY',
        'cacheable'   => true,
        'cache_ttl'   => 3600,
    ],
    'anthropic' => [
        'base_url'    => 'https://api.anthropic.com/v1',
        'auth_header' => 'x-api-key: {key}',
        'api_key_env' => 'ANTHROPIC_API_KEY',
        'cacheable'   => true,
        'cache_ttl'   => 3600,
    ],
    'groq' => [
        'base_url'    => 'https://api.groq.com/openai/v1',
        'auth_header' => 'Authorization: Bearer {key}',
        'api_key_env' => 'GROQ_API_KEY',
        'cacheable'   => true,
        'cache_ttl'   => 3600,
    ],
    'together' => [
        'base_url'    => 'https://api.together.xyz/v1',
        'auth_header' => 'Authorization: Bearer {key}',
        'api_key_env' => 'TOGETHER_API_KEY',
        'cacheable'   => true,
        'cache_ttl'   => 3600,
    ],
    'vapi' => [
        'base_url'    => 'https://api.vapi.ai',
        'auth_header' => 'Authorization: Bearer {key}',
        'api_key_env' => 'VAPI_API_KEY',
        'cacheable'   => false,
        'cache_ttl'   => 0,
    ],
    'telnyx' => [
        'base_url'    => 'https://api.telnyx.com/v2',
        'auth_header' => 'Authorization: Bearer {key}',
        'api_key_env' => 'TELNYX_API_KEY',
        'cacheable'   => false,
        'cache_ttl'   => 0,
    ],
    'stripe' => [
        'base_url'    => 'https://api.stripe.com/v1',
        'auth_header' => 'Authorization: Bearer {key}',
        'api_key_env' => 'STRIPE_SECRET_KEY',
        'cacheable'   => false,
        'cache_ttl'   => 0,
    ],
    'weather' => [
        'base_url'    => 'https://api.openweathermap.org/data/2.5',
        'auth_header' => null,
        'api_key_env' => 'OPENWEATHER_API_KEY',
        'cacheable'   => true,
        'cache_ttl'   => 1800,
    ],
];

class SovereigntyGateway
{
    private static string $cacheDir;
    private static bool $offlineMode;

    public static function init(): void
    {
        self::$cacheDir = dirname(__DIR__) . '/cache/gateway';
        if (!is_dir(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0750, true);
        }

        // Offline detection: check flag file OR test connectivity
        $flagFile = dirname(__DIR__) . '/.sovereignty-offline';
        self::$offlineMode = file_exists($flagFile) || !self::canReachInternet();
    }

    public static function isOffline(): bool
    {
        return self::$offlineMode;
    }

    /**
     * Quick internet connectivity check (cached for 60s).
     */
    private static function canReachInternet(): bool
    {
        $checkFile = self::$cacheDir . '/_connectivity.json';
        if (file_exists($checkFile)) {
            $data = json_decode(file_get_contents($checkFile), true);
            if ($data && (time() - ($data['ts'] ?? 0)) < 60) {
                return $data['online'] ?? false;
            }
        }

        $ch = curl_init('https://connectivity.gositeme.com/ping');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 3,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_NOBODY         => true,
        ]);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Fallback: try Google DNS
        if ($httpCode === 0) {
            $online = @fsockopen('8.8.8.8', 53, $errno, $errstr, 2) !== false;
        } else {
            $online = $httpCode >= 200 && $httpCode < 500;
        }

        file_put_contents($checkFile, json_encode([
            'ts' => time(), 'online' => $online
        ]));

        return $online;
    }

    /**
     * Generate a deterministic cache key for a request.
     */
    private static function cacheKey(string $service, string $endpoint, array $payload): string
    {
        $hash = hash('sha256', $service . $endpoint . json_encode($payload));
        return self::$cacheDir . '/' . $service . '_' . substr($hash, 0, 16) . '.json';
    }

    /**
     * Get cached response if available and not expired.
     */
    public static function getCached(string $service, string $endpoint, array $payload, int $ttl): ?array
    {
        $file = self::cacheKey($service, $endpoint, $payload);
        if (!file_exists($file)) return null;

        $data = json_decode(file_get_contents($file), true);
        if (!$data) return null;

        if ($ttl > 0 && (time() - ($data['cached_at'] ?? 0)) > $ttl) {
            // Expired but still return in offline mode
            if (!self::$offlineMode) return null;
        }

        $data['_from_cache'] = true;
        $data['_cached_at'] = $data['cached_at'] ?? 0;
        return $data;
    }

    /**
     * Store response in cache.
     */
    public static function setCache(string $service, string $endpoint, array $payload, array $response): void
    {
        $file = self::cacheKey($service, $endpoint, $payload);
        $response['cached_at'] = time();
        file_put_contents($file, json_encode($response, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Execute proxied API request.
     */
    public static function proxy(string $service, string $endpoint, array $payload, array $config): array
    {
        $url = rtrim($config['base_url'], '/') . '/' . ltrim($endpoint, '/');

        // For weather API, append key as query param
        if ($service === 'weather') {
            $apiKey = getenv($config['api_key_env']) ?: '';
            $separator = str_contains($url, '?') ? '&' : '?';
            $url .= $separator . 'appid=' . urlencode($apiKey);
            
            // Weather uses GET with query params
            if (!empty($payload)) {
                foreach ($payload as $k => $v) {
                    $url .= '&' . urlencode($k) . '=' . urlencode((string)$v);
                }
            }
        }

        $headers = ['Content-Type: application/json'];

        if (!empty($config['auth_header']) && !empty($config['api_key_env'])) {
            $apiKey = getenv($config['api_key_env']) ?: '';
            if ($apiKey) {
                $headers[] = str_replace('{key}', $apiKey, $config['auth_header']);
            }
        }

        // Anthropic needs version header
        if ($service === 'anthropic') {
            $headers[] = 'anthropic-version: 2023-06-01';
        }

        $ch = curl_init($url);
        $curlOpts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => $headers,
        ];

        if ($service !== 'weather') {
            $curlOpts[CURLOPT_POST] = true;
            $curlOpts[CURLOPT_POSTFIELDS] = json_encode($payload);
        }

        curl_setopt_array($ch, $curlOpts);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['error' => 'Gateway connection failed', 'detail' => $error, 'http_code' => 0];
        }

        $decoded = json_decode($response, true) ?: ['raw' => $response];
        $decoded['_http_code'] = $httpCode;

        return $decoded;
    }
}

// ─── Request Handler ────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'error' => 'Method not allowed',
        'mode'  => SovereigntyGateway::isOffline() ? 'offline' : 'online',
        'services' => array_keys($SERVICE_REGISTRY),
    ]);
    exit;
}

SovereigntyGateway::init();

// ─── Authentication: internal calls only ─────────────────────────────
// Require session auth OR internal secret header
$gatewayAuthed = false;
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!empty($_SESSION['logged_in']) && !empty($_SESSION['client_id'])) {
    $gatewayAuthed = true;
}
$internalSecret = getenv('INTERNAL_API_SECRET') ?: '';
$headerSecret = $_SERVER['HTTP_X_INTERNAL_SECRET'] ?? '';
if ($internalSecret && $headerSecret && hash_equals($internalSecret, $headerSecret)) {
    $gatewayAuthed = true;
}
// Also allow if the request is from the same server (internal PHP calls)
if (($_SERVER['REMOTE_ADDR'] ?? '') === '127.0.0.1' || ($_SERVER['REMOTE_ADDR'] ?? '') === '::1') {
    $gatewayAuthed = true;
}
if (!$gatewayAuthed) {
    http_response_code(403);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['service']) || !isset($input['endpoint'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Required: service, endpoint']);
    exit;
}

$service  = $input['service'];
$endpoint = $input['endpoint'];
$payload  = $input['payload'] ?? [];

// ─── Endpoint sanitization ───────────────────────────────────────────
// Prevent path traversal: endpoint must start with / and not contain ..
$endpoint = '/' . ltrim($endpoint, '/');
if (str_contains($endpoint, '..') || str_contains($endpoint, '//')) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid endpoint path']);
    exit;
}

// Validate service exists
if (!isset($SERVICE_REGISTRY[$service])) {
    http_response_code(400);
    echo json_encode(['error' => 'Unknown service', 'available' => array_keys($SERVICE_REGISTRY)]);
    exit;
}

$config = $SERVICE_REGISTRY[$service];

// Check cache first (for cacheable services)
if ($config['cacheable']) {
    $cached = SovereigntyGateway::getCached($service, $endpoint, $payload, $config['cache_ttl']);
    if ($cached) {
        // In offline mode, always return cache. Online, only if not expired (handled inside getCached).
        echo json_encode($cached);
        exit;
    }
}

// If offline and no cache, return degraded response
if (SovereigntyGateway::isOffline()) {
    http_response_code(503);
    echo json_encode([
        'error' => 'Sovereignty mode: offline — no cached response available',
        'service' => $service,
        'mode' => 'offline',
        'suggestion' => 'This endpoint has not been cached yet. Connect to the internet to prime the cache.',
    ]);
    exit;
}

// Online mode — proxy the request
$result = SovereigntyGateway::proxy($service, $endpoint, $payload, $config);

// Cache successful responses
if ($config['cacheable'] && ($result['_http_code'] ?? 0) >= 200 && ($result['_http_code'] ?? 0) < 300) {
    SovereigntyGateway::setCache($service, $endpoint, $payload, $result);
}

echo json_encode($result);
