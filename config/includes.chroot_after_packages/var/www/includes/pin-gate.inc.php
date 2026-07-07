<?php
/**
 * pin-gate.inc.php — require commander_vault PIN for every logged-in user.
 *
 * Behavior:
 *   - Skips if not logged in (auth-gate handles that).
 *   - Skips if user has no commander_vault row (they can set one later).
 *   - Skips if already verified this session.
 *   - Otherwise: stores return URL and redirects to /verify-pin.php.
 *
 * Required: $clientId set by auth-gate.inc.php (included BEFORE this file).
 */

require_once __DIR__ . '/auth-debug-trace.inc.php';

if (root_auth_debug_trace_enabled()) {
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '-');
    $cid = isset($clientId) ? (int) $clientId : 0;
    $pv = !empty($_SESSION['pin_verified']) ? '1' : '0';
    root_auth_debug_trace_log(date('H:i:s') . " [pin-gate] uri={$uri} cid={$cid} pin_verified={$pv}");
}

if (empty($clientId) || $clientId <= 0) {
    return;
}

// Already verified for this exact client this session
if (!empty($_SESSION['pin_verified']) && (int)($_SESSION['pin_verified_for_client'] ?? 0) === (int)$clientId) {
    return;
}

// ALSO honour vault unlock (commander-vault uses different session key)
if (!empty($_SESSION['vault_unlocked']) && $_SESSION['vault_unlocked'] === true) {
    // Self-heal the canonical key so subsequent gates short-circuit fast
    $_SESSION['pin_verified']            = true;
    $_SESSION['pin_verified_for_client'] = (int)$clientId;
    $_SESSION['pin_verified_at']         = time();
    return;
}

// Allow the verify page itself (and the auth API for sign-out)
$reqPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$bypass = ['/verify-pin.php', '/verify-pin', '/api/auth.php', '/logout', '/logout.php'];
if (in_array($reqPath, $bypass, true)) {
    return;
}

// Check if this user has a PIN configured. If not, don't block them.
if (!isset($_SESSION['pin_required_cache']) || (int)($_SESSION['pin_required_cache_for'] ?? 0) !== (int)$clientId) {
    $hasPin = false;
    try {
        if (!function_exists('getSharedDB')) {
            require_once __DIR__ . '/db-config.inc.php';
        }
        $db = getSharedDB();
        $stmt = $db->prepare("SELECT 1 FROM commander_vault WHERE client_id = ? AND pin_hash <> '' LIMIT 1");
        $stmt->execute([(int)$clientId]);
        $hasPin = (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
        error_log('[PIN-GATE] vault lookup failed: ' . $e->getMessage());
        return; // fail-open: never lock people out due to a DB hiccup
    }
    $_SESSION['pin_required_cache']     = $hasPin;
    $_SESSION['pin_required_cache_for'] = (int)$clientId;
}

if (!$_SESSION['pin_required_cache']) {
    return; // no PIN set for this user
}

// Save return URL and bounce to verify
$_SESSION['pin_return'] = $_SERVER['REQUEST_URI'] ?? '/dashboard.php';
if (!headers_sent()) {
    if (defined('IS_API') && IS_API) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo '{"error":"PIN_REQUIRED"}';
    } else {
        header('Location: /verify-pin.php');
    }
}
exit;
