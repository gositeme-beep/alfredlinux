<?php
/**
 * ═══════════════════════════════════════════
 *  Judge Advocate General (JAG Corps) — Level 6: Sovereign State
 * ═══════════════════════════════════════════
 */
require_once __DIR__ . '/includes/db-config.inc.php';
$db = getSharedDB();
require_once __DIR__ . '/includes/auth-gate.inc.php';
require_once __DIR__ . '/includes/rank-guard.inc.php';
if (empty($_SESSION['csrf_jag'])) $_SESSION['csrf_jag'] = bin2hex(random_bytes(32));
requireRank(1);

$isCommander = ($clientId === 33);
$isFlag      = ($userRankTier >= 9) || $isCommander;
$isOfficer   = ($userRankTier >= 6) || $isCommander;
$isNCO       = ($userRankTier >= 4) || $isCommander;
$msg = '';
$msgType = '';

// ── Auto-create tables ──
$db->exec("CREATE TABLE IF NOT EXISTS jag_attorneys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL UNIQUE,
    bar_number VARCHAR(20) NOT NULL,
    specialty ENUM('prosecution','defense','general','advisory') DEFAULT 'general',
    status ENUM('active','suspended','retired') DEFAULT 'active',
    cases_tried INT DEFAULT 0,
    win_rate DECIMAL(5,2) DEFAULT 0.00,
    admitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS jag_cases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_code VARCHAR(20) NOT NULL,
    prosecution_attorney_id INT DEFAULT NULL,
    defense_attorney_id INT DEFAULT NULL,
    defendant_client_id INT DEFAULT NULL,
    status ENUM('assigned','discovery','pretrial','trial','verdict','sentencing','appeal_window','closed','appealed') DEFAULT 'assigned',
    charges TEXT NOT NULL,
    plea ENUM('not_guilty','guilty','no_contest') DEFAULT 'not_guilty',
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    trial_start TIMESTAMP NULL,
    verdict_at TIMESTAMP NULL,
    sentence TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS jag_evidence (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    evidence_type ENUM('document','testimony','digital','physical') DEFAULT 'document',
    description TEXT NOT NULL,
    submitted_by INT NOT NULL,
    classification ENUM('public','confidential','sealed') DEFAULT 'public',
    admitted TINYINT(1) DEFAULT 0,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS jag_rulings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    ruling_type ENUM('verdict','sentence','motion','procedural') DEFAULT 'verdict',
    ruling TEXT NOT NULL,
    ruled_by INT NOT NULL,
    is_precedent TINYINT(1) DEFAULT 0,
    ruled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS jag_appeals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_case_id INT NOT NULL,
    appellant_id INT NOT NULL,
    appeal_type ENUM('court_of_military_appeals','supreme_military_court') DEFAULT 'court_of_military_appeals',
    grounds TEXT NOT NULL,
    status ENUM('filed','hearing','upheld','overturned','remanded') DEFAULT 'filed',
    filed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    heard_at TIMESTAMP NULL,
    decided_at TIMESTAMP NULL,
    panel_members TEXT DEFAULT NULL,
    decision TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS jag_tribunals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tribunal_code VARCHAR(20) NOT NULL,
    charges TEXT NOT NULL,
    accused_id INT NOT NULL,
    tribunal_type ENUM('standard','war_crimes') DEFAULT 'standard',
    panel_members TEXT DEFAULT NULL,
    status ENUM('convened','hearing','deliberating','verdict') DEFAULT 'convened',
    convened_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verdict TEXT DEFAULT NULL,
    sentence TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$csrf = $_SESSION['csrf_jag'];

// ── Check if attorney ──
$attCheck = $db->prepare("SELECT * FROM jag_attorneys WHERE client_id = ? AND status = 'active'");
$attCheck->execute([$clientId]);
$myBar = $attCheck->fetch(PDO::FETCH_ASSOC);
$isAttorney = (bool)$myBar;

// ── POST Handlers ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, $_POST['csrf'] ?? '')) {
        $msg = 'Invalid security token.'; $msgType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'admit_attorney' && $isOfficer && !$isAttorney) {
            $specialty = $_POST['specialty'] ?? 'general';
            $validSpec = ['prosecution','defense','general','advisory'];
            if (!in_array($specialty, $validSpec, true)) { $msg = 'Invalid specialty.'; $msgType = 'error'; }
            else {
                $barNum = 'JAG-' . str_pad(mt_rand(1000, 9999), 4, '0', STR_PAD_LEFT);
                $db->prepare("INSERT INTO jag_attorneys (client_id, bar_number, specialty) VALUES (?,?,?)")->execute([$clientId, $barNum, $specialty]);
                awardXP($clientId, 'jag_admitted', ['bar' => $barNum]);
                $msg = "Admitted to JAG Bar. Number: <strong>$barNum</strong>."; $msgType = 'success';
                $myBar = ['bar_number' => $barNum, 'specialty' => $specialty]; $isAttorney = true;
            }
        } elseif ($action === 'assign_case' && $isFlag) {
            $charges   = trim($_POST['charges'] ?? '');
            $defId     = (int)($_POST['defendant_id'] ?? 0);
            $prosAttId = (int)($_POST['pros_attorney'] ?? 0);
            $defAttId  = (int)($_POST['def_attorney'] ?? 0);
            if ($charges === '' || $defId < 1) {
                $msg = 'Charges and defendant required.'; $msgType = 'error';
            } else {
                $code = 'JAG-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
                $db->prepare("INSERT INTO jag_cases (case_code, prosecution_attorney_id, defense_attorney_id, defendant_client_id, charges) VALUES (?,?,?,?,?)")
                   ->execute([$code, $prosAttId ?: null, $defAttId ?: null, $defId, $charges]);
                awardXP($clientId, 'case_assigned', ['code' => $code]);
                $msg = "Case <strong>$code</strong> assigned."; $msgType = 'success';
            }
        } elseif ($action === 'submit_evidence' && $isAttorney) {
            $caseId   = (int)($_POST['case_id'] ?? 0);
            $evType   = $_POST['evidence_type'] ?? 'document';
            $desc     = trim($_POST['ev_desc'] ?? '');
            $class    = $_POST['ev_class'] ?? 'public';
            $validET  = ['document','testimony','digital','physical'];
            $validEC  = ['public','confidential','sealed'];
            if ($caseId < 1 || $desc === '' || !in_array($evType, $validET, true) || !in_array($class, $validEC, true)) {
                $msg = 'All evidence fields required.'; $msgType = 'error';
            } else {
                $db->prepare("INSERT INTO jag_evidence (case_id, evidence_type, description, submitted_by, classification) VALUES (?,?,?,?,?)")
                   ->execute([$caseId, $evType, $desc, $clientId, $class]);
                $msg = "Evidence submitted."; $msgType = 'success';
            }
        } elseif ($action === 'enter_plea') {
            $caseId = (int)($_POST['case_id'] ?? 0);
            $plea   = $_POST['plea'] ?? 'not_guilty';
            $validP = ['not_guilty','guilty','no_contest'];
            if (!in_array($plea, $validP, true)) {
                $msg = 'Invalid plea.'; $msgType = 'error';
            } else {
                $stmt = $db->prepare("UPDATE jag_cases SET plea = ?, status = 'pretrial' WHERE id = ? AND defendant_client_id = ? AND status IN ('assigned','discovery')");
                $stmt->execute([$plea, $caseId, $clientId]);
                $msg = $stmt->rowCount() ? "Plea of <strong>" . strtoupper(str_replace('_', ' ', $plea)) . "</strong> entered." : 'Case not found.';
                $msgType = $stmt->rowCount() ? 'success' : 'error';
            }
        } elseif ($action === 'advance_case' && ($isAttorney || $isFlag)) {
            $caseId = (int)($_POST['case_id'] ?? 0);
            $newStatus = $_POST['new_status'] ?? '';
            $validCS = ['discovery','pretrial','trial','verdict','sentencing','appeal_window','closed'];
            if (!in_array($newStatus, $validCS, true)) {
                $msg = 'Invalid status.'; $msgType = 'error';
            } else {
                $updates = ["status = ?"];
                $params = [$newStatus];
                if ($newStatus === 'trial') { $updates[] = "trial_start = NOW()"; }
                if ($newStatus === 'verdict') { $updates[] = "verdict_at = NOW()"; }
                $params[] = $caseId;
                $stmt = $db->prepare("UPDATE jag_cases SET " . implode(', ', $updates) . " WHERE id = ?");
                $stmt->execute($params);
                $msg = $stmt->rowCount() ? "Case advanced to <strong>" . strtoupper(str_replace('_', ' ', $newStatus)) . "</strong>." : 'Not found.';
                $msgType = $stmt->rowCount() ? 'success' : 'error';
            }
        } elseif ($action === 'issue_ruling' && $isFlag) {
            $caseId    = (int)($_POST['case_id'] ?? 0);
            $rulingType = $_POST['ruling_type'] ?? 'verdict';
            $ruling    = trim($_POST['ruling'] ?? '');
            $precedent = !empty($_POST['is_precedent']);
            $sentence  = trim($_POST['sentence'] ?? '');
            $validRT = ['verdict','sentence','motion','procedural'];
            if ($caseId < 1 || $ruling === '' || !in_array($rulingType, $validRT, true)) {
                $msg = 'Ruling details required.'; $msgType = 'error';
            } else {
                $db->prepare("INSERT INTO jag_rulings (case_id, ruling_type, ruling, ruled_by, is_precedent) VALUES (?,?,?,?,?)")
                   ->execute([$caseId, $rulingType, $ruling, $clientId, $precedent ? 1 : 0]);
                if ($rulingType === 'verdict') {
                    $db->prepare("UPDATE jag_cases SET status = 'verdict', verdict_at = NOW() WHERE id = ?")->execute([$caseId]);
                }
                if ($rulingType === 'sentence' && $sentence !== '') {
                    $db->prepare("UPDATE jag_cases SET status = 'sentencing', sentence = ? WHERE id = ?")->execute([$sentence, $caseId]);
                }
                awardXP($clientId, 'ruling_issued', ['type' => $rulingType]);
                $msg = strtoupper($rulingType) . " ruling issued." . ($precedent ? ' <strong>(PRECEDENT-SETTING)</strong>' : ''); $msgType = 'success';
            }
        } elseif ($action === 'file_appeal') {
            $caseId  = (int)($_POST['case_id'] ?? 0);
            $appType = $_POST['appeal_type'] ?? 'court_of_military_appeals';
            $grounds = trim($_POST['grounds'] ?? '');
            $validAT = ['court_of_military_appeals','supreme_military_court'];
            if ($appType === 'supreme_military_court' && !$isFlag) {
                $msg = 'Supreme Military Court appeals require General+ rank.'; $msgType = 'error';
            } elseif ($grounds === '' || !in_array($appType, $validAT, true)) {
                $msg = 'Grounds and valid appeal type required.'; $msgType = 'error';
            } else {
                $db->prepare("INSERT INTO jag_appeals (original_case_id, appellant_id, appeal_type, grounds) VALUES (?,?,?,?)")
                   ->execute([$caseId, $clientId, $appType, $grounds]);
                $db->prepare("UPDATE jag_cases SET status = 'appealed' WHERE id = ?")->execute([$caseId]);
                $msg = "Appeal filed to <strong>" . strtoupper(str_replace('_', ' ', $appType)) . "</strong>."; $msgType = 'success';
            }
        } elseif ($action === 'decide_appeal' && $isFlag) {
            $appId    = (int)($_POST['app_id'] ?? 0);
            $decision = $_POST['decision'] ?? '';
            $decisionText = trim($_POST['decision_text'] ?? '');
            $validAD = ['upheld','overturned','remanded'];
            if (!in_array($decision, $validAD, true) || $decisionText === '') {
                $msg = 'Decision and rationale required.'; $msgType = 'error';
            } else {
                $stmt = $db->prepare("UPDATE jag_appeals SET status = ?, decided_at = NOW(), decision = ? WHERE id = ? AND status IN ('filed','hearing')");
                $stmt->execute([$decision, $decisionText, $appId]);
                $msg = $stmt->rowCount() ? "Appeal <strong>" . strtoupper($decision) . "</strong>." : 'Not found.';
                $msgType = $stmt->rowCount() ? 'success' : 'error';
            }
        } elseif ($action === 'convene_tribunal' && $isCommander) {
            $charges  = trim($_POST['trib_charges'] ?? '');
            $accused  = (int)($_POST['accused_id'] ?? 0);
            $tribType = $_POST['tribunal_type'] ?? 'standard';
            $panel    = trim($_POST['panel'] ?? '');
            $validTT  = ['standard','war_crimes'];
            if ($charges === '' || $accused < 1 || !in_array($tribType, $validTT, true)) {
                $msg = 'All tribunal fields required.'; $msgType = 'error';
            } else {
                $code = 'TRIB-' . strtoupper(bin2hex(random_bytes(3)));
                $db->prepare("INSERT INTO jag_tribunals (tribunal_code, charges, accused_id, tribunal_type, panel_members) VALUES (?,?,?,?,?)")
                   ->execute([$code, $charges, $accused, $tribType, $panel]);
                awardXP($clientId, 'tribunal_convened', ['code' => $code]);
                $msg = "Tribunal <strong>$code</strong> convened."; $msgType = 'success';
            }
        } elseif ($action === 'tribunal_verdict' && $isCommander) {
            $tribId  = (int)($_POST['trib_id'] ?? 0);
            $verdict = trim($_POST['trib_verdict'] ?? '');
            $sentenceT = trim($_POST['trib_sentence'] ?? '');
            if ($verdict === '') {
                $msg = 'Verdict required.'; $msgType = 'error';
            } else {
                $stmt = $db->prepare("UPDATE jag_tribunals SET status = 'verdict', verdict = ?, sentence = ? WHERE id = ? AND status IN ('convened','hearing','deliberating')");
                $stmt->execute([$verdict, $sentenceT, $tribId]);
                $msg = $stmt->rowCount() ? 'Tribunal verdict rendered.' : 'Not found.';
                $msgType = $stmt->rowCount() ? 'success' : 'error';
            }
        } else {
            $msg = 'Unauthorized or unknown action.'; $msgType = 'error';
        }
    }
    $_SESSION['csrf_jag'] = bin2hex(random_bytes(32));
    $csrf = $_SESSION['csrf_jag'];
}

// ── Data Fetch ──
$tab = $_GET['tab'] ?? 'court';
$attorneys = $db->query("SELECT a.*, CONCAT(c.firstname,' ',c.lastname) AS att_name FROM jag_attorneys a LEFT JOIN tblclients c ON c.id = a.client_id ORDER BY a.win_rate DESC, a.cases_tried DESC")->fetchAll(PDO::FETCH_ASSOC);
$cases = $db->query("SELECT jc.*, CONCAT(p.firstname,' ',p.lastname) AS pros_name, CONCAT(d.firstname,' ',d.lastname) AS def_name, CONCAT(df.firstname,' ',df.lastname) AS defendant_name FROM jag_cases jc LEFT JOIN tblclients p ON p.id = jc.prosecution_attorney_id LEFT JOIN tblclients d ON d.id = jc.defense_attorney_id LEFT JOIN tblclients df ON df.id = jc.defendant_client_id ORDER BY jc.assigned_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$rulings = $db->query("SELECT r.*, jc.case_code, CONCAT(c.firstname,' ',c.lastname) AS judge_name FROM jag_rulings r JOIN jag_cases jc ON jc.id = r.case_id LEFT JOIN tblclients c ON c.id = r.ruled_by ORDER BY r.ruled_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$appeals = $db->query("SELECT a.*, jc.case_code, CONCAT(c.firstname,' ',c.lastname) AS appellant_name FROM jag_appeals a JOIN jag_cases jc ON jc.id = a.original_case_id LEFT JOIN tblclients c ON c.id = a.appellant_id ORDER BY a.filed_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$tribunals = $db->query("SELECT t.*, CONCAT(c.firstname,' ',c.lastname) AS accused_name FROM jag_tribunals t LEFT JOIN tblclients c ON c.id = t.accused_id ORDER BY t.convened_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$activeCases = count(array_filter($cases, fn($c) => !in_array($c['status'], ['closed'])));
$precedents = count(array_filter($rulings, fn($r) => $r['is_precedent']));

$pageTitle = 'Judge Advocate General (JAG Corps)';
include __DIR__ . '/includes/site-header.inc.php';
?>
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<style>
.jg-wrap{max-width:1100px;margin:0 auto;padding:1.5rem}
.jg-bg{background:#0a0a14;min-height:100vh;color:#e2e8f0}
.jg-card{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:10px;padding:1.25rem;margin-bottom:1rem}
.jg-card:hover{border-color:#a855f7;box-shadow:0 0 12px rgba(168,85,247,.12)}
.jg-title{font-size:1.6rem;font-weight:700;color:#f1f5f9;margin-bottom:.25rem;display:flex;align-items:center;gap:.5rem}
.jg-sub{color:#94a3b8;font-size:.85rem}
.jg-badge{display:inline-block;padding:2px 10px;border-radius:999px;font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
.jg-tabs{display:flex;gap:.5rem;margin-bottom:1.25rem;flex-wrap:wrap}
.jg-tab{padding:.4rem 1rem;border-radius:6px;cursor:pointer;font-size:.8rem;background:#2a2a4a;color:#94a3b8;text-decoration:none;font-weight:600;border:none}
.jg-tab.active{background:#a855f7;color:#fff}
.jg-stat-bar{display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem}
.jg-stat{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:8px;padding:.75rem 1.25rem;flex:1;min-width:120px;text-align:center}
.jg-stat .val{font-size:1.5rem;font-weight:700;color:#a855f7}
.jg-stat .lbl{font-size:.7rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px}
.jg-btn{background:#a855f7;color:#fff;border:none;padding:.5rem 1.25rem;border-radius:6px;cursor:pointer;font-size:.85rem;font-weight:600;transition:.2s}
.jg-btn:hover{background:#9333ea}
.jg-btn-sm{padding:.3rem .75rem;font-size:.75rem}
.jg-btn-outline{background:transparent;border:1px solid #a855f7;color:#a855f7}
.jg-btn-outline:hover{background:#a855f7;color:#fff}
.jg-btn-red{background:#ef4444;color:#fff}.jg-btn-red:hover{background:#dc2626}
.jg-btn-green{background:#22c55e;color:#fff}.jg-btn-green:hover{background:#16a34a}
.jg-btn-gold{background:#d4a017;color:#000}.jg-btn-gold:hover{background:#e2b340}
.jg-btn-blue{background:#3b82f6;color:#fff}.jg-btn-blue:hover{background:#2563eb}
.jg-input,.jg-select,.jg-textarea{width:100%;background:#0a0a14;border:1px solid #2a2a4a;color:#e2e8f0;padding:.5rem .75rem;border-radius:6px;font-size:.85rem;box-sizing:border-box}
.jg-textarea{min-height:100px;resize:vertical}
.jg-label{display:block;color:#94a3b8;font-size:.75rem;margin-bottom:.25rem;text-transform:uppercase;letter-spacing:.5px}
.jg-msg{padding:.75rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:.85rem}
.jg-msg-success{background:rgba(168,85,247,.12);border:1px solid #a855f7;color:#d8b4fe}
.jg-msg-error{background:rgba(239,68,68,.12);border:1px solid #ef4444;color:#fca5a5}
.jg-modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:900;align-items:center;justify-content:center}
.jg-modal-bg.open{display:flex}
.jg-modal{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:12px;padding:1.5rem;width:90%;max-width:620px;max-height:80vh;overflow-y:auto}
.jg-modal h3{color:#f1f5f9;margin:0 0 1rem}
.jg-form-row{margin-bottom:.75rem}
.jg-pipeline{display:flex;gap:4px;flex-wrap:wrap;margin:.5rem 0}
.jg-pipeline span{padding:2px 8px;border-radius:4px;font-size:.65rem;font-weight:600;text-transform:uppercase}
</style>
<div class="jg-bg">
<div class="jg-wrap">
    <div class="jg-title"><i class="fas fa-scale-balanced"></i> Judge Advocate General — JAG Corps</div>
    <p class="jg-sub" style="margin-bottom:1.25rem">Military justice, trials, evidence, appeals, and war crimes tribunals</p>

    <?php if ($msg): ?><div class="jg-msg jg-msg-<?= $msgType ?>"><?= $msg ?></div><?php endif; ?>

    <?php if ($isAttorney && $myBar): ?>
        <div class="jg-card" style="border-left:4px solid #a855f7;display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
            <i class="fas fa-gavel" style="font-size:2.5rem;color:#a855f7"></i>
            <div>
                <div style="font-family:monospace;color:#a855f7;font-size:.8rem;letter-spacing:.1em"><?= htmlspecialchars($myBar['bar_number']) ?></div>
                <div style="color:#f1f5f9;font-size:.95rem;font-weight:600"><?= strtoupper($myBar['specialty']) ?> COUNSEL</div>
                <div style="font-size:.8rem;color:#94a3b8">Cases: <?= (int)($myBar['cases_tried'] ?? 0) ?> &bull; Win Rate: <?= number_format($myBar['win_rate'] ?? 0, 1) ?>%</div>
            </div>
        </div>
    <?php elseif ($isOfficer && !$isAttorney): ?>
        <div class="jg-card" style="text-align:center">
            <p style="color:#94a3b8;margin-bottom:.5rem">You are eligible for the JAG Bar.</p>
            <form method="POST" style="display:flex;gap:.5rem;justify-content:center;align-items:center">
                <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="admit_attorney">
                <select name="specialty" class="jg-select" style="width:auto"><option value="general">General</option><option value="prosecution">Prosecution</option><option value="defense">Defense</option><option value="advisory">Advisory</option></select>
                <button class="jg-btn"><i class="fas fa-gavel"></i> Apply to Bar</button>
            </form>
        </div>
    <?php endif; ?>

    <div class="jg-stat-bar">
        <div class="jg-stat"><div class="val"><?= count($attorneys) ?></div><div class="lbl">Attorneys</div></div>
        <div class="jg-stat"><div class="val" style="color:#f59e0b"><?= $activeCases ?></div><div class="lbl">Active Cases</div></div>
        <div class="jg-stat"><div class="val" style="color:#22c55e"><?= count($rulings) ?></div><div class="lbl">Rulings</div></div>
        <div class="jg-stat"><div class="val" style="color:#3b82f6"><?= count($appeals) ?></div><div class="lbl">Appeals</div></div>
        <div class="jg-stat"><div class="val" style="color:#d4a017"><?= $precedents ?></div><div class="lbl">Precedents</div></div>
    </div>

    <div class="jg-tabs">
        <a href="?tab=court" class="jg-tab <?= $tab==='court'?'active':'' ?>"><i class="fas fa-landmark"></i> Court</a>
        <a href="?tab=cases" class="jg-tab <?= $tab==='cases'?'active':'' ?>"><i class="fas fa-folder-open"></i> Cases</a>
        <a href="?tab=bar" class="jg-tab <?= $tab==='bar'?'active':'' ?>"><i class="fas fa-user-tie"></i> Bar</a>
        <a href="?tab=appeals" class="jg-tab <?= $tab==='appeals'?'active':'' ?>"><i class="fas fa-file-contract"></i> Appeals</a>
        <a href="?tab=tribunals" class="jg-tab <?= $tab==='tribunals'?'active':'' ?>"><i class="fas fa-building-columns"></i> Tribunals</a>
    </div>

    <!-- ═══ TAB: COURT ═══ -->
    <?php if ($tab === 'court'): ?>
        <h4 style="color:#f1f5f9;font-size:1rem;margin-bottom:.75rem"><i class="fas fa-gavel"></i> Recent Rulings & Precedents</h4>
        <?php
        $rtColors = ['verdict'=>'#a855f7','sentence'=>'#ef4444','motion'=>'#3b82f6','procedural'=>'#94a3b8'];
        foreach (array_slice($rulings, 0, 20) as $rl): ?>
            <div class="jg-card" style="border-left:3px solid <?= $rtColors[$rl['ruling_type']] ?>">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div>
                        <span class="jg-badge" style="background:<?= $rtColors[$rl['ruling_type']] ?>20;color:<?= $rtColors[$rl['ruling_type']] ?>"><?= strtoupper($rl['ruling_type']) ?></span>
                        <strong style="color:#a855f7;margin-left:.5rem"><?= htmlspecialchars($rl['case_code']) ?></strong>
                        <?php if ($rl['is_precedent']): ?><span class="jg-badge" style="background:#d4a01720;color:#d4a017;margin-left:.25rem">⭐ PRECEDENT</span><?php endif; ?>
                    </div>
                    <span style="color:#64748b;font-size:.75rem"><?= date('M j, Y', strtotime($rl['ruled_at'])) ?></span>
                </div>
                <p style="color:#cbd5e1;font-size:.85rem;margin-top:.5rem"><?= htmlspecialchars($rl['ruling']) ?></p>
                <div style="color:#64748b;font-size:.75rem;margin-top:.25rem">Judge: <?= htmlspecialchars($rl['judge_name'] ?? 'Unknown') ?></div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($rulings)): ?><div class="jg-card" style="text-align:center;color:#64748b"><p>No rulings yet.</p></div><?php endif; ?>

    <!-- ═══ TAB: CASES ═══ -->
    <?php elseif ($tab === 'cases'): ?>
        <?php if ($isFlag): ?>
            <div style="margin-bottom:1rem"><button class="jg-btn" onclick="document.getElementById('modalCase').classList.add('open')"><i class="fas fa-folder-plus"></i> Assign New Case</button></div>
        <?php endif; ?>
        <?php
        $csColors = ['assigned'=>'#94a3b8','discovery'=>'#3b82f6','pretrial'=>'#f59e0b','trial'=>'#ef4444','verdict'=>'#a855f7','sentencing'=>'#dc2626','appeal_window'=>'#f97316','closed'=>'#64748b','appealed'=>'#d4a017'];
        $csPipeline = ['assigned','discovery','pretrial','trial','verdict','sentencing','appeal_window','closed'];
        foreach ($cases as $cs): ?>
            <div class="jg-card">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div><strong style="color:#a855f7"><?= htmlspecialchars($cs['case_code']) ?></strong> <span style="color:#f1f5f9">v. <?= htmlspecialchars($cs['defendant_name'] ?? 'Unknown') ?></span></div>
                    <span class="jg-badge" style="background:<?= $csColors[$cs['status']] ?>20;color:<?= $csColors[$cs['status']] ?>;border:1px solid <?= $csColors[$cs['status']] ?>40"><?= strtoupper(str_replace('_', ' ', $cs['status'])) ?></span>
                </div>
                <div class="jg-pipeline">
                    <?php foreach ($csPipeline as $stage):
                        $isCurrent = ($cs['status'] === $stage);
                        $isPast = array_search($cs['status'], $csPipeline) > array_search($stage, $csPipeline);
                    ?>
                        <span style="background:<?= $isCurrent ? '#a855f7' : ($isPast ? '#22c55e30' : '#2a2a4a') ?>;color:<?= $isCurrent ? '#fff' : ($isPast ? '#22c55e' : '#64748b') ?>"><?= strtoupper(str_replace('_', ' ', $stage)) ?></span>
                    <?php endforeach; ?>
                </div>
                <p style="color:#cbd5e1;font-size:.85rem;margin-top:.5rem"><strong>Charges:</strong> <?= htmlspecialchars($cs['charges']) ?></p>
                <div style="color:#94a3b8;font-size:.8rem;margin-top:.25rem">
                    Plea: <strong style="color:<?= $cs['plea']==='guilty'?'#ef4444':'#22c55e' ?>"><?= strtoupper(str_replace('_', ' ', $cs['plea'])) ?></strong>
                    <?php if ($cs['pros_name']): ?>&bull; Prosecution: <?= htmlspecialchars($cs['pros_name']) ?><?php endif; ?>
                    <?php if ($cs['def_name']): ?>&bull; Defense: <?= htmlspecialchars($cs['def_name']) ?><?php endif; ?>
                </div>
                <?php if ($cs['sentence']): ?><div style="color:#ef4444;font-size:.85rem;margin-top:.5rem;border-top:1px solid #2a2a4a;padding-top:.5rem"><strong>Sentence:</strong> <?= htmlspecialchars($cs['sentence']) ?></div><?php endif; ?>
                <div style="margin-top:.5rem;display:flex;gap:.5rem;flex-wrap:wrap">
                    <?php if ($isAttorney && !in_array($cs['status'], ['closed','appealed'])): ?>
                        <button class="jg-btn-sm jg-btn jg-btn-blue" onclick="openEvidence(<?= $cs['id'] ?>,'<?= htmlspecialchars($cs['case_code'], ENT_QUOTES) ?>')"><i class="fas fa-file-circle-plus"></i> Evidence</button>
                    <?php endif; ?>
                    <?php if (($isAttorney || $isFlag) && !in_array($cs['status'], ['closed','appealed'])): ?>
                        <button class="jg-btn-sm jg-btn jg-btn-outline" onclick="openAdvance(<?= $cs['id'] ?>,'<?= htmlspecialchars($cs['case_code'], ENT_QUOTES) ?>')"><i class="fas fa-forward"></i> Advance</button>
                    <?php endif; ?>
                    <?php if ($isFlag && !in_array($cs['status'], ['closed','appealed'])): ?>
                        <button class="jg-btn-sm jg-btn jg-btn-gold" onclick="openRuling(<?= $cs['id'] ?>,'<?= htmlspecialchars($cs['case_code'], ENT_QUOTES) ?>')"><i class="fas fa-gavel"></i> Issue Ruling</button>
                    <?php endif; ?>
                    <?php if (in_array($cs['status'], ['verdict','sentencing','appeal_window'])): ?>
                        <button class="jg-btn-sm jg-btn jg-btn-red" onclick="openAppeal(<?= $cs['id'] ?>,'<?= htmlspecialchars($cs['case_code'], ENT_QUOTES) ?>')"><i class="fas fa-file-contract"></i> Appeal</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($cases)): ?><div class="jg-card" style="text-align:center;color:#64748b"><p>No cases.</p></div><?php endif; ?>

    <!-- ═══ TAB: BAR ═══ -->
    <?php elseif ($tab === 'bar'): ?>
        <h4 style="color:#f1f5f9;font-size:1rem;margin-bottom:.75rem"><i class="fas fa-user-tie"></i> JAG Bar Association</h4>
        <?php
        $specColors = ['prosecution'=>'#ef4444','defense'=>'#3b82f6','general'=>'#a855f7','advisory'=>'#22c55e'];
        foreach ($attorneys as $att): ?>
            <div class="jg-card" style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
                <i class="fas fa-user-tie" style="font-size:1.5rem;color:<?= $specColors[$att['specialty']] ?>"></i>
                <div style="flex:1">
                    <strong style="color:#f1f5f9"><?= htmlspecialchars($att['att_name'] ?? 'Unknown') ?></strong>
                    <span style="font-family:monospace;color:#a855f7;font-size:.75rem;margin-left:.5rem"><?= htmlspecialchars($att['bar_number']) ?></span>
                    <div style="color:#94a3b8;font-size:.75rem">
                        <span class="jg-badge" style="background:<?= $specColors[$att['specialty']] ?>20;color:<?= $specColors[$att['specialty']] ?>"><?= strtoupper($att['specialty']) ?></span>
                        &bull; Cases: <?= (int)$att['cases_tried'] ?> &bull; Win: <?= number_format($att['win_rate'], 1) ?>%
                        &bull; Admitted: <?= date('M j, Y', strtotime($att['admitted_at'])) ?>
                    </div>
                </div>
                <span class="jg-badge" style="background:<?= $att['status']==='active'?'#22c55e20':'#ef444420' ?>;color:<?= $att['status']==='active'?'#22c55e':'#ef4444' ?>"><?= strtoupper($att['status']) ?></span>
            </div>
        <?php endforeach; ?>
        <?php if (empty($attorneys)): ?><div class="jg-card" style="text-align:center;color:#64748b"><p>No attorneys admitted to the bar.</p></div><?php endif; ?>

    <!-- ═══ TAB: APPEALS ═══ -->
    <?php elseif ($tab === 'appeals'): ?>
        <?php
        $apColors = ['filed'=>'#f59e0b','hearing'=>'#3b82f6','upheld'=>'#22c55e','overturned'=>'#ef4444','remanded'=>'#f97316'];
        foreach ($appeals as $ap): ?>
            <div class="jg-card" style="border-left:3px solid <?= $apColors[$ap['status']] ?>">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div>
                        <strong style="color:#a855f7"><?= htmlspecialchars($ap['case_code']) ?></strong>
                        <span class="jg-badge" style="background:#3b82f620;color:#3b82f6;margin-left:.5rem"><?= strtoupper(str_replace('_', ' ', $ap['appeal_type'])) ?></span>
                    </div>
                    <span class="jg-badge" style="background:<?= $apColors[$ap['status']] ?>20;color:<?= $apColors[$ap['status']] ?>;border:1px solid <?= $apColors[$ap['status']] ?>40"><?= strtoupper($ap['status']) ?></span>
                </div>
                <p style="color:#cbd5e1;font-size:.85rem;margin-top:.5rem"><strong>Grounds:</strong> <?= htmlspecialchars($ap['grounds']) ?></p>
                <div style="color:#64748b;font-size:.75rem;margin-top:.25rem">Appellant: <?= htmlspecialchars($ap['appellant_name'] ?? 'Unknown') ?> &bull; Filed: <?= date('M j, Y', strtotime($ap['filed_at'])) ?></div>
                <?php if ($ap['decision']): ?><div style="color:#86efac;font-size:.85rem;margin-top:.5rem;border-top:1px solid #2a2a4a;padding-top:.5rem"><strong>Decision:</strong> <?= htmlspecialchars($ap['decision']) ?></div><?php endif; ?>
                <?php if (in_array($ap['status'], ['filed','hearing']) && $isFlag): ?>
                    <form method="POST" style="margin-top:.5rem;display:flex;gap:.5rem;flex-wrap:wrap;align-items:flex-end">
                        <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="decide_appeal"><input type="hidden" name="app_id" value="<?= $ap['id'] ?>">
                        <div><label class="jg-label">Decision</label><select name="decision" class="jg-select" style="width:auto"><option value="upheld">Upheld</option><option value="overturned">Overturned</option><option value="remanded">Remanded</option></select></div>
                        <div style="flex:1"><label class="jg-label">Rationale</label><input type="text" name="decision_text" class="jg-input" required placeholder="Decision rationale..."></div>
                        <button class="jg-btn-sm jg-btn jg-btn-gold"><i class="fas fa-gavel"></i> Decide</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if (empty($appeals)): ?><div class="jg-card" style="text-align:center;color:#64748b"><p>No appeals filed.</p></div><?php endif; ?>

    <!-- ═══ TAB: TRIBUNALS ═══ -->
    <?php elseif ($tab === 'tribunals'): ?>
        <?php if ($isCommander): ?>
            <div style="margin-bottom:1rem"><button class="jg-btn jg-btn-red" onclick="document.getElementById('modalTribunal').classList.add('open')"><i class="fas fa-building-columns"></i> Convene Tribunal</button></div>
        <?php endif; ?>
        <?php
        $tbColors = ['convened'=>'#f59e0b','hearing'=>'#3b82f6','deliberating'=>'#a855f7','verdict'=>'#22c55e'];
        foreach ($tribunals as $tb): ?>
            <div class="jg-card" style="border:2px solid <?= $tbColors[$tb['status']] ?>40">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div>
                        <strong style="color:#ef4444;font-size:.9rem"><?= htmlspecialchars($tb['tribunal_code']) ?></strong>
                        <span class="jg-badge" style="background:<?= $tb['tribunal_type']==='war_crimes'?'#ef444420':'#3b82f620' ?>;color:<?= $tb['tribunal_type']==='war_crimes'?'#ef4444':'#3b82f6' ?>;margin-left:.5rem"><?= strtoupper(str_replace('_', ' ', $tb['tribunal_type'])) ?></span>
                    </div>
                    <span class="jg-badge" style="background:<?= $tbColors[$tb['status']] ?>20;color:<?= $tbColors[$tb['status']] ?>"><?= strtoupper($tb['status']) ?></span>
                </div>
                <div style="color:#f1f5f9;font-size:.85rem;margin-top:.5rem">Accused: <strong><?= htmlspecialchars($tb['accused_name'] ?? 'Unknown') ?></strong></div>
                <p style="color:#cbd5e1;font-size:.85rem;margin-top:.25rem"><strong>Charges:</strong> <?= htmlspecialchars($tb['charges']) ?></p>
                <?php if ($tb['panel_members']): ?><div style="color:#94a3b8;font-size:.8rem;margin-top:.25rem"><strong>Panel:</strong> <?= htmlspecialchars($tb['panel_members']) ?></div><?php endif; ?>
                <?php if ($tb['verdict']): ?>
                    <div style="margin-top:.5rem;border-top:1px solid #2a2a4a;padding-top:.5rem">
                        <div style="color:#a855f7;font-size:.85rem"><strong>Verdict:</strong> <?= htmlspecialchars($tb['verdict']) ?></div>
                        <?php if ($tb['sentence']): ?><div style="color:#ef4444;font-size:.85rem"><strong>Sentence:</strong> <?= htmlspecialchars($tb['sentence']) ?></div><?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php if ($isCommander && in_array($tb['status'], ['convened','hearing','deliberating'])): ?>
                    <form method="POST" style="margin-top:.5rem;display:flex;gap:.5rem;flex-wrap:wrap;align-items:flex-end">
                        <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="tribunal_verdict"><input type="hidden" name="trib_id" value="<?= $tb['id'] ?>">
                        <div style="flex:1"><label class="jg-label">Verdict</label><input type="text" name="trib_verdict" class="jg-input" required></div>
                        <div style="flex:1"><label class="jg-label">Sentence</label><input type="text" name="trib_sentence" class="jg-input"></div>
                        <button class="jg-btn-sm jg-btn jg-btn-gold"><i class="fas fa-gavel"></i> Render</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if (empty($tribunals)): ?><div class="jg-card" style="text-align:center;color:#64748b"><p>No tribunals convened.</p></div><?php endif; ?>
    <?php endif; ?>
</div>
</div>

<!-- Modals -->
<div class="jg-modal-bg" id="modalCase"><div class="jg-modal"><h3><i class="fas fa-folder-plus"></i> Assign New Case</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="assign_case">
<div class="jg-form-row"><label class="jg-label">Defendant Client ID</label><input type="number" name="defendant_id" class="jg-input" required min="1"></div>
<div class="jg-form-row"><label class="jg-label">Charges</label><textarea name="charges" class="jg-textarea" required placeholder="Detail charges..."></textarea></div>
<div style="display:flex;gap:.75rem"><div class="jg-form-row" style="flex:1"><label class="jg-label">Prosecution Attorney ID</label><input type="number" name="pros_attorney" class="jg-input" min="0"></div><div class="jg-form-row" style="flex:1"><label class="jg-label">Defense Attorney ID</label><input type="number" name="def_attorney" class="jg-input" min="0"></div></div>
<div style="background:#a855f710;border:1px solid #a855f730;border-radius:6px;padding:.75rem;margin:.75rem 0;font-size:.8rem;color:#d8b4fe"><i class="fas fa-info-circle"></i> Bill of Rights guarantees pro bono defense counsel for all defendants.</div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="jg-btn jg-btn-outline" onclick="this.closest('.jg-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="jg-btn"><i class="fas fa-folder-plus"></i> Assign</button></div></form></div></div>

<div class="jg-modal-bg" id="modalEvidence"><div class="jg-modal"><h3><i class="fas fa-file-circle-plus"></i> Submit Evidence</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="submit_evidence"><input type="hidden" name="case_id" id="evCaseId" value="">
<div style="color:#94a3b8;margin-bottom:1rem">Case: <strong id="evCaseCode" style="color:#a855f7"></strong></div>
<div class="jg-form-row"><label class="jg-label">Evidence Type</label><select name="evidence_type" class="jg-select"><option value="document">Document</option><option value="testimony">Testimony</option><option value="digital">Digital</option><option value="physical">Physical</option></select></div>
<div class="jg-form-row"><label class="jg-label">Classification</label><select name="ev_class" class="jg-select"><option value="public">Public</option><option value="confidential">Confidential</option><option value="sealed">Sealed</option></select></div>
<div class="jg-form-row"><label class="jg-label">Description</label><textarea name="ev_desc" class="jg-textarea" required></textarea></div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="jg-btn jg-btn-outline" onclick="this.closest('.jg-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="jg-btn jg-btn-blue"><i class="fas fa-upload"></i> Submit</button></div></form></div></div>

<div class="jg-modal-bg" id="modalAdvance"><div class="jg-modal"><h3><i class="fas fa-forward"></i> Advance Case</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="advance_case"><input type="hidden" name="case_id" id="advCaseId" value="">
<div style="color:#94a3b8;margin-bottom:1rem">Case: <strong id="advCaseCode" style="color:#a855f7"></strong></div>
<div class="jg-form-row"><label class="jg-label">Advance To</label><select name="new_status" class="jg-select"><option value="discovery">Discovery</option><option value="pretrial">Pretrial</option><option value="trial">Trial</option><option value="verdict">Verdict</option><option value="sentencing">Sentencing</option><option value="appeal_window">Appeal Window</option><option value="closed">Closed</option></select></div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="jg-btn jg-btn-outline" onclick="this.closest('.jg-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="jg-btn"><i class="fas fa-forward"></i> Advance</button></div></form></div></div>

<div class="jg-modal-bg" id="modalRuling"><div class="jg-modal"><h3><i class="fas fa-gavel"></i> Issue Ruling</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="issue_ruling"><input type="hidden" name="case_id" id="rulCaseId" value="">
<div style="color:#94a3b8;margin-bottom:1rem">Case: <strong id="rulCaseCode" style="color:#a855f7"></strong></div>
<div class="jg-form-row"><label class="jg-label">Ruling Type</label><select name="ruling_type" class="jg-select"><option value="verdict">Verdict</option><option value="sentence">Sentence</option><option value="motion">Motion</option><option value="procedural">Procedural</option></select></div>
<div class="jg-form-row"><label class="jg-label">Ruling</label><textarea name="ruling" class="jg-textarea" required></textarea></div>
<div class="jg-form-row"><label class="jg-label">Sentence (if applicable)</label><input type="text" name="sentence" class="jg-input" placeholder="e.g. 30 days rank freeze, 500 XP penalty..."></div>
<div class="jg-form-row"><label style="color:#d4a017;font-size:.85rem"><input type="checkbox" name="is_precedent" value="1"> This ruling sets precedent</label></div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="jg-btn jg-btn-outline" onclick="this.closest('.jg-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="jg-btn jg-btn-gold"><i class="fas fa-gavel"></i> Issue</button></div></form></div></div>

<div class="jg-modal-bg" id="modalAppeal"><div class="jg-modal"><h3><i class="fas fa-file-contract"></i> File Appeal</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="file_appeal"><input type="hidden" name="case_id" id="appCaseId" value="">
<div style="color:#94a3b8;margin-bottom:1rem">Case: <strong id="appCaseCode" style="color:#a855f7"></strong></div>
<div class="jg-form-row"><label class="jg-label">Appeal Court</label><select name="appeal_type" class="jg-select"><option value="court_of_military_appeals">Court of Military Appeals (3 Generals)</option><option value="supreme_military_court">Supreme Military Court (Commander)</option></select></div>
<div class="jg-form-row"><label class="jg-label">Grounds for Appeal</label><textarea name="grounds" class="jg-textarea" required placeholder="State legal grounds..."></textarea></div>
<div style="background:#f59e0b10;border:1px solid #f59e0b30;border-radius:6px;padding:.75rem;margin:.75rem 0;font-size:.8rem;color:#fde68a"><i class="fas fa-clock"></i> 14-day appeal window from verdict date.</div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="jg-btn jg-btn-outline" onclick="this.closest('.jg-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="jg-btn jg-btn-red"><i class="fas fa-file-contract"></i> File Appeal</button></div></form></div></div>

<div class="jg-modal-bg" id="modalTribunal"><div class="jg-modal"><h3><i class="fas fa-building-columns"></i> Convene Tribunal</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="convene_tribunal">
<div style="background:#ef444410;border:1px solid #ef444430;border-radius:6px;padding:.75rem;margin-bottom:1rem;font-size:.8rem;color:#fca5a5"><i class="fas fa-exclamation-triangle"></i> Commander-only. Reserved for the most serious offenses.</div>
<div class="jg-form-row"><label class="jg-label">Accused Client ID</label><input type="number" name="accused_id" class="jg-input" required min="1"></div>
<div class="jg-form-row"><label class="jg-label">Tribunal Type</label><select name="tribunal_type" class="jg-select"><option value="standard">Standard Military Tribunal</option><option value="war_crimes">War Crimes Tribunal</option></select></div>
<div class="jg-form-row"><label class="jg-label">Charges</label><textarea name="trib_charges" class="jg-textarea" required></textarea></div>
<div class="jg-form-row"><label class="jg-label">Panel Members</label><input type="text" name="panel" class="jg-input" placeholder="e.g. Gen. Smith, Gen. Jones, Gen. Brown"></div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="jg-btn jg-btn-outline" onclick="this.closest('.jg-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="jg-btn jg-btn-red"><i class="fas fa-building-columns"></i> Convene</button></div></form></div></div>

<script>
function openEvidence(id,code){document.getElementById('evCaseId').value=id;document.getElementById('evCaseCode').textContent=code;document.getElementById('modalEvidence').classList.add('open')}
function openAdvance(id,code){document.getElementById('advCaseId').value=id;document.getElementById('advCaseCode').textContent=code;document.getElementById('modalAdvance').classList.add('open')}
function openRuling(id,code){document.getElementById('rulCaseId').value=id;document.getElementById('rulCaseCode').textContent=code;document.getElementById('modalRuling').classList.add('open')}
function openAppeal(id,code){document.getElementById('appCaseId').value=id;document.getElementById('appCaseCode').textContent=code;document.getElementById('modalAppeal').classList.add('open')}
document.querySelectorAll('.jg-modal-bg').forEach(bg=>{bg.addEventListener('click',e=>{if(e.target===bg)bg.classList.remove('open')})});
</script>
<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
