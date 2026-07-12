<?php
/**
 * Alfred IDE — Connection diagnostic (authenticated).
 *
 * GET /api/alfred-ide-diagnostic.php
 *
 * Requires: Authorization: Bearer <token> and/or X-Alfred-IDE-Token: <same>
 * Returns: whether PHP saw headers, token length, DB session validity, chat CSRF would be skipped.
 *
 * Does not echo the raw token or any secret.
 */
session_start();

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Alfred-IDE-Token, X-Alfred-Source');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Use GET']);
    exit;
}

require_once __DIR__ . '/../includes/db-config.inc.php';
require_once __DIR__ . '/../includes/alfred-ide-bearer.inc.php';

$channels = alfred_ide_bearer_debug_channels();
$token = alfred_resolve_ide_bearer_token();

if ($token === '') {
    http_response_code(401);
    echo json_encode([
        'ok' => false,
        'error' => 'missing_token',
        'hint' => 'Send Authorization: Bearer <token> and X-Alfred-IDE-Token (Apache often strips Authorization).',
        'channels' => $channels,
    ]);
    exit;
}

$tokenHash = hash('sha256', $token);
$db = getSharedDB();

try {
    $user = alfred_ide_lookup_user_by_token_hash($db, $tokenHash);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'database', 'channels' => $channels]);
    exit;
}

if (!$user) {
    http_response_code(403);
    echo json_encode([
        'ok' => false,
        'error' => 'invalid_or_expired_token',
        'channels' => $channels,
        'resolved_token_length' => strlen($token),
    ]);
    exit;
}

$name = (string) ($user['display_name'] ?: $user['google_name'] ?: $user['email'] ?: 'Commander');
$clientId = !empty($user['client_id']) ? (int) $user['client_id'] : null;

echo json_encode([
    'ok' => true,
    'message' => 'IDE session is valid; alfred-chat.php will skip CSRF when this token is sent.',
    'user' => [
        'ide_user_id' => (int) ($user['id'] ?? 0),
        'client_id' => $clientId,
        'name' => $name,
    ],
    'channels' => $channels,
    'checks' => [
        'token_resolved' => true,
        'db_session_valid' => true,
        'csrf_skip_expected' => true,
        'recommend_x_alfred_ide_token' => !$channels['HTTP_AUTHORIZATION_nonempty'] && !$channels['REDIRECT_HTTP_AUTHORIZATION_nonempty'],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
