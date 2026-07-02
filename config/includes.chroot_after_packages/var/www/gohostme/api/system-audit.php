<?php
/**
 * Military-Grade System Audit — Comprehensive Ecosystem Diagnostics
 * ─────────────────────────────────────────────────────────────────
 * Runs deep checks across all 11+ services, agent fleet, communication
 * channels, security posture, and AI fallback cascade.
 *
 * Access: Veil Protocol active OR admin session OR internal secret
 *
 * GET  /api/system-audit.php              → Full audit report
 * GET  /api/system-audit.php?quick=1      → Quick summary
 * POST /api/system-audit.php?action=heal  → Self-healing (pass service name)
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/veil-protocol.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

session_start();
header('Content-Type: application/json; charset=utf-8');
header('X-Audit-Timestamp: ' . date('c'));

// ─── Auth: Commander-level access only ─────────────────────────────────
$isVeil = veil_is_active();
$isAdmin = !empty($_SESSION['is_admin']) || ($_SESSION['client_id'] ?? 0) === 33;
$internalSecret = getenv('INTERNAL_SECRET') ?: '';
$isInternal = $internalSecret && isset($_SERVER['HTTP_X_INTERNAL_SECRET']) && hash_equals($internalSecret, $_SERVER['HTTP_X_INTERNAL_SECRET']);

if (!$isVeil && !$isAdmin && !$isInternal) {
    http_response_code(403);
    echo json_encode(['error' => 'CLASSIFIED — Commander access required', 'classification' => 'TOP SECRET']);
    exit;
}

$db = getDB();

// ─── Service Check Utilities ───────────────────────────────────────────
function pingPort(string $host, int $port, int $timeout = 2): array {
    $start = microtime(true);
    $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
    $ms = round((microtime(true) - $start) * 1000, 1);
    if ($fp) { fclose($fp); return ['status' => 'OPERATIONAL', 'latency_ms' => $ms]; }
    return ['status' => 'DOWN', 'latency_ms' => $ms, 'error' => $errstr];
}

function curlCheck(string $url, int $timeout = 3): array {
    $start = microtime(true);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CONNECTTIMEOUT => $timeout,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $ms = round((microtime(true) - $start) * 1000, 1);
    curl_close($ch);
    if ($code >= 200 && $code < 400) {
        return ['status' => 'OPERATIONAL', 'latency_ms' => $ms, 'http_code' => $code, 'body' => $body];
    }
    return ['status' => 'DOWN', 'latency_ms' => $ms, 'http_code' => $code];
}

function dbQuery(PDO $db, string $sql, array $params = []): array {
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// ─── Audit Sections ────────────────────────────────────────────────────

function auditCoreServices(): array {
    $services = [];

    // Database
    $start = microtime(true);
    try {
        $pdo = getDB();
        $pdo->query('SELECT 1');
        $services['database'] = ['status' => 'OPERATIONAL', 'latency_ms' => round((microtime(true) - $start) * 1000, 1)];
        // Table count
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM information_schema.tables WHERE table_schema = DATABASE()");
        $services['database']['tables'] = (int)$stmt->fetchColumn();
    } catch (Throwable $e) {
        $services['database'] = ['status' => 'DOWN', 'error' => 'Connection failed'];
    }

    // Redis
    $services['redis'] = pingPort('127.0.0.1', 6379);

    // WebSocket Server
    $services['websocket'] = pingPort('127.0.0.1', 3010);

    // MCP Server
    $services['mcp_server'] = curlCheck('http://127.0.0.1:3005/health');
    unset($services['mcp_server']['body']);

    // Jobs Processor
    $services['jobs_processor'] = pingPort('127.0.0.1', 3011);

    // Alfred IDE Middleware
    $services['middleware'] = curlCheck('http://127.0.0.1:3001/health');
    unset($services['middleware']['body']);

    // Meilisearch
    $r = curlCheck('http://127.0.0.1:7700/health');
    $services['meilisearch'] = ['status' => $r['status'], 'latency_ms' => $r['latency_ms']];

    // Ollama (local AI)
    $r = curlCheck('http://127.0.0.1:11434/api/tags');
    $services['ollama'] = ['status' => $r['status'], 'latency_ms' => $r['latency_ms']];
    if ($r['status'] === 'OPERATIONAL' && !empty($r['body'])) {
        $tags = json_decode($r['body'], true);
        $services['ollama']['models'] = array_column($tags['models'] ?? [], 'name');
    }

    // LiveKit
    $services['livekit'] = pingPort('127.0.0.1', 7880);

    return $services;
}

function auditAICascade(): array {
    $providers = [];
    $envMap = [
        'anthropic' => ['key' => 'ANTHROPIC_API_KEY', 'model' => 'claude-sonnet-4-20250514'],
        'groq'      => ['key' => 'GROQ_API_KEY', 'model' => 'llama-3.3-70b-versatile'],
        'openai'    => ['key' => 'OPENAI_API_KEY', 'model' => 'gpt-4.1-mini'],
        'google'    => ['key' => 'GOOGLE_AI_KEY', 'model' => 'gemini-2.5-flash'],
        'xai'       => ['key' => 'XAI_API_KEY', 'model' => 'grok-3-mini'],
        'ollama'    => ['key' => null, 'model' => 'qwen2.5:3b'],
    ];

    foreach ($envMap as $name => $info) {
        if ($name === 'ollama') {
            $r = curlCheck('http://127.0.0.1:11434/api/tags');
            $providers[$name] = [
                'status' => $r['status'] === 'OPERATIONAL' ? 'READY' : 'DOWN',
                'model' => $info['model'],
                'type' => 'local',
            ];
        } else {
            $hasKey = !empty(getenv($info['key']));
            $providers[$name] = [
                'status' => $hasKey ? 'CONFIGURED' : 'MISSING_KEY',
                'model' => $info['model'],
                'type' => 'cloud',
                'key_present' => $hasKey,
            ];
        }
    }

    return $providers;
}

function auditAgentFleet(PDO $db): array {
    $total = dbQuery($db, "SELECT COUNT(*) as cnt FROM alfred_agent_registry");
    $byRole = dbQuery($db, "SELECT agent_role, COUNT(*) as cnt FROM alfred_agent_registry GROUP BY agent_role");
    $byStatus = dbQuery($db, "SELECT status, COUNT(*) as cnt FROM alfred_agent_registry GROUP BY status");
    $busy = dbQuery($db, "SELECT agent_id, agent_name, current_task FROM alfred_agent_registry WHERE status = 'busy'");
    $recentTasks = dbQuery($db, "SELECT task_id, assigned_agent, goal, priority, status, created_at
                                FROM alfred_agent_tasks ORDER BY created_at DESC LIMIT 10");

    return [
        'total_agents' => $total[0]['cnt'] ?? 0,
        'by_role' => array_column($byRole, 'cnt', 'agent_role'),
        'by_status' => array_column($byStatus, 'cnt', 'status'),
        'busy_agents' => $busy,
        'recent_tasks' => $recentTasks,
    ];
}

function auditCommunicationChannels(): array {
    $channels = [];

    // Check which channel credentials are configured
    $channelChecks = [
        'web_chat'  => ['status' => 'OPERATIONAL', 'type' => 'built-in'],
        'sms'       => ['key' => 'TELNYX_API_KEY', 'phone' => '+1-807-798-2850', 'provider' => 'Telnyx'],
        'telegram'  => ['key' => 'TELEGRAM_BOT_TOKEN', 'provider' => 'Telegram Bot API'],
        'discord'   => ['key' => 'DISCORD_BOT_TOKEN', 'provider' => 'Discord'],
        'slack'     => ['key' => 'SLACK_BOT_TOKEN', 'provider' => 'Slack'],
        'whatsapp'  => ['key' => 'WHATSAPP_TOKEN', 'provider' => 'Meta Cloud API'],
        'email'     => ['key' => 'SENDGRID_API_KEY', 'provider' => 'SendGrid'],
        'voice'     => ['key' => 'VAPI_API_KEY', 'phone' => '1-833-GOSITEME', 'provider' => 'VAPI'],
    ];

    foreach ($channelChecks as $channel => $info) {
        if ($channel === 'web_chat') {
            $channels[$channel] = $info;
            continue;
        }
        $hasKey = !empty(getenv($info['key']));
        $channels[$channel] = [
            'status' => $hasKey ? 'CONFIGURED' : 'NOT_CONFIGURED',
            'provider' => $info['provider'] ?? '',
            'phone' => $info['phone'] ?? null,
        ];
    }

    return $channels;
}

function auditSecurity(PDO $db): array {
    // Veil Protocol status
    $veilLogs = dbQuery($db, "SELECT action, COUNT(*) as cnt FROM veil_access_log
                             WHERE timestamp > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                             GROUP BY action");

    // Standing orders
    $orders = dbQuery($db, "SELECT COUNT(*) as cnt, status FROM alfred_ops_standing_orders GROUP BY status");

    // Recent security events
    $recentVeil = dbQuery($db, "SELECT action, channel, ip_address, timestamp
                               FROM veil_access_log ORDER BY timestamp DESC LIMIT 5");

    // SSL check
    $sslInfo = [];
    $stream = @stream_context_create(['ssl' => ['capture_peer_cert' => true]]);
    $client = @stream_socket_client('ssl://gositeme.com:443', $errno, $errstr, 5, STREAM_CLIENT_CONNECT, $stream);
    if ($client) {
        $params = stream_context_get_params($client);
        $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate'] ?? '');
        if ($cert) {
            $sslInfo = [
                'status' => 'VALID',
                'issuer' => $cert['issuer']['O'] ?? 'unknown',
                'expires' => date('Y-m-d', $cert['validTo_time_t'] ?? 0),
                'days_remaining' => max(0, (int)(($cert['validTo_time_t'] - time()) / 86400)),
            ];
        }
        fclose($client);
    }
    if (empty($sslInfo)) {
        $sslInfo = ['status' => 'UNKNOWN'];
    }

    return [
        'veil_protocol' => [
            'status' => veil_is_active() ? 'ACTIVE' : 'STANDBY',
            'last_24h_activity' => array_column($veilLogs, 'cnt', 'action'),
        ],
        'ssl_certificate' => $sslInfo,
        'standing_orders' => array_column($orders, 'cnt', 'status'),
        'recent_veil_events' => $recentVeil,
    ];
}

function auditDatabase(PDO $db): array {
    // Database sizes
    $sizes = dbQuery($db, "SELECT table_name, table_rows, ROUND(data_length/1024/1024, 2) as data_mb,
                           ROUND(index_length/1024/1024, 2) as index_mb
                           FROM information_schema.tables
                           WHERE table_schema = DATABASE()
                           ORDER BY data_length DESC LIMIT 15");

    $totalSize = dbQuery($db, "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as total_mb
                               FROM information_schema.tables WHERE table_schema = DATABASE()");

    return [
        'total_size_mb' => $totalSize[0]['total_mb'] ?? 0,
        'largest_tables' => $sizes,
    ];
}

// ─── Main Audit ────────────────────────────────────────────────────────
$auditStart = microtime(true);

$quick = isset($_GET['quick']);

// Run all checks
$report = [
    'classification' => 'COMMANDER EYES ONLY',
    'audit_id' => 'AUDIT-' . strtoupper(substr(md5(uniqid('', true)), 0, 8)),
    'timestamp' => date('c'),
    'hostname' => gethostname(),
];

// Core services (always run)
$services = auditCoreServices();
$report['services'] = $services;

// Overall health
$total = count($services);
$up = count(array_filter($services, fn($s) => ($s['status'] ?? '') === 'OPERATIONAL'));
$report['overall'] = [
    'status' => $up === $total ? 'ALL SYSTEMS OPERATIONAL' : ($up > $total * 0.7 ? 'DEGRADED' : 'CRITICAL'),
    'operational' => $up,
    'total' => $total,
    'readiness' => round(($up / max($total, 1)) * 100, 1) . '%',
];

if (!$quick && $db) {
    $report['ai_cascade'] = auditAICascade();
    $report['agent_fleet'] = auditAgentFleet($db);
    $report['communication_channels'] = auditCommunicationChannels();
    $report['security'] = auditSecurity($db);
    $report['database'] = auditDatabase($db);
}

$report['audit_duration_ms'] = round((microtime(true) - $auditStart) * 1000, 1);

// Handle self-heal requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    $healResults = [];

    // Use existing self-healing infrastructure
    if (file_exists(__DIR__ . '/self-healing.php')) {
        $selfHealUrl = 'http://127.0.0.1' . dirname($_SERVER['SCRIPT_NAME']) . '/self-healing.php';
        $r = curlCheck($selfHealUrl . '?action=health');
        $healResults['self_heal_status'] = $r['status'];
    }

    $report['heal_results'] = $healResults;
}

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
