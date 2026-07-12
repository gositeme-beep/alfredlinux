<?php
/**
 * GoSiteMe VPN Management API — SECURE VERSION
 * Zero shell_exec. Reads synced files + writes queue requests.
 * Root cron (/usr/local/bin/vpn-sync.sh) handles all privileged operations.
 */

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

session_start();
$isCommander = false;

if (isset($_SESSION['uid']) && $_SESSION['uid'] == 33) {
    $isCommander = true;
}

$apiKey = $_SERVER['HTTP_X_COMMANDER_KEY'] ?? ($_GET['commander_key'] ?? '');
if ($apiKey === 'gositeme-commander-33-vpn') {
    $isCommander = true;
}

if (!$isCommander) {
    http_response_code(403);
    echo json_encode(['error' => 'Commander access only']);
    exit;
}

// All data lives here — synced by root cron
define('VPN_DATA', '/home/gositeme/vpn-data');
define('QUEUE_DIR', VPN_DATA . '/queue');
define('RESULTS_DIR', VPN_DATA . '/results');
define('CONFIGS_DIR', VPN_DATA . '/configs');
define('QR_DIR', VPN_DATA . '/qr');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'server_status':
        readJsonFile(VPN_DATA . '/status.json', ['status' => 'unknown', 'note' => 'Waiting for first sync']);
        break;

    case 'list_clients':
        readJsonFile(VPN_DATA . '/clients.json', ['clients' => [], 'count' => 0]);
        break;

    case 'get_config':
        getConfig();
        break;

    case 'get_qr':
        getQR();
        break;

    case 'create_client':
        createClient();
        break;

    case 'revoke_client':
        revokeClient();
        break;

    case 'check_request':
        checkRequest();
        break;

    default:
        echo json_encode(['error' => 'Unknown action', 'available' => [
            'server_status', 'list_clients', 'get_config', 'get_qr',
            'create_client', 'revoke_client', 'check_request'
        ]]);
}

/** Read a synced JSON file, return fallback if missing */
function readJsonFile($path, $fallback) {
    if (file_exists($path) && is_readable($path)) {
        $data = file_get_contents($path);
        $json = json_decode($data, true);
        if ($json !== null) {
            echo $data;
            return;
        }
    }
    echo json_encode($fallback);
}

/** Get a client's config file (synced copy, no shell needed) */
function getConfig() {
    $name = $_GET['client'] ?? 'commander';
    $name = preg_replace('/[^a-zA-Z0-9_-]/', '', $name);

    $path = CONFIGS_DIR . '/' . $name . '.conf';
    if (!file_exists($path) || !is_readable($path)) {
        echo json_encode(['error' => 'Config not found for: ' . $name]);
        return;
    }

    echo json_encode([
        'client' => $name,
        'config' => file_get_contents($path),
        'has_qr' => file_exists(QR_DIR . '/' . $name . '.png')
    ]);
}

/** Serve QR code PNG (synced copy) */
function getQR() {
    $name = $_GET['client'] ?? 'commander';
    $name = preg_replace('/[^a-zA-Z0-9_-]/', '', $name);

    $path = QR_DIR . '/' . $name . '.png';
    if (!file_exists($path) || !is_readable($path)) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'QR code not available for: ' . $name]);
        return;
    }

    header('Content-Type: image/png');
    header('Content-Disposition: inline; filename="' . $name . '-vpn-qr.png"');
    readfile($path);
    exit;
}

/** Queue a client creation request (root cron processes it) */
function createClient() {
    $name = $_POST['name'] ?? '';
    $name = preg_replace('/[^a-zA-Z0-9_-]/', '', $name);

    if (empty($name) || strlen($name) < 2 || strlen($name) > 32) {
        echo json_encode(['error' => 'Invalid name. Use 2-32 alphanumeric characters, hyphens, or underscores.']);
        return;
    }

    // Check if already exists in synced data
    if (file_exists(CONFIGS_DIR . '/' . $name . '.conf')) {
        echo json_encode(['error' => 'Client "' . $name . '" already exists']);
        return;
    }

    // Generate unique request ID
    $requestId = 'create-' . $name . '-' . time();
    $requestFile = QUEUE_DIR . '/' . $requestId . '.json';

    // Check queue isn't already full of requests for this name
    foreach (glob(QUEUE_DIR . '/create-' . $name . '-*.json') as $existing) {
        echo json_encode(['error' => 'Creation already queued for "' . $name . '". Wait ~60 seconds.']);
        return;
    }

    // Write request to queue (root cron picks it up)
    $request = json_encode([
        'name' => $name,
        'requested_at' => date('c'),
        'requested_by' => 'commander'
    ]);

    file_put_contents($requestFile, $request);

    echo json_encode([
        'queued' => true,
        'request_id' => $requestId,
        'message' => 'Profile creation queued. Ready in ~60 seconds.',
        'check_url' => '?action=check_request&id=' . urlencode($requestId)
    ]);
}

/** Queue a client revocation request */
function revokeClient() {
    $name = $_POST['name'] ?? '';
    $name = preg_replace('/[^a-zA-Z0-9_-]/', '', $name);

    if (empty($name)) {
        echo json_encode(['error' => 'Client name required']);
        return;
    }

    if ($name === 'commander') {
        echo json_encode(['error' => 'Cannot revoke Commander profile']);
        return;
    }

    if (!file_exists(CONFIGS_DIR . '/' . $name . '.conf')) {
        echo json_encode(['error' => 'Client "' . $name . '" not found']);
        return;
    }

    $requestId = 'revoke-' . $name . '-' . time();
    $requestFile = QUEUE_DIR . '/' . $requestId . '.json';

    $request = json_encode([
        'name' => $name,
        'requested_at' => date('c'),
        'requested_by' => 'commander'
    ]);

    file_put_contents($requestFile, $request);

    echo json_encode([
        'queued' => true,
        'request_id' => $requestId,
        'message' => 'Revocation queued. Takes effect in ~60 seconds.',
        'check_url' => '?action=check_request&id=' . urlencode($requestId)
    ]);
}

/** Check status of a queued request */
function checkRequest() {
    $id = $_GET['id'] ?? '';
    $id = preg_replace('/[^a-zA-Z0-9_-]/', '', $id);

    if (empty($id)) {
        echo json_encode(['error' => 'Request ID required']);
        return;
    }

    // Check if result exists
    $resultFile = RESULTS_DIR . '/' . $id . '.json';
    if (file_exists($resultFile)) {
        echo file_get_contents($resultFile);
        return;
    }

    // Check if still in queue
    $queueFile = QUEUE_DIR . '/' . $id . '.json';
    if (file_exists($queueFile)) {
        echo json_encode(['status' => 'pending', 'message' => 'Request is queued. Processing within 60 seconds.']);
        return;
    }

    echo json_encode(['status' => 'unknown', 'message' => 'Request not found. It may have already been processed and cleaned up.']);
}
