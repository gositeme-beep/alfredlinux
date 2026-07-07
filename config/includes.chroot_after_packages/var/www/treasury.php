<?php
/**
 * ═══════════════════════════════════════════
 *  Central Treasury & National Bank — Level 6: Sovereign State
 * ═══════════════════════════════════════════
 */
require_once __DIR__ . '/includes/db-config.inc.php';
$db = getSharedDB();
require_once __DIR__ . '/includes/auth-gate.inc.php';
require_once __DIR__ . '/includes/rank-guard.inc.php';
if (empty($_SESSION['csrf_treasury'])) $_SESSION['csrf_treasury'] = bin2hex(random_bytes(32));
requireRank(6);

$isCommander = ($clientId === 33);
$isFlag      = ($userRankTier >= 9) || $isCommander;
$isOfficer   = ($userRankTier >= 6) || $isCommander;
$msg = '';
$msgType = '';

// ── Auto-create tables ──
$db->exec("CREATE TABLE IF NOT EXISTS treasury_reserves (
    id INT AUTO_INCREMENT PRIMARY KEY,
    currency VARCHAR(10) DEFAULT 'GSM',
    total_supply DECIMAL(18,2) DEFAULT 0,
    in_circulation DECIMAL(18,2) DEFAULT 0,
    in_reserve DECIMAL(18,2) DEFAULT 0,
    last_audit_at TIMESTAMP NULL,
    audited_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS treasury_policy (
    id INT AUTO_INCREMENT PRIMARY KEY,
    policy_name VARCHAR(255) NOT NULL,
    policy_type ENUM('monetary','fiscal','trade') DEFAULT 'fiscal',
    value TEXT NOT NULL,
    effective_date TIMESTAMP NULL,
    expiry_date TIMESTAMP NULL,
    set_by INT NOT NULL,
    approved_by INT DEFAULT NULL,
    status ENUM('active','suspended','expired') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS treasury_bonds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bond_code VARCHAR(20) NOT NULL,
    bond_name VARCHAR(255) NOT NULL,
    bond_type ENUM('war','infrastructure','research') DEFAULT 'infrastructure',
    face_value DECIMAL(12,2) NOT NULL,
    interest_rate DECIMAL(5,2) DEFAULT 0,
    maturity_days INT DEFAULT 365,
    total_issued INT DEFAULT 0,
    total_sold INT DEFAULT 0,
    status ENUM('open','closed','matured') DEFAULT 'open',
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    matures_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS treasury_bond_holdings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bond_id INT NOT NULL,
    client_id INT NOT NULL,
    quantity INT DEFAULT 1,
    purchased_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    redeemed_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS treasury_taxes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tax_type ENUM('transaction','territory','luxury','import') DEFAULT 'transaction',
    rate_pct DECIMAL(5,2) DEFAULT 0,
    applies_to TEXT,
    minimum_exempt DECIMAL(12,2) DEFAULT 0,
    enacted_by INT NOT NULL,
    effective_date TIMESTAMP NULL,
    status ENUM('active','suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS treasury_budget (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fiscal_year INT NOT NULL,
    department VARCHAR(255) NOT NULL,
    allocation DECIMAL(14,2) DEFAULT 0,
    spent DECIMAL(14,2) DEFAULT 0,
    remaining DECIMAL(14,2) DEFAULT 0,
    approved_by INT DEFAULT NULL,
    status ENUM('proposed','approved','active','closed') DEFAULT 'proposed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS economic_indicators (
    id INT AUTO_INCREMENT PRIMARY KEY,
    indicator_name VARCHAR(100) NOT NULL,
    indicator_type ENUM('gdp','unemployment','trade_balance','inflation','confidence') DEFAULT 'gdp',
    value DECIMAL(14,4) NOT NULL,
    measured_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Seed reserve if empty ──
$resCount = (int)$db->query("SELECT COUNT(*) FROM treasury_reserves")->fetchColumn();
if ($resCount === 0) {
    $db->exec("INSERT INTO treasury_reserves (currency, total_supply, in_circulation, in_reserve) VALUES ('GSM', 1000000.00, 250000.00, 750000.00)");
}

// ── Seed economic indicators if empty ──
$indCount = (int)$db->query("SELECT COUNT(*) FROM economic_indicators")->fetchColumn();
if ($indCount === 0) {
    $indicators = [
        ['Gross Domestic Product', 'gdp', 100000],
        ['Unemployment Rate', 'unemployment', 5.2],
        ['Trade Balance', 'trade_balance', 15000],
        ['Inflation Rate', 'inflation', 2.1],
        ['Consumer Confidence', 'confidence', 72.5],
    ];
    $ins = $db->prepare("INSERT INTO economic_indicators (indicator_name, indicator_type, value) VALUES (?,?,?)");
    foreach ($indicators as $i) $ins->execute($i);
}

$csrf = $_SESSION['csrf_treasury'];

// ── POST Handlers ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, $_POST['csrf'] ?? '')) {
        $msg = 'Invalid security token.'; $msgType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'issue_bond' && $isCommander) {
            $bName = trim($_POST['bond_name'] ?? '');
            $bType = $_POST['bond_type'] ?? 'infrastructure';
            $faceV = (float)($_POST['face_value'] ?? 0);
            $intR  = (float)($_POST['interest_rate'] ?? 0);
            $matD  = (int)($_POST['maturity_days'] ?? 365);
            $totI  = (int)($_POST['total_issued'] ?? 100);
            $validBT = ['war','infrastructure','research'];
            if ($bName === '' || $faceV <= 0 || !in_array($bType, $validBT, true)) {
                $msg = 'Bond name, valid type, and face value required.'; $msgType = 'error';
            } else {
                $code = strtoupper(substr($bType, 0, 3)) . '-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
                $matAt = date('Y-m-d H:i:s', strtotime("+$matD days"));
                $stmt = $db->prepare("INSERT INTO treasury_bonds (bond_code, bond_name, bond_type, face_value, interest_rate, maturity_days, total_issued, matures_at) VALUES (?,?,?,?,?,?,?,?)");
                $stmt->execute([$code, $bName, $bType, $faceV, $intR, $matD, $totI, $matAt]);
                awardXP($clientId, 'bond_issued', ['code' => $code]);
                $msg = "Bond <strong>$code</strong> issued: $totI units at \$" . number_format($faceV, 2) . " each."; $msgType = 'success';
            }
        } elseif ($action === 'buy_bond') {
            $bondId = (int)($_POST['bond_id'] ?? 0);
            $qty    = max(1, (int)($_POST['quantity'] ?? 1));
            $bond = $db->prepare("SELECT * FROM treasury_bonds WHERE id = ? AND status = 'open'");
            $bond->execute([$bondId]);
            $bondRow = $bond->fetch(PDO::FETCH_ASSOC);
            if (!$bondRow) {
                $msg = 'Bond not available.'; $msgType = 'error';
            } elseif ($bondRow['total_sold'] + $qty > $bondRow['total_issued']) {
                $msg = 'Not enough bonds available.'; $msgType = 'error';
            } else {
                $db->prepare("INSERT INTO treasury_bond_holdings (bond_id, client_id, quantity) VALUES (?,?,?)")->execute([$bondId, $clientId, $qty]);
                $db->prepare("UPDATE treasury_bonds SET total_sold = total_sold + ? WHERE id = ?")->execute([$qty, $bondId]);
                if ($bondRow['total_sold'] + $qty >= $bondRow['total_issued']) {
                    $db->prepare("UPDATE treasury_bonds SET status = 'closed' WHERE id = ?")->execute([$bondId]);
                }
                awardXP($clientId, 'bond_purchased', ['code' => $bondRow['bond_code'], 'qty' => $qty]);
                $msg = "Purchased $qty unit(s) of <strong>" . htmlspecialchars($bondRow['bond_code']) . "</strong>."; $msgType = 'success';
            }
        } elseif ($action === 'redeem_bond') {
            $holdingId = (int)($_POST['holding_id'] ?? 0);
            $holding = $db->prepare("SELECT bh.*, tb.matures_at, tb.bond_code, tb.interest_rate, tb.face_value FROM treasury_bond_holdings bh JOIN treasury_bonds tb ON tb.id = bh.bond_id WHERE bh.id = ? AND bh.client_id = ? AND bh.redeemed_at IS NULL");
            $holding->execute([$holdingId, $clientId]);
            $hRow = $holding->fetch(PDO::FETCH_ASSOC);
            if (!$hRow) {
                $msg = 'Holding not found or already redeemed.'; $msgType = 'error';
            } elseif (strtotime($hRow['matures_at']) > time()) {
                $msg = 'Bond has not matured yet (matures ' . date('M j, Y', strtotime($hRow['matures_at'])) . ').'; $msgType = 'error';
            } else {
                $payout = $hRow['quantity'] * $hRow['face_value'] * (1 + $hRow['interest_rate'] / 100);
                $db->prepare("UPDATE treasury_bond_holdings SET redeemed_at = NOW() WHERE id = ?")->execute([$holdingId]);
                $msg = "Redeemed <strong>" . htmlspecialchars($hRow['bond_code']) . "</strong> — payout: \$" . number_format($payout, 2) . " GSM."; $msgType = 'success';
            }
        } elseif ($action === 'set_policy' && $isCommander) {
            $pName = trim($_POST['policy_name'] ?? '');
            $pType = $_POST['policy_type'] ?? 'fiscal';
            $pVal  = trim($_POST['policy_value'] ?? '');
            $validPT = ['monetary','fiscal','trade'];
            if ($pName === '' || $pVal === '' || !in_array($pType, $validPT, true)) {
                $msg = 'Policy name, type, and value required.'; $msgType = 'error';
            } else {
                $stmt = $db->prepare("INSERT INTO treasury_policy (policy_name, policy_type, value, effective_date, set_by, approved_by) VALUES (?,?,?,NOW(),?,?)");
                $stmt->execute([$pName, $pType, $pVal, $clientId, $clientId]);
                $msg = "Policy <strong>" . htmlspecialchars($pName) . "</strong> enacted."; $msgType = 'success';
            }
        } elseif ($action === 'propose_budget' && ($isFlag || $isCommander)) {
            $fy   = (int)($_POST['fiscal_year'] ?? date('Y'));
            $dept = trim($_POST['budget_dept'] ?? '');
            $alloc = (float)($_POST['allocation'] ?? 0);
            if ($dept === '' || $alloc <= 0) {
                $msg = 'Department and allocation required.'; $msgType = 'error';
            } else {
                $stmt = $db->prepare("INSERT INTO treasury_budget (fiscal_year, department, allocation, remaining) VALUES (?,?,?,?)");
                $stmt->execute([$fy, $dept, $alloc, $alloc]);
                $msg = "Budget proposed: \$" . number_format($alloc, 2) . " for " . htmlspecialchars($dept) . " (FY $fy)."; $msgType = 'success';
            }
        } elseif ($action === 'approve_budget' && $isCommander) {
            $budId = (int)($_POST['budget_id'] ?? 0);
            $stmt = $db->prepare("UPDATE treasury_budget SET status = 'approved', approved_by = ? WHERE id = ? AND status = 'proposed'");
            $stmt->execute([$clientId, $budId]);
            $msg = $stmt->rowCount() ? 'Budget approved.' : 'Budget not found.';
            $msgType = $stmt->rowCount() ? 'success' : 'error';

        } elseif ($action === 'enact_tax' && ($isFlag || $isCommander)) {
            $tType = $_POST['tax_type'] ?? 'transaction';
            $tRate = (float)($_POST['tax_rate'] ?? 0);
            $tApplies = trim($_POST['tax_applies'] ?? '');
            $tExempt  = (float)($_POST['tax_exempt'] ?? 0);
            $validTT = ['transaction','territory','luxury','import'];
            if ($tRate <= 0 || !in_array($tType, $validTT, true)) {
                $msg = 'Valid tax type and rate required.'; $msgType = 'error';
            } else {
                $stmt = $db->prepare("INSERT INTO treasury_taxes (tax_type, rate_pct, applies_to, minimum_exempt, enacted_by, effective_date) VALUES (?,?,?,?,?,NOW())");
                $stmt->execute([$tType, $tRate, $tApplies ?: null, $tExempt, $clientId]);
                $msg = ucfirst($tType) . " tax enacted at $tRate%."; $msgType = 'success';
            }
        } elseif ($action === 'audit_reserves' && $isFlag) {
            $db->prepare("UPDATE treasury_reserves SET last_audit_at = NOW(), audited_by = ? WHERE id = 1")->execute([$clientId]);
            awardXP($clientId, 'treasury_audit', []);
            $msg = 'Treasury reserves audited.'; $msgType = 'success';

        } elseif ($action === 'update_indicator' && ($isFlag || $isCommander)) {
            $indType = $_POST['ind_type'] ?? '';
            $indVal  = (float)($_POST['ind_value'] ?? 0);
            $validIT = ['gdp','unemployment','trade_balance','inflation','confidence'];
            if (!in_array($indType, $validIT, true)) {
                $msg = 'Invalid indicator.'; $msgType = 'error';
            } else {
                $stmt = $db->prepare("INSERT INTO economic_indicators (indicator_name, indicator_type, value) VALUES (?,?,?)");
                $names = ['gdp'=>'Gross Domestic Product','unemployment'=>'Unemployment Rate','trade_balance'=>'Trade Balance','inflation'=>'Inflation Rate','confidence'=>'Consumer Confidence'];
                $stmt->execute([$names[$indType] ?? $indType, $indType, $indVal]);
                $msg = ucfirst(str_replace('_', ' ', $indType)) . " updated to $indVal."; $msgType = 'success';
            }
        } else {
            $msg = 'Unauthorized or unknown action.'; $msgType = 'error';
        }
    }
    $_SESSION['csrf_treasury'] = bin2hex(random_bytes(32));
    $csrf = $_SESSION['csrf_treasury'];
}

// ── Data Fetch ──
$tab = $_GET['tab'] ?? 'overview';
$reserves   = $db->query("SELECT * FROM treasury_reserves LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$bonds      = $db->query("SELECT * FROM treasury_bonds ORDER BY issued_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$policies   = $db->query("SELECT tp.*, CONCAT(c.firstname,' ',c.lastname) AS set_by_name FROM treasury_policy tp LEFT JOIN tblclients c ON c.id = tp.set_by ORDER BY tp.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$budgets    = $db->query("SELECT tb.*, CONCAT(c.firstname,' ',c.lastname) AS approver_name FROM treasury_budget tb LEFT JOIN tblclients c ON c.id = tb.approved_by ORDER BY tb.fiscal_year DESC, tb.department")->fetchAll(PDO::FETCH_ASSOC);
$taxes      = $db->query("SELECT tt.*, CONCAT(c.firstname,' ',c.lastname) AS enacter_name FROM treasury_taxes tt LEFT JOIN tblclients c ON c.id = tt.enacted_by ORDER BY tt.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$indicators = $db->query("SELECT * FROM economic_indicators ORDER BY measured_at DESC")->fetchAll(PDO::FETCH_ASSOC);
// latest of each indicator type
$latestInd = [];
foreach ($indicators as $ind) {
    if (!isset($latestInd[$ind['indicator_type']])) $latestInd[$ind['indicator_type']] = $ind;
}
$myHoldings = [];
$hq = $db->prepare("SELECT bh.*, tb.bond_code, tb.bond_name, tb.face_value, tb.interest_rate, tb.matures_at FROM treasury_bond_holdings bh JOIN treasury_bonds tb ON tb.id = bh.bond_id WHERE bh.client_id = ? ORDER BY bh.purchased_at DESC");
$hq->execute([$clientId]);
$myHoldings = $hq->fetchAll(PDO::FETCH_ASSOC);

$totalBudget = array_sum(array_column($budgets, 'allocation'));
$totalSpent  = array_sum(array_column($budgets, 'spent'));

$pageTitle = 'Central Treasury & National Bank';
include __DIR__ . '/includes/site-header.inc.php';
?>
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<style>
.tr-wrap{max-width:1100px;margin:0 auto;padding:1.5rem}
.tr-bg{background:#0a0a14;min-height:100vh;color:#e2e8f0}
.tr-card{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:10px;padding:1.25rem;margin-bottom:1rem}
.tr-card:hover{border-color:#22c55e;box-shadow:0 0 12px rgba(34,197,94,.12)}
.tr-title{font-size:1.6rem;font-weight:700;color:#f1f5f9;margin-bottom:.25rem;display:flex;align-items:center;gap:.5rem}
.tr-sub{color:#94a3b8;font-size:.85rem}
.tr-badge{display:inline-block;padding:2px 10px;border-radius:999px;font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
.tr-tabs{display:flex;gap:.5rem;margin-bottom:1.25rem;flex-wrap:wrap}
.tr-tab{padding:.4rem 1rem;border-radius:6px;cursor:pointer;font-size:.8rem;background:#2a2a4a;color:#94a3b8;text-decoration:none;font-weight:600;border:none}
.tr-tab.active{background:#22c55e;color:#000}
.tr-stat-bar{display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem}
.tr-stat{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:8px;padding:.75rem 1.25rem;flex:1;min-width:130px;text-align:center}
.tr-stat .val{font-size:1.3rem;font-weight:700;color:#22c55e}
.tr-stat .lbl{font-size:.7rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px}
.tr-btn{background:#22c55e;color:#000;border:none;padding:.5rem 1.25rem;border-radius:6px;cursor:pointer;font-size:.85rem;font-weight:600;transition:.2s}
.tr-btn:hover{background:#16a34a}
.tr-btn-sm{padding:.3rem .75rem;font-size:.75rem}
.tr-btn-outline{background:transparent;border:1px solid #22c55e;color:#22c55e}
.tr-btn-outline:hover{background:#22c55e;color:#000}
.tr-btn-gold{background:#d4a017;color:#000}.tr-btn-gold:hover{background:#e2b340}
.tr-btn-blue{background:#3b82f6;color:#fff}.tr-btn-blue:hover{background:#2563eb}
.tr-btn-red{background:#ef4444;color:#fff}.tr-btn-red:hover{background:#dc2626}
.tr-input,.tr-select,.tr-textarea{width:100%;background:#0a0a14;border:1px solid #2a2a4a;color:#e2e8f0;padding:.5rem .75rem;border-radius:6px;font-size:.85rem;box-sizing:border-box}
.tr-textarea{min-height:100px;resize:vertical}
.tr-label{display:block;color:#94a3b8;font-size:.75rem;margin-bottom:.25rem;text-transform:uppercase;letter-spacing:.5px}
.tr-msg{padding:.75rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:.85rem}
.tr-msg-success{background:rgba(34,197,94,.12);border:1px solid #22c55e;color:#86efac}
.tr-msg-error{background:rgba(239,68,68,.12);border:1px solid #ef4444;color:#fca5a5}
.tr-modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:900;align-items:center;justify-content:center}
.tr-modal-bg.open{display:flex}
.tr-modal{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:12px;padding:1.5rem;width:90%;max-width:580px;max-height:80vh;overflow-y:auto}
.tr-modal h3{color:#f1f5f9;margin:0 0 1rem}
.tr-form-row{margin-bottom:.75rem}
.tr-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem;margin-bottom:1rem}
.tr-indicator{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:8px;padding:1rem;text-align:center}
.tr-indicator .val{font-size:1.8rem;font-weight:700}
.tr-indicator .lbl{font-size:.7rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px}
.tr-indicator .change{font-size:.75rem;margin-top:.25rem}
.tr-reserve-bar{height:24px;border-radius:12px;background:#0a0a14;overflow:hidden;margin-top:.5rem}
.tr-reserve-fill{height:100%;border-radius:12px;transition:width .3s}
</style>
<div class="tr-bg">
<div class="tr-wrap">
    <div class="tr-title"><i class="fas fa-university"></i> Central Treasury &amp; National Bank</div>
    <p class="tr-sub" style="margin-bottom:1.25rem">Sovereign monetary system and fiscal management — Officer rank and above</p>

    <?php if ($msg): ?>
        <div class="tr-msg tr-msg-<?= $msgType ?>"><?= $msg ?></div>
    <?php endif; ?>

    <!-- Reserve Stats -->
    <?php if ($reserves): ?>
    <div class="tr-stat-bar">
        <div class="tr-stat"><div class="val">$<?= number_format($reserves['total_supply'], 0) ?></div><div class="lbl">Total Supply (GSM)</div></div>
        <div class="tr-stat"><div class="val" style="color:#3b82f6">$<?= number_format($reserves['in_circulation'], 0) ?></div><div class="lbl">In Circulation</div></div>
        <div class="tr-stat"><div class="val" style="color:#d4a017">$<?= number_format($reserves['in_reserve'], 0) ?></div><div class="lbl">In Reserve</div></div>
        <div class="tr-stat"><div class="val" style="color:#8b5cf6"><?= $reserves['total_supply'] > 0 ? round($reserves['in_reserve'] / $reserves['total_supply'] * 100, 1) : 0 ?>%</div><div class="lbl">Reserve Ratio</div></div>
    </div>
    <div class="tr-card" style="padding:.75rem 1rem">
        <div style="display:flex;justify-content:space-between;font-size:.8rem;color:#94a3b8;margin-bottom:.25rem">
            <span>Reserve Ratio</span>
            <span><?= $reserves['last_audit_at'] ? 'Last audit: ' . date('M j, Y', strtotime($reserves['last_audit_at'])) : 'Not yet audited' ?></span>
        </div>
        <div class="tr-reserve-bar">
            <?php $ratio = $reserves['total_supply'] > 0 ? $reserves['in_reserve'] / $reserves['total_supply'] * 100 : 0; ?>
            <div class="tr-reserve-fill" style="width:<?= $ratio ?>%;background:linear-gradient(90deg,#22c55e,#d4a017)"></div>
        </div>
        <?php if ($isFlag): ?>
            <form method="POST" style="margin-top:.5rem;display:inline"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="audit_reserves"><button class="tr-btn-sm tr-btn tr-btn-outline"><i class="fas fa-clipboard-check"></i> Audit Reserves</button></form>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="tr-tabs">
        <a href="?tab=overview" class="tr-tab <?= $tab==='overview'?'active':'' ?>"><i class="fas fa-chart-line"></i> Economy</a>
        <a href="?tab=bonds" class="tr-tab <?= $tab==='bonds'?'active':'' ?>"><i class="fas fa-receipt"></i> Bonds</a>
        <a href="?tab=budget" class="tr-tab <?= $tab==='budget'?'active':'' ?>"><i class="fas fa-wallet"></i> Budget</a>
        <a href="?tab=taxes" class="tr-tab <?= $tab==='taxes'?'active':'' ?>"><i class="fas fa-percent"></i> Taxes</a>
        <a href="?tab=policy" class="tr-tab <?= $tab==='policy'?'active':'' ?>"><i class="fas fa-gavel"></i> Policy</a>
        <a href="?tab=holdings" class="tr-tab <?= $tab==='holdings'?'active':'' ?>"><i class="fas fa-briefcase"></i> My Holdings</a>
    </div>

    <!-- ═══ TAB: ECONOMY (INDICATORS) ═══ -->
    <?php if ($tab === 'overview'): ?>
        <div class="tr-grid">
            <?php
            $indColors = ['gdp'=>'#22c55e','unemployment'=>'#ef4444','trade_balance'=>'#3b82f6','inflation'=>'#f59e0b','confidence'=>'#8b5cf6'];
            $indIcons  = ['gdp'=>'chart-line','unemployment'=>'user-slash','trade_balance'=>'scale-balanced','inflation'=>'arrow-trend-up','confidence'=>'face-smile'];
            $indUnits  = ['gdp'=>'$','unemployment'=>'%','trade_balance'=>'$','inflation'=>'%','confidence'=>''];
            foreach (['gdp','unemployment','trade_balance','inflation','confidence'] as $type):
                $ind = $latestInd[$type] ?? null;
            ?>
                <div class="tr-indicator">
                    <i class="fas fa-<?= $indIcons[$type] ?>" style="font-size:1.5rem;color:<?= $indColors[$type] ?>;margin-bottom:.5rem"></i>
                    <div class="val" style="color:<?= $indColors[$type] ?>"><?= $indUnits[$type] ?><?= $ind ? number_format($ind['value'], $type === 'gdp' || $type === 'trade_balance' ? 0 : 1) : '—' ?></div>
                    <div class="lbl"><?= ucwords(str_replace('_', ' ', $type)) ?></div>
                    <?php if ($ind): ?><div class="change" style="color:#64748b"><?= date('M j, Y', strtotime($ind['measured_at'])) ?></div><?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if ($isFlag || $isCommander): ?>
            <div class="tr-card">
                <h4 style="color:#f1f5f9;font-size:.9rem;margin-bottom:.5rem"><i class="fas fa-edit"></i> Update Economic Indicator</h4>
                <form method="POST" style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:flex-end">
                    <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="update_indicator">
                    <div><label class="tr-label">Indicator</label><select name="ind_type" class="tr-select" style="width:160px">
                        <option value="gdp">GDP</option><option value="unemployment">Unemployment</option><option value="trade_balance">Trade Balance</option><option value="inflation">Inflation</option><option value="confidence">Confidence</option>
                    </select></div>
                    <div><label class="tr-label">Value</label><input type="number" name="ind_value" class="tr-input" style="width:120px" step="0.01" required></div>
                    <button class="tr-btn tr-btn-sm"><i class="fas fa-chart-bar"></i> Update</button>
                </form>
            </div>
        <?php endif; ?>

        <!-- Budget Summary -->
        <h3 style="color:#f1f5f9;font-size:1rem;margin:1.25rem 0 .75rem"><i class="fas fa-chart-pie"></i> Budget Overview (FY <?= date('Y') ?>)</h3>
        <div class="tr-stat-bar">
            <div class="tr-stat"><div class="val">$<?= number_format($totalBudget, 0) ?></div><div class="lbl">Total Allocated</div></div>
            <div class="tr-stat"><div class="val" style="color:#ef4444">$<?= number_format($totalSpent, 0) ?></div><div class="lbl">Total Spent</div></div>
            <div class="tr-stat"><div class="val" style="color:#3b82f6">$<?= number_format($totalBudget - $totalSpent, 0) ?></div><div class="lbl">Remaining</div></div>
        </div>

    <!-- ═══ TAB: BONDS ═══ -->
    <?php elseif ($tab === 'bonds'): ?>
        <?php if ($isCommander): ?>
            <div style="margin-bottom:1rem"><button class="tr-btn tr-btn-gold" onclick="document.getElementById('modalBond').classList.add('open')"><i class="fas fa-plus"></i> Issue Bond</button></div>
        <?php endif; ?>
        <?php
        $bondColors = ['open'=>'#22c55e','closed'=>'#f59e0b','matured'=>'#8b5cf6'];
        $bondIcons  = ['war'=>'shield-halved','infrastructure'=>'building','research'=>'flask'];
        foreach ($bonds as $bond): ?>
            <div class="tr-card">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div>
                        <i class="fas fa-<?= $bondIcons[$bond['bond_type']] ?? 'receipt' ?>" style="color:#d4a017"></i>
                        <strong style="color:#d4a017;font-size:.8rem;margin-left:.25rem"><?= htmlspecialchars($bond['bond_code']) ?></strong>
                        <span style="color:#f1f5f9;margin-left:.5rem"><?= htmlspecialchars($bond['bond_name']) ?></span>
                    </div>
                    <span class="tr-badge" style="background:<?= $bondColors[$bond['status']] ?>20;color:<?= $bondColors[$bond['status']] ?>;border:1px solid <?= $bondColors[$bond['status']] ?>40"><?= strtoupper($bond['status']) ?></span>
                </div>
                <div style="display:flex;gap:1.5rem;margin-top:.5rem;font-size:.8rem;color:#94a3b8;flex-wrap:wrap">
                    <span><strong>Face:</strong> $<?= number_format($bond['face_value'], 2) ?></span>
                    <span><strong>Rate:</strong> <?= $bond['interest_rate'] ?>%</span>
                    <span><strong>Maturity:</strong> <?= $bond['maturity_days'] ?>d</span>
                    <span><strong>Sold:</strong> <?= (int)$bond['total_sold'] ?>/<?= (int)$bond['total_issued'] ?></span>
                    <span><strong>Matures:</strong> <?= $bond['matures_at'] ? date('M j, Y', strtotime($bond['matures_at'])) : '—' ?></span>
                </div>
                <?php if ($bond['status'] === 'open' && $bond['total_sold'] < $bond['total_issued']): ?>
                    <form method="POST" style="margin-top:.5rem;display:flex;gap:.5rem;align-items:center">
                        <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="buy_bond"><input type="hidden" name="bond_id" value="<?= $bond['id'] ?>">
                        <input type="number" name="quantity" class="tr-input" style="width:70px" value="1" min="1" max="<?= $bond['total_issued'] - $bond['total_sold'] ?>">
                        <button class="tr-btn-sm tr-btn"><i class="fas fa-cart-shopping"></i> Buy</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if (empty($bonds)): ?>
            <div class="tr-card" style="text-align:center;color:#64748b"><i class="fas fa-receipt" style="font-size:2rem;margin-bottom:.5rem"></i><p>No bonds issued.</p></div>
        <?php endif; ?>

    <!-- ═══ TAB: BUDGET ═══ -->
    <?php elseif ($tab === 'budget'): ?>
        <?php if ($isFlag || $isCommander): ?>
            <div style="margin-bottom:1rem"><button class="tr-btn" onclick="document.getElementById('modalBudget').classList.add('open')"><i class="fas fa-plus"></i> Propose Budget</button></div>
        <?php endif; ?>
        <?php
        $budColors = ['proposed'=>'#f59e0b','approved'=>'#22c55e','active'=>'#3b82f6','closed'=>'#64748b'];
        foreach ($budgets as $bud): ?>
            <div class="tr-card">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div><strong style="color:#f1f5f9"><?= htmlspecialchars($bud['department']) ?></strong> <span style="color:#64748b;font-size:.8rem">(FY <?= (int)$bud['fiscal_year'] ?>)</span></div>
                    <span class="tr-badge" style="background:<?= $budColors[$bud['status']] ?>20;color:<?= $budColors[$bud['status']] ?>;border:1px solid <?= $budColors[$bud['status']] ?>40"><?= strtoupper($bud['status']) ?></span>
                </div>
                <div style="display:flex;gap:1.5rem;margin-top:.5rem;font-size:.85rem">
                    <span style="color:#22c55e"><strong>Allocated:</strong> $<?= number_format($bud['allocation'], 2) ?></span>
                    <span style="color:#ef4444"><strong>Spent:</strong> $<?= number_format($bud['spent'], 2) ?></span>
                    <span style="color:#3b82f6"><strong>Remaining:</strong> $<?= number_format($bud['remaining'], 2) ?></span>
                </div>
                <div class="tr-reserve-bar" style="margin-top:.5rem;height:8px">
                    <?php $spentPct = $bud['allocation'] > 0 ? min(($bud['spent'] / $bud['allocation']) * 100, 100) : 0; ?>
                    <div class="tr-reserve-fill" style="width:<?= $spentPct ?>%;background:<?= $spentPct > 90 ? '#ef4444' : ($spentPct > 70 ? '#f59e0b' : '#22c55e') ?>"></div>
                </div>
                <?php if ($bud['status'] === 'proposed' && $isCommander): ?>
                    <form method="POST" style="margin-top:.5rem;display:inline"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="approve_budget"><input type="hidden" name="budget_id" value="<?= $bud['id'] ?>"><button class="tr-btn-sm tr-btn tr-btn-gold"><i class="fas fa-check"></i> Approve</button></form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if (empty($budgets)): ?>
            <div class="tr-card" style="text-align:center;color:#64748b"><i class="fas fa-wallet" style="font-size:2rem;margin-bottom:.5rem"></i><p>No budgets proposed.</p></div>
        <?php endif; ?>

    <!-- ═══ TAB: TAXES ═══ -->
    <?php elseif ($tab === 'taxes'): ?>
        <?php if ($isFlag || $isCommander): ?>
            <div style="margin-bottom:1rem"><button class="tr-btn" onclick="document.getElementById('modalTax').classList.add('open')"><i class="fas fa-percent"></i> Enact Tax</button></div>
        <?php endif; ?>
        <?php foreach ($taxes as $tax): ?>
            <div class="tr-card">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div>
                        <span class="tr-badge" style="background:#f59e0b20;color:#f59e0b;border:1px solid #f59e0b40"><?= strtoupper($tax['tax_type']) ?></span>
                        <strong style="color:#f1f5f9;margin-left:.5rem;font-size:1.1rem"><?= number_format($tax['rate_pct'], 1) ?>%</strong>
                    </div>
                    <span class="tr-badge" style="background:<?= $tax['status']==='active'?'#22c55e':'#64748b' ?>20;color:<?= $tax['status']==='active'?'#22c55e':'#64748b' ?>;border:1px solid <?= $tax['status']==='active'?'#22c55e':'#64748b' ?>40"><?= strtoupper($tax['status']) ?></span>
                </div>
                <div style="color:#94a3b8;font-size:.8rem;margin-top:.5rem">
                    <?php if ($tax['applies_to']): ?><div><strong>Applies to:</strong> <?= htmlspecialchars($tax['applies_to']) ?></div><?php endif; ?>
                    <?php if ($tax['minimum_exempt'] > 0): ?><div><strong>Exempt under:</strong> $<?= number_format($tax['minimum_exempt'], 2) ?></div><?php endif; ?>
                    <div>Enacted by <?= htmlspecialchars($tax['enacter_name'] ?? 'Unknown') ?> on <?= date('M j, Y', strtotime($tax['effective_date'] ?? $tax['created_at'])) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($taxes)): ?>
            <div class="tr-card" style="text-align:center;color:#64748b"><i class="fas fa-percent" style="font-size:2rem;margin-bottom:.5rem"></i><p>No taxes enacted.</p></div>
        <?php endif; ?>

    <!-- ═══ TAB: POLICY ═══ -->
    <?php elseif ($tab === 'policy'): ?>
        <?php if ($isCommander): ?>
            <div style="margin-bottom:1rem"><button class="tr-btn tr-btn-gold" onclick="document.getElementById('modalPolicy').classList.add('open')"><i class="fas fa-gavel"></i> Set Policy</button></div>
        <?php endif; ?>
        <?php
        $polColors = ['monetary'=>'#22c55e','fiscal'=>'#3b82f6','trade'=>'#f59e0b'];
        $polIcons  = ['monetary'=>'coins','fiscal'=>'file-invoice-dollar','trade'=>'ship'];
        foreach ($policies as $pol): ?>
            <div class="tr-card">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div>
                        <i class="fas fa-<?= $polIcons[$pol['policy_type']] ?? 'gavel' ?>" style="color:<?= $polColors[$pol['policy_type']] ?? '#94a3b8' ?>"></i>
                        <strong style="color:#f1f5f9;margin-left:.5rem"><?= htmlspecialchars($pol['policy_name']) ?></strong>
                        <span class="tr-badge" style="background:<?= $polColors[$pol['policy_type']] ?? '#64748b' ?>20;color:<?= $polColors[$pol['policy_type']] ?? '#64748b' ?>;margin-left:.5rem"><?= strtoupper($pol['policy_type']) ?></span>
                    </div>
                    <span class="tr-badge" style="background:<?= $pol['status']==='active'?'#22c55e':'#64748b' ?>20;color:<?= $pol['status']==='active'?'#22c55e':'#64748b' ?>;border:1px solid <?= $pol['status']==='active'?'#22c55e':'#64748b' ?>40"><?= strtoupper($pol['status']) ?></span>
                </div>
                <p style="color:#cbd5e1;font-size:.85rem;margin-top:.5rem"><?= htmlspecialchars($pol['value']) ?></p>
                <div style="color:#64748b;font-size:.75rem;margin-top:.25rem">Set by <?= htmlspecialchars($pol['set_by_name'] ?? 'Unknown') ?> on <?= date('M j, Y', strtotime($pol['created_at'])) ?></div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($policies)): ?>
            <div class="tr-card" style="text-align:center;color:#64748b"><i class="fas fa-gavel" style="font-size:2rem;margin-bottom:.5rem"></i><p>No policies set.</p></div>
        <?php endif; ?>

    <!-- ═══ TAB: MY HOLDINGS ═══ -->
    <?php elseif ($tab === 'holdings'): ?>
        <h3 style="color:#f1f5f9;font-size:1rem;margin-bottom:.75rem"><i class="fas fa-briefcase"></i> Your Bond Holdings</h3>
        <?php foreach ($myHoldings as $h): ?>
            <div class="tr-card">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div><strong style="color:#d4a017"><?= htmlspecialchars($h['bond_code']) ?></strong> <span style="color:#f1f5f9"><?= htmlspecialchars($h['bond_name']) ?></span></div>
                    <?php if ($h['redeemed_at']): ?>
                        <span class="tr-badge" style="background:#8b5cf620;color:#8b5cf6;border:1px solid #8b5cf640">REDEEMED</span>
                    <?php elseif (strtotime($h['matures_at']) <= time()): ?>
                        <span class="tr-badge" style="background:#22c55e20;color:#22c55e;border:1px solid #22c55e40">MATURED</span>
                    <?php else: ?>
                        <span class="tr-badge" style="background:#3b82f620;color:#3b82f6;border:1px solid #3b82f640">HOLDING</span>
                    <?php endif; ?>
                </div>
                <div style="display:flex;gap:1.5rem;margin-top:.5rem;font-size:.8rem;color:#94a3b8">
                    <span><strong>Qty:</strong> <?= (int)$h['quantity'] ?></span>
                    <span><strong>Face:</strong> $<?= number_format($h['face_value'], 2) ?></span>
                    <span><strong>Rate:</strong> <?= $h['interest_rate'] ?>%</span>
                    <span><strong>Matures:</strong> <?= date('M j, Y', strtotime($h['matures_at'])) ?></span>
                    <span><strong>Purchased:</strong> <?= date('M j, Y', strtotime($h['purchased_at'])) ?></span>
                </div>
                <?php if (!$h['redeemed_at'] && strtotime($h['matures_at']) <= time()): ?>
                    <form method="POST" style="margin-top:.5rem"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="redeem_bond"><input type="hidden" name="holding_id" value="<?= $h['id'] ?>">
                        <?php $payout = $h['quantity'] * $h['face_value'] * (1 + $h['interest_rate'] / 100); ?>
                        <button class="tr-btn-sm tr-btn tr-btn-gold"><i class="fas fa-hand-holding-dollar"></i> Redeem ($<?= number_format($payout, 2) ?>)</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if (empty($myHoldings)): ?>
            <div class="tr-card" style="text-align:center;color:#64748b"><i class="fas fa-briefcase" style="font-size:2rem;margin-bottom:.5rem"></i><p>You own no bonds. Visit the Bonds tab to invest.</p></div>
        <?php endif; ?>
    <?php endif; ?>
</div>
</div>

<!-- ═══ Modal: Issue Bond ═══ -->
<div class="tr-modal-bg" id="modalBond">
<div class="tr-modal">
    <h3><i class="fas fa-receipt"></i> Issue Government Bond</h3>
    <form method="POST">
        <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="issue_bond">
        <div class="tr-form-row"><label class="tr-label">Bond Name</label><input type="text" name="bond_name" class="tr-input" required placeholder="e.g., Infrastructure Development Series A"></div>
        <div class="tr-form-row"><label class="tr-label">Bond Type</label><select name="bond_type" class="tr-select"><option value="infrastructure">Infrastructure</option><option value="war">War Bond</option><option value="research">Research Bond</option></select></div>
        <div style="display:flex;gap:.75rem">
            <div class="tr-form-row" style="flex:1"><label class="tr-label">Face Value ($)</label><input type="number" name="face_value" class="tr-input" step="0.01" min="1" required></div>
            <div class="tr-form-row" style="flex:1"><label class="tr-label">Interest Rate (%)</label><input type="number" name="interest_rate" class="tr-input" step="0.01" value="5"></div>
        </div>
        <div style="display:flex;gap:.75rem">
            <div class="tr-form-row" style="flex:1"><label class="tr-label">Maturity (days)</label><input type="number" name="maturity_days" class="tr-input" value="365" min="30"></div>
            <div class="tr-form-row" style="flex:1"><label class="tr-label">Units to Issue</label><input type="number" name="total_issued" class="tr-input" value="100" min="1"></div>
        </div>
        <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem">
            <button type="button" class="tr-btn tr-btn-outline" onclick="this.closest('.tr-modal-bg').classList.remove('open')">Cancel</button>
            <button type="submit" class="tr-btn tr-btn-gold"><i class="fas fa-stamp"></i> Issue Bond</button>
        </div>
    </form>
</div>
</div>

<!-- ═══ Modal: Propose Budget ═══ -->
<div class="tr-modal-bg" id="modalBudget">
<div class="tr-modal">
    <h3><i class="fas fa-wallet"></i> Propose Department Budget</h3>
    <form method="POST">
        <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="propose_budget">
        <div class="tr-form-row"><label class="tr-label">Fiscal Year</label><input type="number" name="fiscal_year" class="tr-input" value="<?= date('Y') ?>" required></div>
        <div class="tr-form-row"><label class="tr-label">Department</label><input type="text" name="budget_dept" class="tr-input" required placeholder="e.g., Defense, Intelligence, Infrastructure"></div>
        <div class="tr-form-row"><label class="tr-label">Allocation ($)</label><input type="number" name="allocation" class="tr-input" step="0.01" min="1" required></div>
        <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem">
            <button type="button" class="tr-btn tr-btn-outline" onclick="this.closest('.tr-modal-bg').classList.remove('open')">Cancel</button>
            <button type="submit" class="tr-btn"><i class="fas fa-paper-plane"></i> Propose</button>
        </div>
    </form>
</div>
</div>

<!-- ═══ Modal: Enact Tax ═══ -->
<div class="tr-modal-bg" id="modalTax">
<div class="tr-modal">
    <h3><i class="fas fa-percent"></i> Enact Tax</h3>
    <form method="POST">
        <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="enact_tax">
        <div class="tr-form-row"><label class="tr-label">Tax Type</label><select name="tax_type" class="tr-select"><option value="transaction">Transaction Tax</option><option value="territory">Territory Tax</option><option value="luxury">Luxury Tax</option><option value="import">Import Tax</option></select></div>
        <div class="tr-form-row"><label class="tr-label">Rate (%)</label><input type="number" name="tax_rate" class="tr-input" step="0.01" min="0.01" required></div>
        <div class="tr-form-row"><label class="tr-label">Applies To</label><input type="text" name="tax_applies" class="tr-input" placeholder="e.g., All credit transactions over 100 GSM"></div>
        <div class="tr-form-row"><label class="tr-label">Minimum Exempt ($)</label><input type="number" name="tax_exempt" class="tr-input" step="0.01" value="0"></div>
        <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem">
            <button type="button" class="tr-btn tr-btn-outline" onclick="this.closest('.tr-modal-bg').classList.remove('open')">Cancel</button>
            <button type="submit" class="tr-btn"><i class="fas fa-stamp"></i> Enact</button>
        </div>
    </form>
</div>
</div>

<!-- ═══ Modal: Set Policy ═══ -->
<div class="tr-modal-bg" id="modalPolicy">
<div class="tr-modal">
    <h3><i class="fas fa-gavel"></i> Set Economic Policy</h3>
    <form method="POST">
        <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="set_policy">
        <div class="tr-form-row"><label class="tr-label">Policy Name</label><input type="text" name="policy_name" class="tr-input" required placeholder="e.g., Quantitative Easing Phase I"></div>
        <div class="tr-form-row"><label class="tr-label">Policy Type</label><select name="policy_type" class="tr-select"><option value="monetary">Monetary</option><option value="fiscal">Fiscal</option><option value="trade">Trade</option></select></div>
        <div class="tr-form-row"><label class="tr-label">Policy Details</label><textarea name="policy_value" class="tr-textarea" required placeholder="Full policy description and parameters..."></textarea></div>
        <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem">
            <button type="button" class="tr-btn tr-btn-outline" onclick="this.closest('.tr-modal-bg').classList.remove('open')">Cancel</button>
            <button type="submit" class="tr-btn tr-btn-gold"><i class="fas fa-gavel"></i> Enact Policy</button>
        </div>
    </form>
</div>
</div>

<script>
document.querySelectorAll('.tr-modal-bg').forEach(bg => {
    bg.addEventListener('click', e => { if (e.target === bg) bg.classList.remove('open'); });
});
</script>
<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
