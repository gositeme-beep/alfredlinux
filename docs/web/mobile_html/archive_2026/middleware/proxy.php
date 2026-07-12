<?php
/**
 * GoCodeMe Middleware PHP Reverse Proxy
 * Routes HTTP requests to Node.js middleware on 127.0.0.1:3001
 * 
 * Required because Apache mod_proxy_http is not available on this
 * DirectAdmin server (only mod_proxy_wstunnel works for WebSocket [P] flag).
 * WebSocket upgrades still go through Apache's [P] flag directly.
 */

// Debug: log all errors to file
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/home/gositeme/domains/gositeme.com/logs/proxy-errors.log');

// Catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_log("FATAL: " . json_encode($error));
    }
});

// The target Node.js middleware
define('UPSTREAM', 'http://127.0.0.1:3001');

// ── Self-Healing: Auto-start middleware if port 3001 is down ────────────────
// The jailshell kills background processes between sessions, so PM2/screen/nohup
// don't persist. This PHP layer is the only reliable auto-starter.
$sock = @fsockopen('127.0.0.1', 3001, $errno, $errstr, 1);
if ($sock) {
    fclose($sock);
} else {
    // Middleware is down — try to start it
    $lockFile = '/tmp/gocodeme-mw-starting.lock';
    $lockAge = @filemtime($lockFile);
    
    // Only attempt start if no other request is already starting it (30s cooldown)
    if (!$lockAge || (time() - $lockAge) > 30) {
        @touch($lockFile);
        
        // Start Redis if not running
        $redisSock = @fsockopen('127.0.0.1', 6379, $re, $rs, 1);
        if ($redisSock) {
            fclose($redisSock);
        } else {
            $redisCmd = '/home/gositeme/.local/bin/redis-server /home/gositeme/.local/redis/redis.conf --daemonize yes';
            $disabled = explode(',', ini_get('disable_functions'));
            $disabled = array_map('trim', $disabled);
            if (!in_array('exec', $disabled) && function_exists('exec')) {
                @exec($redisCmd . ' > /dev/null 2>&1');
            }
        }
        
        // Start the middleware
        $mwDir = '/home/gositeme/domains/gositeme.com/public_html/gocodeme/middleware';
        $logFile = '/home/gositeme/domains/gositeme.com/logs/middleware.log';
        $cmd = "cd {$mwDir} && nohup node src/server.js >> {$logFile} 2>&1 & echo \$!";
        $disabled = $disabled ?? array_map('trim', explode(',', ini_get('disable_functions')));
        $pid = (!in_array('shell_exec', $disabled) && function_exists('shell_exec')) ? trim(@shell_exec($cmd)) : '';
        
        if ($pid) {
            @file_put_contents($lockFile, $pid);
        }
        
        // Wait for the middleware to boot (up to 8 seconds)
        $started = false;
        for ($i = 0; $i < 16; $i++) {
            usleep(500000); // 500ms
            $check = @fsockopen('127.0.0.1', 3001, $ce, $cs, 1);
            if ($check) {
                fclose($check);
                $started = true;
                break;
            }
        }
        
        if (!$started) {
            @unlink($lockFile);
            http_response_code(503);
            header('Content-Type: application/json');
            header('Retry-After: 10');
            echo json_encode(['error' => 'Service Unavailable', 'detail' => 'Middleware is starting up, please retry in a few seconds.']);
            exit;
        }
    } else {
        // Another request is already starting the middleware — wait briefly
        $started = false;
        for ($i = 0; $i < 10; $i++) {
            usleep(500000);
            $check = @fsockopen('127.0.0.1', 3001, $ce, $cs, 1);
            if ($check) {
                fclose($check);
                $started = true;
                break;
            }
        }

        if (!$started) {
            http_response_code(503);
            header('Content-Type: application/json');
            header('Retry-After: 10');
            echo json_encode([
                'error' => 'Service Unavailable',
                'detail' => 'Middleware is offline. Auto-recovery is in progress, please retry shortly.'
            ]);
            exit;
        }
    }
}

// Allow long-running requests (socket.io long-polling can take 25-30s per cycle)
set_time_limit(300);

// Get the request path (strip /middleware/ prefix that gets us here)
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($requestUri, PHP_URL_PATH) ?: '/';

// Remove /middleware/ prefix to get the actual path for the upstream
if (str_starts_with($path, '/middleware/')) {
    $path = '/' . substr($path, strlen('/middleware/'));
} elseif (str_starts_with($path, '/middleware')) {
    $path = '/' . substr($path, strlen('/middleware'));
}
if ($path === '') $path = '/';

// Start session ONLY when needed and only if a session cookie already exists.
// This prevents /middleware/api/* calls from emitting Set-Cookie: PHPSESSID
// which can clobber the main site's session and break pages.
$isLoggedIn = false;
$userName = '';
$looksLikeApi = str_starts_with($path, '/api/');
$looksLikeSocketIo = str_starts_with($path, '/socket.io');

if (!$looksLikeApi && !$looksLikeSocketIo && isset($_COOKIE[session_name()])) {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', 1);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', 'Lax');
        session_start();
    }
    $isLoggedIn = !empty($_SESSION['logged_in']) && !empty($_SESSION['client_id']);
    $userName   = htmlspecialchars($_SESSION['client_name'] ?? '', ENT_QUOTES, 'UTF-8');

    // Release session lock immediately — we only read session data above.
    session_write_close();
}

// Preserve query string
$query = parse_url($requestUri, PHP_URL_QUERY);
$upstreamUrl = UPSTREAM . $path . ($query ? '?' . $query : '');

// Build curl request
$ch = curl_init($upstreamUrl);

// Forward the HTTP method
$method = $_SERVER['REQUEST_METHOD'];
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
if ($method === "HEAD") {
    curl_setopt($ch, CURLOPT_NOBODY, true);
}

// Forward request body for POST/PUT/PATCH
if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
    $body = file_get_contents('php://input');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
}

// Forward relevant headers
$forwardHeaders = [];
$skipHeaders = ['host', 'connection', 'transfer-encoding', 'content-length', 'accept-encoding'];
foreach (getallheaders() as $name => $value) {
    if (!in_array(strtolower($name), $skipHeaders)) {
        $forwardHeaders[] = "$name: $value";
    }
}
// Add forwarding headers
$forwardHeaders[] = 'X-Forwarded-For: ' . ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
$forwardHeaders[] = 'X-Forwarded-Proto: https';
$forwardHeaders[] = 'X-Real-IP: ' . ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
$forwardHeaders[] = 'X-Forwarded-Host: ' . ($_SERVER['HTTP_HOST'] ?? 'gositeme.com');
curl_setopt($ch, CURLOPT_HTTPHEADER, $forwardHeaders);

// Handle SSE (Server-Sent Events) streams
$isSSE = false;

// Capture response headers
$responseHeaders = [];
$setCookieHeaders = [];
curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($ch, $header) use (&$responseHeaders, &$isSSE, &$setCookieHeaders) {
    $len = strlen($header);
    $parts = explode(':', $header, 2);
    if (count($parts) === 2) {
        $name = trim($parts[0]);
        $value = trim($parts[1]);
        
        // Set-Cookie can appear multiple times — collect in array
        if (strtolower($name) === 'set-cookie') {
            $setCookieHeaders[] = $value;
        } else {
            $responseHeaders[strtolower($name)] = $value;
        }
        
        // Detect SSE
        if (strtolower($name) === 'content-type' && str_contains(strtolower($value), 'text/event-stream')) {
            $isSSE = true;
        }
    }
    return $len;
});

// Basic curl options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Don't follow redirects — pass them to browser
curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5 min timeout for long operations
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

// Execute request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$error = curl_error($ch);
curl_close($ch);

// Handle errors
if ($response === false) {
    http_response_code(502);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Bad Gateway', 'detail' => $error]);
    exit;
}

// Handle redirects — pass Location header back to browser
if ($httpCode >= 300 && $httpCode < 400 && isset($responseHeaders['location'])) {
    http_response_code($httpCode);
    header('Location: ' . $responseHeaders['location']);
    exit;
}

// Set response status
http_response_code($httpCode);

// Forward response headers (selective, sanitized)
$passHeaders = ['content-type', 'cache-control', 'etag', 'last-modified', 'x-request-id', 'x-powered-by'];
foreach ($responseHeaders as $name => $value) {
    if (in_array($name, $passHeaders)) {
        // SECURITY (VULN-18): Strip \r\n to prevent header injection
        $safeValue = str_replace(["\r", "\n"], '', $value);
        header(ucwords($name, '-') . ': ' . $safeValue);
    }
}
// Forward Set-Cookie headers from upstream (needed for IDE auth cookies)
foreach ($setCookieHeaders as $cookieValue) {
    $safeCookie = str_replace(["\r", "\n"], '', $cookieValue);
    header('Set-Cookie: ' . $safeCookie, false); // false = don't replace previous Set-Cookie
}

// CORS headers for IDE usage — restrict to known origins (VULN-09 fix)
$allowedOrigins = ['https://gocodeme.com', 'https://www.gocodeme.com', 'https://gositeme.com'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
} else {
    header('Access-Control-Allow-Origin: https://gocodeme.com');
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

// Handle preflight
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Let socket.io advertise WebSocket upgrades as-is.
// The browser will attempt a WebSocket upgrade, it will fail through
// the PHP proxy (no persistent connection), and socket.io will gracefully
// fall back to HTTP long-polling — which works correctly through curl.

// ── Inject GoSiteMe Header into HTML responses ──────────────────────────────
// The upstream Node.js app serves full HTML pages without our site header.
// We inject a compact, fixed-position header bar so middleware/dashboard
// matches the look & feel of the rest of gositeme.com.
// SKIP injection for IDE pages — the header covers the editor.
if (
    $httpCode === 200
    && str_contains($contentType ?? '', 'text/html')
    && str_contains($response, '<body')
    && !preg_match('#/ide/\d+#', $path)
) {
    // Build header bar with session-aware CTA
    $ctaHtml = $isLoggedIn
        ? '<a href="/dashboard.php" class="gsm-nav" style="font-size:.82rem;"><i class="fas fa-gauge-high" style="margin-right:4px;"></i>' . ($userName ?: 'Dashboard') . '</a>'
        : '<a href="/login" class="gsm-nav" style="font-size:.82rem;"><i class="fas fa-user" style="margin-right:4px;"></i>Login</a>'
          . '<a href="/register" class="gsm-btn">Get Started</a>';

    $alfredLink = $isLoggedIn ? '/conversations' : '/alfred.php';

    $headerBar = <<<HEADERHTML
<!-- GoSiteMe Injected Header -->
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<style>
.gsm-hdr{position:fixed;top:0;left:0;right:0;z-index:99999;height:52px;background:rgba(10,10,20,0.97);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border-bottom:1px solid rgba(0,168,255,0.12);display:flex;align-items:center;padding:0 20px;font-family:'Inter',system-ui,sans-serif}
.gsm-hdr a{text-decoration:none;color:#a8b2d1;transition:color .2s}
.gsm-hdr a:hover{color:#fff}
.gsm-hdr .gsm-logo{display:flex;align-items:center;gap:10px;margin-right:auto}
.gsm-hdr .gsm-logo img{height:32px;width:auto}
.gsm-hdr .gsm-nav{display:flex;align-items:center;gap:4px;list-style:none;margin:0;padding:0}
.gsm-hdr .gsm-nav a{padding:6px 12px;border-radius:6px;font-size:.82rem;font-weight:500;white-space:nowrap}
.gsm-hdr .gsm-nav a:hover{background:rgba(255,255,255,0.06)}
.gsm-hdr .gsm-nav a.active{color:#55efc4;background:rgba(0,184,148,0.1)}
.gsm-hdr .gsm-nav .gsm-gc{background:linear-gradient(135deg,#7D00FF,#00A8FF);color:#fff!important;font-weight:600;padding:6px 14px;box-shadow:0 2px 10px rgba(125,0,255,0.3)}
.gsm-hdr .gsm-nav .gsm-gc:hover{box-shadow:0 4px 18px rgba(125,0,255,0.5);background:linear-gradient(135deg,#9D4EDD,#00D4FF)}
.gsm-hdr .gsm-cta{display:flex;align-items:center;gap:8px;margin-left:16px}
.gsm-hdr .gsm-btn{padding:6px 16px;border-radius:8px;font-size:.82rem;font-weight:600;background:linear-gradient(135deg,#0074D9,#00A8FF);color:#fff;box-shadow:0 2px 12px rgba(0,116,217,0.3)}
.gsm-hdr .gsm-btn:hover{box-shadow:0 4px 20px rgba(0,116,217,0.5);transform:translateY(-1px);color:#fff}
@media(max-width:900px){.gsm-hdr .gsm-nav{display:none}.gsm-hdr .gsm-mob{display:flex!important}}
.gsm-hdr .gsm-mob{display:none!important;background:none;border:1px solid rgba(255,255,255,0.15);color:#fff;border-radius:6px;padding:4px 8px;font-size:1rem;cursor:pointer;margin-left:8px}
</style>
<header class="gsm-hdr">
  <a href="/" class="gsm-logo"><img src="/brand/logo_w.png" alt="GoSiteMe"></a>
  <nav class="gsm-nav">
    <a href="/store/ai-domain-hosting-connected-with-ai-editor"><i class="fas fa-robot" style="margin-right:4px;font-size:.75rem;"></i> Hosting</a>
    <a href="/gocodeme.php" class="gsm-gc"><i class="fas fa-wand-magic-sparkles" style="margin-right:4px;"></i>GoCodeMe</a>
    <a href="/middleware/dashboard" class="active"><i class="fas fa-globe" style="margin-right:4px;font-size:.75rem;"></i> Dashboard</a>
    <a href="{$alfredLink}"><i class="fas fa-robot" style="margin-right:4px;font-size:.75rem;"></i> Alfred AI</a>
    <a href="/docs/"><i class="fas fa-book" style="margin-right:4px;font-size:.75rem;"></i> Docs</a>
    <a href="/invest" style="color:#55efc4;"><i class="fas fa-chart-line" style="margin-right:4px;font-size:.75rem;"></i> Invest</a>
    <a href="/contact"><i class="fas fa-envelope" style="margin-right:4px;font-size:.75rem;"></i> Support</a>
  </nav>
  <div class="gsm-cta">
    {$ctaHtml}
  </div>
  <button class="gsm-mob" onclick="let n=document.querySelector('.gsm-nav');n.style.display=n.style.display==='flex'?'none':'flex'"><i class="fas fa-bars"></i></button>
</header>
HEADERHTML;

    // Inject after <body> or <body ...> opening tag
    $response = preg_replace(
        '/(<body[^>]*>)/i',
        '$1' . $headerBar,
        $response,
        1 // only first <body> tag
    );

    // Also inject padding-top on body so content isn't hidden behind fixed header
    // Use preg_replace with limit 1 to avoid replacing </head> in JS template literals
    $response = preg_replace(
        '/<\/head>/i',
        '<style>body{padding-top:52px!important;}</style></head>',
        $response,
        1
    );
}

// Output response body
echo $response;
