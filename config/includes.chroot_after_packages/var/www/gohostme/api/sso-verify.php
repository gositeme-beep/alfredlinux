<?php
/**
 * GoSiteMe SSO Token Verifier
 * 
 * Receives a signed SSO token (from LaVocat or any trusted bridge),
 * verifies it, and creates a GoSiteMe session.
 */

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
    echo 'SSO not configured.';
    exit;
}

$tokenB64 = $_GET['token'] ?? '';
$redirect = $_GET['redirect'] ?? '/dashboard.php';

if (!$tokenB64) {
    http_response_code(400);
    echo 'Missing token.';
    exit;
}

$decoded = base64_decode($tokenB64, true);
if (!$decoded) {
    http_response_code(400);
    echo 'Invalid token.';
    exit;
}

$parts = explode('|', $decoded);
if (count($parts) === 6) {
    [$clientId, $email, $targetDomain, $timestamp, $nonce, $signature] = $parts;
    $payload = "{$clientId}|{$email}|{$targetDomain}|{$timestamp}|{$nonce}";
    if ($targetDomain && $targetDomain !== 'gositeme.com') {
        http_response_code(403);
        echo 'Token not issued for this domain.';
        exit;
    }
} elseif (count($parts) === 5) {
    [$clientId, $email, $timestamp, $nonce, $signature] = $parts;
    $payload = "{$clientId}|{$email}|{$timestamp}|{$nonce}";
} else {
    http_response_code(400);
    echo 'Malformed token.';
    exit;
}

$clientId = (int)$clientId;
$timestamp = (int)$timestamp;
$expected = hash_hmac('sha256', $payload, $ssoSecret);
if (!hash_equals($expected, $signature)) {
    http_response_code(403);
    echo 'Invalid signature.';
    exit;
}

// Verify expiry (60 seconds)
if ((time() - $timestamp) > 60) {
    http_response_code(403);
    echo 'Token expired.';
    exit;
}

// Verify single-use (nonce)
require_once dirname(__DIR__) . '/includes/db-config.inc.php';
$pdo = getSharedDB();
$stmt = $pdo->prepare("SELECT id FROM sso_tokens WHERE nonce = ? AND client_id = ? AND expires_at > NOW() AND used = 0 AND used_at IS NULL LIMIT 1");
$stmt->execute([$nonce, $clientId]);
$tokenRow = $stmt->fetch();

if (!$tokenRow) {
    http_response_code(403);
    echo 'Token already used or not found.';
    exit;
}

// Mark token as used
$pdo->prepare("UPDATE sso_tokens SET used = 1, used_at = NOW() WHERE id = ?")->execute([$tokenRow['id']]);

// Create GoSiteMe session
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

$_SESSION['client_id'] = $clientId;
$_SESSION['uid'] = $clientId;
$_SESSION['logged_in'] = true;
$_SESSION['email'] = $email;
$_SESSION['login_time'] = time();
$_SESSION['sso_source'] = 'lavocat';

// Sanitize redirect — must be a local path
if (!preg_match('#^/[a-zA-Z0-9._/-]*#', $redirect)) {
    $redirect = '/dashboard.php';
}

header('Location: ' . $redirect);
exit;
