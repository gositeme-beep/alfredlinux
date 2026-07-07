<?php
/**
 * Intelligence Director API — Paginated data for the Intelligence Director dashboard.
 * Serves agents, personnel, channels, ops, veil log, call log with search + pagination.
 * Supreme Commander only.
 */
$GLOBALS['RATE_LIMIT_EXEMPT'] = true;
$GLOBALS['CSRF_EXEMPT'] = true;
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (session_status() === PHP_SESSION_NONE) session_start();

$clientId = $_SESSION['client_id'] ?? $_SESSION['uid'] ?? null;
$clientEmail = $_SESSION['email'] ?? $_SESSION['client_email'] ?? '';

$supremeAdmins = ['gositeme@gmail.com'];
if (!$clientId || !in_array(strtolower($clientEmail), $supremeAdmins)) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

define('GOSITEME_API', true);
require_once dirname(__DIR__) . '/api/config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database unavailable']);
    exit;
}

$action = $_GET['action'] ?? 'stats';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = min(50, max(10, (int)($_GET['per_page'] ?? 25)));
$search = trim($_GET['q'] ?? '');
$offset = ($page - 1) * $perPage;

switch ($action) {

    case 'stats':
        $stats = [];
        $stats['agents'] = (int) $pdo->query("SELECT fleet FROM fleet_metrics_cache WHERE metric_key = 'fleet-50m' LIMIT 1")->fetchColumn();
        if ($stats['agents'] <= 0) {
            $stats['agents'] = (int)$pdo->query("SELECT COALESCE(TABLE_ROWS,0) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'alfred_agent_registry'")->fetchColumn();
        }
        $stats['clients'] = (int) $pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn();
        $stats['channels'] = max((int) $pdo->query("SELECT COUNT(DISTINCT channel) FROM alfred_gateway_messages")->fetchColumn(), 6);
        $stats['directives'] = (int) $pdo->query("SELECT COUNT(*) FROM alfred_ops_directives WHERE status IN ('pending','in_progress','claimed')")->fetchColumn();
        $stats['standing_orders'] = (int) $pdo->query("SELECT COUNT(*) FROM alfred_ops_standing_orders WHERE active = 1")->fetchColumn();
        $stats['veil_events'] = (int) $pdo->query("SELECT COUNT(*) FROM veil_access_log")->fetchColumn();
        $stats['calls'] = (int) $pdo->query("SELECT COUNT(*) FROM alfred_call_log")->fetchColumn();
        $stats['active_tasks'] = (int) $pdo->query("SELECT COUNT(*) FROM alfred_agent_tasks WHERE status IN ('queued','running')")->fetchColumn();

        $roles = [['role' => 'specialist', 'cnt' => $stats['agents']]];
        $statuses = [['status' => 'active', 'cnt' => $stats['agents']]];

        echo json_encode(['success' => true, 'stats' => $stats, 'roles' => $roles, 'statuses' => $statuses]);
        break;

    case 'agents':
        $where = '';
        $params = [];
        if ($search) {
            $where = "WHERE agent_name LIKE ? OR agent_role LIKE ? OR domain LIKE ?";
            $like = "%{$search}%";
            $params = [$like, $like, $like];
        }

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM alfred_agent_registry {$where}");
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        $sql = "SELECT agent_id, agent_name AS name, agent_role AS role, domain, status,
                       success_rate, tasks_completed, tasks_failed,
                       (tasks_completed + tasks_failed) AS total_tasks,
                       last_active AS last_active_at
                FROM alfred_agent_registry {$where}
                ORDER BY FIELD(agent_role,'commander','director','specialist'), agent_name ASC
                LIMIT ? OFFSET ?";
        $stmt = $pdo->prepare($sql);
        $i = 1;
        foreach ($params as $p) $stmt->bindValue($i++, $p);
        $stmt->bindValue($i++, $perPage, PDO::PARAM_INT);
        $stmt->bindValue($i, $offset, PDO::PARAM_INT);
        $stmt->execute();

        echo json_encode([
            'success' => true, 'agents' => $stmt->fetchAll(),
            'total' => $total, 'page' => $page, 'per_page' => $perPage,
            'pages' => max(1, (int) ceil($total / $perPage)),
        ]);
        break;

    case 'personnel':
        $where = '';
        $params = [];
        if ($search) {
            $where = "WHERE CONCAT(firstname, ' ', lastname) LIKE ? OR email LIKE ?";
            $like = "%{$search}%";
            $params = [$like, $like];
        }

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM clients {$where}");
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        $sql = "SELECT id, CONCAT(firstname, ' ', lastname) AS name, email, status,
                       date_created AS datecreated, last_login AS lastlogin
                FROM clients {$where}
                ORDER BY date_created DESC
                LIMIT ? OFFSET ?";
        $stmt = $pdo->prepare($sql);
        $i = 1;
        foreach ($params as $p) $stmt->bindValue($i++, $p);
        $stmt->bindValue($i++, $perPage, PDO::PARAM_INT);
        $stmt->bindValue($i, $offset, PDO::PARAM_INT);
        $stmt->execute();

        echo json_encode([
            'success' => true, 'personnel' => $stmt->fetchAll(),
            'total' => $total, 'page' => $page, 'per_page' => $perPage,
            'pages' => max(1, (int) ceil($total / $perPage)),
        ]);
        break;

    case 'channels':
        $channels = $pdo->query("SELECT channel, COUNT(*) AS total, MAX(created_at) AS last_activity
                                  FROM alfred_gateway_messages GROUP BY channel ORDER BY total DESC")->fetchAll();
        echo json_encode(['success' => true, 'channels' => $channels]);
        break;

    case 'directives':
        $directives = $pdo->query("SELECT * FROM alfred_ops_directives
                                    WHERE status IN ('pending','in_progress','claimed')
                                    ORDER BY priority DESC, created_at DESC LIMIT 50")->fetchAll();
        echo json_encode(['success' => true, 'directives' => $directives]);
        break;

    case 'standing_orders':
        $orders = $pdo->query("SELECT * FROM alfred_ops_standing_orders
                                WHERE active = 1 ORDER BY priority DESC LIMIT 50")->fetchAll();
        echo json_encode(['success' => true, 'orders' => $orders]);
        break;

    case 'veil_log':
        $total = (int) $pdo->query("SELECT COUNT(*) FROM veil_access_log")->fetchColumn();
        $stmt = $pdo->prepare("SELECT * FROM veil_access_log ORDER BY timestamp DESC LIMIT ? OFFSET ?");
        $stmt->bindValue(1, $perPage, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode([
            'success' => true, 'log' => $stmt->fetchAll(),
            'total' => $total, 'page' => $page, 'pages' => max(1, (int) ceil($total / $perPage)),
        ]);
        break;

    case 'call_log':
        $total = (int) $pdo->query("SELECT COUNT(*) FROM alfred_call_log")->fetchColumn();
        $stmt = $pdo->prepare("SELECT call_id, caller_phone, client_id, call_type,
                                       duration_seconds, summary, created_at
                                FROM alfred_call_log ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->bindValue(1, $perPage, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode([
            'success' => true, 'calls' => $stmt->fetchAll(),
            'total' => $total, 'page' => $page, 'pages' => max(1, (int) ceil($total / $perPage)),
        ]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action', 'available' => ['stats','agents','personnel','channels','directives','standing_orders','veil_log','call_log']]);
}
