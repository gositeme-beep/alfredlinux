<?php
/**
 * Alfred AI — Input Validation Utilities
 * Common validation/sanitization functions for forms and API input.
 *
 * Each validator returns the cleaned value on success or false on failure.
 */

// Prevent double-include
if (defined('ALFRED_INPUT_VALIDATOR_LOADED')) return;
define('ALFRED_INPUT_VALIDATOR_LOADED', true);

/**
 * Validate and sanitize an email address.
 * @return string|false  Lowercase trimmed email or false
 */
function validateEmail(string $email) {
    $email = trim(strtolower($email));
    $clean = filter_var($email, FILTER_VALIDATE_EMAIL);
    return $clean !== false ? $clean : false;
}

/**
 * Validate a URL. Optionally require HTTPS.
 * @return string|false  Sanitized URL or false
 */
function validateUrl(string $url, bool $requireHttps = true) {
    $url = trim($url);
    $clean = filter_var($url, FILTER_VALIDATE_URL);
    if ($clean === false) return false;
    if ($requireHttps && stripos($clean, 'https://') !== 0) return false;
    return $clean;
}

/**
 * Validate a phone number (E.164-ish: digits, optional leading +, 7–15 digits).
 * @return string|false  Digits-only (with optional +) or false
 */
function validatePhone(string $phone) {
    $phone = trim($phone);
    // Strip common formatting
    $stripped = preg_replace('/[\s\-\(\)\.]+/', '', $phone);
    if (preg_match('/^\+?\d{7,15}$/', $stripped)) {
        return $stripped;
    }
    return false;
}

/**
 * Validate a URL slug (lowercase alphanumeric + hyphens, 1-128 chars).
 * @return string|false
 */
function validateSlug(string $slug) {
    $slug = trim($slug);
    if (preg_match('/^[a-z0-9]([a-z0-9\-]{0,126}[a-z0-9])?$/', $slug)) {
        return $slug;
    }
    return false;
}

/**
 * Validate a JSON string and decode it.
 * @return array|false  Decoded associative array or false
 */
function validateJson(string $json) {
    $json = trim($json);
    if ($json === '') return false;
    $decoded = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) return false;
    return $decoded;
}

/**
 * Validate a date range (start < end, both valid ISO dates).
 * @return array|false  ['start' => DateTimeImmutable, 'end' => DateTimeImmutable] or false
 */
function validateDateRange(string $start, string $end) {
    try {
        $s = new DateTimeImmutable($start);
        $e = new DateTimeImmutable($end);
    } catch (\Exception $ex) {
        return false;
    }
    if ($s >= $e) return false;
    return ['start' => $s, 'end' => $e];
}

/**
 * Validate and clamp pagination parameters.
 * @return array ['page' => int, 'per_page' => int, 'offset' => int]
 */
function validatePagination($page, $perPage, int $maxPerPage = 100): array {
    $page    = max(1, (int) $page);
    $perPage = max(1, min($maxPerPage, (int) $perPage));
    return [
        'page'     => $page,
        'per_page' => $perPage,
        'offset'   => ($page - 1) * $perPage,
    ];
}

/**
 * Strip tags and encode special characters for safe HTML output.
 */
function sanitizeHtml(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Validate password strength.
 * @return array ['valid' => bool, 'errors' => string[]]
 */
function validatePassword(string $password): array {
    $errors = [];

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if (strlen($password) > 128) {
        $errors[] = 'Password must not exceed 128 characters.';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter.';
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter.';
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one digit.';
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = 'Password must contain at least one special character.';
    }

    return [
        'valid'  => empty($errors),
        'errors' => $errors,
    ];
}
