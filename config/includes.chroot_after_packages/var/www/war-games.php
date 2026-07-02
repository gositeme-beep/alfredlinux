<?php
require_once __DIR__ . '/includes/db-config.inc.php';
$db = getSharedDB();
require_once __DIR__ . '/includes/auth-gate.inc.php';
require_once __DIR__ . '/includes/rank-guard.inc.php';
requireRank(2);

if (empty($_SESSION['csrf_wargames'])) $_SESSION['csrf_wargames'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf_wargames'];
$msg = '';
$msgType = '';

// POST handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && hash_equals($csrf, $_POST['csrf'] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'join_game') {
        $gameId = (int)($_POST['game_id'] ?? 0);
        $teamId = (int)($_POST['team_id'] ?? 0);
        $role = 'soldier';

        $game = $db->prepare("SELECT * FROM war_games WHERE id = ? AND status IN ('upcoming','active')");
        $game->execute([$gameId]);
        $game = $game->fetch(PDO::FETCH_ASSOC);

        if (!$game) {
            $msg = 'Game not found or not joinable.'; $msgType = 'error';
        } elseif ($userRankTier < $game['required_rank_tier']) {
            $msg = 'Your rank is too low for this game.'; $msgType = 'error';
        } else {
            $already = $db->prepare("SELECT id FROM war_game_participants WHERE game_id = ? AND client_id = ?");
            $already->execute([$gameId, $clientId]);
            if ($already->fetch()) {
                $msg = 'You have already joined this game.'; $msgType = 'error';
            } else {
                $teamCount = $db->prepare("SELECT COUNT(*) FROM war_game_participants WHERE game_id = ? AND team_id = ?");
                $teamCount->execute([$gameId, $teamId]);
                if ((int)$teamCount->fetchColumn() >= $game['max_team_size']) {
                    $msg = 'That team is full.'; $msgType = 'error';
                } else {
                    $ins = $db->prepare("INSERT INTO war_game_participants (game_id, team_id, client_id, role, individual_score, xp_awarded, joined_at) VALUES (?, ?, ?, ?, 0, 0, NOW())");
                    $ins->execute([$gameId, $teamId, $clientId, $role]);
                    $msg = 'You have joined the game!'; $msgType = 'success';
                }
            }
        }
    }

    if ($action === 'create_team') {
        $gameId = (int)($_POST['game_id'] ?? 0);
        $teamName = trim($_POST['team_name'] ?? '');

        if ($userRankTier < 4) {
            $msg = 'Only NCO+ (Tier 4+) can create teams.'; $msgType = 'error';
        } elseif ($teamName === '' || mb_strlen($teamName) > 60) {
            $msg = 'Team name is required (max 60 chars).'; $msgType = 'error';
        } else {
            $game = $db->prepare("SELECT id FROM war_games WHERE id = ? AND status IN ('upcoming','active')");
            $game->execute([$gameId]);
            if (!$game->fetch()) {
                $msg = 'Game not found or not joinable.'; $msgType = 'error';
            } else {
                $dup = $db->prepare("SELECT id FROM war_game_teams WHERE game_id = ? AND team_name = ?");
                $dup->execute([$gameId, $teamName]);
                if ($dup->fetch()) {
                    $msg = 'A team with that name already exists.'; $msgType = 'error';
                } else {
                    $ins = $db->prepare("INSERT INTO war_game_teams (game_id, team_name, unit_id, score, result) VALUES (?, ?, NULL, 0, NULL)");
                    $ins->execute([$gameId, $teamName]);
                    $newTeamId = $db->lastInsertId();
                    // Auto-join creator as leader
                    $join = $db->prepare("INSERT INTO war_game_participants (game_id, team_id, client_id, role, individual_score, xp_awarded, joined_at) VALUES (?, ?, ?, 'leader', 0, 0, NOW())");
                    $join->execute([$gameId, $newTeamId, $clientId]);
                    $msg = "Team \"" . htmlspecialchars($teamName, ENT_QUOTES) . "\" created!"; $msgType = 'success';
                }
            }
        }
    }

    $_SESSION['csrf_wargames'] = bin2hex(random_bytes(32));
    $csrf = $_SESSION['csrf_wargames'];
}

// Fetch data
$gameDetail = null;
$detailTeams = [];
$detailRounds = [];
$detailLeaderboard = [];
$viewCode = $_GET['game'] ?? '';

if ($viewCode !== '') {
    $stmt = $db->prepare("SELECT * FROM war_games WHERE game_code = ?");
    $stmt->execute([$viewCode]);
    $gameDetail = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($gameDetail) {
        $gid = $gameDetail['id'];
        $tStmt = $db->prepare("SELECT t.*, (SELECT COUNT(*) FROM war_game_participants p WHERE p.team_id = t.id) AS member_count FROM war_game_teams t WHERE t.game_id = ? ORDER BY t.score DESC");
        $tStmt->execute([$gid]);
        $detailTeams = $tStmt->fetchAll(PDO::FETCH_ASSOC);

        $rStmt = $db->prepare("SELECT * FROM war_game_rounds WHERE game_id = ? ORDER BY round_number ASC");
        $rStmt->execute([$gid]);
        $detailRounds = $rStmt->fetchAll(PDO::FETCH_ASSOC);

        $lStmt = $db->prepare("SELECT p.client_id, p.individual_score, p.xp_awarded, p.role, t.team_name FROM war_game_participants p LEFT JOIN war_game_teams t ON t.id = p.team_id WHERE p.game_id = ? ORDER BY p.individual_score DESC LIMIT 20");
        $lStmt->execute([$gid]);
        $detailLeaderboard = $lStmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Active & upcoming games
$activeGames = $db->query("SELECT g.*, (SELECT COUNT(*) FROM war_game_participants p WHERE p.game_id = g.id) AS player_count FROM war_games g WHERE g.status IN ('upcoming','active') ORDER BY g.starts_at ASC")->fetchAll(PDO::FETCH_ASSOC);

// My games
$myGames = $db->prepare("SELECT g.*, p.role, p.individual_score, p.xp_awarded, t.team_name FROM war_game_participants p JOIN war_games g ON g.id = p.game_id LEFT JOIN war_game_teams t ON t.id = p.team_id WHERE p.client_id = ? ORDER BY g.starts_at DESC");
$myGames->execute([$clientId]);
$myGames = $myGames->fetchAll(PDO::FETCH_ASSOC);

// Completed history
$history = $db->query("SELECT g.*, (SELECT COUNT(*) FROM war_game_participants p WHERE p.game_id = g.id) AS player_count FROM war_games g WHERE g.status = 'completed' ORDER BY g.ends_at DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);

$typeColors = ['capture_the_flag' => '#DC2626', 'siege' => '#D97706', 'code_challenge' => '#2563EB', 'strategy' => '#7C3AED'];
$typeIcons = ['capture_the_flag' => 'fa-flag', 'siege' => 'fa-shield-halved', 'code_challenge' => 'fa-code', 'strategy' => 'fa-chess'];
$typeLabels = ['capture_the_flag' => 'CTF', 'siege' => 'Siege', 'code_challenge' => 'Code', 'strategy' => 'Strategy'];

function statusBadge(string $s): string {
    $m = ['upcoming' => ['#3b82f6','UPCOMING'], 'active' => ['#22c55e','ACTIVE'], 'completed' => ['#6b7280','COMPLETED'], 'cancelled' => ['#ef4444','CANCELLED']];
    $c = $m[$s] ?? ['#6b7280', strtoupper($s)];
    return '<span style="background:'.$c[0].';color:#fff;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700;letter-spacing:.5px">'.$c[1].'</span>';
}
function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>War Games — GoSiteMe Military Rank System</title>
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{background:#0f172a;color:#e2e8f0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;min-height:100vh}
a{color:#3b82f6;text-decoration:none}a:hover{text-decoration:underline}
.wrap{max-width:1200px;margin:0 auto;padding:20px}
.hdr{display:flex;align-items:center;gap:16px;margin-bottom:28px;flex-wrap:wrap}
.hdr h1{font-size:28px;font-weight:800;background:linear-gradient(135deg,#3b82f6,#7c3aed);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.hdr .rank-badge{background:#334155;padding:4px 12px;border-radius:6px;font-size:13px;color:#94a3b8}
.tabs{display:flex;gap:4px;margin-bottom:24px;flex-wrap:wrap}
.tab{padding:10px 20px;background:#1e293b;border:1px solid #334155;border-radius:8px 8px 0 0;cursor:pointer;font-size:14px;font-weight:600;color:#94a3b8;transition:.2s}
.tab:hover,.tab.active{background:#334155;color:#e2e8f0;border-color:#3b82f6}
.panel{display:none}.panel.active{display:block}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:16px}
.card{background:#1e293b;border:1px solid #334155;border-radius:12px;padding:20px;transition:.2s}
.card:hover{border-color:#3b82f6;transform:translateY(-2px)}
.card-top{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px}
.card h3{font-size:17px;font-weight:700;margin-bottom:4px}
.card .meta{font-size:12px;color:#94a3b8;display:flex;gap:12px;flex-wrap:wrap;margin:8px 0}
.card .meta span{display:flex;align-items:center;gap:4px}
.type-pill{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;color:#fff}
.xp-badge{background:#1a1a2e;border:1px solid #334155;border-radius:8px;padding:8px 12px;margin-top:10px;font-size:13px;display:flex;gap:16px}
.xp-badge span{display:flex;align-items:center;gap:4px}
.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;transition:.2s}
.btn-primary{background:#3b82f6;color:#fff}.btn-primary:hover{background:#2563eb}
.btn-success{background:#22c55e;color:#fff}.btn-success:hover{background:#16a34a}
.btn-sm{padding:5px 12px;font-size:12px}
.msg{padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:14px;font-weight:500}
.msg-success{background:#064e3b;border:1px solid #22c55e;color:#a7f3d0}
.msg-error{background:#450a0a;border:1px solid #dc2626;color:#fca5a5}
.detail-hdr{margin-bottom:24px}
.detail-hdr h2{font-size:24px;font-weight:800;margin-bottom:8px}
.detail-hdr .desc{color:#94a3b8;font-size:14px;line-height:1.6}
.detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px}
@media(max-width:768px){.detail-grid{grid-template-columns:1fr}}
.section{background:#1e293b;border:1px solid #334155;border-radius:12px;padding:20px;margin-bottom:20px}
.section h3{font-size:16px;font-weight:700;margin-bottom:14px;display:flex;align-items:center;gap:8px}
table{width:100%;border-collapse:collapse;font-size:13px}
th{text-align:left;padding:8px 10px;color:#94a3b8;border-bottom:1px solid #334155;font-weight:600;font-size:12px;text-transform:uppercase;letter-spacing:.5px}
td{padding:8px 10px;border-bottom:1px solid #1e293b}
tr:hover td{background:#334155}
.score-bar{height:6px;background:#334155;border-radius:3px;overflow:hidden;min-width:80px}
.score-fill{height:100%;border-radius:3px;background:#3b82f6}
.round-card{background:#0f172a;border:1px solid #334155;border-radius:8px;padding:14px;margin-bottom:10px}
.round-card .rn{font-weight:700;font-size:14px;margin-bottom:4px}
.round-card .rc{font-size:12px;color:#94a3b8}
.empty{text-align:center;padding:40px;color:#64748b;font-size:14px}
.modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:1000;align-items:center;justify-content:center}
.modal-bg.show{display:flex}
.modal{background:#1e293b;border:1px solid #334155;border-radius:12px;padding:24px;max-width:440px;width:90%}
.modal h3{margin-bottom:16px;font-size:18px}
.modal label{display:block;font-size:13px;color:#94a3b8;margin-bottom:4px}
.modal input,.modal select{width:100%;padding:8px 12px;background:#0f172a;border:1px solid #334155;border-radius:6px;color:#e2e8f0;font-size:14px;margin-bottom:12px}
.back-link{display:inline-flex;align-items:center;gap:6px;color:#94a3b8;font-size:13px;margin-bottom:16px}
.back-link:hover{color:#e2e8f0}
</style>
</head>
<body>
<div class="wrap">
<div class="hdr">
    <h1><i class="fas fa-crosshairs"></i> War Games</h1>
    <span class="rank-badge"><i class="fas fa-chevron-up"></i> <?=e($userRankCode)?> — <?=e($userName)?></span>
</div>

<?php if ($msg): ?>
<div class="msg msg-<?=e($msgType)?>"><?=$msg?></div>
<?php endif; ?>

<?php if ($gameDetail): ?>
<!-- GAME DETAIL VIEW -->
<a href="war-games.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to War Games</a>

<div class="detail-hdr">
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:8px">
        <h2><?=e($gameDetail['title'])?></h2>
        <?=statusBadge($gameDetail['status'])?>
        <span class="type-pill" style="background:<?=$typeColors[$gameDetail['game_type']] ?? '#6b7280'?>">
            <i class="fas <?=$typeIcons[$gameDetail['game_type']] ?? 'fa-gamepad'?>"></i>
            <?=$typeLabels[$gameDetail['game_type']] ?? e($gameDetail['game_type'])?>
        </span>
    </div>
    <p class="desc"><?=e($gameDetail['description'])?></p>
    <div class="meta" style="font-size:12px;color:#94a3b8;display:flex;gap:16px;margin-top:8px;flex-wrap:wrap">
        <span><i class="fas fa-calendar"></i> <?=date('M j, Y H:i', strtotime($gameDetail['starts_at']))?></span>
        <?php if ($gameDetail['ends_at']): ?>
        <span><i class="fas fa-flag-checkered"></i> <?=date('M j, Y H:i', strtotime($gameDetail['ends_at']))?></span>
        <?php endif; ?>
        <span><i class="fas fa-users"></i> <?=$gameDetail['min_team_size']?>–<?=$gameDetail['max_team_size']?> per team</span>
        <span><i class="fas fa-shield-halved"></i> Rank Tier <?=$gameDetail['required_rank_tier']?>+</span>
    </div>
</div>

<div class="detail-grid">
    <!-- Teams -->
    <div class="section">
        <h3><i class="fas fa-users-rectangle"></i> Teams</h3>
        <?php if (empty($detailTeams)): ?>
            <p class="empty">No teams yet.</p>
        <?php else: ?>
        <table>
            <tr><th>Team</th><th>Members</th><th>Score</th><th>Result</th></tr>
            <?php $maxScore = max(array_column($detailTeams, 'score') ?: [1]); ?>
            <?php foreach ($detailTeams as $t): ?>
            <tr>
                <td style="font-weight:600"><?=e($t['team_name'])?></td>
                <td><?=$t['member_count']?>/<?=$gameDetail['max_team_size']?></td>
                <td>
                    <div style="display:flex;align-items:center;gap:8px">
                        <span><?=$t['score']?></span>
                        <div class="score-bar"><div class="score-fill" style="width:<?=$maxScore > 0 ? round($t['score']/$maxScore*100) : 0?>%"></div></div>
                    </div>
                </td>
                <td><?=$t['result'] ? e(ucfirst($t['result'])) : '—'?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>

        <?php if (in_array($gameDetail['status'], ['upcoming','active'])): ?>
        <div style="margin-top:14px;display:flex;gap:8px;flex-wrap:wrap">
            <?php if (!empty($detailTeams)):
                $alreadyIn = $db->prepare("SELECT id FROM war_game_participants WHERE game_id = ? AND client_id = ?");
                $alreadyIn->execute([$gameDetail['id'], $clientId]);
                if (!$alreadyIn->fetch()):
            ?>
            <button class="btn btn-primary btn-sm" onclick="document.getElementById('joinModal').classList.add('show')"><i class="fas fa-sign-in-alt"></i> Join a Team</button>
            <?php endif; endif; ?>
            <?php if ($userRankTier >= 4): ?>
            <button class="btn btn-success btn-sm" onclick="document.getElementById('teamModal').classList.add('show')"><i class="fas fa-plus"></i> Create Team</button>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Leaderboard -->
    <div class="section">
        <h3><i class="fas fa-trophy"></i> Leaderboard</h3>
        <?php if (empty($detailLeaderboard)): ?>
            <p class="empty">No scores yet.</p>
        <?php else: ?>
        <table>
            <tr><th>#</th><th>Player</th><th>Team</th><th>Score</th><th>XP</th></tr>
            <?php foreach ($detailLeaderboard as $i => $lb): ?>
            <tr>
                <td style="font-weight:700;color:<?=$i===0?'#facc15':($i===1?'#94a3b8':($i===2?'#b45309':'#64748b'))?>"><?=$i+1?></td>
                <td>#<?=$lb['client_id']?> <span style="color:#64748b;font-size:11px">(<?=e($lb['role'])?>)</span></td>
                <td><?=e($lb['team_name'] ?? '—')?></td>
                <td style="font-weight:600"><?=$lb['individual_score']?></td>
                <td style="color:#22c55e">+<?=$lb['xp_awarded']?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Rounds -->
<div class="section">
    <h3><i class="fas fa-layer-group"></i> Rounds</h3>
    <?php if (empty($detailRounds)): ?>
        <p class="empty">No rounds configured yet.</p>
    <?php else: ?>
        <?php foreach ($detailRounds as $r): ?>
        <div class="round-card">
            <div class="rn">Round <?=$r['round_number']?> — <?=e(ucfirst(str_replace('_', ' ', $r['challenge_type'])))?> <?=statusBadge($r['status'])?></div>
            <div class="rc">
                <?php if ($r['started_at']): ?>Started: <?=date('M j H:i', strtotime($r['started_at']))?><?php endif; ?>
                <?php if ($r['ended_at']): ?> — Ended: <?=date('M j H:i', strtotime($r['ended_at']))?><?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Join Modal -->
<div class="modal-bg" id="joinModal">
<div class="modal">
    <h3><i class="fas fa-sign-in-alt"></i> Join a Team</h3>
    <form method="POST">
        <input type="hidden" name="csrf" value="<?=e($csrf)?>">
        <input type="hidden" name="action" value="join_game">
        <input type="hidden" name="game_id" value="<?=$gameDetail['id']?>">
        <label>Select Team</label>
        <select name="team_id" required>
            <option value="">— Choose —</option>
            <?php foreach ($detailTeams as $t): ?>
            <option value="<?=$t['id']?>"><?=e($t['team_name'])?> (<?=$t['member_count']?>/<?=$gameDetail['max_team_size']?>)</option>
            <?php endforeach; ?>
        </select>
        <div style="display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn" style="background:#334155;color:#e2e8f0" onclick="document.getElementById('joinModal').classList.remove('show')">Cancel</button>
            <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Join</button>
        </div>
    </form>
</div>
</div>

<!-- Create Team Modal -->
<div class="modal-bg" id="teamModal">
<div class="modal">
    <h3><i class="fas fa-plus"></i> Create Team</h3>
    <form method="POST">
        <input type="hidden" name="csrf" value="<?=e($csrf)?>">
        <input type="hidden" name="action" value="create_team">
        <input type="hidden" name="game_id" value="<?=$gameDetail['id']?>">
        <label>Team Name</label>
        <input type="text" name="team_name" maxlength="60" required placeholder="e.g. Alpha Battalion">
        <div style="display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn" style="background:#334155;color:#e2e8f0" onclick="document.getElementById('teamModal').classList.remove('show')">Cancel</button>
            <button type="submit" class="btn btn-success"><i class="fas fa-plus"></i> Create</button>
        </div>
    </form>
</div>
</div>

<?php else: ?>
<!-- MAIN LISTING VIEW -->
<div class="tabs">
    <div class="tab active" onclick="showPanel('active')"><i class="fas fa-bolt"></i> Active & Upcoming</div>
    <div class="tab" onclick="showPanel('mygames')"><i class="fas fa-user-shield"></i> My Games</div>
    <div class="tab" onclick="showPanel('history')"><i class="fas fa-scroll"></i> Results History</div>
</div>

<!-- Active & Upcoming -->
<div class="panel active" id="panel-active">
<?php if (empty($activeGames)): ?>
    <div class="empty"><i class="fas fa-radar" style="font-size:32px;margin-bottom:12px;display:block;color:#334155"></i>No active or upcoming war games. Stand by, soldier.</div>
<?php else: ?>
<div class="grid">
<?php foreach ($activeGames as $g):
    $tc = $typeColors[$g['game_type']] ?? '#6b7280';
    $ti = $typeIcons[$g['game_type']] ?? 'fa-gamepad';
    $tl = $typeLabels[$g['game_type']] ?? $g['game_type'];
?>
<div class="card" style="border-left:3px solid <?=$tc?>">
    <div class="card-top">
        <div>
            <h3><?=e($g['title'])?></h3>
            <span class="type-pill" style="background:<?=$tc?>"><i class="fas <?=$ti?>"></i> <?=$tl?></span>
        </div>
        <?=statusBadge($g['status'])?>
    </div>
    <p style="font-size:13px;color:#94a3b8;margin-bottom:8px"><?=e(mb_strimwidth($g['description'], 0, 120, '…'))?></p>
    <div class="meta">
        <span><i class="fas fa-calendar"></i> <?=date('M j, H:i', strtotime($g['starts_at']))?></span>
        <span><i class="fas fa-users"></i> <?=$g['player_count']?> joined</span>
        <span><i class="fas fa-user-group"></i> <?=$g['min_team_size']?>–<?=$g['max_team_size']?>/team</span>
        <span><i class="fas fa-shield-halved"></i> Tier <?=$g['required_rank_tier']?>+</span>
    </div>
    <div class="xp-badge">
        <span style="color:#22c55e"><i class="fas fa-trophy"></i> Win: +<?=$g['xp_reward_win']?> XP</span>
        <span style="color:#eab308"><i class="fas fa-handshake"></i> Draw: +<?=$g['xp_reward_draw']?> XP</span>
        <span style="color:#ef4444"><i class="fas fa-skull"></i> Loss: +<?=$g['xp_reward_loss']?> XP</span>
    </div>
    <div style="margin-top:12px">
        <a href="?game=<?=e($g['game_code'])?>" class="btn btn-primary btn-sm"><i class="fas fa-crosshairs"></i> View Game</a>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>

<!-- My Games -->
<div class="panel" id="panel-mygames">
<?php if (empty($myGames)): ?>
    <div class="empty"><i class="fas fa-user-slash" style="font-size:32px;margin-bottom:12px;display:block;color:#334155"></i>You haven't joined any war games yet.</div>
<?php else: ?>
<div class="grid">
<?php foreach ($myGames as $g):
    $tc = $typeColors[$g['game_type']] ?? '#6b7280';
    $ti = $typeIcons[$g['game_type']] ?? 'fa-gamepad';
    $tl = $typeLabels[$g['game_type']] ?? $g['game_type'];
?>
<div class="card" style="border-left:3px solid <?=$tc?>">
    <div class="card-top">
        <div>
            <h3><?=e($g['title'])?></h3>
            <span class="type-pill" style="background:<?=$tc?>"><i class="fas <?=$ti?>"></i> <?=$tl?></span>
        </div>
        <?=statusBadge($g['status'])?>
    </div>
    <div class="meta">
        <span><i class="fas fa-id-badge"></i> <?=e(ucfirst($g['role']))?></span>
        <span><i class="fas fa-users"></i> <?=e($g['team_name'] ?? 'No team')?></span>
        <span><i class="fas fa-star"></i> Score: <?=$g['individual_score']?></span>
        <span style="color:#22c55e"><i class="fas fa-bolt"></i> +<?=$g['xp_awarded']?> XP</span>
    </div>
    <div style="margin-top:10px">
        <a href="?game=<?=e($g['game_code'])?>" class="btn btn-primary btn-sm"><i class="fas fa-crosshairs"></i> View</a>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>

<!-- History -->
<div class="panel" id="panel-history">
<?php if (empty($history)): ?>
    <div class="empty"><i class="fas fa-clock-rotate-left" style="font-size:32px;margin-bottom:12px;display:block;color:#334155"></i>No completed games yet.</div>
<?php else: ?>
<table>
    <tr><th>Game</th><th>Type</th><th>Players</th><th>XP (W/D/L)</th><th>Ended</th><th></th></tr>
    <?php foreach ($history as $g):
        $tc = $typeColors[$g['game_type']] ?? '#6b7280';
        $tl = $typeLabels[$g['game_type']] ?? $g['game_type'];
    ?>
    <tr>
        <td style="font-weight:600"><?=e($g['title'])?></td>
        <td><span class="type-pill" style="background:<?=$tc?>;font-size:10px"><?=$tl?></span></td>
        <td><?=$g['player_count']?></td>
        <td style="font-size:12px"><?=$g['xp_reward_win']?>/<?=$g['xp_reward_draw']?>/<?=$g['xp_reward_loss']?></td>
        <td style="color:#94a3b8;font-size:12px"><?=$g['ends_at'] ? date('M j, Y', strtotime($g['ends_at'])) : '—'?></td>
        <td><a href="?game=<?=e($g['game_code'])?>" class="btn btn-sm" style="background:#334155;color:#e2e8f0"><i class="fas fa-eye"></i></a></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>
</div>

<?php endif; ?>
</div>

<script>
function showPanel(id){
    document.querySelectorAll('.panel').forEach(p=>p.classList.remove('active'));
    document.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));
    document.getElementById('panel-'+id).classList.add('active');
    event.currentTarget.classList.add('active');
}
document.querySelectorAll('.modal-bg').forEach(m=>{
    m.addEventListener('click',e=>{if(e.target===m)m.classList.remove('show')});
});
</script>
</body>
</html>
