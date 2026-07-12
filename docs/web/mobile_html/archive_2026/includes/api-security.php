<?php
/**
 * GoSiteMe Shared API Security Middleware
 * ────────────────────────────────────────
 * CSRF validation, rate limiting, security headers, CORS for all API endpoints.
 * Include via: require_once dirname(__DIR__) . '/includes/api-security.php';
 *
 * @since v13.6  — CSRF + rate limiting
 * @since v14.0  — Auto-bootstrap: security headers, CORS, global rate limit
 */

// Also load input validator and response helpers for convenient access
require_once __DIR__ . '/input-validator.inc.php';
require_once __DIR__ . '/api-response.inc.php';

// dbExecute: PDO execute() with proper PARAM_INT for LIMIT/OFFSET
if (!function_exists('dbExecute')) {
    function dbExecute(PDOStatement $stmt, array $params): PDOStatement {
        foreach ($params as $i => $value) {
            $type = is_int($value) ? PDO::PARAM_INT : (is_null($value) ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue($i + 1, $value, $type);
        }
        $stmt->execute();
        return $stmt;
    }
}

/**
 * Validate CSRF token on state-changing requests (POST, PUT, PATCH, DELETE).
 * Call at the top of any handler that modifies data.
 *
 * Skips validation for:
 *  - Internal service calls (valid X-Internal-Secret header)
 *  - API key authenticated requests (Authorization: Bearer ...)
 *  - GET/OPTIONS/HEAD requests
 *
 * @param bool $dieOnFail If true, sends 403 and exits. If false, returns bool.
 * @return bool True if valid or skipped, false if failed (only when $dieOnFail=false)
 */
function requireCSRF(bool $dieOnFail = true): bool {
    // Skip for safe methods
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (in_array($method, ['GET', 'OPTIONS', 'HEAD'], true)) {
        return true;
    }

    // Skip for internal service-to-service calls
    $internalSecret = getenv('INTERNAL_SECRET') ?: '';
    if ($internalSecret && isset($_SERVER['HTTP_X_INTERNAL_SECRET'])
        && hash_equals($internalSecret, $_SERVER['HTTP_X_INTERNAL_SECRET'])) {
        return true;
    }

    // Skip for API-key authenticated requests (SDK/external integrations)
    if (!empty($_SERVER['HTTP_AUTHORIZATION']) && str_starts_with($_SERVER['HTTP_AUTHORIZATION'], 'Bearer ')) {
        return true;
    }

    // Require session CSRF token
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $sessionToken = $_SESSION['csrf_token'] ?? '';
    if (!$sessionToken) {
        // Generate one for future requests
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    // Check token from header or body
    $submittedToken = $_SERVER['HTTP_X_CSRF_TOKEN']
        ?? $_POST['csrf_token']
        ?? '';

    if (!$submittedToken) {
        // Try JSON body
        $body = json_decode(file_get_contents('php://input'), true);
        $submittedToken = $body['csrf_token'] ?? '';
    }

    if (!$sessionToken || !$submittedToken || !hash_equals($sessionToken, $submittedToken)) {
        if ($dieOnFail) {
            // Debug aid: log CSRF failures without recording secrets.
            // (No cookies/tokens are written; only request metadata.)
            $csrfLog = [
                'ts'     => date('c'),
                'ip'     => $_SERVER['REMOTE_ADDR'] ?? '',
                'host'   => $_SERVER['HTTP_HOST'] ?? '',
                'method' => $method,
                'uri'    => $_SERVER['REQUEST_URI'] ?? '',
                'origin' => $_SERVER['HTTP_ORIGIN'] ?? '',
                'ref'    => $_SERVER['HTTP_REFERER'] ?? '',
                'ua'     => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'has_auth' => !empty($_SERVER['HTTP_AUTHORIZATION']),
                'has_internal' => !empty($_SERVER['HTTP_X_INTERNAL_SECRET']),
                'content_type' => $_SERVER['CONTENT_TYPE'] ?? '',
            ];
            @file_put_contents('/tmp/csrf-fail.log', json_encode($csrfLog, JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
            http_response_code(403);
            echo json_encode([
                'error' => 'CSRF validation failed'
            ]);
            exit;
        }
        return false;
    }

    return true;
}

/**
 * File-based rate limiter. Redis-first with file fallback.
 *
 * @param int $maxRequests Maximum requests allowed in the window
 * @param int $windowSecs Time window in seconds
 * @param string $scope Optional scope prefix (e.g. 'treasury', 'fleet') for independent limits
 * @param bool $dieOnFail If true, sends 429 and exits. If false, returns bool.
 * @return bool True if within limit, false if exceeded (only when $dieOnFail=false)
 */
function apiRateLimit(int $maxRequests = 30, int $windowSecs = 60, string $scope = 'api', bool $dieOnFail = true): bool {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $userId = $_SESSION['client_id'] ?? 0;
    $identity = $userId ? "u{$userId}" : "ip_" . md5($ip);
    
    if (md5($ip) === 'e07bf9579c5282fb03fed44e66d6ad6d') {
        @file_put_contents('/tmp/spam.log', date('H:i:s') . ' ' . ($_SERVER['REQUEST_URI'] ?? 'unknown') . "\n", FILE_APPEND);
    }

    $key = "rl_{$scope}_{$identity}";

    $count = 0;
    $remaining = $maxRequests;
    $resetAt = time() + $windowSecs;

    // Try Redis first
    try {
        $redis = new \Redis();
        if (@$redis->connect('127.0.0.1', 6379, 1)) {
            $count = (int) $redis->incr($key);
            if ($count === 1) $redis->expire($key, $windowSecs);
            $ttl = $redis->ttl($key);
            if ($ttl === -1) {
                // Fix race condition where expire wasn't set
                $redis->expire($key, $windowSecs);
                $ttl = $windowSecs;
            }
            $remaining = max(0, $maxRequests - $count);
            $resetAt = time() + ($ttl > 0 ? $ttl : $windowSecs);

            header("X-RateLimit-Limit: $maxRequests");
            header("X-RateLimit-Remaining: $remaining");
            header("X-RateLimit-Reset: $resetAt");

            if ($count > $maxRequests) {
                if ($dieOnFail) {
                    $retryAfter = $ttl > 0 ? $ttl : $windowSecs;
                    http_response_code(429);
                    header('Retry-After: ' . $retryAfter);
                    // Redirect browsers to help page; return JSON for API clients
                    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
                    if (strpos($accept, 'text/html') !== false) {
                        header('Location: /rate-limit-help.html');
                        exit;
                    }
                    echo json_encode(['error' => 'Rate limit exceeded. Please slow down.', 'help' => '/rate-limit-help.html']);
                    exit;
                }
                return false;
            }
            return true;
        }
    } catch (\Throwable $e) { /* fall through to file-based */ }

    // Fallback: file-based rate limiting (atomic read-modify-write)
    $cacheDir = dirname(__DIR__) . '/cache/rate_limits/';
    if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);
    $file = $cacheDir . md5($key) . '.json';

    $data = ['count' => 0, 'start' => time()];
    $fp = fopen($file, 'c+');
    if ($fp && flock($fp, LOCK_EX)) {
        $raw = stream_get_contents($fp);
        if ($raw) $data = json_decode($raw, true) ?: $data;
        if (time() - $data['start'] > $windowSecs) {
            $data = ['count' => 0, 'start' => time()];
        }
        $data['count']++;
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($data));
        flock($fp, LOCK_UN);
    }
    if ($fp) fclose($fp);

    $remaining = max(0, $maxRequests - $data['count']);
    $resetAt = $data['start'] + $windowSecs;

    header("X-RateLimit-Limit: $maxRequests");
    header("X-RateLimit-Remaining: $remaining");
    header("X-RateLimit-Reset: $resetAt");

    if ($data['count'] > $maxRequests) {
        if ($dieOnFail) {
            $retryAfter = max(1, $resetAt - time());
            http_response_code(429);
            header('Retry-After: ' . $retryAfter);
            // Redirect browsers to help page; return JSON for API clients
            $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
            if (strpos($accept, 'text/html') !== false) {
                header('Location: /rate-limit-help.html');
                exit;
            }
            echo json_encode(['error' => 'Rate limit exceeded. Please slow down.', 'help' => '/rate-limit-help.html']);
            exit;
        }
        return false;
    }
    return true;
}

// ═══════════════════════════════════════════════════════════
// AUTO-BOOTSTRAP: Apply security defaults on every API include
// ═══════════════════════════════════════════════════════════

(function () {
    // Skip for CLI (cron scripts, tests)
    if (php_sapi_name() === 'cli') return;

    // ── Protocol-level attack detection ──────────────────────
    // Block HTTP request smuggling (Transfer-Encoding + Content-Length conflict)
    if (isset($_SERVER['HTTP_TRANSFER_ENCODING']) && isset($_SERVER['CONTENT_LENGTH'])) {
        $te = strtolower($_SERVER['HTTP_TRANSFER_ENCODING']);
        if ($te === 'chunked') {
            http_response_code(400);
            exit('Bad Request');
        }
    }

    // Block null bytes in request URI (injection vector)
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($uri, "\0") !== false || strpos($uri, '%00') !== false) {
        http_response_code(400);
        exit('Bad Request');
    }

    // Block CRLF injection in headers (HTTP header injection)
    foreach (['HTTP_HOST', 'HTTP_REFERER', 'HTTP_USER_AGENT'] as $h) {
        if (isset($_SERVER[$h]) && preg_match('/[\r\n]/', $_SERVER[$h])) {
            http_response_code(400);
            exit('Bad Request');
        }
    }

    // Validate Host header (DNS rebinding protection)
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $allowedHosts = [
        'gositeme.com', 'www.gositeme.com',
        'meta-dome.com', 'www.meta-dome.com',
        '15.235.50.60', 'localhost', '127.0.0.1',
    ];
    if ($host && !in_array($host, $allowedHosts, true)) {
        http_response_code(400);
        exit('Bad Request');
    }

    // Security headers for API responses
    if (!headers_sent()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'");        // CORS — restrict to own domain
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $allowed = ['https://gositeme.com', 'https://www.gositeme.com', 'https://meta-dome.com', 'https://www.meta-dome.com'];
        if (in_array($origin, $allowed, true)) {
            header("Access-Control-Allow-Origin: $origin");
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, Authorization');
            header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        }
        // Preflight
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }

    // Auto rate limiting (60 req/min default for all APIs)
    // Endpoints with their own rate limiting can opt out by setting
    // $GLOBALS['RATE_LIMIT_EXEMPT'] = true; BEFORE including this file.
    if (!isset($GLOBALS['RATE_LIMIT_EXEMPT'])) {
        apiRateLimit(200, 60, 'api_global', true);
    }

    // Auto CSRF enforcement on state-changing requests.
    // Endpoints receiving external webhooks/callbacks can opt out by setting
    // $GLOBALS['CSRF_EXEMPT'] = true; BEFORE including this file.
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!in_array($method, ['GET', 'OPTIONS', 'HEAD'], true)
        && empty($GLOBALS['CSRF_EXEMPT'])) {
        requireCSRF();
    }

    // ── Credential Auto-Rotation Hook ───────────────────────────
    // Lightweight: checks a single file timestamp (~0.1ms overhead).
    // Only triggers actual rotation if 11+ minutes have elapsed.
    $rotationScript = '/home/gositeme/.vault/rotate-credentials.php';
    if (PHP_SAPI === "cli" && file_exists($rotationScript)) {
        $stateFile = '/home/gositeme/.vault/rotation-state.json';
        $lastCheck = file_exists($stateFile) ? filemtime($stateFile) : 0;
        // Only bother loading the rotation engine once per 5 minutes
        if (time() - $lastCheck > 300) {
            require_once $rotationScript;
            if (isRotationDue()) {
                triggerRotationCheck();
            }
        }
    }

    // ── File Integrity Check Hook ───────────────────────────────
    // Runs every 5 minutes alongside rotation. Logs violations.
    $integrityScript = '/home/gositeme/.vault/integrity-check.php';
    $integrityState  = '/home/gositeme/.vault/integrity-baseline.json';
    if (PHP_SAPI === "cli" && file_exists($integrityScript) && file_exists($integrityState)) {
        $lastIntegrity = file_exists($integrityState) ? filemtime($integrityState) : 0;
        if (time() - $lastIntegrity > 300) {
            // Touch state to prevent re-runs within 5 min window
            touch($integrityState);
            exec('/usr/bin/php ' . escapeshellarg($integrityScript) . ' 2>&1', $intOut, $intRc);
            if ($intRc === 2) {
                error_log('[SECURITY] Integrity violation detected: ' . implode(' ', $intOut));
            }
        }
    }
})();
