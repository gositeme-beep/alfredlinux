<?php
/**
 * Alfred IDE — Session Validation & User Profile API
 *
 * GET  /api/alfred-ide-session.php              → check session, return user profile
 * GET  /api/alfred-ide-session.php?action=check → same (explicit)
 *
 * Returns: { valid: bool, user_id, email, name, avatar, client_id }
 *
 * Used by the Alfred Voice extension to display the authenticated user's
 * name and avatar in the status bar.
 */

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Alfred-Source, X-Alfred-IDE-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../includes/db-config.inc.php';
require_once __DIR__ . '/../includes/alfred-ide-bearer.inc.php';

$token = alfred_resolve_ide_bearer_token();

if (!$token) {
    $token = $_COOKIE['alfred_ide_token'] ?? '';
}

if (!$token) {
    $token = $_SESSION['ide_session_token'] ?? '';
}

if (!$token) {
    echo json_encode(['valid' => false, 'error' => 'No session token']);
    exit;
}

$tokenHash = hash('sha256', $token);
$db = getSharedDB();

$user = alfred_ide_lookup_user_by_token_hash($db, $tokenHash);

if (!$user) {
    echo json_encode(['valid' => false, 'error' => 'Invalid or expired session']);
    exit;
}

echo json_encode([
    'valid'     => true,
    'user_id'   => (int)$user['id'],
    'client_id' => $user['client_id'] ? (int)$user['client_id'] : null,
    'email'     => $user['email'] ?: $user['google_email'],
    'name'      => $user['display_name'] ?: $user['google_name'] ?: $user['email'] ?: $user['google_email'],
    'avatar'    => !empty($user['google_avatar']) ? (string) $user['google_avatar'] : '',
    'verified'  => true,
    'last_login' => $user['last_login']
]);
