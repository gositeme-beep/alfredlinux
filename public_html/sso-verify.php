<?php
/**
 * Alfred Linux — SSO receiver (GoSiteMe ecosystem).
 *
 * Called by: https://gositeme.com/api/sso-bridge.php?target=alfred
 * Receives: ?token=BASE64&redirect=/path
 */

$ssoSecret = '';
$keyFile = '/home/gositeme/keys/sso-secret.key';
if (is_readable($keyFile)) {
    $ssoSecret = trim((string) file_get_contents($keyFile));
}
if ($ssoSecret === '') {
    $ssoSecret = getenv('SSO_SECRET') ?: '';
}
if ($ssoSecret === '') {
    http_response_code(500);
    echo 'SSO not configured.';
    exit;
}

$tokenB64 = $_GET['token'] ?? '';
$redirect = $_GET['redirect'] ?? '/';

if ($tokenB64 === '') {
    http_response_code(400);
    echo 'Missing token.';
    exit;
}

$decoded = base64_decode($tokenB64, true);
if ($decoded === false) {
    http_response_code(400);
    echo 'Invalid token.';
    exit;
}

$parts = explode('|', $decoded, 6);
if (count($parts) !== 6) {
    $parts = explode('|', $decoded, 5);
    if (count($parts) === 5) {
        [$clientId, $email, $timestamp, $nonce, $signature] = $parts;
        $targetDomain = '';
        $payload = "{$clientId}|{$email}|{$timestamp}|{$nonce}";
    } else {
        http_response_code(400);
        echo 'Malformed token.';
        exit;
    }
} else {
    [$clientId, $email, $targetDomain, $timestamp, $nonce, $signature] = $parts;
    $payload = "{$clientId}|{$email}|{$targetDomain}|{$timestamp}|{$nonce}";
}

$clientId = (int) $clientId;
$timestamp = (int) $timestamp;

$expected = hash_hmac('sha256', $payload, $ssoSecret);
if (!hash_equals($expected, $signature)) {
    http_response_code(403);
    echo 'Invalid signature.';
    exit;
}

if ($targetDomain !== '' && $targetDomain !== 'alfredlinux.com') {
    http_response_code(403);
    echo 'Token not issued for this domain.';
    exit;
}

if ((time() - $timestamp) > 60) {
    http_response_code(403);
    echo 'Token expired.';
    exit;
}

require_once '/home/gositeme/domains/gositeme.com/public_html/includes/db-config.inc.php';
$pdo = getSharedDB();
$stmt = $pdo->prepare('SELECT id FROM sso_tokens WHERE nonce = ? AND client_id = ? AND expires_at > NOW() AND used = 0 AND used_at IS NULL LIMIT 1');
$stmt->execute([$nonce, $clientId]);
$tokenRow = $stmt->fetch();

if (!$tokenRow) {
    http_response_code(403);
    echo 'Token already used or not found.';
    exit;
}

$pdo->prepare('UPDATE sso_tokens SET used = 1, used_at = NOW() WHERE id = ?')->execute([$tokenRow['id']]);

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
$_SESSION['client_email'] = $email;
$_SESSION['login_time'] = time();
$_SESSION['sso_source'] = 'gositeme';

if (!preg_match('#^/[a-zA-Z0-9._/-]*#', $redirect)) {
    $redirect = '/';
}

header('Location: ' . $redirect);
exit;
