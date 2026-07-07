<?php
/**
 * Military SDK REST API — Level 4 Item 12
 * All responses are JSON. No HTML.
 */
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once dirname(__DIR__) . '/includes/db-config.inc.php';

$db = getSharedDB();
$startTime = microtime(true);

// --- Authentication ---
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
if (!preg_match('/^Bearer\s+(mil_[A-Za-z0-9_\-]+)$/', $authHeader, $m)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Missing or malformed Authorization header']);
    exit;
}

$apiKeyRaw = $m[1];
$apiKeyHash = hash('sha256', $apiKeyRaw);

$stmt = $db->prepare(
    'SELECT * FROM military_api_keys WHERE api_key_hash = ? AND is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())'
);
$stmt->bind_param('s', $apiKeyHash);
$stmt->execute();
$keyRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$keyRow) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid or expired API key']);
    exit;
}

$apiKeyId   = (int)$keyRow['id'];
$rateLimit  = (int)($keyRow['rate_limit'] ?? 600);
$permissions = json_decode($keyRow['permissions'] ?? '[]', true) ?: [];

// --- Rate Limiting ---
$stmt = $db->prepare(
    'SELECT COUNT(*) AS cnt FROM military_api_logs WHERE api_key_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)'
);
$stmt->bind_param('i', $apiKeyId);
$stmt->execute();
$usedCount = (int)$stmt->get_result()->fetch_assoc()['cnt'];
$stmt->close();

header("X-RateLimit-Limit: $rateLimit");
header('X-RateLimit-Remaining: ' . max(0, $rateLimit - $usedCount));

if ($usedCount >= $rateLimit) {
    logRequest($db, $apiKeyId, $endpoint ?? '', $_SERVER['REQUEST_METHOD'], null, 429, $startTime);
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Rate limit exceeded']);
    exit;
}

// --- Routing ---
$endpoint = $_GET['endpoint'] ?? trim($_SERVER['PATH_INFO'] ?? '', '/');
$method   = $_SERVER['REQUEST_METHOD'];

// --- Helpers ---
function respond(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function requirePerm(array $permissions, string $perm): void {
    if (!in_array($perm, $permissions, true)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => "Missing permission: $perm"]);
        exit;
    }
}

function getJsonBody(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON body']);
        exit;
    }
    return $data;
}

function logRequest(mysqli $db, int $apiKeyId, string $endpoint, string $method, ?string $requestData, int $responseCode, float $startTime): void {
    $ms = round((microtime(true) - $startTime) * 1000, 2);
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $stmt = $db->prepare(
        'INSERT INTO military_api_logs (api_key_id, endpoint, method, request_data, response_code, response_time_ms, ip_address, user_agent) VALUES (?,?,?,?,?,?,?,?)'
    );
    $stmt->bind_param('isssdiss', $apiKeyId, $endpoint, $method, $requestData, $responseCode, $ms, $ip, $ua);
    $stmt->execute();
    $stmt->close();
}

// --- Dispatch ---
$requestData = ($method === 'POST') ? file_get_contents('php://input') : json_encode($_GET);

switch (true) {

    // 1. GET /rank — single client rank info
    case $endpoint === 'rank' && $method === 'GET':
        requirePerm($permissions, 'rank.read');
        $clientId = (int)($_GET['client_id'] ?? 0);
        if ($clientId <= 0) {
            logRequest($db, $apiKeyId, $endpoint, $method, $requestData, 400, $startTime);
            respond(['success' => false, 'error' => 'client_id required'], 400);
        }
        $stmt = $db->prepare(
            'SELECT ur.client_id, ur.rank_id, ur.xp, mr.rank_code, mr.rank_name, mr.rank_tier, mr.rank_group, mr.badge_icon
             FROM user_ranks ur JOIN military_ranks mr ON ur.rank_id = mr.id WHERE ur.client_id = ?'
        );
        $stmt->bind_param('i', $clientId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) {
            logRequest($db, $apiKeyId, $endpoint, $method, $requestData, 404, $startTime);
            respond(['success' => false, 'error' => 'Client not found'], 404);
        }
        logRequest($db, $apiKeyId, $endpoint, $method, $requestData, 200, $startTime);
        respond(['success' => true, 'data' => $row]);
        break;

    // 2. POST /xp — award XP
    case $endpoint === 'xp' && $method === 'POST':
        requirePerm($permissions, 'xp.write');
        $body = getJsonBody();
        $clientId = (int)($body['client_id'] ?? 0);
        $action   = $body['action'] ?? '';
        $context  = $body['context'] ?? '';
        if ($clientId <= 0 || $action === '') {
            logRequest($db, $apiKeyId, $endpoint, $method, $requestData, 400, $startTime);
            respond(['success' => false, 'error' => 'client_id and action required'], 400);
        }
        // Fetch action definition
        $stmt = $db->prepare('SELECT xp_value, cooldown_seconds FROM xp_actions WHERE action_key = ? AND is_active = 1');
        $stmt->bind_param('s', $action);
        $stmt->execute();
        $xpAction = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$xpAction) {
            logRequest($db, $apiKeyId, $endpoint, $method, $requestData, 400, $startTime);
            respond(['success' => false, 'error' => 'Unknown or inactive action'], 400);
        }
        $xpValue = (int)$xpAction['xp_value'];
        // Rank multiplier
        $stmt = $db->prepare(
            'SELECT ur.xp, ur.rank_id, mr.xp_multiplier, mr.rank_code FROM user_ranks ur JOIN military_ranks mr ON ur.rank_id = mr.id WHERE ur.client_id = ?'
        );
        $stmt->bind_param('i', $clientId);
        $stmt->execute();
        $userRank = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$userRank) {
            logRequest($db, $apiKeyId, $endpoint, $method, $requestData, 404, $startTime);
            respond(['success' => false, 'error' => 'Client has no rank record'], 404);
        }
        $multiplier = (float)($userRank['xp_multiplier'] ?? 1.0);
        $awarded    = (int)round($xpValue * $multiplier);
        $newTotal   = (int)$userRank['xp'] + $awarded;
        // Insert ledger
        $stmt = $db->prepare(
            'INSERT INTO xp_ledger (client_id, action_key, xp_awarded, multiplier, context, source) VALUES (?,?,?,?,?,?)'
        );
        $source = 'api';
        $stmt->bind_param('isisds', $clientId, $action, $awarded, $multiplier, $context, $source);
        $stmt->execute();
        $stmt->close();
        // Update total
        $stmt = $db->prepare('UPDATE user_ranks SET xp = ? WHERE client_id = ?');
        $stmt->bind_param('ii', $newTotal, $clientId);
        $stmt->execute();
        $stmt->close();
        // Check auto-promote
        $rankUp = false;
        $newRank = null;
        $stmt = $db->prepare(
            'SELECT id, rank_code, rank_name FROM military_ranks WHERE xp_threshold <= ? AND id > ? ORDER BY xp_threshold DESC LIMIT 1'
        );
        $stmt->bind_param('ii', $newTotal, $userRank['rank_id']);
        $stmt->execute();
        $nextRank = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($nextRank) {
            $rankUp = true;
            $newRank = $nextRank['rank_code'];
            $stmt = $db->prepare('UPDATE user_ranks SET rank_id = ? WHERE client_id = ?');
            $stmt->bind_param('ii', $nextRank['id'], $clientId);
            $stmt->execute();
            $stmt->close();
        }
        logRequest($db, $apiKeyId, $endpoint, $method, $requestData, 200, $startTime);
        respond(['success' => true, 'data' => [
            'xp_awarded' => $awarded,
            'total_xp'   => $newTotal,
            'rank_up'    => $rankUp,
            'new_rank'   => $newRank,
        ]]);
        break;

    // 3. GET /missions — active missions
    case $endpoint === 'missions' && $method === 'GET':
        requirePerm($permissions, 'mission.read');
        $result = $db->query(
            "SELECT id, mission_code, title, description, mission_type, xp_reward, status, starts_at, ends_at
             FROM missions WHERE status = 'active'
             UNION ALL
             SELECT id, mission_code, title, description, 'auto' AS mission_type, xp_reward, status, starts_at, ends_at
             FROM auto_missions WHERE status = 'active'
             ORDER BY starts_at DESC"
        );
        $missions = [];
        while ($row = $result->fetch_assoc()) {
            $missions[] = $row;
        }
        $result->free();
        logRequest($db, $apiKeyId, $endpoint, $method, $requestData, 200, $startTime);
        respond(['success' => true, 'data' => $missions]);
        break;

    // 4. POST /missions/assign
    case $endpoint === 'missions/assign' && $method === 'POST':
        requirePerm($permissions, 'mission.write');
        $body = getJsonBody();
        $missionId = (int)($body['mission_id'] ?? 0);
        $clientId  = (int)($body['client_id'] ?? 0);
        if ($missionId <= 0 || $clientId <= 0) {
            logRequest($db, $apiKeyId, $endpoint, $method, $requestData, 400, $startTime);
            respond(['success' => false, 'error' => 'mission_id and client_id required'], 400);
        }
        $stmt = $db->prepare(
            'INSERT INTO mission_assignments (mission_id, client_id, assigned_at, status) VALUES (?, ?, NOW(), ?)'
        );
        $status = 'assigned';
        $stmt->bind_param('iis', $missionId, $clientId, $status);
        if (!$stmt->execute()) {
            logRequest($db, $apiKeyId, $endpoint, $method, $requestData, 500, $startTime);
            respond(['success' => false, 'error' => 'Assignment failed'], 500);
        }
        $assignId = $stmt->insert_id;
        $stmt->close();
        logRequest($db, $apiKeyId, $endpoint, $method, $requestData, 201, $startTime);
        respond(['success' => true, 'data' => ['assignment_id' => $assignId]], 201);
        break;

    // 5. GET /territory
    case $endpoint === 'territory' && $method === 'GET':
        requirePerm($permissions, 'territory.read');
        $result = $db->query('SELECT * FROM territory_zones ORDER BY zone_code');
        $zones = [];
        while ($row = $result->fetch_assoc()) {
            $zones[] = $row;
        }
        $result->free();
        logRequest($db, $apiKeyId, $endpoint, $method, $requestData, 200, $startTime);
        respond(['success' => true, 'data' => $zones]);
        break;

    // 6. GET /units
    case $endpoint === 'units' && $method === 'GET':
        requirePerm($permissions, 'unit.read');
        $result = $db->query(
            'SELECT mu.*, (SELECT COUNT(*) FROM unit_members um WHERE um.unit_id = mu.id) AS member_count
             FROM military_units mu ORDER BY mu.unit_name'
        );
        $units = [];
        while ($row = $result->fetch_assoc()) {
            $units[] = $row;
        }
        $result->free();
        logRequest($db, $apiKeyId, $endpoint, $method, $requestData, 200, $startTime);
        respond(['success' => true, 'data' => $units]);
        break;

    // 7. GET /decorations
    case $endpoint === 'decorations' && $method === 'GET':
        requirePerm($permissions, 'decoration.read');
        $clientId = (int)($_GET['client_id'] ?? 0);
        if ($clientId <= 0) {
            logRequest($db, $apiKeyId, $endpoint, $method, $requestData, 400, $startTime);
            respond(['success' => false, 'error' => 'client_id required'], 400);
        }
        $stmt = $db->prepare(
            'SELECT ud.*, md.decoration_name, md.decoration_code, md.icon, md.description
             FROM user_decorations ud JOIN military_decorations md ON ud.decoration_id = md.id
             WHERE ud.client_id = ? ORDER BY ud.awarded_at DESC'
        );
        $stmt->bind_param('i', $clientId);
        $stmt->execute();
        $result = $stmt->get_result();
        $decs = [];
        while ($row = $result->fetch_assoc()) {
            $decs[] = $row;
        }
        $stmt->close();
        logRequest($db, $apiKeyId, $endpoint, $method, $requestData, 200, $startTime);
        respond(['success' => true, 'data' => $decs]);
        break;

    // 8. GET /leaderboard
    case $endpoint === 'leaderboard' && $method === 'GET':
        requirePerm($permissions, 'rank.read');
        $result = $db->query(
            'SELECT ur.client_id, ur.xp, mr.rank_code, mr.rank_name, mr.rank_tier, mr.badge_icon
             FROM user_ranks ur JOIN military_ranks mr ON ur.rank_id = mr.id
             ORDER BY ur.xp DESC LIMIT 50'
        );
        $board = [];
        $pos = 1;
        while ($row = $result->fetch_assoc()) {
            $row['position'] = $pos++;
            $board[] = $row;
        }
        $result->free();
        logRequest($db, $apiKeyId, $endpoint, $method, $requestData, 200, $startTime);
        respond(['success' => true, 'data' => $board]);
        break;

    // Unknown endpoint
    default:
        logRequest($db, $apiKeyId, $endpoint, $method, $requestData, 404, $startTime);
        respond(['success' => false, 'error' => "Unknown endpoint: $method /$endpoint"], 404);
        break;
}
