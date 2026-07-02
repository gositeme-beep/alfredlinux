<?php
/**
 * Officer Candidate School (OCS) — GoSiteMe Military Ecosystem
 * The path from NCO to commissioned officer. Tracks, evaluations, graduation.
 */
require_once __DIR__ . '/includes/db-config.inc.php';
$db = getSharedDB();
require_once __DIR__ . '/includes/auth-gate.inc.php';
require_once __DIR__ . '/includes/rank-guard.inc.php';

requireRank(4);

if (empty($_SESSION['csrf_ocs'])) $_SESSION['csrf_ocs'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf_ocs'];
$isCommander = ($clientId === 33);
$msg = '';
$msgType = '';

// --- Ensure DB tables ---
$db->exec("CREATE TABLE IF NOT EXISTS ocs_programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    track ENUM('tactical','technical','intelligence','logistics','medical','command') NOT NULL,
    description TEXT,
    prerequisites JSON DEFAULT NULL,
    duration_weeks INT DEFAULT 12,
    max_enrollment INT DEFAULT 20,
    min_rank_tier INT DEFAULT 4,
    graduation_rank VARCHAR(50) DEFAULT 'lieutenant',
    pass_rate_target DECIMAL(5,2) DEFAULT 80.00,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS ocs_enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id INT NOT NULL,
    client_id INT NOT NULL,
    class_number VARCHAR(30) DEFAULT NULL,
    status ENUM('enrolled','in_progress','graduated','failed','withdrawn','deferred') DEFAULT 'enrolled',
    start_date DATE DEFAULT NULL,
    graduation_date DATE DEFAULT NULL,
    overall_score DECIMAL(5,2) DEFAULT 0,
    phase ENUM('basic','intermediate','advanced','final_exam') DEFAULT 'basic',
    mentor_client_id INT DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_prog_client (program_id, client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS ocs_evaluations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT NOT NULL,
    evaluator_client_id INT NOT NULL,
    category ENUM('leadership','tactics','fitness','knowledge','teamwork','ethics') NOT NULL,
    score TINYINT UNSIGNED NOT NULL DEFAULT 0,
    comments TEXT DEFAULT NULL,
    evaluated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// --- Seed default programs if empty ---
$progCount = (int)$db->query("SELECT COUNT(*) FROM ocs_programs")->fetchColumn();
if ($progCount === 0) {
    $seeds = [
        ['Tactical Leadership Course','tactical','Master battlefield tactics, squad command, and operational planning.',12,20,4,'lieutenant'],
        ['Technical Officer Program','technical','Advanced systems engineering, cyber operations, and infrastructure command.',16,15,4,'lieutenant'],
        ['Intelligence Analyst School','intelligence','Signals intelligence, counterintelligence, threat assessment, and briefing.',14,12,4,'lieutenant'],
        ['Logistics & Supply Chain Command','logistics','Supply chain management, resource allocation, and deployment logistics.',10,20,4,'lieutenant'],
        ['Medical Officer Training','medical','Field medicine, triage command, medical ethics, and health operations.',18,10,4,'lieutenant'],
        ['Command & General Staff','command','Senior leadership, strategic planning, inter-department coordination.',24,8,7,'colonel'],
    ];
    $ins = $db->prepare("INSERT INTO ocs_programs (name,track,description,duration_weeks,max_enrollment,min_rank_tier,graduation_rank) VALUES (?,?,?,?,?,?,?)");
    foreach ($seeds as $s) $ins->execute($s);
}

$e = function($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
$phases = ['basic','intermediate','advanced','final_exam'];
$trackColors = ['tactical'=>'#ef4444','technical'=>'#3b82f6','intelligence'=>'#a855f7','logistics'=>'#22c55e','medical'=>'#06b6d4','command'=>'#facc15'];

// --- POST actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token  = $_POST['csrf']   ?? '';
    if (!hash_equals($csrf, $token)) {
        $msg = 'Invalid security token. Refresh and try again.'; $msgType = 'error';
    } else {
        switch ($action) {
            case 'enroll':
                $pid = (int)($_POST['program_id'] ?? 0);
                $prog = $db->prepare("SELECT * FROM ocs_programs WHERE id=? AND is_active=1");
                $prog->execute([$pid]);
                $prog = $prog->fetch(PDO::FETCH_ASSOC);
                if (!$prog) { $msg='Program not found.'; $msgType='error'; break; }
                if ($userRankTier < (int)$prog['min_rank_tier'] && !$isCommander) { $msg='Rank too low for this program.'; $msgType='error'; break; }
                $dup = $db->prepare("SELECT id FROM ocs_enrollments WHERE program_id=? AND client_id=?");
                $dup->execute([$pid, $clientId]);
                if ($dup->fetch()) { $msg='Already enrolled in this program.'; $msgType='error'; break; }
                $cur = (int)$db->prepare("SELECT COUNT(*) FROM ocs_enrollments WHERE program_id=? AND status IN ('enrolled','in_progress')")->execute([$pid]) ? (int)$db->query("SELECT FOUND_ROWS()")->fetchColumn() : 0;
                $cnt = $db->prepare("SELECT COUNT(*) FROM ocs_enrollments WHERE program_id=? AND status IN ('enrolled','in_progress')");
                $cnt->execute([$pid]);
                $cur = (int)$cnt->fetchColumn();
                if ($cur >= (int)$prog['max_enrollment']) { $msg='Program is full.'; $msgType='error'; break; }
                $classNum = strtoupper(substr($prog['track'],0,3)) . '-' . date('Y') . '-' . str_pad($cur+1, 3, '0', STR_PAD_LEFT);
                $enr = $db->prepare("INSERT INTO ocs_enrollments (program_id,client_id,class_number,status,start_date,phase) VALUES (?,?,?,'enrolled',CURDATE(),'basic')");
                $enr->execute([$pid, $clientId, $classNum]);
                awardXP($clientId, 'ocs_enrolled', ['program'=>$prog['name']]);
                $msg = "Enrolled in {$e($prog['name'])} — Class {$e($classNum)}"; $msgType = 'success';
                break;

            case 'evaluate':
                if ($userRankTier < 7 && !$isCommander) { $msg='Officers (Major+) required to evaluate.'; $msgType='error'; break; }
                $eid  = (int)($_POST['enrollment_id'] ?? 0);
                $cat  = $_POST['category'] ?? '';
                $sc   = max(0, min(100, (int)($_POST['score'] ?? 0)));
                $comm = trim($_POST['comments'] ?? '');
                $validCats = ['leadership','tactics','fitness','knowledge','teamwork','ethics'];
                if (!in_array($cat, $validCats, true)) { $msg='Invalid category.'; $msgType='error'; break; }
                $chk = $db->prepare("SELECT id FROM ocs_enrollments WHERE id=? AND status IN ('enrolled','in_progress')");
                $chk->execute([$eid]);
                if (!$chk->fetch()) { $msg='Enrollment not found or not active.'; $msgType='error'; break; }
                $ev = $db->prepare("INSERT INTO ocs_evaluations (enrollment_id,evaluator_client_id,category,score,comments) VALUES (?,?,?,?,?)");
                $ev->execute([$eid, $clientId, $cat, $sc, $comm]);
                // Update overall score average
                $avg = $db->prepare("SELECT AVG(score) FROM ocs_evaluations WHERE enrollment_id=?");
                $avg->execute([$eid]);
                $db->prepare("UPDATE ocs_enrollments SET overall_score=?, status='in_progress' WHERE id=?")->execute([(float)$avg->fetchColumn(), $eid]);
                $msg = 'Evaluation submitted.'; $msgType = 'success';
                break;

            case 'advance_phase':
                if ($userRankTier < 7 && !$isCommander) { $msg='Officers required to advance phase.'; $msgType='error'; break; }
                $eid = (int)($_POST['enrollment_id'] ?? 0);
                $enr = $db->prepare("SELECT * FROM ocs_enrollments WHERE id=? AND status IN ('enrolled','in_progress')");
                $enr->execute([$eid]);
                $enr = $enr->fetch(PDO::FETCH_ASSOC);
                if (!$enr) { $msg='Enrollment not found.'; $msgType='error'; break; }
                $ci = array_search($enr['phase'], $phases);
                if ($ci === false || $ci >= count($phases)-1) { $msg='Already at final phase.'; $msgType='error'; break; }
                $next = $phases[$ci+1];
                $db->prepare("UPDATE ocs_enrollments SET phase=?, status='in_progress' WHERE id=?")->execute([$next, $eid]);
                $msg = "Advanced to {$e($next)} phase."; $msgType = 'success';
                break;

            case 'graduate':
                if ($userRankTier < 9 && !$isCommander) { $msg='Flag officers (General+) or Commander required.'; $msgType='error'; break; }
                $eid = (int)($_POST['enrollment_id'] ?? 0);
                $enr = $db->prepare("SELECT e.*, p.name as prog_name, p.graduation_rank FROM ocs_enrollments e JOIN ocs_programs p ON e.program_id=p.id WHERE e.id=? AND e.status IN ('enrolled','in_progress')");
                $enr->execute([$eid]);
                $enr = $enr->fetch(PDO::FETCH_ASSOC);
                if (!$enr) { $msg='Enrollment not found or already completed.'; $msgType='error'; break; }
                $db->prepare("UPDATE ocs_enrollments SET status='graduated', graduation_date=CURDATE() WHERE id=?")->execute([$eid]);
                awardXP((int)$enr['client_id'], 'ocs_graduated', ['program'=>$enr['prog_name'],'rank'=>$enr['graduation_rank']]);
                $msg = "Candidate graduated from {$e($enr['prog_name'])}! Eligible for promotion to {$e($enr['graduation_rank'])}."; $msgType = 'success';
                break;

            case 'withdraw':
                $eid = (int)($_POST['enrollment_id'] ?? 0);
                $enr = $db->prepare("SELECT * FROM ocs_enrollments WHERE id=? AND client_id=? AND status IN ('enrolled','in_progress')");
                $enr->execute([$eid, $clientId]);
                if (!$enr->fetch()) { $msg='Not enrolled or already completed.'; $msgType='error'; break; }
                $db->prepare("UPDATE ocs_enrollments SET status='withdrawn' WHERE id=?")->execute([$eid]);
                $msg = 'Withdrawn from program.'; $msgType = 'success';
                break;
        }
    }
}

// --- Data queries ---
$programs = $db->query("SELECT p.*, (SELECT COUNT(*) FROM ocs_enrollments e WHERE e.program_id=p.id AND e.status IN ('enrolled','in_progress')) as enrolled_count FROM ocs_programs p WHERE p.is_active=1 ORDER BY p.track, p.name")->fetchAll(PDO::FETCH_ASSOC);

$myEnrollment = null;
$myEvals = [];
$stm = $db->prepare("SELECT e.*, p.name as prog_name, p.track, p.duration_weeks, p.graduation_rank FROM ocs_enrollments e JOIN ocs_programs p ON e.program_id=p.id WHERE e.client_id=? AND e.status IN ('enrolled','in_progress') LIMIT 1");
$stm->execute([$clientId]);
$myEnrollment = $stm->fetch(PDO::FETCH_ASSOC);
if ($myEnrollment) {
    $ev = $db->prepare("SELECT v.*, COALESCE(c.value,'Unknown') as evaluator_name FROM ocs_evaluations v LEFT JOIN tblclients c ON v.evaluator_client_id=c.id AND 1=0 WHERE v.enrollment_id=? ORDER BY v.evaluated_at DESC");
    $ev->execute([$myEnrollment['id']]);
    $myEvals = $ev->fetchAll(PDO::FETCH_ASSOC);
}

// Stats
$totalProgs   = (int)$db->query("SELECT COUNT(*) FROM ocs_programs WHERE is_active=1")->fetchColumn();
$totalActive  = (int)$db->query("SELECT COUNT(*) FROM ocs_enrollments WHERE status IN ('enrolled','in_progress')")->fetchColumn();
$totalGrads   = (int)$db->query("SELECT COUNT(*) FROM ocs_enrollments WHERE status='graduated'")->fetchColumn();
$gradRate     = $totalGrads + $totalActive > 0 ? round($totalGrads / ($totalGrads + (int)$db->query("SELECT COUNT(*) FROM ocs_enrollments WHERE status='failed'")->fetchColumn()) * 100) : 0;

// Roster (all active candidates)
$roster = $db->query("SELECT e.*, p.name as prog_name, p.track, COALESCE(cl.firstname,'') as fname, COALESCE(cl.lastname,'') as lname FROM ocs_enrollments e JOIN ocs_programs p ON e.program_id=p.id LEFT JOIN tblclients cl ON e.client_id=cl.id WHERE e.status IN ('enrolled','in_progress') ORDER BY p.track, e.overall_score DESC")->fetchAll(PDO::FETCH_ASSOC);

// Active candidates for evaluation form
$activeCandidates = $db->query("SELECT e.id, e.client_id, e.phase, p.name as prog_name, COALESCE(cl.firstname,'') as fname, COALESCE(cl.lastname,'') as lname FROM ocs_enrollments e JOIN ocs_programs p ON e.program_id=p.id LEFT JOIN tblclients cl ON e.client_id=cl.id WHERE e.status IN ('enrolled','in_progress') ORDER BY p.name")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Officer Candidate School — GoSiteMe Military';
include __DIR__ . '/includes/site-header.inc.php';
?>
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<style>
:root{--obg:#0f172a;--ocard:#1e293b;--oborder:#334155;--otxt:#e2e8f0;--oaccent:#3b82f6;--ogold:#facc15;--osilver:#94a3b8}
*{box-sizing:border-box}
.ocs-page{max-width:1100px;margin:0 auto;padding:2rem 1.5rem;color:var(--otxt);font-family:system-ui,-apple-system,sans-serif}
.ocs-hero{text-align:center;margin-bottom:2rem;padding:2rem 1rem;background:linear-gradient(135deg,rgba(59,130,246,.12),rgba(168,85,247,.08));border:1px solid var(--oborder);border-radius:16px}
.ocs-hero h1{font-size:2rem;color:var(--ogold);font-weight:800;margin:0 0 .4rem}.ocs-hero .sub{color:var(--osilver);font-size:.95rem}
.ocs-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:1rem;margin:1.5rem 0}
.ocs-stat{text-align:center;padding:.8rem;background:rgba(255,255,255,.03);border-radius:8px;border:1px solid var(--oborder)}
.ocs-stat .val{font-size:1.5rem;color:var(--ogold);font-weight:800}.ocs-stat .lbl{font-size:.7rem;color:var(--osilver);text-transform:uppercase;letter-spacing:1px;margin-top:.2rem}
.ocs-msg{padding:.8rem 1.2rem;border-radius:8px;margin-bottom:1.5rem;font-size:.9rem}
.ocs-msg.success{background:rgba(34,197,94,.12);border:1px solid #22c55e;color:#86efac}
.ocs-msg.error{background:rgba(239,68,68,.12);border:1px solid #ef4444;color:#fca5a5}
.ocs-section{margin-bottom:2.5rem}.ocs-section h2{font-size:1.25rem;color:var(--oaccent);margin:0 0 1rem;display:flex;align-items:center;gap:.5rem}
.ocs-card{background:var(--ocard);border:1px solid var(--oborder);border-radius:12px;padding:1.5rem;margin-bottom:1rem}
.prog-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(310px,1fr));gap:1rem}
.prog-card{background:var(--ocard);border:1px solid var(--oborder);border-radius:12px;padding:1.2rem;display:flex;flex-direction:column;gap:.6rem;transition:border-color .2s}
.prog-card:hover{border-color:var(--oaccent)}
.prog-track{display:inline-block;font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;padding:.2rem .6rem;border-radius:4px;color:#fff;width:fit-content}
.prog-name{font-size:1.05rem;font-weight:700;color:var(--otxt)}.prog-desc{font-size:.82rem;color:var(--osilver);line-height:1.5;flex:1}
.prog-meta{display:flex;flex-wrap:wrap;gap:.6rem;font-size:.75rem;color:var(--osilver)}.prog-meta span{display:flex;align-items:center;gap:.3rem}
.prog-enroll{display:flex;justify-content:space-between;align-items:center;margin-top:.4rem}
.prog-slots{font-size:.75rem;color:var(--osilver)}.prog-slots b{color:var(--otxt)}
.btn{display:inline-flex;align-items:center;gap:.4rem;padding:.5rem 1.1rem;border:none;border-radius:8px;font-size:.82rem;font-weight:600;cursor:pointer;transition:opacity .2s;text-decoration:none}
.btn:hover{opacity:.85}.btn-primary{background:var(--oaccent);color:#fff}.btn-gold{background:var(--ogold);color:#0f172a}
.btn-red{background:#ef4444;color:#fff}.btn-green{background:#22c55e;color:#fff}.btn-sm{padding:.35rem .7rem;font-size:.75rem}
.btn:disabled{opacity:.4;cursor:not-allowed}
.phase-bar{display:flex;gap:2px;margin:.8rem 0}.phase-step{flex:1;padding:.4rem;text-align:center;font-size:.7rem;font-weight:600;text-transform:uppercase;border-radius:4px;background:rgba(255,255,255,.05);color:var(--osilver);transition:all .3s}
.phase-step.done{background:var(--oaccent);color:#fff}.phase-step.current{background:var(--ogold);color:#0f172a}
.eval-form{display:grid;grid-template-columns:1fr 1fr;gap:1rem}.eval-form label{font-size:.8rem;color:var(--osilver);display:block;margin-bottom:.3rem}
.eval-form select,.eval-form input,.eval-form textarea{width:100%;padding:.5rem .7rem;background:var(--obg);border:1px solid var(--oborder);border-radius:6px;color:var(--otxt);font-size:.85rem}
.eval-form textarea{grid-column:1/-1;min-height:60px;resize:vertical}
.eval-form .ef-actions{grid-column:1/-1;display:flex;gap:.5rem}
.roster-tbl{width:100%;border-collapse:collapse;font-size:.82rem}
.roster-tbl th{text-align:left;padding:.6rem .5rem;border-bottom:2px solid var(--oborder);color:var(--osilver);text-transform:uppercase;font-size:.7rem;letter-spacing:1px}
.roster-tbl td{padding:.55rem .5rem;border-bottom:1px solid rgba(51,65,85,.5);color:var(--otxt)}
.roster-tbl tr:hover td{background:rgba(255,255,255,.03)}
.score-badge{display:inline-block;padding:.15rem .5rem;border-radius:4px;font-weight:700;font-size:.78rem}
.score-high{background:rgba(34,197,94,.2);color:#86efac}.score-mid{background:rgba(250,204,21,.15);color:#facc15}.score-low{background:rgba(239,68,68,.15);color:#fca5a5}
@media(max-width:600px){.prog-grid{grid-template-columns:1fr}.eval-form{grid-template-columns:1fr}.ocs-hero h1{font-size:1.5rem}}
</style>

<div class="ocs-page" style="background:var(--obg);min-height:100vh">

<?php if ($msg): ?>
<div class="ocs-msg <?= $msgType ?>"><?= $e($msg) ?></div>
<?php endif; ?>

<!-- Hero -->
<div class="ocs-hero">
    <h1><i class="fa-solid fa-star"></i> Officer Candidate School</h1>
    <p class="sub">The path to leadership begins here. Prove yourself worthy of command.</p>
    <div class="ocs-stats">
        <div class="ocs-stat"><div class="val"><?= $totalProgs ?></div><div class="lbl">Programs</div></div>
        <div class="ocs-stat"><div class="val"><?= $totalActive ?></div><div class="lbl">Active Candidates</div></div>
        <div class="ocs-stat"><div class="val"><?= $totalGrads ?></div><div class="lbl">Graduates</div></div>
        <div class="ocs-stat"><div class="val"><?= $gradRate ?>%</div><div class="lbl">Graduation Rate</div></div>
    </div>
</div>

<!-- My Enrollment -->
<?php if ($myEnrollment): ?>
<div class="ocs-section">
    <h2><i class="fa-solid fa-id-badge"></i> My Enrollment</h2>
    <div class="ocs-card">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem;margin-bottom:.8rem">
            <div>
                <span class="prog-track" style="background:<?= $trackColors[$myEnrollment['track']] ?? '#64748b' ?>"><?= $e($myEnrollment['track']) ?></span>
                <span class="prog-name" style="margin-left:.5rem"><?= $e($myEnrollment['prog_name']) ?></span>
            </div>
            <span style="font-size:.8rem;color:var(--osilver)">Class <?= $e($myEnrollment['class_number']) ?></span>
        </div>
        <div class="phase-bar">
            <?php foreach ($phases as $ph):
                $ci = array_search($myEnrollment['phase'], $phases);
                $pi = array_search($ph, $phases);
                $cls = $pi < $ci ? 'done' : ($pi === $ci ? 'current' : '');
            ?>
            <div class="phase-step <?= $cls ?>"><?= ucfirst(str_replace('_',' ',$ph)) ?></div>
            <?php endforeach; ?>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:.5rem;font-size:.82rem;color:var(--osilver);margin:.5rem 0">
            <div>Score: <b style="color:var(--otxt)"><?= number_format((float)$myEnrollment['overall_score'],1) ?>%</b></div>
            <div>Started: <b style="color:var(--otxt)"><?= $e($myEnrollment['start_date']) ?></b></div>
            <div>Duration: <b style="color:var(--otxt)"><?= $e($myEnrollment['duration_weeks']) ?> weeks</b></div>
        </div>
        <?php if ($myEvals): ?>
        <div style="margin-top:.8rem;font-size:.82rem">
            <strong style="color:var(--oaccent)">Recent Evaluations:</strong>
            <?php foreach (array_slice($myEvals, 0, 3) as $ev): ?>
            <div style="margin-top:.4rem;padding:.4rem .6rem;background:rgba(255,255,255,.03);border-radius:6px">
                <span style="text-transform:capitalize;color:var(--ogold)"><?= $e($ev['category']) ?></span>
                — <span class="score-badge <?= $ev['score']>=80?'score-high':($ev['score']>=50?'score-mid':'score-low') ?>"><?= (int)$ev['score'] ?></span>
                <?php if ($ev['comments']): ?><span style="color:var(--osilver);margin-left:.5rem"><?= $e(mb_strimwidth($ev['comments'],0,80,'…')) ?></span><?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <form method="post" style="margin-top:.8rem">
            <input type="hidden" name="csrf" value="<?= $e($csrf) ?>">
            <input type="hidden" name="action" value="withdraw">
            <input type="hidden" name="enrollment_id" value="<?= (int)$myEnrollment['id'] ?>">
            <button type="submit" class="btn btn-red btn-sm" onclick="return confirm('Withdraw from OCS? This cannot be undone.')"><i class="fa-solid fa-right-from-bracket"></i> Withdraw</button>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Program Catalog -->
<div class="ocs-section">
    <h2><i class="fa-solid fa-graduation-cap"></i> Program Catalog</h2>
    <div class="prog-grid">
    <?php foreach ($programs as $p):
        $full = (int)$p['enrolled_count'] >= (int)$p['max_enrollment'];
        $alreadyEnrolled = $myEnrollment && (int)$myEnrollment['program_id'] === (int)$p['id'];
        $meetsRank = $userRankTier >= (int)$p['min_rank_tier'] || $isCommander;
        $canEnroll = !$full && !$myEnrollment && $meetsRank;
    ?>
    <div class="prog-card">
        <span class="prog-track" style="background:<?= $trackColors[$p['track']] ?? '#64748b' ?>"><?= $e($p['track']) ?></span>
        <div class="prog-name"><?= $e($p['name']) ?></div>
        <div class="prog-desc"><?= $e($p['description']) ?></div>
        <div class="prog-meta">
            <span><i class="fa-solid fa-clock"></i> <?= (int)$p['duration_weeks'] ?> weeks</span>
            <span><i class="fa-solid fa-chevron-up"></i> → <?= $e(ucfirst($p['graduation_rank'])) ?></span>
            <span><i class="fa-solid fa-shield"></i> Tier <?= (int)$p['min_rank_tier'] ?>+</span>
        </div>
        <div class="prog-enroll">
            <span class="prog-slots"><b><?= (int)$p['enrolled_count'] ?></b>/<?= (int)$p['max_enrollment'] ?> enrolled</span>
            <?php if ($canEnroll): ?>
            <form method="post" style="margin:0"><input type="hidden" name="csrf" value="<?= $e($csrf) ?>"><input type="hidden" name="action" value="enroll"><input type="hidden" name="program_id" value="<?= (int)$p['id'] ?>">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Enroll</button></form>
            <?php elseif ($alreadyEnrolled): ?>
                <span class="btn btn-gold btn-sm" style="cursor:default"><i class="fa-solid fa-check"></i> Enrolled</span>
            <?php elseif ($full): ?>
                <span class="btn btn-sm" style="background:var(--oborder);color:var(--osilver);cursor:default">Full</span>
            <?php elseif (!$meetsRank): ?>
                <span class="btn btn-sm" style="background:var(--oborder);color:var(--osilver);cursor:default">Rank Required</span>
            <?php elseif ($myEnrollment): ?>
                <span class="btn btn-sm" style="background:var(--oborder);color:var(--osilver);cursor:default">Already in OCS</span>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
</div>

<!-- Evaluate Candidates (Officers tier 7+) -->
<?php if ($userRankTier >= 7 || $isCommander): ?>
<div class="ocs-section">
    <h2><i class="fa-solid fa-clipboard-check"></i> Evaluate Candidate</h2>
    <div class="ocs-card">
        <?php if (empty($activeCandidates)): ?>
            <p style="color:var(--osilver);font-size:.85rem">No active candidates to evaluate.</p>
        <?php else: ?>
        <form method="post" class="eval-form">
            <input type="hidden" name="csrf" value="<?= $e($csrf) ?>">
            <input type="hidden" name="action" value="evaluate">
            <div>
                <label>Candidate</label>
                <select name="enrollment_id" required>
                    <option value="">Select candidate…</option>
                    <?php foreach ($activeCandidates as $ac): ?>
                    <option value="<?= (int)$ac['id'] ?>"><?= $e(trim($ac['fname'].' '.$ac['lname'])) ?> — <?= $e($ac['prog_name']) ?> (<?= $e($ac['phase']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Category</label>
                <select name="category" required>
                    <?php foreach (['leadership','tactics','fitness','knowledge','teamwork','ethics'] as $c): ?>
                    <option value="<?= $c ?>"><?= ucfirst($c) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Score (0–100)</label>
                <input type="number" name="score" min="0" max="100" required placeholder="0-100">
            </div>
            <div></div>
            <textarea name="comments" placeholder="Evaluation comments (optional)"></textarea>
            <div class="ef-actions">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Submit Evaluation</button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<!-- Advance Phase / Graduate (Officers) -->
<div class="ocs-section">
    <h2><i class="fa-solid fa-arrow-up-right-dots"></i> Manage Candidates</h2>
    <div class="ocs-card">
        <?php if (empty($activeCandidates)): ?>
            <p style="color:var(--osilver);font-size:.85rem">No active candidates.</p>
        <?php else: ?>
        <div style="overflow-x:auto">
        <table class="roster-tbl">
            <thead><tr><th>Candidate</th><th>Program</th><th>Phase</th><th>Score</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($activeCandidates as $ac): ?>
            <tr>
                <td><?= $e(trim($ac['fname'].' '.$ac['lname'])) ?></td>
                <td><?= $e($ac['prog_name']) ?></td>
                <td style="text-transform:capitalize"><?= $e(str_replace('_',' ',$ac['phase'])) ?></td>
                <td>—</td>
                <td style="display:flex;gap:.3rem;flex-wrap:wrap">
                    <form method="post" style="margin:0"><input type="hidden" name="csrf" value="<?= $e($csrf) ?>"><input type="hidden" name="action" value="advance_phase"><input type="hidden" name="enrollment_id" value="<?= (int)$ac['id'] ?>">
                        <button type="submit" class="btn btn-primary btn-sm" <?= $ac['phase']==='final_exam'?'disabled':'' ?>><i class="fa-solid fa-forward"></i> Advance</button></form>
                    <?php if ($userRankTier >= 9 || $isCommander): ?>
                    <form method="post" style="margin:0"><input type="hidden" name="csrf" value="<?= $e($csrf) ?>"><input type="hidden" name="action" value="graduate"><input type="hidden" name="enrollment_id" value="<?= (int)$ac['id'] ?>">
                        <button type="submit" class="btn btn-gold btn-sm" onclick="return confirm('Graduate this candidate?')"><i class="fa-solid fa-medal"></i> Graduate</button></form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Class Roster -->
<div class="ocs-section">
    <h2><i class="fa-solid fa-users"></i> Class Roster</h2>
    <div class="ocs-card">
        <?php if (empty($roster)): ?>
            <p style="color:var(--osilver);font-size:.85rem">No active candidates in any program.</p>
        <?php else: ?>
        <div style="overflow-x:auto">
        <table class="roster-tbl">
            <thead><tr><th>Name</th><th>Program</th><th>Track</th><th>Phase</th><th>Score</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($roster as $r): ?>
            <tr>
                <td><?= $e(trim($r['fname'].' '.$r['lname'])) ?: 'ID#'.$e($r['client_id']) ?></td>
                <td><?= $e($r['prog_name']) ?></td>
                <td><span class="prog-track" style="background:<?= $trackColors[$r['track']] ?? '#64748b' ?>;font-size:.6rem;padding:.1rem .4rem"><?= $e($r['track']) ?></span></td>
                <td style="text-transform:capitalize"><?= $e(str_replace('_',' ',$r['phase'])) ?></td>
                <td><span class="score-badge <?= $r['overall_score']>=80?'score-high':($r['overall_score']>=50?'score-mid':'score-low') ?>"><?= number_format((float)$r['overall_score'],1) ?></span></td>
                <td style="text-transform:capitalize"><?= $e(str_replace('_',' ',$r['status'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>

</div>
<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
