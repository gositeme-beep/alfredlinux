<?php
/**
 * Ecosystem Bridge API — Interconnects All Platform Systems
 * ══════════════════════════════════════════════════════════════
 * Links billing ↔ mining ↔ wallet ↔ trading ↔ services.
 * Allows users to:
 *   - Pay invoices with mined GSM tokens
 *   - Apply mining rewards as billing credit
 *   - View unified account balance (fiat + GSM)
 *   - Auto-fund services from mining earnings
 *
 * Endpoints:
 *   GET  ?action=account_overview   → Unified balance across all systems
 *   POST ?action=apply_gsm_credit   → Apply GSM mining balance to invoice
 *   POST ?action=auto_pay_setup     → Configure auto-pay from mining
 *   GET  ?action=auto_pay_status    → Check auto-pay configuration
 *   GET  ?action=earning_summary    → Mining + search earning breakdown
 *   GET  ?action=ecosystem_links    → All connected system status
 *   POST ?action=link_wallet        → Link Solana wallet to billing account
 */

define('GOSITEME_API', true);
require_once dirname(__DIR__) . '/api/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

if (session_status() === PHP_SESSION_NONE) session_start();
$clientId = (int)($_SESSION['client_id'] ?? 0);

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

if (!$clientId) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

$db = getDB();
if (!$db) {
    echo json_encode(['error' => 'Database unavailable']);
    exit;
}

// ── Ensure tables ───────────────────────────────────────────
$db->exec("CREATE TABLE IF NOT EXISTS ecosystem_bridges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    bridge_type ENUM('gsm_credit','auto_pay','wallet_link','service_fund') NOT NULL,
    config JSON,
    status ENUM('active','paused','disabled') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_client_type (client_id, bridge_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS ecosystem_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    transaction_type ENUM('gsm_to_credit','credit_to_invoice','mining_payout','auto_pay','service_fund') NOT NULL,
    gsm_amount DECIMAL(18,9) DEFAULT 0,
    fiat_amount DECIMAL(10,2) DEFAULT 0,
    gsm_rate DECIMAL(10,6) DEFAULT 0,
    reference_id VARCHAR(100),
    reference_type VARCHAR(50),
    status ENUM('pending','completed','failed','reversed') DEFAULT 'pending',
    details JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_client (client_id),
    INDEX idx_type (transaction_type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Route ───────────────────────────────────────────────────
switch ($action) {
    case 'account_overview':   handleAccountOverview($db, $clientId); break;
    case 'apply_gsm_credit':   handleApplyGsmCredit($db, $clientId, $input); break;
    case 'auto_pay_setup':     handleAutoPaySetup($db, $clientId, $input); break;
    case 'auto_pay_status':    handleAutoPayStatus($db, $clientId); break;
    case 'earning_summary':    handleEarningSummary($db, $clientId); break;
    case 'ecosystem_links':    handleEcosystemLinks($db, $clientId); break;
    case 'link_wallet':        handleLinkWallet($db, $clientId, $input); break;
    default:
        echo json_encode(['error' => 'Invalid action', 'valid' => ['account_overview','apply_gsm_credit','auto_pay_setup','auto_pay_status','earning_summary','ecosystem_links','link_wallet']]);
}

// ═══════════════════════════════════════════════════════════════
// GSM RATE — Get current GSM to USD rate
// ═══════════════════════════════════════════════════════════════
function getGsmRate($db): float {
    // Check cached rate
    try {
        $stmt = $db->query("SELECT value FROM system_settings WHERE setting_key = 'gsm_usd_rate' LIMIT 1");
        $cached = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($cached) return (float)$cached['value'];
    } catch (Exception $e) {}

    // Default rate (1 GSM = $0.001 initially)
    return 0.001;
}

// ═══════════════════════════════════════════════════════════════
// MINING BALANCE
// ═══════════════════════════════════════════════════════════════
function getMiningBalance($db, int $clientId): array {
    $balance = ['total_earned' => 0, 'total_spent' => 0, 'available' => 0];
    try {
        // Total earned from mining
        $stmt = $db->prepare("SELECT COALESCE(SUM(reward_gsm), 0) as total FROM search_mining_rewards WHERE user_id = ?");
        $stmt->execute([$clientId]);
        $balance['total_earned'] = (float)$stmt->fetchColumn();

        // Total spent via ecosystem transactions
        $stmt = $db->prepare("SELECT COALESCE(SUM(gsm_amount), 0) as total FROM ecosystem_transactions WHERE client_id = ? AND status = 'completed' AND transaction_type IN ('gsm_to_credit','auto_pay','service_fund')");
        $stmt->execute([$clientId]);
        $balance['total_spent'] = (float)$stmt->fetchColumn();

        $balance['available'] = $balance['total_earned'] - $balance['total_spent'];
    } catch (Exception $e) {}
    return $balance;
}

// ═══════════════════════════════════════════════════════════════
// ACCOUNT OVERVIEW — Unified balance
// ═══════════════════════════════════════════════════════════════
function handleAccountOverview($db, int $clientId) {
    $gsmRate = getGsmRate($db);
    $mining = getMiningBalance($db, $clientId);

    // Billing balance
    $billingBalance = 0;
    $unpaidInvoices = 0;
    $activeServices = 0;
    try {
        $stmt = $db->prepare("SELECT COALESCE(SUM(total), 0) as total FROM invoices WHERE client_id = ? AND status IN ('Unpaid','Overdue')");
        $stmt->execute([$clientId]);
        $unpaidInvoices = (float)$stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COALESCE(credit, 0) FROM clients WHERE id = ?");
        $stmt->execute([$clientId]);
        $billingBalance = (float)$stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM services WHERE client_id = ? AND status = 'Active'");
        $stmt->execute([$clientId]);
        $activeServices = (int)$stmt->fetchColumn();
    } catch (Exception $e) {}

    // Auto-pay config
    $autoPay = null;
    try {
        $stmt = $db->prepare("SELECT config, status FROM ecosystem_bridges WHERE client_id = ? AND bridge_type = 'auto_pay'");
        $stmt->execute([$clientId]);
        $bridge = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($bridge) {
            $autoPay = array_merge(json_decode($bridge['config'], true) ?: [], ['status' => $bridge['status']]);
        }
    } catch (Exception $e) {}

    // Recent ecosystem transactions
    $recent = [];
    try {
        $stmt = $db->prepare("SELECT * FROM ecosystem_transactions WHERE client_id = ? ORDER BY created_at DESC LIMIT 10");
        $stmt->execute([$clientId]);
        $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}

    echo json_encode([
        'success' => true,
        'overview' => [
            'gsm_balance' => [
                'total_earned' => $mining['total_earned'],
                'total_spent' => $mining['total_spent'],
                'available' => $mining['available'],
                'usd_value' => round($mining['available'] * $gsmRate, 2),
            ],
            'billing' => [
                'credit_balance' => $billingBalance,
                'unpaid_invoices' => $unpaidInvoices,
                'active_services' => $activeServices,
            ],
            'gsm_rate' => $gsmRate,
            'auto_pay' => $autoPay,
            'can_pay_with_gsm' => $mining['available'] * $gsmRate >= 0.01,
        ],
        'recent_transactions' => $recent,
        'timestamp' => date('c'),
    ]);
}

// ═══════════════════════════════════════════════════════════════
// APPLY GSM CREDIT — Convert mining GSM to billing credit
// ═══════════════════════════════════════════════════════════════
function handleApplyGsmCredit($db, int $clientId, array $input) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['error' => 'POST required']);
        return;
    }

    $gsmAmount = (float)($input['gsm_amount'] ?? 0);
    $invoiceId = (int)($input['invoice_id'] ?? 0); // Optional: apply to specific invoice

    if ($gsmAmount <= 0) {
        echo json_encode(['error' => 'Positive GSM amount required']);
        return;
    }

    $mining = getMiningBalance($db, $clientId);
    if ($gsmAmount > $mining['available']) {
        echo json_encode(['error' => 'Insufficient GSM balance', 'available' => $mining['available']]);
        return;
    }

    $gsmRate = getGsmRate($db);
    $fiatValue = round($gsmAmount * $gsmRate, 2);

    if ($fiatValue < 0.01) {
        echo json_encode(['error' => 'GSM amount too small to convert']);
        return;
    }

    $db->beginTransaction();
    try {
        // Record the ecosystem transaction
        $txId = 'ECO-' . date('Ymd') . '-' . bin2hex(random_bytes(4));
        $stmt = $db->prepare("INSERT INTO ecosystem_transactions (client_id, transaction_type, gsm_amount, fiat_amount, gsm_rate, reference_id, reference_type, status, details) VALUES (?,?,?,?,?,?,?,?,?)");

        if ($invoiceId) {
            // Apply directly to invoice
            $stmt2 = $db->prepare("SELECT id, client_id, total, status FROM invoices WHERE id = ? AND client_id = ?");
            $stmt2->execute([$invoiceId, $clientId]);
            $invoice = $stmt2->fetch(PDO::FETCH_ASSOC);
            if (!$invoice) throw new Exception('Invoice not found');
            if ($invoice['status'] === 'Paid') throw new Exception('Invoice already paid');

            $applyAmount = min($fiatValue, (float)$invoice['total']);
            $gsmUsed = $applyAmount / $gsmRate;

            // Update invoice
            $newTotal = (float)$invoice['total'] - $applyAmount;
            if ($newTotal <= 0) {
                $db->prepare("UPDATE invoices SET status = 'Paid', paid_at = NOW() WHERE id = ?")->execute([$invoiceId]);
            } else {
                $db->prepare("UPDATE invoices SET total = ? WHERE id = ?")->execute([$newTotal, $invoiceId]);
            }

            $stmt->execute([$clientId, 'gsm_to_credit', $gsmUsed, $applyAmount, $gsmRate, $txId, 'invoice:' . $invoiceId, 'completed', json_encode(['invoice_id' => $invoiceId, 'applied' => $applyAmount])]);
        } else {
            // Add as account credit
            $db->prepare("UPDATE clients SET credit = credit + ? WHERE id = ?")->execute([$fiatValue, $clientId]);
            $stmt->execute([$clientId, 'gsm_to_credit', $gsmAmount, $fiatValue, $gsmRate, $txId, 'account_credit', 'completed', json_encode(['credit_added' => $fiatValue])]);
        }

        $db->commit();
        echo json_encode([
            'success' => true,
            'transaction_id' => $txId,
            'gsm_used' => $invoiceId ? ($gsmUsed ?? $gsmAmount) : $gsmAmount,
            'fiat_value' => $invoiceId ? ($applyAmount ?? $fiatValue) : $fiatValue,
            'gsm_rate' => $gsmRate,
            'new_balance' => getMiningBalance($db, $clientId),
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['error' => $e->getMessage()]);
    }
}

// ═══════════════════════════════════════════════════════════════
// AUTO-PAY SETUP — Configure automatic invoice payment from mining
// ═══════════════════════════════════════════════════════════════
function handleAutoPaySetup($db, int $clientId, array $input) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['error' => 'POST required']);
        return;
    }

    $enabled = (bool)($input['enabled'] ?? false);
    $maxGsmPerMonth = (float)($input['max_gsm_per_month'] ?? 0);
    $minBalance = (float)($input['min_balance_keep'] ?? 100); // Keep at least this many GSM

    if ($enabled && ($maxGsmPerMonth <= 0 || $maxGsmPerMonth > 100000000)) {
        echo json_encode(['error' => 'Set a reasonable monthly GSM limit (1-100M)']);
        return;
    }

    $config = json_encode([
        'max_gsm_per_month' => $maxGsmPerMonth,
        'min_balance_keep' => $minBalance,
        'gsm_used_this_month' => 0,
        'month_reset' => date('Y-m-01'),
    ]);

    $stmt = $db->prepare("INSERT INTO ecosystem_bridges (client_id, bridge_type, config, status) VALUES (?, 'auto_pay', ?, ?) ON DUPLICATE KEY UPDATE config = VALUES(config), status = VALUES(status)");
    $stmt->execute([$clientId, $config, $enabled ? 'active' : 'paused']);

    echo json_encode([
        'success' => true,
        'auto_pay' => [
            'enabled' => $enabled,
            'max_gsm_per_month' => $maxGsmPerMonth,
            'min_balance_keep' => $minBalance,
            'status' => $enabled ? 'active' : 'paused',
        ],
    ]);
}

// ═══════════════════════════════════════════════════════════════
// AUTO-PAY STATUS
// ═══════════════════════════════════════════════════════════════
function handleAutoPayStatus($db, int $clientId) {
    $stmt = $db->prepare("SELECT * FROM ecosystem_bridges WHERE client_id = ? AND bridge_type = 'auto_pay'");
    $stmt->execute([$clientId]);
    $bridge = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$bridge) {
        echo json_encode(['success' => true, 'auto_pay' => null, 'message' => 'Auto-pay not configured']);
        return;
    }

    $config = json_decode($bridge['config'], true) ?: [];

    // Check current month usage
    $monthStart = date('Y-m-01');
    $stmt = $db->prepare("SELECT COALESCE(SUM(gsm_amount), 0) FROM ecosystem_transactions WHERE client_id = ? AND transaction_type = 'auto_pay' AND status = 'completed' AND created_at >= ?");
    $stmt->execute([$clientId, $monthStart]);
    $usedThisMonth = (float)$stmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'auto_pay' => [
            'status' => $bridge['status'],
            'max_gsm_per_month' => $config['max_gsm_per_month'] ?? 0,
            'min_balance_keep' => $config['min_balance_keep'] ?? 0,
            'gsm_used_this_month' => $usedThisMonth,
            'remaining_allowance' => max(0, ($config['max_gsm_per_month'] ?? 0) - $usedThisMonth),
        ],
    ]);
}

// ═══════════════════════════════════════════════════════════════
// EARNING SUMMARY
// ═══════════════════════════════════════════════════════════════
function handleEarningSummary($db, int $clientId) {
    $gsmRate = getGsmRate($db);

    // Mining earnings by source
    $mining = ['total' => 0, 'by_type' => [], 'daily' => []];
    try {
        $stmt = $db->prepare("SELECT COALESCE(SUM(reward_gsm), 0) FROM search_mining_rewards WHERE user_id = ?");
        $stmt->execute([$clientId]);
        $mining['total'] = (float)$stmt->fetchColumn();

        $stmt = $db->prepare("SELECT reward_type, COALESCE(SUM(reward_gsm), 0) as total, COUNT(*) as count FROM search_mining_rewards WHERE user_id = ? GROUP BY reward_type");
        $stmt->execute([$clientId]);
        $mining['by_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Last 7 days
        $stmt = $db->prepare("SELECT DATE(created_at) as day, COALESCE(SUM(reward_gsm), 0) as total FROM search_mining_rewards WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY day");
        $stmt->execute([$clientId]);
        $mining['daily'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}

    // Spending summary
    $spending = ['total_gsm' => 0, 'total_fiat' => 0, 'by_type' => []];
    try {
        $stmt = $db->prepare("SELECT transaction_type, COALESCE(SUM(gsm_amount), 0) as gsm, COALESCE(SUM(fiat_amount), 0) as fiat, COUNT(*) as count FROM ecosystem_transactions WHERE client_id = ? AND status = 'completed' GROUP BY transaction_type");
        $stmt->execute([$clientId]);
        $spending['by_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($spending['by_type'] as $t) {
            $spending['total_gsm'] += (float)$t['gsm'];
            $spending['total_fiat'] += (float)$t['fiat'];
        }
    } catch (Exception $e) {}

    echo json_encode([
        'success' => true,
        'earning' => [
            'mining' => $mining,
            'spending' => $spending,
            'net_gsm' => $mining['total'] - $spending['total_gsm'],
            'net_usd' => round(($mining['total'] - $spending['total_gsm']) * $gsmRate, 2),
            'gsm_rate' => $gsmRate,
        ],
    ]);
}

// ═══════════════════════════════════════════════════════════════
// ECOSYSTEM LINKS — Connected system status
// ═══════════════════════════════════════════════════════════════
function handleEcosystemLinks($db, int $clientId) {
    $links = [];

    // Mining
    try {
        $stmt = $db->prepare("SELECT COUNT(*) as sessions FROM search_mining_rewards WHERE user_id = ?");
        $stmt->execute([$clientId]);
        $count = (int)$stmt->fetchColumn();
        $links['mining'] = ['connected' => $count > 0, 'sessions' => $count];
    } catch (Exception $e) {
        $links['mining'] = ['connected' => false, 'sessions' => 0];
    }

    // Wallet
    try {
        $stmt = $db->prepare("SELECT wallet_address FROM client_wallets WHERE client_id = ? AND is_primary = 1 LIMIT 1");
        $stmt->execute([$clientId]);
        $wallet = $stmt->fetchColumn();
        $links['wallet'] = ['connected' => (bool)$wallet, 'address' => $wallet ? substr($wallet, 0, 6) . '...' . substr($wallet, -4) : null];
    } catch (Exception $e) {
        $links['wallet'] = ['connected' => false, 'address' => null];
    }

    // Billing
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM services WHERE client_id = ? AND status = 'Active'");
        $stmt->execute([$clientId]);
        $services = (int)$stmt->fetchColumn();
        $stmt = $db->prepare("SELECT COUNT(*) FROM invoices WHERE client_id = ? AND status IN ('Unpaid','Overdue')");
        $stmt->execute([$clientId]);
        $unpaid = (int)$stmt->fetchColumn();
        $links['billing'] = ['connected' => true, 'active_services' => $services, 'unpaid_invoices' => $unpaid];
    } catch (Exception $e) {
        $links['billing'] = ['connected' => false, 'active_services' => 0, 'unpaid_invoices' => 0];
    }

    // Auto-pay
    try {
        $stmt = $db->prepare("SELECT status FROM ecosystem_bridges WHERE client_id = ? AND bridge_type = 'auto_pay'");
        $stmt->execute([$clientId]);
        $apStatus = $stmt->fetchColumn();
        $links['auto_pay'] = ['configured' => (bool)$apStatus, 'status' => $apStatus ?: 'not_configured'];
    } catch (Exception $e) {
        $links['auto_pay'] = ['configured' => false, 'status' => 'not_configured'];
    }

    // Fleet
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM alfred_fleets WHERE user_id = ?");
        $stmt->execute([$clientId]);
        $fleets = (int)$stmt->fetchColumn();
        $links['fleet'] = ['connected' => $fleets > 0, 'fleets' => $fleets];
    } catch (Exception $e) {
        $links['fleet'] = ['connected' => false, 'fleets' => 0];
    }

    echo json_encode(['success' => true, 'links' => $links, 'timestamp' => date('c')]);
}

// ═══════════════════════════════════════════════════════════════
// LINK WALLET — Connect Solana wallet to billing
// ═══════════════════════════════════════════════════════════════
function handleLinkWallet($db, int $clientId, array $input) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['error' => 'POST required']);
        return;
    }

    $address = trim($input['wallet_address'] ?? '');
    if (!preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $address)) {
        echo json_encode(['error' => 'Invalid Solana wallet address']);
        return;
    }

    // Check not already linked to another account
    try {
        $stmt = $db->prepare("SELECT client_id FROM client_wallets WHERE wallet_address = ? AND client_id != ?");
        $stmt->execute([$address, $clientId]);
        if ($stmt->fetchColumn()) {
            echo json_encode(['error' => 'Wallet already linked to another account']);
            return;
        }
    } catch (Exception $e) {
        // Table may not exist, create it
        $db->exec("CREATE TABLE IF NOT EXISTS client_wallets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            client_id INT NOT NULL,
            wallet_address VARCHAR(50) NOT NULL,
            label VARCHAR(100) DEFAULT 'Primary',
            is_primary TINYINT(1) DEFAULT 1,
            verified TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_client (client_id),
            UNIQUE KEY uk_address (wallet_address)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    // Upsert wallet
    $stmt = $db->prepare("INSERT INTO client_wallets (client_id, wallet_address, is_primary) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE client_id = VALUES(client_id)");
    $stmt->execute([$clientId, $address]);

    // Record bridge
    $stmt = $db->prepare("INSERT INTO ecosystem_bridges (client_id, bridge_type, config, status) VALUES (?, 'wallet_link', ?, 'active') ON DUPLICATE KEY UPDATE config = VALUES(config), status = 'active'");
    $stmt->execute([$clientId, json_encode(['wallet_address' => $address])]);

    echo json_encode([
        'success' => true,
        'wallet' => [
            'address' => $address,
            'display' => substr($address, 0, 6) . '...' . substr($address, -4),
            'linked' => true,
        ],
    ]);
}
