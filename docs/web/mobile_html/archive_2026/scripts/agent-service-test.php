<?php
/**
 * Agent Service Test Runner
 * ═════════════════════════
 * Deploys specialist agents to comprehensively test every service,
 * generates reports, and schedules them in the Veil agenda.
 * 
 * Run: php scripts/agent-service-test.php
 * Cron: Called by autonomy-cron daily briefing
 */
defined('GOSITEME_API') || define('GOSITEME_API', true);
$configPath = __DIR__ . '/../api/config.php';
if (!file_exists($configPath)) { echo "Config not found\n"; exit(1); }
require_once $configPath;

$logFile = __DIR__ . '/../logs/agent-service-test.log';

function logMsg($msg) {
    global $logFile;
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg;
    file_put_contents($logFile, $line . "\n", FILE_APPEND | LOCK_EX);
    echo $line . "\n";
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
} catch (PDOException $e) {
    logMsg("DB connection failed: " . $e->getMessage());
    exit(1);
}

$startTime = microtime(true);
logMsg("═══ AGENT SERVICE TEST RUNNER STARTED ═══");

// ── Service Definitions ──
$services = [
    ['name' => 'Redis Cache', 'port' => 6379, 'type' => 'tcp', 'agent' => 'architect-infra-01'],
    ['name' => 'WebSocket Server', 'port' => 3010, 'type' => 'http', 'url' => 'http://127.0.0.1:3010/health', 'agent' => 'architect-infra-02'],
    ['name' => 'Job Queue', 'port' => 3011, 'type' => 'tcp', 'agent' => 'architect-infra-03'],
    ['name' => 'MCP Client', 'port' => 3005, 'type' => 'http', 'url' => 'http://127.0.0.1:3005/health', 'agent' => 'engineer-01'],
    ['name' => 'MeiliSearch', 'port' => 7700, 'type' => 'http', 'url' => 'http://127.0.0.1:7700/health', 'agent' => 'engineer-02'],
    ['name' => 'GoCodeMe Middleware', 'port' => 3001, 'type' => 'http', 'url' => 'http://127.0.0.1:3001/health', 'agent' => 'engineer-03'],
    ['name' => 'LiveKit Video', 'port' => 7880, 'type' => 'tcp', 'agent' => 'pulse-comms-01'],
    ['name' => 'Ollama AI', 'port' => 11434, 'type' => 'http', 'url' => 'http://127.0.0.1:11434/api/tags', 'agent' => 'engineer-04'],
];

// ── API Endpoint Tests ──
$apiTests = [
    ['name' => 'Alfred Chat API', 'url' => '/api/alfred-chat.php', 'method' => 'GET', 'agent' => 'scout-recon-01'],
    ['name' => 'Auth API', 'url' => '/api/auth.php', 'method' => 'GET', 'agent' => 'cipher-sec-01'],
    ['name' => 'Health API', 'url' => '/api/health.php', 'method' => 'GET', 'agent' => 'sentinel-mon-01'],
    ['name' => 'Veil Reports API', 'url' => '/api/veil-reports.php?action=types', 'method' => 'GET', 'agent' => 'sentinel-mon-02'],
    ['name' => 'Veil Agenda API', 'url' => '/api/veil-agenda.php?action=upcoming', 'method' => 'GET', 'agent' => 'atlas-data-01'],
    ['name' => 'Self-Healing API', 'url' => '/api/self-healing.php?action=services', 'method' => 'GET', 'agent' => 'architect-infra-04'],
    ['name' => 'System Audit API', 'url' => '/api/system-audit.php?quick=1', 'method' => 'GET', 'agent' => 'scout-recon-02'],
    ['name' => 'Evolve Mode API', 'url' => '/api/evolve-mode.php?action=status', 'method' => 'GET', 'agent' => 'forge-build-01'],
];

// ── Page Health Tests ──
$pageTests = [
    ['name' => 'Main Site', 'url' => '/', 'agent' => 'herald-mkt-01'],
    ['name' => 'Dashboard', 'url' => '/dashboard.php', 'agent' => 'herald-mkt-02'],
    ['name' => 'Voice Portal', 'url' => '/voice-portal.php', 'agent' => 'pulse-comms-02'],
    ['name' => 'Marketplace', 'url' => '/marketplace.php', 'agent' => 'herald-mkt-03'],
    ['name' => 'Open-Source Hub', 'url' => '/open-source/', 'agent' => 'engineer-05'],
    ['name' => 'Veil Command Center', 'url' => '/veil/command-center.php', 'agent' => 'sentinel-mon-03'],
];

$results = ['services' => [], 'apis' => [], 'pages' => [], 'summary' => []];
$passed = 0;
$failed = 0;
$warnings = 0;

// ═══════════════════════════════════════════════════════
// PHASE 1: Service Port/Health Tests
// ═══════════════════════════════════════════════════════
logMsg("── Phase 1: Testing " . count($services) . " services...");

foreach ($services as $svc) {
    $testStart = microtime(true);
    $result = ['name' => $svc['name'], 'port' => $svc['port'], 'agent' => $svc['agent']];

    // Port check
    $sock = @fsockopen('127.0.0.1', $svc['port'], $errno, $errstr, 3);
    if ($sock) {
        fclose($sock);
        $result['port_status'] = 'up';
        $result['latency_ms'] = round((microtime(true) - $testStart) * 1000, 1);

        // HTTP health check if available
        if ($svc['type'] === 'http' && !empty($svc['url'])) {
            $ctx = stream_context_create(['http' => ['timeout' => 5, 'ignore_errors' => true]]);
            $httpStart = microtime(true);
            $response = @file_get_contents($svc['url'], false, $ctx);
            $httpLatency = round((microtime(true) - $httpStart) * 1000, 1);
            $httpCode = 0;
            if (isset($http_response_header[0]) && preg_match('/\d{3}/', $http_response_header[0], $m)) {
                $httpCode = (int)$m[0];
            }
            $result['http_status'] = $httpCode;
            $result['http_latency_ms'] = $httpLatency;
            $result['http_ok'] = ($httpCode >= 200 && $httpCode < 400);
            $result['status'] = $result['http_ok'] ? 'healthy' : 'degraded';
            if (!$result['http_ok']) { $warnings++; } else { $passed++; }
        } else {
            $result['status'] = 'up';
            $passed++;
        }
    } else {
        $result['port_status'] = 'down';
        $result['status'] = 'down';
        $result['error'] = $errstr;
        $failed++;
    }

    $results['services'][] = $result;
    $icon = $result['status'] === 'down' ? '🔴' : ($result['status'] === 'degraded' ? '🟡' : '🟢');
    logMsg("  $icon {$svc['name']} (:{$svc['port']}) → {$result['status']}" .
        (isset($result['latency_ms']) ? " ({$result['latency_ms']}ms)" : ''));
}

// ═══════════════════════════════════════════════════════
// PHASE 2: API Endpoint Tests
// ═══════════════════════════════════════════════════════
logMsg("── Phase 2: Testing " . count($apiTests) . " API endpoints...");

$baseUrl = 'https://gositeme.com';
foreach ($apiTests as $api) {
    $testStart = microtime(true);
    $result = ['name' => $api['name'], 'url' => $api['url'], 'agent' => $api['agent']];
    
    $ctx = stream_context_create(['http' => ['timeout' => 10, 'ignore_errors' => true, 'method' => $api['method']],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
    $response = @file_get_contents($baseUrl . $api['url'], false, $ctx);
    $latency = round((microtime(true) - $testStart) * 1000, 1);
    
    $httpCode = 0;
    if (isset($http_response_header[0]) && preg_match('/\d{3}/', $http_response_header[0], $m)) {
        $httpCode = (int)$m[0];
    }
    
    $result['http_code'] = $httpCode;
    $result['latency_ms'] = $latency;
    $result['response_size'] = strlen($response ?: '');
    $result['is_json'] = !empty($response) && json_decode($response) !== null;
    
    if ($httpCode >= 200 && $httpCode < 400) {
        $result['status'] = 'healthy';
        $passed++;
    } elseif ($httpCode === 401 || $httpCode === 403) {
        $result['status'] = 'auth-required';
        $passed++; // Expected for secured endpoints
    } elseif ($httpCode >= 500) {
        $result['status'] = 'error';
        $failed++;
    } else {
        $result['status'] = 'degraded';
        $warnings++;
    }
    
    $results['apis'][] = $result;
    $icon = $result['status'] === 'healthy' || $result['status'] === 'auth-required' ? '🟢' : ($result['status'] === 'error' ? '🔴' : '🟡');
    logMsg("  $icon {$api['name']} → HTTP {$httpCode} ({$latency}ms)");
}

// ═══════════════════════════════════════════════════════
// PHASE 3: Page Health Tests
// ═══════════════════════════════════════════════════════
logMsg("── Phase 3: Testing " . count($pageTests) . " pages...");

foreach ($pageTests as $page) {
    $testStart = microtime(true);
    $result = ['name' => $page['name'], 'url' => $page['url'], 'agent' => $page['agent']];
    
    $ctx = stream_context_create(['http' => ['timeout' => 10, 'ignore_errors' => true],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
    $response = @file_get_contents($baseUrl . $page['url'], false, $ctx);
    $latency = round((microtime(true) - $testStart) * 1000, 1);
    
    $httpCode = 0;
    if (isset($http_response_header[0]) && preg_match('/\d{3}/', $http_response_header[0], $m)) {
        $httpCode = (int)$m[0];
    }
    
    $result['http_code'] = $httpCode;
    $result['latency_ms'] = $latency;
    $result['response_size'] = strlen($response ?: '');
    $result['has_html'] = !empty($response) && stripos($response, '<html') !== false;
    
    if ($httpCode >= 200 && $httpCode < 400) {
        $result['status'] = 'healthy';
        $passed++;
    } elseif ($httpCode === 401 || $httpCode === 403) {
        $result['status'] = 'auth-gated';
        $passed++;
    } else {
        $result['status'] = 'error';
        $failed++;
    }
    
    $results['pages'][] = $result;
    $icon = $result['status'] === 'healthy' || $result['status'] === 'auth-gated' ? '🟢' : '🔴';
    logMsg("  $icon {$page['name']} → HTTP {$httpCode} ({$latency}ms)");
}

// ═══════════════════════════════════════════════════════
// PHASE 4: PM2 Process Details
// ═══════════════════════════════════════════════════════
logMsg("── Phase 4: Collecting PM2 process data...");

$pm2Bin = getenv('HOME') . '/.local/node_modules/.bin/pm2';
$pm2Data = [];
$pm2Json = shell_exec("$pm2Bin jlist 2>/dev/null");
if ($pm2Json) {
    $processes = json_decode($pm2Json, true) ?: [];
    foreach ($processes as $proc) {
        $pm2Data[] = [
            'name' => $proc['name'] ?? 'unknown',
            'status' => $proc['pm2_env']['status'] ?? 'unknown',
            'pid' => $proc['pid'] ?? 0,
            'uptime' => isset($proc['pm2_env']['pm_uptime']) ? round((time() * 1000 - $proc['pm2_env']['pm_uptime']) / 3600000, 1) . 'h' : '?',
            'restarts' => $proc['pm2_env']['restart_time'] ?? 0,
            'memory_mb' => isset($proc['monit']['memory']) ? round($proc['monit']['memory'] / 1048576, 1) : 0,
            'cpu' => $proc['monit']['cpu'] ?? 0,
        ];
    }
}
$results['pm2_processes'] = $pm2Data;
logMsg("  Found " . count($pm2Data) . " PM2 processes");

// ═══════════════════════════════════════════════════════
// PHASE 5: System Resources
// ═══════════════════════════════════════════════════════
logMsg("── Phase 5: System resource check...");

$diskTotal = disk_total_space('/');
$diskFree = disk_free_space('/');
$diskUsedPct = $diskTotal > 0 ? round(($diskTotal - $diskFree) / $diskTotal * 100, 1) : 0;

$memInfo = @file_get_contents('/proc/meminfo');
$memTotal = $memUsed = 0;
if ($memInfo && preg_match('/MemTotal:\s+(\d+)/', $memInfo, $m)) $memTotal = (int)$m[1];
if ($memInfo && preg_match('/MemAvailable:\s+(\d+)/', $memInfo, $m)) $memUsed = $memTotal - (int)$m[1];
$memPct = $memTotal > 0 ? round($memUsed / $memTotal * 100, 1) : 0;

$load = sys_getloadavg();
$cpuCores = (int)shell_exec('nproc 2>/dev/null') ?: 1;

$results['system'] = [
    'disk_total_gb' => round($diskTotal / 1073741824, 1),
    'disk_free_gb' => round($diskFree / 1073741824, 1),
    'disk_used_pct' => $diskUsedPct,
    'memory_total_gb' => round($memTotal / 1048576, 1),
    'memory_used_pct' => $memPct,
    'load_1m' => $load[0] ?? 0,
    'load_5m' => $load[1] ?? 0,
    'load_15m' => $load[2] ?? 0,
    'cpu_cores' => $cpuCores,
];
logMsg("  Disk: {$diskUsedPct}% | Memory: {$memPct}% | Load: {$load[0]}/{$cpuCores} cores");

// ═══════════════════════════════════════════════════════
// SUMMARY & REPORT GENERATION
// ═══════════════════════════════════════════════════════
$duration = round((microtime(true) - $startTime) * 1000);
$totalTests = $passed + $failed + $warnings;
$healthScore = $totalTests > 0 ? round(($passed / $totalTests) * 100) : 0;

$results['summary'] = [
    'total_tests' => $totalTests,
    'passed' => $passed,
    'failed' => $failed,
    'warnings' => $warnings,
    'health_score' => $healthScore,
    'duration_ms' => $duration,
    'timestamp' => date('Y-m-d H:i:s'),
];

logMsg("═══ RESULTS: {$passed}/{$totalTests} passed ({$healthScore}% health) — {$failed} failed, {$warnings} warnings — {$duration}ms ═══");

// Determine severity
$severity = 'info';
if ($failed > 0) $severity = 'critical';
elseif ($warnings > 0) $severity = 'warning';

// Build summary text
$summaryParts = [];
$summaryParts[] = "{$healthScore}% health score";
$summaryParts[] = "{$passed}/{$totalTests} tests passed";
if ($failed > 0) $summaryParts[] = "{$failed} FAILED";
if ($warnings > 0) $summaryParts[] = "{$warnings} warnings";
$summaryText = implode(' • ', $summaryParts);

// Store in veil_reports
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS veil_reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        report_type VARCHAR(50) NOT NULL,
        title VARCHAR(255) NOT NULL,
        summary TEXT,
        content JSON,
        generated_by VARCHAR(100) DEFAULT 'system',
        severity ENUM('info','warning','critical') DEFAULT 'info',
        client_id INT DEFAULT 1,
        read_at DATETIME DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_type (report_type),
        INDEX idx_created (created_at)
    )");

    $stmt = $pdo->prepare("INSERT INTO veil_reports (report_type, title, summary, content, generated_by, severity, client_id) VALUES (?, ?, ?, ?, ?, ?, 1)");
    $stmt->execute([
        'service_health',
        'Agent Service Test — ' . date('M j, g:ia'),
        $summaryText,
        json_encode($results),
        'agent-service-test',
        $severity,
    ]);
    $reportId = $pdo->lastInsertId();
    logMsg("✅ Report saved to veil_reports (ID: {$reportId})");
} catch (Exception $e) {
    logMsg("❌ Failed to save report: " . $e->getMessage());
}

// Also generate morning briefing via the reports API
logMsg("── Generating morning briefing report...");
$internalSecret = defined('INTERNAL_SECRET') ? INTERNAL_SECRET : 'gositeme-internal-2024';
$briefingCtx = stream_context_create(['http' => [
    'method' => 'POST',
    'header' => "X-Internal-Secret: {$internalSecret}\r\nContent-Type: application/json\r\n",
    'timeout' => 15,
    'ignore_errors' => true,
], 'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
$briefingResp = @file_get_contents('https://gositeme.com/api/veil-reports.php?action=generate&type=morning_briefing', false, $briefingCtx);
if ($briefingResp) {
    $bData = json_decode($briefingResp, true);
    if (!empty($bData['success'])) {
        logMsg("✅ Morning briefing generated");
    } else {
        logMsg("⚠️ Morning briefing: " . ($bData['error'] ?? 'unknown error'));
    }
}

// Schedule in Veil Agenda
logMsg("── Scheduling reports in Veil Agenda...");
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS veil_agenda (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL DEFAULT 1,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        event_date DATE NOT NULL,
        event_time TIME DEFAULT NULL,
        category ENUM('meeting','task','reminder','training','security','personal','ops') DEFAULT 'ops',
        priority ENUM('low','medium','high','critical') DEFAULT 'medium',
        status ENUM('pending','in_progress','completed','cancelled') DEFAULT 'pending',
        recurring VARCHAR(20) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    
    // Check if morning briefing already scheduled for tomorrow
    $check = $pdo->prepare("SELECT id FROM veil_agenda WHERE client_id = 1 AND event_date = ? AND title LIKE '%Morning Briefing%' LIMIT 1");
    $check->execute([$tomorrow]);
    if (!$check->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO veil_agenda (client_id, title, description, event_date, event_time, category, priority, recurring) VALUES (1, ?, ?, ?, ?, 'ops', 2, 'daily')");
        $stmt->execute([
            'Morning Briefing — Review Intelligence Reports',
            "Auto-generated service health report ready in Veil Reports Hub.\n\nHealth Score: {$healthScore}% | {$passed}/{$totalTests} passed | {$failed} failed\n\nView: /veil/reports.php",
            $tomorrow,
            '08:00:00',
        ]);
        logMsg("✅ Morning briefing scheduled for $tomorrow 8:00 AM");
    } else {
        logMsg("ℹ️ Morning briefing already scheduled for $tomorrow");
    }

    // Schedule afternoon ecosystem gap analysis
    $check = $pdo->prepare("SELECT id FROM veil_agenda WHERE client_id = 1 AND event_date = ? AND title LIKE '%Ecosystem%Gap%' LIMIT 1");
    $check->execute([$tomorrow]);
    if (!$check->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO veil_agenda (client_id, title, description, event_date, event_time, category, priority) VALUES (1, ?, ?, ?, ?, 'ops', 2)");
        $stmt->execute([
            'Ecosystem Gap Analysis — Afternoon Report',
            "Comprehensive gap analysis of missing tools, revenue streams, platform features, and integrations.\nAgents have prepared upgrade recommendations.\n\nView: /veil/reports.php",
            $tomorrow,
            '14:00:00',
        ]);
        logMsg("✅ Ecosystem gap analysis scheduled for $tomorrow 2:00 PM");
    }

    // Schedule manuals review
    $check = $pdo->prepare("SELECT id FROM veil_agenda WHERE client_id = 1 AND event_date = ? AND title LIKE '%Manuals%Review%' LIMIT 1");
    $check->execute([$tomorrow]);
    if (!$check->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO veil_agenda (client_id, title, description, event_date, event_time, category, priority) VALUES (1, ?, ?, ?, ?, 'ops', 3)");
        $stmt->execute([
            'Manuals Review — All Docs Available in Veil',
            "All manuals and documentation have been linked in the Veil Reports Hub → Manuals tab.\n\nIncluded:\n- Commander Operations Manual (CLASSIFIED)\n- New Member Onboarding Guide\n- Ecosystem Principles Agreement\n- Agent Operations Runbook\n- API Reference & Swagger\n- Tools Guide (1,220+ tools)\n- Voice Integration Guide\n- OIC Whitepaper",
            $tomorrow,
            '09:00:00',
        ]);
        logMsg("✅ Manuals review scheduled for $tomorrow 9:00 AM");
    }

} catch (Exception $e) {
    logMsg("❌ Agenda scheduling failed: " . $e->getMessage());
}

// Generate ecosystem gaps report
logMsg("── Generating ecosystem gaps report...");
$gapCtx = stream_context_create(['http' => [
    'method' => 'POST',
    'header' => "X-Internal-Secret: {$internalSecret}\r\nContent-Type: application/json\r\n",
    'timeout' => 15,
    'ignore_errors' => true,
], 'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
$gapResp = @file_get_contents('https://gositeme.com/api/veil-reports.php?action=generate&type=ecosystem_gaps', false, $gapCtx);
if ($gapResp) {
    $gData = json_decode($gapResp, true);
    if (!empty($gData['success'])) {
        logMsg("✅ Ecosystem gaps report generated");
    }
}

logMsg("═══ AGENT SERVICE TEST COMPLETE ═══\n");
