<?php
/**
 * Alfred IDE — One-time Commander Session Init
 * Visit this page once to set the auth cookie, then it self-destructs.
 */
session_start();
require_once __DIR__ . '/includes/db-config.inc.php';

$db = getSharedDB();
$stmt = $db->prepare("SELECT id, google_name FROM alfred_ide_users WHERE client_id = 33 LIMIT 1");
$stmt->execute();
$user = $stmt->fetch();

if (!$user) { die('Commander not found in alfred_ide_users.'); }

$token = bin2hex(random_bytes(32));
$tokenHash = hash('sha256', $token);
$expires = date('Y-m-d H:i:s', time() + 86400 * 30);

$db->prepare("UPDATE alfred_ide_users SET session_token = ?, token_expires = ?, last_login = NOW() WHERE id = ?")
   ->execute([$tokenHash, $expires, $user['id']]);

$_SESSION['ide_user_id'] = (int)$user['id'];
$_SESSION['ide_authenticated'] = true;
$_SESSION['ide_session_token'] = $token;
$_SESSION['ide_google_name'] = $user['google_name'];

setcookie('alfred_ide_token', $token, [
    'expires' => time() + 86400 * 30,
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);

header('Location: /alfred-ide/');
exit;
