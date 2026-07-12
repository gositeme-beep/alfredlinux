<?php
/**
 * Alfred IDE Gate — validates IDE session token, then reverse-proxies to code-server.
 * Called by .htaccess on every /alfred-ide/ HTTP request (not WebSockets).
 */

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

function ideGateClearCookies(): void {
    setcookie('alfred_ide_token', '', ['expires' => time() - 86400, 'path' => '/alfred-ide/', 'secure' => true, 'httponly' => true, 'samesite' => 'Lax']);
    setcookie('alfred_ide_token', '', ['expires' => time() - 86400, 'path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'Lax']);
}

function ideGateRedirectToMainLogin(): void {
    ideGateClearCookies();
    // Use IDE auth flow so billing-session users are not sent to /login → /account without a token
    header('Location: /alfred-ide-auth.php');
    exit;
}

function ideGateBridgePaths(): array {
    return [
        '/home/gositeme/domains/gositeme.com/logs/alfred-ide/session.json',
        '/home/gositeme/.alfred-ide/session.json',
    ];
}

function ideGateWriteSessionBridge(string $token, array $user): void {
    if ($token === '') {
        return;
    }

    $payload = [
        'token' => $token,
        'issued_at' => time(),
        'expires_at' => !empty($user['token_expires']) ? strtotime((string)$user['token_expires']) : (time() + 86400),
        'ide_user_id' => (int)($user['id'] ?? 0),
        'name' => (string)($user['display_name'] ?? $user['google_name'] ?? $user['email'] ?? $user['google_email'] ?? ''),
        'email' => (string)($user['email'] ?? $user['google_email'] ?? ''),
        'client_id' => !empty($user['client_id']) ? (int)$user['client_id'] : null,
    ];
    $json = json_encode($payload, JSON_UNESCAPED_SLASHES);

    foreach (ideGateBridgePaths() as $path) {
        $dir = dirname($path);
        if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
            continue;
        }
        if (@file_put_contents($path, $json, LOCK_EX) !== false) {
            @chmod($path, 0600);
            return;
        }
    }
}

function ideGateHasPaidAccess(PDO $db, int $clientId): bool {
    if ($clientId <= 0) return false;

    $svc = $db->prepare("SELECT COUNT(*) FROM services WHERE client_id = ? AND status = 'Active'");
    $svc->execute([$clientId]);
    if ((int)$svc->fetchColumn() > 0) {
        return true;
    }

    $dom = $db->prepare("SELECT COUNT(*) FROM domains WHERE client_id = ? AND status = 'Active'");
    $dom->execute([$clientId]);
    return (int)$dom->fetchColumn() > 0;
}

$token = $_COOKIE['alfred_ide_token'] ?? '';

if (!$token) {
    ideGateRedirectToMainLogin();
    exit;
}

// Quick session cache — avoid DB hit on every sub-request (30s TTL)
// Store hash of token (not raw) to protect session storage
$tokenHash = hash('sha256', $token);
$cacheValid = !empty($_SESSION['ide_gate_hash'])
    && hash_equals($_SESSION['ide_gate_hash'], $tokenHash)
    && !empty($_SESSION['ide_gate_expires'])
    && $_SESSION['ide_gate_expires'] > time();

if (!$cacheValid) {
    require_once __DIR__ . '/includes/db-config.inc.php';
    require_once __DIR__ . '/includes/alfred-ide-bearer.inc.php';

    $db = getSharedDB();
    $user = alfred_ide_lookup_user_by_token_hash($db, $tokenHash);

    if (!$user) {
        ideGateRedirectToMainLogin();
    }

    if (!ideGateHasPaidAccess($db, (int)($user['client_id'] ?? 0))) {
        ideGateRedirectToMainLogin();
    }

    $_SESSION['ide_gate_hash'] = $tokenHash;
    $_SESSION['ide_gate_expires'] = time() + 30;

    ideGateWriteSessionBridge($token, $user);
}

// ── Validated — reverse proxy to code-server ────────────────────────────────

$path = $_GET['__path'] ?? '';
$path = ltrim($path, '/');
$qs = $_SERVER['QUERY_STRING'] ?? '';
// Remove our internal __path param from the query string forwarded to code-server
$qs = preg_replace('/(?:^|&)__path=[^&]*/', '', $qs);
$qs = ltrim($qs, '&');

// Alfred web UI (alfred-ide-v1.js) loads relay.js from /alfred-ide/static/js/relay.js — code-server has no such file → 404.
// Serve these assets from disk instead of proxying to code-server.
$ideStaticRoot = __DIR__ . '/alfred-ide-assets/';
$ideStaticMap = [
    'static/js/relay.js' => $ideStaticRoot . 'static/js/relay.js',
];
if (isset($ideStaticMap[$path]) && is_readable($ideStaticMap[$path])) {
    header('Content-Type: application/javascript; charset=utf-8');
    header('Cache-Control: public, max-age=7200');
    readfile($ideStaticMap[$path]);
    exit;
}

$url = 'http://127.0.0.1:8443/' . $path;
if ($qs) $url .= '?' . $qs;

$method = $_SERVER['REQUEST_METHOD'];

// Allowlist headers — don't forward cookies/auth to code-server
$allowedHeaders = ['ACCEPT', 'ACCEPT-LANGUAGE', 'ACCEPT-ENCODING',
                   'CONTENT-TYPE', 'CONTENT-LENGTH', 'CACHE-CONTROL',
                   'IF-NONE-MATCH', 'IF-MODIFIED-SINCE', 'RANGE',
                   'PRAGMA', 'DNT', 'ORIGIN', 'REFERER', 'USER-AGENT',
                   'UPGRADE-INSECURE-REQUESTS', 'SEC-FETCH-DEST',
                   'SEC-FETCH-MODE', 'SEC-FETCH-SITE', 'SEC-FETCH-USER'];
$headers = [];
foreach ($_SERVER as $k => $v) {
    if (strpos($k, 'HTTP_') === 0 && $k !== 'HTTP_HOST') {
        $name = str_replace('_', '-', substr($k, 5));
        $upperName = strtoupper($name);
        if (in_array($upperName, $allowedHeaders, true) || str_starts_with($upperName, 'SEC-CH-UA')) {
            $headers[] = "$name: $v";
        }
    }
}
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'gositeme.com';
$headers[] = 'Host: ' . $host;
$headers[] = 'X-Forwarded-Host: ' . $host;
$headers[] = 'X-Forwarded-Proto: ' . $scheme;
$headers[] = 'X-Forwarded-For: ' . ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
$headers[] = 'X-Forwarded-Prefix: /alfred-ide';
$headers[] = 'X-Forwarded-Uri: ' . ($_SERVER['REQUEST_URI'] ?? '/alfred-ide/');
if (!empty($_SERVER['CONTENT_TYPE'])) {
    $headers[] = 'Content-Type: ' . $_SERVER['CONTENT_TYPE'];
}

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_CUSTOMREQUEST  => $method,
    CURLOPT_HTTPHEADER     => $headers,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER         => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_MAXREDIRS      => 0,
]);

// Limit request body to 50MB
if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
    $body = file_get_contents('php://input', false, null, 0, 52428800);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
}

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

if ($response === false) {
    http_response_code(502);
    echo 'Alfred IDE is starting up...';
    exit;
}

curl_close($ch);

$responseHeaders = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

http_response_code($httpCode);

// Forward response headers — skip dangerous and hop-by-hop headers
$skipHeaders = ['transfer-encoding', 'connection', 'keep-alive', 'set-cookie',
                'x-powered-by', 'server', 'content-security-policy'];
foreach (explode("\r\n", $responseHeaders) as $line) {
    if (empty($line) || strpos($line, 'HTTP/') === 0) continue;
    $lowerLine = strtolower($line);
    $skip = false;
    foreach ($skipHeaders as $sh) {
        if (strpos($lowerLine, $sh . ':') === 0) { $skip = true; break; }
    }
    if (!$skip) header($line, false);
}

echo $body;
