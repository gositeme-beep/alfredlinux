<?php
/**
 * Alfred OS — Sovereignty Status API
 * 
 * Reports whether the system is in online or offline (sovereignty) mode.
 * Also allows toggling offline mode via the flag file.
 * 
 * GET  /api/sovereignty-status.php  — Check current mode
 * POST /api/sovereignty-status.php  — Toggle mode (requires auth)
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json');

$flagFile  = dirname(__DIR__) . '/.sovereignty-offline';
$cacheDir  = dirname(__DIR__) . '/cache/gateway';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $isOffline = file_exists($flagFile);

    // Count cached responses
    $cachedCount = 0;
    if (is_dir($cacheDir)) {
        $cachedCount = count(glob($cacheDir . '/*.json')) - 1; // minus connectivity check
        if ($cachedCount < 0) $cachedCount = 0;
    }

    // Check what assets are self-hosted
    $assetsDir = dirname(__DIR__) . '/assets';
    $fontCount = 0;
    $vendorJsCount = 0;
    if (is_dir("$assetsDir/fonts")) {
        $fontCount = count(glob("$assetsDir/fonts/*/*.woff2"));
    }
    if (is_dir("$assetsDir/js/vendor")) {
        $vendorJsCount = count(glob("$assetsDir/js/vendor/*.js"));
    }

    echo json_encode([
        'sovereignty_mode' => $isOffline ? 'offline' : 'online',
        'can_go_offline' => true,
        'assets' => [
            'fonts_self_hosted'      => $fontCount,
            'vendor_js_self_hosted'  => $vendorJsCount,
            'fontawesome_local'      => is_dir("$assetsDir/fontawesome"),
            'fonts_css_exists'       => file_exists("$assetsDir/css/fonts.css"),
        ],
        'cache' => [
            'cached_api_responses' => $cachedCount,
            'cache_dir_exists'     => is_dir($cacheDir),
        ],
        'external_dependencies' => [
            'google_fonts'    => 'eliminated',
            'cdn_js_css'      => 'eliminated',
            'api_calls'       => 'proxied_via_gateway',
            'auth_providers'  => 'required_online (Google OAuth, Stripe)',
        ],
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Require internal secret for toggling
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $internalSecret = getenv('INTERNAL_SECRET') ?: '';
    
    if (!$internalSecret || $authHeader !== "Bearer $internalSecret") {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $mode = $input['mode'] ?? '';

    if ($mode === 'offline') {
        file_put_contents($flagFile, json_encode([
            'activated_at' => date('c'),
            'reason' => $input['reason'] ?? 'Manual activation',
        ]));
        echo json_encode(['sovereignty_mode' => 'offline', 'message' => 'System is now in sovereignty/offline mode']);
    } elseif ($mode === 'online') {
        if (file_exists($flagFile)) {
            unlink($flagFile);
        }
        echo json_encode(['sovereignty_mode' => 'online', 'message' => 'System is now in online mode']);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Required: mode (online|offline)']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
