<?php
/**
 * Military Court — Level 3 Justice System
 * File cases, view proceedings, render verdicts. Flag+ officers serve as judges.
 */
require_once __DIR__ . '/includes/auth-gate.inc.php';
require_once __DIR__ . '/includes/rank-guard.inc.php';

$db = getSharedDB();

// Access: Tier 1+ can view, Tier 9+ (Flag) can judge/file
$canJudge = hasProductAccess('court_judge');   // Flag+ (tier 9)
$canFile = $userRankTier >= 4;                 // NCO+ can file cases

// Handle case filing
$error = '';
$filed = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canFile && !empty($clientId)) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_court'] ?? '', $csrf)) {
        $error = 'Invalid form submission.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'file') {
            $defendantId = (int)($_POST['defendant_id'] ?? 0);
            $violationType = $_POST['violation_type'] ?? 'conduct';
            $severity = $_POST['severity'] ?? 'moderate';
            $description = trim($_POST['description'] ?? '');
            $evidence = trim($_POST['evidence'] ?? '');

            $validTypes = ['conduct','abuse','fraud','insubordination','desertion','espionage','spam','harassment','other'];
            $validSev = ['minor','moderate','severe','capital'];
            if (!in_array($violationType, $validTypes, true)) $violationType = 'conduct';
            if (!in_array($severity, $validSev, true)) $severity = 'moderate';

            if ($defendantId <= 0 || strlen($description) < 10) {
                $error = 'Defendant ID and description (10+ chars) are required.';
            } else {
                // Generate case number
                $year = date('Y');
                $countStmt = $db->prepare("SELECT COUNT(*) FROM military_court_cases WHERE case_number LIKE ?");
                $countStmt->execute(["MC-{$year}-%"]);
                $num = (int)$countStmt->fetchColumn() + 1;
                $caseNumber = "MC-{$year}-" . str_pad($num, 4, '0', STR_PAD_LEFT);

                $stmt = $db->prepare("
                    INSERT INTO military_court_cases (case_number, defendant_client_id, prosecutor_client_id, violation_type, severity, description, evidence, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'filed')
                ");
                $stmt->execute([$caseNumber, $defendantId, $clientId, $violationType, $severity, $description, $evidence]);

                // Notify defendant
                $db->prepare("INSERT INTO military_notifications (client_id, notification_type, title, message, data) VALUES (?, 'court_summons', ?, ?, ?)")
                   ->execute([$defendantId, "Court Summons: Case {$caseNumber}", "A case has been filed against you. Violation: {$violationType}. Severity: {$severity}.",
                       json_encode(['case_number' => $caseNumber, 'violation_type' => $violationType])]);

                $filed = true;
            }
        }

        if ($action === 'verdict' && $canJudge) {
            $caseId = (int)($_POST['case_id'] ?? 0);
            $verdict = $_POST['verdict'] ?? '';
            $sentence = trim($_POST['sentence'] ?? '');
            $validVerdicts = ['guilty','not_guilty','dismissed','plea_deal'];
            if (!in_array($verdict, $validVerdicts, true)) $verdict = 'dismissed';

            $db->prepare("UPDATE military_court_cases SET status = 'verdict', verdict = ?, sentence = ?, judge_client_id = ?, resolved_at = NOW() WHERE id = ? AND status IN ('filed','investigating','trial')")
               ->execute([$verdict, $sentence, $clientId, $caseId]);

            // Load case for notification
            $caseStmt = $db->prepare("SELECT * FROM military_court_cases WHERE id = ?");
            $caseStmt->execute([$caseId]);
            $theCase = $caseStmt->fetch(PDO::FETCH_ASSOC);

            if ($theCase) {
                // Apply sentence if guilty
                if ($verdict === 'guilty' && $sentence) {
                    $sentenceData = json_decode($sentence, true);
                    if ($sentenceData) {
                        // XP deduction
                        if (!empty($sentenceData['xp_deduction'])) {
                            $db->prepare("UPDATE user_ranks SET xp = GREATEST(0, CAST(xp AS SIGNED) - ?) WHERE client_id = ? AND is_active = 1")
                               ->execute([(int)$sentenceData['xp_deduction'], $theCase['defendant_client_id']]);
                        }
                        // Demotion
                        if (!empty($sentenceData['demote_to'])) {
                            $db->prepare("UPDATE user_ranks SET rank_code = ? WHERE client_id = ? AND is_active = 1")
                               ->execute([$sentenceData['demote_to'], $theCase['defendant_client_id']]);
                            $db->prepare("INSERT INTO rank_history (client_id, action, from_rank, to_rank, reason, performed_by, performed_at) VALUES (?, 'demote', NULL, ?, ?, ?, NOW())")
                               ->execute([$theCase['defendant_client_id'], $sentenceData['demote_to'], "Court verdict: Case {$theCase['case_number']}", $clientId]);
                            invalidateRankCache($theCase['defendant_client_id']);
                        }
                    }
                }

                // Notify defendant of verdict
                $db->prepare("INSERT INTO military_notifications (client_id, notification_type, title, message, data) VALUES (?, 'court_summons', ?, ?, ?)")
                   ->execute([$theCase['defendant_client_id'], "Verdict: Case {$theCase['case_number']} — " . strtoupper($verdict),
                       $verdict === 'guilty' ? "You have been found guilty. Sentence: {$sentence}" : "Case resolved. Verdict: {$verdict}.",
                       json_encode(['case_number' => $theCase['case_number'], 'verdict' => $verdict])]);
            }

            header('Location: /military-court');
            exit;
        }
    }
}

// Generate CSRF
if (empty($_SESSION['csrf_court'])) {
    $_SESSION['csrf_court'] = bin2hex(random_bytes(32));
}

// Load cases
$openCases = $db->query("SELECT mc.*, c.firstname, c.lastname FROM military_court_cases mc LEFT JOIN clients c ON c.id = mc.defendant_client_id WHERE mc.status NOT IN ('closed','dismissed') ORDER BY mc.filed_at DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
$closedCases = $db->query("SELECT mc.*, c.firstname, c.lastname FROM military_court_cases mc LEFT JOIN clients c ON c.id = mc.defendant_client_id WHERE mc.status IN ('closed','dismissed','verdict') ORDER BY mc.resolved_at DESC LIMIT 25")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Military Court — GoSiteMe';
include __DIR__ . '/includes/site-header.inc.php';
?>
<style>
.court-page{max-width:1000px;margin:0 auto;padding:2rem 1.5rem}
.court-hero{text-align:center;margin-bottom:2rem}
.court-hero h1{font-size:2.2rem;color:#e2b340;font-weight:800;margin-bottom:.3rem}
.court-hero .sub{color:#888;font-size:.95rem}
.court-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:2rem}
.court-stat{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:10px;padding:1rem;text-align:center}
.court-stat .v{font-size:1.5rem;color:#e2b340;font-weight:800}
.court-stat .l{color:#888;font-size:.7rem;text-transform:uppercase;letter-spacing:1px;margin-top:.2rem}
.case-card{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08);border-radius:10px;padding:1.2rem;margin-bottom:.8rem}
.case-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem;flex-wrap:wrap;gap:.5rem}
.case-num{color:#e2b340;font-weight:800;font-family:'JetBrains Mono',monospace;font-size:.85rem}
.case-status{padding:3px 10px;border-radius:4px;font-size:.7rem;font-weight:700;text-transform:uppercase}
.case-status.filed{background:rgba(255,152,0,.2);color:#ffa726}
.case-status.investigating{background:rgba(33,150,243,.2);color:#42a5f5}
.case-status.trial{background:rgba(156,39,176,.2);color:#ab47bc}
.case-status.verdict{background:rgba(76,175,80,.2);color:#66bb6a}
.case-status.dismissed{background:rgba(158,158,158,.2);color:#999}
.case-body{color:#999;font-size:.85rem;line-height:1.5;margin-bottom:.5rem}
.case-meta{display:flex;gap:1rem;flex-wrap:wrap;font-size:.7rem;color:#666}
.sev-tag{padding:2px 6px;border-radius:3px;font-weight:600;font-size:.65rem;text-transform:uppercase}
.sev-tag.minor{background:rgba(76,175,80,.15);color:#4caf50}
.sev-tag.moderate{background:rgba(255,193,7,.15);color:#ffc107}
.sev-tag.severe{background:rgba(255,87,34,.15);color:#ff5722}
.sev-tag.capital{background:rgba(244,67,54,.2);color:#f44336}
.file-form{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.1);border-radius:12px;padding:1.5rem;margin-bottom:2rem}
.file-form h3{color:#e2b340;margin-bottom:1rem}
.file-form label{display:block;color:#999;font-size:.8rem;margin-bottom:.3rem;margin-top:.8rem}
.file-form input,.file-form select,.file-form textarea{width:100%;padding:.6rem;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:6px;color:#eee;font-size:.85rem;box-sizing:border-box}
.file-form textarea{min-height:80px;resize:vertical}
.file-btn{background:#e2b340;color:#111;padding:.7rem 1.5rem;border:none;border-radius:6px;font-weight:700;cursor:pointer;margin-top:1rem;font-size:.9rem}
.verdict-form{margin-top:.8rem;padding:.8rem;background:rgba(226,179,64,.05);border:1px solid rgba(226,179,64,.2);border-radius:8px}
.verdict-form select,.verdict-form textarea{width:100%;padding:.5rem;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:4px;color:#eee;font-size:.8rem;margin-top:.3rem;box-sizing:border-box}
.section-title{color:#e2b340;font-size:1rem;font-weight:700;margin:2rem 0 .8rem;padding-bottom:.3rem;border-bottom:1px solid rgba(226,179,64,.2)}
</style>

<main class="main-content">
<div class="court-page">

    <div class="court-hero">
        <h1>&#x2696;&#xFE0F; Military Court</h1>
        <p class="sub">Justice and discipline within the GoSiteMe Sovereign Military</p>
    </div>

    <div class="court-stats">
        <div class="court-stat"><div class="v"><?= count($openCases) ?></div><div class="l">Open Cases</div></div>
        <div class="court-stat"><div class="v"><?= count($closedCases) ?></div><div class="l">Resolved</div></div>
        <div class="court-stat"><div class="v"><?= $canJudge ? 'Active' : 'View Only' ?></div><div class="l">Your Authority</div></div>
    </div>

    <?php if ($filed): ?>
        <div style="background:rgba(76,175,80,.12);border:1px solid rgba(76,175,80,.3);border-radius:8px;padding:1rem;margin-bottom:1.5rem;text-align:center;color:#66bb6a">
            &#x2714; Case filed successfully. The defendant has been notified.
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div style="background:rgba(244,67,54,.12);border:1px solid rgba(244,67,54,.3);border-radius:8px;padding:1rem;margin-bottom:1.5rem;text-align:center;color:#ef5350">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($canFile && !empty($clientId)): ?>
    <div class="file-form">
        <h3>&#x1F4DD; File a Case</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_court']) ?>">
            <input type="hidden" name="action" value="file">
            <label>Defendant Client ID</label>
            <input type="number" name="defendant_id" required min="1" placeholder="Client ID of the accused">
            <label>Violation Type</label>
            <select name="violation_type">
                <option value="conduct">Conduct Violation</option>
                <option value="abuse">Abuse of Power</option>
                <option value="fraud">Fraud</option>
                <option value="insubordination">Insubordination</option>
                <option value="desertion">Desertion</option>
                <option value="spam">Spam / Disruption</option>
                <option value="harassment">Harassment</option>
                <option value="other">Other</option>
            </select>
            <label>Severity</label>
            <select name="severity">
                <option value="minor">Minor</option>
                <option value="moderate" selected>Moderate</option>
                <option value="severe">Severe</option>
                <option value="capital">Capital</option>
            </select>
            <label>Description</label>
            <textarea name="description" required minlength="10" placeholder="Describe the violation in detail..."></textarea>
            <label>Evidence (optional)</label>
            <textarea name="evidence" placeholder="Links, screenshots, logs, timestamps..."></textarea>
            <button type="submit" class="file-btn">&#x2696;&#xFE0F; File Case</button>
        </form>
    </div>
    <?php endif; ?>

    <div class="section-title">&#x1F4C2; Open Cases</div>
    <?php if (empty($openCases)): ?>
        <div style="text-align:center;padding:2rem;color:#666">
            <p>&#x2696;&#xFE0F; No open cases. The ranks are in order.</p>
        </div>
    <?php else: ?>
        <?php foreach ($openCases as $c): ?>
        <div class="case-card">
            <div class="case-header">
                <span class="case-num"><?= htmlspecialchars($c['case_number']) ?></span>
                <div style="display:flex;gap:.5rem;align-items:center">
                    <span class="sev-tag <?= $c['severity'] ?>"><?= $c['severity'] ?></span>
                    <span class="case-status <?= $c['status'] ?>"><?= $c['status'] ?></span>
                </div>
            </div>
            <div class="case-body">
                <strong>Defendant:</strong> <?= htmlspecialchars(trim(($c['firstname'] ?? '') . ' ' . ($c['lastname'] ?? '')) ?: "Client #{$c['defendant_client_id']}") ?>
                &bull; <strong>Violation:</strong> <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $c['violation_type']))) ?>
                <br><?= htmlspecialchars($c['description']) ?>
            </div>
            <div class="case-meta">
                <span>Filed: <?= date('M j, Y', strtotime($c['filed_at'])) ?></span>
                <?php if ($c['prosecutor_client_id']): ?>
                    <span>Prosecutor: Client #<?= $c['prosecutor_client_id'] ?></span>
                <?php endif; ?>
            </div>

            <?php if ($canJudge && in_array($c['status'], ['filed','investigating','trial'])): ?>
            <div class="verdict-form">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_court']) ?>">
                    <input type="hidden" name="action" value="verdict">
                    <input type="hidden" name="case_id" value="<?= $c['id'] ?>">
                    <label style="color:#e2b340;font-size:.75rem;font-weight:700">Render Verdict</label>
                    <select name="verdict">
                        <option value="guilty">Guilty</option>
                        <option value="not_guilty">Not Guilty</option>
                        <option value="dismissed">Dismissed</option>
                        <option value="plea_deal">Plea Deal</option>
                    </select>
                    <label style="color:#999;font-size:.7rem;margin-top:.3rem">Sentence (JSON for guilty: {"xp_deduction":100,"demote_to":"private"})</label>
                    <textarea name="sentence" rows="2" placeholder='{"xp_deduction": 100}'></textarea>
                    <button type="submit" class="file-btn" style="font-size:.8rem;padding:.5rem 1rem;margin-top:.5rem">&#x2696;&#xFE0F; Issue Verdict</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (!empty($closedCases)): ?>
    <div class="section-title">&#x1F4DA; Resolved Cases</div>
    <?php foreach ($closedCases as $c): ?>
    <div class="case-card" style="opacity:.7">
        <div class="case-header">
            <span class="case-num"><?= htmlspecialchars($c['case_number']) ?></span>
            <span class="case-status verdict"><?= $c['verdict'] ? strtoupper($c['verdict']) : 'RESOLVED' ?></span>
        </div>
        <div class="case-body">
            <strong>Defendant:</strong> <?= htmlspecialchars(trim(($c['firstname'] ?? '') . ' ' . ($c['lastname'] ?? '')) ?: "Client #{$c['defendant_client_id']}") ?>
            &bull; <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $c['violation_type']))) ?>
            <?php if ($c['sentence']): ?><br><strong>Sentence:</strong> <?= htmlspecialchars($c['sentence']) ?><?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <div style="text-align:center;margin-top:2rem;padding-bottom:2rem">
        <a href="/military-hq" style="color:#e2b340;text-decoration:underline">← Back to Military HQ</a>
    </div>

</div>
</main>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
