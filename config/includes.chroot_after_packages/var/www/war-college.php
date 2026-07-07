<?php
/**
 * ═══════════════════════════════════════════
 *  National War College — Level 6: Sovereign State
 * ═══════════════════════════════════════════
 */
require_once __DIR__ . '/includes/db-config.inc.php';
$db = getSharedDB();
require_once __DIR__ . '/includes/auth-gate.inc.php';
require_once __DIR__ . '/includes/rank-guard.inc.php';
if (empty($_SESSION['csrf_wc'])) $_SESSION['csrf_wc'] = bin2hex(random_bytes(32));
requireRank(6);

$isCommander = ($clientId === 33);
$isFlag      = ($userRankTier >= 9) || $isCommander;
$isOfficer   = ($userRankTier >= 6) || $isCommander;
$msg = '';
$msgType = '';

// ── Auto-create tables ──
$db->exec("CREATE TABLE IF NOT EXISTS warcollege_programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_code VARCHAR(20) NOT NULL,
    program_name VARCHAR(120) NOT NULL,
    description TEXT DEFAULT NULL,
    duration_weeks INT DEFAULT 12,
    max_enrollment INT DEFAULT 20,
    prerequisite_tier INT DEFAULT 6,
    status ENUM('active','paused','archived') DEFAULT 'active',
    xp_reward INT DEFAULT 2000,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS warcollege_enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id INT NOT NULL,
    client_id INT NOT NULL,
    status ENUM('applied','accepted','in_progress','graduated','failed','withdrawn') DEFAULT 'applied',
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    accepted_at TIMESTAMP NULL,
    graduated_at TIMESTAMP NULL,
    overall_score DECIMAL(5,2) DEFAULT 0.00,
    mentor_id INT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS warcollege_curricula (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id INT NOT NULL,
    module_number INT NOT NULL,
    module_name VARCHAR(120) NOT NULL,
    module_type ENUM('lecture','case_study','exercise','paper','capstone') DEFAULT 'lecture',
    description TEXT DEFAULT NULL,
    required BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS warcollege_papers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT NOT NULL,
    module_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    grade ENUM('A','B','C','D','F') DEFAULT NULL,
    graded_by INT DEFAULT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    graded_at TIMESTAMP NULL,
    comments TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS warcollege_assessments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT NOT NULL,
    assessor_id INT NOT NULL,
    category ENUM('strategy','leadership','analysis','communication','innovation','judgment') NOT NULL,
    score INT DEFAULT 0,
    comments TEXT DEFAULT NULL,
    assessed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Seed programs ──
$pc = $db->query("SELECT COUNT(*) FROM warcollege_programs")->fetchColumn();
if ($pc == 0) {
    $progs = [
        ['WC-CMD', "Commander's Fellowship", 'The highest academic distinction — one slot. Direct mentorship under the Commander. Potential successor identification.', 52, 1, 9, 10000],
        ['WC-SEN', 'Senior Strategy Program', 'Advanced strategic analysis, operational planning, and theater-level decision making.', 24, 10, 8, 5000],
        ['WC-TAC', 'Tactical Mastery Course', 'Small-unit tactics, combined arms integration, and battlefield leadership.', 12, 20, 6, 2000],
        ['WC-INT', 'Intelligence Analysis Program', 'Intelligence collection, analysis, assessment, and dissemination across the intelligence cycle.', 16, 15, 6, 3000],
        ['WC-CYB', 'Cyber Warfare College', 'Offensive and defensive cyber operations, network defense, and information warfare.', 16, 15, 6, 3000]
    ];
    $ps = $db->prepare("INSERT INTO warcollege_programs (program_code, program_name, description, duration_weeks, max_enrollment, prerequisite_tier, xp_reward) VALUES (?,?,?,?,?,?,?)");
    foreach ($progs as $p) $ps->execute($p);

    // Seed curricula for Tactical Mastery
    $tid = $db->lastInsertId() - 2; // WC-TAC is the 3rd one
    $mods = [
        [1, 'Fundamentals of Combined Arms', 'lecture'],
        [2, 'Small Unit Tactics', 'case_study'],
        [3, 'Field Exercise: Movement to Contact', 'exercise'],
        [4, 'Operational Planning Paper', 'paper'],
        [5, 'Final Capstone: Live Fire Exercise', 'capstone']
    ];
    $ms = $db->prepare("INSERT INTO warcollege_curricula (program_id, module_number, module_name, module_type) VALUES (?,?,?,?)");
    // Get actual program IDs
    $tacId = $db->query("SELECT id FROM warcollege_programs WHERE program_code = 'WC-TAC'")->fetchColumn();
    if ($tacId) foreach ($mods as $m) $ms->execute([$tacId, $m[0], $m[1], $m[2]]);
}

$csrf = $_SESSION['csrf_wc'];

// ── POST Handlers ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, $_POST['csrf'] ?? '')) {
        $msg = 'Invalid security token.'; $msgType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'apply') {
            $progId = (int)($_POST['program_id'] ?? 0);
            $prog = $db->prepare("SELECT * FROM warcollege_programs WHERE id = ? AND status = 'active'");
            $prog->execute([$progId]);
            $progRow = $prog->fetch(PDO::FETCH_ASSOC);
            if (!$progRow) { $msg = 'Program not found.'; $msgType = 'error'; }
            elseif ($userRankTier < $progRow['prerequisite_tier'] && !$isCommander) { $msg = 'Rank tier ' . $progRow['prerequisite_tier'] . ' required.'; $msgType = 'error'; }
            else {
                $dup = $db->prepare("SELECT id FROM warcollege_enrollments WHERE program_id = ? AND client_id = ? AND status IN ('applied','accepted','in_progress')");
                $dup->execute([$progId, $clientId]);
                if ($dup->fetch()) { $msg = 'Already enrolled or applied.'; $msgType = 'error'; }
                else {
                    $enrolled = $db->prepare("SELECT COUNT(*) FROM warcollege_enrollments WHERE program_id = ? AND status IN ('accepted','in_progress')");
                    $enrolled->execute([$progId]);
                    if ($enrolled->fetchColumn() >= $progRow['max_enrollment']) { $msg = 'Program full.'; $msgType = 'error'; }
                    else {
                        $db->prepare("INSERT INTO warcollege_enrollments (program_id, client_id) VALUES (?,?)")->execute([$progId, $clientId]);
                        $msg = "Applied to <strong>" . htmlspecialchars($progRow['program_name']) . "</strong>."; $msgType = 'success';
                    }
                }
            }
        } elseif ($action === 'accept' && $isFlag) {
            $enId = (int)($_POST['enrollment_id'] ?? 0);
            $stmt = $db->prepare("UPDATE warcollege_enrollments SET status = 'accepted', accepted_at = NOW() WHERE id = ? AND status = 'applied'");
            $stmt->execute([$enId]);
            if ($stmt->rowCount()) {
                $db->prepare("UPDATE warcollege_enrollments SET status = 'in_progress' WHERE id = ?")->execute([$enId]);
                $msg = 'Applicant accepted and enrolled.'; $msgType = 'success';
            } else { $msg = 'Enrollment not found.'; $msgType = 'error'; }

        } elseif ($action === 'assign_mentor' && $isFlag) {
            $enId     = (int)($_POST['enrollment_id'] ?? 0);
            $mentorId = (int)($_POST['mentor_id'] ?? 0);
            $stmt = $db->prepare("UPDATE warcollege_enrollments SET mentor_id = ? WHERE id = ?");
            $stmt->execute([$mentorId, $enId]);
            $msg = $stmt->rowCount() ? 'Mentor assigned.' : 'Enrollment not found.';
            $msgType = $stmt->rowCount() ? 'success' : 'error';

        } elseif ($action === 'submit_paper') {
            $enId    = (int)($_POST['enrollment_id'] ?? 0);
            $modId   = (int)($_POST['module_id'] ?? 0);
            $title   = trim($_POST['paper_title'] ?? '');
            $content = trim($_POST['paper_content'] ?? '');
            $en = $db->prepare("SELECT * FROM warcollege_enrollments WHERE id = ? AND client_id = ? AND status = 'in_progress'");
            $en->execute([$enId, $clientId]);
            if (!$en->fetch()) { $msg = 'Not enrolled or not in progress.'; $msgType = 'error'; }
            elseif ($title === '' || $content === '') { $msg = 'Title and content required.'; $msgType = 'error'; }
            else {
                $db->prepare("INSERT INTO warcollege_papers (enrollment_id, module_id, title, content) VALUES (?,?,?,?)")
                   ->execute([$enId, $modId, $title, $content]);
                awardXP($clientId, 'paper_submitted', []);
                $msg = "Paper submitted: <strong>" . htmlspecialchars($title) . "</strong>."; $msgType = 'success';
            }
        } elseif ($action === 'grade_paper' && $isFlag) {
            $paperId = (int)($_POST['paper_id'] ?? 0);
            $grade   = $_POST['grade'] ?? 'C';
            $comments = trim($_POST['comments'] ?? '');
            $validG = ['A','B','C','D','F'];
            if (!in_array($grade, $validG, true)) { $msg = 'Invalid grade.'; $msgType = 'error'; }
            else {
                $stmt = $db->prepare("UPDATE warcollege_papers SET grade = ?, graded_by = ?, graded_at = NOW(), comments = ? WHERE id = ? AND grade IS NULL");
                $stmt->execute([$grade, $clientId, $comments, $paperId]);
                $msg = $stmt->rowCount() ? "Paper graded: <strong>$grade</strong>." : 'Paper not found or already graded.';
                $msgType = $stmt->rowCount() ? 'success' : 'error';
            }
        } elseif ($action === 'assess' && $isFlag) {
            $enId     = (int)($_POST['enrollment_id'] ?? 0);
            $category = $_POST['category'] ?? 'strategy';
            $score    = (int)($_POST['score'] ?? 0);
            $comments = trim($_POST['assess_comments'] ?? '');
            $validCat = ['strategy','leadership','analysis','communication','innovation','judgment'];
            if (!in_array($category, $validCat, true) || $score < 0 || $score > 100) {
                $msg = 'Invalid category or score.'; $msgType = 'error';
            } else {
                $db->prepare("INSERT INTO warcollege_assessments (enrollment_id, assessor_id, category, score, comments) VALUES (?,?,?,?,?)")
                   ->execute([$enId, $clientId, $category, $score, $comments]);
                $msg = "Assessment recorded: " . strtoupper($category) . " = $score/100."; $msgType = 'success';
            }
        } elseif ($action === 'graduate' && $isFlag) {
            $enId = (int)($_POST['enrollment_id'] ?? 0);
            $enrollment = $db->prepare("SELECT we.*, wp.xp_reward, wp.program_name FROM warcollege_enrollments we JOIN warcollege_programs wp ON wp.id = we.program_id WHERE we.id = ? AND we.status = 'in_progress'");
            $enrollment->execute([$enId]);
            $enRow = $enrollment->fetch(PDO::FETCH_ASSOC);
            if (!$enRow) { $msg = 'Enrollment not found.'; $msgType = 'error'; }
            else {
                $avgScore = $db->prepare("SELECT AVG(score) FROM warcollege_assessments WHERE enrollment_id = ?");
                $avgScore->execute([$enId]);
                $avg = round($avgScore->fetchColumn() ?: 0, 2);
                $db->prepare("UPDATE warcollege_enrollments SET status = 'graduated', graduated_at = NOW(), overall_score = ? WHERE id = ?")->execute([$avg, $enId]);
                awardXP($enRow['client_id'], 'war_college_graduate', ['program' => $enRow['program_name']]);
                $msg = "Graduated with score <strong>" . number_format($avg, 1) . "/100</strong>. War College Graduate decoration awarded."; $msgType = 'success';
            }
        } elseif ($action === 'select_fellow' && $isCommander) {
            $enId = (int)($_POST['enrollment_id'] ?? 0);
            $fellowProg = $db->query("SELECT id FROM warcollege_programs WHERE program_code = 'WC-CMD'")->fetchColumn();
            if (!$fellowProg) { $msg = 'Fellowship program not found.'; $msgType = 'error'; }
            else {
                $enrollment = $db->prepare("SELECT * FROM warcollege_enrollments WHERE id = ? AND status = 'graduated'");
                $enrollment->execute([$enId]);
                $enRow = $enrollment->fetch(PDO::FETCH_ASSOC);
                if (!$enRow) { $msg = 'Must be a graduate.'; $msgType = 'error'; }
                else {
                    $db->prepare("INSERT INTO warcollege_enrollments (program_id, client_id, status, accepted_at) VALUES (?,'in_progress',NOW())")
                       ->execute([$fellowProg, $enRow['client_id']]);
                    awardXP($enRow['client_id'], 'commander_fellow_selected', []);
                    $msg = "Selected for <strong>Commander's Fellowship</strong>. Potential successor identified."; $msgType = 'success';
                }
            }
        } else {
            $msg = 'Unauthorized or unknown action.'; $msgType = 'error';
        }
    }
    $_SESSION['csrf_wc'] = bin2hex(random_bytes(32));
    $csrf = $_SESSION['csrf_wc'];
}

// ── Data Fetch ──
$tab = $_GET['tab'] ?? 'programs';
$programs    = $db->query("SELECT wp.*, (SELECT COUNT(*) FROM warcollege_enrollments we WHERE we.program_id = wp.id AND we.status IN ('accepted','in_progress')) AS enrolled, (SELECT COUNT(*) FROM warcollege_enrollments we WHERE we.program_id = wp.id AND we.status = 'graduated') AS graduates FROM warcollege_programs wp ORDER BY wp.prerequisite_tier DESC, wp.program_code")->fetchAll(PDO::FETCH_ASSOC);
$enrollments = $db->query("SELECT we.*, wp.program_name, wp.program_code, CONCAT(c.firstname,' ',c.lastname) AS student_name, CONCAT(c2.firstname,' ',c2.lastname) AS mentor_name FROM warcollege_enrollments we JOIN warcollege_programs wp ON wp.id = we.program_id LEFT JOIN tblclients c ON c.id = we.client_id LEFT JOIN tblclients c2 ON c2.id = we.mentor_id ORDER BY we.applied_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$papers      = $db->query("SELECT wp2.*, we.client_id, wc.module_name, CONCAT(c.firstname,' ',c.lastname) AS author_name, CONCAT(c2.firstname,' ',c2.lastname) AS grader_name FROM warcollege_papers wp2 JOIN warcollege_enrollments we ON we.id = wp2.enrollment_id LEFT JOIN warcollege_curricula wc ON wc.id = wp2.module_id LEFT JOIN tblclients c ON c.id = we.client_id LEFT JOIN tblclients c2 ON c2.id = wp2.graded_by ORDER BY wp2.submitted_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$assessments = $db->query("SELECT wa.*, CONCAT(c.firstname,' ',c.lastname) AS assessor_name, we.client_id AS student_cid, CONCAT(c2.firstname,' ',c2.lastname) AS student_name FROM warcollege_assessments wa JOIN warcollege_enrollments we ON we.id = wa.enrollment_id LEFT JOIN tblclients c ON c.id = wa.assessor_id LEFT JOIN tblclients c2 ON c2.id = we.client_id ORDER BY wa.assessed_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$myEnrollments = $db->prepare("SELECT we.*, wp.program_name FROM warcollege_enrollments we JOIN warcollege_programs wp ON wp.id = we.program_id WHERE we.client_id = ? ORDER BY we.applied_at DESC");
$myEnrollments->execute([$clientId]);
$myEnrolls = $myEnrollments->fetchAll(PDO::FETCH_ASSOC);
$curricula = $db->query("SELECT wc.*, wp.program_code FROM warcollege_curricula wc JOIN warcollege_programs wp ON wp.id = wc.program_id ORDER BY wc.program_id, wc.module_number")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'National War College';
include __DIR__ . '/includes/site-header.inc.php';
?>
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<style>
.wc-wrap{max-width:1100px;margin:0 auto;padding:1.5rem}
.wc-bg{background:#0a0a14;min-height:100vh;color:#e2e8f0}
.wc-card{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:10px;padding:1.25rem;margin-bottom:1rem}
.wc-card:hover{border-color:#d4a017;box-shadow:0 0 12px rgba(212,160,23,.12)}
.wc-title{font-size:1.6rem;font-weight:700;color:#f1f5f9;margin-bottom:.25rem;display:flex;align-items:center;gap:.5rem}
.wc-sub{color:#94a3b8;font-size:.85rem}
.wc-badge{display:inline-block;padding:2px 10px;border-radius:999px;font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
.wc-tabs{display:flex;gap:.5rem;margin-bottom:1.25rem;flex-wrap:wrap}
.wc-tab{padding:.4rem 1rem;border-radius:6px;cursor:pointer;font-size:.8rem;background:#2a2a4a;color:#94a3b8;text-decoration:none;font-weight:600;border:none}
.wc-tab.active{background:#d4a017;color:#fff}
.wc-stat-bar{display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem}
.wc-stat{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:8px;padding:.75rem 1.25rem;flex:1;min-width:110px;text-align:center}
.wc-stat .val{font-size:1.5rem;font-weight:700;color:#d4a017}
.wc-stat .lbl{font-size:.7rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px}
.wc-btn{background:#d4a017;color:#fff;border:none;padding:.5rem 1.25rem;border-radius:6px;cursor:pointer;font-size:.85rem;font-weight:600;transition:.2s}
.wc-btn:hover{background:#b8860b}
.wc-btn-sm{padding:.3rem .75rem;font-size:.75rem}
.wc-btn-outline{background:transparent;border:1px solid #d4a017;color:#d4a017}
.wc-btn-outline:hover{background:#d4a017;color:#fff}
.wc-btn-green{background:#22c55e;color:#fff}.wc-btn-green:hover{background:#16a34a}
.wc-btn-blue{background:#3b82f6;color:#fff}.wc-btn-blue:hover{background:#2563eb}
.wc-btn-red{background:#ef4444;color:#fff}.wc-btn-red:hover{background:#dc2626}
.wc-input,.wc-select,.wc-textarea{width:100%;background:#0a0a14;border:1px solid #2a2a4a;color:#e2e8f0;padding:.5rem .75rem;border-radius:6px;font-size:.85rem;box-sizing:border-box}
.wc-textarea{min-height:100px;resize:vertical}
.wc-label{display:block;color:#94a3b8;font-size:.75rem;margin-bottom:.25rem;text-transform:uppercase;letter-spacing:.5px}
.wc-msg{padding:.75rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:.85rem}
.wc-msg-success{background:rgba(212,160,23,.12);border:1px solid #d4a017;color:#fbbf24}
.wc-msg-error{background:rgba(239,68,68,.12);border:1px solid #ef4444;color:#fca5a5}
.wc-modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:900;align-items:center;justify-content:center}
.wc-modal-bg.open{display:flex}
.wc-modal{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:12px;padding:1.5rem;width:90%;max-width:620px;max-height:80vh;overflow-y:auto}
.wc-modal h3{color:#f1f5f9;margin:0 0 1rem}
.wc-form-row{margin-bottom:.75rem}
.wc-grade{display:inline-block;width:26px;height:26px;border-radius:4px;text-align:center;line-height:26px;font-weight:700;font-size:.8rem}
</style>
<div class="wc-bg">
<div class="wc-wrap">
    <div class="wc-title"><i class="fas fa-university"></i> National War College</div>
    <p class="wc-sub" style="margin-bottom:1.25rem">Officer education, strategic programs, paper grading, and the Commander's Fellowship — Officer+ rank</p>

    <?php if ($msg): ?><div class="wc-msg wc-msg-<?= $msgType ?>"><?= $msg ?></div><?php endif; ?>

    <?php $totalGrads = count(array_filter($enrollments, fn($e) => $e['status'] === 'graduated')); ?>
    <div class="wc-stat-bar">
        <div class="wc-stat"><div class="val"><?= count($programs) ?></div><div class="lbl">Programs</div></div>
        <div class="wc-stat"><div class="val" style="color:#3b82f6"><?= count(array_filter($enrollments, fn($e) => $e['status'] === 'in_progress')) ?></div><div class="lbl">Enrolled</div></div>
        <div class="wc-stat"><div class="val" style="color:#22c55e"><?= $totalGrads ?></div><div class="lbl">Graduates</div></div>
        <div class="wc-stat"><div class="val" style="color:#a855f7"><?= count($papers) ?></div><div class="lbl">Papers</div></div>
        <div class="wc-stat"><div class="val" style="color:#f59e0b"><?= count($assessments) ?></div><div class="lbl">Assessments</div></div>
    </div>

    <div class="wc-tabs">
        <a href="?tab=programs" class="wc-tab <?= $tab==='programs'?'active':'' ?>"><i class="fas fa-graduation-cap"></i> Programs</a>
        <a href="?tab=enrollments" class="wc-tab <?= $tab==='enrollments'?'active':'' ?>"><i class="fas fa-users"></i> Enrollments</a>
        <a href="?tab=papers" class="wc-tab <?= $tab==='papers'?'active':'' ?>"><i class="fas fa-file-alt"></i> Papers</a>
        <a href="?tab=assessments" class="wc-tab <?= $tab==='assessments'?'active':'' ?>"><i class="fas fa-chart-bar"></i> Assessments</a>
        <a href="?tab=curricula" class="wc-tab <?= $tab==='curricula'?'active':'' ?>"><i class="fas fa-book"></i> Curricula</a>
    </div>

    <!-- ═══ TAB: PROGRAMS ═══ -->
    <?php if ($tab === 'programs'): ?>
        <?php
        $prColors = ['active'=>'#22c55e','paused'=>'#f59e0b','archived'=>'#64748b'];
        foreach ($programs as $p):
            $isFellowship = ($p['program_code'] === 'WC-CMD');
        ?>
            <div class="wc-card" style="<?= $isFellowship ? 'border:1px solid #d4a017;background:linear-gradient(135deg,#1a1a2e 0%,#2a1f0e 100%)' : '' ?>">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div>
                        <?php if ($isFellowship): ?><i class="fas fa-crown" style="color:#d4a017;margin-right:.25rem"></i><?php endif; ?>
                        <strong style="color:#d4a017"><?= htmlspecialchars($p['program_code']) ?></strong>
                        <strong style="color:#f1f5f9;margin-left:.5rem"><?= htmlspecialchars($p['program_name']) ?></strong>
                    </div>
                    <span class="wc-badge" style="background:<?= $prColors[$p['status']] ?>20;color:<?= $prColors[$p['status']] ?>"><?= strtoupper($p['status']) ?></span>
                </div>
                <p style="color:#94a3b8;font-size:.85rem;margin-top:.5rem"><?= htmlspecialchars($p['description'] ?? '') ?></p>
                <div style="color:#64748b;font-size:.75rem;margin-top:.25rem">
                    Duration: <?= $p['duration_weeks'] ?> weeks &bull;
                    Enrolled: <?= $p['enrolled'] ?>/<?= $p['max_enrollment'] ?> &bull;
                    Graduates: <?= $p['graduates'] ?> &bull;
                    XP Reward: <?= number_format($p['xp_reward']) ?> &bull;
                    Min Tier: <?= $p['prerequisite_tier'] ?>
                </div>
                <?php if ($p['status'] === 'active' && (!$isFellowship || $isCommander)): ?>
                    <form method="POST" style="margin-top:.5rem">
                        <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="apply"><input type="hidden" name="program_id" value="<?= $p['id'] ?>">
                        <button class="wc-btn-sm wc-btn"><i class="fas fa-hand-point-up"></i> Apply</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

    <!-- ═══ TAB: ENROLLMENTS ═══ -->
    <?php elseif ($tab === 'enrollments'): ?>
        <?php
        $esColors = ['applied'=>'#94a3b8','accepted'=>'#3b82f6','in_progress'=>'#f59e0b','graduated'=>'#22c55e','failed'=>'#ef4444','withdrawn'=>'#64748b'];
        foreach ($enrollments as $e): ?>
            <div class="wc-card" style="border-left:3px solid <?= $esColors[$e['status']] ?>">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div>
                        <strong style="color:#f1f5f9"><?= htmlspecialchars($e['student_name'] ?? 'Unknown') ?></strong>
                        <span style="color:#94a3b8;margin-left:.5rem"><?= htmlspecialchars($e['program_name']) ?></span>
                    </div>
                    <span class="wc-badge" style="background:<?= $esColors[$e['status']] ?>20;color:<?= $esColors[$e['status']] ?>"><?= strtoupper(str_replace('_', ' ', $e['status'])) ?></span>
                </div>
                <div style="color:#64748b;font-size:.75rem;margin-top:.25rem">
                    Applied: <?= date('M j, Y', strtotime($e['applied_at'])) ?>
                    <?php if ($e['mentor_name']): ?>&bull; Mentor: <?= htmlspecialchars($e['mentor_name']) ?><?php endif; ?>
                    <?php if ($e['overall_score'] > 0): ?>&bull; Score: <?= number_format($e['overall_score'], 1) ?>/100<?php endif; ?>
                </div>
                <?php if ($isFlag): ?>
                    <div style="margin-top:.5rem;display:flex;gap:.5rem;flex-wrap:wrap">
                        <?php if ($e['status'] === 'applied'): ?>
                            <form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="accept"><input type="hidden" name="enrollment_id" value="<?= $e['id'] ?>"><button class="wc-btn-sm wc-btn wc-btn-green"><i class="fas fa-check"></i> Accept</button></form>
                        <?php endif; ?>
                        <?php if ($e['status'] === 'in_progress'): ?>
                            <form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="graduate"><input type="hidden" name="enrollment_id" value="<?= $e['id'] ?>"><button class="wc-btn-sm wc-btn"><i class="fas fa-graduation-cap"></i> Graduate</button></form>
                            <button class="wc-btn-sm wc-btn wc-btn-blue" onclick="openAssess(<?= $e['id'] ?>)"><i class="fas fa-chart-bar"></i> Assess</button>
                        <?php endif; ?>
                        <?php if ($e['status'] === 'graduated' && $isCommander): ?>
                            <form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="select_fellow"><input type="hidden" name="enrollment_id" value="<?= $e['id'] ?>"><button class="wc-btn-sm wc-btn" style="background:#d4a017"><i class="fas fa-crown"></i> Select Fellow</button></form>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if (empty($enrollments)): ?><div class="wc-card" style="text-align:center;color:#64748b"><p>No enrollments.</p></div><?php endif; ?>

    <!-- ═══ TAB: PAPERS ═══ -->
    <?php elseif ($tab === 'papers'): ?>
        <?php if (!empty($myEnrolls)): ?>
            <div style="margin-bottom:1rem"><button class="wc-btn" onclick="document.getElementById('modalPaper').classList.add('open')"><i class="fas fa-file-alt"></i> Submit Paper</button></div>
        <?php endif; ?>
        <?php
        $gradeColors = ['A'=>'#22c55e','B'=>'#3b82f6','C'=>'#f59e0b','D'=>'#f97316','F'=>'#ef4444'];
        foreach ($papers as $p): ?>
            <div class="wc-card">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div>
                        <strong style="color:#f1f5f9"><?= htmlspecialchars($p['title']) ?></strong>
                        <?php if ($p['grade']): ?><span class="wc-grade" style="background:<?= $gradeColors[$p['grade']] ?>30;color:<?= $gradeColors[$p['grade']] ?>;margin-left:.5rem"><?= $p['grade'] ?></span><?php endif; ?>
                    </div>
                    <span style="color:#64748b;font-size:.75rem"><?= date('M j, Y', strtotime($p['submitted_at'])) ?></span>
                </div>
                <div style="color:#94a3b8;font-size:.8rem;margin-top:.25rem">
                    Author: <?= htmlspecialchars($p['author_name'] ?? 'Unknown') ?> &bull;
                    Module: <?= htmlspecialchars($p['module_name'] ?? 'General') ?>
                    <?php if ($p['grader_name']): ?>&bull; Graded by: <?= htmlspecialchars($p['grader_name']) ?><?php endif; ?>
                </div>
                <?php
                $preview = mb_substr($p['content'], 0, 200);
                if (mb_strlen($p['content']) > 200) $preview .= '...';
                ?>
                <p style="color:#cbd5e1;font-size:.85rem;margin-top:.5rem;border-top:1px solid #2a2a4a;padding-top:.5rem"><?= htmlspecialchars($preview) ?></p>
                <?php if ($p['comments']): ?><div style="color:#d4a017;font-size:.8rem;margin-top:.25rem"><strong>Comments:</strong> <?= htmlspecialchars($p['comments']) ?></div><?php endif; ?>
                <?php if (!$p['grade'] && $isFlag): ?>
                    <form method="POST" style="margin-top:.5rem;display:flex;gap:.5rem;align-items:center;flex-wrap:wrap">
                        <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="grade_paper"><input type="hidden" name="paper_id" value="<?= $p['id'] ?>">
                        <select name="grade" class="wc-select" style="width:auto"><option value="A">A</option><option value="B">B</option><option value="C" selected>C</option><option value="D">D</option><option value="F">F</option></select>
                        <input type="text" name="comments" class="wc-input" style="flex:1" placeholder="Grading comments...">
                        <button class="wc-btn-sm wc-btn"><i class="fas fa-pen"></i> Grade</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if (empty($papers)): ?><div class="wc-card" style="text-align:center;color:#64748b"><p>No papers submitted.</p></div><?php endif; ?>

    <!-- ═══ TAB: ASSESSMENTS ═══ -->
    <?php elseif ($tab === 'assessments'): ?>
        <?php
        $catColors = ['strategy'=>'#3b82f6','leadership'=>'#d4a017','analysis'=>'#22c55e','communication'=>'#f59e0b','innovation'=>'#a855f7','judgment'=>'#ef4444'];
        foreach ($assessments as $a): ?>
            <div class="wc-card" style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
                <div style="width:50px;height:50px;border-radius:8px;background:<?= $catColors[$a['category']] ?>20;display:flex;align-items:center;justify-content:center;color:<?= $catColors[$a['category']] ?>;font-size:1.2rem;font-weight:700"><?= $a['score'] ?></div>
                <div style="flex:1">
                    <strong style="color:#f1f5f9"><?= htmlspecialchars($a['student_name'] ?? 'Unknown') ?></strong>
                    <span class="wc-badge" style="background:<?= $catColors[$a['category']] ?>20;color:<?= $catColors[$a['category']] ?>;margin-left:.5rem"><?= strtoupper($a['category']) ?></span>
                    <div style="color:#64748b;font-size:.75rem">Assessor: <?= htmlspecialchars($a['assessor_name'] ?? 'Unknown') ?> &bull; <?= date('M j, Y', strtotime($a['assessed_at'])) ?></div>
                    <?php if ($a['comments']): ?><div style="color:#94a3b8;font-size:.8rem;margin-top:.25rem"><?= htmlspecialchars($a['comments']) ?></div><?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($assessments)): ?><div class="wc-card" style="text-align:center;color:#64748b"><p>No assessments recorded.</p></div><?php endif; ?>

    <!-- ═══ TAB: CURRICULA ═══ -->
    <?php elseif ($tab === 'curricula'): ?>
        <?php
        $mtColors = ['lecture'=>'#3b82f6','case_study'=>'#f59e0b','exercise'=>'#22c55e','paper'=>'#a855f7','capstone'=>'#d4a017'];
        $mtIcons  = ['lecture'=>'fa-chalkboard-teacher','case_study'=>'fa-microscope','exercise'=>'fa-running','paper'=>'fa-file-alt','capstone'=>'fa-trophy'];
        $grouped  = [];
        foreach ($curricula as $c) $grouped[$c['program_code']][] = $c;
        foreach ($grouped as $code => $modules): ?>
            <div class="wc-card">
                <h4 style="color:#d4a017;margin:0 0 .75rem"><i class="fas fa-book"></i> <?= htmlspecialchars($code) ?></h4>
                <?php foreach ($modules as $m): ?>
                    <div style="display:flex;align-items:center;gap:.5rem;padding:.4rem 0;border-bottom:1px solid #2a2a4a">
                        <span style="color:#64748b;font-size:.75rem;width:24px;text-align:center"><?= $m['module_number'] ?></span>
                        <i class="fas <?= $mtIcons[$m['module_type']] ?? 'fa-book' ?>" style="color:<?= $mtColors[$m['module_type']] ?>;font-size:.8rem"></i>
                        <span style="color:#f1f5f9;font-size:.85rem;flex:1"><?= htmlspecialchars($m['module_name']) ?></span>
                        <span class="wc-badge" style="background:<?= $mtColors[$m['module_type']] ?>20;color:<?= $mtColors[$m['module_type']] ?>"><?= strtoupper(str_replace('_', ' ', $m['module_type'])) ?></span>
                        <?php if ($m['required']): ?><span style="color:#ef4444;font-size:.65rem;font-weight:600">REQ</span><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
        <?php if (empty($curricula)): ?><div class="wc-card" style="text-align:center;color:#64748b"><p>No curricula defined.</p></div><?php endif; ?>
    <?php endif; ?>
</div>
</div>

<!-- Modals -->
<div class="wc-modal-bg" id="modalPaper"><div class="wc-modal"><h3><i class="fas fa-file-alt"></i> Submit Paper</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="submit_paper">
<div class="wc-form-row"><label class="wc-label">Enrollment</label><select name="enrollment_id" class="wc-select"><?php foreach ($myEnrolls as $me): if ($me['status'] === 'in_progress'): ?><option value="<?= $me['id'] ?>"><?= htmlspecialchars($me['program_name']) ?></option><?php endif; endforeach; ?></select></div>
<div class="wc-form-row"><label class="wc-label">Module</label><select name="module_id" class="wc-select"><option value="0">General</option><?php foreach ($curricula as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['module_name']) ?></option><?php endforeach; ?></select></div>
<div class="wc-form-row"><label class="wc-label">Title</label><input type="text" name="paper_title" class="wc-input" required></div>
<div class="wc-form-row"><label class="wc-label">Content</label><textarea name="paper_content" class="wc-textarea" required style="min-height:200px"></textarea></div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="wc-btn wc-btn-outline" onclick="this.closest('.wc-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="wc-btn"><i class="fas fa-paper-plane"></i> Submit</button></div></form></div></div>

<div class="wc-modal-bg" id="modalAssess"><div class="wc-modal"><h3><i class="fas fa-chart-bar"></i> Assess Student</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="assess"><input type="hidden" name="enrollment_id" id="assessEnrollId" value="">
<div class="wc-form-row"><label class="wc-label">Category</label><select name="category" class="wc-select"><option value="strategy">Strategy</option><option value="leadership">Leadership</option><option value="analysis">Analysis</option><option value="communication">Communication</option><option value="innovation">Innovation</option><option value="judgment">Judgment</option></select></div>
<div class="wc-form-row"><label class="wc-label">Score (0-100)</label><input type="number" name="score" class="wc-input" min="0" max="100" value="75"></div>
<div class="wc-form-row"><label class="wc-label">Comments</label><textarea name="assess_comments" class="wc-textarea" style="min-height:60px"></textarea></div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="wc-btn wc-btn-outline" onclick="this.closest('.wc-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="wc-btn wc-btn-blue"><i class="fas fa-chart-bar"></i> Assess</button></div></form></div></div>

<script>
function openAssess(id){document.getElementById('assessEnrollId').value=id;document.getElementById('modalAssess').classList.add('open')}
document.querySelectorAll('.wc-modal-bg').forEach(bg=>{bg.addEventListener('click',e=>{if(e.target===bg)bg.classList.remove('open')})});
</script>
<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
