<?php
/**
 * GSM Alfred OS — Audit Trail API v2.0 (Phase 3: Safety & Simulation)
 * Immutable append-only log with replay capability
 *
 * Endpoints:
 *   GET    ?action=list              — List audit entries
 *   GET    ?action=get&trace_id=X    — Get full trace
 *   GET    ?action=task&task_id=X    — Get audit by task
 *   GET    ?action=agent&agent_id=X  — Get audit by agent
 *   GET    ?action=replay&task_id=X  — Replay a task execution
 *   GET    ?action=stats             — Audit statistics
 *   GET    ?action=anomalies         — Detect audit anomalies
 *   GET    ?action=export&task_id=X  — Export task execution as structured report
 *   GET    ?action=timeline          — Get system-wide timeline (last N hours)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Internal-Secret');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

define('GOSITEME_API', true);
require_once __DIR__ . '/bootstrap.php';
agentos_ensure_schema();

$auth = agentos_auth();
$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':      handleList($auth); break;
    case 'get':       handleGet($auth); break;
    case 'task':      handleTask($auth); break;
    case 'agent':     handleAgent($auth); break;
    case 'replay':    handleReplay($auth); break;
    case 'stats':     handleStats($auth); break;
    case 'anomalies': handleAnomalies($auth); break;
    case 'export':    handleExport($auth); break;
    case 'timeline':  handleTimeline($auth); break;
    default:          agentos_error('Unknown action');
}

function handleList(array $auth): void {
    $pdo = agentos_pdo();
    $limit = min((int)($_GET['limit'] ?? 50), 500);
    $offset = max((int)($_GET['offset'] ?? 0), 0);

    $where = ['1=1'];
    $params = [];

    if (isset($_GET['action_type'])) {
        $where[] = 'action_type=?';
        $params[] = $_GET['action_type'];
    }
    if (isset($_GET['status'])) {
        $where[] = 'status=?';
        $params[] = $_GET['status'];
    }
    if (isset($_GET['risk_level'])) {
        $where[] = 'risk_level=?';
        $params[] = $_GET['risk_level'];
    }
    if (isset($_GET['since'])) {
        $where[] = 'created_at >= ?';
        $params[] = $_GET['since'];
    }

    $stmt = $pdo->prepare("SELECT id, trace_id, task_id, node_id, agent_id, user_id,
        action_type, capability_id, decision_reason, risk_level, status, 
        duration_ms, created_at
        FROM agentos_audit_log WHERE " . implode(' AND ', $where) . " 
        ORDER BY created_at DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset);
    $stmt->execute($params);

    agentos_respond(['ok' => true, 'entries' => $stmt->fetchAll()]);
}

function handleGet(array $auth): void {
    $traceId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['trace_id'] ?? '');
    if (!$traceId) agentos_error('trace_id required');

    $pdo = agentos_pdo();
    $stmt = $pdo->prepare("SELECT * FROM agentos_audit_log WHERE trace_id=? ORDER BY created_at, id");
    $stmt->execute([$traceId]);
    $entries = $stmt->fetchAll();

    foreach ($entries as &$e) {
        $e['input_summary'] = json_decode($e['input_summary'] ?? 'null', true);
        $e['output_summary'] = json_decode($e['output_summary'] ?? 'null', true);
        $e['metadata'] = json_decode($e['metadata'] ?? 'null', true);
    }

    // Calculate trace summary
    $totalMs = array_sum(array_column($entries, 'duration_ms'));
    $actions = array_column($entries, 'action_type');
    $statuses = array_count_values(array_column($entries, 'status'));

    agentos_respond([
        'ok' => true,
        'trace_id' => $traceId,
        'entry_count' => count($entries),
        'total_duration_ms' => $totalMs,
        'action_types' => array_count_values($actions),
        'status_summary' => $statuses,
        'entries' => $entries,
    ]);
}

function handleTask(array $auth): void {
    $taskId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['task_id'] ?? '');
    if (!$taskId) agentos_error('task_id required');

    $pdo = agentos_pdo();
    $stmt = $pdo->prepare("SELECT * FROM agentos_audit_log WHERE task_id=? ORDER BY created_at, id");
    $stmt->execute([$taskId]);
    $entries = $stmt->fetchAll();

    foreach ($entries as &$e) {
        $e['input_summary'] = json_decode($e['input_summary'] ?? 'null', true);
        $e['output_summary'] = json_decode($e['output_summary'] ?? 'null', true);
    }

    agentos_respond(['ok' => true, 'task_id' => $taskId, 'entries' => $entries]);
}

function handleAgent(array $auth): void {
    $agentId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['agent_id'] ?? 'alfred');
    $limit = min((int)($_GET['limit'] ?? 100), 500);

    $pdo = agentos_pdo();
    $stmt = $pdo->prepare("SELECT * FROM agentos_audit_log WHERE agent_id=? 
        ORDER BY created_at DESC LIMIT " . (int)$limit);
    $stmt->execute([$agentId]);

    agentos_respond(['ok' => true, 'agent_id' => $agentId, 'entries' => $stmt->fetchAll()]);
}

// ═══════════════════════════════════════════════════════════════
// REPLAY — Reconstruct task execution from audit trail
// ═══════════════════════════════════════════════════════════════
function handleReplay(array $auth): void {
    $taskId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['task_id'] ?? '');
    if (!$taskId) agentos_error('task_id required');

    $pdo = agentos_pdo();

    // Get task info
    $stmt = $pdo->prepare("SELECT * FROM agentos_tasks WHERE task_id=?");
    $stmt->execute([$taskId]);
    $task = $stmt->fetch();
    if (!$task) agentos_error('Task not found', 404);

    // Get ordered audit entries
    $stmt = $pdo->prepare("SELECT action_type, capability_id, status, 
        input_summary, output_summary, decision_reason, risk_level, 
        duration_ms, created_at
        FROM agentos_audit_log WHERE task_id=? ORDER BY created_at, id");
    $stmt->execute([$taskId]);
    $entries = $stmt->fetchAll();

    // Build replay timeline
    $timeline = [];
    $startTime = null;
    foreach ($entries as $entry) {
        if (!$startTime) $startTime = $entry['created_at'];
        $entry['input_summary'] = json_decode($entry['input_summary'] ?? 'null', true);
        $entry['output_summary'] = json_decode($entry['output_summary'] ?? 'null', true);

        $timeline[] = [
            'timestamp' => $entry['created_at'],
            'action' => $entry['action_type'],
            'capability' => $entry['capability_id'],
            'status' => $entry['status'],
            'reason' => $entry['decision_reason'],
            'risk' => $entry['risk_level'],
            'duration_ms' => $entry['duration_ms'],
            'input' => $entry['input_summary'],
            'output' => $entry['output_summary'],
        ];
    }

    $task['plan'] = json_decode($task['plan'] ?? 'null', true);
    $task['result'] = json_decode($task['result'] ?? 'null', true);

    agentos_respond([
        'ok' => true,
        'task_id' => $taskId,
        'goal' => $task['goal'],
        'status' => $task['status'],
        'agent_id' => $task['agent_id'],
        'started_at' => $task['started_at'],
        'completed_at' => $task['completed_at'],
        'total_events' => count($timeline),
        'timeline' => $timeline,
    ]);
}

// ═══════════════════════════════════════════════════════════════
// STATS — Audit statistics
// ═══════════════════════════════════════════════════════════════
function handleStats(array $auth): void {
    $pdo = agentos_pdo();

    // Overall counts
    $stmt = $pdo->query("SELECT COUNT(*) as total,
        SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status='failed' THEN 1 ELSE 0 END) as failed,
        SUM(CASE WHEN status='blocked' THEN 1 ELSE 0 END) as blocked,
        AVG(duration_ms) as avg_duration_ms,
        COUNT(DISTINCT trace_id) as unique_traces,
        COUNT(DISTINCT agent_id) as unique_agents
        FROM agentos_audit_log");
    $overall = $stmt->fetch();

    // Last 24h
    $stmt = $pdo->query("SELECT COUNT(*) as count, 
        SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status='failed' THEN 1 ELSE 0 END) as failed
        FROM agentos_audit_log WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $last24h = $stmt->fetch();

    // By action type
    $stmt = $pdo->query("SELECT action_type, COUNT(*) as count 
        FROM agentos_audit_log GROUP BY action_type ORDER BY count DESC LIMIT 20");
    $byAction = $stmt->fetchAll();

    // By risk level
    $stmt = $pdo->query("SELECT risk_level, COUNT(*) as count 
        FROM agentos_audit_log GROUP BY risk_level ORDER BY count DESC");
    $byRisk = $stmt->fetchAll();

    // Top capabilities
    $stmt = $pdo->query("SELECT capability_id, COUNT(*) as count, AVG(duration_ms) as avg_ms
        FROM agentos_audit_log WHERE capability_id IS NOT NULL 
        GROUP BY capability_id ORDER BY count DESC LIMIT 15");
    $topCaps = $stmt->fetchAll();

    agentos_respond([
        'ok' => true,
        'overall' => $overall,
        'last_24h' => $last24h,
        'by_action_type' => $byAction,
        'by_risk_level' => $byRisk,
        'top_capabilities' => $topCaps,
    ]);
}

// ═══════════════════════════════════════════════════════════════
// ANOMALIES — Detect suspicious patterns
// ═══════════════════════════════════════════════════════════════
function handleAnomalies(array $auth): void {
    $pdo = agentos_pdo();
    $anomalies = [];

    // 1. High failure rate in last hour
    $stmt = $pdo->query("SELECT agent_id, COUNT(*) as total,
        SUM(CASE WHEN status='failed' THEN 1 ELSE 0 END) as failures
        FROM agentos_audit_log 
        WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        GROUP BY agent_id
        HAVING failures > 5 AND failures/total > 0.5");
    foreach ($stmt->fetchAll() as $row) {
        $anomalies[] = [
            'type' => 'high_failure_rate',
            'severity' => 'high',
            'agent_id' => $row['agent_id'],
            'detail' => "{$row['failures']}/{$row['total']} actions failed in last hour",
        ];
    }

    // 2. Unusual number of critical actions
    $stmt = $pdo->query("SELECT COUNT(*) as critical_count
        FROM agentos_audit_log 
        WHERE risk_level='critical' AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $criticalCount = (int)$stmt->fetchColumn();
    if ($criticalCount > 10) {
        $anomalies[] = [
            'type' => 'critical_spike',
            'severity' => 'critical',
            'detail' => "{$criticalCount} critical-risk actions in last hour",
        ];
    }

    // 3. Kill switch events
    $stmt = $pdo->query("SELECT COUNT(*) FROM agentos_audit_log 
        WHERE action_type='kill_switch' AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $killSwitchCount = (int)$stmt->fetchColumn();
    if ($killSwitchCount > 0) {
        $anomalies[] = [
            'type' => 'kill_switch_used',
            'severity' => 'critical',
            'detail' => "Kill switch activated {$killSwitchCount} time(s) in last 24h",
        ];
    }

    // 4. Policy denials spike
    $stmt = $pdo->query("SELECT COUNT(*) FROM agentos_audit_log 
        WHERE action_type='policy_deny' AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $denials = (int)$stmt->fetchColumn();
    if ($denials > 20) {
        $anomalies[] = [
            'type' => 'policy_denial_spike',
            'severity' => 'medium',
            'detail' => "{$denials} policy denials in last hour",
        ];
    }

    agentos_respond([
        'ok' => true,
        'anomaly_count' => count($anomalies),
        'anomalies' => $anomalies,
    ]);
}

// ═══════════════════════════════════════════════════════════════
// EXPORT — Generate structured report of a task execution
// ═══════════════════════════════════════════════════════════════
function handleExport(array $auth): void {
    $taskId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['task_id'] ?? '');
    if (!$taskId) agentos_error('task_id required');

    $pdo = agentos_pdo();

    // Get task
    $stmt = $pdo->prepare("SELECT * FROM agentos_tasks WHERE task_id=?");
    $stmt->execute([$taskId]);
    $task = $stmt->fetch();
    if (!$task) agentos_error('Task not found', 404);

    // Get audit entries
    $stmt = $pdo->prepare("SELECT * FROM agentos_audit_log WHERE task_id=? ORDER BY created_at, id");
    $stmt->execute([$taskId]);
    $entries = $stmt->fetchAll();

    // Get task nodes
    $stmt = $pdo->prepare("SELECT * FROM agentos_task_nodes WHERE task_id=? ORDER BY id");
    $stmt->execute([$taskId]);
    $nodes = $stmt->fetchAll();

    // Get simulations
    $stmt = $pdo->prepare("SELECT * FROM agentos_simulations WHERE task_id=?");
    $stmt->execute([$taskId]);
    $sims = $stmt->fetchAll();

    // Get approvals
    $stmt = $pdo->prepare("SELECT * FROM agentos_approvals WHERE task_id=?");
    $stmt->execute([$taskId]);
    $approvals = $stmt->fetchAll();

    // Decode JSON fields
    foreach ($entries as &$e) {
        $e['input_summary'] = json_decode($e['input_summary'] ?? 'null', true);
        $e['output_summary'] = json_decode($e['output_summary'] ?? 'null', true);
        $e['metadata'] = json_decode($e['metadata'] ?? 'null', true);
    }
    foreach ($nodes as &$n) {
        $n['input_data'] = json_decode($n['input_data'] ?? 'null', true);
        $n['output_data'] = json_decode($n['output_data'] ?? 'null', true);
    }
    foreach ($sims as &$s) {
        $s['input_state'] = json_decode($s['input_state'] ?? 'null', true);
        $s['anomalies'] = json_decode($s['anomalies'] ?? '[]', true);
    }

    $task['plan'] = json_decode($task['plan'] ?? 'null', true);
    $task['result'] = json_decode($task['result'] ?? 'null', true);

    // Build phases timeline
    $phases = [];
    foreach ($entries as $e) {
        $phases[] = [
            'time' => $e['created_at'],
            'phase' => $e['action_type'],
            'status' => $e['status'],
            'capability' => $e['capability_id'],
            'risk' => $e['risk_level'],
            'duration_ms' => $e['duration_ms'],
        ];
    }

    // Compute stats
    $totalDuration = array_sum(array_column($entries, 'duration_ms'));
    $failedActions = count(array_filter($entries, fn($e) => $e['status'] === 'failed'));
    $successActions = count(array_filter($entries, fn($e) => $e['status'] === 'completed'));

    agentos_respond([
        'ok' => true,
        'export' => [
            'task' => [
                'task_id' => $task['task_id'],
                'goal' => $task['goal'],
                'agent_id' => $task['agent_id'],
                'status' => $task['status'],
                'started_at' => $task['started_at'],
                'completed_at' => $task['completed_at'],
                'plan' => $task['plan'],
                'result' => $task['result'],
            ],
            'stats' => [
                'total_events' => count($entries),
                'total_duration_ms' => $totalDuration,
                'succeeded' => $successActions,
                'failed' => $failedActions,
                'nodes' => count($nodes),
                'simulations' => count($sims),
                'approvals' => count($approvals),
            ],
            'phases' => $phases,
            'nodes' => $nodes,
            'simulations' => $sims,
            'approvals' => $approvals,
            'audit_log' => $entries,
        ],
    ]);
}

// ═══════════════════════════════════════════════════════════════
// TIMELINE — System-wide event timeline
// ═══════════════════════════════════════════════════════════════
function handleTimeline(array $auth): void {
    $hours = min((int)($_GET['hours'] ?? 24), 168);
    $limit = min((int)($_GET['limit'] ?? 200), 1000);

    $pdo = agentos_pdo();

    // Audit events
    $stmt = $pdo->prepare("SELECT 'audit' as source, action_type as event, agent_id, task_id,
        capability_id, status, risk_level, duration_ms, created_at as timestamp
        FROM agentos_audit_log 
        WHERE created_at > DATE_SUB(NOW(), INTERVAL " . (int)$hours . " HOUR)
        ORDER BY created_at DESC LIMIT " . (int)$limit);
    $stmt->execute();
    $events = $stmt->fetchAll();

    // Recent tasks
    $stmt = $pdo->prepare("SELECT task_id, goal, agent_id, status, started_at, completed_at
        FROM agentos_tasks 
        WHERE started_at > DATE_SUB(NOW(), INTERVAL " . (int)$hours . " HOUR)
        ORDER BY started_at DESC LIMIT 50");
    $stmt->execute();
    $tasks = $stmt->fetchAll();

    // Pending approvals
    $stmt = $pdo->query("SELECT approval_id, task_id, capability_id, risk_level, 
        action_summary, created_at, expires_at
        FROM agentos_approvals WHERE status='pending' ORDER BY created_at DESC");
    $pendingApprovals = $stmt->fetchAll();

    agentos_respond([
        'ok' => true,
        'hours' => $hours,
        'events' => $events,
        'tasks' => $tasks,
        'pending_approvals' => $pendingApprovals,
    ]);
}
