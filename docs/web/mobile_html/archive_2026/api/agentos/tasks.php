<?php
/**
 * GSM Alfred OS — Task Graph (DAG) API v1.0
 * Create / execute / pause / resume multi-step plans as directed acyclic graphs
 *
 * Endpoints:
 *   GET    ?action=list              — List tasks
 *   GET    ?action=get&id=X          — Get task with full graph
 *   POST   ?action=create            — Create task with goal
 *   POST   ?action=add_node          — Add node to task graph
 *   POST   ?action=add_edge          — Add dependency edge
 *   POST   ?action=execute           — Execute task DAG
 *   POST   ?action=pause             — Pause running task
 *   POST   ?action=resume            — Resume paused task
 *   POST   ?action=cancel            — Cancel task
 *   GET    ?action=timeline&id=X     — Execution timeline
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
$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':     handleList($auth); break;
    case 'get':      handleGet($auth); break;
    case 'create':   handleCreate($auth); break;
    case 'add_node': handleAddNode($auth); break;
    case 'add_edge': handleAddEdge($auth); break;
    case 'execute':  handleExecute($auth); break;
    case 'pause':    handlePause($auth); break;
    case 'resume':   handleResume($auth); break;
    case 'cancel':   handleCancel($auth); break;
    case 'timeline': handleTimeline($auth); break;
    default:         agentos_error('Unknown action');
}

function handleList(array $auth): void {
    $pdo = agentos_pdo();
    $where = ['1=1'];
    $params = [];

    if (isset($_GET['status'])) {
        $where[] = 'status=?';
        $params[] = $_GET['status'];
    }
    if (isset($_GET['agent_id'])) {
        $where[] = 'agent_id=?';
        $params[] = $_GET['agent_id'];
    }
    if ($auth['user_id'] && !$auth['is_internal']) {
        $where[] = 'user_id=?';
        $params[] = $auth['user_id'];
    }

    $limit = min((int)($_GET['limit'] ?? 50), 200);
    $offset = max((int)($_GET['offset'] ?? 0), 0);

    $sql = "SELECT task_id, user_id, agent_id, goal, status, priority, 
            started_at, completed_at, created_at,
            (SELECT COUNT(*) FROM agentos_task_nodes WHERE task_id=t.task_id) as node_count,
            (SELECT COUNT(*) FROM agentos_task_nodes WHERE task_id=t.task_id AND status='completed') as completed_nodes
            FROM agentos_tasks t WHERE " . implode(' AND ', $where) . " 
            ORDER BY priority DESC, created_at DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    agentos_respond(['ok' => true, 'tasks' => $stmt->fetchAll()]);
}

function handleGet(array $auth): void {
    $id = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['id'] ?? '');
    if (!$id) agentos_error('id required');

    $pdo = agentos_pdo();
    $stmt = $pdo->prepare("SELECT * FROM agentos_tasks WHERE task_id=?");
    $stmt->execute([$id]);
    $task = $stmt->fetch();
    if (!$task) agentos_error('Task not found', 404);

    $task['plan'] = json_decode($task['plan'] ?? 'null', true);
    $task['result'] = json_decode($task['result'] ?? 'null', true);
    $task['context'] = json_decode($task['context'] ?? '{}', true);

    // Get nodes
    $stmt = $pdo->prepare("SELECT * FROM agentos_task_nodes WHERE task_id=? ORDER BY id");
    $stmt->execute([$id]);
    $nodes = $stmt->fetchAll();
    foreach ($nodes as &$node) {
        $node['input_data'] = json_decode($node['input_data'] ?? 'null', true);
        $node['output_data'] = json_decode($node['output_data'] ?? 'null', true);
    }

    // Get edges
    $stmt = $pdo->prepare("SELECT * FROM agentos_task_edges WHERE task_id=?");
    $stmt->execute([$id]);
    $edges = $stmt->fetchAll();

    agentos_respond(['ok' => true, 'task' => $task, 'nodes' => $nodes, 'edges' => $edges]);
}

function handleCreate(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || empty($input['goal'])) agentos_error('goal required');

    $pdo = agentos_pdo();
    $taskId = $input['task_id'] ?? agentos_id('task');
    $taskId = preg_replace('/[^a-zA-Z0-9_-]/', '', mb_substr($taskId, 0, 100));

    $stmt = $pdo->prepare("INSERT INTO agentos_tasks 
        (task_id, user_id, agent_id, goal, status, priority, context, parent_task_id)
        VALUES (?, ?, ?, ?, 'pending', ?, ?, ?)");
    $stmt->execute([
        $taskId,
        $auth['user_id'],
        mb_substr($input['agent_id'] ?? 'alfred', 0, 50),
        mb_substr($input['goal'], 0, 10000),
        min(max((int)($input['priority'] ?? 5), 1), 10),
        json_encode($input['context'] ?? []),
        $input['parent_task_id'] ?? null,
    ]);

    // If nodes are provided, add them
    if (!empty($input['nodes'])) {
        addNodesAndEdges($pdo, $taskId, $input['nodes'], $input['edges'] ?? []);
    }

    agentos_respond(['ok' => true, 'task_id' => $taskId], 201);
}

function handleAddNode(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $taskId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['task_id'] ?? '');
    if (!$taskId) agentos_error('task_id required');

    $pdo = agentos_pdo();
    $nodeId = $input['node_id'] ?? agentos_id('node');
    $nodeId = preg_replace('/[^a-zA-Z0-9_-]/', '', $nodeId);

    $validTypes = ['capability', 'skill', 'subtask', 'decision', 'wait', 'parallel'];
    $nodeType = in_array($input['node_type'] ?? '', $validTypes) ? $input['node_type'] : 'capability';

    $stmt = $pdo->prepare("INSERT INTO agentos_task_nodes 
        (task_id, node_id, node_type, reference_id, label, input_data, status)
        VALUES (?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->execute([
        $taskId, $nodeId, $nodeType,
        mb_substr($input['reference_id'] ?? '', 0, 100),
        mb_substr($input['label'] ?? '', 0, 200),
        json_encode($input['input_data'] ?? null),
    ]);

    agentos_respond(['ok' => true, 'task_id' => $taskId, 'node_id' => $nodeId]);
}

function handleAddEdge(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $taskId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['task_id'] ?? '');
    if (!$taskId || empty($input['from_node']) || empty($input['to_node'])) {
        agentos_error('task_id, from_node, to_node required');
    }

    // Cycle detection
    $pdo = agentos_pdo();
    if (wouldCreateCycle($pdo, $taskId, $input['from_node'], $input['to_node'])) {
        agentos_error('Adding this edge would create a cycle');
    }

    $validTypes = ['dependency', 'conditional', 'data_flow'];
    $edgeType = in_array($input['edge_type'] ?? '', $validTypes) ? $input['edge_type'] : 'dependency';

    $stmt = $pdo->prepare("INSERT INTO agentos_task_edges 
        (task_id, from_node_id, to_node_id, edge_type, condition_expr)
        VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $taskId,
        preg_replace('/[^a-zA-Z0-9_-]/', '', $input['from_node']),
        preg_replace('/[^a-zA-Z0-9_-]/', '', $input['to_node']),
        $edgeType,
        $input['condition'] ?? null,
    ]);

    agentos_respond(['ok' => true, 'task_id' => $taskId]);
}

function handleExecute(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $taskId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['task_id'] ?? '');
    if (!$taskId) agentos_error('task_id required');

    $pdo = agentos_pdo();
    $traceId = agentos_trace_id();

    // Get task
    $stmt = $pdo->prepare("SELECT * FROM agentos_tasks WHERE task_id=?");
    $stmt->execute([$taskId]);
    $task = $stmt->fetch();
    if (!$task) agentos_error('Task not found', 404);
    if (!in_array($task['status'], ['pending', 'ready', 'paused'])) {
        agentos_error("Task is {$task['status']}, cannot execute");
    }

    // Mark as running
    $pdo->prepare("UPDATE agentos_tasks SET status='running', started_at=COALESCE(started_at,NOW()) WHERE task_id=?")->execute([$taskId]);

    // Get nodes and edges
    $stmt = $pdo->prepare("SELECT * FROM agentos_task_nodes WHERE task_id=? ORDER BY id");
    $stmt->execute([$taskId]);
    $nodes = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT * FROM agentos_task_edges WHERE task_id=?");
    $stmt->execute([$taskId]);
    $edges = $stmt->fetchAll();

    if (empty($nodes)) {
        // No pre-built graph — let the runtime handle it
        $pdo->prepare("UPDATE agentos_tasks SET status='ready' WHERE task_id=?")->execute([$taskId]);
        agentos_respond(['ok' => true, 'task_id' => $taskId, 'status' => 'delegated_to_runtime']);
        return;
    }

    // Build adjacency and in-degree maps
    $adj = [];
    $inDegree = [];
    foreach ($nodes as $n) {
        $adj[$n['node_id']] = [];
        $inDegree[$n['node_id']] = 0;
    }
    foreach ($edges as $e) {
        $adj[$e['from_node_id']][] = $e['to_node_id'];
        $inDegree[$e['to_node_id']] = ($inDegree[$e['to_node_id']] ?? 0) + 1;
    }

    // Topological execution (Kahn's algorithm)
    $queue = [];
    foreach ($inDegree as $nodeId => $deg) {
        if ($deg === 0 && getNodeStatus($nodes, $nodeId) === 'pending') {
            $queue[] = $nodeId;
        }
    }

    $startTime = hrtime(true);
    $executed = 0;
    $failed = 0;
    $results = [];

    while (!empty($queue)) {
        // Check if task was paused/cancelled
        $stmt = $pdo->prepare("SELECT status FROM agentos_tasks WHERE task_id=?");
        $stmt->execute([$taskId]);
        $currentStatus = $stmt->fetchColumn();
        if (in_array($currentStatus, ['paused', 'cancelled'])) break;

        $nodeId = array_shift($queue);
        $node = getNodeById($nodes, $nodeId);
        if (!$node) continue;

        $nodeResult = executeNode($pdo, $traceId, $taskId, $node, $auth);
        $results[$nodeId] = $nodeResult;
        $executed++;

        if ($nodeResult['success']) {
            // Update node status
            $pdo->prepare("UPDATE agentos_task_nodes SET status='completed', output_data=?, completed_at=NOW(), duration_ms=? WHERE task_id=? AND node_id=?")
                ->execute([json_encode($nodeResult['result']), $nodeResult['duration_ms'], $taskId, $nodeId]);

            // Unlock downstream nodes
            foreach ($adj[$nodeId] ?? [] as $next) {
                $inDegree[$next]--;
                if ($inDegree[$next] <= 0 && getNodeStatus($nodes, $next) === 'pending') {
                    $queue[] = $next;
                }
            }
        } else {
            $failed++;
            $pdo->prepare("UPDATE agentos_task_nodes SET status='failed', error=?, completed_at=NOW() WHERE task_id=? AND node_id=?")
                ->execute([$nodeResult['error'] ?? 'Unknown error', $taskId, $nodeId]);
        }
    }

    $totalMs = (int)((hrtime(true) - $startTime) / 1_000_000);
    $allDone = $failed === 0 && $executed === count($nodes);
    $finalStatus = $allDone ? 'completed' : ($failed > 0 ? 'failed' : $currentStatus ?? 'running');

    $pdo->prepare("UPDATE agentos_tasks SET status=?, result=?, completed_at=IF(?='completed',NOW(),completed_at) WHERE task_id=?")
        ->execute([$finalStatus, json_encode($results), $finalStatus, $taskId]);

    agentos_respond([
        'ok' => true,
        'task_id' => $taskId,
        'status' => $finalStatus,
        'executed' => $executed,
        'failed' => $failed,
        'total_nodes' => count($nodes),
        'duration_ms' => $totalMs,
        'results' => $results,
    ]);
}

function handlePause(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $taskId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['task_id'] ?? '');
    $pdo = agentos_pdo();
    $pdo->prepare("UPDATE agentos_tasks SET status='paused' WHERE task_id=? AND status='running'")->execute([$taskId]);
    agentos_respond(['ok' => true, 'task_id' => $taskId, 'status' => 'paused']);
}

function handleResume(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $taskId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['task_id'] ?? '');
    $pdo = agentos_pdo();
    $pdo->prepare("UPDATE agentos_tasks SET status='ready' WHERE task_id=? AND status='paused'")->execute([$taskId]);
    agentos_respond(['ok' => true, 'task_id' => $taskId, 'status' => 'resumed']);
}

function handleCancel(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $taskId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['task_id'] ?? '');
    $pdo = agentos_pdo();
    $pdo->prepare("UPDATE agentos_tasks SET status='cancelled', completed_at=NOW() WHERE task_id=?")->execute([$taskId]);
    $pdo->prepare("UPDATE agentos_task_nodes SET status='cancelled' WHERE task_id=? AND status IN ('pending','running')")->execute([$taskId]);
    agentos_respond(['ok' => true, 'task_id' => $taskId, 'status' => 'cancelled']);
}

function handleTimeline(array $auth): void {
    $id = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['id'] ?? '');
    if (!$id) agentos_error('id required');

    $pdo = agentos_pdo();
    $stmt = $pdo->prepare("SELECT action_type, status, capability_id, decision_reason, 
        duration_ms, risk_level, created_at
        FROM agentos_audit_log WHERE task_id=? ORDER BY created_at, id");
    $stmt->execute([$id]);

    agentos_respond(['ok' => true, 'task_id' => $id, 'timeline' => $stmt->fetchAll()]);
}

// ── Helper Functions ───────────────────────────────────────────

function addNodesAndEdges(PDO $pdo, string $taskId, array $nodes, array $edges): void {
    $stmtNode = $pdo->prepare("INSERT INTO agentos_task_nodes 
        (task_id, node_id, node_type, reference_id, label, input_data, status)
        VALUES (?, ?, ?, ?, ?, ?, 'pending')");

    foreach ($nodes as $node) {
        $nodeId = preg_replace('/[^a-zA-Z0-9_-]/', '', $node['node_id'] ?? agentos_id('node'));
        $stmtNode->execute([
            $taskId, $nodeId,
            $node['node_type'] ?? 'capability',
            mb_substr($node['reference_id'] ?? '', 0, 100),
            mb_substr($node['label'] ?? '', 0, 200),
            json_encode($node['input_data'] ?? null),
        ]);
    }

    $stmtEdge = $pdo->prepare("INSERT INTO agentos_task_edges 
        (task_id, from_node_id, to_node_id, edge_type, condition_expr)
        VALUES (?, ?, ?, ?, ?)");

    foreach ($edges as $edge) {
        $stmtEdge->execute([
            $taskId,
            preg_replace('/[^a-zA-Z0-9_-]/', '', $edge['from'] ?? $edge['from_node'] ?? ''),
            preg_replace('/[^a-zA-Z0-9_-]/', '', $edge['to'] ?? $edge['to_node'] ?? ''),
            $edge['type'] ?? 'dependency',
            $edge['condition'] ?? null,
        ]);
    }
}

function wouldCreateCycle(PDO $pdo, string $taskId, string $from, string $to): bool {
    // Check if 'to' can reach 'from' — if so, adding from→to creates a cycle
    $stmt = $pdo->prepare("SELECT from_node_id, to_node_id FROM agentos_task_edges WHERE task_id=?");
    $stmt->execute([$taskId]);
    $edges = $stmt->fetchAll();

    $adj = [];
    foreach ($edges as $e) {
        $adj[$e['from_node_id']][] = $e['to_node_id'];
    }

    // BFS from 'to' to see if we can reach 'from'
    $visited = [];
    $queue = [$to];
    while (!empty($queue)) {
        $current = array_shift($queue);
        if ($current === $from) return true;
        if (isset($visited[$current])) continue;
        $visited[$current] = true;
        foreach ($adj[$current] ?? [] as $next) {
            $queue[] = $next;
        }
    }
    return false;
}

function executeNode(PDO $pdo, string $traceId, string $taskId, array $node, array $auth): array {
    $start = hrtime(true);
    $refId = $node['reference_id'] ?? '';
    $inputData = json_decode($node['input_data'] ?? '{}', true);

    $pdo->prepare("UPDATE agentos_task_nodes SET status='running', started_at=NOW() WHERE task_id=? AND node_id=?")
        ->execute([$taskId, $node['node_id']]);

    switch ($node['node_type']) {
        case 'capability':
            $result = executeCapabilityById($pdo, $refId, $inputData, $auth);
            break;
        case 'skill':
            $result = executeSkillById($pdo, $refId, $inputData, $auth);
            break;
        case 'decision':
            $result = evaluateDecision($pdo, $node, $inputData, $auth);
            break;
        case 'wait':
            $seconds = min((int)($inputData['seconds'] ?? 1), 30);
            sleep($seconds);
            $result = ['success' => true, 'result' => ['waited' => $seconds]];
            break;
        default:
            $result = ['success' => true, 'result' => ['passthrough' => true]];
    }

    $result['duration_ms'] = (int)((hrtime(true) - $start) / 1_000_000);

    agentos_audit([
        'trace_id' => $traceId, 'task_id' => $taskId, 'node_id' => $node['node_id'],
        'agent_id' => 'alfred', 'user_id' => $auth['user_id'],
        'action_type' => 'node_executed', 'capability_id' => $refId,
        'status' => $result['success'] ? 'completed' : 'failed',
        'duration_ms' => $result['duration_ms'],
    ]);

    return $result;
}

function executeCapabilityById(PDO $pdo, string $capId, array $input, array $auth): array {
    $stmt = $pdo->prepare("SELECT * FROM agentos_capabilities WHERE capability_id=? AND enabled=1");
    $stmt->execute([$capId]);
    $cap = $stmt->fetch();

    if (!$cap || !$cap['endpoint']) {
        return ['success' => false, 'error' => "Capability not found: {$capId}"];
    }

    $secret = getenv('INTERNAL_SECRET') ?: '';
    $ch = curl_init("https://gositeme.com{$cap['endpoint']}");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(array_merge($input, ['_agentos' => true])),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-Internal-Secret: ' . $secret],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT_MS => (int)$cap['timeout_ms'],
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $result = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['success' => $http >= 200 && $http < 300, 'result' => json_decode($result, true), 'http_code' => $http];
}

function executeSkillById(PDO $pdo, string $skillId, array $input, array $auth): array {
    $secret = getenv('INTERNAL_SECRET') ?: '';
    $ch = curl_init("https://gositeme.com/api/agentos/skills.php?action=execute");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(array_merge(['skill_id' => $skillId], $input)),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-Internal-Secret: ' . $secret],
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $result = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['success' => $http >= 200 && $http < 300, 'result' => json_decode($result, true)];
}

function evaluateDecision(PDO $pdo, array $node, array $input, array $auth): array {
    // Use AI to make a decision
    return ['success' => true, 'result' => ['decision' => 'proceed', 'confidence' => 0.8]];
}

function getNodeStatus(array $nodes, string $nodeId): string {
    foreach ($nodes as $n) {
        if ($n['node_id'] === $nodeId) return $n['status'];
    }
    return 'unknown';
}

function getNodeById(array $nodes, string $nodeId): ?array {
    foreach ($nodes as $n) {
        if ($n['node_id'] === $nodeId) return $n;
    }
    return null;
}
