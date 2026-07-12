<?php
/**
 * EVOLVE MODE — Autonomous Agent Self-Improvement System
 * ══════════════════════════════════════════════════════
 * GoSiteMe Ecosystem — Star Trek-Grade Continuous Evolution
 * 
 * Orchestrates the agent fleet to autonomously detect issues,
 * propose improvements, execute approved changes, and verify results.
 * 
 * Pipeline: SCAN → ANALYZE → PROPOSE → APPROVE → EXECUTE → VERIFY → LOG
 * 
 * API Endpoints:
 *   GET  ?action=status        — Current Evolve Mode status
 *   POST ?action=activate      — Activate Evolve Mode
 *   POST ?action=deactivate    — Deactivate Evolve Mode
 *   GET  ?action=proposals     — List improvement proposals
 *   POST ?action=approve       — Approve a proposal
 *   POST ?action=reject        — Reject a proposal
 *   POST ?action=scan          — Trigger a manual evolution scan
 *   GET  ?action=history       — Evolution history log
 *   GET  ?action=metrics       — Evolution performance metrics
 */

if (!defined('GOSITEME_API')) define('GOSITEME_API', true);
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';
session_start();

$clientId = $_SESSION['client_id'] ?? null;
$isOwner  = (int)$clientId === 33;

// Evolve Mode is Commander-only
$isInternal = (isset($_SERVER['HTTP_X_INTERNAL_SECRET']) && $_SERVER['HTTP_X_INTERNAL_SECRET'] === (getenv('INTERNAL_SECRET') ?: ''));

if (!$clientId && !$isInternal) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

if (!$isOwner && !$isInternal) {
    http_response_code(403);
    echo json_encode(['error' => 'Evolve Mode requires Commander access']);
    exit;
}

try {
    $db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// ── Schema ──────────────────────────────────────────────
$db->exec("CREATE TABLE IF NOT EXISTS evolve_config (
    id INT PRIMARY KEY DEFAULT 1,
    active TINYINT(1) DEFAULT 0,
    mode ENUM('supervised','autonomous','conservative') DEFAULT 'supervised',
    scan_interval_minutes INT DEFAULT 60,
    auto_approve_confidence DECIMAL(3,2) DEFAULT 0.95,
    max_daily_changes INT DEFAULT 10,
    last_scan_at DATETIME DEFAULT NULL,
    activated_at DATETIME DEFAULT NULL,
    activated_by INT DEFAULT NULL,
    changes_today INT DEFAULT 0,
    changes_today_date DATE DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS evolve_proposals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scan_id VARCHAR(36),
    category ENUM('performance','security','ux','reliability','feature','maintenance','accessibility') NOT NULL,
    severity ENUM('info','low','medium','high','critical') DEFAULT 'medium',
    title VARCHAR(500) NOT NULL,
    description TEXT,
    affected_files TEXT,
    proposed_action TEXT NOT NULL,
    agent_id VARCHAR(50) DEFAULT 'alfred',
    confidence DECIMAL(3,2) DEFAULT 0.50,
    estimated_impact VARCHAR(200),
    risk_level ENUM('none','low','medium','high') DEFAULT 'low',
    status ENUM('pending','approved','rejected','executing','completed','failed','rolled_back') DEFAULT 'pending',
    approved_by INT DEFAULT NULL,
    approved_at DATETIME DEFAULT NULL,
    executed_at DATETIME DEFAULT NULL,
    execution_result TEXT,
    verification_status ENUM('pending','passed','failed') DEFAULT 'pending',
    verification_details TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_category (category),
    INDEX idx_scan (scan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS evolve_scans (
    id VARCHAR(36) PRIMARY KEY,
    scan_type ENUM('scheduled','manual','triggered') DEFAULT 'manual',
    status ENUM('running','completed','failed') DEFAULT 'running',
    findings_count INT DEFAULT 0,
    proposals_generated INT DEFAULT 0,
    scan_data JSON,
    duration_ms INT DEFAULT NULL,
    started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME DEFAULT NULL,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS evolve_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proposal_id INT,
    action VARCHAR(100),
    details TEXT,
    agent_id VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_proposal (proposal_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Ensure config row exists
$db->exec("INSERT IGNORE INTO evolve_config (id) VALUES (1)");

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

switch ($action) {
    case 'status':     handleStatus($db); break;
    case 'activate':   handleActivate($db, $clientId); break;
    case 'deactivate': handleDeactivate($db); break;
    case 'proposals':  handleProposals($db); break;
    case 'approve':    handleApprove($db, $clientId); break;
    case 'reject':     handleReject($db, $clientId); break;
    case 'scan':       handleScan($db); break;
    case 'history':    handleHistory($db); break;
    case 'metrics':    handleMetrics($db); break;
    default:
        echo json_encode(['error' => 'Unknown action', 'available' => ['status','activate','deactivate','proposals','approve','reject','scan','history','metrics']]);
}

// ═══════════════════════════════════════════════════════
// STATUS — Current Evolve Mode state
// ═══════════════════════════════════════════════════════
function handleStatus(PDO $db): void {
    $config = $db->query("SELECT * FROM evolve_config WHERE id = 1")->fetch();

    // Reset daily counter if new day
    if ($config['changes_today_date'] !== date('Y-m-d')) {
        $db->exec("UPDATE evolve_config SET changes_today = 0, changes_today_date = '" . date('Y-m-d') . "' WHERE id = 1");
        $config['changes_today'] = 0;
    }

    $pending   = $db->query("SELECT COUNT(*) FROM evolve_proposals WHERE status = 'pending'")->fetchColumn();
    $completed = $db->query("SELECT COUNT(*) FROM evolve_proposals WHERE status = 'completed'")->fetchColumn();
    $failed    = $db->query("SELECT COUNT(*) FROM evolve_proposals WHERE status = 'failed'")->fetchColumn();
    $lastScan  = $db->query("SELECT * FROM evolve_scans ORDER BY started_at DESC LIMIT 1")->fetch();

    echo json_encode([
        'evolve_mode' => [
            'active'       => (bool)$config['active'],
            'mode'         => $config['mode'],
            'activated_at' => $config['activated_at'],
            'scan_interval_minutes' => (int)$config['scan_interval_minutes'],
            'auto_approve_confidence' => (float)$config['auto_approve_confidence'],
            'max_daily_changes' => (int)$config['max_daily_changes'],
            'changes_today' => (int)$config['changes_today'],
        ],
        'proposals' => [
            'pending'   => (int)$pending,
            'completed' => (int)$completed,
            'failed'    => (int)$failed,
        ],
        'last_scan' => $lastScan ?: null,
    ]);
}

// ═══════════════════════════════════════════════════════
// ACTIVATE — Turn on Evolve Mode
// ═══════════════════════════════════════════════════════
function handleActivate(PDO $db, int $clientId): void {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $mode  = in_array($input['mode'] ?? '', ['supervised','autonomous','conservative'])
        ? $input['mode'] : 'supervised';

    $db->prepare("UPDATE evolve_config SET active = 1, mode = ?, activated_at = NOW(), activated_by = ?, scan_interval_minutes = ?, auto_approve_confidence = ?, max_daily_changes = ? WHERE id = 1")->execute([
        $mode,
        $clientId,
        min(1440, max(5, (int)($input['scan_interval'] ?? 60))),
        min(1.00, max(0.50, (float)($input['auto_approve_confidence'] ?? 0.95))),
        min(50, max(1, (int)($input['max_daily_changes'] ?? 10))),
    ]);

    logEvolve($db, null, 'evolve_activated', "Mode: $mode", 'alfred');
    echo json_encode(['success' => true, 'mode' => $mode, 'message' => "Evolve Mode activated in $mode mode"]);
}

// ═══════════════════════════════════════════════════════
// DEACTIVATE — Turn off Evolve Mode
// ═══════════════════════════════════════════════════════
function handleDeactivate(PDO $db): void {
    $db->exec("UPDATE evolve_config SET active = 0 WHERE id = 1");
    logEvolve($db, null, 'evolve_deactivated', 'Manually deactivated', 'alfred');
    echo json_encode(['success' => true, 'message' => 'Evolve Mode deactivated']);
}

// ═══════════════════════════════════════════════════════
// SCAN — Run an evolution scan
// ═══════════════════════════════════════════════════════
function handleScan(PDO $db): void {
    $scanId = bin2hex(random_bytes(16));
    $start  = microtime(true);

    $db->prepare("INSERT INTO evolve_scans (id, scan_type, status) VALUES (?, 'manual', 'running')")->execute([$scanId]);

    $findings = [];
    $proposals = [];

    // ── SCOUT: Performance scan ──
    $findings['performance'] = scanPerformance($db);

    // ── SENTINEL: Security scan ──
    $findings['security'] = scanSecurity($db);

    // ── CIPHER: Analytics scan ──
    $findings['analytics'] = scanAnalytics($db);

    // ── NEXUS: Infrastructure scan ──
    $findings['infrastructure'] = scanInfrastructure($db);

    // ── FORGE: Code quality scan ──
    $findings['code_quality'] = scanCodeQuality();

    // ── PULSE: User experience scan ──
    $findings['user_experience'] = scanUserExperience($db);

    // Generate proposals from findings
    foreach ($findings as $category => $items) {
        foreach ($items as $finding) {
            if (!empty($finding['actionable'])) {
                $stmtP = $db->prepare("INSERT INTO evolve_proposals (scan_id, category, severity, title, description, affected_files, proposed_action, agent_id, confidence, estimated_impact, risk_level) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmtP->execute([
                    $scanId,
                    $finding['category'] ?? $category,
                    $finding['severity'] ?? 'medium',
                    substr($finding['title'], 0, 500),
                    $finding['description'] ?? '',
                    $finding['affected_files'] ?? '',
                    $finding['action'],
                    $finding['agent'] ?? 'forge',
                    $finding['confidence'] ?? 0.7,
                    $finding['impact'] ?? 'Moderate improvement',
                    $finding['risk'] ?? 'low',
                ]);
                $proposals[] = $finding['title'];
            }
        }
    }

    $durationMs = (int)((microtime(true) - $start) * 1000);
    $totalFindings = array_sum(array_map('count', $findings));

    $db->prepare("UPDATE evolve_scans SET status = 'completed', findings_count = ?, proposals_generated = ?, scan_data = ?, duration_ms = ?, completed_at = NOW() WHERE id = ?")->execute([
        $totalFindings,
        count($proposals),
        json_encode($findings),
        $durationMs,
        $scanId,
    ]);

    $db->exec("UPDATE evolve_config SET last_scan_at = NOW() WHERE id = 1");
    logEvolve($db, null, 'scan_completed', "Scan $scanId: $totalFindings findings, " . count($proposals) . " proposals", 'scout');

    // Auto-approve high-confidence proposals in autonomous mode
    $config = $db->query("SELECT * FROM evolve_config WHERE id = 1")->fetch();
    $autoApproved = 0;
    if ($config['active'] && $config['mode'] === 'autonomous') {
        $threshold = (float)$config['auto_approve_confidence'];
        $stmt = $db->prepare("SELECT id, title, confidence FROM evolve_proposals WHERE scan_id = ? AND status = 'pending' AND confidence >= ? AND risk_level IN ('none','low')");
        $stmt->execute([$scanId, $threshold]);
        foreach ($stmt->fetchAll() as $p) {
            $db->prepare("UPDATE evolve_proposals SET status = 'approved', approved_by = 0, approved_at = NOW() WHERE id = ?")->execute([$p['id']]);
            logEvolve($db, $p['id'], 'auto_approved', "Confidence {$p['confidence']} >= threshold $threshold", 'alfred');
            $autoApproved++;
        }
    }

    echo json_encode([
        'success'    => true,
        'scan_id'    => $scanId,
        'duration_ms' => $durationMs,
        'findings'   => $totalFindings,
        'proposals'  => count($proposals),
        'auto_approved' => $autoApproved,
        'categories' => array_map('count', $findings),
    ]);
}

// ═══════════════════════════════════════════════════════
// APPROVE — Approve a proposal for execution
// ═══════════════════════════════════════════════════════
function handleApprove(PDO $db, int $clientId): void {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($input['proposal_id'] ?? 0);
    if (!$id) { echo json_encode(['error' => 'proposal_id required']); return; }

    $proposal = $db->prepare("SELECT * FROM evolve_proposals WHERE id = ?");
    $proposal->execute([$id]);
    $p = $proposal->fetch();
    if (!$p) { echo json_encode(['error' => 'Proposal not found']); return; }
    if ($p['status'] !== 'pending') { echo json_encode(['error' => 'Proposal is not pending', 'status' => $p['status']]); return; }

    // Check daily limit
    $config = $db->query("SELECT * FROM evolve_config WHERE id = 1")->fetch();
    if ((int)$config['changes_today'] >= (int)$config['max_daily_changes']) {
        echo json_encode(['error' => 'Daily change limit reached', 'limit' => (int)$config['max_daily_changes']]);
        return;
    }

    $db->prepare("UPDATE evolve_proposals SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?")->execute([$clientId, $id]);
    logEvolve($db, $id, 'approved', "Approved by client #$clientId", 'alfred');

    echo json_encode(['success' => true, 'proposal_id' => $id, 'message' => "Proposal #{$id} approved"]);
}

// ═══════════════════════════════════════════════════════
// REJECT — Reject a proposal
// ═══════════════════════════════════════════════════════
function handleReject(PDO $db, int $clientId): void {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($input['proposal_id'] ?? 0);
    $reason = substr($input['reason'] ?? 'No reason provided', 0, 1000);
    if (!$id) { echo json_encode(['error' => 'proposal_id required']); return; }

    $db->prepare("UPDATE evolve_proposals SET status = 'rejected' WHERE id = ? AND status = 'pending'")->execute([$id]);
    logEvolve($db, $id, 'rejected', "Reason: $reason", 'alfred');

    echo json_encode(['success' => true, 'proposal_id' => $id, 'message' => 'Proposal rejected']);
}

// ═══════════════════════════════════════════════════════
// PROPOSALS — List proposals
// ═══════════════════════════════════════════════════════
function handleProposals(PDO $db): void {
    $status = $_GET['status'] ?? 'all';
    $limit  = min(100, max(1, (int)($_GET['limit'] ?? 50)));

    $sql = "SELECT * FROM evolve_proposals";
    $params = [];
    $validStatuses = ['pending','approved','rejected','executing','completed','failed','rolled_back'];
    if ($status !== 'all' && in_array($status, $validStatuses)) {
        $sql .= " WHERE status = ?";
        $params[] = $status;
    }
    $sql .= " ORDER BY created_at DESC LIMIT $limit";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    echo json_encode(['proposals' => $stmt->fetchAll()]);
}

// ═══════════════════════════════════════════════════════
// HISTORY — Evolution history log
// ═══════════════════════════════════════════════════════
function handleHistory(PDO $db): void {
    $limit = min(200, max(1, (int)($_GET['limit'] ?? 50)));
    $stmt = $db->query("SELECT * FROM evolve_history ORDER BY created_at DESC LIMIT $limit");
    echo json_encode(['history' => $stmt->fetchAll()]);
}

// ═══════════════════════════════════════════════════════
// METRICS — Evolution performance metrics
// ═══════════════════════════════════════════════════════
function handleMetrics(PDO $db): void {
    $total     = (int)$db->query("SELECT COUNT(*) FROM evolve_proposals")->fetchColumn();
    $completed = (int)$db->query("SELECT COUNT(*) FROM evolve_proposals WHERE status='completed'")->fetchColumn();
    $failed    = (int)$db->query("SELECT COUNT(*) FROM evolve_proposals WHERE status='failed'")->fetchColumn();
    $pending   = (int)$db->query("SELECT COUNT(*) FROM evolve_proposals WHERE status='pending'")->fetchColumn();
    $avgConf   = (float)$db->query("SELECT COALESCE(AVG(confidence), 0) FROM evolve_proposals WHERE status='completed'")->fetchColumn();

    $byCategory = $db->query("SELECT category, COUNT(*) as count, SUM(status='completed') as completed FROM evolve_proposals GROUP BY category")->fetchAll();
    $bySeverity = $db->query("SELECT severity, COUNT(*) as count FROM evolve_proposals GROUP BY severity")->fetchAll();
    $scanCount  = (int)$db->query("SELECT COUNT(*) FROM evolve_scans")->fetchColumn();
    $avgDuration = (int)$db->query("SELECT COALESCE(AVG(duration_ms), 0) FROM evolve_scans WHERE status='completed'")->fetchColumn();

    echo json_encode([
        'total_proposals' => $total,
        'completed'       => $completed,
        'failed'          => $failed,
        'pending'         => $pending,
        'success_rate'    => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
        'avg_confidence'  => round($avgConf, 3),
        'by_category'     => $byCategory,
        'by_severity'     => $bySeverity,
        'scans' => [
            'total'        => $scanCount,
            'avg_duration_ms' => $avgDuration,
        ],
    ]);
}

// ═══════════════════════════════════════════════════════
// SCAN MODULES — Each agent scans their domain
// ═══════════════════════════════════════════════════════

function scanPerformance(PDO $db): array {
    $findings = [];
    $docRoot = dirname(__DIR__);

    // Check for large PHP files that might need splitting
    $largeFiles = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($docRoot, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    foreach ($iterator as $file) {
        if ($file->getExtension() === 'php' && $file->getSize() > 200000) { // >200KB
            $path = str_replace($docRoot . '/', '', $file->getPathname());
            // Skip known large vendored files
            if (strpos($path, 'gocodeme-editor/') !== false || strpos($path, 'node_modules/') !== false || strpos($path, 'wp-content/') !== false) continue;
            $largeFiles[] = $path . ' (' . round($file->getSize() / 1024) . 'KB)';
        }
    }
    if (!empty($largeFiles)) {
        $findings[] = [
            'title' => 'Large PHP files detected — consider modularization',
            'description' => 'Files over 200KB may benefit from splitting into smaller modules for maintainability: ' . implode(', ', array_slice($largeFiles, 0, 10)),
            'category' => 'maintenance',
            'severity' => 'low',
            'actionable' => true,
            'action' => 'Review and modularize large files: ' . implode(', ', array_slice($largeFiles, 0, 5)),
            'agent' => 'forge',
            'confidence' => 0.6,
            'impact' => 'Improved maintainability',
            'risk' => 'low',
            'affected_files' => implode("\n", array_slice($largeFiles, 0, 10)),
        ];
    }

    // Check database table optimization needs
    try {
        $tables = $db->query("SELECT TABLE_NAME, DATA_LENGTH, INDEX_LENGTH, DATA_FREE FROM information_schema.TABLES WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND DATA_FREE > 1048576 ORDER BY DATA_FREE DESC LIMIT 5")->fetchAll();
        if (!empty($tables)) {
            $tableList = array_map(fn($t) => $t['TABLE_NAME'] . ' (' . round($t['DATA_FREE'] / 1048576, 1) . 'MB free)', $tables);
            $findings[] = [
                'title' => 'Database tables have reclaimable space',
                'description' => 'These tables have fragmented space that can be reclaimed: ' . implode(', ', $tableList),
                'category' => 'performance',
                'severity' => 'low',
                'actionable' => true,
                'action' => 'Run OPTIMIZE TABLE on fragmented tables: ' . implode(', ', array_column($tables, 'TABLE_NAME')),
                'agent' => 'nexus',
                'confidence' => 0.9,
                'impact' => 'Reduced storage, faster queries',
                'risk' => 'none',
                'affected_files' => 'database',
            ];
        }
    } catch (Exception $e) { /* non-critical */ }

    // Check for missing indexes on large tables
    try {
        $largeTables = $db->query("SELECT TABLE_NAME, TABLE_ROWS FROM information_schema.TABLES WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_ROWS > 10000 ORDER BY TABLE_ROWS DESC LIMIT 10")->fetchAll();
        foreach ($largeTables as $table) {
            $indexes = $db->query("SHOW INDEX FROM `{$table['TABLE_NAME']}`")->fetchAll();
            if (count($indexes) <= 1) {
                $findings[] = [
                    'title' => "Table {$table['TABLE_NAME']} has {$table['TABLE_ROWS']}+ rows but minimal indexes",
                    'description' => "Consider adding indexes to improve query performance",
                    'category' => 'performance',
                    'severity' => 'medium',
                    'actionable' => true,
                    'action' => "Analyze query patterns on {$table['TABLE_NAME']} and add appropriate indexes",
                    'agent' => 'nexus',
                    'confidence' => 0.7,
                    'impact' => 'Faster database queries',
                    'risk' => 'low',
                    'affected_files' => 'database: ' . $table['TABLE_NAME'],
                ];
            }
        }
    } catch (Exception $e) { /* non-critical */ }

    return $findings;
}

function scanSecurity(PDO $db): array {
    $findings = [];
    $docRoot = dirname(__DIR__);

    // Check SSL certificate expiry
    $certInfo = @openssl_x509_parse(@file_get_contents("https://gositeme.com", false, stream_context_create(['ssl' => ['capture_peer_cert' => true], 'http' => ['timeout' => 5]])));
    // Alternative: check via shell
    $sslDays = null;
    $sslOutput = @shell_exec("echo | openssl s_client -connect gositeme.com:443 -servername gositeme.com 2>/dev/null | openssl x509 -noout -enddate 2>/dev/null");
    if ($sslOutput && preg_match('/notAfter=(.+)/', $sslOutput, $m)) {
        $expiry = strtotime(trim($m[1]));
        $sslDays = (int)(($expiry - time()) / 86400);
        if ($sslDays < 30) {
            $findings[] = [
                'title' => "SSL certificate expires in $sslDays days",
                'description' => "Certificate needs renewal before " . date('Y-m-d', $expiry),
                'category' => 'security',
                'severity' => $sslDays < 7 ? 'critical' : 'high',
                'actionable' => true,
                'action' => 'Renew SSL certificate via DirectAdmin or Let\'s Encrypt',
                'agent' => 'sentinel',
                'confidence' => 0.99,
                'impact' => 'Prevent site downtime from expired SSL',
                'risk' => 'none',
            ];
        }
    }

    // Check for PHP files in public directories without auth
    $publicDirs = ['downloads', 'cache', 'logs'];
    foreach ($publicDirs as $dir) {
        $dirPath = $docRoot . '/' . $dir;
        if (is_dir($dirPath)) {
            $htaccess = $dirPath . '/.htaccess';
            if (!file_exists($htaccess)) {
                $findings[] = [
                    'title' => "Directory /$dir/ lacks .htaccess protection",
                    'description' => "The /$dir/ directory should have access controls to prevent unauthorized browsing",
                    'category' => 'security',
                    'severity' => 'medium',
                    'actionable' => true,
                    'action' => "Add .htaccess with 'Options -Indexes' and appropriate access rules to /$dir/",
                    'agent' => 'sentinel',
                    'confidence' => 0.85,
                    'impact' => 'Prevent directory listing and unauthorized access',
                    'risk' => 'low',
                    'affected_files' => "$dir/.htaccess",
                ];
            }
        }
    }

    // Check for error agent count
    try {
        $errorAgents = (int)$db->query("SELECT COUNT(*) FROM alfred_agent_registry WHERE status = 'error'")->fetchColumn();
        if ($errorAgents > 0) {
            $findings[] = [
                'title' => "$errorAgents agent(s) in error state",
                'description' => 'Agents in error state cannot process tasks and may indicate underlying issues',
                'category' => 'reliability',
                'severity' => 'high',
                'actionable' => true,
                'action' => 'Reset error agents and investigate root cause. Run: UPDATE alfred_agent_registry SET status=\'idle\' WHERE status=\'error\'',
                'agent' => 'sentinel',
                'confidence' => 0.95,
                'impact' => 'Restore agent fleet to full capacity',
                'risk' => 'none',
            ];
        }
    } catch (Exception $e) { /* non-critical */ }

    return $findings;
}

function scanAnalytics(PDO $db): array {
    $findings = [];

    // Check for failed tasks in the last 24 hours
    try {
        $failedTasks = $db->query("SELECT COUNT(*) FROM alfred_agent_tasks WHERE status = 'failed' AND updated_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();
        if ((int)$failedTasks > 5) {
            $topErrors = $db->query("SELECT error_message, COUNT(*) as cnt FROM alfred_agent_tasks WHERE status = 'failed' AND updated_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) GROUP BY error_message ORDER BY cnt DESC LIMIT 3")->fetchAll();
            $errorSummary = array_map(fn($e) => "{$e['cnt']}x: " . substr($e['error_message'] ?? 'unknown', 0, 100), $topErrors);
            $findings[] = [
                'title' => "$failedTasks tasks failed in the last 24 hours",
                'description' => 'Top errors: ' . implode('; ', $errorSummary),
                'category' => 'reliability',
                'severity' => 'high',
                'actionable' => true,
                'action' => 'Investigate recurring task failures: ' . implode('; ', $errorSummary),
                'agent' => 'cipher',
                'confidence' => 0.8,
                'impact' => 'Improve task success rate',
                'risk' => 'low',
            ];
        }
    } catch (Exception $e) { /* non-critical */ }

    // Check agent utilization
    try {
        $agentStats = $db->query("SELECT status, COUNT(*) as cnt FROM alfred_agent_registry GROUP BY status")->fetchAll();
        $statusMap = [];
        foreach ($agentStats as $s) $statusMap[$s['status']] = (int)$s['cnt'];
        $total = array_sum($statusMap);
        $idle = $statusMap['idle'] ?? 0;

        if ($total > 0 && ($idle / $total) > 0.95) {
            $findings[] = [
                'title' => 'Agent fleet underutilized — ' . round(($idle / $total) * 100) . '% idle',
                'description' => "$idle of $total agents are idle. Consider automated task generation or proactive scanning.",
                'category' => 'performance',
                'severity' => 'info',
                'actionable' => false,
            ];
        }
    } catch (Exception $e) { /* non-critical */ }

    return $findings;
}

function scanInfrastructure(PDO $db): array {
    $findings = [];

    // Check service ports
    $services = [
        ['Redis', 6379], ['WebSocket', 3010], ['Jobs', 3011],
        ['MCP', 3005], ['Middleware', 3001], ['MeiliSearch', 7700],
        ['LiveKit', 7880],
    ];

    foreach ($services as [$name, $port]) {
        $fp = @fsockopen('127.0.0.1', $port, $errno, $errstr, 2);
        if (!$fp) {
            $findings[] = [
                'title' => "$name service (port $port) is DOWN",
                'description' => "Cannot connect to $name on port $port: $errstr",
                'category' => 'reliability',
                'severity' => 'critical',
                'actionable' => true,
                'action' => "Restart $name service: pm2 restart <process-name> for port $port",
                'agent' => 'nexus',
                'confidence' => 0.99,
                'impact' => "Restore $name functionality",
                'risk' => 'low',
            ];
        } else {
            fclose($fp);
        }
    }

    // Check disk space
    $totalDisk = @disk_total_space('/');
    $freeDisk  = @disk_free_space('/');
    if ($totalDisk && $freeDisk) {
        $usedPct = round((1 - $freeDisk / $totalDisk) * 100, 1);
        if ($usedPct > 85) {
            $findings[] = [
                'title' => "Disk usage at {$usedPct}%",
                'description' => 'Free: ' . round($freeDisk / 1073741824, 1) . 'GB / Total: ' . round($totalDisk / 1073741824, 1) . 'GB',
                'category' => 'reliability',
                'severity' => $usedPct > 95 ? 'critical' : 'high',
                'actionable' => true,
                'action' => 'Clean up old logs, cache files, and temporary data. Check /logs/ and /cache/ directories.',
                'agent' => 'nexus',
                'confidence' => 0.95,
                'impact' => 'Prevent disk-full outage',
                'risk' => 'low',
            ];
        }
    }

    // Check Ollama availability (local AI — last resort)
    $ollamaCheck = @file_get_contents('http://127.0.0.1:11434/api/tags', false, stream_context_create(['http' => ['timeout' => 3]]));
    if (!$ollamaCheck) {
        $findings[] = [
            'title' => 'Ollama local AI is not responding',
            'description' => 'The last-resort local AI on port 11434 is down. This breaks the zero-downtime guarantee.',
            'category' => 'reliability',
            'severity' => 'high',
            'actionable' => true,
            'action' => 'Restart Ollama: systemctl --user restart ollama',
            'agent' => 'nexus',
            'confidence' => 0.99,
            'impact' => 'Restore zero-downtime AI guarantee',
            'risk' => 'none',
        ];
    }

    return $findings;
}

function scanCodeQuality(): array {
    $findings = [];
    $docRoot = dirname(__DIR__);

    // Check for PHP syntax errors in recently modified files
    $recentFiles = [];
    $cutoff = time() - 86400; // last 24 hours
    $apiDir = $docRoot . '/api';
    if (is_dir($apiDir)) {
        foreach (new DirectoryIterator($apiDir) as $file) {
            if ($file->isDot() || $file->getExtension() !== 'php') continue;
            if ($file->getMTime() > $cutoff) {
                $recentFiles[] = $file->getPathname();
            }
        }
    }

    foreach ($recentFiles as $file) {
        $output = [];
        $returnVar = 0;
        exec('php -l ' . escapeshellarg($file) . ' 2>&1', $output, $returnVar);
        if ($returnVar !== 0) {
            $relPath = str_replace($docRoot . '/', '', $file);
            $findings[] = [
                'title' => "PHP syntax error in $relPath",
                'description' => implode("\n", $output),
                'category' => 'reliability',
                'severity' => 'critical',
                'actionable' => true,
                'action' => "Fix syntax error in $relPath: " . implode(' ', $output),
                'agent' => 'forge',
                'confidence' => 0.99,
                'impact' => 'Fix broken page/API',
                'risk' => 'low',
                'affected_files' => $relPath,
            ];
        }
    }

    // Check for TODO/FIXME in recently modified files
    $todoCount = 0;
    foreach ($recentFiles as $file) {
        $content = @file_get_contents($file);
        if ($content) {
            $todoCount += preg_match_all('/\b(TODO|FIXME|HACK|XXX)\b/i', $content);
        }
    }
    if ($todoCount > 10) {
        $findings[] = [
            'title' => "$todoCount TODO/FIXME markers in recently modified API files",
            'description' => 'There are unresolved issues marked in the codebase',
            'category' => 'maintenance',
            'severity' => 'info',
            'actionable' => false,
        ];
    }

    return $findings;
}

function scanUserExperience(PDO $db): array {
    $findings = [];
    $docRoot = dirname(__DIR__);

    // Check if key pages exist and are accessible
    $keyPages = [
        'dashboard.php' => 'Main user dashboard',
        'alfred-voice-live/'     => 'Voice command center',
        'pricing.php'   => 'Pricing page',
        'help.php'      => 'Help/documentation',
    ];

    foreach ($keyPages as $page => $desc) {
        if (!file_exists($docRoot . '/' . $page)) {
            $findings[] = [
                'title' => "Key page missing: $page ($desc)",
                'category' => 'ux',
                'severity' => 'high',
                'actionable' => true,
                'action' => "Create $page — $desc",
                'agent' => 'forge',
                'confidence' => 0.9,
                'impact' => 'Improve user experience',
                'risk' => 'low',
                'affected_files' => $page,
            ];
        }
    }

    // Check mobile viewport in key files
    $checkFiles = ['index.php', 'dashboard.php', 'alfred-voice-live/'];
    foreach ($checkFiles as $f) {
        $path = $docRoot . '/' . $f;
        if (file_exists($path)) {
            $content = @file_get_contents($path);
            if ($content && strpos($content, 'viewport') === false) {
                $findings[] = [
                    'title' => "$f missing viewport meta tag",
                    'description' => 'Page may not render properly on mobile devices',
                    'category' => 'accessibility',
                    'severity' => 'medium',
                    'actionable' => true,
                    'action' => "Add <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\"> to $f",
                    'agent' => 'forge',
                    'confidence' => 0.95,
                    'impact' => 'Better mobile experience',
                    'risk' => 'none',
                    'affected_files' => $f,
                ];
            }
        }
    }

    return $findings;
}

// ═══════════════════════════════════════════════════════
// HELPERS
// ═══════════════════════════════════════════════════════
function logEvolve(PDO $db, ?int $proposalId, string $action, string $details, string $agentId): void {
    $db->prepare("INSERT INTO evolve_history (proposal_id, action, details, agent_id) VALUES (?, ?, ?, ?)")
       ->execute([$proposalId, $action, substr($details, 0, 5000), $agentId]);
}
