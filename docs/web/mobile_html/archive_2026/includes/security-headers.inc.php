<?php
/**
 * Global Security Headers — included by site-header.inc.php
 * Sets security headers for all pages across the platform.
 */

// Only set headers if not already sent
if (!headers_sent()) {
    // HSTS — enforce HTTPS for 1 year, include subdomains
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');

    // Prevent MIME-type sniffing
    header('X-Content-Type-Options: nosniff');

    // Clickjacking protection — allow same-origin framing only
    header('X-Frame-Options: SAMEORIGIN');

    // Control referrer information
    header('Referrer-Policy: strict-origin-when-cross-origin');

    // Restrict browser features
    header('Permissions-Policy: camera=(self), microphone=(self), geolocation=(), payment=(), usb=()');

    // XSS protection (legacy browsers)
    header('X-XSS-Protection: 1; mode=block');
}
