<?php
/**
 * Alfred Agent Delegation Protocol — Phase 1: Autonomy Foundation
 * ────────────────────────────────────────────────────────────────
 * Smart task routing: Alfred → Director → Specialist.
 * Supports 4 delegation strategies: parallel, pipeline, consensus, competition.
 * Handles multi-agent orchestration, result aggregation, and escalation.
 *
 * Endpoints:
 *   POST ?action=delegate         → Delegate a task with smart routing
 *   POST ?action=multi-delegate   → Delegate to multiple agents
 *   GET  ?action=route            → Find best agent for a task (dry-run)
 *   POST ?action=escalate         → Escalate a failed task to parent
 *   POST ?action=aggregate        → Aggregate results from strategy execution
 *   GET  ?action=queue            → View delegation queue
 *   GET  ?action=chain            → View delegation chain for a task
 *   GET  ?action=strategies       → List strategy descriptions
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Internal-Secret, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

requireCSRF();
apiRateLimit(30, 60, 'delegation');

function requireAuth() {
    if (empty($_SESSION['logged_in']) || empty($_SESSION['client_id'])) {
        jsonResponse(['error' => 'Authentication required'], 401);
    }
}

function isAdmin() {
    return !empty($_SESSION['is_admin']) || ($_SESSION['client_id'] ?? 0) === 33;
}

function isInternalCall() {
    $secret = getenv('INTERNAL_SECRET') ?: '';
    return $secret && isset($_SERVER['HTTP_X_INTERNAL_SECRET']) && hash_equals($secret, $_SERVER['HTTP_X_INTERNAL_SECRET']);
}

// ─── Domain → Agent Routing Map ────────────────────────────────────
// Maps task domains/keywords to director agents, with specialist fallbacks
$ROUTING_MAP = [
    'code'       => ['director' => 'NOVA',      'specialists' => ['NOVA-BUILDER', 'NOVA-DEBUGGER', 'NOVA-REVIEWER', 'NOVA-TESTER', 'NOVA-REFACTORER']],
    'develop'    => ['director' => 'NOVA',      'specialists' => ['NOVA-BUILDER', 'NOVA-DEBUGGER', 'NOVA-REVIEWER']],
    'build'      => ['director' => 'NOVA',      'specialists' => ['NOVA-BUILDER', 'NOVA-DEPLOYER']],
    'debug'      => ['director' => 'NOVA',      'specialists' => ['NOVA-DEBUGGER', 'NOVA-TESTER']],
    'test'       => ['director' => 'NOVA',      'specialists' => ['NOVA-TESTER']],
    'deploy'     => ['director' => 'NOVA',      'specialists' => ['NOVA-DEPLOYER']],

    'security'   => ['director' => 'CIPHER',    'specialists' => ['CIPHER-SENTINEL', 'CIPHER-WATCHDOG', 'CIPHER-ENCRYPTOR', 'CIPHER-AUDITOR']],
    'encrypt'    => ['director' => 'CIPHER',    'specialists' => ['CIPHER-ENCRYPTOR']],
    'audit'      => ['director' => 'CIPHER',    'specialists' => ['CIPHER-AUDITOR']],
    'threat'     => ['director' => 'CIPHER',    'specialists' => ['CIPHER-SENTINEL', 'CIPHER-WATCHDOG']],

    'research'   => ['director' => 'SAGE',      'specialists' => ['SAGE-RESEARCHER', 'SAGE-CRAWLER', 'SAGE-SCHOLAR']],
    'search'     => ['director' => 'SAGE',      'specialists' => ['SAGE-CRAWLER', 'SAGE-RESEARCHER']],
    'legal'      => ['director' => 'SAGE',      'specialists' => ['SAGE-SCHOLAR', 'SAGE-WRITER']],
    'write'      => ['director' => 'SAGE',      'specialists' => ['SAGE-WRITER', 'SAGE-TRANSLATOR']],
    'translate'  => ['director' => 'SAGE',      'specialists' => ['SAGE-TRANSLATOR']],

    'finance'    => ['director' => 'ATLAS',     'specialists' => ['ATLAS-TREASURER', 'ATLAS-FORECASTER', 'ATLAS-ACCOUNTANT', 'ATLAS-TRADER']],
    'invoice'    => ['director' => 'ATLAS',     'specialists' => ['ATLAS-INVOICER', 'ATLAS-COLLECTOR']],
    'payment'    => ['director' => 'ATLAS',     'specialists' => ['ATLAS-PAYMASTER', 'ATLAS-TREASURER']],
    'trade'      => ['director' => 'ATLAS',     'specialists' => ['ATLAS-TRADER']],
    'budget'     => ['director' => 'ATLAS',     'specialists' => ['ATLAS-TREASURER', 'ATLAS-UNDERWRITER']],

    'user'       => ['director' => 'PULSE',     'specialists' => ['PULSE-DISPATCHER', 'PULSE-ONBOARDER', 'PULSE-RETENTION']],
    'support'    => ['director' => 'PULSE',     'specialists' => ['PULSE-DISPATCHER', 'PULSE-ESCALATOR']],
    'onboard'    => ['director' => 'PULSE',     'specialists' => ['PULSE-ONBOARDER']],
    'notify'     => ['director' => 'PULSE',     'specialists' => ['PULSE-BROADCASTER']],

    'server'     => ['director' => 'ARCHITECT', 'specialists' => ['ARCHITECT-MONITOR', 'ARCHITECT-SCALER', 'ARCHITECT-NETOPS']],
    'infra'      => ['director' => 'ARCHITECT', 'specialists' => ['ARCHITECT-SCALER', 'ARCHITECT-MONITOR', 'ARCHITECT-NETOPS']],
    'backup'     => ['director' => 'ARCHITECT', 'specialists' => ['ARCHITECT-BACKUP']],
    'database'   => ['director' => 'ARCHITECT', 'specialists' => ['ARCHITECT-DBA']],
    'scale'      => ['director' => 'ARCHITECT', 'specialists' => ['ARCHITECT-SCALER']],

    'marketing'  => ['director' => 'HERALD',    'specialists' => ['HERALD-SEO', 'HERALD-ADVERTISER', 'HERALD-SOCIAL']],
    'seo'        => ['director' => 'HERALD',    'specialists' => ['HERALD-SEO']],
    'email'      => ['director' => 'HERALD',    'specialists' => ['HERALD-EMAILER']],
    'brand'      => ['director' => 'HERALD',    'specialists' => ['HERALD-BRANDER']],

    'analytics'  => ['director' => 'ORACLE',    'specialists' => ['ORACLE-ANALYST', 'ORACLE-SURVEYOR', 'ORACLE-PREDICTOR']],
    'data'       => ['director' => 'ORACLE',    'specialists' => ['ORACLE-ANALYST', 'ORACLE-MINER']],
    'predict'    => ['director' => 'ORACLE',    'specialists' => ['ORACLE-PREDICTOR']],

    'design'     => ['director' => 'EMBER',     'specialists' => ['EMBER-UIUX', 'EMBER-VISUALIZER', 'EMBER-ANIMATOR']],
    'ui'         => ['director' => 'EMBER',     'specialists' => ['EMBER-UIUX']],
    'image'      => ['director' => 'EMBER',     'specialists' => ['EMBER-VISUALIZER']],
    'video'      => ['director' => 'EMBER',     'specialists' => ['EMBER-VIDEOGRAPHER']],
    'audio'      => ['director' => 'EMBER',     'specialists' => ['EMBER-SOUNDSMITH']],

    'robot'      => ['director' => 'VANGUARD',  'specialists' => ['VANGUARD-NAVIGATOR', 'VANGUARD-MANIPULATOR', 'VANGUARD-TWIN']],
    'navigate'   => ['director' => 'VANGUARD',  'specialists' => ['VANGUARD-NAVIGATOR']],
    'hardware'   => ['director' => 'VANGUARD',  'specialists' => ['VANGUARD-MACHINIST', 'VANGUARD-ELECTRICIAN']],
];

// ─── Smart Router ──────────────────────────────────────────────────
function findBestAgent($taskGoal, $domain = null) {
    global $ROUTING_MAP;
    $db = getDB();

    // If domain explicitly provided, use it
    if ($domain && isset($ROUTING_MAP[$domain])) {
        $route = $ROUTING_MAP[$domain];
        // Find best available specialist by success rate
        return selectAvailableAgent($route['specialists'], $route['director']);
    }

    // Keyword matching on task goal
    $goalLower = strtolower($taskGoal);
    $matches = [];

    foreach ($ROUTING_MAP as $keyword => $route) {
        if (strpos($goalLower, $keyword) !== false) {
            $matches[$keyword] = $route;
        }
    }

    if (empty($matches)) {
        // Default: Alfred handles it directly, or round-robin to least-busy director
        return ['agent_id' => 'ALFRED', 'reason' => 'No domain match — Alfred handles directly'];
    }

    // Use the first (most specific) match
    $route = reset($matches);
    $matchedKeyword = key($matches);

    return selectAvailableAgent($route['specialists'], $route['director'], $matchedKeyword);
}

function selectAvailableAgent($specialists, $directorFallback, $keyword = '') {
    $db = getDB();

    // Try specialists first — prefer idle agents with highest success rate
    if (!empty($specialists)) {
        $placeholders = implode(',', array_fill(0, count($specialists), '?'));
        $stmt = $db->prepare("SELECT agent_id, status, success_rate, tasks_completed FROM alfred_agent_registry WHERE agent_id IN ({$placeholders}) AND status IN ('idle', 'active') ORDER BY success_rate DESC, tasks_completed ASC LIMIT 1");
        $stmt->execute($specialists);
        $agent = $stmt->fetch();

        if ($agent) {
            return [
                'agent_id' => $agent['agent_id'],
                'role' => 'specialist',
                'success_rate' => $agent['success_rate'],
                'reason' => "Best available specialist (keyword: {$keyword})",
            ];
        }
    }

    // Fallback to Director
    $stmt = $db->prepare("SELECT agent_id, status, success_rate FROM alfred_agent_registry WHERE agent_id = ?");
    $stmt->execute([$directorFallback]);
    $director = $stmt->fetch();

    if ($director) {
        return [
            'agent_id' => $director['agent_id'],
            'role' => 'director',
            'success_rate' => $director['success_rate'],
            'reason' => "Director fallback — specialists busy (keyword: {$keyword})",
        ];
    }

    return ['agent_id' => 'ALFRED', 'reason' => 'No available agents in domain'];
}

// ─── Create Task ───────────────────────────────────────────────────
function createDelegationTask($assignedAgent, $delegatedBy, $goal, $strategy, $priority, $inputData = null) {
    $db = getDB();
    $taskId = 'TASK-' . strtoupper(bin2hex(random_bytes(6)));

    $stmt = $db->prepare("INSERT INTO alfred_agent_tasks (task_id, assigned_agent, delegated_by, goal, strategy, priority, input_data) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $taskId, $assignedAgent, $delegatedBy, $goal,
        $strategy, $priority,
        $inputData ? json_encode($inputData) : null,
    ]);

    // Update agent status
    $db->prepare("UPDATE alfred_agent_registry SET status = 'busy', current_task = ? WHERE agent_id = ? AND status IN ('idle', 'active')")->execute([$taskId, $assignedAgent]);

    // Log delegation message
    $db->prepare("INSERT INTO alfred_agent_messages (from_agent, to_agent, message_type, payload) VALUES (?, ?, 'task', ?)")->execute([
        $delegatedBy, $assignedAgent,
        json_encode(['task_id' => $taskId, 'goal' => $goal, 'strategy' => $strategy]),
    ]);

    return $taskId;
}

// ─── Router ────────────────────────────────────────────────────────
$action = sanitize($_GET['action'] ?? '', 30);
$db = getDB();

if (!$db) jsonResponse(['error' => 'Database unavailable'], 503);

switch ($action) {

    // ── Smart Delegate ──────────────────────────────────────────────
    case 'delegate':
        if (!isInternalCall()) {
            requireAuth();
            if (!isAdmin()) jsonResponse(['error' => 'Admin access required'], 403);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) jsonResponse(['error' => 'JSON body required'], 400);

        $goal = sanitize($input['goal'] ?? '', 500);
        if (!$goal) jsonResponse(['error' => 'goal is required'], 400);

        $strategy = sanitize($input['strategy'] ?? 'parallel', 20);
        if (!in_array($strategy, ['parallel', 'pipeline', 'consensus', 'competition'])) $strategy = 'parallel';

        $priority = min(max(intval($input['priority'] ?? 5), 1), 10);
        $delegatedBy = sanitize($input['delegated_by'] ?? 'ALFRED', 50);
        $domain = sanitize($input['domain'] ?? '', 30) ?: null;
        $forceAgent = sanitize($input['agent_id'] ?? '', 50) ?: null;

        // Find best agent
        if ($forceAgent) {
            $route = ['agent_id' => $forceAgent, 'reason' => 'Explicitly assigned'];
        } else {
            $route = findBestAgent($goal, $domain);
        }

        // Create the task
        $taskId = createDelegationTask(
            $route['agent_id'], $delegatedBy, $goal, $strategy, $priority,
            $input['input_data'] ?? null
        );

        jsonResponse([
            'success' => true,
            'task_id' => $taskId,
            'assigned_to' => $route['agent_id'],
            'routing_reason' => $route['reason'] ?? 'direct assignment',
            'strategy' => $strategy,
            'priority' => $priority,
        ]);
        break;

    // ── Multi-Delegate (parallel tasks to multiple agents) ──────────
    case 'multi-delegate':
        if (!isInternalCall()) {
            requireAuth();
            if (!isAdmin()) jsonResponse(['error' => 'Admin access required'], 403);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $tasks = $input['tasks'] ?? [];
        if (empty($tasks) || !is_array($tasks)) jsonResponse(['error' => 'tasks array required'], 400);
        if (count($tasks) > 20) jsonResponse(['error' => 'Maximum 20 tasks per batch'], 400);

        $strategy = sanitize($input['strategy'] ?? 'parallel', 20);
        $delegatedBy = sanitize($input['delegated_by'] ?? 'ALFRED', 50);
        $results = [];

        foreach ($tasks as $task) {
            $goal = sanitize($task['goal'] ?? '', 500);
            if (!$goal) continue;

            $domain = sanitize($task['domain'] ?? '', 30) ?: null;
            $forceAgent = sanitize($task['agent_id'] ?? '', 50) ?: null;
            $priority = min(max(intval($task['priority'] ?? 5), 1), 10);

            $route = $forceAgent
                ? ['agent_id' => $forceAgent, 'reason' => 'explicit']
                : findBestAgent($goal, $domain);

            $taskId = createDelegationTask(
                $route['agent_id'], $delegatedBy, $goal, $strategy, $priority,
                $task['input_data'] ?? null
            );

            $results[] = [
                'task_id' => $taskId,
                'goal' => $goal,
                'assigned_to' => $route['agent_id'],
                'reason' => $route['reason'] ?? 'direct',
            ];
        }

        jsonResponse([
            'success' => true,
            'strategy' => $strategy,
            'delegated' => count($results),
            'tasks' => $results,
        ]);
        break;

    // ── Route (dry-run) ─────────────────────────────────────────────
    case 'route':
        if (!isInternalCall()) requireAuth();

        $goal = sanitize($_GET['goal'] ?? '', 500);
        $domain = sanitize($_GET['domain'] ?? '', 30) ?: null;

        if (!$goal) jsonResponse(['error' => 'goal parameter required'], 400);

        $route = findBestAgent($goal, $domain);

        jsonResponse([
            'success' => true,
            'dry_run' => true,
            'goal' => $goal,
            'recommended_agent' => $route['agent_id'],
            'agent_role' => $route['role'] ?? 'commander',
            'success_rate' => $route['success_rate'] ?? null,
            'reason' => $route['reason'],
        ]);
        break;

    // ── Escalate ────────────────────────────────────────────────────
    case 'escalate':
        if (!isInternalCall()) {
            requireAuth();
            if (!isAdmin()) jsonResponse(['error' => 'Admin access required'], 403);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $taskId = sanitize($input['task_id'] ?? '', 50);
        if (!$taskId) jsonResponse(['error' => 'task_id required'], 400);

        // Get the original task
        $stmt = $db->prepare("SELECT * FROM alfred_agent_tasks WHERE task_id = ?");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch();

        if (!$task) jsonResponse(['error' => 'Task not found'], 404);

        // Mark original as failed
        $db->prepare("UPDATE alfred_agent_tasks SET status = 'failed', error_message = ? WHERE task_id = ?")->execute([
            sanitize($input['reason'] ?? 'Escalated to parent', 500), $taskId
        ]);

        // Update agent stats
        $db->prepare("UPDATE alfred_agent_registry SET status = 'idle', current_task = NULL, tasks_completed = tasks_completed + 1, success_rate = GREATEST(0, success_rate - 1) WHERE agent_id = ?")->execute([$task['assigned_agent']]);

        // Find parent agent
        $stmt = $db->prepare("SELECT parent_agent_id FROM alfred_agent_registry WHERE agent_id = ?");
        $stmt->execute([$task['assigned_agent']]);
        $parent = $stmt->fetchColumn();

        if (!$parent) $parent = 'ALFRED'; // Ultimate fallback

        // Create new task for parent
        $newTaskId = createDelegationTask(
            $parent, $task['assigned_agent'], $task['goal'], $task['strategy'],
            min($task['priority'] + 1, 10), // Increase priority
            json_decode($task['input_data'], true)
        );

        // Log escalation message
        $db->prepare("INSERT INTO alfred_agent_messages (from_agent, to_agent, message_type, payload) VALUES (?, ?, 'alert', ?)")->execute([
            $task['assigned_agent'], $parent,
            json_encode(['type' => 'escalation', 'original_task' => $taskId, 'new_task' => $newTaskId, 'reason' => $input['reason'] ?? 'Agent escalated']),
        ]);

        jsonResponse([
            'success' => true,
            'original_task' => $taskId,
            'escalated_to' => $parent,
            'new_task_id' => $newTaskId,
            'new_priority' => min($task['priority'] + 1, 10),
        ]);
        break;

    // ── Aggregate Results ───────────────────────────────────────────
    case 'aggregate':
        if (!isInternalCall()) {
            requireAuth();
            if (!isAdmin()) jsonResponse(['error' => 'Admin access required'], 403);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $taskIds = $input['task_ids'] ?? [];
        if (empty($taskIds)) jsonResponse(['error' => 'task_ids array required'], 400);

        $placeholders = implode(',', array_fill(0, count($taskIds), '?'));
        $stmt = $db->prepare("SELECT task_id, assigned_agent, status, output_data, error_message FROM alfred_agent_tasks WHERE task_id IN ({$placeholders})");
        $stmt->execute($taskIds);
        $tasks = $stmt->fetchAll();

        $completed = array_filter($tasks, fn($t) => $t['status'] === 'completed');
        $failed = array_filter($tasks, fn($t) => $t['status'] === 'failed');
        $pending = array_filter($tasks, fn($t) => in_array($t['status'], ['queued', 'running']));

        foreach ($tasks as &$t) {
            $t['output_data'] = json_decode($t['output_data'], true);
        }

        $strategy = sanitize($input['strategy'] ?? 'parallel', 20);
        $aggregated = null;

        switch ($strategy) {
            case 'consensus':
                // All must complete successfully
                $aggregated = [
                    'consensus_reached' => count($failed) === 0 && count($pending) === 0,
                    'agreement_rate' => count($tasks) > 0 ? round(count($completed) / count($tasks) * 100, 1) : 0,
                ];
                break;
            case 'competition':
                // First completed result wins
                $winner = !empty($completed) ? reset($completed) : null;
                $aggregated = [
                    'winner' => $winner ? $winner['assigned_agent'] : null,
                    'winning_result' => $winner ? json_decode($winner['output_data'], true) : null,
                ];
                break;
            default: // parallel, pipeline
                $aggregated = ['results_collected' => count($completed)];
        }

        jsonResponse([
            'success' => true,
            'strategy' => $strategy,
            'total' => count($tasks),
            'completed' => count($completed),
            'failed' => count($failed),
            'pending' => count($pending),
            'aggregated' => $aggregated,
            'tasks' => $tasks,
        ]);
        break;

    // ── View Queue ──────────────────────────────────────────────────
    case 'queue':
        if (!isInternalCall()) requireAuth();

        $status = sanitize($_GET['status'] ?? '', 20);
        $sql = "SELECT t.*, r.agent_name, r.agent_role FROM alfred_agent_tasks t LEFT JOIN alfred_agent_registry r ON t.assigned_agent = r.agent_id WHERE 1=1";
        $params = [];

        if ($status && in_array($status, ['queued', 'running', 'completed', 'failed', 'cancelled'])) {
            $sql .= " AND t.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY t.priority DESC, t.created_at ASC LIMIT 50";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $tasks = $stmt->fetchAll();

        foreach ($tasks as &$t) {
            $t['input_data'] = json_decode($t['input_data'], true);
            $t['output_data'] = json_decode($t['output_data'], true);
        }

        jsonResponse(['success' => true, 'tasks' => $tasks, 'total' => count($tasks)]);
        break;

    // ── Delegation Chain ────────────────────────────────────────────
    case 'chain':
        if (!isInternalCall()) requireAuth();

        $taskId = sanitize($_GET['task_id'] ?? '', 50);
        if (!$taskId) jsonResponse(['error' => 'task_id parameter required'], 400);

        // Find the task and its delegation history via messages
        $stmt = $db->prepare("SELECT * FROM alfred_agent_tasks WHERE task_id = ?");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch();

        if (!$task) jsonResponse(['error' => 'Task not found'], 404);

        // Get all messages related to this task
        $stmt = $db->prepare("SELECT * FROM alfred_agent_messages WHERE JSON_EXTRACT(payload, '$.task_id') = ? OR JSON_EXTRACT(payload, '$.original_task') = ? ORDER BY created_at ASC");
        $stmt->execute([$taskId, $taskId]);
        $messages = $stmt->fetchAll();

        foreach ($messages as &$m) $m['payload'] = json_decode($m['payload'], true);

        $task['input_data'] = json_decode($task['input_data'], true);
        $task['output_data'] = json_decode($task['output_data'], true);

        jsonResponse([
            'success' => true,
            'task' => $task,
            'delegation_chain' => $messages,
            'chain_length' => count($messages),
        ]);
        break;

    // ── Strategies ──────────────────────────────────────────────────
    case 'strategies':
        jsonResponse([
            'success' => true,
            'strategies' => [
                'parallel' => [
                    'name' => 'Parallel',
                    'description' => 'All agents work simultaneously. Results collected individually.',
                    'use_case' => 'Independent sub-tasks (e.g., "scan 5 servers for vulnerabilities")',
                ],
                'pipeline' => [
                    'name' => 'Pipeline',
                    'description' => 'Tasks flow sequentially — output of agent N becomes input of agent N+1.',
                    'use_case' => 'Multi-step workflows (e.g., "research → write → review → publish")',
                ],
                'consensus' => [
                    'name' => 'Consensus',
                    'description' => 'All agents must agree on an answer. Requires unanimous completion.',
                    'use_case' => 'Critical decisions (e.g., "should we deploy this to production?")',
                ],
                'competition' => [
                    'name' => 'Competition',
                    'description' => 'Multiple agents race — first successful result wins.',
                    'use_case' => 'Speed-critical tasks (e.g., "find the best deal from 3 sources")',
                ],
            ],
        ]);
        break;

    default:
        jsonResponse([
            'error' => 'Unknown action',
            'available_actions' => ['delegate', 'multi-delegate', 'route', 'escalate', 'aggregate', 'queue', 'chain', 'strategies'],
        ], 400);
}
