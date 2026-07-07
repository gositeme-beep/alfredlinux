<?php
/**
 * Psychological Operations (PSYOPS)
 * GoSiteMe Military Ecosystem
 */
session_start();

require_once __DIR__ . '/includes/db-config.inc.php';
$db = getSharedDB();
require_once __DIR__ . '/includes/auth-gate.inc.php';
require_once __DIR__ . '/includes/rank-guard.inc.php';

requireRank(4); // Sergeant+ (NCO)

if (empty($_SESSION['csrf_psyops'])) {
    $_SESSION['csrf_psyops'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_psyops'];
$isCommander = ($clientId === 33);
$isOfficer   = ($userRankTier >= 7 || $isCommander);

// ── Ensure DB Tables ────────────────────────────────────────
$db->exec("CREATE TABLE IF NOT EXISTS psyops_campaigns (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    campaign_type ENUM('morale_boost','propaganda','counter_intel','information','deception') NOT NULL DEFAULT 'information',
    status ENUM('planning','active','completed','cancelled') NOT NULL DEFAULT 'planning',
    target_audience VARCHAR(255) NOT NULL DEFAULT '',
    message TEXT NOT NULL,
    effectiveness_score TINYINT UNSIGNED NOT NULL DEFAULT 0,
    reach_count INT UNSIGNED NOT NULL DEFAULT 0,
    commander_client_id INT UNSIGNED NOT NULL,
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    xp_reward INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS morale_tracker (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL UNIQUE,
    morale_score TINYINT UNSIGNED NOT NULL DEFAULT 75,
    fatigue_level TINYINT UNSIGNED NOT NULL DEFAULT 0,
    motivation ENUM('rock_bottom','low','moderate','high','outstanding','legendary') NOT NULL DEFAULT 'moderate',
    last_boost_at DATETIME DEFAULT NULL,
    last_action_at DATETIME DEFAULT NULL,
    streak_days INT UNSIGNED NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS morale_events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    event_type ENUM('boost','penalty','natural_decay','activity','rest','ceremony','victory','defeat') NOT NULL,
    morale_change INT NOT NULL DEFAULT 0,
    reason VARCHAR(255) NOT NULL DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_client_created (client_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── POST Handlers ───────────────────────────────────────────
$flash = '';
$flashType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!hash_equals($csrfToken, $_POST['csrf_token'] ?? '')) {
        $flash = 'Security token mismatch.';
        $flashType = 'error';
    } else {
        $action = $_POST['action'];

        // ── Boost Morale ────────────────────────────────────
        if ($action === 'boost_morale') {
            $chk = $db->prepare("SELECT last_boost_at FROM morale_tracker WHERE client_id = ?");
            $chk->execute([$clientId]);
            $row = $chk->fetch(PDO::FETCH_ASSOC);
            $canBoost = true;
            if ($row && $row['last_boost_at']) {
                $last = strtotime($row['last_boost_at']);
                if ($last && (time() - $last) < 86400) {
                    $canBoost = false;
                }
            }
            if (!$canBoost) {
                $flash = 'You already boosted today. Try again tomorrow.';
                $flashType = 'info';
            } else {
                $db->beginTransaction();
                $db->prepare("INSERT INTO morale_tracker (client_id, morale_score, last_boost_at, last_action_at)
                    VALUES (?, LEAST(100, 75+5), NOW(), NOW())
                    ON DUPLICATE KEY UPDATE morale_score = LEAST(100, morale_score + 5), last_boost_at = NOW(), last_action_at = NOW()")
                    ->execute([$clientId]);
                $db->prepare("INSERT INTO morale_events (client_id, event_type, morale_change, reason) VALUES (?, 'boost', 5, 'Daily self-boost')")
                    ->execute([$clientId]);
                // Update motivation based on new score
                $db->prepare("UPDATE morale_tracker SET motivation = CASE
                    WHEN morale_score >= 95 THEN 'legendary'
                    WHEN morale_score >= 80 THEN 'outstanding'
                    WHEN morale_score >= 60 THEN 'high'
                    WHEN morale_score >= 40 THEN 'moderate'
                    WHEN morale_score >= 20 THEN 'low'
                    ELSE 'rock_bottom' END WHERE client_id = ?")->execute([$clientId]);
                $db->commit();
                $flash = 'Morale boosted! +5 points.';
                $flashType = 'success';
            }
        }

        // ── Create Campaign ─────────────────────────────────
        if ($action === 'create_campaign' && $isOfficer) {
            $cName    = trim($_POST['cname'] ?? '');
            $cType    = $_POST['ctype'] ?? 'information';
            $cTarget  = trim($_POST['ctarget'] ?? '');
            $cMessage = trim($_POST['cmessage'] ?? '');
            $cXp      = max(0, min(5000, (int)($_POST['cxp'] ?? 0)));
            $validTypes = ['morale_boost','propaganda','counter_intel','information','deception'];
            if ($cName === '' || $cMessage === '') {
                $flash = 'Campaign name and message are required.';
                $flashType = 'error';
            } elseif (!in_array($cType, $validTypes, true)) {
                $flash = 'Invalid campaign type.';
                $flashType = 'error';
            } else {
                $db->prepare("INSERT INTO psyops_campaigns (name, campaign_type, target_audience, message, commander_client_id, start_date, xp_reward) VALUES (?,?,?,?,?, CURDATE(),?)")
                    ->execute([$cName, $cType, $cTarget, $cMessage, $clientId, $cXp]);
                $flash = 'Campaign created: ' . htmlspecialchars($cName);
                $flashType = 'success';
            }
        }

        // ── Activate / Complete Campaign ────────────────────
        if ($action === 'activate_campaign' && $isOfficer) {
            $cId     = (int)($_POST['campaign_id'] ?? 0);
            $newStat = $_POST['new_status'] ?? '';
            $validStat = ['active','completed','cancelled'];
            if ($cId > 0 && in_array($newStat, $validStat, true)) {
                $endDate = in_array($newStat, ['completed','cancelled'], true) ? date('Y-m-d') : null;
                $db->prepare("UPDATE psyops_campaigns SET status = ?, end_date = COALESCE(?, end_date) WHERE id = ?")
                    ->execute([$newStat, $endDate, $cId]);
                $flash = 'Campaign status updated.';
                $flashType = 'success';
            }
        }
    }
}

// ── Load Personal Morale ────────────────────────────────────
$db->prepare("INSERT INTO morale_tracker (client_id, morale_score, motivation) VALUES (?, 75, 'moderate') ON DUPLICATE KEY UPDATE morale_score = morale_score")
    ->execute([$clientId]);
$mStmt = $db->prepare("SELECT * FROM morale_tracker WHERE client_id = ?");
$mStmt->execute([$clientId]);
$morale = $mStmt->fetch(PDO::FETCH_ASSOC);

// ── Morale Events ───────────────────────────────────────────
$evStmt = $db->prepare("SELECT * FROM morale_events WHERE client_id = ? ORDER BY created_at DESC LIMIT 20");
$evStmt->execute([$clientId]);
$events = $evStmt->fetchAll(PDO::FETCH_ASSOC);

// ── Active Campaigns ────────────────────────────────────────
$campStmt = $db->prepare("SELECT * FROM psyops_campaigns ORDER BY FIELD(status,'active','planning','completed','cancelled'), created_at DESC LIMIT 50");
$campStmt->execute();
$campaigns = $campStmt->fetchAll(PDO::FETCH_ASSOC);

// ── Unit Morale Overview ────────────────────────────────────
$unitAvg = (int)($db->query("SELECT ROUND(AVG(morale_score)) FROM morale_tracker")->fetchColumn() ?: 0);
$unitCount = (int)($db->query("SELECT COUNT(*) FROM morale_tracker")->fetchColumn() ?: 0);
$motDist = $db->query("SELECT motivation, COUNT(*) as cnt FROM morale_tracker GROUP BY motivation ORDER BY FIELD(motivation,'legendary','outstanding','high','moderate','low','rock_bottom')")->fetchAll(PDO::FETCH_ASSOC);

// ── Helpers ─────────────────────────────────────────────────
$typeIcons = [
    'morale_boost' => 'fa-heart',
    'propaganda'   => 'fa-bullhorn',
    'counter_intel' => 'fa-user-secret',
    'information'  => 'fa-newspaper',
    'deception'    => 'fa-masks-theater',
];
$statusColors = [
    'planning'  => '#f59e0b',
    'active'    => '#22c55e',
    'completed' => '#6366f1',
    'cancelled' => '#ef4444',
];
$motivColors = [
    'legendary'   => '#f59e0b',
    'outstanding' => '#22c55e',
    'high'        => '#3b82f6',
    'moderate'    => '#8b5cf6',
    'low'         => '#f97316',
    'rock_bottom' => '#ef4444',
];

$noGlobalMain = true;
$pageTitle = 'PSYOPS — Psychological Operations';
require_once __DIR__ . '/includes/site-header.inc.php';
?>
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<style>
:root{--bg:#0f172a;--card:#1e293b;--border:#334155;--text:#e2e8f0;--accent:#3b82f6;--dim:#94a3b8}
*,*::before,*::after{box-sizing:border-box}
body{background:var(--bg);color:var(--text);font-family:'Segoe UI',system-ui,sans-serif;margin:0}
.psy-wrap{max-width:1200px;margin:0 auto;padding:24px 16px}
.psy-title{font-size:1.75rem;font-weight:700;margin:0 0 8px;display:flex;align-items:center;gap:10px}
.psy-title i{color:var(--accent)}
.psy-sub{color:var(--dim);margin:0 0 24px;font-size:.95rem}
.psy-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}
@media(max-width:800px){.psy-grid{grid-template-columns:1fr}}
.card{background:var(--card);border:1px solid var(--border);border-radius:10px;padding:20px}
.card-head{font-size:1.1rem;font-weight:600;margin:0 0 14px;display:flex;align-items:center;gap:8px}
.card-head i{color:var(--accent)}
.flash{padding:12px 16px;border-radius:8px;margin-bottom:18px;font-weight:500}
.flash-success{background:#064e3b;border:1px solid #22c55e;color:#bbf7d0}
.flash-error{background:#7f1d1d;border:1px solid #ef4444;color:#fecaca}
.flash-info{background:#1e3a5f;border:1px solid #3b82f6;color:#bfdbfe}
/* Morale Gauge */
.gauge-wrap{display:flex;align-items:center;gap:24px;margin-bottom:16px}
.gauge{position:relative;width:120px;height:120px}
.gauge svg{width:120px;height:120px;transform:rotate(-90deg)}
.gauge circle{fill:none;stroke-width:10;stroke-linecap:round}
.gauge .bg{stroke:var(--border)}
.gauge .fg{transition:stroke-dashoffset .6s ease}
.gauge-val{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:1.6rem;font-weight:700}
.stat-row{display:flex;flex-wrap:wrap;gap:14px;margin-top:12px}
.stat-pill{background:var(--bg);border:1px solid var(--border);border-radius:8px;padding:8px 14px;font-size:.85rem;display:flex;align-items:center;gap:6px}
.stat-pill i{color:var(--accent);width:16px;text-align:center}
.badge{display:inline-block;padding:3px 10px;border-radius:12px;font-size:.75rem;font-weight:600;text-transform:uppercase}
/* Progress bars */
.bar-wrap{background:var(--bg);border-radius:6px;height:14px;overflow:hidden;margin:6px 0}
.bar-fill{height:100%;border-radius:6px;transition:width .4s ease}
.bar-label{display:flex;justify-content:space-between;font-size:.8rem;color:var(--dim)}
/* Campaign cards */
.camp-list{display:flex;flex-direction:column;gap:12px;max-height:420px;overflow-y:auto}
.camp-card{background:var(--bg);border:1px solid var(--border);border-radius:8px;padding:14px}
.camp-top{display:flex;align-items:center;gap:10px;margin-bottom:8px}
.camp-top i{font-size:1.2rem}
.camp-name{font-weight:600;flex:1}
.camp-meta{font-size:.8rem;color:var(--dim);margin-top:6px}
/* Form */
.form-group{margin-bottom:12px}
.form-group label{display:block;font-size:.85rem;color:var(--dim);margin-bottom:4px}
.form-group input,.form-group select,.form-group textarea{width:100%;background:var(--bg);border:1px solid var(--border);color:var(--text);border-radius:6px;padding:8px 10px;font-size:.9rem}
.form-group textarea{resize:vertical;min-height:60px}
.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 18px;border:none;border-radius:6px;font-size:.9rem;font-weight:600;cursor:pointer;transition:background .2s}
.btn-primary{background:var(--accent);color:#fff}.btn-primary:hover{background:#2563eb}
.btn-sm{padding:5px 12px;font-size:.8rem}
.btn-success{background:#22c55e;color:#fff}.btn-success:hover{background:#16a34a}
.btn-warning{background:#f59e0b;color:#000}.btn-warning:hover{background:#d97706}
.btn-danger{background:#ef4444;color:#fff}.btn-danger:hover{background:#dc2626}
/* Events table */
.ev-table{width:100%;border-collapse:collapse;font-size:.85rem}
.ev-table th{text-align:left;color:var(--dim);padding:6px 8px;border-bottom:1px solid var(--border)}
.ev-table td{padding:6px 8px;border-bottom:1px solid rgba(51,65,85,.4)}
.ev-pos{color:#22c55e;font-weight:600}.ev-neg{color:#ef4444;font-weight:600}
/* Unit bars */
.unit-row{display:flex;align-items:center;gap:10px;margin-bottom:8px}
.unit-label{width:100px;font-size:.82rem;text-align:right}
.unit-bar{flex:1;height:18px;background:var(--bg);border-radius:4px;overflow:hidden;position:relative}
.unit-fill{height:100%;border-radius:4px}
.unit-count{font-size:.78rem;color:var(--dim);width:30px}
</style>

<main>
<div class="psy-wrap">
    <h1 class="psy-title"><i class="fas fa-brain"></i> Psychological Operations</h1>
    <p class="psy-sub">PSYOPS Division — Morale tracking, campaign management, and information warfare.</p>

    <?php if ($flash): ?>
        <div class="flash flash-<?php echo $flashType; ?>"><?php echo htmlspecialchars($flash); ?></div>
    <?php endif; ?>

    <div class="psy-grid">
        <!-- ═══ Personal Morale ═══ -->
        <div class="card">
            <div class="card-head"><i class="fas fa-heart-pulse"></i> Personal Morale</div>
            <?php
            $ms = (int)$morale['morale_score'];
            $fl = (int)$morale['fatigue_level'];
            $mot = $morale['motivation'];
            $streak = (int)$morale['streak_days'];
            $circ = 2 * M_PI * 50;
            $off = $circ * (1 - $ms / 100);
            $mColor = $ms >= 80 ? '#22c55e' : ($ms >= 50 ? '#f59e0b' : '#ef4444');
            ?>
            <div class="gauge-wrap">
                <div class="gauge">
                    <svg viewBox="0 0 120 120">
                        <circle class="bg" cx="60" cy="60" r="50"/>
                        <circle class="fg" cx="60" cy="60" r="50" stroke="<?php echo $mColor; ?>" stroke-dasharray="<?php echo round($circ,1); ?>" stroke-dashoffset="<?php echo round($off,1); ?>"/>
                    </svg>
                    <div class="gauge-val" style="color:<?php echo $mColor; ?>"><?php echo $ms; ?></div>
                </div>
                <div>
                    <div style="font-size:1.3rem;font-weight:700">Morale Score</div>
                    <div class="badge" style="background:<?php echo $motivColors[$mot] ?? '#3b82f6'; ?>;color:#fff;margin-top:6px"><?php echo htmlspecialchars(ucfirst(str_replace('_',' ',$mot))); ?></div>
                </div>
            </div>
            <div class="bar-label"><span>Fatigue</span><span><?php echo $fl; ?>%</span></div>
            <div class="bar-wrap"><div class="bar-fill" style="width:<?php echo $fl; ?>%;background:<?php echo $fl>60?'#ef4444':($fl>30?'#f59e0b':'#22c55e'); ?>"></div></div>
            <div class="stat-row">
                <div class="stat-pill"><i class="fas fa-fire"></i> Streak: <?php echo $streak; ?> days</div>
                <div class="stat-pill"><i class="fas fa-clock"></i> Last: <?php echo $morale['last_action_at'] ? date('M j, g:ia', strtotime($morale['last_action_at'])) : 'N/A'; ?></div>
            </div>
            <?php
            $canBoostNow = true;
            if ($morale['last_boost_at']) {
                $canBoostNow = (time() - strtotime($morale['last_boost_at'])) >= 86400;
            }
            ?>
            <form method="post" style="margin-top:14px">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="action" value="boost_morale">
                <button type="submit" class="btn btn-success" <?php echo $canBoostNow ? '' : 'disabled title="Available tomorrow"'; ?>>
                    <i class="fas fa-arrow-up"></i> <?php echo $canBoostNow ? 'Boost Morale (+5)' : 'Boosted Today'; ?>
                </button>
            </form>
        </div>

        <!-- ═══ Unit Morale Overview ═══ -->
        <div class="card">
            <div class="card-head"><i class="fas fa-users"></i> Unit Morale Overview</div>
            <div style="display:flex;align-items:center;gap:18px;margin-bottom:16px">
                <div style="font-size:2.2rem;font-weight:700;color:<?php echo $unitAvg>=70?'#22c55e':($unitAvg>=40?'#f59e0b':'#ef4444'); ?>"><?php echo $unitAvg; ?></div>
                <div>
                    <div style="font-weight:600">Average Morale</div>
                    <div style="color:var(--dim);font-size:.85rem"><?php echo $unitCount; ?> tracked personnel</div>
                </div>
            </div>
            <div style="font-size:.9rem;font-weight:600;margin-bottom:10px">Motivation Distribution</div>
            <?php
            $maxCnt = 1;
            foreach ($motDist as $md) { if ($md['cnt'] > $maxCnt) $maxCnt = $md['cnt']; }
            foreach ($motDist as $md):
                $pct = round($md['cnt'] / $maxCnt * 100);
                $c = $motivColors[$md['motivation']] ?? '#3b82f6';
            ?>
            <div class="unit-row">
                <div class="unit-label"><?php echo htmlspecialchars(ucfirst(str_replace('_',' ',$md['motivation']))); ?></div>
                <div class="unit-bar"><div class="unit-fill" style="width:<?php echo $pct; ?>%;background:<?php echo $c; ?>"></div></div>
                <div class="unit-count"><?php echo (int)$md['cnt']; ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ═══ Campaigns ═══ -->
    <div class="card" style="margin-top:20px">
        <div class="card-head"><i class="fas fa-satellite-dish"></i> PSYOPS Campaigns</div>
        <?php if (empty($campaigns)): ?>
            <p style="color:var(--dim)">No campaigns on record.</p>
        <?php else: ?>
        <div class="camp-list">
            <?php foreach ($campaigns as $c):
                $icon = $typeIcons[$c['campaign_type']] ?? 'fa-flag';
                $sc = $statusColors[$c['status']] ?? '#6366f1';
                $eff = (int)$c['effectiveness_score'];
            ?>
            <div class="camp-card">
                <div class="camp-top">
                    <i class="fas <?php echo htmlspecialchars($icon); ?>" style="color:<?php echo $sc; ?>"></i>
                    <span class="camp-name"><?php echo htmlspecialchars($c['name']); ?></span>
                    <span class="badge" style="background:<?php echo $sc; ?>;color:#fff"><?php echo htmlspecialchars($c['status']); ?></span>
                </div>
                <div class="bar-label"><span>Effectiveness</span><span><?php echo $eff; ?>%</span></div>
                <div class="bar-wrap"><div class="bar-fill" style="width:<?php echo $eff; ?>%;background:var(--accent)"></div></div>
                <div class="camp-meta">
                    <i class="fas fa-crosshairs"></i> <?php echo htmlspecialchars($c['target_audience'] ?: 'All personnel'); ?>
                    &nbsp;·&nbsp;<i class="fas fa-signal"></i> Reach: <?php echo number_format((int)$c['reach_count']); ?>
                    <?php if ((int)$c['xp_reward'] > 0): ?>
                        &nbsp;·&nbsp;<i class="fas fa-star"></i> <?php echo number_format((int)$c['xp_reward']); ?> XP
                    <?php endif; ?>
                    <?php if ($c['start_date']): ?>
                        &nbsp;·&nbsp;<i class="fas fa-calendar"></i> <?php echo htmlspecialchars($c['start_date']); ?>
                    <?php endif; ?>
                </div>
                <?php if ($isOfficer && in_array($c['status'], ['planning','active'], true)): ?>
                <form method="post" style="margin-top:8px;display:flex;gap:6px">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="action" value="activate_campaign">
                    <input type="hidden" name="campaign_id" value="<?php echo (int)$c['id']; ?>">
                    <?php if ($c['status'] === 'planning'): ?>
                        <button type="submit" name="new_status" value="active" class="btn btn-sm btn-success"><i class="fas fa-play"></i> Activate</button>
                    <?php endif; ?>
                    <?php if ($c['status'] === 'active'): ?>
                        <button type="submit" name="new_status" value="completed" class="btn btn-sm btn-primary"><i class="fas fa-check"></i> Complete</button>
                    <?php endif; ?>
                    <button type="submit" name="new_status" value="cancelled" class="btn btn-sm btn-danger"><i class="fas fa-ban"></i> Cancel</button>
                </form>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="psy-grid" style="margin-top:20px">
        <!-- ═══ Create Campaign (Officers+) ═══ -->
        <?php if ($isOfficer): ?>
        <div class="card">
            <div class="card-head"><i class="fas fa-plus-circle"></i> Create Campaign</div>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="action" value="create_campaign">
                <div class="form-group">
                    <label>Campaign Name</label>
                    <input type="text" name="cname" required maxlength="200" placeholder="Operation name…">
                </div>
                <div class="form-group">
                    <label>Type</label>
                    <select name="ctype">
                        <option value="morale_boost">🫀 Morale Boost</option>
                        <option value="propaganda">📢 Propaganda</option>
                        <option value="counter_intel">🕵️ Counter Intel</option>
                        <option value="information" selected>📰 Information</option>
                        <option value="deception">🎭 Deception</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Target Audience</label>
                    <input type="text" name="ctarget" maxlength="255" placeholder="e.g., All NCOs, Dept: Security…">
                </div>
                <div class="form-group">
                    <label>Message / Brief</label>
                    <textarea name="cmessage" required maxlength="2000" placeholder="Campaign message or directive…"></textarea>
                </div>
                <div class="form-group">
                    <label>XP Reward (0–5000)</label>
                    <input type="number" name="cxp" min="0" max="5000" value="0">
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-rocket"></i> Launch Campaign</button>
            </form>
        </div>
        <?php endif; ?>

        <!-- ═══ Morale History ═══ -->
        <div class="card">
            <div class="card-head"><i class="fas fa-clock-rotate-left"></i> Morale History</div>
            <?php if (empty($events)): ?>
                <p style="color:var(--dim)">No morale events recorded yet.</p>
            <?php else: ?>
            <div style="max-height:340px;overflow-y:auto">
                <table class="ev-table">
                    <thead><tr><th>Event</th><th>Change</th><th>Reason</th><th>When</th></tr></thead>
                    <tbody>
                    <?php foreach ($events as $ev):
                        $ch = (int)$ev['morale_change'];
                        $cls = $ch >= 0 ? 'ev-pos' : 'ev-neg';
                        $sign = $ch >= 0 ? '+' : '';
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars(ucfirst(str_replace('_',' ',$ev['event_type']))); ?></td>
                        <td class="<?php echo $cls; ?>"><?php echo $sign . $ch; ?></td>
                        <td><?php echo htmlspecialchars($ev['reason']); ?></td>
                        <td style="color:var(--dim);white-space:nowrap"><?php echo date('M j, g:ia', strtotime($ev['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</main>

<?php require_once __DIR__ . '/includes/site-footer.inc.php'; ?>
