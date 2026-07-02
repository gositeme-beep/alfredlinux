<?php
/**
 * ═══════════════════════════════════════════════════════════════
 *  COMMAND CENTER — Central Military Operations Dashboard
 * ═══════════════════════════════════════════════════════════════
 *  The nerve center of the GoSiteMe sovereign military.
 *  Every ranked user gets their tier-appropriate command view.
 *  Commander sees global operations; enlisted sees personal ops.
 *
 *  Version: 1.0 — April 2026
 */

require_once __DIR__ . '/includes/db-config.inc.php';
$db = getSharedDB();
require_once __DIR__ . '/includes/auth-gate.inc.php';
require_once __DIR__ . '/includes/rank-guard.inc.php';

// Must be ranked
requireRank(1, 'Recruit');

// Award daily login XP
awardXP($clientId, 'daily_login', ['source' => 'command-center']);

// ─── Data Queries ───
$userName = $clientName ?? 'Soldier';

// Get XP and progression
$xpStmt = $db->prepare("SELECT xp FROM user_ranks WHERE client_id = ? AND is_active = 1");
$xpStmt->execute([$clientId]);
$userXP = (int)$xpStmt->fetchColumn();

// Next rank threshold
$nextRankStmt = $db->prepare("SELECT rank_name, rank_tier, xp_required FROM military_ranks WHERE rank_tier > ? ORDER BY rank_tier ASC LIMIT 1");
$nextRankStmt->execute([$userRankTier]);
$nextRank = $nextRankStmt->fetch(PDO::FETCH_ASSOC);

// XP progress percentage
$xpProgress = 0;
$xpNeeded = 0;
if ($nextRank) {
    $xpNeeded = $nextRank['xp_required'] - $userXP;
    if ($nextRank['xp_required'] > 0) {
        $xpProgress = min(100, round(($userXP / $nextRank['xp_required']) * 100));
    }
}

// Personnel count
$totalPersonnel = (int)$db->query("SELECT COUNT(*) FROM user_ranks WHERE is_active = 1")->fetchColumn();

// Active missions
$activeMissions = (int)$db->query("SELECT COUNT(*) FROM missions WHERE is_active = 1")->fetchColumn();

// Departments
$deptCount = (int)$db->query("SELECT COUNT(*) FROM departments WHERE status = 'active'")->fetchColumn();

// User decorations
$myDecorations = 0;
try {
    $decStmt = $db->prepare("SELECT COUNT(*) FROM user_decorations WHERE client_id = ?");
    $decStmt->execute([$clientId]);
    $myDecorations = (int)$decStmt->fetchColumn();
} catch (Exception $e) {}

// Recent XP history (last 10)
$xpHistory = getXPHistory($clientId, 10);

// Leaderboard (top 10)
$leaderboard = getXPLeaderboard(10);

// Court cases (flag officers only)
$courtCases = 0;
if ($userRankTier >= 9) {
    try { $courtCases = (int)$db->query("SELECT COUNT(*) FROM military_court_cases WHERE status NOT IN ('closed','dismissed')")->fetchColumn(); } catch (Exception $e) {}
}

// Fleet agents (officers+)
$fleetAgents = 0;
if ($userRankTier >= 6) {
    try {
        $estRows = $db->query("SELECT TABLE_ROWS FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'agent_profiles'")->fetchColumn();
        $fleetAgents = (int)$estRows;
    } catch (Exception $e) {}
}

// War Room system count (officers+)
$warRoomSystems = 0;
if ($userRankTier >= 5) {
    $warRoomSystems = 351; // Known count from War Room
}

$pageTitle = 'Command Center — GoSiteMe Military';
include __DIR__ . '/includes/site-header.inc.php';
?>

<style>
:root {
    --cc-bg: #020208;
    --cc-surface: #0a0a14;
    --cc-card: rgba(255,255,255,0.03);
    --cc-border: rgba(255,255,255,0.08);
    --cc-gold: #e2b340;
    --cc-gold-dim: rgba(226,179,64,0.15);
    --cc-green: #10b981;
    --cc-red: #ef4444;
    --cc-cyan: #06b6d4;
    --cc-purple: #8b5cf6;
    --cc-text: rgba(255,255,255,0.92);
    --cc-muted: rgba(255,255,255,0.5);
}

.cc-wrap { max-width:1200px; margin:0 auto; padding:2rem 1.5rem 4rem; }

/* Header Strip */
.cc-header {
    display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem;
    padding:1.5rem 2rem; margin-bottom:2rem;
    background: linear-gradient(135deg, rgba(226,179,64,0.08) 0%, rgba(226,179,64,0.02) 100%);
    border:1px solid var(--cc-border); border-radius:16px;
}
.cc-header-left { display:flex; align-items:center; gap:1.25rem; }
.cc-rank-icon { font-size:2.8rem; }
.cc-header-name { font-size:1.6rem; font-weight:800; color:var(--cc-gold); letter-spacing:.5px; }
.cc-header-subtitle { color:var(--cc-muted); font-size:.85rem; margin-top:2px; }
.cc-header-right { text-align:right; }
.cc-xp-label { font-size:.75rem; color:var(--cc-muted); text-transform:uppercase; letter-spacing:1px; margin-bottom:4px; }
.cc-xp-bar-wrap { width:200px; height:10px; background:rgba(255,255,255,0.06); border-radius:100px; overflow:hidden; }
.cc-xp-bar { height:100%; background:linear-gradient(90deg, var(--cc-gold), #f5c542); border-radius:100px; transition:width .6s ease; }
.cc-xp-text { font-size:.75rem; color:var(--cc-muted); margin-top:4px; }

/* Stats Grid */
.cc-stats { display:grid; grid-template-columns:repeat(auto-fit, minmax(160px,1fr)); gap:1rem; margin-bottom:2rem; }
.cc-stat {
    background:var(--cc-card); border:1px solid var(--cc-border); border-radius:12px;
    padding:1.25rem; text-align:center; transition:border-color .2s;
}
.cc-stat:hover { border-color:rgba(226,179,64,0.3); }
.cc-stat-icon { font-size:1.6rem; margin-bottom:.5rem; }
.cc-stat-value { font-size:1.8rem; font-weight:800; color:var(--cc-text); }
.cc-stat-label { font-size:.75rem; color:var(--cc-muted); text-transform:uppercase; letter-spacing:.5px; margin-top:2px; }

/* Two-Column Layout */
.cc-grid { display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:2rem; }
@media(max-width:800px) { .cc-grid { grid-template-columns:1fr; } }

/* Card */
.cc-card {
    background:var(--cc-card); border:1px solid var(--cc-border); border-radius:14px;
    padding:1.5rem; overflow:hidden;
}
.cc-card-title {
    font-size:.85rem; font-weight:700; color:var(--cc-gold); text-transform:uppercase;
    letter-spacing:1px; margin-bottom:1rem; display:flex; align-items:center; gap:.5rem;
}

/* Quick Actions Grid */
.cc-actions { display:grid; grid-template-columns:repeat(auto-fit, minmax(200px,1fr)); gap:.75rem; margin-bottom:2rem; }
.cc-action {
    display:flex; align-items:center; gap:.75rem;
    background:var(--cc-card); border:1px solid var(--cc-border); border-radius:10px;
    padding:.85rem 1rem; text-decoration:none; color:var(--cc-text);
    transition:all .2s; font-size:.9rem; font-weight:500;
}
.cc-action:hover { border-color:var(--cc-gold); background:var(--cc-gold-dim); transform:translateY(-1px); }
.cc-action-icon { font-size:1.3rem; flex-shrink:0; }
.cc-action-text { flex:1; }
.cc-action-sub { font-size:.7rem; color:var(--cc-muted); display:block; }

/* XP History */
.cc-xp-row { display:flex; justify-content:space-between; padding:.5rem 0; border-bottom:1px solid rgba(255,255,255,0.04); font-size:.85rem; }
.cc-xp-row:last-child { border-bottom:none; }
.cc-xp-action { color:var(--cc-text); }
.cc-xp-amount { color:var(--cc-green); font-weight:700; }
.cc-xp-time { color:var(--cc-muted); font-size:.75rem; }

/* Leaderboard */
.cc-leader-row { display:flex; align-items:center; gap:.75rem; padding:.45rem 0; border-bottom:1px solid rgba(255,255,255,0.04); font-size:.85rem; }
.cc-leader-row:last-child { border-bottom:none; }
.cc-leader-pos { width:24px; text-align:center; font-weight:800; color:var(--cc-gold); }
.cc-leader-name { flex:1; color:var(--cc-text); }
.cc-leader-xp { color:var(--cc-green); font-weight:600; }
.cc-leader-rank { font-size:.7rem; color:var(--cc-muted); }

/* Systems Grid */
.cc-systems { display:grid; grid-template-columns:repeat(auto-fit, minmax(160px,1fr)); gap:.75rem; }
.cc-system-card {
    background:rgba(255,255,255,0.02); border:1px solid var(--cc-border); border-radius:10px;
    padding:1rem; text-align:center; text-decoration:none; color:var(--cc-text);
    transition:all .2s;
}
.cc-system-card:hover { border-color:var(--cc-gold); background:var(--cc-gold-dim); }
.cc-system-icon { font-size:1.8rem; margin-bottom:.4rem; }
.cc-system-name { font-size:.8rem; font-weight:600; }
.cc-system-count { font-size:.7rem; color:var(--cc-muted); }

/* Classification Bar */
.cc-classbar {
    text-align:center; padding:5px 0; font-size:.65rem; font-weight:800;
    letter-spacing:.2em; text-transform:uppercase; margin-bottom:0;
}
.cc-classbar.secret { background:#d97706; color:#fff; }
.cc-classbar.classified { background:#dc2626; color:#fff; }
.cc-classbar.standard { background:#2563eb; color:#fff; }
</style>

<?php
// Classification level based on rank
$classLevel = 'UNCLASSIFIED';
$classClass = 'standard';
if ($userRankTier >= 9) { $classLevel = 'TOP SECRET // SOVEREIGN'; $classClass = 'classified'; }
elseif ($userRankTier >= 6) { $classLevel = 'SECRET // OFFICER'; $classClass = 'secret'; }
elseif ($userRankTier >= 4) { $classLevel = 'CONFIDENTIAL // NCO'; $classClass = 'standard'; }
?>

<div class="cc-classbar <?= $classClass ?>"><?= $classLevel ?></div>

<main class="main-content">
<div class="cc-wrap">

    <!-- HEADER: Rank + XP -->
    <div class="cc-header">
        <div class="cc-header-left">
            <div class="cc-rank-icon"><?= getRankTierStars($userRankTier) ?></div>
            <div>
                <div class="cc-header-name"><?= htmlspecialchars($userName) ?></div>
                <div class="cc-header-subtitle">
                    <?= getUserRankBadge() ?>
                    &nbsp;&middot;&nbsp; Tier <?= $userRankTier ?>
                    <?php if (!empty($userRank['clearance_level'])): ?>
                        &nbsp;&middot;&nbsp; <?= ucfirst(htmlspecialchars($userRank['clearance_level'])) ?> Clearance
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="cc-header-right">
            <div class="cc-xp-label">Experience Points</div>
            <div style="font-size:1.4rem;font-weight:800;color:var(--cc-gold);"><?= number_format($userXP) ?> XP</div>
            <?php if ($nextRank): ?>
                <div class="cc-xp-bar-wrap"><div class="cc-xp-bar" style="width:<?= $xpProgress ?>%"></div></div>
                <div class="cc-xp-text"><?= number_format($xpNeeded) ?> XP to <?= htmlspecialchars($nextRank['rank_name']) ?></div>
            <?php else: ?>
                <div class="cc-xp-text" style="color:var(--cc-gold);">Maximum Rank Achieved</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- LIVE STATS -->
    <div class="cc-stats">
        <div class="cc-stat">
            <div class="cc-stat-icon">&#x2694;&#xFE0F;</div>
            <div class="cc-stat-value"><?= number_format($totalPersonnel) ?></div>
            <div class="cc-stat-label">Active Personnel</div>
        </div>
        <div class="cc-stat">
            <div class="cc-stat-icon">&#x1F3AF;</div>
            <div class="cc-stat-value"><?= $activeMissions ?></div>
            <div class="cc-stat-label">Active Missions</div>
        </div>
        <div class="cc-stat">
            <div class="cc-stat-icon">&#x1F3DB;&#xFE0F;</div>
            <div class="cc-stat-value"><?= $deptCount ?></div>
            <div class="cc-stat-label">Departments</div>
        </div>
        <div class="cc-stat">
            <div class="cc-stat-icon">&#x1F396;&#xFE0F;</div>
            <div class="cc-stat-value"><?= $myDecorations ?></div>
            <div class="cc-stat-label">My Decorations</div>
        </div>
        <?php if ($userRankTier >= 5): ?>
        <div class="cc-stat">
            <div class="cc-stat-icon">&#x1F4BB;</div>
            <div class="cc-stat-value"><?= number_format($warRoomSystems) ?></div>
            <div class="cc-stat-label">War Room Systems</div>
        </div>
        <?php endif; ?>
        <?php if ($userRankTier >= 6): ?>
        <div class="cc-stat">
            <div class="cc-stat-icon">&#x1F916;</div>
            <div class="cc-stat-value"><?= number_format($fleetAgents) ?></div>
            <div class="cc-stat-label">Fleet Agents</div>
        </div>
        <?php endif; ?>
        <?php if ($userRankTier >= 9): ?>
        <div class="cc-stat">
            <div class="cc-stat-icon">&#x2696;&#xFE0F;</div>
            <div class="cc-stat-value"><?= $courtCases ?></div>
            <div class="cc-stat-label">Open Court Cases</div>
        </div>
        <?php endif; ?>
    </div>

    <!-- QUICK ACTIONS -->
    <div class="cc-card-title" style="margin-bottom:.75rem;padding-left:.25rem;">&#x26A1; Operations</div>
    <div class="cc-actions">
        <a href="/military-hq" class="cc-action">
            <span class="cc-action-icon">&#x1F3E0;</span>
            <span class="cc-action-text">Military HQ<span class="cc-action-sub">Headquarters dashboard</span></span>
        </a>
        <a href="/docs/field-manual" class="cc-action">
            <span class="cc-action-icon">&#x1F4D6;</span>
            <span class="cc-action-text">Field Manual<span class="cc-action-sub">72 sections &middot; FM-006</span></span>
        </a>
        <a href="/missions" class="cc-action">
            <span class="cc-action-icon">&#x1F3AF;</span>
            <span class="cc-action-text">Missions<span class="cc-action-sub"><?= $activeMissions ?> active missions</span></span>
        </a>
        <a href="/chain-of-command" class="cc-action">
            <span class="cc-action-icon">&#x1F4DC;</span>
            <span class="cc-action-text">Chain of Command<span class="cc-action-sub">Orders &amp; reports</span></span>
        </a>
        <a href="/service-record" class="cc-action">
            <span class="cc-action-icon">&#x1F4CB;</span>
            <span class="cc-action-text">Service Record<span class="cc-action-sub">Your military file</span></span>
        </a>
        <a href="/decorations" class="cc-action">
            <span class="cc-action-icon">&#x1F396;&#xFE0F;</span>
            <span class="cc-action-text">Decorations<span class="cc-action-sub"><?= $myDecorations ?> earned</span></span>
        </a>
        <?php if ($userRankTier >= 4): ?>
        <a href="/war-games" class="cc-action">
            <span class="cc-action-icon">&#x265F;&#xFE0F;</span>
            <span class="cc-action-text">War Games<span class="cc-action-sub">Strategic PvP</span></span>
        </a>
        <a href="/intelligence" class="cc-action">
            <span class="cc-action-icon">&#x1F575;&#xFE0F;</span>
            <span class="cc-action-text">Intelligence<span class="cc-action-sub">Intel reports &amp; analysis</span></span>
        </a>
        <?php endif; ?>
        <?php if ($userRankTier >= 6): ?>
        <a href="/mission-control" class="cc-action">
            <span class="cc-action-icon">&#x1F6F0;&#xFE0F;</span>
            <span class="cc-action-text">Mission Control<span class="cc-action-sub">Deploy &amp; manage ops</span></span>
        </a>
        <a href="/fleet-dashboard" class="cc-action">
            <span class="cc-action-icon">&#x1F680;</span>
            <span class="cc-action-text">Fleet Dashboard<span class="cc-action-sub">Agent fleet ops</span></span>
        </a>
        <?php endif; ?>
        <?php if ($userRankTier >= 9): ?>
        <a href="/military-court" class="cc-action">
            <span class="cc-action-icon">&#x2696;&#xFE0F;</span>
            <span class="cc-action-text">Military Court<span class="cc-action-sub"><?= $courtCases ?> pending cases</span></span>
        </a>
        <a href="/supreme-command" class="cc-action">
            <span class="cc-action-icon">&#x1F451;</span>
            <span class="cc-action-text">Supreme Command<span class="cc-action-sub">Flag officer controls</span></span>
        </a>
        <?php endif; ?>
        <?php if ($userRankTier >= 11): ?>
        <a href="/commander-agents" class="cc-action" style="border-color:rgba(226,179,64,0.3);background:var(--cc-gold-dim);">
            <span class="cc-action-icon">&#x1F4E1;</span>
            <span class="cc-action-text" style="color:var(--cc-gold);">War Room<span class="cc-action-sub">351 command systems</span></span>
        </a>
        <?php endif; ?>
    </div>

    <!-- TWO COLUMN: XP History + Leaderboard -->
    <div class="cc-grid">

        <!-- XP History -->
        <div class="cc-card">
            <div class="cc-card-title">&#x1F4CA; Recent XP Activity</div>
            <?php if (empty($xpHistory)): ?>
                <p style="color:var(--cc-muted);font-size:.85rem;">No XP activity yet. Complete missions and use the ecosystem to earn XP.</p>
            <?php else: ?>
                <?php foreach ($xpHistory as $xp): ?>
                <div class="cc-xp-row">
                    <span class="cc-xp-action"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $xp['action']))) ?></span>
                    <span>
                        <span class="cc-xp-amount">+<?= (int)$xp['xp_amount'] ?></span>
                        <span class="cc-xp-time"><?= date('M j', strtotime($xp['created_at'])) ?></span>
                    </span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Leaderboard -->
        <div class="cc-card">
            <div class="cc-card-title">&#x1F3C6; XP Leaderboard</div>
            <?php if (empty($leaderboard)): ?>
                <p style="color:var(--cc-muted);font-size:.85rem;">No ranked personnel yet.</p>
            <?php else: ?>
                <?php foreach ($leaderboard as $i => $leader): ?>
                <div class="cc-leader-row" <?php if (($leader['client_id'] ?? 0) == $clientId): ?>style="background:var(--cc-gold-dim);border-radius:6px;padding:.45rem .5rem;"<?php endif; ?>>
                    <span class="cc-leader-pos"><?= $i + 1 ?></span>
                    <span class="cc-leader-name">
                        <?= htmlspecialchars(trim(($leader['firstname'] ?? '') . ' ' . ($leader['lastname'] ?? '')) ?: 'Unknown') ?>
                        <span class="cc-leader-rank"><?= htmlspecialchars($leader['rank_name'] ?? '') ?></span>
                    </span>
                    <span class="cc-leader-xp"><?= number_format((int)($leader['xp'] ?? 0)) ?> XP</span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>

    <!-- NINE PILLARS -->
    <div class="cc-card" style="margin-bottom:2rem;">
        <div class="cc-card-title">&#x1F3DB;&#xFE0F; The Nine Pillars &mdash; Ecosystem Access</div>
        <div class="cc-systems">
            <a href="/veil" class="cc-system-card">
                <div class="cc-system-icon">&#x1F510;</div>
                <div class="cc-system-name">Veil</div>
                <div class="cc-system-count">Secure Comms &middot; Tier <?= $userRankTier >= 6 ? '&#x2705;' : '6+' ?></div>
            </a>
            <a href="https://alfredlinux.com" class="cc-system-card" target="_blank" rel="noopener">
                <div class="cc-system-icon">&#x1F310;</div>
                <div class="cc-system-name">Alfred Browser</div>
                <div class="cc-system-count">Privacy Browser &middot; &#x2705;</div>
            </a>
            <a href="/search" class="cc-system-card">
                <div class="cc-system-icon">&#x1F50D;</div>
                <div class="cc-system-name">Alfred Search</div>
                <div class="cc-system-count">Zero-Track Search &middot; &#x2705;</div>
            </a>
            <a href="/agents" class="cc-system-card">
                <div class="cc-system-icon">&#x1F916;</div>
                <div class="cc-system-name">Alfred AI</div>
                <div class="cc-system-count"><?= number_format($fleetAgents) ?>+ Agents &middot; Tier <?= $userRankTier >= 1 ? '&#x2705;' : '1+' ?></div>
            </a>
            <a href="/pulse" class="cc-system-card">
                <div class="cc-system-icon">&#x1F4AC;</div>
                <div class="cc-system-name">Pulse</div>
                <div class="cc-system-count">Social Network &middot; &#x2705;</div>
            </a>
            <a href="https://meta-dome.com" class="cc-system-card" target="_blank" rel="noopener">
                <div class="cc-system-icon">&#x1F3AE;</div>
                <div class="cc-system-name">MetaDome</div>
                <div class="cc-system-count">VR Worlds &middot; &#x2705;</div>
            </a>
            <a href="/voice" class="cc-system-card">
                <div class="cc-system-icon">&#x1F399;&#xFE0F;</div>
                <div class="cc-system-name">Voice AI</div>
                <div class="cc-system-count">Phone &amp; Speech &middot; Tier <?= $userRankTier >= 1 ? '&#x2705;' : '1+' ?></div>
            </a>
            <a href="/alfred-ide" class="cc-system-card">
                <div class="cc-system-icon">&#x1F4BB;</div>
                <div class="cc-system-name">Alfred IDE</div>
                <div class="cc-system-count">Dev Platform &middot; Tier <?= $userRankTier >= 1 ? '&#x2705;' : '1+' ?></div>
            </a>
        </div>
    </div>

    <!-- TIER-GATED SYSTEMS (Officers+) -->
    <?php if ($userRankTier >= 6): ?>
    <div class="cc-card" style="margin-bottom:2rem;">
        <div class="cc-card-title">&#x1F6E1;&#xFE0F; Officer Systems</div>
        <div class="cc-systems">
            <a href="/arsenal" class="cc-system-card">
                <div class="cc-system-icon">&#x1F52B;</div>
                <div class="cc-system-name">Arsenal</div>
                <div class="cc-system-count">Weapons &amp; Tools</div>
            </a>
            <a href="/cyber-ops" class="cc-system-card">
                <div class="cc-system-icon">&#x1F5A5;&#xFE0F;</div>
                <div class="cc-system-name">Cyber Ops</div>
                <div class="cc-system-count">Digital Warfare</div>
            </a>
            <a href="/spec-ops" class="cc-system-card">
                <div class="cc-system-icon">&#x1F977;</div>
                <div class="cc-system-name">Spec Ops</div>
                <div class="cc-system-count">Special Operations</div>
            </a>
            <a href="/signal-corps" class="cc-system-card">
                <div class="cc-system-icon">&#x1F4E1;</div>
                <div class="cc-system-name">Signal Corps</div>
                <div class="cc-system-count">Communications</div>
            </a>
            <a href="/stratcom" class="cc-system-card">
                <div class="cc-system-icon">&#x1F30D;</div>
                <div class="cc-system-name">STRATCOM</div>
                <div class="cc-system-count">Strategic Command</div>
            </a>
            <a href="/intelligence-director" class="cc-system-card">
                <div class="cc-system-icon">&#x1F441;&#xFE0F;</div>
                <div class="cc-system-name">Intel Director</div>
                <div class="cc-system-count">Intelligence HQ</div>
            </a>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($userRankTier >= 9): ?>
    <div class="cc-card" style="margin-bottom:2rem;">
        <div class="cc-card-title">&#x1F451; Flag Officer Command</div>
        <div class="cc-systems">
            <a href="/nuclear-deterrence" class="cc-system-card">
                <div class="cc-system-icon">&#x2622;&#xFE0F;</div>
                <div class="cc-system-name">Nuclear Deterrence</div>
                <div class="cc-system-count">Strategic Posture</div>
            </a>
            <a href="/space-command" class="cc-system-card">
                <div class="cc-system-icon">&#x1F6F8;</div>
                <div class="cc-system-name">Space Command</div>
                <div class="cc-system-count">Orbital Operations</div>
            </a>
            <a href="/national-guard" class="cc-system-card">
                <div class="cc-system-icon">&#x1F6E1;&#xFE0F;</div>
                <div class="cc-system-name">National Guard</div>
                <div class="cc-system-count">Homeland Defense</div>
            </a>
            <a href="/war-college" class="cc-system-card">
                <div class="cc-system-icon">&#x1F393;</div>
                <div class="cc-system-name">War College</div>
                <div class="cc-system-count">Officer Training</div>
            </a>
            <a href="/constitution" class="cc-system-card">
                <div class="cc-system-icon">&#x1F4DC;</div>
                <div class="cc-system-name">Constitution</div>
                <div class="cc-system-count">Founding Law</div>
            </a>
            <a href="/senate" class="cc-system-card">
                <div class="cc-system-icon">&#x1F3DB;&#xFE0F;</div>
                <div class="cc-system-name">Senate</div>
                <div class="cc-system-count">Legislative Body</div>
            </a>
            <a href="/treasury" class="cc-system-card">
                <div class="cc-system-icon">&#x1F4B0;</div>
                <div class="cc-system-name">Treasury</div>
                <div class="cc-system-count">GSM Economy</div>
            </a>
            <a href="/jag" class="cc-system-card">
                <div class="cc-system-icon">&#x2696;&#xFE0F;</div>
                <div class="cc-system-name">JAG Corps</div>
                <div class="cc-system-count">Military Justice</div>
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- ========================================== -->
    <!-- SOVEREIGN ASCENSION DEPLOYMENTS (THE TRIAD) -->
    <!-- ========================================== -->
    <div class="cc-card" style="margin-bottom:2rem; border-color: rgba(226,179,64,0.3); background: linear-gradient(180deg, rgba(10,10,20,1) 0%, rgba(20,15,5,1) 100%);">
        <div class="cc-card-title" style="color: #fff; font-size: 1rem;"><span style="font-size: 1.3rem; margin-right: 8px;">⚡</span> Ascension Infrastructure</div>
        <p style="color: var(--cc-muted); font-size: .85rem; margin-bottom: 1.5rem;">Sovereign deployment matrix for the Quantum RAM-Disk OS. Choose your deployment vector.</p>
        
        <div class="cc-systems">
            
            <!-- Cloud Bare-Metal Lease -->
            <a href="#" class="cc-system-card" onclick="alert('Ascension Cloud API provisioning offline. Awaiting backend Docker linkage.'); return false;" style="border-color: rgba(147, 51, 234, 0.4);">
                <div class="cc-system-icon" style="color: #c084fc;"><i class="fas fa-server"></i></div>
                <div class="cc-system-name" style="color: #e9d5ff;">Cloud Lease</div>
                <div class="cc-system-count" style="color: #c084fc;">Deploy ephemeral supercomputer</div>
            </a>

            <!-- Custom ISO Forge -->
            <a href="#" class="cc-system-card" onclick="alert('ISO Forge compiler locked. Awaiting identity hook injection.'); return false;" style="border-color: rgba(234, 179, 8, 0.4);">
                <div class="cc-system-icon" style="color: #facc15;"><i class="fas fa-compact-disc"></i></div>
                <div class="cc-system-name" style="color: #fef08a;">ISO Forge</div>
                <div class="cc-system-count" style="color: #facc15;">Bake identity into physical media</div>
            </a>

            <!-- PXE Network Boot Token -->
            <a href="#" class="cc-system-card" onclick="alert('PXE Network Boot API offline. Awaiting iPXE server linkage.'); return false;" style="border-color: rgba(59, 130, 246, 0.4);">
                <div class="cc-system-icon" style="color: #60a5fa;"><i class="fas fa-network-wired"></i></div>
                <div class="cc-system-name" style="color: #bfdbfe;">PXE Stream</div>
                <div class="cc-system-count" style="color: #60a5fa;">Tactical network boot token</div>
            </a>

        </div>
    </div>

    <!-- RANK PROGRESSION TABLE -->
    <div class="cc-card">
        <div class="cc-card-title">&#x1F4C8; Rank Progression</div>
        <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:.82rem;">
            <thead>
                <tr style="border-bottom:1px solid var(--cc-border);">
                    <th style="text-align:left;padding:.5rem;color:var(--cc-muted);">Tier</th>
                    <th style="text-align:left;padding:.5rem;color:var(--cc-muted);">Rank</th>
                    <th style="text-align:left;padding:.5rem;color:var(--cc-muted);">Group</th>
                    <th style="text-align:right;padding:.5rem;color:var(--cc-muted);">XP Required</th>
                    <th style="text-align:center;padding:.5rem;color:var(--cc-muted);">Status</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $allRanks = $db->query("SELECT * FROM military_ranks ORDER BY rank_tier ASC")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($allRanks as $r):
                $isCurrentRank = ($r['rank_code'] === $userRankCode);
                $achieved = ($userRankTier >= (int)$r['rank_tier']);
                $rowStyle = $isCurrentRank ? 'background:var(--cc-gold-dim);' : '';
            ?>
                <tr style="border-bottom:1px solid rgba(255,255,255,0.03);<?= $rowStyle ?>">
                    <td style="padding:.5rem;font-weight:700;"><?= (int)$r['rank_tier'] ?></td>
                    <td style="padding:.5rem;<?= $isCurrentRank ? 'color:var(--cc-gold);font-weight:700;' : '' ?>"><?= htmlspecialchars($r['rank_name']) ?></td>
                    <td style="padding:.5rem;color:var(--cc-muted);text-transform:capitalize;"><?= htmlspecialchars($r['rank_group']) ?></td>
                    <td style="padding:.5rem;text-align:right;"><?= number_format((int)$r['xp_required']) ?></td>
                    <td style="padding:.5rem;text-align:center;">
                        <?php if ($isCurrentRank): ?>
                            <span style="color:var(--cc-gold);font-weight:700;">&#x25C0; YOU</span>
                        <?php elseif ($achieved): ?>
                            <span style="color:var(--cc-green);">&#x2705;</span>
                        <?php else: ?>
                            <span style="color:var(--cc-muted);">&#x1F512;</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>

</div>
</main>

<div class="cc-classbar <?= $classClass ?>"><?= $classLevel ?></div>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
