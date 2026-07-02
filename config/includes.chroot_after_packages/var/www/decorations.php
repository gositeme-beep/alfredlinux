<?php
/**
 * Decorations & Awards — Military decoration catalog and medal rack
 * GoSiteMe Military Rank System — Level 4 Item 2
 */
session_start();

require_once __DIR__ . '/includes/db-config.inc.php';
$db = getSharedDB();

require_once __DIR__ . '/includes/auth-gate.inc.php';
require_once __DIR__ . '/includes/rank-guard.inc.php';

// ── CSRF Token ──────────────────────────────────────────────
if (empty($_SESSION['dec_csrf'])) {
    $_SESSION['dec_csrf'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['dec_csrf'];

// ── Rarity Color Map ────────────────────────────────────────
$rarityColors = [
    'common'    => '#808080',
    'uncommon'  => '#228B22',
    'rare'      => '#4169E1',
    'epic'      => '#9B30FF',
    'legendary' => '#FFD700',
];

// ── POST Handler: Award Decoration ─────────────────────────
$flash = '';
$flashType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'award_decoration') {
    if (!hash_equals($csrfToken, $_POST['csrf_token'] ?? '')) {
        $flash = 'Security token mismatch. Please try again.';
        $flashType = 'error';
    } elseif (!hasRank(9)) {
        $flash = 'Only Flag Officers (General+) may award decorations.';
        $flashType = 'error';
    } else {
        $awardCode = trim($_POST['decoration_code'] ?? '');
        $recipientId = (int)($_POST['recipient_id'] ?? 0);
        $citation = trim($_POST['citation'] ?? '');

        // Validate decoration exists
        $decCheck = $db->prepare("SELECT decoration_code, decoration_name, max_awards FROM military_decorations WHERE decoration_code = ?");
        $decCheck->execute([$awardCode]);
        $decoration = $decCheck->fetch(PDO::FETCH_ASSOC);

        if (!$decoration) {
            $flash = 'Invalid decoration code.';
            $flashType = 'error';
        } elseif ($recipientId < 1) {
            $flash = 'Invalid recipient ID.';
            $flashType = 'error';
        } else {
            // Verify recipient exists
            $recipCheck = $db->prepare("SELECT id, firstname, lastname FROM tblclients WHERE id = ?");
            $recipCheck->execute([$recipientId]);
            $recipient = $recipCheck->fetch(PDO::FETCH_ASSOC);

            if (!$recipient) {
                $flash = 'Recipient client ID not found.';
                $flashType = 'error';
            } else {
                // Check max_awards limit
                $countCheck = $db->prepare("SELECT COUNT(*) FROM user_decorations WHERE client_id = ? AND decoration_code = ?");
                $countCheck->execute([$recipientId, $awardCode]);
                $currentCount = (int)$countCheck->fetchColumn();

                if ($decoration['max_awards'] > 0 && $currentCount >= $decoration['max_awards']) {
                    $flash = htmlspecialchars($recipient['firstname'] . ' ' . $recipient['lastname']) . ' already has the maximum awards for ' . htmlspecialchars($decoration['decoration_name']) . '.';
                    $flashType = 'error';
                } else {
                    // Insert the award
                    $ins = $db->prepare("INSERT INTO user_decorations (client_id, decoration_code, awarded_by, citation, awarded_at) VALUES (?, ?, ?, ?, NOW())");
                    $ins->execute([$recipientId, $awardCode, $clientId, $citation]);

                    // Award 25 XP to recipient
                    awardXP($recipientId, 'decoration_received', ['decoration' => $awardCode, 'awarded_by' => $clientId]);

                    $flash = 'Awarded <strong>' . htmlspecialchars($decoration['decoration_name']) . '</strong> to ' . htmlspecialchars($recipient['firstname'] . ' ' . $recipient['lastname']) . ' (ID ' . $recipientId . '). +25 XP granted.';
                    $flashType = 'success';

                    // Regenerate CSRF
                    $_SESSION['dec_csrf'] = bin2hex(random_bytes(32));
                    $csrfToken = $_SESSION['dec_csrf'];
                }
            }
        }
    }
}

// ── Load User's Earned Decorations ──────────────────────────
$myDecorations = [];
$stmtMy = $db->prepare("
    SELECT ud.decoration_code, ud.citation, ud.awarded_at, ud.awarded_by,
           md.decoration_name, md.decoration_type, md.rarity, md.description,
           md.criteria, md.icon, md.color
    FROM user_decorations ud
    JOIN military_decorations md ON md.decoration_code = ud.decoration_code
    WHERE ud.client_id = ?
    ORDER BY FIELD(md.rarity, 'legendary','epic','rare','uncommon','common'), ud.awarded_at DESC
");
$stmtMy->execute([$clientId]);
$myDecorations = $stmtMy->fetchAll(PDO::FETCH_ASSOC);

$earnedCodes = array_unique(array_column($myDecorations, 'decoration_code'));

// ── Load All Decorations (catalog) ──────────────────────────
$allDecorations = $db->query("
    SELECT decoration_code, decoration_name, decoration_type, rarity,
           description, criteria, icon, color, max_awards, auto_award
    FROM military_decorations
    ORDER BY FIELD(decoration_type, 'medal','ribbon','commendation','badge','citation'),
             FIELD(rarity, 'legendary','epic','rare','uncommon','common'),
             decoration_name
")->fetchAll(PDO::FETCH_ASSOC);

// Group by type
$groupedDecorations = [];
foreach ($allDecorations as $dec) {
    $groupedDecorations[$dec['decoration_type']][] = $dec;
}

$typeLabels = [
    'medal'         => ['Medals', 'fa-medal'],
    'ribbon'        => ['Ribbons', 'fa-ribbon'],
    'commendation'  => ['Commendations', 'fa-scroll'],
    'badge'         => ['Badges', 'fa-certificate'],
    'citation'      => ['Citations', 'fa-file-signature'],
];

$canAward = hasRank(9);

$pageTitle = 'Decorations & Awards';
$pageDescription = 'Military decorations, medals, ribbons, and commendations earned through service in the GoSiteMe kingdom.';
require_once __DIR__ . '/includes/site-header.inc.php';
?>

<style>
/* ── Decorations Page Theme ─────────────────────────── */
.dec-page { background: #0a0a1a; min-height: 100vh; padding: 2rem 0 4rem; }
.dec-container { max-width: 1200px; margin: 0 auto; padding: 0 1rem; }

/* Header */
.dec-hero { text-align: center; margin-bottom: 2.5rem; }
.dec-hero h1 { font-size: 2.2rem; color: #e2b340; margin: 0 0 .5rem; }
.dec-hero h1 i { margin-right: .5rem; }
.dec-hero .dec-subtitle { color: #8888aa; font-size: 1.05rem; }
.dec-hero .rank-badge-inline { margin-left: .5rem; }

/* Flash messages */
.dec-flash { padding: 1rem 1.25rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: .95rem; }
.dec-flash-success { background: rgba(34,139,34,.15); border: 1px solid #228B22; color: #7ddf7d; }
.dec-flash-error { background: rgba(220,53,69,.15); border: 1px solid #dc3545; color: #ff8a8a; }

/* Medal Rack */
.medal-rack { margin-bottom: 3rem; }
.medal-rack h2 { color: #e2b340; font-size: 1.5rem; margin-bottom: 1rem; border-bottom: 1px solid #2a2a4e; padding-bottom: .5rem; }
.medal-rack h2 i { margin-right: .5rem; }
.rack-empty { color: #6666aa; text-align: center; padding: 2rem; font-style: italic; }
.rack-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 1rem;
}
.rack-card {
    background: #1a1a2e;
    border: 1px solid #2a2a4e;
    border-radius: 12px;
    padding: 1.25rem;
    text-align: center;
    position: relative;
    overflow: hidden;
    transition: transform .2s, box-shadow .2s;
}
.rack-card:hover { transform: translateY(-3px); }
.rack-card.rarity-legendary { box-shadow: 0 0 20px rgba(255,215,0,.35); border-color: #FFD700; }
.rack-card.rarity-epic { box-shadow: 0 0 16px rgba(155,48,255,.3); border-color: #9B30FF; }
.rack-card.rarity-rare { box-shadow: 0 0 12px rgba(65,105,225,.25); border-color: #4169E1; }
.rack-card.rarity-uncommon { box-shadow: 0 0 10px rgba(34,139,34,.2); border-color: #228B22; }
.rack-card.rarity-common { border-color: #555; }

.rack-icon { font-size: 2.5rem; margin-bottom: .5rem; }
.rack-name { font-size: 1.1rem; font-weight: 700; color: #e8e8f0; margin-bottom: .25rem; }
.rack-citation { font-size: .82rem; color: #9999bb; font-style: italic; margin-top: .5rem; max-height: 3em; overflow: hidden; }
.rack-date { font-size: .75rem; color: #6666aa; margin-top: .4rem; }

/* Rarity Badge */
.rarity-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: .7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .5px;
}
.type-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: .7rem;
    font-weight: 600;
    text-transform: uppercase;
    background: rgba(226,179,64,.12);
    color: #e2b340;
    margin-right: 4px;
}

/* Catalog */
.dec-catalog h2 { color: #e2b340; font-size: 1.5rem; margin-bottom: .5rem; }
.dec-catalog h2 i { margin-right: .5rem; }

.cat-section { margin-bottom: 2.5rem; }
.cat-section-title {
    color: #c0c0e0;
    font-size: 1.15rem;
    margin-bottom: 1rem;
    padding-bottom: .4rem;
    border-bottom: 1px solid #2a2a4e;
}
.cat-section-title i { margin-right: .4rem; color: #e2b340; }

.cat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1rem;
}

.cat-card {
    background: #1a1a2e;
    border: 1px solid #2a2a4e;
    border-radius: 12px;
    padding: 1.25rem;
    display: flex;
    gap: 1rem;
    position: relative;
    transition: transform .2s, box-shadow .2s;
}
.cat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,.4); }
.cat-card.earned { border-color: #e2b340; }
.cat-card.locked { opacity: .65; }

.cat-icon-wrap {
    flex-shrink: 0;
    width: 56px;
    height: 56px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.6rem;
    background: rgba(255,255,255,.05);
}
.cat-body { flex: 1; min-width: 0; }
.cat-name { font-size: 1rem; font-weight: 700; color: #e8e8f0; margin-bottom: .3rem; }
.cat-badges { margin-bottom: .4rem; }
.cat-desc { font-size: .85rem; color: #9999bb; margin-bottom: .3rem; line-height: 1.4; }
.cat-criteria { font-size: .8rem; color: #7777aa; }
.cat-criteria strong { color: #9999cc; }
.cat-status {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: .78rem;
    font-weight: 600;
    margin-top: .4rem;
}
.cat-status.earned-status { color: #4ade80; }
.cat-status.locked-status { color: #666688; }

/* Award Button */
.btn-award {
    display: inline-block;
    margin-top: .5rem;
    padding: 5px 12px;
    font-size: .78rem;
    font-weight: 600;
    background: rgba(226,179,64,.15);
    color: #e2b340;
    border: 1px solid #e2b340;
    border-radius: 6px;
    cursor: pointer;
    transition: background .2s;
}
.btn-award:hover { background: rgba(226,179,64,.3); }

/* Award Modal */
.award-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.7);
    z-index: 9000;
    align-items: center;
    justify-content: center;
}
.award-overlay.active { display: flex; }
.award-modal {
    background: #1a1a2e;
    border: 1px solid #e2b340;
    border-radius: 14px;
    padding: 2rem;
    width: 90%;
    max-width: 460px;
    position: relative;
}
.award-modal h3 { color: #e2b340; margin: 0 0 1rem; font-size: 1.2rem; }
.award-modal label { display: block; color: #c0c0e0; font-size: .9rem; margin-bottom: .3rem; }
.award-modal input, .award-modal textarea {
    width: 100%;
    padding: .6rem .75rem;
    border-radius: 8px;
    border: 1px solid #2a2a4e;
    background: #0f0f24;
    color: #e0e0f0;
    font-size: .9rem;
    margin-bottom: 1rem;
    box-sizing: border-box;
}
.award-modal textarea { resize: vertical; min-height: 80px; }
.award-modal .btn-submit {
    padding: .6rem 1.5rem;
    background: #e2b340;
    color: #0a0a1a;
    border: none;
    border-radius: 8px;
    font-weight: 700;
    cursor: pointer;
    font-size: .95rem;
    transition: background .2s;
}
.award-modal .btn-submit:hover { background: #f0c860; }
.award-modal .btn-cancel {
    padding: .6rem 1.5rem;
    background: transparent;
    color: #999;
    border: 1px solid #444;
    border-radius: 8px;
    cursor: pointer;
    font-size: .95rem;
    margin-left: .5rem;
}
.award-close {
    position: absolute;
    top: .75rem;
    right: 1rem;
    background: none;
    border: none;
    color: #888;
    font-size: 1.5rem;
    cursor: pointer;
}

/* Responsive */
@media (max-width: 768px) {
    .dec-hero h1 { font-size: 1.6rem; }
    .rack-grid { grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); }
    .cat-grid { grid-template-columns: 1fr; }
    .cat-card { flex-direction: column; text-align: center; }
    .cat-icon-wrap { margin: 0 auto; }
    .award-modal { padding: 1.25rem; }
}
@media (max-width: 480px) {
    .rack-grid { grid-template-columns: 1fr 1fr; gap: .6rem; }
    .rack-card { padding: .9rem; }
    .rack-icon { font-size: 1.8rem; }
}
</style>

<div class="dec-page">
<div class="dec-container">

    <!-- Hero -->
    <div class="dec-hero">
        <h1><i class="fa-solid fa-award"></i> Decorations &amp; Awards</h1>
        <div class="dec-subtitle">
            Service member: <strong><?= htmlspecialchars($clientName) ?></strong>
            <?= getUserRankBadge() ?>
        </div>
    </div>

    <!-- Flash -->
    <?php if ($flash): ?>
        <div class="dec-flash dec-flash-<?= $flashType ?>"><?= $flash ?></div>
    <?php endif; ?>

    <!-- ═══════ MEDAL RACK ═══════ -->
    <div class="medal-rack">
        <h2><i class="fa-solid fa-vest-patches"></i> Your Medal Rack</h2>
        <?php if (empty($myDecorations)): ?>
            <div class="rack-empty">
                <i class="fa-solid fa-ghost" style="font-size:2rem; display:block; margin-bottom:.5rem;"></i>
                No decorations earned yet. Serve with distinction to earn your first medal.
            </div>
        <?php else: ?>
            <div class="rack-grid">
                <?php foreach ($myDecorations as $dec): ?>
                    <?php $rc = $rarityColors[$dec['rarity']] ?? '#808080'; ?>
                    <div class="rack-card rarity-<?= $dec['rarity'] ?>">
                        <div class="rack-icon" style="color: <?= $dec['color'] ?>;">
                            <i class="fa-solid <?= htmlspecialchars($dec['icon']) ?>"></i>
                        </div>
                        <div class="rack-name"><?= htmlspecialchars($dec['decoration_name']) ?></div>
                        <span class="rarity-badge" style="background: <?= $rc ?>22; color: <?= $rc ?>; border: 1px solid <?= $rc ?>55;">
                            <?= ucfirst($dec['rarity']) ?>
                        </span>
                        <?php if (!empty($dec['citation'])): ?>
                            <div class="rack-citation">&ldquo;<?= htmlspecialchars($dec['citation']) ?>&rdquo;</div>
                        <?php endif; ?>
                        <div class="rack-date">
                            <i class="fa-regular fa-calendar"></i>
                            <?= date('M j, Y', strtotime($dec['awarded_at'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- ═══════ FULL CATALOG ═══════ -->
    <div class="dec-catalog">
        <h2><i class="fa-solid fa-book-open"></i> All Decorations</h2>
        <p style="color:#7777aa; margin-bottom:1.5rem; font-size:.9rem;">
            <?= count($allDecorations) ?> decorations across <?= count($groupedDecorations) ?> categories.
            Earned decorations are highlighted with a gold border.
        </p>

        <?php foreach ($typeLabels as $type => [$label, $typeIcon]): ?>
            <?php if (empty($groupedDecorations[$type])) continue; ?>
            <div class="cat-section">
                <h3 class="cat-section-title">
                    <i class="fa-solid <?= $typeIcon ?>"></i> <?= $label ?>
                    <span style="font-weight:400; font-size:.85rem; color:#666688;">(<?= count($groupedDecorations[$type]) ?>)</span>
                </h3>
                <div class="cat-grid">
                    <?php foreach ($groupedDecorations[$type] as $dec): ?>
                        <?php
                            $isEarned = in_array($dec['decoration_code'], $earnedCodes, true);
                            $rc = $rarityColors[$dec['rarity']] ?? '#808080';
                        ?>
                        <div class="cat-card <?= $isEarned ? 'earned' : 'locked' ?>">
                            <div class="cat-icon-wrap" style="color: <?= htmlspecialchars($dec['color']) ?>; border: 2px solid <?= htmlspecialchars($dec['color']) ?>44;">
                                <i class="fa-solid <?= htmlspecialchars($dec['icon']) ?>"></i>
                            </div>
                            <div class="cat-body">
                                <div class="cat-name"><?= htmlspecialchars($dec['decoration_name']) ?></div>
                                <div class="cat-badges">
                                    <span class="type-badge"><?= ucfirst($dec['decoration_type']) ?></span>
                                    <span class="rarity-badge" style="background: <?= $rc ?>22; color: <?= $rc ?>; border: 1px solid <?= $rc ?>55;">
                                        <?= ucfirst($dec['rarity']) ?>
                                    </span>
                                </div>
                                <div class="cat-desc"><?= htmlspecialchars($dec['description']) ?></div>
                                <div class="cat-criteria"><strong>Criteria:</strong> <?= htmlspecialchars($dec['criteria']) ?></div>
                                <?php if ($isEarned): ?>
                                    <div class="cat-status earned-status"><i class="fa-solid fa-circle-check"></i> Earned</div>
                                <?php else: ?>
                                    <div class="cat-status locked-status"><i class="fa-solid fa-lock"></i> Locked</div>
                                <?php endif; ?>
                                <?php if ($dec['auto_award']): ?>
                                    <span style="font-size:.7rem; color:#5588aa; margin-left:6px;" title="Automatically awarded when criteria are met"><i class="fa-solid fa-robot"></i> Auto</span>
                                <?php endif; ?>
                                <?php if ($canAward): ?>
                                    <br>
                                    <button type="button" class="btn-award" onclick="openAwardModal('<?= htmlspecialchars($dec['decoration_code'], ENT_QUOTES) ?>', '<?= htmlspecialchars($dec['decoration_name'], ENT_QUOTES) ?>')">
                                        <i class="fa-solid fa-hand-holding-heart"></i> Award
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</div><!-- /.dec-container -->
</div><!-- /.dec-page -->

<?php if ($canAward): ?>
<!-- ═══════ AWARD MODAL ═══════ -->
<div class="award-overlay" id="awardOverlay">
    <div class="award-modal">
        <button class="award-close" onclick="closeAwardModal()" aria-label="Close">&times;</button>
        <h3><i class="fa-solid fa-hand-holding-heart"></i> Award Decoration</h3>
        <p style="color:#9999bb; font-size:.9rem; margin-bottom:1rem;" id="awardDecName"></p>
        <form method="POST" action="decorations.php">
            <input type="hidden" name="action" value="award_decoration">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="decoration_code" id="awardDecCode" value="">

            <label for="recipient_id">Recipient Client ID</label>
            <input type="number" name="recipient_id" id="recipient_id" min="1" required placeholder="e.g. 33">

            <label for="citation">Citation <span style="color:#666;">(optional)</span></label>
            <textarea name="citation" id="citation" placeholder="For exceptional service in..."></textarea>

            <button type="submit" class="btn-submit"><i class="fa-solid fa-award"></i> Award Decoration</button>
            <button type="button" class="btn-cancel" onclick="closeAwardModal()">Cancel</button>
        </form>
    </div>
</div>

<script>
function openAwardModal(code, name) {
    document.getElementById('awardDecCode').value = code;
    document.getElementById('awardDecName').textContent = 'Awarding: ' + name;
    document.getElementById('awardOverlay').classList.add('active');
}
function closeAwardModal() {
    document.getElementById('awardOverlay').classList.remove('active');
}
document.getElementById('awardOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeAwardModal();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeAwardModal();
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/site-footer.inc.php'; ?>
