<?php
/**
 * GoHostMe License Server API
 *
 * Central license authority at gositeme.com/api/license/
 * All GoHostMe installations verify their licenses against this server.
 *
 * Endpoints:
 *   POST /api/license/verify     — Verify a license key + server fingerprint
 *   POST /api/license/activate   — Activate a license on a server
 *   POST /api/license/deactivate — Release a license from a server
 *   GET  /api/license/tiers      — Available pricing tiers (public)
 *   POST /api/license/generate   — Generate a new license key (admin only)
 *   GET  /api/license/list       — List all licenses (admin only)
 */

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// Admin API key for management endpoints
define('LICENSE_ADMIN_KEY', 'ghm-admin-' . hash('sha256', 'GoSiteMe-License-Admin-2026'));

// Database path
define('LICENSE_DB', '/var/www/data/license-server.db');

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Strip /api/license prefix
$path = preg_replace('#^/api/license/?#', '/', $uri);
$path = rtrim($path, '/') ?: '/';

// Get JSON body
$body = json_decode(file_get_contents('php://input'), true) ?: [];

try {
    $db = getDB();

    switch ("$method $path") {
        case 'POST /verify':
            handleVerify($db, $body);
            break;
        case 'POST /activate':
            handleActivate($db, $body);
            break;
        case 'POST /deactivate':
            handleDeactivate($db, $body);
            break;
        case 'GET /tiers':
            handleTiers();
            break;
        case 'POST /generate':
            requireAdmin();
            handleGenerate($db, $body);
            break;
        case 'GET /list':
            requireAdmin();
            handleList($db);
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Unknown license endpoint']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
    error_log("License API error: " . $e->getMessage());
}

// ─── Database ───────────────────────────────────────────────────

function getDB() {
    $dir = dirname(LICENSE_DB);
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }

    $db = new PDO('sqlite:' . LICENSE_DB);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec('PRAGMA journal_mode = WAL');
    $db->exec('PRAGMA foreign_keys = ON');

    // Create tables if needed
    $db->exec("
        CREATE TABLE IF NOT EXISTS licenses (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            license_key TEXT UNIQUE NOT NULL,
            tier TEXT NOT NULL DEFAULT 'starter',
            max_accounts INTEGER NOT NULL DEFAULT 10,
            billing_cycle TEXT NOT NULL DEFAULT 'monthly',
            owner_email TEXT,
            owner_name TEXT,
            server_id TEXT,
            server_ip TEXT,
            hostname TEXT,
            activated_at TEXT,
            expires_at TEXT NOT NULL,
            issued_at TEXT NOT NULL DEFAULT (datetime('now')),
            last_verified TEXT,
            status TEXT NOT NULL DEFAULT 'active',
            notes TEXT,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at TEXT NOT NULL DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS license_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            license_key TEXT,
            action TEXT NOT NULL,
            server_id TEXT,
            server_ip TEXT,
            details TEXT,
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        );

        CREATE INDEX IF NOT EXISTS idx_licenses_key ON licenses(license_key);
        CREATE INDEX IF NOT EXISTS idx_licenses_status ON licenses(status);
        CREATE INDEX IF NOT EXISTS idx_license_logs_key ON license_logs(license_key);
    ");

    return $db;
}

// ─── Endpoint Handlers ─────────────────────────────────────────

/**
 * POST /verify — Verify a license key is valid for this server
 */
function handleVerify($db, $body) {
    $key      = $body['license_key'] ?? '';
    $serverId = $body['server_id'] ?? '';
    $serverIp = $body['server_ip'] ?? '';
    $hostname = $body['hostname'] ?? '';

    if (empty($key) || empty($serverId)) {
        http_response_code(400);
        echo json_encode(['valid' => false, 'error' => 'Missing license_key or server_id']);
        return;
    }

    $stmt = $db->prepare("SELECT * FROM licenses WHERE license_key = :key LIMIT 1");
    $stmt->execute([':key' => $key]);
    $license = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$license) {
        logAction($db, $key, 'verify_failed', $serverId, $serverIp, 'Key not found');
        echo json_encode(['valid' => false, 'error' => 'License key not found']);
        return;
    }

    // Check status
    if ($license['status'] !== 'active') {
        logAction($db, $key, 'verify_failed', $serverId, $serverIp, 'Status: ' . $license['status']);
        echo json_encode(['valid' => false, 'error' => 'License is ' . $license['status']]);
        return;
    }

    // Check expiry
    if (strtotime($license['expires_at']) < time()) {
        logAction($db, $key, 'verify_failed', $serverId, $serverIp, 'Expired');
        echo json_encode(['valid' => false, 'error' => 'License expired on ' . $license['expires_at']]);
        return;
    }

    // Check server binding — if license is already activated on a different server, reject
    if (!empty($license['server_id']) && $license['server_id'] !== $serverId) {
        logAction($db, $key, 'verify_failed', $serverId, $serverIp, 'Wrong server');
        echo json_encode(['valid' => false, 'error' => 'License is bound to a different server']);
        return;
    }

    // Update last verified
    $stmt = $db->prepare("UPDATE licenses SET last_verified = datetime('now'), server_ip = :ip, hostname = :host, updated_at = datetime('now') WHERE license_key = :key");
    $stmt->execute([':ip' => $serverIp, ':host' => $hostname, ':key' => $key]);

    logAction($db, $key, 'verify_success', $serverId, $serverIp);

    echo json_encode([
        'valid'         => true,
        'tier'          => $license['tier'],
        'max_accounts'  => (int)$license['max_accounts'],
        'billing_cycle' => $license['billing_cycle'],
        'issued_at'     => $license['issued_at'],
        'expires_at'    => $license['expires_at'],
    ]);
}

/**
 * POST /activate — Bind a license to a specific server
 */
function handleActivate($db, $body) {
    $key      = $body['license_key'] ?? '';
    $serverId = $body['server_id'] ?? '';
    $serverIp = $body['server_ip'] ?? '';
    $hostname = $body['hostname'] ?? '';

    if (empty($key) || empty($serverId)) {
        http_response_code(400);
        echo json_encode(['valid' => false, 'error' => 'Missing license_key or server_id']);
        return;
    }

    $stmt = $db->prepare("SELECT * FROM licenses WHERE license_key = :key LIMIT 1");
    $stmt->execute([':key' => $key]);
    $license = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$license) {
        echo json_encode(['valid' => false, 'error' => 'License key not found']);
        return;
    }

    if ($license['status'] !== 'active') {
        echo json_encode(['valid' => false, 'error' => 'License is ' . $license['status']]);
        return;
    }

    if (strtotime($license['expires_at']) < time()) {
        echo json_encode(['valid' => false, 'error' => 'License expired']);
        return;
    }

    // Check if already bound to a different server
    if (!empty($license['server_id']) && $license['server_id'] !== $serverId) {
        echo json_encode(['valid' => false, 'error' => 'License already activated on another server. Deactivate first.']);
        return;
    }

    // Bind to this server
    $stmt = $db->prepare("UPDATE licenses SET server_id = :sid, server_ip = :ip, hostname = :host, activated_at = datetime('now'), updated_at = datetime('now') WHERE license_key = :key");
    $stmt->execute([':sid' => $serverId, ':ip' => $serverIp, ':host' => $hostname, ':key' => $key]);

    logAction($db, $key, 'activated', $serverId, $serverIp);

    echo json_encode([
        'valid'         => true,
        'tier'          => $license['tier'],
        'max_accounts'  => (int)$license['max_accounts'],
        'billing_cycle' => $license['billing_cycle'],
        'issued_at'     => $license['issued_at'],
        'expires_at'    => $license['expires_at'],
        'message'       => 'License activated on this server',
    ]);
}

/**
 * POST /deactivate — Release a license from its server
 */
function handleDeactivate($db, $body) {
    $key      = $body['license_key'] ?? '';
    $serverId = $body['server_id'] ?? '';

    if (empty($key)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing license_key']);
        return;
    }

    $stmt = $db->prepare("SELECT * FROM licenses WHERE license_key = :key LIMIT 1");
    $stmt->execute([':key' => $key]);
    $license = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$license) {
        echo json_encode(['error' => 'License key not found']);
        return;
    }

    // Only allow deactivation from the same server or admin
    if (!empty($license['server_id']) && $license['server_id'] !== $serverId && !isAdmin()) {
        echo json_encode(['error' => 'Can only deactivate from the bound server']);
        return;
    }

    $stmt = $db->prepare("UPDATE licenses SET server_id = NULL, server_ip = NULL, hostname = NULL, activated_at = NULL, updated_at = datetime('now') WHERE license_key = :key");
    $stmt->execute([':key' => $key]);

    logAction($db, $key, 'deactivated', $serverId, $_SERVER['REMOTE_ADDR'] ?? '');

    echo json_encode(['success' => true, 'message' => 'License released. Can be activated on another server.']);
}

/**
 * GET /tiers — Public pricing info
 */
function handleTiers() {
    echo json_encode([
        'tiers' => [
            ['name' => 'Starter',    'max_accounts' => 10,  'monthly' => 9.99,  'yearly' => 99.00],
            ['name' => 'Business',   'max_accounts' => 50,  'monthly' => 24.99, 'yearly' => 249.00],
            ['name' => 'Enterprise', 'max_accounts' => 250, 'monthly' => 49.99, 'yearly' => 499.00],
            ['name' => 'Unlimited',  'max_accounts' => 0,   'monthly' => 99.99, 'yearly' => 999.00],
            ['name' => 'OEM',        'max_accounts' => 0,   'monthly' => null,  'yearly' => null, 'contact' => 'sales@gositeme.com'],
        ],
        'purchase_url' => 'https://gositeme.com/gohostme/pricing',
    ]);
}

/**
 * POST /generate — Admin: create a new license key
 * Body: { tier, billing_cycle, owner_email, owner_name, months }
 */
function handleGenerate($db, $body) {
    $tier    = $body['tier'] ?? 'starter';
    $billing = $body['billing_cycle'] ?? 'monthly';
    $email   = $body['owner_email'] ?? null;
    $name    = $body['owner_name'] ?? null;
    $months  = (int)($body['months'] ?? ($billing === 'yearly' ? 12 : 1));
    $notes   = $body['notes'] ?? null;

    // Tier validation
    $tierLimits = [
        'starter'    => 10,
        'business'   => 50,
        'enterprise' => 250,
        'unlimited'  => 0,
        'oem'        => 0,
    ];

    if (!isset($tierLimits[$tier])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid tier. Valid: starter, business, enterprise, unlimited, oem']);
        return;
    }

    // Generate unique key: GHM-XXXXX-XXXXX-XXXXX-XXXXX
    $key = generateLicenseKey();

    // Check uniqueness
    $stmt = $db->prepare("SELECT COUNT(*) FROM licenses WHERE license_key = :key");
    $stmt->execute([':key' => $key]);
    if ($stmt->fetchColumn() > 0) {
        $key = generateLicenseKey(); // Retry once
    }

    $expiresAt = date('Y-m-d H:i:s', strtotime("+{$months} months"));

    $stmt = $db->prepare("INSERT INTO licenses (license_key, tier, max_accounts, billing_cycle, owner_email, owner_name, expires_at, notes) VALUES (:key, :tier, :max, :billing, :email, :name, :expires, :notes)");
    $stmt->execute([
        ':key'     => $key,
        ':tier'    => $tier,
        ':max'     => $tierLimits[$tier],
        ':billing' => $billing,
        ':email'   => $email,
        ':name'    => $name,
        ':expires' => $expiresAt,
        ':notes'   => $notes,
    ]);

    logAction($db, $key, 'generated', null, null, "Tier: $tier, Billing: $billing, Months: $months");

    echo json_encode([
        'success'       => true,
        'license_key'   => $key,
        'tier'          => $tier,
        'max_accounts'  => $tierLimits[$tier],
        'billing_cycle' => $billing,
        'expires_at'    => $expiresAt,
        'owner_email'   => $email,
        'message'       => 'License key generated. Provide this to the customer.',
    ]);
}

/**
 * GET /list — Admin: list all licenses
 */
function handleList($db) {
    $status = $_GET['status'] ?? null;
    $query = "SELECT id, license_key, tier, max_accounts, billing_cycle, owner_email, owner_name, server_ip, hostname, activated_at, expires_at, last_verified, status, created_at FROM licenses";
    $params = [];

    if ($status) {
        $query .= " WHERE status = :status";
        $params[':status'] = $status;
    }

    $query .= " ORDER BY created_at DESC";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $licenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'total'    => count($licenses),
        'licenses' => $licenses,
    ]);
}

// ─── Helpers ────────────────────────────────────────────────────

function generateLicenseKey() {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // No I/O/0/1 for clarity
    $parts = [];
    for ($i = 0; $i < 4; $i++) {
        $segment = '';
        for ($j = 0; $j < 5; $j++) {
            $segment .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $parts[] = $segment;
    }
    return 'GHM-' . implode('-', $parts);
}

function requireAdmin() {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['HTTP_X_ADMIN_KEY'] ?? '';
    $key = str_replace('Bearer ', '', $authHeader);

    if ($key !== LICENSE_ADMIN_KEY) {
        http_response_code(403);
        echo json_encode(['error' => 'Admin authentication required']);
        exit;
    }
}

function isAdmin() {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['HTTP_X_ADMIN_KEY'] ?? '';
    $key = str_replace('Bearer ', '', $authHeader);
    return $key === LICENSE_ADMIN_KEY;
}

function logAction($db, $licenseKey, $action, $serverId = null, $serverIp = null, $details = null) {
    $stmt = $db->prepare("INSERT INTO license_logs (license_key, action, server_id, server_ip, details) VALUES (:key, :action, :sid, :ip, :details)");
    $stmt->execute([
        ':key'     => $licenseKey,
        ':action'  => $action,
        ':sid'     => $serverId,
        ':ip'      => $serverIp ?? ($_SERVER['REMOTE_ADDR'] ?? ''),
        ':details' => $details,
    ]);
}
