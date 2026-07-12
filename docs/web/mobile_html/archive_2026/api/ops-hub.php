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
        'vault-credentials' => getVaultCredentials(),
        'vault-lock'      => lockVaultSession(),
        'change-ide-password' => changeIdePassword(),
        'services'        => getServices(),
        default           => ['error' => 'Unknown action', 'available' => [
            'system-vitals','docker-status','pm2-status','android-screen',
            'android-tap','android-input','android-key','android-swipe',
            'adb-command','crypto-balances','missions','vault-status','rotation-status','vault-credentials','vault-lock','services'
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

    $tmpFile = '/tmp/android-screen-' . uniqid() . '.png';
    if ($target['mode'] === 'docker') {
        $result = shell_exec('docker exec ' . escapeshellarg(ANDROID_CONTAINER) . ' sh -lc ' . escapeshellarg('screencap -p') . ' > ' . escapeshellarg($tmpFile) . ' 2>&1');
    } else {
        $result = shell_exec(ADB_BIN . ' -s ' . escapeshellarg($target['serial']) . ' exec-out screencap -p > ' . escapeshellarg($tmpFile) . ' 2>&1');
    }
    if (!file_exists($tmpFile) || filesize($tmpFile) < 100) {
        $bootStatus = getAndroidBootStatus($target);
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

    $rotationStateFile = '/home/gositeme/.vault/rotation-state.json';
    $rotationLogFile = '/home/gositeme/.vault/rotation-log.json';
    $rotationState = file_exists($rotationStateFile)
        ? (json_decode((string)file_get_contents($rotationStateFile), true) ?: [])
        : [];
    $rotationLog = file_exists($rotationLogFile)
        ? (json_decode((string)file_get_contents($rotationLogFile), true) ?: [])
        : [];

    $rotationSummary = [];
    foreach (['ssh_password' => 'SSH password', 'quickqr_db' => 'QuickQR DB'] as $key => $label) {
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
        if (preg_match('/LISTEN\s+\d+\s+\d+\s+([\S]+)\s+[\S]+\s+users:\(\((.+?)\)\)/', $line, $m)) {
            $services[] = [
                'listen' => $m[1],
                'process' => $m[2],
            ];
        }
    }
    return ['services' => $services, 'count' => count($services)];
}

// ═══════════════════════════════════════════════════════════════
// ROTATION STATUS
// ═══════════════════════════════════════════════════════════════
function getRotationStatus(): array {
    $stateFile = '/home/gositeme/.vault/rotation-state.json';
    $intervals = [
        'quickqr_db' => 11 * 60,
        'ssh_password' => 13 * 60,
    ];

    $state = ['rotations' => []];
    if (file_exists($stateFile)) {
        $raw = json_decode(file_get_contents($stateFile), true);
        if (is_array($raw)) {
            $state = $raw;
        }
    }

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
    $configFile = '/home/gositeme/.config/code-server/config.yaml';
    if (!file_exists($configFile)) {
        return ['error' => 'code-server config not found'];
    }
    $config = file_get_contents($configFile);
    $config = preg_replace('/^password: .+$/m', 'password: ' . $newPass, $config);
    if (file_put_contents($configFile, $config) === false) {
        return ['error' => 'Failed to write config'];
    }
    shell_exec('/home/gositeme/.local/node_modules/.bin/pm2 restart alfred-ide >/dev/null 2>&1 &');
    return [
        'success' => true,
        'message' => 'IDE password changed. code-server restarting — reload the IDE in a few seconds.',
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
