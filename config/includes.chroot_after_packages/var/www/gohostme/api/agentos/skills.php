<?php
/**
 * GSM Alfred OS — Skill Engine API v1.0
 * Compose capabilities into reusable goal-oriented skills
 *
 * Endpoints:
 *   GET    ?action=list              — List skills
 *   GET    ?action=get&id=X          — Get skill with steps
 *   POST   ?action=create            — Create a new skill
 *   POST   ?action=add_step          — Add step to skill
 *   POST   ?action=execute           — Execute a skill
 *   POST   ?action=learn             — Learn new skill from demonstration
 *   GET    ?action=suggest&goal=X    — Suggest skills for a goal
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
    case 'add_step': handleAddStep($auth); break;
    case 'execute':  handleExecute($auth); break;
    case 'learn':    handleLearn($auth); break;
    case 'suggest':  handleSuggest($auth); break;
    default:         agentos_error('Unknown action');
}

function handleList(array $auth): void {
    $pdo = agentos_pdo();
    $where = ['1=1'];
    $params = [];

    if (isset($_GET['category'])) {
        $where[] = 'category=?';
        $params[] = $_GET['category'];
    }
    if (!isset($_GET['include_disabled'])) {
        $where[] = 'enabled=1';
    }

    $sql = "SELECT skill_id, display_name, description, category, 
            risk_level, requires_approval, enabled, version, author
            FROM agentos_skills WHERE " . implode(' AND ', $where) . " 
            ORDER BY display_name";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    agentos_respond(['ok' => true, 'skills' => $stmt->fetchAll()]);
}

function handleGet(array $auth): void {
    $id = preg_replace('/[^a-zA-Z0-9_.-]/', '', $_GET['id'] ?? '');
    if (!$id) agentos_error('id required');

    $pdo = agentos_pdo();
    $stmt = $pdo->prepare("SELECT * FROM agentos_skills WHERE skill_id=?");
    $stmt->execute([$id]);
    $skill = $stmt->fetch();
    if (!$skill) agentos_error('Skill not found', 404);

    // Get steps
    $stmt = $pdo->prepare("SELECT * FROM agentos_skill_steps WHERE skill_id=? ORDER BY step_order");
    $stmt->execute([$id]);
    $skill['steps'] = $stmt->fetchAll();

    // Decode JSON fields
    foreach ($skill['steps'] as &$step) {
        $step['input_mapping'] = json_decode($step['input_mapping'] ?? 'null', true);
        $step['output_mapping'] = json_decode($step['output_mapping'] ?? 'null', true);
    }

    agentos_respond(['ok' => true, 'skill' => $skill]);
}

function handleCreate(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || empty($input['display_name'])) {
        agentos_error('display_name required');
    }

    $pdo = agentos_pdo();
    $skillId = $input['skill_id'] ?? agentos_id('skill');
    $skillId = preg_replace('/[^a-zA-Z0-9_.-]/', '', mb_substr($skillId, 0, 100));

    $stmt = $pdo->prepare("INSERT INTO agentos_skills 
        (skill_id, display_name, description, category, preconditions, postconditions,
         retry_policy, fallback_skill, risk_level, requires_approval, version, author)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $skillId,
        mb_substr($input['display_name'], 0, 200),
        mb_substr($input['description'] ?? '', 0, 2000),
        mb_substr($input['category'] ?? 'general', 0, 100),
        json_encode($input['preconditions'] ?? null),
        json_encode($input['postconditions'] ?? null),
        json_encode($input['retry_policy'] ?? null),
        $input['fallback_skill'] ?? null,
        in_array($input['risk_level'] ?? 'low', ['low','medium','high','critical']) ? ($input['risk_level'] ?? 'low') : 'low',
        (int)($input['requires_approval'] ?? 0),
        mb_substr($input['version'] ?? '1.0.0', 0, 16),
        mb_substr($input['author'] ?? 'system', 0, 64),
    ]);

    // If steps are provided, add them
    if (!empty($input['steps'])) {
        $stmtStep = $pdo->prepare("INSERT INTO agentos_skill_steps 
            (skill_id, step_order, capability_id, input_mapping, 
             output_mapping, `condition`, on_failure)
            VALUES (?, ?, ?, ?, ?, ?, ?)");

        foreach ($input['steps'] as $order => $step) {
            $stmtStep->execute([
                $skillId,
                $order + 1,
                mb_substr($step['capability_id'] ?? '', 0, 100),
                json_encode($step['input_mapping'] ?? null),
                json_encode($step['output_mapping'] ?? null),
                $step['condition'] ?? null,
                $step['on_failure'] ?? 'abort',
            ]);
        }
    }

    agentos_audit([
        'agent_id' => $input['author'] ?? 'system',
        'user_id' => $auth['user_id'],
        'action_type' => 'skill_created',
        'status' => 'completed',
        'input' => ['skill_id' => $skillId, 'steps' => count($input['steps'] ?? [])],
    ]);

    agentos_respond(['ok' => true, 'skill_id' => $skillId], 201);
}

function handleAddStep(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $skillId = preg_replace('/[^a-zA-Z0-9_.-]/', '', $input['skill_id'] ?? '');
    if (!$skillId || empty($input['capability_id'])) {
        agentos_error('skill_id and capability_id required');
    }

    $pdo = agentos_pdo();

    // Get next order
    $stmt = $pdo->prepare("SELECT COALESCE(MAX(step_order), 0) + 1 FROM agentos_skill_steps WHERE skill_id=?");
    $stmt->execute([$skillId]);
    $nextOrder = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("INSERT INTO agentos_skill_steps 
        (skill_id, step_order, capability_id, input_mapping, 
         output_mapping, `condition`, on_failure)
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $skillId,
        $input['step_order'] ?? $nextOrder,
        mb_substr($input['capability_id'], 0, 100),
        json_encode($input['input_mapping'] ?? null),
        json_encode($input['output_mapping'] ?? null),
        $input['condition'] ?? null,
        $input['on_failure'] ?? 'abort',
    ]);

    agentos_respond(['ok' => true, 'skill_id' => $skillId, 'step_order' => $input['step_order'] ?? $nextOrder]);
}

function handleExecute(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $skillId = preg_replace('/[^a-zA-Z0-9_.-]/', '', $input['skill_id'] ?? '');
    if (!$skillId) agentos_error('skill_id required');

    $pdo = agentos_pdo();

    // Get skill and steps
    $stmt = $pdo->prepare("SELECT * FROM agentos_skills WHERE skill_id=? AND enabled=1");
    $stmt->execute([$skillId]);
    $skill = $stmt->fetch();
    if (!$skill) agentos_error('Skill not found or disabled', 404);

    $stmt = $pdo->prepare("SELECT * FROM agentos_skill_steps WHERE skill_id=? ORDER BY step_order");
    $stmt->execute([$skillId]);
    $steps = $stmt->fetchAll();

    if (empty($steps)) agentos_error('Skill has no steps');

    $traceId = agentos_trace_id();
    $taskId = agentos_id('task');
    $startTime = hrtime(true);
    $results = [];
    $success = true;

    // Create a task for this skill execution
    $stmt = $pdo->prepare("INSERT INTO agentos_tasks (task_id, user_id, agent_id, goal, status, started_at)
        VALUES (?, ?, ?, ?, 'running', NOW())");
    $stmt->execute([$taskId, $auth['user_id'], $skill['author'] ?? 'alfred', "Execute skill: {$skill['display_name']}"]);

    // Execute steps in order
    $stepContext = $input['context'] ?? [];
    foreach ($steps as $step) {
        $capId = $step['capability_id'];

        // Resolve input mapping with context
        $inputMapping = json_decode($step['input_mapping'] ?? '{}', true);
        $stepInput = resolveTemplate($inputMapping, $stepContext, $results);

        // Condition check
        $condition = $step['condition'] ?? null;
        if ($condition && !evaluateConditions(['expr' => $condition], $stepContext, $results)) {
            $results[] = ['step' => $step['step_order'], 'skipped' => true, 'reason' => 'Condition not met'];
            continue;
        }

        // Execute the capability
        $stepResult = executeCapability($pdo, $capId, $stepInput, $auth, 30000);

        $results[] = [
            'step' => $step['step_order'],
            'capability' => $capId,
            'success' => $stepResult['success'],
            'result' => $stepResult['result'] ?? null,
            'error' => $stepResult['error'] ?? null,
        ];

        // Add result to context for next steps
        $stepContext["step_{$step['step_order']}"] = $stepResult['result'] ?? null;

        // Handle failure
        if (!$stepResult['success']) {
            $onFailure = $step['on_failure'] ?? 'abort';
            if ($onFailure === 'abort') {
                $success = false;
                break;
            }
        }
    }

    $durationMs = (int)((hrtime(true) - $startTime) / 1_000_000);

    // Update task
    $status = $success ? 'completed' : 'failed';
    $stmt = $pdo->prepare("UPDATE agentos_tasks SET status=?, result=?, completed_at=NOW() WHERE task_id=?");
    $stmt->execute([$status, json_encode($results), $taskId]);

    agentos_audit([
        'trace_id' => $traceId, 'task_id' => $taskId,
        'agent_id' => $skill['author'] ?? 'alfred', 'user_id' => $auth['user_id'],
        'action_type' => 'skill_executed', 'status' => $status,
        'output' => ['skill_id' => $skillId, 'steps' => count($results), 'success' => $success],
        'duration_ms' => $durationMs,
    ]);

    agentos_respond([
        'ok' => true,
        'skill_id' => $skillId,
        'task_id' => $taskId,
        'success' => $success,
        'steps_executed' => count($results),
        'duration_ms' => $durationMs,
        'results' => $results,
    ]);
}

function handleLearn(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || empty($input['name']) || empty($input['demonstration'])) {
        agentos_error('name and demonstration required');
    }

    // Learn a skill from a sequence of demonstrated actions
    $demo = $input['demonstration']; // Array of {capability_id, input, output}
    $skillId = agentos_id('skill');
    $pdo = agentos_pdo();

    $stmt = $pdo->prepare("INSERT INTO agentos_skills 
        (skill_id, display_name, description, category, author)
        VALUES (?, ?, ?, 'learned', ?)");
    $stmt->execute([
        $skillId,
        mb_substr($input['name'], 0, 200),
        mb_substr($input['description'] ?? "Learned from demonstration", 0, 2000),
        mb_substr($input['author'] ?? 'system', 0, 64),
    ]);

    $stmtStep = $pdo->prepare("INSERT INTO agentos_skill_steps 
        (skill_id, step_order, capability_id, input_mapping, on_failure)
        VALUES (?, ?, ?, ?, 'abort')");

    foreach ($demo as $i => $action) {
        $stmtStep->execute([
            $skillId,
            $i + 1,
            mb_substr($action['capability_id'] ?? '', 0, 100),
            json_encode($action['input'] ?? null),
        ]);
    }

    // Store in procedural memory
    $stmt = $pdo->prepare("INSERT INTO agentos_memory_procedural 
        (agent_id, procedure_name, trigger_pattern, steps, success_rate, times_used, learned_from)
        VALUES (?, ?, ?, ?, 100.00, 0, ?)");
    $stmt->execute([
        $input['author'] ?? 'alfred',
        $input['name'],
        $input['trigger_pattern'] ?? null,
        json_encode($demo),
        'demonstration',
    ]);

    agentos_respond(['ok' => true, 'skill_id' => $skillId, 'steps_learned' => count($demo)]);
}

function handleSuggest(array $auth): void {
    $goal = mb_substr(trim($_GET['goal'] ?? ''), 0, 1000);
    if (!$goal) agentos_error('goal required');

    $pdo = agentos_pdo();

    // Find skills with matching names or descriptions
    $stmt = $pdo->prepare("SELECT skill_id, display_name, description, risk_level, category 
        FROM agentos_skills WHERE enabled=1 
        AND (display_name LIKE ? OR description LIKE ?)
        ORDER BY display_name LIMIT 10");
    $term = '%' . $goal . '%';
    $stmt->execute([$term, $term]);
    $matches = $stmt->fetchAll();

    // Also check procedural memory
    $stmt = $pdo->prepare("SELECT procedure_name, trigger_pattern, success_rate, times_used 
        FROM agentos_memory_procedural 
        WHERE enabled=1 AND (procedure_name LIKE ? OR trigger_pattern LIKE ?)
        ORDER BY success_rate DESC LIMIT 5");
    $stmt->execute([$term, $term]);
    $procedures = $stmt->fetchAll();

    agentos_respond([
        'ok' => true,
        'goal' => $goal,
        'skills' => $matches,
        'procedures' => $procedures,
    ]);
}

// ── Helper Functions ───────────────────────────────────────────

function resolveTemplate(array $template, array $context, array $priorResults): array {
    $resolved = [];
    foreach ($template as $key => $value) {
        if (is_string($value) && preg_match('/^\{\{(.+)\}\}$/', $value, $m)) {
            $ref = trim($m[1]);
            if (isset($context[$ref])) {
                $resolved[$key] = $context[$ref];
            } elseif (isset($priorResults[$ref])) {
                $resolved[$key] = $priorResults[$ref];
            } else {
                $resolved[$key] = $value;
            }
        } elseif (is_array($value)) {
            $resolved[$key] = resolveTemplate($value, $context, $priorResults);
        } else {
            $resolved[$key] = $value;
        }
    }
    return $resolved;
}

function evaluateConditions(array $conditions, array $context, array $results): bool {
    foreach ($conditions as $cond) {
        $field = $cond['field'] ?? '';
        $op = $cond['op'] ?? 'eq';
        $val = $cond['value'] ?? null;
        $actual = $context[$field] ?? null;

        // Check in prior results
        if ($actual === null) {
            foreach ($results as $r) {
                if (isset($r[$field])) { $actual = $r[$field]; break; }
            }
        }

        switch ($op) {
            case 'eq': if ($actual != $val) return false; break;
            case 'neq': if ($actual == $val) return false; break;
            case 'truthy': if (empty($actual)) return false; break;
            case 'falsy': if (!empty($actual)) return false; break;
        }
    }
    return true;
}

function executeCapability(PDO $pdo, string $capId, array $input, array $auth, int $timeoutMs): array {
    $stmt = $pdo->prepare("SELECT * FROM agentos_capabilities WHERE capability_id=? AND enabled=1");
    $stmt->execute([$capId]);
    $cap = $stmt->fetch();

    if (!$cap) {
        return ['success' => false, 'error' => "Capability not found: {$capId}"];
    }

    $endpoint = $cap['endpoint'] ?? '';
    if (!$endpoint) {
        return ['success' => false, 'error' => "No endpoint for: {$capId}"];
    }

    $secret = getenv('INTERNAL_SECRET') ?: '';
    $payload = json_encode(array_merge($input, ['_agentos' => true, '_capability' => $capId]));

    $ch = curl_init("https://gositeme.com{$endpoint}");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-Internal-Secret: ' . $secret],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT_MS => $timeoutMs,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $result = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($result, true);
    return [
        'success' => $http >= 200 && $http < 300,
        'result' => $data,
        'http_code' => $http,
    ];
}
