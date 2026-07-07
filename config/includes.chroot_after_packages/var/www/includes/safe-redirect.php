<?php
/**
 * Post-login redirect targets: same-origin relative paths only.
 * Blocks open redirects, protocol-relative URLs, and obvious traversal tricks.
 */
function root_safe_post_login_path(string $raw, string $default): string {
    $p = trim($raw);
    if ($p === '' || strpos($p, '/') !== 0 || strpos($p, '//') === 0) {
        return $default;
    }
    if (preg_match('/[\x00-\x1f\\\\]/', $p)) {
        return $default;
    }
    $pathOnly = explode('?', $p, 2)[0];
    if (str_contains($pathOnly, ':') || str_contains($pathOnly, '..')) {
        return $default;
    }
    return $p;
}
