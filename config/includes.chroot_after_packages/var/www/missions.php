<?php
/**
 * Mission Board — Level 3 Military Rank System
 * Browse, accept, and track missions. Earn XP completing objectives.
 */
require_once __DIR__ . '/includes/auth-gate.inc.php';
require_once __DIR__ . '/includes/rank-guard.inc.php';

$db = getSharedDB();

// Handle mission actions (accept, complete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($clientId) && $userRankTier >= 1) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (hash_equals($_SESSION['csrf_missions'] ?? '', $csrf)) {
        $missionId = (int)($_POST['mission_id'] ?? 0);
        $action = $_POST['action'] ?? '';

        if ($action === 'accept' && $missionId > 0) {
            // Check mission exists and user qualifies
            $mCheck = $db->prepare("SELECT * FROM missions WHERE id = ? AND is_active = 1");
            $mCheck->execute([$missionId]);
            $mission = $mCheck->fetch(PDO::FETCH_ASSOC);

            if ($mission && $userRankTier >= (int)$mission['required_rank_tier']) {
                // Check not already assigned
                $aCheck = $db->prepare("SELECT id FROM mission_assignments WHERE mission_id = ? AND client_id = ?");
                $aCheck->execute([$missionId, $clientId]);
                if (!$aCheck->fetch()) {
                    $db->prepare("INSERT INTO mission_assignments (mission_id, client_id, status, started_at) VALUES (?, ?, 'in_progress', NOW())")
                       ->execute([$missionId, $clientId]);

                    // Create notification
                    $db->prepare("INSERT INTO military_notifications (client_id, notification_type, title, message, data) VALUES (?, 'mission_assigned', ?, ?, ?)")
                       ->execute([$clientId, "Mission Accepted: {$mission['title']}", $mission['description'],
                           json_encode(['mission_id' => $missionId, 'xp_reward' => $mission['xp_reward']])]);
                }
            }
        }

        if ($action === 'complete' && $missionId > 0) {
            // Mark as completed — only if in_progress
            $aCheck = $db->prepare("SELECT ma.*, m.xp_reward, m.title FROM mission_assignments ma JOIN missions m ON m.id = ma.mission_id WHERE ma.mission_id = ? AND ma.client_id = ? AND ma.status = 'in_progress'");
            $aCheck->execute([$missionId, $clientId]);
            $assignment = $aCheck->fetch(PDO::FETCH_ASSOC);

            if ($assignment) {
                $db->prepare("UPDATE mission_assignments SET status = 'completed', progress = 100, completed_at = NOW(), xp_awarded = ? WHERE mission_id = ? AND client_id = ?")
                   ->execute([$assignment['xp_reward'], $missionId, $clientId]);

                $db->prepare("UPDATE missions SET current_completions = current_completions + 1 WHERE id = ?")
                   ->execute([$missionId]);

                // Award XP
                $xpResult = awardXP($clientId, 'mission_complete', ['mission_id' => $missionId, 'title' => $assignment['title']]);

                // Notification
                $db->prepare("INSERT INTO military_notifications (client_id, notification_type, title, message, data) VALUES (?, 'mission_complete', ?, ?, ?)")
                   ->execute([$clientId, "Mission Complete: {$assignment['title']}",
                       "You earned {$xpResult['xp_awarded']} XP!",
                       json_encode(['mission_id' => $missionId, 'xp_awarded' => $xpResult['xp_awarded'], 'rank_up' => $xpResult['rank_up'], 'new_rank' => $xpResult['new_rank']])]);

                // If rank up, create promotion notification
                if ($xpResult['rank_up']) {
                    $db->prepare("INSERT INTO military_notifications (client_id, notification_type, title, message, data) VALUES (?, 'rank_up', ?, ?, ?)")
                       ->execute([$clientId, "PROMOTED: {$xpResult['new_rank']}!",
                           "Your dedication has been recognized. You have been promoted to {$xpResult['new_rank']}!",
                           json_encode(['new_rank' => $xpResult['new_rank'], 'total_xp' => $xpResult['total_xp']])]);
                }
            }
        }
    }
    header('Location: /missions');
    exit;
}

// Generate CSRF
if (empty($_SESSION['csrf_missions'])) {
    $_SESSION['csrf_missions'] = bin2hex(random_bytes(32));
}

// Load available missions
$availableMissions = $db->query("
    SELECT * FROM missions
    WHERE is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())
    ORDER BY FIELD(mission_type, 'daily','weekly','campaign','special','critical'), difficulty, xp_reward
")->fetchAll(PDO::FETCH_ASSOC);

// Load user's assignments
$myAssignments = [];
if (!empty($clientId)) {
    $aStmt = $db->prepare("SELECT mission_id, status, progress, started_at, completed_at, xp_awarded FROM mission_assignments WHERE client_id = ?");
    $aStmt->execute([$clientId]);
    foreach ($aStmt->fetchAll(PDO::FETCH_ASSOC) as $a) {
        $myAssignments[$a['mission_id']] = $a;
    }
}

// Stats
$completedCount = 0;
$totalXPFromMissions = 0;
foreach ($myAssignments as $a) {
    if ($a['status'] === 'completed') {
        $completedCount++;
        $totalXPFromMissions += (int)$a['xp_awarded'];
    }
}

$pageTitle = 'Mission Board — GoSiteMe Military';
include __DIR__ . '/includes/site-header.inc.php';
?>
<style>
.mb-page{max-width:1000px;margin:0 auto;padding:2rem 1.5rem}
.mb-hero{text-align:center;margin-bottom:2rem}
.mb-hero h1{font-size:2.2rem;color:#e2b340;font-weight:800;margin-bottom:.3rem}
.mb-hero .sub{color:#888;font-size:.95rem}
.mb-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:2rem}
.mb-stat{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:10px;padding:1rem;text-align:center}
.mb-stat .v{font-size:1.6rem;color:#e2b340;font-weight:800}
.mb-stat .l{color:#888;font-size:.75rem;text-transform:uppercase;letter-spacing:1px;margin-top:.2rem}
.mb-type-header{color:#e2b340;font-size:1rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;margin:1.5rem 0 .8rem;padding-bottom:.3rem;border-bottom:1px solid rgba(226,179,64,.2)}
.m-card{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08);border-radius:10px;padding:1.2rem;margin-bottom:.8rem;display:grid;grid-template-columns:1fr auto;gap:1rem;align-items:center;transition:border-color .3s}
.m-card:hover{border-color:rgba(226,179,64,.3)}
.m-card.completed-card{opacity:.6;border-color:rgba(76,175,80,.3)}
.m-card.in-progress-card{border-color:rgba(33,150,243,.3)}
.m-title{color:#eee;font-weight:700;font-size:.95rem;margin-bottom:.3rem}
.m-desc{color:#888;font-size:.8rem;line-height:1.4;margin-bottom:.5rem}
.m-meta{display:flex;gap:.8rem;flex-wrap:wrap;font-size:.7rem}
.m-tag{padding:2px 8px;border-radius:4px;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
.m-tag.daily{background:rgba(33,150,243,.2);color:#42a5f5}
.m-tag.weekly{background:rgba(156,39,176,.2);color:#ab47bc}
.m-tag.special{background:rgba(255,152,0,.2);color:#ffa726}
.m-tag.campaign{background:rgba(76,175,80,.2);color:#66bb6a}
.m-tag.critical{background:rgba(244,67,54,.2);color:#ef5350}
.m-tag.easy{background:rgba(76,175,80,.15);color:#4caf50}
.m-tag.medium{background:rgba(255,193,7,.15);color:#ffc107}
.m-tag.hard{background:rgba(255,87,34,.15);color:#ff5722}
.m-tag.legendary{background:rgba(226,179,64,.2);color:#e2b340}
.m-xp{color:#e2b340;font-weight:800;font-size:.8rem}
.m-rank-req{color:#999;font-size:.7rem}
.m-actions form{display:inline}
.m-btn{padding:.5rem 1.2rem;border:none;border-radius:6px;font-weight:700;cursor:pointer;font-size:.8rem;transition:transform .2s}
.m-btn:hover{transform:scale(1.05)}
.m-btn.accept{background:#e2b340;color:#111}
.m-btn.complete{background:#4caf50;color:#fff}
.m-btn.locked{background:#333;color:#666;cursor:not-allowed}
.m-status{font-size:.75rem;font-weight:700;padding:4px 10px;border-radius:4px}
.m-status.in_progress{background:rgba(33,150,243,.2);color:#42a5f5}
.m-status.completed{background:rgba(76,175,80,.2);color:#4caf50}
.m-status.failed{background:rgba(244,67,54,.2);color:#ef5350}
</style>

<main class="main-content">
<div class="mb-page">

    <div class="mb-hero">
        <h1>&#x1F3AF; Mission Board</h1>
        <p class="sub">Accept missions, complete objectives, earn XP, rise through the ranks</p>
    </div>

    <?php if (!empty($clientId) && $userRankTier >= 1): ?>
    <div class="mb-stats">
        <div class="mb-stat"><div class="v"><?= $completedCount ?></div><div class="l">Missions Completed</div></div>
        <div class="mb-stat"><div class="v"><?= number_format($totalXPFromMissions) ?></div><div class="l">XP from Missions</div></div>
        <div class="mb-stat"><div class="v"><?= count(array_filter($myAssignments, fn($a) => $a['status'] === 'in_progress')) ?></div><div class="l">Active Missions</div></div>
        <div class="mb-stat"><div class="v"><?= count($availableMissions) ?></div><div class="l">Available</div></div>
    </div>
    <?php endif; ?>

    <?php
    $currentType = '';
    $typeLabels = ['daily'=>'&#x2600;&#xFE0F; Daily Missions','weekly'=>'&#x1F4C5; Weekly Missions','campaign'=>'&#x1F3F4; Campaign Missions','special'=>'&#x2B50; Special Missions','critical'=>'&#x1F6A8; Critical Missions'];

    foreach ($availableMissions as $m):
        if ($m['mission_type'] !== $currentType):
            $currentType = $m['mission_type'];
            echo '<div class="mb-type-header">' . ($typeLabels[$currentType] ?? ucfirst($currentType)) . '</div>';
        endif;

        $assignment = $myAssignments[$m['id']] ?? null;
        $status = $assignment['status'] ?? null;
        $canAccept = !$assignment && $userRankTier >= (int)$m['required_rank_tier'];
        $canComplete = $status === 'in_progress';
        $cardClass = $status === 'completed' ? 'completed-card' : ($status === 'in_progress' ? 'in-progress-card' : '');
    ?>
    <div class="m-card <?= $cardClass ?>">
        <div>
            <div class="m-title"><?= htmlspecialchars($m['title']) ?></div>
            <div class="m-desc"><?= htmlspecialchars($m['description']) ?></div>
            <div class="m-meta">
                <span class="m-tag <?= $m['mission_type'] ?>"><?= $m['mission_type'] ?></span>
                <span class="m-tag <?= $m['difficulty'] ?>"><?= $m['difficulty'] ?></span>
                <span class="m-xp">+<?= number_format($m['xp_reward']) ?> XP</span>
                <?php if ((int)$m['required_rank_tier'] > 1): ?>
                    <span class="m-rank-req">Requires Tier <?= $m['required_rank_tier'] ?>+</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="m-actions">
            <?php if ($status === 'completed'): ?>
                <span class="m-status completed">&#x2714; Completed</span>
            <?php elseif ($canComplete): ?>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_missions']) ?>">
                    <input type="hidden" name="mission_id" value="<?= $m['id'] ?>">
                    <input type="hidden" name="action" value="complete">
                    <button type="submit" class="m-btn complete">&#x2714; Complete</button>
                </form>
            <?php elseif ($status === 'in_progress'): ?>
                <span class="m-status in_progress">In Progress</span>
            <?php elseif ($canAccept && !empty($clientId)): ?>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_missions']) ?>">
                    <input type="hidden" name="mission_id" value="<?= $m['id'] ?>">
                    <input type="hidden" name="action" value="accept">
                    <button type="submit" class="m-btn accept">Accept Mission</button>
                </form>
            <?php elseif (empty($clientId)): ?>
                <a href="/enlist" class="m-btn locked">Enlist First</a>
            <?php elseif ($userRankTier < (int)$m['required_rank_tier']): ?>
                <span class="m-btn locked" title="Rank too low">&#x1F512; Tier <?= $m['required_rank_tier'] ?>+</span>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <?php if (empty($availableMissions)): ?>
    <div style="text-align:center;padding:3rem;color:#666">
        <div style="font-size:3rem;margin-bottom:1rem">&#x1F3AF;</div>
        <p>No active missions at this time. Check back soon!</p>
    </div>
    <?php endif; ?>

    <div style="text-align:center;margin-top:2rem;padding-bottom:2rem">
        <a href="/military-hq" style="color:#e2b340;text-decoration:underline">← Back to Military HQ</a>
        &nbsp;|&nbsp;
        <a href="/leaderboard" style="color:#e2b340;text-decoration:underline">View Leaderboard →</a>
    </div>

</div>
</main>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
