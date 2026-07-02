<?php
/**
 * Discord Linked Roles Verification
 * ──────────────────────────────────
 * OAuth2 flow for Discord Linked Roles.
 * Users verify as GoSiteMe customers and receive metadata-based roles.
 *
 * Flow:
 *   1. User clicks "Connect" in Discord server role settings
 *   2. Discord redirects to this URL with OAuth2 code
 *   3. We verify the user is a GoSiteMe client
 *   4. Push role metadata back to Discord
 */

define('GOSITEME_API', true);
$GLOBALS['CSRF_EXEMPT'] = true; // Discord OAuth callback
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

session_start();

$clientId = getenv('DISCORD_APP_ID') ?: '';
$clientSecret = getenv('DISCORD_BOT_TOKEN') ?: ''; // Bot token used for API calls
$redirectUri = SITE_URL . '/api/discord-linked-roles.php';

// ─── Step 1: Redirect to Discord OAuth2 ────────────────────────────────
if (!isset($_GET['code']) && !isset($_GET['action'])) {
    $state = bin2hex(random_bytes(16));
    $_SESSION['discord_oauth_state'] = $state;
    
    $params = http_build_query([
        'client_id'     => $clientId,
        'redirect_uri'  => $redirectUri,
        'response_type' => 'code',
        'state'         => $state,
        'scope'         => 'identify role_connections.write',
        'prompt'        => 'consent',
    ]);
    
    header('Location: https://discord.com/api/oauth2/authorize?' . $params);
    exit;
}

// ─── Step 2: Handle OAuth2 callback ────────────────────────────────────
if (isset($_GET['code'])) {
    // Verify state
    if (!isset($_GET['state']) || !isset($_SESSION['discord_oauth_state']) || 
        !hash_equals($_SESSION['discord_oauth_state'], $_GET['state'])) {
        http_response_code(400);
        die('Invalid state parameter');
    }
    unset($_SESSION['discord_oauth_state']);
    
    // Exchange code for token
    $ch = curl_init('https://discord.com/api/v10/oauth2/token');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_POSTFIELDS => http_build_query([
            'client_id'     => $clientId,
            'client_secret' => getenv('DISCORD_CLIENT_SECRET') ?: '',
            'grant_type'    => 'authorization_code',
            'code'          => $_GET['code'],
            'redirect_uri'  => $redirectUri,
        ]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    ]);
    $tokenResp = json_decode(curl_exec($ch), true);
    curl_close($ch);
    
    if (!isset($tokenResp['access_token'])) {
        http_response_code(400);
        die('Failed to obtain access token');
    }
    
    $accessToken = $tokenResp['access_token'];
    
    // Get Discord user info
    $ch = curl_init('https://discord.com/api/v10/users/@me');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken],
    ]);
    $user = json_decode(curl_exec($ch), true);
    curl_close($ch);
    
    $discordId = $user['id'] ?? '';
    $username = $user['username'] ?? '';
    
    // Check if user is a GoSiteMe client
    $db = getDB();
    $isClient = false;
    $clientSince = null;
    
    if ($db) {
        // Check if Discord ID is linked to a client account
        $stmt = $db->prepare("SELECT id, datecreated FROM tblclients WHERE id IN 
            (SELECT client_id FROM alfred_telegram_subscribers WHERE username = ?) 
            LIMIT 1");
        $stmt->execute([$username]);
        $client = $stmt->fetch();
        
        if ($client) {
            $isClient = true;
            $clientSince = $client['datecreated'];
        }
    }
    
    // Push role connection metadata back to Discord
    $metadata = [
        'platform_name' => 'GoSiteMe',
        'metadata' => [
            'verified' => $isClient ? 1 : 0,
        ],
    ];
    
    $ch = curl_init("https://discord.com/api/v10/users/@me/applications/{$clientId}/role-connection");
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_POSTFIELDS => json_encode($metadata),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ],
    ]);
    curl_exec($ch);
    curl_close($ch);
    
    // Show success page
    echo '<!DOCTYPE html><html><head><title>GoSiteMe Verification</title>';
    echo '<style>body{font-family:system-ui;background:#0a0e17;color:#fff;display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0}';
    echo '.card{background:#151c2c;border:1px solid #1e2d4a;border-radius:16px;padding:3rem;text-align:center;max-width:400px}';
    echo '.success{color:#10b981;font-size:3rem;margin-bottom:1rem}';
    echo 'h1{margin:0 0 .5rem}p{color:#8395a7}</style></head>';
    echo '<body><div class="card">';
    echo '<div class="success">✓</div>';
    echo '<h1>Verified!</h1>';
    echo '<p>Your Discord account <strong>' . htmlspecialchars($username) . '</strong> has been linked to GoSiteMe.</p>';
    echo '<p style="margin-top:1rem;font-size:.85rem">You can close this window and return to Discord.</p>';
    echo '</div></body></html>';
    exit;
}

// ─── Register metadata schema (one-time setup) ────────────────────────
if (($_GET['action'] ?? '') === 'register-metadata') {
    $secret = getenv('INTERNAL_SECRET') ?: '';
    $token = $_SERVER['HTTP_X_INTERNAL_SECRET'] ?? '';
    if (!$secret || !hash_equals($secret, $token)) {
        http_response_code(403);
        die(json_encode(['error' => 'Unauthorized']));
    }
    
    $botToken = getenv('DISCORD_BOT_TOKEN') ?: '';
    
    $metadata = [
        [
            'key' => 'verified',
            'name' => 'Verified Customer',
            'description' => 'GoSiteMe verified customer',
            'type' => 7, // Boolean
        ],
    ];
    
    $ch = curl_init("https://discord.com/api/v10/applications/{$clientId}/role-connections/metadata");
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_POSTFIELDS => json_encode($metadata),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bot ' . $botToken,
            'Content-Type: application/json',
        ],
    ]);
    $resp = json_decode(curl_exec($ch), true);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => $code === 200, 'response' => $resp]);
    exit;
}
