<?php
/**
 * GoSiteMe → LaVocat SSO Bridge
 * 
 * Generates a signed, single-use, time-limited SSO token and redirects
 * the user to LaVocat where they are auto-signed in.
 * 
 * Usage: https://gositeme.com/api/sso-lavocat.php
 *        https://gositeme.com/api/sso-lavocat.php?redirect=/dashboard.php
 * 
 * Security:
 *   - HMAC-SHA256 signed with shared SSO secret
 *   - 60-second expiry
 *   - Single-use (consumed on verification)
 *   - Only for authenticated GoSiteMe users
 */

// Start session to check GoSiteMe auth
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', '1');
    ini_set('session.use_strict_mode', '1');
    session_start();
}

// Must be logged in to GoSiteMe
$clientId = (int)($_SESSION['client_id'] ?? $_SESSION['uid'] ?? 0);
$email = $_SESSION['email'] ?? '';
$isLoggedIn = !empty($_SESSION['logged_in']) || $clientId > 0;

if (!$isLoggedIn || !$clientId) {
    // Not logged in — send to GoSiteMe login with return to this SSO bridge
    header('Location: /login.php?return=' . urlencode('/api/sso-lavocat.php?' . http_build_query($_GET)));
    exit;
}

// Load SSO secret
$ssoSecret = '';
$keyFile = '/run/user/1004/keys/sso-secret.key';
if (is_readable($keyFile)) {
    $ssoSecret = trim(file_get_contents($keyFile));
}
if (!$ssoSecret) {
    $ssoSecret = getenv('SSO_SECRET') ?: '';
}
if (!$ssoSecret) {
    http_response_code(500);
    echo 'SSO not configured. Contact administrator.';
    exit;
}

// If no email in session, look it up
if (!$email && $clientId) {
    require_once dirname(__DIR__) . '/includes/db-config.inc.php';
    $pdo = getSharedDB();
    $stmt = $pdo->prepare("SELECT email FROM tblclients WHERE id = ? LIMIT 1");
    $stmt->execute([$clientId]);
    $email = $stmt->fetchColumn() ?: '';
}

// Build SSO token: client_id|email|timestamp|nonce
$timestamp = time();
$nonce = bin2hex(random_bytes(16));
$payload = "{$clientId}|{$email}|{$timestamp}|{$nonce}";
$signature = hash_hmac('sha256', $payload, $ssoSecret);
$token = base64_encode("{$payload}|{$signature}");

// Store nonce in DB to prevent replay (single-use)
require_once dirname(__DIR__) . '/includes/db-config.inc.php';
$pdo = getSharedDB();
$pdo->prepare("INSERT INTO sso_tokens (token, nonce, client_id, email, name, expires_at, created_at, used, used_at) VALUES (?, ?, ?, ?, 'gsm-to-lavocat', DATE_ADD(NOW(), INTERVAL 60 SECOND), NOW(), 0, NULL)")
    ->execute([$nonce, $nonce, $clientId, $email]);

// Redirect to LaVocat
$redirect = $_GET['redirect'] ?? '/dashboard.php';
$lavocat_url = 'https://lavocat.ca/auth.php?action=sso&token=' . urlencode($token) . '&redirect=' . urlencode($redirect);

header('Location: ' . $lavocat_url);
exit;
