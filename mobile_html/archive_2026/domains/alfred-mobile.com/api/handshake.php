<?php
/**
 * GoSiteMe — Handshake Domain API
 * ────────────────────────────────
 * Manages HNS domain lookups via hnsfans explorer API
 * Wallet/auction actions via local HSD node (when running)
 * Only accessible by Supreme Admin (client_id 33)
 */

// Auth check
require_once __DIR__ . '/../includes/api-security.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Supreme Admin only
$clientId = $_SESSION['client_id'] ?? null;
if ((int)$clientId !== 33) {
    http_response_code(403);
    echo json_encode(['error' => 'Supreme Admin access only']);
    exit;
}

header('Content-Type: application/json');

$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS) 
       ?? filter_input(INPUT_POST, 'action', FILTER_SANITIZE_SPECIAL_CHARS);

if (!$action) {
    echo json_encode(['error' => 'Missing action parameter']);
    exit;
}

// ── Explorer API (primary — always available) ──────────

/**
 * Query hnsfans.com public explorer API
 */
function hns_explorer_lookup(string $name): array {
    if (!preg_match('/^[a-z0-9\-_]{1,63}$/', $name)) return ['error' => 'Invalid name'];
    $ch = curl_init("https://e.hnsfans.com/api/names/" . urlencode($name));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);
    $result = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200 || !$result) return ['error' => 'Explorer API unavailable'];
    return json_decode($result, true) ?: ['error' => 'Invalid response'];
}

/**
 * Get network summary from explorer
 */
function hns_explorer_summary(): array {
    $ch = curl_init("https://e.hnsfans.com/api/summary");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);
    $result = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200 || !$result) return ['error' => 'Explorer API unavailable'];
    return json_decode($result, true) ?: ['error' => 'Invalid response'];
}

/**
 * Get peer count from explorer
 */
function hns_explorer_peers(): int {
    $ch = curl_init("https://e.hnsfans.com/api/peers");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);
    $result = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200 || !$result) return 0;
    $data = json_decode($result, true);
    return $data['total'] ?? 0;
}

// ── Local HSD Node (optional — for wallet actions) ────

$keyFile = getenv('HOME') . '/.hsd/api-keys.json';
$nodeApiKey = '';
$walletApiKey = '';
$hsdAvailable = false;

if (file_exists($keyFile)) {
    $keys = json_decode(file_get_contents($keyFile), true);
    $nodeApiKey = $keys['apiKey'] ?? '';
    $walletApiKey = $keys['walletApiKey'] ?? '';
    $hsdAvailable = true;
}

function hsd_node_request(string $method, string $path = '/', array $body = null): array {
    global $nodeApiKey;
    $ch = curl_init("http://127.0.0.1:14037" . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_USERPWD => "x:" . $nodeApiKey,
    ]);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }
    }
    $result = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    
    if ($err) return ['error' => $err, 'httpCode' => 0];
    return array_merge(json_decode($result, true) ?: [], ['httpCode' => $code]);
}

function hsd_wallet_request(string $method, string $path, array $body = null): array {
    global $walletApiKey;
    $ch = curl_init("http://127.0.0.1:14039" . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_USERPWD => "x:" . $walletApiKey,
        CURLOPT_CUSTOMREQUEST => $method,
    ]);
    if ($body !== null && in_array($method, ['POST', 'PUT'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    $result = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    
    if ($err) return ['error' => $err, 'httpCode' => 0];
    return array_merge(json_decode($result, true) ?: [], ['httpCode' => $code]);
}

// ── Action Router ──────────────────────────────────────

switch ($action) {
    case 'network-info':
        $summary = hns_explorer_summary();
        $peers = hns_explorer_peers();
        // Get height from status endpoint
        $ch = curl_init("https://e.hnsfans.com/api/status");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        $statusRaw = curl_exec($ch);
        curl_close($ch);
        $status = json_decode($statusRaw, true) ?: [];

        echo json_encode([
            'height' => $status['height'] ?? 0,
            'registeredNames' => $summary['registeredNames'] ?? 0,
            'peers' => $status['connections'] ?? $peers,
            'difficulty' => $summary['difficulty'] ?? 0,
            'source' => 'explorer'
        ]);
        break;

    case 'node-info':
        if (!$hsdAvailable) {
            echo json_encode(['error' => 'HSD not configured', 'running' => false]);
            break;
        }
        $info = hsd_node_request('GET', '/');
        $info['synced'] = (($info['chain']['height'] ?? 0) > 100);
        echo json_encode($info);
        break;

    case 'name-status':
        $name = filter_input(INPUT_GET, 'name', FILTER_SANITIZE_SPECIAL_CHARS)
             ?? filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS);
        if (!$name || !preg_match('/^[a-z0-9\-_]{1,63}$/', $name)) {
            echo json_encode(['error' => 'Invalid name']);
            break;
        }
        $info = hns_explorer_lookup($name);
        $info['_source'] = 'explorer';
        echo json_encode($info);
        break;

    case 'lookup-all':
        $names = ['gositeme', 'qgsm', 'gsm'];
        $results = [];
        foreach ($names as $n) {
            $results[$n] = hns_explorer_lookup($n);
        }
        echo json_encode($results);
        break;

    case 'balance':
        if (!$hsdAvailable) {
            echo json_encode(['error' => 'HSD wallet not available. Use Bob Wallet instead.']);
            break;
        }
        $bal = hsd_wallet_request('GET', '/wallet/gositeme/balance');
        $addr = hsd_wallet_request('POST', '/wallet/gositeme/address', ['account' => 'default']);
        echo json_encode([
            'confirmed' => ($bal['confirmed'] ?? 0) / 1e6,
            'unconfirmed' => ($bal['unconfirmed'] ?? 0) / 1e6,
            'locked' => ($bal['lockedConfirmed'] ?? 0) / 1e6,
            'address' => $addr['address'] ?? 'wallet not ready'
        ]);
        break;

    case 'open':
    case 'bid':
    case 'reveal':
    case 'redeem':
    case 'register':
    case 'renew':
        if (!$hsdAvailable) {
            echo json_encode(['error' => 'HSD node not running. Use Bob Wallet for auction actions.']);
            break;
        }
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS);
        if (!$name || !preg_match('/^[a-z0-9\-_]{1,63}$/', $name)) {
            echo json_encode(['error' => 'Invalid name']);
            break;
        }

        switch ($action) {
            case 'open':
                $result = hsd_wallet_request('POST', '/wallet/gositeme/open', ['name' => $name]);
                break;
            case 'bid':
                $bid = filter_input(INPUT_POST, 'bid', FILTER_VALIDATE_FLOAT);
                $blind = filter_input(INPUT_POST, 'blind', FILTER_VALIDATE_FLOAT) ?: 0;
                if (!$bid || $bid <= 0) { echo json_encode(['error' => 'Invalid bid']); exit; }
                $result = hsd_wallet_request('POST', '/wallet/gositeme/bid', [
                    'name' => $name,
                    'bid' => (int)($bid * 1e6),
                    'lockup' => (int)(($bid + $blind) * 1e6)
                ]);
                break;
            case 'reveal':
                $result = hsd_wallet_request('POST', '/wallet/gositeme/reveal', ['name' => $name]);
                break;
            case 'redeem':
                $result = hsd_wallet_request('POST', '/wallet/gositeme/redeem', ['name' => $name]);
                break;
            case 'register':
                $result = hsd_wallet_request('POST', '/wallet/gositeme/update', [
                    'name' => $name,
                    'data' => ['records' => [
                        ['type' => 'NS', 'ns' => 'ns1.gositeme.com.'],
                        ['type' => 'NS', 'ns' => 'ns2.gositeme.com.'],
                        ['type' => 'GLUE4', 'ns' => 'ns1.gositeme.com.', 'address' => '15.235.50.60'],
                        ['type' => 'GLUE4', 'ns' => 'ns2.gositeme.com.', 'address' => '15.235.50.60']
                    ]]
                ]);
                break;
            case 'renew':
                $result = hsd_wallet_request('POST', '/wallet/gositeme/renewal', ['name' => $name]);
                break;
        }
        echo json_encode($result ?? ['error' => 'Unknown action']);
        break;

    case 'receive-address':
        if (!$hsdAvailable) {
            echo json_encode(['error' => 'HSD wallet not available']);
            break;
        }
        $result = hsd_wallet_request('POST', '/wallet/gositeme/address', ['account' => 'default']);
        echo json_encode(['address' => $result['address'] ?? null]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action: ' . $action]);
}
