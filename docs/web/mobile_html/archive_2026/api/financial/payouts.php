<?php
/**
 * Payouts API — PayPal Mass Payouts, Stripe Connect Payouts, Deel, Wise
 * ATLAS Agents: Paymaster (#42), Treasurer (#38)
 */
define('GOSITEME_API', true);
require_once dirname(__DIR__) . '/config.php';
require_once __DIR__ . '/config.php';

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Internal-Secret');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

// ─── Schema ───────────────────────────────────────────────────
function ensurePayoutsSchema(): void {
    $db = getDB();

    $db->exec("CREATE TABLE IF NOT EXISTS fin_payouts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        payout_type ENUM('affiliate_commission','contractor','refund','marketplace','manual') NOT NULL,
        provider ENUM('paypal','stripe_connect','wise','deel','mercury','crypto') NOT NULL,
        recipient_email VARCHAR(200),
        recipient_name VARCHAR(200),
        recipient_id VARCHAR(100),
        amount_cents BIGINT NOT NULL,
        currency VARCHAR(10) DEFAULT 'USD',
        fee_cents BIGINT DEFAULT 0,
        status ENUM('pending','processing','completed','failed','cancelled') DEFAULT 'pending',
        batch_id VARCHAR(100),
        external_id VARCHAR(100),
        reference VARCHAR(200),
        notes TEXT,
        approved_by INT,
        processed_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_type (payout_type),
        INDEX idx_batch (batch_id),
        INDEX idx_recipient (recipient_email)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS fin_payout_batches (
        id INT AUTO_INCREMENT PRIMARY KEY,
        batch_id VARCHAR(100) NOT NULL,
        provider VARCHAR(20) NOT NULL,
        total_amount_cents BIGINT DEFAULT 0,
        total_fee_cents BIGINT DEFAULT 0,
        payout_count INT DEFAULT 0,
        currency VARCHAR(10) DEFAULT 'USD',
        status ENUM('draft','submitted','processing','completed','failed') DEFAULT 'draft',
        external_batch_id VARCHAR(100),
        submitted_at TIMESTAMP NULL,
        completed_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_batch (batch_id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS fin_contractors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(200) NOT NULL,
        email VARCHAR(200) NOT NULL,
        payment_method ENUM('paypal','wise','deel','stripe','crypto') DEFAULT 'paypal',
        payment_details JSON,
        country VARCHAR(5),
        currency VARCHAR(10) DEFAULT 'USD',
        rate_cents BIGINT DEFAULT 0,
        rate_type ENUM('hourly','fixed','monthly') DEFAULT 'hourly',
        status ENUM('active','paused','terminated') DEFAULT 'active',
        total_paid_cents BIGINT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_email (email)
    )");
}
ensurePayoutsSchema();

// Tight rate limit for financial operations (5 per minute)
apiRateLimit(5, 60, 'financial_payouts');

// ─── Routing ──────────────────────────────────────────────────
$action = sanitize($_GET['action'] ?? $_POST['action'] ?? '', 50);

switch ($action) {
    // Payout Management
    case 'create':            finRequireAdminOrInternal(); createPayout(); break;
    case 'batch_create':      finRequireAdminOrInternal(); createBatch(); break;
    case 'batch_submit':      finRequireAdminOrInternal(); submitBatch(); break;
    case 'list':              finRequireAdminOrInternal(); listPayouts(); break;
    case 'batches':           finRequireAdminOrInternal(); listBatches(); break;
    case 'stats':             finRequireAdminOrInternal(); payoutStats(); break;

    // PayPal
    case 'paypal_auth':       finRequireAdminOrInternal(); paypalGetToken(); break;
    case 'paypal_payout':     finRequireAdminOrInternal(); paypalMassPayout(); break;

    // Deel
    case 'deel_contracts':    finRequireAdminOrInternal(); deelContracts(); break;
    case 'deel_create_contract': finRequireAdminOrInternal(); deelCreateContract(); break;
    case 'deel_pay':          finRequireAdminOrInternal(); deelPay(); break;

    // Contractors
    case 'contractor_add':    finRequireAdminOrInternal(); addContractor(); break;
    case 'contractor_list':   finRequireAdminOrInternal(); listContractors(); break;
    case 'contractor_pay':    finRequireAdminOrInternal(); payContractor(); break;

    // Affiliate Payouts
    case 'affiliate_pending': finRequireAdminOrInternal(); affiliatePending(); break;
    case 'affiliate_payout':  finRequireAdminOrInternal(); affiliatePayout(); break;

    default:
        jsonResponse(['error' => 'Invalid action', 'valid' => [
            'create','batch_create','batch_submit','list','batches','stats',
            'paypal_auth','paypal_payout',
            'deel_contracts','deel_create_contract','deel_pay',
            'contractor_add','contractor_list','contractor_pay',
            'affiliate_pending','affiliate_payout'
        ]], 400);
}

// ═══ PAYOUT MANAGEMENT ════════════════════════════════════════

function createPayout(): void {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $type = sanitize($input['payout_type'] ?? 'manual', 30);
    $provider = sanitize($input['provider'] ?? 'paypal', 20);
    $email = sanitize($input['recipient_email'] ?? '', 200);
    $name = sanitize($input['recipient_name'] ?? '', 200);
    $amountCents = (int) ($input['amount_cents'] ?? 0);
    $currency = sanitize($input['currency'] ?? 'USD', 10);
    $reference = sanitize($input['reference'] ?? '', 200);

    if ($amountCents <= 0) {
        jsonResponse(['error' => 'Positive amount_cents required'], 400);
    }
    if ($amountCents > finToCents(FIN_MAX_PAYOUT_USD)) {
        jsonResponse(['error' => 'Exceeds safety limit of $' . FIN_MAX_PAYOUT_USD], 403);
    }

    $db = getDB();
    $stmt = $db->prepare("INSERT INTO fin_payouts (payout_type, provider, recipient_email, recipient_name, amount_cents, currency, reference, approved_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$type, $provider, $email, $name, $amountCents, $currency, $reference, finGetClientId()]);

    finAuditLog('payout_create', 'payouts', ['type' => $type, 'amount' => finFromCents($amountCents), 'recipient' => $email]);

    jsonResponse(['success' => true, 'payout_id' => $db->lastInsertId(), 'amount' => finFromCents($amountCents)]);
}

function createBatch(): void {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $provider = sanitize($input['provider'] ?? 'paypal', 20);
    $items = $input['items'] ?? [];

    if (empty($items)) {
        jsonResponse(['error' => 'items array required'], 400);
    }

    $batchId = 'batch_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4));
    $totalCents = 0;
    $db = getDB();

    foreach ($items as $item) {
        $amountCents = (int) ($item['amount_cents'] ?? 0);
        if ($amountCents <= 0) continue;
        $totalCents += $amountCents;

        $stmt = $db->prepare("INSERT INTO fin_payouts (payout_type, provider, recipient_email, recipient_name, amount_cents, currency, batch_id, reference)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            sanitize($item['type'] ?? 'manual', 30), $provider,
            sanitize($item['email'] ?? '', 200), sanitize($item['name'] ?? '', 200),
            $amountCents, sanitize($item['currency'] ?? 'USD', 10),
            $batchId, sanitize($item['reference'] ?? '', 200)
        ]);
    }

    $stmt = $db->prepare("INSERT INTO fin_payout_batches (batch_id, provider, total_amount_cents, payout_count, currency)
        VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$batchId, $provider, $totalCents, count($items), 'USD']);

    finAuditLog('batch_create', 'payouts', ['batch_id' => $batchId, 'count' => count($items), 'total' => finFromCents($totalCents)]);

    jsonResponse(['success' => true, 'batch_id' => $batchId, 'count' => count($items), 'total' => finFromCents($totalCents)]);
}

function submitBatch(): void {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $batchId = sanitize($input['batch_id'] ?? '', 100);

    if (!$batchId) {
        jsonResponse(['error' => 'batch_id required'], 400);
    }

    $db = getDB();
    $batch = $db->prepare("SELECT * FROM fin_payout_batches WHERE batch_id = ?");
    $batch->execute([$batchId]);
    $b = $batch->fetch();

    if (!$b) {
        jsonResponse(['error' => 'Batch not found'], 404);
    }

    if ($b['status'] !== 'draft') {
        jsonResponse(['error' => 'Batch already submitted'], 400);
    }

    // Get all payouts in batch
    $payouts = $db->prepare("SELECT * FROM fin_payouts WHERE batch_id = ?");
    $payouts->execute([$batchId]);
    $items = $payouts->fetchAll();

    // Route to appropriate provider
    $result = match($b['provider']) {
        'paypal' => executePayPalBatch($items, $batchId),
        default => ['success' => false, 'error' => 'Unsupported batch provider: ' . $b['provider']],
    };

    if ($result['success']) {
        $db->prepare("UPDATE fin_payout_batches SET status='processing', submitted_at=NOW(), external_batch_id=? WHERE batch_id=?")
            ->execute([$result['external_id'] ?? null, $batchId]);
        $db->prepare("UPDATE fin_payouts SET status='processing' WHERE batch_id=?")
            ->execute([$batchId]);
    }

    finAuditLog('batch_submit', 'payouts', ['batch_id' => $batchId, 'provider' => $b['provider']]);

    jsonResponse($result);
}

function listPayouts(): void {
    $db = getDB();
    $status = sanitize($_GET['status'] ?? '', 20);
    $type = sanitize($_GET['type'] ?? '', 30);
    $limit = min((int) ($_GET['limit'] ?? 50), 200);

    $sql = "SELECT * FROM fin_payouts WHERE 1=1";
    $params = [];
    if ($status) { $sql .= " AND status = ?"; $params[] = $status; }
    if ($type) { $sql .= " AND payout_type = ?"; $params[] = $type; }
    $sql .= " ORDER BY created_at DESC LIMIT ?";
    $params[] = $limit;

    $stmt = $db->prepare($sql);
    dbExecute($stmt, $params);

    jsonResponse(['success' => true, 'payouts' => array_map(fn($p) => array_merge($p, ['amount' => finFromCents($p['amount_cents'])]), $stmt->fetchAll())]);
}

function listBatches(): void {
    $db = getDB();
    $batches = $db->query("SELECT * FROM fin_payout_batches ORDER BY created_at DESC LIMIT 50")->fetchAll();
    jsonResponse(['success' => true, 'batches' => array_map(fn($b) => array_merge($b, ['total' => finFromCents($b['total_amount_cents'])]), $batches)]);
}

function payoutStats(): void {
    $db = getDB();
    $stmt = $db->query("SELECT
        payout_type,
        status,
        COUNT(*) as count,
        SUM(amount_cents) as total_cents,
        AVG(amount_cents) as avg_cents
        FROM fin_payouts GROUP BY payout_type, status ORDER BY payout_type, status");

    jsonResponse(['success' => true, 'stats' => $stmt->fetchAll()]);
}

// ═══ PAYPAL MASS PAYOUTS ══════════════════════════════════════

function paypalGetToken(): array {
    $ch = curl_init(PAYPAL_API_URL . '/v1/oauth2/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_USERPWD => PAYPAL_CLIENT_ID . ':' . PAYPAL_SECRET,
    ]);
    $result = curl_exec($ch);
    curl_close($ch);
    $tokenData = json_decode($result, true);

    if (empty($tokenData['access_token'])) {
        jsonResponse(['error' => 'PayPal auth failed'], 500);
    }

    // Don't expose token directly unless called internally
    if (!finIsInternalCall()) {
        jsonResponse(['success' => true, 'authenticated' => true, 'expires_in' => $tokenData['expires_in'] ?? 0]);
    }

    return $tokenData;
}

function paypalMassPayout(): void {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $items = $input['items'] ?? [];

    if (empty($items)) {
        jsonResponse(['error' => 'items array required'], 400);
    }

    $tokenData = paypalGetToken();

    $payoutItems = [];
    foreach ($items as $i => $item) {
        $amountCents = (int) ($item['amount_cents'] ?? 0);
        if ($amountCents <= 0) continue;
        if ($amountCents > finToCents(FIN_MAX_PAYOUT_USD)) {
            jsonResponse(['error' => "Item {$i} exceeds safety limit"], 403);
        }

        $payoutItems[] = [
            'recipient_type' => 'EMAIL',
            'amount' => [
                'value' => number_format(finFromCents($amountCents), 2, '.', ''),
                'currency' => sanitize($item['currency'] ?? 'USD', 10),
            ],
            'receiver' => sanitize($item['email'] ?? '', 200),
            'note' => sanitize($item['note'] ?? 'GoSiteMe Payment', 200),
            'sender_item_id' => 'payout_' . $i . '_' . bin2hex(random_bytes(4)),
        ];
    }

    $response = finApiRequest(PAYPAL_API_URL . '/v1/payments/payouts', 'POST', [
        'sender_batch_header' => [
            'sender_batch_id' => 'GSM_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)),
            'email_subject' => 'GoSiteMe Payment',
            'email_message' => 'You received a payment from GoSiteMe.',
        ],
        'items' => $payoutItems,
    ], [
        'Authorization: Bearer ' . $tokenData['access_token'],
    ]);

    if (!$response['success']) {
        jsonResponse(['error' => 'PayPal payout failed', 'details' => $response['data']], 500);
    }

    finAuditLog('paypal_mass_payout', 'payouts', ['count' => count($payoutItems)]);

    jsonResponse([
        'success' => true,
        'batch_id' => $response['data']['batch_header']['payout_batch_id'] ?? null,
        'status' => $response['data']['batch_header']['batch_status'] ?? 'PENDING',
        'items_count' => count($payoutItems),
    ]);
}

function executePayPalBatch(array $items, string $batchId): array {
    $paypalItems = [];
    foreach ($items as $item) {
        $paypalItems[] = [
            'email' => $item['recipient_email'],
            'amount_cents' => $item['amount_cents'],
            'currency' => $item['currency'] ?? 'USD',
            'note' => $item['reference'] ?? 'GoSiteMe Payment',
        ];
    }

    // Reuse paypal mass payout logic
    $tokenData = ['access_token' => '']; // Will be fetched
    $ch = curl_init(PAYPAL_API_URL . '/v1/oauth2/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_USERPWD => PAYPAL_CLIENT_ID . ':' . PAYPAL_SECRET,
    ]);
    $result = curl_exec($ch);
    curl_close($ch);
    $tokenData = json_decode($result, true);

    if (empty($tokenData['access_token'])) {
        return ['success' => false, 'error' => 'PayPal auth failed'];
    }

    $ppItems = array_map(fn($item, $i) => [
        'recipient_type' => 'EMAIL',
        'amount' => ['value' => number_format(finFromCents($item['amount_cents']), 2, '.', ''), 'currency' => $item['currency']],
        'receiver' => $item['email'],
        'note' => $item['note'],
        'sender_item_id' => $batchId . '_' . $i,
    ], $paypalItems, array_keys($paypalItems));

    $response = finApiRequest(PAYPAL_API_URL . '/v1/payments/payouts', 'POST', [
        'sender_batch_header' => [
            'sender_batch_id' => $batchId,
            'email_subject' => 'GoSiteMe Payment',
        ],
        'items' => $ppItems,
    ], ['Authorization: Bearer ' . $tokenData['access_token']]);

    return [
        'success' => $response['success'],
        'external_id' => $response['data']['batch_header']['payout_batch_id'] ?? null,
        'status' => $response['data']['batch_header']['batch_status'] ?? 'UNKNOWN',
    ];
}

// ═══ DEEL ═════════════════════════════════════════════════════

function deelRequest(string $endpoint, string $method = 'GET', ?array $data = null): array {
    return finApiRequest(DEEL_API_URL . $endpoint, $method, $data, [
        'Authorization: Bearer ' . DEEL_API_KEY,
    ]);
}

function deelContracts(): void {
    $response = deelRequest('/contracts');
    jsonResponse(['success' => $response['success'], 'contracts' => $response['data']['data'] ?? $response['data'] ?? []]);
}

function deelCreateContract(): void {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $response = deelRequest('/contracts', 'POST', [
        'type' => 'ongoing',
        'title' => sanitize($input['title'] ?? 'Contractor Agreement', 200),
        'currency' => sanitize($input['currency'] ?? 'USD', 10),
        'scale' => sanitize($input['scale'] ?? 'monthly', 20),
        'amount' => (float) ($input['amount'] ?? 0),
        'contractor_tax_form' => sanitize($input['tax_form'] ?? 'w8_ben', 20),
        'client_legal_entity_id' => sanitize($input['legal_entity_id'] ?? '', 100),
    ]);

    if (!$response['success']) {
        jsonResponse(['error' => 'Deel contract creation failed', 'details' => $response['data']], 500);
    }

    finAuditLog('deel_contract', 'payouts', ['title' => $input['title'] ?? '']);
    jsonResponse(['success' => true, 'contract' => $response['data']]);
}

function deelPay(): void {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $contractId = sanitize($input['contract_id'] ?? '', 100);

    if (!$contractId) {
        jsonResponse(['error' => 'contract_id required'], 400);
    }

    $response = deelRequest("/contracts/{$contractId}/invoices", 'POST', [
        'description' => sanitize($input['description'] ?? 'Monthly payment', 200),
        'amount' => (float) ($input['amount'] ?? 0),
    ]);

    finAuditLog('deel_pay', 'payouts', ['contract_id' => $contractId]);
    jsonResponse(['success' => $response['success'], 'invoice' => $response['data'] ?? null]);
}

// ═══ CONTRACTORS ══════════════════════════════════════════════

function addContractor(): void {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $name = sanitize($input['name'] ?? '', 200);
    $email = sanitize($input['email'] ?? '', 200);
    $method = sanitize($input['payment_method'] ?? 'paypal', 20);
    $country = sanitize($input['country'] ?? '', 5);
    $rateCents = (int) ($input['rate_cents'] ?? 0);
    $rateType = sanitize($input['rate_type'] ?? 'hourly', 10);

    if (!$name || !$email) {
        jsonResponse(['error' => 'name and email required'], 400);
    }

    $db = getDB();
    $stmt = $db->prepare("INSERT INTO fin_contractors (name, email, payment_method, payment_details, country, currency, rate_cents, rate_type)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE name=VALUES(name), payment_method=VALUES(payment_method), rate_cents=VALUES(rate_cents)");
    $stmt->execute([$name, $email, $method, json_encode($input['payment_details'] ?? []),
        $country, sanitize($input['currency'] ?? 'USD', 10), $rateCents, $rateType]);

    finAuditLog('contractor_add', 'payouts', ['name' => $name, 'email' => $email]);
    jsonResponse(['success' => true, 'contractor_id' => $db->lastInsertId() ?: 'updated']);
}

function listContractors(): void {
    $db = getDB();
    $status = sanitize($_GET['status'] ?? 'active', 20);
    $stmt = $db->prepare("SELECT id, name, email, payment_method, country, currency, rate_cents, rate_type, status, total_paid_cents, created_at
        FROM fin_contractors WHERE status = ? ORDER BY name");
    $stmt->execute([$status]);
    jsonResponse(['success' => true, 'contractors' => $stmt->fetchAll()]);
}

function payContractor(): void {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $contractorId = (int) ($input['contractor_id'] ?? 0);
    $amountCents = (int) ($input['amount_cents'] ?? 0);

    if (!$contractorId || $amountCents <= 0) {
        jsonResponse(['error' => 'contractor_id and positive amount_cents required'], 400);
    }
    if ($amountCents > finToCents(FIN_MAX_PAYOUT_USD)) {
        jsonResponse(['error' => 'Exceeds safety limit of $' . FIN_MAX_PAYOUT_USD], 403);
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM fin_contractors WHERE id = ?");
    $stmt->execute([$contractorId]);
    $contractor = $stmt->fetch();

    if (!$contractor) {
        jsonResponse(['error' => 'Contractor not found'], 404);
    }

    // Create payout record
    $pStmt = $db->prepare("INSERT INTO fin_payouts (payout_type, provider, recipient_email, recipient_name, amount_cents, currency, reference)
        VALUES ('contractor', ?, ?, ?, ?, ?, ?)");
    $pStmt->execute([
        $contractor['payment_method'], $contractor['email'], $contractor['name'],
        $amountCents, $contractor['currency'],
        'Contractor payment - ' . date('Y-m-d')
    ]);

    // Update total paid
    $db->prepare("UPDATE fin_contractors SET total_paid_cents = total_paid_cents + ? WHERE id = ?")
        ->execute([$amountCents, $contractorId]);

    finAuditLog('contractor_pay', 'payouts', [
        'contractor' => $contractor['name'], 'amount' => finFromCents($amountCents)
    ]);

    jsonResponse([
        'success' => true,
        'payout_id' => $db->lastInsertId(),
        'contractor' => $contractor['name'],
        'amount' => finFromCents($amountCents),
        'method' => $contractor['payment_method'],
    ]);
}

// ═══ AFFILIATE PAYOUTS ════════════════════════════════════════

function affiliatePending(): void {
    $db = getDB();
    // Query existing affiliate commissions table
    try {
        $stmt = $db->query("SELECT
            a.id, a.affiliate_id, a.commission_amount, a.status, a.created_at,
            c.firstname, c.lastname, c.email
            FROM affiliate_commissions a
            JOIN clients c ON c.id = a.affiliate_id
            WHERE a.status = 'pending' OR a.status = 'approved'
            ORDER BY a.created_at DESC LIMIT 100");
        jsonResponse(['success' => true, 'pending' => $stmt->fetchAll()]);
    } catch (Exception $e) {
        // Table might not exist yet
        jsonResponse(['success' => true, 'pending' => [], 'note' => 'Affiliate commissions table not found']);
    }
}

function affiliatePayout(): void {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $affiliateIds = $input['affiliate_ids'] ?? [];
    $method = sanitize($input['method'] ?? 'paypal', 20);

    if (empty($affiliateIds)) {
        jsonResponse(['error' => 'affiliate_ids array required'], 400);
    }

    $db = getDB();
    $processed = 0;
    $totalCents = 0;

    foreach ($affiliateIds as $affId) {
        $affId = (int) $affId;
        try {
            $stmt = $db->prepare("SELECT SUM(commission_amount) as total, affiliate_id FROM affiliate_commissions
                WHERE affiliate_id = ? AND (status = 'pending' OR status = 'approved') GROUP BY affiliate_id");
            $stmt->execute([$affId]);
            $aff = $stmt->fetch();

            if ($aff && $aff['total'] > 0) {
                $amtCents = finToCents($aff['total']);
                $totalCents += $amtCents;

                // Get affiliate email
                $clientStmt = $db->prepare("SELECT email, firstname, lastname FROM clients WHERE id = ?");
                $clientStmt->execute([$affId]);
                $client = $clientStmt->fetch();

                // Create payout record
                $pStmt = $db->prepare("INSERT INTO fin_payouts (payout_type, provider, recipient_email, recipient_name, amount_cents, reference)
                    VALUES ('affiliate_commission', ?, ?, ?, ?, ?)");
                $pStmt->execute([
                    $method, $client['email'] ?? '', ($client['firstname'] ?? '') . ' ' . ($client['lastname'] ?? ''),
                    $amtCents, 'Affiliate commission payout'
                ]);

                // Mark commissions as paid
                $db->prepare("UPDATE affiliate_commissions SET status = 'paid' WHERE affiliate_id = ? AND (status = 'pending' OR status = 'approved')")
                    ->execute([$affId]);

                $processed++;
            }
        } catch (Exception $e) {
            continue;
        }
    }

    finAuditLog('affiliate_payout', 'payouts', ['processed' => $processed, 'total' => finFromCents($totalCents)]);

    jsonResponse([
        'success' => true,
        'processed' => $processed,
        'total' => finFromCents($totalCents),
        'method' => $method,
    ]);
}
