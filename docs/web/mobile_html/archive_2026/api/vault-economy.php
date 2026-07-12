<?php
/**
 * VAULT ECONOMY — Gross Vault Product (GVP) Dashboard
 * CLASSIFICATION: ULTRA SECRET — Commander Eyes Only
 * 
 * Aggregates economic activity across ALL GoSiteMe ecosystems:
 *   - GSM Token Economy (Solana blockchain)
 *   - Kingdom Coins (KGD metaverse currency)
 *   - Billing & Revenue (USD via Stripe)
 *   - Agent Portfolios (AI trading)
 *   - Marketplace & Staking
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../includes/commander-auth.api.inc.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json');

$client_id = getCommanderId();
if (!$client_id) {
    echo json_encode(['error' => 'ACCESS DENIED — GVP Economic Dashboard']);
    exit;
}

$action = $_REQUEST['action'] ?? 'dashboard';
$db = getDB();
$db->exec("SET NAMES utf8mb4");

switch ($action) {

    case 'dashboard':
        $gvp = ['timestamp' => date('Y-m-d H:i:s'), 'sectors' => [], 'totals' => []];

        // ─── GSM Token Economy ───
        $gsm = ['sector' => 'GSM Token', 'icon' => '🪙', 'currency' => 'GSM'];
        try {
            $r = $db->query("SELECT COUNT(*) as holders, COALESCE(SUM(balance),0) as circulating, COALESCE(SUM(total_earned),0) as total_earned, COALESCE(SUM(total_spent),0) as total_spent, COALESCE(SUM(staked_amount),0) as staked FROM crypto_gsm_balances")->fetch(PDO::FETCH_ASSOC);
            $gsm['holders'] = (int)($r['holders'] ?? 0);
            $gsm['circulating'] = round((float)($r['circulating'] ?? 0), 2);
            $gsm['total_earned'] = round((float)($r['total_earned'] ?? 0), 2);
            $gsm['total_spent'] = round((float)($r['total_spent'] ?? 0), 2);
            $gsm['staked'] = round((float)($r['staked'] ?? 0), 2);
            $gsm['velocity'] = $gsm['circulating'] > 0 ? round($gsm['total_spent'] / max($gsm['circulating'], 1), 2) : 0;
        } catch(Exception $e) { $gsm['holders'] = 0; $gsm['circulating'] = 0; $gsm['total_earned'] = 0; $gsm['total_spent'] = 0; $gsm['staked'] = 0; $gsm['velocity'] = 0; }

        try {
            $r = $db->query("SELECT COUNT(*) as txns FROM crypto_gsm_ledger")->fetch(PDO::FETCH_ASSOC);
            $gsm['transactions'] = (int)($r['txns'] ?? 0);
        } catch(Exception $e) { $gsm['transactions'] = 0; }

        try {
            $r = $db->query("SELECT COUNT(*) as stakers, COALESCE(SUM(rewards_earned),0) as rewards FROM gsm_staking WHERE status='active'")->fetch(PDO::FETCH_ASSOC);
            $gsm['active_stakers'] = (int)($r['stakers'] ?? 0);
            $gsm['staking_rewards'] = round((float)($r['rewards'] ?? 0), 2);
        } catch(Exception $e) { $gsm['active_stakers'] = 0; $gsm['staking_rewards'] = 0; }
        $gvp['sectors'][] = $gsm;

        // ─── Kingdom Coins (KGD) Economy ───
        $kgd = ['sector' => 'Kingdom Coins', 'icon' => '👑', 'currency' => 'KGD'];
        try {
            $r = $db->query("SELECT COUNT(*) as players, COALESCE(SUM(kgd_balance),0) as circulating, COALESCE(SUM(total_earned),0) as earned, COALESCE(SUM(total_spent),0) as spent, COALESCE(SUM(games_played),0) as games FROM kingdom_players")->fetch(PDO::FETCH_ASSOC);
            $kgd['players'] = (int)($r['players'] ?? 0);
            $kgd['circulating'] = round((float)($r['circulating'] ?? 0), 2);
            $kgd['total_earned'] = round((float)($r['earned'] ?? 0), 2);
            $kgd['total_spent'] = round((float)($r['spent'] ?? 0), 2);
            $kgd['games_played'] = (int)($r['games'] ?? 0);
        } catch(Exception $e) { $kgd['players'] = 0; $kgd['circulating'] = 0; $kgd['total_earned'] = 0; $kgd['total_spent'] = 0; $kgd['games_played'] = 0; }

        try {
            $r = $db->query("SELECT COUNT(*) as txns FROM kingdom_economy")->fetch(PDO::FETCH_ASSOC);
            $kgd['transactions'] = (int)($r['txns'] ?? 0);
        } catch(Exception $e) { $kgd['transactions'] = 0; }
        $gvp['sectors'][] = $kgd;

        // ─── Solana Blockchain ───
        $sol = ['sector' => 'Solana Blockchain', 'icon' => '◎', 'currency' => 'SOL'];
        try {
            $r = $db->query("SELECT COUNT(DISTINCT client_id) as wallets FROM crypto_wallets WHERE verified=1")->fetch(PDO::FETCH_ASSOC);
            $sol['verified_wallets'] = (int)($r['wallets'] ?? 0);
        } catch(Exception $e) { $sol['verified_wallets'] = 0; }

        try {
            $r = $db->query("SELECT COUNT(*) as txns, COALESCE(SUM(amount_usd),0) as volume_usd FROM crypto_transactions WHERE status='confirmed'")->fetch(PDO::FETCH_ASSOC);
            $sol['confirmed_txns'] = (int)($r['txns'] ?? 0);
            $sol['volume_usd'] = round((float)($r['volume_usd'] ?? 0), 2);
        } catch(Exception $e) { $sol['confirmed_txns'] = 0; $sol['volume_usd'] = 0; }

        try {
            $r = $db->query("SELECT COUNT(*) as portfolios, COALESCE(SUM(allocated_sol),0) as allocated, COALESCE(SUM(total_profit),0) as profit, COALESCE(SUM(total_trades),0) as trades FROM crypto_agent_portfolios WHERE status='active'")->fetch(PDO::FETCH_ASSOC);
            $sol['ai_portfolios'] = (int)($r['portfolios'] ?? 0);
            $sol['allocated_sol'] = round((float)($r['allocated'] ?? 0), 4);
            $sol['ai_profit'] = round((float)($r['profit'] ?? 0), 4);
            $sol['ai_trades'] = (int)($r['trades'] ?? 0);
        } catch(Exception $e) { $sol['ai_portfolios'] = 0; $sol['allocated_sol'] = 0; $sol['ai_profit'] = 0; $sol['ai_trades'] = 0; }
        $gvp['sectors'][] = $sol;

        // ─── Revenue & Billing (USD) ───
        $usd = ['sector' => 'Revenue & Billing', 'icon' => '💵', 'currency' => 'USD'];
        try {
            $r = $db->query("SELECT COUNT(*) as paid, COALESCE(SUM(total),0) as revenue FROM invoices WHERE status='Paid'")->fetch(PDO::FETCH_ASSOC);
            $usd['paid_invoices'] = (int)($r['paid'] ?? 0);
            $usd['total_revenue'] = round((float)($r['revenue'] ?? 0), 2);
        } catch(Exception $e) { $usd['paid_invoices'] = 0; $usd['total_revenue'] = 0; }

        try {
            $r = $db->query("SELECT COUNT(*) as active FROM services WHERE status='Active'")->fetch(PDO::FETCH_ASSOC);
            $usd['active_services'] = (int)($r['active'] ?? 0);
        } catch(Exception $e) { $usd['active_services'] = 0; }

        try {
            $r = $db->query("SELECT COUNT(*) as orders, COALESCE(SUM(total),0) as order_vol FROM orders WHERE status='Active'")->fetch(PDO::FETCH_ASSOC);
            $usd['active_orders'] = (int)($r['orders'] ?? 0);
            $usd['order_volume'] = round((float)($r['order_vol'] ?? 0), 2);
        } catch(Exception $e) { $usd['active_orders'] = 0; $usd['order_volume'] = 0; }

        try {
            $r = $db->query("SELECT COALESCE(SUM(amount),0) as processed FROM payment_transactions WHERE status='completed'")->fetch(PDO::FETCH_ASSOC);
            $usd['total_processed'] = round((float)($r['processed'] ?? 0), 2);
        } catch(Exception $e) { $usd['total_processed'] = 0; }
        $gvp['sectors'][] = $usd;

        // ─── Agent Fleet Economy ───
        $fleet = ['sector' => 'Agent Fleet', 'icon' => '🤖', 'currency' => 'TASKS'];
        try {
            $r = $db->query("SELECT COUNT(*) as agents, COALESCE(SUM(tasks_completed),0) as completed, COALESCE(AVG(success_rate),0) as avg_rate FROM alfred_agent_registry WHERE status='active'")->fetch(PDO::FETCH_ASSOC);
            $fleet['active_agents'] = (int)($r['agents'] ?? 0);
            $fleet['tasks_completed'] = (int)($r['completed'] ?? 0);
            $fleet['avg_success_rate'] = round((float)($r['avg_rate'] ?? 0), 1);
        } catch(Exception $e) { $fleet['active_agents'] = 0; $fleet['tasks_completed'] = 0; $fleet['avg_success_rate'] = 0; }

        try {
            $r = $db->query("SELECT COUNT(*) as total, SUM(status='completed') as done, SUM(status='running') as running FROM alfred_agent_tasks")->fetch(PDO::FETCH_ASSOC);
            $fleet['total_tasks'] = (int)($r['total'] ?? 0);
            $fleet['completed_tasks'] = (int)($r['done'] ?? 0);
            $fleet['running_tasks'] = (int)($r['running'] ?? 0);
        } catch(Exception $e) { $fleet['total_tasks'] = 0; $fleet['completed_tasks'] = 0; $fleet['running_tasks'] = 0; }
        $gvp['sectors'][] = $fleet;

        // ─── Marketplace ───
        $mkt = ['sector' => 'Marketplace', 'icon' => '🏪', 'currency' => 'MIXED'];
        try {
            $r = $db->query("SELECT COUNT(*) as orders, SUM(status='open') as open_orders, COALESCE(SUM(amount * price),0) as volume FROM gsm_marketplace_orders")->fetch(PDO::FETCH_ASSOC);
            $mkt['total_orders'] = (int)($r['orders'] ?? 0);
            $mkt['open_orders'] = (int)($r['open_orders'] ?? 0);
            $mkt['gsm_volume'] = round((float)($r['volume'] ?? 0), 2);
        } catch(Exception $e) { $mkt['total_orders'] = 0; $mkt['open_orders'] = 0; $mkt['gsm_volume'] = 0; }
        $gvp['sectors'][] = $mkt;

        // ─── GVP Totals ───
        $gvp['totals'] = [
            'gross_vault_product' => 'GVP',
            'description' => 'Total economic value generated across all GoSiteMe ecosystems',
            'usd_revenue' => $usd['total_revenue'] ?? 0,
            'usd_processed' => $usd['total_processed'] ?? 0,
            'sol_volume_usd' => $sol['volume_usd'] ?? 0,
            'gsm_circulating' => $gsm['circulating'] ?? 0,
            'gsm_velocity' => $gsm['velocity'] ?? 0,
            'kgd_circulating' => $kgd['circulating'] ?? 0,
            'total_wallets' => ($sol['verified_wallets'] ?? 0),
            'total_players' => ($kgd['players'] ?? 0),
            'total_transactions' => ($gsm['transactions'] ?? 0) + ($kgd['transactions'] ?? 0) + ($sol['confirmed_txns'] ?? 0),
            'ai_portfolios' => $sol['ai_portfolios'] ?? 0,
            'ai_profit_sol' => $sol['ai_profit'] ?? 0,
            'agent_fleet_tasks' => $fleet['tasks_completed'] ?? 0,
            'active_services' => $usd['active_services'] ?? 0,
            'economies_active' => 6,
        ];

        echo json_encode($gvp);
        break;

    case 'gsm-history':
        $limit = min(100, max(10, intval($_GET['limit'] ?? 50)));
        try {
            $stmt = $db->prepare("SELECT * FROM crypto_gsm_ledger WHERE client_id = ? ORDER BY created_at DESC LIMIT ?");
            dbExecute($stmt, [$client_id, $limit]);
            echo json_encode(['history' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch(Exception $e) { echo json_encode(['history' => []]); }
        break;

    case 'kgd-history':
        $limit = min(100, max(10, intval($_GET['limit'] ?? 50)));
        try {
            $stmt = $db->query("SELECT * FROM kingdom_economy ORDER BY created_at DESC LIMIT " . $limit);
            echo json_encode(['history' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch(Exception $e) { echo json_encode(['history' => []]); }
        break;

    case 'revenue-summary':
        try {
            $monthly = $db->query("SELECT DATE_FORMAT(paid_date, '%Y-%m') as month, COUNT(*) as invoices, SUM(total) as revenue FROM invoices WHERE status='Paid' AND paid_date IS NOT NULL GROUP BY month ORDER BY month DESC LIMIT 12")->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['monthly_revenue' => $monthly]);
        } catch(Exception $e) { echo json_encode(['monthly_revenue' => []]); }
        break;

    default:
        echo json_encode(['actions' => ['dashboard','gsm-history','kgd-history','revenue-summary']]);
}
