TempTempTempTempTempTemp76577#<?php
/**
 * Veterans Affairs & Service Records — GoSiteMe Military Ecosystem
 * Manages service records, memorial wall, veteran benefits, and service stats.
 */
require_once __DIR__ . '/includes/db-config.inc.php';
$db = getSharedDB();
require_once __DIR__ . '/includes/auth-gate.inc.php';
require_once __DIR__ . '/includes/rank-guard.inc.php';

requireRank(1);

if (empty($_SESSION['csrf_veterans'])) {
    $_SESSION['csrf_veterans'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_veterans'];
$isCommander = ($clientId === 33);
$msg = '';
$msgType = '';

// --- Ensure DB tables exist ---
$db->exec("CREATE TABLE IF NOT EXISTS service_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL UNIQUE,
    service_number VARCHAR(20) NOT NULL,
    enlistment_date DATE,
    discharge_date DATE DEFAULT NULL,
    discharge_type ENUM('active','honorable','general','other_than_honorable','dishonorable','medical') DEFAULT 'active',
    total_xp_earned INT DEFAULT 0,
    total_missions INT DEFAULT 0,
    total_battles INT DEFAULT 0,
    total_decorations INT DEFAULT 0,
    highest_rank_achieved VARCHAR(50) DEFAULT '',
    time_in_service_days INT DEFAULT 0,
    combat_tours INT DEFAULT 0,
    injuries_sustained INT DEFAULT 0,
    units_served JSON DEFAULT NULL,
    notable_achievements TEXT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS memorial_wall (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT DEFAULT NULL,
    name VARCHAR(120) NOT NULL,
    rank_at_event VARCHAR(50) DEFAULT '',
    memorial_type ENUM('fallen','retired','honored','legendary') DEFAULT 'honored',
    inscription TEXT,
    service_dates VARCHAR(100) DEFAULT '',
    added_by INT NOT NULL,
    approved TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS veteran_benefits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    benefit_name VARCHAR(120) NOT NULL,
    description TEXT,
    benefit_type ENUM('discount','access','title','cosmetic','resource') DEFAULT 'access',
    min_service_days INT DEFAULT 0,
    min_rank_tier INT DEFAULT 0,
    value_amount DECIMAL(10,2) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// --- Helper: gather fresh stats for a client ---
function gatherServiceStats(PDO $db, int $cid): array {
    $xp = 0; $rankName = ''; $enlistDate = null; $rankTier = 0;
    $r = $db->prepare("SELECT ur.xp, ur.created_at, mr.rank_name, mr.rank_tier
        FROM user_ranks ur JOIN military_ranks mr ON ur.rank_code=mr.rank_code
        WHERE ur.client_id=? AND ur.is_active=1 ORDER BY mr.rank_tier DESC LIMIT 1");
    $r->execute([$cid]);
    if ($row = $r->fetch(PDO::FETCH_ASSOC)) {
        $xp = (int)$row['xp'];
        $rankName = $row['rank_name'];
        $rankTier = (int)$row['rank_tier'];
        $enlistDate = $row['created_at'];
    }
    $missions = 0;
    try {
        $m = $db->prepare("SELECT COUNT(*) FROM mission_assignments WHERE client_id=? AND status='completed'");
        $m->execute([$cid]);
        $missions = (int)$m->fetchColumn();
    } catch (Exception $e) {}
    $battles = 0;
    try {
        $b = $db->prepare("SELECT COUNT(*) FROM territory_battles WHERE attacker_id=? OR defender_id=?");
        $b->execute([$cid, $cid]);
        $battles = (int)$b->fetchColumn();
    } catch (Exception $e) {}
    $decorations = 0;
    try {
        $d = $db->prepare("SELECT COUNT(*) FROM decoration_nominations WHERE client_id=? AND status='awarded'");
        $d->execute([$cid]);
        $decorations = (int)$d->fetchColumn();
    } catch (Exception $e) {}
    $tis = $enlistDate ? (int)round((time() - strtotime($enlistDate)) / 86400) : 0;
    return [
        'xp' => $xp, 'rank_name' => $rankName, 'rank_tier' => $rankTier,
        'enlist_date' => $enlistDate ? date('Y-m-d', strtotime($enlistDate)) : date('Y-m-d'),
        'missions' => $missions, 'battles' => $battles, 'decorations' => $decorations,
        'time_in_service' => $tis,
    ];
}

// --- POST actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token = $_POST['csrf'] ?? '';
    if (!hash_equals($csrf, $token)) {
        $msg = 'Invalid security token. Refresh and try again.';
        $msgType = 'error';
    } else {
        switch ($action) {
            case 'refresh_record':
                $stats = gatherServiceStats($db, $clientId);
                $sn = 'GSM-' . str_pad($clientId, 5, '0', STR_PAD_LEFT);
                $u = $db->prepare("UPDATE service_records SET total_xp_earned=?, total_missions=?, total_battles=?,
                    total_decorations=?, highest_rank_achieved=?, time_in_service_days=?, updated_at=NOW()
                    WHERE client_id=?");
                $u->execute([$stats['xp'], $stats['missions'], $stats['battles'], $stats['decorations'],
                    $stats['rank_name'], $stats['time_in_service'], $clientId]);
                if ($u->rowCount() === 0) {
                    $ins = $db->prepare("INSERT INTO service_records (client_id, service_number, enlistment_date,
                        total_xp_earned, total_missions, total_battles, total_decorations, highest_rank_achieved,
                        time_in_service_days) VALUES (?,?,?,?,?,?,?,?,?)");
                    $ins->execute([$clientId, $sn, $stats['enlist_date'], $stats['xp'], $stats['missions'],
                        $stats['battles'], $stats['decorations'], $stats['rank_name'], $stats['time_in_service']]);
                }
                $msg = 'Service record updated.';
                $msgType = 'success';
                break;

            case 'add_memorial':
                if ($userRankTier < 9 && !$isCommander) {
                    $msg = 'Only flag officers (General+) may add memorial entries.';
                    $msgType = 'error';
                } else {
                    $mName = trim($_POST['mem_name'] ?? '');
                    $mRank = trim($_POST['mem_rank'] ?? '');
                    $mType = $_POST['mem_type'] ?? 'honored';
                    $mInsc = trim($_POST['mem_inscription'] ?? '');
                    $mDates = trim($_POST['mem_dates'] ?? '');
                    $allowed = ['fallen','retired','honored','legendary'];
                    if (!in_array($mType, $allowed, true)) $mType = 'honored';
                    if ($mName === '') {
                        $msg = 'Name is required.';
                        $msgType = 'error';
                    } else {
                        $approved = $isCommander ? 1 : 0;
                        $ins = $db->prepare("INSERT INTO memorial_wall (name, rank_at_event, memorial_type, inscription, service_dates, added_by, approved)
                            VALUES (?,?,?,?,?,?,?)");
                        $ins->execute([$mName, $mRank, $mType, $mInsc, $mDates, $clientId, $approved]);
                        $msg = $isCommander ? 'Memorial entry added and approved.' : 'Memorial entry submitted for Commander approval.';
                        $msgType = 'success';
                    }
                }
                break;

            case 'approve_memorial':
                if (!$isCommander) {
                    $msg = 'Only the Commander may approve memorial entries.';
                    $msgType = 'error';
                } else {
                    $memId = (int)($_POST['memorial_id'] ?? 0);
                    if ($memId > 0) {
                        $db->prepare("UPDATE memorial_wall SET approved=1 WHERE id=?")->execute([$memId]);
                        $msg = 'Memorial entry approved.';
                        $msgType = 'success';
                    }
                }
                break;
        }
    }
}

// --- Build / refresh own service record ---
$stats = gatherServiceStats($db, $clientId);
$sn = 'GSM-' . str_pad($clientId, 5, '0', STR_PAD_LEFT);
$sr = $db->prepare("SELECT * FROM service_records WHERE client_id=?");
$sr->execute([$clientId]);
$myRecord = $sr->fetch(PDO::FETCH_ASSOC);
if (!$myRecord) {
    $ins = $db->prepare("INSERT INTO service_records (client_id, service_number, enlistment_date,
        total_xp_earned, total_missions, total_battles, total_decorations, highest_rank_achieved,
        time_in_service_days) VALUES (?,?,?,?,?,?,?,?,?)");
    $ins->execute([$clientId, $sn, $stats['enlist_date'], $stats['xp'], $stats['missions'],
        $stats['battles'], $stats['decorations'], $stats['rank_name'], $stats['time_in_service']]);
    $sr->execute([$clientId]);
    $myRecord = $sr->fetch(PDO::FETCH_ASSOC);
}

// --- Memorial wall ---
$memorials = $db->query("SELECT * FROM memorial_wall WHERE approved=1 ORDER BY memorial_type='legendary' DESC, created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$pendingMemorials = [];
if ($isCommander) {
    $pendingMemorials = $db->query("SELECT * FROM memorial_wall WHERE approved=0 ORDER BY created_at ASC")->fetchAll(PDO::FETCH_ASSOC);
}

// --- Veteran benefits ---
$benefits = $db->query("SELECT * FROM veteran_benefits WHERE is_active=1 ORDER BY min_rank_tier ASC, min_service_days ASC")->fetchAll(PDO::FETCH_ASSOC);

// --- Service stats overview ---
$totalActive = (int)$db->query("SELECT COUNT(*) FROM service_records WHERE discharge_type='active'")->fetchColumn();
$avgService = (float)$db->query("SELECT COALESCE(AVG(time_in_service_days),0) FROM service_records WHERE discharge_type='active'")->fetchColumn();
$rankDistQ = $db->query("SELECT mr.rank_name, mr.rank_tier, COUNT(ur.id) as cnt
    FROM military_ranks mr LEFT JOIN user_ranks ur ON ur.rank_code=mr.rank_code AND ur.is_active=1
    GROUP BY mr.rank_code ORDER BY mr.rank_tier DESC");
$rankDist = $rankDistQ ? $rankDistQ->fetchAll(PDO::FETCH_ASSOC) : [];

$e = function($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };

$pageTitle = 'Veterans Affairs — GoSiteMe Military';
include __DIR__ . '/includes/site-header.inc.php';
?>
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<style>
:root{--vbg:#0f172a;--vcard:#1e293b;--vborder:#334155;--vtxt:#e2e8f0;--vaccent:#3b82f6;--vgold:#facc15;--vsilver:#94a3b8}
.vet-page{max-width:1100px;margin:0 auto;padding:2rem 1.5rem;color:var(--vtxt);font-family:system-ui,-apple-system,sans-serif}
.vet-hero{text-align:center;margin-bottom:2rem}.vet-hero h1{font-size:2rem;color:var(--vgold);font-weight:800;margin:0 0 .4rem}
.vet-hero .sub{color:var(--vsilver);font-size:.95rem}
.vet-msg{padding:.8rem 1.2rem;border-radius:8px;margin-bottom:1.5rem;font-size:.9rem}
.vet-msg.success{background:rgba(34,197,94,.12);border:1px solid #22c55e;color:#86efac}
.vet-msg.error{background:rgba(239,68,68,.12);border:1px solid #ef4444;color:#fca5a5}
.vet-section{margin-bottom:2.5rem}.vet-section h2{font-size:1.3rem;color:var(--vaccent);margin:0 0 1rem;display:flex;align-items:center;gap:.5rem}
.vet-card{background:var(--vcard);border:1px solid var(--vborder);border-radius:12px;padding:1.5rem;margin-bottom:1rem}
.vet-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:1rem;margin:1rem 0}
.vet-stat{text-align:center;padding:.8rem;background:rgba(255,255,255,.03);border-radius:8px;border:1px solid var(--vborder)}
.vet-stat .val{font-size:1.6rem;color:var(--vgold);font-weight:800}.vet-stat .lbl{font-size:.7rem;color:var(--vsilver);text-transform:uppercase;letter-spacing:1px;margin-top:.2rem}
.sr-header{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem;margin-bottom:1rem}
.sr-sn{color:var(--vgold);font-size:1.1rem;font-weight:700;font-family:monospace;letter-spacing:2px}
.sr-rank{background:var(--vaccent);color:#fff;padding:.3rem .8rem;border-radius:6px;font-size:.8rem;font-weight:700}
.sr-meta{display:grid;grid-template-columns:1fr 1fr;gap:.3rem .8rem;font-size:.85rem;color:var(--vsilver);margin:.5rem 0}
.sr-meta span{color:var(--vtxt);font-weight:600}
.btn{display:inline-flex;align-items:center;gap:.4rem;padding:.55rem 1.2rem;border:none;border-radius:8px;font-size:.85rem;font-weight:600;cursor:pointer;transition:opacity .2s}
.btn:hover{opacity:.85}.btn-primary{background:var(--vaccent);color:#fff}.btn-gold{background:var(--vgold);color:#0f172a}.btn-sm{padding:.4rem .8rem;font-size:.78rem}
.mem-entry{border-left:3px solid var(--vborder);padding:.8rem 1rem;margin-bottom:.8rem;background:rgba(255,255,255,.02);border-radius:0 8px 8px 0}
.mem-entry.legendary{border-left-color:var(--vgold);box-shadow:0 0 12px rgba(250,204,21,.15)}.mem-entry.honored{border-left-color:var(--vsilver)}
.mem-badge{display:inline-block;font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;padding:.2rem .5rem;border-radius:4px;margin-left:.4rem}
.mem-badge.fallen{background:#dc2626;color:#fff}.mem-badge.retired{background:#6366f1;color:#fff}.mem-badge.honored{background:var(--vsilver);color:#0f172a}.mem-badge.legendary{background:var(--vgold);color:#0f172a}
.mem-name{font-weight:700;color:var(--vtxt);font-size:1rem}.mem-rank{color:var(--vsilver);font-size:.8rem;margin-left:.3rem}
.mem-insc{color:var(--vsilver);font-style:italic;margin:.3rem 0;font-size:.85rem}.mem-dates{color:var(--vborder);font-size:.75rem}
.ben-table{width:100%;border-collapse:separate;border-spacing:0}.ben-table th{background:rgba(59,130,246,.15);color:var(--vaccent);padding:.6rem .8rem;text-align:left;font-size:.7rem;text-transform:uppercase;letter-spacing:1px}
.ben-table th:first-child{border-radius:8px 0 0 0}.ben-table th:last-child{border-radius:0 8px 0 0}
.ben-table td{padding:.6rem .8rem;border-bottom:1px solid var(--vborder);font-size:.85rem;color:var(--vtxt)}.ben-table tr:hover td{background:rgba(59,130,246,.05)}
.elig{display:inline-block;padding:.15rem .5rem;border-radius:4px;font-size:.7rem;font-weight:700;text-transform:uppercase}
.elig.yes{background:#22c55e;color:#fff}.elig.no{background:#475569;color:#94a3b8}
.form-group{margin-bottom:.8rem}.form-group label{display:block;font-size:.78rem;color:var(--vsilver);margin-bottom:.3rem;text-transform:uppercase;letter-spacing:.5px}
.form-group input,.form-group select,.form-group textarea{width:100%;padding:.5rem .7rem;border:1px solid var(--vborder);border-radius:6px;background:var(--vbg);color:var(--vtxt);font-size:.85rem}
.form-group textarea{resize:vertical;min-height:60px}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:.8rem}
.overview-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem}
.overview-card{background:rgba(255,255,255,.03);border:1px solid var(--vborder);border-radius:10px;padding:1rem;text-align:center}
.overview-card .oval{font-size:2rem;color:var(--vaccent);font-weight:800}.overview-card .olbl{color:var(--vsilver);font-size:.75rem;text-transform:uppercase;margin-top:.2rem}
.rank-bar{display:flex;align-items:center;gap:.5rem;margin:.3rem 0;font-size:.8rem}.rank-bar .bar{flex:1;height:6px;background:var(--vborder);border-radius:3px;overflow:hidden}
.rank-bar .fill{height:100%;background:var(--vaccent);border-radius:3px}
.pending-tag{background:#f59e0b;color:#0f172a;font-size:.65rem;font-weight:700;padding:.15rem .4rem;border-radius:3px;margin-left:.3rem}
</style>

<div class="vet-page">
<div class="vet-hero">
    <h1><i class="fas fa-medal"></i> Veterans Affairs</h1>
    <p class="sub">Service Records &bull; Memorial Wall &bull; Benefits</p>
</div>

<?php if ($msg): ?>
<div class="vet-msg <?= $e($msgType) ?>"><?= $e($msg) ?></div>
<?php endif; ?>

<!-- MY SERVICE RECORD -->
<div class="vet-section">
    <h2><i class="fas fa-id-card"></i> My Service Record</h2>
    <div class="vet-card">
        <div class="sr-header">
            <div class="sr-sn"><?= $e($myRecord['service_number']) ?></div>
            <div class="sr-rank"><?= $e($myRecord['highest_rank_achieved'] ?: $stats['rank_name']) ?></div>
        </div>
        <div class="sr-meta">
            <div>Enlisted: <span><?= $e($myRecord['enlistment_date']) ?></span></div>
            <div>Status: <span><?= $e(ucfirst($myRecord['discharge_type'])) ?></span></div>
            <div>Service: <span><?= number_format((int)$myRecord['time_in_service_days']) ?> days</span></div>
            <div>Name: <span><?= $e($clientName) ?></span></div>
        </div>
        <div class="vet-grid">
            <div class="vet-stat"><div class="val"><?= number_format((int)$myRecord['total_xp_earned']) ?></div><div class="lbl">Total XP</div></div>
            <div class="vet-stat"><div class="val"><?= (int)$myRecord['total_missions'] ?></div><div class="lbl">Missions</div></div>
            <div class="vet-stat"><div class="val"><?= (int)$myRecord['total_battles'] ?></div><div class="lbl">Battles</div></div>
            <div class="vet-stat"><div class="val"><?= (int)$myRecord['total_decorations'] ?></div><div class="lbl">Decorations</div></div>
            <div class="vet-stat"><div class="val"><?= (int)$myRecord['combat_tours'] ?></div><div class="lbl">Tours</div></div>
            <div class="vet-stat"><div class="val"><?= (int)$myRecord['injuries_sustained'] ?></div><div class="lbl">Injuries</div></div>
        </div>
        <form method="post" style="margin-top:.8rem;text-align:right">
            <input type="hidden" name="csrf" value="<?= $e($csrf) ?>">
            <input type="hidden" name="action" value="refresh_record">
            <button type="submit" class="btn btn-primary"><i class="fas fa-sync-alt"></i> Refresh Stats</button>
        </form>
    </div>
</div>

<!-- SERVICE STATS OVERVIEW -->
<div class="vet-section">
    <h2><i class="fas fa-chart-bar"></i> Service Stats Overview</h2>
    <div class="overview-grid">
        <div class="overview-card"><div class="oval"><?= number_format($totalActive) ?></div><div class="olbl">Active Personnel</div></div>
        <div class="overview-card"><div class="oval"><?= number_format((int)$avgService) ?></div><div class="olbl">Avg Service (days)</div></div>
        <div class="overview-card"><div class="oval"><?= count($memorials) ?></div><div class="olbl">Memorial Entries</div></div>
        <div class="overview-card"><div class="oval"><?= count($benefits) ?></div><div class="olbl">Active Benefits</div></div>
    </div>
    <?php if ($rankDist): ?>
    <div class="vet-card" style="margin-top:1rem">
        <h3 style="font-size:.9rem;color:var(--vaccent);margin:0 0 .8rem"><i class="fas fa-layer-group"></i> Rank Distribution</h3>
        <?php $maxCnt = max(array_column($rankDist, 'cnt') ?: [1]); foreach ($rankDist as $rd): ?>
        <div class="rank-bar">
            <span style="min-width:90px;color:var(--vtxt)"><?= $e($rd['rank_name']) ?></span>
            <div class="bar"><div class="fill" style="width:<?= $maxCnt > 0 ? round(((int)$rd['cnt'] / $maxCnt) * 100) : 0 ?>%"></div></div>
            <span style="min-width:25px;text-align:right;color:var(--vgold)"><?= (int)$rd['cnt'] ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- MEMORIAL WALL -->
<div class="vet-section">
    <h2><i class="fas fa-monument"></i> Memorial Wall</h2>
    <?php if (empty($memorials)): ?>
        <div class="vet-card" style="text-align:center;color:var(--vsilver)"><i class="fas fa-dove" style="font-size:2rem;margin-bottom:.5rem;display:block;color:var(--vborder)"></i>No memorial entries yet.</div>
    <?php else: ?>
        <?php foreach ($memorials as $mem): ?>
        <div class="mem-entry <?= $e($mem['memorial_type']) ?>">
            <div>
                <span class="mem-name"><?= $e($mem['name']) ?></span>
                <?php if ($mem['rank_at_event']): ?><span class="mem-rank">(<?= $e($mem['rank_at_event']) ?>)</span><?php endif; ?>
                <span class="mem-badge <?= $e($mem['memorial_type']) ?>"><?= $e($mem['memorial_type']) ?></span>
            </div>
            <?php if ($mem['inscription']): ?><div class="mem-insc">&ldquo;<?= $e($mem['inscription']) ?>&rdquo;</div><?php endif; ?>
            <?php if ($mem['service_dates']): ?><div class="mem-dates"><i class="fas fa-calendar-alt"></i> <?= $e($mem['service_dates']) ?></div><?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Pending memorials (Commander only) -->
    <?php if ($isCommander && $pendingMemorials): ?>
    <div class="vet-card" style="margin-top:1rem;border-color:#f59e0b">
        <h3 style="font-size:.9rem;color:#f59e0b;margin:0 0 .8rem"><i class="fas fa-clock"></i> Pending Approval (<?= count($pendingMemorials) ?>)</h3>
        <?php foreach ($pendingMemorials as $pm): ?>
        <div class="mem-entry" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
            <div>
                <span class="mem-name"><?= $e($pm['name']) ?></span>
                <span class="mem-badge <?= $e($pm['memorial_type']) ?>"><?= $e($pm['memorial_type']) ?></span>
                <?php if ($pm['inscription']): ?><div class="mem-insc">&ldquo;<?= $e($pm['inscription']) ?>&rdquo;</div><?php endif; ?>
            </div>
            <form method="post" style="margin:0">
                <input type="hidden" name="csrf" value="<?= $e($csrf) ?>">
                <input type="hidden" name="action" value="approve_memorial">
                <input type="hidden" name="memorial_id" value="<?= (int)$pm['id'] ?>">
                <button type="submit" class="btn btn-gold btn-sm"><i class="fas fa-check"></i> Approve</button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Add Memorial (flag officers+) -->
    <?php if ($userRankTier >= 9 || $isCommander): ?>
    <div class="vet-card" style="margin-top:1rem">
        <h3 style="font-size:.9rem;color:var(--vaccent);margin:0 0 .8rem"><i class="fas fa-plus-circle"></i> Add Memorial Entry</h3>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= $e($csrf) ?>">
            <input type="hidden" name="action" value="add_memorial">
            <div class="form-row">
                <div class="form-group"><label>Name</label><input type="text" name="mem_name" required maxlength="120"></div>
                <div class="form-group"><label>Rank at Event</label><input type="text" name="mem_rank" maxlength="50"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Memorial Type</label>
                    <select name="mem_type"><option value="honored">Honored</option><option value="legendary">Legendary</option><option value="retired">Retired</option><option value="fallen">Fallen</option></select>
                </div>
                <div class="form-group"><label>Service Dates</label><input type="text" name="mem_dates" placeholder="e.g. 2025-01 to 2026-04" maxlength="100"></div>
            </div>
            <div class="form-group"><label>Inscription</label><textarea name="mem_inscription" maxlength="1000" placeholder="Words to honor their service..."></textarea></div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-feather-alt"></i> Submit Memorial</button>
        </form>
    </div>
    <?php endif; ?>
</div>

<!-- VETERAN BENEFITS -->
<div class="vet-section">
    <h2><i class="fas fa-gift"></i> Veteran Benefits</h2>
    <?php if (empty($benefits)): ?>
        <div class="vet-card" style="text-align:center;color:var(--vsilver)">No benefits configured yet.</div>
    <?php else: ?>
    <div class="vet-card" style="overflow-x:auto">
        <table class="ben-table">
            <thead><tr><th>Benefit</th><th>Type</th><th>Requires</th><th>Value</th><th>Eligibility</th></tr></thead>
            <tbody>
            <?php foreach ($benefits as $ben):
                $eligible = ($myRecord['time_in_service_days'] >= $ben['min_service_days']) && ($userRankTier >= $ben['min_rank_tier']);
            ?>
            <tr>
                <td><strong><?= $e($ben['benefit_name']) ?></strong><br><span style="color:var(--vsilver);font-size:.78rem"><?= $e($ben['description']) ?></span></td>
                <td><span style="text-transform:capitalize"><?= $e($ben['benefit_type']) ?></span></td>
                <td style="font-size:.78rem"><?= (int)$ben['min_service_days'] ?> days<br>Tier <?= (int)$ben['min_rank_tier'] ?>+</td>
                <td><?= $ben['value_amount'] > 0 ? '$' . number_format((float)$ben['value_amount'], 2) : '—' ?></td>
                <td><span class="elig <?= $eligible ? 'yes' : 'no' ?>"><?= $eligible ? 'Eligible' : 'Not Yet' ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
</div>

<script>
document.querySelectorAll('form').forEach(f=>{
    f.addEventListener('submit',function(){
        const b=f.querySelector('button[type="submit"]');
        if(b){b.disabled=true;b.style.opacity='.5';}
    });
});
</script>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
