<?php
// downloads/iso.php — Server-side ISO gate (Ring 1 enforcement)
// Streams the ISO ONLY if covenant accepted in this session AND token matches.

declare(strict_types=1);
session_start();

require_once __DIR__ . '/../includes/ga-release-state.php';

$isoPath = __DIR__ . '/' . $gaIsoBasename . '.iso';
$isoName = $gaIsoBasename . '.iso';
const TOKEN_TTL = 3600;

function deny(string $msg, int $code = 403): never {
    http_response_code($code);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset=utf-8><title>Covenant required</title>';
    echo '<body style="font-family:Georgia,serif;max-width:600px;margin:4em auto;'
       . 'background:#0d0d12;color:#e8e2c8;padding:2em;line-height:1.6">';
    echo '<h1 style="color:#e8c97a">&#10010; Covenant Required &#10010;</h1>';
    echo '<p>' . htmlspecialchars($msg) . '</p>';
    echo '<p><a style="color:#c69b3a" href="/covenant?next=%2Fdownload">&rarr; Read &amp; accept the covenant</a> (then use the covenant-sealed path from /download if offered).</p>';
    echo '</body>';
    exit;
}

if (empty($_SESSION['akjv_accepted']) || empty($_SESSION['akjv_token'])) {
    deny('You must accept the covenant before downloading Alfred Linux.');
}

if (empty($_SESSION['akjv_at']) || (time() - (int)$_SESSION['akjv_at']) > TOKEN_TTL) {
    deny('Covenant token has expired (1 hour). Please re-accept.');
}

$qtok = $_GET['t'] ?? '';
if ($qtok !== '' && !hash_equals((string)$_SESSION['akjv_token'], (string)$qtok)) {
    deny('Token mismatch. Please re-accept the covenant.');
}

$iso = is_readable($isoPath) ? $isoPath : null;
if ($iso === null) {
    http_response_code(503);
    echo "ISO not yet available. Check back shortly.";
    exit;
}

$size = filesize($iso);
header('Content-Type: application/x-iso9660-image');
header('Content-Disposition: attachment; filename="' . $isoName . '"');
header('Content-Length: ' . $size);
header('X-Covenant-Sealed: yes');
header('Cache-Control: no-store');

if (function_exists('apache_get_modules') && in_array('mod_xsendfile', apache_get_modules(), true)) {
    header('X-Sendfile: ' . $iso);
    exit;
}

@set_time_limit(0);
$fp = fopen($iso, 'rb');
if ($fp === false) { http_response_code(500); exit; }
while (!feof($fp)) { echo fread($fp, 1 << 20); @ob_flush(); flush(); }
fclose($fp);
