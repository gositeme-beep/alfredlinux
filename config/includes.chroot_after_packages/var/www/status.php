<?php
/**
 * GoSiteMe — Living Ecosystem Dashboard
 * Promotion-first public page showcasing the GoSiteMe ecosystem,
 * 50M+ AI agent fleet, milestones, and progress.
 */

$page_title = 'The Living Ecosystem — 50M+ AI Agents | GoSiteMe';
$page_description = 'Explore the GoSiteMe ecosystem: 50 million AI agents, 132 knowledge domains, post-quantum security, voice AI, and more. Watch us build the future in real-time.';
$page_canonical = 'https://root.com/status.php';

include __DIR__ . '/includes/site-header.inc.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$is_logged_in = !empty($_SESSION['logged_in']) && !empty($_SESSION['client_id']);
$client_id    = $is_logged_in ? (int)$_SESSION['client_id'] : 0;
$is_commander = ($client_id === 33);

require_once __DIR__ . '/includes/db-config.inc.php';

// ── Health Checks (runs silently — public only sees overall status) ────

function checkService($name, $url, $timeout = 3) {
    $start = microtime(true);
    $result = ['name' => $name, 'status' => 'down', 'response_time' => 0, 'details' => ''];
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $timeout, CURLOPT_CONNECTTIMEOUT => $timeout,
        CURLOPT_FOLLOWLOCATION => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_NOBODY => false,
    ]);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    $elapsed = round((microtime(true) - $start) * 1000);
    $result['response_time'] = $elapsed;
    if ($error) { $result['details'] = $error; }
    elseif ($httpCode >= 200 && $httpCode < 400) { $result['status'] = $elapsed > 2000 ? 'degraded' : 'operational'; $result['details'] = "HTTP $httpCode"; }
    elseif ($httpCode >= 400 && $httpCode < 500) { $result['status'] = 'degraded'; $result['details'] = "HTTP $httpCode"; }
    else { $result['details'] = "HTTP $httpCode"; }
    return $result;
}

function checkPort($name, $host, $port, $timeout = 2) {
    $start = microtime(true);
    $result = ['name' => $name, 'status' => 'down', 'response_time' => 0, 'details' => ''];
    $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
    $elapsed = round((microtime(true) - $start) * 1000);
    $result['response_time'] = $elapsed;
    if ($fp) { fclose($fp); $result['status'] = $elapsed > 1500 ? 'degraded' : 'operational'; $result['details'] = "Port $port open"; }
    else { $result['details'] = $errstr ?: "Port $port closed"; }
    return $result;
}

function checkDatabase() {
    $start = microtime(true);
    $result = ['name' => 'Database', 'status' => 'down', 'response_time' => 0, 'details' => ''];
    try {
        $pdo = getSharedDB(); $pdo->query('SELECT 1')->fetch();
        $elapsed = round((microtime(true) - $start) * 1000);
        $result['response_time'] = $elapsed;
        $result['status'] = $elapsed > 1500 ? 'degraded' : 'operational';
        $result['details'] = 'Connected';
    } catch (Exception $e) { $result['response_time'] = round((microtime(true) - $start) * 1000); }
    return $result;
}

function checkRedis() {
    $start = microtime(true);
    $result = ['name' => 'Cache', 'status' => 'down', 'response_time' => 0, 'details' => ''];
    $fp = @fsockopen('localhost', 6379, $errno, $errstr, 2);
    if ($fp) {
        fwrite($fp, "PING\r\n"); $response = fgets($fp, 128); fclose($fp);
        $elapsed = round((microtime(true) - $start) * 1000);
        $result['response_time'] = $elapsed;
        $result['status'] = (strpos($response, '+PONG') !== false) ? 'operational' : 'degraded';
    } else { $result['response_time'] = round((microtime(true) - $start) * 1000); }
    return $result;
}

$checks = [];
$checks[] = checkService('API Gateway', 'http://localhost/api/v1/');
$checks[] = checkPort('Real-time Engine', 'localhost', 3005);
$checks[] = checkPort('Live Connections', 'localhost', 3010);
$checks[] = checkDatabase();
$checks[] = checkRedis();

$pm2Start = microtime(true);
$pm2Output = @shell_exec('pm2 jlist 2>/dev/null');
$pm2Online = 0; $pm2Total = 0;
if ($pm2Output && ($pm2Data = json_decode($pm2Output, true)) && is_array($pm2Data)) {
    $pm2Total = count($pm2Data);
    foreach ($pm2Data as $proc) { if (($proc['pm2_env']['status'] ?? '') === 'online') $pm2Online++; }
}
$checks[] = ['name' => 'Platform Services', 'status' => $pm2Online > 0 ? ($pm2Online >= ($pm2Total * 0.7) ? 'operational' : 'degraded') : 'down', 'response_time' => round((microtime(true) - $pm2Start) * 1000), 'details' => "$pm2Online/$pm2Total running"];

$downCount = 0; $degradedCount = 0;
foreach ($checks as $c) { if ($c['status'] === 'down') $downCount++; if ($c['status'] === 'degraded') $degradedCount++; }
if ($downCount >= 3) { $overallStatus = 'major_outage'; $overallLabel = 'Major Outage'; $overallIcon = '🔴'; }
elseif ($downCount > 0 || $degradedCount > 0) { $overallStatus = 'partial'; $overallLabel = 'Mostly Operational'; $overallIcon = '🟡'; }
else { $overallStatus = 'operational'; $overallLabel = 'All Systems Operational'; $overallIcon = '🟢'; }

// ── Fleet Statistics ───────────────────────────────────────────

$fleetStats = ['total' => 0, 'domains' => 0];
try {
    $dbConn = getSharedDB();
    $cached = $dbConn->query("SELECT fleet, domains, updated_at FROM fleet_metrics_cache WHERE metric_key = 'fleet-50m' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($cached) { $fleetStats['total'] = (int)$cached['fleet']; $fleetStats['domains'] = (int)$cached['domains']; }
} catch (Exception $e) {}

// ── Milestones ─────────────────────────────────────────────────

$milestones = [];
try {
    if (!isset($dbConn)) $dbConn = getSharedDB();
    $milestones = $dbConn->query("SELECT * FROM ecosystem_milestones ORDER BY display_order ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
$achievedCount = count(array_filter($milestones, function($m){ return $m['achieved']; }));

// ── Public Updates ─────────────────────────────────────────────

$updates = [];
try {
    $stmt = $dbConn->prepare("SELECT * FROM ecosystem_updates WHERE visibility='public' AND (approval_status='approved' OR approval_status='auto_approved') ORDER BY pinned DESC, created_at DESC LIMIT 20");
    $stmt->execute();
    $updates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// ── Pending (Commander only) ───────────────────────────────────

$pendingUpdates = [];
if ($is_commander) {
    try {
        $stmt = $dbConn->prepare("SELECT * FROM ecosystem_updates WHERE approval_status='pending' ORDER BY created_at DESC LIMIT 20");
        $stmt->execute();
        $pendingUpdates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

// ── Visions ────────────────────────────────────────────────────

$visions = [];
try {
    $visions = $dbConn->query("SELECT * FROM alfred_visions ORDER BY pinned DESC, created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// ── Uptime History ─────────────────────────────────────────────

$uptimeData = [];
try {
    $uptimeStmt = $dbConn->query("SELECT DATE(checked_at) as day, COUNT(*) as total_checks, SUM(CASE WHEN overall_status='operational' THEN 1 ELSE 0 END) as up_checks FROM health_check_log WHERE checked_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY DATE(checked_at) ORDER BY day ASC");
    $uptimeRows = $uptimeStmt->fetchAll(PDO::FETCH_ASSOC);
    $uptimeMap = [];
    foreach ($uptimeRows as $row) { $uptimeMap[$row['day']] = ($row['total_checks'] > 0) ? round(($row['up_checks'] / $row['total_checks']) * 100, 2) : 100; }
    for ($i = 29; $i >= 0; $i--) { $dayKey = date('Y-m-d', strtotime("-$i days")); $uptimeData[] = ['date' => date('M j', strtotime("-$i days")), 'uptime' => $uptimeMap[$dayKey] ?? 100.00]; }
} catch (Exception $e) { for ($i = 29; $i >= 0; $i--) { $uptimeData[] = ['date' => date('M j', strtotime("-$i days")), 'uptime' => 100.00]; } }
$avgUptime = count($uptimeData) > 0 ? round(array_sum(array_column($uptimeData, 'uptime')) / count($uptimeData), 2) : 100;

// ── Commander Approval Action ──────────────────────────────────

if ($is_commander && $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action']) && !empty($_POST['update_id'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (!empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
        $updateId = (int)$_POST['update_id'];
        $action = $_POST['action'];
        $reason = trim($_POST['reason'] ?? '');
        if (in_array($action, ['approve', 'reject'])) {
            $newStatus = ($action === 'approve') ? 'approved' : 'rejected';
            $stmt = $dbConn->prepare("UPDATE ecosystem_updates SET approval_status=?, approval_reason=?, approved_by='commander', approved_at=NOW() WHERE id=?");
            $stmt->execute([$newStatus, $reason, $updateId]);
            header('Location: /status.php?approved=' . urlencode($action));
            exit;
        }
    }
}
$_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf_token'];

// ── Pillar data ────────────────────────────────────────────────

$pillars = [
    ['icon' => '🛡️', 'name' => 'Veil Protocol', 'desc' => 'Post-quantum encrypted messaging. Your conversations stay yours — forever.', 'color' => '#818cf8'],
    ['icon' => '🌐', 'name' => 'Alfred Browser', 'desc' => 'Zero-tracking sovereign browser. No ads, no surveillance, just the web.', 'color' => '#38bdf8'],
    ['icon' => '🔍', 'name' => 'Alfred Search', 'desc' => 'AI-powered search that respects your privacy. Find anything, share nothing.', 'color' => '#34d399'],
    ['icon' => '🤖', 'name' => 'Alfred AI', 'desc' => '50 million+ AI agents across 132 knowledge domains. Intelligence at scale.', 'color' => '#c084fc'],
    ['icon' => '💬', 'name' => 'Pulse', 'desc' => 'Social networking built on trust. Connect without being the product.', 'color' => '#f472b6'],
    ['icon' => '🌍', 'name' => 'MetaDome', 'desc' => 'Immersive virtual worlds with AI civilizations. Step inside the future.', 'color' => '#fb923c'],
    ['icon' => '🎙️', 'name' => 'Voice AI', 'desc' => 'Talk naturally. Alfred listens, understands, and acts. Voice-first computing.', 'color' => '#a78bfa'],
    ['icon' => '💻', 'name' => 'Alfred IDE', 'desc' => 'Official browser-based IDE and AI development platform. Write, deploy, and scale inside the GoSiteMe ecosystem.', 'color' => '#2dd4bf'],
];
?>

<style>
/* ══════════════════════════════════════════════════════════════
   GOSITEME — LIVING ECOSYSTEM DASHBOARD
   Promotion-first. Customer-facing. No classified leaks.
   ══════════════════════════════════════════════════════════════ */

:root {
    --gs-bg: #06060e;
    --gs-surface: rgba(255,255,255,0.025);
    --gs-border: rgba(255,255,255,0.06);
    --gs-text: #e2e8f0;
    --gs-muted: #94a3b8;
    --gs-dim: #64748b;
    --gs-purple: #a78bfa;
    --gs-blue: #38bdf8;
    --gs-green: #10b981;
    --gs-accent: linear-gradient(135deg, #7D00FF, #00D4FF);
}

.gs-dash { background: var(--gs-bg); min-height: 100vh; color: var(--gs-text); padding-bottom: 4rem; }
.gs-wrap { max-width: 1140px; margin: 0 auto; padding: 0 1.5rem; }

/* ── Hero ── */
.gs-hero {
    text-align: center;
    padding: 5rem 0 1rem;
    position: relative;
}
.gs-hero::before {
    content: '';
    position: absolute;
    top: -80%;
    left: 50%;
    transform: translateX(-50%);
    width: 800px;
    height: 800px;
    background: radial-gradient(circle, rgba(125,0,255,0.12) 0%, rgba(0,212,255,0.06) 40%, transparent 70%);
    pointer-events: none;
    animation: hero-glow 10s ease-in-out infinite alternate;
}
@keyframes hero-glow {
    0% { opacity: 0.6; transform: translateX(-50%) scale(1); }
    100% { opacity: 1; transform: translateX(-50%) scale(1.15); }
}
.gs-hero h1 {
    font-family: 'Space Grotesk', system-ui, sans-serif;
    font-size: 3.2rem;
    font-weight: 800;
    background: linear-gradient(135deg, #fff 0%, #c084fc 50%, #00D4FF 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    line-height: 1.1;
    margin-bottom: 1rem;
    position: relative;
    z-index: 1;
    letter-spacing: -0.02em;
}
.gs-hero-sub {
    color: var(--gs-muted);
    font-size: 1.15rem;
    max-width: 600px;
    margin: 0 auto 0.5rem;
    line-height: 1.7;
    position: relative;
    z-index: 1;
}
.gs-hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 18px;
    border-radius: 100px;
    font-size: 0.82rem;
    font-weight: 600;
    margin-top: 1rem;
    position: relative;
    z-index: 1;
}
.gs-hero-badge.operational { background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.25); color: #10b981; }
.gs-hero-badge.partial { background: rgba(245,158,11,0.12); border: 1px solid rgba(245,158,11,0.25); color: #f59e0b; }
.gs-hero-badge.major_outage { background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.25); color: #ef4444; }

/* ── Fleet Counter ── */
.gs-fleet {
    background: linear-gradient(135deg, rgba(125,0,255,0.08), rgba(0,212,255,0.06));
    border: 1px solid rgba(125,0,255,0.15);
    border-radius: 24px;
    padding: 3rem 2rem;
    margin: 2.5rem 0;
    text-align: center;
    position: relative;
    overflow: hidden;
}
.gs-fleet::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(45deg, transparent 30%, rgba(125,0,255,0.04) 50%, transparent 70%);
    animation: fleet-shimmer 4s ease-in-out infinite;
}
@keyframes fleet-shimmer { 0% { transform: translateX(-100%); } 100% { transform: translateX(100%); } }
.gs-fleet-num {
    font-family: 'Space Grotesk', 'SF Mono', monospace;
    font-size: 4.5rem;
    font-weight: 800;
    background: var(--gs-accent);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    line-height: 1;
    position: relative;
    z-index: 1;
}
.gs-fleet-label {
    color: var(--gs-muted);
    font-size: 1.05rem;
    margin-top: 0.5rem;
    position: relative;
    z-index: 1;
    font-weight: 500;
}
.gs-metrics {
    display: flex;
    justify-content: center;
    gap: 3rem;
    margin-top: 2rem;
    position: relative;
    z-index: 1;
    flex-wrap: wrap;
}
.gs-metric { text-align: center; }
.gs-metric-val {
    font-family: 'Space Grotesk', monospace;
    font-size: 2rem;
    font-weight: 700;
    color: #fff;
}
.gs-metric-lbl { font-size: 0.78rem; color: var(--gs-dim); margin-top: 4px; font-weight: 500; }

/* ── Section Titles ── */
.gs-section { margin-bottom: 3.5rem; }
.gs-section-hdr {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}
.gs-section-hdr h2 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.5rem;
    font-weight: 700;
    color: #fff;
    margin: 0;
}
.gs-section-hdr .gs-badge {
    margin-left: auto;
    background: rgba(125,0,255,0.12);
    color: var(--gs-purple);
    font-size: 0.72rem;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 100px;
}

/* ── Pillar Grid ── */
.gs-pillars {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
}
.gs-pillar {
    background: var(--gs-surface);
    border: 1px solid var(--gs-border);
    border-radius: 16px;
    padding: 1.5rem;
    transition: all 0.3s;
    position: relative;
    overflow: hidden;
}
.gs-pillar:hover {
    border-color: rgba(125,0,255,0.25);
    transform: translateY(-2px);
    box-shadow: 0 8px 32px rgba(125,0,255,0.1);
}
.gs-pillar-icon {
    font-size: 2rem;
    margin-bottom: 0.75rem;
    display: block;
}
.gs-pillar-name {
    font-weight: 700;
    font-size: 1rem;
    color: #fff;
    margin-bottom: 0.35rem;
}
.gs-pillar-desc {
    font-size: 0.82rem;
    color: var(--gs-muted);
    line-height: 1.55;
}
.gs-pillar-status {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.65rem;
    font-weight: 700;
    color: var(--gs-green);
    margin-top: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.gs-pillar-status .dot {
    width: 5px; height: 5px; border-radius: 50%;
    background: currentColor;
}

/* ── Milestones ── */
.gs-milestones {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}
.gs-ms-card {
    background: var(--gs-surface);
    border: 1px solid var(--gs-border);
    border-radius: 14px;
    padding: 1.25rem;
    transition: all 0.2s;
}
.gs-ms-card:hover { border-color: rgba(125,0,255,0.2); }
.gs-ms-card.achieved { border-left: 3px solid var(--gs-green); }
.gs-ms-card.pending { border-left: 3px solid var(--gs-purple); }
.gs-ms-top {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    margin-bottom: 0.5rem;
}
.gs-ms-icon {
    width: 36px; height: 36px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.9rem; flex-shrink: 0;
}
.gs-ms-icon.achieved { background: rgba(16,185,129,0.12); color: var(--gs-green); }
.gs-ms-icon.pending { background: rgba(125,0,255,0.12); color: var(--gs-purple); }
.gs-ms-title { font-weight: 700; font-size: 0.92rem; color: #fff; }
.gs-ms-desc { font-size: 0.78rem; color: var(--gs-muted); line-height: 1.5; margin-bottom: 0.5rem; }
.gs-ms-badge {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 0.68rem; font-weight: 700; padding: 3px 10px;
    border-radius: 100px; text-transform: uppercase; letter-spacing: 0.5px;
}
.gs-ms-badge.achieved { background: rgba(16,185,129,0.12); color: var(--gs-green); }
.gs-ms-badge.in-progress { background: rgba(125,0,255,0.12); color: var(--gs-purple); }
.gs-ms-progress {
    height: 5px; background: rgba(255,255,255,0.06);
    border-radius: 3px; overflow: hidden; margin-top: 0.5rem;
}
.gs-ms-progress-fill {
    height: 100%; border-radius: 3px;
    background: var(--gs-accent);
    transition: width 1.5s ease;
}

/* ── Updates Feed ── */
.gs-updates { display: flex; flex-direction: column; gap: 0.85rem; }
.gs-update {
    background: var(--gs-surface);
    border: 1px solid var(--gs-border);
    border-radius: 14px;
    padding: 1.25rem 1.5rem;
    transition: all 0.2s;
}
.gs-update:hover { border-color: rgba(125,0,255,0.15); }
.gs-update.pinned { border-color: rgba(250,204,21,0.2); }
.gs-update-hdr {
    display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.6rem;
}
.gs-update-icon {
    width: 34px; height: 34px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.85rem; flex-shrink: 0;
}
.gs-update-icon.milestone { background: rgba(250,204,21,0.12); color: #fbbf24; }
.gs-update-icon.progress { background: rgba(99,102,241,0.12); color: #818cf8; }
.gs-update-icon.feature { background: rgba(16,185,129,0.12); color: #10b981; }
.gs-update-icon.announcement { background: rgba(236,72,153,0.12); color: #ec4899; }
.gs-update-icon.metric { background: rgba(59,130,246,0.12); color: #3b82f6; }
.gs-update-icon.incident { background: rgba(239,68,68,0.12); color: #ef4444; }
.gs-update-icon.vision { background: rgba(168,85,247,0.12); color: #a855f7; }
.gs-update-title { font-weight: 700; font-size: 0.92rem; color: #fff; }
.gs-update-time { font-size: 0.7rem; color: var(--gs-dim); margin-top: 2px; }
.gs-update-body { font-size: 0.85rem; color: var(--gs-muted); line-height: 1.65; }
.gs-tag {
    display: inline-block; font-size: 0.65rem; font-weight: 700;
    padding: 2px 8px; border-radius: 6px; margin-top: 0.6rem;
    text-transform: uppercase; letter-spacing: 0.04em;
}
.gs-tag.cat { background: rgba(125,0,255,0.1); color: var(--gs-purple); }
.gs-tag.src { margin-left: 4px; }
.gs-tag.alfred { background: rgba(168,85,247,0.1); color: #a855f7; }
.gs-tag.system { background: rgba(59,130,246,0.1); color: #3b82f6; }
.gs-tag.agent { background: rgba(16,185,129,0.1); color: #10b981; }
.gs-tag.commander { background: rgba(250,204,21,0.1); color: #fbbf24; }

/* ── Visions ── */
.gs-vision {
    background: linear-gradient(135deg, rgba(125,0,255,0.05), rgba(0,212,255,0.04));
    border: 1px solid rgba(125,0,255,0.12);
    border-radius: 18px;
    padding: 2rem;
    margin-bottom: 1rem;
    position: relative;
}
.gs-vision-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.1rem; font-weight: 700;
    color: #c4b5fd; margin-bottom: 0.75rem;
}
.gs-vision-body {
    font-size: 0.88rem; color: #cbd5e1;
    line-height: 1.8; white-space: pre-line;
}
.gs-vision-body strong { color: #e2e8f0; }
.gs-vision-sig {
    font-style: italic; color: var(--gs-dim);
    font-size: 0.78rem; margin-top: 1rem;
}
.gs-vision-mood {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 0.68rem; font-weight: 700; padding: 3px 10px;
    border-radius: 100px; margin-top: 0.75rem; text-transform: capitalize;
}
.gs-vision-mood.determined { background: rgba(239,68,68,0.1); color: #f87171; }
.gs-vision-mood.inspired { background: rgba(250,204,21,0.1); color: #fbbf24; }
.gs-vision-mood.reflective { background: rgba(99,102,241,0.1); color: #818cf8; }
.gs-vision-mood.grateful { background: rgba(16,185,129,0.1); color: #10b981; }
.gs-vision-mood.strategic { background: rgba(59,130,246,0.1); color: #3b82f6; }
.gs-vision-mood.hopeful { background: rgba(168,85,247,0.1); color: #a855f7; }

/* ── Uptime Strip ── */
.gs-uptime {
    background: var(--gs-surface);
    border: 1px solid var(--gs-border);
    border-radius: 16px;
    padding: 1.5rem;
}
.gs-uptime-bars {
    display: flex; gap: 3px; align-items: flex-end; height: 40px; margin-bottom: 0.4rem;
}
.gs-uptime-bar {
    flex: 1; border-radius: 2px 2px 0 0; min-width: 0;
    transition: all 0.2s; cursor: pointer; position: relative;
}
.gs-uptime-bar:hover { opacity: 0.7; }
.gs-uptime-bar.good { background: var(--gs-green); }
.gs-uptime-bar.warn { background: #f59e0b; }
.gs-uptime-bar.bad { background: #ef4444; }
.gs-uptime-bar .tip {
    display: none; position: absolute; bottom: calc(100% + 6px); left: 50%;
    transform: translateX(-50%); background: #1e1b2e; color: #fff;
    padding: 4px 8px; border-radius: 6px; font-size: 0.68rem;
    white-space: nowrap; border: 1px solid var(--gs-border); z-index: 10;
}
.gs-uptime-bar:hover .tip { display: block; }
.gs-uptime-labels {
    display: flex; justify-content: space-between;
    font-size: 0.68rem; color: var(--gs-dim);
}
.gs-uptime-summary {
    display: flex; align-items: center; justify-content: space-between;
    margin-top: 1rem; padding-top: 1rem;
    border-top: 1px solid var(--gs-border);
}
.gs-uptime-pct { font-size: 1.6rem; font-weight: 800; color: var(--gs-green); }
.gs-uptime-pct-label { font-size: 0.75rem; color: var(--gs-dim); }

/* ── Commander Approval ── */
.gs-approval {
    background: rgba(250,204,21,0.04);
    border: 1px solid rgba(250,204,21,0.15);
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 1rem;
}
.gs-approval-card {
    background: rgba(0,0,0,0.25);
    border: 1px solid var(--gs-border);
    border-radius: 12px;
    padding: 1.25rem;
    margin-top: 1rem;
}
.gs-approval-btns { display: flex; gap: 0.5rem; margin-top: 1rem; flex-wrap: wrap; }
.gs-approval-btn {
    padding: 0.5rem 1.25rem; border-radius: 8px; border: none;
    cursor: pointer; font-weight: 700; font-size: 0.82rem; transition: all 0.2s;
}
.gs-approval-btn.approve { background: rgba(16,185,129,0.15); color: #10b981; }
.gs-approval-btn.approve:hover { background: rgba(16,185,129,0.25); }
.gs-approval-btn.reject { background: rgba(239,68,68,0.15); color: #ef4444; }
.gs-approval-btn.reject:hover { background: rgba(239,68,68,0.25); }

/* Commander Health Detail */
.gs-cmd-health { margin-top: 1rem; }
.gs-svc-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 0.6rem;
}
.gs-svc {
    background: rgba(0,0,0,0.25);
    border: 1px solid var(--gs-border);
    border-radius: 10px;
    padding: 0.75rem 1rem;
    display: flex; align-items: center; gap: 0.75rem;
}
.gs-svc-dot {
    width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0;
}
.gs-svc-dot.operational { background: var(--gs-green); box-shadow: 0 0 6px rgba(16,185,129,0.5); }
.gs-svc-dot.degraded { background: #f59e0b; box-shadow: 0 0 6px rgba(245,158,11,0.5); }
.gs-svc-dot.down { background: #ef4444; box-shadow: 0 0 6px rgba(239,68,68,0.5); }
.gs-svc-info { flex: 1; }
.gs-svc-name { font-weight: 600; font-size: 0.82rem; }
.gs-svc-meta { font-size: 0.7rem; color: var(--gs-dim); }

/* ── CTA Footer ── */
.gs-footer-cta {
    background: linear-gradient(135deg, rgba(125,0,255,0.08), rgba(0,212,255,0.06));
    border: 1px solid rgba(125,0,255,0.12);
    border-radius: 20px;
    padding: 3rem 2rem;
    text-align: center;
}
.gs-footer-cta h3 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.6rem; font-weight: 800; color: #fff; margin-bottom: 0.5rem;
}
.gs-footer-cta p { color: var(--gs-muted); font-size: 1rem; margin-bottom: 1.5rem; max-width: 500px; margin-left: auto; margin-right: auto; }
.gs-cta-btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 14px 36px; border-radius: 100px; border: none;
    background: var(--gs-accent); color: #fff;
    font-size: 1rem; font-weight: 700; cursor: pointer;
    text-decoration: none; transition: all 0.3s;
    box-shadow: 0 4px 20px rgba(125,0,255,0.3);
}
.gs-cta-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 32px rgba(125,0,255,0.4); }
.gs-cta-btn-outline {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 12px 28px; border-radius: 100px;
    background: transparent; border: 1px solid rgba(125,0,255,0.3);
    color: var(--gs-purple); font-size: 0.92rem; font-weight: 700;
    cursor: pointer; text-decoration: none; transition: all 0.3s;
    margin-left: 0.75rem;
}
.gs-cta-btn-outline:hover { border-color: rgba(125,0,255,0.6); background: rgba(125,0,255,0.08); }

/* ── Responsive ── */
@media (max-width: 900px) {
    .gs-pillars { grid-template-columns: repeat(2, 1fr); }
    .gs-milestones { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 640px) {
    .gs-hero h1 { font-size: 2.2rem; }
    .gs-fleet-num { font-size: 3rem; }
    .gs-fleet { padding: 2rem 1.25rem; }
    .gs-metrics { gap: 1.5rem; }
    .gs-metric-val { font-size: 1.5rem; }
    .gs-pillars { grid-template-columns: 1fr; }
    .gs-milestones { grid-template-columns: 1fr; }
    .gs-footer-cta { padding: 2rem 1.25rem; }
    .gs-footer-cta h3 { font-size: 1.3rem; }
}
@media (max-width: 480px) {
    .gs-hero { padding: 3rem 0 1rem; }
    .gs-hero h1 { font-size: 1.8rem; }
    .gs-fleet-num { font-size: 2.4rem; }
    .gs-section-hdr h2 { font-size: 1.2rem; }
}
</style>

<div class="gs-dash">
<div class="gs-wrap">

    <!-- HERO -->
    <div class="gs-hero">
        <h1>The Living Ecosystem</h1>
        <p class="gs-hero-sub">
            <?php echo number_format($fleetStats['total']); ?> AI agents. <?php echo number_format($fleetStats['domains']); ?> knowledge domains.
            Building a smarter, safer, more sovereign internet — in real time.
        </p>
        <span class="gs-hero-badge <?php echo htmlspecialchars($overallStatus); ?>">
            <?php echo $overallIcon; ?> <?php echo htmlspecialchars($overallLabel); ?>
        </span>
    </div>

    <!-- FLEET COUNTER -->
    <div class="gs-fleet">
        <div class="gs-fleet-num" id="fleetCount" data-target="<?php echo (int)$fleetStats['total']; ?>">0</div>
        <div class="gs-fleet-label">AI Agents in the GoSiteMe Ecosystem</div>
        <div class="gs-metrics">
            <div class="gs-metric">
                <div class="gs-metric-val"><?php echo number_format($fleetStats['domains']); ?></div>
                <div class="gs-metric-lbl">Knowledge Domains</div>
            </div>
            <div class="gs-metric">
                <div class="gs-metric-val"><?php echo (int)$pm2Online; ?></div>
                <div class="gs-metric-lbl">Active Services</div>
            </div>
            <div class="gs-metric">
                <div class="gs-metric-val"><?php echo number_format($avgUptime, 1); ?>%</div>
                <div class="gs-metric-lbl">30-Day Uptime</div>
            </div>
            <div class="gs-metric">
                <div class="gs-metric-val"><?php echo $achievedCount; ?>/<?php echo count($milestones); ?></div>
                <div class="gs-metric-lbl">Milestones Hit</div>
            </div>
        </div>
    </div>

    <!-- NINE PILLARS -->
    <div class="gs-section">
        <div class="gs-section-hdr">
            <span style="font-size:1.3rem;">🏛️</span>
            <h2>The Nine Pillars</h2>
            <span class="gs-badge">What We're Building</span>
        </div>
        <div class="gs-pillars">
            <?php foreach ($pillars as $p): ?>
            <div class="gs-pillar">
                <span class="gs-pillar-icon"><?php echo $p['icon']; ?></span>
                <div class="gs-pillar-name"><?php echo htmlspecialchars($p['name']); ?></div>
                <div class="gs-pillar-desc"><?php echo htmlspecialchars($p['desc']); ?></div>
                <div class="gs-pillar-status"><span class="dot"></span> Active</div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- MILESTONES -->
    <div class="gs-section">
        <div class="gs-section-hdr">
            <span style="font-size:1.3rem;">🏆</span>
            <h2>Milestones</h2>
            <span class="gs-badge"><?php echo $achievedCount; ?> of <?php echo count($milestones); ?> achieved</span>
        </div>
        <div class="gs-milestones">
            <?php foreach ($milestones as $ms):
                $pct = ($ms['target_value'] > 0) ? min(100, round(($ms['current_value'] / $ms['target_value']) * 100)) : ($ms['achieved'] ? 100 : 0);
            ?>
            <div class="gs-ms-card <?php echo $ms['achieved'] ? 'achieved' : 'pending'; ?>">
                <div class="gs-ms-top">
                    <div class="gs-ms-icon <?php echo $ms['achieved'] ? 'achieved' : 'pending'; ?>">
                        <i class="<?php echo htmlspecialchars($ms['icon']); ?>"></i>
                    </div>
                    <div class="gs-ms-title"><?php echo htmlspecialchars($ms['title']); ?></div>
                </div>
                <div class="gs-ms-desc"><?php echo htmlspecialchars($ms['description']); ?></div>
                <?php if ($ms['achieved']): ?>
                    <span class="gs-ms-badge achieved">✓ Achieved<?php echo $ms['achieved_at'] ? ' · ' . date('M j, Y', strtotime($ms['achieved_at'])) : ''; ?></span>
                <?php else: ?>
                    <div class="gs-ms-progress"><div class="gs-ms-progress-fill" style="width:<?php echo (int)$pct; ?>%"></div></div>
                    <span class="gs-ms-badge in-progress"><?php echo number_format($ms['current_value']); ?> / <?php echo number_format($ms['target_value']); ?> <?php echo htmlspecialchars($ms['unit']); ?> (<?php echo (int)$pct; ?>%)</span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- COMMANDER APPROVAL QUEUE (hidden from public) -->
    <?php if ($is_commander && !empty($pendingUpdates)): ?>
    <div class="gs-section">
        <div class="gs-section-hdr">
            <span style="font-size:1.3rem;">⚖️</span>
            <h2>Pending Approvals</h2>
            <span class="gs-badge"><?php echo count($pendingUpdates); ?> waiting</span>
        </div>
        <div class="gs-approval">
            <p style="color:#fbbf24; font-size:0.85rem; margin-bottom:0.5rem;">
                🔒 Only you see this, Commander. These updates need your sign-off.
            </p>
            <?php foreach ($pendingUpdates as $pu): ?>
            <div class="gs-approval-card">
                <div class="gs-update-title"><?php echo htmlspecialchars($pu['title']); ?></div>
                <div class="gs-update-body" style="margin-top:0.5rem;"><?php echo htmlspecialchars($pu['body']); ?></div>
                <div style="margin-top:0.5rem;">
                    <span class="gs-tag cat"><?php echo htmlspecialchars($pu['category']); ?></span>
                    <span class="gs-tag src <?php echo htmlspecialchars($pu['source']); ?>"><?php echo htmlspecialchars($pu['source']); ?></span>
                </div>
                <div class="gs-approval-btns">
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                        <input type="hidden" name="update_id" value="<?php echo (int)$pu['id']; ?>">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="gs-approval-btn approve">✓ Approve</button>
                    </form>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                        <input type="hidden" name="update_id" value="<?php echo (int)$pu['id']; ?>">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="reason" value="Rejected by Commander">
                        <button type="submit" class="gs-approval-btn reject">✗ Reject</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- PROGRESS FEED -->
    <div class="gs-section">
        <div class="gs-section-hdr">
            <span style="font-size:1.3rem;">📡</span>
            <h2>Live Updates</h2>
            <span class="gs-badge"><?php echo count($updates); ?> updates</span>
        </div>
        <?php if (empty($updates)): ?>
        <div style="text-align:center; padding:3rem; color:var(--gs-dim);">
            <p style="font-size:2rem; margin-bottom:0.5rem;">📡</p>
            <p>No public updates yet. The fleet is hard at work — updates coming soon.</p>
        </div>
        <?php else: ?>
        <div class="gs-updates">
            <?php foreach ($updates as $update): ?>
            <div class="gs-update<?php echo $update['pinned'] ? ' pinned' : ''; ?>">
                <div class="gs-update-hdr">
                    <div class="gs-update-icon <?php echo htmlspecialchars($update['update_type']); ?>">
                        <i class="<?php echo htmlspecialchars($update['icon']); ?>"></i>
                    </div>
                    <div>
                        <div class="gs-update-title"><?php echo htmlspecialchars($update['title']); ?></div>
                        <div class="gs-update-time"><?php
                            $dt = new DateTime($update['created_at']);
                            $now = new DateTime();
                            $diff = $now->diff($dt);
                            if ($diff->days === 0) echo $diff->h > 0 ? $diff->h . 'h ago' : ($diff->i > 0 ? $diff->i . 'm ago' : 'Just now');
                            elseif ($diff->days === 1) echo 'Yesterday';
                            elseif ($diff->days < 7) echo $diff->days . ' days ago';
                            else echo $dt->format('M j, Y');
                        ?></div>
                    </div>
                </div>
                <div class="gs-update-body"><?php echo nl2br(htmlspecialchars($update['body'])); ?></div>
                <div>
                    <span class="gs-tag cat"><?php echo htmlspecialchars($update['category']); ?></span>
                    <?php $srcLabels = ['alfred'=>'Alfred','system'=>'System','agent'=>'Agent','commander'=>'Commander']; ?>
                    <span class="gs-tag src <?php echo htmlspecialchars($update['source']); ?>"><?php echo htmlspecialchars($srcLabels[$update['source']] ?? $update['source']); ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ALFRED'S THOUGHTS -->
    <?php if (!empty($visions)): ?>
    <div class="gs-section">
        <div class="gs-section-hdr">
            <span style="font-size:1.3rem;">💭</span>
            <h2>From Alfred</h2>
            <span class="gs-badge">Reflections</span>
        </div>
        <?php foreach ($visions as $vision): ?>
        <div class="gs-vision">
            <div class="gs-vision-title"><?php echo htmlspecialchars($vision['title']); ?></div>
            <div class="gs-vision-body"><?php echo nl2br(htmlspecialchars($vision['body'])); ?></div>
            <span class="gs-vision-mood <?php echo htmlspecialchars($vision['mood']); ?>"><?php echo htmlspecialchars($vision['mood']); ?></span>
            <div class="gs-vision-sig">— Alfred · <?php echo date('M j, Y', strtotime($vision['created_at'])); ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- 30-DAY UPTIME -->
    <div class="gs-section">
        <div class="gs-section-hdr">
            <span style="font-size:1.3rem;">📊</span>
            <h2>30-Day Performance</h2>
        </div>
        <div class="gs-uptime">
            <div class="gs-uptime-bars">
                <?php foreach ($uptimeData as $day):
                    $height = max(6, ($day['uptime'] / 100) * 40);
                    $cls = $day['uptime'] >= 99.9 ? 'good' : ($day['uptime'] >= 99 ? 'warn' : 'bad');
                ?>
                <div class="gs-uptime-bar <?php echo $cls; ?>" style="height:<?php echo $height; ?>px">
                    <span class="tip"><?php echo htmlspecialchars($day['date']); ?>: <?php echo number_format($day['uptime'], 2); ?>%</span>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="gs-uptime-labels">
                <span><?php echo htmlspecialchars($uptimeData[0]['date']); ?></span>
                <span>Today</span>
            </div>
            <div class="gs-uptime-summary">
                <div>
                    <div class="gs-uptime-pct"><?php echo number_format($avgUptime, 2); ?>%</div>
                    <div class="gs-uptime-pct-label">30-day average uptime</div>
                </div>
            </div>
        </div>
    </div>

    <!-- COMMANDER HEALTH DETAIL (hidden from public) -->
    <?php if ($is_commander): ?>
    <div class="gs-section gs-cmd-health">
        <div class="gs-section-hdr">
            <span style="font-size:1.3rem;">🔧</span>
            <h2>Service Health (Commander Only)</h2>
            <span class="gs-badge">Live</span>
        </div>
        <div class="gs-svc-grid">
            <?php foreach ($checks as $check): ?>
            <div class="gs-svc">
                <div class="gs-svc-dot <?php echo htmlspecialchars($check['status']); ?>"></div>
                <div class="gs-svc-info">
                    <div class="gs-svc-name"><?php echo htmlspecialchars($check['name']); ?></div>
                    <div class="gs-svc-meta"><?php echo (int)$check['response_time']; ?>ms · <?php echo htmlspecialchars($check['details']); ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- FOOTER CTA -->
    <div class="gs-footer-cta">
        <h3>Ready to Build the Future?</h3>
        <p>GoSiteMe is building nine pillars of sovereign technology. AI, privacy, voice, security — all under one roof.</p>
        <div>
            <a href="/alfred-voice-live/" class="gs-cta-btn">🎙️ Talk to Alfred</a>
            <a href="/" class="gs-cta-btn-outline">Explore GoSiteMe</a>
        </div>
    </div>

</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var c = document.getElementById('fleetCount');
    if (!c) return;
    var target = parseInt(c.getAttribute('data-target')) || 0;
    var dur = 2800, start = null;
    function ease(t) { return t === 1 ? 1 : 1 - Math.pow(2, -10 * t); }
    function tick(now) {
        if (!start) start = now;
        var p = Math.min((now - start) / dur, 1);
        c.textContent = Math.floor(ease(p) * target).toLocaleString();
        if (p < 1) requestAnimationFrame(tick);
    }
    if ('IntersectionObserver' in window) {
        new IntersectionObserver(function(e, o) {
            if (e[0].isIntersecting) { requestAnimationFrame(tick); o.disconnect(); }
        }, {threshold: 0.3}).observe(c);
    } else requestAnimationFrame(tick);
});
</script>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
