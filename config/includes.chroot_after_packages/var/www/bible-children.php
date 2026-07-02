<?php
/**
 * AKJV Bible — The Children's Bible
 * ══════════════════════════════════
 * Eden's Generation — Ages 5-14
 * 33 illustrated narratives from Genesis to Revelation.
 * URL: root.com/bible/children
 */
$page_title = "The Children's Bible — AKJV · Eden's Generation | GoSiteMe";
$page_description = "33 illustrated Bible stories from Genesis to Revelation, written for young hearts. Each story links to the real AKJV verse. Interactive, colorful, and full of wonder.";
$page_canonical = 'https://root.com/bible/children';
require_once __DIR__ . '/includes/site-header.inc.php';
require_once '/home/root/shared/bible/bible-data.php';
require_once '/home/root/shared/bible/bible-editions.php';

$lang = $_GET['lang'] ?? $_COOKIE['akjv_lang'] ?? 'en';
if (!in_array($lang, ['en','fr','he'])) $lang = 'en';
$dir = $lang === 'he' ? 'rtl' : 'ltr';
$ed = akjv_edition('children', $lang);

// Single story view?
$storyId = (int)($_GET['story'] ?? 0);
$db = akjv_db();
$stories = [];
$singleStory = null;

if ($storyId > 0) {
    $stmt = $db->prepare("SELECT * FROM akjv_children_stories WHERE id = ? LIMIT 1");
    $stmt->execute([$storyId]);
    $singleStory = $stmt->fetch(PDO::FETCH_ASSOC);
}

$all = $db->query("SELECT id, story_number, title, testament, age_group, verse_reference, summary FROM akjv_children_stories ORDER BY story_number ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<style>
<?= akjv_edition_styles() ?>
:root { --kid-bg: #0a0a0f; }
.children-page { max-width: 1100px; margin: 0 auto; padding: 0 1.5rem 4rem; color: #f0f0f5; }

.children-hero { text-align: center; padding: 50px 0 30px; position: relative; }
.children-hero::after { content:''; position:absolute; bottom:0; left:10%; right:10%; height:2px; background:linear-gradient(90deg,transparent,#22c55e,#ffd700,#3b82f6,transparent); }
.children-hero h1 { font-size: clamp(1.8rem, 5vw, 3rem); font-weight: 800; line-height: 1.15; margin-bottom: .5rem; }
.children-hero h1 .rainbow { background: linear-gradient(90deg, #ef4444, #f59e0b, #22c55e, #3b82f6, #8b5cf6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.children-hero .subtitle { color: rgba(240,240,245,.6); font-size: 1rem; max-width: 550px; margin: .5rem auto 1rem; }
.children-hero .eden { color: var(--akjv-gold); font-style: italic; font-size: .85rem; }

/* Story grid */
.story-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.2rem; margin: 2rem 0; }
.story-card { background: rgba(255,255,255,.03); border: 2px solid rgba(34,197,94,.15); border-radius: 18px; padding: 1.5rem; transition: all .4s; cursor: pointer; text-decoration: none; color: inherit; position: relative; overflow: hidden; }
.story-card:hover { transform: translateY(-4px) scale(1.01); box-shadow: 0 12px 40px rgba(34,197,94,.15); border-color: rgba(34,197,94,.5); }
.story-card .snum { position: absolute; top: 12px; right: 14px; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .85rem; font-weight: 800; }
.story-card .snum.ot { background: rgba(59,130,246,.15); color: #60a5fa; }
.story-card .snum.nt { background: rgba(139,92,246,.15); color: #a78bfa; }
.story-card .emoji { font-size: 2.2rem; margin-bottom: .6rem; display: block; }
.story-card h3 { font-size: 1.05rem; font-weight: 700; margin-bottom: .4rem; line-height: 1.3; color: #fff; }
.story-card .ref { font-size: .75rem; color: #22c55e; font-weight: 600; margin-bottom: .5rem; }
.story-card .summary { font-size: .82rem; color: rgba(255,255,255,.6); line-height: 1.6; }
.story-card .age-badge { position: absolute; bottom: 12px; right: 14px; font-size: .6rem; padding: 3px 8px; border-radius: 6px; background: rgba(255,215,0,.08); color: #ffd700; font-weight: 600; }

/* Single story */
.story-reader { max-width: 750px; margin: 0 auto; padding: 2rem 0; }
.story-reader .story-header { text-align: center; margin-bottom: 2rem; padding-bottom: 2rem; border-bottom: 2px solid rgba(34,197,94,.2); }
.story-reader .story-header h1 { font-size: clamp(1.5rem, 4vw, 2.5rem); font-weight: 700; color: #22c55e; margin-bottom: .5rem; }
.story-reader .story-header .ref { color: var(--akjv-gold); font-size: .9rem; }
.story-reader .story-body { font-size: 1.1rem; line-height: 2; color: rgba(255,255,255,.9); }
.story-reader .story-body p { margin-bottom: 1.2rem; }
.story-reader .verse-link { display: inline-block; margin-top: 1.5rem; padding: .6rem 1.2rem; background: rgba(59,130,246,.1); border: 1px solid rgba(59,130,246,.3); color: #60a5fa; border-radius: 8px; text-decoration: none; font-size: .85rem; font-weight: 600; }
.story-reader .verse-link:hover { border-color: #60a5fa; }
.story-reader .nav-stories { display: flex; justify-content: space-between; margin-top: 3rem; padding-top: 2rem; border-top: 1px solid rgba(255,255,255,.08); }
.story-reader .nav-stories a { color: var(--akjv-gold); text-decoration: none; font-size: .85rem; }
.story-reader .nav-stories a:hover { text-decoration: underline; }

/* Flipbook mode */
.flipbook-toggle { display: flex; gap: .5rem; justify-content: center; margin: 1.5rem 0; }
.flipbook-toggle button { padding: .4rem 1rem; border-radius: 8px; border: 1px solid rgba(34,197,94,.3); background: transparent; color: #22c55e; font-size: .8rem; font-weight: 600; cursor: pointer; transition: .2s; }
.flipbook-toggle button.active, .flipbook-toggle button:hover { background: rgba(34,197,94,.15); border-color: #22c55e; }

/* Fun emojis for stories */
.story-icons { display: flex; flex-wrap: wrap; gap: .3rem; margin: .6rem 0; }
.story-icons span { font-size: 1.1rem; }

/* Actions */
.children-actions { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin: 2rem 0; }
.children-actions .btn-c { padding: .6rem 1.5rem; border-radius: 10px; font-size: .9rem; font-weight: 700; text-decoration: none; border: 2px solid #22c55e; transition: all .3s; display: inline-flex; align-items: center; gap: .5rem; }
.children-actions .btn-c.primary { background: #22c55e; color: #0a0a0f; }
.children-actions .btn-c.primary:hover { filter: brightness(1.15); }
.children-actions .btn-c.secondary { background: transparent; color: #22c55e; }
.children-actions .btn-c.secondary:hover { background: rgba(34,197,94,.1); }

@media (max-width: 700px) { .story-grid { grid-template-columns: 1fr; } }
</style>

<div class="children-page" dir="<?= $dir ?>">
<?php if ($singleStory): ?>
    <!-- ═══ SINGLE STORY READER ═══ -->
    <div class="story-reader">
        <a href="/bible/children?lang=<?= $lang ?>" style="color:var(--akjv-gold);font-size:.85rem;text-decoration:none;display:inline-flex;align-items:center;gap:.3rem;margin-bottom:1rem;">← <?= $lang === 'fr' ? 'Tous les Récits' : 'All Stories' ?></a>
        <div class="story-header">
            <div style="font-size:3rem;margin-bottom:.5rem;"><?= akjv_children_story_emoji($singleStory['story_number']) ?></div>
            <h1><?= htmlspecialchars($singleStory['title']) ?></h1>
            <div class="ref">📖 <?= htmlspecialchars($singleStory['verse_reference'] ?? '') ?></div>
            <div style="margin-top:.5rem;font-size:.75rem;color:var(--akjv-muted);">
                Story <?= $singleStory['story_number'] ?> of 33 · <?= htmlspecialchars($singleStory['testament'] ?? '') ?> · Ages <?= htmlspecialchars($singleStory['age_group'] ?? '5-14') ?>
            </div>
        </div>
        <div class="story-body">
            <?= nl2br(htmlspecialchars($singleStory['content'] ?? $singleStory['story_text'] ?? '')) ?>
        </div>
        <?php if (!empty($singleStory['verse_reference'])): ?>
        <a href="/bible/read/<?= urlencode(preg_replace('/\s\d+:.*/', '', $singleStory['verse_reference'])) ?>?lang=<?= $lang ?>" class="verse-link">📖 <?= $lang === 'fr' ? 'Lire le verset AKJV original' : 'Read the original AKJV verse' ?> →</a>
        <?php endif; ?>
        <div class="nav-stories">
            <?php if ($singleStory['story_number'] > 1): ?>
            <a href="/bible/children?story=<?= $singleStory['id'] - 1 ?>&lang=<?= $lang ?>">← <?= $lang === 'fr' ? 'Récit Précédent' : 'Previous Story' ?></a>
            <?php else: ?><span></span><?php endif; ?>
            <?php if ($singleStory['story_number'] < 33): ?>
            <a href="/bible/children?story=<?= $singleStory['id'] + 1 ?>&lang=<?= $lang ?>"><?= $lang === 'fr' ? 'Récit Suivant' : 'Next Story' ?> →</a>
            <?php endif; ?>
        </div>
    </div>

<?php else: ?>
    <!-- ═══ STORY LIST ═══ -->
    <div class="children-hero">
        <h1><span class="rainbow"><?= akjv_t($ed['title'], $lang) ?></span></h1>
        <p class="subtitle"><?= akjv_t($ed['subtitle'], $lang) ?></p>
        <p class="eden"><?= $lang === 'fr' ? 'Pour Eden Sarai Gabrielle Vallee Perez — et tous les enfants de la promesse' : ($lang === 'he' ? 'לעדן שרי גבריאל ואלי פרץ — ולכל ילדי ההבטחה' : 'For Eden Sarai Gabrielle Vallee Perez — and all the children of the promise') ?></p>
        <p style="color:rgba(255,255,255,.4);font-style:italic;font-size:.8rem;margin-top:.5rem;">"Suffer the little children to come unto me, and forbid them not: for of such is the kingdom of God." — Mark 10:14 AKJV</p>
    </div>

    <div class="children-actions">
        <a href="/bible/children?story=1&lang=<?= $lang ?>" class="btn-c primary">🌟 <?= $lang === 'fr' ? 'Commencer la Lecture' : 'Start Reading' ?></a>
        <a href="/bible/pdf/children?lang=<?= $lang ?>" class="btn-c secondary">📄 <?= $lang === 'fr' ? 'Télécharger PDF' : 'Download PDF' ?></a>
        <a href="/bible/editions?lang=<?= $lang ?>" class="btn-c secondary">← <?= $lang === 'fr' ? 'Éditions' : 'Editions' ?></a>
    </div>

    <div style="text-align:center;margin:1rem 0;font-size:.85rem;color:var(--akjv-muted);">
        33 <?= $lang === 'fr' ? 'stories couvrant la Bible entière' : 'stories spanning the entire Bible' ?> · <?= $lang === 'fr' ? 'Ancien Testament' : 'Old Testament' ?> + <?= $lang === 'fr' ? 'Nouveau Testament' : 'New Testament' ?>
    </div>

    <div class="story-grid">
        <?php foreach ($all as $s): ?>
        <a href="/bible/children?story=<?= $s['id'] ?>&lang=<?= $lang ?>" class="story-card">
            <span class="snum <?= strtolower($s['testament'] ?? 'ot') ?>"><?= $s['story_number'] ?></span>
            <span class="emoji"><?= akjv_children_story_emoji($s['story_number']) ?></span>
            <h3><?= htmlspecialchars($s['title']) ?></h3>
            <div class="ref">📖 <?= htmlspecialchars($s['verse_reference'] ?? '') ?></div>
            <p class="summary"><?= htmlspecialchars(mb_substr($s['summary'] ?? '', 0, 120)) ?>…</p>
            <span class="age-badge"><?= htmlspecialchars($s['age_group'] ?? '5-14') ?></span>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- ═══ FOOTER SEAL ═══ -->
    <div style="text-align:center;padding:3rem 0;border-top:2px solid rgba(34,197,94,.2);margin-top:2rem;">
        <div style="font-size:1.8rem;margin-bottom:.5rem;">🌈 ✝ 🌈</div>
        <p style="color:var(--akjv-muted);font-size:.85rem;max-width:500px;margin:0 auto;line-height:1.7;">
            <?= $lang === 'fr' ? 'La Bible des Enfants — Édition AKJV · Perez Family · A.D. 2026' : 'The Children\'s Bible — AKJV Edition · Perez Family · A.D. 2026' ?>
        </p>
        <p style="color:rgba(34,197,94,.5);font-style:italic;font-size:.8rem;margin-top:.5rem;">"Train up a child in the way he should go: and when he is old, he will not depart from it." — Proverbs 22:6 AKJV</p>
    </div>
<?php endif; ?>
</div>

<?php
require_once __DIR__ . '/includes/site-footer.inc.php';

/**
 * Fun emoji for each of the 33 stories by number.
 */
function akjv_children_story_emoji(int $num): string {
    $emojis = [
        1 => '🌍', 2 => '🍎', 3 => '🚢', 4 => '🌈', 5 => '⭐', 6 => '👶', 7 => '🏜️', 8 => '🔥',
        9 => '🌊', 10 => '⛰️', 11 => '📜', 12 => '💪', 13 => '👑', 14 => '🎵', 15 => '🦁', 16 => '🐋',
        17 => '🕊️', 18 => '🔥', 19 => '📖', 20 => '🏛️', 21 => '⭐', 22 => '🐟', 23 => '🍞', 24 => '✨',
        25 => '🌿', 26 => '👁️', 27 => '💀', 28 => '🌅', 29 => '🔥', 30 => '⚔️', 31 => '📬', 32 => '🏰', 33 => '👼',
    ];
    return $emojis[$num] ?? '📖';
}
?>
