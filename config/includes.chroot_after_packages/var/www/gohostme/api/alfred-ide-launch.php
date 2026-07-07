<?php
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Use POST']);
    exit;
}

require_once __DIR__ . '/../includes/db-config.inc.php';
require_once __DIR__ . '/../includes/alfred-workspace-launch.inc.php';
require_once __DIR__ . '/../includes/alfred-ide-bearer.inc.php';

function alfred_ide_launch_hydrate_main_session(PDO $db, int $clientId): void
{
    if ($clientId <= 0) {
        return;
    }

    try {
        $stmt = $db->prepare("SELECT firstname, lastname, email FROM clients WHERE id = ? LIMIT 1");
        $stmt->execute([$clientId]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$client) {
            return;
        }

        $clientName = trim(((string)($client['firstname'] ?? '')) . ' ' . ((string)($client['lastname'] ?? '')));
        $_SESSION['client_id'] = $clientId;
        $_SESSION['uid'] = $clientId;
        $_SESSION['client_email'] = (string)($client['email'] ?? '');
        $_SESSION['email'] = (string)($client['email'] ?? '');
        $_SESSION['client_name'] = $clientName !== '' ? $clientName : ('Client ' . $clientId);
        $_SESSION['username'] = $_SESSION['client_name'];
        $_SESSION['logged_in'] = true;
    } catch (Throwable $e) {
        error_log('[ALFRED-IDE-LAUNCH] Session hydrate failed for client ' . $clientId . ': ' . $e->getMessage());
    }
}

function alfred_ide_launch_resolve_client_id(PDO $db): int
{
    $clientId = (int)($_SESSION['client_id'] ?? $_SESSION['uid'] ?? 0);
    if ($clientId > 0 && (!empty($_SESSION['logged_in']) || !empty($_SESSION['client_id']) || !empty($_SESSION['uid']))) {
        return $clientId;
    }

    $token = alfred_resolve_ide_bearer_token();
    if ($token === '') {
        $token = $_COOKIE['alfred_ide_token'] ?? $_SESSION['ide_session_token'] ?? '';
    }
    if ($token === '') {
        return 0;
    }

    $user = alfred_ide_lookup_user_by_token_hash($db, hash('sha256', $token));
    $clientId = (int)($user['client_id'] ?? 0);
    if ($clientId > 0) {
        alfred_ide_launch_hydrate_main_session($db, $clientId);
    }

    return $clientId;
}

try {
    $db = getSharedDB();
} catch (Throwable $dbError) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'code' => 'database_unavailable',
        'error' => 'Alfred IDE could not reach the workspace launch database right now.',
        'manage' => '/pay/account/logins.php',
    ]);
    exit;
}

$clientId = alfred_ide_launch_resolve_client_id($db);

if ($clientId <= 0) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'code' => 'login_required',
        'error' => 'Sign in through Alfred IDE before workspace launch can be resolved.',
        'next' => '/alfred-ide-auth.php',
    ]);
    exit;
}

if ($clientId === 33) {
    echo json_encode([
        'success' => true,
        'url' => '/alfred-ide/',
        'method' => 'direct',
        'message' => 'Commander workspace ready.',
    ]);
    exit;
}

$serviceStmt = $db->prepare("SELECT s.id, s.status, s.domain, s.username, p.name AS product_name
    FROM services s
    INNER JOIN products p ON p.id = s.product_id
    WHERE s.client_id = ?
      AND p.server_module = 'gocodeme'
    ORDER BY CASE WHEN s.status = 'Active' THEN 0 ELSE 1 END, s.id DESC");
$serviceStmt->execute([$clientId]);
$services = $serviceStmt->fetchAll(PDO::FETCH_ASSOC);

$activeServices = array_values(array_filter($services, static function (array $service): bool {
    return (($service['status'] ?? '') === 'Active');
}));

if (empty($activeServices)) {
    $hasWorkspaceHistory = !empty($services);
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'code' => $hasWorkspaceHistory ? 'service_not_active' : 'service_missing',
        'error' => $hasWorkspaceHistory
            ? 'This account has an Alfred IDE service record, but nothing active can launch right now.'
            : 'This account does not have an active Alfred IDE service yet.',
        'next' => $hasWorkspaceHistory ? '/pay/account/logins.php' : '/pricing.php',
        'manage' => '/pay/account/logins.php',
        'services' => array_map(static function (array $service): array {
            return [
                'id' => (int)($service['id'] ?? 0),
                'product_name' => (string)($service['product_name'] ?? 'Alfred IDE'),
                'status' => (string)($service['status'] ?? ''),
                'domain' => (string)($service['domain'] ?? ''),
            ];
        }, $services),
    ], JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    $launch = alfred_workspace_build_launch($db, $clientId);
} catch (Throwable $launchError) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'code' => 'launch_failed',
        'error' => 'Alfred IDE could not mint a workspace handoff right now.',
        'manage' => '/pay/account/logins.php',
    ]);
    exit;
}

if (empty($launch['success']) || empty($launch['url'])) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'code' => 'launch_missing_url',
        'error' => 'Workspace launch returned without a usable destination.',
        'manage' => '/pay/account/logins.php',
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'url' => (string)$launch['url'],
    'method' => (string)($launch['method'] ?? 'billing-sso'),
    'expires' => (string)($launch['expires'] ?? ''),
    'service_count' => count($activeServices),
    'message' => count($activeServices) === 1
        ? 'Active Alfred IDE workspace ready.'
        : 'Multiple active Alfred IDE services found; opening the current workspace handoff.',
    'services' => array_map(static function (array $service): array {
        return [
            'id' => (int)($service['id'] ?? 0),
            'product_name' => (string)($service['product_name'] ?? 'Alfred IDE'),
            'status' => (string)($service['status'] ?? ''),
            'domain' => (string)($service['domain'] ?? ''),
        ];
    }, $activeServices),
], JSON_UNESCAPED_SLASHES);