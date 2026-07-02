<?php
require_once __DIR__ . '/../../includes/db-config.inc.php';
session_start();

$clientId    = $_GET['client_id']     ?? '';
$redirectUri = $_GET['redirect_uri']  ?? '';
$responseType= $_GET['response_type'] ?? '';
$state       = $_GET['state']         ?? '';
$scope       = $_GET['scope']         ?? 'openid';
$nonce       = $_GET['nonce']         ?? '';

if ($responseType !== 'code')                              { http_response_code(400); echo 'unsupported_response_type'; exit; }
if ($clientId === '' || $redirectUri === '' || $state==='') { http_response_code(400); echo 'invalid_request'; exit; }

$db = getSharedDB();
$st = $db->prepare("SELECT id, redirect_uris FROM alfred_oauth_apps WHERE client_id = ? AND is_approved = 1 LIMIT 1");
$st->execute([$clientId]);
$app = $st->fetch(PDO::FETCH_ASSOC);
if (!$app) { http_response_code(400); echo 'unauthorized_client'; exit; }

$allowed = json_decode($app['redirect_uris'], true) ?: [];
if (!in_array($redirectUri, $allowed, true)) { http_response_code(400); echo 'invalid_redirect_uri'; exit; }

if (empty($_SESSION['client_id'])) {
    $self = '/api/alfred-oauth/authorize.php?' . http_build_query($_GET);
    header('Location: /login.php?return=' . urlencode($self));
    exit;
}

$userId    = (int)$_SESSION['client_id'];
$code      = bin2hex(random_bytes(32));
$expiresAt = date('Y-m-d H:i:s', time() + 600);
$scopesJson= json_encode(array_values(array_filter(preg_split('/\s+/', trim($scope)))));

$db->prepare("DELETE FROM alfred_oauth_codes WHERE app_id = ? AND user_id = ? AND used_at IS NULL")
   ->execute([$app['id'], $userId]);
$db->prepare(
    "INSERT INTO alfred_oauth_codes (app_id, user_id, code, redirect_uri, scopes, expires_at, nonce)
     VALUES (?, ?, ?, ?, ?, ?, ?)"
)->execute([$app['id'], $userId, $code, $redirectUri, $scopesJson, $expiresAt, $nonce ?: null]);

$sep = (strpos($redirectUri, '?') === false) ? '?' : '&';
header('Location: ' . $redirectUri . $sep . http_build_query(['code' => $code, 'state' => $state]));
exit;