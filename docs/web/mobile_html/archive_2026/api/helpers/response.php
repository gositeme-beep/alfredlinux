<?php
/**
 * GoSiteMe Standardized API Response Helper
 * ──────────────────────────────────────────
 * Usage: require_once __DIR__ . '/helpers/response.php';
 *
 * apiSuccess(['users' => $users]);
 * apiError('Not found', 404);
 * apiPaginated($items, $total, $page, $perPage);
 *
 * @since v14.0
 */

/**
 * Send a successful JSON response.
 *
 * @param array $data   Payload data
 * @param int   $status HTTP status code (default 200)
 * @param string|null $message Optional message
 */
function apiSuccess(array $data = [], int $status = 200, ?string $message = null): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    $response = ['success' => true];
    if ($message !== null) $response['message'] = $message;
    echo json_encode(array_merge($response, $data), JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Send an error JSON response.
 *
 * @param string $message Error message
 * @param int    $status  HTTP status code (default 400)
 * @param array  $extra   Additional data to include
 */
function apiError(string $message, int $status = 400, array $extra = []): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    $response = ['success' => false, 'error' => $message];
    echo json_encode(array_merge($response, $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Send a paginated JSON response.
 *
 * @param array $items   Items for current page
 * @param int   $total   Total items across all pages
 * @param int   $page    Current page number (1-based)
 * @param int   $perPage Items per page
 */
function apiPaginated(array $items, int $total, int $page, int $perPage): never {
    apiSuccess([
        'data' => $items,
        'pagination' => [
            'page'       => $page,
            'per_page'   => $perPage,
            'total'      => $total,
            'total_pages' => (int) ceil($total / max(1, $perPage)),
        ],
    ]);
}

/**
 * Require specific HTTP method(s). Sends 405 if method not allowed.
 *
 * @param string|array $methods Allowed method(s)
 */
function requireMethod(string|array $methods): void {
    $methods = (array) $methods;
    $current = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($current === 'OPTIONS') {
        header('Allow: ' . implode(', ', $methods));
        http_response_code(204);
        exit;
    }
    if (!in_array($current, $methods, true)) {
        header('Allow: ' . implode(', ', $methods));
        apiError('Method not allowed', 405);
    }
}

/**
 * Get JSON request body as associative array.
 *
 * @return array Parsed body
 */
function getJsonBody(): array {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/**
 * Get a required field from the request body. Sends 400 if missing.
 *
 * @param array  $body  Parsed request body
 * @param string $field Field name
 * @param string $label Human-readable label for error message
 * @return mixed Field value
 */
function requireField(array $body, string $field, string $label = ''): mixed {
    if (!isset($body[$field]) || $body[$field] === '') {
        apiError(($label ?: $field) . ' is required', 400);
    }
    return $body[$field];
}
