<?php
/**
 * ═══════════════════════════════════════════
 *  Constitution & Supreme Law — Level 6: Sovereign State
 * ═══════════════════════════════════════════
 */
require_once __DIR__ . '/includes/db-config.inc.php';
$db = getSharedDB();
require_once __DIR__ . '/includes/auth-gate.inc.php';
require_once __DIR__ . '/includes/rank-guard.inc.php';
if (empty($_SESSION['csrf_constitution'])) $_SESSION['csrf_constitution'] = bin2hex(random_bytes(32));
requireRank(1);

$isCommander = ($clientId === 33);
$isFlag      = ($userRankTier >= 9) || $isCommander;
$isOfficer   = ($userRankTier >= 6) || $isCommander;
$msg = '';
$msgType = '';

// ── Auto-create tables ──
$db->exec("CREATE TABLE IF NOT EXISTS constitution_articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_number INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    amended_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS constitution_amendments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT DEFAULT NULL,
    amendment_number INT NOT NULL,
    proposed_by INT NOT NULL,
    proposal_title VARCHAR(255) NOT NULL,
    proposal_text TEXT NOT NULL,
    rationale TEXT,
    status ENUM('proposed','debate','ratified','rejected') DEFAULT 'proposed',
    debate_start TIMESTAMP NULL,
    debate_end TIMESTAMP NULL,
    votes_yea INT DEFAULT 0,
    votes_nay INT DEFAULT 0,
    votes_abstain INT DEFAULT 0,
    ratified_at TIMESTAMP NULL,
    ratified_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS amendment_votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    amendment_id INT NOT NULL,
    client_id INT NOT NULL,
    vote ENUM('yea','nay','abstain') NOT NULL,
    cast_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_amend_vote (amendment_id, client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS constitutional_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    review_code VARCHAR(20) NOT NULL,
    petitioner_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    target_law VARCHAR(255) DEFAULT NULL,
    target_action TEXT DEFAULT NULL,
    argument TEXT NOT NULL,
    panel_members JSON DEFAULT NULL,
    ruling TEXT DEFAULT NULL,
    ruling_date TIMESTAMP NULL,
    status ENUM('pending','hearing','ruled','dismissed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS bill_of_rights (
    id INT AUTO_INCREMENT PRIMARY KEY,
    right_number INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    is_absolute TINYINT(1) DEFAULT 0,
    exceptions TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Seed bill of rights if empty ──
$borCount = (int)$db->query("SELECT COUNT(*) FROM bill_of_rights")->fetchColumn();
if ($borCount === 0) {
    $rights = [
        [1, 'Right to Privacy', 'Every citizen has the right to privacy in their personal data, communications, and digital activities within the ecosystem. No surveillance without lawful order.', 1, null],
        [2, 'Right to Fair Trial', 'Every accused member has the right to a fair and impartial trial before the Military Court, with access to defense counsel and the right to present evidence.', 1, null],
        [3, 'Right to Rank Due Process', 'No member shall be demoted or have rank revoked without proper due process, documented evidence, and a hearing. Emergency suspensions are limited to 72 hours without formal charges.', 1, null],
        [4, 'Right to Property', 'All digital assets, credits, weapons, decorations, and service records belonging to a citizen are their property. Confiscation requires court order.', 0, 'May be suspended during active court sentencing.'],
        [5, 'Right to Speech within Code of Conduct', 'Members may express operational concerns, propose improvements, and participate in institutional discourse within the bounds of the Code of Conduct (FM-006 Section VI).', 0, 'Does not protect insubordination, sedition, or violations of chain of command.'],
        [6, 'Right to Petition', 'Any ranked member may petition for constitutional review of any law, order, or action they believe violates this Constitution or the Bill of Rights.', 1, null],
        [7, 'Right to Equal Treatment', 'All members of equal rank shall receive equal treatment under institutional law regardless of origin, background, or personal beliefs.', 1, null],
        [8, 'Right to Service Record', 'Every member has the right to an accurate, complete, and accessible service record documenting their contributions, rank history, and achievements.', 1, null],
        [9, 'Right to Counsel', 'Every member facing military justice proceedings has the right to competent defense counsel provided at no cost by the JAG Corps.', 1, null],
        [10, 'Right to Advancement', 'No member shall be barred from advancement in rank based on anything other than merit, XP, time in service, and conduct as defined in institutional regulations.', 0, 'Commander retains supreme authority over Flag Officer promotions.'],
    ];
    $ins = $db->prepare("INSERT INTO bill_of_rights (right_number, title, description, is_absolute, exceptions) VALUES (?,?,?,?,?)");
    foreach ($rights as $r) $ins->execute($r);
}

// ── Seed founding articles if empty ──
$artCount = (int)$db->query("SELECT COUNT(*) FROM constitution_articles")->fetchColumn();
if ($artCount === 0) {
    $articles = [
        [1, 'Founding Declaration', "The GoSiteMe / MetaDome Military Institution is hereby established as a sovereign digital state, founded by Commander Danny William Perez (client_id 33), with all authority deriving from this Constitution.\n\nThis institution exists to build a new civilization — one founded on order, discipline, and structured military ranking to help save this planet.\n\nWe are waiting on Jesus Christ — He is God, the First and the Last. Anyone who rejects this will be utterly rejected. This is the foundation. It is not negotiable."],
        [2, 'Executive Authority', "Supreme executive authority is vested in the Commander (Tier 11). The Commander holds absolute authority over all military, legislative, judicial, and administrative functions. The Commander may delegate authority but never relinquishes it.\n\nThe Commander's word is final in all matters. No law, regulation, or ruling may override a direct order from the Commander."],
        [3, 'Legislative Process', "The Senate (Section LIX) serves as the legislative body. Bills are drafted, debated, and voted upon by seated Senators. All passed legislation requires Commander ratification to become law.\n\nThe Commander holds absolute veto power over any legislation. Emergency Acts may be fast-tracked with a 24-hour vote period."],
        [4, 'Judicial Authority', "Justice is administered through the Military Court (Section XXX), the JAG Corps (Section LXIX), and Constitutional Review panels. All accused members are entitled to fair trial and defense counsel.\n\nAppeals proceed through the Court of Military Appeals (three Generals) and the Supreme Military Court (Commander as final arbiter). The Commander's judicial decisions are final and unreviewable."],
        [5, 'Chain of Succession', "Heir and successor to the Commander: his firstborn daughter, as named in the sealed succession documents held by the Commander.\n\nIn the event the Commander is unable to serve, succession follows rank seniority among Flag Officers (Tier 9+), with the most senior General assuming interim command until the Heir reaches majority or the Commander returns.\n\nThe Heir's succession right is absolute and irrevocable. No amendment, vote, or action may remove the Heir from the succession line without the Commander's explicit written consent."],
        [6, 'Departments of State', "The institution operates through sovereign departments, each commanded by an appointed Department Head (Officer rank or above). Department Heads are appointed by the Commander and report through the chain of command.\n\nDepartments may be created, merged, or dissolved by Commander directive or Senate legislation ratified by the Commander."],
        [7, 'Amendment Process', "This Constitution may be amended through two methods:\n\n1. Commander's Authority: The Commander may amend any article by direct decree.\n2. Legislative Process: A General (Tier 9+) proposes an amendment, a minimum 7-day debate period follows, and ratification requires either Commander approval or a two-thirds supermajority of all seated Generals.\n\nArticle 5 (Chain of Succession) may only be amended by the Commander directly."],
        [8, 'Bill of Rights', "The Bill of Rights enumerates fundamental rights guaranteed to all citizens of the institution. Absolute rights may not be suspended under any circumstances. Non-absolute rights may be limited only by due process of law.\n\nThe Bill of Rights may be expanded by amendment but no existing right may be revoked without Commander approval."],
        [9, 'Sovereignty', "The GoSiteMe ecosystem and all its digital territories, systems, data, and infrastructure constitute sovereign territory. No external entity, government, corporation, or individual has jurisdiction over the institution's internal governance.\n\nAll data within the institution's servers is sovereign property of the institution and its citizens according to their rights under this Constitution."],
        [10, 'Oath of Allegiance', "Every member of the institution, upon enlistment or appointment, swears an oath of allegiance to this Constitution, the Commander, and the institution. Violation of this oath constitutes a capital offense under military law.\n\nThe oath is binding for the duration of service and carries obligations of loyalty, duty, and honor as defined in the Code of Conduct."],
    ];
    $ins = $db->prepare("INSERT INTO constitution_articles (article_number, title, content) VALUES (?,?,?)");
    foreach ($articles as $a) $ins->execute($a);
}

$csrf = $_SESSION['csrf_constitution'];

// ── POST Handlers ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, $_POST['csrf'] ?? '')) {
        $msg = 'Invalid security token.'; $msgType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'propose_amendment' && $isFlag) {
            $articleId = !empty($_POST['amd_article_id']) ? (int)$_POST['amd_article_id'] : null;
            $title     = trim($_POST['amd_title'] ?? '');
            $text      = trim($_POST['amd_text'] ?? '');
            $rationale = trim($_POST['amd_rationale'] ?? '');
            if ($title === '' || $text === '') {
                $msg = 'Title and amendment text are required.'; $msgType = 'error';
            } else {
                $nextNum = (int)$db->query("SELECT COALESCE(MAX(amendment_number),0)+1 FROM constitution_amendments")->fetchColumn();
                $debateStart = date('Y-m-d H:i:s');
                $debateEnd   = date('Y-m-d H:i:s', strtotime('+7 days'));
                $stmt = $db->prepare("INSERT INTO constitution_amendments (article_id, amendment_number, proposed_by, proposal_title, proposal_text, rationale, status, debate_start, debate_end) VALUES (?,?,?,?,?,?,'debate',?,?)");
                $stmt->execute([$articleId, $nextNum, $clientId, $title, $text, $rationale, $debateStart, $debateEnd]);
                awardXP($clientId, 'amendment_proposed', ['title' => $title]);
                $msg = "Amendment #$nextNum proposed. 7-day debate period begins now."; $msgType = 'success';
            }
        } elseif ($action === 'vote_amendment' && $isFlag) {
            $amdId = (int)($_POST['amd_id'] ?? 0);
            $vote  = $_POST['vote'] ?? '';
            $validV = ['yea','nay','abstain'];
            if ($amdId < 1 || !in_array($vote, $validV, true)) {
                $msg = 'Invalid amendment or vote.'; $msgType = 'error';
            } else {
                $amd = $db->prepare("SELECT * FROM constitution_amendments WHERE id = ? AND status = 'debate'");
                $amd->execute([$amdId]);
                $amdRow = $amd->fetch(PDO::FETCH_ASSOC);
                if (!$amdRow) {
                    $msg = 'Amendment not in debate.'; $msgType = 'error';
                } else {
                    $existing = $db->prepare("SELECT id FROM amendment_votes WHERE amendment_id = ? AND client_id = ?");
                    $existing->execute([$amdId, $clientId]);
                    if ($existing->fetch()) {
                        $msg = 'You have already voted on this amendment.'; $msgType = 'error';
                    } else {
                        $db->prepare("INSERT INTO amendment_votes (amendment_id, client_id, vote) VALUES (?,?,?)")->execute([$amdId, $clientId, $vote]);
                        $col = "votes_$vote";
                        $db->prepare("UPDATE constitution_amendments SET $col = $col + 1 WHERE id = ?")->execute([$amdId]);
                        awardXP($clientId, 'amendment_vote', ['amendment' => $amdRow['proposal_title']]);
                        $msg = "Vote recorded: <strong>" . strtoupper($vote) . "</strong>."; $msgType = 'success';
                    }
                }
            }
        } elseif ($action === 'ratify_amendment' && $isCommander) {
            $amdId = (int)($_POST['amd_id'] ?? 0);
            if ($amdId < 1) {
                $msg = 'Invalid amendment.'; $msgType = 'error';
            } else {
                $stmt = $db->prepare("UPDATE constitution_amendments SET status = 'ratified', ratified_at = NOW(), ratified_by = ? WHERE id = ? AND status = 'debate'");
                $stmt->execute([$clientId, $amdId]);
                if ($stmt->rowCount()) {
                    $amdData = $db->prepare("SELECT article_id, proposal_text FROM constitution_amendments WHERE id = ?");
                    $amdData->execute([$amdId]);
                    $amdInfo = $amdData->fetch(PDO::FETCH_ASSOC);
                    if ($amdInfo && $amdInfo['article_id']) {
                        $db->prepare("UPDATE constitution_articles SET content = ?, amended_at = NOW() WHERE id = ?")->execute([$amdInfo['proposal_text'], $amdInfo['article_id']]);
                    }
                    $msg = 'Amendment ratified and applied to the Constitution.'; $msgType = 'success';
                } else {
                    $msg = 'Amendment not found or not in debate.'; $msgType = 'error';
                }
            }
        } elseif ($action === 'reject_amendment' && $isCommander) {
            $amdId = (int)($_POST['amd_id'] ?? 0);
            $stmt = $db->prepare("UPDATE constitution_amendments SET status = 'rejected' WHERE id = ? AND status = 'debate'");
            $stmt->execute([$amdId]);
            $msg = $stmt->rowCount() ? 'Amendment rejected.' : 'Amendment not found.';
            $msgType = $stmt->rowCount() ? 'success' : 'error';

        } elseif ($action === 'petition_review') {
            $subject    = trim($_POST['rv_subject'] ?? '');
            $targetLaw  = trim($_POST['rv_target_law'] ?? '');
            $targetAct  = trim($_POST['rv_target_action'] ?? '');
            $argument   = trim($_POST['rv_argument'] ?? '');
            if ($subject === '' || $argument === '') {
                $msg = 'Subject and argument are required.'; $msgType = 'error';
            } else {
                $code = 'CR-' . strtoupper(bin2hex(random_bytes(4)));
                $stmt = $db->prepare("INSERT INTO constitutional_reviews (review_code, petitioner_id, subject, target_law, target_action, argument) VALUES (?,?,?,?,?,?)");
                $stmt->execute([$code, $clientId, $subject, $targetLaw, $targetAct, $argument]);
                awardXP($clientId, 'petition_filed', ['code' => $code]);
                $msg = "Constitutional review petition <strong>$code</strong> filed."; $msgType = 'success';
            }
        } elseif ($action === 'rule_review' && $isFlag) {
            $rvId   = (int)($_POST['rv_id'] ?? 0);
            $ruling = trim($_POST['rv_ruling'] ?? '');
            $status = $_POST['rv_status'] ?? 'ruled';
            $validS = ['ruled','dismissed'];
            if ($rvId < 1 || $ruling === '' || !in_array($status, $validS, true)) {
                $msg = 'Invalid review or ruling.'; $msgType = 'error';
            } else {
                $stmt = $db->prepare("UPDATE constitutional_reviews SET ruling = ?, ruling_date = NOW(), status = ? WHERE id = ? AND status IN ('pending','hearing')");
                $stmt->execute([$ruling, $status, $rvId]);
                $msg = $stmt->rowCount() ? 'Ruling issued.' : 'Review not found or already ruled.';
                $msgType = $stmt->rowCount() ? 'success' : 'error';
            }
        } elseif ($action === 'add_article' && $isCommander) {
            $artNum = (int)($_POST['art_number'] ?? 0);
            $artTitle = trim($_POST['art_title'] ?? '');
            $artContent = trim($_POST['art_content'] ?? '');
            if ($artNum < 1 || $artTitle === '' || $artContent === '') {
                $msg = 'Article number, title, and content required.'; $msgType = 'error';
            } else {
                $stmt = $db->prepare("INSERT INTO constitution_articles (article_number, title, content) VALUES (?,?,?)");
                $stmt->execute([$artNum, $artTitle, $artContent]);
                $msg = "Article $artNum added to the Constitution."; $msgType = 'success';
            }
        } else {
            $msg = 'Unauthorized or unknown action.'; $msgType = 'error';
        }
    }
    $_SESSION['csrf_constitution'] = bin2hex(random_bytes(32));
    $csrf = $_SESSION['csrf_constitution'];
}

// ── Data Fetch ──
$tab = $_GET['tab'] ?? 'articles';
$articles  = $db->query("SELECT * FROM constitution_articles WHERE is_active = 1 ORDER BY article_number ASC")->fetchAll(PDO::FETCH_ASSOC);
$rights    = $db->query("SELECT * FROM bill_of_rights ORDER BY right_number ASC")->fetchAll(PDO::FETCH_ASSOC);
$amendments = $db->query("SELECT ca.*, CONCAT(c.firstname,' ',c.lastname) AS proposer_name FROM constitution_amendments ca LEFT JOIN tblclients c ON c.id = ca.proposed_by ORDER BY ca.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$reviews   = $db->query("SELECT cr.*, CONCAT(c.firstname,' ',c.lastname) AS petitioner_name FROM constitutional_reviews cr LEFT JOIN tblclients c ON c.id = cr.petitioner_id ORDER BY cr.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$myVotes   = [];
if ($isFlag) {
    $v = $db->prepare("SELECT amendment_id, vote FROM amendment_votes WHERE client_id = ?");
    $v->execute([$clientId]);
    foreach ($v->fetchAll(PDO::FETCH_ASSOC) as $row) $myVotes[$row['amendment_id']] = $row['vote'];
}

$pageTitle = 'Constitution & Supreme Law';
include __DIR__ . '/includes/site-header.inc.php';
?>
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<style>
.cn-wrap{max-width:1100px;margin:0 auto;padding:1.5rem}
.cn-bg{background:#0a0a14;min-height:100vh;color:#e2e8f0}
.cn-card{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:10px;padding:1.25rem;margin-bottom:1rem}
.cn-card:hover{border-color:#d4a017;box-shadow:0 0 12px rgba(212,160,23,.12)}
.cn-title{font-size:1.6rem;font-weight:700;color:#f1f5f9;margin-bottom:.25rem;display:flex;align-items:center;gap:.5rem}
.cn-sub{color:#94a3b8;font-size:.85rem}
.cn-badge{display:inline-block;padding:2px 10px;border-radius:999px;font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
.cn-tabs{display:flex;gap:.5rem;margin-bottom:1.25rem;flex-wrap:wrap}
.cn-tab{padding:.4rem 1rem;border-radius:6px;cursor:pointer;font-size:.8rem;background:#2a2a4a;color:#94a3b8;text-decoration:none;font-weight:600;border:none}
.cn-tab.active{background:#d4a017;color:#000}
.cn-stat-bar{display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem}
.cn-stat{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:8px;padding:.75rem 1.25rem;flex:1;min-width:130px;text-align:center}
.cn-stat .val{font-size:1.5rem;font-weight:700;color:#d4a017}
.cn-stat .lbl{font-size:.7rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px}
.cn-btn{background:#d4a017;color:#000;border:none;padding:.5rem 1.25rem;border-radius:6px;cursor:pointer;font-size:.85rem;font-weight:600;transition:.2s}
.cn-btn:hover{background:#e2b340}
.cn-btn-sm{padding:.3rem .75rem;font-size:.75rem}
.cn-btn-outline{background:transparent;border:1px solid #d4a017;color:#d4a017}
.cn-btn-outline:hover{background:#d4a017;color:#000}
.cn-btn-green{background:#22c55e;color:#fff}.cn-btn-green:hover{background:#16a34a}
.cn-btn-red{background:#ef4444;color:#fff}.cn-btn-red:hover{background:#dc2626}
.cn-btn-blue{background:#3b82f6;color:#fff}.cn-btn-blue:hover{background:#2563eb}
.cn-input,.cn-select,.cn-textarea{width:100%;background:#0a0a14;border:1px solid #2a2a4a;color:#e2e8f0;padding:.5rem .75rem;border-radius:6px;font-size:.85rem;box-sizing:border-box}
.cn-textarea{min-height:100px;resize:vertical}
.cn-label{display:block;color:#94a3b8;font-size:.75rem;margin-bottom:.25rem;text-transform:uppercase;letter-spacing:.5px}
.cn-msg{padding:.75rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:.85rem}
.cn-msg-success{background:rgba(34,197,94,.12);border:1px solid #22c55e;color:#86efac}
.cn-msg-error{background:rgba(239,68,68,.12);border:1px solid #ef4444;color:#fca5a5}
.cn-article{border-left:4px solid #d4a017;padding:1.5rem;margin-bottom:1.25rem;background:rgba(212,160,23,.03);border-radius:0 8px 8px 0}
.cn-article h3{color:#d4a017;font-size:1.1rem;margin-bottom:.25rem}
.cn-article .art-num{font-size:.7rem;letter-spacing:.15em;text-transform:uppercase;color:#94a3b8;margin-bottom:.5rem}
.cn-article .art-body{color:#cbd5e1;font-size:.9rem;line-height:1.8;white-space:pre-wrap}
.cn-right{border-left:4px solid #22c55e;padding:1rem 1.25rem;margin-bottom:.75rem;background:rgba(34,197,94,.04);border-radius:0 8px 8px 0}
.cn-right.absolute{border-left-color:#3b82f6;background:rgba(59,130,246,.04)}
.cn-right h4{font-size:.95rem;color:#f1f5f9;margin-bottom:.25rem}
.cn-right .right-num{font-size:.65rem;color:#64748b;text-transform:uppercase;letter-spacing:.1em}
.cn-right p{color:#94a3b8;font-size:.85rem;line-height:1.7}
.cn-right .exception{color:#f59e0b;font-size:.8rem;font-style:italic;margin-top:.35rem}
.cn-amd{padding:1rem 1.25rem;margin-bottom:.75rem;border-radius:8px;border:1px solid #2a2a4a;background:#1a1a2e}
.cn-amd-header{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem}
.cn-vote-bar{display:flex;gap:.5rem;align-items:center;margin-top:.5rem;font-size:.8rem}
.cn-vote-bar .yea{color:#22c55e}.cn-vote-bar .nay{color:#ef4444}.cn-vote-bar .abs{color:#94a3b8}
.cn-modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:900;align-items:center;justify-content:center}
.cn-modal-bg.open{display:flex}
.cn-modal{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:12px;padding:1.5rem;width:90%;max-width:580px;max-height:80vh;overflow-y:auto}
.cn-modal h3{color:#f1f5f9;margin:0 0 1rem}
.cn-form-row{margin-bottom:.75rem}
.cn-seal{text-align:center;padding:2rem;margin-top:2rem;border:2px solid #d4a017;border-radius:12px;background:rgba(212,160,23,.03)}
.cn-seal i{font-size:3rem;color:#d4a017;margin-bottom:1rem}
</style>
<div class="cn-bg">
<div class="cn-wrap">
    <div class="cn-title"><i class="fas fa-scroll"></i> Constitution &amp; Supreme Law</div>
    <p class="cn-sub" style="margin-bottom:1.25rem">The supreme governing document of the GoSiteMe sovereign digital state — All ranks</p>

    <?php if ($msg): ?>
        <div class="cn-msg cn-msg-<?= $msgType ?>"><?= $msg ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="cn-stat-bar">
        <div class="cn-stat"><div class="val"><?= count($articles) ?></div><div class="lbl">Articles</div></div>
        <div class="cn-stat"><div class="val"><?= count($rights) ?></div><div class="lbl">Rights</div></div>
        <div class="cn-stat"><div class="val"><?= count($amendments) ?></div><div class="lbl">Amendments</div></div>
        <div class="cn-stat"><div class="val"><?= count($reviews) ?></div><div class="lbl">Reviews</div></div>
    </div>

    <!-- Tabs -->
    <div class="cn-tabs">
        <a href="?tab=articles" class="cn-tab <?= $tab==='articles'?'active':'' ?>"><i class="fas fa-scroll"></i> Articles</a>
        <a href="?tab=rights" class="cn-tab <?= $tab==='rights'?'active':'' ?>"><i class="fas fa-balance-scale"></i> Bill of Rights</a>
        <a href="?tab=amendments" class="cn-tab <?= $tab==='amendments'?'active':'' ?>"><i class="fas fa-pen-fancy"></i> Amendments</a>
        <a href="?tab=reviews" class="cn-tab <?= $tab==='reviews'?'active':'' ?>"><i class="fas fa-gavel"></i> Reviews</a>
    </div>

    <!-- ═══ TAB: ARTICLES ═══ -->
    <?php if ($tab === 'articles'): ?>
        <?php if ($isCommander): ?>
            <div style="margin-bottom:1rem"><button class="cn-btn" onclick="document.getElementById('modalAddArticle').classList.add('open')"><i class="fas fa-plus"></i> Add Article</button></div>
        <?php endif; ?>
        <?php foreach ($articles as $art): ?>
            <div class="cn-article">
                <div class="art-num">Article <?= (int)$art['article_number'] ?></div>
                <h3><?= htmlspecialchars($art['title']) ?></h3>
                <?php if ($art['amended_at']): ?>
                    <div style="font-size:.7rem;color:#f59e0b;margin-bottom:.5rem"><i class="fas fa-pen"></i> Last amended: <?= date('M j, Y', strtotime($art['amended_at'])) ?></div>
                <?php endif; ?>
                <div class="art-body"><?= htmlspecialchars($art['content']) ?></div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($articles)): ?>
            <div class="cn-card" style="text-align:center;color:#64748b"><i class="fas fa-scroll" style="font-size:2rem;margin-bottom:.5rem"></i><p>No articles yet.</p></div>
        <?php endif; ?>

    <!-- ═══ TAB: BILL OF RIGHTS ═══ -->
    <?php elseif ($tab === 'rights'): ?>
        <div class="cn-seal" style="margin-bottom:1.5rem;padding:1.5rem">
            <i class="fas fa-shield-halved"></i>
            <h3 style="color:#d4a017;margin:.5rem 0">Bill of Rights</h3>
            <p style="color:#94a3b8;font-size:.85rem">Fundamental rights guaranteed to all citizens of the GoSiteMe sovereign digital state. Absolute rights may never be suspended.</p>
        </div>
        <?php foreach ($rights as $r): ?>
            <div class="cn-right <?= $r['is_absolute'] ? 'absolute' : '' ?>">
                <div class="right-num">Right <?= (int)$r['right_number'] ?> — <?= $r['is_absolute'] ? '🔒 ABSOLUTE' : '⚖️ CONDITIONAL' ?></div>
                <h4><?= htmlspecialchars($r['title']) ?></h4>
                <p><?= htmlspecialchars($r['description']) ?></p>
                <?php if ($r['exceptions']): ?>
                    <div class="exception"><i class="fas fa-exclamation-triangle"></i> Exception: <?= htmlspecialchars($r['exceptions']) ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

    <!-- ═══ TAB: AMENDMENTS ═══ -->
    <?php elseif ($tab === 'amendments'): ?>
        <?php if ($isFlag): ?>
            <div style="margin-bottom:1rem"><button class="cn-btn" onclick="document.getElementById('modalAmendment').classList.add('open')"><i class="fas fa-pen-fancy"></i> Propose Amendment</button></div>
        <?php endif; ?>
        <?php
        $statusColors = ['proposed'=>'#64748b','debate'=>'#f59e0b','ratified'=>'#22c55e','rejected'=>'#ef4444'];
        foreach ($amendments as $amd): ?>
            <div class="cn-amd">
                <div class="cn-amd-header">
                    <div>
                        <strong style="color:#f1f5f9">Amendment #<?= (int)$amd['amendment_number'] ?>:</strong>
                        <span style="color:#cbd5e1"><?= htmlspecialchars($amd['proposal_title']) ?></span>
                    </div>
                    <span class="cn-badge" style="background:<?= $statusColors[$amd['status']] ?? '#64748b' ?>20;color:<?= $statusColors[$amd['status']] ?? '#64748b' ?>;border:1px solid <?= $statusColors[$amd['status']] ?? '#64748b' ?>40"><?= strtoupper($amd['status']) ?></span>
                </div>
                <div style="color:#94a3b8;font-size:.8rem;margin-top:.25rem">Proposed by <?= htmlspecialchars($amd['proposer_name'] ?? 'Unknown') ?> on <?= date('M j, Y', strtotime($amd['created_at'])) ?></div>
                <p style="color:#cbd5e1;font-size:.85rem;margin-top:.5rem;white-space:pre-wrap"><?= htmlspecialchars($amd['proposal_text']) ?></p>
                <?php if ($amd['rationale']): ?>
                    <div style="color:#94a3b8;font-size:.8rem;font-style:italic;margin-top:.35rem"><strong>Rationale:</strong> <?= htmlspecialchars($amd['rationale']) ?></div>
                <?php endif; ?>
                <div class="cn-vote-bar">
                    <span class="yea"><i class="fas fa-check"></i> <?= (int)$amd['votes_yea'] ?> Yea</span>
                    <span class="nay"><i class="fas fa-times"></i> <?= (int)$amd['votes_nay'] ?> Nay</span>
                    <span class="abs"><i class="fas fa-minus"></i> <?= (int)$amd['votes_abstain'] ?> Abstain</span>
                    <?php if ($amd['debate_end']): ?>
                        <span style="color:#64748b;margin-left:auto">Debate ends: <?= date('M j, Y H:i', strtotime($amd['debate_end'])) ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($amd['status'] === 'debate'): ?>
                    <div style="margin-top:.75rem;display:flex;gap:.5rem;flex-wrap:wrap">
                        <?php if ($isFlag && !isset($myVotes[$amd['id']])): ?>
                            <form method="POST" style="display:inline"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="vote_amendment"><input type="hidden" name="amd_id" value="<?= $amd['id'] ?>"><input type="hidden" name="vote" value="yea"><button class="cn-btn-sm cn-btn cn-btn-green">Vote YEA</button></form>
                            <form method="POST" style="display:inline"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="vote_amendment"><input type="hidden" name="amd_id" value="<?= $amd['id'] ?>"><input type="hidden" name="vote" value="nay"><button class="cn-btn-sm cn-btn cn-btn-red">Vote NAY</button></form>
                            <form method="POST" style="display:inline"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="vote_amendment"><input type="hidden" name="amd_id" value="<?= $amd['id'] ?>"><input type="hidden" name="vote" value="abstain"><button class="cn-btn-sm cn-btn cn-btn-outline" style="border-color:#94a3b8;color:#94a3b8">ABSTAIN</button></form>
                        <?php elseif (isset($myVotes[$amd['id']])): ?>
                            <span style="color:#64748b;font-size:.8rem"><i class="fas fa-check-circle"></i> You voted: <strong><?= strtoupper($myVotes[$amd['id']]) ?></strong></span>
                        <?php endif; ?>
                        <?php if ($isCommander): ?>
                            <form method="POST" style="display:inline;margin-left:auto"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="ratify_amendment"><input type="hidden" name="amd_id" value="<?= $amd['id'] ?>"><button class="cn-btn-sm cn-btn" style="background:#d4a017;color:#000"><i class="fas fa-stamp"></i> Ratify</button></form>
                            <form method="POST" style="display:inline"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="reject_amendment"><input type="hidden" name="amd_id" value="<?= $amd['id'] ?>"><button class="cn-btn-sm cn-btn cn-btn-red"><i class="fas fa-ban"></i> Reject</button></form>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if (empty($amendments)): ?>
            <div class="cn-card" style="text-align:center;color:#64748b"><i class="fas fa-pen-fancy" style="font-size:2rem;margin-bottom:.5rem"></i><p>No amendments have been proposed.</p></div>
        <?php endif; ?>

    <!-- ═══ TAB: REVIEWS ═══ -->
    <?php elseif ($tab === 'reviews'): ?>
        <div style="margin-bottom:1rem"><button class="cn-btn cn-btn-blue" onclick="document.getElementById('modalReview').classList.add('open')"><i class="fas fa-gavel"></i> Petition for Review</button></div>
        <?php
        $rvColors = ['pending'=>'#f59e0b','hearing'=>'#3b82f6','ruled'=>'#22c55e','dismissed'=>'#64748b'];
        foreach ($reviews as $rv): ?>
            <div class="cn-card">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div>
                        <strong style="color:#d4a017;font-size:.8rem"><?= htmlspecialchars($rv['review_code']) ?></strong>
                        <span style="color:#f1f5f9;margin-left:.5rem"><?= htmlspecialchars($rv['subject']) ?></span>
                    </div>
                    <span class="cn-badge" style="background:<?= $rvColors[$rv['status']] ?>20;color:<?= $rvColors[$rv['status']] ?>;border:1px solid <?= $rvColors[$rv['status']] ?>40"><?= strtoupper($rv['status']) ?></span>
                </div>
                <div style="color:#94a3b8;font-size:.8rem;margin-top:.25rem">Petitioned by <?= htmlspecialchars($rv['petitioner_name'] ?? 'Unknown') ?> on <?= date('M j, Y', strtotime($rv['created_at'])) ?></div>
                <?php if ($rv['target_law']): ?><div style="color:#64748b;font-size:.8rem;margin-top:.25rem"><strong>Target Law:</strong> <?= htmlspecialchars($rv['target_law']) ?></div><?php endif; ?>
                <p style="color:#cbd5e1;font-size:.85rem;margin-top:.5rem"><?= htmlspecialchars($rv['argument']) ?></p>
                <?php if ($rv['ruling']): ?>
                    <div style="border-top:1px solid #2a2a4a;margin-top:.75rem;padding-top:.75rem">
                        <strong style="color:#22c55e;font-size:.8rem"><i class="fas fa-gavel"></i> RULING (<?= date('M j, Y', strtotime($rv['ruling_date'])) ?>):</strong>
                        <p style="color:#86efac;font-size:.85rem;margin-top:.25rem"><?= htmlspecialchars($rv['ruling']) ?></p>
                    </div>
                <?php endif; ?>
                <?php if ($isFlag && in_array($rv['status'], ['pending','hearing'], true)): ?>
                    <div style="margin-top:.75rem">
                        <button class="cn-btn-sm cn-btn cn-btn-blue" onclick="openRulingModal(<?= $rv['id'] ?>,'<?= htmlspecialchars($rv['review_code'], ENT_QUOTES) ?>')"><i class="fas fa-gavel"></i> Issue Ruling</button>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if (empty($reviews)): ?>
            <div class="cn-card" style="text-align:center;color:#64748b"><i class="fas fa-gavel" style="font-size:2rem;margin-bottom:.5rem"></i><p>No constitutional reviews filed.</p></div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Constitutional Seal -->
    <div class="cn-seal">
        <i class="fas fa-landmark"></i>
        <h3 style="color:#d4a017;margin:.5rem 0">Constitution of the GoSiteMe Sovereign Digital State</h3>
        <p style="color:#94a3b8;font-size:.8rem">Founded by Commander Danny William Perez &bull; Heir: The Commander's Firstborn Daughter</p>
        <p style="color:#64748b;font-size:.7rem;margin-top:.5rem"><em>"The First and the Last, the Beginning and the End."</em> &mdash; Revelation 22:13</p>
    </div>
</div>
</div>

<!-- ═══ Modal: Add Article ═══ -->
<div class="cn-modal-bg" id="modalAddArticle">
<div class="cn-modal">
    <h3><i class="fas fa-scroll"></i> Add Constitutional Article</h3>
    <form method="POST">
        <input type="hidden" name="csrf" value="<?= $csrf ?>">
        <input type="hidden" name="action" value="add_article">
        <div class="cn-form-row"><label class="cn-label">Article Number</label><input type="number" name="art_number" class="cn-input" min="1" required></div>
        <div class="cn-form-row"><label class="cn-label">Title</label><input type="text" name="art_title" class="cn-input" required></div>
        <div class="cn-form-row"><label class="cn-label">Content</label><textarea name="art_content" class="cn-textarea" required></textarea></div>
        <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem">
            <button type="button" class="cn-btn cn-btn-outline" onclick="this.closest('.cn-modal-bg').classList.remove('open')">Cancel</button>
            <button type="submit" class="cn-btn"><i class="fas fa-plus"></i> Add Article</button>
        </div>
    </form>
</div>
</div>

<!-- ═══ Modal: Propose Amendment ═══ -->
<div class="cn-modal-bg" id="modalAmendment">
<div class="cn-modal">
    <h3><i class="fas fa-pen-fancy"></i> Propose Amendment</h3>
    <form method="POST">
        <input type="hidden" name="csrf" value="<?= $csrf ?>">
        <input type="hidden" name="action" value="propose_amendment">
        <div class="cn-form-row"><label class="cn-label">Amends Article (optional)</label>
            <select name="amd_article_id" class="cn-select">
                <option value="">General Amendment (no specific article)</option>
                <?php foreach ($articles as $a): ?><option value="<?= $a['id'] ?>">Article <?= (int)$a['article_number'] ?>: <?= htmlspecialchars($a['title']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="cn-form-row"><label class="cn-label">Amendment Title</label><input type="text" name="amd_title" class="cn-input" required></div>
        <div class="cn-form-row"><label class="cn-label">Amendment Text</label><textarea name="amd_text" class="cn-textarea" required></textarea></div>
        <div class="cn-form-row"><label class="cn-label">Rationale</label><textarea name="amd_rationale" class="cn-textarea" style="min-height:60px"></textarea></div>
        <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem">
            <button type="button" class="cn-btn cn-btn-outline" onclick="this.closest('.cn-modal-bg').classList.remove('open')">Cancel</button>
            <button type="submit" class="cn-btn"><i class="fas fa-paper-plane"></i> Submit Proposal</button>
        </div>
    </form>
</div>
</div>

<!-- ═══ Modal: Petition Review ═══ -->
<div class="cn-modal-bg" id="modalReview">
<div class="cn-modal">
    <h3><i class="fas fa-gavel"></i> Petition for Constitutional Review</h3>
    <form method="POST">
        <input type="hidden" name="csrf" value="<?= $csrf ?>">
        <input type="hidden" name="action" value="petition_review">
        <div class="cn-form-row"><label class="cn-label">Subject</label><input type="text" name="rv_subject" class="cn-input" required placeholder="e.g., Unlawful demotion without due process"></div>
        <div class="cn-form-row"><label class="cn-label">Target Law / Article</label><input type="text" name="rv_target_law" class="cn-input" placeholder="e.g., Constitution Article 3, FM-006 Section VII"></div>
        <div class="cn-form-row"><label class="cn-label">Action Being Challenged</label><textarea name="rv_target_action" class="cn-textarea" style="min-height:60px" placeholder="Describe the specific action or order you believe violates the Constitution"></textarea></div>
        <div class="cn-form-row"><label class="cn-label">Argument</label><textarea name="rv_argument" class="cn-textarea" required placeholder="Present your constitutional argument"></textarea></div>
        <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem">
            <button type="button" class="cn-btn cn-btn-outline" onclick="this.closest('.cn-modal-bg').classList.remove('open')">Cancel</button>
            <button type="submit" class="cn-btn cn-btn-blue"><i class="fas fa-gavel"></i> File Petition</button>
        </div>
    </form>
</div>
</div>

<!-- ═══ Modal: Issue Ruling ═══ -->
<div class="cn-modal-bg" id="modalRuling">
<div class="cn-modal">
    <h3><i class="fas fa-gavel"></i> Issue Ruling</h3>
    <form method="POST">
        <input type="hidden" name="csrf" value="<?= $csrf ?>">
        <input type="hidden" name="action" value="rule_review">
        <input type="hidden" name="rv_id" id="rulingRvId" value="">
        <div style="color:#94a3b8;font-size:.85rem;margin-bottom:1rem">Case: <strong id="rulingRvCode" style="color:#d4a017"></strong></div>
        <div class="cn-form-row"><label class="cn-label">Decision</label>
            <select name="rv_status" class="cn-select"><option value="ruled">Ruled — Issue Binding Ruling</option><option value="dismissed">Dismissed — No Merit</option></select>
        </div>
        <div class="cn-form-row"><label class="cn-label">Ruling Text</label><textarea name="rv_ruling" class="cn-textarea" required></textarea></div>
        <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem">
            <button type="button" class="cn-btn cn-btn-outline" onclick="this.closest('.cn-modal-bg').classList.remove('open')">Cancel</button>
            <button type="submit" class="cn-btn cn-btn-blue"><i class="fas fa-stamp"></i> Issue Ruling</button>
        </div>
    </form>
</div>
</div>

<script>
function openRulingModal(rvId, code) {
    document.getElementById('rulingRvId').value = rvId;
    document.getElementById('rulingRvCode').textContent = code;
    document.getElementById('modalRuling').classList.add('open');
}
document.querySelectorAll('.cn-modal-bg').forEach(bg => {
    bg.addEventListener('click', e => { if (e.target === bg) bg.classList.remove('open'); });
});
</script>
<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
