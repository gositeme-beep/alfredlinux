<?php
$GLOBALS['CSRF_EXEMPT'] = true; // External monitoring probes
require_once dirname(__DIR__) . '/includes/api-security.php';
/**
 * Alfred AI — API Health Check
 * GET /api/health.php
 *
 * Returns JSON with status of all critical services.
 * Authenticated users get full details; public gets only overall status.
 * Cached for 30 seconds to keep response fast (< 500 ms).
 */

define('GOSITEME_API', true);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=30');
header('X-Content-Type-Options: nosniff');

// ── Auth check — determine detail level ──────────────────────
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$isAdmin = (int)($_SESSION['client_id'] ?? 0) === 33;
$isInternal = !empty($_SERVER['HTTP_X_INTERNAL_SECRET']) && hash_equals(
    getenv('INTERNAL_SECRET') ?: '', $_SERVER['HTTP_X_INTERNAL_SECRET']
);

// ── Cache layer (file-based, 30 s TTL) ──────────────────────
$cacheDir  = __DIR__ . '/../cache';
$cacheFile = $cacheDir . '/health-check.json';
$cacheTTL  = 30; // seconds

if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTTL) {
    readfile($cacheFile);
    exit;
}

// ── Service checks ──────────────────────────────────────────

/**
 * Check database connectivity with a simple SELECT 1 query.
 */
function checkDatabase(): array {
    $start = microtime(true);
    try {
        require_once __DIR__ . '/config.php';
        $pdo = getDB();
        if ($pdo === null) {
            return ['status' => 'down', 'latency_ms' => 0, 'error' => 'connection_failed'];
        }
        $pdo->query('SELECT 1');
        $ms = round((microtime(true) - $start) * 1000, 1);
        return ['status' => 'up', 'latency_ms' => $ms];
    } catch (\Throwable $e) {
        $ms = round((microtime(true) - $start) * 1000, 1);
        return ['status' => 'down', 'latency_ms' => $ms, 'error' => 'query_failed'];
    }
}

/**
 * Ping Redis on localhost:6379.
 */
function checkRedis(): array {
    $start = microtime(true);
    try {
        $fp = @fsockopen('127.0.0.1', 6379, $errno, $errstr, 2);
        if (!$fp) {
            return ['status' => 'down', 'latency_ms' => 0];
        }
        fwrite($fp, "PING\r\n");
        $response = fgets($fp, 64);
        fclose($fp);
        $ms = round((microtime(true) - $start) * 1000, 1);
        if (strpos($response, '+PONG') !== false) {
            return ['status' => 'up', 'latency_ms' => $ms];
        }
        return ['status' => 'degraded', 'latency_ms' => $ms];
    } catch (\Throwable $e) {
        return ['status' => 'down', 'latency_ms' => 0];
    }
}

/**
 * Check an HTTP service by URL (WebSocket server health, MCP server, etc.).
 */
function checkHttpService(string $url, int $port, int $timeout = 2): array {
    $start = microtime(true);
    try {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body     = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err      = curl_error($ch);
        curl_close($ch);

        $ms = round((microtime(true) - $start) * 1000, 1);

        if ($err) {
            // Fallback: raw socket check on the port
            $fp = @fsockopen('127.0.0.1', $port, $errno, $errstr, $timeout);
            if ($fp) {
                fclose($fp);
                $ms = round((microtime(true) - $start) * 1000, 1);
                return ['status' => 'up', 'latency_ms' => $ms, 'port' => $port];
            }
            return ['status' => 'down', 'latency_ms' => $ms, 'port' => $port];
        }

        if ($httpCode >= 200 && $httpCode < 500) {
            return ['status' => 'up', 'latency_ms' => $ms, 'port' => $port];
        }
        return ['status' => 'degraded', 'latency_ms' => $ms, 'port' => $port];
    } catch (\Throwable $e) {
        return ['status' => 'down', 'latency_ms' => 0, 'port' => $port];
    }
}

/**
 * Check if Post-Quantum crypto columns exist in the comms tables.
 */
function checkPQCrypto(): array {
    try {
        require_once __DIR__ . '/config.php';
        $pdo = getDB();
        if ($pdo === null) {
            return ['status' => 'unknown', 'algorithm' => 'Kyber-768-Hybrid'];
        }
        $stmt = $pdo->query("SHOW COLUMNS FROM comms_identity_keys LIKE 'pq_public'");
        $hasPQ = $stmt && $stmt->rowCount() > 0;
        return [
            'status'    => $hasPQ ? 'active' : 'not_provisioned',
            'algorithm' => 'Kyber-768-Hybrid',
            'scheme'    => 'ECDH-P256 + Kyber-768 → HKDF-SHA256 → AES-256-GCM',
        ];
    } catch (\Throwable $e) {
        return ['status' => 'unknown', 'algorithm' => 'Kyber-768-Hybrid'];
    }
}

// ── Run checks ──────────────────────────────────────────────
$services = [
    'database'    => checkDatabase(),
    'redis'       => checkRedis(),
    'websocket'   => checkHttpService('http://127.0.0.1:3010/health', 3010),
    'mcp_server'  => checkHttpService('http://127.0.0.1:3005/', 3005),
    'job_queue'   => checkHttpService('http://127.0.0.1:3011/health', 3011),
    'meilisearch' => checkHttpService('http://127.0.0.1:7700/health', 7700),
    'ollama'      => checkHttpService('http://127.0.0.1:11434/api/version', 11434),
    'tts_server'  => checkHttpService('http://127.0.0.1:5002/', 5002, 3),
    'icecast'     => checkHttpService('http://127.0.0.1:8000/', 8000),
    'livekit'     => checkHttpService('http://127.0.0.1:7880/', 7880),
    'pq_crypto'   => checkPQCrypto(),
];

// Overall status
$allUp    = true;
$anyDown  = false;
foreach ($services as $name => $svc) {
    if ($name === 'pq_crypto') continue; // informational, not uptime
    if ($svc['status'] !== 'up') $allUp = false;
    if ($svc['status'] === 'down') $anyDown = true;
}
$overallStatus = $allUp ? 'healthy' : ($anyDown ? 'degraded' : 'partial');

// Uptime (Linux)
$uptimeSeconds = 0;
if (is_readable('/proc/uptime')) {
    $raw = file_get_contents('/proc/uptime');
    $uptimeSeconds = (int) floatval($raw);
}

// ── Public response: only overall status (no service details) ──
if (!$isAdmin && !$isInternal) {
    echo json_encode([
        'status'    => $overallStatus,
        'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
        'version'   => '3.2.0',
    ], JSON_PRETTY_PRINT);
    exit;
}

// ── Full response: admin/internal only ──
$payload = [
    'status'         => $overallStatus,
    'timestamp'      => gmdate('Y-m-d\TH:i:s\Z'),
    'services'       => $services,
    'version'        => '3.2.0',
    'uptime_seconds' => $uptimeSeconds,
];

$json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

// Write cache
if (!is_dir($cacheDir)) @mkdir($cacheDir, 0750, true);
@file_put_contents($cacheFile, $json, LOCK_EX);

echo $json;
