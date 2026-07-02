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

function ideGateClearSessionBridge(): void {
    foreach (ideGateBridgePaths() as $path) {
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

function ideGateClearLocalState(): void {
    unset($_SESSION['ide_user_id'], $_SESSION['ide_google_email'], $_SESSION['ide_google_name'],
          $_SESSION['ide_google_avatar'], $_SESSION['ide_session_token'], $_SESSION['ide_authenticated'],
          $_SESSION['ide_gate_token'], $_SESSION['ide_gate_expires'], $_SESSION['ide_gate_hash']);
}

function ideGateRedirectToMainLogin(): void {
    ideGateClearLocalState();
    ideGateClearCookies();
    ideGateClearSessionBridge();
    // Use IDE auth flow so billing-session users are not sent to /login → /account without a token
    header('Location: /alfred-ide-auth.php');
    exit;
}

function ideGateBridgePaths(): array {
    // Primary path: group-writable (root:access) so Apache/PHP-FPM can write; code-server (root) can read.
    // Older paths under logs/ and ~/.alfred-ide were 0700 root-only — bridge writes failed silently → "No session token" in IDE stats.
    return [
        '/home/root/domains/root.com/.alfred-ide-bridge/session.json',
        '/home/root/domains/root.com/logs/alfred-ide/session.json',
        '/home/root/.alfred-ide/session.json',
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
            // 0660: code-server (root) must read token; PHP may run as root or apache — group access when parent is setgid "access"
            @chmod($path, 0660);
            return;
        }
    }
}

/**
 * Browser-visible origin for Alfred IDE (no trailing slash). Used to rewrite upstream redirects.
 */
function ideGatePublicIdeBase(): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'root.com';
    return $scheme . '://' . $host . '/alfred-ide';
}

function ideGateHostIsLocalCodeServer(string $host): bool {
    $h = strtolower(trim($host, "[]\t "));
    return $h === '127.0.0.1' || $h === 'localhost' || $h === '::1';
}

/**
 * Rewrite a single redirect target URL from upstream code-server for the browser.
 */
function ideGateRewriteRedirectUrl(string $loc, string $publicBase): string {
    if ($loc === '' || str_starts_with($loc, '//')) {
        return $loc;
    }
    $p = parse_url($loc);
    $scheme = strtolower($p['scheme'] ?? '');
    $host = (string)($p['host'] ?? '');
    $port = (int)($p['port'] ?? 0);
    if ($scheme !== '' && $host !== '' && ideGateHostIsLocalCodeServer($host) && $port === 8443) {
        $path = $p['path'] ?? '/';
        if ($path === '') {
            $path = '/';
        }
        $q = isset($p['query']) ? '?' . $p['query'] : '';
        $frag = isset($p['fragment']) ? '#' . $p['fragment'] : '';
        return $publicBase . $path . $q . $frag;
    }
    // Path-only redirects (/stable-…, /callback, …) must stay under /alfred-ide or the browser leaves the IDE mount.
    if (($p['scheme'] ?? '') === '' && ($p['host'] ?? '') === '' && str_starts_with($loc, '/') && !str_starts_with($loc, '/alfred-ide')) {
        $path = $p['path'] ?? '';
        $q = isset($p['query']) ? '?' . $p['query'] : '';
        $frag = isset($p['fragment']) ? '#' . $p['fragment'] : '';
        return $publicBase . $path . $q . $frag;
    }
    return $loc;
}

/**
 * Fix Location / Refresh headers from code-server so OAuth and navigation never point at loopback:8443 or drop the /alfred-ide prefix.
 */
function ideGateMaybeRewriteResponseHeaderLine(string $line, string $publicBase): string {
    if (preg_match('/^Location:\s*(.+)$/i', $line, $m)) {
        $new = ideGateRewriteRedirectUrl(trim($m[1], " \t\"'"), $publicBase);
        return 'Location: ' . $new;
    }
    if (preg_match('/^Refresh:\s*(.+)$/i', $line, $m)) {
        $val = trim($m[1]);
        if (preg_match('/^(.*\burl\s*=\s*)(.+)$/i', $val, $u)) {
            $urlPart = trim($u[2], " \t\"'");
            $rew = ideGateRewriteRedirectUrl($urlPart, $publicBase);
            return 'Refresh: ' . $u[1] . $rew;
        }
        return $line;
    }
    return $line;
}

function ideGateRedirectToCustomerWorkspace(PDO $db, int $clientId): void {
    ideGateClearLocalState();
    ideGateClearCookies();
    ideGateClearSessionBridge();

    try {
        $launch = alfred_workspace_build_launch($db, $clientId);
    } catch (Throwable $e) {
        error_log('[ALFRED-IDE-GATE] Workspace launch failed for client ' . $clientId . ': ' . $e->getMessage());
        ideGateRedirectToMainLogin();
    }

    if (empty($launch['success']) || empty($launch['url'])) {
        ideGateRedirectToMainLogin();
    }

    header('Location: ' . $launch['url']);
    exit;
}

function ideGateHasPaidAccess(PDO $db, int $clientId): bool {
    if ($clientId <= 0) return false;

    // Commander always has access
    if ($clientId === 33) return true;

    // Any user with an active gocodeme service has access
    $svc = $db->prepare("SELECT COUNT(*) FROM services s INNER JOIN products p ON p.id = s.product_id WHERE s.client_id = ? AND s.status = 'Active' AND p.server_module = 'gocodeme'");
    $svc->execute([$clientId]);
    return (int)$svc->fetchColumn() > 0;
}

/**
 * Ensure a customer workspace directory exists and return the path.
 */
function ideGateEnsureCustomerWorkspace(int $clientId): string {
    $wsRoot = '/home/root/customer-workspaces';
    $wsDir = $wsRoot . '/client_' . $clientId;
    if (!is_dir($wsDir)) {
        @mkdir($wsDir, 0755, true);
        // Scaffold a welcome project
        @mkdir($wsDir . '/my-project', 0755, true);
        @file_put_contents($wsDir . '/README.md', "# Welcome to Alfred IDE\n\nThis is your personal workspace. Create folders and files here — they're yours.\n\n## Getting Started\n\n1. Create a new file or folder in the Explorer sidebar\n2. Use the built-in terminal (Ctrl+\`)\n3. Chat with Alfred AI in the sidebar\n\nHappy coding!\n");
        @file_put_contents($wsDir . '/my-project/index.html', "<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n  <meta charset=\"UTF-8\">\n  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n  <title>My First Project</title>\n  <style>\n    body { font-family: system-ui, sans-serif; max-width: 600px; margin: 80px auto; text-align: center; }\n    h1 { color: #e2b340; }\n  </style>\n</head>\n<body>\n  <h1>Hello from Alfred IDE!</h1>\n  <p>Edit this file to start building your website.</p>\n</body>\n</html>\n");
    }
    return $wsDir;
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
    require_once __DIR__ . '/includes/alfred-workspace-launch.inc.php';

    $db = getSharedDB();
    $user = alfred_ide_lookup_user_by_token_hash($db, $tokenHash);

    if (!$user) {
        ideGateRedirectToMainLogin();
    }

    $clientId = (int)($user['client_id'] ?? 0);

    if (!ideGateHasPaidAccess($db, $clientId)) {
        // No paid access — send to workspace dashboard (shows upgrade options)
        header('Location: /alfred-workspace/dashboard.php');
        exit;
    }

    // For non-Commander customers, ensure workspace dir exists and store their folder
    if ($clientId !== 33) {
        $wsFolder = ideGateEnsureCustomerWorkspace($clientId);
        $_SESSION['ide_customer_folder'] = $wsFolder;
    } else {
        unset($_SESSION['ide_customer_folder']);
    }

    $_SESSION['ide_gate_hash'] = $tokenHash;
    $_SESSION['ide_gate_expires'] = time() + 30;
    $_SESSION['ide_gate_client_id'] = $clientId;

    ideGateWriteSessionBridge($token, $user);
}

// ── Validated — reverse proxy to code-server ────────────────────────────────

$path = $_GET['__path'] ?? '';
$path = ltrim($path, '/');

// Never proxy /alfred-ide/login to upstream code-server login pages.
// Keep auth centralized at /alfred-ide-auth.php so missing app configs cannot block sign-in.
if (preg_match('#^login(?:/.*)?$#i', $path)) {
    ideGateRedirectToMainLogin();
}
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

// For non-Commander customers accessing the root, add ?folder= to scope their workspace
$customerFolder = $_SESSION['ide_customer_folder'] ?? '';
if ($customerFolder) {
    $gateClientId = (int)($_SESSION['ide_gate_client_id'] ?? 0);
    if ($gateClientId !== 33) {
        // Never trust inbound folder query values for customer accounts.
        parse_str($qs, $queryParams);
        $queryParams['folder'] = $customerFolder;
        $qs = http_build_query($queryParams);
    }
}


function ideGateCodeServerCookie(): string {
    static $cached = null;
    if ($cached !== null) return $cached;
    $cacheFile = sys_get_temp_dir() . '/ide-gate-cs-cookie-' . posix_getuid();
    if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < 43200) {
        $c = trim((string)@file_get_contents($cacheFile));
        if ($c !== '') return $cached = $c;
    }
    $cfgPaths = [
        getenv('HOME') . '/.config/code-server/config.yaml',
        '/root/.config/code-server/config.yaml',
        '/etc/code-server/config.yaml',
    ];
    $password = '';
    foreach ($cfgPaths as $cp) {
        if (!@is_readable($cp)) continue;
        $cfg = (string)@file_get_contents($cp);
        if (preg_match('/^[ \t]*password[ \t]*:[ \t]*(.+)$/m', $cfg, $m)) {
            $password = trim($m[1], " \t\"'");
            if ($password !== '') break;
        }
    }
    if ($password === '') return $cached = '';
    foreach (['8443','8080'] as $port) {
        $ch = curl_init("http://127.0.0.1:$port/login");
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query(['password' => $password]),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CONNECTTIMEOUT => 2,
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);
        if (!is_string($resp)) continue;
        if (preg_match_all('/^Set-Cookie:[ \t]*([^=]+=[^;\r\n]+)/mi', $resp, $mm)) {
            $parts = [];
            foreach ($mm[1] as $cv) {
                $name = trim(strtok($cv, '='));
                if ($name === 'key' || stripos($name, 'code-server') !== false) {
                    $parts[] = trim($cv);
                }
            }
            if ($parts) {
                $cached = implode('; ', $parts);
                @file_put_contents($cacheFile, $cached);
                @chmod($cacheFile, 0600);
                return $cached;
            }
        }
    }
    return $cached = '';
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
$host = $_SERVER['HTTP_HOST'] ?? 'root.com';
$headers[] = 'Host: ' . $host;
$headers[] = 'X-Forwarded-Host: ' . $host;
$headers[] = 'X-Forwarded-Proto: ' . $scheme;
$headers[] = 'X-Forwarded-For: ' . ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
$headers[] = 'X-Forwarded-Prefix: /alfred-ide';
$headers[] = 'X-Forwarded-Uri: ' . ($_SERVER['REQUEST_URI'] ?? '/alfred-ide/');
if (!empty($_SERVER['CONTENT_TYPE'])) {
    $headers[] = 'Content-Type: ' . $_SERVER['CONTENT_TYPE'];
}

$__csCookie = ideGateCodeServerCookie();
@file_put_contents('/tmp/ide-gate-trace.log', date('c').' url='.$url.' cookie_len='.strlen($__csCookie).' first40='.substr($__csCookie,0,40)."\n", FILE_APPEND); // IDE_GATE_TRACE
if ($__csCookie !== '') { $headers[] = 'Cookie: ' . $__csCookie; }

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

// Forward response headers — skip hop-by-hop headers; forward Set-Cookie (GitHub OAuth / workbench session).
$skipHeaders = ['transfer-encoding', 'connection', 'keep-alive',
                'x-powered-by', 'server', 'content-security-policy'];
$publicIdeBase = ideGatePublicIdeBase();
foreach (explode("\r\n", $responseHeaders) as $line) {
    if (empty($line) || strpos($line, 'HTTP/') === 0) {
        continue;
    }
    $line = ideGateMaybeRewriteResponseHeaderLine($line, $publicIdeBase);
    $lowerLine = strtolower($line);
    $skip = false;
    foreach ($skipHeaders as $sh) {
        if (strpos($lowerLine, $sh . ':') === 0) {
            $skip = true;
            break;
        }
    }
    if (!$skip) {
        header($line, false);
    }
}

echo $body;
