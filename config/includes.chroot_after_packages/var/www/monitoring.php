<?php
/**
 * GoSiteMe Monitoring Dashboard — The War Room
 * Real-time server health, PM2 fleet, service endpoints, resource usage
 * Auth: client_id 33 only (Commander)
 * Auto-refreshes every 30 seconds via AJAX
 */
session_start();
require_once __DIR__ . '/includes/db-config.inc.php';

// ── AUTH GATE — Commander Only ───────────────────────────────────────
$db = getSharedDB();
$token = $_COOKIE['alfred_ide_token'] ?? $_SESSION['ide_session_token'] ?? '';
$authed = false;
if ($token) {
    $hash = hash('sha256', $token);
    $u = $db->prepare("SELECT client_id FROM alfred_ide_users WHERE session_token = ? AND token_expires > NOW() LIMIT 1");
    $u->execute([$hash]);
    $row = $u->fetch(PDO::FETCH_ASSOC);
    if ($row && (int)$row['client_id'] === 33) $authed = true;
}
if (!$authed) {
    header('Location: /alfred-ide-auth.php');
    exit;
}

// ── AJAX API — Return JSON for auto-refresh ──────────────────────────
if (isset($_GET['api'])) {
    header('Content-Type: application/json');
    $section = $_GET['api'];

    if ($section === 'all' || $section === 'server') {
        // Server vitals
        $mem = shell_exec('free -b 2>/dev/null');
        preg_match('/Mem:\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/', $mem, $m);
        $memTotal = (int)($m[1] ?? 0); $memUsed = (int)($m[2] ?? 0); $memAvail = (int)($m[6] ?? 0);
        $memPct = $memTotal > 0 ? round($memUsed / $memTotal * 100) : 0;

        preg_match('/Swap:\s+(\d+)\s+(\d+)/', $mem, $s);
        $swapTotal = (int)($s[1] ?? 0); $swapUsed = (int)($s[2] ?? 0);
        $swapPct = $swapTotal > 0 ? round($swapUsed / $swapTotal * 100) : 0;

        $load = sys_getloadavg();
        $uptime = trim(shell_exec("uptime -p 2>/dev/null"));
        $uptimeRaw = trim(shell_exec("cat /proc/uptime 2>/dev/null | awk '{print \$1}'"));
        $disk = shell_exec("df -B1 / 2>/dev/null");
        preg_match('/\d+\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)%/', $disk, $dk);
        $diskTotal = (int)($dk[1] ?? 0) + (int)($dk[2] ?? 0);
        $diskUsed = (int)($dk[1] ?? 0); $diskAvail = (int)($dk[2] ?? 0); $diskPct = (int)($dk[4] ?? 0);

        $cpuCores = (int)trim(shell_exec("nproc 2>/dev/null"));
        $cpuModel = trim(shell_exec("grep 'model name' /proc/cpuinfo 2>/dev/null | head -1 | cut -d':' -f2"));

        // Network connections
        $tcpConns = (int)trim(shell_exec("ss -t 2>/dev/null | tail -n +2 | wc -l"));

        $serverData = [
            'memory' => ['total' => $memTotal, 'used' => $memUsed, 'available' => $memAvail, 'percent' => $memPct],
            'swap' => ['total' => $swapTotal, 'used' => $swapUsed, 'percent' => $swapPct],
            'load' => $load,
            'uptime' => $uptime,
            'uptime_seconds' => (float)$uptimeRaw,
            'disk' => ['total' => $diskTotal, 'used' => $diskUsed, 'available' => $diskAvail, 'percent' => $diskPct],
            'cpu' => ['cores' => $cpuCores, 'model' => $cpuModel],
            'tcp_connections' => $tcpConns,
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }

    if ($section === 'all' || $section === 'pm2') {
        $pm2Raw = shell_exec("pm2 jlist 2>/dev/null");
        $pm2 = json_decode($pm2Raw, true) ?: [];
        $pm2Data = ['online' => 0, 'stopped' => 0, 'errored' => 0, 'services' => []];
        $totalMem = 0;
        foreach ($pm2 as $p) {
            $st = $p['pm2_env']['status'] ?? 'unknown';
            if ($st === 'online') $pm2Data['online']++;
            elseif ($st === 'stopped') $pm2Data['stopped']++;
            else $pm2Data['errored']++;
            $mem = $p['monit']['memory'] ?? 0;
            $totalMem += $mem;
            $pm2Data['services'][] = [
                'id' => $p['pm_id'],
                'name' => $p['name'],
                'status' => $st,
                'memory' => $mem,
                'cpu' => $p['monit']['cpu'] ?? 0,
                'restarts' => $p['pm2_env']['restart_time'] ?? 0,
                'uptime' => $p['pm2_env']['pm_uptime'] ?? 0,
                'port' => $p['pm2_env']['env']['PORT'] ?? null,
            ];
        }
        $pm2Data['total'] = count($pm2);
        $pm2Data['total_memory'] = $totalMem;
        usort($pm2Data['services'], function($a, $b) { return $b['memory'] <=> $a['memory']; });
    }

    if ($section === 'all' || $section === 'endpoints') {
        $endpoints = [
            ['name' => 'Alfred IDE (code-server)', 'url' => 'http://127.0.0.1:8443/healthz', 'port' => 8443],
            ['name' => 'Alfred Agent', 'url' => 'http://127.0.0.1:3102/health', 'port' => 3102],
            ['name' => 'GoCodeMe MCP', 'url' => 'http://127.0.0.1:3006/health', 'port' => 3006],
            ['name' => 'GoForge (Gitea)', 'url' => 'http://127.0.0.1:3300/', 'port' => 3300],
            ['name' => 'Alfred WebSocket', 'url' => 'http://127.0.0.1:3010/', 'port' => 3010],
            ['name' => 'GoHostMe', 'url' => 'http://127.0.0.1:3050/', 'port' => 3050],
            ['name' => 'Redis', 'url' => null, 'port' => 6379, 'check' => 'redis'],
            ['name' => 'MySQL', 'url' => null, 'port' => 3306, 'check' => 'mysql'],
            ['name' => 'Meilisearch', 'url' => 'http://127.0.0.1:7700/health', 'port' => 7700],
            ['name' => 'Ollama', 'url' => 'http://127.0.0.1:11434/api/tags', 'port' => 11434],
            ['name' => 'Headscale', 'url' => 'http://127.0.0.1:8090/health', 'port' => 8090],
            ['name' => 'App Seeder', 'url' => 'http://127.0.0.1:3202/status', 'port' => 3202],
            ['name' => 'Torrent Tracker', 'url' => 'http://127.0.0.1:3201/', 'port' => 3201],
            ['name' => 'VNC Websockify', 'url' => null, 'port' => 6080, 'check' => 'tcp'],
            ['name' => 'Apache (HTTPS)', 'url' => 'https://root.com/', 'port' => 443],
        ];

        $endpointData = [];
        foreach ($endpoints as $ep) {
            $result = ['name' => $ep['name'], 'port' => $ep['port'], 'status' => 'unknown', 'latency' => null, 'detail' => ''];

            if (isset($ep['check']) && $ep['check'] === 'redis') {
                $start = microtime(true);
                $rConn = @fsockopen('127.0.0.1', 6379, $errno, $errstr, 2);
                if ($rConn) {
                    fwrite($rConn, "PING\r\n");
                    $resp = fgets($rConn, 128);
                    fclose($rConn);
                    $result['status'] = (strpos($resp, 'PONG') !== false) ? 'healthy' : 'degraded';
                    $result['detail'] = trim($resp);
                } else {
                    $result['status'] = 'down';
                    $result['detail'] = $errstr;
                }
                $result['latency'] = round((microtime(true) - $start) * 1000, 1);
            } elseif (isset($ep['check']) && $ep['check'] === 'mysql') {
                $start = microtime(true);
                try {
                    $db->query("SELECT 1");
                    $result['status'] = 'healthy';
                    $result['detail'] = 'Connected via socket';
                } catch (Exception $e) {
                    $result['status'] = 'down';
                    $result['detail'] = $e->getMessage();
                }
                $result['latency'] = round((microtime(true) - $start) * 1000, 1);
            } elseif (isset($ep['check']) && $ep['check'] === 'tcp') {
                $start = microtime(true);
                $conn = @fsockopen('127.0.0.1', $ep['port'], $errno, $errstr, 2);
                $result['latency'] = round((microtime(true) - $start) * 1000, 1);
                if ($conn) {
                    $result['status'] = 'healthy';
                    fclose($conn);
                } else {
                    $result['status'] = 'down';
                    $result['detail'] = $errstr;
                }
            } elseif ($ep['url']) {
                $start = microtime(true);
                $ctx = stream_context_create(['http' => ['timeout' => 3, 'ignore_errors' => true], 'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
                $resp = @file_get_contents($ep['url'], false, $ctx);
                $result['latency'] = round((microtime(true) - $start) * 1000, 1);
                if ($resp !== false) {
                    $httpCode = 0;
                    if (isset($http_response_header[0])) {
                        preg_match('/\d{3}/', $http_response_header[0], $hm);
                        $httpCode = (int)($hm[0] ?? 0);
                    }
                    $result['status'] = ($httpCode >= 200 && $httpCode < 400) ? 'healthy' : 'degraded';
                    $result['detail'] = "HTTP $httpCode";
                    // Try to parse JSON health response
                    $json = @json_decode($resp, true);
                    if ($json && isset($json['status'])) $result['detail'] .= ' — ' . $json['status'];
                    elseif ($json && isset($json['version'])) $result['detail'] .= ' — v' . $json['version'];
                } else {
                    $result['status'] = 'down';
                    $result['detail'] = 'Connection failed';
                }
            }

            $endpointData[] = $result;
        }
    }

    if ($section === 'all' || $section === 'db') {
        // Database stats
        try {
            $dbStats = [];
            $dbStats['active_ide_sessions'] = (int)$db->query("SELECT COUNT(*) FROM alfred_ide_sessions WHERE token_expires > NOW()")->fetchColumn();
            $dbStats['total_ide_users'] = (int)$db->query("SELECT COUNT(*) FROM alfred_ide_users")->fetchColumn();
            $dbStats['chat_messages_today'] = (int)$db->query("SELECT COUNT(*) FROM alfred_chat_messages WHERE created_at >= CURDATE()")->fetchColumn();
            $dbStats['total_chat_sessions'] = (int)$db->query("SELECT COUNT(*) FROM alfred_chat_sessions")->fetchColumn();
            $dbStats['unpaid_invoices'] = (int)$db->query("SELECT COUNT(*) FROM invoices WHERE status='Unpaid'")->fetchColumn();
            $dbStats['unpaid_total'] = (float)$db->query("SELECT COALESCE(SUM(total),0) FROM invoices WHERE status='Unpaid'")->fetchColumn();
            $dbStats['active_clients'] = (int)$db->query("SELECT COUNT(*) FROM clients WHERE status='Active'")->fetchColumn();
            $dbStats['active_domains'] = (int)$db->query("SELECT COUNT(*) FROM domains WHERE status='Active'")->fetchColumn();
            $dbStats['military_personnel'] = (int)$db->query("SELECT COUNT(*) FROM user_ranks WHERE is_active=1")->fetchColumn();
            $dbStats['gsm_total_supply'] = (float)$db->query("SELECT COALESCE(SUM(balance),0) FROM gsm_wallets")->fetchColumn();
            $dbStats['pm2_total'] = $pm2Data['total'] ?? count($pm2 ?? []);
        } catch (Exception $e) {
            $dbStats = ['error' => $e->getMessage()];
        }
    }

    if ($section === 'all' || $section === 'vault') {
        $vaultKey = @file_get_contents('/run/user/1004/keys/vault.key');
        $masterKey = @file_get_contents('/home/root/.vault-master-key');
        $vaultData = [
            'key_in_ram' => !empty($vaultKey) && strlen(trim($vaultKey)) === 64,
            'master_on_disk' => !empty($masterKey) && strlen(trim($masterKey)) === 64,
            'keys_match' => trim($vaultKey ?? '') === trim($masterKey ?? ''),
            'ssh_creds' => file_exists('/home/root/.vault/ssh-credentials.enc'),
            'main_vault' => file_exists('/home/root/.vault/credentials.enc'),
            'credential_count' => (int)$db->query("SELECT COUNT(*) FROM commander_credentials")->fetchColumn(),
        ];
    }

    if ($section === 'all' || $section === 'ml') {
        $ollamaBin = '/home/root/.local/bin/ollama';
        $mlData = [
            'ollama_version' => trim((string)@shell_exec($ollamaBin . ' --version 2>/dev/null')),
            'models' => [],
            'gpu_local' => [
                'nvidia_smi_available' => false,
                'summary' => 'No local NVIDIA driver detected — Ollama runs CPU on this host.',
            ],
            'burst_gpu_playbook' => [
                'Use hourly A100/H100 pods (RunPod, Lambda Labs, Modal, CoreWeave) for batch jobs; keep Ollama here for always-on small models.',
                'Wire burst workers to the same API shape: OpenAI-compatible base URL + key in vault; never commit keys.',
                'Promote successful burst configs into PM2 or cron with cost caps and idle shutdown.',
            ],
            'oss_vs_composer' => 'No single open-weight model is guaranteed to beat Composer 2 on every task — Composer uses proprietary frontier stacks. Strong OSS-aligned backups: Qwen2.5-Coder, DeepSeek-Coder family, Llama 3.3 70B — pick by VRAM and eval on your repos.',
        ];
        $nvPath = trim((string)@shell_exec('command -v nvidia-smi 2>/dev/null'));
        if ($nvPath !== '') {
            $smi = @shell_exec(escapeshellcmd($nvPath) . ' --query-gpu=name,memory.total,driver_version --format=csv,noheader 2>/dev/null');
            if (is_string($smi) && $smi !== '') {
                $mlData['gpu_local']['nvidia_smi_available'] = true;
                $mlData['gpu_local']['summary'] = trim($smi);
            }
        }
        $ctx = stream_context_create(['http' => ['timeout' => 3], 'ssl' => ['verify_peer' => false]]);
        $tagsRaw = @file_get_contents('http://127.0.0.1:11434/api/tags', false, $ctx);
        if ($tagsRaw) {
            $tj = json_decode($tagsRaw, true);
            foreach ($tj['models'] ?? [] as $m) {
                $mlData['models'][] = [
                    'name' => (string)($m['name'] ?? ''),
                    'size' => (int)($m['size'] ?? 0),
                    'modified_at' => (string)($m['modified_at'] ?? ''),
                ];
            }
        }
        $csVer = trim((string)@shell_exec('/home/root/.local/bin/code-server --version 2>/dev/null | head -1'));
        if ($csVer !== '') {
            $mlData['code_server_line'] = $csVer;
        }
    }

    // Build response
    $response = ['timestamp' => date('Y-m-d H:i:s')];
    if (isset($serverData)) $response['server'] = $serverData;
    if (isset($pm2Data)) $response['pm2'] = $pm2Data;
    if (isset($endpointData)) $response['endpoints'] = $endpointData;
    if (isset($dbStats)) $response['database'] = $dbStats;
    if (isset($vaultData)) $response['vault'] = $vaultData;
    if (isset($mlData)) $response['ml_stack'] = $mlData;

    echo json_encode($response);
    exit;
}

// ── INITIAL PAGE DATA ────────────────────────────────────────────────
// Minimal — JS will fetch via AJAX on load
$hostname = gethostname();
$serverIp = '15.235.50.60';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring — GoSiteMe War Room</title>
    <meta name="robots" content="noindex, nofollow">
    <style>
        :root {
            --bg: #0a0e14;
            --surface: #131920;
            --surface2: #1a2230;
            --border: #1e2a3a;
            --text: #e0e6ed;
            --text2: #8899aa;
            --accent: #00d4ff;
            --accent2: #7b61ff;
            --green: #00e676;
            --yellow: #ffd600;
            --orange: #ff9100;
            --red: #ff1744;
            --gold: #ffd700;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            background: var(--bg); color: var(--text); font-family: 'SF Mono', 'Fira Code', 'Cascadia Code', monospace;
            min-height: 100vh;
        }
        a { color: var(--accent); text-decoration: none; }
        a:hover { text-decoration: underline; }

        /* Header */
        .header {
            background: linear-gradient(135deg, #0d1117 0%, #161b22 100%);
            border-bottom: 1px solid var(--border);
            padding: 16px 24px;
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 100;
        }
        .header h1 {
            font-size: 18px; font-weight: 700; color: var(--accent);
            display: flex; align-items: center; gap: 10px;
        }
        .header h1 .dot {
            width: 10px; height: 10px; border-radius: 50%; background: var(--green);
            animation: pulse-dot 2s infinite;
        }
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; box-shadow: 0 0 4px var(--green); }
            50% { opacity: 0.5; box-shadow: 0 0 12px var(--green); }
        }
        .header-meta { display: flex; gap: 16px; align-items: center; font-size: 12px; color: var(--text2); }
        .header-meta .refresh-badge {
            padding: 3px 8px; border-radius: 4px; background: var(--surface2); cursor: pointer;
            transition: background 0.2s;
        }
        .header-meta .refresh-badge:hover { background: var(--accent); color: #000; }

        /* Layout */
        .dashboard { padding: 20px 24px; max-width: 1600px; margin: 0 auto; }

        /* Top stat cards */
        .stat-row {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px; margin-bottom: 20px;
        }
        .stat-card {
            background: var(--surface); border: 1px solid var(--border); border-radius: 8px;
            padding: 14px 16px; position: relative; overflow: hidden;
        }
        .stat-card .label { font-size: 11px; color: var(--text2); text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-card .value { font-size: 26px; font-weight: 700; margin-top: 4px; }
        .stat-card .sub { font-size: 11px; color: var(--text2); margin-top: 2px; }
        .stat-card::after {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px;
        }
        .stat-card.green::after { background: var(--green); }
        .stat-card.yellow::after { background: var(--yellow); }
        .stat-card.red::after { background: var(--red); }
        .stat-card.accent::after { background: var(--accent); }
        .stat-card.gold::after { background: var(--gold); }
        .stat-card.purple::after { background: var(--accent2); }

        /* Sections */
        .section {
            background: var(--surface); border: 1px solid var(--border); border-radius: 8px;
            margin-bottom: 16px; overflow: hidden;
        }
        .section-header {
            padding: 12px 16px; border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
            font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;
        }
        .section-header .count { 
            background: var(--surface2); padding: 2px 8px; border-radius: 10px; font-size: 11px; color: var(--text2);
        }
        .section-body { padding: 0; }

        /* Progress bars */
        .progress-bar {
            height: 6px; background: var(--surface2); border-radius: 3px; overflow: hidden; margin-top: 6px;
        }
        .progress-fill {
            height: 100%; border-radius: 3px; transition: width 0.6s ease;
        }
        .progress-fill.green { background: var(--green); }
        .progress-fill.yellow { background: var(--yellow); }
        .progress-fill.orange { background: var(--orange); }
        .progress-fill.red { background: var(--red); }

        /* Service grid */
        .svc-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1px; background: var(--border);
        }
        .svc-item {
            background: var(--surface); padding: 10px 14px;
            display: flex; align-items: center; gap: 10px;
            transition: background 0.15s;
        }
        .svc-item:hover { background: var(--surface2); }
        .svc-dot {
            width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0;
        }
        .svc-dot.online { background: var(--green); box-shadow: 0 0 4px var(--green); }
        .svc-dot.stopped { background: var(--yellow); }
        .svc-dot.errored { background: var(--red); box-shadow: 0 0 4px var(--red); }
        .svc-dot.unknown { background: var(--text2); }
        .svc-info { flex: 1; min-width: 0; }
        .svc-name { font-size: 12px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .svc-meta { font-size: 10px; color: var(--text2); display: flex; gap: 8px; margin-top: 2px; }
        .svc-mem { font-size: 11px; color: var(--text2); text-align: right; white-space: nowrap; }

        /* Endpoint checks */
        .ep-list { }
        .ep-item {
            padding: 10px 16px; border-bottom: 1px solid var(--border);
            display: flex; align-items: center; gap: 12px;
        }
        .ep-item:last-child { border-bottom: none; }
        .ep-status {
            width: 28px; height: 28px; border-radius: 6px;
            display: flex; align-items: center; justify-content: center;
            font-size: 14px; flex-shrink: 0;
        }
        .ep-status.healthy { background: rgba(0,230,118,0.15); color: var(--green); }
        .ep-status.degraded { background: rgba(255,214,0,0.15); color: var(--yellow); }
        .ep-status.down { background: rgba(255,23,68,0.15); color: var(--red); }
        .ep-status.unknown { background: rgba(136,153,170,0.15); color: var(--text2); }
        .ep-info { flex: 1; }
        .ep-name { font-size: 12px; font-weight: 600; }
        .ep-detail { font-size: 10px; color: var(--text2); margin-top: 1px; }
        .ep-latency { font-size: 11px; color: var(--text2); text-align: right; }
        .ep-latency.fast { color: var(--green); }
        .ep-latency.medium { color: var(--yellow); }
        .ep-latency.slow { color: var(--red); }

        /* Vault section */
        .vault-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1px; background: var(--border);
        }
        .vault-item {
            background: var(--surface); padding: 10px 14px;
            display: flex; align-items: center; gap: 8px;
        }
        .vault-check { font-size: 14px; }

        /* DB stats grid */
        .db-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1px; background: var(--border);
        }
        .db-item {
            background: var(--surface); padding: 12px 14px;
        }
        .db-label { font-size: 10px; color: var(--text2); text-transform: uppercase; }
        .db-value { font-size: 18px; font-weight: 700; margin-top: 2px; }

        /* Resource bars */
        .resource-row {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 12px; margin-bottom: 20px;
        }
        .resource-card {
            background: var(--surface); border: 1px solid var(--border); border-radius: 8px;
            padding: 16px;
        }
        .resource-label {
            display: flex; justify-content: space-between; align-items: center;
            font-size: 12px; margin-bottom: 4px;
        }
        .resource-label .pct { font-weight: 700; }

        /* Two column layout for bottom sections */
        .two-col {
            display: grid; grid-template-columns: 1fr 1fr; gap: 16px;
        }
        @media (max-width: 900px) {
            .two-col { grid-template-columns: 1fr; }
            .svc-grid { grid-template-columns: 1fr; }
            .ml-top-grid { grid-template-columns: 1fr !important; }
        }

        /* Loading states */
        .loading { color: var(--text2); text-align: center; padding: 40px; font-size: 13px; }
        .loading::after {
            content: ''; display: inline-block; width: 16px; height: 16px;
            border: 2px solid var(--text2); border-top-color: var(--accent);
            border-radius: 50%; animation: spin 0.8s linear infinite;
            margin-left: 8px; vertical-align: middle;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Alert banner */
        .alert-banner {
            background: rgba(255,23,68,0.1); border: 1px solid rgba(255,23,68,0.3);
            border-radius: 8px; padding: 12px 16px; margin-bottom: 16px;
            display: none; font-size: 13px; color: var(--red);
        }
        .alert-banner.visible { display: flex; align-items: center; gap: 10px; }

        /* Nav */
        .back-link { font-size: 12px; color: var(--text2); }
    </style>
</head>
<body>

<div class="header">
    <h1>
        <span class="dot" id="pulse-dot"></span>
        WAR ROOM — GoSiteMe Monitoring
    </h1>
    <div class="header-meta">
        <span id="server-time">—</span>
        <span>Uptime: <strong id="uptime-display">—</strong></span>
        <span class="refresh-badge" onclick="refreshAll()" title="Refresh Now">⟳ Refresh</span>
        <span class="refresh-badge" id="auto-toggle" onclick="toggleAuto()" title="Toggle auto-refresh">Auto: ON</span>
        <a href="/commander.php" class="back-link">← Commander HQ</a>
    </div>
</div>

<div class="dashboard">

    <!-- Alert banner for down services -->
    <div class="alert-banner" id="alert-banner">
        <span>⚠</span>
        <span id="alert-text"></span>
    </div>

    <!-- Top stat cards -->
    <div class="stat-row" id="stat-row">
        <div class="stat-card green" id="card-pm2">
            <div class="label">PM2 Services</div>
            <div class="value" id="pm2-count">—</div>
            <div class="sub" id="pm2-sub">Loading...</div>
        </div>
        <div class="stat-card accent" id="card-memory">
            <div class="label">Memory</div>
            <div class="value" id="mem-pct">—</div>
            <div class="sub" id="mem-sub">Loading...</div>
        </div>
        <div class="stat-card accent" id="card-disk">
            <div class="label">Disk</div>
            <div class="value" id="disk-pct">—</div>
            <div class="sub" id="disk-sub">Loading...</div>
        </div>
        <div class="stat-card purple" id="card-load">
            <div class="label">CPU Load</div>
            <div class="value" id="load-val">—</div>
            <div class="sub" id="load-sub">Loading...</div>
        </div>
        <div class="stat-card gold" id="card-conns">
            <div class="label">TCP Connections</div>
            <div class="value" id="tcp-val">—</div>
            <div class="sub" id="tcp-sub">Active</div>
        </div>
        <div class="stat-card green" id="card-endpoints">
            <div class="label">Endpoints</div>
            <div class="value" id="ep-count">—</div>
            <div class="sub" id="ep-sub">Checked</div>
        </div>
        <div class="stat-card accent" id="card-sessions">
            <div class="label">IDE Sessions</div>
            <div class="value" id="sessions-val">—</div>
            <div class="sub" id="sessions-sub">Active</div>
        </div>
        <div class="stat-card gold" id="card-chat">
            <div class="label">Chat Today</div>
            <div class="value" id="chat-val">—</div>
            <div class="sub" id="chat-sub">Messages</div>
        </div>
    </div>

    <!-- Resource bars -->
    <div class="resource-row">
        <div class="resource-card">
            <div class="resource-label">
                <span>Memory</span>
                <span class="pct" id="mem-bar-pct">—</span>
            </div>
            <div class="progress-bar"><div class="progress-fill green" id="mem-bar" style="width:0%"></div></div>
            <div style="display:flex;justify-content:space-between;margin-top:4px;font-size:10px;color:var(--text2)">
                <span id="mem-bar-used">—</span>
                <span id="mem-bar-total">—</span>
            </div>
        </div>
        <div class="resource-card">
            <div class="resource-label">
                <span>Disk</span>
                <span class="pct" id="disk-bar-pct">—</span>
            </div>
            <div class="progress-bar"><div class="progress-fill green" id="disk-bar" style="width:0%"></div></div>
            <div style="display:flex;justify-content:space-between;margin-top:4px;font-size:10px;color:var(--text2)">
                <span id="disk-bar-used">—</span>
                <span id="disk-bar-total">—</span>
            </div>
        </div>
        <div class="resource-card">
            <div class="resource-label">
                <span>Swap</span>
                <span class="pct" id="swap-bar-pct">—</span>
            </div>
            <div class="progress-bar"><div class="progress-fill green" id="swap-bar" style="width:0%"></div></div>
            <div style="display:flex;justify-content:space-between;margin-top:4px;font-size:10px;color:var(--text2)">
                <span id="swap-bar-used">—</span>
                <span id="swap-bar-total">—</span>
            </div>
        </div>
    </div>

    <!-- PM2 Fleet -->
    <div class="section">
        <div class="section-header">
            <span>PM2 Service Fleet</span>
            <span class="count" id="pm2-fleet-count">—</span>
        </div>
        <div class="section-body">
            <div class="svc-grid" id="svc-grid">
                <div class="loading">Loading fleet status</div>
            </div>
        </div>
    </div>

    <div class="two-col">
        <!-- Endpoint Health Checks -->
        <div class="section">
            <div class="section-header">
                <span>Endpoint Health Checks</span>
                <span class="count" id="ep-check-count">—</span>
            </div>
            <div class="section-body">
                <div class="ep-list" id="ep-list">
                    <div class="loading">Checking endpoints</div>
                </div>
            </div>
        </div>

        <!-- Right column: Vault + DB Stats -->
        <div>
            <!-- Vault Status -->
            <div class="section" style="margin-bottom:16px">
                <div class="section-header">
                    <span>Vault Status</span>
                </div>
                <div class="section-body">
                    <div class="vault-grid" id="vault-grid">
                        <div class="loading">Checking vault</div>
                    </div>
                </div>
            </div>

            <!-- Database Stats -->
            <div class="section">
                <div class="section-header">
                    <span>Database / Platform</span>
                </div>
                <div class="section-body">
                    <div class="db-grid" id="db-grid">
                        <div class="loading">Loading stats</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ML / GPU ops (open models + burst strategy) -->
    <div class="section" id="ml-section">
        <div class="section-header">
            <span>ML / GPU Ops — Open models &amp; burst runway</span>
            <span class="count" id="ml-model-count">—</span>
        </div>
        <div class="section-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:14px" class="ml-top-grid">
                <div style="background:var(--surface2);border:1px solid var(--border);border-radius:8px;padding:14px;font-size:12px;line-height:1.6;color:var(--text2)">
                    <div style="color:var(--accent);font-weight:700;margin-bottom:8px">Local inference</div>
                    <div id="ml-ollama-ver">—</div>
                    <div id="ml-codeserver" style="margin-top:6px">—</div>
                    <div id="ml-gpu-local" style="margin-top:10px;color:var(--gold)">—</div>
                </div>
                <div style="background:var(--surface2);border:1px solid var(--border);border-radius:8px;padding:14px;font-size:12px;line-height:1.6;color:var(--text2)">
                    <div style="color:var(--accent2);font-weight:700;margin-bottom:8px">OSS vs Composer 2</div>
                    <div id="ml-oss-note">—</div>
                    <div style="margin-top:10px;font-size:11px;color:var(--text2)">Burst GPU: provision pod → run job → destroy. Dashboard stays truthy; workers live in vault + PM2/cron.</div>
                </div>
            </div>
            <div style="font-size:11px;color:var(--text2);margin-bottom:8px;text-transform:uppercase">Ollama models on this machine</div>
            <div class="svc-grid" id="ml-model-grid"><div class="loading">Loading Ollama</div></div>
            <div style="margin-top:14px;font-size:11px;color:var(--text2);text-transform:uppercase">Burst playbook</div>
            <ul id="ml-playbook" style="margin:8px 0 0 18px;font-size:12px;color:var(--text2);line-height:1.7"></ul>
        </div>
    </div>

</div>

<script>
let autoRefreshEnabled = true;
let refreshTimer = null;
const REFRESH_INTERVAL = 30000; // 30 seconds

function fmt(bytes) {
    if (bytes >= 1073741824) return (bytes / 1073741824).toFixed(1) + ' GB';
    if (bytes >= 1048576) return (bytes / 1048576).toFixed(0) + ' MB';
    if (bytes >= 1024) return (bytes / 1024).toFixed(0) + ' KB';
    return bytes + ' B';
}

function barColor(pct) {
    if (pct < 60) return 'green';
    if (pct < 80) return 'yellow';
    if (pct < 90) return 'orange';
    return 'red';
}

function cardColor(el, pct) {
    el.className = el.className.replace(/\b(green|yellow|red|accent|gold|purple)\b/g, '').trim();
    if (pct < 60) el.classList.add('green');
    else if (pct < 80) el.classList.add('yellow');
    else el.classList.add('red');
}

function timeSince(ms) {
    const s = Math.floor((Date.now() - ms) / 1000);
    if (s < 60) return s + 's';
    if (s < 3600) return Math.floor(s / 60) + 'm';
    if (s < 86400) return Math.floor(s / 3600) + 'h';
    return Math.floor(s / 86400) + 'd';
}

function updateServer(d) {
    // Memory
    document.getElementById('mem-pct').textContent = d.memory.percent + '%';
    document.getElementById('mem-sub').textContent = fmt(d.memory.used) + ' / ' + fmt(d.memory.total);
    document.getElementById('mem-bar').style.width = d.memory.percent + '%';
    document.getElementById('mem-bar').className = 'progress-fill ' + barColor(d.memory.percent);
    document.getElementById('mem-bar-pct').textContent = d.memory.percent + '%';
    document.getElementById('mem-bar-used').textContent = fmt(d.memory.used) + ' used';
    document.getElementById('mem-bar-total').textContent = fmt(d.memory.total) + ' total';
    cardColor(document.getElementById('card-memory'), d.memory.percent);

    // Disk
    document.getElementById('disk-pct').textContent = d.disk.percent + '%';
    document.getElementById('disk-sub').textContent = fmt(d.disk.used) + ' / ' + fmt(d.disk.total);
    document.getElementById('disk-bar').style.width = d.disk.percent + '%';
    document.getElementById('disk-bar').className = 'progress-fill ' + barColor(d.disk.percent);
    document.getElementById('disk-bar-pct').textContent = d.disk.percent + '%';
    document.getElementById('disk-bar-used').textContent = fmt(d.disk.used) + ' used';
    document.getElementById('disk-bar-total').textContent = fmt(d.disk.total) + ' total';
    cardColor(document.getElementById('card-disk'), d.disk.percent);

    // Swap
    document.getElementById('swap-bar').style.width = d.swap.percent + '%';
    document.getElementById('swap-bar').className = 'progress-fill ' + barColor(d.swap.percent);
    document.getElementById('swap-bar-pct').textContent = d.swap.percent + '%';
    document.getElementById('swap-bar-used').textContent = fmt(d.swap.used) + ' used';
    document.getElementById('swap-bar-total').textContent = fmt(d.swap.total) + ' total';

    // Load
    const loadPct = Math.round((d.load[0] / d.cpu.cores) * 100);
    document.getElementById('load-val').textContent = d.load[0].toFixed(2);
    document.getElementById('load-sub').textContent = d.cpu.cores + ' cores — ' + d.load.map(l => l.toFixed(1)).join(' / ');
    cardColor(document.getElementById('card-load'), loadPct);

    // TCP
    document.getElementById('tcp-val').textContent = d.tcp_connections;

    // Uptime + time
    document.getElementById('uptime-display').textContent = d.uptime;
    document.getElementById('server-time').textContent = d.timestamp;
}

function updatePM2(d) {
    document.getElementById('pm2-count').textContent = d.online + '/' + d.total;
    const issues = [];
    if (d.stopped > 0) issues.push(d.stopped + ' stopped');
    if (d.errored > 0) issues.push(d.errored + ' errored');
    document.getElementById('pm2-sub').textContent = issues.length ? issues.join(', ') : 'All online';
    document.getElementById('pm2-fleet-count').textContent = d.total + ' services — ' + fmt(d.total_memory) + ' total';

    const card = document.getElementById('card-pm2');
    card.className = card.className.replace(/\b(green|yellow|red)\b/g, '').trim();
    card.classList.add(d.errored > 0 ? 'red' : d.stopped > 0 ? 'yellow' : 'green');

    // Alert banner
    const alertBanner = document.getElementById('alert-banner');
    if (d.stopped > 0 || d.errored > 0) {
        const downNames = d.services.filter(s => s.status !== 'online').map(s => s.name);
        document.getElementById('alert-text').textContent = 'Services not online: ' + downNames.join(', ');
        alertBanner.classList.add('visible');
    } else {
        alertBanner.classList.remove('visible');
    }

    // Service grid
    const grid = document.getElementById('svc-grid');
    grid.innerHTML = d.services.map(s => `
        <div class="svc-item">
            <div class="svc-dot ${s.status}"></div>
            <div class="svc-info">
                <div class="svc-name">${s.name}</div>
                <div class="svc-meta">
                    <span>id:${s.id}</span>
                    ${s.port ? '<span>:' + s.port + '</span>' : ''}
                    <span>↻${s.restarts}</span>
                    <span>up ${timeSince(s.uptime)}</span>
                </div>
            </div>
            <div class="svc-mem">
                ${s.cpu > 0 ? s.cpu + '% · ' : ''}${fmt(s.memory)}
            </div>
        </div>
    `).join('');
}

function updateEndpoints(endpoints) {
    const healthy = endpoints.filter(e => e.status === 'healthy').length;
    const total = endpoints.length;
    document.getElementById('ep-count').textContent = healthy + '/' + total;
    document.getElementById('ep-sub').textContent = healthy === total ? 'All healthy' : (total - healthy) + ' issues';
    document.getElementById('ep-check-count').textContent = healthy + '/' + total + ' healthy';

    const epCard = document.getElementById('card-endpoints');
    epCard.className = epCard.className.replace(/\b(green|yellow|red)\b/g, '').trim();
    epCard.classList.add(healthy === total ? 'green' : healthy > total * 0.7 ? 'yellow' : 'red');

    const list = document.getElementById('ep-list');
    list.innerHTML = endpoints.map(ep => {
        const icon = ep.status === 'healthy' ? '✓' : ep.status === 'degraded' ? '!' : '✕';
        const latClass = !ep.latency ? '' : ep.latency < 50 ? 'fast' : ep.latency < 200 ? 'medium' : 'slow';
        return `
        <div class="ep-item">
            <div class="ep-status ${ep.status}">${icon}</div>
            <div class="ep-info">
                <div class="ep-name">${ep.name}</div>
                <div class="ep-detail">:${ep.port} ${ep.detail ? '— ' + ep.detail : ''}</div>
            </div>
            <div class="ep-latency ${latClass}">${ep.latency ? ep.latency + 'ms' : '—'}</div>
        </div>`;
    }).join('');
}

function updateVault(v) {
    const items = [
        ['Vault Key in RAM', v.key_in_ram],
        ['Master Key on Disk', v.master_on_disk],
        ['Keys Unified', v.keys_match],
        ['SSH Credentials', v.ssh_creds],
        ['Main Vault', v.main_vault],
        ['DB Credentials', v.credential_count + ' entries'],
    ];
    document.getElementById('vault-grid').innerHTML = items.map(([label, val]) => {
        const ok = val === true || (typeof val === 'string' && val.includes('entr'));
        return `<div class="vault-item">
            <span class="vault-check">${ok ? '🟢' : '🔴'}</span>
            <span style="font-size:12px">${label}</span>
            ${typeof val === 'string' ? '<span style="font-size:11px;color:var(--text2);margin-left:auto">' + val + '</span>' : ''}
        </div>`;
    }).join('');
}

function updateML(m) {
    if (!m) return;
    document.getElementById('ml-ollama-ver').textContent = m.ollama_version || 'Ollama version unknown';
    document.getElementById('ml-codeserver').textContent = m.code_server_line ? ('Alfred IDE / code-server: ' + m.code_server_line) : '';
    const g = m.gpu_local || {};
    document.getElementById('ml-gpu-local').textContent = g.nvidia_smi_available
        ? ('GPU: ' + g.summary)
        : ('GPU: ' + (g.summary || 'CPU-only'));
    document.getElementById('ml-oss-note').textContent = m.oss_vs_composer || '';
    const models = m.models || [];
    document.getElementById('ml-model-count').textContent = models.length + ' models';
    const grid = document.getElementById('ml-model-grid');
    if (!models.length) {
        grid.innerHTML = '<div style="padding:16px;color:var(--text2)">No models reported — check Ollama on :11434</div>';
    } else {
        grid.innerHTML = models.map(x => `
            <div class="svc-item">
                <div class="svc-dot online"></div>
                <div class="svc-info">
                    <div class="svc-name">${x.name}</div>
                    <div class="svc-meta"><span>${x.modified_at ? x.modified_at : '—'}</span></div>
                </div>
                <div class="svc-mem">${fmt(x.size)}</div>
            </div>`).join('');
    }
    const ul = document.getElementById('ml-playbook');
    const steps = m.burst_gpu_playbook || [];
    ul.innerHTML = steps.map(s => '<li>' + s + '</li>').join('');
}

function updateDB(d) {
    if (d.error) {
        document.getElementById('db-grid').innerHTML = '<div style="padding:16px;color:var(--red)">Error: ' + d.error + '</div>';
        return;
    }
    const items = [
        ['IDE Sessions', d.active_ide_sessions],
        ['IDE Users', d.total_ide_users],
        ['Chat Today', d.chat_messages_today],
        ['Chat Sessions', d.total_chat_sessions],
        ['Active Clients', d.active_clients],
        ['Active Domains', d.active_domains],
        ['Military Personnel', d.military_personnel],
        ['GSM Supply', d.gsm_total_supply?.toLocaleString() || '0'],
        ['Unpaid Invoices', d.unpaid_invoices],
        ['Unpaid Total', '$' + (d.unpaid_total || 0).toFixed(2)],
    ];

    document.getElementById('db-grid').innerHTML = items.map(([label, val]) => `
        <div class="db-item">
            <div class="db-label">${label}</div>
            <div class="db-value">${val}</div>
        </div>
    `).join('');

    document.getElementById('sessions-val').textContent = d.active_ide_sessions;
    document.getElementById('chat-val').textContent = d.chat_messages_today;
}

async function refreshAll() {
    try {
        const resp = await fetch('/monitoring.php?api=all', { credentials: 'same-origin' });
        const data = await resp.json();
        if (data.server) updateServer(data.server);
        if (data.pm2) updatePM2(data.pm2);
        if (data.endpoints) updateEndpoints(data.endpoints);
        if (data.vault) updateVault(data.vault);
        if (data.database) updateDB(data.database);
        if (data.ml_stack) updateML(data.ml_stack);

        const dot = document.getElementById('pulse-dot');
        dot.style.background = 'var(--accent)';
        setTimeout(() => dot.style.background = 'var(--green)', 300);
    } catch (err) {
        console.error('Refresh failed:', err);
        document.getElementById('pulse-dot').style.background = 'var(--red)';
    }
}

function toggleAuto() {
    autoRefreshEnabled = !autoRefreshEnabled;
    document.getElementById('auto-toggle').textContent = 'Auto: ' + (autoRefreshEnabled ? 'ON' : 'OFF');
    if (autoRefreshEnabled) {
        scheduleRefresh();
    } else if (refreshTimer) {
        clearTimeout(refreshTimer);
        refreshTimer = null;
    }
}

function scheduleRefresh() {
    if (refreshTimer) clearTimeout(refreshTimer);
    refreshTimer = setTimeout(async () => {
        if (autoRefreshEnabled) {
            await refreshAll();
            scheduleRefresh();
        }
    }, REFRESH_INTERVAL);
}

// Initial load
refreshAll();
scheduleRefresh();
</script>

</body>
</html>
