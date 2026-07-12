<?php
/**
 * /forge-pin-set.php on alfredlinux.com
 *
 * Receives an HMAC token from gositeme.com/forge-pin-gate.php, validates it,
 * sets `forge_pin_ok=1` cookie, and 302s to the requested forge URL.
 */

$secretFile = '/home/gositeme/.forge_gate_secret';
if (!is_readable($secretFile)) { http_response_code(500); echo 'gate secret missing'; exit; }
$secret = trim((string)@file_get_contents($secretFile));
if ($secret === '') { http_response_code(500); echo 'gate secret empty'; exit; }

$token = (string)($_GET['token'] ?? '');
$to    = (string)($_GET['to']    ?? 'https://alfredlinux.com/forge/');

// Only allow returning to forge on this host.
if (!preg_match('~^https://alfredlinux\.com/forge(/|$|\?)~', $to)) {
    $to = 'https://alfredlinux.com/forge/';
}

$parts = explode(':', $token);
if (count($parts) !== 3) { http_response_code(400); echo 'bad token'; exit; }
[$cid, $exp, $sig] = $parts;

if (!ctype_digit($cid) || !ctype_digit($exp)) { http_response_code(400); echo 'bad token'; exit; }
if ((int)$exp < time()) { http_response_code(400); echo 'token expired'; exit; }

$expect = hash_hmac('sha256', $cid . ':' . $exp, $secret);
if (!hash_equals($expect, $sig)) { http_response_code(403); echo 'bad signature'; exit; }

setcookie('forge_pin_ok', '1', [
    'expires'  => time() + 900,           // 1 hour
    'path'     => '/',
    'domain'   => '.alfredlinux.com',
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Lax',
]);

header('Location: ' . $to, true, 302);
exit;
