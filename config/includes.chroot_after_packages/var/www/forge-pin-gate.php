<?php
/**
 * /forge-pin-gate.php on root.com
 *
 * Called from alfredlinux.com when a user requests /forge/* without a valid
 * forge_pin_ok cookie. We:
 *   1. Require root.com login (login.php handles that).
 *   2. Require the user's vault PIN to be verified this session
 *      (pin-gate.inc.php handles that automatically since auth-gate runs first).
 *   3. Mint an HMAC token and bounce back to alfredlinux.com/forge-pin-set.php
 *      which sets the local cookie and forwards to the original URL.
 *
 * Query: ?to=<absolute alfredlinux.com URL under /forge/>
 */
require_once __DIR__ . '/includes/auth-gate.inc.php';   // forces root login + sets $clientId

$to = $_GET['to'] ?? 'https://alfredlinux.com/forge/';
if (!preg_match('~^https://alfredlinux\.com/forge(/|$|\?)~', $to)) {
    $to = 'https://alfredlinux.com/forge/';
}

// Tell vault-unlock to come back HERE (not /dashboard.php) once PIN is entered.
// Must be set BEFORE pin-gate.inc.php triggers the redirect to /security-unlock.php.
$selfReturn = 'https://root.com/forge-pin-gate.php?to=' . urlencode($to);
$_SESSION['vault_return_url'] = $selfReturn;
$_SESSION['pin_return']       = $selfReturn;

require_once __DIR__ . '/includes/pin-gate.inc.php';    // forces PIN if user has one

$secretFile = '/home/root/.forge_gate_secret';
if (!is_readable($secretFile)) {
    http_response_code(500);
    echo 'forge gate secret missing';
    exit;
}
$secret = trim((string)@file_get_contents($secretFile));
if ($secret === '') { http_response_code(500); echo 'forge gate secret empty'; exit; }

$clientId = (int)($_SESSION['client_id'] ?? 0);
if ($clientId <= 0) { http_response_code(403); echo 'no session'; exit; }

$exp     = time() + 60;                 // token valid 60s — single bounce only
$payload = $clientId . ':' . $exp;
$sig     = hash_hmac('sha256', $payload, $secret);
$token   = $payload . ':' . $sig;

$sep = (strpos($to, '?') === false) ? '?' : '&';
$dest = 'https://alfredlinux.com/forge-pin-set.php?token=' . urlencode($token)
      . '&to=' . urlencode($to);

header('Location: ' . $dest, true, 302);
exit;
