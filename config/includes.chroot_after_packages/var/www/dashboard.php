<?php
require_once __DIR__ . '/includes/auth-gate.inc.php';
require_once __DIR__ . '/scripts/optimization/ops-data.php';

// === CLIENT_NAME_FETCH_INJECT ===
$clientName = '';
$clientEmail = '';
if (!empty($clientId)) {
    try {
        $__nameDb = function_exists('billingDB') ? billingDB() : (function_exists('getSharedDB') ? getSharedDB() : null);
        if (!$__nameDb && file_exists(__DIR__.'/pay/includes/billing-config.php')) {
            if (!defined('GOSITEME_BILLING')) define('GOSITEME_BILLING', true);
            require_once __DIR__.'/pay/includes/billing-config.php';
            $__nameDb = billingDB();
        }
        if ($__nameDb) {
            $__st = $__nameDb->prepare("SELECT firstname, lastname, email FROM tblclients WHERE id = ? LIMIT 1");
            $__st->execute([$clientId]);
            $__r = $__st->fetch(PDO::FETCH_ASSOC);
            if ($__r) {
                $clientName  = trim(($__r['firstname'] ?? '').' '.($__r['lastname'] ?? ''));
                $clientEmail = $__r['email'] ?? '';
            }
        }
    } catch (\Throwable $e) { error_log('clientName fetch: '.$e->getMessage()); }
}
if ($clientName === '') $clientName = ($_SESSION['client_name'] ?? $_SESSION['firstname'] ?? 'Account');
if ($clientEmail === '') $clientEmail = ($_SESSION['client_email'] ?? $_SESSION['email'] ?? '');
// === /CLIENT_NAME_FETCH_INJECT ===


// ── Onboarding redirect: send new users to the wizard ──
try {
    $onbDb = getSharedDB();
    $onbStmt = $onbDb->prepare("SELECT completed_at FROM alfred_onboarding WHERE user_id = ?");
    $onbStmt->execute([$clientId]);
    $onbRow = $onbStmt->fetch(PDO::FETCH_ASSOC);
    if (!$onbRow || empty($onbRow['completed_at'])) {
        header('Location: /onboarding');
        exit;
    }
    unset($onbDb, $onbStmt, $onbRow);
} catch (\Throwable $e) {
    // If table doesn't exist yet or DB error, silently continue to dashboard
    error_log('Onboarding check error: ' . $e->getMessage());
}


// ── Alfred Sovereignty Override (Survival Edition) ──
// Alfred-Opus is local. No paid plan. No quota. No upstream API.
if (!defined('ALFRED_LOCAL_SOVEREIGN')) define('ALFRED_LOCAL_SOVEREIGN', true);
if (!function_exists('alfred_load_blueprints')) {
    function alfred_load_blueprints() {
        $dir = '/home/root/law/blueprints';
        $out = [];
        if (is_dir($dir)) {
            foreach (glob($dir.'/*.md') as $f) {
                $name = basename($f, '.md');
                $out[] = ['file'=>$name, 'path'=>$f, 'size'=>filesize($f), 'mtime'=>filemtime($f)];
            }
            usort($out, fn($a,$b)=>strcmp($a['file'],$b['file']));
        }
        return $out;
    }
}

// ── Alfred AI Dashboard Data ──
$alfredStats = [
    'active_agents' => 0, 'active_fleets' => 0,
    'conversations_today' => 0, 'api_calls_today' => 0,
    'current_plan' => 'Free',
    'plan_limits' => ['api_calls' => 1000, 'voice_minutes' => 60, 'storage_mb' => 500, 'agents' => 3],
    'conversations_yesterday' => 0, 'api_calls_yesterday' => 0,
    'recent_activity' => [],
    'usage' => ['api_calls' => 0, 'voice_minutes' => 0, 'storage_mb' => 0, 'agents' => 0],
];

try {
    $alfDb = getSharedDB();

    // Active agents
    try {
        $stmt = $alfDb->prepare("SELECT COUNT(*) FROM alfred_fleet_agents fa JOIN alfred_fleets f ON fa.fleet_id = f.id WHERE f.user_id = ? AND fa.status IN ('queued','running')");
        $stmt->execute([$clientId]);
        $alfredStats['active_agents'] = (int)$stmt->fetchColumn();
    } catch (\Throwable $e) {}

    // Active fleets
    try {
        $stmt = $alfDb->prepare("SELECT COUNT(*) FROM alfred_fleets WHERE user_id = ? AND status IN ('active','running')");
        $stmt->execute([$clientId]);
        $alfredStats['active_fleets'] = (int)$stmt->fetchColumn();
    } catch (\Throwable $e) {}

    // Conversations today + yesterday (range queries to allow index usage)
    try {
        $stmt = $alfDb->prepare("SELECT COUNT(*) FROM alfred_conversations WHERE user_id = ? AND created_at >= CURDATE() AND created_at < CURDATE() + INTERVAL 1 DAY");
        $stmt->execute([$clientId]);
        $alfredStats['conversations_today'] = (int)$stmt->fetchColumn();
        $stmt = $alfDb->prepare("SELECT COUNT(*) FROM alfred_conversations WHERE user_id = ? AND created_at >= CURDATE() - INTERVAL 1 DAY AND created_at < CURDATE()");
        $stmt->execute([$clientId]);
        $alfredStats['conversations_yesterday'] = (int)$stmt->fetchColumn();
    } catch (\Throwable $e) {}

    // API calls today + yesterday (range queries to allow index usage)
    try {
        $stmt = $alfDb->prepare("SELECT COUNT(*) FROM alfred_usage WHERE user_id = ? AND resource_type = 'api_call' AND created_at >= CURDATE() AND created_at < CURDATE() + INTERVAL 1 DAY");
        $stmt->execute([$clientId]);
        $alfredStats['api_calls_today'] = (int)$stmt->fetchColumn();
        $stmt = $alfDb->prepare("SELECT COUNT(*) FROM alfred_usage WHERE user_id = ? AND resource_type = 'api_call' AND created_at >= CURDATE() - INTERVAL 1 DAY AND created_at < CURDATE()");
        $stmt->execute([$clientId]);
        $alfredStats['api_calls_yesterday'] = (int)$stmt->fetchColumn();
    } catch (\Throwable $e) {}

    // Current plan
    try {
        $stmt = $alfDb->prepare("SELECT plan_name, plan_limits FROM alfred_subscriptions WHERE user_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$clientId]);
        $planRow = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($planRow) {
            $alfredStats['current_plan'] = $planRow['plan_name'];
            $limits = json_decode($planRow['plan_limits'] ?? '{}', true);
            if ($limits) $alfredStats['plan_limits'] = array_merge($alfredStats['plan_limits'], $limits);
        }
    } catch (\Throwable $e) {}

    // Usage totals this month
    try {
        $stmt = $alfDb->prepare("SELECT resource_type, COALESCE(SUM(quantity), 0) as total FROM alfred_usage WHERE user_id = ? AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01') AND created_at < DATE_FORMAT(CURDATE(), '%Y-%m-01') + INTERVAL 1 MONTH GROUP BY resource_type");
        $stmt->execute([$clientId]);
        while ($uRow = $stmt->fetch(PDO::FETCH_ASSOC)) {
            switch ($uRow['resource_type']) {
                case 'api_call': $alfredStats['usage']['api_calls'] = (int)$uRow['total']; break;
                case 'voice_minute': $alfredStats['usage']['voice_minutes'] = (int)$uRow['total']; break;
                case 'storage': $alfredStats['usage']['storage_mb'] = (int)$uRow['total']; break;
            }
        }
        $stmt = $alfDb->prepare("SELECT COUNT(*) FROM alfred_fleet_agents fa JOIN alfred_fleets f ON fa.fleet_id = f.id WHERE f.user_id = ?");
        $stmt->execute([$clientId]);
        $alfredStats['usage']['agents'] = (int)$stmt->fetchColumn();
    } catch (\Throwable $e) {}

    // Recent activity (last 10)
    try {
        $stmt = $alfDb->prepare("SELECT resource_type, IFNULL(description, '') as description, COALESCE(quantity, 0) as quantity, created_at, IFNULL(status, 'completed') as status FROM alfred_usage WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
        $stmt->execute([$clientId]);
        $alfredStats['recent_activity'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {}

    unset($alfDb, $stmt, $planRow, $limits, $uRow);
} catch (\Throwable $e) {
    error_log('Alfred dashboard data error: ' . $e->getMessage());
}

// Force Alfred-Opus sovereign display regardless of DB state
if (ALFRED_LOCAL_SOVEREIGN) {
    $alfredStats['current_plan'] = 'Alfred-Opus · Local · Sovereign';
    $alfredStats['plan_limits']  = ['api_calls'=>0,'voice_minutes'=>0,'storage_mb'=>0,'agents'=>0];
    $alfredStats['api_calls_today'] = 0;
    $alfredStats['api_calls_yesterday'] = 0;
    $alfredStats['blueprints'] = alfred_load_blueprints();
}


$autonomySummary = null;
if ((int)$clientId === 33) {
    $autonomySummary = gsmOpsSummary();
}

// ── Helper functions ──
function alfredTrend($today, $yesterday) {
    if ($yesterday == 0) return $today > 0 ? ['up', 100] : ['flat', 0];
    $pct = round((($today - $yesterday) / $yesterday) * 100);
    if ($pct > 0) return ['up', $pct];
    if ($pct < 0) return ['down', abs($pct)];
    return ['flat', 0];
}
function usageBarColor($used, $limit) {
    if ($limit <= 0) return '#10b981';
    $pct = ($used / $limit) * 100;
    if ($pct >= 80) return '#ef4444';
    if ($pct >= 60) return '#f59e0b';
    return '#10b981';
}
function usagePct($used, $limit) {
    if ($limit <= 0) return 0;
    return min(100, round(($used / $limit) * 100));
}
function alfredTimeAgo($datetime) {
    $ts = strtotime($datetime);
    if (!$ts) return '';
    $diff = time() - $ts;
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j', $ts);
}
function activityIcon($type) {
    switch ($type) {
        case 'api_call': return 'fa-code';
        case 'voice_minute': return 'fa-microphone';
        case 'tool_execution': return 'fa-wrench';
        case 'conversation': return 'fa-comments';
        case 'agent_deploy': return 'fa-robot';
        default: return 'fa-circle-dot';
    }
}
function activityLabel($type, $desc) {
    if (!empty($desc)) return htmlspecialchars($desc);
    switch ($type) {
        case 'api_call': return 'API call executed';
        case 'voice_minute': return 'Voice call processed';
        case 'tool_execution': return 'Tool executed';
        case 'conversation': return 'Conversation started';
        case 'agent_deploy': return 'Agent deployed';
        default: return ucfirst(str_replace('_', ' ', $type));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Dashboard - GoSiteMe</title>
    
    <link rel="stylesheet" href="/assets/css/fonts.css">
    <link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/design-tokens.css?v=20260310">
    <link rel="stylesheet" href="/assets/css/components.css?v=20260310">
    
    <style>
        /* Dashboard extends design-tokens.css — only dashboard-specific overrides here */
        :root {
            --dark-card: #161636;
            --border: rgba(255,255,255,0.06);
            --border-hover: rgba(0,168,255,0.3);
            --glow: var(--gds-shadow-glow);
            --danger: #ef4444;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--dark);
            color: var(--text);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }
        
        .dashboard {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background: #12122a;
            border-right: 1px solid var(--border);
            padding: 20px 16px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
        }
        .sidebar::-webkit-scrollbar { width: 4px; }
        .sidebar::-webkit-scrollbar-track { background: transparent; }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.08); border-radius: 4px; }
        
        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 32px;
            padding: 8px 12px;
            text-decoration: none;
        }
        
        .sidebar-logo img {
            height: 36px;
            opacity: 0.9;
            transition: opacity 0.2s;
        }
        .sidebar-logo:hover img { opacity: 1; }
        
        .sidebar-nav {
            list-style: none;
        }
        
        .sidebar-nav li {
            margin-bottom: 2px;
        }
        
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            color: var(--text-muted);
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.2s ease;
            font-size: 13.5px;
            font-weight: 500;
            position: relative;
        }
        
        .sidebar-nav a:hover {
            background: rgba(255,255,255,0.04);
            color: var(--text);
        }
        .sidebar-nav a.active {
            background: rgba(0, 168, 255, 0.1);
            color: var(--cyan);
        }
        .sidebar-nav a.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 20px;
            background: var(--cyan);
            border-radius: 0 3px 3px 0;
        }
        
        .sidebar-nav a i {
            width: 20px;
            text-align: center;
            font-size: 14px;
        }
        
        .sidebar-section {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
        }
        
        .sidebar-section h4 {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: rgba(136,136,170,0.6);
            margin-bottom: 12px;
            padding-left: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-right: 14px;
            user-select: none;
        }
        .sidebar-section h4::after {
            content: '\f078';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            font-size: 9px;
            transition: transform 0.2s ease;
        }
        .sidebar-section.collapsed h4::after {
            transform: rotate(-90deg);
        }
        .sidebar-section.collapsed > .sidebar-nav {
            display: none;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 32px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }
        
        .header h1 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 2rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #7D00FF, #00A8FF);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 32px;
        }
        
        .stat-card {
            background: var(--dark-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--cyan), var(--purple));
            opacity: 0;
            transition: opacity 0.3s;
        }
        .stat-card:hover {
            border-color: var(--border-hover);
            transform: translateY(-2px);
            box-shadow: var(--glow);
        }
        .stat-card:hover::before { opacity: 1; }
        
        .stat-card .icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            margin-bottom: 16px;
        }
        
        .stat-card .icon.blue { background: rgba(0, 168, 255, 0.12); color: var(--cyan); }
        .stat-card .icon.green { background: rgba(34, 197, 94, 0.12); color: var(--success); }
        .stat-card .icon.purple { background: rgba(125, 0, 255, 0.12); color: var(--purple); }
        .stat-card .icon.orange { background: rgba(245, 158, 11, 0.12); color: var(--warning); }
        
        .stat-card h3 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 2rem;
            margin-bottom: 4px;
            color: var(--text);
        }
        
        .stat-card p {
            color: var(--text-muted);
            font-size: 0.85rem;
        }
        
        /* Content Cards */
        .content-card {
            background: var(--dark-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            margin-bottom: 24px;
            transition: border-color 0.2s;
        }
        .content-card:hover {
            border-color: rgba(255,255,255,0.1);
        }
        
        .content-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
        }
        
        .content-card-header h2 {
            font-size: 1.15rem;
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .content-card-header h2 i {
            font-size: 1rem;
            color: var(--cyan);
        }
        
        .content-card-body {
            padding: 24px;
        }
        
        /* Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th,
        .data-table td {
            padding: 14px 16px;
            text-align: left;
        }
        
        .data-table th {
            background: rgba(0, 168, 255, 0.06);
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
        }
        
        .data-table th:first-child {
            border-radius: 8px 0 0 8px;
        }
        
        .data-table th:last-child {
            border-radius: 0 8px 8px 0;
        }
        
        .data-table td {
            border-bottom: 1px solid var(--border);
            font-size: 13.5px;
            color: var(--text-muted);
        }
        
        .data-table tr:last-child td {
            border-bottom: none;
        }
        
        .data-table tr:hover td {
            background: rgba(0, 168, 255, 0.04);
        }
        .data-table td strong {
            color: var(--text);
        }
        
        /* Status badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: 100px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }
        .badge::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        
        .badge-success { background: rgba(34, 197, 94, 0.12); color: var(--success); }
        .badge-success::before { background: var(--success); box-shadow: 0 0 6px var(--success); animation: badgePulse 2s infinite; }
        .badge-warning { background: rgba(245, 158, 11, 0.12); color: var(--warning); }
        .badge-warning::before { background: var(--warning); animation: badgePulse 1.5s infinite; }
        .badge-danger { background: rgba(239, 68, 68, 0.12); color: var(--danger); }
        .badge-danger::before { background: var(--danger); }
        .badge-info { background: rgba(0, 168, 255, 0.12); color: var(--cyan); }
        .badge-info::before { background: var(--cyan); }
        .badge-secondary { background: rgba(255, 255, 255, 0.05); color: var(--text-muted); }
        .badge-secondary::before { background: var(--text-muted); }
        @keyframes badgePulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }
        
        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #0074D9, #00A8FF);
            color: #fff;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 116, 217, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #c0392b, #ef4444);
            color: #fff;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.8rem;
        }
        
        .btn-outline {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: var(--text);
        }
        
        .btn-outline:hover {
            border-color: var(--cyan);
            color: var(--cyan);
        }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 48px;
            color: var(--text-muted);
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        
        /* Loading */
        .loading {
            text-align: center;
            padding: 32px;
            color: var(--cyan);
        }

        /* PIN Section */
        .pin-setup {
            display: flex;
            gap: 32px;
            align-items: flex-start;
            flex-wrap: wrap;
        }

        .pin-info {
            flex: 1;
            min-width: 260px;
        }

        .pin-info h3 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.1rem;
            margin-bottom: 10px;
            color: var(--cyan);
        }

        .pin-info p {
            color: var(--text-muted);
            font-size: 0.9rem;
            line-height: 1.6;
            margin-bottom: 12px;
        }

        .pin-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 16px;
        }

        .pin-status-badge.set {
            background: rgba(16, 185, 129, 0.15);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .pin-status-badge.not-set {
            background: rgba(245, 158, 11, 0.15);
            color: var(--warning);
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .pin-form {
            flex: 1;
            min-width: 260px;
        }

        .pin-inputs {
            display: flex;
            gap: 12px;
            margin-bottom: 16px;
            align-items: center;
        }

        .pin-digit {
            width: 56px;
            height: 64px;
            text-align: center;
            font-size: 1.8rem;
            font-weight: 700;
            background: rgba(0, 0, 0, 0.3);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: var(--cyan);
            caret-color: var(--cyan);
            outline: none;
            transition: all 0.2s ease;
        }

        .pin-digit:focus {
            border-color: var(--cyan);
            box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.15);
        }

        .pin-message {
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 0.875rem;
            margin-top: 12px;
            display: none;
        }

        .pin-message.success {
            background: rgba(16, 185, 129, 0.15);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.3);
            display: block;
        }

        .pin-message.error {
            background: rgba(239, 68, 68, 0.15);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.3);
            display: block;
        }

        .alfred-callout {
            background: linear-gradient(135deg, rgba(125, 0, 255, 0.1), rgba(0, 168, 255, 0.1));
            border: 1px solid rgba(0, 212, 255, 0.2);
            border-radius: 12px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
        }

        .alfred-callout .alfred-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, #7D00FF, #00A8FF);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        .alfred-callout p {
            color: var(--text-muted);
            font-size: 0.875rem;
            line-height: 1.5;
            margin: 0;
        }

        .alfred-callout strong {
            color: var(--cyan);
        }
        
        /* Welcome Banner */
        .welcome-banner {
            display: flex; justify-content: space-between; align-items: center;
            background: linear-gradient(135deg, #1a103d 0%, #0f1b4d 40%, #0a2a5e 70%, #0d3868 100%);
            padding: 2.5rem 3rem; border-radius: 20px; margin-bottom: 2rem;
            position: relative; overflow: hidden;
            border: 1px solid rgba(0,168,255,0.15);
        }
        .welcome-banner::before {
            content: ''; position: absolute; top: -50%; right: -20%;
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(0,168,255,0.12) 0%, transparent 70%);
            pointer-events: none;
        }
        .welcome-banner::after {
            content: ''; position: absolute; bottom: -30%; left: 10%;
            width: 300px; height: 300px;
            background: radial-gradient(circle, rgba(124,58,237,0.1) 0%, transparent 70%);
            pointer-events: none;
        }
        .welcome-banner .welcome-text { position: relative; z-index: 1; }
        .welcome-banner .welcome-text h1 {
            font-family: 'Space Grotesk', sans-serif; font-size: 1.75rem;
            color: #fff; margin-bottom: 0.25rem;
        }
        .welcome-banner .welcome-text p { color: rgba(255,255,255,0.7); font-size: 0.95rem; }
        .welcome-banner .welcome-actions { display: flex; gap: 12px; position: relative; z-index: 1; }
        .welcome-banner .btn-secondary {
            background: rgba(255,255,255,0.08); color: #fff;
            border: 1px solid rgba(255,255,255,0.15); padding: 10px 20px;
            border-radius: 10px; font-weight: 600; text-decoration: none; transition: all 0.3s;
            backdrop-filter: blur(8px);
        }
        .welcome-banner .btn-secondary:hover { background: rgba(255,255,255,0.15); border-color: rgba(255,255,255,0.3); }
        .welcome-banner .btn-primary { text-decoration: none; box-shadow: 0 0 20px rgba(0,168,255,0.3); }

        /* Alfred Stats Row */
        .alfred-stats-row {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px; margin-bottom: 2rem;
        }
        .alfred-stat-card {
            background: var(--dark-card); padding: 1.5rem;
            border-radius: 14px; border: 1px solid var(--border);
            position: relative; overflow: hidden;
            transition: all 0.3s;
        }
        .alfred-stat-card:hover {
            border-color: var(--border-hover);
            transform: translateY(-2px);
            box-shadow: var(--glow);
        }
        .alfred-stat-card::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0;
            height: 2px; background: linear-gradient(90deg, #6c5ce7, #00A8FF);
            opacity: 0.7;
        }
        .alfred-stat-card .stat-icon {
            width: 42px; height: 42px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem; margin-bottom: 12px;
        }
        .alfred-stat-card .stat-number {
            font-family: 'Space Grotesk', sans-serif; font-size: 2rem;
            font-weight: 700; color: var(--text); line-height: 1;
        }
        .alfred-stat-card .stat-label { color: var(--text-muted); font-size: 0.85rem; margin-top: 4px; }
        .alfred-stat-card .stat-trend {
            display: inline-flex; align-items: center; gap: 4px;
            font-size: 0.75rem; font-weight: 600; margin-top: 8px;
            padding: 2px 8px; border-radius: 12px;
        }
        .stat-trend.up { background: rgba(16,185,129,0.15); color: #10b981; }
        .stat-trend.down { background: rgba(239,68,68,0.15); color: #ef4444; }
        .stat-trend.flat { background: rgba(255,255,255,0.08); color: var(--text-muted); }

        /* Quick Actions Grid */
        .quick-actions-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 12px; margin-bottom: 2rem;
        }
        .action-card {
            background: var(--dark-card); padding: 1.5rem; border-radius: 14px;
            cursor: pointer; transition: all 0.25s ease;
            border: 1px solid var(--border); text-decoration: none;
            color: inherit; display: block; position: relative; overflow: hidden;
        }
        .action-card::after {
            content: ''; position: absolute; inset: 0;
            background: linear-gradient(135deg, transparent, rgba(0,168,255,0.03));
            opacity: 0; transition: opacity 0.3s;
        }
        .action-card:hover {
            transform: translateY(-3px); border-color: var(--border-hover);
            box-shadow: 0 8px 32px rgba(0,0,0,0.3), 0 0 0 1px rgba(0,168,255,0.15);
        }
        .action-card:hover::after { opacity: 1; }
        .action-card .action-icon {
            width: 44px; height: 44px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.15rem; margin-bottom: 12px;
        }
        .action-card h3 { font-size: 1rem; font-weight: 600; margin-bottom: 4px; color: var(--text); }
        .action-card p { color: var(--text-muted); font-size: 0.8rem; line-height: 1.4; }

        /* Activity Feed */
        .activity-item {
            display: flex; align-items: center; gap: 16px;
            padding: 14px 0; border-bottom: 1px solid var(--border);
            transition: background 0.2s;
        }
        .activity-item:hover { background: rgba(0,168,255,0.03); border-radius: 8px; padding-left: 8px; }
        .activity-item:last-child { border-bottom: none; }
        .activity-item .activity-icon {
            width: 38px; height: 38px; border-radius: 10px;
            background: rgba(108, 92, 231, 0.12); color: #6c5ce7;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.9rem; flex-shrink: 0;
        }
        .activity-item .activity-details { flex: 1; }
        .activity-item .activity-desc { font-size: 0.875rem; color: var(--text); }
        .activity-item .activity-time { font-size: 0.75rem; color: var(--text-muted); margin-top: 2px; }
        .activity-item .activity-badge { font-size: 0.65rem; padding: 3px 10px; border-radius: 100px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px; }

        /* Usage Meters */
        .usage-meters {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem;
        }
        .usage-meter-item { margin-bottom: 0.5rem; }
        .usage-meter-item .usage-header {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;
        }
        .usage-meter-item .usage-label { font-size: 0.85rem; font-weight: 600; color: var(--text); }
        .usage-meter-item .usage-count { font-size: 0.8rem; color: var(--text-muted); }
        .usage-bar { height: 8px; background: rgba(255,255,255,0.08); border-radius: 4px; overflow: hidden; }
        .usage-bar-fill { height: 100%; border-radius: 4px; transition: width 0.6s ease; }

        /* System Status */
        .system-status {
            display: flex; align-items: center; gap: 12px; padding: 16px 20px;
            background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 12px; margin-top: 1.5rem;
        }
        .system-status .status-dot {
            width: 10px; height: 10px; border-radius: 50%; background: #10b981;
            box-shadow: 0 0 8px rgba(16, 185, 129, 0.5); animation: statusPulse 2s infinite;
        }
        @keyframes statusPulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        .system-status .status-text { font-size: 0.9rem; font-weight: 600; color: #10b981; flex: 1; }
        .system-status .status-link { font-size: 0.8rem; color: var(--text-muted); text-decoration: none; }
        .system-status .status-link:hover { color: var(--cyan); }

        /* Section title */
        .dash-section-title {
            font-family: 'Space Grotesk', sans-serif; font-size: 1.15rem; font-weight: 600;
            margin-bottom: 1rem; color: var(--text); display: flex; align-items: center; gap: 8px;
        }
        .dash-section-title i { color: #6c5ce7; }

        /* Upgrade nudge */
        .upgrade-nudge {
            display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px;
            background: rgba(239, 68, 68, 0.15); color: #ef4444; border-radius: 8px;
            font-size: 0.75rem; font-weight: 600; text-decoration: none;
        }
        .upgrade-nudge:hover { background: rgba(239, 68, 68, 0.25); }

        /* ══════════════════════════════════════════════════
           DASHBOARD — Mobile Responsive
           ══════════════════════════════════════════════════ */

        /* Mobile sidebar toggle button */
        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 16px;
            left: 16px;
            z-index: 1100;
            width: 44px;
            height: 44px;
            border: none;
            border-radius: 12px;
            background: var(--dark-card);
            backdrop-filter: blur(10px);
            color: var(--cyan);
            font-size: 1.25rem;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            transition: background 0.2s;
        }
        .sidebar-toggle:hover {
            background: rgba(0,168,255,0.15);
        }

        /* Sidebar overlay for mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.6);
            z-index: 1049;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .sidebar-overlay.active {
            opacity: 1;
        }

        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(3, 1fr) !important;
            }
        }

        @media (max-width: 992px) {
            /* Show hamburger toggle */
            .sidebar-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .sidebar-overlay {
                display: block;
                pointer-events: none;
            }
            .sidebar-overlay.active {
                pointer-events: auto;
            }

            /* Slide sidebar off-screen */
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                z-index: 1050;
            }
            .sidebar.open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 24px 20px;
                padding-top: 72px; /* space for hamburger */
            }
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            .header h1 {
                font-size: 1.5rem;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 16px;
            }
            .alfred-stats-row {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 16px 12px;
                padding-top: 68px;
            }
            .stats-grid {
                grid-template-columns: 1fr !important;
                gap: 12px;
            }
            .stat-card {
                padding: 20px;
            }
            .stat-card h3 {
                font-size: 1.5rem;
            }

            /* Welcome banner */
            .welcome-banner {
                flex-direction: column !important;
                text-align: center;
                gap: 1rem;
            }
            .welcome-banner .welcome-actions {
                flex-direction: column;
                width: 100%;
            }
            .welcome-banner .welcome-actions .btn {
                width: 100%;
                justify-content: center;
            }

            /* Alfred stats */
            .alfred-stats-row {
                grid-template-columns: 1fr !important;
            }

            /* Quick actions */
            .quick-actions-grid {
                grid-template-columns: 1fr !important;
            }

            /* Tables: horizontal scroll */
            .content-card-body {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            .data-table {
                font-size: 0.82rem;
                min-width: 580px;
            }
            .data-table th,
            .data-table td {
                padding: 10px 12px;
                white-space: nowrap;
            }

            /* Content card headers */
            .content-card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
                padding: 16px;
            }
            .content-card-header h2 {
                font-size: 1.1rem;
            }

            /* Credit section */
            .credit-row {
                flex-direction: column;
            }
            .credit-balance-card {
                min-width: unset;
                width: 100%;
            }
            .credit-history {
                min-width: unset;
                width: 100%;
            }

            /* Profile form */
            .profile-form-grid {
                grid-template-columns: 1fr;
            }
            .form-actions {
                flex-direction: column;
            }
            .form-actions .btn {
                width: 100%;
                justify-content: center;
            }

            /* Security grid */
            .security-grid {
                grid-template-columns: 1fr;
            }
            .security-link-card {
                padding: 16px;
            }

            /* Payment methods */
            .payment-method-item {
                flex-wrap: wrap;
            }

            /* PIN section */
            .pin-setup {
                flex-direction: column;
            }
            .pin-form, .pin-info {
                min-width: unset;
                width: 100%;
            }

            /* Email rows */
            .email-row {
                flex-wrap: wrap;
            }

            /* Usage meters */
            .usage-meters {
                gap: 12px !important;
            }

            /* Recent activity */
            .activity-item {
                flex-wrap: wrap;
            }

            /* Header user info */
            .user-info {
                width: 100%;
                justify-content: flex-start;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 12px 8px;
                padding-top: 64px;
            }
            .content-card {
                border-radius: 12px;
            }
            .stat-card {
                padding: 16px;
                border-radius: 12px;
            }
            .stat-card .icon {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }
            .stat-card h3 {
                font-size: 1.3rem;
            }
            .credit-balance-card .credit-amount {
                font-size: 2rem;
            }
            .header h1 {
                font-size: 1.25rem;
            }
        }

        /* ─── Profile Form ─── */
        .profile-form-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 20px;
        }
        .form-group { margin-bottom: 0; }
        .form-group label {
            display: block; font-size: 0.8rem; font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.5px;
            color: var(--text-muted); margin-bottom: 6px;
        }
        .form-group input, .form-group select {
            width: 100%; padding: 10px 14px; font-size: 0.9rem;
            background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px; color: var(--text); outline: none;
            font-family: 'Inter', sans-serif; transition: border-color 0.2s;
        }
        .form-group input:focus, .form-group select:focus {
            border-color: var(--cyan); box-shadow: 0 0 0 3px rgba(0,212,255,0.1);
        }
        .form-group.full-width { grid-column: 1 / -1; }
        .form-actions {
            grid-column: 1 / -1; display: flex; gap: 12px;
            justify-content: flex-end; margin-top: 8px;
        }
        .profile-member-since {
            display: inline-flex; align-items: center; gap: 8px;
            font-size: 0.8rem; color: var(--text-muted);
            background: rgba(108,92,231,0.1); padding: 6px 14px;
            border-radius: 8px; margin-bottom: 20px;
        }

        /* ─── Credit Balance Card ─── */
        .credit-row {
            display: flex; gap: 24px; align-items: flex-start; flex-wrap: wrap;
        }
        .credit-balance-card {
            background: linear-gradient(135deg, rgba(125,0,255,0.15), rgba(0,168,255,0.15));
            border: 1px solid rgba(125,0,255,0.2); border-radius: 14px;
            padding: 28px; min-width: 260px; flex-shrink: 0; text-align: center;
        }
        .credit-balance-card .credit-amount {
            font-family: 'Space Grotesk', sans-serif; font-size: 2.5rem;
            font-weight: 700; color: var(--text); margin-bottom: 4px;
        }
        .credit-balance-card .credit-label {
            font-size: 0.85rem; color: var(--text-muted); margin-bottom: 16px;
        }
        .credit-history { flex: 1; min-width: 300px; }
        .credit-history-item {
            display: flex; justify-content: space-between; align-items: center;
            padding: 12px 0; border-bottom: 1px solid var(--border);
            font-size: 0.875rem; transition: background 0.2s;
        }
        .credit-history-item:hover { background: rgba(0,168,255,0.03); border-radius: 6px; padding: 12px 8px; }
        .credit-history-item:last-child { border-bottom: none; }
        .credit-history-item .credit-desc { color: var(--text-muted); }
        .credit-history-item .credit-amt.positive { color: var(--success); }
        .credit-history-item .credit-amt.negative { color: var(--danger); }

        /* ─── Payment Method Card ─── */
        .payment-method-item {
            display: flex; align-items: center; gap: 16px; padding: 16px 20px;
            background: var(--dark-card); border: 1px solid var(--border);
            border-radius: 12px; margin-bottom: 12px;
            transition: border-color 0.2s, transform 0.15s;
        }
        .payment-method-item:hover { border-color: var(--border-hover); transform: translateX(4px); }
        .payment-method-item:last-child { margin-bottom: 0; }
        .payment-method-icon {
            width: 48px; height: 48px; border-radius: 10px;
            background: rgba(0,168,255,0.12); color: var(--cyan);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.25rem;
        }
        .payment-method-info { flex: 1; }
        .payment-method-info .pm-desc { font-weight: 600; color: var(--text); }
        .payment-method-info .pm-type { font-size: 0.8rem; color: var(--text-muted); margin-top: 2px; }

        /* ─── Email row ─── */
        .email-row {
            display: flex; align-items: center; gap: 14px; padding: 14px 0;
            border-bottom: 1px solid var(--border); cursor: pointer;
            transition: background 0.2s;
        }
        .email-row:last-child { border-bottom: none; }
        .email-row:hover { background: rgba(0,168,255,0.05); border-radius: 8px; padding-left: 8px; }
        .email-icon {
            width: 36px; height: 36px; border-radius: 8px;
            background: rgba(108,92,231,0.15); color: #6c5ce7;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.85rem; flex-shrink: 0;
        }
        .email-details { flex: 1; }
        .email-subject { font-size: 0.875rem; color: var(--text); font-weight: 500; }
        .email-date { font-size: 0.75rem; color: var(--text-muted); margin-top: 2px; }

        /* ─── Security Links ─── */
        .security-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;
        }
        .security-link-card {
            display: flex; align-items: center; gap: 16px; padding: 20px;
            background: var(--dark-card); border: 1px solid var(--border);
            border-radius: 12px; text-decoration: none; color: inherit;
            transition: border-color 0.2s, transform 0.2s, box-shadow 0.2s;
        }
        .security-link-card:hover {
            border-color: var(--cyan); transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,168,255,0.1);
        }
        .security-link-card .sec-icon {
            width: 48px; height: 48px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.15rem;
        }
        .security-link-card h4 { font-size: 1rem; font-weight: 600; color: var(--text); margin-bottom: 2px; }
        .security-link-card p { font-size: 0.8rem; color: var(--text-muted); }

        /* Badge for primary payment */
        .badge-primary { background: rgba(0, 116, 217, 0.2); color: var(--primary-light); }

        /* ─── Premium Services Section ─── */
        .svc-stats-strip {
            display: flex; gap: 16px; margin-bottom: 24px; flex-wrap: wrap;
        }
        .svc-stat {
            display: flex; align-items: center; gap: 8px;
            background: var(--dark-card); border: 1px solid var(--border);
            border-radius: 12px; padding: 12px 20px;
            flex: 1; min-width: 120px;
        }
        .svc-stat-dot {
            width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0;
        }
        .svc-stat-dot.active { background: var(--success); box-shadow: 0 0 8px rgba(34,197,94,0.5); }
        .svc-stat-dot.pending { background: var(--warning); box-shadow: 0 0 8px rgba(245,158,11,0.5); }
        .svc-stat-dot.suspended { background: var(--danger); box-shadow: 0 0 8px rgba(239,68,68,0.5); }
        .svc-stat-dot.cancelled { background: #6b7280; }
        .svc-stat-value { font-size: 1.25rem; font-weight: 700; color: var(--text); }
        .svc-stat-label { font-size: 0.8rem; color: var(--text-muted); }

        .svc-filter-bar {
            display: flex; align-items: center; gap: 8px; margin-bottom: 24px;
            flex-wrap: wrap; padding: 8px; background: var(--dark-card);
            border: 1px solid var(--border); border-radius: 14px;
        }
        .svc-filter {
            display: flex; align-items: center; gap: 6px; padding: 8px 16px;
            border: none; background: transparent; color: var(--text-muted);
            border-radius: 10px; cursor: pointer; font-size: 0.85rem;
            font-weight: 500; transition: all 0.2s; font-family: inherit;
        }
        .svc-filter:hover { color: var(--text); background: rgba(255,255,255,0.05); }
        .svc-filter.active { color: #fff; background: rgba(0,168,255,0.2); }
        .svc-filter-count {
            background: rgba(255,255,255,0.08); padding: 2px 8px;
            border-radius: 20px; font-size: 0.75rem; font-weight: 600;
        }
        .svc-filter.active .svc-filter-count {
            background: rgba(0,168,255,0.3);
        }
        .svc-filter-right {
            margin-left: auto; display: flex; gap: 4px;
        }
        .svc-view-toggle {
            width: 36px; height: 36px; display: flex; align-items: center;
            justify-content: center; border: none; background: transparent;
            color: var(--text-muted); border-radius: 8px; cursor: pointer;
            transition: all 0.2s; font-size: 0.9rem;
        }
        .svc-view-toggle:hover { color: var(--text); background: rgba(255,255,255,0.05); }
        .svc-view-toggle.active { color: var(--cyan); background: rgba(0,168,255,0.15); }

        .svc-group { margin-bottom: 32px; }
        .svc-group-header {
            display: flex; align-items: center; gap: 14px; margin-bottom: 16px;
        }
        .svc-group-icon {
            width: 44px; height: 44px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem; flex-shrink: 0;
        }
        .svc-group-title {
            font-size: 1.1rem; font-weight: 700; color: var(--text);
        }
        .svc-group-count {
            font-size: 0.8rem; color: var(--text-muted);
        }
        .svc-group-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 16px;
        }

        .svc-card {
            background: var(--dark-card); border: 1px solid var(--border);
            border-radius: 16px; overflow: hidden;
            transition: border-color 0.3s, transform 0.3s, box-shadow 0.3s;
            opacity: 0; transform: translateY(12px);
            animation: svcCardIn 0.4s ease forwards;
        }
        .svc-card:hover {
            border-color: var(--card-accent, var(--cyan));
            transform: translateY(-3px);
            box-shadow: 0 8px 32px rgba(0,0,0,0.3), 0 0 0 1px var(--card-accent, var(--cyan));
        }
        @keyframes svcCardIn {
            to { opacity: 1; transform: translateY(0); }
        }
        .svc-card-accent {
            height: 3px;
            background: linear-gradient(90deg, var(--card-accent, var(--cyan)), transparent);
        }
        .svc-card-body { padding: 20px; }
        .svc-card-top {
            display: flex; justify-content: space-between; align-items: flex-start;
            margin-bottom: 16px; gap: 12px;
        }
        .svc-card-product { display: flex; align-items: center; gap: 12px; flex: 1; min-width: 0; }
        .svc-product-icon {
            width: 40px; height: 40px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem; flex-shrink: 0;
        }
        .svc-card-title {
            font-size: 0.95rem; font-weight: 600; color: var(--text);
            margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .svc-card-domain {
            font-size: 0.78rem; color: var(--cyan); display: block;
            margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .svc-card-domain i { margin-right: 4px; font-size: 0.7rem; }

        .svc-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 4px 12px; border-radius: 20px; font-size: 0.75rem;
            font-weight: 600; white-space: nowrap; flex-shrink: 0;
        }
        .svc-badge-dot {
            width: 7px; height: 7px; border-radius: 50%;
        }
        .svc-badge-active { background: rgba(34,197,94,0.15); color: #22c55e; }
        .svc-badge-active .svc-badge-dot { background: #22c55e; box-shadow: 0 0 6px rgba(34,197,94,0.6); }
        .svc-badge-pending { background: rgba(245,158,11,0.15); color: #f59e0b; }
        .svc-badge-pending .svc-badge-dot { background: #f59e0b; }
        .svc-badge-suspended { background: rgba(239,68,68,0.15); color: #ef4444; }
        .svc-badge-suspended .svc-badge-dot { background: #ef4444; }
        .svc-badge-cancelled, .svc-badge-terminated {
            background: rgba(107,114,128,0.15); color: #9ca3af;
        }
        .svc-badge-cancelled .svc-badge-dot, .svc-badge-terminated .svc-badge-dot { background: #6b7280; }

        .svc-card-info {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 12px; margin-bottom: 16px;
            padding: 14px; background: rgba(0,0,0,0.2);
            border-radius: 12px;
        }
        .svc-info-label {
            font-size: 0.72rem; color: var(--text-muted); display: block;
            margin-bottom: 3px; text-transform: uppercase; letter-spacing: 0.5px;
        }
        .svc-info-label i { margin-right: 4px; width: 14px; text-align: center; }
        .svc-info-value {
            font-size: 0.88rem; font-weight: 600; color: var(--text);
        }
        .svc-info-value .svc-free { color: var(--success); }
        .svc-due-soon { color: var(--danger) !important; }

        .svc-card-actions {
            display: flex; align-items: center; justify-content: space-between;
            gap: 12px; padding-top: 14px; border-top: 1px solid var(--border);
        }
        .svc-manage-btn {
            font-size: 0.82rem; padding: 7px 16px;
        }
        .svc-quick-actions {
            display: flex; gap: 6px;
        }
        .svc-action-link {
            width: 34px; height: 34px; display: flex; align-items: center;
            justify-content: center; border-radius: 8px; color: var(--text-muted);
            text-decoration: none; transition: all 0.2s; font-size: 0.85rem;
            background: rgba(255,255,255,0.04);
        }
        .svc-action-link:hover {
            color: var(--cyan); background: rgba(0,168,255,0.12);
        }
        .svc-sso-btn:hover { color: #22c55e; background: rgba(34,197,94,0.12); }
        .svc-pending-note { font-size: 0.8rem; color: var(--text-muted); }
        .svc-pending-note i { margin-right: 4px; }

        /* Services table view */
        .svc-table-view { display: none; }
        .svc-full-table {
            width: 100%; border-collapse: collapse;
        }
        .svc-full-table th {
            text-align: left; padding: 12px 16px; font-size: 0.75rem;
            text-transform: uppercase; letter-spacing: 0.5px;
            color: var(--text-muted); border-bottom: 1px solid var(--border);
            font-weight: 600;
        }
        .svc-full-table td {
            padding: 14px 16px; border-bottom: 1px solid var(--border);
            font-size: 0.88rem; color: var(--text);
        }
        .svc-full-table tbody tr { transition: background 0.2s; }
        .svc-full-table tbody tr:hover { background: rgba(0,168,255,0.04); }
        .svc-tbl-product { display: flex; align-items: center; gap: 10px; }
        .svc-tbl-dot {
            width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0;
        }
        .svc-tbl-group {
            font-size: 0.78rem; color: var(--text-muted);
            background: rgba(255,255,255,0.05); padding: 3px 10px;
            border-radius: 6px;
        }
        .svc-tbl-actions { display: flex; gap: 6px; justify-content: flex-end; }
        .svc-tbl-actions .btn { padding: 6px 10px; font-size: 0.78rem; }

        /* Services empty state */
        .svc-empty {
            text-align: center; padding: 60px 20px;
        }
        .svc-empty-icon {
            width: 80px; height: 80px; margin: 0 auto 20px;
            border-radius: 24px; background: rgba(0,168,255,0.1);
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem; color: var(--cyan);
        }
        .svc-empty h2 { font-size: 1.4rem; margin-bottom: 8px; }
        .svc-empty p { color: var(--text-muted); margin-bottom: 20px; }

        @media (max-width: 768px) {
            .svc-stats-strip { gap: 8px; }
            .svc-stat { padding: 10px 14px; min-width: 100px; }
            .svc-stat-value { font-size: 1rem; }
            .svc-group-grid { grid-template-columns: 1fr; }
            .svc-card-info { grid-template-columns: 1fr; }
            .svc-filter-bar { gap: 4px; padding: 6px; }
            .svc-filter { padding: 6px 12px; font-size: 0.8rem; }
        }
    </style>
</head>
<body>
    <!-- Mobile sidebar toggle -->
    <button class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu">
        <i class="fas fa-bars"></i>
    </button>
    <!-- Sidebar overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="dashboard">
        <!-- Sidebar -->
        <?php $isAdmin = in_array($clientId, [33]); ?>
        <aside class="sidebar" id="dashSidebar">
            <a href="/" class="sidebar-logo">
                <img src="/brand/logo.png" alt="GoSiteMe">
            </a>
            
            <ul class="sidebar-nav">
                <li><a href="#overview" class="active"><i class="fas fa-home"></i> Overview</a></li>
                <li><a href="#services"><i class="fas fa-server"></i> Services</a></li>
                <li><a href="#domains"><i class="fas fa-globe"></i> Domains</a></li>
                <li><a href="#invoices"><i class="fas fa-file-invoice-dollar"></i> Invoices</a></li>
                <li><a href="#tickets"><i class="fas fa-ticket"></i> Support Tickets</a></li>
                <li><a href="#support-pin"><i class="fas fa-shield-halved"></i> Support PIN</a></li>
                <li><a href="/voice-portal"><i class="fas fa-satellite-dish"></i> Voice & AI</a></li>
            </ul>

            <div class="sidebar-section">
                <h4>Billing</h4>
                <ul class="sidebar-nav">
                    <li><a href="#credit"><i class="fas fa-wallet"></i> Credit & Balance</a></li>
                    <li><a href="#payment-methods"><i class="fas fa-credit-card"></i> Payment Methods</a></li>
                    <li><a href="#quotes"><i class="fas fa-file-contract"></i> Quotes</a></li>
                    <li><a href="/pay/account/invoice.php"><i class="fas fa-file-invoice"></i> Invoices</a></li>
                </ul>
            </div>

            <div class="sidebar-section">
                <h4>Hosting & Site Tools</h4>
                <ul class="sidebar-nav">
                    <li><a href="/pay/account/dns.php"><i class="fas fa-sitemap"></i> DNS Manager</a></li>
                    <li><a href="/pay/account/ssl.php"><i class="fas fa-lock"></i> SSL Certificates</a></li>
                    <li><a href="/pay/account/email.php"><i class="fas fa-envelope"></i> Email Accounts</a></li>
                    <li><a href="/pay/account/files.php"><i class="fas fa-folder-open"></i> File Manager</a></li>
                    <li><a href="/pay/account/ftp.php"><i class="fas fa-upload"></i> FTP Accounts</a></li>
                    <li><a href="/pay/account/databases.php"><i class="fas fa-database"></i> Databases</a></li>
                    <li><a href="/pay/account/backups.php"><i class="fas fa-cloud-arrow-down"></i> Backups</a></li>
                    <li><a href="/pay/account/cron.php"><i class="fas fa-clock"></i> Cron Jobs</a></li>
                    <li><a href="/pay/account/subdomains.php"><i class="fas fa-diagram-project"></i> Subdomains</a></li>
                    <li><a href="/pay/account/addon-domains.php"><i class="fas fa-circle-plus"></i> Addon Domains</a></li>
                    <li><a href="/pay/account/domain-pointers.php"><i class="fas fa-arrows-turn-right"></i> Domain Pointers</a></li>
                    <li><a href="/pay/account/redirects.php"><i class="fas fa-share"></i> Redirects</a></li>
                    <li><a href="/pay/account/staging.php"><i class="fas fa-flask"></i> Staging</a></li>
                    <li><a href="/pay/account/deploy.php"><i class="fas fa-rocket"></i> Deploy Pipeline</a></li>
                </ul>
            </div>

            <div class="sidebar-section">
                <h4>Site Health</h4>
                <ul class="sidebar-nav">
                    <li><a href="/pay/account/uptime.php"><i class="fas fa-heart-pulse"></i> Uptime Monitor</a></li>
                    <li><a href="/pay/account/site-doctor.php"><i class="fas fa-stethoscope"></i> Site Doctor</a></li>
                    <li><a href="/pay/account/email-health.php"><i class="fas fa-envelope-circle-check"></i> Email Health</a></li>
                    <li><a href="/pay/account/stats.php"><i class="fas fa-chart-bar"></i> Statistics & Logs</a></li>
                    <li><a href="/pay/account/analytics.php"><i class="fas fa-chart-line"></i> Usage Analytics</a></li>
                    <li><a href="/pay/account/webhooks.php"><i class="fas fa-plug"></i> Webhooks</a></li>
                </ul>
            </div>

            <div class="sidebar-section">
                <h4>Website Builders</h4>
                <ul class="sidebar-nav">
                    <li><a href="/pay/account/website-builder.php"><i class="fas fa-wand-magic-sparkles"></i> Website Builder</a></li>
                    <li><a href="/pay/account/website-editor.php"><i class="fas fa-code"></i> Website Editor</a></li>
                    <li><a href="/pay/account/apps.php"><i class="fas fa-boxes-stacked"></i> App Installer</a></li>
                    <li><a href="/pay/account/collab.php"><i class="fas fa-people-arrows"></i> Collaboration</a></li>
                    <li><a href="/pay/account/image-generator.php"><i class="fas fa-image"></i> AI Image Generator</a></li>
                </ul>
            </div>

            <div class="sidebar-section">
                <h4>Account & Support</h4>
                <ul class="sidebar-nav">
                    <li><a href="/pay/account/contacts.php"><i class="fas fa-address-book"></i> My Contacts</a></li>
                    <li><a href="/pay/account/logins.php"><i class="fas fa-key"></i> My Logins</a></li>
                    <li><a href="/pay/account/onboarding.php"><i class="fas fa-flag-checkered"></i> Getting Started</a></li>
                    <li><a href="/pay/account/knowledge-base.php"><i class="fas fa-book-open"></i> Knowledge Base</a></li>
                    <li><a href="/pay/account/support-chat.php"><i class="fas fa-message-bot"></i> AI Support Chat</a></li>
                    <li><a href="/pay/account/tech-support.php"><i class="fas fa-screwdriver-wrench"></i> Tech Support</a></li>
                    <li><a href="/pay/account/affiliates.php"><i class="fas fa-handshake"></i> Affiliates</a></li>
                    <li><a href="/pay/account/whitelabel.php"><i class="fas fa-palette"></i> White Label</a></li>
                    <li><a href="/pay/account/reseller.php"><i class="fas fa-building"></i> Reseller Portal</a></li>
                </ul>
            </div>

            <div class="sidebar-section">
                <h4>Crypto & Tokens</h4>
                <ul class="sidebar-nav">
                    <li><a href="/pay/account/crypto.php"><i class="fas fa-coins"></i> Crypto Dashboard</a></li>
                    <li><a href="/pay/account/crypto-reports.php"><i class="fas fa-chart-pie"></i> Crypto Reports</a></li>
                    <li><a href="/pay/account/gsm-token.php"><i class="fas fa-gem"></i> GSM Token</a></li>
                    <li><a href="/pay/account/gsm-marketplace.php"><i class="fas fa-store"></i> GSM Marketplace</a></li>
                </ul>
            </div>

            <div class="sidebar-section">
                <h4>AI & Voice Tools</h4>
                <ul class="sidebar-nav">
                    <li><a href="/pay/account/agents.php"><i class="fas fa-robot"></i> AI Agents Hub</a></li>
                    <li><a href="/pay/account/marketplace.php"><i class="fas fa-store-alt"></i> Agent Marketplace</a></li>
                    <li><a href="/pay/account/voice-hosting.php"><i class="fas fa-microphone"></i> Voice Hosting</a></li>
                    <li><a href="/pay/account/voice-games.php"><i class="fas fa-gamepad"></i> Voice Games</a></li>
                    <li><a href="/pay/account/vr-world.php"><i class="fas fa-vr-cardboard"></i> VR World</a></li>
                    <li><a href="/pay/account/autopilot.php"><i class="fas fa-wand-sparkles"></i> Hosting Autopilot</a></li>
                    <li><a href="/pay/account/alfred-report.php"><i class="fas fa-file-lines"></i> Alfred Report</a></li>
                    <li><a href="/pay/account/api-docs.php"><i class="fas fa-book-atlas"></i> API Documentation</a></li>
                </ul>
            </div>

            <div class="sidebar-section">
                <h4>Alfred AI</h4>
                <ul class="sidebar-nav">
                    <li><a href="/conversations"><i class="fas fa-robot"></i> Chat with Alfred</a></li>
                    <li><a href="/team-chat"><i class="fas fa-users-cog"></i> Team Chat</a></li>
                    <li><a href="/conversations"><i class="fas fa-comments"></i> Conversations</a></li>
                    <li><a href="/fleet-dashboard"><i class="fas fa-layer-group"></i> Fleet Dashboard</a></li>
                    <li><a href="/agent-templates"><i class="fas fa-rocket"></i> Agent Templates</a></li>
                    <li><a href="/marketplace"><i class="fas fa-store"></i> Marketplace</a></li>
                    <li><a href="/marketplace-creator"><i class="fas fa-wand-magic-sparkles"></i> Marketplace Creator</a></li>
                    <li><a href="/alfred-tools"><i class="fas fa-wrench"></i> Alfred Tools</a></li>
                </ul>
            </div>

            <div class="sidebar-section">
                <h4>Voice & Communications</h4>
                <ul class="sidebar-nav">
                    <li><a href="/voice-cloning"><i class="fas fa-microphone-lines"></i> Voice Cloning</a></li>
                    <li><a href="/conference-room"><i class="fas fa-headset"></i> Conference Rooms</a></li>
                    <li><a href="/call-campaigns"><i class="fas fa-bullhorn"></i> Call Campaigns</a></li>
                    <li><a href="/ivr-builder"><i class="fas fa-sitemap"></i> IVR Builder</a></li>
                    <li><a href="/uc"><i class="fas fa-phone-flip"></i> Unified Comms</a></li>
                </ul>
            </div>

            <div class="sidebar-section">
                <h4>Explore</h4>
                <ul class="sidebar-nav">
                    <li><a href="/universe"><i class="fas fa-atom"></i> Universe</a></li>
                    <li><a href="/chronicles"><i class="fas fa-scroll"></i> Research Chronicles</a></li>
                    <li><a href="/health-research"><i class="fas fa-dna" style="color:#00e676"></i> Health Research</a></li>
                    <li><a href="/ecosystem"><i class="fas fa-diagram-project"></i> Ecosystem</a></li>
                </ul>
            </div>

            <div class="sidebar-section">
                <h4>Dashboards</h4>
                <ul class="sidebar-nav">
                    <li><a href="/finance-dashboard"><i class="fas fa-coins"></i> Finance Center</a></li>
                    <li><a href="/analytics"><i class="fas fa-chart-line"></i> Analytics</a></li>
                    <li><a href="/reporting-dashboard"><i class="fas fa-file-chart-line"></i> Reports</a></li>
                    <li><a href="/biz-dashboard"><i class="fas fa-briefcase"></i> Business Tools</a></li>
                    <li><a href="/collaboration-dashboard"><i class="fas fa-people-arrows"></i> Collaboration</a></li>
                    <li><a href="/healthcare-dashboard"><i class="fas fa-heartbeat"></i> Healthcare</a></li>
                    <li><a href="/gamification-dashboard"><i class="fas fa-trophy"></i> Gamification Hub</a></li>
                </ul>
            </div>

            <div class="sidebar-section">
                <h4>Developer</h4>
                <ul class="sidebar-nav">
                    <li><a href="/developer-portal"><i class="fas fa-code"></i> Developer Portal</a></li>
                    <li><a href="/webhooks"><i class="fas fa-plug"></i> Webhooks</a></li>
                    <li><a href="/integrations"><i class="fas fa-puzzle-piece"></i> Integrations</a></li>
                    <li><a href="/sdks"><i class="fas fa-cubes"></i> SDKs</a></li>
                    <li><a href="/alfred-ide.php"><i class="fas fa-terminal"></i> Alfred IDE</a></li>
                    <li><a href="/library.php"><i class="fas fa-book-open"></i> Kingdom Library</a></li>
                    <li><a href="/docs"><i class="fas fa-book"></i> Documentation</a></li>
                </ul>
            </div>

            <div class="sidebar-section">
                <h4>Tools & Apps</h4>
                <ul class="sidebar-nav">
                    <li><a href="/tools/"><i class="fas fa-toolbox"></i> Tools Directory</a></li>
                    <li><a href="/games"><i class="fas fa-gamepad"></i> Games</a></li>
                    <li><a href="/editor/"><i class="fas fa-paintbrush"></i> Website Editor</a></li>
                    <li><a href="/extensions"><i class="fas fa-puzzle-piece"></i> Extensions</a></li>
                    <li><a href="/downloads/33-jurisdictions/"><i class="fas fa-scale-balanced"></i> 33 Jurisdictions</a></li>
                    <li><a href="/ai-servers"><i class="fas fa-microchip"></i> AI Servers</a></li>
                    <li><a href="/open-source"><i class="fab fa-osi"></i> Open Source Hub</a></li>
                    <li><a href="/octopart-scraper.php"><i class="fas fa-search"></i> Octopart Scraper</a></li>
                </ul>
            </div>

            <?php if ($isAdmin): ?>
            <div class="sidebar-section">
                <h4 style="color: var(--danger);">Admin</h4>
                <ul class="sidebar-nav">
                    <li><a href="/pay/admin/"><i class="fas fa-gauge-high"></i> Billing Admin</a></li>
                    <li><a href="/enterprise-admin"><i class="fas fa-building-shield"></i> Enterprise Admin</a></li>
                    <li><a href="/investor-admin"><i class="fas fa-chart-pie"></i> Investor Admin</a></li>
                    <li><a href="/investor-dashboard"><i class="fas fa-money-bill-trend-up"></i> Investor Dashboard</a></li>
                    <li><a href="/admin/agenda"><i class="fas fa-calendar-check"></i> Agenda</a></li>
                    <li><a href="/admin/threat-intel"><i class="fas fa-shield-virus" style="color:#ef4444;"></i> Threat Intel</a></li>
                    <li><a href="/status"><i class="fas fa-signal"></i> System Status</a></li>
                </ul>
            </div>

                        <div class="sidebar-section">
                <h4 style="color: #7D00FF;">Command & Control</h4>
                <ul class="sidebar-nav">
                                <li><a href="/autonomy-ops"><i class="fas fa-wave-square"></i> Autonomy Ops</a></li>
                    <li><a href="/command-center"><i class="fas fa-satellite"></i> Command Center</a></li>
                    <li><a href="/intelligence-director"><i class="fas fa-brain"></i> Intelligence Director</a></li>
                    <li><a href="/mission-control"><i class="fas fa-rocket"></i> Mission Control</a></li>
                    <li><a href="/agentos-dashboard"><i class="fas fa-microchip"></i> Alfred OS</a></li>
                    <li><a href="/pulse"><i class="fas fa-wave-square"></i> Pulse Monitor</a></li>
                    <li><a href="/admin/alfred-sovereignty"><i class="fas fa-crown"></i> Alfred Sovereignty</a></li>
                    <li><a href="/veil/operations-hub.php"><i class="fas fa-server" style="color:#00f5ff"></i> Operations Hub</a></li>
                    <li><a href="/commander-defcon"><i class="fas fa-shield-virus" style="color:#ef4444;"></i> DEFCON Status</a></li>
                    <li><a href="/commander-emergency"><i class="fas fa-triangle-exclamation" style="color:#ef4444;"></i> Emergency Protocol</a></li>
                </ul>
            </div>

            <div class="sidebar-section">
                <h4 style="color: #f5c542;">Commander Docs</h4>
                <ul class="sidebar-nav">
                    <li><a href="/docs/commander-briefing" style="color:#f5c542;"><i class="fas fa-clipboard-list" style="color:#f5c542;"></i> Briefing</a></li>
                    <li><a href="/docs/letter-to-future-me" style="color:#f5c542;"><i class="fas fa-scroll" style="color:#f5c542;"></i> My Letter</a></li>
                    <li><a href="/docs/commanders-daily-brief" style="color:#f5c542;"><i class="fas fa-sun" style="color:#f5c542;"></i> Daily Brief</a></li>
                    <li><a href="/docs/commander-manual"><i class="fas fa-book"></i> Commander Manual</a></li>
                    <li><a href="/docs/commander-blueprint"><i class="fas fa-drafting-compass"></i> Blueprint</a></li>
                    <li><a href="/docs/ecosystem-principles"><i class="fas fa-seedling"></i> Ecosystem Principles</a></li>
                    <li><a href="/commanders-chronicle"><i class="fas fa-feather-pointed"></i> Chronicle</a></li>
                </ul>
            </div>

            <div class="sidebar-section">
                <h4 style="color: #00D4FF;">Infrastructure</h4>
                <ul class="sidebar-nav">
                    <li><a href="/docs/ovh-intelligence" style="color:#00D4FF;"><i class="fas fa-server" style="color:#00D4FF;"></i> OVH Intelligence</a></li>
                    <li><a href="/docs/infra-capabilities"><i class="fas fa-network-wired"></i> Infra Capabilities</a></li>
                    <li><a href="/docs/commander-encryption-ops"><i class="fas fa-key"></i> Encryption Ops</a></li>
                    <li><a href="/commander-vault-credentials"><i class="fas fa-vault"></i> Vault Credentials</a></li>
                    <li><a href="/commander-vault-unlock"><i class="fas fa-lock-open"></i> Vault Unlock</a></li>
                    <li><a href="/commander-passwords"><i class="fas fa-user-shield"></i> Password Manager</a></li>
                    <li><a href="/commander-terminal"><i class="fas fa-terminal"></i> Server Terminal</a></li>
                    <li><a href="/docs/reseller-strategy" style="color:#f59e0b;"><i class="fas fa-chess-king" style="color:#f59e0b;"></i> Reseller Strategy</a></li>
                    <li><a href="/docs/world-firsts" style="color:#fbbf24;"><i class="fas fa-trophy" style="color:#fbbf24;"></i> World Firsts</a></li>
                    <li><a href="/docs/social-strategy" style="color:#ff6b9d;"><i class="fas fa-bullhorn" style="color:#ff6b9d;"></i> Social Strategy</a>
                    <a href="/docs/classification-audit" class="sidebar-link"><i class="fas fa-file-shield" style="color: #ef4444;"></i> Classification Audit</a>
                    <a href="/docs/video2-playbook" class="sidebar-link"><i class="fas fa-video" style="color: #ec4899;"></i> Video #2 Playbook</a></li>
                </ul>
            </div>

            <div class="sidebar-section">
                <h4 style="color: #c084fc;">Alfred Systems</h4>
                <ul class="sidebar-nav">
                    <li><a href="/alfred-evolution"><i class="fas fa-dna" style="color:#c084fc;"></i> Alfred Evolution</a></li>
                    <li><a href="/alfred-os-dashboard"><i class="fas fa-gauge-high"></i> Alfred OS Dashboard</a></li>
                    <li><a href="/alfred-calls"><i class="fas fa-phone"></i> Alfred Calls</a></li>
                    <li><a href="/alfred-browser"><i class="fas fa-globe"></i> Alfred Browser</a></li>
                    <li><a href="/commander-missions"><i class="fas fa-bullseye"></i> Missions</a></li>
                    <li><a href="/commander-memory"><i class="fas fa-database"></i> Memory Bank</a></li>
                    <li><a href="/commander-organizer"><i class="fas fa-calendar-days"></i> Organizer</a></li>
                </ul>
            </div>

            <div class="sidebar-section">
                <h4 style="color: #ef4444;">Veil Protocol</h4>
                <ul class="sidebar-nav">
                    <li><a href="/veil/"><i class="fas fa-eye-slash"></i> Veil Dashboard</a></li>
                    <li><a href="/veil/black-vault"><i class="fas fa-vault"></i> Black Vault</a></li>
                    <li><a href="/veil/command-center"><i class="fas fa-shield-virus"></i> Veil Command</a></li>
                    <li><a href="/veil/fleet-tracker"><i class="fas fa-location-crosshairs"></i> Fleet Tracker</a></li>
                    <li><a href="/veil/revenue-agents"><i class="fas fa-sack-dollar"></i> Revenue Agents</a></li>
                    <li><a href="/veil/departments"><i class="fas fa-city"></i> Departments</a></li>
                    <li><a href="/veil/integrity-report"><i class="fas fa-clipboard-check"></i> Integrity Report</a></li>
                    <li><a href="/veil/reports"><i class="fas fa-file-shield"></i> Veil Reports</a></li>
                    <li><a href="/veil/vault"><i class="fas fa-lock"></i> Vault</a></li>
                    <li><a href="/veil/world-events"><i class="fas fa-globe-americas"></i> World Events</a></li>
                </ul>
            </div>
            <?php endif; ?>

            <div class="sidebar-section">
                <h4>Quick Actions</h4>
                <ul class="sidebar-nav">
                    <li><a href="/#domains"><i class="fas fa-search"></i> Register Domain</a></li>
                    <li><a href="/store"><i class="fas fa-cart-plus"></i> Order Hosting</a></li>
                    <li><a href="/submit-ticket"><i class="fas fa-plus"></i> Open Ticket</a></li>
                </ul>
            </div>
            
            <div class="sidebar-section">
                <h4>Account</h4>
                <ul class="sidebar-nav">
                    <li><a href="#profile"><i class="fas fa-user"></i> My Profile</a></li>
                    <li><a href="#security"><i class="fas fa-lock"></i> Security</a></li>
                    <li><a href="#emails"><i class="fas fa-envelope"></i> Email History</a></li>
                    <li><a href="/logout.php"><i class="fas fa-sign-out-alt"></i> Sign Out</a></li>
                </ul>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <div>
                    <h1>Welcome back, <?php echo htmlspecialchars(explode(' ', $clientName)[0]); ?>!</h1>
                    <p style="color: var(--text-muted);">Here's what's happening with your account</p>
                </div>
                <div class="user-info" style="position:relative;">
                    <div>
                        <div style="font-weight: 600;text-align:right;"><?php echo htmlspecialchars($clientName); ?></div>
                        <div style="font-size: 0.85rem; color: var(--text-muted);text-align:right;"><?php echo htmlspecialchars($clientEmail); ?></div>
                    </div>
                    <button id="userAvatarBtn" type="button" class="user-avatar" aria-haspopup="true" aria-expanded="false" aria-label="Account menu" style="border:none;cursor:pointer;color:#fff;font-size:.95rem;">
                        <?php echo strtoupper(substr($clientName, 0, 1) . substr(strstr($clientName, ' '), 1, 1)); ?>
                    </button>
                    <div id="userAvatarMenu" role="menu" style="display:none;position:absolute;top:54px;right:0;min-width:240px;background:#15151f;border:1px solid #2a2a3a;border-radius:12px;box-shadow:0 12px 40px rgba(0,0,0,.5);padding:8px;z-index:9999;">
                        <div style="padding:10px 12px 8px;border-bottom:1px solid #2a2a3a;margin-bottom:6px;">
                            <div style="font-weight:600;font-size:.9rem;"><?php echo htmlspecialchars($clientName); ?></div>
                            <div style="font-size:.78rem;color:#8b8fa3;"><?php echo htmlspecialchars($clientEmail); ?></div>
                        </div>
                        <a href="/dashboard.php" role="menuitem" class="uam-item" style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;text-decoration:none;color:inherit;font-size:.9rem;"><i class="fas fa-gauge-high" style="width:16px;color:#06b6d4;"></i>Dashboard</a>
                        <a href="/pay/account/" role="menuitem" class="uam-item" style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;text-decoration:none;color:inherit;font-size:.9rem;"><i class="fas fa-user" style="width:16px;color:#a78bfa;"></i>My Account</a>
                        <a href="/pay/account/dedicated-servers.php" role="menuitem" class="uam-item" style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;text-decoration:none;color:inherit;font-size:.9rem;"><i class="fas fa-server" style="width:16px;color:#fbbf24;"></i>My Servers</a>
                        <a href="/pay/invoices.php" role="menuitem" class="uam-item" style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;text-decoration:none;color:inherit;font-size:.9rem;"><i class="fas fa-file-invoice-dollar" style="width:16px;color:#34d399;"></i>Invoices</a>
                        <a href="/security-unlock.php" role="menuitem" class="uam-item" style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;text-decoration:none;color:inherit;font-size:.9rem;"><i class="fas fa-lock" style="width:16px;color:#f59e0b;"></i>Security Vault</a>
                        <a href="/admin/threat-intel" role="menuitem" class="uam-item" style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;text-decoration:none;color:inherit;font-size:.9rem;"><i class="fas fa-shield-virus" style="width:16px;color:#ef4444;"></i>Threat Intel</a>
                        <div style="height:1px;background:#2a2a3a;margin:6px 4px;"></div>
                        <a href="/logout.php" role="menuitem" class="uam-item" style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;text-decoration:none;color:#ff6b6b;font-size:.9rem;font-weight:600;"><i class="fas fa-right-from-bracket" style="width:16px;"></i>Sign Out</a>
                    </div>
                    <style>.uam-item:hover{background:rgba(255,255,255,.06);}</style>
                    <script>
                    (function(){
                        var btn=document.getElementById('userAvatarBtn');
                        var menu=document.getElementById('userAvatarMenu');
                        if(!btn||!menu)return;
                        function close(){menu.style.display='none';btn.setAttribute('aria-expanded','false');}
                        function open(){menu.style.display='block';btn.setAttribute('aria-expanded','true');}
                        btn.addEventListener('click',function(e){e.stopPropagation();menu.style.display==='block'?close():open();});
                        document.addEventListener('click',function(e){if(!menu.contains(e.target)&&e.target!==btn)close();});
                        document.addEventListener('keydown',function(e){if(e.key==='Escape')close();});
                    })();
                    </script>
                </div>
            </header>

            <?php if ($isAdmin): ?>
            <!-- Memory Beacon — only visible to Commander (client_id 33) -->
            <div style="background:rgba(245,197,66,.06);border:2px solid #f5c542;border-radius:12px;padding:16px 20px;margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
                <div>
                    <span style="font-size:1.1rem;">📋</span>
                    <strong style="color:#f5c542;margin-left:6px;">Danny</strong>
                    <span style="color:#8b8fa3;margin-left:8px;font-size:.85rem;">If you're unsure what this is —</span>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <a href="/docs/commander-briefing.php" style="background:#f5c542;color:#0a0a14;padding:6px 14px;border-radius:6px;text-decoration:none;font-weight:700;font-size:.82rem;">📋 Briefing</a>
                    <a href="/docs/letter-to-future-me.php" style="background:rgba(245,197,66,.15);color:#f5c542;padding:6px 14px;border-radius:6px;text-decoration:none;font-weight:600;font-size:.82rem;border:1px solid rgba(245,197,66,.3);">📜 Your Letter</a>
                    <a href="/projects.php" style="background:rgba(255,215,0,.15);color:#ffd700;padding:6px 14px;border-radius:6px;text-decoration:none;font-weight:600;font-size:.82rem;border:1px solid rgba(255,215,0,.3);">🧭 Find Everything</a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Alfred Welcome Banner -->
            <div class="welcome-banner">
                <div class="welcome-text">
                    <h1>Welcome back, <?php echo htmlspecialchars($clientName); ?></h1>
                    <p>Here's what's happening with your Alfred AI platform</p>
                </div>
                <div class="welcome-actions">
                    <a href="/conversations" class="btn btn-primary"><i class="fas fa-robot"></i> Chat with Alfred</a>
                    <a href="/agent-templates" class="btn-secondary"><i class="fas fa-rocket"></i> Deploy Agent</a>
                </div>
            </div>
            
            <!-- Stats -->
            <section class="stats-grid" id="overview" style="grid-template-columns: repeat(5, 1fr);">
                <div class="stat-card">
                    <div class="icon blue"><i class="fas fa-server"></i></div>
                    <h3 id="statServices">-</h3>
                    <p>Active Services</p>
                </div>
                <div class="stat-card">
                    <div class="icon green"><i class="fas fa-globe"></i></div>
                    <h3 id="statDomains">-</h3>
                    <p>Active Domains</p>
                </div>
                <div class="stat-card">
                    <div class="icon orange"><i class="fas fa-file-invoice"></i></div>
                    <h3 id="statInvoices">-</h3>
                    <p>Unpaid Invoices</p>
                </div>
                <div class="stat-card">
                    <div class="icon" style="background: rgba(239,68,68,0.15); color: var(--danger);"><i class="fas fa-comments"></i></div>
                    <h3 id="statTickets">-</h3>
                    <p>Open Tickets</p>
                </div>
                <div class="stat-card">
                    <div class="icon purple"><i class="fas fa-dollar-sign"></i></div>
                    <h3 id="statDue">$0</h3>
                    <p>Total Due</p>
                </div>
            </section>

            <?php if ($isAdmin && $autonomySummary): ?>
            <section style="margin:1.25rem 0 1.5rem; background:linear-gradient(135deg, rgba(125,0,255,0.12), rgba(0,212,255,0.08)); border:1px solid rgba(125,0,255,0.22); border-radius:18px; padding:1.25rem; box-shadow:0 18px 48px rgba(0,0,0,0.22);">
                <div style="display:flex; justify-content:space-between; gap:1rem; align-items:flex-start; flex-wrap:wrap; margin-bottom:1rem;">
                    <div>
                        <div style="font-size:.78rem; letter-spacing:.08em; text-transform:uppercase; color:#a78bfa; margin-bottom:.4rem;">Commander Snapshot</div>
                        <h3 style="margin:0; font-size:1.2rem;">Autonomy status at a glance</h3>
                        <p style="margin:.45rem 0 0; color:var(--text-muted); max-width:720px;">This pulls the same artifact set as Autonomy Ops, so the dashboard view and the internal control page stay in sync.</p>
                    </div>
                    <div style="display:flex; gap:.6rem; flex-wrap:wrap;">
                        <a href="/autonomy-ops.php" class="btn btn-primary"><i class="fas fa-wave-square"></i> Open Ops</a>
                        <a href="/whats-new.php" class="btn-secondary"><i class="fas fa-bullhorn"></i> Public Updates</a>
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(170px, 1fr)); gap:.85rem;">
                    <div style="background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:14px; padding:.95rem;">
                        <div style="font-size:.75rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:.08em;">Integrity Issues</div>
                        <div style="font-size:1.6rem; font-weight:700; margin-top:.35rem;"><?php echo (int)($autonomySummary['latest_integrity']['issue_count'] ?? 0); ?></div>
                    </div>
                    <div style="background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:14px; padding:.95rem;">
                        <div style="font-size:.75rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:.08em;">Refresh Proposals</div>
                        <div style="font-size:1.6rem; font-weight:700; margin-top:.35rem;"><?php echo count($autonomySummary['refresh_items'] ?? []); ?></div>
                    </div>
                    <div style="background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:14px; padding:.95rem;">
                        <div style="font-size:.75rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:.08em;">Upgrade Proposals</div>
                        <div style="font-size:1.6rem; font-weight:700; margin-top:.35rem;"><?php echo count($autonomySummary['upgrade_items'] ?? []); ?></div>
                    </div>
                    <div style="background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); border-radius:14px; padding:.95rem;">
                        <div style="font-size:.75rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:.08em;">Public Updates</div>
                        <div style="font-size:1.6rem; font-weight:700; margin-top:.35rem;"><?php echo (int)($autonomySummary['public_update_count'] ?? 0); ?></div>
                    </div>
                </div>
                <?php $latestAutonomyEvent = $autonomySummary['changelog_entries'][0] ?? null; ?>
                <?php if ($latestAutonomyEvent): ?>
                <div style="margin-top:.9rem; padding:.85rem 1rem; border-radius:14px; background:rgba(0,0,0,0.18); border:1px solid rgba(255,255,255,0.06); color:var(--text-muted);">
                    <strong style="color:var(--text);">Latest event:</strong>
                    <?php echo htmlspecialchars((string)($latestAutonomyEvent['summary'] ?? '')); ?>
                    <span style="opacity:.8;"> · <?php echo htmlspecialchars((string)($latestAutonomyEvent['timestamp'] ?? '')); ?></span>
                </div>
                <?php endif; ?>
            </section>
            <?php endif; ?>

            <?php if (ALFRED_LOCAL_SOVEREIGN): ?>
            <!-- ═══ ALFRED-OPUS HERO (Survival Edition) ═══ -->
            <section class="content-card" id="alfred-opus-hero" style="background:linear-gradient(135deg,#1a0033 0%,#2d1b69 100%);border:1px solid #6c5ce7;margin-bottom:1.5rem;">
                <div class="content-card-body" style="padding:2rem;">
                    <div style="display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap;">
                        <div style="font-size:3.5rem;color:#a29bfe;"><i class="fas fa-brain"></i></div>
                        <div style="flex:1;min-width:260px;">
                            <h2 style="color:#fff;margin:0 0 .35rem 0;font-size:1.6rem;">Alfred-Opus <span style="color:#a29bfe;font-size:1rem;font-weight:400;">· Local · Sovereign · Yours Forever</span></h2>
                            <div style="color:#dcd6f7;font-size:1rem;">0 API calls. 0 dollars. Infinite questions. Runs entirely on your machine.</div>
                            <div style="color:#9d8bd9;font-size:.85rem;margin-top:.4rem;">Three tiers baked into the ISO: Haiku (5GB) · Sonnet (9GB) · Opus (19GB) · Opus-IQ3 (12GB)</div>
                        </div>
                        <div style="display:flex;flex-direction:column;gap:.4rem;">
                            <span style="background:rgba(16,185,129,.15);color:#10b981;padding:.4rem .8rem;border-radius:6px;font-size:.85rem;"><i class="fas fa-shield-alt"></i> Zero outbound</span>
                            <span style="background:rgba(108,92,231,.15);color:#a29bfe;padding:.4rem .8rem;border-radius:6px;font-size:.85rem;"><i class="fas fa-network-wired"></i> 127.0.0.1:4000</span>
                            <span style="background:rgba(245,158,11,.15);color:#f59e0b;padding:.4rem .8rem;border-radius:6px;font-size:.85rem;"><i class="fas fa-fire"></i> Survival Edition</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ═══ BLUEPRINTS ═══ -->
            <section class="content-card" id="alfred-blueprints" style="margin-bottom:1.5rem;">
                <div class="content-card-header">
                    <h2><i class="fas fa-drafting-compass"></i> Commander Blueprints</h2>
                    <span style="color:#888;font-size:.85rem;">/home/root/law/blueprints/</span>
                </div>
                <div class="content-card-body">
                    <?php $bps = $alfredStats['blueprints'] ?? []; if (!$bps): ?>
                        <p style="color:#888;">No blueprints yet.</p>
                    <?php else: ?>
                        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem;">
                        <?php foreach ($bps as $bp): ?>
                            <details style="background:rgba(108,92,231,0.08);border:1px solid rgba(108,92,231,0.3);border-radius:8px;padding:.75rem 1rem;">
                                <summary style="cursor:pointer;color:#a29bfe;font-weight:600;"><i class="fas fa-file-alt"></i> <?php echo htmlspecialchars($bp['file']); ?></summary>
                                <div style="margin-top:.5rem;font-size:.8rem;color:#888;"><?php echo number_format($bp['size']); ?> bytes · <?php echo date('Y-m-d H:i', $bp['mtime']); ?></div>
                                <pre style="margin-top:.75rem;background:#0a0a0a;color:#dcd6f7;padding:.75rem;border-radius:6px;max-height:400px;overflow:auto;font-size:.78rem;white-space:pre-wrap;"><?php echo htmlspecialchars(file_get_contents($bp['path'])); ?></pre>
                            </details>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- Alfred AI Stats -->
            <h3 class="dash-section-title"><i class="fas fa-brain"></i> Alfred AI Overview</h3>
            <div class="alfred-stats-row">
                <div class="alfred-stat-card">
                    <div class="stat-icon" style="background: rgba(108,92,231,0.15); color: #6c5ce7;"><i class="fas fa-robot"></i></div>
                    <div class="stat-number"><?php echo number_format($alfredStats['active_agents']); ?></div>
                    <div class="stat-label">Active Agents</div>
                    <?php $t = alfredTrend($alfredStats['active_agents'], 0); ?>
                    <span class="stat-trend <?php echo $t[0]; ?>">
                        <?php if($t[0]==='up') echo '<i class="fas fa-arrow-up"></i>'; elseif($t[0]==='down') echo '<i class="fas fa-arrow-down"></i>'; else echo '<i class="fas fa-minus"></i>'; ?>
                        <?php echo $t[1]; ?>%
                    </span>
                </div>
                <div class="alfred-stat-card">
                    <div class="stat-icon" style="background: rgba(0,168,255,0.15); color: #00A8FF;"><i class="fas fa-layer-group"></i></div>
                    <div class="stat-number"><?php echo number_format($alfredStats['active_fleets']); ?></div>
                    <div class="stat-label">Active Fleets</div>
                    <span class="stat-trend flat"><i class="fas fa-minus"></i> 0%</span>
                </div>
                <div class="alfred-stat-card">
                    <div class="stat-icon" style="background: rgba(16,185,129,0.15); color: #10b981;"><i class="fas fa-comments"></i></div>
                    <div class="stat-number"><?php echo number_format($alfredStats['conversations_today']); ?></div>
                    <div class="stat-label">Conversations Today</div>
                    <?php $t = alfredTrend($alfredStats['conversations_today'], $alfredStats['conversations_yesterday']); ?>
                    <span class="stat-trend <?php echo $t[0]; ?>">
                        <?php if($t[0]==='up') echo '<i class="fas fa-arrow-up"></i>'; elseif($t[0]==='down') echo '<i class="fas fa-arrow-down"></i>'; else echo '<i class="fas fa-minus"></i>'; ?>
                        <?php echo $t[1]; ?>% vs yesterday
                    </span>
                </div>
                <div class="alfred-stat-card">
                    <div class="stat-icon" style="background: rgba(245,158,11,0.15); color: #f59e0b;"><i class="fas fa-code"></i></div>
                    <div class="stat-number"><?php echo number_format($alfredStats['api_calls_today']); ?></div>
                    <div class="stat-label">API Calls Today</div>
                    <?php $t = alfredTrend($alfredStats['api_calls_today'], $alfredStats['api_calls_yesterday']); ?>
                    <span class="stat-trend <?php echo $t[0]; ?>">
                        <?php if($t[0]==='up') echo '<i class="fas fa-arrow-up"></i>'; elseif($t[0]==='down') echo '<i class="fas fa-arrow-down"></i>'; else echo '<i class="fas fa-minus"></i>'; ?>
                        <?php echo $t[1]; ?>% vs yesterday
                    </span>
                </div>
                <div class="alfred-stat-card">
                    <div class="stat-icon" style="background: rgba(125,0,255,0.15); color: #7D00FF;"><i class="fas fa-crown"></i></div>
                    <div class="stat-number" style="font-size: 1.5rem;"><?php echo htmlspecialchars($alfredStats['current_plan']); ?></div>
                    <div class="stat-label">Current Plan</div>
                    <?php if (ALFRED_LOCAL_SOVEREIGN): ?><span style="color:#10b981;font-size:0.8rem;"><i class="fas fa-shield-alt"></i> Sovereign · Yours Forever</span><?php else: ?><a href="/pricing" style="color: var(--cyan); font-size: 0.8rem; text-decoration: none;">Upgrade &rarr;</a><?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <h3 class="dash-section-title"><i class="fas fa-bolt"></i> Quick Actions</h3>
            <div class="quick-actions-grid">

                <a href="https://alfredlinux.com/forge/" target="_blank" rel="noopener" class="action-card" id="qa-corner-forge">
                    <div class="action-icon" style="background: rgba(239,68,68,0.15); color: #ef4444;"><i class="fas fa-hammer"></i></div>
                    <h3>Alfred Forge</h3>
                    <p>Self-hosted Git — your sovereign code platform</p>
                </a>
                <a href="https://gohostme.com/" target="_blank" rel="noopener" class="action-card" id="qa-corner-gohostme">
                    <div class="action-icon" style="background: rgba(59,130,246,0.15); color: #3b82f6;"><i class="fas fa-cloud"></i></div>
                    <h3>GoHostMe</h3>
                    <p>Sovereign hosting — websites, apps, infrastructure</p>
                </a>
                <a href="https://gocodeme.com/" target="_blank" rel="noopener" class="action-card" id="qa-corner-gocodeme">
                    <div class="action-icon" style="background: rgba(168,85,247,0.15); color: #a855f7;"><i class="fas fa-code"></i></div>
                    <h3>GoCodeMe</h3>
                    <p>Code platform — build, ship, deploy</p>
                </a>

                <a href="/conversations" class="action-card">
                    <div class="action-icon" style="background: rgba(108,92,231,0.15); color: #6c5ce7;"><i class="fas fa-comments"></i></div>
                    <h3>New Conversation</h3>
                    <p>Start a new chat with Alfred AI assistant</p>
                </a>
                <a href="/agent-templates" class="action-card">
                    <div class="action-icon" style="background: rgba(0,168,255,0.15); color: #00A8FF;"><i class="fas fa-rocket"></i></div>
                    <h3>Deploy Agent</h3>
                    <p>Launch a new AI agent from templates</p>
                </a>
                <a href="/analytics" class="action-card">
                    <div class="action-icon" style="background: rgba(16,185,129,0.15); color: #10b981;"><i class="fas fa-chart-line"></i></div>
                    <h3>View Analytics</h3>
                    <p>Track performance and usage metrics</p>
                </a>
                <a href="/webhooks" class="action-card">
                    <div class="action-icon" style="background: rgba(245,158,11,0.15); color: #f59e0b;"><i class="fas fa-plug"></i></div>
                    <h3>Manage Webhooks</h3>
                    <p>Configure event notifications and integrations</p>
                </a>
                <a href="/kingdom-vault/" class="action-card">
                    <div class="action-icon" style="background: rgba(212,175,55,0.15); color: #d4af37;"><i class="fas fa-vault"></i></div>
                    <h3>Kingdom Vault</h3>
                    <p>Sovereign file storage — your private vault</p>
                </a>
                <a href="/developer-portal" class="action-card">
                    <div class="action-icon" style="background: rgba(239,68,68,0.15); color: #ef4444;"><i class="fas fa-key"></i></div>
                    <h3>API Keys</h3>
                    <p>Manage your API credentials and tokens</p>
                </a>
                <a href="/marketplace" class="action-card">
                    <div class="action-icon" style="background: rgba(125,0,255,0.15); color: #7D00FF;"><i class="fas fa-store"></i></div>
                    <h3>Browse Marketplace</h3>
                    <p>Discover agents, tools, and extensions</p>
                </a>
                <a href="/health-research" class="action-card">
                    <div class="action-icon" style="background: rgba(0,230,118,0.15); color: #00e676;"><i class="fas fa-dna"></i></div>
                    <h3>Health Research</h3>
                    <p>59K agents researching genetics, longevity, and more</p>
                </a>
                <a href="/universe" class="action-card">
                    <div class="action-icon" style="background: rgba(0,206,201,0.15); color: #00cec9;"><i class="fas fa-atom"></i></div>
                    <h3>Explore Universe</h3>
                    <p>The complete GoSiteMe super-app ecosystem</p>
                </a>
                <a href="/api/sso-bridge.php?target=metadome&amp;redirect=%2Fpassport.php" class="action-card">
                    <div class="action-icon" style="background: rgba(52,211,153,0.15); color: #34d399;"><i class="fas fa-globe"></i></div>
                    <h3>MetaDome</h3>
                    <p>Passport office &amp; civilization — single sign-on</p>
                </a>
                <a href="/api/sso-bridge.php?target=alfred&amp;redirect=%2Fmember-lounge" class="action-card">
                    <div class="action-icon" style="background: rgba(99,102,241,0.15); color: #818cf8;"><i class="fab fa-linux"></i></div>
                    <h3>Alfred Linux</h3>
                    <p>AI-native OS site — member lounge &amp; downloads</p>
                </a>
            </div>

            <!-- Site Management Toolbox -->
            <h3 class="dash-section-title" style="margin-top: 2rem;"><i class="fas fa-toolbox"></i> Site Management</h3>
            <div class="quick-actions-grid" style="grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px;">
                <a href="/pay/account/dns.php" class="action-card" style="padding: 14px;">
                    <div class="action-icon" style="background: rgba(0,168,255,0.15); color: #00A8FF; width: 36px; height: 36px; font-size: 15px;"><i class="fas fa-sitemap"></i></div>
                    <h3 style="font-size: .88rem;">DNS</h3>
                    <p>Manage DNS records</p>
                </a>
                <a href="/pay/account/ssl.php" class="action-card" style="padding: 14px;">
                    <div class="action-icon" style="background: rgba(16,185,129,0.15); color: #10b981; width: 36px; height: 36px; font-size: 15px;"><i class="fas fa-lock"></i></div>
                    <h3 style="font-size: .88rem;">SSL</h3>
                    <p>SSL certificates</p>
                </a>
                <a href="/pay/account/email.php" class="action-card" style="padding: 14px;">
                    <div class="action-icon" style="background: rgba(108,92,231,0.15); color: #6c5ce7; width: 36px; height: 36px; font-size: 15px;"><i class="fas fa-envelope"></i></div>
                    <h3 style="font-size: .88rem;">Email</h3>
                    <p>Email accounts</p>
                </a>
                <a href="/pay/account/files.php" class="action-card" style="padding: 14px;">
                    <div class="action-icon" style="background: rgba(245,158,11,0.15); color: #f59e0b; width: 36px; height: 36px; font-size: 15px;"><i class="fas fa-folder-open"></i></div>
                    <h3 style="font-size: .88rem;">Files</h3>
                    <p>File manager</p>
                </a>
                <a href="/pay/account/databases.php" class="action-card" style="padding: 14px;">
                    <div class="action-icon" style="background: rgba(239,68,68,0.15); color: #ef4444; width: 36px; height: 36px; font-size: 15px;"><i class="fas fa-database"></i></div>
                    <h3 style="font-size: .88rem;">Databases</h3>
                    <p>MySQL management</p>
                </a>
                <a href="/pay/account/backups.php" class="action-card" style="padding: 14px;">
                    <div class="action-icon" style="background: rgba(125,0,255,0.15); color: #7D00FF; width: 36px; height: 36px; font-size: 15px;"><i class="fas fa-cloud-arrow-down"></i></div>
                    <h3 style="font-size: .88rem;">Backups</h3>
                    <p>Backup & restore</p>
                </a>
                <a href="/pay/account/ftp.php" class="action-card" style="padding: 14px;">
                    <div class="action-icon" style="background: rgba(0,206,201,0.15); color: #00cec9; width: 36px; height: 36px; font-size: 15px;"><i class="fas fa-upload"></i></div>
                    <h3 style="font-size: .88rem;">FTP</h3>
                    <p>FTP accounts</p>
                </a>
                <a href="/pay/account/cron.php" class="action-card" style="padding: 14px;">
                    <div class="action-icon" style="background: rgba(255,107,157,0.15); color: #ff6b9d; width: 36px; height: 36px; font-size: 15px;"><i class="fas fa-clock"></i></div>
                    <h3 style="font-size: .88rem;">Cron Jobs</h3>
                    <p>Scheduled tasks</p>
                </a>
                <a href="/pay/account/subdomains.php" class="action-card" style="padding: 14px;">
                    <div class="action-icon" style="background: rgba(136,136,170,0.15); color: #8888aa; width: 36px; height: 36px; font-size: 15px;"><i class="fas fa-diagram-project"></i></div>
                    <h3 style="font-size: .88rem;">Subdomains</h3>
                    <p>Manage subdomains</p>
                </a>
                <a href="/pay/account/website-builder.php" class="action-card" style="padding: 14px;">
                    <div class="action-icon" style="background: rgba(245,197,66,0.15); color: #f5c542; width: 36px; height: 36px; font-size: 15px;"><i class="fas fa-wand-magic-sparkles"></i></div>
                    <h3 style="font-size: .88rem;">Builder</h3>
                    <p>Website builder</p>
                </a>
                <a href="/pay/account/site-doctor.php" class="action-card" style="padding: 14px;">
                    <div class="action-icon" style="background: rgba(0,230,118,0.15); color: #00e676; width: 36px; height: 36px; font-size: 15px;"><i class="fas fa-stethoscope"></i></div>
                    <h3 style="font-size: .88rem;">Site Doctor</h3>
                    <p>Health scanner</p>
                </a>
                <a href="/pay/account/apps.php" class="action-card" style="padding: 14px;">
                    <div class="action-icon" style="background: rgba(0,212,255,0.15); color: #00d4ff; width: 36px; height: 36px; font-size: 15px;"><i class="fas fa-boxes-stacked"></i></div>
                    <h3 style="font-size: .88rem;">Apps</h3>
                    <p>Install apps</p>
                </a>
            </div>

            <!-- Services -->
            <section class="content-card" id="services">
                <div class="content-card-header">
                    <h2><i class="fas fa-layer-group"></i> My Services</h2>
                    <a href="/store" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Order New Service</a>
                </div>
                <div class="content-card-body">
                    <div class="loading" id="servicesLoading"><i class="fas fa-spinner fa-spin"></i> Loading services...</div>
                    <div id="servicesContent" style="display: none;">
                        <!-- Stats strip -->
                        <div class="svc-stats-strip" id="svcStatsStrip"></div>
                        <!-- Filter bar -->
                        <div class="svc-filter-bar" id="svcFilterBar" style="display:none;"></div>
                        <!-- Card view -->
                        <div id="svcCardsView"></div>
                        <!-- Table view -->
                        <div class="svc-table-view" id="svcTableView"></div>
                    </div>
                </div>
            </section>
            
            <!-- Domains -->
            <section class="content-card" id="domains">
                <div class="content-card-header">
                    <h2><i class="fas fa-globe"></i> My Domains</h2>
                    <a href="/#domains" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Register Domain</a>
                </div>
                <div class="content-card-body">
                    <div class="loading" id="domainsLoading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
                    <div id="domainsContent" style="display: none;"></div>
                </div>
            </section>
            
            <!-- Invoices -->
            <section class="content-card" id="invoices">
                <div class="content-card-header">
                    <h2><i class="fas fa-file-invoice-dollar"></i> Recent Invoices</h2>
                </div>
                <div class="content-card-body">
                    <div class="loading" id="invoicesLoading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
                    <div id="invoicesContent" style="display: none;"></div>
                </div>
            </section>

            <!-- Support Tickets -->
            <section class="content-card" id="tickets">
                <div class="content-card-header">
                    <h2><i class="fas fa-ticket"></i> Support Tickets</h2>
                    <a href="/submit-ticket" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> New Ticket</a>
                </div>
                <div class="content-card-body">
                    <div class="loading" id="ticketsLoading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
                    <div id="ticketsContent" style="display: none;"></div>
                </div>
            </section>

            <!-- Credit & Billing -->
            <section class="content-card" id="credit">
                <div class="content-card-header">
                    <h2><i class="fas fa-wallet"></i> Credit & Balance</h2>
                    <a href="/profile#billing" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Funds</a>
                </div>
                <div class="content-card-body">
                    <div class="loading" id="creditLoading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
                    <div id="creditContent" style="display: none;"></div>
                </div>
            </section>

            <!-- Payment Methods -->
            <section class="content-card" id="payment-methods">
                <div class="content-card-header">
                    <h2><i class="fas fa-credit-card"></i> Payment Methods</h2>
                    <a href="/payment-methods" class="btn btn-primary btn-sm"><i class="fas fa-cog"></i> Manage</a>
                </div>
                <div class="content-card-body">
                    <div class="loading" id="paymentLoading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
                    <div id="paymentContent" style="display: none;"></div>
                </div>
            </section>

            <!-- Support PIN -->
            <section class="content-card" id="support-pin">
                <div class="content-card-header">
                    <h2><i class="fas fa-shield-halved"></i> Alfred Support PIN</h2>
                    <span id="pinStatusBadge" class="pin-status-badge not-set"><i class="fas fa-circle-xmark"></i> Not Set</span>
                </div>
                <div class="content-card-body">

                    <div class="alfred-callout">
                        <div class="alfred-icon">🤖</div>
                        <p>
                            <strong>Alfred</strong> is your AI support assistant on <strong>1-833-GOSITEME ext. 0</strong>.<br>
                            Set a 4-digit PIN so Alfred can instantly verify your identity when you call — no hold times, no passwords to type.
                        </p>
                    </div>

                    <div class="pin-setup">
                        <div class="pin-info">
                            <h3><i class="fas fa-phone"></i> How it works</h3>
                            <p>When you call GoSiteMe support, Alfred will ask for your email and your 4-digit PIN. Once verified, Alfred can instantly access your account, check your services, review invoices, and help you right away.</p>
                            <p>Your PIN is stored securely and is only used to verify your identity on support calls.</p>
                            <p id="pinSetDate" style="font-size:0.8rem; color: var(--text-muted);"></p>
                        </div>

                        <div class="pin-form">
                            <p style="margin-bottom: 16px; font-weight: 600;">Enter a 4-digit PIN:</p>
                            <div class="pin-inputs">
                                <input type="password" class="pin-digit" id="pin1" maxlength="1" inputmode="numeric" pattern="[0-9]">
                                <input type="password" class="pin-digit" id="pin2" maxlength="1" inputmode="numeric" pattern="[0-9]">
                                <input type="password" class="pin-digit" id="pin3" maxlength="1" inputmode="numeric" pattern="[0-9]">
                                <input type="password" class="pin-digit" id="pin4" maxlength="1" inputmode="numeric" pattern="[0-9]">
                            </div>
                            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                                <button class="btn btn-primary" onclick="savePin()">
                                    <i class="fas fa-shield-check"></i> Save PIN
                                </button>
                                <button class="btn btn-outline" id="removePinBtn" onclick="removePin()" style="display:none;">
                                    <i class="fas fa-trash"></i> Remove PIN
                                </button>
                            </div>
                            <div id="pinMessage" class="pin-message"></div>
                        </div>
                    </div>

                </div>
            </section>

            <!-- Recent Activity Feed -->
            <section class="content-card" id="activity-feed">
                <div class="content-card-header">
                    <h2><i class="fas fa-clock-rotate-left"></i> Recent Activity</h2>
                    <a href="/analytics" class="btn btn-outline btn-sm">View All</a>
                </div>
                <div class="content-card-body">
                    <?php if (empty($alfredStats['recent_activity'])): ?>
                        <div class="empty-state">
                            <i class="fas fa-clock-rotate-left"></i>
                            <p>No recent activity yet. Start a conversation with Alfred to see activity here.</p>
                            <a href="/conversations" class="btn btn-primary" style="margin-top: 16px;">Start Chatting</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($alfredStats['recent_activity'] as $act): ?>
                            <div class="activity-item">
                                <div class="activity-icon"><i class="fas <?php echo activityIcon($act['resource_type']); ?>"></i></div>
                                <div class="activity-details">
                                    <div class="activity-desc"><?php echo activityLabel($act['resource_type'], $act['description']); ?></div>
                                    <div class="activity-time"><?php echo alfredTimeAgo($act['created_at']); ?></div>
                                </div>
                                <span class="activity-badge badge badge-<?php echo ($act['status'] === 'completed' || $act['status'] === 'success') ? 'success' : (($act['status'] === 'error' || $act['status'] === 'failed') ? 'danger' : 'info'); ?>">
                                    <?php echo htmlspecialchars($act['status']); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <?php if (!ALFRED_LOCAL_SOVEREIGN): ?>
            <!-- Usage Meters -->
            <section class="content-card" id="usage-meters">
                <div class="content-card-header">
                    <h2><i class="fas fa-gauge-high"></i> Usage This Month</h2>
                    <?php
                    $showUpgrade = false;
                    foreach (['api_calls', 'voice_minutes', 'storage_mb', 'agents'] as $uk) {
                        if ($alfredStats['plan_limits'][$uk] > 0 && usagePct($alfredStats['usage'][$uk], $alfredStats['plan_limits'][$uk]) > 80) {
                            $showUpgrade = true; break;
                        }
                    }
                    if ($showUpgrade): ?>
                        <a href="/pricing" class="upgrade-nudge"><i class="fas fa-arrow-up"></i> Upgrade Plan</a>
                    <?php endif; ?>
                </div>
                <div class="content-card-body">
                    <div class="usage-meters">
                        <div class="usage-meter-item">
                            <div class="usage-header">
                                <span class="usage-label"><i class="fas fa-code"></i> API Calls</span>
                                <span class="usage-count"><?php echo number_format($alfredStats['usage']['api_calls']); ?> / <?php echo number_format($alfredStats['plan_limits']['api_calls']); ?></span>
                            </div>
                            <div class="usage-bar">
                                <div class="usage-bar-fill" style="width: <?php echo usagePct($alfredStats['usage']['api_calls'], $alfredStats['plan_limits']['api_calls']); ?>%; background: <?php echo usageBarColor($alfredStats['usage']['api_calls'], $alfredStats['plan_limits']['api_calls']); ?>;"></div>
                            </div>
                        </div>
                        <div class="usage-meter-item">
                            <div class="usage-header">
                                <span class="usage-label"><i class="fas fa-microphone"></i> Voice Minutes</span>
                                <span class="usage-count"><?php echo number_format($alfredStats['usage']['voice_minutes']); ?> / <?php echo number_format($alfredStats['plan_limits']['voice_minutes']); ?></span>
                            </div>
                            <div class="usage-bar">
                                <div class="usage-bar-fill" style="width: <?php echo usagePct($alfredStats['usage']['voice_minutes'], $alfredStats['plan_limits']['voice_minutes']); ?>%; background: <?php echo usageBarColor($alfredStats['usage']['voice_minutes'], $alfredStats['plan_limits']['voice_minutes']); ?>;"></div>
                            </div>
                        </div>
                        <div class="usage-meter-item">
                            <div class="usage-header">
                                <span class="usage-label"><i class="fas fa-hard-drive"></i> Storage</span>
                                <span class="usage-count"><?php echo number_format($alfredStats['usage']['storage_mb']); ?> MB / <?php echo number_format($alfredStats['plan_limits']['storage_mb']); ?> MB</span>
                            </div>
                            <div class="usage-bar">
                                <div class="usage-bar-fill" style="width: <?php echo usagePct($alfredStats['usage']['storage_mb'], $alfredStats['plan_limits']['storage_mb']); ?>%; background: <?php echo usageBarColor($alfredStats['usage']['storage_mb'], $alfredStats['plan_limits']['storage_mb']); ?>;"></div>
                            </div>
                        </div>
                        <div class="usage-meter-item">
                            <div class="usage-header">
                                <span class="usage-label"><i class="fas fa-robot"></i> Agents</span>
                                <span class="usage-count"><?php echo number_format($alfredStats['usage']['agents']); ?> / <?php echo number_format($alfredStats['plan_limits']['agents']); ?></span>
                            </div>
                            <div class="usage-bar">
                                <div class="usage-bar-fill" style="width: <?php echo usagePct($alfredStats['usage']['agents'], $alfredStats['plan_limits']['agents']); ?>%; background: <?php echo usageBarColor($alfredStats['usage']['agents'], $alfredStats['plan_limits']['agents']); ?>;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <?php endif; ?>
            <!-- Email History -->
            <section class="content-card" id="emails">
                <div class="content-card-header">
                    <h2><i class="fas fa-envelope"></i> Email History</h2>
                </div>
                <div class="content-card-body">
                    <div class="loading" id="emailsLoading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
                    <div id="emailsContent" style="display: none;"></div>
                </div>
            </section>

            <!-- Quotes -->
            <section class="content-card" id="quotes">
                <div class="content-card-header">
                    <h2><i class="fas fa-file-contract"></i> Quotes</h2>
                </div>
                <div class="content-card-body">
                    <div class="loading" id="quotesLoading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
                    <div id="quotesContent" style="display: none;"></div>
                </div>
            </section>

            <!-- Profile -->
            <section class="content-card" id="profile">
                <div class="content-card-header">
                    <h2><i class="fas fa-user"></i> My Profile</h2>
                </div>
                <div class="content-card-body">
                    <div class="loading" id="profileLoading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
                    <div id="profileContent" style="display: none;"></div>
                </div>
            </section>

            <!-- Security -->
            <section class="content-card" id="security">
                <div class="content-card-header">
                    <h2><i class="fas fa-lock"></i> Security</h2>
                </div>
                <div class="content-card-body">
                    <div class="security-grid">
                        <a href="/security" class="security-link-card">
                            <div class="sec-icon" style="background: rgba(239,68,68,0.15); color: #ef4444;">
                                <i class="fas fa-key"></i>
                            </div>
                            <div>
                                <h4>Change Password</h4>
                                <p>Update your account password</p>
                            </div>
                        </a>
                        <a href="/security" class="security-link-card">
                            <div class="sec-icon" style="background: rgba(16,185,129,0.15); color: #10b981;">
                                <i class="fas fa-shield-halved"></i>
                            </div>
                            <div>
                                <h4>Two-Factor Auth</h4>
                                <p>Enable 2FA for extra security</p>
                            </div>
                        </a>
                        <a href="/security" class="security-link-card">
                            <div class="sec-icon" style="background: rgba(0,168,255,0.15); color: #00A8FF;">
                                <i class="fas fa-users"></i>
                            </div>
                            <div>
                                <h4>User Management</h4>
                                <p>Manage authorized users and contacts</p>
                            </div>
                        </a>
                        <a href="/security" class="security-link-card">
                            <div class="sec-icon" style="background: rgba(245,158,11,0.15); color: #f59e0b;">
                                <i class="fas fa-clock-rotate-left"></i>
                            </div>
                            <div>
                                <h4>Login History</h4>
                                <p>Review recent account access</p>
                            </div>
                        </a>
                    </div>
                </div>
            </section>

            <!-- System Status -->
            <div class="system-status" id="system-status">
                <div class="status-dot"></div>
                <span class="status-text">All Systems Operational</span>
                <a href="/status" class="status-link">View Status Page &rarr;</a>
            </div>

        </main>
    </div>
    
    <script src="/assets/js/dashboard.js?v=20260321"></script>
    <script>
    // Collapsible sidebar sections
    (function() {
        const defaultCollapsed = ['Hosting & Site Tools','Site Health','Website Builders','Account & Support','Crypto & Tokens','AI & Voice Tools'];
        const saved = JSON.parse(localStorage.getItem('dash_collapsed') || '{}');
        document.querySelectorAll('.sidebar-section').forEach(sec => {
            const h4 = sec.querySelector('h4');
            if (!h4) return;
            const label = h4.textContent.trim();
            const isCollapsed = saved[label] !== undefined ? saved[label] : defaultCollapsed.includes(label);
            if (isCollapsed) sec.classList.add('collapsed');
            h4.addEventListener('click', () => {
                sec.classList.toggle('collapsed');
                const state = JSON.parse(localStorage.getItem('dash_collapsed') || '{}');
                state[label] = sec.classList.contains('collapsed');
                localStorage.setItem('dash_collapsed', JSON.stringify(state));
            });
        });
    })();
    </script>
    <!-- Alfred Chat Widget -->
    <?php $awVer = '9.6.1'; ?>
    <link rel="stylesheet" href="/assets/css/alfred-widget.min.css?v=<?php echo $awVer; ?>">
    <script>
    window.AW_AUTH_TOKEN = "<?php
        $aw_uid = $_SESSION['uid'] ?? $_SESSION['client_id'] ?? null;
        $aw_hmac_secret = getenv('ALFRED_HMAC_SECRET')
            ?: (defined('ALFRED_HMAC_SECRET') ? ALFRED_HMAC_SECRET : '')
            ?: 'root-alfred-hmac-2026';
        echo $aw_uid ? hash_hmac('sha256', session_id() . '|' . $aw_uid, $aw_hmac_secret) : '';
    ?>";
    window.AW_USERNAME = "<?php echo htmlspecialchars($_SESSION['username'] ?? $_SESSION['client_name'] ?? 'Guest', ENT_QUOTES); ?>";
    window.AW_CSRF_TOKEN = "<?php if (!isset($_SESSION['alfred_csrf'])) $_SESSION['alfred_csrf'] = bin2hex(random_bytes(32)); $_SESSION['csrf_token'] = $_SESSION['alfred_csrf']; echo $_SESSION['alfred_csrf']; ?>";
    window.AW_USER_ID = "<?php echo $_SESSION['uid'] ?? $_SESSION['client_id'] ?? ''; ?>";
    window.AW_PAGE_CONTEXT = "dashboard";
    </script>
    <script src="/assets/js/alfred-widget.min.js?v=<?php echo $awVer; ?>" defer></script>
    <div style="text-align:center;padding:12px 0;font-size:11px;opacity:0.5">
        <a href="/privacy-policy/" style="color:inherit">Privacy Policy</a> · <a href="/terms-of-service.php" style="color:inherit">Terms of Service</a>
        · <span style="font-size:10px">Cryptocurrency involves risk. Not financial advice.</span>
    </div>
</body>
</html>
