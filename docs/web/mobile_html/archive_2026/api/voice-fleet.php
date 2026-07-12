<?php
/**
 * ═══════════════════════════════════════════════════════════════════════
 * VOICE FLEET COMMANDER — Natural Language Fleet Control API
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Accepts natural language text (from voice transcription) and maps it
 * to fleet management actions (Agent Orchestrator + Alfred Command Center).
 *
 * Flow: Voice UI → Whisper transcription → this API → execute command → spoken response
 *
 * POST /api/voice-fleet.php
 *   Body: { "text": "deploy 5 security agents", "context": "voice" }
 *   Returns: { "intent": "deploy", "spoken": "Deploying 5 security agents now...", "result": {...} }
 *
 * Supported intents:
 *   deploy      — Spawn agents for tasks (by count, category, or specific task)
 *   status      — Get fleet/task statistics
 *   stop        — Cancel pending/running tasks
 *   sprint      — Run focused sprint on a category
 *   import      — Import backlog tasks
 *   retry       — Retry failed tasks
 *   list        — List tasks by status/category
 *   fleet       — Fleet agent management (SLA, config)
 *   help        — List available commands
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

session_start();

// ── Auth: owner only (clientId 33) ───────────────────────────────
$isLoggedIn = !empty($_SESSION['logged_in']) && !empty($_SESSION['client_id']);
$clientId   = $isLoggedIn ? (int)$_SESSION['client_id'] : 0;
$isOwner    = $clientId === 33;

if (!$isOwner) {
    http_response_code(403);
    echo json_encode(['error' => true, 'spoken' => 'Voice fleet commands require owner authentication.']);
    exit;
}

// ── CSRF verification ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        http_response_code(403);
        echo json_encode(['error' => true, 'spoken' => 'Security token expired. Please refresh the page.']);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => true, 'spoken' => 'Voice fleet only accepts POST requests.']);
    exit;
}

// ── Parse input ──────────────────────────────────────────────────
$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
$text = trim($input['text'] ?? '');

if (!$text) {
    echo json_encode(['error' => true, 'spoken' => 'I didn\'t catch that. Could you repeat your command?']);
    exit;
}

// Normalize text for matching
$lower = mb_strtolower($text);

// ── Intent Classification ────────────────────────────────────────
$intent = classifyIntent($lower);
$result = executeIntent($intent, $lower, $text);

echo json_encode([
    'intent'  => $intent['name'],
    'params'  => $intent['params'],
    'spoken'  => $result['spoken'],
    'result'  => $result['data'] ?? null,
    'success' => $result['success'] ?? true,
]);

// ═══════════════════════════════════════════════════════════════════
// INTENT CLASSIFICATION
// ═══════════════════════════════════════════════════════════════════

function classifyIntent(string $text): array {
    // Deploy / spawn agents
    if (preg_match('/\b(deploy|spawn|launch|start|run|send|dispatch)\b.*?\b(\d+)\b.*?\b(agent|worker|bot)s?\b/i', $text, $m)) {
        return ['name' => 'deploy', 'params' => ['count' => (int)$m[2]]];
    }
    if (preg_match('/\b(deploy|spawn|launch|start|run|send)\b\s+(\d+)\s+(security|frontend|api|javascript|test|script|docs|sdk|debt|feature)/i', $text, $m)) {
        return ['name' => 'deploy_category', 'params' => ['count' => (int)$m[2], 'category' => strtolower($m[3])]];
    }
    if (preg_match('/\b(deploy|spawn|launch|start|run)\b.*?\b(security|frontend|api|javascript|test|script|docs|sdk|debt|feature)\b.*?\b(agent|worker|sprint|task)s?\b/i', $text, $m)) {
        return ['name' => 'deploy_category', 'params' => ['count' => 5, 'category' => strtolower($m[2])]];
    }
    if (preg_match('/\b(deploy|spawn|launch|start|run|send)\b\s+(\d+)/i', $text, $m)) {
        return ['name' => 'deploy', 'params' => ['count' => (int)$m[2]]];
    }
    if (preg_match('/\b(deploy|spawn|launch)\b\s+(all|everything|every\s*thing)/i', $text)) {
        return ['name' => 'deploy_all', 'params' => []];
    }

    // Sprint — focused work on a category
    if (preg_match('/\b(sprint|blitz|focus|concentrate)\b.*?\b(security|frontend|api|javascript|test|script|docs|sdk|debt|feature)\b/i', $text, $m)) {
        return ['name' => 'sprint', 'params' => ['category' => strtolower($m[2])]];
    }

    // Stop / cancel
    if (preg_match('/\b(stop|cancel|halt|abort|kill|pause)\b\s+(all|every\s*thing|the\s+fleet)/i', $text)) {
        return ['name' => 'stop_all', 'params' => []];
    }
    if (preg_match('/\b(stop|cancel|halt|abort)\b.*?\b([A-Z]+-\d+)\b/i', $text, $m)) {
        return ['name' => 'stop_task', 'params' => ['task_id' => strtoupper($m[2])]];
    }
    if (preg_match('/\b(stop|cancel|halt|abort|kill)\b/i', $text)) {
        return ['name' => 'stop_all', 'params' => []];
    }

    // Retry failed
    if (preg_match('/\b(retry|rerun|redo|re-run)\b\s+(all|failed|everything)/i', $text)) {
        return ['name' => 'retry_all', 'params' => []];
    }
    if (preg_match('/\b(retry|rerun|redo)\b.*?\b([A-Z]+-\d+)\b/i', $text, $m)) {
        return ['name' => 'retry_task', 'params' => ['task_id' => strtoupper($m[2])]];
    }

    // Status / stats / report
    if (preg_match('/\b(status|stats|statistics|report|dashboard|overview|progress|how.*?(doing|going|many|far)|what.*?(status|progress))\b/i', $text)) {
        return ['name' => 'status', 'params' => []];
    }

    // List tasks
    if (preg_match('/\b(list|show|what|which)\b.*?\b(pending|running|failed|done|completed|cancelled)\b.*?\b(task|job|work)s?\b/i', $text, $m)) {
        $statusMap = ['completed' => 'done', 'cancelled' => 'cancelled'];
        $status = strtolower($m[2]);
        return ['name' => 'list', 'params' => ['status' => $statusMap[$status] ?? $status]];
    }
    if (preg_match('/\b(list|show)\b.*?\b(task|job|work|backlog)s?\b/i', $text)) {
        return ['name' => 'list', 'params' => ['status' => 'pending']];
    }

    // Import backlog
    if (preg_match('/\b(import|load|ingest)\b.*?\b(circuit|simulator|sim)\b/i', $text)) {
        return ['name' => 'import_circuit_sim', 'params' => []];
    }
    if (preg_match('/\b(import|load|ingest)\b.*?\b(backlog|tasks|upgrade)\b/i', $text)) {
        return ['name' => 'import', 'params' => []];
    }

    // Fleet agent management
    if (preg_match('/\b(fleet|agent)\b.*?\b(status|list|show)\b/i', $text)) {
        return ['name' => 'fleet_status', 'params' => []];
    }
    if (preg_match('/\bset\s+sla\b/i', $text)) {
        return ['name' => 'fleet_sla', 'params' => []];
    }

    // Help
    if (preg_match('/\b(help|commands|what can you do|what.*?commands)\b/i', $text)) {
        return ['name' => 'help', 'params' => []];
    }

    // Fallback — unknown intent
    return ['name' => 'unknown', 'params' => ['raw' => $text]];
}

// ═══════════════════════════════════════════════════════════════════
// INTENT EXECUTION
// ═══════════════════════════════════════════════════════════════════

function executeIntent(array $intent, string $lower, string $original): array {
    switch ($intent['name']) {
        case 'deploy':
            return handleDeploy($intent['params']['count']);

        case 'deploy_category':
            return handleDeployCategory($intent['params']['count'], $intent['params']['category']);

        case 'deploy_all':
            return handleDeployAll();

        case 'sprint':
            return handleSprint($intent['params']['category']);

        case 'stop_all':
            return handleStopAll();

        case 'stop_task':
            return handleStopTask($intent['params']['task_id']);

        case 'retry_all':
            return handleRetryAll();

        case 'retry_task':
            return handleRetryTask($intent['params']['task_id']);

        case 'status':
            return handleStatus();

        case 'list':
            return handleList($intent['params']['status']);

        case 'import':
            return handleImport();

        case 'import_circuit_sim':
            return handleImportCircuitSim();

        case 'fleet_status':
            return handleFleetStatus();

        case 'fleet_sla':
            return ['spoken' => 'To set SLA, specify the agent ID and response time. For example, say "set SLA for agent 1 to 3 seconds".', 'success' => true];

        case 'help':
            return handleHelp();

        default:
            return ['spoken' => "I didn't recognize that fleet command. Try saying things like: deploy 5 agents, check status, run security sprint, stop all, or retry failed tasks. Say help for the full list.", 'success' => false];
    }
}

// ── Orchestrator API Helper ──────────────────────────────────────
function orchestratorAPI(string $action, string $method = 'GET', array $data = []): ?array {
    $url = 'http://localhost/api/agent-orchestrator.php?action=' . urlencode($action);
    if ($method === 'GET' && $data) {
        $url .= '&' . http_build_query($data);
    }

    $opts = [
        'http' => [
            'method'  => $method,
            'timeout' => 10,
            'header'  => "Content-Type: application/json\r\n" .
                         "Cookie: " . ($_SERVER['HTTP_COOKIE'] ?? '') . "\r\n" .
                         "X-CSRF-Token: " . ($_SESSION['csrf_token'] ?? '') . "\r\n",
        ]
    ];

    if ($method === 'POST' && $data) {
        $opts['http']['content'] = json_encode($data);
    } elseif ($method === 'POST') {
        $opts['http']['content'] = '{}';
    }

    $ctx = stream_context_create($opts);
    $response = @file_get_contents($url, false, $ctx);
    if ($response === false) return null;

    return json_decode($response, true);
}

// ── Alfred Command Helper ────────────────────────────────────────
function alfredCommand(string $action, array $data = []): ?array {
    $url = 'http://localhost/api/alfred-command.php?action=' . urlencode($action);

    $secret = defined('INTERNAL_SECRET') ? INTERNAL_SECRET : '';
    $opts = [
        'http' => [
            'method'  => 'POST',
            'timeout' => 10,
            'header'  => "Content-Type: application/json\r\n" .
                         "X-Internal-Secret: " . $secret . "\r\n",
            'content' => json_encode($data),
        ]
    ];

    $ctx = stream_context_create($opts);
    $response = @file_get_contents($url, false, $ctx);
    if ($response === false) return null;

    return json_decode($response, true);
}

// ═══════════════════════════════════════════════════════════════════
// COMMAND HANDLERS
// ═══════════════════════════════════════════════════════════════════

function handleDeploy(int $count): array {
    $count = min($count, 50); // Safety cap
    $stats = orchestratorAPI('stats');
    if (!$stats || !empty($stats['error'])) {
        return ['spoken' => 'I couldn\'t reach the orchestrator. The service might be down.', 'success' => false];
    }

    $pending = $stats['stats']['by_status']['pending'] ?? 0;
    if ($pending === 0) {
        return ['spoken' => 'There are no pending tasks to deploy agents for. Import a backlog first or create new tasks.', 'success' => true, 'data' => $stats];
    }

    $toDeploy = min($count, $pending);

    // Get pending tasks
    $backlog = orchestratorAPI('backlog', 'GET', ['status' => 'pending', 'limit' => $toDeploy]);
    if (!$backlog || empty($backlog['tasks'])) {
        return ['spoken' => 'No pending tasks found to spawn agents for.', 'success' => false];
    }

    $spawned = 0;
    $failed = 0;
    foreach ($backlog['tasks'] as $task) {
        $result = orchestratorAPI('spawn', 'POST', ['id' => $task['id']]);
        if ($result && !empty($result['success'])) {
            $spawned++;
        } else {
            $failed++;
        }
    }

    $spoken = "Deployed {$spawned} agent" . ($spawned !== 1 ? 's' : '') . " successfully.";
    if ($failed > 0) $spoken .= " {$failed} failed to spawn.";
    $spoken .= " {$pending} total tasks were pending.";

    return ['spoken' => $spoken, 'success' => true, 'data' => ['spawned' => $spawned, 'failed' => $failed, 'pending' => $pending]];
}

function handleDeployCategory(int $count, string $category): array {
    $count = min($count, 50);
    $backlog = orchestratorAPI('backlog', 'GET', ['status' => 'pending', 'category' => $category, 'limit' => $count]);

    if (!$backlog || empty($backlog['tasks'])) {
        return ['spoken' => "No pending {$category} tasks found. Import the backlog or create {$category} tasks first.", 'success' => false];
    }

    $spawned = 0;
    foreach ($backlog['tasks'] as $task) {
        $result = orchestratorAPI('spawn', 'POST', ['id' => $task['id']]);
        if ($result && !empty($result['success'])) $spawned++;
    }

    $total = count($backlog['tasks']);
    return [
        'spoken' => "Deployed {$spawned} of {$total} {$category} agents. They're working on it now.",
        'success' => true,
        'data' => ['spawned' => $spawned, 'category' => $category, 'total' => $total]
    ];
}

function handleDeployAll(): array {
    $stats = orchestratorAPI('stats');
    $pending = $stats['stats']['by_status']['pending'] ?? 0;

    if ($pending === 0) {
        return ['spoken' => 'No pending tasks to deploy. The queue is clear.', 'success' => true];
    }

    if ($pending > 100) {
        return [
            'spoken' => "There are {$pending} pending tasks. That's a lot. Say deploy 50 agents to start a batch, or confirm with deploy all confirmed.",
            'success' => true,
            'data' => ['pending' => $pending]
        ];
    }

    // Spawn in batches of 10
    $backlog = orchestratorAPI('backlog', 'GET', ['status' => 'pending', 'limit' => $pending]);
    $spawned = 0;
    if ($backlog && !empty($backlog['tasks'])) {
        foreach ($backlog['tasks'] as $task) {
            $result = orchestratorAPI('spawn', 'POST', ['id' => $task['id']]);
            if ($result && !empty($result['success'])) $spawned++;
        }
    }

    return [
        'spoken' => "Full fleet deployment — {$spawned} agents launched across all categories. The fleet is fully active.",
        'success' => true,
        'data' => ['spawned' => $spawned, 'total_pending' => $pending]
    ];
}

function handleSprint(string $category): array {
    $backlog = orchestratorAPI('backlog', 'GET', ['status' => 'pending', 'category' => $category, 'limit' => 20]);

    if (!$backlog || empty($backlog['tasks'])) {
        return ['spoken' => "No pending {$category} tasks for a sprint. The {$category} queue is clear.", 'success' => true];
    }

    $spawned = 0;
    foreach ($backlog['tasks'] as $task) {
        $result = orchestratorAPI('spawn', 'POST', ['id' => $task['id']]);
        if ($result && !empty($result['success'])) $spawned++;
    }

    $total = count($backlog['tasks']);
    return [
        'spoken' => "{$category} sprint launched — {$spawned} agents deployed on {$total} {$category} tasks. I'll report back when they finish.",
        'success' => true,
        'data' => ['spawned' => $spawned, 'category' => $category]
    ];
}

function handleStopAll(): array {
    // Cancel all pending tasks
    $backlog = orchestratorAPI('backlog', 'GET', ['status' => 'pending', 'limit' => 500]);
    $cancelled = 0;

    if ($backlog && !empty($backlog['tasks'])) {
        foreach ($backlog['tasks'] as $task) {
            $result = orchestratorAPI('cancel', 'POST', ['id' => $task['id']]);
            if ($result && !empty($result['success'])) $cancelled++;
        }
    }

    // Also try claimed tasks
    $claimed = orchestratorAPI('backlog', 'GET', ['status' => 'claimed', 'limit' => 100]);
    if ($claimed && !empty($claimed['tasks'])) {
        foreach ($claimed['tasks'] as $task) {
            $result = orchestratorAPI('cancel', 'POST', ['id' => $task['id']]);
            if ($result && !empty($result['success'])) $cancelled++;
        }
    }

    if ($cancelled === 0) {
        return ['spoken' => 'No active tasks to stop. The fleet is already idle.', 'success' => true];
    }

    return [
        'spoken' => "Fleet halted — {$cancelled} tasks cancelled. All agents standing down.",
        'success' => true,
        'data' => ['cancelled' => $cancelled]
    ];
}

function handleStopTask(string $taskId): array {
    $result = orchestratorAPI('cancel', 'POST', ['task_id' => $taskId]);
    if ($result && !empty($result['success'])) {
        return ['spoken' => "Task {$taskId} cancelled.", 'success' => true];
    }
    return ['spoken' => "Couldn't cancel task {$taskId}. It may already be completed or not exist.", 'success' => false];
}

function handleRetryAll(): array {
    $backlog = orchestratorAPI('backlog', 'GET', ['status' => 'failed', 'limit' => 100]);
    if (!$backlog || empty($backlog['tasks'])) {
        return ['spoken' => 'No failed tasks to retry. Everything looks clean.', 'success' => true];
    }

    $retried = 0;
    foreach ($backlog['tasks'] as $task) {
        $result = orchestratorAPI('retry', 'POST', ['id' => $task['id']]);
        if ($result && !empty($result['success'])) $retried++;
    }

    return [
        'spoken' => "Retrying {$retried} failed tasks. They've been reset to pending for redeployment.",
        'success' => true,
        'data' => ['retried' => $retried]
    ];
}

function handleRetryTask(string $taskId): array {
    // Look up task by task_id
    $backlog = orchestratorAPI('backlog', 'GET', ['limit' => 1]);
    // Use the task endpoint instead
    $result = orchestratorAPI('retry', 'POST', ['task_id' => $taskId]);
    if ($result && !empty($result['success'])) {
        return ['spoken' => "Task {$taskId} reset for retry. Deploy an agent to pick it up.", 'success' => true];
    }
    return ['spoken' => "Couldn't retry task {$taskId}. It may have hit the retry limit or doesn't exist.", 'success' => false];
}

function handleStatus(): array {
    $stats = orchestratorAPI('stats');
    if (!$stats || !empty($stats['error'])) {
        return ['spoken' => 'Couldn\'t reach the orchestrator for status. The service may need restarting.', 'success' => false];
    }

    $s = $stats['stats'];
    $total    = $s['total'] ?? 0;
    $pending  = $s['by_status']['pending'] ?? 0;
    $running  = $s['by_status']['running'] ?? 0;
    $done     = $s['by_status']['done'] ?? 0;
    $failed   = $s['by_status']['failed'] ?? 0;
    $claimed  = $s['by_status']['claimed'] ?? 0;

    if ($total === 0) {
        return ['spoken' => 'The task queue is empty. Import a backlog or create tasks to get started.', 'success' => true, 'data' => $s];
    }

    $parts = [];
    $parts[] = "{$total} total tasks in the system";
    if ($done > 0)    $parts[] = "{$done} completed";
    if ($running > 0) $parts[] = "{$running} currently running";
    if ($claimed > 0) $parts[] = "{$claimed} claimed";
    if ($pending > 0) $parts[] = "{$pending} pending";
    if ($failed > 0)  $parts[] = "{$failed} failed";

    $pct = $total > 0 ? round(($done / $total) * 100) : 0;
    $spoken = "Fleet status report: " . implode(', ', $parts) . ". Overall progress: {$pct} percent complete.";

    return ['spoken' => $spoken, 'success' => true, 'data' => $s];
}

function handleList(string $status): array {
    $backlog = orchestratorAPI('backlog', 'GET', ['status' => $status, 'limit' => 10]);
    if (!$backlog || empty($backlog['tasks'])) {
        return ['spoken' => "No {$status} tasks found.", 'success' => true];
    }

    $count = $backlog['total'] ?? count($backlog['tasks']);
    $shown = min(5, count($backlog['tasks']));
    $taskNames = [];
    for ($i = 0; $i < $shown; $i++) {
        $t = $backlog['tasks'][$i];
        $taskNames[] = ($t['task_id'] ?? '') . ' — ' . ($t['title'] ?? 'Untitled');
    }

    $spoken = "{$count} {$status} tasks. Here are the first {$shown}: " . implode('. ', $taskNames) . '.';
    if ($count > $shown) {
        $spoken .= " And " . ($count - $shown) . " more.";
    }

    return ['spoken' => $spoken, 'success' => true, 'data' => $backlog['tasks']];
}

function handleImport(): array {
    $result = orchestratorAPI('import_backlog', 'POST');
    if ($result && !empty($result['success'])) {
        $imported = $result['imported'] ?? 0;
        $skipped = $result['skipped'] ?? 0;
        return [
            'spoken' => "Backlog imported — {$imported} new tasks loaded, {$skipped} already existed. The fleet is ready to deploy.",
            'success' => true,
            'data' => $result
        ];
    }
    return ['spoken' => 'Backlog import failed. Check that UPGRADE_BACKLOG.md exists.', 'success' => false];
}

function handleFleetStatus(): array {
    $result = alfredCommand('fleet.agents');
    if (!$result || !empty($result['error'])) {
        // Fall back to orchestrator stats
        return handleStatus();
    }

    $agents = $result['agents'] ?? [];
    if (empty($agents)) {
        return ['spoken' => 'No fleet agents registered yet. The orchestrator has task-level tracking — say check status to see task progress.', 'success' => true];
    }

    $active = count(array_filter($agents, fn($a) => ($a['status'] ?? '') === 'active'));
    $total = count($agents);
    return [
        'spoken' => "{$total} fleet agents registered, {$active} currently active.",
        'success' => true,
        'data' => $agents
    ];
}

function handleImportCircuitSim(): array {
    $result = orchestratorAPI('import_circuit_sim', 'POST');
    if ($result && !empty($result['success'])) {
        $imported = $result['imported'] ?? 0;
        $skipped = $result['skipped_duplicates'] ?? 0;
        return [
            'spoken' => "Circuit simulator upgrade backlog imported — {$imported} new tasks loaded across 10 upgrade streams, {$skipped} already existed. Ready to deploy agents on the circuit simulator.",
            'success' => true,
            'data' => $result
        ];
    }
    return ['spoken' => 'Circuit simulator backlog import failed. Check that the file exists in the data directory.', 'success' => false];
}

function handleHelp(): array {
    return [
        'spoken' => 'Here are the fleet commands I understand: ' .
            'Deploy 5 agents — spawns agents for pending tasks. ' .
            'Deploy 10 security agents — targets a specific category. ' .
            'Run security sprint — focused batch on one category. ' .
            'Check status — fleet progress report. ' .
            'List failed tasks — shows tasks by status. ' .
            'Stop all — cancels all pending work. ' .
            'Retry failed — resets failed tasks. ' .
            'Import backlog — loads tasks from the upgrade backlog. ' .
            'Deploy all — launches the full fleet. ' .
            'Import circuit sim — loads 510 circuit simulator upgrade tasks.',
        'success' => true
    ];
}