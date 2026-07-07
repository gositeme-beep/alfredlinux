<?php
/**
 * HTTP headers for Commander-only / classified UI (Veil, Dell Watch, etc.).
 * Call after session/auth and after confirming the viewer is authorized.
 * Safe to require_once multiple times.
 */
declare(strict_types=1);

if (headers_sent()) {
    return;
}

if (defined('GOSITEME_CLASSIFIED_HEADERS_APPLIED')) {
    return;
}

header('Cache-Control: no-store, no-cache, must-revalidate, private');
header('Pragma: no-cache');
header('Expires: 0');
header('Vary: Cookie');
header('Referrer-Policy: same-origin');
header('X-Content-Type-Options: nosniff');
header('X-DNS-Prefetch-Control: off');
header('X-Permitted-Cross-Domain-Policies: none');
header('X-Robots-Tag: noindex, nofollow, noai');
header('X-Frame-Options: DENY');
// Veil comms use camera/microphone on-origin; keep other powerful APIs off.
header(
    'Permissions-Policy: accelerometer=(), ambient-light-sensor=(), battery=(), browsing-topics=(), '
    . 'camera=(self), display-capture=(self), encrypted-media=(self), geolocation=(), gyroscope=(), '
    . 'magnetometer=(), microphone=(self), payment=(), publickey-credentials-get=(self), '
    . 'usb=(), web-share=()'
);

define('GOSITEME_CLASSIFIED_HEADERS_APPLIED', true);
