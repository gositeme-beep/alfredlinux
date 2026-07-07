<?php
/**
 * Alfred API v1 — Voice Resource Handler
 *
 * Endpoints:
 *   GET  /voice/calls          — List call history
 *   GET  /voice/calls/{id}     — Get call details + transcript
 *   POST /voice/rooms          — Create voice room
 *   GET  /voice/rooms          — List active rooms
 *   GET  /voice/rooms/{id}     — Get room details
 *
 * @version 1.0.0
 * @since   2026-03-04
 */

declare(strict_types=1);

require_once __DIR__ . '/../helpers.php';

/**
 * Handle voice requests
 */
function handleVoiceRequest(array $ctx): void
{
    $method = $ctx['method'];
    $route  = $ctx['route'];
    $id     = $route['id'] ?? null;   // "calls" or "rooms"
    $sub    = $route['sub'] ?? null;   // call/room ID
    $extra  = $route['extra'] ?? null;

    // Route: /voice/calls
    if ($id === 'calls') {
        if ($method === 'GET' && $sub === null) {
            listCalls($ctx);
        } elseif ($method === 'GET' && $sub !== null) {
            getCall($ctx, (int) $sub);
        } else {
            respondError("Method {$method} not allowed on /voice/calls", 405, 'method_not_allowed');
        }
        return;
    }

    // Route: /voice/rooms
    if ($id === 'rooms') {
        if ($method === 'POST' && $sub === null) {
            createRoom($ctx);
        } elseif ($method === 'GET' && $sub === null) {
            listRooms($ctx);
        } elseif ($method === 'GET' && $sub !== null) {
            getRoom($ctx, (int) $sub);
        } else {
            respondError("Method {$method} not allowed on /voice/rooms", 405, 'method_not_allowed');
        }
        return;
    }

    // Top-level /voice — return summary
    if ($id === null && $method === 'GET') {
        requireScopes($ctx['auth'], 'voice:read');
        respond([
            'data' => [
                'endpoints' => [
                    'calls' => '/api/v1/voice/calls',
                    'rooms' => '/api/v1/voice/rooms',
                ],
                'description' => 'Voice & conferencing endpoints for Alfred',
            ],
        ]);
        return;
    }

    respondError("Unknown voice sub-resource '{$id}'", 404, 'resource_not_found');
}

// ─── Calls ──────────────────────────────────────────────────────────────────

/**
 * GET /voice/calls — List call history from alfred_conferences (calls are modeled as 1:1 conferences)
 */
function listCalls(array $ctx): void
{
    requireScopes($ctx['auth'], 'voice:read');

    $db = getDB();
    if (!$db) {
        respondError('Database unavailable', 503, 'service_unavailable');
    }

    $userId = $ctx['auth']['user_id'];
    $pg     = getPagination();
    $status = sanitizeInput($_GET['status'] ?? '', 20);

    try {
        $where  = 'host_user_id = :uid AND max_participants <= 2';
        $params = [':uid' => $userId];

        if ($status !== '' && in_array($status, ['waiting', 'active', 'ended'])) {
            $where .= ' AND status = :status';
            $params[':status'] = $status;
        }

        $countStmt = $db->prepare("SELECT COUNT(*) FROM alfred_conferences WHERE {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT id, host_user_id, topic, room_code, status, created_at, ended_at
                FROM alfred_conferences 
                WHERE {$where}
                ORDER BY created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $pg['per_page'], \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $pg['offset'], \PDO::PARAM_INT);
        $stmt->execute();
        $calls = $stmt->fetchAll();

        $calls = array_map(fn($r) => formatRow($r, ['id', 'host_user_id']), $calls);

        logUsage($userId, 'voice', 1, 'GET /voice/calls');

        respond(paginatedResponse($calls, $total, $pg['page'], $pg['per_page']));
    } catch (\PDOException $e) {
        error_log('API v1 voice: list calls failed: ' . $e->getMessage());
        respondError('Failed to list calls', 500, 'internal_error');
    }
}

/**
 * GET /voice/calls/{id} — Get call details + transcript
 */
function getCall(array $ctx, int $id): void
{
    requireScopes($ctx['auth'], 'voice:read');

    $db = getDB();
    if (!$db) {
        respondError('Database unavailable', 503, 'service_unavailable');
    }

    $call = getOwnedResource($db, 'alfred_conferences', $id, $ctx['auth']['user_id'], 'host_user_id');
    if (!$call) {
        respondError('Call not found or access denied', 404, 'call_not_found');
    }

    $call = formatRow($call, ['id', 'host_user_id', 'max_participants', 'current_participants'], ['agenda']);

    logUsage($ctx['auth']['user_id'], 'voice', 1, "GET /voice/calls/{$id}");

    respond(['data' => $call]);
}

// ─── Rooms ──────────────────────────────────────────────────────────────────

/**
 * POST /voice/rooms — Create a new conference room
 */
function createRoom(array $ctx): void
{
    requireScopes($ctx['auth'], 'voice:write');

    $body = validateRequired($ctx['body'], ['topic']);

    $topic            = sanitizeInput($body['topic'], 255);
    $maxParticipants  = min(100, max(2, (int) ($ctx['body']['max_participants'] ?? 10)));
    $agenda           = $ctx['body']['agenda'] ?? null;

    $db = getDB();
    if (!$db) {
        respondError('Database unavailable', 503, 'service_unavailable');
    }

    $userId = $ctx['auth']['user_id'];

    // Generate unique room code: ALF-XXXX-XXXX
    $roomCode = 'ALF-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4)) . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));

    try {
        $stmt = $db->prepare("
            INSERT INTO alfred_conferences (host_user_id, topic, room_code, max_participants, agenda, created_at)
            VALUES (:uid, :topic, :code, :max, :agenda, NOW())
        ");
        $stmt->execute([
            ':uid'    => $userId,
            ':topic'  => $topic,
            ':code'   => $roomCode,
            ':max'    => $maxParticipants,
            ':agenda' => $agenda ? json_encode($agenda) : null,
        ]);
        $roomId = (int) $db->lastInsertId();

        logUsage($userId, 'voice', 1, 'POST /voice/rooms');
        dispatchWebhook($userId, 'voice.room_created', ['room_id' => $roomId, 'room_code' => $roomCode]);

        respond([
            'data' => [
                'id'               => $roomId,
                'topic'            => $topic,
                'room_code'        => $roomCode,
                'max_participants'  => $maxParticipants,
                'status'           => 'waiting',
                'join_url'         => SITE_URL . '/conference-room.php?code=' . $roomCode,
                'agenda'           => $agenda,
            ],
        ], 201);
    } catch (\PDOException $e) {
        error_log('API v1 voice: create room failed: ' . $e->getMessage());
        respondError('Failed to create room', 500, 'internal_error');
    }
}

/**
 * GET /voice/rooms — List active conference rooms
 */
function listRooms(array $ctx): void
{
    requireScopes($ctx['auth'], 'voice:read');

    $db = getDB();
    if (!$db) {
        respondError('Database unavailable', 503, 'service_unavailable');
    }

    $userId = $ctx['auth']['user_id'];
    $pg     = getPagination();
    $status = sanitizeInput($_GET['status'] ?? '', 20);

    try {
        $where  = 'host_user_id = :uid AND max_participants > 2';
        $params = [':uid' => $userId];

        if ($status !== '' && in_array($status, ['waiting', 'active', 'ended'])) {
            $where .= ' AND status = :status';
            $params[':status'] = $status;
        }

        $countStmt = $db->prepare("SELECT COUNT(*) FROM alfred_conferences WHERE {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT id, host_user_id, topic, room_code, max_participants, current_participants, status, created_at, ended_at
                FROM alfred_conferences
                WHERE {$where}
                ORDER BY created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $pg['per_page'], \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $pg['offset'], \PDO::PARAM_INT);
        $stmt->execute();
        $rooms = $stmt->fetchAll();

        $rooms = array_map(fn($r) => formatRow($r, ['id', 'host_user_id', 'max_participants', 'current_participants']), $rooms);

        logUsage($userId, 'voice', 1, 'GET /voice/rooms');

        respond(paginatedResponse($rooms, $total, $pg['page'], $pg['per_page']));
    } catch (\PDOException $e) {
        error_log('API v1 voice: list rooms failed: ' . $e->getMessage());
        respondError('Failed to list rooms', 500, 'internal_error');
    }
}

/**
 * GET /voice/rooms/{id} — Get room details
 */
function getRoom(array $ctx, int $id): void
{
    requireScopes($ctx['auth'], 'voice:read');

    $db = getDB();
    if (!$db) {
        respondError('Database unavailable', 503, 'service_unavailable');
    }

    $room = getOwnedResource($db, 'alfred_conferences', $id, $ctx['auth']['user_id'], 'host_user_id');
    if (!$room) {
        respondError('Room not found or access denied', 404, 'room_not_found');
    }

    $room = formatRow($room, ['id', 'host_user_id', 'max_participants', 'current_participants'], ['agenda']);
    $room['join_url'] = SITE_URL . '/conference-room.php?code=' . $room['room_code'];

    logUsage($ctx['auth']['user_id'], 'voice', 1, "GET /voice/rooms/{$id}");

    respond(['data' => $room]);
}
