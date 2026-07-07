<?php
/**
 * Organizational Units — Level 3 Military Structure
 * View chain of command, join units, see unit rosters.
 */
require_once __DIR__ . '/includes/auth-gate.inc.php';
require_once __DIR__ . '/includes/rank-guard.inc.php';

$db = getSharedDB();

// Handle join/leave actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($clientId) && $userRankTier >= 1) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (hash_equals($_SESSION['csrf_units'] ?? '', $csrf)) {
        $action = $_POST['action'] ?? '';
        $unitId = (int)($_POST['unit_id'] ?? 0);

        if ($action === 'join' && $unitId > 0) {
            // Check unit exists and has space
            $unit = $db->prepare("SELECT * FROM military_units WHERE id = ? AND is_active = 1");
            $unit->execute([$unitId]);
            $u = $unit->fetch(PDO::FETCH_ASSOC);

            if ($u) {
                $currentCount = $db->prepare("SELECT COUNT(*) FROM unit_members WHERE unit_id = ? AND is_active = 1");
                $currentCount->execute([$unitId]);
                if ($currentCount->fetchColumn() < $u['max_members']) {
                    // Check not already in a unit of same type
                    $alreadyIn = $db->prepare("
                        SELECT um.id FROM unit_members um
                        JOIN military_units mu ON mu.id = um.unit_id
                        WHERE um.client_id = ? AND um.is_active = 1 AND mu.unit_type = ?
                    ");
                    $alreadyIn->execute([$clientId, $u['unit_type']]);
                    if (!$alreadyIn->fetch()) {
                        $db->prepare("INSERT INTO unit_members (unit_id, client_id, role, is_active) VALUES (?, ?, 'member', 1)")
                           ->execute([$unitId, $clientId]);

                        $db->prepare("INSERT INTO military_notifications (client_id, notification_type, title, message, data) VALUES (?, 'unit_assigned', ?, ?, ?)")
                           ->execute([$clientId, "Assigned to {$u['unit_name']}", "You have joined {$u['unit_name']} ({$u['unit_type']})",
                               json_encode(['unit_id' => $unitId, 'unit_name' => $u['unit_name']])]);
                    }
                }
            }
        }

        if ($action === 'leave' && $unitId > 0) {
            $db->prepare("UPDATE unit_members SET is_active = 0 WHERE unit_id = ? AND client_id = ?")
               ->execute([$unitId, $clientId]);
        }
    }
    header('Location: /units');
    exit;
}

// CSRF
if (empty($_SESSION['csrf_units'])) {
    $_SESSION['csrf_units'] = bin2hex(random_bytes(32));
}

// Load all units with member counts
$units = $db->query("
    SELECT mu.*, COUNT(um.id) as member_count,
           (SELECT unit_name FROM military_units WHERE id = mu.parent_unit_id) as parent_name
    FROM military_units mu
    LEFT JOIN unit_members um ON um.unit_id = mu.id AND um.is_active = 1
    WHERE mu.is_active = 1
    GROUP BY mu.id
    ORDER BY FIELD(mu.unit_type, 'army','corps','division','regiment','battalion','company','platoon','squad'), mu.unit_name
")->fetchAll(PDO::FETCH_ASSOC);

// User's current units
$myUnits = [];
if (!empty($clientId)) {
    $muStmt = $db->prepare("SELECT unit_id FROM unit_members WHERE client_id = ? AND is_active = 1");
    $muStmt->execute([$clientId]);
    $myUnits = $muStmt->fetchAll(PDO::FETCH_COLUMN);
}

// Build hierarchy
$hierarchy = [];
foreach ($units as $u) {
    $hierarchy[$u['unit_type']][] = $u;
}

$pageTitle = 'Military Units — GoSiteMe';
include __DIR__ . '/includes/site-header.inc.php';
?>
<style>
.units-page{max-width:1000px;margin:0 auto;padding:2rem 1.5rem}
.units-hero{text-align:center;margin-bottom:2rem}
.units-hero h1{font-size:2.2rem;color:#e2b340;font-weight:800;margin-bottom:.3rem}
.units-hero .sub{color:#888;font-size:.95rem}
.unit-type-header{color:#e2b340;font-size:1rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;margin:2rem 0 .8rem;padding-bottom:.3rem;border-bottom:1px solid rgba(226,179,64,.2);display:flex;justify-content:space-between;align-items:center}
.unit-type-header .count{color:#666;font-size:.75rem;font-weight:400}
.unit-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem}
.unit-card{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.08);border-radius:12px;padding:1.3rem;transition:all .3s}
.unit-card:hover{border-color:rgba(226,179,64,.3);transform:translateY(-2px)}
.unit-card.my-unit{border-color:rgba(76,175,80,.3);background:rgba(76,175,80,.05)}
.unit-icon{font-size:1.5rem;margin-bottom:.5rem}
.unit-name{color:#eee;font-size:1rem;font-weight:700;margin-bottom:.3rem}
.unit-meta{display:flex;gap:.8rem;flex-wrap:wrap;font-size:.75rem;color:#888;margin-bottom:.5rem}
.unit-bar{height:6px;background:rgba(255,255,255,.06);border-radius:3px;margin-bottom:.8rem;overflow:hidden}
.unit-bar-fill{height:100%;background:#e2b340;border-radius:3px;transition:width .5s}
.unit-btn{padding:.4rem 1rem;border:none;border-radius:5px;font-weight:700;cursor:pointer;font-size:.75rem;transition:transform .2s}
.unit-btn:hover{transform:scale(1.05)}
.unit-btn.join{background:#e2b340;color:#111}
.unit-btn.leave{background:rgba(244,67,54,.2);color:#ef5350;border:1px solid rgba(244,67,54,.3)}
.unit-btn.full{background:#333;color:#666;cursor:not-allowed}
.chain-info{background:rgba(226,179,64,.06);border:1px solid rgba(226,179,64,.15);border-radius:10px;padding:1.2rem;margin-bottom:2rem;text-align:center}
.chain-info p{color:#999;font-size:.85rem;margin:0}
.chain-arrow{color:#e2b340;font-size:1.2rem;margin:0 .5rem}
</style>

<main class="main-content">
<div class="units-page">

    <div class="units-hero">
        <h1>&#x1F3DB;&#xFE0F; Military Units</h1>
        <p class="sub">Chain of command — from squad to army</p>
    </div>

    <div class="chain-info">
        <p>
            <strong style="color:#e2b340">Squad</strong> <span class="chain-arrow">→</span>
            <strong style="color:#ccc">Platoon</strong> <span class="chain-arrow">→</span>
            <strong style="color:#ccc">Company</strong> <span class="chain-arrow">→</span>
            <strong style="color:#ccc">Battalion</strong> <span class="chain-arrow">→</span>
            <strong style="color:#888">Regiment</strong> <span class="chain-arrow">→</span>
            <strong style="color:#888">Division</strong> <span class="chain-arrow">→</span>
            <strong style="color:#666">Corps</strong> <span class="chain-arrow">→</span>
            <strong style="color:#555">Army</strong>
        </p>
    </div>

    <?php
    $typeIcons = ['squad'=>'&#x1F6E1;','platoon'=>'&#x1F465;','company'=>'&#x1F3E2;','battalion'=>'&#x1F3F0;',
                  'regiment'=>'&#x2694;','division'=>'&#x1F30D;','corps'=>'&#x1F3DB;','army'=>'&#x1F451;'];
    $typeLabels = ['squad'=>'Squads','platoon'=>'Platoons','company'=>'Companies','battalion'=>'Battalions',
                   'regiment'=>'Regiments','division'=>'Divisions','corps'=>'Corps','army'=>'Armies'];

    foreach (['battalion','company','platoon','squad'] as $type):
        if (empty($hierarchy[$type])) continue;
    ?>
    <div class="unit-type-header">
        <span><?= $typeIcons[$type] ?? '' ?> <?= $typeLabels[$type] ?? ucfirst($type) ?></span>
        <span class="count"><?= count($hierarchy[$type]) ?> units</span>
    </div>
    <div class="unit-grid">
        <?php foreach ($hierarchy[$type] as $u):
            $isMyUnit = in_array($u['id'], $myUnits);
            $isFull = $u['member_count'] >= $u['max_members'];
            $pct = $u['max_members'] > 0 ? round(($u['member_count'] / $u['max_members']) * 100) : 0;
        ?>
        <div class="unit-card <?= $isMyUnit ? 'my-unit' : '' ?>">
            <div class="unit-icon"><i class="fa-solid <?= htmlspecialchars($u['icon'] ?: 'fa-shield') ?>"></i></div>
            <div class="unit-name"><?= htmlspecialchars($u['unit_name']) ?><?= $isMyUnit ? ' <small style="color:#4caf50">(your unit)</small>' : '' ?></div>
            <div class="unit-meta">
                <span><?= $u['member_count'] ?>/<?= $u['max_members'] ?> members</span>
                <?php if ($u['parent_name']): ?>
                    <span>Part of: <?= htmlspecialchars($u['parent_name']) ?></span>
                <?php endif; ?>
                <?php if ($u['motto']): ?>
                    <span>"<?= htmlspecialchars($u['motto']) ?>"</span>
                <?php endif; ?>
            </div>
            <div class="unit-bar"><div class="unit-bar-fill" style="width:<?= $pct ?>%"></div></div>
            <?php if (!empty($clientId) && $userRankTier >= 1): ?>
                <?php if ($isMyUnit): ?>
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_units']) ?>">
                        <input type="hidden" name="unit_id" value="<?= $u['id'] ?>">
                        <input type="hidden" name="action" value="leave">
                        <button type="submit" class="unit-btn leave">Leave Unit</button>
                    </form>
                <?php elseif ($isFull): ?>
                    <span class="unit-btn full">Full</span>
                <?php else: ?>
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_units']) ?>">
                        <input type="hidden" name="unit_id" value="<?= $u['id'] ?>">
                        <input type="hidden" name="action" value="join">
                        <button type="submit" class="unit-btn join">Join Unit</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>

    <div style="text-align:center;margin-top:2rem;padding-bottom:2rem">
        <a href="/military-hq" style="color:#e2b340;text-decoration:underline">← Back to Military HQ</a>
        &nbsp;|&nbsp;
        <a href="/missions" style="color:#e2b340;text-decoration:underline">Mission Board →</a>
    </div>

</div>
</main>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
