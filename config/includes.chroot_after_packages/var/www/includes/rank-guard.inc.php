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

// Redis cache for rank lookups (avoids 3 DB queries per page load)
$_rankRedis = null;
try {
    $_rankRedis = new Redis();
    $_rankRedis->connect('127.0.0.1', 6379, 0.5); // 500ms timeout
    $_rankRedis->setOption(Redis::OPT_PREFIX, 'rank:');
} catch (Exception $e) {
    $_rankRedis = null; // Fall through to DB
}

$userRank        = null;
$userRankCode    = 'civilian';
$userRankTier    = 0;
$userRankGroup   = null;
$userPermissions = [];

if (!empty($clientId)) {
    $cacheKey = "u:{$clientId}";
    $cached = $_rankRedis ? $_rankRedis->get($cacheKey) : false;

    if ($cached !== false) {
        $data = json_decode($cached, true);
        if ($data) {
            $userRank        = $data['rank'];
            $userRankCode    = $data['rankCode'];
            $userRankTier    = (int)$data['rankTier'];
            $userRankGroup   = $data['rankGroup'];
            $userPermissions = $data['permissions'];
        }
    }

    if ($cached === false) {
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

        // Cache the resolved rank data for 300 seconds (5 min per blueprint)
        if ($_rankRedis) {
            $_rankRedis->setex($cacheKey, 300, json_encode([
                'rank'        => $userRank,
                'rankCode'    => $userRankCode,
                'rankTier'    => $userRankTier,
                'rankGroup'   => $userRankGroup,
                'permissions' => $userPermissions,
            ]));
        }
    } // end cache-miss block
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

/**
 * Invalidate cached rank data for a user (call after promotion/demotion/elevation).
 */
function invalidateRankCache(int $clientId): void {
    try {
        $r = new Redis();
        $r->connect('127.0.0.1', 6379, 0.5);
        $r->setOption(Redis::OPT_PREFIX, 'rank:');
        $r->del("u:{$clientId}");
    } catch (Exception $e) {
        // Redis unavailable — cache will expire naturally
    }
}

// ═══════════════════════════════════════════════════════════════
//  LEVEL 2: PRODUCT GATE — Tier-based access per ecosystem pillar
// ═══════════════════════════════════════════════════════════════

/**
 * Product access tiers — defines minimum rank tier for each pillar/feature.
 * Tier 0 = civilian (anyone), Tier 1+ = enlisted, etc.
 */
define('PRODUCT_TIERS', [
    // Pillar access
    'search'        => 0,   // Everyone gets search
    'browser'       => 0,   // Everyone can download
    'pulse_read'    => 0,   // Everyone can read Pulse
    'pulse_post'    => 1,   // Enlisted+ can post
    'pulse_moderate'=> 4,   // NCO+ can moderate
    'metadome_basic'=> 0,   // Everyone enters basic zones
    'metadome_zones'=> 4,   // NCO+ access officer zones
    'metadome_create'=> 6,  // Officers create experiences
    'voice_basic'   => 1,   // Enlisted+ use STT/TTS
    'voice_campaign'=> 4,   // NCO+ create campaigns
    'voice_full'    => 6,   // Officers full campaign mgmt
    'veil_dm'       => 1,   // Enlisted+ get encrypted DMs
    'veil_squad'    => 4,   // NCO+ get squad channels
    'veil_dept'     => 6,   // Officers get dept channels
    'veil_emergency'=> 9,   // Flag+ get emergency
    'veil_owner'    => 11,  // Supreme only
    'ide_free'      => 1,   // Enlisted+ get free workspace
    'ide_pro'       => 6,   // Officers get pro workspace
    'ide_enterprise'=> 9,   // Flag+ get enterprise
    'warroom_view'  => 4,   // NCO+ view 50 core systems
    'warroom_full'  => 6,   // Officers view all systems
    'warroom_admin' => 9,   // Flag+ full control
    'warroom_create'=> 11,  // Supreme creates new systems
    'mesh_basic'    => 1,   // Enlisted+ get basic VPN
    'mesh_squad'    => 4,   // NCO+ get squad mesh
    'mesh_regional' => 6,   // Officers get regional mesh
    'mesh_global'   => 9,   // Flag+ get global mesh
    'rank_promote'  => 9,   // Flag+ can promote below own rank
    'court_judge'   => 9,   // Flag+ can serve as judge
    'hosting_priority'=>6,  // Officers get priority hosting support
    // Level 4 — MOS & Training
    'mos_view'        => 2,   // Corporal+ can view MOS catalog
    'mos_enroll'      => 3,   // Sergeant+ can enroll in MOS
    'training_view'   => 2,   // Corporal+ can view courses
    'training_enroll' => 2,   // Corporal+ can enroll in courses
    'training_admin'  => 9,   // Flag+ manage courses
    // Level 4 — Decorations
    'decoration_view' => 1,   // All enlisted see decorations
    'decoration_nominate' => 4, // NCO+ nominate
    'decoration_award'=> 7,   // Officers award decorations
    // Level 4 — War Games
    'wargame_view'    => 2,   // Corporal+ view games
    'wargame_join'    => 2,   // Corporal+ join games
    'wargame_create'  => 7,   // Officers create games
    // Level 4 — Chain of Command
    'coc_view'        => 1,   // All enlisted view orders/channels
    'coc_order_issue' => 7,   // Officers issue orders
    'coc_report_file' => 4,   // NCO+ file reports
    'coc_channel_admin'=> 7,  // Officers manage channels
    // Level 4 — Intelligence
    'intel_view'      => 3,   // Sergeant+ view intel
    'intel_file'      => 5,   // Staff Sgt+ file reports
    'intel_review'    => 7,   // Officers review intel
    'intel_classify'  => 9,   // Flag+ set classification
    // Level 4 — Supply & Economy
    'supply_view'     => 1,   // All enlisted access supply
    'supply_requisition'=> 1, // All enlisted can requisition
    'supply_approve'  => 7,   // Officers approve requisitions
    'supply_admin'    => 9,   // Flag+ manage inventory
    // Level 4 — Auto-Missions
    'automission_view'=> 1,   // All enlisted view missions
    'automission_accept'=> 1, // All enlisted accept missions
    'automission_generate'=> 7, // Officers generate from templates
    // Level 4 — Territory
    'territory_view'  => 2,   // Corporal+ view map
    'territory_claim' => 4,   // NCO+ claim territory
    'territory_attack'=> 4,   // NCO+ attack territory
    'territory_harvest'=> 2,  // Corporal+ harvest resources
    // Level 4 — Diplomacy
    'diplomacy_view'  => 3,   // Sergeant+ view relations
    'diplomacy_communicate'=> 5, // Staff Sgt+ send comms
    'diplomacy_treaty'=> 7,   // Officers propose treaties
    'diplomacy_ratify'=> 9,   // Flag+ ratify treaties
    'diplomacy_appoint'=> 8,  // Senior Officers appoint ambassadors
    // Level 4 — Hivemind
    'hivemind_view'   => 5,   // Staff Sgt+ view federation
    'hivemind_sync'   => 7,   // Officers trigger sync
    'hivemind_verify' => 7,   // Officers run verification
    'hivemind_register'=> 9,  // Flag+ register nodes
    // Level 4 — Military SDK/API
    'milapi_view'     => 4,   // NCO+ view API docs
    'milapi_create_key'=> 4,  // NCO+ create API keys
    'milapi_admin'    => 9,   // Flag+ manage all keys
    // Level 3 — Enlistment & Leaderboard
    'enlist_view'     => 0,   // Everyone can view enlistment
    'enlist_apply'    => 0,   // Everyone can apply
    'enlist_process'  => 7,   // Officers process applications
    'leaderboard_view'=> 0,   // Everyone can view leaderboard
    // Level 3 — Military HQ
    'hq_view'         => 1,   // Enlisted+ view HQ
    'hq_manage'       => 9,   // Flag+ manage HQ
    // Level 5 — Cyber Operations (CYBERCOM)
    'cyber_view'      => 7,   // Officers view cyber dashboards
    'cyber_operate'   => 7,   // Officers run operations
    'cyber_manage'    => 9,   // Generals manage campaigns
    'cyber_weapons'   => 11,  // Commander authorizes cyber weapons
    // Level 5 — Broadcasting (MIL-NET)
    'broadcast_view'  => 6,   // Officers view broadcasts
    'broadcast_transmit'=> 6, // Officers transmit
    'broadcast_manage'=> 7,   // Senior Officers manage network
    'broadcast_emergency'=> 9,// Generals emergency broadcast
    // Level 5 — Strategic Command (STRATCOM)
    'stratcom_view'   => 6,   // Officers view campaigns
    'stratcom_plan'   => 7,   // Senior Officers create war plans
    'stratcom_execute'=> 9,   // Generals execute campaigns
    'stratcom_doctrine'=> 11, // Commander sets doctrine
    // Level 5 — Arsenal
    'arsenal_view'    => 6,   // Officers view armory
    'arsenal_requisition'=> 6,// Officers requisition weapons
    'arsenal_manage'  => 7,   // Senior Officers manage inventory
    'arsenal_authorize'=> 9,  // Generals authorize heavy weapons
    // Level 5 — PsyOps
    'psyops_view'     => 6,   // Officers view operations
    'psyops_plan'     => 6,   // Officers plan campaigns
    'psyops_manage'   => 7,   // Senior Officers manage ops
    'psyops_authorize'=> 9,   // Generals authorize campaigns
    // Level 5 — Veterans Affairs
    'veterans_view'   => 4,   // NCO+ view services
    'veterans_submit' => 4,   // NCO+ submit claims
    'veterans_manage' => 7,   // Officers manage cases
    'veterans_admin'  => 9,   // Generals administer programs
    // Level 5 — Officer Candidate School (OCS)
    'ocs_view'        => 4,   // NCO+ view catalog
    'ocs_enroll'      => 4,   // NCO+ enroll in OCS
    'ocs_evaluate'    => 7,   // Officers evaluate candidates
    'ocs_graduate'    => 9,   // Generals commission graduates
    // Level 5 — Supreme Command
    'supreme_view'    => 9,   // Flag+ view command center
    'supreme_direct'  => 9,   // Flag+ direct forces
    'supreme_override'=> 11,  // Commander override
    // Level 5 — Nuclear Deterrence
    'nuclear_view'    => 9,   // Flag+ view deterrence status
    'nuclear_manage'  => 9,   // Flag+ manage posture
    'nuclear_authorize'=> 11, // Commander authorizes launch
    // Level 6 — Sovereign State
    'constitution_view'       => 0,   // All view
    'constitution_amend'      => 9,   // Generals propose amendments
    'constitution_ratify'     => 11,  // Commander ratifies
    'senate_view'             => 6,   // Officers view proceedings
    'senate_seat'             => 6,   // Officers eligible for seats
    'senate_vote'             => 6,   // Seated senators vote
    'senate_ratify'           => 11,  // Commander ratifies/vetoes
    'treasury_view'           => 6,   // Officers view indicators
    'treasury_manage'         => 9,   // Generals manage policy
    'treasury_approve'        => 11,  // Commander approves budget
    'socom_view'              => 7,   // Officers view (with invitation)
    'socom_manage'            => 9,   // Generals manage units
    'socom_ghost'             => 11,  // Commander authorizes Ghost Protocol
    'spacecom_view'           => 6,   // Officers view orbital status
    'spacecom_manage'         => 9,   // Generals manage assets
    'spacecom_weapon'         => 11,  // Commander authorizes weapons
    'mp_duty'                 => 4,   // NCO+ apply for MP
    'mp_investigate'          => 7,   // Officers investigate
    'mp_command'              => 9,   // Provost Marshal commands
    'civil_view'              => 0,   // All view registry
    'civil_manage'            => 6,   // Officers manage
    'civil_oversee'           => 9,   // Generals oversee
    'rnd_propose'             => 6,   // Officers propose projects
    'rnd_fund'                => 9,   // Generals fund
    'rnd_paradigm'            => 11,  // Commander authorizes paradigm shifts
    'warcollege_enroll'       => 7,   // Officers enroll
    'warcollege_evaluate'     => 9,   // Generals evaluate
    'warcollege_fellowship'   => 11,  // Commander selects fellow
    'homeland_view'           => 4,   // NCO+ view threat levels
    'homeland_investigate'    => 7,   // Officers investigate
    'homeland_manage'         => 9,   // Generals manage watchlist
    'homeland_emergency'      => 11,  // Commander declares emergencies
    'guard_enlist'            => 2,   // Corporal+ enlist
    'guard_lead'              => 4,   // NCO+ lead drill
    'guard_activate'          => 7,   // Officers activate units
    'guard_national'          => 11,  // Commander national activation
    'jag_view'                => 0,   // All view public rulings
    'jag_attorney'            => 7,   // Officers serve as attorneys
    'jag_appellate'           => 9,   // Generals as appellate judges
    'jag_final'               => 11,  // Commander final arbiter
    'signal_use'              => 2,   // Corporal+ use comms
    'signal_operate'          => 4,   // NCO+ operate
    'signal_manage'           => 7,   // Officers manage networks
    'signal_ew'               => 9,   // Generals direct EW campaigns
    'propaganda_view'         => 0,   // All view publications
    'propaganda_submit'       => 4,   // NCO+ submit content
    'propaganda_manage'       => 7,   // Officers manage campaigns
    'propaganda_review'       => 9,   // Generals on review board
    'energy_view'             => 6,   // Officers view dashboard
    'energy_manage'           => 9,   // Generals manage infrastructure
    'energy_policy'           => 11,  // Commander sets policy
]);

/**
 * Check if user has access to a specific product/feature.
 */
function hasProductAccess(string $product): bool {
    $tier = PRODUCT_TIERS[$product] ?? 99;
    return hasRank($tier);
}

/**
 * Gate a page/feature behind a product tier requirement.
 * Shows enlistment CTA for civilians, promotion message for ranked users.
 */
function requireProductAccess(string $product, string $label = ''): void {
    if (hasProductAccess($product)) return;

    $tier = PRODUCT_TIERS[$product] ?? 99;
    global $userRankCode, $userRankTier, $clientId;
    http_response_code(403);

    $productLabel = $label ?: ucfirst(str_replace('_', ' ', $product));

    include __DIR__ . '/site-header.inc.php';
    echo '<main class="main-content" style="padding:4rem 1.5rem;text-align:center;max-width:700px;margin:auto;">';

    if (empty($clientId) || $userRankTier === 0) {
        // Civilian — show enlistment CTA
        echo '<div style="font-size:4rem;margin-bottom:1rem;">&#x1F6E1;&#xFE0F;</div>';
        echo '<h1 style="color:#e2b340;margin-bottom:.5rem;">Join the Mission</h1>';
        echo '<p style="color:#ccc;margin-bottom:1rem;font-size:1.1rem;">';
        echo '<strong>' . htmlspecialchars($productLabel) . '</strong> is available to members of the GoSiteMe sovereign ecosystem.</p>';
        echo '<p style="color:#999;margin-bottom:2rem;">Enlist today — it\'s free, takes 30 seconds, and you\'ll earn your first XP immediately.</p>';
        echo '<div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">';
        echo '<a href="/enlist" class="btn btn-primary" style="padding:.8rem 2rem;font-size:1rem;">Enlist Now</a>';
        echo '<a href="/docs/field-manual" class="btn btn-secondary" style="padding:.8rem 2rem;font-size:1rem;">Read the Field Manual</a>';
        echo '</div>';
    } else {
        // Ranked but insufficient — show promotion path
        echo '<div style="font-size:3rem;margin-bottom:1rem;">&#x2B50;</div>';
        echo '<h1 style="color:#e2b340;margin-bottom:.5rem;">Rank Required</h1>';
        echo '<p style="color:#ccc;">Access to <strong>' . htmlspecialchars($productLabel) . '</strong> requires <strong>Tier ' . $tier . '+</strong>.</p>';
        echo '<p style="color:#999;">Your current rank: ' . getUserRankBadge() . ' (Tier ' . $userRankTier . ')</p>';
        echo '<p style="color:#666;margin-top:1.5rem;">Earn XP through missions, posts, games, and contributions to advance your rank.</p>';
        echo '<a href="/military-hq" class="btn btn-primary" style="margin-top:1rem;">View Your Service Record</a>';
    }

    echo '</main>';
    include __DIR__ . '/site-footer.inc.php';
    exit;
}

// ═══════════════════════════════════════════════════════════════
//  LEVEL 2: XP AWARD SERVICE — Every action earns XP
// ═══════════════════════════════════════════════════════════════

/**
 * XP values per action type. Rank multipliers applied automatically.
 */
define('XP_ACTIONS', [
    'daily_login'     => 10,
    'pulse_post'      => 5,
    'pulse_comment'   => 3,
    'pulse_like'      => 1,
    'game_play'       => 10,
    'game_win'        => 25,
    'game_wager_win'  => 50,
    'code_commit'     => 50,
    'extension_publish'=> 100,
    'bug_report'      => 15,
    'vr_visit'        => 5,
    'vr_create'       => 75,
    'voice_call'      => 10,
    'search_query'    => 1,
    'mission_complete'=> 30,
    'mission_critical'=> 100,
    'recruit_member'  => 200,
    'content_create'  => 20,
    'translation'     => 25,
    'moderation'      => 15,
    'mentoring'       => 40,
    'decoration_received' => 25,
    // Level 4 XP actions
    'mos_enroll'       => 50,
    'training_module'  => 30,
    'training_complete'=> 200,
    'training_exam'    => 75,
    'war_game'         => 50,
    'war_game_win'     => 150,
    'territory_capture'=> 200,
    'territory_defend' => 100,
    'territory_harvest'=> 10,
    'intel_file'       => 40,
    'intel_review'     => 25,
    'threat_report'    => 60,
    'order_issued'     => 30,
    'report_filed'     => 20,
    'requisition'      => 5,
    'supply_approve'   => 15,
    'auto_mission_complete' => 50,
    'treaty_proposed'  => 40,
    'treaty_ratified'  => 75,
    'diplomatic_comm'  => 15,
    'hivemind_sync'    => 50,
    'hivemind_verify'  => 25,
    'api_key_created'  => 10,
]);

/**
 * XP multipliers by rank group.
 */
define('XP_MULTIPLIERS', [
    'civilian' => 1.0,
    'enlisted' => 1.0,
    'nco'      => 1.5,
    'officer'  => 2.0,
    'flag'     => 3.0,
    'supreme'  => 5.0,
]);

/**
 * Award XP to a user for an action.
 * Handles multipliers, deduplication (daily login), and auto-promotion checks.
 *
 * @return array{xp_awarded: int, total_xp: int, rank_up: bool, new_rank: ?string}
 */
function awardXP(int $clientId, string $action, array $context = []): array {
    if (!isset(XP_ACTIONS[$action])) {
        return ['xp_awarded' => 0, 'total_xp' => 0, 'rank_up' => false, 'new_rank' => null];
    }

    $db = getSharedDB();

    // Deduplication for daily_login — only once per day
    if ($action === 'daily_login') {
        $check = $db->prepare("SELECT COUNT(*) FROM xp_ledger WHERE client_id = ? AND action = 'daily_login' AND DATE(created_at) = CURDATE()");
        $check->execute([$clientId]);
        if ($check->fetchColumn() > 0) {
            $xpStmt = $db->prepare("SELECT ur.xp FROM user_ranks ur JOIN military_ranks mr ON mr.rank_code = ur.rank_code WHERE ur.client_id = ? AND ur.is_active = 1 ORDER BY mr.rank_tier DESC LIMIT 1");
            $xpStmt->execute([$clientId]);
            return ['xp_awarded' => 0, 'total_xp' => (int)($xpStmt->fetchColumn() ?: 0), 'rank_up' => false, 'new_rank' => null];
        }
    }

    // Get user's rank group for multiplier
    global $userRankGroup;
    $group = $userRankGroup ?? 'civilian';
    $multiplier = XP_MULTIPLIERS[$group] ?? 1.0;
    $baseXP = XP_ACTIONS[$action];
    $xpAwarded = (int)round($baseXP * $multiplier);

    // Record in XP ledger
    $ledgerStmt = $db->prepare("
        INSERT INTO xp_ledger (client_id, action, xp_amount, multiplier, context, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $ledgerStmt->execute([$clientId, $action, $xpAwarded, $multiplier, json_encode($context)]);

    // Update user_ranks XP total
    $updateStmt = $db->prepare("UPDATE user_ranks SET xp = xp + ? WHERE client_id = ? AND is_active = 1");
    $updateStmt->execute([$xpAwarded, $clientId]);

    // Get new total XP
    $totalStmt = $db->prepare("SELECT ur.xp, ur.rank_code FROM user_ranks ur JOIN military_ranks mr ON mr.rank_code = ur.rank_code WHERE ur.client_id = ? AND ur.is_active = 1 ORDER BY mr.rank_tier DESC LIMIT 1");
    $totalStmt->execute([$clientId]);
    $current = $totalStmt->fetch();
    $totalXP = (int)($current['xp'] ?? 0);
    $currentRank = $current['rank_code'] ?? null;

    // Check for auto-promotion
    $rankUp = false;
    $newRank = null;
    if ($currentRank) {
        $nextStmt = $db->prepare("
            SELECT rank_code, rank_name, rank_tier, xp_required
            FROM military_ranks
            WHERE xp_required <= ? AND rank_tier > (SELECT rank_tier FROM military_ranks WHERE rank_code = ?)
            ORDER BY rank_tier ASC
            LIMIT 1
        ");
        $nextStmt->execute([$totalXP, $currentRank]);
        $nextRank = $nextStmt->fetch();

        if ($nextRank) {
            // Auto-promote (user_ranks has no rank_tier column — tier resolves via JOIN)
            $db->prepare("UPDATE user_ranks SET rank_code = ? WHERE client_id = ? AND is_active = 1")
               ->execute([$nextRank['rank_code'], $clientId]);

            // Log promotion
            $db->prepare("INSERT INTO rank_history (client_id, action, from_rank, to_rank, reason, performed_by, performed_at) VALUES (?, 'promote', ?, ?, ?, 0, NOW())")
               ->execute([$clientId, $currentRank, $nextRank['rank_code'], "Auto-promotion: XP threshold reached ({$totalXP})"]);

            $rankUp = true;
            $newRank = $nextRank['rank_name'];

            // Invalidate rank cache
            invalidateRankCache($clientId);
        }
    }

    return ['xp_awarded' => $xpAwarded, 'total_xp' => $totalXP, 'rank_up' => $rankUp, 'new_rank' => $newRank];
}

/**
 * Get XP leaderboard.
 */
function getXPLeaderboard(int $limit = 25): array {
    $db = getSharedDB();
    $stmt = $db->prepare("
        SELECT ur.client_id, ur.xp, ur.rank_code, mr.rank_name, mr.rank_tier, mr.rank_group, mr.badge_icon,
               c.firstname, c.lastname
        FROM user_ranks ur
        JOIN military_ranks mr ON mr.rank_code = ur.rank_code
        LEFT JOIN clients c ON c.id = ur.client_id
        WHERE ur.is_active = 1
        ORDER BY ur.xp DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get a user's XP history (last N actions).
 */
function getXPHistory(int $clientId, int $limit = 50): array {
    $db = getSharedDB();
    $stmt = $db->prepare("SELECT action, xp_amount, multiplier, context, created_at FROM xp_ledger WHERE client_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$clientId, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
