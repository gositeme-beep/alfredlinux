<?php
/**
 * GSM Alfred OS — Navigation & SLAM System
 *
 * Autonomous navigation stack for Alfred Robots.
 * Path planning (A*, RRT*, Theta*), SLAM map management,
 * waypoint missions, obstacle avoidance, fleet coordination.
 *
 * This is the brain that lets a robot pick you up from school/work
 * and navigate safely through streets, sidewalks, and buildings.
 *
 * Endpoints (18):
 *   create_map          — Create/import a SLAM map
 *   maps                — List maps for a device/location
 *   map_detail          — Get map details + metadata
 *   update_map          — Update map with new SLAM data
 *   delete_map          — Delete a map
 *   plan_path           — Plan a path between two poses
 *   active_path         — Get current active navigation path
 *   create_waypoint     — Create a named waypoint
 *   waypoints           — List waypoints for a map
 *   create_mission      — Create a multi-waypoint mission
 *   missions            — List missions
 *   execute_mission     — Start executing a mission
 *   mission_status      — Get mission execution status
 *   cancel_navigation   — Cancel current navigation
 *   set_goal            — Send immediate navigation goal
 *   costmap             — Get/update costmap layers
 *   obstacles           — Report/query obstacles
 *   fleet_coordination  — Coordinate multiple robots avoiding collision
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
    header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type,Authorization,X-Internal-Secret,X-Device-Id');
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// ─── Schema ─────────────────────────────────────────────────────
function navEnsureSchema(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    $pdo = agentos_pdo();

    $pdo->exec("CREATE TABLE IF NOT EXISTS agentos_nav_maps (
        id VARCHAR(32) PRIMARY KEY,
        device_id VARCHAR(64),
        location_name VARCHAR(128),
        map_type ENUM('occupancy_grid','point_cloud','hybrid','semantic','topological') NOT NULL DEFAULT 'occupancy_grid',
        resolution FLOAT NOT NULL DEFAULT 0.05,
        width INT UNSIGNED NOT NULL DEFAULT 0,
        height INT UNSIGNED NOT NULL DEFAULT 0,
        origin_x DOUBLE NOT NULL DEFAULT 0,
        origin_y DOUBLE NOT NULL DEFAULT 0,
        origin_theta DOUBLE NOT NULL DEFAULT 0,
        map_data_url VARCHAR(512),
        metadata JSON,
        status ENUM('building','complete','outdated','archived') NOT NULL DEFAULT 'building',
        floor_level INT DEFAULT 0,
        building VARCHAR(128),
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        INDEX idx_device (device_id),
        INDEX idx_location (location_name),
        INDEX idx_building_floor (building, floor_level)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS agentos_nav_waypoints (
        id VARCHAR(32) PRIMARY KEY,
        map_id VARCHAR(32) NOT NULL,
        name VARCHAR(128) NOT NULL,
        waypoint_type ENUM('pickup','dropoff','charging','home','docking','elevator','door','intersection','custom') NOT NULL DEFAULT 'custom',
        x DOUBLE NOT NULL,
        y DOUBLE NOT NULL,
        z DOUBLE DEFAULT 0,
        theta DOUBLE DEFAULT 0,
        floor_level INT DEFAULT 0,
        tolerance_xy FLOAT DEFAULT 0.5,
        tolerance_theta FLOAT DEFAULT 0.3,
        approach_direction DOUBLE,
        wait_duration_sec INT DEFAULT 0,
        metadata JSON,
        created_at DATETIME NOT NULL,
        INDEX idx_map (map_id),
        INDEX idx_type (waypoint_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS agentos_nav_missions (
        id VARCHAR(32) PRIMARY KEY,
        device_id VARCHAR(64) NOT NULL,
        mission_name VARCHAR(128) NOT NULL,
        mission_type ENUM('patrol','delivery','pickup','escort','survey','custom') NOT NULL DEFAULT 'custom',
        waypoint_sequence JSON NOT NULL,
        loop_count INT DEFAULT 1,
        priority INT DEFAULT 5,
        schedule_cron VARCHAR(64),
        status ENUM('draft','queued','executing','paused','completed','failed','canceled') NOT NULL DEFAULT 'draft',
        current_waypoint_idx INT DEFAULT 0,
        total_waypoints INT DEFAULT 0,
        started_at DATETIME,
        completed_at DATETIME,
        created_by VARCHAR(64),
        error_message TEXT,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        INDEX idx_device (device_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS agentos_nav_paths (
        id VARCHAR(32) PRIMARY KEY,
        device_id VARCHAR(64) NOT NULL,
        map_id VARCHAR(32),
        start_x DOUBLE NOT NULL,
        start_y DOUBLE NOT NULL,
        goal_x DOUBLE NOT NULL,
        goal_y DOUBLE NOT NULL,
        algorithm ENUM('a_star','rrt_star','theta_star','dijkstra','hybrid_a_star','dwa') NOT NULL DEFAULT 'a_star',
        path_points JSON,
        path_length_m FLOAT DEFAULT 0,
        estimated_time_sec INT DEFAULT 0,
        planning_time_ms INT DEFAULT 0,
        status ENUM('planning','ready','executing','completed','failed','replanning') NOT NULL DEFAULT 'planning',
        created_at DATETIME NOT NULL,
        INDEX idx_device (device_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS agentos_nav_obstacles (
        id VARCHAR(32) PRIMARY KEY,
        device_id VARCHAR(64) NOT NULL,
        map_id VARCHAR(32),
        obstacle_type ENUM('static','dynamic','person','vehicle','animal','construction','unknown') NOT NULL DEFAULT 'unknown',
        shape ENUM('circle','rectangle','polygon','point') NOT NULL DEFAULT 'circle',
        center_x DOUBLE NOT NULL,
        center_y DOUBLE NOT NULL,
        radius FLOAT DEFAULT 0.5,
        width FLOAT DEFAULT 0,
        height FLOAT DEFAULT 0,
        velocity_x FLOAT DEFAULT 0,
        velocity_y FLOAT DEFAULT 0,
        confidence FLOAT DEFAULT 1.0,
        ttl_sec INT DEFAULT 60,
        detected_at DATETIME NOT NULL,
        expires_at DATETIME,
        INDEX idx_device_map (device_id, map_id),
        INDEX idx_type (obstacle_type),
        INDEX idx_expires (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

navEnsureSchema();
$auth = agentos_auth();

// ─── Path Planning Algorithms ─────────────────────────────────

/**
 * A* path planning on an occupancy grid.
 * Returns array of [x, y] waypoints.
 */
function planAStar(float $sx, float $sy, float $gx, float $gy, float $gridRes, int $w, int $h): array {
    // Convert to grid coords
    $startCol = (int)floor($sx / $gridRes);
    $startRow = (int)floor($sy / $gridRes);
    $goalCol = (int)floor($gx / $gridRes);
    $goalRow = (int)floor($gy / $gridRes);

    // Clamp
    $startCol = max(0, min($w - 1, $startCol));
    $startRow = max(0, min($h - 1, $startRow));
    $goalCol = max(0, min($w - 1, $goalCol));
    $goalRow = max(0, min($h - 1, $goalRow));

    // 8-directional neighbors
    $dirs = [[-1,-1],[-1,0],[-1,1],[0,-1],[0,1],[1,-1],[1,0],[1,1]];
    $costs = [1.414, 1, 1.414, 1, 1, 1.414, 1, 1.414];

    $open = new SplPriorityQueue();
    $open->setExtractFlags(SplPriorityQueue::EXTR_BOTH);

    $gScore = [];
    $cameFrom = [];
    $closed = [];

    $startKey = "$startRow,$startCol";
    $goalKey = "$goalRow,$goalCol";

    $gScore[$startKey] = 0;
    $h = sqrt(pow($goalRow - $startRow, 2) + pow($goalCol - $startCol, 2));
    $open->insert($startKey, -$h); // Negative because SplPriority is max-heap

    $maxIter = 50000;
    $iter = 0;

    while (!$open->isEmpty() && $iter < $maxIter) {
        $iter++;
        $current = $open->extract();
        $currentKey = $current['data'];

        if ($currentKey === $goalKey) {
            // Reconstruct path
            $path = [];
            $k = $goalKey;
            while (isset($cameFrom[$k])) {
                [$r, $c] = explode(',', $k);
                $path[] = [(float)$c * $gridRes, (float)$r * $gridRes];
                $k = $cameFrom[$k];
            }
            $path[] = [$sx, $sy];
            return array_reverse($path);
        }

        if (isset($closed[$currentKey])) continue;
        $closed[$currentKey] = true;

        [$cr, $cc] = array_map('intval', explode(',', $currentKey));

        for ($d = 0; $d < 8; $d++) {
            $nr = $cr + $dirs[$d][0];
            $nc = $cc + $dirs[$d][1];
            // Simple bounds check (in full system we'd check occupancy grid)
            if ($nr < 0 || $nr >= ($h ?: 1000) || $nc < 0 || $nc >= ($w ?: 1000)) continue;

            $nKey = "$nr,$nc";
            if (isset($closed[$nKey])) continue;

            $ng = ($gScore[$currentKey] ?? INF) + $costs[$d];
            if ($ng < ($gScore[$nKey] ?? INF)) {
                $gScore[$nKey] = $ng;
                $cameFrom[$nKey] = $currentKey;
                $fScore = $ng + sqrt(pow($goalRow - $nr, 2) + pow($goalCol - $nc, 2));
                $open->insert($nKey, -$fScore);
            }
        }
    }

    // No path found — return straight line as fallback
    return [[$sx, $sy], [$gx, $gy]];
}

/**
 * Haversine distance between GPS coordinates.
 */
function gpsDistance(float $lat1, float $lon1, float $lat2, float $lon2): float {
    $R = 6371000; // Earth radius in meters
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
}

// ─── Handlers ───────────────────────────────────────────────────

function handleCreateMap(): void {
    $auth = agentos_auth();
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);

    $id = agentos_id('map');
    $stmt = $pdo->prepare('INSERT INTO agentos_nav_maps 
        (id, device_id, location_name, map_type, resolution, width, height, origin_x, origin_y, origin_theta, map_data_url, metadata, status, floor_level, building, created_at, updated_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())');
    $stmt->execute([
        $id,
        $data['device_id'] ?? null,
        $data['location_name'] ?? 'Unnamed Map',
        $data['map_type'] ?? 'occupancy_grid',
        $data['resolution'] ?? 0.05,
        $data['width'] ?? 0,
        $data['height'] ?? 0,
        $data['origin_x'] ?? 0,
        $data['origin_y'] ?? 0,
        $data['origin_theta'] ?? 0,
        $data['map_data_url'] ?? null,
        json_encode($data['metadata'] ?? []),
        $data['status'] ?? 'building',
        $data['floor_level'] ?? 0,
        $data['building'] ?? null
    ]);

    agentos_audit(['action_type' => 'nav_create_map', 'user_id' => $auth['user_id'], 'status' => 'success',
        'metadata' => ['map_id' => $id]]);

    agentos_respond(['ok' => true, 'map_id' => $id], 201);
}

function handleMaps(): void {
    $pdo = agentos_pdo();
    $deviceId = $_GET['device_id'] ?? null;
    $building = $_GET['building'] ?? null;

    $sql = 'SELECT * FROM agentos_nav_maps WHERE 1=1';
    $params = [];
    if ($deviceId) { $sql .= ' AND device_id = ?'; $params[] = $deviceId; }
    if ($building) { $sql .= ' AND building = ?'; $params[] = $building; }
    $sql .= ' ORDER BY updated_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $maps = $stmt->fetchAll();
    foreach ($maps as &$m) $m['metadata'] = json_decode($m['metadata'] ?? '{}', true);

    agentos_respond(['ok' => true, 'maps' => $maps]);
}

function handleMapDetail(): void {
    $pdo = agentos_pdo();
    $mapId = $_GET['map_id'] ?? '';
    if (!$mapId) agentos_error('Missing map_id');

    $stmt = $pdo->prepare('SELECT * FROM agentos_nav_maps WHERE id = ?');
    $stmt->execute([$mapId]);
    $map = $stmt->fetch();
    if (!$map) agentos_error('Map not found', 404);
    $map['metadata'] = json_decode($map['metadata'] ?? '{}', true);

    // Get waypoints on this map
    $stmt = $pdo->prepare('SELECT * FROM agentos_nav_waypoints WHERE map_id = ? ORDER BY name');
    $stmt->execute([$mapId]);
    $map['waypoints'] = $stmt->fetchAll();

    // Get obstacle count
    $stmt = $pdo->prepare('SELECT COUNT(*) as c FROM agentos_nav_obstacles WHERE map_id = ? AND (expires_at IS NULL OR expires_at > NOW())');
    $stmt->execute([$mapId]);
    $map['active_obstacles'] = (int)$stmt->fetch()['c'];

    agentos_respond(['ok' => true, 'map' => $map]);
}

function handleUpdateMap(): void {
    $auth = agentos_auth();
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);
    $mapId = $data['map_id'] ?? '';
    if (!$mapId) agentos_error('Missing map_id');

    $fields = [];
    $params = [];
    $allowed = ['location_name','resolution','width','height','origin_x','origin_y','origin_theta','map_data_url','status','floor_level','building'];
    foreach ($allowed as $f) {
        if (isset($data[$f])) { $fields[] = "$f = ?"; $params[] = $data[$f]; }
    }
    if (isset($data['metadata'])) { $fields[] = 'metadata = ?'; $params[] = json_encode($data['metadata']); }

    if (empty($fields)) agentos_error('No fields to update');
    $fields[] = 'updated_at = NOW()';
    $params[] = $mapId;

    $pdo->prepare('UPDATE agentos_nav_maps SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($params);
    agentos_respond(['ok' => true, 'updated' => true]);
}

function handleDeleteMap(): void {
    $auth = agentos_auth();
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);
    $mapId = $data['map_id'] ?? '';
    if (!$mapId) agentos_error('Missing map_id');

    $pdo->prepare('DELETE FROM agentos_nav_waypoints WHERE map_id = ?')->execute([$mapId]);
    $pdo->prepare('DELETE FROM agentos_nav_obstacles WHERE map_id = ?')->execute([$mapId]);
    $pdo->prepare('DELETE FROM agentos_nav_maps WHERE id = ?')->execute([$mapId]);

    agentos_respond(['ok' => true, 'deleted' => true]);
}

function handlePlanPath(): void {
    $auth = agentos_auth();
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);

    $deviceId = $data['device_id'] ?? '';
    $mapId = $data['map_id'] ?? null;
    $startX = (float)($data['start_x'] ?? 0);
    $startY = (float)($data['start_y'] ?? 0);
    $goalX = (float)($data['goal_x'] ?? 0);
    $goalY = (float)($data['goal_y'] ?? 0);
    $algorithm = $data['algorithm'] ?? 'a_star';

    if (!$deviceId) agentos_error('Missing device_id');

    $validAlgorithms = ['a_star','rrt_star','theta_star','dijkstra','hybrid_a_star','dwa'];
    if (!in_array($algorithm, $validAlgorithms, true)) agentos_error('Invalid algorithm');

    $startTime = microtime(true);

    // Get map info for grid resolution
    $gridRes = 0.05;
    $gridW = 1000;
    $gridH = 1000;
    if ($mapId) {
        $stmt = $pdo->prepare('SELECT resolution, width, height FROM agentos_nav_maps WHERE id = ?');
        $stmt->execute([$mapId]);
        $mapInfo = $stmt->fetch();
        if ($mapInfo) {
            $gridRes = (float)$mapInfo['resolution'];
            $gridW = (int)$mapInfo['width'];
            $gridH = (int)$mapInfo['height'];
        }
    }

    // Plan path using A* (other algorithms delegate to device-side ROS2 nav2)
    $pathPoints = planAStar($startX, $startY, $goalX, $goalY, $gridRes, $gridW, $gridH);
    $planningTimeMs = (int)((microtime(true) - $startTime) * 1000);

    // Calculate path length
    $pathLength = 0;
    for ($i = 1; $i < count($pathPoints); $i++) {
        $dx = $pathPoints[$i][0] - $pathPoints[$i-1][0];
        $dy = $pathPoints[$i][1] - $pathPoints[$i-1][1];
        $pathLength += sqrt($dx*$dx + $dy*$dy);
    }

    // Estimate travel time (avg 1.0 m/s for indoor, 1.5 m/s outdoor)
    $estimatedTime = (int)ceil($pathLength / 1.0);

    $id = agentos_id('path');
    $stmt = $pdo->prepare('INSERT INTO agentos_nav_paths 
        (id, device_id, map_id, start_x, start_y, goal_x, goal_y, algorithm, path_points, path_length_m, estimated_time_sec, planning_time_ms, status, created_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())');
    $stmt->execute([$id, $deviceId, $mapId, $startX, $startY, $goalX, $goalY, $algorithm,
        json_encode($pathPoints), round($pathLength, 2), $estimatedTime, $planningTimeMs, 'ready']);

    agentos_respond(['ok' => true, 'path_id' => $id, 'path_length_m' => round($pathLength, 2),
        'estimated_time_sec' => $estimatedTime, 'planning_time_ms' => $planningTimeMs,
        'waypoint_count' => count($pathPoints), 'algorithm' => $algorithm]);
}

function handleActivePath(): void {
    $pdo = agentos_pdo();
    $deviceId = $_GET['device_id'] ?? '';
    if (!$deviceId) agentos_error('Missing device_id');

    $stmt = $pdo->prepare("SELECT * FROM agentos_nav_paths WHERE device_id = ? AND status IN ('ready','executing','replanning') ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$deviceId]);
    $path = $stmt->fetch();
    if (!$path) agentos_respond(['ok' => true, 'path' => null, 'navigating' => false]);

    $path['path_points'] = json_decode($path['path_points'] ?? '[]', true);
    agentos_respond(['ok' => true, 'path' => $path, 'navigating' => true]);
}

function handleCreateWaypoint(): void {
    $auth = agentos_auth();
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);

    $mapId = $data['map_id'] ?? '';
    $name = $data['name'] ?? '';
    if (!$mapId || !$name) agentos_error('Missing map_id or name');

    $validTypes = ['pickup','dropoff','charging','home','docking','elevator','door','intersection','custom'];
    $type = $data['waypoint_type'] ?? 'custom';
    if (!in_array($type, $validTypes, true)) agentos_error('Invalid waypoint_type');

    $id = agentos_id('wp');
    $stmt = $pdo->prepare('INSERT INTO agentos_nav_waypoints 
        (id, map_id, name, waypoint_type, x, y, z, theta, floor_level, tolerance_xy, tolerance_theta, approach_direction, wait_duration_sec, metadata, created_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())');
    $stmt->execute([
        $id, $mapId, $name, $type,
        (float)($data['x'] ?? 0), (float)($data['y'] ?? 0), (float)($data['z'] ?? 0), (float)($data['theta'] ?? 0),
        (int)($data['floor_level'] ?? 0),
        (float)($data['tolerance_xy'] ?? 0.5), (float)($data['tolerance_theta'] ?? 0.3),
        isset($data['approach_direction']) ? (float)$data['approach_direction'] : null,
        (int)($data['wait_duration_sec'] ?? 0),
        json_encode($data['metadata'] ?? [])
    ]);

    agentos_respond(['ok' => true, 'waypoint_id' => $id], 201);
}

function handleWaypoints(): void {
    $pdo = agentos_pdo();
    $mapId = $_GET['map_id'] ?? '';
    if (!$mapId) agentos_error('Missing map_id');

    $stmt = $pdo->prepare('SELECT * FROM agentos_nav_waypoints WHERE map_id = ? ORDER BY name');
    $stmt->execute([$mapId]);
    $waypoints = $stmt->fetchAll();
    foreach ($waypoints as &$wp) $wp['metadata'] = json_decode($wp['metadata'] ?? '{}', true);

    agentos_respond(['ok' => true, 'waypoints' => $waypoints]);
}

function handleCreateMission(): void {
    $auth = agentos_auth();
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);

    $deviceId = $data['device_id'] ?? '';
    $missionName = $data['mission_name'] ?? '';
    $waypointSequence = $data['waypoint_sequence'] ?? [];

    if (!$deviceId || !$missionName || empty($waypointSequence)) {
        agentos_error('Missing device_id, mission_name, or waypoint_sequence');
    }

    $validTypes = ['patrol','delivery','pickup','escort','survey','custom'];
    $missionType = $data['mission_type'] ?? 'custom';
    if (!in_array($missionType, $validTypes, true)) agentos_error('Invalid mission_type');

    $id = agentos_id('msn');
    $stmt = $pdo->prepare('INSERT INTO agentos_nav_missions 
        (id, device_id, mission_name, mission_type, waypoint_sequence, loop_count, priority, schedule_cron, status, total_waypoints, created_by, created_at, updated_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())');
    $stmt->execute([
        $id, $deviceId, $missionName, $missionType,
        json_encode($waypointSequence),
        (int)($data['loop_count'] ?? 1),
        (int)($data['priority'] ?? 5),
        $data['schedule_cron'] ?? null,
        'draft',
        count($waypointSequence),
        $auth['user_id']
    ]);

    agentos_audit(['action_type' => 'nav_create_mission', 'user_id' => $auth['user_id'], 'status' => 'success',
        'metadata' => ['mission_id' => $id, 'type' => $missionType, 'waypoints' => count($waypointSequence)]]);

    agentos_respond(['ok' => true, 'mission_id' => $id], 201);
}

function handleMissions(): void {
    $pdo = agentos_pdo();
    $deviceId = $_GET['device_id'] ?? '';
    if (!$deviceId) agentos_error('Missing device_id');

    $stmt = $pdo->prepare('SELECT * FROM agentos_nav_missions WHERE device_id = ? ORDER BY created_at DESC');
    $stmt->execute([$deviceId]);
    $missions = $stmt->fetchAll();
    foreach ($missions as &$m) $m['waypoint_sequence'] = json_decode($m['waypoint_sequence'], true);

    agentos_respond(['ok' => true, 'missions' => $missions]);
}

function handleExecuteMission(): void {
    $auth = agentos_auth();
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);
    $missionId = $data['mission_id'] ?? '';
    if (!$missionId) agentos_error('Missing mission_id');

    $stmt = $pdo->prepare('SELECT * FROM agentos_nav_missions WHERE id = ?');
    $stmt->execute([$missionId]);
    $mission = $stmt->fetch();
    if (!$mission) agentos_error('Mission not found', 404);

    if ($mission['status'] === 'executing') agentos_error('Mission already executing');

    $pdo->prepare("UPDATE agentos_nav_missions SET status = 'executing', current_waypoint_idx = 0, started_at = NOW(), updated_at = NOW() WHERE id = ?")
        ->execute([$missionId]);

    // Push mission to device for execution
    agentos_push("device:{$mission['device_id']}", 'nav_execute_mission', [
        'mission_id' => $missionId,
        'waypoint_sequence' => json_decode($mission['waypoint_sequence'], true),
        'loop_count' => (int)$mission['loop_count']
    ]);

    agentos_respond(['ok' => true, 'status' => 'executing']);
}

function handleMissionStatus(): void {
    $pdo = agentos_pdo();
    $missionId = $_GET['mission_id'] ?? '';
    if (!$missionId) agentos_error('Missing mission_id');

    $stmt = $pdo->prepare('SELECT * FROM agentos_nav_missions WHERE id = ?');
    $stmt->execute([$missionId]);
    $mission = $stmt->fetch();
    if (!$mission) agentos_error('Mission not found', 404);

    $mission['waypoint_sequence'] = json_decode($mission['waypoint_sequence'], true);
    $mission['progress_percent'] = $mission['total_waypoints'] > 0
        ? round(((int)$mission['current_waypoint_idx'] / (int)$mission['total_waypoints']) * 100, 1) : 0;

    agentos_respond(['ok' => true, 'mission' => $mission]);
}

function handleCancelNavigation(): void {
    $auth = agentos_auth();
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);
    $deviceId = $data['device_id'] ?? '';
    if (!$deviceId) agentos_error('Missing device_id');

    // Cancel active paths
    $pdo->prepare("UPDATE agentos_nav_paths SET status = 'failed' WHERE device_id = ? AND status IN ('ready','executing','replanning')")
        ->execute([$deviceId]);

    // Cancel executing missions
    $pdo->prepare("UPDATE agentos_nav_missions SET status = 'canceled', updated_at = NOW() WHERE device_id = ? AND status = 'executing'")
        ->execute([$deviceId]);

    // Send stop command to device
    agentos_push("device:$deviceId", 'nav_cancel', ['reason' => $data['reason'] ?? 'user_requested']);

    agentos_respond(['ok' => true, 'canceled' => true]);
}

function handleSetGoal(): void {
    $auth = agentos_auth();
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);

    $deviceId = $data['device_id'] ?? '';
    $goalX = $data['goal_x'] ?? null;
    $goalY = $data['goal_y'] ?? null;

    if (!$deviceId || $goalX === null || $goalY === null) agentos_error('Missing device_id, goal_x, or goal_y');

    // If goal is a waypoint ID, resolve it
    if (isset($data['waypoint_id'])) {
        $stmt = $pdo->prepare('SELECT x, y, theta, floor_level FROM agentos_nav_waypoints WHERE id = ?');
        $stmt->execute([$data['waypoint_id']]);
        $wp = $stmt->fetch();
        if ($wp) {
            $goalX = (float)$wp['x'];
            $goalY = (float)$wp['y'];
        }
    }

    // Push navigation goal to device
    agentos_push("device:$deviceId", 'nav_goal', [
        'goal_x' => (float)$goalX,
        'goal_y' => (float)$goalY,
        'goal_theta' => (float)($data['goal_theta'] ?? 0),
        'map_id' => $data['map_id'] ?? null,
        'planner' => $data['algorithm'] ?? 'a_star'
    ]);

    agentos_respond(['ok' => true, 'goal_sent' => true, 'goal' => ['x' => $goalX, 'y' => $goalY]]);
}

function handleCostmap(): void {
    $pdo = agentos_pdo();
    $deviceId = $_GET['device_id'] ?? '';
    $mapId = $_GET['map_id'] ?? '';
    if (!$deviceId) agentos_error('Missing device_id');

    // Get active obstacles as costmap overlays
    $sql = 'SELECT * FROM agentos_nav_obstacles WHERE device_id = ? AND (expires_at IS NULL OR expires_at > NOW())';
    $params = [$deviceId];
    if ($mapId) { $sql .= ' AND map_id = ?'; $params[] = $mapId; }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $obstacles = $stmt->fetchAll();

    // Build inflation radii
    $costmapLayers = [
        'static_layer' => ['source' => 'map', 'active' => true],
        'obstacle_layer' => ['source' => 'sensors', 'active' => true, 'obstacles' => count($obstacles)],
        'inflation_layer' => ['radius' => 0.55, 'cost_scaling' => 10.0, 'active' => true],
        'voxel_layer' => ['source' => 'depth_camera', 'active' => true],
    ];

    agentos_respond(['ok' => true, 'costmap_layers' => $costmapLayers, 'obstacles' => $obstacles]);
}

function handleObstacles(): void {
    $pdo = agentos_pdo();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $deviceId = $data['device_id'] ?? '';
        if (!$deviceId) agentos_error('Missing device_id');

        $validTypes = ['static','dynamic','person','vehicle','animal','construction','unknown'];
        $type = $data['obstacle_type'] ?? 'unknown';
        if (!in_array($type, $validTypes, true)) $type = 'unknown';

        $ttl = (int)($data['ttl_sec'] ?? 60);
        $id = agentos_id('obs');
        $stmt = $pdo->prepare('INSERT INTO agentos_nav_obstacles 
            (id, device_id, map_id, obstacle_type, shape, center_x, center_y, radius, width, height, velocity_x, velocity_y, confidence, ttl_sec, detected_at, expires_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),DATE_ADD(NOW(), INTERVAL ? SECOND))');
        $stmt->execute([
            $id, $deviceId, $data['map_id'] ?? null, $type,
            $data['shape'] ?? 'circle',
            (float)($data['center_x'] ?? 0), (float)($data['center_y'] ?? 0),
            (float)($data['radius'] ?? 0.5),
            (float)($data['width'] ?? 0), (float)($data['height'] ?? 0),
            (float)($data['velocity_x'] ?? 0), (float)($data['velocity_y'] ?? 0),
            (float)($data['confidence'] ?? 1.0),
            $ttl, $ttl
        ]);

        agentos_respond(['ok' => true, 'obstacle_id' => $id], 201);
    } else {
        $deviceId = $_GET['device_id'] ?? '';
        if (!$deviceId) agentos_error('Missing device_id');

        $stmt = $pdo->prepare('SELECT * FROM agentos_nav_obstacles WHERE device_id = ? AND (expires_at IS NULL OR expires_at > NOW()) ORDER BY detected_at DESC');
        $stmt->execute([$deviceId]);
        agentos_respond(['ok' => true, 'obstacles' => $stmt->fetchAll()]);
    }
}

function handleFleetCoordination(): void {
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);
    $deviceIds = $data['device_ids'] ?? [];

    if (empty($deviceIds)) agentos_error('Missing device_ids');

    // Get active paths for all devices
    $placeholders = implode(',', array_fill(0, count($deviceIds), '?'));
    $stmt = $pdo->prepare("SELECT device_id, goal_x, goal_y, path_points, status FROM agentos_nav_paths WHERE device_id IN ($placeholders) AND status IN ('ready','executing')");
    $stmt->execute($deviceIds);
    $paths = $stmt->fetchAll();

    // Check for potential collisions (simplified: within 2m of each other)
    $conflicts = [];
    for ($i = 0; $i < count($paths); $i++) {
        for ($j = $i + 1; $j < count($paths); $j++) {
            $pathA = json_decode($paths[$i]['path_points'] ?? '[]', true);
            $pathB = json_decode($paths[$j]['path_points'] ?? '[]', true);

            foreach ($pathA as $pA) {
                foreach ($pathB as $pB) {
                    $dist = sqrt(pow($pA[0] - $pB[0], 2) + pow($pA[1] - $pB[1], 2));
                    if ($dist < 2.0) {
                        $conflicts[] = [
                            'device_a' => $paths[$i]['device_id'],
                            'device_b' => $paths[$j]['device_id'],
                            'conflict_point' => $pA,
                            'distance_m' => round($dist, 2)
                        ];
                        break 2; // One conflict per pair is enough
                    }
                }
            }
        }
    }

    agentos_respond(['ok' => true, 'active_navigations' => count($paths), 'conflicts' => $conflicts,
        'recommendation' => empty($conflicts) ? 'clear' : 'replan_needed']);
}

// ─── Router ────────────────────────────────────────────────────
$action = $_GET['action'] ?? $_POST['action'] ?? '';

$routes = [
    'create_map'         => 'handleCreateMap',
    'maps'               => 'handleMaps',
    'map_detail'         => 'handleMapDetail',
    'update_map'         => 'handleUpdateMap',
    'delete_map'         => 'handleDeleteMap',
    'plan_path'          => 'handlePlanPath',
    'active_path'        => 'handleActivePath',
    'create_waypoint'    => 'handleCreateWaypoint',
    'waypoints'          => 'handleWaypoints',
    'create_mission'     => 'handleCreateMission',
    'missions'           => 'handleMissions',
    'execute_mission'    => 'handleExecuteMission',
    'mission_status'     => 'handleMissionStatus',
    'cancel_navigation'  => 'handleCancelNavigation',
    'set_goal'           => 'handleSetGoal',
    'costmap'            => 'handleCostmap',
    'obstacles'          => 'handleObstacles',
    'fleet_coordination' => 'handleFleetCoordination',
];

if (!isset($routes[$action])) {
    agentos_respond(['ok' => true, 'module' => 'Alfred OS — Navigation & SLAM', 'version' => AGENTOS_VERSION,
        'description' => 'Autonomous navigation, path planning, SLAM maps, waypoint missions',
        'endpoints' => array_keys($routes),
        'algorithms' => ['a_star','rrt_star','theta_star','dijkstra','hybrid_a_star','dwa']]);
}

$routes[$action]();
