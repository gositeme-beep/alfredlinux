<?php
/**
 * ═══════════════════════════════════════════
 *  National Guard & Reserve Forces — Level 6: Sovereign State
 * ═══════════════════════════════════════════
 */
require_once __DIR__ . '/includes/db-config.inc.php';
$db = getSharedDB();
require_once __DIR__ . '/includes/auth-gate.inc.php';
require_once __DIR__ . '/includes/rank-guard.inc.php';
if (empty($_SESSION['csrf_ng'])) $_SESSION['csrf_ng'] = bin2hex(random_bytes(32));
requireRank(2);

$isCommander = ($clientId === 33);
$isFlag      = ($userRankTier >= 9) || $isCommander;
$isOfficer   = ($userRankTier >= 6) || $isCommander;
$isNCO       = ($userRankTier >= 4) || $isCommander;
$isCorporal  = ($userRankTier >= 2) || $isCommander;
$msg = '';
$msgType = '';

// ── Auto-create tables ──
$db->exec("CREATE TABLE IF NOT EXISTS guard_units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unit_code VARCHAR(20) NOT NULL,
    unit_name VARCHAR(120) NOT NULL,
    territory_zone VARCHAR(100) DEFAULT NULL,
    commander_id INT DEFAULT NULL,
    max_members INT DEFAULT 50,
    status ENUM('active','standby','mobilized','disbanded') DEFAULT 'standby',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS guard_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unit_id INT NOT NULL,
    client_id INT NOT NULL,
    guard_rank VARCHAR(40) DEFAULT 'guardsman',
    status ENUM('active','reserve','deployed','inactive') DEFAULT 'active',
    enlisted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_drill TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS guard_activations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unit_id INT NOT NULL,
    activation_type ENUM('local','regional','national') DEFAULT 'local',
    activated_by INT NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('ordered','mobilized','deployed','stood_down') DEFAULT 'ordered',
    activated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    stood_down_at TIMESTAMP NULL,
    duration_hours INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS guard_drills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unit_id INT NOT NULL,
    drill_type ENUM('monthly','annual','emergency') DEFAULT 'monthly',
    scheduled_at TIMESTAMP NOT NULL,
    completed_at TIMESTAMP NULL,
    attendance_count INT DEFAULT 0,
    notes TEXT DEFAULT NULL,
    conducted_by INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS reserve_roster (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL UNIQUE,
    reserve_category ENUM('ready','standby','retired') DEFAULT 'ready',
    original_rank VARCHAR(40) DEFAULT NULL,
    last_active_date TIMESTAMP NULL,
    reactivation_priority INT DEFAULT 50
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Seed starting units ──
$uc = $db->query("SELECT COUNT(*) FROM guard_units")->fetchColumn();
if ($uc == 0) {
    $units = [
        ['NG-1ST', '1st Guard Battalion — Alpha', 'Core Territory'],
        ['NG-2ND', '2nd Guard Battalion — Bravo', 'Eastern Frontier'],
        ['NG-3RD', '3rd Guard Battalion — Charlie', 'Western Frontier'],
        ['NG-RES', 'Reserve Support Company', 'Rear Echelon']
    ];
    $us = $db->prepare("INSERT INTO guard_units (unit_code, unit_name, territory_zone) VALUES (?,?,?)");
    foreach ($units as $u) $us->execute($u);
}

$csrf = $_SESSION['csrf_ng'];

// ── Check membership ──
$memCheck = $db->prepare("SELECT gm.*, gu.unit_name, gu.unit_code FROM guard_members gm JOIN guard_units gu ON gu.id = gm.unit_id WHERE gm.client_id = ? AND gm.status IN ('active','deployed')");
$memCheck->execute([$clientId]);
$myMembership = $memCheck->fetch(PDO::FETCH_ASSOC);

// ── POST Handlers ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, $_POST['csrf'] ?? '')) {
        $msg = 'Invalid security token.'; $msgType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'enlist_guard' && $isCorporal && !$myMembership) {
            $unitId = (int)($_POST['unit_id'] ?? 0);
            $unit = $db->prepare("SELECT * FROM guard_units WHERE id = ? AND status != 'disbanded'");
            $unit->execute([$unitId]);
            $unitRow = $unit->fetch(PDO::FETCH_ASSOC);
            if (!$unitRow) {
                $msg = 'Unit not found.'; $msgType = 'error';
            } else {
                $mc = $db->prepare("SELECT COUNT(*) FROM guard_members WHERE unit_id = ? AND status IN ('active','deployed')");
                $mc->execute([$unitId]);
                if ($mc->fetchColumn() >= $unitRow['max_members']) {
                    $msg = 'Unit at capacity.'; $msgType = 'error';
                } else {
                    $gRank = $isOfficer ? 'guard_officer' : ($isNCO ? 'guard_sergeant' : 'guardsman');
                    $db->prepare("INSERT INTO guard_members (unit_id, client_id, guard_rank) VALUES (?,?,?)")->execute([$unitId, $clientId, $gRank]);
                    awardXP($clientId, 'guard_enlisted', ['unit' => $unitRow['unit_code']]);
                    $msg = "Enlisted in <strong>" . htmlspecialchars($unitRow['unit_name']) . "</strong>."; $msgType = 'success';
                }
            }
        } elseif ($action === 'schedule_drill' && $isNCO) {
            $unitId   = (int)($_POST['unit_id'] ?? 0);
            $drillType = $_POST['drill_type'] ?? 'monthly';
            $schedDate = $_POST['sched_date'] ?? '';
            $validDT = ['monthly','annual','emergency'];
            if ($unitId < 1 || !in_array($drillType, $validDT, true) || $schedDate === '') {
                $msg = 'All fields required.'; $msgType = 'error';
            } else {
                $db->prepare("INSERT INTO guard_drills (unit_id, drill_type, scheduled_at, conducted_by) VALUES (?,?,?,?)")
                   ->execute([$unitId, $drillType, $schedDate, $clientId]);
                $msg = strtoupper($drillType) . " drill scheduled."; $msgType = 'success';
            }
        } elseif ($action === 'complete_drill' && $isNCO) {
            $drillId   = (int)($_POST['drill_id'] ?? 0);
            $attendance = (int)($_POST['attendance'] ?? 0);
            $notes     = trim($_POST['drill_notes'] ?? '');
            $stmt = $db->prepare("UPDATE guard_drills SET completed_at = NOW(), attendance_count = ?, notes = ? WHERE id = ? AND completed_at IS NULL");
            $stmt->execute([$attendance, $notes, $drillId]);
            if ($stmt->rowCount()) {
                awardXP($clientId, 'drill_completed', ['attendance' => $attendance]);
                $msg = "Drill completed. $attendance members attended."; $msgType = 'success';
            } else { $msg = 'Drill not found.'; $msgType = 'error'; }

        } elseif ($action === 'activate_unit' && $isOfficer) {
            $unitId = (int)($_POST['unit_id'] ?? 0);
            $actType = $_POST['activation_type'] ?? 'local';
            $reason  = trim($_POST['act_reason'] ?? '');
            $validAT = ['local','regional','national'];
            if ($actType === 'national' && !$isCommander) {
                $msg = 'National activation requires Commander authority.'; $msgType = 'error';
            } elseif (!in_array($actType, $validAT, true) || $reason === '') {
                $msg = 'Activation type and reason required.'; $msgType = 'error';
            } else {
                $db->prepare("UPDATE guard_units SET status = 'mobilized' WHERE id = ?")->execute([$unitId]);
                $db->prepare("INSERT INTO guard_activations (unit_id, activation_type, activated_by, reason) VALUES (?,?,?,?)")
                   ->execute([$unitId, $actType, $clientId, $reason]);
                awardXP($clientId, 'unit_activated', ['type' => $actType]);
                $msg = strtoupper($actType) . " activation ordered."; $msgType = 'success';
            }
        } elseif ($action === 'stand_down' && $isOfficer) {
            $actId = (int)($_POST['act_id'] ?? 0);
            $act = $db->prepare("SELECT activated_at FROM guard_activations WHERE id = ? AND status IN ('ordered','mobilized','deployed')");
            $act->execute([$actId]);
            $actRow = $act->fetch(PDO::FETCH_ASSOC);
            if ($actRow) {
                $hours = max(1, (int)((time() - strtotime($actRow['activated_at'])) / 3600));
                $db->prepare("UPDATE guard_activations SET status = 'stood_down', stood_down_at = NOW(), duration_hours = ? WHERE id = ?")->execute([$hours, $actId]);
                $unitId = $db->prepare("SELECT unit_id FROM guard_activations WHERE id = ?");
                $unitId->execute([$actId]);
                $uid = $unitId->fetchColumn();
                if ($uid) $db->prepare("UPDATE guard_units SET status = 'standby' WHERE id = ?")->execute([$uid]);
                $msg = "Unit stood down after $hours hour(s)."; $msgType = 'success';
            } else { $msg = 'Activation not found.'; $msgType = 'error'; }

        } elseif ($action === 'transfer_reserve') {
            $category = $_POST['reserve_cat'] ?? 'ready';
            $validRC = ['ready','standby','retired'];
            if (!in_array($category, $validRC, true)) {
                $msg = 'Invalid category.'; $msgType = 'error';
            } else {
                if ($myMembership) {
                    $db->prepare("UPDATE guard_members SET status = 'reserve' WHERE client_id = ? AND status = 'active'")->execute([$clientId]);
                }
                $db->prepare("INSERT INTO reserve_roster (client_id, reserve_category, original_rank, last_active_date, reactivation_priority) VALUES (?,?,?,NOW(),?) ON DUPLICATE KEY UPDATE reserve_category = ?, last_active_date = NOW()")
                   ->execute([$clientId, $category, $myMembership['guard_rank'] ?? 'guardsman', $category === 'ready' ? 80 : ($category === 'standby' ? 50 : 10), $category]);
                $msg = "Transferred to <strong>" . strtoupper($category) . "</strong> reserve."; $msgType = 'success';
            }
        } elseif ($action === 'reactivate' && $isOfficer) {
            $resId    = (int)($_POST['res_id'] ?? 0);
            $unitId   = (int)($_POST['unit_id'] ?? 0);
            $res = $db->prepare("SELECT * FROM reserve_roster WHERE id = ?");
            $res->execute([$resId]);
            $resRow = $res->fetch(PDO::FETCH_ASSOC);
            if ($resRow && $unitId > 0) {
                $db->prepare("INSERT INTO guard_members (unit_id, client_id, guard_rank, status) VALUES (?,?,?,'active')")
                   ->execute([$unitId, $resRow['client_id'], $resRow['original_rank'] ?? 'guardsman']);
                $db->prepare("DELETE FROM reserve_roster WHERE id = ?")->execute([$resId]);
                $msg = "Reserve member reactivated."; $msgType = 'success';
            } else { $msg = 'Not found.'; $msgType = 'error'; }

        } elseif ($action === 'create_unit' && $isFlag) {
            $unitName = trim($_POST['unit_name'] ?? '');
            $zone     = trim($_POST['unit_zone'] ?? '');
            $maxMem   = (int)($_POST['max_members'] ?? 50);
            if ($unitName === '') {
                $msg = 'Unit name required.'; $msgType = 'error';
            } else {
                $code = 'NG-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
                $db->prepare("INSERT INTO guard_units (unit_code, unit_name, territory_zone, commander_id, max_members) VALUES (?,?,?,?,?)")
                   ->execute([$code, $unitName, $zone, $clientId, max(10, $maxMem)]);
                $msg = "Unit <strong>$code</strong> created."; $msgType = 'success';
            }
        } else {
            $msg = 'Unauthorized or unknown action.'; $msgType = 'error';
        }
    }
    $_SESSION['csrf_ng'] = bin2hex(random_bytes(32));
    $csrf = $_SESSION['csrf_ng'];
}

// ── Data Fetch ──
$tab = $_GET['tab'] ?? 'units';
$units = $db->query("SELECT gu.*, CONCAT(c.firstname,' ',c.lastname) AS cmd_name, (SELECT COUNT(*) FROM guard_members gm WHERE gm.unit_id = gu.id AND gm.status IN ('active','deployed')) AS member_count FROM guard_units gu LEFT JOIN tblclients c ON c.id = gu.commander_id ORDER BY gu.unit_code")->fetchAll(PDO::FETCH_ASSOC);
$drills = $db->query("SELECT gd.*, gu.unit_name, gu.unit_code, CONCAT(c.firstname,' ',c.lastname) AS conductor_name FROM guard_drills gd JOIN guard_units gu ON gu.id = gd.unit_id LEFT JOIN tblclients c ON c.id = gd.conducted_by ORDER BY gd.scheduled_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$activations = $db->query("SELECT ga.*, gu.unit_name, gu.unit_code, CONCAT(c.firstname,' ',c.lastname) AS activator_name FROM guard_activations ga JOIN guard_units gu ON gu.id = ga.unit_id LEFT JOIN tblclients c ON c.id = ga.activated_by ORDER BY ga.activated_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$reserves = $db->query("SELECT rr.*, CONCAT(c.firstname,' ',c.lastname) AS member_name FROM reserve_roster rr LEFT JOIN tblclients c ON c.id = rr.client_id ORDER BY rr.reactivation_priority DESC")->fetchAll(PDO::FETCH_ASSOC);
$totalGuard = $db->query("SELECT COUNT(*) FROM guard_members WHERE status IN ('active','deployed')")->fetchColumn();
$mobilized = count(array_filter($units, fn($u) => $u['status'] === 'mobilized'));
$pendingDrills = count(array_filter($drills, fn($d) => $d['completed_at'] === null));

$pageTitle = 'National Guard & Reserve Forces';
include __DIR__ . '/includes/site-header.inc.php';
?>
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<style>
.ng-wrap{max-width:1100px;margin:0 auto;padding:1.5rem}
.ng-bg{background:#0a0a14;min-height:100vh;color:#e2e8f0}
.ng-card{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:10px;padding:1.25rem;margin-bottom:1rem}
.ng-card:hover{border-color:#059669;box-shadow:0 0 12px rgba(5,150,105,.12)}
.ng-title{font-size:1.6rem;font-weight:700;color:#f1f5f9;margin-bottom:.25rem;display:flex;align-items:center;gap:.5rem}
.ng-sub{color:#94a3b8;font-size:.85rem}
.ng-badge{display:inline-block;padding:2px 10px;border-radius:999px;font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
.ng-tabs{display:flex;gap:.5rem;margin-bottom:1.25rem;flex-wrap:wrap}
.ng-tab{padding:.4rem 1rem;border-radius:6px;cursor:pointer;font-size:.8rem;background:#2a2a4a;color:#94a3b8;text-decoration:none;font-weight:600;border:none}
.ng-tab.active{background:#059669;color:#fff}
.ng-stat-bar{display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem}
.ng-stat{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:8px;padding:.75rem 1.25rem;flex:1;min-width:120px;text-align:center}
.ng-stat .val{font-size:1.5rem;font-weight:700;color:#059669}
.ng-stat .lbl{font-size:.7rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px}
.ng-btn{background:#059669;color:#fff;border:none;padding:.5rem 1.25rem;border-radius:6px;cursor:pointer;font-size:.85rem;font-weight:600;transition:.2s}
.ng-btn:hover{background:#047857}
.ng-btn-sm{padding:.3rem .75rem;font-size:.75rem}
.ng-btn-outline{background:transparent;border:1px solid #059669;color:#059669}
.ng-btn-outline:hover{background:#059669;color:#fff}
.ng-btn-red{background:#ef4444;color:#fff}.ng-btn-red:hover{background:#dc2626}
.ng-btn-blue{background:#3b82f6;color:#fff}.ng-btn-blue:hover{background:#2563eb}
.ng-btn-gold{background:#d4a017;color:#000}.ng-btn-gold:hover{background:#e2b340}
.ng-input,.ng-select,.ng-textarea{width:100%;background:#0a0a14;border:1px solid #2a2a4a;color:#e2e8f0;padding:.5rem .75rem;border-radius:6px;font-size:.85rem;box-sizing:border-box}
.ng-textarea{min-height:80px;resize:vertical}
.ng-label{display:block;color:#94a3b8;font-size:.75rem;margin-bottom:.25rem;text-transform:uppercase;letter-spacing:.5px}
.ng-msg{padding:.75rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:.85rem}
.ng-msg-success{background:rgba(5,150,105,.12);border:1px solid #059669;color:#6ee7b7}
.ng-msg-error{background:rgba(239,68,68,.12);border:1px solid #ef4444;color:#fca5a5}
.ng-modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:900;align-items:center;justify-content:center}
.ng-modal-bg.open{display:flex}
.ng-modal{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:12px;padding:1.5rem;width:90%;max-width:580px;max-height:80vh;overflow-y:auto}
.ng-modal h3{color:#f1f5f9;margin:0 0 1rem}
.ng-form-row{margin-bottom:.75rem}
.ng-row{display:flex;align-items:center;gap:1rem;padding:.75rem 1rem;background:#0a0a14;border:1px solid #2a2a4a;border-radius:8px;margin-bottom:.5rem}
.ng-cap-bar{height:8px;background:#2a2a4a;border-radius:4px;overflow:hidden;margin-top:.25rem}
.ng-cap-fill{height:100%;border-radius:4px;transition:width .3s}
</style>
<div class="ng-bg">
<div class="ng-wrap">
    <div class="ng-title"><i class="fas fa-shield-dog"></i> National Guard & Reserve Forces</div>
    <p class="ng-sub" style="margin-bottom:1.25rem">Territory defense, unit management, drills, activations, and reserve roster — Corporal+ rank</p>

    <?php if ($msg): ?><div class="ng-msg ng-msg-<?= $msgType ?>"><?= $msg ?></div><?php endif; ?>

    <?php if ($myMembership): ?>
        <div class="ng-card" style="border-left:4px solid #059669;display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
            <i class="fas fa-id-card-clip" style="font-size:2.5rem;color:#059669"></i>
            <div>
                <div style="color:#059669;font-size:.8rem;font-weight:600"><?= htmlspecialchars($myMembership['unit_code']) ?></div>
                <div style="color:#f1f5f9;font-size:.95rem;font-weight:600"><?= strtoupper(str_replace('_', ' ', $myMembership['guard_rank'])) ?></div>
                <div style="font-size:.8rem;color:#94a3b8"><?= htmlspecialchars($myMembership['unit_name']) ?> &bull; <?= strtoupper($myMembership['status']) ?></div>
            </div>
        </div>
    <?php elseif ($isCorporal): ?>
        <div class="ng-card" style="text-align:center">
            <p style="color:#94a3b8;margin-bottom:.5rem">You are eligible to enlist in the National Guard. Choose a unit below.</p>
        </div>
    <?php endif; ?>

    <div class="ng-stat-bar">
        <div class="ng-stat"><div class="val"><?= count($units) ?></div><div class="lbl">Units</div></div>
        <div class="ng-stat"><div class="val" style="color:#22c55e"><?= $totalGuard ?></div><div class="lbl">Active Guard</div></div>
        <div class="ng-stat"><div class="val" style="color:#f59e0b"><?= $mobilized ?></div><div class="lbl">Mobilized</div></div>
        <div class="ng-stat"><div class="val" style="color:#3b82f6"><?= $pendingDrills ?></div><div class="lbl">Pending Drills</div></div>
        <div class="ng-stat"><div class="val" style="color:#94a3b8"><?= count($reserves) ?></div><div class="lbl">Reserves</div></div>
    </div>

    <div class="ng-tabs">
        <a href="?tab=units" class="ng-tab <?= $tab==='units'?'active':'' ?>"><i class="fas fa-people-group"></i> Units</a>
        <a href="?tab=drills" class="ng-tab <?= $tab==='drills'?'active':'' ?>"><i class="fas fa-crosshairs"></i> Drills</a>
        <a href="?tab=activations" class="ng-tab <?= $tab==='activations'?'active':'' ?>"><i class="fas fa-bolt"></i> Activations</a>
        <a href="?tab=reserves" class="ng-tab <?= $tab==='reserves'?'active':'' ?>"><i class="fas fa-user-clock"></i> Reserves</a>
    </div>

    <!-- ═══ TAB: UNITS ═══ -->
    <?php if ($tab === 'units'): ?>
        <?php if ($isFlag): ?>
            <div style="margin-bottom:1rem"><button class="ng-btn ng-btn-gold" onclick="document.getElementById('modalCreateUnit').classList.add('open')"><i class="fas fa-plus"></i> Create Unit</button></div>
        <?php endif; ?>
        <?php
        $statusColors = ['active'=>'#22c55e','standby'=>'#3b82f6','mobilized'=>'#f59e0b','disbanded'=>'#64748b'];
        foreach ($units as $u):
            $capPct = $u['max_members'] > 0 ? round(($u['member_count'] / $u['max_members']) * 100) : 0;
            $capColor = $capPct >= 90 ? '#ef4444' : ($capPct >= 60 ? '#f59e0b' : '#22c55e');
        ?>
            <div class="ng-card">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div>
                        <strong style="color:#059669;font-size:.85rem"><?= htmlspecialchars($u['unit_code']) ?></strong>
                        <strong style="color:#f1f5f9;margin-left:.5rem"><?= htmlspecialchars($u['unit_name']) ?></strong>
                    </div>
                    <span class="ng-badge" style="background:<?= $statusColors[$u['status']] ?>20;color:<?= $statusColors[$u['status']] ?>;border:1px solid <?= $statusColors[$u['status']] ?>40"><?= strtoupper($u['status']) ?></span>
                </div>
                <div style="color:#94a3b8;font-size:.8rem;margin-top:.25rem">
                    Zone: <?= htmlspecialchars($u['territory_zone'] ?? 'Unassigned') ?>
                    &bull; CO: <?= htmlspecialchars($u['cmd_name'] ?? 'None') ?>
                    &bull; Strength: <?= $u['member_count'] ?>/<?= $u['max_members'] ?>
                </div>
                <div class="ng-cap-bar"><div class="ng-cap-fill" style="width:<?= $capPct ?>%;background:<?= $capColor ?>"></div></div>
                <div style="margin-top:.5rem;display:flex;gap:.5rem;flex-wrap:wrap">
                    <?php if ($isCorporal && !$myMembership && $u['status'] !== 'disbanded'): ?>
                        <form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="enlist_guard"><input type="hidden" name="unit_id" value="<?= $u['id'] ?>"><button class="ng-btn-sm ng-btn"><i class="fas fa-user-plus"></i> Enlist</button></form>
                    <?php endif; ?>
                    <?php if ($isOfficer && $u['status'] !== 'disbanded' && $u['status'] !== 'mobilized'): ?>
                        <button class="ng-btn-sm ng-btn ng-btn-red" onclick="openActivate(<?= $u['id'] ?>,'<?= htmlspecialchars($u['unit_code'], ENT_QUOTES) ?>')"><i class="fas fa-bolt"></i> Activate</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

    <!-- ═══ TAB: DRILLS ═══ -->
    <?php elseif ($tab === 'drills'): ?>
        <?php if ($isNCO): ?>
            <div style="margin-bottom:1rem"><button class="ng-btn" onclick="document.getElementById('modalDrill').classList.add('open')"><i class="fas fa-crosshairs"></i> Schedule Drill</button></div>
        <?php endif; ?>
        <?php
        $drillColors = ['monthly'=>'#3b82f6','annual'=>'#d4a017','emergency'=>'#ef4444'];
        foreach ($drills as $dr): ?>
            <div class="ng-card" style="border-left:3px solid <?= $drillColors[$dr['drill_type']] ?>">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div>
                        <span class="ng-badge" style="background:<?= $drillColors[$dr['drill_type']] ?>20;color:<?= $drillColors[$dr['drill_type']] ?>"><?= strtoupper($dr['drill_type']) ?></span>
                        <strong style="color:#f1f5f9;margin-left:.5rem"><?= htmlspecialchars($dr['unit_name']) ?></strong>
                        <span style="color:#94a3b8;font-size:.8rem">(<?= htmlspecialchars($dr['unit_code']) ?>)</span>
                    </div>
                    <span class="ng-badge" style="background:<?= $dr['completed_at'] ? '#22c55e20' : '#f59e0b20' ?>;color:<?= $dr['completed_at'] ? '#22c55e' : '#f59e0b' ?>"><?= $dr['completed_at'] ? 'COMPLETED' : 'SCHEDULED' ?></span>
                </div>
                <div style="color:#94a3b8;font-size:.8rem;margin-top:.25rem">
                    Scheduled: <?= date('M j, Y H:i', strtotime($dr['scheduled_at'])) ?>
                    &bull; By: <?= htmlspecialchars($dr['conductor_name'] ?? 'Unknown') ?>
                    <?php if ($dr['completed_at']): ?>
                        &bull; Completed: <?= date('M j H:i', strtotime($dr['completed_at'])) ?> &bull; Attendance: <?= (int)$dr['attendance_count'] ?>
                    <?php endif; ?>
                </div>
                <?php if ($dr['notes']): ?><p style="color:#cbd5e1;font-size:.85rem;margin-top:.5rem"><?= htmlspecialchars($dr['notes']) ?></p><?php endif; ?>
                <?php if (!$dr['completed_at'] && $isNCO): ?>
                    <form method="POST" style="margin-top:.5rem;display:flex;gap:.5rem;align-items:center;flex-wrap:wrap">
                        <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="complete_drill"><input type="hidden" name="drill_id" value="<?= $dr['id'] ?>">
                        <div><label class="ng-label">Attendance</label><input type="number" name="attendance" class="ng-input" style="width:80px" min="0" value="0"></div>
                        <div style="flex:1"><label class="ng-label">Notes</label><input type="text" name="drill_notes" class="ng-input" placeholder="Summary..."></div>
                        <button class="ng-btn-sm ng-btn" style="margin-top:1rem"><i class="fas fa-check"></i> Complete</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if (empty($drills)): ?><div class="ng-card" style="text-align:center;color:#64748b"><p>No drills scheduled.</p></div><?php endif; ?>

    <!-- ═══ TAB: ACTIVATIONS ═══ -->
    <?php elseif ($tab === 'activations'): ?>
        <?php
        $actColors = ['ordered'=>'#f59e0b','mobilized'=>'#ef4444','deployed'=>'#3b82f6','stood_down'=>'#64748b'];
        $actTypeColors = ['local'=>'#22c55e','regional'=>'#f97316','national'=>'#ef4444'];
        foreach ($activations as $act): ?>
            <div class="ng-card" style="border-left:3px solid <?= $actTypeColors[$act['activation_type']] ?>">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div>
                        <span class="ng-badge" style="background:<?= $actTypeColors[$act['activation_type']] ?>20;color:<?= $actTypeColors[$act['activation_type']] ?>;border:1px solid <?= $actTypeColors[$act['activation_type']] ?>40"><?= strtoupper($act['activation_type']) ?></span>
                        <strong style="color:#f1f5f9;margin-left:.5rem"><?= htmlspecialchars($act['unit_name']) ?></strong>
                    </div>
                    <span class="ng-badge" style="background:<?= $actColors[$act['status']] ?>20;color:<?= $actColors[$act['status']] ?>"><?= strtoupper($act['status']) ?></span>
                </div>
                <p style="color:#cbd5e1;font-size:.85rem;margin-top:.5rem"><?= htmlspecialchars($act['reason']) ?></p>
                <div style="color:#64748b;font-size:.75rem;margin-top:.25rem">
                    By: <?= htmlspecialchars($act['activator_name'] ?? 'Unknown') ?> &bull; <?= date('M j H:i', strtotime($act['activated_at'])) ?>
                    <?php if ($act['stood_down_at']): ?>&bull; Stood down: <?= date('M j H:i', strtotime($act['stood_down_at'])) ?> (<?= (int)$act['duration_hours'] ?>h)<?php endif; ?>
                </div>
                <?php if (in_array($act['status'], ['ordered','mobilized','deployed']) && $isOfficer): ?>
                    <form method="POST" style="margin-top:.5rem"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="stand_down"><input type="hidden" name="act_id" value="<?= $act['id'] ?>"><button class="ng-btn-sm ng-btn" style="background:#22c55e"><i class="fas fa-flag-checkered"></i> Stand Down</button></form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if (empty($activations)): ?><div class="ng-card" style="text-align:center;color:#64748b"><p>No activation records.</p></div><?php endif; ?>

    <!-- ═══ TAB: RESERVES ═══ -->
    <?php elseif ($tab === 'reserves'): ?>
        <?php if ($myMembership && $myMembership['status'] === 'active'): ?>
            <div class="ng-card" style="text-align:center">
                <p style="color:#94a3b8;margin-bottom:.5rem">Transfer to reserve status:</p>
                <form method="POST" style="display:flex;gap:.5rem;justify-content:center;flex-wrap:wrap">
                    <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="transfer_reserve">
                    <select name="reserve_cat" class="ng-select" style="width:auto"><option value="ready">Ready Reserve (80% priority)</option><option value="standby">Standby Reserve (50%)</option><option value="retired">Retired (10%)</option></select>
                    <button class="ng-btn-sm ng-btn ng-btn-blue"><i class="fas fa-exchange-alt"></i> Transfer</button>
                </form>
            </div>
        <?php endif; ?>
        <?php
        $rcColors = ['ready'=>'#22c55e','standby'=>'#f59e0b','retired'=>'#64748b'];
        foreach ($reserves as $res): ?>
            <div class="ng-row">
                <div style="width:12px;height:12px;border-radius:50%;background:<?= $rcColors[$res['reserve_category']] ?>;flex-shrink:0"></div>
                <div style="flex:1">
                    <strong style="color:#f1f5f9"><?= htmlspecialchars($res['member_name'] ?? 'Unknown') ?></strong>
                    <span class="ng-badge" style="background:<?= $rcColors[$res['reserve_category']] ?>20;color:<?= $rcColors[$res['reserve_category']] ?>;margin-left:.5rem"><?= strtoupper($res['reserve_category']) ?></span>
                    <div style="color:#64748b;font-size:.75rem">Rank: <?= strtoupper(str_replace('_', ' ', $res['original_rank'] ?? '')) ?> &bull; Priority: <?= (int)$res['reactivation_priority'] ?>% &bull; Last active: <?= $res['last_active_date'] ? date('M j, Y', strtotime($res['last_active_date'])) : 'N/A' ?></div>
                </div>
                <?php if ($isOfficer): ?>
                    <form method="POST" style="display:flex;gap:.5rem;align-items:center">
                        <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="reactivate"><input type="hidden" name="res_id" value="<?= $res['id'] ?>">
                        <select name="unit_id" class="ng-select" style="width:auto">
                            <?php foreach ($units as $u): ?><option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['unit_code']) ?></option><?php endforeach; ?>
                        </select>
                        <button class="ng-btn-sm ng-btn"><i class="fas fa-user-check"></i> Reactivate</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if (empty($reserves)): ?><div class="ng-card" style="text-align:center;color:#64748b"><p>No reserves.</p></div><?php endif; ?>
    <?php endif; ?>
</div>
</div>

<!-- Modals -->
<div class="ng-modal-bg" id="modalCreateUnit"><div class="ng-modal"><h3><i class="fas fa-plus"></i> Create Guard Unit</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="create_unit">
<div class="ng-form-row"><label class="ng-label">Unit Name</label><input type="text" name="unit_name" class="ng-input" required placeholder="e.g. 5th Guard Battalion — Echo"></div>
<div class="ng-form-row"><label class="ng-label">Territory Zone</label><input type="text" name="unit_zone" class="ng-input" placeholder="e.g. Northern Frontier"></div>
<div class="ng-form-row"><label class="ng-label">Max Members</label><input type="number" name="max_members" class="ng-input" value="50" min="10" max="500"></div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="ng-btn ng-btn-outline" onclick="this.closest('.ng-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="ng-btn ng-btn-gold"><i class="fas fa-plus"></i> Create</button></div></form></div></div>

<div class="ng-modal-bg" id="modalDrill"><div class="ng-modal"><h3><i class="fas fa-crosshairs"></i> Schedule Drill</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="schedule_drill">
<div class="ng-form-row"><label class="ng-label">Unit</label><select name="unit_id" class="ng-select"><?php foreach ($units as $u): ?><option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['unit_code'] . ' — ' . $u['unit_name']) ?></option><?php endforeach; ?></select></div>
<div class="ng-form-row"><label class="ng-label">Drill Type</label><select name="drill_type" class="ng-select"><option value="monthly">Monthly</option><option value="annual">Annual Intensive</option><option value="emergency">Emergency</option></select></div>
<div class="ng-form-row"><label class="ng-label">Scheduled Date</label><input type="datetime-local" name="sched_date" class="ng-input" required></div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="ng-btn ng-btn-outline" onclick="this.closest('.ng-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="ng-btn"><i class="fas fa-calendar-plus"></i> Schedule</button></div></form></div></div>

<div class="ng-modal-bg" id="modalActivate"><div class="ng-modal"><h3><i class="fas fa-bolt"></i> Activate Unit</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="activate_unit"><input type="hidden" name="unit_id" id="actUnitId" value="">
<div style="color:#94a3b8;margin-bottom:1rem">Activating unit: <strong id="actUnitCode" style="color:#059669"></strong></div>
<div class="ng-form-row"><label class="ng-label">Activation Level</label><select name="activation_type" class="ng-select"><option value="local">Local (Officer auth)</option><option value="regional">Regional (Officer auth)</option><option value="national">National (Commander only)</option></select></div>
<div class="ng-form-row"><label class="ng-label">Reason</label><textarea name="act_reason" class="ng-textarea" required placeholder="Reason for activation..."></textarea></div>
<div style="background:#f59e0b10;border:1px solid #f59e0b30;border-radius:6px;padding:.75rem;margin-bottom:.75rem;font-size:.8rem;color:#fde68a"><i class="fas fa-info-circle"></i> 90-day limit without Senate authorization per Constitution.</div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="ng-btn ng-btn-outline" onclick="this.closest('.ng-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="ng-btn ng-btn-red"><i class="fas fa-bolt"></i> Activate</button></div></form></div></div>

<script>
function openActivate(id, code) { document.getElementById('actUnitId').value = id; document.getElementById('actUnitCode').textContent = code; document.getElementById('modalActivate').classList.add('open'); }
document.querySelectorAll('.ng-modal-bg').forEach(bg => { bg.addEventListener('click', e => { if (e.target === bg) bg.classList.remove('open'); }); });
</script>
<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
