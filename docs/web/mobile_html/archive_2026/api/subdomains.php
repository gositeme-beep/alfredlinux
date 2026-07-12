<?php
/**
 * GoSiteMe — Subdomain Provisioning API
 *
 * Lets authenticated users claim a personal subdomain: username.gositeme.com
 * Stores claims in `user_subdomains` table, creates via DirectAdmin API.
 *
 * Endpoints:
 *   GET  ?action=check&name=X   — Check if subdomain is available
 *   POST ?action=claim           — Claim a subdomain (body: name)
 *   GET  ?action=mine            — List user's claimed subdomains
 *   POST ?action=release         — Release a claimed subdomain (body: name)
 *   GET  ?action=list_all        — Admin only: list all claimed subdomains
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Strict');
    session_start();
}

require_once dirname(__DIR__) . '/includes/db-config.inc.php';

$clientId = $_SESSION['client_id'] ?? $_SESSION['uid'] ?? null;
if (!$clientId) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}
$clientId = (int) $clientId;

// CSRF for POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    $sentToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
    if (!$sentToken || !hash_equals($_SESSION['csrf_token'], $sentToken)) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit;
    }
}

try {
    $pdo = getSharedDB();
} catch (Exception $e) {
    error_log('subdomains API DB error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Service unavailable']);
    exit;
}

// ── Ensure table exists ────────────────────────────────────────────
$pdo->exec("CREATE TABLE IF NOT EXISTS user_subdomains (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    subdomain_name VARCHAR(63) NOT NULL,
    parent_domain VARCHAR(255) NOT NULL DEFAULT 'gositeme.com',
    status ENUM('active','suspended','released') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    released_at TIMESTAMP NULL,
    UNIQUE KEY uq_subdomain (subdomain_name, parent_domain),
    KEY idx_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Constants ──────────────────────────────────────────────────────
const PARENT_DOMAIN    = 'gositeme.com';
const MAX_PER_USER     = 3;   // max subdomains per user
const OWNER_CLIENT_ID  = 33;  // admin gets unlimited

// Reserved names that cannot be claimed
const RESERVED_NAMES = [
    'www', 'mail', 'smtp', 'imap', 'pop', 'ftp', 'ns1', 'ns2', 'dns',
    'api', 'admin', 'root', 'support', 'help', 'billing', 'pay',
    'blog', 'store', 'shop', 'app', 'cdn', 'static', 'assets',
    'dev', 'staging', 'test', 'demo', 'beta', 'alpha',
    'webmail', 'cpanel', 'whm', 'directadmin',
    'alfred', 'gocodeme', 'veil', 'qgsm', 'gsm',
    'status', 'health', 'monitor', 'metrics',
];

function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
    exit;
}

function validateSubdomainName(string $name): ?string {
    $name = strtolower(trim($name));
    if (strlen($name) < 2 || strlen($name) > 63) {
        return 'Subdomain must be 2-63 characters';
    }
    if (!preg_match('/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?$/', $name)) {
        return 'Subdomain may only contain letters, numbers, and hyphens (cannot start/end with hyphen)';
    }
    if (in_array($name, RESERVED_NAMES, true)) {
        return 'This subdomain name is reserved';
    }
    return null;
}

// ── Middleware proxy to DirectAdmin ────────────────────────────────
function callMiddlewareSubdomain(string $method, string $subdomain): array {
    $middlewareUrl = 'http://127.0.0.1:3001';
    
    // Generate an internal service token for the DA call
    $internalSecret = getenv('INTERNAL_SECRET') ?: ($_ENV['INTERNAL_SECRET'] ?? '');
    if (!$internalSecret) {
        return ['ok' => false, 'error' => 'Internal configuration error'];
    }
    
    $url = $middlewareUrl . '/hosting/domains/' . urlencode(PARENT_DOMAIN) . '/subdomains';
    
    $headers = [
        'Content-Type: application/json',
        'X-Internal-Secret: ' . $internalSecret,
        'X-DA-Username: gositeme',
    ];
    
    $ch = curl_init();
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['subdomain' => $subdomain]));
    } elseif ($method === 'DELETE') {
        $url .= '/' . urlencode($subdomain);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        $data = json_decode($response, true);
        return $data ?: ['ok' => true];
    }
    
    $data = json_decode($response, true);
    return ['ok' => false, 'error' => $data['error'] ?? 'DirectAdmin API error', 'http' => $httpCode];
}

// ── Route ──────────────────────────────────────────────────────────
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

case 'check':
    $name = strtolower(trim($_GET['name'] ?? ''));
    $err = validateSubdomainName($name);
    if ($err) respond(['available' => false, 'reason' => $err]);
    
    $stmt = $pdo->prepare("SELECT client_id FROM user_subdomains WHERE subdomain_name = ? AND parent_domain = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$name, PARENT_DOMAIN]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    respond([
        'available' => !$existing,
        'subdomain' => $name,
        'full'      => $name . '.' . PARENT_DOMAIN,
        'reason'    => $existing ? 'Already claimed' : null,
    ]);

case 'claim':
    $body = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $name = strtolower(trim($body['name'] ?? ''));
    
    $err = validateSubdomainName($name);
    if ($err) respond(['error' => $err], 400);
    
    // Check user limit (admin exempt)
    if ($clientId !== OWNER_CLIENT_ID) {
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM user_subdomains WHERE client_id = ? AND status = 'active'");
        $countStmt->execute([$clientId]);
        if ((int) $countStmt->fetchColumn() >= MAX_PER_USER) {
            respond(['error' => 'Maximum ' . MAX_PER_USER . ' subdomains per account'], 403);
        }
    }
    
    // Check availability
    $checkStmt = $pdo->prepare("SELECT id FROM user_subdomains WHERE subdomain_name = ? AND parent_domain = ? AND status = 'active' LIMIT 1");
    $checkStmt->execute([$name, PARENT_DOMAIN]);
    if ($checkStmt->fetch()) {
        respond(['error' => 'Subdomain already claimed'], 409);
    }
    
    // Create in DirectAdmin
    $daResult = callMiddlewareSubdomain('POST', $name);
    if (!($daResult['ok'] ?? false)) {
        error_log("subdomain claim DA error: " . json_encode($daResult));
        // If DA reports it already exists, that's okay — maybe it was manually created
        if (stripos($daResult['error'] ?? '', 'already exists') === false) {
            respond(['error' => 'Failed to provision subdomain: ' . ($daResult['error'] ?? 'unknown')], 500);
        }
    }
    
    // Store in DB
    $insertStmt = $pdo->prepare("INSERT INTO user_subdomains (client_id, subdomain_name, parent_domain) VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE client_id = VALUES(client_id), status = 'active', released_at = NULL");
    $insertStmt->execute([$clientId, $name, PARENT_DOMAIN]);
    
    respond([
        'ok'        => true,
        'subdomain' => $name,
        'full'      => $name . '.' . PARENT_DOMAIN,
        'url'       => 'https://' . $name . '.' . PARENT_DOMAIN,
    ]);

case 'mine':
    $stmt = $pdo->prepare("SELECT subdomain_name, parent_domain, status, created_at FROM user_subdomains WHERE client_id = ? ORDER BY created_at DESC");
    $stmt->execute([$clientId]);
    $subs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    respond([
        'subdomains' => array_map(function($s) {
            return [
                'name'   => $s['subdomain_name'],
                'domain' => $s['parent_domain'],
                'full'   => $s['subdomain_name'] . '.' . $s['parent_domain'],
                'url'    => 'https://' . $s['subdomain_name'] . '.' . $s['parent_domain'],
                'status' => $s['status'],
                'created' => $s['created_at'],
            ];
        }, $subs),
        'limit' => $clientId === OWNER_CLIENT_ID ? 'unlimited' : MAX_PER_USER,
    ]);

case 'release':
    $body = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $name = strtolower(trim($body['name'] ?? ''));
    
    if (!$name) respond(['error' => 'Subdomain name required'], 400);
    
    // Verify ownership
    $stmt = $pdo->prepare("SELECT id FROM user_subdomains WHERE client_id = ? AND subdomain_name = ? AND parent_domain = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$clientId, $name, PARENT_DOMAIN]);
    if (!$stmt->fetch()) {
        respond(['error' => 'Subdomain not found or not yours'], 404);
    }
    
    // Delete from DirectAdmin
    callMiddlewareSubdomain('DELETE', $name);
    
    // Mark released in DB
    $pdo->prepare("UPDATE user_subdomains SET status = 'released', released_at = NOW() WHERE client_id = ? AND subdomain_name = ? AND parent_domain = ?")->execute([$clientId, $name, PARENT_DOMAIN]);
    
    respond(['ok' => true, 'released' => $name . '.' . PARENT_DOMAIN]);

case 'list_all':
    if ($clientId !== OWNER_CLIENT_ID) {
        respond(['error' => 'Admin only'], 403);
    }
    
    $stmt = $pdo->query("SELECT us.*, c.firstname, c.lastname, c.email 
        FROM user_subdomains us 
        LEFT JOIN clients c ON c.id = us.client_id 
        ORDER BY us.created_at DESC 
        LIMIT 500");
    $all = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    respond(['subdomains' => $all, 'total' => count($all)]);

default:
    respond(['error' => 'Unknown action', 'actions' => ['check', 'claim', 'mine', 'release', 'list_all']], 400);
}
