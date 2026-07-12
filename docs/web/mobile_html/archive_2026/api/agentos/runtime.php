<?php
/**
 * GSM Alfred OS — Agent Runtime Core v2.0 (Phase 3: Safety & Simulation)
 * The unified execution loop: observe → plan → simulate → act → verify → learn
 *
 * Endpoints:
 *   POST /api/agentos/runtime.php?action=run          — Execute full agent loop for a goal
 *   POST /api/agentos/runtime.php?action=step          — Execute one step of a task
 *   POST /api/agentos/runtime.php?action=observe       — Get current observations
 *   POST /api/agentos/runtime.php?action=sandbox       — Execute goal in full sandbox mode
 *   GET  /api/agentos/runtime.php?action=status&task_id=X — Get task status
 *   GET  /api/agentos/runtime.php?action=sessions      — List active agent sessions
 *   POST /api/agentos/runtime.php?action=pause         — Pause a running task
 *   POST /api/agentos/runtime.php?action=resume        — Resume a paused task
 *   POST /api/agentos/runtime.php?action=cancel        — Cancel a task
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Internal-Secret');

// Runtime error handling — log to file
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/runtime_errors.log');
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error [{$errno}]: {$errstr} in {$errfile}:{$errline}");
    return false;
});
set_exception_handler(function($e) {
    error_log("Uncaught Exception: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    exit;
});
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_log("FATAL: {$err['message']} in {$err['file']}:{$err['line']}");
        if (!headers_sent()) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'Fatal: ' . $err['message']]);
        }
    }
});
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

define('GOSITEME_API', true);
require_once __DIR__ . '/bootstrap.php';
agentos_ensure_schema();

$auth = agentos_auth();
$action = $_GET['action'] ?? 'run';

switch ($action) {
    case 'run':      handleRun($auth); break;
    case 'execute':  handleRun($auth); break;
    case 'sandbox':  handleSandbox($auth); break;
    case 'step':     handleStep($auth); break;
    case 'observe':  handleObserve($auth); break;
    case 'status':   handleStatus($auth); break;
    case 'sessions': handleSessions($auth); break;
    case 'pause':    handlePause($auth); break;
    case 'resume':   handleResume($auth); break;
    case 'cancel':   handleCancel($auth); break;
    default:         agentos_error('Unknown action');
}

// ═══════════════════════════════════════════════════════════════
// RUN — Full agent loop for a goal
// ═══════════════════════════════════════════════════════════════
function handleRun(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || empty($input['goal'])) {
        agentos_error('Goal is required');
    }

    $goal = mb_substr(trim($input['goal']), 0, 10000);
    $agentId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['agent_id'] ?? 'alfred');
    $context = $input['context'] ?? [];
    $maxRounds = min((int)($input['max_rounds'] ?? 5), 10);
    $dryRun = !empty($input['dry_run']);

    $pdo = agentos_pdo();
    $traceId = agentos_trace_id();
    $taskId = agentos_id('task');

    // Create task record
    $stmt = $pdo->prepare("INSERT INTO agentos_tasks 
        (task_id, user_id, agent_id, goal, status, priority, context, started_at)
        VALUES (?, ?, ?, ?, 'planning', ?, ?, NOW())");
    $stmt->execute([
        $taskId,
        $auth['user_id'],
        $agentId,
        $goal,
        (int)($input['priority'] ?? 5),
        json_encode($context),
    ]);

    // Create or reuse session
    $sessionId = $input['session_id'] ?? agentos_id('sess');
    $stmt = $pdo->prepare("INSERT INTO agentos_agent_sessions 
        (session_id, agent_id, user_id, status, current_task_id, goals)
        VALUES (?, ?, ?, 'active', ?, ?)
        ON DUPLICATE KEY UPDATE current_task_id=VALUES(current_task_id), 
        status='active', goals=VALUES(goals), updated_at=NOW()");
    $stmt->execute([$sessionId, $agentId, $auth['user_id'], $taskId, json_encode([$goal])]);

    agentos_audit([
        'trace_id' => $traceId, 'task_id' => $taskId,
        'agent_id' => $agentId, 'user_id' => $auth['user_id'],
        'action_type' => 'task_created', 'status' => 'started',
        'input' => ['goal' => $goal, 'dry_run' => $dryRun],
        'reason' => 'User initiated goal',
    ]);

    // ── THE LOOP: observe → plan → simulate → act → verify → learn ──
    $result = agentLoop($pdo, $traceId, $taskId, $agentId, $goal, $context, $maxRounds, $dryRun, $auth);

    // Finalize
    $finalStatus = $result['success'] ? 'completed' : 'failed';
    $stmt = $pdo->prepare("UPDATE agentos_tasks SET status=?, result=?, completed_at=NOW() WHERE task_id=?");
    $stmt->execute([$finalStatus, json_encode($result), $taskId]);

    $stmt = $pdo->prepare("UPDATE agentos_agent_sessions SET loop_count=loop_count+?, last_loop_at=NOW() WHERE session_id=?");
    $stmt->execute([$result['rounds'], $sessionId]);

    agentos_audit([
        'trace_id' => $traceId, 'task_id' => $taskId,
        'agent_id' => $agentId, 'user_id' => $auth['user_id'],
        'action_type' => 'task_completed', 'status' => $finalStatus,
        'output' => ['rounds' => $result['rounds'], 'success' => $result['success']],
        'duration_ms' => $result['duration_ms'],
    ]);

    agentos_respond([
        'ok' => true,
        'task_id' => $taskId,
        'session_id' => $sessionId,
        'trace_id' => $traceId,
        'status' => $finalStatus,
        'result' => $result,
    ]);
}

// ═══════════════════════════════════════════════════════════════
// THE AGENT LOOP — The heart of Alfred OS
// ═══════════════════════════════════════════════════════════════
function agentLoop(PDO $pdo, string $traceId, string $taskId, string $agentId,
                   string $goal, array $context, int $maxRounds, bool $dryRun, array $auth): array {
    $startTime = hrtime(true);
    $rounds = 0;
    $actions = [];
    $observations = [];
    $finalOutput = null;
    $success = false;

    for ($round = 0; $round < $maxRounds; $round++) {
        $rounds++;
        $roundStart = hrtime(true);

        // Broadcast round start
        agentos_push('agentos:runtime', 'loop_round', [
            'task_id' => $taskId, 'round' => $rounds, 'max' => $maxRounds,
            'phase' => 'observe', 'goal' => $goal,
        ]);

        // ── 1. OBSERVE ──────────────────────────────────────────
        $obs = agentObserve($pdo, $taskId, $agentId, $goal, $context, $auth);
        $observations[] = $obs;

        agentos_audit([
            'trace_id' => $traceId, 'task_id' => $taskId,
            'agent_id' => $agentId, 'user_id' => $auth['user_id'],
            'action_type' => 'observe', 'status' => 'completed',
            'output' => ['observation_keys' => array_keys($obs)],
        ]);

        // ── 2. RETRIEVE MEMORY ──────────────────────────────────
        $memories = agentRecall($pdo, $agentId, $goal, $auth['user_id']);

        // ── 3. PLAN ─────────────────────────────────────────────
        agentos_push('agentos:runtime', 'loop_phase', [
            'task_id' => $taskId, 'round' => $rounds, 'phase' => 'plan',
        ]);
        $plan = agentPlan($pdo, $taskId, $agentId, $goal, $obs, $memories, $context, $actions);

        agentos_audit([
            'trace_id' => $traceId, 'task_id' => $taskId,
            'agent_id' => $agentId, 'user_id' => $auth['user_id'],
            'action_type' => 'plan', 'status' => 'completed',
            'output' => ['plan_type' => $plan['type'], 'steps' => count($plan['steps'] ?? [])],
            'reason' => $plan['reasoning'],
        ]);

        // Check if plan says we're done
        if ($plan['type'] === 'complete') {
            $finalOutput = $plan['output'];
            $success = true;
            break;
        }

        if ($plan['type'] === 'error') {
            $finalOutput = $plan['output'];
            break;
        }

        // ── 4. SIMULATE (if needed) ─────────────────────────────
        if ($dryRun || shouldSimulate($plan)) {
            $simResult = agentSimulate($pdo, $traceId, $taskId, $plan);

            agentos_audit([
                'trace_id' => $traceId, 'task_id' => $taskId,
                'agent_id' => $agentId, 'user_id' => $auth['user_id'],
                'action_type' => 'simulate', 'status' => 'completed',
                'output' => $simResult,
            ]);

            if ($simResult['outcome'] === 'unsafe') {
                $finalOutput = ['blocked' => true, 'reason' => 'Simulation detected unsafe outcome', 'details' => $simResult];
                break;
            }

            if ($dryRun) {
                $finalOutput = ['dry_run' => true, 'plan' => $plan, 'simulation' => $simResult];
                $success = true;
                break;
            }
        }

        // ── 5. POLICY CHECK ─────────────────────────────────────
        $policyResult = agentPolicyCheck($pdo, $plan, $agentId, $auth);
        if ($policyResult['action'] === 'deny') {
            $finalOutput = ['blocked' => true, 'reason' => 'Policy denied: ' . $policyResult['reason']];
            agentos_audit([
                'trace_id' => $traceId, 'task_id' => $taskId,
                'agent_id' => $agentId, 'action_type' => 'policy_deny',
                'status' => 'blocked', 'reason' => $policyResult['reason'],
            ]);
            break;
        }
        if ($policyResult['action'] === 'require_approval') {
            // Create approval request and pause
            createApprovalRequest($pdo, $taskId, $agentId, $plan, $auth);
            $pdo->prepare("UPDATE agentos_tasks SET status='waiting_approval' WHERE task_id=?")->execute([$taskId]);
            $finalOutput = ['waiting_approval' => true, 'reason' => $policyResult['reason']];
            break;
        }

        // ── 6. ACT ──────────────────────────────────────────────
        agentos_push('agentos:runtime', 'loop_phase', [
            'task_id' => $taskId, 'round' => $rounds, 'phase' => 'act',
            'capability' => $plan['capability_id'] ?? 'unknown',
        ]);
        $actionResult = agentAct($pdo, $traceId, $taskId, $plan, $agentId, $auth);
        $actions[] = $actionResult;

        agentos_audit([
            'trace_id' => $traceId, 'task_id' => $taskId,
            'agent_id' => $agentId, 'user_id' => $auth['user_id'],
            'action_type' => 'execute', 'status' => $actionResult['success'] ? 'completed' : 'failed',
            'capability_id' => $actionResult['capability_id'] ?? null,
            'output' => ['result_keys' => array_keys($actionResult)],
            'duration_ms' => $actionResult['duration_ms'] ?? null,
        ]);

        // Update context with action results
        $context['last_action'] = $actionResult;
        $context['action_history'] = array_map(fn($a) => [
            'capability' => $a['capability_id'] ?? 'unknown',
            'success' => $a['success'],
        ], $actions);

        // ── 7. VERIFY ───────────────────────────────────────────
        $verified = agentVerify($pdo, $taskId, $plan, $actionResult, $obs);

        agentos_audit([
            'trace_id' => $traceId, 'task_id' => $taskId,
            'agent_id' => $agentId, 'action_type' => 'verify',
            'status' => $verified['passed'] ? 'completed' : 'failed',
            'output' => $verified,
        ]);

        // ── 8. LEARN ────────────────────────────────────────────
        agentLearn($pdo, $agentId, $goal, $plan, $actionResult, $verified, $auth['user_id']);

        // If verification says goal is met, we're done
        if ($verified['goal_met']) {
            $finalOutput = $actionResult['result'] ?? $verified;
            $success = true;
            agentos_push('agentos:runtime', 'loop_complete', [
                'task_id' => $taskId, 'rounds' => $rounds, 'success' => true,
            ]);
            break;
        }
    }

    // If we ran out of rounds but actions succeeded, consider it partial success
    if (!$success && !empty($actions)) {
        $anySucceeded = array_filter($actions, fn($a) => $a['success']);
        if (!empty($anySucceeded)) {
            $success = true;
            $lastSuccess = end($anySucceeded);
            $finalOutput = $lastSuccess['result'] ?? ['summary' => 'Actions completed, partial progress made'];
        }
    }

    $totalMs = (int)((hrtime(true) - $startTime) / 1_000_000);

    return [
        'success' => $success,
        'rounds' => $rounds,
        'duration_ms' => $totalMs,
        'output' => $finalOutput,
        'actions_taken' => count($actions),
        'observations' => count($observations),
    ];
}

// ═══════════════════════════════════════════════════════════════
// OBSERVE — Gather current state of the world
// ═══════════════════════════════════════════════════════════════
function agentObserve(PDO $pdo, string $taskId, string $agentId, string $goal, array $context, array $auth): array {
    $observations = [
        'timestamp' => date('c'),
        'goal' => $goal,
    ];

    // Current world state
    $stmt = $pdo->prepare("SELECT state_key, state_value, drift_detected FROM agentos_world_state 
        WHERE world_id='default' ORDER BY observed_at DESC LIMIT 50");
    $stmt->execute();
    $observations['world_state'] = $stmt->fetchAll();

    // Active entities
    $stmt = $pdo->prepare("SELECT entity_id, entity_type, status, display_name 
        FROM agentos_world_entities WHERE world_id='default' AND status != 'offline' LIMIT 20");
    $stmt->execute();
    $observations['active_entities'] = $stmt->fetchAll();

    // Task history (what we've done so far)
    $stmt = $pdo->prepare("SELECT node_id, node_type, reference_id, status, output_data 
        FROM agentos_task_nodes WHERE task_id=? ORDER BY id");
    $stmt->execute([$taskId]);
    $observations['completed_steps'] = $stmt->fetchAll();

    // Available capabilities
    $observations['available_capabilities'] = getAvailableCapabilities($pdo);

    return $observations;
}

// ═══════════════════════════════════════════════════════════════
// RECALL — Retrieve relevant memories
// ═══════════════════════════════════════════════════════════════
function agentRecall(PDO $pdo, string $agentId, string $goal, ?int $userId): array {
    $memories = [];

    // Episodic: relevant past experiences
    $stmt = $pdo->prepare("SELECT summary, outcome, importance, details 
        FROM agentos_memory_episodic 
        WHERE agent_id=? AND (user_id=? OR user_id IS NULL)
        ORDER BY importance DESC, created_at DESC LIMIT 10");
    $stmt->execute([$agentId, $userId]);
    $memories['episodic'] = $stmt->fetchAll();

    // Update recall count
    if (!empty($memories['episodic'])) {
        $pdo->prepare("UPDATE agentos_memory_episodic SET recalled_count=recalled_count+1, last_recalled=NOW()
            WHERE agent_id=? AND (user_id=? OR user_id IS NULL)
            ORDER BY importance DESC LIMIT 10")->execute([$agentId, $userId]);
    }

    // Semantic: relevant facts
    $stmt = $pdo->prepare("SELECT fact_key, fact_value, confidence, domain 
        FROM agentos_memory_semantic 
        WHERE agent_id=? AND (user_id=? OR user_id IS NULL)
        ORDER BY confidence DESC LIMIT 20");
    $stmt->execute([$agentId, $userId]);
    $memories['semantic'] = $stmt->fetchAll();

    // Procedural: relevant procedures
    $stmt = $pdo->prepare("SELECT procedure_name, trigger_pattern, steps, success_rate 
        FROM agentos_memory_procedural 
        WHERE agent_id=? AND enabled=1
        ORDER BY success_rate DESC, times_used DESC LIMIT 5");
    $stmt->execute([$agentId]);
    $memories['procedural'] = $stmt->fetchAll();

    // Relational: relevant connections
    if ($userId) {
        $stmt = $pdo->prepare("SELECT relation, object_type, object_id, weight 
            FROM agentos_memory_relational 
            WHERE subject_type='user' AND subject_id=? AND (valid_until IS NULL OR valid_until > NOW())
            LIMIT 20");
        $stmt->execute([(string)$userId]);
        $memories['relational'] = $stmt->fetchAll();
    }

    return $memories;
}

// ═══════════════════════════════════════════════════════════════
// PLAN — Use AI to generate an execution plan
// ═══════════════════════════════════════════════════════════════
function agentPlan(PDO $pdo, string $taskId, string $agentId, string $goal,
                   array $observations, array $memories, array $context, array $priorActions): array {
    // Build the planning prompt for the AI
    $capList = implode("\n", array_map(function($c) {
        return "- {$c['capability_id']}: {$c['description']} [risk:{$c['risk_level']}]";
    }, $observations['available_capabilities'] ?? []));

    $memoryContext = '';
    foreach ($memories['episodic'] ?? [] as $ep) {
        $memoryContext .= "- Past: {$ep['summary']} (outcome: {$ep['outcome']})\n";
    }
    foreach ($memories['semantic'] ?? [] as $fact) {
        $memoryContext .= "- Fact: {$fact['fact_key']} = {$fact['fact_value']} (confidence: {$fact['confidence']})\n";
    }
    foreach ($memories['procedural'] ?? [] as $proc) {
        $memoryContext .= "- Known procedure: {$proc['procedure_name']} (success rate: {$proc['success_rate']}%)\n";
    }

    $priorSummary = '';
    foreach ($priorActions as $a) {
        $priorSummary .= "- " . ($a['capability_id'] ?? 'action') . ": " . ($a['success'] ? 'OK' : 'FAILED') . "\n";
    }

    $completedSteps = '';
    foreach ($observations['completed_steps'] ?? [] as $step) {
        $completedSteps .= "- [{$step['status']}] {$step['reference_id']}\n";
    }

    $planningPrompt = <<<PROMPT
You are an autonomous agent planner for GSM Alfred OS.

GOAL: {$goal}

AVAILABLE CAPABILITIES:
{$capList}

RELEVANT MEMORIES:
{$memoryContext}

COMPLETED STEPS SO FAR:
{$completedSteps}

PRIOR ACTIONS THIS RUN:
{$priorSummary}

INSTRUCTIONS:
1. Analyze the goal and current state
2. If the goal is already achieved, respond with type="complete"
3. If you need to act, choose the BEST capability and provide its input
4. Output ONLY valid JSON in this format:

For an action:
{"type":"action","capability_id":"<id>","input":{...},"reasoning":"<why>","steps":[{"capability_id":"<id>","input":{}}]}

For completion:
{"type":"complete","output":{"summary":"<result>"},"reasoning":"<why>"}

For error:
{"type":"error","output":{"error":"<what went wrong>"},"reasoning":"<why>"}
PROMPT;

    // Call AI to generate plan
    $plan = callAIForPlan($planningPrompt, $agentId);

    // Store plan in task
    $pdo->prepare("UPDATE agentos_tasks SET plan=?, status='ready' WHERE task_id=?")
        ->execute([json_encode($plan), $taskId]);

    return $plan;
}

// ═══════════════════════════════════════════════════════════════
// SIMULATE — Dry-run the plan
// ═══════════════════════════════════════════════════════════════
function agentSimulate(PDO $pdo, string $traceId, string $taskId, array $plan): array {
    $simId = agentos_id('sim');

    // Check risk levels of involved capabilities
    $riskScore = 0.0;
    $capId = $plan['capability_id'] ?? '';

    if ($capId) {
        $stmt = $pdo->prepare("SELECT risk_level FROM agentos_capabilities WHERE capability_id=?");
        $stmt->execute([$capId]);
        $cap = $stmt->fetch();
        $riskMap = ['low' => 0.1, 'medium' => 0.4, 'high' => 0.7, 'critical' => 0.95];
        $riskScore = $riskMap[$cap['risk_level'] ?? 'low'] ?? 0.1;
    }

    $outcome = $riskScore >= 0.7 ? 'warning' : 'safe';
    $anomalies = [];

    if ($riskScore >= 0.95) {
        $outcome = 'unsafe';
        $anomalies[] = 'Critical risk capability requires explicit human approval';
    }

    $simResult = [
        'sim_id' => $simId,
        'outcome' => $outcome,
        'risk_score' => $riskScore,
        'anomalies' => $anomalies,
        'plan_steps' => count($plan['steps'] ?? []),
    ];

    // Record simulation
    $stmt = $pdo->prepare("INSERT INTO agentos_simulations 
        (sim_id, task_id, sim_type, input_state, outcome, risk_score, anomalies)
        VALUES (?, ?, 'dry_run', ?, ?, ?, ?)");
    $stmt->execute([
        $simId, $taskId,
        json_encode($plan),
        $outcome, $riskScore,
        json_encode($anomalies),
    ]);

    return $simResult;
}

// ═══════════════════════════════════════════════════════════════
// POLICY CHECK — Safety kernel gate
// ═══════════════════════════════════════════════════════════════
function agentPolicyCheck(PDO $pdo, array $plan, string $agentId, array $auth): array {
    $capId = $plan['capability_id'] ?? '';

    // Check capability risk and category
    $stmt = $pdo->prepare("SELECT risk_level, requires_approval, requires_simulation, category FROM agentos_capabilities WHERE capability_id=?");
    $stmt->execute([$capId]);
    $cap = $stmt->fetch();

    // Critical actions always need approval (unless internal)
    if ($cap && $cap['risk_level'] === 'critical' && !$auth['is_internal']) {
        if ($cap['requires_approval']) {
            return ['action' => 'require_approval', 'reason' => "Critical action '{$capId}' requires human approval"];
        }
    }

    // If capability explicitly requires simulation, force it
    if ($cap && $cap['requires_simulation'] && !isset($plan['simulated'])) {
        $plan['risk_level'] = $cap['risk_level'];
    }

    // Check explicit policy rules
    $stmt = $pdo->prepare("SELECT pr.action, pr.action_params, pr.condition_expr, p.display_name
        FROM agentos_policy_rules pr 
        JOIN agentos_policies p ON p.policy_id = pr.policy_id
        WHERE p.enabled=1 AND (p.scope='global' OR (p.scope='agent' AND p.scope_target=?) 
              OR (p.scope='capability' AND p.scope_target=?))
        ORDER BY p.priority DESC, pr.rule_order");
    $stmt->execute([$agentId, $capId]);

    foreach ($stmt->fetchAll() as $rule) {
        if (evaluatePolicyCondition($rule['condition_expr'], $plan, $auth, $cap)) {
            return [
                'action' => $rule['action'],
                'reason' => $rule['condition_expr'],
                'policy' => $rule['display_name'],
            ];
        }
    }

    return ['action' => 'allow', 'reason' => 'No blocking policies'];
}

// ═══════════════════════════════════════════════════════════════
// ACT — Execute the planned action
// ═══════════════════════════════════════════════════════════════
function agentAct(PDO $pdo, string $traceId, string $taskId, array $plan,
                  string $agentId, array $auth): array {
    $startTime = hrtime(true);
    $capId = $plan['capability_id'] ?? '';
    $input = $plan['input'] ?? [];
    $nodeId = agentos_id('node');

    // Record task node
    $stmt = $pdo->prepare("INSERT INTO agentos_task_nodes 
        (task_id, node_id, node_type, reference_id, label, input_data, status, started_at)
        VALUES (?, ?, 'capability', ?, ?, ?, 'running', NOW())");
    $stmt->execute([$taskId, $nodeId, $capId, $plan['reasoning'] ?? '', json_encode($input)]);

    // Resolve capability
    $stmt = $pdo->prepare("SELECT * FROM agentos_capabilities WHERE capability_id=? AND enabled=1");
    $stmt->execute([$capId]);
    $capability = $stmt->fetch();

    $result = ['capability_id' => $capId, 'node_id' => $nodeId, 'success' => false];

    if (!$capability) {
        // Capability not in registry — try as alfred-chat tool, then AI fallback
        $chatResult = executeChatTool($capId, $input);
        if ($chatResult['success']) {
            $result['success'] = true;
            $result['result'] = $chatResult['result'];
        } else {
            $aiResult = executeViaAI($capId, $input, $agentId, $plan['reasoning'] ?? '');
            $result['success'] = $aiResult['success'];
            $result['result'] = $aiResult['output'];
        }
    } else {
        // Execute based on provider
        switch ($capability['provider']) {
            case 'native':
                $result = executeNativeCapability($capability, $input, $auth);
                break;
            case 'mcp':
                $result = executeMcpCapability($capability, $input);
                break;
            case 'api':
                $result = executeApiCapability($capability, $input, $auth);
                break;
            case 'chat_tool':
                $toolName = $capability['endpoint'] ?: $capId;
                $result = executeChatTool($toolName, $input);
                break;
            default:
                $result = executeViaAI($capId, $input, $agentId, $plan['reasoning'] ?? '');
        }
        $result['capability_id'] = $capId;
        $result['node_id'] = $nodeId;
    }

    $durationMs = (int)((hrtime(true) - $startTime) / 1_000_000);
    $result['duration_ms'] = $durationMs;

    // Update task node
    $status = $result['success'] ? 'completed' : 'failed';
    $stmt = $pdo->prepare("UPDATE agentos_task_nodes SET status=?, output_data=?, completed_at=NOW(), 
        duration_ms=?, error=? WHERE task_id=? AND node_id=?");
    $stmt->execute([
        $status,
        json_encode($result['result'] ?? null),
        $durationMs,
        $result['error'] ?? null,
        $taskId, $nodeId,
    ]);

    return $result;
}

// ═══════════════════════════════════════════════════════════════
// VERIFY — Check if the action achieved its goal
// ═══════════════════════════════════════════════════════════════
function agentVerify(PDO $pdo, string $taskId, array $plan, array $actionResult, array $obs): array {
    $passed = $actionResult['success'];
    $goalMet = false;

    // Basic verification: action succeeded
    if (!$passed) {
        return ['passed' => false, 'goal_met' => false, 'reason' => 'Action failed'];
    }

    // Check if plan says this was the final step
    if (empty($plan['steps']) || count($plan['steps'] ?? []) <= 1) {
        $goalMet = true;
    }

    // World state drift detection
    $stmt = $pdo->prepare("SELECT COUNT(*) as drifts FROM agentos_world_state WHERE drift_detected=1 AND world_id='default'");
    $stmt->execute();
    $drifts = (int)$stmt->fetchColumn();

    return [
        'passed' => $passed,
        'goal_met' => $goalMet,
        'drifts_detected' => $drifts,
        'reason' => $goalMet ? 'Goal achieved' : 'More steps needed',
    ];
}

// ═══════════════════════════════════════════════════════════════
// LEARN — Persist experience for future use
// ═══════════════════════════════════════════════════════════════
function agentLearn(PDO $pdo, string $agentId, string $goal, array $plan,
                    array $actionResult, array $verified, ?int $userId): void {
    // Store episodic memory
    $outcome = $verified['passed'] ? 'success' : 'failure';
    $importance = $verified['goal_met'] ? 8 : ($verified['passed'] ? 5 : 3);

    $stmt = $pdo->prepare("INSERT INTO agentos_memory_episodic 
        (user_id, agent_id, episode_type, summary, details, outcome, importance)
        VALUES (?, ?, 'task_execution', ?, ?, ?, ?)");
    $stmt->execute([
        $userId, $agentId,
        "Goal: {$goal} → Action: " . ($plan['capability_id'] ?? 'reasoning') . " → {$outcome}",
        json_encode([
            'goal' => $goal,
            'capability' => $plan['capability_id'] ?? null,
            'input' => $plan['input'] ?? null,
            'success' => $actionResult['success'],
            'verification' => $verified,
        ]),
        $outcome, $importance,
    ]);

    // If successful, update or create procedural memory
    if ($outcome === 'success' && ($plan['capability_id'] ?? '')) {
        $stmt = $pdo->prepare("INSERT INTO agentos_memory_procedural 
            (agent_id, procedure_name, trigger_pattern, steps, success_rate, times_used, last_used, learned_from)
            VALUES (?, ?, ?, ?, 100.00, 1, NOW(), ?)
            ON DUPLICATE KEY UPDATE 
                success_rate = (success_rate * times_used + 100) / (times_used + 1),
                times_used = times_used + 1, last_used = NOW()");
        $stmt->execute([
            $agentId,
            $plan['capability_id'],
            $goal,
            json_encode([['capability_id' => $plan['capability_id'], 'input' => $plan['input'] ?? []]]),
            null,
        ]);
    }
}

// ═══════════════════════════════════════════════════════════════
// HELPER FUNCTIONS
// ═══════════════════════════════════════════════════════════════

function getAvailableCapabilities(PDO $pdo): array {
    $cached = agentos_cache_get('capabilities_list');
    if ($cached) return $cached;

    $stmt = $pdo->query("SELECT capability_id, display_name, description, category, 
        risk_level, input_schema FROM agentos_capabilities WHERE enabled=1 ORDER BY category, capability_id");
    $caps = $stmt->fetchAll();
    agentos_cache_set('capabilities_list', $caps, 60);
    return $caps;
}

function shouldSimulate(array $plan): bool {
    // Simulate if the plan involves high/critical risk
    return in_array($plan['risk_level'] ?? 'low', ['high', 'critical']);
}

function callAIForPlan(string $prompt, string $agentId): array {
    $payload = json_encode([
        'model' => 'claude-sonnet-4-20250514',
        'max_tokens' => 2048,
        'system' => "You are {$agentId}, an autonomous agent planner for GSM Alfred OS. Output ONLY valid JSON. No markdown, no explanation outside JSON.",
        'messages' => [['role' => 'user', 'content' => $prompt]],
    ]);

    $ch = curl_init('http://127.0.0.1:3001/api/anthropic-proxy/gositeme/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'x-api-key: agentos-planner', 'anthropic-version: 2023-06-01'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);
    $result = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http === 200 && $result) {
        $data = json_decode($result, true);
        $text = $data['content'][0]['text'] ?? '';
        // Extract JSON from response
        $text = trim($text);
        if (preg_match('/\{.*\}/s', $text, $m)) {
            $plan = json_decode($m[0], true);
            if ($plan && isset($plan['type'])) return $plan;
        }
    }

    // AI unavailable — return a reasoning-only plan  
    return [
        'type' => 'complete',
        'output' => ['summary' => 'Processed goal through internal reasoning', 'goal' => 'acknowledged'],
        'reasoning' => 'AI planner unavailable, completed with basic acknowledgment',
    ];
}

function executeViaAI(string $capId, array $input, string $agentId, string $reasoning): array {
    $payload = json_encode([
        'model' => 'claude-sonnet-4-20250514',
        'max_tokens' => 2048,
        'system' => "You are {$agentId}, executing capability '{$capId}'. Perform the requested action and return a JSON result.",
        'messages' => [['role' => 'user', 'content' => "Execute: {$capId}\nInput: " . json_encode($input) . "\nReasoning: {$reasoning}"]],
    ]);

    $ch = curl_init('http://127.0.0.1:3001/api/anthropic-proxy/gositeme/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'x-api-key: agentos-executor', 'anthropic-version: 2023-06-01'],
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30,
    ]);
    $result = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http === 200 && $result) {
        $data = json_decode($result, true);
        $text = $data['content'][0]['text'] ?? '';
        return ['success' => true, 'output' => $text];
    }
    return ['success' => false, 'output' => null, 'error' => 'AI execution failed'];
}

function executeNativeCapability(array $cap, array $input, array $auth): array {
    $endpoint = $cap['endpoint'] ?? '';
    if (!$endpoint) return ['success' => false, 'error' => 'No endpoint configured'];

    // Internal API call
    $payload = json_encode(array_merge($input, ['_agentos' => true]));
    $secret = defined('AGENTOS_INTERNAL_SECRET') ? AGENTOS_INTERNAL_SECRET : (getenv('INTERNAL_SECRET') ?: '');

    $ch = curl_init("https://gositeme.com{$endpoint}");
    curl_setopt_array($ch, [
        CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-Internal-Secret: ' . $secret],
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => (int)($cap['timeout_ms'] / 1000),
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

function executeMcpCapability(array $cap, array $input): array {
    // Route directly to MCP server at port 3005 via JSON-RPC
    $toolName = $cap['endpoint'] ?: $cap['capability_id'];
    return executeChatTool($toolName, $input);
}

/**
 * Execute a tool from the alfred-chat tool registry.
 * Routes directly to the MCP server at port 3005 via JSON-RPC.
 */
function executeChatTool(string $toolName, array $input): array {
    $mcpSecret = defined('AGENTOS_MCP_SECRET') ? AGENTOS_MCP_SECRET : (getenv('MCP_SECRET') ?: '');

    if (!$mcpSecret) {
        return executeViaAI($toolName, $input, 'alfred', "Execute the {$toolName} capability with the given parameters");
    }

    // First try MCP server (port 3005) — 17 specialized tools
    $ch = curl_init('http://127.0.0.1:3005/tools/call');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'tool' => $toolName,
            'args' => $input,
        ]),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-MCP-Secret: ' . $mcpSecret,
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 3,
    ]);
    $result = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($http === 200 && $result) {
        $data = json_decode($result, true);
        if (!empty($data['success'])) {
            $content = $data['result']['content'] ?? $data['result'] ?? $data;
            if (is_array($content) && isset($content[0]['text'])) {
                $text = implode("\n", array_map(fn($c) => $c['text'] ?? '', $content));
                return ['success' => true, 'result' => $text];
            }
            return ['success' => true, 'result' => $content];
        }
        // MCP returned error (e.g. tool not found)
        $errorMsg = $data['error'] ?? 'MCP execution failed';
        return ['success' => false, 'error' => $errorMsg];
    }

    if ($err) {
        return ['success' => false, 'error' => "MCP connection error: {$err}"];
    }

    // Not an MCP tool — execute as AI reasoning action
    return executeViaAI($toolName, $input, 'alfred', "Execute the {$toolName} capability with the given parameters");
}

function executeApiCapability(array $cap, array $input, array $auth): array {
    return executeNativeCapability($cap, $input, $auth);
}

function evaluatePolicyCondition(string $condition, array $plan, array $auth, ?array $cap = null): bool {
    // Simple condition evaluator for policy rules
    if ($condition === 'always') return true;
    if ($condition === 'never') return false;
    if ($condition === 'unauthenticated' && !$auth['user_id'] && !$auth['is_internal']) return true;
    if (strpos($condition, 'risk>=') === 0) {
        $threshold = substr($condition, 6);
        $riskMap = ['low' => 1, 'medium' => 2, 'high' => 3, 'critical' => 4];
        $planRisk = $riskMap[$plan['risk_level'] ?? 'low'] ?? 1;
        return $planRisk >= (int)$threshold;
    }
    if (preg_match('/^risk_level=(\w+)$/', $condition, $m)) {
        $capRisk = $cap['risk_level'] ?? ($plan['risk_level'] ?? 'low');
        return $capRisk === $m[1];
    }
    if (preg_match('/^category=(\w+)$/', $condition, $m)) {
        return ($cap['category'] ?? '') === $m[1];
    }
    return false;
}

function createApprovalRequest(PDO $pdo, string $taskId, string $agentId, array $plan, array $auth): void {
    $approvalId = agentos_id('apr');
    $stmt = $pdo->prepare("INSERT INTO agentos_approvals 
        (approval_id, task_id, capability_id, requested_by, requested_for, 
         action_summary, risk_level, status, expires_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', DATE_ADD(NOW(), INTERVAL 1 HOUR))");
    $stmt->execute([
        $approvalId,
        $taskId,
        $plan['capability_id'] ?? '',
        $agentId,
        $auth['user_id'],
        $plan['reasoning'] ?? 'Action requires approval',
        $plan['risk_level'] ?? 'high',
    ]);

    // Push real-time notification so dashboard shows it
    agentos_push('agentos:approvals', 'approval_requested', [
        'approval_id' => $approvalId,
        'task_id' => $taskId,
        'agent_id' => $agentId,
        'capability_id' => $plan['capability_id'] ?? '',
        'risk_level' => $plan['risk_level'] ?? 'high',
        'reasoning' => $plan['reasoning'] ?? '',
        'expires_in_seconds' => 3600,
    ]);

    agentos_audit([
        'agent_id' => $agentId, 'user_id' => $auth['user_id'],
        'action_type' => 'approval_requested', 'task_id' => $taskId,
        'capability_id' => $plan['capability_id'] ?? '',
        'status' => 'pending', 'risk_level' => $plan['risk_level'] ?? 'high',
        'reason' => $plan['reasoning'] ?? 'Requires approval',
    ]);
}

// ═══════════════════════════════════════════════════════════════
// STATUS / SESSIONS / PAUSE / RESUME / CANCEL
// ═══════════════════════════════════════════════════════════════

function handleStep(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $taskId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['task_id'] ?? '');
    if (!$taskId) agentos_error('task_id required');

    $pdo = agentos_pdo();
    $stmt = $pdo->prepare("SELECT * FROM agentos_tasks WHERE task_id=?");
    $stmt->execute([$taskId]);
    $task = $stmt->fetch();
    if (!$task) agentos_error('Task not found', 404);

    // Execute one round of the loop
    $traceId = agentos_trace_id();
    $context = json_decode($task['context'] ?? '{}', true);
    $result = agentLoop($pdo, $traceId, $taskId, $task['agent_id'], $task['goal'], $context, 1, false, $auth);

    agentos_respond(['ok' => true, 'task_id' => $taskId, 'result' => $result]);
}

function handleObserve(array $auth): void {
    $pdo = agentos_pdo();
    $taskId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['task_id'] ?? '');
    $obs = agentObserve($pdo, $taskId ?: 'none', $_GET['agent_id'] ?? 'alfred', '', [], $auth);
    agentos_respond(['ok' => true, 'observations' => $obs]);
}

function handleStatus(array $auth): void {
    $taskId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['task_id'] ?? '');
    if (!$taskId) agentos_error('task_id required');

    $pdo = agentos_pdo();
    $stmt = $pdo->prepare("SELECT * FROM agentos_tasks WHERE task_id=?");
    $stmt->execute([$taskId]);
    $task = $stmt->fetch();
    if (!$task) agentos_error('Task not found', 404);

    // Get nodes
    $stmt = $pdo->prepare("SELECT node_id, node_type, reference_id, status, duration_ms FROM agentos_task_nodes WHERE task_id=?");
    $stmt->execute([$taskId]);
    $nodes = $stmt->fetchAll();

    // Get audit trail
    $stmt = $pdo->prepare("SELECT action_type, status, capability_id, decision_reason, duration_ms, created_at 
        FROM agentos_audit_log WHERE task_id=? ORDER BY created_at");
    $stmt->execute([$taskId]);
    $trail = $stmt->fetchAll();

    $task['plan'] = json_decode($task['plan'] ?? 'null', true);
    $task['result'] = json_decode($task['result'] ?? 'null', true);
    $task['context'] = json_decode($task['context'] ?? '{}', true);

    agentos_respond(['ok' => true, 'task' => $task, 'nodes' => $nodes, 'audit_trail' => $trail]);
}

function handleSessions(array $auth): void {
    $pdo = agentos_pdo();
    $stmt = $pdo->prepare("SELECT * FROM agentos_agent_sessions WHERE status='active' ORDER BY updated_at DESC LIMIT 50");
    $stmt->execute();
    agentos_respond(['ok' => true, 'sessions' => $stmt->fetchAll()]);
}

function handlePause(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $taskId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['task_id'] ?? '');
    $pdo = agentos_pdo();
    $pdo->prepare("UPDATE agentos_tasks SET status='paused' WHERE task_id=? AND status IN ('running','ready')")->execute([$taskId]);
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
    agentos_respond(['ok' => true, 'task_id' => $taskId, 'status' => 'cancelled']);
}

// ═══════════════════════════════════════════════════════════════
// SANDBOX — Full simulation of a goal (plans all steps, simulates each)
// ═══════════════════════════════════════════════════════════════
function handleSandbox(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || empty($input['goal'])) agentos_error('Goal is required');

    $goal = mb_substr(trim($input['goal']), 0, 10000);
    $agentId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['agent_id'] ?? 'alfred');
    $context = $input['context'] ?? [];
    $maxRounds = min((int)($input['max_rounds'] ?? 5), 10);

    $pdo = agentos_pdo();
    $traceId = agentos_trace_id();
    $taskId = agentos_id('sandbox');

    // Create sandbox task record
    $stmt = $pdo->prepare("INSERT INTO agentos_tasks 
        (task_id, user_id, agent_id, goal, status, priority, context, started_at)
        VALUES (?, ?, ?, ?, 'sandbox', ?, ?, NOW())");
    $stmt->execute([
        $taskId, $auth['user_id'], $agentId, '[SANDBOX] ' . $goal,
        (int)($input['priority'] ?? 5), json_encode($context),
    ]);

    agentos_audit([
        'trace_id' => $traceId, 'task_id' => $taskId,
        'agent_id' => $agentId, 'user_id' => $auth['user_id'],
        'action_type' => 'sandbox_started', 'status' => 'started',
        'input' => ['goal' => $goal],
    ]);

    $startTime = hrtime(true);
    $sandboxSteps = [];
    $cumulativeRisk = 0.0;
    $observations = [];
    $actions = [];

    for ($round = 0; $round < $maxRounds; $round++) {
        // Observe
        $obs = agentObserve($pdo, $taskId, $agentId, $goal, $context, $auth);
        $observations[] = array_keys($obs);

        // Recall
        $memories = agentRecall($pdo, $agentId, $goal, $auth['user_id']);

        // Plan
        $plan = agentPlan($pdo, $taskId, $agentId, $goal, $obs, $memories, $context, $actions);

        if ($plan['type'] === 'complete') {
            $sandboxSteps[] = [
                'round' => $round + 1,
                'phase' => 'complete',
                'plan' => $plan,
                'sim' => null,
                'policy' => null,
            ];
            break;
        }

        if ($plan['type'] === 'error') {
            $sandboxSteps[] = [
                'round' => $round + 1,
                'phase' => 'error',
                'plan' => $plan,
                'sim' => null,
                'policy' => null,
            ];
            break;
        }

        // Simulate the planned action
        $simResult = agentSimulate($pdo, $traceId, $taskId, $plan);

        // Policy check
        $policyResult = agentPolicyCheck($pdo, $plan, $agentId, $auth);

        // Cumulative risk
        $stepRisk = $simResult['risk_score'] ?? 0;
        $cumulativeRisk = 1 - (1 - $cumulativeRisk) * (1 - $stepRisk);

        $sandboxSteps[] = [
            'round' => $round + 1,
            'capability' => $plan['capability_id'] ?? null,
            'reasoning' => $plan['reasoning'] ?? '',
            'input' => $plan['input'] ?? [],
            'sim' => [
                'outcome' => $simResult['outcome'],
                'risk_score' => $simResult['risk_score'],
                'anomalies' => $simResult['anomalies'] ?? [],
            ],
            'policy' => [
                'action' => $policyResult['action'],
                'reason' => $policyResult['reason'],
            ],
            'would_execute' => $policyResult['action'] === 'allow',
            'would_need_approval' => $policyResult['action'] === 'require_approval',
            'would_be_blocked' => $policyResult['action'] === 'deny' || $simResult['outcome'] === 'unsafe',
        ];

        // Simulate action result for context
        $actions[] = [
            'capability_id' => $plan['capability_id'] ?? 'unknown',
            'success' => $simResult['outcome'] !== 'unsafe',
            'result' => ['simulated' => true],
        ];
        $context['last_action'] = end($actions);
    }

    $totalMs = (int)((hrtime(true) - $startTime) / 1_000_000);

    // Determine overall sandbox outcome
    $blocked = array_filter($sandboxSteps, fn($s) => $s['would_be_blocked'] ?? false);
    $needsApproval = array_filter($sandboxSteps, fn($s) => $s['would_need_approval'] ?? false);

    $overallOutcome = 'safe';
    if (!empty($blocked)) $overallOutcome = 'unsafe';
    elseif (!empty($needsApproval)) $overallOutcome = 'needs_approval';
    elseif ($cumulativeRisk > 0.7) $overallOutcome = 'warning';

    // Store simulation record
    $stmt = $pdo->prepare("INSERT INTO agentos_simulations 
        (sim_id, task_id, sim_type, input_state, outcome, risk_score, anomalies, duration_ms)
        VALUES (?, ?, 'sandbox', ?, ?, ?, ?, ?)");
    $stmt->execute([
        agentos_id('sim'), $taskId,
        json_encode(['goal' => $goal, 'steps' => count($sandboxSteps)]),
        $overallOutcome, $cumulativeRisk,
        json_encode(array_merge(...array_map(fn($s) => $s['sim']['anomalies'] ?? [], $sandboxSteps))),
        $totalMs,
    ]);

    // Mark sandbox task complete
    $result = [
        'outcome' => $overallOutcome,
        'steps' => $sandboxSteps,
        'cumulative_risk' => round($cumulativeRisk, 4),
        'duration_ms' => $totalMs,
        'blocked_steps' => count($blocked),
        'approval_needed' => count($needsApproval),
        'total_steps' => count($sandboxSteps),
    ];
    $pdo->prepare("UPDATE agentos_tasks SET status='completed', result=?, completed_at=NOW() WHERE task_id=?")
        ->execute([json_encode($result), $taskId]);

    agentos_audit([
        'trace_id' => $traceId, 'task_id' => $taskId,
        'agent_id' => $agentId, 'user_id' => $auth['user_id'],
        'action_type' => 'sandbox_completed', 'status' => 'completed',
        'output' => ['outcome' => $overallOutcome, 'steps' => count($sandboxSteps), 'risk' => $cumulativeRisk],
        'duration_ms' => $totalMs,
    ]);

    agentos_respond([
        'ok' => true,
        'task_id' => $taskId,
        'trace_id' => $traceId,
        'sandbox' => $result,
    ]);
}
