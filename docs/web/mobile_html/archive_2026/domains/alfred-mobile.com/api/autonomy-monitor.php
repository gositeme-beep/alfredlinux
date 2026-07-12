<?php
/**
 * Autonomy Monitor API — Unified Ecosystem Health & Self-Healing
 * ══════════════════════════════════════════════════════════════════
 * Single pane of glass for ALL system components. Aggregates health
 * data from PM2 services, crawlers, mining, trading, intelligence,
 * billing, fleet agents, and the AI pipeline.
 *
 * Endpoints:
 *   GET  ?action=dashboard        → Full ecosystem health overview
 *   GET  ?action=services         → PM2 service status + ports
 *   GET  ?action=subsystems       → All subsystem health checks
 *   GET  ?action=incidents        → Recent incidents & healing logs
 *   GET  ?action=continuity       → Uptime & continuity metrics
 *   POST ?action=heal             → Manually trigger healing on a service
 *   GET  ?action=agent_census     → Count all agents across all systems
 *   GET  ?action=pulse            → Quick health pulse (for heartbeat)
 */

define('GOSITEME_API', true);
require_once dirname(__DIR__) . '/api/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// ── Auth ────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) session_start();
$userId = (int)($_SESSION['user_id'] ?? $_SESSION['client_id'] ?? 0);
$adminIds = [33];

// Pulse endpoint accessible internally (for heartbeat checks)
$action = $_GET['action'] ?? $_POST['action'] ?? 'dashboard';
$internalSecret = getenv('INTERNAL_SECRET') ?: '';
$isInternal = $internalSecret && isset($_SERVER['HTTP_X_INTERNAL_SECRET']) && hash_equals($internalSecret, $_SERVER['HTTP_X_INTERNAL_SECRET']);

if ($action !== 'pulse' && !$isInternal) {
    if (!$userId || !in_array($userId, $adminIds)) {
        http_response_code(403);
        echo json_encode(['error' => 'Admin access required']);
        exit;
    }
}

$db = getDB();
if (!$db) {
    echo json_encode(['error' => 'Database unavailable']);
    exit;
}

// ── Ensure tables ───────────────────────────────────────────────
$db->exec("CREATE TABLE IF NOT EXISTS autonomy_health_snapshots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ecosystem_score DECIMAL(5,2) DEFAULT 100.00,
    services_up INT DEFAULT 0,
    services_total INT DEFAULT 0,
    subsystems_healthy INT DEFAULT 0,
    subsystems_total INT DEFAULT 0,
    agents_active INT DEFAULT 0,
    incidents_open INT DEFAULT 0,
    details JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS autonomy_healing_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    target_system VARCHAR(100) NOT NULL,
    issue_detected VARCHAR(500) NOT NULL,
    healing_action VARCHAR(500) NOT NULL,
    result ENUM('success','failed','partial') DEFAULT 'failed',
    triggered_by VARCHAR(50) DEFAULT 'auto',
    details JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_target (target_system),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Route ───────────────────────────────────────────────────────
switch ($action) {
    case 'dashboard':     handleDashboard($db); break;
    case 'services':      handleServices($db); break;
    case 'subsystems':    handleSubsystems($db); break;
    case 'incidents':     handleIncidents($db); break;
    case 'continuity':    handleContinuity($db); break;
    case 'heal':          handleHeal($db); break;
    case 'agent_census':  handleAgentCensus($db); break;
    case 'pulse':         handlePulse($db); break;
    default:
        echo json_encode(['error' => 'Invalid action', 'valid' => ['dashboard','services','subsystems','incidents','continuity','heal','agent_census','pulse']]);
}

// ═══════════════════════════════════════════════════════════════
// PM2 SERVICE CHECKS
// ═══════════════════════════════════════════════════════════════
function getPm2Services(): array {
    $pm2Bin = '/home/gositeme/.local/node_modules/.bin/pm2';
    $json = @shell_exec("{$pm2Bin} jlist 2>/dev/null");
    $procs = json_decode($json ?: '[]', true) ?: [];

    $services = [];
    foreach ($procs as $p) {
        $env = $p['pm2_env'] ?? [];
        $name = $p['name'] ?? 'unknown';
        $services[$name] = [
            'name'       => $name,
            'status'     => $env['status'] ?? 'unknown',
            'pid'        => $p['pid'] ?? null,
            'cpu'        => $p['monit']['cpu'] ?? 0,
            'memory_mb'  => round(($p['monit']['memory'] ?? 0) / 1024 / 1024, 1),
            'restarts'   => $env['restart_time'] ?? 0,
            'uptime_ms'  => ($env['status'] === 'online' && isset($env['pm_uptime'])) ? (time() * 1000 - $env['pm_uptime']) : 0,
            'uptime_human' => '',
        ];
        $ms = $services[$name]['uptime_ms'];
        if ($ms > 0) {
            $hours = floor($ms / 3600000);
            $mins = floor(($ms % 3600000) / 60000);
            $services[$name]['uptime_human'] = "{$hours}h {$mins}m";
        }
    }
    return $services;
}

function checkPort(int $port): bool {
    $conn = @fsockopen('127.0.0.1', $port, $errno, $errstr, 2);
    if ($conn) { fclose($conn); return true; }
    return false;
}

function checkHttp(string $url, int $timeout = 3): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CONNECTTIMEOUT => $timeout,
        CURLOPT_NOBODY => true,
    ]);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $time = round(curl_getinfo($ch, CURLINFO_TOTAL_TIME) * 1000);
    curl_close($ch);
    return ['up' => $code >= 200 && $code < 500, 'code' => $code, 'latency_ms' => $time];
}

// ═══════════════════════════════════════════════════════════════
// SUBSYSTEM HEALTH CHECKS
// ═══════════════════════════════════════════════════════════════
function checkAllSubsystems($db): array {
    $subsystems = [];

    // 1. Database
    try {
        $row = $db->query("SELECT 1 as ok")->fetch();
        $subsystems['database'] = ['status' => 'healthy', 'details' => 'MySQL responding'];
    } catch (Exception $e) {
        $subsystems['database'] = ['status' => 'critical', 'details' => $e->getMessage()];
    }

    // 2. Redis
    $subsystems['redis'] = ['status' => checkPort(6379) ? 'healthy' : 'critical', 'details' => 'Port 6379'];

    // 3. Meilisearch
    $ms = checkHttp('http://127.0.0.1:7700/health');
    $subsystems['meilisearch'] = ['status' => $ms['up'] ? 'healthy' : 'critical', 'details' => "HTTP {$ms['code']}, {$ms['latency_ms']}ms"];

    // 4. Ollama
    $ollama = checkHttp('http://127.0.0.1:11434/api/tags');
    $subsystems['ollama'] = ['status' => $ollama['up'] ? 'healthy' : 'degraded', 'details' => "HTTP {$ollama['code']}, {$ollama['latency_ms']}ms"];

    // 5. WebSocket
    $subsystems['websocket'] = ['status' => checkPort(3010) ? 'healthy' : 'critical', 'details' => 'Port 3010'];

    // 6. Mining System
    try {
        $miningRewards = $db->query("SELECT COUNT(*) FROM search_mining_rewards WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)")->fetchColumn();
        $subsystems['mining'] = ['status' => 'healthy', 'details' => "{$miningRewards} rewards in last hour"];
    } catch (Exception $e) {
        $subsystems['mining'] = ['status' => 'unknown', 'details' => 'Table may not exist'];
    }

    // 7. Crawler v2
    try {
        $crawled = $db->query("SELECT COUNT(*) FROM crawler_pages_v2 WHERE crawled_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)")->fetchColumn();
        $totalPages = $db->query("SELECT COUNT(*) FROM crawler_pages_v2")->fetchColumn();
        $subsystems['crawler'] = ['status' => 'healthy', 'details' => "{$totalPages} total pages, {$crawled} crawled this hour"];
    } catch (Exception $e) {
        $subsystems['crawler'] = ['status' => 'unknown', 'details' => 'Table may not exist'];
    }

    // 8. Intelligence Crawler
    try {
        $intel = $db->query("SELECT COUNT(*) FROM intel_articles WHERE crawled_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)")->fetchColumn();
        $alerts = $db->query("SELECT COUNT(*) FROM intel_alerts WHERE acknowledged = 0")->fetchColumn();
        $subsystems['intelligence'] = ['status' => $alerts > 5 ? 'warning' : 'healthy', 'details' => "{$intel} articles this hour, {$alerts} unacknowledged alerts"];
    } catch (Exception $e) {
        $subsystems['intelligence'] = ['status' => 'unknown', 'details' => 'Table may not exist'];
    }

    // 9. Trading Agents
    try {
        $activeAgents = $db->query("SELECT COUNT(*) FROM trading_agents WHERE status = 'active'")->fetchColumn();
        $openPositions = $db->query("SELECT COUNT(*) FROM trading_positions WHERE status = 'open'")->fetchColumn();
        $subsystems['trading'] = ['status' => 'healthy', 'details' => "{$activeAgents} active agents, {$openPositions} open positions"];
    } catch (Exception $e) {
        $subsystems['trading'] = ['status' => 'unknown', 'details' => 'Table may not exist'];
    }

    // 10. Fleet Agents
    try {
        $fleetActive = $db->query("SELECT COUNT(*) FROM agent_fleet_tracker WHERE status NOT IN ('idle','offline','maintenance')")->fetchColumn();
        $fleetTotal = $db->query("SELECT COUNT(*) FROM agent_fleet_tracker")->fetchColumn();
        $avgHealth = (float)$db->query("SELECT AVG(health_score) FROM agent_fleet_tracker")->fetchColumn();
        $subsystems['fleet'] = [
            'status' => $avgHealth >= 70 ? 'healthy' : ($avgHealth >= 40 ? 'degraded' : 'critical'),
            'details' => "{$fleetActive}/{$fleetTotal} active, avg health: " . round($avgHealth)
        ];
    } catch (Exception $e) {
        $subsystems['fleet'] = ['status' => 'unknown', 'details' => 'Table may not exist'];
    }

    // 11. Billing System
    try {
        $invoiceCount = $db->query("SELECT COUNT(*) FROM invoices WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();
        $subsystems['billing'] = ['status' => 'healthy', 'details' => "{$invoiceCount} invoices in 24h"];
    } catch (Exception $e) {
        $subsystems['billing'] = ['status' => 'unknown', 'details' => 'Table may not exist'];
    }

    // 12. Revenue/Treasury
    try {
        $treasury = $db->query("SELECT SUM(balance) FROM platform_treasury")->fetchColumn();
        $subsystems['treasury'] = ['status' => 'healthy', 'details' => 'Balance: ' . number_format((float)$treasury, 2) . ' GSM'];
    } catch (Exception $e) {
        $subsystems['treasury'] = ['status' => 'unknown', 'details' => 'Table may not exist'];
    }

    // 13. Disk Space
    $freeBytes = disk_free_space('/');
    $totalBytes = disk_total_space('/');
    $usedPct = round((1 - $freeBytes / $totalBytes) * 100, 1);
    $freeGB = round($freeBytes / 1073741824, 1);
    $subsystems['disk'] = [
        'status' => $usedPct < 80 ? 'healthy' : ($usedPct < 90 ? 'warning' : 'critical'),
        'details' => "{$freeGB} GB free ({$usedPct}% used)"
    ];

    // 14. Memory
    $memInfo = @file_get_contents('/proc/meminfo');
    if ($memInfo) {
        preg_match('/MemTotal:\s+(\d+)/', $memInfo, $totalMatch);
        preg_match('/MemAvailable:\s+(\d+)/', $memInfo, $availMatch);
        $totalMB = round(($totalMatch[1] ?? 0) / 1024);
        $availMB = round(($availMatch[1] ?? 0) / 1024);
        $usedMB = $totalMB - $availMB;
        $memPct = $totalMB > 0 ? round($usedMB / $totalMB * 100, 1) : 0;
        $subsystems['memory'] = [
            'status' => $memPct < 80 ? 'healthy' : ($memPct < 90 ? 'warning' : 'critical'),
            'details' => "{$usedMB}MB / {$totalMB}MB ({$memPct}% used), {$availMB}MB available"
        ];
    }

    // 15. Load Average
    $load = sys_getloadavg();
    $cores = (int)shell_exec('nproc 2>/dev/null') ?: 1;
    $loadPct = round($load[0] / $cores * 100, 1);
    $subsystems['cpu_load'] = [
        'status' => $loadPct < 70 ? 'healthy' : ($loadPct < 90 ? 'warning' : 'critical'),
        'details' => "Load: " . round($load[0], 2) . " / " . round($load[1], 2) . " / " . round($load[2], 2) . " ({$cores} cores, {$loadPct}%)"
    ];

    return $subsystems;
}

// ═══════════════════════════════════════════════════════════════
// ECOSYSTEM SCORE CALCULATION
// ═══════════════════════════════════════════════════════════════
function calculateEcosystemScore(array $pm2Services, array $subsystems): float {
    $score = 100.0;
    $weights = [
        'database' => 20, 'redis' => 10, 'meilisearch' => 8, 'websocket' => 8,
        'ollama' => 5, 'mining' => 8, 'crawler' => 5, 'intelligence' => 5,
        'trading' => 5, 'fleet' => 5, 'billing' => 5, 'treasury' => 3,
        'disk' => 5, 'memory' => 4, 'cpu_load' => 4
    ];

    foreach ($subsystems as $name => $info) {
        $weight = $weights[$name] ?? 3;
        if ($info['status'] === 'critical') $score -= $weight;
        elseif ($info['status'] === 'degraded') $score -= $weight * 0.5;
        elseif ($info['status'] === 'warning') $score -= $weight * 0.3;
        elseif ($info['status'] === 'unknown') $score -= $weight * 0.1;
    }

    // PM2 service penalties
    foreach ($pm2Services as $svc) {
        if ($svc['status'] !== 'online') $score -= 3;
        if ($svc['restarts'] > 10) $score -= 1;
    }

    return max(0, min(100, $score));
}

// ═══════════════════════════════════════════════════════════════
// DASHBOARD — Full ecosystem overview
// ═══════════════════════════════════════════════════════════════
function handleDashboard($db) {
    $pm2 = getPm2Services();
    $subsystems = checkAllSubsystems($db);
    $score = calculateEcosystemScore($pm2, $subsystems);

    $servicesUp = 0;
    $servicesTotal = count($pm2);
    foreach ($pm2 as $s) { if ($s['status'] === 'online') $servicesUp++; }

    $subsHealthy = 0;
    $subsTotal = count($subsystems);
    foreach ($subsystems as $s) { if ($s['status'] === 'healthy') $subsHealthy++; }

    // Threat level
    $threatLevel = 'GREEN';
    if ($score < 90) $threatLevel = 'YELLOW';
    if ($score < 70) $threatLevel = 'ORANGE';
    if ($score < 50) $threatLevel = 'RED';

    // Recent incidents
    $incidents = [];
    try {
        $stmt = $db->query("SELECT * FROM alfred_incidents ORDER BY created_at DESC LIMIT 10");
        $incidents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}

    // Recent healing
    $healing = [];
    try {
        $stmt = $db->query("SELECT * FROM autonomy_healing_log ORDER BY created_at DESC LIMIT 10");
        $healing = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}

    // Save snapshot
    try {
        $snap = $db->prepare("INSERT INTO autonomy_health_snapshots (ecosystem_score, services_up, services_total, subsystems_healthy, subsystems_total, agents_active, incidents_open, details) VALUES (?,?,?,?,?,?,?,?)");
        $activeAgents = 0;
        try {
            $activeAgents += (int)$db->query("SELECT COUNT(*) FROM agent_fleet_tracker WHERE status NOT IN ('idle','offline','maintenance')")->fetchColumn();
        } catch (Exception $e) {}
        try {
            $activeAgents += (int)$db->query("SELECT COUNT(*) FROM trading_agents WHERE status = 'active'")->fetchColumn();
        } catch (Exception $e) {}
        $openIncidents = 0;
        try {
            $openIncidents = (int)$db->query("SELECT COUNT(*) FROM alfred_incidents WHERE resolved_at IS NULL")->fetchColumn();
        } catch (Exception $e) {}
        $snap->execute([$score, $servicesUp, $servicesTotal, $subsHealthy, $subsTotal, $activeAgents, $openIncidents, json_encode(['pm2' => $pm2, 'subsystems' => $subsystems])]);
    } catch (Exception $e) {}

    echo json_encode([
        'success' => true,
        'ecosystem' => [
            'score' => round($score, 1),
            'threat_level' => $threatLevel,
            'services_up' => $servicesUp,
            'services_total' => $servicesTotal,
            'subsystems_healthy' => $subsHealthy,
            'subsystems_total' => $subsTotal,
            'uptime_target' => '99.9%',
        ],
        'pm2_services' => $pm2,
        'subsystems' => $subsystems,
        'recent_incidents' => $incidents,
        'recent_healing' => $healing,
        'timestamp' => date('c'),
        'next_check' => date('c', time() + 60),
    ]);
}

// ═══════════════════════════════════════════════════════════════
// SERVICES — PM2 details
// ═══════════════════════════════════════════════════════════════
function handleServices($db) {
    $pm2 = getPm2Services();
    $portMap = [
        'redis' => 6379, 'meilisearch' => 7700, 'alfred-ws' => 3010,
        'alfred-jobs' => 3011, 'alfred-mcp' => 3005, 'gocodeme-middleware' => 3001,
        'ollama' => 11434,
    ];

    foreach ($pm2 as $name => &$svc) {
        $svc['port'] = $portMap[$name] ?? null;
        $svc['port_open'] = $svc['port'] ? checkPort($svc['port']) : null;
    }

    echo json_encode(['success' => true, 'services' => $pm2, 'timestamp' => date('c')]);
}

// ═══════════════════════════════════════════════════════════════
// SUBSYSTEMS — Detailed health
// ═══════════════════════════════════════════════════════════════
function handleSubsystems($db) {
    $subsystems = checkAllSubsystems($db);
    $healthy = $degraded = $critical = $unknown = 0;
    foreach ($subsystems as $s) {
        match ($s['status']) {
            'healthy' => $healthy++,
            'degraded', 'warning' => $degraded++,
            'critical' => $critical++,
            default => $unknown++,
        };
    }

    echo json_encode([
        'success' => true,
        'summary' => compact('healthy', 'degraded', 'critical', 'unknown'),
        'subsystems' => $subsystems,
        'timestamp' => date('c'),
    ]);
}

// ═══════════════════════════════════════════════════════════════
// INCIDENTS — Recent system incidents
// ═══════════════════════════════════════════════════════════════
function handleIncidents($db) {
    $limit = min(100, max(1, (int)($_GET['limit'] ?? 50)));
    $incidents = [];

    try {
        $stmt = $db->prepare("SELECT * FROM alfred_incidents ORDER BY created_at DESC LIMIT ?");
        dbExecute($stmt, [$limit]);
        $incidents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}

    $healing = [];
    try {
        $stmt = $db->prepare("SELECT * FROM autonomy_healing_log ORDER BY created_at DESC LIMIT ?");
        dbExecute($stmt, [$limit]);
        $healing = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}

    // Stats
    $stats = ['total_incidents' => 0, 'resolved' => 0, 'open' => 0, 'healing_success' => 0, 'healing_failed' => 0];
    try {
        $stats['total_incidents'] = (int)$db->query("SELECT COUNT(*) FROM alfred_incidents")->fetchColumn();
        $stats['resolved'] = (int)$db->query("SELECT COUNT(*) FROM alfred_incidents WHERE resolved_at IS NOT NULL")->fetchColumn();
        $stats['open'] = $stats['total_incidents'] - $stats['resolved'];
    } catch (Exception $e) {}
    try {
        $stats['healing_success'] = (int)$db->query("SELECT COUNT(*) FROM autonomy_healing_log WHERE result = 'success'")->fetchColumn();
        $stats['healing_failed'] = (int)$db->query("SELECT COUNT(*) FROM autonomy_healing_log WHERE result = 'failed'")->fetchColumn();
    } catch (Exception $e) {}

    echo json_encode(['success' => true, 'stats' => $stats, 'incidents' => $incidents, 'healing' => $healing]);
}

// ═══════════════════════════════════════════════════════════════
// CONTINUITY — Uptime metrics
// ═══════════════════════════════════════════════════════════════
function handleContinuity($db) {
    // Health snapshots over time
    $snapshots = [];
    try {
        $stmt = $db->query("SELECT ecosystem_score, services_up, services_total, subsystems_healthy, subsystems_total, agents_active, incidents_open, created_at FROM autonomy_health_snapshots ORDER BY created_at DESC LIMIT 1440"); // 24h of minute-by-minute
        $snapshots = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}

    // Calculate uptime from snapshots
    $total = count($snapshots);
    $healthy = 0;
    $avgScore = 0;
    foreach ($snapshots as $s) {
        if ($s['ecosystem_score'] >= 70) $healthy++;
        $avgScore += $s['ecosystem_score'];
    }
    $uptimePct = $total > 0 ? round($healthy / $total * 100, 2) : 100;
    $avgScore = $total > 0 ? round($avgScore / $total, 1) : 100;

    // Server uptime
    $serverUptime = @file_get_contents('/proc/uptime');
    $uptimeSeconds = $serverUptime ? (float)explode(' ', $serverUptime)[0] : 0;
    $uptimeDays = floor($uptimeSeconds / 86400);
    $uptimeHours = floor(($uptimeSeconds % 86400) / 3600);

    echo json_encode([
        'success' => true,
        'continuity' => [
            'ecosystem_uptime_pct' => $uptimePct,
            'avg_ecosystem_score' => $avgScore,
            'snapshots_analyzed' => $total,
            'server_uptime_days' => $uptimeDays,
            'server_uptime_hours' => $uptimeHours,
            'server_uptime_human' => "{$uptimeDays}d {$uptimeHours}h",
        ],
        'recent_scores' => array_slice($snapshots, 0, 60), // Last hour
    ]);
}

// ═══════════════════════════════════════════════════════════════
// HEAL — Manual healing trigger
// ═══════════════════════════════════════════════════════════════
function handleHeal($db) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['error' => 'POST required']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $target = $input['target'] ?? '';
    $allowed = ['redis', 'meilisearch', 'websocket', 'alfred-ws', 'alfred-jobs', 'alfred-mcp', 'gocodeme-middleware', 'alfred-heartbeat', 'alfred-discord', 'ollama'];

    if (!$target || !in_array($target, $allowed)) {
        echo json_encode(['error' => 'Invalid target', 'allowed' => $allowed]);
        return;
    }

    $pm2Bin = '/home/gositeme/.local/node_modules/.bin/pm2';
    $escaped = escapeshellarg($target);
    $output = shell_exec("{$pm2Bin} restart {$escaped} 2>&1");
    sleep(3);

    // Verify
    $status = 'unknown';
    $json = @shell_exec("{$pm2Bin} jlist 2>/dev/null");
    $procs = json_decode($json ?: '[]', true) ?: [];
    foreach ($procs as $p) {
        if (($p['name'] ?? '') === $target) {
            $status = $p['pm2_env']['status'] ?? 'unknown';
        }
    }

    $result = $status === 'online' ? 'success' : 'failed';

    // Log
    try {
        $stmt = $db->prepare("INSERT INTO autonomy_healing_log (target_system, issue_detected, healing_action, result, triggered_by) VALUES (?,?,?,?,?)");
        $stmt->execute([$target, 'Manual heal requested by admin', "PM2 restart {$target}", $result, 'admin']);
    } catch (Exception $e) {}

    echo json_encode([
        'success' => $result === 'success',
        'target' => $target,
        'new_status' => $status,
        'result' => $result,
        'output' => $output,
    ]);
}

// ═══════════════════════════════════════════════════════════════
// AGENT CENSUS — Count agents across all systems
// ═══════════════════════════════════════════════════════════════
function handleAgentCensus($db) {
    $census = [
        'fleet_agents' => ['total' => 0, 'active' => 0, 'divisions' => []],
        'trading_agents' => ['total' => 0, 'active' => 0, 'strategies' => []],
        'fleet_tasks' => ['total' => 0, 'running' => 0],
        'pm2_services' => ['total' => 0, 'online' => 0],
        'intelligence_sources' => 0,
        'grand_total' => 0,
    ];

    // Fleet tracker agents
    try {
        $census['fleet_agents']['total'] = (int)$db->query("SELECT COUNT(*) FROM agent_fleet_tracker")->fetchColumn();
        $census['fleet_agents']['active'] = (int)$db->query("SELECT COUNT(*) FROM agent_fleet_tracker WHERE status NOT IN ('idle','offline','maintenance')")->fetchColumn();
        $divs = $db->query("SELECT division, COUNT(*) as cnt FROM agent_fleet_tracker GROUP BY division")->fetchAll(PDO::FETCH_ASSOC);
        $census['fleet_agents']['divisions'] = $divs;
    } catch (Exception $e) {}

    // Trading agents
    try {
        $census['trading_agents']['total'] = (int)$db->query("SELECT COUNT(*) FROM trading_agents")->fetchColumn();
        $census['trading_agents']['active'] = (int)$db->query("SELECT COUNT(*) FROM trading_agents WHERE status = 'active'")->fetchColumn();
        $strats = $db->query("SELECT strategy, COUNT(*) as cnt FROM trading_agents GROUP BY strategy")->fetchAll(PDO::FETCH_ASSOC);
        $census['trading_agents']['strategies'] = $strats;
    } catch (Exception $e) {}

    // Fleet tasks (alfred_fleets)
    try {
        $census['fleet_tasks']['total'] = (int)$db->query("SELECT COUNT(*) FROM alfred_fleets")->fetchColumn();
        $census['fleet_tasks']['running'] = (int)$db->query("SELECT COUNT(*) FROM alfred_fleets WHERE status = 'running'")->fetchColumn();
    } catch (Exception $e) {}

    // PM2
    $pm2 = getPm2Services();
    $census['pm2_services']['total'] = count($pm2);
    foreach ($pm2 as $s) { if ($s['status'] === 'online') $census['pm2_services']['online']++; }

    // Intelligence sources
    try {
        $census['intelligence_sources'] = (int)$db->query("SELECT COUNT(*) FROM intel_sources WHERE active = 1")->fetchColumn();
    } catch (Exception $e) {}

    $census['grand_total'] = $census['fleet_agents']['total'] + $census['trading_agents']['total'] + $census['pm2_services']['total'] + $census['intelligence_sources'];

    echo json_encode(['success' => true, 'census' => $census, 'timestamp' => date('c')]);
}

// ═══════════════════════════════════════════════════════════════
// PULSE — Quick health check (used by heartbeat)
// ═══════════════════════════════════════════════════════════════
function handlePulse($db) {
    $criticals = [];

    // Quick checks
    if (!checkPort(6379)) $criticals[] = 'redis';
    if (!checkPort(3010)) $criticals[] = 'websocket';
    if (!checkPort(7700)) $criticals[] = 'meilisearch';

    try {
        $db->query("SELECT 1");
    } catch (Exception $e) {
        $criticals[] = 'database';
    }

    $freeGB = round(disk_free_space('/') / 1073741824, 1);
    if ($freeGB < 10) $criticals[] = 'disk_low';

    $load = sys_getloadavg();
    $cores = (int)shell_exec('nproc 2>/dev/null') ?: 1;
    if ($load[0] / $cores > 0.9) $criticals[] = 'cpu_high';

    $status = empty($criticals) ? 'NOMINAL' : 'ALERT';

    echo json_encode([
        'status' => $status,
        'criticals' => $criticals,
        'score' => empty($criticals) ? 100 : max(0, 100 - count($criticals) * 15),
        'timestamp' => date('c'),
    ]);
}
