<?php
/**
 * Financial Analytics & Forecasting API — ChartMogul, ProfitWell, Baremetrics, Internal Forecasting
 * ATLAS Agents: Forecaster (#45), Accountant (#41)
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
function ensureAnalyticsSchema(): void {
    $db = getDB();

    $db->exec("CREATE TABLE IF NOT EXISTS fin_metrics_cache (
        id INT AUTO_INCREMENT PRIMARY KEY,
        metric_name VARCHAR(50) NOT NULL,
        metric_value DECIMAL(15,2) DEFAULT 0,
        period VARCHAR(20),
        period_start DATE,
        period_end DATE,
        source VARCHAR(30) DEFAULT 'internal',
        metadata JSON,
        cached_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_metric (metric_name, period_start),
        INDEX idx_source (source)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS fin_forecasts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        forecast_type VARCHAR(30) NOT NULL,
        target_date DATE NOT NULL,
        predicted_value DECIMAL(15,2),
        confidence DECIMAL(5,2),
        model VARCHAR(30) DEFAULT 'linear',
        actual_value DECIMAL(15,2),
        metadata JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_type_date (forecast_type, target_date)
    )");
}
ensureAnalyticsSchema();

// ─── Routing ──────────────────────────────────────────────────
$action = sanitize($_GET['action'] ?? $_POST['action'] ?? '', 50);

switch ($action) {
    // Internal SaaS Metrics
    case 'mrr':              finRequireAdminOrInternal(); calcMRR(); break;
    case 'arr':              finRequireAdminOrInternal(); calcARR(); break;
    case 'churn':            finRequireAdminOrInternal(); calcChurn(); break;
    case 'ltv':              finRequireAdminOrInternal(); calcLTV(); break;
    case 'revenue_trend':    finRequireAdminOrInternal(); revenueTrend(); break;
    case 'cohort':           finRequireAdminOrInternal(); cohortAnalysis(); break;
    case 'dashboard_kpis':   finRequireAdminOrInternal(); dashboardKPIs(); break;

    // ChartMogul
    case 'cm_mrr':           finRequireAdminOrInternal(); chartMogulMRR(); break;
    case 'cm_arr':           finRequireAdminOrInternal(); chartMogulARR(); break;
    case 'cm_churn':         finRequireAdminOrInternal(); chartMogulChurn(); break;
    case 'cm_customers':     finRequireAdminOrInternal(); chartMogulCustomers(); break;

    // ProfitWell
    case 'pw_metrics':       finRequireAdminOrInternal(); profitWellMetrics(); break;
    case 'pw_mrr':           finRequireAdminOrInternal(); profitWellMRR(); break;

    // Forecasting
    case 'forecast_revenue': finRequireAdminOrInternal(); forecastRevenue(); break;
    case 'forecast_churn':   finRequireAdminOrInternal(); forecastChurn(); break;
    case 'forecast_cashflow':finRequireAdminOrInternal(); forecastCashflow(); break;
    case 'forecasts':        finRequireAdminOrInternal(); listForecasts(); break;

    default:
        jsonResponse(['error' => 'Invalid action', 'valid' => [
            'mrr','arr','churn','ltv','revenue_trend','cohort','dashboard_kpis',
            'cm_mrr','cm_arr','cm_churn','cm_customers',
            'pw_metrics','pw_mrr',
            'forecast_revenue','forecast_churn','forecast_cashflow','forecasts'
        ]], 400);
}

// ═══ INTERNAL SAAS METRICS ════════════════════════════════════

function calcMRR(): void {
    $db = getDB();
    // Pull from Stripe subscriptions or internal treasury
    try {
        $stmt = $db->query("SELECT
            SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as total_mrr,
            COUNT(DISTINCT client_id) as active_subscribers,
            DATE_FORMAT(created_at, '%Y-%m') as month
            FROM alfred_treasury
            WHERE type = 'subscription' AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY month ORDER BY month DESC");
        $rows = $stmt->fetchAll();
    } catch (Exception $e) {
        $rows = [];
    }

    $currentMRR = $rows[0]['total_mrr'] ?? 0;

    // Cache it
    cacheMetric('mrr', $currentMRR, 'monthly', date('Y-m-01'));

    jsonResponse([
        'success' => true,
        'current_mrr' => round($currentMRR, 2),
        'current_arr' => round($currentMRR * 12, 2),
        'active_subscribers' => (int) ($rows[0]['active_subscribers'] ?? 0),
        'trend' => $rows,
    ]);
}

function calcARR(): void {
    $db = getDB();
    try {
        $stmt = $db->query("SELECT SUM(amount) as annual_total FROM alfred_treasury
            WHERE type = 'subscription' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)");
        $row = $stmt->fetch();
        $arr = (float) ($row['annual_total'] ?? 0);
    } catch (Exception $e) {
        $arr = 0;
    }

    cacheMetric('arr', $arr, 'yearly', date('Y-01-01'));
    jsonResponse(['success' => true, 'arr' => round($arr, 2)]);
}

function calcChurn(): void {
    $db = getDB();
    $months = min((int) ($_GET['months'] ?? 6), 24);

    try {
        // Monthly churn = lost subscribers / start subscribers
        $stmt = $db->prepare("SELECT
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(DISTINCT CASE WHEN amount > 0 THEN client_id END) as active,
            COUNT(DISTINCT CASE WHEN type = 'cancellation' THEN client_id END) as cancelled
            FROM alfred_treasury
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? MONTH)
            GROUP BY month ORDER BY month");
        $stmt->execute([$months]);
        $data = $stmt->fetchAll();
    } catch (Exception $e) {
        $data = [];
    }

    $churnRates = array_map(fn($row) => [
        'month' => $row['month'],
        'active' => (int) $row['active'],
        'cancelled' => (int) $row['cancelled'],
        'churn_rate' => $row['active'] > 0 ? round($row['cancelled'] / $row['active'] * 100, 2) : 0,
    ], $data);

    $avgChurn = count($churnRates) > 0 ? round(array_sum(array_column($churnRates, 'churn_rate')) / count($churnRates), 2) : 0;

    jsonResponse(['success' => true, 'avg_churn_rate' => $avgChurn, 'monthly' => $churnRates]);
}

function calcLTV(): void {
    $db = getDB();
    try {
        // LTV = ARPU / Churn Rate
        $stmt = $db->query("SELECT
            AVG(total_revenue) as arpu,
            COUNT(*) as total_customers
            FROM (
                SELECT client_id, SUM(amount) as total_revenue
                FROM alfred_treasury
                WHERE amount > 0
                GROUP BY client_id
            ) rev");
        $row = $stmt->fetch();
        $arpu = (float) ($row['arpu'] ?? 0);
    } catch (Exception $e) {
        $arpu = 0;
    }

    // Estimate monthly churn from cancellation data (simplified)
    $churnRate = 0.05; // Default 5% if no data
    try {
        $churnData = $db->query("SELECT
            COUNT(DISTINCT CASE WHEN type = 'cancellation' THEN client_id END) as cancelled,
            COUNT(DISTINCT client_id) as total
            FROM alfred_treasury WHERE created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)")->fetch();
        if ($churnData['total'] > 0) {
            $churnRate = max($churnData['cancelled'] / $churnData['total'], 0.01);
        }
    } catch (Exception $e) { /* use default */ }

    $ltv = $churnRate > 0 ? round($arpu / $churnRate, 2) : 0;

    cacheMetric('ltv', $ltv, 'quarterly', date('Y-m-01'));

    jsonResponse([
        'success' => true,
        'ltv' => $ltv,
        'arpu' => round($arpu, 2),
        'estimated_churn_rate' => round($churnRate * 100, 2) . '%',
    ]);
}

function revenueTrend(): void {
    $db = getDB();
    $months = min((int) ($_GET['months'] ?? 12), 36);

    try {
        $stmt = $db->prepare("SELECT
            DATE_FORMAT(created_at, '%Y-%m') as month,
            SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as revenue,
            SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as expenses,
            COUNT(DISTINCT client_id) as unique_clients
            FROM alfred_treasury
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? MONTH)
            GROUP BY month ORDER BY month");
        $stmt->execute([$months]);
        $trend = $stmt->fetchAll();
    } catch (Exception $e) {
        $trend = [];
    }

    // Calculate growth rates
    for ($i = 1; $i < count($trend); $i++) {
        $prev = (float) $trend[$i - 1]['revenue'];
        $curr = (float) $trend[$i]['revenue'];
        $trend[$i]['growth_rate'] = $prev > 0 ? round(($curr - $prev) / $prev * 100, 2) : 0;
        $trend[$i]['net_revenue'] = round($curr - (float) $trend[$i]['expenses'], 2);
    }
    if (count($trend) > 0) {
        $trend[0]['growth_rate'] = 0;
        $trend[0]['net_revenue'] = round((float) $trend[0]['revenue'] - (float) $trend[0]['expenses'], 2);
    }

    jsonResponse(['success' => true, 'months' => $months, 'trend' => $trend]);
}

function cohortAnalysis(): void {
    $db = getDB();
    try {
        $stmt = $db->query("SELECT
            DATE_FORMAT(first_purchase, '%Y-%m') as cohort,
            TIMESTAMPDIFF(MONTH, first_purchase, last_purchase) as months_retained,
            COUNT(*) as customers,
            SUM(total_revenue) as total_revenue
            FROM (
                SELECT client_id,
                    MIN(created_at) as first_purchase,
                    MAX(created_at) as last_purchase,
                    SUM(amount) as total_revenue
                FROM alfred_treasury WHERE amount > 0
                GROUP BY client_id
            ) cohorts
            GROUP BY cohort, months_retained
            ORDER BY cohort, months_retained");
        $rows = $stmt->fetchAll();
    } catch (Exception $e) {
        $rows = [];
    }

    jsonResponse(['success' => true, 'cohorts' => $rows]);
}

function dashboardKPIs(): void {
    $db = getDB();
    $kpis = [];

    try {
        // Revenue this month (amount_cents, entry_type='income')
        $stmt = $db->query("SELECT COALESCE(SUM(amount_cents), 0) FROM alfred_treasury WHERE entry_type = 'income' AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')");
        $kpis['revenue_mtd'] = finFromCents((int) $stmt->fetchColumn());

        // Revenue last month
        $stmt = $db->query("SELECT COALESCE(SUM(amount_cents), 0) FROM alfred_treasury WHERE entry_type = 'income' AND created_at >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m-01') AND created_at < DATE_FORMAT(NOW(), '%Y-%m-01')");
        $kpis['revenue_last_month'] = finFromCents((int) $stmt->fetchColumn());

        // Total balance (treasury: income - expense)
        $stmt = $db->query("SELECT COALESCE(SUM(CASE WHEN entry_type = 'income' THEN amount_cents WHEN entry_type = 'expense' THEN -amount_cents ELSE 0 END), 0) FROM alfred_treasury");
        $kpis['total_balance'] = finFromCents((int) $stmt->fetchColumn());

        // Active subscriptions estimate (category-based since table has no client_id)
        $stmt = $db->query("SELECT COUNT(*) FROM alfred_treasury WHERE entry_type = 'income' AND category = 'subscription' AND created_at >= DATE_SUB(NOW(), INTERVAL 2 MONTH)");
        $kpis['active_subscriptions'] = (int) $stmt->fetchColumn();

        // Pending payouts
        try {
            $stmt = $db->query("SELECT COALESCE(SUM(amount_cents), 0) FROM fin_payouts WHERE status = 'pending'");
            $kpis['pending_payouts'] = finFromCents((int) $stmt->fetchColumn());
        } catch (Exception $e) {
            $kpis['pending_payouts'] = 0;
        }

        // Growth rate (month over month)
        $kpis['growth_rate'] = $kpis['revenue_last_month'] > 0
            ? round(($kpis['revenue_mtd'] - $kpis['revenue_last_month']) / $kpis['revenue_last_month'] * 100, 2)
            : 0;

    } catch (Exception $e) {
        error_log('[financial-analytics] ' . $e->getMessage());
        $kpis = ['error' => 'Unable to compute KPIs'];
    }

    jsonResponse(['success' => true, 'kpis' => $kpis]);
}

// ═══ CHARTMOGUL ═══════════════════════════════════════════════

function cmRequest(string $endpoint, string $method = 'GET', ?array $data = null): array {
    return finApiRequest('https://api.chartmogul.com/v1' . $endpoint, $method, $data, [
        'Authorization: Basic ' . base64_encode(CHARTMOGUL_API_KEY . ':'),
    ]);
}

function chartMogulMRR(): void {
    $startDate = sanitize($_GET['start_date'] ?? date('Y-m-d', strtotime('-12 months')), 15);
    $endDate = sanitize($_GET['end_date'] ?? date('Y-m-d'), 15);

    $response = cmRequest("/metrics/mrr?start-date={$startDate}&end-date={$endDate}&interval=month");

    if ($response['success']) {
        $entries = $response['data']['entries'] ?? [];
        // Cache latest value
        if (!empty($entries)) {
            $latest = end($entries);
            cacheMetric('cm_mrr', $latest['mrr'] / 100, 'monthly', $latest['date'] ?? date('Y-m-01'), 'chartmogul');
        }
    }

    jsonResponse(['success' => $response['success'], 'mrr_data' => $response['data']['entries'] ?? [], 'summary' => $response['data']['summary'] ?? null]);
}

function chartMogulARR(): void {
    $startDate = sanitize($_GET['start_date'] ?? date('Y-m-d', strtotime('-12 months')), 15);
    $endDate = sanitize($_GET['end_date'] ?? date('Y-m-d'), 15);

    $response = cmRequest("/metrics/arr?start-date={$startDate}&end-date={$endDate}&interval=month");
    jsonResponse(['success' => $response['success'], 'arr_data' => $response['data']['entries'] ?? []]);
}

function chartMogulChurn(): void {
    $startDate = sanitize($_GET['start_date'] ?? date('Y-m-d', strtotime('-12 months')), 15);
    $endDate = sanitize($_GET['end_date'] ?? date('Y-m-d'), 15);

    $response = cmRequest("/metrics/customer-churn-rate?start-date={$startDate}&end-date={$endDate}&interval=month");
    jsonResponse(['success' => $response['success'], 'churn_data' => $response['data']['entries'] ?? []]);
}

function chartMogulCustomers(): void {
    $status = sanitize($_GET['status'] ?? 'Active', 20);
    $response = cmRequest("/customers?status={$status}&per_page=50");
    jsonResponse(['success' => $response['success'], 'customers' => $response['data']['entries'] ?? [], 'total' => $response['data']['total_pages'] ?? 0]);
}

// ═══ PROFITWELL ═══════════════════════════════════════════════

function pwRequest(string $endpoint): array {
    return finApiRequest('https://api.profitwell.com/v2' . $endpoint, 'GET', null, [
        'Authorization: ' . PROFITWELL_API_KEY,
    ]);
}

function profitWellMetrics(): void {
    $response = pwRequest('/metrics/daily/?metrics=recurring_revenue,active_customers,mrr_churn_rate');
    jsonResponse(['success' => $response['success'], 'metrics' => $response['data'] ?? []]);
}

function profitWellMRR(): void {
    $month = sanitize($_GET['month'] ?? date('Y-m'), 10);
    $response = pwRequest("/metrics/monthly/?month={$month}&metrics=recurring_revenue");

    if ($response['success'] && !empty($response['data'])) {
        cacheMetric('pw_mrr', $response['data']['recurring_revenue'] ?? 0, 'monthly', $month . '-01', 'profitwell');
    }

    jsonResponse(['success' => $response['success'], 'mrr' => $response['data'] ?? []]);
}

// ═══ FORECASTING ══════════════════════════════════════════════

function forecastRevenue(): void {
    $db = getDB();
    $months_ahead = min((int) ($_GET['months_ahead'] ?? 3), 12);

    // Get historical monthly revenue
    try {
        $stmt = $db->query("SELECT
            DATE_FORMAT(created_at, '%Y-%m') as month,
            SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as revenue
            FROM alfred_treasury
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY month ORDER BY month");
        $historical = $stmt->fetchAll();
    } catch (Exception $e) {
        $historical = [];
    }

    if (count($historical) < 3) {
        jsonResponse(['success' => false, 'error' => 'Need at least 3 months of data for forecasting']);
    }

    $values = array_column($historical, 'revenue');
    $forecasts = linearForecast($values, $months_ahead);

    // Store forecasts
    $lastMonth = end($historical)['month'];
    foreach ($forecasts as $i => $forecast) {
        $targetDate = date('Y-m-01', strtotime($lastMonth . '-01 + ' . ($i + 1) . ' months'));
        storeForecast('revenue', $targetDate, $forecast['value'], $forecast['confidence'], 'linear');
    }

    jsonResponse([
        'success' => true,
        'historical' => $historical,
        'forecasts' => $forecasts,
        'model' => 'linear_regression',
    ]);
}

function forecastChurn(): void {
    $db = getDB();
    $months_ahead = min((int) ($_GET['months_ahead'] ?? 3), 12);

    try {
        $stmt = $db->query("SELECT
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(DISTINCT CASE WHEN type = 'cancellation' THEN client_id END) * 100.0 /
                GREATEST(COUNT(DISTINCT client_id), 1) as churn_rate
            FROM alfred_treasury
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY month ORDER BY month");
        $historical = $stmt->fetchAll();
    } catch (Exception $e) {
        $historical = [];
    }

    if (count($historical) < 3) {
        jsonResponse(['success' => false, 'error' => 'Insufficient data for churn forecast']);
    }

    $values = array_column($historical, 'churn_rate');
    // Use moving average for churn (more stable)
    $forecasts = movingAvgForecast($values, $months_ahead);

    jsonResponse([
        'success' => true,
        'historical' => $historical,
        'forecasts' => $forecasts,
        'model' => 'moving_average',
    ]);
}

function forecastCashflow(): void {
    $db = getDB();
    $months_ahead = min((int) ($_GET['months_ahead'] ?? 3), 12);

    try {
        $stmt = $db->query("SELECT
            DATE_FORMAT(created_at, '%Y-%m') as month,
            SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as inflow,
            SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as outflow,
            SUM(amount) as net
            FROM alfred_treasury
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY month ORDER BY month");
        $historical = $stmt->fetchAll();
    } catch (Exception $e) {
        $historical = [];
    }

    if (count($historical) < 3) {
        jsonResponse(['success' => false, 'error' => 'Insufficient data for cashflow forecast']);
    }

    $netValues = array_column($historical, 'net');
    $forecasts = linearForecast($netValues, $months_ahead);

    // Calculate runway
    $currentBalance = 0;
    try {
        $currentBalance = (float) $db->query("SELECT COALESCE(SUM(amount), 0) FROM alfred_treasury")->fetchColumn();
    } catch (Exception $e) { /* ignore */ }

    $avgBurn = 0;
    $negativeMonths = array_filter($netValues, fn($v) => $v < 0);
    if (count($negativeMonths) > 0) {
        $avgBurn = abs(array_sum($negativeMonths) / count($negativeMonths));
        $runwayMonths = $avgBurn > 0 ? round($currentBalance / $avgBurn, 1) : null;
    } else {
        $runwayMonths = null; // Profitable
    }

    jsonResponse([
        'success' => true,
        'historical' => $historical,
        'forecasts' => $forecasts,
        'current_balance' => $currentBalance,
        'avg_monthly_burn' => round($avgBurn, 2),
        'runway_months' => $runwayMonths,
        'model' => 'linear_regression',
    ]);
}

function listForecasts(): void {
    $db = getDB();
    $type = sanitize($_GET['type'] ?? '', 30);

    $sql = "SELECT * FROM fin_forecasts WHERE target_date >= CURDATE()";
    $params = [];
    if ($type) { $sql .= " AND forecast_type = ?"; $params[] = $type; }
    $sql .= " ORDER BY target_date LIMIT 50";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    jsonResponse(['success' => true, 'forecasts' => $stmt->fetchAll()]);
}

// ═══ HELPERS ══════════════════════════════════════════════════

function cacheMetric(string $name, float $value, string $period, string $periodStart, string $source = 'internal'): void {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO fin_metrics_cache (metric_name, metric_value, period, period_start, source)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE metric_value = VALUES(metric_value), cached_at = NOW()");
    $stmt->execute([$name, $value, $period, $periodStart, $source]);
}

function storeForecast(string $type, string $targetDate, float $value, float $confidence, string $model): void {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO fin_forecasts (forecast_type, target_date, predicted_value, confidence, model)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE predicted_value = VALUES(predicted_value), confidence = VALUES(confidence)");
    $stmt->execute([$type, $targetDate, $value, $confidence, $model]);
}

function linearForecast(array $values, int $ahead): array {
    $n = count($values);
    if ($n < 2) return [];

    // Simple linear regression: y = a + bx
    $sumX = 0; $sumY = 0; $sumXY = 0; $sumX2 = 0;
    for ($i = 0; $i < $n; $i++) {
        $v = (float) $values[$i];
        $sumX += $i;
        $sumY += $v;
        $sumXY += $i * $v;
        $sumX2 += $i * $i;
    }

    $denominator = ($n * $sumX2 - $sumX * $sumX);
    if ($denominator == 0) {
        // All x values same, just predict average
        $avg = $sumY / $n;
        return array_map(fn($i) => ['period' => $n + $i, 'value' => round($avg, 2), 'confidence' => 50], range(0, $ahead - 1));
    }

    $b = ($n * $sumXY - $sumX * $sumY) / $denominator;
    $a = ($sumY - $b * $sumX) / $n;

    // Calculate R² for confidence
    $yMean = $sumY / $n;
    $ssTot = 0; $ssRes = 0;
    for ($i = 0; $i < $n; $i++) {
        $v = (float) $values[$i];
        $predicted = $a + $b * $i;
        $ssTot += ($v - $yMean) ** 2;
        $ssRes += ($v - $predicted) ** 2;
    }
    $r2 = $ssTot > 0 ? max(0, 1 - $ssRes / $ssTot) : 0;

    $forecasts = [];
    for ($i = 0; $i < $ahead; $i++) {
        $x = $n + $i;
        $predicted = $a + $b * $x;
        // Confidence decreases with distance
        $confidence = max(20, round($r2 * 100 - $i * 5, 2));
        $forecasts[] = [
            'period' => $x,
            'value' => round(max(0, $predicted), 2),
            'confidence' => $confidence,
        ];
    }

    return $forecasts;
}

function movingAvgForecast(array $values, int $ahead, int $window = 3): array {
    $n = count($values);
    if ($n < $window) $window = $n;

    $lastWindow = array_slice($values, -$window);
    $avg = array_sum(array_map('floatval', $lastWindow)) / count($lastWindow);

    $forecasts = [];
    for ($i = 0; $i < $ahead; $i++) {
        $forecasts[] = [
            'period' => $n + $i,
            'value' => round($avg, 2),
            'confidence' => max(30, round(80 - $i * 10, 2)),
        ];
    }

    return $forecasts;
}
