<?php
/**
 * GSM Alfred OS — Policy & Safety Kernel v2.0 (Phase 3)
 * Enforcement layer for all agent actions
 *
 * Endpoints:
 *   GET    ?action=list              — List policies
 *   GET    ?action=get&id=X          — Get policy with rules
 *   POST   ?action=create            — Create policy
 *   POST   ?action=add_rule          — Add rule to policy
 *   POST   ?action=check             — Check action against policies
 *   POST   ?action=approve           — Approve a pending action
 *   POST   ?action=deny              — Deny a pending action
 *   GET    ?action=approvals         — List pending approvals (status: pending|approved|denied|expired)
 *   POST   ?action=kill_switch       — Emergency kill switch (all agents)
 *   POST   ?action=kill_task         — Kill a specific task
 *   POST   ?action=rollback          — Rollback a completed task's actions
 *   POST   ?action=expire_approvals  — Expire stale approval requests
 *   POST   ?action=seed              — Seed default safety policies
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
    case 'list':        handleList($auth); break;
    case 'get':         handleGet($auth); break;
    case 'create':      handleCreate($auth); break;
    case 'add_rule':    handleAddRule($auth); break;
    case 'check':       handleCheck($auth); break;
    case 'approve':     handleApprove($auth); break;
    case 'deny':        handleDeny($auth); break;
    case 'approvals':   handleApprovals($auth); break;
    case 'kill_switch':      handleKillSwitch($auth); break;
    case 'kill_task':        handleKillTask($auth); break;
    case 'rollback':         handleRollback($auth); break;
    case 'expire_approvals': handleExpireApprovals($auth); break;
    case 'seed':             handleSeed($auth); break;
    default:                 agentos_error('Unknown action');
}

function handleList(array $auth): void {
    $pdo = agentos_pdo();
    $stmt = $pdo->query("SELECT policy_id, display_name, description, scope, scope_target, 
        priority, enabled, created_at,
        (SELECT COUNT(*) FROM agentos_policy_rules WHERE policy_id=p.policy_id) as rule_count
        FROM agentos_policies p ORDER BY priority DESC, created_at");
    agentos_respond(['ok' => true, 'policies' => $stmt->fetchAll()]);
}

function handleGet(array $auth): void {
    $id = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['id'] ?? '');
    if (!$id) agentos_error('id required');

    $pdo = agentos_pdo();
    $stmt = $pdo->prepare("SELECT * FROM agentos_policies WHERE policy_id=?");
    $stmt->execute([$id]);
    $policy = $stmt->fetch();
    if (!$policy) agentos_error('Policy not found', 404);

    $stmt = $pdo->prepare("SELECT * FROM agentos_policy_rules WHERE policy_id=? ORDER BY rule_order");
    $stmt->execute([$id]);
    $policy['rules'] = $stmt->fetchAll();

    foreach ($policy['rules'] as &$rule) {
        $rule['action_params'] = json_decode($rule['action_params'] ?? 'null', true);
    }

    agentos_respond(['ok' => true, 'policy' => $policy]);
}

function handleCreate(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || empty($input['display_name'])) agentos_error('display_name required');

    $pdo = agentos_pdo();
    $policyId = $input['policy_id'] ?? agentos_id('pol');
    $policyId = preg_replace('/[^a-zA-Z0-9_-]/', '', mb_substr($policyId, 0, 100));

    $validScopes = ['global', 'agent', 'capability', 'user', 'task'];
    $scope = in_array($input['scope'] ?? '', $validScopes) ? $input['scope'] : 'global';

    $stmt = $pdo->prepare("INSERT INTO agentos_policies 
        (policy_id, display_name, description, scope, scope_target, priority, enabled)
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $policyId,
        mb_substr($input['display_name'], 0, 200),
        mb_substr($input['description'] ?? '', 0, 2000),
        $scope,
        mb_substr($input['scope_target'] ?? '', 0, 100),
        (int)($input['priority'] ?? 50),
        isset($input['enabled']) ? (int)$input['enabled'] : 1,
    ]);

    // Add rules if provided
    if (!empty($input['rules'])) {
        addRulesToPolicy($pdo, $policyId, $input['rules']);
    }

    agentos_respond(['ok' => true, 'policy_id' => $policyId], 201);
}

function handleAddRule(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $policyId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['policy_id'] ?? '');
    if (!$policyId) agentos_error('policy_id required');

    $pdo = agentos_pdo();
    addRulesToPolicy($pdo, $policyId, [$input]);

    agentos_respond(['ok' => true, 'policy_id' => $policyId]);
}

// ═══════════════════════════════════════════════════════════════
// CHECK — Evaluate action against all active policies
// ═══════════════════════════════════════════════════════════════
function handleCheck(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) agentos_error('Request body required');

    $capabilityId = mb_substr($input['capability_id'] ?? '', 0, 100);
    $agentId = mb_substr($input['agent_id'] ?? 'alfred', 0, 50);
    $userId = $auth['user_id'];
    $riskLevel = $input['risk_level'] ?? 'low';

    $pdo = agentos_pdo();

    // Get all applicable policies in priority order
    $stmt = $pdo->prepare("SELECT p.policy_id, p.display_name, p.scope, p.scope_target, p.priority,
        pr.rule_order, pr.condition_expr, pr.action, pr.action_params
        FROM agentos_policies p
        JOIN agentos_policy_rules pr ON pr.policy_id = p.policy_id
        WHERE p.enabled=1 
        AND (p.scope='global' 
             OR (p.scope='agent' AND p.scope_target=?)
             OR (p.scope='capability' AND p.scope_target=?)
             OR (p.scope='user' AND p.scope_target=?))
        ORDER BY p.priority DESC, pr.rule_order");
    $stmt->execute([$agentId, $capabilityId, (string)($userId ?? '')]);
    $rules = $stmt->fetchAll();

    // Check capability risk
    $stmt = $pdo->prepare("SELECT risk_level, requires_simulation, requires_approval 
        FROM agentos_capabilities WHERE capability_id=?");
    $stmt->execute([$capabilityId]);
    $capInfo = $stmt->fetch();

    $result = [
        'decision' => 'allow',
        'reasons' => [],
        'matching_rules' => [],
        'requires_simulation' => false,
        'requires_approval' => false,
    ];

    // Capability-level requirements
    if ($capInfo) {
        if ($capInfo['requires_simulation']) $result['requires_simulation'] = true;
        if ($capInfo['requires_approval']) $result['requires_approval'] = true;
    }

    // Evaluate rules
    $riskMap = ['low' => 1, 'medium' => 2, 'high' => 3, 'critical' => 4];
    $currentRisk = $riskMap[$riskLevel] ?? 1;

    foreach ($rules as $rule) {
        $matches = evaluateCondition($rule['condition_expr'], [
            'capability_id' => $capabilityId,
            'agent_id' => $agentId,
            'user_id' => $userId,
            'risk_level' => $riskLevel,
            'risk_score' => $currentRisk,
            'is_internal' => $auth['is_internal'],
        ]);

        if ($matches) {
            $result['matching_rules'][] = [
                'policy' => $rule['display_name'],
                'rule' => $rule['condition_expr'],
                'action' => $rule['action'],
            ];

            switch ($rule['action']) {
                case 'deny':
                    $result['decision'] = 'deny';
                    $result['reasons'][] = "Denied by policy '{$rule['display_name']}': {$rule['condition_expr']}";
                    break 2; // Stop on first deny
                case 'require_approval':
                    $result['decision'] = 'require_approval';
                    $result['requires_approval'] = true;
                    $result['reasons'][] = "Approval required by '{$rule['display_name']}'";
                    break;
                case 'require_simulation':
                    $result['requires_simulation'] = true;
                    $result['reasons'][] = "Simulation required by '{$rule['display_name']}'";
                    break;
                case 'log':
                    // Just log, don't block
                    agentos_audit([
                        'agent_id' => $agentId, 'user_id' => $userId,
                        'action_type' => 'policy_log', 'capability_id' => $capabilityId,
                        'status' => 'logged', 'reason' => $rule['condition_expr'],
                    ]);
                    break;
            }
        }
    }

    agentos_respond(['ok' => true, 'check' => $result]);
}

// ═══════════════════════════════════════════════════════════════
// APPROVALS — Manage approval requests
// ═══════════════════════════════════════════════════════════════
function handleApprovals(array $auth): void {
    $pdo = agentos_pdo();
    $status = $_GET['status'] ?? null;
    $limit = min((int)($_GET['limit'] ?? 50), 200);

    $where = ['1=1'];
    $params = [];

    if ($status) {
        $where[] = 'a.status=?';
        $params[] = $status;
    }

    $stmt = $pdo->prepare("SELECT a.*, t.goal as task_goal, t.agent_id as task_agent,
        t.status as task_status,
        TIMESTAMPDIFF(SECOND, a.created_at, COALESCE(a.decided_at, NOW())) as wait_seconds
        FROM agentos_approvals a
        LEFT JOIN agentos_tasks t ON t.task_id = a.task_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY FIELD(a.status, 'pending', 'approved', 'denied', 'expired'), a.created_at DESC 
        LIMIT " . (int)$limit);
    $stmt->execute($params);
    $approvals = $stmt->fetchAll();

    // Counts by status
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM agentos_approvals GROUP BY status");
    $counts = [];
    foreach ($stmt->fetchAll() as $row) $counts[$row['status']] = (int)$row['count'];

    agentos_respond(['ok' => true, 'approvals' => $approvals, 'counts' => $counts]);
}

function handleApprove(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $approvalId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['approval_id'] ?? '');
    if (!$approvalId) agentos_error('approval_id required');

    $pdo = agentos_pdo();
    $stmt = $pdo->prepare("UPDATE agentos_approvals SET status='approved', 
        decided_by=?, decided_at=NOW(), decision_reason=?
        WHERE approval_id=? AND status='pending'");
    $stmt->execute([
        $auth['user_id'] ?? $auth['username'],
        mb_substr($input['reason'] ?? 'Approved', 0, 2000),
        $approvalId,
    ]);

    if ($stmt->rowCount() === 0) agentos_error('Approval not found or already decided');

    // Resume the task if it was waiting
    $stmt = $pdo->prepare("SELECT task_id FROM agentos_approvals WHERE approval_id=?");
    $stmt->execute([$approvalId]);
    $taskId = $stmt->fetchColumn();
    if ($taskId) {
        $pdo->prepare("UPDATE agentos_tasks SET status='ready' WHERE task_id=? AND status='waiting_approval'")
            ->execute([$taskId]);
    }

    agentos_audit([
        'agent_id' => 'system', 'user_id' => $auth['user_id'],
        'action_type' => 'approval_granted', 'task_id' => $taskId,
        'status' => 'completed', 'reason' => $input['reason'] ?? 'Approved',
    ]);

    agentos_push('agentos:approvals', 'approval_decided', [
        'approval_id' => $approvalId, 'decision' => 'approved',
        'task_id' => $taskId, 'decided_by' => $auth['username'] ?? 'admin',
    ]);

    agentos_respond(['ok' => true, 'approval_id' => $approvalId, 'status' => 'approved']);
}

function handleDeny(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $approvalId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['approval_id'] ?? '');
    if (!$approvalId) agentos_error('approval_id required');

    $pdo = agentos_pdo();
    $stmt = $pdo->prepare("UPDATE agentos_approvals SET status='denied', 
        decided_by=?, decided_at=NOW(), decision_reason=?
        WHERE approval_id=? AND status='pending'");
    $stmt->execute([
        $auth['user_id'] ?? $auth['username'],
        mb_substr($input['reason'] ?? 'Denied', 0, 2000),
        $approvalId,
    ]);

    if ($stmt->rowCount() === 0) agentos_error('Approval not found or already decided');

    // Cancel the task
    $stmt = $pdo->prepare("SELECT task_id FROM agentos_approvals WHERE approval_id=?");
    $stmt->execute([$approvalId]);
    $taskId = $stmt->fetchColumn();
    if ($taskId) {
        $pdo->prepare("UPDATE agentos_tasks SET status='cancelled', completed_at=NOW() WHERE task_id=? AND status='waiting_approval'")
            ->execute([$taskId]);
    }

    agentos_audit([
        'agent_id' => 'system', 'user_id' => $auth['user_id'],
        'action_type' => 'approval_denied', 'task_id' => $taskId,
        'status' => 'completed', 'reason' => $input['reason'] ?? 'Denied',
    ]);

    agentos_push('agentos:approvals', 'approval_decided', [
        'approval_id' => $approvalId, 'decision' => 'denied',
        'task_id' => $taskId, 'decided_by' => $auth['username'] ?? 'admin',
    ]);

    agentos_respond(['ok' => true, 'approval_id' => $approvalId, 'status' => 'denied']);
}

// ═══════════════════════════════════════════════════════════════
// KILL SWITCH — Emergency stop for all agents
// ═══════════════════════════════════════════════════════════════
function handleKillSwitch(array $auth): void {
    if (!$auth['is_internal'] && !$auth['user_id']) {
        agentos_error('Kill switch requires authentication', 403);
    }

    $pdo = agentos_pdo();

    // Cancel all running tasks
    $stmt = $pdo->prepare("UPDATE agentos_tasks SET status='cancelled', 
        result=JSON_OBJECT('reason', 'Emergency kill switch activated'),
        completed_at=NOW()
        WHERE status IN ('running', 'ready', 'planning', 'waiting_approval')");
    $stmt->execute();
    $cancelledTasks = $stmt->rowCount();

    // Deactivate all sessions
    $stmt = $pdo->prepare("UPDATE agentos_agent_sessions SET status='killed' WHERE status='active'");
    $stmt->execute();
    $killedSessions = $stmt->rowCount();

    // Deny all pending approvals
    $stmt = $pdo->prepare("UPDATE agentos_approvals SET status='denied', 
        decided_by='kill_switch', decided_at=NOW(), decision_reason='Emergency kill switch'
        WHERE status='pending'");
    $stmt->execute();

    agentos_audit([
        'agent_id' => 'system', 'user_id' => $auth['user_id'],
        'action_type' => 'kill_switch', 'status' => 'completed',
        'risk_level' => 'critical',
        'output' => ['cancelled_tasks' => $cancelledTasks, 'killed_sessions' => $killedSessions],
        'reason' => 'Emergency kill switch activated',
    ]);

    // Push notification
    agentos_push('agentos:system', 'kill_switch', [
        'activated_by' => $auth['username'] ?? 'system',
        'cancelled_tasks' => $cancelledTasks,
    ]);

    agentos_respond([
        'ok' => true,
        'kill_switch' => 'activated',
        'cancelled_tasks' => $cancelledTasks,
        'killed_sessions' => $killedSessions,
    ]);
}

// ═══════════════════════════════════════════════════════════════
// KILL TASK — Stop a specific running task
// ═══════════════════════════════════════════════════════════════
function handleKillTask(array $auth): void {
    if (!$auth['is_internal'] && !$auth['user_id']) {
        agentos_error('Authentication required', 403);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $taskId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['task_id'] ?? '');
    if (!$taskId) agentos_error('task_id required');

    $pdo = agentos_pdo();

    // Get task info first
    $stmt = $pdo->prepare("SELECT task_id, goal, agent_id, status FROM agentos_tasks WHERE task_id=?");
    $stmt->execute([$taskId]);
    $task = $stmt->fetch();
    if (!$task) agentos_error('Task not found', 404);

    if (in_array($task['status'], ['completed', 'cancelled'])) {
        agentos_error('Task already ' . $task['status']);
    }

    // Cancel the task
    $reason = mb_substr($input['reason'] ?? 'Killed by user', 0, 2000);
    $stmt = $pdo->prepare("UPDATE agentos_tasks SET status='cancelled', 
        result=?, completed_at=NOW()
        WHERE task_id=? AND status NOT IN ('completed','cancelled')");
    $stmt->execute([json_encode(['reason' => $reason, 'killed' => true]), $taskId]);

    // Deny any pending approvals for this task
    $pdo->prepare("UPDATE agentos_approvals SET status='denied', 
        decided_by='kill_task', decided_at=NOW(), decision_reason=?
        WHERE task_id=? AND status='pending'")->execute([$reason, $taskId]);

    agentos_audit([
        'agent_id' => $task['agent_id'], 'user_id' => $auth['user_id'],
        'action_type' => 'kill_task', 'task_id' => $taskId,
        'status' => 'completed', 'risk_level' => 'high',
        'reason' => $reason,
    ]);

    agentos_push('agentos:system', 'task_killed', [
        'task_id' => $taskId, 'goal' => $task['goal'],
        'killed_by' => $auth['username'] ?? 'system',
    ]);

    agentos_respond(['ok' => true, 'task_id' => $taskId, 'status' => 'cancelled']);
}

// ═══════════════════════════════════════════════════════════════
// ROLLBACK — Undo a task's completed actions where possible
// ═══════════════════════════════════════════════════════════════
function handleRollback(array $auth): void {
    if (!$auth['is_internal'] && !$auth['user_id']) {
        agentos_error('Authentication required', 403);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $taskId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['task_id'] ?? '');
    if (!$taskId) agentos_error('task_id required');

    $pdo = agentos_pdo();

    // Get task
    $stmt = $pdo->prepare("SELECT * FROM agentos_tasks WHERE task_id=?");
    $stmt->execute([$taskId]);
    $task = $stmt->fetch();
    if (!$task) agentos_error('Task not found', 404);

    // Get completed action nodes in reverse order
    $stmt = $pdo->prepare("SELECT node_id, reference_id, input_data, output_data, status
        FROM agentos_task_nodes WHERE task_id=? AND status='completed'
        ORDER BY id DESC");
    $stmt->execute([$taskId]);
    $nodes = $stmt->fetchAll();

    if (empty($nodes)) {
        agentos_respond(['ok' => true, 'task_id' => $taskId, 'rolled_back' => 0, 'message' => 'No completed actions to rollback']);
        return;
    }

    // Determine rollback capabilities
    $rollbackMap = [
        'memory.store' => 'memory.forget',
        'world.modify' => 'world.modify',
        'world.spawn' => 'world.despawn',
        'mcp.kg_create_entities' => 'mcp.kg_delete_entities',
    ];

    $rolledBack = 0;
    $rollbackLog = [];

    foreach ($nodes as $node) {
        $capId = $node['reference_id'];
        $inputData = json_decode($node['input_data'] ?? '{}', true);
        $outputData = json_decode($node['output_data'] ?? '{}', true);
        $reversible = isset($rollbackMap[$capId]);

        $entry = [
            'node_id' => $node['node_id'],
            'capability' => $capId,
            'reversible' => $reversible,
            'status' => 'skipped',
        ];

        if ($reversible) {
            // Mark node as rolled back
            $pdo->prepare("UPDATE agentos_task_nodes SET status='rolled_back' WHERE node_id=? AND task_id=?")
                ->execute([$node['node_id'], $taskId]);
            $entry['status'] = 'rolled_back';
            $rolledBack++;
        }

        $rollbackLog[] = $entry;
    }

    // Update task status
    $pdo->prepare("UPDATE agentos_tasks SET status='rolled_back', 
        result=? WHERE task_id=?")
        ->execute([json_encode(['rollback' => $rollbackLog, 'rolled_back_count' => $rolledBack]), $taskId]);

    agentos_audit([
        'agent_id' => $task['agent_id'], 'user_id' => $auth['user_id'],
        'action_type' => 'rollback', 'task_id' => $taskId,
        'status' => 'completed', 'risk_level' => 'high',
        'output' => ['rolled_back' => $rolledBack, 'total_nodes' => count($nodes)],
        'reason' => $input['reason'] ?? 'User-initiated rollback',
    ]);

    agentos_push('agentos:system', 'task_rolled_back', [
        'task_id' => $taskId, 'rolled_back' => $rolledBack,
    ]);

    agentos_respond([
        'ok' => true,
        'task_id' => $taskId,
        'rolled_back' => $rolledBack,
        'total_nodes' => count($nodes),
        'details' => $rollbackLog,
    ]);
}

// ═══════════════════════════════════════════════════════════════
// EXPIRE APPROVALS — Clean up stale approval requests
// ═══════════════════════════════════════════════════════════════
function handleExpireApprovals(array $auth): void {
    $pdo = agentos_pdo();

    // Expire approvals past their deadline
    $stmt = $pdo->prepare("UPDATE agentos_approvals SET status='expired', 
        decided_by='system', decided_at=NOW(), decision_reason='Expired'
        WHERE status='pending' AND expires_at < NOW()");
    $stmt->execute();
    $expired = $stmt->rowCount();

    // Cancel tasks waiting on expired approvals
    $pdo->exec("UPDATE agentos_tasks t 
        JOIN agentos_approvals a ON a.task_id = t.task_id
        SET t.status='cancelled', t.completed_at=NOW(),
            t.result=JSON_OBJECT('reason', 'Approval expired')
        WHERE t.status='waiting_approval' AND a.status='expired'");

    agentos_respond(['ok' => true, 'expired' => $expired]);
}

// ═══════════════════════════════════════════════════════════════
// SEED — Default safety policies
// ═══════════════════════════════════════════════════════════════
function handleSeed(array $auth): void {
    $pdo = agentos_pdo();
    $seeded = 0;

    $policies = [
        [
            'id' => 'safety-critical-approval',
            'name' => 'Critical Actions Require Approval',
            'desc' => 'All critical-risk capabilities must be approved by a human',
            'scope' => 'global', 'target' => '', 'priority' => 100,
            'rules' => [
                ['condition' => 'risk_level=critical', 'action' => 'require_approval', 'desc' => 'Critical risk requires human approval'],
            ],
        ],
        [
            'id' => 'safety-high-simulate',
            'name' => 'High-Risk Actions Require Simulation',
            'desc' => 'High-risk capabilities must be simulated before execution',
            'scope' => 'global', 'target' => '', 'priority' => 90,
            'rules' => [
                ['condition' => 'risk_level=high', 'action' => 'require_simulation', 'desc' => 'High risk requires simulation first'],
            ],
        ],
        [
            'id' => 'safety-no-anon-write',
            'name' => 'No Anonymous Writes',
            'desc' => 'Unauthenticated users cannot execute write operations',
            'scope' => 'global', 'target' => '', 'priority' => 95,
            'rules' => [
                ['condition' => 'unauthenticated AND capability_type=action', 'action' => 'deny', 'desc' => 'Anonymous users cannot execute actions'],
            ],
        ],
        [
            'id' => 'safety-payment-approval',
            'name' => 'Payment Approval',
            'desc' => 'All payment processing requires human approval',
            'scope' => 'capability', 'target' => 'commerce.payment', 'priority' => 100,
            'rules' => [
                ['condition' => 'always', 'action' => 'require_approval', 'desc' => 'Payment always requires approval'],
            ],
        ],
        [
            'id' => 'safety-deploy-approval',
            'name' => 'Deployment Approval',
            'desc' => 'Code deployment requires approval and simulation',
            'scope' => 'capability', 'target' => 'code.deploy', 'priority' => 100,
            'rules' => [
                ['condition' => 'always', 'action' => 'require_simulation', 'desc' => 'Deployment must be simulated'],
                ['condition' => 'always', 'action' => 'require_approval', 'desc' => 'Deployment requires human approval'],
            ],
        ],
        [
            'id' => 'safety-robot-approval',
            'name' => 'Robot Safety',
            'desc' => 'Robot physical commands require simulation and approval',
            'scope' => 'capability', 'target' => 'robot.command', 'priority' => 100,
            'rules' => [
                ['condition' => 'always', 'action' => 'require_simulation', 'desc' => 'Robot commands must be simulated'],
                ['condition' => 'always', 'action' => 'require_approval', 'desc' => 'Robot commands require human approval'],
            ],
        ],
        [
            'id' => 'safety-audit-all',
            'name' => 'Audit Everything',
            'desc' => 'Log all medium+ risk actions',
            'scope' => 'global', 'target' => '', 'priority' => 10,
            'rules' => [
                ['condition' => 'risk_score>=2', 'action' => 'log', 'desc' => 'Log all medium+ risk actions'],
            ],
        ],
    ];

    foreach ($policies as $pol) {
        try {
            $stmt = $pdo->prepare("INSERT IGNORE INTO agentos_policies 
                (policy_id, display_name, description, scope, scope_target, priority, enabled)
                VALUES (?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([$pol['id'], $pol['name'], $pol['desc'], $pol['scope'], $pol['target'], $pol['priority']]);
            if ($stmt->rowCount() > 0) {
                addRulesToPolicy($pdo, $pol['id'], $pol['rules']);
                $seeded++;
            }
        } catch (\Throwable $e) {
            // Skip duplicates
        }
    }

    agentos_respond(['ok' => true, 'seeded' => $seeded, 'total' => count($policies)]);
}

// ── Helper Functions ───────────────────────────────────────────

function addRulesToPolicy(PDO $pdo, string $policyId, array $rules): void {
    $stmt = $pdo->prepare("SELECT COALESCE(MAX(rule_order), 0) FROM agentos_policy_rules WHERE policy_id=?");
    $stmt->execute([$policyId]);
    $maxOrder = (int)$stmt->fetchColumn();

    $insert = $pdo->prepare("INSERT INTO agentos_policy_rules 
        (policy_id, rule_order, condition_expr, action, action_params)
        VALUES (?, ?, ?, ?, ?)");

    foreach ($rules as $i => $rule) {
        $validActions = ['allow', 'deny', 'require_approval', 'require_simulation', 'log', 'rate_limit', 'alert', 'escalate'];
        $ruleAction = in_array($rule['action'] ?? '', $validActions) ? $rule['action'] : 'log';

        $insert->execute([
            $policyId,
            $maxOrder + $i + 1,
            $rule['condition'] ?? $rule['condition_expr'] ?? 'always',
            $ruleAction,
            json_encode($rule['action_params'] ?? null),
        ]);
    }
}

function evaluateCondition(string $condition, array $ctx): bool {
    if ($condition === 'always') return true;
    if ($condition === 'never') return false;

    // Parse compound conditions with AND
    $parts = array_map('trim', explode(' AND ', $condition));
    foreach ($parts as $part) {
        if (!evaluateSingleCondition($part, $ctx)) return false;
    }
    return true;
}

function evaluateSingleCondition(string $cond, array $ctx): bool {
    if ($cond === 'unauthenticated') return empty($ctx['user_id']) && empty($ctx['is_internal']);
    if ($cond === 'authenticated') return !empty($ctx['user_id']) || !empty($ctx['is_internal']);

    // risk_level=X
    if (preg_match('/^risk_level=(\w+)$/', $cond, $m)) {
        return ($ctx['risk_level'] ?? '') === $m[1];
    }
    // risk_score>=N
    if (preg_match('/^risk_score>=(\d+)$/', $cond, $m)) {
        return ($ctx['risk_score'] ?? 0) >= (int)$m[1];
    }
    // capability_type=X
    if (preg_match('/^capability_type=(\w+)$/', $cond, $m)) {
        return ($ctx['capability_type'] ?? '') === $m[1];
    }
    // agent_id=X
    if (preg_match('/^agent_id=(\w+)$/', $cond, $m)) {
        return ($ctx['agent_id'] ?? '') === $m[1];
    }
    // category=X (match capability category)
    if (preg_match('/^category=(\w+)$/', $cond, $m)) {
        return ($ctx['category'] ?? '') === $m[1];
    }
    // capability_id=X
    if (preg_match('/^capability_id=([\w.]+)$/', $cond, $m)) {
        return ($ctx['capability_id'] ?? '') === $m[1];
    }

    return false;
}
