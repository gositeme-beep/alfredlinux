<?php
/**
 * ═══════════════════════════════════════════
 *  Department of Homeland Security — Level 6: Sovereign State
 * ═══════════════════════════════════════════
 */
require_once __DIR__ . '/includes/db-config.inc.php';
$db = getSharedDB();
require_once __DIR__ . '/includes/auth-gate.inc.php';
require_once __DIR__ . '/includes/rank-guard.inc.php';
if (empty($_SESSION['csrf_hl'])) $_SESSION['csrf_hl'] = bin2hex(random_bytes(32));
requireRank(4);

$isCommander = ($clientId === 33);
$isFlag      = ($userRankTier >= 9) || $isCommander;
$isOfficer   = ($userRankTier >= 6) || $isCommander;
$isNCO       = ($userRankTier >= 4) || $isCommander;
$msg = '';
$msgType = '';

// ── Auto-create tables ──
$db->exec("CREATE TABLE IF NOT EXISTS homeland_threats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    threat_level ENUM('green','blue','yellow','orange','red') DEFAULT 'green',
    source TEXT DEFAULT NULL,
    description TEXT DEFAULT NULL,
    assessed_by INT NOT NULL,
    assessed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS homeland_watchlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    target_client_id INT NOT NULL,
    watch_level ENUM('monitoring','poi','suspect','known_threat','most_wanted') DEFAULT 'monitoring',
    threat_type TEXT DEFAULT NULL,
    evidence TEXT DEFAULT NULL,
    added_by INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active','resolved','escalated') DEFAULT 'active',
    notes TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS homeland_incidents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    incident_code VARCHAR(20) NOT NULL,
    incident_type ENUM('intrusion','subversion','coordinated_attack','data_breach','impersonation') DEFAULT 'intrusion',
    severity ENUM('low','medium','high','critical') DEFAULT 'low',
    description TEXT NOT NULL,
    reported_by INT NOT NULL,
    assigned_to INT DEFAULT NULL,
    status ENUM('reported','investigating','contained','resolved') DEFAULT 'reported',
    reported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS homeland_borders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    access_point VARCHAR(120) NOT NULL,
    access_type ENUM('login','api','webhook','external') DEFAULT 'login',
    monitored TINYINT(1) DEFAULT 1,
    restrictions TEXT DEFAULT NULL,
    last_audit_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS homeland_emergencies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    emergency_code VARCHAR(20) NOT NULL,
    emergency_type ENUM('system_failure','attack','natural_disaster','civil_unrest','pandemic') DEFAULT 'system_failure',
    declared_by INT NOT NULL,
    severity ENUM('local','regional','national') DEFAULT 'local',
    status ENUM('declared','active','contained','resolved') DEFAULT 'declared',
    protocols_activated TEXT DEFAULT NULL,
    declared_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Seed border access points ──
$bc = $db->query("SELECT COUNT(*) FROM homeland_borders")->fetchColumn();
if ($bc == 0) {
    $borders = [
        ['Alfred IDE Login', 'login'], ['WHMCS Client Area', 'login'], ['API Gateway', 'api'],
        ['MCP Webhook', 'webhook'], ['External SSH', 'external'], ['Admin Panel', 'login']
    ];
    $bs = $db->prepare("INSERT INTO homeland_borders (access_point, access_type) VALUES (?,?)");
    foreach ($borders as $b) $bs->execute($b);
}

// ── Seed initial threat level ──
$tc = $db->query("SELECT COUNT(*) FROM homeland_threats")->fetchColumn();
if ($tc == 0) {
    $db->prepare("INSERT INTO homeland_threats (threat_level, source, description, assessed_by) VALUES (?,?,?,?)")
       ->execute(['green', 'Initial Assessment', 'System initialized. All clear.', $clientId]);
}

$csrf = $_SESSION['csrf_hl'];

// ── POST Handlers ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, $_POST['csrf'] ?? '')) {
        $msg = 'Invalid security token.'; $msgType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'set_threat_level' && $isOfficer) {
            $level = $_POST['threat_level'] ?? 'green';
            $source = trim($_POST['threat_source'] ?? '');
            $desc   = trim($_POST['threat_desc'] ?? '');
            $validTL = ['green','blue','yellow','orange','red'];
            if (!in_array($level, $validTL, true)) {
                $msg = 'Invalid threat level.'; $msgType = 'error';
            } else {
                $db->exec("UPDATE homeland_threats SET active = 0");
                $db->prepare("INSERT INTO homeland_threats (threat_level, source, description, assessed_by) VALUES (?,?,?,?)")
                   ->execute([$level, $source, $desc, $clientId]);
                awardXP($clientId, 'threat_assessment', ['level' => $level]);
                $msg = "Threat level set to <strong>" . strtoupper($level) . "</strong>."; $msgType = 'success';
            }
        } elseif ($action === 'add_watchlist' && $isFlag) {
            $targetId   = (int)($_POST['target_id'] ?? 0);
            $watchLevel = $_POST['watch_level'] ?? 'monitoring';
            $threatType = trim($_POST['threat_type'] ?? '');
            $evidence   = trim($_POST['evidence'] ?? '');
            $validWL = ['monitoring','poi','suspect','known_threat','most_wanted'];
            if ($targetId < 1 || !in_array($watchLevel, $validWL, true)) {
                $msg = 'Valid target and watch level required.'; $msgType = 'error';
            } else {
                $db->prepare("INSERT INTO homeland_watchlist (target_client_id, watch_level, threat_type, evidence, added_by) VALUES (?,?,?,?,?)")
                   ->execute([$targetId, $watchLevel, $threatType, $evidence, $clientId]);
                awardXP($clientId, 'watchlist_add', ['level' => $watchLevel]);
                $msg = "Target added to watchlist at <strong>" . strtoupper(str_replace('_', ' ', $watchLevel)) . "</strong>."; $msgType = 'success';
            }
        } elseif ($action === 'update_watchlist' && $isFlag) {
            $wlId   = (int)($_POST['wl_id'] ?? 0);
            $newLvl = $_POST['new_watch_level'] ?? '';
            $status = $_POST['wl_status'] ?? '';
            $notes  = trim($_POST['wl_notes'] ?? '');
            $validWL = ['monitoring','poi','suspect','known_threat','most_wanted'];
            $validWS = ['active','resolved','escalated'];
            if (!in_array($newLvl, $validWL, true) || !in_array($status, $validWS, true)) {
                $msg = 'Invalid parameters.'; $msgType = 'error';
            } else {
                $stmt = $db->prepare("UPDATE homeland_watchlist SET watch_level = ?, status = ?, notes = ? WHERE id = ?");
                $stmt->execute([$newLvl, $status, $notes, $wlId]);
                $msg = $stmt->rowCount() ? 'Watchlist entry updated.' : 'Not found.';
                $msgType = $stmt->rowCount() ? 'success' : 'error';
            }
        } elseif ($action === 'report_incident' && $isNCO) {
            $incType = $_POST['incident_type'] ?? 'intrusion';
            $severity = $_POST['severity'] ?? 'low';
            $desc     = trim($_POST['inc_desc'] ?? '');
            $validIT = ['intrusion','subversion','coordinated_attack','data_breach','impersonation'];
            $validSV = ['low','medium','high','critical'];
            if (!in_array($incType, $validIT, true) || !in_array($severity, $validSV, true) || $desc === '') {
                $msg = 'All incident fields required.'; $msgType = 'error';
            } else {
                $code = 'INC-' . strtoupper(bin2hex(random_bytes(4)));
                $db->prepare("INSERT INTO homeland_incidents (incident_code, incident_type, severity, description, reported_by) VALUES (?,?,?,?,?)")
                   ->execute([$code, $incType, $severity, $desc, $clientId]);
                awardXP($clientId, 'incident_reported', ['code' => $code]);
                $msg = "Incident <strong>$code</strong> reported."; $msgType = 'success';
            }
        } elseif ($action === 'investigate_incident' && $isOfficer) {
            $incId     = (int)($_POST['inc_id'] ?? 0);
            $assignedTo = (int)($_POST['assigned_to'] ?? $clientId);
            $stmt = $db->prepare("UPDATE homeland_incidents SET status = 'investigating', assigned_to = ? WHERE id = ? AND status = 'reported'");
            $stmt->execute([$assignedTo, $incId]);
            $msg = $stmt->rowCount() ? 'Investigation assigned.' : 'Not found or already assigned.';
            $msgType = $stmt->rowCount() ? 'success' : 'error';

        } elseif ($action === 'resolve_incident' && $isOfficer) {
            $incId = (int)($_POST['inc_id'] ?? 0);
            $stmt = $db->prepare("UPDATE homeland_incidents SET status = 'resolved', resolved_at = NOW() WHERE id = ? AND status IN ('investigating','contained')");
            $stmt->execute([$incId]);
            $msg = $stmt->rowCount() ? 'Incident resolved.' : 'Not found.';
            $msgType = $stmt->rowCount() ? 'success' : 'error';

        } elseif ($action === 'contain_incident' && $isOfficer) {
            $incId = (int)($_POST['inc_id'] ?? 0);
            $stmt = $db->prepare("UPDATE homeland_incidents SET status = 'contained' WHERE id = ? AND status = 'investigating'");
            $stmt->execute([$incId]);
            $msg = $stmt->rowCount() ? 'Incident contained.' : 'Not found.';
            $msgType = $stmt->rowCount() ? 'success' : 'error';

        } elseif ($action === 'declare_emergency' && $isCommander) {
            $emType   = $_POST['emergency_type'] ?? 'system_failure';
            $severity = $_POST['em_severity'] ?? 'local';
            $protocols = trim($_POST['protocols'] ?? '');
            $validET = ['system_failure','attack','natural_disaster','civil_unrest','pandemic'];
            $validES = ['local','regional','national'];
            if (!in_array($emType, $validET, true) || !in_array($severity, $validES, true)) {
                $msg = 'Invalid emergency parameters.'; $msgType = 'error';
            } else {
                $code = 'EM-' . strtoupper(bin2hex(random_bytes(3)));
                $db->prepare("INSERT INTO homeland_emergencies (emergency_code, emergency_type, declared_by, severity, protocols_activated) VALUES (?,?,?,?,?)")
                   ->execute([$code, $emType, $clientId, $severity, $protocols]);
                awardXP($clientId, 'emergency_declared', ['code' => $code]);
                $msg = "🚨 EMERGENCY <strong>$code</strong> declared — " . strtoupper($severity) . " level."; $msgType = 'error';
            }
        } elseif ($action === 'resolve_emergency' && $isCommander) {
            $emId = (int)($_POST['em_id'] ?? 0);
            $stmt = $db->prepare("UPDATE homeland_emergencies SET status = 'resolved', resolved_at = NOW() WHERE id = ? AND status IN ('declared','active','contained')");
            $stmt->execute([$emId]);
            $msg = $stmt->rowCount() ? 'Emergency resolved.' : 'Not found.';
            $msgType = $stmt->rowCount() ? 'success' : 'error';

        } elseif ($action === 'activate_protocol' && $isFlag) {
            $emId      = (int)($_POST['em_id'] ?? 0);
            $protocol  = trim($_POST['protocol_name'] ?? '');
            if ($protocol === '') { $msg = 'Protocol name required.'; $msgType = 'error'; }
            else {
                $em = $db->prepare("SELECT protocols_activated FROM homeland_emergencies WHERE id = ?");
                $em->execute([$emId]);
                $row = $em->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $existing = $row['protocols_activated'] ? $row['protocols_activated'] . ', ' . $protocol : $protocol;
                    $db->prepare("UPDATE homeland_emergencies SET protocols_activated = ?, status = 'active' WHERE id = ?")->execute([$existing, $emId]);
                    $msg = "Protocol <strong>" . htmlspecialchars($protocol) . "</strong> activated."; $msgType = 'success';
                } else { $msg = 'Emergency not found.'; $msgType = 'error'; }
            }
        } elseif ($action === 'audit_border' && $isOfficer) {
            $borderId = (int)($_POST['border_id'] ?? 0);
            $restrictions = trim($_POST['border_restrictions'] ?? '');
            $monitored = !empty($_POST['border_monitored']);
            $stmt = $db->prepare("UPDATE homeland_borders SET monitored = ?, restrictions = ?, last_audit_at = NOW() WHERE id = ?");
            $stmt->execute([$monitored ? 1 : 0, $restrictions, $borderId]);
            $msg = $stmt->rowCount() ? 'Border access point audited.' : 'Not found.';
            $msgType = $stmt->rowCount() ? 'success' : 'error';
        } else {
            $msg = 'Unauthorized or unknown action.'; $msgType = 'error';
        }
    }
    $_SESSION['csrf_hl'] = bin2hex(random_bytes(32));
    $csrf = $_SESSION['csrf_hl'];
}

// ── Data Fetch ──
$tab = $_GET['tab'] ?? 'advisory';
$currentThreat = $db->query("SELECT t.*, CONCAT(c.firstname,' ',c.lastname) AS assessor_name FROM homeland_threats t LEFT JOIN tblclients c ON c.id = t.assessed_by WHERE t.active = 1 ORDER BY t.assessed_at DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$watchlist = $db->query("SELECT w.*, CONCAT(c.firstname,' ',c.lastname) AS target_name, CONCAT(c2.firstname,' ',c2.lastname) AS added_name FROM homeland_watchlist w LEFT JOIN tblclients c ON c.id = w.target_client_id LEFT JOIN tblclients c2 ON c2.id = w.added_by ORDER BY FIELD(w.watch_level,'most_wanted','known_threat','suspect','poi','monitoring'), w.added_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$incidents = $db->query("SELECT i.*, CONCAT(c.firstname,' ',c.lastname) AS reporter_name, CONCAT(c2.firstname,' ',c2.lastname) AS assignee_name FROM homeland_incidents i LEFT JOIN tblclients c ON c.id = i.reported_by LEFT JOIN tblclients c2 ON c2.id = i.assigned_to ORDER BY i.reported_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$borders = $db->query("SELECT * FROM homeland_borders ORDER BY access_type, access_point")->fetchAll(PDO::FETCH_ASSOC);
$emergencies = $db->query("SELECT e.*, CONCAT(c.firstname,' ',c.lastname) AS declarer_name FROM homeland_emergencies e LEFT JOIN tblclients c ON c.id = e.declared_by ORDER BY e.declared_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$activeWL = count(array_filter($watchlist, fn($w) => $w['status'] === 'active'));
$openInc  = count(array_filter($incidents, fn($i) => in_array($i['status'], ['reported','investigating'])));
$activeEmg = count(array_filter($emergencies, fn($e) => in_array($e['status'], ['declared','active'])));

$threatLevel = $currentThreat['threat_level'] ?? 'green';
$threatColors = ['green'=>'#22c55e','blue'=>'#3b82f6','yellow'=>'#eab308','orange'=>'#f97316','red'=>'#ef4444'];
$threatLabels = ['green'=>'LOW','blue'=>'GUARDED','yellow'=>'ELEVATED','orange'=>'HIGH','red'=>'SEVERE'];
$tlColor = $threatColors[$threatLevel];

$pageTitle = 'Department of Homeland Security';
include __DIR__ . '/includes/site-header.inc.php';
?>
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<style>
.hl-wrap{max-width:1100px;margin:0 auto;padding:1.5rem}
.hl-bg{background:#0a0a14;min-height:100vh;color:#e2e8f0}
.hl-card{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:10px;padding:1.25rem;margin-bottom:1rem}
.hl-card:hover{border-color:<?= $tlColor ?>;box-shadow:0 0 12px <?= $tlColor ?>1a}
.hl-title{font-size:1.6rem;font-weight:700;color:#f1f5f9;margin-bottom:.25rem;display:flex;align-items:center;gap:.5rem}
.hl-sub{color:#94a3b8;font-size:.85rem}
.hl-badge{display:inline-block;padding:2px 10px;border-radius:999px;font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
.hl-tabs{display:flex;gap:.5rem;margin-bottom:1.25rem;flex-wrap:wrap}
.hl-tab{padding:.4rem 1rem;border-radius:6px;cursor:pointer;font-size:.8rem;background:#2a2a4a;color:#94a3b8;text-decoration:none;font-weight:600;border:none}
.hl-tab.active{background:<?= $tlColor ?>;color:#fff}
.hl-stat-bar{display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem}
.hl-stat{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:8px;padding:.75rem 1.25rem;flex:1;min-width:120px;text-align:center}
.hl-stat .val{font-size:1.5rem;font-weight:700;color:<?= $tlColor ?>}
.hl-stat .lbl{font-size:.7rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px}
.hl-btn{background:<?= $tlColor ?>;color:#fff;border:none;padding:.5rem 1.25rem;border-radius:6px;cursor:pointer;font-size:.85rem;font-weight:600;transition:.2s}
.hl-btn:hover{opacity:.85}
.hl-btn-sm{padding:.3rem .75rem;font-size:.75rem}
.hl-btn-outline{background:transparent;border:1px solid <?= $tlColor ?>;color:<?= $tlColor ?>}
.hl-btn-outline:hover{background:<?= $tlColor ?>;color:#fff}
.hl-btn-red{background:#ef4444;color:#fff}.hl-btn-red:hover{background:#dc2626}
.hl-btn-green{background:#22c55e;color:#fff}.hl-btn-green:hover{background:#16a34a}
.hl-btn-blue{background:#3b82f6;color:#fff}.hl-btn-blue:hover{background:#2563eb}
.hl-input,.hl-select,.hl-textarea{width:100%;background:#0a0a14;border:1px solid #2a2a4a;color:#e2e8f0;padding:.5rem .75rem;border-radius:6px;font-size:.85rem;box-sizing:border-box}
.hl-textarea{min-height:100px;resize:vertical}
.hl-label{display:block;color:#94a3b8;font-size:.75rem;margin-bottom:.25rem;text-transform:uppercase;letter-spacing:.5px}
.hl-msg{padding:.75rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:.85rem}
.hl-msg-success{background:rgba(34,197,94,.12);border:1px solid #22c55e;color:#86efac}
.hl-msg-error{background:rgba(239,68,68,.12);border:1px solid #ef4444;color:#fca5a5}
.hl-modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:900;align-items:center;justify-content:center}
.hl-modal-bg.open{display:flex}
.hl-modal{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:12px;padding:1.5rem;width:90%;max-width:580px;max-height:80vh;overflow-y:auto}
.hl-modal h3{color:#f1f5f9;margin:0 0 1rem}
.hl-form-row{margin-bottom:.75rem}
.hl-threat-banner{border-radius:10px;padding:1.5rem;display:flex;align-items:center;gap:1.5rem;margin-bottom:1.5rem;flex-wrap:wrap}
.hl-row{display:flex;align-items:center;gap:1rem;padding:.75rem 1rem;background:#0a0a14;border:1px solid #2a2a4a;border-radius:8px;margin-bottom:.5rem}
</style>
<div class="hl-bg">
<div class="hl-wrap">
    <div class="hl-title"><i class="fas fa-shield-virus"></i> Department of Homeland Security</div>
    <p class="hl-sub" style="margin-bottom:1.25rem">Threat advisory, watchlist, incident management, border security, and emergency declarations</p>

    <?php if ($msg): ?><div class="hl-msg hl-msg-<?= $msgType ?>"><?= $msg ?></div><?php endif; ?>

    <!-- Threat Level Banner -->
    <div class="hl-threat-banner" style="background:<?= $tlColor ?>15;border:2px solid <?= $tlColor ?>">
        <div style="text-align:center;min-width:120px">
            <div style="font-size:3rem;font-weight:900;color:<?= $tlColor ?>;line-height:1"><?= $threatLabels[$threatLevel] ?></div>
            <div style="color:<?= $tlColor ?>;font-size:.75rem;text-transform:uppercase;letter-spacing:.1em;margin-top:.25rem">THREAT LEVEL <?= strtoupper($threatLevel) ?></div>
        </div>
        <div style="flex:1;min-width:200px">
            <div style="display:flex;gap:4px;margin-bottom:.5rem">
                <?php foreach (['green','blue','yellow','orange','red'] as $tl): ?>
                    <div style="flex:1;height:8px;border-radius:4px;background:<?= $threatColors[$tl] ?>;opacity:<?= $tl === $threatLevel ? 1 : .2 ?>"></div>
                <?php endforeach; ?>
            </div>
            <div style="color:#94a3b8;font-size:.8rem"><?= htmlspecialchars($currentThreat['source'] ?? '') ?> — <?= htmlspecialchars($currentThreat['description'] ?? '') ?></div>
            <div style="color:#64748b;font-size:.75rem;margin-top:.25rem">By: <?= htmlspecialchars($currentThreat['assessor_name'] ?? 'System') ?> &bull; <?= date('M j, Y H:i', strtotime($currentThreat['assessed_at'] ?? 'now')) ?></div>
        </div>
    </div>

    <div class="hl-stat-bar">
        <div class="hl-stat"><div class="val"><?= $activeWL ?></div><div class="lbl">Active Watchlist</div></div>
        <div class="hl-stat"><div class="val" style="color:#f59e0b"><?= $openInc ?></div><div class="lbl">Open Incidents</div></div>
        <div class="hl-stat"><div class="val" style="color:<?= $activeEmg > 0 ? '#ef4444' : '#22c55e' ?>"><?= $activeEmg ?></div><div class="lbl">Active Emergencies</div></div>
        <div class="hl-stat"><div class="val" style="color:#3b82f6"><?= count($borders) ?></div><div class="lbl">Access Points</div></div>
    </div>

    <div class="hl-tabs">
        <a href="?tab=advisory" class="hl-tab <?= $tab==='advisory'?'active':'' ?>"><i class="fas fa-gauge-high"></i> Advisory</a>
        <a href="?tab=watchlist" class="hl-tab <?= $tab==='watchlist'?'active':'' ?>"><i class="fas fa-eye"></i> Watchlist</a>
        <a href="?tab=incidents" class="hl-tab <?= $tab==='incidents'?'active':'' ?>"><i class="fas fa-triangle-exclamation"></i> Incidents</a>
        <a href="?tab=borders" class="hl-tab <?= $tab==='borders'?'active':'' ?>"><i class="fas fa-border-all"></i> Borders</a>
        <a href="?tab=emergencies" class="hl-tab <?= $tab==='emergencies'?'active':'' ?>"><i class="fas fa-siren-on"></i> Emergencies</a>
    </div>

    <!-- ═══ TAB: ADVISORY ═══ -->
    <?php if ($tab === 'advisory'): ?>
        <?php if ($isOfficer): ?>
            <div class="hl-card"><h4 style="color:#f1f5f9;margin:0 0 .75rem"><i class="fas fa-gauge-high"></i> Set Threat Level</h4>
                <form method="POST">
                    <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="set_threat_level">
                    <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:.75rem">
                        <?php foreach (['green'=>'LOW','blue'=>'GUARDED','yellow'=>'ELEVATED','orange'=>'HIGH','red'=>'SEVERE'] as $tlk => $tll): ?>
                            <label style="display:flex;align-items:center;gap:.25rem;padding:.4rem .75rem;border-radius:6px;cursor:pointer;background:<?= $threatColors[$tlk] ?>15;border:1px solid <?= $threatColors[$tlk] ?>40;color:<?= $threatColors[$tlk] ?>;font-size:.8rem;font-weight:600">
                                <input type="radio" name="threat_level" value="<?= $tlk ?>" <?= $tlk === $threatLevel ? 'checked' : '' ?>> <?= $tll ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="hl-form-row"><label class="hl-label">Source</label><input type="text" name="threat_source" class="hl-input" placeholder="Intel source..."></div>
                    <div class="hl-form-row"><label class="hl-label">Assessment</label><textarea name="threat_desc" class="hl-textarea" style="min-height:60px" placeholder="Situation assessment..."></textarea></div>
                    <button class="hl-btn"><i class="fas fa-shield-halved"></i> Update Advisory</button>
                </form>
            </div>
        <?php endif; ?>
        <h4 style="color:#f1f5f9;font-size:1rem;margin-bottom:.75rem"><i class="fas fa-clock-rotate-left"></i> Assessment History</h4>
        <?php
        $history = $db->query("SELECT t.*, CONCAT(c.firstname,' ',c.lastname) AS assessor_name FROM homeland_threats t LEFT JOIN tblclients c ON c.id = t.assessed_by ORDER BY t.assessed_at DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($history as $h): ?>
            <div class="hl-row">
                <div style="width:12px;height:12px;border-radius:50%;background:<?= $threatColors[$h['threat_level']] ?>;flex-shrink:0"></div>
                <div style="flex:1">
                    <strong style="color:<?= $threatColors[$h['threat_level']] ?>"><?= strtoupper($h['threat_level']) ?></strong>
                    <span style="color:#94a3b8;font-size:.8rem;margin-left:.5rem"><?= htmlspecialchars($h['source'] ?? '') ?></span>
                    <div style="color:#64748b;font-size:.75rem"><?= htmlspecialchars($h['description'] ?? '') ?></div>
                </div>
                <div style="color:#64748b;font-size:.75rem;white-space:nowrap"><?= date('M j H:i', strtotime($h['assessed_at'])) ?></div>
            </div>
        <?php endforeach; ?>

    <!-- ═══ TAB: WATCHLIST ═══ -->
    <?php elseif ($tab === 'watchlist'): ?>
        <?php if ($isFlag): ?>
            <div style="margin-bottom:1rem"><button class="hl-btn hl-btn-red" onclick="document.getElementById('modalWatchlist').classList.add('open')"><i class="fas fa-eye"></i> Add to Watchlist</button></div>
        <?php endif; ?>
        <?php
        $wlColors = ['monitoring'=>'#94a3b8','poi'=>'#3b82f6','suspect'=>'#f59e0b','known_threat'=>'#f97316','most_wanted'=>'#ef4444'];
        foreach ($watchlist as $wl): ?>
            <div class="hl-card" style="border-left:3px solid <?= $wlColors[$wl['watch_level']] ?>">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div>
                        <span class="hl-badge" style="background:<?= $wlColors[$wl['watch_level']] ?>20;color:<?= $wlColors[$wl['watch_level']] ?>;border:1px solid <?= $wlColors[$wl['watch_level']] ?>40"><?= strtoupper(str_replace('_', ' ', $wl['watch_level'])) ?></span>
                        <strong style="color:#f1f5f9;margin-left:.5rem"><?= htmlspecialchars($wl['target_name'] ?? 'ID:' . $wl['target_client_id']) ?></strong>
                    </div>
                    <span class="hl-badge" style="background:<?= $wl['status']==='active'?'#ef444420':'#64748b20' ?>;color:<?= $wl['status']==='active'?'#ef4444':'#64748b' ?>"><?= strtoupper($wl['status']) ?></span>
                </div>
                <?php if ($wl['threat_type']): ?><div style="color:#f59e0b;font-size:.8rem;margin-top:.25rem"><i class="fas fa-tag"></i> <?= htmlspecialchars($wl['threat_type']) ?></div><?php endif; ?>
                <?php if ($wl['evidence']): ?><p style="color:#cbd5e1;font-size:.85rem;margin-top:.5rem"><?= htmlspecialchars($wl['evidence']) ?></p><?php endif; ?>
                <div style="color:#64748b;font-size:.75rem;margin-top:.25rem">Added by: <?= htmlspecialchars($wl['added_name'] ?? 'Unknown') ?> &bull; <?= date('M j, Y', strtotime($wl['added_at'])) ?></div>
                <?php if ($isFlag && $wl['status'] === 'active'): ?>
                    <form method="POST" style="margin-top:.5rem;display:flex;gap:.5rem;flex-wrap:wrap;align-items:center">
                        <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="update_watchlist"><input type="hidden" name="wl_id" value="<?= $wl['id'] ?>">
                        <select name="new_watch_level" class="hl-select" style="width:auto">
                            <?php foreach ($wlColors as $wk => $wc): ?><option value="<?= $wk ?>" <?= $wk === $wl['watch_level'] ? 'selected' : '' ?>><?= strtoupper(str_replace('_', ' ', $wk)) ?></option><?php endforeach; ?>
                        </select>
                        <select name="wl_status" class="hl-select" style="width:auto">
                            <option value="active">Active</option><option value="resolved">Resolved</option><option value="escalated">Escalated</option>
                        </select>
                        <input type="text" name="wl_notes" class="hl-input" style="flex:1;min-width:120px" placeholder="Notes...">
                        <button class="hl-btn-sm hl-btn"><i class="fas fa-pen"></i> Update</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if (empty($watchlist)): ?><div class="hl-card" style="text-align:center;color:#64748b"><p>Watchlist clear.</p></div><?php endif; ?>

    <!-- ═══ TAB: INCIDENTS ═══ -->
    <?php elseif ($tab === 'incidents'): ?>
        <?php if ($isNCO): ?>
            <div style="margin-bottom:1rem"><button class="hl-btn" onclick="document.getElementById('modalIncident').classList.add('open')"><i class="fas fa-triangle-exclamation"></i> Report Incident</button></div>
        <?php endif; ?>
        <?php
        $incColors = ['reported'=>'#f59e0b','investigating'=>'#3b82f6','contained'=>'#f97316','resolved'=>'#22c55e'];
        $sevColors = ['low'=>'#94a3b8','medium'=>'#f59e0b','high'=>'#f97316','critical'=>'#ef4444'];
        foreach ($incidents as $inc): ?>
            <div class="hl-card" style="border-left:3px solid <?= $sevColors[$inc['severity']] ?>">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div>
                        <strong style="color:#f97316;font-size:.8rem"><?= htmlspecialchars($inc['incident_code']) ?></strong>
                        <span class="hl-badge" style="background:<?= $sevColors[$inc['severity']] ?>20;color:<?= $sevColors[$inc['severity']] ?>;margin-left:.5rem"><?= strtoupper($inc['severity']) ?></span>
                        <span style="color:#94a3b8;font-size:.8rem;margin-left:.5rem"><?= strtoupper(str_replace('_', ' ', $inc['incident_type'])) ?></span>
                    </div>
                    <span class="hl-badge" style="background:<?= $incColors[$inc['status']] ?>20;color:<?= $incColors[$inc['status']] ?>;border:1px solid <?= $incColors[$inc['status']] ?>40"><?= strtoupper($inc['status']) ?></span>
                </div>
                <p style="color:#cbd5e1;font-size:.85rem;margin-top:.5rem"><?= htmlspecialchars($inc['description']) ?></p>
                <div style="color:#64748b;font-size:.75rem;margin-top:.25rem">
                    Reporter: <?= htmlspecialchars($inc['reporter_name'] ?? 'Unknown') ?>
                    <?php if ($inc['assigned_to']): ?>&bull; Assigned: <?= htmlspecialchars($inc['assignee_name'] ?? 'Unknown') ?><?php endif; ?>
                    &bull; <?= date('M j H:i', strtotime($inc['reported_at'])) ?>
                </div>
                <?php if ($isOfficer && in_array($inc['status'], ['reported','investigating','contained'])): ?>
                    <div style="margin-top:.5rem;display:flex;gap:.5rem;flex-wrap:wrap">
                        <?php if ($inc['status'] === 'reported'): ?>
                            <form method="POST" style="display:inline"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="investigate_incident"><input type="hidden" name="inc_id" value="<?= $inc['id'] ?>"><button class="hl-btn-sm hl-btn hl-btn-blue"><i class="fas fa-search"></i> Investigate</button></form>
                        <?php endif; ?>
                        <?php if ($inc['status'] === 'investigating'): ?>
                            <form method="POST" style="display:inline"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="contain_incident"><input type="hidden" name="inc_id" value="<?= $inc['id'] ?>"><button class="hl-btn-sm hl-btn" style="background:#f97316"><i class="fas fa-hand"></i> Contain</button></form>
                        <?php endif; ?>
                        <?php if (in_array($inc['status'], ['investigating','contained'])): ?>
                            <form method="POST" style="display:inline"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="resolve_incident"><input type="hidden" name="inc_id" value="<?= $inc['id'] ?>"><button class="hl-btn-sm hl-btn hl-btn-green"><i class="fas fa-check"></i> Resolve</button></form>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if (empty($incidents)): ?><div class="hl-card" style="text-align:center;color:#64748b"><p>No incidents reported.</p></div><?php endif; ?>

    <!-- ═══ TAB: BORDERS ═══ -->
    <?php elseif ($tab === 'borders'): ?>
        <h4 style="color:#f1f5f9;font-size:1rem;margin-bottom:.75rem"><i class="fas fa-border-all"></i> Border Access Points</h4>
        <?php
        $typeIcons = ['login'=>'fa-right-to-bracket','api'=>'fa-code','webhook'=>'fa-link','external'=>'fa-globe'];
        foreach ($borders as $bp): ?>
            <div class="hl-card">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div><i class="fas <?= $typeIcons[$bp['access_type']] ?>" style="color:<?= $tlColor ?>"></i> <strong style="color:#f1f5f9"><?= htmlspecialchars($bp['access_point']) ?></strong>
                        <span class="hl-badge" style="background:#3b82f620;color:#3b82f6;margin-left:.5rem"><?= strtoupper($bp['access_type']) ?></span>
                    </div>
                    <span class="hl-badge" style="background:<?= $bp['monitored']?'#22c55e20':'#ef444420' ?>;color:<?= $bp['monitored']?'#22c55e':'#ef4444' ?>"><?= $bp['monitored'] ? '🟢 MONITORED' : '🔴 UNMONITORED' ?></span>
                </div>
                <?php if ($bp['restrictions']): ?><div style="color:#f59e0b;font-size:.8rem;margin-top:.25rem"><i class="fas fa-ban"></i> <?= htmlspecialchars($bp['restrictions']) ?></div><?php endif; ?>
                <div style="color:#64748b;font-size:.75rem;margin-top:.25rem">Last audit: <?= date('M j, Y H:i', strtotime($bp['last_audit_at'])) ?></div>
                <?php if ($isOfficer): ?>
                    <form method="POST" style="margin-top:.5rem;display:flex;gap:.5rem;align-items:center;flex-wrap:wrap">
                        <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="audit_border"><input type="hidden" name="border_id" value="<?= $bp['id'] ?>">
                        <label style="color:#94a3b8;font-size:.8rem"><input type="checkbox" name="border_monitored" value="1" <?= $bp['monitored'] ? 'checked' : '' ?>> Monitored</label>
                        <input type="text" name="border_restrictions" class="hl-input" style="flex:1;min-width:120px" placeholder="Restrictions..." value="<?= htmlspecialchars($bp['restrictions'] ?? '') ?>">
                        <button class="hl-btn-sm hl-btn hl-btn-blue"><i class="fas fa-clipboard-check"></i> Audit</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

    <!-- ═══ TAB: EMERGENCIES ═══ -->
    <?php elseif ($tab === 'emergencies'): ?>
        <?php if ($isCommander): ?>
            <div style="margin-bottom:1rem"><button class="hl-btn hl-btn-red" onclick="document.getElementById('modalEmergency').classList.add('open')"><i class="fas fa-siren-on"></i> Declare Emergency</button></div>
        <?php endif; ?>
        <?php
        $emColors = ['declared'=>'#f59e0b','active'=>'#ef4444','contained'=>'#f97316','resolved'=>'#22c55e'];
        $emSevColors = ['local'=>'#3b82f6','regional'=>'#f97316','national'=>'#ef4444'];
        foreach ($emergencies as $em): ?>
            <div class="hl-card" style="border:2px solid <?= $emColors[$em['status']] ?>40">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div>
                        <strong style="color:#ef4444;font-size:.9rem"><?= htmlspecialchars($em['emergency_code']) ?></strong>
                        <span class="hl-badge" style="background:<?= $emSevColors[$em['severity']] ?>20;color:<?= $emSevColors[$em['severity']] ?>;margin-left:.5rem"><?= strtoupper($em['severity']) ?></span>
                        <span style="color:#94a3b8;font-size:.8rem;margin-left:.5rem"><?= strtoupper(str_replace('_', ' ', $em['emergency_type'])) ?></span>
                    </div>
                    <span class="hl-badge" style="background:<?= $emColors[$em['status']] ?>20;color:<?= $emColors[$em['status']] ?>;border:1px solid <?= $emColors[$em['status']] ?>40;font-size:.8rem"><?= strtoupper($em['status']) ?></span>
                </div>
                <?php if ($em['protocols_activated']): ?><div style="color:#f59e0b;font-size:.8rem;margin-top:.5rem"><i class="fas fa-bolt"></i> <strong>Protocols:</strong> <?= htmlspecialchars($em['protocols_activated']) ?></div><?php endif; ?>
                <div style="color:#64748b;font-size:.75rem;margin-top:.25rem">
                    Declared by: <?= htmlspecialchars($em['declarer_name'] ?? 'Unknown') ?> &bull; <?= date('M j, Y H:i', strtotime($em['declared_at'])) ?>
                    <?php if ($em['resolved_at']): ?>&bull; Resolved: <?= date('M j, Y H:i', strtotime($em['resolved_at'])) ?><?php endif; ?>
                </div>
                <?php if (in_array($em['status'], ['declared','active','contained'])): ?>
                    <div style="margin-top:.5rem;display:flex;gap:.5rem;flex-wrap:wrap">
                        <?php if ($isFlag): ?>
                            <form method="POST" style="display:flex;gap:.5rem;align-items:center"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="activate_protocol"><input type="hidden" name="em_id" value="<?= $em['id'] ?>"><input type="text" name="protocol_name" class="hl-input" style="width:200px" placeholder="Protocol name..." required><button class="hl-btn-sm hl-btn" style="background:#f97316"><i class="fas fa-bolt"></i> Activate</button></form>
                        <?php endif; ?>
                        <?php if ($isCommander): ?>
                            <form method="POST" style="display:inline"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="resolve_emergency"><input type="hidden" name="em_id" value="<?= $em['id'] ?>"><button class="hl-btn-sm hl-btn hl-btn-green"><i class="fas fa-check-double"></i> Resolve</button></form>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if (empty($emergencies)): ?><div class="hl-card" style="text-align:center;color:#64748b"><p>No emergencies declared.</p></div><?php endif; ?>
    <?php endif; ?>
</div>
</div>

<!-- Modals -->
<div class="hl-modal-bg" id="modalWatchlist"><div class="hl-modal"><h3><i class="fas fa-eye"></i> Add to Watchlist</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="add_watchlist">
<div class="hl-form-row"><label class="hl-label">Target Client ID</label><input type="number" name="target_id" class="hl-input" required min="1"></div>
<div class="hl-form-row"><label class="hl-label">Watch Level</label><select name="watch_level" class="hl-select"><option value="monitoring">Monitoring</option><option value="poi">Person of Interest</option><option value="suspect">Suspect</option><option value="known_threat">Known Threat</option><option value="most_wanted">Most Wanted</option></select></div>
<div class="hl-form-row"><label class="hl-label">Threat Type</label><input type="text" name="threat_type" class="hl-input" placeholder="e.g. Data Exfiltration, Impersonation..."></div>
<div class="hl-form-row"><label class="hl-label">Evidence</label><textarea name="evidence" class="hl-textarea" placeholder="Supporting evidence..."></textarea></div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="hl-btn hl-btn-outline" onclick="this.closest('.hl-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="hl-btn hl-btn-red"><i class="fas fa-eye"></i> Add</button></div></form></div></div>

<div class="hl-modal-bg" id="modalIncident"><div class="hl-modal"><h3><i class="fas fa-triangle-exclamation"></i> Report Incident</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="report_incident">
<div class="hl-form-row"><label class="hl-label">Incident Type</label><select name="incident_type" class="hl-select"><option value="intrusion">Intrusion</option><option value="subversion">Subversion</option><option value="coordinated_attack">Coordinated Attack</option><option value="data_breach">Data Breach</option><option value="impersonation">Impersonation</option></select></div>
<div class="hl-form-row"><label class="hl-label">Severity</label><select name="severity" class="hl-select"><option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option><option value="critical">Critical</option></select></div>
<div class="hl-form-row"><label class="hl-label">Description</label><textarea name="inc_desc" class="hl-textarea" required placeholder="Describe the incident..."></textarea></div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="hl-btn hl-btn-outline" onclick="this.closest('.hl-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="hl-btn"><i class="fas fa-paper-plane"></i> Report</button></div></form></div></div>

<div class="hl-modal-bg" id="modalEmergency"><div class="hl-modal"><h3><i class="fas fa-siren-on"></i> Declare Emergency</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="declare_emergency">
<div style="background:#ef444410;border:1px solid #ef444430;border-radius:6px;padding:.75rem;margin-bottom:1rem;font-size:.8rem;color:#fca5a5"><i class="fas fa-exclamation-triangle"></i> Commander-only action. Activates emergency protocols across all departments.</div>
<div class="hl-form-row"><label class="hl-label">Emergency Type</label><select name="emergency_type" class="hl-select"><option value="system_failure">System Failure</option><option value="attack">Attack</option><option value="natural_disaster">Natural Disaster</option><option value="civil_unrest">Civil Unrest</option><option value="pandemic">Pandemic</option></select></div>
<div class="hl-form-row"><label class="hl-label">Severity</label><select name="em_severity" class="hl-select"><option value="local">Local</option><option value="regional">Regional</option><option value="national">National</option></select></div>
<div class="hl-form-row"><label class="hl-label">Initial Protocols</label><textarea name="protocols" class="hl-textarea" style="min-height:60px" placeholder="Protocols to activate immediately..."></textarea></div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="hl-btn hl-btn-outline" onclick="this.closest('.hl-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="hl-btn hl-btn-red"><i class="fas fa-siren-on"></i> Declare</button></div></form></div></div>

<script>
document.querySelectorAll('.hl-modal-bg').forEach(bg => { bg.addEventListener('click', e => { if (e.target === bg) bg.classList.remove('open'); }); });
</script>
<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
