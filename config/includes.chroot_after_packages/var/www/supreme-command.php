<?php
/**
 * Supreme Command Dashboard — GoSiteMe Military Ecosystem
 * The master overview of the entire military. Flag officers+ only.
 */
require_once __DIR__ . '/includes/db-config.inc.php';
$db = getSharedDB();
require_once __DIR__ . '/includes/auth-gate.inc.php';
require_once __DIR__ . '/includes/rank-guard.inc.php';

requireRank(9);

if (empty($_SESSION['csrf_supreme'])) $_SESSION['csrf_supreme'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf_supreme'];
$isCommander = ($clientId === 33);
$msg = '';
$msgType = '';

// --- Ensure DB tables ---
$db->exec("CREATE TABLE IF NOT EXISTS supreme_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    metric_key VARCHAR(100) NOT NULL,
    metric_value DECIMAL(20,4) NOT NULL DEFAULT 0,
    metric_type ENUM('gauge','counter','rate') NOT NULL DEFAULT 'gauge',
    category ENUM('personnel','combat','economy','territory','morale','cyber','intel','logistics') NOT NULL,
    recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cat_key (category, metric_key),
    INDEX idx_recorded (recorded_at)
)");

$db->exec("CREATE TABLE IF NOT EXISTS supreme_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alert_type ENUM('info','warning','danger','critical','victory') NOT NULL DEFAULT 'info',
    title VARCHAR(200) NOT NULL,
    message TEXT,
    source_system VARCHAR(100),
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    is_actionable TINYINT(1) NOT NULL DEFAULT 0,
    action_url VARCHAR(500),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_read (is_read, created_at)
)");

$db->exec("CREATE TABLE IF NOT EXISTS supreme_directives (
    id INT AUTO_INCREMENT PRIMARY KEY,
    directive_code VARCHAR(50) NOT NULL UNIQUE,
    title VARCHAR(200) NOT NULL,
    content TEXT,
    status ENUM('draft','active','suspended','revoked','archived') NOT NULL DEFAULT 'draft',
    priority INT NOT NULL DEFAULT 5,
    issued_by INT NOT NULL,
    effective_date DATE,
    expiry_date DATE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status)
)");

// --- POST handlers ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, $_POST['csrf'] ?? '')) {
        $msg = 'CSRF validation failed.'; $msgType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'issue_directive' && $isCommander) {
            $code  = trim($_POST['d_code'] ?? '');
            $title = trim($_POST['d_title'] ?? '');
            $body  = trim($_POST['d_content'] ?? '');
            $pri   = max(1, min(10, (int)($_POST['d_priority'] ?? 5)));
            $eff   = $_POST['d_effective'] ?? date('Y-m-d');
            $exp   = $_POST['d_expiry'] ?? '';
            if ($code === '' || $title === '') {
                $msg = 'Directive code and title required.'; $msgType = 'error';
            } else {
                $dup = $db->prepare("SELECT id FROM supreme_directives WHERE directive_code = ?");
                $dup->execute([$code]);
                if ($dup->fetch()) {
                    $msg = 'Directive code already exists.'; $msgType = 'error';
                } else {
                    $st = $db->prepare("INSERT INTO supreme_directives (directive_code,title,content,status,priority,issued_by,effective_date,expiry_date) VALUES (?,?,?,'active',?,?,?,NULLIF(?,''))");
                    $st->execute([$code, $title, $body, $pri, $clientId, $eff, $exp]);
                    $msg = "Directive {$code} issued."; $msgType = 'success';
                }
            }
        } elseif ($action === 'update_directive' && $isCommander) {
            $did    = (int)($_POST['dir_id'] ?? 0);
            $status = $_POST['dir_status'] ?? '';
            $valid  = ['active','suspended','revoked','archived'];
            if ($did < 1 || !in_array($status, $valid, true)) {
                $msg = 'Invalid directive or status.'; $msgType = 'error';
            } else {
                $st = $db->prepare("UPDATE supreme_directives SET status = ? WHERE id = ?");
                $st->execute([$status, $did]);
                $msg = 'Directive updated.'; $msgType = 'success';
            }
        } elseif ($action === 'set_defcon' && $isCommander) {
            $level = max(1, min(5, (int)($_POST['defcon_level'] ?? 5)));
            $reason = trim($_POST['defcon_reason'] ?? '');
            try {
                $db->exec("CREATE TABLE IF NOT EXISTS defcon_status (id INT AUTO_INCREMENT PRIMARY KEY, defcon_level INT NOT NULL, set_by INT, reason TEXT, set_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
                $st = $db->prepare("INSERT INTO defcon_status (defcon_level, set_by, reason) VALUES (?,?,?)");
                $st->execute([$level, $clientId, $reason]);
                // Update world_state if it exists
                $db->prepare("UPDATE world_state SET defcon_level = ? WHERE id = 1")->execute([$level]);
                $msg = "DEFCON set to {$level}."; $msgType = 'success';
            } catch (PDOException $e) {
                $msg = 'Failed to set DEFCON.'; $msgType = 'error';
            }
        } elseif ($action === 'dismiss_alert') {
            $aid = (int)($_POST['alert_id'] ?? 0);
            if ($aid > 0) {
                $db->prepare("UPDATE supreme_alerts SET is_read = 1 WHERE id = ?")->execute([$aid]);
                $msg = 'Alert dismissed.'; $msgType = 'success';
            }
        }
    }
}

// --- Data Queries ---
$commanderName = htmlspecialchars($clientName);

// DEFCON
$defcon = 5;
try {
    $dc = $db->query("SELECT defcon_level FROM defcon_status ORDER BY set_at DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($dc) $defcon = (int)$dc['defcon_level'];
} catch (PDOException $e) { /* table may not exist yet */ }

$defconColors = [1 => '#dc2626', 2 => '#f97316', 3 => '#eab308', 4 => '#3b82f6', 5 => '#22c55e'];
$defconLabels = [1 => 'MAXIMUM READINESS', 2 => 'HIGH ALERT', 3 => 'ELEVATED', 4 => 'GUARDED', 5 => 'NORMAL'];

// World state
$worldState = null;
try { $worldState = $db->query("SELECT * FROM world_state WHERE id = 1")->fetch(PDO::FETCH_ASSOC); } catch (PDOException $e) {}

// Helper: safe count query
function scq(PDO $db, string $sql): int {
    try { return (int)$db->query($sql)->fetchColumn(); } catch (PDOException $e) { return 0; }
}
function scv(PDO $db, string $sql): float {
    try { $v = $db->query($sql)->fetchColumn(); return $v !== false ? round((float)$v, 2) : 0; } catch (PDOException $e) { return 0; }
}

// Personnel
$totalPersonnel  = scq($db, "SELECT COUNT(*) FROM user_ranks");
$totalUnits      = scq($db, "SELECT COUNT(*) FROM military_units WHERE is_active = 1");
$avgXp           = scv($db, "SELECT AVG(total_xp) FROM alfred_user_xp_summary");

// Combat
$activeMissions  = scq($db, "SELECT COUNT(*) FROM missions WHERE status = 'active'");
$activeCampaigns = scq($db, "SELECT COUNT(*) FROM strategic_campaigns WHERE status = 'active'");
$controlledZones = scq($db, "SELECT COUNT(*) FROM territory_control");

// Economy
$totalTreasury   = scv($db, "SELECT SUM(balance) FROM military_wallets");
$totalSupply     = scq($db, "SELECT COUNT(*) FROM supply_requisitions WHERE status = 'pending'");

// Territory
$totalZones      = scq($db, "SELECT COUNT(*) FROM territory_zones WHERE is_active = 1");

// Morale
$avgMorale       = scv($db, "SELECT AVG(morale_score) FROM morale_tracker");

// Cyber
$cyberSolved     = scq($db, "SELECT COUNT(*) FROM cyber_scoreboard");
$cyberOps        = scq($db, "SELECT COUNT(*) FROM cyber_operations WHERE status = 'active'");

// Intel
$intelReports    = scq($db, "SELECT COUNT(*) FROM intel_reports");
$activeThreats   = scq($db, "SELECT COUNT(*) FROM intel_reports WHERE threat_level IN ('high','critical') AND status = 'active'");

// Logistics
$activeConvoys   = scq($db, "SELECT COUNT(*) FROM supply_convoys WHERE status = 'in_transit'");
$fleetReady      = scq($db, "SELECT COUNT(*) FROM military_units WHERE unit_type = 'fleet' AND status = 'active'");

// Court cases
$openCases       = scq($db, "SELECT COUNT(*) FROM military_court_cases WHERE status IN ('filed','investigating','tribunal')");

// Alliances
$activeAlliances = scq($db, "SELECT COUNT(*) FROM alliances WHERE status = 'active'");

// Alerts
$alerts = [];
try {
    $alerts = $db->query("SELECT * FROM supreme_alerts ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}
$unreadAlerts = 0;
foreach ($alerts as $a) { if (!$a['is_read']) $unreadAlerts++; }

// Directives
$directives = [];
try {
    $directives = $db->query("SELECT sd.*, CONCAT(c.firstname,' ',c.lastname) AS issuer_name FROM supreme_directives sd LEFT JOIN tblclients c ON c.id = sd.issued_by ORDER BY sd.priority ASC, sd.created_at DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    try { $directives = $db->query("SELECT * FROM supreme_directives ORDER BY priority ASC, created_at DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC); } catch (PDOException $e) {}
}

$noGlobalMain = true;
$pageTitle = 'Supreme Command — GoSiteMe Military';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo $pageTitle; ?></title>
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<style>
:root{--bg:#0f172a;--card:#1e293b;--border:#334155;--text:#e2e8f0;--dim:#94a3b8;--accent:#3b82f6;--gold:#e2b340;--red:#dc2626;--green:#22c55e;--orange:#f97316;--yellow:#eab308;--purple:#a855f7}
*{box-sizing:border-box;margin:0;padding:0}
body{background:var(--bg);color:var(--text);font-family:'Segoe UI',system-ui,sans-serif;line-height:1.6}
.sc-wrap{max-width:1400px;margin:0 auto;padding:20px}
.sc-header{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;padding:24px;background:linear-gradient(135deg,#1a1a2e,#16213e);border:2px solid var(--gold);border-radius:12px;margin-bottom:24px;position:relative;overflow:hidden}
.sc-header::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--gold),transparent,var(--gold))}
.sc-title{display:flex;align-items:center;gap:12px}
.sc-title i{color:var(--gold);font-size:2rem}
.sc-title h1{font-size:1.6rem;color:var(--gold);text-transform:uppercase;letter-spacing:2px}
.sc-title h1 span{color:var(--text);font-size:.9rem;display:block;letter-spacing:0;text-transform:none;font-weight:400}
.defcon-badge{display:inline-flex;align-items:center;gap:8px;padding:10px 20px;border-radius:8px;font-weight:700;font-size:1.1rem;text-transform:uppercase;letter-spacing:1px;border:2px solid;animation:pulse-glow 2s infinite}
@keyframes pulse-glow{0%,100%{box-shadow:0 0 8px rgba(255,255,255,.1)}50%{box-shadow:0 0 20px rgba(255,255,255,.25)}}
.world-bar{display:flex;gap:16px;flex-wrap:wrap;margin-bottom:24px}
.world-chip{background:var(--card);border:1px solid var(--border);border-radius:8px;padding:8px 14px;font-size:.85rem;display:flex;align-items:center;gap:6px}
.world-chip i{color:var(--accent)}
.msg{padding:12px 18px;border-radius:8px;margin-bottom:18px;font-weight:500;border-left:4px solid}
.msg.success{background:rgba(34,197,94,.12);border-color:var(--green);color:#86efac}
.msg.error{background:rgba(220,38,38,.12);border-color:var(--red);color:#fca5a5}
.metrics-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;margin-bottom:28px}
.m-card{background:var(--card);border:1px solid var(--border);border-radius:10px;padding:18px;position:relative;transition:border-color .2s}
.m-card:hover{border-color:var(--accent)}
.m-card .m-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px}
.m-card .m-head h3{font-size:.9rem;text-transform:uppercase;letter-spacing:1px;color:var(--dim)}
.m-card .m-head i{font-size:1.3rem}
.m-card .m-body{display:flex;flex-direction:column;gap:8px}
.m-stat{display:flex;justify-content:space-between;align-items:baseline}
.m-stat .label{color:var(--dim);font-size:.85rem}
.m-stat .value{font-size:1.4rem;font-weight:700;color:var(--text)}
.m-stat .value.gold{color:var(--gold)}
.m-bar{height:6px;background:var(--border);border-radius:3px;overflow:hidden;margin-top:4px}
.m-bar span{display:block;height:100%;border-radius:3px;transition:width .5s}
.section-title{font-size:1.15rem;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;margin-bottom:16px;padding-bottom:8px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:8px}
.section-title i{color:var(--gold)}
.two-col{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:28px}
@media(max-width:900px){.two-col{grid-template-columns:1fr}}
.alert-feed{display:flex;flex-direction:column;gap:10px}
.alert-item{display:flex;align-items:flex-start;gap:12px;padding:12px 16px;background:var(--card);border-radius:8px;border-left:4px solid var(--border)}
.alert-item.info{border-color:var(--accent)}
.alert-item.warning{border-color:var(--orange)}
.alert-item.danger{border-color:var(--red)}
.alert-item.critical{border-color:#dc2626;background:rgba(220,38,38,.08)}
.alert-item.victory{border-color:var(--gold);background:rgba(226,179,64,.06)}
.alert-item.read{opacity:.55}
.alert-icon{font-size:1.1rem;margin-top:2px;flex-shrink:0}
.alert-body{flex:1;min-width:0}
.alert-body h4{font-size:.9rem;margin-bottom:2px}
.alert-body p{font-size:.8rem;color:var(--dim)}
.alert-body .alert-meta{font-size:.75rem;color:var(--dim);margin-top:4px}
.alert-actions{display:flex;gap:6px;flex-shrink:0}
.btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:6px;border:1px solid var(--border);background:var(--card);color:var(--text);cursor:pointer;font-size:.82rem;text-decoration:none;transition:all .2s}
.btn:hover{border-color:var(--accent);color:#fff}
.btn-sm{padding:4px 10px;font-size:.78rem}
.btn-gold{background:var(--gold);color:#000;border-color:var(--gold);font-weight:600}
.btn-gold:hover{background:#d4a630;color:#000}
.btn-red{border-color:var(--red);color:#fca5a5}
.btn-red:hover{background:var(--red);color:#fff}
.btn-green{border-color:var(--green);color:#86efac}
.btn-green:hover{background:var(--green);color:#000}
.btn-blue{border-color:var(--accent);color:#93c5fd}
.btn-blue:hover{background:var(--accent);color:#fff}
.dir-table{width:100%;border-collapse:collapse;font-size:.85rem}
.dir-table th{text-align:left;padding:8px 10px;border-bottom:2px solid var(--border);color:var(--dim);font-weight:600;font-size:.78rem;text-transform:uppercase;letter-spacing:.5px}
.dir-table td{padding:8px 10px;border-bottom:1px solid var(--border);vertical-align:top}
.dir-status{padding:3px 8px;border-radius:4px;font-size:.75rem;font-weight:600;text-transform:uppercase}
.dir-status.active{background:rgba(34,197,94,.15);color:#86efac}
.dir-status.draft{background:rgba(148,163,184,.15);color:var(--dim)}
.dir-status.suspended{background:rgba(249,115,22,.15);color:#fdba74}
.dir-status.revoked{background:rgba(220,38,38,.15);color:#fca5a5}
.dir-status.archived{background:rgba(100,116,139,.15);color:#94a3b8}
.quick-panel{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:28px}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
@media(max-width:700px){.form-grid{grid-template-columns:1fr}}
.form-grid label{display:flex;flex-direction:column;gap:4px;font-size:.82rem;color:var(--dim)}
.form-grid input,.form-grid select,.form-grid textarea{background:var(--bg);border:1px solid var(--border);border-radius:6px;padding:8px 10px;color:var(--text);font-size:.85rem}
.form-grid textarea{grid-column:1/-1;min-height:80px;resize:vertical}
.form-footer{grid-column:1/-1;display:flex;justify-content:flex-end;gap:8px;margin-top:4px}
.card-box{background:var(--card);border:1px solid var(--border);border-radius:10px;padding:20px}
.defcon-selector{display:flex;gap:8px;margin:12px 0}
.defcon-btn{width:48px;height:48px;border-radius:8px;border:2px solid var(--border);background:var(--card);color:var(--text);font-size:1.2rem;font-weight:700;cursor:pointer;transition:all .2s}
.defcon-btn:hover,.defcon-btn.active{transform:scale(1.1);border-width:3px}
.empty-state{text-align:center;padding:30px;color:var(--dim);font-size:.9rem}
.empty-state i{font-size:2rem;margin-bottom:8px;display:block}
</style>
</head>
<body>
<?php require_once __DIR__ . '/includes/site-header.inc.php'; ?>
<main>
<div class="sc-wrap">

<!-- Command Header -->
<div class="sc-header">
    <div class="sc-title">
        <i class="fas fa-crown"></i>
        <h1>SUPREME COMMAND<span><?php echo $commanderName; ?> — <?php echo htmlspecialchars($userRankCode); ?></span></h1>
    </div>
    <div class="defcon-badge" style="border-color:<?php echo $defconColors[$defcon]; ?>;color:<?php echo $defconColors[$defcon]; ?>;background:<?php echo $defconColors[$defcon]; ?>18">
        <i class="fas fa-shield-halved"></i>
        DEFCON <?php echo $defcon; ?> — <?php echo $defconLabels[$defcon]; ?>
    </div>
</div>

<!-- Flash message -->
<?php if ($msg): ?>
<div class="msg <?php echo $msgType === 'success' ? 'success' : 'error'; ?>"><?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>

<!-- World State Bar -->
<div class="world-bar">
    <div class="world-chip"><i class="fas fa-users"></i> <?php echo number_format($totalPersonnel); ?> Personnel</div>
    <div class="world-chip"><i class="fas fa-shield"></i> <?php echo $totalUnits; ?> Units</div>
    <div class="world-chip"><i class="fas fa-crosshairs"></i> <?php echo $activeMissions; ?> Active Missions</div>
    <div class="world-chip"><i class="fas fa-map"></i> <?php echo $controlledZones; ?>/<?php echo $totalZones; ?> Zones</div>
    <div class="world-chip"><i class="fas fa-gavel"></i> <?php echo $openCases; ?> Open Cases</div>
    <div class="world-chip"><i class="fas fa-handshake"></i> <?php echo $activeAlliances; ?> Alliances</div>
    <?php if ($unreadAlerts > 0): ?>
    <div class="world-chip" style="border-color:var(--red)"><i class="fas fa-bell" style="color:var(--red)"></i> <?php echo $unreadAlerts; ?> Unread Alerts</div>
    <?php endif; ?>
</div>

<!-- Metrics Grid -->
<div class="section-title"><i class="fas fa-chart-line"></i> STRATEGIC OVERVIEW</div>
<div class="metrics-grid">
    <!-- Personnel -->
    <div class="m-card">
        <div class="m-head"><h3>Personnel</h3><i class="fas fa-users" style="color:var(--accent)"></i></div>
        <div class="m-body">
            <div class="m-stat"><span class="label">Total Enlisted</span><span class="value"><?php echo number_format($totalPersonnel); ?></span></div>
            <div class="m-stat"><span class="label">Active Units</span><span class="value"><?php echo $totalUnits; ?></span></div>
            <div class="m-stat"><span class="label">Avg XP</span><span class="value"><?php echo number_format($avgXp); ?></span></div>
        </div>
    </div>
    <!-- Combat -->
    <div class="m-card">
        <div class="m-head"><h3>Combat</h3><i class="fas fa-crosshairs" style="color:var(--red)"></i></div>
        <div class="m-body">
            <div class="m-stat"><span class="label">Active Missions</span><span class="value"><?php echo $activeMissions; ?></span></div>
            <div class="m-stat"><span class="label">Campaigns</span><span class="value"><?php echo $activeCampaigns; ?></span></div>
            <div class="m-stat"><span class="label">Zones Held</span><span class="value"><?php echo $controlledZones; ?></span></div>
        </div>
    </div>
    <!-- Economy -->
    <div class="m-card">
        <div class="m-head"><h3>Economy</h3><i class="fas fa-coins" style="color:var(--gold)"></i></div>
        <div class="m-body">
            <div class="m-stat"><span class="label">Treasury</span><span class="value gold"><?php echo number_format($totalTreasury, 2); ?></span></div>
            <div class="m-stat"><span class="label">Pending Supply Req.</span><span class="value"><?php echo $totalSupply; ?></span></div>
        </div>
    </div>
    <!-- Territory -->
    <div class="m-card">
        <div class="m-head"><h3>Territory</h3><i class="fas fa-map-marked-alt" style="color:var(--green)"></i></div>
        <div class="m-body">
            <div class="m-stat"><span class="label">Controlled / Total</span><span class="value"><?php echo $controlledZones; ?> / <?php echo $totalZones; ?></span></div>
            <?php $pct = $totalZones > 0 ? round(($controlledZones / $totalZones) * 100) : 0; ?>
            <div class="m-bar"><span style="width:<?php echo $pct; ?>%;background:var(--green)"></span></div>
            <div class="m-stat"><span class="label">Control %</span><span class="value"><?php echo $pct; ?>%</span></div>
        </div>
    </div>
    <!-- Morale -->
    <div class="m-card">
        <div class="m-head"><h3>Morale</h3><i class="fas fa-heart" style="color:var(--purple)"></i></div>
        <div class="m-body">
            <?php $moralePct = min(100, max(0, $avgMorale)); ?>
            <div class="m-stat"><span class="label">Average Morale</span><span class="value"><?php echo $avgMorale; ?></span></div>
            <div class="m-bar"><span style="width:<?php echo $moralePct; ?>%;background:<?php echo $moralePct >= 70 ? 'var(--green)' : ($moralePct >= 40 ? 'var(--yellow)' : 'var(--red)'); ?>"></span></div>
        </div>
    </div>
    <!-- Cyber -->
    <div class="m-card">
        <div class="m-head"><h3>Cyber</h3><i class="fas fa-terminal" style="color:#06b6d4"></i></div>
        <div class="m-body">
            <div class="m-stat"><span class="label">Challenges Solved</span><span class="value"><?php echo $cyberSolved; ?></span></div>
            <div class="m-stat"><span class="label">Active Ops</span><span class="value"><?php echo $cyberOps; ?></span></div>
        </div>
    </div>
    <!-- Intel -->
    <div class="m-card">
        <div class="m-head"><h3>Intel</h3><i class="fas fa-satellite-dish" style="color:var(--orange)"></i></div>
        <div class="m-body">
            <div class="m-stat"><span class="label">Total Reports</span><span class="value"><?php echo $intelReports; ?></span></div>
            <div class="m-stat"><span class="label">Active Threats</span><span class="value" style="color:<?php echo $activeThreats > 0 ? 'var(--red)' : 'var(--green)'; ?>"><?php echo $activeThreats; ?></span></div>
        </div>
    </div>
    <!-- Logistics -->
    <div class="m-card">
        <div class="m-head"><h3>Logistics</h3><i class="fas fa-truck" style="color:#84cc16"></i></div>
        <div class="m-body">
            <div class="m-stat"><span class="label">Active Convoys</span><span class="value"><?php echo $activeConvoys; ?></span></div>
            <div class="m-stat"><span class="label">Fleet Readiness</span><span class="value"><?php echo $fleetReady; ?></span></div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<?php if ($isCommander): ?>
<div class="section-title"><i class="fas fa-bolt"></i> QUICK ACTIONS</div>
<div class="quick-panel">
    <button class="btn btn-gold" onclick="document.getElementById('defcon-panel').scrollIntoView({behavior:'smooth'})"><i class="fas fa-shield-halved"></i> Set DEFCON</button>
    <button class="btn btn-gold" onclick="document.getElementById('directive-form').scrollIntoView({behavior:'smooth'})"><i class="fas fa-scroll"></i> Issue Directive</button>
    <a href="/broadcasting.php" class="btn btn-blue"><i class="fas fa-tower-broadcast"></i> Global Broadcast</a>
    <a href="/strategic-campaigns.php" class="btn btn-blue"><i class="fas fa-chess"></i> View Campaigns</a>
    <a href="/territory.php" class="btn btn-blue"><i class="fas fa-map"></i> Territory Map</a>
    <a href="/intelligence.php" class="btn btn-blue"><i class="fas fa-satellite-dish"></i> Intel Center</a>
    <a href="/military-court.php" class="btn btn-blue"><i class="fas fa-gavel"></i> Court Martial</a>
    <a href="/chain-of-command.php" class="btn btn-blue"><i class="fas fa-sitemap"></i> Chain of Command</a>
</div>

<div class="section-title" style="margin-top:1.5rem"><i class="fas fa-landmark"></i> SOVEREIGN STATE</div>
<div class="quick-panel">
    <a href="/constitution.php" class="btn btn-gold"><i class="fas fa-scroll"></i> Constitution</a>
    <a href="/senate.php" class="btn btn-gold"><i class="fas fa-landmark-dome"></i> Senate</a>
    <a href="/treasury.php" class="btn btn-gold"><i class="fas fa-vault"></i> Treasury</a>
    <a href="/homeland.php" class="btn btn-blue"><i class="fas fa-shield-virus"></i> Homeland Security</a>
    <a href="/civil-affairs.php" class="btn btn-blue"><i class="fas fa-people-group"></i> Civil Affairs</a>
    <a href="/diplomacy.php" class="btn btn-blue"><i class="fas fa-handshake"></i> Diplomacy</a>
    <a href="/national-guard.php" class="btn btn-blue"><i class="fas fa-flag-usa"></i> National Guard</a>
    <a href="/stratcom.php" class="btn btn-blue"><i class="fas fa-chess-knight"></i> StratCom</a>
    <a href="/psyops.php" class="btn btn-blue"><i class="fas fa-brain"></i> PsyOps</a>
    <a href="/spec-ops.php" class="btn btn-blue"><i class="fas fa-crosshairs"></i> Spec Ops</a>
    <a href="/military-police.php" class="btn btn-blue"><i class="fas fa-shield"></i> Military Police</a>
</div>
<?php endif; ?>

<!-- Two-column: Alerts + DEFCON -->
<div class="two-col">
    <!-- Alert Feed -->
    <div>
        <div class="section-title"><i class="fas fa-bell"></i> ALERT FEED <?php if ($unreadAlerts): ?><span style="color:var(--red);font-size:.8rem">(<?php echo $unreadAlerts; ?> unread)</span><?php endif; ?></div>
        <div class="alert-feed">
        <?php if (empty($alerts)): ?>
            <div class="empty-state"><i class="fas fa-check-circle"></i> No alerts. All clear.</div>
        <?php else: foreach ($alerts as $a):
            $icons = ['info'=>'fa-circle-info','warning'=>'fa-triangle-exclamation','danger'=>'fa-skull-crossbones','critical'=>'fa-radiation','victory'=>'fa-trophy'];
            $colors = ['info'=>'var(--accent)','warning'=>'var(--orange)','danger'=>'var(--red)','critical'=>'#dc2626','victory'=>'var(--gold)'];
            $at = $a['alert_type'];
        ?>
            <div class="alert-item <?php echo htmlspecialchars($at); ?> <?php echo $a['is_read'] ? 'read' : ''; ?>">
                <div class="alert-icon" style="color:<?php echo $colors[$at] ?? 'var(--dim)'; ?>"><i class="fas <?php echo $icons[$at] ?? 'fa-circle'; ?>"></i></div>
                <div class="alert-body">
                    <h4><?php echo htmlspecialchars($a['title']); ?></h4>
                    <p><?php echo htmlspecialchars($a['message'] ?? ''); ?></p>
                    <div class="alert-meta"><?php echo htmlspecialchars($a['source_system'] ?? ''); ?> &middot; <?php echo date('M j, H:i', strtotime($a['created_at'])); ?></div>
                </div>
                <div class="alert-actions">
                    <?php if ($a['is_actionable'] && $a['action_url']): ?>
                    <a href="<?php echo htmlspecialchars($a['action_url']); ?>" class="btn btn-sm btn-blue"><i class="fas fa-arrow-right"></i></a>
                    <?php endif; ?>
                    <?php if (!$a['is_read']): ?>
                    <form method="post" style="display:inline">
                        <input type="hidden" name="csrf" value="<?php echo $csrf; ?>">
                        <input type="hidden" name="action" value="dismiss_alert">
                        <input type="hidden" name="alert_id" value="<?php echo (int)$a['id']; ?>">
                        <button class="btn btn-sm" title="Dismiss"><i class="fas fa-check"></i></button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- DEFCON Panel -->
    <div id="defcon-panel">
        <div class="section-title"><i class="fas fa-shield-halved"></i> DEFCON CONTROL</div>
        <div class="card-box">
            <p style="margin-bottom:12px;color:var(--dim);font-size:.85rem">Current level: <strong style="color:<?php echo $defconColors[$defcon]; ?>"><?php echo $defcon; ?> — <?php echo $defconLabels[$defcon]; ?></strong></p>
            <?php if ($isCommander): ?>
            <form method="post" id="defcon-form">
                <input type="hidden" name="csrf" value="<?php echo $csrf; ?>">
                <input type="hidden" name="action" value="set_defcon">
                <input type="hidden" name="defcon_level" id="defcon-val" value="<?php echo $defcon; ?>">
                <div class="defcon-selector">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <button type="button" class="defcon-btn <?php echo $i === $defcon ? 'active' : ''; ?>" style="border-color:<?php echo $defconColors[$i]; ?>;color:<?php echo $defconColors[$i]; ?><?php echo $i === $defcon ? ';background:'.$defconColors[$i].'25' : ''; ?>" onclick="selectDefcon(<?php echo $i; ?>)"><?php echo $i; ?></button>
                    <?php endfor; ?>
                </div>
                <label style="display:flex;flex-direction:column;gap:4px;font-size:.82rem;color:var(--dim);margin-bottom:12px">
                    Reason
                    <input type="text" name="defcon_reason" placeholder="Reason for change..." style="background:var(--bg);border:1px solid var(--border);border-radius:6px;padding:8px 10px;color:var(--text);font-size:.85rem">
                </label>
                <button type="submit" class="btn btn-gold"><i class="fas fa-shield-halved"></i> Set DEFCON</button>
            </form>
            <?php else: ?>
            <p style="color:var(--dim);font-size:.85rem">Only the Commander can change DEFCON level.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Directives -->
<div class="section-title"><i class="fas fa-scroll"></i> SUPREME DIRECTIVES</div>
<div class="card-box" style="margin-bottom:28px;overflow-x:auto">
<?php if (empty($directives)): ?>
    <div class="empty-state"><i class="fas fa-scroll"></i> No directives issued.</div>
<?php else: ?>
    <table class="dir-table">
        <thead><tr><th>Code</th><th>Title</th><th>Priority</th><th>Status</th><th>Issued By</th><th>Effective</th><th>Expiry</th><?php if ($isCommander): ?><th>Action</th><?php endif; ?></tr></thead>
        <tbody>
        <?php foreach ($directives as $d): ?>
        <tr>
            <td style="font-weight:600;color:var(--gold)"><?php echo htmlspecialchars($d['directive_code']); ?></td>
            <td><?php echo htmlspecialchars($d['title']); ?></td>
            <td style="text-align:center"><?php echo (int)$d['priority']; ?></td>
            <td><span class="dir-status <?php echo htmlspecialchars($d['status']); ?>"><?php echo htmlspecialchars($d['status']); ?></span></td>
            <td style="color:var(--dim)"><?php echo htmlspecialchars($d['issuer_name'] ?? 'System'); ?></td>
            <td style="color:var(--dim)"><?php echo $d['effective_date'] ? date('M j, Y', strtotime($d['effective_date'])) : '—'; ?></td>
            <td style="color:var(--dim)"><?php echo $d['expiry_date'] ? date('M j, Y', strtotime($d['expiry_date'])) : '—'; ?></td>
            <?php if ($isCommander): ?>
            <td>
                <?php if ($d['status'] === 'active'): ?>
                <form method="post" style="display:inline">
                    <input type="hidden" name="csrf" value="<?php echo $csrf; ?>">
                    <input type="hidden" name="action" value="update_directive">
                    <input type="hidden" name="dir_id" value="<?php echo (int)$d['id']; ?>">
                    <select name="dir_status" onchange="this.form.submit()" style="background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:4px;padding:3px 6px;font-size:.78rem">
                        <option value="">— change —</option>
                        <option value="suspended">Suspend</option>
                        <option value="revoked">Revoke</option>
                        <option value="archived">Archive</option>
                    </select>
                </form>
                <?php elseif ($d['status'] === 'suspended'): ?>
                <form method="post" style="display:inline">
                    <input type="hidden" name="csrf" value="<?php echo $csrf; ?>">
                    <input type="hidden" name="action" value="update_directive">
                    <input type="hidden" name="dir_id" value="<?php echo (int)$d['id']; ?>">
                    <input type="hidden" name="dir_status" value="active">
                    <button class="btn btn-sm btn-green"><i class="fas fa-play"></i> Reactivate</button>
                </form>
                <?php else: ?>
                <span style="color:var(--dim);font-size:.78rem">—</span>
                <?php endif; ?>
            </td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
</div>

<!-- Issue New Directive (Commander) -->
<?php if ($isCommander): ?>
<div id="directive-form">
    <div class="section-title"><i class="fas fa-pen-fancy"></i> ISSUE NEW DIRECTIVE</div>
    <div class="card-box" style="margin-bottom:28px">
        <form method="post">
            <input type="hidden" name="csrf" value="<?php echo $csrf; ?>">
            <input type="hidden" name="action" value="issue_directive">
            <div class="form-grid">
                <label>Directive Code <input type="text" name="d_code" required placeholder="SC-2026-001" maxlength="50"></label>
                <label>Title <input type="text" name="d_title" required placeholder="Directive title" maxlength="200"></label>
                <label>Priority (1-10) <input type="number" name="d_priority" value="5" min="1" max="10"></label>
                <label>Effective Date <input type="date" name="d_effective" value="<?php echo date('Y-m-d'); ?>"></label>
                <label>Expiry Date <input type="date" name="d_expiry"></label>
                <label style="grid-column:1/-1">Content <textarea name="d_content" placeholder="Directive content..." rows="4"></textarea></label>
                <div class="form-footer"><button type="submit" class="btn btn-gold"><i class="fas fa-scroll"></i> Issue Directive</button></div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

</div><!-- /sc-wrap -->
</main>

<script>
function selectDefcon(level) {
    document.getElementById('defcon-val').value = level;
    document.querySelectorAll('.defcon-btn').forEach(function(b) { b.classList.remove('active'); b.style.background = ''; });
    var btn = document.querySelectorAll('.defcon-btn')[level - 1];
    btn.classList.add('active');
    btn.style.background = btn.style.borderColor + '25';
}
</script>

<?php require_once __DIR__ . '/includes/site-footer.inc.php'; ?>
</body>
</html>
