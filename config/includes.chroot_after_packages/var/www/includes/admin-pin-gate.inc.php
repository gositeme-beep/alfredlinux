<?php
/**
 * Admin PIN Gate — emails a 6-digit code to the admin (client_id=33)
 * after successful password login. Only this account is gated.
 *
 * Storage: $_SESSION['admin_pin_hash'] / ['admin_pin_expires'] / ['admin_pin_attempts']
 *          $_SESSION['admin_pin_verified'] = true on success
 *
 * Required: $clientId from auth-gate.inc.php (this file is included AFTER auth-gate.inc.php)
 */

const ADMIN_PIN_CLIENT_ID  = 33;
const ADMIN_PIN_EMAIL      = 'root@gmail.com';
const ADMIN_PIN_TTL_SEC    = 600;   // 10 minutes
const ADMIN_PIN_MAX_TRIES  = 5;

if (!isset($clientId)) {
    return; // require auth-gate first
}

if ($clientId !== ADMIN_PIN_CLIENT_ID) {
    return; // non-admin: PIN gate does not apply
}

if (!empty($_SESSION['admin_pin_verified']) && (int)$_SESSION['admin_pin_verified_for_client'] === $clientId) {
    return; // already verified this session
}

// Don't gate the verify-pin page itself
$reqPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if ($reqPath === '/verify-pin.php' || $reqPath === '/verify-pin') {
    return;
}

// Build redirect-after-pin
$returnTo = $_SERVER['REQUEST_URI'] ?? '/dashboard.php';
$_SESSION['admin_pin_return'] = $returnTo;

if (!headers_sent()) {
    header('Location: /verify-pin.php');
}
exit;
