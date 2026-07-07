<?php
/**
 * GSM Alfred OS — V2X Communication System
 *
 * Vehicle-to-Everything communication for road-going and sidewalk robots.
 * Supports V2V (vehicle), V2I (infrastructure), V2P (pedestrian), V2N (network).
 * Manages intersection coordination, traffic signal phase data, collision warnings.
 *
 * Endpoints (14):
 *   broadcast             — Broadcast V2X message
 *   nearby                — Get nearby V2X entities
 *   intersection_status   — Query intersection signal state
 *   register_intersection — Register infrastructure intersection
 *   intersections         — List known intersections
 *   collision_warning     — Send/receive collision warnings
 *   platoon               — Manage platoon/convoy operations
 *   speed_advisory        — Get speed advisory for road segment
 *   road_conditions       — Report/query road conditions
 *   emergency_vehicle     — Handle emergency vehicle alerts
 *   pedestrian_alert      — Send pedestrian proximity alert
 *   message_log           — View V2X message history
 *   channel_status        — Get DSRC/C-V2X channel status
 *   v2x_stats             — System statistics
 */

declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = ['https://gositeme.com','https://www.gositeme.com'];
if (in_array($origin, $allowed, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET,POST,PUT,OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type,Authorization,X-Internal-Secret');
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// ─── Schema ─────────────────────────────────────────────────────
function v2xEnsureSchema(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    $pdo = agentos_pdo();

    $pdo->exec("CREATE TABLE IF NOT EXISTS agentos_v2x_messages (
        id VARCHAR(32) PRIMARY KEY,
        sender_id VARCHAR(32) NOT NULL,
        sender_type ENUM('robot','vehicle','infrastructure','pedestrian','network','emergency') NOT NULL,
        message_type ENUM('bsm','spat','map','tim','psm','eva','rsm','srm','ssm','custom') NOT NULL,
        channel ENUM('dsrc','cv2x','wifi_direct','cellular','bluetooth','mesh') NOT NULL DEFAULT 'cv2x',
        priority ENUM('critical','high','normal','low') NOT NULL DEFAULT 'normal',
        latitude DOUBLE,
        longitude DOUBLE,
        heading FLOAT,
        speed_mps FLOAT,
        payload JSON NOT NULL,
        ttl_seconds INT DEFAULT 10,
        radius_m FLOAT DEFAULT 300,
        acknowledged TINYINT(1) DEFAULT 0,
        created_at DATETIME NOT NULL,
        INDEX idx_sender (sender_id),
        INDEX idx_type (message_type),
        INDEX idx_location (latitude, longitude),
        INDEX idx_created (created_at),
        INDEX idx_priority (priority)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS agentos_v2x_entities (
        id VARCHAR(32) PRIMARY KEY,
        entity_id VARCHAR(64) NOT NULL UNIQUE,
        entity_type ENUM('robot','vehicle','infrastructure','pedestrian','emergency','cyclist','scooter') NOT NULL,
        entity_class VARCHAR(32),
        latitude DOUBLE NOT NULL,
        longitude DOUBLE NOT NULL,
        altitude FLOAT,
        heading FLOAT,
        speed_mps FLOAT DEFAULT 0,
        acceleration_mps2 FLOAT DEFAULT 0,
        dimensions_json JSON,
        status ENUM('active','idle','parked','emergency','offline') NOT NULL DEFAULT 'active',
        last_seen DATETIME NOT NULL,
        capabilities JSON,
        INDEX idx_entity (entity_id),
        INDEX idx_type (entity_type),
        INDEX idx_location (latitude, longitude),
        INDEX idx_seen (last_seen)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS agentos_v2x_intersections (
        id VARCHAR(32) PRIMARY KEY,
        intersection_id VARCHAR(32) NOT NULL UNIQUE,
        name VARCHAR(128),
        latitude DOUBLE NOT NULL,
        longitude DOUBLE NOT NULL,
        signal_type ENUM('traffic_light','stop_sign','yield','roundabout','uncontrolled','pedestrian_crossing','railway_crossing') NOT NULL DEFAULT 'traffic_light',
        current_phase ENUM('green','yellow','red','flashing_red','flashing_yellow','off','pedestrian_walk','pedestrian_countdown') DEFAULT 'red',
        phase_remaining_sec FLOAT,
        phase_schedule JSON,
        approach_count INT DEFAULT 4,
        speed_limit_mps FLOAT,
        geometry JSON,
        active TINYINT(1) DEFAULT 1,
        last_updated DATETIME NOT NULL,
        INDEX idx_intersection (intersection_id),
        INDEX idx_location (latitude, longitude)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS agentos_v2x_collisions (
        id VARCHAR(32) PRIMARY KEY,
        reporter_id VARCHAR(32) NOT NULL,
        threat_entity_id VARCHAR(32),
        threat_type ENUM('forward_collision','intersection_collision','lane_change','pedestrian_crossing','blind_spot','rear_end','head_on','side_swipe','stationary_obstacle') NOT NULL,
        severity ENUM('advisory','warning','critical','imminent') NOT NULL DEFAULT 'warning',
        reporter_lat DOUBLE,
        reporter_lon DOUBLE,
        reporter_speed_mps FLOAT,
        threat_lat DOUBLE,
        threat_lon DOUBLE,
        threat_speed_mps FLOAT,
        time_to_collision_sec FLOAT,
        distance_m FLOAT,
        action_taken ENUM('none','decelerate','brake','emergency_brake','swerve','stop','yield') DEFAULT 'none',
        resolved TINYINT(1) DEFAULT 0,
        created_at DATETIME NOT NULL,
        INDEX idx_reporter (reporter_id),
        INDEX idx_severity (severity),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS agentos_v2x_road_conditions (
        id VARCHAR(32) PRIMARY KEY,
        reporter_id VARCHAR(32) NOT NULL,
        latitude DOUBLE NOT NULL,
        longitude DOUBLE NOT NULL,
        radius_m FLOAT DEFAULT 50,
        condition_type ENUM('ice','snow','rain','fog','pothole','debris','construction','flooding','accident','closure','slow_traffic','congestion','animal','poor_visibility') NOT NULL,
        severity ENUM('low','moderate','high','extreme') NOT NULL DEFAULT 'moderate',
        description VARCHAR(256),
        confirmed_count INT DEFAULT 1,
        active TINYINT(1) DEFAULT 1,
        expires_at DATETIME,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        INDEX idx_location (latitude, longitude),
        INDEX idx_type (condition_type),
        INDEX idx_active (active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

v2xEnsureSchema();
$auth = agentos_auth();

// ─── Helpers ─────────────────────────────────────────────────
function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float {
    $R = 6371000;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
}

// ─── Handlers ───────────────────────────────────────────────────

function handleBroadcast(): void {
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);

    $senderId = $data['sender_id'] ?? '';
    if (!$senderId) agentos_error('Missing sender_id');

    $id = agentos_id('v2x');
    $lat = (float)($data['latitude'] ?? 0);
    $lon = (float)($data['longitude'] ?? 0);
    $radius = (float)($data['radius_m'] ?? 300);

    $stmt = $pdo->prepare('INSERT INTO agentos_v2x_messages (id, sender_id, sender_type, message_type, channel, priority, latitude, longitude, heading, speed_mps, payload, ttl_seconds, radius_m, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())');
    $stmt->execute([
        $id, $senderId,
        $data['sender_type'] ?? 'robot',
        $data['message_type'] ?? 'bsm',
        $data['channel'] ?? 'cv2x',
        $data['priority'] ?? 'normal',
        $lat, $lon,
        (float)($data['heading'] ?? 0),
        (float)($data['speed_mps'] ?? 0),
        json_encode($data['payload'] ?? []),
        (int)($data['ttl_seconds'] ?? 10),
        $radius
    ]);

    // Update entity position
    $pdo->prepare('INSERT INTO agentos_v2x_entities (id, entity_id, entity_type, latitude, longitude, heading, speed_mps, last_seen) VALUES (?,?,?,?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE latitude = VALUES(latitude), longitude = VALUES(longitude), heading = VALUES(heading), speed_mps = VALUES(speed_mps), last_seen = NOW()')
        ->execute([
            agentos_id('ent'), $senderId, $data['sender_type'] ?? 'robot',
            $lat, $lon, (float)($data['heading'] ?? 0), (float)($data['speed_mps'] ?? 0)
        ]);

    // Find nearby entities to relay to
    $stmt = $pdo->prepare("SELECT entity_id FROM agentos_v2x_entities WHERE entity_id != ? AND last_seen > DATE_SUB(NOW(), INTERVAL 30 SECOND)");
    $stmt->execute([$senderId]);
    $nearby = $stmt->fetchAll();

    $relayed = 0;
    foreach ($nearby as $entity) {
        agentos_push("device:{$entity['entity_id']}", 'v2x_message', [
            'message_id' => $id, 'sender_id' => $senderId,
            'message_type' => $data['message_type'] ?? 'bsm',
            'priority' => $data['priority'] ?? 'normal',
            'payload' => $data['payload'] ?? []
        ]);
        $relayed++;
    }

    agentos_respond(['ok' => true, 'message_id' => $id, 'relayed_to' => $relayed], 201);
}

function handleNearby(): void {
    $pdo = agentos_pdo();
    $lat = (float)($_GET['latitude'] ?? 0);
    $lon = (float)($_GET['longitude'] ?? 0);
    $radius = min((float)($_GET['radius_m'] ?? 500), 5000);

    // Get recently active entities
    $stmt = $pdo->prepare("SELECT * FROM agentos_v2x_entities WHERE last_seen > DATE_SUB(NOW(), INTERVAL 60 SECOND) ORDER BY last_seen DESC");
    $stmt->execute();
    $all = $stmt->fetchAll();

    $nearby = [];
    foreach ($all as $e) {
        $dist = haversineDistance($lat, $lon, (float)$e['latitude'], (float)$e['longitude']);
        if ($dist <= $radius) {
            $e['distance_m'] = round($dist, 1);
            $e['dimensions_json'] = json_decode($e['dimensions_json'] ?? '{}', true);
            $e['capabilities'] = json_decode($e['capabilities'] ?? '[]', true);
            $nearby[] = $e;
        }
    }

    usort($nearby, fn($a, $b) => $a['distance_m'] <=> $b['distance_m']);

    agentos_respond(['ok' => true, 'nearby_entities' => $nearby, 'count' => count($nearby), 'search_radius_m' => $radius]);
}

function handleIntersectionStatus(): void {
    $pdo = agentos_pdo();
    $intersectionId = $_GET['intersection_id'] ?? '';
    if (!$intersectionId) agentos_error('Missing intersection_id');

    $stmt = $pdo->prepare('SELECT * FROM agentos_v2x_intersections WHERE intersection_id = ?');
    $stmt->execute([$intersectionId]);
    $inter = $stmt->fetch();
    if (!$inter) agentos_error('Intersection not found', 404);

    $inter['phase_schedule'] = json_decode($inter['phase_schedule'] ?? '[]', true);
    $inter['geometry'] = json_decode($inter['geometry'] ?? '{}', true);

    // SPaT (Signal Phase and Timing) data
    $spat = [
        'intersection_id' => $inter['intersection_id'],
        'current_phase' => $inter['current_phase'],
        'time_remaining_sec' => (float)($inter['phase_remaining_sec'] ?? 0),
        'speed_limit_mps' => (float)($inter['speed_limit_mps'] ?? 0),
        'recommendation' => 'proceed'
    ];

    if ($inter['current_phase'] === 'red' || $inter['current_phase'] === 'flashing_red') {
        $spat['recommendation'] = 'stop';
    } elseif ($inter['current_phase'] === 'yellow') {
        $spat['recommendation'] = ($inter['phase_remaining_sec'] ?? 0) > 3 ? 'caution' : 'stop';
    }

    agentos_respond(['ok' => true, 'intersection' => $inter, 'spat' => $spat]);
}

function handleRegisterIntersection(): void {
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);

    $id = agentos_id('intr');
    $stmt = $pdo->prepare('INSERT INTO agentos_v2x_intersections (id, intersection_id, name, latitude, longitude, signal_type, current_phase, phase_schedule, approach_count, speed_limit_mps, geometry, last_updated) VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW())');
    $stmt->execute([
        $id, $data['intersection_id'] ?? $id,
        $data['name'] ?? null,
        (float)($data['latitude'] ?? 0),
        (float)($data['longitude'] ?? 0),
        $data['signal_type'] ?? 'traffic_light',
        $data['current_phase'] ?? 'red',
        json_encode($data['phase_schedule'] ?? []),
        (int)($data['approach_count'] ?? 4),
        (float)($data['speed_limit_mps'] ?? 0),
        json_encode($data['geometry'] ?? [])
    ]);

    agentos_respond(['ok' => true, 'intersection_id' => $data['intersection_id'] ?? $id], 201);
}

function handleIntersections(): void {
    $pdo = agentos_pdo();
    $lat = isset($_GET['latitude']) ? (float)$_GET['latitude'] : null;
    $lon = isset($_GET['longitude']) ? (float)$_GET['longitude'] : null;

    $stmt = $pdo->query('SELECT * FROM agentos_v2x_intersections WHERE active = 1 ORDER BY name');
    $all = $stmt->fetchAll();

    foreach ($all as &$i) {
        $i['phase_schedule'] = json_decode($i['phase_schedule'] ?? '[]', true);
        $i['geometry'] = json_decode($i['geometry'] ?? '{}', true);
        if ($lat !== null && $lon !== null) {
            $i['distance_m'] = round(haversineDistance($lat, $lon, (float)$i['latitude'], (float)$i['longitude']), 1);
        }
    }

    if ($lat !== null) {
        usort($all, fn($a, $b) => ($a['distance_m'] ?? PHP_INT_MAX) <=> ($b['distance_m'] ?? PHP_INT_MAX));
    }

    agentos_respond(['ok' => true, 'intersections' => $all]);
}

function handleCollisionWarning(): void {
    $pdo = agentos_pdo();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $reporterId = $data['reporter_id'] ?? '';
        if (!$reporterId) agentos_error('Missing reporter_id');

        $id = agentos_id('coll');
        $stmt = $pdo->prepare('INSERT INTO agentos_v2x_collisions (id, reporter_id, threat_entity_id, threat_type, severity, reporter_lat, reporter_lon, reporter_speed_mps, threat_lat, threat_lon, threat_speed_mps, time_to_collision_sec, distance_m, action_taken, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())');
        $stmt->execute([
            $id, $reporterId,
            $data['threat_entity_id'] ?? null,
            $data['threat_type'] ?? 'forward_collision',
            $data['severity'] ?? 'warning',
            (float)($data['reporter_lat'] ?? 0),
            (float)($data['reporter_lon'] ?? 0),
            (float)($data['reporter_speed_mps'] ?? 0),
            (float)($data['threat_lat'] ?? 0),
            (float)($data['threat_lon'] ?? 0),
            (float)($data['threat_speed_mps'] ?? 0),
            (float)($data['time_to_collision_sec'] ?? 0),
            (float)($data['distance_m'] ?? 0),
            $data['action_taken'] ?? 'none'
        ]);

        // Alert threat entity
        $threatId = $data['threat_entity_id'] ?? null;
        if ($threatId) {
            agentos_push("device:$threatId", 'collision_warning', [
                'warning_id' => $id, 'from' => $reporterId,
                'severity' => $data['severity'] ?? 'warning',
                'threat_type' => $data['threat_type'] ?? 'forward_collision',
                'ttc_sec' => $data['time_to_collision_sec'] ?? 0
            ]);
        }

        agentos_respond(['ok' => true, 'warning_id' => $id], 201);
    } else {
        $deviceId = $_GET['device_id'] ?? '';
        if (!$deviceId) agentos_error('Missing device_id');

        $stmt = $pdo->prepare('SELECT * FROM agentos_v2x_collisions WHERE (reporter_id = ? OR threat_entity_id = ?) AND resolved = 0 ORDER BY created_at DESC LIMIT 20');
        $stmt->execute([$deviceId, $deviceId]);
        agentos_respond(['ok' => true, 'warnings' => $stmt->fetchAll()]);
    }
}

function handlePlatoon(): void {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['platoon_action'] ?? 'status';
    $deviceId = $data['device_id'] ?? '';
    if (!$deviceId) agentos_error('Missing device_id');

    $redis = agentos_redis();
    $platoonKey = 'v2x:platoon:' . ($data['platoon_id'] ?? 'default');

    if ($action === 'join') {
        $members = json_decode($redis->get($platoonKey) ?: '[]', true);
        if (!in_array($deviceId, $members, true)) $members[] = $deviceId;
        $redis->set($platoonKey, json_encode($members));

        foreach ($members as $m) {
            if ($m !== $deviceId) {
                agentos_push("device:$m", 'platoon_update', ['action' => 'member_joined', 'device' => $deviceId, 'members' => $members]);
            }
        }

        agentos_respond(['ok' => true, 'platoon_id' => $data['platoon_id'] ?? 'default', 'members' => $members]);
    } elseif ($action === 'leave') {
        $members = json_decode($redis->get($platoonKey) ?: '[]', true);
        $members = array_values(array_filter($members, fn($m) => $m !== $deviceId));
        $redis->set($platoonKey, json_encode($members));

        foreach ($members as $m) {
            agentos_push("device:$m", 'platoon_update', ['action' => 'member_left', 'device' => $deviceId, 'members' => $members]);
        }

        agentos_respond(['ok' => true, 'left' => true, 'remaining' => $members]);
    } else {
        $members = json_decode($redis->get($platoonKey) ?: '[]', true);
        agentos_respond(['ok' => true, 'platoon_id' => $data['platoon_id'] ?? 'default', 'members' => $members]);
    }
}

function handleSpeedAdvisory(): void {
    $pdo = agentos_pdo();
    $lat = (float)($_GET['latitude'] ?? 0);
    $lon = (float)($_GET['longitude'] ?? 0);
    $heading = (float)($_GET['heading'] ?? 0);
    $currentSpeed = (float)($_GET['speed_mps'] ?? 0);

    // Check nearby intersections
    $stmt = $pdo->query('SELECT * FROM agentos_v2x_intersections WHERE active = 1');
    $intersections = $stmt->fetchAll();

    $advisory = ['recommended_speed_mps' => $currentSpeed, 'reason' => 'clear', 'details' => []];
    $minSpeed = $currentSpeed;

    foreach ($intersections as $inter) {
        $dist = haversineDistance($lat, $lon, (float)$inter['latitude'], (float)$inter['longitude']);
        if ($dist < 200) {
            if ($inter['current_phase'] === 'red') {
                $needed = $dist > 0 ? $dist / max(1, (float)($inter['phase_remaining_sec'] ?? 10)) : 0;
                $minSpeed = min($minSpeed, max(0, $needed));
                $advisory['details'][] = ['type' => 'red_light', 'distance_m' => round($dist, 1), 'phase_remaining' => (float)$inter['phase_remaining_sec']];
                $advisory['reason'] = 'red_light_ahead';
            } elseif ($inter['current_phase'] === 'yellow') {
                $minSpeed = min($minSpeed, 2.0);
                $advisory['details'][] = ['type' => 'yellow_light', 'distance_m' => round($dist, 1)];
                $advisory['reason'] = 'yellow_light_ahead';
            }
        }
    }

    // Check road conditions
    $stmt = $pdo->prepare("SELECT * FROM agentos_v2x_road_conditions WHERE active = 1 AND expires_at > NOW()");
    $stmt->execute();
    $conditions = $stmt->fetchAll();

    foreach ($conditions as $cond) {
        $dist = haversineDistance($lat, $lon, (float)$cond['latitude'], (float)$cond['longitude']);
        if ($dist < (float)$cond['radius_m'] + 100) {
            $severityMultiplier = match($cond['severity']) {
                'extreme' => 0.3, 'high' => 0.5, 'moderate' => 0.7, default => 0.85
            };
            $minSpeed = min($minSpeed, $currentSpeed * $severityMultiplier);
            $advisory['details'][] = ['type' => 'road_condition', 'condition' => $cond['condition_type'], 'severity' => $cond['severity'], 'distance_m' => round($dist, 1)];
            if ($advisory['reason'] === 'clear') $advisory['reason'] = 'road_condition';
        }
    }

    $advisory['recommended_speed_mps'] = round(max(0, $minSpeed), 1);
    agentos_respond(['ok' => true, 'speed_advisory' => $advisory]);
}

function handleRoadConditions(): void {
    $pdo = agentos_pdo();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        $id = agentos_id('road');
        $stmt = $pdo->prepare('INSERT INTO agentos_v2x_road_conditions (id, reporter_id, latitude, longitude, radius_m, condition_type, severity, description, expires_at, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,NOW(),NOW())');
        $stmt->execute([
            $id, $data['reporter_id'] ?? 'unknown',
            (float)($data['latitude'] ?? 0),
            (float)($data['longitude'] ?? 0),
            (float)($data['radius_m'] ?? 50),
            $data['condition_type'] ?? 'debris',
            $data['severity'] ?? 'moderate',
            $data['description'] ?? null,
            $data['expires_at'] ?? date('Y-m-d H:i:s', time() + 3600)
        ]);

        agentos_respond(['ok' => true, 'condition_id' => $id], 201);
    } else {
        $lat = (float)($_GET['latitude'] ?? 0);
        $lon = (float)($_GET['longitude'] ?? 0);
        $radius = min((float)($_GET['radius_m'] ?? 1000), 10000);

        $stmt = $pdo->prepare("SELECT * FROM agentos_v2x_road_conditions WHERE active = 1 AND (expires_at IS NULL OR expires_at > NOW()) ORDER BY created_at DESC");
        $stmt->execute();
        $all = $stmt->fetchAll();

        $nearby = [];
        foreach ($all as $c) {
            $dist = haversineDistance($lat, $lon, (float)$c['latitude'], (float)$c['longitude']);
            if ($dist <= $radius) {
                $c['distance_m'] = round($dist, 1);
                $nearby[] = $c;
            }
        }

        usort($nearby, fn($a, $b) => $a['distance_m'] <=> $b['distance_m']);
        agentos_respond(['ok' => true, 'road_conditions' => $nearby]);
    }
}

function handleEmergencyVehicle(): void {
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);

    $senderId = $data['sender_id'] ?? '';
    if (!$senderId) agentos_error('Missing sender_id');

    $lat = (float)($data['latitude'] ?? 0);
    $lon = (float)($data['longitude'] ?? 0);

    $id = agentos_id('v2x');
    $pdo->prepare('INSERT INTO agentos_v2x_messages (id, sender_id, sender_type, message_type, channel, priority, latitude, longitude, heading, speed_mps, payload, ttl_seconds, radius_m, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())')
        ->execute([
            $id, $senderId, 'emergency', 'eva', 'cv2x', 'critical',
            $lat, $lon, (float)($data['heading'] ?? 0), (float)($data['speed_mps'] ?? 0),
            json_encode([
                'vehicle_type' => $data['vehicle_type'] ?? 'ambulance',
                'sirens_active' => $data['sirens_active'] ?? true,
                'message' => 'Emergency vehicle approaching — yield immediately'
            ]),
            30, 1000
        ]);

    // Alert ALL nearby entities
    $stmt = $pdo->prepare("SELECT entity_id FROM agentos_v2x_entities WHERE last_seen > DATE_SUB(NOW(), INTERVAL 60 SECOND) AND entity_id != ?");
    $stmt->execute([$senderId]);
    $entities = $stmt->fetchAll();

    foreach ($entities as $e) {
        agentos_push("device:{$e['entity_id']}", 'emergency_vehicle_alert', [
            'message_id' => $id, 'emergency_type' => $data['vehicle_type'] ?? 'ambulance',
            'lat' => $lat, 'lon' => $lon, 'heading' => $data['heading'] ?? 0,
            'speed_mps' => $data['speed_mps'] ?? 0,
            'action' => 'yield_immediately'
        ]);
    }

    agentos_respond(['ok' => true, 'alert_id' => $id, 'entities_notified' => count($entities)], 201);
}

function handlePedestrianAlert(): void {
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);

    $senderId = $data['sender_id'] ?? '';
    if (!$senderId) agentos_error('Missing sender_id');

    $id = agentos_id('v2x');
    $pdo->prepare('INSERT INTO agentos_v2x_messages (id, sender_id, sender_type, message_type, channel, priority, latitude, longitude, heading, speed_mps, payload, ttl_seconds, radius_m, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())')
        ->execute([
            $id, $senderId, $data['sender_type'] ?? 'robot', 'psm', 'cv2x', 'high',
            (float)($data['latitude'] ?? 0), (float)($data['longitude'] ?? 0),
            (float)($data['heading'] ?? 0), (float)($data['speed_mps'] ?? 0),
            json_encode([
                'pedestrian_count' => (int)($data['pedestrian_count'] ?? 1),
                'crosswalk' => $data['crosswalk'] ?? false,
                'children_present' => $data['children_present'] ?? false,
                'recommended_action' => $data['children_present'] ?? false ? 'full_stop' : 'reduce_speed'
            ]),
            15, 200
        ]);

    agentos_respond(['ok' => true, 'alert_id' => $id], 201);
}

function handleMessageLog(): void {
    $pdo = agentos_pdo();
    $deviceId = $_GET['device_id'] ?? null;
    $type = $_GET['message_type'] ?? null;
    $limit = min(max((int)($_GET['limit'] ?? 50), 1), 500);

    $sql = 'SELECT * FROM agentos_v2x_messages WHERE 1=1';
    $params = [];
    if ($deviceId) { $sql .= ' AND sender_id = ?'; $params[] = $deviceId; }
    if ($type) { $sql .= ' AND message_type = ?'; $params[] = $type; }
    $sql .= ' ORDER BY created_at DESC LIMIT ?';
    $params[] = $limit;

    $stmt = $pdo->prepare($sql);
    dbExecute($stmt, $params);
    $msgs = $stmt->fetchAll();
    foreach ($msgs as &$m) $m['payload'] = json_decode($m['payload'], true);

    agentos_respond(['ok' => true, 'messages' => $msgs, 'count' => count($msgs)]);
}

function handleChannelStatus(): void {
    $channels = [
        'dsrc' => ['frequency' => '5.9 GHz', 'standard' => 'IEEE 802.11p', 'range_m' => 1000, 'latency_ms' => 2, 'status' => 'available'],
        'cv2x' => ['frequency' => 'LTE Band 47 (5.9 GHz)', 'standard' => '3GPP Rel-14/16', 'range_m' => 1500, 'latency_ms' => 5, 'status' => 'primary'],
        'wifi_direct' => ['frequency' => '2.4/5 GHz', 'standard' => 'IEEE 802.11', 'range_m' => 100, 'latency_ms' => 10, 'status' => 'fallback'],
        'cellular' => ['frequency' => 'Various', 'standard' => '4G/5G', 'range_m' => 50000, 'latency_ms' => 20, 'status' => 'available'],
        'bluetooth' => ['frequency' => '2.4 GHz', 'standard' => 'BLE 5.0', 'range_m' => 50, 'latency_ms' => 15, 'status' => 'pedestrian_only'],
        'mesh' => ['frequency' => 'Various', 'standard' => 'Alfred Mesh Protocol', 'range_m' => 500, 'latency_ms' => 8, 'status' => 'experimental']
    ];

    agentos_respond(['ok' => true, 'channels' => $channels]);
}

function handleV2xStats(): void {
    $pdo = agentos_pdo();

    $msgStats = $pdo->query("SELECT message_type, COUNT(*) as count FROM agentos_v2x_messages WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) GROUP BY message_type")->fetchAll();
    $entityCount = (int)$pdo->query("SELECT COUNT(*) as c FROM agentos_v2x_entities WHERE last_seen > DATE_SUB(NOW(), INTERVAL 5 MINUTE)")->fetch()['c'];
    $activeConditions = (int)$pdo->query("SELECT COUNT(*) as c FROM agentos_v2x_road_conditions WHERE active = 1 AND (expires_at IS NULL OR expires_at > NOW())")->fetch()['c'];
    $collisionWarnings = (int)$pdo->query("SELECT COUNT(*) as c FROM agentos_v2x_collisions WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetch()['c'];
    $intersections = (int)$pdo->query("SELECT COUNT(*) as c FROM agentos_v2x_intersections WHERE active = 1")->fetch()['c'];

    agentos_respond(['ok' => true, 'v2x_stats' => [
        'messages_24h' => $msgStats,
        'active_entities' => $entityCount,
        'active_road_conditions' => $activeConditions,
        'collision_warnings_24h' => $collisionWarnings,
        'registered_intersections' => $intersections
    ]]);
}

// ─── Router ────────────────────────────────────────────────────
$action = $_GET['action'] ?? $_POST['action'] ?? '';

$routes = [
    'broadcast'             => 'handleBroadcast',
    'nearby'                => 'handleNearby',
    'intersection_status'   => 'handleIntersectionStatus',
    'register_intersection' => 'handleRegisterIntersection',
    'intersections'         => 'handleIntersections',
    'collision_warning'     => 'handleCollisionWarning',
    'platoon'               => 'handlePlatoon',
    'speed_advisory'        => 'handleSpeedAdvisory',
    'road_conditions'       => 'handleRoadConditions',
    'emergency_vehicle'     => 'handleEmergencyVehicle',
    'pedestrian_alert'      => 'handlePedestrianAlert',
    'message_log'           => 'handleMessageLog',
    'channel_status'        => 'handleChannelStatus',
    'v2x_stats'             => 'handleV2xStats',
];

if (!isset($routes[$action])) {
    agentos_respond(['ok' => true, 'module' => 'Alfred OS — V2X Communication', 'version' => AGENTOS_VERSION,
        'description' => 'Vehicle-to-Everything: V2V, V2I, V2P, V2N communications for autonomous robots',
        'endpoints' => array_keys($routes),
        'message_types' => ['bsm'=>'Basic Safety Message','spat'=>'Signal Phase & Timing','map'=>'Map Data','tim'=>'Traveler Info','psm'=>'Personal Safety Message','eva'=>'Emergency Vehicle Alert','rsm'=>'Road Safety Message']]);
}

$routes[$action]();
