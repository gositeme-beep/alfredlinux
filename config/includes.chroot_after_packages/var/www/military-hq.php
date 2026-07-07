<?php
/**
 * Military HQ — Rank-based Dashboard Router
 * Every ranked user lands here and sees their tier-appropriate view.
 * Commander sees the Global Fleet Command Center.
 */
$page_title = 'Military HQ — GoSiteMe Command';
$page_description = 'Military headquarters and command dashboards.';
$page_canonical = 'https://root.com/military-hq';
$page_robots = 'noindex, nofollow';

require_once __DIR__ . '/includes/auth-gate.inc.php';
require_once __DIR__ . '/includes/rank-guard.inc.php';

if ($userRankTier === 0) {
    // Not ranked — show enlistment page
    include __DIR__ . '/includes/site-header.inc.php';
    ?>
    <main class="main-content" style="padding:4rem 1.5rem;text-align:center;max-width:700px;margin:auto;">
        <div style="font-size:4rem;margin-bottom:1rem;">&#x2694;</div>
        <h1 style="color:#e2b340;margin-bottom:.5rem;">Military Headquarters</h1>
        <p style="color:#999;margin-bottom:2rem;">You are not currently enlisted in the GoSiteMe Defense Force.</p>
        <p style="color:#666;">Contact your commanding officer or the Supreme Commander to be assigned a rank.</p>
        <a href="/" class="btn btn-outline" style="margin-top:1.5rem;">Return Home</a>
    </main>
    <?php
    include __DIR__ . '/includes/site-footer.inc.php';
    exit;
}

include __DIR__ . '/includes/site-header.inc.php';

$db = getSharedDB();

// Load user details
$userName = $clientName ?? 'Soldier';
$rankBadge = getUserRankBadge();
$rankStars = getRankTierStars($userRankTier);

// Roster stats — use fast approximate counts for massive tables
$totalPersonnel = (int)$db->query("SELECT COUNT(*) FROM user_ranks WHERE is_active = 1")->fetchColumn();

// agent_registry has 48M+ rows — use information_schema estimate
$totalAgents = 0;
$fleetCount = 0;
try {
    $estRows = $db->query("SELECT TABLE_NAME, TABLE_ROWS FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('alfred_agent_registry','fleet_passports')")->fetchAll(PDO::FETCH_KEY_PAIR);
    $totalAgents = (int)($estRows['alfred_agent_registry'] ?? 0);
    $fleetCount  = (int)($estRows['fleet_passports'] ?? 0);
} catch (Exception $e) {}

// Rank distribution for commanders
$rankDistribution = [];
if ($userRankTier >= 9) {
    $rankDistribution = $db->query("
        SELECT mr.rank_name, mr.rank_group, mr.rank_tier, COUNT(ur.id) as count
        FROM military_ranks mr LEFT JOIN user_ranks ur ON ur.rank_code = mr.rank_code AND ur.is_active = 1
        GROUP BY mr.rank_code ORDER BY mr.rank_tier ASC
    ")->fetchAll();
}

// Recent promotions for officers+
$recentPromotions = [];
if ($userRankTier >= 6) {
    $recentPromotions = $db->query("
        SELECT rh.*, CONCAT(tc.firstname, ' ', tc.lastname) AS subject_name, mr.rank_name AS to_rank_name
        FROM rank_history rh
        LEFT JOIN tblclients tc ON tc.id = rh.client_id
        LEFT JOIN military_ranks mr ON mr.rank_code = rh.to_rank
        ORDER BY rh.performed_at DESC LIMIT 10
    ")->fetchAll();
}
?>

<style>
.mhq { background:#0a0a14; min-height:100vh; color:#e0e0e0; }
.mhq-header { background:linear-gradient(135deg,#0d1117 0%,#161b22 50%,#0d1117 100%); border-bottom:1px solid #e2b34030; padding:2rem 1.5rem; }
.mhq-header-inner { max-width:1400px; margin:auto; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem; }
.mhq-identity { display:flex; align-items:center; gap:1rem; }
.mhq-avatar { width:56px; height:56px; border-radius:50%; background:#e2b34020; border:2px solid #e2b340; display:flex; align-items:center; justify-content:center; font-size:1.5rem; font-weight:700; color:#e2b340; }
.mhq-name { font-size:1.3rem; font-weight:700; color:#fff; }
.mhq-meta { font-size:.85rem; color:#888; margin-top:2px; }
.mhq-stars { color:#e2b340; font-size:1.2rem; letter-spacing:2px; }
.mhq-grid { max-width:1400px; margin:2rem auto; padding:0 1.5rem; display:grid; grid-template-columns:repeat(auto-fill,minmax(340px,1fr)); gap:1.25rem; }
.mhq-card { background:#12151e; border:1px solid #222; border-radius:12px; padding:1.5rem; transition:border-color .2s; }
.mhq-card:hover { border-color:#e2b34060; }
.mhq-card h3 { color:#e2b340; font-size:1rem; margin-bottom:1rem; text-transform:uppercase; letter-spacing:1px; font-weight:600; }
.mhq-card h3 span { float:right; font-size:.75rem; color:#666; text-transform:none; letter-spacing:0; }
.mhq-stat { display:flex; justify-content:space-between; padding:.5rem 0; border-bottom:1px solid #1a1d28; }
.mhq-stat:last-child { border-bottom:none; }
.mhq-stat-label { color:#888; font-size:.85rem; }
.mhq-stat-value { color:#fff; font-weight:600; font-size:.95rem; }
.mhq-list { list-style:none; padding:0; margin:0; }
.mhq-list li { padding:.5rem 0; border-bottom:1px solid #1a1d28; font-size:.85rem; color:#ccc; display:flex; justify-content:space-between; align-items:center; }
.mhq-list li:last-child { border-bottom:none; }
.mhq-rank-bar { display:flex; align-items:center; gap:.5rem; margin-bottom:.5rem; }
.mhq-rank-fill { height:6px; border-radius:3px; background:#e2b340; transition:width .5s; }
.mhq-rank-track { flex:1; height:6px; border-radius:3px; background:#1a1d28; }
.mhq-section-title { max-width:1400px; margin:2rem auto 1rem; padding:0 1.5rem; font-size:1.1rem; color:#e2b340; text-transform:uppercase; letter-spacing:2px; font-weight:600; }
.mhq-full { max-width:1400px; margin:0 auto 2rem; padding:0 1.5rem; }
.mhq-table { width:100%; border-collapse:collapse; font-size:.85rem; }
.mhq-table th { text-align:left; padding:.6rem .8rem; color:#e2b340; border-bottom:1px solid #333; font-weight:600; text-transform:uppercase; font-size:.75rem; letter-spacing:1px; }
.mhq-table td { padding:.6rem .8rem; border-bottom:1px solid #1a1d28; color:#ccc; }
.mhq-table tr:hover td { background:#1a1d2850; }
.mhq-actions { display:flex; gap:.5rem; flex-wrap:wrap; margin-top:1rem; }
.mhq-btn { display:inline-block; padding:.5rem 1rem; border-radius:6px; font-size:.8rem; font-weight:600; text-decoration:none; text-transform:uppercase; letter-spacing:.5px; cursor:pointer; border:none; transition:all .2s; }
.mhq-btn-gold { background:#e2b340; color:#000; }
.mhq-btn-gold:hover { background:#f0c050; }
.mhq-btn-outline { background:transparent; color:#e2b340; border:1px solid #e2b340; }
.mhq-btn-outline:hover { background:#e2b34015; }
.mhq-btn-red { background:#8a2a2a; color:#fff; }
.mhq-promote-form { background:#12151e; border:1px solid #333; border-radius:12px; padding:1.5rem; margin-top:1.5rem; }
.mhq-input { background:#0d1117; border:1px solid #333; color:#fff; padding:.5rem .75rem; border-radius:6px; font-size:.85rem; width:100%; margin-bottom:.75rem; }
.mhq-input:focus { border-color:#e2b340; outline:none; }
.mhq-select { background:#0d1117; border:1px solid #333; color:#fff; padding:.5rem .75rem; border-radius:6px; font-size:.85rem; width:100%; margin-bottom:.75rem; }
.rank-group-enlisted { color:#6b8e6b; }
.rank-group-nco { color:#7ba37b; }
.rank-group-officer { color:#5b9bd5; }
.rank-group-flag { color:#d55b5b; }
.rank-group-supreme { color:#e2b340; }
@media(max-width:768px) {
    .mhq-grid { grid-template-columns:1fr; }
    .mhq-header-inner { flex-direction:column; text-align:center; }
}
</style>

<div class="mhq">

<!-- ── Header ────────────────────────────────────────────────────────── -->
<div class="mhq-header">
    <div class="mhq-header-inner">
        <div class="mhq-identity">
            <div class="mhq-avatar"><?= strtoupper(substr($userName, 0, 1)) ?></div>
            <div>
                <div class="mhq-name"><?= htmlspecialchars($userName) ?></div>
                <div class="mhq-meta"><?= $rankBadge ?> <span class="mhq-stars"><?= $rankStars ?></span></div>
                <?php if (!empty($userRank['is_temporary'])): ?>
                    <div class="mhq-meta" style="color:#e2b340;">&#x26A0; Temporary Elevation Active</div>
                <?php endif; ?>
            </div>
        </div>
        <div style="text-align:right;">
            <div style="font-size:2rem;color:#e2b340;">&#x2694; Military HQ</div>
            <div style="font-size:.8rem;color:#666;">GoSiteMe Defense Force — Global Command</div>
        </div>
    </div>
</div>

<!-- ── Dashboard Grid ────────────────────────────────────────────────── -->
<div class="mhq-grid">

    <!-- Service Card -->
    <div class="mhq-card">
        <h3>&#x1F4CB; Service Record <span>Tier <?= $userRankTier ?></span></h3>
        <div class="mhq-stat"><span class="mhq-stat-label">Rank</span><span class="mhq-stat-value rank-group-<?= $userRankGroup ?>"><?= htmlspecialchars($userRank['rank_name'] ?? 'Civilian') ?></span></div>
        <div class="mhq-stat"><span class="mhq-stat-label">Group</span><span class="mhq-stat-value"><?= ucfirst($userRankGroup ?? 'none') ?></span></div>
        <div class="mhq-stat"><span class="mhq-stat-label">Clearance</span><span class="mhq-stat-value"><?= ucfirst($userRank['clearance_level'] ?? 'none') ?></span></div>
        <div class="mhq-stat"><span class="mhq-stat-label">Fleet View</span><span class="mhq-stat-value"><?= ucfirst($userRank['max_fleet_view'] ?? 'none') ?></span></div>
        <div class="mhq-stat"><span class="mhq-stat-label">Region</span><span class="mhq-stat-value"><?= htmlspecialchars($userRank['region'] ?? 'Unassigned') ?></span></div>
        <div class="mhq-stat"><span class="mhq-stat-label">Division</span><span class="mhq-stat-value"><?= htmlspecialchars($userRank['division'] ?? 'Unassigned') ?></span></div>
        <div style="margin-top:.75rem;">
            <div class="mhq-rank-bar">
                <span style="font-size:.75rem;color:#666;">Progression</span>
                <div class="mhq-rank-track"><div class="mhq-rank-fill" style="width:<?= min(100, ($userRankTier / 11) * 100) ?>%"></div></div>
                <span style="font-size:.75rem;color:#e2b340;"><?= $userRankTier ?>/11</span>
            </div>
        </div>
    </div>

    <!-- Fleet Overview -->
    <div class="mhq-card">
        <h3>&#x1F30D; Fleet Overview</h3>
        <div class="mhq-stat"><span class="mhq-stat-label">Total Personnel</span><span class="mhq-stat-value"><?= number_format($totalPersonnel) ?></span></div>
        <div class="mhq-stat"><span class="mhq-stat-label">AI Agents</span><span class="mhq-stat-value"><?= number_format($totalAgents) ?></span></div>
        <div class="mhq-stat"><span class="mhq-stat-label">Fleet Citizens</span><span class="mhq-stat-value"><?= number_format($fleetCount) ?></span></div>
        <?php if ($userRankTier >= 6): ?>
        <div class="mhq-stat"><span class="mhq-stat-label">Departments</span><span class="mhq-stat-value">12</span></div>
        <div class="mhq-stat"><span class="mhq-stat-label">Domains</span><span class="mhq-stat-value">10</span></div>
        <?php endif; ?>
    </div>

    <!-- Permissions -->
    <div class="mhq-card">
        <h3>&#x1F512; Your Permissions <span><?= count($userPermissions) ?> granted</span></h3>
        <ul class="mhq-list">
            <?php
            $permGroups = [];
            foreach ($userPermissions as $p) {
                $parts = explode('.', $p, 2);
                $permGroups[$parts[0]][] = $parts[1] ?? $p;
            }
            foreach (array_slice($permGroups, 0, 8) as $group => $perms): ?>
                <li><span style="color:#e2b340;"><?= htmlspecialchars($group) ?></span><span><?= count($perms) ?> access<?= count($perms) > 1 ? 'es' : '' ?></span></li>
            <?php endforeach; ?>
            <?php if (count($permGroups) > 8): ?>
                <li style="color:#666;">+ <?= count($permGroups) - 8 ?> more groups</li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- Field Manual — Available to ALL ranked members -->
    <div class="mhq-card">
        <h3>&#x1F4D6; Field Manual <span>FM-001</span></h3>
        <p style="font-size:.85rem;color:#888;margin-bottom:1rem;">Official operational manual of the GoSiteMe Defense Force. Classified — ranking members only.</p>
        <div class="mhq-stat"><span class="mhq-stat-label">Document</span><span class="mhq-stat-value">FM-001 v1.0</span></div>
        <div class="mhq-stat"><span class="mhq-stat-label">Classification</span><span class="mhq-stat-value" style="color:#e2b340;">Ranking Members</span></div>
        <div class="mhq-stat"><span class="mhq-stat-label">Chapters</span><span class="mhq-stat-value">13</span></div>
        <div class="mhq-stat"><span class="mhq-stat-label">Your Clearance</span><span class="mhq-stat-value"><?= ucfirst($userRank['clearance_level'] ?? 'basic') ?></span></div>
        <div class="mhq-actions">
            <a href="/docs/field-manual" class="mhq-btn mhq-btn-gold">&#x1F4D6; Read Field Manual</a>
            <a href="/docs/field-manual#ranks" class="mhq-btn mhq-btn-outline">Rank Structure</a>
            <a href="/docs/field-manual#code" class="mhq-btn mhq-btn-outline">Code of Conduct</a>
        </div>
    </div>

    <!-- Military Library — 510+ manuals across all branches -->
    <div class="mhq-card">
        <h3>&#x1F4DA; Military Library <span>510+ Manuals</span></h3>
        <p style="font-size:.85rem;color:#888;margin-bottom:1rem;">Complete library of field manuals, technical manuals, doctrine, training, intelligence, and operational publications across 20 military branches.</p>
        <div class="mhq-stat"><span class="mhq-stat-label">Total Manuals</span><span class="mhq-stat-value">510+</span></div>
        <div class="mhq-stat"><span class="mhq-stat-label">Categories</span><span class="mhq-stat-value">20</span></div>
        <div class="mhq-stat"><span class="mhq-stat-label">Classification Levels</span><span class="mhq-stat-value">6</span></div>
        <div class="mhq-stat"><span class="mhq-stat-label">Your Clearance</span><span class="mhq-stat-value"><?= ucfirst($userRank['clearance_level'] ?? 'basic') ?></span></div>
        <div class="mhq-actions">
            <a href="/docs/military-library" class="mhq-btn mhq-btn-gold">&#x1F4DA; Open Military Library</a>
            <a href="/docs/military-library?cat=field_manual" class="mhq-btn mhq-btn-outline">Field Manuals</a>
            <a href="/docs/military-library?cat=special_ops" class="mhq-btn mhq-btn-outline">Special Ops</a>
        </div>
    </div>

    <?php if ($userRankTier >= 4): // NCO+ gets comms ?>
    <div class="mhq-card">
        <h3>&#x1F4E1; Communications</h3>
        <div class="mhq-stat"><span class="mhq-stat-label">Broadcast Level</span><span class="mhq-stat-value"><?= $userRankTier >= 10 ? 'Global' : ($userRankTier >= 8 ? 'Division' : 'Squad') ?></span></div>
        <div class="mhq-stat"><span class="mhq-stat-label">Intel Access</span><span class="mhq-stat-value"><?= $userRankTier >= 10 ? 'Sovereign' : ($userRankTier >= 7 ? 'Classified' : 'Basic') ?></span></div>
        <div class="mhq-actions">
            <a href="/team-chat" class="mhq-btn mhq-btn-outline">Team Chat</a>
            <?php if ($userRankTier >= 8): ?><a href="/fleet-dashboard" class="mhq-btn mhq-btn-outline">Fleet Ops</a><?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php
// ══════════════════════════════════════════════════════════════════════════
// COMMANDER-ONLY SECTIONS (Tier 11)
// ══════════════════════════════════════════════════════════════════════════
if ($userRankTier >= 11):
?>

<div class="mhq-section-title">&#x1F451; Supreme Commander — Global Fleet Command Center</div>

<div class="mhq-grid">

    <!-- Promote/Demote Quick Action -->
    <div class="mhq-card" style="grid-column:span 2;">
        <h3>&#x2B06; Rank Management — Quick Actions</h3>
        <p style="font-size:.85rem;color:#888;margin-bottom:1rem;">Promote, demote, or temporarily elevate any soldier. All changes are logged and take effect immediately.</p>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
            <div>
                <h4 style="color:#6b8e6b;font-size:.85rem;margin-bottom:.5rem;">&#x2B06; Promote / Assign</h4>
                <form id="promoteForm" onsubmit="return submitRankAction(event, 'promote')">
                    <input class="mhq-input" name="client_id" placeholder="Client ID (e.g., 45)" type="number" required>
                    <select class="mhq-select" name="to_rank" required>
                        <option value="">Select target rank...</option>
                        <?php foreach ($db->query("SELECT rank_code, rank_name, rank_tier FROM military_ranks ORDER BY rank_tier ASC")->fetchAll() as $r): ?>
                            <option value="<?= $r['rank_code'] ?>"><?= $r['rank_name'] ?> (Tier <?= $r['rank_tier'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <input class="mhq-input" name="reason" placeholder="Reason (optional)">
                    <button class="mhq-btn mhq-btn-gold" type="submit">Promote</button>
                    <button class="mhq-btn mhq-btn-outline" type="button" onclick="submitRankAction(event, 'assign', this.form)">Assign (New)</button>
                </form>
            </div>
            <div>
                <h4 style="color:#d55b5b;font-size:.85rem;margin-bottom:.5rem;">&#x26A0; Temp Elevate / Demote</h4>
                <form id="tempForm" onsubmit="return submitRankAction(event, 'temp_elevate')">
                    <input class="mhq-input" name="client_id" placeholder="Client ID" type="number" required>
                    <select class="mhq-select" name="to_rank" required>
                        <option value="">Select elevated rank...</option>
                        <?php foreach ($db->query("SELECT rank_code, rank_name, rank_tier FROM military_ranks ORDER BY rank_tier ASC")->fetchAll() as $r): ?>
                            <option value="<?= $r['rank_code'] ?>"><?= $r['rank_name'] ?> (Tier <?= $r['rank_tier'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <input class="mhq-input" name="duration_hours" placeholder="Duration (hours, default 4)" type="number" value="4">
                    <input class="mhq-input" name="reason" placeholder="Reason">
                    <button class="mhq-btn mhq-btn-gold" type="submit">Temp Elevate</button>
                    <button class="mhq-btn mhq-btn-red" type="button" onclick="submitRankAction(event, 'demote', this.form)">Demote</button>
                </form>
            </div>
        </div>
        <div id="rankResult" style="margin-top:1rem;font-size:.85rem;"></div>
    </div>

    <!-- Rank Distribution -->
    <div class="mhq-card">
        <h3>&#x1F4CA; Rank Distribution</h3>
        <?php foreach ($rankDistribution as $rd): ?>
        <div class="mhq-stat">
            <span class="mhq-stat-label rank-group-<?= $rd['rank_group'] ?>"><?= $rd['rank_name'] ?></span>
            <span class="mhq-stat-value"><?= $rd['count'] ?></span>
        </div>
        <?php endforeach; ?>
    </div>

</div>

<!-- Global Fleet Map Placeholder -->
<div class="mhq-section-title">&#x1F5FA; Global Fleet Map</div>
<div class="mhq-full">
    <div class="mhq-card" style="min-height:400px;position:relative;" id="fleetMapContainer">
        <h3>&#x1F30D; Geographic Fleet Distribution <span>Live</span></h3>
        <div id="fleetMap" style="width:100%;height:350px;background:#080c14;border-radius:8px;position:relative;overflow:hidden;">
            <canvas id="mapCanvas" style="width:100%;height:100%;"></canvas>
        </div>
        <div class="mhq-actions" style="margin-top:1rem;">
            <button class="mhq-btn mhq-btn-outline" onclick="loadFleetMap()">Refresh Map</button>
            <a href="/fleet-scanner-dashboard" class="mhq-btn mhq-btn-gold">Fleet Scanner</a>
            <a href="/supreme-admin" class="mhq-btn mhq-btn-outline">Supreme Admin</a>
        </div>
    </div>
</div>

<!-- Recent Rank Changes -->
<?php if (!empty($recentPromotions)): ?>
<div class="mhq-section-title">&#x1F4DC; Recent Rank Changes</div>
<div class="mhq-full">
    <div class="mhq-card">
        <table class="mhq-table">
            <thead><tr><th>When</th><th>Action</th><th>Soldier</th><th>From</th><th>To</th><th>Reason</th></tr></thead>
            <tbody>
            <?php foreach ($recentPromotions as $rp): ?>
                <tr>
                    <td><?= date('M j, g:ia', strtotime($rp['performed_at'])) ?></td>
                    <td><span style="text-transform:uppercase;font-size:.75rem;font-weight:600;color:<?= $rp['action'] === 'promote' ? '#6b8e6b' : ($rp['action'] === 'demote' ? '#d55b5b' : '#e2b340') ?>"><?= $rp['action'] ?></span></td>
                    <td><?= htmlspecialchars($rp['subject_name'] ?? '#' . $rp['client_id']) ?></td>
                    <td><?= $rp['from_rank'] ?? '-' ?></td>
                    <td style="color:#e2b340;"><?= $rp['to_rank_name'] ?? $rp['to_rank'] ?? '-' ?></td>
                    <td style="color:#888;max-width:200px;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars(substr($rp['reason'] ?? '', 0, 60)) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php endif; // end commander sections ?>

<?php
// ── Officer+ Quick Links ────────────────────────────────────────────────
if ($userRankTier >= 6):
?>
<div class="mhq-section-title">&#x1F517; Command Resources</div>
<div class="mhq-grid">
    <?php if (hasPermission('dashboard.fleet_advanced')): ?>
    <div class="mhq-card">
        <h3>&#x1F680; Fleet Operations</h3>
        <div class="mhq-actions">
            <a href="/fleet-dashboard" class="mhq-btn mhq-btn-outline">Fleet Dashboard</a>
            <a href="/dashboard" class="mhq-btn mhq-btn-outline">AI Dashboard</a>
            <?php if (hasPermission('agents.deploy')): ?><a href="/agent-orchestrator" class="mhq-btn mhq-btn-outline">Orchestrator</a><?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php if (hasPermission('departments.view')): ?>
    <div class="mhq-card">
        <h3>&#x1F3DB; Departments</h3>
        <div class="mhq-actions">
            <a href="/justice-dashboard" class="mhq-btn mhq-btn-outline">Justice</a>
            <a href="/finance-dashboard" class="mhq-btn mhq-btn-outline">Finance</a>
            <a href="/reporting-dashboard" class="mhq-btn mhq-btn-outline">Reports</a>
        </div>
    </div>
    <?php endif; ?>
    <div class="mhq-card">
        <h3>&#x1F4D6; Classified Documents</h3>
        <div class="mhq-actions">
            <a href="/docs/military-library" class="mhq-btn mhq-btn-gold">&#x1F4DA; Military Library (510+)</a>
            <a href="/docs/field-manual" class="mhq-btn mhq-btn-outline">FM-006 Field Manual</a>
            <a href="/docs/degree-manuals" class="mhq-btn mhq-btn-outline">Degree Manuals</a>
            <a href="/service-record" class="mhq-btn mhq-btn-outline">Service Record</a>            <a href="/docs/breach-report-alfred-mobile" class="mhq-btn" style="background:#cc0000;color:#fff;border-color:#ff0000;">&#x26A0; BREACH REPORT — alfred-mobile.com</a>        </div>
    </div>
</div>
<?php endif; ?>

</div><!-- end .mhq -->

<script>
async function submitRankAction(e, action, formOverride) {
    e.preventDefault();
    const form = formOverride || e.target;
    const data = Object.fromEntries(new FormData(form));
    data.to_rank = data.to_rank || undefined;
    const resultDiv = document.getElementById('rankResult');
    resultDiv.innerHTML = '<span style="color:#e2b340;">Processing...</span>';

    // For assign action, use assign endpoint; for demote from temp form, use demote
    let endpoint = action;
    if (action === 'assign') {
        endpoint = 'assign';
        data.rank_code = data.to_rank;
    }

    try {
        const res = await fetch('/api/rank-management.php?action=' + endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const json = await res.json();
        if (json.ok) {
            resultDiv.innerHTML = '<span style="color:#6b8e6b;">&#x2713; ' + json.message + '</span>';
            setTimeout(() => location.reload(), 1500);
        } else {
            resultDiv.innerHTML = '<span style="color:#d55b5b;">&#x2717; ' + (json.error || 'Failed') + '</span>';
        }
    } catch (err) {
        resultDiv.innerHTML = '<span style="color:#d55b5b;">Network error: ' + err.message + '</span>';
    }
}

<?php if ($userRankTier >= 11): ?>
// Simple world map on canvas
function loadFleetMap() {
    const canvas = document.getElementById('mapCanvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    canvas.width = canvas.offsetWidth * 2;
    canvas.height = canvas.offsetHeight * 2;
    ctx.scale(2, 2);
    const w = canvas.offsetWidth, h = canvas.offsetHeight;

    // Dark background with grid
    ctx.fillStyle = '#080c14';
    ctx.fillRect(0, 0, w, h);

    // Grid lines
    ctx.strokeStyle = '#ffffff08';
    ctx.lineWidth = .5;
    for (let x = 0; x < w; x += 30) { ctx.beginPath(); ctx.moveTo(x, 0); ctx.lineTo(x, h); ctx.stroke(); }
    for (let y = 0; y < h; y += 30) { ctx.beginPath(); ctx.moveTo(0, y); ctx.lineTo(w, y); ctx.stroke(); }

    // Simplified world map outline (major landmasses as polygons)
    ctx.strokeStyle = '#e2b34040';
    ctx.lineWidth = 1;
    ctx.beginPath();
    // North America
    ctx.moveTo(w*.12, h*.25); ctx.lineTo(w*.08, h*.35); ctx.lineTo(w*.15, h*.55); ctx.lineTo(w*.22, h*.55);
    ctx.lineTo(w*.25, h*.45); ctx.lineTo(w*.2, h*.25); ctx.closePath();
    // South America
    ctx.moveTo(w*.22, h*.55); ctx.lineTo(w*.2, h*.65); ctx.lineTo(w*.22, h*.85); ctx.lineTo(w*.28, h*.75);
    ctx.lineTo(w*.26, h*.55); ctx.closePath();
    // Europe
    ctx.moveTo(w*.42, h*.2); ctx.lineTo(w*.38, h*.3); ctx.lineTo(w*.45, h*.4); ctx.lineTo(w*.52, h*.35);
    ctx.lineTo(w*.48, h*.2); ctx.closePath();
    // Africa
    ctx.moveTo(w*.42, h*.4); ctx.lineTo(w*.38, h*.5); ctx.lineTo(w*.42, h*.75); ctx.lineTo(w*.52, h*.7);
    ctx.lineTo(w*.52, h*.45); ctx.closePath();
    // Asia
    ctx.moveTo(w*.55, h*.15); ctx.lineTo(w*.52, h*.35); ctx.lineTo(w*.6, h*.5); ctx.lineTo(w*.78, h*.45);
    ctx.lineTo(w*.82, h*.25); ctx.lineTo(w*.7, h*.15); ctx.closePath();
    // Australia
    ctx.moveTo(w*.75, h*.6); ctx.lineTo(w*.72, h*.7); ctx.lineTo(w*.8, h*.75); ctx.lineTo(w*.85, h*.65); ctx.closePath();
    ctx.stroke();
    ctx.fillStyle = '#e2b34008';
    ctx.fill();

    // Fleet presence dots (simulated — would be real data)
    const fleetPoints = [
        { x: .16, y: .38, label: 'HQ — Canada', size: 8, color: '#e2b340' },
        { x: .14, y: .42, label: 'US East', size: 5, color: '#5b9bd5' },
        { x: .1,  y: .4,  label: 'US West', size: 4, color: '#5b9bd5' },
        { x: .22, y: .65, label: 'Brazil', size: 3, color: '#6b8e6b' },
        { x: .45, y: .3,  label: 'Europe', size: 4, color: '#5b9bd5' },
        { x: .48, y: .55, label: 'Africa', size: 3, color: '#6b8e6b' },
        { x: .65, y: .35, label: 'Asia', size: 4, color: '#5b9bd5' },
        { x: .78, y: .65, label: 'Australia', size: 3, color: '#6b8e6b' },
    ];

    fleetPoints.forEach(p => {
        const px = p.x * w, py = p.y * h;
        // Pulse ring
        ctx.beginPath();
        ctx.arc(px, py, p.size + 4, 0, Math.PI * 2);
        ctx.fillStyle = p.color + '20';
        ctx.fill();
        // Dot
        ctx.beginPath();
        ctx.arc(px, py, p.size, 0, Math.PI * 2);
        ctx.fillStyle = p.color;
        ctx.fill();
        // Label
        ctx.fillStyle = '#ffffff90';
        ctx.font = '10px system-ui';
        ctx.fillText(p.label, px + p.size + 4, py + 4);
    });

    // Title overlay
    ctx.fillStyle = '#ffffff30';
    ctx.font = 'bold 12px system-ui';
    ctx.fillText('GOSITE DEFENSE FORCE — GLOBAL FLEET MAP', 10, 20);
    ctx.fillStyle = '#e2b34060';
    ctx.fillText('<?= number_format($totalAgents) ?> agents | <?= number_format($totalPersonnel) ?> personnel | <?= number_format($fleetCount) ?> citizens', 10, 36);
}
loadFleetMap();
window.addEventListener('resize', loadFleetMap);
<?php endif; ?>
</script>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
