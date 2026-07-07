<?php
/**
 * Alfred AI — CORS Handler
 * Strict origin-based CORS for the REST API.
 *
 * Usage (top of any API endpoint):
 *   require_once __DIR__ . '/includes/cors.php';
 *   handleCors();
 */

function handleCors(): void {
    // Allowed first-party origins
    $allowedOrigins = [
        'https://gositeme.com',
        'https://www.gositeme.com',
    ];

    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    // Match exact origin or any subdomain of gositeme.com over HTTPS
    $isAllowed = false;
    if (in_array($origin, $allowedOrigins, true)) {
        $isAllowed = true;
    } elseif (!empty($origin) && preg_match('#^https://([a-z0-9\-]+\.)*gositeme\.com$#i', $origin)) {
        $isAllowed = true;
    }

    if ($isAllowed) {
        header("Access-Control-Allow-Origin: {$origin}");
        header("Access-Control-Allow-Credentials: true");
        header("Vary: Origin");
    }

    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key, X-Alfred-Signature");
    header("Access-Control-Max-Age: 86400");

    // Handle preflight
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}
