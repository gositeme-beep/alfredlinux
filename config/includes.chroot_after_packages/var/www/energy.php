<?php
/**
 * ═══════════════════════════════════════════
 *  Department of Energy & Infrastructure — Level 6: Sovereign State
 * ═══════════════════════════════════════════
 */
require_once __DIR__ . '/includes/db-config.inc.php';
$db = getSharedDB();
require_once __DIR__ . '/includes/auth-gate.inc.php';
require_once __DIR__ . '/includes/rank-guard.inc.php';
if (empty($_SESSION['csrf_en'])) $_SESSION['csrf_en'] = bin2hex(random_bytes(32));
requireRank(6);

$isCommander = ($clientId === 33);
$isFlag      = ($userRankTier >= 9) || $isCommander;
$isOfficer   = ($userRankTier >= 6) || $isCommander;
$msg = '';
$msgType = '';

// ── Auto-create tables ──
$db->exec("CREATE TABLE IF NOT EXISTS energy_grid (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resource_type ENUM('cpu','ram','storage','bandwidth','gpu') NOT NULL,
    total_capacity DECIMAL(14,2) NOT NULL,
    current_usage DECIMAL(14,2) DEFAULT 0.00,
    usage_pct DECIMAL(5,2) DEFAULT 0.00,
    unit VARCHAR(20) DEFAULT '%',
    threshold_warning INT DEFAULT 70,
    threshold_critical INT DEFAULT 90,
    measured_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS energy_datacenters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dc_code VARCHAR(20) NOT NULL,
    dc_name VARCHAR(200) NOT NULL,
    location VARCHAR(200) DEFAULT NULL,
    provider VARCHAR(100) DEFAULT 'OVH',
    status ENUM('operational','maintenance','offline','planned') DEFAULT 'operational',
    server_count INT DEFAULT 1,
    ip_address VARCHAR(45) DEFAULT NULL,
    uptime_pct DECIMAL(5,2) DEFAULT 99.99,
    last_audit_at TIMESTAMP NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS energy_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(120) NOT NULL,
    pm2_id INT DEFAULT NULL,
    port INT DEFAULT NULL,
    status ENUM('online','stopped','erroring','restarting') DEFAULT 'online',
    memory_mb INT DEFAULT 0,
    cpu_pct DECIMAL(5,2) DEFAULT 0.00,
    restarts INT DEFAULT 0,
    uptime_seconds BIGINT DEFAULT 0,
    last_heartbeat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    datacenter_id INT DEFAULT NULL,
    notes TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS energy_capacity (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resource_type VARCHAR(40) NOT NULL,
    forecast_date DATE NOT NULL,
    projected_usage DECIMAL(14,2) DEFAULT 0.00,
    projected_capacity DECIMAL(14,2) DEFAULT 0.00,
    recommendation TEXT DEFAULT NULL,
    forecasted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    forecasted_by INT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS energy_budget (
    id INT AUTO_INCREMENT PRIMARY KEY,
    month VARCHAR(7) NOT NULL,
    service_category VARCHAR(120) NOT NULL,
    cost_usd DECIMAL(14,2) DEFAULT 0.00,
    projected_cost DECIMAL(14,2) DEFAULT 0.00,
    variance_pct DECIMAL(5,2) DEFAULT 0.00,
    notes TEXT DEFAULT NULL,
    submitted_by INT DEFAULT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Seed datacenters ──
$dcCount = $db->query("SELECT COUNT(*) FROM energy_datacenters")->fetchColumn();
if ($dcCount == 0) {
    $dcs = [
        ['DC-NA1', 'OVH NA-East Primary', 'Beauharnois, QC, Canada', 'OVH', 'operational', 1, '15.235.50.60'],
        ['DC-EU1', 'OVH EU Reserve', 'Europe', 'OVH', 'planned', 0, NULL]
    ];
    $stmt = $db->prepare("INSERT INTO energy_datacenters (dc_code, dc_name, location, provider, status, server_count, ip_address) VALUES (?,?,?,?,?,?,?)");
    foreach ($dcs as $dc) $stmt->execute($dc);
}

// ── Seed grid resources ──
$grCount = $db->query("SELECT COUNT(*) FROM energy_grid")->fetchColumn();
if ($grCount == 0) {
    $resources = [
        ['cpu', 800, '%'],      // 8 vCPUs, percentage based
        ['ram', 32768, 'MB'],   // 32 GB
        ['storage', 500, 'GB'], // 500 GB
        ['bandwidth', 1000, 'Mbps'],
        ['gpu', 0, 'units']     // No GPU yet
    ];
    $stmt = $db->prepare("INSERT INTO energy_grid (resource_type, total_capacity, unit) VALUES (?,?,?)");
    foreach ($resources as $r) $stmt->execute($r);
}

$csrf = $_SESSION['csrf_en'];

// ── POST Handlers ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, $_POST['csrf'] ?? '')) {
        $msg = 'Invalid security token.'; $msgType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_grid' && $isOfficer) {
            $resId   = (int)($_POST['resource_id'] ?? 0);
            $usage   = (float)($_POST['current_usage'] ?? 0);
            $total   = (float)($_POST['total_capacity'] ?? 0);
            if ($total <= 0) { $msg = 'Total capacity must be positive.'; $msgType = 'error'; }
            else {
                $pct = round(($usage / $total) * 100, 2);
                $db->prepare("UPDATE energy_grid SET current_usage = ?, total_capacity = ?, usage_pct = ?, measured_at = NOW() WHERE id = ?")
                   ->execute([$usage, $total, $pct, $resId]);
                $msg = "Grid resource updated ($pct% usage)."; $msgType = 'success';
            }
        } elseif ($action === 'audit_datacenter' && $isFlag) {
            $dcId    = (int)($_POST['dc_id'] ?? 0);
            $uptime  = (float)($_POST['uptime_pct'] ?? 99.99);
            $servers = (int)($_POST['server_count'] ?? 1);
            $notes   = trim($_POST['audit_notes'] ?? '');
            $db->prepare("UPDATE energy_datacenters SET uptime_pct = ?, server_count = ?, notes = ?, last_audit_at = NOW() WHERE id = ?")
               ->execute([min(100, max(0, $uptime)), max(0, $servers), $notes, $dcId]);
            awardXP($clientId, 'dc_audit', []);
            $msg = "Datacenter audit recorded."; $msgType = 'success';
        } elseif ($action === 'register_service' && $isOfficer) {
            $name  = trim($_POST['svc_name'] ?? '');
            $pm2Id = ($_POST['pm2_id'] !== '' && $_POST['pm2_id'] !== null) ? (int)$_POST['pm2_id'] : null;
            $port  = ($_POST['svc_port'] !== '' && $_POST['svc_port'] !== null) ? (int)$_POST['svc_port'] : null;
            $dcId  = (int)($_POST['dc_id'] ?? 0);
            $notes = trim($_POST['svc_notes'] ?? '');
            if ($name === '') { $msg = 'Service name required.'; $msgType = 'error'; }
            else {
                $db->prepare("INSERT INTO energy_services (service_name, pm2_id, port, datacenter_id, notes) VALUES (?,?,?,?,?)")
                   ->execute([$name, $pm2Id, $port, $dcId ?: null, $notes]);
                $msg = "Service <strong>" . htmlspecialchars($name) . "</strong> registered."; $msgType = 'success';
            }
        } elseif ($action === 'update_service_status' && $isOfficer) {
            $svcId  = (int)($_POST['svc_id'] ?? 0);
            $status = $_POST['svc_status'] ?? 'online';
            $memMb  = (int)($_POST['memory_mb'] ?? 0);
            $cpuPct = (float)($_POST['cpu_pct'] ?? 0);
            $restarts = (int)($_POST['restarts'] ?? 0);
            $uptime   = (int)($_POST['uptime_seconds'] ?? 0);
            $validSt = ['online','stopped','erroring','restarting'];
            if (!in_array($status, $validSt, true)) { $msg = 'Invalid status.'; $msgType = 'error'; }
            else {
                $db->prepare("UPDATE energy_services SET status = ?, memory_mb = ?, cpu_pct = ?, restarts = ?, uptime_seconds = ?, last_heartbeat = NOW() WHERE id = ?")
                   ->execute([$status, max(0, $memMb), max(0, $cpuPct), max(0, $restarts), max(0, $uptime), $svcId]);
                $msg = "Service health updated."; $msgType = 'success';
            }
        } elseif ($action === 'forecast_capacity' && $isFlag) {
            $resType = trim($_POST['res_type'] ?? '');
            $fDate   = $_POST['forecast_date'] ?? '';
            $pUsage  = (float)($_POST['proj_usage'] ?? 0);
            $pCap    = (float)($_POST['proj_capacity'] ?? 0);
            $rec     = trim($_POST['recommendation'] ?? '');
            if ($resType === '' || $fDate === '') { $msg = 'Resource type and forecast date required.'; $msgType = 'error'; }
            else {
                $db->prepare("INSERT INTO energy_capacity (resource_type, forecast_date, projected_usage, projected_capacity, recommendation, forecasted_by) VALUES (?,?,?,?,?,?)")
                   ->execute([$resType, $fDate, $pUsage, $pCap, $rec, $clientId]);
                $msg = "Capacity forecast recorded for " . strtoupper($resType) . "."; $msgType = 'success';
            }
        } elseif ($action === 'submit_budget' && $isOfficer) {
            $month    = $_POST['budget_month'] ?? '';
            $category = trim($_POST['svc_category'] ?? '');
            $cost     = (float)($_POST['cost_usd'] ?? 0);
            $projected = (float)($_POST['projected_cost'] ?? 0);
            $notes    = trim($_POST['budget_notes'] ?? '');
            if ($month === '' || $category === '') { $msg = 'Month and category required.'; $msgType = 'error'; }
            else {
                $variance = ($projected > 0) ? round((($cost - $projected) / $projected) * 100, 2) : 0;
                $db->prepare("INSERT INTO energy_budget (month, service_category, cost_usd, projected_cost, variance_pct, notes, submitted_by) VALUES (?,?,?,?,?,?,?)")
                   ->execute([$month, $category, $cost, $projected, $variance, $notes, $clientId]);
                $msg = "Budget submitted: $" . number_format($cost, 2) . " (" . ($variance >= 0 ? '+' : '') . "$variance%)."; $msgType = 'success';
            }
        } elseif ($action === 'set_policy' && $isCommander) {
            $dcId   = (int)($_POST['dc_id'] ?? 0);
            $status = $_POST['dc_status'] ?? '';
            $validDcSt = ['operational','maintenance','offline','planned'];
            if (!in_array($status, $validDcSt, true)) { $msg = 'Invalid status.'; $msgType = 'error'; }
            else {
                $db->prepare("UPDATE energy_datacenters SET status = ? WHERE id = ?")->execute([$status, $dcId]);
                awardXP($clientId, 'energy_policy_set', []);
                $msg = "Datacenter policy updated to " . strtoupper($status) . "."; $msgType = 'success';
            }
        } else {
            $msg = 'Unauthorized or unknown action.'; $msgType = 'error';
        }
    }
    $_SESSION['csrf_en'] = bin2hex(random_bytes(32));
    $csrf = $_SESSION['csrf_en'];
}

// ── Data Fetch ──
$tab = $_GET['tab'] ?? 'grid';
$grid        = $db->query("SELECT * FROM energy_grid ORDER BY resource_type")->fetchAll(PDO::FETCH_ASSOC);
$datacenters = $db->query("SELECT * FROM energy_datacenters ORDER BY dc_code")->fetchAll(PDO::FETCH_ASSOC);
$services    = $db->query("SELECT es.*, ed.dc_code FROM energy_services es LEFT JOIN energy_datacenters ed ON ed.id = es.datacenter_id ORDER BY es.service_name")->fetchAll(PDO::FETCH_ASSOC);
$forecasts   = $db->query("SELECT ec.*, CONCAT(c.firstname,' ',c.lastname) AS forecaster FROM energy_capacity ec LEFT JOIN tblclients c ON c.id = ec.forecasted_by ORDER BY ec.forecast_date DESC")->fetchAll(PDO::FETCH_ASSOC);
$budgets     = $db->query("SELECT eb.*, CONCAT(c.firstname,' ',c.lastname) AS submitter FROM energy_budget eb LEFT JOIN tblclients c ON c.id = eb.submitted_by ORDER BY eb.month DESC, eb.service_category")->fetchAll(PDO::FETCH_ASSOC);
$onlineSvc   = count(array_filter($services, fn($s) => $s['status'] === 'online'));
$totalCost   = array_sum(array_column($budgets, 'cost_usd'));

// Energy independence score: ratio of self-hosted vs cloud
$selfHosted = count(array_filter($datacenters, fn($d) => in_array($d['provider'], ['OVH','Self']) && $d['status'] === 'operational'));
$totalDC    = max(1, count(array_filter($datacenters, fn($d) => $d['status'] !== 'planned')));
$independenceScore = round(($selfHosted / $totalDC) * 100);

$pageTitle = 'Department of Energy & Infrastructure';
include __DIR__ . '/includes/site-header.inc.php';
?>
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<style>
.en-wrap{max-width:1100px;margin:0 auto;padding:1.5rem}
.en-bg{background:#0a0a14;min-height:100vh;color:#e2e8f0}
.en-card{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:10px;padding:1.25rem;margin-bottom:1rem}
.en-card:hover{border-color:#22c55e;box-shadow:0 0 12px rgba(34,197,94,.12)}
.en-title{font-size:1.6rem;font-weight:700;color:#f1f5f9;margin-bottom:.25rem;display:flex;align-items:center;gap:.5rem}
.en-sub{color:#94a3b8;font-size:.85rem}
.en-badge{display:inline-block;padding:2px 10px;border-radius:999px;font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
.en-tabs{display:flex;gap:.5rem;margin-bottom:1.25rem;flex-wrap:wrap}
.en-tab{padding:.4rem 1rem;border-radius:6px;cursor:pointer;font-size:.8rem;background:#2a2a4a;color:#94a3b8;text-decoration:none;font-weight:600;border:none}
.en-tab.active{background:#22c55e;color:#fff}
.en-stat-bar{display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem}
.en-stat{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:8px;padding:.75rem 1.25rem;flex:1;min-width:110px;text-align:center}
.en-stat .val{font-size:1.5rem;font-weight:700;color:#22c55e}
.en-stat .lbl{font-size:.7rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px}
.en-btn{background:#22c55e;color:#fff;border:none;padding:.5rem 1.25rem;border-radius:6px;cursor:pointer;font-size:.85rem;font-weight:600;transition:.2s}
.en-btn:hover{background:#16a34a}
.en-btn-sm{padding:.3rem .75rem;font-size:.75rem}
.en-btn-outline{background:transparent;border:1px solid #22c55e;color:#22c55e}
.en-btn-outline:hover{background:#22c55e;color:#fff}
.en-btn-blue{background:#3b82f6;color:#fff}.en-btn-blue:hover{background:#2563eb}
.en-btn-gold{background:#d4a017;color:#fff}.en-btn-gold:hover{background:#b8860b}
.en-btn-red{background:#ef4444;color:#fff}.en-btn-red:hover{background:#dc2626}
.en-input,.en-select,.en-textarea{width:100%;background:#0a0a14;border:1px solid #2a2a4a;color:#e2e8f0;padding:.5rem .75rem;border-radius:6px;font-size:.85rem;box-sizing:border-box}
.en-textarea{min-height:80px;resize:vertical}
.en-label{display:block;color:#94a3b8;font-size:.75rem;margin-bottom:.25rem;text-transform:uppercase;letter-spacing:.5px}
.en-msg{padding:.75rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:.85rem}
.en-msg-success{background:rgba(34,197,94,.12);border:1px solid #22c55e;color:#86efac}
.en-msg-error{background:rgba(239,68,68,.12);border:1px solid #ef4444;color:#fca5a5}
.en-modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:900;align-items:center;justify-content:center}
.en-modal-bg.open{display:flex}
.en-modal{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:12px;padding:1.5rem;width:90%;max-width:620px;max-height:80vh;overflow-y:auto}
.en-modal h3{color:#f1f5f9;margin:0 0 1rem}
.en-form-row{margin-bottom:.75rem}
.en-bar{height:8px;background:#2a2a4a;border-radius:4px;overflow:hidden;margin-top:.25rem}
.en-bar-fill{height:100%;border-radius:4px;transition:width .3s}
</style>
<div class="en-bg">
<div class="en-wrap">
    <div class="en-title"><i class="fas fa-bolt"></i> Department of Energy & Infrastructure</div>
    <p class="en-sub" style="margin-bottom:1.25rem">Resource monitoring, datacenter management, service registry, capacity forecasting, and budget — Officer+ rank</p>

    <?php if ($msg): ?><div class="en-msg en-msg-<?= $msgType ?>"><?= $msg ?></div><?php endif; ?>

    <div class="en-stat-bar">
        <div class="en-stat"><div class="val"><?= count($datacenters) ?></div><div class="lbl">Datacenters</div></div>
        <div class="en-stat"><div class="val" style="color:#3b82f6"><?= $onlineSvc ?>/<?= count($services) ?></div><div class="lbl">Services Online</div></div>
        <div class="en-stat"><div class="val" style="color:#d4a017">$<?= number_format($totalCost, 0) ?></div><div class="lbl">Total Cost</div></div>
        <div class="en-stat"><div class="val" style="color:<?= $independenceScore >= 80 ? '#22c55e' : ($independenceScore >= 50 ? '#f59e0b' : '#ef4444') ?>"><?= $independenceScore ?>%</div><div class="lbl">Independence</div></div>
        <div class="en-stat"><div class="val" style="color:#8b5cf6"><?= count($forecasts) ?></div><div class="lbl">Forecasts</div></div>
    </div>

    <div class="en-tabs">
        <a href="?tab=grid" class="en-tab <?= $tab==='grid'?'active':'' ?>"><i class="fas fa-tachometer-alt"></i> Grid</a>
        <a href="?tab=datacenters" class="en-tab <?= $tab==='datacenters'?'active':'' ?>"><i class="fas fa-server"></i> Datacenters</a>
        <a href="?tab=services" class="en-tab <?= $tab==='services'?'active':'' ?>"><i class="fas fa-cogs"></i> Services</a>
        <a href="?tab=forecasts" class="en-tab <?= $tab==='forecasts'?'active':'' ?>"><i class="fas fa-chart-line"></i> Forecasts</a>
        <a href="?tab=budget" class="en-tab <?= $tab==='budget'?'active':'' ?>"><i class="fas fa-file-invoice-dollar"></i> Budget</a>
    </div>

    <!-- ═══ TAB: GRID ═══ -->
    <?php if ($tab === 'grid'): ?>
        <?php
        $resIcons = ['cpu'=>'fa-microchip','ram'=>'fa-memory','storage'=>'fa-hdd','bandwidth'=>'fa-wifi','gpu'=>'fa-tv'];
        $resColors = ['cpu'=>'#3b82f6','ram'=>'#8b5cf6','storage'=>'#f59e0b','bandwidth'=>'#22c55e','gpu'=>'#ef4444'];
        foreach ($grid as $g):
            $pct = round($g['usage_pct'], 1);
            $barColor = $pct >= $g['threshold_critical'] ? '#ef4444' : ($pct >= $g['threshold_warning'] ? '#f59e0b' : '#22c55e');
        ?>
            <div class="en-card">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div>
                        <i class="fas <?= $resIcons[$g['resource_type']] ?? 'fa-bolt' ?>" style="color:<?= $resColors[$g['resource_type']] ?>"></i>
                        <strong style="color:#f1f5f9;margin-left:.5rem"><?= strtoupper($g['resource_type']) ?></strong>
                    </div>
                    <div style="font-size:.9rem;font-weight:700;color:<?= $barColor ?>"><?= $pct ?>%</div>
                </div>
                <div class="en-bar"><div class="en-bar-fill" style="width:<?= min(100, $pct) ?>%;background:<?= $barColor ?>"></div></div>
                <div style="color:#64748b;font-size:.75rem;margin-top:.25rem">
                    Usage: <?= number_format($g['current_usage'], 1) ?> / <?= number_format($g['total_capacity'], 1) ?> <?= htmlspecialchars($g['unit']) ?>
                    &bull; Warning: <?= $g['threshold_warning'] ?>% &bull; Critical: <?= $g['threshold_critical'] ?>%
                    &bull; Last: <?= $g['measured_at'] ? date('M j H:i', strtotime($g['measured_at'])) : 'Never' ?>
                </div>
                <?php if ($isOfficer): ?>
                    <form method="POST" style="display:flex;gap:.5rem;align-items:center;margin-top:.5rem;flex-wrap:wrap">
                        <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="update_grid"><input type="hidden" name="resource_id" value="<?= $g['id'] ?>">
                        <input type="number" name="current_usage" class="en-input" style="width:100px" placeholder="Usage" step="0.1" value="<?= $g['current_usage'] ?>">
                        <input type="number" name="total_capacity" class="en-input" style="width:100px" placeholder="Capacity" step="0.1" value="<?= $g['total_capacity'] ?>">
                        <button class="en-btn en-btn-sm"><i class="fas fa-sync"></i> Update</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

    <!-- ═══ TAB: DATACENTERS ═══ -->
    <?php elseif ($tab === 'datacenters'): ?>
        <?php
        $dcColors = ['operational'=>'#22c55e','maintenance'=>'#f59e0b','offline'=>'#ef4444','planned'=>'#64748b'];
        foreach ($datacenters as $dc): ?>
            <div class="en-card" style="border-left:3px solid <?= $dcColors[$dc['status']] ?>">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div>
                        <i class="fas fa-server" style="color:<?= $dcColors[$dc['status']] ?>"></i>
                        <strong style="color:#22c55e;margin-left:.25rem"><?= htmlspecialchars($dc['dc_code']) ?></strong>
                        <strong style="color:#f1f5f9;margin-left:.5rem"><?= htmlspecialchars($dc['dc_name']) ?></strong>
                    </div>
                    <span class="en-badge" style="background:<?= $dcColors[$dc['status']] ?>20;color:<?= $dcColors[$dc['status']] ?>;border:1px solid <?= $dcColors[$dc['status']] ?>40"><?= strtoupper($dc['status']) ?></span>
                </div>
                <div style="color:#94a3b8;font-size:.85rem;margin-top:.25rem">
                    <i class="fas fa-map-marker-alt" style="color:#64748b"></i> <?= htmlspecialchars($dc['location'] ?? 'Unknown') ?>
                    &bull; Provider: <?= htmlspecialchars($dc['provider']) ?>
                    &bull; Servers: <?= $dc['server_count'] ?>
                    <?php if ($dc['ip_address']): ?>&bull; IP: <span style="color:#f59e0b;font-family:monospace"><?= htmlspecialchars($dc['ip_address']) ?></span><?php endif; ?>
                </div>
                <div style="color:#64748b;font-size:.75rem;margin-top:.25rem">
                    Uptime: <span style="color:<?= $dc['uptime_pct'] >= 99.9 ? '#22c55e' : '#f59e0b' ?>;font-weight:600"><?= $dc['uptime_pct'] ?>%</span>
                    &bull; Last audit: <?= $dc['last_audit_at'] ? date('M j, Y', strtotime($dc['last_audit_at'])) : 'Never' ?>
                    <?php if ($dc['notes']): ?>&bull; <?= htmlspecialchars($dc['notes']) ?><?php endif; ?>
                </div>
                <div style="margin-top:.5rem;display:flex;gap:.5rem;flex-wrap:wrap">
                    <?php if ($isFlag): ?>
                        <button class="en-btn-sm en-btn en-btn-blue" onclick="openAudit(<?= $dc['id'] ?>,'<?= htmlspecialchars($dc['dc_code'], ENT_QUOTES) ?>',<?= $dc['uptime_pct'] ?>,<?= $dc['server_count'] ?>)"><i class="fas fa-clipboard-check"></i> Audit</button>
                    <?php endif; ?>
                    <?php if ($isCommander): ?>
                        <form method="POST" style="display:flex;gap:.25rem;align-items:center">
                            <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="set_policy"><input type="hidden" name="dc_id" value="<?= $dc['id'] ?>">
                            <select name="dc_status" class="en-select" style="width:130px;padding:.25rem .5rem;font-size:.75rem">
                                <?php foreach (['operational','maintenance','offline','planned'] as $st): ?>
                                    <option value="<?= $st ?>" <?= $dc['status'] === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="en-btn-sm en-btn en-btn-gold"><i class="fas fa-shield-alt"></i> Set</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

    <!-- ═══ TAB: SERVICES ═══ -->
    <?php elseif ($tab === 'services'): ?>
        <?php if ($isOfficer): ?>
            <div style="margin-bottom:1rem"><button class="en-btn" onclick="document.getElementById('modalService').classList.add('open')"><i class="fas fa-plus"></i> Register Service</button></div>
        <?php endif; ?>
        <?php
        $svcColors = ['online'=>'#22c55e','stopped'=>'#64748b','erroring'=>'#ef4444','restarting'=>'#f59e0b'];
        foreach ($services as $s):
            $uptimeHrs = round($s['uptime_seconds'] / 3600, 1);
        ?>
            <div class="en-card" style="border-left:3px solid <?= $svcColors[$s['status']] ?>">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div>
                        <i class="fas fa-<?= $s['status'] === 'online' ? 'circle' : ($s['status'] === 'erroring' ? 'exclamation-circle' : 'pause-circle') ?>" style="color:<?= $svcColors[$s['status']] ?>"></i>
                        <strong style="color:#f1f5f9;margin-left:.25rem"><?= htmlspecialchars($s['service_name']) ?></strong>
                        <?php if ($s['pm2_id'] !== null): ?><span style="color:#64748b;font-size:.8rem;margin-left:.5rem">PM2:<?= $s['pm2_id'] ?></span><?php endif; ?>
                        <?php if ($s['port']): ?><span style="color:#f59e0b;font-size:.8rem;margin-left:.25rem">:<?= $s['port'] ?></span><?php endif; ?>
                    </div>
                    <span class="en-badge" style="background:<?= $svcColors[$s['status']] ?>20;color:<?= $svcColors[$s['status']] ?>;border:1px solid <?= $svcColors[$s['status']] ?>40"><?= strtoupper($s['status']) ?></span>
                </div>
                <div style="color:#64748b;font-size:.75rem;margin-top:.25rem">
                    Mem: <span style="color:#8b5cf6"><?= $s['memory_mb'] ?> MB</span>
                    &bull; CPU: <span style="color:#3b82f6"><?= $s['cpu_pct'] ?>%</span>
                    &bull; Restarts: <span style="color:<?= $s['restarts'] > 5 ? '#ef4444' : '#64748b' ?>"><?= $s['restarts'] ?></span>
                    &bull; Uptime: <?= $uptimeHrs ?>h
                    <?php if ($s['dc_code']): ?>&bull; DC: <?= htmlspecialchars($s['dc_code']) ?><?php endif; ?>
                    &bull; Heartbeat: <?= date('M j H:i', strtotime($s['last_heartbeat'])) ?>
                </div>
                <?php if ($isOfficer): ?>
                    <button class="en-btn-sm en-btn en-btn-blue" style="margin-top:.5rem" onclick="openSvcUpdate(<?= $s['id'] ?>,'<?= htmlspecialchars($s['service_name'], ENT_QUOTES) ?>','<?= $s['status'] ?>',<?= $s['memory_mb'] ?>,<?= $s['cpu_pct'] ?>,<?= $s['restarts'] ?>,<?= $s['uptime_seconds'] ?>)"><i class="fas fa-sync"></i> Update</button>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if (empty($services)): ?><div class="en-card" style="text-align:center;color:#64748b"><p>No services registered.</p></div><?php endif; ?>

    <!-- ═══ TAB: FORECASTS ═══ -->
    <?php elseif ($tab === 'forecasts'): ?>
        <?php if ($isFlag): ?>
            <div style="margin-bottom:1rem"><button class="en-btn en-btn-gold" onclick="document.getElementById('modalForecast').classList.add('open')"><i class="fas fa-chart-line"></i> New Forecast</button></div>
        <?php endif; ?>
        <?php foreach ($forecasts as $f): ?>
            <div class="en-card">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div>
                        <i class="fas fa-chart-line" style="color:#d4a017"></i>
                        <strong style="color:#f1f5f9;margin-left:.25rem"><?= strtoupper(htmlspecialchars($f['resource_type'])) ?></strong>
                        <span style="color:#64748b;font-size:.85rem;margin-left:.5rem">Target: <?= htmlspecialchars($f['forecast_date']) ?></span>
                    </div>
                </div>
                <div style="color:#94a3b8;font-size:.85rem;margin-top:.25rem">
                    Projected Usage: <span style="color:#f59e0b;font-weight:600"><?= number_format($f['projected_usage'], 1) ?></span> /
                    Capacity: <span style="color:#22c55e;font-weight:600"><?= number_format($f['projected_capacity'], 1) ?></span>
                    <?php if ($f['projected_capacity'] > 0): ?>
                        <?php $pct = round(($f['projected_usage'] / $f['projected_capacity']) * 100, 1); ?>
                        — <span style="color:<?= $pct >= 90 ? '#ef4444' : ($pct >= 70 ? '#f59e0b' : '#22c55e') ?>;font-weight:700"><?= $pct ?>%</span>
                    <?php endif; ?>
                </div>
                <?php if ($f['recommendation']): ?><div style="color:#86efac;font-size:.8rem;margin-top:.25rem"><strong>Recommendation:</strong> <?= htmlspecialchars($f['recommendation']) ?></div><?php endif; ?>
                <div style="color:#64748b;font-size:.75rem;margin-top:.25rem">By: <?= htmlspecialchars($f['forecaster'] ?? 'Unknown') ?> &bull; <?= date('M j, Y', strtotime($f['forecasted_at'])) ?></div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($forecasts)): ?><div class="en-card" style="text-align:center;color:#64748b"><p>No forecasts filed.</p></div><?php endif; ?>

    <!-- ═══ TAB: BUDGET ═══ -->
    <?php elseif ($tab === 'budget'): ?>
        <?php if ($isOfficer): ?>
            <div style="margin-bottom:1rem"><button class="en-btn en-btn-gold" onclick="document.getElementById('modalBudget').classList.add('open')"><i class="fas fa-file-invoice-dollar"></i> Submit Budget</button></div>
        <?php endif; ?>
        <?php foreach ($budgets as $b): ?>
            <div class="en-card" style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
                <div style="background:<?= $b['variance_pct'] > 10 ? '#ef444420' : ($b['variance_pct'] > 0 ? '#f59e0b20' : '#22c55e20') ?>;color:<?= $b['variance_pct'] > 10 ? '#ef4444' : ($b['variance_pct'] > 0 ? '#f59e0b' : '#22c55e') ?>;padding:.5rem .75rem;border-radius:8px;font-size:1rem;font-weight:700">$<?= number_format($b['cost_usd'], 0) ?></div>
                <div style="flex:1">
                    <strong style="color:#f1f5f9"><?= htmlspecialchars($b['service_category']) ?></strong>
                    <span style="color:#64748b;font-size:.85rem;margin-left:.5rem"><?= htmlspecialchars($b['month']) ?></span>
                    <span class="en-badge" style="margin-left:.5rem;background:<?= $b['variance_pct'] > 10 ? '#ef444420' : '#22c55e20' ?>;color:<?= $b['variance_pct'] > 10 ? '#ef4444' : '#22c55e' ?>"><?= ($b['variance_pct'] >= 0 ? '+' : '') . $b['variance_pct'] ?>%</span>
                    <div style="color:#64748b;font-size:.75rem">Projected: $<?= number_format($b['projected_cost'], 2) ?> &bull; By: <?= htmlspecialchars($b['submitter'] ?? 'Unknown') ?><?= $b['notes'] ? ' &bull; ' . htmlspecialchars($b['notes']) : '' ?></div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($budgets)): ?><div class="en-card" style="text-align:center;color:#64748b"><p>No budget entries.</p></div><?php endif; ?>
    <?php endif; ?>
</div>
</div>

<!-- Modals -->
<div class="en-modal-bg" id="modalService"><div class="en-modal"><h3><i class="fas fa-cogs"></i> Register Service</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="register_service">
<div class="en-form-row"><label class="en-label">Service Name</label><input type="text" name="svc_name" class="en-input" required></div>
<div style="display:flex;gap:.75rem"><div class="en-form-row" style="flex:1"><label class="en-label">PM2 ID</label><input type="number" name="pm2_id" class="en-input" min="0" placeholder="Optional"></div><div class="en-form-row" style="flex:1"><label class="en-label">Port</label><input type="number" name="svc_port" class="en-input" min="1" placeholder="Optional"></div></div>
<div class="en-form-row"><label class="en-label">Datacenter</label><select name="dc_id" class="en-select"><option value="">None</option><?php foreach ($datacenters as $dc): ?><option value="<?= $dc['id'] ?>"><?= htmlspecialchars($dc['dc_code'] . ' — ' . $dc['dc_name']) ?></option><?php endforeach; ?></select></div>
<div class="en-form-row"><label class="en-label">Notes</label><textarea name="svc_notes" class="en-textarea" style="min-height:60px"></textarea></div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="en-btn en-btn-outline" onclick="this.closest('.en-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="en-btn"><i class="fas fa-plus"></i> Register</button></div></form></div></div>

<div class="en-modal-bg" id="modalAudit"><div class="en-modal"><h3><i class="fas fa-clipboard-check"></i> Audit Datacenter</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="audit_datacenter"><input type="hidden" name="dc_id" id="auditDcId" value="">
<div style="color:#94a3b8;margin-bottom:1rem">Auditing: <strong id="auditDcCode" style="color:#22c55e"></strong></div>
<div style="display:flex;gap:.75rem"><div class="en-form-row" style="flex:1"><label class="en-label">Uptime %</label><input type="number" name="uptime_pct" id="auditUptime" class="en-input" step="0.01" min="0" max="100"></div><div class="en-form-row" style="flex:1"><label class="en-label">Server Count</label><input type="number" name="server_count" id="auditServers" class="en-input" min="0"></div></div>
<div class="en-form-row"><label class="en-label">Audit Notes</label><textarea name="audit_notes" class="en-textarea"></textarea></div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="en-btn en-btn-outline" onclick="this.closest('.en-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="en-btn en-btn-blue"><i class="fas fa-clipboard-check"></i> Submit Audit</button></div></form></div></div>

<div class="en-modal-bg" id="modalSvcUpdate"><div class="en-modal"><h3><i class="fas fa-sync"></i> Update Service Health</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="update_service_status"><input type="hidden" name="svc_id" id="svcUpdId" value="">
<div style="color:#94a3b8;margin-bottom:1rem">Service: <strong id="svcUpdName" style="color:#22c55e"></strong></div>
<div style="display:flex;gap:.75rem;flex-wrap:wrap"><div class="en-form-row" style="flex:1"><label class="en-label">Status</label><select name="svc_status" id="svcUpdStatus" class="en-select"><option value="online">Online</option><option value="stopped">Stopped</option><option value="erroring">Erroring</option><option value="restarting">Restarting</option></select></div><div class="en-form-row" style="flex:1"><label class="en-label">Memory MB</label><input type="number" name="memory_mb" id="svcUpdMem" class="en-input" min="0"></div></div>
<div style="display:flex;gap:.75rem;flex-wrap:wrap"><div class="en-form-row" style="flex:1"><label class="en-label">CPU %</label><input type="number" name="cpu_pct" id="svcUpdCpu" class="en-input" step="0.1" min="0"></div><div class="en-form-row" style="flex:1"><label class="en-label">Restarts</label><input type="number" name="restarts" id="svcUpdRst" class="en-input" min="0"></div><div class="en-form-row" style="flex:1"><label class="en-label">Uptime (sec)</label><input type="number" name="uptime_seconds" id="svcUpdUp" class="en-input" min="0"></div></div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="en-btn en-btn-outline" onclick="this.closest('.en-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="en-btn en-btn-blue"><i class="fas fa-sync"></i> Update</button></div></form></div></div>

<div class="en-modal-bg" id="modalForecast"><div class="en-modal"><h3><i class="fas fa-chart-line"></i> Capacity Forecast</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="forecast_capacity">
<div style="display:flex;gap:.75rem"><div class="en-form-row" style="flex:1"><label class="en-label">Resource Type</label><select name="res_type" class="en-select"><option value="cpu">CPU</option><option value="ram">RAM</option><option value="storage">Storage</option><option value="bandwidth">Bandwidth</option><option value="gpu">GPU</option></select></div><div class="en-form-row" style="flex:1"><label class="en-label">Forecast Date</label><input type="date" name="forecast_date" class="en-input" required></div></div>
<div style="display:flex;gap:.75rem"><div class="en-form-row" style="flex:1"><label class="en-label">Projected Usage</label><input type="number" name="proj_usage" class="en-input" step="0.1" min="0" required></div><div class="en-form-row" style="flex:1"><label class="en-label">Projected Capacity</label><input type="number" name="proj_capacity" class="en-input" step="0.1" min="0" required></div></div>
<div class="en-form-row"><label class="en-label">Recommendation</label><textarea name="recommendation" class="en-textarea"></textarea></div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="en-btn en-btn-outline" onclick="this.closest('.en-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="en-btn en-btn-gold"><i class="fas fa-chart-line"></i> Submit</button></div></form></div></div>

<div class="en-modal-bg" id="modalBudget"><div class="en-modal"><h3><i class="fas fa-file-invoice-dollar"></i> Submit Monthly Budget</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="submit_budget">
<div style="display:flex;gap:.75rem"><div class="en-form-row" style="flex:1"><label class="en-label">Month (YYYY-MM)</label><input type="month" name="budget_month" class="en-input" required></div><div class="en-form-row" style="flex:1"><label class="en-label">Service Category</label><input type="text" name="svc_category" class="en-input" placeholder="e.g. Hosting, CDN, API..." required></div></div>
<div style="display:flex;gap:.75rem"><div class="en-form-row" style="flex:1"><label class="en-label">Actual Cost ($)</label><input type="number" name="cost_usd" class="en-input" step="0.01" min="0" required></div><div class="en-form-row" style="flex:1"><label class="en-label">Projected Cost ($)</label><input type="number" name="projected_cost" class="en-input" step="0.01" min="0" required></div></div>
<div class="en-form-row"><label class="en-label">Notes</label><textarea name="budget_notes" class="en-textarea" style="min-height:60px"></textarea></div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="en-btn en-btn-outline" onclick="this.closest('.en-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="en-btn en-btn-gold"><i class="fas fa-file-invoice-dollar"></i> Submit</button></div></form></div></div>

<script>
function openAudit(id,code,up,srv){document.getElementById('auditDcId').value=id;document.getElementById('auditDcCode').textContent=code;document.getElementById('auditUptime').value=up;document.getElementById('auditServers').value=srv;document.getElementById('modalAudit').classList.add('open')}
function openSvcUpdate(id,name,st,mem,cpu,rst,up){document.getElementById('svcUpdId').value=id;document.getElementById('svcUpdName').textContent=name;document.getElementById('svcUpdStatus').value=st;document.getElementById('svcUpdMem').value=mem;document.getElementById('svcUpdCpu').value=cpu;document.getElementById('svcUpdRst').value=rst;document.getElementById('svcUpdUp').value=up;document.getElementById('modalSvcUpdate').classList.add('open')}
document.querySelectorAll('.en-modal-bg').forEach(bg=>{bg.addEventListener('click',e=>{if(e.target===bg)bg.classList.remove('open')})});
</script>
<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
