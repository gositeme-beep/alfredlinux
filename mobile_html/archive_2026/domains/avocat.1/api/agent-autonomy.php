<?php
require_once dirname(__DIR__) . '/includes/api-security.php';
/**
 * Agent Autonomy & Approval Workflow API
 * 
 * Agents can submit proposals, request funding, file reports.
 * Owner reviews & approves via Command Center.
 * Advisory Panel provides automated recommendations.
 * 
 * Endpoints:
 *   ?action=submit_proposal   POST  - Agent submits a project proposal
 *   ?action=proposals         GET   - List all proposals (filterable)
 *   ?action=approve           POST  - Owner approves a proposal
 *   ?action=reject            POST  - Owner rejects a proposal  
 *   ?action=advisory_review   GET   - Advisory panel auto-review of proposal
 *   ?action=agent_report      POST  - Agent files a status report
 *   ?action=reports           GET   - List agent reports
 *   ?action=panel_brief       GET   - Advisory panel daily briefing
 *   ?action=escalate          POST  - Agent escalates an issue to owner
 *   ?action=permissions       GET   - Get agent permission matrix
 *   ?action=grant_permission  POST  - Grant agent a new permission
 *   ?action=stats             GET   - Dashboard statistics
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Internal-Secret');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

define('GOSITEME_API', true);
require_once dirname(__DIR__) . '/api/config.php';

try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// ── Schema Bootstrap ──
$db->exec("CREATE TABLE IF NOT EXISTS agent_proposals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agent_id VARCHAR(64) NOT NULL,
    agent_name VARCHAR(128) NOT NULL,
    title VARCHAR(256) NOT NULL,
    description TEXT NOT NULL,
    category ENUM('feature','security','optimization','research','infrastructure','integration','expansion','maintenance') DEFAULT 'feature',
    priority ENUM('low','medium','high','critical','urgent') DEFAULT 'medium',
    estimated_cost DECIMAL(10,2) DEFAULT 0.00,
    estimated_hours INT DEFAULT 0,
    risk_level ENUM('low','medium','high','critical') DEFAULT 'low',
    affected_systems TEXT,
    proposed_changes TEXT,
    expected_outcome TEXT,
    advisory_score INT DEFAULT NULL,
    advisory_notes TEXT,
    status ENUM('pending','advisory_review','approved','rejected','in_progress','completed','cancelled') DEFAULT 'pending',
    approved_by VARCHAR(128) DEFAULT NULL,
    approved_at DATETIME DEFAULT NULL,
    rejection_reason TEXT DEFAULT NULL,
    completed_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_agent (agent_id),
    INDEX idx_priority (priority),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS agent_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agent_id VARCHAR(64) NOT NULL,
    agent_name VARCHAR(128) NOT NULL,
    report_type ENUM('status','progress','alert','escalation','daily','weekly','incident','discovery') DEFAULT 'status',
    title VARCHAR(256) NOT NULL,
    content TEXT NOT NULL,
    severity ENUM('info','notice','warning','critical','emergency') DEFAULT 'info',
    proposal_id INT DEFAULT NULL,
    metrics JSON DEFAULT NULL,
    requires_attention TINYINT(1) DEFAULT 0,
    acknowledged TINYINT(1) DEFAULT 0,
    acknowledged_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_agent (agent_id),
    INDEX idx_type (report_type),
    INDEX idx_severity (severity),
    INDEX idx_attention (requires_attention, acknowledged),
    FOREIGN KEY (proposal_id) REFERENCES agent_proposals(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS agent_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agent_id VARCHAR(64) NOT NULL,
    permission VARCHAR(128) NOT NULL,
    scope VARCHAR(256) DEFAULT '*',
    granted_by VARCHAR(128) NOT NULL,
    requires_approval TINYINT(1) DEFAULT 1,
    max_cost_without_approval DECIMAL(10,2) DEFAULT 0.00,
    granted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME DEFAULT NULL,
    active TINYINT(1) DEFAULT 1,
    UNIQUE KEY uk_agent_perm (agent_id, permission),
    INDEX idx_agent (agent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS advisory_panel_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proposal_id INT DEFAULT NULL,
    panel_member VARCHAR(64) NOT NULL,
    recommendation ENUM('approve','reject','needs_info','defer','escalate') NOT NULL,
    confidence_score INT DEFAULT 50,
    reasoning TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (proposal_id) REFERENCES agent_proposals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Auth Check ──
function isOwner(): bool {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return (int)($_SESSION['client_id'] ?? 0) === 33;
}

function isInternalAgent(): bool {
    $secret = $_SERVER['HTTP_X_INTERNAL_SECRET'] ?? '';
    return defined('INTERNAL_SECRET') && INTERNAL_SECRET !== '' && hash_equals(INTERNAL_SECRET, $secret);
}

function requireAuth(): void {
    if (!isOwner() && !isInternalAgent()) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
}

function requireOwner(): void {
    if (!isOwner()) {
        http_response_code(403);
        echo json_encode(['error' => 'Owner approval required']);
        exit;
    }
}

// ── Advisory Panel Logic ──
function advisoryReview(array $proposal): array {
    $score = 50;
    $notes = [];
    $recommendation = 'approve';

    // Cost analysis
    $cost = (float)$proposal['estimated_cost'];
    if ($cost === 0.0) {
        $score += 15;
        $notes[] = '✅ Zero cost — auto-approvable';
    } elseif ($cost < 50) {
        $score += 10;
        $notes[] = '✅ Low cost (<$50) — minor budget impact';
    } elseif ($cost < 500) {
        $score += 0;
        $notes[] = '⚠️ Moderate cost ($50-$500) — review recommended';
    } else {
        $score -= 10;
        $notes[] = '🔴 High cost (>$500) — owner approval mandatory';
        $recommendation = 'escalate';
    }

    // Risk analysis
    $risk = $proposal['risk_level'] ?? 'low';
    if ($risk === 'low') { $score += 10; $notes[] = '✅ Low risk — safe to proceed'; }
    elseif ($risk === 'medium') { $score += 0; $notes[] = '⚠️ Medium risk — proceed with monitoring'; }
    elseif ($risk === 'high') { $score -= 15; $notes[] = '🔴 High risk — requires owner review'; $recommendation = 'escalate'; }
    elseif ($risk === 'critical') { $score -= 25; $notes[] = '🚨 Critical risk — mandatory owner approval'; $recommendation = 'escalate'; }

    // Priority boost
    $priority = $proposal['priority'] ?? 'medium';
    if ($priority === 'critical' || $priority === 'urgent') { $score += 5; $notes[] = '⚡ High priority — expedited review'; }

    // Category bonuses
    $cat = $proposal['category'] ?? 'feature';
    if ($cat === 'security') { $score += 10; $notes[] = '🛡️ Security improvement — always prioritized'; }
    if ($cat === 'maintenance') { $score += 5; $notes[] = '🔧 Maintenance — keeps systems healthy'; }

    // Advisory panel members vote
    $panelMembers = [
        ['name' => 'Sage', 'role' => 'Strategic Advisor', 'focus' => 'long-term value'],
        ['name' => 'Sentinel', 'role' => 'Security Advisor', 'focus' => 'risk assessment'],
        ['name' => 'Atlas', 'role' => 'Operations Advisor', 'focus' => 'resource efficiency'],
        ['name' => 'Nova', 'role' => 'Innovation Advisor', 'focus' => 'growth potential'],
        ['name' => 'Cipher', 'role' => 'Technical Advisor', 'focus' => 'implementation feasibility'],
    ];

    $score = max(0, min(100, $score));
    
    if ($score >= 75 && $cost < 50 && $risk !== 'high' && $risk !== 'critical') {
        $recommendation = 'approve';
        $notes[] = '🟢 Advisory Panel: AUTO-APPROVE recommended (score ' . $score . '/100)';
    } elseif ($score >= 50) {
        $recommendation = 'approve';
        $notes[] = '🟡 Advisory Panel: APPROVE with monitoring (score ' . $score . '/100)';
    } elseif ($score >= 30) {
        $recommendation = 'needs_info';
        $notes[] = '🟠 Advisory Panel: NEEDS MORE INFO (score ' . $score . '/100)';
    } else {
        $recommendation = 'reject';
        $notes[] = '🔴 Advisory Panel: REJECT recommended (score ' . $score . '/100)';
    }

    return [
        'score' => $score,
        'recommendation' => $recommendation,
        'notes' => $notes,
        'panel_members' => $panelMembers,
        'auto_approvable' => ($score >= 75 && $cost < 50 && $risk !== 'high' && $risk !== 'critical'),
    ];
}

// ── Route ──
$action = $_GET['action'] ?? '';

switch ($action) {

    // ── Submit Proposal ──
    case 'submit_proposal':
        requireAuth();
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        
        $required = ['agent_id', 'agent_name', 'title', 'description'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Missing required field: $field"]);
                exit;
            }
        }

        $stmt = $db->prepare("INSERT INTO agent_proposals 
            (agent_id, agent_name, title, description, category, priority, estimated_cost, estimated_hours, risk_level, affected_systems, proposed_changes, expected_outcome, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'advisory_review')");
        $stmt->execute([
            substr($input['agent_id'], 0, 64),
            substr($input['agent_name'], 0, 128),
            substr($input['title'], 0, 256),
            $input['description'],
            $input['category'] ?? 'feature',
            $input['priority'] ?? 'medium',
            (float)($input['estimated_cost'] ?? 0),
            (int)($input['estimated_hours'] ?? 0),
            $input['risk_level'] ?? 'low',
            $input['affected_systems'] ?? null,
            $input['proposed_changes'] ?? null,
            $input['expected_outcome'] ?? null,
        ]);
        $proposalId = $db->lastInsertId();

        // Auto advisory review
        $review = advisoryReview($input);
        $db->prepare("UPDATE agent_proposals SET advisory_score = ?, advisory_notes = ? WHERE id = ?")
           ->execute([$review['score'], implode("\n", $review['notes']), $proposalId]);

        // Log panel votes
        foreach ($review['panel_members'] as $member) {
            $db->prepare("INSERT INTO advisory_panel_log (proposal_id, panel_member, recommendation, confidence_score, reasoning) VALUES (?, ?, ?, ?, ?)")
               ->execute([$proposalId, $member['name'], $review['recommendation'], $review['score'], "Focus: {$member['focus']}. Role: {$member['role']}"]);
        }

        // Auto-approve if score is high enough and cost is low
        if ($review['auto_approvable']) {
            $db->prepare("UPDATE agent_proposals SET status = 'approved', approved_by = 'Advisory Panel (Auto)', approved_at = NOW() WHERE id = ?")
               ->execute([$proposalId]);
        }

        // File a report for the agenda
        $db->prepare("INSERT INTO agent_reports (agent_id, agent_name, report_type, title, content, severity, proposal_id, requires_attention)
            VALUES (?, ?, 'status', ?, ?, ?, ?, ?)")
           ->execute([
               $input['agent_id'], $input['agent_name'],
               "📋 New Proposal: " . $input['title'],
               "Proposal submitted for review.\nAdvisory Score: {$review['score']}/100\nRecommendation: {$review['recommendation']}\n\n" . implode("\n", $review['notes']),
               $review['auto_approvable'] ? 'info' : 'notice',
               $proposalId,
               $review['auto_approvable'] ? 0 : 1,
           ]);

        echo json_encode([
            'success' => true,
            'proposal_id' => $proposalId,
            'advisory_review' => $review,
            'status' => $review['auto_approvable'] ? 'auto_approved' : 'pending_owner_review',
        ]);
        break;

    // ── List Proposals ──
    case 'proposals':
        requireAuth();
        $status = $_GET['status'] ?? null;
        $category = $_GET['category'] ?? null;
        $limit = min((int)($_GET['limit'] ?? 50), 200);

        $sql = "SELECT * FROM agent_proposals WHERE 1=1";
        $params = [];
        if ($status) { $sql .= " AND status = ?"; $params[] = $status; }
        if ($category) { $sql .= " AND category = ?"; $params[] = $category; }
        $sql .= " ORDER BY FIELD(priority,'urgent','critical','high','medium','low'), created_at DESC LIMIT ?";
        $params[] = $limit;

        $stmt = $db->prepare($sql);
        dbExecute($stmt, $params);
        
        echo json_encode([
            'proposals' => $stmt->fetchAll(),
            'total' => $db->query("SELECT COUNT(*) FROM agent_proposals")->fetchColumn(),
            'pending' => $db->query("SELECT COUNT(*) FROM agent_proposals WHERE status IN ('pending','advisory_review')")->fetchColumn(),
        ]);
        break;

    // ── Approve Proposal ──
    case 'approve':
        requireOwner();
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $id = (int)($input['proposal_id'] ?? 0);
        if (!$id) { http_response_code(400); echo json_encode(['error' => 'Missing proposal_id']); exit; }

        $stmt = $db->prepare("UPDATE agent_proposals SET status = 'approved', approved_by = 'Owner', approved_at = NOW() WHERE id = ? AND status IN ('pending','advisory_review')");
        $stmt->execute([$id]);

        // Log approval 
        $proposal = $db->prepare("SELECT * FROM agent_proposals WHERE id = ?");
        $proposal->execute([$id]);
        $p = $proposal->fetch();
        if ($p) {
            $db->prepare("INSERT INTO agent_reports (agent_id, agent_name, report_type, title, content, severity)
                VALUES (?, ?, 'status', ?, ?, 'info')")
               ->execute(['system', 'System', "✅ Proposal Approved: {$p['title']}", "Owner approved proposal #{$id} by {$p['agent_name']}."]);
        }

        echo json_encode(['success' => true, 'message' => "Proposal #{$id} approved"]);
        break;

    // ── Reject Proposal ──
    case 'reject':
        requireOwner();
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $id = (int)($input['proposal_id'] ?? 0);
        $reason = $input['reason'] ?? 'No reason provided';

        $stmt = $db->prepare("UPDATE agent_proposals SET status = 'rejected', rejection_reason = ? WHERE id = ? AND status IN ('pending','advisory_review')");
        $stmt->execute([$reason, $id]);

        echo json_encode(['success' => true, 'message' => "Proposal #{$id} rejected"]);
        break;

    // ── Advisory Review ──
    case 'advisory_review':
        requireAuth();
        $id = (int)($_GET['proposal_id'] ?? 0);
        if (!$id) { http_response_code(400); echo json_encode(['error' => 'Missing proposal_id']); exit; }

        $stmt = $db->prepare("SELECT * FROM agent_proposals WHERE id = ?");
        $stmt->execute([$id]);
        $proposal = $stmt->fetch();
        if (!$proposal) { http_response_code(404); echo json_encode(['error' => 'Proposal not found']); exit; }

        $review = advisoryReview($proposal);

        $panelLog = $db->prepare("SELECT * FROM advisory_panel_log WHERE proposal_id = ? ORDER BY created_at DESC");
        $panelLog->execute([$id]);

        echo json_encode([
            'proposal' => $proposal,
            'advisory_review' => $review,
            'panel_votes' => $panelLog->fetchAll(),
        ]);
        break;

    // ── Agent Report ──
    case 'agent_report':
        requireAuth();
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        
        $required = ['agent_id', 'agent_name', 'title', 'content'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Missing: $field"]);
                exit;
            }
        }

        $stmt = $db->prepare("INSERT INTO agent_reports 
            (agent_id, agent_name, report_type, title, content, severity, proposal_id, metrics, requires_attention)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            substr($input['agent_id'], 0, 64),
            substr($input['agent_name'], 0, 128),
            $input['report_type'] ?? 'status',
            substr($input['title'], 0, 256),
            $input['content'],
            $input['severity'] ?? 'info',
            $input['proposal_id'] ?? null,
            isset($input['metrics']) ? json_encode($input['metrics']) : null,
            (int)($input['requires_attention'] ?? 0),
        ]);

        echo json_encode(['success' => true, 'report_id' => $db->lastInsertId()]);
        break;

    // ── List Reports ──
    case 'reports':
        requireAuth();
        $type = $_GET['type'] ?? null;
        $severity = $_GET['severity'] ?? null;
        $unread = isset($_GET['unread']);
        $limit = min((int)($_GET['limit'] ?? 50), 200);

        $sql = "SELECT * FROM agent_reports WHERE 1=1";
        $params = [];
        if ($type) { $sql .= " AND report_type = ?"; $params[] = $type; }
        if ($severity) { $sql .= " AND severity = ?"; $params[] = $severity; }
        if ($unread) { $sql .= " AND requires_attention = 1 AND acknowledged = 0"; }
        $sql .= " ORDER BY FIELD(severity,'emergency','critical','warning','notice','info'), created_at DESC LIMIT ?";
        $params[] = $limit;

        $stmt = $db->prepare($sql);
        dbExecute($stmt, $params);

        echo json_encode([
            'reports' => $stmt->fetchAll(),
            'unread_count' => $db->query("SELECT COUNT(*) FROM agent_reports WHERE requires_attention = 1 AND acknowledged = 0")->fetchColumn(),
        ]);
        break;

    // ── Panel Brief (Daily) ──
    case 'panel_brief':
        requireAuth();
        
        // Gather stats
        $stats = [];
        $stats['pending_proposals'] = (int)$db->query("SELECT COUNT(*) FROM agent_proposals WHERE status IN ('pending','advisory_review')")->fetchColumn();
        $stats['approved_today'] = (int)$db->query("SELECT COUNT(*) FROM agent_proposals WHERE status = 'approved' AND DATE(approved_at) = CURDATE()")->fetchColumn();
        $stats['in_progress'] = (int)$db->query("SELECT COUNT(*) FROM agent_proposals WHERE status = 'in_progress'")->fetchColumn();
        $stats['completed_this_week'] = (int)$db->query("SELECT COUNT(*) FROM agent_proposals WHERE status = 'completed' AND completed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
        $stats['unread_reports'] = (int)$db->query("SELECT COUNT(*) FROM agent_reports WHERE requires_attention = 1 AND acknowledged = 0")->fetchColumn();
        $stats['critical_alerts'] = (int)$db->query("SELECT COUNT(*) FROM agent_reports WHERE severity IN ('critical','emergency') AND acknowledged = 0")->fetchColumn();
        $stats['total_agents'] = (int)$db->query("SELECT COUNT(*) FROM alfred_agent_registry WHERE status = 'active'")->fetchColumn();
        $stats['total_proposals_all_time'] = (int)$db->query("SELECT COUNT(*) FROM agent_proposals")->fetchColumn();

        // Recent proposals awaiting review  
        $pending = $db->query("SELECT id, agent_name, title, category, priority, estimated_cost, advisory_score, created_at 
            FROM agent_proposals WHERE status IN ('pending','advisory_review') 
            ORDER BY FIELD(priority,'urgent','critical','high','medium','low'), created_at DESC LIMIT 10")->fetchAll();

        // Recent critical reports
        $criticals = $db->query("SELECT id, agent_name, title, severity, created_at 
            FROM agent_reports WHERE severity IN ('critical','emergency') AND acknowledged = 0 
            ORDER BY created_at DESC LIMIT 5")->fetchAll();

        // Advisory recommendations
        $recommendations = [];
        if ($stats['pending_proposals'] > 10) {
            $recommendations[] = ['type' => 'warning', 'message' => "You have {$stats['pending_proposals']} proposals pending review. Consider batch-approving low-risk items."];
        }
        if ($stats['critical_alerts'] > 0) {
            $recommendations[] = ['type' => 'critical', 'message' => "{$stats['critical_alerts']} critical alert(s) require immediate attention."];
        }
        if ($stats['in_progress'] > 20) {
            $recommendations[] = ['type' => 'info', 'message' => "{$stats['in_progress']} projects in progress. Consider prioritizing completions before approving new work."];
        }
        $recommendations[] = ['type' => 'info', 'message' => "Advisory Panel recommends establishing @gositeme.com email service — centralizes user communication, increases trust, monetizable feature for premium users."];
        $recommendations[] = ['type' => 'info', 'message' => "Advisory Panel recommends expanding the monitoring agent fleet to cover: uptime, SEO, crawler health, security scanning, performance benchmarks, and user experience metrics."];

        echo json_encode([
            'briefing_date' => date('Y-m-d H:i:s'),
            'stats' => $stats,
            'pending_proposals' => $pending,
            'critical_alerts' => $criticals,
            'advisory_recommendations' => $recommendations,
            'panel_members' => [
                ['name' => 'Sage', 'role' => 'Strategic Advisor', 'status' => 'active'],
                ['name' => 'Sentinel', 'role' => 'Security Advisor', 'status' => 'active'],
                ['name' => 'Atlas', 'role' => 'Operations Advisor', 'status' => 'active'],
                ['name' => 'Nova', 'role' => 'Innovation Advisor', 'status' => 'active'],
                ['name' => 'Cipher', 'role' => 'Technical Advisor', 'status' => 'active'],
            ],
        ]);
        break;

    // ── Escalate ──
    case 'escalate':
        requireAuth();
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

        $stmt = $db->prepare("INSERT INTO agent_reports 
            (agent_id, agent_name, report_type, title, content, severity, requires_attention)
            VALUES (?, ?, 'escalation', ?, ?, ?, 1)");
        $stmt->execute([
            substr($input['agent_id'] ?? 'unknown', 0, 64),
            substr($input['agent_name'] ?? 'Unknown Agent', 0, 128),
            substr($input['title'] ?? 'Escalation', 0, 256),
            $input['content'] ?? 'No details provided',
            $input['severity'] ?? 'warning',
        ]);

        echo json_encode(['success' => true, 'report_id' => $db->lastInsertId(), 'message' => 'Escalated to owner']);
        break;

    // ── Permissions ──
    case 'permissions':
        requireAuth();
        $agentId = $_GET['agent_id'] ?? null;
        $sql = "SELECT * FROM agent_permissions WHERE active = 1";
        $params = [];
        if ($agentId) { $sql .= " AND agent_id = ?"; $params[] = $agentId; }
        $sql .= " ORDER BY agent_id, permission";
        $stmt = $db->prepare($sql);
        dbExecute($stmt, $params);
        echo json_encode(['permissions' => $stmt->fetchAll()]);
        break;

    // ── Grant Permission ──
    case 'grant_permission':
        requireOwner();
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $stmt = $db->prepare("INSERT INTO agent_permissions (agent_id, permission, scope, granted_by, requires_approval, max_cost_without_approval, expires_at)
            VALUES (?, ?, ?, 'Owner', ?, ?, ?) ON DUPLICATE KEY UPDATE scope = VALUES(scope), requires_approval = VALUES(requires_approval), max_cost_without_approval = VALUES(max_cost_without_approval), active = 1");
        $stmt->execute([
            $input['agent_id'],
            $input['permission'],
            $input['scope'] ?? '*',
            (int)($input['requires_approval'] ?? 1),
            (float)($input['max_cost_without_approval'] ?? 0),
            $input['expires_at'] ?? null,
        ]);
        echo json_encode(['success' => true]);
        break;

    // ── Stats ──
    case 'stats':
        requireAuth();
        echo json_encode([
            'proposals' => [
                'total' => (int)$db->query("SELECT COUNT(*) FROM agent_proposals")->fetchColumn(),
                'pending' => (int)$db->query("SELECT COUNT(*) FROM agent_proposals WHERE status IN ('pending','advisory_review')")->fetchColumn(),
                'approved' => (int)$db->query("SELECT COUNT(*) FROM agent_proposals WHERE status = 'approved'")->fetchColumn(),
                'in_progress' => (int)$db->query("SELECT COUNT(*) FROM agent_proposals WHERE status = 'in_progress'")->fetchColumn(),
                'completed' => (int)$db->query("SELECT COUNT(*) FROM agent_proposals WHERE status = 'completed'")->fetchColumn(),
                'rejected' => (int)$db->query("SELECT COUNT(*) FROM agent_proposals WHERE status = 'rejected'")->fetchColumn(),
            ],
            'reports' => [
                'total' => (int)$db->query("SELECT COUNT(*) FROM agent_reports")->fetchColumn(),
                'unread' => (int)$db->query("SELECT COUNT(*) FROM agent_reports WHERE requires_attention = 1 AND acknowledged = 0")->fetchColumn(),
                'critical' => (int)$db->query("SELECT COUNT(*) FROM agent_reports WHERE severity IN ('critical','emergency') AND acknowledged = 0")->fetchColumn(),
            ],
            'agents_with_permissions' => (int)$db->query("SELECT COUNT(DISTINCT agent_id) FROM agent_permissions WHERE active = 1")->fetchColumn(),
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode([
            'error' => 'Unknown action',
            'available_actions' => ['submit_proposal','proposals','approve','reject','advisory_review','agent_report','reports','panel_brief','escalate','permissions','grant_permission','stats'],
        ]);
}
