<?php
/**
 * ═══════════════════════════════════════════
 *  Ministry of Propaganda & State Media — Level 6: Sovereign State
 * ═══════════════════════════════════════════
 */
require_once __DIR__ . '/includes/db-config.inc.php';
$db = getSharedDB();
require_once __DIR__ . '/includes/auth-gate.inc.php';
require_once __DIR__ . '/includes/rank-guard.inc.php';
if (empty($_SESSION['csrf_pr'])) $_SESSION['csrf_pr'] = bin2hex(random_bytes(32));
requireRank(1);

$isCommander = ($clientId === 33);
$isFlag      = ($userRankTier >= 9) || $isCommander;
$isOfficer   = ($userRankTier >= 7) || $isCommander;
$isNCO       = ($userRankTier >= 4) || $isCommander;
$msg = '';
$msgType = '';

// ── Auto-create tables ──
$db->exec("CREATE TABLE IF NOT EXISTS media_publications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pub_code VARCHAR(20) NOT NULL,
    title VARCHAR(250) NOT NULL,
    content_type ENUM('article','report','briefing','broadcast','editorial','intelligence','decree') DEFAULT 'article',
    body TEXT NOT NULL,
    status ENUM('draft','submitted','under_review','approved','published','retracted') DEFAULT 'draft',
    classification ENUM('public','internal','restricted','classified') DEFAULT 'public',
    author_id INT NOT NULL,
    reviewer_id INT DEFAULT NULL,
    published_at TIMESTAMP NULL,
    retracted_at TIMESTAMP NULL,
    retraction_reason TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    views INT DEFAULT 0,
    platform VARCHAR(100) DEFAULT 'Pulse'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS media_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    publication_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    verdict ENUM('approve','reject','revise') NOT NULL,
    comments TEXT DEFAULT NULL,
    reviewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS media_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_code VARCHAR(20) NOT NULL,
    campaign_name VARCHAR(200) NOT NULL,
    objective TEXT NOT NULL,
    campaign_type ENUM('narrative','counter_propaganda','morale','recruitment','deterrence','awareness') DEFAULT 'narrative',
    status ENUM('planning','active','paused','concluded','cancelled') DEFAULT 'planning',
    target_audience VARCHAR(200) DEFAULT 'General Population',
    platforms TEXT DEFAULT NULL,
    launched_by INT DEFAULT NULL,
    launched_at TIMESTAMP NULL,
    concluded_at TIMESTAMP NULL,
    notes TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS media_press_corps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    press_code VARCHAR(20) NOT NULL,
    accreditation_level ENUM('correspondent','reporter','editor','bureau_chief','anchor') DEFAULT 'correspondent',
    beat VARCHAR(120) DEFAULT 'General',
    status ENUM('active','suspended','revoked') DEFAULT 'active',
    articles_published INT DEFAULT 0,
    accredited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    suspended_reason TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS media_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    metric_type ENUM('reach','engagement','sentiment','penetration') NOT NULL,
    metric_value DECIMAL(14,2) NOT NULL,
    measured_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$csrf = $_SESSION['csrf_pr'];

// ── POST Handlers ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, $_POST['csrf'] ?? '')) {
        $msg = 'Invalid security token.'; $msgType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'submit_publication' && $isNCO) {
            $title   = trim($_POST['pub_title'] ?? '');
            $type    = $_POST['content_type'] ?? 'article';
            $body    = trim($_POST['pub_body'] ?? '');
            $class   = $_POST['classification'] ?? 'public';
            $platform = trim($_POST['platform'] ?? 'Pulse');
            $validT = ['article','report','briefing','broadcast','editorial','intelligence','decree'];
            $validC = ['public','internal','restricted','classified'];
            if ($title === '' || $body === '' || !in_array($type, $validT, true) || !in_array($class, $validC, true)) {
                $msg = 'Title, body, and valid type required.'; $msgType = 'error';
            } elseif ($type === 'decree' && !$isCommander) {
                $msg = 'Only the Commander may issue Decrees.'; $msgType = 'error';
            } else {
                $code = 'PUB-' . strtoupper(bin2hex(random_bytes(3)));
                $status = ($type === 'decree' && $isCommander) ? 'published' : 'submitted';
                $pubAt  = ($status === 'published') ? date('Y-m-d H:i:s') : null;
                $db->prepare("INSERT INTO media_publications (pub_code, title, content_type, body, status, classification, author_id, published_at, platform) VALUES (?,?,?,?,?,?,?,?,?)")
                   ->execute([$code, $title, $type, $body, $status, $class, $clientId, $pubAt, $platform]);
                if ($status === 'published') awardXP($clientId, 'decree_published', []);
                $msg = ($status === 'published') ? "Decree <strong>$code</strong> published." : "Publication <strong>$code</strong> submitted for review."; $msgType = 'success';
            }
        } elseif ($action === 'review_publication' && $isFlag) {
            $pubId   = (int)($_POST['pub_id'] ?? 0);
            $verdict = $_POST['verdict'] ?? '';
            $comments = trim($_POST['review_comments'] ?? '');
            $validV = ['approve','reject','revise'];
            if (!in_array($verdict, $validV, true)) { $msg = 'Invalid verdict.'; $msgType = 'error'; }
            else {
                $db->prepare("INSERT INTO media_reviews (publication_id, reviewer_id, verdict, comments) VALUES (?,?,?,?)")
                   ->execute([$pubId, $clientId, $verdict, $comments]);
                if ($verdict === 'approve') {
                    // Count approvals — need 3 to publish (or Commander overrides)
                    $appCount = $db->prepare("SELECT COUNT(*) FROM media_reviews WHERE publication_id = ? AND verdict = 'approve'");
                    $appCount->execute([$pubId]);
                    $approved = $appCount->fetchColumn();
                    if ($approved >= 3 || $isCommander) {
                        $db->prepare("UPDATE media_publications SET status = 'approved', reviewer_id = ? WHERE id = ?")->execute([$clientId, $pubId]);
                        $msg = "Publication APPROVED (votes: $approved)."; $msgType = 'success';
                    } else {
                        $db->prepare("UPDATE media_publications SET status = 'under_review' WHERE id = ?")->execute([$pubId]);
                        $msg = "Approval recorded ($approved/3)."; $msgType = 'success';
                    }
                } elseif ($verdict === 'reject') {
                    $db->prepare("UPDATE media_publications SET status = 'draft', reviewer_id = ? WHERE id = ?")->execute([$clientId, $pubId]);
                    $msg = "Publication REJECTED — returned to draft."; $msgType = 'success';
                } else {
                    $db->prepare("UPDATE media_publications SET status = 'under_review' WHERE id = ?")->execute([$pubId]);
                    $msg = "Revision requested."; $msgType = 'success';
                }
            }
        } elseif ($action === 'publish' && $isOfficer) {
            $pubId = (int)($_POST['pub_id'] ?? 0);
            $pub = $db->prepare("SELECT * FROM media_publications WHERE id = ? AND status = 'approved'");
            $pub->execute([$pubId]);
            if (!$pub->fetch()) { $msg = 'Only approved content can be published.'; $msgType = 'error'; }
            else {
                $db->prepare("UPDATE media_publications SET status = 'published', published_at = NOW() WHERE id = ?")->execute([$pubId]);
                $db->exec("UPDATE media_press_corps SET articles_published = articles_published + 1 WHERE client_id = (SELECT author_id FROM media_publications WHERE id = " . (int)$pubId . ")");
                awardXP($clientId, 'publication_published', []);
                $msg = "Content PUBLISHED."; $msgType = 'success';
            }
        } elseif ($action === 'retract' && $isFlag) {
            $pubId  = (int)($_POST['pub_id'] ?? 0);
            $reason = trim($_POST['retract_reason'] ?? '');
            if ($reason === '') { $msg = 'Retraction reason required.'; $msgType = 'error'; }
            else {
                $db->prepare("UPDATE media_publications SET status = 'retracted', retracted_at = NOW(), retraction_reason = ? WHERE id = ? AND status = 'published'")
                   ->execute([$reason, $pubId]);
                $msg = "Publication RETRACTED."; $msgType = 'success';
            }
        } elseif ($action === 'create_campaign' && $isOfficer) {
            $name      = trim($_POST['camp_name'] ?? '');
            $objective = trim($_POST['objective'] ?? '');
            $type      = $_POST['camp_type'] ?? 'narrative';
            $audience  = trim($_POST['audience'] ?? 'General Population');
            $platforms = trim($_POST['platforms'] ?? '');
            $validCT = ['narrative','counter_propaganda','morale','recruitment','deterrence','awareness'];
            if ($name === '' || $objective === '' || !in_array($type, $validCT, true)) {
                $msg = 'Campaign name, objective and valid type required.'; $msgType = 'error';
            } else {
                $code = 'CMP-' . strtoupper(bin2hex(random_bytes(3)));
                $db->prepare("INSERT INTO media_campaigns (campaign_code, campaign_name, objective, campaign_type, target_audience, platforms, launched_by) VALUES (?,?,?,?,?,?,?)")
                   ->execute([$code, $name, $objective, $type, $audience, $platforms, $clientId]);
                $msg = "Campaign <strong>$code</strong> created."; $msgType = 'success';
            }
        } elseif ($action === 'launch_campaign' && $isOfficer) {
            $campId = (int)($_POST['camp_id'] ?? 0);
            $db->prepare("UPDATE media_campaigns SET status = 'active', launched_at = NOW() WHERE id = ? AND status = 'planning'")->execute([$campId]);
            awardXP($clientId, 'campaign_launched', []);
            $msg = "Campaign LAUNCHED."; $msgType = 'success';
        } elseif ($action === 'conclude_campaign' && $isFlag) {
            $campId = (int)($_POST['camp_id'] ?? 0);
            $db->prepare("UPDATE media_campaigns SET status = 'concluded', concluded_at = NOW() WHERE id = ? AND status = 'active'")->execute([$campId]);
            $msg = "Campaign concluded."; $msgType = 'success';
        } elseif ($action === 'accredit_journalist' && $isOfficer) {
            $targetId = (int)($_POST['journalist_id'] ?? 0);
            $level    = $_POST['acc_level'] ?? 'correspondent';
            $beat     = trim($_POST['beat'] ?? 'General');
            $validAL = ['correspondent','reporter','editor','bureau_chief','anchor'];
            if ($targetId <= 0 || !in_array($level, $validAL, true)) { $msg = 'Valid journalist ID and level required.'; $msgType = 'error'; }
            else {
                $code = 'PRESS-' . strtoupper(bin2hex(random_bytes(3)));
                $db->prepare("INSERT INTO media_press_corps (client_id, press_code, accreditation_level, beat) VALUES (?,?,?,?)")
                   ->execute([$targetId, $code, $level, $beat]);
                awardXP($targetId, 'press_accredited', ['level' => $level]);
                $msg = "Press credential <strong>$code</strong> issued ($level)."; $msgType = 'success';
            }
        } elseif ($action === 'track_metrics' && $isOfficer) {
            $campId = (int)($_POST['camp_id'] ?? 0);
            $mType  = $_POST['metric_type'] ?? '';
            $mValue = (float)($_POST['metric_value'] ?? 0);
            $notes  = trim($_POST['metric_notes'] ?? '');
            $validMT = ['reach','engagement','sentiment','penetration'];
            if (!in_array($mType, $validMT, true) || $mValue < 0) { $msg = 'Valid metric type and value required.'; $msgType = 'error'; }
            else {
                $db->prepare("INSERT INTO media_metrics (campaign_id, metric_type, metric_value, notes) VALUES (?,?,?,?)")
                   ->execute([$campId, $mType, $mValue, $notes]);
                $msg = "Metrics recorded: " . strtoupper($mType) . " = " . number_format($mValue, 2) . "."; $msgType = 'success';
            }
        } elseif ($action === 'suspend_journalist' && $isFlag) {
            $pressId = (int)($_POST['press_id'] ?? 0);
            $reason  = trim($_POST['suspend_reason'] ?? '');
            $db->prepare("UPDATE media_press_corps SET status = 'suspended', suspended_reason = ? WHERE id = ?")->execute([$reason, $pressId]);
            $msg = "Press credential SUSPENDED."; $msgType = 'success';
        } else {
            $msg = 'Unauthorized or unknown action.'; $msgType = 'error';
        }
    }
    $_SESSION['csrf_pr'] = bin2hex(random_bytes(32));
    $csrf = $_SESSION['csrf_pr'];
}

// ── Data Fetch ──
$tab = $_GET['tab'] ?? 'publications';
$publications = $db->query("SELECT mp.*, CONCAT(c.firstname,' ',c.lastname) AS author FROM media_publications mp LEFT JOIN tblclients c ON c.id = mp.author_id ORDER BY mp.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$campaigns    = $db->query("SELECT mc.*, CONCAT(c.firstname,' ',c.lastname) AS launcher FROM media_campaigns mc LEFT JOIN tblclients c ON c.id = mc.launched_by ORDER BY mc.id DESC")->fetchAll(PDO::FETCH_ASSOC);
$pressCorps   = $db->query("SELECT mpc.*, CONCAT(c.firstname,' ',c.lastname) AS journalist_name FROM media_press_corps mpc LEFT JOIN tblclients c ON c.id = mpc.client_id ORDER BY mpc.accredited_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$metrics      = $db->query("SELECT mm.*, mc.campaign_code, mc.campaign_name FROM media_metrics mm JOIN media_campaigns mc ON mc.id = mm.campaign_id ORDER BY mm.measured_at DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
$reviews      = $db->query("SELECT mr.*, mp.pub_code, mp.title, CONCAT(c.firstname,' ',c.lastname) AS reviewer FROM media_reviews mr JOIN media_publications mp ON mp.id = mr.publication_id LEFT JOIN tblclients c ON c.id = mr.reviewer_id ORDER BY mr.reviewed_at DESC LIMIT 30")->fetchAll(PDO::FETCH_ASSOC);
$publishedCt  = count(array_filter($publications, fn($p) => $p['status'] === 'published'));
$activeCamps  = count(array_filter($campaigns, fn($c) => $c['status'] === 'active'));

$pageTitle = 'Ministry of Propaganda & State Media';
include __DIR__ . '/includes/site-header.inc.php';
?>
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<style>
.pr-wrap{max-width:1100px;margin:0 auto;padding:1.5rem}
.pr-bg{background:#0a0a14;min-height:100vh;color:#e2e8f0}
.pr-card{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:10px;padding:1.25rem;margin-bottom:1rem}
.pr-card:hover{border-color:#ef4444;box-shadow:0 0 12px rgba(239,68,68,.12)}
.pr-title{font-size:1.6rem;font-weight:700;color:#f1f5f9;margin-bottom:.25rem;display:flex;align-items:center;gap:.5rem}
.pr-sub{color:#94a3b8;font-size:.85rem}
.pr-badge{display:inline-block;padding:2px 10px;border-radius:999px;font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
.pr-tabs{display:flex;gap:.5rem;margin-bottom:1.25rem;flex-wrap:wrap}
.pr-tab{padding:.4rem 1rem;border-radius:6px;cursor:pointer;font-size:.8rem;background:#2a2a4a;color:#94a3b8;text-decoration:none;font-weight:600;border:none}
.pr-tab.active{background:#ef4444;color:#fff}
.pr-stat-bar{display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem}
.pr-stat{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:8px;padding:.75rem 1.25rem;flex:1;min-width:110px;text-align:center}
.pr-stat .val{font-size:1.5rem;font-weight:700;color:#ef4444}
.pr-stat .lbl{font-size:.7rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px}
.pr-btn{background:#ef4444;color:#fff;border:none;padding:.5rem 1.25rem;border-radius:6px;cursor:pointer;font-size:.85rem;font-weight:600;transition:.2s}
.pr-btn:hover{background:#dc2626}
.pr-btn-sm{padding:.3rem .75rem;font-size:.75rem}
.pr-btn-outline{background:transparent;border:1px solid #ef4444;color:#ef4444}
.pr-btn-outline:hover{background:#ef4444;color:#fff}
.pr-btn-green{background:#22c55e;color:#fff}.pr-btn-green:hover{background:#16a34a}
.pr-btn-blue{background:#3b82f6;color:#fff}.pr-btn-blue:hover{background:#2563eb}
.pr-btn-gold{background:#d4a017;color:#fff}.pr-btn-gold:hover{background:#b8860b}
.pr-input,.pr-select,.pr-textarea{width:100%;background:#0a0a14;border:1px solid #2a2a4a;color:#e2e8f0;padding:.5rem .75rem;border-radius:6px;font-size:.85rem;box-sizing:border-box}
.pr-textarea{min-height:100px;resize:vertical}
.pr-label{display:block;color:#94a3b8;font-size:.75rem;margin-bottom:.25rem;text-transform:uppercase;letter-spacing:.5px}
.pr-msg{padding:.75rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:.85rem}
.pr-msg-success{background:rgba(239,68,68,.12);border:1px solid #ef4444;color:#fca5a5}
.pr-msg-error{background:rgba(239,68,68,.12);border:1px solid #ef4444;color:#fca5a5}
.pr-modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:900;align-items:center;justify-content:center}
.pr-modal-bg.open{display:flex}
.pr-modal{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:12px;padding:1.5rem;width:90%;max-width:620px;max-height:80vh;overflow-y:auto}
.pr-modal h3{color:#f1f5f9;margin:0 0 1rem}
.pr-form-row{margin-bottom:.75rem}
.pr-workflow{display:flex;gap:4px;flex-wrap:wrap;margin:.5rem 0}
.pr-workflow span{padding:2px 8px;border-radius:4px;font-size:.65rem;font-weight:600;text-transform:uppercase}
</style>
<div class="pr-bg">
<div class="pr-wrap">
    <div class="pr-title"><i class="fas fa-bullhorn"></i> Ministry of Propaganda & State Media</div>
    <p class="pr-sub" style="margin-bottom:1.25rem">Content creation, review board, campaign operations, press corps management — NCO+ submit, Officers manage, Generals review</p>

    <?php if ($msg): ?><div class="pr-msg pr-msg-<?= $msgType ?>"><?= $msg ?></div><?php endif; ?>

    <div class="pr-stat-bar">
        <div class="pr-stat"><div class="val"><?= count($publications) ?></div><div class="lbl">Publications</div></div>
        <div class="pr-stat"><div class="val" style="color:#22c55e"><?= $publishedCt ?></div><div class="lbl">Published</div></div>
        <div class="pr-stat"><div class="val" style="color:#3b82f6"><?= $activeCamps ?></div><div class="lbl">Active Campaigns</div></div>
        <div class="pr-stat"><div class="val" style="color:#d4a017"><?= count($pressCorps) ?></div><div class="lbl">Press Corps</div></div>
        <div class="pr-stat"><div class="val" style="color:#f59e0b"><?= count($reviews) ?></div><div class="lbl">Reviews</div></div>
    </div>

    <div class="pr-tabs">
        <a href="?tab=publications" class="pr-tab <?= $tab==='publications'?'active':'' ?>"><i class="fas fa-newspaper"></i> Publications</a>
        <a href="?tab=campaigns" class="pr-tab <?= $tab==='campaigns'?'active':'' ?>"><i class="fas fa-bullhorn"></i> Campaigns</a>
        <a href="?tab=press" class="pr-tab <?= $tab==='press'?'active':'' ?>"><i class="fas fa-id-badge"></i> Press Corps</a>
        <a href="?tab=reviews" class="pr-tab <?= $tab==='reviews'?'active':'' ?>"><i class="fas fa-gavel"></i> Review Board</a>
        <a href="?tab=metrics" class="pr-tab <?= $tab==='metrics'?'active':'' ?>"><i class="fas fa-chart-bar"></i> Metrics</a>
    </div>

    <!-- ═══ TAB: PUBLICATIONS ═══ -->
    <?php if ($tab === 'publications'): ?>
        <?php if ($isNCO): ?>
            <div style="margin-bottom:1rem"><button class="pr-btn" onclick="document.getElementById('modalSubmit').classList.add('open')"><i class="fas fa-plus"></i> Submit Content</button></div>
        <?php endif; ?>
        <?php
        $stColors = ['draft'=>'#64748b','submitted'=>'#f59e0b','under_review'=>'#3b82f6','approved'=>'#22c55e','published'=>'#8b5cf6','retracted'=>'#ef4444'];
        $typeIcons = ['article'=>'fa-newspaper','report'=>'fa-file-alt','briefing'=>'fa-microphone','broadcast'=>'fa-tv','editorial'=>'fa-pen-fancy','intelligence'=>'fa-user-secret','decree'=>'fa-stamp'];
        $clColors = ['public'=>'#22c55e','internal'=>'#3b82f6','restricted'=>'#f59e0b','classified'=>'#ef4444'];
        $workflow = ['draft','submitted','under_review','approved','published'];
        foreach ($publications as $p): ?>
            <div class="pr-card">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div>
                        <i class="fas <?= $typeIcons[$p['content_type']] ?? 'fa-file' ?>" style="color:#ef4444"></i>
                        <strong style="color:#f1f5f9;margin-left:.25rem"><?= htmlspecialchars($p['title']) ?></strong>
                        <span class="pr-badge" style="background:<?= $clColors[$p['classification']] ?>20;color:<?= $clColors[$p['classification']] ?>;margin-left:.25rem"><?= strtoupper($p['classification']) ?></span>
                    </div>
                    <span class="pr-badge" style="background:<?= $stColors[$p['status']] ?>20;color:<?= $stColors[$p['status']] ?>;border:1px solid <?= $stColors[$p['status']] ?>40"><?= strtoupper(str_replace('_', ' ', $p['status'])) ?></span>
                </div>
                <div class="pr-workflow">
                    <?php foreach ($workflow as $step):
                        $isCur = ($p['status'] === $step);
                        $isPast = ($p['status'] === 'retracted') ? false : (array_search($p['status'], $workflow) > array_search($step, $workflow));
                    ?>
                        <span style="background:<?= $isCur ? '#ef4444' : ($isPast ? '#22c55e30' : '#2a2a4a') ?>;color:<?= $isCur ? '#fff' : ($isPast ? '#22c55e' : '#64748b') ?>"><?= strtoupper(str_replace('_', ' ', $step)) ?></span>
                    <?php endforeach; ?>
                </div>
                <p style="color:#94a3b8;font-size:.85rem;margin:.25rem 0"><?= htmlspecialchars(mb_substr($p['body'], 0, 200)) ?><?= mb_strlen($p['body']) > 200 ? '...' : '' ?></p>
                <div style="color:#64748b;font-size:.75rem">
                    <strong><?= htmlspecialchars($p['pub_code']) ?></strong> &bull;
                    <?= ucfirst($p['content_type']) ?> &bull;
                    By: <?= htmlspecialchars($p['author'] ?? 'Unknown') ?> &bull;
                    Platform: <?= htmlspecialchars($p['platform'] ?? 'Pulse') ?> &bull;
                    <?= $p['views'] ?> views
                    <?php if ($p['retraction_reason']): ?><br><span style="color:#ef4444">⚠ RETRACTED: <?= htmlspecialchars($p['retraction_reason']) ?></span><?php endif; ?>
                </div>
                <div style="margin-top:.5rem;display:flex;gap:.5rem;flex-wrap:wrap">
                    <?php if (in_array($p['status'], ['submitted','under_review']) && $isFlag): ?>
                        <button class="pr-btn-sm pr-btn pr-btn-green" onclick="openReview(<?= $p['id'] ?>,'<?= htmlspecialchars($p['pub_code'], ENT_QUOTES) ?>')"><i class="fas fa-gavel"></i> Review</button>
                    <?php endif; ?>
                    <?php if ($p['status'] === 'approved' && $isOfficer): ?>
                        <form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="publish"><input type="hidden" name="pub_id" value="<?= $p['id'] ?>"><button class="pr-btn-sm pr-btn" style="background:#8b5cf6"><i class="fas fa-paper-plane"></i> Publish</button></form>
                    <?php endif; ?>
                    <?php if ($p['status'] === 'published' && $isFlag): ?>
                        <button class="pr-btn-sm pr-btn" onclick="openRetract(<?= $p['id'] ?>,'<?= htmlspecialchars($p['pub_code'], ENT_QUOTES) ?>')"><i class="fas fa-ban"></i> Retract</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($publications)): ?><div class="pr-card" style="text-align:center;color:#64748b"><p>No publications submitted.</p></div><?php endif; ?>

    <!-- ═══ TAB: CAMPAIGNS ═══ -->
    <?php elseif ($tab === 'campaigns'): ?>
        <?php if ($isOfficer): ?>
            <div style="margin-bottom:1rem"><button class="pr-btn pr-btn-gold" onclick="document.getElementById('modalCampaign').classList.add('open')"><i class="fas fa-bullhorn"></i> Create Campaign</button></div>
        <?php endif; ?>
        <?php
        $csColors = ['planning'=>'#f59e0b','active'=>'#22c55e','paused'=>'#3b82f6','concluded'=>'#64748b','cancelled'=>'#ef4444'];
        $ctIcons  = ['narrative'=>'fa-book','counter_propaganda'=>'fa-shield-alt','morale'=>'fa-heart','recruitment'=>'fa-user-plus','deterrence'=>'fa-exclamation-triangle','awareness'=>'fa-eye'];
        foreach ($campaigns as $c): ?>
            <div class="pr-card" style="border-left:3px solid <?= $csColors[$c['status']] ?>">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div>
                        <i class="fas <?= $ctIcons[$c['campaign_type']] ?? 'fa-bullhorn' ?>" style="color:#ef4444"></i>
                        <strong style="color:#ef4444;margin-left:.25rem"><?= htmlspecialchars($c['campaign_code']) ?></strong>
                        <strong style="color:#f1f5f9;margin-left:.5rem"><?= htmlspecialchars($c['campaign_name']) ?></strong>
                    </div>
                    <span class="pr-badge" style="background:<?= $csColors[$c['status']] ?>20;color:<?= $csColors[$c['status']] ?>;border:1px solid <?= $csColors[$c['status']] ?>40"><?= strtoupper($c['status']) ?></span>
                </div>
                <p style="color:#cbd5e1;font-size:.85rem;margin:.5rem 0"><?= htmlspecialchars($c['objective']) ?></p>
                <div style="color:#64748b;font-size:.75rem">
                    Type: <span class="pr-badge" style="background:#ef444420;color:#ef4444"><?= strtoupper(str_replace('_', ' ', $c['campaign_type'])) ?></span>
                    &bull; Audience: <?= htmlspecialchars($c['target_audience']) ?>
                    &bull; Platforms: <?= htmlspecialchars($c['platforms'] ?: 'TBD') ?>
                    &bull; By: <?= htmlspecialchars($c['launcher'] ?? 'Pending') ?>
                </div>
                <div style="margin-top:.5rem;display:flex;gap:.5rem;flex-wrap:wrap">
                    <?php if ($c['status'] === 'planning' && $isOfficer): ?>
                        <form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="launch_campaign"><input type="hidden" name="camp_id" value="<?= $c['id'] ?>"><button class="pr-btn-sm pr-btn pr-btn-green"><i class="fas fa-rocket"></i> Launch</button></form>
                    <?php endif; ?>
                    <?php if ($c['status'] === 'active'): ?>
                        <button class="pr-btn-sm pr-btn pr-btn-blue" onclick="openMetric(<?= $c['id'] ?>,'<?= htmlspecialchars($c['campaign_code'], ENT_QUOTES) ?>')"><i class="fas fa-chart-bar"></i> Track</button>
                        <?php if ($isFlag): ?>
                            <form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="conclude_campaign"><input type="hidden" name="camp_id" value="<?= $c['id'] ?>"><button class="pr-btn-sm pr-btn" style="background:#64748b"><i class="fas fa-flag-checkered"></i> Conclude</button></form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($campaigns)): ?><div class="pr-card" style="text-align:center;color:#64748b"><p>No campaigns created.</p></div><?php endif; ?>

    <!-- ═══ TAB: PRESS CORPS ═══ -->
    <?php elseif ($tab === 'press'): ?>
        <?php if ($isOfficer): ?>
            <div style="margin-bottom:1rem"><button class="pr-btn pr-btn-blue" onclick="document.getElementById('modalAccredit').classList.add('open')"><i class="fas fa-id-badge"></i> Accredit Journalist</button></div>
        <?php endif; ?>
        <?php
        $accColors = ['correspondent'=>'#94a3b8','reporter'=>'#3b82f6','editor'=>'#f59e0b','bureau_chief'=>'#ef4444','anchor'=>'#d4a017'];
        $stPColors = ['active'=>'#22c55e','suspended'=>'#ef4444','revoked'=>'#64748b'];
        foreach ($pressCorps as $pc): ?>
            <div class="pr-card" style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
                <i class="fas fa-id-badge" style="font-size:1.3rem;color:<?= $accColors[$pc['accreditation_level']] ?>"></i>
                <div style="flex:1">
                    <strong style="color:#f1f5f9"><?= htmlspecialchars($pc['journalist_name'] ?? 'Unknown') ?></strong>
                    <span class="pr-badge" style="background:<?= $accColors[$pc['accreditation_level']] ?>20;color:<?= $accColors[$pc['accreditation_level']] ?>;margin-left:.5rem"><?= strtoupper(str_replace('_', ' ', $pc['accreditation_level'])) ?></span>
                    <span class="pr-badge" style="background:<?= $stPColors[$pc['status']] ?>20;color:<?= $stPColors[$pc['status']] ?>;margin-left:.25rem"><?= strtoupper($pc['status']) ?></span>
                    <div style="color:#64748b;font-size:.75rem">
                        ID: <?= htmlspecialchars($pc['press_code']) ?> &bull; Beat: <?= htmlspecialchars($pc['beat']) ?> &bull; Published: <?= $pc['articles_published'] ?>
                        <?php if ($pc['suspended_reason']): ?>&bull; <span style="color:#ef4444"><?= htmlspecialchars($pc['suspended_reason']) ?></span><?php endif; ?>
                    </div>
                </div>
                <?php if ($pc['status'] === 'active' && $isFlag): ?>
                    <form method="POST" style="display:flex;gap:.5rem;align-items:center"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="suspend_journalist"><input type="hidden" name="press_id" value="<?= $pc['id'] ?>"><input type="text" name="suspend_reason" class="pr-input" placeholder="Reason..." style="width:150px"><button class="pr-btn-sm pr-btn"><i class="fas fa-ban"></i> Suspend</button></form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if (empty($pressCorps)): ?><div class="pr-card" style="text-align:center;color:#64748b"><p>No press corps accredited.</p></div><?php endif; ?>

    <!-- ═══ TAB: REVIEW BOARD ═══ -->
    <?php elseif ($tab === 'reviews'): ?>
        <?php
        $vColors = ['approve'=>'#22c55e','reject'=>'#ef4444','revise'=>'#f59e0b'];
        foreach ($reviews as $r): ?>
            <div class="pr-card" style="display:flex;align-items:start;gap:1rem;flex-wrap:wrap;border-left:3px solid <?= $vColors[$r['verdict']] ?>">
                <i class="fas fa-gavel" style="color:<?= $vColors[$r['verdict']] ?>;margin-top:.25rem"></i>
                <div style="flex:1">
                    <strong style="color:#f1f5f9"><?= htmlspecialchars($r['title']) ?></strong>
                    <span style="color:#64748b;font-size:.8rem;margin-left:.5rem"><?= htmlspecialchars($r['pub_code']) ?></span>
                    <span class="pr-badge" style="background:<?= $vColors[$r['verdict']] ?>20;color:<?= $vColors[$r['verdict']] ?>;margin-left:.5rem"><?= strtoupper($r['verdict']) ?></span>
                    <div style="color:#94a3b8;font-size:.85rem;margin-top:.25rem"><?= htmlspecialchars($r['comments'] ?: 'No comments.') ?></div>
                    <div style="color:#64748b;font-size:.75rem;margin-top:.25rem">By: <?= htmlspecialchars($r['reviewer'] ?? 'Unknown') ?> &bull; <?= date('M j, Y H:i', strtotime($r['reviewed_at'])) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($reviews)): ?><div class="pr-card" style="text-align:center;color:#64748b"><p>No reviews submitted.</p></div><?php endif; ?>

    <!-- ═══ TAB: METRICS ═══ -->
    <?php elseif ($tab === 'metrics'): ?>
        <?php
        $mtColors = ['reach'=>'#3b82f6','engagement'=>'#22c55e','sentiment'=>'#f59e0b','penetration'=>'#ef4444'];
        $mtIcons  = ['reach'=>'fa-globe','engagement'=>'fa-hand-pointer','sentiment'=>'fa-smile','penetration'=>'fa-bullseye'];
        foreach ($metrics as $m): ?>
            <div class="pr-card" style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
                <div style="background:<?= $mtColors[$m['metric_type']] ?>20;color:<?= $mtColors[$m['metric_type']] ?>;padding:.5rem .75rem;border-radius:8px;font-size:1.2rem;font-weight:700"><?= number_format($m['metric_value'], 1) ?></div>
                <div style="flex:1">
                    <i class="fas <?= $mtIcons[$m['metric_type']] ?>" style="color:<?= $mtColors[$m['metric_type']] ?>"></i>
                    <strong style="color:#f1f5f9;margin-left:.25rem"><?= strtoupper($m['metric_type']) ?></strong>
                    <span style="color:#94a3b8;margin-left:.5rem"><?= htmlspecialchars($m['campaign_code'] . ' — ' . $m['campaign_name']) ?></span>
                    <div style="color:#64748b;font-size:.75rem"><?= date('M j, Y H:i', strtotime($m['measured_at'])) ?><?= $m['notes'] ? ' — ' . htmlspecialchars($m['notes']) : '' ?></div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($metrics)): ?><div class="pr-card" style="text-align:center;color:#64748b"><p>No metrics recorded yet.</p></div><?php endif; ?>
    <?php endif; ?>
</div>
</div>

<!-- Modals -->
<div class="pr-modal-bg" id="modalSubmit"><div class="pr-modal"><h3><i class="fas fa-newspaper"></i> Submit Content</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="submit_publication">
<div class="pr-form-row"><label class="pr-label">Title</label><input type="text" name="pub_title" class="pr-input" required></div>
<div style="display:flex;gap:.75rem"><div class="pr-form-row" style="flex:1"><label class="pr-label">Type</label><select name="content_type" class="pr-select"><option value="article">Article</option><option value="report">Report</option><option value="briefing">Briefing</option><option value="broadcast">Broadcast</option><option value="editorial">Editorial</option><option value="intelligence">Intelligence</option><?php if ($isCommander): ?><option value="decree">Decree ⚠️</option><?php endif; ?></select></div><div class="pr-form-row" style="flex:1"><label class="pr-label">Classification</label><select name="classification" class="pr-select"><option value="public">Public</option><option value="internal">Internal</option><option value="restricted">Restricted</option><option value="classified">Classified</option></select></div></div>
<div class="pr-form-row"><label class="pr-label">Platform</label><input type="text" name="platform" class="pr-input" value="Pulse" placeholder="Pulse, Veil, MIL-NET, MetaDome..."></div>
<div class="pr-form-row"><label class="pr-label">Content Body</label><textarea name="pub_body" class="pr-textarea" style="min-height:160px" required></textarea></div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="pr-btn pr-btn-outline" onclick="this.closest('.pr-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="pr-btn"><i class="fas fa-paper-plane"></i> Submit</button></div></form></div></div>

<div class="pr-modal-bg" id="modalReview"><div class="pr-modal"><h3><i class="fas fa-gavel"></i> Review Publication</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="review_publication"><input type="hidden" name="pub_id" id="reviewPubId" value="">
<div style="color:#94a3b8;margin-bottom:1rem">Reviewing: <strong id="reviewPubCode" style="color:#ef4444"></strong></div>
<div class="pr-form-row"><label class="pr-label">Verdict</label><select name="verdict" class="pr-select"><option value="approve">✅ Approve</option><option value="revise">🔄 Request Revision</option><option value="reject">❌ Reject</option></select></div>
<div class="pr-form-row"><label class="pr-label">Comments</label><textarea name="review_comments" class="pr-textarea" style="min-height:80px"></textarea></div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="pr-btn pr-btn-outline" onclick="this.closest('.pr-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="pr-btn pr-btn-green"><i class="fas fa-gavel"></i> Submit Review</button></div></form></div></div>

<div class="pr-modal-bg" id="modalRetract"><div class="pr-modal"><h3><i class="fas fa-ban"></i> Retract Publication</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="retract"><input type="hidden" name="pub_id" id="retractPubId" value="">
<div style="color:#94a3b8;margin-bottom:1rem">Retracting: <strong id="retractPubCode" style="color:#ef4444"></strong></div>
<div class="pr-form-row"><label class="pr-label">Retraction Reason</label><textarea name="retract_reason" class="pr-textarea" required></textarea></div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="pr-btn pr-btn-outline" onclick="this.closest('.pr-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="pr-btn"><i class="fas fa-ban"></i> Retract</button></div></form></div></div>

<div class="pr-modal-bg" id="modalCampaign"><div class="pr-modal"><h3><i class="fas fa-bullhorn"></i> Create Campaign</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="create_campaign">
<div class="pr-form-row"><label class="pr-label">Campaign Name</label><input type="text" name="camp_name" class="pr-input" required></div>
<div class="pr-form-row"><label class="pr-label">Objective</label><textarea name="objective" class="pr-textarea" required></textarea></div>
<div style="display:flex;gap:.75rem"><div class="pr-form-row" style="flex:1"><label class="pr-label">Type</label><select name="camp_type" class="pr-select"><option value="narrative">Narrative</option><option value="counter_propaganda">Counter-Propaganda</option><option value="morale">Morale</option><option value="recruitment">Recruitment</option><option value="deterrence">Deterrence</option><option value="awareness">Awareness</option></select></div><div class="pr-form-row" style="flex:1"><label class="pr-label">Target Audience</label><input type="text" name="audience" class="pr-input" value="General Population"></div></div>
<div class="pr-form-row"><label class="pr-label">Platforms</label><input type="text" name="platforms" class="pr-input" placeholder="Pulse, Veil, MIL-NET, MetaDome..."></div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="pr-btn pr-btn-outline" onclick="this.closest('.pr-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="pr-btn pr-btn-gold"><i class="fas fa-bullhorn"></i> Launch</button></div></form></div></div>

<div class="pr-modal-bg" id="modalAccredit"><div class="pr-modal"><h3><i class="fas fa-id-badge"></i> Accredit Journalist</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="accredit_journalist">
<div class="pr-form-row"><label class="pr-label">Journalist Client ID</label><input type="number" name="journalist_id" class="pr-input" min="1" required></div>
<div style="display:flex;gap:.75rem"><div class="pr-form-row" style="flex:1"><label class="pr-label">Accreditation Level</label><select name="acc_level" class="pr-select"><option value="correspondent">Correspondent</option><option value="reporter">Reporter</option><option value="editor">Editor</option><option value="bureau_chief">Bureau Chief</option><option value="anchor">Anchor</option></select></div><div class="pr-form-row" style="flex:1"><label class="pr-label">Beat</label><input type="text" name="beat" class="pr-input" value="General"></div></div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="pr-btn pr-btn-outline" onclick="this.closest('.pr-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="pr-btn pr-btn-blue"><i class="fas fa-id-badge"></i> Accredit</button></div></form></div></div>

<div class="pr-modal-bg" id="modalMetric"><div class="pr-modal"><h3><i class="fas fa-chart-bar"></i> Track Campaign Metrics</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="track_metrics"><input type="hidden" name="camp_id" id="metricCampId" value="">
<div style="color:#94a3b8;margin-bottom:1rem">Campaign: <strong id="metricCampCode" style="color:#ef4444"></strong></div>
<div style="display:flex;gap:.75rem"><div class="pr-form-row" style="flex:1"><label class="pr-label">Metric Type</label><select name="metric_type" class="pr-select"><option value="reach">📡 Reach</option><option value="engagement">👆 Engagement</option><option value="sentiment">😊 Sentiment</option><option value="penetration">🎯 Penetration</option></select></div><div class="pr-form-row" style="flex:1"><label class="pr-label">Value</label><input type="number" name="metric_value" class="pr-input" min="0" step="0.01" required></div></div>
<div class="pr-form-row"><label class="pr-label">Notes</label><textarea name="metric_notes" class="pr-textarea" style="min-height:60px"></textarea></div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="pr-btn pr-btn-outline" onclick="this.closest('.pr-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="pr-btn pr-btn-blue"><i class="fas fa-chart-bar"></i> Record</button></div></form></div></div>

<script>
function openReview(id,code){document.getElementById('reviewPubId').value=id;document.getElementById('reviewPubCode').textContent=code;document.getElementById('modalReview').classList.add('open')}
function openRetract(id,code){document.getElementById('retractPubId').value=id;document.getElementById('retractPubCode').textContent=code;document.getElementById('modalRetract').classList.add('open')}
function openMetric(id,code){document.getElementById('metricCampId').value=id;document.getElementById('metricCampCode').textContent=code;document.getElementById('modalMetric').classList.add('open')}
document.querySelectorAll('.pr-modal-bg').forEach(bg=>{bg.addEventListener('click',e=>{if(e.target===bg)bg.classList.remove('open')})});
</script>
<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
