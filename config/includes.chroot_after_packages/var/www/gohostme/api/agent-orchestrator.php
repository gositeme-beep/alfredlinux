<?php
/**
 * Agent Orchestrator API
 * ═══════════════════════
 * Manages codebase upgrade tasks for AI agent fleets.
 * Tasks are stored in the database and processed by the PM2 runner service.
 *
 * Endpoints:
 *   GET  ?action=backlog          — List all tasks with filters
 *   GET  ?action=stats            — Dashboard statistics
 *   GET  ?action=task&id=X        — Get single task details
 *   GET  ?action=logs&id=X        — Get task execution logs
 *   POST ?action=create           — Create new task
 *   POST ?action=claim&id=X       — Claim a task (mark in-progress)
 *   POST ?action=complete&id=X    — Mark task as done
 *   POST ?action=fail&id=X        — Mark task as failed
 *   POST ?action=spawn            — Spawn agent for a task
 *   POST ?action=bulk_create      — Create multiple tasks at once
 *   POST ?action=import_backlog   — Import from UPGRADE_BACKLOG.md
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

session_start();

// ── Auth ──────────────────────────────────────────────────────────
$isLoggedIn = !empty($_SESSION['logged_in']) && !empty($_SESSION['client_id']);
$clientId   = $isLoggedIn ? (int)$_SESSION['client_id'] : 0;
$isOwner    = $clientId === 33;

// Internal API calls (from PM2 runner)
$internalSecret = getenv('ORCHESTRATOR_SECRET') ?: '';
$isInternal = false;
if (!empty($_SERVER['HTTP_X_ORCHESTRATOR_SECRET']) && $internalSecret &&
    hash_equals($internalSecret, $_SERVER['HTTP_X_ORCHESTRATOR_SECRET'])) {
    $isInternal = true;
}

if (!$isLoggedIn && !$isInternal) {
    http_response_code(401);
    echo json_encode(['error' => true, 'message' => 'Authentication required']);
    exit;
}

// ── Database ──────────────────────────────────────────────────────
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => true, 'message' => 'Database connection failed']);
    exit;
}

// ── CSRF Token Verification ───────────────────────────────────────
function verifyCsrf(): bool {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ── Ensure Table Exists (cached via flag file) ───────────────────
$flagFile = __DIR__ . '/../data/.ao-tables-created';
if (!file_exists($flagFile)) {
$pdo->exec("CREATE TABLE IF NOT EXISTS agent_orchestrator_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id VARCHAR(20) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category ENUM('security','frontend','api','javascript','test','script','docs','sdk','debt','feature') NOT NULL DEFAULT 'frontend',
    priority ENUM('P0','P1','P2','P3','P4') NOT NULL DEFAULT 'P2',
    status ENUM('pending','claimed','running','done','failed','cancelled') NOT NULL DEFAULT 'pending',
    target_file VARCHAR(500) DEFAULT NULL,
    agent_type VARCHAR(50) DEFAULT NULL,
    agent_session_id VARCHAR(100) DEFAULT NULL,
    claimed_by INT DEFAULT NULL,
    claimed_at DATETIME DEFAULT NULL,
    started_at DATETIME DEFAULT NULL,
    completed_at DATETIME DEFAULT NULL,
    result_summary TEXT DEFAULT NULL,
    error_message TEXT DEFAULT NULL,
    files_changed TEXT DEFAULT NULL,
    validation_log TEXT DEFAULT NULL,
    retry_count INT DEFAULT 0,
    max_retries INT DEFAULT 3,
    depends_on JSON DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_category (category),
    INDEX idx_task_id (task_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$pdo->exec("CREATE TABLE IF NOT EXISTS agent_orchestrator_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id VARCHAR(20) NOT NULL,
    level ENUM('info','warn','error','success') NOT NULL DEFAULT 'info',
    agent_name VARCHAR(100) DEFAULT NULL,
    message TEXT NOT NULL,
    metadata JSON DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_task (task_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
@file_put_contents($flagFile, date('c'));
}

// ── Router ────────────────────────────────────────────────────────
$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS) ?: '';
$taskIdParam = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_SPECIAL_CHARS) ?: '';

switch ($action) {

    // ── List Backlog ──────────────────────────────────────────────
    case 'backlog':
        $status   = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_SPECIAL_CHARS) ?: '';
        $category = filter_input(INPUT_GET, 'category', FILTER_SANITIZE_SPECIAL_CHARS) ?: '';
        $priority = filter_input(INPUT_GET, 'priority', FILTER_SANITIZE_SPECIAL_CHARS) ?: '';
        $search   = filter_input(INPUT_GET, 'q', FILTER_SANITIZE_SPECIAL_CHARS) ?: '';
        $page     = max(1, (int)(filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1));
        $limit    = min(100, max(10, (int)(filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 50)));
        $offset   = ($page - 1) * $limit;

        $where = [];
        $params = [];

        if ($status && in_array($status, ['pending','claimed','running','done','failed','cancelled'])) {
            $where[] = 'status = ?';
            $params[] = $status;
        }
        if ($category && in_array($category, ['security','frontend','api','javascript','test','script','docs','sdk','debt','feature'])) {
            $where[] = 'category = ?';
            $params[] = $category;
        }
        if ($priority && preg_match('/^P[0-4]$/', $priority)) {
            $where[] = 'priority = ?';
            $params[] = $priority;
        }
        if ($search) {
            $where[] = '(title LIKE ? OR description LIKE ? OR task_id LIKE ?)';
            $s = '%' . $search . '%';
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
        }

        $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM agent_orchestrator_tasks $whereSQL");
        dbExecute($countStmt, $params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT * FROM agent_orchestrator_tasks $whereSQL ORDER BY
            FIELD(priority, 'P0','P1','P2','P3','P4'),
            FIELD(status, 'running','claimed','pending','failed','done','cancelled'),
            created_at DESC
            LIMIT ? OFFSET ?");
        $params[] = $limit;
        $params[] = $offset;
        dbExecute($stmt, $params);
        $tasks = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'tasks'   => $tasks,
            'total'   => $total,
            'page'    => $page,
            'pages'   => ceil($total / $limit)
        ]);
        break;

    // ── Dashboard Stats ───────────────────────────────────────────
    case 'stats':
        $stats = [];

        // Status counts
        $rows = $pdo->query("SELECT status, COUNT(*) as c FROM agent_orchestrator_tasks GROUP BY status")->fetchAll();
        $statusCounts = array_column($rows, 'c', 'status');
        $stats['by_status'] = [
            'pending'   => (int)($statusCounts['pending'] ?? 0),
            'claimed'   => (int)($statusCounts['claimed'] ?? 0),
            'running'   => (int)($statusCounts['running'] ?? 0),
            'done'      => (int)($statusCounts['done'] ?? 0),
            'failed'    => (int)($statusCounts['failed'] ?? 0),
            'cancelled' => (int)($statusCounts['cancelled'] ?? 0),
        ];
        $stats['total'] = array_sum($stats['by_status']);

        // Category counts
        $rows = $pdo->query("SELECT category, COUNT(*) as c FROM agent_orchestrator_tasks GROUP BY category")->fetchAll();
        $stats['by_category'] = array_column($rows, 'c', 'category');

        // Priority counts
        $rows = $pdo->query("SELECT priority, COUNT(*) as c FROM agent_orchestrator_tasks GROUP BY priority")->fetchAll();
        $stats['by_priority'] = array_column($rows, 'c', 'priority');

        // Recent completions
        $rows = $pdo->query("SELECT task_id, title, completed_at FROM agent_orchestrator_tasks
            WHERE status = 'done' ORDER BY completed_at DESC LIMIT 10")->fetchAll();
        $stats['recent_completions'] = $rows;

        // Completion rate (last 24h)
        $done24h = (int)$pdo->query("SELECT COUNT(*) FROM agent_orchestrator_tasks WHERE status = 'done' AND completed_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();
        $failed24h = (int)$pdo->query("SELECT COUNT(*) FROM agent_orchestrator_tasks WHERE status = 'failed' AND updated_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();
        $total24h = $done24h + $failed24h;
        $stats['completion_rate_24h'] = $total24h > 0 ? round(($done24h / $total24h) * 100, 1) : 100;
        $stats['done_24h'] = $done24h;
        $stats['failed_24h'] = $failed24h;

        // Average completion time
        $avg = $pdo->query("SELECT AVG(TIMESTAMPDIFF(MINUTE, started_at, completed_at)) FROM agent_orchestrator_tasks WHERE status = 'done' AND started_at IS NOT NULL AND completed_at IS NOT NULL")->fetchColumn();
        $stats['avg_completion_minutes'] = $avg ? round((float)$avg, 1) : 0;

        echo json_encode(['success' => true, 'stats' => $stats]);
        break;

    // ── Get Single Task ───────────────────────────────────────────
    case 'task':
        if (!$taskIdParam) {
            http_response_code(400);
            echo json_encode(['error' => true, 'message' => 'Task ID required']);
            break;
        }
        $stmt = $pdo->prepare("SELECT * FROM agent_orchestrator_tasks WHERE task_id = ? OR id = ?");
        $stmt->execute([$taskIdParam, (int)$taskIdParam]);
        $task = $stmt->fetch();
        if (!$task) {
            http_response_code(404);
            echo json_encode(['error' => true, 'message' => 'Task not found']);
            break;
        }
        echo json_encode(['success' => true, 'task' => $task]);
        break;

    // ── Get Task Logs ─────────────────────────────────────────────
    case 'logs':
        if (!$taskIdParam) {
            http_response_code(400);
            echo json_encode(['error' => true, 'message' => 'Task ID required']);
            break;
        }
        $stmt = $pdo->prepare("SELECT * FROM agent_orchestrator_logs WHERE task_id = ? ORDER BY created_at DESC LIMIT 100");
        $stmt->execute([$taskIdParam]);
        $logs = $stmt->fetchAll();
        echo json_encode(['success' => true, 'logs' => $logs]);
        break;

    // ── Create Task ───────────────────────────────────────────────
    case 'create':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => true, 'message' => 'POST required']);
            break;
        }
        if (!$isInternal && !verifyCsrf()) {
            http_response_code(403);
            echo json_encode(['error' => true, 'message' => 'Invalid CSRF token']);
            break;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            http_response_code(400);
            echo json_encode(['error' => true, 'message' => 'Invalid JSON']);
            break;
        }

        $taskId     = trim($input['task_id'] ?? '');
        $title      = trim($input['title'] ?? '');
        $desc       = trim($input['description'] ?? '');
        $category   = $input['category'] ?? 'frontend';
        $priority   = $input['priority'] ?? 'P2';
        $targetFile = trim($input['target_file'] ?? '');
        $agentType  = trim($input['agent_type'] ?? '');
        $dependsOn  = isset($input['depends_on']) && is_array($input['depends_on']) ? $input['depends_on'] : null;

        if (!$title) {
            http_response_code(400);
            echo json_encode(['error' => true, 'message' => 'Title is required']);
            break;
        }

        // Auto-generate task_id if not provided
        if (!$taskId) {
            $prefixMap = ['security'=>'SEC','frontend'=>'FE','api'=>'API','javascript'=>'JS','test'=>'TEST','script'=>'SCR','docs'=>'DOC','sdk'=>'SDK','debt'=>'DEBT','feature'=>'NEW'];
            $prefix = $prefixMap[$category] ?? 'TASK';
            $maxStmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING(task_id, LOCATE('-', task_id)+1) AS UNSIGNED)) FROM agent_orchestrator_tasks WHERE task_id LIKE ?");
            $maxStmt->execute([$prefix . '-%']);
            $max = $maxStmt->fetchColumn();
            $taskId = $prefix . '-' . str_pad(((int)$max + 1), 3, '0', STR_PAD_LEFT);
        }

        $validCategories = ['security','frontend','api','javascript','test','script','docs','sdk','debt','feature'];
        if (!in_array($category, $validCategories)) $category = 'frontend';
        if (!preg_match('/^P[0-4]$/', $priority)) $priority = 'P2';

        try {
            $stmt = $pdo->prepare("INSERT INTO agent_orchestrator_tasks
                (task_id, title, description, category, priority, target_file, agent_type, depends_on, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$taskId, $title, $desc, $category, $priority, $targetFile ?: null, $agentType ?: null, $dependsOn ? json_encode($dependsOn) : null, $clientId]);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                http_response_code(409);
                echo json_encode(['error' => true, 'message' => 'Task ID already exists']);
                break;
            }
            throw $e;
        }

        addLog($pdo, $taskId, 'info', 'System', "Task created: $title");

        echo json_encode(['success' => true, 'task_id' => $taskId, 'id' => $pdo->lastInsertId()]);
        break;

    // ── Claim Task ────────────────────────────────────────────────
    case 'claim':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => true, 'message' => 'POST required']);
            break;
        }
        if (!$isInternal && !verifyCsrf()) {
            http_response_code(403);
            echo json_encode(['error' => true, 'message' => 'Invalid CSRF token']);
            break;
        }
        if (!$taskIdParam) {
            http_response_code(400);
            echo json_encode(['error' => true, 'message' => 'Task ID required']);
            break;
        }

        // Atomic claim — only claim if still pending
        $stmt = $pdo->prepare("UPDATE agent_orchestrator_tasks SET
            status = 'claimed', claimed_by = ?, claimed_at = NOW()
            WHERE (task_id = ? OR id = ?) AND status = 'pending'");
        $stmt->execute([$clientId, $taskIdParam, (int)$taskIdParam]);

        if ($stmt->rowCount() === 0) {
            http_response_code(409);
            echo json_encode(['error' => true, 'message' => 'Task already claimed or not found']);
            break;
        }

        addLog($pdo, $taskIdParam, 'info', 'System', "Task claimed by user #$clientId");
        echo json_encode(['success' => true, 'message' => 'Task claimed']);
        break;

    // ── Retry Task (reset failed → pending) ──────────────────────
    case 'retry':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => true, 'message' => 'POST required']);
            break;
        }
        if (!$isInternal && !verifyCsrf()) {
            http_response_code(403);
            echo json_encode(['error' => true, 'message' => 'Invalid CSRF token']);
            break;
        }
        if (!$isOwner && !$isInternal) {
            http_response_code(403);
            echo json_encode(['error' => true, 'message' => 'Owner access required']);
            break;
        }
        if (!$taskIdParam) {
            http_response_code(400);
            echo json_encode(['error' => true, 'message' => 'Task ID required']);
            break;
        }
        // Check retry limit
        $checkStmt = $pdo->prepare("SELECT retry_count, max_retries FROM agent_orchestrator_tasks WHERE (task_id = ? OR id = ?) AND status = 'failed'");
        $checkStmt->execute([$taskIdParam, (int)$taskIdParam]);
        $retryInfo = $checkStmt->fetch();
        if (!$retryInfo) {
            http_response_code(409);
            echo json_encode(['error' => true, 'message' => 'Task not in failed state']);
            break;
        }
        if ((int)$retryInfo['retry_count'] >= (int)$retryInfo['max_retries']) {
            http_response_code(409);
            echo json_encode(['error' => true, 'message' => 'Max retries (' . $retryInfo['max_retries'] . ') exceeded']);
            break;
        }
        $stmt = $pdo->prepare("UPDATE agent_orchestrator_tasks SET
            status = 'pending', error_message = NULL, agent_session_id = NULL
            WHERE (task_id = ? OR id = ?) AND status = 'failed'");
        $stmt->execute([$taskIdParam, (int)$taskIdParam]);
        addLog($pdo, $taskIdParam, 'info', 'System', 'Task retried — reset to pending');
        echo json_encode(['success' => true, 'message' => 'Task reset to pending']);
        break;

    // ── Spawn Agent ───────────────────────────────────────────────
    case 'spawn':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => true, 'message' => 'POST required']);
            break;
        }
        if (!$isInternal && !verifyCsrf()) {
            http_response_code(403);
            echo json_encode(['error' => true, 'message' => 'Invalid CSRF token']);
            break;
        }
        if (!$isOwner && !$isInternal) {
            http_response_code(403);
            echo json_encode(['error' => true, 'message' => 'Only platform owner can spawn agents']);
            break;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $spawnTaskId = trim($input['task_id'] ?? $taskIdParam);
        if (!$spawnTaskId) {
            http_response_code(400);
            echo json_encode(['error' => true, 'message' => 'Task ID required']);
            break;
        }

        // Get the task
        $stmt = $pdo->prepare("SELECT * FROM agent_orchestrator_tasks WHERE task_id = ? OR id = ?");
        $stmt->execute([$spawnTaskId, (int)$spawnTaskId]);
        $task = $stmt->fetch();
        if (!$task) {
            http_response_code(404);
            echo json_encode(['error' => true, 'message' => 'Task not found']);
            break;
        }

        // Only spawn if task is in a spawnable state
        if (!in_array($task['status'], ['pending', 'failed'])) {
            http_response_code(409);
            echo json_encode(['error' => true, 'message' => 'Task is already ' . $task['status']]);
            break;
        }

        // Check task dependencies — all must be 'done' before spawning
        if (!empty($task['depends_on'])) {
            $deps = json_decode($task['depends_on'], true);
            if (is_array($deps) && count($deps) > 0) {
                $placeholders = implode(',', array_fill(0, count($deps), '?'));
                $depStmt = $pdo->prepare("SELECT task_id, status FROM agent_orchestrator_tasks WHERE task_id IN ($placeholders)");
                $depStmt->execute($deps);
                $depStatuses = $depStmt->fetchAll();
                $depMap = array_column($depStatuses, 'status', 'task_id');
                $blocking = [];
                foreach ($deps as $depId) {
                    if (($depMap[$depId] ?? 'pending') !== 'done') {
                        $blocking[] = $depId . ' (' . ($depMap[$depId] ?? 'not found') . ')';
                    }
                }
                if ($blocking) {
                    http_response_code(409);
                    echo json_encode(['error' => true, 'message' => 'Blocked by dependencies: ' . implode(', ', $blocking)]);
                    break;
                }
            }
        }

        // Mark as running
        $sessionId = 'agent-' . $task['task_id'] . '-' . time();
        $pdo->prepare("UPDATE agent_orchestrator_tasks SET
            status = 'running', started_at = NOW(), agent_session_id = ?, error_message = NULL
            WHERE id = ?")->execute([$sessionId, $task['id']]);

        // Write task to the spawn queue file (PM2 runner picks this up)
        $queueDir = dirname(__DIR__) . '/data/agent-queue';
        if (!is_dir($queueDir)) mkdir($queueDir, 0755, true);

        $queueFile = $queueDir . '/' . $task['task_id'] . '.json';
        $queueData = [
            'task_id'     => $task['task_id'],
            'title'       => $task['title'],
            'description' => $task['description'],
            'category'    => $task['category'],
            'priority'    => $task['priority'],
            'target_file' => $task['target_file'],
            'agent_type'  => $task['agent_type'],
            'session_id'  => $sessionId,
            'spawned_at'  => date('c'),
            'status'      => 'queued'
        ];
        file_put_contents($queueFile, json_encode($queueData, JSON_PRETTY_PRINT));

        addLog($pdo, $task['task_id'], 'info', 'Orchestrator', "Agent spawned: session $sessionId");

        echo json_encode([
            'success'    => true,
            'session_id' => $sessionId,
            'task_id'    => $task['task_id'],
            'message'    => 'Agent spawned and queued for execution'
        ]);
        break;

    // ── Complete Task ─────────────────────────────────────────────
    case 'complete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => true, 'message' => 'POST required']);
            break;
        }
        if (!$isInternal && !verifyCsrf()) {
            http_response_code(403);
            echo json_encode(['error' => true, 'message' => 'Invalid CSRF token']);
            break;
        }
        if (!$taskIdParam) {
            http_response_code(400);
            echo json_encode(['error' => true, 'message' => 'Task ID required']);
            break;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $summary = trim($input['summary'] ?? '');
        $filesChanged = trim($input['files_changed'] ?? '');
        $validationLog = trim($input['validation_log'] ?? '');

        $stmt = $pdo->prepare("UPDATE agent_orchestrator_tasks SET
            status = 'done', completed_at = NOW(), result_summary = ?, files_changed = ?, validation_log = ?
            WHERE (task_id = ? OR id = ?) AND status IN ('running','claimed')");
        $stmt->execute([$summary ?: null, $filesChanged ?: null, $validationLog ?: null, $taskIdParam, (int)$taskIdParam]);

        if ($stmt->rowCount() === 0) {
            http_response_code(409);
            echo json_encode(['error' => true, 'message' => 'Task not in completable state']);
            break;
        }

        addLog($pdo, $taskIdParam, 'success', 'System', "Task completed" . ($summary ? ": $summary" : ''));

        // Clean up queue file
        $queueFile = dirname(__DIR__) . '/data/agent-queue/' . $taskIdParam . '.json';
        if (file_exists($queueFile)) unlink($queueFile);

        echo json_encode(['success' => true, 'message' => 'Task marked as done']);
        break;

    // ── Fail Task ─────────────────────────────────────────────────
    case 'fail':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => true, 'message' => 'POST required']);
            break;
        }
        if (!$isInternal && !verifyCsrf()) {
            http_response_code(403);
            echo json_encode(['error' => true, 'message' => 'Invalid CSRF token']);
            break;
        }
        if (!$taskIdParam) {
            http_response_code(400);
            echo json_encode(['error' => true, 'message' => 'Task ID required']);
            break;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $errorMsg = trim($input['error'] ?? 'Unknown error');

        $stmt = $pdo->prepare("UPDATE agent_orchestrator_tasks SET
            status = 'failed', error_message = ?, retry_count = retry_count + 1
            WHERE (task_id = ? OR id = ?) AND status IN ('running','claimed')");
        $stmt->execute([$errorMsg, $taskIdParam, (int)$taskIdParam]);

        addLog($pdo, $taskIdParam, 'error', 'System', "Task failed: $errorMsg");

        // Clean up queue file
        $queueFile = dirname(__DIR__) . '/data/agent-queue/' . $taskIdParam . '.json';
        if (file_exists($queueFile)) unlink($queueFile);

        echo json_encode(['success' => true, 'message' => 'Task marked as failed']);
        break;

    // ── Bulk Create ───────────────────────────────────────────────
    case 'bulk_create':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => true, 'message' => 'POST required']);
            break;
        }
        if (!$isInternal && !verifyCsrf()) {
            http_response_code(403);
            echo json_encode(['error' => true, 'message' => 'Invalid CSRF token']);
            break;
        }
        if (!$isOwner && !$isInternal) {
            http_response_code(403);
            echo json_encode(['error' => true, 'message' => 'Owner access required']);
            break;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $tasks = $input['tasks'] ?? [];
        if (empty($tasks) || !is_array($tasks)) {
            http_response_code(400);
            echo json_encode(['error' => true, 'message' => 'Tasks array required']);
            break;
        }

        $created = 0;
        $skipped = 0;
        $stmt = $pdo->prepare("INSERT IGNORE INTO agent_orchestrator_tasks
            (task_id, title, description, category, priority, target_file, agent_type, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        foreach ($tasks as $t) {
            $tId = trim($t['task_id'] ?? '');
            $tTitle = trim($t['title'] ?? '');
            if (!$tId || !$tTitle) { $skipped++; continue; }
            $bCat = $t['category'] ?? 'frontend';
            $bPri = $t['priority'] ?? 'P2';
            $validCategories = ['security','frontend','api','javascript','test','script','docs','sdk','debt','feature'];
            if (!in_array($bCat, $validCategories)) $bCat = 'frontend';
            if (!preg_match('/^P[0-4]$/', $bPri)) $bPri = 'P2';
            $stmt->execute([
                $tId,
                $tTitle,
                trim($t['description'] ?? ''),
                $bCat,
                $bPri,
                isset($t['target_file']) ? trim($t['target_file']) : null,
                isset($t['agent_type']) ? trim($t['agent_type']) : null,
                $clientId
            ]);
            if ($stmt->rowCount() > 0) $created++;
            else $skipped++;
        }

        echo json_encode(['success' => true, 'created' => $created, 'skipped' => $skipped]);
        break;

    // ── Import from UPGRADE_BACKLOG.md ────────────────────────────
    case 'import_backlog':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => true, 'message' => 'POST required']);
            break;
        }
        if (!$isInternal && !verifyCsrf()) {
            http_response_code(403);
            echo json_encode(['error' => true, 'message' => 'Invalid CSRF token']);
            break;
        }
        if (!$isOwner && !$isInternal) {
            http_response_code(403);
            echo json_encode(['error' => true, 'message' => 'Owner access required']);
            break;
        }

        $backlogPath = dirname(__DIR__) . '/UPGRADE_BACKLOG.md';
        if (!file_exists($backlogPath)) {
            http_response_code(404);
            echo json_encode(['error' => true, 'message' => 'UPGRADE_BACKLOG.md not found']);
            break;
        }

        $content = file_get_contents($backlogPath);
        $lines = explode("\n", $content);

        $currentPriority = 'P2';
        $currentCategory = 'frontend';
        $created = 0;
        $skipped = 0;

        $stmt = $pdo->prepare("INSERT IGNORE INTO agent_orchestrator_tasks
            (task_id, title, description, category, priority, created_by)
            VALUES (?, ?, ?, ?, ?, ?)");

        foreach ($lines as $line) {
            // Detect priority sections
            if (preg_match('/^## P(\d)/', $line, $m)) {
                $currentPriority = 'P' . $m[1];
            }
            // Detect category headers
            if (stripos($line, 'SECURITY') !== false) $currentCategory = 'security';
            elseif (stripos($line, 'FRONTEND') !== false) $currentCategory = 'frontend';
            elseif (stripos($line, 'API ENDPOINTS') !== false) $currentCategory = 'api';
            elseif (stripos($line, 'JAVASCRIPT') !== false) $currentCategory = 'javascript';
            elseif (stripos($line, 'TESTS') !== false) $currentCategory = 'test';
            elseif (stripos($line, 'SCRIPTS') !== false) $currentCategory = 'script';
            elseif (stripos($line, 'DOCUMENTATION') !== false) $currentCategory = 'docs';
            elseif (stripos($line, 'SDK') !== false) $currentCategory = 'sdk';
            elseif (stripos($line, 'TECHNICAL DEBT') !== false) $currentCategory = 'debt';
            elseif (stripos($line, 'NEW FEATURES') !== false) $currentCategory = 'feature';

            // Parse task lines: - [ ] `TASK-ID` — Description (supports em-dash and regular dash)
            if (preg_match('/^- \[ \] `([A-Z]+-\d+)` [—–-]{1,2} (.+)$/', $line, $m)) {
                $taskId = $m[1];
                $title = trim($m[2]);
                $stmt->execute([$taskId, $title, $title, $currentCategory, $currentPriority, $clientId]);
                if ($stmt->rowCount() > 0) $created++;
                else $skipped++;
            }
        }

        echo json_encode(['success' => true, 'imported' => $created, 'skipped_duplicates' => $skipped]);
        break;

    // ── Import Circuit Sim Backlog ────────────────────────────────
    case 'import_circuit_sim':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => true, 'message' => 'POST required']);
            break;
        }
        if (!$isInternal && !verifyCsrf()) {
            http_response_code(403);
            echo json_encode(['error' => true, 'message' => 'Invalid CSRF token']);
            break;
        }
        if (!$isOwner && !$isInternal) {
            http_response_code(403);
            echo json_encode(['error' => true, 'message' => 'Owner access required']);
            break;
        }

        $csPath = dirname(__DIR__) . '/data/circuit-sim-upgrade-backlog.md';
        if (!file_exists($csPath)) {
            http_response_code(404);
            echo json_encode(['error' => true, 'message' => 'circuit-sim-upgrade-backlog.md not found']);
            break;
        }

        $content = file_get_contents($csPath);
        $lines = explode("\n", $content);

        $currentPriority = 'P2';
        $currentCategory = 'feature';
        $created = 0;
        $skipped = 0;

        // Map stream categories
        $categoryMap = [
            'Stream A' => 'javascript', 'Stream B' => 'javascript', 'Stream C' => 'frontend',
            'Stream D' => 'frontend', 'Stream E' => 'javascript', 'Stream F' => 'frontend',
            'Stream G' => 'api', 'Stream H' => 'debt', 'Stream I' => 'javascript', 'Stream J' => 'security',
        ];

        $stmt = $pdo->prepare("INSERT IGNORE INTO agent_orchestrator_tasks
            (task_id, title, description, category, priority, target_file, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)");

        foreach ($lines as $line) {
            // Detect priority from headers: ## Stream A: ... [P0]
            if (preg_match('/^## Stream ([A-J]).*?\[(P\d)\]/', $line, $m)) {
                $currentPriority = $m[2];
                $streamKey = 'Stream ' . $m[1];
                if (isset($categoryMap[$streamKey])) $currentCategory = $categoryMap[$streamKey];
            }

            // Parse: - CSA-001 — [feature] Title — `target_file`
            if (preg_match('/^- (CS[A-J]-\d+) [—–-]{1,2} \[(feature|debt|security)\] (.+?) [—–-]{1,2} `(.+?)`$/', $line, $m)) {
                $taskId = $m[1];
                $type = $m[2];
                $title = trim($m[3]);
                $targetFile = trim($m[4]);
                $cat = ($type === 'debt') ? 'debt' : (($type === 'security') ? 'security' : $currentCategory);
                $stmt->execute([$taskId, $title, $title, $cat, $currentPriority, $targetFile, $clientId]);
                if ($stmt->rowCount() > 0) $created++;
                else $skipped++;
            }
        }

        echo json_encode(['success' => true, 'imported' => $created, 'skipped_duplicates' => $skipped, 'source' => 'circuit-sim']);
        break;

    // ── Cancel Task ───────────────────────────────────────────────
    case 'cancel':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => true, 'message' => 'POST required']);
            break;
        }
        if (!$isInternal && !verifyCsrf()) {
            http_response_code(403);
            echo json_encode(['error' => true, 'message' => 'Invalid CSRF token']);
            break;
        }
        if (!$taskIdParam) {
            http_response_code(400);
            echo json_encode(['error' => true, 'message' => 'Task ID required']);
            break;
        }
        $stmt = $pdo->prepare("UPDATE agent_orchestrator_tasks SET status = 'cancelled' WHERE (task_id = ? OR id = ?) AND status IN ('pending','claimed','failed')");
        $stmt->execute([$taskIdParam, (int)$taskIdParam]);
        if ($stmt->rowCount() === 0) {
            http_response_code(409);
            echo json_encode(['error' => true, 'message' => 'Task not in cancellable state']);
            break;
        }
        addLog($pdo, $taskIdParam, 'info', 'System', 'Task cancelled');
        echo json_encode(['success' => true, 'message' => 'Task cancelled']);
        break;

    // ── Add Log Entry ─────────────────────────────────────────────
    case 'log':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => true, 'message' => 'POST required']);
            break;
        }
        if (!$isInternal && !verifyCsrf()) {
            http_response_code(403);
            echo json_encode(['error' => true, 'message' => 'Invalid CSRF token']);
            break;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $logTaskId = trim($input['task_id'] ?? '');
        $logLevel  = $input['level'] ?? 'info';
        $logAgent  = trim($input['agent'] ?? 'Unknown');
        $logMsg    = trim($input['message'] ?? '');

        if (!$logTaskId || !$logMsg) {
            http_response_code(400);
            echo json_encode(['error' => true, 'message' => 'task_id and message required']);
            break;
        }

        // Validate level against ENUM
        $validLevels = ['info', 'warn', 'error', 'success'];
        if (!in_array($logLevel, $validLevels)) $logLevel = 'info';

        addLog($pdo, $logTaskId, $logLevel, $logAgent, $logMsg);
        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => true, 'message' => 'Unknown action. Valid: backlog, stats, task, logs, create, claim, retry, complete, fail, spawn, cancel, bulk_create, import_backlog, import_circuit_sim, log']);
        break;
}

// ── Helper: Add Log ───────────────────────────────────────────────
function addLog(PDO $pdo, string $taskId, string $level, string $agent, string $message): void {
    $stmt = $pdo->prepare("INSERT INTO agent_orchestrator_logs (task_id, level, agent_name, message) VALUES (?, ?, ?, ?)");
    $stmt->execute([$taskId, $level, $agent, $message]);
}
