<?php
/**
 * ═══════════════════════════════════════════
 *  Military Police (MP Corps) — Level 6: Sovereign State
 * ═══════════════════════════════════════════
 */
require_once __DIR__ . '/includes/db-config.inc.php';
$db = getSharedDB();
require_once __DIR__ . '/includes/auth-gate.inc.php';
require_once __DIR__ . '/includes/rank-guard.inc.php';
if (empty($_SESSION['csrf_mp'])) $_SESSION['csrf_mp'] = bin2hex(random_bytes(32));
requireRank(4);

$isCommander = ($clientId === 33);
$isFlag      = ($userRankTier >= 9) || $isCommander;
$isOfficer   = ($userRankTier >= 6) || $isCommander;
$isNCO       = ($userRankTier >= 4) || $isCommander;
$msg = '';
$msgType = '';

// ── Auto-create tables ──
$db->exec("CREATE TABLE IF NOT EXISTS mp_personnel (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL UNIQUE,
    mp_rank ENUM('mp_private','mp_sergeant','mp_officer','provost_marshal') DEFAULT 'mp_private',
    badge_number VARCHAR(20) NOT NULL,
    status ENUM('active','suspended','retired') DEFAULT 'active',
    assigned_zone VARCHAR(100) DEFAULT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS mp_patrols (
    id INT AUTO_INCREMENT PRIMARY KEY,
    zone VARCHAR(100) NOT NULL,
    mp_id INT NOT NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ended_at TIMESTAMP NULL,
    incidents_reported INT DEFAULT 0,
    status ENUM('active','completed','abandoned') DEFAULT 'active',
    report TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS mp_citations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cited_client_id INT NOT NULL,
    issuing_mp_id INT NOT NULL,
    citation_type ENUM('warning','minor','major') DEFAULT 'warning',
    violation TEXT NOT NULL,
    xp_penalty INT DEFAULT 0,
    rank_freeze_hours INT DEFAULT 0,
    notes TEXT DEFAULT NULL,
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    appealed TINYINT(1) DEFAULT 0,
    appeal_text TEXT DEFAULT NULL,
    appeal_status ENUM('none','pending','upheld','overturned') DEFAULT 'none'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS mp_investigations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_code VARCHAR(20) NOT NULL,
    lead_investigator_id INT NOT NULL,
    subject_client_id INT NOT NULL,
    charge TEXT NOT NULL,
    evidence TEXT DEFAULT NULL,
    status ENUM('open','active','closed','referred_to_court') DEFAULT 'open',
    opened_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    closed_at TIMESTAMP NULL,
    outcome TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS mp_detention_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    detained_client_id INT NOT NULL,
    detaining_mp_id INT NOT NULL,
    reason TEXT NOT NULL,
    detained_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    released_at TIMESTAMP NULL,
    duration_hours INT DEFAULT 0,
    charges_filed TINYINT(1) DEFAULT 0,
    notes TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$csrf = $_SESSION['csrf_mp'];

// ── Check if current user is MP ──
$mpCheck = $db->prepare("SELECT * FROM mp_personnel WHERE client_id = ? AND status = 'active'");
$mpCheck->execute([$clientId]);
$myMP = $mpCheck->fetch(PDO::FETCH_ASSOC);
$isMP = (bool)$myMP;

// ── POST Handlers ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, $_POST['csrf'] ?? '')) {
        $msg = 'Invalid security token.'; $msgType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'enlist_mp' && $isNCO && !$isMP) {
            $badgeNum = 'MP-' . str_pad(mt_rand(1000, 9999), 4, '0', STR_PAD_LEFT);
            $mpRank = $isOfficer ? 'mp_officer' : ($isNCO ? 'mp_sergeant' : 'mp_private');
            $stmt = $db->prepare("INSERT INTO mp_personnel (client_id, mp_rank, badge_number) VALUES (?,?,?)");
            $stmt->execute([$clientId, $mpRank, $badgeNum]);
            awardXP($clientId, 'mp_enlisted', ['badge' => $badgeNum]);
            $msg = "Enlisted in MP Corps. Badge: <strong>$badgeNum</strong>."; $msgType = 'success';
            $myMP = ['badge_number' => $badgeNum, 'mp_rank' => $mpRank]; $isMP = true;

        } elseif ($action === 'start_patrol' && $isMP) {
            $zone = trim($_POST['patrol_zone'] ?? '');
            if ($zone === '') {
                $msg = 'Patrol zone required.'; $msgType = 'error';
            } else {
                $active = $db->prepare("SELECT id FROM mp_patrols WHERE mp_id = ? AND status = 'active'");
                $active->execute([$clientId]);
                if ($active->fetch()) {
                    $msg = 'You already have an active patrol. End it first.'; $msgType = 'error';
                } else {
                    $db->prepare("INSERT INTO mp_patrols (zone, mp_id) VALUES (?,?)")->execute([$zone, $clientId]);
                    $msg = "Patrol started in zone <strong>" . htmlspecialchars($zone) . "</strong>."; $msgType = 'success';
                }
            }
        } elseif ($action === 'end_patrol' && $isMP) {
            $patrolId  = (int)($_POST['patrol_id'] ?? 0);
            $incidents = (int)($_POST['incidents'] ?? 0);
            $report    = trim($_POST['patrol_report'] ?? '');
            $stmt = $db->prepare("UPDATE mp_patrols SET status = 'completed', ended_at = NOW(), incidents_reported = ?, report = ? WHERE id = ? AND mp_id = ? AND status = 'active'");
            $stmt->execute([$incidents, $report, $patrolId, $clientId]);
            if ($stmt->rowCount()) {
                awardXP($clientId, 'patrol_completed', ['incidents' => $incidents]);
                $msg = "Patrol completed. $incidents incident(s) reported."; $msgType = 'success';
            } else {
                $msg = 'Patrol not found.'; $msgType = 'error';
            }
        } elseif ($action === 'issue_citation' && $isMP) {
            $citedId = (int)($_POST['cited_client_id'] ?? 0);
            $citType = $_POST['citation_type'] ?? 'warning';
            $violation = trim($_POST['violation'] ?? '');
            $xpPen   = (int)($_POST['xp_penalty'] ?? 0);
            $freezeH = (int)($_POST['rank_freeze'] ?? 0);
            $notes   = trim($_POST['citation_notes'] ?? '');
            $validCT = ['warning','minor','major'];
            if ($citedId < 1 || $violation === '' || !in_array($citType, $validCT, true)) {
                $msg = 'Cited client, violation, and type required.'; $msgType = 'error';
            } else {
                if ($citType === 'warning') { $xpPen = 0; $freezeH = 0; }
                elseif ($citType === 'minor') { $xpPen = max($xpPen, 50); $freezeH = max($freezeH, 24); }
                elseif ($citType === 'major') { $xpPen = max($xpPen, 200); $freezeH = max($freezeH, 72); }
                $stmt = $db->prepare("INSERT INTO mp_citations (cited_client_id, issuing_mp_id, citation_type, violation, xp_penalty, rank_freeze_hours, notes) VALUES (?,?,?,?,?,?,?)");
                $stmt->execute([$citedId, $clientId, $citType, $violation, $xpPen, $freezeH, $notes ?: null]);
                awardXP($clientId, 'citation_issued', ['type' => $citType]);
                $msg = strtoupper($citType) . " citation issued. XP penalty: $xpPen, Rank freeze: {$freezeH}h."; $msgType = 'success';
            }
        } elseif ($action === 'open_investigation' && ($isMP || $isOfficer)) {
            $subjectId = (int)($_POST['subject_id'] ?? 0);
            $charge    = trim($_POST['charge'] ?? '');
            if ($subjectId < 1 || $charge === '') {
                $msg = 'Subject and charges required.'; $msgType = 'error';
            } else {
                $code = 'INV-' . strtoupper(bin2hex(random_bytes(4)));
                $stmt = $db->prepare("INSERT INTO mp_investigations (case_code, lead_investigator_id, subject_client_id, charge) VALUES (?,?,?,?)");
                $stmt->execute([$code, $clientId, $subjectId, $charge]);
                awardXP($clientId, 'investigation_opened', ['code' => $code]);
                $msg = "Investigation <strong>$code</strong> opened."; $msgType = 'success';
            }
        } elseif ($action === 'close_investigation' && ($isMP || $isOfficer)) {
            $invId    = (int)($_POST['inv_id'] ?? 0);
            $outcome  = trim($_POST['outcome'] ?? '');
            $evidence = trim($_POST['evidence'] ?? '');
            $refer    = !empty($_POST['refer_to_court']);
            $newStatus = $refer ? 'referred_to_court' : 'closed';
            $stmt = $db->prepare("UPDATE mp_investigations SET status = ?, outcome = ?, evidence = ?, closed_at = NOW() WHERE id = ? AND status IN ('open','active')");
            $stmt->execute([$newStatus, $outcome, $evidence, $invId]);
            $msg = $stmt->rowCount() ? "Investigation " . ($refer ? 'referred to Military Court.' : 'closed.') : 'Not found.';
            $msgType = $stmt->rowCount() ? 'success' : 'error';

        } elseif ($action === 'detain' && $isMP) {
            $detainedId = (int)($_POST['detained_id'] ?? 0);
            $reason     = trim($_POST['detain_reason'] ?? '');
            if ($detainedId < 1 || $reason === '') {
                $msg = 'Detained ID and reason required.'; $msgType = 'error';
            } else {
                $db->prepare("INSERT INTO mp_detention_log (detained_client_id, detaining_mp_id, reason) VALUES (?,?,?)")->execute([$detainedId, $clientId, $reason]);
                $msg = "Subject detained. 72-hour limit without formal charges."; $msgType = 'success';
            }
        } elseif ($action === 'release' && $isMP) {
            $detId = (int)($_POST['det_id'] ?? 0);
            $notes = trim($_POST['release_notes'] ?? '');
            $det = $db->prepare("SELECT detained_at FROM mp_detention_log WHERE id = ? AND released_at IS NULL");
            $det->execute([$detId]);
            $detRow = $det->fetch(PDO::FETCH_ASSOC);
            if ($detRow) {
                $hours = max(1, (int)((time() - strtotime($detRow['detained_at'])) / 3600));
                $db->prepare("UPDATE mp_detention_log SET released_at = NOW(), duration_hours = ?, notes = ? WHERE id = ?")->execute([$hours, $notes, $detId]);
                $msg = "Subject released after $hours hour(s)."; $msgType = 'success';
            } else {
                $msg = 'Detention record not found.'; $msgType = 'error';
            }
        } elseif ($action === 'appeal_citation') {
            $citId   = (int)($_POST['cit_id'] ?? 0);
            $appText = trim($_POST['appeal_text'] ?? '');
            if ($citId < 1 || $appText === '') {
                $msg = 'Citation ID and appeal text required.'; $msgType = 'error';
            } else {
                $stmt = $db->prepare("UPDATE mp_citations SET appealed = 1, appeal_text = ?, appeal_status = 'pending' WHERE id = ? AND cited_client_id = ? AND appealed = 0");
                $stmt->execute([$appText, $citId, $clientId]);
                $msg = $stmt->rowCount() ? 'Appeal filed successfully.' : 'Citation not found or already appealed.';
                $msgType = $stmt->rowCount() ? 'success' : 'error';
            }
        } elseif ($action === 'rule_appeal' && $isFlag) {
            $citId   = (int)($_POST['cit_id'] ?? 0);
            $ruling  = $_POST['appeal_ruling'] ?? '';
            $validAR = ['upheld','overturned'];
            if (!in_array($ruling, $validAR, true)) {
                $msg = 'Invalid ruling.'; $msgType = 'error';
            } else {
                $stmt = $db->prepare("UPDATE mp_citations SET appeal_status = ? WHERE id = ? AND appeal_status = 'pending'");
                $stmt->execute([$ruling, $citId]);
                $msg = $stmt->rowCount() ? 'Appeal ' . $ruling . '.' : 'Not found.';
                $msgType = $stmt->rowCount() ? 'success' : 'error';
            }
        } else {
            $msg = 'Unauthorized or unknown action.'; $msgType = 'error';
        }
    }
    $_SESSION['csrf_mp'] = bin2hex(random_bytes(32));
    $csrf = $_SESSION['csrf_mp'];
}

// ── Data Fetch ──
$tab = $_GET['tab'] ?? 'hq';
$personnel  = $db->query("SELECT mp.*, CONCAT(c.firstname,' ',c.lastname) AS mp_name FROM mp_personnel mp LEFT JOIN tblclients c ON c.id = mp.client_id ORDER BY FIELD(mp.mp_rank,'provost_marshal','mp_officer','mp_sergeant','mp_private'), mp.joined_at")->fetchAll(PDO::FETCH_ASSOC);
$patrols    = $db->query("SELECT p.*, CONCAT(c.firstname,' ',c.lastname) AS mp_name FROM mp_patrols p LEFT JOIN tblclients c ON c.id = p.mp_id ORDER BY p.started_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$citations  = $db->query("SELECT ct.*, CONCAT(c.firstname,' ',c.lastname) AS cited_name, CONCAT(c2.firstname,' ',c2.lastname) AS issuer_name FROM mp_citations ct LEFT JOIN tblclients c ON c.id = ct.cited_client_id LEFT JOIN tblclients c2 ON c2.id = ct.issuing_mp_id ORDER BY ct.issued_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$investigations = $db->query("SELECT inv.*, CONCAT(c.firstname,' ',c.lastname) AS investigator_name, CONCAT(c2.firstname,' ',c2.lastname) AS subject_name FROM mp_investigations inv LEFT JOIN tblclients c ON c.id = inv.lead_investigator_id LEFT JOIN tblclients c2 ON c2.id = inv.subject_client_id ORDER BY inv.opened_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$detentions = $db->query("SELECT d.*, CONCAT(c.firstname,' ',c.lastname) AS detained_name, CONCAT(c2.firstname,' ',c2.lastname) AS detainer_name FROM mp_detention_log d LEFT JOIN tblclients c ON c.id = d.detained_client_id LEFT JOIN tblclients c2 ON c2.id = d.detaining_mp_id ORDER BY d.detained_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$activePatrols = count(array_filter($patrols, fn($p) => $p['status'] === 'active'));
$openInvest = count(array_filter($investigations, fn($i) => in_array($i['status'], ['open','active'])));
$activeDetentions = count(array_filter($detentions, fn($d) => $d['released_at'] === null));

$pageTitle = 'Military Police (MP Corps)';
include __DIR__ . '/includes/site-header.inc.php';
?>
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<style>
.mp-wrap{max-width:1100px;margin:0 auto;padding:1.5rem}
.mp-bg{background:#0a0a14;min-height:100vh;color:#e2e8f0}
.mp-card{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:10px;padding:1.25rem;margin-bottom:1rem}
.mp-card:hover{border-color:#ef4444;box-shadow:0 0 12px rgba(239,68,68,.12)}
.mp-title{font-size:1.6rem;font-weight:700;color:#f1f5f9;margin-bottom:.25rem;display:flex;align-items:center;gap:.5rem}
.mp-sub{color:#94a3b8;font-size:.85rem}
.mp-badge{display:inline-block;padding:2px 10px;border-radius:999px;font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
.mp-tabs{display:flex;gap:.5rem;margin-bottom:1.25rem;flex-wrap:wrap}
.mp-tab{padding:.4rem 1rem;border-radius:6px;cursor:pointer;font-size:.8rem;background:#2a2a4a;color:#94a3b8;text-decoration:none;font-weight:600;border:none}
.mp-tab.active{background:#ef4444;color:#fff}
.mp-stat-bar{display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem}
.mp-stat{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:8px;padding:.75rem 1.25rem;flex:1;min-width:120px;text-align:center}
.mp-stat .val{font-size:1.5rem;font-weight:700;color:#ef4444}
.mp-stat .lbl{font-size:.7rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px}
.mp-btn{background:#ef4444;color:#fff;border:none;padding:.5rem 1.25rem;border-radius:6px;cursor:pointer;font-size:.85rem;font-weight:600;transition:.2s}
.mp-btn:hover{background:#dc2626}
.mp-btn-sm{padding:.3rem .75rem;font-size:.75rem}
.mp-btn-outline{background:transparent;border:1px solid #ef4444;color:#ef4444}
.mp-btn-outline:hover{background:#ef4444;color:#fff}
.mp-btn-green{background:#22c55e;color:#fff}.mp-btn-green:hover{background:#16a34a}
.mp-btn-blue{background:#3b82f6;color:#fff}.mp-btn-blue:hover{background:#2563eb}
.mp-btn-gold{background:#d4a017;color:#000}.mp-btn-gold:hover{background:#e2b340}
.mp-input,.mp-select,.mp-textarea{width:100%;background:#0a0a14;border:1px solid #2a2a4a;color:#e2e8f0;padding:.5rem .75rem;border-radius:6px;font-size:.85rem;box-sizing:border-box}
.mp-textarea{min-height:100px;resize:vertical}
.mp-label{display:block;color:#94a3b8;font-size:.75rem;margin-bottom:.25rem;text-transform:uppercase;letter-spacing:.5px}
.mp-msg{padding:.75rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:.85rem}
.mp-msg-success{background:rgba(34,197,94,.12);border:1px solid #22c55e;color:#86efac}
.mp-msg-error{background:rgba(239,68,68,.12);border:1px solid #ef4444;color:#fca5a5}
.mp-modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:900;align-items:center;justify-content:center}
.mp-modal-bg.open{display:flex}
.mp-modal{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:12px;padding:1.5rem;width:90%;max-width:580px;max-height:80vh;overflow-y:auto}
.mp-modal h3{color:#f1f5f9;margin:0 0 1rem}
.mp-form-row{margin-bottom:.75rem}
.mp-row{display:flex;align-items:center;gap:1rem;padding:.75rem 1rem;background:#0a0a14;border:1px solid #2a2a4a;border-radius:8px;margin-bottom:.5rem}
</style>
<div class="mp-bg">
<div class="mp-wrap">
    <div class="mp-title"><i class="fas fa-shield-halved"></i> Military Police — MP Corps</div>
    <p class="mp-sub" style="margin-bottom:1.25rem">Law enforcement, patrols, citations, investigations, and detention — NCO+ rank</p>

    <?php if ($msg): ?><div class="mp-msg mp-msg-<?= $msgType ?>"><?= $msg ?></div><?php endif; ?>

    <?php if ($isMP && $myMP): ?>
        <div class="mp-card" style="border-left:4px solid #ef4444;display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
            <i class="fas fa-id-badge" style="font-size:2.5rem;color:#ef4444"></i>
            <div>
                <div style="font-family:monospace;color:#ef4444;font-size:.8rem;letter-spacing:.1em"><?= htmlspecialchars($myMP['badge_number']) ?></div>
                <div style="color:#f1f5f9;font-size:.95rem;font-weight:600"><?= strtoupper(str_replace('_', ' ', $myMP['mp_rank'])) ?></div>
                <div style="font-size:.8rem;color:#94a3b8"><?= $myMP['assigned_zone'] ? 'Zone: ' . htmlspecialchars($myMP['assigned_zone']) : 'No assigned zone' ?></div>
            </div>
        </div>
    <?php elseif ($isNCO && !$isMP): ?>
        <div class="mp-card" style="text-align:center">
            <p style="color:#94a3b8;margin-bottom:.5rem">You are eligible to join the MP Corps.</p>
            <form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="enlist_mp"><button class="mp-btn"><i class="fas fa-shield-halved"></i> Enlist in MP Corps</button></form>
        </div>
    <?php endif; ?>

    <div class="mp-stat-bar">
        <div class="mp-stat"><div class="val"><?= count($personnel) ?></div><div class="lbl">MP Personnel</div></div>
        <div class="mp-stat"><div class="val" style="color:#22c55e"><?= $activePatrols ?></div><div class="lbl">Active Patrols</div></div>
        <div class="mp-stat"><div class="val" style="color:#f59e0b"><?= count($citations) ?></div><div class="lbl">Citations</div></div>
        <div class="mp-stat"><div class="val" style="color:#3b82f6"><?= $openInvest ?></div><div class="lbl">Open Investigations</div></div>
        <div class="mp-stat"><div class="val" style="color:#ef4444"><?= $activeDetentions ?></div><div class="lbl">In Detention</div></div>
    </div>

    <div class="mp-tabs">
        <a href="?tab=hq" class="mp-tab <?= $tab==='hq'?'active':'' ?>"><i class="fas fa-building-shield"></i> HQ</a>
        <a href="?tab=patrols" class="mp-tab <?= $tab==='patrols'?'active':'' ?>"><i class="fas fa-person-walking"></i> Patrols</a>
        <a href="?tab=citations" class="mp-tab <?= $tab==='citations'?'active':'' ?>"><i class="fas fa-file-lines"></i> Citations</a>
        <a href="?tab=investigations" class="mp-tab <?= $tab==='investigations'?'active':'' ?>"><i class="fas fa-magnifying-glass"></i> Investigations</a>
        <a href="?tab=detention" class="mp-tab <?= $tab==='detention'?'active':'' ?>"><i class="fas fa-lock"></i> Detention</a>
    </div>

    <!-- ═══ TAB: HQ ═══ -->
    <?php if ($tab === 'hq'): ?>
        <h3 style="color:#f1f5f9;font-size:1rem;margin-bottom:.75rem"><i class="fas fa-users"></i> MP Personnel Roster</h3>
        <?php
        $rankColors = ['provost_marshal'=>'#d4a017','mp_officer'=>'#3b82f6','mp_sergeant'=>'#22c55e','mp_private'=>'#94a3b8'];
        foreach ($personnel as $mp): ?>
            <div class="mp-row">
                <div style="flex:0 0 40px;text-align:center"><i class="fas fa-user-shield" style="font-size:1.3rem;color:<?= $rankColors[$mp['mp_rank']] ?>"></i></div>
                <div style="flex:1">
                    <strong style="color:#f1f5f9"><?= htmlspecialchars($mp['mp_name'] ?? 'Unknown') ?></strong>
                    <span style="font-family:monospace;color:#ef4444;font-size:.75rem;margin-left:.5rem"><?= htmlspecialchars($mp['badge_number']) ?></span>
                    <div style="color:#94a3b8;font-size:.75rem"><?= strtoupper(str_replace('_', ' ', $mp['mp_rank'])) ?> &bull; <?= $mp['assigned_zone'] ? 'Zone: ' . htmlspecialchars($mp['assigned_zone']) : 'Unassigned' ?> &bull; Since <?= date('M j, Y', strtotime($mp['joined_at'])) ?></div>
                </div>
                <span class="mp-badge" style="background:<?= $mp['status']==='active'?'#22c55e':'#ef4444' ?>20;color:<?= $mp['status']==='active'?'#22c55e':'#ef4444' ?>"><?= strtoupper($mp['status']) ?></span>
            </div>
        <?php endforeach; ?>
        <?php if (empty($personnel)): ?><div class="mp-card" style="text-align:center;color:#64748b"><p>No MP personnel enlisted.</p></div><?php endif; ?>

    <!-- ═══ TAB: PATROLS ═══ -->
    <?php elseif ($tab === 'patrols'): ?>
        <?php if ($isMP): ?>
            <div style="margin-bottom:1rem;display:flex;gap:.5rem;flex-wrap:wrap">
                <form method="POST" style="display:flex;gap:.5rem;align-items:center">
                    <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="start_patrol">
                    <input type="text" name="patrol_zone" class="mp-input" style="width:200px" placeholder="Zone name..." required>
                    <button class="mp-btn"><i class="fas fa-play"></i> Start Patrol</button>
                </form>
            </div>
        <?php endif; ?>
        <?php
        $patColors = ['active'=>'#22c55e','completed'=>'#64748b','abandoned'=>'#ef4444'];
        foreach ($patrols as $pat): ?>
            <div class="mp-card">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div><i class="fas fa-route" style="color:#ef4444"></i> <strong style="color:#f1f5f9"><?= htmlspecialchars($pat['zone']) ?></strong></div>
                    <span class="mp-badge" style="background:<?= $patColors[$pat['status']] ?>20;color:<?= $patColors[$pat['status']] ?>;border:1px solid <?= $patColors[$pat['status']] ?>40"><?= strtoupper($pat['status']) ?></span>
                </div>
                <div style="color:#94a3b8;font-size:.8rem;margin-top:.25rem">
                    Officer: <?= htmlspecialchars($pat['mp_name'] ?? 'Unknown') ?> &bull; Started: <?= date('M j H:i', strtotime($pat['started_at'])) ?>
                    <?php if ($pat['ended_at']): ?>&bull; Ended: <?= date('M j H:i', strtotime($pat['ended_at'])) ?><?php endif; ?>
                    &bull; Incidents: <?= (int)$pat['incidents_reported'] ?>
                </div>
                <?php if ($pat['report']): ?><p style="color:#cbd5e1;font-size:.85rem;margin-top:.5rem"><?= htmlspecialchars($pat['report']) ?></p><?php endif; ?>
                <?php if ($pat['status'] === 'active' && $pat['mp_id'] == $clientId): ?>
                    <form method="POST" style="margin-top:.5rem;display:flex;gap:.5rem;flex-wrap:wrap;align-items:flex-end">
                        <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="end_patrol"><input type="hidden" name="patrol_id" value="<?= $pat['id'] ?>">
                        <div><label class="mp-label">Incidents</label><input type="number" name="incidents" class="mp-input" style="width:70px" value="0" min="0"></div>
                        <div style="flex:1"><label class="mp-label">Report</label><input type="text" name="patrol_report" class="mp-input" placeholder="Patrol summary..."></div>
                        <button class="mp-btn-sm mp-btn mp-btn-green"><i class="fas fa-stop"></i> End Patrol</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

    <!-- ═══ TAB: CITATIONS ═══ -->
    <?php elseif ($tab === 'citations'): ?>
        <?php if ($isMP): ?>
            <div style="margin-bottom:1rem"><button class="mp-btn" onclick="document.getElementById('modalCitation').classList.add('open')"><i class="fas fa-file-lines"></i> Issue Citation</button></div>
        <?php endif; ?>
        <?php
        $citColors = ['warning'=>'#f59e0b','minor'=>'#ef4444','major'=>'#dc2626'];
        foreach ($citations as $ct): ?>
            <div class="mp-card" style="border-left:3px solid <?= $citColors[$ct['citation_type']] ?>">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div>
                        <span class="mp-badge" style="background:<?= $citColors[$ct['citation_type']] ?>20;color:<?= $citColors[$ct['citation_type']] ?>;border:1px solid <?= $citColors[$ct['citation_type']] ?>40"><?= strtoupper($ct['citation_type']) ?></span>
                        <strong style="color:#f1f5f9;margin-left:.5rem"><?= htmlspecialchars($ct['cited_name'] ?? 'Unknown') ?></strong>
                    </div>
                    <?php if ($ct['appeal_status'] !== 'none'): ?>
                        <span class="mp-badge" style="background:#8b5cf620;color:#8b5cf6">APPEAL: <?= strtoupper($ct['appeal_status']) ?></span>
                    <?php endif; ?>
                </div>
                <p style="color:#cbd5e1;font-size:.85rem;margin-top:.5rem"><?= htmlspecialchars($ct['violation']) ?></p>
                <div style="display:flex;gap:1rem;margin-top:.25rem;font-size:.8rem;color:#94a3b8">
                    <span>By: <?= htmlspecialchars($ct['issuer_name'] ?? 'Unknown') ?></span>
                    <span>XP Penalty: <strong style="color:#ef4444"><?= (int)$ct['xp_penalty'] ?></strong></span>
                    <span>Rank Freeze: <strong style="color:#f59e0b"><?= (int)$ct['rank_freeze_hours'] ?>h</strong></span>
                    <span><?= date('M j, Y', strtotime($ct['issued_at'])) ?></span>
                </div>
                <?php if ($ct['cited_client_id'] == $clientId && !$ct['appealed']): ?>
                    <form method="POST" style="margin-top:.5rem;display:flex;gap:.5rem;align-items:center">
                        <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="appeal_citation"><input type="hidden" name="cit_id" value="<?= $ct['id'] ?>">
                        <input type="text" name="appeal_text" class="mp-input" style="flex:1" placeholder="Grounds for appeal..." required>
                        <button class="mp-btn-sm mp-btn mp-btn-blue"><i class="fas fa-scale-balanced"></i> Appeal</button>
                    </form>
                <?php endif; ?>
                <?php if ($ct['appeal_status'] === 'pending' && $isFlag): ?>
                    <div style="margin-top:.5rem;display:flex;gap:.5rem">
                        <form method="POST" style="display:inline"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="rule_appeal"><input type="hidden" name="cit_id" value="<?= $ct['id'] ?>"><input type="hidden" name="appeal_ruling" value="upheld"><button class="mp-btn-sm mp-btn mp-btn-green">Uphold</button></form>
                        <form method="POST" style="display:inline"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="rule_appeal"><input type="hidden" name="cit_id" value="<?= $ct['id'] ?>"><input type="hidden" name="appeal_ruling" value="overturned"><button class="mp-btn-sm mp-btn" style="background:#f59e0b;color:#000">Overturn</button></form>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if (empty($citations)): ?><div class="mp-card" style="text-align:center;color:#64748b"><p>No citations issued.</p></div><?php endif; ?>

    <!-- ═══ TAB: INVESTIGATIONS ═══ -->
    <?php elseif ($tab === 'investigations'): ?>
        <?php if ($isMP || $isOfficer): ?>
            <div style="margin-bottom:1rem"><button class="mp-btn mp-btn-blue" onclick="document.getElementById('modalInvestigation').classList.add('open')"><i class="fas fa-magnifying-glass"></i> Open Investigation</button></div>
        <?php endif; ?>
        <?php
        $invColors = ['open'=>'#f59e0b','active'=>'#3b82f6','closed'=>'#64748b','referred_to_court'=>'#ef4444'];
        foreach ($investigations as $inv): ?>
            <div class="mp-card">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div><strong style="color:#ef4444;font-size:.8rem"><?= htmlspecialchars($inv['case_code']) ?></strong> <span style="color:#f1f5f9">v. <?= htmlspecialchars($inv['subject_name'] ?? 'Unknown') ?></span></div>
                    <span class="mp-badge" style="background:<?= $invColors[$inv['status']] ?>20;color:<?= $invColors[$inv['status']] ?>;border:1px solid <?= $invColors[$inv['status']] ?>40"><?= strtoupper(str_replace('_', ' ', $inv['status'])) ?></span>
                </div>
                <div style="color:#94a3b8;font-size:.8rem;margin-top:.25rem">Lead: <?= htmlspecialchars($inv['investigator_name'] ?? 'Unknown') ?> &bull; Opened: <?= date('M j, Y', strtotime($inv['opened_at'])) ?></div>
                <p style="color:#cbd5e1;font-size:.85rem;margin-top:.5rem"><strong>Charges:</strong> <?= htmlspecialchars($inv['charge']) ?></p>
                <?php if ($inv['evidence']): ?><p style="color:#94a3b8;font-size:.8rem;margin-top:.25rem"><strong>Evidence:</strong> <?= htmlspecialchars($inv['evidence']) ?></p><?php endif; ?>
                <?php if ($inv['outcome']): ?><p style="color:#86efac;font-size:.85rem;margin-top:.5rem;border-top:1px solid #2a2a4a;padding-top:.5rem"><strong>Outcome:</strong> <?= htmlspecialchars($inv['outcome']) ?></p><?php endif; ?>
                <?php if (in_array($inv['status'], ['open','active']) && ($isMP || $isOfficer)): ?>
                    <button class="mp-btn-sm mp-btn mp-btn-gold" style="margin-top:.5rem" onclick="openCloseInv(<?= $inv['id'] ?>,'<?= htmlspecialchars($inv['case_code'], ENT_QUOTES) ?>')"><i class="fas fa-folder-closed"></i> Close/Refer</button>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if (empty($investigations)): ?><div class="mp-card" style="text-align:center;color:#64748b"><p>No investigations.</p></div><?php endif; ?>

    <!-- ═══ TAB: DETENTION ═══ -->
    <?php elseif ($tab === 'detention'): ?>
        <?php if ($isMP): ?>
            <div style="margin-bottom:1rem"><button class="mp-btn" onclick="document.getElementById('modalDetain').classList.add('open')"><i class="fas fa-lock"></i> Detain Subject</button></div>
        <?php endif; ?>
        <?php foreach ($detentions as $det): ?>
            <div class="mp-card" style="border-left:3px solid <?= $det['released_at'] ? '#64748b' : '#ef4444' ?>">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div><i class="fas fa-<?= $det['released_at']?'lock-open':'lock' ?>" style="color:<?= $det['released_at']?'#64748b':'#ef4444' ?>"></i> <strong style="color:#f1f5f9"><?= htmlspecialchars($det['detained_name'] ?? 'Unknown') ?></strong></div>
                    <?php if ($det['released_at']): ?>
                        <span class="mp-badge" style="background:#64748b20;color:#64748b">RELEASED (<?= (int)$det['duration_hours'] ?>h)</span>
                    <?php else: ?>
                        <?php $hours = (int)((time() - strtotime($det['detained_at'])) / 3600); ?>
                        <span class="mp-badge" style="background:#ef444420;color:#ef4444"><?= $hours >= 72 ? '⚠️ OVER 72H' : "DETAINED ({$hours}h)" ?></span>
                    <?php endif; ?>
                </div>
                <p style="color:#cbd5e1;font-size:.85rem;margin-top:.5rem"><strong>Reason:</strong> <?= htmlspecialchars($det['reason']) ?></p>
                <div style="color:#94a3b8;font-size:.8rem;margin-top:.25rem">By: <?= htmlspecialchars($det['detainer_name'] ?? 'Unknown') ?> &bull; <?= date('M j H:i', strtotime($det['detained_at'])) ?></div>
                <?php if (!$det['released_at'] && $isMP): ?>
                    <form method="POST" style="margin-top:.5rem;display:flex;gap:.5rem;align-items:center">
                        <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="release"><input type="hidden" name="det_id" value="<?= $det['id'] ?>">
                        <input type="text" name="release_notes" class="mp-input" style="flex:1" placeholder="Release notes...">
                        <button class="mp-btn-sm mp-btn mp-btn-green"><i class="fas fa-lock-open"></i> Release</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if (empty($detentions)): ?><div class="mp-card" style="text-align:center;color:#64748b"><p>No detention records.</p></div><?php endif; ?>
    <?php endif; ?>
</div>
</div>

<!-- Modals -->
<div class="mp-modal-bg" id="modalCitation"><div class="mp-modal"><h3><i class="fas fa-file-lines"></i> Issue Citation</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="issue_citation">
<div class="mp-form-row"><label class="mp-label">Cited Client ID</label><input type="number" name="cited_client_id" class="mp-input" required min="1"></div>
<div class="mp-form-row"><label class="mp-label">Citation Type</label><select name="citation_type" class="mp-select"><option value="warning">Warning (no penalty)</option><option value="minor">Minor (50+ XP, 24h freeze)</option><option value="major">Major (200+ XP, 72h freeze)</option></select></div>
<div class="mp-form-row"><label class="mp-label">Violation</label><textarea name="violation" class="mp-textarea" required></textarea></div>
<div style="display:flex;gap:.75rem"><div class="mp-form-row" style="flex:1"><label class="mp-label">XP Penalty</label><input type="number" name="xp_penalty" class="mp-input" value="0" min="0"></div><div class="mp-form-row" style="flex:1"><label class="mp-label">Rank Freeze (hours)</label><input type="number" name="rank_freeze" class="mp-input" value="0" min="0"></div></div>
<div class="mp-form-row"><label class="mp-label">Notes</label><input type="text" name="citation_notes" class="mp-input"></div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="mp-btn mp-btn-outline" onclick="this.closest('.mp-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="mp-btn"><i class="fas fa-stamp"></i> Issue</button></div></form></div></div>

<div class="mp-modal-bg" id="modalInvestigation"><div class="mp-modal"><h3><i class="fas fa-magnifying-glass"></i> Open Investigation</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="open_investigation">
<div class="mp-form-row"><label class="mp-label">Subject Client ID</label><input type="number" name="subject_id" class="mp-input" required min="1"></div>
<div class="mp-form-row"><label class="mp-label">Charges</label><textarea name="charge" class="mp-textarea" required placeholder="Describe charges..."></textarea></div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="mp-btn mp-btn-outline" onclick="this.closest('.mp-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="mp-btn mp-btn-blue"><i class="fas fa-folder-open"></i> Open Case</button></div></form></div></div>

<div class="mp-modal-bg" id="modalDetain"><div class="mp-modal"><h3><i class="fas fa-lock"></i> Detain Subject</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="detain">
<div class="mp-form-row"><label class="mp-label">Subject Client ID</label><input type="number" name="detained_id" class="mp-input" required min="1"></div>
<div class="mp-form-row"><label class="mp-label">Reason</label><textarea name="detain_reason" class="mp-textarea" required></textarea></div>
<div style="background:#ef444410;border:1px solid #ef444430;border-radius:6px;padding:.75rem;margin-bottom:.75rem;font-size:.8rem;color:#fca5a5"><i class="fas fa-exclamation-triangle"></i> Maximum 72 hours without formal charges per Bill of Rights Article 3.</div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="mp-btn mp-btn-outline" onclick="this.closest('.mp-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="mp-btn"><i class="fas fa-lock"></i> Detain</button></div></form></div></div>

<div class="mp-modal-bg" id="modalCloseInv"><div class="mp-modal"><h3><i class="fas fa-folder-closed"></i> Close Investigation</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="close_investigation"><input type="hidden" name="inv_id" id="closeInvId" value="">
<div style="color:#94a3b8;font-size:.85rem;margin-bottom:1rem">Case: <strong id="closeInvCode" style="color:#ef4444"></strong></div>
<div class="mp-form-row"><label class="mp-label">Outcome</label><textarea name="outcome" class="mp-textarea" required></textarea></div>
<div class="mp-form-row"><label class="mp-label">Evidence Summary</label><textarea name="evidence" class="mp-textarea" style="min-height:60px"></textarea></div>
<div class="mp-form-row"><label style="color:#f59e0b;font-size:.85rem"><input type="checkbox" name="refer_to_court" value="1"> Refer to Military Court</label></div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="mp-btn mp-btn-outline" onclick="this.closest('.mp-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="mp-btn mp-btn-gold"><i class="fas fa-stamp"></i> Close</button></div></form></div></div>

<script>
function openCloseInv(id, code) { document.getElementById('closeInvId').value = id; document.getElementById('closeInvCode').textContent = code; document.getElementById('modalCloseInv').classList.add('open'); }
document.querySelectorAll('.mp-modal-bg').forEach(bg => { bg.addEventListener('click', e => { if (e.target === bg) bg.classList.remove('open'); }); });
</script>
<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
