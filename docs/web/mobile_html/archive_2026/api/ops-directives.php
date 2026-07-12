<?php
/**
 * GoSiteMe — Agent Operations Directive System
 * ═══════════════════════════════════════════════
 * "For where two or three gather in my name, there am I with them." — Matthew 18:20
 *
 * The command & control layer for autonomous agent operations.
 * Danny (Supreme Commander) sets directives → Alfred (Chief Commander) orchestrates
 * → Directors delegate → Specialists execute → Results flow back up.
 *
 * Directive Types:
 *   repair      — Fix broken things (self-healing, service restarts, data fixes)
 *   upgrade     — Improve capabilities (new features, optimizations, integrations)
 *   investigate — Research and report (audit, analyze, discover)
 *   maintain    — Routine upkeep (cleanup, rotation, optimization)
 *   deploy      — Ship changes (config updates, feature flags, rollouts)
 *
 * Flow: directive → alfred_ops_directives → autonomy-cron picks up → delegates to agents
 *       → agents report outcome → escalation if SLA breached → Danny notified
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/../api/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

// Auth: internal secret OR admin session
session_start();
$internalSecret = getenv('INTERNAL_SECRET') ?: '';
$isInternal = $internalSecret && isset($_SERVER['HTTP_X_INTERNAL_SECRET'])
    && hash_equals($internalSecret, $_SERVER['HTTP_X_INTERNAL_SECRET']);
$isAdmin = isset($_SESSION['user_email']) && $_SESSION['user_email'] === 'gositeme@gmail.com';

if (!$isInternal && !$isAdmin) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$db = getDB();

// ── Bootstrap tables on first call ──────────────────────────────────────────
ensureSchema($db);

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {
    $result = match ($action) {
        // ─── Core CRUD ───────────────────────────────────────────────────
        'create'     => createDirective($db),
        'list'       => listDirectives($db),
        'get'        => getDirective($db),
        'update'     => updateDirective($db),
        'cancel'     => cancelDirective($db),

        // ─── Batch Operations ────────────────────────────────────────────
        'batch'      => batchCreate($db),
        'templates'  => getTemplates($db),

        // ─── Agent Execution ─────────────────────────────────────────────
        'claim'      => claimDirective($db),       // Agent picks up work
        'report'     => reportOutcome($db),         // Agent reports result
        'escalate'   => escalateDirective($db),     // Agent escalates to director

        // ─── Ops Intelligence ────────────────────────────────────────────
        'dashboard'  => opsDashboard($db),
        'timeline'   => opsTimeline($db),
        'sla-check'  => slaCheck($db),
        'agent-perf' => agentPerformance($db),

        // ─── Standing Orders ─────────────────────────────────────────────
        'standing-orders'     => listStandingOrders($db),
        'create-standing'     => createStandingOrder($db),
        'toggle-standing'     => toggleStandingOrder($db),

        default => ['error' => 'Unknown action: ' . $action]
    };
    echo json_encode($result, JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    http_response_code(500);
    error_log('[ops-directives] ' . $e->getMessage());
    echo json_encode(['error' => 'Internal server error']);
}

// ═══════════════════════════════════════════════════════════════════════════════
// SCHEMA
// ═══════════════════════════════════════════════════════════════════════════════
function ensureSchema(PDO $db): void
{
    $db->exec("
        CREATE TABLE IF NOT EXISTS alfred_ops_directives (
            id              INT AUTO_INCREMENT PRIMARY KEY,
            directive_id    VARCHAR(36) NOT NULL UNIQUE DEFAULT (UUID()),
            type            ENUM('repair','upgrade','investigate','maintain','deploy') NOT NULL,
            title           VARCHAR(255) NOT NULL,
            description     TEXT,
            priority        TINYINT NOT NULL DEFAULT 5 COMMENT '1=low 9=critical',
            status          ENUM('pending','claimed','in_progress','blocked','completed','failed','cancelled','escalated') NOT NULL DEFAULT 'pending',
            source          ENUM('commander','alfred','agent','system','cron') NOT NULL DEFAULT 'commander',
            assigned_agent  VARCHAR(100) DEFAULT NULL,
            assigned_by     VARCHAR(100) DEFAULT 'ALFRED',
            sla_minutes     INT DEFAULT 60 COMMENT 'Max time to complete',
            escalation_path VARCHAR(255) DEFAULT 'specialist→director→alfred→commander',
            input_data      JSON DEFAULT NULL,
            output_data     JSON DEFAULT NULL,
            error_message   TEXT DEFAULT NULL,
            attempts        INT DEFAULT 0,
            max_attempts    INT DEFAULT 3,
            parent_id       INT DEFAULT NULL COMMENT 'For sub-directives',
            tags            JSON DEFAULT NULL,
            claimed_at      DATETIME DEFAULT NULL,
            started_at      DATETIME DEFAULT NULL,
            completed_at    DATETIME DEFAULT NULL,
            deadline        DATETIME DEFAULT NULL,
            created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_priority (priority DESC),
            INDEX idx_type (type),
            INDEX idx_agent (assigned_agent),
            INDEX idx_parent (parent_id),
            FOREIGN KEY (parent_id) REFERENCES alfred_ops_directives(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS alfred_ops_standing_orders (
            id              INT AUTO_INCREMENT PRIMARY KEY,
            order_id        VARCHAR(36) NOT NULL UNIQUE DEFAULT (UUID()),
            title           VARCHAR(255) NOT NULL,
            description     TEXT,
            type            ENUM('repair','upgrade','investigate','maintain','deploy') NOT NULL,
            schedule        VARCHAR(100) NOT NULL COMMENT 'cron expression or interval',
            priority        TINYINT NOT NULL DEFAULT 5,
            assigned_agent  VARCHAR(100) DEFAULT NULL,
            input_data      JSON DEFAULT NULL,
            active          BOOLEAN DEFAULT TRUE,
            last_run_at     DATETIME DEFAULT NULL,
            next_run_at     DATETIME DEFAULT NULL,
            run_count       INT DEFAULT 0,
            created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_active (active),
            INDEX idx_next (next_run_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS alfred_ops_log (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            directive_id VARCHAR(36) NOT NULL,
            agent       VARCHAR(100),
            action      VARCHAR(100) NOT NULL,
            details     JSON DEFAULT NULL,
            created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_directive (directive_id),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

// ═══════════════════════════════════════════════════════════════════════════════
// CORE CRUD
// ═══════════════════════════════════════════════════════════════════════════════
function createDirective(PDO $db): array
{
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $required = ['type', 'title'];
    foreach ($required as $field) {
        if (empty($data[$field])) return ['error' => "Missing required field: $field"];
    }

    $type = $data['type'];
    $validTypes = ['repair', 'upgrade', 'investigate', 'maintain', 'deploy'];
    if (!in_array($type, $validTypes, true)) {
        return ['error' => 'Invalid type. Must be: ' . implode(', ', $validTypes)];
    }

    $stmt = $db->prepare("
        INSERT INTO alfred_ops_directives
            (type, title, description, priority, source, assigned_agent, sla_minutes, input_data, tags, deadline)
        VALUES
            (:type, :title, :desc, :priority, :source, :agent, :sla, :input, :tags, :deadline)
    ");

    $stmt->execute([
        'type'     => $type,
        'title'    => substr($data['title'], 0, 255),
        'desc'     => $data['description'] ?? null,
        'priority' => max(1, min(9, (int)($data['priority'] ?? 5))),
        'source'   => $data['source'] ?? 'commander',
        'agent'    => $data['assigned_agent'] ?? null,
        'sla'      => (int)($data['sla_minutes'] ?? 60),
        'input'    => isset($data['input_data']) ? json_encode($data['input_data']) : null,
        'tags'     => isset($data['tags']) ? json_encode($data['tags']) : null,
        'deadline' => $data['deadline'] ?? null,
    ]);

    $id = $db->lastInsertId();
    $stmt2 = $db->prepare("SELECT * FROM alfred_ops_directives WHERE id = :id");
    $stmt2->execute(['id' => $id]);
    $directive = $stmt2->fetch();

    logOps($db, $directive['directive_id'], $data['source'] ?? 'commander', 'created', [
        'title' => $data['title'], 'priority' => $data['priority'] ?? 5
    ]);

    return ['ok' => true, 'directive' => $directive];
}

function listDirectives(PDO $db): array
{
    $status = $_GET['status'] ?? null;
    $type   = $_GET['type'] ?? null;
    $agent  = $_GET['agent'] ?? null;
    $limit  = min(100, max(1, (int)($_GET['limit'] ?? 50)));
    $offset = max(0, (int)($_GET['offset'] ?? 0));

    $where = ['1=1'];
    $params = [];

    if ($status) {
        $where[] = 'status = :status';
        $params['status'] = $status;
    }
    if ($type) {
        $where[] = 'type = :type';
        $params['type'] = $type;
    }
    if ($agent) {
        $where[] = 'assigned_agent = :agent';
        $params['agent'] = $agent;
    }

    $sql = "SELECT * FROM alfred_ops_directives WHERE " . implode(' AND ', $where)
         . " ORDER BY priority DESC, created_at ASC LIMIT $limit OFFSET $offset";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $directives = $stmt->fetchAll();

    $countSql = "SELECT COUNT(*) FROM alfred_ops_directives WHERE " . implode(' AND ', $where);
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);

    return [
        'directives' => $directives,
        'total'      => (int)$countStmt->fetchColumn(),
        'limit'      => $limit,
        'offset'     => $offset,
    ];
}

function getDirective(PDO $db): array
{
    $id = $_GET['id'] ?? null;
    if (!$id) return ['error' => 'Missing id'];

    $stmt = $db->prepare("SELECT * FROM alfred_ops_directives WHERE id = :id OR directive_id = :id");
    $stmt->execute(['id' => $id]);
    $directive = $stmt->fetch();
    if (!$directive) return ['error' => 'Not found'];

    // Get sub-directives
    $sub = $db->prepare("SELECT * FROM alfred_ops_directives WHERE parent_id = :pid ORDER BY priority DESC");
    $sub->execute(['pid' => $directive['id']]);

    // Get activity log
    $log = $db->prepare("SELECT * FROM alfred_ops_log WHERE directive_id = :did ORDER BY created_at DESC LIMIT 50");
    $log->execute(['did' => $directive['directive_id']]);

    return [
        'directive'      => $directive,
        'sub_directives' => $sub->fetchAll(),
        'activity_log'   => $log->fetchAll(),
    ];
}

function updateDirective(PDO $db): array
{
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $id = $data['id'] ?? $_GET['id'] ?? null;
    if (!$id) return ['error' => 'Missing id'];

    $allowedFields = ['title', 'description', 'priority', 'status', 'assigned_agent', 'sla_minutes', 'tags', 'deadline'];
    $sets = [];
    $params = ['id' => $id];

    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $sets[] = "$field = :$field";
            $params[$field] = is_array($data[$field]) ? json_encode($data[$field]) : $data[$field];
        }
    }

    if (empty($sets)) return ['error' => 'No fields to update'];

    $stmt = $db->prepare("UPDATE alfred_ops_directives SET " . implode(', ', $sets) . " WHERE id = :id OR directive_id = :id");
    $stmt->execute($params);

    return ['ok' => true, 'updated' => $stmt->rowCount()];
}

function cancelDirective(PDO $db): array
{
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $id = $data['id'] ?? $_GET['id'] ?? null;
    if (!$id) return ['error' => 'Missing id'];

    $stmt = $db->prepare("
        UPDATE alfred_ops_directives SET status = 'cancelled'
        WHERE (id = :id OR directive_id = :id) AND status NOT IN ('completed','cancelled')
    ");
    $stmt->execute(['id' => $id]);

    return ['ok' => true, 'cancelled' => $stmt->rowCount()];
}

// ═══════════════════════════════════════════════════════════════════════════════
// BATCH & TEMPLATES
// ═══════════════════════════════════════════════════════════════════════════════
function batchCreate(PDO $db): array
{
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['directives']) || !is_array($data['directives'])) {
        return ['error' => 'Missing directives array'];
    }

    $created = [];
    foreach ($data['directives'] as $d) {
        $_POST = $d;
        $result = createDirective($db);
        $created[] = $result;
    }

    return ['ok' => true, 'created' => count($created), 'results' => $created];
}

function getTemplates(PDO $db): array
{
    return [
        'templates' => [
            [
                'name' => 'Full System Health Check',
                'type' => 'investigate',
                'title' => 'Run comprehensive system health audit',
                'description' => 'Check all services, DB, API endpoints, security headers, SSL, disk, memory, CPU',
                'priority' => 7,
                'sla_minutes' => 15,
                'assigned_agent' => 'SENTINEL-GUARD',
                'tags' => ['health', 'audit', 'automated'],
            ],
            [
                'name' => 'Feed Processing Sweep',
                'type' => 'maintain',
                'title' => 'Process all unprocessed feed items',
                'description' => 'Poll stale feeds, process queued items, update relevance scores',
                'priority' => 4,
                'sla_minutes' => 30,
                'assigned_agent' => 'SAGE-CRAWLER',
                'tags' => ['feeds', 'data', 'routine'],
            ],
            [
                'name' => 'Security Vulnerability Scan',
                'type' => 'investigate',
                'title' => 'Scan for exposed secrets, weak permissions, outdated deps',
                'description' => 'Check .env exposure, backup files, directory permissions, dependency vulns',
                'priority' => 8,
                'sla_minutes' => 20,
                'assigned_agent' => 'SENTINEL-GUARD',
                'tags' => ['security', 'audit', 'critical'],
            ],
            [
                'name' => 'Database Optimization',
                'type' => 'maintain',
                'title' => 'Optimize database tables and clean stale data',
                'description' => 'ANALYZE tables, remove orphan records, check index usage, report slow queries',
                'priority' => 5,
                'sla_minutes' => 45,
                'assigned_agent' => 'CIPHER-ANALYST',
                'tags' => ['database', 'performance', 'routine'],
            ],
            [
                'name' => 'Agent Performance Review',
                'type' => 'investigate',
                'title' => 'Analyze agent success rates and optimize delegation',
                'description' => 'Review task completion rates, identify underperforming agents, suggest retraining',
                'priority' => 6,
                'sla_minutes' => 30,
                'assigned_agent' => 'NOVA',
                'tags' => ['agents', 'performance', 'optimization'],
            ],
            [
                'name' => 'Service Recovery',
                'type' => 'repair',
                'title' => 'Restart failed services and verify operation',
                'description' => 'Check PM2 process list, restart crashed services, verify ports, test endpoints',
                'priority' => 9,
                'sla_minutes' => 10,
                'assigned_agent' => 'ARCHITECT-BUILDER',
                'tags' => ['services', 'repair', 'critical'],
            ],
            [
                'name' => 'Treasury & Economy Report',
                'type' => 'investigate',
                'title' => 'Generate financial report: revenue, costs, DeFi positions',
                'description' => 'Aggregate billing data, treasury balance, DeFi yield, GSM token metrics',
                'priority' => 6,
                'sla_minutes' => 30,
                'assigned_agent' => 'ATLAS-TRADER',
                'tags' => ['finance', 'reporting', 'economy'],
            ],
            [
                'name' => 'Deploy Feature Flag',
                'type' => 'deploy',
                'title' => 'Toggle feature flag for gradual rollout',
                'description' => 'Enable/disable feature flags with percentage rollout and monitoring',
                'priority' => 7,
                'sla_minutes' => 5,
                'assigned_agent' => 'ALFRED',
                'tags' => ['deploy', 'feature-flags', 'rollout'],
            ],
        ]
    ];
}

// ═══════════════════════════════════════════════════════════════════════════════
// AGENT EXECUTION
// ═══════════════════════════════════════════════════════════════════════════════
function claimDirective(PDO $db): array
{
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $agent = $data['agent'] ?? null;
    if (!$agent) return ['error' => 'Missing agent name'];

    $directiveId = $data['directive_id'] ?? null;

    if ($directiveId) {
        // Claim specific directive
        $stmt = $db->prepare("
            UPDATE alfred_ops_directives
            SET status = 'claimed', assigned_agent = :agent, claimed_at = NOW()
            WHERE directive_id = :did AND status = 'pending'
        ");
        $stmt->execute(['agent' => $agent, 'did' => $directiveId]);
    } else {
        // Auto-claim highest priority pending directive (optionally matching agent)
        $stmt = $db->prepare("
            UPDATE alfred_ops_directives
            SET status = 'claimed', assigned_agent = :agent, claimed_at = NOW()
            WHERE status = 'pending'
              AND (assigned_agent IS NULL OR assigned_agent = :agent2)
            ORDER BY priority DESC, created_at ASC
            LIMIT 1
        ");
        $stmt->execute(['agent' => $agent, 'agent2' => $agent]);
    }

    if ($stmt->rowCount() === 0) {
        return ['ok' => true, 'message' => 'No directives available to claim'];
    }

    // Fetch the claimed directive
    $fetch = $db->prepare("
        SELECT * FROM alfred_ops_directives
        WHERE assigned_agent = :agent AND status = 'claimed'
        ORDER BY claimed_at DESC LIMIT 1
    ");
    $fetch->execute(['agent' => $agent]);
    $directive = $fetch->fetch();

    logOps($db, $directive['directive_id'], $agent, 'claimed', []);

    return ['ok' => true, 'directive' => $directive];
}

function reportOutcome(PDO $db): array
{
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $directiveId = $data['directive_id'] ?? null;
    $outcome     = $data['outcome'] ?? null; // 'completed' or 'failed'
    $agent       = $data['agent'] ?? null;

    if (!$directiveId || !$outcome || !$agent) {
        return ['error' => 'Missing directive_id, outcome, or agent'];
    }

    $validOutcomes = ['completed', 'failed', 'blocked'];
    if (!in_array($outcome, $validOutcomes, true)) {
        return ['error' => 'Invalid outcome. Must be: ' . implode(', ', $validOutcomes)];
    }

    $stmt = $db->prepare("
        UPDATE alfred_ops_directives
        SET status        = :status,
            output_data   = :output,
            error_message = :error,
            completed_at  = CASE WHEN :status2 = 'completed' THEN NOW() ELSE completed_at END,
            attempts      = attempts + 1
        WHERE directive_id = :did
    ");
    $stmt->execute([
        'status'  => $outcome,
        'status2' => $outcome,
        'output'  => isset($data['output']) ? json_encode($data['output']) : null,
        'error'   => $data['error_message'] ?? null,
        'did'     => $directiveId,
    ]);

    // If failed and under max_attempts, requeue
    if ($outcome === 'failed') {
        $check = $db->prepare("
            SELECT attempts, max_attempts, directive_id FROM alfred_ops_directives WHERE directive_id = :did
        ");
        $check->execute(['did' => $directiveId]);
        $row = $check->fetch();
        if ($row && $row['attempts'] < $row['max_attempts']) {
            $db->prepare("UPDATE alfred_ops_directives SET status = 'pending', assigned_agent = NULL WHERE directive_id = :did")
               ->execute(['did' => $directiveId]);
            logOps($db, $directiveId, 'SYSTEM', 'requeued', ['attempt' => $row['attempts']]);
        } else {
            // Escalate
            escalateDirectiveById($db, $directiveId, $agent, 'Max attempts reached');
        }
    }

    logOps($db, $directiveId, $agent, "outcome:$outcome", $data['output'] ?? []);

    // Update agent stats
    updateAgentStats($db, $agent, $outcome);

    return ['ok' => true, 'outcome' => $outcome];
}

function escalateDirective(PDO $db): array
{
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $directiveId = $data['directive_id'] ?? null;
    $agent       = $data['agent'] ?? 'SYSTEM';
    $reason      = $data['reason'] ?? 'Manual escalation';

    if (!$directiveId) return ['error' => 'Missing directive_id'];

    return escalateDirectiveById($db, $directiveId, $agent, $reason);
}

function escalateDirectiveById(PDO $db, string $directiveId, string $agent, string $reason): array
{
    $stmt = $db->prepare("
        UPDATE alfred_ops_directives
        SET status = 'escalated', error_message = :reason
        WHERE directive_id = :did
    ");
    $stmt->execute(['did' => $directiveId, 'reason' => $reason]);

    logOps($db, $directiveId, $agent, 'escalated', ['reason' => $reason]);

    return ['ok' => true, 'escalated' => true, 'reason' => $reason];
}

// ═══════════════════════════════════════════════════════════════════════════════
// STANDING ORDERS (recurring directives)
// ═══════════════════════════════════════════════════════════════════════════════
function listStandingOrders(PDO $db): array
{
    $orders = $db->query("SELECT * FROM alfred_ops_standing_orders ORDER BY priority DESC")->fetchAll();
    return ['orders' => $orders];
}

function createStandingOrder(PDO $db): array
{
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $required = ['type', 'title', 'schedule'];
    foreach ($required as $f) {
        if (empty($data[$f])) return ['error' => "Missing: $f"];
    }

    $stmt = $db->prepare("
        INSERT INTO alfred_ops_standing_orders
            (title, description, type, schedule, priority, assigned_agent, input_data)
        VALUES
            (:title, :desc, :type, :schedule, :priority, :agent, :input)
    ");
    $stmt->execute([
        'title'    => substr($data['title'], 0, 255),
        'desc'     => $data['description'] ?? null,
        'type'     => $data['type'],
        'schedule' => $data['schedule'],
        'priority' => max(1, min(9, (int)($data['priority'] ?? 5))),
        'agent'    => $data['assigned_agent'] ?? null,
        'input'    => isset($data['input_data']) ? json_encode($data['input_data']) : null,
    ]);

    return ['ok' => true, 'id' => $db->lastInsertId()];
}

function toggleStandingOrder(PDO $db): array
{
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $id = $data['id'] ?? $_GET['id'] ?? null;
    if (!$id) return ['error' => 'Missing id'];

    $stmt = $db->prepare("UPDATE alfred_ops_standing_orders SET active = NOT active WHERE id = :id");
    $stmt->execute(['id' => $id]);

    return ['ok' => true, 'toggled' => $stmt->rowCount()];
}

// ═══════════════════════════════════════════════════════════════════════════════
// OPS INTELLIGENCE
// ═══════════════════════════════════════════════════════════════════════════════
function opsDashboard(PDO $db): array
{
    $statusCounts = $db->query("
        SELECT status, COUNT(*) as cnt FROM alfred_ops_directives GROUP BY status
    ")->fetchAll(PDO::FETCH_KEY_PAIR);

    $typeCounts = $db->query("
        SELECT type, COUNT(*) as cnt FROM alfred_ops_directives GROUP BY type
    ")->fetchAll(PDO::FETCH_KEY_PAIR);

    $avgCompletion = $db->query("
        SELECT ROUND(AVG(TIMESTAMPDIFF(MINUTE, created_at, completed_at)), 1) as avg_minutes
        FROM alfred_ops_directives WHERE status = 'completed'
    ")->fetchColumn();

    $slaBreaches = $db->query("
        SELECT COUNT(*) FROM alfred_ops_directives
        WHERE status = 'completed'
          AND TIMESTAMPDIFF(MINUTE, created_at, completed_at) > sla_minutes
    ")->fetchColumn();

    $topAgents = $db->query("
        SELECT assigned_agent, COUNT(*) as completed,
               ROUND(AVG(TIMESTAMPDIFF(MINUTE, claimed_at, completed_at)), 1) as avg_minutes
        FROM alfred_ops_directives
        WHERE status = 'completed' AND assigned_agent IS NOT NULL
        GROUP BY assigned_agent
        ORDER BY completed DESC
        LIMIT 10
    ")->fetchAll();

    $recentActivity = $db->query("
        SELECT * FROM alfred_ops_log ORDER BY created_at DESC LIMIT 20
    ")->fetchAll();

    $standingOrders = $db->query("
        SELECT COUNT(*) as total,
               SUM(active) as active_count
        FROM alfred_ops_standing_orders
    ")->fetch();

    return [
        'status_breakdown' => $statusCounts,
        'type_breakdown'   => $typeCounts,
        'avg_completion_minutes' => $avgCompletion ?: 0,
        'sla_breaches'     => (int)$slaBreaches,
        'top_agents'       => $topAgents,
        'standing_orders'  => $standingOrders,
        'recent_activity'  => $recentActivity,
        'timestamp'        => date('c'),
    ];
}

function opsTimeline(PDO $db): array
{
    $days = min(90, max(1, (int)($_GET['days'] ?? 7)));

    $timeline = $db->prepare("
        SELECT DATE(created_at) as day,
               SUM(status = 'completed') as completed,
               SUM(status = 'failed') as failed,
               SUM(status IN ('pending','claimed','in_progress')) as active,
               COUNT(*) as total
        FROM alfred_ops_directives
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
        GROUP BY DATE(created_at)
        ORDER BY day
    ");
    $timeline->execute(['days' => $days]);

    return ['timeline' => $timeline->fetchAll(), 'days' => $days];
}

function slaCheck(PDO $db): array
{
    $breached = $db->query("
        SELECT directive_id, title, type, priority, assigned_agent, sla_minutes,
               TIMESTAMPDIFF(MINUTE, created_at, NOW()) as elapsed_minutes,
               status
        FROM alfred_ops_directives
        WHERE status IN ('pending','claimed','in_progress')
          AND TIMESTAMPDIFF(MINUTE, created_at, NOW()) > sla_minutes
        ORDER BY priority DESC
    ")->fetchAll();

    return ['sla_breaches' => $breached, 'count' => count($breached)];
}

function agentPerformance(PDO $db): array
{
    $agents = $db->query("
        SELECT assigned_agent as agent,
               COUNT(*) as total_tasks,
               SUM(status = 'completed') as completed,
               SUM(status = 'failed') as failed,
               SUM(status = 'escalated') as escalated,
               ROUND(SUM(status = 'completed') / NULLIF(COUNT(*), 0) * 100, 1) as success_rate,
               ROUND(AVG(CASE WHEN status = 'completed'
                   THEN TIMESTAMPDIFF(SECOND, claimed_at, completed_at) END) / 60, 1) as avg_minutes
        FROM alfred_ops_directives
        WHERE assigned_agent IS NOT NULL
        GROUP BY assigned_agent
        ORDER BY total_tasks DESC
    ")->fetchAll();

    return ['agents' => $agents];
}

// ═══════════════════════════════════════════════════════════════════════════════
// HELPERS
// ═══════════════════════════════════════════════════════════════════════════════
function logOps(PDO $db, string $directiveId, string $agent, string $action, array $details): void
{
    $stmt = $db->prepare("
        INSERT INTO alfred_ops_log (directive_id, agent, action, details)
        VALUES (:did, :agent, :action, :details)
    ");
    $stmt->execute([
        'did'     => $directiveId,
        'agent'   => $agent,
        'action'  => $action,
        'details' => json_encode($details),
    ]);
}

function updateAgentStats(PDO $db, string $agent, string $outcome): void
{
    try {
        if ($outcome === 'completed') {
            $db->prepare("
                UPDATE alfred_agent_registry
                SET tasks_completed = tasks_completed + 1,
                    status = 'idle'
                WHERE agent_name = :agent
            ")->execute(['agent' => $agent]);
        } elseif ($outcome === 'failed') {
            $db->prepare("
                UPDATE alfred_agent_registry
                SET tasks_failed = tasks_failed + 1,
                    status = 'idle'
                WHERE agent_name = :agent
            ")->execute(['agent' => $agent]);
        }
    } catch (Throwable $e) {
        // Non-critical — don't break the flow
    }
}
