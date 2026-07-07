<?php
/**
 * Military Rank Management API
 * 
 * Endpoints:
 *   POST ?action=promote        — Promote a user      { client_id, to_rank, reason }
 *   POST ?action=demote         — Demote a user       { client_id, to_rank, reason }
 *   POST ?action=assign         — Initial assignment   { client_id, rank_code, region?, division? }
 *   POST ?action=temp_elevate   — Temporary elevation  { client_id, to_rank, duration_hours, reason }
 *   POST ?action=temp_revoke    — Revoke temp rank     { client_id }
 *   POST ?action=discharge      — Remove from service  { client_id, reason }
 *   GET  ?action=roster         — Full ranked roster    { ?rank_group, ?region }
 *   GET  ?action=user_rank      — Get user's rank       { client_id }
 *   GET  ?action=ranks          — List all ranks
 *   GET  ?action=history        — Rank change history   { ?client_id }
 *   GET  ?action=fleet_map      — Geographic fleet data
 *   GET  ?action=stats          — Global rank statistics
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/db-config.inc.php';

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

header('Content-Type: application/json');
$db = getSharedDB();

// Auth: internal secret (Alfred/MCP) or logged-in commander
$isInternal = false;
$internalSecret = $_SERVER['HTTP_X_INTERNAL_SECRET'] ?? '';
$expectedSecret = getenv('INTERNAL_SECRET') ?: getenv('INTERNAL_RELAY_SECRET') ?: '';
if ($internalSecret && $expectedSecret && hash_equals($expectedSecret, $internalSecret)) {
    $isInternal = true;
}

$callerClientId = (int)($_SESSION['client_id'] ?? $_SESSION['uid'] ?? 0);
$isCommander = ($callerClientId === 33) || $isInternal;

// Get caller's rank for permission checking
$callerRankTier = 0;
if ($callerClientId) {
    $stmt = $db->prepare("SELECT mr.rank_tier FROM user_ranks ur JOIN military_ranks mr ON mr.rank_code = ur.rank_code WHERE ur.client_id = ? AND ur.is_active = 1 ORDER BY mr.rank_tier DESC LIMIT 1");
    $stmt->execute([$callerClientId]);
    $callerRankTier = (int)($stmt->fetchColumn() ?: 0);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'promote':    handlePromotion(); break;
    case 'demote':     handleDemotion(); break;
    case 'assign':     handleAssign(); break;
    case 'temp_elevate': handleTempElevate(); break;
    case 'temp_revoke':  handleTempRevoke(); break;
    case 'discharge':  handleDischarge(); break;
    case 'roster':     handleRoster(); break;
    case 'user_rank':  handleUserRank(); break;
    case 'ranks':      handleListRanks(); break;
    case 'history':    handleHistory(); break;
    case 'fleet_map':  handleFleetMap(); break;
    case 'stats':      handleStats(); break;
    default:
        json_out(['error' => 'Unknown action'], 400);
}

function json_out(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function getInput(): array {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    return is_array($json) ? array_merge($_POST, $json) : $_POST;
}

function requireAuth(int $minTier = 11): void {
    global $isCommander, $callerRankTier;
    if ($isCommander) return;
    if ($callerRankTier >= $minTier) return;
    json_out(['error' => 'Insufficient rank. Required tier: ' . $minTier], 403);
}

function handlePromotion(): void {
    global $db, $callerClientId;
    requireAuth(7); // Captain+ can promote enlisted; Commander approves all
    $input = getInput();
    $targetId = (int)($input['client_id'] ?? 0);
    $toRank   = trim($input['to_rank'] ?? '');
    $reason   = trim($input['reason'] ?? 'Promotion approved');
    if (!$targetId || !$toRank) json_out(['error' => 'client_id and to_rank required'], 400);

    $newRank = $db->prepare("SELECT * FROM military_ranks WHERE rank_code = ?");
    $newRank->execute([$toRank]);
    $newRankData = $newRank->fetch();
    if (!$newRankData) json_out(['error' => 'Invalid rank code: ' . $toRank], 400);

    // Get current rank
    $cur = $db->prepare("SELECT ur.rank_code, mr.rank_tier, mr.rank_name FROM user_ranks ur JOIN military_ranks mr ON mr.rank_code = ur.rank_code WHERE ur.client_id = ? AND ur.is_active = 1 ORDER BY mr.rank_tier DESC LIMIT 1");
    $cur->execute([$targetId]);
    $currentRank = $cur->fetch();

    if ($currentRank && (int)$newRankData['rank_tier'] <= (int)$currentRank['rank_tier']) {
        json_out(['error' => 'New rank must be higher than current rank (' . $currentRank['rank_name'] . '). Use demote for lower ranks.'], 400);
    }

    // Deactivate old rank
    $db->prepare("UPDATE user_ranks SET is_active = 0 WHERE client_id = ? AND is_active = 1")->execute([$targetId]);

    // Assign new rank
    $db->prepare("INSERT INTO user_ranks (client_id, rank_code, assigned_by, notes) VALUES (?, ?, ?, ?)")
        ->execute([$targetId, $toRank, $callerClientId ?: 33, $reason]);

    // Log
    $db->prepare("INSERT INTO rank_history (client_id, action, from_rank, to_rank, reason, performed_by) VALUES (?, 'promote', ?, ?, ?, ?)")
        ->execute([$targetId, $currentRank['rank_code'] ?? null, $toRank, $reason, $callerClientId ?: 33]);

    // Get user name
    $nameStmt = $db->prepare("SELECT CONCAT(firstname, ' ', lastname) as name FROM tblclients WHERE id = ?");
    $nameStmt->execute([$targetId]);
    $userName = $nameStmt->fetchColumn() ?: "User #$targetId";

    json_out([
        'ok' => true,
        'message' => "$userName promoted to " . $newRankData['rank_name'],
        'client_id' => $targetId,
        'name' => $userName,
        'from_rank' => $currentRank['rank_code'] ?? null,
        'to_rank' => $toRank,
        'rank_name' => $newRankData['rank_name'],
        'rank_tier' => (int)$newRankData['rank_tier'],
    ]);
}

function handleDemotion(): void {
    global $db, $callerClientId;
    requireAuth(10); // General+ can demote
    $input = getInput();
    $targetId = (int)($input['client_id'] ?? 0);
    $toRank   = trim($input['to_rank'] ?? '');
    $reason   = trim($input['reason'] ?? 'Demotion ordered');
    if (!$targetId || !$toRank) json_out(['error' => 'client_id and to_rank required'], 400);

    $newRank = $db->prepare("SELECT * FROM military_ranks WHERE rank_code = ?");
    $newRank->execute([$toRank]);
    $newRankData = $newRank->fetch();
    if (!$newRankData) json_out(['error' => 'Invalid rank code'], 400);

    $cur = $db->prepare("SELECT ur.rank_code, mr.rank_tier, mr.rank_name FROM user_ranks ur JOIN military_ranks mr ON mr.rank_code = ur.rank_code WHERE ur.client_id = ? AND ur.is_active = 1 ORDER BY mr.rank_tier DESC LIMIT 1");
    $cur->execute([$targetId]);
    $currentRank = $cur->fetch();

    $db->prepare("UPDATE user_ranks SET is_active = 0 WHERE client_id = ? AND is_active = 1")->execute([$targetId]);
    $db->prepare("INSERT INTO user_ranks (client_id, rank_code, assigned_by, notes) VALUES (?, ?, ?, ?)")
        ->execute([$targetId, $toRank, $callerClientId ?: 33, $reason]);

    $db->prepare("INSERT INTO rank_history (client_id, action, from_rank, to_rank, reason, performed_by) VALUES (?, 'demote', ?, ?, ?, ?)")
        ->execute([$targetId, $currentRank['rank_code'] ?? null, $toRank, $reason, $callerClientId ?: 33]);

    json_out(['ok' => true, 'message' => "Demoted to " . $newRankData['rank_name'], 'to_rank' => $toRank]);
}

function handleAssign(): void {
    global $db, $callerClientId;
    requireAuth(7);
    $input = getInput();
    $targetId = (int)($input['client_id'] ?? 0);
    $rankCode = trim($input['rank_code'] ?? 'recruit');
    $region   = trim($input['region'] ?? '');
    $division = trim($input['division'] ?? '');
    if (!$targetId) json_out(['error' => 'client_id required'], 400);

    $rankData = $db->prepare("SELECT * FROM military_ranks WHERE rank_code = ?");
    $rankData->execute([$rankCode]);
    $rank = $rankData->fetch();
    if (!$rank) json_out(['error' => 'Invalid rank code'], 400);

    // Check if already assigned
    $existing = $db->prepare("SELECT id FROM user_ranks WHERE client_id = ? AND is_active = 1");
    $existing->execute([$targetId]);
    if ($existing->fetch()) {
        json_out(['error' => 'User already has an active rank. Use promote/demote instead.'], 400);
    }

    $db->prepare("INSERT INTO user_ranks (client_id, rank_code, assigned_by, region, division, notes) VALUES (?, ?, ?, ?, ?, 'Initial assignment')")
        ->execute([$targetId, $rankCode, $callerClientId ?: 33, $region ?: null, $division ?: null]);

    $db->prepare("INSERT INTO rank_history (client_id, action, from_rank, to_rank, reason, performed_by) VALUES (?, 'assign', NULL, ?, 'Enlisted into service', ?)")
        ->execute([$targetId, $rankCode, $callerClientId ?: 33]);

    $nameStmt = $db->prepare("SELECT CONCAT(firstname, ' ', lastname) as name FROM tblclients WHERE id = ?");
    $nameStmt->execute([$targetId]);
    $userName = $nameStmt->fetchColumn() ?: "User #$targetId";

    json_out([
        'ok' => true,
        'message' => "$userName enlisted as " . $rank['rank_name'],
        'client_id' => $targetId,
        'rank_code' => $rankCode,
        'rank_name' => $rank['rank_name'],
    ]);
}

function handleTempElevate(): void {
    global $db, $callerClientId;
    requireAuth(11); // Commander only
    $input = getInput();
    $targetId = (int)($input['client_id'] ?? 0);
    $toRank   = trim($input['to_rank'] ?? '');
    $hours    = (int)($input['duration_hours'] ?? 4);
    $reason   = trim($input['reason'] ?? 'Temporary rank elevation');
    if (!$targetId || !$toRank) json_out(['error' => 'client_id and to_rank required'], 400);
    if ($hours < 1 || $hours > 168) json_out(['error' => 'Duration must be 1-168 hours'], 400);

    // Get current rank
    $cur = $db->prepare("SELECT rank_code FROM user_ranks WHERE client_id = ? AND is_active = 1 ORDER BY id DESC LIMIT 1");
    $cur->execute([$targetId]);
    $currentRank = $cur->fetchColumn() ?: 'civilian';

    // Revoke any existing temp elevation
    $db->prepare("UPDATE rank_elevations SET is_active = 0, revoked_at = NOW() WHERE client_id = ? AND is_active = 1")->execute([$targetId]);

    $db->prepare("INSERT INTO rank_elevations (client_id, original_rank, elevated_rank, reason, granted_by, expires_at) VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? HOUR))")
        ->execute([$targetId, $currentRank, $toRank, $reason, $callerClientId ?: 33, $hours]);

    $db->prepare("INSERT INTO rank_history (client_id, action, from_rank, to_rank, reason, performed_by) VALUES (?, 'temp_elevate', ?, ?, ?, ?)")
        ->execute([$targetId, $currentRank, $toRank, "Temp elevation for {$hours}h: $reason", $callerClientId ?: 33]);

    json_out([
        'ok' => true,
        'message' => "Temporarily elevated to $toRank for {$hours} hours",
        'client_id' => $targetId,
        'elevated_rank' => $toRank,
        'expires_in_hours' => $hours,
    ]);
}

function handleTempRevoke(): void {
    global $db, $callerClientId;
    requireAuth(11);
    $input = getInput();
    $targetId = (int)($input['client_id'] ?? 0);
    if (!$targetId) json_out(['error' => 'client_id required'], 400);

    $revoked = $db->prepare("UPDATE rank_elevations SET is_active = 0, revoked_at = NOW() WHERE client_id = ? AND is_active = 1");
    $revoked->execute([$targetId]);

    $db->prepare("INSERT INTO rank_history (client_id, action, reason, performed_by) VALUES (?, 'temp_revoke', 'Temporary elevation revoked', ?)")
        ->execute([$targetId, $callerClientId ?: 33]);

    json_out(['ok' => true, 'message' => 'Temporary elevation revoked', 'affected' => $revoked->rowCount()]);
}

function handleDischarge(): void {
    global $db, $callerClientId;
    requireAuth(11);
    $input = getInput();
    $targetId = (int)($input['client_id'] ?? 0);
    $reason   = trim($input['reason'] ?? 'Discharged from service');
    if (!$targetId) json_out(['error' => 'client_id required'], 400);

    $cur = $db->prepare("SELECT rank_code FROM user_ranks WHERE client_id = ? AND is_active = 1 LIMIT 1");
    $cur->execute([$targetId]);
    $fromRank = $cur->fetchColumn();

    $db->prepare("UPDATE user_ranks SET is_active = 0 WHERE client_id = ? AND is_active = 1")->execute([$targetId]);
    $db->prepare("UPDATE rank_elevations SET is_active = 0, revoked_at = NOW() WHERE client_id = ? AND is_active = 1")->execute([$targetId]);

    $db->prepare("INSERT INTO rank_history (client_id, action, from_rank, to_rank, reason, performed_by) VALUES (?, 'discharge', ?, NULL, ?, ?)")
        ->execute([$targetId, $fromRank, $reason, $callerClientId ?: 33]);

    json_out(['ok' => true, 'message' => 'Discharged from service', 'from_rank' => $fromRank]);
}

function handleRoster(): void {
    global $db;
    // Public-ish: any logged-in user can see roster (filtered by their rank later)
    $group  = $_GET['rank_group'] ?? null;
    $region = $_GET['region'] ?? null;

    $sql = "SELECT ur.client_id, ur.rank_code, ur.region, ur.division, ur.assigned_at,
                   mr.rank_name, mr.rank_tier, mr.rank_group, mr.clearance_level,
                   CONCAT(tc.firstname, ' ', tc.lastname) AS name, tc.email
            FROM user_ranks ur
            JOIN military_ranks mr ON mr.rank_code = ur.rank_code
            LEFT JOIN tblclients tc ON tc.id = ur.client_id
            WHERE ur.is_active = 1";
    $params = [];
    if ($group)  { $sql .= " AND mr.rank_group = ?"; $params[] = $group; }
    if ($region) { $sql .= " AND ur.region = ?"; $params[] = $region; }
    $sql .= " ORDER BY mr.rank_tier DESC, ur.assigned_at ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $roster = $stmt->fetchAll();

    json_out(['ok' => true, 'roster' => $roster, 'total' => count($roster)]);
}

function handleUserRank(): void {
    global $db;
    $targetId = (int)($_GET['client_id'] ?? 0);
    if (!$targetId) json_out(['error' => 'client_id required'], 400);

    $stmt = $db->prepare("
        SELECT ur.*, mr.rank_name, mr.rank_tier, mr.rank_group, mr.clearance_level, mr.max_fleet_view, mr.description AS rank_description,
               CONCAT(tc.firstname, ' ', tc.lastname) AS name, tc.email
        FROM user_ranks ur
        JOIN military_ranks mr ON mr.rank_code = ur.rank_code
        LEFT JOIN tblclients tc ON tc.id = ur.client_id
        WHERE ur.client_id = ? AND ur.is_active = 1
        LIMIT 1
    ");
    $stmt->execute([$targetId]);
    $rank = $stmt->fetch();

    // Check temp elevation
    $temp = $db->prepare("SELECT re.*, mr.rank_name AS elevated_name, mr.rank_tier AS elevated_tier
        FROM rank_elevations re JOIN military_ranks mr ON mr.rank_code = re.elevated_rank
        WHERE re.client_id = ? AND re.is_active = 1 AND re.expires_at > NOW() LIMIT 1");
    $temp->execute([$targetId]);
    $tempElev = $temp->fetch();

    // Permissions
    $effectiveRank = $tempElev ? $tempElev['elevated_rank'] : ($rank ? $rank['rank_code'] : null);
    $permissions = [];
    if ($effectiveRank) {
        $p = $db->prepare("SELECT permission_key FROM rank_permissions WHERE rank_code = ? AND granted = 1");
        $p->execute([$effectiveRank]);
        $permissions = $p->fetchAll(PDO::FETCH_COLUMN);
    }

    json_out([
        'ok' => true,
        'rank' => $rank ?: null,
        'temp_elevation' => $tempElev ?: null,
        'effective_rank' => $effectiveRank ?? 'civilian',
        'permissions' => $permissions,
    ]);
}

function handleListRanks(): void {
    global $db;
    $ranks = $db->query("SELECT * FROM military_ranks ORDER BY rank_tier ASC")->fetchAll();
    json_out(['ok' => true, 'ranks' => $ranks]);
}

function handleHistory(): void {
    global $db;
    requireAuth(5);
    $targetId = (int)($_GET['client_id'] ?? 0);
    $sql = "SELECT rh.*, CONCAT(tc.firstname, ' ', tc.lastname) AS performed_by_name,
                   CONCAT(tc2.firstname, ' ', tc2.lastname) AS subject_name
            FROM rank_history rh
            LEFT JOIN tblclients tc ON tc.id = rh.performed_by
            LEFT JOIN tblclients tc2 ON tc2.id = rh.client_id
            WHERE 1=1";
    $params = [];
    if ($targetId) { $sql .= " AND rh.client_id = ?"; $params[] = $targetId; }
    $sql .= " ORDER BY rh.performed_at DESC LIMIT 100";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    json_out(['ok' => true, 'history' => $stmt->fetchAll()]);
}

function handleFleetMap(): void {
    global $db;
    // Geographic distribution of ranked personnel
    $stmt = $db->query("
        SELECT ur.region, mr.rank_group,
               COUNT(*) as count,
               GROUP_CONCAT(DISTINCT mr.rank_name ORDER BY mr.rank_tier DESC) AS ranks_present
        FROM user_ranks ur
        JOIN military_ranks mr ON mr.rank_code = ur.rank_code
        WHERE ur.is_active = 1 AND ur.region IS NOT NULL AND ur.region != ''
        GROUP BY ur.region, mr.rank_group
        ORDER BY count DESC
    ");
    $geoData = $stmt->fetchAll();

    // Agent fleet — 48M+ rows, use fast approximate from information_schema
    $agentFleet = [];
    try {
        $estRows = (int)$db->query("SELECT TABLE_ROWS FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'alfred_agent_registry'")->fetchColumn();
        $agentFleet = [['domain' => 'all', 'agent_role' => 'all', 'count' => $estRows]];
    } catch (Exception $e) {}

    // Department distribution
    $deptData = $db->query("
        SELECT department, COUNT(*) as agents FROM dept_agents WHERE status = 'active' GROUP BY department ORDER BY agents DESC
    ")->fetchAll();

    json_out([
        'ok' => true,
        'personnel_by_region' => $geoData,
        'agent_fleet_by_domain' => $agentFleet,
        'departments' => $deptData,
    ]);
}

function handleStats(): void {
    global $db;
    $byRank = $db->query("
        SELECT mr.rank_code, mr.rank_name, mr.rank_tier, mr.rank_group, COUNT(ur.id) as personnel
        FROM military_ranks mr
        LEFT JOIN user_ranks ur ON ur.rank_code = mr.rank_code AND ur.is_active = 1
        GROUP BY mr.rank_code, mr.rank_name, mr.rank_tier, mr.rank_group
        ORDER BY mr.rank_tier ASC
    ")->fetchAll();

    $total = $db->query("SELECT COUNT(*) FROM user_ranks WHERE is_active = 1")->fetchColumn();
    $tempElevations = $db->query("SELECT COUNT(*) FROM rank_elevations WHERE is_active = 1 AND expires_at > NOW()")->fetchColumn();
    $recentPromotions = $db->query("SELECT COUNT(*) FROM rank_history WHERE action = 'promote' AND performed_at > DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();

    json_out([
        'ok' => true,
        'total_personnel' => (int)$total,
        'active_temp_elevations' => (int)$tempElevations,
        'promotions_last_30d' => (int)$recentPromotions,
        'by_rank' => $byRank,
    ]);
}
