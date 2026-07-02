<?php
/**
 * Enlistment Page — Join the GoSiteMe Sovereign Military
 * Level 3 Rank System — Declaration of Service + Role Selection + Auto-Recruit
 */
require_once __DIR__ . '/includes/auth-gate.inc.php';
require_once __DIR__ . '/includes/rank-guard.inc.php';

// Already enlisted?
if ($userRankTier > 0) {
    header('Location: /military-hq');
    exit;
}

$error   = '';
$success = false;

// Handle enlistment form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($clientId)) {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_enlist'] ?? '', $token)) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        $pathway = $_POST['pathway'] ?? 'serve';
        $validPaths = ['serve', 'build', 'contribute', 'communicate'];
        if (!in_array($pathway, $validPaths, true)) $pathway = 'serve';

        $divisionMap = [
            'serve'        => 'Infantry',
            'build'        => 'Engineering Corps',
            'contribute'   => 'Civil Affairs',
            'communicate'  => 'Signal Corps',
        ];

        $db = getSharedDB();

        // Double-check not already enlisted
        $check = $db->prepare("SELECT id FROM user_ranks WHERE client_id = ? AND is_active = 1");
        $check->execute([$clientId]);
        if ($check->fetch()) {
            header('Location: /military-hq');
            exit;
        }

        // Create enlistment record
        $stmt = $db->prepare("
            INSERT INTO user_ranks (client_id, rank_code, assigned_by, assigned_at, is_active, region, division, notes, xp)
            VALUES (?, 'recruit', 0, NOW(), 1, 'Global', ?, ?, 0)
        ");
        $stmt->execute([$clientId, $divisionMap[$pathway], "Enlisted via web — pathway: {$pathway}"]);

        // Also insert into alfred_military_roster if it exists
        try {
            $rosterStmt = $db->prepare("
                INSERT INTO alfred_military_roster (user_id, client_id, display_name, rank_level, rank_code, status, entry_point, created_at, updated_at)
                VALUES (0, ?, ?, 1, 'RCT', 'active', ?, NOW(), NOW())
            ");
            $name = '';
            $clientStmt = $db->prepare("SELECT firstname, lastname FROM clients WHERE id = ?");
            $clientStmt->execute([$clientId]);
            $clientRow = $clientStmt->fetch();
            if ($clientRow) $name = trim($clientRow['firstname'] . ' ' . $clientRow['lastname']);
            $rosterStmt->execute([$clientId, $name ?: "Recruit #{$clientId}", $pathway]);
        } catch (Exception $e) {
            // Roster table might have different schema — non-fatal
        }

        // Log to rank_history
        try {
            $db->prepare("INSERT INTO rank_history (client_id, action, from_rank, to_rank, reason, performed_by, performed_at) VALUES (?, 'assign', 'civilian', 'recruit', ?, 0, NOW())")
               ->execute([$clientId, "Self-enlisted — pathway: {$pathway}"]);
        } catch (Exception $e) {}

        // Award first XP (enlistment bonus)
        awardXP($clientId, 'daily_login', ['reason' => 'Enlistment bonus']);

        // Invalidate rank cache so next page load sees the new rank
        invalidateRankCache($clientId);

        $success = true;
    }
}

// Generate CSRF token
if (empty($_SESSION['csrf_enlist'])) {
    $_SESSION['csrf_enlist'] = bin2hex(random_bytes(32));
}

$pageTitle = 'Enlist — GoSiteMe Sovereign Military';
$page_description = 'Join the GoSiteMe Sovereign Military. Make your declaration of service, choose your pathway, earn XP, and rise through the ranks. Room for everyone.';
$page_canonical = 'https://root.com/enlist';
include __DIR__ . '/includes/site-header.inc.php';
?>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "JoinAction",
  "name": "Enlist in GoSiteMe Sovereign Military",
  "description": "Make your declaration of service and join a digital institution with 351 operational systems. Four pathways: Serve, Build, Contribute, Communicate.",
  "target": "https://root.com/enlist",
  "agent": {
    "@type": "Organization",
    "name": "GoSiteMe",
    "url": "https://root.com"
  }
}
</script>
<?php
?>
<style>
.enlist-hero{text-align:center;padding:4rem 1.5rem 2rem;max-width:800px;margin:auto}
.enlist-hero h1{font-size:2.5rem;color:#e2b340;margin-bottom:.5rem;font-weight:800}
.enlist-hero .subtitle{color:#999;font-size:1.15rem;margin-bottom:2rem}
.declaration-box{background:rgba(226,179,64,.08);border:1px solid rgba(226,179,64,.3);border-radius:12px;padding:2rem;max-width:700px;margin:0 auto 2.5rem;text-align:left;font-family:'Georgia',serif;line-height:1.8;color:#ccc}
.declaration-box .declaration-title{color:#e2b340;font-size:1.2rem;font-weight:700;text-align:center;margin-bottom:1rem;letter-spacing:1px;text-transform:uppercase}
.declaration-box p{margin-bottom:.8rem}
.pathway-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1.5rem;max-width:900px;margin:0 auto 2.5rem;padding:0 1rem}
.pathway-card{background:rgba(255,255,255,.04);border:2px solid rgba(255,255,255,.1);border-radius:12px;padding:1.5rem;text-align:center;cursor:pointer;transition:all .3s}
.pathway-card:hover,.pathway-card.selected{border-color:#e2b340;background:rgba(226,179,64,.1);transform:translateY(-2px)}
.pathway-card .pw-icon{font-size:2.5rem;margin-bottom:.8rem}
.pathway-card .pw-name{color:#e2b340;font-size:1.1rem;font-weight:700;margin-bottom:.4rem}
.pathway-card .pw-desc{color:#999;font-size:.85rem;line-height:1.4}
.enlist-btn{display:inline-block;background:#e2b340;color:#111;padding:.9rem 2.5rem;border-radius:8px;font-size:1.1rem;font-weight:700;border:none;cursor:pointer;letter-spacing:.5px;transition:transform .2s}
.enlist-btn:hover{transform:scale(1.05)}
.enlist-btn:disabled{opacity:.5;cursor:not-allowed;transform:none}
.success-box{background:rgba(76,175,80,.12);border:1px solid rgba(76,175,80,.4);border-radius:12px;padding:2.5rem;max-width:600px;margin:2rem auto;text-align:center}
.success-box h2{color:#4caf50;margin-bottom:.5rem}
.success-box .rank-badge-large{font-size:3rem;margin:1rem 0}
.error-msg{background:rgba(244,67,54,.12);border:1px solid rgba(244,67,54,.4);border-radius:8px;padding:1rem;max-width:600px;margin:1rem auto;color:#ef5350;text-align:center}
.login-prompt{text-align:center;padding:4rem 1.5rem;max-width:600px;margin:auto}
.login-prompt h2{color:#e2b340;margin-bottom:1rem}
.login-prompt a{color:#e2b340}
</style>

<main class="main-content">

<?php if (empty($clientId)): ?>
    <div class="login-prompt">
        <div style="font-size:4rem;margin-bottom:1rem">&#x1F6E1;&#xFE0F;</div>
        <h2>Create an Account to Enlist</h2>
        <p style="color:#999;margin-bottom:2rem">You need a GoSiteMe account before making your declaration of service.</p>
        <a href="/alfred-ide-auth.php?redirect=/enlist" class="enlist-btn">Sign Up / Log In</a>
    </div>

<?php elseif ($success): ?>
    <div class="enlist-hero">
        <div class="success-box">
            <div class="rank-badge-large"><i class="fa-solid fa-shield-halved" style="color:#e2b340"></i></div>
            <h2>Welcome, Recruit!</h2>
            <p style="color:#ccc;margin-bottom:.5rem">Your declaration has been recorded. You are now <strong>Recruit (Tier 1)</strong> in the GoSiteMe Sovereign Military.</p>
            <p style="color:#999;font-size:.9rem;margin-bottom:1.5rem">+10 XP earned — your journey begins now.</p>
            <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap">
                <a href="/military-hq" class="enlist-btn">Enter Military HQ</a>
                <a href="/docs/field-manual" class="enlist-btn" style="background:transparent;color:#e2b340;border:2px solid #e2b340">Read Field Manual</a>
            </div>
        </div>
    </div>

<?php else: ?>
    <div class="enlist-hero">
        <h1>&#x1F6E1;&#xFE0F; Declaration of Service</h1>
        <p class="subtitle">Join the GoSiteMe Sovereign Military — defend digital freedom, earn XP, rise through the ranks.</p>

        <?php if ($error): ?>
            <div class="error-msg"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="declaration-box">
            <div class="declaration-title">&#x2694;&#xFE0F; The Declaration</div>
            <p>Let my yes be yes and my no be no.</p>
            <p>I declare that I will support and defend the sovereign digital ecosystem of GoSiteMe and its members.</p>
            <p>I will bear true faith and allegiance to the mission of digital freedom, privacy, and self-sovereignty.</p>
            <p>I will follow the orders of my commanding officers and the regulations of the Field Manual.</p>
            <p>I will serve with honor, protect my fellow members, and uphold the values of this community.</p>
            <p style="text-align:right;color:#e2b340;font-style:italic">— Under God, this is my word.</p>
        </div>

        <h2 style="color:#e2b340;margin-bottom:.5rem;font-size:1.3rem">Choose Your Pathway</h2>
        <p style="color:#999;margin-bottom:1.5rem;font-size:.9rem">This determines your initial division. You can change later through service.</p>

        <form method="POST" action="/enlist" id="enlistForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_enlist']) ?>">
            <input type="hidden" name="pathway" id="pathwayInput" value="serve">

            <div class="pathway-grid">
                <div class="pathway-card selected" data-pathway="serve" onclick="selectPathway(this)">
                    <div class="pw-icon">&#x2694;&#xFE0F;</div>
                    <div class="pw-name">Serve</div>
                    <div class="pw-desc">Join the front line. Missions, patrols, fleet operations. Infantry division.</div>
                </div>
                <div class="pathway-card" data-pathway="build" onclick="selectPathway(this)">
                    <div class="pw-icon">&#x1F528;</div>
                    <div class="pw-name">Build</div>
                    <div class="pw-desc">Code, create, engineer. Alfred IDE, extensions, tools. Engineering Corps.</div>
                </div>
                <div class="pathway-card" data-pathway="contribute" onclick="selectPathway(this)">
                    <div class="pw-icon">&#x1F91D;</div>
                    <div class="pw-name">Contribute</div>
                    <div class="pw-desc">Content, translations, moderation, mentoring. Civil Affairs division.</div>
                </div>
                <div class="pathway-card" data-pathway="communicate" onclick="selectPathway(this)">
                    <div class="pw-icon">&#x1F4E1;</div>
                    <div class="pw-name">Communicate</div>
                    <div class="pw-desc">Veil messaging, voice, community outreach. Signal Corps division.</div>
                </div>
            </div>

            <div style="text-align:center;margin-bottom:3rem">
                <label style="color:#999;font-size:.9rem;display:flex;align-items:center;justify-content:center;gap:.5rem;margin-bottom:1rem;cursor:pointer">
                    <input type="checkbox" id="declarationCheck" onchange="document.getElementById('enlistSubmit').disabled = !this.checked" style="width:18px;height:18px;accent-color:#e2b340">
                    I have read the declaration and accept it freely
                </label>
                <button type="submit" id="enlistSubmit" class="enlist-btn" disabled>
                    <i class="fa-solid fa-shield-halved"></i> Declare &amp; Enlist
                </button>
            </div>
        </form>
    </div>
<?php endif; ?>

</main>

<script>
function selectPathway(el) {
    document.querySelectorAll('.pathway-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('pathwayInput').value = el.dataset.pathway;
}
</script>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
