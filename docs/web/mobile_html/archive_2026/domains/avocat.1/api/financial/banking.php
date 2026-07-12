<?php
/**
 * Banking Integration API — Plaid, Mercury, Wise
 * ATLAS Agents: Treasurer (#38), Accountant (#41), Paymaster (#42), Forecaster (#45)
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
function ensureBankingSchema(): void {
    $db = getDB();

    $db->exec("CREATE TABLE IF NOT EXISTS fin_bank_connections (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL DEFAULT 0,
        provider ENUM('plaid','mercury','wise') NOT NULL,
        institution_name VARCHAR(100),
        access_token TEXT,
        item_id VARCHAR(100),
        account_ids JSON,
        status ENUM('active','disconnected','error') DEFAULT 'active',
        last_synced_at TIMESTAMP NULL,
        metadata JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_client (client_id),
        INDEX idx_provider (provider)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS fin_bank_accounts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        connection_id INT NOT NULL,
        client_id INT NOT NULL DEFAULT 0,
        provider VARCHAR(20) NOT NULL,
        account_id VARCHAR(100) NOT NULL,
        name VARCHAR(100),
        account_type VARCHAR(30),
        subtype VARCHAR(30),
        currency VARCHAR(10) DEFAULT 'USD',
        balance_current BIGINT DEFAULT 0,
        balance_available BIGINT DEFAULT 0,
        mask VARCHAR(10),
        last_updated TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_account (provider, account_id),
        INDEX idx_client (client_id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS fin_bank_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL DEFAULT 0,
        account_id VARCHAR(100) NOT NULL,
        provider VARCHAR(20) NOT NULL,
        transaction_id VARCHAR(100) NOT NULL,
        date DATE NOT NULL,
        amount_cents BIGINT NOT NULL,
        currency VARCHAR(10) DEFAULT 'USD',
        name VARCHAR(200),
        merchant_name VARCHAR(200),
        category VARCHAR(100),
        subcategory VARCHAR(100),
        pending TINYINT(1) DEFAULT 0,
        plaid_category_id VARCHAR(50),
        categorized_account VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_txn (provider, transaction_id),
        INDEX idx_date (date),
        INDEX idx_client (client_id),
        INDEX idx_account (account_id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS fin_fx_rates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        base_currency VARCHAR(10) NOT NULL,
        target_currency VARCHAR(10) NOT NULL,
        rate DECIMAL(18,8) NOT NULL,
        source VARCHAR(30) DEFAULT 'wise',
        fetched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_pair (base_currency, target_currency, fetched_at)
    )");
}
ensureBankingSchema();

// ─── Routing ──────────────────────────────────────────────────
$action = sanitize($_GET['action'] ?? $_POST['action'] ?? '', 50);

switch ($action) {
    // Plaid
    case 'plaid_link_token':    finRequireAuth(); plaidLinkToken(); break;
    case 'plaid_exchange':      finRequireAuth(); plaidExchange(); break;
    case 'plaid_accounts':      finRequireAuth(); plaidAccounts(); break;
    case 'plaid_balances':      finRequireAuth(); plaidBalances(); break;
    case 'plaid_transactions':  finRequireAuth(); plaidTransactions(); break;
    case 'plaid_identity':      finRequireAuth(); plaidIdentity(); break;

    // Mercury
    case 'mercury_accounts':    finRequireAdminOrInternal(); mercuryAccounts(); break;
    case 'mercury_balance':     finRequireAdminOrInternal(); mercuryBalance(); break;
    case 'mercury_transactions':finRequireAdminOrInternal(); mercuryTransactions(); break;
    case 'mercury_transfer':    finRequireAdminOrInternal(); mercuryTransfer(); break;
    case 'mercury_recipients':  finRequireAdminOrInternal(); mercuryRecipients(); break;

    // Wise
    case 'wise_profiles':       finRequireAdminOrInternal(); wiseProfiles(); break;
    case 'wise_balances':       finRequireAdminOrInternal(); wiseBalances(); break;
    case 'wise_quote':          finRequireAdminOrInternal(); wiseQuote(); break;
    case 'wise_transfer':       finRequireAdminOrInternal(); wiseTransfer(); break;
    case 'wise_rates':          wiseRates(); break;

    // Aggregate
    case 'all_balances':        finRequireAuth(); allBalances(); break;
    case 'connections':         finRequireAuth(); listConnections(); break;

    default:
        jsonResponse(['error' => 'Invalid action', 'valid' => [
            'plaid_link_token','plaid_exchange','plaid_accounts','plaid_balances','plaid_transactions','plaid_identity',
            'mercury_accounts','mercury_balance','mercury_transactions','mercury_transfer','mercury_recipients',
            'wise_profiles','wise_balances','wise_quote','wise_transfer','wise_rates',
            'all_balances','connections'
        ]], 400);
}

// ═══ PLAID ════════════════════════════════════════════════════

function plaidRequest(string $endpoint, array $body): array {
    $body['client_id'] = PLAID_CLIENT_ID;
    $body['secret'] = PLAID_SECRET;
    return finApiRequest(PLAID_API_URL . $endpoint, 'POST', $body);
}

function plaidLinkToken(): void {
    $clientId = finGetClientId();
    $response = plaidRequest('/link/token/create', [
        'user' => ['client_user_id' => "gositeme_$clientId"],
        'client_name' => SITE_NAME,
        'products' => ['auth', 'transactions'],
        'country_codes' => ['US', 'CA'],
        'language' => 'en',
    ]);

    if (!$response['success']) {
        jsonResponse(['error' => 'Plaid Link failed', 'details' => $response['data']], 500);
    }

    jsonResponse(['success' => true, 'link_token' => $response['data']['link_token']]);
}

function plaidExchange(): void {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $publicToken = $input['public_token'] ?? '';

    if (!$publicToken) {
        jsonResponse(['error' => 'public_token required'], 400);
    }

    $response = plaidRequest('/item/public_token/exchange', [
        'public_token' => $publicToken,
    ]);

    if (!$response['success']) {
        jsonResponse(['error' => 'Token exchange failed'], 500);
    }

    $accessToken = $response['data']['access_token'];
    $itemId = $response['data']['item_id'];

    // Get institution info
    $itemInfo = plaidRequest('/item/get', ['access_token' => $accessToken]);
    $instId = $itemInfo['data']['item']['institution_id'] ?? '';
    $instName = 'Unknown Bank';
    if ($instId) {
        $instResp = plaidRequest('/institutions/get_by_id', [
            'institution_id' => $instId,
            'country_codes' => ['US', 'CA'],
        ]);
        $instName = $instResp['data']['institution']['name'] ?? 'Unknown Bank';
    }

    // Get accounts
    $acctResp = plaidRequest('/accounts/get', ['access_token' => $accessToken]);
    $accountIds = array_map(fn($a) => $a['account_id'], $acctResp['data']['accounts'] ?? []);

    // Store connection
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO fin_bank_connections (client_id, provider, institution_name, access_token, item_id, account_ids)
        VALUES (?, 'plaid', ?, ?, ?, ?)");
    $stmt->execute([finGetClientId(), $instName, $accessToken, $itemId, json_encode($accountIds)]);
    $connId = $db->lastInsertId();

    // Store accounts
    foreach ($acctResp['data']['accounts'] ?? [] as $acct) {
        $stmt = $db->prepare("INSERT INTO fin_bank_accounts (connection_id, client_id, provider, account_id, name, account_type, subtype, currency, balance_current, balance_available, mask)
            VALUES (?, ?, 'plaid', ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE balance_current=VALUES(balance_current), balance_available=VALUES(balance_available), last_updated=NOW()");
        $stmt->execute([
            $connId, finGetClientId(), $acct['account_id'], $acct['name'],
            $acct['type'], $acct['subtype'] ?? null,
            $acct['balances']['iso_currency_code'] ?? 'USD',
            finToCents($acct['balances']['current'] ?? 0),
            finToCents($acct['balances']['available'] ?? 0),
            $acct['mask'] ?? null,
        ]);
    }

    finAuditLog('plaid_connect', 'banking', ['institution' => $instName, 'accounts' => count($accountIds)]);

    jsonResponse([
        'success' => true,
        'institution' => $instName,
        'accounts_connected' => count($accountIds),
    ]);
}

function plaidAccounts(): void {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM fin_bank_accounts WHERE client_id = ? AND provider = 'plaid' ORDER BY name");
    $stmt->execute([finGetClientId()]);
    $accounts = $stmt->fetchAll();

    jsonResponse(['success' => true, 'accounts' => array_map(fn($a) => [
        'account_id' => $a['account_id'],
        'name' => $a['name'],
        'type' => $a['account_type'],
        'subtype' => $a['subtype'],
        'balance' => finFromCents($a['balance_current']),
        'available' => finFromCents($a['balance_available']),
        'currency' => $a['currency'],
        'mask' => $a['mask'],
        'last_updated' => $a['last_updated'],
    ], $accounts)]);
}

function plaidBalances(): void {
    $db = getDB();
    $conn = $db->prepare("SELECT access_token FROM fin_bank_connections WHERE client_id = ? AND provider = 'plaid' AND status = 'active'");
    $conn->execute([finGetClientId()]);
    $connections = $conn->fetchAll();

    $allBalances = [];
    foreach ($connections as $c) {
        $response = plaidRequest('/accounts/balance/get', ['access_token' => $c['access_token']]);
        if ($response['success']) {
            foreach ($response['data']['accounts'] ?? [] as $acct) {
                // Update local cache
                $stmt = $db->prepare("UPDATE fin_bank_accounts SET balance_current=?, balance_available=?, last_updated=NOW()
                    WHERE provider='plaid' AND account_id=?");
                $stmt->execute([
                    finToCents($acct['balances']['current'] ?? 0),
                    finToCents($acct['balances']['available'] ?? 0),
                    $acct['account_id']
                ]);

                $allBalances[] = [
                    'account_id' => $acct['account_id'],
                    'name' => $acct['name'],
                    'current' => $acct['balances']['current'],
                    'available' => $acct['balances']['available'],
                    'currency' => $acct['balances']['iso_currency_code'] ?? 'USD',
                ];
            }
        }
    }

    jsonResponse(['success' => true, 'balances' => $allBalances]);
}

function plaidTransactions(): void {
    $db = getDB();
    $from = sanitize($_GET['from'] ?? date('Y-m-01'), 10);
    $to = sanitize($_GET['to'] ?? date('Y-m-d'), 10);
    $accountId = sanitize($_GET['account_id'] ?? '', 100);

    $conn = $db->prepare("SELECT access_token FROM fin_bank_connections WHERE client_id = ? AND provider = 'plaid' AND status = 'active'");
    $conn->execute([finGetClientId()]);
    $connections = $conn->fetchAll();

    $allTxns = [];
    foreach ($connections as $c) {
        $body = [
            'access_token' => $c['access_token'],
            'start_date' => $from,
            'end_date' => $to,
            'options' => ['count' => 100],
        ];
        if ($accountId) {
            $body['options']['account_ids'] = [$accountId];
        }

        $response = plaidRequest('/transactions/get', $body);
        if ($response['success']) {
            foreach ($response['data']['transactions'] ?? [] as $txn) {
                // Store locally
                $stmt = $db->prepare("INSERT INTO fin_bank_transactions
                    (client_id, account_id, provider, transaction_id, date, amount_cents, currency, name, merchant_name, category, pending)
                    VALUES (?, ?, 'plaid', ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE amount_cents=VALUES(amount_cents), pending=VALUES(pending)");
                $stmt->execute([
                    finGetClientId(), $txn['account_id'], $txn['transaction_id'],
                    $txn['date'], finToCents($txn['amount']),
                    $txn['iso_currency_code'] ?? 'USD', $txn['name'],
                    $txn['merchant_name'] ?? null,
                    implode(' > ', $txn['category'] ?? []),
                    $txn['pending'] ? 1 : 0,
                ]);

                $allTxns[] = [
                    'id' => $txn['transaction_id'],
                    'date' => $txn['date'],
                    'amount' => $txn['amount'],
                    'name' => $txn['name'],
                    'merchant' => $txn['merchant_name'],
                    'category' => $txn['category'] ?? [],
                    'pending' => $txn['pending'],
                ];
            }
        }
    }

    jsonResponse(['success' => true, 'count' => count($allTxns), 'transactions' => $allTxns]);
}

function plaidIdentity(): void {
    $db = getDB();
    $conn = $db->prepare("SELECT access_token FROM fin_bank_connections WHERE client_id = ? AND provider = 'plaid' AND status = 'active' LIMIT 1");
    $conn->execute([finGetClientId()]);
    $c = $conn->fetch();

    if (!$c) {
        jsonResponse(['error' => 'No Plaid connection found'], 404);
    }

    $response = plaidRequest('/identity/get', ['access_token' => $c['access_token']]);
    if (!$response['success']) {
        jsonResponse(['error' => 'Identity retrieval failed'], 500);
    }

    jsonResponse(['success' => true, 'identity' => $response['data']]);
}

// ═══ MERCURY ══════════════════════════════════════════════════

function mercuryRequest(string $endpoint, string $method = 'GET', ?array $data = null): array {
    return finApiRequest(MERCURY_API_URL . $endpoint, $method, $data, [
        'Authorization: Bearer ' . MERCURY_API_KEY,
    ]);
}

function mercuryAccounts(): void {
    $response = mercuryRequest('/accounts');
    if (!$response['success']) {
        jsonResponse(['error' => 'Mercury API failed', 'details' => $response['data']], 500);
    }

    $accounts = $response['data']['accounts'] ?? $response['data'] ?? [];
    $db = getDB();
    foreach ($accounts as $acct) {
        $stmt = $db->prepare("INSERT INTO fin_bank_accounts (connection_id, client_id, provider, account_id, name, account_type, currency, balance_current)
            VALUES (0, 0, 'mercury', ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE balance_current=VALUES(balance_current), last_updated=NOW()");
        $stmt->execute([
            $acct['id'] ?? '', $acct['name'] ?? 'Mercury Account',
            $acct['type'] ?? 'checking', $acct['currency'] ?? 'USD',
            finToCents($acct['currentBalance'] ?? 0),
        ]);
    }

    jsonResponse(['success' => true, 'accounts' => $accounts]);
}

function mercuryBalance(): void {
    $response = mercuryRequest('/accounts');
    if (!$response['success']) {
        jsonResponse(['error' => 'Mercury API failed'], 500);
    }

    $accounts = $response['data']['accounts'] ?? $response['data'] ?? [];
    $total = 0;
    $balances = [];
    foreach ($accounts as $acct) {
        $bal = $acct['currentBalance'] ?? 0;
        $total += $bal;
        $balances[] = [
            'id' => $acct['id'] ?? '',
            'name' => $acct['name'] ?? 'Account',
            'balance' => $bal,
            'currency' => $acct['currency'] ?? 'USD',
        ];
    }

    jsonResponse(['success' => true, 'total' => $total, 'accounts' => $balances]);
}

function mercuryTransactions(): void {
    $accountId = sanitize($_GET['account_id'] ?? '', 100);
    $limit = min((int) ($_GET['limit'] ?? 50), 200);

    if (!$accountId) {
        // Get first account
        $resp = mercuryRequest('/accounts');
        $accounts = $resp['data']['accounts'] ?? $resp['data'] ?? [];
        $accountId = $accounts[0]['id'] ?? '';
    }

    if (!$accountId) {
        jsonResponse(['error' => 'No Mercury account found'], 404);
    }

    $response = mercuryRequest("/account/{$accountId}/transactions?limit={$limit}");
    if (!$response['success']) {
        jsonResponse(['error' => 'Failed to fetch Mercury transactions'], 500);
    }

    jsonResponse(['success' => true, 'transactions' => $response['data']['transactions'] ?? $response['data'] ?? []]);
}

function mercuryTransfer(): void {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $accountId = sanitize($input['account_id'] ?? '', 100);
    $recipientId = sanitize($input['recipient_id'] ?? '', 100);
    $amount = (float) ($input['amount'] ?? 0);
    $note = sanitize($input['note'] ?? '', 200);

    if (!$accountId || !$recipientId || $amount <= 0) {
        jsonResponse(['error' => 'account_id, recipient_id, and positive amount required'], 400);
    }

    if ($amount > FIN_MAX_PAYOUT_USD) {
        jsonResponse(['error' => 'Transfer exceeds safety limit of $' . FIN_MAX_PAYOUT_USD], 403);
    }

    $response = mercuryRequest("/account/{$accountId}/transactions", 'POST', [
        'recipientId' => $recipientId,
        'amount' => $amount,
        'paymentMethod' => 'ach',
        'note' => $note,
        'idempotencyKey' => bin2hex(random_bytes(16)),
    ]);

    if (!$response['success']) {
        jsonResponse(['error' => 'Mercury transfer failed', 'details' => $response['data']], 500);
    }

    finAuditLog('mercury_transfer', 'banking', [
        'amount' => $amount, 'recipient' => $recipientId
    ]);

    jsonResponse(['success' => true, 'transfer' => $response['data']]);
}

function mercuryRecipients(): void {
    $response = mercuryRequest('/recipients');
    jsonResponse(['success' => $response['success'], 'recipients' => $response['data']['recipients'] ?? $response['data'] ?? []]);
}

// ═══ WISE ═════════════════════════════════════════════════════

function wiseRequest(string $endpoint, string $method = 'GET', ?array $data = null): array {
    return finApiRequest(WISE_API_URL . $endpoint, $method, $data, [
        'Authorization: Bearer ' . WISE_API_KEY,
    ]);
}

function wiseProfiles(): void {
    $response = wiseRequest('/v2/profiles');
    jsonResponse(['success' => $response['success'], 'profiles' => $response['data'] ?? []]);
}

function wiseBalances(): void {
    $profileId = WISE_PROFILE_ID ?: (sanitize($_GET['profile_id'] ?? '', 50));
    if (!$profileId) {
        jsonResponse(['error' => 'Profile ID required'], 400);
    }

    $response = wiseRequest("/v4/profiles/{$profileId}/balances?types=STANDARD");
    if (!$response['success']) {
        jsonResponse(['error' => 'Wise balance fetch failed'], 500);
    }

    $balances = array_map(fn($b) => [
        'id' => $b['id'] ?? '',
        'currency' => $b['currency'] ?? '',
        'amount' => $b['amount']['value'] ?? 0,
        'type' => $b['type'] ?? 'STANDARD',
    ], $response['data'] ?? []);

    jsonResponse(['success' => true, 'balances' => $balances]);
}

function wiseQuote(): void {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $sourceCurrency = sanitize($input['source_currency'] ?? 'CAD', 10);
    $targetCurrency = sanitize($input['target_currency'] ?? 'USD', 10);
    $sourceAmount = (float) ($input['source_amount'] ?? 0);
    $targetAmount = (float) ($input['target_amount'] ?? 0);

    if ($sourceAmount <= 0 && $targetAmount <= 0) {
        jsonResponse(['error' => 'source_amount or target_amount required'], 400);
    }

    $profileId = WISE_PROFILE_ID ?: sanitize($input['profile_id'] ?? '', 50);
    $body = [
        'sourceCurrency' => strtoupper($sourceCurrency),
        'targetCurrency' => strtoupper($targetCurrency),
        'profileId' => (int) $profileId,
    ];
    if ($sourceAmount > 0) {
        $body['sourceAmount'] = $sourceAmount;
    } else {
        $body['targetAmount'] = $targetAmount;
    }

    $response = wiseRequest('/v3/quotes', 'POST', $body);
    if (!$response['success']) {
        jsonResponse(['error' => 'Wise quote failed', 'details' => $response['data']], 500);
    }

    $q = $response['data'];
    jsonResponse([
        'success' => true,
        'quote' => [
            'id' => $q['id'] ?? '',
            'source' => ['currency' => $sourceCurrency, 'amount' => $q['sourceAmount'] ?? $sourceAmount],
            'target' => ['currency' => $targetCurrency, 'amount' => $q['targetAmount'] ?? 0],
            'rate' => $q['rate'] ?? 0,
            'fee' => $q['fee'] ?? 0,
            'delivery' => $q['deliveryEstimate'] ?? null,
        ]
    ]);
}

function wiseTransfer(): void {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $quoteId = sanitize($input['quote_id'] ?? '', 100);
    $recipientId = (int) ($input['recipient_id'] ?? 0);
    $reference = sanitize($input['reference'] ?? 'GoSiteMe Payment', 100);

    if (!$quoteId || !$recipientId) {
        jsonResponse(['error' => 'quote_id and recipient_id required'], 400);
    }

    $response = wiseRequest('/v1/transfers', 'POST', [
        'targetAccount' => $recipientId,
        'quoteUuid' => $quoteId,
        'customerTransactionId' => bin2hex(random_bytes(16)),
        'details' => ['reference' => $reference],
    ]);

    if (!$response['success']) {
        jsonResponse(['error' => 'Wise transfer failed', 'details' => $response['data']], 500);
    }

    finAuditLog('wise_transfer', 'banking', [
        'quote_id' => $quoteId, 'recipient' => $recipientId
    ]);

    jsonResponse(['success' => true, 'transfer' => $response['data']]);
}

function wiseRates(): void {
    $source = sanitize($_GET['source'] ?? 'CAD', 10);
    $target = sanitize($_GET['target'] ?? 'USD', 10);

    $response = wiseRequest("/v1/rates?source={$source}&target={$target}");
    if ($response['success'] && !empty($response['data'])) {
        $rate = $response['data'][0] ?? [];
        // Cache rate
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO fin_fx_rates (base_currency, target_currency, rate, source)
            VALUES (?, ?, ?, 'wise')
            ON DUPLICATE KEY UPDATE rate=VALUES(rate)");
        $stmt->execute([$source, $target, $rate['rate'] ?? 0]);

        jsonResponse(['success' => true, 'rate' => $rate['rate'] ?? 0, 'source' => $source, 'target' => $target, 'time' => $rate['time'] ?? null]);
    } else {
        jsonResponse(['error' => 'Rate fetch failed'], 500);
    }
}

// ═══ AGGREGATE ════════════════════════════════════════════════

function allBalances(): void {
    $db = getDB();
    $clientId = finGetClientId();

    // Get all cached bank balances
    $stmt = $db->prepare("SELECT provider, name, account_type, currency, balance_current, balance_available, last_updated
        FROM fin_bank_accounts WHERE client_id = ? OR client_id = 0 ORDER BY provider, name");
    $stmt->execute([$clientId]);
    $accounts = $stmt->fetchAll();

    $byProvider = [];
    $totalUSD = 0;
    foreach ($accounts as $acct) {
        $provider = $acct['provider'];
        if (!isset($byProvider[$provider])) {
            $byProvider[$provider] = ['accounts' => [], 'total' => 0];
        }
        $balance = finFromCents($acct['balance_current']);
        $byProvider[$provider]['accounts'][] = [
            'name' => $acct['name'],
            'type' => $acct['account_type'],
            'balance' => $balance,
            'currency' => $acct['currency'],
            'last_updated' => $acct['last_updated'],
        ];
        $byProvider[$provider]['total'] += $balance;
        $totalUSD += $balance; // Simplified — should convert via FX rates
    }

    jsonResponse([
        'success' => true,
        'providers' => $byProvider,
        'total_usd_approx' => $totalUSD,
        'note' => 'Total is approximate — multi-currency conversion not applied',
    ]);
}

function listConnections(): void {
    $db = getDB();
    $clientId = finGetClientId();
    $stmt = $db->prepare("SELECT id, provider, institution_name, status, last_synced_at, created_at
        FROM fin_bank_connections WHERE client_id = ? OR client_id = 0 ORDER BY created_at DESC");
    $stmt->execute([$clientId]);

    jsonResponse(['success' => true, 'connections' => $stmt->fetchAll()]);
}
