<?php
/**
 * Alfred Goal System API — Phase 1: Autonomy Foundation
 * ──────────────────────────────────────────────────────
 * Persistent goals that survive across conversations.
 * Goal hierarchy: Life → Strategic → Operational → Reactive
 *
 * Endpoints:
 *   GET  ?action=list            → List goals (filter by type/status)
 *   GET  ?action=get&goal_id=X   → Get single goal + sub-goals
 *   POST ?action=create          → Create a new goal
 *   POST ?action=update          → Update goal progress/status
 *   POST ?action=decompose       → AI-decompose a goal into sub-goals
 *   GET  ?action=active          → All active goals with progress
 *   GET  ?action=dashboard       → Goal dashboard (stats + timeline)
 *   POST ?action=evaluate        → Run goal evaluation (decision loop trigger)
 *   GET  ?action=decisions       → Decision log (what Alfred decided and why)
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

// ─── DB Schema ─────────────────────────────────────────────────────
function ensureGoalSchema() {
    $db = getDB();
    if (!$db) return false;

    $db->exec("CREATE TABLE IF NOT EXISTS alfred_goals (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        goal_id         VARCHAR(50) UNIQUE NOT NULL,
        goal_type       ENUM('life','strategic','operational','reactive') NOT NULL,
        description     TEXT NOT NULL,
        success_criteria JSON NOT NULL,
        assigned_agents JSON NOT NULL,
        progress        DECIMAL(5,2) DEFAULT 0.00,
        status          ENUM('active','paused','completed','abandoned') DEFAULT 'active',
        parent_goal_id  VARCHAR(50) DEFAULT NULL,
        priority        TINYINT DEFAULT 5,
        deadline        TIMESTAMP NULL,
        metadata        JSON DEFAULT NULL,
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_type (goal_type),
        INDEX idx_status (status),
        INDEX idx_parent (parent_goal_id),
        INDEX idx_priority (priority)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS alfred_decisions (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        decision_id     VARCHAR(50) UNIQUE NOT NULL,
        trigger_type    ENUM('scheduled','event','reactive','manual') NOT NULL,
        perception      JSON NOT NULL COMMENT 'What Alfred observed',
        reasoning       JSON NOT NULL COMMENT 'Why Alfred chose this action',
        action_taken    TEXT NOT NULL,
        agent_delegated VARCHAR(50) DEFAULT NULL,
        goal_id         VARCHAR(50) DEFAULT NULL,
        outcome         ENUM('pending','success','partial','failure') DEFAULT 'pending',
        outcome_details TEXT DEFAULT NULL,
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_trigger (trigger_type),
        INDEX idx_outcome (outcome),
        INDEX idx_agent (agent_delegated)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    return true;
}

function generateGoalId() {
    return 'goal_' . bin2hex(random_bytes(8));
}

function generateDecisionId() {
    return 'dec_' . bin2hex(random_bytes(8));
}

// ─── Seed Life Goals ───────────────────────────────────────────────
function seedLifeGoals($db) {
    $lifeGoals = [
        ['Serve users with excellence', '["User satisfaction > 90%","Response time < 2s","Zero critical bugs in production"]', '["alfred","pulse","nova"]'],
        ['Grow the GoSiteMe ecosystem', '["Reach $100K MRR","1000+ developer API users","50+ marketplace tools"]', '["alfred","herald","atlas"]'],
        ['Maintain and improve all systems', '["99.9% uptime","All security scans green","Zero data loss"]', '["alfred","architect","cipher"]'],
        ['Learn and evolve continuously', '["Read 100+ feed items daily","Create 1+ new tool weekly","Improve success rate to 95%+"]', '["alfred","sage","nova"]'],
    ];

    $stmt = $db->prepare("INSERT IGNORE INTO alfred_goals (goal_id, goal_type, description, success_criteria, assigned_agents, priority) VALUES (?, 'life', ?, ?, ?, 10)");

    $count = 0;
    foreach ($lifeGoals as $i => $g) {
        $goalId = 'life_' . ($i + 1);
        $stmt->execute([$goalId, $g[0], $g[1], $g[2]]);
        if ($stmt->rowCount() > 0) $count++;
    }
    return $count;
}

// ─── Router ────────────────────────────────────────────────────────
$action = sanitize($_GET['action'] ?? '', 30);
$db = getDB();

if (!$db) jsonResponse(['error' => 'Database unavailable'], 503);

ensureGoalSchema();

switch ($action) {

    // ── List Goals ──────────────────────────────────────────────────
    case 'list':
        $sql = "SELECT goal_id, goal_type, description, progress, status, priority, parent_goal_id, deadline, created_at, updated_at FROM alfred_goals WHERE 1=1";
        $params = [];

        if (!empty($_GET['type'])) {
            $sql .= " AND goal_type = ?";
            $params[] = sanitize($_GET['type'], 20);
        }
        if (!empty($_GET['status'])) {
            $sql .= " AND status = ?";
            $params[] = sanitize($_GET['status'], 20);
        }
        if (!empty($_GET['parent'])) {
            $sql .= " AND parent_goal_id = ?";
            $params[] = sanitize($_GET['parent'], 50);
        }

        $sql .= " ORDER BY priority DESC, created_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        jsonResponse(['success' => true, 'goals' => $stmt->fetchAll()]);
        break;

    // ── Get Goal ────────────────────────────────────────────────────
    case 'get':
        $goalId = sanitize($_GET['goal_id'] ?? '', 50);
        if (!$goalId) jsonResponse(['error' => 'goal_id required'], 400);

        $stmt = $db->prepare("SELECT * FROM alfred_goals WHERE goal_id = ?");
        $stmt->execute([$goalId]);
        $goal = $stmt->fetch();
        if (!$goal) jsonResponse(['error' => 'Goal not found'], 404);

        $goal['success_criteria'] = json_decode($goal['success_criteria'], true);
        $goal['assigned_agents'] = json_decode($goal['assigned_agents'], true);
        $goal['metadata'] = json_decode($goal['metadata'], true);

        // Sub-goals
        $stmtS = $db->prepare("SELECT goal_id, goal_type, description, progress, status, priority FROM alfred_goals WHERE parent_goal_id = ? ORDER BY priority DESC");
        $stmtS->execute([$goalId]);
        $goal['sub_goals'] = $stmtS->fetchAll();

        // Related decisions
        $stmtD = $db->prepare("SELECT decision_id, trigger_type, action_taken, outcome, created_at FROM alfred_decisions WHERE goal_id = ? ORDER BY created_at DESC LIMIT 10");
        $stmtD->execute([$goalId]);
        $goal['recent_decisions'] = $stmtD->fetchAll();

        jsonResponse(['success' => true, 'goal' => $goal]);
        break;

    // ── Create Goal ─────────────────────────────────────────────────
    case 'create':
        if (!isInternalCall()) requireAuth();

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) jsonResponse(['error' => 'JSON body required'], 400);

        $type = sanitize($input['goal_type'] ?? 'operational', 20);
        $desc = sanitize($input['description'] ?? '', 2000);
        $priority = min(max(intval($input['priority'] ?? 5), 1), 10);

        if (!$desc) jsonResponse(['error' => 'description required'], 400);

        $validTypes = ['life', 'strategic', 'operational', 'reactive'];
        if (!in_array($type, $validTypes)) $type = 'operational';

        // Only admin/internal can create life/strategic goals
        if (in_array($type, ['life', 'strategic']) && !isInternalCall() && !isAdmin()) {
            jsonResponse(['error' => 'Admin access required for life/strategic goals'], 403);
        }

        $goalId = generateGoalId();
        $stmt = $db->prepare("INSERT INTO alfred_goals (goal_id, goal_type, description, success_criteria, assigned_agents, priority, parent_goal_id, deadline, metadata) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $goalId, $type, $desc,
            json_encode($input['success_criteria'] ?? []),
            json_encode($input['assigned_agents'] ?? ['alfred']),
            $priority,
            sanitize($input['parent_goal_id'] ?? '', 50) ?: null,
            !empty($input['deadline']) ? date('Y-m-d H:i:s', strtotime($input['deadline'])) : null,
            json_encode($input['metadata'] ?? null),
        ]);

        jsonResponse(['success' => true, 'goal_id' => $goalId, 'type' => $type]);
        break;

    // ── Update Goal ─────────────────────────────────────────────────
    case 'update':
        if (!isInternalCall()) requireAuth();

        $input = json_decode(file_get_contents('php://input'), true);
        $goalId = sanitize($input['goal_id'] ?? '', 50);
        if (!$goalId) jsonResponse(['error' => 'goal_id required'], 400);

        $updates = [];
        $params = [];

        if (isset($input['progress'])) {
            $updates[] = "progress = ?";
            $params[] = min(max(floatval($input['progress']), 0), 100);
        }
        if (isset($input['status'])) {
            $valid = ['active', 'paused', 'completed', 'abandoned'];
            $s = sanitize($input['status'], 20);
            if (in_array($s, $valid)) {
                $updates[] = "status = ?";
                $params[] = $s;
            }
        }
        if (isset($input['priority'])) {
            $updates[] = "priority = ?";
            $params[] = min(max(intval($input['priority']), 1), 10);
        }
        if (isset($input['metadata'])) {
            $updates[] = "metadata = ?";
            $params[] = json_encode($input['metadata']);
        }

        if (empty($updates)) jsonResponse(['error' => 'Nothing to update'], 400);

        $params[] = $goalId;
        $db->prepare("UPDATE alfred_goals SET " . implode(', ', $updates) . " WHERE goal_id = ?")->execute($params);

        // If completed, check if parent goal should update
        if (($input['status'] ?? '') === 'completed' || isset($input['progress'])) {
            $stmt = $db->prepare("SELECT parent_goal_id FROM alfred_goals WHERE goal_id = ?");
            $stmt->execute([$goalId]);
            $row = $stmt->fetch();
            if ($row && $row['parent_goal_id']) {
                // Calculate average progress of sibling goals
                $stmtAvg = $db->prepare("SELECT AVG(progress) as avg_progress FROM alfred_goals WHERE parent_goal_id = ?");
                $stmtAvg->execute([$row['parent_goal_id']]);
                $avg = $stmtAvg->fetch();
                if ($avg) {
                    $db->prepare("UPDATE alfred_goals SET progress = ? WHERE goal_id = ?")->execute([
                        round($avg['avg_progress'], 2), $row['parent_goal_id']
                    ]);
                }
            }
        }

        jsonResponse(['success' => true, 'goal_id' => $goalId]);
        break;

    // ── Active Goals Dashboard ──────────────────────────────────────
    case 'active':
        $stmt = $db->query("SELECT goal_id, goal_type, description, progress, priority, assigned_agents, deadline, updated_at FROM alfred_goals WHERE status = 'active' ORDER BY goal_type ASC, priority DESC");
        $goals = $stmt->fetchAll();

        foreach ($goals as &$g) {
            $g['assigned_agents'] = json_decode($g['assigned_agents'], true);
        }

        // Group by type
        $grouped = ['life' => [], 'strategic' => [], 'operational' => [], 'reactive' => []];
        foreach ($goals as $g) {
            $grouped[$g['goal_type']][] = $g;
        }

        jsonResponse(['success' => true, 'goals' => $grouped, 'total_active' => count($goals)]);
        break;

    // ── Goal Dashboard ──────────────────────────────────────────────
    case 'dashboard':
        $byType = $db->query("SELECT goal_type, status, COUNT(*) as cnt, AVG(progress) as avg_progress FROM alfred_goals GROUP BY goal_type, status")->fetchAll();
        $totalGoals = $db->query("SELECT COUNT(*) FROM alfred_goals")->fetchColumn();
        $completedGoals = $db->query("SELECT COUNT(*) FROM alfred_goals WHERE status = 'completed'")->fetchColumn();
        $activeGoals = $db->query("SELECT COUNT(*) FROM alfred_goals WHERE status = 'active'")->fetchColumn();

        // Overdue goals
        $overdueGoals = $db->query("SELECT goal_id, description, deadline FROM alfred_goals WHERE status = 'active' AND deadline IS NOT NULL AND deadline < NOW()")->fetchAll();

        // Recent decisions
        $recentDecisions = $db->query("SELECT decision_id, trigger_type, action_taken, outcome, created_at FROM alfred_decisions ORDER BY created_at DESC LIMIT 10")->fetchAll();

        jsonResponse([
            'success' => true,
            'total_goals' => (int) $totalGoals,
            'active_goals' => (int) $activeGoals,
            'completed_goals' => (int) $completedGoals,
            'completion_rate' => $totalGoals > 0 ? round(($completedGoals / $totalGoals) * 100, 1) : 0,
            'by_type_status' => $byType,
            'overdue' => $overdueGoals,
            'recent_decisions' => $recentDecisions,
        ]);
        break;

    // ── Run Goal Evaluation (Decision Loop) ─────────────────────────
    case 'evaluate':
        if (!isInternalCall() && !isAdmin()) {
            requireAuth();
            if (!isAdmin()) jsonResponse(['error' => 'Admin or internal access required'], 403);
        }

        $decisions = [];

        // 1. PERCEIVE — Gather system state
        $perception = [
            'timestamp' => date('c'),
            'active_goals' => (int) $db->query("SELECT COUNT(*) FROM alfred_goals WHERE status = 'active'")->fetchColumn(),
            'queued_tasks' => (int) $db->query("SELECT COUNT(*) FROM alfred_agent_tasks WHERE status = 'queued'")->fetchColumn(),
            'running_tasks' => (int) $db->query("SELECT COUNT(*) FROM alfred_agent_tasks WHERE status = 'running'")->fetchColumn(),
            'failed_tasks_24h' => (int) $db->query("SELECT COUNT(*) FROM alfred_agent_tasks WHERE status = 'failed' AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn(),
            'idle_agents' => (int) $db->query("SELECT COUNT(*) FROM alfred_agent_registry WHERE status = 'idle'")->fetchColumn(),
            'busy_agents' => (int) $db->query("SELECT COUNT(*) FROM alfred_agent_registry WHERE status = 'busy'")->fetchColumn(),
        ];

        // Check for overdue goals
        $overdueStmt = $db->query("SELECT goal_id, description, deadline, assigned_agents FROM alfred_goals WHERE status = 'active' AND deadline IS NOT NULL AND deadline < NOW()");
        $overdue = $overdueStmt->fetchAll();
        $perception['overdue_goals'] = count($overdue);

        // 2. REASON — Identify highest-impact actions
        $actions = [];

        // Overdue goals need attention
        foreach ($overdue as $og) {
            $actions[] = [
                'action' => 'escalate_overdue_goal',
                'goal_id' => $og['goal_id'],
                'description' => "Goal overdue: " . $og['description'],
                'priority' => 9,
                'reasoning' => "Deadline passed, needs immediate attention or deadline extension",
            ];
        }

        // Failed tasks need retry or investigation
        if ($perception['failed_tasks_24h'] > 3) {
            $actions[] = [
                'action' => 'investigate_failures',
                'description' => $perception['failed_tasks_24h'] . " tasks failed in last 24h",
                'priority' => 8,
                'reasoning' => "High failure rate indicates systemic issue — needs Debugger investigation",
            ];
        }

        // Queued tasks with idle agents — can delegate
        if ($perception['queued_tasks'] > 0 && $perception['idle_agents'] > 0) {
            $actions[] = [
                'action' => 'process_queue',
                'description' => $perception['queued_tasks'] . " tasks queued, " . $perception['idle_agents'] . " agents idle",
                'priority' => 7,
                'reasoning' => "Available compute capacity exists — should process backlog",
            ];
        }

        // 3. DECIDE + ACT — Log decisions
        foreach ($actions as $actionItem) {
            $decisionId = generateDecisionId();
            $db->prepare("INSERT INTO alfred_decisions (decision_id, trigger_type, perception, reasoning, action_taken, goal_id, agent_delegated) VALUES (?, 'scheduled', ?, ?, ?, ?, ?)")->execute([
                $decisionId,
                json_encode($perception),
                json_encode(['priority' => $actionItem['priority'], 'rationale' => $actionItem['reasoning']]),
                $actionItem['description'],
                $actionItem['goal_id'] ?? null,
                $actionItem['agent_delegated'] ?? null,
            ]);
            $decisions[] = ['decision_id' => $decisionId, 'action' => $actionItem['action'], 'description' => $actionItem['description'], 'priority' => $actionItem['priority']];
        }

        // 4. REFLECT
        jsonResponse([
            'success' => true,
            'perception' => $perception,
            'decisions_made' => count($decisions),
            'decisions' => $decisions,
            'next_evaluation' => date('c', time() + 60),
        ]);
        break;

    // ── Decision Log ────────────────────────────────────────────────
    case 'decisions':
        $limit = min(intval($_GET['limit'] ?? 25), 100);
        $stmt = $db->prepare("SELECT * FROM alfred_decisions ORDER BY created_at DESC LIMIT ?");
        dbExecute($stmt, [$limit]);
        $decs = $stmt->fetchAll();

        foreach ($decs as &$d) {
            $d['perception'] = json_decode($d['perception'], true);
            $d['reasoning'] = json_decode($d['reasoning'], true);
        }

        jsonResponse(['success' => true, 'decisions' => $decs]);
        break;

    // ── Seed Life Goals ─────────────────────────────────────────────
    case 'seed':
        if (!isInternalCall() && !isAdmin()) {
            requireAuth();
            if (!isAdmin()) jsonResponse(['error' => 'Admin access required'], 403);
        }

        $count = seedLifeGoals($db);
        jsonResponse(['success' => true, 'seeded' => $count, 'message' => "Seeded {$count} life goals."]);
        break;

    default:
        jsonResponse([
            'error' => 'Unknown action',
            'available_actions' => ['list', 'get', 'create', 'update', 'active', 'dashboard', 'evaluate', 'decisions', 'seed'],
        ], 400);
}
