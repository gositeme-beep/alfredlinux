<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 *  SERVICE RECORD — Military Profile Page
 * ═══════════════════════════════════════════════════════════════════════════
 *  Displays a member's military rank, service record, XP, achievements,
 *  department assignment, and Pulse social feed — all in one page.
 *
 *  Routes:
 *    /service-record.php           → own profile (if logged in)
 *    /service-record.php?id=33     → view by client_id
 *    /service-record.php?name=Danny → view by display_name
 * ═══════════════════════════════════════════════════════════════════════════
 */

require_once __DIR__ . '/includes/db-config.inc.php';

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.use_strict_mode', 1);
    session_start();
}

$db = getSharedDB();
$isLoggedIn = !empty($_SESSION['logged_in']) && !empty($_SESSION['client_id']);
$myClientId = $isLoggedIn ? (int)$_SESSION['client_id'] : 0;

// ─── Determine which profile to show ────────────────────────────────────────
$viewClientId = 0;
if (isset($_GET['id'])) {
    $viewClientId = (int)$_GET['id'];
} elseif (isset($_GET['name'])) {
    $stmt = $db->prepare("SELECT client_id FROM alfred_military_roster WHERE display_name LIKE ? AND status='active' LIMIT 1");
    $stmt->execute(['%' . $_GET['name'] . '%']);
    $viewClientId = (int)$stmt->fetchColumn();
} elseif ($isLoggedIn) {
    $viewClientId = $myClientId;
}

if (!$viewClientId) {
    // No valid profile → show enlistment CTA
    $showEnlistCTA = true;
} else {
    $showEnlistCTA = false;
}

// ─── Load service record ────────────────────────────────────────────────────
$member = null;
$rankInfo = null;
$nextRank = null;
$achievements = [];
$xpSummary = null;
$streaks = null;
$pulseProfile = null;
$pulsePosts = [];
$socialStats = ['posts' => 0, 'followers' => 0, 'following' => 0];
$isOwnProfile = false;

if ($viewClientId) {
    // Military roster entry
    $stmt = $db->prepare("SELECT r.*, m.rank_name, m.min_xp, m.min_days_active, m.permissions, m.description AS rank_desc 
                          FROM alfred_military_roster r 
                          JOIN alfred_military_ranks m ON r.rank_code = m.rank_code 
                          WHERE r.client_id = ? AND r.status IN ('active','suspended') 
                          LIMIT 1");
    $stmt->execute([$viewClientId]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$member) {
        // Check if they're in the system but not in military roster
        $stmt = $db->prepare("SELECT id, firstname, lastname, email FROM clients WHERE id = ? LIMIT 1");
        $stmt->execute([$viewClientId]);
        $clientRow = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($clientRow) {
            // They exist but aren't enlisted
            $showEnlistCTA = true;
            $civilianName = trim($clientRow['firstname'] . ' ' . $clientRow['lastname']) ?: $clientRow['email'];
        }
    }

    if ($member) {
        $isOwnProfile = ($viewClientId === $myClientId);

        // Next rank
        $stmt = $db->prepare("SELECT * FROM alfred_military_ranks WHERE rank_level > ? ORDER BY rank_level ASC LIMIT 1");
        $stmt->execute([$member['rank_level']]);
        $nextRank = $stmt->fetch(PDO::FETCH_ASSOC);

        // XP summary
        $stmt = $db->prepare("SELECT * FROM alfred_user_xp_summary WHERE user_id = ? LIMIT 1");
        $stmt->execute([$viewClientId]);
        $xpSummary = $stmt->fetch(PDO::FETCH_ASSOC);

        // Achievements
        $stmt = $db->prepare("SELECT * FROM alfred_achievements WHERE user_id = ? AND unlocked_at IS NOT NULL ORDER BY unlocked_at DESC LIMIT 20");
        $stmt->execute([$viewClientId]);
        $achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Streaks
        $stmt = $db->prepare("SELECT * FROM alfred_streaks WHERE user_id = ? ORDER BY current_count DESC LIMIT 5");
        $stmt->execute([$viewClientId]);
        $streaks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Pulse profile
        $stmt = $db->prepare("SELECT * FROM pulse_profiles WHERE user_id = ? LIMIT 1");
        $stmt->execute([$viewClientId]);
        $pulseProfile = $stmt->fetch(PDO::FETCH_ASSOC);

        // Pulse posts (recent)
        $stmt = $db->prepare("SELECT * FROM pulse_posts WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
        $stmt->execute([$viewClientId]);
        $pulsePosts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Social stats
        $socialStats['posts'] = (int)$db->prepare("SELECT COUNT(*) FROM pulse_posts WHERE user_id = ?")->execute([$viewClientId]) ? $db->query("SELECT FOUND_ROWS()")->fetchColumn() : count($pulsePosts);
        $stmt = $db->prepare("SELECT COUNT(*) FROM pulse_posts WHERE user_id = ?");
        $stmt->execute([$viewClientId]);
        $socialStats['posts'] = (int)$stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM pulse_follows WHERE following_id = ?");
        $stmt->execute([$viewClientId]);
        $socialStats['followers'] = (int)$stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM pulse_follows WHERE follower_id = ?");
        $stmt->execute([$viewClientId]);
        $socialStats['following'] = (int)$stmt->fetchColumn();

        // Promotion history
        $stmt = $db->prepare("SELECT * FROM alfred_promotion_log WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
        $stmt->execute([$viewClientId]);
        $promotions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Days in service
        $enlistDate = new DateTime($member['created_at']);
        $now = new DateTime();
        $daysInService = $enlistDate->diff($now)->days;

        // Gravatar fallback
        $stmt = $db->prepare("SELECT email FROM clients WHERE id = ? LIMIT 1");
        $stmt->execute([$viewClientId]);
        $emailRow = $stmt->fetch(PDO::FETCH_ASSOC);
        $email = $emailRow['email'] ?? '';
        $gravatarUrl = 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($email))) . '?d=identicon&s=160';
        $avatarUrl = $pulseProfile['avatar_url'] ?? $gravatarUrl;
    }
}

// Rank color mapping
function getRankColor(string $code): string {
    $map = [
        'O-6' => '#d4a017', 'O-5' => '#c0392b', 'O-4' => '#8e44ad',
        'O-3' => '#2980b9', 'O-2' => '#27ae60', 'O-1' => '#16a085',
        'E-4' => '#f39c12', 'E-3' => '#e67e22', 'E-2' => '#3498db',
        'E-1' => '#95a5a6', 'E-0' => '#7f8c8d',
    ];
    return $map[$code] ?? '#95a5a6';
}

function getRankIcon(string $code): string {
    $map = [
        'O-6' => 'fa-crown', 'O-5' => 'fa-star', 'O-4' => 'fa-medal',
        'O-3' => 'fa-shield-alt', 'O-2' => 'fa-anchor', 'O-1' => 'fa-chevron-up',
        'E-4' => 'fa-chevron-up', 'E-3' => 'fa-chevron-up', 'E-2' => 'fa-user',
        'E-1' => 'fa-user', 'E-0' => 'fa-user',
    ];
    return $map[$code] ?? 'fa-user';
}

function getBadgeTierColor(string $tier): string {
    $map = [
        'bronze' => '#cd7f32', 'silver' => '#c0c0c0', 'gold' => '#ffd700',
        'platinum' => '#e5e4e2', 'diamond' => '#b9f2ff',
    ];
    return $map[strtolower($tier)] ?? '#95a5a6';
}

function timeAgo(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff/60) . 'm ago';
    if ($diff < 86400) return floor($diff/3600) . 'h ago';
    if ($diff < 604800) return floor($diff/86400) . 'd ago';
    return date('M j, Y', strtotime($datetime));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $member ? htmlspecialchars($member['rank_name'] . ' ' . $member['display_name']) . ' — Service Record' : 'Service Record — GoSiteMe Military Institution' ?></title>
<meta name="description" content="<?= $member ? htmlspecialchars($member['rank_name'] . ' ' . $member['display_name'] . ' — ' . $member['rank_desc']) : 'View military service records for GoSiteMe/MetaDome institution members.' ?>">
<meta property="og:title" content="<?= $member ? htmlspecialchars($member['rank_name'] . ' ' . $member['display_name']) : 'Service Record' ?>">
<meta property="og:description" content="<?= $member ? 'Rank: ' . htmlspecialchars($member['rank_code'] . ' ' . $member['rank_name']) . ' | Days in service: ' . $daysInService : 'GoSiteMe Military Institution' ?>">
<meta property="og:image" content="https://root.com/assets/images/alfred-icon-512.png">
<link rel="icon" type="image/png" href="/favicon.png">
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<style>
*, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

:root {
    --sr-bg: #020208;
    --sr-surface: #0a0a14;
    --sr-surface-2: #10101c;
    --sr-surface-3: #181828;
    --sr-card: rgba(255,255,255,0.03);
    --sr-border: rgba(255,255,255,0.06);
    --sr-text: rgba(255,255,255,0.9);
    --sr-muted: rgba(255,255,255,0.5);
    --sr-dim: rgba(255,255,255,0.3);
    --sr-gold: #d4a017;
    --sr-gold-light: #f5c542;
    --sr-green: #10b981;
    --sr-cyan: #06b6d4;
    --sr-red: #dc2626;
    --sr-purple: #8b5cf6;
    --sr-blue: #3b82f6;
    --sr-coral: #f97316;
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: var(--sr-bg);
    color: var(--sr-text);
    line-height: 1.6;
    -webkit-font-smoothing: antialiased;
}

/* ═══ CLASSIFICATION BAR ═══ */
.sr-class-bar {
    background: var(--sr-gold);
    color: #000;
    text-align: center;
    padding: 5px 0;
    font-size: 0.65rem;
    font-weight: 800;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    position: sticky;
    top: 0;
    z-index: 1000;
}

/* ═══ NAV ═══ */
.sr-nav {
    position: sticky;
    top: 24px;
    z-index: 999;
    background: rgba(2,2,8,0.92);
    backdrop-filter: blur(12px);
    border-bottom: 1px solid var(--sr-border);
    padding: 0.5rem 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.sr-nav a {
    color: var(--sr-muted);
    text-decoration: none;
    font-size: 0.75rem;
    font-weight: 600;
    transition: color 0.2s;
}
.sr-nav a:hover { color: var(--sr-gold); }
.sr-nav-brand {
    color: var(--sr-gold) !important;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    font-size: 0.7rem !important;
}
.sr-nav-links { display: flex; gap: 1rem; }

/* ═══ PROFILE COVER ═══ */
.sr-cover {
    height: 200px;
    position: relative;
    overflow: hidden;
}
.sr-cover-bg {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, 
        rgba(212,160,23,0.15) 0%, 
        rgba(139,92,246,0.1) 40%, 
        rgba(220,38,38,0.08) 100%);
}
.sr-cover-pattern {
    position: absolute;
    inset: 0;
    background: repeating-linear-gradient(
        45deg,
        transparent,
        transparent 40px,
        rgba(212,160,23,0.02) 40px,
        rgba(212,160,23,0.02) 80px
    );
}
.sr-cover-fade {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 80px;
    background: linear-gradient(transparent, var(--sr-bg));
}

/* ═══ PROFILE HERO ═══ */
.sr-container { max-width: 960px; margin: 0 auto; padding: 0 1.5rem; }

.sr-hero {
    margin-top: -64px;
    position: relative;
    z-index: 10;
    padding-bottom: 2rem;
    border-bottom: 1px solid var(--sr-border);
}
.sr-hero-top {
    display: flex;
    gap: 1.5rem;
    align-items: flex-end;
}
.sr-avatar {
    width: 128px;
    height: 128px;
    border-radius: 50%;
    border: 4px solid var(--sr-bg);
    overflow: hidden;
    flex-shrink: 0;
    position: relative;
    box-shadow: 0 0 0 3px var(--sr-gold);
}
.sr-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.sr-avatar-initials {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    font-weight: 900;
    color: #fff;
}
.sr-rank-badge-floating {
    position: absolute;
    bottom: -4px;
    right: -4px;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    color: #000;
    font-weight: 900;
    border: 3px solid var(--sr-bg);
    z-index: 11;
}
.sr-hero-info {
    flex: 1;
    padding-bottom: 0.5rem;
}
.sr-hero-rank {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    padding: 0.25rem 0.8rem;
    border-radius: 2px;
    margin-bottom: 0.5rem;
}
.sr-hero-name {
    font-size: 1.8rem;
    font-weight: 900;
    letter-spacing: -0.02em;
    margin-bottom: 0.2rem;
}
.sr-hero-subtitle {
    font-size: 0.85rem;
    color: var(--sr-muted);
}
.sr-hero-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.8rem;
}
.sr-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.5rem 1.2rem;
    font-size: 0.78rem;
    font-weight: 700;
    border-radius: 4px;
    border: none;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s;
}
.sr-btn-gold {
    background: var(--sr-gold);
    color: #000;
}
.sr-btn-gold:hover { background: var(--sr-gold-light); }
.sr-btn-outline {
    background: transparent;
    border: 1px solid var(--sr-border);
    color: var(--sr-text);
}
.sr-btn-outline:hover { border-color: var(--sr-gold); color: var(--sr-gold); }
.sr-btn-pulse {
    background: linear-gradient(135deg, var(--sr-blue), var(--sr-coral));
    color: #fff;
}
.sr-btn-pulse:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(59,130,246,0.3); }

/* ═══ STATS ROW ═══ */
.sr-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
    gap: 1px;
    background: var(--sr-border);
    border: 1px solid var(--sr-border);
    margin: 1.5rem 0;
    border-radius: 4px;
    overflow: hidden;
}
.sr-stat {
    background: var(--sr-surface);
    padding: 1rem 0.8rem;
    text-align: center;
}
.sr-stat-val {
    font-size: 1.3rem;
    font-weight: 900;
    font-family: 'JetBrains Mono', monospace;
}
.sr-stat-label {
    font-size: 0.62rem;
    color: var(--sr-dim);
    letter-spacing: 0.1em;
    text-transform: uppercase;
    margin-top: 0.15rem;
}

/* ═══ TWO COLUMN LAYOUT ═══ */
.sr-layout {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 1.5rem;
    padding: 1.5rem 0 4rem;
}

/* ═══ CARDS ═══ */
.sr-card {
    background: var(--sr-surface);
    border: 1px solid var(--sr-border);
    border-radius: 8px;
    margin-bottom: 1rem;
    overflow: hidden;
}
.sr-card-header {
    padding: 1rem 1.2rem;
    border-bottom: 1px solid var(--sr-border);
    display: flex;
    align-items: center;
    gap: 0.6rem;
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--sr-gold);
}
.sr-card-header i { font-size: 0.85rem; }
.sr-card-body { padding: 1.2rem; }

/* ═══ RANK PROGRESSION ═══ */
.sr-rank-progress {
    position: relative;
    padding: 0.5rem 0;
}
.sr-rank-bar-bg {
    width: 100%;
    height: 8px;
    background: var(--sr-surface-3);
    border-radius: 4px;
    overflow: hidden;
    margin: 0.5rem 0;
}
.sr-rank-bar-fill {
    height: 100%;
    border-radius: 4px;
    transition: width 0.6s ease;
}
.sr-rank-bar-labels {
    display: flex;
    justify-content: space-between;
    font-size: 0.72rem;
    color: var(--sr-muted);
}
.sr-rank-bar-labels strong { color: var(--sr-text); }

/* ═══ PERMISSIONS ═══ */
.sr-perm {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.5rem 0;
    font-size: 0.82rem;
    border-bottom: 1px solid rgba(255,255,255,0.03);
}
.sr-perm:last-child { border-bottom: none; }
.sr-perm i.granted { color: var(--sr-green); }
.sr-perm i.denied { color: var(--sr-red); opacity: 0.5; }
.sr-perm span.denied-text { color: var(--sr-dim); }

/* ═══ SERVICE TIMELINE ═══ */
.sr-timeline { position: relative; padding-left: 1.5rem; }
.sr-timeline::before {
    content: '';
    position: absolute;
    left: 0.35rem;
    top: 0;
    bottom: 0;
    width: 2px;
    background: linear-gradient(180deg, var(--sr-gold), var(--sr-green));
}
.sr-timeline-item {
    position: relative;
    padding: 0.8rem 0;
}
.sr-timeline-item::before {
    content: '';
    position: absolute;
    left: -1.15rem;
    top: 1.1rem;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--sr-gold);
    border: 2px solid var(--sr-bg);
}
.sr-timeline-date {
    font-size: 0.68rem;
    color: var(--sr-dim);
    letter-spacing: 0.05em;
}
.sr-timeline-text {
    font-size: 0.82rem;
    margin-top: 0.15rem;
}

/* ═══ ACHIEVEMENT BADGES ═══ */
.sr-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}
.sr-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.35rem 0.7rem;
    border-radius: 4px;
    font-size: 0.72rem;
    font-weight: 700;
    border: 1px solid;
}
.sr-badge i { font-size: 0.65rem; }

/* ═══ SOCIAL POST CARDS ═══ */
.sr-post {
    padding: 1rem 1.2rem;
    border-bottom: 1px solid rgba(255,255,255,0.03);
}
.sr-post:last-child { border-bottom: none; }
.sr-post-content {
    font-size: 0.88rem;
    line-height: 1.6;
    margin-bottom: 0.5rem;
}
.sr-post-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.72rem;
    color: var(--sr-dim);
}
.sr-post-meta span { display: inline-flex; align-items: center; gap: 0.3rem; }

/* ═══ EMPTY STATE ═══ */
.sr-empty {
    text-align: center;
    padding: 2rem;
    color: var(--sr-dim);
    font-size: 0.85rem;
}
.sr-empty i {
    font-size: 2rem;
    display: block;
    margin-bottom: 0.8rem;
    color: var(--sr-gold);
    opacity: 0.3;
}

/* ═══ BIG ENLIST CTA ═══ */
.sr-enlist-hero {
    text-align: center;
    padding: 6rem 2rem;
    background: radial-gradient(ellipse 60% 40% at 50% 30%, rgba(212,160,23,0.06), transparent);
}
.sr-enlist-hero h1 {
    font-size: 2rem;
    font-weight: 900;
    margin-bottom: 1rem;
}
.sr-enlist-hero h1 .gold {
    background: linear-gradient(135deg, var(--sr-gold), var(--sr-gold-light));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.sr-enlist-hero p {
    color: var(--sr-muted);
    max-width: 500px;
    margin: 0 auto 2rem;
    font-size: 1rem;
}
.sr-btn-big {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    padding: 1rem 2.5rem;
    background: var(--sr-gold);
    color: #000;
    font-weight: 800;
    font-size: 0.9rem;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    text-decoration: none;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s;
}
.sr-btn-big:hover {
    background: var(--sr-gold-light);
    transform: translateY(-2px);
    box-shadow: 0 6px 24px rgba(212,160,23,0.3);
}

/* ═══ RESPONSIVE ═══ */
@media (max-width: 800px) {
    .sr-layout { grid-template-columns: 1fr; }
    .sr-hero-top { flex-direction: column; align-items: center; text-align: center; }
    .sr-hero-info { text-align: center; }
    .sr-hero-actions { justify-content: center; }
    .sr-avatar { width: 100px; height: 100px; }
    .sr-hero-name { font-size: 1.4rem; }
    .sr-cover { height: 150px; }
    .sr-hero { margin-top: -50px; }
    .sr-stats { grid-template-columns: repeat(3, 1fr); }
    .sr-nav-links { display: none; }
}
</style>
</head>
<body>

<!-- CLASSIFICATION BAR -->
<div class="sr-class-bar">Service Record — GoSiteMe / MetaDome Military Institution</div>

<!-- NAV -->
<nav class="sr-nav">
    <a href="/docs/field-manual" class="sr-nav-brand"><i class="fas fa-shield-alt"></i> Field Manual</a>
    <div class="sr-nav-links">
        <a href="/pulse">Pulse</a>
        <a href="/docs/field-manual#ranks">Ranks</a>
        <a href="/docs/field-manual#departments">Departments</a>
        <a href="/dashboard.php">Dashboard</a>
        <a href="https://meta-dome.com/passport">Passport</a>
    </div>
</nav>

<?php if ($showEnlistCTA): ?>
<!-- ═══════════════════════════════════════ -->
<!--           NOT ENLISTED                 -->
<!-- ═══════════════════════════════════════ -->
<div class="sr-enlist-hero">
    <div style="font-size: 3rem; color: var(--sr-gold); margin-bottom: 1rem; opacity: 0.6;"><i class="fas fa-shield-alt"></i></div>
    <h1><span class="gold">Service Record</span></h1>
    <p>
        <?php if (isset($civilianName)): ?>
            <?= htmlspecialchars($civilianName) ?>, you are not yet enlisted in the institution.
        <?php else: ?>
            This member has not yet enlisted. To view a service record, the individual must first secure a passport and take the oath.
        <?php endif; ?>
    </p>
    <a href="https://meta-dome.com/passport" class="sr-btn-big">
        <i class="fas fa-passport"></i> Secure Your Passport
    </a>
    <br><br>
    <a href="/docs/field-manual" class="sr-btn sr-btn-outline" style="font-size: 0.85rem; padding: 0.6rem 1.5rem;">
        <i class="fas fa-book"></i> Read the Field Manual
    </a>
</div>

<?php else: ?>
<!-- ═══════════════════════════════════════ -->
<!--           PROFILE COVER               -->
<!-- ═══════════════════════════════════════ -->
<div class="sr-cover">
    <?php if (!empty($pulseProfile['cover_url'])): ?>
        <img src="<?= htmlspecialchars($pulseProfile['cover_url']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
    <?php endif; ?>
    <div class="sr-cover-bg"></div>
    <div class="sr-cover-pattern"></div>
    <div class="sr-cover-fade"></div>
</div>

<!-- ═══════════════════════════════════════ -->
<!--           PROFILE HERO                -->
<!-- ═══════════════════════════════════════ -->
<div class="sr-container">
    <div class="sr-hero">
        <div class="sr-hero-top">
            <!-- AVATAR -->
            <div class="sr-avatar" style="box-shadow: 0 0 0 3px <?= getRankColor($member['rank_code']) ?>;">
                <?php
                    $initials = '';
                    $parts = explode(' ', $member['display_name']);
                    $initials = strtoupper(mb_substr($parts[0], 0, 1) . (isset($parts[1]) ? mb_substr($parts[1], 0, 1) : ''));
                ?>
                <?php if ($avatarUrl && strpos($avatarUrl, 'identicon') === false): ?>
                    <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="<?= htmlspecialchars($member['display_name']) ?>">
                <?php else: ?>
                    <div class="sr-avatar-initials" style="background: linear-gradient(135deg, <?= getRankColor($member['rank_code']) ?>, #1a1a2e);">
                        <?= $initials ?>
                    </div>
                <?php endif; ?>
                <div class="sr-rank-badge-floating" style="background: <?= getRankColor($member['rank_code']) ?>;">
                    <i class="fas <?= getRankIcon($member['rank_code']) ?>"></i>
                </div>
            </div>

            <!-- INFO -->
            <div class="sr-hero-info">
                <div class="sr-hero-rank" style="background: <?= getRankColor($member['rank_code']) ?>22; color: <?= getRankColor($member['rank_code']) ?>; border: 1px solid <?= getRankColor($member['rank_code']) ?>44;">
                    <i class="fas <?= getRankIcon($member['rank_code']) ?>"></i>
                    <?= htmlspecialchars($member['rank_code'] . ' — ' . $member['rank_name']) ?>
                </div>
                <div class="sr-hero-name"><?= htmlspecialchars($member['display_name']) ?></div>
                <div class="sr-hero-subtitle">
                    <?= htmlspecialchars($member['rank_desc']) ?>
                    <?php if (!empty($pulseProfile['bio'])): ?>
                        <br><em style="color: var(--sr-text);">"<?= htmlspecialchars($pulseProfile['bio']) ?>"</em>
                    <?php endif; ?>
                </div>
                <div class="sr-hero-actions">
                    <a href="/pulse?profile=<?= $viewClientId ?>" class="sr-btn sr-btn-pulse">
                        <i class="fas fa-stream"></i> View on Pulse
                    </a>
                    <?php if ($isOwnProfile): ?>
                        <a href="/pulse" class="sr-btn sr-btn-outline">
                            <i class="fas fa-pen"></i> Edit Profile
                        </a>
                    <?php elseif ($isLoggedIn): ?>
                        <button class="sr-btn sr-btn-outline" onclick="followUser(<?= $viewClientId ?>)">
                            <i class="fas fa-user-plus"></i> Follow
                        </button>
                    <?php endif; ?>
                    <a href="/docs/field-manual" class="sr-btn sr-btn-outline">
                        <i class="fas fa-book"></i> Field Manual
                    </a>
                </div>
            </div>
        </div>

        <!-- STATS -->
        <div class="sr-stats">
            <div class="sr-stat">
                <div class="sr-stat-val" style="color: <?= getRankColor($member['rank_code']) ?>;"><?= htmlspecialchars($member['rank_code']) ?></div>
                <div class="sr-stat-label">Rank</div>
            </div>
            <div class="sr-stat">
                <div class="sr-stat-val" style="color: var(--sr-gold);"><?= number_format($daysInService) ?></div>
                <div class="sr-stat-label">Days Active</div>
            </div>
            <div class="sr-stat">
                <div class="sr-stat-val" style="color: var(--sr-cyan);"><?= number_format($xpSummary['total_xp'] ?? 0) ?></div>
                <div class="sr-stat-label">Total XP</div>
            </div>
            <div class="sr-stat">
                <div class="sr-stat-val" style="color: var(--sr-green);"><?= count($achievements) ?></div>
                <div class="sr-stat-label">Achievements</div>
            </div>
            <div class="sr-stat">
                <div class="sr-stat-val" style="color: var(--sr-blue);"><?= number_format($socialStats['posts']) ?></div>
                <div class="sr-stat-label">Posts</div>
            </div>
            <div class="sr-stat">
                <div class="sr-stat-val" style="color: var(--sr-purple);"><?= number_format($socialStats['followers']) ?></div>
                <div class="sr-stat-label">Followers</div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════ -->
    <!--          TWO COLUMN LAYOUT             -->
    <!-- ═══════════════════════════════════════ -->
    <div class="sr-layout">
        <!-- ═══ MAIN COLUMN ═══ -->
        <div>
            <!-- RANK PROGRESSION -->
            <?php if ($nextRank && $member['rank_code'] !== 'O-6'): ?>
            <div class="sr-card">
                <div class="sr-card-header"><i class="fas fa-chart-line"></i> Rank Progression</div>
                <div class="sr-card-body">
                    <div class="sr-rank-progress">
                        <?php
                        $currentXp = (int)($xpSummary['total_xp'] ?? 0);
                        $currentMin = (int)$member['min_xp'];
                        $nextMin = (int)$nextRank['min_xp'];
                        $range = max($nextMin - $currentMin, 1);
                        $progress = min(100, max(0, (($currentXp - $currentMin) / $range) * 100));
                        ?>
                        <div class="sr-rank-bar-labels">
                            <span><strong><?= htmlspecialchars($member['rank_name']) ?></strong> (<?= number_format($currentMin) ?> XP)</span>
                            <span><strong><?= htmlspecialchars($nextRank['rank_name']) ?></strong> (<?= number_format($nextMin) ?> XP)</span>
                        </div>
                        <div class="sr-rank-bar-bg">
                            <div class="sr-rank-bar-fill" style="width: <?= round($progress) ?>%; background: linear-gradient(90deg, <?= getRankColor($member['rank_code']) ?>, <?= getRankColor($nextRank['rank_code'] ?? 'E-1') ?>);"></div>
                        </div>
                        <div class="sr-rank-bar-labels">
                            <span>Current: <strong><?= number_format($currentXp) ?> XP</strong></span>
                            <span><?= number_format(max(0, $nextMin - $currentXp)) ?> XP remaining</span>
                        </div>
                        <?php if ($member['min_days_active'] > 0 || $nextRank['min_days_active'] > 0): ?>
                        <div style="margin-top: 0.8rem; font-size: 0.78rem; color: var(--sr-muted);">
                            <i class="fas fa-clock" style="color: var(--sr-gold); margin-right: 0.3rem;"></i>
                            Time requirement: <?= $daysInService ?> / <?= $nextRank['min_days_active'] ?> days
                            <?= $daysInService >= $nextRank['min_days_active'] ? '<span style="color: var(--sr-green); margin-left: 0.3rem;"><i class="fas fa-check"></i> Met</span>' : '' ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- PULSE FEED -->
            <div class="sr-card">
                <div class="sr-card-header">
                    <i class="fas fa-stream"></i> Recent Activity on Pulse
                    <a href="/pulse?profile=<?= $viewClientId ?>" style="margin-left: auto; font-size: 0.68rem; color: var(--sr-blue); text-decoration: none; text-transform: none; letter-spacing: 0;">View All →</a>
                </div>
                <?php if (empty($pulsePosts)): ?>
                    <div class="sr-empty">
                        <i class="fas fa-stream"></i>
                        No posts yet.
                        <?php if ($isOwnProfile): ?>
                            <br><a href="/pulse" style="color: var(--sr-gold);">Share your first post on Pulse →</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($pulsePosts as $post): ?>
                    <div class="sr-post">
                        <div class="sr-post-content"><?= nl2br(htmlspecialchars($post['content'])) ?></div>
                        <div class="sr-post-meta">
                            <span><i class="fas fa-heart"></i> <?= (int)$post['like_count'] ?></span>
                            <span><i class="fas fa-comment"></i> <?= (int)$post['comment_count'] ?></span>
                            <span><i class="fas fa-clock"></i> <?= timeAgo($post['created_at']) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- ACHIEVEMENTS -->
            <div class="sr-card">
                <div class="sr-card-header"><i class="fas fa-trophy"></i> Achievements</div>
                <div class="sr-card-body">
                    <?php if (empty($achievements)): ?>
                        <div class="sr-empty">
                            <i class="fas fa-trophy"></i>
                            No achievements unlocked yet. Earn them through service.
                        </div>
                    <?php else: ?>
                        <div class="sr-badges">
                            <?php foreach ($achievements as $ach): ?>
                                <div class="sr-badge" style="border-color: <?= getBadgeTierColor($ach['badge_tier'] ?? 'bronze') ?>44; background: <?= getBadgeTierColor($ach['badge_tier'] ?? 'bronze') ?>11; color: <?= getBadgeTierColor($ach['badge_tier'] ?? 'bronze') ?>;">
                                    <i class="fas fa-award"></i>
                                    <?= htmlspecialchars($ach['achievement_name']) ?>
                                    <span style="font-size: 0.6rem; opacity: 0.7;">+<?= (int)$ach['xp_awarded'] ?> XP</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ═══ SIDEBAR ═══ -->
        <div>
            <!-- SERVICE DETAILS -->
            <div class="sr-card">
                <div class="sr-card-header"><i class="fas fa-id-card"></i> Service Details</div>
                <div class="sr-card-body">
                    <div style="font-size: 0.82rem;">
                        <div style="display: flex; justify-content: space-between; padding: 0.4rem 0; border-bottom: 1px solid rgba(255,255,255,0.03);">
                            <span style="color: var(--sr-muted);">Rank</span>
                            <span style="font-weight: 700; color: <?= getRankColor($member['rank_code']) ?>;"><?= htmlspecialchars($member['rank_code'] . ' ' . $member['rank_name']) ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 0.4rem 0; border-bottom: 1px solid rgba(255,255,255,0.03);">
                            <span style="color: var(--sr-muted);">Status</span>
                            <span style="font-weight: 700; color: <?= $member['status'] === 'active' ? 'var(--sr-green)' : 'var(--sr-red)' ?>; text-transform: uppercase; font-size: 0.72rem; letter-spacing: 0.1em;"><?= htmlspecialchars($member['status']) ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 0.4rem 0; border-bottom: 1px solid rgba(255,255,255,0.03);">
                            <span style="color: var(--sr-muted);">Enlisted</span>
                            <span><?= date('M j, Y', strtotime($member['created_at'])) ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 0.4rem 0; border-bottom: 1px solid rgba(255,255,255,0.03);">
                            <span style="color: var(--sr-muted);">Days Active</span>
                            <span style="font-weight: 700;"><?= number_format($daysInService) ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 0.4rem 0; border-bottom: 1px solid rgba(255,255,255,0.03);">
                            <span style="color: var(--sr-muted);">Entry Point</span>
                            <span style="text-transform: capitalize;"><?= htmlspecialchars($member['entry_point'] ?? 'passport') ?></span>
                        </div>
                        <?php if (!empty($member['promotion_reason'])): ?>
                        <div style="padding: 0.6rem 0; font-size: 0.78rem; font-style: italic; color: var(--sr-gold);">
                            "<?= htmlspecialchars($member['promotion_reason']) ?>"
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- PERMISSIONS -->
            <div class="sr-card">
                <div class="sr-card-header"><i class="fas fa-key"></i> Permissions</div>
                <div class="sr-card-body">
                    <?php
                    $perms = json_decode($member['permissions'] ?? '{}', true) ?: [];
                    $permLabels = [
                        'chat' => ['Chat Access', 'fa-comments'],
                        'tools' => ['Tool Access', 'fa-wrench'],
                        'admin' => ['Admin Panel', 'fa-cog'],
                        'manage_team' => ['Team Management', 'fa-users'],
                        'deploy' => ['Deploy Services', 'fa-rocket'],
                        'view_logs' => ['View Logs', 'fa-file-alt'],
                        'budget' => ['Budget Authority', 'fa-coins'],
                        'create_dept' => ['Create Departments', 'fa-building'],
                        'modify_ranks' => ['Modify Ranks', 'fa-chevron-up'],
                        'vault_access' => ['Vault Access', 'fa-lock'],
                        'owner' => ['Owner Authority', 'fa-crown'],
                    ];
                    foreach ($permLabels as $key => [$label, $icon]):
                        $val = $perms[$key] ?? false;
                        $granted = ($val && $val !== false && $val !== 'false');
                    ?>
                    <div class="sr-perm">
                        <i class="fas <?= $icon ?> <?= $granted ? 'granted' : 'denied' ?>"></i>
                        <span class="<?= $granted ? '' : 'denied-text' ?>"><?= $label ?></span>
                        <?php if (is_string($val) && $val !== '1' && $granted): ?>
                            <span style="margin-left: auto; font-size: 0.65rem; color: var(--sr-cyan); text-transform: uppercase; letter-spacing: 0.05em;"><?= htmlspecialchars($val) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <div style="margin-top: 0.6rem; font-size: 0.7rem; color: var(--sr-dim);">
                        API limit: <strong style="color: var(--sr-cyan);">
                            <?php
                            $apiLimit = $perms['max_api_calls'] ?? 0;
                            echo $apiLimit == -1 ? 'Unlimited' : number_format($apiLimit);
                            ?>
                        </strong> calls
                    </div>
                </div>
            </div>

            <!-- STREAKS -->
            <div class="sr-card">
                <div class="sr-card-header"><i class="fas fa-fire"></i> Streaks</div>
                <div class="sr-card-body">
                    <?php if (empty($streaks)): ?>
                        <div class="sr-empty" style="padding: 1rem;">
                            <i class="fas fa-fire" style="font-size: 1.2rem;"></i>
                            No active streaks. Show up daily to build them.
                        </div>
                    <?php else: ?>
                        <?php foreach ($streaks as $streak): ?>
                        <div style="display: flex; align-items: center; gap: 0.6rem; padding: 0.5rem 0; border-bottom: 1px solid rgba(255,255,255,0.03);">
                            <i class="fas fa-fire" style="color: var(--sr-coral);"></i>
                            <div style="flex: 1;">
                                <div style="font-size: 0.8rem; font-weight: 600; text-transform: capitalize;"><?= htmlspecialchars(str_replace('_', ' ', $streak['streak_type'])) ?></div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 1rem; font-weight: 900; color: var(--sr-coral); font-family: 'JetBrains Mono', monospace;"><?= (int)$streak['current_count'] ?></div>
                                <div style="font-size: 0.6rem; color: var(--sr-dim);">CURRENT</div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- SERVICE TIMELINE -->
            <div class="sr-card">
                <div class="sr-card-header"><i class="fas fa-history"></i> Service Timeline</div>
                <div class="sr-card-body">
                    <div class="sr-timeline">
                        <?php if (!empty($promotions)): ?>
                            <?php foreach ($promotions as $promo): ?>
                            <div class="sr-timeline-item">
                                <div class="sr-timeline-date"><?= date('M j, Y', strtotime($promo['created_at'])) ?></div>
                                <div class="sr-timeline-text">
                                    Promoted from <strong><?= htmlspecialchars($promo['from_rank']) ?></strong> 
                                    to <strong style="color: var(--sr-gold);"><?= htmlspecialchars($promo['to_rank']) ?></strong>
                                    <?php if ($promo['reason']): ?>
                                        <br><span style="font-size: 0.75rem; color: var(--sr-muted);"><?= htmlspecialchars($promo['reason']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <div class="sr-timeline-item">
                            <div class="sr-timeline-date"><?= date('M j, Y', strtotime($member['created_at'])) ?></div>
                            <div class="sr-timeline-text">
                                Enlisted at rank <strong style="color: var(--sr-gold);"><?= htmlspecialchars($member['rank_code']) ?></strong>
                                via <em><?= htmlspecialchars($member['entry_point'] ?? 'passport') ?></em>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- LINKS -->
            <div class="sr-card">
                <div class="sr-card-header"><i class="fas fa-link"></i> Quick Links</div>
                <div class="sr-card-body" style="display: flex; flex-direction: column; gap: 0.3rem;">
                    <a href="/pulse?profile=<?= $viewClientId ?>" style="color: var(--sr-blue); font-size: 0.82rem; text-decoration: none;"><i class="fas fa-stream" style="width: 16px;"></i> Pulse Profile</a>
                    <a href="/docs/field-manual" style="color: var(--sr-gold); font-size: 0.82rem; text-decoration: none;"><i class="fas fa-book" style="width: 16px;"></i> Field Manual</a>
                    <a href="/docs/field-manual#ranks" style="color: var(--sr-green); font-size: 0.82rem; text-decoration: none;"><i class="fas fa-chevron-up" style="width: 16px;"></i> Rank Structure</a>
                    <a href="/docs/field-manual#code" style="color: var(--sr-purple); font-size: 0.82rem; text-decoration: none;"><i class="fas fa-gavel" style="width: 16px;"></i> Code of Conduct</a>
                    <a href="https://meta-dome.com/passport" style="color: var(--sr-coral); font-size: 0.82rem; text-decoration: none;"><i class="fas fa-passport" style="width: 16px;"></i> Passport Office</a>
                    <a href="/civilization-chronicle" style="color: var(--sr-cyan); font-size: 0.82rem; text-decoration: none;"><i class="fas fa-scroll" style="width: 16px;"></i> Civilization Chronicle</a>
                    <?php if ($viewClientId === 33): ?>
                    <a href="/commander-agents" style="color: var(--sr-gold); font-size: 0.82rem; text-decoration: none;"><i class="fas fa-chess-king" style="width: 16px;"></i> Agent War Room</a>
                    <a href="/docs/field-manual.php#agents" style="color: var(--sr-purple); font-size: 0.82rem; text-decoration: none;"><i class="fas fa-robot" style="width: 16px;"></i> FM Section XIII — Agent Division</a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($viewClientId === 33): ?>
            <!-- COMMANDER — AGENT DIVISION -->
            <div class="sr-card" style="grid-column: 1 / -1;">
                <div class="sr-card-header"><i class="fas fa-chess-king"></i> Agent Division — The Ten</div>
                <div class="sr-card-body">
                    <div style="font-size: 0.78rem; color: var(--sr-muted); margin-bottom: 0.75rem;">
                        Commanding Officer of 10 sovereign coding agents. Each governs a domain of the kingdom.
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 0.5rem;">
                        <?php
                        $agentDefs = [
                            ['Fortress', 'fa-shield-halved', '#ef4444', 'Security'],
                            ['Architect', 'fa-server', '#3b82f6', 'Infrastructure'],
                            ['Veil', 'fa-lock', '#8b5cf6', 'Comms'],
                            ['Pulse', 'fa-bolt', '#06b6d4', 'Social'],
                            ['MetaDome', 'fa-vr-cardboard', '#f97316', 'VR Worlds'],
                            ['Garrison', 'fa-chess-rook', '#d4a017', 'Military'],
                            ['Forge', 'fa-code', '#10b981', 'IDE'],
                            ['Herald', 'fa-microphone', '#ec4899', 'Voice AI'],
                            ['QM', 'fa-warehouse', '#f59e0b', 'Hosting'],
                            ['Sentinel', 'fa-robot', '#14b8a6', 'AI Fleet'],
                        ];
                        foreach ($agentDefs as [$name, $icon, $color, $dept]):
                            $agentSlug = strtolower($name === 'QM' ? 'quartermaster' : $name);
                            $fileExists = file_exists('/home/root/.github/agents/' . $agentSlug . '.agent.md');
                        ?>
                        <div style="background: <?= $color ?>0a; border: 1px solid <?= $color ?>33; border-radius: 8px; padding: 0.5rem; text-align: center;">
                            <i class="fas <?= $icon ?>" style="font-size: 0.9rem; color: <?= $color ?>;"></i>
                            <div style="font-size: 0.7rem; font-weight: 700; color: <?= $color ?>; margin-top: 0.2rem;"><?= $name ?></div>
                            <div style="font-size: 0.55rem; color: var(--sr-dim); text-transform: uppercase; letter-spacing: 0.06em;"><?= $dept ?></div>
                            <div style="width: 6px; height: 6px; border-radius: 50%; background: <?= $fileExists ? '#10b981' : '#ef4444' ?>; margin: 0.25rem auto 0;<?= $fileExists ? ' box-shadow: 0 0 4px #10b981;' : '' ?>"></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($isOwnProfile): ?>
                    <div style="margin-top: 0.75rem; text-align: center;">
                        <a href="/commander-agents" style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.4rem 1rem; background: rgba(212,160,23,.1); border: 1px solid rgba(212,160,23,.3); color: var(--sr-gold); border-radius: 6px; text-decoration: none; font-size: 0.75rem; font-weight: 600;">
                            <i class="fas fa-chess-king"></i> ENTER WAR ROOM
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>
<?php endif; ?>

<script>
async function followUser(userId) {
    try {
        const res = await fetch('/api/pulse.php?action=follow', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ user_id: userId })
        });
        const data = await res.json();
        if (data.success) {
            const btn = event.target.closest('button');
            btn.innerHTML = '<i class="fas fa-check"></i> Following';
            btn.disabled = true;
            btn.style.borderColor = 'var(--sr-green)';
            btn.style.color = 'var(--sr-green)';
        }
    } catch (e) {
        console.error('Follow failed:', e);
    }
}
</script>

</body>
</html>