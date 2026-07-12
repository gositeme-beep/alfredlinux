<?php
/**
 * Alfred Self-Healing Infrastructure API — Phase 2: Self-Evolution
 * ────────────────────────────────────────────────────────────────
 * Auto-detect, diagnose, and fix infrastructure issues.
 * Monitor → Diagnose → Heal → Verify → Report
 *
 * Endpoints:
 *   GET  ?action=health           → Full system health check
 *   GET  ?action=services         → Service status overview
 *   POST ?action=diagnose         → Diagnose a specific issue
 *   POST ?action=heal             → Execute a healing action
 *   GET  ?action=incidents        → Incident history
 *   POST ?action=report-incident  → Report an incident
 *   GET  ?action=rules            → View healing rules
 *   POST ?action=add-rule         → Add an auto-heal rule
 *   GET  ?action=metrics          → System metrics snapshot
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Internal-Secret');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

function requireAuth() {
    if (empty($_SESSION['logged_in']) || empty($_SESSION['client_id'])) jsonResponse(['error' => 'Authentication required'], 401);
}
function isAdmin() { return !empty($_SESSION['is_admin']) || ($_SESSION['client_id'] ?? 0) === 33; }
function isInternalCall() {
    $s = getenv('INTERNAL_SECRET') ?: '';
    return $s && isset($_SERVER['HTTP_X_INTERNAL_SECRET']) && hash_equals($s, $_SERVER['HTTP_X_INTERNAL_SECRET']);
}

function ensureHealingSchema() {
    $db = getDB();
    if (!$db) return false;

    $db->exec("CREATE TABLE IF NOT EXISTS alfred_incidents (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        incident_id     VARCHAR(50) UNIQUE NOT NULL,
        severity        ENUM('info','warning','critical','fatal') DEFAULT 'warning',
        service         VARCHAR(100) NOT NULL,
        description     TEXT NOT NULL,
        diagnosis       JSON DEFAULT NULL,
        healing_action  TEXT DEFAULT NULL,
        healing_result  ENUM('pending','success','failed','manual') DEFAULT 'pending',
        detected_by     VARCHAR(50) DEFAULT 'MONITOR',
        healed_by       VARCHAR(50) DEFAULT NULL,
        resolved_at     TIMESTAMP NULL,
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_severity (severity),
        INDEX idx_service (service),
        INDEX idx_result (healing_result),
        INDEX idx_date (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS alfred_healing_rules (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        rule_name       VARCHAR(100) UNIQUE NOT NULL,
        condition_type  ENUM('cpu','memory','disk','service_down','error_rate','response_time','custom') NOT NULL,
        threshold       VARCHAR(50) NOT NULL,
        healing_action  TEXT NOT NULL,
        cooldown_seconds INT DEFAULT 300,
        last_triggered  TIMESTAMP NULL,
        enabled         BOOLEAN DEFAULT TRUE,
        trigger_count   INT DEFAULT 0,
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_type (condition_type),
        INDEX idx_enabled (enabled)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    return true;
}

// ─── Health Check Helpers ──────────────────────────────────────────
function checkDiskSpace() {
    $total = @disk_total_space('/');
    $free = @disk_free_space('/');
    if (!$total) return ['status' => 'unknown'];
    $used = $total - $free;
    $pct = round(($used / $total) * 100, 1);
    return [
        'status' => $pct > 95 ? 'critical' : ($pct > 85 ? 'warning' : 'healthy'),
        'total_gb' => round($total / 1073741824, 1),
        'free_gb' => round($free / 1073741824, 1),
        'used_pct' => $pct,
    ];
}

function checkMemory() {
    if (!@is_readable('/proc/meminfo')) return ['status' => 'unknown'];
    $mem = @file_get_contents('/proc/meminfo');
    if (!$mem) return ['status' => 'unknown'];
    preg_match('/MemTotal:\s+(\d+)/', $mem, $total);
    preg_match('/MemAvailable:\s+(\d+)/', $mem, $available);
    if (empty($total[1])) return ['status' => 'unknown'];
    $totalMB = $total[1] / 1024;
    $availMB = ($available[1] ?? 0) / 1024;
    $usedPct = round((1 - $availMB / $totalMB) * 100, 1);
    return [
        'status' => $usedPct > 95 ? 'critical' : ($usedPct > 85 ? 'warning' : 'healthy'),
        'total_mb' => round($totalMB),
        'available_mb' => round($availMB),
        'used_pct' => $usedPct,
    ];
}

function checkLoadAvg() {
    try {
        $load = @sys_getloadavg();
    } catch (\Throwable $e) {
        return ['status' => 'unknown'];
    }
    if (!$load) return ['status' => 'unknown'];
    $cores = 1;
    try { $cores = (int)(@shell_exec('nproc') ?: 1); } catch (\Throwable $e) {}
    $normalized = $load[0] / max($cores, 1);
    return [
        'status' => $normalized > 2.0 ? 'critical' : ($normalized > 1.0 ? 'warning' : 'healthy'),
        'load_1m' => $load[0],
        'load_5m' => $load[1],
        'load_15m' => $load[2],
        'cores' => $cores,
        'normalized' => round($normalized, 2),
    ];
}

function checkDatabase() {
    try {
        $db = getDB();
        $start = microtime(true);
        $db->query("SELECT 1");
        $latency = round((microtime(true) - $start) * 1000, 2);
        return [
            'status' => $latency > 500 ? 'warning' : 'healthy',
            'latency_ms' => $latency,
            'connected' => true,
        ];
    } catch (Exception $e) {
        error_log('[self-healing] DB check: ' . $e->getMessage());
        return ['status' => 'critical', 'connected' => false, 'error' => 'Database check failed'];
    }
}

function checkService($name, $port) {
    $start = microtime(true);
    $conn = @fsockopen('127.0.0.1', $port, $errno, $errstr, 2);
    $latency = round((microtime(true) - $start) * 1000, 2);
    if ($conn) {
        fclose($conn);
        return ['status' => 'healthy', 'port' => $port, 'latency_ms' => $latency];
    }
    return ['status' => 'down', 'port' => $port, 'error' => $errstr];
}

$action = sanitize($_GET['action'] ?? '', 30);
$db = getDB();
if (!$db) jsonResponse(['error' => 'Database unavailable'], 503);
ensureHealingSchema();

switch ($action) {

    case 'health':
        if (!isInternalCall()) requireAuth();

        $health = [];
        try { $health['disk'] = checkDiskSpace(); } catch (\Throwable $e) { error_log('[self-healing] disk: ' . $e->getMessage()); $health['disk'] = ['status' => 'unknown', 'error' => 'Check failed']; }
        try { $health['memory'] = checkMemory(); } catch (\Throwable $e) { error_log('[self-healing] memory: ' . $e->getMessage()); $health['memory'] = ['status' => 'unknown', 'error' => 'Check failed']; }
        try { $health['cpu'] = checkLoadAvg(); } catch (\Throwable $e) { error_log('[self-healing] cpu: ' . $e->getMessage()); $health['cpu'] = ['status' => 'unknown', 'error' => 'Check failed']; }
        try { $health['database'] = checkDatabase(); } catch (\Throwable $e) { error_log('[self-healing] db: ' . $e->getMessage()); $health['database'] = ['status' => 'unknown', 'error' => 'Check failed']; }
        $health['php'] = [
            'status' => 'healthy',
            'version' => PHP_VERSION,
            'memory_usage_mb' => round(memory_get_usage(true) / 1048576, 1),
            'memory_limit' => ini_get('memory_limit'),
        ];

        // Overall status
        $statuses = array_column(array_values($health), 'status');
        $overall = 'healthy';
        if (in_array('warning', $statuses)) $overall = 'warning';
        if (in_array('critical', $statuses)) $overall = 'critical';
        if (in_array('down', $statuses)) $overall = 'critical';

        jsonResponse(['success' => true, 'overall' => $overall, 'health' => $health, 'checked_at' => date('c')]);
        break;

    case 'services':
        if (!isInternalCall()) requireAuth();

        $services = [
            'redis'      => checkService('Redis', 6379),
            'websocket'  => checkService('WebSocket', 3010),
            'job_queue'  => checkService('Job Queue', 3011),
            'mcp_server' => checkService('MCP Server', 3005),
            'middleware'  => checkService('Middleware', 3001),
            'meilisearch' => checkService('MeiliSearch', 7700),
            'livekit'    => checkService('LiveKit', 7880),
        ];

        // Check Ollama via HTTP
        $ollamaOk = @file_get_contents('http://127.0.0.1:11434/api/tags', false, stream_context_create(['http' => ['timeout' => 2]]));
        $services['ollama'] = $ollamaOk !== false
            ? ['status' => 'healthy', 'port' => 11434]
            : ['status' => 'down', 'port' => 11434, 'error' => 'HTTP check failed'];

        // Check PM2 processes
        $pm2 = @shell_exec('pm2 jlist 2>/dev/null');
        if ($pm2) {
            $processes = json_decode($pm2, true);
            if (is_array($processes)) {
                foreach ($processes as $p) {
                    $services['pm2_' . ($p['name'] ?? 'unknown')] = [
                        'status' => ($p['pm2_env']['status'] ?? '') === 'online' ? 'healthy' : 'down',
                        'pid' => $p['pid'] ?? null,
                        'uptime' => $p['pm2_env']['pm_uptime'] ?? null,
                        'restarts' => $p['pm2_env']['restart_time'] ?? 0,
                        'memory_mb' => round(($p['monit']['memory'] ?? 0) / 1048576, 1),
                        'cpu' => $p['monit']['cpu'] ?? 0,
                    ];
                }
            }
        }

        jsonResponse(['success' => true, 'services' => $services]);
        break;

    case 'diagnose':
        if (!isInternalCall()) { requireAuth(); if (!isAdmin()) jsonResponse(['error' => 'Admin required'], 403); }

        $input = json_decode(file_get_contents('php://input'), true);
        $service = sanitize($input['service'] ?? '', 100);
        if (!$service) jsonResponse(['error' => 'service required'], 400);

        $diagnosis = ['service' => $service, 'checks' => []];

        // Run relevant checks
        $diagnosis['checks']['disk'] = checkDiskSpace();
        $diagnosis['checks']['memory'] = checkMemory();
        $diagnosis['checks']['cpu'] = checkLoadAvg();
        $diagnosis['checks']['database'] = checkDatabase();

        // Check recent errors in logs
        $logFile = __DIR__ . '/../logs/error.log';
        $recentErrors = [];
        if (file_exists($logFile)) {
            $lines = array_slice(file($logFile, FILE_IGNORE_NEW_LINES), -20);
            foreach ($lines as $line) {
                if (stripos($line, $service) !== false || stripos($line, 'error') !== false) {
                    $recentErrors[] = $line;
                }
            }
        }
        $diagnosis['recent_errors'] = array_slice($recentErrors, -5);

        // Recommendations
        $recommendations = [];
        if (($diagnosis['checks']['disk']['used_pct'] ?? 0) > 85) $recommendations[] = 'Disk space running low — consider cleanup or expansion';
        if (($diagnosis['checks']['memory']['used_pct'] ?? 0) > 85) $recommendations[] = 'Memory usage high — check for memory leaks';
        if (($diagnosis['checks']['cpu']['normalized'] ?? 0) > 1.0) $recommendations[] = 'CPU load high — consider scaling or optimizing';
        if (($diagnosis['checks']['database']['latency_ms'] ?? 0) > 200) $recommendations[] = 'Database latency elevated — check slow queries';

        $diagnosis['recommendations'] = $recommendations;
        $diagnosis['overall'] = empty($recommendations) ? 'healthy' : 'needs_attention';

        jsonResponse(['success' => true, 'diagnosis' => $diagnosis]);
        break;

    case 'heal':
        if (!isInternalCall()) { requireAuth(); if (!isAdmin()) jsonResponse(['error' => 'Admin required'], 403); }

        $input = json_decode(file_get_contents('php://input'), true);
        $incidentId = sanitize($input['incident_id'] ?? '', 50);
        $healingAction = sanitize($input['healing_action'] ?? '', 200);

        if (!$incidentId) jsonResponse(['error' => 'incident_id required'], 400);

        // Available safe healing actions
        $safeActions = [
            'restart_pm2_process' => 'Restart a PM2-managed service',
            'clear_cache' => 'Clear PHP opcache and application cache',
            'optimize_db' => 'Run OPTIMIZE TABLE on key tables',
            'clear_logs' => 'Rotate and compress old log files',
            'reset_agent' => 'Reset a stuck agent to idle state',
        ];

        if ($healingAction && !isset($safeActions[$healingAction])) {
            jsonResponse(['error' => 'Unknown healing action', 'available_actions' => $safeActions], 400);
        }

        $result = 'pending';
        $details = '';

        switch ($healingAction) {
            case 'restart_pm2_process':
                $processName = sanitize($input['process_name'] ?? '', 50);
                if (!$processName) {
                    $details = 'process_name required';
                    $result = 'failed';
                    break;
                }
                // Whitelist of safe PM2 process names
                $allowedProcesses = [
                    'redis', 'alfred-ws', 'alfred-jobs', 'alfred-mcp',
                    'alfred-heartbeat', 'meilisearch', 'gocodeme-middleware',
                    'livekit', 'gocodeme-scheduler',
                ];
                if (!in_array($processName, $allowedProcesses, true)) {
                    $details = 'Process not in allowed list';
                    $result = 'failed';
                    break;
                }
                $pm2Bin = '/home/gositeme/.local/node_modules/.bin/pm2';
                $escapedName = escapeshellarg($processName);
                $output = shell_exec("{$pm2Bin} restart {$escapedName} 2>&1");
                // Verify it came back online
                sleep(2);
                $verify = shell_exec("{$pm2Bin} jlist 2>/dev/null");
                $procs = json_decode($verify, true) ?: [];
                $online = false;
                foreach ($procs as $p) {
                    if (($p['name'] ?? '') === $processName && ($p['pm2_env']['status'] ?? '') === 'online') {
                        $online = true;
                        break;
                    }
                }
                $result = $online ? 'success' : 'failed';
                $details = $online
                    ? "Restarted {$processName} — confirmed online"
                    : "Restart attempted for {$processName} — not confirmed online. Output: " . substr($output, 0, 200);
                break;

            case 'clear_cache':
                if (function_exists('opcache_reset')) opcache_reset();
                $cacheDir = __DIR__ . '/../cache/';
                if (is_dir($cacheDir)) {
                    $files = glob($cacheDir . '*.cache');
                    foreach ($files as $f) @unlink($f);
                    $details = 'Cleared opcache + ' . count($files) . ' cache files';
                }
                $result = 'success';
                break;

            case 'optimize_db':
                $tables = ['alfred_agent_tasks', 'alfred_agent_messages', 'alfred_feed_items', 'alfred_decisions'];
                foreach ($tables as $t) {
                    try { $db->exec("OPTIMIZE TABLE {$t}"); } catch (Exception $e) {}
                }
                $details = 'Optimized ' . count($tables) . ' tables';
                $result = 'success';
                break;

            case 'reset_agent':
                $agentId = sanitize($input['agent_id'] ?? '', 50);
                if ($agentId) {
                    $db->prepare("UPDATE alfred_agent_registry SET status = 'idle', current_task = NULL WHERE agent_id = ?")->execute([$agentId]);
                    $details = "Reset agent {$agentId} to idle";
                    $result = 'success';
                }
                break;

            case 'clear_logs':
                $logDir = __DIR__ . '/../logs/';
                $compressed = 0;
                if (is_dir($logDir)) {
                    $logFiles = glob($logDir . '*.log');
                    foreach ($logFiles as $lf) {
                        $size = filesize($lf);
                        if ($size > 5 * 1024 * 1024) { // >5MB — compress old content
                            $content = file_get_contents($lf);
                            $lines = explode("\n", $content);
                            // Keep last 500 lines, archive the rest
                            if (count($lines) > 500) {
                                $archive = implode("\n", array_slice($lines, 0, -500));
                                $archivePath = $lf . '.' . date('Ymd-His') . '.gz';
                                file_put_contents('compress.zlib://' . $archivePath, $archive);
                                file_put_contents($lf, implode("\n", array_slice($lines, -500)));
                                $compressed++;
                            }
                        }
                    }
                }
                $details = "Rotated {$compressed} log files (kept last 500 lines each)";
                $result = 'success';
                break;

            default:
                $details = 'Action queued for execution';
                $result = 'pending';
        }

        // Update incident
        $db->prepare("UPDATE alfred_incidents SET healing_action = ?, healing_result = ?, healed_by = ?, resolved_at = CASE WHEN ? = 'success' THEN NOW() ELSE NULL END WHERE incident_id = ?")->execute([
            $healingAction, $result, 'ARCHITECT-MONITOR', $result, $incidentId
        ]);

        jsonResponse(['success' => true, 'result' => $result, 'details' => $details]);
        break;

    case 'incidents':
        if (!isInternalCall()) requireAuth();

        $limit = min(max(intval($_GET['limit'] ?? 25), 1), 100);
        $severity = sanitize($_GET['severity'] ?? '', 20);

        $sql = "SELECT * FROM alfred_incidents WHERE 1=1";
        $params = [];
        if ($severity) { $sql .= " AND severity = ?"; $params[] = $severity; }
        $sql .= " ORDER BY created_at DESC LIMIT ?";
        $params[] = $limit;

        $stmt = $db->prepare($sql);
        dbExecute($stmt, $params);
        $incidents = $stmt->fetchAll();

        foreach ($incidents as &$i) $i['diagnosis'] = json_decode($i['diagnosis'], true);

        jsonResponse(['success' => true, 'incidents' => $incidents]);
        break;

    case 'report-incident':
        if (!isInternalCall()) { requireAuth(); if (!isAdmin()) jsonResponse(['error' => 'Admin required'], 403); }

        $input = json_decode(file_get_contents('php://input'), true);
        $service = sanitize($input['service'] ?? '', 100);
        $description = sanitize($input['description'] ?? '', 1000);
        if (!$service || !$description) jsonResponse(['error' => 'service and description required'], 400);

        $incidentId = 'INC-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
        $severity = sanitize($input['severity'] ?? 'warning', 20);
        if (!in_array($severity, ['info','warning','critical','fatal'])) $severity = 'warning';

        $stmt = $db->prepare("INSERT INTO alfred_incidents (incident_id, severity, service, description, detected_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$incidentId, $severity, $service, $description, sanitize($input['detected_by'] ?? 'MONITOR', 50)]);

        // Auto-heal if a matching rule exists
        $rule = $db->prepare("SELECT * FROM alfred_healing_rules WHERE enabled = 1 AND condition_type = 'custom' AND rule_name LIKE ? AND (last_triggered IS NULL OR TIMESTAMPDIFF(SECOND, last_triggered, NOW()) >= cooldown_seconds) LIMIT 1");
        $rule->execute(['%' . $service . '%']);
        $matchedRule = $rule->fetch();

        $autoHealed = false;
        if ($matchedRule) {
            $db->prepare("UPDATE alfred_healing_rules SET last_triggered = NOW(), trigger_count = trigger_count + 1 WHERE id = ?")->execute([$matchedRule['id']]);
            $autoHealed = true;
        }

        jsonResponse(['success' => true, 'incident_id' => $incidentId, 'auto_heal_triggered' => $autoHealed]);
        break;

    case 'rules':
        if (!isInternalCall()) requireAuth();
        $rules = $db->query("SELECT * FROM alfred_healing_rules ORDER BY condition_type, rule_name")->fetchAll();
        jsonResponse(['success' => true, 'rules' => $rules]);
        break;

    case 'add-rule':
        if (!isInternalCall()) { requireAuth(); if (!isAdmin()) jsonResponse(['error' => 'Admin required'], 403); }

        $input = json_decode(file_get_contents('php://input'), true);
        $ruleName = sanitize($input['rule_name'] ?? '', 100);
        $condType = sanitize($input['condition_type'] ?? '', 20);
        $threshold = sanitize($input['threshold'] ?? '', 50);
        $healAction = sanitize($input['healing_action'] ?? '', 500);

        if (!$ruleName || !$condType || !$threshold || !$healAction) {
            jsonResponse(['error' => 'rule_name, condition_type, threshold, and healing_action required'], 400);
        }

        $validTypes = ['cpu','memory','disk','service_down','error_rate','response_time','custom'];
        if (!in_array($condType, $validTypes)) jsonResponse(['error' => 'Invalid condition_type'], 400);

        $stmt = $db->prepare("INSERT INTO alfred_healing_rules (rule_name, condition_type, threshold, healing_action, cooldown_seconds) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE threshold = VALUES(threshold), healing_action = VALUES(healing_action)");
        $stmt->execute([$ruleName, $condType, $threshold, $healAction, intval($input['cooldown_seconds'] ?? 300)]);

        jsonResponse(['success' => true, 'rule_name' => $ruleName]);
        break;

    case 'metrics':
        if (!isInternalCall()) requireAuth();

        $totalIncidents = $db->query("SELECT COUNT(*) FROM alfred_incidents")->fetchColumn();
        $openIncidents = $db->query("SELECT COUNT(*) FROM alfred_incidents WHERE healing_result = 'pending'")->fetchColumn();
        $autoHealed = $db->query("SELECT COUNT(*) FROM alfred_incidents WHERE healing_result = 'success'")->fetchColumn();
        $bySeverity = $db->query("SELECT severity, COUNT(*) as c FROM alfred_incidents GROUP BY severity")->fetchAll(PDO::FETCH_KEY_PAIR);
        $last24h = $db->query("SELECT COUNT(*) FROM alfred_incidents WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();

        jsonResponse([
            'success' => true,
            'total_incidents' => (int)$totalIncidents,
            'open_incidents' => (int)$openIncidents,
            'auto_healed' => (int)$autoHealed,
            'heal_rate' => $totalIncidents > 0 ? round(($autoHealed / $totalIncidents) * 100, 1) : 0,
            'by_severity' => $bySeverity,
            'last_24h' => (int)$last24h,
            'system' => [
                'disk' => checkDiskSpace(),
                'memory' => checkMemory(),
                'cpu' => checkLoadAvg(),
            ],
        ]);
        break;

    default:
        jsonResponse(['error' => 'Unknown action', 'available_actions' => ['health','services','diagnose','heal','incidents','report-incident','rules','add-rule','metrics']], 400);
}
