<?php
/**
 * XP Leaderboard — Level 3 Military Rank System
 * Public leaderboard showing top XP earners and their ranks.
 */
require_once __DIR__ . '/includes/auth-gate.inc.php';
require_once __DIR__ . '/includes/rank-guard.inc.php';

$db = getSharedDB();

// Get leaderboard data
$leaders = getXPLeaderboard(50);

// Get total enlisted count
$totalEnlisted = (int)$db->query("SELECT COUNT(*) FROM user_ranks WHERE is_active = 1")->fetchColumn();
$totalXP = (int)$db->query("SELECT COALESCE(SUM(xp),0) FROM user_ranks WHERE is_active = 1")->fetchColumn();

// Get rank distribution
$rankDist = $db->query("
    SELECT mr.rank_name, mr.rank_tier, mr.badge_icon, mr.rank_group, COUNT(ur.id) as count
    FROM military_ranks mr
    LEFT JOIN user_ranks ur ON ur.rank_code = mr.rank_code AND ur.is_active = 1
    GROUP BY mr.rank_code
    ORDER BY mr.rank_tier ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Current user's position
$myPosition = 0;
if (!empty($clientId)) {
    $posStmt = $db->prepare("
        SELECT COUNT(*) + 1 FROM user_ranks
        WHERE is_active = 1 AND xp > COALESCE((SELECT xp FROM user_ranks WHERE client_id = ? AND is_active = 1),0)
    ");
    $posStmt->execute([$clientId]);
    $myPosition = (int)$posStmt->fetchColumn();
}

$pageTitle = 'XP Leaderboard — GoSiteMe Military';
include __DIR__ . '/includes/site-header.inc.php';
?>
<style>
.lb-page{max-width:1000px;margin:0 auto;padding:2rem 1.5rem}
.lb-hero{text-align:center;margin-bottom:2.5rem}
.lb-hero h1{font-size:2.2rem;color:#e2b340;font-weight:800;margin-bottom:.5rem}
.lb-hero .subtitle{color:#888;font-size:1rem}
.lb-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:2.5rem}
.lb-stat{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:10px;padding:1.2rem;text-align:center}
.lb-stat .val{font-size:1.8rem;color:#e2b340;font-weight:800}
.lb-stat .lbl{color:#888;font-size:.8rem;text-transform:uppercase;letter-spacing:1px;margin-top:.3rem}
.lb-table{width:100%;border-collapse:separate;border-spacing:0;margin-bottom:2rem}
.lb-table th{background:rgba(226,179,64,.15);color:#e2b340;padding:.8rem 1rem;text-align:left;font-size:.75rem;text-transform:uppercase;letter-spacing:1px}
.lb-table th:first-child{border-radius:8px 0 0 0}
.lb-table th:last-child{border-radius:0 8px 0 0}
.lb-table td{padding:.7rem 1rem;border-bottom:1px solid rgba(255,255,255,.06);color:#ccc;font-size:.9rem}
.lb-table tr:hover td{background:rgba(226,179,64,.05)}
.lb-table .pos{color:#e2b340;font-weight:800;font-size:1rem;width:40px}
.lb-table .pos-1{color:#ffd700;font-size:1.2rem}
.lb-table .pos-2{color:#c0c0c0;font-size:1.1rem}
.lb-table .pos-3{color:#cd7f32;font-size:1.1rem}
.lb-table .name{font-weight:600}
.lb-table .xp-val{color:#e2b340;font-weight:700;font-family:'JetBrains Mono',monospace}
.lb-table .me td{background:rgba(226,179,64,.1);border-left:3px solid #e2b340}
.rank-dist{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:.8rem;margin-bottom:2rem}
.rd-card{background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06);border-radius:8px;padding:.8rem;text-align:center}
.rd-card .rd-icon{font-size:1.3rem;margin-bottom:.3rem}
.rd-card .rd-name{color:#ccc;font-size:.75rem;font-weight:600;text-transform:uppercase}
.rd-card .rd-count{color:#e2b340;font-size:1.2rem;font-weight:800}
.group-enlisted .rd-icon{color:#4a6741}
.group-nco .rd-icon{color:#5a7a50}
.group-officer .rd-icon{color:#2a5a8a}
.group-flag .rd-icon{color:#8a2a2a}
.group-supreme .rd-icon{color:#e2b340}
</style>

<main class="main-content">
<div class="lb-page">

    <div class="lb-hero">
        <h1>&#x1F3C6; XP Leaderboard</h1>
        <p class="subtitle">Top soldiers of the GoSiteMe Sovereign Military — ranked by experience points</p>
    </div>

    <div class="lb-stats">
        <div class="lb-stat">
            <div class="val"><?= number_format($totalEnlisted) ?></div>
            <div class="lbl">Total Enlisted</div>
        </div>
        <div class="lb-stat">
            <div class="val"><?= number_format($totalXP) ?></div>
            <div class="lbl">Total XP Earned</div>
        </div>
        <?php if ($myPosition > 0): ?>
        <div class="lb-stat">
            <div class="val">#<?= $myPosition ?></div>
            <div class="lbl">Your Position</div>
        </div>
        <?php endif; ?>
        <div class="lb-stat">
            <div class="val"><?= count($leaders) ?></div>
            <div class="lbl">Active Soldiers</div>
        </div>
    </div>

    <h2 style="color:#e2b340;font-size:1.1rem;margin-bottom:1rem">&#x2B50; Rank Distribution</h2>
    <div class="rank-dist">
        <?php foreach ($rankDist as $rd): ?>
        <div class="rd-card group-<?= htmlspecialchars($rd['rank_group']) ?>">
            <div class="rd-icon"><i class="fa-solid <?= htmlspecialchars($rd['badge_icon'] ?: 'fa-circle') ?>"></i></div>
            <div class="rd-name"><?= htmlspecialchars($rd['rank_name']) ?></div>
            <div class="rd-count"><?= $rd['count'] ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <h2 style="color:#e2b340;font-size:1.1rem;margin-bottom:1rem">&#x1F947; Top 50</h2>

    <?php if (empty($leaders)): ?>
        <div style="text-align:center;padding:3rem;color:#666">
            <div style="font-size:3rem;margin-bottom:1rem">&#x1F6E1;&#xFE0F;</div>
            <p>No soldiers enlisted yet. <a href="/enlist" style="color:#e2b340">Be the first!</a></p>
        </div>
    <?php else: ?>
    <table class="lb-table">
        <thead>
            <tr><th>#</th><th>Soldier</th><th>Rank</th><th>Division</th><th>XP</th></tr>
        </thead>
        <tbody>
        <?php foreach ($leaders as $i => $l):
            $pos = $i + 1;
            $posClass = $pos <= 3 ? " pos-{$pos}" : '';
            $isMe = (!empty($clientId) && (int)$l['client_id'] === $clientId);
            $name = trim(($l['firstname'] ?? '') . ' ' . ($l['lastname'] ?? '')) ?: "Soldier #{$l['client_id']}";
            $medal = $pos === 1 ? '&#x1F947;' : ($pos === 2 ? '&#x1F948;' : ($pos === 3 ? '&#x1F949;' : ''));
        ?>
            <tr class="<?= $isMe ? 'me' : '' ?>">
                <td class="pos<?= $posClass ?>"><?= $medal ?: $pos ?></td>
                <td class="name"><?= htmlspecialchars($name) ?><?= $isMe ? ' <small style="color:#e2b340">(you)</small>' : '' ?></td>
                <td><span class="rank-badge" style="background:<?= ['enlisted'=>'#4a6741','nco'=>'#5a7a50','officer'=>'#2a5a8a','flag'=>'#8a2a2a','supreme'=>'#e2b340'][$l['rank_group'] ?? 'enlisted'] ?? '#444' ?>;color:#fff;padding:2px 8px;border-radius:3px;font-size:.7rem;font-weight:700;text-transform:uppercase"><?= htmlspecialchars($l['rank_name']) ?></span></td>
                <td style="color:#888;font-size:.8rem">T<?= $l['rank_tier'] ?></td>
                <td class="xp-val"><?= number_format($l['xp'] ?? 0) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <div style="text-align:center;margin-top:2rem">
        <?php if (empty($clientId)): ?>
            <a href="/alfred-ide-auth.php?redirect=/enlist" style="color:#e2b340;text-decoration:underline">Log in &amp; Enlist to join the leaderboard</a>
        <?php elseif ($userRankTier < 1): ?>
            <a href="/enlist" style="color:#e2b340;text-decoration:underline">Enlist now to start earning XP</a>
        <?php else: ?>
            <a href="/missions" style="color:#e2b340;text-decoration:underline">Complete missions to earn more XP →</a>
        <?php endif; ?>
    </div>

</div>
</main>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
