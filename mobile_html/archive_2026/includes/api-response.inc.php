<?php
/**
 * GoSiteMe Standardized API Response Helpers
 * ────────────────────────────────────────────
 * Consistent JSON response format for all API endpoints.
 * Auto-loaded via api-security.php — available in every API file.
 *
 * Standard format:
 *   { "success": true,  "data": {...}, "meta": {...} }
 *   { "success": false, "error": "message", "code": "ERROR_CODE" }
 *
 * @since v14.0
 */

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

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    return $_POST;
}
