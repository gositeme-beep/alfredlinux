<?php
/**
 * GSM Alfred OS — Edge AI Runtime Manager v1.0
 * On-device AI model management, inference orchestration, edge compute
 *
 * Endpoints:
 *   POST   ?action=deploy_model       — Deploy AI model to device edge
 *   GET    ?action=models              — List deployed models on device
 *   POST   ?action=inference           — Run inference request on edge
 *   GET    ?action=inference_log       — Inference history and latency stats
 *   POST   ?action=update_model        — Update model version on edge
 *   POST   ?action=remove_model        — Remove model from device
 *   GET    ?action=capabilities        — Get device AI compute capabilities
 *   POST   ?action=benchmark           — Run AI benchmark on device
 *   GET    ?action=benchmark_results   — Get benchmark results
 *   POST   ?action=set_priority        — Set model inference priority
 *   GET    ?action=resource_usage      — Edge compute resource utilization
 *   POST   ?action=batch_deploy        — Deploy model to multiple devices
 *
 * Supports: TensorFlow Lite, ONNX Runtime, PyTorch Mobile, custom
 * Accelerators: CPU, GPU, NPU, TPU, FPGA
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Internal-Secret, X-Device-Token');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

define('GOSITEME_API', true);
require_once __DIR__ . '/bootstrap.php';
edgeAiEnsureSchema();

$auth = agentos_auth();
$action = $_GET['action'] ?? 'models';

switch ($action) {
    case 'deploy_model':      handleDeployModel($auth); break;
    case 'models':            handleModels($auth); break;
    case 'inference':         handleInference($auth); break;
    case 'inference_log':     handleInferenceLog($auth); break;
    case 'update_model':      handleUpdateModel($auth); break;
    case 'remove_model':      handleRemoveModel($auth); break;
    case 'capabilities':      handleCapabilities($auth); break;
    case 'benchmark':         handleBenchmark($auth); break;
    case 'benchmark_results': handleBenchmarkResults($auth); break;
    case 'set_priority':      handleSetPriority($auth); break;
    case 'resource_usage':    handleResourceUsage($auth); break;
    case 'batch_deploy':      handleBatchDeploy($auth); break;
    default:                  agentos_error('Unknown action');
}

// ── Schema ─────────────────────────────────────────────────────

function edgeAiEnsureSchema(): void {
    static $done = false;
    if ($done) return;
    $done = true;

    $pdo = agentos_pdo();
    $r = $pdo->query("SHOW TABLES LIKE 'agentos_edge_models'");
    if ($r->rowCount() > 0) return;

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS agentos_edge_models (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            model_id        VARCHAR(64) NOT NULL UNIQUE,
            name            VARCHAR(256) NOT NULL,
            version         VARCHAR(32) NOT NULL,
            framework       ENUM('tflite','onnx','pytorch_mobile','tensorrt','openvino','custom') NOT NULL,
            model_type      ENUM('vision','nlp','audio','sensor_fusion','navigation',
                                'object_detection','gesture','slam','anomaly','general') NOT NULL,
            description     TEXT,
            file_size_mb    DECIMAL(8,2),
            file_hash       VARCHAR(64) COMMENT 'SHA-256 of model file',
            input_spec      JSON COMMENT 'Expected input tensor spec',
            output_spec     JSON COMMENT 'Output tensor spec',
            min_memory_mb   INT UNSIGNED DEFAULT 256,
            min_compute_tops DECIMAL(6,2) DEFAULT 0 COMMENT 'Min TOPS required',
            accelerator     ENUM('cpu','gpu','npu','tpu','fpga','any') NOT NULL DEFAULT 'any',
            quantization    ENUM('none','fp16','int8','int4','dynamic') DEFAULT 'none',
            accuracy_score  DECIMAL(5,4) COMMENT 'Model accuracy 0-1',
            is_active       TINYINT(1) NOT NULL DEFAULT 1,
            created_by      INT UNSIGNED,
            created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_type (model_type),
            INDEX idx_framework (framework),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS agentos_edge_deployments (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            deployment_id   VARCHAR(64) NOT NULL UNIQUE,
            model_id        VARCHAR(64) NOT NULL,
            device_id       VARCHAR(128) NOT NULL,
            status          ENUM('pending','downloading','deploying','active','paused',
                                'failed','removed') NOT NULL DEFAULT 'pending',
            priority        INT UNSIGNED NOT NULL DEFAULT 50 COMMENT 'Inference priority 0-100',
            config          JSON COMMENT 'Runtime configuration overrides',
            inference_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
            avg_latency_ms  DECIMAL(8,2) DEFAULT 0,
            last_inference  TIMESTAMP NULL,
            error_message   TEXT,
            deployed_at     TIMESTAMP NULL,
            created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_model_device (model_id, device_id),
            INDEX idx_device (device_id),
            INDEX idx_status (status),
            INDEX idx_model (model_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS agentos_edge_inferences (
            id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            inference_id    VARCHAR(64) NOT NULL UNIQUE,
            model_id        VARCHAR(64) NOT NULL,
            device_id       VARCHAR(128) NOT NULL,
            input_summary   JSON COMMENT 'Summarized input metadata',
            output_summary  JSON COMMENT 'Summarized output/predictions',
            latency_ms      DECIMAL(8,2) NOT NULL,
            confidence      DECIMAL(5,4),
            accelerator_used ENUM('cpu','gpu','npu','tpu','fpga') NOT NULL DEFAULT 'cpu',
            memory_used_mb  INT UNSIGNED,
            status          ENUM('success','error','timeout') NOT NULL DEFAULT 'success',
            error_message   TEXT,
            created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_model (model_id),
            INDEX idx_device (device_id),
            INDEX idx_created (created_at),
            INDEX idx_model_device (model_id, device_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS agentos_edge_benchmarks (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            benchmark_id    VARCHAR(64) NOT NULL UNIQUE,
            device_id       VARCHAR(128) NOT NULL,
            model_id        VARCHAR(64),
            benchmark_type  ENUM('latency','throughput','accuracy','memory','power','full') NOT NULL DEFAULT 'full',
            results         JSON NOT NULL,
            score           DECIMAL(10,2),
            status          ENUM('running','completed','failed') NOT NULL DEFAULT 'running',
            started_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            completed_at    TIMESTAMP NULL,
            INDEX idx_device (device_id),
            INDEX idx_model (model_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Seed default Alfred AI models
    $defaultModels = [
        ['alfred_vision_v1', 'Alfred Vision Core', '1.0.0', 'tflite', 'object_detection', 'Real-time object detection for navigation', 45.0, 'int8', 'npu'],
        ['alfred_gesture_v1', 'Alfred Gesture Recognition', '1.0.0', 'tflite', 'gesture', 'Hand/body gesture command recognition', 12.0, 'int8', 'npu'],
        ['alfred_slam_v1', 'Alfred Visual SLAM', '1.0.0', 'onnx', 'slam', 'Simultaneous localization and mapping', 85.0, 'fp16', 'gpu'],
        ['alfred_nlp_v1', 'Alfred NLP Edge', '1.0.0', 'onnx', 'nlp', 'On-device natural language understanding', 120.0, 'int8', 'cpu'],
        ['alfred_anomaly_v1', 'Alfred Anomaly Detector', '1.0.0', 'tflite', 'anomaly', 'Sensor anomaly and fault detection', 8.0, 'int8', 'cpu'],
        ['alfred_audio_v1', 'Alfred Audio Processing', '1.0.0', 'tflite', 'audio', 'Wake word and command recognition', 15.0, 'int8', 'npu'],
        ['alfred_fusion_v1', 'Alfred Sensor Fusion', '1.0.0', 'onnx', 'sensor_fusion', 'Multi-sensor data fusion for navigation', 30.0, 'fp16', 'gpu'],
        ['alfred_nav_v1', 'Alfred Navigation Planner', '1.0.0', 'pytorch_mobile', 'navigation', 'Path planning and obstacle avoidance', 55.0, 'fp16', 'gpu'],
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO agentos_edge_models 
        (model_id, name, version, framework, model_type, description, file_size_mb, quantization, accelerator) VALUES (?,?,?,?,?,?,?,?,?)");
    foreach ($defaultModels as $m) $stmt->execute($m);

    error_log("[AGENTOS-EDGE-AI] Schema auto-migrated with default models");
}

// ── Handlers ───────────────────────────────────────────────────

function handleDeployModel(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $modelId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['model_id'] ?? '');
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['device_id'] ?? '');

    if (!$modelId || !$deviceId) agentos_error('model_id and device_id required');

    $pdo = agentos_pdo();

    // Verify model exists
    $stmt = $pdo->prepare("SELECT * FROM agentos_edge_models WHERE model_id = ? AND is_active = 1");
    $stmt->execute([$modelId]);
    $model = $stmt->fetch();
    if (!$model) agentos_error('Model not found');

    $deployId = agentos_id('edep');

    $pdo->prepare("INSERT INTO agentos_edge_deployments 
        (deployment_id, model_id, device_id, status, priority, config)
        VALUES (?,?,?,'pending',?,?)
        ON DUPLICATE KEY UPDATE status = 'pending', updated_at = NOW()")
        ->execute([
            $deployId, $modelId, $deviceId,
            intval($input['priority'] ?? 50),
            json_encode($input['config'] ?? [])
        ]);

    // Push deployment command to device
    agentos_push("device:{$deviceId}", 'edge_model_deploy', [
        'deployment_id' => $deployId,
        'model_id' => $modelId,
        'name' => $model['name'],
        'framework' => $model['framework'],
        'file_size_mb' => floatval($model['file_size_mb']),
        'file_hash' => $model['file_hash'],
        'accelerator' => $model['accelerator'],
        'quantization' => $model['quantization'],
        'config' => $input['config'] ?? []
    ]);

    agentos_audit([
        'action_type' => 'edge_model_deploy',
        'user_id' => $auth['user_id'],
        'risk_level' => 'medium',
        'status' => 'completed',
        'input' => ['model_id' => $modelId, 'device_id' => $deviceId]
    ]);

    agentos_respond(['ok' => true, 'deployment_id' => $deployId]);
}

function handleModels(array $auth): void {
    $pdo = agentos_pdo();
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['device_id'] ?? '');

    if ($deviceId) {
        // Models deployed to specific device
        $stmt = $pdo->prepare("
            SELECT m.model_id, m.name, m.version, m.framework, m.model_type, m.accelerator,
                   m.quantization, m.file_size_mb,
                   d.deployment_id, d.status, d.priority, d.inference_count, d.avg_latency_ms,
                   d.last_inference, d.deployed_at
            FROM agentos_edge_deployments d
            JOIN agentos_edge_models m ON d.model_id = m.model_id
            WHERE d.device_id = ? AND d.status != 'removed'
            ORDER BY d.priority DESC
        ");
        $stmt->execute([$deviceId]);
    } else {
        // All available models
        $stmt = $pdo->query("SELECT model_id, name, version, framework, model_type, description,
            file_size_mb, accelerator, quantization, accuracy_score, created_at
            FROM agentos_edge_models WHERE is_active = 1 ORDER BY model_type, name");
    }

    agentos_respond(['ok' => true, 'models' => $stmt->fetchAll()]);
}

function handleInference(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $modelId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['model_id'] ?? '');
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['device_id'] ?? '');

    if (!$modelId || !$deviceId) agentos_error('model_id and device_id required');

    // Verify model is deployed and active on device
    $pdo = agentos_pdo();
    $stmt = $pdo->prepare("SELECT deployment_id FROM agentos_edge_deployments 
        WHERE model_id = ? AND device_id = ? AND status = 'active'");
    $stmt->execute([$modelId, $deviceId]);
    if (!$stmt->fetch()) agentos_error('Model not active on this device');

    $inferenceId = agentos_id('inf');

    // Send inference request to device
    agentos_push("device:{$deviceId}", 'edge_inference_request', [
        'inference_id' => $inferenceId,
        'model_id' => $modelId,
        'input' => $input['input'] ?? [],
        'options' => $input['options'] ?? [],
        'timeout_ms' => intval($input['timeout_ms'] ?? 5000)
    ]);

    // Record inference request (result comes back via telemetry)
    $pdo->prepare("INSERT INTO agentos_edge_inferences 
        (inference_id, model_id, device_id, input_summary, latency_ms, status)
        VALUES (?,?,?,?,0,'success')")
        ->execute([$inferenceId, $modelId, $deviceId, json_encode($input['input'] ?? [])]);

    agentos_respond([
        'ok' => true,
        'inference_id' => $inferenceId,
        'message' => 'Inference request sent to device. Results delivered via WebSocket.'
    ]);
}

function handleInferenceLog(array $auth): void {
    $pdo = agentos_pdo();
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['device_id'] ?? '');
    $modelId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['model_id'] ?? '');
    $limit = min(200, max(1, intval($_GET['limit'] ?? 50)));

    $sql = "SELECT inference_id, model_id, device_id, latency_ms, confidence, 
                   accelerator_used, memory_used_mb, status, created_at
            FROM agentos_edge_inferences WHERE 1=1";
    $params = [];

    if ($deviceId) { $sql .= " AND device_id = ?"; $params[] = $deviceId; }
    if ($modelId) { $sql .= " AND model_id = ?"; $params[] = $modelId; }
    $sql .= " ORDER BY created_at DESC LIMIT ?";
    $params[] = $limit;

    $stmt = $pdo->prepare($sql);
    dbExecute($stmt, $params);

    // Aggregate stats
    $statsQuery = "SELECT COUNT(*) as total, AVG(latency_ms) as avg_latency, 
                   MIN(latency_ms) as min_latency, MAX(latency_ms) as max_latency,
                   AVG(confidence) as avg_confidence
                   FROM agentos_edge_inferences WHERE 1=1";
    $statsParams = [];
    if ($deviceId) { $statsQuery .= " AND device_id = ?"; $statsParams[] = $deviceId; }
    if ($modelId) { $statsQuery .= " AND model_id = ?"; $statsParams[] = $modelId; }

    $statsStmt = $pdo->prepare($statsQuery);
    $statsStmt->execute($statsParams);
    $stats = $statsStmt->fetch();

    agentos_respond(['ok' => true, 'inferences' => $stmt->fetchAll(), 'stats' => $stats]);
}

function handleUpdateModel(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $modelId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['model_id'] ?? '');
    if (!$modelId) agentos_error('model_id required');

    $pdo = agentos_pdo();

    $updates = [];
    $params = [];
    $allowedFields = ['name','version','description','file_size_mb','file_hash','input_spec',
                      'output_spec','accuracy_score','quantization','accelerator'];

    foreach ($allowedFields as $f) {
        if (isset($input[$f])) {
            $updates[] = "$f = ?";
            $params[] = in_array($f, ['input_spec','output_spec']) ? json_encode($input[$f]) : $input[$f];
        }
    }

    if (empty($updates)) agentos_error('No fields to update');
    $params[] = $modelId;

    $pdo->prepare("UPDATE agentos_edge_models SET " . implode(', ', $updates) . " WHERE model_id = ?")
        ->execute($params);

    agentos_respond(['ok' => true, 'model_id' => $modelId]);
}

function handleRemoveModel(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $modelId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['model_id'] ?? '');
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['device_id'] ?? '');

    if (!$modelId || !$deviceId) agentos_error('model_id and device_id required');

    $pdo = agentos_pdo();
    $pdo->prepare("UPDATE agentos_edge_deployments SET status = 'removed' WHERE model_id = ? AND device_id = ?")
        ->execute([$modelId, $deviceId]);

    agentos_push("device:{$deviceId}", 'edge_model_remove', ['model_id' => $modelId]);

    agentos_respond(['ok' => true, 'removed' => true]);
}

function handleCapabilities(array $auth): void {
    $pdo = agentos_pdo();
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['device_id'] ?? '');
    if (!$deviceId) agentos_error('device_id required');

    // Get latest compute capability telemetry
    $metrics = ['cpu_cores','gpu_memory_mb','npu_tops','ram_total_mb','storage_free_mb',
                'gpu_model','npu_model','cpu_model','compute_capability'];
    $caps = [];
    foreach ($metrics as $metric) {
        $stmt = $pdo->prepare("SELECT metric_value FROM agentos_telemetry_history 
            WHERE device_id = ? AND metric_name = ? ORDER BY recorded_at DESC LIMIT 1");
        $stmt->execute([$deviceId, $metric]);
        $val = $stmt->fetchColumn();
        if ($val !== false) $caps[$metric] = $val;
    }

    // Get supported frameworks from deployed models
    $fwStmt = $pdo->prepare("SELECT DISTINCT m.framework FROM agentos_edge_deployments d
        JOIN agentos_edge_models m ON d.model_id = m.model_id
        WHERE d.device_id = ? AND d.status = 'active'");
    $fwStmt->execute([$deviceId]);

    $caps['supported_frameworks'] = $fwStmt->fetchAll(PDO::FETCH_COLUMN);
    $caps['active_models'] = $pdo->prepare("SELECT COUNT(*) FROM agentos_edge_deployments WHERE device_id = ? AND status = 'active'");
    $caps['active_models']->execute([$deviceId]);
    $caps['active_models'] = (int)$caps['active_models']->fetchColumn();

    agentos_respond(['ok' => true, 'device_id' => $deviceId, 'capabilities' => $caps]);
}

function handleBenchmark(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['device_id'] ?? '');
    $modelId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['model_id'] ?? '');
    $benchType = $input['benchmark_type'] ?? 'full';

    if (!$deviceId) agentos_error('device_id required');
    if (!in_array($benchType, ['latency','throughput','accuracy','memory','power','full'])) {
        agentos_error('Invalid benchmark_type');
    }

    $benchId = agentos_id('bench');
    $pdo = agentos_pdo();

    $pdo->prepare("INSERT INTO agentos_edge_benchmarks 
        (benchmark_id, device_id, model_id, benchmark_type, results, status)
        VALUES (?,?,?,?,'{}','running')")
        ->execute([$benchId, $deviceId, $modelId ?: null, $benchType]);

    agentos_push("device:{$deviceId}", 'edge_benchmark_start', [
        'benchmark_id' => $benchId,
        'model_id' => $modelId ?: null,
        'benchmark_type' => $benchType
    ]);

    agentos_respond(['ok' => true, 'benchmark_id' => $benchId, 'status' => 'running']);
}

function handleBenchmarkResults(array $auth): void {
    $pdo = agentos_pdo();
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['device_id'] ?? '');
    $benchId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['benchmark_id'] ?? '');

    if ($benchId) {
        $stmt = $pdo->prepare("SELECT * FROM agentos_edge_benchmarks WHERE benchmark_id = ?");
        $stmt->execute([$benchId]);
    } elseif ($deviceId) {
        $stmt = $pdo->prepare("SELECT * FROM agentos_edge_benchmarks WHERE device_id = ? ORDER BY started_at DESC LIMIT 10");
        $stmt->execute([$deviceId]);
    } else {
        agentos_error('device_id or benchmark_id required');
    }

    $results = $benchId ? $stmt->fetch() : $stmt->fetchAll();
    if ($results && !is_array($results[0] ?? null)) {
        $results['results'] = json_decode($results['results'], true);
    } else if (is_array($results)) {
        foreach ($results as &$r) {
            $r['results'] = json_decode($r['results'], true);
        }
    }

    agentos_respond(['ok' => true, 'benchmarks' => $results ?: []]);
}

function handleSetPriority(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $modelId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['model_id'] ?? '');
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['device_id'] ?? '');
    $priority = min(100, max(0, intval($input['priority'] ?? 50)));

    if (!$modelId || !$deviceId) agentos_error('model_id and device_id required');

    $pdo = agentos_pdo();
    $pdo->prepare("UPDATE agentos_edge_deployments SET priority = ? WHERE model_id = ? AND device_id = ?")
        ->execute([$priority, $modelId, $deviceId]);

    agentos_push("device:{$deviceId}", 'edge_priority_update', [
        'model_id' => $modelId,
        'priority' => $priority
    ]);

    agentos_respond(['ok' => true, 'priority' => $priority]);
}

function handleResourceUsage(array $auth): void {
    $pdo = agentos_pdo();
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['device_id'] ?? '');
    if (!$deviceId) agentos_error('device_id required');

    $resourceMetrics = ['cpu_usage_percent','gpu_usage_percent','npu_usage_percent',
                        'ram_used_mb','gpu_memory_used_mb','temperature_cpu','temperature_gpu',
                        'power_draw_watts','disk_io_mbps'];

    $usage = [];
    foreach ($resourceMetrics as $metric) {
        $stmt = $pdo->prepare("SELECT metric_value, recorded_at FROM agentos_telemetry_history 
            WHERE device_id = ? AND metric_name = ? ORDER BY recorded_at DESC LIMIT 1");
        $stmt->execute([$deviceId, $metric]);
        $val = $stmt->fetch();
        if ($val) $usage[$metric] = ['value' => floatval($val['metric_value']), 'at' => $val['recorded_at']];
    }

    // Active model count and total inference load
    $activeModels = $pdo->prepare("SELECT model_id, priority, inference_count, avg_latency_ms 
        FROM agentos_edge_deployments WHERE device_id = ? AND status = 'active'");
    $activeModels->execute([$deviceId]);

    agentos_respond([
        'ok' => true,
        'device_id' => $deviceId,
        'resource_usage' => $usage,
        'active_models' => $activeModels->fetchAll()
    ]);
}

function handleBatchDeploy(array $auth): void {
    if (!$auth['is_internal'] && !edgeIsAdmin($auth)) {
        agentos_error('Admin access required', 403);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $modelId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['model_id'] ?? '');
    $deviceIds = $input['device_ids'] ?? [];

    if (!$modelId) agentos_error('model_id required');
    if (empty($deviceIds) || !is_array($deviceIds)) agentos_error('device_ids array required');

    $pdo = agentos_pdo();

    // Verify model
    $stmt = $pdo->prepare("SELECT * FROM agentos_edge_models WHERE model_id = ? AND is_active = 1");
    $stmt->execute([$modelId]);
    $model = $stmt->fetch();
    if (!$model) agentos_error('Model not found');

    $deployments = [];
    foreach ($deviceIds as $rawId) {
        $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $rawId);
        if (!$deviceId) continue;

        $deployId = agentos_id('edep');
        $pdo->prepare("INSERT INTO agentos_edge_deployments 
            (deployment_id, model_id, device_id, status, priority)
            VALUES (?,?,?,'pending',50)
            ON DUPLICATE KEY UPDATE status = 'pending', updated_at = NOW()")
            ->execute([$deployId, $modelId, $deviceId]);

        agentos_push("device:{$deviceId}", 'edge_model_deploy', [
            'deployment_id' => $deployId,
            'model_id' => $modelId,
            'name' => $model['name'],
            'framework' => $model['framework']
        ]);

        $deployments[] = ['device_id' => $deviceId, 'deployment_id' => $deployId];
    }

    agentos_respond(['ok' => true, 'model_id' => $modelId, 'deployments' => $deployments]);
}

function edgeIsAdmin(array $auth): bool {
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
