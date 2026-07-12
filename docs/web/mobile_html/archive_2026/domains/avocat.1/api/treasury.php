<?php
/**
 * Alfred Treasury API — Phase 1: Autonomy Foundation
 * ───────────────────────────────────────────────────
 * Financial self-awareness: track income, expenses, investments, budgets.
 * Alfred knows its own financial position at all times.
 *
 * Endpoints:
 *   POST ?action=record           → Record a financial entry
 *   GET  ?action=balance          → Current balance summary
 *   GET  ?action=ledger           → Full transaction ledger (paginated)
 *   GET  ?action=budget           → Budget allocation by department
 *   POST ?action=budget-set       → Set/update budget for a category
 *   GET  ?action=forecast         → Cash flow forecast (30/60/90 days)
 *   GET  ?action=report           → Financial report (daily/weekly/monthly)
 *   GET  ?action=stats            → Treasury statistics
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
apiRateLimit(20, 60, 'treasury');

function requireAuth() {
    if (empty($_SESSION['logged_in']) || empty($_SESSION['client_id'])) {
        jsonResponse(['error' => 'Authentication required'], 401);
    }
}

function isAdmin() {
    return !empty($_SESSION['is_admin']) || ($_SESSION['client_id'] ?? 0) === 33;
}

function isInternalCall() {
    $secret = getenv('INTERNAL_SECRET') ?: '';
    return $secret && isset($_SERVER['HTTP_X_INTERNAL_SECRET']) && hash_equals($secret, $_SERVER['HTTP_X_INTERNAL_SECRET']);
}

// ─── DB Schema ─────────────────────────────────────────────────────
function ensureTreasurySchema() {
    $db = getDB();
    if (!$db) return false;

    $db->exec("CREATE TABLE IF NOT EXISTS alfred_treasury (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        entry_type      ENUM('income','expense','investment','transfer') NOT NULL,
        category        VARCHAR(50) NOT NULL,
        amount_cents    BIGINT NOT NULL,
        currency        VARCHAR(10) DEFAULT 'USD',
        source          VARCHAR(100) NOT NULL,
        destination     VARCHAR(100) DEFAULT NULL,
        description     TEXT DEFAULT NULL,
        reference_id    VARCHAR(100) DEFAULT NULL,
        agent_id        VARCHAR(50) DEFAULT NULL,
        metadata        JSON DEFAULT NULL,
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_type (entry_type),
        INDEX idx_category (category),
        INDEX idx_date (created_at),
        INDEX idx_agent (agent_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS alfred_budgets (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        category        VARCHAR(50) UNIQUE NOT NULL,
        monthly_limit_cents BIGINT NOT NULL DEFAULT 0,
        current_spent_cents BIGINT NOT NULL DEFAULT 0,
        period_start    DATE NOT NULL,
        alert_threshold DECIMAL(3,2) DEFAULT 0.80,
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    return true;
}

// Amount helpers (all stored in cents to avoid float issues)
function toCents($amount) { return (int) round($amount * 100); }
function fromCents($cents) { return round($cents / 100, 2); }

// ─── Router ────────────────────────────────────────────────────────
$action = sanitize($_GET['action'] ?? '', 30);
$db = getDB();

if (!$db) jsonResponse(['error' => 'Database unavailable'], 503);

ensureTreasurySchema();

switch ($action) {

    // ── Record Entry ────────────────────────────────────────────────
    case 'record':
        if (!isInternalCall()) {
            requireAuth();
            if (!isAdmin()) jsonResponse(['error' => 'Admin access required'], 403);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) jsonResponse(['error' => 'JSON body required'], 400);

        $type = sanitize($input['entry_type'] ?? '', 20);
        $category = sanitize($input['category'] ?? '', 50);
        $amount = floatval($input['amount'] ?? 0);
        $source = sanitize($input['source'] ?? '', 100);

        if (!$type || !$category || $amount == 0 || !$source) {
            jsonResponse(['error' => 'entry_type, category, amount, and source required'], 400);
        }

        $validTypes = ['income', 'expense', 'investment', 'transfer'];
        if (!in_array($type, $validTypes)) jsonResponse(['error' => 'Invalid entry_type'], 400);

        $stmt = $db->prepare("INSERT INTO alfred_treasury (entry_type, category, amount_cents, currency, source, destination, description, reference_id, agent_id, metadata) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $type, $category, toCents($amount),
            sanitize($input['currency'] ?? 'USD', 10),
            $source,
            sanitize($input['destination'] ?? '', 100) ?: null,
            sanitize($input['description'] ?? '', 500) ?: null,
            sanitize($input['reference_id'] ?? '', 100) ?: null,
            sanitize($input['agent_id'] ?? '', 50) ?: null,
            isset($input['metadata']) ? json_encode($input['metadata']) : null,
        ]);

        // Update budget tracking
        if ($type === 'expense') {
            $db->prepare("UPDATE alfred_budgets SET current_spent_cents = current_spent_cents + ? WHERE category = ? AND period_start <= CURDATE()")->execute([
                toCents($amount), $category
            ]);
        }

        jsonResponse(['success' => true, 'entry_id' => $db->lastInsertId(), 'amount_cents' => toCents($amount)]);
        break;

    // ── Balance ─────────────────────────────────────────────────────
    case 'balance':
        if (!isInternalCall()) requireAuth();

        $income = $db->query("SELECT COALESCE(SUM(amount_cents), 0) FROM alfred_treasury WHERE entry_type = 'income'")->fetchColumn();
        $expenses = $db->query("SELECT COALESCE(SUM(amount_cents), 0) FROM alfred_treasury WHERE entry_type = 'expense'")->fetchColumn();
        $investments = $db->query("SELECT COALESCE(SUM(amount_cents), 0) FROM alfred_treasury WHERE entry_type = 'investment'")->fetchColumn();

        // This month
        $monthIncome = $db->query("SELECT COALESCE(SUM(amount_cents), 0) FROM alfred_treasury WHERE entry_type = 'income' AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())")->fetchColumn();
        $monthExpenses = $db->query("SELECT COALESCE(SUM(amount_cents), 0) FROM alfred_treasury WHERE entry_type = 'expense' AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())")->fetchColumn();

        // By category this month
        $byCategory = $db->query("SELECT category, entry_type, SUM(amount_cents) as total FROM alfred_treasury WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW()) GROUP BY category, entry_type ORDER BY total DESC")->fetchAll();

        jsonResponse([
            'success' => true,
            'balance' => [
                'total_income' => fromCents($income),
                'total_expenses' => fromCents($expenses),
                'total_investments' => fromCents($investments),
                'net_balance' => fromCents($income - $expenses - $investments),
            ],
            'this_month' => [
                'income' => fromCents($monthIncome),
                'expenses' => fromCents($monthExpenses),
                'net' => fromCents($monthIncome - $monthExpenses),
            ],
            'by_category' => array_map(function($c) {
                $c['total'] = fromCents($c['total']);
                return $c;
            }, $byCategory),
            'currency' => 'USD',
        ]);
        break;

    // ── Ledger ──────────────────────────────────────────────────────
    case 'ledger':
        if (!isInternalCall()) requireAuth();

        $page = max(intval($_GET['page'] ?? 1), 1);
        $limit = min(max(intval($_GET['limit'] ?? 25), 1), 100);
        $offset = ($page - 1) * $limit;

        $sql = "SELECT * FROM alfred_treasury WHERE 1=1";
        $countSql = "SELECT COUNT(*) FROM alfred_treasury WHERE 1=1";
        $params = [];

        if (!empty($_GET['type'])) {
            $sql .= " AND entry_type = ?";
            $countSql .= " AND entry_type = ?";
            $params[] = sanitize($_GET['type'], 20);
        }
        if (!empty($_GET['category'])) {
            $sql .= " AND category = ?";
            $countSql .= " AND category = ?";
            $params[] = sanitize($_GET['category'], 50);
        }
        if (!empty($_GET['from'])) {
            $sql .= " AND created_at >= ?";
            $countSql .= " AND created_at >= ?";
            $params[] = sanitize($_GET['from'], 30);
        }
        if (!empty($_GET['to'])) {
            $sql .= " AND created_at <= ?";
            $countSql .= " AND created_at <= ?";
            $params[] = sanitize($_GET['to'], 30);
        }

        $countStmt = $db->prepare($countSql);
        dbExecute($countStmt, $params);
        $total = $countStmt->fetchColumn();

        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $db->prepare($sql);
        dbExecute($stmt, $params);
        $entries = $stmt->fetchAll();

        foreach ($entries as &$e) {
            $e['amount'] = fromCents($e['amount_cents']);
            $e['metadata'] = json_decode($e['metadata'], true);
        }

        jsonResponse([
            'success' => true,
            'entries' => $entries,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int) $total,
                'pages' => ceil($total / $limit),
            ],
        ]);
        break;

    // ── Budget ──────────────────────────────────────────────────────
    case 'budget':
        if (!isInternalCall()) requireAuth();

        $budgets = $db->query("SELECT * FROM alfred_budgets ORDER BY category ASC")->fetchAll();

        foreach ($budgets as &$b) {
            $b['monthly_limit'] = fromCents($b['monthly_limit_cents']);
            $b['current_spent'] = fromCents($b['current_spent_cents']);
            $b['remaining'] = fromCents($b['monthly_limit_cents'] - $b['current_spent_cents']);
            $b['utilization'] = $b['monthly_limit_cents'] > 0
                ? round(($b['current_spent_cents'] / $b['monthly_limit_cents']) * 100, 1)
                : 0;
            $b['over_threshold'] = $b['utilization'] >= ($b['alert_threshold'] * 100);
        }

        jsonResponse(['success' => true, 'budgets' => $budgets]);
        break;

    // ── Set Budget ──────────────────────────────────────────────────
    case 'budget-set':
        if (!isInternalCall()) {
            requireAuth();
            if (!isAdmin()) jsonResponse(['error' => 'Admin access required'], 403);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $category = sanitize($input['category'] ?? '', 50);
        $monthlyLimit = floatval($input['monthly_limit'] ?? 0);

        if (!$category || $monthlyLimit <= 0) jsonResponse(['error' => 'category and monthly_limit required'], 400);

        $stmt = $db->prepare("INSERT INTO alfred_budgets (category, monthly_limit_cents, period_start) VALUES (?, ?, DATE_FORMAT(NOW(), '%Y-%m-01')) ON DUPLICATE KEY UPDATE monthly_limit_cents = ?, alert_threshold = ?");
        $stmt->execute([
            $category, toCents($monthlyLimit),
            toCents($monthlyLimit),
            floatval($input['alert_threshold'] ?? 0.80),
        ]);

        jsonResponse(['success' => true, 'category' => $category, 'monthly_limit' => $monthlyLimit]);
        break;

    // ── Cash Flow Forecast ──────────────────────────────────────────
    case 'forecast':
        if (!isInternalCall()) requireAuth();

        // Historical averages for forecasting
        $avgIncome = $db->query("SELECT COALESCE(AVG(monthly_income), 0) FROM (SELECT SUM(amount_cents) as monthly_income FROM alfred_treasury WHERE entry_type = 'income' GROUP BY YEAR(created_at), MONTH(created_at) ORDER BY YEAR(created_at) DESC, MONTH(created_at) DESC LIMIT 6) sub")->fetchColumn();
        $avgExpenses = $db->query("SELECT COALESCE(AVG(monthly_expense), 0) FROM (SELECT SUM(amount_cents) as monthly_expense FROM alfred_treasury WHERE entry_type = 'expense' GROUP BY YEAR(created_at), MONTH(created_at) ORDER BY YEAR(created_at) DESC, MONTH(created_at) DESC LIMIT 6) sub")->fetchColumn();

        $currentBalance = $db->query("SELECT COALESCE(SUM(CASE WHEN entry_type = 'income' THEN amount_cents WHEN entry_type IN ('expense','investment') THEN -amount_cents ELSE 0 END), 0) FROM alfred_treasury")->fetchColumn();

        $forecast = [];
        $balance = $currentBalance;
        for ($m = 1; $m <= 3; $m++) {
            $balance += ($avgIncome - $avgExpenses);
            $forecast[] = [
                'month' => date('Y-m', strtotime("+{$m} months")),
                'projected_income' => fromCents($avgIncome),
                'projected_expenses' => fromCents($avgExpenses),
                'projected_net' => fromCents($avgIncome - $avgExpenses),
                'projected_balance' => fromCents($balance),
            ];
        }

        jsonResponse([
            'success' => true,
            'current_balance' => fromCents($currentBalance),
            'forecast' => $forecast,
            'method' => '6-month moving average',
        ]);
        break;

    // ── Financial Report ────────────────────────────────────────────
    case 'report':
        if (!isInternalCall()) requireAuth();

        $period = sanitize($_GET['period'] ?? 'monthly', 20);
        $validPeriods = ['daily', 'weekly', 'monthly'];
        if (!in_array($period, $validPeriods)) $period = 'monthly';

        switch ($period) {
            case 'daily':
                $groupBy = "DATE(created_at)";
                $dateFilter = "created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
            case 'weekly':
                $groupBy = "YEARWEEK(created_at, 1)";
                $dateFilter = "created_at > DATE_SUB(NOW(), INTERVAL 12 WEEK)";
                break;
            case 'monthly':
            default:
                $groupBy = "DATE_FORMAT(created_at, '%Y-%m')";
                $dateFilter = "created_at > DATE_SUB(NOW(), INTERVAL 12 MONTH)";
                break;
        }

        $stmt = $db->query("SELECT {$groupBy} as period, entry_type, SUM(amount_cents) as total, COUNT(*) as tx_count FROM alfred_treasury WHERE {$dateFilter} GROUP BY {$groupBy}, entry_type ORDER BY period DESC");
        $rows = $stmt->fetchAll();

        // Pivot into periods
        $report = [];
        foreach ($rows as $r) {
            $p = $r['period'];
            if (!isset($report[$p])) $report[$p] = ['period' => $p, 'income' => 0, 'expenses' => 0, 'investments' => 0, 'transfers' => 0, 'transactions' => 0];
            $report[$p][$r['entry_type'] === 'income' ? 'income' : ($r['entry_type'] === 'expense' ? 'expenses' : ($r['entry_type'] === 'investment' ? 'investments' : 'transfers'))] = fromCents($r['total']);
            $report[$p]['transactions'] += $r['tx_count'];
        }

        // Add net
        foreach ($report as &$rp) {
            $rp['net'] = round($rp['income'] - $rp['expenses'] - $rp['investments'], 2);
        }

        jsonResponse(['success' => true, 'period' => $period, 'report' => array_values($report)]);
        break;

    // ── Stats ───────────────────────────────────────────────────────
    case 'stats':
        if (!isInternalCall()) requireAuth();

        $totalEntries = $db->query("SELECT COUNT(*) FROM alfred_treasury")->fetchColumn();
        $topIncomeCategories = $db->query("SELECT category, SUM(amount_cents) as total FROM alfred_treasury WHERE entry_type = 'income' GROUP BY category ORDER BY total DESC LIMIT 5")->fetchAll();
        $topExpenseCategories = $db->query("SELECT category, SUM(amount_cents) as total FROM alfred_treasury WHERE entry_type = 'expense' GROUP BY category ORDER BY total DESC LIMIT 5")->fetchAll();

        foreach ($topIncomeCategories as &$c) $c['total'] = fromCents($c['total']);
        foreach ($topExpenseCategories as &$c) $c['total'] = fromCents($c['total']);

        jsonResponse([
            'success' => true,
            'total_entries' => (int) $totalEntries,
            'top_income_categories' => $topIncomeCategories,
            'top_expense_categories' => $topExpenseCategories,
        ]);
        break;

    default:
        jsonResponse([
            'error' => 'Unknown action',
            'available_actions' => ['record', 'balance', 'ledger', 'budget', 'budget-set', 'forecast', 'report', 'stats'],
        ], 400);
}
