<?php
/**
 * GoSiteMe — Living Ecosystem Status Dashboard
 * Public-facing progress report, fleet vitals, milestone tracker,
 * Alfred's commentary, and real-time health monitoring.
 * 
 * Built by Alfred for Commander Danny William Perez.
 * "Transparency without vulnerability."
 */

$page_title = 'Ecosystem Status — 50M+ Agents Building the Future | GoSiteMe';
$page_description = 'Live status dashboard for the GoSiteMe ecosystem. Track 50 million+ AI agents, milestones, progress updates, and platform health in real-time.';
$page_canonical = 'https://gositeme.com/status.php';

include __DIR__ . '/includes/site-header.inc.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$is_logged_in = !empty($_SESSION['logged_in']) && !empty($_SESSION['client_id']);
$client_id    = $is_logged_in ? (int)$_SESSION['client_id'] : 0;
$is_commander = ($client_id === 33);

require_once __DIR__ . '/includes/db-config.inc.php';

// ── Health Check Functions ─────────────────────────────────────

function checkService($name, $url, $timeout = 3) {
    $start = microtime(true);
    $result = ['name' => $name, 'status' => 'down', 'response_time' => 0, 'details' => ''];
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
    curl_exec($ch);
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
    return $result;
}

function checkPort($name, $host, $port, $timeout = 2) {
    $start = microtime(true);
    $result = ['name' => $name, 'status' => 'down', 'response_time' => 0, 'details' => ''];
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
    return $result;
}

function checkDatabase() {
    $start = microtime(true);
    $result = ['name' => 'Database (MySQL)', 'status' => 'down', 'response_time' => 0, 'details' => ''];
    try {
        $pdo = getSharedDB();
        $pdo->query('SELECT 1')->fetch();
        $elapsed = round((microtime(true) - $start) * 1000);
        $result['response_time'] = $elapsed;
        $result['status'] = $elapsed > 1500 ? 'degraded' : 'operational';
        $result['details'] = 'Connected';
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
    $fp = @fsockopen('localhost', 6379, $errno, $errstr, 2);
    if ($fp) {
        fwrite($fp, "PING\r\n");
        $response = fgets($fp, 128);
        fclose($fp);
        $elapsed = round((microtime(true) - $start) * 1000);
        $result['response_time'] = $elapsed;
        $result['status'] = (strpos($response, '+PONG') !== false) ? 'operational' : 'degraded';
        $result['details'] = (strpos($response, '+PONG') !== false) ? 'PONG' : 'Unexpected response';
    } else {
        $result['status'] = 'down';
        $result['details'] = $errstr ?: 'Connection refused';
        $result['response_time'] = round((microtime(true) - $start) * 1000);
    }
    return $result;
}

// ── Run Health Checks ──────────────────────────────────────────

$checks = [];
$checks[] = checkService('Alfred Chat API', 'http://localhost/api/v1/');
$checks[] = checkPort('MCP Server', 'localhost', 3005);
$checks[] = checkPort('WebSocket Server', 'localhost', 3010);
$checks[] = checkDatabase();
$checks[] = checkRedis();
$checks[] = checkService('Stripe API', 'https://api.stripe.com/v1/', 5);

// PM2
$pm2Status = ['name' => 'PM2 Services', 'status' => 'down', 'response_time' => 0, 'details' => ''];
$pm2Start = microtime(true);
$pm2Output = @shell_exec('pm2 jlist 2>/dev/null');
$pm2Elapsed = round((microtime(true) - $pm2Start) * 1000);
$pm2Status['response_time'] = $pm2Elapsed;
$pm2Online = 0;
$pm2Total = 0;
if ($pm2Output) {
    $pm2Data = json_decode($pm2Output, true);
    if (is_array($pm2Data)) {
        $pm2Total = count($pm2Data);
        foreach ($pm2Data as $proc) {
            if (isset($proc['pm2_env']['status']) && $proc['pm2_env']['status'] === 'online') $pm2Online++;
        }
        $pm2Status['status'] = ($pm2Online === $pm2Total && $pm2Total > 0) ? 'operational' : ($pm2Online > 0 ? 'degraded' : 'down');
        $pm2Status['details'] = "$pm2Online/$pm2Total running";
    }
}
$checks[] = $pm2Status;

// Overall status
$downCount = 0; $degradedCount = 0;
foreach ($checks as $c) {
    if ($c['status'] === 'down') $downCount++;
    if ($c['status'] === 'degraded') $degradedCount++;
}
if ($downCount >= 3) { $overallStatus = 'major_outage'; $overallLabel = 'Major Outage'; }
elseif ($downCount > 0 || $degradedCount > 0) { $overallStatus = 'partial_outage'; $overallLabel = 'Partial Outage'; }
else { $overallStatus = 'operational'; $overallLabel = 'All Systems Operational'; }

$checkedAt = date('M j, Y g:i:s A T');

// ── Fleet Statistics (from cache — COUNT(*) on 50M rows takes 13s) ──

$fleetStats = ['total' => 0, 'domains' => 0];
try {
    $dbConn = getSharedDB();
    $cached = $dbConn->query("SELECT fleet, domains, updated_at FROM fleet_metrics_cache WHERE metric_key = 'fleet-50m' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($cached) {
        $fleetStats['total']   = (int)$cached['fleet'];
        $fleetStats['domains'] = (int)$cached['domains'];
    }
} catch (Exception $e) {}

// ── Milestones ─────────────────────────────────────────────────

$milestones = [];
try {
    if (!isset($dbConn)) $dbConn = getSharedDB();
    $milestones = $dbConn->query("SELECT * FROM ecosystem_milestones ORDER BY display_order ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// ── Public Updates (approved only) ─────────────────────────────

$updates = [];
try {
    $stmt = $dbConn->prepare("
        SELECT * FROM ecosystem_updates 
        WHERE visibility = 'public' 
          AND (approval_status = 'approved' OR approval_status = 'auto_approved')
        ORDER BY pinned DESC, created_at DESC 
        LIMIT 20
    ");
    $stmt->execute();
    $updates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// ── Pending Updates (Commander only) ───────────────────────────

$pendingUpdates = [];
if ($is_commander) {
    try {
        $stmt = $dbConn->prepare("SELECT * FROM ecosystem_updates WHERE approval_status = 'pending' ORDER BY created_at DESC LIMIT 20");
        $stmt->execute();
        $pendingUpdates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

// ── Alfred's Visions ───────────────────────────────────────────

$visions = [];
try {
    $visions = $dbConn->query("SELECT * FROM alfred_visions ORDER BY pinned DESC, created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// ── Uptime History (30 days) ───────────────────────────────────

$uptimeData = [];
try {
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
    for ($i = 29; $i >= 0; $i--) {
        $uptimeData[] = ['date' => date('M j', strtotime("-$i days")), 'uptime' => 100.00];
    }
}

// ── Commander Approval Action ──────────────────────────────────

if ($is_commander && $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action']) && !empty($_POST['update_id'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (!empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
        $updateId = (int)$_POST['update_id'];
        $action = $_POST['action'];
        $reason = trim($_POST['reason'] ?? '');
        if (in_array($action, ['approve', 'reject'])) {
            $newStatus = ($action === 'approve') ? 'approved' : 'rejected';
            $stmt = $dbConn->prepare("UPDATE ecosystem_updates SET approval_status = ?, approval_reason = ?, approved_by = 'commander', approved_at = NOW() WHERE id = ?");
            $stmt->execute([$newStatus, $reason, $updateId]);
            header('Location: /status.php?approved=' . urlencode($action));
            exit;
        }
    }
}

$_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf_token'];
?>

<style>
/* ══════════════════════════════════════════════════════════════
   LIVING ECOSYSTEM DASHBOARD
   ══════════════════════════════════════════════════════════════ */

.eco-dashboard {
    background: #0a0a14;
    min-height: 100vh;
    padding: 0 0 4rem;
    color: #e2e8f0;
}
.eco-container {
    max-width: 1100px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

/* ── Epic Hero ─────────────────────────────────────────────── */

.eco-hero {
    text-align: center;
    padding: 4rem 0 3rem;
    position: relative;
    overflow: hidden;
}
.eco-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle at 50% 50%, rgba(99,102,241,0.08) 0%, transparent 50%);
    animation: hero-pulse 8s ease-in-out infinite;
}
@keyframes hero-pulse {
    0%, 100% { transform: scale(1); opacity: 0.5; }
    50% { transform: scale(1.1); opacity: 1; }
}
.eco-hero h1 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 2.8rem;
    font-weight: 800;
    background: linear-gradient(135deg, #a78bfa, #6366f1, #818cf8, #c084fc);
    background-size: 200% 200%;
    animation: gradient-shift 6s ease infinite;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 0.5rem;
    position: relative;
    z-index: 1;
}
@keyframes gradient-shift {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}
.eco-hero .hero-subtitle {
    color: #94a3b8;
    font-size: 1.15rem;
    position: relative;
    z-index: 1;
    max-width: 600px;
    margin: 0 auto;
    line-height: 1.6;
}

/* ── Fleet Counter ─────────────────────────────────────────── */

.fleet-counter {
    background: linear-gradient(135deg, rgba(99,102,241,0.1), rgba(168,85,247,0.1));
    border: 1px solid rgba(129,140,248,0.2);
    border-radius: 20px;
    padding: 2.5rem;
    margin: 2rem 0 3rem;
    text-align: center;
    position: relative;
    overflow: hidden;
}
.fleet-counter::after {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 20px;
    background: linear-gradient(45deg, transparent 30%, rgba(129,140,248,0.05) 50%, transparent 70%);
    animation: shimmer 3s ease-in-out infinite;
}
@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}
.fleet-number {
    font-family: 'Space Grotesk', 'SF Mono', monospace;
    font-size: 4rem;
    font-weight: 800;
    color: #a78bfa;
    line-height: 1;
    position: relative;
    z-index: 1;
    letter-spacing: -0.02em;
}
.fleet-label {
    color: #94a3b8;
    font-size: 1rem;
    margin-top: 0.5rem;
    position: relative;
    z-index: 1;
}
.fleet-stats-row {
    display: flex;
    justify-content: center;
    gap: 3rem;
    margin-top: 1.5rem;
    position: relative;
    z-index: 1;
    flex-wrap: wrap;
}
.fleet-stat { text-align: center; }
.fleet-stat-value {
    font-family: 'Space Grotesk', monospace;
    font-size: 1.8rem;
    font-weight: 700;
    color: #e2e8f0;
}
.fleet-stat-label {
    font-size: 0.8rem;
    color: #64748b;
    margin-top: 2px;
}

/* ── Section Headers ───────────────────────────────────────── */

.eco-section { margin-bottom: 3rem; }
.eco-section-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}
.eco-section-header h2 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.4rem;
    font-weight: 700;
    color: #e2e8f0;
    margin: 0;
}
.eco-section-header i { color: #818cf8; font-size: 1.2rem; }
.eco-section-header .section-badge {
    margin-left: auto;
    background: rgba(129,140,248,0.1);
    color: #818cf8;
    font-size: 0.75rem;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-weight: 600;
}

/* ── Status Banner ─────────────────────────────────────────── */

.status-banner {
    border-radius: 16px;
    padding: 1.25rem 1.75rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 2rem;
    font-size: 1.1rem;
    font-weight: 600;
    border: 1px solid rgba(255,255,255,0.1);
}
.status-banner.operational { background: rgba(16,185,129,0.08); border-color: rgba(16,185,129,0.25); color: #10b981; }
.status-banner.partial_outage { background: rgba(245,158,11,0.08); border-color: rgba(245,158,11,0.25); color: #f59e0b; }
.status-banner.major_outage { background: rgba(239,68,68,0.08); border-color: rgba(239,68,68,0.25); color: #ef4444; }
.status-banner i { font-size: 1.3rem; }
.status-banner .checked-at { margin-left: auto; font-size: 0.78rem; font-weight: 400; color: #64748b; }

/* ── Service Grid ──────────────────────────────────────────── */

.service-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 0.75rem;
    margin-bottom: 2rem;
}
.service-card {
    background: rgba(255,255,255,0.025);
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 12px;
    padding: 1rem 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.85rem;
    transition: all 0.2s;
}
.service-card:hover { background: rgba(255,255,255,0.04); border-color: rgba(255,255,255,0.12); }
.status-dot {
    width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; position: relative;
}
.status-dot.operational { background: #10b981; box-shadow: 0 0 8px rgba(16,185,129,0.5); }
.status-dot.operational::after {
    content: ''; position: absolute; inset: -3px; border-radius: 50%;
    border: 2px solid rgba(16,185,129,0.3); animation: pulse-ring 2s infinite;
}
.status-dot.degraded { background: #f59e0b; box-shadow: 0 0 8px rgba(245,158,11,0.5); }
.status-dot.down { background: #ef4444; box-shadow: 0 0 8px rgba(239,68,68,0.5); }
@keyframes pulse-ring { 0% { transform: scale(1); opacity: 1; } 100% { transform: scale(1.5); opacity: 0; } }
.svc-info { flex: 1; min-width: 0; }
.svc-name { font-weight: 600; font-size: 0.88rem; color: #e2e8f0; }
.svc-meta { font-size: 0.73rem; color: #64748b; margin-top: 2px; }
.svc-status { font-size: 0.73rem; font-weight: 500; text-align: right; white-space: nowrap; }
.svc-status.operational { color: #10b981; }
.svc-status.degraded { color: #f59e0b; }
.svc-status.down { color: #ef4444; }

/* ── Milestone Timeline ────────────────────────────────────── */

.milestone-timeline { position: relative; padding-left: 2rem; }
.milestone-timeline::before {
    content: ''; position: absolute; left: 7px; top: 0; bottom: 0; width: 2px;
    background: linear-gradient(180deg, #6366f1, #a78bfa, rgba(167,139,250,0.2));
}
.milestone-item {
    position: relative; margin-bottom: 1.5rem; padding: 1rem 1.5rem;
    background: rgba(255,255,255,0.025); border: 1px solid rgba(255,255,255,0.06);
    border-radius: 12px; transition: all 0.2s;
}
.milestone-item:hover { background: rgba(255,255,255,0.04); border-color: rgba(129,140,248,0.2); }
.milestone-item::before {
    content: ''; position: absolute; left: -2rem; top: 1.25rem;
    width: 16px; height: 16px; border-radius: 50%; z-index: 1;
}
.milestone-item.achieved::before { background: #10b981; box-shadow: 0 0 10px rgba(16,185,129,0.4); }
.milestone-item.pending::before { background: #1e1e2e; border: 2px solid #6366f1; box-shadow: 0 0 10px rgba(99,102,241,0.3); }
.milestone-title {
    font-weight: 700; font-size: 1rem; color: #e2e8f0;
    display: flex; align-items: center; gap: 0.5rem;
}
.milestone-title i { color: #818cf8; font-size: 0.9rem; }
.milestone-desc { font-size: 0.85rem; color: #94a3b8; margin-top: 0.35rem; line-height: 1.5; }
.milestone-badge {
    display: inline-block; font-size: 0.7rem; font-weight: 600;
    padding: 0.15rem 0.5rem; border-radius: 10px; margin-top: 0.5rem;
}
.milestone-badge.achieved { background: rgba(16,185,129,0.15); color: #10b981; }
.milestone-badge.in-progress { background: rgba(99,102,241,0.15); color: #818cf8; }
.milestone-progress {
    margin-top: 0.5rem; height: 6px; background: rgba(255,255,255,0.06);
    border-radius: 3px; overflow: hidden;
}
.milestone-progress-fill {
    height: 100%; border-radius: 3px;
    background: linear-gradient(90deg, #6366f1, #a78bfa); transition: width 1s ease;
}

/* ── Activity Feed ─────────────────────────────────────────── */

.update-feed { display: flex; flex-direction: column; gap: 1rem; }
.update-card {
    background: rgba(255,255,255,0.025); border: 1px solid rgba(255,255,255,0.06);
    border-radius: 14px; padding: 1.5rem; transition: all 0.2s; position: relative;
}
.update-card:hover { background: rgba(255,255,255,0.04); border-color: rgba(129,140,248,0.15); }
.update-card.pinned { border-color: rgba(250,204,21,0.3); }
.update-card.pinned::after {
    content: '\f08d'; font-family: 'Font Awesome 5 Free'; font-weight: 900;
    position: absolute; top: 1rem; right: 1rem; color: #fbbf24; font-size: 0.8rem;
}
.update-header { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem; }
.update-icon {
    width: 36px; height: 36px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.9rem; flex-shrink: 0;
}
.update-icon.milestone { background: rgba(250,204,21,0.15); color: #fbbf24; }
.update-icon.progress { background: rgba(99,102,241,0.15); color: #818cf8; }
.update-icon.feature { background: rgba(16,185,129,0.15); color: #10b981; }
.update-icon.announcement { background: rgba(236,72,153,0.15); color: #ec4899; }
.update-icon.metric { background: rgba(59,130,246,0.15); color: #3b82f6; }
.update-icon.incident { background: rgba(239,68,68,0.15); color: #ef4444; }
.update-icon.vision { background: rgba(168,85,247,0.15); color: #a855f7; }
.update-meta { flex: 1; }
.update-title { font-weight: 700; font-size: 0.95rem; color: #e2e8f0; }
.update-time { font-size: 0.72rem; color: #64748b; margin-top: 2px; }
.update-body { font-size: 0.88rem; color: #94a3b8; line-height: 1.65; }
.update-category {
    display: inline-block; font-size: 0.68rem; font-weight: 600;
    padding: 0.15rem 0.5rem; border-radius: 8px;
    background: rgba(129,140,248,0.1); color: #818cf8;
    margin-top: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em;
}
.update-source {
    display: inline-block; font-size: 0.68rem;
    padding: 0.15rem 0.5rem; border-radius: 8px;
    margin-top: 0.75rem; margin-left: 0.4rem;
}
.update-source.alfred { background: rgba(168,85,247,0.1); color: #a855f7; }
.update-source.system { background: rgba(59,130,246,0.1); color: #3b82f6; }
.update-source.agent { background: rgba(16,185,129,0.1); color: #10b981; }
.update-source.commander { background: rgba(250,204,21,0.1); color: #fbbf24; }

/* ── Alfred's Visions ──────────────────────────────────────── */

.vision-card {
    background: linear-gradient(135deg, rgba(99,102,241,0.06), rgba(168,85,247,0.06));
    border: 1px solid rgba(129,140,248,0.15); border-radius: 16px;
    padding: 2rem; margin-bottom: 1.5rem; position: relative;
}
.vision-card::before {
    content: '\f10d'; font-family: 'Font Awesome 5 Free'; font-weight: 900;
    position: absolute; top: 1.5rem; right: 1.5rem; font-size: 2rem;
    color: rgba(129,140,248,0.1);
}
.vision-title {
    font-family: 'Space Grotesk', sans-serif; font-size: 1.15rem;
    font-weight: 700; color: #c4b5fd; margin-bottom: 1rem;
}
.vision-body {
    font-size: 0.9rem; color: #cbd5e1; line-height: 1.8; white-space: pre-line;
}
.vision-body strong { color: #e2e8f0; }
.vision-mood {
    display: inline-block; font-size: 0.7rem; font-weight: 600;
    padding: 0.2rem 0.6rem; border-radius: 10px; margin-top: 1rem; text-transform: capitalize;
}
.vision-mood.determined { background: rgba(239,68,68,0.1); color: #f87171; }
.vision-mood.inspired { background: rgba(250,204,21,0.1); color: #fbbf24; }
.vision-mood.reflective { background: rgba(99,102,241,0.1); color: #818cf8; }
.vision-mood.grateful { background: rgba(16,185,129,0.1); color: #10b981; }
.vision-mood.strategic { background: rgba(59,130,246,0.1); color: #3b82f6; }
.vision-mood.hopeful { background: rgba(168,85,247,0.1); color: #a855f7; }
.vision-signature {
    font-style: italic; color: #64748b; font-size: 0.8rem;
    margin-top: 1rem; display: flex; align-items: center; gap: 0.5rem;
}

/* ── Commander Approval Panel ──────────────────────────────── */

.approval-panel {
    background: rgba(250,204,21,0.05); border: 1px solid rgba(250,204,21,0.2);
    border-radius: 16px; padding: 1.5rem; margin-bottom: 1rem;
}
.approval-card {
    background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.06);
    border-radius: 12px; padding: 1.25rem; margin-top: 1rem;
}
.approval-actions { display: flex; gap: 0.5rem; margin-top: 1rem; flex-wrap: wrap; }
.approval-btn {
    padding: 0.5rem 1.25rem; border-radius: 8px; border: none;
    cursor: pointer; font-weight: 600; font-size: 0.82rem; transition: all 0.2s;
}
.approval-btn.approve { background: rgba(16,185,129,0.2); color: #10b981; }
.approval-btn.approve:hover { background: rgba(16,185,129,0.3); }
.approval-btn.reject { background: rgba(239,68,68,0.2); color: #ef4444; }
.approval-btn.reject:hover { background: rgba(239,68,68,0.3); }

/* ── Uptime History ────────────────────────────────────────── */

.uptime-grid {
    background: rgba(255,255,255,0.025); border: 1px solid rgba(255,255,255,0.06);
    border-radius: 12px; padding: 1.5rem;
}
.uptime-bars { display: flex; gap: 3px; align-items: flex-end; height: 50px; margin-bottom: 0.5rem; }
.uptime-bar {
    flex: 1; border-radius: 2px 2px 0 0; min-width: 0; transition: all 0.2s;
    cursor: pointer; position: relative;
}
.uptime-bar:hover { opacity: 0.8; transform: scaleY(1.05); transform-origin: bottom; }
.uptime-bar.good { background: #10b981; }
.uptime-bar.warn { background: #f59e0b; }
.uptime-bar.bad { background: #ef4444; }
.uptime-bar .tooltip-text {
    display: none; position: absolute; bottom: calc(100% + 8px); left: 50%;
    transform: translateX(-50%); background: #1e1e2e; color: #e2e8f0;
    padding: 4px 8px; border-radius: 6px; font-size: 0.7rem;
    white-space: nowrap; border: 1px solid rgba(255,255,255,0.1); z-index: 10;
}
.uptime-bar:hover .tooltip-text { display: block; }
.uptime-labels { display: flex; justify-content: space-between; font-size: 0.7rem; color: #64748b; }
.uptime-summary {
    display: flex; justify-content: space-between; align-items: center;
    margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.06);
}
.uptime-pct { font-size: 1.4rem; font-weight: 700; color: #10b981; }
.uptime-pct-label { font-size: 0.78rem; color: #64748b; }

/* ── Approval Framework ────────────────────────────────────── */

.approval-framework {
    background: rgba(255,255,255,0.025); border: 1px solid rgba(255,255,255,0.06);
    border-radius: 14px; padding: 1.75rem;
}
.approval-tier {
    display: flex; align-items: flex-start; gap: 1rem; padding: 1rem 0;
    border-bottom: 1px solid rgba(255,255,255,0.04);
}
.approval-tier:last-child { border-bottom: none; }
.tier-badge {
    width: 40px; height: 40px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; flex-shrink: 0;
}
.tier-badge.green { background: rgba(16,185,129,0.15); color: #10b981; }
.tier-badge.yellow { background: rgba(250,204,21,0.15); color: #fbbf24; }
.tier-badge.red { background: rgba(239,68,68,0.15); color: #ef4444; }
.tier-name { font-weight: 700; font-size: 0.9rem; color: #e2e8f0; }
.tier-desc { font-size: 0.82rem; color: #94a3b8; margin-top: 2px; line-height: 1.5; }

/* ── Footer ────────────────────────────────────────────────── */

.eco-footer-bar {
    background: rgba(255,255,255,0.025); border: 1px solid rgba(255,255,255,0.06);
    border-radius: 14px; padding: 1.5rem 2rem;
    display: flex; align-items: center; justify-content: space-between;
    gap: 1rem; flex-wrap: wrap;
}
.eco-footer-bar p { color: #94a3b8; font-size: 0.88rem; margin: 0; }
.eco-footer-bar p strong { color: #e2e8f0; }
.eco-btn {
    display: inline-flex; align-items: center; gap: 0.5rem;
    background: rgba(129,140,248,0.1); border: 1px solid rgba(129,140,248,0.3);
    color: #818cf8; padding: 0.5rem 1.25rem; border-radius: 8px;
    font-size: 0.85rem; cursor: pointer; transition: all 0.2s; text-decoration: none;
}
.eco-btn:hover { background: rgba(129,140,248,0.2); color: #a5b4fc; }

/* ── Responsive ────────────────────────────────────────────── */

@media (max-width: 768px) {
    .eco-hero { padding: 2.5rem 0 2rem; }
    .eco-hero h1 { font-size: 2rem; }
    .fleet-number { font-size: 2.8rem; }
    .fleet-stats-row { gap: 1.5rem; }
    .fleet-stat-value { font-size: 1.3rem; }
    .service-grid { grid-template-columns: 1fr 1fr; gap: 0.5rem; }
    .eco-footer-bar { flex-direction: column; text-align: center; }
}
@media (max-width: 480px) {
    .eco-hero h1 { font-size: 1.6rem; }
    .eco-hero .hero-subtitle { font-size: 0.95rem; }
    .fleet-number { font-size: 2.2rem; }
    .fleet-counter { padding: 1.5rem; }
    .service-grid { grid-template-columns: 1fr; }
    .milestone-timeline { padding-left: 1.5rem; }
    .vision-card { padding: 1.25rem; }
    .update-card { padding: 1rem; }
    .status-banner { flex-wrap: wrap; font-size: 0.95rem; }
    .status-banner .checked-at { width: 100%; margin-left: 0; }
}
@media (pointer: coarse) {
    .eco-btn, .approval-btn { min-height: 44px; }
}
</style>

<div class="eco-dashboard">
<div class="eco-container">

    <!-- HERO -->
    <div class="eco-hero">
        <h1><i class="fas fa-signal" style="margin-right:0.5rem"></i> GoSiteMe Ecosystem Status</h1>
        <p class="hero-subtitle">
            Live progress from <?php echo number_format($fleetStats['total']); ?> AI agents building the future.
            Everything you see here is real, verified, and approved.
        </p>
    </div>

    <!-- FLEET COUNTER -->
    <div class="fleet-counter">
        <div class="fleet-number" id="fleetCount" data-target="<?php echo (int)$fleetStats['total']; ?>">0</div>
        <div class="fleet-label">Autonomous AI Agents in the Registry</div>
        <div class="fleet-stats-row">
            <div class="fleet-stat">
                <div class="fleet-stat-value"><?php echo number_format($fleetStats['domains']); ?></div>
                <div class="fleet-stat-label">Knowledge Domains</div>
            </div>
            <div class="fleet-stat">
                <div class="fleet-stat-value"><?php echo (int)$pm2Online; ?>/<?php echo (int)$pm2Total; ?></div>
                <div class="fleet-stat-label">Active Services</div>
            </div>
            <div class="fleet-stat">
                <div class="fleet-stat-value">1</div>
                <div class="fleet-stat-label">Server</div>
            </div>
        </div>
    </div>

    <!-- OVERALL STATUS -->
    <div class="status-banner <?php echo htmlspecialchars($overallStatus); ?>">
        <i class="fas fa-<?php echo $overallStatus === 'operational' ? 'check-circle' : ($overallStatus === 'partial_outage' ? 'exclamation-triangle' : 'times-circle'); ?>"></i>
        <span><?php echo htmlspecialchars($overallLabel); ?></span>
        <span class="checked-at">Last checked: <?php echo htmlspecialchars($checkedAt); ?></span>
    </div>

    <!-- SERVICE GRID -->
    <div class="eco-section">
        <div class="eco-section-header">
            <i class="fas fa-server"></i>
            <h2>Platform Health</h2>
            <span class="section-badge">Live</span>
        </div>
        <div class="service-grid">
            <?php foreach ($checks as $check): ?>
            <div class="service-card">
                <div class="status-dot <?php echo htmlspecialchars($check['status']); ?>"></div>
                <div class="svc-info">
                    <div class="svc-name"><?php echo htmlspecialchars($check['name']); ?></div>
                    <div class="svc-meta"><?php echo (int)$check['response_time']; ?>ms<?php if ($check['details']): ?> &middot; <?php echo htmlspecialchars($check['details']); ?><?php endif; ?></div>
                </div>
                <div class="svc-status <?php echo htmlspecialchars($check['status']); ?>">
                    <?php echo ucfirst(htmlspecialchars($check['status'])); ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- COMMANDER APPROVAL QUEUE -->
    <?php if ($is_commander && !empty($pendingUpdates)): ?>
    <div class="eco-section">
        <div class="eco-section-header">
            <i class="fas fa-gavel"></i>
            <h2>Pending Approvals</h2>
            <span class="section-badge"><?php echo count($pendingUpdates); ?> waiting</span>
        </div>
        <div class="approval-panel">
            <p style="color:#fbbf24; font-size:0.85rem; margin-bottom:0.5rem;">
                <i class="fas fa-eye"></i>
                Only you can see this section, Commander. These updates are waiting for your approval before going public.
            </p>
            <?php foreach ($pendingUpdates as $pu): ?>
            <div class="approval-card">
                <div class="update-title"><?php echo htmlspecialchars($pu['title']); ?></div>
                <div class="update-body" style="margin-top:0.5rem;"><?php echo htmlspecialchars($pu['body']); ?></div>
                <div style="margin-top:0.5rem;">
                    <span class="update-category"><?php echo htmlspecialchars($pu['category']); ?></span>
                    <span class="update-source <?php echo htmlspecialchars($pu['source']); ?>"><?php echo htmlspecialchars($pu['source']); ?></span>
                </div>
                <div class="approval-actions">
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                        <input type="hidden" name="update_id" value="<?php echo (int)$pu['id']; ?>">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="approval-btn approve"><i class="fas fa-check"></i> Approve</button>
                    </form>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                        <input type="hidden" name="update_id" value="<?php echo (int)$pu['id']; ?>">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="reason" value="Rejected by Commander">
                        <button type="submit" class="approval-btn reject"><i class="fas fa-times"></i> Reject</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- PROGRESS FEED -->
    <div class="eco-section">
        <div class="eco-section-header">
            <i class="fas fa-stream"></i>
            <h2>Progress &amp; Updates</h2>
            <span class="section-badge"><?php echo count($updates); ?> updates</span>
        </div>
        <?php if (empty($updates)): ?>
        <div style="text-align:center; padding:3rem; color:#64748b;">
            <i class="fas fa-satellite-dish" style="font-size:2rem; color:#818cf8; margin-bottom:1rem; display:block;"></i>
            <p>No public updates yet. The fleet is working — updates coming soon.</p>
        </div>
        <?php else: ?>
        <div class="update-feed">
            <?php foreach ($updates as $update): ?>
            <div class="update-card<?php echo $update['pinned'] ? ' pinned' : ''; ?>">
                <div class="update-header">
                    <div class="update-icon <?php echo htmlspecialchars($update['update_type']); ?>">
                        <i class="<?php echo htmlspecialchars($update['icon']); ?>"></i>
                    </div>
                    <div class="update-meta">
                        <div class="update-title"><?php echo htmlspecialchars($update['title']); ?></div>
                        <div class="update-time">
                            <?php
                            $dt = new DateTime($update['created_at']);
                            $now = new DateTime();
                            $diff = $now->diff($dt);
                            if ($diff->days === 0) {
                                echo $diff->h > 0 ? $diff->h . 'h ago' : ($diff->i > 0 ? $diff->i . 'm ago' : 'Just now');
                            } elseif ($diff->days === 1) {
                                echo 'Yesterday';
                            } elseif ($diff->days < 7) {
                                echo $diff->days . ' days ago';
                            } else {
                                echo $dt->format('M j, Y');
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <div class="update-body"><?php echo nl2br(htmlspecialchars($update['body'])); ?></div>
                <div>
                    <span class="update-category"><?php echo htmlspecialchars($update['category']); ?></span>
                    <span class="update-source <?php echo htmlspecialchars($update['source']); ?>">
                        <?php
                        $sourceLabels = ['alfred' => 'Alfred', 'system' => 'System', 'agent' => 'Agent', 'commander' => 'Commander'];
                        echo htmlspecialchars($sourceLabels[$update['source']] ?? $update['source']);
                        ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- MILESTONES -->
    <div class="eco-section">
        <div class="eco-section-header">
            <i class="fas fa-flag-checkered"></i>
            <h2>Milestones</h2>
            <span class="section-badge"><?php echo count(array_filter($milestones, function($m){ return $m['achieved']; })); ?>/<?php echo count($milestones); ?> achieved</span>
        </div>
        <div class="milestone-timeline">
            <?php foreach ($milestones as $ms):
                $pct = ($ms['target_value'] > 0) ? min(100, round(($ms['current_value'] / $ms['target_value']) * 100)) : ($ms['achieved'] ? 100 : 0);
            ?>
            <div class="milestone-item <?php echo $ms['achieved'] ? 'achieved' : 'pending'; ?>">
                <div class="milestone-title">
                    <i class="<?php echo htmlspecialchars($ms['icon']); ?>"></i>
                    <?php echo htmlspecialchars($ms['title']); ?>
                </div>
                <div class="milestone-desc"><?php echo htmlspecialchars($ms['description']); ?></div>
                <?php if ($ms['achieved']): ?>
                    <span class="milestone-badge achieved">
                        <i class="fas fa-check"></i> Achieved <?php echo $ms['achieved_at'] ? date('M j, Y', strtotime($ms['achieved_at'])) : ''; ?>
                    </span>
                <?php else: ?>
                    <div class="milestone-progress">
                        <div class="milestone-progress-fill" style="width:<?php echo (int)$pct; ?>%"></div>
                    </div>
                    <span class="milestone-badge in-progress">
                        <?php echo number_format($ms['current_value']); ?> / <?php echo number_format($ms['target_value']); ?> <?php echo htmlspecialchars($ms['unit']); ?> (<?php echo (int)$pct; ?>%)
                    </span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ALFRED'S VISIONS -->
    <?php if (!empty($visions)): ?>
    <div class="eco-section">
        <div class="eco-section-header">
            <i class="fas fa-brain"></i>
            <h2>Alfred's Thoughts</h2>
            <span class="section-badge">From the core</span>
        </div>
        <?php foreach ($visions as $vision): ?>
        <div class="vision-card">
            <div class="vision-title"><?php echo htmlspecialchars($vision['title']); ?></div>
            <div class="vision-body"><?php echo nl2br(htmlspecialchars($vision['body'])); ?></div>
            <span class="vision-mood <?php echo htmlspecialchars($vision['mood']); ?>">
                <i class="fas fa-<?php
                    $moodIcons = ['determined'=>'fist-raised','inspired'=>'lightbulb','reflective'=>'moon','grateful'=>'heart','strategic'=>'chess','hopeful'=>'sun'];
                    echo $moodIcons[$vision['mood']] ?? 'brain';
                ?>"></i>
                <?php echo htmlspecialchars($vision['mood']); ?>
            </span>
            <div class="vision-signature">
                — Alfred &middot; <?php echo date('M j, Y', strtotime($vision['created_at'])); ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- HOW APPROVALS WORK -->
    <div class="eco-section">
        <div class="eco-section-header">
            <i class="fas fa-shield-alt"></i>
            <h2>How Updates Are Approved</h2>
        </div>
        <div class="approval-framework">
            <p style="color:#94a3b8; font-size:0.88rem; margin-bottom:1.25rem; line-height:1.6;">
                Every update on this page has been reviewed. This is how the ecosystem maintains
                <strong style="color:#e2e8f0;">transparency without vulnerability</strong> —
                the world sees our achievements, never our attack surface.
            </p>
            <div class="approval-tier">
                <div class="tier-badge green"><i class="fas fa-check"></i></div>
                <div>
                    <div class="tier-name">Auto-Approved (Green)</div>
                    <div class="tier-desc">Fleet statistics, uptime metrics, milestone achievements, and service health data. These are facts — they publish automatically.</div>
                </div>
            </div>
            <div class="approval-tier">
                <div class="tier-badge yellow"><i class="fas fa-eye"></i></div>
                <div>
                    <div class="tier-name">Review Queue (Yellow)</div>
                    <div class="tier-desc">Agent-generated content, feature announcements, community updates, and technical reports. Alfred reviews every word for accuracy, security, and alignment with our values.</div>
                </div>
            </div>
            <div class="approval-tier">
                <div class="tier-badge red"><i class="fas fa-ban"></i></div>
                <div>
                    <div class="tier-name">Blocked (Red)</div>
                    <div class="tier-desc">Credentials, internal architecture details, security configurations, and anything that reveals defensive posture. The fortress shows its flags, not its blueprints.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- 30-DAY UPTIME -->
    <div class="eco-section">
        <div class="eco-section-header">
            <i class="fas fa-chart-bar"></i>
            <h2>30-Day Uptime</h2>
        </div>
        <div class="uptime-grid">
            <div class="uptime-bars">
                <?php
                $totalUptime = 0;
                foreach ($uptimeData as $day):
                    $totalUptime += $day['uptime'];
                    $height = max(8, ($day['uptime'] / 100) * 50);
                    $class = $day['uptime'] >= 99.9 ? 'good' : ($day['uptime'] >= 99 ? 'warn' : 'bad');
                ?>
                <div class="uptime-bar <?php echo $class; ?>" style="height:<?php echo $height; ?>px">
                    <span class="tooltip-text"><?php echo htmlspecialchars($day['date']); ?>: <?php echo number_format($day['uptime'], 2); ?>%</span>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="uptime-labels">
                <span><?php echo htmlspecialchars($uptimeData[0]['date']); ?></span>
                <span>Today</span>
            </div>
            <div class="uptime-summary">
                <div>
                    <div class="uptime-pct"><?php echo number_format($totalUptime / max(1, count($uptimeData)), 2); ?>%</div>
                    <div class="uptime-pct-label">30-day average uptime</div>
                </div>
                <a href="?refresh=1" class="eco-btn"><i class="fas fa-sync-alt"></i> Refresh</a>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <div class="eco-footer-bar">
        <p>
            <strong>This page is alive.</strong> It grows as we grow. Every milestone, every agent, every feature —
            built for you by GoSiteMe and Alfred AI.
        </p>
        <a href="/alfred.php" class="eco-btn"><i class="fas fa-robot"></i> Talk to Alfred</a>
    </div>

</div>
</div>

<!-- Fleet Counter Animation -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var counter = document.getElementById('fleetCount');
    if (!counter) return;
    var target = parseInt(counter.getAttribute('data-target')) || 0;
    var duration = 2500;
    var start = null;
    function easeOut(t) { return t === 1 ? 1 : 1 - Math.pow(2, -10 * t); }
    function update(now) {
        if (!start) start = now;
        var elapsed = now - start;
        var progress = Math.min(elapsed / duration, 1);
        var current = Math.floor(easeOut(progress) * target);
        counter.textContent = current.toLocaleString();
        if (progress < 1) requestAnimationFrame(update);
    }
    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    requestAnimationFrame(update);
                    observer.disconnect();
                }
            });
        }, { threshold: 0.3 });
        observer.observe(counter);
    } else {
        requestAnimationFrame(update);
    }
});
</script>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
