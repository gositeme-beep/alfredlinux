<?php
/**
 * GSM Alfred OS — World State API v1.0
 * Live environment state tracking with drift detection
 *
 * Endpoints:
 *   GET    ?action=get                — Get current world state
 *   POST   ?action=update             — Update world state
 *   GET    ?action=entities           — List world entities
 *   POST   ?action=spawn              — Spawn a new entity
 *   POST   ?action=despawn            — Remove an entity
 *   POST   ?action=entity_update      — Update entity state
 *   GET    ?action=drifts             — Get state anomalies/drifts
 *   POST   ?action=snapshot           — Take a state snapshot
 *   GET    ?action=diff               — Compare two snapshots
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Internal-Secret');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

define('GOSITEME_API', true);
require_once __DIR__ . '/bootstrap.php';
agentos_ensure_schema();

$auth = agentos_auth();
$action = $_GET['action'] ?? 'get';

switch ($action) {
    case 'get':            handleGet($auth); break;
    case 'update':         handleUpdate($auth); break;
    case 'entities':       handleEntities($auth); break;
    case 'spawn':          handleSpawn($auth); break;
    case 'despawn':        handleDespawn($auth); break;
    case 'entity_update':  handleEntityUpdate($auth); break;
    case 'drifts':         handleDrifts($auth); break;
    case 'snapshot':       handleSnapshot($auth); break;
    case 'diff':           handleDiff($auth); break;
    default:               agentos_error('Unknown action');
}

// ═══════════════════════════════════════════════════════════════
// GET — Current world state
// ═══════════════════════════════════════════════════════════════
function handleGet(array $auth): void {
    $worldId = mb_substr($_GET['world_id'] ?? 'default', 0, 100);
    $pdo = agentos_pdo();

    // State vars
    $stmt = $pdo->prepare("SELECT state_key, state_value, state_type,
        drift_detected, expected_value, observed_by, observed_at
        FROM agentos_world_state WHERE world_id=? ORDER BY state_key");
    $stmt->execute([$worldId]);
    $state = [];
    foreach ($stmt->fetchAll() as $row) {
        $row['state_value'] = json_decode($row['state_value'], true);
        $row['expected_value'] = json_decode($row['expected_value'] ?? 'null', true);
        $state[$row['state_key']] = $row;
    }

    // Entity summary
    $stmt = $pdo->prepare("SELECT entity_type, COUNT(*) as count, 
        SUM(status='online') as online, SUM(status='offline') as offline
        FROM agentos_world_entities WHERE world_id=? GROUP BY entity_type");
    $stmt->execute([$worldId]);
    $entitySummary = $stmt->fetchAll();

    // Drift count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM agentos_world_state WHERE world_id=? AND drift_detected=1");
    $stmt->execute([$worldId]);
    $driftCount = (int)$stmt->fetchColumn();

    agentos_respond([
        'ok' => true,
        'world_id' => $worldId,
        'state_count' => count($state),
        'drift_count' => $driftCount,
        'state' => $state,
        'entity_summary' => $entitySummary,
    ]);
}

// ═══════════════════════════════════════════════════════════════
// UPDATE — Update world state variables
// ═══════════════════════════════════════════════════════════════
function handleUpdate(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || empty($input['updates'])) agentos_error('updates array required');

    $worldId = mb_substr($input['world_id'] ?? 'default', 0, 100);
    $source = mb_substr($input['source'] ?? 'api', 0, 50);
    $pdo = agentos_pdo();
    $updated = 0;
    $drifts = 0;

    $stmtGet = $pdo->prepare("SELECT state_value, expected_value FROM agentos_world_state 
        WHERE world_id=? AND state_key=?");
    $stmtUpsert = $pdo->prepare("INSERT INTO agentos_world_state 
        (world_id, state_key, state_value, state_type, expected_value, observed_by, drift_detected)
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            expected_value = state_value,
            state_value = VALUES(state_value),
            state_type = VALUES(state_type),
            observed_by = VALUES(observed_by),
            drift_detected = VALUES(drift_detected)");

    foreach ($input['updates'] as $upd) {
        $key = mb_substr($upd['key'] ?? '', 0, 200);
        if (!$key) continue;

        $newValue = json_encode($upd['value']);
        $stateType = mb_substr($upd['type'] ?? 'environment', 0, 64);

        // Check for drift
        $stmtGet->execute([$worldId, $key]);
        $existing = $stmtGet->fetch();
        $expectedValue = $existing ? $existing['state_value'] : null;
        $driftDetected = false;

        if ($existing && $existing['state_value'] !== $newValue) {
            $oldDecoded = json_decode($existing['state_value'], true);
            $newDecoded = $upd['value'];
            if (is_numeric($oldDecoded) && is_numeric($newDecoded)) {
                $pctChange = abs($oldDecoded) > 0 ? abs($newDecoded - $oldDecoded) / abs($oldDecoded) : 1;
                $driftDetected = $pctChange > 0.5;
            } else {
                $driftDetected = ($upd['unexpected'] ?? false);
            }
        }

        if ($driftDetected) $drifts++;

        $stmtUpsert->execute([$worldId, $key, $newValue, $stateType, $expectedValue, $source, $driftDetected ? 1 : 0]);
        $updated++;
    }

    // Push drift notifications
    if ($drifts > 0) {
        agentos_push("agentos:world:{$worldId}", 'state_drift', [
            'world_id' => $worldId, 'drifts' => $drifts,
        ]);
    }

    agentos_respond(['ok' => true, 'world_id' => $worldId, 'updated' => $updated, 'drifts_detected' => $drifts]);
}

// ═══════════════════════════════════════════════════════════════
// ENTITIES — List world entities
// ═══════════════════════════════════════════════════════════════
function handleEntities(array $auth): void {
    $worldId = mb_substr($_GET['world_id'] ?? 'default', 0, 100);
    $pdo = agentos_pdo();

    $where = ['world_id=?'];
    $params = [$worldId];

    if (isset($_GET['type'])) {
        $where[] = 'entity_type=?';
        $params[] = $_GET['type'];
    }
    if (isset($_GET['status'])) {
        $where[] = 'status=?';
        $params[] = $_GET['status'];
    }

    $stmt = $pdo->prepare("SELECT entity_id, entity_type, display_name, status, 
        properties, capabilities, twin_data, last_heartbeat, owner_id
        FROM agentos_world_entities WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC");
    $stmt->execute($params);
    $entities = $stmt->fetchAll();

    foreach ($entities as &$e) {
        $e['properties'] = json_decode($e['properties'] ?? '{}', true);
        $e['capabilities'] = json_decode($e['capabilities'] ?? '[]', true);
        $e['twin_data'] = json_decode($e['twin_data'] ?? 'null', true);
    }

    agentos_respond(['ok' => true, 'world_id' => $worldId, 'count' => count($entities), 'entities' => $entities]);
}

// ═══════════════════════════════════════════════════════════════
// SPAWN — Create a new world entity
// ═══════════════════════════════════════════════════════════════
function handleSpawn(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || empty($input['entity_type'])) agentos_error('entity_type required');

    $pdo = agentos_pdo();
    $entityId = $input['entity_id'] ?? agentos_id('ent');
    $entityId = preg_replace('/[^a-zA-Z0-9_-]/', '', mb_substr($entityId, 0, 100));
    $worldId = mb_substr($input['world_id'] ?? 'default', 0, 100);

    $validTypes = ['robot', 'device', 'sensor', 'avatar', 'object', 'zone', 'service'];
    $entityType = in_array($input['entity_type'], $validTypes) ? $input['entity_type'] : 'object';

    $stmt = $pdo->prepare("INSERT INTO agentos_world_entities 
        (world_id, entity_id, entity_type, display_name, status,
         properties, capabilities, twin_data, last_heartbeat, owner_id)
        VALUES (?, ?, ?, ?, 'online', ?, ?, ?, NOW(), ?)");
    $stmt->execute([
        $worldId, $entityId, $entityType,
        mb_substr($input['display_name'] ?? $entityId, 0, 200),
        json_encode($input['properties'] ?? []),
        json_encode($input['capabilities'] ?? []),
        json_encode($input['twin_data'] ?? null),
        $input['owner_id'] ?? null,
    ]);

    agentos_push("agentos:world:{$worldId}", 'entity_spawned', [
        'entity_id' => $entityId, 'entity_type' => $entityType,
    ]);

    agentos_audit([
        'agent_id' => 'system', 'user_id' => $auth['user_id'],
        'action_type' => 'entity_spawned', 'status' => 'completed',
        'input' => ['entity_id' => $entityId, 'entity_type' => $entityType, 'world_id' => $worldId],
    ]);

    agentos_respond(['ok' => true, 'entity_id' => $entityId, 'world_id' => $worldId], 201);
}

// ═══════════════════════════════════════════════════════════════
// DESPAWN — Remove entity from world
// ═══════════════════════════════════════════════════════════════
function handleDespawn(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $entityId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['entity_id'] ?? '');
    $worldId = mb_substr($input['world_id'] ?? 'default', 0, 100);

    if (!$entityId) agentos_error('entity_id required');

    $pdo = agentos_pdo();
    $stmt = $pdo->prepare("DELETE FROM agentos_world_entities WHERE world_id=? AND entity_id=?");
    $stmt->execute([$worldId, $entityId]);

    agentos_push("agentos:world:{$worldId}", 'entity_despawned', ['entity_id' => $entityId]);

    agentos_respond(['ok' => true, 'entity_id' => $entityId, 'removed' => $stmt->rowCount()]);
}

// ═══════════════════════════════════════════════════════════════
// ENTITY UPDATE — Modify an entity's state
// ═══════════════════════════════════════════════════════════════
function handleEntityUpdate(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $entityId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['entity_id'] ?? '');
    $worldId = mb_substr($input['world_id'] ?? 'default', 0, 100);

    if (!$entityId) agentos_error('entity_id required');

    $pdo = agentos_pdo();
    $sets = [];
    $params = [];

    $allowed = ['display_name', 'status'];
    foreach ($allowed as $field) {
        if (isset($input[$field])) {
            $sets[] = "{$field}=?";
            $params[] = $input[$field];
        }
    }
    foreach (['properties', 'capabilities', 'twin_data'] as $jsonField) {
        if (isset($input[$jsonField])) {
            $sets[] = "{$jsonField}=?";
            $params[] = json_encode($input[$jsonField]);
        }
    }

    $sets[] = 'last_heartbeat=NOW()';
    if (empty($sets)) agentos_error('No fields to update');

    $params[] = $worldId;
    $params[] = $entityId;

    $sql = "UPDATE agentos_world_entities SET " . implode(', ', $sets) . " WHERE world_id=? AND entity_id=?";
    $pdo->prepare($sql)->execute($params);

    agentos_respond(['ok' => true, 'entity_id' => $entityId, 'updated' => true]);
}

// ═══════════════════════════════════════════════════════════════
// DRIFTS — Get state anomalies
// ═══════════════════════════════════════════════════════════════
function handleDrifts(array $auth): void {
    $worldId = mb_substr($_GET['world_id'] ?? 'default', 0, 100);
    $pdo = agentos_pdo();

    $stmt = $pdo->prepare("SELECT state_key, state_value, expected_value, state_type, observed_by, observed_at
        FROM agentos_world_state WHERE world_id=? AND drift_detected=1 ORDER BY observed_at DESC");
    $stmt->execute([$worldId]);
    $drifts = $stmt->fetchAll();

    foreach ($drifts as &$d) {
        $d['state_value'] = json_decode($d['state_value'], true);
        $d['expected_value'] = json_decode($d['expected_value'] ?? 'null', true);
    }

    // Also check for stale entities (no heartbeat > 5 min)
    $stmt = $pdo->prepare("SELECT entity_id, entity_type, display_name, last_heartbeat
        FROM agentos_world_entities WHERE world_id=? AND status='online' 
        AND last_heartbeat < DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
    $stmt->execute([$worldId]);
    $staleEntities = $stmt->fetchAll();

    agentos_respond([
        'ok' => true,
        'world_id' => $worldId,
        'state_drifts' => $drifts,
        'stale_entities' => $staleEntities,
    ]);
}

// ═══════════════════════════════════════════════════════════════
// SNAPSHOT & DIFF — State snapshots for comparison
// ═══════════════════════════════════════════════════════════════
function handleSnapshot(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $worldId = mb_substr(($input['world_id'] ?? 'default'), 0, 100);
    $pdo = agentos_pdo();

    // Capture full state
    $stmt = $pdo->prepare("SELECT state_key, state_value FROM agentos_world_state WHERE world_id=?");
    $stmt->execute([$worldId]);
    $stateSnapshot = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Capture entities
    $stmt = $pdo->prepare("SELECT entity_id, entity_type, status, position FROM agentos_world_entities WHERE world_id=?");
    $stmt->execute([$worldId]);
    $entitySnapshot = $stmt->fetchAll();

    $snapshotId = agentos_id('snap');
    $snapshot = [
        'snapshot_id' => $snapshotId,
        'world_id' => $worldId,
        'timestamp' => date('c'),
        'state' => $stateSnapshot,
        'entities' => $entitySnapshot,
    ];

    // Store in Redis with 1-hour TTL
    agentos_cache_set("snapshot:{$snapshotId}", $snapshot, 3600);

    agentos_respond(['ok' => true, 'snapshot_id' => $snapshotId, 'state_keys' => count($stateSnapshot), 'entities' => count($entitySnapshot)]);
}

function handleDiff(array $auth): void {
    $snapA = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['a'] ?? '');
    $snapB = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['b'] ?? '');

    if (!$snapA || !$snapB) agentos_error('Both snapshot IDs (a, b) required');

    $a = agentos_cache_get("snapshot:{$snapA}");
    $b = agentos_cache_get("snapshot:{$snapB}");

    if (!$a || !$b) agentos_error('One or both snapshots not found (may have expired)');

    $diff = ['added' => [], 'removed' => [], 'changed' => []];

    $aState = $a['state'] ?? [];
    $bState = $b['state'] ?? [];

    foreach ($bState as $key => $val) {
        if (!isset($aState[$key])) {
            $diff['added'][] = ['key' => $key, 'value' => $val];
        } elseif ($aState[$key] !== $val) {
            $diff['changed'][] = ['key' => $key, 'from' => $aState[$key], 'to' => $val];
        }
    }
    foreach ($aState as $key => $val) {
        if (!isset($bState[$key])) {
            $diff['removed'][] = ['key' => $key, 'value' => $val];
        }
    }

    agentos_respond([
        'ok' => true,
        'from' => $snapA, 'to' => $snapB,
        'added' => count($diff['added']),
        'removed' => count($diff['removed']),
        'changed' => count($diff['changed']),
        'diff' => $diff,
    ]);
}
