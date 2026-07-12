<?php
/**
 * Alfred Accounting Integration API — Phase 4: Financial Ops
 * ───────────────────────────────────────────────────────────
 * Xero integration, invoice automation, expense tracking,
 * treasury sync, financial reporting.
 *
 * Endpoints:
 *   GET  ?action=dashboard        → Financial dashboard
 *   GET  ?action=invoices         → List invoices
 *   POST ?action=create-invoice   → Create new invoice
 *   POST ?action=mark-paid        → Mark invoice paid
 *   GET  ?action=expenses         → List expenses
 *   POST ?action=add-expense      → Log expense
 *   POST ?action=categorize       → Auto-categorize expenses
 *   GET  ?action=reports          → Financial reports
 *   POST ?action=sync-treasury    → Sync with Alfred treasury
 *   GET  ?action=categories       → Expense categories
 *   GET  ?action=tax-summary      → Tax period summary
 *   POST ?action=xero-connect     → Connect Xero account
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Internal-Secret, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

requireCSRF();
apiRateLimit(20, 60, 'accounting');

function requireAuth() {
    if (empty($_SESSION['logged_in']) || empty($_SESSION['client_id'])) jsonResponse(['error' => 'Authentication required'], 401);
}
function isAdmin() { return !empty($_SESSION['is_admin']) || ($_SESSION['client_id'] ?? 0) === 33; }
function isInternalCall() {
    $s = getenv('INTERNAL_SECRET') ?: '';
    return $s && isset($_SERVER['HTTP_X_INTERNAL_SECRET']) && hash_equals($s, $_SERVER['HTTP_X_INTERNAL_SECRET']);
}

function ensureAccountingSchema() {
    $db = getDB();
    if (!$db) return false;

    $db->exec("CREATE TABLE IF NOT EXISTS accounting_invoices (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        invoice_number  VARCHAR(30) UNIQUE NOT NULL,
        client_id       INT NOT NULL,
        customer_name   VARCHAR(200) NOT NULL,
        customer_email  VARCHAR(200) DEFAULT NULL,
        items           JSON NOT NULL,
        subtotal        DECIMAL(12,2) NOT NULL,
        tax_rate        DECIMAL(5,2) DEFAULT 0,
        tax_amount      DECIMAL(12,2) DEFAULT 0,
        total           DECIMAL(12,2) NOT NULL,
        currency        VARCHAR(3) DEFAULT 'CAD',
        status          ENUM('draft','sent','paid','overdue','cancelled') DEFAULT 'draft',
        due_date        DATE DEFAULT NULL,
        paid_date       DATE DEFAULT NULL,
        notes           TEXT DEFAULT NULL,
        xero_id         VARCHAR(100) DEFAULT NULL,
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_client (client_id),
        INDEX idx_status (status),
        INDEX idx_due (due_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS accounting_expenses (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        client_id       INT NOT NULL,
        description     VARCHAR(500) NOT NULL,
        amount          DECIMAL(12,2) NOT NULL,
        currency        VARCHAR(3) DEFAULT 'CAD',
        category        VARCHAR(50) DEFAULT 'uncategorized',
        vendor          VARCHAR(200) DEFAULT NULL,
        receipt_url     VARCHAR(500) DEFAULT NULL,
        is_recurring    TINYINT(1) DEFAULT 0,
        recurrence      VARCHAR(30) DEFAULT NULL,
        tax_deductible  TINYINT(1) DEFAULT 0,
        status          ENUM('pending','approved','rejected') DEFAULT 'pending',
        expense_date    DATE NOT NULL,
        xero_id         VARCHAR(100) DEFAULT NULL,
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_client (client_id),
        INDEX idx_category (category),
        INDEX idx_date (expense_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS accounting_xero (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        client_id       INT UNIQUE NOT NULL,
        tenant_id       VARCHAR(100) NOT NULL,
        access_token    TEXT NOT NULL,
        refresh_token   TEXT NOT NULL,
        token_expires   TIMESTAMP NOT NULL,
        org_name        VARCHAR(200) DEFAULT NULL,
        connected_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_client (client_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    return true;
}

$EXPENSE_CATEGORIES = [
    'hosting'        => ['name' => 'Hosting & Infrastructure', 'tax_deductible' => true],
    'software'       => ['name' => 'Software & SaaS', 'tax_deductible' => true],
    'api_costs'      => ['name' => 'API Costs (AI, SMS, etc)', 'tax_deductible' => true],
    'payroll'        => ['name' => 'Payroll & Contractors', 'tax_deductible' => true],
    'marketing'      => ['name' => 'Marketing & Advertising', 'tax_deductible' => true],
    'office'         => ['name' => 'Office & Supplies', 'tax_deductible' => true],
    'travel'         => ['name' => 'Travel & Meals', 'tax_deductible' => true],
    'legal'          => ['name' => 'Legal & Professional', 'tax_deductible' => true],
    'equipment'      => ['name' => 'Equipment & Hardware', 'tax_deductible' => true],
    'insurance'      => ['name' => 'Insurance', 'tax_deductible' => true],
    'banking'        => ['name' => 'Banking & Fees', 'tax_deductible' => true],
    'personal'       => ['name' => 'Personal / Non-deductible', 'tax_deductible' => false],
    'uncategorized'  => ['name' => 'Uncategorized', 'tax_deductible' => false],
];

$action = sanitize($_GET['action'] ?? '', 30);
$db = getDB();
if (!$db) jsonResponse(['error' => 'Database unavailable'], 503);
ensureAccountingSchema();

switch ($action) {

    case 'dashboard':
        requireAuth();
        $clientId = $_SESSION['client_id'];

        $invoiceStats = $db->prepare("SELECT status, COUNT(*) as c, SUM(total) as total FROM accounting_invoices WHERE client_id = ? GROUP BY status");
        $invoiceStats->execute([$clientId]);
        $invByStatus = [];
        foreach ($invoiceStats->fetchAll() as $r) $invByStatus[$r['status']] = ['count' => (int)$r['c'], 'total' => round((float)$r['total'], 2)];

        $expenseTotal = $db->prepare("SELECT SUM(amount) FROM accounting_expenses WHERE client_id = ? AND expense_date >= DATE_FORMAT(NOW(), '%Y-01-01')");
        $expenseTotal->execute([$clientId]);
        $ytdExpenses = round((float)$expenseTotal->fetchColumn(), 2);

        $revenueTotal = $db->prepare("SELECT SUM(total) FROM accounting_invoices WHERE client_id = ? AND status = 'paid' AND paid_date >= DATE_FORMAT(NOW(), '%Y-01-01')");
        $revenueTotal->execute([$clientId]);
        $ytdRevenue = round((float)$revenueTotal->fetchColumn(), 2);

        $overdueCount = $db->prepare("SELECT COUNT(*) FROM accounting_invoices WHERE client_id = ? AND status = 'sent' AND due_date < CURDATE()");
        $overdueCount->execute([$clientId]);

        // Monthly trend (last 6 months)
        $trend = $db->prepare("SELECT DATE_FORMAT(expense_date, '%Y-%m') as month, SUM(amount) as expenses FROM accounting_expenses WHERE client_id = ? AND expense_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY month ORDER BY month");
        $trend->execute([$clientId]);

        jsonResponse([
            'success' => true,
            'dashboard' => [
                'ytd_revenue' => $ytdRevenue,
                'ytd_expenses' => $ytdExpenses,
                'ytd_profit' => round($ytdRevenue - $ytdExpenses, 2),
                'invoices_by_status' => $invByStatus,
                'overdue_invoices' => (int)$overdueCount->fetchColumn(),
                'monthly_trend' => $trend->fetchAll(),
            ],
        ]);
        break;

    case 'invoices':
        requireAuth();
        $status = sanitize($_GET['status'] ?? '', 20);
        $limit = min(max(intval($_GET['limit'] ?? 25), 1), 100);

        $where = "client_id = ?";
        $params = [$_SESSION['client_id']];
        if ($status && in_array($status, ['draft','sent','paid','overdue','cancelled'])) {
            $where .= " AND status = ?";
            $params[] = $status;
        }

        $params[] = $limit;
        $stmt = $db->prepare("SELECT id, invoice_number, customer_name, subtotal, tax_amount, total, currency, status, due_date, paid_date, created_at FROM accounting_invoices WHERE $where ORDER BY created_at DESC LIMIT ?");
        dbExecute($stmt, $params);
        $invoices = $stmt->fetchAll();

        jsonResponse(['success' => true, 'invoices' => $invoices]);
        break;

    case 'create-invoice':
        requireAuth();
        $input = json_decode(file_get_contents('php://input'), true);

        $customerName = sanitize($input['customer_name'] ?? '', 200);
        $items = $input['items'] ?? [];
        if (!$customerName || empty($items) || !is_array($items)) jsonResponse(['error' => 'customer_name and items[] required'], 400);

        // Calculate totals
        $subtotal = 0;
        $sanitizedItems = [];
        foreach ($items as $item) {
            $desc = sanitize($item['description'] ?? '', 200);
            $qty = max(floatval($item['quantity'] ?? 1), 0.01);
            $price = max(floatval($item['unit_price'] ?? 0), 0);
            $lineTotal = round($qty * $price, 2);
            $subtotal += $lineTotal;
            $sanitizedItems[] = ['description' => $desc, 'quantity' => $qty, 'unit_price' => $price, 'total' => $lineTotal];
        }

        $taxRate = max(min(floatval($input['tax_rate'] ?? 0), 50), 0);
        $taxAmount = round($subtotal * ($taxRate / 100), 2);
        $total = round($subtotal + $taxAmount, 2);

        // Generate invoice number
        $year = date('Y');
        $lastNum = $db->prepare("SELECT invoice_number FROM accounting_invoices WHERE invoice_number LIKE ? ORDER BY id DESC LIMIT 1");
        $lastNum->execute(["INV-{$year}-%"]);
        $last = $lastNum->fetchColumn();
        $seq = $last ? intval(substr($last, -4)) + 1 : 1;
        $invoiceNumber = sprintf("INV-%s-%04d", $year, $seq);

        $db->prepare("INSERT INTO accounting_invoices (invoice_number, client_id, customer_name, customer_email, items, subtotal, tax_rate, tax_amount, total, currency, due_date, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")->execute([
            $invoiceNumber, $_SESSION['client_id'], $customerName,
            filter_var($input['customer_email'] ?? '', FILTER_VALIDATE_EMAIL) ?: null,
            json_encode($sanitizedItems), $subtotal, $taxRate, $taxAmount, $total,
            sanitize($input['currency'] ?? 'CAD', 3),
            $input['due_date'] ?? date('Y-m-d', strtotime('+30 days')),
            sanitize($input['notes'] ?? '', 1000) ?: null,
        ]);

        jsonResponse(['success' => true, 'invoice_number' => $invoiceNumber, 'total' => $total]);
        break;

    case 'mark-paid':
        requireAuth();
        $input = json_decode(file_get_contents('php://input'), true);
        $invoiceId = intval($input['invoice_id'] ?? 0);
        if (!$invoiceId) jsonResponse(['error' => 'invoice_id required'], 400);

        $stmt = $db->prepare("UPDATE accounting_invoices SET status = 'paid', paid_date = CURDATE() WHERE id = ? AND client_id = ? AND status IN ('draft','sent','overdue')");
        $stmt->execute([$invoiceId, $_SESSION['client_id']]);

        if ($stmt->rowCount() === 0) jsonResponse(['error' => 'Invoice not found or already paid'], 404);
        jsonResponse(['success' => true]);
        break;

    case 'expenses':
        requireAuth();
        $category = sanitize($_GET['category'] ?? '', 50);
        $limit = min(max(intval($_GET['limit'] ?? 25), 1), 100);

        $where = "client_id = ?";
        $params = [$_SESSION['client_id']];
        if ($category) { $where .= " AND category = ?"; $params[] = $category; }

        $params[] = $limit;
        $stmt = $db->prepare("SELECT * FROM accounting_expenses WHERE $where ORDER BY expense_date DESC LIMIT ?");
        dbExecute($stmt, $params);
        jsonResponse(['success' => true, 'expenses' => $stmt->fetchAll()]);
        break;

    case 'add-expense':
        requireAuth();
        $input = json_decode(file_get_contents('php://input'), true);
        $description = sanitize($input['description'] ?? '', 500);
        $amount = floatval($input['amount'] ?? 0);
        if (!$description || $amount <= 0) jsonResponse(['error' => 'description and positive amount required'], 400);

        $category = sanitize($input['category'] ?? 'uncategorized', 50);
        global $EXPENSE_CATEGORIES;
        if (!isset($EXPENSE_CATEGORIES[$category])) $category = 'uncategorized';

        $db->prepare("INSERT INTO accounting_expenses (client_id, description, amount, currency, category, vendor, receipt_url, is_recurring, recurrence, tax_deductible, expense_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")->execute([
            $_SESSION['client_id'], $description, $amount,
            sanitize($input['currency'] ?? 'CAD', 3),
            $category,
            sanitize($input['vendor'] ?? '', 200) ?: null,
            isset($input['receipt_url']) ? filter_var($input['receipt_url'], FILTER_VALIDATE_URL) ?: null : null,
            !empty($input['is_recurring']) ? 1 : 0,
            sanitize($input['recurrence'] ?? '', 30) ?: null,
            $EXPENSE_CATEGORIES[$category]['tax_deductible'] ? 1 : 0,
            $input['expense_date'] ?? date('Y-m-d'),
        ]);

        jsonResponse(['success' => true, 'expense_id' => $db->lastInsertId()]);
        break;

    case 'categorize':
        if (!isInternalCall()) { requireAuth(); if (!isAdmin()) jsonResponse(['error' => 'Admin/internal required'], 403); }

        // Auto-categorize uncategorized expenses using keyword matching
        $uncategorized = $db->prepare("SELECT id, description, vendor FROM accounting_expenses WHERE category = 'uncategorized' LIMIT 50");
        $uncategorized->execute();
        $rules = [
            'hosting'    => ['server', 'hosting', 'vultr', 'digitalocean', 'aws', 'cloud', 'domain', 'ssl', 'cdn'],
            'software'   => ['software', 'license', 'subscription', 'saas', 'github', 'jetbrains', 'adobe'],
            'api_costs'  => ['openai', 'anthropic', 'twilio', 'stripe', 'api', 'sms', 'ai model', 'gpt', 'claude'],
            'marketing'  => ['ads', 'advertising', 'marketing', 'facebook', 'google ads', 'seo', 'promotion'],
            'office'     => ['office', 'supplies', 'stationery', 'furniture', 'desk'],
            'equipment'  => ['computer', 'laptop', 'monitor', 'keyboard', 'hardware', 'phone', 'tablet'],
            'travel'     => ['flight', 'hotel', 'uber', 'taxi', 'airbnb', 'restaurant', 'meal', 'gas'],
            'legal'      => ['lawyer', 'legal', 'accountant', 'consulting', 'professional'],
            'insurance'  => ['insurance', 'policy'],
            'banking'    => ['bank', 'fee', 'interest', 'wire', 'payment processing'],
        ];

        $categorized = 0;
        foreach ($uncategorized->fetchAll() as $exp) {
            $text = strtolower($exp['description'] . ' ' . ($exp['vendor'] ?? ''));
            foreach ($rules as $cat => $keywords) {
                foreach ($keywords as $kw) {
                    if (str_contains($text, $kw)) {
                        global $EXPENSE_CATEGORIES;
                        $db->prepare("UPDATE accounting_expenses SET category = ?, tax_deductible = ? WHERE id = ?")->execute([
                            $cat, $EXPENSE_CATEGORIES[$cat]['tax_deductible'] ? 1 : 0, $exp['id']
                        ]);
                        $categorized++;
                        break 2;
                    }
                }
            }
        }

        jsonResponse(['success' => true, 'categorized' => $categorized]);
        break;

    case 'reports':
        requireAuth();
        $clientId = $_SESSION['client_id'];
        $year = intval($_GET['year'] ?? date('Y'));

        // Monthly P&L
        $revenue = $db->prepare("SELECT DATE_FORMAT(paid_date, '%Y-%m') as month, SUM(total) as revenue FROM accounting_invoices WHERE client_id = ? AND status = 'paid' AND YEAR(paid_date) = ? GROUP BY month ORDER BY month");
        $revenue->execute([$clientId, $year]);

        $expenses = $db->prepare("SELECT DATE_FORMAT(expense_date, '%Y-%m') as month, SUM(amount) as expenses FROM accounting_expenses WHERE client_id = ? AND YEAR(expense_date) = ? GROUP BY month ORDER BY month");
        $expenses->execute([$clientId, $year]);

        $revMap = [];
        foreach ($revenue->fetchAll() as $r) $revMap[$r['month']] = (float)$r['revenue'];
        $expMap = [];
        foreach ($expenses->fetchAll() as $e) $expMap[$e['month']] = (float)$e['expenses'];

        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $key = sprintf("%d-%02d", $year, $m);
            $rev = $revMap[$key] ?? 0;
            $exp = $expMap[$key] ?? 0;
            $months[] = ['month' => $key, 'revenue' => round($rev, 2), 'expenses' => round($exp, 2), 'profit' => round($rev - $exp, 2)];
        }

        // Category breakdown
        $catBreakdown = $db->prepare("SELECT category, SUM(amount) as total, COUNT(*) as count FROM accounting_expenses WHERE client_id = ? AND YEAR(expense_date) = ? GROUP BY category ORDER BY total DESC");
        $catBreakdown->execute([$clientId, $year]);

        jsonResponse(['success' => true, 'year' => $year, 'monthly_pnl' => $months, 'expense_breakdown' => $catBreakdown->fetchAll()]);
        break;

    case 'sync-treasury':
        if (!isInternalCall()) { requireAuth(); if (!isAdmin()) jsonResponse(['error' => 'Admin/internal required'], 403); }

        $clientId = $_SESSION['client_id'] ?? 1;

        // Sum paid invoices and expenses for current month
        $monthStart = date('Y-m-01');

        $monthRevenue = $db->prepare("SELECT COALESCE(SUM(total), 0) FROM accounting_invoices WHERE client_id = ? AND status = 'paid' AND paid_date >= ?");
        $monthRevenue->execute([$clientId, $monthStart]);
        $rev = (float)$monthRevenue->fetchColumn();

        $monthExpenses = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM accounting_expenses WHERE client_id = ? AND expense_date >= ?");
        $monthExpenses->execute([$clientId, $monthStart]);
        $exp = (float)$monthExpenses->fetchColumn();

        // Push to treasury API
        $treasuryData = ['month_revenue' => $rev, 'month_expenses' => $exp, 'month_profit' => round($rev - $exp, 2)];

        $internalSecret = getenv('INTERNAL_SECRET') ?: '';
        if ($internalSecret) {
            $ch = curl_init(SITE_URL . '/api/treasury.php?action=record');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'X-Internal-Secret: ' . $internalSecret,
                ],
                CURLOPT_POSTFIELDS => json_encode([
                    'entry_type' => 'revenue',
                    'amount' => $rev,
                    'category' => 'invoices',
                    'description' => 'Monthly revenue sync from accounting',
                ]),
                CURLOPT_TIMEOUT => 10,
            ]);
            curl_exec($ch);
            curl_close($ch);
        }

        jsonResponse(['success' => true, 'synced' => $treasuryData]);
        break;

    case 'categories':
        global $EXPENSE_CATEGORIES;
        jsonResponse(['success' => true, 'categories' => $EXPENSE_CATEGORIES]);
        break;

    case 'tax-summary':
        requireAuth();
        $clientId = $_SESSION['client_id'];
        $year = intval($_GET['year'] ?? date('Y'));

        $deductible = $db->prepare("SELECT category, SUM(amount) as total FROM accounting_expenses WHERE client_id = ? AND tax_deductible = 1 AND YEAR(expense_date) = ? GROUP BY category ORDER BY total DESC");
        $deductible->execute([$clientId, $year]);

        $totalDeductible = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM accounting_expenses WHERE client_id = ? AND tax_deductible = 1 AND YEAR(expense_date) = ?");
        $totalDeductible->execute([$clientId, $year]);

        $totalRevenue = $db->prepare("SELECT COALESCE(SUM(total), 0) FROM accounting_invoices WHERE client_id = ? AND status = 'paid' AND YEAR(paid_date) = ?");
        $totalRevenue->execute([$clientId, $year]);

        $rev = (float)$totalRevenue->fetchColumn();
        $ded = (float)$totalDeductible->fetchColumn();

        $taxCollected = $db->prepare("SELECT COALESCE(SUM(tax_amount), 0) FROM accounting_invoices WHERE client_id = ? AND status = 'paid' AND YEAR(paid_date) = ?");
        $taxCollected->execute([$clientId, $year]);

        jsonResponse([
            'success' => true,
            'tax_summary' => [
                'year' => $year,
                'gross_revenue' => round($rev, 2),
                'total_deductions' => round($ded, 2),
                'taxable_income' => round($rev - $ded, 2),
                'tax_collected' => round((float)$taxCollected->fetchColumn(), 2),
                'deductions_by_category' => $deductible->fetchAll(),
            ],
        ]);
        break;

    case 'xero-connect':
        requireAuth();
        if (!isAdmin()) jsonResponse(['error' => 'Admin required'], 403);

        $input = json_decode(file_get_contents('php://input'), true);
        $tenantId = sanitize($input['tenant_id'] ?? '', 100);
        $accessToken = $input['access_token'] ?? '';
        $refreshToken = $input['refresh_token'] ?? '';

        if (!$tenantId || !$accessToken || !$refreshToken) jsonResponse(['error' => 'tenant_id, access_token, and refresh_token required'], 400);

        $db->prepare("INSERT INTO accounting_xero (client_id, tenant_id, access_token, refresh_token, token_expires) VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE)) ON DUPLICATE KEY UPDATE tenant_id = VALUES(tenant_id), access_token = VALUES(access_token), refresh_token = VALUES(refresh_token), token_expires = VALUES(token_expires)")->execute([
            $_SESSION['client_id'], $tenantId, $accessToken, $refreshToken
        ]);

        jsonResponse(['success' => true]);
        break;

    default:
        jsonResponse(['error' => 'Unknown action', 'available_actions' => ['dashboard','invoices','create-invoice','mark-paid','expenses','add-expense','categorize','reports','sync-treasury','categories','tax-summary','xero-connect']], 400);
}
