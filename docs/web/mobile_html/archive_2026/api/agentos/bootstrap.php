<?php
/**
 * GSM Alfred OS — Bootstrap & Shared Infrastructure
 * Common initialization for all Alfred OS modules
 */

if (!defined('AGENTOS_LOADED')) {
    define('AGENTOS_LOADED', true);
    define('AGENTOS_VERSION', '1.0.0');
    define('AGENTOS_ROOT', __DIR__);
}

// ── Database Connection ────────────────────────────────────────
require_once dirname(AGENTOS_ROOT) . '/config.php';

// ── Runtime Secrets ────────────────────────────────────────────
$secretsFile = dirname(dirname(AGENTOS_ROOT)) . '/config/.agentos_secrets.php';
if (file_exists($secretsFile)) {
    require_once $secretsFile;
}

function agentos_pdo(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    return $pdo;
}

// ── ID Generation ──────────────────────────────────────────────
function agentos_id(string $prefix = 'aos'): string {
    return $prefix . '-' . bin2hex(random_bytes(8));
}

function agentos_trace_id(): string {
    return 'trace-' . bin2hex(random_bytes(12));
}

// ── JSON Response Helper ───────────────────────────────────────
function agentos_respond(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function agentos_error(string $message, int $code = 400): void {
    agentos_respond(['ok' => false, 'error' => $message], $code);
}

// ── Audit Logger ───────────────────────────────────────────────
function agentos_audit(array $entry): void {
    try {
        $pdo = agentos_pdo();
        $stmt = $pdo->prepare("INSERT INTO agentos_audit_log 
            (trace_id, task_id, node_id, agent_id, user_id, action_type,
             capability_id, input_summary, output_summary, decision_reason,
             risk_level, status, duration_ms, metadata)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $entry['trace_id'] ?? agentos_trace_id(),
            $entry['task_id'] ?? null,
            $entry['node_id'] ?? null,
            $entry['agent_id'] ?? 'alfred',
            $entry['user_id'] ?? null,
            $entry['action_type'],
            $entry['capability_id'] ?? null,
            isset($entry['input']) ? json_encode($entry['input']) : null,
            isset($entry['output']) ? json_encode($entry['output']) : null,
            $entry['reason'] ?? null,
            $entry['risk_level'] ?? 'low',
            $entry['status'],
            $entry['duration_ms'] ?? null,
            isset($entry['metadata']) ? json_encode($entry['metadata']) : null,
        ]);
    } catch (\Throwable $e) {
        error_log("[AGENTOS-AUDIT] Failed to write audit log: " . $e->getMessage());
    }
}

// ── Redis Helper (with file fallback) ──────────────────────────
function agentos_redis(): ?Redis {
    static $redis = null;
    static $tried = false;
    if ($tried) return $redis;
    $tried = true;
    try {
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379, 1.0);
        $redis->setOption(Redis::OPT_PREFIX, 'agentos:');
        return $redis;
    } catch (\Throwable $e) {
        $redis = null;
        return null;
    }
}

function agentos_cache_get(string $key) {
    $r = agentos_redis();
    if ($r) {
        $val = $r->get($key);
        return $val !== false ? json_decode($val, true) : null;
    }
    $file = sys_get_temp_dir() . '/agentos_cache_' . md5($key);
    if (file_exists($file) && (time() - filemtime($file)) < 300) {
        return json_decode(file_get_contents($file), true);
    }
    return null;
}

function agentos_cache_set(string $key, $value, int $ttl = 300): void {
    $r = agentos_redis();
    $json = json_encode($value);
    if ($r) {
        $r->setex($key, $ttl, $json);
        return;
    }
    $file = sys_get_temp_dir() . '/agentos_cache_' . md5($key);
    file_put_contents($file, $json, LOCK_EX);
}

// ── WebSocket Push ─────────────────────────────────────────────
function agentos_push(string $channel, string $event, array $data): void {
    try {
        require_once dirname(AGENTOS_ROOT, 2) . '/includes/ws-push.php';
        ws_push($channel, array_merge($data, ['event' => $event]));
    } catch (\Throwable $e) {
        error_log("[AGENTOS-WS] Push failed: " . $e->getMessage());
    }
}

// ── Auth Check ─────────────────────────────────────────────────
function agentos_auth(): array {
    session_start();
    $userId = $_SESSION['uid'] ?? $_SESSION['client_id'] ?? null;
    $username = $_SESSION['username'] ?? $_SESSION['client_name'] ?? 'Guest';
    $isInternal = false;

    // Internal secret bypass for server-to-server
    $internalSecret = $_SERVER['HTTP_X_INTERNAL_SECRET'] ?? '';
    $expectedSecret = defined('AGENTOS_INTERNAL_SECRET') ? AGENTOS_INTERNAL_SECRET : (getenv('INTERNAL_SECRET') ?: '');
    if ($internalSecret !== '' && $expectedSecret !== '' && hash_equals($expectedSecret, $internalSecret)) {
        $isInternal = true;
    }

    return ['user_id' => $userId, 'username' => $username, 'is_internal' => $isInternal];
}

// ── Schema Auto-Migration ──────────────────────────────────────
function agentos_ensure_schema(): void {
    static $checked = false;
    if ($checked) return;
    $checked = true;

    $pdo = agentos_pdo();
    $result = $pdo->query("SHOW TABLES LIKE 'agentos_capabilities'");
    if ($result->rowCount() === 0) {
        $sql = file_get_contents(__DIR__ . '/schema.sql');
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($statements as $stmt) {
            if (!empty($stmt) && stripos($stmt, 'CREATE TABLE') !== false) {
                $pdo->exec($stmt);
            }
        }
        error_log("[AGENTOS] Schema auto-migrated successfully");
    }
}
