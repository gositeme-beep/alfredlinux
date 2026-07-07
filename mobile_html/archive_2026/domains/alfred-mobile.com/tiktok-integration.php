<?php
/**
 * ═══════════════════════════════════════════════════════════════
 *  TIKTOK OAUTH + DISPLAY API INTEGRATION
 *  Handles:
 *  - OAuth authorization flow (user.info.basic scope)
 *  - Access token management
 *  - Display user profile
 *  - Display user videos
 *  - Token refresh
 *
 *  Based on: https://developers.tiktok.com/doc/display-api-get-started/
 * ═══════════════════════════════════════════════════════════════
 */

session_start();

// Load TikTok API credentials from vault
require_once __DIR__ . '/includes/db-config.inc.php';
require_once __DIR__ . '/scripts/vault-crypto.php';

function getTikTokCreds() {
    static $creds = null;
    if ($creds) return $creds;

    $pdo = new PDO(
        'mysql:unix_socket=/run/mysql/mysql.sock;dbname=' . GOSITEME_DB_NAME,
        GOSITEME_DB_USER, GOSITEME_DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $stmt = $pdo->prepare("SELECT username, password FROM commander_credentials WHERE credential_id = 'tiktok-api'");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) throw new Exception('TikTok API credentials not found in vault');

    $creds = [
        'client_key'    => vault_decrypt($row['username']),
        'client_secret' => vault_decrypt($row['password']),
    ];
    return $creds;
}

$REDIRECT_URI = 'https://gositeme.com/tiktok-callback';

// ═══════════════════════════════════════════════════════════════
//  ROUTE: /tiktok-auth — Start OAuth flow
// ═══════════════════════════════════════════════════════════════
if (isset($_GET['action']) && $_GET['action'] === 'auth') {
    $creds = getTikTokCreds();
    $csrfState = bin2hex(random_bytes(16));
    $_SESSION['tiktok_csrf'] = $csrfState;

    $params = http_build_query([
        'client_key'    => $creds['client_key'],
        'scope'         => 'user.info.basic,video.list',
        'response_type' => 'code',
        'redirect_uri'  => $REDIRECT_URI,
        'state'         => $csrfState,
    ]);

    header('Location: https://www.tiktok.com/v2/auth/authorize/?' . $params);
    exit;
}

// ═══════════════════════════════════════════════════════════════
//  ROUTE: /tiktok-callback — Handle OAuth callback
// ═══════════════════════════════════════════════════════════════
if (isset($_GET['action']) && $_GET['action'] === 'callback') {
    header('Content-Type: application/json');

    // CSRF check
    if (!isset($_GET['state']) || !isset($_SESSION['tiktok_csrf']) || $_GET['state'] !== $_SESSION['tiktok_csrf']) {
        http_response_code(403);
        echo json_encode(['error' => 'CSRF validation failed']);
        exit;
    }
    unset($_SESSION['tiktok_csrf']);

    if (isset($_GET['error'])) {
        echo json_encode(['error' => $_GET['error'], 'description' => $_GET['error_description'] ?? '']);
        exit;
    }

    $code = $_GET['code'] ?? '';
    if (!$code) {
        echo json_encode(['error' => 'No authorization code received']);
        exit;
    }

    // Exchange code for access token
    $creds = getTikTokCreds();
    $tokenData = tiktokApiCall('https://open.tiktokapis.com/v2/oauth/token/', [
        'client_key'    => $creds['client_key'],
        'client_secret' => $creds['client_secret'],
        'code'          => $code,
        'grant_type'    => 'authorization_code',
        'redirect_uri'  => $REDIRECT_URI,
    ]);

    if (isset($tokenData['access_token'])) {
        // Store tokens securely in session
        $_SESSION['tiktok_access_token']  = $tokenData['access_token'];
        $_SESSION['tiktok_refresh_token'] = $tokenData['refresh_token'] ?? '';
        $_SESSION['tiktok_open_id']       = $tokenData['open_id'] ?? '';
        $_SESSION['tiktok_expires']       = time() + ($tokenData['expires_in'] ?? 86400);
        $_SESSION['tiktok_scope']         = $tokenData['scope'] ?? '';

        // Store refresh token in vault for background use
        $pdo = new PDO(
            'mysql:unix_socket=/run/mysql/mysql.sock;dbname=' . GOSITEME_DB_NAME,
            GOSITEME_DB_USER, GOSITEME_DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $stmt = $pdo->prepare("UPDATE commander_credentials SET notes = ? WHERE credential_id = 'tiktok-api'");
        $stmt->execute([vault_encrypt(json_encode([
            'access_token'  => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'] ?? '',
            'open_id'       => $tokenData['open_id'] ?? '',
            'expires'       => time() + ($tokenData['expires_in'] ?? 86400),
            'scope'         => $tokenData['scope'] ?? '',
            'obtained_at'   => date('Y-m-d H:i:s'),
        ]))]);

        header('Location: /tiktok-integration?action=dashboard');
        exit;
    }

    echo json_encode(['error' => 'Token exchange failed', 'details' => $tokenData]);
    exit;
}

// ═══════════════════════════════════════════════════════════════
//  ROUTE: /tiktok-integration?action=profile — Get user profile
// ═══════════════════════════════════════════════════════════════
if (isset($_GET['action']) && $_GET['action'] === 'profile') {
    header('Content-Type: application/json');
    $token = getAccessToken();
    if (!$token) { echo json_encode(['error' => 'Not authenticated']); exit; }

    $result = tiktokGet('https://open.tiktokapis.com/v2/user/info/', $token, [
        'fields' => 'open_id,union_id,avatar_url,display_name,bio_description,profile_deep_link,is_verified,follower_count,following_count,likes_count,video_count',
    ]);
    echo json_encode($result);
    exit;
}

// ═══════════════════════════════════════════════════════════════
//  ROUTE: /tiktok-integration?action=videos — Get user's videos
// ═══════════════════════════════════════════════════════════════
if (isset($_GET['action']) && $_GET['action'] === 'videos') {
    header('Content-Type: application/json');
    $token = getAccessToken();
    if (!$token) { echo json_encode(['error' => 'Not authenticated']); exit; }

    $body = ['max_count' => 20];
    if (isset($_GET['cursor'])) $body['cursor'] = (int)$_GET['cursor'];

    $result = tiktokPost('https://open.tiktokapis.com/v2/video/list/', $token, $body, [
        'fields' => 'id,title,video_description,duration,cover_image_url,embed_link,like_count,comment_count,share_count,view_count,create_time',
    ]);
    echo json_encode($result);
    exit;
}

// ═══════════════════════════════════════════════════════════════
//  ROUTE: /tiktok-integration?action=refresh — Refresh token
// ═══════════════════════════════════════════════════════════════
if (isset($_GET['action']) && $_GET['action'] === 'refresh') {
    header('Content-Type: application/json');
    $refreshToken = $_SESSION['tiktok_refresh_token'] ?? '';
    if (!$refreshToken) {
        // Try from vault
        $refreshToken = getRefreshTokenFromVault();
    }
    if (!$refreshToken) {
        echo json_encode(['error' => 'No refresh token available']);
        exit;
    }

    $creds = getTikTokCreds();
    $tokenData = tiktokApiCall('https://open.tiktokapis.com/v2/oauth/token/', [
        'client_key'    => $creds['client_key'],
        'client_secret' => $creds['client_secret'],
        'grant_type'    => 'refresh_token',
        'refresh_token' => $refreshToken,
    ]);

    if (isset($tokenData['access_token'])) {
        $_SESSION['tiktok_access_token']  = $tokenData['access_token'];
        $_SESSION['tiktok_refresh_token'] = $tokenData['refresh_token'] ?? $refreshToken;
        $_SESSION['tiktok_expires']       = time() + ($tokenData['expires_in'] ?? 86400);
        echo json_encode(['success' => true, 'expires_in' => $tokenData['expires_in']]);
    } else {
        echo json_encode(['error' => 'Refresh failed', 'details' => $tokenData]);
    }
    exit;
}

// ═══════════════════════════════════════════════════════════════
//  API HELPERS
// ═══════════════════════════════════════════════════════════════

function tiktokApiCall($url, $data) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_POSTFIELDS     => http_build_query($data),
        CURLOPT_TIMEOUT        => 15,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true) ?: [];
}

function tiktokGet($url, $token, $params = []) {
    if ($params) $url .= '?' . http_build_query($params);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => 15,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true) ?: [];
}

function tiktokPost($url, $token, $body, $params = []) {
    if ($params) $url .= '?' . http_build_query($params);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode($body),
        CURLOPT_TIMEOUT    => 15,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true) ?: [];
}

function getAccessToken() {
    if (isset($_SESSION['tiktok_access_token']) && $_SESSION['tiktok_expires'] > time()) {
        return $_SESSION['tiktok_access_token'];
    }
    // Try from vault
    $tokens = getTokensFromVault();
    if ($tokens && isset($tokens['access_token']) && $tokens['expires'] > time()) {
        $_SESSION['tiktok_access_token'] = $tokens['access_token'];
        $_SESSION['tiktok_refresh_token'] = $tokens['refresh_token'] ?? '';
        $_SESSION['tiktok_expires'] = $tokens['expires'];
        return $tokens['access_token'];
    }
    return null;
}

function getTokensFromVault() {
    try {
        $pdo = new PDO(
            'mysql:unix_socket=/run/mysql/mysql.sock;dbname=' . GOSITEME_DB_NAME,
            GOSITEME_DB_USER, GOSITEME_DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $stmt = $pdo->prepare("SELECT notes FROM commander_credentials WHERE credential_id = 'tiktok-api'");
        $stmt->execute();
        $enc = $stmt->fetchColumn();
        if (!$enc) return null;
        $dec = vault_decrypt($enc);
        return json_decode($dec, true);
    } catch (Exception $e) {
        return null;
    }
}

function getRefreshTokenFromVault() {
    $tokens = getTokensFromVault();
    return $tokens['refresh_token'] ?? null;
}

// ═══════════════════════════════════════════════════════════════
//  DASHBOARD (default action)
// ═══════════════════════════════════════════════════════════════
$hasToken = getAccessToken() !== null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TikTok Integration — Alfred / GoSiteMe</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background:#0a0a1a; color:#e0e0ff; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif; padding:24px; }
        h1 { font-size:1.8rem; margin-bottom:8px; }
        h1 span { color:#ee1d52; }
        .subtitle { color:#666; margin-bottom:24px; }
        .card { background:#111133; border:1px solid #2a2a5e; border-radius:16px; padding:24px; margin-bottom:24px; max-width:800px; }
        .card h2 { color:#ee1d52; margin-bottom:16px; }
        .btn { display:inline-block; background:#ee1d52; color:#fff; padding:12px 28px; border-radius:8px; text-decoration:none; font-weight:600; font-size:1rem; border:none; cursor:pointer; }
        .btn:hover { background:#cc1847; }
        .btn-secondary { background:#1a1a3e; border:1px solid #3a3a7e; }
        .btn-secondary:hover { border-color:#ee1d52; }
        .profile { display:flex; align-items:center; gap:20px; }
        .profile img { width:80px; height:80px; border-radius:50%; border:3px solid #ee1d52; }
        .profile-info h3 { font-size:1.2rem; }
        .profile-info p { color:#888; font-size:0.9rem; }
        .stats { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-top:16px; }
        .stat { background:#0d0d2a; border-radius:10px; padding:14px; text-align:center; }
        .stat-value { font-size:1.3rem; font-weight:700; }
        .stat-label { font-size:0.7rem; color:#888; margin-top:4px; }
        .video-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-top:16px; }
        .video-card { background:#0d0d2a; border-radius:10px; overflow:hidden; }
        .video-card img { width:100%; aspect-ratio:9/16; object-fit:cover; }
        .video-card .info { padding:10px; }
        .video-card .info h4 { font-size:0.85rem; margin-bottom:4px; }
        .video-card .info .meta { font-size:0.7rem; color:#888; }
        .status-badge { display:inline-block; padding:4px 12px; border-radius:20px; font-size:0.8rem; font-weight:600; }
        .status-badge.connected { background:rgba(34,197,94,0.2); color:#22c55e; }
        .status-badge.disconnected { background:rgba(229,62,62,0.2); color:#e53e3e; }
        #loading { display:none; color:#888; }
    </style>
</head>
<body>

<h1>🎵 <span>TikTok</span> Integration</h1>
<p class="subtitle">Connect GoSiteMe to TikTok — display profile, videos, and go live with Alfred</p>

<div class="card">
    <h2>Connection Status</h2>
    <?php if ($hasToken): ?>
        <span class="status-badge connected">✓ Connected</span>
        <div id="profile-section" class="profile" style="margin-top:16px;">
            <div id="loading">Loading profile...</div>
        </div>
        <div id="stats-section"></div>
        <div style="margin-top:16px;">
            <a href="?action=auth" class="btn btn-secondary" style="margin-right:8px;">🔄 Reconnect</a>
            <a href="?action=refresh" class="btn btn-secondary">🔑 Refresh Token</a>
            <a href="/livestream" class="btn" style="margin-left:8px;">📡 Go Live with Alfred</a>
        </div>
    <?php else: ?>
        <span class="status-badge disconnected">✗ Not Connected</span>
        <p style="margin:16px 0;color:#888;">Connect your TikTok account to display your profile, videos, and enable Alfred livestreaming.</p>
        <a href="?action=auth" class="btn">🎵 Connect TikTok</a>
    <?php endif; ?>
</div>

<?php if ($hasToken): ?>
<div class="card">
    <h2>Your Videos</h2>
    <div id="video-grid" class="video-grid">
        <div id="loading-videos" style="grid-column:1/-1;color:#888;">Loading videos...</div>
    </div>
</div>

<script>
// Load profile
fetch('?action=profile')
    .then(r => r.json())
    .then(data => {
        const user = data?.data?.user;
        if (!user) { document.getElementById('profile-section').innerHTML = '<p style="color:#e53e3e;">Could not load profile</p>'; return; }
        document.getElementById('profile-section').innerHTML = `
            <img src="${user.avatar_url}" alt="${user.display_name}">
            <div class="profile-info">
                <h3>${user.display_name} ${user.is_verified ? '✓' : ''}</h3>
                <p>${user.bio_description || 'No bio'}</p>
                <a href="${user.profile_deep_link}" target="_blank" style="color:#ee1d52;font-size:0.85rem;">View on TikTok →</a>
            </div>
        `;
        document.getElementById('stats-section').innerHTML = `<div class="stats">
            <div class="stat"><div class="stat-value">${formatNum(user.follower_count)}</div><div class="stat-label">Followers</div></div>
            <div class="stat"><div class="stat-value">${formatNum(user.following_count)}</div><div class="stat-label">Following</div></div>
            <div class="stat"><div class="stat-value">${formatNum(user.likes_count)}</div><div class="stat-label">Likes</div></div>
            <div class="stat"><div class="stat-value">${formatNum(user.video_count)}</div><div class="stat-label">Videos</div></div>
        </div>`;
    })
    .catch(e => { document.getElementById('profile-section').innerHTML = '<p style="color:#888;">Profile unavailable</p>'; });

// Load videos
fetch('?action=videos')
    .then(r => r.json())
    .then(data => {
        const videos = data?.data?.videos;
        if (!videos || videos.length === 0) { document.getElementById('video-grid').innerHTML = '<p style="color:#888;">No videos found</p>'; return; }
        document.getElementById('video-grid').innerHTML = videos.map(v => `
            <div class="video-card">
                <img src="${v.cover_image_url}" alt="${v.title || 'Video'}" loading="lazy">
                <div class="info">
                    <h4>${v.title || v.video_description || 'Untitled'}</h4>
                    <div class="meta">👁 ${formatNum(v.view_count)} · ❤️ ${formatNum(v.like_count)} · 💬 ${formatNum(v.comment_count)}</div>
                </div>
            </div>
        `).join('');
    })
    .catch(e => { document.getElementById('video-grid').innerHTML = '<p style="color:#888;">Could not load videos</p>'; });

function formatNum(n) {
    if (!n) return '0';
    if (n >= 1000000) return (n/1000000).toFixed(1) + 'M';
    if (n >= 1000) return (n/1000).toFixed(1) + 'K';
    return String(n);
}
</script>
<?php endif; ?>

</body>
</html>
