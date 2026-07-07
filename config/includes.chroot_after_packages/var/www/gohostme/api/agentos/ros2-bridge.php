<?php
/**
 * GSM Alfred OS — ROS2 Bridge API
 * 
 * Bridges Alfred OS fleet management to Robot Operating System 2 (ROS2) nodes.
 * Translates between HTTP/WebSocket APIs and ROS2 pub/sub topics, services, actions.
 * Manages ROS2 node lifecycle, parameter configuration, and diagnostics.
 *
 * This is the critical link between the cloud platform and physical robot hardware.
 * Every motor command, sensor reading, and navigation goal passes through this bridge.
 *
 * Endpoints (16):
 *   nodes                — List active ROS2 nodes on a device
 *   node_info            — Detailed info for a specific node
 *   register_node        — Register a new ROS2 node
 *   deregister_node      — Remove a ROS2 node
 *   topics               — List active ROS2 topics
 *   publish_topic        — Publish message to a ROS2 topic
 *   subscribe_topic      — Register a subscriber callback
 *   call_service         — Call a ROS2 service
 *   services             — List available ROS2 services
 *   send_action_goal     — Send an action goal (e.g., navigate, pick_up)
 *   cancel_action        — Cancel a running action
 *   action_status        — Get action execution status
 *   set_params           — Set node parameters
 *   get_params           — Get node parameters
 *   tf_lookup            — Look up TF2 transform between frames
 *   diagnostics          — Get ROS2 diagnostics aggregation
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
    header('Access-Control-Allow-Methods: GET,POST,OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type,Authorization,X-Internal-Secret,X-Device-Id');
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// ─── Schema ─────────────────────────────────────────────────────
function ros2EnsureSchema(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    $pdo = agentos_pdo();

    $pdo->exec("CREATE TABLE IF NOT EXISTS agentos_ros2_nodes (
        id VARCHAR(32) PRIMARY KEY,
        device_id VARCHAR(64) NOT NULL,
        node_name VARCHAR(128) NOT NULL,
        node_namespace VARCHAR(128) DEFAULT '/',
        node_type ENUM('sensor','actuator','controller','planner','perception','localization','driver','bridge','custom') NOT NULL,
        package_name VARCHAR(128),
        executable VARCHAR(128),
        status ENUM('running','stopped','error','starting','shutting_down') NOT NULL DEFAULT 'stopped',
        parameters JSON,
        remappings JSON,
        qos_profile ENUM('sensor_data','system_default','services','parameters','reliable') DEFAULT 'system_default',
        cpu_percent FLOAT DEFAULT 0,
        mem_mb FLOAT DEFAULT 0,
        msg_rate FLOAT DEFAULT 0,
        last_heartbeat DATETIME,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        UNIQUE KEY uk_device_node (device_id, node_name, node_namespace),
        INDEX idx_device (device_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS agentos_ros2_topics (
        id VARCHAR(32) PRIMARY KEY,
        device_id VARCHAR(64) NOT NULL,
        topic_name VARCHAR(256) NOT NULL,
        msg_type VARCHAR(128) NOT NULL,
        direction ENUM('pub','sub','both') NOT NULL,
        qos_reliability ENUM('reliable','best_effort') DEFAULT 'reliable',
        qos_durability ENUM('transient_local','volatile') DEFAULT 'volatile',
        qos_history_depth INT UNSIGNED DEFAULT 10,
        publishers INT UNSIGNED DEFAULT 0,
        subscribers INT UNSIGNED DEFAULT 0,
        msg_rate FLOAT DEFAULT 0,
        last_message_at DATETIME,
        created_at DATETIME NOT NULL,
        UNIQUE KEY uk_device_topic (device_id, topic_name),
        INDEX idx_device (device_id),
        INDEX idx_type (msg_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS agentos_ros2_services (
        id VARCHAR(32) PRIMARY KEY,
        device_id VARCHAR(64) NOT NULL,
        service_name VARCHAR(256) NOT NULL,
        srv_type VARCHAR(128) NOT NULL,
        node_id VARCHAR(32),
        status ENUM('available','busy','unavailable') DEFAULT 'available',
        call_count INT UNSIGNED DEFAULT 0,
        avg_response_ms FLOAT DEFAULT 0,
        last_called_at DATETIME,
        created_at DATETIME NOT NULL,
        UNIQUE KEY uk_device_service (device_id, service_name),
        INDEX idx_device (device_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS agentos_ros2_actions (
        id VARCHAR(32) PRIMARY KEY,
        device_id VARCHAR(64) NOT NULL,
        action_name VARCHAR(256) NOT NULL,
        action_type VARCHAR(128) NOT NULL,
        goal_id VARCHAR(64),
        goal_data JSON,
        status ENUM('pending','accepted','executing','canceling','succeeded','aborted','canceled') NOT NULL DEFAULT 'pending',
        feedback JSON,
        result JSON,
        progress_percent FLOAT DEFAULT 0,
        submitted_by VARCHAR(64),
        submitted_at DATETIME NOT NULL,
        started_at DATETIME,
        completed_at DATETIME,
        INDEX idx_device (device_id),
        INDEX idx_status (status),
        INDEX idx_goal (goal_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS agentos_ros2_transforms (
        id VARCHAR(32) PRIMARY KEY,
        device_id VARCHAR(64) NOT NULL,
        parent_frame VARCHAR(64) NOT NULL,
        child_frame VARCHAR(64) NOT NULL,
        translation_x DOUBLE DEFAULT 0,
        translation_y DOUBLE DEFAULT 0,
        translation_z DOUBLE DEFAULT 0,
        rotation_x DOUBLE DEFAULT 0,
        rotation_y DOUBLE DEFAULT 0,
        rotation_z DOUBLE DEFAULT 0,
        rotation_w DOUBLE DEFAULT 1,
        is_static TINYINT(1) DEFAULT 0,
        updated_at DATETIME NOT NULL,
        UNIQUE KEY uk_device_frames (device_id, parent_frame, child_frame),
        INDEX idx_device (device_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Seed standard ROS2 topic types
    $stmt = $pdo->query("SELECT COUNT(*) as c FROM agentos_ros2_topics");
    if ((int)$stmt->fetch()['c'] === 0) {
        // Standard topics are created per-device at registration time
    }
}

ros2EnsureSchema();
$auth = agentos_auth();

// ─── Standard ROS2 Message Types ─────────────────────────────
$STANDARD_MSG_TYPES = [
    'geometry_msgs/msg/Twist',
    'geometry_msgs/msg/PoseStamped',
    'geometry_msgs/msg/TransformStamped',
    'sensor_msgs/msg/LaserScan',
    'sensor_msgs/msg/Image',
    'sensor_msgs/msg/Imu',
    'sensor_msgs/msg/JointState',
    'sensor_msgs/msg/BatteryState',
    'sensor_msgs/msg/PointCloud2',
    'sensor_msgs/msg/NavSatFix',
    'nav_msgs/msg/Odometry',
    'nav_msgs/msg/OccupancyGrid',
    'nav_msgs/msg/Path',
    'std_msgs/msg/String',
    'std_msgs/msg/Bool',
    'std_msgs/msg/Float64',
    'diagnostic_msgs/msg/DiagnosticArray',
    'tf2_msgs/msg/TFMessage',
    'action_msgs/msg/GoalStatusArray',
    'control_msgs/msg/JointTrajectoryControllerState',
    'moveit_msgs/msg/RobotState',
    'alfred_msgs/msg/SafetyStatus',
    'alfred_msgs/msg/FleetTelemetry',
    'alfred_msgs/msg/AutonomyState',
];

// ─── Handlers ───────────────────────────────────────────────────

function handleNodes(): void {
    $pdo = agentos_pdo();
    $deviceId = $_GET['device_id'] ?? '';
    if (!$deviceId) agentos_error('Missing device_id');

    $stmt = $pdo->prepare('SELECT * FROM agentos_ros2_nodes WHERE device_id = ? ORDER BY node_namespace, node_name');
    $stmt->execute([$deviceId]);
    $nodes = $stmt->fetchAll();
    foreach ($nodes as &$n) {
        $n['parameters'] = json_decode($n['parameters'] ?? '{}', true);
        $n['remappings'] = json_decode($n['remappings'] ?? '[]', true);
    }
    agentos_respond(['ok' => true, 'nodes' => $nodes]);
}

function handleNodeInfo(): void {
    $pdo = agentos_pdo();
    $nodeId = $_GET['node_id'] ?? '';
    if (!$nodeId) agentos_error('Missing node_id');

    $stmt = $pdo->prepare('SELECT * FROM agentos_ros2_nodes WHERE id = ?');
    $stmt->execute([$nodeId]);
    $node = $stmt->fetch();
    if (!$node) agentos_error('Node not found', 404);

    $node['parameters'] = json_decode($node['parameters'] ?? '{}', true);
    $node['remappings'] = json_decode($node['remappings'] ?? '[]', true);

    // Get associated topics
    $stmt = $pdo->prepare('SELECT topic_name, msg_type, direction, msg_rate FROM agentos_ros2_topics WHERE device_id = ?');
    $stmt->execute([$node['device_id']]);
    $node['topics'] = $stmt->fetchAll();

    // Get associated services
    $stmt = $pdo->prepare('SELECT service_name, srv_type, status FROM agentos_ros2_services WHERE device_id = ? AND node_id = ?');
    $stmt->execute([$node['device_id'], $nodeId]);
    $node['services'] = $stmt->fetchAll();

    agentos_respond(['ok' => true, 'node' => $node]);
}

function handleRegisterNode(): void {
    $auth = agentos_auth();
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);

    $required = ['device_id', 'node_name', 'node_type'];
    foreach ($required as $f) {
        if (empty($data[$f])) agentos_error("Missing field: $f");
    }

    $validTypes = ['sensor','actuator','controller','planner','perception','localization','driver','bridge','custom'];
    if (!in_array($data['node_type'], $validTypes, true)) agentos_error('Invalid node_type');

    $id = agentos_id('ros2');
    $stmt = $pdo->prepare('INSERT INTO agentos_ros2_nodes 
        (id, device_id, node_name, node_namespace, node_type, package_name, executable, status, parameters, remappings, qos_profile, created_at, updated_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())');
    $stmt->execute([
        $id,
        $data['device_id'],
        $data['node_name'],
        $data['node_namespace'] ?? '/',
        $data['node_type'],
        $data['package_name'] ?? null,
        $data['executable'] ?? null,
        'stopped',
        json_encode($data['parameters'] ?? []),
        json_encode($data['remappings'] ?? []),
        $data['qos_profile'] ?? 'system_default'
    ]);

    // Auto-register standard topics for this node type
    $standardTopics = getStandardTopicsForType($data['node_type'], $data['device_id'], $data['node_name']);
    foreach ($standardTopics as $topic) {
        $pdo->prepare('INSERT IGNORE INTO agentos_ros2_topics 
            (id, device_id, topic_name, msg_type, direction, qos_reliability, created_at)
            VALUES (?,?,?,?,?,?,NOW())')
            ->execute([agentos_id('topic'), $data['device_id'], $topic['name'], $topic['type'], $topic['dir'], $topic['qos']]);
    }

    agentos_audit(['action_type' => 'ros2_register_node', 'user_id' => $auth['user_id'], 'status' => 'success',
        'metadata' => ['node_id' => $id, 'device_id' => $data['device_id'], 'node_name' => $data['node_name']]]);

    agentos_respond(['ok' => true, 'node_id' => $id], 201);
}

function getStandardTopicsForType(string $type, string $deviceId, string $nodeName): array {
    $ns = "/$nodeName";
    $topics = [];
    switch ($type) {
        case 'sensor':
            $topics = [
                ['name' => "$ns/scan", 'type' => 'sensor_msgs/msg/LaserScan', 'dir' => 'pub', 'qos' => 'best_effort'],
                ['name' => "$ns/imu", 'type' => 'sensor_msgs/msg/Imu', 'dir' => 'pub', 'qos' => 'best_effort'],
                ['name' => "$ns/image_raw", 'type' => 'sensor_msgs/msg/Image', 'dir' => 'pub', 'qos' => 'best_effort'],
                ['name' => "$ns/battery", 'type' => 'sensor_msgs/msg/BatteryState', 'dir' => 'pub', 'qos' => 'reliable'],
            ];
            break;
        case 'actuator':
            $topics = [
                ['name' => "$ns/cmd_vel", 'type' => 'geometry_msgs/msg/Twist', 'dir' => 'sub', 'qos' => 'reliable'],
                ['name' => "$ns/joint_states", 'type' => 'sensor_msgs/msg/JointState', 'dir' => 'pub', 'qos' => 'reliable'],
            ];
            break;
        case 'controller':
            $topics = [
                ['name' => "$ns/cmd_vel", 'type' => 'geometry_msgs/msg/Twist', 'dir' => 'pub', 'qos' => 'reliable'],
                ['name' => "$ns/odom", 'type' => 'nav_msgs/msg/Odometry', 'dir' => 'sub', 'qos' => 'reliable'],
            ];
            break;
        case 'planner':
            $topics = [
                ['name' => "$ns/plan", 'type' => 'nav_msgs/msg/Path', 'dir' => 'pub', 'qos' => 'reliable'],
                ['name' => "$ns/goal_pose", 'type' => 'geometry_msgs/msg/PoseStamped', 'dir' => 'sub', 'qos' => 'reliable'],
                ['name' => "$ns/costmap", 'type' => 'nav_msgs/msg/OccupancyGrid', 'dir' => 'sub', 'qos' => 'reliable'],
            ];
            break;
        case 'perception':
            $topics = [
                ['name' => "$ns/pointcloud", 'type' => 'sensor_msgs/msg/PointCloud2', 'dir' => 'pub', 'qos' => 'best_effort'],
                ['name' => "$ns/detections", 'type' => 'std_msgs/msg/String', 'dir' => 'pub', 'qos' => 'reliable'],
            ];
            break;
        case 'localization':
            $topics = [
                ['name' => "$ns/odom", 'type' => 'nav_msgs/msg/Odometry', 'dir' => 'pub', 'qos' => 'reliable'],
                ['name' => "$ns/map", 'type' => 'nav_msgs/msg/OccupancyGrid', 'dir' => 'pub', 'qos' => 'reliable'],
                ['name' => "$ns/gps/fix", 'type' => 'sensor_msgs/msg/NavSatFix', 'dir' => 'sub', 'qos' => 'best_effort'],
            ];
            break;
    }
    return $topics;
}

function handleDeregisterNode(): void {
    $auth = agentos_auth();
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);
    $nodeId = $data['node_id'] ?? '';
    if (!$nodeId) agentos_error('Missing node_id');

    $stmt = $pdo->prepare('SELECT * FROM agentos_ros2_nodes WHERE id = ?');
    $stmt->execute([$nodeId]);
    $node = $stmt->fetch();
    if (!$node) agentos_error('Node not found', 404);

    // Cancel any running actions
    $pdo->prepare("UPDATE agentos_ros2_actions SET status = 'canceled', completed_at = NOW() WHERE device_id = ? AND status IN ('pending','accepted','executing')")
        ->execute([$node['device_id']]);

    $pdo->prepare('DELETE FROM agentos_ros2_nodes WHERE id = ?')->execute([$nodeId]);

    agentos_audit(['action_type' => 'ros2_deregister_node', 'user_id' => $auth['user_id'], 'status' => 'success',
        'metadata' => ['node_id' => $nodeId]]);

    agentos_respond(['ok' => true, 'deregistered' => true]);
}

function handleTopics(): void {
    $pdo = agentos_pdo();
    $deviceId = $_GET['device_id'] ?? '';
    if (!$deviceId) agentos_error('Missing device_id');

    $stmt = $pdo->prepare('SELECT * FROM agentos_ros2_topics WHERE device_id = ? ORDER BY topic_name');
    $stmt->execute([$deviceId]);
    agentos_respond(['ok' => true, 'topics' => $stmt->fetchAll()]);
}

function handlePublishTopic(): void {
    $auth = agentos_auth();
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);

    $deviceId = $data['device_id'] ?? '';
    $topicName = $data['topic_name'] ?? '';
    $message = $data['message'] ?? null;

    if (!$deviceId || !$topicName || $message === null) agentos_error('Missing device_id, topic_name, or message');

    // Safety check: block direct cmd_vel from cloud if safety limits are active
    if (strpos($topicName, 'cmd_vel') !== false) {
        $safetyCheck = checkSafetyForCommand($pdo, $deviceId, $message);
        if (!$safetyCheck['allowed']) {
            agentos_error('Safety interlock: ' . $safetyCheck['reason'], 403);
        }
    }

    // Update topic stats
    $pdo->prepare('UPDATE agentos_ros2_topics SET msg_rate = msg_rate + 1, last_message_at = NOW() WHERE device_id = ? AND topic_name = ?')
        ->execute([$deviceId, $topicName]);

    // Push to device via WebSocket bridge
    agentos_push("device:$deviceId", 'ros2_publish', [
        'topic' => $topicName,
        'message' => $message,
        'timestamp' => microtime(true)
    ]);

    // Forward via MQTT if topic is telemetry-class
    $r = agentos_redis();
    if ($r) {
        $r->publish("ros2:$deviceId:$topicName", json_encode($message));
    }

    agentos_respond(['ok' => true, 'published' => true]);
}

function checkSafetyForCommand(PDO $pdo, string $deviceId, $message): array {
    // Check if E-stop is active
    try {
        $stmt = $pdo->prepare("SELECT status FROM agentos_safety_interlocks WHERE device_id = ? AND interlock_type = 'emergency_stop' AND status = 'triggered'");
        $stmt->execute([$deviceId]);
        if ($stmt->fetch()) {
            return ['allowed' => false, 'reason' => 'E-Stop is active — all motion commands blocked'];
        }

        // Check velocity limits from safety module
        $stmt = $pdo->prepare("SELECT limits FROM agentos_safety_limits WHERE device_id = ?");
        $stmt->execute([$deviceId]);
        $limits = $stmt->fetch();
        if ($limits) {
            $lim = json_decode($limits['limits'], true);
            $maxSpeed = $lim['max_speed'] ?? 1.5;
            if (isset($message['linear'])) {
                $speed = sqrt(
                    pow($message['linear']['x'] ?? 0, 2) +
                    pow($message['linear']['y'] ?? 0, 2) +
                    pow($message['linear']['z'] ?? 0, 2)
                );
                if ($speed > $maxSpeed) {
                    return ['allowed' => false, 'reason' => "Velocity {$speed}m/s exceeds safety limit {$maxSpeed}m/s"];
                }
            }
        }
    } catch (\Throwable $e) {
        // Safety tables may not exist yet — allow but log
        error_log("[ROS2-SAFETY] Could not check safety: " . $e->getMessage());
    }

    return ['allowed' => true, 'reason' => ''];
}

function handleSubscribeTopic(): void {
    $auth = agentos_auth();
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);

    $deviceId = $data['device_id'] ?? '';
    $topicName = $data['topic_name'] ?? '';
    $callbackUrl = $data['callback_url'] ?? '';

    if (!$deviceId || !$topicName) agentos_error('Missing device_id or topic_name');

    // Update subscriber count
    $pdo->prepare('UPDATE agentos_ros2_topics SET subscribers = subscribers + 1 WHERE device_id = ? AND topic_name = ?')
        ->execute([$deviceId, $topicName]);

    // Register subscription push via WebSocket
    agentos_push("device:$deviceId", 'ros2_subscribe', [
        'topic' => $topicName,
        'subscriber_id' => $auth['user_id'] ?? 'anonymous'
    ]);

    agentos_respond(['ok' => true, 'subscribed' => true, 'topic' => $topicName]);
}

function handleCallService(): void {
    $auth = agentos_auth();
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);

    $deviceId = $data['device_id'] ?? '';
    $serviceName = $data['service_name'] ?? '';
    $request = $data['request'] ?? [];

    if (!$deviceId || !$serviceName) agentos_error('Missing device_id or service_name');

    // Update service stats
    $pdo->prepare('UPDATE agentos_ros2_services SET call_count = call_count + 1, last_called_at = NOW(), status = ? WHERE device_id = ? AND service_name = ?')
        ->execute(['busy', $deviceId, $serviceName]);

    // Push service call to device
    $callId = agentos_id('call');
    agentos_push("device:$deviceId", 'ros2_service_call', [
        'call_id' => $callId,
        'service' => $serviceName,
        'request' => $request
    ]);

    agentos_audit(['action_type' => 'ros2_service_call', 'user_id' => $auth['user_id'], 'status' => 'success',
        'metadata' => ['device_id' => $deviceId, 'service' => $serviceName, 'call_id' => $callId]]);

    agentos_respond(['ok' => true, 'call_id' => $callId, 'status' => 'dispatched']);
}

function handleServices(): void {
    $pdo = agentos_pdo();
    $deviceId = $_GET['device_id'] ?? '';
    if (!$deviceId) agentos_error('Missing device_id');

    $stmt = $pdo->prepare('SELECT * FROM agentos_ros2_services WHERE device_id = ? ORDER BY service_name');
    $stmt->execute([$deviceId]);
    agentos_respond(['ok' => true, 'services' => $stmt->fetchAll()]);
}

function handleSendActionGoal(): void {
    $auth = agentos_auth();
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);

    $deviceId = $data['device_id'] ?? '';
    $actionName = $data['action_name'] ?? '';
    $actionType = $data['action_type'] ?? '';
    $goalData = $data['goal'] ?? [];

    if (!$deviceId || !$actionName || !$actionType) agentos_error('Missing device_id, action_name, or action_type');

    $id = agentos_id('act');
    $goalId = bin2hex(random_bytes(8));

    $stmt = $pdo->prepare('INSERT INTO agentos_ros2_actions 
        (id, device_id, action_name, action_type, goal_id, goal_data, status, progress_percent, submitted_by, submitted_at)
        VALUES (?,?,?,?,?,?,?,?,?,NOW())');
    $stmt->execute([$id, $deviceId, $actionName, $actionType, $goalId, json_encode($goalData), 'pending', 0, $auth['user_id']]);

    // Push action goal to device
    agentos_push("device:$deviceId", 'ros2_action_goal', [
        'action_id' => $id,
        'goal_id' => $goalId,
        'action_name' => $actionName,
        'action_type' => $actionType,
        'goal' => $goalData
    ]);

    agentos_audit(['action_type' => 'ros2_action_goal', 'user_id' => $auth['user_id'], 'status' => 'success',
        'metadata' => ['device_id' => $deviceId, 'action' => $actionName, 'goal_id' => $goalId]]);

    agentos_respond(['ok' => true, 'action_id' => $id, 'goal_id' => $goalId, 'status' => 'pending'], 201);
}

function handleCancelAction(): void {
    $auth = agentos_auth();
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);
    $actionId = $data['action_id'] ?? '';
    if (!$actionId) agentos_error('Missing action_id');

    $stmt = $pdo->prepare('SELECT * FROM agentos_ros2_actions WHERE id = ?');
    $stmt->execute([$actionId]);
    $action = $stmt->fetch();
    if (!$action) agentos_error('Action not found', 404);
    if (in_array($action['status'], ['succeeded','aborted','canceled'], true)) {
        agentos_error('Action already completed');
    }

    $pdo->prepare("UPDATE agentos_ros2_actions SET status = 'canceling' WHERE id = ?")
        ->execute([$actionId]);

    agentos_push("device:{$action['device_id']}", 'ros2_action_cancel', [
        'action_id' => $actionId,
        'goal_id' => $action['goal_id']
    ]);

    agentos_respond(['ok' => true, 'status' => 'canceling']);
}

function handleActionStatus(): void {
    $pdo = agentos_pdo();
    $actionId = $_GET['action_id'] ?? '';
    if (!$actionId) agentos_error('Missing action_id');

    $stmt = $pdo->prepare('SELECT * FROM agentos_ros2_actions WHERE id = ?');
    $stmt->execute([$actionId]);
    $action = $stmt->fetch();
    if (!$action) agentos_error('Action not found', 404);

    $action['goal_data'] = json_decode($action['goal_data'] ?? '{}', true);
    $action['feedback'] = json_decode($action['feedback'] ?? '{}', true);
    $action['result'] = json_decode($action['result'] ?? '{}', true);

    agentos_respond(['ok' => true, 'action' => $action]);
}

function handleSetParams(): void {
    $auth = agentos_auth();
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);

    $nodeId = $data['node_id'] ?? '';
    $params = $data['parameters'] ?? [];
    if (!$nodeId || empty($params)) agentos_error('Missing node_id or parameters');

    $stmt = $pdo->prepare('SELECT device_id, parameters FROM agentos_ros2_nodes WHERE id = ?');
    $stmt->execute([$nodeId]);
    $node = $stmt->fetch();
    if (!$node) agentos_error('Node not found', 404);

    $existing = json_decode($node['parameters'] ?? '{}', true);
    $merged = array_merge($existing, $params);

    $pdo->prepare('UPDATE agentos_ros2_nodes SET parameters = ?, updated_at = NOW() WHERE id = ?')
        ->execute([json_encode($merged), $nodeId]);

    // Push param update to device
    agentos_push("device:{$node['device_id']}", 'ros2_set_params', [
        'node_id' => $nodeId,
        'parameters' => $params
    ]);

    agentos_respond(['ok' => true, 'parameters' => $merged]);
}

function handleGetParams(): void {
    $pdo = agentos_pdo();
    $nodeId = $_GET['node_id'] ?? '';
    if (!$nodeId) agentos_error('Missing node_id');

    $stmt = $pdo->prepare('SELECT parameters FROM agentos_ros2_nodes WHERE id = ?');
    $stmt->execute([$nodeId]);
    $node = $stmt->fetch();
    if (!$node) agentos_error('Node not found', 404);

    agentos_respond(['ok' => true, 'parameters' => json_decode($node['parameters'] ?? '{}', true)]);
}

function handleTfLookup(): void {
    $pdo = agentos_pdo();
    $deviceId = $_GET['device_id'] ?? '';
    $parentFrame = $_GET['parent_frame'] ?? '';
    $childFrame = $_GET['child_frame'] ?? '';

    if (!$deviceId) agentos_error('Missing device_id');

    if ($parentFrame && $childFrame) {
        $stmt = $pdo->prepare('SELECT * FROM agentos_ros2_transforms WHERE device_id = ? AND parent_frame = ? AND child_frame = ?');
        $stmt->execute([$deviceId, $parentFrame, $childFrame]);
        $tf = $stmt->fetch();
        if (!$tf) agentos_error('Transform not found', 404);
        agentos_respond(['ok' => true, 'transform' => $tf]);
    } else {
        $stmt = $pdo->prepare('SELECT * FROM agentos_ros2_transforms WHERE device_id = ? ORDER BY parent_frame, child_frame');
        $stmt->execute([$deviceId]);
        agentos_respond(['ok' => true, 'transforms' => $stmt->fetchAll()]);
    }
}

function handleDiagnostics(): void {
    $pdo = agentos_pdo();
    $deviceId = $_GET['device_id'] ?? '';
    if (!$deviceId) agentos_error('Missing device_id');

    // Aggregate node health
    $stmt = $pdo->prepare('SELECT node_name, node_type, status, cpu_percent, mem_mb, msg_rate, last_heartbeat FROM agentos_ros2_nodes WHERE device_id = ?');
    $stmt->execute([$deviceId]);
    $nodes = $stmt->fetchAll();

    $healthy = 0;
    $warnings = [];
    $errors = [];
    foreach ($nodes as $n) {
        if ($n['status'] === 'running') {
            $healthy++;
            // Heartbeat stale > 10 seconds
            if ($n['last_heartbeat'] && strtotime($n['last_heartbeat']) < time() - 10) {
                $warnings[] = "{$n['node_name']}: heartbeat stale (last: {$n['last_heartbeat']})";
            }
            if ($n['cpu_percent'] > 90) {
                $warnings[] = "{$n['node_name']}: high CPU ({$n['cpu_percent']}%)";
            }
            if ($n['mem_mb'] > 500) {
                $warnings[] = "{$n['node_name']}: high memory ({$n['mem_mb']}MB)";
            }
        } elseif ($n['status'] === 'error') {
            $errors[] = "{$n['node_name']}: node in error state";
        }
    }

    // Topic health
    $stmt = $pdo->prepare('SELECT COUNT(*) as total, SUM(CASE WHEN last_message_at > DATE_SUB(NOW(), INTERVAL 30 SECOND) THEN 1 ELSE 0 END) as active FROM agentos_ros2_topics WHERE device_id = ?');
    $stmt->execute([$deviceId]);
    $topicHealth = $stmt->fetch();

    // Active actions
    $stmt = $pdo->prepare("SELECT COUNT(*) as c FROM agentos_ros2_actions WHERE device_id = ? AND status IN ('pending','accepted','executing')");
    $stmt->execute([$deviceId]);
    $activeActions = (int)$stmt->fetch()['c'];

    $overall = (empty($errors) && count($warnings) < 3) ? 'healthy' : (!empty($errors) ? 'error' : 'degraded');

    agentos_respond(['ok' => true, 'diagnostics' => [
        'device_id' => $deviceId,
        'overall_status' => $overall,
        'nodes_total' => count($nodes),
        'nodes_healthy' => $healthy,
        'nodes_error' => count($errors),
        'topics_total' => (int)$topicHealth['total'],
        'topics_active' => (int)$topicHealth['active'],
        'active_actions' => $activeActions,
        'warnings' => $warnings,
        'errors' => $errors,
        'nodes' => $nodes,
        'checked_at' => date('c')
    ]]);
}

// ─── Router ────────────────────────────────────────────────────
$action = $_GET['action'] ?? $_POST['action'] ?? '';

$routes = [
    'nodes'             => 'handleNodes',
    'node_info'         => 'handleNodeInfo',
    'register_node'     => 'handleRegisterNode',
    'deregister_node'   => 'handleDeregisterNode',
    'topics'            => 'handleTopics',
    'publish_topic'     => 'handlePublishTopic',
    'subscribe_topic'   => 'handleSubscribeTopic',
    'call_service'      => 'handleCallService',
    'services'          => 'handleServices',
    'send_action_goal'  => 'handleSendActionGoal',
    'cancel_action'     => 'handleCancelAction',
    'action_status'     => 'handleActionStatus',
    'set_params'        => 'handleSetParams',
    'get_params'        => 'handleGetParams',
    'tf_lookup'         => 'handleTfLookup',
    'diagnostics'       => 'handleDiagnostics',
];

if (!isset($routes[$action])) {
    agentos_respond(['ok' => true, 'module' => 'Alfred OS — ROS2 Bridge', 'version' => AGENTOS_VERSION,
        'description' => 'Bridges Alfred OS to Robot Operating System 2 nodes',
        'endpoints' => array_keys($routes),
        'supported_msg_types' => $STANDARD_MSG_TYPES]);
}

$routes[$action]();
