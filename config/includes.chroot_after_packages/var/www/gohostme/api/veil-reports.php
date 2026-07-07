<?php
/**
 * Veil Reports API — Intelligence Briefings & Service Reports
 * ════════════════════════════════════════════════════════════
 * Generates, stores, and serves reports for the Commander's daily operations.
 * Reports are stored in DB and accessible via the Veil Reports Hub.
 *
 * Endpoints:
 *   GET  ?action=list              → List reports (filter by type, date)
 *   GET  ?action=get&id=X          → Get single report
 *   GET  ?action=latest&type=X     → Get most recent report of a type
 *   POST ?action=generate&type=X   → Generate a new report
 *   POST ?action=schedule          → Schedule a report for future generation
 *   GET  ?action=types             → List available report types
 */
define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';
session_start();

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

$clientId = $_SESSION['client_id'] ?? null;
$isInternal = false;
$internalSecret = getenv('INTERNAL_SECRET') ?: '';
if ($internalSecret && isset($_SERVER['HTTP_X_INTERNAL_SECRET']) && hash_equals($internalSecret, $_SERVER['HTTP_X_INTERNAL_SECRET'])) {
    $isInternal = true;
    $clientId = 1; // Internal calls act as owner
}

if (!$clientId && !$isInternal) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}
$isOwner = (int)$clientId === 33;

$db = getDB();
if (!$db) {
    http_response_code(503);
    echo json_encode(['error' => 'Database unavailable']);
    exit;
}

// Auto-create tables
$db->exec("CREATE TABLE IF NOT EXISTS veil_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_type VARCHAR(50) NOT NULL,
    title VARCHAR(500) NOT NULL,
    summary TEXT,
    content JSON NOT NULL,
    generated_by VARCHAR(50) DEFAULT 'system',
    severity ENUM('info','warning','critical') DEFAULT 'info',
    client_id INT DEFAULT 1,
    read_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type (report_type),
    INDEX idx_date (created_at),
    INDEX idx_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$action = sanitize($_GET['action'] ?? '', 30);
$method = $_SERVER['REQUEST_METHOD'];

switch ($action) {

// ─── List Reports ──────────────────────────────────────────────────
case 'list':
    $type = sanitize($_GET['type'] ?? '', 50);
    $limit = min(max(intval($_GET['limit'] ?? 25), 1), 100);
    $sql = "SELECT id, report_type, title, summary, severity, generated_by, read_at, created_at FROM veil_reports WHERE client_id = ?";
    $params = [$clientId];
    if ($type) { $sql .= " AND report_type = ?"; $params[] = $type; }
    $sql .= " ORDER BY created_at DESC LIMIT ?";
    $params[] = $limit;
    $stmt = $db->prepare($sql);
    dbExecute($stmt, $params);
    echo json_encode(['success' => true, 'reports' => $stmt->fetchAll()]);
    break;

// ─── Get Single Report ─────────────────────────────────────────────
case 'get':
    $id = intval($_GET['id'] ?? 0);
    if (!$id) { echo json_encode(['error' => 'id required']); break; }
    $stmt = $db->prepare("SELECT * FROM veil_reports WHERE id = ? AND client_id = ?");
    $stmt->execute([$id, $clientId]);
    $report = $stmt->fetch();
    if (!$report) { http_response_code(404); echo json_encode(['error' => 'Report not found']); break; }
    $report['content'] = json_decode($report['content'], true);
    // Mark as read
    if (!$report['read_at']) {
        $db->prepare("UPDATE veil_reports SET read_at = NOW() WHERE id = ?")->execute([$id]);
        $report['read_at'] = date('Y-m-d H:i:s');
    }
    echo json_encode(['success' => true, 'report' => $report]);
    break;

// ─── Latest of Type ─────────────────────────────────────────────────
case 'latest':
    $type = sanitize($_GET['type'] ?? '', 50);
    if (!$type) { echo json_encode(['error' => 'type required']); break; }
    $stmt = $db->prepare("SELECT * FROM veil_reports WHERE report_type = ? AND client_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$type, $clientId]);
    $report = $stmt->fetch();
    if ($report) $report['content'] = json_decode($report['content'], true);
    echo json_encode(['success' => true, 'report' => $report ?: null]);
    break;

// ─── Generate Report ────────────────────────────────────────────────
case 'generate':
    if ($method !== 'POST' && !$isInternal) { echo json_encode(['error' => 'POST required']); break; }
    if (!$isOwner && !$isInternal) { http_response_code(403); echo json_encode(['error' => 'Owner only']); break; }

    $type = sanitize($_GET['type'] ?? ($_POST['type'] ?? ''), 50);
    $reportTypes = [
        'morning_briefing'    => 'Morning Briefing',
        'service_health'      => 'Service Health Report',
        'agent_performance'   => 'Agent Performance Report',
        'security_scan'       => 'Security Scan Report',
        'ecosystem_gaps'      => 'Ecosystem Gap Analysis',
        'evolve_summary'      => 'Evolve Mode Summary',
        'weekly_digest'       => 'Weekly Digest',
        'incident_report'     => 'Incident Report',
    ];

    if (!isset($reportTypes[$type])) {
        echo json_encode(['error' => 'Unknown report type', 'available' => array_keys($reportTypes)]);
        break;
    }

    $content = [];
    $summary = '';
    $severity = 'info';
    $title = $reportTypes[$type] . ' — ' . date('M j, Y g:i A');

    switch ($type) {

    case 'morning_briefing':
        $content = generateMorningBriefing($db);
        $summary = "Daily briefing: {$content['services_up']}/{$content['services_total']} services UP, {$content['agents_active']} agents active, {$content['tasks_pending']} tasks pending";
        if ($content['services_down'] > 0) $severity = 'warning';
        break;

    case 'service_health':
        $content = generateServiceHealth($db);
        $summary = "{$content['healthy']}/{$content['total']} services healthy";
        if ($content['down'] > 0) $severity = 'critical';
        elseif ($content['warnings'] > 0) $severity = 'warning';
        break;

    case 'agent_performance':
        $content = generateAgentPerformance($db);
        $summary = "{$content['total_agents']} agents, {$content['tasks_completed_24h']} tasks completed (24h), {$content['failure_rate']}% failure rate";
        break;

    case 'security_scan':
        $content = generateSecurityReport($db);
        $summary = "{$content['findings']} findings, SSL expires in {$content['ssl_days_left']} days";
        if ($content['critical_findings'] > 0) $severity = 'critical';
        elseif ($content['findings'] > 0) $severity = 'warning';
        break;

    case 'ecosystem_gaps':
        $content = generateEcosystemGaps($db);
        $summary = "{$content['total_gaps']} gaps identified across {$content['categories_affected']} categories";
        break;

    case 'evolve_summary':
        $content = generateEvolveSummary($db);
        $summary = "{$content['scans_total']} scans, {$content['proposals_total']} proposals, {$content['approved']} approved";
        break;

    case 'incident_report':
        $content = generateIncidentReport($db);
        $summary = "{$content['incidents_24h']} incidents (24h), {$content['auto_healed']} auto-healed";
        if ($content['unresolved'] > 0) $severity = 'warning';
        break;
    }

    // Store report
    $stmt = $db->prepare("INSERT INTO veil_reports (report_type, title, summary, content, generated_by, severity, client_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$type, $title, $summary, json_encode($content), 'alfred', $severity, $clientId]);
    $reportId = $db->lastInsertId();

    echo json_encode(['success' => true, 'report_id' => $reportId, 'title' => $title, 'summary' => $summary, 'severity' => $severity]);
    break;

// ─── Report Types ───────────────────────────────────────────────────
case 'types':
    echo json_encode(['success' => true, 'types' => [
        ['id' => 'morning_briefing', 'name' => 'Morning Briefing', 'description' => 'Daily status of all services, agents, tasks, and security', 'schedule' => '8:00 AM daily'],
        ['id' => 'service_health', 'name' => 'Service Health', 'description' => 'Deep health check of all 9 services with latencies and uptime', 'schedule' => 'On demand'],
        ['id' => 'agent_performance', 'name' => 'Agent Performance', 'description' => 'Agent activity, task completion rates, and utilization', 'schedule' => 'On demand'],
        ['id' => 'security_scan', 'name' => 'Security Scan', 'description' => 'SSL status, vulnerability findings, access log analysis', 'schedule' => 'Daily'],
        ['id' => 'ecosystem_gaps', 'name' => 'Ecosystem Gaps', 'description' => 'Analysis of missing features and upgrade opportunities', 'schedule' => 'Weekly'],
        ['id' => 'evolve_summary', 'name' => 'Evolve Summary', 'description' => 'AI improvement proposals and scan results', 'schedule' => 'Daily'],
        ['id' => 'weekly_digest', 'name' => 'Weekly Digest', 'description' => 'Comprehensive weekly summary of all operations', 'schedule' => 'Weekly'],
        ['id' => 'incident_report', 'name' => 'Incident Report', 'description' => 'Service incidents, auto-healing results, and resolution times', 'schedule' => 'On demand'],
    ]]);
    break;

default:
    echo json_encode(['error' => 'Unknown action', 'available' => ['list','get','latest','generate','types']]);
}

// ═══════════════════════════════════════════════════════════════════════
// Report Generation Functions
// ═══════════════════════════════════════════════════════════════════════

function generateMorningBriefing(PDO $db): array {
    $report = ['generated_at' => date('c'), 'sections' => []];

    // 1. Service Health
    $services = [
        'Redis' => 6379, 'WebSocket' => 3010, 'Job Queue' => 3011,
        'MCP Server' => 3005, 'Middleware' => 3001, 'MeiliSearch' => 7700,
        'LiveKit' => 7880,
    ];
    $svcResults = [];
    $up = 0;
    foreach ($services as $name => $port) {
        $start = microtime(true);
        $conn = @fsockopen('127.0.0.1', $port, $en, $es, 2);
        $latency = round((microtime(true) - $start) * 1000, 1);
        $status = $conn ? 'up' : 'down';
        if ($conn) { fclose($conn); $up++; }
        $svcResults[] = ['name' => $name, 'port' => $port, 'status' => $status, 'latency_ms' => $latency];
    }
    // Ollama
    $ollamaOk = @file_get_contents('http://127.0.0.1:11434/api/tags', false, stream_context_create(['http' => ['timeout' => 2]]));
    $svcResults[] = ['name' => 'Ollama', 'port' => 11434, 'status' => $ollamaOk !== false ? 'up' : 'down', 'latency_ms' => 0];
    if ($ollamaOk !== false) $up++;

    $report['services'] = $svcResults;
    $report['services_up'] = $up;
    $report['services_down'] = count($svcResults) - $up;
    $report['services_total'] = count($svcResults);
    $report['sections'][] = ['title' => 'Service Health', 'status' => $up === count($svcResults) ? 'green' : 'yellow'];

    // 2. Agent Status
    try {
        $agents = $db->query("SELECT status, COUNT(*) as c FROM alfred_agent_registry GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (Exception $e) { $agents = []; }
    $report['agents'] = $agents;
    $report['agents_active'] = ($agents['idle'] ?? 0) + ($agents['busy'] ?? 0);
    $report['agents_error'] = $agents['error'] ?? 0;
    $report['sections'][] = ['title' => 'Agent Fleet', 'status' => ($report['agents_error'] > 0) ? 'yellow' : 'green'];

    // 3. Tasks
    try {
        $tasks = $db->query("SELECT status, COUNT(*) as c FROM alfred_agent_tasks WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (Exception $e) { $tasks = []; }
    $report['tasks'] = $tasks;
    $report['tasks_pending'] = ($tasks['queued'] ?? 0) + ($tasks['running'] ?? 0);
    $report['tasks_completed_24h'] = $tasks['completed'] ?? 0;
    $report['tasks_failed_24h'] = $tasks['failed'] ?? 0;
    $report['sections'][] = ['title' => 'Task Queue', 'status' => ($report['tasks_failed_24h'] > 3) ? 'red' : 'green'];

    // 4. Incidents (24h)
    try {
        $incidents = $db->query("SELECT COUNT(*) FROM alfred_incidents WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();
        $autoHealed = $db->query("SELECT COUNT(*) FROM alfred_incidents WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) AND healing_result = 'success'")->fetchColumn();
    } catch (Exception $e) { $incidents = 0; $autoHealed = 0; }
    $report['incidents_24h'] = (int)$incidents;
    $report['incidents_healed'] = (int)$autoHealed;
    $report['sections'][] = ['title' => 'Incidents', 'status' => ($incidents > 0 && $autoHealed < $incidents) ? 'yellow' : 'green'];

    // 5. System Resources
    $disk = @disk_free_space('/');
    $diskTotal = @disk_total_space('/');
    $diskPct = $diskTotal ? round(((($diskTotal - $disk) / $diskTotal) * 100), 1) : 0;
    $mem = @file_get_contents('/proc/meminfo');
    $memPct = 0;
    if ($mem) {
        preg_match('/MemTotal:\s+(\d+)/', $mem, $mt);
        preg_match('/MemAvailable:\s+(\d+)/', $mem, $ma);
        if (!empty($mt[1])) $memPct = round((1 - ($ma[1] ?? 0) / $mt[1]) * 100, 1);
    }
    $load = @sys_getloadavg();
    $report['system'] = [
        'disk_used_pct' => $diskPct,
        'memory_used_pct' => $memPct,
        'load_1m' => $load[0] ?? 0,
        'load_5m' => $load[1] ?? 0,
        'cpu_cores' => (int)(@shell_exec('nproc') ?: 1),
    ];
    $report['sections'][] = ['title' => 'System Resources', 'status' => ($diskPct > 85 || $memPct > 85) ? 'yellow' : 'green'];

    // 6. Upcoming Agenda
    try {
        $upcoming = $db->query("SELECT title, event_date, event_time, category, priority FROM veil_agenda WHERE event_date >= CURDATE() AND status != 'cancelled' ORDER BY event_date, event_time LIMIT 5")->fetchAll();
    } catch (Exception $e) { $upcoming = []; }
    $report['upcoming_events'] = $upcoming;
    $report['sections'][] = ['title' => 'Upcoming Agenda', 'status' => 'info'];

    // 7. Directives
    try {
        $directives = $db->query("SELECT COUNT(*) FROM alfred_ops_directives WHERE status = 'pending'")->fetchColumn();
    } catch (Exception $e) { $directives = 0; }
    $report['pending_directives'] = (int)$directives;

    return $report;
}

function generateServiceHealth(PDO $db): array {
    $services = [
        ['name' => 'Redis', 'port' => 6379, 'pm2' => 'redis'],
        ['name' => 'WebSocket', 'port' => 3010, 'pm2' => 'alfred-ws'],
        ['name' => 'Job Queue', 'port' => 3011, 'pm2' => 'alfred-jobs'],
        ['name' => 'MCP Server', 'port' => 3005, 'pm2' => 'alfred-mcp'],
        ['name' => 'Middleware', 'port' => 3001, 'pm2' => 'gocodeme-middleware'],
        ['name' => 'MeiliSearch', 'port' => 7700, 'pm2' => 'meilisearch'],
        ['name' => 'LiveKit', 'port' => 7880, 'pm2' => 'livekit'],
        ['name' => 'Heartbeat', 'port' => null, 'pm2' => 'alfred-heartbeat'],
    ];

    // Get PM2 data
    $pm2Json = @shell_exec('pm2 jlist 2>/dev/null');
    $pm2Procs = json_decode($pm2Json, true) ?: [];
    $pm2Map = [];
    foreach ($pm2Procs as $p) {
        $pm2Map[$p['name'] ?? ''] = [
            'status' => $p['pm2_env']['status'] ?? 'unknown',
            'pid' => $p['pid'] ?? 0,
            'uptime' => $p['pm2_env']['pm_uptime'] ?? 0,
            'restarts' => $p['pm2_env']['restart_time'] ?? 0,
            'memory_mb' => round(($p['monit']['memory'] ?? 0) / 1048576, 1),
            'cpu' => $p['monit']['cpu'] ?? 0,
        ];
    }

    $results = [];
    $healthy = 0; $warnings = 0; $down = 0;
    foreach ($services as $svc) {
        $portStatus = 'n/a';
        $latency = 0;
        if ($svc['port']) {
            $start = microtime(true);
            $conn = @fsockopen('127.0.0.1', $svc['port'], $en, $es, 2);
            $latency = round((microtime(true) - $start) * 1000, 1);
            $portStatus = $conn ? 'up' : 'down';
            if ($conn) fclose($conn);
        }

        $pm2Info = $pm2Map[$svc['pm2']] ?? ['status' => 'not_found'];
        $isUp = ($portStatus === 'up' || ($portStatus === 'n/a' && ($pm2Info['status'] ?? '') === 'online'));
        if ($isUp) $healthy++;
        else $down++;

        $results[] = array_merge([
            'name' => $svc['name'],
            'port' => $svc['port'],
            'port_status' => $portStatus,
            'latency_ms' => $latency,
        ], $pm2Info);
    }

    // Ollama (system service)
    $ollamaOk = @file_get_contents('http://127.0.0.1:11434/api/tags', false, stream_context_create(['http' => ['timeout' => 2]]));
    $results[] = ['name' => 'Ollama', 'port' => 11434, 'port_status' => $ollamaOk !== false ? 'up' : 'down', 'status' => $ollamaOk !== false ? 'online' : 'down'];
    if ($ollamaOk !== false) $healthy++; else $down++;

    // Recent incidents
    try {
        $incidents = $db->query("SELECT incident_id, severity, service, description, healing_result, created_at FROM alfred_incidents ORDER BY created_at DESC LIMIT 10")->fetchAll();
    } catch (Exception $e) { $incidents = []; }

    return [
        'services' => $results,
        'total' => count($results),
        'healthy' => $healthy,
        'warnings' => $warnings,
        'down' => $down,
        'recent_incidents' => $incidents,
        'checked_at' => date('c'),
    ];
}

function generateAgentPerformance(PDO $db): array {
    try {
        $agents = $db->query("SELECT agent_id, agent_name AS name, agent_role AS role, domain, status FROM alfred_agent_registry WHERE status != 'decommissioned' ORDER BY agent_role, agent_name")->fetchAll();
        $taskStats = $db->query("SELECT assigned_agent AS agent_id, status, COUNT(*) as c FROM alfred_agent_tasks WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) GROUP BY assigned_agent, status")->fetchAll();
        $totalAgents = count($agents);
        $byStatus = $db->query("SELECT status, COUNT(*) as c FROM alfred_agent_registry GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
        $tasksCompleted = $db->query("SELECT COUNT(*) FROM alfred_agent_tasks WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) AND status = 'completed'")->fetchColumn();
        $tasksFailed = $db->query("SELECT COUNT(*) FROM alfred_agent_tasks WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) AND status = 'failed'")->fetchColumn();
        $tasksTotal = $db->query("SELECT COUNT(*) FROM alfred_agent_tasks WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();
    } catch (Exception $e) {
        error_log('[veil-reports] health KPI: ' . $e->getMessage());
        return ['error' => 'Health check failed'];
    }

    $failureRate = $tasksTotal > 0 ? round(($tasksFailed / $tasksTotal) * 100, 1) : 0;

    // Top performers
    $topPerformers = $db->query("SELECT assigned_agent AS agent_id, COUNT(*) as completed FROM alfred_agent_tasks WHERE status = 'completed' AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) GROUP BY assigned_agent ORDER BY completed DESC LIMIT 10")->fetchAll();

    return [
        'total_agents' => $totalAgents,
        'by_status' => $byStatus,
        'tasks_completed_24h' => (int)$tasksCompleted,
        'tasks_failed_24h' => (int)$tasksFailed,
        'tasks_total_24h' => (int)$tasksTotal,
        'failure_rate' => $failureRate,
        'top_performers' => $topPerformers,
        'agent_roster' => $agents,
    ];
}

function generateSecurityReport(PDO $db): array {
    $findings = [];

    // SSL Check
    $sslDaysLeft = 999;
    $certPath = '/home/gositeme/.local/share/caddy/certificates/acme-v02.api.letsencrypt.org-directory/gositeme.com/gositeme.com.crt';
    if (file_exists($certPath)) {
        $cert = openssl_x509_parse(file_get_contents($certPath));
        if ($cert && isset($cert['validTo_time_t'])) {
            $sslDaysLeft = max(0, intdiv($cert['validTo_time_t'] - time(), 86400));
            if ($sslDaysLeft < 14) $findings[] = ['severity' => 'critical', 'finding' => "SSL expires in {$sslDaysLeft} days"];
            elseif ($sslDaysLeft < 30) $findings[] = ['severity' => 'warning', 'finding' => "SSL expires in {$sslDaysLeft} days"];
        }
    }

    // Agents in error state
    try {
        $errorAgents = $db->query("SELECT agent_id, agent_name AS name FROM alfred_agent_registry WHERE status = 'error'")->fetchAll();
        if (count($errorAgents) > 0) $findings[] = ['severity' => 'warning', 'finding' => count($errorAgents) . ' agent(s) in error state'];
    } catch (Exception $e) { $errorAgents = []; }

    // Recent failed logins (if login log exists)
    try {
        $failedLogins = $db->query("SELECT COUNT(*) FROM alfred_event_log WHERE event_type = 'login_failed' AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();
        if ($failedLogins > 5) $findings[] = ['severity' => 'warning', 'finding' => "{$failedLogins} failed login attempts (24h)"];
    } catch (Exception $e) { $failedLogins = 0; }

    // Recent incidents
    try {
        $criticalIncidents = $db->query("SELECT COUNT(*) FROM alfred_incidents WHERE severity = 'critical' AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();
        if ($criticalIncidents > 0) $findings[] = ['severity' => 'critical', 'finding' => "{$criticalIncidents} critical incidents in 24h"];
    } catch (Exception $e) {}

    $criticalCount = count(array_filter($findings, fn($f) => $f['severity'] === 'critical'));

    return [
        'ssl_days_left' => $sslDaysLeft,
        'findings' => count($findings),
        'critical_findings' => $criticalCount,
        'finding_details' => $findings,
        'error_agents' => $errorAgents,
        'failed_logins_24h' => (int)$failedLogins,
        'scanned_at' => date('c'),
    ];
}

function generateEcosystemGaps(PDO $db): array {
    // Comprehensive gap analysis
    $gaps = [];
    $categories = 0;

    // 1. Communication Channels
    $channelGaps = [];
    $channels = [
        'Web Chat' => true, 'SMS (Telnyx)' => true, 'Telegram' => true, 'Discord' => true,
        'Slack' => false, 'WhatsApp' => false, 'Email/SendGrid' => false,
    ];
    foreach ($channels as $ch => $configured) {
        if (!$configured) $channelGaps[] = $ch;
    }
    if ($channelGaps) {
        $gaps[] = ['category' => 'Communication', 'priority' => 'high', 'gaps' => $channelGaps,
            'recommendation' => 'Configure Slack, WhatsApp, and Email channels to reach users on all platforms'];
        $categories++;
    }

    // 2. Open-Source Tools
    $toolGaps = [];
    $existingTools = ['OnlyOffice (Docs)', 'RustDesk (Remote)', 'OpenCut (Video)', 'Element (Messaging)', 'Gitea (Git)'];
    $missingTools = [
        'Whiteboard (Excalidraw)' => 'Collaborative whiteboard for brainstorming and diagrams',
        'Project Management (Plane/Focalboard)' => 'Kanban boards, sprints, issue tracking',
        'CRM (Twenty/EspoCRM)' => 'Customer relationship management',
        'Email Server (Stalwart/Mailu)' => 'Self-hosted email with IMAP/SMTP',
        'Wiki/Knowledge Base (Outline/BookStack)' => 'Team documentation and knowledge sharing',
        'Forms/Surveys (Formbricks)' => 'Custom forms and feedback collection',
        'File Storage (Nextcloud)' => 'File sync, sharing, and collaboration',
        'Password Manager (Vaultwarden)' => 'Team password and secrets management',
    ];
    foreach ($missingTools as $tool => $desc) {
        $toolGaps[] = ['tool' => $tool, 'description' => $desc];
    }
    if ($toolGaps) {
        $gaps[] = ['category' => 'Open-Source Tools', 'priority' => 'medium', 'gaps' => $toolGaps,
            'recommendation' => 'Add whiteboard and project management first — highest demand. Bundle with hosting plans.'];
        $categories++;
    }

    // 3. Monetization/Revenue
    $revenueGaps = [];
    $existingRevenue = ['Hosting Plans', 'Domain Registration', 'AI Token Top-ups', 'Voice Products', 'Marketplace'];
    $missingRevenue = [
        'Managed Services' => 'Managed hosting/security/backups at premium',
        'White-Label Licensing' => 'License the platform to agencies',
        'API Usage Billing' => 'Per-call billing for external API consumers',
        'Training/Certification' => 'Paid AI/hosting courses and certifications',
        'Custom Agent Building' => 'Build custom AI agents for enterprise clients',
    ];
    foreach ($missingRevenue as $item => $desc) {
        $revenueGaps[] = ['item' => $item, 'description' => $desc];
    }
    if ($revenueGaps) {
        $gaps[] = ['category' => 'Revenue Streams', 'priority' => 'high', 'gaps' => $revenueGaps,
            'recommendation' => 'White-label licensing and managed services are fastest to revenue'];
        $categories++;
    }

    // 4. Platform Features
    $featureGaps = [
        ['feature' => 'Mobile App (iOS)', 'description' => 'Native iOS app for the Veil Command Center', 'priority' => 'medium'],
        ['feature' => 'Push Notifications', 'description' => 'Real-time alerts for incidents, tasks, and messages', 'priority' => 'high'],
        ['feature' => 'Multi-Tenant Admin', 'description' => 'Client-level admin panels with delegated management', 'priority' => 'medium'],
        ['feature' => 'Automated Backups UI', 'description' => 'Self-service backup management dashboard', 'priority' => 'medium'],
        ['feature' => 'AI Training Dashboard', 'description' => 'Fine-tune AI models with customer data', 'priority' => 'low'],
        ['feature' => 'Usage Analytics Dashboard', 'description' => 'Client-facing token usage and API analytics', 'priority' => 'medium'],
    ];
    $gaps[] = ['category' => 'Platform Features', 'priority' => 'medium', 'gaps' => $featureGaps,
        'recommendation' => 'Push notifications and multi-tenant admin will improve retention'];
    $categories++;

    // 5. Integration Gaps
    $integrationGaps = [
        ['integration' => 'Stripe Subscriptions', 'description' => 'Auto-recurring billing for plans', 'status' => 'partial'],
        ['integration' => 'Zapier/Make', 'description' => 'Third-party automation triggers', 'status' => 'missing'],
        ['integration' => 'Google Workspace', 'description' => 'Calendar, Drive, Gmail integration', 'status' => 'missing'],
        ['integration' => 'Microsoft 365', 'description' => 'Teams, OneDrive, Outlook integration', 'status' => 'missing'],
        ['integration' => 'Cloudflare', 'description' => 'CDN and DDoS protection', 'status' => 'missing'],
    ];
    $gaps[] = ['category' => 'Integrations', 'priority' => 'medium', 'gaps' => $integrationGaps,
        'recommendation' => 'Zapier integration opens access to 5000+ apps'];
    $categories++;

    $totalGaps = 0;
    foreach ($gaps as $g) {
        $totalGaps += count($g['gaps']);
    }

    return [
        'gaps' => $gaps,
        'total_gaps' => $totalGaps,
        'categories_affected' => $categories,
        'existing_strengths' => [
            '101 AI Agents with hierarchical delegation',
            '6 AI providers with cascade fallback',
            '1220+ tools across 89 categories',
            '52 Voice AI products',
            'Post-quantum encryption (Kyber-1024)',
            '8+ communication channels',
            'Self-healing infrastructure (9 services)',
            'Evolve Mode (self-improving AI)',
            '5 open-source tools bundled',
            'Full marketplace with cart system',
        ],
        'top_priorities' => [
            ['gap' => 'Collaborative Whiteboard', 'category' => 'Tools', 'reason' => 'High demand, easy to deploy (Excalidraw), bundles with OnlyOffice'],
            ['gap' => 'Push Notifications', 'category' => 'Platform', 'reason' => 'Critical for incident alerts and mobile experience'],
            ['gap' => 'Slack/WhatsApp/Email Channels', 'category' => 'Communication', 'reason' => '3 unconfigured channels — easy wins'],
            ['gap' => 'Project Management Tool', 'category' => 'Tools', 'reason' => 'Essential for teams, high retention impact'],
            ['gap' => 'White-Label Licensing', 'category' => 'Revenue', 'reason' => 'Fastest path to recurring enterprise revenue'],
        ],
        'analyzed_at' => date('c'),
    ];
}

function generateEvolveSummary(PDO $db): array {
    try {
        $config = $db->query("SELECT * FROM evolve_config WHERE id = 1")->fetch();
        $scansTotal = $db->query("SELECT COUNT(*) FROM evolve_scans")->fetchColumn();
        $proposalsTotal = $db->query("SELECT COUNT(*) FROM evolve_proposals")->fetchColumn();
        $approved = $db->query("SELECT COUNT(*) FROM evolve_proposals WHERE status = 'approved'")->fetchColumn();
        $rejected = $db->query("SELECT COUNT(*) FROM evolve_proposals WHERE status = 'rejected'")->fetchColumn();
        $pending = $db->query("SELECT COUNT(*) FROM evolve_proposals WHERE status = 'pending'")->fetchColumn();
        $recentScans = $db->query("SELECT scan_id, scan_type, findings_count, proposals_generated, created_at FROM evolve_scans ORDER BY created_at DESC LIMIT 5")->fetchAll();
        $recentProposals = $db->query("SELECT proposal_id, title, category, risk_level, confidence, status, created_at FROM evolve_proposals ORDER BY created_at DESC LIMIT 10")->fetchAll();
    } catch (Exception $e) {
        return ['error' => 'Evolve tables not yet initialized', 'scans_total' => 0, 'proposals_total' => 0, 'approved' => 0];
    }

    return [
        'mode' => $config['mode'] ?? 'unknown',
        'active' => ($config['active'] ?? 0) == 1,
        'scans_total' => (int)$scansTotal,
        'proposals_total' => (int)$proposalsTotal,
        'approved' => (int)$approved,
        'rejected' => (int)$rejected,
        'pending' => (int)$pending,
        'recent_scans' => $recentScans,
        'recent_proposals' => $recentProposals,
        'config' => [
            'scan_interval' => $config['scan_interval_minutes'] ?? 60,
            'approval_threshold' => $config['approval_threshold'] ?? 0.95,
            'daily_change_limit' => $config['daily_change_limit'] ?? 10,
        ],
    ];
}

function generateIncidentReport(PDO $db): array {
    try {
        $total = $db->query("SELECT COUNT(*) FROM alfred_incidents WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();
        $autoHealed = $db->query("SELECT COUNT(*) FROM alfred_incidents WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) AND healing_result = 'success'")->fetchColumn();
        $unresolved = $db->query("SELECT COUNT(*) FROM alfred_incidents WHERE healing_result = 'pending'")->fetchColumn();
        $bySeverity = $db->query("SELECT severity, COUNT(*) as c FROM alfred_incidents WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) GROUP BY severity")->fetchAll(PDO::FETCH_KEY_PAIR);
        $byService = $db->query("SELECT service, COUNT(*) as c FROM alfred_incidents WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) GROUP BY service ORDER BY c DESC")->fetchAll(PDO::FETCH_KEY_PAIR);
        $recent = $db->query("SELECT incident_id, severity, service, description, healing_action, healing_result, created_at FROM alfred_incidents ORDER BY created_at DESC LIMIT 20")->fetchAll();
    } catch (Exception $e) {
        error_log('[veil-reports] incident KPI: ' . $e->getMessage());
        return ['error' => 'Incident check failed', 'incidents_24h' => 0, 'auto_healed' => 0, 'unresolved' => 0];
    }

    return [
        'incidents_24h' => (int)$total,
        'auto_healed' => (int)$autoHealed,
        'unresolved' => (int)$unresolved,
        'heal_rate' => $total > 0 ? round(($autoHealed / $total) * 100, 1) : 100,
        'by_severity' => $bySeverity,
        'by_service' => $byService,
        'recent_incidents' => $recent,
    ];
}
