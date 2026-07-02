<?php
/**
 * ═══════════════════════════════════════════
 *  Research & Development Labs — Level 6: Sovereign State
 * ═══════════════════════════════════════════
 */
require_once __DIR__ . '/includes/db-config.inc.php';
$db = getSharedDB();
require_once __DIR__ . '/includes/auth-gate.inc.php';
require_once __DIR__ . '/includes/rank-guard.inc.php';
if (empty($_SESSION['csrf_rd'])) $_SESSION['csrf_rd'] = bin2hex(random_bytes(32));
requireRank(6);

$isCommander = ($clientId === 33);
$isFlag      = ($userRankTier >= 9) || $isCommander;
$isOfficer   = ($userRankTier >= 6) || $isCommander;
$msg = '';
$msgType = '';

// ── Auto-create tables ──
$db->exec("CREATE TABLE IF NOT EXISTS rnd_domains (
    id INT AUTO_INCREMENT PRIMARY KEY,
    domain_name VARCHAR(120) NOT NULL,
    description TEXT DEFAULT NULL,
    lead_researcher_id INT DEFAULT NULL,
    total_projects INT DEFAULT 0,
    total_breakthroughs INT DEFAULT 0,
    budget_allocated DECIMAL(14,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS rnd_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_code VARCHAR(20) NOT NULL,
    domain_id INT NOT NULL,
    project_name VARCHAR(200) NOT NULL,
    description TEXT DEFAULT NULL,
    phase ENUM('theory','prototype','testing','production') DEFAULT 'theory',
    status ENUM('proposed','funded','active','paused','completed','cancelled') DEFAULT 'proposed',
    priority ENUM('low','medium','high','critical') DEFAULT 'medium',
    proposed_by INT DEFAULT NULL,
    approved_by INT DEFAULT NULL,
    budget_cost DECIMAL(14,2) DEFAULT 0.00,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    estimated_weeks INT DEFAULT 12
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS rnd_personnel (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    client_id INT NOT NULL,
    role ENUM('lead','researcher','assistant','consultant') DEFAULT 'researcher',
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    hours_logged INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS rnd_breakthroughs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    breakthrough_name VARCHAR(200) NOT NULL,
    breakthrough_rank ENUM('incremental','significant','major','revolutionary','paradigm_shift') DEFAULT 'incremental',
    description TEXT NOT NULL,
    impact TEXT DEFAULT NULL,
    xp_reward INT DEFAULT 1000,
    unlocks TEXT DEFAULT NULL,
    achieved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verified_by INT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS rnd_phases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    phase_name VARCHAR(80) NOT NULL,
    phase_order INT DEFAULT 1,
    status ENUM('pending','active','complete') DEFAULT 'pending',
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    deliverables TEXT DEFAULT NULL,
    notes TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS rnd_funding (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    amount DECIMAL(14,2) NOT NULL,
    source ENUM('treasury','department','grant','commander') DEFAULT 'treasury',
    funded_by INT DEFAULT NULL,
    funded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Seed domains ──
$dc = $db->query("SELECT COUNT(*) FROM rnd_domains")->fetchColumn();
if ($dc == 0) {
    $domains = [
        ['Weapons & Defense Systems', 'Kinetic and energy weapons, shield tech, armor, and countermeasures'],
        ['Cyber & Information Warfare', 'Offensive/defensive cyber tools, AI-driven intelligence, encryption'],
        ['Space & Orbital Systems', 'Propulsion, satellite tech, orbital mechanics, and space habitation'],
        ['Bio & Medical Sciences', 'Field medicine, enhancement programs, disease research, and resilience'],
        ['Energy & Infrastructure', 'Power generation, grid optimization, renewable sources, and efficiency']
    ];
    $ds = $db->prepare("INSERT INTO rnd_domains (domain_name, description) VALUES (?,?)");
    foreach ($domains as $d) $ds->execute($d);
}

$csrf = $_SESSION['csrf_rd'];

// ── POST Handlers ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, $_POST['csrf'] ?? '')) {
        $msg = 'Invalid security token.'; $msgType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'propose_project' && $isOfficer) {
            $domId    = (int)($_POST['domain_id'] ?? 0);
            $name     = trim($_POST['project_name'] ?? '');
            $desc     = trim($_POST['project_desc'] ?? '');
            $priority = $_POST['priority'] ?? 'medium';
            $cost     = (float)($_POST['budget_cost'] ?? 0);
            $weeks    = (int)($_POST['estimated_weeks'] ?? 12);
            $validP = ['low','medium','high','critical'];
            if ($name === '' || !in_array($priority, $validP, true)) {
                $msg = 'Project name and valid priority required.'; $msgType = 'error';
            } else {
                $code = 'RND-' . strtoupper(bin2hex(random_bytes(3)));
                $db->prepare("INSERT INTO rnd_projects (project_code, domain_id, project_name, description, priority, proposed_by, budget_cost, estimated_weeks) VALUES (?,?,?,?,?,?,?,?)")
                   ->execute([$code, $domId, $name, $desc, $priority, $clientId, max(0, $cost), max(1, $weeks)]);
                $db->exec("UPDATE rnd_domains SET total_projects = total_projects + 1 WHERE id = " . (int)$domId);
                $msg = "Project <strong>$code</strong> proposed."; $msgType = 'success';
            }
        } elseif ($action === 'fund_project' && $isFlag) {
            $projId = (int)($_POST['project_id'] ?? 0);
            $amount = (float)($_POST['amount'] ?? 0);
            $source = $_POST['source'] ?? 'treasury';
            $notes  = trim($_POST['fund_notes'] ?? '');
            $validS = ['treasury','department','grant','commander'];
            if ($amount <= 0 || !in_array($source, $validS, true)) {
                $msg = 'Valid amount and source required.'; $msgType = 'error';
            } else {
                $db->prepare("INSERT INTO rnd_funding (project_id, amount, source, funded_by, notes) VALUES (?,?,?,?,?)")
                   ->execute([$projId, $amount, $source, $clientId, $notes]);
                $db->prepare("UPDATE rnd_projects SET status = 'funded', approved_by = ? WHERE id = ? AND status = 'proposed'")
                   ->execute([$clientId, $projId]);
                $proj = $db->prepare("SELECT domain_id FROM rnd_projects WHERE id = ?");
                $proj->execute([$projId]);
                $pRow = $proj->fetch(PDO::FETCH_ASSOC);
                if ($pRow) $db->exec("UPDATE rnd_domains SET budget_allocated = budget_allocated + " . round($amount, 2) . " WHERE id = " . (int)$pRow['domain_id']);
                $msg = 'Project funded: $' . number_format($amount, 2) . '.'; $msgType = 'success';
            }
        } elseif ($action === 'assign_personnel' && $isOfficer) {
            $projId   = (int)($_POST['project_id'] ?? 0);
            $targetId = (int)($_POST['researcher_id'] ?? $clientId);
            $role     = $_POST['researcher_role'] ?? 'researcher';
            $validR = ['lead','researcher','assistant','consultant'];
            if (!in_array($role, $validR, true)) { $msg = 'Invalid role.'; $msgType = 'error'; }
            else {
                $db->prepare("INSERT INTO rnd_personnel (project_id, client_id, role) VALUES (?,?,?)")
                   ->execute([$projId, $targetId, $role]);
                $msg = "Personnel assigned as " . strtoupper($role) . "."; $msgType = 'success';
            }
        } elseif ($action === 'advance_phase' && $isOfficer) {
            $projId = (int)($_POST['project_id'] ?? 0);
            $proj = $db->prepare("SELECT * FROM rnd_projects WHERE id = ? AND status IN ('funded','active')");
            $proj->execute([$projId]);
            $pRow = $proj->fetch(PDO::FETCH_ASSOC);
            if (!$pRow) { $msg = 'Project not found or not active.'; $msgType = 'error'; }
            else {
                $phases = ['theory','prototype','testing','production'];
                $curIdx = array_search($pRow['phase'], $phases);
                if ($curIdx < 3) {
                    $nextPhase = $phases[$curIdx + 1];
                    $db->prepare("UPDATE rnd_projects SET phase = ?, status = 'active', started_at = COALESCE(started_at, NOW()) WHERE id = ?")
                       ->execute([$nextPhase, $projId]);
                    $db->prepare("INSERT INTO rnd_phases (project_id, phase_name, phase_order, status, started_at) VALUES (?,?,?,'active',NOW())")
                       ->execute([$projId, ucfirst($nextPhase), $curIdx + 2]);
                    awardXP($clientId, 'rnd_phase_advance', ['phase' => $nextPhase]);
                    $msg = "Advanced to <strong>" . strtoupper($nextPhase) . "</strong>."; $msgType = 'success';
                } else {
                    $db->prepare("UPDATE rnd_projects SET status = 'completed', completed_at = NOW() WHERE id = ?")
                       ->execute([$projId]);
                    awardXP($clientId, 'rnd_project_complete', []);
                    $msg = "Project COMPLETED. Ready for deployment."; $msgType = 'success';
                }
            }
        } elseif ($action === 'log_breakthrough' && $isOfficer) {
            $projId = (int)($_POST['project_id'] ?? 0);
            $name   = trim($_POST['bt_name'] ?? '');
            $rank   = $_POST['bt_rank'] ?? 'incremental';
            $desc   = trim($_POST['bt_desc'] ?? '');
            $impact = trim($_POST['bt_impact'] ?? '');
            $unlocks = trim($_POST['bt_unlocks'] ?? '');
            $validBR = ['incremental','significant','major','revolutionary','paradigm_shift'];
            $xpMap = ['incremental'=>1000,'significant'=>2500,'major'=>5000,'revolutionary'=>15000,'paradigm_shift'=>50000];
            if ($name === '' || $desc === '' || !in_array($rank, $validBR, true)) {
                $msg = 'All fields required.'; $msgType = 'error';
            } elseif ($rank === 'paradigm_shift' && !$isCommander) {
                $msg = 'Paradigm Shift requires Commander authorization.'; $msgType = 'error';
            } else {
                $xpR = $xpMap[$rank] ?? 1000;
                $db->prepare("INSERT INTO rnd_breakthroughs (project_id, breakthrough_name, breakthrough_rank, description, impact, xp_reward, unlocks) VALUES (?,?,?,?,?,?,?)")
                   ->execute([$projId, $name, $rank, $desc, $impact, $xpR, $unlocks]);
                $db->exec("UPDATE rnd_domains SET total_breakthroughs = total_breakthroughs + 1 WHERE id = (SELECT domain_id FROM rnd_projects WHERE id = " . (int)$projId . ")");
                $msg = "Breakthrough logged: <strong>" . htmlspecialchars($name) . "</strong> (" . strtoupper(str_replace('_', ' ', $rank)) . " — " . number_format($xpR) . " XP)."; $msgType = 'success';
            }
        } elseif ($action === 'verify_breakthrough' && $isFlag) {
            $btId = (int)($_POST['bt_id'] ?? 0);
            $bt = $db->prepare("SELECT * FROM rnd_breakthroughs WHERE id = ? AND verified_by IS NULL");
            $bt->execute([$btId]);
            $btRow = $bt->fetch(PDO::FETCH_ASSOC);
            if (!$btRow) { $msg = 'Breakthrough not found or already verified.'; $msgType = 'error'; }
            else {
                $db->prepare("UPDATE rnd_breakthroughs SET verified_by = ? WHERE id = ?")->execute([$clientId, $btId]);
                $researcher = $db->prepare("SELECT proposed_by FROM rnd_projects WHERE id = ?");
                $researcher->execute([$btRow['project_id']]);
                $rRow = $researcher->fetch(PDO::FETCH_ASSOC);
                if ($rRow && $rRow['proposed_by']) awardXP($rRow['proposed_by'], 'breakthrough_verified', ['rank' => $btRow['breakthrough_rank']]);
                $msg = "Breakthrough VERIFIED."; $msgType = 'success';
            }
        } else {
            $msg = 'Unauthorized or unknown action.'; $msgType = 'error';
        }
    }
    $_SESSION['csrf_rd'] = bin2hex(random_bytes(32));
    $csrf = $_SESSION['csrf_rd'];
}

// ── Data Fetch ──
$tab = $_GET['tab'] ?? 'labs';
$domains      = $db->query("SELECT rd.*, CONCAT(c.firstname,' ',c.lastname) AS lead_name FROM rnd_domains rd LEFT JOIN tblclients c ON c.id = rd.lead_researcher_id ORDER BY rd.domain_name")->fetchAll(PDO::FETCH_ASSOC);
$projects     = $db->query("SELECT rp.*, rd.domain_name, CONCAT(c.firstname,' ',c.lastname) AS proposer, CONCAT(c2.firstname,' ',c2.lastname) AS approver FROM rnd_projects rp JOIN rnd_domains rd ON rd.id = rp.domain_id LEFT JOIN tblclients c ON c.id = rp.proposed_by LEFT JOIN tblclients c2 ON c2.id = rp.approved_by ORDER BY FIELD(rp.priority,'critical','high','medium','low'), rp.id DESC")->fetchAll(PDO::FETCH_ASSOC);
$breakthroughs = $db->query("SELECT rb.*, rp.project_name, rp.project_code, CONCAT(c.firstname,' ',c.lastname) AS verifier FROM rnd_breakthroughs rb JOIN rnd_projects rp ON rp.id = rb.project_id LEFT JOIN tblclients c ON c.id = rb.verified_by ORDER BY rb.achieved_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$funding      = $db->query("SELECT rf.*, rp.project_code, rp.project_name, CONCAT(c.firstname,' ',c.lastname) AS funder FROM rnd_funding rf JOIN rnd_projects rp ON rp.id = rf.project_id LEFT JOIN tblclients c ON c.id = rf.funded_by ORDER BY rf.funded_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$personnel    = $db->query("SELECT rpe.*, rp.project_code, CONCAT(c.firstname,' ',c.lastname) AS researcher_name FROM rnd_personnel rpe JOIN rnd_projects rp ON rp.id = rpe.project_id LEFT JOIN tblclients c ON c.id = rpe.client_id ORDER BY rpe.assigned_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$activeProj   = count(array_filter($projects, fn($p) => in_array($p['status'], ['active','funded'])));
$totalFunding = array_sum(array_column($funding, 'amount'));

$pageTitle = 'Research & Development Labs';
include __DIR__ . '/includes/site-header.inc.php';
?>
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<style>
.rd-wrap{max-width:1100px;margin:0 auto;padding:1.5rem}
.rd-bg{background:#0a0a14;min-height:100vh;color:#e2e8f0}
.rd-card{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:10px;padding:1.25rem;margin-bottom:1rem}
.rd-card:hover{border-color:#8b5cf6;box-shadow:0 0 12px rgba(139,92,246,.12)}
.rd-title{font-size:1.6rem;font-weight:700;color:#f1f5f9;margin-bottom:.25rem;display:flex;align-items:center;gap:.5rem}
.rd-sub{color:#94a3b8;font-size:.85rem}
.rd-badge{display:inline-block;padding:2px 10px;border-radius:999px;font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
.rd-tabs{display:flex;gap:.5rem;margin-bottom:1.25rem;flex-wrap:wrap}
.rd-tab{padding:.4rem 1rem;border-radius:6px;cursor:pointer;font-size:.8rem;background:#2a2a4a;color:#94a3b8;text-decoration:none;font-weight:600;border:none}
.rd-tab.active{background:#8b5cf6;color:#fff}
.rd-stat-bar{display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem}
.rd-stat{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:8px;padding:.75rem 1.25rem;flex:1;min-width:110px;text-align:center}
.rd-stat .val{font-size:1.5rem;font-weight:700;color:#8b5cf6}
.rd-stat .lbl{font-size:.7rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px}
.rd-btn{background:#8b5cf6;color:#fff;border:none;padding:.5rem 1.25rem;border-radius:6px;cursor:pointer;font-size:.85rem;font-weight:600;transition:.2s}
.rd-btn:hover{background:#7c3aed}
.rd-btn-sm{padding:.3rem .75rem;font-size:.75rem}
.rd-btn-outline{background:transparent;border:1px solid #8b5cf6;color:#8b5cf6}
.rd-btn-outline:hover{background:#8b5cf6;color:#fff}
.rd-btn-green{background:#22c55e;color:#fff}.rd-btn-green:hover{background:#16a34a}
.rd-btn-blue{background:#3b82f6;color:#fff}.rd-btn-blue:hover{background:#2563eb}
.rd-btn-gold{background:#d4a017;color:#fff}.rd-btn-gold:hover{background:#b8860b}
.rd-input,.rd-select,.rd-textarea{width:100%;background:#0a0a14;border:1px solid #2a2a4a;color:#e2e8f0;padding:.5rem .75rem;border-radius:6px;font-size:.85rem;box-sizing:border-box}
.rd-textarea{min-height:100px;resize:vertical}
.rd-label{display:block;color:#94a3b8;font-size:.75rem;margin-bottom:.25rem;text-transform:uppercase;letter-spacing:.5px}
.rd-msg{padding:.75rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:.85rem}
.rd-msg-success{background:rgba(139,92,246,.12);border:1px solid #8b5cf6;color:#c4b5fd}
.rd-msg-error{background:rgba(239,68,68,.12);border:1px solid #ef4444;color:#fca5a5}
.rd-modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:900;align-items:center;justify-content:center}
.rd-modal-bg.open{display:flex}
.rd-modal{background:#1a1a2e;border:1px solid #2a2a4a;border-radius:12px;padding:1.5rem;width:90%;max-width:620px;max-height:80vh;overflow-y:auto}
.rd-modal h3{color:#f1f5f9;margin:0 0 1rem}
.rd-form-row{margin-bottom:.75rem}
.rd-pipeline{display:flex;gap:4px;flex-wrap:wrap;margin:.5rem 0}
.rd-pipeline span{padding:2px 8px;border-radius:4px;font-size:.65rem;font-weight:600;text-transform:uppercase}
</style>
<div class="rd-bg">
<div class="rd-wrap">
    <div class="rd-title"><i class="fas fa-flask"></i> Research & Development Labs</div>
    <p class="rd-sub" style="margin-bottom:1.25rem">Research proposals, breakthrough discovery, funding, and tech progression — Officer+ rank</p>

    <?php if ($msg): ?><div class="rd-msg rd-msg-<?= $msgType ?>"><?= $msg ?></div><?php endif; ?>

    <div class="rd-stat-bar">
        <div class="rd-stat"><div class="val"><?= count($domains) ?></div><div class="lbl">Domains</div></div>
        <div class="rd-stat"><div class="val" style="color:#3b82f6"><?= $activeProj ?></div><div class="lbl">Active Projects</div></div>
        <div class="rd-stat"><div class="val" style="color:#22c55e"><?= count($breakthroughs) ?></div><div class="lbl">Breakthroughs</div></div>
        <div class="rd-stat"><div class="val" style="color:#d4a017">$<?= number_format($totalFunding, 0) ?></div><div class="lbl">Total Funding</div></div>
        <div class="rd-stat"><div class="val" style="color:#f59e0b"><?= count($personnel) ?></div><div class="lbl">Researchers</div></div>
    </div>

    <div class="rd-tabs">
        <a href="?tab=labs" class="rd-tab <?= $tab==='labs'?'active':'' ?>"><i class="fas fa-flask"></i> Labs</a>
        <a href="?tab=projects" class="rd-tab <?= $tab==='projects'?'active':'' ?>"><i class="fas fa-project-diagram"></i> Projects</a>
        <a href="?tab=breakthroughs" class="rd-tab <?= $tab==='breakthroughs'?'active':'' ?>"><i class="fas fa-lightbulb"></i> Breakthroughs</a>
        <a href="?tab=funding" class="rd-tab <?= $tab==='funding'?'active':'' ?>"><i class="fas fa-coins"></i> Funding</a>
        <a href="?tab=team" class="rd-tab <?= $tab==='team'?'active':'' ?>"><i class="fas fa-users-cog"></i> Team</a>
    </div>

    <!-- ═══ TAB: LABS (Domains) ═══ -->
    <?php if ($tab === 'labs'): ?>
        <?php foreach ($domains as $d): ?>
            <div class="rd-card">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div>
                        <i class="fas fa-flask" style="color:#8b5cf6"></i>
                        <strong style="color:#f1f5f9;margin-left:.5rem"><?= htmlspecialchars($d['domain_name']) ?></strong>
                    </div>
                    <div style="font-size:.8rem;color:#64748b"><?= $d['total_projects'] ?> projects &bull; <?= $d['total_breakthroughs'] ?> breakthroughs</div>
                </div>
                <p style="color:#94a3b8;font-size:.85rem;margin-top:.25rem"><?= htmlspecialchars($d['description'] ?? '') ?></p>
                <div style="color:#64748b;font-size:.75rem;margin-top:.25rem">
                    Lead: <?= htmlspecialchars($d['lead_name'] ?? 'Unassigned') ?> &bull;
                    Budget: $<?= number_format($d['budget_allocated'], 2) ?>
                </div>
            </div>
        <?php endforeach; ?>

    <!-- ═══ TAB: PROJECTS ═══ -->
    <?php elseif ($tab === 'projects'): ?>
        <?php if ($isOfficer): ?>
            <div style="margin-bottom:1rem"><button class="rd-btn" onclick="document.getElementById('modalPropose').classList.add('open')"><i class="fas fa-plus"></i> Propose Project</button></div>
        <?php endif; ?>
        <?php
        $prColors = ['proposed'=>'#94a3b8','funded'=>'#3b82f6','active'=>'#22c55e','paused'=>'#f59e0b','completed'=>'#8b5cf6','cancelled'=>'#64748b'];
        $piColors = ['low'=>'#64748b','medium'=>'#3b82f6','high'=>'#f59e0b','critical'=>'#ef4444'];
        $phasePipeline = ['theory','prototype','testing','production'];
        foreach ($projects as $p): ?>
            <div class="rd-card" style="border-left:3px solid <?= $piColors[$p['priority']] ?>">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div>
                        <strong style="color:#8b5cf6"><?= htmlspecialchars($p['project_code']) ?></strong>
                        <strong style="color:#f1f5f9;margin-left:.5rem"><?= htmlspecialchars($p['project_name']) ?></strong>
                        <span class="rd-badge" style="background:<?= $piColors[$p['priority']] ?>20;color:<?= $piColors[$p['priority']] ?>;margin-left:.25rem"><?= strtoupper($p['priority']) ?></span>
                    </div>
                    <span class="rd-badge" style="background:<?= $prColors[$p['status']] ?>20;color:<?= $prColors[$p['status']] ?>;border:1px solid <?= $prColors[$p['status']] ?>40"><?= strtoupper($p['status']) ?></span>
                </div>
                <div class="rd-pipeline">
                    <?php foreach ($phasePipeline as $phase):
                        $isCur = ($p['phase'] === $phase);
                        $isPast = array_search($p['phase'], $phasePipeline) > array_search($phase, $phasePipeline);
                    ?>
                        <span style="background:<?= $isCur ? '#8b5cf6' : ($isPast ? '#22c55e30' : '#2a2a4a') ?>;color:<?= $isCur ? '#fff' : ($isPast ? '#22c55e' : '#64748b') ?>"><?= strtoupper($phase) ?></span>
                    <?php endforeach; ?>
                </div>
                <p style="color:#94a3b8;font-size:.85rem;margin-top:.25rem"><?= htmlspecialchars(mb_substr($p['description'] ?? '', 0, 150)) ?><?= mb_strlen($p['description'] ?? '') > 150 ? '...' : '' ?></p>
                <div style="color:#64748b;font-size:.75rem;margin-top:.25rem">
                    Domain: <?= htmlspecialchars($p['domain_name']) ?> &bull;
                    Cost: $<?= number_format($p['budget_cost'], 2) ?> &bull;
                    Est: <?= $p['estimated_weeks'] ?> weeks &bull;
                    By: <?= htmlspecialchars($p['proposer'] ?? 'Unknown') ?>
                </div>
                <?php if ($isFlag && !in_array($p['status'], ['completed','cancelled'])): ?>
                    <div style="margin-top:.5rem;display:flex;gap:.5rem;flex-wrap:wrap">
                        <?php if ($p['status'] === 'proposed'): ?>
                            <button class="rd-btn-sm rd-btn rd-btn-gold" onclick="openFund(<?= $p['id'] ?>,'<?= htmlspecialchars($p['project_code'], ENT_QUOTES) ?>')"><i class="fas fa-coins"></i> Fund</button>
                        <?php endif; ?>
                        <?php if (in_array($p['status'], ['funded','active'])): ?>
                            <form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="advance_phase"><input type="hidden" name="project_id" value="<?= $p['id'] ?>"><button class="rd-btn-sm rd-btn rd-btn-green"><i class="fas fa-forward"></i> Advance</button></form>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if (empty($projects)): ?><div class="rd-card" style="text-align:center;color:#64748b"><p>No projects proposed.</p></div><?php endif; ?>

    <!-- ═══ TAB: BREAKTHROUGHS ═══ -->
    <?php elseif ($tab === 'breakthroughs'): ?>
        <?php if ($isOfficer): ?>
            <div style="margin-bottom:1rem"><button class="rd-btn rd-btn-gold" onclick="document.getElementById('modalBreakthrough').classList.add('open')"><i class="fas fa-lightbulb"></i> Log Breakthrough</button></div>
        <?php endif; ?>
        <?php
        $brColors = ['incremental'=>'#64748b','significant'=>'#3b82f6','major'=>'#f59e0b','revolutionary'=>'#ef4444','paradigm_shift'=>'#d4a017'];
        $brIcons  = ['incremental'=>'fa-seedling','significant'=>'fa-star','major'=>'fa-star-half-alt','revolutionary'=>'fa-fire','paradigm_shift'=>'fa-atom'];
        foreach ($breakthroughs as $bt): ?>
            <div class="rd-card" style="border-left:3px solid <?= $brColors[$bt['breakthrough_rank']] ?>">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
                    <div>
                        <i class="fas <?= $brIcons[$bt['breakthrough_rank']] ?? 'fa-lightbulb' ?>" style="color:<?= $brColors[$bt['breakthrough_rank']] ?>"></i>
                        <strong style="color:#f1f5f9;margin-left:.25rem"><?= htmlspecialchars($bt['breakthrough_name']) ?></strong>
                        <span class="rd-badge" style="background:<?= $brColors[$bt['breakthrough_rank']] ?>20;color:<?= $brColors[$bt['breakthrough_rank']] ?>;margin-left:.5rem"><?= strtoupper(str_replace('_', ' ', $bt['breakthrough_rank'])) ?></span>
                    </div>
                    <span style="color:#d4a017;font-size:.8rem;font-weight:600"><?= number_format($bt['xp_reward']) ?> XP</span>
                </div>
                <p style="color:#cbd5e1;font-size:.85rem;margin-top:.5rem"><?= htmlspecialchars($bt['description']) ?></p>
                <?php if ($bt['impact']): ?><div style="color:#86efac;font-size:.8rem;margin-top:.25rem"><strong>Impact:</strong> <?= htmlspecialchars($bt['impact']) ?></div><?php endif; ?>
                <?php if ($bt['unlocks']): ?><div style="color:#c4b5fd;font-size:.8rem;margin-top:.25rem"><strong>Unlocks:</strong> <?= htmlspecialchars($bt['unlocks']) ?></div><?php endif; ?>
                <div style="color:#64748b;font-size:.75rem;margin-top:.25rem">
                    Project: <?= htmlspecialchars($bt['project_code'] ?? '') ?> — <?= htmlspecialchars($bt['project_name'] ?? '') ?>
                    &bull; <?= date('M j, Y', strtotime($bt['achieved_at'])) ?>
                    <?php if ($bt['verified_by']): ?>&bull; <span style="color:#22c55e">✓ Verified by <?= htmlspecialchars($bt['verifier'] ?? 'Commander') ?></span><?php elseif ($isFlag): ?>
                        &bull; <form method="POST" style="display:inline"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="verify_breakthrough"><input type="hidden" name="bt_id" value="<?= $bt['id'] ?>"><button class="rd-btn-sm rd-btn rd-btn-green" style="font-size:.7rem"><i class="fas fa-check"></i> Verify</button></form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($breakthroughs)): ?><div class="rd-card" style="text-align:center;color:#64748b"><p>No breakthroughs yet.</p></div><?php endif; ?>

    <!-- ═══ TAB: FUNDING ═══ -->
    <?php elseif ($tab === 'funding'): ?>
        <?php
        $srcColors = ['treasury'=>'#22c55e','department'=>'#3b82f6','grant'=>'#f59e0b','commander'=>'#d4a017'];
        foreach ($funding as $f): ?>
            <div class="rd-card" style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
                <div style="background:<?= $srcColors[$f['source']] ?>20;color:<?= $srcColors[$f['source']] ?>;padding:.5rem .75rem;border-radius:8px;font-size:1rem;font-weight:700">$<?= number_format($f['amount'], 0) ?></div>
                <div style="flex:1">
                    <strong style="color:#8b5cf6"><?= htmlspecialchars($f['project_code']) ?></strong>
                    <span style="color:#f1f5f9;margin-left:.5rem"><?= htmlspecialchars($f['project_name']) ?></span>
                    <div style="color:#64748b;font-size:.75rem">Source: <span class="rd-badge" style="background:<?= $srcColors[$f['source']] ?>20;color:<?= $srcColors[$f['source']] ?>"><?= strtoupper($f['source']) ?></span> &bull; By: <?= htmlspecialchars($f['funder'] ?? 'Unknown') ?> &bull; <?= date('M j, Y', strtotime($f['funded_at'])) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($funding)): ?><div class="rd-card" style="text-align:center;color:#64748b"><p>No funding records.</p></div><?php endif; ?>

    <!-- ═══ TAB: TEAM ═══ -->
    <?php elseif ($tab === 'team'): ?>
        <?php if ($isOfficer): ?>
            <div style="margin-bottom:1rem"><button class="rd-btn rd-btn-blue" onclick="document.getElementById('modalAssign').classList.add('open')"><i class="fas fa-user-plus"></i> Assign Personnel</button></div>
        <?php endif; ?>
        <?php
        $roleColors = ['lead'=>'#d4a017','researcher'=>'#8b5cf6','assistant'=>'#3b82f6','consultant'=>'#f59e0b'];
        foreach ($personnel as $pe): ?>
            <div class="rd-card" style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
                <i class="fas fa-user-cog" style="font-size:1.2rem;color:<?= $roleColors[$pe['role']] ?>"></i>
                <div style="flex:1">
                    <strong style="color:#f1f5f9"><?= htmlspecialchars($pe['researcher_name'] ?? 'Unknown') ?></strong>
                    <span class="rd-badge" style="background:<?= $roleColors[$pe['role']] ?>20;color:<?= $roleColors[$pe['role']] ?>;margin-left:.5rem"><?= strtoupper($pe['role']) ?></span>
                    <div style="color:#64748b;font-size:.75rem">Project: <?= htmlspecialchars($pe['project_code']) ?> &bull; Hours: <?= $pe['hours_logged'] ?> &bull; Since: <?= date('M j, Y', strtotime($pe['assigned_at'])) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($personnel)): ?><div class="rd-card" style="text-align:center;color:#64748b"><p>No personnel assigned.</p></div><?php endif; ?>
    <?php endif; ?>
</div>
</div>

<!-- Modals -->
<div class="rd-modal-bg" id="modalPropose"><div class="rd-modal"><h3><i class="fas fa-plus"></i> Propose Research Project</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="propose_project">
<div class="rd-form-row"><label class="rd-label">Domain</label><select name="domain_id" class="rd-select"><?php foreach ($domains as $d): ?><option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['domain_name']) ?></option><?php endforeach; ?></select></div>
<div class="rd-form-row"><label class="rd-label">Project Name</label><input type="text" name="project_name" class="rd-input" required></div>
<div class="rd-form-row"><label class="rd-label">Description</label><textarea name="project_desc" class="rd-textarea" required></textarea></div>
<div style="display:flex;gap:.75rem"><div class="rd-form-row" style="flex:1"><label class="rd-label">Priority</label><select name="priority" class="rd-select"><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option><option value="critical">Critical</option></select></div><div class="rd-form-row" style="flex:1"><label class="rd-label">Budget Cost ($)</label><input type="number" name="budget_cost" class="rd-input" value="10000" min="0" step="100"></div><div class="rd-form-row" style="flex:1"><label class="rd-label">Est. Weeks</label><input type="number" name="estimated_weeks" class="rd-input" value="12" min="1"></div></div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="rd-btn rd-btn-outline" onclick="this.closest('.rd-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="rd-btn"><i class="fas fa-plus"></i> Propose</button></div></form></div></div>

<div class="rd-modal-bg" id="modalFund"><div class="rd-modal"><h3><i class="fas fa-coins"></i> Fund Project</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="fund_project"><input type="hidden" name="project_id" id="fundProjId" value="">
<div style="color:#94a3b8;margin-bottom:1rem">Project: <strong id="fundProjCode" style="color:#8b5cf6"></strong></div>
<div style="display:flex;gap:.75rem"><div class="rd-form-row" style="flex:1"><label class="rd-label">Amount ($)</label><input type="number" name="amount" class="rd-input" min="1" step="100" required></div><div class="rd-form-row" style="flex:1"><label class="rd-label">Source</label><select name="source" class="rd-select"><option value="treasury">Treasury</option><option value="department">Department</option><option value="grant">Grant</option><option value="commander">Commander</option></select></div></div>
<div class="rd-form-row"><label class="rd-label">Notes</label><textarea name="fund_notes" class="rd-textarea" style="min-height:60px"></textarea></div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="rd-btn rd-btn-outline" onclick="this.closest('.rd-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="rd-btn rd-btn-gold"><i class="fas fa-coins"></i> Fund</button></div></form></div></div>

<div class="rd-modal-bg" id="modalBreakthrough"><div class="rd-modal"><h3><i class="fas fa-lightbulb"></i> Log Breakthrough</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="log_breakthrough">
<div class="rd-form-row"><label class="rd-label">Project</label><select name="project_id" class="rd-select"><?php foreach ($projects as $p): if (in_array($p['status'], ['funded','active'])): ?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['project_code'] . ' — ' . $p['project_name']) ?></option><?php endif; endforeach; ?></select></div>
<div class="rd-form-row"><label class="rd-label">Breakthrough Name</label><input type="text" name="bt_name" class="rd-input" required></div>
<div class="rd-form-row"><label class="rd-label">Rank</label><select name="bt_rank" class="rd-select"><option value="incremental">Incremental (1,000 XP)</option><option value="significant">Significant (2,500 XP)</option><option value="major">Major (5,000 XP)</option><option value="revolutionary">Revolutionary (15,000 XP)</option><option value="paradigm_shift">Paradigm Shift (50,000 XP) ⚠️</option></select></div>
<div class="rd-form-row"><label class="rd-label">Description</label><textarea name="bt_desc" class="rd-textarea" required></textarea></div>
<div class="rd-form-row"><label class="rd-label">Impact</label><textarea name="bt_impact" class="rd-textarea" style="min-height:60px"></textarea></div>
<div class="rd-form-row"><label class="rd-label">Unlocks (what this enables)</label><input type="text" name="bt_unlocks" class="rd-input" placeholder="e.g. Quantum Shield v2, Phase Cannon..."></div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="rd-btn rd-btn-outline" onclick="this.closest('.rd-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="rd-btn rd-btn-gold"><i class="fas fa-lightbulb"></i> Log</button></div></form></div></div>

<div class="rd-modal-bg" id="modalAssign"><div class="rd-modal"><h3><i class="fas fa-user-plus"></i> Assign Personnel</h3><form method="POST"><input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="assign_personnel">
<div class="rd-form-row"><label class="rd-label">Project</label><select name="project_id" class="rd-select"><?php foreach ($projects as $p): if ($p['status'] !== 'cancelled'): ?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['project_code'] . ' — ' . $p['project_name']) ?></option><?php endif; endforeach; ?></select></div>
<div class="rd-form-row"><label class="rd-label">Researcher Client ID</label><input type="number" name="researcher_id" class="rd-input" value="<?= $clientId ?>" min="1"></div>
<div class="rd-form-row"><label class="rd-label">Role</label><select name="researcher_role" class="rd-select"><option value="researcher">Researcher</option><option value="lead">Lead</option><option value="assistant">Assistant</option><option value="consultant">Consultant</option></select></div>
<div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem"><button type="button" class="rd-btn rd-btn-outline" onclick="this.closest('.rd-modal-bg').classList.remove('open')">Cancel</button><button type="submit" class="rd-btn rd-btn-blue"><i class="fas fa-user-plus"></i> Assign</button></div></form></div></div>

<script>
function openFund(id,code){document.getElementById('fundProjId').value=id;document.getElementById('fundProjCode').textContent=code;document.getElementById('modalFund').classList.add('open')}
document.querySelectorAll('.rd-modal-bg').forEach(bg=>{bg.addEventListener('click',e=>{if(e.target===bg)bg.classList.remove('open')})});
</script>
<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
