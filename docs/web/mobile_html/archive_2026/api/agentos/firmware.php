<?php
/**
 * GSM Alfred OS — Firmware OTA Update System v1.0
 * Over-the-Air firmware management for GoSiteMe Hardware (Alfred Robots)
 *
 * Endpoints:
 *   GET    ?action=versions            — List firmware versions for a device model
 *   GET    ?action=current&device_id=X — Get current firmware version for a device
 *   POST   ?action=upload              — Upload new firmware package (admin only)
 *   POST   ?action=stage               — Stage firmware for rollout
 *   POST   ?action=deploy              — Deploy firmware to device(s)
 *   POST   ?action=rollback            — Rollback device to previous firmware
 *   GET    ?action=status&device_id=X  — Get OTA update status for a device
 *   GET    ?action=rollout             — Get rollout status for a firmware version
 *   POST   ?action=approve             — Approve staged firmware for fleet-wide rollout
 *   POST   ?action=halt                — Emergency halt active rollout
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Internal-Secret, X-Device-Token');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

define('GOSITEME_API', true);
require_once __DIR__ . '/bootstrap.php';
firmwareEnsureSchema();

$auth = agentos_auth();
$action = $_GET['action'] ?? 'versions';

switch ($action) {
    case 'versions':   handleVersions($auth); break;
    case 'current':    handleCurrent($auth); break;
    case 'upload':     handleUpload($auth); break;
    case 'stage':      handleStage($auth); break;
    case 'deploy':     handleDeploy($auth); break;
    case 'rollback':   handleRollback($auth); break;
    case 'status':     handleStatus($auth); break;
    case 'rollout':    handleRollout($auth); break;
    case 'approve':    handleApprove($auth); break;
    case 'halt':       handleHalt($auth); break;
    default:           agentos_error('Unknown action');
}

// ── Schema ─────────────────────────────────────────────────────

function firmwareEnsureSchema(): void {
    static $done = false;
    if ($done) return;
    $done = true;

    $pdo = agentos_pdo();
    $r = $pdo->query("SHOW TABLES LIKE 'agentos_firmware_versions'");
    if ($r->rowCount() > 0) return;

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS agentos_firmware_versions (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            version_id      VARCHAR(64) NOT NULL UNIQUE,
            model           VARCHAR(128) NOT NULL COMMENT 'alfred-v1, alfred-v2, etc.',
            version         VARCHAR(32) NOT NULL,
            channel         ENUM('stable','beta','canary','security') NOT NULL DEFAULT 'stable',
            release_type    ENUM('major','minor','patch','security','hotfix') NOT NULL DEFAULT 'patch',
            description     TEXT,
            changelog       TEXT,
            file_size       BIGINT UNSIGNED NOT NULL DEFAULT 0,
            file_hash_sha256 VARCHAR(64) NOT NULL COMMENT 'SHA-256 of firmware binary',
            signature       TEXT COMMENT 'Ed25519 signature of firmware binary',
            signing_key_id  VARCHAR(64) COMMENT 'Public key ID used for signing',
            min_version     VARCHAR(32) COMMENT 'Minimum current version required to update',
            is_mandatory    TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Safety-critical updates',
            requires_reboot TINYINT(1) NOT NULL DEFAULT 1,
            status          ENUM('draft','staged','canary','rolling','stable','halted','deprecated') NOT NULL DEFAULT 'draft',
            canary_percent  TINYINT UNSIGNED NOT NULL DEFAULT 5 COMMENT 'Percentage for canary rollout',
            rollout_percent TINYINT UNSIGNED NOT NULL DEFAULT 0,
            approved_by     INT UNSIGNED,
            approved_at     TIMESTAMP NULL,
            created_by      INT UNSIGNED,
            created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_model (model),
            INDEX idx_channel (channel),
            INDEX idx_status (status),
            INDEX idx_version (model, version)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS agentos_firmware_deployments (
            id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            deployment_id   VARCHAR(64) NOT NULL UNIQUE,
            device_id       VARCHAR(128) NOT NULL,
            version_id      VARCHAR(64) NOT NULL,
            previous_version VARCHAR(32),
            status          ENUM('pending','downloading','verifying','installing','rebooting',
                                'completed','failed','rolled_back','cancelled') NOT NULL DEFAULT 'pending',
            progress        TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0-100 percent',
            error_message   TEXT,
            started_at      TIMESTAMP NULL,
            completed_at    TIMESTAMP NULL,
            verified_at     TIMESTAMP NULL COMMENT 'Post-install health check passed',
            rollback_reason TEXT,
            created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_device (device_id),
            INDEX idx_version (version_id),
            INDEX idx_status (status),
            INDEX idx_device_time (device_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS agentos_firmware_rollouts (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            rollout_id      VARCHAR(64) NOT NULL UNIQUE,
            version_id      VARCHAR(64) NOT NULL,
            strategy        ENUM('canary','staged','immediate','manual') NOT NULL DEFAULT 'staged',
            target_percent  TINYINT UNSIGNED NOT NULL DEFAULT 100,
            current_percent TINYINT UNSIGNED NOT NULL DEFAULT 0,
            stage_increment TINYINT UNSIGNED NOT NULL DEFAULT 10 COMMENT 'Increase by X% per stage',
            stage_delay_hours INT UNSIGNED NOT NULL DEFAULT 24 COMMENT 'Wait hours between stages',
            max_failure_rate TINYINT UNSIGNED NOT NULL DEFAULT 5 COMMENT 'Auto-halt if failure > X%',
            total_devices   INT UNSIGNED NOT NULL DEFAULT 0,
            updated_devices INT UNSIGNED NOT NULL DEFAULT 0,
            failed_devices  INT UNSIGNED NOT NULL DEFAULT 0,
            status          ENUM('active','paused','completed','halted','cancelled') NOT NULL DEFAULT 'active',
            halted_reason   TEXT,
            started_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_stage_at   TIMESTAMP NULL,
            completed_at    TIMESTAMP NULL,
            INDEX idx_version (version_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    error_log("[AGENTOS-FIRMWARE] Schema auto-migrated");
}

// ── Handlers ───────────────────────────────────────────────────

function handleVersions(array $auth): void {
    $pdo = agentos_pdo();
    $model = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['model'] ?? 'alfred-v1');
    $channel = $_GET['channel'] ?? null;

    $sql = "SELECT version_id, model, version, channel, release_type, description,
                   file_size, is_mandatory, status, rollout_percent, created_at
            FROM agentos_firmware_versions WHERE model = ?";
    $params = [$model];

    if ($channel && in_array($channel, ['stable','beta','canary','security'])) {
        $sql .= " AND channel = ?";
        $params[] = $channel;
    }
    $sql .= " ORDER BY created_at DESC LIMIT 50";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    agentos_respond(['ok' => true, 'versions' => $stmt->fetchAll()]);
}

function handleCurrent(array $auth): void {
    $pdo = agentos_pdo();
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['device_id'] ?? '');
    if (!$deviceId) agentos_error('device_id required');

    // Get latest completed deployment
    $stmt = $pdo->prepare("
        SELECT d.version_id, v.version, v.channel, d.completed_at
        FROM agentos_firmware_deployments d
        JOIN agentos_firmware_versions v ON d.version_id = v.version_id
        WHERE d.device_id = ? AND d.status = 'completed'
        ORDER BY d.completed_at DESC LIMIT 1
    ");
    $stmt->execute([$deviceId]);
    $current = $stmt->fetch();

    // Check for pending updates
    $stmt2 = $pdo->prepare("
        SELECT d.deployment_id, d.version_id, v.version, d.status, d.progress
        FROM agentos_firmware_deployments d
        JOIN agentos_firmware_versions v ON d.version_id = v.version_id
        WHERE d.device_id = ? AND d.status IN ('pending','downloading','verifying','installing','rebooting')
        ORDER BY d.created_at DESC LIMIT 1
    ");
    $stmt2->execute([$deviceId]);
    $pending = $stmt2->fetch();

    agentos_respond([
        'ok' => true,
        'device_id' => $deviceId,
        'current' => $current ?: null,
        'pending_update' => $pending ?: null
    ]);
}

function handleUpload(array $auth): void {
    if (!$auth['is_internal'] && !isAdmin($auth)) {
        agentos_error('Admin access required', 403);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) agentos_error('Invalid JSON body');

    $required = ['model', 'version', 'file_hash_sha256'];
    foreach ($required as $f) {
        if (empty($input[$f])) agentos_error("$f is required");
    }

    $model = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['model']);
    $version = preg_replace('/[^0-9.]/', '', $input['version']);
    if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
        agentos_error('Version must be semver (e.g., 1.2.3)');
    }

    $hash = preg_replace('/[^a-f0-9]/', '', strtolower($input['file_hash_sha256']));
    if (strlen($hash) !== 64) agentos_error('Invalid SHA-256 hash');

    $versionId = agentos_id('fw');
    $pdo = agentos_pdo();

    $stmt = $pdo->prepare("INSERT INTO agentos_firmware_versions 
        (version_id, model, version, channel, release_type, description, changelog,
         file_size, file_hash_sha256, signature, signing_key_id, min_version,
         is_mandatory, requires_reboot, created_by)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([
        $versionId, $model, $version,
        $input['channel'] ?? 'stable',
        $input['release_type'] ?? 'patch',
        $input['description'] ?? null,
        $input['changelog'] ?? null,
        intval($input['file_size'] ?? 0),
        $hash,
        $input['signature'] ?? null,
        $input['signing_key_id'] ?? null,
        $input['min_version'] ?? null,
        intval($input['is_mandatory'] ?? 0),
        intval($input['requires_reboot'] ?? 1),
        $auth['user_id']
    ]);

    agentos_audit([
        'action_type' => 'firmware_upload',
        'user_id' => $auth['user_id'],
        'risk_level' => 'high',
        'status' => 'completed',
        'input' => ['model' => $model, 'version' => $version],
        'output' => ['version_id' => $versionId]
    ]);

    agentos_respond(['ok' => true, 'version_id' => $versionId, 'status' => 'draft']);
}

function handleStage(array $auth): void {
    if (!$auth['is_internal'] && !isAdmin($auth)) {
        agentos_error('Admin access required', 403);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $versionId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['version_id'] ?? '');
    if (!$versionId) agentos_error('version_id required');

    $pdo = agentos_pdo();
    $stmt = $pdo->prepare("UPDATE agentos_firmware_versions SET status = 'staged' WHERE version_id = ? AND status = 'draft'");
    $stmt->execute([$versionId]);

    if ($stmt->rowCount() === 0) agentos_error('Version not found or not in draft status');

    agentos_audit([
        'action_type' => 'firmware_staged',
        'user_id' => $auth['user_id'],
        'risk_level' => 'high',
        'status' => 'completed',
        'input' => ['version_id' => $versionId]
    ]);

    agentos_respond(['ok' => true, 'version_id' => $versionId, 'status' => 'staged']);
}

function handleDeploy(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $versionId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['version_id'] ?? '');
    $deviceIds = $input['device_ids'] ?? [];

    if (!$versionId) agentos_error('version_id required');
    if (empty($deviceIds) || !is_array($deviceIds)) agentos_error('device_ids array required');

    $pdo = agentos_pdo();

    // Verify firmware version exists and is deployable
    $stmt = $pdo->prepare("SELECT * FROM agentos_firmware_versions WHERE version_id = ? AND status IN ('staged','canary','rolling','stable')");
    $stmt->execute([$versionId]);
    $fw = $stmt->fetch();
    if (!$fw) agentos_error('Firmware version not found or not deployable');

    // Get mandatory safety check: signature must exist for production deploys
    if ($fw['channel'] === 'stable' && empty($fw['signature'])) {
        agentos_error('Stable channel firmware must be signed before deployment');
    }

    $deployments = [];
    foreach ($deviceIds as $rawId) {
        $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $rawId);
        if (!$deviceId) continue;

        // Check device exists
        $devStmt = $pdo->prepare("SELECT device_id, status FROM agentos_devices WHERE device_id = ?");
        $devStmt->execute([$deviceId]);
        $dev = $devStmt->fetch();
        if (!$dev) continue;

        // Check no active deployment already running
        $activeStmt = $pdo->prepare("SELECT id FROM agentos_firmware_deployments 
            WHERE device_id = ? AND status IN ('pending','downloading','verifying','installing','rebooting')");
        $activeStmt->execute([$deviceId]);
        if ($activeStmt->rowCount() > 0) continue;

        // Get current version for rollback reference
        $curStmt = $pdo->prepare("
            SELECT v.version FROM agentos_firmware_deployments d
            JOIN agentos_firmware_versions v ON d.version_id = v.version_id
            WHERE d.device_id = ? AND d.status = 'completed'
            ORDER BY d.completed_at DESC LIMIT 1
        ");
        $curStmt->execute([$deviceId]);
        $curVersion = $curStmt->fetchColumn() ?: 'unknown';

        $deployId = agentos_id('dep');
        $stmt2 = $pdo->prepare("INSERT INTO agentos_firmware_deployments 
            (deployment_id, device_id, version_id, previous_version, status, started_at)
            VALUES (?,?,?,?,?,NOW())");
        $stmt2->execute([$deployId, $deviceId, $versionId, $curVersion, 'pending']);

        $deployments[] = ['deployment_id' => $deployId, 'device_id' => $deviceId];

        // Push OTA notification to device via WebSocket
        agentos_push("device:{$deviceId}", 'firmware_update_available', [
            'deployment_id' => $deployId,
            'version_id' => $versionId,
            'version' => $fw['version'],
            'file_hash_sha256' => $fw['file_hash_sha256'],
            'is_mandatory' => (bool)$fw['is_mandatory'],
            'requires_reboot' => (bool)$fw['requires_reboot']
        ]);
    }

    agentos_audit([
        'action_type' => 'firmware_deploy',
        'user_id' => $auth['user_id'],
        'risk_level' => 'critical',
        'status' => 'completed',
        'input' => ['version_id' => $versionId, 'device_count' => count($deployments)],
        'output' => ['deployments' => $deployments]
    ]);

    agentos_respond(['ok' => true, 'deployments' => $deployments]);
}

function handleRollback(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['device_id'] ?? '');
    $reason = trim($input['reason'] ?? 'Manual rollback');

    if (!$deviceId) agentos_error('device_id required');

    $pdo = agentos_pdo();

    // Cancel any active deployment
    $stmt = $pdo->prepare("UPDATE agentos_firmware_deployments 
        SET status = 'cancelled', error_message = ? WHERE device_id = ? AND status IN ('pending','downloading','verifying')");
    $stmt->execute([$reason, $deviceId]);

    // Find previous successful version
    $stmt2 = $pdo->prepare("
        SELECT d.deployment_id, d.version_id, v.version
        FROM agentos_firmware_deployments d
        JOIN agentos_firmware_versions v ON d.version_id = v.version_id
        WHERE d.device_id = ? AND d.status = 'completed'
        ORDER BY d.completed_at DESC LIMIT 1 OFFSET 1
    ");
    $stmt2->execute([$deviceId]);
    $prev = $stmt2->fetch();

    if (!$prev) agentos_error('No previous version available for rollback');

    // Create rollback deployment
    $deployId = agentos_id('rbk');
    $stmt3 = $pdo->prepare("INSERT INTO agentos_firmware_deployments 
        (deployment_id, device_id, version_id, status, rollback_reason, started_at)
        VALUES (?,?,?,'pending',?,NOW())");
    $stmt3->execute([$deployId, $deviceId, $prev['version_id'], $reason]);

    agentos_push("device:{$deviceId}", 'firmware_rollback', [
        'deployment_id' => $deployId,
        'target_version' => $prev['version']
    ]);

    agentos_audit([
        'action_type' => 'firmware_rollback',
        'user_id' => $auth['user_id'],
        'risk_level' => 'critical',
        'status' => 'completed',
        'input' => ['device_id' => $deviceId, 'reason' => $reason],
        'output' => ['rollback_to' => $prev['version']]
    ]);

    agentos_respond(['ok' => true, 'deployment_id' => $deployId, 'rollback_to' => $prev['version']]);
}

function handleStatus(array $auth): void {
    $pdo = agentos_pdo();
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['device_id'] ?? '');
    if (!$deviceId) agentos_error('device_id required');

    $stmt = $pdo->prepare("
        SELECT d.*, v.version, v.channel, v.release_type, v.is_mandatory
        FROM agentos_firmware_deployments d
        JOIN agentos_firmware_versions v ON d.version_id = v.version_id
        WHERE d.device_id = ?
        ORDER BY d.created_at DESC LIMIT 10
    ");
    $stmt->execute([$deviceId]);

    agentos_respond(['ok' => true, 'device_id' => $deviceId, 'deployments' => $stmt->fetchAll()]);
}

function handleRollout(array $auth): void {
    $pdo = agentos_pdo();
    $versionId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['version_id'] ?? '');
    if (!$versionId) agentos_error('version_id required');

    $stmt = $pdo->prepare("SELECT * FROM agentos_firmware_rollouts WHERE version_id = ? ORDER BY started_at DESC LIMIT 1");
    $stmt->execute([$versionId]);
    $rollout = $stmt->fetch();

    // Get deployment stats
    $stats = $pdo->prepare("
        SELECT status, COUNT(*) as count
        FROM agentos_firmware_deployments
        WHERE version_id = ?
        GROUP BY status
    ");
    $stats->execute([$versionId]);

    agentos_respond([
        'ok' => true,
        'rollout' => $rollout ?: null,
        'deployment_stats' => $stats->fetchAll(PDO::FETCH_KEY_PAIR)
    ]);
}

function handleApprove(array $auth): void {
    if (!$auth['is_internal'] && !isAdmin($auth)) {
        agentos_error('Admin access required', 403);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $versionId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['version_id'] ?? '');
    $strategy = $input['strategy'] ?? 'staged';

    if (!$versionId) agentos_error('version_id required');
    if (!in_array($strategy, ['canary', 'staged', 'immediate', 'manual'])) {
        agentos_error('Invalid rollout strategy');
    }

    $pdo = agentos_pdo();

    $stmt = $pdo->prepare("UPDATE agentos_firmware_versions 
        SET status = 'rolling', approved_by = ?, approved_at = NOW()
        WHERE version_id = ? AND status = 'staged'");
    $stmt->execute([$auth['user_id'], $versionId]);

    if ($stmt->rowCount() === 0) agentos_error('Version not found or not staged');

    // Count target devices
    $devStmt = $pdo->prepare("SELECT model FROM agentos_firmware_versions WHERE version_id = ?");
    $devStmt->execute([$versionId]);
    $model = $devStmt->fetchColumn();

    // Create rollout record
    $rolloutId = agentos_id('rol');
    $pdo->prepare("INSERT INTO agentos_firmware_rollouts 
        (rollout_id, version_id, strategy, stage_increment, stage_delay_hours, max_failure_rate)
        VALUES (?,?,?,?,?,?)")->execute([
        $rolloutId, $versionId, $strategy,
        intval($input['stage_increment'] ?? 10),
        intval($input['stage_delay_hours'] ?? 24),
        intval($input['max_failure_rate'] ?? 5)
    ]);

    agentos_audit([
        'action_type' => 'firmware_approved',
        'user_id' => $auth['user_id'],
        'risk_level' => 'critical',
        'status' => 'completed',
        'input' => ['version_id' => $versionId, 'strategy' => $strategy]
    ]);

    agentos_respond(['ok' => true, 'rollout_id' => $rolloutId, 'strategy' => $strategy]);
}

function handleHalt(array $auth): void {
    if (!$auth['is_internal'] && !isAdmin($auth)) {
        agentos_error('Admin access required', 403);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $versionId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['version_id'] ?? '');
    $reason = trim($input['reason'] ?? 'Emergency halt');

    if (!$versionId) agentos_error('version_id required');

    $pdo = agentos_pdo();

    // Halt the firmware version
    $pdo->prepare("UPDATE agentos_firmware_versions SET status = 'halted' WHERE version_id = ?")
        ->execute([$versionId]);

    // Halt active rollout
    $pdo->prepare("UPDATE agentos_firmware_rollouts 
        SET status = 'halted', halted_reason = ? WHERE version_id = ? AND status = 'active'")
        ->execute([$reason, $versionId]);

    // Cancel pending deployments
    $pdo->prepare("UPDATE agentos_firmware_deployments 
        SET status = 'cancelled', error_message = ?
        WHERE version_id = ? AND status IN ('pending','downloading')")
        ->execute(["Rollout halted: $reason", $versionId]);

    agentos_audit([
        'action_type' => 'firmware_halt',
        'user_id' => $auth['user_id'],
        'risk_level' => 'critical',
        'status' => 'completed',
        'input' => ['version_id' => $versionId, 'reason' => $reason]
    ]);

    agentos_respond(['ok' => true, 'halted' => true, 'reason' => $reason]);
}

// ── Helpers ────────────────────────────────────────────────────

function isAdmin(array $auth): bool {
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
