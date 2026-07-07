<?php
/**
 * Alfred Composio Integration API
 * ────────────────────────────────
 * Bridge to Composio's 11,000+ tools across 850+ apps.
 * Provides auth management, tool discovery, and execution proxy.
 *
 * Endpoints:
 *   GET  ?action=apps               → List available apps
 *   GET  ?action=tools&app=...      → List tools for an app
 *   POST ?action=execute            → Execute a Composio tool
 *   POST ?action=connect            → Initiate OAuth connection to an app
 *   GET  ?action=connections        → List user's connected apps
 *   DELETE ?action=disconnect&app=  → Disconnect an app
 *   GET  ?action=stats              → Usage statistics
 */

define('GOSITEME_API', true);
$GLOBALS['CSRF_EXEMPT'] = true; // Composio external callbacks
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

define('COMPOSIO_API_KEY', getenv('COMPOSIO_API_KEY') ?: '');
define('COMPOSIO_BASE_URL', 'https://backend.composio.dev/api/v2');

// ── Database Setup ──────────────────────────────────────────────
function ensureComposioTables() {
    $db = getDB();
    if (!$db) return;
    $db->exec("CREATE TABLE IF NOT EXISTS composio_connections (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        app_name VARCHAR(100) NOT NULL,
        connection_id VARCHAR(255) NOT NULL,
        status VARCHAR(20) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY idx_client_app (client_id, app_name),
        KEY idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS composio_usage (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        app_name VARCHAR(100) NOT NULL,
        tool_name VARCHAR(255) NOT NULL,
        status VARCHAR(20) DEFAULT 'success',
        execution_time_ms INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_client (client_id),
        KEY idx_app (app_name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function getAuthUser() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return $_SESSION['client_id'] ?? null;
}

// ── Composio API Client ────────────────────────────────────────
function composioRequest($method, $path, $body = null) {
    if (!COMPOSIO_API_KEY) {
        return ['error' => 'Composio API key not configured', 'status' => 0];
    }

    $ch = curl_init(COMPOSIO_BASE_URL . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-API-Key: ' . COMPOSIO_API_KEY,
        ],
    ]);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($body) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }

    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) return ['error' => $error, 'status' => 0];
    return ['data' => json_decode($response, true), 'status' => $code];
}

// ── Router ──────────────────────────────────────────────────────
$action = sanitize($_GET['action'] ?? '', 30);

switch ($action) {

    // ── List available apps ─────────────────────────────────────
    case 'apps':
        $category = sanitize($_GET['category'] ?? '', 50);
        $search = sanitize($_GET['q'] ?? '', 100);
        $limit = min(intval($_GET['limit'] ?? 50), 200);

        $path = '/apps?limit=' . $limit;
        if ($category) $path .= '&category=' . urlencode($category);
        if ($search) $path .= '&search=' . urlencode($search);

        $result = composioRequest('GET', $path);

        if ($result['status'] === 200) {
            jsonResponse([
                'success' => true,
                'apps' => $result['data']['items'] ?? $result['data'] ?? [],
                'total' => $result['data']['totalCount'] ?? count($result['data']['items'] ?? []),
            ]);
        }

        jsonResponse(['error' => 'Failed to fetch apps', 'details' => $result['error'] ?? ''], 502);
        break;

    // ── List tools for an app ───────────────────────────────────
    case 'tools':
        $app = sanitize($_GET['app'] ?? '', 100);
        if (!$app) jsonResponse(['error' => 'app parameter required'], 400);

        $result = composioRequest('GET', '/actions?appNames=' . urlencode($app) . '&limit=100');

        if ($result['status'] === 200) {
            $tools = array_map(function($tool) {
                return [
                    'name' => $tool['name'] ?? '',
                    'displayName' => $tool['displayName'] ?? $tool['name'] ?? '',
                    'description' => $tool['description'] ?? '',
                    'appName' => $tool['appName'] ?? '',
                    'parameters' => $tool['parameters'] ?? [],
                    'tags' => $tool['tags'] ?? [],
                ];
            }, $result['data']['items'] ?? $result['data'] ?? []);

            jsonResponse([
                'success' => true,
                'app' => $app,
                'tools' => $tools,
                'count' => count($tools),
            ]);
        }

        jsonResponse(['error' => 'Failed to fetch tools', 'details' => $result['error'] ?? ''], 502);
        break;

    // ── Execute a tool ──────────────────────────────────────────
    case 'execute':
        $clientId = getAuthUser();
        if (!$clientId) jsonResponse(['error' => 'Authentication required'], 401);

        $input = json_decode(file_get_contents('php://input'), true);
        $toolName = sanitize($input['tool'] ?? '', 255);
        $params = $input['params'] ?? [];
        $appName = sanitize($input['app'] ?? '', 100);

        if (!$toolName) jsonResponse(['error' => 'tool name required'], 400);

        // Check if user has connection for this app
        $db = getDB();
        ensureComposioTables();

        if ($appName) {
            $stmt = $db->prepare("SELECT connection_id FROM composio_connections WHERE client_id = ? AND app_name = ? AND status = 'active'");
            $stmt->execute([$clientId, $appName]);
            $conn = $stmt->fetch();
            if ($conn) {
                $params['connectedAccountId'] = $conn['connection_id'];
            }
        }

        $startTime = microtime(true);

        $result = composioRequest('POST', '/actions/' . urlencode($toolName) . '/execute', [
            'input' => $params,
            'entityId' => 'user_' . $clientId,
        ]);

        $executionTime = intval((microtime(true) - $startTime) * 1000);

        // Log usage
        $stmt = $db->prepare("INSERT INTO composio_usage (client_id, app_name, tool_name, status, execution_time_ms) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $clientId,
            $appName ?: 'unknown',
            $toolName,
            ($result['status'] === 200) ? 'success' : 'error',
            $executionTime,
        ]);

        if ($result['status'] === 200) {
            jsonResponse([
                'success' => true,
                'tool' => $toolName,
                'result' => $result['data'],
                'execution_time_ms' => $executionTime,
            ]);
        }

        jsonResponse(['error' => 'Tool execution failed', 'details' => $result['data']['error'] ?? $result['error'] ?? ''], 502);
        break;

    // ── Initiate OAuth connection ───────────────────────────────
    case 'connect':
        $clientId = getAuthUser();
        if (!$clientId) jsonResponse(['error' => 'Authentication required'], 401);

        $input = json_decode(file_get_contents('php://input'), true);
        $appName = sanitize($input['app'] ?? '', 100);

        if (!$appName) jsonResponse(['error' => 'app name required'], 400);

        $result = composioRequest('POST', '/connectedAccounts', [
            'integrationId' => $appName,
            'entityId' => 'user_' . $clientId,
            'redirectUrl' => SITE_URL . '/integrations.php?composio_callback=1&app=' . urlencode($appName),
        ]);

        if ($result['status'] === 200 || $result['status'] === 201) {
            $connectionId = $result['data']['connectedAccountId'] ?? $result['data']['id'] ?? '';

            // Store connection
            $db = getDB();
            ensureComposioTables();
            $stmt = $db->prepare("INSERT INTO composio_connections (client_id, app_name, connection_id)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE connection_id = VALUES(connection_id), status = 'active'");
            $stmt->execute([$clientId, $appName, $connectionId]);

            jsonResponse([
                'success' => true,
                'app' => $appName,
                'redirectUrl' => $result['data']['redirectUrl'] ?? null,
                'connectionId' => $connectionId,
            ]);
        }

        jsonResponse(['error' => 'Failed to create connection', 'details' => $result['error'] ?? ''], 502);
        break;

    // ── List user's connections ──────────────────────────────────
    case 'connections':
        $clientId = getAuthUser();
        if (!$clientId) jsonResponse(['error' => 'Authentication required'], 401);

        $db = getDB();
        ensureComposioTables();

        $stmt = $db->prepare("SELECT app_name, connection_id, status, created_at
            FROM composio_connections WHERE client_id = ? ORDER BY created_at DESC");
        $stmt->execute([$clientId]);

        jsonResponse(['success' => true, 'connections' => $stmt->fetchAll()]);
        break;

    // ── Disconnect an app ───────────────────────────────────────
    case 'disconnect':
        $clientId = getAuthUser();
        if (!$clientId) jsonResponse(['error' => 'Authentication required'], 401);

        $appName = sanitize($_GET['app'] ?? '', 100);
        if (!$appName) jsonResponse(['error' => 'app parameter required'], 400);

        $db = getDB();
        ensureComposioTables();

        $stmt = $db->prepare("UPDATE composio_connections SET status = 'disconnected' WHERE client_id = ? AND app_name = ?");
        $stmt->execute([$clientId, $appName]);

        jsonResponse(['success' => true, 'disconnected' => $appName]);
        break;

    // ── Usage stats ─────────────────────────────────────────────
    case 'stats':
        $clientId = getAuthUser();
        if (!$clientId) jsonResponse(['error' => 'Authentication required'], 401);

        $db = getDB();
        ensureComposioTables();

        // Total executions
        $total = $db->prepare("SELECT COUNT(*) FROM composio_usage WHERE client_id = ?");
        $total->execute([$clientId]);

        // By app
        $byApp = $db->prepare("SELECT app_name, COUNT(*) as calls, AVG(execution_time_ms) as avg_time
            FROM composio_usage WHERE client_id = ?
            GROUP BY app_name ORDER BY calls DESC LIMIT 20");
        $byApp->execute([$clientId]);

        // Connected apps
        $connected = $db->prepare("SELECT COUNT(*) FROM composio_connections WHERE client_id = ? AND status = 'active'");
        $connected->execute([$clientId]);

        jsonResponse([
            'success' => true,
            'stats' => [
                'total_executions' => (int)$total->fetchColumn(),
                'connected_apps' => (int)$connected->fetchColumn(),
                'by_app' => $byApp->fetchAll(),
            ],
        ]);
        break;

    default:
        jsonResponse(['error' => 'Unknown action. Use: apps, tools, execute, connect, connections, disconnect, stats'], 400);
}
