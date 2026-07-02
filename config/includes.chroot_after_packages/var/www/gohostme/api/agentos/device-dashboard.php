<?php
/**
 * GSM Alfred OS — Device Dashboard API v1.0
 * Real-time monitoring, telemetry visualization, fleet overview
 *
 * Endpoints:
 *   GET    ?action=overview           — Fleet-wide dashboard overview
 *   GET    ?action=device_detail      — Detailed device status
 *   GET    ?action=telemetry          — Device telemetry time-series
 *   GET    ?action=battery_health     — Battery status and health trends
 *   GET    ?action=motor_status       — Motor/actuator status for device
 *   GET    ?action=sensor_readings    — Latest sensor readings
 *   GET    ?action=connectivity       — Network connectivity status
 *   GET    ?action=gps_track          — GPS track history
 *   GET    ?action=alerts             — Active and recent alerts
 *   GET    ?action=maintenance        — Maintenance schedule and history
 *   POST   ?action=schedule_maintenance — Schedule maintenance task
 *   GET    ?action=live_feed          — Live camera/sensor feed URLs
 *   GET    ?action=energy_report      — Energy consumption report
 *   GET    ?action=fleet_map          — All device positions for map
 *   GET    ?action=uptime             — Device uptime statistics
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Internal-Secret, X-Device-Token');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

define('GOSITEME_API', true);
require_once __DIR__ . '/bootstrap.php';
dashEnsureSchema();

$auth = agentos_auth();
$action = $_GET['action'] ?? 'overview';

switch ($action) {
    case 'overview':              handleOverview($auth); break;
    case 'device_detail':         handleDeviceDetail($auth); break;
    case 'telemetry':             handleTelemetry($auth); break;
    case 'battery_health':        handleBatteryHealth($auth); break;
    case 'motor_status':          handleMotorStatus($auth); break;
    case 'sensor_readings':       handleSensorReadings($auth); break;
    case 'connectivity':          handleConnectivity($auth); break;
    case 'gps_track':             handleGpsTrack($auth); break;
    case 'alerts':                handleAlerts($auth); break;
    case 'maintenance':           handleMaintenance($auth); break;
    case 'schedule_maintenance':  handleScheduleMaintenance($auth); break;
    case 'live_feed':             handleLiveFeed($auth); break;
    case 'energy_report':         handleEnergyReport($auth); break;
    case 'fleet_map':             handleFleetMap($auth); break;
    case 'uptime':                handleUptime($auth); break;
    default:                      agentos_error('Unknown action');
}

// ── Schema ─────────────────────────────────────────────────────

function dashEnsureSchema(): void {
    static $done = false;
    if ($done) return;
    $done = true;

    $pdo = agentos_pdo();
    $r = $pdo->query("SHOW TABLES LIKE 'agentos_maintenance_tasks'");
    if ($r->rowCount() > 0) return;

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS agentos_maintenance_tasks (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            task_id         VARCHAR(64) NOT NULL UNIQUE,
            device_id       VARCHAR(128) NOT NULL,
            task_type       ENUM('inspection','cleaning','calibration','firmware_update',
                                'battery_replacement','motor_service','sensor_calibration',
                                'lubrication','safety_check','full_service','custom') NOT NULL,
            priority        ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
            description     TEXT,
            scheduled_date  DATE NOT NULL,
            due_date        DATE,
            completed_at    TIMESTAMP NULL,
            status          ENUM('scheduled','in_progress','completed','overdue','cancelled') NOT NULL DEFAULT 'scheduled',
            assigned_to     INT UNSIGNED,
            notes           TEXT,
            parts_required  JSON,
            estimated_hours DECIMAL(4,1),
            actual_hours    DECIMAL(4,1),
            created_by      INT UNSIGNED,
            created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_device (device_id),
            INDEX idx_status (status),
            INDEX idx_scheduled (scheduled_date),
            INDEX idx_priority (priority)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS agentos_device_positions (
            id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            device_id       VARCHAR(128) NOT NULL,
            latitude        DECIMAL(10,8) NOT NULL,
            longitude       DECIMAL(11,8) NOT NULL,
            altitude_m      DECIMAL(8,2),
            speed_ms        DECIMAL(6,3),
            heading_deg     DECIMAL(5,2),
            accuracy_m      DECIMAL(6,2),
            source          ENUM('gps','wifi','bluetooth','uwb','visual','fused') NOT NULL DEFAULT 'gps',
            created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_device (device_id),
            INDEX idx_created (created_at),
            INDEX idx_device_time (device_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    error_log("[AGENTOS-DASH] Schema auto-migrated");
}

// ── Handlers ───────────────────────────────────────────────────

function handleOverview(array $auth): void {
    $pdo = agentos_pdo();

    // Device counts by status
    $statusCounts = $pdo->query("SELECT status, COUNT(*) as count FROM agentos_devices GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

    // Active interlocks
    $interlockCount = $pdo->query("SELECT COUNT(*) FROM agentos_safety_interlocks WHERE is_active = 1")->fetchColumn();

    // Devices online (heartbeat in last 5 min)
    $onlineCount = 0;
    try {
        $onlineCount = $pdo->query("SELECT COUNT(*) FROM agentos_watchdog_status WHERE last_ping > DATE_SUB(NOW(), INTERVAL 5 MINUTE)")->fetchColumn();
    } catch (\Throwable $e) {}

    // Recent alerts
    $alerts = [];
    try {
        $alerts = $pdo->query("SELECT event_id, device_id, event_type, severity, created_at 
            FROM agentos_safety_events WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) 
            ORDER BY created_at DESC LIMIT 10")->fetchAll();
    } catch (\Throwable $e) {}

    // Active firmware rollouts
    $rollouts = [];
    try {
        $rollouts = $pdo->query("SELECT rollout_id, version_id, status, current_percent, total_devices, updated_devices
            FROM agentos_firmware_rollouts WHERE status = 'active' LIMIT 5")->fetchAll();
    } catch (\Throwable $e) {}

    // Pending maintenance
    $pendingMaint = $pdo->query("SELECT COUNT(*) FROM agentos_maintenance_tasks WHERE status IN ('scheduled','overdue')")->fetchColumn();

    // Geofence breaches (24h)
    $breaches24h = 0;
    try {
        $breaches24h = $pdo->query("SELECT COUNT(*) FROM agentos_geofence_breaches WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();
    } catch (\Throwable $e) {}

    agentos_respond([
        'ok' => true,
        'fleet' => [
            'total_devices' => array_sum($statusCounts),
            'by_status' => $statusCounts,
            'online_now' => (int)$onlineCount,
            'active_interlocks' => (int)$interlockCount,
            'pending_maintenance' => (int)$pendingMaint,
            'geofence_breaches_24h' => (int)$breaches24h,
            'active_rollouts' => $rollouts
        ],
        'recent_alerts' => $alerts,
        'timestamp' => date('c')
    ]);
}

function handleDeviceDetail(array $auth): void {
    $pdo = agentos_pdo();
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['device_id'] ?? '');
    if (!$deviceId) agentos_error('device_id required');

    // Core device info
    $stmt = $pdo->prepare("SELECT * FROM agentos_devices WHERE device_id = ?");
    $stmt->execute([$deviceId]);
    $device = $stmt->fetch();
    if (!$device) agentos_error('Device not found', 404);

    // Provisioning info
    $provStmt = $pdo->prepare("SELECT serial_number, model, hardware_rev, firmware_version, 
        provisioning_status, activated_at, owner_id FROM agentos_provisioning WHERE device_id = ?");
    $provStmt->execute([$deviceId]);
    $prov = $provStmt->fetch();

    // Latest telemetry
    $telStmt = $pdo->prepare("SELECT metric_name, metric_value, recorded_at 
        FROM agentos_telemetry_history WHERE device_id = ? 
        ORDER BY recorded_at DESC LIMIT 20");
    $telStmt->execute([$deviceId]);

    // Latest position
    $posStmt = $pdo->prepare("SELECT latitude, longitude, altitude_m, speed_ms, heading_deg, accuracy_m, source, created_at
        FROM agentos_device_positions WHERE device_id = ? ORDER BY created_at DESC LIMIT 1");
    $posStmt->execute([$deviceId]);

    // Watchdog
    $wdStmt = $pdo->prepare("SELECT * FROM agentos_watchdog_status WHERE device_id = ?");
    $wdStmt->execute([$deviceId]);

    // Active interlocks
    $ilStmt = $pdo->prepare("SELECT interlock_id, interlock_type, severity, created_at 
        FROM agentos_safety_interlocks WHERE device_id = ? AND is_active = 1");
    $ilStmt->execute([$deviceId]);

    // Current firmware
    $fwStmt = $pdo->prepare("SELECT v.version, v.channel, d.completed_at
        FROM agentos_firmware_deployments d
        JOIN agentos_firmware_versions v ON d.version_id = v.version_id
        WHERE d.device_id = ? AND d.status = 'completed'
        ORDER BY d.completed_at DESC LIMIT 1");
    $fwStmt->execute([$deviceId]);

    $device['safety_config'] = $device['safety_config'] ? json_decode($device['safety_config'], true) : null;

    agentos_respond([
        'ok' => true,
        'device' => $device,
        'provisioning' => $prov ?: null,
        'latest_telemetry' => $telStmt->fetchAll(),
        'position' => $posStmt->fetch() ?: null,
        'watchdog' => $wdStmt->fetch() ?: null,
        'active_interlocks' => $ilStmt->fetchAll(),
        'firmware' => $fwStmt->fetch() ?: null
    ]);
}

function handleTelemetry(array $auth): void {
    $pdo = agentos_pdo();
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['device_id'] ?? '');
    $metric = preg_replace('/[^a-zA-Z0-9_.-]/', '', $_GET['metric'] ?? '');
    $hours = min(720, max(1, intval($_GET['hours'] ?? 24)));

    if (!$deviceId) agentos_error('device_id required');

    $sql = "SELECT metric_name, metric_value, unit, recorded_at 
            FROM agentos_telemetry_history WHERE device_id = ? AND recorded_at > DATE_SUB(NOW(), INTERVAL ? HOUR)";
    $params = [$deviceId, $hours];

    if ($metric) {
        $sql .= " AND metric_name = ?";
        $params[] = $metric;
    }
    $sql .= " ORDER BY recorded_at ASC LIMIT 5000";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    agentos_respond(['ok' => true, 'device_id' => $deviceId, 'period_hours' => $hours, 'data' => $stmt->fetchAll()]);
}

function handleBatteryHealth(array $auth): void {
    $pdo = agentos_pdo();
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['device_id'] ?? '');
    if (!$deviceId) agentos_error('device_id required');

    // Battery percentage over time
    $stmt = $pdo->prepare("SELECT metric_value as percentage, recorded_at 
        FROM agentos_telemetry_history 
        WHERE device_id = ? AND metric_name = 'battery_percent'
        ORDER BY recorded_at DESC LIMIT 200");
    $stmt->execute([$deviceId]);
    $percentHistory = $stmt->fetchAll();

    // Battery temperature
    $tempStmt = $pdo->prepare("SELECT metric_value as temperature_c, recorded_at 
        FROM agentos_telemetry_history 
        WHERE device_id = ? AND metric_name = 'battery_temp'
        ORDER BY recorded_at DESC LIMIT 100");
    $tempStmt->execute([$deviceId]);

    // Cycle count
    $cycleStmt = $pdo->prepare("SELECT metric_value FROM agentos_telemetry_history 
        WHERE device_id = ? AND metric_name = 'battery_cycles'
        ORDER BY recorded_at DESC LIMIT 1");
    $cycleStmt->execute([$deviceId]);
    $cycles = $cycleStmt->fetchColumn();

    // Current level
    $currentLevel = !empty($percentHistory) ? floatval($percentHistory[0]['percentage']) : null;

    agentos_respond([
        'ok' => true,
        'device_id' => $deviceId,
        'battery' => [
            'current_percent' => $currentLevel,
            'cycle_count' => $cycles ? intval($cycles) : null,
            'health_estimate' => $cycles ? max(0, 100 - (intval($cycles) / 10)) : null,
            'history' => $percentHistory,
            'temperature' => $tempStmt->fetchAll()
        ]
    ]);
}

function handleMotorStatus(array $auth): void {
    $pdo = agentos_pdo();
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['device_id'] ?? '');
    if (!$deviceId) agentos_error('device_id required');

    $motorMetrics = ['motor_temp', 'motor_current', 'motor_speed', 'motor_torque', 
                     'motor_position', 'motor_error', 'servo_angle'];

    $data = [];
    foreach ($motorMetrics as $metric) {
        $stmt = $pdo->prepare("SELECT metric_value, unit, recorded_at 
            FROM agentos_telemetry_history 
            WHERE device_id = ? AND metric_name LIKE ?
            ORDER BY recorded_at DESC LIMIT 10");
        $stmt->execute([$deviceId, "%{$metric}%"]);
        $readings = $stmt->fetchAll();
        if ($readings) $data[$metric] = $readings;
    }

    agentos_respond(['ok' => true, 'device_id' => $deviceId, 'motors' => $data]);
}

function handleSensorReadings(array $auth): void {
    $pdo = agentos_pdo();
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['device_id'] ?? '');
    if (!$deviceId) agentos_error('device_id required');

    // Get latest reading for each sensor type
    $stmt = $pdo->prepare("SELECT t1.metric_name, t1.metric_value, t1.unit, t1.recorded_at
        FROM agentos_telemetry_history t1
        INNER JOIN (
            SELECT metric_name, MAX(recorded_at) as max_time
            FROM agentos_telemetry_history WHERE device_id = ?
            GROUP BY metric_name
        ) t2 ON t1.metric_name = t2.metric_name AND t1.recorded_at = t2.max_time
        WHERE t1.device_id = ?
        ORDER BY t1.metric_name");
    $stmt->execute([$deviceId, $deviceId]);

    $sensors = [];
    foreach ($stmt->fetchAll() as $row) {
        $sensors[$row['metric_name']] = [
            'value' => floatval($row['metric_value']),
            'unit' => $row['unit'],
            'last_updated' => $row['recorded_at']
        ];
    }

    agentos_respond(['ok' => true, 'device_id' => $deviceId, 'sensors' => $sensors]);
}

function handleConnectivity(array $auth): void {
    $pdo = agentos_pdo();
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['device_id'] ?? '');
    if (!$deviceId) agentos_error('device_id required');

    // Watchdog status
    $wdStmt = $pdo->prepare("SELECT * FROM agentos_watchdog_status WHERE device_id = ?");
    $wdStmt->execute([$deviceId]);
    $wd = $wdStmt->fetch();

    // Connection-related telemetry
    $connMetrics = $pdo->prepare("SELECT metric_name, metric_value, recorded_at 
        FROM agentos_telemetry_history 
        WHERE device_id = ? AND metric_name IN ('wifi_rssi','wifi_ssid','cellular_signal',
            'latency_ms','packet_loss','bandwidth_mbps')
        ORDER BY recorded_at DESC LIMIT 30");
    $connMetrics->execute([$deviceId]);

    $isOnline = $wd && (time() - strtotime($wd['last_ping'])) < 300;

    agentos_respond([
        'ok' => true,
        'device_id' => $deviceId,
        'connectivity' => [
            'online' => $isOnline,
            'last_seen' => $wd['last_ping'] ?? null,
            'watchdog_status' => $wd['status'] ?? 'unknown',
            'metrics' => $connMetrics->fetchAll()
        ]
    ]);
}

function handleGpsTrack(array $auth): void {
    $pdo = agentos_pdo();
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['device_id'] ?? '');
    $hours = min(168, max(1, intval($_GET['hours'] ?? 24)));
    if (!$deviceId) agentos_error('device_id required');

    $stmt = $pdo->prepare("SELECT latitude, longitude, altitude_m, speed_ms, heading_deg, accuracy_m, source, created_at
        FROM agentos_device_positions WHERE device_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? HOUR)
        ORDER BY created_at ASC LIMIT 5000");
    $stmt->execute([$deviceId, $hours]);

    agentos_respond(['ok' => true, 'device_id' => $deviceId, 'period_hours' => $hours, 'track' => $stmt->fetchAll()]);
}

function handleAlerts(array $auth): void {
    $pdo = agentos_pdo();
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['device_id'] ?? '');
    $severity = $_GET['severity'] ?? null;

    $sql = "SELECT event_id, device_id, event_type, severity, details, created_at, resolved, resolved_at
            FROM agentos_safety_events WHERE 1=1";
    $params = [];

    if ($deviceId) {
        $sql .= " AND device_id = ?";
        $params[] = $deviceId;
    }
    if ($severity && in_array($severity, ['info','warning','caution','critical','emergency'])) {
        $sql .= " AND severity = ?";
        $params[] = $severity;
    }
    $sql .= " ORDER BY created_at DESC LIMIT 50";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $alerts = $stmt->fetchAll();

    foreach ($alerts as &$a) {
        $a['details'] = json_decode($a['details'], true);
    }

    agentos_respond(['ok' => true, 'alerts' => $alerts]);
}

function handleMaintenance(array $auth): void {
    $pdo = agentos_pdo();
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['device_id'] ?? '');
    $status = $_GET['status'] ?? null;

    $sql = "SELECT * FROM agentos_maintenance_tasks WHERE 1=1";
    $params = [];

    if ($deviceId) {
        $sql .= " AND device_id = ?";
        $params[] = $deviceId;
    }
    if ($status && in_array($status, ['scheduled','in_progress','completed','overdue','cancelled'])) {
        $sql .= " AND status = ?";
        $params[] = $status;
    }
    $sql .= " ORDER BY FIELD(status, 'overdue','in_progress','scheduled','completed','cancelled'), scheduled_date ASC LIMIT 50";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tasks = $stmt->fetchAll();

    foreach ($tasks as &$t) {
        $t['parts_required'] = json_decode($t['parts_required'], true);
    }

    agentos_respond(['ok' => true, 'tasks' => $tasks]);
}

function handleScheduleMaintenance(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) agentos_error('Invalid JSON body');

    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['device_id'] ?? '');
    if (!$deviceId) agentos_error('device_id required');

    $taskType = $input['task_type'] ?? 'inspection';
    if (!in_array($taskType, ['inspection','cleaning','calibration','firmware_update',
        'battery_replacement','motor_service','sensor_calibration','lubrication',
        'safety_check','full_service','custom'])) {
        agentos_error('Invalid task_type');
    }

    $scheduledDate = $input['scheduled_date'] ?? date('Y-m-d', strtotime('+7 days'));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $scheduledDate)) {
        agentos_error('scheduled_date must be YYYY-MM-DD');
    }

    $taskId = agentos_id('maint');
    $pdo = agentos_pdo();

    $pdo->prepare("INSERT INTO agentos_maintenance_tasks 
        (task_id, device_id, task_type, priority, description, scheduled_date, due_date,
         assigned_to, parts_required, estimated_hours, created_by)
        VALUES (?,?,?,?,?,?,?,?,?,?,?)")
        ->execute([
            $taskId, $deviceId, $taskType,
            $input['priority'] ?? 'medium',
            $input['description'] ?? null,
            $scheduledDate,
            $input['due_date'] ?? null,
            $input['assigned_to'] ?? null,
            json_encode($input['parts_required'] ?? []),
            $input['estimated_hours'] ?? null,
            $auth['user_id']
        ]);

    agentos_respond(['ok' => true, 'task_id' => $taskId]);
}

function handleLiveFeed(array $auth): void {
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['device_id'] ?? '');
    if (!$deviceId) agentos_error('device_id required');

    // Generate time-limited feed URLs (valid 5 min)
    $expires = time() + 300;
    $secret = defined('INTERNAL_SECRET') ? INTERNAL_SECRET : (getenv('INTERNAL_SECRET') ?: '');
    $token = hash_hmac('sha256', "{$deviceId}:{$expires}", $secret);

    agentos_respond([
        'ok' => true,
        'device_id' => $deviceId,
        'feeds' => [
            'camera_front' => [
                'url' => "/api/agentos/stream.php?device={$deviceId}&cam=front&exp={$expires}&tok={$token}",
                'type' => 'mjpeg',
                'expires' => $expires
            ],
            'camera_rear' => [
                'url' => "/api/agentos/stream.php?device={$deviceId}&cam=rear&exp={$expires}&tok={$token}",
                'type' => 'mjpeg',
                'expires' => $expires
            ],
            'depth_sensor' => [
                'url' => "/api/agentos/stream.php?device={$deviceId}&cam=depth&exp={$expires}&tok={$token}",
                'type' => 'raw',
                'expires' => $expires
            ],
            'lidar' => [
                'url' => "/api/agentos/stream.php?device={$deviceId}&sensor=lidar&exp={$expires}&tok={$token}",
                'type' => 'pointcloud',
                'expires' => $expires
            ]
        ]
    ]);
}

function handleEnergyReport(array $auth): void {
    $pdo = agentos_pdo();
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['device_id'] ?? '');
    $days = min(90, max(1, intval($_GET['days'] ?? 7)));
    if (!$deviceId) agentos_error('device_id required');

    // Power consumption over time
    $stmt = $pdo->prepare("SELECT metric_value, recorded_at 
        FROM agentos_telemetry_history 
        WHERE device_id = ? AND metric_name = 'power_consumption_watts'
        AND recorded_at > DATE_SUB(NOW(), INTERVAL ? DAY)
        ORDER BY recorded_at ASC");
    $stmt->execute([$deviceId, $days]);

    // Charge events
    $chargeStmt = $pdo->prepare("SELECT metric_value, recorded_at 
        FROM agentos_telemetry_history 
        WHERE device_id = ? AND metric_name = 'charging_status'
        AND recorded_at > DATE_SUB(NOW(), INTERVAL ? DAY)
        ORDER BY recorded_at ASC");
    $chargeStmt->execute([$deviceId, $days]);

    agentos_respond([
        'ok' => true,
        'device_id' => $deviceId,
        'period_days' => $days,
        'energy' => [
            'consumption' => $stmt->fetchAll(),
            'charge_events' => $chargeStmt->fetchAll()
        ]
    ]);
}

function handleFleetMap(array $auth): void {
    $pdo = agentos_pdo();

    // Get latest position for each active device
    $stmt = $pdo->query("
        SELECT p.device_id, p.latitude, p.longitude, p.altitude_m, p.speed_ms,
               p.heading_deg, p.source, p.created_at,
               d.name, d.status as device_status
        FROM agentos_device_positions p
        INNER JOIN (
            SELECT device_id, MAX(created_at) as max_time
            FROM agentos_device_positions
            GROUP BY device_id
        ) latest ON p.device_id = latest.device_id AND p.created_at = latest.max_time
        LEFT JOIN agentos_devices d ON p.device_id = d.device_id
        WHERE d.status IN ('active','provisioned')
        ORDER BY p.device_id
    ");

    $positions = $stmt->fetchAll();

    // Get active geofence zones for overlay
    $zones = [];
    try {
        $zStmt = $pdo->query("SELECT zone_id, name, zone_type, geometry, breach_action FROM agentos_geofence_zones WHERE is_active = 1");
        $zones = $zStmt->fetchAll();
        foreach ($zones as &$z) {
            $z['geometry'] = json_decode($z['geometry'], true);
        }
    } catch (\Throwable $e) {}

    agentos_respond([
        'ok' => true,
        'devices' => $positions,
        'geofence_zones' => $zones,
        'timestamp' => date('c')
    ]);
}

function handleUptime(array $auth): void {
    $pdo = agentos_pdo();
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['device_id'] ?? '');
    $days = min(365, max(1, intval($_GET['days'] ?? 30)));
    if (!$deviceId) agentos_error('device_id required');

    // Calculate uptime from watchdog pings
    $stmt = $pdo->prepare("SELECT metric_value, recorded_at 
        FROM agentos_telemetry_history 
        WHERE device_id = ? AND metric_name = 'uptime_seconds'
        ORDER BY recorded_at DESC LIMIT 1");
    $stmt->execute([$deviceId]);
    $uptimeRaw = $stmt->fetch();

    // Safety events count (downtime incidents)
    $incidents = $pdo->prepare("SELECT COUNT(*) FROM agentos_safety_events 
        WHERE device_id = ? AND event_type IN ('estop','watchdog_timeout') 
        AND created_at > DATE_SUB(NOW(), INTERVAL ? DAY)");
    $incidents->execute([$deviceId, $days]);

    $uptimeSeconds = $uptimeRaw ? intval($uptimeRaw['metric_value']) : 0;

    agentos_respond([
        'ok' => true,
        'device_id' => $deviceId,
        'uptime' => [
            'current_seconds' => $uptimeSeconds,
            'current_formatted' => formatUptime($uptimeSeconds),
            'incidents_period' => (int)$incidents->fetchColumn(),
            'period_days' => $days
        ]
    ]);
}

// ── Helpers ────────────────────────────────────────────────────

function formatUptime(int $seconds): string {
    $days = floor($seconds / 86400);
    $hours = floor(($seconds % 86400) / 3600);
    $mins = floor(($seconds % 3600) / 60);
    return "{$days}d {$hours}h {$mins}m";
}
