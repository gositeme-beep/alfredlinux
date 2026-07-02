<?php
/**
 * GoSiteMe Universal SSO Bridge
 * 
 * Generates a signed, single-use, time-limited SSO token and redirects
 * the user to any trusted domain where they are auto-signed in.
 * 
 * Usage:
 *   /api/sso-bridge.php?target=lavocat         → lavocat.ca
 *   /api/sso-bridge.php?target=metadome         → meta-dome.com
 *   /api/sso-bridge.php?target=lavocat&redirect=/journal.php
 * 
 * Security:
 *   - HMAC-SHA256 signed with shared SSO secret
 *   - 60-second expiry
 *   - Single-use (nonce consumed on verification)
 *   - Only for authenticated GoSiteMe users
 *   - Strict target whitelist — no open redirect
 */

// Trusted targets — the ONLY domains we will SSO to
$TRUSTED_TARGETS = [
    'lavocat'   => ['domain' => 'lavocat.ca',    'endpoint' => '/auth.php?action=sso'],
    'metadome'  => ['domain' => 'meta-dome.com',  'endpoint' => '/sso-verify.php'],
    'alfred'    => ['domain' => 'alfredlinux.com', 'endpoint' => '/sso-verify.php'],
];

$target = $_GET['target'] ?? '';
$redirect = $_GET['redirect'] ?? '/';

if (!$target || !isset($TRUSTED_TARGETS[$target])) {
    http_response_code(400);
    echo 'Unknown target. Valid: ' . implode(', ', array_keys($TRUSTED_TARGETS));
    exit;
}

$targetInfo = $TRUSTED_TARGETS[$target];

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
    header('Location: /login.php?return=' . urlencode('/api/sso-bridge.php?' . http_build_query($_GET)));
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

// Look up email if missing from session
if (!$email && $clientId) {
    require_once dirname(__DIR__) . '/includes/db-config.inc.php';
    $pdo = getSharedDB();
    $stmt = $pdo->prepare("SELECT email FROM tblclients WHERE id = ? LIMIT 1");
    $stmt->execute([$clientId]);
    $email = $stmt->fetchColumn() ?: '';
}

// Build SSO token: client_id|email|target_domain|timestamp|nonce
$timestamp = time();
$nonce = bin2hex(random_bytes(16));
$targetDomain = $targetInfo['domain'];
$payload = "{$clientId}|{$email}|{$targetDomain}|{$timestamp}|{$nonce}";
$signature = hash_hmac('sha256', $payload, $ssoSecret);
$token = base64_encode("{$payload}|{$signature}");

// Store nonce in DB to prevent replay
require_once dirname(__DIR__) . '/includes/db-config.inc.php';
$pdo = getSharedDB();
$pdo->prepare("INSERT INTO sso_tokens (token, nonce, client_id, email, name, expires_at, created_at, used, used_at) VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 60 SECOND), NOW(), 0, NULL)")
    ->execute([$nonce, $nonce, $clientId, $email, "gsm-to-{$target}"]);

// Build target URL
$targetUrl = "https://{$targetDomain}{$targetInfo['endpoint']}"
    . (strpos($targetInfo['endpoint'], '?') !== false ? '&' : '?')
    . 'token=' . urlencode($token)
    . '&redirect=' . urlencode($redirect);

header('Location: ' . $targetUrl);
exit;
