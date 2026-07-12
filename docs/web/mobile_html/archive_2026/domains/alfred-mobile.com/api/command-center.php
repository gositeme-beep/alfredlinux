<?php
/**
 * Command Center API Proxy
 * ═══════════════════════════════════════════
 * Server-side proxy that handles INTERNAL_SECRET so the frontend
 * never exposes secrets in client-side JavaScript.
 *
 * AUTH: Requires login + owner check (client_id 33)
 */
$GLOBALS['CSRF_EXEMPT'] = true;
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../includes/auth-gate.inc.php';

header('Content-Type: application/json');

$isOwner = (int)($clientId ?? 0) === 33;
if (!$isOwner) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Commander access only']);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database unavailable']);
    exit;
}

switch ($action) {

    // ── Dashboard Stats ──
    case 'stats':
        $stats = ['agents' => 0, 'tasks' => 0, 'directives' => 0, 'clients' => 0];
        try {
            $stats['agents'] = (int)$pdo->query("SELECT fleet FROM fleet_metrics_cache WHERE metric_key = 'fleet-50m' LIMIT 1")->fetchColumn();
            if ($stats['agents'] <= 0) {
                $stats['agents'] = (int)$pdo->query("SELECT COALESCE(TABLE_ROWS,0) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'alfred_agent_registry'")->fetchColumn();
            }
        } catch (Exception $e) {}
        try { $stats['tasks'] = (int)$pdo->query("SELECT COUNT(*) FROM alfred_agent_tasks WHERE status IN ('queued','running')")->fetchColumn(); } catch (Exception $e) {}
        try { $stats['directives'] = (int)$pdo->query("SELECT COUNT(*) FROM alfred_ops_directives WHERE status = 'pending'")->fetchColumn(); } catch (Exception $e) {}
        try { $stats['clients'] = (int)$pdo->query("SELECT COUNT(*) FROM tblclients")->fetchColumn(); } catch (Exception $e) {}
        echo json_encode(['success' => true, 'stats' => $stats]);
        break;

    // ── Fleet (Top Agents) ──
    case 'fleet':
        $limit = min((int)($_GET['limit'] ?? 20), 100);
        $agents = $pdo->prepare("SELECT agent_id, agent_name, agent_role, domain, status, success_rate, total_tasks, last_active_at FROM alfred_agent_registry WHERE status != 'decommissioned' ORDER BY FIELD(agent_role,'commander','director','manager','specialist','worker','intern'), total_tasks DESC LIMIT :lim");
        $agents->bindValue(':lim', $limit, PDO::PARAM_INT);
        $agents->execute();
        echo json_encode(['success' => true, 'agents' => $agents->fetchAll()]);
        break;

    // ── Proxy to internal APIs ──
    case 'proxy':
        $target = $_GET['target'] ?? '';
        $params = $_GET;
        unset($params['action'], $params['target']);

        // Whitelist of allowed proxy targets
        $allowed = [
            'ecosystem-control', 'agent-growth', 'social-crosspost',
            'agent-content-engine', 'server-registry', 'system-audit',
            'evolve-mode', 'veil-status', 'seed-agentwork-gigs'
        ];

        if (!in_array($target, $allowed)) {
            echo json_encode(['success' => false, 'error' => 'Invalid proxy target']);
            break;
        }

        // Build internal URL
        $url = 'http://localhost' . '/api/' . $target . '.php?';
        $queryParts = [];

        // Determine which secret key name this API expects
        $secretKeyMap = [
            'ecosystem-control' => 'secret',
            'agent-growth' => 'internal_secret',
            'social-crosspost' => 'internal_secret',
            'agent-content-engine' => 'internal_secret',
            'server-registry' => 'secret',
            'seed-agentwork-gigs' => 'secret',
            'system-audit' => 'quick',
            'evolve-mode' => 'action',
            'veil-status' => '',
        ];

        foreach ($params as $k => $v) {
            $queryParts[] = urlencode($k) . '=' . urlencode($v);
        }

        // Add internal secret where needed
        $secretKey = $secretKeyMap[$target] ?? '';
        if ($secretKey && $secretKey !== 'quick' && $secretKey !== 'action') {
            $queryParts[] = urlencode($secretKey) . '=' . urlencode(INTERNAL_SECRET);
        }

        $url .= implode('&', $queryParts);

        // Use file_get_contents for internal call
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 10,
                'header' => "Cookie: " . ($_SERVER['HTTP_COOKIE'] ?? '') . "\r\n"
            ]
        ]);

        $result = @file_get_contents($url, false, $ctx);
        if ($result === false) {
            // Fallback: call via filesystem include won't work cleanly, try curl
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => 'https://gositeme.com/api/' . $target . '.php?' . implode('&', $queryParts),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_HTTPHEADER => ['Cookie: ' . ($_SERVER['HTTP_COOKIE'] ?? '')],
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $result = curl_exec($ch);
            curl_close($ch);
        }

        if ($result) {
            echo $result;
        } else {
            echo json_encode(['success' => false, 'error' => 'Proxy request failed']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action: ' . $action]);
}
