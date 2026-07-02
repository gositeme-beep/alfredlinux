<?php
/**
 * Alfred IDE — Workspace Lifecycle API
 *
 * GET  ?action=status   → workspace health: disk, memory, uptime, sessions
 * POST ?action=restart  → restart IDE service (Commander only)
 *
 * Requires valid IDE session token.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Alfred-IDE-Token');
header('Access-Control-Allow-Credentials: true');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../includes/db-config.inc.php';
require_once __DIR__ . '/../includes/alfred-ide-bearer.inc.php';

// --- Auth ---
$token = alfred_resolve_ide_bearer_token();
if (!$token) $token = $_COOKIE['alfred_ide_token'] ?? '';
if (!$token) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

$db = new PDO(
    'mysql:unix_socket=/run/mysql/mysql.sock;dbname=' . GOSITEME_DB_NAME,
    GOSITEME_DB_USER,
    GOSITEME_DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$tokenHash = hash('sha256', $token);
$user = alfred_ide_lookup_user_by_token_hash($db, $tokenHash);
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid or expired session']);
    exit;
}

$clientId = (int)($user['client_id'] ?? 0);
$isCommander = ($clientId === 33);
$action = $_GET['action'] ?? $_POST['action'] ?? 'status';

switch ($action) {
    case 'status':
        echo json_encode(getWorkspaceStatus($db, $user, $isCommander));
        break;

    case 'restart':
        if (!$isCommander) {
            http_response_code(403);
            echo json_encode(['error' => 'Only Commander can restart services']);
            exit;
        }
        echo json_encode(restartIDE());
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action: ' . htmlspecialchars($action, ENT_QUOTES, 'UTF-8')]);
}

// --- Functions ---

function getWorkspaceStatus(PDO $db, array $user, bool $isCommander): array {
    $status = [
        'workspace' => [
            'user'       => $user['display_name'] ?? $user['email'] ?? 'unknown',
            'client_id'  => (int)($user['client_id'] ?? 0),
            'role'       => $isCommander ? 'commander' : 'customer',
            'session_expires' => $user['token_expires'] ?? null,
        ],
        'server' => getServerHealth(),
        'ide'    => getIDEServiceStatus(),
        'ts'     => date('c'),
    ];

    if ($isCommander) {
        $status['pm2'] = getPM2Summary();
        $status['backup'] = getBackupStatus();
    }

    return $status;
}

function getServerHealth(): array {
    $disk = @disk_free_space('/home/gositeme');
    $diskTotal = @disk_total_space('/home/gositeme');
    $load = sys_getloadavg();

    $memTotal = 0;
    $memAvail = 0;
    $uptimeSec = 0;

    // Try /proc first, fall back to shell commands
    $memInfo = @file_get_contents('/proc/meminfo');
    if ($memInfo) {
        if (preg_match('/MemTotal:\s+(\d+)/', $memInfo, $m)) $memTotal = (int)$m[1] * 1024;
        if (preg_match('/MemAvailable:\s+(\d+)/', $memInfo, $m)) $memAvail = (int)$m[1] * 1024;
    }
    if (!$memTotal) {
        $free = @shell_exec('free -b 2>/dev/null');
        if ($free && preg_match('/Mem:\s+(\d+)\s+\d+\s+\d+\s+\d+\s+\d+\s+(\d+)/', $free, $m)) {
            $memTotal = (int)$m[1];
            $memAvail = (int)$m[2];
        }
    }

    $uptime = @file_get_contents('/proc/uptime');
    if ($uptime) {
        $uptimeSec = (int)floatval($uptime);
    } else {
        $uptimeStr = trim(@shell_exec('cat /proc/uptime 2>/dev/null') ?: '');
        if ($uptimeStr) $uptimeSec = (int)floatval($uptimeStr);
    }

    return [
        'disk_free_gb'    => $disk ? round($disk / 1073741824, 1) : null,
        'disk_total_gb'   => $diskTotal ? round($diskTotal / 1073741824, 1) : null,
        'disk_used_pct'   => ($disk && $diskTotal) ? round(100 - ($disk / $diskTotal * 100), 1) : null,
        'mem_total_gb'    => $memTotal ? round($memTotal / 1073741824, 1) : null,
        'mem_avail_gb'    => $memAvail ? round($memAvail / 1073741824, 1) : null,
        'load_1m'         => $load[0] ?? null,
        'load_5m'         => $load[1] ?? null,
        'uptime_days'     => $uptimeSec ? round($uptimeSec / 86400, 1) : null,
    ];
}

function getIDEServiceStatus(): array {
    $pm2Out = shell_exec('HOME=/home/gositeme PM2_HOME=/home/gositeme/.pm2 /usr/bin/node /var/www/gocodeme/middleware/node_modules/.bin/pm2 jlist 2>/dev/null');
    if (!$pm2Out) return ['status' => 'unknown', 'note' => 'PM2 not accessible from web context'];

    $processes = json_decode($pm2Out, true);
    if (!is_array($processes)) return ['status' => 'unknown'];

    foreach ($processes as $p) {
        if (($p['name'] ?? '') === 'alfred-ide') {
            return [
                'status'    => $p['pm2_env']['status'] ?? 'unknown',
                'uptime_ms' => $p['pm2_env']['pm_uptime'] ?? null,
                'restarts'  => $p['pm2_env']['restart_time'] ?? 0,
                'memory_mb' => isset($p['monit']['memory']) ? round($p['monit']['memory'] / 1048576, 1) : null,
                'cpu'       => $p['monit']['cpu'] ?? null,
                'pid'       => $p['pid'] ?? null,
            ];
        }
    }
    return ['status' => 'not_found'];
}

function getPM2Summary(): array {
    $pm2Out = shell_exec('HOME=/home/gositeme PM2_HOME=/home/gositeme/.pm2 /usr/bin/node /var/www/gocodeme/middleware/node_modules/.bin/pm2 jlist 2>/dev/null');
    if (!$pm2Out) return ['error' => 'Cannot read PM2'];

    $processes = json_decode($pm2Out, true);
    if (!is_array($processes)) return ['error' => 'Invalid PM2 output'];

    $online = 0; $stopped = 0; $errored = 0;
    $critical = [];
    $criticalNames = ['alfred-ws', 'alfred-mcp', 'alfred-jobs', 'alfred-ide', 'redis', 'alfred-heartbeat'];

    foreach ($processes as $p) {
        $s = $p['pm2_env']['status'] ?? 'unknown';
        if ($s === 'online') $online++;
        elseif ($s === 'stopped') $stopped++;
        else $errored++;

        if (in_array($p['name'] ?? '', $criticalNames)) {
            $critical[$p['name']] = $s;
        }
    }

    return [
        'total'    => count($processes),
        'online'   => $online,
        'stopped'  => $stopped,
        'errored'  => $errored,
        'critical' => $critical,
    ];
}

function getBackupStatus(): array {
    $logFile = '/home/gositeme/logs/mesh-backup.log';
    if (!file_exists($logFile)) return ['status' => 'no_log'];

    $log = file_get_contents($logFile);
    preg_match_all('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] ={10} MESH BACKUP COMPLETE/', $log, $m);
    $lastComplete = end($m[1]) ?: null;

    $ageHours = null;
    if ($lastComplete) {
        $ageHours = round((time() - strtotime($lastComplete)) / 3600, 1);
    }

    return [
        'last_success'  => $lastComplete,
        'age_hours'     => $ageHours,
        'healthy'       => ($ageHours !== null && $ageHours < 48),
    ];
}

function restartIDE(): array {
    $output = shell_exec('HOME=/home/gositeme PM2_HOME=/home/gositeme/.pm2 /usr/bin/node /var/www/gocodeme/middleware/node_modules/.bin/pm2 restart alfred-ide 2>&1');
    return [
        'action'  => 'restart',
        'success' => (strpos($output, 'online') !== false || strpos($output, 'restarted') !== false),
        'output'  => trim(substr($output ?? '', 0, 500)),
    ];
}
