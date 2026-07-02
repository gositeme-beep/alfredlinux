<?php
/**
 * Shared validation for absolute filesystem directory paths used for local writes.
 * Include early from auth, api-security, or other bootstrap code.
 */
declare(strict_types=1);

if (defined('GOSITEME_PATH_GUARD_INC')) {
    return;
}
define('GOSITEME_PATH_GUARD_INC', true);

/**
 * Flags for JSON sent to clients (UTF-8 substitute where supported).
 * Safe to use from lockdown / error-handler without loading api-response.
 */
function root_json_public_encode_flags(): int
{
    $f = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
        $f |= JSON_INVALID_UTF8_SUBSTITUTE;
    }

    return $f;
}

/**
 * @return non-empty-string|null Normalized path without trailing slash, or null if unsafe / unusable
 */
function root_safe_absolute_dir(?string $raw): ?string
{
    if (!is_string($raw)) {
        return null;
    }
    $p = trim($raw);
    if ($p === '' || strpos($p, "\0") !== false || str_contains($p, '..')) {
        return null;
    }
    if (!isset($p[0]) || $p[0] !== '/') {
        return null;
    }
    if (preg_match('#//|\./#', $p)) {
        return null;
    }

    return rtrim($p, '/');
}
