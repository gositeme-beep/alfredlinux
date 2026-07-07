<?php
require_once __DIR__ . '/includes/db-config.inc.php';
$db = getSharedDB();
require_once __DIR__ . '/includes/auth-gate.inc.php';
require_once __DIR__ . '/includes/rank-guard.inc.php';
requireRank(2);

if (empty($_SESSION['csrf_training'])) {
    $_SESSION['csrf_training'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_training'];

$flash = '';
$flashType = 'info';

// POST: Enroll in course
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!hash_equals($csrf, $_POST['csrf'] ?? '')) {
        $flash = 'Invalid security token. Please try again.';
        $flashType = 'error';
    } else {
        $action = $_POST['action'];

        if ($action === 'enroll') {
            $courseCode = trim($_POST['course_code'] ?? '');
            $course = $db->prepare("SELECT * FROM training_courses WHERE course_code = ? AND is_active = 1");
            $course->execute([$courseCode]);
            $course = $course->fetch(PDO::FETCH_ASSOC);

            if (!$course) {
                $flash = 'Course not found.';
                $flashType = 'error';
            } elseif ($userRankTier < (int)$course['required_rank_tier']) {
                $flash = 'Your rank is too low for this course.';
                $flashType = 'error';
            } else {
                $existing = $db->prepare("SELECT id FROM training_enrollments WHERE client_id = ? AND course_code = ? AND status IN ('enrolled','in_progress')");
                $existing->execute([$clientId, $courseCode]);
                if ($existing->fetch()) {
                    $flash = 'You are already enrolled in this course.';
                    $flashType = 'error';
                } else {
                    $stmt = $db->prepare("INSERT INTO training_enrollments (client_id, course_code, status, enrolled_at) VALUES (?, ?, 'enrolled', NOW())");
                    $stmt->execute([$clientId, $courseCode]);
                    $db->prepare("INSERT INTO training_progress (client_id, course_code, current_chapter, status, started_at) VALUES (?, ?, 1, 'in_progress', NOW())")->execute([$clientId, $courseCode]);
                    $flash = 'Enrolled in ' . htmlspecialchars($course['course_name'], ENT_QUOTES, 'UTF-8') . '!';
                    $flashType = 'success';
                }
            }
        }

        if ($action === 'complete_module') {
            $courseCode = trim($_POST['course_code'] ?? '');
            $moduleNum = (int)($_POST['module_number'] ?? 0);
            $score = max(0, min(100, (int)($_POST['score'] ?? 0)));

            $module = $db->prepare("SELECT * FROM training_modules WHERE course_code = ? AND module_number = ?");
            $module->execute([$courseCode, $moduleNum]);
            $module = $module->fetch(PDO::FETCH_ASSOC);

            $enrolled = $db->prepare("SELECT id FROM training_enrollments WHERE client_id = ? AND course_code = ? AND status IN ('enrolled','in_progress')");
            $enrolled->execute([$clientId, $courseCode]);

            if (!$module || !$enrolled->fetch()) {
                $flash = 'Invalid module or not enrolled.';
                $flashType = 'error';
            } else {
                $passed = $score >= (int)$module['passing_score'] ? 1 : 0;
                $db->prepare("INSERT INTO training_exams (client_id, course_code, module_number, score, passed, taken_at) VALUES (?, ?, ?, ?, ?, NOW())")
                   ->execute([$clientId, $courseCode, $moduleNum, $score, $passed]);

                if ($passed && (int)$module['xp_reward'] > 0) {
                    awardXP($db, $clientId, 'training_module', (int)$module['xp_reward']);
                }

                // Advance progress
                $course = $db->prepare("SELECT chapters_count, passing_score, xp_reward, course_name FROM training_courses WHERE course_code = ?");
                $course->execute([$courseCode]);
                $course = $course->fetch(PDO::FETCH_ASSOC);

                $nextChapter = $moduleNum + 1;
                if ($passed && $nextChapter > (int)$course['chapters_count']) {
                    // Course complete
                    $certCode = strtoupper($courseCode) . '-' . $clientId . '-' . date('Ymd') . '-' . substr(bin2hex(random_bytes(4)), 0, 8);
                    $db->prepare("UPDATE training_enrollments SET status = 'completed', completed_at = NOW(), final_score = ?, certificate_code = ? WHERE client_id = ? AND course_code = ? AND status IN ('enrolled','in_progress')")
                       ->execute([$score, $certCode, $clientId, $courseCode]);
                    $db->prepare("UPDATE training_progress SET current_chapter = ?, status = 'completed', score = ?, completed_at = NOW() WHERE client_id = ? AND course_code = ?")
                       ->execute([$moduleNum, $score, $clientId, $courseCode]);
                    if ((int)$course['xp_reward'] > 0) {
                        awardXP($db, $clientId, 'training_module', (int)$course['xp_reward']);
                    }
                    $flash = 'Course completed! Certificate: ' . htmlspecialchars($certCode, ENT_QUOTES, 'UTF-8');
                    $flashType = 'success';
                } elseif ($passed) {
                    $db->prepare("UPDATE training_progress SET current_chapter = ?, score = ? WHERE client_id = ? AND course_code = ?")
                       ->execute([$nextChapter, $score, $clientId, $courseCode]);
                    $flash = 'Module passed! Advancing to chapter ' . $nextChapter . '.';
                    $flashType = 'success';
                } else {
                    $flash = 'Score ' . $score . '% — you need ' . $module['passing_score'] . '% to pass. Try again.';
                    $flashType = 'error';
                }
            }
        }
    }
    $_SESSION['csrf_training'] = bin2hex(random_bytes(32));
    $csrf = $_SESSION['csrf_training'];
}

// Course detail view
$viewCourse = null;
$viewModules = [];
$viewProgress = null;
$viewExams = [];
if (!empty($_GET['course'])) {
    $code = trim($_GET['course']);
    $stmt = $db->prepare("SELECT * FROM training_courses WHERE course_code = ? AND is_active = 1");
    $stmt->execute([$code]);
    $viewCourse = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($viewCourse) {
        $stmt = $db->prepare("SELECT * FROM training_modules WHERE course_code = ? ORDER BY module_number ASC");
        $stmt->execute([$code]);
        $viewModules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $db->prepare("SELECT * FROM training_progress WHERE client_id = ? AND course_code = ?");
        $stmt->execute([$clientId, $code]);
        $viewProgress = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = $db->prepare("SELECT * FROM training_exams WHERE client_id = ? AND course_code = ? ORDER BY module_number ASC, taken_at DESC");
        $stmt->execute([$clientId, $code]);
        $viewExams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Catalog
$courses = $db->query("SELECT * FROM training_courses WHERE is_active = 1 ORDER BY required_rank_tier ASC, course_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Enrollments
$enrollments = $db->prepare("SELECT e.*, c.course_name, c.icon, c.chapters_count, c.xp_reward AS course_xp FROM training_enrollments e JOIN training_courses c ON c.course_code = e.course_code WHERE e.client_id = ? ORDER BY e.enrolled_at DESC");
$enrollments->execute([$clientId]);
$enrollments = $enrollments->fetchAll(PDO::FETCH_ASSOC);

$typeLabels = ['boot_camp' => 'Boot Camp', 'nco_school' => 'NCO School', 'ocs' => 'OCS', 'specialty' => 'Specialty'];
$typeColors = ['boot_camp' => '#22c55e', 'nco_school' => '#eab308', 'ocs' => '#a855f7', 'specialty' => '#3b82f6'];
$statusColors = ['enrolled' => '#3b82f6', 'in_progress' => '#eab308', 'completed' => '#22c55e', 'failed' => '#ef4444', 'withdrawn' => '#64748b'];
$pageTitle = $viewCourse ? htmlspecialchars($viewCourse['course_name'], ENT_QUOTES, 'UTF-8') : 'Training Academy';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $pageTitle ?> — GoSiteMe</title>
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{background:#0f172a;color:#e2e8f0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;min-height:100vh}
a{color:#60a5fa;text-decoration:none}a:hover{text-decoration:underline}
.wrap{max-width:1200px;margin:0 auto;padding:24px 16px}
h1{font-size:1.8rem;margin-bottom:8px}
.sub{color:#94a3b8;margin-bottom:24px}
.flash{padding:12px 16px;border-radius:8px;margin-bottom:20px;font-weight:500}
.flash-success{background:#14532d;border:1px solid #22c55e;color:#bbf7d0}
.flash-error{background:#7f1d1d;border:1px solid #ef4444;color:#fecaca}
.flash-info{background:#1e3a5f;border:1px solid #3b82f6;color:#bfdbfe}
.tabs{display:flex;gap:8px;margin-bottom:24px;flex-wrap:wrap}
.tab{padding:8px 18px;border-radius:6px;background:#1e293b;border:1px solid #334155;cursor:pointer;color:#94a3b8;font-weight:600;transition:.2s}
.tab.active,.tab:hover{background:#3b82f6;color:#fff;border-color:#3b82f6}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:20px}
.card{background:#1e293b;border:1px solid #334155;border-radius:12px;padding:20px;transition:border-color .2s}
.card:hover{border-color:#3b82f6}
.card-head{display:flex;align-items:center;gap:12px;margin-bottom:12px}
.card-icon{font-size:1.6rem;color:#3b82f6}
.badge{display:inline-block;padding:2px 10px;border-radius:999px;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em}
.card-desc{color:#94a3b8;font-size:.88rem;margin-bottom:14px;line-height:1.5}
.meta{display:flex;flex-wrap:wrap;gap:12px;font-size:.8rem;color:#64748b;margin-bottom:14px}
.meta i{margin-right:4px}
.btn{display:inline-block;padding:8px 20px;border-radius:6px;border:none;font-weight:600;cursor:pointer;font-size:.88rem;transition:.2s}
.btn-primary{background:#3b82f6;color:#fff}.btn-primary:hover{background:#2563eb}
.btn-sm{padding:6px 14px;font-size:.8rem}
.btn-disabled{background:#334155;color:#64748b;cursor:not-allowed}
.progress-bar{height:8px;background:#334155;border-radius:4px;overflow:hidden;margin:8px 0}
.progress-fill{height:100%;background:#3b82f6;border-radius:4px;transition:width .4s}
.module-list{list-style:none;margin-top:16px}
.module-item{display:flex;align-items:center;gap:12px;padding:12px 16px;background:#1e293b;border:1px solid #334155;border-radius:8px;margin-bottom:8px}
.module-num{width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:50%;font-weight:700;font-size:.85rem}
.module-done{background:#14532d;color:#22c55e}
.module-current{background:#1e3a5f;color:#60a5fa}
.module-locked{background:#334155;color:#64748b}
.module-info{flex:1}
.module-title{font-weight:600;font-size:.95rem}
.module-type{font-size:.75rem;color:#64748b;text-transform:uppercase}
.cert-box{background:linear-gradient(135deg,#1e293b,#0f172a);border:2px solid #eab308;border-radius:12px;padding:32px;text-align:center;margin-top:24px}
.cert-box h3{color:#eab308;font-size:1.3rem;margin-bottom:8px}
.cert-code{font-family:monospace;font-size:1.1rem;color:#fbbf24;background:#334155;padding:8px 16px;border-radius:6px;display:inline-block;margin-top:12px}
.back-link{display:inline-flex;align-items:center;gap:6px;margin-bottom:20px;color:#94a3b8;font-size:.9rem}
.back-link:hover{color:#e2e8f0}
.section-title{font-size:1.2rem;font-weight:700;margin:32px 0 16px;padding-bottom:8px;border-bottom:1px solid #334155}
.empty{text-align:center;color:#64748b;padding:40px;font-size:.95rem}
@media(max-width:640px){.grid{grid-template-columns:1fr}.wrap{padding:16px 10px}h1{font-size:1.4rem}}
</style>
</head>
<body>
<div class="wrap">
<?php if ($flash): ?>
<div class="flash flash-<?= $flashType ?>"><?= $flash ?></div>
<?php endif; ?>

<?php if ($viewCourse): ?>
<!-- COURSE DETAIL VIEW -->
<a href="training.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Academy</a>
<div class="card-head">
    <span class="card-icon"><i class="fas fa-<?= htmlspecialchars($viewCourse['icon'] ?: 'book', ENT_QUOTES, 'UTF-8') ?>"></i></span>
    <div>
        <h1><?= htmlspecialchars($viewCourse['course_name'], ENT_QUOTES, 'UTF-8') ?></h1>
        <span class="badge" style="background:<?= $typeColors[$viewCourse['course_type']] ?? '#3b82f6' ?>;color:#000">
            <?= $typeLabels[$viewCourse['course_type']] ?? $viewCourse['course_type'] ?>
        </span>
    </div>
</div>
<p class="card-desc" style="margin-top:12px"><?= htmlspecialchars($viewCourse['description'], ENT_QUOTES, 'UTF-8') ?></p>
<div class="meta" style="margin-top:12px">
    <span><i class="fas fa-layer-group"></i> <?= (int)$viewCourse['chapters_count'] ?> Chapters</span>
    <span><i class="fas fa-bullseye"></i> <?= (int)$viewCourse['passing_score'] ?>% to Pass</span>
    <span><i class="fas fa-star"></i> <?= (int)$viewCourse['xp_reward'] ?> XP</span>
    <span><i class="fas fa-shield-halved"></i> Rank Tier <?= (int)$viewCourse['required_rank_tier'] ?>+</span>
</div>

<?php if ($viewProgress): ?>
<div style="margin-top:20px">
    <div style="display:flex;justify-content:space-between;font-size:.85rem;color:#94a3b8">
        <span>Progress: Chapter <?= (int)$viewProgress['current_chapter'] ?> / <?= (int)$viewCourse['chapters_count'] ?></span>
        <span><?= round(((int)$viewProgress['current_chapter'] - 1) / max(1, (int)$viewCourse['chapters_count']) * 100) ?>%</span>
    </div>
    <div class="progress-bar"><div class="progress-fill" style="width:<?= round(((int)$viewProgress['current_chapter'] - 1) / max(1, (int)$viewCourse['chapters_count']) * 100) ?>%"></div></div>
</div>
<?php endif; ?>

<?php
$examsByModule = [];
foreach ($viewExams as $ex) $examsByModule[(int)$ex['module_number']][] = $ex;
$currentChapter = $viewProgress ? (int)$viewProgress['current_chapter'] : 0;
$isEnrolled = $viewProgress && $viewProgress['status'] !== 'completed';
$isCompleted = $viewProgress && $viewProgress['status'] === 'completed';
?>

<h2 class="section-title"><i class="fas fa-list-ol" style="margin-right:8px"></i>Modules</h2>
<ul class="module-list">
<?php foreach ($viewModules as $mod):
    $mNum = (int)$mod['module_number'];
    $modExams = $examsByModule[$mNum] ?? [];
    $bestScore = 0; $modPassed = false;
    foreach ($modExams as $mex) { if ((int)$mex['score'] > $bestScore) $bestScore = (int)$mex['score']; if ($mex['passed']) $modPassed = true; }
    $isCurrent = $isEnrolled && $mNum === $currentChapter;
    $isLocked = !$isCompleted && (!$isEnrolled || $mNum > $currentChapter);
    $stateClass = $modPassed ? 'module-done' : ($isCurrent ? 'module-current' : 'module-locked');
    $stateIcon = $modPassed ? 'check' : ($isCurrent ? 'play' : 'lock');
?>
<li class="module-item">
    <div class="module-num <?= $stateClass ?>"><i class="fas fa-<?= $stateIcon ?>"></i></div>
    <div class="module-info">
        <div class="module-title"><?= htmlspecialchars($mod['title'], ENT_QUOTES, 'UTF-8') ?></div>
        <div class="module-type"><?= htmlspecialchars($mod['content_type'], ENT_QUOTES, 'UTF-8') ?> · <?= (int)$mod['passing_score'] ?>% to pass · <?= (int)$mod['xp_reward'] ?> XP</div>
        <?php if ($modPassed): ?><div style="color:#22c55e;font-size:.8rem;margin-top:2px"><i class="fas fa-check-circle"></i> Passed (<?= $bestScore ?>%)</div><?php endif; ?>
        <?php if (!empty($modExams) && !$modPassed): ?><div style="color:#eab308;font-size:.8rem;margin-top:2px"><i class="fas fa-redo"></i> Best: <?= $bestScore ?>%</div><?php endif; ?>
    </div>
    <?php if ($isCurrent && !$modPassed): ?>
    <form method="post" style="display:flex;gap:6px;align-items:center">
        <input type="hidden" name="csrf" value="<?= $csrf ?>">
        <input type="hidden" name="action" value="complete_module">
        <input type="hidden" name="course_code" value="<?= htmlspecialchars($viewCourse['course_code'], ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="module_number" value="<?= $mNum ?>">
        <input type="number" name="score" min="0" max="100" value="0" style="width:60px;padding:4px 8px;border-radius:4px;border:1px solid #334155;background:#0f172a;color:#e2e8f0;font-size:.85rem" required>
        <button type="submit" class="btn btn-primary btn-sm">Submit</button>
    </form>
    <?php endif; ?>
</li>
<?php endforeach; ?>
</ul>

<?php
// Certificate
if ($isCompleted):
    $cert = $db->prepare("SELECT certificate_code, final_score, completed_at FROM training_enrollments WHERE client_id = ? AND course_code = ? AND status = 'completed' ORDER BY completed_at DESC LIMIT 1");
    $cert->execute([$clientId, $viewCourse['course_code']]);
    $cert = $cert->fetch(PDO::FETCH_ASSOC);
    if ($cert && $cert['certificate_code']):
?>
<div class="cert-box">
    <i class="fas fa-award" style="font-size:2.5rem;color:#eab308;margin-bottom:12px"></i>
    <h3>Course Completed</h3>
    <p style="color:#94a3b8">Final Score: <?= (int)$cert['final_score'] ?>% · <?= date('M j, Y', strtotime($cert['completed_at'])) ?></p>
    <div class="cert-code"><?= htmlspecialchars($cert['certificate_code'], ENT_QUOTES, 'UTF-8') ?></div>
</div>
<?php endif; endif; ?>

<?php else: ?>
<!-- MAIN CATALOG VIEW -->
<h1><i class="fas fa-graduation-cap" style="margin-right:10px;color:#3b82f6"></i>Training Academy</h1>
<p class="sub">Welcome, <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>. Select a course to begin your advancement.</p>

<div class="tabs">
    <span class="tab active" onclick="showTab('catalog')">Course Catalog</span>
    <span class="tab" onclick="showTab('enrolled')">My Enrollments (<?= count($enrollments) ?>)</span>
</div>

<!-- CATALOG -->
<div id="tab-catalog">
<?php if (empty($courses)): ?>
<div class="empty"><i class="fas fa-inbox" style="font-size:2rem;display:block;margin-bottom:12px"></i>No courses available yet.</div>
<?php else: ?>
<div class="grid">
<?php foreach ($courses as $c):
    $canEnroll = $userRankTier >= (int)$c['required_rank_tier'];
    $alreadyEnrolled = false;
    foreach ($enrollments as $en) { if ($en['course_code'] === $c['course_code'] && in_array($en['status'], ['enrolled','in_progress'])) { $alreadyEnrolled = true; break; } }
?>
<div class="card">
    <div class="card-head">
        <span class="card-icon"><i class="fas fa-<?= htmlspecialchars($c['icon'] ?: 'book', ENT_QUOTES, 'UTF-8') ?>"></i></span>
        <div style="flex:1">
            <a href="training.php?course=<?= urlencode($c['course_code']) ?>" style="color:#e2e8f0;font-weight:700;font-size:1.05rem"><?= htmlspecialchars($c['course_name'], ENT_QUOTES, 'UTF-8') ?></a>
            <div style="margin-top:4px"><span class="badge" style="background:<?= $typeColors[$c['course_type']] ?? '#3b82f6' ?>;color:#000"><?= $typeLabels[$c['course_type']] ?? $c['course_type'] ?></span></div>
        </div>
    </div>
    <p class="card-desc"><?= htmlspecialchars($c['description'], ENT_QUOTES, 'UTF-8') ?></p>
    <div class="meta">
        <span><i class="fas fa-layer-group"></i> <?= (int)$c['chapters_count'] ?> Ch</span>
        <span><i class="fas fa-bullseye"></i> <?= (int)$c['passing_score'] ?>%</span>
        <span><i class="fas fa-star"></i> <?= (int)$c['xp_reward'] ?> XP</span>
        <span><i class="fas fa-shield-halved"></i> Tier <?= (int)$c['required_rank_tier'] ?>+</span>
    </div>
    <?php if ($alreadyEnrolled): ?>
        <a href="training.php?course=<?= urlencode($c['course_code']) ?>" class="btn btn-primary btn-sm">Continue <i class="fas fa-arrow-right"></i></a>
    <?php elseif ($canEnroll): ?>
        <form method="post" style="display:inline">
            <input type="hidden" name="csrf" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="enroll">
            <input type="hidden" name="course_code" value="<?= htmlspecialchars($c['course_code'], ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="btn btn-primary btn-sm">Enroll <i class="fas fa-plus"></i></button>
        </form>
    <?php else: ?>
        <span class="btn btn-disabled btn-sm"><i class="fas fa-lock"></i> Rank Too Low</span>
    <?php endif; ?>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>

<!-- ENROLLMENTS -->
<div id="tab-enrolled" style="display:none">
<?php if (empty($enrollments)): ?>
<div class="empty"><i class="fas fa-clipboard-list" style="font-size:2rem;display:block;margin-bottom:12px"></i>You haven't enrolled in any courses yet.</div>
<?php else: ?>
<div class="grid">
<?php foreach ($enrollments as $en):
    $prog = $db->prepare("SELECT current_chapter FROM training_progress WHERE client_id = ? AND course_code = ?");
    $prog->execute([$clientId, $en['course_code']]);
    $prog = $prog->fetch(PDO::FETCH_ASSOC);
    $ch = $prog ? (int)$prog['current_chapter'] : 1;
    $total = max(1, (int)$en['chapters_count']);
    $pct = $en['status'] === 'completed' ? 100 : round(($ch - 1) / $total * 100);
?>
<div class="card">
    <div class="card-head">
        <span class="card-icon"><i class="fas fa-<?= htmlspecialchars($en['icon'] ?: 'book', ENT_QUOTES, 'UTF-8') ?>"></i></span>
        <div style="flex:1">
            <a href="training.php?course=<?= urlencode($en['course_code']) ?>" style="color:#e2e8f0;font-weight:700"><?= htmlspecialchars($en['course_name'], ENT_QUOTES, 'UTF-8') ?></a>
            <div style="margin-top:4px">
                <span class="badge" style="background:<?= $statusColors[$en['status']] ?? '#64748b' ?>;color:#000"><?= ucfirst(str_replace('_', ' ', $en['status'])) ?></span>
            </div>
        </div>
    </div>
    <div style="font-size:.85rem;color:#94a3b8;margin-bottom:8px">Enrolled <?= date('M j, Y', strtotime($en['enrolled_at'])) ?></div>
    <div style="display:flex;justify-content:space-between;font-size:.8rem;color:#64748b">
        <span>Chapter <?= $ch ?> / <?= $total ?></span><span><?= $pct ?>%</span>
    </div>
    <div class="progress-bar"><div class="progress-fill" style="width:<?= $pct ?>%"></div></div>
    <?php if ($en['status'] === 'completed' && $en['certificate_code']): ?>
    <div style="margin-top:8px;font-size:.8rem;color:#eab308"><i class="fas fa-award"></i> <?= htmlspecialchars($en['certificate_code'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <div style="margin-top:12px">
        <a href="training.php?course=<?= urlencode($en['course_code']) ?>" class="btn btn-primary btn-sm"><?= $en['status'] === 'completed' ? 'View' : 'Continue' ?> <i class="fas fa-arrow-right"></i></a>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>

<script>
function showTab(name){
    document.querySelectorAll('[id^="tab-"]').forEach(e=>e.style.display='none');
    document.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));
    document.getElementById('tab-'+name).style.display='block';
    event.target.classList.add('active');
}
</script>
<?php endif; ?>
</div>
</body>
</html>