<?php
/**
 * OVH API Integration — GoSiteMe/GoHostMe
 * Provides server provisioning, VPS management, and resource monitoring
 * via the OVH Public Cloud API (CA region).
 *
 * Credentials: Stored in commander_credentials table (VENC1 encrypted)
 *   - Create a row with service_name='OVH API' containing:
 *     username = Application Key (AK)
 *     password = Application Secret (AS)
 *     notes = Consumer Key (CK)
 *     service_url = https://ca.api.ovh.com/1.0
 *
 * To generate API credentials:
 *   1. Go to https://ca.api.ovh.com/createApp
 *   2. Login with OVH account
 *   3. Create application → get AK + AS
 *   4. Request consumer key with needed permissions
 *   5. Store all three in commander_credentials via vault
 */

// Security: admin-only access
session_start();
header('Content-Type: application/json');

// Load shared DB config and vault crypto
require_once dirname(__DIR__, 2) . '/includes/db-config.inc.php';
require_once dirname(__DIR__, 2) . '/scripts/vault-crypto.php';

// Auth check — must be Commander (client_id 33) or have admin session
$isAdmin = false;
if (isset($_SESSION['client_id']) && $_SESSION['client_id'] == 33) $isAdmin = true;
if (isset($_SESSION['admin_id'])) $isAdmin = true;
if (isset($_SERVER['HTTP_X_INTERNAL_SECRET']) && $_SERVER['HTTP_X_INTERNAL_SECRET'] === 'gohostme-ovh-internal') $isAdmin = true;

if (!$isAdmin) {
    http_response_code(403);
    echo json_encode(['error' => 'Admin access required']);
    exit;
}

// Load OVH API credentials from vault
function getOVHCredentials() {
    try {
        $pdo = new PDO(
            'mysql:unix_socket=/run/mysql/mysql.sock;dbname=' . GOSITEME_DB_NAME,
            GOSITEME_DB_USER, GOSITEME_DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        // Look for OVH API credentials
        $stmt = $pdo->query("SELECT username, password, notes, service_url FROM commander_credentials WHERE service_name LIKE '%OVH API%' OR service_name LIKE '%OVH Api%' LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return ['error' => 'OVH API credentials not found in vault. Create a commander_credentials entry with service_name="OVH API", username=AppKey, password=AppSecret, notes=ConsumerKey, service_url=https://ca.api.ovh.com/1.0'];
        }

        $row = vault_decrypt_row($row, ['username', 'password', 'notes', 'service_url']);

        return [
            'application_key' => $row['username'],
            'application_secret' => $row['password'],
            'consumer_key' => $row['notes'],
            'endpoint' => $row['service_url'] ?: 'https://ca.api.ovh.com/1.0'
        ];
    } catch (Exception $e) {
        return ['error' => 'Failed to load OVH credentials: ' . $e->getMessage()];
    }
}

/**
 * Make an authenticated OVH API request
 */
function ovhRequest($method, $path, $body = null) {
    $creds = getOVHCredentials();
    if (isset($creds['error'])) return $creds;

    $endpoint = rtrim($creds['endpoint'], '/');
    $url = $endpoint . $path;
    $bodyStr = $body ? json_encode($body) : '';

    // OVH API signature: "$1$" + SHA1(AS+"+"+CK+"+"+METHOD+"+"+URL+"+"+BODY+"+"+TIMESTAMP)
    $timestamp = time();
    $toSign = $creds['application_secret'] . '+' . $creds['consumer_key'] . '+' . $method . '+' . $url . '+' . $bodyStr . '+' . $timestamp;
    $signature = '$1$' . sha1($toSign);

    $headers = [
        'Content-Type: application/json',
        'X-Ovh-Application: ' . $creds['application_key'],
        'X-Ovh-Timestamp: ' . $timestamp,
        'X-Ovh-Signature: ' . $signature,
        'X-Ovh-Consumer: ' . $creds['consumer_key']
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    if ($bodyStr && in_array($method, ['POST', 'PUT'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyStr);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) return ['error' => 'API request failed: ' . $error];

    $decoded = json_decode($response, true);
    if ($httpCode >= 400) {
        return ['error' => 'OVH API error', 'http_code' => $httpCode, 'message' => $decoded['message'] ?? $response];
    }

    return $decoded;
}

// Route the request
$action = $_GET['action'] ?? '';

switch ($action) {

    // ─── Status / Health ───
    case 'status':
        $creds = getOVHCredentials();
        if (isset($creds['error'])) {
            echo json_encode(['status' => 'not_configured', 'message' => $creds['error']]);
        } else {
            $result = ovhRequest('GET', '/auth/time');
            if (isset($result['error'])) {
                echo json_encode(['status' => 'error', 'message' => $result['error']]);
            } else {
                echo json_encode(['status' => 'connected', 'server_time' => $result, 'endpoint' => $creds['endpoint']]);
            }
        }
        break;

    // ─── List Cloud Projects ───
    case 'projects':
        $result = ovhRequest('GET', '/cloud/project');
        echo json_encode(['projects' => $result]);
        break;

    // ─── Project Details ───
    case 'project':
        $projectId = $_GET['id'] ?? '';
        if (!$projectId) { echo json_encode(['error' => 'Project ID required']); break; }
        $projectId = preg_replace('/[^a-f0-9]/', '', $projectId);
        $result = ovhRequest('GET', "/cloud/project/$projectId");
        echo json_encode($result);
        break;

    // ─── List VPS Instances ───
    case 'instances':
        $projectId = $_GET['project'] ?? '37bf65871cb846e08198ee61ff6a3210';
        $projectId = preg_replace('/[^a-f0-9]/', '', $projectId);
        $result = ovhRequest('GET', "/cloud/project/$projectId/instance");
        echo json_encode(['instances' => $result]);
        break;

    // ─── Get Instance Details ───
    case 'instance':
        $projectId = $_GET['project'] ?? '37bf65871cb846e08198ee61ff6a3210';
        $projectId = preg_replace('/[^a-f0-9]/', '', $projectId);
        $instanceId = $_GET['id'] ?? '';
        if (!$instanceId) { echo json_encode(['error' => 'Instance ID required']); break; }
        $instanceId = preg_replace('/[^a-f0-9-]/', '', $instanceId);
        $result = ovhRequest('GET', "/cloud/project/$projectId/instance/$instanceId");
        echo json_encode($result);
        break;

    // ─── List Available Flavors (VPS sizes) ───
    case 'flavors':
        $projectId = $_GET['project'] ?? '37bf65871cb846e08198ee61ff6a3210';
        $projectId = preg_replace('/[^a-f0-9]/', '', $projectId);
        $region = $_GET['region'] ?? '';
        $path = "/cloud/project/$projectId/flavor";
        if ($region) $path .= '?region=' . preg_replace('/[^a-zA-Z0-9-]/', '', $region);
        $result = ovhRequest('GET', $path);
        echo json_encode(['flavors' => $result]);
        break;

    // ─── List Available Images (OS) ───
    case 'images':
        $projectId = $_GET['project'] ?? '37bf65871cb846e08198ee61ff6a3210';
        $projectId = preg_replace('/[^a-f0-9]/', '', $projectId);
        $region = $_GET['region'] ?? '';
        $path = "/cloud/project/$projectId/image";
        if ($region) $path .= '?region=' . preg_replace('/[^a-zA-Z0-9-]/', '', $region);
        $result = ovhRequest('GET', $path);
        // Filter to show Ubuntu images by default
        if (is_array($result)) {
            $ubuntu = array_filter($result, fn($img) => stripos($img['name'] ?? '', 'ubuntu') !== false);
            echo json_encode(['images' => array_values($ubuntu), 'total_all' => count($result)]);
        } else {
            echo json_encode(['images' => $result]);
        }
        break;

    // ─── List Regions ───
    case 'regions':
        $projectId = $_GET['project'] ?? '37bf65871cb846e08198ee61ff6a3210';
        $projectId = preg_replace('/[^a-f0-9]/', '', $projectId);
        $result = ovhRequest('GET', "/cloud/project/$projectId/region");
        echo json_encode(['regions' => $result]);
        break;

    // ─── Create Instance (Provision VPS) ───
    case 'create_instance':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        $input = json_decode(file_get_contents('php://input'), true);
        $projectId = $input['project'] ?? '37bf65871cb846e08198ee61ff6a3210';
        $projectId = preg_replace('/[^a-f0-9]/', '', $projectId);

        $required = ['name', 'flavorId', 'imageId', 'region'];
        foreach ($required as $field) {
            if (empty($input[$field])) { echo json_encode(['error' => "$field is required"]); exit; }
        }

        $body = [
            'name' => preg_replace('/[^a-zA-Z0-9_.-]/', '', substr($input['name'], 0, 64)),
            'flavorId' => $input['flavorId'],
            'imageId' => $input['imageId'],
            'region' => preg_replace('/[^a-zA-Z0-9-]/', '', $input['region']),
            'monthlyBilling' => $input['monthly'] ?? false
        ];

        // Optional SSH key
        if (!empty($input['sshKeyId'])) {
            $body['sshKeyId'] = $input['sshKeyId'];
        }

        // Optional user data (cloud-init script)
        if (!empty($input['userData'])) {
            $body['userData'] = base64_encode($input['userData']);
        }

        $result = ovhRequest('POST', "/cloud/project/$projectId/instance", $body);
        echo json_encode($result);
        break;

    // ─── Delete Instance ───
    case 'delete_instance':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        $input = json_decode(file_get_contents('php://input'), true);
        $projectId = $input['project'] ?? '37bf65871cb846e08198ee61ff6a3210';
        $projectId = preg_replace('/[^a-f0-9]/', '', $projectId);
        $instanceId = $input['id'] ?? '';
        if (!$instanceId) { echo json_encode(['error' => 'Instance ID required']); break; }
        $instanceId = preg_replace('/[^a-f0-9-]/', '', $instanceId);
        $result = ovhRequest('DELETE', "/cloud/project/$projectId/instance/$instanceId");
        echo json_encode(['success' => true, 'message' => 'Instance deletion initiated']);
        break;

    // ─── SSH Keys ───
    case 'ssh_keys':
        $projectId = $_GET['project'] ?? '37bf65871cb846e08198ee61ff6a3210';
        $projectId = preg_replace('/[^a-f0-9]/', '', $projectId);
        $result = ovhRequest('GET', "/cloud/project/$projectId/sshkey");
        echo json_encode(['ssh_keys' => $result]);
        break;

    // ─── Usage / Billing ───
    case 'usage':
        $projectId = $_GET['project'] ?? '37bf65871cb846e08198ee61ff6a3210';
        $projectId = preg_replace('/[^a-f0-9]/', '', $projectId);
        $result = ovhRequest('GET', "/cloud/project/$projectId/usage/current");
        echo json_encode($result);
        break;

    // ─── Dedicated Servers ───
    case 'dedicated_servers':
        $result = ovhRequest('GET', '/dedicated/server');
        echo json_encode(['servers' => $result]);
        break;

    case 'dedicated_server':
        $name = $_GET['name'] ?? '';
        if (!$name) { echo json_encode(['error' => 'Server name required']); break; }
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
        $result = ovhRequest('GET', "/dedicated/server/$name");
        echo json_encode($result);
        break;

    // ═══════════════════════════════════════════════════════
    //  VPS Management — /vps/
    // ═══════════════════════════════════════════════════════

    // ─── List all VPS services ───
    case 'vps_list':
        $result = ovhRequest('GET', '/vps');
        echo json_encode(['vps' => $result]);
        break;

    // ─── VPS details ───
    case 'vps_detail':
        $name = $_GET['name'] ?? '';
        if (!$name) { echo json_encode(['error' => 'VPS service name required']); break; }
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
        $result = ovhRequest('GET', "/vps/$name");
        echo json_encode($result);
        break;

    // ─── VPS status (monitoring data) ───
    case 'vps_status':
        $name = $_GET['name'] ?? '';
        if (!$name) { echo json_encode(['error' => 'VPS name required']); break; }
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
        $result = ovhRequest('GET', "/vps/$name/status");
        echo json_encode($result);
        break;

    // ─── VPS monitoring (CPU, RAM, network graphs) ───
    case 'vps_monitoring':
        $name = $_GET['name'] ?? '';
        $period = $_GET['period'] ?? 'lastday';
        $type = $_GET['type'] ?? 'cpu:used';
        if (!$name) { echo json_encode(['error' => 'VPS name required']); break; }
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
        $period = preg_replace('/[^a-z]/', '', $period);
        $type = preg_replace('/[^a-z:]/', '', $type);
        $result = ovhRequest('GET', "/vps/$name/monitoring?period=$period&type=$type");
        echo json_encode($result);
        break;

    // ─── VPS reboot (soft) ───
    case 'vps_reboot':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        $input = json_decode(file_get_contents('php://input'), true);
        $name = $input['name'] ?? '';
        if (!$name) { echo json_encode(['error' => 'VPS name required']); break; }
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
        $result = ovhRequest('POST', "/vps/$name/reboot");
        echo json_encode(['success' => true, 'task' => $result, 'message' => 'VPS reboot initiated']);
        break;

    // ─── VPS start ───
    case 'vps_start':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        $input = json_decode(file_get_contents('php://input'), true);
        $name = $input['name'] ?? '';
        if (!$name) { echo json_encode(['error' => 'VPS name required']); break; }
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
        $result = ovhRequest('POST', "/vps/$name/start");
        echo json_encode(['success' => true, 'task' => $result, 'message' => 'VPS start initiated']);
        break;

    // ─── VPS stop (hard power off) ───
    case 'vps_stop':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        $input = json_decode(file_get_contents('php://input'), true);
        $name = $input['name'] ?? '';
        if (!$name) { echo json_encode(['error' => 'VPS name required']); break; }
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
        $result = ovhRequest('POST', "/vps/$name/stop");
        echo json_encode(['success' => true, 'task' => $result, 'message' => 'VPS stop initiated']);
        break;

    // ─── VPS reinstall OS ───
    case 'vps_reinstall':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        $input = json_decode(file_get_contents('php://input'), true);
        $name = $input['name'] ?? '';
        $templateId = $input['templateId'] ?? '';
        if (!$name) { echo json_encode(['error' => 'VPS name required']); break; }
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
        $body = [];
        if ($templateId) $body['templateId'] = intval($templateId);
        if (!empty($input['sshKey'])) $body['sshKey'] = $input['sshKey'];
        $result = ovhRequest('POST', "/vps/$name/reinstall", $body ?: null);
        echo json_encode(['success' => true, 'task' => $result, 'message' => 'VPS reinstall initiated']);
        break;

    // ─── VPS available OS templates ───
    case 'vps_templates':
        $name = $_GET['name'] ?? '';
        if (!$name) { echo json_encode(['error' => 'VPS name required']); break; }
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
        $result = ovhRequest('GET', "/vps/$name/availableUpgrade");
        $templates = ovhRequest('GET', "/vps/$name/templates");
        echo json_encode(['upgrades' => $result, 'templates' => $templates]);
        break;

    // ─── VPS IPs ───
    case 'vps_ips':
        $name = $_GET['name'] ?? '';
        if (!$name) { echo json_encode(['error' => 'VPS name required']); break; }
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
        $result = ovhRequest('GET', "/vps/$name/ips");
        echo json_encode(['ips' => $result]);
        break;

    // ─── VPS disks ───
    case 'vps_disks':
        $name = $_GET['name'] ?? '';
        if (!$name) { echo json_encode(['error' => 'VPS name required']); break; }
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
        $result = ovhRequest('GET', "/vps/$name/disks");
        echo json_encode(['disks' => $result]);
        break;

    // ─── VPS snapshots ───
    case 'vps_snapshot':
        $name = $_GET['name'] ?? '';
        if (!$name) { echo json_encode(['error' => 'VPS name required']); break; }
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
        $result = ovhRequest('GET', "/vps/$name/snapshot");
        echo json_encode($result);
        break;

    // ─── VPS create snapshot ───
    case 'vps_create_snapshot':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        $input = json_decode(file_get_contents('php://input'), true);
        $name = $input['name'] ?? '';
        if (!$name) { echo json_encode(['error' => 'VPS name required']); break; }
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
        $body = [];
        if (!empty($input['description'])) $body['description'] = substr($input['description'], 0, 255);
        $result = ovhRequest('POST', "/vps/$name/createSnapshot", $body ?: null);
        echo json_encode(['success' => true, 'task' => $result, 'message' => 'Snapshot creation initiated']);
        break;

    // ─── VPS restore snapshot ───
    case 'vps_restore_snapshot':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        $input = json_decode(file_get_contents('php://input'), true);
        $name = $input['name'] ?? '';
        if (!$name) { echo json_encode(['error' => 'VPS name required']); break; }
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
        $result = ovhRequest('POST', "/vps/$name/snapshot/revert");
        echo json_encode(['success' => true, 'task' => $result, 'message' => 'Snapshot restore initiated']);
        break;

    // ─── VPS tasks (ongoing operations) ───
    case 'vps_tasks':
        $name = $_GET['name'] ?? '';
        if (!$name) { echo json_encode(['error' => 'VPS name required']); break; }
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
        $result = ovhRequest('GET', "/vps/$name/tasks");
        echo json_encode(['tasks' => $result]);
        break;

    // ─── VPS VNC console URL ───
    case 'vps_console':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        $input = json_decode(file_get_contents('php://input'), true);
        $name = $input['name'] ?? '';
        if (!$name) { echo json_encode(['error' => 'VPS name required']); break; }
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
        $result = ovhRequest('POST', "/vps/$name/openConsoleAccess", ['protocol' => 'VNCOverWebSocket']);
        echo json_encode($result);
        break;

    // ═══════════════════════════════════════════════════════
    //  Dedicated Server Management — /dedicated/server/
    // ═══════════════════════════════════════════════════════

    // ─── Dedicated server reboot (soft) ───
    case 'dedicated_reboot':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        $input = json_decode(file_get_contents('php://input'), true);
        $name = $input['name'] ?? '';
        if (!$name) { echo json_encode(['error' => 'Server name required']); break; }
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
        $result = ovhRequest('POST', "/dedicated/server/$name/reboot");
        echo json_encode(['success' => true, 'task' => $result, 'message' => 'Server reboot initiated']);
        break;

    // ─── Dedicated server boot into rescue mode ───
    case 'dedicated_rescue':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        $input = json_decode(file_get_contents('php://input'), true);
        $name = $input['name'] ?? '';
        $rescue = $input['rescue'] ?? true; // true = enable, false = disable
        if (!$name) { echo json_encode(['error' => 'Server name required']); break; }
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
        // Set boot to rescue
        $result = ovhRequest('PUT', "/dedicated/server/$name", ['bootId' => $rescue ? 1 : 0]);
        if (!isset($result['error'])) {
            // Now reboot to apply
            if ($rescue) {
                $reboot = ovhRequest('POST', "/dedicated/server/$name/reboot");
                echo json_encode(['success' => true, 'task' => $reboot, 'message' => 'Rescue mode enabled, server rebooting into rescue']);
            } else {
                echo json_encode(['success' => true, 'message' => 'Rescue mode disabled. Reboot to return to normal boot.']);
            }
        } else {
            echo json_encode($result);
        }
        break;

    // ─── Dedicated server boot modes ───
    case 'dedicated_boots':
        $name = $_GET['name'] ?? '';
        if (!$name) { echo json_encode(['error' => 'Server name required']); break; }
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
        $result = ovhRequest('GET', "/dedicated/server/$name/boot");
        echo json_encode(['boots' => $result]);
        break;

    // ─── Dedicated server boot detail ───
    case 'dedicated_boot_detail':
        $name = $_GET['name'] ?? '';
        $bootId = intval($_GET['bootId'] ?? 0);
        if (!$name) { echo json_encode(['error' => 'Server name required']); break; }
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
        $result = ovhRequest('GET', "/dedicated/server/$name/boot/$bootId");
        echo json_encode($result);
        break;

    // ─── Dedicated server hardware specs ───
    case 'dedicated_hardware':
        $name = $_GET['name'] ?? '';
        if (!$name) { echo json_encode(['error' => 'Server name required']); break; }
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
        $result = ovhRequest('GET', "/dedicated/server/$name/specifications/hardware");
        echo json_encode($result);
        break;

    // ─── Dedicated server network specs ───
    case 'dedicated_network':
        $name = $_GET['name'] ?? '';
        if (!$name) { echo json_encode(['error' => 'Server name required']); break; }
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
        $result = ovhRequest('GET', "/dedicated/server/$name/specifications/network");
        echo json_encode($result);
        break;

    // ─── Dedicated server IPs ───
    case 'dedicated_ips':
        $name = $_GET['name'] ?? '';
        if (!$name) { echo json_encode(['error' => 'Server name required']); break; }
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
        $result = ovhRequest('GET', "/dedicated/server/$name/ips");
        echo json_encode(['ips' => $result]);
        break;

    // ─── Dedicated server IPMI/KVM access ───
    case 'dedicated_ipmi':
        $name = $_GET['name'] ?? '';
        if (!$name) { echo json_encode(['error' => 'Server name required']); break; }
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
        $result = ovhRequest('GET', "/dedicated/server/$name/features/ipmi");
        echo json_encode($result);
        break;

    // ─── Dedicated server IPMI access URL ───
    case 'dedicated_ipmi_access':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        $input = json_decode(file_get_contents('php://input'), true);
        $name = $input['name'] ?? '';
        $type = $input['type'] ?? 'kvmoverip'; // kvmoverip, serialoverlan, or kvmoverip (java)
        if (!$name) { echo json_encode(['error' => 'Server name required']); break; }
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
        $type = preg_replace('/[^a-z]/', '', $type);
        $result = ovhRequest('POST', "/dedicated/server/$name/features/ipmi/access", [
            'ipToAllow' => $_SERVER['REMOTE_ADDR'] ?: '0.0.0.0',
            'type' => $type
        ]);
        echo json_encode($result);
        break;

    // ─── Dedicated server tasks ───
    case 'dedicated_tasks':
        $name = $_GET['name'] ?? '';
        if (!$name) { echo json_encode(['error' => 'Server name required']); break; }
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
        $result = ovhRequest('GET', "/dedicated/server/$name/task");
        echo json_encode(['tasks' => $result]);
        break;

    // ─── Dedicated server task detail ───
    case 'dedicated_task_detail':
        $name = $_GET['name'] ?? '';
        $taskId = intval($_GET['taskId'] ?? 0);
        if (!$name || !$taskId) { echo json_encode(['error' => 'Server name and taskId required']); break; }
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
        $result = ovhRequest('GET', "/dedicated/server/$name/task/$taskId");
        echo json_encode($result);
        break;

    // ─── Dedicated server monitoring ───
    case 'dedicated_monitoring':
        $name = $_GET['name'] ?? '';
        $period = $_GET['period'] ?? 'daily';
        $type = $_GET['type'] ?? 'traffic:download';
        if (!$name) { echo json_encode(['error' => 'Server name required']); break; }
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
        $period = preg_replace('/[^a-z]/', '', $period);
        $result = ovhRequest('GET', "/dedicated/server/$name/statistics/chart?period=$period&type=" . urlencode($type));
        echo json_encode($result);
        break;

    // ─── Dedicated server interventions (datacenter maintenance) ───
    case 'dedicated_interventions':
        $name = $_GET['name'] ?? '';
        if (!$name) { echo json_encode(['error' => 'Server name required']); break; }
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
        $result = ovhRequest('GET', "/dedicated/server/$name/intervention");
        echo json_encode(['interventions' => $result]);
        break;

    // ─── Dedicated server reinstall OS ───
    case 'dedicated_reinstall':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        $input = json_decode(file_get_contents('php://input'), true);
        $name = $input['name'] ?? '';
        $templateName = $input['templateName'] ?? '';
        if (!$name || !$templateName) { echo json_encode(['error' => 'Server name and templateName required']); break; }
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
        $body = ['details' => ['language' => 'en', 'softRaidDevices' => null], 'templateName' => $templateName];
        if (!empty($input['sshKeyName'])) $body['details']['sshKeyName'] = $input['sshKeyName'];
        $result = ovhRequest('POST', "/dedicated/server/$name/install/start", $body);
        echo json_encode(['success' => true, 'task' => $result, 'message' => 'OS reinstall initiated']);
        break;

    // ─── Dedicated server available OS templates ───
    case 'dedicated_templates':
        $name = $_GET['name'] ?? '';
        if (!$name) { echo json_encode(['error' => 'Server name required']); break; }
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
        $result = ovhRequest('GET', "/dedicated/server/$name/install/compatibleTemplates");
        echo json_encode($result);
        break;

    // ─── Dedicated server install status ───
    case 'dedicated_install_status':
        $name = $_GET['name'] ?? '';
        if (!$name) { echo json_encode(['error' => 'Server name required']); break; }
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
        $result = ovhRequest('GET', "/dedicated/server/$name/install/status");
        echo json_encode($result);
        break;

    // ═══════════════════════════════════════════════════════
    //  Cloud Instance Actions — reboot, resize, rescue, console
    // ═══════════════════════════════════════════════════════

    // ─── Cloud instance reboot ───
    case 'instance_reboot':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        $input = json_decode(file_get_contents('php://input'), true);
        $projectId = $input['project'] ?? '37bf65871cb846e08198ee61ff6a3210';
        $projectId = preg_replace('/[^a-f0-9]/', '', $projectId);
        $instanceId = $input['id'] ?? '';
        $type = $input['type'] ?? 'soft'; // soft or hard
        if (!$instanceId) { echo json_encode(['error' => 'Instance ID required']); break; }
        $instanceId = preg_replace('/[^a-f0-9-]/', '', $instanceId);
        $type = in_array($type, ['soft', 'hard']) ? $type : 'soft';
        $result = ovhRequest('POST', "/cloud/project/$projectId/instance/$instanceId/reboot", ['type' => $type]);
        echo json_encode(['success' => true, 'task' => $result, 'message' => "Instance $type reboot initiated"]);
        break;

    // ─── Cloud instance rescue mode ───
    case 'instance_rescue':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        $input = json_decode(file_get_contents('php://input'), true);
        $projectId = $input['project'] ?? '37bf65871cb846e08198ee61ff6a3210';
        $projectId = preg_replace('/[^a-f0-9]/', '', $projectId);
        $instanceId = $input['id'] ?? '';
        if (!$instanceId) { echo json_encode(['error' => 'Instance ID required']); break; }
        $instanceId = preg_replace('/[^a-f0-9-]/', '', $instanceId);
        $imageId = $input['imageId'] ?? '';
        $body = [];
        if ($imageId) $body['imageId'] = preg_replace('/[^a-f0-9-]/', '', $imageId);
        $result = ovhRequest('POST', "/cloud/project/$projectId/instance/$instanceId/rescueMode", $body ?: null);
        echo json_encode(['success' => true, 'task' => $result, 'message' => 'Rescue mode initiated']);
        break;

    // ─── Cloud instance unrescue (back to normal) ───
    case 'instance_unrescue':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        $input = json_decode(file_get_contents('php://input'), true);
        $projectId = $input['project'] ?? '37bf65871cb846e08198ee61ff6a3210';
        $projectId = preg_replace('/[^a-f0-9]/', '', $projectId);
        $instanceId = $input['id'] ?? '';
        if (!$instanceId) { echo json_encode(['error' => 'Instance ID required']); break; }
        $instanceId = preg_replace('/[^a-f0-9-]/', '', $instanceId);
        $result = ovhRequest('POST', "/cloud/project/$projectId/instance/$instanceId/unrescue");
        echo json_encode(['success' => true, 'task' => $result, 'message' => 'Exiting rescue mode']);
        break;

    // ─── Cloud instance resize ───
    case 'instance_resize':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        $input = json_decode(file_get_contents('php://input'), true);
        $projectId = $input['project'] ?? '37bf65871cb846e08198ee61ff6a3210';
        $projectId = preg_replace('/[^a-f0-9]/', '', $projectId);
        $instanceId = $input['id'] ?? '';
        $flavorId = $input['flavorId'] ?? '';
        if (!$instanceId || !$flavorId) { echo json_encode(['error' => 'Instance ID and flavorId required']); break; }
        $instanceId = preg_replace('/[^a-f0-9-]/', '', $instanceId);
        $result = ovhRequest('POST', "/cloud/project/$projectId/instance/$instanceId/resize", ['flavorId' => $flavorId]);
        echo json_encode(['success' => true, 'task' => $result, 'message' => 'Instance resize initiated']);
        break;

    // ─── Cloud instance reinstall ───
    case 'instance_reinstall':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        $input = json_decode(file_get_contents('php://input'), true);
        $projectId = $input['project'] ?? '37bf65871cb846e08198ee61ff6a3210';
        $projectId = preg_replace('/[^a-f0-9]/', '', $projectId);
        $instanceId = $input['id'] ?? '';
        $imageId = $input['imageId'] ?? '';
        if (!$instanceId || !$imageId) { echo json_encode(['error' => 'Instance ID and imageId required']); break; }
        $instanceId = preg_replace('/[^a-f0-9-]/', '', $instanceId);
        $result = ovhRequest('POST', "/cloud/project/$projectId/instance/$instanceId/reinstall", ['imageId' => $imageId]);
        echo json_encode(['success' => true, 'task' => $result, 'message' => 'OS reinstall initiated']);
        break;

    // ─── Cloud instance VNC console ───
    case 'instance_console':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        $input = json_decode(file_get_contents('php://input'), true);
        $projectId = $input['project'] ?? '37bf65871cb846e08198ee61ff6a3210';
        $projectId = preg_replace('/[^a-f0-9]/', '', $projectId);
        $instanceId = $input['id'] ?? '';
        if (!$instanceId) { echo json_encode(['error' => 'Instance ID required']); break; }
        $instanceId = preg_replace('/[^a-f0-9-]/', '', $instanceId);
        $result = ovhRequest('POST', "/cloud/project/$projectId/instance/$instanceId/vnc");
        echo json_encode($result);
        break;

    // ─── Cloud instance snapshots ───
    case 'instance_snapshot':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        $input = json_decode(file_get_contents('php://input'), true);
        $projectId = $input['project'] ?? '37bf65871cb846e08198ee61ff6a3210';
        $projectId = preg_replace('/[^a-f0-9]/', '', $projectId);
        $instanceId = $input['id'] ?? '';
        $snapshotName = $input['snapshotName'] ?? 'snapshot-' . date('Ymd-His');
        if (!$instanceId) { echo json_encode(['error' => 'Instance ID required']); break; }
        $instanceId = preg_replace('/[^a-f0-9-]/', '', $instanceId);
        $result = ovhRequest('POST', "/cloud/project/$projectId/instance/$instanceId/snapshot", ['snapshotName' => $snapshotName]);
        echo json_encode(['success' => true, 'task' => $result, 'message' => 'Snapshot creation initiated']);
        break;

    // ─── Cloud snapshots list ───
    case 'snapshots':
        $projectId = $_GET['project'] ?? '37bf65871cb846e08198ee61ff6a3210';
        $projectId = preg_replace('/[^a-f0-9]/', '', $projectId);
        $result = ovhRequest('GET', "/cloud/project/$projectId/snapshot");
        echo json_encode(['snapshots' => $result]);
        break;

    // ─── Cloud project quotas ───
    case 'quotas':
        $projectId = $_GET['project'] ?? '37bf65871cb846e08198ee61ff6a3210';
        $projectId = preg_replace('/[^a-f0-9]/', '', $projectId);
        $result = ovhRequest('GET', "/cloud/project/$projectId/quota");
        echo json_encode(['quotas' => $result]);
        break;

    // ─── Add SSH key ───
    case 'add_ssh_key':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        $input = json_decode(file_get_contents('php://input'), true);
        $projectId = $input['project'] ?? '37bf65871cb846e08198ee61ff6a3210';
        $projectId = preg_replace('/[^a-f0-9]/', '', $projectId);
        if (empty($input['name']) || empty($input['publicKey'])) { echo json_encode(['error' => 'name and publicKey required']); break; }
        $result = ovhRequest('POST', "/cloud/project/$projectId/sshkey", [
            'name' => substr($input['name'], 0, 64),
            'publicKey' => $input['publicKey']
        ]);
        echo json_encode($result);
        break;

    // ─── Delete SSH key ───
    case 'delete_ssh_key':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        $input = json_decode(file_get_contents('php://input'), true);
        $projectId = $input['project'] ?? '37bf65871cb846e08198ee61ff6a3210';
        $projectId = preg_replace('/[^a-f0-9]/', '', $projectId);
        $keyId = $input['id'] ?? '';
        if (!$keyId) { echo json_encode(['error' => 'SSH key ID required']); break; }
        $keyId = preg_replace('/[^a-f0-9]/', '', $keyId);
        $result = ovhRequest('DELETE', "/cloud/project/$projectId/sshkey/$keyId");
        echo json_encode(['success' => true, 'message' => 'SSH key deleted']);
        break;

    // ─── API Info ───
    case '':
    case 'info':
        echo json_encode([
            'service' => 'GoHostMe OVH API Integration',
            'version' => '2.0.0',
            'project' => '37bf65871cb846e08198ee61ff6a3210',
            'endpoints' => [
                'GET status' => 'Check OVH API connection',
                'GET projects' => 'List cloud projects',
                'GET instances' => 'List cloud instances',
                'POST instance_reboot' => 'Reboot cloud instance (soft/hard)',
                'POST instance_rescue' => 'Boot into rescue mode',
                'POST instance_unrescue' => 'Exit rescue mode',
                'POST instance_resize' => 'Resize cloud instance',
                'POST instance_reinstall' => 'Reinstall OS on cloud instance',
                'POST instance_console' => 'Get VNC console URL',
                'POST instance_snapshot' => 'Create snapshot',
                'GET snapshots' => 'List project snapshots',
                'GET quotas' => 'Project quotas',
                'POST create_instance' => 'Provision new VPS',
                'POST delete_instance' => 'Delete a VPS',
                'GET ssh_keys' => 'List SSH keys',
                'POST add_ssh_key' => 'Add SSH key',
                'POST delete_ssh_key' => 'Delete SSH key',
                'GET usage' => 'Current billing/usage',
                'GET vps_list' => 'List all VPS services',
                'GET vps_detail' => 'VPS details',
                'POST vps_reboot' => 'Reboot VPS',
                'POST vps_start' => 'Start VPS',
                'POST vps_stop' => 'Stop VPS',
                'POST vps_reinstall' => 'Reinstall VPS OS',
                'POST vps_console' => 'Get VPS VNC console',
                'POST vps_create_snapshot' => 'Create VPS snapshot',
                'POST vps_restore_snapshot' => 'Restore VPS snapshot',
                'GET dedicated_servers' => 'List dedicated servers',
                'GET dedicated_server' => 'Server details',
                'POST dedicated_reboot' => 'Reboot dedicated server',
                'POST dedicated_rescue' => 'Enable/disable rescue mode',
                'GET dedicated_hardware' => 'Hardware specifications',
                'GET dedicated_ips' => 'Server IP addresses',
                'POST dedicated_ipmi_access' => 'IPMI/KVM console access',
                'POST dedicated_reinstall' => 'Reinstall server OS',
                'GET dedicated_templates' => 'Available OS templates'
            ]
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action: ' . htmlspecialchars($action, ENT_QUOTES)]);
}
