<?php
/**
 * Rank Guard — Military rank-based access control middleware.
 *
 * Include this after auth-gate.inc.php. It resolves the user's effective rank
 * (including temporary elevations) and exposes helper functions.
 *
 * Provides:
 *   $userRank         — array with rank details or null
 *   $userRankCode     — string rank_code (e.g. 'sergeant') or 'civilian'
 *   $userRankTier     — int tier (0 for civilians)
 *   $userPermissions  — array of permission keys
 *
 * Functions:
 *   hasRank(int $minTier)        — true if user's tier >= $minTier
 *   hasPermission(string $key)   — true if user has the permission
 *   requireRank(int $minTier)    — die with 403 if insufficient
 *   requirePermission(string $k) — die with 403 if missing
 *   getUserRankBadge()           — HTML badge for the user's rank
 */

if (!defined('GOSITEME_DB_CONFIGURED')) {
    require_once __DIR__ . '/db-config.inc.php';
}

$userRank        = null;
$userRankCode    = 'civilian';
$userRankTier    = 0;
$userRankGroup   = null;
$userPermissions = [];

if (!empty($clientId)) {
    $db = getSharedDB();

    // Check for active temporary elevation first
    $elevStmt = $db->prepare("
        SELECT re.elevated_rank AS rank_code, mr.*
        FROM rank_elevations re
        JOIN military_ranks mr ON mr.rank_code = re.elevated_rank
        WHERE re.client_id = ? AND re.is_active = 1 AND re.expires_at > NOW() AND re.revoked_at IS NULL
        ORDER BY mr.rank_tier DESC
        LIMIT 1
    ");
    $elevStmt->execute([$clientId]);
    $tempRank = $elevStmt->fetch();

    if ($tempRank) {
        $userRank = $tempRank;
        $userRank['is_temporary'] = true;
    } else {
        // Permanent rank
        $rankStmt = $db->prepare("
            SELECT ur.*, mr.rank_name, mr.rank_tier, mr.rank_group, mr.clearance_level,
                   mr.max_fleet_view, mr.badge_icon, mr.description AS rank_description
            FROM user_ranks ur
            JOIN military_ranks mr ON mr.rank_code = ur.rank_code
            WHERE ur.client_id = ? AND ur.is_active = 1
            ORDER BY mr.rank_tier DESC
            LIMIT 1
        ");
        $rankStmt->execute([$clientId]);
        $userRank = $rankStmt->fetch() ?: null;
        if ($userRank) $userRank['is_temporary'] = false;
    }

    if ($userRank) {
        $userRankCode  = $userRank['rank_code'];
        $userRankTier  = (int)$userRank['rank_tier'];
        $userRankGroup = $userRank['rank_group'];

        // Load permissions for this rank
        $permStmt = $db->prepare("SELECT permission_key FROM rank_permissions WHERE rank_code = ? AND granted = 1");
        $permStmt->execute([$userRankCode]);
        $userPermissions = $permStmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Auto-expire temporary elevations
    $db->exec("UPDATE rank_elevations SET is_active = 0 WHERE is_active = 1 AND expires_at <= NOW()");
}

function hasRank(int $minTier): bool {
    global $userRankTier;
    return $userRankTier >= $minTier;
}

function hasPermission(string $key): bool {
    global $userPermissions;
    return in_array($key, $userPermissions, true);
}

function requireRank(int $minTier, string $label = ''): void {
    if (hasRank($minTier)) return;

    http_response_code(403);
    global $userRankCode, $userRankTier;
    $needed = $label ?: "Tier $minTier";
    include __DIR__ . '/site-header.inc.php';
    echo '<main class="main-content" style="padding:4rem 1.5rem;text-align:center;max-width:700px;margin:auto;">';
    echo '<div style="font-size:3rem;margin-bottom:1rem;">&#x1F6E1;</div>';
    echo '<h1 style="color:#e2b340;margin-bottom:.5rem;">Access Restricted</h1>';
    echo '<p style="color:#999;margin-bottom:2rem;">This area requires rank <strong>' . htmlspecialchars($needed) . '</strong> or higher.</p>';
    echo '<p style="color:#666;">Your current rank: <strong>' . htmlspecialchars($userRankCode) . '</strong> (Tier ' . $userRankTier . ')</p>';
    echo '<a href="/military-hq" class="btn btn-primary" style="margin-top:1.5rem;">Return to HQ</a>';
    echo '</main>';
    include __DIR__ . '/site-footer.inc.php';
    exit;
}

function requirePermission(string $key): void {
    if (hasPermission($key)) return;

    http_response_code(403);
    global $userRankCode;
    include __DIR__ . '/site-header.inc.php';
    echo '<main class="main-content" style="padding:4rem 1.5rem;text-align:center;max-width:700px;margin:auto;">';
    echo '<div style="font-size:3rem;margin-bottom:1rem;">&#x1F512;</div>';
    echo '<h1 style="color:#e2b340;margin-bottom:.5rem;">Permission Denied</h1>';
    echo '<p style="color:#999;">You need the <code>' . htmlspecialchars($key) . '</code> permission.</p>';
    echo '<p style="color:#666;">Rank: <strong>' . htmlspecialchars($userRankCode) . '</strong>. Request promotion from your commanding officer.</p>';
    echo '<a href="/military-hq" class="btn btn-primary" style="margin-top:1.5rem;">Return to HQ</a>';
    echo '</main>';
    include __DIR__ . '/site-footer.inc.php';
    exit;
}

function getUserRankBadge(): string {
    global $userRank, $userRankCode, $userRankTier;
    if (!$userRank) return '<span class="rank-badge rank-civilian">Civilian</span>';

    $colors = [
        'enlisted' => '#4a6741',
        'nco'      => '#5a7a50',
        'officer'  => '#2a5a8a',
        'flag'     => '#8a2a2a',
        'supreme'  => '#e2b340',
    ];
    $group = $userRank['rank_group'] ?? 'enlisted';
    $color = $colors[$group] ?? '#444';
    $name  = htmlspecialchars($userRank['rank_name'] ?? $userRankCode);
    $temp  = !empty($userRank['is_temporary']) ? ' <small style="opacity:.7">(temp)</small>' : '';

    return '<span class="rank-badge" style="background:' . $color . ';color:#fff;padding:3px 10px;border-radius:4px;font-size:12px;font-weight:600;letter-spacing:.5px;text-transform:uppercase;">'
         . $name . $temp . '</span>';
}

function getRankTierStars(int $tier): string {
    if ($tier >= 11) return str_repeat('&#9733;', 5) . ' &#x1F451;';
    if ($tier >= 10) return str_repeat('&#9733;', 4);
    if ($tier >= 9)  return str_repeat('&#9733;', 3);
    if ($tier >= 6)  return str_repeat('&#9733;', 2);
    if ($tier >= 4)  return str_repeat('&#9733;', 1);
    return '&#x25CB;';
}
