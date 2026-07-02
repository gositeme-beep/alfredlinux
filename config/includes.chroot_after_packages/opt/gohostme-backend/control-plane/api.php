<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

cp_require_api_key();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$db = cp_db();

if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '{}', true);
    if (!is_array($data)) {
        cp_json_response(['ok' => false, 'error' => 'Invalid JSON body'], 400);
    }

    $action = (string) ($data['action'] ?? '');
    $payload = $data['payload'] ?? [];
    $idempotencyKey = trim((string) ($data['idempotency_key'] ?? ''));
    if (!is_array($payload)) {
        $payload = [];
    }

    if (!cp_validate_action($action)) {
        cp_json_response(['ok' => false, 'error' => 'Invalid action'], 400);
    }

    if ($idempotencyKey !== '' && !preg_match('/^[a-zA-Z0-9:_-]{8,120}$/', $idempotencyKey)) {
        cp_json_response(['ok' => false, 'error' => 'Invalid idempotency key format'], 400);
    }

    $requestedBy = (string) ($data['requested_by'] ?? 'control-api');

    if ($idempotencyKey !== '') {
        $existingStmt = $db->prepare('SELECT id, status FROM control_jobs WHERE action = ? AND idempotency_key = ? ORDER BY id DESC LIMIT 1');
        $existingStmt->execute([$action, $idempotencyKey]);
        $existing = $existingStmt->fetch();
        if ($existing) {
            cp_json_response([
                'ok' => true,
                'job_id' => (int) $existing['id'],
                'status' => (string) $existing['status'],
                'deduplicated' => true,
            ]);
        }
    }

    $stmt = $db->prepare('INSERT INTO control_jobs (action, payload_json, idempotency_key, status, requested_by, created_at, updated_at) VALUES (?, ?, ?, "pending", ?, NOW(), NOW())');
    $stmt->execute([$action, json_encode($payload, JSON_UNESCAPED_SLASHES), $idempotencyKey !== '' ? $idempotencyKey : null, $requestedBy]);
    $jobId = (int) $db->lastInsertId();

    cp_insert_event($db, $jobId, 'info', 'Job created', ['action' => $action, 'idempotency_key' => $idempotencyKey]);
    cp_json_response(['ok' => true, 'job_id' => $jobId, 'status' => 'pending'], 201);
}

if ($method === 'GET') {
    $jobId = isset($_GET['job_id']) ? (int) $_GET['job_id'] : 0;
    if ($jobId <= 0) {
        cp_json_response(['ok' => false, 'error' => 'Missing job_id'], 400);
    }

    $stmt = $db->prepare('SELECT id, action, status, payload_json, result_json, error_message, requested_by, started_at, finished_at, created_at, updated_at FROM control_jobs WHERE id = ? LIMIT 1');
    $stmt->execute([$jobId]);
    $job = $stmt->fetch();
    if (!$job) {
        cp_json_response(['ok' => false, 'error' => 'Job not found'], 404);
    }

    $eventsStmt = $db->prepare('SELECT level, message, context_json, created_at FROM control_events WHERE job_id = ? ORDER BY id ASC');
    $eventsStmt->execute([$jobId]);
    $events = $eventsStmt->fetchAll();

    cp_json_response([
        'ok' => true,
        'job' => $job,
        'events' => $events,
    ]);
}

cp_json_response(['ok' => false, 'error' => 'Method not allowed'], 405);
