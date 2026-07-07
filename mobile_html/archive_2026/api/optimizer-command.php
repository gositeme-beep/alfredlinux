// Redis setup (if available)
$redis = null;
if (class_exists('Redis')) {
    try {
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379, 0.5);
    } catch (Exception $e) { $redis = null; }
}
<?php
/**
 * Optimizer Command API
 *
 * Commander endpoint for optimization telemetry and open findings.
 */

$GLOBALS['RATE_LIMIT_EXEMPT'] = true;
$GLOBALS['CSRF_EXEMPT'] = true;
require_once __DIR__ . '/../includes/api-security.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$clientId = (int)($_SESSION['client_id'] ?? $_SESSION['uid'] ?? 0);
$email = strtolower((string)($_SESSION['email'] ?? $_SESSION['client_email'] ?? ''));
if ($clientId !== 33 && $email !== 'gositeme@gmail.com') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Commander access only']);
    exit;
}

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';

$db = getDB();
if (!$db) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database unavailable']);
    exit;
}

$action = $_GET['action'] ?? 'overview';

switch ($action) {
    case 'metrics-summary':
        // Return summary metrics for 24h, 1h, 5m from summary table, with Redis cache
        header('Cache-Control: public, max-age=30, must-revalidate');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 30) . ' GMT');
        $cacheKey = 'optimizer:metrics-summary';
        $logFile = __DIR__ . '/../logs/optimizer-api.log';
        $start = microtime(true);
        $cacheHit = false;
        if ($redis) {
            $cached = $redis->get($cacheKey);
            if ($cached) {
                $cacheHit = true;
                echo $cached;
                break;
            }
        }
        $stmt = $db->query("SELECT * FROM system_optimizer_metrics_summary ORDER BY FIELD(window, '5m', '1h', '24h')");
        $out = json_encode(['success' => true, 'summary' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        if ($redis) $redis->setex($cacheKey, 30, $out);
        $elapsed = microtime(true) - $start;
        if (!$cacheHit || $elapsed > 0.2) {
            file_put_contents($logFile, date('c') . " metrics-summary cacheMiss=" . ($cacheHit ? '0' : '1') . " time=" . number_format($elapsed,3) . "\n", FILE_APPEND);
        }
        echo $out;
        break;

    case 'findings-summary':
        // Return findings status counts from summary table, with Redis cache
        header('Cache-Control: public, max-age=30, must-revalidate');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 30) . ' GMT');
        $cacheKey = 'optimizer:findings-summary';
        $logFile = __DIR__ . '/../logs/optimizer-api.log';
        $start = microtime(true);
        $cacheHit = false;
        if ($redis) {
            $cached = $redis->get($cacheKey);
            if ($cached) {
                $cacheHit = true;
                echo $cached;
                break;
            }
        }
        $stmt = $db->query("SELECT * FROM system_optimizer_findings_summary");
        $out = json_encode(['success' => true, 'summary' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        if ($redis) $redis->setex($cacheKey, 30, $out);
        $elapsed = microtime(true) - $start;
        if (!$cacheHit || $elapsed > 0.2) {
            file_put_contents($logFile, date('c') . " findings-summary cacheMiss=" . ($cacheHit ? '0' : '1') . " time=" . number_format($elapsed,3) . "\n", FILE_APPEND);
        }
        echo $out;
        break;
    case 'overview':
        $cacheKey = 'optimizer:overview';
        if ($redis) {
            $cached = $redis->get($cacheKey);
            if ($cached) {
                echo $cached;
                break;
            }
        }
        $latest = $db->query("SELECT id, severity, cpu_pct, mem_pct, mysql_threads_running, severe_queries, optimizer_agents_active, created_at FROM system_optimizer_metrics ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $open = $db->query("SELECT severity, subsystem, COUNT(*) c FROM system_optimizer_findings WHERE status='open' GROUP BY severity, subsystem ORDER BY c DESC")->fetchAll(PDO::FETCH_ASSOC);
        $breachedOpen = (int)$db->query("SELECT COUNT(*) FROM system_optimizer_findings WHERE status='open' AND due_at IS NOT NULL AND due_at < NOW()")->fetchColumn();
        $cohorts = $db->query("SELECT department, COUNT(*) c FROM agent_profiles WHERE agent_id LIKE 'OPT-AGENT-%' AND status='active' GROUP BY department ORDER BY c DESC")->fetchAll(PDO::FETCH_ASSOC);
        $out = json_encode([
            'success' => true,
            'latest' => $latest,
            'open_findings' => $open,
            'breached_open' => $breachedOpen,
            'cohorts' => $cohorts,
        ]);
        if ($redis) $redis->setex($cacheKey, 30, $out);
        echo $out;
        break;

    case 'findings':
        $status = $_GET['status'] ?? 'open';
        $limit = max(10, min(200, (int)($_GET['limit'] ?? 50)));
        $cacheKey = 'optimizer:findings:' . md5($status . ':' . $limit);
        if ($redis) {
            $cached = $redis->get($cacheKey);
            if ($cached) {
                echo $cached;
                break;
            }
        }
        $stmt = $db->prepare("SELECT id, metric_id, severity, subsystem, finding, recommendation, assigned_agents, status, sla_target_minutes, due_at, created_at FROM system_optimizer_findings WHERE status = ? ORDER BY id DESC LIMIT ?");
        $stmt->bindValue(1, $status, PDO::PARAM_STR);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $out = json_encode(['success' => true, 'findings' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        if ($redis) $redis->setex($cacheKey, 30, $out);
        echo $out;
        break;

    case 'metrics':
        $limit = max(10, min(300, (int)($_GET['limit'] ?? 120)));
        $cacheKey = 'optimizer:metrics:' . $limit;
        if ($redis) {
            $cached = $redis->get($cacheKey);
            if ($cached) {
                echo $cached;
                break;
            }
        }
        $stmt = $db->prepare("SELECT id, load_1, load_5, load_15, cpu_pct, mem_pct, mysql_threads_running, severe_queries, severity, optimizer_agents_active, created_at FROM system_optimizer_metrics ORDER BY id DESC LIMIT ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $out = json_encode(['success' => true, 'metrics' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        if ($redis) $redis->setex($cacheKey, 30, $out);
        echo $out;
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
        break;
}
