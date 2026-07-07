<?php
/**
 * GSM Alfred OS — Robot Diagnostics & Self-Test System
 *
 * Comprehensive health checks, component-level testing, error code management,
 * predictive maintenance, diagnostic history, and self-healing routines.
 *
 * Endpoints (16):
 *   run_diagnostic     — Trigger a full diagnostic suite on a device
 *   diagnostic_status  — Check diagnostic run status
 *   diagnostic_history — View past diagnostic runs
 *   component_test     — Run individual component test
 *   health_summary     — Quick health overview of a device
 *   error_codes        — List all known error codes
 *   report_error       — Device reports an error
 *   active_errors      — Get active (unresolved) errors for a device
 *   resolve_error      — Mark an error as resolved
 *   maintenance_schedule — Get/create predictive maintenance schedule
 *   maintenance_due    — Get upcoming maintenance items
 *   battery_health     — Deep battery analysis
 *   sensor_calibration — Run sensor calibration routines
 *   self_heal          — Trigger self-healing for known issues
 *   fleet_health       — Aggregate health across all devices
 *   export_report      — Generate exportable diagnostic report
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
function diagEnsureSchema(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    $pdo = agentos_pdo();

    $pdo->exec("CREATE TABLE IF NOT EXISTS agentos_diag_runs (
        id VARCHAR(32) PRIMARY KEY,
        device_id VARCHAR(32) NOT NULL,
        run_type ENUM('full','quick','component','scheduled','pre_deploy','post_incident') NOT NULL DEFAULT 'full',
        status ENUM('queued','running','completed','failed','cancelled') NOT NULL DEFAULT 'queued',
        overall_result ENUM('healthy','degraded','warning','critical','unknown') DEFAULT 'unknown',
        total_tests INT DEFAULT 0,
        passed_tests INT DEFAULT 0,
        failed_tests INT DEFAULT 0,
        warning_tests INT DEFAULT 0,
        test_results JSON,
        duration_ms INT,
        triggered_by VARCHAR(64),
        started_at DATETIME,
        completed_at DATETIME,
        created_at DATETIME NOT NULL,
        INDEX idx_device (device_id),
        INDEX idx_status (status),
        INDEX idx_result (overall_result),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS agentos_diag_errors (
        id VARCHAR(32) PRIMARY KEY,
        device_id VARCHAR(32) NOT NULL,
        error_code VARCHAR(16) NOT NULL,
        severity ENUM('info','warning','error','critical','fatal') NOT NULL DEFAULT 'error',
        component ENUM('motor_left','motor_right','motor_arm','lidar','camera_front','camera_rear','camera_depth','imu','gps','battery','pcb_main','pcb_power','pcb_sensor','wifi','bluetooth','cellular','speaker','microphone','display','compute','storage','cooling','chassis','software','network','other') NOT NULL,
        message VARCHAR(512) NOT NULL,
        details JSON,
        occurrence_count INT DEFAULT 1,
        first_seen DATETIME NOT NULL,
        last_seen DATETIME NOT NULL,
        resolved TINYINT(1) DEFAULT 0,
        resolved_at DATETIME,
        resolved_by VARCHAR(64),
        resolution_notes TEXT,
        auto_healed TINYINT(1) DEFAULT 0,
        INDEX idx_device (device_id),
        INDEX idx_code (error_code),
        INDEX idx_severity (severity),
        INDEX idx_component (component),
        INDEX idx_resolved (resolved)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS agentos_diag_error_codes (
        error_code VARCHAR(16) PRIMARY KEY,
        category VARCHAR(32) NOT NULL,
        component VARCHAR(32) NOT NULL,
        severity_default ENUM('info','warning','error','critical','fatal') NOT NULL DEFAULT 'error',
        description VARCHAR(256) NOT NULL,
        possible_causes TEXT,
        recommended_actions TEXT,
        self_healable TINYINT(1) DEFAULT 0,
        heal_procedure VARCHAR(128),
        created_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS agentos_diag_maintenance (
        id VARCHAR(32) PRIMARY KEY,
        device_id VARCHAR(32) NOT NULL,
        component VARCHAR(64) NOT NULL,
        maintenance_type ENUM('inspection','cleaning','calibration','replacement','firmware_update','lubrication','testing','overhaul') NOT NULL,
        priority ENUM('critical','high','normal','low') NOT NULL DEFAULT 'normal',
        status ENUM('scheduled','overdue','in_progress','completed','skipped') NOT NULL DEFAULT 'scheduled',
        interval_hours INT,
        interval_km FLOAT,
        last_performed DATETIME,
        next_due DATETIME,
        current_hours FLOAT DEFAULT 0,
        current_km FLOAT DEFAULT 0,
        performed_by VARCHAR(128),
        notes TEXT,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        INDEX idx_device (device_id),
        INDEX idx_due (next_due),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS agentos_diag_battery (
        id VARCHAR(32) PRIMARY KEY,
        device_id VARCHAR(32) NOT NULL,
        cycle_count INT DEFAULT 0,
        design_capacity_mah INT,
        current_capacity_mah INT,
        health_percent FLOAT DEFAULT 100,
        voltage_mv INT,
        current_ma INT,
        temperature_c FLOAT,
        charge_percent FLOAT,
        charging TINYINT(1) DEFAULT 0,
        charge_rate_w FLOAT,
        estimated_range_km FLOAT,
        estimated_runtime_min INT,
        cell_voltages JSON,
        degradation_rate_per_cycle FLOAT,
        predicted_eol_cycles INT,
        last_full_charge DATETIME,
        recorded_at DATETIME NOT NULL,
        INDEX idx_device (device_id),
        INDEX idx_health (health_percent),
        INDEX idx_recorded (recorded_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // ─── Seed Error Codes ────────────────────────────────────
    $stmt = $pdo->query("SELECT COUNT(*) as c FROM agentos_diag_error_codes");
    if ((int)$stmt->fetch()['c'] === 0) {
        $codes = [
            // Motor errors
            ['M001', 'motor', 'motor_left', 'error', 'Left motor stall detected', 'Obstruction, overload, wiring fault', 'Check motor path, inspect wiring, restart motor controller', 1, 'restart_motor'],
            ['M002', 'motor', 'motor_right', 'error', 'Right motor stall detected', 'Obstruction, overload, wiring fault', 'Check motor path, inspect wiring, restart motor controller', 1, 'restart_motor'],
            ['M003', 'motor', 'motor_left', 'warning', 'Motor temperature high', 'Extended operation, ambient heat, bearing wear', 'Reduce speed, allow cooldown, check bearings', 1, 'reduce_speed'],
            ['M004', 'motor', 'motor_arm', 'critical', 'Arm motor encoder failure', 'Encoder cable, encoder chip failure', 'Immediate stop, manual inspection required', 0, null],

            // Sensor errors
            ['S001', 'sensor', 'lidar', 'critical', 'LiDAR not responding', 'Connection lost, module failure, firmware crash', 'Power cycle LiDAR, check connections', 1, 'power_cycle_lidar'],
            ['S002', 'sensor', 'camera_front', 'error', 'Front camera image degraded', 'Lens obstruction, exposure failure, cable loose', 'Clean lens, restart camera driver', 1, 'restart_camera'],
            ['S003', 'sensor', 'imu', 'warning', 'IMU drift exceeds threshold', 'Magnetic interference, calibration needed', 'Recalibrate IMU, check for magnetic sources', 1, 'calibrate_imu'],
            ['S004', 'sensor', 'gps', 'warning', 'GPS signal weak', 'Indoor operation, urban canyon, antenna issue', 'Move to open area, check antenna', 0, null],
            ['S005', 'sensor', 'camera_depth', 'error', 'Depth camera point cloud corrupt', 'IR interference, hardware fault', 'Restart depth camera, check environment', 1, 'restart_camera'],

            // Battery errors
            ['B001', 'battery', 'battery', 'warning', 'Battery below 20%', 'Normal discharge', 'Navigate to charging station', 1, 'navigate_to_charger'],
            ['B002', 'battery', 'battery', 'critical', 'Battery below 5% — emergency', 'Over-discharge risk', 'Immediate safe stop, alert operator', 1, 'emergency_stop'],
            ['B003', 'battery', 'battery', 'critical', 'Battery temperature critical', 'Rapid charge, ambient heat, cell failure', 'Stop charging, safe shutdown', 1, 'emergency_shutdown'],
            ['B004', 'battery', 'battery', 'error', 'Battery cell imbalance detected', 'Cell degradation, BMS issue', 'Run cell balancing, schedule battery inspection', 1, 'balance_cells'],

            // Compute errors
            ['C001', 'compute', 'compute', 'warning', 'CPU temperature high', 'Heavy processing, cooling failure', 'Reduce processing load, check cooling', 1, 'throttle_cpu'],
            ['C002', 'compute', 'storage', 'warning', 'Storage 90% full', 'Log buildup, map cache', 'Purge old logs, compress maps', 1, 'purge_logs'],
            ['C003', 'compute', 'compute', 'error', 'ROS2 node crash detected', 'Software bug, resource exhaustion', 'Restart node, collect crash dump', 1, 'restart_ros_node'],

            // Network errors
            ['N001', 'network', 'wifi', 'warning', 'WiFi signal weak', 'Range, interference', 'Move closer to AP, switch to cellular', 1, 'switch_network'],
            ['N002', 'network', 'cellular', 'error', 'Cellular connection lost', 'No coverage, SIM issue', 'Switch to WiFi, continue autonomous', 1, 'switch_network'],
            ['N003', 'network', 'network', 'critical', 'All connectivity lost', 'Coverage dead zone, hardware fault', 'Enable offline mode, navigate to last known connectivity', 1, 'enable_offline_mode'],

            // Safety errors
            ['F001', 'safety', 'chassis', 'fatal', 'Collision detected', 'Obstacle not detected, sensor failure', 'Emergency stop, report to operator', 0, null],
            ['F002', 'safety', 'chassis', 'critical', 'E-Stop activated', 'Manual E-Stop or remote E-Stop', 'Investigate cause, reset when safe', 0, null],
            ['F003', 'safety', 'software', 'critical', 'Safety watchdog timeout', 'Software hang, high CPU', 'Force restart safety controller', 1, 'restart_safety'],
        ];

        $stmt = $pdo->prepare('INSERT INTO agentos_diag_error_codes (error_code, category, component, severity_default, description, possible_causes, recommended_actions, self_healable, heal_procedure, created_at) VALUES (?,?,?,?,?,?,?,?,?,NOW())');
        foreach ($codes as $c) {
            $stmt->execute($c);
        }
    }
}

diagEnsureSchema();
$auth = agentos_auth();

// ─── Component test definitions ──────────────────────────────
function getComponentTests(): array {
    return [
        'motor_left'    => ['name' => 'Left Drive Motor', 'tests' => ['power_draw','stall_detect','encoder_count','temperature','speed_accuracy']],
        'motor_right'   => ['name' => 'Right Drive Motor', 'tests' => ['power_draw','stall_detect','encoder_count','temperature','speed_accuracy']],
        'motor_arm'     => ['name' => 'Arm Motor', 'tests' => ['power_draw','encoder_count','position_accuracy','torque_check']],
        'lidar'         => ['name' => 'LiDAR Sensor', 'tests' => ['connection','scan_rate','range_check','point_density','noise_level']],
        'camera_front'  => ['name' => 'Front Camera', 'tests' => ['connection','image_capture','exposure','focus','framerate']],
        'camera_rear'   => ['name' => 'Rear Camera', 'tests' => ['connection','image_capture','exposure','focus','framerate']],
        'camera_depth'  => ['name' => 'Depth Camera', 'tests' => ['connection','depth_accuracy','point_cloud','ir_emitter']],
        'imu'           => ['name' => 'IMU (Accel+Gyro+Mag)', 'tests' => ['connection','bias_check','noise_level','orientation']],
        'gps'           => ['name' => 'GNSS Receiver', 'tests' => ['connection','satellite_count','fix_quality','accuracy']],
        'battery'       => ['name' => 'Battery System', 'tests' => ['voltage','current','temperature','cell_balance','bms_comms','capacity_test']],
        'wifi'          => ['name' => 'WiFi Module', 'tests' => ['connection','signal_strength','throughput','latency']],
        'bluetooth'     => ['name' => 'Bluetooth Module', 'tests' => ['connection','discovery','pairing','throughput']],
        'cellular'      => ['name' => 'Cellular Modem', 'tests' => ['connection','signal_strength','registration','data_test']],
        'speaker'       => ['name' => 'Speaker', 'tests' => ['connection','tone_test','volume_range']],
        'microphone'    => ['name' => 'Microphone Array', 'tests' => ['connection','noise_floor','sensitivity','beamforming']],
        'compute'       => ['name' => 'Main Compute', 'tests' => ['cpu_temp','cpu_load','memory_usage','gpu_temp','gpu_load']],
        'storage'       => ['name' => 'Storage', 'tests' => ['capacity','read_speed','write_speed','health_check']],
        'cooling'       => ['name' => 'Cooling System', 'tests' => ['fan_speed','thermal_zones','airflow_check']],
    ];
}

// ─── Handlers ───────────────────────────────────────────────────

function handleRunDiagnostic(): void {
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);

    $deviceId = $data['device_id'] ?? '';
    if (!$deviceId) agentos_error('Missing device_id');

    $runType = $data['run_type'] ?? 'full';
    $components = getComponentTests();

    $id = agentos_id('diag');

    // Build test manifest
    $testManifest = [];
    $totalTests = 0;
    foreach ($components as $compKey => $comp) {
        if ($runType === 'quick' && !in_array($compKey, ['battery','compute','lidar','camera_front','motor_left','motor_right'], true)) {
            continue;
        }
        foreach ($comp['tests'] as $test) {
            $testManifest[] = ['component' => $compKey, 'component_name' => $comp['name'], 'test' => $test, 'status' => 'pending', 'result' => null];
            $totalTests++;
        }
    }

    $stmt = $pdo->prepare('INSERT INTO agentos_diag_runs (id, device_id, run_type, status, total_tests, test_results, triggered_by, created_at) VALUES (?,?,?,?,?,?,?,NOW())');
    $stmt->execute([$id, $deviceId, $runType, 'queued', $totalTests, json_encode($testManifest), $data['triggered_by'] ?? 'api']);

    // Push command to device
    agentos_push("device:$deviceId", 'diagnostic_start', [
        'run_id' => $id, 'run_type' => $runType, 'tests' => $testManifest
    ]);

    agentos_respond(['ok' => true, 'run_id' => $id, 'total_tests' => $totalTests, 'run_type' => $runType], 201);
}

function handleDiagnosticStatus(): void {
    $pdo = agentos_pdo();
    $runId = $_GET['run_id'] ?? '';
    if (!$runId) agentos_error('Missing run_id');

    $stmt = $pdo->prepare('SELECT * FROM agentos_diag_runs WHERE id = ?');
    $stmt->execute([$runId]);
    $run = $stmt->fetch();
    if (!$run) agentos_error('Diagnostic run not found', 404);

    $run['test_results'] = json_decode($run['test_results'] ?? '[]', true);
    agentos_respond(['ok' => true, 'diagnostic' => $run]);
}

function handleDiagnosticHistory(): void {
    $pdo = agentos_pdo();
    $deviceId = $_GET['device_id'] ?? '';
    if (!$deviceId) agentos_error('Missing device_id');

    $limit = min(max((int)($_GET['limit'] ?? 20), 1), 100);
    $stmt = $pdo->prepare('SELECT id, device_id, run_type, status, overall_result, total_tests, passed_tests, failed_tests, warning_tests, duration_ms, triggered_by, started_at, completed_at, created_at FROM agentos_diag_runs WHERE device_id = ? ORDER BY created_at DESC LIMIT ?');
    dbExecute($stmt, [$deviceId, $limit]);

    agentos_respond(['ok' => true, 'history' => $stmt->fetchAll()]);
}

function handleComponentTest(): void {
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);

    $deviceId = $data['device_id'] ?? '';
    $component = $data['component'] ?? '';
    if (!$deviceId || !$component) agentos_error('Missing device_id or component');

    $components = getComponentTests();
    if (!isset($components[$component])) agentos_error('Unknown component: ' . $component);

    $id = agentos_id('diag');
    $compDef = $components[$component];
    $tests = [];
    foreach ($compDef['tests'] as $t) {
        $tests[] = ['component' => $component, 'component_name' => $compDef['name'], 'test' => $t, 'status' => 'pending', 'result' => null];
    }

    $stmt = $pdo->prepare('INSERT INTO agentos_diag_runs (id, device_id, run_type, status, total_tests, test_results, triggered_by, created_at) VALUES (?,?,?,?,?,?,?,NOW())');
    $stmt->execute([$id, $deviceId, 'component', 'queued', count($tests), json_encode($tests), 'api']);

    agentos_push("device:$deviceId", 'component_test', [
        'run_id' => $id, 'component' => $component, 'tests' => $tests
    ]);

    agentos_respond(['ok' => true, 'run_id' => $id, 'component' => $component, 'tests_count' => count($tests)], 201);
}

function handleHealthSummary(): void {
    $pdo = agentos_pdo();
    $deviceId = $_GET['device_id'] ?? '';
    if (!$deviceId) agentos_error('Missing device_id');

    // Latest diagnostic
    $stmt = $pdo->prepare('SELECT * FROM agentos_diag_runs WHERE device_id = ? AND status = ? ORDER BY completed_at DESC LIMIT 1');
    $stmt->execute([$deviceId, 'completed']);
    $lastDiag = $stmt->fetch();
    if ($lastDiag) $lastDiag['test_results'] = json_decode($lastDiag['test_results'] ?? '[]', true);

    // Active errors
    $stmt = $pdo->prepare('SELECT * FROM agentos_diag_errors WHERE device_id = ? AND resolved = 0 ORDER BY severity ASC');
    $stmt->execute([$deviceId]);
    $activeErrors = $stmt->fetchAll();

    // Battery
    $stmt = $pdo->prepare('SELECT * FROM agentos_diag_battery WHERE device_id = ? ORDER BY recorded_at DESC LIMIT 1');
    $stmt->execute([$deviceId]);
    $battery = $stmt->fetch();
    if ($battery) $battery['cell_voltages'] = json_decode($battery['cell_voltages'] ?? '[]', true);

    // Overdue maintenance
    $stmt = $pdo->prepare('SELECT * FROM agentos_diag_maintenance WHERE device_id = ? AND (status = ? OR (next_due IS NOT NULL AND next_due < NOW())) ORDER BY next_due ASC');
    $stmt->execute([$deviceId, 'overdue']);
    $overdue = $stmt->fetchAll();

    // Determine overall health
    $health = 'healthy';
    $criticalErrors = count(array_filter($activeErrors, fn($e) => in_array($e['severity'], ['critical','fatal'])));
    $warningErrors = count(array_filter($activeErrors, fn($e) => $e['severity'] === 'warning'));
    if ($criticalErrors > 0) $health = 'critical';
    elseif (count($activeErrors) > 3 || $warningErrors > 5) $health = 'degraded';
    elseif (count($activeErrors) > 0 || count($overdue) > 0) $health = 'warning';

    agentos_respond(['ok' => true, 'health' => [
        'device_id' => $deviceId,
        'overall' => $health,
        'last_diagnostic' => $lastDiag ? [
            'run_id' => $lastDiag['id'],
            'result' => $lastDiag['overall_result'],
            'completed_at' => $lastDiag['completed_at'],
            'passed' => (int)$lastDiag['passed_tests'],
            'failed' => (int)$lastDiag['failed_tests']
        ] : null,
        'active_errors' => count($activeErrors),
        'critical_errors' => $criticalErrors,
        'errors' => $activeErrors,
        'battery' => $battery,
        'overdue_maintenance' => $overdue,
    ]]);
}

function handleErrorCodes(): void {
    $pdo = agentos_pdo();
    $category = $_GET['category'] ?? null;
    $sql = 'SELECT * FROM agentos_diag_error_codes';
    $params = [];
    if ($category) { $sql .= ' WHERE category = ?'; $params[] = $category; }
    $sql .= ' ORDER BY error_code';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    agentos_respond(['ok' => true, 'error_codes' => $stmt->fetchAll()]);
}

function handleReportError(): void {
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);

    $deviceId = $data['device_id'] ?? '';
    $errorCode = $data['error_code'] ?? '';
    if (!$deviceId || !$errorCode) agentos_error('Missing device_id or error_code');

    // Check for existing unresolved error
    $stmt = $pdo->prepare('SELECT id, occurrence_count FROM agentos_diag_errors WHERE device_id = ? AND error_code = ? AND resolved = 0');
    $stmt->execute([$deviceId, $errorCode]);
    $existing = $stmt->fetch();

    if ($existing) {
        $pdo->prepare('UPDATE agentos_diag_errors SET occurrence_count = occurrence_count + 1, last_seen = NOW(), details = ? WHERE id = ?')
            ->execute([json_encode($data['details'] ?? []), $existing['id']]);
        agentos_respond(['ok' => true, 'error_id' => $existing['id'], 'occurrences' => (int)$existing['occurrence_count'] + 1]);
        return;
    }

    // Look up error code defaults
    $stmt = $pdo->prepare('SELECT * FROM agentos_diag_error_codes WHERE error_code = ?');
    $stmt->execute([$errorCode]);
    $codeDef = $stmt->fetch();

    $id = agentos_id('err');
    $stmt = $pdo->prepare('INSERT INTO agentos_diag_errors (id, device_id, error_code, severity, component, message, details, first_seen, last_seen) VALUES (?,?,?,?,?,?,?,NOW(),NOW())');
    $stmt->execute([
        $id, $deviceId, $errorCode,
        $data['severity'] ?? ($codeDef['severity_default'] ?? 'error'),
        $data['component'] ?? ($codeDef['component'] ?? 'other'),
        $data['message'] ?? ($codeDef['description'] ?? 'Unknown error'),
        json_encode($data['details'] ?? [])
    ]);

    // Attempt self-heal if supported
    $healed = false;
    if ($codeDef && $codeDef['self_healable'] && $codeDef['heal_procedure']) {
        agentos_push("device:$deviceId", 'self_heal', [
            'error_id' => $id, 'error_code' => $errorCode,
            'procedure' => $codeDef['heal_procedure']
        ]);
        $healed = true;
    }

    agentos_respond(['ok' => true, 'error_id' => $id, 'self_heal_triggered' => $healed], 201);
}

function handleActiveErrors(): void {
    $pdo = agentos_pdo();
    $deviceId = $_GET['device_id'] ?? '';
    if (!$deviceId) agentos_error('Missing device_id');

    $stmt = $pdo->prepare('SELECT e.*, c.description, c.possible_causes, c.recommended_actions, c.self_healable FROM agentos_diag_errors e LEFT JOIN agentos_diag_error_codes c ON e.error_code = c.error_code WHERE e.device_id = ? AND e.resolved = 0 ORDER BY e.severity ASC, e.last_seen DESC');
    $stmt->execute([$deviceId]);
    $errors = $stmt->fetchAll();
    foreach ($errors as &$e) $e['details'] = json_decode($e['details'] ?? '[]', true);

    agentos_respond(['ok' => true, 'active_errors' => $errors, 'count' => count($errors)]);
}

function handleResolveError(): void {
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);
    $errorId = $data['error_id'] ?? '';
    if (!$errorId) agentos_error('Missing error_id');

    $pdo->prepare('UPDATE agentos_diag_errors SET resolved = 1, resolved_at = NOW(), resolved_by = ?, resolution_notes = ?, auto_healed = ? WHERE id = ?')
        ->execute([
            $data['resolved_by'] ?? 'operator',
            $data['resolution_notes'] ?? null,
            (int)($data['auto_healed'] ?? 0),
            $errorId
        ]);

    agentos_respond(['ok' => true, 'resolved' => true]);
}

function handleMaintenanceSchedule(): void {
    $pdo = agentos_pdo();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $deviceId = $data['device_id'] ?? '';
        if (!$deviceId) agentos_error('Missing device_id');

        $id = agentos_id('mnt');
        $stmt = $pdo->prepare('INSERT INTO agentos_diag_maintenance (id, device_id, component, maintenance_type, priority, status, interval_hours, interval_km, next_due, notes, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,NOW(),NOW())');
        $stmt->execute([
            $id, $deviceId,
            $data['component'] ?? 'general',
            $data['maintenance_type'] ?? 'inspection',
            $data['priority'] ?? 'normal',
            'scheduled',
            $data['interval_hours'] ?? null,
            $data['interval_km'] ?? null,
            $data['next_due'] ?? null,
            $data['notes'] ?? null
        ]);

        agentos_respond(['ok' => true, 'maintenance_id' => $id], 201);
    } else {
        $deviceId = $_GET['device_id'] ?? '';
        if (!$deviceId) agentos_error('Missing device_id');

        $stmt = $pdo->prepare('SELECT * FROM agentos_diag_maintenance WHERE device_id = ? ORDER BY next_due ASC');
        $stmt->execute([$deviceId]);
        agentos_respond(['ok' => true, 'maintenance' => $stmt->fetchAll()]);
    }
}

function handleMaintenanceDue(): void {
    $pdo = agentos_pdo();
    $days = min(max((int)($_GET['days'] ?? 30), 1), 365);

    $stmt = $pdo->prepare("SELECT m.*, d.serial_number FROM agentos_diag_maintenance m LEFT JOIN agentos_mfg_serials d ON m.device_id = d.serial_number WHERE (m.next_due IS NOT NULL AND m.next_due <= DATE_ADD(NOW(), INTERVAL ? DAY)) OR m.status = 'overdue' ORDER BY m.next_due ASC");
    $stmt->execute([$days]);

    agentos_respond(['ok' => true, 'due_items' => $stmt->fetchAll(), 'window_days' => $days]);
}

function handleBatteryHealth(): void {
    $pdo = agentos_pdo();
    $deviceId = $_GET['device_id'] ?? '';
    if (!$deviceId) agentos_error('Missing device_id');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = agentos_id('bat');
        $stmt = $pdo->prepare('INSERT INTO agentos_diag_battery (id, device_id, cycle_count, design_capacity_mah, current_capacity_mah, health_percent, voltage_mv, current_ma, temperature_c, charge_percent, charging, charge_rate_w, estimated_range_km, estimated_runtime_min, cell_voltages, degradation_rate_per_cycle, predicted_eol_cycles, last_full_charge, recorded_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())');
        $stmt->execute([
            $id, $deviceId,
            (int)($data['cycle_count'] ?? 0),
            (int)($data['design_capacity_mah'] ?? 0),
            (int)($data['current_capacity_mah'] ?? 0),
            (float)($data['health_percent'] ?? 100),
            (int)($data['voltage_mv'] ?? 0),
            (int)($data['current_ma'] ?? 0),
            (float)($data['temperature_c'] ?? 0),
            (float)($data['charge_percent'] ?? 0),
            (int)($data['charging'] ?? 0),
            (float)($data['charge_rate_w'] ?? 0),
            (float)($data['estimated_range_km'] ?? 0),
            (int)($data['estimated_runtime_min'] ?? 0),
            json_encode($data['cell_voltages'] ?? []),
            (float)($data['degradation_rate_per_cycle'] ?? 0),
            (int)($data['predicted_eol_cycles'] ?? 0),
            $data['last_full_charge'] ?? null
        ]);
        agentos_respond(['ok' => true, 'battery_record_id' => $id], 201);
    } else {
        // Latest + history
        $stmt = $pdo->prepare('SELECT * FROM agentos_diag_battery WHERE device_id = ? ORDER BY recorded_at DESC LIMIT 50');
        $stmt->execute([$deviceId]);
        $records = $stmt->fetchAll();
        foreach ($records as &$r) $r['cell_voltages'] = json_decode($r['cell_voltages'] ?? '[]', true);

        $latest = $records[0] ?? null;
        $trend = [];
        foreach (array_reverse($records) as $r) {
            $trend[] = ['date' => $r['recorded_at'], 'health' => (float)$r['health_percent'], 'cycles' => (int)$r['cycle_count']];
        }

        agentos_respond(['ok' => true, 'latest' => $latest, 'trend' => $trend, 'records' => count($records)]);
    }
}

function handleSensorCalibration(): void {
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);
    $deviceId = $data['device_id'] ?? '';
    $sensors = $data['sensors'] ?? ['imu', 'lidar', 'camera_front', 'camera_depth'];
    if (!$deviceId) agentos_error('Missing device_id');

    agentos_push("device:$deviceId", 'calibration_start', [
        'sensors' => $sensors, 'timestamp' => date('c')
    ]);

    agentos_respond(['ok' => true, 'calibrating' => $sensors, 'device_id' => $deviceId]);
}

function handleSelfHeal(): void {
    $pdo = agentos_pdo();
    $data = json_decode(file_get_contents('php://input'), true);
    $deviceId = $data['device_id'] ?? '';
    if (!$deviceId) agentos_error('Missing device_id');

    // Find all self-healable active errors
    $stmt = $pdo->prepare('SELECT e.*, c.heal_procedure FROM agentos_diag_errors e JOIN agentos_diag_error_codes c ON e.error_code = c.error_code WHERE e.device_id = ? AND e.resolved = 0 AND c.self_healable = 1');
    $stmt->execute([$deviceId]);
    $healable = $stmt->fetchAll();

    $procedures = [];
    foreach ($healable as $e) {
        $procedures[] = [
            'error_id' => $e['id'], 'error_code' => $e['error_code'],
            'procedure' => $e['heal_procedure'], 'component' => $e['component']
        ];
    }

    if (!empty($procedures)) {
        agentos_push("device:$deviceId", 'self_heal_batch', [
            'procedures' => $procedures, 'timestamp' => date('c')
        ]);
    }

    agentos_respond(['ok' => true, 'healable_errors' => count($procedures), 'procedures' => $procedures]);
}

function handleFleetHealth(): void {
    $pdo = agentos_pdo();

    // Aggregate across all devices
    $deviceErrors = $pdo->query("SELECT device_id, COUNT(*) as error_count, SUM(CASE WHEN severity IN ('critical','fatal') THEN 1 ELSE 0 END) as critical_count FROM agentos_diag_errors WHERE resolved = 0 GROUP BY device_id ORDER BY critical_count DESC")->fetchAll();

    $batteryHealth = $pdo->query("SELECT b.device_id, b.health_percent, b.charge_percent, b.temperature_c, b.estimated_range_km FROM agentos_diag_battery b INNER JOIN (SELECT device_id, MAX(recorded_at) as latest FROM agentos_diag_battery GROUP BY device_id) latest ON b.device_id = latest.device_id AND b.recorded_at = latest.latest")->fetchAll();

    $overdueMaint = $pdo->query("SELECT device_id, COUNT(*) as overdue_count FROM agentos_diag_maintenance WHERE status = 'overdue' OR (next_due IS NOT NULL AND next_due < NOW()) GROUP BY device_id")->fetchAll();

    $totalDevices = count(array_unique(array_merge(
        array_column($deviceErrors, 'device_id'),
        array_column($batteryHealth, 'device_id')
    )));

    $criticalDevices = count(array_filter($deviceErrors, fn($d) => (int)$d['critical_count'] > 0));

    agentos_respond(['ok' => true, 'fleet_health' => [
        'total_devices_reporting' => $totalDevices,
        'devices_with_critical_errors' => $criticalDevices,
        'device_errors' => $deviceErrors,
        'battery_status' => $batteryHealth,
        'overdue_maintenance' => $overdueMaint,
        'fleet_status' => $criticalDevices === 0 ? 'healthy' : 'attention_required'
    ]]);
}

function handleExportReport(): void {
    $pdo = agentos_pdo();
    $deviceId = $_GET['device_id'] ?? '';
    if (!$deviceId) agentos_error('Missing device_id');

    $health = [];

    // Latest diag
    $stmt = $pdo->prepare('SELECT * FROM agentos_diag_runs WHERE device_id = ? ORDER BY completed_at DESC LIMIT 1');
    $stmt->execute([$deviceId]);
    $diag = $stmt->fetch();
    if ($diag) $diag['test_results'] = json_decode($diag['test_results'] ?? '[]', true);
    $health['last_diagnostic'] = $diag;

    // Errors
    $stmt = $pdo->prepare('SELECT * FROM agentos_diag_errors WHERE device_id = ? ORDER BY resolved ASC, last_seen DESC');
    $stmt->execute([$deviceId]);
    $health['errors'] = $stmt->fetchAll();

    // Battery
    $stmt = $pdo->prepare('SELECT * FROM agentos_diag_battery WHERE device_id = ? ORDER BY recorded_at DESC LIMIT 1');
    $stmt->execute([$deviceId]);
    $bat = $stmt->fetch();
    if ($bat) $bat['cell_voltages'] = json_decode($bat['cell_voltages'] ?? '[]', true);
    $health['battery'] = $bat;

    // Maintenance
    $stmt = $pdo->prepare('SELECT * FROM agentos_diag_maintenance WHERE device_id = ? ORDER BY next_due ASC');
    $stmt->execute([$deviceId]);
    $health['maintenance'] = $stmt->fetchAll();

    $health['generated_at'] = date('c');
    $health['device_id'] = $deviceId;

    agentos_respond(['ok' => true, 'report' => $health]);
}

// ─── Router ────────────────────────────────────────────────────
$action = $_GET['action'] ?? $_POST['action'] ?? '';

$routes = [
    'run_diagnostic'       => 'handleRunDiagnostic',
    'diagnostic_status'    => 'handleDiagnosticStatus',
    'diagnostic_history'   => 'handleDiagnosticHistory',
    'component_test'       => 'handleComponentTest',
    'health_summary'       => 'handleHealthSummary',
    'error_codes'          => 'handleErrorCodes',
    'report_error'         => 'handleReportError',
    'active_errors'        => 'handleActiveErrors',
    'resolve_error'        => 'handleResolveError',
    'maintenance_schedule' => 'handleMaintenanceSchedule',
    'maintenance_due'      => 'handleMaintenanceDue',
    'battery_health'       => 'handleBatteryHealth',
    'sensor_calibration'   => 'handleSensorCalibration',
    'self_heal'            => 'handleSelfHeal',
    'fleet_health'         => 'handleFleetHealth',
    'export_report'        => 'handleExportReport',
];

if (!isset($routes[$action])) {
    agentos_respond(['ok' => true, 'module' => 'Alfred OS — Robot Diagnostics & Self-Test', 'version' => AGENTOS_VERSION,
        'description' => 'Health checks, error codes, predictive maintenance, battery analysis, self-healing',
        'endpoints' => array_keys($routes),
        'error_code_categories' => ['motor','sensor','battery','compute','network','safety']]);
}

$routes[$action]();
