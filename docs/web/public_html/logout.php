<?php
/**
 * Alfred Linux — clear local SSO session and return home.
 */
require_once __DIR__ . '/includes/al-session.inc.php';

$_SESSION = [];
$p = session_get_cookie_params();
setcookie(session_name(), '', time() - 86400, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
session_destroy();

$next = $_GET['redirect'] ?? '/';
if (!is_string($next) || $next === '' || $next[0] !== '/' || strpos($next, '//') === 0) {
    $next = '/';
}

header('Location: ' . $next, true, 302);
exit;
