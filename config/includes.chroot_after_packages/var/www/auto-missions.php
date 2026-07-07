<?php
require_once __DIR__ . '/includes/db-config.inc.php';
$db = getSharedDB();
require_once __DIR__ . '/includes/auth-gate.inc.php';
require_once __DIR__ . '/includes/rank-guard.inc.php';
requireRank(1);

if (empty($_SESSION['csrf_automission'])) $_SESSION['csrf_automission'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf_automission'];
$error = ''; $success = '';

// POST handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, $_POST['csrf'] ?? '')) { $error = 'Invalid security token.'; }
    else {
        $action = $_POST['action'] ?? '';

        if ($action === 'accept_mission') {
            $missionId = (int)($_POST['mission_id'] ?? 0);
            $m = $db->prepare("SELECT * FROM auto_missions WHERE id=? AND status='active'");
            $m->execute([$missionId]);
            $mission = $m->fetch(PDO::FETCH_ASSOC);
            if (!$mission) { $error = 'Mission not found or inactive.'; }
            elseif ($mission['required_rank_tier'] > $userRankTier) { $error = 'Insufficient rank for this mission.'; }
            else {
                if ($mission['required_mos']) {
                    $hasMos = $db->prepare("SELECT 1 FROM mos_assignments WHERE client_id=? AND mos_code=? AND status='active'");
                    $hasMos->execute([$clientId, $mission['required_mos']]);
                    if (!$hasMos->fetch()) { $error = 'You lack the required MOS for this mission.'; }
                }
                if (!$error) {
                    $dup = $db->prepare("SELECT 1 FROM mission_assignments WHERE mission_id=? AND client_id=? AND status IN ('assigned','in_progress')");
                    $dup->execute([$missionId, $clientId]);
                    if ($dup->fetch()) { $error = 'Already assigned to this mission.'; }
                }
                if (!$error && $mission['max_assignees']) {
                    $cnt = $db->prepare("SELECT COUNT(*) FROM mission_assignments WHERE mission_id=? AND status IN ('assigned','in_progress')");
                    $cnt->execute([$missionId]);
                    if ($cnt->fetchColumn() >= $mission['max_assignees']) { $error = 'Mission is full.'; }
                }
                if (!$error) {
                    $ins = $db->prepare("INSERT INTO mission_assignments (mission_id, client_id, status, assigned_at) VALUES (?,?,'assigned',NOW())");
                    $ins->execute([$missionId, $clientId]);
                    $db->prepare("INSERT INTO auto_mission_log (mission_id, client_id, action, detail, logged_at) VALUES (?,?,'accepted','Mission accepted',NOW())")->execute([$missionId, $clientId]);
                    $success = 'Mission accepted!';
                }
            }
        } elseif ($action === 'complete_mission') {
            $assignId = (int)($_POST['assign_id'] ?? 0);
            $a = $db->prepare("SELECT ma.*, am.xp_reward, am.credit_reward, am.title FROM mission_assignments ma JOIN auto_missions am ON am.id=ma.mission_id WHERE ma.id=? AND ma.client_id=? AND ma.status IN ('assigned','in_progress')");
            $a->execute([$assignId, $clientId]);
            $assign = $a->fetch(PDO::FETCH_ASSOC);
            if (!$assign) { $error = 'Assignment not found.'; }
            else {
                $db->prepare("UPDATE mission_assignments SET status='completed', completed_at=NOW() WHERE id=?")->execute([$assignId]);
                if ($assign['xp_reward'] > 0) { awardXP($db, $clientId, 'mission_complete', (int)$assign['xp_reward']); }
                if ($assign['credit_reward'] > 0) {
                    $db->prepare("UPDATE client_credits SET balance = balance + ? WHERE client_id=?")->execute([$assign['credit_reward'], $clientId]);
                }
                $db->prepare("INSERT INTO auto_mission_log (mission_id, client_id, action, detail, logged_at) VALUES (?,?,'completed',?,NOW())")->execute([$assign['mission_id'], $clientId, 'Completed: ' . $assign['title']]);
                $remaining = $db->prepare("SELECT COUNT(*) FROM mission_assignments WHERE mission_id=? AND status IN ('assigned','in_progress')");
                $remaining->execute([$assign['mission_id']]);
                if ($remaining->fetchColumn() == 0) {
                    $db->prepare("UPDATE auto_missions SET status='completed' WHERE id=? AND status='active'")->execute([$assign['mission_id']]);
                }
                $success = 'Mission complete! +' . (int)$assign['xp_reward'] . ' XP' . ($assign['credit_reward'] > 0 ? ', +$' . number_format($assign['credit_reward'], 2) . ' credits' : '');
            }
        } elseif ($action === 'abandon_mission') {
            $assignId = (int)($_POST['assign_id'] ?? 0);
            $a = $db->prepare("SELECT ma.*, am.title FROM mission_assignments ma JOIN auto_missions am ON am.id=ma.mission_id WHERE ma.id=? AND ma.client_id=? AND ma.status IN ('assigned','in_progress')");
            $a->execute([$assignId, $clientId]);
            $assign = $a->fetch(PDO::FETCH_ASSOC);
            if (!$assign) { $error = 'Assignment not found.'; }
            else {
                $db->prepare("UPDATE mission_assignments SET status='abandoned' WHERE id=?")->execute([$assignId]);
                $db->prepare("INSERT INTO auto_mission_log (mission_id, client_id, action, detail, logged_at) VALUES (?,?,'abandoned',?,NOW())")->execute([$assign['mission_id'], $clientId, 'Abandoned: ' . $assign['title']]);
                $success = 'Mission abandoned.';
            }
        } elseif ($action === 'generate_mission' && $userRankTier >= 7) {
            $tplId = (int)($_POST['template_id'] ?? 0);
            $tpl = $db->prepare("SELECT * FROM auto_mission_templates WHERE id=? AND is_active=1");
            $tpl->execute([$tplId]);
            $template = $tpl->fetch(PDO::FETCH_ASSOC);
            if (!$template) { $error = 'Template not found.'; }
            else {
                $code = strtoupper($template['template_code']) . '-' . date('Ymd') . '-' . bin2hex(random_bytes(3));
                $freq = $template['frequency'];
                $hours = $freq === 'daily' ? 24 : ($freq === 'weekly' ? 168 : 72);
                $ins = $db->prepare("INSERT INTO auto_missions (mission_code, title, description, mission_type, objective_type, target_metric, target_value, xp_reward, credit_reward, required_rank_tier, status, generated_by, generated_reason, starts_at, expires_at, created_at) VALUES (?,?,?,?,?,NULL,1,?,?,1,'active','manual',?,NOW(),DATE_ADD(NOW(), INTERVAL ? HOUR),NOW())");
                $ins->execute([$code, $template['title_template'], $template['description_template'], $freq === 'daily' ? 'daily' : ($freq === 'weekly' ? 'weekly' : 'special'), $template['objective_type'], (int)$template['base_xp'], $template['base_credits'] ?? 0, 'Generated by officer from template', $hours]);
                $newId = $db->lastInsertId();
                $db->prepare("INSERT INTO auto_mission_log (mission_id, client_id, action, detail, logged_at) VALUES (?,?,'generated',?,NOW())")->execute([$newId, $clientId, 'From template: ' . $template['template_code']]);
                $success = 'Mission generated: ' . htmlspecialchars($template['title_template']);
            }
        }
    }
    $_SESSION['csrf_automission'] = bin2hex(random_bytes(32));
    $csrf = $_SESSION['csrf_automission'];
}

// Fetch data
$availQ = $db->prepare("SELECT am.*, (SELECT COUNT(*) FROM mission_assignments WHERE mission_id=am.id AND status IN ('assigned','in_progress')) as assignee_count FROM auto_missions am WHERE am.status='active' AND am.required_rank_tier <= ? AND (am.expires_at IS NULL OR am.expires_at > NOW()) ORDER BY am.mission_type='special' DESC, am.xp_reward DESC");
$availQ->execute([$userRankTier]);
$available = $availQ->fetchAll(PDO::FETCH_ASSOC);

$myQ = $db->prepare("SELECT ma.*, am.title, am.mission_type, am.objective_type, am.xp_reward, am.credit_reward, am.expires_at FROM mission_assignments ma JOIN auto_missions am ON am.id=ma.mission_id WHERE ma.client_id=? AND ma.status IN ('assigned','in_progress') ORDER BY ma.assigned_at DESC");
$myQ->execute([$clientId]);
$myMissions = $myQ->fetchAll(PDO::FETCH_ASSOC);

$logQ = $db->prepare("SELECT l.*, am.title as mission_title FROM auto_mission_log l JOIN auto_missions am ON am.id=l.mission_id ORDER BY l.logged_at DESC LIMIT 30");
$logQ->execute();
$logs = $logQ->fetchAll(PDO::FETCH_ASSOC);

$statsQ = $db->query("SELECT COUNT(*) as total, SUM(status='completed') as done, SUM(mission_type='daily') as daily, SUM(mission_type='weekly') as weekly, SUM(mission_type='special') as special, SUM(mission_type='dynamic') as dynamic FROM auto_missions");
$stats = $statsQ->fetch(PDO::FETCH_ASSOC);

$templates = [];
if ($userRankTier >= 7) {
    $tQ = $db->query("SELECT * FROM auto_mission_templates WHERE is_active=1 ORDER BY frequency, title_template");
    $templates = $tQ->fetchAll(PDO::FETCH_ASSOC);
}

$typeColors = ['daily'=>'#3b82f6','weekly'=>'#8b5cf6','special'=>'#f59e0b','dynamic'=>'#10b981'];
$objIcons = ['build'=>'fa-hammer','patrol'=>'fa-shield-alt','research'=>'fa-flask','train'=>'fa-dumbbell','deploy'=>'fa-rocket','repair'=>'fa-wrench'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Auto-Missions — GoSiteMe Command</title>
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{background:#0f172a;color:#e2e8f0;font-family:'Segoe UI',system-ui,sans-serif;min-height:100vh}
.wrap{max-width:1200px;margin:0 auto;padding:24px 16px}
h1{font-size:1.8rem;font-weight:700;margin-bottom:4px}
.subtitle{color:#94a3b8;font-size:.95rem;margin-bottom:24px}
.tabs{display:flex;gap:4px;margin-bottom:20px;flex-wrap:wrap}
.tab{padding:10px 20px;background:#1e293b;border:1px solid #334155;border-radius:8px 8px 0 0;cursor:pointer;color:#94a3b8;font-weight:600;transition:.2s}
.tab.active,.tab:hover{background:#334155;color:#e2e8f0;border-color:#3b82f6}
.panel{display:none} .panel.active{display:block}
.alert{padding:12px 16px;border-radius:8px;margin-bottom:16px;font-weight:500}
.alert-err{background:#7f1d1d;border:1px solid #991b1b;color:#fca5a5}
.alert-ok{background:#14532d;border:1px solid #166534;color:#86efac}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:16px}
.card{background:#1e293b;border:1px solid #334155;border-radius:12px;padding:20px;transition:.2s}
.card:hover{border-color:#3b82f6;transform:translateY(-2px)}
.card-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px}
.card-title{font-size:1.1rem;font-weight:700}
.badge{padding:3px 10px;border-radius:20px;font-size:.75rem;font-weight:700;text-transform:uppercase;color:#fff}
.obj-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;background:#334155;margin-right:12px;flex-shrink:0}
.card-body{color:#cbd5e1;font-size:.9rem;margin-bottom:12px;line-height:1.5}
.reward-row{display:flex;gap:16px;margin-bottom:12px;flex-wrap:wrap}
.reward{display:flex;align-items:center;gap:6px;font-weight:600;font-size:.9rem}
.reward .xp{color:#facc15} .reward .cr{color:#34d399}
.meta{display:flex;gap:12px;font-size:.8rem;color:#64748b;margin-bottom:14px;flex-wrap:wrap}
.btn{padding:8px 18px;border:none;border-radius:8px;font-weight:600;cursor:pointer;font-size:.85rem;transition:.2s}
.btn-primary{background:#3b82f6;color:#fff} .btn-primary:hover{background:#2563eb}
.btn-danger{background:#dc2626;color:#fff} .btn-danger:hover{background:#b91c1c}
.btn-success{background:#16a34a;color:#fff} .btn-success:hover{background:#15803d}
.btn-gold{background:#d97706;color:#fff} .btn-gold:hover{background:#b45309}
.btn-sm{padding:6px 12px;font-size:.8rem}
.stat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;margin-bottom:24px}
.stat-card{background:#1e293b;border:1px solid #334155;border-radius:10px;padding:16px;text-align:center}
.stat-val{font-size:1.6rem;font-weight:800;color:#3b82f6}
.stat-lbl{font-size:.8rem;color:#94a3b8;margin-top:4px}
.tbl{width:100%;border-collapse:collapse;font-size:.85rem}
.tbl th{background:#334155;padding:10px 12px;text-align:left;font-weight:600;color:#e2e8f0}
.tbl td{padding:8px 12px;border-bottom:1px solid #1e293b;color:#cbd5e1}
.tbl tr:hover td{background:#1e293b}
.action-badge{display:inline-block;padding:2px 8px;border-radius:4px;font-size:.75rem;font-weight:600}
.ab-accepted{background:#1e3a5f;color:#60a5fa} .ab-completed{background:#14532d;color:#86efac}
.ab-abandoned{background:#7f1d1d;color:#fca5a5} .ab-generated{background:#4a3728;color:#fbbf24}
.time-left{font-size:.8rem;color:#f59e0b}
.empty{text-align:center;padding:40px;color:#64748b;font-size:.95rem}
.tpl-row{display:flex;align-items:center;justify-content:space-between;background:#1e293b;border:1px solid #334155;border-radius:10px;padding:14px 18px;margin-bottom:8px}
.tpl-info{flex:1}
.tpl-name{font-weight:700;font-size:1rem} .tpl-meta{font-size:.8rem;color:#94a3b8;margin-top:2px}
@media(max-width:640px){.grid{grid-template-columns:1fr}.stat-grid{grid-template-columns:repeat(2,1fr)}}
</style>
</head>
<body>
<div class="wrap">
    <h1><i class="fas fa-crosshairs" style="color:#3b82f6;margin-right:8px"></i>Auto-Missions</h1>
    <p class="subtitle">AI-generated missions • Level 4 Military Rank System • <?= htmlspecialchars($userName) ?> — Tier <?= $userRankTier ?></p>

    <?php if ($error): ?><div class="alert alert-err"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-ok"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div><?php endif; ?>

    <!-- Stats -->
    <div class="stat-grid">
        <div class="stat-card"><div class="stat-val"><?= (int)$stats['total'] ?></div><div class="stat-lbl">Total Missions</div></div>
        <div class="stat-card"><div class="stat-val" style="color:#16a34a"><?= (int)$stats['done'] ?></div><div class="stat-lbl">Completed</div></div>
        <div class="stat-card"><div class="stat-val" style="color:#3b82f6"><?= (int)$stats['daily'] ?></div><div class="stat-lbl">Daily</div></div>
        <div class="stat-card"><div class="stat-val" style="color:#8b5cf6"><?= (int)$stats['weekly'] ?></div><div class="stat-lbl">Weekly</div></div>
        <div class="stat-card"><div class="stat-val" style="color:#f59e0b"><?= (int)$stats['special'] ?></div><div class="stat-lbl">Special</div></div>
        <div class="stat-card"><div class="stat-val" style="color:#10b981"><?= (int)$stats['dynamic'] ?></div><div class="stat-lbl">Dynamic</div></div>
    </div>

    <!-- Tabs -->
    <div class="tabs">
        <div class="tab active" onclick="showTab('board')"><i class="fas fa-satellite-dish"></i> Mission Board</div>
        <div class="tab" onclick="showTab('active')"><i class="fas fa-running"></i> My Missions (<?= count($myMissions) ?>)</div>
        <?php if ($userRankTier >= 7): ?><div class="tab" onclick="showTab('templates')"><i class="fas fa-drafting-compass"></i> Templates</div><?php endif; ?>
        <div class="tab" onclick="showTab('log')"><i class="fas fa-scroll"></i> Mission Log</div>
    </div>

    <!-- Mission Board -->
    <div class="panel active" id="panel-board">
        <?php if (empty($available)): ?>
            <div class="empty"><i class="fas fa-satellite-dish" style="font-size:2rem;margin-bottom:12px;display:block"></i>No missions available at your rank right now.</div>
        <?php else: ?>
        <div class="grid">
        <?php foreach ($available as $m):
            $tc = $typeColors[$m['mission_type']] ?? '#3b82f6';
            $oi = $objIcons[$m['objective_type']] ?? 'fa-bullseye';
            $full = $m['max_assignees'] && $m['assignee_count'] >= $m['max_assignees'];
            $timeLeft = $m['expires_at'] ? max(0, strtotime($m['expires_at']) - time()) : null;
            $alreadyAssigned = false;
            foreach ($myMissions as $mm) { if ($mm['mission_id'] == $m['id']) { $alreadyAssigned = true; break; } }
        ?>
        <div class="card">
            <div class="card-head">
                <div style="display:flex;align-items:center">
                    <div class="obj-icon"><i class="fas <?= $oi ?>"></i></div>
                    <div class="card-title"><?= htmlspecialchars($m['title']) ?></div>
                </div>
                <span class="badge" style="background:<?= $tc ?>"><?= htmlspecialchars($m['mission_type']) ?></span>
            </div>
            <div class="card-body"><?= htmlspecialchars($m['description'] ?: 'No briefing available.') ?></div>
            <div class="reward-row">
                <div class="reward"><i class="fas fa-star xp"></i> <span class="xp"><?= number_format((int)$m['xp_reward']) ?> XP</span></div>
                <?php if ($m['credit_reward'] > 0): ?><div class="reward"><i class="fas fa-coins cr"></i> <span class="cr">$<?= number_format($m['credit_reward'], 2) ?></span></div><?php endif; ?>
            </div>
            <div class="meta">
                <span><i class="fas fa-users"></i> <?= (int)$m['assignee_count'] ?><?= $m['max_assignees'] ? '/' . (int)$m['max_assignees'] : '' ?></span>
                <span><i class="fas fa-bolt"></i> <?= htmlspecialchars($m['objective_type']) ?></span>
                <?php if ($m['required_mos']): ?><span><i class="fas fa-id-badge"></i> MOS: <?= htmlspecialchars($m['required_mos']) ?></span><?php endif; ?>
                <?php if ($m['generated_by'] === 'ai'): ?><span><i class="fas fa-robot"></i> AI-generated</span><?php endif; ?>
                <?php if ($timeLeft !== null): ?><span class="time-left"><i class="fas fa-clock"></i> <?= gmdate($timeLeft > 86400 ? 'j\d G\h' : 'G\h i\m', $timeLeft) ?> left</span><?php endif; ?>
            </div>
            <?php if ($alreadyAssigned): ?>
                <span style="color:#60a5fa;font-size:.85rem;font-weight:600"><i class="fas fa-check"></i> Already assigned</span>
            <?php elseif ($full): ?>
                <span style="color:#94a3b8;font-size:.85rem;font-weight:600"><i class="fas fa-lock"></i> Full</span>
            <?php else: ?>
                <form method="POST" style="display:inline">
                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="accept_mission">
                    <input type="hidden" name="mission_id" value="<?= (int)$m['id'] ?>">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-hand-paper"></i> Accept Mission</button>
                </form>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- My Active Missions -->
    <div class="panel" id="panel-active">
        <?php if (empty($myMissions)): ?>
            <div class="empty"><i class="fas fa-inbox" style="font-size:2rem;margin-bottom:12px;display:block"></i>No active missions. Visit the Mission Board to enlist.</div>
        <?php else: ?>
        <div class="grid">
        <?php foreach ($myMissions as $a):
            $tc = $typeColors[$a['mission_type']] ?? '#3b82f6';
            $oi = $objIcons[$a['objective_type']] ?? 'fa-bullseye';
        ?>
        <div class="card" style="border-left:3px solid <?= $tc ?>">
            <div class="card-head">
                <div style="display:flex;align-items:center">
                    <div class="obj-icon"><i class="fas <?= $oi ?>"></i></div>
                    <div class="card-title"><?= htmlspecialchars($a['title']) ?></div>
                </div>
                <span class="badge" style="background:<?= $tc ?>"><?= htmlspecialchars($a['mission_type']) ?></span>
            </div>
            <div class="reward-row">
                <div class="reward"><i class="fas fa-star xp"></i> <span class="xp"><?= number_format((int)$a['xp_reward']) ?> XP</span></div>
                <?php if ($a['credit_reward'] > 0): ?><div class="reward"><i class="fas fa-coins cr"></i> <span class="cr">$<?= number_format($a['credit_reward'], 2) ?></span></div><?php endif; ?>
            </div>
            <div class="meta">
                <span><i class="fas fa-clock"></i> Assigned <?= date('M j, g:ia', strtotime($a['assigned_at'])) ?></span>
                <span class="badge" style="background:#334155;font-size:.7rem"><?= htmlspecialchars($a['status']) ?></span>
            </div>
            <div style="display:flex;gap:8px;margin-top:8px">
                <form method="POST" style="display:inline"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="complete_mission"><input type="hidden" name="assign_id" value="<?= (int)$a['id'] ?>"><button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Mark mission complete?')"><i class="fas fa-flag-checkered"></i> Complete</button></form>
                <form method="POST" style="display:inline"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="abandon_mission"><input type="hidden" name="assign_id" value="<?= (int)$a['id'] ?>"><button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Abandon this mission?')"><i class="fas fa-door-open"></i> Abandon</button></form>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Templates (Officers Tier 7+) -->
    <?php if ($userRankTier >= 7): ?>
    <div class="panel" id="panel-templates">
        <h2 style="font-size:1.2rem;margin-bottom:16px"><i class="fas fa-drafting-compass" style="color:#f59e0b"></i> Mission Templates — Officer Console</h2>
        <?php if (empty($templates)): ?>
            <div class="empty">No active mission templates configured.</div>
        <?php else: ?>
        <?php foreach ($templates as $t): ?>
        <div class="tpl-row">
            <div class="tpl-info">
                <div class="tpl-name"><?= htmlspecialchars($t['title_template']) ?></div>
                <div class="tpl-meta">
                    <span style="color:<?= $typeColors[$t['frequency']] ?? '#94a3b8' ?>;font-weight:600"><?= htmlspecialchars($t['frequency']) ?></span> •
                    <?= htmlspecialchars($t['objective_type']) ?> •
                    <?= (int)$t['base_xp'] ?> XP<?= $t['base_credits'] > 0 ? ' • $' . number_format($t['base_credits'], 2) : '' ?> •
                    <code style="color:#64748b;font-size:.75rem"><?= htmlspecialchars($t['template_code']) ?></code>
                </div>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                <input type="hidden" name="action" value="generate_mission">
                <input type="hidden" name="template_id" value="<?= (int)$t['id'] ?>">
                <button type="submit" class="btn btn-gold btn-sm" onclick="return confirm('Generate mission from this template?')"><i class="fas fa-magic"></i> Generate</button>
            </form>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Mission Log -->
    <div class="panel" id="panel-log">
        <h2 style="font-size:1.2rem;margin-bottom:16px"><i class="fas fa-scroll" style="color:#3b82f6"></i> Recent Mission Log</h2>
        <?php if (empty($logs)): ?>
            <div class="empty">No mission log entries yet.</div>
        <?php else: ?>
        <div style="overflow-x:auto">
        <table class="tbl">
            <thead><tr><th>Time</th><th>Mission</th><th>Action</th><th>Detail</th><th>Operator</th></tr></thead>
            <tbody>
            <?php foreach ($logs as $l):
                $ac = ['accepted'=>'ab-accepted','completed'=>'ab-completed','abandoned'=>'ab-abandoned','generated'=>'ab-generated'][$l['action']] ?? '';
            ?>
            <tr>
                <td><?= date('M j, g:ia', strtotime($l['logged_at'])) ?></td>
                <td><?= htmlspecialchars($l['mission_title'] ?? '—') ?></td>
                <td><span class="action-badge <?= $ac ?>"><?= htmlspecialchars($l['action']) ?></span></td>
                <td><?= htmlspecialchars($l['detail'] ?: '—') ?></td>
                <td>#<?= (int)$l['client_id'] ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function showTab(name){
    document.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));
    document.querySelectorAll('.panel').forEach(p=>p.classList.remove('active'));
    document.getElementById('panel-'+name).classList.add('active');
    event.currentTarget.classList.add('active');
}
</script>
</body>
</html>
