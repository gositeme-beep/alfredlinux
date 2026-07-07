<?php
/**
 * ═══════════════════════════════════════════
 *  Special Operations Command (SOCOM) — Level 6: Sovereign State
 * ═══════════════════════════════════════════
 */
require_once __DIR__ . '/includes/db-config.inc.php';
$db = getSharedDB();
require_once __DIR__ . '/includes/auth-gate.inc.php';
require_once __DIR__ . '/includes/rank-guard.inc.php';
if (empty($_SESSION['csrf_so'])) $_SESSION['csrf_so'] = bin2hex(random_bytes(32));
requireRank(6);

$isCommander = ($clientId === 33);
$isFlag      = ($userRankTier >= 9) || $isCommander;
$isOfficer   = ($userRankTier >= 6) || $isCommander;
$msg = '';
$msgType = '';

// ── Auto-create tables ──
$db->exec("CREATE TABLE IF NOT EXISTS socom_units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unit_code VARCHAR(20) NOT NULL,
    unit_name VARCHAR(120) NOT NULL,
    specialty VARCHAR(100) DEFAULT NULL,
    max_operators INT DEFAULT 20,
    status ENUM('active','standby','deployed','disbanded') DEFAULT 'standby',
    commander_id INT DEFAULT NULL,
    classification ENUM('secret','top_secret','black') DEFAULT 'secret',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS socom_operators (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unit_id INT NOT NULL,
    client_id INT NOT NULL,
    callsign VARCHAR(40) DEFAULT NULL,
    status ENUM('active','reserve','kia','retired') DEFAULT 'active',
    qualification_level ENUM('candidate','operator','senior','master') DEFAULT 'candidate',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    missions_completed INT DEFAULT 0,
    success_rate DECIMAL(5,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS socom_missions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mission_code VARCHAR(20) NOT NULL,
    unit_id INT NOT NULL,
    mission_type ENUM('direct_action','recon','sabotage','extraction','assassination','cyber_infiltration','counter_espionage') DEFAULT 'direct_action',
    objective TEXT NOT NULL,
    target TEXT DEFAULT NULL,
    status ENUM('planning','briefing','deployed','in_progress','complete','failed','aborted') DEFAULT 'planning',
    classification ENUM('secret','top_secret','black') DEFAULT 'secret',
    briefed_by INT DEFAULT NULL,
    authorized_by INT DEFAULT NULL,
    start_at TIMESTAMP NULL,
    end_at TIMESTAMP NULL,
    outcome TEXT DEFAULT NULL,
    xp_reward INT DEFAULT 500
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS socom_qualifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    phase ENUM('assessment','selection','training','operational') DEFAULT 'assessment',
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    result ENUM('in_progress','passed','failed','withdrawn') DEFAULT 'in_progress',
    evaluator_id INT DEFAULT NULL,
    notes TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS socom_after_action (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mission_id INT NOT NULL,
    filed_by INT NOT NULL,
    report TEXT NOT NULL,
    lessons_learned TEXT DEFAULT NULL,
    casualties INT DEFAULT 0,
    collateral TEXT DEFAULT NULL,
    filed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Seed units ──
$uc = $db->query("SELECT COUNT(*) FROM socom_units")->fetchColumn();
if ($uc == 0) {
    $units = [
        ['DELTA-1', 'Delta Force', 'Direct Action & Counter-Terrorism', 'top_secret'],
        ['PHANTOM', 'Phantom Division', 'Deep Reconnaissance & Infiltration', 'top_secret'],
        ['NIGHTSTK', 'Night Stalkers', 'Special Aviation & Extraction', 'secret'],
        ['SENTINEL', 'Sentinel Group', 'Counter-Espionage & Intelligence', 'top_secret'],
        ['GHOST', 'Ghost Protocol', 'Black Operations — Commander Eyes Only', 'black']
    ];
    $us = $db->prepare("INSERT INTO socom_units (unit_code, unit_name, specialty, classification) VALUES (?,?,?,?)");
    foreach ($units as $u) $us->execute($u);
}

$csrf = $_SESSION['csrf_so'];

// ── Check operator status ──
$opCheck = $db->prepare("SELECT so.*, su.unit_name, su.unit_code FROM socom_operators so JOIN socom_units su ON su.id = so.unit_id WHERE so.client_id = ? AND so.status = 'active'");
$opCheck->execute([$clientId]);
$myOp = $opCheck->fetch(PDO::FETCH_ASSOC);
$isOperator = (bool)$myOp;

// ── POST Handlers ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, $_POST['csrf'] ?? '')) {
        $msg = 'Invalid security token.'; $msgType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create_unit' && $isCommander) {
            $unitName  = trim($_POST['unit_name'] ?? '');
            $specialty = trim($_POST['specialty'] ?? '');
            $class     = $_POST['unit_class'] ?? 'secret';
            $maxOps    = (int)($_POST['max_ops'] ?? 20);
            $validCL = ['secret','top_secret','black'];
            if ($unitName === '' || !in_array($class, $validCL, true)) {
                $msg = 'Unit name and valid classification required.'; $msgType = 'error';
            } else {
                $code = 'SOC-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
                $db->prepare("INSERT INTO socom_units (unit_code, unit_name, specialty, max_operators, commander_id, classification) VALUES (?,?,?,?,?,?)")
                   ->execute([$code, $unitName, $specialty, max(5, $maxOps), $clientId, $class]);
                $msg = "Unit <strong>$code</strong> created."; $msgType = 'success';
            }
        } elseif ($action === 'start_selection' && $isOfficer) {
            $targetId = (int)($_POST['target_id'] ?? $clientId);
            $existing = $db->prepare("SELECT id FROM socom_qualifications WHERE client_id = ? AND result = 'in_progress'");
            $existing->execute([$targetId]);
            if ($existing->fetch()) {
                $msg = 'Already in qualification pipeline.'; $msgType = 'error';
            } else {
                $db->prepare("INSERT INTO socom_qualifications (client_id, evaluator_id) VALUES (?,?)")->execute([$targetId, $clientId]);
                $msg = "Selection pipeline started."; $msgType = 'success';
            }
        } elseif ($action === 'advance_qualification' && $isFlag) {
            $qualId = (int)($_POST['qual_id'] ?? 0);
            $result = $_POST['qual_result'] ?? 'passed';
            $notes  = trim($_POST['qual_notes'] ?? '');
            $validQR = ['passed','failed','withdrawn'];
            $qual = $db->prepare("SELECT * FROM socom_qualifications WHERE id = ? AND result = 'in_progress'");
            $qual->execute([$qualId]);
            $qualRow = $qual->fetch(PDO::FETCH_ASSOC);
            if (!$qualRow || !in_array($result, $validQR, true)) {
                $msg = 'Qualification not found or invalid result.'; $msgType = 'error';
            } else {
                $phases = ['assessment','selection','training','operational'];
                $curIdx = array_search($qualRow['phase'], $phases);
                if ($result === 'passed' && $curIdx < 3) {
                    $nextPhase = $phases[$curIdx + 1];
                    $db->prepare("UPDATE socom_qualifications SET phase = ?, notes = ?, evaluator_id = ? WHERE id = ?")
                       ->execute([$nextPhase, $notes, $clientId, $qualId]);
                    awardXP($qualRow['client_id'], 'socom_qual_advance', ['phase' => $nextPhase]);
                    $msg = "Advanced to <strong>" . strtoupper($nextPhase) . "</strong> phase."; $msgType = 'success';
                } elseif ($result === 'passed' && $curIdx === 3) {
                    $db->prepare("UPDATE socom_qualifications SET result = 'passed', completed_at = NOW(), notes = ?, evaluator_id = ? WHERE id = ?")
                       ->execute([$notes, $clientId, $qualId]);
                    awardXP($qualRow['client_id'], 'socom_qualified', []);
                    $msg = "Qualification <strong>COMPLETE</strong>. Operator certified."; $msgType = 'success';
                } else {
                    $db->prepare("UPDATE socom_qualifications SET result = ?, completed_at = NOW(), notes = ?, evaluator_id = ? WHERE id = ?")
                       ->execute([$result, $notes, $clientId, $qualId]);
                    $msg = "Candidate " . strtoupper($result) . "."; $msgType = $result === 'failed' ? 'error' : 'success';
                }
            }
        } elseif ($action === 'invite_operator' && $isFlag) {
            $targetId = (int)($_POST['operator_id'] ?? 0);
            $unitId   = (int)($_POST['unit_id'] ?? 0);
            $callsign = trim($_POST['callsign'] ?? '');
            if ($targetId < 1 || $unitId < 1) {
                $msg = 'Operator and unit required.'; $msgType = 'error';
            } else {
                $unit = $db->prepare("SELECT * FROM socom_units WHERE id = ? AND status != 'disbanded'");
                $unit->execute([$unitId]);
                $unitRow = $unit->fetch(PDO::FETCH_ASSOC);
                if (!$unitRow) { $msg = 'Unit not found.'; $msgType = 'error'; }
                elseif ($unitRow['classification'] === 'black' && !$isCommander) { $msg = 'Black units require Commander authorization.'; $msgType = 'error'; }
                else {
                    $db->prepare("INSERT INTO socom_operators (unit_id, client_id, callsign, qualification_level) VALUES (?,?,?,'operator')")
                       ->execute([$unitId, $targetId, $callsign ?: null]);
                    awardXP($targetId, 'socom_inducted', ['unit' => $unitRow['unit_code']]);
                    $msg = "Operator inducted into <strong>" . htmlspecialchars($unitRow['unit_name']) . "</strong>."; $msgType = 'success';
                }
            }
        } elseif ($action === 'plan_mission' && ($isOperator || $isFlag)) {
            $unitId  = (int)($_POST['unit_id'] ?? 0);
            $mType   = $_POST['mission_type'] ?? 'direct_action';
            $obj     = trim($_POST['objective'] ?? '');
            $target  = trim($_POST['target'] ?? '');
            $class   = $_POST['mission_class'] ?? 'secret';
            $xpR     = (int)($_POST['xp_reward'] ?? 500);
            $validMT = ['direct_action','recon','sabotage','extraction','assassination','cyber_infiltration','counter_espionage'];
            $validCL = ['secret','top_secret','black'];
            if (!in_array($mType, $validMT, true) || $obj === '' || !in_array($class, $validCL, true)) {
                $msg = 'All mission fields required.'; $msgType = 'error';
            } elseif ($class === 'black' && !$isCommander) {
                $msg = 'Black classification requires Commander authorization.'; $msgType = 'error';
            } else {
                $code = 'OP-' . strtoupper(bin2hex(random_bytes(4)));
                $db->prepare("INSERT INTO socom_missions (mission_code, unit_id, mission_type, objective, target, classification, briefed_by, xp_reward) VALUES (?,?,?,?,?,?,?,?)")
                   ->execute([$code, $unitId, $mType, $obj, $target, $class, $clientId, max(100, $xpR)]);
                $msg = "Mission <strong>$code</strong> planned."; $msgType = 'success';
            }
        } elseif ($action === 'authorize_mission' && $isFlag) {
            $mId = (int)($_POST['mission_id'] ?? 0);
            $stmt = $db->prepare("UPDATE socom_missions SET status = 'briefing', authorized_by = ? WHERE id = ? AND status = 'planning'");
            $stmt->execute([$clientId, $mId]);
            $msg = $stmt->rowCount() ? 'Mission authorized.' : 'Not found.';
            $msgType = $stmt->rowCount() ? 'success' : 'error';

        } elseif ($action === 'deploy_mission' && $isFlag) {
            $mId = (int)($_POST['mission_id'] ?? 0);
            $stmt = $db->prepare("UPDATE socom_missions SET status = 'deployed', start_at = NOW() WHERE id = ? AND status = 'briefing'");
            $stmt->execute([$mId]);
            $msg = $stmt->rowCount() ? 'Mission DEPLOYED. Operators in the field.' : 'Not found.';
            $msgType = $stmt->rowCount() ? 'success' : 'error';

        } elseif ($action === 'complete_mission' && ($isOperator || $isFlag)) {
            $mId    = (int)($_POST['mission_id'] ?? 0);
            $outcome = trim($_POST['outcome'] ?? '');
            $success = !empty($_POST['success']);
            $newStatus = $success ? 'complete' : 'failed';
            $stmt = $db->prepare("UPDATE socom_missions SET status = ?, end_at = NOW(), outcome = ? WHERE id = ? AND status IN ('deployed','in_progress')");
            $stmt->execute([$newStatus, $outcome, $mId]);
            if ($stmt->rowCount() && $success) {
                $mission = $db->prepare("SELECT xp_reward, unit_id FROM socom_missions WHERE id = ?");
                $mission->execute([$mId]);
                $mRow = $mission->fetch(PDO::FETCH_ASSOC);
                if ($mRow) {
                    awardXP($clientId, 'mission_complete', ['code' => $mId]);
                    $db->exec("UPDATE socom_operators SET missions_completed = missions_completed + 1 WHERE unit_id = " . (int)$mRow['unit_id'] . " AND status = 'active'");
                }
            }
            $msg = $stmt->rowCount() ? ($success ? 'Mission COMPLETE.' : 'Mission FAILED.') : 'Not found.';
            $msgType = $stmt->rowCount() ? ($success ? 'success' : 'error') : 'error';

        } elseif ($action === 'file_aar' && ($isOperator || $isFlag)) {
            $mId     = (int)($_POST['mission_id'] ?? 0);
            $report  = trim($_POST['aar_report'] ?? '');
            $lessons = trim($_POST['lessons'] ?? '');
            $cas     = (int)($_POST['casualties'] ?? 0);
            $collat  = trim($_POST['collateral'] ?? '');
            if ($mId < 1 || $report === '') {
                $msg = 'Mission ID and report required.'; $msgType = 'error';
            } else {
                $db->prepare("INSERT INTO socom_after_action (mission_id, filed_by, report, lessons_learned, casualties, collateral) VALUES (?,?,?,?,?,?)")
                   ->execute([$mId, $clientId, $report, $lessons, $cas, $collat]);
                awardXP($clientId, 'aar_filed', []);
                $msg = "After-Action Report filed."; $msgType = 'success';
            }
        } else {
            $msg = 'Unauthorized or unknown action.'; $msgType = 'error';
        }
    }
    $_SESSION['csrf_so'] = bin2hex(random_bytes(32));
    $csrf = $_SESSION['csrf_so'];
}

// ── Data Fetch ──
$tab = $_GET['tab'] ?? 'command';
$units = $db->query("SELECT su.*, CONCAT(c.firstname,' ',c.lastname) AS cmd_name, (SELECT COUNT(*) FROM socom_operators so WHERE so.unit_id = su.id AND so.status = 'active') AS op_count FROM socom_units su LEFT JOIN tblclients c ON c.id = su.commander_id ORDER BY FIELD(su.classification,'black','top_secret','secret'), su.unit_code")->fetchAll(PDO::FETCH_ASSOC);
$missions = $db->query("SELECT sm.*, su.unit_name, su.unit_code, CONCAT(c1.firstname,' ',c1.lastname) AS briefer_name, CONCAT(c2.firstname,' ',c2.lastname) AS authorizer_name FROM socom_missions sm JOIN socom_units su ON su.id = sm.unit_id LEFT JOIN tblclients c1 ON c1.id = sm.briefed_by LEFT JOIN tblclients c2 ON c2.id = sm.authorized_by ORDER BY sm.start_at DESC, sm.id DESC")->fetchAll(PDO::FETCH_ASSOC);
$operators = $db->query("SELECT so.*, su.unit_name, su.unit_code, CONCAT(c.firstname,' ',c.lastname) AS op_name FROM socom_operators so JOIN socom_units su ON su.id = so.unit_id LEFT JOIN tblclients c ON c.id = so.client_id ORDER BY FIELD(so.qualification_level,'master','senior','operator','candidate'), so.missions_completed DESC")->fetchAll(PDO::FETCH_ASSOC);
$quals = $db->query("SELECT sq.*, CONCAT(c.firstname,' ',c.lastname) AS candidate_name, CONCAT(c2.firstname,' ',c2.lastname) AS eval_name FROM socom_qualifications sq LEFT JOIN tblclients c ON c.id = sq.client_id LEFT JOIN tblclients c2 ON c2.id = sq.evaluator_id ORDER BY sq.started_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$aars = $db->query("SELECT aa.*, sm.mission_code, CONCAT(c.firstname,' ',c.lastname) AS filer_name FROM socom_after_action aa JOIN socom_missions sm ON sm.id = aa.mission_id LEFT JOIN tblclients c ON c.id = aa.filed_by ORDER BY aa.filed_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$activeMissions = count(array_filter($missions, fn($m) => in_array($m['status'], ['deployed','in_progress'])));
$totalOps = count(array_filter($operators, fn($o) => $o['status'] === 'active'));

$pageTitle = 'Special Operations Command (SOCOM)';
include __DIR__ . '/includes/site-header.inc.php';
?>
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<style>
.so-wrap{max-width:1100px;margin:0 auto;padding:1.5rem}
.so-bg{background:#0a0a14;min-height:100vh;color:#e2e8f0}
.so-card{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:10px;padding:1.25rem;margin-bottom:1rem}
.so-card:hover{border-color:#f97316;box-shadow:0 0 12px rgba(249,115,22,.12)}
.so-title{font-size:1.6rem;font-weight:700;color:#f1f5f9;margin-bottom:.25rem;display:flex;align-items:center;gap:.5rem}
.so-sub{color:#94a3b8;font-size:.85rem}
.so-badge{display:inline-block;padding:2px 10px;border-radius:999px;font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
.so-tabs{display:flex;gap:.5rem;margin-bottom:1.25rem;flex-wrap:wrap}
.so-tab{padding:.4rem 1rem;border-radius:6px;cursor:pointer;font-size:.8rem;background:#2a2a4a;color:#94a3b8;text-decoration:none;font-weight:600;border:none}
.so-tab.active{background:#f97316;color:#fff}
.so-stat-bar{display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem}
.so-stat{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:8px;padding:.75rem 1.25rem;flex:1;min-width:110px;text-align:center}
.so-stat .val{font-size:1.5rem;font-weight:700;color:#f97316}
.so-stat .lbl{font-size:.7rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px}
.so-btn{background:#f97316;color:#fff;border:none;padding:.5rem 1.25rem;border-radius:6px;cursor:pointer;font-size:.85rem;font-weight:600;transition:.2s}
.so-btn:hover{background:#ea580c}
.so-btn-sm{padding:.3rem .75rem;font-size:.75rem}
.so-btn-outline{background:transparent;border:1px solid #f97316;color:#f97316}
.so-btn-outline:hover{background:#f97316;color:#fff}
.so-btn-red{background:#ef4444;color:#fff}.so-btn-red:hover{background:#dc2626}
.so-btn-green{background:#22c55e;color:#fff}.so-btn-green:hover{background:#16a34a}
.so-btn-blue{background:#3b82f6;color:#fff}.so-btn-blue:hover{background:#2563eb}
.so-btn-black{background:#1e1e1e;color:#ef4444;border:1px solid #ef4444}.so-btn-black:hover{background:#ef4444;color:#fff}
.so-input,.so-select,.so-textarea{width:100%;background:#0a0a14;border:1px solid #2a2a4a;color:#e2e8f0;padding:.5rem .75rem;border-radius:6px;font-size:.85rem;box-sizing:border-box}
.so-textarea{min-height:100px;resize:vertical}
.so-label{display:block;color:#94a3b8;font-size:.75rem;margin-bottom:.25rem;text-transform:uppercase;letter-spacing:.5px}
.so-msg{padding:.75rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:.85rem}
.so-msg-success{background:rgba(249,115,22,.12);border:1px solid #f97316;color:#fdba74}
.so-msg-error{background:rgba(239,68,68,.12);border:1px solid #ef4444;color:#fca5a5}
.so-modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:900;align-items:center;justify-content:center}
.so-modal-bg.open{display:flex}
.so-modal{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:12px;padding:1.5rem;width:90%;max-width:620px;max-height:80vh;overflow-y:auto}
.so-modal h3{color:#f1f5f9;margin:0 0 1rem}
.so-form-row{margin-bottom:.75rem}
.so-pipeline{display:flex;gap:4px;flex-wrap:wrap;margin:.5rem 0}
.so-pipeline span{padding:2px 8px;border-radius:4px;font-size:.65rem;font-weight:600;text-transform:uppercase}
</style>
<div class="so-bg">
<div class="so-wrap">
    <div class="so-title"><i class="fas fa-crosshairs"></i> Special Operations Command — SOCOM</div>
    <p class="so-sub" style="margin-bottom:1.25rem">Black ops, operator qualification, mission planning, and after-action reports — Officer+ rank</p>

    <?php if ($msg): ?><div class="so-msg so-msg-<?= $msgType ?>"><?= $msg ?></div><?php endif; ?>

    <?php if ($isOperator && $myOp): ?>
        <div class="so-card" style="border-left:4px solid #f97316;display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
            <i class="fas fa-user-secret" style="font-size:2.5rem;color:#f97316"></i>
            <div>
                <div style="color:#f97316;font-size:.9rem;font-weight:700"><?= htmlspecialchars($myOp['callsign'] ?? 'NO CALLSIGN') ?></div>
                <div style="color:#f1f5f9;font-size:.85rem"><?= htmlspecialchars($myOp['unit_name']) ?> <span style="color:#94a3b8">(<?= htmlspecialchars($myOp['unit_code']) ?>)</span></div>
                <div style="font-size:.8rem;color:#94a3b8"><?= strtoupper(str_replace('_', ' ', $myOp['qualification_level'])) ?> &bull; Missions: <?= (int)$myOp['missions_completed'] ?> &bull; Success: <?= number_format($myOp['success_rate'], 1) ?>%</div>
            </div>
        </div>
    <?php endif; ?>

    <div class="so-stat-bar">
        <div class="so-stat"><div class="val"><?= count($units) ?></div><div class="lbl">Units</div></div>
        <div class="so-stat"><div class="val" style="color:#22c55e"><?= $totalOps ?></div><div class="lbl">Active Operators</div></div>
        <div class="so-stat"><div class="val" style="color:#ef4444"><?= $activeMissions ?></div><div class="lbl">Active Missions</div></div>
        <div class="so-stat"><div class="val" style="color:#3b82f6"><?= count($missions) ?></div><div class="lbl">Total Missions</div></div>
        <div class="so-stat"><div class="val" style="color:#d4a017"><?= count($aars) ?></div><div class="lbl">AARs Filed</div></div>
    </div>

    <div class="so-tabs">
        <a href="?tab=command" class="so-tab <?= $tab==='command'?'active':'' ?>"><i class="fas fa-terminal"></i> Command</a>
        <a href="?tab=missions" class="so-tab <?= $tab==='missions'?'active':'' ?>"><i class="fas fa-crosshairs"></i> Missions</a>
        <a href="?tab=operators" class="so-tab <?= $tab==='operators'?'active':'' ?>"><i class="fas fa-user-secret"></i> Operators</a>
        <a href="?tab=pipeline" class="so-tab <?= $tab==='pipeline'?'active':'' ?>"><i class="fas fa-filter"></i> Pipeline</a>
        <a href="?tab=aars" class="so-tab <?= $tab==='aars'?'active':'' ?>"><i class="fas fa-file-alt"></i> AARs</a>
    </div>

    <!-- ═══ TAB: COMMAND ═══ -->
    <?php if ($tab === 'command'): ?>
        <?php if ($isCommander): ?>
            <div style="margin-bottom:1rem"><button class="so-btn so-btn-black" onclick="document.getElementById('modalUnit').classList.add('open')"><i class="fas fa-plus"></i> Create Unit</button></div>
        <?php endif; ?>
        <?php
        $clColors = ['secret'=>'#3b82f6','top_secret'=>'#f59e0b','black'=>'#ef4444'];
        $stColors = ['active'=>'#22c55e','standby'=>'#3b82f6','deployed'=>'#f97316','disbanded'=>'#64748b'];
        foreach ($units as $u): ?>
            <div class="so-card" style="border-left:3px solid <?= $clColors[$u['classification']] ?>">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div>
                        <strong style="color:#f97316"><?= htmlspecialchars($u['unit_code']) ?></strong>
                        <strong style="color:#f1f5f9;margin-left:.5rem"><?= htmlspecialchars($u['unit_name']) ?></strong>
                        <span class="so-badge" style="background:<?= $clColors[$u['classification']] ?>20;color:<?= $clColors[$u['classification']] ?>;margin-left:.5rem"><?= strtoupper(str_replace('_', ' ', $u['classification'])) ?></span>
                    </div>
                    <span class="so-badge" style="background:<?= $stColors[$u['status']] ?>20;color:<?= $stColors[$u['status']] ?>"><?= strtoupper($u['status']) ?></span>
                </div>
                <div style="color:#94a3b8;font-size:.8rem;margin-top:.25rem">
                    <?= htmlspecialchars($u['specialty'] ?? 'General Operations') ?> &bull; Operators: <?= $u['op_count'] ?>/<?= $u['max_operators'] ?>
                    &bull; CO: <?= htmlspecialchars($u['cmd_name'] ?? 'None') ?>
                </div>
            </div>
        <?php endforeach; ?>

    <!-- ═══ TAB: MISSIONS ═══ -->
    <?php elseif ($tab === 'missions'): ?>
        <?php if ($isOperator || $isFlag): ?>
            <div style="margin-bottom:1rem"><button class="so-btn" onclick="document.getElementById('modalMission').classList.add('open')"><i class="fas fa-crosshairs"></i> Plan Mission</button></div>
        <?php endif; ?>
        <?php
        $msColors = ['planning'=>'#94a3b8','briefing'=>'#3b82f6','deployed'=>'#f97316','in_progress'=>'#f59e0b','complete'=>'#22c55e','failed'=>'#ef4444','aborted'=>'#64748b'];
        $msPipeline = ['planning','briefing','deployed','in_progress','complete'];
        foreach ($missions as $ms): ?>
            <div class="so-card">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div>
                        <strong style="color:#f97316"><?= htmlspecialchars($ms['mission_code']) ?></strong>
                        <span class="so-badge" style="background:<?= $clColors[$ms['classification']] ?>20;color:<?= $clColors[$ms['classification']] ?>;margin-left:.25rem"><?= strtoupper(str_replace('_', ' ', $ms['classification'])) ?></span>
                        <span style="color:#94a3b8;font-size:.8rem;margin-left:.5rem"><?= strtoupper(str_replace('_', ' ', $ms['mission_type'])) ?></span>
                    </div>
                    <span class="so-badge" style="background:<?= $msColors[$ms['status']] ?>20;color:<?= $msColors[$ms['status']] ?>;border:1px solid <?= $msColors[$ms['status']] ?>40"><?= strtoupper($ms['status']) ?></span>
                </div>
                <div class="so-pipeline">
                    <?php foreach ($msPipeline as $stage):
                        $isCurrent = ($ms['status'] === $stage);
                        $isPast = array_search($ms['status'], $msPipeline) > array_search($stage, $msPipeline);
                    ?>
                        <span style="background:<?= $isCurrent ? '#f97316' : ($isPast ? '#22c55e30' : '#2a2a4a') ?>;color:<?= $isCurrent ? '#fff' : ($isPast ? '#22c55e' : '#64748b') ?>"><?= strtoupper(str_replace('_', ' ', $stage)) ?></span>
                    <?php endforeach; ?>
                </div>
                <p style="color:#cbd5e1;font-size:.85rem;margin-top:.5rem"><strong>Objective:</strong> <?= htmlspecialchars($ms['objective']) ?></p>
                <?php if ($ms['target']): ?><div style="color:#f59e0b;font-size:.8rem"><strong>Target:</strong> <?= htmlspecialchars($ms['target']) ?></div><?php endif; ?>
                <div style="color:#64748b;font-size:.75rem;margin-top:.25rem">
                    Unit: <?= htmlspecialchars($ms['unit_name'] ?? 'Unknown') ?> &bull; XP: <?= (int)$ms['xp_reward'] ?>
                    <?php if ($ms['authorized_by']): ?>&bull; Auth: <?= htmlspecialchars($ms['authorizer_name'] ?? 'Unknown') ?><?php endif; ?>
                </div>
                <?php if ($ms['outcome']): ?><div style="color:#86efac;font-size:.85rem;margin-top:.5rem;border-top:1px solid #2a2a4a;padding-top:.5rem"><strong>Outcome:</strong> <?= htmlspecialchars($ms['outcome']) ?></div><?php endif; ?>
                <?php if ($isFlag && !in_array($ms['status'], ['complete','failed','aborted'])): ?>
                    <div style="margin-top:.5rem;display:flex;gap:.5rem;flex-wrap:wrap">
                        <?php if ($ms['status'] === 'planning'): ?>
                            <form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="authorize_mission"><input type="hidden" name="mission_id" value="<?= $ms['id'] ?>"><button class="so-btn-sm so-btn so-btn-blue"><i class="fas fa-stamp"></i> Authorize</button></form>
                        <?php endif; ?>
                        <?php if ($ms['status'] === 'briefing'): ?>
                            <form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="deploy_mission"><input type="hidden" name="mission_id" value="<?= $ms['id'] ?>"><button class="so-btn-sm so-btn so-btn-red"><i class="fas fa-rocket"></i> Deploy</button></form>
                        <?php endif; ?>
                        <?php if (in_array($ms['status'], ['deployed','in_progress'])): ?>
                            <button class="so-btn-sm so-btn so-btn-green" onclick="openComplete(<?= $ms['id'] ?>,'<?= htmlspecialchars($ms['mission_code'], ENT_QUOTES) ?>')"><i class="fas fa-flag-checkered"></i> Complete</button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if (empty($missions)): ?><div class="so-card" style="text-align:center;color:#64748b"><p>No missions planned.</p></div><?php endif; ?>

    <!-- ═══ TAB: OPERATORS ═══ -->
    <?php elseif ($tab === 'operators'): ?>
        <?php if ($isFlag): ?>
            <div style="margin-bottom:1rem"><button class="so-btn" onclick="document.getElementById('modalInvite').classList.add('open')"><i class="fas fa-user-plus"></i> Invite Operator</button></div>
        <?php endif; ?>
        <?php
        $qlColors = ['candidate'=>'#94a3b8','operator'=>'#3b82f6','senior'=>'#f59e0b','master'=>'#ef4444'];
        foreach ($operators as $op): ?>
            <div class="so-card" style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
                <i class="fas fa-user-secret" style="font-size:1.5rem;color:<?= $qlColors[$op['qualification_level']] ?>"></i>
                <div style="flex:1">
                    <strong style="color:#f97316"><?= htmlspecialchars($op['callsign'] ?? '—') ?></strong>
                    <span style="color:#f1f5f9;margin-left:.5rem"><?= htmlspecialchars($op['op_name'] ?? 'Unknown') ?></span>
                    <div style="color:#94a3b8;font-size:.75rem">
                        <span class="so-badge" style="background:<?= $qlColors[$op['qualification_level']] ?>20;color:<?= $qlColors[$op['qualification_level']] ?>"><?= strtoupper(str_replace('_', ' ', $op['qualification_level'])) ?></span>
                        &bull; <?= htmlspecialchars($op['unit_name']) ?>
                        &bull; Missions: <?= (int)$op['missions_completed'] ?> &bull; Success: <?= number_format($op['success_rate'], 1) ?>%
                    </div>
                </div>
                <span class="so-badge" style="background:<?= $op['status']==='active'?'#22c55e20':'#64748b20' ?>;color:<?= $op['status']==='active'?'#22c55e':'#64748b' ?>"><?= strtoupper($op['status']) ?></span>
            </div>
        <?php endforeach; ?>
        <?php if (empty($operators)): ?><div class="so-card" style="text-align:center;color:#64748b"><p>No operators.</p></div><?php endif; ?>

    <!-- ═══ TAB: PIPELINE ═══ -->
    <?php elseif ($tab === 'pipeline'): ?>
        <?php if ($isOfficer): ?>
            <div style="margin-bottom:1rem"><button class="so-btn so-btn-blue" onclick="document.getElementById('modalSelection').classList.add('open')"><i class="fas fa-filter"></i> Start Selection</button></div>
        <?php endif; ?>
        <div style="display:flex;gap:4px;margin-bottom:1rem">
            <?php foreach (['assessment','selection','training','operational'] as $ph): ?>
                <div style="flex:1;text-align:center;padding:.5rem;border-radius:6px;background:#1a1a2e;border:1px solid #2a2a4a">
                    <div style="font-size:.7rem;color:#94a3b8;text-transform:uppercase"><?= $ph ?></div>
                    <div style="font-size:1.2rem;font-weight:700;color:#f97316"><?= count(array_filter($quals, fn($q) => $q['phase'] === $ph && $q['result'] === 'in_progress')) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        $qrColors = ['in_progress'=>'#f59e0b','passed'=>'#22c55e','failed'=>'#ef4444','withdrawn'=>'#64748b'];
        foreach ($quals as $q): ?>
            <div class="so-card" style="border-left:3px solid <?= $qrColors[$q['result']] ?>">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div>
                        <strong style="color:#f1f5f9"><?= htmlspecialchars($q['candidate_name'] ?? 'Unknown') ?></strong>
                        <span class="so-badge" style="background:#f9731620;color:#f97316;margin-left:.5rem"><?= strtoupper($q['phase']) ?></span>
                    </div>
                    <span class="so-badge" style="background:<?= $qrColors[$q['result']] ?>20;color:<?= $qrColors[$q['result']] ?>"><?= strtoupper(str_replace('_', ' ', $q['result'])) ?></span>
                </div>
                <div style="color:#64748b;font-size:.75rem;margin-top:.25rem">Evaluator: <?= htmlspecialchars($q['eval_name'] ?? 'None') ?> &bull; Started: <?= date('M j, Y', strtotime($q['started_at'])) ?></div>
                <?php if ($q['notes']): ?><p style="color:#94a3b8;font-size:.8rem;margin-top:.25rem"><?= htmlspecialchars($q['notes']) ?></p><?php endif; ?>
                <?php if ($q['result'] === 'in_progress' && $isFlag): ?>
                    <form method="POST" style="margin-top:.5rem;display:flex;gap:.5rem;align-items:center;flex-wrap:wrap">
                        <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="advance_qualification"><input type="hidden" name="qual_id" value="<?= $q['id'] ?>">
                        <select name="qual_result" class="so-select" style="width:auto"><option value="passed">Pass</option><option value="failed">Fail</option><option value="withdrawn">Withdraw</option></select>
                        <input type="text" name="qual_notes" class="so-input" style="flex:1" placeholder="Notes...">
                        <button class="so-btn-sm so-btn"><i class="fas fa-forward"></i> Advance</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if (empty($quals)): ?><div class="so-card" style="text-align:center;color:#64748b"><p>No candidates in the pipeline.</p></div><?php endif; ?>

    <!-- ═══ TAB: AARs ═══ -->
    <?php elseif ($tab === 'aars'): ?>
        <?php if ($isOperator || $isFlag): ?>
            <div style="margin-bottom:1rem"><button class="so-btn" onclick="document.getElementById('modalAAR').classList.add('open')"><i class="fas fa-file-alt"></i> File AAR</button></div>
        <?php endif; ?>
        <?php foreach ($aars as $aar): ?>
            <div class="so-card">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div><strong style="color:#f97316"><?= htmlspecialchars($aar['mission_code']) ?></strong> <span style="color:#94a3b8;font-size:.8rem">After-Action Report</span></div>
                    <span style="color:#64748b;font-size:.75rem"><?= date('M j, Y', strtotime($aar['filed_at'])) ?></span>
                </div>
                <p style="color:#cbd5e1;font-size:.85rem;margin-top:.5rem"><?= nl2br(htmlspecialchars($aar['report'])) ?></p>
                <?php if ($aar['lessons_learned']): ?><div style="color:#86efac;font-size:.85rem;margin-top:.5rem;border-top:1px solid #2a2a4a;padding-top:.5rem"><strong>Lessons:</strong> <?= htmlspecialchars($aar['lessons_learned']) ?></div><?php endif; ?>
                <div style="color:#64748b;font-size:.75rem;margin-top:.25rem">Filed by: <?= htmlspecialchars($aar['filer_name'] ?? 'Unknown') ?> &bull; Casualties: <?= (int)$aar['casualties'] ?></div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($aars)): ?><div class="so-card" style="text-align:center;color:#64748b"><p>No AARs filed.</p></div><?php endif; ?>
    <?php endif; ?>
</div>
</div>

<!-- Modals -->
<div class="so-modal-bg" id="modalUnit"><div class="so-modal"><h3><i class="fas fa-plus"></i> Create SOCOM Unit</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="create_unit">
<div class="so-form-row"><label class="so-label">Unit Name</label><input type="text" name="unit_name" class="so-input" required></div>
<div class="so-form-row"><label class="so-label">Specialty</label><input type="text" name="specialty" class="so-input" placeholder="e.g. Cyber Warfare"></div>
<div style="display:flex;gap:.75rem"><div class="so-form-row" style="flex:1"><label class="so-label">Classification</label><select name="unit_class" class="so-select"><option value="secret">Secret</option><option value="top_secret">Top Secret</option><option value="black">Black</option></select></div><div class="so-form-row" style="flex:1"><label class="so-label">Max Operators</label><input type="number" name="max_ops" class="so-input" value="20" min="5" max="100"></div></div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="so-btn so-btn-outline" onclick="this.closest('.so-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="so-btn so-btn-black"><i class="fas fa-plus"></i> Create</button></div></form></div></div>

<div class="so-modal-bg" id="modalMission"><div class="so-modal"><h3><i class="fas fa-crosshairs"></i> Plan Mission</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="plan_mission">
<div class="so-form-row"><label class="so-label">Unit</label><select name="unit_id" class="so-select"><?php foreach ($units as $u): ?><option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['unit_code'] . ' — ' . $u['unit_name']) ?></option><?php endforeach; ?></select></div>
<div style="display:flex;gap:.75rem"><div class="so-form-row" style="flex:1"><label class="so-label">Mission Type</label><select name="mission_type" class="so-select"><option value="direct_action">Direct Action</option><option value="recon">Reconnaissance</option><option value="sabotage">Sabotage</option><option value="extraction">Extraction</option><option value="assassination">Assassination</option><option value="cyber_infiltration">Cyber Infiltration</option><option value="counter_espionage">Counter-Espionage</option></select></div><div class="so-form-row" style="flex:1"><label class="so-label">Classification</label><select name="mission_class" class="so-select"><option value="secret">Secret</option><option value="top_secret">Top Secret</option><option value="black">Black</option></select></div></div>
<div class="so-form-row"><label class="so-label">Objective</label><textarea name="objective" class="so-textarea" required></textarea></div>
<div class="so-form-row"><label class="so-label">Target</label><input type="text" name="target" class="so-input" placeholder="Target designation..."></div>
<div class="so-form-row"><label class="so-label">XP Reward</label><input type="number" name="xp_reward" class="so-input" value="500" min="100"></div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="so-btn so-btn-outline" onclick="this.closest('.so-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="so-btn"><i class="fas fa-crosshairs"></i> Plan</button></div></form></div></div>

<div class="so-modal-bg" id="modalInvite"><div class="so-modal"><h3><i class="fas fa-user-plus"></i> Invite Operator</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="invite_operator">
<div class="so-form-row"><label class="so-label">Operator Client ID</label><input type="number" name="operator_id" class="so-input" required min="1"></div>
<div class="so-form-row"><label class="so-label">Unit</label><select name="unit_id" class="so-select"><?php foreach ($units as $u): ?><option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['unit_code'] . ' — ' . $u['unit_name']) ?></option><?php endforeach; ?></select></div>
<div class="so-form-row"><label class="so-label">Callsign</label><input type="text" name="callsign" class="so-input" placeholder="e.g. Viper, Ghost-6, Wraith"></div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="so-btn so-btn-outline" onclick="this.closest('.so-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="so-btn"><i class="fas fa-user-plus"></i> Invite</button></div></form></div></div>

<div class="so-modal-bg" id="modalSelection"><div class="so-modal"><h3><i class="fas fa-filter"></i> Start Selection</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="start_selection">
<div class="so-form-row"><label class="so-label">Candidate Client ID (or self)</label><input type="number" name="target_id" class="so-input" value="<?= $clientId ?>" min="1"></div>
<div style="background:#f9731610;border:1px solid #f9731630;border-radius:6px;padding:.75rem;margin-bottom:.75rem;font-size:.8rem;color:#fdba74"><i class="fas fa-info-circle"></i> 80% washout rate target. 4-phase pipeline: Assessment → Selection → Training → Operational.</div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="so-btn so-btn-outline" onclick="this.closest('.so-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="so-btn so-btn-blue"><i class="fas fa-filter"></i> Begin</button></div></form></div></div>

<div class="so-modal-bg" id="modalComplete"><div class="so-modal"><h3><i class="fas fa-flag-checkered"></i> Complete Mission</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="complete_mission"><input type="hidden" name="mission_id" id="compMissionId" value="">
<div style="color:#94a3b8;margin-bottom:1rem">Mission: <strong id="compMissionCode" style="color:#f97316"></strong></div>
<div class="so-form-row"><label class="so-label">Outcome</label><textarea name="outcome" class="so-textarea" required></textarea></div>
<div class="so-form-row"><label style="color:#22c55e;font-size:.85rem"><input type="checkbox" name="success" value="1" checked> Mission Successful</label></div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="so-btn so-btn-outline" onclick="this.closest('.so-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="so-btn so-btn-green"><i class="fas fa-flag-checkered"></i> Complete</button></div></form></div></div>

<div class="so-modal-bg" id="modalAAR"><div class="so-modal"><h3><i class="fas fa-file-alt"></i> File After-Action Report</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="file_aar">
<div class="so-form-row"><label class="so-label">Mission ID</label><input type="number" name="mission_id" class="so-input" required min="1"></div>
<div class="so-form-row"><label class="so-label">Report</label><textarea name="aar_report" class="so-textarea" required></textarea></div>
<div class="so-form-row"><label class="so-label">Lessons Learned</label><textarea name="lessons" class="so-textarea" style="min-height:60px"></textarea></div>
<div style="display:flex;gap:.75rem"><div class="so-form-row" style="flex:1"><label class="so-label">Casualties</label><input type="number" name="casualties" class="so-input" value="0" min="0"></div><div class="so-form-row" style="flex:1"><label class="so-label">Collateral</label><input type="text" name="collateral" class="so-input" placeholder="e.g. None, Minimal..."></div></div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="so-btn so-btn-outline" onclick="this.closest('.so-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="so-btn"><i class="fas fa-file-alt"></i> File</button></div></form></div></div>

<script>
function openComplete(id,code){document.getElementById('compMissionId').value=id;document.getElementById('compMissionCode').textContent=code;document.getElementById('modalComplete').classList.add('open')}
document.querySelectorAll('.so-modal-bg').forEach(bg=>{bg.addEventListener('click',e=>{if(e.target===bg)bg.classList.remove('open')})});
</script>
<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
