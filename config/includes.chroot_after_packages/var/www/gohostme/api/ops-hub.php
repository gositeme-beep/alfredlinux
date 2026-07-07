<?php
/**
 * Alfred Operations Hub — API
 * ═══════════════════════════
 * Classified: Commander Eyes Only
 * 
 * Endpoints:
 *   ?action=system-vitals     — CPU, RAM, disk, uptime, load
 *   ?action=docker-status     — Container list and stats
 *   ?action=pm2-status        — PM2 process list
 *   ?action=android-screen    — Take Android screencap (returns base64 PNG)
 *   ?action=android-tap       — Tap at x,y on Android (POST: x, y)
 *   ?action=android-input     — Type text on Android (POST: text)
 *   ?action=android-key       — Send keyevent (POST: key)
 *   ?action=android-swipe     — Swipe gesture (POST: x1,y1,x2,y2,duration)
 *   ?action=adb-command       — Execute ADB shell command (POST: cmd) [RESTRICTED]
 *   ?action=crypto-balances   — Poloniex balances
 *   ?action=missions          — Active commander missions
 *   ?action=vault-status      — Vault key and credential health
 *   ?action=rotation-status   — Credential rotation countdown status
 *   ?action=services          — All port listeners
 *   ?action=vault-credentials — Decrypted credential vault (PIN protected)
 *   ?action=vault-lock        — Lock the vault (clear session unlock)
 */

header('Content-Type: application/json');
header('X-Classified: COMMANDER-EYES-ONLY');

// ADB binary path — use modern version for Android 14+ support
define('ADB_BIN', file_exists('/home/gositeme/.local/platform-tools/adb')
    ? '/home/gositeme/.local/platform-tools/adb'
    : 'adb');
define('ANDROID_CONTAINER', 'redroid');

// Commander auth (API-safe: never redirects)
require_once __DIR__ . '/../includes/commander-auth.api.inc.php';
$commanderId = getCommanderId();
if (!$commanderId) {
    http_response_code(401);
    echo json_encode([
        'error' => 'Authentication required',
        'code' => 'AUTH_REQUIRED',
        'login_url' => '/?login=1',
    ]);
    exit;
}

// Normalize expected auth variables for downstream helpers
$clientId = 33;
$authenticated = true;

// Ops Hub is already auth-gated to client_id 33 — exempt from CSRF auto-enforcement
$GLOBALS['CSRF_EXEMPT'] = true;

// === PHP-FPM Environment Fix ===
putenv("PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/home/gositeme/.local/node_modules/.bin");
putenv("HOME=/home/gositeme");
putenv("PM2_HOME=/home/gositeme/.pm2");
if (!defined('GOSITEME_API')) define('GOSITEME_API', true);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    $result = match($action) {
        'system-vitals'   => getSystemVitals(),
        'docker-status'   => getDockerStatus(),
        'pm2-status'      => getPM2Status(),
        'android-screen'  => getAndroidScreen(),
        'android-tap'     => androidTap(),
        'android-input'   => androidInput(),
        'android-key'     => androidKey(),
        'android-swipe'   => androidSwipe(),
        'adb-command'     => adbCommand(),
        'crypto-balances' => getCryptoBalances(),
        'missions'        => getMissions(),
        'vault-status'    => getVaultStatus(),
        'rotation-status' => getRotationStatus(),
        'desktop-status'  => getDesktopStatus(),
        'vault-credentials' => getVaultCredentials(),
        'vault-lock'      => lockVaultSession(),
        'update-credential' => updateVaultCredential(),
        'delete-credential' => deleteVaultCredential(),
        'change-ide-password' => changeIdePassword(),
        'services'        => getServices(),
        default           => ['error' => 'Unknown action', 'available' => [
            'system-vitals','docker-status','pm2-status','android-screen',
            'android-tap','android-input','android-key','android-swipe',
            'adb-command','crypto-balances','missions','vault-status','rotation-status','desktop-status','vault-credentials','vault-lock','update-credential','delete-credential','services'
        ]]
    };
    echo json_encode($result, JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

// ═══════════════════════════════════════════════════════════════
// SYSTEM VITALS
// ═══════════════════════════════════════════════════════════════
function getSystemVitals(): array {
    $uptime = trim(shell_exec('uptime -p 2>/dev/null') ?: 'unknown');
    $load = trim(shell_exec('cat /proc/loadavg 2>/dev/null') ?: '');
    
    // CPU usage
    $cpuInfo = shell_exec("top -bn1 | grep 'Cpu(s)' | head -1 2>/dev/null") ?: '';
    preg_match('/([\d.]+)\s*id/', $cpuInfo, $m);
    $cpuUsage = isset($m[1]) ? round(100 - (float)$m[1], 1) : 0;
    
    // Memory
    $memInfo = shell_exec('free -b 2>/dev/null') ?: '';
    preg_match('/Mem:\s+(\d+)\s+(\d+)\s+(\d+)/', $memInfo, $mm);
    $memTotal = (int)($mm[1] ?? 0);
    $memUsed = (int)($mm[2] ?? 0);
    
    // Disk
    $diskInfo = shell_exec("df -B1 / 2>/dev/null | tail -1") ?: '';
    preg_match('/(\d+)\s+(\d+)\s+(\d+)\s+(\d+)%/', $diskInfo, $dm);
    
    // Network
    $netRx = trim(shell_exec("cat /sys/class/net/eno1/statistics/rx_bytes 2>/dev/null") ?: '0');
    $netTx = trim(shell_exec("cat /sys/class/net/eno1/statistics/tx_bytes 2>/dev/null") ?: '0');
    
    // CPU cores and temp
    $cores = (int)trim(shell_exec('nproc 2>/dev/null') ?: '0');
    $temp = trim(shell_exec("cat /sys/class/thermal/thermal_zone0/temp 2>/dev/null") ?: '0');
    $tempC = round((int)$temp / 1000, 1);
    
    return [
        'status' => 'operational',
        'uptime' => $uptime,
        'load_avg' => $load,
        'cpu' => [
            'usage_percent' => $cpuUsage,
            'cores' => $cores,
            'temp_c' => $tempC,
        ],
        'memory' => [
            'total_gb' => round($memTotal / 1073741824, 1),
            'used_gb' => round($memUsed / 1073741824, 1),
            'percent' => $memTotal > 0 ? round($memUsed / $memTotal * 100, 1) : 0,
        ],
        'disk' => [
            'total_gb' => round((int)($dm[1] ?? 0) / 1073741824, 0),
            'used_gb' => round((int)($dm[2] ?? 0) / 1073741824, 0),
            'percent' => (int)($dm[4] ?? 0),
        ],
        'network' => [
            'rx_gb' => round((int)$netRx / 1073741824, 2),
            'tx_gb' => round((int)$netTx / 1073741824, 2),
        ],
        'timestamp' => date('c'),
    ];
}

// ═══════════════════════════════════════════════════════════════
// DOCKER STATUS
// ═══════════════════════════════════════════════════════════════
function getDockerStatus(): array {
    $json = shell_exec('docker ps -a --format "{{json .}}" 2>/dev/null') ?: '';
    $containers = [];
    foreach (explode("\n", trim($json)) as $line) {
        if (empty($line)) continue;
        $c = json_decode($line, true);
        if ($c) $containers[] = [
            'id' => substr($c['ID'] ?? '', 0, 12),
            'name' => $c['Names'] ?? '',
            'image' => $c['Image'] ?? '',
            'status' => $c['Status'] ?? '',
            'ports' => $c['Ports'] ?? '',
            'created' => $c['CreatedAt'] ?? '',
        ];
    }
    // Docker stats
    $stats = shell_exec('docker stats --no-stream --format "{{json .}}" 2>/dev/null') ?: '';
    $statsMap = [];
    foreach (explode("\n", trim($stats)) as $line) {
        if (empty($line)) continue;
        $s = json_decode($line, true);
        if ($s) $statsMap[$s['Name'] ?? ''] = [
            'cpu' => $s['CPUPerc'] ?? '0%',
            'mem_usage' => $s['MemUsage'] ?? '',
            'mem_percent' => $s['MemPerc'] ?? '0%',
            'net_io' => $s['NetIO'] ?? '',
        ];
    }
    foreach ($containers as &$c) {
        $c['stats'] = $statsMap[$c['name']] ?? null;
    }
    return ['containers' => $containers, 'count' => count($containers)];
}

// ═══════════════════════════════════════════════════════════════
// PM2 STATUS
// ═══════════════════════════════════════════════════════════════
function getPM2Status(): array {
    $json = shell_exec('/home/gositeme/.local/node_modules/.bin/pm2 jlist 2>/dev/null') ?: '[]';
    $processes = json_decode($json, true) ?: [];
    $result = [];
    foreach ($processes as $p) {
        $result[] = [
            'name' => $p['name'] ?? '',
            'status' => $p['pm2_env']['status'] ?? 'unknown',
            'pid' => $p['pid'] ?? 0,
            'cpu' => $p['monit']['cpu'] ?? 0,
            'memory_mb' => round(($p['monit']['memory'] ?? 0) / 1048576, 1),
            'restarts' => $p['pm2_env']['restart_time'] ?? 0,
            'uptime' => $p['pm2_env']['pm_uptime'] ?? 0,
        ];
    }
    return ['processes' => $result, 'count' => count($result)];
}

function isAndroidContainerRunning(): bool {
    static $isRunning = null;
    if ($isRunning !== null) {
        return $isRunning;
    }

    $name = trim(shell_exec("docker ps --filter " . escapeshellarg('name=^/' . ANDROID_CONTAINER . '$') . " --format '{{.Names}}' 2>/dev/null") ?: '');
    $isRunning = ($name === ANDROID_CONTAINER);
    return $isRunning;
}

function listAdbDevices(): array {
    $output = shell_exec(ADB_BIN . ' devices 2>/dev/null') ?: '';
    $devices = [];
    foreach (preg_split('/\r?\n/', trim($output)) as $line) {
        if ($line === '' || str_starts_with($line, 'List of devices attached')) {
            continue;
        }
        if (!preg_match('/^(\S+)\s+(\S+)/', trim($line), $matches)) {
            continue;
        }
        $devices[$matches[1]] = $matches[2];
    }
    return $devices;
}

function getAndroidTarget(): ?array {
    $preferred = ['127.0.0.1:5555', 'localhost:5555', 'emulator-5554'];

    $devices = listAdbDevices();
    if (!$devices) {
        shell_exec(ADB_BIN . ' connect 127.0.0.1:5555 >/dev/null 2>&1');
        $devices = listAdbDevices();
    }

    foreach ($preferred as $serial) {
        if (($devices[$serial] ?? null) === 'device') {
            return ['mode' => 'adb', 'serial' => $serial, 'label' => $serial];
        }
    }

    foreach ($devices as $serial => $state) {
        if ($state === 'device') {
            return ['mode' => 'adb', 'serial' => $serial, 'label' => $serial];
        }
    }

    if (isAndroidContainerRunning()) {
        $bootCompleted = trim(shell_exec('docker exec ' . escapeshellarg(ANDROID_CONTAINER) . ' getprop sys.boot_completed 2>/dev/null') ?: '');
        if ($bootCompleted === '1') {
            $adbdStatus = trim(shell_exec('docker exec ' . escapeshellarg(ANDROID_CONTAINER) . ' getprop init.svc.adbd 2>/dev/null') ?: '');
            if ($adbdStatus !== 'running') {
                shell_exec('docker exec ' . escapeshellarg(ANDROID_CONTAINER) . ' setprop ctl.start adbd >/dev/null 2>&1');
            }
            return ['mode' => 'docker', 'serial' => ANDROID_CONTAINER, 'label' => ANDROID_CONTAINER];
        }
    }

    return null;
}

function getAndroidSerial(): ?string {
    $target = getAndroidTarget();
    return $target && $target['mode'] === 'adb' ? $target['serial'] : null;
}

function getAndroidBootStatus(array $target): array {
    if ($target['mode'] === 'docker') {
        return [
            'boot_completed' => trim(shell_exec('docker exec ' . escapeshellarg(ANDROID_CONTAINER) . ' getprop sys.boot_completed 2>/dev/null') ?: ''),
            'bootanim' => trim(shell_exec('docker exec ' . escapeshellarg(ANDROID_CONTAINER) . ' getprop init.svc.bootanim 2>/dev/null') ?: ''),
            'adbd' => trim(shell_exec('docker exec ' . escapeshellarg(ANDROID_CONTAINER) . ' getprop init.svc.adbd 2>/dev/null') ?: ''),
        ];
    }

    return [
        'boot_completed' => trim(shell_exec(ADB_BIN . ' -s ' . escapeshellarg($target['serial']) . ' shell getprop sys.boot_completed 2>/dev/null') ?: ''),
        'bootanim' => trim(shell_exec(ADB_BIN . ' -s ' . escapeshellarg($target['serial']) . ' shell getprop init.svc.bootanim 2>/dev/null') ?: ''),
        'adbd' => 'running',
    ];
}

// ═══════════════════════════════════════════════════════════════
// ANDROID SCREEN (ADB screencap → base64 PNG)
// ═══════════════════════════════════════════════════════════════
function getAndroidScreen(): array {
    $target = getAndroidTarget();
    if (!$target) {
        return ['error' => 'No Android device connected'];
    }

    $bootStatus = getAndroidBootStatus($target);

    $tmpFile = '/tmp/android-screen-' . uniqid() . '.png';
    if ($target['mode'] === 'docker') {
        $result = shell_exec('docker exec ' . escapeshellarg(ANDROID_CONTAINER) . ' sh -lc ' . escapeshellarg('screencap -p') . ' > ' . escapeshellarg($tmpFile) . ' 2>&1');
    } else {
        $result = shell_exec(ADB_BIN . ' -s ' . escapeshellarg($target['serial']) . ' exec-out screencap -p > ' . escapeshellarg($tmpFile) . ' 2>&1');
    }
    if (!file_exists($tmpFile) || filesize($tmpFile) < 100) {
        @unlink($tmpFile);
        return [
            'error' => 'Screenshot failed — Android may be booting',
            'detail' => $result,
            'serial' => $target['label'],
            'transport' => $target['mode'],
            'boot_completed' => $bootStatus['boot_completed'],
            'bootanim' => $bootStatus['bootanim'],
            'adbd' => $bootStatus['adbd'],
        ];
    }
    $base64 = base64_encode(file_get_contents($tmpFile));
    $size = filesize($tmpFile);
    @unlink($tmpFile);
    return [
        'image' => 'data:image/png;base64,' . $base64,
        'size_bytes' => $size,
        'width' => 720,
        'height' => 1280,
        'serial' => $target['label'],
        'transport' => $target['mode'],
        'boot_completed' => $bootStatus['boot_completed'],
        'bootanim' => $bootStatus['bootanim'],
        'adbd' => $bootStatus['adbd'],
        'timestamp' => date('c'),
    ];
}

// ═══════════════════════════════════════════════════════════════
// ANDROID INPUT COMMANDS
// ═══════════════════════════════════════════════════════════════
function androidTap(): array {
    $target = getAndroidTarget();
    if (!$target) {
        return ['error' => 'No Android device connected'];
    }

    $x = (int)($_POST['x'] ?? 0);
    $y = (int)($_POST['y'] ?? 0);
    if ($x < 0 || $x > 720 || $y < 0 || $y > 1280) {
        return ['error' => 'Coordinates out of bounds (720x1280)'];
    }
    if ($target['mode'] === 'docker') {
        $result = trim(shell_exec('docker exec ' . escapeshellarg(ANDROID_CONTAINER) . ' sh -lc ' . escapeshellarg("input tap {$x} {$y}") . ' 2>&1') ?: '');
    } else {
        $result = trim(shell_exec(ADB_BIN . ' -s ' . escapeshellarg($target['serial']) . " shell input tap {$x} {$y} 2>&1") ?: '');
    }
    return ['success' => true, 'action' => 'tap', 'x' => $x, 'y' => $y, 'serial' => $target['label'], 'transport' => $target['mode'], 'output' => $result];
}

function androidInput(): array {
    $target = getAndroidTarget();
    if (!$target) {
        return ['error' => 'No Android device connected'];
    }

    $text = $_POST['text'] ?? '';
    if (empty($text) || strlen($text) > 500) {
        return ['error' => 'Text required (max 500 chars)'];
    }
    // Escape for shell — only allow safe chars
    $safe = preg_replace('/[^a-zA-Z0-9@._\-+= ]/', '', $text);
    $escaped = str_replace(' ', '%s', $safe);
    if ($target['mode'] === 'docker') {
        $result = trim(shell_exec('docker exec ' . escapeshellarg(ANDROID_CONTAINER) . ' sh -lc ' . escapeshellarg('input text ' . $escaped) . ' 2>&1') ?: '');
    } else {
        $result = trim(shell_exec(ADB_BIN . ' -s ' . escapeshellarg($target['serial']) . ' shell input text ' . escapeshellarg($escaped) . ' 2>&1') ?: '');
    }
    return ['success' => true, 'action' => 'input', 'text' => $safe, 'serial' => $target['label'], 'transport' => $target['mode'], 'output' => $result];
}

function androidKey(): array {
    $target = getAndroidTarget();
    if (!$target) {
        return ['error' => 'No Android device connected'];
    }

    $key = $_POST['key'] ?? '';
    // Whitelist allowed keys
    $allowed = ['KEYCODE_HOME','KEYCODE_BACK','KEYCODE_MENU','KEYCODE_POWER',
                'KEYCODE_VOLUME_UP','KEYCODE_VOLUME_DOWN','KEYCODE_ENTER',
                'KEYCODE_DEL','KEYCODE_TAB','KEYCODE_ESCAPE','KEYCODE_APP_SWITCH',
                'KEYCODE_DPAD_UP','KEYCODE_DPAD_DOWN','KEYCODE_DPAD_LEFT','KEYCODE_DPAD_RIGHT'];
    if (!in_array($key, $allowed)) {
        return ['error' => 'Key not allowed', 'allowed' => $allowed];
    }
    if ($target['mode'] === 'docker') {
        $result = trim(shell_exec('docker exec ' . escapeshellarg(ANDROID_CONTAINER) . ' sh -lc ' . escapeshellarg("input keyevent {$key}") . ' 2>&1') ?: '');
    } else {
        $result = trim(shell_exec(ADB_BIN . ' -s ' . escapeshellarg($target['serial']) . " shell input keyevent {$key} 2>&1") ?: '');
    }
    return ['success' => true, 'action' => 'keyevent', 'key' => $key, 'serial' => $target['label'], 'transport' => $target['mode'], 'output' => $result];
}

function androidSwipe(): array {
    $target = getAndroidTarget();
    if (!$target) {
        return ['error' => 'No Android device connected'];
    }

    $x1 = (int)($_POST['x1'] ?? 0);
    $y1 = (int)($_POST['y1'] ?? 0);
    $x2 = (int)($_POST['x2'] ?? 0);
    $y2 = (int)($_POST['y2'] ?? 0);
    $duration = min(max((int)($_POST['duration'] ?? 300), 100), 3000);
    if ($target['mode'] === 'docker') {
        $result = trim(shell_exec('docker exec ' . escapeshellarg(ANDROID_CONTAINER) . ' sh -lc ' . escapeshellarg("input swipe {$x1} {$y1} {$x2} {$y2} {$duration}") . ' 2>&1') ?: '');
    } else {
        $result = trim(shell_exec(ADB_BIN . ' -s ' . escapeshellarg($target['serial']) . " shell input swipe {$x1} {$y1} {$x2} {$y2} {$duration} 2>&1") ?: '');
    }
    return ['success' => true, 'action' => 'swipe', 'from' => [$x1,$y1], 'to' => [$x2,$y2], 'duration' => $duration, 'serial' => $target['label'], 'transport' => $target['mode'], 'output' => $result];
}

function adbCommand(): array {
    $target = getAndroidTarget();
    if (!$target) return ['error' => 'No Android device connected'];

    $cmd = trim($_POST['cmd'] ?? '');
    if (empty($cmd)) return ['error' => 'No command provided'];
    // Safety: block dangerous commands
    $blocked = ['rm -rf','mkfs','dd if=','format','reboot','shutdown','su ','chmod 777'];
    foreach ($blocked as $b) {
        if (stripos($cmd, $b) !== false) {
            return ['error' => 'Command blocked for safety', 'blocked' => $b];
        }
    }
    if (strlen($cmd) > 500) return ['error' => 'Command too long'];
    if ($target['mode'] === 'docker') {
        $output = shell_exec('docker exec ' . escapeshellarg(ANDROID_CONTAINER) . ' sh -lc ' . escapeshellarg($cmd) . ' 2>&1') ?: '';
    } else {
        $output = shell_exec(ADB_BIN . ' -s ' . escapeshellarg($target['serial']) . ' shell ' . escapeshellarg($cmd) . ' 2>&1') ?: '';
    }
    return ['success' => true, 'command' => $cmd, 'serial' => $target['label'], 'transport' => $target['mode'], 'output' => $output, 'timestamp' => date('c')];
}

// ═══════════════════════════════════════════════════════════════
// CRYPTO BALANCES (Poloniex) + Market Prices Fallback
// ═══════════════════════════════════════════════════════════════
function getCryptoBalances(): array {
    $envFile = dirname(__DIR__) . '/.env.php'; if (file_exists($envFile)) require_once $envFile;
    $key = getenv('POLONIEX_API_KEY');
    $secret = getenv('POLONIEX_API_SECRET');

    $balances = [];
    $balanceError = null;

    // Try authenticated balance fetch
    if ($key && $secret) {
        $timestamp = round(microtime(true) * 1000);
        $method = 'GET';
        $path = '/accounts/balances';
        $signPayload = "{$timestamp}\n{$method}\n{$path}\n";
        $signature = base64_encode(hash_hmac('sha256', $signPayload, $secret, true));

        $ch = curl_init("https://api.poloniex.com{$path}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "key: {$key}",
                "signTimestamp: {$timestamp}",
                "signature: {$signature}",
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 10,
        ]);
        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $data = json_decode($resp, true) ?: [];
            foreach ($data as $b) {
                $avail = (float)($b['available'] ?? 0);
                $hold = (float)($b['hold'] ?? 0);
                if ($avail > 0 || $hold > 0) {
                    $balances[] = [
                        'currency' => $b['currency'] ?? 'unknown',
                        'available' => $avail,
                        'hold' => $hold,
                        'total' => $avail + $hold,
                    ];
                }
            }
        } else {
            $balanceError = 'API keys expired or revoked — regenerate at poloniex.com/settings/api-keys';
        }
    } else {
        $balanceError = 'Poloniex API keys not configured';
    }

    // Always fetch public market prices for watchlist
    $watchlist = ['BTC_USDT','ETH_USDT','SOL_USDT','XRP_USDT','ADA_USDT','DOGE_USDT'];
    $prices = [];
    $ch = curl_init('https://api.poloniex.com/markets/ticker24h');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
    $tickerResp = curl_exec($ch);
    $tickerCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($tickerCode === 200) {
        $tickers = json_decode($tickerResp, true) ?: [];
        foreach ($tickers as $t) {
            $sym = $t['symbol'] ?? '';
            if (in_array($sym, $watchlist)) {
                $prices[] = [
                    'symbol' => $sym,
                    'price' => $t['close'] ?? $t['markPrice'] ?? '0',
                    'change' => $t['dailyChange'] ?? '0',
                    'high' => $t['high'] ?? '0',
                    'low' => $t['low'] ?? '0',
                    'volume' => $t['quantity'] ?? '0',
                ];
            }
        }
    }

    return [
        'balances' => $balances,
        'count' => count($balances),
        'prices' => $prices,
        'balance_error' => $balanceError,
        'timestamp' => date('c'),
    ];
}

// ═══════════════════════════════════════════════════════════════
// MISSIONS
// ═══════════════════════════════════════════════════════════════
function getMissions(): array {
    if (!function_exists('getSharedDB')) {
        require_once dirname(__DIR__) . '/includes/db-config.inc.php';
    }
    $pdo = getSharedDB();
    $stmt = $pdo->query("SELECT id, title, category, status, priority, due_date FROM commander_missions WHERE status NOT IN ('completed','cancelled') ORDER BY sort_order ASC, priority DESC LIMIT 20");
    $missions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return ['missions' => $missions, 'count' => count($missions)];
}

function loadRotationSnapshot(): array {
    $stateFile = '/home/gositeme/.vault/rotation-state.json';
    if (!file_exists($stateFile)) {
        return ['rotations' => []];
    }

    $state = json_decode((string) file_get_contents($stateFile), true);
    return is_array($state) ? $state : ['rotations' => []];
}

function getTrackedCredentialWatchlist(): array {
    if (!function_exists('getSharedDB')) {
        require_once dirname(__DIR__) . '/includes/db-config.inc.php';
    }

    $tracked = [
        'ssh-ubuntu-user' => [
            'label' => 'Ubuntu SSH',
            'account' => 'ubuntu',
            'mode' => 'auto',
            'rotation_key' => 'ssh_password',
            'lookup_field' => 'credential_id',
        ],
        'ssh-root-user' => [
            'label' => 'Root SSH',
            'account' => 'root',
            'mode' => 'manual',
            'rotation_key' => null,
            'lookup_field' => 'credential_id',
        ],
        'ssh-gositeme-user' => [
            'label' => 'GoSiteMe SSH',
            'account' => 'gositeme',
            'mode' => 'manual',
            'rotation_key' => null,
            'lookup_field' => 'credential_id',
        ],
    ];

    $pdo = getSharedDB();
    $rows = $pdo->query(
        "SELECT credential_id, service_name, category, updated_at, last_verified
         FROM commander_credentials
         WHERE credential_id IN ('ssh-ubuntu-user', 'ssh-root-user', 'ssh-gositeme-user')"
    )->fetchAll(PDO::FETCH_ASSOC);

    $rowMap = [];
    foreach ($rows as $row) {
        if (!empty($row['credential_id'])) {
            $rowMap['credential_id:' . $row['credential_id']] = $row;
        }
        if (!empty($row['service_name'])) {
            $rowMap['service_name:' . $row['service_name']] = $row;
        }
    }

    $rotationState = loadRotationSnapshot()['rotations'] ?? [];
    $results = [];
    foreach ($tracked as $lookup => $meta) {
        $row = $rowMap[$meta['lookup_field'] . ':' . $lookup] ?? null;
        $rotation = $meta['rotation_key'] ? ($rotationState[$meta['rotation_key']] ?? []) : [];
        $freshnessTs = 0;
        foreach (['last_verified', 'updated_at'] as $field) {
            if (!empty($row[$field])) {
                $freshnessTs = max($freshnessTs, strtotime((string) $row[$field]) ?: 0);
            }
        }

        $status = 'missing';
        if ($row) {
            $status = 'ok';
            if (!empty($rotation['paused'])) {
                $status = 'paused';
            } elseif (($rotation['consecutive_failures'] ?? 0) > 0) {
                $status = 'warning';
            } elseif ($freshnessTs > 0 && (time() - $freshnessTs) > (14 * 24 * 3600)) {
                $status = 'warning';
            }
        }

        $results[] = [
            'lookup' => $lookup,
            'label' => $meta['label'],
            'account' => $meta['account'],
            'mode' => $meta['mode'],
            'status' => $status,
            'rotation_key' => $meta['rotation_key'],
            'updated_at' => $row['updated_at'] ?? null,
            'last_verified' => $row['last_verified'] ?? null,
            'category' => $row['category'] ?? null,
            'paused' => !empty($rotation['paused']),
            'consecutive_failures' => (int) ($rotation['consecutive_failures'] ?? 0),
            'pause_reason' => $rotation['pause_reason'] ?? null,
        ];
    }

    return $results;
}

// ═══════════════════════════════════════════════════════════════
// VAULT STATUS
// ═══════════════════════════════════════════════════════════════
function getVaultStatus(): array {
    $keyFile = '/home/gositeme/.vault-master-key';
    $tmpfsKey = '/run/user/1004/keys/vault.key';
    $encFile = '/home/gositeme/.vault/credentials.enc';
    $vaultDir = '/home/gositeme/.vault';

    $masterKeyPresent = file_exists($keyFile);
    $tmpfsKeyPresent = file_exists($tmpfsKey);
    $masterKey = $masterKeyPresent ? trim((string)file_get_contents($keyFile)) : '';
    $ramKey = $tmpfsKeyPresent ? trim((string)file_get_contents($tmpfsKey)) : '';
    $keysMatch = $masterKeyPresent && $tmpfsKeyPresent && $masterKey !== '' && hash_equals($masterKey, $ramKey);
    $legacyKeySync = $masterKeyPresent && $tmpfsKeyPresent ? ($keysMatch ? 'MATCH' : 'STALE') : 'UNKNOWN';

    $vaultRead = 'UNKNOWN';
    if (file_exists($encFile)) {
        require_once dirname(__DIR__) . '/scripts/vault-crypto.php';
        $encrypted = (string)file_get_contents($encFile);
        if ($encrypted !== '' && function_exists('vault_decrypt')) {
            $vaultRead = vault_decrypt($encrypted) !== false ? 'DECRYPT OK' : 'DECRYPT FAIL';
        }
    }

    $encFiles = [];
    foreach ((array)glob($vaultDir . '/*.enc') as $file) {
        $encFiles[] = [
            'name' => basename($file),
            'size' => filesize($file) ?: 0,
            'modified_at' => date('c', filemtime($file) ?: time()),
        ];
    }

    $credentialStats = [
        'total' => 0,
        'active' => 0,
        'legacy' => 0,
        'by_category' => [],
    ];

    if (!function_exists('getSharedDB')) {
        require_once dirname(__DIR__) . '/includes/db-config.inc.php';
    }

    try {
        $pdo = getSharedDB();
        $rows = $pdo->query("SELECT category, COUNT(*) AS count FROM commander_credentials GROUP BY category")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $category = (string)$row['category'];
            $count = (int)$row['count'];
            $credentialStats['by_category'][$category] = $count;
            $credentialStats['total'] += $count;
            if ($category === 'archive') {
                $credentialStats['legacy'] += $count;
            } else {
                $credentialStats['active'] += $count;
            }
        }
    } catch (Throwable $e) {
        $credentialStats['error'] = 'stats unavailable';
    }

    $rotationLogFile = '/home/gositeme/.vault/rotation-log.json';
    $rotationState = loadRotationSnapshot();
    $rotationLog = file_exists($rotationLogFile)
        ? (json_decode((string)file_get_contents($rotationLogFile), true) ?: [])
        : [];

    $rotationSummary = [];
    foreach (['ssh_password' => 'Ubuntu SSH', 'quickqr_db' => 'QuickQR DB', 'breakglass' => 'Breakglass'] as $key => $label) {
        $state = $rotationState['rotations'][$key] ?? [];
        $lastLog = null;
        foreach ($rotationLog as $logEntry) {
            if (($logEntry['credential'] ?? '') === $key) {
                $lastLog = $logEntry;
                break;
            }
        }
        $rotationSummary[$key] = [
            'label' => $label,
            'last_rotated_at' => $state['last_rotated_at'] ?? null,
            'consecutive_failures' => (int)($state['consecutive_failures'] ?? 0),
            'paused' => !empty($state['paused']),
            'last_result' => $lastLog['success'] ?? null,
            'last_message' => $lastLog['message'] ?? null,
        ];
    }
    
    return [
        'master_key_file' => $masterKeyPresent ? 'PRESENT' : 'MISSING',
        'tmpfs_key' => $tmpfsKeyPresent ? 'LOADED' : 'NOT IN RAM',
        'vault_read' => $vaultRead,
        'legacy_key_sync' => $legacyKeySync,
        'credentials_enc' => file_exists($encFile) ? 'PRESENT (' . filesize($encFile) . ' bytes)' : 'MISSING',
        'vault_dir' => is_dir($vaultDir) ? 'EXISTS' : 'MISSING',
        'key_loader' => file_exists('/home/gositeme/.vault/key-loader.php') ? 'OK' : 'MISSING',
        'credential_stats' => $credentialStats,
        'enc_files' => $encFiles,
        'rotation' => $rotationSummary,
        'tracked_credentials' => getTrackedCredentialWatchlist(),
        'status' => ($masterKeyPresent && file_exists($encFile) && $vaultRead === 'DECRYPT OK') ? 'OPERATIONAL' : 'DEGRADED',
        'timestamp' => date('c'),
    ];
}

// ═══════════════════════════════════════════════════════════════
// SERVICES (port listeners)
// ═══════════════════════════════════════════════════════════════
function getServices(): array {
    $raw = shell_exec("ss -tlnp 2>/dev/null") ?: '';
    $lines = explode("\n", trim($raw));
    $services = [];
    foreach (array_slice($lines, 1) as $line) {
        if (preg_match('/LISTEN\s+\d+\s+\d+\s+(\S+)\s+\S+(?:\s+users:\(\((.+?)\)\))?/', trim($line), $m)) {
            $services[] = [
                'listen' => $m[1],
                'process' => $m[2] ?? 'system / privileged',
            ];
        }
    }
    return ['services' => $services, 'count' => count($services)];
}

function getDesktopStatus(): array {
    $listeners = shell_exec('ss -tln 2>/dev/null') ?: '';
    $hasListener = static function (string $needle) use ($listeners): bool {
        return str_contains($listeners, $needle);
    };

    $vncLocal = $hasListener('127.0.0.1:5902') || $hasListener('[::1]:5902');
    $websockifyLocal = $hasListener('127.0.0.1:6080');
    $websockifyPublic = $hasListener('0.0.0.0:6090');
    $novncHttpCode = trim((string) shell_exec("curl -fsS --max-time 2 -o /dev/null -w '%{http_code}' http://127.0.0.1:6080/vnc.html 2>/dev/null"));
    $novncHealthy = $novncHttpCode === '200';

    $status = 'offline';
    if ($vncLocal && ($websockifyLocal || $websockifyPublic) && $novncHealthy) {
        $status = 'live';
    } elseif ($vncLocal || $websockifyLocal || $websockifyPublic || $novncHealthy) {
        $status = 'degraded';
    }

    return [
        'status' => $status,
        'vnc_listener' => $vncLocal,
        'websockify_local' => $websockifyLocal,
        'websockify_public' => $websockifyPublic,
        'novnc_http_code' => $novncHttpCode !== '' ? $novncHttpCode : null,
        'novnc_healthy' => $novncHealthy,
        'timestamp' => date('c'),
    ];
}

// ═══════════════════════════════════════════════════════════════
// ROTATION STATUS
// ═══════════════════════════════════════════════════════════════
function getRotationStatus(): array {
    $intervals = [
        'quickqr_db' => 11 * 60,
        'ssh_password' => 13 * 60,
        'breakglass' => 7 * 24 * 3600,
    ];

    $state = loadRotationSnapshot();

    $now = time();
    $rot = $state['rotations'] ?? [];

    $build = function(string $key, string $label) use ($rot, $intervals, $now): array {
        $last = (int)($rot[$key]['last_rotated'] ?? 0);
        $elapsed = max(0, $now - $last);
        $interval = (int)$intervals[$key];
        $remaining = $last > 0 ? max(0, $interval - $elapsed) : 0;
        return [
            'key' => $key,
            'label' => $label,
            'interval_sec' => $interval,
            'last_rotated' => $last,
            'last_rotated_at' => $rot[$key]['last_rotated_at'] ?? null,
            'remaining_sec' => $remaining,
            'due' => $last === 0 ? true : ($elapsed >= $interval),
            'consecutive_failures' => (int)($rot[$key]['consecutive_failures'] ?? 0),
            'paused' => !empty($rot[$key]['paused']),
        ];
    };

    return [
        'status' => 'ok',
        'ssh' => $build('ssh_password', 'SSH password'),
        'db' => $build('quickqr_db', 'QuickQR DB'),
        'breakglass' => $build('breakglass', 'Breakglass'),
        'tracked' => getTrackedCredentialWatchlist(),
        'timestamp' => date('c'),
    ];
}

// ═══════════════════════════════════════════════════════════════
// VAULT CREDENTIALS (Decrypted — Commander Eyes Only)
// ═══════════════════════════════════════════════════════════════
function getVaultCredentials(): array {
    // Prefer trusted Commander vault session from login; fall back to PIN re-auth.
    $pin = trim($_POST['vault_pin'] ?? '');
    $timeout = defined('COMMANDER_VAULT_IDLE_TIMEOUT_SECONDS')
        ? (int) COMMANDER_VAULT_IDLE_TIMEOUT_SECONDS
        : 900;
    $sessionUnlocked = !empty($_SESSION['vault_unlocked'])
        && $_SESSION['vault_unlocked'] === true
        && !empty($_SESSION['vault_unlock_time'])
        && (time() - (int)$_SESSION['vault_unlock_time']) <= $timeout;
    
    // Load crypto helpers and shared DB config used by the main auth stack.
    require_once dirname(__DIR__) . '/scripts/vault-crypto.php';
    if (!function_exists('getSharedDB')) {
        require_once dirname(__DIR__) . '/includes/db-config.inc.php';
    }

    $pdo = getSharedDB();
    $clientId = (int)($_SESSION['client_id'] ?? 33);
    
    // Verify PIN against Argon2id hash in commander_vault (same PIN as website login)
    $stmt = $pdo->prepare("SELECT pin_hash, failed_attempts, lockout_until, frozen_until FROM commander_vault WHERE client_id = ?");
    $stmt->execute([$clientId]);
    $vault = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$vault || empty($vault['pin_hash'])) {
        return ['locked' => true, 'message' => 'No Commander Vault PIN set. Set one at gositeme.com first.'];
    }
    
    // Check lockout/freeze status
    $now = time();
    if (!empty($vault['frozen_until']) && strtotime($vault['frozen_until']) > $now) {
        return ['locked' => true, 'message' => 'Vault FROZEN due to too many failed attempts. Try later.'];
    }
    if (!empty($vault['lockout_until']) && strtotime($vault['lockout_until']) > $now) {
        return ['locked' => true, 'message' => 'Vault temporarily locked. Try again in a few minutes.'];
    }
    
    // If session is still unlocked from login, skip PIN prompt entirely.
    if ($sessionUnlocked) {
        $_SESSION['vault_unlock_time'] = time();
    } else {
        if ($pin === '') {
            return ['locked' => true, 'message' => 'Session locked. Enter Commander PIN to unlock vault credentials.'];
        }

        // Argon2id constant-time verification
        if (!password_verify($pin, $vault['pin_hash'])) {
            // Increment failed attempts in DB
            $pdo->prepare("UPDATE commander_vault SET failed_attempts = failed_attempts + 1 WHERE client_id = ?")->execute([$clientId]);
            return ['locked' => true, 'message' => 'Incorrect PIN.'];
        }

        // Successful PIN unlock should refresh the vault session.
        $_SESSION['vault_unlocked'] = true;
        $_SESSION['vault_unlock_time'] = time();
    }
    
    // Success — reset failed attempts
    $pdo->prepare("UPDATE commander_vault SET failed_attempts = 0, lockout_until = NULL WHERE client_id = ?")->execute([$clientId]);
    
    // Decrypt credentials
    $stmt = $pdo->query("SELECT credential_id, service_name, service_url, username, password, notes, category, last_verified, updated_at FROM commander_credentials ORDER BY category, service_name");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $decryptedFields = ['service_name', 'service_url', 'username', 'password', 'notes'];
    $credentials = [];
    
    foreach ($rows as $row) {
        foreach ($decryptedFields as $field) {
            if (!empty($row[$field])) {
                $decrypted = vault_decrypt($row[$field]);
                $row[$field] = ($decrypted !== false) ? $decrypted : '[DECRYPT FAILED]';
            }
        }
        $credentials[] = $row;
    }
    
    return [
        'credentials' => $credentials,
        'count' => count($credentials),
        'timestamp' => date('c'),
        'warning' => 'CLASSIFIED — Do not screenshot or share'
    ];
}

function changeIdePassword(): array {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return ['error' => 'POST required'];
    }
    $newPass = trim($_POST['new_password'] ?? '');
    if (strlen($newPass) < 12) {
        return ['error' => 'Password must be at least 12 characters'];
    }
    if (strlen($newPass) > 128) {
        return ['error' => 'Password too long'];
    }

    if (!function_exists('getSharedDB')) {
        require_once dirname(__DIR__) . '/includes/db-config.inc.php';
    }

    $db = getSharedDB();
    $stmt = $db->prepare('SELECT id, email, google_email FROM alfred_ide_users WHERE client_id = 33 LIMIT 1');
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $email = 'danny@gositeme.com';
        $hash = password_hash($newPass, PASSWORD_ARGON2ID);
        $db->prepare('INSERT INTO alfred_ide_users (client_id, email, google_name, password_hash) VALUES (33, ?, ?, ?)')
            ->execute([$email, 'Commander', $hash]);
        return [
            'success' => true,
            'message' => 'Commander Alfred IDE login created and password saved to alfred_ide_users.',
            'timestamp' => date('c'),
        ];
    }

    $hash = password_hash($newPass, PASSWORD_ARGON2ID);
    $db->prepare('UPDATE alfred_ide_users SET password_hash = ?, failed_attempts = 0, lockout_until = NULL, frozen_until = NULL, email = COALESCE(email, google_email) WHERE id = ?')
        ->execute([$hash, $user['id']]);

    return [
        'success' => true,
        'message' => 'Alfred IDE login password updated in alfred_ide_users. code-server remains auth:none behind the site auth gate.',
        'timestamp' => date('c'),
    ];
}

function lockVaultSession(): array {
    $_SESSION['vault_unlocked'] = false;
    $_SESSION['vault_unlock_time'] = 0;
    return [
        'success' => true,
        'locked' => true,
        'timestamp' => date('c'),
    ];
}

// ═══════════════════════════════════════════════════════════════
// UPDATE VAULT CREDENTIAL (Commander Eyes Only)
// ═══════════════════════════════════════════════════════════════
function updateVaultCredential(): array {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return ['error' => 'POST required'];
    }

    // Require active vault session
    $timeout = defined('COMMANDER_VAULT_IDLE_TIMEOUT_SECONDS')
        ? (int) COMMANDER_VAULT_IDLE_TIMEOUT_SECONDS
        : 900;
    if (empty($_SESSION['vault_unlocked'])
        || $_SESSION['vault_unlocked'] !== true
        || empty($_SESSION['vault_unlock_time'])
        || (time() - (int)$_SESSION['vault_unlock_time']) > $timeout) {
        return ['error' => 'Vault session expired. Re-enter PIN.', 'locked' => true];
    }

    $credentialId = trim($_POST['credential_id'] ?? '');
    if ($credentialId === '') {
        return ['error' => 'credential_id is required'];
    }

    require_once dirname(__DIR__) . '/scripts/vault-crypto.php';
    if (!function_exists('getSharedDB')) {
        require_once dirname(__DIR__) . '/includes/db-config.inc.php';
    }
    $pdo = getSharedDB();

    // Verify credential exists
    $stmt = $pdo->prepare('SELECT credential_id FROM commander_credentials WHERE credential_id = ?');
    $stmt->execute([$credentialId]);
    if (!$stmt->fetch()) {
        return ['error' => 'Credential not found'];
    }

    // Build update set from allowed fields
    $allowed = ['service_name', 'service_url', 'username', 'password', 'notes', 'category'];
    $encryptedFields = ['service_name', 'service_url', 'username', 'password', 'notes'];
    $updates = [];
    $params = [];

    foreach ($allowed as $field) {
        if (!isset($_POST[$field])) continue;
        $value = $_POST[$field];
        if (in_array($field, $encryptedFields) && $value !== '') {
            $value = vault_encrypt($value);
        }
        $updates[] = "{$field} = ?";
        $params[] = $value;
    }

    if (empty($updates)) {
        return ['error' => 'No fields to update'];
    }

    $updates[] = 'updated_at = NOW()';
    $params[] = $credentialId;

    $sql = 'UPDATE commander_credentials SET ' . implode(', ', $updates) . ' WHERE credential_id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Refresh session timer
    $_SESSION['vault_unlock_time'] = time();

    return [
        'success' => true,
        'credential_id' => $credentialId,
        'fields_updated' => count($updates) - 1,
        'timestamp' => date('c'),
    ];
}

// ═══════════════════════════════════════════════════════════════
// DELETE VAULT CREDENTIAL (Commander Eyes Only)
// ═══════════════════════════════════════════════════════════════
function deleteVaultCredential(): array {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return ['error' => 'POST required'];
    }

    $timeout = defined('COMMANDER_VAULT_IDLE_TIMEOUT_SECONDS')
        ? (int) COMMANDER_VAULT_IDLE_TIMEOUT_SECONDS
        : 900;
    if (empty($_SESSION['vault_unlocked'])
        || $_SESSION['vault_unlocked'] !== true
        || empty($_SESSION['vault_unlock_time'])
        || (time() - (int)$_SESSION['vault_unlock_time']) > $timeout) {
        return ['error' => 'Vault session expired. Re-enter PIN.', 'locked' => true];
    }

    $credentialId = trim($_POST['credential_id'] ?? '');
    if ($credentialId === '') {
        return ['error' => 'credential_id is required'];
    }

    if (!function_exists('getSharedDB')) {
        require_once dirname(__DIR__) . '/includes/db-config.inc.php';
    }
    $pdo = getSharedDB();

    $stmt = $pdo->prepare('DELETE FROM commander_credentials WHERE credential_id = ?');
    $stmt->execute([$credentialId]);

    if ($stmt->rowCount() === 0) {
        return ['error' => 'Credential not found'];
    }

    $_SESSION['vault_unlock_time'] = time();

    return [
        'success' => true,
        'credential_id' => $credentialId,
        'deleted' => true,
        'timestamp' => date('c'),
    ];
}
