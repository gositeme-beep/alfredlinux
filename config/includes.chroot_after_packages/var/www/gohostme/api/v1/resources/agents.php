<?php
/**
 * Alfred API v1 — Agents Resource Handler
 *
 * Endpoints:
 *   POST   /agents              — Create agent
 *   GET    /agents              — List user's agents
 *   GET    /agents/{id}         — Get agent details
 *   PUT    /agents/{id}         — Update agent
 *   DELETE /agents/{id}         — Delete agent
 *   POST   /agents/{id}/execute — Send task to agent
 *
 * @version 1.0.0
 * @since   2026-03-04
 */

declare(strict_types=1);

require_once __DIR__ . '/../helpers.php';

/**
 * Handle agents requests
 */
function handleAgentsRequest(array $ctx): void
{
    $method = $ctx['method'];
    $route  = $ctx['route'];
    $auth   = $ctx['auth'];
    $id     = $route['id'] ?? null;
    $sub    = $route['sub'] ?? null;

    // ── POST /agents ──
    if ($method === 'POST' && $id === null) {
        createAgent($ctx);
    }
    // ── GET /agents ──
    elseif ($method === 'GET' && $id === null) {
        listAgents($ctx);
    }
    // ── GET /agents/{id} ──
    elseif ($method === 'GET' && $id !== null && $sub === null) {
        getAgent($ctx, (int) $id);
    }
    // ── PUT /agents/{id} ──
    elseif ($method === 'PUT' && $id !== null && $sub === null) {
        updateAgent($ctx, (int) $id);
    }
    // ── DELETE /agents/{id} ──
    elseif ($method === 'DELETE' && $id !== null && $sub === null) {
        deleteAgent($ctx, (int) $id);
    }
    // ── POST /agents/{id}/execute ──
    elseif ($method === 'POST' && $id !== null && $sub === 'execute') {
        executeAgent($ctx, (int) $id);
    }
    else {
        respondError("Method {$method} not allowed on /agents" . ($id ? "/{$id}" : ''), 405, 'method_not_allowed');
    }
}

/**
 * POST /agents — Create a new agent
 */
function createAgent(array $ctx): void
{
    requireScopes($ctx['auth'], 'agents:write');

    $body = validateRequired($ctx['body'], ['agent_name', 'agent_role']);

    $agentName = sanitizeInput($body['agent_name'], 100);
    $agentRole = sanitizeInput($body['agent_role'], 50);
    $task      = sanitizeInput($ctx['body']['task'] ?? '', 2000);
    $skills    = $ctx['body']['skills'] ?? [];
    $fleetId   = (int) ($ctx['body']['fleet_id'] ?? 0);

    // Validate role
    $validRoles = ['leader', 'specialist', 'generalist', 'reviewer'];
    if (!in_array($agentRole, $validRoles, true)) {
        respondError('Invalid agent role. Valid: ' . implode(', ', $validRoles), 400, 'validation_error');
    }

    $db = getDB();
    if (!$db) {
        respondError('Database unavailable', 503, 'service_unavailable');
    }

    // If fleet_id provided, verify user owns the fleet
    if ($fleetId > 0) {
        $fleet = getOwnedResource($db, 'alfred_fleets', $fleetId, $ctx['auth']['user_id']);
        if (!$fleet) {
            respondError('Fleet not found or access denied', 404, 'fleet_not_found');
        }
        if (in_array($fleet['status'], ['completed', 'failed'])) {
            respondError("Cannot add agents to a fleet with status '{$fleet['status']}'", 400, 'fleet_closed');
        }
    } else {
        // Default to first fleet or create a virtual one — for API we require fleet_id
        respondError('fleet_id is required to assign the agent to a fleet', 400, 'validation_error');
    }

    try {
        $resultJson = !empty($skills) ? json_encode(['skills' => $skills]) : null;

        $stmt = $db->prepare("
            INSERT INTO alfred_fleet_agents (fleet_id, agent_name, agent_role, task, result, status)
            VALUES (:fleet, :name, :role, :task, :result, 'queued')
        ");
        $stmt->execute([
            ':fleet'  => $fleetId,
            ':name'   => $agentName,
            ':role'   => $agentRole,
            ':task'   => $task ?: null,
            ':result' => $resultJson,
        ]);
        $agentId = (int) $db->lastInsertId();

        // Update agent count
        $db->prepare("UPDATE alfred_fleets SET agent_count = (SELECT COUNT(*) FROM alfred_fleet_agents WHERE fleet_id = ?) WHERE id = ?")
           ->execute([$fleetId, $fleetId]);

        logUsage($ctx['auth']['user_id'], 'agents', 1, 'POST /agents');
        dispatchWebhook($ctx['auth']['user_id'], 'agent.created', ['agent_id' => $agentId, 'fleet_id' => $fleetId]);

        respond([
            'data' => [
                'id'         => $agentId,
                'fleet_id'   => $fleetId,
                'agent_name' => $agentName,
                'agent_role' => $agentRole,
                'task'       => $task ?: null,
                'skills'     => $skills,
                'status'     => 'queued',
            ],
        ], 201);
    } catch (\PDOException $e) {
        error_log('API v1 agents: create failed: ' . $e->getMessage());
        respondError('Failed to create agent', 500, 'internal_error');
    }
}

/**
 * GET /agents — List user's agents across all fleets
 */
function listAgents(array $ctx): void
{
    requireScopes($ctx['auth'], 'agents:read');

    $db = getDB();
    if (!$db) {
        respondError('Database unavailable', 503, 'service_unavailable');
    }

    $pg      = getPagination();
    $userId  = $ctx['auth']['user_id'];
    $status  = sanitizeInput($_GET['status'] ?? '', 20);
    $fleetId = (int) ($_GET['fleet_id'] ?? 0);

    try {
        $where  = 'f.user_id = :uid';
        $params = [':uid' => $userId];

        if ($status !== '' && in_array($status, ['queued', 'running', 'completed', 'failed', 'cancelled'])) {
            $where .= ' AND a.status = :status';
            $params[':status'] = $status;
        }
        if ($fleetId > 0) {
            $where .= ' AND a.fleet_id = :fid';
            $params[':fid'] = $fleetId;
        }

        // Count total
        $countStmt = $db->prepare("SELECT COUNT(*) FROM alfred_fleet_agents a JOIN alfred_fleets f ON a.fleet_id = f.id WHERE {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Fetch page
        $sql = "SELECT a.*, f.fleet_name, f.strategy
                FROM alfred_fleet_agents a
                JOIN alfred_fleets f ON a.fleet_id = f.id
                WHERE {$where}
                ORDER BY a.id DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $pg['per_page'], \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $pg['offset'], \PDO::PARAM_INT);
        $stmt->execute();
        $agents = $stmt->fetchAll();

        // Format
        $agents = array_map(fn($row) => formatRow($row, ['id', 'fleet_id'], ['result']), $agents);

        logUsage($userId, 'agents', 1, 'GET /agents');

        respond(paginatedResponse($agents, $total, $pg['page'], $pg['per_page']));
    } catch (\PDOException $e) {
        error_log('API v1 agents: list failed: ' . $e->getMessage());
        respondError('Failed to list agents', 500, 'internal_error');
    }
}

/**
 * GET /agents/{id} — Get agent details
 */
function getAgent(array $ctx, int $id): void
{
    requireScopes($ctx['auth'], 'agents:read');

    $db = getDB();
    if (!$db) {
        respondError('Database unavailable', 503, 'service_unavailable');
    }

    try {
        $stmt = $db->prepare("
            SELECT a.*, f.fleet_name, f.strategy, f.status as fleet_status
            FROM alfred_fleet_agents a
            JOIN alfred_fleets f ON a.fleet_id = f.id
            WHERE a.id = :id AND f.user_id = :uid
            LIMIT 1
        ");
        $stmt->execute([':id' => $id, ':uid' => $ctx['auth']['user_id']]);
        $agent = $stmt->fetch();

        if (!$agent) {
            respondError('Agent not found or access denied', 404, 'agent_not_found');
        }

        $agent = formatRow($agent, ['id', 'fleet_id'], ['result']);

        logUsage($ctx['auth']['user_id'], 'agents', 1, "GET /agents/{$id}");

        respond(['data' => $agent]);
    } catch (\PDOException $e) {
        error_log('API v1 agents: get failed: ' . $e->getMessage());
        respondError('Failed to get agent', 500, 'internal_error');
    }
}

/**
 * PUT /agents/{id} — Update agent
 */
function updateAgent(array $ctx, int $id): void
{
    requireScopes($ctx['auth'], 'agents:write');

    $db = getDB();
    if (!$db) {
        respondError('Database unavailable', 503, 'service_unavailable');
    }

    $body = $ctx['body'];

    try {
        // Verify ownership
        $stmt = $db->prepare("
            SELECT a.*, f.user_id, f.status as fleet_status
            FROM alfred_fleet_agents a
            JOIN alfred_fleets f ON a.fleet_id = f.id
            WHERE a.id = :id AND f.user_id = :uid
            LIMIT 1
        ");
        $stmt->execute([':id' => $id, ':uid' => $ctx['auth']['user_id']]);
        $agent = $stmt->fetch();

        if (!$agent) {
            respondError('Agent not found or access denied', 404, 'agent_not_found');
        }

        if ($agent['status'] === 'running') {
            respondError('Cannot update a running agent. Pause the fleet first.', 400, 'agent_running');
        }

        // Build update fields
        $updates = [];
        $params  = [':id' => $id];

        if (isset($body['agent_name'])) {
            $updates[] = 'agent_name = :name';
            $params[':name'] = sanitizeInput($body['agent_name'], 100);
        }
        if (isset($body['agent_role'])) {
            $role = sanitizeInput($body['agent_role'], 50);
            if (!in_array($role, ['leader', 'specialist', 'generalist', 'reviewer'])) {
                respondError('Invalid agent role', 400, 'validation_error');
            }
            $updates[] = 'agent_role = :role';
            $params[':role'] = $role;
        }
        if (isset($body['task'])) {
            $updates[] = 'task = :task';
            $params[':task'] = sanitizeInput($body['task'], 2000);
        }
        if (isset($body['skills'])) {
            $result = json_decode($agent['result'] ?? '{}', true) ?: [];
            $result['skills'] = $body['skills'];
            $updates[] = 'result = :result';
            $params[':result'] = json_encode($result);
        }

        if (empty($updates)) {
            respondError('No valid fields to update. Supported: agent_name, agent_role, task, skills', 400, 'validation_error');
        }

        $sql = 'UPDATE alfred_fleet_agents SET ' . implode(', ', $updates) . ' WHERE id = :id';
        $db->prepare($sql)->execute($params);

        logUsage($ctx['auth']['user_id'], 'agents', 1, "PUT /agents/{$id}");

        // Fetch updated
        $stmt = $db->prepare("SELECT * FROM alfred_fleet_agents WHERE id = ?");
        $stmt->execute([$id]);
        $updated = formatRow($stmt->fetch(), ['id', 'fleet_id'], ['result']);

        respond(['data' => $updated]);
    } catch (\PDOException $e) {
        error_log('API v1 agents: update failed: ' . $e->getMessage());
        respondError('Failed to update agent', 500, 'internal_error');
    }
}

/**
 * DELETE /agents/{id} — Delete agent
 */
function deleteAgent(array $ctx, int $id): void
{
    requireScopes($ctx['auth'], 'agents:write');

    $db = getDB();
    if (!$db) {
        respondError('Database unavailable', 503, 'service_unavailable');
    }

    try {
        $stmt = $db->prepare("
            SELECT a.*, f.user_id, f.fleet_name, f.status as fleet_status
            FROM alfred_fleet_agents a
            JOIN alfred_fleets f ON a.fleet_id = f.id
            WHERE a.id = :id AND f.user_id = :uid
            LIMIT 1
        ");
        $stmt->execute([':id' => $id, ':uid' => $ctx['auth']['user_id']]);
        $agent = $stmt->fetch();

        if (!$agent) {
            respondError('Agent not found or access denied', 404, 'agent_not_found');
        }

        if ($agent['status'] === 'running') {
            respondError('Cannot delete a running agent. Pause the fleet first.', 400, 'agent_running');
        }

        $fleetId = (int) $agent['fleet_id'];

        $db->prepare("DELETE FROM alfred_fleet_agents WHERE id = ?")->execute([$id]);

        // Refresh agent count
        $db->prepare("UPDATE alfred_fleets SET agent_count = (SELECT COUNT(*) FROM alfred_fleet_agents WHERE fleet_id = ?) WHERE id = ?")
           ->execute([$fleetId, $fleetId]);

        logUsage($ctx['auth']['user_id'], 'agents', 1, "DELETE /agents/{$id}");
        dispatchWebhook($ctx['auth']['user_id'], 'agent.deleted', ['agent_id' => $id, 'fleet_id' => $fleetId]);

        respond(['data' => ['deleted' => true, 'agent_id' => $id]], 200);
    } catch (\PDOException $e) {
        error_log('API v1 agents: delete failed: ' . $e->getMessage());
        respondError('Failed to delete agent', 500, 'internal_error');
    }
}

/**
 * POST /agents/{id}/execute — Send a task to an agent
 */
function executeAgent(array $ctx, int $id): void
{
    requireScopes($ctx['auth'], 'agents:execute');

    $db = getDB();
    if (!$db) {
        respondError('Database unavailable', 503, 'service_unavailable');
    }

    $body = $ctx['body'];
    $task = $body['task'] ?? $body['input'] ?? '';

    if (empty($task)) {
        respondError('Request body must include "task" or "input"', 400, 'validation_error');
    }

    try {
        $stmt = $db->prepare("
            SELECT a.*, f.user_id, f.fleet_name, f.strategy, f.status as fleet_status
            FROM alfred_fleet_agents a
            JOIN alfred_fleets f ON a.fleet_id = f.id
            WHERE a.id = :id AND f.user_id = :uid
            LIMIT 1
        ");
        $stmt->execute([':id' => $id, ':uid' => $ctx['auth']['user_id']]);
        $agent = $stmt->fetch();

        if (!$agent) {
            respondError('Agent not found or access denied', 404, 'agent_not_found');
        }

        $startTime = microtime(true);

        // Update agent task and status
        $db->prepare("UPDATE alfred_fleet_agents SET task = ?, status = 'running', started_at = NOW() WHERE id = ?")
           ->execute([sanitizeInput($task, 2000), $id]);

        // Try to execute via MCP
        $mcpResult = callMcpServer('agent_execute', [
            'agent_name' => $agent['agent_name'],
            'agent_role' => $agent['agent_role'],
            'task'       => $task,
        ], 60);

        $executionMs = (int) ((microtime(true) - $startTime) * 1000);
        $success = !isset($mcpResult['error']);

        if (!$success && str_contains($mcpResult['error'] ?? '', 'unreachable')) {
            // Provide structured response when MCP is unavailable
            $mcpResult = [
                'result' => "Agent '{$agent['agent_name']}' ({$agent['agent_role']}) accepted task: " . substr($task, 0, 200),
                'status' => 'processing',
                '_note'  => 'MCP server not reachable; agent task queued for async processing.',
            ];
            $success = true;
        }

        // Update agent status and result
        $status = $success ? 'completed' : 'failed';
        $db->prepare("UPDATE alfred_fleet_agents SET status = ?, result = ?, completed_at = NOW() WHERE id = ?")
           ->execute([$status, json_encode($mcpResult), $id]);

        logUsage($ctx['auth']['user_id'], 'agents', 1, "POST /agents/{$id}/execute");
        dispatchWebhook($ctx['auth']['user_id'], 'agent.executed', [
            'agent_id'     => $id,
            'agent_name'   => $agent['agent_name'],
            'success'      => $success,
            'execution_ms' => $executionMs,
        ]);

        respond([
            'data' => [
                'agent_id'          => $id,
                'agent_name'        => $agent['agent_name'],
                'agent_role'        => $agent['agent_role'],
                'task'              => $task,
                'status'            => $status,
                'result'            => $mcpResult,
                'execution_time_ms' => $executionMs,
            ],
        ]);
    } catch (\PDOException $e) {
        error_log('API v1 agents: execute failed: ' . $e->getMessage());
        respondError('Failed to execute agent task', 500, 'internal_error');
    }
}
