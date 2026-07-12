<?php
/**
 * GoSiteMe — Logout Handler
 * Destroys session and redirects to login page.
 * Supports ?emergency=1 for stealth sign-out (redirects to Google).
 */
session_start();

$ideClientId = $_SESSION['client_id'] ?? null;

unset(
    $_SESSION['vault_unlocked'],
    $_SESSION['vault_unlock_time'],
    $_SESSION['ide_gate_token'],
    $_SESSION['ide_gate_expires'],
    $_SESSION['2fa_pending_client_id'],
    $_SESSION['2fa_pending_email'],
    $_SESSION['2fa_pending_time'],
    $_SESSION['2fa_pending_oauth'],
    $_SESSION['2fa_pending_provider'],
    $_SESSION['2fa_pending_provider_id'],
    $_SESSION['oauth_provider'],
    $_SESSION['oauth_redirect']
);

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 86400, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}

if ($ideClientId) {
    try {
        require_once __DIR__ . '/includes/db-config.inc.php';
        $db = getSharedDB();
        $db->prepare("UPDATE alfred_ide_users SET session_token = NULL, token_expires = NULL WHERE client_id = ?")
           ->execute([$ideClientId]);
    } catch (Exception $e) {
        // Non-fatal during logout.
    }
}

setcookie('alfred_ide_token', '', ['expires' => time() - 86400, 'path' => '/alfred-ide/', 'secure' => true, 'httponly' => true, 'samesite' => 'Lax']);
setcookie('alfred_ide_token', '', ['expires' => time() - 86400, 'path' => '/alfred-ide-auth.php', 'secure' => true, 'httponly' => true, 'samesite' => 'Lax']);
setcookie('alfred_ide_token', '', ['expires' => time() - 86400, 'path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'Lax']);

session_destroy();

if (isset($_GET['emergency'])) {
    // Stealth exit: replace the current page with a neutral blank tab.
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>New Tab</title></head><body style="margin:0;background:#fff"><script>try{localStorage.setItem("gositeme:logout", String(Date.now()));}catch(e){}try{history.replaceState(null,"","/");}catch(e){}document.title="New Tab";window.location.replace("about:blank");</script></body></html>';
    exit;
}
echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta http-equiv="refresh" content="0;url=/login.php?logged_out=1"><title>Signing Out</title></head><body><script>try{localStorage.setItem("gositeme:logout", String(Date.now()));}catch(e){}window.location.replace("/login.php?logged_out=1");</script></body></html>';
exit;
