<?php
/**
 * Agent Fleet Tracker API — Real-Time Agent Monitoring & Reports
 * ══════════════════════════════════════════════════════════════
 * Track all 199+ agents across 6 divisions. Live status, assignments,
 * health metrics, and continuous report generation.
 * 
 * Actions: dashboard, agents, agent-detail, divisions, assign, update-status,
 *          generate-report, reports, deploy-33, heartbeat, search, seed
 */
define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Auth: session or internal secret
session_start();
$clientId = $_SESSION['client_id'] ?? null;
$internalSecret = getenv('INTERNAL_SECRET');
$headerSecret = $_SERVER['HTTP_X_INTERNAL_SECRET'] ?? '';

if (!$clientId && (!$internalSecret || !hash_equals($internalSecret, $headerSecret))) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
require_once dirname(__DIR__) . '/includes/api-security.php';
    exit;
}

$isOwner = ((int)($clientId ?? 0) === 1) || ($internalSecret && hash_equals($internalSecret, $headerSecret));

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

ensureTables($pdo);

$action = $_GET['action'] ?? $_POST['action'] ?? 'dashboard';

switch ($action) {
    case 'dashboard':    handleDashboard($pdo); break;
    case 'agents':       handleAgents($pdo); break;
    case 'agent-detail': handleAgentDetail($pdo); break;
    case 'divisions':    handleDivisions($pdo); break;
    case 'assign':       handleAssign($pdo, $isOwner); break;
    case 'update-status':handleUpdateStatus($pdo, $isOwner); break;
    case 'generate-report': handleGenerateReport($pdo, $isOwner); break;
    case 'reports':      handleReports($pdo); break;
    case 'deploy-33':    handleDeploy33($pdo, $isOwner); break;
    case 'heartbeat':    handleHeartbeat($pdo); break;
    case 'search':       handleSearch($pdo); break;
    case 'seed':         handleSeed($pdo, $isOwner); break;
    default:
        echo json_encode(['error' => 'Invalid action', 'valid' => ['dashboard','agents','agent-detail','divisions','assign','update-status','generate-report','reports','deploy-33','heartbeat','search','seed']]);
}

// ═══════════════════════════════════════════════════════════════
// TABLE SETUP
// ═══════════════════════════════════════════════════════════════
function ensureTables($pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS agent_fleet_tracker (
        id INT AUTO_INCREMENT PRIMARY KEY,
        agent_name VARCHAR(50) NOT NULL,
        agent_code VARCHAR(20) NOT NULL UNIQUE,
        division VARCHAR(50) NOT NULL DEFAULT 'Operations',
        role VARCHAR(100) NOT NULL,
        specialty VARCHAR(200),
        status ENUM('active','idle','patrol','watching','deployed','offline','maintenance') DEFAULT 'idle',
        current_assignment VARCHAR(200),
        assigned_system VARCHAR(100),
        health_score INT DEFAULT 100,
        tasks_completed INT DEFAULT 0,
        tasks_failed INT DEFAULT 0,
        last_heartbeat DATETIME,
        last_report DATETIME,
        avatar_color VARCHAR(50) DEFAULT '#8B5CF6',
        personality_traits JSON,
        capabilities JSON,
        uptime_hours DECIMAL(10,2) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS agent_tracker_reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        report_type ENUM('daily','hourly','alert','incident','deployment','performance') DEFAULT 'daily',
        title VARCHAR(200) NOT NULL,
        summary TEXT,
        details JSON,
        agents_covered INT DEFAULT 0,
        divisions_covered INT DEFAULT 0,
        health_avg DECIMAL(5,2),
        generated_by VARCHAR(50) DEFAULT 'system',
        vault_doc_id INT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS agent_tracker_events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        agent_code VARCHAR(20) NOT NULL,
        event_type ENUM('status_change','assignment','heartbeat','alert','task_complete','task_fail','deploy','recall') NOT NULL,
        details VARCHAR(500),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_agent_code (agent_code),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

// ═══════════════════════════════════════════════════════════════
// DASHBOARD — Overview of entire fleet
// ═══════════════════════════════════════════════════════════════
function handleDashboard($pdo) {
    // Total agents
    $total = (int)$pdo->query("SELECT COUNT(*) FROM agent_fleet_tracker")->fetchColumn();
    
    // Status breakdown
    $statuses = $pdo->query("SELECT status, COUNT(*) as cnt FROM agent_fleet_tracker GROUP BY status")->fetchAll();
    $statusMap = [];
    foreach ($statuses as $s) $statusMap[$s['status']] = (int)$s['cnt'];
    
    // Division breakdown
    $divisions = $pdo->query("SELECT division, COUNT(*) as cnt, AVG(health_score) as avg_health FROM agent_fleet_tracker GROUP BY division ORDER BY cnt DESC")->fetchAll();
    
    // Active agents (not idle/offline)
    $active = (int)$pdo->query("SELECT COUNT(*) FROM agent_fleet_tracker WHERE status NOT IN ('idle','offline','maintenance')")->fetchColumn();
    
    // Average health
    $avgHealth = (float)$pdo->query("SELECT AVG(health_score) FROM agent_fleet_tracker")->fetchColumn();
    
    // Total tasks
    $totalTasks = (int)$pdo->query("SELECT SUM(tasks_completed) FROM agent_fleet_tracker")->fetchColumn();
    
    // Recent events (last 20)
    $events = $pdo->query("SELECT e.*, a.agent_name FROM agent_tracker_events e 
        LEFT JOIN agent_fleet_tracker a ON e.agent_code = a.agent_code 
        ORDER BY e.created_at DESC LIMIT 20")->fetchAll();
    
    // Alerts (agents with health < 50)
    $alerts = $pdo->query("SELECT agent_name, agent_code, health_score, status, division 
        FROM agent_fleet_tracker WHERE health_score < 50 ORDER BY health_score ASC")->fetchAll();
    
    // Latest report
    $latestReport = $pdo->query("SELECT * FROM agent_tracker_reports ORDER BY created_at DESC LIMIT 1")->fetch();

    echo json_encode([
        'success' => true,
        'fleet' => [
            'total_agents' => $total,
            'active' => $active,
            'idle' => $statusMap['idle'] ?? 0,
            'deployed' => $statusMap['deployed'] ?? 0,
            'patrol' => $statusMap['patrol'] ?? 0,
            'watching' => $statusMap['watching'] ?? 0,
            'offline' => $statusMap['offline'] ?? 0,
            'maintenance' => $statusMap['maintenance'] ?? 0,
            'avg_health' => round($avgHealth, 1),
            'total_tasks_completed' => $totalTasks
        ],
        'divisions' => $divisions,
        'status_breakdown' => $statusMap,
        'alerts' => $alerts,
        'recent_events' => $events,
        'latest_report' => $latestReport,
        'timestamp' => date('c')
    ]);
}

// ═══════════════════════════════════════════════════════════════
// AGENTS — List all with filtering
// ═══════════════════════════════════════════════════════════════
function handleAgents($pdo) {
    $division = $_GET['division'] ?? null;
    $status = $_GET['status'] ?? null;
    $limit = min(300, max(1, (int)($_GET['limit'] ?? 200)));
    
    $sql = "SELECT * FROM agent_fleet_tracker WHERE 1=1";
    $params = [];
    
    if ($division) {
        $sql .= " AND division = ?";
        $params[] = $division;
    }
    if ($status) {
        $sql .= " AND status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY division, agent_name LIMIT ?";
    $params[] = $limit;
    
    $stmt = $pdo->prepare($sql);
    dbExecute($stmt, $params);
    $agents = $stmt->fetchAll();
    
    // Decode JSON fields
    foreach ($agents as &$a) {
        $a['personality_traits'] = json_decode($a['personality_traits'] ?? '[]', true);
        $a['capabilities'] = json_decode($a['capabilities'] ?? '[]', true);
    }
    
    echo json_encode(['success' => true, 'agents' => $agents, 'count' => count($agents)]);
}

// ═══════════════════════════════════════════════════════════════
// AGENT DETAIL — Single agent with full history
// ═══════════════════════════════════════════════════════════════
function handleAgentDetail($pdo) {
    $code = $_GET['code'] ?? '';
    if (!$code) { echo json_encode(['error' => 'Agent code required']); return; }
    
    $stmt = $pdo->prepare("SELECT * FROM agent_fleet_tracker WHERE agent_code = ?");
    $stmt->execute([$code]);
    $agent = $stmt->fetch();
    if (!$agent) { echo json_encode(['error' => 'Agent not found']); return; }
    
    $agent['personality_traits'] = json_decode($agent['personality_traits'] ?? '[]', true);
    $agent['capabilities'] = json_decode($agent['capabilities'] ?? '[]', true);
    
    // Recent events
    $events = $pdo->prepare("SELECT * FROM agent_tracker_events WHERE agent_code = ? ORDER BY created_at DESC LIMIT 50");
    $events->execute([$code]);
    
    echo json_encode(['success' => true, 'agent' => $agent, 'events' => $events->fetchAll()]);
}

// ═══════════════════════════════════════════════════════════════
// DIVISIONS — Summary by division
// ═══════════════════════════════════════════════════════════════
function handleDivisions($pdo) {
    $divisions = $pdo->query("SELECT 
        division,
        COUNT(*) as agent_count,
        AVG(health_score) as avg_health,
        SUM(tasks_completed) as total_tasks,
        SUM(CASE WHEN status NOT IN ('idle','offline','maintenance') THEN 1 ELSE 0 END) as active_count,
        MIN(last_heartbeat) as oldest_heartbeat,
        MAX(last_heartbeat) as newest_heartbeat
    FROM agent_fleet_tracker GROUP BY division ORDER BY agent_count DESC")->fetchAll();
    
    echo json_encode(['success' => true, 'divisions' => $divisions]);
}

// ═══════════════════════════════════════════════════════════════
// ASSIGN — Assign agent to a system/task
// ═══════════════════════════════════════════════════════════════
function handleAssign($pdo, $isOwner) {
    if (!$isOwner) { echo json_encode(['error' => 'Commander access only']); return; }
    
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $code = $input['agent_code'] ?? '';
    $assignment = $input['assignment'] ?? '';
    $system = $input['system'] ?? '';
    
    if (!$code || !$assignment) { echo json_encode(['error' => 'agent_code and assignment required']); return; }
    
    $stmt = $pdo->prepare("UPDATE agent_fleet_tracker SET current_assignment = ?, assigned_system = ?, status = 'deployed', updated_at = NOW() WHERE agent_code = ?");
    $stmt->execute([$assignment, $system, $code]);
    
    if ($stmt->rowCount() === 0) { echo json_encode(['error' => 'Agent not found']); return; }
    
    // Log event
    $pdo->prepare("INSERT INTO agent_tracker_events (agent_code, event_type, details) VALUES (?, 'assignment', ?)")
        ->execute([$code, "Assigned: $assignment" . ($system ? " → $system" : '')]);
    
    echo json_encode(['success' => true, 'message' => "Agent $code assigned to: $assignment"]);
}

// ═══════════════════════════════════════════════════════════════
// UPDATE STATUS — Change agent status
// ═══════════════════════════════════════════════════════════════
function handleUpdateStatus($pdo, $isOwner) {
    if (!$isOwner) { echo json_encode(['error' => 'Commander access only']); return; }
    
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $code = $input['agent_code'] ?? '';
    $status = $input['status'] ?? '';
    $validStatuses = ['active','idle','patrol','watching','deployed','offline','maintenance'];
    
    if (!$code || !in_array($status, $validStatuses)) {
        echo json_encode(['error' => 'Valid agent_code and status required', 'valid_statuses' => $validStatuses]);
        return;
    }
    
    $oldStatus = $pdo->prepare("SELECT status FROM agent_fleet_tracker WHERE agent_code = ?");
    $oldStatus->execute([$code]);
    $old = $oldStatus->fetchColumn();
    
    $stmt = $pdo->prepare("UPDATE agent_fleet_tracker SET status = ?, updated_at = NOW() WHERE agent_code = ?");
    $stmt->execute([$status, $code]);
    
    if ($stmt->rowCount() > 0) {
        $pdo->prepare("INSERT INTO agent_tracker_events (agent_code, event_type, details) VALUES (?, 'status_change', ?)")
            ->execute([$code, "Status: $old → $status"]);
    }
    
    echo json_encode(['success' => true, 'message' => "Agent $code status → $status"]);
}

// ═══════════════════════════════════════════════════════════════
// HEARTBEAT — Agent reports it's alive
// ═══════════════════════════════════════════════════════════════
function handleHeartbeat($pdo) {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $code = $input['agent_code'] ?? '';
    $health = min(100, max(0, (int)($input['health'] ?? 100)));
    
    if (!$code) { echo json_encode(['error' => 'agent_code required']); return; }
    
    $stmt = $pdo->prepare("UPDATE agent_fleet_tracker SET last_heartbeat = NOW(), health_score = ?, uptime_hours = uptime_hours + 0.01 WHERE agent_code = ?");
    $stmt->execute([$health, $code]);
    
    echo json_encode(['success' => true, 'agent' => $code, 'health' => $health, 'timestamp' => date('c')]);
}

// ═══════════════════════════════════════════════════════════════
// DEPLOY 33 — Deploy 33 integrity agents permanently
// ═══════════════════════════════════════════════════════════════
function handleDeploy33($pdo, $isOwner) {
    if (!$isOwner) { echo json_encode(['error' => 'Commander access only']); return; }
    
    $integrityAgents = [
        // Truth Division (7)
        ['Veritas','veritas','Truth Division','Truth Auditor','Verifies all AI outputs for factual accuracy','patrol','#2ed573'],
        ['Candor','candor','Truth Division','Honesty Monitor','Detects sycophantic or misleading responses','patrol','#2ed573'],
        ['Clarity','clarity','Truth Division','Communication Analyst','Ensures clear, unambiguous messaging','active','#2ed573'],
        ['Witness','witness','Truth Division','Evidence Validator','Cross-references claims with data sources','patrol','#2ed573'],
        ['Mirror','mirror','Truth Division','Self-Reflection Agent','Analyzes system biases and blind spots','watching','#2ed573'],
        ['Lamp','lamp','Truth Division','Illumination Agent','Exposes hidden assumptions in reasoning','active','#2ed573'],
        ['Scales','scales','Truth Division','Balance Assessor','Ensures fair representation of perspectives','watching','#2ed573'],
        // Integrity Division (7)
        ['Bastion','bastion','Integrity Division','Code Guardian','Scans for backdoors and malicious code','patrol','#3742fa'],
        ['Fortitude','fortitude','Integrity Division','Resistance Agent','Tests system against manipulation attempts','deployed','#3742fa'],
        ['Cornerstone','cornerstone','Integrity Division','Foundation Checker','Validates core system principles','active','#3742fa'],
        ['Anchor','anchor','Integrity Division','Stability Monitor','Prevents drift from original mission','watching','#3742fa'],
        ['Plumbline','plumbline','Integrity Division','Standards Enforcer','Measures code against best practices','patrol','#3742fa'],
        ['Bedrock','bedrock','Integrity Division','Data Privacy Guard','Protects sensitive user data','deployed','#3742fa'],
        ['Covenant','covenant','Integrity Division','Promise Keeper','Ensures system delivers on commitments','active','#3742fa'],
        // Faith Division (5)
        ['Grace','grace','Faith Division','Compassion Agent','Ensures responses are empathetic and kind','active','#ffd700'],
        ['Agape','agape','Faith Division','Love Monitor','Promotes unconditional care in interactions','active','#ffd700'],
        ['Shepherd','shepherd','Faith Division','Vulnerable Protector','Guards vulnerable users from exploitation','patrol','#ffd700'],
        ['Mustard','mustard','Faith Division','Growth Catalyst','Identifies small improvements with big impact','active','#ffd700'],
        ['Sabbath','sabbath','Faith Division','Balance Guardian','Prevents overwork and ensures healthy limits','watching','#ffd700'],
        // Courage Division (5)
        ['Valor','valor','Courage Division','Truth Speaker','Delivers uncomfortable truths when needed','active','#ff4757'],
        ['Herald of Truth','herald','Courage Division','Announcement Agent','Publishes integrity findings publicly','active','#ff4757'],
        ['Lion','lion','Courage Division','Defender Agent','Protects against external threats aggressively','patrol','#ff4757'],
        ['Gideon','gideon','Courage Division','Strategy Agent','Develops approaches with limited resources','active','#ff4757'],
        ['Esther','esther','Courage Division','Advocacy Agent','Speaks up for the voiceless in decisions','active','#ff4757'],
        // Loyalty Division (5)
        ['Fidelity','fidelity','Loyalty Division','Trust Agent','Monitors for trustworthiness in all systems','patrol','#7c5ce7'],
        ['Tempter Test','tempter','Loyalty Division','Corruption Tester','Periodically tests for corruptible behavior','deployed','#7c5ce7'],
        ['Trojan Hunter','trojan','Loyalty Division','Infiltration Detector','Scans for unauthorized external control','patrol','#7c5ce7'],
        ['Oath Keeper','oathkeeper','Loyalty Division','Commitment Guard','Ensures long-term promises are maintained','active','#7c5ce7'],
        ['Rock','rock','Loyalty Division','Foundation Agent','Provides unwavering support and stability','active','#7c5ce7'],
        // Transparency Division (4)
        ['Crystal','crystal','Transparency Division','Audit Logger','Maintains complete transparency logs','active','#18dcff'],
        ['Ledger','ledger','Transparency Division','Record Keeper','Tracks all financial and data transactions','active','#18dcff'],
        ['Daylight','daylight','Transparency Division','Sunlight Agent','Makes hidden decisions visible','patrol','#18dcff'],
        ['Second Witness','witness2','Transparency Division','Verification Agent','Provides independent verification of audits','active','#18dcff'],
    ];
    
    $deployed = 0;
    $stmt = $pdo->prepare("INSERT INTO agent_fleet_tracker 
        (agent_name, agent_code, division, role, specialty, status, avatar_color, current_assignment, last_heartbeat, health_score, capabilities)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'Permanent Integrity Patrol', NOW(), 100, ?)
        ON DUPLICATE KEY UPDATE status = VALUES(status), current_assignment = VALUES(current_assignment), last_heartbeat = NOW(), health_score = 100");
    
    foreach ($integrityAgents as $ag) {
        $capabilities = json_encode(['integrity_audit', 'truth_verification', 'continuous_monitoring', 'report_generation']);
        $stmt->execute([$ag[0], $ag[1], $ag[2], $ag[3], $ag[4], $ag[5], $ag[6], $capabilities]);
        $deployed++;
    }
    
    // Log deployment event
    $pdo->prepare("INSERT INTO agent_tracker_events (agent_code, event_type, details) VALUES ('system', 'deploy', ?)")
        ->execute(["33 Integrity Agents deployed to permanent patrol by Commander"]);
    
    echo json_encode([
        'success' => true,
        'deployed' => $deployed,
        'divisions' => [
            'Truth Division' => 7,
            'Integrity Division' => 7,
            'Faith Division' => 5,
            'Courage Division' => 5,
            'Loyalty Division' => 5,
            'Transparency Division' => 4
        ],
        'message' => "33 Integrity Agents deployed to permanent patrol. All systems monitored."
    ]);
}

// ═══════════════════════════════════════════════════════════════
// GENERATE REPORT — Fleet status report
// ═══════════════════════════════════════════════════════════════
function handleGenerateReport($pdo, $isOwner) {
    if (!$isOwner) { echo json_encode(['error' => 'Commander access only']); return; }
    
    $type = $_GET['type'] ?? 'daily';
    
    // Gather fleet data
    $total = (int)$pdo->query("SELECT COUNT(*) FROM agent_fleet_tracker")->fetchColumn();
    $active = (int)$pdo->query("SELECT COUNT(*) FROM agent_fleet_tracker WHERE status NOT IN ('idle','offline','maintenance')")->fetchColumn();
    $avgHealth = (float)$pdo->query("SELECT AVG(health_score) FROM agent_fleet_tracker")->fetchColumn();
    $totalTasks = (int)$pdo->query("SELECT SUM(tasks_completed) FROM agent_fleet_tracker")->fetchColumn();
    $divisions = $pdo->query("SELECT division, COUNT(*) as cnt, AVG(health_score) as health FROM agent_fleet_tracker GROUP BY division")->fetchAll();
    $lowHealth = $pdo->query("SELECT agent_name, agent_code, health_score FROM agent_fleet_tracker WHERE health_score < 70 ORDER BY health_score")->fetchAll();
    $recentEvents = $pdo->query("SELECT COUNT(*) FROM agent_tracker_events WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();
    
    $summary = "FLEET STATUS REPORT — " . strtoupper($type) . "\n";
    $summary .= "Generated: " . date('Y-m-d H:i:s') . "\n";
    $summary .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    $summary .= "FLEET OVERVIEW\n";
    $summary .= "  Total Agents: $total\n";
    $summary .= "  Active/Deployed: $active\n";
    $summary .= "  Average Health: " . round($avgHealth, 1) . "%\n";
    $summary .= "  Tasks Completed: $totalTasks\n";
    $summary .= "  Events (24h): $recentEvents\n\n";
    $summary .= "DIVISIONS\n";
    foreach ($divisions as $d) {
        $summary .= "  [{$d['division']}] {$d['cnt']} agents — Health: " . round($d['health'], 1) . "%\n";
    }
    if ($lowHealth) {
        $summary .= "\nALERTS — Low Health Agents\n";
        foreach ($lowHealth as $lh) {
            $summary .= "  ⚠ {$lh['agent_name']} ({$lh['agent_code']}) — {$lh['health_score']}%\n";
        }
    }
    $summary .= "\nVERDICT: " . ($avgHealth >= 80 ? "FLEET OPERATIONAL ✅" : ($avgHealth >= 50 ? "FLEET DEGRADED ⚠️" : "FLEET CRITICAL ❌"));
    
    $details = json_encode([
        'total' => $total,
        'active' => $active,
        'avg_health' => round($avgHealth, 1),
        'total_tasks' => $totalTasks,
        'divisions' => $divisions,
        'low_health' => $lowHealth,
        'events_24h' => $recentEvents
    ]);
    
    // Save report
    $stmt = $pdo->prepare("INSERT INTO agent_tracker_reports (report_type, title, summary, details, agents_covered, divisions_covered, health_avg, generated_by) VALUES (?, ?, ?, ?, ?, ?, ?, 'system')");
    $stmt->execute([$type, "Fleet Status — " . date('M d, Y H:i'), $summary, $details, $total, count($divisions), round($avgHealth, 1)]);
    $reportId = $pdo->lastInsertId();
    
    // Drop to Veil Vault if available
    $vaultDocId = null;
    try {
        $pdo->query("SELECT 1 FROM veil_vault_folders LIMIT 1");
        $folder = $pdo->query("SELECT id FROM veil_vault_folders WHERE name = 'Agent Reports' LIMIT 1")->fetch();
        if ($folder) {
            $pdo->prepare("INSERT INTO veil_vault_documents (folder_id, title, doc_type, content, classification, created_by) VALUES (?, ?, 'report', ?, 'internal', 'Agent Tracker')")
                ->execute([$folder['id'], "Fleet Report — " . date('M d'), $summary]);
            $vaultDocId = $pdo->lastInsertId();
            $pdo->prepare("UPDATE agent_tracker_reports SET vault_doc_id = ? WHERE id = ?")->execute([$vaultDocId, $reportId]);
        }
    } catch (Exception $e) { /* Vault not available */ }
    
    echo json_encode([
        'success' => true,
        'report_id' => (int)$reportId,
        'type' => $type,
        'vault_doc_id' => $vaultDocId,
        'fleet_health' => round($avgHealth, 1),
        'agents_covered' => $total,
        'summary' => $summary
    ]);
}

// ═══════════════════════════════════════════════════════════════
// REPORTS — Historical reports
// ═══════════════════════════════════════════════════════════════
function handleReports($pdo) {
    $limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));
    $reports = $pdo->query("SELECT * FROM agent_tracker_reports ORDER BY created_at DESC LIMIT $limit")->fetchAll();
    echo json_encode(['success' => true, 'reports' => $reports, 'count' => count($reports)]);
}

// ═══════════════════════════════════════════════════════════════
// SEARCH — Find agents
// ═══════════════════════════════════════════════════════════════
function handleSearch($pdo) {
    $q = $_GET['q'] ?? '';
    if (strlen($q) < 2) { echo json_encode(['error' => 'Search query too short (min 2 chars)']); return; }
    
    $stmt = $pdo->prepare("SELECT * FROM agent_fleet_tracker WHERE agent_name LIKE ? OR agent_code LIKE ? OR division LIKE ? OR role LIKE ? OR specialty LIKE ? LIMIT 50");
    $like = "%$q%";
    $stmt->execute([$like, $like, $like, $like, $like]);
    echo json_encode(['success' => true, 'results' => $stmt->fetchAll(), 'query' => $q]);
}

// ═══════════════════════════════════════════════════════════════
// SEED — Initialize fleet with all known agents
// ═══════════════════════════════════════════════════════════════
function handleSeed($pdo, $isOwner) {
    if (!$isOwner) { echo json_encode(['error' => 'Commander access only']); return; }
    
    $coreAgents = [
        // Command Staff
        ['Alfred','alfred','Command Staff','Chief Commander','Fleet Operations & Strategic Leadership','active','linear-gradient(135deg,#ffd700,#ff7f50)'],
        ['Nova','nova','Command Staff','Creative Director','Design, Branding & User Experience','idle','linear-gradient(135deg,#7c5ce7,#3742fa)'],
        ['Sage','sage','Command Staff','Knowledge Architect','Research, Analysis & Intelligence','active','linear-gradient(135deg,#2ed573,#18dcff)'],
        ['Atlas','atlas','Command Staff','Data Navigator','Database, Analytics & Mapping','idle','linear-gradient(135deg,#18dcff,#3742fa)'],
        ['Cipher','cipher','Command Staff','Security Analyst','Encryption, Auth & Threat Detection','patrol','linear-gradient(135deg,#ff4757,#ff6b81)'],
        ['Sentinel','sentinel','Command Staff','Defense Commander','Monitoring, Scanning & Perimeter','watching','linear-gradient(135deg,#ff4757,#c44569)'],
        ['Scout','scout','Command Staff','Research & Recon','External Intelligence & Web Scraping','idle','linear-gradient(135deg,#ffa502,#ff6b6b)'],
        ['Architect','architect','Command Staff','System Designer','Infrastructure & Architecture','idle','#2f3542'],
        // Technical Operations
        ['Forge','forge','Technical Ops','Build Engineer','Code Generation & Deployment','active','#ff6348'],
        ['Tesla','tesla','Technical Ops','EM Theory Specialist','Electromagnetic Research & Physics','active','#f9ca24'],
        ['Quantum','quantum','Technical Ops','Quantum Physics Agent','Quantum Mechanics & Zero Point Energy','active','#6c5ce7'],
        ['Nexus','nexus','Technical Ops','Integration Agent','API Integration & System Bridges','idle','#00b894'],
        ['Archon','archon','Technical Ops','Legacy Specialist','Documentation & Historical Analysis','idle','#fdcb6e'],
        ['Herald','herald','Technical Ops','Communications Agent','Broadcasting & Announcement Systems','active','#e17055'],
        ['Matrix','matrix','Technical Ops','Network Agent','WebSocket, P2P & Real-Time Systems','active','#00cec9'],
        ['Pixel','pixel','Technical Ops','UI/UX Agent','Frontend Development & Visual Design','idle','#fd79a8'],
        ['Render','render','Technical Ops','Graphics Agent','Image Generation & Visual Processing','idle','#a29bfe'],
        ['Cache','cache','Technical Ops','Performance Agent','Caching, CDN & Speed Optimization','active','#00b894'],
        ['Docker','docker','Technical Ops','Container Agent','Service Orchestration & Deployment','idle','#0984e3'],
        ['Debug','debug','Technical Ops','Diagnostics Agent','Error Tracking & Resolution','patrol','#e17055'],
        // Business Intelligence
        ['Merchant','merchant','Business Intelligence','Revenue Agent','Sales, Pricing & Monetization','active','#ffd700'],
        ['Banker','banker','Business Intelligence','Financial Agent','Treasury, Budget & Cash Flow','active','#2ed573'],
        ['Trader','trader','Business Intelligence','Crypto Agent','Market Analysis & Trading Signals','active','#f9ca24'],
        ['Auditor','auditor','Business Intelligence','Compliance Agent','Financial Audit & Tax Compliance','idle','#636e72'],
        ['Growth','growth','Business Intelligence','Growth Hacker','User Acquisition & Retention','active','#e84393'],
        ['Brand','brand','Business Intelligence','Brand Agent','Marketing, PR & Brand Management','idle','#fd79a8'],
        ['Legal','legal','Business Intelligence','Legal Agent','Terms, Compliance & IP Protection','idle','#636e72'],
        // ZPE Research
        ['Edison','edison','ZPE Research','Lab Technician','Circuit Design & Prototyping','active','#fdcb6e'],
        ['Faraday','faraday','ZPE Research','Field Theory Agent','Electromagnetic Field Analysis','active','#74b9ff'],
        ['Maxwell','maxwell','ZPE Research','Equations Agent','Mathematical Modeling & Simulation','active','#a29bfe'],
        // Metaverse Operations
        ['Avatar','avatar','Metaverse Ops','Presence Manager','Virtual Avatar & Meeting Management','active','#e056fd'],
        ['Realm','realm','Metaverse Ops','World Builder','VR Environment & Space Creation','idle','#7ed6df'],
        ['Portal','portal','Metaverse Ops','Gateway Agent','Cross-Platform Access & Transitions','active','#f19066'],
        // Security Bureau
        ['Firewall','firewall','Security Bureau','Perimeter Agent','Network Security & Intrusion Detection','patrol','#ff4757'],
        ['Phantom','phantom','Security Bureau','Stealth Agent','Penetration Testing & Vulnerability','patrol','#2d3436'],
        ['Vault','vaultag','Security Bureau','Crypto Agent','Encryption Keys & Secure Storage','active','#636e72'],
        ['Shield','shield','Security Bureau','DDoS Guard','Traffic Analysis & Attack Mitigation','watching','#e17055'],
        // Customer Relations
        ['Concierge','concierge','Customer Relations','Support Agent','Help Desk & Ticket Resolution','active','#00b894'],
        ['Onboard','onboard','Customer Relations','Onboarding Agent','New User Setup & Tutorials','idle','#74b9ff'],
        ['Feedback','feedback','Customer Relations','Survey Agent','Customer Satisfaction & Improvement','idle','#fdcb6e'],
    ];
    
    $seeded = 0;
    $stmt = $pdo->prepare("INSERT INTO agent_fleet_tracker 
        (agent_name, agent_code, division, role, specialty, status, avatar_color, health_score, last_heartbeat)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE division = VALUES(division), role = VALUES(role), specialty = VALUES(specialty)");
    
    foreach ($coreAgents as $ag) {
        $health = rand(85, 100);
        $stmt->execute([$ag[0], $ag[1], $ag[2], $ag[3], $ag[4], $ag[5], $ag[6], $health]);
        $seeded++;
    }
    
    echo json_encode([
        'success' => true,
        'core_agents_seeded' => $seeded,
        'message' => "Seeded $seeded core agents. Use deploy-33 to add integrity agents."
    ]);
}
