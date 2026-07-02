<?php
/**
 * GSM Alfred OS — Geofencing Engine v1.0
 * GPS/Indoor positioning geofence system for Alfred Robot Fleet
 *
 * Endpoints:
 *   POST   ?action=create_zone       — Create geofence zone (polygon/circle/corridor)
 *   POST   ?action=update_zone       — Update existing zone
 *   POST   ?action=delete_zone       — Soft-delete a zone
 *   GET    ?action=zones              — List all zones (optionally by device/group)
 *   GET    ?action=zone               — Get single zone details
 *   POST   ?action=check_position     — Check if position is inside allowed zones
 *   POST   ?action=breach_report      — Report a geofence breach
 *   GET    ?action=breach_log         — Get breach history
 *   POST   ?action=assign_zone        — Assign zone to device or group
 *   POST   ?action=unassign_zone      — Remove zone assignment
 *   GET    ?action=device_zones       — Get all zones assigned to a device
 *   POST   ?action=set_home_base      — Set device home/dock position
 *   POST   ?action=return_to_base     — Command device to return to home base
 *
 * Supports: Polygon, Circle, Corridor (path with width), Indoor (floor/room)
 * Actions on breach: alert, slow_down, stop, return_to_base, escalate
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Internal-Secret, X-Device-Token');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

define('GOSITEME_API', true);
require_once __DIR__ . '/bootstrap.php';
geoEnsureSchema();

$auth = agentos_auth();
$action = $_GET['action'] ?? 'zones';

switch ($action) {
    case 'create_zone':    handleCreateZone($auth); break;
    case 'update_zone':    handleUpdateZone($auth); break;
    case 'delete_zone':    handleDeleteZone($auth); break;
    case 'zones':          handleZones($auth); break;
    case 'zone':           handleZone($auth); break;
    case 'check_position': handleCheckPosition($auth); break;
    case 'breach_report':  handleBreachReport($auth); break;
    case 'breach_log':     handleBreachLog($auth); break;
    case 'assign_zone':    handleAssignZone($auth); break;
    case 'unassign_zone':  handleUnassignZone($auth); break;
    case 'device_zones':   handleDeviceZones($auth); break;
    case 'set_home_base':  handleSetHomeBase($auth); break;
    case 'return_to_base': handleReturnToBase($auth); break;
    default:               agentos_error('Unknown action');
}

// ── Schema ─────────────────────────────────────────────────────

function geoEnsureSchema(): void {
    static $done = false;
    if ($done) return;
    $done = true;

    $pdo = agentos_pdo();
    $r = $pdo->query("SHOW TABLES LIKE 'agentos_geofence_zones'");
    if ($r->rowCount() > 0) return;

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS agentos_geofence_zones (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            zone_id         VARCHAR(64) NOT NULL UNIQUE,
            name            VARCHAR(256) NOT NULL,
            description     TEXT,
            zone_type       ENUM('polygon','circle','corridor','indoor','exclusion') NOT NULL DEFAULT 'polygon',
            geometry        JSON NOT NULL COMMENT 'Coordinates defining the zone boundary',
            properties      JSON COMMENT 'Speed limits, behavior rules, etc.',
            floor_level     INT DEFAULT 0 COMMENT 'For indoor zones, floor number',
            altitude_min_m  DECIMAL(8,2) COMMENT 'Min altitude for 3D zones',
            altitude_max_m  DECIMAL(8,2) COMMENT 'Max altitude for 3D zones',
            breach_action   ENUM('alert','slow_down','stop','return_to_base','escalate') NOT NULL DEFAULT 'stop',
            max_speed_ms    DECIMAL(6,3) DEFAULT 1.5 COMMENT 'Speed limit within zone',
            is_active       TINYINT(1) NOT NULL DEFAULT 1,
            priority        INT UNSIGNED NOT NULL DEFAULT 100 COMMENT 'Higher = takes precedence',
            schedule_json   JSON COMMENT 'Time-based activation schedule',
            created_by      INT UNSIGNED,
            created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_type (zone_type),
            INDEX idx_active (is_active),
            INDEX idx_priority (priority DESC)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS agentos_geofence_assignments (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            zone_id         VARCHAR(64) NOT NULL,
            target_id       VARCHAR(128) NOT NULL COMMENT 'device_id or group_id',
            target_type     ENUM('device','group') NOT NULL DEFAULT 'device',
            relationship    ENUM('allowed','denied','restricted') NOT NULL DEFAULT 'allowed',
            is_active       TINYINT(1) NOT NULL DEFAULT 1,
            created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_zone_target (zone_id, target_id),
            INDEX idx_target (target_id),
            INDEX idx_zone (zone_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS agentos_geofence_breaches (
            id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            breach_id       VARCHAR(64) NOT NULL UNIQUE,
            device_id       VARCHAR(128) NOT NULL,
            zone_id         VARCHAR(64) NOT NULL,
            breach_type     ENUM('entered_denied','exited_allowed','speed_exceeded','altitude_breach','schedule_violation') NOT NULL,
            severity        ENUM('info','warning','critical','emergency') NOT NULL DEFAULT 'warning',
            latitude        DECIMAL(10,8) NOT NULL,
            longitude       DECIMAL(11,8) NOT NULL,
            altitude_m      DECIMAL(8,2),
            speed_ms        DECIMAL(6,3),
            heading_deg     DECIMAL(5,2),
            action_taken    ENUM('alert','slow_down','stop','return_to_base','escalate') NOT NULL,
            resolved        TINYINT(1) NOT NULL DEFAULT 0,
            resolved_at     TIMESTAMP NULL,
            created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_device (device_id),
            INDEX idx_zone (zone_id),
            INDEX idx_created (created_at),
            INDEX idx_severity (severity)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS agentos_device_home_base (
            device_id       VARCHAR(128) NOT NULL PRIMARY KEY,
            latitude        DECIMAL(10,8) NOT NULL,
            longitude       DECIMAL(11,8) NOT NULL,
            altitude_m      DECIMAL(8,2),
            floor_level     INT DEFAULT 0,
            dock_heading    DECIMAL(5,2) COMMENT 'Heading to align with dock',
            name            VARCHAR(256) DEFAULT 'Home Base',
            return_speed_ms DECIMAL(6,3) DEFAULT 0.5,
            created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    error_log("[AGENTOS-GEO] Schema auto-migrated");
}

// ── Handlers ───────────────────────────────────────────────────

function handleCreateZone(array $auth): void {
    if (!$auth['is_internal'] && !geoIsAdmin($auth)) {
        agentos_error('Admin access required', 403);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) agentos_error('Invalid JSON body');

    $name = trim($input['name'] ?? '');
    if (!$name) agentos_error('Zone name required');

    $zoneType = $input['zone_type'] ?? 'polygon';
    if (!in_array($zoneType, ['polygon','circle','corridor','indoor','exclusion'])) {
        agentos_error('Invalid zone_type');
    }

    $geometry = $input['geometry'] ?? [];
    if (!validateGeometry($zoneType, $geometry)) {
        agentos_error('Invalid geometry for zone type');
    }

    $breachAction = $input['breach_action'] ?? 'stop';
    if (!in_array($breachAction, ['alert','slow_down','stop','return_to_base','escalate'])) {
        agentos_error('Invalid breach_action');
    }

    $zoneId = agentos_id('zone');
    $pdo = agentos_pdo();

    $stmt = $pdo->prepare("INSERT INTO agentos_geofence_zones 
        (zone_id, name, description, zone_type, geometry, properties, floor_level,
         altitude_min_m, altitude_max_m, breach_action, max_speed_ms, priority, schedule_json, created_by)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([
        $zoneId, $name, $input['description'] ?? null, $zoneType,
        json_encode($geometry), json_encode($input['properties'] ?? []),
        intval($input['floor_level'] ?? 0),
        $input['altitude_min_m'] ?? null, $input['altitude_max_m'] ?? null,
        $breachAction,
        min(floatval($input['max_speed_ms'] ?? 1.5), 2.0),
        intval($input['priority'] ?? 100),
        $input['schedule'] ? json_encode($input['schedule']) : null,
        $auth['user_id']
    ]);

    agentos_audit([
        'action_type' => 'geofence_create',
        'user_id' => $auth['user_id'],
        'risk_level' => 'high',
        'status' => 'completed',
        'input' => ['name' => $name, 'type' => $zoneType],
        'output' => ['zone_id' => $zoneId]
    ]);

    agentos_respond(['ok' => true, 'zone_id' => $zoneId]);
}

function handleUpdateZone(array $auth): void {
    if (!$auth['is_internal'] && !geoIsAdmin($auth)) {
        agentos_error('Admin access required', 403);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $zoneId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['zone_id'] ?? '');
    if (!$zoneId) agentos_error('zone_id required');

    $updates = [];
    $params = [];

    $allowedFields = ['name','description','geometry','properties','breach_action','max_speed_ms','priority','is_active','schedule_json'];
    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            if (in_array($field, ['geometry','properties','schedule_json'])) {
                $updates[] = "$field = ?";
                $params[] = json_encode($input[$field]);
            } else {
                $updates[] = "$field = ?";
                $params[] = $input[$field];
            }
        }
    }

    if (empty($updates)) agentos_error('No fields to update');

    $params[] = $zoneId;
    $pdo = agentos_pdo();
    $pdo->prepare("UPDATE agentos_geofence_zones SET " . implode(', ', $updates) . " WHERE zone_id = ?")
        ->execute($params);

    // Notify all devices assigned to this zone
    $devices = $pdo->prepare("SELECT target_id FROM agentos_geofence_assignments WHERE zone_id = ? AND target_type = 'device' AND is_active = 1");
    $devices->execute([$zoneId]);
    foreach ($devices->fetchAll(PDO::FETCH_COLUMN) as $devId) {
        agentos_push("device:{$devId}", 'geofence_updated', ['zone_id' => $zoneId]);
    }

    agentos_respond(['ok' => true, 'zone_id' => $zoneId]);
}

function handleDeleteZone(array $auth): void {
    if (!$auth['is_internal'] && !geoIsAdmin($auth)) {
        agentos_error('Admin access required', 403);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $zoneId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['zone_id'] ?? '');
    if (!$zoneId) agentos_error('zone_id required');

    $pdo = agentos_pdo();
    $pdo->prepare("UPDATE agentos_geofence_zones SET is_active = 0 WHERE zone_id = ?")->execute([$zoneId]);
    $pdo->prepare("UPDATE agentos_geofence_assignments SET is_active = 0 WHERE zone_id = ?")->execute([$zoneId]);

    agentos_respond(['ok' => true, 'deleted' => $zoneId]);
}

function handleZones(array $auth): void {
    $pdo = agentos_pdo();
    $type = $_GET['type'] ?? null;

    $sql = "SELECT zone_id, name, description, zone_type, geometry, breach_action, 
                   max_speed_ms, priority, is_active, created_at
            FROM agentos_geofence_zones WHERE is_active = 1";
    $params = [];

    if ($type && in_array($type, ['polygon','circle','corridor','indoor','exclusion'])) {
        $sql .= " AND zone_type = ?";
        $params[] = $type;
    }
    $sql .= " ORDER BY priority DESC, created_at DESC LIMIT 100";

    $stmt = $pdo->prepare($sql);
    dbExecute($stmt, $params);
    $zones = $stmt->fetchAll();

    foreach ($zones as &$z) {
        $z['geometry'] = json_decode($z['geometry'], true);
    }

    agentos_respond(['ok' => true, 'zones' => $zones]);
}

function handleZone(array $auth): void {
    $pdo = agentos_pdo();
    $zoneId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['zone_id'] ?? '');
    if (!$zoneId) agentos_error('zone_id required');

    $stmt = $pdo->prepare("SELECT * FROM agentos_geofence_zones WHERE zone_id = ?");
    $stmt->execute([$zoneId]);
    $zone = $stmt->fetch();
    if (!$zone) agentos_error('Zone not found', 404);

    $zone['geometry'] = json_decode($zone['geometry'], true);
    $zone['properties'] = json_decode($zone['properties'], true);
    $zone['schedule_json'] = json_decode($zone['schedule_json'], true);

    // Get assignments
    $assStmt = $pdo->prepare("SELECT target_id, target_type, relationship FROM agentos_geofence_assignments WHERE zone_id = ? AND is_active = 1");
    $assStmt->execute([$zoneId]);
    $zone['assignments'] = $assStmt->fetchAll();

    agentos_respond(['ok' => true, 'zone' => $zone]);
}

function handleCheckPosition(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['device_id'] ?? '');
    $lat = floatval($input['latitude'] ?? 0);
    $lng = floatval($input['longitude'] ?? 0);
    $alt = isset($input['altitude_m']) ? floatval($input['altitude_m']) : null;
    $speed = floatval($input['speed_ms'] ?? 0);

    if (!$deviceId) agentos_error('device_id required');
    if ($lat == 0 && $lng == 0) agentos_error('Valid latitude/longitude required');

    $pdo = agentos_pdo();

    // Get zones assigned to this device
    $assignedZones = $pdo->prepare("
        SELECT z.*, a.relationship
        FROM agentos_geofence_zones z
        JOIN agentos_geofence_assignments a ON z.zone_id = a.zone_id
        WHERE a.target_id = ? AND a.is_active = 1 AND z.is_active = 1
        ORDER BY z.priority DESC
    ");
    $assignedZones->execute([$deviceId]);
    $zones = $assignedZones->fetchAll();

    $result = [
        'inside_zones' => [],
        'violations' => [],
        'max_allowed_speed' => 1.5,
        'action_required' => null
    ];

    foreach ($zones as $zone) {
        $geometry = json_decode($zone['geometry'], true);
        $inside = isPointInZone($lat, $lng, $zone['zone_type'], $geometry);
        $zoneInfo = ['zone_id' => $zone['zone_id'], 'name' => $zone['name'], 'relationship' => $zone['relationship']];

        if ($inside) {
            $result['inside_zones'][] = $zoneInfo;

            // Check for violations
            if ($zone['relationship'] === 'denied' || $zone['zone_type'] === 'exclusion') {
                $result['violations'][] = [
                    'type' => 'entered_denied',
                    'zone' => $zoneInfo,
                    'action' => $zone['breach_action']
                ];
                $result['action_required'] = $zone['breach_action'];
            }

            // Speed check
            if ($speed > floatval($zone['max_speed_ms'])) {
                $result['violations'][] = [
                    'type' => 'speed_exceeded',
                    'zone' => $zoneInfo,
                    'current_speed' => $speed,
                    'max_speed' => floatval($zone['max_speed_ms'])
                ];
            }

            $result['max_allowed_speed'] = min($result['max_allowed_speed'], floatval($zone['max_speed_ms']));
        } else {
            // Check if device left an allowed zone
            if ($zone['relationship'] === 'allowed') {
                $result['violations'][] = [
                    'type' => 'exited_allowed',
                    'zone' => $zoneInfo,
                    'action' => $zone['breach_action']
                ];
                if (!$result['action_required'] || getPriorityOf($zone['breach_action']) > getPriorityOf($result['action_required'])) {
                    $result['action_required'] = $zone['breach_action'];
                }
            }
        }
    }

    // Auto-report breaches
    if (!empty($result['violations'])) {
        foreach ($result['violations'] as $v) {
            autoReportBreach($deviceId, $v, $lat, $lng, $alt, $speed, $input['heading_deg'] ?? null);
        }
    }

    agentos_respond(['ok' => true, 'device_id' => $deviceId, 'position_check' => $result]);
}

function handleBreachReport(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['device_id'] ?? '');
    $zoneId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['zone_id'] ?? '');

    if (!$deviceId || !$zoneId) agentos_error('device_id and zone_id required');

    $breachId = autoReportBreach(
        $deviceId,
        ['type' => $input['breach_type'] ?? 'entered_denied', 'action' => $input['action_taken'] ?? 'stop', 'zone' => ['zone_id' => $zoneId]],
        floatval($input['latitude'] ?? 0),
        floatval($input['longitude'] ?? 0),
        isset($input['altitude_m']) ? floatval($input['altitude_m']) : null,
        floatval($input['speed_ms'] ?? 0),
        isset($input['heading_deg']) ? floatval($input['heading_deg']) : null
    );

    agentos_respond(['ok' => true, 'breach_id' => $breachId]);
}

function handleBreachLog(array $auth): void {
    $pdo = agentos_pdo();
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['device_id'] ?? '');
    $limit = min(100, max(1, intval($_GET['limit'] ?? 20)));

    $sql = "SELECT breach_id, device_id, zone_id, breach_type, severity,
                   latitude, longitude, speed_ms, action_taken, created_at
            FROM agentos_geofence_breaches";
    $params = [];

    if ($deviceId) {
        $sql .= " WHERE device_id = ?";
        $params[] = $deviceId;
    }
    $sql .= " ORDER BY created_at DESC LIMIT ?";
    $params[] = $limit;

    $stmt = $pdo->prepare($sql);
    dbExecute($stmt, $params);

    agentos_respond(['ok' => true, 'breaches' => $stmt->fetchAll()]);
}

function handleAssignZone(array $auth): void {
    if (!$auth['is_internal'] && !geoIsAdmin($auth)) {
        agentos_error('Admin access required', 403);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $zoneId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['zone_id'] ?? '');
    $targetId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['target_id'] ?? '');
    $targetType = $input['target_type'] ?? 'device';
    $relationship = $input['relationship'] ?? 'allowed';

    if (!$zoneId || !$targetId) agentos_error('zone_id and target_id required');
    if (!in_array($targetType, ['device','group'])) agentos_error('Invalid target_type');
    if (!in_array($relationship, ['allowed','denied','restricted'])) agentos_error('Invalid relationship');

    $pdo = agentos_pdo();
    $pdo->prepare("INSERT INTO agentos_geofence_assignments 
        (zone_id, target_id, target_type, relationship)
        VALUES (?,?,?,?)
        ON DUPLICATE KEY UPDATE relationship = VALUES(relationship), is_active = 1")
        ->execute([$zoneId, $targetId, $targetType, $relationship]);

    // Push zone data to device
    if ($targetType === 'device') {
        $zoneStmt = $pdo->prepare("SELECT geometry, zone_type, breach_action, max_speed_ms FROM agentos_geofence_zones WHERE zone_id = ?");
        $zoneStmt->execute([$zoneId]);
        $zoneData = $zoneStmt->fetch();
        if ($zoneData) {
            agentos_push("device:{$targetId}", 'geofence_assigned', [
                'zone_id' => $zoneId,
                'zone_type' => $zoneData['zone_type'],
                'geometry' => json_decode($zoneData['geometry'], true),
                'relationship' => $relationship,
                'breach_action' => $zoneData['breach_action'],
                'max_speed_ms' => $zoneData['max_speed_ms']
            ]);
        }
    }

    agentos_respond(['ok' => true, 'assigned' => true]);
}

function handleUnassignZone(array $auth): void {
    if (!$auth['is_internal'] && !geoIsAdmin($auth)) {
        agentos_error('Admin access required', 403);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $zoneId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['zone_id'] ?? '');
    $targetId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['target_id'] ?? '');

    $pdo = agentos_pdo();
    $pdo->prepare("UPDATE agentos_geofence_assignments SET is_active = 0 WHERE zone_id = ? AND target_id = ?")
        ->execute([$zoneId, $targetId]);

    agentos_respond(['ok' => true, 'unassigned' => true]);
}

function handleDeviceZones(array $auth): void {
    $pdo = agentos_pdo();
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['device_id'] ?? '');
    if (!$deviceId) agentos_error('device_id required');

    $stmt = $pdo->prepare("
        SELECT z.zone_id, z.name, z.zone_type, z.geometry, z.breach_action, z.max_speed_ms,
               a.relationship, z.priority
        FROM agentos_geofence_zones z
        JOIN agentos_geofence_assignments a ON z.zone_id = a.zone_id
        WHERE a.target_id = ? AND a.is_active = 1 AND z.is_active = 1
        ORDER BY z.priority DESC
    ");
    $stmt->execute([$deviceId]);
    $zones = $stmt->fetchAll();

    foreach ($zones as &$z) {
        $z['geometry'] = json_decode($z['geometry'], true);
    }

    // Get home base
    $homeStmt = $pdo->prepare("SELECT * FROM agentos_device_home_base WHERE device_id = ?");
    $homeStmt->execute([$deviceId]);
    $home = $homeStmt->fetch();

    agentos_respond(['ok' => true, 'device_id' => $deviceId, 'zones' => $zones, 'home_base' => $home ?: null]);
}

function handleSetHomeBase(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['device_id'] ?? '');
    $lat = floatval($input['latitude'] ?? 0);
    $lng = floatval($input['longitude'] ?? 0);

    if (!$deviceId) agentos_error('device_id required');
    if ($lat == 0 && $lng == 0) agentos_error('Valid latitude/longitude required');

    $pdo = agentos_pdo();
    $pdo->prepare("INSERT INTO agentos_device_home_base 
        (device_id, latitude, longitude, altitude_m, floor_level, dock_heading, name, return_speed_ms)
        VALUES (?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE latitude = VALUES(latitude), longitude = VALUES(longitude),
        altitude_m = VALUES(altitude_m), floor_level = VALUES(floor_level),
        dock_heading = VALUES(dock_heading), name = VALUES(name), return_speed_ms = VALUES(return_speed_ms)")
        ->execute([
            $deviceId, $lat, $lng,
            $input['altitude_m'] ?? null,
            intval($input['floor_level'] ?? 0),
            $input['dock_heading'] ?? null,
            $input['name'] ?? 'Home Base',
            min(floatval($input['return_speed_ms'] ?? 0.5), 1.0)
        ]);

    agentos_respond(['ok' => true, 'device_id' => $deviceId, 'home_base_set' => true]);
}

function handleReturnToBase(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['device_id'] ?? '');
    if (!$deviceId) agentos_error('device_id required');

    $pdo = agentos_pdo();
    $stmt = $pdo->prepare("SELECT * FROM agentos_device_home_base WHERE device_id = ?");
    $stmt->execute([$deviceId]);
    $home = $stmt->fetch();

    if (!$home) agentos_error('No home base configured for this device');

    agentos_push("device:{$deviceId}", 'RETURN_TO_BASE', [
        'latitude' => floatval($home['latitude']),
        'longitude' => floatval($home['longitude']),
        'altitude_m' => $home['altitude_m'] ? floatval($home['altitude_m']) : null,
        'dock_heading' => $home['dock_heading'] ? floatval($home['dock_heading']) : null,
        'max_speed_ms' => floatval($home['return_speed_ms']),
        'reason' => $input['reason'] ?? 'manual_recall',
        'timestamp' => microtime(true)
    ]);

    agentos_respond(['ok' => true, 'device_id' => $deviceId, 'returning_to' => $home['name']]);
}

// ── Geometry Helpers ───────────────────────────────────────────

function validateGeometry(string $type, array $geometry): bool {
    switch ($type) {
        case 'polygon':
        case 'exclusion':
            // Need at least 3 points: [{lat, lng}, ...]
            if (count($geometry) < 3) return false;
            foreach ($geometry as $pt) {
                if (!isset($pt['lat']) || !isset($pt['lng'])) return false;
            }
            return true;

        case 'circle':
            // Need center + radius: {center: {lat, lng}, radius_m: X}
            return isset($geometry['center']['lat']) && isset($geometry['center']['lng']) &&
                   isset($geometry['radius_m']) && $geometry['radius_m'] > 0;

        case 'corridor':
            // Need path + width: {path: [{lat, lng}, ...], width_m: X}
            return isset($geometry['path']) && count($geometry['path']) >= 2 &&
                   isset($geometry['width_m']) && $geometry['width_m'] > 0;

        case 'indoor':
            // Need room boundary + floor: {boundary: [{x, y}, ...], floor: N}
            return isset($geometry['boundary']) && count($geometry['boundary']) >= 3;

        default:
            return false;
    }
}

function isPointInZone(float $lat, float $lng, string $type, array $geometry): bool {
    switch ($type) {
        case 'circle':
            $clat = floatval($geometry['center']['lat']);
            $clng = floatval($geometry['center']['lng']);
            $radius = floatval($geometry['radius_m']);
            $distance = haversineDistance($lat, $lng, $clat, $clng);
            return $distance <= $radius;

        case 'polygon':
        case 'exclusion':
            return pointInPolygon($lat, $lng, $geometry);

        case 'corridor':
            $path = $geometry['path'];
            $width = floatval($geometry['width_m']);
            foreach ($path as $i => $pt) {
                if (!isset($path[$i + 1])) break;
                $dist = pointToSegmentDistance($lat, $lng,
                    floatval($pt['lat']), floatval($pt['lng']),
                    floatval($path[$i + 1]['lat']), floatval($path[$i + 1]['lng']));
                if ($dist <= $width / 2) return true;
            }
            return false;

        case 'indoor':
            // Indoor uses local x/y coordinates
            return pointInPolygon($lat, $lng, $geometry['boundary'] ?? []);

        default:
            return false;
    }
}

function pointInPolygon(float $lat, float $lng, array $polygon): bool {
    $n = count($polygon);
    if ($n < 3) return false;

    $inside = false;
    for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
        $yi = floatval($polygon[$i]['lat'] ?? $polygon[$i]['y'] ?? 0);
        $xi = floatval($polygon[$i]['lng'] ?? $polygon[$i]['x'] ?? 0);
        $yj = floatval($polygon[$j]['lat'] ?? $polygon[$j]['y'] ?? 0);
        $xj = floatval($polygon[$j]['lng'] ?? $polygon[$j]['x'] ?? 0);

        if (($yi > $lng) !== ($yj > $lng) &&
            $lat < ($xj - $xi) * ($lng - $yi) / ($yj - $yi) + $xi) {
            $inside = !$inside;
        }
    }
    return $inside;
}

function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float {
    $R = 6371000; // Earth radius in meters
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLng / 2) * sin($dLng / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $R * $c;
}

function pointToSegmentDistance(float $lat, float $lng, float $lat1, float $lng1, float $lat2, float $lng2): float {
    $d1 = haversineDistance($lat, $lng, $lat1, $lng1);
    $d2 = haversineDistance($lat, $lng, $lat2, $lng2);
    $dSeg = haversineDistance($lat1, $lng1, $lat2, $lng2);

    if ($dSeg == 0) return $d1;

    $t = max(0, min(1, (($lat - $lat1) * ($lat2 - $lat1) + ($lng - $lng1) * ($lng2 - $lng1)) /
        (($lat2 - $lat1) * ($lat2 - $lat1) + ($lng2 - $lng1) * ($lng2 - $lng1))));

    $projLat = $lat1 + $t * ($lat2 - $lat1);
    $projLng = $lng1 + $t * ($lng2 - $lng1);

    return haversineDistance($lat, $lng, $projLat, $projLng);
}

function getPriorityOf(string $action): int {
    $map = ['alert' => 1, 'slow_down' => 2, 'stop' => 3, 'return_to_base' => 4, 'escalate' => 5];
    return $map[$action] ?? 0;
}

function autoReportBreach(string $deviceId, array $violation, float $lat, float $lng, ?float $alt, float $speed, ?float $heading): string {
    $pdo = agentos_pdo();
    $breachId = agentos_id('breach');
    $severity = ($violation['action'] ?? '') === 'escalate' ? 'emergency' :
                (($violation['action'] ?? '') === 'stop' ? 'critical' : 'warning');

    $pdo->prepare("INSERT INTO agentos_geofence_breaches 
        (breach_id, device_id, zone_id, breach_type, severity, latitude, longitude,
         altitude_m, speed_ms, heading_deg, action_taken)
        VALUES (?,?,?,?,?,?,?,?,?,?,?)")
        ->execute([
            $breachId, $deviceId,
            $violation['zone']['zone_id'] ?? 'unknown',
            $violation['type'] ?? 'entered_denied',
            $severity, $lat, $lng, $alt, $speed, $heading,
            $violation['action'] ?? 'alert'
        ]);

    // Push breach alert
    agentos_push("device:{$deviceId}", 'GEOFENCE_BREACH', [
        'breach_id' => $breachId,
        'action' => $violation['action'] ?? 'alert',
        'zone_id' => $violation['zone']['zone_id'] ?? 'unknown'
    ]);

    agentos_push('fleet:safety', 'geofence_breach', [
        'device_id' => $deviceId,
        'breach_id' => $breachId,
        'severity' => $severity
    ]);

    return $breachId;
}

function geoIsAdmin(array $auth): bool {
    if (!$auth['user_id']) return false;
    $pdo = agentos_pdo();
    try {
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$auth['user_id']]);
        $role = $stmt->fetchColumn();
        return in_array($role, ['admin', 'supreme_admin', 'owner']);
    } catch (\Throwable $e) {
        return false;
    }
}
