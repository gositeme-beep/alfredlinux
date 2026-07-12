<?php
/**
 * ══════════════════════════════════════════════════════════════
 * GoSiteMe — Server Registry API
 * ══════════════════════════════════════════════════════════════
 * 
 * Manages Alfred instances deployed across multiple servers.
 * Receives heartbeats, handles registration, provides fleet view.
 *
 * Actions:
 *   register    - Register a new server instance
 *   heartbeat   - Receive health check from a server
 *   list        - List all registered servers (owner-only)
 *   status      - Get status of a specific server
 *   remove      - Remove a server from registry
 *   sync        - Trigger file sync to a server
 *   command     - Send a command to a remote server
 *
 * ══════════════════════════════════════════════════════════════
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

// ── Auth ────────────────────────────────────────────────
$headers = function_exists('getallheaders') ? getallheaders() : [];
$internalSecret = $headers['X-Internal-Secret'] ?? $headers['x-internal-secret'] ?? ($_GET['secret'] ?? '');
$isOwner = false;

if ($internalSecret && defined('INTERNAL_SECRET') && hash_equals(INTERNAL_SECRET, $internalSecret)) {
    $isOwner = true;
}

if (!$isOwner && php_sapi_name() !== 'cli') {
    session_start();
    $clientId = $_SESSION['client_id'] ?? null;
    if ($clientId && in_array((int)$clientId, [1, 33])) {
        $isOwner = true;
    }
}

if (php_sapi_name() === 'cli') {
    $isOwner = true;
}

// ── DB Setup ────────────────────────────────────────────
$db = getDB();

try {
    $db->exec("CREATE TABLE IF NOT EXISTS server_registry (
        server_id VARCHAR(32) PRIMARY KEY,
        server_name VARCHAR(200) NOT NULL,
        domain VARCHAR(255) DEFAULT NULL,
        ip VARCHAR(45) DEFAULT NULL,
        hostname VARCHAR(255) DEFAULT NULL,
        os VARCHAR(100) DEFAULT NULL,
        node_version VARCHAR(20) DEFAULT NULL,
        php_version VARCHAR(20) DEFAULT NULL,
        deploy_version VARCHAR(20) DEFAULT '1.0.0',
        status ENUM('online','offline','degraded','deploying','decommissioned') DEFAULT 'deploying',
        last_heartbeat DATETIME DEFAULT NULL,
        load_avg VARCHAR(20) DEFAULT NULL,
        memory_total_mb INT DEFAULT NULL,
        memory_used_mb INT DEFAULT NULL,
        disk_used VARCHAR(10) DEFAULT NULL,
        pm2_services INT DEFAULT 0,
        -- Hardware Specs
        cpu_model VARCHAR(200) DEFAULT NULL,
        cpu_cores INT DEFAULT NULL,
        cpu_threads INT DEFAULT NULL,
        ram_total_gb DECIMAL(6,1) DEFAULT NULL,
        disk_total_gb INT DEFAULT NULL,
        disk_type VARCHAR(50) DEFAULT NULL,
        bandwidth_mbps INT DEFAULT NULL,
        bandwidth_monthly_tb DECIMAL(6,2) DEFAULT NULL,
        network_ports JSON DEFAULT NULL,
        public_ipv4 VARCHAR(45) DEFAULT NULL,
        public_ipv6 VARCHAR(100) DEFAULT NULL,
        private_ip VARCHAR(45) DEFAULT NULL,
        datacenter VARCHAR(100) DEFAULT NULL,
        provider VARCHAR(100) DEFAULT NULL,
        monthly_cost DECIMAL(10,2) DEFAULT NULL,
        specs_notes TEXT DEFAULT NULL,
        deployed_at DATETIME DEFAULT NULL,
        registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        notes TEXT DEFAULT NULL,
        INDEX idx_status (status),
        INDEX idx_heartbeat (last_heartbeat)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS server_command_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        server_id VARCHAR(32) NOT NULL,
        command VARCHAR(200) NOT NULL,
        payload TEXT DEFAULT NULL,
        status ENUM('pending','sent','completed','failed') DEFAULT 'pending',
        result TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        completed_at DATETIME DEFAULT NULL,
        INDEX idx_server (server_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (PDOException $e) {}

// ── Parse Action ────────────────────────────────────────
$rawInput = file_get_contents('php://input');
$jsonInput = $rawInput ? json_decode($rawInput, true) : [];

$action = $jsonInput['action'] ?? ($_GET['action'] ?? ($_POST['action'] ?? 'list'));

// ── Actions ─────────────────────────────────────────────
switch ($action) {

    case 'register':
        // Any server with valid INTERNAL_SECRET can register
        $serverId = $jsonInput['server_id'] ?? '';
        $serverName = $jsonInput['server_name'] ?? '';
        
        if (!$serverId || !$serverName) {
            jsonResponse(['error' => 'server_id and server_name required'], 400);
        }
        
        $stmt = $db->prepare("INSERT INTO server_registry 
            (server_id, server_name, domain, ip, hostname, os, node_version, php_version, deployed_at, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'online')
            ON DUPLICATE KEY UPDATE 
                server_name = VALUES(server_name),
                domain = VALUES(domain),
                ip = VALUES(ip),
                hostname = VALUES(hostname),
                os = VALUES(os),
                node_version = VALUES(node_version),
                php_version = VALUES(php_version),
                status = 'online',
                last_heartbeat = NOW()");
        
        $stmt->execute([
            $serverId,
            $serverName,
            $jsonInput['domain'] ?? null,
            $jsonInput['ip'] ?? null,
            $jsonInput['hostname'] ?? null,
            $jsonInput['os'] ?? null,
            $jsonInput['node_version'] ?? null,
            $jsonInput['php_version'] ?? null,
            $jsonInput['deployed_at'] ?? date('Y-m-d H:i:s')
        ]);
        
        jsonResponse([
            'success' => true,
            'message' => "Server {$serverName} registered",
            'server_id' => $serverId
        ]);
        break;

    case 'heartbeat':
        $serverId = $jsonInput['server_id'] ?? '';
        if (!$serverId) {
            jsonResponse(['error' => 'server_id required'], 400);
        }
        
        $stmt = $db->prepare("UPDATE server_registry SET 
            status = 'online',
            last_heartbeat = NOW(),
            load_avg = ?,
            memory_total_mb = ?,
            memory_used_mb = ?,
            disk_used = ?,
            pm2_services = ?
            WHERE server_id = ?");
        
        $stmt->execute([
            $jsonInput['load'] ?? null,
            $jsonInput['memory_total_mb'] ?? null,
            $jsonInput['memory_used_mb'] ?? null,
            $jsonInput['disk_used'] ?? null,
            $jsonInput['pm2_services'] ?? 0,
            $serverId
        ]);
        
        // Check for pending commands
        $cmdStmt = $db->prepare("SELECT id, command, payload FROM server_command_log 
            WHERE server_id = ? AND status = 'pending' ORDER BY created_at ASC LIMIT 5");
        $cmdStmt->execute([$serverId]);
        $pendingCmds = $cmdStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Mark as sent
        if ($pendingCmds) {
            $ids = array_column($pendingCmds, 'id');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $db->prepare("UPDATE server_command_log SET status = 'sent' WHERE id IN ({$placeholders})")->execute($ids);
        }
        
        jsonResponse([
            'status' => 'ok',
            'pending_commands' => $pendingCmds
        ]);
        break;

    case 'list':
        if (!$isOwner) {
            http_response_code(404);
            jsonResponse(['error' => 'Not Found']);
        }
        
        // Mark servers as offline if no heartbeat in 10 minutes
        $db->exec("UPDATE server_registry SET status = 'offline' 
            WHERE status = 'online' AND last_heartbeat < DATE_SUB(NOW(), INTERVAL 10 MINUTE)");
        
        $servers = $db->query("SELECT * FROM server_registry WHERE status != 'decommissioned' ORDER BY status, server_name")->fetchAll(PDO::FETCH_ASSOC);
        
        $summary = [
            'total' => count($servers),
            'online' => count(array_filter($servers, fn($s) => $s['status'] === 'online')),
            'offline' => count(array_filter($servers, fn($s) => $s['status'] === 'offline')),
            'degraded' => count(array_filter($servers, fn($s) => $s['status'] === 'degraded')),
        ];
        
        jsonResponse([
            'success' => true,
            'summary' => $summary,
            'servers' => $servers
        ]);
        break;

    case 'status':
        if (!$isOwner) {
            http_response_code(404);
            jsonResponse(['error' => 'Not Found']);
        }
        
        $serverId = $_GET['server_id'] ?? ($jsonInput['server_id'] ?? '');
        if (!$serverId) {
            jsonResponse(['error' => 'server_id required'], 400);
        }
        
        $stmt = $db->prepare("SELECT * FROM server_registry WHERE server_id = ?");
        $stmt->execute([$serverId]);
        $server = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$server) {
            jsonResponse(['error' => 'Server not found'], 404);
        }
        
        // Get recent commands
        $cmdStmt = $db->prepare("SELECT * FROM server_command_log WHERE server_id = ? ORDER BY created_at DESC LIMIT 20");
        $cmdStmt->execute([$serverId]);
        $commands = $cmdStmt->fetchAll(PDO::FETCH_ASSOC);
        
        jsonResponse([
            'success' => true,
            'server' => $server,
            'recent_commands' => $commands
        ]);
        break;

    case 'remove':
        if (!$isOwner) {
            http_response_code(404);
            jsonResponse(['error' => 'Not Found']);
        }
        
        $serverId = $_GET['server_id'] ?? ($jsonInput['server_id'] ?? '');
        if (!$serverId) {
            jsonResponse(['error' => 'server_id required'], 400);
        }
        
        $stmt = $db->prepare("UPDATE server_registry SET status = 'decommissioned' WHERE server_id = ?");
        $stmt->execute([$serverId]);
        
        jsonResponse(['success' => true, 'message' => 'Server decommissioned']);
        break;

    case 'command':
        if (!$isOwner) {
            http_response_code(404);
            jsonResponse(['error' => 'Not Found']);
        }
        
        $serverId = $_GET['server_id'] ?? ($jsonInput['server_id'] ?? '');
        $command = $_GET['command'] ?? ($jsonInput['command'] ?? '');
        
        if (!$serverId || !$command) {
            jsonResponse(['error' => 'server_id and command required'], 400);
        }
        
        // Whitelist of allowed commands
        $allowedCommands = [
            'restart-pm2', 'sync-files', 'update-config', 
            'clear-cache', 'status-check', 'deploy-update'
        ];
        
        if (!in_array($command, $allowedCommands)) {
            jsonResponse(['error' => 'Invalid command. Allowed: ' . implode(', ', $allowedCommands)], 400);
        }
        
        $stmt = $db->prepare("INSERT INTO server_command_log (server_id, command, payload) VALUES (?, ?, ?)");
        $stmt->execute([$serverId, $command, $jsonInput['payload'] ?? null]);
        
        jsonResponse([
            'success' => true,
            'message' => "Command '{$command}' queued for server {$serverId}",
            'command_id' => $db->lastInsertId()
        ]);
        break;

    case 'deploy-script':
        // Serve the deployment script
        if (!$isOwner) {
            http_response_code(404);
            jsonResponse(['error' => 'Not Found']);
        }
        
        $scriptPath = __DIR__ . '/../scripts/alfred-deploy.sh';
        if (file_exists($scriptPath)) {
            header('Content-Type: text/plain');
            readfile($scriptPath);
        } else {
            jsonResponse(['error' => 'Deploy script not found'], 404);
        }
        exit;

    case 'update-specs':
        // Update server hardware specs — owner only
        if (!$isOwner) {
            http_response_code(404);
            jsonResponse(['error' => 'Not Found']);
        }
        
        $serverId = $jsonInput['server_id'] ?? '';
        if (!$serverId) {
            jsonResponse(['error' => 'server_id required'], 400);
        }

        // Add columns if they don't exist (idempotent migration)
        $specsCols = ['cpu_model','cpu_cores','cpu_threads','ram_total_gb','disk_total_gb',
            'disk_type','bandwidth_mbps','bandwidth_monthly_tb','network_ports',
            'public_ipv4','public_ipv6','private_ip','datacenter','provider','monthly_cost','specs_notes'];
        foreach ($specsCols as $col) {
            try {
                $colType = match($col) {
                    'cpu_model','datacenter','provider' => 'VARCHAR(200)',
                    'disk_type' => 'VARCHAR(50)',
                    'public_ipv4','private_ip' => 'VARCHAR(45)',
                    'public_ipv6' => 'VARCHAR(100)',
                    'cpu_cores','cpu_threads','disk_total_gb','bandwidth_mbps' => 'INT',
                    'ram_total_gb','bandwidth_monthly_tb' => 'DECIMAL(6,2)',
                    'monthly_cost' => 'DECIMAL(10,2)',
                    'network_ports' => 'JSON',
                    'specs_notes' => 'TEXT',
                    default => 'VARCHAR(200)'
                };
                $db->exec("ALTER TABLE server_registry ADD COLUMN $col $colType DEFAULT NULL");
            } catch (PDOException $e) {
                // Column already exists
            }
        }
        
        $updates = [];
        $params = [];
        $allowed = ['cpu_model','cpu_cores','cpu_threads','ram_total_gb','disk_total_gb',
            'disk_type','bandwidth_mbps','bandwidth_monthly_tb','network_ports',
            'public_ipv4','public_ipv6','private_ip','datacenter','provider','monthly_cost','specs_notes'];
        
        foreach ($allowed as $field) {
            if (isset($jsonInput[$field])) {
                $updates[] = "$field = ?";
                $params[] = $field === 'network_ports' ? json_encode($jsonInput[$field]) : $jsonInput[$field];
            }
        }
        
        if (empty($updates)) {
            jsonResponse(['error' => 'No specs provided to update'], 400);
        }
        
        $params[] = $serverId;
        $db->prepare("UPDATE server_registry SET " . implode(', ', $updates) . " WHERE server_id = ?")
           ->execute($params);
        
        jsonResponse([
            'success' => true,
            'message' => "Specs updated for server {$serverId}",
            'fields_updated' => count($updates)
        ]);
        break;

    default:
        if (!$isOwner) {
            http_response_code(404);
            jsonResponse(['error' => 'Not Found']);
        }
        jsonResponse(['error' => 'Unknown action', 'available' => [
            'register', 'heartbeat', 'list', 'status', 'remove', 'command', 'deploy-script', 'update-specs'
        ]], 400);
}
