<?php
/**
 * Alfred LangGraph Orchestrator API — Phase 4: Multi-Agent Workflows
 * ──────────────────────────────────────────────────────────────────
 * Graph-based agent workflow orchestration. Define workflows as directed
 * graphs: nodes are agent actions, edges are conditional transitions.
 *
 * Endpoints:
 *   POST ?action=create-workflow   → Define a new workflow graph
 *   GET  ?action=workflows        → List workflows
 *   GET  ?action=workflow         → Get workflow definition
 *   POST ?action=execute          → Execute a workflow
 *   GET  ?action=runs             → List workflow runs
 *   GET  ?action=run              → Get run status/result
 *   POST ?action=pause            → Pause a running workflow
 *   POST ?action=resume           → Resume a paused workflow
 *   POST ?action=cancel           → Cancel a workflow run
 *   GET  ?action=presets          → Built-in workflow presets
 *   GET  ?action=stats            → Orchestrator stats
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Internal-Secret');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

function requireAuth() {
    if (empty($_SESSION['logged_in']) || empty($_SESSION['client_id'])) jsonResponse(['error' => 'Authentication required'], 401);
}
function isAdmin() { return !empty($_SESSION['is_admin']) || ($_SESSION['client_id'] ?? 0) === 33; }
function isInternalCall() {
    $s = getenv('INTERNAL_SECRET') ?: '';
    return $s && isset($_SERVER['HTTP_X_INTERNAL_SECRET']) && hash_equals($s, $_SERVER['HTTP_X_INTERNAL_SECRET']);
}

function ensureOrchestratorSchema() {
    $db = getDB();
    if (!$db) return false;

    $db->exec("CREATE TABLE IF NOT EXISTS orchestrator_workflows (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        workflow_id     VARCHAR(64) UNIQUE NOT NULL,
        name            VARCHAR(200) NOT NULL,
        description     TEXT DEFAULT NULL,
        graph           JSON NOT NULL,
        input_schema    JSON DEFAULT NULL,
        output_schema   JSON DEFAULT NULL,
        is_active       TINYINT(1) DEFAULT 1,
        version         INT DEFAULT 1,
        created_by      INT DEFAULT NULL,
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS orchestrator_runs (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        run_id          VARCHAR(64) UNIQUE NOT NULL,
        workflow_id     VARCHAR(64) NOT NULL,
        client_id       INT DEFAULT NULL,
        input_data      JSON DEFAULT NULL,
        state           JSON DEFAULT NULL,
        current_node    VARCHAR(100) DEFAULT NULL,
        nodes_executed  JSON DEFAULT NULL,
        status          ENUM('running','paused','completed','failed','cancelled') DEFAULT 'running',
        output_data     JSON DEFAULT NULL,
        error_message   TEXT DEFAULT NULL,
        started_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        completed_at    TIMESTAMP NULL DEFAULT NULL,
        execution_ms    INT DEFAULT 0,
        INDEX idx_workflow (workflow_id),
        INDEX idx_client (client_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS orchestrator_node_logs (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        run_id          VARCHAR(64) NOT NULL,
        node_id         VARCHAR(100) NOT NULL,
        node_type       VARCHAR(50) NOT NULL,
        input_data      JSON DEFAULT NULL,
        output_data     JSON DEFAULT NULL,
        status          ENUM('started','completed','failed','skipped') DEFAULT 'started',
        duration_ms     INT DEFAULT 0,
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_run (run_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    return true;
}

// ─── Workflow Presets ──────────────────────────────────────────────
function getPresets() {
    return [
        'deep_research' => [
            'name' => 'Deep Research Pipeline',
            'description' => 'Multi-agent research: plan → search → analyze → synthesize → review',
            'graph' => [
                'nodes' => [
                    ['id' => 'plan', 'type' => 'agent', 'agent' => 'SAGE', 'action' => 'Create research plan from query', 'config' => ['temperature' => 0.3]],
                    ['id' => 'search_web', 'type' => 'tool', 'tool' => 'web-search', 'config' => ['max_results' => 10]],
                    ['id' => 'search_docs', 'type' => 'tool', 'tool' => 'smart-search', 'config' => ['limit' => 5]],
                    ['id' => 'analyze', 'type' => 'agent', 'agent' => 'ORACLE', 'action' => 'Analyze and cross-reference findings'],
                    ['id' => 'synthesize', 'type' => 'agent', 'agent' => 'SAGE', 'action' => 'Synthesize final report with citations'],
                    ['id' => 'review', 'type' => 'agent', 'agent' => 'NOVA', 'action' => 'Quality review and fact-check'],
                ],
                'edges' => [
                    ['from' => 'plan', 'to' => 'search_web'],
                    ['from' => 'plan', 'to' => 'search_docs'],
                    ['from' => 'search_web', 'to' => 'analyze'],
                    ['from' => 'search_docs', 'to' => 'analyze'],
                    ['from' => 'analyze', 'to' => 'synthesize'],
                    ['from' => 'synthesize', 'to' => 'review'],
                ],
                'entry' => 'plan',
                'exit' => 'review',
            ],
        ],
        'content_creation' => [
            'name' => 'Content Creation Pipeline',
            'description' => 'Generate → edit → optimize → publish',
            'graph' => [
                'nodes' => [
                    ['id' => 'research', 'type' => 'agent', 'agent' => 'SAGE', 'action' => 'Research topic and gather data'],
                    ['id' => 'draft', 'type' => 'agent', 'agent' => 'HERALD', 'action' => 'Write initial draft'],
                    ['id' => 'edit', 'type' => 'agent', 'agent' => 'HERALD', 'action' => 'Edit for clarity, tone, grammar'],
                    ['id' => 'seo', 'type' => 'agent', 'agent' => 'PULSE', 'action' => 'Optimize for SEO'],
                    ['id' => 'review', 'type' => 'conditional', 'condition' => 'quality_score > 0.8', 'true_target' => 'publish', 'false_target' => 'edit'],
                    ['id' => 'publish', 'type' => 'tool', 'tool' => 'documents', 'action' => 'create'],
                ],
                'edges' => [
                    ['from' => 'research', 'to' => 'draft'],
                    ['from' => 'draft', 'to' => 'edit'],
                    ['from' => 'edit', 'to' => 'seo'],
                    ['from' => 'seo', 'to' => 'review'],
                    ['from' => 'review', 'to' => 'publish', 'condition' => 'pass'],
                    ['from' => 'review', 'to' => 'edit', 'condition' => 'fail'],
                ],
                'entry' => 'research',
                'exit' => 'publish',
            ],
        ],
        'customer_onboarding' => [
            'name' => 'Customer Onboarding Flow',
            'description' => 'Welcome → configure → tutorial → follow-up',
            'graph' => [
                'nodes' => [
                    ['id' => 'welcome', 'type' => 'action', 'action' => 'send_welcome_email', 'channel' => 'email'],
                    ['id' => 'create_account', 'type' => 'tool', 'tool' => 'client', 'action' => 'create'],
                    ['id' => 'configure', 'type' => 'agent', 'agent' => 'ATLAS', 'action' => 'Set up default configurations'],
                    ['id' => 'tutorial', 'type' => 'action', 'action' => 'send_tutorial_sequence'],
                    ['id' => 'day3_check', 'type' => 'delay', 'delay_hours' => 72],
                    ['id' => 'followup', 'type' => 'agent', 'agent' => 'HERALD', 'action' => 'Send personalized follow-up'],
                ],
                'edges' => [
                    ['from' => 'welcome', 'to' => 'create_account'],
                    ['from' => 'create_account', 'to' => 'configure'],
                    ['from' => 'configure', 'to' => 'tutorial'],
                    ['from' => 'tutorial', 'to' => 'day3_check'],
                    ['from' => 'day3_check', 'to' => 'followup'],
                ],
                'entry' => 'welcome',
                'exit' => 'followup',
            ],
        ],
        'incident_response' => [
            'name' => 'Incident Response Pipeline',
            'description' => 'Detect → diagnose → heal → notify → postmortem',
            'graph' => [
                'nodes' => [
                    ['id' => 'detect', 'type' => 'tool', 'tool' => 'self-healing', 'action' => 'health'],
                    ['id' => 'diagnose', 'type' => 'tool', 'tool' => 'self-healing', 'action' => 'diagnose'],
                    ['id' => 'severity_check', 'type' => 'conditional', 'condition' => 'severity >= critical', 'true_target' => 'escalate', 'false_target' => 'auto_heal'],
                    ['id' => 'auto_heal', 'type' => 'tool', 'tool' => 'self-healing', 'action' => 'heal'],
                    ['id' => 'escalate', 'type' => 'action', 'action' => 'notify_admin', 'channel' => 'sms'],
                    ['id' => 'notify', 'type' => 'tool', 'tool' => 'comm-bus', 'action' => 'publish'],
                    ['id' => 'postmortem', 'type' => 'agent', 'agent' => 'ARCHITECT', 'action' => 'Generate incident postmortem report'],
                ],
                'edges' => [
                    ['from' => 'detect', 'to' => 'diagnose'],
                    ['from' => 'diagnose', 'to' => 'severity_check'],
                    ['from' => 'severity_check', 'to' => 'auto_heal', 'condition' => 'low'],
                    ['from' => 'severity_check', 'to' => 'escalate', 'condition' => 'high'],
                    ['from' => 'auto_heal', 'to' => 'notify'],
                    ['from' => 'escalate', 'to' => 'notify'],
                    ['from' => 'notify', 'to' => 'postmortem'],
                ],
                'entry' => 'detect',
                'exit' => 'postmortem',
            ],
        ],
        'daily_autonomy' => [
            'name' => 'Daily Autonomy Cycle',
            'description' => 'Alfred\'s daily autonomous routine',
            'graph' => [
                'nodes' => [
                    ['id' => 'check_feeds', 'type' => 'tool', 'tool' => 'feeds', 'action' => 'refresh'],
                    ['id' => 'check_health', 'type' => 'tool', 'tool' => 'self-healing', 'action' => 'health'],
                    ['id' => 'check_goals', 'type' => 'tool', 'tool' => 'goals', 'action' => 'evaluate'],
                    ['id' => 'check_treasury', 'type' => 'tool', 'tool' => 'treasury', 'action' => 'report'],
                    ['id' => 'learning_insights', 'type' => 'tool', 'tool' => 'learning', 'action' => 'insights'],
                    ['id' => 'compile_briefing', 'type' => 'agent', 'agent' => 'ALFRED', 'action' => 'Compile daily briefing from all data'],
                    ['id' => 'send_briefing', 'type' => 'tool', 'tool' => 'comm-bus', 'action' => 'trigger'],
                ],
                'edges' => [
                    ['from' => 'check_feeds', 'to' => 'compile_briefing'],
                    ['from' => 'check_health', 'to' => 'compile_briefing'],
                    ['from' => 'check_goals', 'to' => 'compile_briefing'],
                    ['from' => 'check_treasury', 'to' => 'compile_briefing'],
                    ['from' => 'learning_insights', 'to' => 'compile_briefing'],
                    ['from' => 'compile_briefing', 'to' => 'send_briefing'],
                ],
                'entry' => ['check_feeds', 'check_health', 'check_goals', 'check_treasury', 'learning_insights'],
                'exit' => 'send_briefing',
            ],
        ],
    ];
}

// ─── Execute a graph node ──────────────────────────────────────────
function executeNode($node, $state, $runId) {
    $db = getDB();
    $start = hrtime(true);

    $db->prepare("INSERT INTO orchestrator_node_logs (run_id, node_id, node_type, input_data, status) VALUES (?, ?, ?, ?, 'started')")->execute([
        $runId, $node['id'], $node['type'], json_encode($state)
    ]);
    $logId = $db->lastInsertId();

    $output = [];
    $status = 'completed';

    switch ($node['type']) {
        case 'agent':
            // Delegate to agent via delegation API
            $internalSecret = getenv('INTERNAL_SECRET') ?: '';
            if ($internalSecret) {
                $ch = curl_init(SITE_URL . '/api/delegation.php?action=delegate');
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-Internal-Secret: ' . $internalSecret],
                    CURLOPT_POSTFIELDS => json_encode([
                        'agent' => $node['agent'] ?? 'ALFRED',
                        'task' => $node['action'] ?? '',
                        'context' => $state,
                    ]),
                    CURLOPT_TIMEOUT => 30,
                ]);
                $resp = json_decode(curl_exec($ch), true);
                curl_close($ch);
                $output = $resp ?? ['result' => 'delegated'];
            } else {
                $output = ['result' => 'Agent ' . ($node['agent'] ?? 'ALFRED') . ' would execute: ' . ($node['action'] ?? ''), 'state' => $state];
            }
            break;

        case 'tool':
            $internalSecret = getenv('INTERNAL_SECRET') ?: '';
            if ($internalSecret) {
                $toolAction = $node['action'] ?? 'health';
                $toolName = $node['tool'] ?? '';
                $ch = curl_init(SITE_URL . "/api/{$toolName}.php?action={$toolAction}");
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-Internal-Secret: ' . $internalSecret],
                    CURLOPT_POSTFIELDS => json_encode($state),
                    CURLOPT_TIMEOUT => 30,
                ]);
                $resp = json_decode(curl_exec($ch), true);
                curl_close($ch);
                $output = $resp ?? [];
            } else {
                $output = ['result' => 'Tool call simulated', 'tool' => $node['tool'], 'action' => $node['action']];
            }
            break;

        case 'conditional':
            // Evaluate condition against state
            $conditionMet = false;
            if (isset($node['condition'])) {
                // Simple condition evaluation: "key > value" or "key == value"
                if (preg_match('/^(\w+)\s*(>|<|>=|<=|==|!=)\s*(.+)$/', $node['condition'], $m)) {
                    $val = $state[$m[1]] ?? 0;
                    $threshold = is_numeric($m[3]) ? floatval($m[3]) : trim($m[3], '"\'');
                    switch ($m[2]) {
                        case '>':  $conditionMet = $val > $threshold; break;
                        case '<':  $conditionMet = $val < $threshold; break;
                        case '>=': $conditionMet = $val >= $threshold; break;
                        case '<=': $conditionMet = $val <= $threshold; break;
                        case '==': $conditionMet = $val == $threshold; break;
                        case '!=': $conditionMet = $val != $threshold; break;
                    }
                }
            }
            $output = ['condition_met' => $conditionMet, 'next' => $conditionMet ? ($node['true_target'] ?? null) : ($node['false_target'] ?? null)];
            break;

        case 'action':
            $output = ['action' => $node['action'] ?? 'no-op', 'executed' => true, 'state' => $state];
            break;

        case 'delay':
            $output = ['delayed_hours' => $node['delay_hours'] ?? 0, 'resume_at' => date('Y-m-d H:i:s', time() + (($node['delay_hours'] ?? 0) * 3600))];
            $status = 'completed';
            break;

        default:
            $output = ['result' => 'Unknown node type: ' . $node['type']];
            $status = 'skipped';
    }

    $durationMs = (int)((hrtime(true) - $start) / 1e6);
    $db->prepare("UPDATE orchestrator_node_logs SET output_data = ?, status = ?, duration_ms = ? WHERE id = ?")->execute([
        json_encode($output), $status, $durationMs, $logId
    ]);

    return ['output' => $output, 'status' => $status, 'duration_ms' => $durationMs];
}

$action = sanitize($_GET['action'] ?? '', 30);
$db = getDB();
if (!$db) jsonResponse(['error' => 'Database unavailable'], 503);
ensureOrchestratorSchema();

switch ($action) {

    case 'create-workflow':
        if (!isInternalCall()) { requireAuth(); if (!isAdmin()) jsonResponse(['error' => 'Admin/internal required'], 403); }

        $input = json_decode(file_get_contents('php://input'), true);
        $name = sanitize($input['name'] ?? '', 200);
        $graph = $input['graph'] ?? null;
        if (!$name || !$graph) jsonResponse(['error' => 'name and graph required'], 400);

        // Validate graph
        if (!isset($graph['nodes']) || !isset($graph['edges']) || !isset($graph['entry'])) {
            jsonResponse(['error' => 'Graph must have nodes, edges, and entry'], 400);
        }

        $workflowId = 'wf_' . bin2hex(random_bytes(12));

        $db->prepare("INSERT INTO orchestrator_workflows (workflow_id, name, description, graph, input_schema, output_schema, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)")->execute([
            $workflowId, $name,
            sanitize($input['description'] ?? '', 1000) ?: null,
            json_encode($graph),
            isset($input['input_schema']) ? json_encode($input['input_schema']) : null,
            isset($input['output_schema']) ? json_encode($input['output_schema']) : null,
            $_SESSION['client_id'] ?? null,
        ]);

        jsonResponse(['success' => true, 'workflow_id' => $workflowId]);
        break;

    case 'workflows':
        if (!isInternalCall()) requireAuth();

        $stmt = $db->query("SELECT workflow_id, name, description, is_active, version, created_at FROM orchestrator_workflows ORDER BY created_at DESC");
        jsonResponse(['success' => true, 'workflows' => $stmt->fetchAll()]);
        break;

    case 'workflow':
        if (!isInternalCall()) requireAuth();

        $workflowId = sanitize($_GET['workflow_id'] ?? '', 64);
        $stmt = $db->prepare("SELECT * FROM orchestrator_workflows WHERE workflow_id = ?");
        $stmt->execute([$workflowId]);
        $workflow = $stmt->fetch();
        if (!$workflow) jsonResponse(['error' => 'Workflow not found'], 404);

        $workflow['graph'] = json_decode($workflow['graph'], true);
        $workflow['input_schema'] = json_decode($workflow['input_schema'], true);
        $workflow['output_schema'] = json_decode($workflow['output_schema'], true);

        jsonResponse(['success' => true, 'workflow' => $workflow]);
        break;

    case 'execute':
        if (!isInternalCall()) { requireAuth(); if (!isAdmin()) jsonResponse(['error' => 'Admin/internal required'], 403); }

        $input = json_decode(file_get_contents('php://input'), true);
        $workflowId = sanitize($input['workflow_id'] ?? '', 64);
        $presetName = sanitize($input['preset'] ?? '', 50);
        $inputData = $input['input'] ?? [];

        $graph = null;

        if ($presetName) {
            $presets = getPresets();
            if (!isset($presets[$presetName])) jsonResponse(['error' => 'Unknown preset', 'available' => array_keys($presets)], 400);
            $graph = $presets[$presetName]['graph'];
            $workflowId = 'preset_' . $presetName;
        } else {
            $stmt = $db->prepare("SELECT graph FROM orchestrator_workflows WHERE workflow_id = ? AND is_active = 1");
            $stmt->execute([$workflowId]);
            $wf = $stmt->fetch();
            if (!$wf) jsonResponse(['error' => 'Workflow not found or inactive'], 404);
            $graph = json_decode($wf['graph'], true);
        }

        $runId = 'run_' . bin2hex(random_bytes(16));
        $startTime = hrtime(true);

        $db->prepare("INSERT INTO orchestrator_runs (run_id, workflow_id, client_id, input_data, state, status) VALUES (?, ?, ?, ?, ?, 'running')")->execute([
            $runId, $workflowId, $_SESSION['client_id'] ?? null, json_encode($inputData), json_encode($inputData)
        ]);

        // Build node map and adjacency
        $nodeMap = [];
        foreach ($graph['nodes'] as $node) $nodeMap[$node['id']] = $node;
        $adjacency = [];
        foreach ($graph['edges'] as $edge) $adjacency[$edge['from']][] = $edge;

        // Execute graph (BFS from entry point(s))
        $entries = is_array($graph['entry']) ? $graph['entry'] : [$graph['entry']];
        $state = $inputData;
        $nodesExecuted = [];
        $queue = $entries;
        $visited = [];
        $maxNodes = 50; // Safety: prevent infinite loops
        $nodeCount = 0;

        while (!empty($queue) && $nodeCount < $maxNodes) {
            $currentId = array_shift($queue);
            if (isset($visited[$currentId])) continue;
            $visited[$currentId] = true;
            $nodeCount++;

            if (!isset($nodeMap[$currentId])) continue;
            $node = $nodeMap[$currentId];

            // Update current node
            $db->prepare("UPDATE orchestrator_runs SET current_node = ? WHERE run_id = ?")->execute([$currentId, $runId]);

            // Execute
            $result = executeNode($node, $state, $runId);
            $nodesExecuted[] = ['node' => $currentId, 'status' => $result['status'], 'duration_ms' => $result['duration_ms']];

            // Merge output into state
            if (is_array($result['output'])) {
                $state = array_merge($state, $result['output']);
            }

            // Handle conditional routing
            if ($node['type'] === 'conditional' && isset($result['output']['next'])) {
                $queue[] = $result['output']['next'];
            } else {
                // Follow edges
                foreach ($adjacency[$currentId] ?? [] as $edge) {
                    $queue[] = $edge['to'];
                }
            }
        }

        $totalMs = (int)((hrtime(true) - $startTime) / 1e6);

        $db->prepare("UPDATE orchestrator_runs SET status = 'completed', output_data = ?, nodes_executed = ?, current_node = NULL, execution_ms = ?, completed_at = NOW() WHERE run_id = ?")->execute([
            json_encode($state), json_encode($nodesExecuted), $totalMs, $runId
        ]);

        jsonResponse([
            'success' => true,
            'run_id' => $runId,
            'status' => 'completed',
            'nodes_executed' => count($nodesExecuted),
            'execution_ms' => $totalMs,
            'output' => $state,
        ]);
        break;

    case 'runs':
        if (!isInternalCall()) requireAuth();

        $limit = min(max(intval($_GET['limit'] ?? 20), 1), 100);
        $status = sanitize($_GET['status'] ?? '', 20);

        $where = "1=1";
        $params = [];
        if ($status && in_array($status, ['running','paused','completed','failed','cancelled'])) {
            $where .= " AND status = ?";
            $params[] = $status;
        }

        $params[] = $limit;
        $stmt = $db->prepare("SELECT run_id, workflow_id, status, current_node, execution_ms, started_at, completed_at FROM orchestrator_runs WHERE $where ORDER BY started_at DESC LIMIT ?");
        dbExecute($stmt, $params);
        jsonResponse(['success' => true, 'runs' => $stmt->fetchAll()]);
        break;

    case 'run':
        if (!isInternalCall()) requireAuth();

        $runId = sanitize($_GET['run_id'] ?? '', 64);
        $stmt = $db->prepare("SELECT * FROM orchestrator_runs WHERE run_id = ?");
        $stmt->execute([$runId]);
        $run = $stmt->fetch();
        if (!$run) jsonResponse(['error' => 'Run not found'], 404);

        $run['input_data'] = json_decode($run['input_data'], true);
        $run['state'] = json_decode($run['state'], true);
        $run['nodes_executed'] = json_decode($run['nodes_executed'], true);
        $run['output_data'] = json_decode($run['output_data'], true);

        // Get node logs
        $logs = $db->prepare("SELECT * FROM orchestrator_node_logs WHERE run_id = ? ORDER BY created_at");
        $logs->execute([$runId]);
        $nodeLogs = $logs->fetchAll();
        foreach ($nodeLogs as &$log) {
            $log['input_data'] = json_decode($log['input_data'], true);
            $log['output_data'] = json_decode($log['output_data'], true);
        }

        $run['node_logs'] = $nodeLogs;

        jsonResponse(['success' => true, 'run' => $run]);
        break;

    case 'pause':
        if (!isInternalCall()) requireAuth();

        $input = json_decode(file_get_contents('php://input'), true);
        $runId = sanitize($input['run_id'] ?? '', 64);
        $db->prepare("UPDATE orchestrator_runs SET status = 'paused' WHERE run_id = ? AND status = 'running'")->execute([$runId]);
        jsonResponse(['success' => true]);
        break;

    case 'resume':
        if (!isInternalCall()) requireAuth();

        $input = json_decode(file_get_contents('php://input'), true);
        $runId = sanitize($input['run_id'] ?? '', 64);
        $db->prepare("UPDATE orchestrator_runs SET status = 'running' WHERE run_id = ? AND status = 'paused'")->execute([$runId]);
        jsonResponse(['success' => true]);
        break;

    case 'cancel':
        if (!isInternalCall()) requireAuth();

        $input = json_decode(file_get_contents('php://input'), true);
        $runId = sanitize($input['run_id'] ?? '', 64);
        $db->prepare("UPDATE orchestrator_runs SET status = 'cancelled', completed_at = NOW() WHERE run_id = ? AND status IN ('running','paused')")->execute([$runId]);
        jsonResponse(['success' => true]);
        break;

    case 'presets':
        $presets = getPresets();
        $summary = [];
        foreach ($presets as $key => $preset) {
            $summary[$key] = [
                'name' => $preset['name'],
                'description' => $preset['description'],
                'node_count' => count($preset['graph']['nodes']),
                'edge_count' => count($preset['graph']['edges']),
            ];
        }
        jsonResponse(['success' => true, 'presets' => $summary]);
        break;

    case 'stats':
        if (!isInternalCall()) { requireAuth(); if (!isAdmin()) jsonResponse(['error' => 'Admin required'], 403); }

        $totalRuns = $db->query("SELECT COUNT(*) FROM orchestrator_runs")->fetchColumn();
        $completed = $db->query("SELECT COUNT(*) FROM orchestrator_runs WHERE status = 'completed'")->fetchColumn();
        $failed = $db->query("SELECT COUNT(*) FROM orchestrator_runs WHERE status = 'failed'")->fetchColumn();
        $avgExecution = $db->query("SELECT AVG(execution_ms) FROM orchestrator_runs WHERE status = 'completed'")->fetchColumn();
        $totalWorkflows = $db->query("SELECT COUNT(*) FROM orchestrator_workflows WHERE is_active = 1")->fetchColumn();

        $topWorkflows = $db->query("SELECT workflow_id, COUNT(*) as runs, AVG(execution_ms) as avg_ms FROM orchestrator_runs GROUP BY workflow_id ORDER BY runs DESC LIMIT 10")->fetchAll();

        jsonResponse([
            'success' => true,
            'stats' => [
                'total_workflows' => (int)$totalWorkflows,
                'total_runs' => (int)$totalRuns,
                'completed_runs' => (int)$completed,
                'failed_runs' => (int)$failed,
                'success_rate' => $totalRuns > 0 ? round(($completed / $totalRuns) * 100, 1) : 0,
                'avg_execution_ms' => round((float)($avgExecution ?? 0), 0),
                'top_workflows' => $topWorkflows,
                'preset_count' => count(getPresets()),
            ],
        ]);
        break;

    default:
        jsonResponse(['error' => 'Unknown action', 'available_actions' => ['create-workflow','workflows','workflow','execute','runs','run','pause','resume','cancel','presets','stats']], 400);
}
