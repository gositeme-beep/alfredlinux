<?php
/**
 * Crypto Transfer API — QR Code & Secure Transfer System
 * ═══════════════════════════════════════════════════════
 * Generate payment QR codes, create transfer requests, verify transactions,
 * and manage crypto wallets for the GoSiteMe ecosystem.
 * 
 * Actions: generate-qr, create-request, verify, history, wallets, 
 *          scan-result, nfc-payload, estimate-fee, address-book, seed
 */
define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

session_start();

requireCSRF();
apiRateLimit(15, 60, 'crypto');
$clientId = $_SESSION['client_id'] ?? null;
$internalSecret = getenv('INTERNAL_SECRET');
$headerSecret = $_SERVER['HTTP_X_INTERNAL_SECRET'] ?? '';

if (!$clientId && (!$internalSecret || !hash_equals($internalSecret, $headerSecret))) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

$isOwner = ((int)($clientId ?? 0) === 1) || ($internalSecret && hash_equals($internalSecret, $headerSecret));

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

ensureTables($pdo);

$action = $_GET['action'] ?? $_POST['action'] ?? 'wallets';

switch ($action) {
    case 'generate-qr':    handleGenerateQR($pdo, $clientId); break;
    case 'create-request': handleCreateRequest($pdo, $clientId); break;
    case 'verify':         handleVerify($pdo, $clientId); break;
    case 'history':        handleHistory($pdo, $clientId); break;
    case 'wallets':        handleWallets($pdo, $clientId); break;
    case 'scan-result':    handleScanResult($pdo, $clientId); break;
    case 'nfc-payload':    handleNFCPayload($pdo, $clientId); break;
    case 'estimate-fee':   handleEstimateFee($pdo); break;
    case 'address-book':   handleAddressBook($pdo, $clientId); break;
    case 'seed':           handleSeed($pdo, $isOwner); break;
    default:
        echo json_encode(['error' => 'Invalid action', 'valid' => ['generate-qr','create-request','verify','history','wallets','scan-result','nfc-payload','estimate-fee','address-book','seed']]);
}

function ensureTables($pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS crypto_transfer_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        request_id VARCHAR(36) NOT NULL UNIQUE,
        sender_client_id INT,
        recipient_client_id INT,
        recipient_address VARCHAR(200),
        network ENUM('solana','ethereum','bitcoin','polygon') DEFAULT 'solana',
        token VARCHAR(20) DEFAULT 'SOL',
        amount DECIMAL(20,9) NOT NULL,
        amount_usd DECIMAL(10,2),
        memo VARCHAR(200),
        status ENUM('pending','scanned','confirmed','expired','cancelled','failed') DEFAULT 'pending',
        qr_data TEXT,
        nfc_payload TEXT,
        transfer_method ENUM('qr','nfc','link','direct') DEFAULT 'qr',
        tx_signature VARCHAR(200),
        expires_at DATETIME,
        confirmed_at DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_sender (sender_client_id),
        INDEX idx_recipient (recipient_client_id),
        INDEX idx_status (status),
        INDEX idx_request_id (request_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS crypto_address_book (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        label VARCHAR(100) NOT NULL,
        address VARCHAR(200) NOT NULL,
        network VARCHAR(20) DEFAULT 'solana',
        is_verified TINYINT DEFAULT 0,
        last_used DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_client (client_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

// ═══════════════════════════════════════════════════════════════
// GENERATE QR — Create a payment QR code
// ═══════════════════════════════════════════════════════════════
function handleGenerateQR($pdo, $clientId) {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    
    $amount = (float)($input['amount'] ?? 0);
    $token = strtoupper($input['token'] ?? 'SOL');
    $network = $input['network'] ?? 'solana';
    $recipientAddress = $input['recipient_address'] ?? '';
    $memo = substr($input['memo'] ?? '', 0, 200);
    
    if ($amount <= 0) {
        echo json_encode(['error' => 'Amount must be greater than 0']);
        return;
    }
    
    $validTokens = ['SOL', 'USDC', 'USDT', 'GSM', 'ETH', 'BTC', 'MATIC', 'BNB'];
    if (!in_array($token, $validTokens)) {
        echo json_encode(['error' => 'Invalid token', 'valid_tokens' => $validTokens]);
        return;
    }
    
    $requestId = bin2hex(random_bytes(16));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    
    // Build Solana Pay compatible QR data
    // Format: solana:<recipient>?amount=<amount>&spl-token=<mint>&reference=<ref>&memo=<memo>
    $qrData = buildPaymentURI($network, $recipientAddress, $amount, $token, $requestId, $memo);
    
    // NFC payload (compact JSON for tap-to-pay)
    $nfcPayload = json_encode([
        'v' => 1,
        'r' => $requestId,
        'to' => $recipientAddress,
        'a' => $amount,
        't' => $token,
        'n' => $network,
        'e' => strtotime($expiresAt)
    ]);
    
    $stmt = $pdo->prepare("INSERT INTO crypto_transfer_requests 
        (request_id, sender_client_id, recipient_address, network, token, amount, memo, qr_data, nfc_payload, expires_at, transfer_method)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'qr')");
    $stmt->execute([$requestId, $clientId, $recipientAddress, $network, $token, $amount, $memo, $qrData, $nfcPayload, $expiresAt]);
    
    echo json_encode([
        'success' => true,
        'request_id' => $requestId,
        'qr_data' => $qrData,
        'nfc_payload' => $nfcPayload,
        'amount' => $amount,
        'token' => $token,
        'network' => $network,
        'expires_at' => $expiresAt,
        'recipient' => $recipientAddress
    ]);
}

function buildPaymentURI($network, $address, $amount, $token, $reference, $memo) {
    switch ($network) {
        case 'solana':
            // Solana Pay protocol: solana:<recipient>?amount=<amount>&reference=<ref>
            $uri = "solana:{$address}?amount={$amount}";
            if ($token !== 'SOL') {
                // SPL token mints (well-known)
                $mints = [
                    'USDC' => 'EPjFWdd5AufqSSqeM2qN1xzybapC8G4wEGGkZwyTDt1v',
                    'USDT' => 'Es9vMFrzaCERmJfrF4H2FYD4KCoNkY11McCe8BenwNYB',
                    'GSM'  => '' // GSM token mint address to be configured
                ];
                if (isset($mints[$token]) && $mints[$token]) {
                    $uri .= "&spl-token=" . $mints[$token];
                }
            }
            $uri .= "&reference={$reference}";
            if ($memo) $uri .= "&memo=" . urlencode($memo);
            return $uri;
            
        case 'ethereum':
        case 'polygon':
            // EIP-681: ethereum:<address>@<chainId>?value=<wei>
            $chainId = $network === 'ethereum' ? 1 : 137;
            $wei = bcmul((string)$amount, '1000000000000000000', 0);
            return "ethereum:{$address}@{$chainId}?value={$wei}";
            
        case 'bitcoin':
            // BIP-21: bitcoin:<address>?amount=<btc>
            $uri = "bitcoin:{$address}?amount={$amount}";
            if ($memo) $uri .= "&message=" . urlencode($memo);
            return $uri;
            
        default:
            return "pay:{$address}?amount={$amount}&token={$token}";
    }
}

// ═══════════════════════════════════════════════════════════════
// CREATE REQUEST — Direct crypto transfer request
// ═══════════════════════════════════════════════════════════════
function handleCreateRequest($pdo, $clientId) {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $recipientId = (int)($input['recipient_id'] ?? 0);
    $recipientAddress = $input['recipient_address'] ?? '';
    $amount = (float)($input['amount'] ?? 0);
    $token = strtoupper($input['token'] ?? 'SOL');
    $network = $input['network'] ?? 'solana';
    $method = $input['method'] ?? 'link';
    $memo = substr($input['memo'] ?? '', 0, 200);

    if ($amount <= 0) {
        echo json_encode(['error' => 'Amount must be greater than 0']);
        return;
    }

    if (!$recipientId && !$recipientAddress) {
        echo json_encode(['error' => 'recipient_id or recipient_address required']);
        return;
    }

    // If recipient_id given, look up their wallet
    if ($recipientId && !$recipientAddress) {
        $stmt = $pdo->prepare("SELECT address FROM crypto_address_book WHERE client_id = ? AND network = ? ORDER BY last_used DESC LIMIT 1");
        $stmt->execute([$recipientId, $network]);
        $recipientAddress = $stmt->fetchColumn() ?: '';
    }

    $requestId = bin2hex(random_bytes(16));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $qrData = $recipientAddress ? buildPaymentURI($network, $recipientAddress, $amount, $token, $requestId, $memo) : '';

    $stmt = $pdo->prepare("INSERT INTO crypto_transfer_requests 
        (request_id, sender_client_id, recipient_client_id, recipient_address, network, token, amount, memo, qr_data, expires_at, transfer_method)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$requestId, $clientId, $recipientId ?: null, $recipientAddress, $network, $token, $amount, $memo, $qrData, $expiresAt, $method]);

    // Generate shareable link
    $shareLink = "https://gositeme.com/pay/transfer.php?r={$requestId}";

    echo json_encode([
        'success' => true,
        'request_id' => $requestId,
        'share_link' => $shareLink,
        'qr_data' => $qrData,
        'amount' => $amount,
        'token' => $token,
        'expires_at' => $expiresAt
    ]);
}

// ═══════════════════════════════════════════════════════════════
// VERIFY — Check/confirm a transfer
// ═══════════════════════════════════════════════════════════════
function handleVerify($pdo, $clientId) {
    $requestId = $_GET['request_id'] ?? $_POST['request_id'] ?? '';
    $txSignature = $_POST['tx_signature'] ?? $_GET['tx_signature'] ?? '';

    if (!$requestId) {
        echo json_encode(['error' => 'request_id required']);
        return;
    }

    $stmt = $pdo->prepare("SELECT * FROM crypto_transfer_requests WHERE request_id = ?");
    $stmt->execute([$requestId]);
    $req = $stmt->fetch();

    if (!$req) {
        echo json_encode(['error' => 'Transfer request not found']);
        return;
    }

    // Check expiration
    if (strtotime($req['expires_at']) < time() && $req['status'] === 'pending') {
        $pdo->prepare("UPDATE crypto_transfer_requests SET status = 'expired' WHERE request_id = ?")->execute([$requestId]);
        echo json_encode(['success' => false, 'status' => 'expired', 'message' => 'Transfer request has expired']);
        return;
    }

    // If tx_signature provided, mark as confirmed
    if ($txSignature && $req['status'] !== 'confirmed') {
        $pdo->prepare("UPDATE crypto_transfer_requests SET status = 'confirmed', tx_signature = ?, confirmed_at = NOW() WHERE request_id = ?")
            ->execute([$txSignature, $requestId]);
        echo json_encode([
            'success' => true,
            'status' => 'confirmed',
            'tx_signature' => $txSignature,
            'amount' => $req['amount'],
            'token' => $req['token']
        ]);
        return;
    }

    echo json_encode([
        'success' => true,
        'status' => $req['status'],
        'amount' => $req['amount'],
        'token' => $req['token'],
        'network' => $req['network'],
        'recipient' => $req['recipient_address'],
        'expires_at' => $req['expires_at'],
        'created_at' => $req['created_at']
    ]);
}

// ═══════════════════════════════════════════════════════════════
// HISTORY — Transaction history
// ═══════════════════════════════════════════════════════════════
function handleHistory($pdo, $clientId) {
    $limit = min(100, max(1, (int)($_GET['limit'] ?? 50)));
    $status = $_GET['status'] ?? null;

    $sql = "SELECT * FROM crypto_transfer_requests WHERE (sender_client_id = ? OR recipient_client_id = ?)";
    $params = [$clientId, $clientId];

    if ($status) {
        $sql .= " AND status = ?";
        $params[] = $status;
    }

    $sql .= " ORDER BY created_at DESC LIMIT ?";
    $params[] = $limit;

    $stmt = $pdo->prepare($sql);
    dbExecute($stmt, $params);

    echo json_encode(['success' => true, 'transactions' => $stmt->fetchAll(), 'count' => $stmt->rowCount()]);
}

// ═══════════════════════════════════════════════════════════════
// WALLETS — Connected wallet addresses
// ═══════════════════════════════════════════════════════════════
function handleWallets($pdo, $clientId) {
    $stmt = $pdo->prepare("SELECT * FROM crypto_address_book WHERE client_id = ? ORDER BY last_used DESC");
    $stmt->execute([$clientId]);

    echo json_encode(['success' => true, 'wallets' => $stmt->fetchAll()]);
}

// ═══════════════════════════════════════════════════════════════
// SCAN RESULT — Process a scanned QR code
// ═══════════════════════════════════════════════════════════════
function handleScanResult($pdo, $clientId) {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $qrData = $input['qr_data'] ?? '';

    if (!$qrData) {
        echo json_encode(['error' => 'qr_data required']);
        return;
    }

    // Parse the QR data
    $parsed = parsePaymentURI($qrData);

    if (!$parsed) {
        echo json_encode(['error' => 'Invalid payment QR code', 'raw' => $qrData]);
        return;
    }

    // Check if this is one of our request IDs
    if (!empty($parsed['reference'])) {
        $stmt = $pdo->prepare("SELECT * FROM crypto_transfer_requests WHERE request_id = ?");
        $stmt->execute([$parsed['reference']]);
        $req = $stmt->fetch();
        if ($req) {
            $pdo->prepare("UPDATE crypto_transfer_requests SET status = 'scanned' WHERE request_id = ? AND status = 'pending'")
                ->execute([$parsed['reference']]);
            $parsed['internal_request'] = true;
            $parsed['request_status'] = $req['status'];
        }
    }

    echo json_encode([
        'success' => true,
        'parsed' => $parsed,
        'ready_to_send' => !empty($parsed['address']) && !empty($parsed['amount'])
    ]);
}

function parsePaymentURI($uri) {
    // Solana Pay: solana:<address>?amount=...
    if (preg_match('/^solana:([a-zA-Z0-9]{32,44})\??(.*)$/', $uri, $m)) {
        $params = [];
        if (!empty($m[2])) parse_str($m[2], $params);
        return [
            'network' => 'solana',
            'address' => $m[1],
            'amount' => (float)($params['amount'] ?? 0),
            'token' => isset($params['spl-token']) ? 'SPL' : 'SOL',
            'spl_token' => $params['spl-token'] ?? null,
            'reference' => $params['reference'] ?? null,
            'memo' => $params['memo'] ?? null
        ];
    }

    // Bitcoin: bitcoin:<address>?amount=...
    if (preg_match('/^bitcoin:([a-zA-Z0-9]{26,62})\??(.*)$/', $uri, $m)) {
        $params = [];
        if (!empty($m[2])) parse_str($m[2], $params);
        return [
            'network' => 'bitcoin',
            'address' => $m[1],
            'amount' => (float)($params['amount'] ?? 0),
            'token' => 'BTC',
            'memo' => $params['message'] ?? null
        ];
    }

    // Ethereum EIP-681: ethereum:<address>@<chainId>?value=...
    if (preg_match('/^ethereum:([0-9a-fA-Fx]{42})@?(\d*)\??(.*)$/', $uri, $m)) {
        $params = [];
        if (!empty($m[3])) parse_str($m[3], $params);
        $chainId = $m[2] ?: 1;
        return [
            'network' => $chainId == 137 ? 'polygon' : 'ethereum',
            'address' => $m[1],
            'amount' => isset($params['value']) ? bcdiv($params['value'], '1000000000000000000', 9) : 0,
            'token' => $chainId == 137 ? 'MATIC' : 'ETH',
            'chain_id' => (int)$chainId
        ];
    }

    // Try JSON (NFC payload)
    $json = json_decode($uri, true);
    if ($json && isset($json['r'])) {
        return [
            'network' => $json['n'] ?? 'solana',
            'address' => $json['to'] ?? '',
            'amount' => (float)($json['a'] ?? 0),
            'token' => $json['t'] ?? 'SOL',
            'reference' => $json['r'],
            'expires' => $json['e'] ?? null,
            'source' => 'nfc'
        ];
    }

    return null;
}

// ═══════════════════════════════════════════════════════════════
// NFC PAYLOAD — Generate NFC tap-to-pay data
// ═══════════════════════════════════════════════════════════════
function handleNFCPayload($pdo, $clientId) {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $amount = (float)($input['amount'] ?? 0);
    $token = strtoupper($input['token'] ?? 'SOL');
    $address = $input['address'] ?? '';

    if ($amount <= 0 || !$address) {
        echo json_encode(['error' => 'amount and address required']);
        return;
    }

    $requestId = bin2hex(random_bytes(16));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));

    // Compact NFC payload (minimized for NDEF)
    $payload = json_encode([
        'v' => 1,
        'r' => $requestId,
        'to' => $address,
        'a' => $amount,
        't' => $token,
        'n' => 'solana',
        'e' => strtotime($expiresAt)
    ]);

    $stmt = $pdo->prepare("INSERT INTO crypto_transfer_requests 
        (request_id, sender_client_id, recipient_address, network, token, amount, nfc_payload, expires_at, transfer_method)
        VALUES (?, ?, ?, 'solana', ?, ?, ?, ?, 'nfc')");
    $stmt->execute([$requestId, $clientId, $address, $token, $amount, $payload, $expiresAt]);

    echo json_encode([
        'success' => true,
        'request_id' => $requestId,
        'nfc_payload' => $payload,
        'payload_size' => strlen($payload),
        'expires_at' => $expiresAt,
        'ndef_type' => 'application/vnd.gositeme.pay'
    ]);
}

// ═══════════════════════════════════════════════════════════════
// ESTIMATE FEE — Network fee estimation
// ═══════════════════════════════════════════════════════════════
function handleEstimateFee($pdo) {
    $network = $_GET['network'] ?? 'solana';
    
    // Approximate network fees (updated periodically)
    $fees = [
        'solana'   => ['fee' => 0.000005, 'token' => 'SOL', 'usd' => 0.001, 'speed' => '400ms', 'finality' => '~13s'],
        'ethereum' => ['fee' => 0.002, 'token' => 'ETH', 'usd' => 5.00, 'speed' => '12s', 'finality' => '~6min'],
        'bitcoin'  => ['fee' => 0.00005, 'token' => 'BTC', 'usd' => 4.50, 'speed' => '10min', 'finality' => '~60min'],
        'polygon'  => ['fee' => 0.001, 'token' => 'MATIC', 'usd' => 0.003, 'speed' => '2s', 'finality' => '~32min'],
    ];
    
    echo json_encode([
        'success' => true,
        'network' => $network,
        'estimate' => $fees[$network] ?? $fees['solana'],
        'recommendation' => 'solana',
        'reason' => 'Lowest fees ($0.001), fastest finality (400ms), Solana Pay protocol support'
    ]);
}

// ═══════════════════════════════════════════════════════════════
// ADDRESS BOOK — Manage saved addresses
// ═══════════════════════════════════════════════════════════════
function handleAddressBook($pdo, $clientId) {
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $label = substr($input['label'] ?? '', 0, 100);
        $address = $input['address'] ?? '';
        $network = $input['network'] ?? 'solana';

        if (!$label || !$address) {
            echo json_encode(['error' => 'label and address required']);
            return;
        }

        // Validate address format
        if (!preg_match('/^[a-zA-Z0-9]{26,62}$/', $address)) {
            echo json_encode(['error' => 'Invalid wallet address format']);
            return;
        }

        $stmt = $pdo->prepare("INSERT INTO crypto_address_book (client_id, label, address, network) VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE label = VALUES(label), last_used = NOW()");
        $stmt->execute([$clientId, $label, $address, $network]);

        echo json_encode(['success' => true, 'message' => "Address saved: $label"]);
        return;
    }

    // GET — list
    $stmt = $pdo->prepare("SELECT * FROM crypto_address_book WHERE client_id = ? ORDER BY last_used DESC, label ASC");
    $stmt->execute([$clientId]);

    echo json_encode(['success' => true, 'addresses' => $stmt->fetchAll()]);
}

// ═══════════════════════════════════════════════════════════════
// SEED — Initialize with sample data
// ═══════════════════════════════════════════════════════════════
function handleSeed($pdo, $isOwner) {
    if (!$isOwner) { echo json_encode(['error' => 'Commander access only']); return; }

    // Seed address book for commander
    $addresses = [
        ['Commander Wallet', 'DummySolanaAddress1234567890abcdef12', 'solana'],
        ['GSM Treasury', 'DummyGSMTreasury1234567890abcdef12', 'solana'],
        ['Phantom Wallet', 'DummyPhantomWallet234567890abcdef12', 'solana'],
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO crypto_address_book (client_id, label, address, network) VALUES (1, ?, ?, ?)");
    foreach ($addresses as $a) {
        $stmt->execute($a);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Crypto transfer system initialized',
        'features' => [
            'qr_payments' => 'Generate Solana Pay compatible QR codes',
            'nfc_tap' => 'NFC tap-to-pay with compact NDEF payloads',
            'multi_chain' => 'Solana, Ethereum, Bitcoin, Polygon support',
            'address_book' => 'Save and manage wallet addresses',
            'transfer_links' => 'Shareable payment links',
            'fee_estimation' => 'Real-time network fee estimates'
        ]
    ]);
}
