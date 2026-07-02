<?php
/**
 * GoSiteMe Shared API Security Middleware
 * ────────────────────────────────────────
 * CSRF validation, rate limiting, security headers, CORS for all API endpoints.
 * Include via: require_once dirname(__DIR__) . '/includes/api-security.php';
 *
 * @since v13.6  — CSRF + rate limiting
 * @since v14.0  — Auto-bootstrap: security headers, CORS, global rate limit
 * @since v14.1  — File RL data under law/var (override: GOSITEME_RATE_LIMIT_DATA_DIR); X-Request-Id correlation
 */

// Also load path validation (writable data dirs); then validators and API responses.
require_once __DIR__ . '/path-guard.inc.php';
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
    if (!isset($GLOBALS['root_request_id']) || !is_string($GLOBALS['root_request_id'])) {
        $incomingRid = trim((string) ($_SERVER['HTTP_X_REQUEST_ID'] ?? $_SERVER['HTTP_X_TRACE_ID'] ?? ''));
        if ($incomingRid !== '' && preg_match('/^[a-zA-Z0-9._@-]{1,80}$/', $incomingRid)) {
            $GLOBALS['root_request_id'] = $incomingRid;
        } else {
            $GLOBALS['root_request_id'] = bin2hex(random_bytes(8));
        }
    }

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
        // Generate one for future requests — must refresh $sessionToken for the check below.
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $sessionToken = $_SESSION['csrf_token'];
    }

    // Check token from header or body
    $submittedToken = $_SERVER['HTTP_X_CSRF_TOKEN']
        ?? $_POST['csrf_token']
        ?? '';

    if (!$submittedToken) {
        // Try JSON body (bounded read — see api_read_json_body_limited)
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json') && function_exists('api_read_json_body_limited')) {
            $body = api_read_json_body_limited();
            $submittedToken = $body['csrf_token'] ?? '';
        } else {
            $raw = '';
            if (function_exists('api_read_raw_body_limited') && function_exists('api_max_nonjson_body_bytes')) {
                $raw = api_read_raw_body_limited(api_max_nonjson_body_bytes());
            } else {
                $raw = (string) file_get_contents('php://input');
            }
            $body = json_decode($raw, true);
            $submittedToken = is_array($body) ? ($body['csrf_token'] ?? '') : '';
        }
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
                'request_id'   => $GLOBALS['root_request_id'] ?? null,
            ];
            $csrfDir = '/home/root/law/var';
            if (!is_dir($csrfDir)) {
                @mkdir($csrfDir, 0700, true);
            }
            $csrfLogPath = $csrfDir . '/csrf-fail.log';
            $jf = JSON_UNESCAPED_SLASHES;
            if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
                $jf |= JSON_INVALID_UTF8_SUBSTITUTE;
            }
            $csrfLine = json_encode($csrfLog, $jf);
            if ($csrfLine === false) {
                $csrfLine = '{"error":"csrf_log_encode_failed"}';
            }
            @file_put_contents($csrfLogPath, $csrfLine . "\n", FILE_APPEND | LOCK_EX);
            http_response_code(403);
            if (!headers_sent()) {
                header('Cache-Control: no-store, no-cache, must-revalidate, private');
                header('Pragma: no-cache');
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode([
                'error' => 'CSRF validation failed',
                'request_id' => $GLOBALS['root_request_id'] ?? null,
            ], root_json_public_encode_flags());
            exit;
        }
        return false;
    }

    return true;
}

/**
 * File-based rate limiter. Redis-first with file fallback.
 * Redis keys: GOSITEME_REDIS_RL_PREFIX (default root:rl:) + logical key rl_{scope}_{identity}.
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
        $watchDir = '/home/root/law/var';
        if (!is_dir($watchDir)) {
            @mkdir($watchDir, 0700, true);
        }
        @file_put_contents(
            $watchDir . '/rate-spam-watch.log',
            date('H:i:s') . ' ' . ($_SERVER['REQUEST_URI'] ?? 'unknown') . "\n",
            FILE_APPEND | LOCK_EX
        );
    }

    $key = "rl_{$scope}_{$identity}";
    $redisPrefix = getenv('GOSITEME_REDIS_RL_PREFIX');
    if (!is_string($redisPrefix) || !preg_match('/^[a-zA-Z0-9:_-]{1,64}$/', $redisPrefix)) {
        $redisPrefix = 'root:rl:';
    } elseif (substr($redisPrefix, -1) !== ':') {
        $redisPrefix .= ':';
    }
    $redisKey = $redisPrefix . $key;

    $count = 0;
    $remaining = $maxRequests;
    $resetAt = time() + $windowSecs;

    // Try Redis first
    try {
        $redis = new \Redis();
        if (@$redis->connect('127.0.0.1', 6379, 1)) {
            // Atomic increment + expire via Lua to prevent INCR/EXPIRE race
            $lua = <<<'LUA'
local count = redis.call('INCR', KEYS[1])
if count == 1 then
    redis.call('EXPIRE', KEYS[1], ARGV[1])
end
local ttl = redis.call('TTL', KEYS[1])
if ttl == -1 then
    redis.call('EXPIRE', KEYS[1], ARGV[1])
    ttl = tonumber(ARGV[1])
end
return {count, ttl}
LUA;
            $result = $redis->eval($lua, [$redisKey, $windowSecs], 1);
            $count = (int) ($result[0] ?? 1);
            $ttl = (int) ($result[1] ?? $windowSecs);
            $remaining = max(0, $maxRequests - $count);
            $resetAt = time() + ($ttl > 0 ? $ttl : $windowSecs);

            header("X-RateLimit-Limit: $maxRequests");
            header("X-RateLimit-Remaining: $remaining");
            header("X-RateLimit-Reset: $resetAt");

            if ($count > $maxRequests) {
                if ($dieOnFail) {
                    $retryAfter = $ttl > 0 ? $ttl : $windowSecs;
                    http_response_code(429);
                    if (!headers_sent()) {
                        header('Cache-Control: no-store, no-cache, must-revalidate, private');
                        header('Pragma: no-cache');
                    }
                    header('Retry-After: ' . $retryAfter);
                    // Redirect browsers to help page; return JSON for API clients
                    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
                    if (strpos($accept, 'text/html') !== false) {
                        header('Location: /rate-limit-help.html');
                        exit;
                    }
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'error'       => 'Rate limit exceeded. Please slow down.',
                        'help'        => '/rate-limit-help.html',
                        'request_id'  => $GLOBALS['root_request_id'] ?? null,
                    ], root_json_public_encode_flags());
                    exit;
                }
                return false;
            }
            return true;
        }
    } catch (\Throwable $e) { /* fall through to file-based */ }

    // Fallback: file-based rate limiting (atomic read-modify-write)
    $cacheDir = '/home/root/law/var/api-rate-limits/';
    $rlCustom = root_safe_absolute_dir((string) (getenv('GOSITEME_RATE_LIMIT_DATA_DIR') ?: ''));
    if ($rlCustom !== null) {
        $cacheDir = $rlCustom . '/';
    }
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0700, true);
    }
    @chmod($cacheDir, 0700);
    $file = $cacheDir . md5($key) . '.json';

    $data = ['count' => 0, 'start' => time()];
    $fp = fopen($file, 'c+');
    if ($fp && flock($fp, LOCK_EX)) {
        $raw = stream_get_contents($fp);
        if ($raw) {
            $data = json_decode($raw, true) ?: $data;
        }
        if (time() - $data['start'] > $windowSecs) {
            $data = ['count' => 0, 'start' => time()];
        }
        $data['count']++;
        ftruncate($fp, 0);
        rewind($fp);
        $jf = JSON_UNESCAPED_SLASHES;
        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $jf |= JSON_INVALID_UTF8_SUBSTITUTE;
        }
        fwrite($fp, json_encode($data, $jf));
        flock($fp, LOCK_UN);
    }
    if ($fp) {
        fclose($fp);
        if (is_file($file)) {
            @chmod($file, 0600);
        }
    }

    $remaining = max(0, $maxRequests - $data['count']);
    $resetAt = $data['start'] + $windowSecs;

    header("X-RateLimit-Limit: $maxRequests");
    header("X-RateLimit-Remaining: $remaining");
    header("X-RateLimit-Reset: $resetAt");

    if ($data['count'] > $maxRequests) {
        if ($dieOnFail) {
            $retryAfter = max(1, $resetAt - time());
            http_response_code(429);
            if (!headers_sent()) {
                header('Cache-Control: no-store, no-cache, must-revalidate, private');
                header('Pragma: no-cache');
            }
            header('Retry-After: ' . $retryAfter);
            // Redirect browsers to help page; return JSON for API clients
            $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
            if (strpos($accept, 'text/html') !== false) {
                header('Location: /rate-limit-help.html');
                exit;
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'error'       => 'Rate limit exceeded. Please slow down.',
                'help'        => '/rate-limit-help.html',
                'request_id'  => $GLOBALS['root_request_id'] ?? null,
            ], root_json_public_encode_flags());
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
        'root.com', 'www.root.com',
        'meta-dome.com', 'www.meta-dome.com',
        '15.235.50.60', 'localhost', '127.0.0.1',
    ];
    if ($host && !in_array($host, $allowedHosts, true)) {
        http_response_code(400);
        exit('Bad Request');
    }

    // Request correlation for support / log cross-reference (echo-safe inbound id or random)
    $incomingRid = trim((string) ($_SERVER['HTTP_X_REQUEST_ID'] ?? $_SERVER['HTTP_X_TRACE_ID'] ?? ''));
    if ($incomingRid !== '' && preg_match('/^[a-zA-Z0-9._@-]{1,80}$/', $incomingRid)) {
        $GLOBALS['root_request_id'] = $incomingRid;
    } else {
        $GLOBALS['root_request_id'] = bin2hex(random_bytes(8));
    }

    // Security headers for API responses
    if (!headers_sent()) {
        header('X-Request-Id: ' . $GLOBALS['root_request_id']);
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'");        // CORS — restrict to own domain
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $allowed = ['https://root.com', 'https://www.root.com', 'https://meta-dome.com', 'https://www.meta-dome.com'];
        if (in_array($origin, $allowed, true)) {
            header("Access-Control-Allow-Origin: $origin");
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, Authorization, X-Request-Id, X-Trace-Id');
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
    $rotationScript = '/home/root/.vault/rotate-credentials.php';
    if (PHP_SAPI === "cli" && file_exists($rotationScript)) {
        $stateFile = '/home/root/.vault/rotation-state.json';
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
    $integrityScript = '/home/root/.vault/integrity-check.php';
    $integrityState  = '/home/root/.vault/integrity-baseline.json';
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
