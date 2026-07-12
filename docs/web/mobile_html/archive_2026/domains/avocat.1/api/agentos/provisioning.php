<?php
/**
 * GSM Alfred OS — Device Provisioning System v1.0
 * Factory setup, certificate enrollment, device identity, activation
 *
 * Endpoints:
 *   POST   ?action=provision          — Provision a new device (factory setup)
 *   POST   ?action=activate           — Activate provisioned device (first boot)
 *   POST   ?action=enroll_cert        — Certificate enrollment (mTLS)
 *   POST   ?action=rotate_token       — Rotate device auth token
 *   POST   ?action=decommission       — Decommission a device
 *   POST   ?action=reactivate         — Reactivate decommissioned device
 *   GET    ?action=device_info        — Get full provisioning info
 *   GET    ?action=provisioning_log   — Provisioning history for a device
 *   POST   ?action=batch_provision    — Batch provision multiple devices
 *   POST   ?action=transfer_ownership — Transfer device to new owner
 *   GET    ?action=certificates       — List device certificates
 *   POST   ?action=revoke_cert        — Revoke a device certificate
 *
 * Security: SHA-256 token hashing, Ed25519 device identity keys,
 *           Certificate pinning, secure random generation
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Internal-Secret, X-Device-Token');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

define('GOSITEME_API', true);
require_once __DIR__ . '/bootstrap.php';
provisionEnsureSchema();

$auth = agentos_auth();
$action = $_GET['action'] ?? 'device_info';

switch ($action) {
    case 'provision':           handleProvision($auth); break;
    case 'activate':            handleActivate($auth); break;
    case 'enroll_cert':         handleEnrollCert($auth); break;
    case 'rotate_token':        handleRotateToken($auth); break;
    case 'decommission':        handleDecommission($auth); break;
    case 'reactivate':          handleReactivate($auth); break;
    case 'device_info':         handleDeviceInfo($auth); break;
    case 'provisioning_log':    handleProvisioningLog($auth); break;
    case 'batch_provision':     handleBatchProvision($auth); break;
    case 'transfer_ownership':  handleTransferOwnership($auth); break;
    case 'certificates':        handleCertificates($auth); break;
    case 'revoke_cert':         handleRevokeCert($auth); break;
    default:                    agentos_error('Unknown action');
}

// ── Schema ─────────────────────────────────────────────────────

function provisionEnsureSchema(): void {
    static $done = false;
    if ($done) return;
    $done = true;

    $pdo = agentos_pdo();
    $r = $pdo->query("SHOW TABLES LIKE 'agentos_provisioning'");
    if ($r->rowCount() > 0) return;

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS agentos_provisioning (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            provision_id    VARCHAR(64) NOT NULL UNIQUE,
            device_id       VARCHAR(128) NOT NULL UNIQUE,
            serial_number   VARCHAR(128) NOT NULL UNIQUE,
            model           VARCHAR(128) NOT NULL,
            hardware_rev    VARCHAR(32),
            firmware_version VARCHAR(32),
            manufacture_date DATE,
            owner_id        INT UNSIGNED COMMENT 'User who owns this device',
            activation_code VARCHAR(64) COMMENT 'One-time activation code (hashed)',
            identity_pubkey TEXT COMMENT 'Device Ed25519 public key',
            provisioning_status ENUM('manufactured','provisioned','activating','active',
                                     'decommissioned','recalled','transferred') NOT NULL DEFAULT 'manufactured',
            provisioned_at  TIMESTAMP NULL,
            activated_at    TIMESTAMP NULL,
            decommissioned_at TIMESTAMP NULL,
            last_seen       TIMESTAMP NULL,
            provisioned_by  INT UNSIGNED,
            metadata        JSON COMMENT 'Additional manufacturing/provisioning data',
            created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_serial (serial_number),
            INDEX idx_model (model),
            INDEX idx_owner (owner_id),
            INDEX idx_status (provisioning_status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS agentos_device_certificates (
            id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            cert_id         VARCHAR(64) NOT NULL UNIQUE,
            device_id       VARCHAR(128) NOT NULL,
            cert_type       ENUM('device_identity','mtls_client','firmware_signing','communication') NOT NULL,
            certificate     TEXT NOT NULL COMMENT 'PEM-encoded certificate',
            public_key      TEXT NOT NULL COMMENT 'PEM-encoded public key',
            fingerprint     VARCHAR(128) NOT NULL COMMENT 'SHA-256 fingerprint',
            serial_number   VARCHAR(64),
            issuer          VARCHAR(256) DEFAULT 'GoSiteMe CA',
            valid_from      TIMESTAMP NOT NULL,
            valid_until     TIMESTAMP NOT NULL,
            is_revoked      TINYINT(1) NOT NULL DEFAULT 0,
            revoked_at      TIMESTAMP NULL,
            revoke_reason   TEXT,
            created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_device (device_id),
            INDEX idx_type (cert_type),
            INDEX idx_fingerprint (fingerprint),
            INDEX idx_valid (valid_until),
            INDEX idx_revoked (is_revoked)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS agentos_provisioning_log (
            id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            log_id          VARCHAR(64) NOT NULL UNIQUE,
            device_id       VARCHAR(128) NOT NULL,
            event_type      ENUM('manufactured','provisioned','activated','token_rotated',
                                'cert_enrolled','cert_revoked','decommissioned','reactivated',
                                'ownership_transferred','firmware_flashed','factory_reset') NOT NULL,
            details         JSON,
            performed_by    INT UNSIGNED,
            ip_address      VARCHAR(45),
            created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_device (device_id),
            INDEX idx_type (event_type),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    error_log("[AGENTOS-PROVISION] Schema auto-migrated");
}

// ── Handlers ───────────────────────────────────────────────────

function handleProvision(array $auth): void {
    if (!$auth['is_internal'] && !provIsAdmin($auth)) {
        agentos_error('Admin/factory access required', 403);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) agentos_error('Invalid JSON body');

    $serialNumber = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['serial_number'] ?? '');
    $model = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['model'] ?? '');

    if (!$serialNumber) agentos_error('serial_number required');
    if (!$model) agentos_error('model required');

    $pdo = agentos_pdo();

    // Check serial uniqueness
    $chk = $pdo->prepare("SELECT id FROM agentos_provisioning WHERE serial_number = ?");
    $chk->execute([$serialNumber]);
    if ($chk->rowCount() > 0) agentos_error('Serial number already provisioned');

    // Generate device identity
    $deviceId = agentos_id('dev');
    $provisionId = agentos_id('prov');

    // Generate auth token and activation code
    $authToken = bin2hex(random_bytes(32)); // 256-bit token
    $activationCode = strtoupper(substr(bin2hex(random_bytes(8)), 0, 12)); // 12-char activation code
    $authTokenHash = hash('sha256', $authToken);
    $activationHash = hash('sha256', $activationCode);

    // Register device in main device table
    $pdo->prepare("INSERT INTO agentos_devices 
        (device_id, name, device_type, protocol, status, auth_token_hash, safety_config)
        VALUES (?, ?, 'robot', 'mqtt', 'provisioned', ?, ?)")
        ->execute([$deviceId, "Alfred {$model} - {$serialNumber}", $authTokenHash,
                   json_encode(['provisioned' => true, 'model' => $model])]);

    // Create provisioning record
    $pdo->prepare("INSERT INTO agentos_provisioning 
        (provision_id, device_id, serial_number, model, hardware_rev, firmware_version,
         manufacture_date, activation_code, identity_pubkey, provisioning_status,
         provisioned_at, provisioned_by, metadata)
        VALUES (?,?,?,?,?,?,?,?,?,?,NOW(),?,?)")
        ->execute([
            $provisionId, $deviceId, $serialNumber, $model,
            $input['hardware_rev'] ?? null,
            $input['firmware_version'] ?? null,
            $input['manufacture_date'] ?? date('Y-m-d'),
            $activationHash,
            $input['identity_pubkey'] ?? null,
            'provisioned',
            $auth['user_id'],
            json_encode($input['metadata'] ?? [])
        ]);

    // Log provisioning event
    provisionLog($deviceId, 'provisioned', $auth['user_id'], [
        'serial_number' => $serialNumber,
        'model' => $model
    ]);

    agentos_audit([
        'action_type' => 'device_provisioned',
        'user_id' => $auth['user_id'],
        'risk_level' => 'high',
        'status' => 'completed',
        'input' => ['serial_number' => $serialNumber, 'model' => $model],
        'output' => ['device_id' => $deviceId, 'provision_id' => $provisionId]
    ]);

    // Return credentials (only shown ONCE during factory provisioning)
    agentos_respond([
        'ok' => true,
        'provision_id' => $provisionId,
        'device_id' => $deviceId,
        'auth_token' => $authToken,          // SENSITIVE — store securely on device
        'activation_code' => $activationCode, // SENSITIVE — include in packaging
        'message' => 'Store auth_token on device securely. Include activation_code in box.'
    ]);
}

function handleActivate(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $serialNumber = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['serial_number'] ?? '');
    $activationCode = preg_replace('/[^a-zA-Z0-9]/', '', $input['activation_code'] ?? '');
    $ownerId = intval($input['owner_id'] ?? $auth['user_id'] ?? 0);

    if (!$serialNumber) agentos_error('serial_number required');
    if (!$activationCode) agentos_error('activation_code required');

    $pdo = agentos_pdo();

    // Find provisioned device
    $stmt = $pdo->prepare("SELECT * FROM agentos_provisioning 
        WHERE serial_number = ? AND provisioning_status = 'provisioned'");
    $stmt->execute([$serialNumber]);
    $device = $stmt->fetch();

    if (!$device) agentos_error('Device not found or not in provisioned state');

    // Verify activation code (constant-time comparison)
    $codeHash = hash('sha256', strtoupper($activationCode));
    if (!hash_equals($device['activation_code'], $codeHash)) {
        agentos_error('Invalid activation code');
    }

    // Activate device
    $pdo->prepare("UPDATE agentos_provisioning 
        SET provisioning_status = 'active', activated_at = NOW(), owner_id = ?, activation_code = NULL
        WHERE provision_id = ?")
        ->execute([$ownerId, $device['provision_id']]);

    $pdo->prepare("UPDATE agentos_devices SET status = 'active' WHERE device_id = ?")
        ->execute([$device['device_id']]);

    provisionLog($device['device_id'], 'activated', $ownerId, [
        'serial_number' => $serialNumber
    ]);

    agentos_respond([
        'ok' => true,
        'device_id' => $device['device_id'],
        'status' => 'active',
        'model' => $device['model'],
        'message' => 'Device activated successfully'
    ]);
}

function handleEnrollCert(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['device_id'] ?? '');
    $certType = $input['cert_type'] ?? 'device_identity';
    $certificate = trim($input['certificate'] ?? '');
    $publicKey = trim($input['public_key'] ?? '');

    if (!$deviceId) agentos_error('device_id required');
    if (!$certificate || !$publicKey) agentos_error('certificate and public_key required');
    if (!in_array($certType, ['device_identity','mtls_client','firmware_signing','communication'])) {
        agentos_error('Invalid cert_type');
    }

    // Validate PEM format
    if (strpos($certificate, '-----BEGIN CERTIFICATE-----') !== 0) {
        agentos_error('Certificate must be PEM-encoded');
    }

    $fingerprint = hash('sha256', base64_decode(
        str_replace(['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----', "\n", "\r"], '', $certificate)
    ));

    $certId = agentos_id('cert');
    $pdo = agentos_pdo();

    // Revoke any existing cert of same type for this device
    $pdo->prepare("UPDATE agentos_device_certificates SET is_revoked = 1, revoked_at = NOW(), 
        revoke_reason = 'Superseded by new enrollment' 
        WHERE device_id = ? AND cert_type = ? AND is_revoked = 0")
        ->execute([$deviceId, $certType]);

    $validFrom = date('Y-m-d H:i:s');
    $validUntil = date('Y-m-d H:i:s', strtotime('+2 years'));

    $pdo->prepare("INSERT INTO agentos_device_certificates 
        (cert_id, device_id, cert_type, certificate, public_key, fingerprint,
         serial_number, valid_from, valid_until)
        VALUES (?,?,?,?,?,?,?,?,?)")
        ->execute([
            $certId, $deviceId, $certType, $certificate, $publicKey,
            $fingerprint, $input['serial_number'] ?? null, $validFrom, $validUntil
        ]);

    provisionLog($deviceId, 'cert_enrolled', $auth['user_id'], [
        'cert_type' => $certType,
        'fingerprint' => $fingerprint
    ]);

    agentos_respond(['ok' => true, 'cert_id' => $certId, 'fingerprint' => $fingerprint, 'valid_until' => $validUntil]);
}

function handleRotateToken(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['device_id'] ?? '');

    if (!$deviceId) agentos_error('device_id required');

    // Verify current token
    $currentToken = trim($input['current_token'] ?? '');
    if (!$currentToken) agentos_error('current_token required for rotation');

    $pdo = agentos_pdo();
    $stmt = $pdo->prepare("SELECT auth_token_hash FROM agentos_devices WHERE device_id = ?");
    $stmt->execute([$deviceId]);
    $storedHash = $stmt->fetchColumn();

    if (!$storedHash || !hash_equals($storedHash, hash('sha256', $currentToken))) {
        agentos_error('Current token verification failed', 403);
    }

    // Generate new token
    $newToken = bin2hex(random_bytes(32));
    $newHash = hash('sha256', $newToken);

    $pdo->prepare("UPDATE agentos_devices SET auth_token_hash = ? WHERE device_id = ?")
        ->execute([$newHash, $deviceId]);

    provisionLog($deviceId, 'token_rotated', $auth['user_id'], ['rotated_from_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);

    agentos_respond([
        'ok' => true,
        'device_id' => $deviceId,
        'new_token' => $newToken,
        'message' => 'Token rotated. Update device configuration with new token.'
    ]);
}

function handleDecommission(array $auth): void {
    if (!$auth['is_internal'] && !provIsAdmin($auth)) {
        agentos_error('Admin access required', 403);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['device_id'] ?? '');
    $reason = trim($input['reason'] ?? 'Administrative decommission');

    if (!$deviceId) agentos_error('device_id required');

    $pdo = agentos_pdo();

    $pdo->prepare("UPDATE agentos_provisioning SET provisioning_status = 'decommissioned', decommissioned_at = NOW() WHERE device_id = ?")
        ->execute([$deviceId]);

    $pdo->prepare("UPDATE agentos_devices SET status = 'decommissioned' WHERE device_id = ?")
        ->execute([$deviceId]);

    // Revoke all certificates
    $pdo->prepare("UPDATE agentos_device_certificates SET is_revoked = 1, revoked_at = NOW(), revoke_reason = ? WHERE device_id = ? AND is_revoked = 0")
        ->execute(["Device decommissioned: $reason", $deviceId]);

    provisionLog($deviceId, 'decommissioned', $auth['user_id'], ['reason' => $reason]);

    agentos_push("device:{$deviceId}", 'DEVICE_DECOMMISSIONED', ['reason' => $reason]);

    agentos_respond(['ok' => true, 'device_id' => $deviceId, 'status' => 'decommissioned']);
}

function handleReactivate(array $auth): void {
    if (!$auth['is_internal'] && !provIsAdmin($auth)) {
        agentos_error('Admin access required', 403);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['device_id'] ?? '');

    if (!$deviceId) agentos_error('device_id required');

    $pdo = agentos_pdo();
    $pdo->prepare("UPDATE agentos_provisioning SET provisioning_status = 'active' WHERE device_id = ? AND provisioning_status = 'decommissioned'")
        ->execute([$deviceId]);

    $pdo->prepare("UPDATE agentos_devices SET status = 'active' WHERE device_id = ?")
        ->execute([$deviceId]);

    // Generate new token since old one was compromised by decommission
    $newToken = bin2hex(random_bytes(32));
    $pdo->prepare("UPDATE agentos_devices SET auth_token_hash = ? WHERE device_id = ?")
        ->execute([hash('sha256', $newToken), $deviceId]);

    provisionLog($deviceId, 'reactivated', $auth['user_id']);

    agentos_respond(['ok' => true, 'device_id' => $deviceId, 'status' => 'active', 'new_token' => $newToken]);
}

function handleDeviceInfo(array $auth): void {
    $pdo = agentos_pdo();
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['device_id'] ?? '');
    $serial = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['serial_number'] ?? '');

    if (!$deviceId && !$serial) agentos_error('device_id or serial_number required');

    if ($serial) {
        $stmt = $pdo->prepare("SELECT * FROM agentos_provisioning WHERE serial_number = ?");
        $stmt->execute([$serial]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM agentos_provisioning WHERE device_id = ?");
        $stmt->execute([$deviceId]);
    }
    $info = $stmt->fetch();
    if (!$info) agentos_error('Device not found', 404);

    // Don't expose sensitive fields
    unset($info['activation_code']);
    $info['metadata'] = json_decode($info['metadata'], true);

    // Get active certificates
    $certStmt = $pdo->prepare("SELECT cert_id, cert_type, fingerprint, valid_from, valid_until, is_revoked 
        FROM agentos_device_certificates WHERE device_id = ? ORDER BY created_at DESC");
    $certStmt->execute([$info['device_id']]);
    $info['certificates'] = $certStmt->fetchAll();

    agentos_respond(['ok' => true, 'device' => $info]);
}

function handleProvisioningLog(array $auth): void {
    $pdo = agentos_pdo();
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['device_id'] ?? '');
    if (!$deviceId) agentos_error('device_id required');

    $stmt = $pdo->prepare("SELECT log_id, event_type, details, performed_by, created_at 
        FROM agentos_provisioning_log WHERE device_id = ? ORDER BY created_at DESC LIMIT 50");
    $stmt->execute([$deviceId]);

    agentos_respond(['ok' => true, 'device_id' => $deviceId, 'log' => $stmt->fetchAll()]);
}

function handleBatchProvision(array $auth): void {
    if (!$auth['is_internal'] && !provIsAdmin($auth)) {
        agentos_error('Admin/factory access required', 403);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $devices = $input['devices'] ?? [];
    if (empty($devices) || !is_array($devices)) agentos_error('devices array required');
    if (count($devices) > 100) agentos_error('Maximum 100 devices per batch');

    $results = [];
    foreach ($devices as $dev) {
        $serialNumber = preg_replace('/[^a-zA-Z0-9_-]/', '', $dev['serial_number'] ?? '');
        $model = preg_replace('/[^a-zA-Z0-9_-]/', '', $dev['model'] ?? '');

        if (!$serialNumber || !$model) {
            $results[] = ['serial_number' => $serialNumber, 'status' => 'error', 'message' => 'Missing serial or model'];
            continue;
        }

        $pdo = agentos_pdo();
        $chk = $pdo->prepare("SELECT id FROM agentos_provisioning WHERE serial_number = ?");
        $chk->execute([$serialNumber]);
        if ($chk->rowCount() > 0) {
            $results[] = ['serial_number' => $serialNumber, 'status' => 'error', 'message' => 'Already provisioned'];
            continue;
        }

        $deviceId = agentos_id('dev');
        $provisionId = agentos_id('prov');
        $authToken = bin2hex(random_bytes(32));
        $activationCode = strtoupper(substr(bin2hex(random_bytes(8)), 0, 12));

        $pdo->prepare("INSERT INTO agentos_devices 
            (device_id, name, device_type, protocol, status, auth_token_hash, safety_config)
            VALUES (?, ?, 'robot', 'mqtt', 'provisioned', ?, ?)")
            ->execute([$deviceId, "Alfred {$model} - {$serialNumber}", hash('sha256', $authToken),
                       json_encode(['provisioned' => true, 'model' => $model])]);

        $pdo->prepare("INSERT INTO agentos_provisioning 
            (provision_id, device_id, serial_number, model, hardware_rev, 
             activation_code, provisioning_status, provisioned_at, provisioned_by)
            VALUES (?,?,?,?,?,?,'provisioned',NOW(),?)")
            ->execute([$provisionId, $deviceId, $serialNumber, $model,
                       $dev['hardware_rev'] ?? null, hash('sha256', $activationCode), $auth['user_id']]);

        provisionLog($deviceId, 'provisioned', $auth['user_id'], ['serial_number' => $serialNumber, 'batch' => true]);

        $results[] = [
            'serial_number' => $serialNumber,
            'device_id' => $deviceId,
            'auth_token' => $authToken,
            'activation_code' => $activationCode,
            'status' => 'provisioned'
        ];
    }

    agentos_respond(['ok' => true, 'provisioned' => count(array_filter($results, fn($r) => $r['status'] === 'provisioned')), 'results' => $results]);
}

function handleTransferOwnership(array $auth): void {
    if (!$auth['is_internal'] && !provIsAdmin($auth)) {
        agentos_error('Admin access required', 403);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['device_id'] ?? '');
    $newOwnerId = intval($input['new_owner_id'] ?? 0);

    if (!$deviceId || !$newOwnerId) agentos_error('device_id and new_owner_id required');

    $pdo = agentos_pdo();
    $pdo->prepare("UPDATE agentos_provisioning SET owner_id = ?, provisioning_status = 'transferred' WHERE device_id = ?")
        ->execute([$newOwnerId, $deviceId]);

    // Rotate token on ownership transfer
    $newToken = bin2hex(random_bytes(32));
    $pdo->prepare("UPDATE agentos_devices SET auth_token_hash = ? WHERE device_id = ?")
        ->execute([hash('sha256', $newToken), $deviceId]);

    provisionLog($deviceId, 'ownership_transferred', $auth['user_id'], [
        'new_owner_id' => $newOwnerId
    ]);

    agentos_respond(['ok' => true, 'device_id' => $deviceId, 'new_owner_id' => $newOwnerId, 'new_token' => $newToken]);
}

function handleCertificates(array $auth): void {
    $pdo = agentos_pdo();
    $deviceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['device_id'] ?? '');
    if (!$deviceId) agentos_error('device_id required');

    $stmt = $pdo->prepare("SELECT cert_id, cert_type, fingerprint, issuer, 
        valid_from, valid_until, is_revoked, revoked_at, created_at 
        FROM agentos_device_certificates WHERE device_id = ? ORDER BY created_at DESC");
    $stmt->execute([$deviceId]);

    agentos_respond(['ok' => true, 'device_id' => $deviceId, 'certificates' => $stmt->fetchAll()]);
}

function handleRevokeCert(array $auth): void {
    if (!$auth['is_internal'] && !provIsAdmin($auth)) {
        agentos_error('Admin access required', 403);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $certId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['cert_id'] ?? '');
    $reason = trim($input['reason'] ?? 'Administrative revocation');

    if (!$certId) agentos_error('cert_id required');

    $pdo = agentos_pdo();
    $stmt = $pdo->prepare("SELECT device_id FROM agentos_device_certificates WHERE cert_id = ?");
    $stmt->execute([$certId]);
    $deviceId = $stmt->fetchColumn();

    $pdo->prepare("UPDATE agentos_device_certificates SET is_revoked = 1, revoked_at = NOW(), revoke_reason = ? WHERE cert_id = ?")
        ->execute([$reason, $certId]);

    if ($deviceId) {
        provisionLog($deviceId, 'cert_revoked', $auth['user_id'], ['cert_id' => $certId, 'reason' => $reason]);
    }

    agentos_respond(['ok' => true, 'cert_id' => $certId, 'revoked' => true]);
}

// ── Helpers ────────────────────────────────────────────────────

function provisionLog(string $deviceId, string $eventType, $userId, array $details = []): void {
    $pdo = agentos_pdo();
    $pdo->prepare("INSERT INTO agentos_provisioning_log (log_id, device_id, event_type, details, performed_by, ip_address) VALUES (?,?,?,?,?,?)")
        ->execute([agentos_id('plog'), $deviceId, $eventType, json_encode($details), $userId, $_SERVER['REMOTE_ADDR'] ?? null]);
}

function provIsAdmin(array $auth): bool {
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
