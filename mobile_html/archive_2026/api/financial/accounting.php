<?php
/**
 * Accounting Integration API — Xero + QuickBooks Online
 * ATLAS Agents: Accountant (#41), Invoicer (#39), Auditor-F (#46), Forecaster (#45)
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
function ensureAccountingSchema(): void {
    $db = getDB();

    $db->exec("CREATE TABLE IF NOT EXISTS fin_accounting_sync (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        provider ENUM('xero','quickbooks') NOT NULL,
        entity_type VARCHAR(50) NOT NULL,
        local_id VARCHAR(100) NOT NULL,
        remote_id VARCHAR(100) NOT NULL,
        last_synced_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        sync_status ENUM('synced','pending','error') DEFAULT 'synced',
        error_message TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_provider (provider),
        INDEX idx_local (local_id),
        UNIQUE KEY uk_sync (provider, entity_type, local_id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS fin_journal_entries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT DEFAULT 0,
        entry_date DATE NOT NULL,
        description TEXT,
        debit_account VARCHAR(100) NOT NULL,
        credit_account VARCHAR(100) NOT NULL,
        amount_cents BIGINT NOT NULL,
        currency VARCHAR(10) DEFAULT 'USD',
        reference VARCHAR(100),
        source VARCHAR(50),
        remote_id VARCHAR(100),
        synced_to ENUM('none','xero','quickbooks','both') DEFAULT 'none',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_date (entry_date),
        INDEX idx_accounts (debit_account, credit_account)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS fin_chart_of_accounts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(20) NOT NULL,
        name VARCHAR(100) NOT NULL,
        account_type ENUM('asset','liability','equity','revenue','expense') NOT NULL,
        sub_type VARCHAR(50),
        description TEXT,
        is_active TINYINT(1) DEFAULT 1,
        xero_account_id VARCHAR(50),
        qbo_account_id VARCHAR(50),
        parent_code VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_code (code)
    )");

    // Seed default chart of accounts
    $check = $db->query("SELECT COUNT(*) FROM fin_chart_of_accounts")->fetchColumn();
    if ($check == 0) {
        $db->exec("INSERT INTO fin_chart_of_accounts (code, name, account_type, sub_type) VALUES
            ('1000', 'Cash and Bank', 'asset', 'bank'),
            ('1100', 'Accounts Receivable', 'asset', 'receivable'),
            ('1200', 'Crypto Holdings', 'asset', 'other_asset'),
            ('1300', 'Prepaid Expenses', 'asset', 'prepaid'),
            ('2000', 'Accounts Payable', 'liability', 'payable'),
            ('2100', 'Sales Tax Payable', 'liability', 'tax'),
            ('2200', 'Deferred Revenue', 'liability', 'deferred'),
            ('3000', 'Owner Equity', 'equity', 'equity'),
            ('3100', 'Retained Earnings', 'equity', 'retained'),
            ('4000', 'Subscription Revenue', 'revenue', 'subscription'),
            ('4100', 'API Usage Revenue', 'revenue', 'usage'),
            ('4200', 'Voice Revenue', 'revenue', 'voice'),
            ('4300', 'Domain Revenue', 'revenue', 'domains'),
            ('4400', 'Marketplace Revenue', 'revenue', 'marketplace'),
            ('4500', 'Trading Revenue', 'revenue', 'trading'),
            ('4600', 'Hosting Revenue', 'revenue', 'hosting'),
            ('5000', 'Server & Infrastructure', 'expense', 'infrastructure'),
            ('5100', 'Payment Processing Fees', 'expense', 'fees'),
            ('5200', 'API & Service Costs', 'expense', 'services'),
            ('5300', 'Marketing & Advertising', 'expense', 'marketing'),
            ('5400', 'Contractor Payments', 'expense', 'contractors'),
            ('5500', 'Software Licenses', 'expense', 'software'),
            ('5600', 'Trading Losses', 'expense', 'trading'),
            ('5700', 'Office & Admin', 'expense', 'admin')
        ");
    }
}
ensureAccountingSchema();

// ─── Routing ──────────────────────────────────────────────────
$action = sanitize($_GET['action'] ?? $_POST['action'] ?? '', 50);

switch ($action) {
    // Chart of Accounts
    case 'accounts':         finRequireAuth(); getAccounts(); break;
    case 'account_create':   finRequireAdminOrInternal(); createAccount(); break;

    // Journal Entries (double-entry bookkeeping)
    case 'journal_create':   finRequireAdminOrInternal(); createJournalEntry(); break;
    case 'journal_list':     finRequireAuth(); listJournalEntries(); break;

    // Financial Reports
    case 'profit_loss':      finRequireAuth(); profitAndLoss(); break;
    case 'balance_sheet':    finRequireAuth(); balanceSheet(); break;
    case 'trial_balance':    finRequireAuth(); trialBalance(); break;
    case 'cash_flow':        finRequireAuth(); cashFlowStatement(); break;

    // Xero Integration
    case 'xero_connect':     finRequireAuth(); xeroConnect(); break;
    case 'xero_callback':    xeroCallback(); break;
    case 'xero_sync':        finRequireAdminOrInternal(); xeroSync(); break;
    case 'xero_invoices':    finRequireAuth(); xeroGetInvoices(); break;
    case 'xero_create_invoice': finRequireAuth(); xeroCreateInvoice(); break;

    // QuickBooks Integration
    case 'qbo_connect':      finRequireAuth(); qboConnect(); break;
    case 'qbo_callback':     qboCallback(); break;
    case 'qbo_sync':         finRequireAdminOrInternal(); qboSync(); break;
    case 'qbo_profit_loss':  finRequireAuth(); qboProfitLoss(); break;

    // Auto-bookkeeping
    case 'auto_categorize':  finRequireAdminOrInternal(); autoCategorize(); break;
    case 'reconcile':        finRequireAdminOrInternal(); reconcile(); break;

    default:
        jsonResponse(['error' => 'Invalid action', 'valid' => [
            'accounts','account_create','journal_create','journal_list',
            'profit_loss','balance_sheet','trial_balance','cash_flow',
            'xero_connect','xero_callback','xero_sync','xero_invoices','xero_create_invoice',
            'qbo_connect','qbo_callback','qbo_sync','qbo_profit_loss',
            'auto_categorize','reconcile'
        ]], 400);
}

// ═══ CHART OF ACCOUNTS ════════════════════════════════════════

function getAccounts(): void {
    $db = getDB();
    $type = sanitize($_GET['type'] ?? '', 20);
    $sql = "SELECT * FROM fin_chart_of_accounts WHERE is_active = 1";
    $params = [];
    if ($type) { $sql .= " AND account_type = ?"; $params[] = $type; }
    $sql .= " ORDER BY code";
    $stmt = $db->prepare($sql);
    dbExecute($stmt, $params);
    jsonResponse(['success' => true, 'accounts' => $stmt->fetchAll()]);
}

function createAccount(): void {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $code = sanitize($input['code'] ?? '', 20);
    $name = sanitize($input['name'] ?? '', 100);
    $type = sanitize($input['account_type'] ?? '', 20);

    if (!$code || !$name || !$type) {
        jsonResponse(['error' => 'code, name, and account_type required'], 400);
    }

    $db = getDB();
    $stmt = $db->prepare("INSERT INTO fin_chart_of_accounts (code, name, account_type, sub_type, description)
        VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE name=VALUES(name)");
    $stmt->execute([$code, $name, $type, sanitize($input['sub_type'] ?? '', 50), sanitize($input['description'] ?? '', 500)]);

    finAuditLog('account_create', 'accounting', ['code' => $code, 'name' => $name]);
    jsonResponse(['success' => true, 'code' => $code, 'name' => $name]);
}

// ═══ JOURNAL ENTRIES ══════════════════════════════════════════

function createJournalEntry(): void {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $date = sanitize($input['date'] ?? date('Y-m-d'), 10);
    $description = sanitize($input['description'] ?? '', 500);
    $debit = sanitize($input['debit_account'] ?? '', 100);
    $credit = sanitize($input['credit_account'] ?? '', 100);
    $amountCents = (int) ($input['amount_cents'] ?? 0);
    $currency = sanitize($input['currency'] ?? 'USD', 10);
    $reference = sanitize($input['reference'] ?? '', 100);
    $source = sanitize($input['source'] ?? 'manual', 50);

    if (!$debit || !$credit || $amountCents <= 0) {
        jsonResponse(['error' => 'debit_account, credit_account, and positive amount_cents required'], 400);
    }

    $db = getDB();
    $stmt = $db->prepare("INSERT INTO fin_journal_entries (client_id, entry_date, description, debit_account, credit_account, amount_cents, currency, reference, source)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([finGetClientId(), $date, $description, $debit, $credit, $amountCents, $currency, $reference, $source]);

    finAuditLog('journal_create', 'accounting', [
        'debit' => $debit, 'credit' => $credit, 'amount' => finFromCents($amountCents)
    ]);

    jsonResponse(['success' => true, 'entry_id' => $db->lastInsertId()]);
}

function listJournalEntries(): void {
    $db = getDB();
    $from = sanitize($_GET['from'] ?? date('Y-m-01'), 10);
    $to = sanitize($_GET['to'] ?? date('Y-m-d'), 10);
    $account = sanitize($_GET['account'] ?? '', 100);
    $limit = min((int) ($_GET['limit'] ?? 50), 200);
    $offset = (int) ($_GET['offset'] ?? 0);

    $sql = "SELECT * FROM fin_journal_entries WHERE entry_date BETWEEN ? AND ?";
    $params = [$from, $to];
    if ($account) {
        $sql .= " AND (debit_account = ? OR credit_account = ?)";
        $params[] = $account;
        $params[] = $account;
    }
    $sql .= " ORDER BY entry_date DESC, id DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    $stmt = $db->prepare($sql);
    dbExecute($stmt, $params);
    jsonResponse(['success' => true, 'entries' => $stmt->fetchAll(), 'from' => $from, 'to' => $to]);
}

// ═══ FINANCIAL REPORTS ════════════════════════════════════════

function profitAndLoss(): void {
    $db = getDB();
    $from = sanitize($_GET['from'] ?? date('Y-01-01'), 10);
    $to = sanitize($_GET['to'] ?? date('Y-m-d'), 10);

    // Revenue (credit side of revenue accounts)
    $revStmt = $db->prepare("SELECT credit_account as account, SUM(amount_cents) as total
        FROM fin_journal_entries WHERE entry_date BETWEEN ? AND ?
        AND credit_account IN (SELECT code FROM fin_chart_of_accounts WHERE account_type = 'revenue')
        GROUP BY credit_account ORDER BY total DESC");
    $revStmt->execute([$from, $to]);
    $revenue = $revStmt->fetchAll();

    // Expenses (debit side of expense accounts)
    $expStmt = $db->prepare("SELECT debit_account as account, SUM(amount_cents) as total
        FROM fin_journal_entries WHERE entry_date BETWEEN ? AND ?
        AND debit_account IN (SELECT code FROM fin_chart_of_accounts WHERE account_type = 'expense')
        GROUP BY debit_account ORDER BY total DESC");
    $expStmt->execute([$from, $to]);
    $expenses = $expStmt->fetchAll();

    $totalRevenue = array_sum(array_column($revenue, 'total'));
    $totalExpenses = array_sum(array_column($expenses, 'total'));
    $netIncome = $totalRevenue - $totalExpenses;

    // Enrich with account names
    $accounts = $db->query("SELECT code, name FROM fin_chart_of_accounts")->fetchAll(PDO::FETCH_KEY_PAIR);

    jsonResponse([
        'success' => true,
        'period' => ['from' => $from, 'to' => $to],
        'revenue' => array_map(fn($r) => [
            'account' => $r['account'], 'name' => $accounts[$r['account']] ?? $r['account'],
            'amount' => finFromCents($r['total'])
        ], $revenue),
        'expenses' => array_map(fn($e) => [
            'account' => $e['account'], 'name' => $accounts[$e['account']] ?? $e['account'],
            'amount' => finFromCents($e['total'])
        ], $expenses),
        'total_revenue' => finFromCents($totalRevenue),
        'total_expenses' => finFromCents($totalExpenses),
        'net_income' => finFromCents($netIncome),
        'margin' => $totalRevenue > 0 ? round(($netIncome / $totalRevenue) * 100, 2) : 0,
    ]);
}

function balanceSheet(): void {
    $db = getDB();
    $asOf = sanitize($_GET['as_of'] ?? date('Y-m-d'), 10);

    $types = ['asset', 'liability', 'equity'];
    $result = [];

    foreach ($types as $type) {
        $stmt = $db->prepare("SELECT
            CASE WHEN ca.account_type = ? THEN
                CASE WHEN ? IN ('asset','expense') THEN
                    COALESCE(SUM(CASE WHEN j.debit_account = ca.code THEN j.amount_cents ELSE 0 END), 0) -
                    COALESCE(SUM(CASE WHEN j.credit_account = ca.code THEN j.amount_cents ELSE 0 END), 0)
                ELSE
                    COALESCE(SUM(CASE WHEN j.credit_account = ca.code THEN j.amount_cents ELSE 0 END), 0) -
                    COALESCE(SUM(CASE WHEN j.debit_account = ca.code THEN j.amount_cents ELSE 0 END), 0)
                END
            END as balance,
            ca.code, ca.name
            FROM fin_chart_of_accounts ca
            LEFT JOIN fin_journal_entries j ON (j.debit_account = ca.code OR j.credit_account = ca.code) AND j.entry_date <= ?
            WHERE ca.account_type = ? AND ca.is_active = 1
            GROUP BY ca.code, ca.name
            HAVING balance != 0
            ORDER BY ca.code");
        $stmt->execute([$type, $type, $asOf, $type]);
        $result[$type . 's'] = array_map(fn($r) => [
            'code' => $r['code'], 'name' => $r['name'], 'balance' => finFromCents($r['balance'])
        ], $stmt->fetchAll());
    }

    $totalAssets = array_sum(array_column($result['assets'], 'balance'));
    $totalLiabilities = array_sum(array_column($result['liabilitys'], 'balance'));
    $totalEquity = array_sum(array_column($result['equitys'], 'balance'));

    jsonResponse([
        'success' => true,
        'as_of' => $asOf,
        'assets' => $result['assets'],
        'liabilities' => $result['liabilitys'],
        'equity' => $result['equitys'],
        'total_assets' => $totalAssets,
        'total_liabilities' => $totalLiabilities,
        'total_equity' => $totalEquity,
        'balanced' => abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01,
    ]);
}

function trialBalance(): void {
    $db = getDB();
    $asOf = sanitize($_GET['as_of'] ?? date('Y-m-d'), 10);

    $stmt = $db->prepare("SELECT ca.code, ca.name, ca.account_type,
        COALESCE(SUM(CASE WHEN j.debit_account = ca.code THEN j.amount_cents ELSE 0 END), 0) as total_debits,
        COALESCE(SUM(CASE WHEN j.credit_account = ca.code THEN j.amount_cents ELSE 0 END), 0) as total_credits
        FROM fin_chart_of_accounts ca
        LEFT JOIN fin_journal_entries j ON (j.debit_account = ca.code OR j.credit_account = ca.code) AND j.entry_date <= ?
        WHERE ca.is_active = 1
        GROUP BY ca.code, ca.name, ca.account_type
        HAVING total_debits > 0 OR total_credits > 0
        ORDER BY ca.code");
    $stmt->execute([$asOf]);
    $rows = $stmt->fetchAll();

    $totalDebits = 0;
    $totalCredits = 0;
    $entries = array_map(function($r) use (&$totalDebits, &$totalCredits) {
        $totalDebits += $r['total_debits'];
        $totalCredits += $r['total_credits'];
        return [
            'code' => $r['code'], 'name' => $r['name'], 'type' => $r['account_type'],
            'debits' => finFromCents($r['total_debits']),
            'credits' => finFromCents($r['total_credits']),
            'balance' => finFromCents($r['total_debits'] - $r['total_credits']),
        ];
    }, $rows);

    jsonResponse([
        'success' => true,
        'as_of' => $asOf,
        'entries' => $entries,
        'total_debits' => finFromCents($totalDebits),
        'total_credits' => finFromCents($totalCredits),
        'balanced' => $totalDebits === $totalCredits,
    ]);
}

function cashFlowStatement(): void {
    $db = getDB();
    $from = sanitize($_GET['from'] ?? date('Y-01-01'), 10);
    $to = sanitize($_GET['to'] ?? date('Y-m-d'), 10);

    // Operating: Revenue received minus operating expenses paid
    $opStmt = $db->prepare("SELECT
        COALESCE(SUM(CASE WHEN ca.account_type = 'revenue' AND j.credit_account = ca.code THEN j.amount_cents ELSE 0 END), 0) as revenue_in,
        COALESCE(SUM(CASE WHEN ca.account_type = 'expense' AND j.debit_account = ca.code THEN j.amount_cents ELSE 0 END), 0) as expenses_out
        FROM fin_journal_entries j
        JOIN fin_chart_of_accounts ca ON ca.code = j.debit_account OR ca.code = j.credit_account
        WHERE j.entry_date BETWEEN ? AND ?");
    $opStmt->execute([$from, $to]);
    $op = $opStmt->fetch();

    // Investing: Asset purchases/sales
    $invStmt = $db->prepare("SELECT
        COALESCE(SUM(CASE WHEN j.debit_account IN (SELECT code FROM fin_chart_of_accounts WHERE account_type='asset' AND sub_type != 'bank' AND sub_type != 'receivable') THEN j.amount_cents ELSE 0 END), 0) as invested
        FROM fin_journal_entries j WHERE j.entry_date BETWEEN ? AND ?");
    $invStmt->execute([$from, $to]);
    $inv = $invStmt->fetch();

    $operatingCash = ($op['revenue_in'] ?? 0) - ($op['expenses_out'] ?? 0);
    $investingCash = -($inv['invested'] ?? 0);

    jsonResponse([
        'success' => true,
        'period' => ['from' => $from, 'to' => $to],
        'operating' => [
            'revenue_received' => finFromCents($op['revenue_in'] ?? 0),
            'expenses_paid' => finFromCents($op['expenses_out'] ?? 0),
            'net_operating' => finFromCents($operatingCash),
        ],
        'investing' => [
            'asset_purchases' => finFromCents($inv['invested'] ?? 0),
            'net_investing' => finFromCents($investingCash),
        ],
        'net_cash_change' => finFromCents($operatingCash + $investingCash),
    ]);
}

// ═══ XERO INTEGRATION ═════════════════════════════════════════

function xeroConnect(): void {
    $scopes = 'openid profile email accounting.transactions accounting.contacts accounting.settings offline_access';
    $state = bin2hex(random_bytes(16));
    $_SESSION['xero_state'] = $state;

    $url = 'https://login.xero.com/identity/connect/authorize?' . http_build_query([
        'response_type' => 'code',
        'client_id' => XERO_CLIENT_ID,
        'redirect_uri' => XERO_REDIRECT_URI,
        'scope' => $scopes,
        'state' => $state,
    ]);

    jsonResponse(['success' => true, 'auth_url' => $url]);
}

function xeroCallback(): void {
    $code = $_GET['code'] ?? '';
    $state = $_GET['state'] ?? '';

    if (!$code || !hash_equals($_SESSION['xero_state'] ?? '', $state)) {
        jsonResponse(['error' => 'Invalid OAuth callback'], 400);
    }

    $response = finApiRequest('https://identity.xero.com/connect/token', 'POST', null, [
        'Content-Type: application/x-www-form-urlencoded',
        'Authorization: Basic ' . base64_encode(XERO_CLIENT_ID . ':' . XERO_CLIENT_SECRET),
    ]);

    // For Xero, we need form-encoded body
    $ch = curl_init('https://identity.xero.com/connect/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => XERO_REDIRECT_URI,
        ]),
        CURLOPT_HTTPHEADER => [
            'Authorization: Basic ' . base64_encode(XERO_CLIENT_ID . ':' . XERO_CLIENT_SECRET),
            'Content-Type: application/x-www-form-urlencoded',
        ],
    ]);
    $result = curl_exec($ch);
    curl_close($ch);
    $tokenData = json_decode($result, true);

    if (empty($tokenData['access_token'])) {
        jsonResponse(['error' => 'Failed to get Xero token'], 500);
    }

    // Get tenant ID
    $tenants = finApiRequest('https://api.xero.com/connections', 'GET', null, [
        'Authorization: Bearer ' . $tokenData['access_token'],
    ]);
    $tenantId = $tenants['data'][0]['tenantId'] ?? null;

    finStoreToken('xero', finGetClientId(), [
        'access_token' => $tokenData['access_token'],
        'refresh_token' => $tokenData['refresh_token'] ?? null,
        'expires_at' => date('Y-m-d H:i:s', time() + ($tokenData['expires_in'] ?? 1800)),
        'extra' => ['tenant_id' => $tenantId],
    ]);

    finAuditLog('xero_connect', 'accounting', ['tenant_id' => $tenantId]);

    // Redirect back to dashboard
    header('Location: ' . SITE_URL . '/finance-dashboard.php?connected=xero');
    exit;
}

function xeroMakeRequest(string $endpoint, string $method = 'GET', ?array $data = null): array {
    $token = finGetToken('xero', finGetClientId());
    if (!$token) {
        jsonResponse(['error' => 'Xero not connected. Use xero_connect first.'], 400);
    }

    // Refresh if expired
    if (!empty($token['expired'])) {
        $ch = curl_init('https://identity.xero.com/connect/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'refresh_token',
                'refresh_token' => $token['refresh_token'],
            ]),
            CURLOPT_HTTPHEADER => [
                'Authorization: Basic ' . base64_encode(XERO_CLIENT_ID . ':' . XERO_CLIENT_SECRET),
                'Content-Type: application/x-www-form-urlencoded',
            ],
        ]);
        $result = curl_exec($ch);
        curl_close($ch);
        $newToken = json_decode($result, true);

        if (!empty($newToken['access_token'])) {
            $extra = json_decode($token['extra'], true) ?: [];
            finStoreToken('xero', finGetClientId(), [
                'access_token' => $newToken['access_token'],
                'refresh_token' => $newToken['refresh_token'] ?? $token['refresh_token'],
                'expires_at' => date('Y-m-d H:i:s', time() + ($newToken['expires_in'] ?? 1800)),
                'extra' => $extra,
            ]);
            $token['access_token'] = $newToken['access_token'];
        }
    }

    $extra = is_string($token['extra']) ? json_decode($token['extra'], true) : ($token['extra'] ?? []);
    $tenantId = $extra['tenant_id'] ?? '';

    $headers = [
        'Authorization: Bearer ' . $token['access_token'],
        'Xero-tenant-id: ' . $tenantId,
    ];

    return finApiRequest(XERO_API_URL . '/' . $endpoint, $method, $data, $headers);
}

function xeroSync(): void {
    $entity = sanitize($_POST['entity'] ?? 'invoices', 30);

    $response = xeroMakeRequest(ucfirst($entity));
    if (!$response['success']) {
        jsonResponse(['error' => 'Xero sync failed', 'details' => $response['data']], 500);
    }

    finAuditLog('xero_sync', 'accounting', ['entity' => $entity]);
    jsonResponse(['success' => true, 'entity' => $entity, 'data' => $response['data']]);
}

function xeroGetInvoices(): void {
    $status = sanitize($_GET['status'] ?? '', 30);
    $endpoint = 'Invoices';
    if ($status) $endpoint .= '?Statuses=' . strtoupper($status);

    $response = xeroMakeRequest($endpoint);
    if (!$response['success']) {
        jsonResponse(['error' => 'Failed to fetch Xero invoices', 'details' => $response['data']], 500);
    }

    $invoices = $response['data']['Invoices'] ?? [];
    jsonResponse([
        'success' => true,
        'count' => count($invoices),
        'invoices' => array_map(fn($inv) => [
            'id' => $inv['InvoiceID'],
            'number' => $inv['InvoiceNumber'] ?? null,
            'contact' => $inv['Contact']['Name'] ?? 'Unknown',
            'date' => $inv['DateString'] ?? null,
            'due_date' => $inv['DueDateString'] ?? null,
            'status' => $inv['Status'],
            'total' => $inv['Total'] ?? 0,
            'amount_due' => $inv['AmountDue'] ?? 0,
            'currency' => $inv['CurrencyCode'] ?? 'USD',
        ], $invoices)
    ]);
}

function xeroCreateInvoice(): void {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $contactName = sanitize($input['contact_name'] ?? '', 200);
    $lineItems = $input['line_items'] ?? [];
    $dueDate = sanitize($input['due_date'] ?? date('Y-m-d', strtotime('+30 days')), 10);

    if (!$contactName || empty($lineItems)) {
        jsonResponse(['error' => 'contact_name and line_items required'], 400);
    }

    $xeroItems = array_map(fn($item) => [
        'Description' => sanitize($item['description'] ?? '', 200),
        'Quantity' => (float) ($item['quantity'] ?? 1),
        'UnitAmount' => (float) ($item['unit_amount'] ?? 0),
        'AccountCode' => sanitize($item['account_code'] ?? '4000', 20),
    ], $lineItems);

    $response = xeroMakeRequest('Invoices', 'POST', [
        'Invoices' => [[
            'Type' => 'ACCREC',
            'Contact' => ['Name' => $contactName],
            'DueDate' => $dueDate,
            'LineItems' => $xeroItems,
            'Status' => 'AUTHORISED',
        ]]
    ]);

    if (!$response['success']) {
        jsonResponse(['error' => 'Failed to create Xero invoice', 'details' => $response['data']], 500);
    }

    finAuditLog('xero_create_invoice', 'accounting', ['contact' => $contactName]);
    jsonResponse(['success' => true, 'invoice' => $response['data']['Invoices'][0] ?? null]);
}

// ═══ QUICKBOOKS INTEGRATION ═══════════════════════════════════

function qboConnect(): void {
    $scopes = 'com.intuit.quickbooks.accounting';
    $state = bin2hex(random_bytes(16));
    $_SESSION['qbo_state'] = $state;

    $url = 'https://appcenter.intuit.com/connect/oauth2?' . http_build_query([
        'client_id' => QBO_CLIENT_ID,
        'response_type' => 'code',
        'scope' => $scopes,
        'redirect_uri' => QBO_REDIRECT_URI,
        'state' => $state,
    ]);

    jsonResponse(['success' => true, 'auth_url' => $url]);
}

function qboCallback(): void {
    $code = $_GET['code'] ?? '';
    $state = $_GET['state'] ?? '';
    $realmId = $_GET['realmId'] ?? '';

    if (!$code || !hash_equals($_SESSION['qbo_state'] ?? '', $state)) {
        jsonResponse(['error' => 'Invalid OAuth callback'], 400);
    }

    $ch = curl_init('https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => QBO_REDIRECT_URI,
        ]),
        CURLOPT_HTTPHEADER => [
            'Authorization: Basic ' . base64_encode(QBO_CLIENT_ID . ':' . QBO_CLIENT_SECRET),
            'Content-Type: application/x-www-form-urlencoded',
        ],
    ]);
    $result = curl_exec($ch);
    curl_close($ch);
    $tokenData = json_decode($result, true);

    if (empty($tokenData['access_token'])) {
        jsonResponse(['error' => 'Failed to get QBO token'], 500);
    }

    finStoreToken('quickbooks', finGetClientId(), [
        'access_token' => $tokenData['access_token'],
        'refresh_token' => $tokenData['refresh_token'] ?? null,
        'expires_at' => date('Y-m-d H:i:s', time() + ($tokenData['expires_in'] ?? 3600)),
        'extra' => ['realm_id' => $realmId],
    ]);

    finAuditLog('qbo_connect', 'accounting', ['realm_id' => $realmId]);

    header('Location: ' . SITE_URL . '/finance-dashboard.php?connected=quickbooks');
    exit;
}

function qboMakeRequest(string $endpoint, string $method = 'GET', ?array $data = null): array {
    $token = finGetToken('quickbooks', finGetClientId());
    if (!$token) {
        jsonResponse(['error' => 'QuickBooks not connected'], 400);
    }

    $extra = is_string($token['extra']) ? json_decode($token['extra'], true) : ($token['extra'] ?? []);
    $realmId = $extra['realm_id'] ?? '';
    $url = QBO_API_URL . '/company/' . $realmId . '/' . $endpoint;

    return finApiRequest($url, $method, $data, [
        'Authorization: Bearer ' . $token['access_token'],
    ]);
}

function qboSync(): void {
    $entity = sanitize($_POST['entity'] ?? 'Invoice', 30);
    $response = qboMakeRequest('query?query=' . urlencode("SELECT * FROM {$entity} MAXRESULTS 100"));

    if (!$response['success']) {
        jsonResponse(['error' => 'QBO sync failed', 'details' => $response['data']], 500);
    }

    finAuditLog('qbo_sync', 'accounting', ['entity' => $entity]);
    jsonResponse(['success' => true, 'entity' => $entity, 'data' => $response['data']]);
}

function qboProfitLoss(): void {
    $from = sanitize($_GET['from'] ?? date('Y-01-01'), 10);
    $to = sanitize($_GET['to'] ?? date('Y-m-d'), 10);

    $response = qboMakeRequest("reports/ProfitAndLoss?start_date={$from}&end_date={$to}");
    if (!$response['success']) {
        jsonResponse(['error' => 'QBO P&L failed', 'details' => $response['data']], 500);
    }

    jsonResponse(['success' => true, 'report' => $response['data']]);
}

// ═══ AUTO-BOOKKEEPING ═════════════════════════════════════════

function autoCategorize(): void {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $transactions = $input['transactions'] ?? [];

    if (empty($transactions)) {
        jsonResponse(['error' => 'transactions array required'], 400);
    }

    $categorized = [];
    $rules = [
        '/stripe|payment processing/' => ['5100', 'Payment Processing Fees'],
        '/aws|azure|google cloud|digitalocean|vultr|hetzner/' => ['5000', 'Server & Infrastructure'],
        '/openai|anthropic|cohere|replicate/' => ['5200', 'API & Service Costs'],
        '/google ads|facebook ads|twitter ads|linkedin ads/' => ['5300', 'Marketing & Advertising'],
        '/github|jetbrains|figma|notion|slack/' => ['5500', 'Software Licenses'],
        '/upwork|fiverr|freelancer/' => ['5400', 'Contractor Payments'],
        '/subscription|recurring|monthly/' => ['4000', 'Subscription Revenue'],
        '/api.usage|api.call|metered/' => ['4100', 'API Usage Revenue'],
        '/voice|telnyx|twilio|call/' => ['4200', 'Voice Revenue'],
        '/domain|whois|registrar/' => ['4300', 'Domain Revenue'],
    ];

    foreach ($transactions as $txn) {
        $desc = strtolower($txn['description'] ?? '');
        $matched = false;
        foreach ($rules as $pattern => $account) {
            if (preg_match($pattern, $desc)) {
                $categorized[] = [
                    'description' => $txn['description'],
                    'amount' => $txn['amount'] ?? 0,
                    'account_code' => $account[0],
                    'account_name' => $account[1],
                    'confidence' => 0.85,
                ];
                $matched = true;
                break;
            }
        }
        if (!$matched) {
            $categorized[] = [
                'description' => $txn['description'],
                'amount' => $txn['amount'] ?? 0,
                'account_code' => null,
                'account_name' => 'Uncategorized',
                'confidence' => 0,
            ];
        }
    }

    jsonResponse([
        'success' => true,
        'categorized' => count(array_filter($categorized, fn($c) => $c['account_code'] !== null)),
        'uncategorized' => count(array_filter($categorized, fn($c) => $c['account_code'] === null)),
        'transactions' => $categorized,
    ]);
}

function reconcile(): void {
    $db = getDB();
    $from = sanitize($_POST['from'] ?? date('Y-m-01'), 10);
    $to = sanitize($_POST['to'] ?? date('Y-m-d'), 10);

    // Get journal totals
    $stmt = $db->prepare("SELECT
        SUM(CASE WHEN ca.account_type = 'revenue' AND j.credit_account = ca.code THEN j.amount_cents ELSE 0 END) as book_revenue,
        SUM(CASE WHEN ca.account_type = 'expense' AND j.debit_account = ca.code THEN j.amount_cents ELSE 0 END) as book_expenses
        FROM fin_journal_entries j
        JOIN fin_chart_of_accounts ca ON ca.code = j.debit_account OR ca.code = j.credit_account
        WHERE j.entry_date BETWEEN ? AND ?");
    $stmt->execute([$from, $to]);
    $books = $stmt->fetch();

    // Get treasury totals
    $tStmt = $db->prepare("SELECT
        COALESCE(SUM(CASE WHEN entry_type = 'income' THEN amount_cents ELSE 0 END), 0) as treasury_income,
        COALESCE(SUM(CASE WHEN entry_type = 'expense' THEN amount_cents ELSE 0 END), 0) as treasury_expenses
        FROM alfred_treasury WHERE created_at BETWEEN ? AND ?");
    $tStmt->execute([$from . ' 00:00:00', $to . ' 23:59:59']);
    $treasury = $tStmt->fetch();

    $revDiff = ($books['book_revenue'] ?? 0) - ($treasury['treasury_income'] ?? 0);
    $expDiff = ($books['book_expenses'] ?? 0) - ($treasury['treasury_expenses'] ?? 0);

    jsonResponse([
        'success' => true,
        'period' => ['from' => $from, 'to' => $to],
        'books' => [
            'revenue' => finFromCents($books['book_revenue'] ?? 0),
            'expenses' => finFromCents($books['book_expenses'] ?? 0),
        ],
        'treasury' => [
            'income' => finFromCents($treasury['treasury_income'] ?? 0),
            'expenses' => finFromCents($treasury['treasury_expenses'] ?? 0),
        ],
        'discrepancies' => [
            'revenue_diff' => finFromCents($revDiff),
            'expense_diff' => finFromCents($expDiff),
        ],
        'reconciled' => abs($revDiff) < 100 && abs($expDiff) < 100,
    ]);
}
