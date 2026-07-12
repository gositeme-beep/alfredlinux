<?php
/**
 * Alfred API v1 — Fleets Resource Handler
 *
 * Endpoints:
 *   POST /fleets              — Create fleet
 *   GET  /fleets              — List user's fleets
 *   GET  /fleets/{id}         — Get fleet details
 *   GET  /fleets/{id}/status  — Fleet status + metrics
 *   POST /fleets/{id}/deploy  — Deploy fleet
 *   POST /fleets/{id}/pause   — Pause fleet
 *   DELETE /fleets/{id}       — Delete (retire) fleet
 *
 * @version 1.0.0
 * @since   2026-03-04
 */

declare(strict_types=1);

require_once __DIR__ . '/../helpers.php';

/**
 * Handle fleets requests
 */
function handleFleetsRequest(array $ctx): void
{
    $method = $ctx['method'];
    $route  = $ctx['route'];
    $id     = $route['id'] ?? null;
    $sub    = $route['sub'] ?? null;

    // ── POST /fleets ──
    if ($method === 'POST' && $id === null) {
        createFleetV1($ctx);
    }
    // ── GET /fleets ──
    elseif ($method === 'GET' && $id === null) {
        listFleetsV1($ctx);
    }
    // ── GET /fleets/{id} ──
    elseif ($method === 'GET' && $id !== null && $sub === null) {
        getFleetV1($ctx, (int) $id);
    }
    // ── GET /fleets/{id}/status ──
    elseif ($method === 'GET' && $id !== null && $sub === 'status') {
        getFleetStatusV1($ctx, (int) $id);
    }
    // ── POST /fleets/{id}/deploy ──
    elseif ($method === 'POST' && $id !== null && $sub === 'deploy') {
        deployFleetV1($ctx, (int) $id);
    }
    // ── POST /fleets/{id}/pause ──
    elseif ($method === 'POST' && $id !== null && $sub === 'pause') {
        pauseFleetV1($ctx, (int) $id);
    }
    // ── DELETE /fleets/{id} ──
    elseif ($method === 'DELETE' && $id !== null && $sub === null) {
        deleteFleetV1($ctx, (int) $id);
    }
    else {
        respondError("Method {$method} not allowed on /fleets" . ($id ? "/{$id}" : '') . ($sub ? "/{$sub}" : ''), 405, 'method_not_allowed');
    }
}

// ─── Implementation ─────────────────────────────────────────────────────────

function createFleetV1(array $ctx): void
{
    requireScopes($ctx['auth'], 'fleets:write');

    $body = validateRequired($ctx['body'], ['name', 'objective']);

    $name      = sanitizeInput($body['name'], 100);
    $objective = sanitizeInput($body['objective'], 2000);
    $strategy  = sanitizeInput($ctx['body']['strategy'] ?? 'parallel', 30);
    $kpis      = $ctx['body']['kpis'] ?? [];

    $validStrategies = ['parallel', 'pipeline', 'consensus', 'competition'];
    if (!in_array($strategy, $validStrategies, true)) {
        respondError('Invalid strategy. Valid: ' . implode(', ', $validStrategies), 400, 'validation_error');
    }

    $db = getDB();
    if (!$db) {
        respondError('Database unavailable', 503, 'service_unavailable');
    }

    $userId = $ctx['auth']['user_id'];

    try {
        $stmt = $db->prepare("
            INSERT INTO alfred_fleets (user_id, fleet_name, objective, strategy, results, created_at)
            VALUES (:uid, :name, :obj, :strat, :results, NOW())
        ");
        $stmt->execute([
            ':uid'     => $userId,
            ':name'    => $name,
            ':obj'     => $objective,
            ':strat'   => $strategy,
            ':results' => json_encode(['kpis' => $kpis]),
        ]);
        $fleetId = (int) $db->lastInsertId();

        logUsage($userId, 'fleets', 1, 'POST /fleets');
        dispatchWebhook($userId, 'fleet.created', ['fleet_id' => $fleetId, 'name' => $name]);

        respond([
            'data' => [
                'id'          => $fleetId,
                'name'        => $name,
                'objective'   => $objective,
                'strategy'    => $strategy,
                'status'      => 'idle',
                'agent_count' => 0,
                'kpis'        => $kpis,
            ],
        ], 201);
    } catch (\PDOException $e) {
        error_log('API v1 fleets: create failed: ' . $e->getMessage());
        respondError('Failed to create fleet', 500, 'internal_error');
    }
}

function listFleetsV1(array $ctx): void
{
    requireScopes($ctx['auth'], 'fleets:read');

    $db = getDB();
    if (!$db) {
        respondError('Database unavailable', 503, 'service_unavailable');
    }

    $userId = $ctx['auth']['user_id'];
    $pg     = getPagination();
    $status = sanitizeInput($_GET['status'] ?? '', 20);

    try {
        $where  = 'user_id = :uid';
        $params = [':uid' => $userId];

        if ($status !== '' && in_array($status, ['idle', 'running', 'paused', 'completed', 'failed'])) {
            $where .= ' AND status = :status';
            $params[':status'] = $status;
        }

        // Count
        $countStmt = $db->prepare("SELECT COUNT(*) FROM alfred_fleets WHERE {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Fetch
        $sql = "SELECT * FROM alfred_fleets WHERE {$where} ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $pg['per_page'], \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $pg['offset'], \PDO::PARAM_INT);
        $stmt->execute();
        $fleets = $stmt->fetchAll();

        $fleets = array_map(
            fn($r) => formatRow($r, ['id', 'user_id', 'agent_count', 'progress_percent'], ['results']),
            $fleets
        );

        logUsage($userId, 'fleets', 1, 'GET /fleets');

        respond(paginatedResponse($fleets, $total, $pg['page'], $pg['per_page']));
    } catch (\PDOException $e) {
        error_log('API v1 fleets: list failed: ' . $e->getMessage());
        respondError('Failed to list fleets', 500, 'internal_error');
    }
}

function getFleetV1(array $ctx, int $id): void
{
    requireScopes($ctx['auth'], 'fleets:read');

    $db = getDB();
    if (!$db) {
        respondError('Database unavailable', 503, 'service_unavailable');
    }

    $fleet = getOwnedResource($db, 'alfred_fleets', $id, $ctx['auth']['user_id']);
    if (!$fleet) {
        respondError('Fleet not found or access denied', 404, 'fleet_not_found');
    }

    // Get agents
    $stmt = $db->prepare("SELECT * FROM alfred_fleet_agents WHERE fleet_id = ? ORDER BY id ASC");
    $stmt->execute([$id]);
    $agents = $stmt->fetchAll();
    $agents = array_map(fn($r) => formatRow($r, ['id', 'fleet_id'], ['result']), $agents);

    $fleet = formatRow($fleet, ['id', 'user_id', 'agent_count', 'progress_percent'], ['results']);
    $fleet['agents'] = $agents;

    logUsage($ctx['auth']['user_id'], 'fleets', 1, "GET /fleets/{$id}");

    respond(['data' => $fleet]);
}

function getFleetStatusV1(array $ctx, int $id): void
{
    requireScopes($ctx['auth'], 'fleets:read');

    $db = getDB();
    if (!$db) {
        respondError('Database unavailable', 503, 'service_unavailable');
    }

    $fleet = getOwnedResource($db, 'alfred_fleets', $id, $ctx['auth']['user_id']);
    if (!$fleet) {
        respondError('Fleet not found or access denied', 404, 'fleet_not_found');
    }

    // Agent status breakdown
    $stmt = $db->prepare("SELECT status, COUNT(*) as count FROM alfred_fleet_agents WHERE fleet_id = ? GROUP BY status");
    $stmt->execute([$id]);
    $breakdown = [];
    foreach ($stmt->fetchAll() as $row) {
        $breakdown[$row['status']] = (int) $row['count'];
    }

    $fleet = formatRow($fleet, ['id', 'user_id', 'agent_count', 'progress_percent'], ['results']);

    logUsage($ctx['auth']['user_id'], 'fleets', 1, "GET /fleets/{$id}/status");

    respond([
        'data' => [
            'fleet'                => $fleet,
            'agent_status_breakdown' => $breakdown,
            'total_agents'         => array_sum($breakdown),
        ],
    ]);
}

function deployFleetV1(array $ctx, int $id): void
{
    requireScopes($ctx['auth'], 'fleets:write');

    $db = getDB();
    if (!$db) {
        respondError('Database unavailable', 503, 'service_unavailable');
    }

    $fleet = getOwnedResource($db, 'alfred_fleets', $id, $ctx['auth']['user_id']);
    if (!$fleet) {
        respondError('Fleet not found or access denied', 404, 'fleet_not_found');
    }

    if (!in_array($fleet['status'], ['idle', 'paused'])) {
        respondError("Cannot deploy fleet with status '{$fleet['status']}'. Must be idle or paused.", 400, 'invalid_state');
    }

    try {
        // Check agent count
        $stmt = $db->prepare("SELECT COUNT(*) FROM alfred_fleet_agents WHERE fleet_id = ?");
        $stmt->execute([$id]);
        $agentCount = (int) $stmt->fetchColumn();

        if ($agentCount === 0) {
            respondError('Cannot deploy fleet with no agents', 400, 'no_agents');
        }

        $db->prepare("UPDATE alfred_fleets SET status = 'running', updated_at = NOW() WHERE id = ?")->execute([$id]);
        $db->prepare("UPDATE alfred_fleet_agents SET status = 'running', started_at = NOW() WHERE fleet_id = ? AND status = 'queued'")->execute([$id]);

        logUsage($ctx['auth']['user_id'], 'fleets', 1, "POST /fleets/{$id}/deploy");
        dispatchWebhook($ctx['auth']['user_id'], 'fleet.deployed', ['fleet_id' => $id, 'agents' => $agentCount]);

        respond([
            'data' => [
                'fleet_id'        => $id,
                'status'          => 'running',
                'agents_deployed' => $agentCount,
                'message'         => "Fleet '{$fleet['fleet_name']}' deployed with {$agentCount} agents",
            ],
        ]);
    } catch (\PDOException $e) {
        error_log('API v1 fleets: deploy failed: ' . $e->getMessage());
        respondError('Failed to deploy fleet', 500, 'internal_error');
    }
}

function pauseFleetV1(array $ctx, int $id): void
{
    requireScopes($ctx['auth'], 'fleets:write');

    $db = getDB();
    if (!$db) {
        respondError('Database unavailable', 503, 'service_unavailable');
    }

    $fleet = getOwnedResource($db, 'alfred_fleets', $id, $ctx['auth']['user_id']);
    if (!$fleet) {
        respondError('Fleet not found or access denied', 404, 'fleet_not_found');
    }

    if ($fleet['status'] !== 'running') {
        respondError("Cannot pause fleet with status '{$fleet['status']}'. Must be running.", 400, 'invalid_state');
    }

    try {
        $db->prepare("UPDATE alfred_fleets SET status = 'paused', updated_at = NOW() WHERE id = ?")->execute([$id]);
        $db->prepare("UPDATE alfred_fleet_agents SET status = 'queued' WHERE fleet_id = ? AND status = 'running'")->execute([$id]);

        logUsage($ctx['auth']['user_id'], 'fleets', 1, "POST /fleets/{$id}/pause");
        dispatchWebhook($ctx['auth']['user_id'], 'fleet.paused', ['fleet_id' => $id]);

        respond([
            'data' => [
                'fleet_id' => $id,
                'status'   => 'paused',
                'message'  => "Fleet '{$fleet['fleet_name']}' paused",
            ],
        ]);
    } catch (\PDOException $e) {
        error_log('API v1 fleets: pause failed: ' . $e->getMessage());
        respondError('Failed to pause fleet', 500, 'internal_error');
    }
}

function deleteFleetV1(array $ctx, int $id): void
{
    requireScopes($ctx['auth'], 'fleets:write');

    $db = getDB();
    if (!$db) {
        respondError('Database unavailable', 503, 'service_unavailable');
    }

    $fleet = getOwnedResource($db, 'alfred_fleets', $id, $ctx['auth']['user_id']);
    if (!$fleet) {
        respondError('Fleet not found or access denied', 404, 'fleet_not_found');
    }

    if ($fleet['status'] === 'running') {
        respondError('Cannot delete a running fleet. Pause it first.', 400, 'invalid_state');
    }

    try {
        $results = $fleet['results'] ? json_decode($fleet['results'], true) : [];
        $results['retired']    = true;
        $results['retired_at'] = date('c');

        $db->prepare("UPDATE alfred_fleets SET status = 'failed', results = ?, completed_at = NOW(), updated_at = NOW() WHERE id = ?")
           ->execute([json_encode($results), $id]);
        $db->prepare("UPDATE alfred_fleet_agents SET status = 'cancelled' WHERE fleet_id = ? AND status IN ('queued', 'running')")
           ->execute([$id]);

        logUsage($ctx['auth']['user_id'], 'fleets', 1, "DELETE /fleets/{$id}");
        dispatchWebhook($ctx['auth']['user_id'], 'fleet.deleted', ['fleet_id' => $id]);

        respond(['data' => ['deleted' => true, 'fleet_id' => $id]]);
    } catch (\PDOException $e) {
        error_log('API v1 fleets: delete failed: ' . $e->getMessage());
        respondError('Failed to delete fleet', 500, 'internal_error');
    }
}
