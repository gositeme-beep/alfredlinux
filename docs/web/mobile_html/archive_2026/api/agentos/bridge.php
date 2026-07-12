<?php
/**
 * GSM Alfred OS — Device Bridge API v2.0
 * Server-side ROS2 / IoT / Physical device interface
 * Phase 4: Digital twin sync, telemetry history, sensor pipeline, fleet status
 *
 * Endpoints:
 *   GET    ?action=list              — List registered devices
 *   GET    ?action=get&id=X          — Get device details + twin state
 *   POST   ?action=register          — Register a new device
 *   POST   ?action=command           — Send command to device
 *   POST   ?action=telemetry         — Submit telemetry data (with history)
 *   GET    ?action=telemetry&id=X    — Get latest telemetry
 *   POST   ?action=heartbeat         — Device heartbeat
 *   POST   ?action=emergency_stop    — Emergency stop a device
 *   GET    ?action=ros2_topics       — List ROS2 topics (if connected)
 *   GET    ?action=fleet_status      — Fleet-wide device overview
 *   POST   ?action=twin_sync         — Sync digital twin state
 *   GET    ?action=twin_snapshot     — Get/create twin snapshot
 *   POST   ?action=twin_snapshot     — Create manual twin snapshot
 *   GET    ?action=telemetry_history  — Historical telemetry for a device
 *   POST   ?action=group_create      — Create device group
 *   GET    ?action=groups            — List device groups
 *   POST   ?action=group_command     — Send command to device group
 *   GET    ?action=sensor_pipeline   — Real-time sensor aggregation
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Internal-Secret, X-Device-Token');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

define('GOSITEME_API', true);
require_once __DIR__ . '/bootstrap.php';
agentos_ensure_schema();

$auth = agentos_auth();
$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':            handleList($auth); break;
    case 'get':             handleGet($auth); break;
    case 'register':        handleRegister($auth); break;
    case 'command':         handleCommand($auth); break;
    case 'telemetry':       handleTelemetry($auth); break;
    case 'heartbeat':       handleHeartbeat($auth); break;
    case 'emergency_stop':  handleEmergencyStop($auth); break;
    case 'ros2_topics':     handleRos2Topics($auth); break;
    case 'fleet_status':    handleFleetStatus($auth); break;
    case 'twin_sync':       handleTwinSync($auth); break;
    case 'twin_snapshot':   handleTwinSnapshot($auth); break;
    case 'telemetry_history': handleTelemetryHistory($auth); break;
    case 'group_create':    handleGroupCreate($auth); break;
    case 'groups':          handleGroups($auth); break;
    case 'group_command':   handleGroupCommand($auth); break;
    case 'sensor_pipeline': handleSensorPipeline($auth); break;
    default:                agentos_error('Unknown action');
}

function handleList(array $auth): void {
    $pdo = agentos_pdo();
    $where = ['1=1'];
    $params = [];

    if (isset($_GET['type'])) {
        $where[] = 'device_type=?';
        $params[] = $_GET['type'];
    }
    if (isset($_GET['status'])) {
        $where[] = 'status=?';
        $params[] = $_GET['status'];
    }

    $stmt = $pdo->prepare("SELECT device_id, device_type, display_name, status, 
        protocol, capabilities, last_heartbeat
        FROM agentos_devices WHERE " . implode(' AND ', $where) . " ORDER BY display_name");
    $stmt->execute($params);
    $devices = $stmt->fetchAll();

    foreach ($devices as &$d) {
        $d['capabilities'] = json_decode($d['capabilities'] ?? '[]', true);
    }

    agentos_respond(['ok' => true, 'devices' => $devices]);
}

function handleGet(array $auth): void {
    $id = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['id'] ?? '');
    if (!$id) agentos_error('id required');

    $pdo = agentos_pdo();
    $stmt = $pdo->prepare("SELECT * FROM agentos_devices WHERE device_id=?");
    $stmt->execute([$id]);
    $device = $stmt->fetch();
    if (!$device) agentos_error('Device not found', 404);

    $device['capabilities'] = json_decode($device['capabilities'] ?? '[]', true);
    $device['telemetry'] = json_decode($device['telemetry'] ?? '{}', true);
    $device['safety_config'] = json_decode($device['safety_config'] ?? 'null', true);

    // Get recent commands
    $stmt = $pdo->prepare("SELECT action_type, capability_id, status, duration_ms, created_at
        FROM agentos_audit_log WHERE metadata LIKE ? ORDER BY created_at DESC LIMIT 20");
    $stmt->execute(['%"device_id":"' . $id . '"%']);
    $device['recent_commands'] = $stmt->fetchAll();

    agentos_respond(['ok' => true, 'device' => $device]);
}

function handleRegister(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || empty($input['device_type'])) agentos_error('device_type required');

    $pdo = agentos_pdo();
    $deviceId = $input['device_id'] ?? agentos_id('dev');
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', mb_substr($deviceId, 0, 100));

    $validTypes = ['robot', 'iot_sensor', 'iot_actuator', 'camera', 'microphone',
                   'speaker', 'display', 'controller', 'custom'];
    $deviceType = in_array($input['device_type'], $validTypes) ? $input['device_type'] : 'custom';

    $validProtocols = ['ros2', 'mqtt', 'http', 'websocket', 'serial', 'custom'];
    $protocol = in_array($input['protocol'] ?? '', $validProtocols) ? $input['protocol'] : 'http';

    // Generate device auth token
    $deviceToken = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $deviceToken);

    $stmt = $pdo->prepare("INSERT INTO agentos_devices 
        (device_id, device_type, display_name, status, protocol, connection_url,
         auth_token_hash, capabilities, safety_config)
        VALUES (?, ?, ?, 'offline', ?, ?, ?, ?, ?)");
    $stmt->execute([
        $deviceId, $deviceType,
        mb_substr($input['display_name'] ?? $deviceId, 0, 200),
        $protocol,
        $input['connection_url'] ?? null,
        $tokenHash,
        json_encode($input['capabilities'] ?? []),
        json_encode($input['safety_config'] ?? null),
    ]);

    // Also spawn as world entity
    $entityType = in_array($deviceType, ['robot','device','sensor','avatar','object','zone','service'])
        ? $deviceType : 'device';
    $stmt = $pdo->prepare("INSERT IGNORE INTO agentos_world_entities 
        (world_id, entity_id, entity_type, display_name, status,
         properties, last_heartbeat)
        VALUES ('default', ?, ?, ?, 'online', ?, NOW())");
    $stmt->execute([
        $deviceId, $entityType,
        $input['display_name'] ?? $deviceId,
        json_encode(['protocol' => $protocol, 'controlled_by' => 'device_bridge']),
    ]);

    agentos_audit([
        'agent_id' => 'device_bridge', 'user_id' => $auth['user_id'],
        'action_type' => 'device_registered', 'status' => 'completed',
        'input' => ['device_id' => $deviceId, 'device_type' => $deviceType],
        'metadata' => ['device_id' => $deviceId],
    ]);

    agentos_respond([
        'ok' => true,
        'device_id' => $deviceId,
        'device_token' => $deviceToken,
        'protocol' => $protocol,
    ], 201);
}

// ═══════════════════════════════════════════════════════════════
// COMMAND — Send command to device
// ═══════════════════════════════════════════════════════════════
function handleCommand(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['device_id'] ?? '');
    if (!$deviceId || empty($input['command'])) {
        agentos_error('device_id and command required');
    }

    $pdo = agentos_pdo();

    // Get device
    $stmt = $pdo->prepare("SELECT * FROM agentos_devices WHERE device_id=? AND status IN ('online', 'idle')");
    $stmt->execute([$deviceId]);
    $device = $stmt->fetch();
    if (!$device) agentos_error('Device not found or offline', 404);

    $command = mb_substr(trim($input['command']), 0, 255);

    // Policy check — all device commands go through safety kernel
    $riskLevel = 'high';
    if (in_array($device['device_type'], ['robot', 'controller'])) {
        $riskLevel = 'critical';
    }
    $isEmergency = in_array($command, ['emergency_stop', 'e_stop', 'halt', 'shutdown']);

    if (!$isEmergency && !$auth['is_internal']) {
        // Non-emergency commands to physical robots need approval
        if ($riskLevel === 'critical') {
            // Check for pending approval
            $stmt = $pdo->prepare("SELECT approval_id, status FROM agentos_approvals 
                WHERE capability_id=? AND status='approved' AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
                LIMIT 1");
            $stmt->execute(["robot.command.{$deviceId}"]);
            $approval = $stmt->fetch();

            if (!$approval && !$auth['is_internal']) {
                agentos_error('Critical device command requires approval. Use policy.php to approve.', 403);
            }
        }
    }

    $startTime = hrtime(true);

    // Send command based on protocol
    $result = null;
    switch ($device['protocol']) {
        case 'ros2':
            $result = sendRos2Command($device, $command, $input['params'] ?? []);
            break;
        case 'mqtt':
            $result = sendMqttCommand($device, $command, $input['params'] ?? []);
            break;
        case 'http':
        case 'websocket':
            $result = sendHttpCommand($device, $command, $input['params'] ?? []);
            break;
        default:
            $result = ['success' => false, 'error' => "Unsupported protocol: {$device['protocol']}"];
    }

    $durationMs = (int)((hrtime(true) - $startTime) / 1_000_000);

    agentos_audit([
        'agent_id' => 'device_bridge', 'user_id' => $auth['user_id'],
        'action_type' => 'device_command', 'capability_id' => $command,
        'status' => $result['success'] ? 'completed' : 'failed',
        'risk_level' => $riskLevel,
        'duration_ms' => $durationMs,
        'input' => ['device_id' => $deviceId, 'command' => $command],
        'output' => $result,
        'metadata' => ['device_id' => $deviceId],
    ]);

    agentos_respond([
        'ok' => true,
        'device_id' => $deviceId,
        'command' => $command,
        'result' => $result,
        'duration_ms' => $durationMs,
    ]);
}

// ═══════════════════════════════════════════════════════════════
// TELEMETRY — Submit or retrieve telemetry
// ═══════════════════════════════════════════════════════════════
function handleTelemetry(array $auth): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Submit telemetry
        $input = json_decode(file_get_contents('php://input'), true);
        $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['device_id'] ?? '');
        if (!$deviceId) agentos_error('device_id required');

        // Authenticate device
        $pdo = agentos_pdo();
        $deviceToken = $_SERVER['HTTP_X_DEVICE_TOKEN'] ?? '';
        if ($deviceToken) {
            $stmt = $pdo->prepare("SELECT device_id FROM agentos_devices WHERE device_id=? AND auth_token_hash=?");
            $stmt->execute([$deviceId, hash('sha256', $deviceToken)]);
            if (!$stmt->fetch()) agentos_error('Invalid device token', 403);
        }

        $telemetry = $input['telemetry'] ?? [];

        // Store latest telemetry
        $stmt = $pdo->prepare("UPDATE agentos_devices SET 
            telemetry=?, last_heartbeat=NOW(), status='online' WHERE device_id=?");
        $stmt->execute([json_encode($telemetry), $deviceId]);

        // Record telemetry history for time-series tracking
        if (!empty($telemetry)) {
            $historyStmt = $pdo->prepare("INSERT INTO agentos_telemetry_history 
                (device_id, metric_name, metric_value, unit, metadata) VALUES (?, ?, ?, ?, ?)");
            foreach ($telemetry as $key => $value) {
                if (is_numeric($value)) {
                    $historyStmt->execute([
                        $deviceId, $key, (float)$value,
                        $input['units'][$key] ?? null,
                        json_encode(['source' => 'telemetry_submit']),
                    ]);
                }
            }
        }

        // Auto-snapshot if anomaly thresholds exceeded
        $safetyConfig = null;
        $devStmt = $pdo->prepare("SELECT safety_config FROM agentos_devices WHERE device_id=?");
        $devStmt->execute([$deviceId]);
        $devRow = $devStmt->fetch();
        if ($devRow) {
            $safetyConfig = json_decode($devRow['safety_config'] ?? '{}', true);
            $thresholds = $safetyConfig['thresholds'] ?? [];
            $alertTriggered = false;
            foreach ($thresholds as $metric => $limits) {
                $val = $telemetry[$metric] ?? null;
                if ($val !== null && (($limits['max'] ?? PHP_INT_MAX) < $val || ($limits['min'] ?? -PHP_INT_MAX) > $val)) {
                    $alertTriggered = true;
                    agentos_push('agentos:devices', 'threshold_alert', [
                        'device_id' => $deviceId, 'metric' => $metric,
                        'value' => $val, 'limits' => $limits,
                    ]);
                }
            }
            if ($alertTriggered) {
                $pdo->prepare("INSERT INTO agentos_twin_snapshots 
                    (device_id, snapshot_type, twin_state, telemetry, trigger_event)
                    VALUES (?, 'alert', ?, ?, 'threshold_exceeded')")
                    ->execute([$deviceId, json_encode(['status' => 'online']), json_encode($telemetry)]);
            }
        }

        // Cache in Redis for real-time access
        agentos_cache_set("telemetry:{$deviceId}", $telemetry, 60);

        // Update world entity position if provided
        if (isset($telemetry['position'])) {
            $pdo->prepare("UPDATE agentos_world_entities SET 
                twin_data=?, last_heartbeat=NOW() WHERE entity_id=?")
                ->execute([json_encode($telemetry), $deviceId]);
        }

        agentos_respond(['ok' => true, 'device_id' => $deviceId, 'received' => count($telemetry)]);
    } else {
        // Retrieve telemetry
        $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['id'] ?? '');
        if (!$deviceId) agentos_error('id required');

        // Try cache first
        $cached = agentos_cache_get("telemetry:{$deviceId}");
        if ($cached) {
            agentos_respond(['ok' => true, 'device_id' => $deviceId, 'source' => 'realtime', 'telemetry' => $cached]);
            return;
        }

        // Fall back to DB
        $pdo = agentos_pdo();
        $stmt = $pdo->prepare("SELECT telemetry, last_heartbeat FROM agentos_devices WHERE device_id=?");
        $stmt->execute([$deviceId]);
        $row = $stmt->fetch();
        if (!$row) agentos_error('Device not found', 404);

        agentos_respond([
            'ok' => true,
            'device_id' => $deviceId,
            'source' => 'stored',
            'telemetry' => json_decode($row['telemetry'] ?? '{}', true),
            'last_heartbeat' => $row['last_heartbeat'],
        ]);
    }
}

// ═══════════════════════════════════════════════════════════════
// HEARTBEAT
// ═══════════════════════════════════════════════════════════════
function handleHeartbeat(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['device_id'] ?? '');
    if (!$deviceId) agentos_error('device_id required');

    $pdo = agentos_pdo();
    $stmt = $pdo->prepare("UPDATE agentos_devices SET last_heartbeat=NOW(), status='online' WHERE device_id=?");
    $stmt->execute([$deviceId]);

    // Also update world entity
    $pdo->prepare("UPDATE agentos_world_entities SET last_heartbeat=NOW(), status='online' WHERE entity_id=?")->execute([$deviceId]);

    agentos_respond(['ok' => true, 'device_id' => $deviceId]);
}

// ═══════════════════════════════════════════════════════════════
// EMERGENCY STOP — Immediate halt
// ═══════════════════════════════════════════════════════════════
function handleEmergencyStop(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['device_id'] ?? '');

    $pdo = agentos_pdo();

    if ($deviceId) {
        // Stop specific device
        $stmt = $pdo->prepare("SELECT * FROM agentos_devices WHERE device_id=?");
        $stmt->execute([$deviceId]);
        $device = $stmt->fetch();

        if ($device) {
            sendEmergencyStop($device);
            $pdo->prepare("UPDATE agentos_devices SET status='maintenance' WHERE device_id=?")->execute([$deviceId]);
            $pdo->prepare("UPDATE agentos_world_entities SET status='maintenance' WHERE entity_id=?")->execute([$deviceId]);
        }

        $stopped = $device ? 1 : 0;
    } else {
        // Stop ALL devices
        $stmt = $pdo->query("SELECT * FROM agentos_devices WHERE status='online'");
        $devices = $stmt->fetchAll();

        foreach ($devices as $device) {
            sendEmergencyStop($device);
        }

        $pdo->exec("UPDATE agentos_devices SET status='maintenance' WHERE status='online'");
        $pdo->exec("UPDATE agentos_world_entities SET status='maintenance' WHERE status='online'");
        $stopped = count($devices);
    }

    agentos_audit([
        'agent_id' => 'device_bridge', 'user_id' => $auth['user_id'],
        'action_type' => 'emergency_stop', 'status' => 'completed',
        'risk_level' => 'critical',
        'output' => ['devices_stopped' => $stopped],
        'metadata' => ['device_id' => $deviceId ?: 'ALL'],
    ]);

    agentos_push('agentos:devices', 'emergency_stop', [
        'device_id' => $deviceId ?: 'ALL',
        'stopped' => $stopped,
    ]);

    agentos_respond(['ok' => true, 'devices_stopped' => $stopped]);
}

function handleRos2Topics(array $auth): void {
    // List available ROS2 topics from connected devices
    $pdo = agentos_pdo();
    $stmt = $pdo->query("SELECT device_id, display_name, safety_config FROM agentos_devices 
        WHERE protocol='ros2' AND status='online'");
    $devices = $stmt->fetchAll();

    $topics = [];
    foreach ($devices as $d) {
        $config = json_decode($d['safety_config'] ?? '{}', true);
        foreach ($config['topics'] ?? [] as $topic) {
            $topics[] = [
                'device_id' => $d['device_id'],
                'device_name' => $d['display_name'],
                'topic' => $topic['name'] ?? '',
                'msg_type' => $topic['type'] ?? '',
                'direction' => $topic['direction'] ?? 'subscribe',
            ];
        }
    }

    agentos_respond(['ok' => true, 'topics' => $topics]);
}

// ── Protocol Handlers ──────────────────────────────────────────

function sendRos2Command(array $device, string $command, array $params): array {
    $config = json_decode($device['safety_config'] ?? '{}', true);
    $bridgeUrl = $config['bridge_url'] ?? ($device['connection_url'] ?? 'http://localhost:9090');

    // ROS2 bridge typically uses rosbridge_suite on port 9090
    $ch = curl_init("{$bridgeUrl}/service/call");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'service' => $command,
            'args' => $params,
        ]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $result = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http >= 200 && $http < 300) {
        return ['success' => true, 'response' => json_decode($result, true)];
    }
    return ['success' => false, 'error' => "ROS2 bridge returned HTTP {$http}", 'protocol' => 'ros2'];
}

function sendMqttCommand(array $device, string $command, array $params): array {
    // MQTT via HTTP bridge (Mosquitto REST or similar)
    $endpoint = $device['connection_url'] ?? '';
    if (!$endpoint) return ['success' => false, 'error' => 'No MQTT endpoint configured'];

    $topic = "devices/{$device['device_id']}/commands";
    $payload = json_encode(['command' => $command, 'params' => $params, 'ts' => time()]);

    // Use Redis PUBLISH as MQTT-like mechanism if no external broker
    $redis = agentos_redis();
    if ($redis) {
        $redis->publish($topic, $payload);
        return ['success' => true, 'channel' => $topic, 'protocol' => 'mqtt-redis'];
    }

    return ['success' => false, 'error' => 'MQTT broker not available', 'protocol' => 'mqtt'];
}

function sendHttpCommand(array $device, string $command, array $params): array {
    $endpoint = $device['connection_url'] ?? '';
    if (!$endpoint) return ['success' => false, 'error' => 'No endpoint configured'];

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'command' => $command,
            'params' => $params,
            'device_id' => $device['device_id'],
        ]),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-Device-Token: ' . ($device['auth_token_hash'] ?? ''),
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $result = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'success' => $http >= 200 && $http < 300,
        'response' => json_decode($result, true),
        'http_code' => $http,
        'protocol' => 'http',
    ];
}

function sendEmergencyStop(array $device): void {
    switch ($device['protocol']) {
        case 'ros2':
            sendRos2Command($device, '/emergency_stop', ['stop' => true]);
            break;
        case 'mqtt':
            sendMqttCommand($device, 'emergency_stop', ['immediate' => true]);
            break;
        default:
            sendHttpCommand($device, 'emergency_stop', ['immediate' => true]);
    }
}

// ═══════════════════════════════════════════════════════════════
// FLEET STATUS — System-wide device overview
// ═══════════════════════════════════════════════════════════════
function handleFleetStatus(array $auth): void {
    $pdo = agentos_pdo();

    // Get device counts by status
    $stmt = $pdo->query("SELECT status, COUNT(*) as cnt FROM agentos_devices GROUP BY status");
    $statusCounts = [];
    while ($row = $stmt->fetch()) {
        $statusCounts[$row['status']] = (int)$row['cnt'];
    }

    // Get device counts by type
    $stmt = $pdo->query("SELECT device_type, COUNT(*) as cnt FROM agentos_devices GROUP BY device_type");
    $typeCounts = [];
    while ($row = $stmt->fetch()) {
        $typeCounts[$row['device_type']] = (int)$row['cnt'];
    }

    // Stale devices (no heartbeat in 5 minutes)
    $stmt = $pdo->query("SELECT device_id, display_name, device_type, last_heartbeat 
        FROM agentos_devices WHERE status='online' 
        AND (last_heartbeat IS NULL OR last_heartbeat < DATE_SUB(NOW(), INTERVAL 5 MINUTE))");
    $stale = $stmt->fetchAll();

    // Recent alerts (last 24h telemetry threshold events)
    $stmt = $pdo->query("SELECT device_id, trigger_event, created_at 
        FROM agentos_twin_snapshots WHERE snapshot_type='alert' 
        AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) 
        ORDER BY created_at DESC LIMIT 50");
    $alerts = $stmt->fetchAll();

    // Groups
    $stmt = $pdo->query("SELECT group_id, display_name, device_ids FROM agentos_device_groups ORDER BY display_name");
    $groups = $stmt->fetchAll();
    foreach ($groups as &$g) {
        $g['device_ids'] = json_decode($g['device_ids'] ?? '[]', true);
        $g['device_count'] = count($g['device_ids']);
    }

    $total = array_sum($statusCounts);

    agentos_respond([
        'ok' => true,
        'fleet' => [
            'total_devices' => $total,
            'by_status' => $statusCounts,
            'by_type' => $typeCounts,
            'stale_devices' => $stale,
            'recent_alerts' => $alerts,
            'groups' => $groups,
            'health' => $total > 0 ? round(($statusCounts['online'] ?? 0) / $total * 100, 1) : 0,
        ],
    ]);
}

// ═══════════════════════════════════════════════════════════════
// TWIN SYNC — Update digital twin state for a device
// ═══════════════════════════════════════════════════════════════
function handleTwinSync(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['device_id'] ?? '');
    if (!$deviceId) agentos_error('device_id required');

    $pdo = agentos_pdo();

    // Get device
    $stmt = $pdo->prepare("SELECT device_id, device_type FROM agentos_devices WHERE device_id=?");
    $stmt->execute([$deviceId]);
    if (!$stmt->fetch()) agentos_error('Device not found', 404);

    $twinState = $input['twin_state'] ?? [];
    $syncMode = $input['mode'] ?? 'merge'; // merge or replace

    if ($syncMode === 'merge') {
        // Merge with existing twin data
        $stmt = $pdo->prepare("SELECT twin_data FROM agentos_world_entities WHERE entity_id=?");
        $stmt->execute([$deviceId]);
        $existing = $stmt->fetch();
        $currentTwin = json_decode($existing['twin_data'] ?? '{}', true);
        $twinState = array_merge($currentTwin, $twinState);
    }

    // Update world entity twin data
    $pdo->prepare("UPDATE agentos_world_entities SET twin_data=?, last_heartbeat=NOW() WHERE entity_id=?")
        ->execute([json_encode($twinState), $deviceId]);

    // Auto-snapshot on significant state changes
    if ($input['snapshot'] ?? false) {
        $telRow = $pdo->prepare("SELECT telemetry FROM agentos_devices WHERE device_id=?");
        $telRow->execute([$deviceId]);
        $tel = $telRow->fetch();
        $pdo->prepare("INSERT INTO agentos_twin_snapshots 
            (device_id, snapshot_type, twin_state, telemetry, trigger_event)
            VALUES (?, 'auto', ?, ?, 'twin_sync')")
            ->execute([$deviceId, json_encode($twinState), $tel['telemetry'] ?? '{}']);
    }

    agentos_push('agentos:devices', 'twin_synced', [
        'device_id' => $deviceId, 'mode' => $syncMode,
    ]);

    agentos_respond(['ok' => true, 'device_id' => $deviceId, 'twin_state' => $twinState]);
}

// ═══════════════════════════════════════════════════════════════
// TWIN SNAPSHOT — Create or retrieve twin snapshots
// ═══════════════════════════════════════════════════════════════
function handleTwinSnapshot(array $auth): void {
    $pdo = agentos_pdo();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Create manual snapshot
        $input = json_decode(file_get_contents('php://input'), true);
        $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['device_id'] ?? '');
        if (!$deviceId) agentos_error('device_id required');

        // Get current device + twin state
        $stmt = $pdo->prepare("SELECT d.*, we.twin_data, we.properties 
            FROM agentos_devices d 
            LEFT JOIN agentos_world_entities we ON we.entity_id = d.device_id 
            WHERE d.device_id=?");
        $stmt->execute([$deviceId]);
        $device = $stmt->fetch();
        if (!$device) agentos_error('Device not found', 404);

        $twinState = [
            'device_type' => $device['device_type'],
            'status' => $device['status'],
            'protocol' => $device['protocol'],
            'capabilities' => json_decode($device['capabilities'] ?? '[]', true),
            'twin_data' => json_decode($device['twin_data'] ?? '{}', true),
            'properties' => json_decode($device['properties'] ?? '{}', true),
        ];

        $stmt = $pdo->prepare("INSERT INTO agentos_twin_snapshots 
            (device_id, snapshot_type, twin_state, telemetry, trigger_event)
            VALUES (?, 'manual', ?, ?, ?)");
        $stmt->execute([
            $deviceId,
            json_encode($twinState),
            $device['telemetry'] ?? '{}',
            $input['trigger'] ?? 'manual_snapshot',
        ]);

        agentos_respond(['ok' => true, 'device_id' => $deviceId, 'snapshot_id' => $pdo->lastInsertId()]);

    } else {
        // Get snapshots for a device
        $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['id'] ?? '');
        if (!$deviceId) agentos_error('id required');

        $limit = min(max((int)($_GET['limit'] ?? 20), 1), 100);

        $stmt = $pdo->prepare("SELECT id, device_id, snapshot_type, twin_state, telemetry, 
            trigger_event, created_at FROM agentos_twin_snapshots 
            WHERE device_id=? ORDER BY created_at DESC LIMIT $limit");
        $stmt->execute([$deviceId]);
        $snapshots = $stmt->fetchAll();

        foreach ($snapshots as &$s) {
            $s['twin_state'] = json_decode($s['twin_state'] ?? '{}', true);
            $s['telemetry'] = json_decode($s['telemetry'] ?? '{}', true);
        }

        agentos_respond(['ok' => true, 'device_id' => $deviceId, 'snapshots' => $snapshots]);
    }
}

// ═══════════════════════════════════════════════════════════════
// TELEMETRY HISTORY — Time-series telemetry for a device
// ═══════════════════════════════════════════════════════════════
function handleTelemetryHistory(array $auth): void {
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['id'] ?? '');
    if (!$deviceId) agentos_error('id required');

    $metric = preg_replace('/[^a-zA-Z0-9_.-]/', '', $_GET['metric'] ?? '');
    $hours = min(max((int)($_GET['hours'] ?? 24), 1), 720); // Max 30 days
    $limit = min(max((int)($_GET['limit'] ?? 500), 1), 5000);

    $pdo = agentos_pdo();

    $where = "device_id=? AND recorded_at > DATE_SUB(NOW(), INTERVAL $hours HOUR)";
    $params = [$deviceId];

    if ($metric) {
        $where .= " AND metric_name=?";
        $params[] = $metric;
    }

    $stmt = $pdo->prepare("SELECT metric_name, metric_value, unit, recorded_at 
        FROM agentos_telemetry_history WHERE $where 
        ORDER BY recorded_at DESC LIMIT $limit");
    $stmt->execute($params);
    $history = $stmt->fetchAll();

    // Get available metrics for this device
    $metricStmt = $pdo->prepare("SELECT DISTINCT metric_name FROM agentos_telemetry_history 
        WHERE device_id=? ORDER BY metric_name");
    $metricStmt->execute([$deviceId]);
    $availableMetrics = array_column($metricStmt->fetchAll(), 'metric_name');

    // Compute basic stats per metric
    $statsStmt = $pdo->prepare("SELECT metric_name, 
        MIN(metric_value) as min_val, MAX(metric_value) as max_val, 
        AVG(metric_value) as avg_val, COUNT(*) as count
        FROM agentos_telemetry_history 
        WHERE device_id=? AND recorded_at > DATE_SUB(NOW(), INTERVAL $hours HOUR)
        GROUP BY metric_name");
    $statsStmt->execute([$deviceId]);
    $stats = [];
    while ($row = $statsStmt->fetch()) {
        $stats[$row['metric_name']] = [
            'min' => round($row['min_val'], 4),
            'max' => round($row['max_val'], 4),
            'avg' => round($row['avg_val'], 4),
            'count' => (int)$row['count'],
        ];
    }

    agentos_respond([
        'ok' => true,
        'device_id' => $deviceId,
        'hours' => $hours,
        'available_metrics' => $availableMetrics,
        'stats' => $stats,
        'history' => $history,
    ]);
}

// ═══════════════════════════════════════════════════════════════
// DEVICE GROUPS — Create, list, and manage device groups
// ═══════════════════════════════════════════════════════════════
function handleGroupCreate(array $auth): void {
    if (!$auth['is_internal'] && !$auth['user_id']) {
        agentos_error('Authentication required', 403);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (empty($input['display_name'])) agentos_error('display_name required');

    $groupId = $input['group_id'] ?? agentos_id('grp');
    $groupId = preg_replace('/[^a-zA-Z0-9_-]/', '', mb_substr($groupId, 0, 128));
    $deviceIds = array_filter(array_map(function($id) {
        return preg_replace('/[^a-zA-Z0-9_-]/', '', $id);
    }, $input['device_ids'] ?? []));

    $pdo = agentos_pdo();
    $stmt = $pdo->prepare("INSERT INTO agentos_device_groups 
        (group_id, display_name, description, device_ids, metadata) 
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE display_name=VALUES(display_name), 
        description=VALUES(description), device_ids=VALUES(device_ids), metadata=VALUES(metadata)");
    $stmt->execute([
        $groupId,
        mb_substr($input['display_name'], 0, 255),
        mb_substr($input['description'] ?? '', 0, 1000),
        json_encode($deviceIds),
        json_encode($input['metadata'] ?? null),
    ]);

    agentos_respond(['ok' => true, 'group_id' => $groupId, 'device_count' => count($deviceIds)], 201);
}

function handleGroups(array $auth): void {
    $pdo = agentos_pdo();
    $stmt = $pdo->query("SELECT group_id, display_name, description, device_ids, metadata, created_at 
        FROM agentos_device_groups ORDER BY display_name");
    $groups = $stmt->fetchAll();

    foreach ($groups as &$g) {
        $ids = json_decode($g['device_ids'] ?? '[]', true);
        $g['device_ids'] = $ids;
        $g['device_count'] = count($ids);
        $g['metadata'] = json_decode($g['metadata'] ?? 'null', true);

        // Get online count for this group
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $onStmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM agentos_devices 
                WHERE device_id IN ($placeholders) AND status='online'");
            $onStmt->execute($ids);
            $g['online_count'] = (int)$onStmt->fetchColumn();
        } else {
            $g['online_count'] = 0;
        }
    }

    agentos_respond(['ok' => true, 'groups' => $groups]);
}

// ═══════════════════════════════════════════════════════════════
// GROUP COMMAND — Send command to all devices in a group
// ═══════════════════════════════════════════════════════════════
function handleGroupCommand(array $auth): void {
    if (!$auth['is_internal'] && !$auth['user_id']) {
        agentos_error('Authentication required', 403);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $groupId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['group_id'] ?? '');
    if (!$groupId || empty($input['command'])) agentos_error('group_id and command required');

    $pdo = agentos_pdo();
    $stmt = $pdo->prepare("SELECT device_ids FROM agentos_device_groups WHERE group_id=?");
    $stmt->execute([$groupId]);
    $group = $stmt->fetch();
    if (!$group) agentos_error('Group not found', 404);

    $deviceIds = json_decode($group['device_ids'] ?? '[]', true);
    if (empty($deviceIds)) agentos_error('Group has no devices');

    $command = mb_substr(trim($input['command']), 0, 255);
    $params = $input['params'] ?? [];
    $results = [];

    foreach ($deviceIds as $deviceId) {
        $devStmt = $pdo->prepare("SELECT * FROM agentos_devices WHERE device_id=? AND status IN ('online','idle')");
        $devStmt->execute([$deviceId]);
        $device = $devStmt->fetch();

        if (!$device) {
            $results[] = ['device_id' => $deviceId, 'success' => false, 'error' => 'offline'];
            continue;
        }

        $result = null;
        switch ($device['protocol']) {
            case 'ros2': $result = sendRos2Command($device, $command, $params); break;
            case 'mqtt': $result = sendMqttCommand($device, $command, $params); break;
            default:     $result = sendHttpCommand($device, $command, $params); break;
        }
        $results[] = ['device_id' => $deviceId, 'success' => $result['success'] ?? false, 'result' => $result];
    }

    $succeeded = count(array_filter($results, fn($r) => $r['success']));

    agentos_audit([
        'agent_id' => 'device_bridge', 'user_id' => $auth['user_id'],
        'action_type' => 'group_command', 'status' => $succeeded > 0 ? 'completed' : 'failed',
        'risk_level' => 'high',
        'input' => ['group_id' => $groupId, 'command' => $command],
        'output' => ['total' => count($results), 'succeeded' => $succeeded],
        'metadata' => ['group_id' => $groupId],
    ]);

    agentos_respond([
        'ok' => true,
        'group_id' => $groupId,
        'command' => $command,
        'total_devices' => count($results),
        'succeeded' => $succeeded,
        'results' => $results,
    ]);
}

// ═══════════════════════════════════════════════════════════════
// SENSOR PIPELINE — Real-time sensor aggregation across fleet
// ═══════════════════════════════════════════════════════════════
function handleSensorPipeline(array $auth): void {
    $pdo = agentos_pdo();
    $minutes = min(max((int)($_GET['minutes'] ?? 5), 1), 60);
    $groupId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['group'] ?? '');

    // Filter by group if specified
    $deviceFilter = '';
    $deviceParams = [];
    if ($groupId) {
        $stmt = $pdo->prepare("SELECT device_ids FROM agentos_device_groups WHERE group_id=?");
        $stmt->execute([$groupId]);
        $group = $stmt->fetch();
        if ($group) {
            $ids = json_decode($group['device_ids'] ?? '[]', true);
            if (!empty($ids)) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $deviceFilter = "AND device_id IN ($placeholders)";
                $deviceParams = $ids;
            }
        }
    }

    // Aggregate recent telemetry across devices
    $sql = "SELECT device_id, metric_name, 
        MIN(metric_value) as min_val, MAX(metric_value) as max_val,
        AVG(metric_value) as avg_val, COUNT(*) as readings,
        MAX(recorded_at) as latest_at
        FROM agentos_telemetry_history 
        WHERE recorded_at > DATE_SUB(NOW(), INTERVAL $minutes MINUTE) $deviceFilter
        GROUP BY device_id, metric_name
        ORDER BY device_id, metric_name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($deviceParams);
    $aggregates = $stmt->fetchAll();

    // Format as device→metric→stats
    $pipeline = [];
    foreach ($aggregates as $a) {
        $pipeline[$a['device_id']][$a['metric_name']] = [
            'min' => round((float)$a['min_val'], 4),
            'max' => round((float)$a['max_val'], 4),
            'avg' => round((float)$a['avg_val'], 4),
            'readings' => (int)$a['readings'],
            'latest_at' => $a['latest_at'],
        ];
    }

    // Get latest raw readings per device
    $latestSql = "SELECT d.device_id, d.display_name, d.device_type, d.status, d.telemetry, d.last_heartbeat
        FROM agentos_devices d WHERE d.status='online' $deviceFilter ORDER BY d.display_name";
    $stmt = $pdo->prepare($latestSql);
    $stmt->execute($deviceParams);
    $liveDevices = $stmt->fetchAll();
    foreach ($liveDevices as &$d) {
        $d['telemetry'] = json_decode($d['telemetry'] ?? '{}', true);
    }

    agentos_respond([
        'ok' => true,
        'window_minutes' => $minutes,
        'group_id' => $groupId ?: null,
        'device_count' => count($liveDevices),
        'aggregated' => $pipeline,
        'live_devices' => $liveDevices,
    ]);
}
