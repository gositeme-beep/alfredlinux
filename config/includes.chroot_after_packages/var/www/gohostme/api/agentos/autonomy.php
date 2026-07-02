<?php
/**
 * GSM Alfred OS — Robot Autonomy Controller
 *
 * High-level autonomous behavior management: behavior trees, state machines,
 * autonomous decision-making, passenger interaction protocols, pickup/dropoff
 * scenarios, energy management, and autonomy level transitions.
 *
 * SAE J3016 Autonomy Levels:
 *   L0: No Automation        L1: Driver Assistance      L2: Partial Automation
 *   L3: Conditional           L4: High Automation         L5: Full Automation
 *
 * Endpoints (18):
 *   autonomy_status       — Get current autonomy level & state
 *   set_autonomy_level    — Change autonomy level
 *   behavior_tree         — Get/set active behavior tree
 *   behavior_trees        — List available behavior trees
 *   create_behavior       — Create a custom behavior tree
 *   state_machine         — Get current state machine state
 *   transition_state      — Force a state transition
 *   create_mission        — Create an autonomous mission (pickup/delivery)
 *   missions              — List missions
 *   mission_detail        — Get mission detail
 *   update_mission        — Update mission progress
 *   passenger_interaction — Handle passenger interaction events
 *   energy_plan           — Get energy-aware mission planning
 *   decision_log          — View autonomous decision history
 *   override              — Manual override (take control)
 *   release_override      — Release manual override
 *   capabilities          — Get robot capabilities
 *   autonomy_stats        — Autonomy system statistics
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
function autoEnsureSchema(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    $pdo = agentos_pdo();

    $pdo->exec("CREATE TABLE IF NOT EXISTS agentos_autonomy_state (
        id VARCHAR(32) PRIMARY KEY,
        device_id VARCHAR(32) NOT NULL UNIQUE,
        autonomy_level TINYINT NOT NULL DEFAULT 0 COMMENT 'SAE J3016 Level 0-5',
        operating_mode ENUM('idle','autonomous','teleoperated','manual','emergency','charging','maintenance','suspended') NOT NULL DEFAULT 'idle',
        behavior_tree_id VARCHAR(32),
        state_machine_state VARCHAR(64) DEFAULT 'idle',
        mission_id VARCHAR(32),
        override_active TINYINT(1) DEFAULT 0,
        override_operator VARCHAR(128),
        override_reason VARCHAR(256),
        battery_percent FLOAT DEFAULT 100,
        speed_mps FLOAT DEFAULT 0,
        latitude DOUBLE,
        longitude DOUBLE,
        heading FLOAT,
        passengers INT DEFAULT 0,
        max_passengers INT DEFAULT 4,
        doors_locked TINYINT(1) DEFAULT 1,
        last_heartbeat DATETIME,
        updated_at DATETIME NOT NULL,
        INDEX idx_device (device_id),
        INDEX idx_mode (operating_mode)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS agentos_behavior_trees (
        id VARCHAR(32) PRIMARY KEY,
        name VARCHAR(128) NOT NULL,
        description TEXT,
        tree_type ENUM('transport','delivery','patrol','escort','survey','cleaning','charging','emergency','custom') NOT NULL DEFAULT 'custom',
        tree_definition JSON NOT NULL,
        version INT DEFAULT 1,
        active TINYINT(1) DEFAULT 1,
        required_autonomy_level TINYINT DEFAULT 3,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        INDEX idx_type (tree_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS agentos_auto_missions (
        id VARCHAR(32) PRIMARY KEY,
        device_id VARCHAR(32) NOT NULL,
        mission_type ENUM('pickup','dropoff','delivery','patrol','escort','recharge','custom') NOT NULL,
        status ENUM('created','assigned','en_route_pickup','waiting_passenger','boarding','en_route_destination','arriving','unloading','completed','cancelled','failed','aborted') NOT NULL DEFAULT 'created',
        priority ENUM('emergency','high','normal','low') NOT NULL DEFAULT 'normal',
        requester_id VARCHAR(32),
        requester_name VARCHAR(128),
        pickup_lat DOUBLE,
        pickup_lon DOUBLE,
        pickup_address VARCHAR(256),
        destination_lat DOUBLE,
        destination_lon DOUBLE,
        destination_address VARCHAR(256),
        waypoints JSON,
        passenger_count INT DEFAULT 0,
        passenger_names JSON,
        estimated_pickup_time DATETIME,
        actual_pickup_time DATETIME,
        estimated_arrival_time DATETIME,
        actual_arrival_time DATETIME,
        distance_km FLOAT,
        energy_used_percent FLOAT,
        fare_usd DECIMAL(10,2),
        rating INT,
        feedback TEXT,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        INDEX idx_device (device_id),
        INDEX idx_status (status),
        INDEX idx_requester (requester_id),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS agentos_auto_decisions (
        id VARCHAR(32) PRIMARY KEY,
        device_id VARCHAR(32) NOT NULL,
        decision_type ENUM('path_change','speed_adjust','stop','yield','lane_change','obstacle_avoid','emergency_stop','reroute','wait','proceed','abort_mission','passenger_interaction','energy_conservation','mode_switch') NOT NULL,
        trigger_source ENUM('sensor','v2x','planner','safety','passenger','operator','energy','schedule','geofence') NOT NULL,
        context JSON,
        decision VARCHAR(256) NOT NULL,
        confidence FLOAT DEFAULT 1.0,
        alternatives JSON,
        outcome ENUM('success','partial','failed','pending','overridden') DEFAULT 'pending',
        reaction_time_ms INT,
        created_at DATETIME NOT NULL,
        INDEX idx_device (device_id),
        INDEX idx_type (decision_type),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS agentos_passenger_events (
        id VARCHAR(32) PRIMARY KEY,
        device_id VARCHAR(32) NOT NULL,
        mission_id VARCHAR(32),
        event_type ENUM('approach','door_open','boarding','seated','door_close','departure','arrival','door_open_dest','alighting','door_close_dest','emergency_stop_request','help_request','rating','complaint','no_show','wrong_vehicle') NOT NULL,
        passenger_id VARCHAR(32),
        passenger_name VARCHAR(128),
        details JSON,
        created_at DATETIME NOT NULL,
        INDEX idx_device (device_id),
        INDEX idx_mission (mission_id),
        INDEX idx_type (event_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // ─── Seed Behavior Trees ────────────────────────────────
    $stmt = $pdo->query("SELECT COUNT(*) as c FROM agentos_behavior_trees");
    if ((int)$stmt->fetch()['c'] === 0) {
        $trees = [
            ['Passenger Pickup & Transport', 'Complete autonomous passenger pickup, transport, and dropoff sequence', 'transport', 4, [
                'type' => 'sequence', 'name' => 'transport_mission', 'children' => [
                    ['type' => 'action', 'name' => 'verify_mission_params'],
                    ['type' => 'action', 'name' => 'plan_route_to_pickup'],
                    ['type' => 'action', 'name' => 'navigate_to_pickup'],
                    ['type' => 'action', 'name' => 'announce_arrival'],
                    ['type' => 'action', 'name' => 'unlock_doors'],
                    ['type' => 'condition', 'name' => 'wait_for_boarding', 'timeout_sec' => 300],
                    ['type' => 'action', 'name' => 'confirm_passengers_seated'],
                    ['type' => 'action', 'name' => 'lock_doors'],
                    ['type' => 'action', 'name' => 'plan_route_to_destination'],
                    ['type' => 'action', 'name' => 'navigate_to_destination'],
                    ['type' => 'action', 'name' => 'announce_arrival_destination'],
                    ['type' => 'action', 'name' => 'unlock_doors'],
                    ['type' => 'condition', 'name' => 'wait_for_alighting', 'timeout_sec' => 120],
                    ['type' => 'action', 'name' => 'lock_doors'],
                    ['type' => 'action', 'name' => 'complete_mission']
                ]
            ]],
            ['Package Delivery', 'Autonomous package pickup and delivery', 'delivery', 4, [
                'type' => 'sequence', 'name' => 'delivery_mission', 'children' => [
                    ['type' => 'action', 'name' => 'verify_package_loaded'],
                    ['type' => 'action', 'name' => 'plan_delivery_route'],
                    ['type' => 'action', 'name' => 'navigate_to_delivery'],
                    ['type' => 'action', 'name' => 'announce_delivery'],
                    ['type' => 'selector', 'name' => 'delivery_handoff', 'children' => [
                        ['type' => 'action', 'name' => 'wait_for_recipient'],
                        ['type' => 'action', 'name' => 'secure_drop_location']
                    ]],
                    ['type' => 'action', 'name' => 'confirm_delivery'],
                    ['type' => 'action', 'name' => 'complete_mission']
                ]
            ]],
            ['Security Patrol', 'Autonomous area patrol with monitoring', 'patrol', 3, [
                'type' => 'sequence', 'name' => 'patrol_mission', 'children' => [
                    ['type' => 'action', 'name' => 'load_patrol_route'],
                    ['type' => 'repeater', 'name' => 'patrol_loop', 'repeat' => -1, 'children' => [
                        ['type' => 'sequence', 'name' => 'patrol_cycle', 'children' => [
                            ['type' => 'action', 'name' => 'navigate_to_next_checkpoint'],
                            ['type' => 'action', 'name' => 'perform_surveillance_scan'],
                            ['type' => 'selector', 'name' => 'anomaly_check', 'children' => [
                                ['type' => 'condition', 'name' => 'no_anomaly_detected'],
                                ['type' => 'sequence', 'name' => 'anomaly_response', 'children' => [
                                    ['type' => 'action', 'name' => 'alert_security'],
                                    ['type' => 'action', 'name' => 'record_evidence'],
                                    ['type' => 'action', 'name' => 'maintain_safe_distance']
                                ]]
                            ]],
                            ['type' => 'condition', 'name' => 'check_battery_sufficient']
                        ]]
                    ]]
                ]
            ]],
            ['Auto Recharge', 'Navigate to and dock with charging station', 'charging', 2, [
                'type' => 'sequence', 'name' => 'recharge_mission', 'children' => [
                    ['type' => 'action', 'name' => 'find_nearest_charger'],
                    ['type' => 'action', 'name' => 'navigate_to_charger'],
                    ['type' => 'action', 'name' => 'align_with_dock'],
                    ['type' => 'action', 'name' => 'initiate_docking'],
                    ['type' => 'condition', 'name' => 'wait_for_charge', 'target_percent' => 95],
                    ['type' => 'action', 'name' => 'undock'],
                    ['type' => 'action', 'name' => 'resume_operations']
                ]
            ]],
            ['Emergency Response', 'Emergency stop and safe state entry', 'emergency', 0, [
                'type' => 'sequence', 'name' => 'emergency_response', 'children' => [
                    ['type' => 'action', 'name' => 'emergency_brake'],
                    ['type' => 'action', 'name' => 'activate_hazard_lights'],
                    ['type' => 'action', 'name' => 'alert_operations_center'],
                    ['type' => 'action', 'name' => 'announce_safety_message'],
                    ['type' => 'selector', 'name' => 'passenger_safety', 'children' => [
                        ['type' => 'condition', 'name' => 'no_passengers'],
                        ['type' => 'sequence', 'name' => 'evacuate', 'children' => [
                            ['type' => 'action', 'name' => 'unlock_all_doors'],
                            ['type' => 'action', 'name' => 'announce_evacuation'],
                            ['type' => 'condition', 'name' => 'wait_for_evacuation']
                        ]]
                    ]],
                    ['type' => 'action', 'name' => 'enter_safe_state']
                ]
            ]]
        ];

        $stmt = $pdo->prepare('INSERT INTO agentos_behavior_trees (id, name, description, tree_type, tree_definition, required_autonomy_level, created_at, updated_at) VALUES (?,?,?,?,?,?,NOW(),NOW())');
        foreach ($trees as $t) {
            $stmt->execute([agentos_id('bt'), $t[0], $t[1], $t[2], json_encode($t[4]), $t[3]]);
        }
    }
}

autoEnsureSchema();
$auth = agentos_auth();

// ─── State Machine Definitions ──────────────────────────────
function getStateTransitions(): array {
    return [
        'idle' => ['dispatched', 'charging', 'maintenance', 'emergency'],
        'dispatched' => ['en_route_pickup', 'cancelled', 'emergency'],
        'en_route_pickup' => ['at_pickup', 'rerouting', 'emergency', 'cancelled'],
        'at_pickup' => ['boarding', 'no_show', 'emergency', 'cancelled'],
        'boarding' => ['en_route_destination', 'emergency'],
        'en_route_destination' => ['arriving', 'rerouting', 'emergency'],
        'arriving' => ['at_destination', 'emergency'],
        'at_destination' => ['unloading', 'emergency'],
        'unloading' => ['idle', 'dispatched', 'charging'],
        'rerouting' => ['en_route_pickup', 'en_route_destination', 'emergency'],
        'no_show' => ['idle', 'dispatched'],
        'charging' => ['idle', 'dispatched', 'emergency'],
        'maintenance' => ['idle'],
        'emergency' => ['idle', 'maintenance'],
        'cancelled' => ['idle']
    ];
}

// ─── Handlers ───────────────────────────────────────────────────

function handleAutonomyStatus(): void {
    $pdo = agentos_pdo();
    $deviceId = $_GET['device_id'] ?? '';
    if (!$deviceId) agentos_error('Missing device_id');

    $stmt = $pdo->prepare('SELECT * FROM agentos_autonomy_state WHERE device_id = ?');
    $stmt->execute([$deviceId]);
    $state = $stmt->fetch();

    if (!$state) {
        // Initialize
        $id = agentos_id('auto');
        $pdo->prepare('INSERT INTO agentos_autonomy_state (id, device_id, autonomy_level, operating_mode, state_machine_state, updated_at) VALUES (?,?,0,?,?,NOW())')
            ->execute([$id, $deviceId, 'idle', 'idle']);
        $state = ['id' => $id, 'device_id' => $deviceId, 'autonomy_level' => 0, 'operating_mode' => 'idle',
            'state_machine_state' => 'idle', 'override_active' => 0, 'passengers' => 0];
    }

    $levelNames = ['L0: No Automation','L1: Driver Assistance','L2: Partial','L3: Conditional','L4: High Automation','L5: Full Automation'];
    $state['autonomy_level_name'] = $levelNames[(int)$state['autonomy_level']] ?? 'Unknown';

    agentos_respond(['ok' => true, 'autonomy' => $state]);
}

function handleSetAutonomyLevel(): void {
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);
    $deviceId = $data['device_id'] ?? '';
    $level = (int)($data['level'] ?? -1);
    if (!$deviceId || $level < 0 || $level > 5) agentos_error('Invalid device_id or level (0-5)');

    $pdo->prepare('UPDATE agentos_autonomy_state SET autonomy_level = ?, updated_at = NOW() WHERE device_id = ?')
        ->execute([$level, $deviceId]);

    agentos_push("device:$deviceId", 'autonomy_level_change', ['level' => $level]);

    // Log decision
    $pdo->prepare('INSERT INTO agentos_auto_decisions (id, device_id, decision_type, trigger_source, decision, confidence, created_at) VALUES (?,?,?,?,?,?,NOW())')
        ->execute([agentos_id('dec'), $deviceId, 'mode_switch', 'operator', "Autonomy level set to L$level", 1.0]);

    agentos_respond(['ok' => true, 'level' => $level]);
}

function handleBehaviorTree(): void {
    $pdo = agentos_pdo();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $deviceId = $data['device_id'] ?? '';
        $treeId = $data['behavior_tree_id'] ?? '';
        if (!$deviceId || !$treeId) agentos_error('Missing device_id or behavior_tree_id');

        // Verify tree exists and device meets autonomy level
        $stmt = $pdo->prepare('SELECT * FROM agentos_behavior_trees WHERE id = ?');
        $stmt->execute([$treeId]);
        $tree = $stmt->fetch();
        if (!$tree) agentos_error('Behavior tree not found', 404);

        $stmt = $pdo->prepare('SELECT autonomy_level FROM agentos_autonomy_state WHERE device_id = ?');
        $stmt->execute([$deviceId]);
        $device = $stmt->fetch();
        if ($device && (int)$device['autonomy_level'] < (int)$tree['required_autonomy_level']) {
            agentos_error("Autonomy level L{$device['autonomy_level']} insufficient — tree requires L{$tree['required_autonomy_level']}");
        }

        $pdo->prepare('UPDATE agentos_autonomy_state SET behavior_tree_id = ?, updated_at = NOW() WHERE device_id = ?')
            ->execute([$treeId, $deviceId]);

        agentos_push("device:$deviceId", 'behavior_tree_change', [
            'tree_id' => $treeId, 'tree_name' => $tree['name'],
            'tree' => json_decode($tree['tree_definition'], true)
        ]);

        agentos_respond(['ok' => true, 'assigned' => true, 'tree_name' => $tree['name']]);
    } else {
        $deviceId = $_GET['device_id'] ?? '';
        if (!$deviceId) agentos_error('Missing device_id');

        $stmt = $pdo->prepare('SELECT bt.* FROM agentos_behavior_trees bt JOIN agentos_autonomy_state a ON a.behavior_tree_id = bt.id WHERE a.device_id = ?');
        $stmt->execute([$deviceId]);
        $tree = $stmt->fetch();
        if ($tree) $tree['tree_definition'] = json_decode($tree['tree_definition'], true);

        agentos_respond(['ok' => true, 'behavior_tree' => $tree]);
    }
}

function handleBehaviorTrees(): void {
    $pdo = agentos_pdo();
    $stmt = $pdo->query('SELECT id, name, description, tree_type, version, required_autonomy_level, active, created_at FROM agentos_behavior_trees WHERE active = 1 ORDER BY tree_type, name');
    agentos_respond(['ok' => true, 'behavior_trees' => $stmt->fetchAll()]);
}

function handleCreateBehavior(): void {
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);

    $id = agentos_id('bt');
    $stmt = $pdo->prepare('INSERT INTO agentos_behavior_trees (id, name, description, tree_type, tree_definition, required_autonomy_level, created_at, updated_at) VALUES (?,?,?,?,?,?,NOW(),NOW())');
    $stmt->execute([
        $id, $data['name'] ?? 'Custom Behavior',
        $data['description'] ?? null,
        $data['tree_type'] ?? 'custom',
        json_encode($data['tree_definition'] ?? []),
        (int)($data['required_autonomy_level'] ?? 3)
    ]);

    agentos_respond(['ok' => true, 'behavior_tree_id' => $id], 201);
}

function handleStateMachine(): void {
    $pdo = agentos_pdo();
    $deviceId = $_GET['device_id'] ?? '';
    if (!$deviceId) agentos_error('Missing device_id');

    $stmt = $pdo->prepare('SELECT state_machine_state, operating_mode, mission_id FROM agentos_autonomy_state WHERE device_id = ?');
    $stmt->execute([$deviceId]);
    $state = $stmt->fetch();
    if (!$state) agentos_error('Device not found', 404);

    $transitions = getStateTransitions();
    $currentState = $state['state_machine_state'] ?? 'idle';

    agentos_respond(['ok' => true, 'current_state' => $currentState,
        'available_transitions' => $transitions[$currentState] ?? [],
        'operating_mode' => $state['operating_mode'],
        'mission_id' => $state['mission_id']
    ]);
}

function handleTransitionState(): void {
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);
    $deviceId = $data['device_id'] ?? '';
    $newState = $data['new_state'] ?? '';
    if (!$deviceId || !$newState) agentos_error('Missing device_id or new_state');

    $stmt = $pdo->prepare('SELECT state_machine_state FROM agentos_autonomy_state WHERE device_id = ?');
    $stmt->execute([$deviceId]);
    $current = $stmt->fetchColumn();

    $transitions = getStateTransitions();
    $allowed = $transitions[$current] ?? [];
    if (!in_array($newState, $allowed, true)) {
        agentos_error("Invalid transition: $current → $newState. Allowed: " . implode(', ', $allowed));
    }

    $mode = match($newState) {
        'idle', 'no_show', 'cancelled' => 'idle',
        'emergency' => 'emergency',
        'charging' => 'charging',
        'maintenance' => 'maintenance',
        default => 'autonomous'
    };

    $pdo->prepare('UPDATE agentos_autonomy_state SET state_machine_state = ?, operating_mode = ?, updated_at = NOW() WHERE device_id = ?')
        ->execute([$newState, $mode, $deviceId]);

    agentos_push("device:$deviceId", 'state_transition', ['from' => $current, 'to' => $newState, 'mode' => $mode]);

    agentos_respond(['ok' => true, 'from' => $current, 'to' => $newState, 'operating_mode' => $mode]);
}

function handleCreateMission(): void {
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);

    $deviceId = $data['device_id'] ?? '';
    if (!$deviceId) agentos_error('Missing device_id');

    $id = agentos_id('msn');
    $stmt = $pdo->prepare('INSERT INTO agentos_auto_missions (id, device_id, mission_type, status, priority, requester_id, requester_name, pickup_lat, pickup_lon, pickup_address, destination_lat, destination_lon, destination_address, waypoints, passenger_count, passenger_names, estimated_pickup_time, estimated_arrival_time, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())');
    $stmt->execute([
        $id, $deviceId,
        $data['mission_type'] ?? 'pickup',
        'created',
        $data['priority'] ?? 'normal',
        $data['requester_id'] ?? null,
        $data['requester_name'] ?? null,
        (float)($data['pickup_lat'] ?? 0),
        (float)($data['pickup_lon'] ?? 0),
        $data['pickup_address'] ?? null,
        (float)($data['destination_lat'] ?? 0),
        (float)($data['destination_lon'] ?? 0),
        $data['destination_address'] ?? null,
        json_encode($data['waypoints'] ?? []),
        (int)($data['passenger_count'] ?? 0),
        json_encode($data['passenger_names'] ?? []),
        $data['estimated_pickup_time'] ?? null,
        $data['estimated_arrival_time'] ?? null
    ]);

    // Update device state
    $pdo->prepare('UPDATE agentos_autonomy_state SET mission_id = ?, state_machine_state = ?, operating_mode = ?, updated_at = NOW() WHERE device_id = ?')
        ->execute([$id, 'dispatched', 'autonomous', $deviceId]);

    agentos_push("device:$deviceId", 'mission_assigned', [
        'mission_id' => $id, 'type' => $data['mission_type'] ?? 'pickup',
        'pickup' => ['lat' => $data['pickup_lat'] ?? 0, 'lon' => $data['pickup_lon'] ?? 0, 'address' => $data['pickup_address'] ?? ''],
        'destination' => ['lat' => $data['destination_lat'] ?? 0, 'lon' => $data['destination_lon'] ?? 0, 'address' => $data['destination_address'] ?? '']
    ]);

    agentos_respond(['ok' => true, 'mission_id' => $id], 201);
}

function handleMissions(): void {
    $pdo = agentos_pdo();
    $deviceId = $_GET['device_id'] ?? null;
    $status = $_GET['status'] ?? null;

    $sql = 'SELECT * FROM agentos_auto_missions WHERE 1=1';
    $params = [];
    if ($deviceId) { $sql .= ' AND device_id = ?'; $params[] = $deviceId; }
    if ($status) { $sql .= ' AND status = ?'; $params[] = $status; }
    $sql .= ' ORDER BY created_at DESC LIMIT 100';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $missions = $stmt->fetchAll();
    foreach ($missions as &$m) {
        $m['waypoints'] = json_decode($m['waypoints'] ?? '[]', true);
        $m['passenger_names'] = json_decode($m['passenger_names'] ?? '[]', true);
    }

    agentos_respond(['ok' => true, 'missions' => $missions]);
}

function handleMissionDetail(): void {
    $pdo = agentos_pdo();
    $missionId = $_GET['mission_id'] ?? '';
    if (!$missionId) agentos_error('Missing mission_id');

    $stmt = $pdo->prepare('SELECT * FROM agentos_auto_missions WHERE id = ?');
    $stmt->execute([$missionId]);
    $mission = $stmt->fetch();
    if (!$mission) agentos_error('Mission not found', 404);

    $mission['waypoints'] = json_decode($mission['waypoints'] ?? '[]', true);
    $mission['passenger_names'] = json_decode($mission['passenger_names'] ?? '[]', true);

    // Passenger events
    $stmt = $pdo->prepare('SELECT * FROM agentos_passenger_events WHERE mission_id = ? ORDER BY created_at');
    $stmt->execute([$missionId]);
    $events = $stmt->fetchAll();
    foreach ($events as &$e) $e['details'] = json_decode($e['details'] ?? '{}', true);
    $mission['passenger_events'] = $events;

    // Decisions during mission
    $stmt = $pdo->prepare('SELECT * FROM agentos_auto_decisions WHERE device_id = ? AND created_at >= ? ORDER BY created_at LIMIT 50');
    $stmt->execute([$mission['device_id'], $mission['created_at']]);
    $decisions = $stmt->fetchAll();
    foreach ($decisions as &$d) {
        $d['context'] = json_decode($d['context'] ?? '{}', true);
        $d['alternatives'] = json_decode($d['alternatives'] ?? '[]', true);
    }
    $mission['decisions'] = $decisions;

    agentos_respond(['ok' => true, 'mission' => $mission]);
}

function handleUpdateMission(): void {
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);
    $missionId = $data['mission_id'] ?? '';
    if (!$missionId) agentos_error('Missing mission_id');

    $fields = [];
    $params = [];
    $allowed = ['status','actual_pickup_time','actual_arrival_time','distance_km','energy_used_percent','fare_usd','rating','feedback','passenger_count'];
    foreach ($allowed as $f) {
        if (isset($data[$f])) { $fields[] = "$f = ?"; $params[] = $data[$f]; }
    }

    if (empty($fields)) agentos_error('No fields to update');
    $fields[] = 'updated_at = NOW()';
    $params[] = $missionId;

    $pdo->prepare('UPDATE agentos_auto_missions SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($params);

    // Update device state machine if status changed
    if (isset($data['status'])) {
        $stmt = $pdo->prepare('SELECT device_id FROM agentos_auto_missions WHERE id = ?');
        $stmt->execute([$missionId]);
        $deviceId = $stmt->fetchColumn();

        $stateMap = [
            'en_route_pickup' => 'en_route_pickup', 'waiting_passenger' => 'at_pickup',
            'boarding' => 'boarding', 'en_route_destination' => 'en_route_destination',
            'arriving' => 'arriving', 'unloading' => 'unloading',
            'completed' => 'idle', 'cancelled' => 'idle', 'failed' => 'idle'
        ];

        if (isset($stateMap[$data['status']])) {
            $mode = in_array($data['status'], ['completed','cancelled','failed']) ? 'idle' : 'autonomous';
            $pdo->prepare('UPDATE agentos_autonomy_state SET state_machine_state = ?, operating_mode = ?, updated_at = NOW() WHERE device_id = ?')
                ->execute([$stateMap[$data['status']], $mode, $deviceId]);
        }
    }

    agentos_respond(['ok' => true, 'updated' => true]);
}

function handlePassengerInteraction(): void {
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);

    $deviceId = $data['device_id'] ?? '';
    if (!$deviceId) agentos_error('Missing device_id');

    $id = agentos_id('pev');
    $stmt = $pdo->prepare('INSERT INTO agentos_passenger_events (id, device_id, mission_id, event_type, passenger_id, passenger_name, details, created_at) VALUES (?,?,?,?,?,?,?,NOW())');
    $stmt->execute([
        $id, $deviceId,
        $data['mission_id'] ?? null,
        $data['event_type'] ?? 'approach',
        $data['passenger_id'] ?? null,
        $data['passenger_name'] ?? null,
        json_encode($data['details'] ?? [])
    ]);

    // Handle door logic
    $doorEvents = ['boarding' => 0, 'door_open' => 0, 'door_close' => 1, 'door_open_dest' => 0, 'door_close_dest' => 1];
    if (isset($doorEvents[$data['event_type']])) {
        $pdo->prepare('UPDATE agentos_autonomy_state SET doors_locked = ?, updated_at = NOW() WHERE device_id = ?')
            ->execute([$doorEvents[$data['event_type']], $deviceId]);
    }

    // Update passenger count
    if ($data['event_type'] === 'boarding') {
        $pdo->prepare('UPDATE agentos_autonomy_state SET passengers = passengers + 1, updated_at = NOW() WHERE device_id = ?')
            ->execute([$deviceId]);
    } elseif ($data['event_type'] === 'alighting') {
        $pdo->prepare('UPDATE agentos_autonomy_state SET passengers = GREATEST(0, passengers - 1), updated_at = NOW() WHERE device_id = ?')
            ->execute([$deviceId]);
    }

    agentos_respond(['ok' => true, 'event_id' => $id]);
}

function handleEnergyPlan(): void {
    $pdo = agentos_pdo();
    $deviceId = $_GET['device_id'] ?? '';
    if (!$deviceId) agentos_error('Missing device_id');

    $stmt = $pdo->prepare('SELECT battery_percent, latitude, longitude FROM agentos_autonomy_state WHERE device_id = ?');
    $stmt->execute([$deviceId]);
    $device = $stmt->fetch();
    if (!$device) agentos_error('Device not found', 404);

    $battery = (float)($device['battery_percent'] ?? 100);

    // Estimate range (rough: 0.5 km per % for a typical robot)
    $estimatedRange = $battery * 0.5;
    $reservePercent = 15;
    $usableRange = max(0, ($battery - $reservePercent) * 0.5);

    $plan = [
        'battery_percent' => $battery,
        'estimated_range_km' => round($estimatedRange, 1),
        'usable_range_km' => round($usableRange, 1),
        'reserve_percent' => $reservePercent,
        'recommendation' => 'normal_operations',
        'can_accept_mission' => true
    ];

    if ($battery < 20) {
        $plan['recommendation'] = 'return_to_charger';
        $plan['can_accept_mission'] = false;
    } elseif ($battery < 40) {
        $plan['recommendation'] = 'short_missions_only';
    }

    agentos_respond(['ok' => true, 'energy_plan' => $plan]);
}

function handleDecisionLog(): void {
    $pdo = agentos_pdo();
    $deviceId = $_GET['device_id'] ?? '';
    if (!$deviceId) agentos_error('Missing device_id');

    $limit = min(max((int)($_GET['limit'] ?? 50), 1), 500);
    $stmt = $pdo->prepare('SELECT * FROM agentos_auto_decisions WHERE device_id = ? ORDER BY created_at DESC LIMIT ?');
    dbExecute($stmt, [$deviceId, $limit]);
    $decisions = $stmt->fetchAll();
    foreach ($decisions as &$d) {
        $d['context'] = json_decode($d['context'] ?? '{}', true);
        $d['alternatives'] = json_decode($d['alternatives'] ?? '[]', true);
    }

    agentos_respond(['ok' => true, 'decisions' => $decisions]);
}

function handleOverride(): void {
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);
    $deviceId = $data['device_id'] ?? '';
    if (!$deviceId) agentos_error('Missing device_id');

    $pdo->prepare('UPDATE agentos_autonomy_state SET override_active = 1, operating_mode = ?, override_operator = ?, override_reason = ?, updated_at = NOW() WHERE device_id = ?')
        ->execute(['teleoperated', $data['operator'] ?? 'unknown', $data['reason'] ?? 'Manual override', $deviceId]);

    agentos_push("device:$deviceId", 'override_activated', ['operator' => $data['operator'] ?? 'unknown']);

    $pdo->prepare('INSERT INTO agentos_auto_decisions (id, device_id, decision_type, trigger_source, decision, confidence, created_at) VALUES (?,?,?,?,?,?,NOW())')
        ->execute([agentos_id('dec'), $deviceId, 'mode_switch', 'operator',
            'Manual override activated by ' . ($data['operator'] ?? 'unknown'), 1.0]);

    agentos_respond(['ok' => true, 'override' => true]);
}

function handleReleaseOverride(): void {
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);
    $deviceId = $data['device_id'] ?? '';
    if (!$deviceId) agentos_error('Missing device_id');

    $stmt = $pdo->prepare('SELECT mission_id FROM agentos_autonomy_state WHERE device_id = ?');
    $stmt->execute([$deviceId]);
    $state = $stmt->fetch();
    $mode = $state && $state['mission_id'] ? 'autonomous' : 'idle';

    $pdo->prepare('UPDATE agentos_autonomy_state SET override_active = 0, operating_mode = ?, override_operator = NULL, override_reason = NULL, updated_at = NOW() WHERE device_id = ?')
        ->execute([$mode, $deviceId]);

    agentos_push("device:$deviceId", 'override_released', ['mode' => $mode]);

    agentos_respond(['ok' => true, 'released' => true, 'operating_mode' => $mode]);
}

function handleCapabilities(): void {
    $capabilities = [
        'autonomy_levels_supported' => [0, 1, 2, 3, 4],
        'operating_modes' => ['idle','autonomous','teleoperated','manual','emergency','charging','maintenance'],
        'mission_types' => ['pickup','dropoff','delivery','patrol','escort','recharge','custom'],
        'max_passengers' => 4,
        'max_payload_kg' => 50,
        'max_speed_mps' => 8.33,
        'sensors' => ['lidar','camera_front','camera_rear','camera_depth','imu','gps','ultrasonic','radar'],
        'communication' => ['wifi','cellular','bluetooth','v2x','mesh'],
        'weather_operation' => ['clear','rain','light_snow','fog'],
        'terrain' => ['paved','sidewalk','parking_lot','indoor','crosswalk'],
        'safety_certifications' => ['ISO-13482','CSA-Z434','UL-3300','IEC-62443'],
        'battery_capacity_kwh' => 5.0,
        'range_km' => 50,
        'charge_time_hours' => 2.5
    ];

    agentos_respond(['ok' => true, 'capabilities' => $capabilities]);
}

function handleAutonomyStats(): void {
    $pdo = agentos_pdo();

    $missionStats = $pdo->query("SELECT status, COUNT(*) as count FROM agentos_auto_missions GROUP BY status")->fetchAll();
    $missionTypes = $pdo->query("SELECT mission_type, COUNT(*) as count FROM agentos_auto_missions GROUP BY mission_type")->fetchAll();
    $completedMissions = (int)$pdo->query("SELECT COUNT(*) as c FROM agentos_auto_missions WHERE status = 'completed'")->fetch()['c'];
    $failedMissions = (int)$pdo->query("SELECT COUNT(*) as c FROM agentos_auto_missions WHERE status IN ('failed','aborted')")->fetch()['c'];
    $avgRating = $pdo->query("SELECT AVG(rating) as avg FROM agentos_auto_missions WHERE rating IS NOT NULL")->fetch();
    $totalPassengers = $pdo->query("SELECT SUM(passenger_count) as total FROM agentos_auto_missions WHERE status = 'completed'")->fetch();
    $totalDistance = $pdo->query("SELECT SUM(distance_km) as total FROM agentos_auto_missions WHERE status = 'completed'")->fetch();

    $decisionStats = $pdo->query("SELECT decision_type, COUNT(*) as count FROM agentos_auto_decisions WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) GROUP BY decision_type ORDER BY count DESC")->fetchAll();
    $avgReaction = $pdo->query("SELECT AVG(reaction_time_ms) as avg FROM agentos_auto_decisions WHERE reaction_time_ms IS NOT NULL AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetch();

    agentos_respond(['ok' => true, 'autonomy_stats' => [
        'missions_by_status' => $missionStats,
        'missions_by_type' => $missionTypes,
        'completed_missions' => $completedMissions,
        'failed_missions' => $failedMissions,
        'success_rate' => ($completedMissions + $failedMissions) > 0
            ? round(($completedMissions / ($completedMissions + $failedMissions)) * 100, 1) : 100,
        'average_rating' => round((float)($avgRating['avg'] ?? 0), 2),
        'total_passengers_transported' => (int)($totalPassengers['total'] ?? 0),
        'total_distance_km' => round((float)($totalDistance['total'] ?? 0), 1),
        'decisions_24h' => $decisionStats,
        'avg_reaction_time_ms' => round((float)($avgReaction['avg'] ?? 0), 1)
    ]]);
}

// ─── Router ────────────────────────────────────────────────────
$action = $_GET['action'] ?? $_POST['action'] ?? '';

$routes = [
    'autonomy_status'       => 'handleAutonomyStatus',
    'set_autonomy_level'    => 'handleSetAutonomyLevel',
    'behavior_tree'         => 'handleBehaviorTree',
    'behavior_trees'        => 'handleBehaviorTrees',
    'create_behavior'       => 'handleCreateBehavior',
    'state_machine'         => 'handleStateMachine',
    'transition_state'      => 'handleTransitionState',
    'create_mission'        => 'handleCreateMission',
    'missions'              => 'handleMissions',
    'mission_detail'        => 'handleMissionDetail',
    'update_mission'        => 'handleUpdateMission',
    'passenger_interaction' => 'handlePassengerInteraction',
    'energy_plan'           => 'handleEnergyPlan',
    'decision_log'          => 'handleDecisionLog',
    'override'              => 'handleOverride',
    'release_override'      => 'handleReleaseOverride',
    'capabilities'          => 'handleCapabilities',
    'autonomy_stats'        => 'handleAutonomyStats',
];

if (!isset($routes[$action])) {
    agentos_respond(['ok' => true, 'module' => 'Alfred OS — Robot Autonomy Controller', 'version' => AGENTOS_VERSION,
        'description' => 'SAE J3016 autonomy levels, behavior trees, state machines, passenger transport, decision logging',
        'endpoints' => array_keys($routes),
        'autonomy_levels' => ['L0: No Automation','L1: Driver Assistance','L2: Partial','L3: Conditional','L4: High Automation','L5: Full Automation']]);
}

$routes[$action]();
