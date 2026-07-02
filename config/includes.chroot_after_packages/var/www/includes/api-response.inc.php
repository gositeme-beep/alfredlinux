<?php
/**
 * GoSiteMe Standardized API Response Helpers
 * ────────────────────────────────────────────
 * Consistent JSON response format for all API endpoints.
 * Auto-loaded via api-security.php — available in every API file.
 *
 * Standard format:
 *   { "success": true,  "data": {...}, "meta": {...}, "request_id": "…" }
 *   { "success": false, "error": "message", "code": "ERROR_CODE", "request_id": "…" }
 *
 * Includes automatic secret redaction via AlfredRedactor on all outgoing JSON.
 *
 * @since v14.0
 * @since v15.0 — Integrated Omahon Secret Redaction
 */

if (defined('GOSITEME_API_RESPONSE_INC')) {
    return;
}
define('GOSITEME_API_RESPONSE_INC', true);

require_once __DIR__ . '/path-guard.inc.php';

// ── Lazy-loaded singleton redactor ──────────────────────────────────
function _getRedactor(): ?AlfredRedactor {
    static $redactor = null;
    static $tried = false;
    if (!$tried) {
        $tried = true;
        $redactFile = '/home/root/alfred-services/redact.php';
        if (file_exists($redactFile)) {
            require_once $redactFile;
            $redactor = new AlfredRedactor();
            $redactor->loadFromVault();
        }
    }
    return $redactor;
}

/**
 * Scrub secrets from a JSON string before sending to the client.
 */
function _redactOutput(string $json): string {
    $r = _getRedactor();
    return $r ? $r->redact($json) : $json;
}

/** @return int-mask-of<JSON_*> */
function _api_json_encode_flags(): int
{
    return root_json_public_encode_flags();
}

/** Public alias for JSON flags (api-security error payloads, etc.). */
function root_api_json_flags(): int
{
    return _api_json_encode_flags();
}

/**
 * Max bytes read for application/json bodies (override: GOSITEME_API_MAX_JSON_BYTES, cap 16MiB).
 */
function api_max_json_body_bytes(): int
{
    $v = (int) (getenv('GOSITEME_API_MAX_JSON_BYTES') ?: 0);
    if ($v <= 0) {
        return 2 * 1024 * 1024;
    }

    return min($v, 16 * 1024 * 1024);
}

/**
 * Read JSON body from php://input with a hard size cap (memory / slowloris guard).
 *
 * @return array Decoded object/array; empty array if body is empty or invalid JSON object
 */
function api_read_json_body_limited(): array
{
    $max = api_max_json_body_bytes();
    $raw = '';
    $fh = @fopen('php://input', 'rb');
    if ($fh !== false) {
        $chunk = fread($fh, $max + 1);
        fclose($fh);
        $raw = is_string($chunk) ? $chunk : '';
    }
    if ($raw === '') {
        return [];
    }
    if (strlen($raw) > $max) {
        apiError('Request body too large', 413, 'PAYLOAD_TOO_LARGE');
    }
    $data = json_decode($raw, true);

    return is_array($data) ? $data : [];
}

/**
 * Max bytes read for non-JSON bodies from php://input (CSRF fallback path; GOSITEME_API_MAX_NONJSON_BODY_BYTES, cap 1MiB).
 */
function api_max_nonjson_body_bytes(): int
{
    $v = (int) (getenv('GOSITEME_API_MAX_NONJSON_BODY_BYTES') ?: 0);
    if ($v <= 0) {
        return 65536;
    }

    return min($v, 1024 * 1024);
}

/**
 * Read raw body from php://input with a hard cap (DoS guard for non-JSON CSRF token extraction).
 */
function api_read_raw_body_limited(int $maxBytes): string
{
    if ($maxBytes <= 0) {
        return '';
    }
    $fh = @fopen('php://input', 'rb');
    if ($fh === false) {
        return '';
    }
    $chunk = fread($fh, $maxBytes + 1);
    fclose($fh);
    $raw = is_string($chunk) ? $chunk : '';
    if (strlen($raw) > $maxBytes) {
        apiError('Request body too large', 413, 'PAYLOAD_TOO_LARGE');
    }

    return $raw;
}

/**
 * Send a success response with standardized format.
 *
 * @param mixed  $data       Response payload
 * @param int    $statusCode HTTP status code (default: 200)
 * @param array  $meta       Optional metadata (pagination, timing, etc.)
 */
function apiSuccess(mixed $data = null, int $statusCode = 200, array $meta = []): never {
    http_response_code($statusCode);
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }

    $response = ['success' => true];

    if ($data !== null) {
        $response['data'] = $data;
    }

    if (!empty($meta)) {
        $response['meta'] = $meta;
    }

    if (!empty($GLOBALS['root_request_id']) && is_string($GLOBALS['root_request_id'])) {
        $response['request_id'] = $GLOBALS['root_request_id'];
    }

    echo _redactOutput(json_encode($response, _api_json_encode_flags()));
    exit;
}

/**
 * Send an error response with standardized format.
 *
 * @param string $message    Human-readable error message
 * @param int    $statusCode HTTP status code (default: 400)
 * @param string $code       Machine-readable error code (default: derived from message)
 * @param array  $details    Optional extra error details
 */
function apiError(string $message, int $statusCode = 400, string $code = '', array $details = []): never {
    http_response_code($statusCode);
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, private');
        header('Pragma: no-cache');
    }

    if (!$code) {
        $code = strtoupper(str_replace([' ', '-', '.'], '_', preg_replace('/[^a-zA-Z0-9\s_-]/', '', $message)));
        $code = substr($code, 0, 50);
    }

    $response = [
        'success' => false,
        'error'   => $message,
        'code'    => $code,
    ];

    if (!empty($details)) {
        $response['details'] = $details;
    }

    if (!empty($GLOBALS['root_request_id']) && is_string($GLOBALS['root_request_id'])) {
        $response['request_id'] = $GLOBALS['root_request_id'];
    }

    echo _redactOutput(json_encode($response, _api_json_encode_flags()));
    exit;
}

/**
 * Send a paginated list response.
 *
 * @param array $items   Array of items for this page
 * @param int   $total   Total number of items across all pages
 * @param int   $page    Current page number (1-based)
 * @param int   $perPage Items per page
 */
function apiPaginated(array $items, int $total, int $page = 1, int $perPage = 20): never {
    $totalPages = $perPage > 0 ? (int) ceil($total / $perPage) : 1;

    apiSuccess($items, 200, [
        'pagination' => [
            'page'        => $page,
            'per_page'    => $perPage,
            'total'       => $total,
            'total_pages' => $totalPages,
            'has_more'    => $page < $totalPages,
        ],
    ]);
}

/**
 * Validate required fields from request input.
 * Sends 400 error and exits if any required fields are missing.
 *
 * @param array  $input    Input array (typically $_POST or parsed JSON body)
 * @param array  $required Array of required field names
 * @return array The validated input (for chaining)
 */
function apiRequireFields(array $input, array $required): array {
    $missing = [];
    foreach ($required as $field) {
        if (!isset($input[$field]) || (is_string($input[$field]) && trim($input[$field]) === '')) {
            $missing[] = $field;
        }
    }

    if (!empty($missing)) {
        apiError(
            'Missing required fields: ' . implode(', ', $missing),
            400,
            'MISSING_REQUIRED_FIELDS',
            ['fields' => $missing]
        );
    }

    return $input;
}

/**
 * Get JSON request body, parsed.
 * Falls back to $_POST for form-encoded requests.
 *
 * @return array Parsed request body
 */
function apiGetBody(): array {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (str_contains($contentType, 'application/json')) {
        return api_read_json_body_limited();
    }

    return $_POST;
}
