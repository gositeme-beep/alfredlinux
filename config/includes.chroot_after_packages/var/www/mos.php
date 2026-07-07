<?php
/**
 * MOS Specializations — Military Occupational Specialty Selection
 * GoSiteMe Military Rank System — Level 4 Item 1
 */
session_start();

require_once __DIR__ . '/includes/db-config.inc.php';
$db = getSharedDB();

require_once __DIR__ . '/includes/auth-gate.inc.php';
require_once __DIR__ . '/includes/rank-guard.inc.php';

// Must be at least Corporal (Tier 2)
requireRank(2, 'Corporal (E-2)');

// ── CSRF Token ──────────────────────────────────────────────
if (empty($_SESSION['mos_csrf'])) {
    $_SESSION['mos_csrf'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['mos_csrf'];

// ── POST Handler ────────────────────────────────────────────
$flash = '';
$flashType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Validate CSRF
    if (!hash_equals($csrfToken, $_POST['csrf_token'] ?? '')) {
        $flash = 'Security token mismatch. Please try again.';
        $flashType = 'error';
    } else {
        $postAction = $_POST['action'];

        if ($postAction === 'enroll') {
            $mosCode = trim($_POST['mos_code'] ?? '');

            // Validate MOS exists and is active
            $mosCheck = $db->prepare("SELECT mos_code, mos_name, min_rank_tier FROM military_mos WHERE mos_code = ? AND is_active = 1");
            $mosCheck->execute([$mosCode]);
            $mos = $mosCheck->fetch(PDO::FETCH_ASSOC);

            if (!$mos) {
                $flash = 'Invalid MOS code.';
                $flashType = 'error';
            } elseif ($userRankTier < $mos['min_rank_tier']) {
                $flash = 'You need Tier ' . $mos['min_rank_tier'] . '+ to enroll in ' . htmlspecialchars($mos['mos_name']) . '.';
                $flashType = 'error';
            } else {
                // Check if already enrolled
                $enrolled = $db->prepare("SELECT id FROM user_mos WHERE client_id = ? AND mos_code = ?");
                $enrolled->execute([$clientId, $mosCode]);

                if ($enrolled->fetch()) {
                    $flash = 'You are already enrolled in ' . htmlspecialchars($mos['mos_name']) . '.';
                    $flashType = 'warning';
                } else {
                    // Check if user has any MOS at all (for primary flag)
                    $anyMos = $db->prepare("SELECT COUNT(*) FROM user_mos WHERE client_id = ?");
                    $anyMos->execute([$clientId]);
                    $mosCount = (int)$anyMos->fetchColumn();
                    $isPrimary = ($mosCount === 0) ? 1 : 0;

                    $insert = $db->prepare("
                        INSERT INTO user_mos (client_id, mos_code, skill_level, skill_xp, is_primary, assigned_at)
                        VALUES (?, ?, 1, 0, ?, NOW())
                    ");
                    $insert->execute([$clientId, $mosCode, $isPrimary]);

                    // Award 50 XP for first MOS selection
                    if ($mosCount === 0) {
                        awardXP($clientId, 'mission_complete', ['reason' => 'MOS selection']);
                    }

                    $label = $isPrimary ? 'primary' : 'secondary';
                    $flash = 'Enrolled in <strong>' . htmlspecialchars($mos['mos_name']) . '</strong> (' . htmlspecialchars($mosCode) . ') as your ' . $label . ' MOS!';
                    $flashType = 'success';
                }
            }
        } elseif ($postAction === 'set_primary') {
            $mosCode = trim($_POST['mos_code'] ?? '');

            // Verify user actually has this MOS
            $hasMos = $db->prepare("SELECT id FROM user_mos WHERE client_id = ? AND mos_code = ?");
            $hasMos->execute([$clientId, $mosCode]);

            if (!$hasMos->fetch()) {
                $flash = 'You are not enrolled in that MOS.';
                $flashType = 'error';
            } else {
                // Clear all primary flags
                $db->prepare("UPDATE user_mos SET is_primary = 0 WHERE client_id = ?")->execute([$clientId]);
                // Set new primary
                $db->prepare("UPDATE user_mos SET is_primary = 1 WHERE client_id = ? AND mos_code = ?")->execute([$clientId, $mosCode]);

                $flash = 'Primary MOS updated to <strong>' . htmlspecialchars($mosCode) . '</strong>.';
                $flashType = 'success';
            }
        }
    }

    // Regenerate CSRF after POST
    $_SESSION['mos_csrf'] = bin2hex(random_bytes(32));
    $csrfToken = $_SESSION['mos_csrf'];
}

// ── Fetch Data ──────────────────────────────────────────────
// All active MOS entries
$allMos = $db->query("SELECT * FROM military_mos WHERE is_active = 1 ORDER BY track, mos_code")->fetchAll(PDO::FETCH_ASSOC);

// User's current MOS assignments
$userMos = $db->prepare("
    SELECT um.*, m.mos_name, m.track, m.description, m.icon
    FROM user_mos um
    JOIN military_mos m ON m.mos_code = um.mos_code
    WHERE um.client_id = ?
    ORDER BY um.is_primary DESC, um.assigned_at ASC
");
$userMos->execute([$clientId]);
$userMosRows = $userMos->fetchAll(PDO::FETCH_ASSOC);

// Build a set of enrolled MOS codes for quick lookup
$enrolledCodes = [];
foreach ($userMosRows as $row) {
    $enrolledCodes[$row['mos_code']] = true;
}

// Skill tree counts per MOS
$skillCounts = $db->query("SELECT mos_code, COUNT(*) as cnt, MAX(xp_required) as max_xp FROM mos_skill_tree GROUP BY mos_code")->fetchAll(PDO::FETCH_ASSOC);
$skillMap = [];
foreach ($skillCounts as $sc) {
    $skillMap[$sc['mos_code']] = ['count' => (int)$sc['cnt'], 'max_xp' => (int)$sc['max_xp']];
}

// Group MOS by track
$tracks = [];
foreach ($allMos as $m) {
    $tracks[$m['track']][] = $m;
}

$trackMeta = [
    'combat'       => ['label' => 'Combat',       'color' => '#cc3333', 'icon' => 'fa-crosshairs',       'desc' => 'Front-line warriors and tactical specialists'],
    'intelligence' => ['label' => 'Intelligence',  'color' => '#3366cc', 'icon' => 'fa-magnifying-glass', 'desc' => 'Analysts, counterintel, and information warfare'],
    'engineering'  => ['label' => 'Engineering',   'color' => '#33cc66', 'icon' => 'fa-code',             'desc' => 'Systems, networks, cyber defense, and development'],
    'medical'      => ['label' => 'Medical',       'color' => '#cc6633', 'icon' => 'fa-kit-medical',      'desc' => 'Health, recovery, triage, and system diagnostics'],
    'signal'       => ['label' => 'Signal',        'color' => '#9933cc', 'icon' => 'fa-tower-cell',       'desc' => 'Communications, encryption, and broadcast'],
    'logistics'    => ['label' => 'Logistics',     'color' => '#33cccc', 'icon' => 'fa-boxes-stacked',    'desc' => 'Supply chain, transport, and resource management'],
];

$pageTitle = 'MOS Specializations — GoSiteMe Military';

require_once __DIR__ . '/includes/site-header.inc.php';
?>

<style>
/* ── MOS Page Styles ───────────────────────────────────── */
.mos-page { background: #0a0a1a; min-height: 100vh; padding: 2rem 1rem 4rem; }
.mos-container { max-width: 1200px; margin: 0 auto; }

/* Header */
.mos-header { text-align: center; margin-bottom: 2.5rem; }
.mos-header h1 { color: #e2b340; font-size: 2rem; font-weight: 700; margin: 0 0 0.5rem; letter-spacing: 1px; }
.mos-header .rank-line { color: #aaa; font-size: 0.95rem; margin-bottom: 0.5rem; }
.mos-header .subtitle { color: #777; font-size: 0.9rem; max-width: 600px; margin: 0 auto; }

/* Flash Messages */
.mos-flash { max-width: 700px; margin: 0 auto 1.5rem; padding: 0.9rem 1.2rem; border-radius: 6px; font-size: 0.95rem; border-left: 4px solid; }
.mos-flash.success { background: rgba(51, 204, 102, 0.12); border-color: #33cc66; color: #7fdb98; }
.mos-flash.error   { background: rgba(204, 51, 51, 0.12); border-color: #cc3333; color: #db7f7f; }
.mos-flash.warning { background: rgba(226, 179, 64, 0.12); border-color: #e2b340; color: #e2b340; }

/* My Specialties Section */
.my-mos-section { margin-bottom: 2.5rem; }
.my-mos-section h2 { color: #e2b340; font-size: 1.3rem; margin: 0 0 1rem; border-bottom: 1px solid #2a2a4e; padding-bottom: 0.5rem; }
.my-mos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1rem; }
.my-mos-card {
    background: #1a1a2e; border: 1px solid #2a2a4e; border-radius: 8px; padding: 1.2rem;
    display: flex; flex-direction: column; gap: 0.8rem; position: relative; transition: border-color 0.2s;
}
.my-mos-card:hover { border-color: #e2b340; }
.my-mos-card .primary-badge {
    position: absolute; top: 10px; right: 10px;
    background: #e2b340; color: #0a0a1a; font-size: 0.7rem; font-weight: 700;
    padding: 2px 8px; border-radius: 3px; text-transform: uppercase; letter-spacing: 0.5px;
}
.my-mos-card .mos-card-top { display: flex; align-items: center; gap: 0.8rem; }
.my-mos-card .mos-icon { font-size: 1.5rem; width: 40px; text-align: center; }
.my-mos-card .mos-info h3 { color: #fff; font-size: 1rem; margin: 0; }
.my-mos-card .mos-info .mos-code-label { color: #888; font-size: 0.8rem; }
.progress-bar-wrap { background: #0a0a1a; border-radius: 4px; height: 8px; overflow: hidden; }
.progress-bar-fill { height: 100%; border-radius: 4px; transition: width 0.4s ease; }
.progress-meta { display: flex; justify-content: space-between; color: #888; font-size: 0.78rem; }
.my-mos-actions { display: flex; gap: 0.5rem; margin-top: 0.3rem; }
.btn-sm {
    padding: 5px 12px; border-radius: 4px; font-size: 0.78rem; border: 1px solid #2a2a4e;
    background: transparent; color: #ccc; cursor: pointer; transition: all 0.2s; text-decoration: none;
}
.btn-sm:hover { border-color: #e2b340; color: #e2b340; }
.btn-sm.btn-gold { background: #e2b340; color: #0a0a1a; border-color: #e2b340; font-weight: 600; }
.btn-sm.btn-gold:hover { background: #c99a20; }

/* Track Filters */
.track-filters { display: flex; flex-wrap: wrap; gap: 0.5rem; justify-content: center; margin-bottom: 2rem; }
.track-btn {
    padding: 7px 16px; border-radius: 20px; font-size: 0.82rem; cursor: pointer;
    border: 1px solid #2a2a4e; background: #1a1a2e; color: #bbb; transition: all 0.2s;
    font-weight: 500;
}
.track-btn:hover { border-color: #e2b340; color: #e2b340; }
.track-btn.active { background: #e2b340; color: #0a0a1a; border-color: #e2b340; font-weight: 700; }

/* MOS Catalog Grid */
.mos-catalog h2 { color: #e2b340; font-size: 1.3rem; margin: 0 0 1rem; border-bottom: 1px solid #2a2a4e; padding-bottom: 0.5rem; }
.mos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem; }
.mos-card {
    background: #1a1a2e; border: 1px solid #2a2a4e; border-radius: 8px; padding: 1.2rem;
    display: flex; flex-direction: column; gap: 0.6rem; transition: border-color 0.2s, transform 0.15s;
    position: relative;
}
.mos-card:hover { border-color: #e2b340; transform: translateY(-2px); }
.mos-card .track-strip {
    position: absolute; top: 0; left: 0; right: 0; height: 3px; border-radius: 8px 8px 0 0;
}
.mos-card .card-header { display: flex; align-items: center; gap: 0.8rem; margin-top: 4px; }
.mos-card .card-icon { font-size: 1.6rem; width: 44px; text-align: center; }
.mos-card .card-title h3 { color: #fff; font-size: 0.95rem; margin: 0; }
.mos-card .card-title .code { color: #888; font-size: 0.78rem; }
.mos-card .card-track {
    display: inline-block; font-size: 0.7rem; padding: 2px 8px; border-radius: 3px;
    text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; align-self: flex-start;
}
.mos-card .card-desc { color: #999; font-size: 0.85rem; line-height: 1.5; flex: 1; }
.mos-card .card-skills { color: #666; font-size: 0.78rem; }
.mos-card .card-footer { margin-top: auto; }
.mos-card .enrolled-badge {
    display: inline-block; background: rgba(51, 204, 102, 0.15); color: #33cc66;
    font-size: 0.75rem; padding: 4px 10px; border-radius: 4px; font-weight: 600;
}
.btn-enroll {
    display: inline-block; padding: 7px 18px; border-radius: 4px; font-size: 0.82rem;
    background: #e2b340; color: #0a0a1a; border: none; cursor: pointer; font-weight: 700;
    transition: background 0.2s; text-transform: uppercase; letter-spacing: 0.5px;
}
.btn-enroll:hover { background: #c99a20; }
.btn-enroll:disabled { background: #555; color: #888; cursor: not-allowed; }

/* Track Section Headers */
.track-section { margin-bottom: 2rem; }
.track-section-header {
    display: flex; align-items: center; gap: 0.6rem; margin-bottom: 1rem;
    padding-bottom: 0.5rem; border-bottom: 1px solid #2a2a4e;
}
.track-section-header .track-dot { width: 10px; height: 10px; border-radius: 50%; }
.track-section-header h3 { color: #ddd; font-size: 1.1rem; margin: 0; }
.track-section-header .track-count { color: #666; font-size: 0.82rem; }

/* Responsive */
@media (max-width: 768px) {
    .mos-header h1 { font-size: 1.5rem; }
    .mos-grid, .my-mos-grid { grid-template-columns: 1fr; }
    .track-filters { gap: 0.4rem; }
    .track-btn { padding: 5px 12px; font-size: 0.75rem; }
}
@media (max-width: 480px) {
    .mos-page { padding: 1rem 0.5rem 3rem; }
    .my-mos-card, .mos-card { padding: 1rem; }
}
</style>

<main class="mos-page">
<div class="mos-container">

    <!-- ── Page Header ────────────────────────────────── -->
    <div class="mos-header">
        <h1><i class="fas fa-id-card-clip" style="margin-right:8px;"></i>MOS Specializations</h1>
        <div class="rank-line"><?= getUserRankBadge() ?> &nbsp; <?= htmlspecialchars($clientName) ?></div>
        <p class="subtitle">Choose your Military Occupational Specialty. Your MOS defines your expertise track, unlocks specialized skill trees, and determines your role in the kingdom.</p>
    </div>

    <?php if ($flash): ?>
        <div class="mos-flash <?= $flashType ?>"><?= $flash ?></div>
    <?php endif; ?>

    <!-- ── My Current Specialties ─────────────────────── -->
    <?php if (!empty($userMosRows)): ?>
    <section class="my-mos-section">
        <h2><i class="fas fa-star" style="color:#e2b340;margin-right:6px;"></i>My Specialties (<?= count($userMosRows) ?>)</h2>
        <div class="my-mos-grid">
            <?php foreach ($userMosRows as $um):
                $tColor = $trackMeta[$um['track']]['color'] ?? '#666';
                $maxXp  = $skillMap[$um['mos_code']]['max_xp'] ?? 500;
                $pct    = $maxXp > 0 ? min(100, round(($um['skill_xp'] / $maxXp) * 100)) : 0;
                $skills = $skillMap[$um['mos_code']]['count'] ?? 0;
            ?>
            <div class="my-mos-card" style="border-left: 3px solid <?= $tColor ?>;">
                <?php if ($um['is_primary']): ?>
                    <span class="primary-badge">Primary</span>
                <?php endif; ?>
                <div class="mos-card-top">
                    <div class="mos-icon" style="color:<?= $tColor ?>;"><i class="fas <?= htmlspecialchars($um['icon']) ?>"></i></div>
                    <div class="mos-info">
                        <h3><?= htmlspecialchars($um['mos_name']) ?></h3>
                        <span class="mos-code-label"><?= htmlspecialchars($um['mos_code']) ?> &middot; <?= ucfirst(htmlspecialchars($um['track'])) ?></span>
                    </div>
                </div>
                <div>
                    <div class="progress-bar-wrap">
                        <div class="progress-bar-fill" style="width:<?= $pct ?>%;background:<?= $tColor ?>;"></div>
                    </div>
                    <div class="progress-meta">
                        <span>Skill Level <?= (int)$um['skill_level'] ?></span>
                        <span><?= number_format($um['skill_xp']) ?> / <?= number_format($maxXp) ?> XP (<?= $pct ?>%)</span>
                    </div>
                </div>
                <?php if ($skills > 0): ?>
                    <div style="color:#666;font-size:0.78rem;"><i class="fas fa-sitemap" style="margin-right:4px;"></i><?= $skills ?> skills in tree</div>
                <?php endif; ?>
                <div class="my-mos-actions">
                    <?php if (!$um['is_primary']): ?>
                    <form method="POST" style="margin:0;">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="action" value="set_primary">
                        <input type="hidden" name="mos_code" value="<?= htmlspecialchars($um['mos_code']) ?>">
                        <button type="submit" class="btn-sm btn-gold">Set as Primary</button>
                    </form>
                    <?php else: ?>
                    <span class="btn-sm" style="cursor:default;color:#e2b340;border-color:#e2b340;opacity:0.7;">★ Primary MOS</span>
                    <?php endif; ?>
                    <span class="btn-sm" style="cursor:default;">Enrolled <?= date('M j, Y', strtotime($um['assigned_at'])) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- ── Track Filters ──────────────────────────────── -->
    <div class="track-filters">
        <button class="track-btn active" data-track="all" onclick="filterTrack('all', this)">
            <i class="fas fa-th" style="margin-right:4px;"></i>All Tracks
        </button>
        <?php foreach ($trackMeta as $tKey => $tInfo): ?>
        <button class="track-btn" data-track="<?= $tKey ?>" onclick="filterTrack('<?= $tKey ?>', this)"
                style="--track-color:<?= $tInfo['color'] ?>;">
            <i class="fas <?= $tInfo['icon'] ?>" style="margin-right:4px;"></i><?= $tInfo['label'] ?>
        </button>
        <?php endforeach; ?>
    </div>

    <!-- ── MOS Catalog ────────────────────────────────── -->
    <section class="mos-catalog">
        <h2><i class="fas fa-book-open" style="color:#e2b340;margin-right:6px;"></i>MOS Catalog</h2>

        <?php foreach ($trackMeta as $tKey => $tInfo):
            $trackItems = $tracks[$tKey] ?? [];
            if (empty($trackItems)) continue;
        ?>
        <div class="track-section" data-track-section="<?= $tKey ?>">
            <div class="track-section-header">
                <span class="track-dot" style="background:<?= $tInfo['color'] ?>;"></span>
                <h3><?= $tInfo['label'] ?></h3>
                <span class="track-count"><?= count($trackItems) ?> specialties &mdash; <?= $tInfo['desc'] ?></span>
            </div>
            <div class="mos-grid">
                <?php foreach ($trackItems as $m):
                    $isEnrolled = isset($enrolledCodes[$m['mos_code']]);
                    $skills = $skillMap[$m['mos_code']]['count'] ?? 0;
                    $userSkill = null;
                    if ($isEnrolled) {
                        foreach ($userMosRows as $um) {
                            if ($um['mos_code'] === $m['mos_code']) { $userSkill = $um; break; }
                        }
                    }
                ?>
                <div class="mos-card" data-track="<?= $tKey ?>">
                    <div class="track-strip" style="background:<?= $tInfo['color'] ?>;"></div>
                    <div class="card-header">
                        <div class="card-icon" style="color:<?= $tInfo['color'] ?>;"><i class="fas <?= htmlspecialchars($m['icon']) ?>"></i></div>
                        <div class="card-title">
                            <h3><?= htmlspecialchars($m['mos_name']) ?></h3>
                            <span class="code"><?= htmlspecialchars($m['mos_code']) ?></span>
                        </div>
                    </div>
                    <span class="card-track" style="background:<?= $tInfo['color'] ?>22;color:<?= $tInfo['color'] ?>;">
                        <?= $tInfo['label'] ?>
                    </span>
                    <p class="card-desc"><?= htmlspecialchars($m['description'] ?? 'Specialty training available.') ?></p>
                    <?php if ($skills > 0): ?>
                        <div class="card-skills"><i class="fas fa-sitemap" style="margin-right:4px;"></i><?= $skills ?> skills in tree</div>
                    <?php endif; ?>
                    <?php if ($m['min_rank_tier'] > $userRankTier): ?>
                        <div class="card-skills" style="color:#cc3333;"><i class="fas fa-lock" style="margin-right:4px;"></i>Requires Tier <?= $m['min_rank_tier'] ?>+</div>
                    <?php endif; ?>
                    <div class="card-footer">
                        <?php if ($isEnrolled): ?>
                            <span class="enrolled-badge"><i class="fas fa-check" style="margin-right:4px;"></i>Enrolled — Level <?= (int)($userSkill['skill_level'] ?? 1) ?></span>
                        <?php elseif ($m['min_rank_tier'] > $userRankTier): ?>
                            <button class="btn-enroll" disabled>Rank Locked</button>
                        <?php else: ?>
                            <form method="POST" style="margin:0;display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <input type="hidden" name="action" value="enroll">
                                <input type="hidden" name="mos_code" value="<?= htmlspecialchars($m['mos_code']) ?>">
                                <button type="submit" class="btn-enroll" onclick="return confirm('Enroll in <?= htmlspecialchars(addslashes($m['mos_name'])) ?> (<?= htmlspecialchars($m['mos_code']) ?>)?');">
                                    <i class="fas fa-plus" style="margin-right:4px;"></i>Enroll
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </section>

</div>
</main>

<script>
function filterTrack(track, btn) {
    // Update active button
    document.querySelectorAll('.track-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    // Show/hide track sections
    document.querySelectorAll('.track-section').forEach(section => {
        if (track === 'all') {
            section.style.display = '';
        } else {
            section.style.display = section.dataset.trackSection === track ? '' : 'none';
        }
    });
}
</script>

<?php require_once __DIR__ . '/includes/site-footer.inc.php'; ?>
