<?php
/**
 * GoSiteMe — Universal Auth Gate
 *
 * Required by all logged-in pages (dashboard.php, alfred-ide-dashboard.php,
 * supreme-admin, military-hq, finance-dashboard, agent-*, analytics, arsenal,
 * etc). Verifies a valid PHP session, otherwise redirects the visitor to /login
 * with a return URL.
 *
 * Exposes (after include):
 *   $clientId     int  — current logged-in client id (0 if none)
 *   $is_logged_in bool — true if session indicates a logged-in user
 *   $is_owner     bool — true only for the Commander account (client_id = 33)
 */

require_once __DIR__ . '/auth-debug-trace.inc.php';

if (PHP_SAPI !== 'cli') {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', '1');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_samesite', 'Lax');
        session_start();
// === LOCKDOWN_INJECT ===
if (file_exists(__DIR__."/lockdown.inc.php")) {
    require_once __DIR__."/lockdown.inc.php";
    if (defined("LOCKDOWN_ENABLED") && LOCKDOWN_ENABLED) {
        $__cid = (int)($_SESSION["client_id"] ?? $_SESSION["uid"] ?? 0);
        if ($__cid > 0 && !in_array($__cid, LOCKDOWN_ALLOW, true)) {
            // boot them
            $_SESSION = [];
            if (ini_get("session.use_cookies")) { $p=session_get_cookie_params(); setcookie(session_name(),"",time()-42000,$p["path"],$p["domain"],$p["secure"],$p["httponly"]); }
            session_destroy();
            lockdown_block_response();
        }
    }
}
// === /LOCKDOWN_INJECT ===
        if (root_auth_debug_trace_enabled()) {
            $uri = (string) ($_SERVER['REQUEST_URI'] ?? '-');
            $sid = session_id();
            $cid = (int) ($_SESSION['client_id'] ?? $_SESSION['uid'] ?? 0);
            root_auth_debug_trace_log(
                date('H:i:s') . ' [gate-enter] uri=' . $uri . ' sid_prefix=' . substr($sid, 0, 8) . ' cid=' . $cid
            );
        }
        if (root_session_backup_enabled()) {
            root_session_backup_gate_snapshot();
        }

    }
}

$clientId     = (int)($_SESSION['client_id'] ?? $_SESSION['uid'] ?? 0);
$is_logged_in = !empty($_SESSION['logged_in']) && $clientId > 0;
$is_owner     = $is_logged_in && $clientId === 33;

if (!$is_logged_in) {
    if (PHP_SAPI === 'cli') {
        return; // CLI scripts (cron) bypass redirect
    }

    // Build return URL
    $reqUri = $_SERVER['REQUEST_URI'] ?? '/';
    // Strip any pre-existing redirect param to avoid loops
    $reqUri = preg_replace('#[?&]redirect=[^&]*#', '', $reqUri);
    $reqUri = preg_replace('#[?&]return=[^&]*#', '', $reqUri);

    // Don't loop back to /login itself
    if (strpos($reqUri, '/login') === 0 || strpos($reqUri, '/pay/login') === 0) {
        $reqUri = '/dashboard.php';
    }

    if (!headers_sent()) {
        if (defined('IS_API') && IS_API) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo '{"error":"UNAUTHORIZED"}';
        } else {
            header('Location: /login?redirect=' . rawurlencode($reqUri));
        }
    }
    exit;
}

// Admin email-PIN gate (only applies to client_id = 33; everyone else is unaffected)
require_once __DIR__ . '/pin-gate.inc.php';
