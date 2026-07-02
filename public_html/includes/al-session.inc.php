<?php
/**
 * Alfred Linux — optional PHP session (set after GoSiteMe SSO via sso-verify.php).
 */
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}
$al_user_logged_in = !empty($_SESSION['logged_in']) && !empty($_SESSION['client_id']);
