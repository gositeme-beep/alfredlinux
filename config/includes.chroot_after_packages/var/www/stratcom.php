<?php
require_once __DIR__ . '/includes/db-config.inc.php';
$db = getSharedDB();
require_once __DIR__ . '/includes/auth-gate.inc.php';
require_once __DIR__ . '/includes/rank-guard.inc.php';
if (empty($_SESSION['csrf_stratcom'])) $_SESSION['csrf_stratcom'] = bin2hex(random_bytes(32));
requireRank(6);

$isCommander = ($clientId === 33);
$isFlag      = ($userRankTier >= 9) || $isCommander;
$msg = '';
$msgType = '';

// ── POST Actions ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_stratcom'], $_POST['csrf'] ?? '')) {
        $msg = 'Invalid security token.'; $msgType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create_campaign' && $isFlag) {
            $name     = trim($_POST['camp_name'] ?? '');
            $codename = trim($_POST['camp_codename'] ?? '');
            $theater  = trim($_POST['camp_theater'] ?? '');
            $obj      = trim($_POST['camp_objective'] ?? '');
            $priority = $_POST['camp_priority'] ?? 'medium';
            $vcond    = trim($_POST['camp_victory'] ?? '');
            $validP   = ['low','medium','high','critical','supreme'];
            if ($name === '' || $codename === '' || $obj === '') {
                $msg = 'Name, codename, and objective are required.'; $msgType = 'error';
            } elseif (!in_array($priority, $validP, true)) {
                $msg = 'Invalid priority.'; $msgType = 'error';
            } else {
                $stmt = $db->prepare("INSERT INTO strategic_campaigns (name, codename, theater, objective, commander_client_id, priority, victory_conditions, start_date) VALUES (?,?,?,?,?,?,?,CURDATE())");
                $stmt->execute([$name, $codename, $theater, $obj, $clientId, $priority, $vcond]);
                awardXP($clientId, 'campaign_created', ['campaign' => $codename]);
                $msg = "Campaign <strong>" . htmlspecialchars($codename) . "</strong> created."; $msgType = 'success';
            }
        } elseif ($action === 'create_plan') {
            $campId   = (int)($_POST['plan_campaign_id'] ?? 0);
            $title    = trim($_POST['plan_title'] ?? '');
            $planType = $_POST['plan_type'] ?? 'offensive';
            $phases   = trim($_POST['plan_phases'] ?? '[]');
            $validT   = ['offensive','defensive','siege','guerrilla','naval','aerial','combined'];
            if ($campId < 1 || $title === '') {
                $msg = 'Campaign and plan title are required.'; $msgType = 'error';
            } elseif (!in_array($planType, $validT, true)) {
                $msg = 'Invalid plan type.'; $msgType = 'error';
            } else {
                $check = $db->prepare("SELECT id FROM strategic_campaigns WHERE id = ?");
                $check->execute([$campId]);
                if (!$check->fetch()) {
                    $msg = 'Campaign not found.'; $msgType = 'error';
                } else {
                    $pJson = json_decode($phases);
                    $pSafe = $pJson !== null ? json_encode($pJson) : '[]';
                    $stmt = $db->prepare("INSERT INTO strategic_war_plans (campaign_id, title, plan_type, phases, author_client_id) VALUES (?,?,?,?,?)");
                    $stmt->execute([$campId, $title, $planType, $pSafe, $clientId]);
                    awardXP($clientId, 'plan_created', ['plan' => $title]);
                    $msg = "War plan <strong>" . htmlspecialchars($title) . "</strong> submitted."; $msgType = 'success';
                }
            }
        } elseif ($action === 'approve_plan' && $isFlag) {
            $planId = (int)($_POST['plan_id'] ?? 0);
            if ($planId < 1) {
                $msg = 'Invalid plan.'; $msgType = 'error';
            } else {
                $stmt = $db->prepare("UPDATE strategic_war_plans SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ? AND status = 'draft'");
                $stmt->execute([$clientId, $planId]);
                $msg = $stmt->rowCount() ? 'Plan approved.' : 'Plan not found or already processed.';
                $msgType = $stmt->rowCount() ? 'success' : 'error';
            }
        } elseif ($action === 'complete_objective') {
            $objId = (int)($_POST['obj_id'] ?? 0);
            if ($objId < 1) {
                $msg = 'Invalid objective.'; $msgType = 'error';
            } else {
                $obj = $db->prepare("SELECT so.*, sc.codename FROM strategic_objectives so JOIN strategic_campaigns sc ON sc.id = so.campaign_id WHERE so.id = ? AND so.status IN ('pending','active')");
                $obj->execute([$objId]);
                $objRow = $obj->fetch(PDO::FETCH_ASSOC);
                if (!$objRow) {
                    $msg = 'Objective not found or already resolved.'; $msgType = 'error';
                } else {
                    $db->prepare("UPDATE strategic_objectives SET status = 'completed', completed_at = NOW() WHERE id = ?")->execute([$objId]);
                    awardXP($clientId, 'objective_completed', ['objective' => $objRow['title'], 'campaign' => $objRow['codename']]);
                    $msg = "Objective <strong>" . htmlspecialchars($objRow['title']) . "</strong> completed! +" . (int)$objRow['reward_xp'] . " XP"; $msgType = 'success';
                }
            }
        } elseif ($action === 'update_campaign_status' && $isFlag) {
            $campId    = (int)($_POST['camp_id'] ?? 0);
            $newStatus = $_POST['new_status'] ?? '';
            $validS    = ['planning','mobilizing','active','paused','victory','defeat','cancelled'];
            if ($campId < 1 || !in_array($newStatus, $validS, true)) {
                $msg = 'Invalid campaign or status.'; $msgType = 'error';
            } else {
                $stmt = $db->prepare("UPDATE strategic_campaigns SET status = ? WHERE id = ?");
                $stmt->execute([$newStatus, $campId]);
                $msg = $stmt->rowCount() ? 'Campaign status updated.' : 'No changes made.';
                $msgType = $stmt->rowCount() ? 'success' : 'error';
            }
        } else {
            $msg = 'Unauthorized or unknown action.'; $msgType = 'error';
        }
    }
}

// ── Data Fetch ──
$viewCampaign = isset($_GET['campaign']) ? (int)$_GET['campaign'] : 0;

// Analytics
$stats = $db->query("SELECT COUNT(*) AS total, SUM(status='active') AS active, SUM(status='victory') AS victories, SUM(status='defeat') AS defeats, SUM(total_forces) AS forces, SUM(casualties) AS casualties FROM strategic_campaigns")->fetch(PDO::FETCH_ASSOC);

// Campaign detail view
$campaign = null;
$objectives = [];
$warPlans = [];
if ($viewCampaign > 0) {
    $cStmt = $db->prepare("SELECT sc.*, CONCAT(c.firstname,' ',c.lastname) AS cmdr_name FROM strategic_campaigns sc LEFT JOIN tblclients c ON c.id = sc.commander_client_id WHERE sc.id = ?");
    $cStmt->execute([$viewCampaign]);
    $campaign = $cStmt->fetch(PDO::FETCH_ASSOC);
    if ($campaign) {
        $oStmt = $db->prepare("SELECT * FROM strategic_objectives WHERE campaign_id = ? ORDER BY FIELD(objective_type,'primary','secondary','bonus','hidden'), status ASC");
        $oStmt->execute([$viewCampaign]);
        $objectives = $oStmt->fetchAll(PDO::FETCH_ASSOC);
        $pStmt = $db->prepare("SELECT wp.*, CONCAT(c.firstname,' ',c.lastname) AS author_name FROM strategic_war_plans wp LEFT JOIN tblclients c ON c.id = wp.author_client_id WHERE wp.campaign_id = ? ORDER BY wp.created_at DESC");
        $pStmt->execute([$viewCampaign]);
        $warPlans = $pStmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// All campaigns list
$allCampaigns = $db->query("SELECT sc.*, CONCAT(c.firstname,' ',c.lastname) AS cmdr_name FROM strategic_campaigns sc LEFT JOIN tblclients c ON c.id = sc.commander_client_id ORDER BY FIELD(sc.status,'active','mobilizing','planning','paused','victory','defeat','cancelled'), sc.priority DESC, sc.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Campaigns for plan dropdown
$activeCampaigns = $db->query("SELECT id, codename FROM strategic_campaigns WHERE status NOT IN ('victory','defeat','cancelled') ORDER BY codename")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Strategic Command (STRATCOM)';
include __DIR__ . '/includes/site-header.inc.php';

$statusColors = ['planning'=>'#64748b','mobilizing'=>'#f59e0b','active'=>'#22c55e','paused'=>'#a855f7','victory'=>'#3b82f6','defeat'=>'#ef4444','cancelled'=>'#6b7280'];
$priorityColors = ['low'=>'#64748b','medium'=>'#3b82f6','high'=>'#f59e0b','critical'=>'#ef4444','supreme'=>'#a855f7'];
$planTypeIcons = ['offensive'=>'fa-crosshairs','defensive'=>'fa-shield-halved','siege'=>'fa-chess-rook','guerrilla'=>'fa-mask','naval'=>'fa-ship','aerial'=>'fa-jet-fighter','combined'=>'fa-layer-group'];
$objTypeColors = ['primary'=>'#ef4444','secondary'=>'#f59e0b','bonus'=>'#22c55e','hidden'=>'#a855f7'];
?>
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<style>
.sc-wrap{max-width:1200px;margin:0 auto;padding:1.5rem}
.sc-bg{background:#0f172a;min-height:100vh;color:#e2e8f0}
.sc-card{background:#1e293b;border:1px solid #334155;border-radius:10px;padding:1.25rem;margin-bottom:1rem}
.sc-card:hover{border-color:#3b82f6;box-shadow:0 0 12px rgba(59,130,246,.15)}
.sc-title{font-size:1.6rem;font-weight:700;color:#f1f5f9;margin-bottom:.25rem;display:flex;align-items:center;gap:.5rem}
.sc-sub{color:#94a3b8;font-size:.85rem}
.sc-badge{display:inline-block;padding:2px 10px;border-radius:999px;font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
.sc-grid{display:grid;gap:1rem}
.sc-grid-2{grid-template-columns:repeat(auto-fill,minmax(340px,1fr))}
.sc-grid-3{grid-template-columns:repeat(auto-fill,minmax(280px,1fr))}
.sc-stat-bar{display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem}
.sc-stat{background:#1e293b;border:1px solid #334155;border-radius:8px;padding:.75rem 1.25rem;flex:1;min-width:140px;text-align:center}
.sc-stat .val{font-size:1.5rem;font-weight:700;color:#3b82f6}
.sc-stat .lbl{font-size:.7rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px}
.sc-btn{background:#3b82f6;color:#fff;border:none;padding:.5rem 1.25rem;border-radius:6px;cursor:pointer;font-size:.85rem;font-weight:600;transition:.2s}
.sc-btn:hover{background:#2563eb}
.sc-btn-sm{padding:.3rem .75rem;font-size:.75rem}
.sc-btn-outline{background:transparent;border:1px solid #3b82f6;color:#3b82f6}
.sc-btn-outline:hover{background:#3b82f6;color:#fff}
.sc-btn-green{background:#22c55e}.sc-btn-green:hover{background:#16a34a}
.sc-btn-red{background:#ef4444}.sc-btn-red:hover{background:#dc2626}
.sc-input,.sc-select,.sc-textarea{width:100%;background:#0f172a;border:1px solid #334155;color:#e2e8f0;padding:.5rem .75rem;border-radius:6px;font-size:.85rem;box-sizing:border-box}
.sc-textarea{min-height:80px;resize:vertical}
.sc-label{display:block;color:#94a3b8;font-size:.75rem;margin-bottom:.25rem;text-transform:uppercase;letter-spacing:.5px}
.sc-msg{padding:.75rem 1rem;border-radius:8px;margin-bottom:1rem;font-size:.85rem}
.sc-msg-success{background:rgba(34,197,94,.12);border:1px solid #22c55e;color:#86efac}
.sc-msg-error{background:rgba(239,68,68,.12);border:1px solid #ef4444;color:#fca5a5}
.sc-tabs{display:flex;gap:.5rem;margin-bottom:1.25rem;flex-wrap:wrap}
.sc-tab{padding:.4rem 1rem;border-radius:6px;cursor:pointer;font-size:.8rem;background:#334155;color:#94a3b8;border:none;font-weight:600}
.sc-tab.active{background:#3b82f6;color:#fff}
.sc-obj-row{display:flex;align-items:center;gap:.75rem;padding:.5rem 0;border-bottom:1px solid #334155}
.sc-obj-row:last-child{border-bottom:none}
.sc-obj-check{width:18px;height:18px;accent-color:#3b82f6}
.sc-priority-bar{width:4px;border-radius:2px;height:100%;min-height:60px;position:absolute;left:0;top:0}
.sc-camp-card{position:relative;padding-left:1rem}
.sc-back{color:#3b82f6;text-decoration:none;font-size:.85rem;display:inline-flex;align-items:center;gap:.35rem;margin-bottom:1rem}
.sc-back:hover{color:#60a5fa}
.sc-modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:900;align-items:center;justify-content:center}
.sc-modal-bg.open{display:flex}
.sc-modal{background:#1e293b;border:1px solid #334155;border-radius:12px;padding:1.5rem;width:90%;max-width:520px;max-height:80vh;overflow-y:auto}
.sc-modal h3{color:#f1f5f9;margin:0 0 1rem}
.sc-form-row{margin-bottom:.75rem}
.sc-phase-list{list-style:none;padding:0;margin:.5rem 0}
.sc-phase-list li{padding:.35rem .5rem;background:#0f172a;border-radius:4px;margin-bottom:.35rem;font-size:.8rem;color:#cbd5e1}
.sc-timeline{display:flex;gap:.5rem;align-items:center;font-size:.75rem;color:#64748b}
.sc-force-bar{height:6px;background:#334155;border-radius:3px;overflow:hidden;margin-top:.35rem}
.sc-force-fill{height:100%;background:#3b82f6;border-radius:3px}
.sc-casualty-fill{height:100%;background:#ef4444;border-radius:3px}
</style>
<div class="sc-bg">
<div class="sc-wrap">
    <div class="sc-title"><i class="fas fa-satellite-dish"></i> Strategic Command (STRATCOM)</div>
    <p class="sc-sub" style="margin-bottom:1.25rem">Theater-level campaign planning, war plans, and objective tracking — Officers+ clearance</p>

    <?php if ($msg): ?>
        <div class="sc-msg sc-msg-<?= $msgType ?>"><?= $msg ?></div>
    <?php endif; ?>

    <!-- ── Analytics Bar ── -->
    <div class="sc-stat-bar">
        <div class="sc-stat"><div class="val"><?= (int)$stats['total'] ?></div><div class="lbl">Total Campaigns</div></div>
        <div class="sc-stat"><div class="val" style="color:#22c55e"><?= (int)$stats['active'] ?></div><div class="lbl">Active</div></div>
        <div class="sc-stat"><div class="val" style="color:#3b82f6"><?= (int)$stats['victories'] ?></div><div class="lbl">Victories</div></div>
        <div class="sc-stat"><div class="val" style="color:#ef4444"><?= (int)$stats['defeats'] ?></div><div class="lbl">Defeats</div></div>
        <div class="sc-stat"><div class="val"><?= number_format((int)$stats['forces']) ?></div><div class="lbl">Forces Deployed</div></div>
        <div class="sc-stat"><div class="val" style="color:#f59e0b"><?= number_format((int)$stats['casualties']) ?></div><div class="lbl">Casualties</div></div>
    </div>

<?php if ($campaign): ?>
    <!-- ══════ CAMPAIGN DETAIL ══════ -->
    <a href="stratcom.php" class="sc-back"><i class="fas fa-chevron-left"></i> All Campaigns</a>
    <div class="sc-card">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:.5rem">
            <div>
                <div class="sc-title" style="font-size:1.3rem"><i class="fas fa-crosshairs"></i> <?= htmlspecialchars($campaign['codename']) ?></div>
                <div class="sc-sub"><?= htmlspecialchars($campaign['name']) ?> — Theater: <?= htmlspecialchars($campaign['theater'] ?: 'Unspecified') ?></div>
            </div>
            <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap">
                <span class="sc-badge" style="background:<?= $statusColors[$campaign['status']] ?? '#64748b' ?>;color:#fff"><?= htmlspecialchars($campaign['status']) ?></span>
                <span class="sc-badge" style="background:<?= $priorityColors[$campaign['priority']] ?? '#64748b' ?>;color:#fff"><?= strtoupper(htmlspecialchars($campaign['priority'])) ?></span>
            </div>
        </div>
        <div style="margin-top:1rem;display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:.75rem">
            <div><span class="sc-sub">Commander</span><div style="font-weight:600"><?= htmlspecialchars($campaign['cmdr_name'] ?: 'Unknown') ?></div></div>
            <div><span class="sc-sub">Forces</span><div style="font-weight:600"><?= number_format((int)$campaign['total_forces']) ?></div></div>
            <div><span class="sc-sub">Casualties</span><div style="font-weight:600;color:#ef4444"><?= number_format((int)$campaign['casualties']) ?><?php if ((int)$campaign['total_forces'] > 0): ?> (<?= round((int)$campaign['casualties']/(int)$campaign['total_forces']*100,1) ?>%)<?php endif; ?></div></div>
            <div><span class="sc-sub">Territory Gained</span><div style="font-weight:600;color:#22c55e"><?= number_format((int)$campaign['territory_gained']) ?></div></div>
            <div><span class="sc-sub">Resources Spent</span><div style="font-weight:600"><?= number_format((float)$campaign['resources_spent'],2) ?></div></div>
            <div><span class="sc-sub">Timeline</span><div class="sc-timeline"><?= htmlspecialchars($campaign['start_date'] ?? '—') ?> → <?= htmlspecialchars($campaign['end_date'] ?? 'Ongoing') ?></div></div>
        </div>
        <?php if ($campaign['objective']): ?>
            <div style="margin-top:1rem"><span class="sc-sub">Objective</span><p style="margin:.25rem 0;font-size:.9rem"><?= nl2br(htmlspecialchars($campaign['objective'])) ?></p></div>
        <?php endif; ?>
        <?php if ($campaign['victory_conditions']): ?>
            <div style="margin-top:.75rem"><span class="sc-sub">Victory Conditions</span><p style="margin:.25rem 0;font-size:.85rem;color:#86efac"><?= nl2br(htmlspecialchars($campaign['victory_conditions'])) ?></p></div>
        <?php endif; ?>
        <?php if ($campaign['after_action_report']): ?>
            <div style="margin-top:.75rem"><span class="sc-sub">After-Action Report</span><p style="margin:.25rem 0;font-size:.85rem;color:#fde68a"><?= nl2br(htmlspecialchars($campaign['after_action_report'])) ?></p></div>
        <?php endif; ?>
        <?php if ($isFlag): ?>
        <div style="margin-top:1rem;display:flex;gap:.5rem;flex-wrap:wrap">
            <form method="post" style="display:inline"><input type="hidden" name="csrf" value="<?= $_SESSION['csrf_stratcom'] ?>"><input type="hidden" name="action" value="update_campaign_status"><input type="hidden" name="camp_id" value="<?= $campaign['id'] ?>">
                <select name="new_status" class="sc-select" style="width:auto;display:inline-block">
                    <?php foreach (['planning','mobilizing','active','paused','victory','defeat','cancelled'] as $s): ?>
                        <option value="<?= $s ?>" <?= $campaign['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="sc-btn sc-btn-sm">Update Status</button>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <!-- Objectives -->
    <div class="sc-card">
        <h3 style="color:#f1f5f9;margin:0 0 .75rem;font-size:1.1rem"><i class="fas fa-bullseye"></i> Objectives (<?= count($objectives) ?>)</h3>
        <?php if (empty($objectives)): ?>
            <p class="sc-sub">No objectives defined for this campaign yet.</p>
        <?php else: ?>
            <?php foreach ($objectives as $o): ?>
                <div class="sc-obj-row">
                    <?php if ($o['status'] === 'completed'): ?>
                        <i class="fas fa-check-circle" style="color:#22c55e;font-size:1.1rem"></i>
                    <?php elseif ($o['status'] === 'failed'): ?>
                        <i class="fas fa-times-circle" style="color:#ef4444;font-size:1.1rem"></i>
                    <?php else: ?>
                        <form method="post" style="margin:0"><input type="hidden" name="csrf" value="<?= $_SESSION['csrf_stratcom'] ?>"><input type="hidden" name="action" value="complete_objective"><input type="hidden" name="obj_id" value="<?= $o['id'] ?>">
                            <button type="submit" class="sc-obj-check" style="background:none;border:1px solid #64748b;border-radius:4px;cursor:pointer" title="Mark complete">&nbsp;</button>
                        </form>
                    <?php endif; ?>
                    <div style="flex:1">
                        <div style="font-weight:600;font-size:.9rem"><?= htmlspecialchars($o['title']) ?>
                            <span class="sc-badge" style="background:<?= $objTypeColors[$o['objective_type']] ?? '#64748b' ?>;color:#fff;margin-left:.5rem"><?= htmlspecialchars($o['objective_type']) ?></span>
                        </div>
                        <?php if ($o['description']): ?><div class="sc-sub"><?= htmlspecialchars($o['description']) ?></div><?php endif; ?>
                        <div class="sc-sub">+<?= (int)$o['reward_xp'] ?> XP<?php if ($o['completed_at']): ?> — Completed <?= htmlspecialchars($o['completed_at']) ?><?php endif; ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- War Plans -->
    <div class="sc-card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem">
            <h3 style="color:#f1f5f9;margin:0;font-size:1.1rem"><i class="fas fa-map"></i> War Plans (<?= count($warPlans) ?>)</h3>
            <button class="sc-btn sc-btn-sm sc-btn-outline" onclick="document.getElementById('planModal').classList.add('open')"><i class="fas fa-plus"></i> New Plan</button>
        </div>
        <?php if (empty($warPlans)): ?>
            <p class="sc-sub">No war plans submitted.</p>
        <?php else: ?>
            <div class="sc-grid sc-grid-2">
            <?php foreach ($warPlans as $wp): ?>
                <div class="sc-card" style="margin:0">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start">
                        <div>
                            <div style="font-weight:600"><i class="fas <?= $planTypeIcons[$wp['plan_type']] ?? 'fa-scroll' ?>"></i> <?= htmlspecialchars($wp['title']) ?></div>
                            <div class="sc-sub"><?= ucfirst(htmlspecialchars($wp['plan_type'])) ?> — by <?= htmlspecialchars($wp['author_name'] ?: 'Unknown') ?></div>
                        </div>
                        <span class="sc-badge" style="background:<?= $wp['status']==='approved'?'#22c55e':($wp['status']==='draft'?'#f59e0b':'#3b82f6') ?>;color:#fff"><?= htmlspecialchars($wp['status']) ?></span>
                    </div>
                    <?php $phases = json_decode($wp['phases'] ?? '[]', true); if (!empty($phases)): ?>
                        <ul class="sc-phase-list">
                            <?php foreach ($phases as $i => $ph): ?>
                                <li><strong>Phase <?= $i+1 ?>:</strong> <?= htmlspecialchars(is_string($ph) ? $ph : ($ph['name'] ?? json_encode($ph))) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <?php if ($isFlag && $wp['status'] === 'draft'): ?>
                        <form method="post" style="margin-top:.5rem"><input type="hidden" name="csrf" value="<?= $_SESSION['csrf_stratcom'] ?>"><input type="hidden" name="action" value="approve_plan"><input type="hidden" name="plan_id" value="<?= $wp['id'] ?>">
                            <button class="sc-btn sc-btn-sm sc-btn-green"><i class="fas fa-check"></i> Approve</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

<?php else: ?>
    <!-- ══════ CAMPAIGNS DASHBOARD ══════ -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;flex-wrap:wrap;gap:.5rem">
        <h2 style="color:#f1f5f9;margin:0;font-size:1.15rem"><i class="fas fa-flag"></i> Campaigns</h2>
        <?php if ($isFlag): ?>
            <button class="sc-btn" onclick="document.getElementById('campModal').classList.add('open')"><i class="fas fa-plus"></i> New Campaign</button>
        <?php endif; ?>
    </div>

    <div class="sc-tabs" id="statusTabs">
        <button class="sc-tab active" data-filter="all">All</button>
        <button class="sc-tab" data-filter="active">Active</button>
        <button class="sc-tab" data-filter="planning">Planning</button>
        <button class="sc-tab" data-filter="mobilizing">Mobilizing</button>
        <button class="sc-tab" data-filter="paused">Paused</button>
        <button class="sc-tab" data-filter="victory">Victory</button>
        <button class="sc-tab" data-filter="defeat">Defeat</button>
    </div>

    <?php if (empty($allCampaigns)): ?>
        <div class="sc-card" style="text-align:center;padding:3rem">
            <i class="fas fa-satellite-dish" style="font-size:2.5rem;color:#334155;margin-bottom:1rem"></i>
            <p class="sc-sub">No campaigns yet. Flag officers can create the first campaign.</p>
        </div>
    <?php else: ?>
        <div class="sc-grid sc-grid-2" id="campaignGrid">
        <?php foreach ($allCampaigns as $c): ?>
            <a href="stratcom.php?campaign=<?= $c['id'] ?>" class="sc-card sc-camp-card" data-status="<?= htmlspecialchars($c['status']) ?>" style="text-decoration:none;color:inherit;display:block">
                <div class="sc-priority-bar" style="background:<?= $priorityColors[$c['priority']] ?? '#64748b' ?>"></div>
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:.5rem">
                    <div>
                        <div style="font-weight:700;font-size:1rem;color:#f1f5f9"><?= htmlspecialchars($c['codename']) ?></div>
                        <div class="sc-sub"><?= htmlspecialchars($c['name']) ?></div>
                    </div>
                    <span class="sc-badge" style="background:<?= $statusColors[$c['status']] ?? '#64748b' ?>;color:#fff;flex-shrink:0"><?= htmlspecialchars($c['status']) ?></span>
                </div>
                <div style="margin-top:.75rem;display:grid;grid-template-columns:1fr 1fr 1fr;gap:.5rem;font-size:.75rem;color:#94a3b8">
                    <div><i class="fas fa-globe"></i> <?= htmlspecialchars($c['theater'] ?: '—') ?></div>
                    <div><i class="fas fa-users"></i> <?= number_format((int)$c['total_forces']) ?> forces</div>
                    <div><i class="fas fa-skull-crossbones"></i> <?= number_format((int)$c['casualties']) ?> KIA</div>
                </div>
                <?php if ((int)$c['total_forces'] > 0): ?>
                    <div class="sc-force-bar" style="margin-top:.5rem" title="Casualty ratio">
                        <div class="sc-casualty-fill" style="width:<?= min(100, round((int)$c['casualties']/(int)$c['total_forces']*100,1)) ?>%"></div>
                    </div>
                <?php endif; ?>
                <div style="margin-top:.5rem;display:flex;justify-content:space-between;font-size:.7rem;color:#64748b">
                    <span><i class="fas fa-user-shield"></i> <?= htmlspecialchars($c['cmdr_name'] ?: 'Unknown') ?></span>
                    <span><i class="fas fa-flag-checkered"></i> <?= htmlspecialchars($c['territory_gained']) ?> territory</span>
                </div>
            </a>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>
</div><!-- .sc-wrap -->
</div><!-- .sc-bg -->

<!-- ── Create Campaign Modal ── -->
<div class="sc-modal-bg" id="campModal">
<div class="sc-modal">
    <h3><i class="fas fa-flag"></i> Create Campaign</h3>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= $_SESSION['csrf_stratcom'] ?>">
        <input type="hidden" name="action" value="create_campaign">
        <div class="sc-form-row"><label class="sc-label">Campaign Name *</label><input name="camp_name" class="sc-input" required maxlength="200"></div>
        <div class="sc-form-row"><label class="sc-label">Codename *</label><input name="camp_codename" class="sc-input" required maxlength="100" placeholder="e.g. OPERATION DAWN"></div>
        <div class="sc-form-row"><label class="sc-label">Theater</label><input name="camp_theater" class="sc-input" maxlength="150" placeholder="e.g. Eastern Front"></div>
        <div class="sc-form-row"><label class="sc-label">Objective *</label><textarea name="camp_objective" class="sc-textarea" required></textarea></div>
        <div class="sc-form-row"><label class="sc-label">Priority</label>
            <select name="camp_priority" class="sc-select">
                <option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option><option value="critical">Critical</option><option value="supreme">Supreme</option>
            </select>
        </div>
        <div class="sc-form-row"><label class="sc-label">Victory Conditions</label><textarea name="camp_victory" class="sc-textarea" placeholder="Define what constitutes victory..."></textarea></div>
        <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem">
            <button type="button" class="sc-btn sc-btn-outline" onclick="this.closest('.sc-modal-bg').classList.remove('open')">Cancel</button>
            <button type="submit" class="sc-btn"><i class="fas fa-rocket"></i> Launch Campaign</button>
        </div>
    </form>
</div>
</div>

<!-- ── Create War Plan Modal ── -->
<div class="sc-modal-bg" id="planModal">
<div class="sc-modal">
    <h3><i class="fas fa-map"></i> Create War Plan</h3>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= $_SESSION['csrf_stratcom'] ?>">
        <input type="hidden" name="action" value="create_plan">
        <div class="sc-form-row"><label class="sc-label">Campaign *</label>
            <select name="plan_campaign_id" class="sc-select" required>
                <option value="">Select campaign...</option>
                <?php foreach ($activeCampaigns as $ac): ?>
                    <option value="<?= $ac['id'] ?>" <?= $viewCampaign===$ac['id']?'selected':'' ?>><?= htmlspecialchars($ac['codename']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="sc-form-row"><label class="sc-label">Plan Title *</label><input name="plan_title" class="sc-input" required maxlength="200"></div>
        <div class="sc-form-row"><label class="sc-label">Type</label>
            <select name="plan_type" class="sc-select">
                <option value="offensive">Offensive</option><option value="defensive">Defensive</option><option value="siege">Siege</option><option value="guerrilla">Guerrilla</option><option value="naval">Naval</option><option value="aerial">Aerial</option><option value="combined">Combined Arms</option>
            </select>
        </div>
        <div class="sc-form-row"><label class="sc-label">Phases (JSON array of strings)</label><textarea name="plan_phases" class="sc-textarea" placeholder='["Phase 1: Reconnaissance","Phase 2: Advance","Phase 3: Assault"]'>[]</textarea></div>
        <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem">
            <button type="button" class="sc-btn sc-btn-outline" onclick="this.closest('.sc-modal-bg').classList.remove('open')">Cancel</button>
            <button type="submit" class="sc-btn"><i class="fas fa-scroll"></i> Submit Plan</button>
        </div>
    </form>
</div>
</div>

<script>
// Tab filtering
document.querySelectorAll('.sc-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.sc-tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        const f = tab.dataset.filter;
        document.querySelectorAll('#campaignGrid > .sc-camp-card').forEach(c => {
            c.style.display = (f === 'all' || c.dataset.status === f) ? '' : 'none';
        });
    });
});
// Close modals on backdrop click
document.querySelectorAll('.sc-modal-bg').forEach(bg => {
    bg.addEventListener('click', e => { if (e.target === bg) bg.classList.remove('open'); });
});
</script>
<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
