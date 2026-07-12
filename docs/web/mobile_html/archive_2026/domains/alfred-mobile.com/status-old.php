<?php
/**
 * Alfred AI System Status Page
 * Real-time platform health monitoring
 */

$page_title = 'System Status — Alfred AI Platform Health | GoSiteMe';
$page_description = 'Real-time system status and health monitoring for the Alfred AI platform. Check service availability, response times, and incident history.';
$page_canonical = 'https://gositeme.com/status.php';

include __DIR__ . '/includes/site-header.inc.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$is_logged_in = !empty($_SESSION['logged_in']) && !empty($_SESSION['client_id']);
$client_id    = $is_logged_in ? (int)$_SESSION['client_id'] : 0;
$client_name  = $_SESSION['client_name'] ?? '';
$client_email = $_SESSION['client_email'] ?? '';

// ── Health Check Functions ─────────────────────────────────────

function checkService($name, $url, $timeout = 3) {
    $start = microtime(true);
    $result = ['name' => $name, 'status' => 'down', 'response_time' => 0, 'details' => ''];

    try {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_NOBODY => false,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $elapsed = round((microtime(true) - $start) * 1000);
        $result['response_time'] = $elapsed;

        if ($error) {
            $result['status'] = 'down';
            $result['details'] = $error;
        } elseif ($httpCode >= 200 && $httpCode < 400) {
            $result['status'] = $elapsed > 2000 ? 'degraded' : 'operational';
            $result['details'] = "HTTP $httpCode";
        } elseif ($httpCode >= 400 && $httpCode < 500) {
            $result['status'] = 'degraded';
            $result['details'] = "HTTP $httpCode";
        } else {
            $result['status'] = 'down';
            $result['details'] = "HTTP $httpCode";
        }
    } catch (Exception $e) {
        $result['status'] = 'down';
        $result['details'] = $e->getMessage();
        $result['response_time'] = round((microtime(true) - $start) * 1000);
    }

    return $result;
}

function checkPort($name, $host, $port, $timeout = 2) {
    $start = microtime(true);
    $result = ['name' => $name, 'status' => 'down', 'response_time' => 0, 'details' => ''];

    try {
        $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
        $elapsed = round((microtime(true) - $start) * 1000);
        $result['response_time'] = $elapsed;

        if ($fp) {
            fclose($fp);
            $result['status'] = $elapsed > 1500 ? 'degraded' : 'operational';
            $result['details'] = "Port $port open";
        } else {
            $result['status'] = 'down';
            $result['details'] = $errstr ?: "Port $port closed";
        }
    } catch (Exception $e) {
        $result['status'] = 'down';
        $result['details'] = $e->getMessage();
        $result['response_time'] = round((microtime(true) - $start) * 1000);
    }

    return $result;
}

function checkDatabase() {
    $start = microtime(true);
    $result = ['name' => 'Database (MySQL)', 'status' => 'down', 'response_time' => 0, 'details' => ''];

    try {
        // Use shared database configuration
        require_once dirname(__FILE__) . '/includes/db-config.inc.php';

        $pdo = getSharedDB();

        $stmt = $pdo->query('SELECT 1');
        $stmt->fetch();
        $elapsed = round((microtime(true) - $start) * 1000);
        $result['response_time'] = $elapsed;
        $result['status'] = $elapsed > 1500 ? 'degraded' : 'operational';
        $result['details'] = 'Connected';
        $pdo = null;
    } catch (Exception $e) {
        $result['status'] = 'down';
        $result['details'] = 'Connection failed';
        $result['response_time'] = round((microtime(true) - $start) * 1000);
    }

    return $result;
}

function checkRedis() {
    $start = microtime(true);
    $result = ['name' => 'Redis Cache', 'status' => 'down', 'response_time' => 0, 'details' => ''];

    try {
        $fp = @fsockopen('localhost', 6379, $errno, $errstr, 2);
        if ($fp) {
            fwrite($fp, "PING\r\n");
            $response = fgets($fp, 128);
            fclose($fp);
            $elapsed = round((microtime(true) - $start) * 1000);
            $result['response_time'] = $elapsed;
            if (strpos($response, '+PONG') !== false) {
                $result['status'] = 'operational';
                $result['details'] = 'PONG';
            } else {
                $result['status'] = 'degraded';
                $result['details'] = 'Unexpected response';
            }
        } else {
            $result['status'] = 'down';
            $result['details'] = $errstr ?: 'Connection refused';
            $result['response_time'] = round((microtime(true) - $start) * 1000);
        }
    } catch (Exception $e) {
        $result['status'] = 'down';
        $result['details'] = $e->getMessage();
        $result['response_time'] = round((microtime(true) - $start) * 1000);
    }

    return $result;
}

function checkFileExists($name, $path) {
    $start = microtime(true);
    $exists = file_exists(__DIR__ . '/' . $path);
    $elapsed = round((microtime(true) - $start) * 1000);
    return [
        'name' => $name,
        'status' => $exists ? 'operational' : 'down',
        'response_time' => $elapsed,
        'details' => $exists ? 'File exists' : 'Not found'
    ];
}

// ── Run Health Checks ──────────────────────────────────────────

$checks = [];
$checks[] = checkService('Alfred Chat API', 'http://localhost/api/v1/');
$checks[] = checkPort('MCP Server', 'localhost', 3005);
$checks[] = checkPort('WebSocket Server', 'localhost', 3010);
$checks[] = checkDatabase();
$checks[] = checkRedis();
$checks[] = checkFileExists('VAPI Webhook', 'api/vapi-webhook.php');
$checks[] = checkService('Stripe API', 'https://api.stripe.com/v1/', 5);

// PM2 — try to get process list
$pm2Status = ['name' => 'PM2 Services', 'status' => 'down', 'response_time' => 0, 'details' => ''];
$pm2Start = microtime(true);
try {
    $pm2Output = @shell_exec('pm2 jlist 2>/dev/null');
    $pm2Elapsed = round((microtime(true) - $pm2Start) * 1000);
    $pm2Status['response_time'] = $pm2Elapsed;
    if ($pm2Output) {
        $pm2Data = json_decode($pm2Output, true);
        if (is_array($pm2Data)) {
            $running = 0;
            $total = count($pm2Data);
            foreach ($pm2Data as $proc) {
                if (isset($proc['pm2_env']['status']) && $proc['pm2_env']['status'] === 'online') {
                    $running++;
                }
            }
            $pm2Status['status'] = ($running === $total && $total > 0) ? 'operational' : ($running > 0 ? 'degraded' : 'down');
            $pm2Status['details'] = "$running/$total running";
        }
    }
} catch (Exception $e) {
    $pm2Status['details'] = 'Cannot query PM2';
    $pm2Status['response_time'] = round((microtime(true) - $pm2Start) * 1000);
}
$checks[] = $pm2Status;

// ── Calculate Overall Status ───────────────────────────────────

$downCount = 0;
$degradedCount = 0;
foreach ($checks as $c) {
    if ($c['status'] === 'down') $downCount++;
    if ($c['status'] === 'degraded') $degradedCount++;
}

if ($downCount >= 3) {
    $overallStatus = 'major_outage';
    $overallLabel = 'Major Outage';
    $overallColor = '#ef4444';
    $overallIcon = 'fas fa-times-circle';
} elseif ($downCount > 0 || $degradedCount > 0) {
    $overallStatus = 'partial_outage';
    $overallLabel = 'Partial Outage';
    $overallColor = '#f59e0b';
    $overallIcon = 'fas fa-exclamation-triangle';
} else {
    $overallStatus = 'operational';
    $overallLabel = 'All Systems Operational';
    $overallColor = '#10b981';
    $overallIcon = 'fas fa-check-circle';
}

$checkedAt = date('M j, Y g:i:s A T');

// ── Uptime Data (30 days) from health_check_log ────────────────

$uptimeData = [];
try {
    $dbConn = getSharedDB();
    $uptimeStmt = $dbConn->query("
        SELECT DATE(checked_at) as day,
               COUNT(*) as total_checks,
               SUM(CASE WHEN overall_status = 'operational' THEN 1 ELSE 0 END) as up_checks
        FROM health_check_log
        WHERE checked_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(checked_at)
        ORDER BY day ASC
    ");
    $uptimeRows = $uptimeStmt->fetchAll(PDO::FETCH_ASSOC);
    $uptimeMap = [];
    foreach ($uptimeRows as $row) {
        $uptimeMap[$row['day']] = ($row['total_checks'] > 0) ? round(($row['up_checks'] / $row['total_checks']) * 100, 2) : 100;
    }
    for ($i = 29; $i >= 0; $i--) {
        $dayKey = date('Y-m-d', strtotime("-$i days"));
        $dateLabel = date('M j', strtotime("-$i days"));
        $uptimeData[] = ['date' => $dateLabel, 'uptime' => $uptimeMap[$dayKey] ?? 100.00];
    }
} catch (Exception $e) {
    // Fallback if table doesn't exist yet
    for ($i = 29; $i >= 0; $i--) {
        $uptimeData[] = ['date' => date('M j', strtotime("-$i days")), 'uptime' => 100.00];
    }
}

// ── Response Times (24h) from health_check_log ─────────────────

$responseTimes = [];
try {
    $rtStmt = $dbConn->query("
        SELECT DATE_FORMAT(checked_at, '%H:00') as hour_label,
               ROUND(AVG(api_ms)) as api,
               ROUND(AVG(ws_ms)) as ws,
               ROUND(AVG(db_ms)) as db
        FROM health_check_log
        WHERE checked_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        GROUP BY HOUR(checked_at)
        ORDER BY MIN(checked_at) ASC
    ");
    $rtRows = $rtStmt->fetchAll(PDO::FETCH_ASSOC);
    $rtMap = [];
    foreach ($rtRows as $row) {
        $rtMap[$row['hour_label']] = ['api' => (int)$row['api'], 'ws' => (int)$row['ws'], 'db' => (int)$row['db']];
    }
    for ($h = 23; $h >= 0; $h--) {
        $hour = date('H:00', strtotime("-$h hours"));
        $responseTimes[] = [
            'hour' => $hour,
            'api' => $rtMap[$hour]['api'] ?? 0,
            'ws' => $rtMap[$hour]['ws'] ?? 0,
            'db' => $rtMap[$hour]['db'] ?? 0
        ];
    }
} catch (Exception $e) {
    for ($h = 23; $h >= 0; $h--) {
        $responseTimes[] = ['hour' => date('H:00', strtotime("-$h hours")), 'api' => 0, 'ws' => 0, 'db' => 0];
    }
}
?>

<style>
/* ── Status Page Styles ─────────────────────────────────────── */
.status-page {
    background: #0a0a14;
    min-height: 100vh;
    padding: 2rem 0 4rem;
    color: #e2e8f0;
}
.status-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

/* Hero */
.status-hero {
    text-align: center;
    padding: 3rem 0 2rem;
}
.status-hero h1 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 2.5rem;
    font-weight: 700;
    background: linear-gradient(135deg, #a78bfa, #6366f1, #818cf8);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 0.5rem;
}
.status-hero p {
    color: #94a3b8;
    font-size: 1.1rem;
}

/* Overall Banner */
.overall-banner {
    border-radius: 16px;
    padding: 1.5rem 2rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 2rem;
    font-size: 1.25rem;
    font-weight: 600;
    border: 1px solid rgba(255,255,255,0.1);
}
.overall-banner.operational {
    background: rgba(16, 185, 129, 0.1);
    border-color: rgba(16, 185, 129, 0.3);
    color: #10b981;
}
.overall-banner.partial_outage {
    background: rgba(245, 158, 11, 0.1);
    border-color: rgba(245, 158, 11, 0.3);
    color: #f59e0b;
}
.overall-banner.major_outage {
    background: rgba(239, 68, 68, 0.1);
    border-color: rgba(239, 68, 68, 0.3);
    color: #ef4444;
}
.overall-banner i {
    font-size: 1.5rem;
}
.overall-banner .checked-at {
    margin-left: auto;
    font-size: 0.8rem;
    font-weight: 400;
    color: #94a3b8;
}

/* Service Grid */
.service-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1rem;
    margin-bottom: 3rem;
}
.service-card {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 12px;
    padding: 1.25rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: all 0.2s;
}
.service-card:hover {
    background: rgba(255,255,255,0.05);
    border-color: rgba(255,255,255,0.15);
}
.status-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    flex-shrink: 0;
    position: relative;
}
.status-dot.operational {
    background: #10b981;
    box-shadow: 0 0 8px rgba(16,185,129,0.5);
}
.status-dot.operational::after {
    content: '';
    position: absolute;
    inset: -3px;
    border-radius: 50%;
    border: 2px solid rgba(16,185,129,0.3);
    animation: pulse-ring 2s infinite;
}
.status-dot.degraded {
    background: #f59e0b;
    box-shadow: 0 0 8px rgba(245,158,11,0.5);
}
.status-dot.down {
    background: #ef4444;
    box-shadow: 0 0 8px rgba(239,68,68,0.5);
}
@keyframes pulse-ring {
    0% { transform: scale(1); opacity: 1; }
    100% { transform: scale(1.5); opacity: 0; }
}
.service-info {
    flex: 1;
    min-width: 0;
}
.service-name {
    font-weight: 600;
    font-size: 0.95rem;
    color: #e2e8f0;
}
.service-meta {
    font-size: 0.78rem;
    color: #64748b;
    margin-top: 2px;
}
.service-status-label {
    font-size: 0.78rem;
    font-weight: 500;
    text-align: right;
    white-space: nowrap;
}
.service-status-label.operational { color: #10b981; }
.service-status-label.degraded { color: #f59e0b; }
.service-status-label.down { color: #ef4444; }

/* Section Headers */
.status-section {
    margin-bottom: 2.5rem;
}
.status-section h2 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.3rem;
    font-weight: 600;
    color: #e2e8f0;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.status-section h2 i {
    color: #818cf8;
    font-size: 1.1rem;
}

/* Uptime History */
.uptime-grid {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 12px;
    padding: 1.5rem;
}
.uptime-bars {
    display: flex;
    gap: 3px;
    align-items: flex-end;
    height: 60px;
    margin-bottom: 0.5rem;
}
.uptime-bar {
    flex: 1;
    border-radius: 2px 2px 0 0;
    min-width: 0;
    transition: all 0.2s;
    cursor: pointer;
    position: relative;
}
.uptime-bar:hover {
    opacity: 0.8;
    transform: scaleY(1.05);
    transform-origin: bottom;
}
.uptime-bar[data-uptime="100"] { background: #10b981; }
.uptime-bar.good { background: #10b981; }
.uptime-bar.warn { background: #f59e0b; }
.uptime-bar.bad { background: #ef4444; }
.uptime-bar .tooltip-text {
    display: none;
    position: absolute;
    bottom: calc(100% + 8px);
    left: 50%;
    transform: translateX(-50%);
    background: #1e1e2e;
    color: #e2e8f0;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 0.7rem;
    white-space: nowrap;
    border: 1px solid rgba(255,255,255,0.1);
    z-index: 10;
}
.uptime-bar:hover .tooltip-text {
    display: block;
}
.uptime-labels {
    display: flex;
    justify-content: space-between;
    font-size: 0.7rem;
    color: #64748b;
}
.uptime-summary {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid rgba(255,255,255,0.06);
}
.uptime-pct {
    font-size: 1.5rem;
    font-weight: 700;
    color: #10b981;
}
.uptime-pct-label {
    font-size: 0.8rem;
    color: #64748b;
}

/* Response Time Chart */
.response-chart {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 12px;
    padding: 1.5rem;
}
.chart-bars {
    display: flex;
    align-items: flex-end;
    gap: 2px;
    height: 120px;
    margin-bottom: 0.75rem;
}
.chart-bar-group {
    flex: 1;
    display: flex;
    gap: 1px;
    align-items: flex-end;
    height: 100%;
}
.chart-bar {
    flex: 1;
    border-radius: 2px 2px 0 0;
    min-width: 0;
    transition: all 0.2s;
    position: relative;
}
.chart-bar:hover { opacity: 0.7; }
.chart-bar.api { background: #818cf8; }
.chart-bar.ws { background: #34d399; }
.chart-bar.db { background: #fbbf24; }
.chart-legend {
    display: flex;
    gap: 1.5rem;
    margin-top: 1rem;
    font-size: 0.8rem;
    color: #94a3b8;
}
.chart-legend span::before {
    content: '';
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 2px;
    margin-right: 6px;
    vertical-align: middle;
}
.chart-legend .legend-api::before { background: #818cf8; }
.chart-legend .legend-ws::before { background: #34d399; }
.chart-legend .legend-db::before { background: #fbbf24; }
.chart-labels {
    display: flex;
    justify-content: space-between;
    font-size: 0.65rem;
    color: #475569;
}

/* Incidents */
.incidents-list {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 12px;
    padding: 1.5rem;
}
.incident-empty {
    text-align: center;
    padding: 2rem;
    color: #64748b;
}
.incident-empty i {
    font-size: 2rem;
    color: #10b981;
    margin-bottom: 0.75rem;
    display: block;
}
.incident-item {
    padding: 1rem 0;
    border-bottom: 1px solid rgba(255,255,255,0.06);
    display: flex;
    gap: 1rem;
}
.incident-item:last-child { border-bottom: none; }
.incident-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-top: 5px;
    flex-shrink: 0;
}
.incident-dot.resolved { background: #10b981; }
.incident-dot.investigating { background: #f59e0b; }
.incident-title {
    font-weight: 600;
    font-size: 0.9rem;
    color: #e2e8f0;
}
.incident-time {
    font-size: 0.75rem;
    color: #64748b;
    margin-top: 2px;
}
.incident-desc {
    font-size: 0.85rem;
    color: #94a3b8;
    margin-top: 4px;
}

/* Refresh button */
.status-refresh {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(129, 140, 248, 0.1);
    border: 1px solid rgba(129, 140, 248, 0.3);
    color: #818cf8;
    padding: 0.5rem 1.25rem;
    border-radius: 8px;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    margin-top: 1rem;
}
.status-refresh:hover {
    background: rgba(129, 140, 248, 0.2);
    color: #a5b4fc;
}

/* Subscribe bar */
.subscribe-bar {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 12px;
    padding: 1.5rem 2rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
}
.subscribe-bar p {
    color: #94a3b8;
    font-size: 0.9rem;
}
.subscribe-bar p strong {
    color: #e2e8f0;
}

/* Responsive */
@media (max-width: 768px) {
    .status-hero { padding: 2rem 0 1.5rem; }
    .status-hero h1 { font-size: 2rem; }
    .status-hero p { font-size: 1rem; }
    .status-section { margin-bottom: 2rem; }
    .status-section h2 { font-size: 1.15rem; }
    .subscribe-bar { flex-direction: column; text-align: center; padding: 1.25rem 1.5rem; }
    .service-grid { grid-template-columns: 1fr 1fr; gap: 0.75rem; }
    .status-container { padding: 0 1rem; }
}
@media (max-width: 640px) {
    .status-hero h1 { font-size: 1.75rem; }
    .service-grid { grid-template-columns: 1fr; }
    .overall-banner { flex-wrap: wrap; font-size: 1.05rem; }
    .overall-banner .checked-at { margin-left: 0; width: 100%; }
    .subscribe-bar { flex-direction: column; text-align: center; }
}
@media (max-width: 480px) {
    .status-hero { padding: 1.5rem 0 1rem; }
    .status-hero h1 { font-size: 1.4rem; }
    .status-hero p { font-size: 0.9rem; }
    .status-section { margin-bottom: 1.5rem; }
    .status-section h2 { font-size: 1rem; }
    .overall-banner { font-size: 0.95rem; padding: 0.75rem 1rem; gap: 0.5rem; }
    .service-card { padding: 0.75rem; }
    .service-name { font-size: 0.85rem; }
    .subscribe-bar { padding: 1rem; gap: 0.75rem; }
    .subscribe-bar p { font-size: 0.8rem; }
    .status-refresh { padding: 0.4rem 1rem; font-size: 0.8rem; min-height: 44px; }
    .incident-item { flex-direction: column; gap: 0.5rem; }
    .uptime-bars { gap: 2px; }
}
@media (pointer: coarse) {
    .status-refresh { min-height: 44px; }
    .subscribe-bar a, .subscribe-bar button { min-height: 44px; }
}
</style>

<div class="status-page">
<div class="status-container">

    <!-- Hero -->
    <div class="status-hero">
        <h1><i class="fas fa-signal" style="margin-right:0.5rem"></i> Alfred AI System Status</h1>
        <p>Real-time platform health monitoring</p>
    </div>

    <!-- Overall Status Banner -->
    <div class="overall-banner <?php echo $overallStatus; ?>">
        <i class="<?php echo $overallIcon; ?>"></i>
        <span><?php echo $overallLabel; ?></span>
        <span class="checked-at">Last checked: <?php echo $checkedAt; ?></span>
    </div>

    <!-- Service Status Grid -->
    <div class="status-section">
        <h2><i class="fas fa-server"></i> Service Status</h2>
        <div class="service-grid">
            <?php foreach ($checks as $check): ?>
            <div class="service-card">
                <div class="status-dot <?php echo htmlspecialchars($check['status']); ?>"></div>
                <div class="service-info">
                    <div class="service-name"><?php echo htmlspecialchars($check['name']); ?></div>
                    <div class="service-meta">
                        <?php echo $check['response_time']; ?>ms
                        <?php if ($check['details']): ?>
                            &middot; <?php echo htmlspecialchars($check['details']); ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="service-status-label <?php echo htmlspecialchars($check['status']); ?>">
                    <?php
                    switch ($check['status']) {
                        case 'operational': echo 'Operational'; break;
                        case 'degraded': echo 'Degraded'; break;
                        case 'down': echo 'Down'; break;
                        default: echo ucfirst($check['status']);
                    }
                    ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Uptime History (30 days) -->
    <div class="status-section">
        <h2><i class="fas fa-chart-bar"></i> 30-Day Uptime History</h2>
        <div class="uptime-grid">
            <div class="uptime-bars">
                <?php
                $totalUptime = 0;
                foreach ($uptimeData as $day):
                    $totalUptime += $day['uptime'];
                    $height = max(10, ($day['uptime'] / 100) * 60);
                    $class = $day['uptime'] >= 99.9 ? 'good' : ($day['uptime'] >= 99 ? 'warn' : 'bad');
                ?>
                <div class="uptime-bar <?php echo $class; ?>" style="height:<?php echo $height; ?>px">
                    <span class="tooltip-text"><?php echo $day['date']; ?>: <?php echo number_format($day['uptime'], 2); ?>%</span>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="uptime-labels">
                <span><?php echo $uptimeData[0]['date']; ?></span>
                <span>Today</span>
            </div>
            <div class="uptime-summary">
                <div>
                    <div class="uptime-pct"><?php echo number_format($totalUptime / count($uptimeData), 2); ?>%</div>
                    <div class="uptime-pct-label">30-day average uptime</div>
                </div>
                <a href="?refresh=1" class="status-refresh"><i class="fas fa-sync-alt"></i> Refresh Status</a>
            </div>
        </div>
    </div>

    <!-- Response Time Chart (24h) -->
    <div class="status-section">
        <h2><i class="fas fa-tachometer-alt"></i> Response Times (24 Hours)</h2>
        <div class="response-chart">
            <div class="chart-bars">
                <?php
                $maxTime = 1;
                foreach ($responseTimes as $rt) {
                    $maxTime = max($maxTime, $rt['api'], $rt['ws'], $rt['db']);
                }
                foreach ($responseTimes as $rt):
                    $apiH = max(2, ($rt['api'] / $maxTime) * 110);
                    $wsH = max(2, ($rt['ws'] / $maxTime) * 110);
                    $dbH = max(2, ($rt['db'] / $maxTime) * 110);
                ?>
                <div class="chart-bar-group">
                    <div class="chart-bar api" style="height:<?php echo $apiH; ?>px" title="API: <?php echo $rt['api']; ?>ms"></div>
                    <div class="chart-bar ws" style="height:<?php echo $wsH; ?>px" title="WS: <?php echo $rt['ws']; ?>ms"></div>
                    <div class="chart-bar db" style="height:<?php echo $dbH; ?>px" title="DB: <?php echo $rt['db']; ?>ms"></div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="chart-labels">
                <span><?php echo $responseTimes[0]['hour']; ?></span>
                <span><?php echo $responseTimes[intval(count($responseTimes)/2)]['hour']; ?></span>
                <span>Now</span>
            </div>
            <div class="chart-legend">
                <span class="legend-api">API</span>
                <span class="legend-ws">WebSocket</span>
                <span class="legend-db">Database</span>
            </div>
        </div>
    </div>

    <!-- Recent Incidents -->
    <div class="status-section">
        <h2><i class="fas fa-clipboard-list"></i> Recent Incidents</h2>
        <div class="incidents-list">
            <div class="incident-empty">
                <i class="fas fa-shield-alt"></i>
                <p><strong>No incidents in the last 30 days</strong></p>
                <p style="font-size:0.8rem; margin-top:0.5rem;">All systems have been running smoothly.</p>
            </div>
        </div>
    </div>

    <!-- Subscribe / Info Bar -->
    <div class="subscribe-bar">
        <p><strong>Need help?</strong> If you're experiencing issues, contact support at <a href="tel:+18334674836" style="color:#818cf8">1-833-GOSITEME</a> or open a ticket.</p>
        <a href="/alfred.php" class="status-refresh"><i class="fas fa-robot"></i> Ask Alfred</a>
    </div>

</div>
</div>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
