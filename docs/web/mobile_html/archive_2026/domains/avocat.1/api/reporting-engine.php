<?php
/**
 * Reporting Engine API — Real analytics from existing DB tables
 * Usage reports, revenue, agent performance, tool usage, client analytics
 */
define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Internal-Secret');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

function rptIsInternal(): bool {
    $secret = defined('INTERNAL_SECRET') ? INTERNAL_SECRET : '';
    return $secret && isset($_SERVER['HTTP_X_INTERNAL_SECRET']) && hash_equals($secret, $_SERVER['HTTP_X_INTERNAL_SECRET']);
}
function rptRequireAuth(): void {
    if (rptIsInternal()) return;
    if (empty($_SESSION['logged_in']) || empty($_SESSION['client_id'])) {
        jsonResponse(['error' => 'Authentication required'], 401);
require_once dirname(__DIR__) . '/includes/api-security.php';
    }
}
function rptGetClientId(): int {
    if (rptIsInternal()) {
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        return (int) ($body['client_id'] ?? $_SESSION['client_id'] ?? 0);
    }
    return (int) ($_SESSION['client_id'] ?? 0);
}

// ─── Schema ───────────────────────────────────────────────────
function ensureReportingSchema(): void {
    $db = getDB();
    try {
    $db->exec("CREATE TABLE IF NOT EXISTS report_saved (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        name VARCHAR(128) NOT NULL,
        report_type VARCHAR(50) NOT NULL,
        parameters JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_client (client_id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS report_exports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        report_type VARCHAR(50) NOT NULL,
        format ENUM('csv','json') DEFAULT 'csv',
        row_count INT DEFAULT 0,
        file_path VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_client (client_id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS report_schedules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        report_type VARCHAR(50) NOT NULL,
        frequency ENUM('daily','weekly','monthly') DEFAULT 'weekly',
        parameters JSON,
        email VARCHAR(255),
        last_run DATETIME,
        next_run DATETIME,
        active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_client (client_id)
    )");
    } catch (PDOException $e) {
        error_log('Reporting schema error: ' . $e->getMessage());
    }
}
ensureReportingSchema();

$action = sanitize($_GET['action'] ?? $_POST['action'] ?? '', 50);

switch ($action) {
    case 'usage_report':       rptRequireAuth(); usageReport(); break;
    case 'revenue_report':     rptRequireAuth(); revenueReport(); break;
    case 'agent_performance':  rptRequireAuth(); agentPerformance(); break;
    case 'tool_usage':         rptRequireAuth(); toolUsage(); break;
    case 'client_report':      rptRequireAuth(); clientReport(); break;
    case 'dashboard_kpis':     rptRequireAuth(); dashboardKPIs(); break;
    case 'conversation_stats': rptRequireAuth(); conversationStats(); break;
    case 'growth_metrics':     rptRequireAuth(); growthMetrics(); break;
    case 'save_report':        rptRequireAuth(); saveReport(); break;
    case 'saved_reports':      rptRequireAuth(); savedReports(); break;
    case 'export':             rptRequireAuth(); exportReport(); break;
    case 'schedule':           rptRequireAuth(); scheduleReport(); break;
    default: jsonResponse(['error' => 'Unknown action', 'actions' => [
        'usage_report','revenue_report','agent_performance','tool_usage','client_report',
        'dashboard_kpis','conversation_stats','growth_metrics','save_report','saved_reports','export','schedule'
    ]], 400);
}

// ─── Helper: safe table check ─────────────────────────────────
function tableExists(PDO $db, string $table): bool {
    $safe = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $stmt = $db->query("SHOW TABLES LIKE '{$safe}'");
    return $stmt->rowCount() > 0;
}

// ─── Usage Report ─────────────────────────────────────────────
function usageReport(): void {
    $db = getDB();
    $clientId = rptGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_GET;
    $days = min(365, max(1, (int) ($input['days'] ?? 30)));

    $report = ['period_days' => $days];

    // Conversations
    if (tableExists($db, 'conversations')) {
        $stmt = $db->prepare("SELECT COUNT(*) as total, COUNT(DISTINCT DATE(created_at)) as active_days FROM conversations WHERE client_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)");
        $stmt->execute([$clientId, $days]);
        $report['conversations'] = $stmt->fetch();

        // Daily breakdown
        $stmt = $db->prepare("SELECT DATE(created_at) as date, COUNT(*) as count FROM conversations WHERE client_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY DATE(created_at) ORDER BY date");
        $stmt->execute([$clientId, $days]);
        $report['daily_conversations'] = $stmt->fetchAll();
    }

    // Messages
    if (tableExists($db, 'conversation_messages')) {
        $stmt = $db->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) as user_msgs, SUM(CASE WHEN role = 'assistant' THEN 1 ELSE 0 END) as assistant_msgs FROM conversation_messages cm JOIN conversations c ON cm.conversation_id = c.id WHERE c.client_id = ? AND cm.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)");
        $stmt->execute([$clientId, $days]);
        $report['messages'] = $stmt->fetch();
    }

    // Tool usage
    if (tableExists($db, 'tool_usage_log')) {
        $stmt = $db->prepare("SELECT COUNT(*) as total, COUNT(DISTINCT tool_name) as unique_tools FROM tool_usage_log WHERE client_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)");
        $stmt->execute([$clientId, $days]);
        $report['tools'] = $stmt->fetch();
    }

    jsonResponse(['success' => true, 'report' => 'usage', 'data' => $report]);
}

// ─── Revenue Report ───────────────────────────────────────────
function revenueReport(): void {
    $db = getDB();
    $clientId = rptGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_GET;
    $days = min(365, max(1, (int) ($input['days'] ?? 30)));

    $report = ['period_days' => $days];

    // Invoices
    if (tableExists($db, 'invoices')) {
        $stmt = $db->prepare("SELECT COUNT(*) as count, COALESCE(SUM(total), 0) as total_revenue, COALESCE(AVG(total), 0) as avg_invoice FROM invoices WHERE client_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) AND status = 'paid'");
        $stmt->execute([$clientId, $days]);
        $report['invoices'] = $stmt->fetch();

        $stmt = $db->prepare("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count, SUM(total) as revenue FROM invoices WHERE client_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) AND status = 'paid' GROUP BY month ORDER BY month");
        $stmt->execute([$clientId, $days]);
        $report['monthly_revenue'] = $stmt->fetchAll();
    }

    // Marketplace earnings
    if (tableExists($db, 'alfred_marketplace_purchases')) {
        $stmt = $db->prepare("SELECT COUNT(*) as sales, COALESCE(SUM(seller_earnings), 0) as earnings FROM alfred_marketplace_purchases WHERE seller_id = ? AND status = 'completed' AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)");
        $stmt->execute([$clientId, $days]);
        $report['marketplace_earnings'] = $stmt->fetch();
    }

    // Treasury/Billing
    if (tableExists($db, 'alfred_treasury')) {
        $stmt = $db->prepare("SELECT entry_type, COALESCE(SUM(amount_cents), 0) / 100 as total FROM alfred_treasury WHERE client_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY entry_type");
        $stmt->execute([$clientId, $days]);
        $report['treasury'] = $stmt->fetchAll();
    }

    jsonResponse(['success' => true, 'report' => 'revenue', 'data' => $report]);
}

// ─── Agent Performance ────────────────────────────────────────
function agentPerformance(): void {
    $db = getDB();
    $clientId = rptGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_GET;
    $days = min(365, max(1, (int) ($input['days'] ?? 30)));

    $report = ['period_days' => $days];

    // Fleet agents
    if (tableExists($db, 'fleet_agents')) {
        $stmt = $db->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active FROM fleet_agents WHERE client_id = ?");
        $stmt->execute([$clientId]);
        $report['agents'] = $stmt->fetch();
    }

    // Agent conversations
    if (tableExists($db, 'fleet_agent_conversations')) {
        $stmt = $db->prepare("SELECT fac.agent_id, fa.name as agent_name, COUNT(*) as conversations, AVG(fac.satisfaction_score) as avg_satisfaction FROM fleet_agent_conversations fac JOIN fleet_agents fa ON fac.agent_id = fa.id WHERE fa.client_id = ? AND fac.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY fac.agent_id ORDER BY conversations DESC LIMIT 20");
        $stmt->execute([$clientId, $days]);
        $report['agent_conversations'] = $stmt->fetchAll();
    }

    // Agent task completions
    if (tableExists($db, 'fleet_agent_tasks')) {
        $stmt = $db->prepare("SELECT fat.agent_id, fa.name as agent_name, COUNT(*) as total_tasks, SUM(CASE WHEN fat.status = 'completed' THEN 1 ELSE 0 END) as completed, AVG(TIMESTAMPDIFF(SECOND, fat.created_at, fat.completed_at)) as avg_seconds FROM fleet_agent_tasks fat JOIN fleet_agents fa ON fat.agent_id = fa.id WHERE fa.client_id = ? AND fat.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY fat.agent_id ORDER BY total_tasks DESC LIMIT 20");
        $stmt->execute([$clientId, $days]);
        $report['agent_tasks'] = $stmt->fetchAll();
    }

    jsonResponse(['success' => true, 'report' => 'agent_performance', 'data' => $report]);
}

// ─── Tool Usage ───────────────────────────────────────────────
function toolUsage(): void {
    $db = getDB();
    $clientId = rptGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_GET;
    $days = min(365, max(1, (int) ($input['days'] ?? 30)));

    $report = ['period_days' => $days];

    if (tableExists($db, 'tool_usage_log')) {
        // Top tools
        $stmt = $db->prepare("SELECT tool_name, COUNT(*) as uses, COUNT(DISTINCT DATE(created_at)) as days_used FROM tool_usage_log WHERE client_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY tool_name ORDER BY uses DESC LIMIT 25");
        $stmt->execute([$clientId, $days]);
        $report['top_tools'] = $stmt->fetchAll();

        // Tool categories
        $stmt = $db->prepare("SELECT SUBSTRING_INDEX(tool_name, '_', 1) as category, COUNT(*) as uses FROM tool_usage_log WHERE client_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY category ORDER BY uses DESC");
        $stmt->execute([$clientId, $days]);
        $report['categories'] = $stmt->fetchAll();

        // Daily tool usage trend
        $stmt = $db->prepare("SELECT DATE(created_at) as date, COUNT(*) as uses FROM tool_usage_log WHERE client_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY date ORDER BY date");
        $stmt->execute([$clientId, $days]);
        $report['daily_trend'] = $stmt->fetchAll();
    } else {
        $report['note'] = 'Tool usage logging not yet active. Tools are tracked in extended tool dispatch.';
    }

    jsonResponse(['success' => true, 'report' => 'tool_usage', 'data' => $report]);
}

// ─── Client Report ────────────────────────────────────────────
function clientReport(): void {
    $db = getDB();
    $clientId = rptGetClientId();

    $report = [];

    // Account info
    if (tableExists($db, 'clients')) {
        $stmt = $db->prepare("SELECT id, email, firstname, lastname, company, plan, created_at FROM clients WHERE id = ?");
        $stmt->execute([$clientId]);
        $report['account'] = $stmt->fetch();
    }

    // Domains
    if (tableExists($db, 'domains')) {
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM domains WHERE client_id = ?");
        $stmt->execute([$clientId]);
        $report['domains'] = $stmt->fetch();
    }

    // Conversations total
    if (tableExists($db, 'conversations')) {
        $stmt = $db->prepare("SELECT COUNT(*) as total, MIN(created_at) as first_conversation, MAX(created_at) as last_conversation FROM conversations WHERE client_id = ?");
        $stmt->execute([$clientId]);
        $report['conversations'] = $stmt->fetch();
    }

    // Agents
    if (tableExists($db, 'fleet_agents')) {
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM fleet_agents WHERE client_id = ?");
        $stmt->execute([$clientId]);
        $report['agents'] = $stmt->fetch();
    }

    // Gamification
    if (tableExists($db, 'gamify_user_stats')) {
        $stmt = $db->prepare("SELECT total_xp, level, current_streak, longest_streak FROM gamify_user_stats WHERE client_id = ?");
        $stmt->execute([$clientId]);
        $report['gamification'] = $stmt->fetch() ?: ['total_xp' => 0, 'level' => 1];
    }

    jsonResponse(['success' => true, 'report' => 'client_summary', 'data' => $report]);
}

// ─── Dashboard KPIs ───────────────────────────────────────────
function dashboardKPIs(): void {
    try {
    $db = getDB();
    $clientId = rptGetClientId();

    $kpis = [];

    // Today's activity
    if (tableExists($db, 'conversations')) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM conversations WHERE client_id = ? AND DATE(created_at) = CURDATE()");
        $stmt->execute([$clientId]);
        $kpis['conversations_today'] = (int) $stmt->fetchColumn();
    }

    // 7-day trend
    if (tableExists($db, 'conversations')) {
        $stmt = $db->prepare("SELECT DATE(created_at) as date, COUNT(*) as count FROM conversations WHERE client_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY date ORDER BY date");
        $stmt->execute([$clientId]);
        $kpis['weekly_trend'] = $stmt->fetchAll();
    }

    // Tool usage today
    if (tableExists($db, 'tool_usage_log')) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM tool_usage_log WHERE client_id = ? AND DATE(created_at) = CURDATE()");
        $stmt->execute([$clientId]);
        $kpis['tools_today'] = (int) $stmt->fetchColumn();
    }

    // Active agents
    if (tableExists($db, 'fleet_agents')) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM fleet_agents WHERE client_id = ? AND status = 'active'");
        $stmt->execute([$clientId]);
        $kpis['active_agents'] = (int) $stmt->fetchColumn();
    }

    // Streak
    if (tableExists($db, 'gamify_user_stats')) {
        $stmt = $db->prepare("SELECT current_streak, level FROM gamify_user_stats WHERE client_id = ?");
        $stmt->execute([$clientId]);
        $g = $stmt->fetch();
        $kpis['streak'] = (int) ($g['current_streak'] ?? 0);
        $kpis['level'] = (int) ($g['level'] ?? 1);
    }

    jsonResponse(['success' => true, 'kpis' => $kpis]);
    } catch (\Throwable $e) {
        error_log('[reporting-engine] ' . $e->getMessage());
        jsonResponse(['error' => 'KPI computation failed'], 500);
    }
}

// ─── Conversation Stats ──────────────────────────────────────
function conversationStats(): void {
    $db = getDB();
    $clientId = rptGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_GET;
    $days = min(365, max(1, (int) ($input['days'] ?? 30)));

    $report = ['period_days' => $days];

    if (tableExists($db, 'conversations')) {
        // Total and unique days
        $stmt = $db->prepare("SELECT COUNT(*) as total, COUNT(DISTINCT DATE(created_at)) as active_days FROM conversations WHERE client_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)");
        $stmt->execute([$clientId, $days]);
        $report['overview'] = $stmt->fetch();

        // By agent
        if (tableExists($db, 'fleet_agents')) {
            $stmt = $db->prepare("SELECT c.agent_id, COALESCE(fa.name, 'Alfred') as agent_name, COUNT(*) as conversations FROM conversations c LEFT JOIN fleet_agents fa ON c.agent_id = fa.id WHERE c.client_id = ? AND c.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY c.agent_id ORDER BY conversations DESC LIMIT 10");
            $stmt->execute([$clientId, $days]);
            $report['by_agent'] = $stmt->fetchAll();
        }

        // Hour of day distribution
        $stmt = $db->prepare("SELECT HOUR(created_at) as hour, COUNT(*) as count FROM conversations WHERE client_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY hour ORDER BY hour");
        $stmt->execute([$clientId, $days]);
        $report['by_hour'] = $stmt->fetchAll();

        // Day of week distribution
        $stmt = $db->prepare("SELECT DAYNAME(created_at) as day, COUNT(*) as count FROM conversations WHERE client_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY day ORDER BY FIELD(day, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')");
        $stmt->execute([$clientId, $days]);
        $report['by_day_of_week'] = $stmt->fetchAll();
    }

    jsonResponse(['success' => true, 'report' => 'conversation_stats', 'data' => $report]);
}

// ─── Growth Metrics ───────────────────────────────────────────
function growthMetrics(): void {
    $db = getDB();
    $clientId = rptGetClientId();

    $metrics = [];

    // Monthly conversation growth
    if (tableExists($db, 'conversations')) {
        $stmt = $db->prepare("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as conversations FROM conversations WHERE client_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) GROUP BY month ORDER BY month");
        $stmt->execute([$clientId]);
        $months = $stmt->fetchAll();
        $metrics['monthly_conversations'] = $months;

        // Month-over-month growth
        if (count($months) >= 2) {
            $last = (int) end($months)['conversations'];
            $prev = (int) $months[count($months) - 2]['conversations'];
            $metrics['mom_growth'] = $prev > 0 ? round(($last - $prev) / $prev * 100, 1) : 0;
        }
    }

    // Agent fleet growth
    if (tableExists($db, 'fleet_agents')) {
        $stmt = $db->prepare("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as agents_created FROM fleet_agents WHERE client_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) GROUP BY month ORDER BY month");
        $stmt->execute([$clientId]);
        $metrics['monthly_agents'] = $stmt->fetchAll();
    }

    // XP growth
    if (tableExists($db, 'gamify_xp_log')) {
        $stmt = $db->prepare("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, SUM(xp_amount) as xp FROM gamify_xp_log WHERE client_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) GROUP BY month ORDER BY month");
        $stmt->execute([$clientId]);
        $metrics['monthly_xp'] = $stmt->fetchAll();
    }

    jsonResponse(['success' => true, 'report' => 'growth_metrics', 'data' => $metrics]);
}

// ─── Save Report ──────────────────────────────────────────────
function saveReport(): void {
    $db = getDB();
    $clientId = rptGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: [];

    $name = sanitize($input['name'] ?? '', 128);
    $type = sanitize($input['report_type'] ?? '', 50);
    if (!$name || !$type) jsonResponse(['error' => 'name and report_type required'], 400);

    $params = json_encode($input['parameters'] ?? []);
    $stmt = $db->prepare("INSERT INTO report_saved (client_id, name, report_type, parameters) VALUES (?, ?, ?, ?)");
    $stmt->execute([$clientId, $name, $type, $params]);

    jsonResponse(['success' => true, 'id' => (int) $db->lastInsertId()]);
}

function savedReports(): void {
    $db = getDB();
    $clientId = rptGetClientId();
    $stmt = $db->prepare("SELECT id, name, report_type, parameters, created_at FROM report_saved WHERE client_id = ? ORDER BY created_at DESC LIMIT 50");
    $stmt->execute([$clientId]);
    jsonResponse(['success' => true, 'reports' => $stmt->fetchAll()]);
}

// ─── Export ───────────────────────────────────────────────────
function exportReport(): void {
    $db = getDB();
    $clientId = rptGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: [];

    $type = sanitize($input['report_type'] ?? '', 50);
    $format = in_array($input['format'] ?? '', ['csv', 'json']) ? $input['format'] : 'json';

    if (!$type) jsonResponse(['error' => 'report_type required'], 400);

    // Log the export
    $stmt = $db->prepare("INSERT INTO report_exports (client_id, report_type, format) VALUES (?, ?, ?)");
    $stmt->execute([$clientId, $type, $format]);

    jsonResponse(['success' => true, 'message' => "Report '$type' queued for export as $format", 'export_id' => (int) $db->lastInsertId()]);
}

// ─── Schedule ─────────────────────────────────────────────────
function scheduleReport(): void {
    $db = getDB();
    $clientId = rptGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: [];

    $type = sanitize($input['report_type'] ?? '', 50);
    $freq = in_array($input['frequency'] ?? '', ['daily', 'weekly', 'monthly']) ? $input['frequency'] : 'weekly';
    $email = sanitize($input['email'] ?? '', 255);

    if (!$type) jsonResponse(['error' => 'report_type required'], 400);

    $nextRun = match($freq) {
        'daily' => date('Y-m-d H:i:s', strtotime('+1 day')),
        'weekly' => date('Y-m-d H:i:s', strtotime('+1 week')),
        'monthly' => date('Y-m-d H:i:s', strtotime('+1 month')),
    };

    $stmt = $db->prepare("INSERT INTO report_schedules (client_id, report_type, frequency, parameters, email, next_run) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$clientId, $type, $freq, json_encode($input['parameters'] ?? []), $email, $nextRun]);

    jsonResponse(['success' => true, 'schedule_id' => (int) $db->lastInsertId(), 'next_run' => $nextRun]);
}
