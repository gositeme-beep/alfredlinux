<?php
/**
 * Cyber Operations Center (CYBERCOM) — CTF Challenge Hub
 * GoSiteMe Military Ecosystem
 */
session_start();

require_once __DIR__ . '/includes/db-config.inc.php';
$db = getSharedDB();
require_once __DIR__ . '/includes/auth-gate.inc.php';
require_once __DIR__ . '/includes/rank-guard.inc.php';

requireRank(3); // Sergeant+ (E-4, tier 4)

if (empty($_SESSION['csrf_cyberops'])) {
    $_SESSION['csrf_cyberops'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_cyberops'];
$isCommander = ($clientId === 33);

// ── POST Handlers ───────────────────────────────────────────
$flash = '';
$flashType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!hash_equals($csrfToken, $_POST['csrf_token'] ?? '')) {
        $flash = 'Security token mismatch.';
        $flashType = 'error';
    } else {
        $action = $_POST['action'];

        // ── Submit Flag ─────────────────────────────────────
        if ($action === 'submit_flag') {
            $challengeId = (int)($_POST['challenge_id'] ?? 0);
            $flag = trim($_POST['flag'] ?? '');

            $ch = $db->prepare("SELECT id, flag_hash, points, max_attempts, is_active FROM cyber_challenges WHERE id = ?");
            $ch->execute([$challengeId]);
            $challenge = $ch->fetch(PDO::FETCH_ASSOC);

            if (!$challenge || !$challenge['is_active']) {
                $flash = 'Challenge not found or inactive.';
                $flashType = 'error';
            } elseif ($flag === '') {
                $flash = 'Flag cannot be empty.';
                $flashType = 'error';
            } else {
                // Check already solved
                $solved = $db->prepare("SELECT id FROM cyber_submissions WHERE challenge_id = ? AND client_id = ? AND is_correct = 1");
                $solved->execute([$challengeId, $clientId]);
                if ($solved->fetch()) {
                    $flash = 'You already solved this challenge.';
                    $flashType = 'info';
                } else {
                    // Count attempts
                    $attStmt = $db->prepare("SELECT COUNT(*) FROM cyber_submissions WHERE challenge_id = ? AND client_id = ?");
                    $attStmt->execute([$challengeId, $clientId]);
                    $attempts = (int)$attStmt->fetchColumn();

                    if ($challenge['max_attempts'] > 0 && $attempts >= $challenge['max_attempts']) {
                        $flash = 'Maximum attempts reached for this challenge.';
                        $flashType = 'error';
                    } else {
                        $submittedHash = hash('sha256', $flag);
                        $isCorrect = hash_equals($challenge['flag_hash'], $submittedHash) ? 1 : 0;
                        $pointsAwarded = $isCorrect ? (int)$challenge['points'] : 0;

                        $ins = $db->prepare("INSERT INTO cyber_submissions (challenge_id, client_id, submitted_flag_hash, is_correct, attempt_number, points_awarded, submitted_at) VALUES (?,?,?,?,?,?,NOW())");
                        $ins->execute([$challengeId, $clientId, $submittedHash, $isCorrect, $attempts + 1, $pointsAwarded]);

                        if ($isCorrect) {
                            // Update scoreboard
                            $db->prepare("
                                INSERT INTO cyber_scoreboard (client_id, total_points, challenges_solved, challenges_attempted, current_streak, best_streak, rank_position, updated_at)
                                VALUES (?, ?, 1, 1, 1, 1, 0, NOW())
                                ON DUPLICATE KEY UPDATE
                                    total_points = total_points + VALUES(total_points),
                                    challenges_solved = challenges_solved + 1,
                                    current_streak = current_streak + 1,
                                    best_streak = GREATEST(best_streak, current_streak + 1),
                                    updated_at = NOW()
                            ")->execute([$clientId, $pointsAwarded]);

                            // Recalc rank positions
                            $db->exec("SET @r=0; UPDATE cyber_scoreboard SET rank_position = (@r:=@r+1) ORDER BY total_points DESC");

                            awardXP($clientId, 'cyber_challenge', ['challenge_id' => $challengeId]);
                            $flash = "Correct! +{$pointsAwarded} points awarded.";
                            $flashType = 'success';
                        } else {
                            // Reset streak on wrong answer
                            $db->prepare("UPDATE cyber_scoreboard SET current_streak = 0 WHERE client_id = ?")->execute([$clientId]);
                            $remaining = $challenge['max_attempts'] > 0 ? ($challenge['max_attempts'] - $attempts - 1) : '∞';
                            $flash = "Incorrect flag. Attempts remaining: {$remaining}";
                            $flashType = 'error';
                        }
                    }
                }
            }
        }

        // ── Join Operation ──────────────────────────────────
        if ($action === 'join_operation') {
            $opId = (int)($_POST['operation_id'] ?? 0);
            $op = $db->prepare("SELECT id, status, team_size_max FROM cyber_operations WHERE id = ? AND status IN ('planning','active')");
            $op->execute([$opId]);
            $operation = $op->fetch(PDO::FETCH_ASSOC);

            if (!$operation) {
                $flash = 'Operation not found or not accepting members.';
                $flashType = 'error';
            } else {
                $already = $db->prepare("SELECT id FROM cyber_op_members WHERE operation_id = ? AND client_id = ?");
                $already->execute([$opId, $clientId]);
                if ($already->fetch()) {
                    $flash = 'You are already in this operation.';
                    $flashType = 'info';
                } else {
                    $memberCount = $db->prepare("SELECT COUNT(*) FROM cyber_op_members WHERE operation_id = ? AND status = 'active'");
                    $memberCount->execute([$opId]);
                    if ((int)$memberCount->fetchColumn() >= (int)$operation['team_size_max']) {
                        $flash = 'Operation team is full.';
                        $flashType = 'error';
                    } else {
                        $db->prepare("INSERT INTO cyber_op_members (operation_id, client_id, role, status, joined_at) VALUES (?,?,'operator','active',NOW())")
                           ->execute([$opId, $clientId]);
                        $flash = 'You have joined the operation.';
                        $flashType = 'success';
                    }
                }
            }
        }

        // ── Create Operation (Officers, tier 7+) ────────────
        if ($action === 'create_operation') {
            if (!hasRank(7) && !$isCommander) {
                $flash = 'Only Officers (Major+) can create operations.';
                $flashType = 'error';
            } else {
                $opName = trim($_POST['op_name'] ?? '');
                $opType = trim($_POST['op_type'] ?? '');
                $objective = trim($_POST['objective'] ?? '');
                $teamMin = max(1, (int)($_POST['team_min'] ?? 2));
                $teamMax = max($teamMin, (int)($_POST['team_max'] ?? 5));
                $xpReward = max(0, min(5000, (int)($_POST['xp_reward'] ?? 100)));

                $allowedTypes = ['offensive', 'defensive', 'recon', 'training'];
                if ($opName === '' || !in_array($opType, $allowedTypes, true) || $objective === '') {
                    $flash = 'All fields are required. Valid types: ' . implode(', ', $allowedTypes);
                    $flashType = 'error';
                } else {
                    $db->prepare("INSERT INTO cyber_operations (name, op_type, status, objective, commander_client_id, team_size_min, team_size_max, xp_reward, created_at) VALUES (?,?,'planning',?,?,?,?,?,NOW())")
                       ->execute([$opName, $opType, $objective, $clientId, $teamMin, $teamMax, $xpReward]);
                    $flash = 'Operation created successfully.';
                    $flashType = 'success';
                }
            }
        }
    }
}

// ── Data Queries ────────────────────────────────────────────
$categories = ['recon', 'exploit', 'crypto', 'forensics', 'defense', 'web', 'reverse'];
$activeTab = in_array($_GET['cat'] ?? '', $categories) ? $_GET['cat'] : '';

$challengeQuery = "SELECT c.*, (SELECT COUNT(*) FROM cyber_submissions s WHERE s.challenge_id = c.id AND s.is_correct = 1) AS solved_count FROM cyber_challenges c WHERE c.is_active = 1";
$params = [];
if ($activeTab) {
    $challengeQuery .= " AND c.category = ?";
    $params[] = $activeTab;
}
$challengeQuery .= " ORDER BY c.category, c.difficulty, c.points";
$chStmt = $db->prepare($challengeQuery);
$chStmt->execute($params);
$challenges = $chStmt->fetchAll(PDO::FETCH_ASSOC);

// My solved challenges
$mySolved = [];
$solvedStmt = $db->prepare("SELECT challenge_id FROM cyber_submissions WHERE client_id = ? AND is_correct = 1");
$solvedStmt->execute([$clientId]);
foreach ($solvedStmt->fetchAll(PDO::FETCH_COLUMN) as $sid) $mySolved[$sid] = true;

// My attempt counts per challenge
$myAttempts = [];
$attStmt = $db->prepare("SELECT challenge_id, COUNT(*) as cnt FROM cyber_submissions WHERE client_id = ? GROUP BY challenge_id");
$attStmt->execute([$clientId]);
foreach ($attStmt->fetchAll(PDO::FETCH_ASSOC) as $a) $myAttempts[$a['challenge_id']] = (int)$a['cnt'];

// Scoreboard top 25
$leaderboard = $db->query("SELECT sb.*, CONCAT(cl.firstname, ' ', cl.lastname) AS operator_name FROM cyber_scoreboard sb JOIN tblclients cl ON cl.id = sb.client_id ORDER BY sb.total_points DESC LIMIT 25")->fetchAll(PDO::FETCH_ASSOC);

// My stats
$myStats = $db->prepare("SELECT * FROM cyber_scoreboard WHERE client_id = ?");
$myStats->execute([$clientId]);
$myStats = $myStats->fetch(PDO::FETCH_ASSOC) ?: ['total_points' => 0, 'challenges_solved' => 0, 'challenges_attempted' => 0, 'current_streak' => 0, 'best_streak' => 0, 'rank_position' => 0];

// Active operations
$ops = $db->query("SELECT o.*, (SELECT COUNT(*) FROM cyber_op_members m WHERE m.operation_id = o.id AND m.status = 'active') AS member_count, CONCAT(cl.firstname, ' ', cl.lastname) AS commander_name FROM cyber_operations o JOIN tblclients cl ON cl.id = o.commander_client_id WHERE o.status IN ('planning','active') ORDER BY o.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Am I in any operations?
$myOps = [];
$myOpsStmt = $db->prepare("SELECT operation_id FROM cyber_op_members WHERE client_id = ? AND status = 'active'");
$myOpsStmt->execute([$clientId]);
foreach ($myOpsStmt->fetchAll(PDO::FETCH_COLUMN) as $oid) $myOps[$oid] = true;

$pageTitle = 'Cyber Operations Center';
$noGlobalMain = true;
require_once __DIR__ . '/includes/site-header.inc.php';

$difficultyColors = ['easy' => '#22c55e', 'medium' => '#eab308', 'hard' => '#f97316', 'extreme' => '#ef4444', 'insane' => '#a855f7'];
$catIcons = ['recon' => 'fa-binoculars', 'exploit' => 'fa-bug', 'crypto' => 'fa-lock', 'forensics' => 'fa-microscope', 'defense' => 'fa-shield-halved', 'web' => 'fa-globe', 'reverse' => 'fa-gears'];
$opTypeIcons = ['offensive' => 'fa-crosshairs', 'defensive' => 'fa-shield', 'recon' => 'fa-satellite-dish', 'training' => 'fa-dumbbell'];
?>
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<style>
:root{--bg:#0f172a;--card:#1e293b;--border:#334155;--text:#e2e8f0;--muted:#94a3b8;--accent:#3b82f6;--success:#22c55e;--danger:#ef4444;--warn:#eab308}
*{box-sizing:border-box}
.cy-wrap{max-width:1200px;margin:0 auto;padding:24px 16px;color:var(--text);font-family:system-ui,-apple-system,sans-serif}
.cy-hero{text-align:center;padding:32px 0 24px}
.cy-hero h1{font-size:2rem;margin:0 0 8px;letter-spacing:1px}
.cy-hero p{color:var(--muted);margin:0}
.cy-flash{padding:12px 16px;border-radius:8px;margin-bottom:20px;font-weight:500}
.cy-flash.success{background:#166534;border:1px solid var(--success);color:#bbf7d0}
.cy-flash.error{background:#7f1d1d;border:1px solid var(--danger);color:#fecaca}
.cy-flash.info{background:#1e3a5f;border:1px solid var(--accent);color:#bfdbfe}
.cy-tabs{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:20px;border-bottom:1px solid var(--border);padding-bottom:12px}
.cy-tab{padding:8px 14px;border-radius:6px;color:var(--muted);text-decoration:none;font-size:.875rem;font-weight:500;transition:all .2s}
.cy-tab:hover,.cy-tab.active{background:var(--accent);color:#fff}
.cy-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;margin-bottom:32px}
.cy-card{background:var(--card);border:1px solid var(--border);border-radius:10px;padding:20px;transition:border-color .2s;cursor:pointer;position:relative}
.cy-card:hover{border-color:var(--accent)}
.cy-card.solved{border-color:var(--success)}
.cy-card .badge{display:inline-block;padding:2px 8px;border-radius:4px;font-size:.75rem;font-weight:700;text-transform:uppercase;color:#fff}
.cy-card h3{margin:10px 0 6px;font-size:1.05rem}
.cy-card .meta{color:var(--muted);font-size:.8rem;display:flex;gap:12px;align-items:center}
.cy-card .pts{margin-left:auto;font-weight:700;color:var(--accent);font-size:1rem}
.cy-card .solved-badge{position:absolute;top:12px;right:12px;color:var(--success);font-size:1.1rem}
.cy-section{margin-bottom:36px}
.cy-section h2{font-size:1.3rem;margin:0 0 16px;display:flex;align-items:center;gap:8px}
.cy-modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:1000;align-items:center;justify-content:center}
.cy-modal-bg.open{display:flex}
.cy-modal{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:28px;width:90%;max-width:560px;max-height:85vh;overflow-y:auto;position:relative}
.cy-modal h2{margin:0 0 12px;font-size:1.25rem}
.cy-modal .close{position:absolute;top:14px;right:16px;background:none;border:none;color:var(--muted);font-size:1.3rem;cursor:pointer}
.cy-modal .desc{color:var(--muted);font-size:.9rem;line-height:1.6;margin-bottom:16px}
.cy-hint{background:#0f172a;border:1px solid var(--border);border-radius:6px;padding:10px 14px;font-size:.85rem;color:var(--warn);margin-bottom:14px;display:none}
.cy-input{width:100%;padding:10px 14px;background:var(--bg);border:1px solid var(--border);border-radius:6px;color:var(--text);font-size:.95rem;margin-bottom:10px}
.cy-input:focus{outline:none;border-color:var(--accent)}
.cy-btn{padding:10px 20px;border:none;border-radius:6px;font-weight:600;cursor:pointer;font-size:.9rem;transition:opacity .2s}
.cy-btn:hover{opacity:.85}
.cy-btn-primary{background:var(--accent);color:#fff}
.cy-btn-success{background:var(--success);color:#fff}
.cy-btn-sm{padding:6px 14px;font-size:.8rem}
.cy-table{width:100%;border-collapse:collapse}
.cy-table th,.cy-table td{padding:10px 12px;text-align:left;border-bottom:1px solid var(--border);font-size:.875rem}
.cy-table th{color:var(--muted);font-weight:600;text-transform:uppercase;font-size:.75rem;letter-spacing:.5px}
.cy-table tr:hover{background:rgba(59,130,246,.06)}
.cy-stat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;margin-bottom:20px}
.cy-stat{background:var(--card);border:1px solid var(--border);border-radius:8px;padding:16px;text-align:center}
.cy-stat .val{font-size:1.5rem;font-weight:700;color:var(--accent)}
.cy-stat .lbl{font-size:.75rem;color:var(--muted);text-transform:uppercase;margin-top:4px}
.op-card{background:var(--card);border:1px solid var(--border);border-radius:10px;padding:20px;margin-bottom:12px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px}
.op-card h3{margin:0 0 4px;font-size:1rem}
.op-card .op-meta{color:var(--muted);font-size:.8rem;display:flex;gap:14px;flex-wrap:wrap}
.op-status{display:inline-block;padding:2px 8px;border-radius:4px;font-size:.7rem;font-weight:700;text-transform:uppercase}
.op-status.planning{background:#854d0e;color:#fef08a}
.op-status.active{background:#166534;color:#bbf7d0}
.create-op-form{background:var(--card);border:1px solid var(--border);border-radius:10px;padding:20px;margin-bottom:20px;display:none}
.create-op-form.open{display:block}
.form-row{display:flex;gap:12px;margin-bottom:10px;flex-wrap:wrap}
.form-row>*{flex:1;min-width:140px}
.form-row label{display:block;font-size:.8rem;color:var(--muted);margin-bottom:4px}
.form-row select{width:100%;padding:8px;background:var(--bg);border:1px solid var(--border);border-radius:6px;color:var(--text)}
@media(max-width:640px){.cy-grid{grid-template-columns:1fr}.cy-stat-grid{grid-template-columns:repeat(2,1fr)}.op-card{flex-direction:column;align-items:flex-start}}
</style>
<main style="background:var(--bg);min-height:100vh">
<div class="cy-wrap">

<!-- Hero -->
<div class="cy-hero">
    <h1><i class="fas fa-terminal"></i> CYBERCOM</h1>
    <p>Cyber Operations Center &mdash; Capture The Flag &amp; Tactical Operations</p>
</div>

<?php if ($flash): ?>
<div class="cy-flash <?= htmlspecialchars($flashType) ?>"><?= htmlspecialchars($flash) ?></div>
<?php endif; ?>

<!-- My Stats -->
<section class="cy-section">
    <h2><i class="fas fa-user-shield"></i> My Stats</h2>
    <div class="cy-stat-grid">
        <div class="cy-stat"><div class="val"><?= (int)$myStats['total_points'] ?></div><div class="lbl">Points</div></div>
        <div class="cy-stat"><div class="val"><?= (int)$myStats['challenges_solved'] ?></div><div class="lbl">Solved</div></div>
        <div class="cy-stat"><div class="val"><?= (int)$myStats['current_streak'] ?></div><div class="lbl">Streak</div></div>
        <div class="cy-stat"><div class="val"><?= (int)$myStats['best_streak'] ?></div><div class="lbl">Best Streak</div></div>
        <div class="cy-stat"><div class="val">#<?= (int)$myStats['rank_position'] ?: '—' ?></div><div class="lbl">Rank</div></div>
    </div>
</section>

<!-- Challenge Board -->
<section class="cy-section">
    <h2><i class="fas fa-flag"></i> Challenge Board</h2>
    <div class="cy-tabs">
        <a href="?cat=" class="cy-tab <?= $activeTab === '' ? 'active' : '' ?>">All</a>
        <?php foreach ($categories as $cat): ?>
        <a href="?cat=<?= $cat ?>" class="cy-tab <?= $activeTab === $cat ? 'active' : '' ?>">
            <i class="fas <?= $catIcons[$cat] ?>"></i> <?= ucfirst($cat) ?>
        </a>
        <?php endforeach; ?>
    </div>
    <div class="cy-grid">
    <?php foreach ($challenges as $ch):
        $isSolved = isset($mySolved[$ch['id']]);
        $diff = htmlspecialchars($ch['difficulty']);
        $dColor = $difficultyColors[$ch['difficulty']] ?? '#64748b';
    ?>
        <div class="cy-card<?= $isSolved ? ' solved' : '' ?>" onclick="openChallenge(<?= (int)$ch['id'] ?>)" data-id="<?= (int)$ch['id'] ?>">
            <?php if ($isSolved): ?><span class="solved-badge"><i class="fas fa-check-circle"></i></span><?php endif; ?>
            <span class="badge" style="background:<?= $dColor ?>"><?= $diff ?></span>
            <h3><?= htmlspecialchars($ch['title']) ?></h3>
            <div class="meta">
                <span><i class="fas <?= $catIcons[$ch['category']] ?? 'fa-cube' ?>"></i> <?= ucfirst(htmlspecialchars($ch['category'])) ?></span>
                <span><i class="fas fa-users"></i> <?= (int)$ch['solved_count'] ?> solved</span>
                <span class="pts"><?= (int)$ch['points'] ?> pts</span>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if (empty($challenges)): ?>
        <p style="color:var(--muted);grid-column:1/-1;text-align:center;padding:40px 0">No challenges available<?= $activeTab ? ' in this category' : '' ?>.</p>
    <?php endif; ?>
    </div>
</section>

<!-- Scoreboard -->
<section class="cy-section">
    <h2><i class="fas fa-trophy"></i> Scoreboard — Top 25</h2>
    <div style="overflow-x:auto">
    <table class="cy-table">
        <thead><tr><th>#</th><th>Operator</th><th>Points</th><th>Solved</th><th>Streak</th></tr></thead>
        <tbody>
        <?php foreach ($leaderboard as $i => $row): ?>
        <tr<?= (int)$row['client_id'] === $clientId ? ' style="background:rgba(59,130,246,.12)"' : '' ?>>
            <td><?= $i + 1 ?></td>
            <td><?= htmlspecialchars($row['operator_name']) ?></td>
            <td style="font-weight:700;color:var(--accent)"><?= number_format((int)$row['total_points']) ?></td>
            <td><?= (int)$row['challenges_solved'] ?></td>
            <td><?= (int)$row['best_streak'] ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($leaderboard)): ?>
        <tr><td colspan="5" style="text-align:center;color:var(--muted);padding:24px">No scores yet. Be the first operator.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
</section>

<!-- Cyber Operations -->
<section class="cy-section">
    <h2>
        <i class="fas fa-satellite-dish"></i> Cyber Operations
        <?php if (hasRank(7) || $isCommander): ?>
        <button class="cy-btn cy-btn-primary cy-btn-sm" style="margin-left:auto" onclick="document.getElementById('createOpForm').classList.toggle('open')">
            <i class="fas fa-plus"></i> New Operation
        </button>
        <?php endif; ?>
    </h2>

    <?php if (hasRank(7) || $isCommander): ?>
    <form method="POST" id="createOpForm" class="create-op-form">
        <input type="hidden" name="action" value="create_operation">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <div class="form-row">
            <div><label>Operation Name</label><input type="text" name="op_name" class="cy-input" required maxlength="120" placeholder="Operation Nightfall"></div>
            <div><label>Type</label>
                <select name="op_type" required>
                    <option value="">Select...</option>
                    <option value="offensive">Offensive</option>
                    <option value="defensive">Defensive</option>
                    <option value="recon">Recon</option>
                    <option value="training">Training</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div><label>Objective</label><input type="text" name="objective" class="cy-input" required maxlength="500" placeholder="Breach target infrastructure..."></div>
        </div>
        <div class="form-row">
            <div><label>Min Team</label><input type="number" name="team_min" class="cy-input" value="2" min="1" max="50"></div>
            <div><label>Max Team</label><input type="number" name="team_max" class="cy-input" value="5" min="1" max="50"></div>
            <div><label>XP Reward</label><input type="number" name="xp_reward" class="cy-input" value="100" min="0" max="5000"></div>
        </div>
        <button type="submit" class="cy-btn cy-btn-success"><i class="fas fa-rocket"></i> Create Operation</button>
    </form>
    <?php endif; ?>

    <?php foreach ($ops as $op):
        $inOp = isset($myOps[$op['id']]);
        $full = (int)$op['member_count'] >= (int)$op['team_size_max'];
    ?>
    <div class="op-card">
        <div>
            <h3><i class="fas <?= $opTypeIcons[$op['op_type']] ?? 'fa-terminal' ?>"></i> <?= htmlspecialchars($op['name']) ?></h3>
            <div class="op-meta">
                <span class="op-status <?= htmlspecialchars($op['status']) ?>"><?= htmlspecialchars($op['status']) ?></span>
                <span><i class="fas fa-user-group"></i> <?= (int)$op['member_count'] ?>/<?= (int)$op['team_size_max'] ?></span>
                <span><i class="fas fa-star"></i> <?= (int)$op['xp_reward'] ?> XP</span>
                <span>Led by <?= htmlspecialchars($op['commander_name']) ?></span>
            </div>
            <p style="color:var(--muted);font-size:.85rem;margin:6px 0 0"><?= htmlspecialchars($op['objective']) ?></p>
        </div>
        <div>
        <?php if ($inOp): ?>
            <span class="cy-btn cy-btn-sm" style="background:var(--border);color:var(--text);cursor:default"><i class="fas fa-check"></i> Joined</span>
        <?php elseif (!$full): ?>
            <form method="POST" style="margin:0">
                <input type="hidden" name="action" value="join_operation">
                <input type="hidden" name="operation_id" value="<?= (int)$op['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <button type="submit" class="cy-btn cy-btn-primary cy-btn-sm"><i class="fas fa-right-to-bracket"></i> Join</button>
            </form>
        <?php else: ?>
            <span class="cy-btn cy-btn-sm" style="background:var(--border);color:var(--muted);cursor:default">Full</span>
        <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($ops)): ?>
    <p style="color:var(--muted);text-align:center;padding:24px 0">No active operations. <?= (hasRank(7) || $isCommander) ? 'Create one above.' : 'Officers (Major+) can create operations.' ?></p>
    <?php endif; ?>
</section>

</div><!-- /cy-wrap -->

<!-- Challenge Detail Modal -->
<div class="cy-modal-bg" id="challengeModal">
    <div class="cy-modal">
        <button class="close" onclick="closeModal()">&times;</button>
        <h2 id="cmTitle"></h2>
        <div id="cmBadges" style="margin-bottom:12px"></div>
        <div class="desc" id="cmDesc"></div>
        <button type="button" class="cy-btn cy-btn-sm" style="background:var(--border);color:var(--warn);margin-bottom:12px" onclick="toggleHint()"><i class="fas fa-lightbulb"></i> Show Hint</button>
        <div class="cy-hint" id="cmHint"></div>
        <div id="cmForm"></div>
    </div>
</div>
</main>

<script>
const challenges = <?= json_encode(array_map(function($c) use ($mySolved, $myAttempts, $difficultyColors) {
    return [
        'id' => (int)$c['id'],
        'title' => $c['title'],
        'category' => $c['category'],
        'difficulty' => $c['difficulty'],
        'description' => $c['description'],
        'hint' => $c['hint'] ?: '',
        'points' => (int)$c['points'],
        'max_attempts' => (int)$c['max_attempts'],
        'solved' => isset($mySolved[$c['id']]),
        'attempts' => $myAttempts[$c['id']] ?? 0,
        'diffColor' => $difficultyColors[$c['difficulty']] ?? '#64748b'
    ];
}, $challenges), JSON_HEX_TAG | JSON_HEX_AMP) ?>;
const csrf = <?= json_encode($csrfToken) ?>;

function openChallenge(id) {
    const c = challenges.find(x => x.id === id);
    if (!c) return;
    document.getElementById('cmTitle').textContent = c.title;
    document.getElementById('cmBadges').innerHTML =
        '<span class="badge" style="background:' + c.diffColor + '">' + esc(c.difficulty) + '</span> ' +
        '<span style="color:var(--accent);font-weight:700;margin-left:8px">' + c.points + ' pts</span>';
    document.getElementById('cmDesc').textContent = c.description;
    const hintEl = document.getElementById('cmHint');
    hintEl.textContent = c.hint || 'No hint available.';
    hintEl.style.display = 'none';

    let formHtml = '';
    if (c.solved) {
        formHtml = '<p style="color:var(--success);font-weight:600"><i class="fas fa-check-circle"></i> Challenge completed!</p>';
    } else if (c.max_attempts > 0 && c.attempts >= c.max_attempts) {
        formHtml = '<p style="color:var(--danger);font-weight:600"><i class="fas fa-ban"></i> Max attempts reached.</p>';
    } else {
        const remaining = c.max_attempts > 0 ? (c.max_attempts - c.attempts) : '∞';
        formHtml = '<form method="POST">' +
            '<input type="hidden" name="action" value="submit_flag">' +
            '<input type="hidden" name="csrf_token" value="' + esc(csrf) + '">' +
            '<input type="hidden" name="challenge_id" value="' + c.id + '">' +
            '<input type="text" name="flag" class="cy-input" placeholder="Enter flag..." required autocomplete="off">' +
            '<div style="display:flex;justify-content:space-between;align-items:center">' +
            '<button type="submit" class="cy-btn cy-btn-primary"><i class="fas fa-paper-plane"></i> Submit Flag</button>' +
            '<span style="color:var(--muted);font-size:.8rem">Attempts left: ' + remaining + '</span></div></form>';
    }
    document.getElementById('cmForm').innerHTML = formHtml;
    document.getElementById('challengeModal').classList.add('open');
}

function closeModal() { document.getElementById('challengeModal').classList.remove('open'); }
function toggleHint() { const h = document.getElementById('cmHint'); h.style.display = h.style.display === 'none' ? 'block' : 'none'; }
function esc(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

document.getElementById('challengeModal').addEventListener('click', function(e) { if (e.target === this) closeModal(); });
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeModal(); });
</script>
<?php require_once __DIR__ . '/includes/site-footer.inc.php'; ?>
