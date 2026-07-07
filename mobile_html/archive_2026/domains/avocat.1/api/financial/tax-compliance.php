<?php
/**
 * Tax Compliance API — Stripe Tax, TaxJar, Koinly (Crypto Tax)
 * ATLAS Agents: Auditor-F (#46), Accountant (#41)
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
function ensureTaxSchema(): void {
    $db = getDB();

    $db->exec("CREATE TABLE IF NOT EXISTS fin_tax_obligations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        jurisdiction VARCHAR(10) NOT NULL,
        tax_type ENUM('sales_tax','vat','gst','crypto_gains','income','withholding') NOT NULL,
        period VARCHAR(20),
        amount_cents BIGINT DEFAULT 0,
        taxable_amount_cents BIGINT DEFAULT 0,
        rate DECIMAL(8,4),
        status ENUM('estimated','calculated','filed','paid','overdue') DEFAULT 'estimated',
        due_date DATE,
        filed_date DATE,
        filing_reference VARCHAR(100),
        provider VARCHAR(30),
        metadata JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_jurisdiction (jurisdiction),
        INDEX idx_period (period),
        INDEX idx_status (status),
        INDEX idx_due (due_date)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS fin_tax_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        transaction_type ENUM('sale','refund','service','crypto_trade','crypto_transfer') NOT NULL,
        amount_cents BIGINT NOT NULL,
        tax_cents BIGINT DEFAULT 0,
        tax_rate DECIMAL(8,4),
        jurisdiction VARCHAR(10),
        customer_country VARCHAR(5),
        customer_state VARCHAR(10),
        product_type VARCHAR(50),
        tax_code VARCHAR(50),
        external_id VARCHAR(100),
        provider VARCHAR(30),
        transaction_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_date (transaction_date),
        INDEX idx_jurisdiction (jurisdiction)
    )");
}
ensureTaxSchema();

// ─── Routing ──────────────────────────────────────────────────
$action = sanitize($_GET['action'] ?? $_POST['action'] ?? '', 50);

switch ($action) {
    // Tax Obligations
    case 'obligations':       finRequireAdminOrInternal(); listObligations(); break;
    case 'upcoming':          finRequireAdminOrInternal(); upcomingDeadlines(); break;
    case 'summary':           finRequireAdminOrInternal(); taxSummary(); break;

    // TaxJar
    case 'taxjar_calculate':  finRequireAdminOrInternal(); taxJarCalculate(); break;
    case 'taxjar_rates':      finRequireAdminOrInternal(); taxJarRates(); break;
    case 'taxjar_categories': finRequireAdminOrInternal(); taxJarCategories(); break;
    case 'taxjar_nexus':      finRequireAdminOrInternal(); taxJarNexus(); break;

    // Koinly (Crypto Tax)
    case 'koinly_sync':       finRequireAdminOrInternal(); koinlySync(); break;
    case 'koinly_gains':      finRequireAdminOrInternal(); koinlyGains(); break;
    case 'koinly_report':     finRequireAdminOrInternal(); koinlyReport(); break;

    // Internal Tax Tools
    case 'record':            finRequireAdminOrInternal(); recordTaxTransaction(); break;
    case 'estimate':          finRequireAdminOrInternal(); estimateQuarterlyTax(); break;
    case 'gst_report':        finRequireAdminOrInternal(); gstReport(); break;

    default:
        jsonResponse(['error' => 'Invalid action', 'valid' => [
            'obligations','upcoming','summary',
            'taxjar_calculate','taxjar_rates','taxjar_categories','taxjar_nexus',
            'koinly_sync','koinly_gains','koinly_report',
            'record','estimate','gst_report'
        ]], 400);
}

// ═══ TAX OBLIGATIONS ══════════════════════════════════════════

function listObligations(): void {
    $db = getDB();
    $status = sanitize($_GET['status'] ?? '', 20);
    $year = sanitize($_GET['year'] ?? date('Y'), 4);

    $sql = "SELECT * FROM fin_tax_obligations WHERE period LIKE ?";
    $params = [$year . '%'];
    if ($status) { $sql .= " AND status = ?"; $params[] = $status; }
    $sql .= " ORDER BY due_date";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    jsonResponse(['success' => true, 'obligations' => array_map(fn($o) => array_merge($o, [
        'amount' => finFromCents($o['amount_cents']),
        'taxable_amount' => finFromCents($o['taxable_amount_cents']),
    ]), $stmt->fetchAll())]);
}

function upcomingDeadlines(): void {
    $db = getDB();
    $days = min((int) ($_GET['days'] ?? 90), 365);

    $stmt = $db->prepare("SELECT * FROM fin_tax_obligations
        WHERE due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
        AND status NOT IN ('filed', 'paid')
        ORDER BY due_date");
    $stmt->execute([$days]);

    $obligations = $stmt->fetchAll();
    $overdue = $db->query("SELECT * FROM fin_tax_obligations WHERE due_date < CURDATE() AND status NOT IN ('filed', 'paid') ORDER BY due_date")->fetchAll();

    jsonResponse([
        'success' => true,
        'upcoming' => $obligations,
        'overdue' => $overdue,
        'overdue_count' => count($overdue),
    ]);
}

function taxSummary(): void {
    $db = getDB();
    $year = sanitize($_GET['year'] ?? date('Y'), 4);

    $stmt = $db->prepare("SELECT
        jurisdiction,
        tax_type,
        SUM(amount_cents) as total_tax_cents,
        SUM(taxable_amount_cents) as total_taxable_cents,
        COUNT(*) as filing_count
        FROM fin_tax_obligations
        WHERE period LIKE ?
        GROUP BY jurisdiction, tax_type
        ORDER BY jurisdiction, tax_type");
    $stmt->execute([$year . '%']);

    $summary = $stmt->fetchAll();
    $totalTax = array_sum(array_column($summary, 'total_tax_cents'));

    jsonResponse([
        'success' => true,
        'year' => $year,
        'total_tax' => finFromCents($totalTax),
        'by_jurisdiction' => array_map(fn($s) => array_merge($s, [
            'total_tax' => finFromCents($s['total_tax_cents']),
            'total_taxable' => finFromCents($s['total_taxable_cents']),
        ]), $summary),
    ]);
}

// ═══ TAXJAR ═══════════════════════════════════════════════════

function tjRequest(string $endpoint, string $method = 'GET', ?array $data = null): array {
    return finApiRequest('https://api.taxjar.com/v2' . $endpoint, $method, $data, [
        'Authorization: Bearer ' . TAXJAR_API_KEY,
    ]);
}

function taxJarCalculate(): void {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $payload = [
        'from_country' => 'CA',
        'from_zip' => sanitize($input['from_zip'] ?? '', 10),
        'from_state' => sanitize($input['from_state'] ?? 'QC', 5),
        'to_country' => sanitize($input['to_country'] ?? 'US', 5),
        'to_zip' => sanitize($input['to_zip'] ?? '', 10),
        'to_state' => sanitize($input['to_state'] ?? '', 5),
        'amount' => (float) ($input['amount'] ?? 0),
        'shipping' => (float) ($input['shipping'] ?? 0),
    ];

    // Add line items if provided
    if (!empty($input['line_items'])) {
        $payload['line_items'] = array_map(fn($item) => [
            'quantity' => (int) ($item['quantity'] ?? 1),
            'unit_price' => (float) ($item['unit_price'] ?? 0),
            'product_tax_code' => sanitize($item['tax_code'] ?? '31000', 20), // SaaS default
        ], $input['line_items']);
    }

    $response = tjRequest('/taxes', 'POST', $payload);

    if ($response['success']) {
        $tax = $response['data']['tax'] ?? [];
        // Record the calculation
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO fin_tax_transactions (transaction_type, amount_cents, tax_cents, tax_rate, jurisdiction, customer_country, customer_state, product_type, provider, transaction_date)
            VALUES ('sale', ?, ?, ?, ?, ?, ?, ?, 'taxjar', CURDATE())");
        $stmt->execute([
            finToCents($tax['taxable_amount'] ?? 0),
            finToCents($tax['amount_to_collect'] ?? 0),
            $tax['rate'] ?? 0,
            $tax['jurisdictions']['country'] ?? $payload['to_country'],
            $payload['to_country'],
            $payload['to_state'],
            'saas'
        ]);
    }

    jsonResponse(['success' => $response['success'], 'tax' => $response['data']['tax'] ?? $response['data']]);
}

function taxJarRates(): void {
    $zip = sanitize($_GET['zip'] ?? '', 10);
    if (!$zip) {
        jsonResponse(['error' => 'zip required'], 400);
    }

    $country = sanitize($_GET['country'] ?? 'US', 5);
    $response = tjRequest("/rates/{$zip}?country={$country}");
    jsonResponse(['success' => $response['success'], 'rate' => $response['data']['rate'] ?? $response['data']]);
}

function taxJarCategories(): void {
    $response = tjRequest('/categories');
    jsonResponse(['success' => $response['success'], 'categories' => $response['data']['categories'] ?? []]);
}

function taxJarNexus(): void {
    $response = tjRequest('/nexus/regions');
    jsonResponse(['success' => $response['success'], 'nexus_regions' => $response['data']['regions'] ?? []]);
}

// ═══ KOINLY (CRYPTO TAX) ═════════════════════════════════════

function koinlyRequest(string $endpoint, string $method = 'GET', ?array $data = null): array {
    return finApiRequest('https://api.koinly.io/api' . $endpoint, $method, $data, [
        'Authorization: Bearer ' . KOINLY_API_KEY,
    ]);
}

function koinlySync(): void {
    // Sync internal DeFi transactions to Koinly
    $db = getDB();

    try {
        $stmt = $db->query("SELECT * FROM defi_transactions
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY created_at DESC LIMIT 100");
        $txns = $stmt->fetchAll();
    } catch (Exception $e) {
        $txns = [];
    }

    $synced = 0;
    foreach ($txns as $tx) {
        $response = koinlyRequest('/transactions', 'POST', [
            'type' => 'crypto_transfer',
            'date' => $tx['created_at'],
            'from' => ['currency' => $tx['token_symbol'] ?? 'ETH', 'amount' => $tx['amount'] ?? 0],
            'fee' => ['currency' => 'USD', 'amount' => $tx['gas_fee'] ?? 0],
            'description' => $tx['description'] ?? 'GoSiteMe DeFi Transaction',
            'txhash' => $tx['tx_hash'] ?? null,
        ]);
        if ($response['success']) $synced++;
    }

    finAuditLog('koinly_sync', 'tax', ['synced' => $synced, 'total' => count($txns)]);
    jsonResponse(['success' => true, 'synced' => $synced, 'total_transactions' => count($txns)]);
}

function koinlyGains(): void {
    $year = sanitize($_GET['year'] ?? date('Y'), 4);
    $response = koinlyRequest("/tax/gains?tax_year={$year}");

    if ($response['success']) {
        // Store as tax obligation
        $gains = $response['data'];
        $gainsCents = finToCents($gains['total_capital_gains'] ?? 0);

        if ($gainsCents > 0) {
            $db = getDB();
            $stmt = $db->prepare("INSERT INTO fin_tax_obligations (jurisdiction, tax_type, period, amount_cents, taxable_amount_cents, provider, status)
                VALUES ('CA', 'crypto_gains', ?, ?, ?, 'koinly', 'calculated')
                ON DUPLICATE KEY UPDATE amount_cents = VALUES(amount_cents), taxable_amount_cents = VALUES(taxable_amount_cents)");
            $stmt->execute([$year, $gainsCents, $gainsCents]);
        }
    }

    jsonResponse(['success' => $response['success'], 'gains' => $response['data'] ?? []]);
}

function koinlyReport(): void {
    $year = sanitize($_GET['year'] ?? date('Y'), 4);
    $format = sanitize($_GET['format'] ?? 'summary', 20);

    $response = koinlyRequest("/tax/report?tax_year={$year}&format={$format}");
    jsonResponse(['success' => $response['success'], 'report' => $response['data'] ?? []]);
}

// ═══ INTERNAL TAX TOOLS ═══════════════════════════════════════

function recordTaxTransaction(): void {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $db = getDB();
    $stmt = $db->prepare("INSERT INTO fin_tax_transactions
        (transaction_type, amount_cents, tax_cents, tax_rate, jurisdiction, customer_country, customer_state, product_type, tax_code, external_id, provider, transaction_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        sanitize($input['type'] ?? 'sale', 30),
        (int) ($input['amount_cents'] ?? 0),
        (int) ($input['tax_cents'] ?? 0),
        (float) ($input['tax_rate'] ?? 0),
        sanitize($input['jurisdiction'] ?? '', 10),
        sanitize($input['customer_country'] ?? '', 5),
        sanitize($input['customer_state'] ?? '', 10),
        sanitize($input['product_type'] ?? 'saas', 50),
        sanitize($input['tax_code'] ?? '', 50),
        sanitize($input['external_id'] ?? '', 100),
        sanitize($input['provider'] ?? 'internal', 30),
        sanitize($input['transaction_date'] ?? date('Y-m-d'), 15),
    ]);

    jsonResponse(['success' => true, 'id' => $db->lastInsertId()]);
}

function estimateQuarterlyTax(): void {
    $db = getDB();
    $quarter = sanitize($_GET['quarter'] ?? ceil(date('n') / 3), 2);
    $year = sanitize($_GET['year'] ?? date('Y'), 4);

    $quarterStart = $year . '-' . str_pad(($quarter - 1) * 3 + 1, 2, '0', STR_PAD_LEFT) . '-01';
    $quarterEnd = date('Y-m-t', strtotime($quarterStart . ' + 2 months'));

    // Aggregate tax transactions for the quarter
    $stmt = $db->prepare("SELECT
        customer_country,
        SUM(amount_cents) as total_amount_cents,
        SUM(tax_cents) as total_tax_cents,
        COUNT(*) as transaction_count
        FROM fin_tax_transactions
        WHERE transaction_date BETWEEN ? AND ?
        GROUP BY customer_country
        ORDER BY total_amount_cents DESC");
    $stmt->execute([$quarterStart, $quarterEnd]);
    $byCountry = $stmt->fetchAll();

    $totalTax = array_sum(array_column($byCountry, 'total_tax_cents'));
    $totalRevenue = array_sum(array_column($byCountry, 'total_amount_cents'));

    // GST/HST estimate for Canadian operations (5% federal + provincial)
    $canadianRevenue = 0;
    foreach ($byCountry as $row) {
        if ($row['customer_country'] === 'CA') {
            $canadianRevenue = (int) $row['total_amount_cents'];
        }
    }
    $gstEstimate = round($canadianRevenue * 0.05);

    // Store obligation
    $period = "{$year}-Q{$quarter}";
    $dueDate = match((int) $quarter) {
        1 => "{$year}-04-30",
        2 => "{$year}-07-31",
        3 => "{$year}-10-31",
        4 => ($year + 1) . "-01-31",
    };

    $stmt = $db->prepare("INSERT INTO fin_tax_obligations (jurisdiction, tax_type, period, amount_cents, taxable_amount_cents, rate, status, due_date, provider)
        VALUES ('CA', 'gst', ?, ?, ?, 0.05, 'estimated', ?, 'internal')
        ON DUPLICATE KEY UPDATE amount_cents = VALUES(amount_cents), taxable_amount_cents = VALUES(taxable_amount_cents)");
    $stmt->execute([$period, $gstEstimate, $canadianRevenue, $dueDate]);

    jsonResponse([
        'success' => true,
        'quarter' => "Q{$quarter} {$year}",
        'total_revenue' => finFromCents($totalRevenue),
        'total_tax_collected' => finFromCents($totalTax),
        'gst_estimate' => finFromCents($gstEstimate),
        'due_date' => $dueDate,
        'by_country' => array_map(fn($r) => array_merge($r, [
            'total_amount' => finFromCents($r['total_amount_cents']),
            'total_tax' => finFromCents($r['total_tax_cents']),
        ]), $byCountry),
    ]);
}

function gstReport(): void {
    $db = getDB();
    $year = sanitize($_GET['year'] ?? date('Y'), 4);

    // Canadian GST/HST/QST report
    $stmt = $db->prepare("SELECT
        DATE_FORMAT(transaction_date, '%Y-%m') as month,
        SUM(amount_cents) as revenue_cents,
        SUM(tax_cents) as tax_collected_cents,
        COUNT(*) as transactions
        FROM fin_tax_transactions
        WHERE customer_country = 'CA'
        AND transaction_date BETWEEN ? AND ?
        GROUP BY month ORDER BY month");
    $stmt->execute(["{$year}-01-01", "{$year}-12-31"]);
    $monthly = $stmt->fetchAll();

    $totalRevenue = array_sum(array_column($monthly, 'revenue_cents'));
    $totalCollected = array_sum(array_column($monthly, 'tax_collected_cents'));

    // GST = 5% federal, QST = 9.975% for Quebec
    $gstOwed = round($totalRevenue * 0.05);
    $qstOwed = round($totalRevenue * 0.09975);

    jsonResponse([
        'success' => true,
        'year' => $year,
        'total_revenue' => finFromCents($totalRevenue),
        'total_tax_collected' => finFromCents($totalCollected),
        'gst_owed' => finFromCents($gstOwed),
        'qst_owed' => finFromCents($qstOwed),
        'total_owed' => finFromCents($gstOwed + $qstOwed),
        'net_remittance' => finFromCents($gstOwed + $qstOwed - $totalCollected),
        'monthly' => array_map(fn($m) => array_merge($m, [
            'revenue' => finFromCents($m['revenue_cents']),
            'tax_collected' => finFromCents($m['tax_collected_cents']),
        ]), $monthly),
    ]);
}
