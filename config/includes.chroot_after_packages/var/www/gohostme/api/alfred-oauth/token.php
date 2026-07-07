<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, private');
header('Pragma: no-cache');
require_once __DIR__ . '/../../includes/path-guard.inc.php';
require_once __DIR__ . '/../../includes/db-config.inc.php';

function jout(array $p, int $s = 200): void {
    http_response_code($s);
    echo json_encode($p, gositeme_json_public_encode_flags());
    exit;
}

$grantType = $_POST['grant_type']    ?? '';
$clientId  = $_POST['client_id']     ?? '';
$clientSec = $_POST['client_secret'] ?? '';
if ($clientId === '' && !empty($_SERVER['PHP_AUTH_USER'])) {
    $clientId  = $_SERVER['PHP_AUTH_USER'];
    $clientSec = $_SERVER['PHP_AUTH_PW'] ?? '';
}

if (!in_array($grantType, ['authorization_code', 'refresh_token'], true)) {
    jout(['error' => 'unsupported_grant_type'], 400);
}

$db = getSharedDB();
$st = $db->prepare("SELECT id, client_secret FROM alfred_oauth_apps WHERE client_id = ? AND is_approved = 1 LIMIT 1");
$st->execute([$clientId]);
$app = $st->fetch(PDO::FETCH_ASSOC);
if (!$app || !hash_equals((string)$app['client_secret'], (string)$clientSec)) {
    jout(['error' => 'invalid_client'], 401);
}

if ($grantType === 'authorization_code') {
    $code        = $_POST['code']         ?? '';
    $redirectUri = $_POST['redirect_uri'] ?? '';
    if ($code === '' || $redirectUri === '') jout(['error' => 'invalid_request'], 400);

    $st = $db->prepare(
        "SELECT * FROM alfred_oauth_codes
         WHERE app_id = ? AND code = ? AND used_at IS NULL AND expires_at > NOW()
           AND redirect_uri = ?
         LIMIT 1"
    );
    $st->execute([$app['id'], $code, $redirectUri]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) jout(['error' => 'invalid_grant'], 400);

    $db->prepare("UPDATE alfred_oauth_codes SET used_at = NOW() WHERE id = ?")
       ->execute([$row['id']]);

    $access  = bin2hex(random_bytes(32));
    $refresh = bin2hex(random_bytes(32));
    $exp     = date('Y-m-d H:i:s', time() + 3600);

    $db->prepare(
        "INSERT INTO alfred_oauth_tokens (app_id, user_id, access_token, refresh_token, scopes, expires_at)
         VALUES (?, ?, ?, ?, ?, ?)"
    )->execute([$app['id'], $row['user_id'], hash('sha256', $access), hash('sha256', $refresh), $row['scopes'], $exp]);

    $st = $db->prepare("SELECT firstname, lastname, email FROM clients WHERE id = ? LIMIT 1");
    $st->execute([$row['user_id']]);
    $u = $st->fetch(PDO::FETCH_ASSOC) ?: [];

    $b64 = function ($d) { return rtrim(strtr(base64_encode($d), '+/', '-_'), '='); };
    $now = time();
    $claims = [
        'iss' => 'https://gositeme.com',
        'sub' => (string)$row['user_id'],
        'aud' => $clientId,
        'exp' => $now + 3600,
        'iat' => $now,
        'name' => trim(($u['firstname'] ?? '') . ' ' . ($u['lastname'] ?? '')),
        'email' => $u['email'] ?? '',
        'email_verified' => true,
        'preferred_username' => $u['email'] ?? ('user' . $row['user_id']),
    ];
    if (!empty($row['nonce'])) $claims['nonce'] = $row['nonce'];

    $h = $b64(json_encode(['alg' => 'HS256', 'typ' => 'JWT'], gositeme_json_public_encode_flags()));
    $p = $b64(json_encode($claims, gositeme_json_public_encode_flags()));
    $sig = $b64(hash_hmac('sha256', "$h.$p", $clientSec, true));

    jout([
        'access_token'  => $access,
        'refresh_token' => $refresh,
        'token_type'    => 'Bearer',
        'expires_in'    => 3600,
        'id_token'      => "$h.$p.$sig",
        'scope'         => 'openid profile email',
    ]);
}

if ($grantType === 'refresh_token') {
    $refresh = $_POST['refresh_token'] ?? '';
    if ($refresh === '') jout(['error' => 'invalid_request'], 400);
    $rHash = hash('sha256', $refresh);
    $st = $db->prepare(
        "SELECT * FROM alfred_oauth_tokens
         WHERE app_id = ? AND refresh_token = ? AND revoked_at IS NULL
         LIMIT 1"
    );
    $st->execute([$app['id'], $rHash]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) jout(['error' => 'invalid_grant'], 400);

    $access = bin2hex(random_bytes(32));
    $exp    = date('Y-m-d H:i:s', time() + 3600);
    $db->prepare("UPDATE alfred_oauth_tokens SET access_token = ?, expires_at = ? WHERE id = ?")
       ->execute([hash('sha256', $access), $exp, $row['id']]);
    jout(['access_token' => $access, 'token_type' => 'Bearer', 'expires_in' => 3600]);
}